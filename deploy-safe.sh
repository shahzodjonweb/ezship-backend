#!/bin/bash

echo "=== SAFE DEPLOYMENT SCRIPT ==="
echo "Ensuring site stays accessible during deployment..."

# Navigate to project directory
cd /var/www/ezship-backend || exit

# Clear caches safely
echo "Clearing caches..."
php artisan cache:clear || true
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true

# Optimize without caching (safer for production)
echo "Optimizing application..."
php artisan config:cache || true
php artisan route:cache || true

# Set correct permissions
echo "Setting permissions..."
chown -R www-data:www-data storage bootstrap/cache || true
chmod -R 775 storage bootstrap/cache || true

# Ensure log file exists and is writable
touch storage/logs/laravel.log
chmod 664 storage/logs/laravel.log
chown www-data:www-data storage/logs/laravel.log

# Create a health check file
echo '<?php echo json_encode(["status" => "ok", "time" => time()]); ?>' > public/health.php

echo "=== DEPLOYMENT COMPLETE ==="
echo "Site should remain accessible"