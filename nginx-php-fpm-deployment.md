# Nginx + PHP-FPM Deployment Configuration

## Production Server Setup for EduPro

---

## 1. Server Requirements

- **OS**: Ubuntu 22.04 LTS (recommended) or Debian 11+
- **Web Server**: Nginx 1.20+
- **PHP**: 8.2+ with FPM
- **Database**: MySQL 8.0 / MariaDB 10.6+ / PostgreSQL 14+
- **Node.js**: 18+ (for building frontend)

---

## 2. Initial Server Setup

```bash
#!/bin/bash
# Run as root or with sudo

# Update system
apt update && apt upgrade -y

# Install Nginx
apt install nginx -y

# Install PHP 8.2 and extensions
apt install php8.2 php8.2-fpm php8.2-cli php8.2-common php8.2-mysql \
php8.2-zip php8.2-gd php8.2-mbstring php8.2-curl php8.2-xml \
php8.2-bcmath php8.2-sqlite3 php8.2-intl php8.2-tokenizer -y

# Install Composer
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer

# Install Node.js 18.x
curl -fsSL https://deb.nodesource.com/setup_18.x | bash -
apt install nodejs -y

# Install Git (if not installed)
apt install git -y

# Install Certbot for SSL
apt install certbot python3-certbot-nginx -y

echo "✅ Server setup complete!"
```

---

## 3. Application Directory Setup

```bash
# Create application directory
mkdir -p /var/www/edupro
cd /var/www/edupro

# Upload your application (choose one method):

# Method 1: Git Clone
# git clone <your-repository-url> .

# Method 2: Upload via SCP
# scp -r backend frontend user@server:/var/www/edupro/

# Method 3: Upload ZIP and extract
# unzip edupro.zip && rm edupro.zip

# Set ownership
chown -R $USER:$USER /var/www/edupro
```

---

## 4. Backend Configuration

```bash
cd /var/www/edupro/backend

# Install PHP dependencies (production)
composer install --optimize-autoloader --no-dev

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Edit .env file with production settings
nano .env
```

### Production .env Configuration

```env
APP_NAME="EduPro SMS"
APP_ENV=production
APP_KEY=base64:your-generated-key-here
APP_DEBUG=false
APP_URL=https://your-domain.com

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

# Database Configuration (MySQL/MariaDB)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=edupro
DB_USERNAME=edupro_user
DB_PASSWORD=your_strong_password_here

# Or SQLite (simpler for small deployments)
# DB_CONNECTION=sqlite
# SQLITE_WAL_MODE=true

# Session & Cache
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true
SESSION_PATH=/
SESSION_DOMAIN=your-domain.com

CACHE_STORE=database
QUEUE_CONNECTION=sync

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@edupro.com
MAIL_FROM_NAME="${APP_NAME}"

# Sanctum Configuration
SANCTUM_STATEFUL_DOMAINS=your-domain.com,www.your-domain.com
```

---

## 5. Database Setup

### MySQL/MariaDB Setup

```bash
# Login to MySQL
mysql -u root -p

# Run these SQL commands:
CREATE DATABASE edupro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'edupro_user'@'localhost' IDENTIFIED BY 'your_strong_password_here';
GRANT ALL PRIVILEGES ON edupro.* TO 'edupro_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Run migrations
cd /var/www/edupro/backend
php artisan migrate --force

# Seed initial data (optional)
php artisan db:seed --force
```

### SQLite Setup (Alternative)

```bash
cd /var/www/edupro/backend

# Create database file
touch database/database.sqlite
chmod 664 database/database.sqlite

# Update .env (set DB_CONNECTION=sqlite)

# Run migrations
php artisan migrate --force
php artisan db:seed --force
```

---

## 6. Frontend Build

```bash
cd /var/www/edupro/frontend

# Install dependencies
npm install

# Build for production
npm run build

# Verify dist folder created
ls -la dist/
```

---

## 7. Nginx Configuration

### Create Nginx Server Block

```bash
nano /etc/nginx/sites-available/edupro
```

### Complete Nginx Configuration

```nginx
# Rate limiting zone
limit_req_zone $binary_remote_addr zone=api:10m rate=10r/s;
limit_req_zone $binary_remote_addr zone=auth:10m rate=5r/s;

server {
    listen 80;
    server_name your-domain.com www.your-domain.com;
    
    # Redirect HTTP to HTTPS (after SSL setup)
    # return 301 https://$server_name$request_uri;
    
    # For initial setup without SSL
    root /var/www/edupro/frontend/dist;
    index index.html;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Permissions-Policy "geolocation=(), microphone=(), camera=()" always;

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_min_length 1024;
    gzip_types text/plain text/css text/xml text/javascript application/x-javascript application/xml+rss application/json application/javascript application/x-font-ttf font/opentype image/svg+xml;

    # Frontend - Vue.js SPA
    location / {
        try_files $uri $uri/ /index.html;
    }

    # Backend API - Laravel via PHP-FPM
    location /api {
        alias /var/www/edupro/backend/public;
        
        # Try to serve file directly, otherwise pass to PHP
        try_files $uri $uri/ /index.php?$query_string;

        # PHP-FPM configuration
        location ~ \.php$ {
            fastcgi_pass unix:/run/php/php8.2-fpm.sock;
            fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
            include fastcgi_params;
            fastcgi_hide_header X-Powered-By;
            fastcgi_hide_header X-Robots-Tag;
            
            # Fastcgi settings
            fastcgi_buffer_size 128k;
            fastcgi_buffers 4 256k;
            fastcgi_busy_buffers_size 256k;
            fastcgi_read_timeout 300s;
            fastcgi_send_timeout 300s;
        }

        # Deny access to .htaccess and .git
        location ~ /\.ht {
            deny all;
        }
        
        location ~ /\.git {
            deny all;
        }

        # Rate limiting for auth endpoints
        location /api/auth {
            alias /var/www/edupro/backend/public;
            try_files $uri $uri/ /index.php?$query_string;
            
            location ~ \.php$ {
                fastcgi_pass unix:/run/php/php8.2-fpm.sock;
                fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
                include fastcgi_params;
                limit_req zone=auth burst=5 nodelay;
            }
        }
    }

    # Laravel storage (user uploads)
    location /storage {
        alias /var/www/edupro/backend/storage/app/public;
        
        location ~ \.php$ {
            fastcgi_pass unix:/run/php/php8.2-fpm.sock;
            fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
            include fastcgi_params;
        }
    }

    # Cache static assets
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot|mp4|webm)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    # Deny access to sensitive files
    location ~ /(\.env|\.git|composer\.(json|lock)|package\.json|phpunit\.xml|\.php$) {
        deny all;
        return 404;
    }

    # Logs
    access_log /var/log/nginx/edupro_access.log;
    error_log /var/log/nginx/edupro_error.log;
}
```

### Enable Site

```bash
# Create symbolic link
ln -s /etc/nginx/sites-available/edupro /etc/nginx/sites-enabled/

# Remove default site
rm /etc/nginx/sites-enabled/default

# Test Nginx configuration
nginx -t

# Reload Nginx
systemctl reload nginx
```

---

## 8. PHP-FPM Configuration

```bash
nano /etc/php/8.2/fpm/pool.d/www.conf
```

### Update Pool Settings

```ini
; User/Group
user = www-data
group = www-data

; Process Manager
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.max_requests = 500

; PHP Settings
php_admin_value[upload_max_filesize] = 20M
php_admin_value[post_max_size] = 20M
php_admin_value[max_execution_time] = 300
php_admin_value[max_input_time] = 300
php_admin_value[memory_limit] = 256M
php_admin_value[display_errors] = off
php_admin_value[log_errors] = on
php_admin_value[error_log] = /var/www/edupro/backend/storage/logs/php-fpm.log

; Session
php_admin_value[session.save_path] = /var/lib/php/sessions
php_admin_value[session.gc_maxlifetime] = 7200

; Security
php_admin_flag[expose_php] = off
php_admin_value[disable_functions] = exec,passthru,shell_exec,system,proc_open,popen,curl_exec,curl_multi_exec,parse_ini_file,show_source
```

### Restart PHP-FPM

```bash
systemctl restart php8.2-fpm
systemctl enable php8.2-fpm
```

---

## 9. File Permissions

```bash
cd /var/www/edupro/backend

# Set ownership
chown -R www-data:www-data /var/www/edupro/backend

# Set directory permissions
find /var/www/edupro/backend -type d -exec chmod 755 {} \;

# Set file permissions
find /var/www/edupro/backend -type f -exec chmod 644 {} \;

# Storage and cache need write permissions
chmod -R 775 /var/www/edupro/backend/storage
chmod -R 775 /var/www/edupro/backend/bootstrap/cache

# Set proper ownership for storage
chown -R www-data:www-data /var/www/edupro/backend/storage
chown -R www-data:www-data /var/www/edupro/backend/bootstrap/cache
```

---

## 10. Laravel Optimization

```bash
cd /var/www/edupro/backend

# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Build optimized caches
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Verify optimization
php artisan optimize:clear
```

---

## 11. SSL Certificate (Let's Encrypt)

```bash
# Stop Nginx temporarily (if port 80 is busy)
# systemctl stop nginx

# Get certificate
certbot --nginx -d your-domain.com -d www.your-domain.com

# Or standalone mode
# certbot certonly --standalone -d your-domain.com -d www.your-domain.com

# Nginx will be automatically configured with SSL
# Test auto-renewal
certbot renew --dry-run
```

### Manual SSL Configuration (if needed)

```nginx
server {
    listen 443 ssl http2;
    server_name your-domain.com www.your-domain.com;

    ssl_certificate /etc/letsencrypt/live/your-domain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/your-domain.com/privkey.pem;
    
    # SSL settings
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_prefer_server_ciphers on;
    ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;
    ssl_stapling on;
    ssl_stapling_verify on;

    # ... rest of configuration from step 7
}

# HTTP redirect to HTTPS
server {
    listen 80;
    server_name your-domain.com www.your-domain.com;
    return 301 https://$server_name$request_uri;
}
```

---

## 12. Queue Worker Setup (Optional)

If using queues for background jobs:

```bash
# Install Supervisor
apt install supervisor -y

# Create worker configuration
nano /etc/supervisor/conf.d/edupro-worker.conf
```

```ini
[program:edupro-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/edupro/backend/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasuser=false
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/edupro/backend/storage/logs/worker-%(process_num)02d.log
stopwaitsecs=3600
```

```bash
# Start workers
supervisorctl reread
supervisorctl update
supervisorctl start edupro-worker:*
supervisorctl status edupro-worker:*
```

---

## 13. Scheduled Tasks (Cron)

```bash
# Edit crontab
crontab -e

# Add Laravel scheduler
* * * * * cd /var/www/edupro/backend && php artisan schedule:run >> /dev/null 2>&1

# Add backup job (daily at 2 AM)
0 2 * * * /var/www/edupro/backup.sh >> /var/log/edupro_backup.log 2>&1

# Add log rotation (weekly)
0 0 * * 0 cd /var/www/edupro/backend && php artisan log:rotate >> /dev/null 2>&1
```

---

## 14. Automated Deployment Script

Create deployment script:

```bash
nano /var/www/edupro/deploy.sh
```

```bash
#!/bin/bash

set -e

echo "🚀 Starting EduPro Deployment..."
echo "================================"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Backend
echo -e "${YELLOW}📦 Deploying Backend...${NC}"
cd /var/www/edupro/backend

# Pull latest changes (if using git)
# git pull origin main

# Install dependencies
composer install --optimize-autoloader --no-dev --quiet

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Run migrations
php artisan migrate --force

# Optimize
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set permissions
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

echo -e "${GREEN}✅ Backend deployed${NC}"

# Frontend
echo -e "${YELLOW}🎨 Deploying Frontend...${NC}"
cd /var/www/edupro/frontend

# Pull latest changes
# git pull origin main

# Install dependencies
npm install --silent

# Build
npm run build

echo -e "${GREEN}✅ Frontend deployed${NC}"

# Restart services
echo -e "${YELLOW}🔄 Restarting Services...${NC}"
systemctl reload nginx
systemctl reload php8.2-fpm
supervisorctl restart edupro-worker:* 2>/dev/null || true

echo -e "${GREEN}================================${NC}"
echo -e "${GREEN}✅ Deployment Complete!${NC}"
echo -e "${GREEN}================================${NC}"
echo ""
echo "Application URL: https://your-domain.com"
echo "Backend: /var/www/edupro/backend"
echo "Frontend: /var/www/edupro/frontend"
```

```bash
# Make executable
chmod +x /var/www/edupro/deploy.sh

# Usage:
# ./deploy.sh
```

---

## 15. Backup Script

```bash
nano /var/www/edupro/backup.sh
```

```bash
#!/bin/bash

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backups/edupro"
RETENTION_DAYS=7

# Create backup directory
mkdir -p $BACKUP_DIR

echo "📦 Starting backup: $DATE"

# Backup database
echo "💾 Backing up database..."
mysqldump -u edupro_user -p'your_password' edupro | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# Backup files
echo "📁 Backing up files..."
tar -czf $BACKUP_DIR/files_$DATE.tar.gz \
    --exclude='node_modules' \
    --exclude='vendor' \
    --exclude='.git' \
    /var/www/edupro

# Backup uploads
echo "📸 Backing up uploads..."
tar -czf $BACKUP_DIR/uploads_$DATE.tar.gz /var/www/edupro/backend/storage/app

# Delete old backups
echo "🧹 Cleaning old backups..."
find $BACKUP_DIR -name "*.sql.gz" -mtime +$RETENTION_DAYS -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +$RETENTION_DAYS -delete

echo "✅ Backup completed: $DATE"
```

```bash
chmod +x /var/www/edupro/backup.sh
```

---

## 16. Monitoring & Logs

### Log Files Location

```bash
# Laravel logs
tail -f /var/www/edupro/backend/storage/logs/laravel.log

# Nginx access log
tail -f /var/log/nginx/edupro_access.log

# Nginx error log
tail -f /var/log/nginx/edupro_error.log

# PHP-FPM log
tail -f /var/log/php8.2-fpm.log

# Queue worker logs
tail -f /var/www/edupro/backend/storage/logs/worker-00.log
```

### Health Check Script

```bash
nano /var/www/edupro/health-check.sh
```

```bash
#!/bin/bash

echo "🏥 EduPro Health Check"
echo "====================="

# Check Nginx
if systemctl is-active --quiet nginx; then
    echo "✅ Nginx is running"
else
    echo "❌ Nginx is NOT running"
fi

# Check PHP-FPM
if systemctl is-active --quiet php8.2-fpm; then
    echo "✅ PHP-FPM is running"
else
    echo "❌ PHP-FPM is NOT running"
fi

# Check MySQL
if systemctl is-active --quiet mysql; then
    echo "✅ MySQL is running"
else
    echo "❌ MySQL is NOT running"
fi

# Check disk space
DISK_USAGE=$(df -h / | awk 'NR==2 {print $5}')
echo "💾 Disk Usage: $DISK_USAGE"

# Check memory
MEMORY_USAGE=$(free -h | awk 'NR==2 {printf "%.2f%%", $3*100/$2}')
echo "🧠 Memory Usage: $MEMORY_USAGE"

# Test application
RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" https://your-domain.com)
if [ "$RESPONSE" = "200" ]; then
    echo "✅ Application is responding (HTTP $RESPONSE)"
else
    echo "❌ Application returned HTTP $RESPONSE"
fi
```

---

## 17. Troubleshooting

### Common Issues

**502 Bad Gateway**
```bash
systemctl status php8.2-fpm
systemctl restart php8.2-fpm
tail -f /var/log/nginx/edupro_error.log
```

**403 Forbidden**
```bash
chown -R www-data:www-data /var/www/edupro/backend
chmod -R 755 /var/www/edupro/backend/public
```

**Slow Performance**
```bash
# Increase PHP-FPM workers
nano /etc/php/8.2/fpm/pool.d/www.conf
# Increase pm.max_children

systemctl restart php8.2-fpm
```

**Database Connection Error**
```bash
# Check MySQL is running
systemctl status mysql

# Test connection
mysql -u edupro_user -p -e "SELECT 1"
```

**Permission Denied**
```bash
chmod -R 775 /var/www/edupro/backend/storage
chmod -R 775 /var/www/edupro/backend/bootstrap/cache
chown -R www-data:www-data /var/www/edupro/backend/storage
```

---

## 18. Final Checklist

- [ ] Server updated and secured
- [ ] Nginx installed and configured
- [ ] PHP 8.2+ with FPM installed
- [ ] Database created and migrated
- [ ] Backend deployed and optimized
- [ ] Frontend built for production
- [ ] SSL certificate installed
- [ ] File permissions set correctly
- [ ] Nginx configuration tested
- [ ] All services running
- [ ] Application accessible via HTTPS
- [ ] Backups configured
- [ ] Monitoring in place
- [ ] Deployment script tested

---

## Quick Commands Reference

```bash
# Restart all services
systemctl restart nginx php8.2-fpm mysql

# Check status
systemctl status nginx php8.2-fpm mysql

# View logs
journalctl -u nginx -f
journalctl -u php8.2-fpm -f

# Deploy application
cd /var/www/edupro && ./deploy.sh

# Clear Laravel cache
cd /var/www/edupro/backend && php artisan optimize:clear

# Test Nginx config
nginx -t

# Check PHP-FPM status
systemctl status php8.2-fpm
```

---

Your EduPro application is now deployed and ready for production! 🎉
