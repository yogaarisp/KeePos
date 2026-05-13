<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Category;
use App\Models\Unit;
use App\Models\StokGudang;
use App\Models\KitchenStock;
use App\Models\CategoryKasir;
use App\Models\Product;
use App\Models\CustomCategory;
use App\Models\CustomOption;
use App\Models\Table;
use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // --- Users ---
        User::create([
            'username' => 'admin',
            'email' => 'admin@wartegkee.com',
            'password' => Hash::make('admin123'),
            'full_name' => 'Administrator',
            'role' => 'admin',
            'is_active' => true,
        ]);
        User::create([
            'username' => 'kasir1',
            'email' => 'kasir1@wartegkee.com',
            'password' => Hash::make('kasir123'),
            'full_name' => 'Siti Kasir',
            'role' => 'kasir',
            'is_active' => true,
        ]);
        User::create([
            'username' => 'kasir2',
            'email' => 'kasir2@wartegkee.com',
            'password' => Hash::make('kasir123'),
            'full_name' => 'Budi Kasir',
            'role' => 'kasir',
            'is_active' => true,
        ]);

        // --- Categories (Warehouse/Kitchen) ---
        $catBumbu = Category::create(['name' => 'Bumbu', 'description' => 'Bumbu dapur', 'is_active' => true]);
        $catDaging = Category::create(['name' => 'Daging & Protein', 'description' => 'Daging, ikan, telur', 'is_active' => true]);
        $catSayur = Category::create(['name' => 'Sayuran', 'description' => 'Sayuran segar', 'is_active' => true]);
        $catBahan = Category::create(['name' => 'Bahan Pokok', 'description' => 'Beras, minyak, dll', 'is_active' => true]);
        $catMinuman = Category::create(['name' => 'Minuman', 'description' => 'Bahan minuman', 'is_active' => true]);

        // --- Units ---
        Unit::create(['name' => 'Kilogram', 'abbreviation' => 'kg']);
        Unit::create(['name' => 'Gram', 'abbreviation' => 'g']);
        Unit::create(['name' => 'Liter', 'abbreviation' => 'L']);
        Unit::create(['name' => 'Mililiter', 'abbreviation' => 'ml']);
        Unit::create(['name' => 'Pieces', 'abbreviation' => 'pcs']);
        Unit::create(['name' => 'Bungkus', 'abbreviation' => 'bks']);

        // --- Stok Gudang ---
        StokGudang::create(['name' => 'Beras', 'category_id' => $catBahan->id, 'stock' => 50, 'unit' => 'kg', 'price_per_unit' => 12000, 'min_stock' => 10]);
        StokGudang::create(['name' => 'Minyak Goreng', 'category_id' => $catBahan->id, 'stock' => 20, 'unit' => 'L', 'price_per_unit' => 18000, 'min_stock' => 5]);
        StokGudang::create(['name' => 'Ayam', 'category_id' => $catDaging->id, 'stock' => 15, 'unit' => 'kg', 'price_per_unit' => 35000, 'min_stock' => 5]);
        StokGudang::create(['name' => 'Ikan Lele', 'category_id' => $catDaging->id, 'stock' => 10, 'unit' => 'kg', 'price_per_unit' => 28000, 'min_stock' => 3]);
        StokGudang::create(['name' => 'Telur', 'category_id' => $catDaging->id, 'stock' => 100, 'unit' => 'pcs', 'price_per_unit' => 2500, 'min_stock' => 30]);
        StokGudang::create(['name' => 'Tempe', 'category_id' => $catDaging->id, 'stock' => 30, 'unit' => 'pcs', 'price_per_unit' => 3000, 'min_stock' => 10]);
        StokGudang::create(['name' => 'Tahu', 'category_id' => $catDaging->id, 'stock' => 30, 'unit' => 'pcs', 'price_per_unit' => 2000, 'min_stock' => 10]);
        StokGudang::create(['name' => 'Bawang Merah', 'category_id' => $catBumbu->id, 'stock' => 5, 'unit' => 'kg', 'price_per_unit' => 40000, 'min_stock' => 2]);
        StokGudang::create(['name' => 'Bawang Putih', 'category_id' => $catBumbu->id, 'stock' => 3, 'unit' => 'kg', 'price_per_unit' => 35000, 'min_stock' => 1]);
        StokGudang::create(['name' => 'Cabe Merah', 'category_id' => $catBumbu->id, 'stock' => 3, 'unit' => 'kg', 'price_per_unit' => 50000, 'min_stock' => 1]);
        StokGudang::create(['name' => 'Kangkung', 'category_id' => $catSayur->id, 'stock' => 20, 'unit' => 'pcs', 'price_per_unit' => 3000, 'min_stock' => 5]);
        StokGudang::create(['name' => 'Bayam', 'category_id' => $catSayur->id, 'stock' => 15, 'unit' => 'pcs', 'price_per_unit' => 3000, 'min_stock' => 5]);
        StokGudang::create(['name' => 'Teh Celup', 'category_id' => $catMinuman->id, 'stock' => 10, 'unit' => 'bks', 'price_per_unit' => 5000, 'min_stock' => 3]);
        StokGudang::create(['name' => 'Gula Pasir', 'category_id' => $catBahan->id, 'stock' => 10, 'unit' => 'kg', 'price_per_unit' => 15000, 'min_stock' => 3]);

        // --- Kitchen Stocks ---
        KitchenStock::create(['name' => 'Nasi Putih', 'category_id' => $catBahan->id, 'stock' => 10, 'unit' => 'kg', 'min_stock' => 3]);
        KitchenStock::create(['name' => 'Ayam Goreng', 'category_id' => $catDaging->id, 'stock' => 20, 'unit' => 'pcs', 'min_stock' => 5]);
        KitchenStock::create(['name' => 'Ikan Lele Goreng', 'category_id' => $catDaging->id, 'stock' => 15, 'unit' => 'pcs', 'min_stock' => 5]);
        KitchenStock::create(['name' => 'Tempe Goreng', 'category_id' => $catDaging->id, 'stock' => 25, 'unit' => 'pcs', 'min_stock' => 8]);
        KitchenStock::create(['name' => 'Sambal Merah', 'category_id' => $catBumbu->id, 'stock' => 2, 'unit' => 'kg', 'min_stock' => 0.5]);
        KitchenStock::create(['name' => 'Sayur Kangkung', 'category_id' => $catSayur->id, 'stock' => 10, 'unit' => 'porsi', 'min_stock' => 3]);

        // --- Categories Kasir ---
        $catMakanan = CategoryKasir::create(['name' => 'Makanan', 'icon' => 'utensils', 'sort_order' => 1, 'is_active' => true]);
        $catLauk = CategoryKasir::create(['name' => 'Lauk', 'icon' => 'drumstick', 'sort_order' => 2, 'is_active' => true]);
        $catMin = CategoryKasir::create(['name' => 'Minuman', 'icon' => 'cup-soda', 'sort_order' => 3, 'is_active' => true]);
        $catExtra = CategoryKasir::create(['name' => 'Extra', 'icon' => 'plus-circle', 'sort_order' => 4, 'is_active' => true]);

        // --- Products ---
        Product::create(['name' => 'Nasi Putih', 'price' => 5000, 'category_id' => $catMakanan->id, 'is_available' => true, 'sort_order' => 1]);
        Product::create(['name' => 'Nasi Uduk', 'price' => 8000, 'category_id' => $catMakanan->id, 'is_available' => true, 'sort_order' => 2]);
        Product::create(['name' => 'Nasi Goreng', 'price' => 15000, 'category_id' => $catMakanan->id, 'is_available' => true, 'sort_order' => 3]);
        Product::create(['name' => 'Ayam Goreng', 'price' => 12000, 'category_id' => $catLauk->id, 'is_available' => true, 'sort_order' => 1]);
        Product::create(['name' => 'Ayam Bakar', 'price' => 15000, 'category_id' => $catLauk->id, 'is_available' => true, 'sort_order' => 2]);
        Product::create(['name' => 'Ikan Lele Goreng', 'price' => 10000, 'category_id' => $catLauk->id, 'is_available' => true, 'sort_order' => 3]);
        Product::create(['name' => 'Tempe Goreng', 'price' => 3000, 'category_id' => $catLauk->id, 'is_available' => true, 'sort_order' => 4]);
        Product::create(['name' => 'Tahu Goreng', 'price' => 3000, 'category_id' => $catLauk->id, 'is_available' => true, 'sort_order' => 5]);
        Product::create(['name' => 'Telur Dadar', 'price' => 5000, 'category_id' => $catLauk->id, 'is_available' => true, 'sort_order' => 6]);
        Product::create(['name' => 'Telur Ceplok', 'price' => 5000, 'category_id' => $catLauk->id, 'is_available' => true, 'sort_order' => 7]);
        Product::create(['name' => 'Sayur Kangkung', 'price' => 5000, 'category_id' => $catLauk->id, 'is_available' => true, 'sort_order' => 8]);
        Product::create(['name' => 'Sayur Bayam', 'price' => 5000, 'category_id' => $catLauk->id, 'is_available' => true, 'sort_order' => 9]);
        Product::create(['name' => 'Es Teh Manis', 'price' => 5000, 'category_id' => $catMin->id, 'is_available' => true, 'sort_order' => 1]);
        Product::create(['name' => 'Teh Hangat', 'price' => 4000, 'category_id' => $catMin->id, 'is_available' => true, 'sort_order' => 2]);
        Product::create(['name' => 'Es Jeruk', 'price' => 6000, 'category_id' => $catMin->id, 'is_available' => true, 'sort_order' => 3]);
        Product::create(['name' => 'Kopi Hitam', 'price' => 5000, 'category_id' => $catMin->id, 'is_available' => true, 'sort_order' => 4]);
        Product::create(['name' => 'Air Mineral', 'price' => 3000, 'category_id' => $catMin->id, 'is_available' => true, 'sort_order' => 5]);
        Product::create(['name' => 'Kerupuk', 'price' => 2000, 'category_id' => $catExtra->id, 'is_available' => true, 'sort_order' => 1]);
        Product::create(['name' => 'Sambal Extra', 'price' => 2000, 'category_id' => $catExtra->id, 'is_available' => true, 'sort_order' => 2]);

        // --- Custom Categories & Options ---
        $ccLevel = CustomCategory::create(['name' => 'Level Pedas', 'type' => 'single', 'is_required' => false]);
        CustomOption::create(['custom_category_id' => $ccLevel->id, 'name' => 'Tidak Pedas', 'price' => 0, 'is_active' => true]);
        CustomOption::create(['custom_category_id' => $ccLevel->id, 'name' => 'Pedas Sedang', 'price' => 0, 'is_active' => true]);
        CustomOption::create(['custom_category_id' => $ccLevel->id, 'name' => 'Pedas Banget', 'price' => 0, 'is_active' => true]);

        $ccExtra = CustomCategory::create(['name' => 'Tambahan', 'type' => 'multiple', 'is_required' => false]);
        CustomOption::create(['custom_category_id' => $ccExtra->id, 'name' => 'Extra Nasi', 'price' => 3000, 'is_active' => true]);
        CustomOption::create(['custom_category_id' => $ccExtra->id, 'name' => 'Extra Sambal', 'price' => 2000, 'is_active' => true]);
        CustomOption::create(['custom_category_id' => $ccExtra->id, 'name' => 'Extra Kerupuk', 'price' => 2000, 'is_active' => true]);

        // --- Tables ---
        Table::create(['table_number' => '1', 'capacity' => 4, 'status' => 'available']);
        Table::create(['table_number' => '2', 'capacity' => 4, 'status' => 'available']);
        Table::create(['table_number' => '3', 'capacity' => 6, 'status' => 'available']);
        Table::create(['table_number' => '4', 'capacity' => 6, 'status' => 'available']);
        Table::create(['table_number' => '5', 'capacity' => 2, 'status' => 'available']);
        Table::create(['table_number' => '6', 'capacity' => 8, 'status' => 'available']);

        // --- Settings ---
        Setting::create(['key' => 'shop_name', 'value' => 'Wartegkee', 'group' => 'shop']);
        Setting::create(['key' => 'shop_address', 'value' => 'Jl. Contoh No. 123, Jakarta', 'group' => 'shop']);
        Setting::create(['key' => 'shop_phone', 'value' => '08123456789', 'group' => 'shop']);
        Setting::create(['key' => 'tax_percentage', 'value' => '0', 'group' => 'billing']);
        Setting::create(['key' => 'receipt_footer', 'value' => 'Terima kasih sudah mampir!', 'group' => 'billing']);
        Setting::create(['key' => 'printer_paper_size', 'value' => '58', 'group' => 'printer']);
    }
}
