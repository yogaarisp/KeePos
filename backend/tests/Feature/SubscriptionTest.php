<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Tenant;
use App\Models\SubscriptionInvoice;
use App\Models\Subscription;
use App\Models\PlatformSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    private $tenant;
    private $owner;
    private $superadmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Warteg Test',
            'slug' => 'warteg-test',
            'email' => 'warteg@test.com',
            'plan' => 'free',
            'is_active' => true,
        ]);

        $this->owner = User::create([
            'tenant_id' => $this->tenant->id,
            'username' => 'owner',
            'email' => 'owner@example.com',
            'password' => bcrypt('password'),
            'full_name' => 'Warteg Owner',
            'role' => 'admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->superadmin = User::create([
            'tenant_id' => null,
            'username' => 'superadmin',
            'email' => 'superadmin@example.com',
            'password' => bcrypt('password'),
            'full_name' => 'Platform Superadmin',
            'role' => 'superadmin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
    }

    public function test_tenant_can_checkout_manual_invoice()
    {
        $response = $this->actingAs($this->owner)
            ->postJson('/api/subscriptions/checkout-manual', [
                'plan' => 'pro',
                'months' => 3,
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'data' => ['id', 'invoice_number', 'amount', 'plan', 'months', 'status']
            ]);

        $this->assertDatabaseHas('subscription_invoices', [
            'tenant_id' => $this->tenant->id,
            'plan' => 'pro',
            'months' => 3,
            'status' => 'pending',
            'payment_method' => 'manual',
        ]);
    }

    public function test_superadmin_can_approve_manual_payment()
    {
        Notification::fake();

        $invoice = SubscriptionInvoice::create([
            'tenant_id' => $this->tenant->id,
            'invoice_number' => 'SUBSM-1-123456',
            'plan' => 'pro',
            'amount' => 897000.00,
            'months' => 3,
            'status' => 'pending',
            'payment_method' => 'manual',
        ]);

        $response = $this->actingAs($this->superadmin)
            ->patchJson("/api/admin/invoices/{$invoice->id}/approve");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Pembayaran berhasil dikonfirmasi. Tenant telah diaktifkan.'
            ]);

        $invoice->refresh();
        $this->tenant->refresh();

        $this->assertEquals('paid', $invoice->status);
        $this->assertEquals('pro', $this->tenant->plan);
        $this->assertNotNull($this->tenant->subscription_ends_at);
        $this->assertTrue($this->tenant->is_active);

        $this->assertDatabaseHas('subscriptions', [
            'tenant_id' => $this->tenant->id,
            'plan' => 'pro',
            'status' => 'active',
        ]);

        Notification::assertSentTo($this->owner, \App\Notifications\PaymentApprovedNotification::class);
    }

    public function test_superadmin_can_reject_manual_payment()
    {
        Notification::fake();

        $invoice = SubscriptionInvoice::create([
            'tenant_id' => $this->tenant->id,
            'invoice_number' => 'SUBSM-1-654321',
            'plan' => 'pro',
            'amount' => 897000.00,
            'months' => 3,
            'status' => 'pending',
            'payment_method' => 'manual',
        ]);

        $response = $this->actingAs($this->superadmin)
            ->patchJson("/api/admin/invoices/{$invoice->id}/reject", [
                'reason' => 'Bukti transfer tidak valid/palsu.',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Invoice telah ditolak dan tenant telah diberitahu.'
            ]);

        $invoice->refresh();
        $this->assertEquals('rejected', $invoice->status);
        $this->assertEquals('Bukti transfer tidak valid/palsu.', $invoice->rejection_reason);

        Notification::assertSentTo($this->owner, \App\Notifications\PaymentRejectedNotification::class);
    }

    public function test_midtrans_webhook_handles_successful_payment()
    {
        Notification::fake();

        PlatformSetting::create([
            'key' => 'midtrans_server_key',
            'value' => 'sandbox-key-123',
        ]);

        $orderId = 'SUBS-1-123456';
        $invoice = SubscriptionInvoice::create([
            'tenant_id' => $this->tenant->id,
            'invoice_number' => $orderId,
            'plan' => 'basic',
            'amount' => 297000.00,
            'months' => 3,
            'status' => 'pending',
        ]);

        $serverKey = 'sandbox-key-123';
        $signature = hash('sha512', $orderId . '200' . '297000.00' . $serverKey);

        $response = $this->postJson('/api/subscriptions/webhook', [
            'order_id' => $orderId,
            'status_code' => '200',
            'gross_amount' => '297000.00',
            'transaction_status' => 'settlement',
            'fraud_status' => 'accept',
            'signature_key' => $signature,
            'payment_type' => 'credit_card',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $invoice->refresh();
        $this->tenant->refresh();

        $this->assertEquals('paid', $invoice->status);
        $this->assertEquals('basic', $this->tenant->plan);
        $this->assertNotNull($this->tenant->subscription_ends_at);

        Notification::assertSentTo($this->owner, \App\Notifications\PaymentSuccessNotification::class);
    }
}
