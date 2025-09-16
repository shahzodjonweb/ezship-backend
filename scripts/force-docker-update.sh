#!/bin/bash

# Force update Docker containers with new password configuration
# Run this on production server: cd /opt/ezship && bash scripts/force-docker-update.sh

echo "=== Force Docker Update for EzShip ==="
echo

# Pull latest changes
echo "1. Pulling latest code..."
git pull origin master

# Stop all containers
echo "2. Stopping all containers..."
docker-compose -f docker-compose.prod.yml down

# Remove old volumes to force recreation
echo "3. Removing old database volume to force clean setup..."
docker volume rm ezship-backend_postgres-prod-data 2>/dev/null || true

# Set the password in environment
export DB_PASSWORD=ezship123

# Update .env files with fixed password
echo "4. Updating .env files with fixed password..."
for env_file in .env .env.prod; do
    if [ -f $env_file ]; then
        cp $env_file ${env_file}.backup.$(date +%Y%m%d_%H%M%S)
        
        # Update database configuration
        sed -i "s/^DB_CONNECTION=.*/DB_CONNECTION=pgsql/" $env_file
        sed -i "s/^DB_HOST=.*/DB_HOST=postgres/" $env_file
        sed -i "s/^DB_PORT=.*/DB_PORT=5432/" $env_file
        sed -i "s/^DB_DATABASE=.*/DB_DATABASE=ezship_production/" $env_file
        sed -i "s/^DB_USERNAME=.*/DB_USERNAME=ezship_user/" $env_file
        sed -i "s/^DB_PASSWORD=.*/DB_PASSWORD=ezship123/" $env_file
        
        # Add if missing
        grep -q "^DB_CONNECTION=" $env_file || echo "DB_CONNECTION=pgsql" >> $env_file
        grep -q "^DB_HOST=" $env_file || echo "DB_HOST=postgres" >> $env_file
        grep -q "^DB_PORT=" $env_file || echo "DB_PORT=5432" >> $env_file
        grep -q "^DB_DATABASE=" $env_file || echo "DB_DATABASE=ezship_production" >> $env_file
        grep -q "^DB_USERNAME=" $env_file || echo "DB_USERNAME=ezship_user" >> $env_file
        grep -q "^DB_PASSWORD=" $env_file || echo "DB_PASSWORD=ezship123" >> $env_file
        
        echo "  ✅ Updated $env_file"
    fi
done

# Rebuild images with latest changes
echo "5. Rebuilding Docker images..."
docker-compose -f docker-compose.prod.yml build --no-cache app

# Start containers with new configuration
echo "6. Starting containers with new configuration..."
docker-compose -f docker-compose.prod.yml up -d

# Wait for services
echo "7. Waiting for services to initialize..."
sleep 30

# Check container status
echo "8. Container status:"
docker-compose -f docker-compose.prod.yml ps

# Test database connection
echo "9. Testing database connection..."
docker exec ezship-app-prod php artisan db:show || {
    echo "Database connection failed, checking logs..."
    docker logs ezship-postgres-prod --tail 20
    docker logs ezship-app-prod --tail 20
}

# Force run migrations
echo "10. Running migrations..."
docker exec ezship-app-prod php artisan migrate:fresh --force --seed

echo
echo "=== Update Complete ==="
echo
echo "The database has been completely reset with:"
echo "  Password: ezship123"
echo "  Database: ezship_production"
echo "  User: ezship_user"
echo
echo "Admin login:"
echo "  URL: https://api.ezship.app/admin/login"
echo "  Email: admin@ezship.com"
echo "  Password: password"
echo
echo "Test the connection:"
echo "  https://api.ezship.app/check.php"
echo "  https://api.ezship.app/db-test.php"