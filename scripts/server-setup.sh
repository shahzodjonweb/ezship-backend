#!/bin/bash

# EzShip Server Setup Script
# This script sets up both app.ezship.app and dev.ezship.app on the same server

set -e

echo "========================================="
echo "EzShip Server Setup"
echo "========================================="

# Check if running as root or with sudo
if [[ $EUID -ne 0 ]]; then
   echo "This script must be run as root or with sudo" 
   exit 1
fi

# Variables
WEB_ROOT="/var/www"
PRODUCTION_DIR="$WEB_ROOT/app.ezship.app"
DEV_DIR="$WEB_ROOT/dev.ezship.app"
REPO_URL="https://github.com/YOUR_USERNAME/ezship-backend.git"  # Update this
NGINX_USER="www-data"

# Update system
echo "Updating system packages..."
apt-get update
apt-get upgrade -y

# Install required packages
echo "Installing required packages..."
apt-get install -y \
    nginx \
    postgresql \
    postgresql-contrib \
    redis-server \
    git \
    curl \
    zip \
    unzip \
    supervisor \
    certbot \
    python3-certbot-nginx

# Install PHP 8.2 and extensions
echo "Installing PHP 8.2..."
apt-get install -y software-properties-common
add-apt-repository ppa:ondrej/php -y
apt-get update
apt-get install -y \
    php8.2-fpm \
    php8.2-cli \
    php8.2-common \
    php8.2-mysql \
    php8.2-pgsql \
    php8.2-mbstring \
    php8.2-xml \
    php8.2-zip \
    php8.2-bcmath \
    php8.2-curl \
    php8.2-gd \
    php8.2-redis \
    php8.2-intl

# Install Composer
echo "Installing Composer..."
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer

# Create web directories
echo "Creating web directories..."
mkdir -p $PRODUCTION_DIR
mkdir -p $DEV_DIR

# Clone repository for production
echo "Setting up app.ezship.app..."
cd $PRODUCTION_DIR
git clone -b master $REPO_URL .
cp .env.example .env
# Edit .env file for production
echo "Please edit $PRODUCTION_DIR/.env for production settings"

# Clone repository for development
echo "Setting up dev.ezship.app..."
cd $DEV_DIR
git clone -b dev $REPO_URL .
cp .env.example .env
# Edit .env file for development
echo "Please edit $DEV_DIR/.env for development settings"

# Set permissions
echo "Setting permissions..."
chown -R $NGINX_USER:$NGINX_USER $PRODUCTION_DIR
chown -R $NGINX_USER:$NGINX_USER $DEV_DIR
chmod -R 755 $PRODUCTION_DIR/storage
chmod -R 755 $PRODUCTION_DIR/bootstrap/cache
chmod -R 755 $DEV_DIR/storage
chmod -R 755 $DEV_DIR/bootstrap/cache

# Create Nginx configuration for app.ezship.app
cat > /etc/nginx/sites-available/app.ezship.app << 'EOF'
server {
    listen 80;
    listen [::]:80;
    server_name app.ezship.app;
    root /var/www/app.ezship.app/public;

    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    client_max_body_size 100M;
}
EOF

# Create Nginx configuration for dev.ezship.app
cat > /etc/nginx/sites-available/dev.ezship.app << 'EOF'
server {
    listen 80;
    listen [::]:80;
    server_name dev.ezship.app;
    root /var/www/dev.ezship.app/public;

    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    client_max_body_size 100M;
}
EOF

# Enable sites
ln -sf /etc/nginx/sites-available/app.ezship.app /etc/nginx/sites-enabled/
ln -sf /etc/nginx/sites-available/dev.ezship.app /etc/nginx/sites-enabled/

# Test and reload Nginx
nginx -t
systemctl reload nginx

# Setup supervisor for queue workers
cat > /etc/supervisor/conf.d/ezship-production-worker.conf << 'EOF'
[program:ezship-production-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/app.ezship.app/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/app.ezship.app/storage/logs/worker.log
stopwaitsecs=3600
EOF

cat > /etc/supervisor/conf.d/ezship-dev-worker.conf << 'EOF'
[program:ezship-dev-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/dev.ezship.app/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/dev.ezship.app/storage/logs/worker.log
stopwaitsecs=3600
EOF

# Reload supervisor
supervisorctl reread
supervisorctl update

# Setup cron for Laravel scheduler
(crontab -l 2>/dev/null; echo "* * * * * cd /var/www/app.ezship.app && php artisan schedule:run >> /dev/null 2>&1") | crontab -
(crontab -l 2>/dev/null; echo "* * * * * cd /var/www/dev.ezship.app && php artisan schedule:run >> /dev/null 2>&1") | crontab -

# Create deployment user
echo "Creating deployment user..."
useradd -m -s /bin/bash deploy
usermod -aG www-data deploy

# Setup SSH for deployment user
mkdir -p /home/deploy/.ssh
touch /home/deploy/.ssh/authorized_keys
chmod 700 /home/deploy/.ssh
chmod 600 /home/deploy/.ssh/authorized_keys
chown -R deploy:deploy /home/deploy/.ssh

# Grant sudo permissions for deployment commands
cat > /etc/sudoers.d/deploy << 'EOF'
deploy ALL=(www-data) NOPASSWD: /usr/bin/php
deploy ALL=(root) NOPASSWD: /usr/bin/chown -R www-data\:www-data /var/www/app.ezship.app/storage
deploy ALL=(root) NOPASSWD: /usr/bin/chown -R www-data\:www-data /var/www/app.ezship.app/bootstrap/cache
deploy ALL=(root) NOPASSWD: /usr/bin/chown -R www-data\:www-data /var/www/dev.ezship.app/storage
deploy ALL=(root) NOPASSWD: /usr/bin/chown -R www-data\:www-data /var/www/dev.ezship.app/bootstrap/cache
deploy ALL=(root) NOPASSWD: /usr/bin/chmod -R 755 /var/www/app.ezship.app/storage
deploy ALL=(root) NOPASSWD: /usr/bin/chmod -R 755 /var/www/app.ezship.app/bootstrap/cache
deploy ALL=(root) NOPASSWD: /usr/bin/chmod -R 755 /var/www/dev.ezship.app/storage
deploy ALL=(root) NOPASSWD: /usr/bin/chmod -R 755 /var/www/dev.ezship.app/bootstrap/cache
deploy ALL=(root) NOPASSWD: /usr/bin/supervisorctl restart ezship-production-worker\:*
deploy ALL=(root) NOPASSWD: /usr/bin/supervisorctl restart ezship-dev-worker\:*
EOF

echo "========================================="
echo "Setup completed!"
echo "========================================="
echo ""
echo "Next steps:"
echo "1. Edit environment files:"
echo "   - $PRODUCTION_DIR/.env"
echo "   - $DEV_DIR/.env"
echo ""
echo "2. Add your SSH public key to /home/deploy/.ssh/authorized_keys"
echo ""
echo "3. Run initial setup for each environment:"
echo "   cd $PRODUCTION_DIR && composer install && php artisan key:generate && php artisan migrate"
echo "   cd $DEV_DIR && composer install && php artisan key:generate && php artisan migrate"
echo ""
echo "4. Setup SSL certificates:"
echo "   certbot --nginx -d app.ezship.app"
echo "   certbot --nginx -d dev.ezship.app"
echo ""
echo "5. Configure PostgreSQL database and create users"
echo ""
echo "========================================="