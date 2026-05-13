# Setup Jaringan Lokal (LAN)

## Cara Menggunakan

### Mode Network (Akses dari Perangkat Lain)

1. **Switch ke Network Mode:**
   ```
   Double-click: switch-to-network.bat
   ```

2. **Start Servers:**
   ```
   Double-click: start-all-network.bat
   ```

3. **Akses dari Perangkat Lain:**
   - Pastikan perangkat terhubung ke WiFi/jaringan yang sama
   - Buka browser di HP/Tablet/Laptop lain
   - Akses: `http://192.168.18.242:3000`

### Mode Local (Development Normal)

1. **Switch ke Local Mode:**
   ```
   Double-click: switch-to-local.bat
   ```

2. **Start Backend:**
   ```
   cd backend
   php artisan serve
   ```

3. **Start Frontend:**
   ```
   cd frontend
   npm run dev
   ```

4. **Akses:**
   - Browser: `http://localhost:3000`

## Troubleshooting

### Firewall Windows
Jika tidak bisa diakses dari perangkat lain:
1. Buka Windows Defender Firewall
2. Klik "Allow an app through firewall"
3. Pastikan Node.js dan PHP diizinkan untuk Private networks

### IP Address Berubah
Jika IP komputer berubah (setelah restart router):
1. Cek IP baru: `ipconfig` di CMD
2. Update IP di file-file berikut:
   - `frontend/.env.network`
   - `frontend/vite.config.js`
   - `backend/.env.network`
   - `backend/config/cors.php`
   - `start-all-network.bat`
   - `switch-to-network.bat`

### Port Sudah Digunakan
Jika port 3000 atau 8000 sudah digunakan:
- Tutup aplikasi yang menggunakan port tersebut
- Atau ubah port di konfigurasi

## Catatan

- **Network Mode**: Untuk testing di perangkat lain (HP, tablet)
- **Local Mode**: Untuk development normal di komputer sendiri
- Jangan lupa switch mode sesuai kebutuhan!
