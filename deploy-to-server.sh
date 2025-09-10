#!/bin/bash

# EzShip Server Deployment Script
# Run this on your server after initial setup

set -e

echo "======================================"
echo "EzShip Server Deployment Script"
echo "======================================"

# Variables
DOMAIN="ezship.app"
SERVER_USER="www-data"
APP_DIR="/var/www/ezship"
REPO_URL="https://github.com/yourusername/ezship-backend.git"  # Update this
DB_NAME="ezship_production"
DB_USER="ezship_user"
DB_PASS="CHANGE_THIS_STRONG_PASSWORD"  # Update this

# Step 1: Clone or update repository
echo "Step 1: Setting up application directory..."
if [ ! -d "$APP_DIR" ]; then
    sudo mkdir -p $APP_DIR
    sudo chown $USER:$SERVER_USER $APP_DIR
    git clone $REPO_URL $APP_DIR
else
    cd $APP_DIR
    git pull origin master
fi

cd $APP_DIR

# Step 2: Install dependencies
echo "Step 2: Installing dependencies..."
composer install --no-dev --optimize-autoloader
npm install
npm run build

# Step 3: Set up environment file
echo "Step 3: Setting up environment..."
if [ ! -f .env ]; then
    cp .env.production .env
    php artisan key:generate
fi

# Step 4: Set up database
echo "Step 4: Setting up PostgreSQL database..."
sudo -u postgres psql <<EOF
CREATE DATABASE $DB_NAME;
CREATE USER $DB_USER WITH ENCRYPTED PASSWORD '$DB_PASS';
GRANT ALL PRIVILEGES ON DATABASE $DB_NAME TO $DB_USER;
\q
EOF

# Step 5: Run migrations
echo "Step 5: Running migrations..."
php artisan migrate --force

# Step 6: Set permissions
echo "Step 6: Setting permissions..."
sudo chown -R $USER:$SERVER_USER $APP_DIR
sudo chmod -R 755 $APP_DIR
sudo chmod -R 775 $APP_DIR/storage
sudo chmod -R 775 $APP_DIR/bootstrap/cache

# Step 7: Set up Nginx
echo "Step 7: Configuring Nginx..."
sudo cp nginx-ezship.conf /etc/nginx/sites-available/ezship
sudo ln -sf /etc/nginx/sites-available/ezship /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx

# Step 8: Set up SSL with Let's Encrypt
echo "Step 8: Setting up SSL certificates..."
sudo certbot --nginx -d $DOMAIN -d www.$DOMAIN -d api.$DOMAIN --non-interactive --agree-tos --email admin@$DOMAIN

# Step 9: Set up Laravel scheduler cron job
echo "Step 9: Setting up Laravel scheduler..."
(crontab -l 2>/dev/null; echo "* * * * * cd $APP_DIR && php artisan schedule:run >> /dev/null 2>&1") | crontab -

# Step 10: Set up queue worker (supervisor)
echo "Step 10: Setting up queue worker..."
sudo apt install -y supervisor

cat <<EOT | sudo tee /etc/supervisor/conf.d/ezship-worker.conf
[program:ezship-worker]
process_name=%(program_name)s_%(process_num)02d
command=php $APP_DIR/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=$USER
numprocs=4
redirect_stderr=true
stdout_logfile=$APP_DIR/storage/logs/worker.log
stopwaitsecs=3600
EOT

sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start ezship-worker:*

# Step 11: Optimize Laravel
echo "Step 11: Optimizing Laravel..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# Step 12: Set up log rotation
echo "Step 12: Setting up log rotation..."
cat <<EOT | sudo tee /etc/logrotate.d/ezship
$APP_DIR/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 $USER $SERVER_USER
    sharedscripts
    postrotate
        systemctl reload php8.2-fpm
    endscript
}
EOT

echo "======================================"
echo "Deployment Complete!"
echo "======================================"
echo ""
echo "Next steps:"
echo "1. Update the repository URL in this script"
echo "2. Update database password in .env file"
echo "3. Configure mail settings in .env file"
echo "4. Test your site at https://$DOMAIN"
echo ""
echo "Useful commands:"
echo "- View logs: tail -f $APP_DIR/storage/logs/laravel.log"
echo "- Clear cache: php artisan cache:clear"
echo "- Queue status: sudo supervisorctl status"
echo "- SSL renewal: sudo certbot renew --dry-run"