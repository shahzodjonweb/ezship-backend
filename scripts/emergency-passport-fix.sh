#!/bin/bash

echo "=== EMERGENCY PASSPORT FIX ==="
echo "This script will fix the Passport key issue immediately"

# Function to run commands in the app container
run_in_container() {
    # Try different container names
    if docker exec ezship-app-prod "$@" 2>/dev/null; then
        return 0
    elif docker exec ezship-backend-app-1 "$@" 2>/dev/null; then
        return 0
    elif docker exec $(docker ps --filter "name=app" --format "{{.Names}}" | head -1) "$@" 2>/dev/null; then
        return 0
    else
        echo "Warning: Could not find app container, trying locally..."
        cd /var/www/ezship-backend 2>/dev/null || cd /var/www/html 2>/dev/null
        "$@"
    fi
}

echo "1. Generating Passport keys..."
run_in_container php artisan passport:keys --force

echo "2. Setting correct permissions..."
run_in_container chmod 600 /var/www/html/storage/oauth-private.key
run_in_container chmod 644 /var/www/html/storage/oauth-public.key
run_in_container chown www-data:www-data /var/www/html/storage/oauth-private.key
run_in_container chown www-data:www-data /var/www/html/storage/oauth-public.key

echo "3. Clearing caches..."
run_in_container php artisan config:clear
run_in_container php artisan cache:clear

echo "4. Verifying keys exist..."
run_in_container ls -la /var/www/html/storage/oauth*.key

echo "5. Testing key readability..."
run_in_container test -r /var/www/html/storage/oauth-private.key && echo "✓ Private key readable" || echo "✗ Private key not readable"
run_in_container test -r /var/www/html/storage/oauth-public.key && echo "✓ Public key readable" || echo "✗ Public key not readable"

echo "=== FIX COMPLETE ==="
echo ""
echo "If keys are still not working, run these commands manually:"
echo "1. docker exec -it ezship-app-prod bash"
echo "2. php artisan passport:install --force"
echo "3. php artisan config:clear"
echo "4. php artisan cache:clear"