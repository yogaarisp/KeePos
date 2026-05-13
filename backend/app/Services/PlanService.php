<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\Product;
use App\Models\User;
use App\Models\Table;
use App\Models\StokGudang;
use App\Models\KitchenStock;

class PlanService
{
    /**
     * Plan definitions and their limits.
     */
    public const PLANS = [
        'free' => [
            'name' => 'FREE',
            'price' => 0,
            'max_products' => 10,
            'max_users' => 1, // 1 Akun (Owner)
            'max_tables' => 3,
            'max_warehouse_items' => 0, // No warehouse management
            'max_kitchen_items' => 0, // No kitchen management
            'features' => [
                'pos_desktop',
                'daily_reports',
                'basic_inventory'
            ],
            'description' => 'Cocok untuk UMKM baru/mulai'
        ],
        'basic' => [
            'name' => 'BASIC',
            'price' => 99000,
            'max_products' => 100,
            'max_users' => 2, // 2 Akun (Owner + Kasir)
            'max_tables' => 20,
            'max_warehouse_items' => 100,
            'max_kitchen_items' => 0, // No kitchen management
            'features' => [
                'pos_desktop',
                'daily_reports',
                'warehouse_management',
                'export_excel',
                'google_sheets_sync',
                'multi_user'
            ],
            'description' => 'Untuk bisnis yang berkembang'
        ],
        'pro' => [
            'name' => 'PRO',
            'price' => 249000,
            'max_products' => -1, // Unlimited
            'max_users' => -1,    // Akun Tanpa Batas
            'max_tables' => -1,   // Unlimited
            'max_warehouse_items' => -1,
            'max_kitchen_items' => -1, // Unlimited kitchen items
            'features' => [
                'pos_desktop',
                'daily_reports',
                'warehouse_management',
                'export_excel',
                'google_sheets_sync',
                'multi_user',
                'kitchen_management',
                'recipe_management',
                'inventory_reports',
                'priority_support',
                'unlimited_accounts'
            ],
            'description' => 'Solusi lengkap bisnis kuliner'
        ],
    ];

    /**
     * Check if a tenant can add a new product.
     */
    public static function canAddProduct(?Tenant $tenant): bool
    {
        if (!$tenant) return true; // Superadmin or global context

        $limit = self::PLANS[$tenant->plan]['max_products'] ?? 10;
        if ($limit === -1) return true;

        return Product::count() < $limit;
    }

    /**
     * Check if a tenant can add a new warehouse item.
     */
    public static function canAddWarehouseItem(?Tenant $tenant): bool
    {
        if (!$tenant) return true;

        $limit = self::PLANS[$tenant->plan]['max_warehouse_items'] ?? 15;
        if ($limit === -1) return true;

        return StokGudang::count() < $limit;
    }

    /**
     * Check if a tenant can add a new kitchen item.
     */
    public static function canAddKitchenItem(?Tenant $tenant): bool
    {
        if (!$tenant) return true;

        $limit = self::PLANS[$tenant->plan]['max_kitchen_items'] ?? 15;
        if ($limit === -1) return true;

        return KitchenStock::count() < $limit;
    }

    /**
     * Check if a tenant can add a new user.
     */
    public static function canAddUser(?Tenant $tenant): bool
    {
        if (!$tenant) return true;

        $limit = self::PLANS[$tenant->plan]['max_users'] ?? 1;
        if ($limit === -1) return true;

        return User::count() < $limit;
    }

    /**
     * Check if a tenant can add a new table.
     */
    public static function canAddTable(?Tenant $tenant): bool
    {
        if (!$tenant) return true;

        $limit = self::PLANS[$tenant->plan]['max_tables'] ?? 3;
        if ($limit === -1) return true;

        return Table::count() < $limit;
    }

    /**
     * Check if a tenant has access to a specific feature.
     */
    public static function hasFeature(?Tenant $tenant, string $feature): bool
    {
        if (!$tenant) return true;

        $features = self::PLANS[$tenant->plan]['features'] ?? [];
        if (in_array('all', $features)) return true;

        return in_array($feature, $features);
    }

    /**
     * Get plan information with pricing from database.
     */
    public static function getPlanInfo(string $plan): array
    {
        $planData = self::PLANS[$plan] ?? null;
        if (!$planData) {
            return [];
        }

        // Get pricing from database settings
        if ($plan === 'basic') {
            $planData['price'] = (int)\App\Models\PlatformSetting::getValue('plan_basic_price', 99000);
        } elseif ($plan === 'pro') {
            $planData['price'] = (int)\App\Models\PlatformSetting::getValue('plan_pro_price', 249000);
        }

        // Get features from database settings
        $featuresKey = 'plan_' . $plan . '_features';
        $featuresJson = \App\Models\PlatformSetting::getValue($featuresKey, '[]');
        $featuresFromDb = json_decode($featuresJson, true) ?: [];
        
        if (!empty($featuresFromDb)) {
            $planData['feature_list'] = $featuresFromDb;
        }

        return $planData;
    }

    /**
     * Get all plans with current pricing and features.
     */
    public static function getAllPlans(): array
    {
        $plans = [];
        foreach (array_keys(self::PLANS) as $planKey) {
            $plans[$planKey] = self::getPlanInfo($planKey);
        }
        return $plans;
    }

    /**
     * Check if a plan allows kitchen management features.
     */
    public static function allowsKitchenManagement(?Tenant $tenant): bool
    {
        if (!$tenant) return true;
        return $tenant->plan === 'pro';
    }

    /**
     * Check if a plan allows warehouse management features.
     */
    public static function allowsWarehouseManagement(?Tenant $tenant): bool
    {
        if (!$tenant) return true;
        return in_array($tenant->plan, ['basic', 'pro']);
    }

    /**
     * Check if a plan allows export features.
     */
    public static function allowsExport(?Tenant $tenant): bool
    {
        if (!$tenant) return true;
        return in_array($tenant->plan, ['basic', 'pro']);
    }

    /**
     * Get plan limits for a tenant.
     */
    public static function getPlanLimits(?Tenant $tenant): array
    {
        if (!$tenant) {
            return [
                'max_products' => -1,
                'max_users' => -1,
                'max_tables' => -1,
                'max_warehouse_items' => -1,
                'max_kitchen_items' => -1,
            ];
        }

        $planData = self::PLANS[$tenant->plan] ?? self::PLANS['free'];
        
        return [
            'max_products' => $planData['max_products'],
            'max_users' => $planData['max_users'],
            'max_tables' => $planData['max_tables'],
            'max_warehouse_items' => $planData['max_warehouse_items'],
            'max_kitchen_items' => $planData['max_kitchen_items'],
        ];
    }
}
