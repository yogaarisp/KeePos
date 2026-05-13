# Panduan Konfigurasi SMTP & Verifikasi OTP - Kee POS

Dokumen ini menjelaskan cara mengkonfigurasi layanan pengiriman email (SMTP) agar fitur **Reset Password** dan **Verifikasi Akun** dapat berjalan dengan lancar.

---

## 1. Konfigurasi SMTP di Dashboard
Sebagai Superadmin, Anda dapat mengatur SMTP secara global tanpa harus menyentuh file `.env`.

### Langkah-langkah:
1. Masuk ke halaman **SaaS Konfigurasi** (Superadmin).
2. Klik tab **Email SMTP**.
3. Isi kolom sesuai provider email Anda:

| Kolom | Contoh (Gmail) | Keterangan |
| :--- | :--- | :--- |
| **SMTP Host** | `smtp.gmail.com` | Host server provider email. |
| **SMTP Port** | `587` | Gunakan `587` untuk TLS atau `465` untuk SSL. |
| **SMTP Username** | `emailanda@gmail.com` | Alamat email pengirim. |
| **SMTP Password** | `xxxx xxxx xxxx xxxx` | **App Password** (Lihat bagian bawah). |
| **Encryption** | `TLS` | Pilih mode enkripsi yang sesuai. |
| **Sender Email** | `noreply@wartegkee.com` | Email yang muncul di kotak masuk penerima. |
| **Sender Name** | `Kee POS System` | Nama pengirim yang muncul. |

---

## 2. Cara Mendapatkan App Password (Gmail)
Jika Anda menggunakan Gmail, Anda **tidak bisa** menggunakan password email biasa. Anda harus membuat *App Password*:

1. Buka [Google Account Security](https://myaccount.google.com/security).
2. Aktifkan **2-Step Verification** (Wajib).
3. Di kolom pencarian bagian atas, ketik **"App Passwords"**.
4. Pilih **Create** -> Beri nama (contoh: "Kee POS SaaS").
5. Google akan memberikan **16 digit kode**. Masukkan kode ini ke kolom **SMTP Password** di dashboard.

---

## 3. Alur Verifikasi OTP (Registrasi)
Saat ini sistem secara default menggunakan **Link Verifikasi**. Untuk mengubahnya menjadi **OTP (6-Digit Kode)**, berikut adalah alur kerjanya:

### A. Alur Saat Ini (Link)
1. User daftar -> Email dikirim -> User klik link di email -> Akun Aktif.

### B. Perubahan ke OTP (Akan Datang)
Jika Anda meminta saya mengimplementasikan OTP, saya akan melakukan perubahan berikut:
1. **Database**: Membuat tabel `otp_codes` untuk menyimpan kode sementara.
2. **Notification**: Mengubah template email dari "Tombol Link" menjadi "Teks Kode 6 Digit".
3. **Frontend**:
    - Menambah halaman `/verify-otp` dengan input kotak-kotak (premium design).
    - Menambah fitur "Resend OTP" jika kode belum masuk.
4. **Backend**: Menambah endpoint API untuk validasi kode yang dimasukkan user.

---

## 4. Tips Troubleshooting
- **Email tidak masuk?** Cek folder Spam atau periksa apakah port `587` diblokir oleh provider internet/hosting Anda.
- **Error "Authentication Failed"?** Pastikan App Password benar dan tidak ada typo pada username.
- **Link/OTP Expired?** Secara default, sistem disetel akan kadaluarsa dalam **60 menit** untuk keamanan.

---

**Rekomendasi Provider Email:**
Untuk produksi (live), disarankan menggunakan layanan profesional seperti **Mailgun**, **SendGrid**, atau **Amazon SES** agar email tidak masuk ke folder spam pelanggan Anda.

**PENTING:**
Berikan perintah **"Lanjutkan implementasi OTP"** kepada Antigravity jika Anda ingin merubah sistem link verifikasi menjadi input kode 6-digit sekarang juga.
