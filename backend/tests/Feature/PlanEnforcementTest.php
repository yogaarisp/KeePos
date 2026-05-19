<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanEnforcementTest extends TestCase
{
    use RefreshDatabase;

    private $freeTenant;
    private $basicTenant;
    private $proTenant;
    private $freeOwner;
    private $basicOwner;
    private $proOwner;

    protected function setUp(): void
    {
        parent::setUp();

        // FREE Tenant
        $this->freeTenant = Tenant::create([
            'name' => 'Warteg Gratisan', 'slug' => 'warteg-gratis',
            'email' => 'free@warteg.com', 'plan' => 'free', 'is_active' => true,
        ]);
        $this->freeOwner = User::create([
            'tenant_id' => $this->freeTenant->id, 'username' => 'freeowner',
            'email' => 'free@example.com', 'password' => bcrypt('password'),
            'full_name' => 'Free Owner', 'role' => 'admin', 'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // BASIC Tenant
        $this->basicTenant = Tenant::create([
            'name' => 'Warteg Menengah', 'slug' => 'warteg-basic',
            'email' => 'basic@warteg.com', 'plan' => 'basic', 'is_active' => true,
        ]);
        $this->basicOwner = User::create([
            'tenant_id' => $this->basicTenant->id, 'username' => 'basicowner',
            'email' => 'basic@example.com', 'password' => bcrypt('password'),
            'full_name' => 'Basic Owner', 'role' => 'admin', 'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // PRO Tenant
        $this->proTenant = Tenant::create([
            'name' => 'Warteg Mewah', 'slug' => 'warteg-pro',
            'email' => 'pro@warteg.com', 'plan' => 'pro', 'is_active' => true,
        ]);
        $this->proOwner = User::create([
            'tenant_id' => $this->proTenant->id, 'username' => 'proowner',
            'email' => 'pro@example.com', 'password' => bcrypt('password'),
            'full_name' => 'Pro Owner', 'role' => 'admin', 'is_active' => true,
            'email_verified_at' => now(),
        ]);
    }

    public function test_free_tier_cannot_access_warehouse()
    {
        $response = $this->actingAs($this->freeOwner)
            ->withHeader('X-Tenant-Slug', 'warteg-gratis')
            ->getJson('/api/warehouse');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'code' => 'PLAN_INSUFFICIENT',
                'required_plan' => 'basic',
                'current_plan' => 'free',
            ]);
    }

    public function test_basic_tier_can_access_warehouse_but_not_recipes()
    {
        $response = $this->actingAs($this->basicOwner)
            ->withHeader('X-Tenant-Slug', 'warteg-basic')
            ->getJson('/api/warehouse');
        $response->assertStatus(200);

        $response = $this->actingAs($this->basicOwner)
            ->withHeader('X-Tenant-Slug', 'warteg-basic')
            ->getJson('/api/recipes');
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'code' => 'PLAN_INSUFFICIENT',
                'required_plan' => 'pro',
                'current_plan' => 'basic',
            ]);
    }

    public function test_pro_tier_can_access_recipes()
    {
        $response = $this->actingAs($this->proOwner)
            ->withHeader('X-Tenant-Slug', 'warteg-pro')
            ->getJson('/api/recipes');
        $response->assertStatus(200);
    }

    public function test_deactivated_tenant_cannot_access_api()
    {
        $this->basicTenant->update(['is_active' => false]);

        $response = $this->actingAs($this->basicOwner)
            ->withHeader('X-Tenant-Slug', 'warteg-basic')
            ->getJson('/api/warehouse');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'code' => 'TENANT_DEACTIVATED',
            ]);
    }

    public function test_expired_subscription_cannot_access_api()
    {
        $this->basicTenant->update(['subscription_ends_at' => now()->subDay()]);

        $response = $this->actingAs($this->basicOwner)
            ->withHeader('X-Tenant-Slug', 'warteg-basic')
            ->getJson('/api/warehouse');

        $response->assertStatus(402)
            ->assertJson([
                'success' => false,
                'code' => 'SUBSCRIPTION_EXPIRED',
            ]);
    }
}
