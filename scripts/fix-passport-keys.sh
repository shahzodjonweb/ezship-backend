#!/bin/bash

echo "=== Fixing Laravel Passport Keys ==="

# Navigate to the Laravel directory
cd /var/www/html || cd /var/www/ezship-backend || exit

# Generate Passport keys if they don't exist
if [ ! -f "storage/oauth-private.key" ] || [ ! -f "storage/oauth-public.key" ]; then
    echo "Generating Passport encryption keys..."
    php artisan passport:keys --force
else
    echo "Passport keys already exist, regenerating for safety..."
    php artisan passport:keys --force
fi

# Set correct permissions for the keys
echo "Setting correct permissions for Passport keys..."
chmod 600 storage/oauth-private.key
chmod 644 storage/oauth-public.key
chown www-data:www-data storage/oauth-*.key

# Clear caches to ensure new keys are loaded
echo "Clearing caches..."
php artisan config:clear
php artisan cache:clear

echo "=== Passport Keys Fixed ==="
echo "Keys location:"
ls -la storage/oauth-*.key 2>/dev/null || echo "Keys not found in storage/"

# Check if keys exist and are readable
if [ -r "storage/oauth-private.key" ] && [ -r "storage/oauth-public.key" ]; then
    echo "✓ Keys are properly configured"
else
    echo "✗ Warning: Keys may not be properly configured"
fi