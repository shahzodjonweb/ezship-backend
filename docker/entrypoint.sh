#!/bin/sh
set -e

echo "Starting EzShip Application..."

# Set database and Redis hosts for docker-compose environment
DB_HOST="${DB_HOST:-postgres}"
REDIS_HOST="${REDIS_HOST:-redis}"
DB_CONNECTION="${DB_CONNECTION:-pgsql}"
DB_PORT="${DB_PORT:-5432}"
DB_DATABASE="${DB_DATABASE:-ezship_production}"
DB_USERNAME="${DB_USERNAME:-ezship_user}"
DB_PASSWORD="${DB_PASSWORD:-changeme}"

# Export for PHP processes
export DB_HOST
export REDIS_HOST
export DB_CONNECTION
export DB_PORT
export DB_DATABASE
export DB_USERNAME
export DB_PASSWORD

# Update .env file with correct hosts and database settings
if [ -f /var/www/html/.env ]; then
  sed -i "s/^DB_HOST=.*/DB_HOST=${DB_HOST}/" /var/www/html/.env
  sed -i "s/^REDIS_HOST=.*/REDIS_HOST=${REDIS_HOST}/" /var/www/html/.env
  
  # Ensure all database settings are in .env
  grep -q "^DB_CONNECTION=" /var/www/html/.env || echo "DB_CONNECTION=${DB_CONNECTION}" >> /var/www/html/.env
  grep -q "^DB_HOST=" /var/www/html/.env || echo "DB_HOST=${DB_HOST}" >> /var/www/html/.env
  grep -q "^DB_PORT=" /var/www/html/.env || echo "DB_PORT=${DB_PORT}" >> /var/www/html/.env
  grep -q "^DB_DATABASE=" /var/www/html/.env || echo "DB_DATABASE=${DB_DATABASE}" >> /var/www/html/.env
  grep -q "^DB_USERNAME=" /var/www/html/.env || echo "DB_USERNAME=${DB_USERNAME}" >> /var/www/html/.env
  grep -q "^DB_PASSWORD=" /var/www/html/.env || echo "DB_PASSWORD=${DB_PASSWORD}" >> /var/www/html/.env
  grep -q "^REDIS_HOST=" /var/www/html/.env || echo "REDIS_HOST=${REDIS_HOST}" >> /var/www/html/.env
  
  # Update existing values
  sed -i "s/^DB_CONNECTION=.*/DB_CONNECTION=${DB_CONNECTION}/" /var/www/html/.env
  sed -i "s/^DB_PORT=.*/DB_PORT=${DB_PORT}/" /var/www/html/.env
  sed -i "s/^DB_DATABASE=.*/DB_DATABASE=${DB_DATABASE}/" /var/www/html/.env
  sed -i "s/^DB_USERNAME=.*/DB_USERNAME=${DB_USERNAME}/" /var/www/html/.env
  sed -i "s/^DB_PASSWORD=.*/DB_PASSWORD=${DB_PASSWORD}/" /var/www/html/.env
fi

# Wait for database to be ready (with timeout)
echo "Waiting for database at $DB_HOST..."
WAIT_COUNT=0
while ! nc -z $DB_HOST 5432 2>/dev/null; do
  WAIT_COUNT=$((WAIT_COUNT + 1))
  if [ $WAIT_COUNT -gt 30 ]; then
    echo "Warning: Database connection timeout, proceeding anyway..."
    break
  fi
  sleep 1
done
echo "Database check complete!"

# Wait for Redis to be ready (with timeout)
echo "Waiting for Redis at $REDIS_HOST..."
WAIT_COUNT=0
while ! nc -z $REDIS_HOST 6379 2>/dev/null; do
  WAIT_COUNT=$((WAIT_COUNT + 1))
  if [ $WAIT_COUNT -gt 30 ]; then
    echo "Warning: Redis connection timeout, proceeding anyway..."
    break
  fi
  sleep 1
done
echo "Redis check complete!"

# Run migrations (only if database is configured)
if [ "$DB_CONNECTION" = "pgsql" ] || [ "$DB_CONNECTION" = "mysql" ]; then
  echo "Running migrations..."
  php artisan migrate --force || {
    echo "WARNING: Migration failed - continuing anyway"
    echo "Database might not be ready or credentials incorrect"
  }
  
  # Seed admin user if migrations succeeded
  if [ $? -eq 0 ]; then
    echo "Seeding admin user..."
    php artisan db:seed --class=AdminUserSeeder --force || {
      echo "Admin user seeding skipped (may already exist)"
    }
  fi
else
  echo "Skipping migrations (no database configured)"
fi

# Clear and cache config (with error handling)
echo "Optimizing application..."
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true
php artisan cache:clear || true

php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Create storage symlink if it doesn't exist
php artisan storage:link || true

# Generate Passport keys if they don't exist
echo "Checking Passport configuration..."
if [ ! -f "/var/www/html/storage/oauth-private.key" ] || [ ! -f "/var/www/html/storage/oauth-public.key" ]; then
    echo "Installing Passport..."
    php artisan passport:install --force --no-interaction || {
        echo "Passport install failed, trying keys only..."
        php artisan passport:keys --force || true
    }
fi

# Ensure keys have correct permissions
if [ -f "/var/www/html/storage/oauth-private.key" ]; then
    chmod 600 /var/www/html/storage/oauth-private.key
    chown www-data:www-data /var/www/html/storage/oauth-private.key
    echo "✓ Private key configured"
fi

if [ -f "/var/www/html/storage/oauth-public.key" ]; then
    chmod 644 /var/www/html/storage/oauth-public.key
    chown www-data:www-data /var/www/html/storage/oauth-public.key
    echo "✓ Public key configured"
fi

# Ensure log file exists and is writable
touch /var/www/html/storage/logs/laravel.log || true
chmod -R 775 /var/www/html/storage || true
chown -R www-data:www-data /var/www/html/storage || true

# Set permissions
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

echo "Application ready!"

# Choose supervisor config based on environment
# Check if we're running with external nginx (docker-compose)
if [ -n "$EXTERNAL_NGINX" ]; then
  echo "Using app-only supervisor configuration (external nginx detected via env var)..."
  exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.app-only.conf
elif [ -f /etc/supervisor/conf.d/supervisord.prod.conf ] && [ "$APP_ENV" = "production" ]; then
  echo "Using production supervisor configuration..."
  exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.prod.conf
else
  echo "Using default supervisor configuration..."
  exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
fi