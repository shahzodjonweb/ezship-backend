#!/bin/sh
set -e

echo "Starting EzShip Application..."

# Set database and Redis hosts for docker-compose environment
DB_HOST="${DB_HOST:-postgres}"
REDIS_HOST="${REDIS_HOST:-redis}"

# Export for PHP processes
export DB_HOST
export REDIS_HOST

# Update .env file with correct hosts
if [ -f /var/www/html/.env ]; then
  sed -i "s/^DB_HOST=.*/DB_HOST=${DB_HOST}/" /var/www/html/.env
  sed -i "s/^REDIS_HOST=.*/REDIS_HOST=${REDIS_HOST}/" /var/www/html/.env
  
  # Add REDIS_HOST if it doesn't exist
  grep -q "^REDIS_HOST=" /var/www/html/.env || echo "REDIS_HOST=${REDIS_HOST}" >> /var/www/html/.env
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
else
  echo "Skipping migrations (no database configured)"
fi

# Clear and cache config
echo "Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# Create storage symlink if it doesn't exist
php artisan storage:link || true

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