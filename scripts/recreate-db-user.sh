#!/bin/bash

# Script to recreate PostgreSQL user when password is completely broken
# Run this on the production server as a last resort

echo "=== EzShip PostgreSQL User Recreation ==="
echo

# Set the password
NEW_PASSWORD="${DB_PASSWORD:-defaultpassword123}"

echo "1. Connecting to PostgreSQL as superuser..."
# First, try to connect as postgres superuser
docker exec -it ezship-postgres-prod psql -U postgres -d postgres << EOF
-- Drop existing user (this will fail if user owns objects)
DROP USER IF EXISTS ezship_user;

-- Create user with new password
CREATE USER ezship_user WITH PASSWORD '${NEW_PASSWORD}';

-- Grant all privileges on database
GRANT ALL PRIVILEGES ON DATABASE ezship_production TO ezship_user;

-- Make user owner of database
ALTER DATABASE ezship_production OWNER TO ezship_user;

-- Grant schema permissions
\c ezship_production
GRANT ALL ON SCHEMA public TO ezship_user;
GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO ezship_user;
GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO ezship_user;
EOF

echo
echo "2. Alternative approach - just reset password without dropping user..."
docker exec ezship-postgres-prod psql -U postgres -d postgres -c "ALTER USER ezship_user WITH PASSWORD '${NEW_PASSWORD}';"

echo
echo "3. Updating environment files..."

# Update .env
if [ -f .env ]; then
    cp .env .env.backup.$(date +%Y%m%d_%H%M%S)
    sed -i "s/^DB_PASSWORD=.*/DB_PASSWORD=${NEW_PASSWORD}/" .env
    grep -q "^DB_PASSWORD=" .env || echo "DB_PASSWORD=${NEW_PASSWORD}" >> .env
    echo "✅ .env updated"
fi

# Update .env.prod
if [ -f .env.prod ]; then
    sed -i "s/^DB_PASSWORD=.*/DB_PASSWORD=${NEW_PASSWORD}/" .env.prod
    grep -q "^DB_PASSWORD=" .env.prod || echo "DB_PASSWORD=${NEW_PASSWORD}" >> .env.prod
    echo "✅ .env.prod updated"
fi

echo
echo "4. Exporting password for Docker Compose..."
export DB_PASSWORD="${NEW_PASSWORD}"

echo
echo "5. Restarting containers with new password..."
docker-compose -f docker-compose.prod.yml down
DB_PASSWORD="${NEW_PASSWORD}" docker-compose -f docker-compose.prod.yml up -d

echo
echo "6. Waiting for services..."
sleep 15

echo
echo "7. Running migrations and seeding..."
docker exec ezship-app-prod php artisan migrate:fresh --force --seed

echo
echo "=== User Recreation Complete ==="
echo "New password has been set to: ${NEW_PASSWORD}"
echo
echo "Admin login:"
echo "  URL: https://api.ezship.app/admin/login"
echo "  Email: admin@ezship.com"
echo "  Password: password"