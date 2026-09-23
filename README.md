# Kee POS

Aplikasi **Point of Sale (POS)** multi-tenant untuk warung/kuliner — satu instance melayani banyak toko (tenant), masing-masing dengan data terisolasi penuh.

## Fitur Utama

- **Kasir (POS)**: transaksi cepat, dukungan produk custom (atribut opsi), meja/operasi, print struk thermal.
- **Manajemen toko**: produk, kategori, bahan baku, resep, supplier, karyawan, shift, stok & produksi (kitchen).
- **Laporan**: penjualan, laba rugi, inventori, waste — per-tenant terisolasi.
- **Multi-tenant SaaS**: registrasi toko, paket langganan (free/basic/pro), pembatasan sesuai plan.
- **Google Sheets**: sinkronisasi laporan/transaksi ke spreadsheet (via Service Account), terenkripsi per secret tenant.
- **Otentikasi**: login + OTP 2FA via email, role `superadmin` / `admin` / `kasir`.

## Arsitektur

Repositori ini adalah monorepo dua bagian:

```
backend/   Laravel 12 REST API (multi-tenant) + build output frontend (backend/public)
frontend/  Vue 3 + Vite SPA
```

### Multi-tenancy

- Setiap `Tenant` memiliki slug, ditentukan dari **user yang login** (otoritatif) — header `X-Tenant-Slug` / subdomain hanya dipakai untuk request tanpa autentikasi.
- Isolasi data via global scope `BelongsToTenant` (model Eloquent) + filter eksplisit untuk query mentah (`DB::table`).
- Kredensial sensitif (Google Service Account JSON, password SMTP) dimasking dan hanya bisa diubah admin/superadmin.
- Harga transaksi POS dihitung **server-side** dari database — nilai dari client tidak dipercaya.

### Tech Stack

| Layer    | Teknologi                               |
|----------|-----------------------------------------|
| Backend  | PHP 8.2, Laravel 12, Sanctum, MySQL     |
| Frontend | Vue 3, Vite, Tailwind, SweetAlert2      |
| Auth     | Sanctum token + verifikasi email + OTP  |
| Sync     | Google Sheets API (Service Account)     |

## Persiapan Lingkungan

Backend (`.env` — contoh di `backend/.env.example`):

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=wartegkee
DB_USERNAME=root
DB_PASSWORD=
APP_URL=http://localhost:8000
FRONTEND_URL=http://localhost:3000
```

Frontend (`frontend/.env.local` — contoh di `frontend/.env.example`):

```
VITE_API_URL=http://localhost:8000/api
VITE_APP_URL=http://localhost:3000
```

> File `.env` (backend) dan `.env.local` / `.env` (frontend) tidak di-commit ke version control.

## Menjalankan Lokal

```bash
# 1. Backend
cd backend
composer install
cp .env.example .env            # lalu isi konfigurasi DB
php artisan key:generate
php artisan migrate --seed
php artisan serve                # http://localhost:8000

# 2. Frontend (terminal terpisah)
cd frontend
npm install
npm run dev                      # http://localhost:3000
```

### Build produksi (frontend → backend/public)

```bash
cd frontend
npm run build                    # output ke ../backend/public
```

## Testing

```bash
cd backend
php artisan test
```

Suite mencakup smoke test tiap endpoint, transaksi POS, kebijakan plan, subscription, serta **regression keamanan multi-tenant** (isolasi data antar tenant, pembatasan role, masking secret, harga POS server-side).

## Struktur Direktori Penting

```
backend/
  app/Http/Middleware/IdentifyTenant.php   # resolusi tenant dari user login
  app/Services/POSService.php              # logika transaksi POS (harga server-side)
  app/Services/GoogleSheetService.php      # sinkronisasi Google Sheets
  app/Models/                              # model + global scope BelongsToTenant
  routes/api.php                           # endpoint API
  tests/Feature/                           # feature tests & security regression
frontend/
  src/views/                               # halaman Vue
  src/api/                                 # client API
```

## Deployment

- Backend Laravel: VPS/panel (lihat `backend/DEPLOYMENT_GUIDE.md`, `plan/Panduan_Deploy_VPS.md`).
- Frontend di-build menjadi asset statis di `backend/public` (SPA; pastikan server menangani fallback ke `index.html` — contoh di `public-htaccess-spa.txt`).
- Secret service account Google disimpan terenkripsi (jangan di-commit).

## Lisensi

Proprietary. Dilarang menggandakan atau mendistribusikan tanpa izin.