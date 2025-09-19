#!/bin/bash

echo "=== Running Passport Installation Commands ==="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
NC='\033[0m' # No Color

# Function to run commands in the app container
run_command() {
    echo -e "${GREEN}Running: $1${NC}"
    
    # Try different possible container names
    if docker exec ezship-app-prod bash -c "$1" 2>/dev/null; then
        return 0
    elif docker exec ezship-backend-app-1 bash -c "$1" 2>/dev/null; then
        return 0
    elif docker exec ezship-backend_app_1 bash -c "$1" 2>/dev/null; then
        return 0
    else
        # Try to find the container dynamically
        CONTAINER=$(docker ps --filter "name=app" --format "{{.Names}}" | grep -E "(ezship|backend)" | head -1)
        if [ -n "$CONTAINER" ]; then
            docker exec "$CONTAINER" bash -c "$1"
        else
            echo -e "${RED}Error: Could not find app container${NC}"
            echo "Available containers:"
            docker ps --format "table {{.Names}}\t{{.Image}}\t{{.Status}}"
            return 1
        fi
    fi
}

echo "Step 1: Installing Laravel Passport..."
run_command "cd /var/www/html && php artisan passport:install --force --no-interaction"

echo ""
echo "Step 2: Generating additional keys if needed..."
run_command "cd /var/www/html && php artisan passport:keys --force"

echo ""
echo "Step 3: Creating Password Grant Client..."
run_command "cd /var/www/html && php artisan passport:client --password --no-interaction --name='Password Grant Client' || true"

echo ""
echo "Step 4: Setting correct permissions..."
run_command "chmod 600 /var/www/html/storage/oauth-private.key"
run_command "chmod 644 /var/www/html/storage/oauth-public.key"
run_command "chown www-data:www-data /var/www/html/storage/oauth-*.key"

echo ""
echo "Step 5: Clearing all caches..."
run_command "cd /var/www/html && php artisan config:clear"
run_command "cd /var/www/html && php artisan cache:clear"
run_command "cd /var/www/html && php artisan route:clear"
run_command "cd /var/www/html && php artisan view:clear"

echo ""
echo "Step 6: Optimizing application..."
run_command "cd /var/www/html && php artisan config:cache"
run_command "cd /var/www/html && php artisan route:cache"

echo ""
echo "Step 7: Verifying installation..."
run_command "ls -la /var/www/html/storage/oauth*.key"

echo ""
echo -e "${GREEN}=== Passport Installation Complete ===${NC}"
echo ""
echo "If you still see errors, you may need to:"
echo "1. Restart the PHP-FPM service:"
echo "   docker exec ezship-app-prod supervisorctl restart php-fpm"
echo ""
echo "2. Or restart the entire container:"
echo "   docker-compose -f docker-compose.prod.yml restart app"