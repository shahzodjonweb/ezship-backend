#!/bin/bash

# Fix Laravel 500 errors comprehensively
# Run: cd /opt/ezship && bash scripts/fix-laravel-500.sh

echo "=== Fixing Laravel 500 Errors ==="
echo

# 1. Ensure .env has all required values
echo "1. Checking .env configuration..."
docker exec ezship-app-prod sh -c "
    cd /var/www/html
    
    # Ensure APP_KEY exists
    if ! grep -q '^APP_KEY=base64:' .env; then
        echo 'Generating APP_KEY...'
        php artisan key:generate --force
    fi
    
    # Ensure SESSION_DRIVER is set to file
    sed -i 's/^SESSION_DRIVER=.*/SESSION_DRIVER=file/' .env
    grep -q '^SESSION_DRIVER=' .env || echo 'SESSION_DRIVER=file' >> .env
    
    # Ensure CACHE_DRIVER is set
    grep -q '^CACHE_DRIVER=' .env || echo 'CACHE_DRIVER=file' >> .env
    
    # Ensure APP_DEBUG is false for production
    sed -i 's/^APP_DEBUG=.*/APP_DEBUG=false/' .env
    
    echo '.env configuration updated'
"

# 2. Fix storage permissions
echo "2. Fixing storage permissions..."
docker exec ezship-app-prod sh -c "
    cd /var/www/html
    
    # Create all required directories
    mkdir -p storage/framework/sessions
    mkdir -p storage/framework/views
    mkdir -p storage/framework/cache/data
    mkdir -p storage/app/public
    mkdir -p storage/logs
    mkdir -p bootstrap/cache
    
    # Set proper permissions
    chown -R www-data:www-data storage bootstrap/cache
    chmod -R 775 storage bootstrap/cache
    
    # Ensure log file exists and is writable
    touch storage/logs/laravel.log
    chown www-data:www-data storage/logs/laravel.log
    chmod 664 storage/logs/laravel.log
    
    echo 'Storage permissions fixed'
"

# 3. Clear all caches
echo "3. Clearing all caches..."
docker exec ezship-app-prod sh -c "
    cd /var/www/html
    php artisan config:clear
    php artisan cache:clear
    php artisan route:clear
    php artisan view:clear
    rm -rf bootstrap/cache/*.php
    echo 'Caches cleared'
"

# 4. Reinstall composer dependencies
echo "4. Ensuring all dependencies are installed..."
docker exec ezship-app-prod sh -c "
    cd /var/www/html
    composer install --no-dev --optimize-autoloader
    echo 'Dependencies verified'
"

# 5. Run migrations
echo "5. Ensuring database is up to date..."
docker exec ezship-app-prod php artisan migrate --force

# 6. Optimize application
echo "6. Optimizing application..."
docker exec ezship-app-prod sh -c "
    cd /var/www/html
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan optimize
    echo 'Application optimized'
"

# 7. Create storage link
echo "7. Creating storage link..."
docker exec ezship-app-prod php artisan storage:link 2>/dev/null || true

# 8. Restart PHP-FPM and nginx
echo "8. Restarting services..."
docker exec ezship-app-prod sh -c "
    supervisorctl restart php-fpm 2>/dev/null || true
    supervisorctl restart nginx 2>/dev/null || true
    echo 'Services restarted'
"

# 9. Final permission check
echo "9. Final permission check..."
docker exec ezship-app-prod sh -c "
    cd /var/www/html
    chown -R www-data:www-data storage bootstrap/cache
    chmod -R 775 storage bootstrap/cache
"

echo
echo "=== Fix Complete ==="
echo
echo "Testing endpoints:"
echo "  https://api.ezship.app/error-check.php (detailed diagnostics)"
echo "  https://api.ezship.app/check.php (database check)"
echo "  https://api.ezship.app/admin/login (login page)"
echo
echo "If still getting errors, check logs:"
echo "  docker exec ezship-app-prod tail -100 storage/logs/laravel.log"