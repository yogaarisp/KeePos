<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    /**
     * Display a listing of the tenants.
     */
    public function index()
    {
        $tenants = Tenant::withCount(['users' => function ($query) {
            $query->withoutGlobalScope('tenant');
        }])
        ->with([
            'profile:tenant_id,shop_name,shop_logo',
            // Load owner (admin) user for each tenant
            'users' => function ($query) {
                $query->withoutGlobalScope('tenant')
                      ->where('role', 'admin')
                      ->select('id', 'tenant_id', 'full_name', 'email', 'username', 'is_active', 'last_login')
                      ->limit(1);
            }
        ])
        ->latest()
        ->paginate(20);

        // Flatten owner info into each tenant
        $tenants->getCollection()->transform(function ($tenant) {
            $owner = $tenant->users->first();
            $tenant->owner_name  = $owner?->full_name;
            $tenant->owner_email = $owner?->email;
            $tenant->owner_id    = $owner?->id;
            $tenant->owner_last_login = $owner?->last_login;
            unset($tenant->users); // clean up
            return $tenant;
        });

        return response()->json([
            'success' => true,
            'data' => $tenants
        ]);
    }

    /**
     * Update the specified tenant (and optionally its owner email).
     */
    public function update(Request $request, $id)
    {
        $tenant = Tenant::findOrFail($id);

        $validated = $request->validate([
            'name'                  => 'required|string|max:255',
            'plan'                  => 'required|in:free,basic,pro',
            'is_active'             => 'required|boolean',
            'trial_ends_at'         => 'nullable|date',
            'subscription_ends_at'  => 'nullable|date',
            // Owner fields
            'owner_email'           => 'nullable|email|max:255',
            'owner_name'            => 'nullable|string|max:255',
        ]);

        // Update tenant
        $tenant->update([
            'name'                 => $validated['name'],
            'plan'                 => $validated['plan'],
            'is_active'            => $validated['is_active'],
            'trial_ends_at'        => $validated['trial_ends_at'] ?? null,
            'subscription_ends_at' => $validated['subscription_ends_at'] ?? null,
        ]);

        // Update owner user if email or name provided
        if (!empty($validated['owner_email']) || !empty($validated['owner_name'])) {
            $owner = \App\Models\User::withoutGlobalScope('tenant')
                ->where('tenant_id', $tenant->id)
                ->where('role', 'admin')
                ->first();

            if ($owner) {
                $ownerUpdate = [];

                if (!empty($validated['owner_email']) && $validated['owner_email'] !== $owner->email) {
                    // Check email uniqueness
                    $emailExists = \App\Models\User::withoutGlobalScope('tenant')
                        ->where('email', $validated['owner_email'])
                        ->where('id', '!=', $owner->id)
                        ->exists();

                    if ($emailExists) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Email sudah digunakan oleh akun lain.'
                        ], 422);
                    }

                    $ownerUpdate['email'] = $validated['owner_email'];
                    // Reset email verification
                    $ownerUpdate['email_verified_at'] = null;
                }

                if (!empty($validated['owner_name'])) {
                    $ownerUpdate['full_name'] = $validated['owner_name'];
                }

                if (!empty($ownerUpdate)) {
                    $owner->update($ownerUpdate);
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Tenant berhasil diperbarui',
            'data'    => $tenant
        ]);
    }

    /**
     * Remove the specified tenant (Soft delete or hard delete).
     */
    public function destroy($id)
    {
        $tenant = Tenant::findOrFail($id);
        
        // Prevent deleting the main tenant
        if ($tenant->slug === 'default') {
            return response()->json([
                'success' => false,
                'message' => 'Tenant default tidak dapat dihapus'
            ], 403);
        }

        $tenant->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tenant berhasil dihapus'
        ]);
    }

    /**
     * Get tenant statistics for dashboard.
     */
    public function stats()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'total_tenants' => Tenant::count(),
                'active_tenants' => Tenant::where('is_active', true)->count(),
                'plan_distribution' => Tenant::selectRaw('plan, count(*) as count')
                    ->groupBy('plan')
                    ->get(),
                'estimated_revenue' => $this->calculateEstimatedRevenue()
            ]
        ]);
    }

    /**
     * Get comprehensive platform dashboard data.
     */
    public function dashboard()
    {
        // Platform wide metrics
        $totalSales = \App\Models\Sale::sum('total_amount');
        $totalTransactions = \App\Models\Sale::count();
        $totalUsers = \App\Models\User::count();
        
        // Recent registrations
        $recentTenants = Tenant::latest()->limit(5)->get();
        
        // Growth data (last 30 days)
        $growth = Tenant::where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, count(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Top Performing Tenants (by sales)
        $topTenants = Tenant::withSum('sales', 'total_amount')
            ->orderBy('sales_sum_total_amount', 'desc')
            ->limit(5)
            ->get();

        $basicPrice = \App\Models\PlatformSetting::getValue('plan_basic_price', 99000);
        $proPrice = \App\Models\PlatformSetting::getValue('plan_pro_price', 249000);

        return response()->json([
            'success' => true,
            'data' => [
                'metrics' => [
                    'total_tenants' => Tenant::count(),
                    'active_tenants' => Tenant::where('is_active', true)->count(),
                    'total_sales' => $totalSales,
                    'total_transactions' => $totalTransactions,
                    'total_users' => $totalUsers,
                    'estimated_revenue_monthly' => $this->calculateEstimatedRevenue(),
                    'pricing' => [
                        'basic' => (int)$basicPrice,
                        'pro' => (int)$proPrice
                    ]
                ],
                'recent_tenants' => $recentTenants,
                'growth' => $growth,
                'top_tenants' => $topTenants,
                'plan_distribution' => Tenant::selectRaw('plan, count(*) as count')
                    ->groupBy('plan')
                    ->get()
            ]
        ]);
    }

    /**
     * Helper to calculate revenue based on active plans.
     */
    private function calculateEstimatedRevenue()
    {
        $distribution = Tenant::where('is_active', true)
            ->selectRaw('plan, count(*) as count')
            ->groupBy('plan')
            ->get();
            
        $basicPrice = \App\Models\PlatformSetting::getValue('plan_basic_price', 99000);
        $proPrice = \App\Models\PlatformSetting::getValue('plan_pro_price', 249000);

        $total = 0;
        foreach ($distribution as $item) {
            if ($item->plan === 'basic') $total += $item->count * (int)$basicPrice;
            if ($item->plan === 'pro') $total += $item->count * (int)$proPrice;
        }
        
        return $total;
    }

    /**
     * Get all subscription invoices globally.
     */
    public function globalInvoices()
    {
        $invoices = \App\Models\SubscriptionInvoice::with('tenant')
            ->latest()
            ->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $invoices
        ]);
    }
}
