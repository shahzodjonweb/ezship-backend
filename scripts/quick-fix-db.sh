#!/bin/bash

# Quick fix for database password mismatch
# Run on production: cd /opt/ezship && bash scripts/quick-fix-db.sh

echo "Quick fixing database password..."

# Use a simple password that we know
FIXED_PASSWORD="ezship123"

# Update PostgreSQL password
docker exec ezship-postgres-prod psql -U postgres -c "ALTER USER ezship_user WITH PASSWORD '${FIXED_PASSWORD}';" 2>/dev/null || \
docker exec ezship-postgres-prod psql -U ezship_user -c "ALTER USER ezship_user WITH PASSWORD '${FIXED_PASSWORD}';" 2>/dev/null

# Update .env files
for env_file in .env .env.prod; do
    if [ -f $env_file ]; then
        sed -i "s/^DB_PASSWORD=.*/DB_PASSWORD=${FIXED_PASSWORD}/" $env_file
        grep -q "^DB_PASSWORD=" $env_file || echo "DB_PASSWORD=${FIXED_PASSWORD}" >> $env_file
    fi
done

# Restart app to pick up new password
docker restart ezship-app-prod

sleep 5

# Test connection and run migrations
docker exec ezship-app-prod sh -c "php artisan migrate --force && php artisan db:seed --class=AdminUserSeeder --force"

echo "Done! Password set to: ${FIXED_PASSWORD}"
echo "Test at: https://api.ezship.app/admin/login"