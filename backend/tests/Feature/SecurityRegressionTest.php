<?php

namespace Tests\Feature;

use App\Models\CategoryKasir;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\TenantSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SecurityRegressionTest extends TestCase
{
    use RefreshDatabase;

    private function makeTenant(string $slug, string $plan = 'pro'): array
    {
        $tenant = Tenant::create([
            'name' => 'Store ' . $slug, 'slug' => $slug,
            'email' => $slug . '@test.com', 'plan' => $plan, 'is_active' => true,
        ]);
        return [$tenant];
    }

    private function makeUser(int $tenantId, string $email, string $role): User
    {
        return User::create([
            'tenant_id' => $tenantId, 'username' => str_replace('@', '_', $email),
            'email' => $email, 'password' => Hash::make('password123'),
            'full_name' => 'User ' . $role, 'role' => $role, 'is_active' => true,
            'email_verified_at' => now(),
        ]);
    }

    private function loginAs(string $email): string
    {
        $token = $this->postJson('/api/login', [
            'email' => $email, 'password' => 'password123',
        ])->assertStatus(200)->json('access_token');
        $this->app['auth']->forgetGuards();
        return $token;
    }

    public function test_kasir_cannot_create_or_assign_superadmin_role(): void
    {
        [$tenant] = $this->makeTenant('sec-ten-a');
        $admin = $this->makeUser($tenant->id, 'admin@a.com', 'admin');
        $kasir = $this->makeUser($tenant->id, 'kasir@a.com', 'kasir');
        $token = $this->loginAs('kasir@a.com');

        $this->assertNotSame($kasir->role, 'admin');

        $res = $this->withToken($token)->postJson('/api/users', [
            'username' => 'hacker', 'email' => 'hacker@a.com', 'password' => '123456',
            'full_name' => 'Hacker', 'role' => 'superadmin',
        ]);
        $res->assertStatus(403);
        $this->assertDatabaseMissing('users', ['username' => 'hacker']);

        $admin->update(['username' => 'admina']);
        $res2 = $this->withToken($token)->putJson('/api/users/' . $admin->id, [
            'username' => 'admina', 'email' => 'admin@a.com', 'full_name' => 'Admin A',
            'role' => 'superadmin',
        ]);
        $res2->assertStatus(403);
        $this->assertSame('admin', $admin->fresh()->role);
    }

    public function test_kasir_cannot_access_user_management_endpoint(): void
    {
        [$tenant] = $this->makeTenant('sec-ten-b');
        $this->makeUser($tenant->id, 'kasir@b.com', 'kasir');
        $token = $this->loginAs('kasir@b.com');

        $this->withToken($token)->getJson('/api/users')->assertStatus(403);
    }

    public function test_cross_tenant_header_cannot_access_other_tenant_data(): void
    {
        [$tenantA] = $this->makeTenant('sec-ten-c');
        [$tenantB] = $this->makeTenant('sec-ten-d');

        $userA = $this->makeUser($tenantA->id, 'user@c.com', 'admin');
        $this->makeUser($tenantB->id, 'user@d.com', 'admin');

        $catA = CategoryKasir::create(['tenant_id' => $tenantA->id, 'name' => 'Makanan A', 'is_active' => true]);
        $catB = CategoryKasir::create(['tenant_id' => $tenantB->id, 'name' => 'Makanan B', 'is_active' => true]);
        Product::create([
            'tenant_id' => $tenantA->id, 'category_id' => $catA->id,
            'name' => 'Produk Rahasia A', 'price' => 5000, 'stock' => 1,
            'is_active' => true, 'is_available' => true,
        ]);
        Product::create([
            'tenant_id' => $tenantB->id, 'category_id' => $catB->id,
            'name' => 'Produk Bocor B', 'price' => 7000, 'stock' => 1,
            'is_active' => true, 'is_available' => true,
        ]);

        $token = $this->loginAs('user@c.com');
        $this->app['auth']->forgetGuards();

        // Header X-Tenant-Slug milik tenant lain HARUS diabaikan (tenant dari user login yang dipakai)
        $res = $this->withToken($token)->withHeader('X-Tenant-Slug', 'sec-ten-d')->getJson('/api/products');
        $res->assertStatus(200);
        $withHeader = collect(array_column($res->json('data.products.data'), 'name'));
        $this->assertContains('Produk Rahasia A', $withHeader->all());
        $this->assertTrue($withHeader->doesntContain('Produk Bocor B'));

        // Tanpa header pun tetap hanya data tenant sendiri (user->tenant_id)
        $resNoHeader = $this->withToken($token)->getJson('/api/products');
        $noHeader = collect(array_column($resNoHeader->json('data.products.data'), 'name'));
        $this->assertContains('Produk Rahasia A', $noHeader->all());
        $this->assertTrue($noHeader->doesntContain('Produk Bocor B'));
    }

    public function test_reports_do_not_aggregate_other_tenant_data(): void
    {
        [$tenantA] = $this->makeTenant('sec-ten-e');
        [$tenantB] = $this->makeTenant('sec-ten-f');

        $this->makeUser($tenantA->id, 'reporter@e.com', 'admin');
        $this->makeUser($tenantB->id, 'reporter@f.com', 'admin');

        $catA = CategoryKasir::create(['tenant_id' => $tenantA->id, 'name' => 'Makanan E', 'is_active' => true]);
        Product::create([
            'tenant_id' => $tenantA->id, 'category_id' => $catA->id,
            'name' => 'Produk E', 'price' => 10000, 'stock' => 1,
            'is_active' => true, 'is_available' => true,
        ]);

        $token = $this->loginAs('reporter@e.com');
        $res = $this->withToken($token)->withHeader('X-Tenant-Slug', 'sec-ten-f')
            ->getJson('/api/reports/profit?start_date=2020-01-01&end_date=' . now()->format('Y-m-d'));
        $res->assertStatus(200);
    }

    public function test_settings_response_masks_secret_values(): void
    {
        [$tenant] = $this->makeTenant('sec-ten-g');
        $this->makeUser($tenant->id, 'mask@user.com', 'admin');
        config(['app.current_tenant_id' => $tenant->id]);

        TenantSetting::setValue('google_service_account_json', '{"private_key":"SUPER-SECRET-KEY"}', 'googlesheet', 'text');
        TenantSetting::setValue('smtp_password', 'smtp-secret-pass', 'email', 'text');
        TenantSetting::setValue('google_sync_enabled', 'true', 'googlesheet', 'text');

        $token = $this->loginAs('mask@user.com');
        $settings = collect($this->withToken($token)->getJson('/api/settings')->assertStatus(200)->json('data.settings'));

        $gKey = $settings->first(fn($s) => $s['key'] === 'google_service_account_json');
        $smtp = $settings->first(fn($s) => $s['key'] === 'smtp_password');

        $this->assertNotNull($gKey);
        $this->assertNotSame('SUPER-SECRET-KEY', $gKey['value']);
        $this->assertSame('********', $gKey['value']);
        $this->assertSame('********', $smtp['value']);

        // Update dengan nilai mask TIDAK boleh menimpa secret asli
        $this->withToken($token)->postJson('/api/settings', [
            'settings' => json_encode([
                'google_service_account_json' => '********',
                'smtp_password' => '********',
                'google_sync_enabled' => 'true',
            ]),
        ])->assertStatus(200);

        $this->assertSame('{"private_key":"SUPER-SECRET-KEY"}', TenantSetting::getValue('google_service_account_json'));
        $this->assertSame('smtp-secret-pass', TenantSetting::getValue('smtp_password'));

        // Kasir TIDAK boleh mengubah pengaturan sensitif
        $this->makeUser($tenant->id, 'maskkasir@user.com', 'kasir');
        $kasirToken = $this->loginAs('maskkasir@user.com');
        fwrite(STDERR, "[DEBUG] current_tenant_id=" . config('app.current_tenant_id') . "\n");
        $this->withToken($kasirToken)->postJson('/api/settings', [
            'settings' => json_encode([
                'google_service_account_json' => '{"private_key":"EVIL"}',
                'google_sync_enabled' => 'false',
            ]),
        ])->assertStatus(200);

        $this->assertSame('{"private_key":"SUPER-SECRET-KEY"}', TenantSetting::getValue('google_service_account_json'));
    }

    public function test_pos_price_is_taken_from_server_not_client(): void
    {
        [$tenant] = $this->makeTenant('sec-ten-h');
        $this->makeUser($tenant->id, 'posuser@h.com', 'admin');

        $cat = CategoryKasir::create(['tenant_id' => $tenant->id, 'name' => 'Makanan H', 'is_active' => true]);
        $product = Product::create([
            'tenant_id' => $tenant->id, 'category_id' => $cat->id,
            'name' => 'Nasi Padang', 'price' => 20000, 'cost_price' => 10000,
            'stock' => 5, 'is_active' => true, 'is_available' => true,
        ]);
        PaymentMethod::create(['tenant_id' => $tenant->id, 'name' => 'Tunai', 'type' => 'cash', 'is_active' => true, 'sort_order' => 1]);

        $token = $this->loginAs('posuser@h.com');

        // Client mengirim harga Rp 0 / Rp 1 — harus ditolak/diambil dari DB (Rp 20000)
        $res = $this->withToken($token)->postJson('/api/pos/checkout', [
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'price' => 1]],
            'payment_method' => 'cash',
            'payment_amount' => 20000,
            'discount' => 0,
        ]);
        $res->assertStatus(200)->assertJson(['success' => true]);

        $sale = \App\Models\Sale::latest('id')->first();
        $this->assertEquals(20000, (float) $sale->total_amount);
        $item = $sale->items->first();
        $this->assertEquals(20000, (float) $item->price);
    }
}