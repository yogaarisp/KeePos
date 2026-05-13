# 🔧 Quick Fix: Billing Page 401 Error

## 📋 Problem

Halaman Billing (`http://192.168.18.242:3000/app/billing`) tidak menampilkan pilihan paket karena API error 401 Unauthorized:

```
❌ /api/subscriptions/plans → 401 Unauthorized
❌ /api/subscriptions/invoices → 401 Unauthorized
❌ /api/me → 401 Unauthorized
```

## 🎯 Root Cause

Token authentication di browser sudah **TIDAK VALID** setelah perubahan database structure. Ini adalah masalah yang sama dengan error 401 di halaman Settings.

## ✅ SOLUSI CEPAT (2 Menit)

### Option 1: Clear Storage via Browser Console (RECOMMENDED)

1. Buka halaman Billing: `http://192.168.18.242:3000/app/billing`
2. Tekan **F12** untuk buka DevTools
3. Klik tab **Console**
4. Paste code ini dan tekan Enter:

```javascript
localStorage.clear()
sessionStorage.clear()
alert('Storage cleared! Redirecting to login...')
location.href = 'http://192.168.18.242:3000/login'
```

5. Login ulang dengan:
   - Email: `duasrcbook@gmail.com`
   - Password: (password Anda)

6. Setelah login, buka Billing lagi: `http://192.168.18.242:3000/app/billing`

7. Pilihan paket akan muncul! ✅

### Option 2: Use Test Tool

1. Buka test tool: `http://192.168.18.242:8000/test-auth.html`
2. Klik **"Clear Storage"**
3. Di section **"Login Test"**, masukkan:
   - Email: `duasrcbook@gmail.com`
   - Password: (password Anda)
4. Klik **"Login"**
5. Setelah berhasil, buka Billing page

### Option 3: Manual Clear

1. Tekan **F12** → Tab **Application** (Chrome) atau **Storage** (Firefox)
2. Expand **Local Storage** → Pilih `http://192.168.18.242:3000`
3. Klik kanan → **Clear**
4. Expand **Session Storage** → Clear juga
5. Close DevTools
6. Tekan **Ctrl+Shift+R** (hard refresh)
7. Login ulang

## 🧪 Verify Fix

Setelah login ulang, test di Console:

```javascript
// Test 1: Check token
console.log('Token:', localStorage.getItem('auth_token'))

// Test 2: Test API
fetch('/api/subscriptions/plans', {
  headers: {
    'Authorization': 'Bearer ' + localStorage.getItem('auth_token'),
    'Accept': 'application/json'
  }
})
.then(r => r.json())
.then(data => {
  console.log('✅ Plans API Working!')
  console.log('Available Plans:', data.data)
})
.catch(err => console.error('❌ Still error:', err))
```

Expected output:
```javascript
✅ Plans API Working!
Available Plans: {
  free: { name: "FREE", price: 0, features: [...] },
  basic: { name: "BASIC", price: 99000, features: [...] },
  pro: { name: "PRO", price: 249000, features: [...] }
}
```

## 📊 What You Should See After Fix

### Billing Page - Plans Section

```
┌─────────────────────────────────────────────────────┐
│  Pilih Paket Langganan                              │
│  Tingkatkan efisiensi bisnis Anda dengan paket     │
│  yang tepat.                                        │
├─────────────────────────────────────────────────────┤
│                                                     │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐         │
│  │   FREE   │  │  BASIC   │  │   PRO    │         │
│  │          │  │          │  │ REKOMENDASI        │
│  │  Rp 0    │  │ Rp 99K   │  │ Rp 249K  │         │
│  │  /bln    │  │  /bln    │  │  /bln    │         │
│  │          │  │          │  │          │         │
│  │ Features │  │ Features │  │ Features │         │
│  │ ...      │  │ ...      │  │ ...      │         │
│  │          │  │          │  │          │         │
│  │ [Paket   │  │ [Pilih   │  │ [Pilih   │         │
│  │  Dasar]  │  │  Paket]  │  │  Paket]  │         │
│  └──────────┘  └──────────┘  └──────────┘         │
└─────────────────────────────────────────────────────┘
```

### Current Subscription Status

```
┌─────────────────────────────────────────────────────┐
│  PAKET SAAT INI                                     │
│  FREE  [Masa Trial]                                 │
│                                                     │
│  📅 TRIAL BERAKHIR                                  │
│  [Date]                                             │
└─────────────────────────────────────────────────────┘
```

## 🔍 Troubleshooting

### Masih 401 setelah login ulang?

**1. Check Network Tab:**
- DevTools (F12) → **Network** tab
- Refresh page
- Klik request `/api/subscriptions/plans`
- Tab **Headers** → **Request Headers**
- Harus ada: `Authorization: Bearer 77|abc...`

**2. Check Console Errors:**
```javascript
// Paste di Console untuk debug
fetch('/api/subscriptions/plans', {
  headers: {
    'Authorization': 'Bearer ' + localStorage.getItem('auth_token'),
    'Accept': 'application/json'
  }
})
.then(async r => {
  console.log('Status:', r.status)
  const data = await r.json()
  console.log('Response:', data)
})
```

**3. Regenerate Token Manually:**

Jika masih gagal, generate token baru:

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
    slug: 'semoga-jaya',
    plan: 'free',
    is_active: true
  }
}))
location.reload()
```

## 📝 Why This Happened?

Setelah database migration (settings → 3 tables):
1. ✅ Database schema berubah
2. ✅ Data migrated successfully
3. ❌ Token di browser masih **token LAMA**
4. ❌ Token lama tidak valid karena session reset

**Solution:** Clear storage + login ulang = Generate **token BARU**

## 🎯 Expected Behavior After Fix

1. ✅ Billing page loads successfully
2. ✅ 3 plan cards displayed (FREE, BASIC, PRO)
3. ✅ Current subscription status shows
4. ✅ Invoice history table shows (empty if no transactions)
5. ✅ Can click "Pilih Paket" to open checkout modal

## 📞 Still Having Issues?

1. **Run diagnostic:**
   ```bash
   cd backend
   php test_api_auth.php
   ```

2. **Check backend logs:**
   ```bash
   tail -f backend/storage/logs/laravel.log
   ```

3. **Clear backend cache:**
   ```bash
   cd backend
   php artisan config:clear
   php artisan cache:clear
   php artisan route:clear
   ```

4. **Read full solution:**
   - `SOLUTION_401_ERRORS.md`
   - `backend/FIX_401_ERRORS.md`

---

**TL;DR:**
```javascript
// Paste di Console:
localStorage.clear()
location.href = '/login'
// Login ulang → Billing page akan normal ✅
```

**Status:** This is the SAME 401 issue from before. Fix is the same: clear storage + re-login.

**Estimated Fix Time:** 2 minutes
