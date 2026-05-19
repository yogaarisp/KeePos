📋 Planning: Kee POS SaaS — Gap Analysis & Roadmap
✅ Yang Sudah Ada (Solid)
Multi-tenant architecture (single DB, tenant isolation via global scope)
3 plan tiers (FREE/BASIC/PRO) dengan enforcement di backend
Midtrans payment + manual bank transfer
Superadmin panel (tenant management, SaaS config, system info)
POS, produk, gudang, dapur, resep, laporan, shift, waste, supplier
Email verification (OTP + link), password reset
Google Sheets sync
Dynamic branding per tenant (logo, favicon, warna)
🔴 Critical — Harus Ada Sebelum Production
1. Manual Payment Approval oleh Superadmin
Masalah: Tenant bisa upload bukti transfer, tapi superadmin tidak punya endpoint untuk approve/reject. Invoice manual selamanya pending. Yang dibutuhkan:

PATCH /admin/invoices/{id}/approve → set status paid, extend subscription
PATCH /admin/invoices/{id}/reject → set status rejected, kirim notifikasi
UI di halaman admin/invoices untuk tombol approve/reject + preview bukti
2. Subscription Expiry Reminder
Masalah: Tenant tidak dapat peringatan sebelum langganan habis, tiba-tiba akses diblokir. Yang dibutuhkan:

Laravel Scheduled Command: cek tenant yang akan expired dalam 7 hari danlanj 1 hari
Email notifikasi ke owner tenant
Banner peringatan di dalam app (sudah ada sebagian di Dashboard.vue)
3. Rate Limiting pada Auth Endpoints
Masalah: /login, /register, /forgot-password tidak ada throttle — rentan brute force. Yang dibutuhkan:

throttle:5,1 pada login (5 percobaan per menit)
throttle:3,60 pada forgot-password
4. Queue Worker untuk Email
Masalah: Email dikirim synchronous — kalau SMTP lambat, request user ikut lambat/timeout. Yang dibutuhkan:

Setup Laravel Queue (database driver, sudah ada tabel jobs)
Semua notifikasi dispatch ke queue
Supervisor config untuk worker di server
🟡 Penting — Untuk Pengalaman Pengguna yang Baik
5. Email Konfirmasi Pembayaran
Masalah: Setelah bayar via Midtrans atau manual diapprove, tenant tidak dapat email konfirmasi. Yang dibutuhkan:

PaymentSuccessNotification → dikirim saat webhook Midtrans settlement atau manual approve
Isi: detail paket, tanggal aktif sampai, invoice number
6. Welcome Email Setelah Registrasi
Masalah: Tidak ada onboarding email setelah verifikasi berhasil. Yang dibutuhkan:

WelcomeNotification → dikirim setelah email terverifikasi
Isi: link login, panduan singkat mulai pakai
7. Workforce Module (Employee & Attendance)
Masalah: Model Employee dan Attendance sudah ada, AttendanceController dan EmployeeController sudah ada, tapi tidak ada routes dan tidak ada frontend views. Folder views/workforce/ kosong. Yang dibutuhkan:

Register routes di api.php
Frontend views: daftar karyawan, absensi, laporan kehadiran
Integrasi dengan shift (siapa kasir yang bertugas)
8. In-App Notification Bell
Masalah: Tidak ada sistem notifikasi di dalam app — tenant tidak tahu kalau ada update penting. Yang dibutuhkan:

Notification model/table
Endpoint GET /notifications dan PATCH /notifications/{id}/read
Bell icon di header dengan badge count
9. Profit & Margin Reporting
Masalah: Tidak ada tracking harga beli (HPP) produk, sehingga tidak bisa hitung profit. Yang dibutuhkan:

Tambah field cost_price di tabel products
Laporan profit: omzet - HPP = laba kotor
Tersedia di plan BASIC+
🟢 Nice to Have — Untuk Kompetitif
10. Attendance & Payroll Report
Laporan kehadiran karyawan per periode
Integrasi dengan shift untuk hitung jam kerja
11. Waste Cost Report
Model WasteReport sudah ada tapi tidak ada laporan biaya waste
Tambah field cost_per_unit untuk hitung kerugian dari waste
12. Production Batch Tracking
Model ProductionRecipe dan ProductionTransaction sudah ada tapi tidak ada controller/routes
Fitur untuk catat produksi massal (misal: bikin 50 risol sekaligus)
13. 2FA Login
Saat ini hanya OTP saat registrasi
Tambah opsi 2FA via email/authenticator app untuk login
14. Subdomain per Tenant
Middleware IdentifyTenant sudah support subdomain detection
Tapi belum ada wildcard DNS setup dan dokumentasi cara konfigurasi
15. Automated Testing
Tidak ada satu pun test file
Minimal: feature tests untuk auth, subscription webhook, plan enforcement


📊 Prioritas Eksekusi
Prioritas	Item	Estimasi
🔴 P1	Manual payment approval	1 hari
🔴 P1	Rate limiting auth	2 jam
🔴 P1	Queue worker setup	2 jam
🔴 P1	Subscription expiry reminder	1 hari
🟡 P2	Email konfirmasi pembayaran	4 jam
🟡 P2	Welcome email	2 jam
🟡 P2	Workforce module (routes + views)	2-3 hari
🟡 P2	In-app notifications	1-2 hari
🟡 P2	Profit reporting (cost_price)	1 hari
🟢 P3	Waste cost report	4 jam
🟢 P3	Production batch tracking	1-2 hari
🟢 P3	2FA	1 hari
🟢 P3	Automated tests	2-3 hari
