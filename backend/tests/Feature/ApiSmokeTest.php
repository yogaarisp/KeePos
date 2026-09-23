<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CategoryKasir;
use App\Models\KitchenStock;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\RecipeItem;
use App\Models\Supplier;
use App\Models\Table;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\UnitConversion;
use App\Models\User;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ApiSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function seedData(): array
    {
        $tenant = Tenant::create([
            'name' => 'Smoke Store', 'slug' => 'smoke-store',
            'email' => 'smoke@test.com', 'plan' => 'pro', 'is_active' => true,
        ]);
        $user = User::create([
            'tenant_id' => $tenant->id, 'username' => 'smokeowner',
            'email' => 'smoke@example.com', 'password' => Hash::make('password123'),
            'full_name' => 'Smoke Owner', 'role' => 'admin', 'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $pc = CategoryKasir::create(['tenant_id' => $tenant->id, 'name' => 'Makanan', 'is_active' => true]);
        $wc = Category::create(['tenant_id' => $tenant->id, 'name' => 'Umum', 'is_active' => true]);
        Unit::create(['tenant_id' => $tenant->id, 'name' => 'Gram', 'abbreviation' => 'gr']);
        Product::create([
            'tenant_id' => $tenant->id, 'category_id' => $pc->id,
            'name' => 'Produk A', 'price' => 10000, 'cost_price' => 5000,
            'stock' => 10, 'is_active' => true, 'is_available' => true,
        ]);
        Table::create(['tenant_id' => $tenant->id, 'table_number' => 'M1', 'status' => 'available', 'is_active' => true]);
        PaymentMethod::create(['tenant_id' => $tenant->id, 'name' => 'Tunai', 'type' => 'cash', 'is_active' => true, 'sort_order' => 1]);
        KitchenStock::create(['tenant_id' => $tenant->id, 'name' => 'Beras', 'category_id' => $wc->id, 'stock' => 50, 'unit' => 'gr', 'min_stock' => 5]);
        Supplier::create(['tenant_id' => $tenant->id, 'name' => 'Supplier A', 'phone' => '0812', 'email' => 'a@b.com', 'address' => 'Jl. A']);
        Employee::create(['tenant_id' => $tenant->id, 'name' => 'Karyawan A', 'position' => 'Kasir', 'status' => 'active']);

        return [$tenant, $user];
    }

    public function test_all_read_endpoints_are_healthy(): void
    {
        [$tenant, $user] = $this->seedData();

        $token = $this->postJson('/api/login', [
            'email' => 'smoke@example.com', 'password' => 'password123',
        ])->assertStatus(200)->json('access_token');

        $h = [
            'Authorization' => "Bearer {$token}",
            'X-Tenant-Slug' => 'smoke-store',
        ];

        $endpoints = [
            ['get', '/api/me'],
            ['get', '/api/pos/init'],
            ['get', '/api/orders'],
            ['get', '/api/warehouse'],
            ['get', '/api/products'],
            ['get', '/api/categories'],
            ['get', '/api/material-categories'],
            ['get', '/api/tables'],
            ['get', '/api/units'],
            ['get', '/api/waste'],
            ['get', '/api/waste/summary'],
            ['get', '/api/shifts'],
            ['get', '/api/shifts/active'],
            ['get', '/api/settings'],
            ['get', '/api/users'],
            ['get', '/api/settings/payment-methods'],
            ['get', '/api/suppliers'],
            ['get', '/api/suppliers/stats'],
            ['get', '/api/employees'],
            ['get', '/api/attendance'],
            ['get', '/api/attendance/summary/monthly'],
            ['get', '/api/kitchen'],
            ['get', '/api/kitchen/warehouse-items'],
            ['get', '/api/recipes'],
            ['get', '/api/recipes/ingredients'],
            ['get', '/api/missing-recipes'],
            ['get', '/api/production/recipes'],
            ['get', '/api/production/transactions'],
            ['get', '/api/reports/sales?start_date=2026-01-01&end_date=2026-12-31'],
            ['get', '/api/reports/stock'],
            ['get', '/api/reports/profit?start_date=2026-01-01&end_date=2026-12-31'],
            ['get', '/api/reports/inventory'],
            ['get', '/api/notifications'],
            ['get', '/api/subscriptions/status'],
            ['get', '/api/subscriptions/plans'],
            ['get', '/api/subscriptions/invoices'],
            ['get', '/api/settings/billing-stats'],
        ];

        $failures = [];
        foreach ($endpoints as [$method, $uri]) {
            $resp = $this->withHeaders($h)->json($method, $uri);
            if ($resp->status() >= 500) {
                $failures[] = "{$method} {$uri} => {$resp->status()} {$resp->json('message')}";
            }
        }

        $this->assertEmpty($failures, "Endpoints with 5xx:\n" . implode("\n", $failures));
    }

    public function test_superadmin_admin_endpoints_are_healthy(): void
    {
        [$tenant, $user] = $this->seedData();
        $admin = User::create([
            'tenant_id' => null, 'username' => 'superadmin',
            'email' => 'admin@super.com', 'password' => Hash::make('password123'),
            'full_name' => 'Super Admin', 'role' => 'superadmin', 'is_active' => true,
        ]);

        $token = $this->postJson('/api/login', [
            'email' => 'admin@super.com', 'password' => 'password123',
        ])->assertStatus(200)->json('access_token');

        $h = ['Authorization' => "Bearer {$token}"];

        $endpoints = [
            ['get', '/api/admin/dashboard'],
            ['get', '/api/admin/tenants'],
            ['get', '/api/admin/tenants/stats'],
            ['get', '/api/admin/invoices'],
            ['get', '/api/admin/saas/config'],
            ['get', '/api/admin/saas/bank-accounts'],
            ['get', '/api/admin/saas/system-info'],
        ];

        $failures = [];
        foreach ($endpoints as [$method, $uri]) {
            $resp = $this->withHeaders($h)->json($method, $uri);
            if ($resp->status() >= 500) {
                $failures[] = "{$method} {$uri} => {$resp->status()} {$resp->json('message')}";
            }
        }

        $this->assertEmpty($failures, "Admin endpoints with 5xx:\n" . implode("\n", $failures));
    }
}
