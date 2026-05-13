# Rencana Implementasi Pengurangan Stok Otomatis

Rencana ini bertujuan untuk mengintegrasikan sistem Point of Sale (POS) dengan manajemen stok (Gudang/Dapur) agar stok berkurang secara otomatis setiap kali terjadi transaksi penjualan.

## 1. Analisis Struktur Data Saat Ini
- **Sales/Orders**: Disimpan di tabel `sales` dan `order_items`.
- **Products**: Tabel `products` (menu yang dijual).
- **Warehouse Stock**: Tabel `stock_gudang` (bahan baku utama).
- **Kitchen Stock**: Tabel `kitchen_stocks` (bahan baku di dapur).
- **Stock Service**: [StockService.php](file:///c:/laragon/www/wartegkee/backend/app/Services/StockService.php) sudah memiliki fungsi `reduceStock`.

## 2. Strategi Implementasi (Product-to-Stock Mapping)
Acuan utama pemetaan produk ke stok adalah fitur **Recipes (Resep)** yang dapat dikelola melalui halaman `http://localhost:3000/recipes`.

### Struktur Data Resep:
- **Recipe Model**: Terhubung ke `Product` (menu POS).
- **RecipeItem Model**: Mendefinisikan bahan baku untuk resep tersebut.
  - `ingredient_type`: Bisa berupa `gudang` (StokGudang) atau `kitchen` (KitchenStock).
  - `ingredient_id`: ID dari item stok terkait.
  - `quantity`: Jumlah bahan yang digunakan per porsi.

## 3. Alur Proses Baru (Checkout)
1. User melakukan **Checkout** di POS.
2. `POSService->processOrder()` dijalankan.
3. Setelah menyimpan `OrderItem`:
   - Cari `Recipe` yang aktif (`is_active = true`) untuk `product_id` tersebut.
   - Jika resep ditemukan, ambil semua `RecipeItem` (bahan-bahannya).
   - Untuk setiap bahan:
     - Hitung total kebutuhan: `recipe_item.quantity * order_item.quantity`.
     - Jika `ingredient_type == 'kitchen'`, kurangi stok di dapur menggunakan `KitchenStockTransaction`.
     - Jika `ingredient_type == 'gudang'`, kurangi stok di gudang menggunakan `StockService->reduceStock()`.
     - Catat transaksi stok dengan keterangan "Penjualan POS: [Invoice Number]".
4. Proses checkout tetap berlanjut meskipun stok tidak mencukupi (stok menjadi negatif), namun tetap mencatatkan histori transaksi keluar.

## 4. Langkah Kerja (To-Do List)
- [x] Analisis struktur `Recipe` dan `RecipeItem` yang sudah ada.
- [ ] Implementasi logika pencarian resep di `POSService.php`.
- [ ] Integrasi pengurangan stok dapur (`kitchen`) dan gudang (`gudang`) di dalam loop item order.
- [ ] Tambahkan logging transaksi stok yang jelas (Invoice Ref).
- [ ] Verifikasi pengurangan stok di dashboard Gudang dan Dapur setelah transaksi POS dilakukan.

---
*Dibuat pada: 2026-03-01*
