<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        // Skip for Superadmin
        if ($user && $user->role === 'superadmin') {
            return $next($request);
        }

        $tenantId = config('app.current_tenant_id');
        if (!$tenantId) {
            return $next($request);
        }

        $tenant = \App\Models\Tenant::find($tenantId);
        
        if (!$tenant) {
            return $next($request);
        }

        // 1. Manually deactivated by Superadmin
        if (!$tenant->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Akun toko Anda telah dinonaktifkan oleh administrator.',
                'code' => 'TENANT_DEACTIVATED'
            ], 403);
        }

        // 2. Check Subscription
        $now = now();
        $isExpired = false;

        if ($tenant->plan === 'free') {
             if ($tenant->trial_ends_at && $now->greaterThan($tenant->trial_ends_at)) {
                 $isExpired = true;
             }
        } else {
            if ($tenant->subscription_ends_at && $now->greaterThan($tenant->subscription_ends_at)) {
                $isExpired = true;
            }
        }

        if ($isExpired) {
            return response()->json([
                'success' => false,
                'message' => 'Masa aktif toko Anda telah berakhir. Silakan lakukan perpanjangan layanan.',
                'code' => 'SUBSCRIPTION_EXPIRED',
                'ends_at' => $tenant->subscription_ends_at ?: $tenant->trial_ends_at
            ], 402);
        }

        return $next($request);
    }
}
