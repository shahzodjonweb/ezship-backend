#!/bin/bash

# Script to reset PostgreSQL password when there's a mismatch
# This should be run on the production server

echo "=== EzShip PostgreSQL Password Reset ==="
echo

# Default password that both app and postgres will use
NEW_PASSWORD="${DB_PASSWORD:-defaultpassword123}"

echo "1. Resetting PostgreSQL user password..."
# Connect to postgres container and reset the password
docker exec ezship-postgres-prod psql -U postgres -d postgres -c "ALTER USER ezship_user WITH PASSWORD '${NEW_PASSWORD}';"

if [ $? -eq 0 ]; then
    echo "✅ PostgreSQL password reset successfully"
else
    echo "❌ Failed to reset PostgreSQL password"
    echo "   Trying alternative method..."
    
    # Alternative: Use POSTGRES_USER from environment
    docker exec ezship-postgres-prod psql -U ezship_user -d ezship_production -c "ALTER USER ezship_user WITH PASSWORD '${NEW_PASSWORD}';"
fi

echo
echo "2. Updating .env file with new password..."
if [ -f .env ]; then
    # Backup current .env
    cp .env .env.backup.$(date +%Y%m%d_%H%M%S)
    
    # Update password in .env
    sed -i "s/^DB_PASSWORD=.*/DB_PASSWORD=${NEW_PASSWORD}/" .env
    
    # Add if missing
    grep -q "^DB_PASSWORD=" .env || echo "DB_PASSWORD=${NEW_PASSWORD}" >> .env
    
    echo "✅ .env file updated"
fi

echo
echo "3. Updating .env.prod if exists..."
if [ -f .env.prod ]; then
    sed -i "s/^DB_PASSWORD=.*/DB_PASSWORD=${NEW_PASSWORD}/" .env.prod
    grep -q "^DB_PASSWORD=" .env.prod || echo "DB_PASSWORD=${NEW_PASSWORD}" >> .env.prod
    echo "✅ .env.prod file updated"
fi

echo
echo "4. Restarting app container to pick up new password..."
docker restart ezship-app-prod

echo
echo "5. Waiting for services to be ready..."
sleep 10

echo
echo "6. Testing database connection..."
docker exec ezship-app-prod php artisan db:show

if [ $? -eq 0 ]; then
    echo "✅ Database connection successful!"
    
    echo
    echo "7. Running migrations..."
    docker exec ezship-app-prod php artisan migrate --force
    
    echo
    echo "8. Seeding admin user..."
    docker exec ezship-app-prod php artisan db:seed --class=AdminUserSeeder --force
    
    echo
    echo "9. Clearing caches..."
    docker exec ezship-app-prod php artisan config:clear
    docker exec ezship-app-prod php artisan cache:clear
    docker exec ezship-app-prod php artisan config:cache
    
    echo
    echo "=== Password Reset Complete ==="
    echo "Database is now accessible with the new password."
    echo
    echo "Admin credentials:"
    echo "  Email: admin@ezship.com"
    echo "  Password: password"
    echo
    echo "Test the login at: https://api.ezship.app/admin/login"
else
    echo "❌ Database connection still failing"
    echo "   Please check Docker logs:"
    echo "   docker logs ezship-postgres-prod"
    echo "   docker logs ezship-app-prod"
fi