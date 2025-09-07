# EzShip Backend - Docker Deployment Guide

## Prerequisites

- Docker Engine 20.10+
- Docker Compose 2.0+
- Make (optional, for using Makefile commands)

## Quick Start

### 1. Clone the repository
```bash
git clone <repository-url>
cd ezship-backend
```

### 2. Setup environment
```bash
cp .env.docker .env
# Edit .env file with your configuration
```

### 3. Build and start containers
```bash
docker-compose up -d --build
```

### 4. Install dependencies and setup database
```bash
docker-compose exec app composer install
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate
docker-compose exec app php artisan db:seed  # Optional
docker-compose exec app php artisan storage:link
```

### 5. Access the application
- Application: http://localhost:8080
- MySQL: localhost:3307
- Redis: localhost:6380

## Docker Services

The application uses the following services:

- **app**: PHP 8.2 with Laravel application
- **nginx**: Web server
- **db**: MySQL 8.0 database
- **redis**: Redis cache server
- **queue**: Queue worker for background jobs
- **scheduler**: Laravel task scheduler

## Using Makefile Commands

If you have `make` installed, you can use these shortcuts:

```bash
# Setup development environment
make dev-setup

# Start containers
make up

# Stop containers
make down

# View logs
make logs

# Access app shell
make shell

# Run migrations
make migrate

# Clear caches
make clear

# Full deployment
make deploy
```

## Common Docker Commands

### Container Management
```bash
# Start all containers
docker-compose up -d

# Stop all containers
docker-compose down

# Restart containers
docker-compose restart

# View logs
docker-compose logs -f

# View specific service logs
docker-compose logs -f app
```

### Application Commands
```bash
# Access app container
docker-compose exec app bash

# Run artisan commands
docker-compose exec app php artisan [command]

# Run composer
docker-compose exec app composer [command]

# Clear caches
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan view:clear

# Optimize for production
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache
```

### Database Commands
```bash
# Run migrations
docker-compose exec app php artisan migrate

# Fresh migration with seeders
docker-compose exec app php artisan migrate:fresh --seed

# Access MySQL shell
docker-compose exec db mysql -u root -p

# Backup database
docker-compose exec db mysqldump -u ezship -p ezship > backup.sql

# Restore database
docker-compose exec -T db mysql -u ezship -p ezship < backup.sql
```

## Environment Configuration

### Development
Use `.env.docker` as a template and modify for development:
```bash
APP_ENV=local
APP_DEBUG=true
```

### Production
For production deployment:
```bash
APP_ENV=production
APP_DEBUG=false
```

Remember to:
1. Set strong passwords for database
2. Configure proper mail settings
3. Set up SSL certificates for HTTPS
4. Configure QuickBooks API credentials

## Troubleshooting

### Permission Issues
If you encounter permission errors:
```bash
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
docker-compose exec app chmod -R 775 storage bootstrap/cache
```

### Database Connection Issues
Ensure the database service is running:
```bash
docker-compose ps db
docker-compose logs db
```

### Clear Docker Resources
To clean up Docker resources:
```bash
docker-compose down -v  # Remove containers and volumes
docker system prune -f  # Remove unused resources
```

### Rebuild Containers
If you make changes to Dockerfile:
```bash
docker-compose build --no-cache
docker-compose up -d
```

## Production Deployment on app.ezship.app & dev.ezship.app

### Server Setup

1. **Run the setup script on your server:**
   ```bash
   wget https://raw.githubusercontent.com/YOUR_REPO/master/scripts/server-setup.sh
   chmod +x server-setup.sh
   sudo ./server-setup.sh
   ```

2. **Configure GitHub Secrets:**
   Go to Settings → Secrets → Actions and add:
   - `SERVER_HOST`: Your server IP or domain
   - `SERVER_USER`: deploy
   - `SERVER_SSH_KEY`: Your private SSH key
   - `SERVER_PORT`: 22 (or your custom SSH port)

3. **Deployment Flow:**
   - Push to `dev` branch → Deploys to https://dev.ezship.app
   - Push to `master` branch → Deploys to https://app.ezship.app

### Manual Deployment Commands

For dev.ezship.app:
```bash
cd /var/www/dev.ezship.app
git pull origin dev
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize
php artisan queue:restart
```

For app.ezship.app:
```bash
cd /var/www/app.ezship.app
git pull origin master
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize
php artisan queue:restart
```

### Example Production docker-compose Override
Create `docker-compose.prod.yml`:
```yaml
version: '3.8'

services:
  app:
    restart: always
    environment:
      - APP_ENV=production
      - APP_DEBUG=false

  nginx:
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./ssl:/etc/nginx/ssl:ro
```

Run with:
```bash
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d
```

## Security Considerations

1. **Never commit `.env` file** to version control
2. Use strong passwords for all services
3. Regularly update Docker images
4. Implement proper firewall rules
5. Use SSL/TLS for production
6. Regularly backup your database
7. Monitor logs for suspicious activity

## Support

For issues or questions, please check:
- Docker logs: `docker-compose logs`
- Laravel logs: `storage/logs/laravel.log`
- Nginx logs: `docker-compose logs nginx`