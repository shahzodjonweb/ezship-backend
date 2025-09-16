#!/bin/bash

# Simple fix to ensure database host is set correctly
# Run: cd /opt/ezship && bash scripts/simple-db-fix.sh

echo "=== Simple Database Fix ==="
echo

# 1. Fix DB_HOST to use 'postgres' not '127.0.0.1'
echo "1. Setting DB_HOST to 'postgres' (Docker container name)..."
docker exec ezship-app-prod sh -c "
    sed -i 's/127.0.0.1/postgres/g' /var/www/html/.env
    sed -i 's/^DB_HOST=.*/DB_HOST=postgres/' /var/www/html/.env
    echo 'DB_HOST fixed'
"

# 2. Ensure all DB settings are correct
echo "2. Setting all database variables..."
docker exec ezship-app-prod sh -c "
    cat > /tmp/db_config.txt << 'EOF'
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=ezship_production
DB_USERNAME=ezship_user
DB_PASSWORD=ezship123
EOF
    
    # Update each setting
    while IFS='=' read -r key value; do
        sed -i \"s/^\${key}=.*/\${key}=\${value}/\" /var/www/html/.env
        grep -q \"^\${key}=\" /var/www/html/.env || echo \"\${key}=\${value}\" >> /var/www/html/.env
    done < /tmp/db_config.txt
    
    rm /tmp/db_config.txt
    echo 'Database configuration updated'
"

# 3. Clear Laravel cache
echo "3. Clearing Laravel cache..."
docker exec ezship-app-prod php artisan config:clear
docker exec ezship-app-prod php artisan cache:clear

# 4. Test connection
echo "4. Testing database connection..."
docker exec ezship-app-prod php artisan tinker --execute="DB::select('SELECT 1');" && {
    echo "✓ Database connection successful!"
    
    # 5. Run migrations
    echo "5. Running migrations..."
    docker exec ezship-app-prod php artisan migrate --force
    
    # 6. Seed admin
    echo "6. Seeding admin user..."
    docker exec ezship-app-prod php artisan db:seed --class=AdminUserSeeder --force
    
    echo
    echo "=== Success! ==="
    echo "Database is now working with:"
    echo "  Host: postgres"
    echo "  Database: ezship_production"
    echo "  User: ezship_user"
    echo "  Password: ezship123"
} || {
    echo "✗ Database connection failed"
    echo
    echo "Current configuration:"
    docker exec ezship-app-prod grep "DB_" /var/www/html/.env
    echo
    echo "You may need to recreate the PostgreSQL container."
    echo "Run: bash scripts/fix-postgres-container.sh"
}

echo
echo "Test at: https://api.ezship.app/admin/login"