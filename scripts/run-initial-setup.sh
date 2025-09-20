#!/bin/bash

echo "=== Running Initial Setup Command ==="

# Function to run commands in the app container
run_command() {
    echo "Running: $1"
    
    # Try different container names
    if docker exec ezship-app-prod bash -c "$1" 2>/dev/null; then
        return 0
    elif docker exec ezship-backend-app-1 bash -c "$1" 2>/dev/null; then
        return 0
    else
        CONTAINER=$(docker ps --filter "name=app" --format "{{.Names}}" | grep -E "(ezship|backend)" | head -1)
        if [ -n "$CONTAINER" ]; then
            docker exec "$CONTAINER" bash -c "$1"
        else
            echo "Error: Could not find app container"
            return 1
        fi
    fi
}

echo "Step 1: Checking current QuickBooks credentials..."
run_command "cd /var/www/html && php artisan tinker --execute=\"
    use App\\Models\\Credential;
    \\\$cred = Credential::where('name', 'quickbooks')->first();
    if (\\\$cred) {
        echo 'QuickBooks credentials exist:';
        echo PHP_EOL;
        echo 'ID: ' . \\\$cred->id;
        echo PHP_EOL;
        echo 'Name: ' . \\\$cred->name;
        echo PHP_EOL;
        echo 'Has refresh token: ' . (!empty(\\\$cred->refresh_token) ? 'Yes' : 'No');
        echo PHP_EOL;
        echo 'Has access token: ' . (!empty(\\\$cred->access_token) ? 'Yes' : 'No');
    } else {
        echo 'No QuickBooks credentials found';
    }
\""

echo ""
echo "Step 2: Running initial:setup command..."
run_command "cd /var/www/html && php artisan initial:setup --force"

echo ""
echo "Step 3: Verifying setup..."
run_command "cd /var/www/html && php artisan tinker --execute=\"
    use App\\Models\\Credential;
    \\\$count = Credential::where('name', 'quickbooks')->count();
    echo 'QuickBooks credentials count: ' . \\\$count;
\""

echo ""
echo "=== Initial Setup Complete ==="
echo ""
echo "If credentials were not created, check:"
echo "1. Database connection is working"
echo "2. Credentials table exists (run migrations)"
echo "3. Check Laravel logs for errors"