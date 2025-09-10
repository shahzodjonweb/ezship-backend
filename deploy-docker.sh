#!/bin/bash

# EzShip Docker Deployment Script for Server
# Run this on your server to deploy with Docker

set -e

echo "======================================"
echo "EzShip Docker Deployment"
echo "======================================"

# Variables (update these)
DOMAIN="ezship.app"
EMAIL="admin@ezship.app"
GITHUB_REPO="https://github.com/YOUR_USERNAME/ezship-backend.git"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}[✓]${NC} $1"
}

print_error() {
    echo -e "${RED}[✗]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[!]${NC} $1"
}

# Check if running as root
if [[ $EUID -ne 0 ]]; then
   print_error "This script must be run as root"
   exit 1
fi

# Step 1: Install Docker
print_status "Installing Docker..."
if ! command -v docker &> /dev/null; then
    curl -fsSL https://get.docker.com -o get-docker.sh
    sh get-docker.sh
    rm get-docker.sh
    systemctl enable docker
    systemctl start docker
else
    print_warning "Docker already installed"
fi

# Step 2: Install Docker Compose
print_status "Installing Docker Compose..."
if ! command -v docker-compose &> /dev/null; then
    curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
    chmod +x /usr/local/bin/docker-compose
else
    print_warning "Docker Compose already installed"
fi

# Step 3: Install Nginx for reverse proxy
print_status "Installing Nginx..."
apt-get update
apt-get install -y nginx certbot python3-certbot-nginx

# Step 4: Clone repository
print_status "Setting up application directory..."
APP_DIR="/opt/ezship"
if [ ! -d "$APP_DIR" ]; then
    git clone $GITHUB_REPO $APP_DIR
else
    cd $APP_DIR
    git pull origin master
fi

cd $APP_DIR

# Step 5: Create .env file
print_status "Setting up environment..."
if [ ! -f .env ]; then
    cp .env.production .env
    print_warning "Please edit .env file with your production values"
fi

# Step 6: Set up Nginx reverse proxy
print_status "Configuring Nginx..."
cat > /etc/nginx/sites-available/ezship <<EOF
server {
    listen 80;
    server_name $DOMAIN www.$DOMAIN api.$DOMAIN;

    location / {
        proxy_pass http://127.0.0.1:8080;
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
        proxy_buffer_size 128k;
        proxy_buffers 4 256k;
        proxy_busy_buffers_size 256k;
        client_max_body_size 100M;
    }
}
EOF

ln -sf /etc/nginx/sites-available/ezship /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default
nginx -t
systemctl reload nginx

# Step 7: Build and start containers
print_status "Building Docker containers..."
docker-compose build

print_status "Starting containers..."
docker-compose up -d

# Step 8: Wait for containers to be ready
print_status "Waiting for containers to be ready..."
sleep 10

# Step 9: Run initial setup commands
print_status "Running initial setup..."
docker-compose exec -T app php artisan key:generate
docker-compose exec -T app php artisan migrate --force
docker-compose exec -T app php artisan storage:link

# Step 10: Set up SSL with Let's Encrypt
print_status "Setting up SSL certificates..."
certbot --nginx -d $DOMAIN -d www.$DOMAIN -d api.$DOMAIN --non-interactive --agree-tos --email $EMAIL

# Step 11: Set up auto-renewal for SSL
print_status "Setting up SSL auto-renewal..."
(crontab -l 2>/dev/null; echo "0 0 * * * certbot renew --quiet") | crontab -

# Step 12: Create update script
print_status "Creating update script..."
cat > /opt/ezship/update.sh <<'SCRIPT'
#!/bin/bash
cd /opt/ezship
git pull origin master
docker-compose build
docker-compose down
docker-compose up -d
docker-compose exec -T app php artisan migrate --force
docker-compose exec -T app php artisan config:cache
docker-compose exec -T app php artisan route:cache
docker-compose exec -T app php artisan view:cache
docker system prune -f
SCRIPT
chmod +x /opt/ezship/update.sh

# Step 13: Create backup script
print_status "Creating backup script..."
cat > /opt/ezship/backup.sh <<'SCRIPT'
#!/bin/bash
BACKUP_DIR="/backups/ezship"
DATE=$(date +%Y%m%d_%H%M%S)
mkdir -p $BACKUP_DIR

# Backup database
docker-compose exec -T postgres pg_dump -U ezship_user ezship_production > $BACKUP_DIR/db_$DATE.sql

# Backup files
tar -czf $BACKUP_DIR/files_$DATE.tar.gz storage/app public/uploads

# Keep only last 7 days of backups
find $BACKUP_DIR -type f -mtime +7 -delete

echo "Backup completed: $BACKUP_DIR"
SCRIPT
chmod +x /opt/ezship/backup.sh

# Step 14: Set up daily backup cron
print_status "Setting up daily backups..."
(crontab -l 2>/dev/null; echo "0 2 * * * /opt/ezship/backup.sh") | crontab -

# Final status
echo ""
echo "======================================"
echo -e "${GREEN}Deployment Complete!${NC}"
echo "======================================"
echo ""
print_status "Your application is running at:"
echo "  - Main: https://$DOMAIN"
echo "  - API: https://api.$DOMAIN"
echo ""
print_status "Useful commands:"
echo "  - View logs: docker-compose logs -f"
echo "  - Enter app container: docker-compose exec app bash"
echo "  - Restart services: docker-compose restart"
echo "  - Update application: /opt/ezship/update.sh"
echo "  - Create backup: /opt/ezship/backup.sh"
echo ""
print_warning "Next steps:"
echo "  1. Edit /opt/ezship/.env with your production values"
echo "  2. Update database password in .env"
echo "  3. Configure mail settings in .env"
echo "  4. Restart containers: cd /opt/ezship && docker-compose restart"