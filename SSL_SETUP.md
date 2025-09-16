# SSL Certificate Setup with Let's Encrypt

This setup provides automatic SSL certificates with auto-renewal for your EzShip API.

## Features
- ✅ Free SSL certificates from Let's Encrypt
- ✅ Auto-renewal every 60 days
- ✅ HTTP to HTTPS redirect
- ✅ A+ SSL rating configuration
- ✅ Works with Docker
- ✅ Zero downtime renewal

## Prerequisites
- Domain pointing to your server (api.ezship.app)
- Port 80 and 443 open on your server
- Docker and Docker Compose installed

## Initial Setup

### 1. Update Email Address
Edit `scripts/init-ssl.sh` and change the email:
```bash
EMAIL="your-email@example.com"  # Change this to your email
```

### 2. Run SSL Initialization
```bash
cd /opt/ezship
sudo ./scripts/init-ssl.sh
```

This will:
- Create a temporary certificate
- Start nginx
- Request real Let's Encrypt certificate
- Set up auto-renewal

### 3. Verify SSL
Visit: https://api.ezship.app

Check SSL status:
```bash
docker exec ezship-certbot certbot certificates
```

## Auto-Renewal

Certificates auto-renew in two ways:

### 1. Certbot Container (Primary)
The certbot container runs continuously and checks every 12 hours:
```bash
docker logs ezship-certbot
```

### 2. Cron Job (Backup)
A daily cron job at noon also checks for renewal:
```bash
crontab -l | grep certbot
```

## Manual Operations

### Force Renewal
```bash
docker-compose -f docker-compose.ssl.yml run --rm certbot renew --force-renewal
docker-compose -f docker-compose.ssl.yml exec nginx nginx -s reload
```

### Check Certificate Expiry
```bash
docker exec ezship-certbot certbot certificates
```

### View Renewal Logs
```bash
docker logs ezship-certbot
```

## Switching Environments

### Use Staging (Testing)
Edit `scripts/init-ssl.sh`:
```bash
STAGING=1  # Use Let's Encrypt staging server
```

### Use Production (Live)
Edit `scripts/init-ssl.sh`:
```bash
STAGING=0  # Use Let's Encrypt production server
```

## File Structure
```
/opt/ezship/
├── docker-compose.ssl.yml      # SSL-enabled compose file
├── docker/
│   └── nginx/
│       └── nginx-ssl.conf      # SSL nginx configuration
├── certbot/
│   ├── conf/                   # SSL certificates
│   │   └── live/
│   │       └── api.ezship.app/
│   │           ├── fullchain.pem
│   │           └── privkey.pem
│   └── www/                    # ACME challenges
└── scripts/
    └── init-ssl.sh              # SSL initialization script
```

## Troubleshooting

### Certificate Request Failed
- Check domain DNS: `nslookup api.ezship.app`
- Check port 80 is accessible: `curl http://api.ezship.app`
- Check nginx logs: `docker logs ezship-nginx-prod`

### Renewal Failed
- Check certbot logs: `docker logs ezship-certbot`
- Manually renew: `docker-compose -f docker-compose.ssl.yml run --rm certbot renew`
- Check disk space: `df -h`

### HTTPS Not Working
- Check certificate exists: `ls -la ./certbot/conf/live/api.ezship.app/`
- Check nginx config: `docker exec ezship-nginx-prod nginx -t`
- Reload nginx: `docker-compose -f docker-compose.ssl.yml exec nginx nginx -s reload`

## Security Headers
The SSL configuration includes:
- HSTS (Strict Transport Security)
- X-Frame-Options
- X-Content-Type-Options
- X-XSS-Protection
- TLS 1.2+ only
- Strong cipher suites

## Testing SSL
Test your SSL configuration:
- https://www.ssllabs.com/ssltest/analyze.html?d=api.ezship.app
- https://securityheaders.com/?q=api.ezship.app

## Notes
- Certificates are valid for 90 days
- Renewal happens automatically when <30 days remain
- Let's Encrypt rate limits: 50 certificates per domain per week
- Always test with staging server first# SSL Test
