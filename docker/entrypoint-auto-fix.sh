#!/bin/sh
set -e

echo "Starting EzShip Application with Auto-Fix..."

# Set database and Redis hosts for docker-compose environment
DB_HOST="${DB_HOST:-postgres}"
REDIS_HOST="${REDIS_HOST:-redis}"
DB_CONNECTION="${DB_CONNECTION:-pgsql}"
DB_PORT="${DB_PORT:-5432}"
DB_DATABASE="${DB_DATABASE:-ezship_production}"
DB_USERNAME="${DB_USERNAME:-ezship_user}"
DB_PASSWORD="${DB_PASSWORD:-ezship123}"  # Use a fixed known password

# Export for PHP processes
export DB_HOST
export REDIS_HOST
export DB_CONNECTION
export DB_PORT
export DB_DATABASE
export DB_USERNAME
export DB_PASSWORD

echo "Database configuration:"
echo "  Host: $DB_HOST"
echo "  Database: $DB_DATABASE"
echo "  Username: $DB_USERNAME"

# Ensure .env file exists
if [ ! -f /var/www/html/.env ]; then
    echo "Creating .env file from example..."
    if [ -f /var/www/html/.env.docker ]; then
        cp /var/www/html/.env.docker /var/www/html/.env
    elif [ -f /var/www/html/.env.example ]; then
        cp /var/www/html/.env.example /var/www/html/.env
    else
        echo "Creating minimal .env file..."
        cat > /var/www/html/.env << EOF
APP_NAME=EzShip
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://api.ezship.app

DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=ezship_production
DB_USERNAME=ezship_user
DB_PASSWORD=ezship123

REDIS_HOST=redis
EOF
    fi
fi

# Update .env file with correct database settings
echo "Updating .env with database configuration..."
sed -i "s/^DB_CONNECTION=.*/DB_CONNECTION=${DB_CONNECTION}/" /var/www/html/.env
sed -i "s/^DB_HOST=.*/DB_HOST=${DB_HOST}/" /var/www/html/.env
sed -i "s/^DB_PORT=.*/DB_PORT=${DB_PORT}/" /var/www/html/.env
sed -i "s/^DB_DATABASE=.*/DB_DATABASE=${DB_DATABASE}/" /var/www/html/.env
sed -i "s/^DB_USERNAME=.*/DB_USERNAME=${DB_USERNAME}/" /var/www/html/.env
sed -i "s/^DB_PASSWORD=.*/DB_PASSWORD=${DB_PASSWORD}/" /var/www/html/.env
sed -i "s/^REDIS_HOST=.*/REDIS_HOST=${REDIS_HOST}/" /var/www/html/.env

# Add missing entries
grep -q "^DB_CONNECTION=" /var/www/html/.env || echo "DB_CONNECTION=${DB_CONNECTION}" >> /var/www/html/.env
grep -q "^DB_HOST=" /var/www/html/.env || echo "DB_HOST=${DB_HOST}" >> /var/www/html/.env
grep -q "^DB_PORT=" /var/www/html/.env || echo "DB_PORT=${DB_PORT}" >> /var/www/html/.env
grep -q "^DB_DATABASE=" /var/www/html/.env || echo "DB_DATABASE=${DB_DATABASE}" >> /var/www/html/.env
grep -q "^DB_USERNAME=" /var/www/html/.env || echo "DB_USERNAME=${DB_USERNAME}" >> /var/www/html/.env
grep -q "^DB_PASSWORD=" /var/www/html/.env || echo "DB_PASSWORD=${DB_PASSWORD}" >> /var/www/html/.env
grep -q "^REDIS_HOST=" /var/www/html/.env || echo "REDIS_HOST=${REDIS_HOST}" >> /var/www/html/.env

# Generate app key if missing
if ! grep -q "^APP_KEY=base64:" /var/www/html/.env; then
    echo "Generating application key..."
    php artisan key:generate --force
fi

# Wait for database with extended timeout and connection test
echo "Waiting for database at $DB_HOST:$DB_PORT..."
WAIT_COUNT=0
MAX_WAIT=60

while [ $WAIT_COUNT -lt $MAX_WAIT ]; do
    if nc -z $DB_HOST $DB_PORT 2>/dev/null; then
        echo "Database port is open, testing connection..."
        
        # Try to connect with psql to test credentials
        if PGPASSWORD=$DB_PASSWORD psql -h $DB_HOST -U $DB_USERNAME -d postgres -c '\q' 2>/dev/null; then
            echo "Database connection successful!"
            break
        else
            echo "Database connection failed, attempting to fix password..."
            
            # Try to fix password using postgres superuser
            # This assumes the postgres container allows trust authentication locally
            if nc -z $DB_HOST $DB_PORT; then
                echo "Attempting to reset user password..."
                
                # Create a SQL script to fix the user
                cat > /tmp/fix_user.sql << EOF
-- Ensure user exists with correct password
DO \$\$
BEGIN
    IF NOT EXISTS (SELECT FROM pg_user WHERE usename = '${DB_USERNAME}') THEN
        CREATE USER ${DB_USERNAME} WITH PASSWORD '${DB_PASSWORD}';
    ELSE
        ALTER USER ${DB_USERNAME} WITH PASSWORD '${DB_PASSWORD}';
    END IF;
END
\$\$;

-- Ensure database exists
SELECT 'CREATE DATABASE ${DB_DATABASE}' WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = '${DB_DATABASE}');

-- Grant privileges
GRANT ALL PRIVILEGES ON DATABASE ${DB_DATABASE} TO ${DB_USERNAME};
EOF
                
                # Try to apply the fix (this might fail if we don't have superuser access)
                PGPASSWORD=$DB_PASSWORD psql -h $DB_HOST -U postgres -f /tmp/fix_user.sql 2>/dev/null || true
                rm -f /tmp/fix_user.sql
            fi
        fi
    fi
    
    WAIT_COUNT=$((WAIT_COUNT + 1))
    sleep 1
done

if [ $WAIT_COUNT -ge $MAX_WAIT ]; then
    echo "Warning: Database connection timeout after ${MAX_WAIT} seconds"
    echo "Proceeding anyway, but database operations may fail"
fi

# Wait for Redis
echo "Waiting for Redis at $REDIS_HOST..."
WAIT_COUNT=0
while ! nc -z $REDIS_HOST 6379 2>/dev/null && [ $WAIT_COUNT -lt 30 ]; do
    WAIT_COUNT=$((WAIT_COUNT + 1))
    sleep 1
done
echo "Redis check complete!"

# Run migrations
echo "Running database migrations..."
php artisan migrate --force 2>&1 | tee /tmp/migration.log

# Check if migrations succeeded
if grep -q "Nothing to migrate" /tmp/migration.log || grep -q "Migrated:" /tmp/migration.log; then
    echo "Migrations completed successfully!"
    
    # Seed admin user
    echo "Seeding admin user..."
    php artisan db:seed --class=AdminUserSeeder --force 2>&1 || echo "Admin user may already exist"
else
    echo "Warning: Migrations may have failed, check logs"
    cat /tmp/migration.log
fi

# Clear and optimize
echo "Optimizing application..."
php artisan config:clear
php artisan cache:clear
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Create storage symlink
php artisan storage:link 2>/dev/null || true

# Set permissions
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true

echo "Application ready!"

# Start supervisor based on environment
if [ -n "$EXTERNAL_NGINX" ]; then
    echo "Starting with app-only configuration (external nginx)..."
    exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.app-only.conf
elif [ "$APP_ENV" = "production" ] && [ -f /etc/supervisor/conf.d/supervisord.prod.conf ]; then
    echo "Starting with production configuration..."
    exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.prod.conf
else
    echo "Starting with default configuration..."
    exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
fi