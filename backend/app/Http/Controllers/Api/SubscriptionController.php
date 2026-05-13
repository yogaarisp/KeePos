<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionInvoice;
use App\Models\Tenant;
use App\Models\Subscription;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class SubscriptionController extends Controller
{
    /**
     * Get pricing plans.
     */
    public function plans()
    {
        $basicPrice = \App\Models\PlatformSetting::getValue('plan_basic_price', 99000);
        $proPrice = \App\Models\PlatformSetting::getValue('plan_pro_price', 299000);
        
        // Get features from database
        $freeFeatures = json_decode(\App\Models\PlatformSetting::getValue('plan_free_features', '[]'), true);
        $basicFeatures = json_decode(\App\Models\PlatformSetting::getValue('plan_basic_features', '[]'), true);
        $proFeatures = json_decode(\App\Models\PlatformSetting::getValue('plan_pro_features', '[]'), true);

        return response()->json([
            'success' => true,
            'data' => [
                'free' => [
                    'name' => 'FREE',
                    'price' => 0,
                    'price_formatted' => 'Rp 0',
                    'period' => '/bln',
                    'description' => 'Cocok untuk UMKM baru/mulai',
                    'features' => $freeFeatures ?: [
                        '1 Akun (Owner)',
                        'Kasir POS Desktop',
                        'Laporan Harian'
                    ],
                    'limits' => [
                        'max_products' => 10,
                        'max_users' => 1,
                        'max_tables' => 3,
                        'max_warehouse_items' => 0, // No warehouse access
                        'max_kitchen_items' => 0
                    ],
                    'button_text' => 'Mulai Gratis',
                    'popular' => false
                ],
                'basic' => [
                    'name' => 'BASIC',
                    'price' => (int)$basicPrice,
                    'price_formatted' => 'Rp ' . number_format((int)$basicPrice, 0, ',', '.'),
                    'period' => '/bln',
                    'description' => 'Untuk bisnis yang berkembang',
                    'features' => $basicFeatures ?: [
                        '2 Akun (Owner + Kasir)',
                        'Pengaturan Stok Gudang',
                        'Export Excel Laporan',
                        'Google Sheets Sync'
                    ],
                    'limits' => [
                        'max_products' => 100,
                        'max_users' => 2,
                        'max_tables' => 20,
                        'max_warehouse_items' => 100,
                        'max_kitchen_items' => 0
                    ],
                    'button_text' => 'Pilih Paket',
                    'popular' => true
                ],
                'pro' => [
                    'name' => 'PRO',
                    'price' => (int)$proPrice,
                    'price_formatted' => 'Rp ' . number_format((int)$proPrice, 0, ',', '.'),
                    'period' => '/bln',
                    'description' => 'Solusi lengkap bisnis kuliner',
                    'features' => $proFeatures ?: [
                        'Akun Tanpa Batas',
                        'Resep & Stok Dapur',
                        'Inventory Report',
                        'Support Prioritas'
                    ],
                    'limits' => [
                        'max_products' => -1,
                        'max_users' => -1,
                        'max_tables' => -1,
                        'max_warehouse_items' => -1,
                        'max_kitchen_items' => -1
                    ],
                    'button_text' => 'Pilih Paket',
                    'popular' => false
                ],
            ],
            'app_info' => [
                'name' => \App\Models\PlatformSetting::getValue('app_name', 'Kee POS Premium'),
                'tagline' => \App\Models\PlatformSetting::getValue('app_tagline', 'Harga Transparan, Tanpa Biaya Tersembunyi'),
                'whatsapp' => \App\Models\PlatformSetting::getValue('app_whatsapp', '+6281234567890')
            ]
        ]);
    }

    /**
     * Create an invoice for upgrade via Midtrans Snap.
     */
    public function checkout(Request $request)
    {
        $request->validate([
            'plan' => 'required|in:basic,pro',
            'months' => 'required|integer|min:1|max:12',
        ]);

        $user = Auth::user();
        $tenant = $user->tenant;
        
        if (!$tenant) {
            return response()->json([
                'success' => false,
                'message' => 'Akun superadmin tidak bisa melakukan langganan. Silakan login sebagai tenant/owner.'
            ], 400);
        }
        
        $basicPrice = \App\Models\PlatformSetting::getValue('plan_basic_price', 99000);
        $proPrice = \App\Models\PlatformSetting::getValue('plan_pro_price', 299000);

        $prices = [
            'basic' => (int)$basicPrice,
            'pro' => (int)$proPrice
        ];

        $amount = $prices[$request->plan] * $request->months;
        $orderId = 'SUBS-' . $tenant->id . '-' . time();

        $serverKey = \App\Models\PlatformSetting::getValue('midtrans_server_key')
                    ?? config('services.midtrans.server_key');
        
        if (!$serverKey) {
            return response()->json([
                'success' => false,
                'message' => 'Midtrans Server Key belum dikonfigurasi'
            ], 500);
        }

        // Trim whitespace from key
        $serverKey = trim($serverKey);

        $isProductionRaw = \App\Models\PlatformSetting::getValue('midtrans_is_production', '0');
        $isProduction = in_array($isProductionRaw, [true, 1, '1', 'true'], true);
        
        $baseUrl = $isProduction 
            ? 'https://app.midtrans.com/snap/v1/transactions'
            : 'https://app.sandbox.midtrans.com/snap/v1/transactions';

        // Debug log
        \Illuminate\Support\Facades\Log::info('Midtrans checkout attempt', [
            'url' => $baseUrl,
            'is_production' => $isProduction,
            'key_prefix' => substr($serverKey, 0, 15) . '...',
            'amount' => $amount,
            'order_id' => $orderId,
        ]);

        try {
            $payload = [
                'transaction_details' => [
                    'order_id' => $orderId,
                    'gross_amount' => (int)$amount,
                ],
                'item_details' => [
                    [
                        'id' => $request->plan,
                        'price' => (int)$prices[$request->plan],
                        'quantity' => (int)$request->months,
                        'name' => 'Paket ' . strtoupper($request->plan) . ' (' . $request->months . ' bulan)',
                    ]
                ],
                'customer_details' => [
                    'first_name' => $user->full_name,
                    'email' => $user->email,
                ],
                'callbacks' => [
                    'finish' => $request->header('origin') . '/app/billing?status=success',
                ],
            ];

            $response = Http::withBasicAuth($serverKey, '')
                ->acceptJson()
                ->post($baseUrl, $payload);

            if (!$response->successful()) {
                throw new \Exception('Midtrans Error (' . $response->status() . '): ' . $response->body());
            }

            $midtransResponse = $response->json();

            $invoice = SubscriptionInvoice::create([
                'tenant_id' => $tenant->id,
                'invoice_number' => $orderId,
                'external_id' => $orderId,
                'plan' => $request->plan,
                'amount' => $amount,
                'months' => $request->months,
                'status' => 'pending',
                'payment_token' => $midtransResponse['token'] ?? null,
                'payment_url' => $midtransResponse['redirect_url'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'data' => $invoice
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Webhook/Notification handler for Midtrans.
     */
    public function webhook(Request $request)
    {
        $serverKey = \App\Models\PlatformSetting::getValue('midtrans_server_key')
                    ?? config('services.midtrans.server_key');

        // Verify signature from Midtrans
        $orderId = $request->order_id;
        $statusCode = $request->status_code;
        $grossAmount = $request->gross_amount;
        $transactionStatus = $request->transaction_status;
        $fraudStatus = $request->fraud_status ?? 'accept';

        $signatureKey = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        if ($request->signature_key !== $signatureKey) {
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        $invoice = SubscriptionInvoice::where('external_id', $orderId)->first();

        if (!$invoice) {
            return response()->json(['message' => 'Invoice not found'], 404);
        }

        // Handle transaction status
        if ($transactionStatus === 'capture' || $transactionStatus === 'settlement') {
            if ($fraudStatus === 'accept') {
                $invoice->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'payment_method' => $request->payment_type ?? null,
                ]);

                // Update Tenant Subscription
                $tenant = $invoice->tenant;
                $months = $invoice->months ?? 1;
                
                $currentEndsAt = ($tenant->subscription_ends_at && $tenant->subscription_ends_at->isFuture()) 
                    ? $tenant->subscription_ends_at 
                    : now();
                    
                $newEndsAt = $currentEndsAt->addMonths($months);

                $tenant->update([
                    'plan' => $invoice->plan,
                    'subscription_ends_at' => $newEndsAt,
                    'is_active' => true,
                ]);

                // Track Active Subscription Record
                Subscription::create([
                    'tenant_id' => $tenant->id,
                    'plan' => $invoice->plan,
                    'starts_at' => now(),
                    'ends_at' => $newEndsAt,
                    'status' => 'active',
                ]);
            }
        } elseif (in_array($transactionStatus, ['deny', 'cancel', 'expire'])) {
            $invoice->update(['status' => 'expired']);
        } elseif ($transactionStatus === 'pending') {
            $invoice->update(['status' => 'pending']);
        }

        return response()->json(['success' => true]);
    }

    public function invoices()
    {
        $invoices = SubscriptionInvoice::where('tenant_id', Auth::user()->tenant_id)
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $invoices
        ]);
    }

    /**
     * Get current tenant's subscription status and plan info.
     */
    public function status()
    {
        $user = Auth::user();
        $tenant = $user->tenant;
        
        if (!$tenant) {
            return response()->json([
                'success' => false,
                'message' => 'Akun superadmin tidak memiliki subscription'
            ], 400);
        }

        $planInfo = \App\Services\PlanService::getPlanInfo($tenant->plan);
        $planLimits = \App\Services\PlanService::getPlanLimits($tenant);
        
        // Get current usage
        $currentUsage = [
            'products' => \App\Models\Product::count(),
            'users' => \App\Models\User::count(),
            'tables' => \App\Models\Table::count(),
            'warehouse_items' => \App\Models\StokGudang::count(),
            'kitchen_items' => \App\Models\KitchenStock::count(),
        ];

        // Check subscription status
        $now = now();
        $isExpired = false;
        $expiresAt = null;

        if ($tenant->plan === 'free') {
            if ($tenant->trial_ends_at) {
                $expiresAt = $tenant->trial_ends_at;
                $isExpired = $now->greaterThan($tenant->trial_ends_at);
            }
        } else {
            if ($tenant->subscription_ends_at) {
                $expiresAt = $tenant->subscription_ends_at;
                $isExpired = $now->greaterThan($tenant->subscription_ends_at);
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'tenant' => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'plan' => $tenant->plan,
                    'is_active' => $tenant->is_active,
                    'expires_at' => $expiresAt,
                    'is_expired' => $isExpired,
                    'days_remaining' => $expiresAt ? $now->diffInDays($expiresAt, false) : null
                ],
                'plan_info' => $planInfo,
                'limits' => $planLimits,
                'usage' => $currentUsage,
                'usage_percentage' => [
                    'products' => $planLimits['max_products'] > 0 ? round(($currentUsage['products'] / $planLimits['max_products']) * 100, 1) : 0,
                    'users' => $planLimits['max_users'] > 0 ? round(($currentUsage['users'] / $planLimits['max_users']) * 100, 1) : 0,
                    'tables' => $planLimits['max_tables'] > 0 ? round(($currentUsage['tables'] / $planLimits['max_tables']) * 100, 1) : 0,
                    'warehouse_items' => $planLimits['max_warehouse_items'] > 0 ? round(($currentUsage['warehouse_items'] / $planLimits['max_warehouse_items']) * 100, 1) : 0,
                    'kitchen_items' => $planLimits['max_kitchen_items'] > 0 ? round(($currentUsage['kitchen_items'] / $planLimits['max_kitchen_items']) * 100, 1) : 0,
                ]
            ]
        ]);
    }
}
