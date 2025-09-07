# GitHub Secrets Configuration

This document outlines all the secrets needed for the CI/CD pipelines to work correctly.

## Required GitHub Secrets

### For CI Testing
No secrets required - uses GitHub's default environment.

### For Docker Hub (Docker Registry)
- `DOCKER_HUB_USERNAME` - Your Docker Hub username
- `DOCKER_HUB_TOKEN` - Docker Hub access token (not password)
  - Generate at: https://hub.docker.com/settings/security

### For Server Deployment (Both dev.ezship.app and app.ezship.app)
Since both environments are on the same server, you only need one set of credentials:
- `SERVER_HOST` - Your server IP or hostname
- `SERVER_USER` - SSH username for the server
- `SERVER_SSH_KEY` - Private SSH key for the server
- `SERVER_PORT` - SSH port (optional, defaults to 22)

### Environment Files (Required)
Store your complete environment configurations:
- `PRODUCTION_ENV_FILE` - Complete .env file content for app.ezship.app
- `DEVELOPMENT_ENV_FILE` - Complete .env file content for dev.ezship.app

The deployment will automatically:
- Use `DEVELOPMENT_ENV_FILE` when deploying to `dev.ezship.app` (dev branch)
- Use `PRODUCTION_ENV_FILE` when deploying to `app.ezship.app` (master branch)

### For Notifications (Optional)
- `SLACK_WEBHOOK` - Slack webhook URL for deployment notifications
  - Create at: https://api.slack.com/messaging/webhooks

## How to Add Secrets to GitHub

1. Go to your repository on GitHub
2. Click on **Settings** tab
3. Click on **Secrets and variables** → **Actions**
4. Click **New repository secret**
5. Add the secret name and value
6. Click **Add secret**

## Adding Environment Files as Secrets

### For PRODUCTION_ENV_FILE (app.ezship.app)

1. Copy your production .env file content:
   ```bash
   cat .env.production
   # Or copy from your local .env configured for production
   ```

2. Go to GitHub repository → Settings → Secrets → Actions
3. Click "New repository secret"
4. Name: `PRODUCTION_ENV_FILE`
5. Value: Paste the entire .env content with production values:
   ```
   APP_NAME=Laravel
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://app.ezship.app
   
   DB_CONNECTION=pgsql
   DB_HOST=localhost
   DB_PORT=5432
   DB_DATABASE=ezship_production
   DB_USERNAME=ezship_prod
   DB_PASSWORD=your_production_password
   
   # ... rest of your production config
   ```
6. Click "Add secret"

### For DEVELOPMENT_ENV_FILE (dev.ezship.app)

1. Copy your development .env file content:
   ```bash
   cat .env.development
   # Or copy from your local .env configured for development
   ```

2. Go to GitHub repository → Settings → Secrets → Actions
3. Click "New repository secret"
4. Name: `DEVELOPMENT_ENV_FILE`
5. Value: Paste the entire .env content with development values:
   ```
   APP_NAME=Laravel
   APP_ENV=local
   APP_DEBUG=true
   APP_URL=https://dev.ezship.app
   
   DB_CONNECTION=pgsql
   DB_HOST=localhost
   DB_PORT=5432
   DB_DATABASE=ezship_dev
   DB_USERNAME=ezship_dev
   DB_PASSWORD=your_dev_password
   
   # ... rest of your development config
   ```
6. Click "Add secret"

### Benefits of This Approach

1. **Security**: No sensitive data in your repository
2. **Flexibility**: Different configs for each environment
3. **Version Control**: Changes to secrets are logged in GitHub
4. **Easy Updates**: Update secrets without code changes
5. **Backup**: GitHub Secrets serve as config backup

## Example SSH Key Setup

### Generate SSH Key Pair (if needed)
```bash
ssh-keygen -t ed25519 -C "github-actions@ezship" -f deploy_key
```

### Add Public Key to Server
```bash
ssh-copy-id -i deploy_key.pub user@your-server
# Or manually add to ~/.ssh/authorized_keys on the server
```

### Add Private Key to GitHub Secrets
1. Copy the private key content:
   ```bash
   cat deploy_key
   ```
2. Add it as `STAGING_SSH_KEY` or `PRODUCTION_SSH_KEY` in GitHub Secrets

## Environment-Specific Secrets

### For Production Environment File
You can also store your production `.env` file as a secret:

1. Create a secret named `PRODUCTION_ENV_FILE`
2. Copy your entire production `.env` content as the value
3. Update the deployment workflow to use it:
   ```yaml
   - name: Create .env file
     run: echo "${{ secrets.PRODUCTION_ENV_FILE }}" > .env
   ```

### For QuickBooks Integration
Store sensitive QuickBooks credentials:
- `QUICKBOOKS_CLIENT_ID`
- `QUICKBOOKS_CLIENT_SECRET`
- `QUICKBOOKS_BASIC_TOKEN`
- `QUICKBOOKS_REALM_ID`

## Security Best Practices

1. **Never commit secrets** to the repository
2. **Use strong, unique tokens** for each service
3. **Rotate secrets regularly** (every 90 days)
4. **Limit secret access** to only necessary workflows
5. **Use environments** for production secrets with required reviewers
6. **Monitor secret usage** in GitHub's security tab

## Environments Setup

### Creating Environments
1. Go to Settings → Environments
2. Click **New environment**
3. Create environments: `staging` and `production`

### Environment Protection Rules
For production environment:
- Required reviewers: Add team members who must approve
- Deployment branches: Only allow `main` or `master`
- Environment secrets: Add production-specific secrets

## Testing Secrets

To test if secrets are properly configured:

```yaml
- name: Test Secrets
  run: |
    if [ -z "${{ secrets.DOCKER_HUB_USERNAME }}" ]; then
      echo "DOCKER_HUB_USERNAME is not set"
      exit 1
    fi
    echo "Secrets are configured correctly"
```

## Troubleshooting

### Secret Not Found
- Ensure the secret name matches exactly (case-sensitive)
- Check if the secret is in the correct environment
- Verify the workflow has access to the secret

### SSH Connection Failed
- Verify the SSH key format (should include headers)
- Check if the public key is on the server
- Ensure the server allows key-based authentication
- Test connection locally first

### Docker Push Failed
- Verify Docker Hub token has push permissions
- Check if the repository exists on Docker Hub
- Ensure the username matches the repository owner