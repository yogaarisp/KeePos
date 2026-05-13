# Troubleshooting Guide

## Masalah Umum dan Solusi

### 1. Masih Akses ke localhost Padahal Sudah Network Mode

**Gejala:**
- Console browser menunjukkan: `GET http://localhost:8000/api/...`
- Padahal sudah jalankan `switch-to-network.bat`

**Penyebab:**
- File `.env.local` override file `.env`
- Vite server belum restart setelah `.env` berubah
- Browser cache

**Solusi:**
1. Stop semua server
2. Hapus file `.env.local` di folder `frontend` (jika ada)
3. Jalankan `restart-network.bat`
4. Di browser: Hard refresh (Ctrl+Shift+R) atau gunakan Incognito mode
5. Cek console: harus muncul `API Base URL: http://192.168.18.242:8000/api`

### 2. Error: Cannot read properties of undefined

**Gejala:**
- Error di console: `Cannot read properties of undefined (reading 'toLowerCase')`
- Halaman dashboard error

**Penyebab:**
- Data dari API ada field yang null/undefined
- Kode tidak handle null safety

**Solusi:**
- Sudah diperbaiki di Home.vue dengan optional chaining (`?.`)
- Jika masih error, refresh browser

### 3. Tidak Bisa Akses dari HP/Laptop Lain

**Gejala:**
- Dari komputer server bisa akses
- Dari perangkat lain tidak bisa

**Penyebab:**
- Windows Firewall memblokir
- Tidak satu jaringan WiFi
- Server tidak running dengan host 0.0.0.0

**Solusi:**

#### A. Cek Windows Firewall:
1. Buka "Windows Defender Firewall"
2. Klik "Allow an app through firewall"
3. Cari "Node.js" dan "PHP" - pastikan dicentang untuk "Private"
4. Jika tidak ada, klik "Allow another app" dan tambahkan

#### B. Pastikan Satu Jaringan:
- Komputer server dan perangkat lain harus terhubung ke WiFi yang sama
- Cek IP: `ipconfig` di CMD

#### C. Pastikan Server Running dengan Benar:
Backend harus dengan flag `--host=0.0.0.0`:
```bash
php artisan serve --host=0.0.0.0 --port=8000
```

Frontend (Vite) sudah otomatis dengan `host: '0.0.0.0'` di `vite.config.js`

### 4. IP Address Berubah

**Gejala:**
- Kemarin bisa, hari ini tidak bisa
- Setelah restart router/komputer

**Penyebab:**
- IP dinamis berubah

**Solusi:**
1. Cek IP baru: `ipconfig` di CMD
2. Update di semua file:
   - `frontend/.env`
   - `frontend/.env.network`
   - `frontend/vite.config.js`
   - `backend/.env`
   - `backend/.env.network`
   - `backend/config/cors.php`
   - `start-all-network.bat`
   - `switch-to-network.bat`

**Cara Cepat:**
Gunakan Find & Replace di editor:
- Find: `192.168.18.242`
- Replace: `[IP_BARU]`

### 5. Port Sudah Digunakan

**Gejala:**
- Error: "Port 3000 is already in use"
- Error: "Port 8000 is already in use"

**Solusi:**
1. Tutup aplikasi yang menggunakan port tersebut
2. Atau kill process:
```bash
# Kill port 3000
netstat -ano | findstr :3000
taskkill /PID [PID_NUMBER] /F

# Kill port 8000
netstat -ano | findstr :8000
taskkill /PID [PID_NUMBER] /F
```

### 6. CORS Error

**Gejala:**
- Error di console: "CORS policy: No 'Access-Control-Allow-Origin'"

**Solusi:**
Pastikan IP sudah ditambahkan di `backend/config/cors.php`:
```php
'allowed_origins' => [
    'http://localhost:3000',
    'http://192.168.18.242:3000', // Tambahkan ini
],
```

Setelah edit, restart backend server.

### 7. Database Connection Error

**Gejala:**
- Error: "SQLSTATE[HY000] [2002] Connection refused"

**Solusi:**
1. Pastikan MySQL/MariaDB running di Laragon
2. Cek kredensial di `backend/.env`:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=wartegkee
DB_USERNAME=root
DB_PASSWORD=
```

### 8. Web Bluetooth Error (Printer)

**Gejala:**
- Error: "Web Bluetooth tidak didukung"

**Penjelasan:**
- Web Bluetooth hanya bekerja di:
  - Chrome/Edge di Android
  - Chrome/Edge di Windows/Mac dengan HTTPS
  - TIDAK bekerja di iOS (Safari/Chrome iOS)

**Solusi:**
- Gunakan Chrome/Edge di Android untuk fitur printer Bluetooth
- Atau akses dengan HTTPS (perlu SSL certificate)

## Checklist Sebelum Trial

- [ ] IP address sudah benar di semua file
- [ ] Sudah jalankan `switch-to-network.bat`
- [ ] Backend running dengan `--host=0.0.0.0`
- [ ] Frontend running dan sudah restart setelah `.env` berubah
- [ ] Windows Firewall allow Node.js dan PHP
- [ ] Semua perangkat terhubung ke WiFi yang sama
- [ ] Browser di perangkat lain sudah clear cache atau gunakan Incognito

## Cara Test Cepat

1. **Test dari komputer server:**
   - Buka: `http://192.168.18.242:3000`
   - Harus bisa login

2. **Test dari HP/Laptop lain:**
   - Buka Incognito/Private mode
   - Akses: `http://192.168.18.242:3000`
   - Buka Console (F12 di laptop)
   - Cek log: `API Base URL: http://192.168.18.242:8000/api`

## Kontak Support

Jika masih bermasalah, screenshot:
1. Console browser (F12 → Console tab)
2. Network tab (F12 → Network tab)
3. Error message yang muncul
