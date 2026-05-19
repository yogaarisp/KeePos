<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Tenant;
use App\Models\OTPVerification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function createTenantAndOwner($overrides = [])
    {
        $tenant = Tenant::create(array_merge([
            'name' => 'Warteg Test',
            'slug' => 'warteg-test',
            'email' => 'warteg@test.com',
            'plan' => 'free',
            'is_active' => true,
        ], $overrides['tenant'] ?? []));

        $user = User::create(array_merge([
            'tenant_id' => $tenant->id,
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'full_name' => 'Test User',
            'role' => 'admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ], $overrides['user'] ?? []));

        return [$tenant, $user];
    }

    public function test_user_can_login_with_valid_credentials()
    {
        [$tenant, $user] = $this->createTenantAndOwner();

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'user' => ['id', 'username', 'email', 'full_name', 'role']
            ]);
    }

    public function test_user_cannot_login_with_invalid_credentials()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'wrong@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_user_registration_creates_tenant_and_inactive_user()
    {
        Notification::fake();

        $response = $this->postJson('/api/register', [
            'store_name' => 'Warteg Premium',
            'username' => 'adminwarteg',
            'full_name' => 'Admin Warteg',
            'email' => 'admin@premium.com',
            'password' => 'Password123!',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Registrasi berhasil! Masukkan kode OTP yang dikirim ke email Anda.'
            ]);

        $this->assertDatabaseHas('tenants', ['name' => 'Warteg Premium']);
        $this->assertDatabaseHas('users', ['email' => 'admin@premium.com', 'is_active' => false]);
        $this->assertDatabaseHas('otp_verifications', ['email' => 'admin@premium.com']);
    }

    public function test_user_can_verify_email_via_otp()
    {
        [$tenant, $user] = $this->createTenantAndOwner([
            'tenant' => ['is_active' => false],
            'user' => ['is_active' => false, 'email_verified_at' => null],
        ]);

        OTPVerification::create([
            'email' => 'test@example.com',
            'code' => '123456',
            'expires_at' => now()->addMinutes(10),
        ]);

        $response = $this->postJson('/api/email/verify-otp', [
            'email' => 'test@example.com',
            'code' => '123456',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Email berhasil diverifikasi!']);

        $user->refresh();
        $tenant->refresh();

        $this->assertTrue($user->is_active);
        $this->assertTrue($tenant->is_active);
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_login_requires_2fa_otp_if_enabled()
    {
        Notification::fake();

        [$tenant, $user] = $this->createTenantAndOwner([
            'user' => ['two_factor_enabled' => true],
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'two_factor_required' => true,
                'email' => 'test@example.com',
            ]);

        $user->refresh();
        $this->assertNotNull($user->two_factor_code);
        $this->assertNotNull($user->two_factor_expires_at);

        Notification::assertSentTo($user, \App\Notifications\TwoFactorOTPNotification::class);
    }

    public function test_user_can_verify_2fa_login()
    {
        [$tenant, $user] = $this->createTenantAndOwner([
            'user' => [
                'two_factor_enabled' => true,
                'two_factor_code' => '654321',
                'two_factor_expires_at' => now()->addMinutes(10),
            ],
        ]);

        $response = $this->postJson('/api/login/2fa', [
            'email' => 'test@example.com',
            'code' => '654321',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['access_token', 'token_type', 'user']);

        $user->refresh();
        $this->assertNull($user->two_factor_code);
        $this->assertNull($user->two_factor_expires_at);
    }
}
