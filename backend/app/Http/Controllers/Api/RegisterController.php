<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Unit;
use App\Models\Category;
use App\Models\CategoryKasir;
use App\Models\PaymentMethod;
use App\Models\OTPVerification;
use App\Notifications\VerifyEmailOTPNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    /**
     * Handle a registration request for a new store.
     */
    public function register(Request $request)
    {
        $request->validate([
            'store_name' => 'required|string|max:255',
            'full_name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username',
            'email' => 'required|email|max:255|unique:users,email|unique:tenants,email',
            'password' => ['required', Password::defaults()],
        ]);

        return DB::transaction(function () use ($request) {
            // Generate unique slug
            $baseSlug = Str::slug($request->store_name);
            $slug = $baseSlug;
            $count = 1;
            while (Tenant::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $count++;
            }

            // 1. Create the Tenant
            $tenant = Tenant::create([
                'name' => $request->store_name,
                'slug' => $slug,
                'email' => $request->email,
                'plan' => 'free',
                'trial_ends_at' => now()->addDays((int)\App\Models\PlatformSetting::getValue('default_trial_days', 20)),
                'is_active' => false, // Set to false initially
            ]);

            // 2. Temporarily bind tenant_id for scopes
            app()->instance('tenant_id', $tenant->id);

            // 3. Create the Admin User
            $user = User::create([
                'tenant_id' => $tenant->id,
                'username' => $request->username,
                'email' => $request->email,
                'full_name' => $request->full_name,
                'password' => Hash::make($request->password),
                'role' => 'admin',
                'is_active' => false, // Set to false initially
            ]);

            // 4. Initialize Default Data (Units, Categories, etc.)
            $this->initializeDefaultData($tenant->id);

            // 5. Generate and Send OTP
            $otpCode = sprintf("%06d", mt_rand(1, 999999));
            OTPVerification::updateOrCreate(
                ['email' => $user->email],
                [
                    'code' => $otpCode,
                    'expires_at' => now()->addMinutes(10)
                ]
            );

            $user->notify(new VerifyEmailOTPNotification($otpCode));

            return response()->json([
                'message' => 'Registrasi berhasil! Masukkan kode OTP yang dikirim ke email Anda.',
                'email' => $user->email,
                'requires_otp' => true
            ], 201);
        });
    }

    /**
     * Initialize default data for a new tenant.
     */
    protected function initializeDefaultData($tenantId)
    {
        // Default Units
        $units = [
            ['name' => 'Gram', 'abbreviation' => 'gr'],
            ['name' => 'Kilogram', 'abbreviation' => 'kg'],
            ['name' => 'Liter', 'abbreviation' => 'L'],
            ['name' => 'Mililiter', 'abbreviation' => 'ml'],
            ['name' => 'Pcs', 'abbreviation' => 'pcs'],
            ['name' => 'Bungkus', 'abbreviation' => 'bks'],
        ];

        foreach ($units as $unit) {
            Unit::create(array_merge($unit, ['tenant_id' => $tenantId]));
        }

        // Default Warehouse Category
        Category::create([
            'tenant_id' => $tenantId,
            'name' => 'Umum',
            'description' => 'Kategori umum bahan baku',
            'is_active' => true,
        ]);

        // Default POS Category
        CategoryKasir::create([
            'tenant_id' => $tenantId,
            'name' => 'Makanan',
            'is_active' => true,
        ]);

        CategoryKasir::create([
            'tenant_id' => $tenantId,
            'name' => 'Minuman',
            'is_active' => true,
        ]);

        // Default Payment Methods
        PaymentMethod::create([
            'tenant_id' => $tenantId,
            'name' => 'Tunai',
            'type' => 'cash',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        
        // Default Shop Settings - Ambil dari tenant name
        $tenant = Tenant::find($tenantId);
        if ($tenant) {
            // Create tenant profile (lebih rapi dari settings key-value)
            \App\Models\TenantProfile::create([
                'tenant_id' => $tenantId,
                'shop_name' => $tenant->name,
                'shop_email' => $tenant->email,
                'shop_phone' => $tenant->phone,
                'shop_address' => $tenant->address,
                'shop_tagline' => 'Smart POS System',
            ]);
            
            // Create default tenant settings (konfigurasi teknis)
            \App\Models\TenantSetting::create([
                'tenant_id' => $tenantId,
                'key' => 'tax_percentage',
                'value' => '10',
                'group' => 'tax',
                'type' => 'number'
            ]);
            
            \App\Models\TenantSetting::create([
                'tenant_id' => $tenantId,
                'key' => 'auto_backup_enabled',
                'value' => 'false',
                'group' => 'general',
                'type' => 'boolean'
            ]);
        }
    }
}
