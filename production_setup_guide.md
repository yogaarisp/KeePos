# 🌐 Panduan Setup Produksi & Infrastruktur SaaS — WartegKee POS

Panduan ini menjelaskan konfigurasi infrastruktur server yang diperlukan sebelum meluncurkan aplikasi **WartegKee POS** ke lingkungan produksi (aaPanel/Nginx & Ubuntu Server) agar fitur **Subdomain per Tenant** dan **Antrean Email (Queue Worker)** dapat beroperasi secara otomatis dan tanpa kendala.

---

## 1. Konfigurasi Wildcard DNS & Subdomain per Tenant

Aplikasi WartegKee POS sudah memiliki modul pendeteksi subdomain (`IdentifyTenant` middleware). Untuk mengaktifkannya secara fisik di internet sehingga tenant dapat mengakses toko mereka melalui `nama-toko.wartegkee.com`, ikuti langkah-langkah berikut:

### Langkah A: Konfigurasi DNS di Cloudflare / DNS Provider Anda
Anda harus mengarahkan seluruh subdomain (*wildcard*) ke alamat IP server produksi Anda.
1. Masuk ke dashboard DNS Provider Anda (misal: Cloudflare).
2. Tambahkan **A Record** baru:
   - **Type**: `A`
   - **Name**: `*` (simbol asterisk melambangkan wildcard)
   - **IPv4 Address**: `IP_SERVER_PRODUKSI_ANDA` (misal: `103.120.30.40`)
   - **TTL**: `Auto` atau `3600`
   - **Proxy Status**: *DNS Only* (direkomendasikan jika ingin mempermudah setup SSL Wildcard Let's Encrypt di server).

### Langkah B: Setup Domain di aaPanel / Nginx
Anda harus mendaftarkan domain utama beserta wildcard-nya di virtual host Nginx.
1. Buka dashboard **aaPanel** Anda.
2. Masuk ke menu **Website** -> Klik nama situs/domain Anda (misal: `wartegkee.com`).
3. Pada tab **Domain Manager**, tambahkan domain berikut:
   ```txt
   wartegkee.com
   *.wartegkee.com
   ```
4. Simpan konfigurasi. Kini Nginx akan otomatis menerima semua request subdomain ke folder aplikasi Laravel yang sama.

### Langkah C: Konfigurasi SSL Wildcard Let's Encrypt
Agar semua subdomain dapat diakses menggunakan protokol aman HTTPS:
1. Di panel Website aaPanel Anda, klik menu **SSL**.
2. Pilih tab **Let's Encrypt**.
3. Centang domain utama `wartegkee.com` dan wildcard `*.wartegkee.com`.
4. Pilih metode verifikasi **DNS Verification** (Anda perlu memasukkan API token Cloudflare atau memasukkan DNS TXT record secara manual sesuai instruksi aaPanel).
5. Klik **Apply**. Setelah sukses, aktifkan opsi **Force HTTPS** di pojok kanan atas tab SSL.

---

## 2. Setup Supervisor untuk Antrean Email (Queue Worker)

Aplikasi mengirimkan email verifikasi OTP, welcome email, dan pengingat langganan secara asinkron di latar belakang menggunakan antrean database Laravel (`database` driver). Di lingkungan produksi, Anda memerlukan process manager seperti **Supervisor** agar worker antrean berjalan terus-menerus.

### Langkah A: Install Supervisor di Server (Ubuntu/Debian)
Hubungkan terminal SSH ke server Anda, lalu jalankan perintah:
```bash
sudo apt update
sudo apt install supervisor -y
```

### Langkah B: Buat File Konfigurasi Worker Baru
Buat file konfigurasi Supervisor khusus untuk aplikasi WartegKee:
```bash
sudo nano /etc/supervisor/conf.d/wartegkee-worker.conf
```

Masukkan konfigurasi berikut (sesuaikan path folder aplikasi Anda):
```ini
[program:wartegkee-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /www/wwwroot/wartegkee/backend/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www
numprocs=2
redirect_stderr=true
stdout_logfile=/www/wwwroot/wartegkee/backend/storage/logs/worker.log
stopwaitsecs=3600
```
> [!NOTE]
> - **user=www**: Adalah user default web server di aaPanel. Pastikan hak akses file penyimpanan log sesuai dengan user ini.
> - **numprocs=2**: Supervisor akan menjalankan 2 proses worker secara bersamaan untuk mempercepat pemrosesan email masuk.

### Langkah C: Aktifkan dan Jalankan Supervisor
Jalankan perintah berikut untuk memuat ulang konfigurasi dan memulai proses worker:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start wartegkee-worker:*
```

Untuk memantau status worker secara berkala:
```bash
sudo supervisorctl status
```

---

## 3. Langkah Final Checklist Deployment

Saat pertama kali mengunggah file backend ke server produksi:
1. Pastikan file `.env` produksi sudah disesuaikan:
   ```env
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://wartegkee.com
   
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=wartegkee_prod
   DB_USERNAME=wartegkee_user
   DB_PASSWORD=password_aman_anda
   
   QUEUE_CONNECTION=database
   ```
2. Jalankan perintah optimasi dari folder root backend:
   ```bash
   php artisan migrate --force
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```
3. Tambahkan cron job di aaPanel untuk menjalankan scheduler Laravel setiap menit:
   - **Type**: `Shell Script`
   - **Name**: `Laravel Scheduler`
   - **Execution cycle**: `N Minute` (1 Minute)
   - **Script content**:
     ```bash
     php /www/wwwroot/wartegkee/backend/artisan schedule:run >> /dev/null 2>&1
     ```

---
*Dokumen ini dibuat secara otomatis sebagai panduan resmi kelayakan produksi sistem WartegKee POS SaaS.*
