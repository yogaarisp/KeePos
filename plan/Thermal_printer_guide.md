# Thermal Printer Web Bluetooth Guide (Starter Kit)

Panduan ini berisi arsitektur dan logika yang digunakan untuk mengimplementasikan sistem cetak thermal bluetooth murni berbasis browser (Web Bluetooth API) yang stabil, mendukung auto-reconnect, dan kompatibel dengan berbagai ukuran kertas.

---

## 1. Arsitektur Utama
Metode ini menggunakan **Hybrid Strategy**:
1.  **Web Bluetooth API**: Untuk koneksi langsung dari browser (Chrome/Edge/Opera).
2.  **ESC/POS Command**: Mengirim perintah biner langsung ke hardware printer.
3.  **Local Storage Persistence**: Menyimpan status koneksi terakhir untuk fitur auto-reconnect.
4.  **Manual Padding Logic**: Solusi centering teks untuk kertas 58mm pada hardware printer yang berbasis 80mm.

---

## 2. API Penting & Konfigurasi
Berikut adalah parameter kunci yang digunakan:

*   **Service UUID**: `000018f0-0000-1000-8000-00805f9b34fb` (Standard Thermal Printer).
*   **Web Bluetooth Methods**:
    *   `navigator.bluetooth.requestDevice()`: Menampilkan jendela pilih device.
    *   `navigator.bluetooth.getDevices()`: Mengambil list device yang sudah pernah diizinkan.
    *   `device.watchAdvertisements()`: Mencari sinyal bluetooth di latar belakang.
    *   `device.forget()`: Menghapus total izin printer (Penting untuk reset state).

---

## 3. Logika Centering 58mm (Anti-Melenceng)
Banyak printer 58mm memiliki firmware 80mm, sehingga perintah `Center` bawaan printer sering melenceng ke kanan. Solusinya adalah menggunakan **Manual Space Padding** (32 Karakter):

```javascript
// Rumus Manual Centering untuk Kertas 58mm (32 Karakter)
const pad = (text) => {
  const maxWidth = 32;
  const spaces = Math.max(0, Math.floor((maxWidth - text.length) / 2));
  return ' '.repeat(spaces) + text;
}
```

---

## 4. Alur Kerja (Workflow)

### A. Proses Koneksi
1.  User klik **Connect**.
2.  Sistem memanggil `requestDevice` dengan filter Service UUID.
3.  Setelah terkoneksi, sistem menyimpan flag `printer_auto_reconnect: true` di LocalStorage.
4.  Sistem mendengarkan event `gattserverdisconnected` untuk mendeteksi putusnya koneksi.

### B. Proses Auto-Reconnect (Saat Refresh Halaman)
1.  Saat halaman dimuat, cek flag di LocalStorage.
2.  Jika `true`, jalankan `getDevices()`.
3.  Jika ada device yang tersimpan, panggil `watchAdvertisements()`.
4.  Begitu printer menyalakan sinyal bluetooth-nya, jalankan `connect()` otomatis.

### C. Proses Disconnect Bersih (Hard Reset)
1.  Set flag `printer_auto_reconnect: false`.
2.  Panggil `device.gatt.disconnect()`.
3.  Panggil `device.forget()` untuk menghapus permission di browser.
4.  Nullify semua variabel internal (`device`, `characteristic`).

---

## 5. Struktur ESC/POS Dasar
Gunakan perintah biner (Uint8Array) untuk performa terbaik:

| Perintah | Hex | Deskripsi |
| :--- | :--- | :--- |
| **Initialize** | `1B 40` | Reset printer (wajib di awal) |
| **Bold ON** | `1B 45 01` | Tebalkan teks |
| **Bold OFF** | `1B 45 00` | Normalkan teks |
| **Double Height**| `1D 21 01` | Teks Tinggi (Cocok untuk Header 58mm) |
| **Double W+H**| `1D 21 11` | Teks Besar (Cocok untuk Header 80mm) |
| **Cut Paper** | `1D 56 00` | Potong kertas otomatis |

---

## 6. Contoh File Struktur
Untuk project baru, Anda perlu menyiapkan:
1.  `thermalPrinter.js`: Service utama berbasis Class/Singleton (Logic inti).
2.  `usePrinter.js`: Composable atau Hook untuk menghubungkan ke UI.
3.  `Settings.vue`: UI untuk scan, connect, dan pilih ukuran kertas (58/80mm).

---

## 7. Troubleshooting Tip
1.  **Gagal Konek setelah Refresh**: Pastikan ada jeda ~500ms di awal sebelum mencoba auto-connect agar hardware printer sempat "reset".
2.  **Device Not Found**: Pastikan browser dijalankan di **HTTPS** atau **Localhost**. Web Bluetooth tidak aktif di HTTP.
3.  **Teks Terpotong**: Periksa jumlah karakter per baris (58mm = 32 karakter, 80mm = 48 karakter).

---
*Dokumentasi ini dibuat berdasarkan implementasi sukses pada project Dimsum Sarang (2026).*
