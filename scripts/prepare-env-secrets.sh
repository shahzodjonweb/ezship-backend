#!/bin/bash

# Script to prepare environment files for GitHub Secrets
# This helps format your .env files for easy copying to GitHub Secrets

echo "📋 Prepare Environment Files for GitHub Secrets"
echo "=============================================="
echo ""

# Function to prepare env file
prepare_env_file() {
    local env_file=$1
    local secret_name=$2
    
    if [ ! -f "$env_file" ]; then
        echo "❌ File not found: $env_file"
        return 1
    fi
    
    echo "📄 Preparing $env_file for $secret_name"
    echo "=============================================="
    echo ""
    echo "Copy the following content to GitHub Secret: $secret_name"
    echo ""
    echo "---START---"
    cat "$env_file"
    echo "---END---"
    echo ""
    echo "=============================================="
    echo ""
}

# Check for production env file
if [ -f ".env.production" ]; then
    prepare_env_file ".env.production" "PRODUCTION_ENV_FILE"
elif [ -f ".env.prod" ]; then
    prepare_env_file ".env.prod" "PRODUCTION_ENV_FILE"
else
    echo "⚠️  Production environment file not found."
    echo "   Create .env.production with your production settings"
    echo ""
fi

# Check for development env file
if [ -f ".env.development" ]; then
    prepare_env_file ".env.development" "DEVELOPMENT_ENV_FILE"
elif [ -f ".env.dev" ]; then
    prepare_env_file ".env.dev" "DEVELOPMENT_ENV_FILE"
else
    echo "⚠️  Development environment file not found."
    echo "   Create .env.development with your development settings"
    echo ""
fi

echo "📝 Instructions:"
echo "1. Go to: https://github.com/YOUR_USERNAME/YOUR_REPO/settings/secrets/actions"
echo "2. Click 'New repository secret'"
echo "3. Add each secret with the content shown above"
echo ""
echo "🔒 Security Tips:"
echo "- Never commit .env files to your repository"
echo "- Use strong, unique passwords for each environment"
echo "- Rotate credentials regularly"
echo "- Use different database credentials for each environment"
echo ""