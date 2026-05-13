# Pricing Implementation - Kee POS Premium

## Overview
Sistem pricing telah diimplementasikan dengan benar sesuai dengan screenshot yang diberikan. Pricing menggunakan struktur 3-tier dengan fitur yang jelas dan batasan yang sesuai.

## Pricing Structure

### FREE Plan (Rp 0/bln)
- **Target**: Cocok untuk UMKM baru/mulai
- **Fitur**:
  - 1 Akun (Owner)
  - Kasir POS Desktop
  - Laporan Harian
- **Batasan**:
  - Max Products: 10
  - Max Users: 1
  - Max Tables: 3
  - Max Warehouse Items: 15
  - Max Kitchen Items: 0 (tidak ada akses kitchen)

### BASIC Plan (Rp 99,000/bln) - POPULER
- **Target**: Untuk bisnis yang berkembang
- **Fitur**:
  - 2 Akun (Owner + Kasir)
  - Pengaturan Stok Gudang
  - Export Excel Laporan
  - Google Sheets Sync
- **Batasan**:
  - Max Products: 100
  - Max Users: 2
  - Max Tables: 20
  - Max Warehouse Items: 100
  - Max Kitchen Items: 0 (tidak ada akses kitchen)

### PRO Plan (Rp 249,000/bln)
- **Target**: Solusi lengkap bisnis kuliner
- **Fitur**:
  - Akun Tanpa Batas
  - Resep & Stok Dapur
  - Inventory Report
  - Support Prioritas
- **Batasan**:
  - Max Products: Unlimited (-1)
  - Max Users: Unlimited (-1)
  - Max Tables: Unlimited (-1)
  - Max Warehouse Items: Unlimited (-1)
  - Max Kitchen Items: Unlimited (-1)

## Implementation Details

### 1. Database Storage
Pricing disimpan di tabel `platform_settings` dengan keys:
- `plan_basic_price`: 99000
- `plan_pro_price`: 249000
- `plan_free_features`: JSON array fitur FREE
- `plan_basic_features`: JSON array fitur BASIC
- `plan_pro_features`: JSON array fitur PRO

### 2. Models & Services
- **PlatformSetting**: Model untuk menyimpan konfigurasi pricing
- **PlanService**: Service untuk validasi batasan dan fitur plan
- **Subscription**: Model untuk tracking subscription aktif
- **SubscriptionInvoice**: Model untuk invoice pembayaran

### 3. Controllers
- **SubscriptionController**: Handle pricing API, checkout, webhook
- **TenantController**: Admin dashboard dengan revenue calculation
- **SettingController**: Konfigurasi SaaS untuk superadmin

### 4. Middleware
- **CheckPlan**: Validasi hierarki plan (free < basic < pro)
- **CheckSubscription**: Validasi subscription aktif dan expiry

### 5. API Endpoints

#### Public Endpoints
- `GET /api/subscriptions/plans/public` - Get pricing plans (no auth)

#### Authenticated Endpoints
- `GET /api/subscriptions/plans` - Get pricing plans
- `GET /api/subscriptions/status` - Get current subscription status
- `POST /api/subscriptions/checkout` - Create payment invoice
- `GET /api/subscriptions/invoices` - Get payment history
- `POST /api/subscriptions/webhook` - Midtrans payment webhook

### 6. Feature Enforcement

#### Route Protection
```php
// Warehouse management - requires BASIC
Route::middleware('plan:basic')->group(function () {
    Route::get('/warehouse', [WarehouseController::class, 'index']);
    // ...
});

// Kitchen & Recipe management - requires PRO
Route::middleware('plan:pro')->group(function () {
    Route::get('/kitchen', [KitchenController::class, 'index']);
    Route::get('/recipes', [RecipeController::class, 'index']);
    // ...
});
```

#### Resource Limits
```php
// Before creating resources
if (!PlanService::canAddProduct($tenant)) {
    return response()->json(['error' => 'Product limit exceeded'], 403);
}

if (!PlanService::canAddUser($tenant)) {
    return response()->json(['error' => 'User limit exceeded'], 403);
}
```

### 7. Payment Integration
- **Gateway**: Midtrans Snap
- **Webhook**: Automatic subscription activation
- **Invoice**: Tracking pembayaran dan renewal

### 8. Admin Features
- **Dashboard**: Revenue calculation, tenant statistics
- **Tenant Management**: Manual plan assignment
- **Configuration**: Pricing adjustment via admin panel

## Testing

### Test Pricing API
```bash
# Public endpoint (no auth required)
curl http://localhost:8000/api/subscriptions/plans/public

# Test page
http://localhost:8000/test-pricing.html
```

### Seeder
```bash
php artisan db:seed --class=PricingSeeder
```

## Configuration Files

### Seeder: `database/seeders/PricingSeeder.php`
- Mengisi data pricing yang benar
- Konfigurasi fitur per plan
- Setup default trial period

### Service: `app/Services/PlanService.php`
- Definisi plan dan batasan
- Validasi fitur dan limits
- Helper methods untuk checking

### Test Page: `public/test-pricing.html`
- Visual testing pricing display
- API response debugging
- Frontend integration example

## Key Features Restored

✅ **Correct Pricing**: FREE (Rp 0), BASIC (Rp 99rb), PRO (Rp 249rb)
✅ **Feature Lists**: Sesuai dengan screenshot yang diberikan
✅ **Plan Hierarchy**: Proper enforcement dengan middleware
✅ **Resource Limits**: Validasi batasan per plan
✅ **Payment Integration**: Midtrans untuk subscription
✅ **Admin Dashboard**: Revenue tracking dan tenant management
✅ **Public API**: Endpoint untuk frontend pricing display
✅ **Database Storage**: Configurable pricing via platform_settings

## Next Steps

1. **Frontend Integration**: Implement pricing page di frontend
2. **Trial Management**: Auto-create trial period untuk new tenants
3. **Renewal Notifications**: Email reminder sebelum expiry
4. **Usage Analytics**: Track feature usage per plan
5. **Discount System**: Coupon codes dan promotional pricing