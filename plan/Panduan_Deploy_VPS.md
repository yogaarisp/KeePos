# Panduan Build & Deploy ke VPS (Wartegkee POS)

Karena proyek Anda terdiri dari **Backend (Laravel)** dan **Frontend (Vue/Vite)**, Anda perlu memproses keduanya agar bisa berjalan optimal di VPS. Berikut adalah langkah-langkah untuk menyatukannya dan melakukan deploy.

## 1. Persiapan Build (Local)

Sebelum diupload ke VPS, Anda harus melakukan "Build" pada bagian Frontend agar kode Javascript-nya menjadi ringan dan cepat.

### A. Build Frontend
1. Buka terminal di folder `frontend`.
2. Jalankan perintah:
   ```bash
   npm run build
   ```
3. Hasilnya akan ada di folder `frontend/dist`. File-file di dalam folder `dist` inilah yang nanti akan dibaca oleh browser.

---

## 2. Strategi Deployment (Menjadi "Satu Paket")

Agar lebih mudah di VPS, kita punya dua pilihan:

### Opsi A: Dipisah (Terbaik untuk Performa)
*   Domain `api.wartegkee.com` mengarah ke folder `backend/public`.
*   Domain `app.wartegkee.com` mengarah ke folder `frontend/dist`.

### Opsi B: Disatukan (Paling Simpel / Seperti "1 File")
Anda bisa memindahkan isi folder `frontend/dist` ke dalam folder `backend/public`. Dengan begitu, Anda hanya perlu mendeploy folder **backend** saja di VPS.

---

## 3. Langkah-Langkah Deploy ke VPS

### Langkah 1: Packaging
Setelah `npm run build` selesai, kompres (ZIP) seluruh isi folder proyek Anda.
> **Penting**: Jangan masukkan folder `node_modules` atau `vendor` ke dalam ZIP agar ukurannya kecil.

### Langkah 2: Upload & Ekstrak
Upload file ZIP tersebut ke VPS (bisa pakai FileZilla atau scp), lalu ekstrak di folder `/var/www/wartegkee`.

### Langkah 3: Install Dependencies di VPS
Masuk ke terminal VPS, lalu jalankan:
```bash
# Di folder backend
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
```

### Langkah 4: Konfigurasi `.env`
Pastikan file `.env` di VPS sudah diatur sesuai database VPS Anda:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://wartegkee.com

DB_DATABASE=nama_db_vps
DB_USERNAME=user_db_vps
DB_PASSWORD=pass_db_vps
```

---

## 4. Konfigurasi Web Server (Nginx)

Gunakan konfigurasi ini agar Nginx mengarah ke folder `public` Laravel Anda:

```nginx
server {
    listen 80;
    server_name wartegkee.com;
    root /var/www/wartegkee/backend/public;

    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
    }
}
```

## 5. Ringkasan: "Dibuat 1 File"
Maksud dari "1 file" dalam dunia web biasanya adalah **Docker Image**. 
Jika Anda ingin benar-benar menjadi 1 file image tinggal "colok" jalan, saya sarankan menggunakan **Docker**. Jika diinginkan, saya bisa buatkan file `Dockerfile` untuk Anda.

---
> [!TIP]
> **Cara Paling Cepat:**
> Cukup ZIP folder `backend` Anda, lalu upload. Pastikan folder `backend/public` sudah berisi hasil build dari `frontend/dist` tadi. Dengan begitu, VPS Anda hanya perlu menjalankan Laravel saja.
