#!/bin/bash

# Fix nginx and networking issues
# Run: cd /opt/ezship && bash scripts/fix-nginx.sh

echo "=== Fixing Nginx and Networking ==="
echo

# 1. Check if nginx container exists
echo "1. Checking nginx container..."
docker ps -a | grep nginx

# 2. Check nginx logs
echo
echo "2. Nginx error logs:"
docker logs ezship-nginx-prod --tail 20 2>&1 || {
    echo "Nginx container not found, creating it..."
    docker-compose -f docker-compose.prod.yml up -d nginx
}

# 3. Check if ports are already in use
echo
echo "3. Checking if ports 80/443 are already in use..."
sudo lsof -i :80 | head -5
sudo lsof -i :443 | head -5

# 4. If ports are blocked, stop conflicting services
echo
echo "4. Stopping any conflicting web servers..."
sudo systemctl stop apache2 2>/dev/null || true
sudo systemctl stop nginx 2>/dev/null || true
sudo systemctl stop httpd 2>/dev/null || true

# 5. Restart Docker nginx
echo
echo "5. Restarting nginx container..."
docker stop ezship-nginx-prod 2>/dev/null || true
docker rm ezship-nginx-prod 2>/dev/null || true
docker-compose -f docker-compose.prod.yml up -d nginx

# 6. Check if nginx is running
echo
echo "6. Verifying nginx is running..."
sleep 5
docker ps | grep nginx

# 7. Test nginx config
echo
echo "7. Testing nginx configuration..."
docker exec ezship-nginx-prod nginx -t 2>&1 || echo "Config test failed"

# 8. Check network connectivity
echo
echo "8. Testing network connectivity..."
docker exec ezship-nginx-prod ping -c 2 app 2>&1 || echo "Cannot reach app container"

# 9. Alternative: Use app container's built-in nginx
echo
echo "9. If nginx still fails, using app container directly..."
read -p "Do you want to expose app container directly? (y/N): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    # Stop nginx container
    docker stop ezship-nginx-prod
    
    # Update app container to expose port 80
    docker stop ezship-app-prod
    docker run -d \
        --name ezship-app-prod-temp \
        --network ezship-backend_ezship-network \
        -p 80:80 \
        -p 443:443 \
        -v $(pwd)/storage:/var/www/html/storage \
        -v $(pwd)/public/uploads:/var/www/html/public/uploads \
        -e APP_ENV=production \
        -e DB_HOST=postgres \
        -e DB_DATABASE=ezship_production \
        -e DB_USERNAME=ezship_user \
        -e DB_PASSWORD=ezship123 \
        ezship-prod:latest
    
    docker rm ezship-app-prod
    docker rename ezship-app-prod-temp ezship-app-prod
    
    echo "App container now directly exposed on ports 80/443"
fi

echo
echo "=== Nginx Fix Complete ==="
echo
echo "Test the site:"
echo "  curl -I http://localhost"
echo "  curl -I https://api.ezship.app"
echo
echo "If still not working:"
echo "  1. Check firewall: sudo ufw status"
echo "  2. Check DNS: nslookup api.ezship.app"
echo "  3. Check SSL: docker exec ezship-nginx-prod ls /etc/nginx/ssl/"