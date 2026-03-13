# EduPro Deployment Guide - Nginx Server

## Prerequisites
- Ubuntu/Debian server with nginx installed
- PHP 8.2+ with required extensions
- Composer
- Node.js 18+ and npm
- MySQL/PostgreSQL or SQLite
- SSL certificate (recommended)

---

## 1. Server Setup

### Install Required Packages
```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install nginx
sudo apt install nginx -y

# Install PHP and extensions
sudo apt install php8.2 php8.2-fpm php8.2-cli php8.2-common php8.2-mysql \
php8.2-zip php8.2-gd php8.2-mbstring php8.2-curl php8.2-xml php8.2-bcmath \
php8.2-json php8.2-tokenizer php8.2-sqlite3 -y

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Node.js (if not installed)
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs
```

---

## 2. Clone/Upload Application

```bash
# Create application directory
sudo mkdir -p /var/www/edupro
sudo chown -R $USER:$USER /var/www/edupro

# Clone repository or upload files
cd /var/www/edupro
# git clone <your-repo-url> .  # If using git
# Or upload via SCP/FTP

# Directory structure:
# /var/www/edupro/
# ├── backend/
# └── frontend/
```

---

## 3. Backend Setup (Laravel)

```bash
cd /var/www/edupro/backend

# Install PHP dependencies
composer install --optimize-autoloader --no-dev

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure .env file
nano .env

# Recommended production settings:
APP_NAME=EduPro
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com/api

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=edupro
DB_USERNAME=edupro_user
DB_PASSWORD=your_secure_password

# Or for SQLite:
DB_CONNECTION=sqlite
# DB_HOST=null
# DB_PORT=null
# DB_DATABASE=null
# DB_USERNAME=null
# DB_PASSWORD=null

LOG_CHANNEL=stack
LOG_LEVEL=error

# Run migrations
php artisan migrate --force

# Seed initial data (optional)
php artisan db:seed --force

# Optimize Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set permissions
sudo chown -R www-data:www-data /var/www/edupro/backend
sudo chmod -R 755 /var/www/edupro/backend/storage
sudo chmod -R 755 /var/www/edupro/backend/bootstrap/cache
```

---

## 4. Frontend Setup (Vue.js)

```bash
cd /var/www/edupro/frontend

# Install dependencies
npm install

# Build for production
npm run build

# The built files will be in dist/ folder
```

---

## 5. Nginx Configuration

### Create Nginx Server Block

```bash
sudo nano /etc/nginx/sites-available/edupro
```

### Configuration for Separate Backend/Frontend

```nginx
# Frontend (Vue.js)
server {
    listen 80;
    server_name your-domain.com www.your-domain.com;
    root /var/www/edupro/frontend/dist;
    index index.html;

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css text/xml text/javascript application/x-javascript application/xml+rss application/json application/javascript;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;

    # Serve static files directly
    location / {
        try_files $uri $uri/ /index.html;
    }

    # Cache static assets
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # API proxy to Laravel backend
    location /api/ {
        proxy_pass http://127.0.0.1:8000/;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_http_version 1.1;
        proxy_set_header Connection "";
        proxy_buffering off;
        proxy_connect_timeout 300s;
        proxy_send_timeout 300s;
        proxy_read_timeout 300s;
    }

    # Rate limiting
    location /api/auth/ {
        proxy_pass http://127.0.0.1:8000/api/auth/;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        limit_req zone=api burst=10 nodelay;
    }
}

# Backend API (Laravel) - Separate subdomain option
# server {
#     listen 80;
#     server_name api.your-domain.com;
#     root /var/www/edupro/backend/public;
#
#     location / {
#         try_files $uri $uri/ /index.php?$query_string;
#     }
#
#     location ~ \.php$ {
#         fastcgi_pass unix:/run/php/php8.2-fpm.sock;
#         fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
#         include fastcgi_params;
#         fastcgi_hide_header X-Powered-By;
#     }
#
#     location ~ /\.ht {
#         deny all;
#     }
#
#     location ~ /\.git {
#         deny all;
#     }
# }
```

### Enable Site
```bash
sudo ln -s /etc/nginx/sites-available/edupro /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

---

## 6. PHP-FPM Configuration (If using PHP-FPM directly)

```bash
sudo nano /etc/php/8.2/fpm/pool.d/www.conf

# Update these settings:
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.max_requests = 500

php_admin_value[upload_max_filesize] = 20M
php_admin_value[post_max_size] = 20M
php_admin_value[max_execution_time] = 300
php_admin_value[memory_limit] = 256M

sudo systemctl restart php8.2-fpm
```

---

## 7. Laravel Queue & Scheduler (Optional but Recommended)

### Setup Supervisor for Queue Worker

```bash
sudo apt install supervisor -y

sudo nano /etc/supervisor/conf.d/edupro-worker.conf
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
stdout_logfile=/var/www/edupro/backend/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start edupro-worker:*
```

### Setup Cron for Scheduler

```bash
sudo crontab -e

# Add this line:
* * * * * cd /var/www/edupro/backend && php artisan schedule:run >> /dev/null 2>&1
```

---

## 8. SSL Configuration (Let's Encrypt)

```bash
# Install Certbot
sudo apt install certbot python3-certbot-nginx -y

# Get SSL certificate
sudo certbot --nginx -d your-domain.com -d www.your-domain.com

# Auto-renewal is configured automatically
# Test renewal:
sudo certbot renew --dry-run
```

---

## 9. Production Optimizations

### Laravel Optimizations
```bash
cd /var/www/edupro/backend

# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Rebuild optimized caches
php artisan optimize
```

### Database Optimization
```bash
# For MySQL/MariaDB
mysql -u root -p

# Run these SQL commands:
CREATE DATABASE edupro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'edupro_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON edupro.* TO 'edupro_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### File Permissions
```bash
sudo chown -R www-data:www-data /var/www/edupro/backend/storage
sudo chown -R www-data:www-data /var/www/edupro/backend/bootstrap/cache
sudo chmod -R 775 /var/www/edupro/backend/storage
sudo chmod -R 775 /var/www/edupro/backend/bootstrap/cache
```

---

## 10. Environment Variables (.env)

### Production .env Settings
```env
APP_NAME="EduPro SMS"
APP_ENV=production
APP_KEY=base64:your-generated-key
APP_DEBUG=false
APP_URL=https://your-domain.com

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=edupro
DB_USERNAME=edupro_user
DB_PASSWORD=your_secure_password

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true

CACHE_STORE=database
QUEUE_CONNECTION=database

MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-email
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME="${APP_NAME}"

SANCTUM_STATEFUL_DOMAINS=your-domain.com,www.your-domain.com
```

---

## 11. Deployment Script

Create a deployment script for easy updates:

```bash
nano /var/www/edupro/deploy.sh
```

```bash
#!/bin/bash

echo "🚀 Starting EduPro Deployment..."

# Backend
echo "📦 Updating Backend..."
cd /var/www/edupro/backend
git pull origin main
composer install --optimize-autoloader --no-dev
php artisan migrate --force
php artisan optimize
sudo chown -R www-data:www-data storage bootstrap/cache
sudo supervisorctl restart edupro-worker:*

# Frontend
echo "🎨 Building Frontend..."
cd /var/www/edupro/frontend
git pull origin main
npm install
npm run build

echo "✅ Deployment Complete!"
```

```bash
chmod +x /var/www/edupro/deploy.sh
```

---

## 12. Monitoring & Maintenance

### Check Logs
```bash
# Laravel logs
tail -f /var/www/edupro/backend/storage/logs/laravel.log

# Nginx logs
tail -f /var/log/nginx/access.log
tail -f /var/log/nginx/error.log

# PHP-FPM logs
tail -f /var/log/php8.2-fpm.log
```

### Backup Script
```bash
nano /var/www/edupro/backup.sh
```

```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backups/edupro"

# Create backup directory
mkdir -p $BACKUP_DIR

# Backup database
mysqldump -u edupro_user -p'password' edupro > $BACKUP_DIR/db_$DATE.sql

# Backup files
tar -czf $BACKUP_DIR/files_$DATE.tar.gz /var/www/edupro

# Delete old backups (keep 7 days)
find $BACKUP_DIR -name "*.sql" -mtime +7 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +7 -delete

echo "Backup completed: $DATE"
```

```bash
# Add to crontab for daily backups
0 2 * * * /var/www/edupro/backup.sh >> /var/log/edupro_backup.log 2>&1
```

---

## 13. Troubleshooting

### Common Issues

**502 Bad Gateway**
```bash
sudo systemctl status php8.2-fpm
sudo systemctl restart php8.2-fpm
```

**403 Forbidden**
```bash
sudo chown -R www-data:www-data /var/www/edupro/backend
sudo chmod -R 755 /var/www/edupro/backend/public
```

**Permission Denied**
```bash
sudo chmod -R 775 /var/www/edupro/backend/storage
sudo chmod -R 775 /var/www/edupro/backend/bootstrap/cache
```

**API Returns 500**
```bash
cd /var/www/edupro/backend
php artisan config:clear
php artisan cache:clear
tail -f storage/logs/laravel.log
```

---

## Quick Deploy Checklist

- [ ] Server updated and secured
- [ ] PHP 8.2+ installed with extensions
- [ ] Composer and Node.js installed
- [ ] Application files uploaded
- [ ] Backend dependencies installed
- [ ] Frontend built for production
- [ ] Database configured and migrated
- [ ] Nginx configured and enabled
- [ ] SSL certificate installed
- [ ] File permissions set correctly
- [ ] Queue worker running (if using queues)
- [ ] Cron job set for scheduler
- [ ] Backups configured
- [ ] Monitoring in place

---

## Support

For issues or questions:
- Check Laravel logs: `storage/logs/laravel.log`
- Check Nginx error logs: `/var/log/nginx/error.log`
- Review PHP-FPM logs: `/var/log/php8.2-fpm.log`
