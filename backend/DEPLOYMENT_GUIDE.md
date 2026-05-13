# Deployment Guide - Update ke aaPanel

## Files yang Perlu Diupdate

### 1. **Core Files yang Diubah**

#### Backend Files:
```
backend/app/Services/PlanService.php
backend/app/Http/Controllers/Api/SubscriptionController.php
backend/app/Http/Controllers/Api/Admin/TenantController.php
backend/app/Http/Controllers/Api/ReportController.php
backend/routes/api.php
backend/database/seeders/PricingSeeder.php
```

#### New Files:
```
backend/database/seeders/PricingSeeder.php
backend/public/test-pricing.html
backend/PRICING_IMPLEMENTATION.md
backend/DEPLOYMENT_GUIDE.md
```

### 2. **Database Updates**

#### Seeder yang Perlu Dijalankan:
```bash
php artisan db:seed --class=PricingSeeder --force
```

#### Data yang Akan Diupdate:
- `platform_settings` table dengan pricing yang benar
- Plan features yang sesuai dengan screenshot
- Limits yang tepat per plan

### 3. **Cara Update ke aaPanel**

#### Step 1: Backup Database
```bash
# Di aaPanel, buat backup database dulu
mysqldump -u username -p database_name > backup_$(date +%Y%m%d_%H%M%S).sql
```

#### Step 2: Upload Files
1. **Via File Manager aaPanel:**
   - Upload semua files yang diubah ke direktori yang sesuai
   - Pastikan permissions 644 untuk files PHP
   - Pastikan permissions 755 untuk directories

2. **Via Git (Recommended):**
   ```bash
   cd /www/wwwroot/your-domain
   git pull origin main
   # atau
   git fetch origin
   git reset --hard origin/main
   ```

#### Step 3: Update Dependencies & Cache
```bash
cd /www/wwwroot/your-domain/backend

# Update composer dependencies (jika ada)
composer install --no-dev --optimize-autoloader

# Clear cache
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

#### Step 4: Run Database Seeder
```bash
cd /www/wwwroot/your-domain/backend
php artisan db:seed --class=PricingSeeder --force
```

#### Step 5: Verify Deployment
```bash
# Test API endpoint
curl -X GET "https://your-domain.com/api/subscriptions/plans/public" -H "Accept: application/json"

# Check test page
# Visit: https://your-domain.com/test-pricing.html
```

### 4. **Environment Configuration**

#### Pastikan .env sudah benar:
```env
# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password

# App
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Midtrans (untuk payment)
MIDTRANS_SERVER_KEY=your_server_key
MIDTRANS_CLIENT_KEY=your_client_key
MIDTRANS_IS_PRODUCTION=true
```

### 5. **Verification Checklist**

#### ✅ **API Endpoints Test:**
```bash
# Test pricing API
curl https://your-domain.com/api/subscriptions/plans/public

# Expected response:
{
  "success": true,
  "data": {
    "free": {"price": 0, "name": "FREE"},
    "basic": {"price": 99000, "name": "BASIC"},
    "pro": {"price": 249000, "name": "PRO"}
  }
}
```

#### ✅ **Feature Enforcement Test:**
1. **FREE Plan:**
   - ❌ Tidak bisa akses `/api/warehouse/*` (401/403)
   - ❌ Tidak bisa akses `/api/kitchen/*` (401/403)
   - ✅ Bisa akses `/api/pos/*`
   - ✅ Bisa akses `/api/reports/sales`

2. **BASIC Plan:**
   - ✅ Bisa akses `/api/warehouse/*`
   - ✅ Bisa akses `/api/reports/export-excel`
   - ✅ Bisa akses `/api/settings/sync-google-sheet`
   - ❌ Tidak bisa akses `/api/kitchen/*` (401/403)

3. **PRO Plan:**
   - ✅ Bisa akses semua endpoints
   - ✅ Unlimited resources

#### ✅ **Database Verification:**
```sql
-- Check platform settings
SELECT * FROM platform_settings WHERE `key` LIKE 'plan_%';

-- Expected results:
-- plan_basic_price: 99000
-- plan_pro_price: 249000
-- plan_free_features: ["1 Akun (Owner)","Kasir POS Desktop","Laporan Harian"]
-- plan_basic_features: ["2 Akun (Owner + Kasir)","Pengaturan Stok Gudang","Export Excel Laporan","Google Sheets Sync"]
-- plan_pro_features: ["Akun Tanpa Batas","Resep & Stok Dapur","Inventory Report","Support Prioritas"]
```

### 6. **Troubleshooting**

#### Problem: API returns 500 error
```bash
# Check Laravel logs
tail -f /www/wwwroot/your-domain/backend/storage/logs/laravel.log

# Check PHP error logs
tail -f /var/log/php/error.log
```

#### Problem: Database connection error
```bash
# Test database connection
php artisan tinker
>>> DB::connection()->getPdo();
```

#### Problem: Permission denied
```bash
# Fix permissions
chown -R www-data:www-data /www/wwwroot/your-domain
chmod -R 755 /www/wwwroot/your-domain
chmod -R 644 /www/wwwroot/your-domain/backend/storage
chmod -R 644 /www/wwwroot/your-domain/backend/bootstrap/cache
```

### 7. **Post-Deployment Tasks**

#### Update Frontend (jika ada):
1. Update pricing display di frontend
2. Update feature checks di frontend
3. Test subscription flow end-to-end

#### Monitor & Analytics:
1. Setup monitoring untuk API endpoints
2. Track subscription conversions
3. Monitor plan usage dan limits

#### Documentation:
1. Update API documentation
2. Update user guides
3. Update admin documentation

### 8. **Rollback Plan**

Jika ada masalah setelah deployment:

```bash
# 1. Restore database backup
mysql -u username -p database_name < backup_YYYYMMDD_HHMMSS.sql

# 2. Revert code changes
git reset --hard HEAD~1

# 3. Clear cache
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# 4. Restore previous state
php artisan config:cache
php artisan route:cache
```

## Summary

**Files to Update:**
- 6 core PHP files
- 4 new files
- 1 database seeder

**Commands to Run:**
1. Upload files to aaPanel
2. `composer install --no-dev --optimize-autoloader`
3. `php artisan cache:clear && php artisan config:cache`
4. `php artisan db:seed --class=PricingSeeder --force`
5. Test API endpoints

**Expected Result:**
- ✅ Pricing: FREE (Rp 0), BASIC (Rp 99rb), PRO (Rp 249rb)
- ✅ Feature enforcement sesuai plan
- ✅ Resource limits working
- ✅ Export Excel untuk BASIC+
- ✅ Google Sheets Sync untuk BASIC+
- ✅ Kitchen Management untuk PRO only