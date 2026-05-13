# Rencana Implementasi QRIS Mandiri (Tanpa Pihak Ketiga)

Dokumen ini menjelaskan bagaimana menggunakan **QRIS pribadi** Anda (dari Bank/E-wallet sendiri) agar bisa tampil secara dinamis (nominal otomatis) di aplikasi POS Wartegkee.

## 1. Konsep Dasar: Menambahkan Nominal ke QRIS Statis
Meskipun QRIS Anda statis, ia mengikuti standar **QRIS (EMVCo)**. Kita bisa menggunakan kode program untuk mengambil data NMID (Merchant ID) Anda, lalu menyisipkan nominal transaksi ke dalamnya sehingga customer tidak perlu mengetik manual.

### Alur Kerja:
1.  **Ekstrak Data**: Kita ambil kode teks dari QRIS statis Anda (contoh: `00020101021126590013ID1...`).
2.  **Injeksi Nominal**: Saat Checkout, sistem mengambil total belanja (misal: Rp 15.500) dan menyisipkannya ke dalam kode tersebut.
3.  **Generate QR**: Aplikasi menampilkan QR baru di layar kasir.
4.  **Hasil**: Saat customer scan, di HP mereka akan langsung tertulis **"Wartegkee - Rp 15.500"**.

## 2. Pilihan Verifikasi (Cek Pembayaran)

Karena kita tidak pakai Midtrans/Xendit, aplikasi tidak bisa "tahu" secara otomatis jika uang sudah masuk. Berikut opsinya:

### Opsi A: Verifikasi Manual (Paling Simpel)
*   Sistem menampilkan QR dengan nominal yang pas.
*   Customer bayar dan menunjukkan bukti di HP.
*   Kasir menekan tombol **"Konfirmasi Bayar"** di aplikasi untuk menyelesaikan transaksi dan cetak struk.
*   *Kelebihan:* 100% Gratis, uang langsung masuk ke rekening.

### Opsi B: Pengecekan Mutasi (Otomatis)
*   Sistem menggunakan layanan pihak ketiga (seperti *Moota* atau *Cekmutasi*) yang hanya bertugas **membaca riwayat bank** Anda.
*   Sistem akan menggunakan **Kode Unik** (misal: total Rp 15.000 jadi Rp 15.003).
*   Jika layanan tersebut mendeteksi ada uang masuk Rp 15.003, aplikasi kasir Anda akan **otomatis menutup modal** dan menyatakan lunas.
*   *Kelebihan:* Terasa otomatis seperti Midtrans tanpa potongan biaya per transaksi (biasanya biaya langganan murah).

## 3. Langkah Implementasi Produk

1.  **Tahap 1: Setup Library QR**: Menginstruksikan Laravel untuk bisa mengolah kode QRIS (menggunakan logika CRC16).
2.  **Tahap 2: Modal Kasir**: Membuat tampilan modal baru di halaman POS yang muncul saat metode pembayaran "QRIS" dipilih.
3.  **Tahap 3: Timer & Instruksi**: Menambahkan hitung mundur 5-10 menit di layar sebagai panduan customer.

## 4. Keuntungan QRIS Mandiri
*   **Tanpa Potongan**: Uang masuk 100% utuh ke rekening Anda.
*   **Cepat**: Uang langsung masuk (Real-time Settlement), tidak perlu menunggu H+1 seperti Payment Gateway.
*   **Hemat**: Tidak ada biaya MDR (Merchant Discount Rate) yang biasanya 0.7%.

---

### Yang Perlu Anda Siapkan:
Jika Anda ingin saya mulai mengintegrasikan ini, saya butuh:
1.  **Teks QRIS Anda**: Silakan scan QRIS statis Anda pakai aplikasi QR Scanner biasa di HP, lalu copy-paste teks kodenya ke saya. (Kodenya biasanya panjang diawali `000201...`).

> [!IMPORTANT]
> Dengan sistem ini, Anda mendapatkan kemewahan QRIS dinamis (nominal otomatis) dengan biaya operasional Rp 0,-.
