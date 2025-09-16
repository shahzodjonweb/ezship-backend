#!/bin/bash

# Quick restart when site is down
# Run: cd /opt/ezship && bash scripts/quick-restart.sh

echo "=== Quick Restart EzShip ==="

# Check current status
echo "Current container status:"
docker ps -a | grep ezship

# Restart all containers
echo
echo "Restarting all containers..."
docker-compose -f docker-compose.prod.yml restart

# If restart doesn't work, try stop and start
if [ $? -ne 0 ]; then
    echo "Restart failed, trying stop and start..."
    docker-compose -f docker-compose.prod.yml stop
    docker-compose -f docker-compose.prod.yml up -d
fi

# Wait for services
echo
echo "Waiting for services to be ready..."
sleep 15

# Check status
echo
echo "New container status:"
docker ps | grep ezship

# Test connectivity
echo
echo "Testing connectivity..."
curl -I http://localhost 2>&1 | head -3
curl -I https://api.ezship.app 2>&1 | head -3

echo
echo "=== Restart Complete ==="
echo
echo "If site is still down, run: bash scripts/emergency-recovery.sh"