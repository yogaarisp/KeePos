# 🎨 Logo Tenant Implementation

## ✅ Fitur yang Sudah Diimplementasi

Logo toko tenant sekarang ditampilkan di:

### 1. **Live Struk Preview** (Settings Page)
**Lokasi:** `frontend/src/views/Settings.vue`

**Fitur:**
- ✅ Logo muncul di atas nama toko
- ✅ Preview real-time saat upload logo baru
- ✅ Responsive untuk ukuran kertas 58mm & 80mm
- ✅ Fallback ke nama toko jika belum ada logo

**Kode:**
```vue
<div class="thermal-receipt">
  <div class="r-header">
    <!-- Logo Toko -->
    <div v-if="logoPreview || shopForm.shop_logo_url" class="r-logo">
      <img :src="logoPreview || (baseUrl + '/storage/' + shopForm.shop_logo_url)" 
           alt="Logo" 
           class="receipt-logo">
    </div>
    <h2 class="r-shop-name">{{ shopForm.shop_name || 'Kee POS' }}</h2>
    <p class="r-shop-addr">{{ shopForm.shop_address }}</p>
    <p class="r-shop-telp">Telp: {{ shopForm.shop_phone }}</p>
  </div>
</div>
```

**CSS:**
```css
.r-logo { 
  display: flex; 
  justify-content: center; 
  margin-bottom: 12px; 
}
.receipt-logo { 
  max-width: 80px; 
  max-height: 80px; 
  object-fit: contain; 
  border-radius: 8px;
}
```

---

### 2. **Digital Receipt** (POS Page)
**Lokasi:** `frontend/src/views/POS.vue`

**Fitur:**
- ✅ Logo muncul di struk digital setelah checkout
- ✅ Fallback ke icon Utensils jika belum ada logo
- ✅ Data logo diambil dari `pos.shopSettings`

**Kode:**
```vue
<div class="receipt-header">
  <div class="receipt-logo-wrap">
    <img v-if="pos.shopSettings?.shop_logo" 
         :src="baseUrl + '/storage/' + pos.shopSettings.shop_logo" 
         class="receipt-logo">
    <div v-else class="receipt-icon-fallback">
      <Utensils :size="30" />
    </div>
  </div>
  <h2 class="receipt-shop-name">{{ pos.shopSettings?.shop_name }}</h2>
</div>
```

---

### 3. **Receipt Data** (Print & Orders)
**Lokasi:** 
- `frontend/src/views/POS.vue` (2 tempat)
- `frontend/src/views/Orders.vue`

**Fitur:**
- ✅ Logo data di-pass ke printer service
- ✅ Siap untuk implementasi logo printing (bitmap)

**Kode:**
```javascript
const receiptData = {
  shopName: pos.shopSettings?.shop_name || 'Wartegkee',
  shopLogo: pos.shopSettings?.shop_logo || null, // ✅ Added
  shopAddress: pos.shopSettings?.shop_address || '',
  shopPhone: pos.shopSettings?.shop_phone || '',
  // ... other data
};

await printer.printReceipt(receiptData, paperSize);
```

---

## 📊 Data Flow

```
┌─────────────────────────────────────────┐
│  Backend: tenant_profiles table         │
│  - shop_logo: "tenants/logos/xxx.png"   │
└──────────────┬──────────────────────────┘
               │
               ↓ API: /api/settings
┌──────────────▼──────────────────────────┐
│  Frontend: settingStore                 │
│  - settings.shop_logo                   │
└──────────────┬──────────────────────────┘
               │
       ┌───────┴────────┐
       │                │
       ↓                ↓
┌──────▼─────┐   ┌─────▼──────┐
│ Settings   │   │ POS        │
│ (Preview)  │   │ (Digital)  │
└────────────┘   └────────────┘
```

---

## 🎯 Cara Menggunakan

### Upload Logo Toko

1. **Login sebagai Admin/Owner**
2. **Buka Settings** → Tab "Profil Toko"
3. **Upload Logo:**
   - Klik area upload logo
   - Pilih file gambar (PNG, JPG, max 2MB)
   - Logo langsung muncul di Live Preview
4. **Simpan Pengaturan**

### Lihat Logo di Struk

**Live Preview:**
- Scroll ke kanan di Settings page
- Logo muncul di atas nama toko

**Digital Receipt:**
- Lakukan transaksi di POS
- Setelah checkout, logo muncul di struk digital

---

## 🖨️ Thermal Printer Support

### Status Saat Ini
❌ **Logo belum bisa dicetak ke thermal printer**

**Alasan:**
- Thermal printer menggunakan ESC/POS commands (text-based)
- Logo perlu di-convert ke bitmap format khusus
- Butuh encoding ESC * atau GS v 0

### Implementasi Future (Optional)

Untuk print logo ke thermal printer, perlu:

1. **Convert image to bitmap:**
```javascript
async function imageToBitmap(imageUrl) {
  const img = new Image();
  img.src = imageUrl;
  await img.decode();
  
  const canvas = document.createElement('canvas');
  canvas.width = 384; // 58mm = 384 dots
  canvas.height = img.height * (384 / img.width);
  
  const ctx = canvas.getContext('2d');
  ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
  
  const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
  return convertToMonochrome(imageData);
}
```

2. **Send bitmap to printer:**
```javascript
// ESC * command for bitmap
const ESC_BITMAP = [0x1B, 0x2A, 0x21, ...bitmapData];
await this._sendChunked(ESC_BITMAP);
```

3. **Update thermalPrinter.js:**
```javascript
async printReceipt(receiptData, width) {
  // ... existing code
  
  // Print logo if exists
  if (receiptData.shopLogo) {
    const bitmap = await imageToBitmap(receiptData.shopLogo);
    commands.push(...this.ESC.ALIGN_CENTER);
    commands.push(...bitmap);
    commands.push(...encoder.encode('\n'));
  }
  
  // ... rest of receipt
}
```

**Estimasi Effort:** 4-6 jam development + testing

---

## ✅ Testing Checklist

### Backend
- [x] Tenant profile punya field `shop_logo`
- [x] API `/settings` return `profile.shop_logo`
- [x] Upload logo tersimpan di `storage/app/public/tenants/logos/`
- [x] Symlink storage sudah dibuat: `php artisan storage:link`

### Frontend
- [x] Settings store merge `profile.shop_logo` ke `settings`
- [x] Live Preview menampilkan logo
- [x] Logo preview update saat upload
- [x] Digital receipt menampilkan logo
- [x] Logo data di-pass ke printer service

### User Flow
- [x] Upload logo di Settings
- [x] Logo muncul di Live Preview
- [x] Logo tersimpan setelah klik "Simpan"
- [x] Logo muncul di POS digital receipt
- [ ] Logo tercetak di thermal printer (future)

---

## 🐛 Troubleshooting

### Logo Tidak Muncul di Preview

**Penyebab:** File belum di-upload atau path salah

**Solusi:**
1. Cek `shopForm.shop_logo_url` di console
2. Cek file exists: `ls storage/app/public/tenants/logos/`
3. Pastikan symlink: `php artisan storage:link`

### Logo Tidak Tersimpan

**Penyebab:** Backend tidak handle file upload

**Solusi:**
1. Cek `SettingController@update` handle `shop_logo` file
2. Cek permission folder: `chmod 775 storage/app/public/tenants/logos`

### Logo Pecah/Blur

**Penyebab:** Resolusi terlalu rendah

**Solusi:**
1. Upload logo minimal 200x200px
2. Format PNG dengan background transparan
3. Ukuran file max 2MB

---

## 📝 File Changes Summary

### Modified Files:
1. ✅ `frontend/src/views/Settings.vue`
   - Added logo preview in thermal receipt
   - Added CSS for `.r-logo` and `.receipt-logo`

2. ✅ `frontend/src/views/POS.vue`
   - Added `shopLogo` to receiptData (2 places)

3. ✅ `frontend/src/views/Orders.vue`
   - Added `shopLogo` to receiptData

4. ✅ `frontend/src/stores/setting.js`
   - Merge `profile.shop_logo` to settings

5. ✅ `backend/app/Http/Controllers/Api/SettingController.php`
   - Handle `shop_logo` file upload
   - Save to `tenant_profiles` table

---

## 🎉 Result

Sekarang setiap tenant bisa:
- ✅ Upload logo toko sendiri
- ✅ Lihat logo di Live Preview struk
- ✅ Logo muncul di struk digital POS
- ✅ Logo tersimpan per tenant (isolated)
- ⏳ Print logo ke thermal printer (future enhancement)

**Tenant "Semoga Jaya"** sekarang bisa upload logo dan akan muncul di semua struk mereka! 🎨
