#!/bin/bash

# Debug and fix 500 errors after login
# Run: cd /opt/ezship && bash scripts/debug-500-error.sh

echo "=== Debugging 500 Error on Login ==="
echo

# 1. Check Laravel logs
echo "1. Checking Laravel error logs..."
docker exec ezship-app-prod tail -50 /var/www/html/storage/logs/laravel.log 2>/dev/null || echo "No Laravel log found"

echo
echo "2. Checking PHP error logs..."
docker exec ezship-app-prod tail -20 /var/log/php/error.log 2>/dev/null || echo "No PHP error log"

echo
echo "3. Ensuring storage permissions are correct..."
docker exec ezship-app-prod chown -R www-data:www-data /var/www/html/storage
docker exec ezship-app-prod chmod -R 775 /var/www/html/storage
docker exec ezship-app-prod chown -R www-data:www-data /var/www/html/bootstrap/cache
docker exec ezship-app-prod chmod -R 775 /var/www/html/bootstrap/cache

echo
echo "4. Creating required storage directories..."
docker exec ezship-app-prod mkdir -p /var/www/html/storage/framework/sessions
docker exec ezship-app-prod mkdir -p /var/www/html/storage/framework/views
docker exec ezship-app-prod mkdir -p /var/www/html/storage/framework/cache
docker exec ezship-app-prod mkdir -p /var/www/html/storage/logs
docker exec ezship-app-prod chown -R www-data:www-data /var/www/html/storage

echo
echo "5. Checking if APP_KEY is set..."
docker exec ezship-app-prod grep "APP_KEY=" /var/www/html/.env

echo
echo "6. Generating APP_KEY if missing..."
docker exec ezship-app-prod php artisan key:generate --force

echo
echo "7. Clearing all caches..."
docker exec ezship-app-prod php artisan config:clear
docker exec ezship-app-prod php artisan cache:clear
docker exec ezship-app-prod php artisan route:clear
docker exec ezship-app-prod php artisan view:clear

echo
echo "8. Optimizing application..."
docker exec ezship-app-prod php artisan config:cache
docker exec ezship-app-prod php artisan route:cache
docker exec ezship-app-prod php artisan view:cache

echo
echo "9. Checking session configuration..."
docker exec ezship-app-prod grep -E "SESSION_DRIVER|SESSION_LIFETIME" /var/www/html/.env

echo
echo "10. Setting session to file driver..."
docker exec ezship-app-prod sed -i 's/^SESSION_DRIVER=.*/SESSION_DRIVER=file/' /var/www/html/.env
docker exec ezship-app-prod sh -c "grep -q '^SESSION_DRIVER=' /var/www/html/.env || echo 'SESSION_DRIVER=file' >> /var/www/html/.env"

echo
echo "11. Checking database tables..."
docker exec ezship-app-prod php artisan migrate:status

echo
echo "12. Running missing migrations..."
docker exec ezship-app-prod php artisan migrate --force

echo
echo "13. Restarting PHP-FPM..."
docker exec ezship-app-prod supervisorctl restart php-fpm

echo
echo "14. Testing admin login route..."
curl -I https://api.ezship.app/admin/login

echo
echo "=== Debug Complete ==="
echo
echo "Check if the error is fixed at: https://api.ezship.app/admin/login"
echo
echo "If still getting 500 error, check the latest logs:"
echo "docker exec ezship-app-prod tail -100 /var/www/html/storage/logs/laravel.log"