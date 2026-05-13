# Rencana Implementasi: Google Sheets Sync 📊

Fitur ini memungkinkan sistem POS Wartegkee untuk mengirim data transaksi atau rekap harian secara otomatis ke Google Spreadsheet Anda.

## 🛠️ Persiapan (Sisi Google Cloud)

Sebelum fitur diaktifkan, Anda harus menyiapkan kredensial Google Cloud:

1.  **Buat Google Cloud Project**:
    *   Buka [Google Cloud Console](https://console.cloud.google.com/).
    *   Buat Project baru (misal: "Wartegkee-POS-Sync").
2.  **Aktifkan API (Library)**:
    *   Cari dan aktifkan **Google Sheets API**.
    *   Cari dan aktifkan **Google Drive API**.
3.  **Buat Service Account**:
    *   Buka menu **IAM & Admin** > **Service Accounts**.
    *   Klik **Create Service Account**, isi nama (misal: `pos-sync`).
    *   Selesaikan pembuatan (Grant access bisa dilewati).
4.  **Dapatkan File JSON (Key)**:
    *   Klik pada Service Account yang baru dibuat.
    *   Buka tab **Keys** > **Add Key** > **Create New Key**.
    *   Pilih format **JSON**, lalu download. File ini berisi "Private Key" yang sangat penting.
5.  **Siapkan Spreadsheet**:
    *   Buat file Google Spreadsheet baru di akun Google Anda.
    *   Ambil **Spreadsheet ID** dari URL (kode antara `/d/` dan `/edit`).
    *   **PENTING**: Klik tombol **Share** di Google Sheet Anda, lalu tambahkan email Service Account (`nama-akun@project-id.iam.gserviceaccount.com`) sebagai **Editor**.

---

## 💻 Rencana Implementasi Sistem

### 1. Update Menu Pengaturan (Frontend)
Menambahkan tab baru **"Google Sheets"** di halaman `Settings.vue` dengan kolom input:
*   `spreadsheet_id`: ID dari Google Sheet tujuan.
*   `service_account_json`: Area teks untuk menempelkan isi file JSON kredensial.
*   `sync_enabled`: Switch On/Off untuk mengaktifkan fitur secara global.
*   `test_connection`: Tombol untuk memvalidasi apakah kredensial sudah benar dan bisa akses ke Sheet.

### 2. Backend Integration (Laravel)
*   **Library**: Menginstal library resmi `google/apiclient`.
*   **Service Class**: Membuat `GoogleSheetService.php` untuk menangani:
    *   Autentikasi menggunakan JSON key.
    *   Pengecekan keberadaan Sheet/Tab.
    *   Penulisan data (Append) ke baris baru.
*   **API Routes**: Membuat endpoint untuk menyimpan pengaturan dan melakukan tes koneksi.

### 3. Alur Data (Syncing)
Fitur ini direncanakan mengirim data pada saat:
*   **Setiap Checkout Berhasil**: Mengirim satu baris data transaksi (Invoice, Total, Metode Bayar).
*   **Tutup Shift**: Mengirim rekap penjualan harian/shift ke tab khusus.
*   **Update Stok (Opsional)**: Mengirim peringatan jika stok bahan baku kritis.

---

## 📋 Struktur Data di Spreadsheet (5 Kategori Utama)

Sistem akan otomatis membuat/mengupdate tab berikut di Google Sheet Anda:

### 1. Transaksi (Real-time)
Tiap ada pesanan baru sukses, baris baru akan ditambahkan.
*   *Kolom:* Tgl, Jam, Invoice, Pelanggan, Kasir, Total, Bayar, Metode, Status.

### 2. Stok Gudang (Sync on Change / Manual)
Daftar seluruh bahan baku di gudang utama.
*   *Kolom:* Kode/ID, Nama Bahan, Kategori, Stok Saat Ini, Satuan, Stok Minimum, Status Kritis.

### 3. Stok Dapur (Sync on Change / Manual)
Daftar seluruh bahan baku yang sudah didistribusikan ke dapur.
*   *Kolom:* Nama Bahan, Stok Dapur, Satuan, Update Terakhir.

### 4. Produk (Manual Sync)
Daftar menu/produk yang dijual.
*   *Kolom:* Nama Menu, Kategori, Harga Jual, Status (Tersedia/Habis).

### 5. Resep (Manual Sync)
Detail komposisi tiap menu.
*   *Kolom:* Nama Menu, Bahan Baku, Takaran (Qty), Satuan.

---

## 🚀 Fitur Sinkronisasi
*   **Auto-Sync**: Berlaku untuk *Transaksi* (setiap kali klik bayar).
*   **Manual Export**: Tersedia tombol "Sync All to Google Sheets" di halaman Pengaturan untuk memperbarui seluruh data (Stok, Produk, Resep) sekaligus.

---

> [!TIP]
> **Keamanan Data**: File JSON Service Account hanya akan disimpan di server (Database terenkripsi) dan tidak akan pernah ditampilkan di sisi publik/client tanpa izin admin.

> [!IMPORTANT]
> **Pastikan Anda sudah mendownload file JSON Key dari Google Cloud Console** sebelum kita melanjutkan ke tahap pengkodean.
