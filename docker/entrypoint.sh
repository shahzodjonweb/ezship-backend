#!/bin/sh
set -e

echo "Starting EzShip Application..."

# Check if we're running in docker-compose environment
if [ -n "$DB_HOST" ]; then
  # Use environment variables if set
  DB_HOST="${DB_HOST:-127.0.0.1}"
  REDIS_HOST="${REDIS_HOST:-127.0.0.1}"
else
  # Use docker-compose service names
  DB_HOST="postgres"
  REDIS_HOST="redis"
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
  php artisan migrate --force || echo "Migration skipped or failed"
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
if [ -n "$EXTERNAL_NGINX" ] || [ -f /.dockerenv ]; then
  echo "Using app-only supervisor configuration (external nginx)..."
  exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.app-only.conf
elif [ -f /etc/supervisor/conf.d/supervisord.prod.conf ] && [ "$APP_ENV" = "production" ]; then
  echo "Using production supervisor configuration..."
  exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.prod.conf
else
  echo "Using default supervisor configuration..."
  exec "$@"
fi