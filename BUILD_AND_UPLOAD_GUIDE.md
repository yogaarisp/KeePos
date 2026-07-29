# Panduan Build dan Upload Frontend

## Langkah-langkah untuk Update Frontend di Server

### 1. Build Frontend Locally
```bash
cd frontend
npm run build
```

### 2. Upload File dist ke Server
Setelah build selesai, upload seluruh isi folder `frontend/dist/` ke server di path folder `public` yang aktif:

*   **Jika Laravel langsung berada di root** (ada file `artisan` langsung di `/www/wwwroot/pos.keetech.my.id/`):
    Upload ke: `/www/wwwroot/pos.keetech.my.id/public/`
*   **Jika Laravel berada di dalam folder `backend`**:
    Upload ke: `/www/wwwroot/pos.keetech.my.id/backend/public/`

*PENTING: Pastikan Anda menghapus folder `assets` lama di folder tujuan sebelum melakukan ekstrak zip, dan pastikan file zip di-extract langsung ke dalam folder public aktif tersebut (jangan sampai membuat subfolder baru).*

### 3. Set Permissions (Jalankan di Server)
Sesuaikan path folder `public` dengan kondisi server Anda:
```bash
# Masuk ke server via SSH
cd /www/wwwroot/pos.keetech.my.id

# Jika Laravel langsung di root:
chown -R www:www public/
chmod -R 755 public/

# ATAU jika Laravel di dalam folder backend:
chown -R www:www backend/public/
chmod -R 755 backend/public/
```

### 4. Clear Browser Cache
- Buka browser
- Tekan Ctrl+Shift+R (atau Cmd+Shift+R di Mac) untuk hard refresh
- Atau buka Developer Tools > Application > Storage > Clear Storage

## Troubleshooting

### Jika API URL masih menunjukkan "/api":
1. Pastikan file `.env` di folder `frontend/` sudah benar
2. Hapus folder `frontend/dist/` dan build ulang
3. Upload ulang file hasil build

### Jika masih ada error service worker:
1. Buka Developer Tools > Application > Service Workers
2. Klik "Unregister" pada service worker lama
3. Refresh halaman

### Jika pricing masih salah:
1. Pastikan backend sudah diupdate dengan harga PRO = 299,000
2. Clear cache browser
3. Test API endpoint: `curl https://pos.keetech.my.id/api/subscriptions/plans/public`

## File yang Sudah Diperbaiki:
- ✅ `frontend/.env` - URL configuration
- ✅ `frontend/src/api/index.js` - API URL fallback
- ✅ `frontend/src/views/Billing.vue` - Endpoint public
- ✅ `backend/app/Http/Controllers/Api/SubscriptionController.php` - PRO price 299,000

## Hasil yang Diharapkan:
- Pricing menampilkan: BASIC Rp 99.000, PRO Rp 299.000
- Tidak ada error console terkait API URL
- Service worker berfungsi normal
- Billing page load dengan benar