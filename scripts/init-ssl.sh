#!/bin/bash

# SSL Certificate Initialization Script for EzShip
# This script sets up Let's Encrypt SSL certificates with auto-renewal

set -e

# Configuration
DOMAINS="api.ezship.app"
EMAIL="admin@ezship.app"  # Change this to your email
STAGING=0  # Set to 1 to use staging server for testing

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}=== EzShip SSL Certificate Setup ===${NC}"

# Check if running as root or with sudo
if [[ $EUID -ne 0 ]]; then
   echo -e "${RED}This script must be run as root or with sudo${NC}"
   exit 1
fi

# Create necessary directories
echo -e "${YELLOW}Creating certificate directories...${NC}"
mkdir -p ./certbot/conf
mkdir -p ./certbot/www

# Check if certificates already exist
if [ -d "./certbot/conf/live/$DOMAINS" ]; then
    echo -e "${YELLOW}Certificates already exist for $DOMAINS${NC}"
    read -p "Do you want to renew/recreate them? (y/n) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo -e "${GREEN}Using existing certificates${NC}"
        exit 0
    fi
fi

# Download recommended TLS parameters
echo -e "${YELLOW}Downloading recommended TLS parameters...${NC}"
if [ ! -e "./certbot/conf/options-ssl-nginx.conf" ]; then
    curl -s https://raw.githubusercontent.com/certbot/certbot/master/certbot-nginx/certbot_nginx/_internal/tls_configs/options-ssl-nginx.conf > "./certbot/conf/options-ssl-nginx.conf"
fi

if [ ! -e "./certbot/conf/ssl-dhparams.pem" ]; then
    curl -s https://raw.githubusercontent.com/certbot/certbot/master/certbot/certbot/ssl-dhparams.pem > "./certbot/conf/ssl-dhparams.pem"
fi

# Create dummy certificate for nginx to start
echo -e "${YELLOW}Creating dummy certificate for $DOMAINS...${NC}"
path="/etc/letsencrypt/live/$DOMAINS"
mkdir -p "./certbot/conf/live/$DOMAINS"

docker run --rm \
    -v $(pwd)/certbot/conf:/etc/letsencrypt \
    -v $(pwd)/certbot/www:/var/www/certbot \
    --entrypoint openssl \
    certbot/certbot:latest \
    req -x509 -nodes -newkey rsa:2048 -days 1 \
    -keyout "$path/privkey.pem" \
    -out "$path/fullchain.pem" \
    -subj "/CN=$DOMAINS"

echo -e "${GREEN}Dummy certificate created${NC}"

# Start nginx with dummy certificate
echo -e "${YELLOW}Starting nginx...${NC}"
docker-compose -f docker-compose.ssl.yml up -d nginx

# Wait for nginx to be ready
sleep 5

# Delete dummy certificate
echo -e "${YELLOW}Deleting dummy certificate...${NC}"
docker run --rm \
    -v $(pwd)/certbot/conf:/etc/letsencrypt \
    -v $(pwd)/certbot/www:/var/www/certbot \
    --entrypoint rm \
    certbot/certbot:latest \
    -Rf /etc/letsencrypt/live/$DOMAINS /etc/letsencrypt/archive/$DOMAINS /etc/letsencrypt/renewal/$DOMAINS.conf

echo -e "${GREEN}Dummy certificate deleted${NC}"

# Request Let's Encrypt certificate
echo -e "${YELLOW}Requesting Let's Encrypt certificate for $DOMAINS...${NC}"

# Set staging flag
if [ $STAGING != "0" ]; then
    STAGING_ARG="--staging"
    echo -e "${YELLOW}Using Let's Encrypt staging server (for testing)${NC}"
else
    STAGING_ARG=""
    echo -e "${YELLOW}Using Let's Encrypt production server${NC}"
fi

# Request certificate
docker run --rm \
    -v $(pwd)/certbot/conf:/etc/letsencrypt \
    -v $(pwd)/certbot/www:/var/www/certbot \
    --network ezship_ezship-network \
    certbot/certbot:latest \
    certonly --webroot \
    --webroot-path=/var/www/certbot \
    --email $EMAIL \
    --agree-tos \
    --no-eff-email \
    --force-renewal \
    $STAGING_ARG \
    -d $DOMAINS

# Check if certificate was obtained successfully
if [ $? -eq 0 ]; then
    echo -e "${GREEN}SSL Certificate obtained successfully!${NC}"
else
    echo -e "${RED}Failed to obtain SSL certificate${NC}"
    exit 1
fi

# Reload nginx with new certificate
echo -e "${YELLOW}Reloading nginx...${NC}"
docker-compose -f docker-compose.ssl.yml exec nginx nginx -s reload

# Start certbot container for auto-renewal
echo -e "${YELLOW}Starting certbot auto-renewal container...${NC}"
docker-compose -f docker-compose.ssl.yml up -d certbot

echo -e "${GREEN}=== SSL Setup Complete ===${NC}"
echo -e "${GREEN}Your site is now available at: https://$DOMAINS${NC}"
echo -e "${GREEN}Certificates will auto-renew every 60 days${NC}"

# Create renewal cron job
echo -e "${YELLOW}Setting up auto-renewal cron job...${NC}"
(crontab -l 2>/dev/null; echo "0 12 * * * cd $(pwd) && docker-compose -f docker-compose.ssl.yml run --rm certbot renew --quiet && docker-compose -f docker-compose.ssl.yml exec nginx nginx -s reload") | crontab -

echo -e "${GREEN}Auto-renewal cron job added${NC}"
echo -e "${GREEN}SSL certificates will be checked for renewal daily at noon${NC}"