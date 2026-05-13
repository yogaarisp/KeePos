# 🔧 Solusi 401 Unauthorized Errors

## 📋 Ringkasan Masalah

Setelah perubahan struktur database (migrasi ke `platform_settings`, `tenant_profiles`, `tenant_settings`), user **duasrcbook@gmail.com** (tenant "Semoga Jaya") mengalami error 401 Unauthorized pada semua API endpoint:

```
❌ /api/settings → 401 Unauthorized
❌ /api/subscriptions/plans → 401 Unauthorized  
❌ /api/subscriptions/invoices → 401 Unauthorized
❌ /api/me → 401 Unauthorized
```

## 🔍 Diagnosis

Hasil pengecekan:
- ✅ User exists: duasrcbook@gmail.com (ID: 8, Tenant ID: 4)
- ✅ Tenant exists: Semoga Jaya (ID: 4)
- ✅ Token exists in database (ID: 76, created 18 minutes ago)
- ❌ Token **Last Used: Never** ← Ini masalahnya!
- ✅ API accessible (HTTP 200)
- ✅ Routes configured correctly
- ✅ Middleware configured correctly

**Root Cause:** Token di browser (localStorage) sudah **TIDAK VALID** atau **TIDAK COCOK** dengan token di database setelah migration.

## ✅ SOLUSI CEPAT (3 Langkah)

### 1️⃣ Clear Browser Storage

Buka browser, tekan **F12**, paste di Console:

```javascript
localStorage.clear()
sessionStorage.clear()
alert('Storage cleared! Redirecting to login...')
location.href = 'http://192.168.18.242:3000/login'
```

### 2️⃣ Login Ulang

Login dengan:
- Email: `duasrcbook@gmail.com`
- Password: (password Anda)

### 3️⃣ Verify (Optional)

Setelah login, paste di Console untuk verify:

```javascript
console.log('✅ Token:', localStorage.getItem('auth_token'))
console.log('✅ User:', JSON.parse(localStorage.getItem('user')))

// Test API
fetch('/api/settings', {
  headers: {
    'Authorization': 'Bearer ' + localStorage.getItem('auth_token'),
    'Accept': 'application/json'
  }
})
.then(r => r.json())
.then(data => {
  if (data.success) {
    console.log('✅ API Working!')
    console.log('Shop Name:', data.data.profile?.shop_name)
    console.log('Tenant:', data.data.tenant?.name)
  }
})
```

Expected output:
```
✅ API Working!
Shop Name: Semoga Jaya
Tenant: Semoga Jaya
```

## 🧪 Testing Tool

Kami sudah buat testing tool untuk diagnose masalah:

**Open in browser:**
```
http://192.168.18.242:8000/test-auth.html
```

Tool ini bisa:
- ✅ Check localStorage (token & user data)
- ✅ Clear storage dengan 1 klik
- ✅ Test API endpoints
- ✅ Login langsung dari tool
- ✅ Real-time diagnostic

## 📝 Diagnostic Scripts

Kami juga sudah buat PHP scripts untuk backend diagnosis:

### 1. Check User & Token
```bash
cd backend
php check_auth_token.php
```

Output:
```
✅ User: Semoga Jaya (ID: 8)
   Tenant: Semoga Jaya (ID: 4)
🎫 Latest Token:
   ID: 76
   Created: 2026-03-13 10:56:45
   Last Used: Never  ← Problem!
```

### 2. Test API Authentication
```bash
php test_api_auth.php
```

Akan show:
- User info
- Token info
- Sanctum config
- API accessibility
- Debugging steps
- Solution

### 3. List All Users
```bash
php list_users.php
```

## 🔍 Troubleshooting Lanjutan

### Masih 401 setelah login ulang?

**1. Check Network Tab:**
- DevTools (F12) → **Network** tab
- Refresh page
- Klik request `/api/settings`
- Tab **Headers** → **Request Headers**
- Harus ada: `Authorization: Bearer 77|abc...`

Jika tidak ada header Authorization:
- Check `frontend/src/api/index.js` interceptor
- Verify token di localStorage: `localStorage.getItem('auth_token')`

**2. Check Backend Logs:**
```bash
cd backend
tail -f storage/logs/laravel.log
```

Look for:
- `Unauthenticated` → Token invalid
- `SQLSTATE` → Database error
- `Tenant not found` → Tenant issue

**3. Clear Backend Cache:**
```bash
cd backend
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

**4. Regenerate Token Manually:**

Jika semua cara gagal, generate token baru:

```bash
cd backend
php artisan tinker
```

```php
$user = App\Models\User::where('email', 'duasrcbook@gmail.com')->first();
$token = $user->createToken('auth_token')->plainTextToken;
echo "New Token: " . $token . "\n";
exit
```

Copy token, lalu di browser Console:
```javascript
localStorage.setItem('auth_token', 'PASTE_TOKEN_HERE')
localStorage.setItem('user', JSON.stringify({
  id: 8,
  email: 'duasrcbook@gmail.com',
  full_name: 'Semoga Jaya',
  tenant_id: 4,
  tenant: {
    id: 4,
    name: 'Semoga Jaya',
    slug: 'semoga-jaya'
  }
}))
location.reload()
```

## 🎯 Why This Happened?

Setelah database migration:
1. ✅ Schema berubah (settings → 3 tables)
2. ✅ Data migrated successfully
3. ❌ Token di browser masih **token LAMA**
4. ❌ Token lama mungkin:
   - Expired
   - Hash mismatch
   - Session reset
   - Database connection reset

**Solution:** Clear storage + login ulang = Generate **token BARU** yang valid

## 📚 Files Created

Untuk troubleshooting, kami sudah buat:

1. **backend/FIX_401_ERRORS.md** - Panduan lengkap fix 401 errors
2. **backend/check_auth_token.php** - Check user & token status
3. **backend/test_api_auth.php** - Test API authentication
4. **backend/public/test-auth.html** - Web-based testing tool
5. **SOLUTION_401_ERRORS.md** - This file (summary)

## ✅ Expected Result

Setelah fix, user "Semoga Jaya" harus bisa:
- ✅ Login successfully
- ✅ Access `/api/settings` → Show "Semoga Jaya" profile
- ✅ Access `/api/subscriptions/plans` → Show plans
- ✅ Access `/api/me` → Show user info
- ✅ See correct shop name in Settings page
- ✅ See tenant logo (if uploaded)

## 🚀 Prevention

Untuk mencegah masalah ini di masa depan, tambahkan auto-logout on 401:

Edit `frontend/src/api/index.js`:

```javascript
api.interceptors.response.use(
  response => response,
  error => {
    if (error.response?.status === 401) {
      // Token expired or invalid
      console.warn('⚠️ Session expired. Redirecting to login...')
      localStorage.clear()
      window.location.href = '/login'
    }
    return Promise.reject(error)
  }
)
```

## 📞 Need Help?

1. Open testing tool: http://192.168.18.242:8000/test-auth.html
2. Run diagnostic: `php backend/test_api_auth.php`
3. Check logs: `tail -f backend/storage/logs/laravel.log`
4. Read guide: `backend/FIX_401_ERRORS.md`

---

**TL;DR:**
```javascript
// Paste di browser Console:
localStorage.clear()
location.href = '/login'
// Login ulang → Done! ✅
```
