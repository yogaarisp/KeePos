<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\PlatformSetting;
use App\Models\TenantSetting;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Models\Product;
use App\Models\StokGudang;
use App\Models\KitchenStock;
use App\Models\Recipe;
use App\Models\Sale;
use App\Services\GoogleSheetService;
use App\Services\PlanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        
        if ($user->role === 'superadmin') {
             // Superadmin sees platform-wide settings
             $settings = PlatformSetting::all();
             $profile = null;
        } else {
             // Tenant user: get tenant-specific settings
             $settings = TenantSetting::all();
             
             // Get tenant profile - always ensure it exists
             $profile = $user->tenant ? $user->tenant->getOrCreateProfile() : null;
        }

        $paymentMethods = PaymentMethod::orderBy('sort_order')->get();
        
        return response()->json([
            'success' => true,
            'data' => [
                'settings' => $settings,
                'payment_methods' => $paymentMethods,
                'tenant' => $user->tenant,
                'profile' => $profile ? [
                    'shop_name'     => $profile->shop_name,
                    'shop_logo'     => $profile->shop_logo,
                    'shop_favicon'  => $profile->shop_favicon,
                    'shop_tagline'  => $profile->shop_tagline,
                    'shop_address'  => $profile->shop_address,
                    'shop_phone'    => $profile->shop_phone,
                    'shop_email'    => $profile->shop_email,
                    'primary_color' => $profile->primary_color,
                    'secondary_color' => $profile->secondary_color,
                    'receipt_header' => $profile->receipt_header,
                    'receipt_footer' => $profile->receipt_footer,
                    'show_logo_on_receipt' => $profile->show_logo_on_receipt,
                ] : null
            ]
        ]);
    }

    public function publicSettings()
    {
        $tenantId = config('app.current_tenant_id');
        $platformName = PlatformSetting::getValue('app_name', 'Kee POS');
        $platformLogo = PlatformSetting::getValue('app_logo');
        $platformFavicon = PlatformSetting::getValue('app_favicon');
        $basicPrice = PlatformSetting::getValue('plan_basic_price', 149000);
        $proPrice = PlatformSetting::getValue('plan_pro_price', 299000);
        
        if ($tenantId) {
            // Get tenant profile
            $tenant = \App\Models\Tenant::with('profile')->find($tenantId);
            $profile = $tenant ? $tenant->getOrCreateProfile() : null;
            
            return response()->json([
                'success' => true,
                'data' => [
                    // Dari tenant profile (prioritas)
                    'shop_name' => $profile->shop_name ?? $tenant->name ?? $platformName,
                    'shop_logo' => $profile->shop_logo,
                    'shop_favicon' => $profile->shop_favicon ?: $platformFavicon,
                    'shop_tagline' => $profile->shop_tagline ?? 'Smart POS System',
                    'shop_address' => $profile->shop_address,
                    'shop_phone' => $profile->shop_phone,
                    'shop_email' => $profile->shop_email,
                    
                    // Platform info
                    'app_name' => $platformName,
                    'app_logo' => $platformLogo,
                    'app_favicon' => $platformFavicon,
                    'plan_basic_price' => $basicPrice,
                    'plan_pro_price' => $proPrice,
                    
                    // Tenant info
                    'tenant_name' => $tenant->name ?? null,
                    'tenant_slug' => $tenant->slug ?? null,
                    'tenant_plan' => $tenant->plan ?? 'free',
                ]
            ]);
        }

        // Platform level (Landing Page)
        return response()->json([
            'success' => true,
            'data' => [
                'shop_name' => $platformName,
                'shop_logo' => $platformLogo,
                'shop_favicon' => $platformFavicon,
                'shop_tagline' => 'Cloud POS & SaaS Management System',
                'app_name' => $platformName,
                'app_logo' => $platformLogo,
                'app_favicon' => $platformFavicon,
                'plan_basic_price' => $basicPrice,
                'plan_pro_price' => $proPrice,
                'plan_free_features' => json_decode(PlatformSetting::getValue('plan_free_features', '[]'), true),
                'plan_basic_features' => json_decode(PlatformSetting::getValue('plan_basic_features', '[]'), true),
                'plan_pro_features' => json_decode(PlatformSetting::getValue('plan_pro_features', '[]'), true),
                'default_trial_days' => (int)PlatformSetting::getValue('default_trial_days', 20),
                'app_whatsapp' => PlatformSetting::getValue('app_whatsapp', ''),
            ]
        ]);
    }

    public function update(Request $request)
    {
        $user = auth()->user();
        $settingsData = json_decode($request->input('settings', '[]'), true);
        
        // Update tenant profile
        if ($user->tenant) {
            $profile = $user->tenant->getOrCreateProfile();
            
            $profileData = [];
            
            // Ambil data profil dari settings
            if (isset($settingsData['shop_name'])) {
                $profileData['shop_name'] = $settingsData['shop_name'];
                unset($settingsData['shop_name']);
            }
            if (isset($settingsData['shop_tagline'])) {
                $profileData['shop_tagline'] = $settingsData['shop_tagline'];
                unset($settingsData['shop_tagline']);
            }
            if (isset($settingsData['shop_address'])) {
                $profileData['shop_address'] = $settingsData['shop_address'];
                unset($settingsData['shop_address']);
            }
            if (isset($settingsData['shop_phone'])) {
                $profileData['shop_phone'] = $settingsData['shop_phone'];
                unset($settingsData['shop_phone']);
            }
            if (isset($settingsData['shop_email'])) {
                $profileData['shop_email'] = $settingsData['shop_email'];
                unset($settingsData['shop_email']);
            }
            if (isset($settingsData['shop_favicon'])) {
                $profileData['shop_favicon'] = $settingsData['shop_favicon'];
                unset($settingsData['shop_favicon']);
            }
            
            // Update profile jika ada data
            if (!empty($profileData)) {
                $profile->update($profileData);
            }
        }
        
        // Handle file uploads untuk profile
        if ($request->hasFile('shop_logo')) {
            if ($user->tenant) {
                $profile = $user->tenant->getOrCreateProfile();
                
                // Hapus logo lama jika ada
                if ($profile->shop_logo && \Storage::disk('public')->exists($profile->shop_logo)) {
                    \Storage::disk('public')->delete($profile->shop_logo);
                }
                
                $path = $request->file('shop_logo')->store('tenants/logos', 'public');
                $profile->update(['shop_logo' => $path]);
            }
        }
        
        if ($request->hasFile('shop_favicon')) {
            if ($user->tenant) {
                $profile = $user->tenant->getOrCreateProfile();
                
                // Hapus favicon lama jika ada
                if ($profile->shop_favicon && \Storage::disk('public')->exists($profile->shop_favicon)) {
                    \Storage::disk('public')->delete($profile->shop_favicon);
                }
                
                $path = $request->file('shop_favicon')->store('tenants/favicons', 'public');
                $profile->update(['shop_favicon' => $path]);
            }
        }

        // Update tenant settings (konfigurasi teknis)
        foreach ($settingsData as $key => $value) {
            $existing = TenantSetting::where('key', $key)->first();
            $group = $existing ? $existing->group : 'general';
            
            // Map specific keys to groups
            if (strpos($key, 'google_') === 0) {
                $group = 'googlesheet';
            } elseif (strpos($key, 'tax_') === 0) {
                $group = 'tax';
            } elseif (strpos($key, 'printer_') === 0) {
                $group = 'printer';
            } elseif (strpos($key, 'smtp_') === 0) {
                $group = 'email';
            }

            TenantSetting::setValue($key, $value, $group);
        }

        return response()->json([
            'success' => true,
            'message' => 'Pengaturan berhasil diperbarui'
        ]);
    }

    public function storePaymentMethod(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|string|max:50',
            'account_number' => 'nullable|string|max:100',
            'account_name' => 'nullable|string|max:100',
            'sort_order' => 'nullable|integer',
        ]);

        $method = PaymentMethod::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Metode pembayaran berhasil ditambahkan',
            'data' => $method
        ]);
    }

    public function updatePaymentMethod(Request $request, $id)
    {
        $method = PaymentMethod::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|string|max:50',
            'account_number' => 'nullable|string|max:100',
            'account_name' => 'nullable|string|max:100',
            'is_active' => 'required|boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $method->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Metode pembayaran berhasil diperbarui',
            'data' => $method
        ]);
    }

    public function destroyPaymentMethod($id)
    {
        $method = PaymentMethod::findOrFail($id);
        $method->delete();

        return response()->json([
            'success' => true,
            'message' => 'Metode pembayaran berhasil dihapus'
        ]);
    }

    public function exportDatabase()
    {
        $user = auth()->user();
        if (!$user->tenant_id) {
            return response()->json(['success' => false, 'message' => 'Hanya Admin Toko yang dapat mengekspor data toko.'], 403);
        }

        $tenantId = $user->tenant_id;
        $filename = 'backup-toko-' . $user->tenant->slug . '-' . date('Y-m-d-H-i-s') . '.sql';
        $path = storage_path('app/backups/' . $filename);

        if (!is_dir(storage_path('app/backups'))) {
            mkdir(storage_path('app/backups'), 0755, true);
        }
        
        $dbHost = config('database.connections.mysql.host', '127.0.0.1');
        $dbPort = config('database.connections.mysql.port', 3306);
        $dbName = config('database.connections.mysql.database');
        $dbUser = config('database.connections.mysql.username');
        $dbPass = config('database.connections.mysql.password');

        // Locate mysqldump
        $mysqldump = 'mysqldump';
        if (PHP_OS_FAMILY === 'Windows') {
            $where = shell_exec('where mysqldump 2>nul');
            if (!$where && is_dir('C:\laragon\bin\mysql')) {
                exec('dir /s /b C:\laragon\bin\mysql\mysqldump.exe', $out);
                if (!empty($out[0])) { $mysqldump = '"' . trim($out[0]) . '"'; }
            }
        }

        $tenantTables = [
            'audit_logs', 'categories', 'categories_product', 'custom_categories', 'custom_options',
            'kitchen_unit_conversions', 'order_customizations', 'order_items', 'payment_methods',
            'product_custom_categories', 'production_recipes', 'production_transactions', 'products',
            'recipe_items', 'recipes', 'sale_missing_recipes', 'sales', 'shift_transactions',
            'shifts', 'stock_dapur', 'stock_dapur_transactions', 'stock_gudang', 'stock_transactions',
            'subscription_invoices', 'subscriptions', 'suppliers', 'tables', 'tenant_profiles',
            'tenant_settings', 'unit_conversions', 'units', 'users', 'waste_reports'
        ];

        $passArg = !empty($dbPass) ? "--password=" . escapeshellarg($dbPass) : "";
        
        // Dump tenant-specific rows only
        $command = sprintf(
            '%s --user=%s %s --host=%s --port=%s --no-create-info --single-transaction --where="tenant_id=%s" %s %s > %s 2>&1',
            $mysqldump,
            escapeshellarg($dbUser),
            $passArg,
            escapeshellarg($dbHost),
            escapeshellarg($dbPort),
            $tenantId,
            escapeshellarg($dbName),
            implode(' ', $tenantTables),
            escapeshellarg($path)
        );

        exec($command, $output, $returnVar);

        if ($returnVar !== 0 || !file_exists($path) || filesize($path) === 0) {
            if (file_exists($path)) @unlink($path);
            return response()->json([
                'success' => false,
                'message' => 'Gagal ekspor data toko.',
                'debug' => $output
            ], 500);
        }

        return response()->download($path, $filename, [
            'Content-Type' => 'application/sql',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Access-Control-Expose-Headers' => 'Content-Disposition'
        ])->deleteFileAfterSend(true);
    }

    public function importDatabase(Request $request)
    {
        $user = auth()->user();
        if (!$user->tenant_id) {
            return response()->json(['success' => false, 'message' => 'Hanya Admin Toko yang dapat memulihkan data toko.'], 403);
        }

        $request->validate(['database_file' => 'required|file']);
        $tenantId = $user->tenant_id;
        $path = $request->file('database_file')->store('temp');
        $fullPath = storage_path('app/' . $path);

        $dbHost = config('database.connections.mysql.host', '127.0.0.1');
        $dbPort = config('database.connections.mysql.port', 3306);
        $dbName = config('database.connections.mysql.database');
        $dbUser = config('database.connections.mysql.username');
        $dbPass = config('database.connections.mysql.password');

        $mysql = 'mysql';
        if (PHP_OS_FAMILY === 'Windows') {
            $where = shell_exec('where mysql 2>nul');
            if (!$where && is_dir('C:\laragon\bin\mysql')) {
                exec('dir /s /b C:\laragon\bin\mysql\mysql.exe', $out);
                if (!empty($out[0])) { $mysql = '"' . trim($out[0]) . '"'; }
            }
        }

        $tenantTables = [
            'audit_logs', 'categories', 'categories_product', 'custom_categories', 'custom_options',
            'kitchen_unit_conversions', 'order_customizations', 'order_items', 'payment_methods',
            'product_custom_categories', 'production_recipes', 'production_transactions', 'products',
            'recipe_items', 'recipes', 'sale_missing_recipes', 'sales', 'shift_transactions',
            'shifts', 'stock_dapur', 'stock_dapur_transactions', 'stock_gudang', 'stock_transactions',
            'subscription_invoices', 'subscriptions', 'suppliers', 'tables', 'tenant_profiles',
            'tenant_settings', 'unit_conversions', 'units', 'users', 'waste_reports'
        ];

        try {
            \DB::beginTransaction();
            foreach ($tenantTables as $table) {
                if ($table === 'users') {
                    \DB::table($table)->where('tenant_id', $tenantId)->where('id', '!=', $user->id)->delete();
                } else {
                    \DB::table($table)->where('tenant_id', $tenantId)->delete();
                }
            }
            \DB::commit();

            $passArg = !empty($dbPass) ? "--password=" . escapeshellarg($dbPass) : "";
            $command = sprintf(
                '%s --user=%s %s --host=%s --port=%s %s < %s 2>&1',
                $mysql,
                escapeshellarg($dbUser),
                $passArg,
                escapeshellarg($dbHost),
                escapeshellarg($dbPort),
                escapeshellarg($dbName),
                escapeshellarg($fullPath)
            );

            exec($command, $output, $returnVar);
            @unlink($fullPath);

            if ($returnVar !== 0) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Gagal memulihkan data toko.',
                    'debug' => $output
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Data toko berhasil dipulihkan.'
            ]);

        } catch (\Exception $e) {
            \DB::rollBack();
            @unlink($fullPath);
            return response()->json([
                'success' => false,
                'message' => 'Gagal merestore data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function syncGoogleSheet(GoogleSheetService $gsService)
    {
        try {
            if (!$gsService->isEnabled()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sinkronisasi Google Sheets tidak aktif atau konfigurasi belum lengkap.'
                ], 400);
            }

            // Sync Products
            $products = Product::with('category')->get();
            $gsService->syncProducts($products);

            // Sync Stocks (Warehouse & Kitchen)
            $warehouseStocks = StokGudang::with('unitConversions')->get();
            $gsService->syncStock($warehouseStocks, 'Warehouse');

            $kitchenStocks = KitchenStock::with('conversions')->get();
            $gsService->syncStock($kitchenStocks, 'Kitchen');

            // Sync Recipes
            $recipes = Recipe::with(['product', 'items'])->get();
            $gsService->syncRecipes($recipes);

            // Sync recent sales (last 100 for example)
            $sales = Sale::with(['user', 'items.product'])
                ->orderBy('created_at', 'desc')
                ->limit(100)
                ->get();
            
            $gsService->syncTransactions($sales);

            return response()->json([
                'success' => true,
                'message' => 'Sinkronisasi seluruh data ke Google Sheets berhasil dimulai!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal sinkronisasi: ' . $e->getMessage()
            ], 500);
        }
    }

    public function syncInventoryToGSheet(Request $request, GoogleSheetService $gsService)
    {
        try {
            if (!$gsService->isEnabled()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sinkronisasi Google Sheets tidak aktif.'
                ], 400);
            }

            $transactions = $request->input('transactions', []);
            if (empty($transactions)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada data transaksi untuk disinkronkan.'
                ], 400);
            }

            $gsService->syncInventory($transactions);

            return response()->json([
                'success' => true,
                'message' => 'Laporan Inventori berhasil disinkronkan ke Google Sheets!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal sinkronisasi: ' . $e->getMessage()
            ], 500);
        }
    }
    public function getBillingStats()
    {
        $tenant = auth()->user()->tenant;
        
        if (!$tenant) {
            return response()->json([
                'success' => true,
                'data' => [
                    'plan' => 'superadmin',
                    'plan_name' => 'Super Administrator',
                    'user_count' => User::count(),
                    'max_users' => 'unlimited',
                    'product_count' => Product::count(),
                    'max_products' => 'unlimited',
                    'subscription_ends_at' => null,
                    'trial_ends_at' => null,
                ]
            ]);
        }

        $userCount = User::count();
        $productCount = Product::count();
        
        $planData = PlanService::PLANS[$tenant->plan] ?? PlanService::PLANS['free'];
        
        return response()->json([
            'success' => true,
            'data' => [
                'plan' => $tenant->plan,
                'plan_name' => $planData['name'],
                'user_count' => $userCount,
                'max_users' => $planData['max_users'],
                'product_count' => $productCount,
                'max_products' => $planData['max_products'],
                'subscription_ends_at' => $tenant->subscription_ends_at,
                'trial_ends_at' => $tenant->trial_ends_at,
            ]
        ]);
    }

    public function getSaaSConfig()
    {
        // Helper to safely decode JSON features
        $decodeFeatures = function(string $key, array $default = []) {
            $raw = PlatformSetting::getValue($key);
            if (!$raw) return $default;
            $decoded = json_decode($raw, true);
            return (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : $default;
        };

        return response()->json([
            'success' => true,
            'data' => [
                // Identity
                'app_name'              => PlatformSetting::getValue('app_name', 'Kee POS'),
                'app_whatsapp'          => PlatformSetting::getValue('app_whatsapp', ''),
                'app_logo'              => PlatformSetting::getValue('app_logo'),
                'app_favicon'           => PlatformSetting::getValue('app_favicon'),

                // Payment Gateway
                'midtrans_server_key'   => PlatformSetting::getValue('midtrans_server_key', ''),
                'midtrans_client_key'   => PlatformSetting::getValue('midtrans_client_key', ''),
                'midtrans_is_production'=> PlatformSetting::getValue('midtrans_is_production', '0'),

                // Pricing
                'plan_basic_price'      => (int) PlatformSetting::getValue('plan_basic_price', 99000),
                'plan_pro_price'        => (int) PlatformSetting::getValue('plan_pro_price', 299000),
                'default_trial_days'    => (int) PlatformSetting::getValue('default_trial_days', 20),

                // Features (always return array)
                'plan_free_features'    => $decodeFeatures('plan_free_features', ['1 Akun (Owner)', 'Kasir POS Desktop', 'Laporan Harian']),
                'plan_basic_features'   => $decodeFeatures('plan_basic_features', ['2 Akun (Owner + Kasir)', 'Pengaturan Stok Gudang', 'Export Excel Laporan', 'Google Sheets Sync']),
                'plan_pro_features'     => $decodeFeatures('plan_pro_features', ['Akun Tanpa Batas', 'Resep & Stok Dapur', 'Inventory Report', 'Support Prioritas']),

                // SMTP
                'smtp_host'             => PlatformSetting::getValue('smtp_host', ''),
                'smtp_port'             => PlatformSetting::getValue('smtp_port', '587'),
                'smtp_username'         => PlatformSetting::getValue('smtp_username', ''),
                'smtp_password'         => PlatformSetting::getValue('smtp_password', ''),
                'smtp_encryption'       => PlatformSetting::getValue('smtp_encryption', 'tls'),
                'smtp_from_address'     => PlatformSetting::getValue('smtp_from_address', ''),
                'smtp_from_name'        => PlatformSetting::getValue('smtp_from_name', ''),
            ]
        ]);
    }

    public function updateSaaSConfig(Request $request)
    {
        // No required validation - allow partial updates from any tab
        $request->validate([
            'app_name' => 'nullable|string|max:100',
        ]);

        // Simple key-value fields
        $keys = [
            'app_name' => 'platform',
            'midtrans_server_key' => 'platform',
            'midtrans_client_key' => 'platform',
            'midtrans_is_production' => 'platform',
            'plan_basic_price' => 'platform',
            'plan_pro_price' => 'platform',
            'default_trial_days' => 'platform',
            'app_whatsapp' => 'platform',
            'smtp_host' => 'email',
            'smtp_port' => 'email',
            'smtp_username' => 'email',
            'smtp_password' => 'email',
            'smtp_encryption' => 'email',
            'smtp_from_address' => 'email',
            'smtp_from_name' => 'email',
        ];

        foreach ($keys as $key => $group) {
            if ($request->has($key)) {
                PlatformSetting::setValue($key, $request->get($key), $group);
            }
        }

        // Feature arrays - ensure stored as valid JSON string
        $featureKeys = ['plan_free_features', 'plan_basic_features', 'plan_pro_features'];
        foreach ($featureKeys as $key) {
            if ($request->has($key)) {
                $value = $request->get($key);
                // If already a string, validate it's valid JSON; if array, encode it
                if (is_array($value)) {
                    $value = json_encode(array_values(array_filter($value, fn($v) => $v !== '')));
                } elseif (is_string($value)) {
                    // Validate it's valid JSON, fallback to empty array
                    $decoded = json_decode($value, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $value = '[]';
                    } else {
                        // Re-encode to ensure clean JSON (filter empty strings)
                        $value = json_encode(array_values(array_filter($decoded, fn($v) => $v !== '')));
                    }
                } else {
                    $value = '[]';
                }
                PlatformSetting::setValue($key, $value, 'platform');
            }
        }

        if ($request->hasFile('app_logo')) {
            $file = $request->file('app_logo');
            
            // 1. Simpan ke storage (untuk ditampilkan di UI)
            $path = $file->store('platform', 'public');
            PlatformSetting::setValue('app_logo', $path, 'platform');
            
            // 2. Replace file fisik di public/ agar favicon & PWA icon ikut update
            $publicPath = public_path();
            $file->move($publicPath, 'logo-192.png');
            
            // Copy juga sebagai logo-512.png dan apple-touch-icon.png
            copy($publicPath . '/logo-192.png', $publicPath . '/logo-512.png');
            copy($publicPath . '/logo-192.png', $publicPath . '/apple-touch-icon.png');
        }

        if ($request->hasFile('app_favicon')) {
            $file = $request->file('app_favicon');
            
            // 1. Simpan ke storage
            $path = $file->store('platform', 'public');
            PlatformSetting::setValue('app_favicon', $path, 'platform');
            
            // 2. Replace favicon.ico di public/
            $publicPath = public_path();
            $ext = strtolower($file->getClientOriginalExtension());
            
            if ($ext === 'ico') {
                // Langsung replace favicon.ico
                copy(\Storage::disk('public')->path($path), $publicPath . '/favicon.ico');
            } else {
                // PNG/JPG: simpan sebagai favicon.png dan update link di index.html tidak perlu
                // karena index.html sudah pakai logo-192.png sebagai fallback
                copy(\Storage::disk('public')->path($path), $publicPath . '/favicon.png');
                // Juga replace logo-192 jika belum ada logo upload
                if (!$request->hasFile('app_logo')) {
                    copy(\Storage::disk('public')->path($path), $publicPath . '/logo-192.png');
                    copy(\Storage::disk('public')->path($path), $publicPath . '/apple-touch-icon.png');
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Konfigurasi SaaS berhasil diperbarui'
        ]);
    }

    public function testSMTP(Request $request)
    {
        $request->validate([
            'smtp_host' => 'required',
            'smtp_port' => 'required',
            'smtp_username' => 'required',
            'smtp_password' => 'required',
            'smtp_encryption' => 'nullable',
            'smtp_from_address' => 'required|email',
            'smtp_from_name' => 'required',
            'recipient' => 'nullable|email',
        ]);

        try {
            $recipient = $request->recipient ?? $request->smtp_from_address;
            
            config([
                'mail.default' => 'smtp',
                'mail.mailers.smtp.host' => $request->smtp_host,
                'mail.mailers.smtp.port' => $request->smtp_port,
                'mail.mailers.smtp.username' => $request->smtp_username,
                'mail.mailers.smtp.password' => $request->smtp_password,
                'mail.mailers.smtp.encryption' => $request->smtp_encryption,
                'mail.from.address' => $request->smtp_from_address,
                'mail.from.name' => $request->smtp_from_name,
            ]);

            \Illuminate\Support\Facades\Mail::raw('Koneksi SMTP Berhasil! Ini adalah email percobaan dari sistem Kee POS SaaS.', function ($message) use ($recipient) {
                $message->to($recipient)
                    ->subject('Tes Koneksi SMTP - Kee POS SaaS');
            });

            return response()->json([
                'success' => true,
                'message' => 'Koneksi Berhasil! Email percobaan telah dikirim ke ' . $recipient
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghubungkan ke server SMTP: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Backup full database (superadmin only)
     */
    public function backupFullDatabase()
    {
        $filename = 'full-backup-' . date('Y-m-d_H-i-s') . '.sql';
        $path = storage_path('app/backups');

        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }

        $filePath = $path . '/' . $filename;

        $dbHost = config('database.connections.mysql.host', '127.0.0.1');
        $dbPort = config('database.connections.mysql.port', 3306);
        $dbName = config('database.connections.mysql.database');
        $dbUser = config('database.connections.mysql.username');
        $dbPass = config('database.connections.mysql.password');

        // Locate mysqldump
        $mysqldump = 'mysqldump';
        if (PHP_OS_FAMILY === 'Windows') {
            $where = shell_exec('where mysqldump 2>nul');
            if (!$where && is_dir('C:\laragon\bin\mysql')) {
                // Try to find it in Laragon
                exec('dir /s /b C:\laragon\bin\mysql\mysqldump.exe', $out);
                if (!empty($out[0])) {
                    $mysqldump = '"' . trim($out[0]) . '"';
                }
            }
        }

        $passArg = !empty($dbPass) ? "--password=" . escapeshellarg($dbPass) : "";

        $command = sprintf(
            '%s --user=%s %s --host=%s --port=%s --single-transaction --routines --triggers --add-drop-table %s > %s 2>&1',
            $mysqldump,
            escapeshellarg($dbUser),
            $passArg,
            escapeshellarg($dbHost),
            escapeshellarg($dbPort),
            escapeshellarg($dbName),
            escapeshellarg($filePath)
        );

        exec($command, $output, $returnVar);

        if ($returnVar !== 0 || !file_exists($filePath) || filesize($filePath) === 0) {
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat backup. ' . ($output[0] ?? 'Pastikan mysqldump tersedia.'),
                'debug' => $output
            ], 500);
        }

        return response()->download($filePath, $filename, [
            'Content-Type' => 'application/sql',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Access-Control-Expose-Headers' => 'Content-Disposition'
        ])->deleteFileAfterSend(true);
    }

    /**
     * Restore database from SQL file (superadmin only)
     */
    public function restoreDatabase(Request $request)
    {
        $request->validate([
            'sql_file' => 'required|file|max:102400', // max 100MB
        ]);

        $file = $request->file('sql_file');
        $ext = strtolower($file->getClientOriginalExtension());

        if (!in_array($ext, ['sql'])) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya file .sql yang diperbolehkan.'
            ], 422);
        }

        $path = $file->store('temp', 'local');
        $fullPath = storage_path('app/' . $path);

        $dbHost = config('database.connections.mysql.host');
        $dbPort = config('database.connections.mysql.port', 3306);
        $dbName = config('database.connections.mysql.database');
        $dbUser = config('database.connections.mysql.username');
        $dbPass = config('database.connections.mysql.password');

        // Locate mysql binary
        $mysql = 'mysql';
        if (PHP_OS_FAMILY === 'Windows') {
            $where = shell_exec('where mysql 2>nul');
            if (!$where && is_dir('C:\laragon\bin\mysql')) {
                exec('dir /s /b C:\laragon\bin\mysql\mysql.exe', $out);
                if (!empty($out[0])) {
                    $mysql = '"' . trim($out[0]) . '"';
                }
            }
        }

        $passArg = !empty($dbPass) ? "--password=" . escapeshellarg($dbPass) : "";

        $command = sprintf(
            '%s --user=%s %s --host=%s --port=%s %s < %s 2>&1',
            $mysql,
            escapeshellarg($dbUser),
            $passArg,
            escapeshellarg($dbHost),
            escapeshellarg($dbPort),
            escapeshellarg($dbName),
            escapeshellarg($fullPath)
        );

        exec($command, $output, $returnVar);
        @unlink($fullPath);

        if ($returnVar !== 0) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal restore. ' . ($output[0] ?? 'Cek koneksi database.'),
                'debug' => $output
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Database berhasil di-restore dari file backup.'
        ]);
    }

    /**
     * Get system information (superadmin only)
     */
    public function systemInfo()
    {
        // Database size
        $dbName = config('database.connections.mysql.database');
        $dbSize = 0;
        try {
            $result = \DB::select("SELECT SUM(data_length + index_length) as size FROM information_schema.TABLES WHERE table_schema = ?", [$dbName]);
            $dbSize = $result[0]->size ?? 0;
        } catch (\Exception $e) {}

        // Table count
        $tableCount = 0;
        try {
            $tables = \DB::select("SELECT COUNT(*) as cnt FROM information_schema.TABLES WHERE table_schema = ?", [$dbName]);
            $tableCount = $tables[0]->cnt ?? 0;
        } catch (\Exception $e) {}

        // Disk usage of storage folder
        $storagePath = storage_path();
        $storageSize = 0;
        try {
            if (PHP_OS_FAMILY === 'Windows') {
                // Windows: use dir command
                exec('dir /s /a "' . $storagePath . '" 2>nul', $dirOutput);
                $lastLine = end($dirOutput);
                if (preg_match('/([\d,\.]+)\s+bytes/', $lastLine, $m)) {
                    $storageSize = (int) str_replace([',', '.'], '', $m[1]);
                }
            } else {
                exec('du -sb ' . escapeshellarg($storagePath) . ' 2>/dev/null', $duOutput);
                if (!empty($duOutput[0])) {
                    $storageSize = (int) explode("\t", $duOutput[0])[0];
                }
            }
        } catch (\Exception $e) {}

        // Record counts
        $totalUsers = \App\Models\User::count();
        $totalTenants = \App\Models\Tenant::count();
        $totalProducts = \App\Models\Product::count();
        $totalOrders = \App\Models\Sale::count();

        return response()->json([
            'success' => true,
            'data' => [
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'server_os' => PHP_OS_FAMILY . ' ' . php_uname('r'),
                'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'CLI',
                'db_driver' => config('database.default'),
                'db_name' => $dbName,
                'db_size' => $dbSize,
                'db_size_formatted' => $this->formatBytes($dbSize),
                'table_count' => $tableCount,
                'storage_size' => $storageSize,
                'storage_size_formatted' => $this->formatBytes($storageSize),
                'total_users' => $totalUsers,
                'total_tenants' => $totalTenants,
                'total_products' => $totalProducts,
                'total_orders' => $totalOrders,
                'cache_driver' => config('cache.default'),
                'session_driver' => config('session.driver'),
                'queue_driver' => config('queue.default'),
                'app_env' => config('app.env'),
                'app_debug' => config('app.debug'),
                'timezone' => config('app.timezone'),
                'max_upload' => ini_get('upload_max_filesize'),
                'memory_limit' => ini_get('memory_limit'),
            ]
        ]);
    }

    /**
     * Clear application caches (superadmin only)
     */
    public function clearCache()
    {
        $results = [];

        try {
            \Artisan::call('cache:clear');
            $results[] = 'Application cache cleared';
        } catch (\Exception $e) {
            $results[] = 'Cache clear failed: ' . $e->getMessage();
        }

        try {
            \Artisan::call('config:clear');
            $results[] = 'Config cache cleared';
        } catch (\Exception $e) {
            $results[] = 'Config clear failed: ' . $e->getMessage();
        }

        try {
            \Artisan::call('route:clear');
            $results[] = 'Route cache cleared';
        } catch (\Exception $e) {
            $results[] = 'Route clear failed: ' . $e->getMessage();
        }

        try {
            \Artisan::call('view:clear');
            $results[] = 'View cache cleared';
        } catch (\Exception $e) {
            $results[] = 'View clear failed: ' . $e->getMessage();
        }

        return response()->json([
            'success' => true,
            'message' => 'Semua cache berhasil dibersihkan!',
            'details' => $results
        ]);
    }

    /**
     * Optimize application (superadmin only)
     */
    public function optimizeApp()
    {
        $results = [];

        try {
            \Artisan::call('config:cache');
            $results[] = 'Config cached';
        } catch (\Exception $e) {
            $results[] = 'Config cache failed: ' . $e->getMessage();
        }

        try {
            \Artisan::call('route:cache');
            $results[] = 'Route cached';
        } catch (\Exception $e) {
            $results[] = 'Route cache failed: ' . $e->getMessage();
        }

        try {
            \Artisan::call('view:cache');
            $results[] = 'View cached';
        } catch (\Exception $e) {
            $results[] = 'View cache failed: ' . $e->getMessage();
        }

        return response()->json([
            'success' => true,
            'message' => 'Aplikasi berhasil dioptimasi!',
            'details' => $results
        ]);
    }

    /**
     * Format bytes to human readable
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
