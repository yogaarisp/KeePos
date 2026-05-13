# Panduan Backup Database Otomatis ke Google Drive (GRATIS)

Anda bisa melakukan backup database secara otomatis dan gratis menggunakan library **Spatie Laravel Backup**. Data akan dikompres (.zip) dan dikirim langsung ke folder Google Drive Anda.

## 1. Instalasi Library

Jalankan perintah ini di terminal folder `backend`:

```bash
# Library backup utama
composer require spatie/laravel-backup

# Driver untuk Google Drive
composer require masbug/flysystem-google-drive-ext
```

---

## 2. Konfigurasi Google Drive

Sama seperti saat kita membuat Google Sheets, kita butuh akses ke Google API.

1. Buka [Google Cloud Console](https://console.cloud.google.com/).
2. Aktifkan **Google Drive API**.
3. Buat **OAuth Client ID** atau gunakan **Service Account** yang sudah ada.
4. Buat folder baru di Google Drive Anda, lalu ambil **Folder ID** dari URL browser saat Anda membuka folder tersebut (Contoh: `1abcde12345...`).

---

## 3. Tambahkan "Disk" di Laravel

Buka file `backend/config/filesystems.php` dan tambahkan driver `google` di dalam array `disks`:

```php
'google' => [
    'driver' => 'google',
    'clientId' => env('GOOGLE_DRIVE_CLIENT_ID'),
    'clientSecret' => env('GOOGLE_DRIVE_CLIENT_SECRET'),
    'refreshToken' => env('GOOGLE_DRIVE_REFRESH_TOKEN'),
    'folderId' => env('GOOGLE_DRIVE_FOLDER_ID'),
],
```

*(Lalu tambahkan variabel tersebut di file `.env` Anda)*

---

## 4. Jalankan Backup

Setelah konfigurasi selesai, Anda bisa memicu backup manual dengan perintah:

```bash
php artisan backup:run
```

File `.zip` berisi database dan file storage Anda akan muncul di Google Drive dalam beberapa saat.

---

## 5. Otomatisasi (Sangat Penting)

Agar Anda tidak perlu mengetik perintah setiap hari, kita buat otomatis di VPS.

1. Jalankan `php artisan vendor:publish --provider="Spatie\Backup\BackupServiceProvider"` untuk membuat file `config/backup.php`.
2. Buka `app/Console/Kernel.php` dan tambahkan jadwal:

```php
protected function schedule(Schedule $schedule)
{
    // Backup database setiap hari jam 2 pagi
    $schedule->command('backup:run --only-db')->dailyAt('02:00');
    
    // Hapus backup lama agar tidak penuh
    $schedule->command('backup:clean')->dailyAt('01:00');
}
```

3. Pastikan VPS Anda sudah menjalankan **Cron Job** Laravel:
   `* * * * * cd /path-ke-proyek-anda && php artisan schedule:run >> /dev/null 2>&1`

---

## Keuntungan Cara Ini:
*   **100% Gratis**: Tidak ada biaya langganan bulanan.
*   **Aman**: Data disimpan di server Google yang sangat stabil.
*   **Otomatis**: Anda tinggal tidur, sistem yang bekerja melakukan backup.

> [!TIP]
> Saya sarankan Anda melakukan backup rutin setidaknya sehari sekali untuk menjaga keamanan data transaksi pelanggan Anda.
