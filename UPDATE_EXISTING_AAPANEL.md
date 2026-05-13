# 🔄 Update Existing aaPanel - Kee POS Premium

## 📋 **Untuk Project yang Sudah Ada di aaPanel**

Jika project Kee POS sudah running di aaPanel dan Anda hanya ingin update dengan pricing system yang baru, ikuti langkah berikut:

## 🚀 **Method 1: Quick Update (Recommended)**

### **Step 1: SSH ke Server**
```bash
ssh root@your-server-ip
cd /www/wwwroot/your-domain.com
```

### **Step 2: Backup Current Version**
```bash
# Backup current version
cp -r . ../backup_$(date +%Y%m%d_%H%M%S)
```

### **Step 3: Pull Latest Changes**
```bash
# Pull latest updates from GitHub
git pull origin main

# Jika ada conflict atau error, force pull:
git fetch origin
git reset --hard origin/main
```

### **Step 4: Update Backend**
```bash
cd backend

# Update PHP dependencies
composer install --no-dev --optimize-autoloader

# Run new migrations (pricing system)
php artisan migrate --force

# Seed pricing data (PENTING!)
php artisan db:seed --class=PricingSeeder --force

# Clear and cache
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### **Step 5: Update Frontend**
```bash
cd ../frontend

# Update Node dependencies
npm install

# Rebuild frontend
npm run build
```

### **Step 6: Fix Permissions**
```bash
cd ..
chown -R www:www .
chmod -R 775 backend/storage backend/bootstrap/cache
```

### **Step 7: Test Update**
```bash
# Test pricing API
curl https://your-domain.com/api/subscriptions/plans/public

# Expected response:
# {"success":true,"data":{"free":{"price":0},"basic":{"price":99000},"pro":{"price":249000}}}
```

## 🚀 **Method 2: One-Command Update**

Buat script update otomatis:

```bash
# SSH ke server
ssh root@your-server-ip
cd /www/wwwroot/your-domain.com

# Buat script update
cat > update.sh << 'EOF'
#!/bin/bash
set -e

echo "🔄 Starting Kee POS Premium update..."

# Backup current version
echo "📦 Creating backup..."
cp -r . ../backup_$(date +%Y%m%d_%H%M%S)

# Pull latest changes
echo "📥 Pulling latest changes..."
git pull origin main || (git fetch origin && git reset --hard origin/main)

# Update backend
echo "⚙️ Updating backend..."
cd backend
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan db:seed --class=PricingSeeder --force
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Update frontend
echo "🎨 Updating frontend..."
cd ../frontend
npm install
npm run build

# Fix permissions
echo "🔐 Fixing permissions..."
cd ..
chown -R www:www .
chmod -R 775 backend/storage backend/bootstrap/cache

echo "✅ Update completed successfully!"
echo "🌐 Test your website: https://$(basename $(pwd))"
EOF

# Make executable and run
chmod +x update.sh
./update.sh
```

## 🔍 **Method 3: Manual File Update (Jika Git Bermasalah)**

Jika ada masalah dengan git, update manual:

### **Step 1: Download Files Terbaru**
```bash
cd /tmp
wget https://github.com/yogaarisp/KeePos/archive/refs/heads/main.zip
unzip main.zip
```

### **Step 2: Copy Files yang Diupdate**
```bash
# Copy updated files
cp -r KeePos-main/backend/app/Services/PlanService.php /www/wwwroot/your-domain.com/backend/app/Services/
cp -r KeePos-main/backend/app/Http/Controllers/Api/SubscriptionController.php /www/wwwroot/your-domain.com/backend/app/Http/Controllers/Api/
cp -r KeePos-main/backend/app/Http/Controllers/Api/Admin/TenantController.php /www/wwwroot/your-domain.com/backend/app/Http/Controllers/Api/Admin/
cp -r KeePos-main/backend/app/Http/Controllers/Api/ReportController.php /www/wwwroot/your-domain.com/backend/app/Http/Controllers/Api/
cp -r KeePos-main/backend/routes/api.php /www/wwwroot/your-domain.com/backend/routes/
cp -r KeePos-main/backend/database/seeders/PricingSeeder.php /www/wwwroot/your-domain.com/backend/database/seeders/

# Set permissions
chown -R www:www /www/wwwroot/your-domain.com
```

### **Step 3: Run Database Updates**
```bash
cd /www/wwwroot/your-domain.com/backend
php artisan db:seed --class=PricingSeeder --force
php artisan config:cache
php artisan route:cache
```

## ✅ **Verification Checklist**

### **1. Test Pricing API**
```bash
curl https://your-domain.com/api/subscriptions/plans/public
```

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "free": {
      "name": "FREE",
      "price": 0,
      "price_formatted": "Rp 0"
    },
    "basic": {
      "name": "BASIC", 
      "price": 99000,
      "price_formatted": "Rp 99.000",
      "popular": true
    },
    "pro": {
      "name": "PRO",
      "price": 249000,
      "price_formatted": "Rp 249.000"
    }
  }
}
```

### **2. Test Website**
- ✅ Buka: `https://your-domain.com`
- ✅ Test login/register
- ✅ Check pricing page
- ✅ Test plan restrictions

### **3. Check Database**
```bash
cd /www/wwwroot/your-domain.com/backend
php artisan tinker
>>> \App\Models\PlatformSetting::where('key', 'like', 'plan_%')->get();
>>> exit
```

**Expected Output:**
- `plan_basic_price`: 99000
- `plan_pro_price`: 249000
- `plan_free_features`: JSON array
- `plan_basic_features`: JSON array  
- `plan_pro_features`: JSON array

## 🛠️ **Troubleshooting**

### **Problem: Git Pull Error**
```bash
# Force reset to latest version
git fetch origin
git reset --hard origin/main
```

### **Problem: Composer Error**
```bash
# Clear composer cache
composer clear-cache
composer install --no-dev --optimize-autoloader
```

### **Problem: Permission Denied**
```bash
chown -R www:www /www/wwwroot/your-domain.com
chmod -R 775 /www/wwwroot/your-domain.com/backend/storage
chmod -R 775 /www/wwwroot/your-domain.com/backend/bootstrap/cache
```

### **Problem: Database Error**
```bash
# Check database connection
cd /www/wwwroot/your-domain.com/backend
php artisan tinker
>>> DB::connection()->getPdo();
```

### **Problem: API Returns Old Pricing**
```bash
# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Re-seed pricing data
php artisan db:seed --class=PricingSeeder --force

# Cache again
php artisan config:cache
php artisan route:cache
```

## 📊 **What's New in This Update**

### **✅ Pricing System Fixed:**
- FREE Plan: Rp 0/bln (no warehouse access)
- BASIC Plan: Rp 99,000/bln (was 149,000)
- PRO Plan: Rp 249,000/bln (was 299,000)

### **✅ Feature Enforcement:**
- FREE: Only POS + basic reports
- BASIC: + Warehouse + Export Excel + Google Sheets
- PRO: + Kitchen + Recipes + Unlimited everything

### **✅ New API Endpoints:**
- `/api/subscriptions/plans/public` - Public pricing
- `/api/subscriptions/status` - Current subscription status
- `/api/reports/export-excel` - Excel export (BASIC+)

### **✅ Security Improvements:**
- Google Cloud credentials removed
- Better .gitignore rules
- Enhanced middleware protection

## 🔄 **Future Updates**

Untuk update selanjutnya, cukup jalankan:
```bash
cd /www/wwwroot/your-domain.com
./update.sh
```

---

**🎉 Update Completed!** 

Your Kee POS Premium now has the correct pricing system:
- **FREE**: Rp 0
- **BASIC**: Rp 99,000 (POPULER) 
- **PRO**: Rp 249,000