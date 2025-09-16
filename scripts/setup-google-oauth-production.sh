#!/bin/bash

# Complete Google OAuth Setup for Production
echo "=== Google OAuth Production Setup ==="
echo ""
echo "This script will help you set up Google OAuth on your production server."
echo ""

# Prompt for credentials
read -p "Enter your Google Client ID (ends with .apps.googleusercontent.com): " CLIENT_ID
read -p "Enter your Google Client Secret (starts with GOCSPX-): " CLIENT_SECRET

if [ -z "$CLIENT_ID" ] || [ -z "$CLIENT_SECRET" ]; then
    echo "❌ Error: Client ID and Secret are required!"
    exit 1
fi

echo ""
echo "Adding credentials to production environment..."

# SSH command to add credentials
ssh_command="cd /opt/ezship && \
    grep -q 'GOOGLE_CLIENT_ID=' .env && \
    sed -i 's/^GOOGLE_CLIENT_ID=.*/GOOGLE_CLIENT_ID=$CLIENT_ID/' .env || \
    echo 'GOOGLE_CLIENT_ID=$CLIENT_ID' >> .env && \
    grep -q 'GOOGLE_CLIENT_SECRET=' .env && \
    sed -i 's/^GOOGLE_CLIENT_SECRET=.*/GOOGLE_CLIENT_SECRET=$CLIENT_SECRET/' .env || \
    echo 'GOOGLE_CLIENT_SECRET=$CLIENT_SECRET' >> .env && \
    grep -q 'GOOGLE_REDIRECT_URL=' .env && \
    sed -i 's|^GOOGLE_REDIRECT_URL=.*|GOOGLE_REDIRECT_URL=https://api.ezship.app/api/google/callback|' .env || \
    echo 'GOOGLE_REDIRECT_URL=https://api.ezship.app/api/google/callback' >> .env && \
    docker exec ezship-app-prod php artisan config:clear && \
    docker exec ezship-app-prod php artisan cache:clear && \
    echo '✅ Google OAuth configured successfully!'"

echo "Please enter your server details:"
read -p "Server IP or hostname: " SERVER_HOST
read -p "SSH username (default: root): " SSH_USER
SSH_USER=${SSH_USER:-root}

echo ""
echo "Connecting to $SERVER_HOST as $SSH_USER..."
ssh $SSH_USER@$SERVER_HOST "$ssh_command"

echo ""
echo "=== Setup Complete ==="
echo "Test your Google OAuth at: https://api.ezship.app/api/google/login"
echo ""
echo "Use this test curl command:"
echo "curl -X POST https://api.ezship.app/api/google/login \\"
echo "  -H 'Content-Type: application/json' \\"
echo "  -d '{\"access_key\": \"test\"}'"