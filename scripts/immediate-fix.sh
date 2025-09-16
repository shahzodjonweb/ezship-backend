#!/bin/bash

# Immediate fix without rebuilding containers
# Run: cd /opt/ezship && bash scripts/immediate-fix.sh

echo "=== Immediate Database Fix ==="

# Set password to ezship123 in all places
PASSWORD="ezship123"

# 1. Update .env in the app container
echo "1. Updating app container environment..."
docker exec ezship-app-prod sh -c "sed -i 's/^DB_PASSWORD=.*/DB_PASSWORD=${PASSWORD}/' /var/www/html/.env"

# 2. Reset PostgreSQL password
echo "2. Resetting PostgreSQL password..."
docker exec ezship-postgres-prod psql -U postgres -c "ALTER USER ezship_user WITH PASSWORD '${PASSWORD}';"

# 3. Restart app to pick up changes
echo "3. Restarting app container..."
docker restart ezship-app-prod

# 4. Wait for app to be ready
echo "4. Waiting for app to be ready..."
sleep 10

# 5. Test connection
echo "5. Testing connection..."
docker exec ezship-app-prod php artisan migrate --force

# 6. Seed admin
echo "6. Seeding admin user..."
docker exec ezship-app-prod php artisan db:seed --class=AdminUserSeeder --force

echo
echo "=== Fix Applied ==="
echo "Password is now: ${PASSWORD}"
echo "Test at: https://api.ezship.app/admin/login"