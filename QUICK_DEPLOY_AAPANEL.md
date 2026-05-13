# 🚀 Quick Deploy ke aaPanel - Kee POS Premium

## 📋 **Cara Tercepat Deploy ke aaPanel**

### **Method 1: One-Command Deploy (Recommended)**

```bash
# SSH ke server aaPanel
ssh root@your-server-ip

# Download dan jalankan deployment script
curl -fsSL https://raw.githubusercontent.com/yogaarisp/KeePos/main/deploy-to-aapanel.sh -o deploy.sh
chmod +x deploy.sh
./deploy.sh your-domain.com
```

### **Method 2: Manual Step-by-Step**

#### **Step 1: Clone Repository**
```bash
# SSH ke server
ssh root@your-server-ip

# Navigate ke website directory (ganti your-domain.com)
cd /www/wwwroot/your-domain.com

# Clone repository
git clone https://github.com/yogaarisp/KeePos.git .
```

#### **Step 2: Setup Backend**
```bash
cd backend

# Install dependencies
composer install --no-dev --optimize-autoloader

# Setup environment
cp .env.example .env
php artisan key:generate

# Edit .env file (PENTING!)
nano .env
```

**Edit .env dengan data yang benar:**
```env
APP_URL=https://your-domain.com
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user  
DB_PASSWORD=your_database_password
```

#### **Step 3: Setup Database**
```bash
# Run migrations
php artisan migrate --force

# Seed pricing data
php artisan db:seed --class=PricingSeeder --force

# Cache configuration
php artisan config:cache
php artisan route:cache
```

#### **Step 4: Setup Frontend**
```bash
cd ../frontend

# Install dependencies
npm install

# Create environment file
echo "VITE_API_URL=https://your-domain.com/api" > .env

# Build for production
npm run build
```

#### **Step 5: Set Permissions**
```bash
cd ..
chown -R www:www .
chmod -R 775 backend/storage backend/bootstrap/cache
```

#### **Step 6: Configure Nginx**
Di aaPanel Dashboard:
1. **Website** → **Conf** → Edit Nginx config
2. Replace dengan config dari `AAPANEL_DEPLOYMENT_GUIDE.md`
3. **Save** dan **Reload**

## ✅ **Verification**

### **Test API:**
```bash
curl https://your-domain.com/api/subscriptions/plans/public
```

### **Test Website:**
Buka browser: `https://your-domain.com`

## 🔄 **Future Updates**

```bash
# SSH ke server
ssh root@your-server-ip

# Navigate ke website directory
cd /www/wwwroot/your-domain.com

# Pull latest changes
git pull origin main

# Update backend
cd backend
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache

# Update frontend
cd ../frontend
npm install
npm run build

# Fix permissions
cd ..
chown -R www:www .
```

## 🛠️ **Troubleshooting Cepat**

### **1. Database Error:**
```bash
# Check database connection
cd /www/wwwroot/your-domain.com/backend
php artisan tinker
>>> DB::connection()->getPdo();
```

### **2. Permission Error:**
```bash
chown -R www:www /www/wwwroot/your-domain.com
chmod -R 775 /www/wwwroot/your-domain.com/backend/storage
```

### **3. API Not Working:**
- Check Nginx config
- Restart PHP-FPM: `systemctl restart php-fpm`
- Check logs: `tail -f /www/wwwroot/your-domain.com/backend/storage/logs/laravel.log`

### **4. Frontend Not Loading:**
```bash
cd /www/wwwroot/your-domain.com/frontend
npm run build
```

## 📞 **Need Help?**

1. **Check logs:** `backend/storage/logs/laravel.log`
2. **Test API:** `curl https://your-domain.com/api/`
3. **Verify files:** `ls -la /www/wwwroot/your-domain.com/`
4. **Check permissions:** `ls -la backend/storage/`

---

**🎉 Done! Your Kee POS Premium is now live!**

Visit: `https://your-domain.com`