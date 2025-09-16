#!/bin/bash

# Emergency recovery script when site is down
# Run: cd /opt/ezship && bash scripts/emergency-recovery.sh

echo "=== Emergency Recovery for EzShip ==="
echo

# 1. Check if containers are running
echo "1. Checking container status..."
docker ps -a | grep ezship

echo
echo "2. Check for any errors in containers..."
docker logs ezship-app-prod --tail 20 2>&1 || echo "App container not found"
docker logs ezship-nginx-prod --tail 20 2>&1 || echo "Nginx container not found"
docker logs ezship-postgres-prod --tail 10 2>&1 || echo "Postgres container not found"

echo
echo "3. Attempting to start all containers..."
docker-compose -f docker-compose.prod.yml up -d

echo
echo "4. Waiting for containers to start..."
sleep 10

echo
echo "5. Checking container status again..."
docker ps | grep ezship

echo
echo "6. If containers keep crashing, recreate them..."
read -p "Do you want to recreate all containers? (y/N): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo "Recreating all containers..."
    
    # Stop everything
    docker-compose -f docker-compose.prod.yml down
    
    # Clean up
    docker system prune -f
    
    # Create proper .env file
    cat > .env << 'EOF'
APP_NAME=EzShip
APP_ENV=production
APP_KEY=base64:w3XEs41oX2moZD2wI2XzRXiwtdT4peFbpNDttVVSj90=
APP_DEBUG=false
APP_URL=https://api.ezship.app

DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=ezship_production
DB_USERNAME=ezship_user
DB_PASSWORD=ezship123

SESSION_DRIVER=file
CACHE_DRIVER=file
QUEUE_CONNECTION=sync

REDIS_HOST=redis
REDIS_PORT=6379
EOF
    
    # Start fresh
    docker-compose -f docker-compose.prod.yml up -d --build
    
    echo "Waiting for services to be ready..."
    sleep 30
fi

echo
echo "7. Testing nginx..."
curl -I http://localhost:80 2>/dev/null || echo "Nginx not responding on port 80"
curl -I http://localhost:443 2>/dev/null || echo "Nginx not responding on port 443"

echo
echo "8. Testing app health..."
docker exec ezship-app-prod php artisan --version 2>/dev/null || echo "App container not healthy"

echo
echo "9. Network diagnostics..."
docker network ls | grep ezship
docker network inspect ezship-backend_ezship-network 2>/dev/null | grep -A 5 "Containers" || echo "Network issue detected"

echo
echo "=== Recovery Complete ==="
echo
echo "Check site status:"
echo "  Local: curl http://localhost"
echo "  Remote: https://api.ezship.app"
echo
echo "If still down, check:"
echo "  1. Firewall rules (port 80, 443)"
echo "  2. Domain DNS (should point to server IP)"
echo "  3. SSL certificates"
echo "  4. docker logs ezship-nginx-prod"