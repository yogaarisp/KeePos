<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CategoryKasir;
use App\Models\KitchenStock;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\RecipeItem;
use App\Models\Table;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class POSTransactionTest extends TestCase
{
    use RefreshDatabase;

    private function setupTenant(string $plan = 'pro'): array
    {
        $tenant = Tenant::create([
            'name' => 'Pos Test Store', 'slug' => 'pos-test-store',
            'email' => 'pos@test.com', 'plan' => $plan, 'is_active' => true,
        ]);
        $user = User::create([
            'tenant_id' => $tenant->id, 'username' => 'posowner',
            'email' => 'pos@example.com', 'password' => Hash::make('password123'),
            'full_name' => 'POS Owner', 'role' => 'admin', 'is_active' => true,
            'email_verified_at' => now(),
        ]);
        return [$tenant, $user];
    }

    private function loginAs(string $email = 'pos@example.com', string $password = 'password123'): string
    {
        return $this->postJson('/api/login', [
            'email' => $email, 'password' => $password,
        ])->assertStatus(200)->json('access_token');
    }

    public function test_pos_init_and_checkout_flow_deducts_kitchen_stock(): void
    {
        [$tenant, $user] = $this->setupTenant();

        $cat = CategoryKasir::create(['tenant_id' => $tenant->id, 'name' => 'Makanan', 'is_active' => true]);
        $product = Product::create([
            'tenant_id' => $tenant->id, 'category_id' => $cat->id,
            'name' => 'Nasi Goreng', 'price' => 15000, 'cost_price' => 8000,
            'stock' => 100, 'is_active' => true, 'is_available' => true,
        ]);
        Table::create(['tenant_id' => $tenant->id, 'table_number' => 'T1', 'status' => 'available', 'is_active' => true]);
        PaymentMethod::create(['tenant_id' => $tenant->id, 'name' => 'Tunai', 'type' => 'cash', 'is_active' => true, 'sort_order' => 1]);
        $wc = Category::create(['tenant_id' => $tenant->id, 'name' => 'Umum', 'is_active' => true]);
        Unit::create(['tenant_id' => $tenant->id, 'name' => 'Gram', 'abbreviation' => 'gr']);
        $kstock = KitchenStock::create(['tenant_id' => $tenant->id, 'name' => 'Beras', 'category_id' => $wc->id, 'stock' => 50, 'unit' => 'gr', 'min_stock' => 5]);
        $recipe = Recipe::create(['tenant_id' => $tenant->id, 'product_id' => $product->id, 'name' => 'Resep Nasi Goreng', 'is_active' => true]);
        RecipeItem::create(['tenant_id' => $tenant->id, 'recipe_id' => $recipe->id, 'ingredient_type' => 'kitchen', 'ingredient_id' => $kstock->id, 'quantity' => 20, 'unit' => 'gr']);

        $token = $this->loginAs();

        $init = $this->withToken($token)->withHeader('X-Tenant-Slug', 'pos-test-store')
            ->getJson('/api/pos/init');
        $init->assertStatus(200)->assertJson(['success' => true]);
        $data = $init->json('data');
        $this->assertNotEmpty($data['categories']);
        $this->assertNotEmpty($data['payment_methods']);

        $res = $this->withToken($token)->withHeader('X-Tenant-Slug', 'pos-test-store')
            ->postJson('/api/pos/checkout', [
                'items' => [['product_id' => $product->id, 'quantity' => 2, 'price' => 15000]],
                'payment_method' => 'cash',
                'payment_amount' => 40000,
                'order_type' => 'dine_in',
                'discount' => 0,
            ]);
        $res->assertStatus(200)->assertJson(['success' => true]);

        $kstock->refresh();
        $this->assertEquals(10, (float) $kstock->stock);

        $this->withToken($token)->withHeader('X-Tenant-Slug', 'pos-test-store')->getJson('/api/orders')->assertStatus(200);
        $this->withToken($token)->withHeader('X-Tenant-Slug', 'pos-test-store')
            ->getJson('/api/reports/sales?start_date=' . now()->format('Y-m-d') . '&end_date=' . now()->format('Y-m-d'))
            ->assertStatus(200)->assertJson(['success' => true]);
    }

    public function test_product_without_recipe_is_logged_as_missing(): void
    {
        [$tenant, $user] = $this->setupTenant();
        $cat = CategoryKasir::create(['tenant_id' => $tenant->id, 'name' => 'Minuman', 'is_active' => true]);
        $product = Product::create([
            'tenant_id' => $tenant->id, 'category_id' => $cat->id,
            'name' => 'Es Teh', 'price' => 5000, 'cost_price' => 1000,
            'stock' => 50, 'is_active' => true, 'is_available' => true,
        ]);
        PaymentMethod::create(['tenant_id' => $tenant->id, 'name' => 'Cash', 'type' => 'cash', 'is_active' => true, 'sort_order' => 1]);

        $token = $this->loginAs();

        $res = $this->withToken($token)->withHeader('X-Tenant-Slug', 'pos-test-store')
            ->postJson('/api/pos/checkout', [
                'items' => [['product_id' => $product->id, 'quantity' => 1, 'price' => 5000]],
                'payment_method' => 'cash',
                'payment_amount' => 5000,
            ]);
        $res->assertStatus(200)->assertJson(['success' => true]);
        $this->assertDatabaseHas('sale_missing_recipes', ['product_id' => $product->id]);
    }
}