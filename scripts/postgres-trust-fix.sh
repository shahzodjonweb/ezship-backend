#!/bin/bash

# Fix PostgreSQL by temporarily enabling trust authentication
# Run: cd /opt/ezship && bash scripts/postgres-trust-fix.sh

echo "=== PostgreSQL Trust Authentication Fix ==="
echo

# 1. Modify PostgreSQL to trust all connections temporarily
echo "1. Enabling trust authentication in PostgreSQL..."
docker exec ezship-postgres-prod sh -c "
    echo 'host all all 0.0.0.0/0 trust' >> /var/lib/postgresql/data/pg_hba.conf
    echo 'local all all trust' >> /var/lib/postgresql/data/pg_hba.conf
"

# 2. Reload PostgreSQL configuration
echo "2. Reloading PostgreSQL configuration..."
docker exec ezship-postgres-prod psql -U ezship_user -c "SELECT pg_reload_conf();" 2>/dev/null || {
    # If that fails, restart the container
    docker restart ezship-postgres-prod
    sleep 5
}

# 3. Connect and reset the password
echo "3. Resetting password to ezship123..."
docker exec ezship-postgres-prod psql -U postgres -d postgres -c "
    ALTER USER ezship_user WITH PASSWORD 'ezship123';
    GRANT ALL PRIVILEGES ON DATABASE ezship_production TO ezship_user;
" 2>/dev/null || docker exec ezship-postgres-prod psql -U ezship_user -d postgres -c "
    ALTER USER ezship_user WITH PASSWORD 'ezship123';
"

# 4. Restore secure authentication
echo "4. Restoring secure authentication..."
docker exec ezship-postgres-prod sh -c "
    # Remove trust lines we added
    sed -i '/host all all 0.0.0.0\/0 trust/d' /var/lib/postgresql/data/pg_hba.conf
    sed -i '/local all all trust/d' /var/lib/postgresql/data/pg_hba.conf
"

# 5. Reload configuration again
echo "5. Reloading configuration..."
docker restart ezship-postgres-prod
sleep 5

# 6. Update app configuration
echo "6. Updating app configuration..."
docker exec ezship-app-prod sh -c "
    sed -i 's/^DB_PASSWORD=.*/DB_PASSWORD=ezship123/' /var/www/html/.env
    php artisan config:clear
    php artisan cache:clear
"

# 7. Test connection
echo "7. Testing connection..."
docker exec ezship-app-prod php artisan db:show 2>/dev/null && {
    echo "✓ Connection successful!"
    
    # Run migrations
    echo "8. Running migrations..."
    docker exec ezship-app-prod php artisan migrate --force
    
    # Seed admin
    echo "9. Seeding admin user..."
    docker exec ezship-app-prod php artisan db:seed --class=AdminUserSeeder --force
    
    echo
    echo "=== Success! ==="
    echo "Password is now: ezship123"
} || {
    echo "✗ Connection still failing"
    echo "Try the complete recreation: bash scripts/final-fix.sh"
}