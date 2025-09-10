#!/bin/bash

# EzShip Multi-Environment Docker Deployment Script
# Supports both production and development environments

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
GITHUB_REPO="https://github.com/YOUR_USERNAME/ezship-backend.git"
EMAIL="admin@ezship.app"

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

print_info() {
    echo -e "${BLUE}[i]${NC} $1"
}

# Check if running as root
if [[ $EUID -ne 0 ]]; then
   print_error "This script must be run as root"
   exit 1
fi

echo "======================================"
echo "EzShip Multi-Environment Deployment"
echo "======================================"

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
print_status "Installing Nginx and Certbot..."
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

# Step 5: Create environment files
print_status "Creating environment files..."

# Production .env
if [ ! -f .env.prod ]; then
    cp .env.production .env.prod
    print_warning "Created .env.prod - Please edit with production values"
fi

# Development .env
if [ ! -f .env.dev ]; then
    cp .env.production .env.dev
    sed -i 's/APP_ENV=production/APP_ENV=development/g' .env.dev
    sed -i 's/APP_DEBUG=false/APP_DEBUG=true/g' .env.dev
    sed -i 's/APP_URL=https:\/\/ezship.app/APP_URL=https:\/\/dev.ezship.app/g' .env.dev
    sed -i 's/DB_DATABASE=ezship_production/DB_DATABASE=ezship_development/g' .env.dev
    print_warning "Created .env.dev - Please edit with development values"
fi

# Step 6: Set up Nginx configuration
print_status "Configuring Nginx..."
cp nginx-server.conf /etc/nginx/sites-available/ezship
ln -sf /etc/nginx/sites-available/ezship /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default

# Test nginx configuration
nginx -t
systemctl reload nginx

# Step 7: Build containers
print_status "Building Docker containers..."

# Build production
print_info "Building production containers..."
docker-compose -f docker-compose.prod.yml build

# Build development
print_info "Building development containers..."
docker-compose -f docker-compose.dev.yml build

# Step 8: Start containers
print_status "Starting containers..."

# Start production
print_info "Starting production environment..."
cp .env.prod .env
docker-compose -f docker-compose.prod.yml up -d

# Start development
print_info "Starting development environment..."
cp .env.dev .env
docker-compose -f docker-compose.dev.yml up -d

# Wait for containers to be ready
sleep 15

# Step 9: Run initial setup for both environments
print_status "Running initial setup..."

# Production setup
print_info "Setting up production database..."
docker-compose -f docker-compose.prod.yml exec -T app php artisan key:generate --env=production
docker-compose -f docker-compose.prod.yml exec -T app php artisan migrate --force --env=production
docker-compose -f docker-compose.prod.yml exec -T app php artisan storage:link

# Development setup
print_info "Setting up development database..."
docker-compose -f docker-compose.dev.yml exec -T app-dev php artisan key:generate --env=development
docker-compose -f docker-compose.dev.yml exec -T app-dev php artisan migrate --force --env=development
docker-compose -f docker-compose.dev.yml exec -T app-dev php artisan storage:link

# Step 10: Set up SSL certificates
print_status "Setting up SSL certificates..."

# Production domains
certbot --nginx -d ezship.app -d www.ezship.app -d api.ezship.app \
    --non-interactive --agree-tos --email $EMAIL || true

# Development domains
certbot --nginx -d dev.ezship.app -d api-dev.ezship.app \
    --non-interactive --agree-tos --email $EMAIL || true

# Step 11: Set up SSL auto-renewal
print_status "Setting up SSL auto-renewal..."
(crontab -l 2>/dev/null; echo "0 0 * * * certbot renew --quiet") | crontab -

# Step 12: Create management scripts
print_status "Creating management scripts..."

# Production update script
cat > /opt/ezship/update-prod.sh <<'SCRIPT'
#!/bin/bash
cd /opt/ezship
git pull origin master
cp .env.prod .env
docker-compose -f docker-compose.prod.yml build
docker-compose -f docker-compose.prod.yml down
docker-compose -f docker-compose.prod.yml up -d
docker-compose -f docker-compose.prod.yml exec -T app php artisan migrate --force
docker-compose -f docker-compose.prod.yml exec -T app php artisan config:cache
docker-compose -f docker-compose.prod.yml exec -T app php artisan route:cache
docker-compose -f docker-compose.prod.yml exec -T app php artisan view:cache
docker system prune -f
echo "Production updated successfully"
SCRIPT

# Development update script
cat > /opt/ezship/update-dev.sh <<'SCRIPT'
#!/bin/bash
cd /opt/ezship
git pull origin develop || git pull origin master
cp .env.dev .env
docker-compose -f docker-compose.dev.yml build
docker-compose -f docker-compose.dev.yml down
docker-compose -f docker-compose.dev.yml up -d
docker-compose -f docker-compose.dev.yml exec -T app-dev php artisan migrate --force
docker-compose -f docker-compose.dev.yml exec -T app-dev php artisan config:clear
docker system prune -f
echo "Development updated successfully"
SCRIPT

# Backup script
cat > /opt/ezship/backup.sh <<'SCRIPT'
#!/bin/bash
BACKUP_DIR="/backups/ezship"
DATE=$(date +%Y%m%d_%H%M%S)
mkdir -p $BACKUP_DIR

# Backup production database
echo "Backing up production database..."
docker-compose -f docker-compose.prod.yml exec -T postgres pg_dump -U ezship_user ezship_production > $BACKUP_DIR/prod_db_$DATE.sql

# Backup development database
echo "Backing up development database..."
docker-compose -f docker-compose.dev.yml exec -T postgres-dev pg_dump -U ezship_user ezship_development > $BACKUP_DIR/dev_db_$DATE.sql

# Backup files
tar -czf $BACKUP_DIR/files_$DATE.tar.gz storage/app public/uploads

# Keep only last 7 days of backups
find $BACKUP_DIR -type f -mtime +7 -delete

echo "Backup completed: $BACKUP_DIR"
SCRIPT

# Status check script
cat > /opt/ezship/status.sh <<'SCRIPT'
#!/bin/bash
echo "======================================"
echo "EzShip Environment Status"
echo "======================================"
echo ""
echo "Production Containers:"
docker-compose -f docker-compose.prod.yml ps
echo ""
echo "Development Containers:"
docker-compose -f docker-compose.dev.yml ps
echo ""
echo "Port Mappings:"
echo "  Production App: 8080"
echo "  Production DB:  5432"
echo "  Production Redis: 6379"
echo "  Development App: 8081"
echo "  Development DB:  5433"
echo "  Development Redis: 6380"
echo "  Mailhog: 8025"
SCRIPT

# Make scripts executable
chmod +x /opt/ezship/*.sh

# Step 13: Set up daily backup cron
print_status "Setting up daily backups..."
(crontab -l 2>/dev/null; echo "0 2 * * * /opt/ezship/backup.sh") | crontab -

# Final status
echo ""
echo "======================================"
echo -e "${GREEN}Multi-Environment Deployment Complete!${NC}"
echo "======================================"
echo ""
print_status "Production Environment:"
echo "  - Main App: https://ezship.app"
echo "  - API: https://api.ezship.app"
echo "  - Database Port: 5432"
echo "  - Redis Port: 6379"
echo ""
print_status "Development Environment:"
echo "  - Main App: https://dev.ezship.app"
echo "  - API: https://api-dev.ezship.app"
echo "  - Database Port: 5433"
echo "  - Redis Port: 6380"
echo "  - Mailhog: http://YOUR_SERVER_IP:8025"
echo ""
print_status "Management Scripts:"
echo "  - Update Production: /opt/ezship/update-prod.sh"
echo "  - Update Development: /opt/ezship/update-dev.sh"
echo "  - Create Backup: /opt/ezship/backup.sh"
echo "  - Check Status: /opt/ezship/status.sh"
echo ""
print_status "Useful Commands:"
echo "  - Production logs: docker-compose -f docker-compose.prod.yml logs -f"
echo "  - Development logs: docker-compose -f docker-compose.dev.yml logs -f"
echo "  - Enter prod container: docker-compose -f docker-compose.prod.yml exec app bash"
echo "  - Enter dev container: docker-compose -f docker-compose.dev.yml exec app-dev bash"
echo ""
print_warning "Next Steps:"
echo "  1. Edit /opt/ezship/.env.prod with production values"
echo "  2. Edit /opt/ezship/.env.dev with development values"
echo "  3. Update database passwords in both .env files"
echo "  4. Restart containers after configuration"
echo "     - Production: docker-compose -f docker-compose.prod.yml restart"
echo "     - Development: docker-compose -f docker-compose.dev.yml restart"