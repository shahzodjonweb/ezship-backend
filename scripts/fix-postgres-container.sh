#!/bin/bash

# Fix PostgreSQL container and database connection
# Run: cd /opt/ezship && bash scripts/fix-postgres-container.sh

echo "=== Fixing PostgreSQL Container ==="
echo

# 1. First, ensure we're using the right host in .env
echo "1. Fixing database host configuration..."
docker exec ezship-app-prod sh -c "
    sed -i 's/^DB_HOST=.*/DB_HOST=postgres/' /var/www/html/.env
    sed -i 's/127.0.0.1/postgres/g' /var/www/html/.env
    grep -q '^DB_HOST=' /var/www/html/.env || echo 'DB_HOST=postgres' >> /var/www/html/.env
    echo 'DB_HOST set to postgres'
"

# 2. Check if postgres container is running
echo "2. Checking PostgreSQL container status..."
docker ps | grep ezship-postgres-prod

# 3. Try to connect directly to postgres container
echo "3. Testing connection to PostgreSQL container..."
docker exec ezship-postgres-prod psql -U ezship_user -d ezship_production -c '\l' 2>/dev/null || {
    echo "Connection as ezship_user failed, trying to fix..."
    
    # 4. Try to reset password using POSTGRES_USER
    echo "4. Attempting to reset password using environment user..."
    docker exec ezship-postgres-prod sh -c "
        PGPASSWORD=ezship123 psql -U ezship_user -d postgres -c \"ALTER USER ezship_user WITH PASSWORD 'ezship123';\" 2>/dev/null || {
            echo 'Could not connect as ezship_user'
            
            # Try to create the user if it doesn't exist
            PGPASSWORD=ezship123 psql -U postgres -d postgres -c \"
                CREATE USER ezship_user WITH PASSWORD 'ezship123';
                CREATE DATABASE ezship_production OWNER ezship_user;
                GRANT ALL PRIVILEGES ON DATABASE ezship_production TO ezship_user;
            \" 2>/dev/null || echo 'Could not create user'
        }
    "
}

# 5. Alternative: Recreate the postgres container with correct settings
echo "5. If the above failed, recreating PostgreSQL container..."
read -p "Do you want to recreate the PostgreSQL container? This will delete all data! (y/N): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo "Recreating PostgreSQL container..."
    
    # Stop and remove old container
    docker stop ezship-postgres-prod
    docker rm ezship-postgres-prod
    docker volume rm ezship-backend_postgres-prod-data 2>/dev/null || true
    
    # Start new postgres container with correct settings
    docker run -d \
        --name ezship-postgres-prod \
        --network ezship-backend_ezship-network \
        -e POSTGRES_USER=ezship_user \
        -e POSTGRES_PASSWORD=ezship123 \
        -e POSTGRES_DB=ezship_production \
        -v ezship-backend_postgres-prod-data:/var/lib/postgresql/data \
        postgres:15-alpine
    
    echo "Waiting for new PostgreSQL container to be ready..."
    sleep 10
fi

# 6. Update app container environment and restart
echo "6. Updating app container configuration..."
docker exec ezship-app-prod sh -c "
    # Ensure correct database configuration
    sed -i 's/^DB_CONNECTION=.*/DB_CONNECTION=pgsql/' /var/www/html/.env
    sed -i 's/^DB_HOST=.*/DB_HOST=postgres/' /var/www/html/.env
    sed -i 's/^DB_PORT=.*/DB_PORT=5432/' /var/www/html/.env
    sed -i 's/^DB_DATABASE=.*/DB_DATABASE=ezship_production/' /var/www/html/.env
    sed -i 's/^DB_USERNAME=.*/DB_USERNAME=ezship_user/' /var/www/html/.env
    sed -i 's/^DB_PASSWORD=.*/DB_PASSWORD=ezship123/' /var/www/html/.env
    
    # Clear config cache
    php artisan config:clear
    php artisan cache:clear
"

# 7. Restart app container
echo "7. Restarting app container..."
docker restart ezship-app-prod
sleep 5

# 8. Test database connection
echo "8. Testing database connection from app..."
docker exec ezship-app-prod php artisan db:show || {
    echo "Connection still failing. Checking configuration..."
    docker exec ezship-app-prod grep "DB_" /var/www/html/.env
}

# 9. Run migrations if connection works
echo "9. Running migrations..."
docker exec ezship-app-prod php artisan migrate --force || echo "Migration failed"

# 10. Seed admin user
echo "10. Seeding admin user..."
docker exec ezship-app-prod php artisan db:seed --class=AdminUserSeeder --force || echo "Seeding failed"

echo
echo "=== Fix Complete ==="
echo
echo "Test the connection at:"
echo "  https://api.ezship.app/check.php"
echo "  https://api.ezship.app/admin/login"
echo
echo "If still having issues, check:"
echo "  docker logs ezship-postgres-prod"
echo "  docker exec ezship-app-prod cat /var/www/html/.env | grep DB_"