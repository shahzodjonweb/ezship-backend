#!/bin/bash

# Simple Docker Setup Script for Fresh Ubuntu/Debian Server
# This script installs Docker and Docker Compose only

set -e

echo "========================================="
echo "EzShip Docker Server Setup"
echo "========================================="

# Check if running as root or with sudo
if [[ $EUID -ne 0 ]]; then
   echo "This script must be run as root or with sudo" 
   exit 1
fi

# Update system
echo "Updating system packages..."
apt-get update
apt-get upgrade -y

# Install required packages for Docker
echo "Installing prerequisites..."
apt-get install -y \
    ca-certificates \
    curl \
    gnupg \
    lsb-release \
    git

# Add Docker's official GPG key
echo "Adding Docker GPG key..."
mkdir -p /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | gpg --dearmor -o /etc/apt/keyrings/docker.gpg

# Set up Docker repository
echo "Setting up Docker repository..."
echo \
  "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu \
  $(lsb_release -cs) stable" | tee /etc/apt/sources.list.d/docker.list > /dev/null

# Install Docker Engine
echo "Installing Docker..."
apt-get update
apt-get install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin

# Start and enable Docker
systemctl start docker
systemctl enable docker

# Install Docker Compose standalone (optional, for older compose commands)
echo "Installing Docker Compose..."
curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
chmod +x /usr/local/bin/docker-compose

# Create deployment user
echo "Creating deployment user..."
useradd -m -s /bin/bash deploy || echo "User deploy already exists"
usermod -aG docker deploy

# Create application directories
echo "Creating application directories..."
mkdir -p /var/www/app.ezship.app
mkdir -p /var/www/dev.ezship.app
chown -R deploy:deploy /var/www

# Setup SSH for deployment user
mkdir -p /home/deploy/.ssh
touch /home/deploy/.ssh/authorized_keys
chmod 700 /home/deploy/.ssh
chmod 600 /home/deploy/.ssh/authorized_keys
chown -R deploy:deploy /home/deploy/.ssh

# Allow deploy user to run docker commands without sudo
echo "Setting up permissions..."
cat > /etc/sudoers.d/deploy << 'EOF'
deploy ALL=(ALL) NOPASSWD: /usr/bin/docker, /usr/bin/docker-compose, /usr/local/bin/docker-compose
EOF

# Create docker-compose files for both environments
echo "Creating docker-compose files..."

# Production docker-compose
cat > /var/www/app.ezship.app/docker-compose.yml << 'EOF'
version: '3.8'

services:
  app:
    image: ghcr.io/${GITHUB_REPOSITORY}:latest
    container_name: ezship-app-prod
    restart: unless-stopped
    env_file:
      - .env
    networks:
      - ezship-prod
    depends_on:
      - db
      - redis

  nginx:
    image: nginx:alpine
    container_name: ezship-nginx-prod
    restart: unless-stopped
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./nginx.conf:/etc/nginx/conf.d/default.conf
      - ./ssl:/etc/nginx/ssl
    networks:
      - ezship-prod
    depends_on:
      - app

  db:
    image: postgres:15-alpine
    container_name: ezship-db-prod
    restart: unless-stopped
    environment:
      POSTGRES_DB: ezship_production
      POSTGRES_USER: ezship
      POSTGRES_PASSWORD: ${DB_PASSWORD}
    volumes:
      - ezship-prod-db:/var/lib/postgresql/data
    networks:
      - ezship-prod

  redis:
    image: redis:alpine
    container_name: ezship-redis-prod
    restart: unless-stopped
    networks:
      - ezship-prod

networks:
  ezship-prod:
    driver: bridge

volumes:
  ezship-prod-db:
    driver: local
EOF

# Development docker-compose
cat > /var/www/dev.ezship.app/docker-compose.yml << 'EOF'
version: '3.8'

services:
  app:
    image: ghcr.io/${GITHUB_REPOSITORY}:dev
    container_name: ezship-app-dev
    restart: unless-stopped
    env_file:
      - .env
    networks:
      - ezship-dev
    depends_on:
      - db
      - redis

  nginx:
    image: nginx:alpine
    container_name: ezship-nginx-dev
    restart: unless-stopped
    ports:
      - "8080:80"
    volumes:
      - ./nginx.conf:/etc/nginx/conf.d/default.conf
    networks:
      - ezship-dev
    depends_on:
      - app

  db:
    image: postgres:15-alpine
    container_name: ezship-db-dev
    restart: unless-stopped
    environment:
      POSTGRES_DB: ezship_development
      POSTGRES_USER: ezship
      POSTGRES_PASSWORD: ${DB_PASSWORD}
    volumes:
      - ezship-dev-db:/var/lib/postgresql/data
    networks:
      - ezship-dev

  redis:
    image: redis:alpine
    container_name: ezship-redis-dev
    restart: unless-stopped
    networks:
      - ezship-dev

networks:
  ezship-dev:
    driver: bridge

volumes:
  ezship-dev-db:
    driver: local
EOF

# Create basic nginx config
cat > /var/www/app.ezship.app/nginx.conf << 'EOF'
server {
    listen 80;
    server_name app.ezship.app;
    root /var/www/html/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass app:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
EOF

cp /var/www/app.ezship.app/nginx.conf /var/www/dev.ezship.app/nginx.conf

# Set ownership
chown -R deploy:deploy /var/www

echo "========================================="
echo "Docker setup completed!"
echo "========================================="
echo ""
echo "Next steps:"
echo "1. Add your SSH public key to: /home/deploy/.ssh/authorized_keys"
echo ""
echo "2. Add these GitHub Secrets:"
echo "   - SERVER_HOST: $(curl -s ifconfig.me || echo 'your-server-ip')"
echo "   - SERVER_USER: deploy"
echo "   - SERVER_SSH_KEY: (your private SSH key)"
echo ""
echo "3. The deployment will use Docker images from GitHub Container Registry"
echo ""
echo "4. SSL certificates can be added later with:"
echo "   - Certbot or Let's Encrypt"
echo "   - Or use Cloudflare/Nginx Proxy Manager"
echo ""
echo "========================================="