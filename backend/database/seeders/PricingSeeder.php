<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PlatformSetting;

class PricingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Pricing Configuration
        PlatformSetting::setValue('plan_basic_price', '99000', 'platform', 'number', 'Harga paket Basic per bulan (IDR)');
        PlatformSetting::setValue('plan_pro_price', '249000', 'platform', 'number', 'Harga paket Pro per bulan (IDR)');
        
        // FREE Plan Features
        $freeFeatures = [
            '1 Akun (Owner)',
            'Kasir POS Desktop', 
            'Laporan Harian'
        ];
        PlatformSetting::setValue('plan_free_features', json_encode($freeFeatures), 'platform', 'json', 'Fitur paket Free');
        
        // BASIC Plan Features  
        $basicFeatures = [
            '2 Akun (Owner + Kasir)',
            'Pengaturan Stok Gudang',
            'Export Excel Laporan',
            'Google Sheets Sync'
        ];
        PlatformSetting::setValue('plan_basic_features', json_encode($basicFeatures), 'platform', 'json', 'Fitur paket Basic');
        
        // PRO Plan Features
        $proFeatures = [
            'Akun Tanpa Batas',
            'Resep & Stok Dapur', 
            'Inventory Report',
            'Support Prioritas'
        ];
        PlatformSetting::setValue('plan_pro_features', json_encode($proFeatures), 'platform', 'json', 'Fitur paket Pro');
        
        // Default Trial Period
        PlatformSetting::setValue('default_trial_days', '14', 'platform', 'number', 'Masa trial default (hari)');
        
        // App Configuration
        PlatformSetting::setValue('app_name', 'Kee POS Premium', 'platform', 'text', 'Nama aplikasi');
        PlatformSetting::setValue('app_tagline', 'Harga Transparan, Tanpa Biaya Tersembunyi', 'platform', 'text', 'Tagline aplikasi');
        PlatformSetting::setValue('app_whatsapp', '+6281234567890', 'platform', 'text', 'Nomor WhatsApp support');
        
        // Payment Gateway (Midtrans) - Default to sandbox
        PlatformSetting::setValue('midtrans_server_key', '', 'platform', 'text', 'Midtrans Server Key');
        PlatformSetting::setValue('midtrans_client_key', '', 'platform', 'text', 'Midtrans Client Key');
        PlatformSetting::setValue('midtrans_is_production', '0', 'platform', 'boolean', 'Midtrans Production Mode');
        
        $this->command->info('✅ Pricing configuration seeded successfully');
        $this->command->info('📊 FREE: Rp 0 - ' . count($freeFeatures) . ' features');
        $this->command->info('📊 BASIC: Rp 99,000 - ' . count($basicFeatures) . ' features');  
        $this->command->info('📊 PRO: Rp 249,000 - ' . count($proFeatures) . ' features');
    }
}