#!/bin/bash

# Final fix - recreate PostgreSQL container with correct password
# Run: cd /opt/ezship && bash scripts/final-fix.sh

echo "=== Final Database Password Fix ==="
echo
echo "This will recreate the PostgreSQL container with the correct password."
echo "WARNING: This will DELETE all existing data!"
echo

read -p "Continue? (y/N): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Aborted."
    exit 1
fi

# 1. Stop all containers
echo "1. Stopping all containers..."
docker-compose -f docker-compose.prod.yml down

# 2. Remove PostgreSQL volume completely
echo "2. Removing old PostgreSQL data..."
docker volume rm ezship-backend_postgres-prod-data 2>/dev/null || true
docker volume rm $(docker volume ls -q | grep postgres) 2>/dev/null || true

# 3. Remove old containers
echo "3. Removing old containers..."
docker rm ezship-postgres-prod 2>/dev/null || true
docker rm ezship-app-prod 2>/dev/null || true

# 4. Create new .env with correct password
echo "4. Creating correct .env file..."
cat > .env << 'EOF'
APP_NAME=EzShip
APP_ENV=production
APP_KEY=base64:w3XEs41oX2moZD2wI2XzRXiwtdT4peFbpNDttVVSj90=
APP_DEBUG=false
APP_URL=https://api.ezship.app

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=ezship_production
DB_USERNAME=ezship_user
DB_PASSWORD=ezship123

SESSION_DRIVER=file
CACHE_DRIVER=file
QUEUE_CONNECTION=sync

REDIS_HOST=redis
REDIS_PORT=6379
EOF

echo "5. Creating .env.prod with same settings..."
cp .env .env.prod

# 6. Start PostgreSQL first with correct password
echo "6. Starting PostgreSQL with password: ezship123..."
docker run -d \
    --name ezship-postgres-prod \
    --network ezship-backend_ezship-network \
    -e POSTGRES_USER=ezship_user \
    -e POSTGRES_PASSWORD=ezship123 \
    -e POSTGRES_DB=ezship_production \
    postgres:15-alpine

# 7. Wait for PostgreSQL to be ready
echo "7. Waiting for PostgreSQL to initialize..."
sleep 15

# 8. Verify PostgreSQL is working
echo "8. Testing PostgreSQL connection..."
docker exec ezship-postgres-prod psql -U ezship_user -d ezship_production -c "SELECT 1;" && {
    echo "✓ PostgreSQL is working!"
} || {
    echo "✗ PostgreSQL connection failed"
    echo "Checking PostgreSQL logs:"
    docker logs ezship-postgres-prod --tail 20
    exit 1
}

# 9. Start app container
echo "9. Starting app container..."
docker-compose -f docker-compose.prod.yml up -d app

# 10. Wait for app to be ready
echo "10. Waiting for app to start..."
sleep 10

# 11. Ensure app has correct environment
echo "11. Setting app environment..."
docker exec ezship-app-prod sh -c "
    cd /var/www/html
    
    # Update .env in container
    cat > .env << 'ENVFILE'
APP_NAME=EzShip
APP_ENV=production
APP_KEY=base64:w3XEs41oX2moZD2wI2XzRXiwtdT4peFbpNDttVVSj90=
APP_DEBUG=false
APP_URL=https://api.ezship.app

DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=ezship_production
DB_USERNAME=ezship_user
DB_PASSWORD=ezship123

SESSION_DRIVER=file
CACHE_DRIVER=file
QUEUE_CONNECTION=sync

REDIS_HOST=redis
REDIS_PORT=6379
ENVFILE
    
    # Clear all caches
    php artisan config:clear
    php artisan cache:clear
    php artisan view:clear
    
    # Generate key if needed
    php artisan key:generate --force
    
    # Set permissions
    chown -R www-data:www-data storage bootstrap/cache
    chmod -R 775 storage bootstrap/cache
"

# 12. Run migrations
echo "12. Running migrations..."
docker exec ezship-app-prod php artisan migrate:fresh --force --seed

# 13. Start other services
echo "13. Starting remaining services..."
docker-compose -f docker-compose.prod.yml up -d

# 14. Final test
echo "14. Testing database connection..."
sleep 5
docker exec ezship-app-prod php artisan tinker --execute="DB::select('SELECT 1');" && {
    echo
    echo "=== SUCCESS! ==="
    echo "Database is now working!"
    echo "Password: ezship123"
    echo
    echo "Admin credentials:"
    echo "  Email: admin@ezship.com"
    echo "  Password: password"
    echo
    echo "Test at:"
    echo "  https://api.ezship.app/check.php"
    echo "  https://api.ezship.app/admin/login"
} || {
    echo
    echo "=== FAILED ==="
    echo "Connection still not working."
    echo "Check logs:"
    echo "  docker logs ezship-postgres-prod"
    echo "  docker logs ezship-app-prod"
}