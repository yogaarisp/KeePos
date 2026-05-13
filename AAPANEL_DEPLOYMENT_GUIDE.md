# 🚀 aaPanel Deployment Guide - Kee POS Premium

## 📋 **Prerequisites**

Pastikan aaPanel sudah terinstall:
- ✅ **PHP 8.1+** (dengan extensions: mysqli, pdo_mysql, mbstring, xml, ctype, json, tokenizer, openssl, zip, curl, gd)
- ✅ **MySQL 8.0+** atau MariaDB 10.4+
- ✅ **Nginx** atau Apache
- ✅ **Composer** (PHP package manager)
- ✅ **Node.js 18+** dan npm (untuk frontend)
- ✅ **Git** (untuk clone repository)

## 🔧 **Step 1: Setup Server Environment**

### **1.1 Install Git di aaPanel**
```bash
# SSH ke server aaPanel
ssh root@your-server-ip

# Install Git (jika belum ada)
yum install git -y          # CentOS/RHEL
# atau
apt install git -y          # Ubuntu/Debian

# Verify Git installation
git --version
```

### **1.2 Install Composer**
```bash
# Download dan install Composer
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer

# Verify Composer
composer --version
```

### **1.3 Install Node.js & npm**
```bash
# Install Node.js 18 LTS
curl -fsSL https://rpm.nodesource.com/setup_18.x | bash -
yum install nodejs -y

# Verify installation
node --version
npm --version
```

## 🌐 **Step 2: Create Website di aaPanel**

### **2.1 Buat Website Baru**
1. Login ke **aaPanel Dashboard**
2. Go to **Website** → **Add Site**
3. Fill form:
   - **Domain**: `your-domain.com` (atau subdomain)
   - **Document Root**: `/www/wwwroot/your-domain.com`
   - **PHP Version**: `8.1` atau `8.2`
   - **Database**: Create new MySQL database
4. Click **Submit**

### **2.2 Setup SSL Certificate (Optional tapi Recommended)**
1. Go to **Website** → **SSL**
2. Choose **Let's Encrypt** (free)
3. Add domain dan click **Apply**
4. Enable **Force HTTPS**

## 📥 **Step 3: Clone Repository dari GitHub**

### **3.1 SSH ke Server dan Navigate ke Website Directory**
```bash
# SSH ke server
ssh root@your-server-ip

# Navigate ke website directory
cd /www/wwwroot/your-domain.com

# Backup existing files (jika ada)
mv * backup_$(date +%Y%m%d) 2>/dev/null || true

# Clone repository dari GitHub
git clone https://github.com/yogaarisp/KeePos.git .

# Verify files
ls -la
```

### **3.2 Set Proper Permissions**
```bash
# Set ownership ke www user
chown -R www:www /www/wwwroot/your-domain.com

# Set proper permissions
find /www/wwwroot/your-domain.com -type f -exec chmod 644 {} \;
find /www/wwwroot/your-domain.com -type d -exec chmod 755 {} \;

# Set writable permissions untuk Laravel storage
chmod -R 775 backend/storage
chmod -R 775 backend/bootstrap/cache
```

## ⚙️ **Step 4: Setup Backend (Laravel)**

### **4.1 Install PHP Dependencies**
```bash
cd /www/wwwroot/your-domain.com/backend

# Install Composer dependencies
composer install --no-dev --optimize-autoloader

# Verify installation
composer show | head -10
```

### **4.2 Setup Environment Configuration**
```bash
# Copy environment file
cp .env.example .env

# Edit .env file
nano .env
```

**Edit .env file dengan konfigurasi yang benar:**
```env
APP_NAME="Kee POS Premium"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://your-domain.com

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

# Midtrans Configuration (untuk payment)
MIDTRANS_SERVER_KEY=your_midtrans_server_key
MIDTRANS_CLIENT_KEY=your_midtrans_client_key
MIDTRANS_IS_PRODUCTION=true

# Mail Configuration (untuk OTP/notifications)
MAIL_MAILER=smtp
MAIL_HOST=your_smtp_host
MAIL_PORT=587
MAIL_USERNAME=your_email@domain.com
MAIL_PASSWORD=your_email_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME="Kee POS Premium"
```

### **4.3 Generate Application Key dan Setup Database**
```bash
# Generate Laravel application key
php artisan key:generate

# Run database migrations
php artisan migrate --force

# Seed pricing data
php artisan db:seed --class=PricingSeeder --force

# Create superadmin user (optional)
php artisan db:seed --class=SuperAdminSeeder --force

# Clear dan cache configuration
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 🎨 **Step 5: Setup Frontend (Vue.js)**

### **5.1 Install Node Dependencies dan Build**
```bash
cd /www/wwwroot/your-domain.com/frontend

# Install npm dependencies
npm install

# Build for production
npm run build

# Verify build
ls -la dist/
```

### **5.2 Configure Frontend Environment**
```bash
# Edit frontend environment
nano .env
```

**Edit frontend .env:**
```env
VITE_API_URL=https://your-domain.com/api
VITE_APP_NAME="Kee POS Premium"
VITE_APP_URL=https://your-domain.com
```

## 🌐 **Step 6: Configure Web Server (Nginx)**

### **6.1 Setup Nginx Configuration**
```bash
# Edit Nginx config untuk website
nano /www/server/panel/vhost/nginx/your-domain.com.conf
```

**Replace dengan konfigurasi ini:**
```nginx
server {
    listen 80;
    listen 443 ssl http2;
    server_name your-domain.com www.your-domain.com;
    
    # SSL Configuration (jika menggunakan SSL)
    ssl_certificate /www/server/panel/vhost/cert/your-domain.com/fullchain.pem;
    ssl_certificate_key /www/server/panel/vhost/cert/your-domain.com/privkey.pem;
    
    root /www/wwwroot/your-domain.com/frontend/dist;
    index index.html index.php;
    
    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;
    
    # API routes (Laravel backend)
    location /api {
        alias /www/wwwroot/your-domain.com/backend/public;
        try_files $uri $uri/ @api;
        
        location ~ \.php$ {
            include fastcgi_params;
            fastcgi_param SCRIPT_FILENAME /www/wwwroot/your-domain.com/backend/public/index.php;
            fastcgi_pass unix:/tmp/php-cgi-81.sock;
            fastcgi_index index.php;
        }
    }
    
    location @api {
        rewrite /api/(.*)$ /api/index.php?/$1 last;
    }
    
    # Frontend routes (Vue.js SPA)
    location / {
        try_files $uri $uri/ /index.html;
    }
    
    # Static assets
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
    
    # Security - Hide sensitive files
    location ~ /\. {
        deny all;
    }
    
    location ~ /(vendor|storage|bootstrap|database|tests|artisan) {
        deny all;
    }
    
    # PHP configuration
    location ~ \.php$ {
        return 404;
    }
}
```

### **6.2 Test dan Reload Nginx**
```bash
# Test Nginx configuration
nginx -t

# Reload Nginx
systemctl reload nginx
# atau
service nginx reload
```

## 🔧 **Step 7: Setup Cron Jobs (Optional)**

### **7.1 Setup Laravel Scheduler**
```bash
# Edit crontab
crontab -e

# Add Laravel scheduler
* * * * * cd /www/wwwroot/your-domain.com/backend && php artisan schedule:run >> /dev/null 2>&1
```

## ✅ **Step 8: Verification & Testing**

### **8.1 Test Backend API**
```bash
# Test API endpoint
curl -X GET "https://your-domain.com/api/subscriptions/plans/public" -H "Accept: application/json"

# Expected response:
# {"success":true,"data":{"free":{"price":0},"basic":{"price":99000},"pro":{"price":249000}}}
```

### **8.2 Test Frontend**
1. Open browser: `https://your-domain.com`
2. Should see Kee POS Premium landing page
3. Test login/register functionality
4. Test pricing page: `https://your-domain.com/pricing`

### **8.3 Test Database Connection**
```bash
cd /www/wwwroot/your-domain.com/backend

# Test database connection
php artisan tinker
>>> DB::connection()->getPdo();
>>> exit
```

## 🔄 **Step 9: Setup Auto-Deployment (Optional)**

### **9.1 Create Deployment Script**
```bash
# Create deployment script
nano /www/wwwroot/your-domain.com/deploy.sh
```

**Script content:**
```bash
#!/bin/bash
cd /www/wwwroot/your-domain.com

echo "🚀 Starting deployment..."

# Pull latest changes
git pull origin main

# Backend updates
cd backend
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan db:seed --class=PricingSeeder --force
php artisan config:clear
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Frontend updates
cd ../frontend
npm install
npm run build

# Set permissions
cd ..
chown -R www:www .
chmod -R 775 backend/storage
chmod -R 775 backend/bootstrap/cache

echo "✅ Deployment completed!"
```

```bash
# Make script executable
chmod +x /www/wwwroot/your-domain.com/deploy.sh
```

### **9.2 Future Updates**
```bash
# Untuk update selanjutnya, jalankan:
/www/wwwroot/your-domain.com/deploy.sh
```

## 🛠️ **Troubleshooting**

### **Common Issues:**

#### **1. Permission Denied**
```bash
chown -R www:www /www/wwwroot/your-domain.com
chmod -R 775 backend/storage backend/bootstrap/cache
```

#### **2. Database Connection Error**
- Check database credentials di `.env`
- Verify database exists di aaPanel → Database
- Test connection: `php artisan tinker` → `DB::connection()->getPdo();`

#### **3. 500 Internal Server Error**
```bash
# Check Laravel logs
tail -f backend/storage/logs/laravel.log

# Check Nginx error logs
tail -f /www/wwwroot/your-domain.com/logs/error.log
```

#### **4. API Not Working**
- Verify Nginx configuration
- Check PHP-FPM is running: `systemctl status php-fpm`
- Test API directly: `curl https://your-domain.com/api/`

#### **5. Frontend Not Loading**
- Check if `frontend/dist/` exists dan berisi files
- Run `npm run build` again
- Check Nginx serves static files correctly

## 📊 **Performance Optimization**

### **1. Enable OPcache**
```bash
# Edit PHP configuration
nano /www/server/php/81/etc/php.ini

# Add/uncomment:
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
opcache.revalidate_freq=2
opcache.fast_shutdown=1
```

### **2. Setup Redis (Optional)**
```bash
# Install Redis
yum install redis -y
systemctl enable redis
systemctl start redis

# Update Laravel .env
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

## 🔐 **Security Checklist**

- ✅ SSL Certificate installed dan force HTTPS enabled
- ✅ Hide sensitive directories di Nginx config
- ✅ Set proper file permissions (644 files, 755 directories)
- ✅ Database credentials secure
- ✅ APP_DEBUG=false di production
- ✅ Regular backups enabled
- ✅ Firewall configured (only 80, 443, 22 open)

## 📞 **Support**

Jika ada masalah:
1. Check logs: `backend/storage/logs/laravel.log`
2. Check Nginx logs: `/www/wwwroot/your-domain.com/logs/`
3. Test API endpoints dengan curl
4. Verify database connection
5. Check file permissions

---

**🎉 Selamat! Kee POS Premium sudah berhasil di-deploy ke aaPanel!**

Visit: `https://your-domain.com` untuk melihat hasilnya.