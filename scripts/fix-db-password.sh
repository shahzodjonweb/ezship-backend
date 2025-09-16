#!/bin/bash

# Script to fix database password mismatch in production
# Run this on the production server after deployment

echo "=== EzShip Database Password Fix ==="
echo

# Set the correct password (use the same one for both app and postgres)
DB_PASSWORD="${DB_PASSWORD:-defaultpassword123}"

echo "1. Updating .env file with database credentials..."
if [ -f .env ]; then
    # Backup current .env
    cp .env .env.backup.$(date +%Y%m%d_%H%M%S)
    
    # Update database credentials
    sed -i "s/^DB_CONNECTION=.*/DB_CONNECTION=pgsql/" .env
    sed -i "s/^DB_HOST=.*/DB_HOST=postgres/" .env
    sed -i "s/^DB_PORT=.*/DB_PORT=5432/" .env
    sed -i "s/^DB_DATABASE=.*/DB_DATABASE=ezship_production/" .env
    sed -i "s/^DB_USERNAME=.*/DB_USERNAME=ezship_user/" .env
    sed -i "s/^DB_PASSWORD=.*/DB_PASSWORD=${DB_PASSWORD}/" .env
    
    # Add if missing
    grep -q "^DB_CONNECTION=" .env || echo "DB_CONNECTION=pgsql" >> .env
    grep -q "^DB_HOST=" .env || echo "DB_HOST=postgres" >> .env
    grep -q "^DB_PORT=" .env || echo "DB_PORT=5432" >> .env
    grep -q "^DB_DATABASE=" .env || echo "DB_DATABASE=ezship_production" >> .env
    grep -q "^DB_USERNAME=" .env || echo "DB_USERNAME=ezship_user" >> .env
    grep -q "^DB_PASSWORD=" .env || echo "DB_PASSWORD=${DB_PASSWORD}" >> .env
    
    echo "✅ .env file updated"
else
    echo "❌ .env file not found!"
    exit 1
fi

echo
echo "2. Restarting containers with updated configuration..."

# Stop containers
docker-compose -f docker-compose.prod.yml down

# Start with updated environment
DB_PASSWORD="${DB_PASSWORD}" docker-compose -f docker-compose.prod.yml up -d

echo
echo "3. Waiting for services to be ready..."
sleep 10

echo
echo "4. Running migrations and seeding..."
docker exec ezship-app-prod php artisan migrate --force
docker exec ezship-app-prod php artisan db:seed --class=AdminUserSeeder --force

echo
echo "5. Clearing application cache..."
docker exec ezship-app-prod php artisan config:clear
docker exec ezship-app-prod php artisan cache:clear
docker exec ezship-app-prod php artisan config:cache

echo
echo "=== Fix Complete ==="
echo "Admin credentials:"
echo "  Email: admin@ezship.com"
echo "  Password: password"
echo
echo "Test the login at: https://api.ezship.app/admin/login"