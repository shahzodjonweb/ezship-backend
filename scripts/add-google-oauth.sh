#!/bin/bash

# Script to add Google OAuth credentials to production
# Usage: ./add-google-oauth.sh CLIENT_ID CLIENT_SECRET

if [ "$#" -ne 2 ]; then
    echo "Usage: $0 <GOOGLE_CLIENT_ID> <GOOGLE_CLIENT_SECRET>"
    echo "Example: $0 123456789.apps.googleusercontent.com your-client-secret"
    exit 1
fi

GOOGLE_CLIENT_ID=$1
GOOGLE_CLIENT_SECRET=$2

echo "Adding Google OAuth credentials to production..."

# Check if credentials already exist
if grep -q "GOOGLE_CLIENT_ID=" .env; then
    echo "Updating existing Google OAuth credentials..."
    sed -i "s/^GOOGLE_CLIENT_ID=.*/GOOGLE_CLIENT_ID=${GOOGLE_CLIENT_ID}/" .env
    sed -i "s/^GOOGLE_CLIENT_SECRET=.*/GOOGLE_CLIENT_SECRET=${GOOGLE_CLIENT_SECRET}/" .env
else
    echo "Adding new Google OAuth credentials..."
    echo "" >> .env
    echo "# Google OAuth Configuration" >> .env
    echo "GOOGLE_CLIENT_ID=${GOOGLE_CLIENT_ID}" >> .env
    echo "GOOGLE_CLIENT_SECRET=${GOOGLE_CLIENT_SECRET}" >> .env
    echo "GOOGLE_REDIRECT_URL=https://api.ezship.app/api/google/callback" >> .env
fi

echo "Clearing Laravel cache..."
docker exec ezship-app-prod php artisan config:clear
docker exec ezship-app-prod php artisan cache:clear

echo "Restarting application..."
docker-compose -f docker-compose.ssl.yml restart app || docker-compose -f docker-compose.prod.yml restart app

echo "✅ Google OAuth credentials added successfully!"
echo "Test at: https://api.ezship.app/api/google/login"