<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Tenant;

class CheckPlan
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $requiredPlan
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, string $requiredPlan): Response
    {
        $user = $request->user();
        
        // Superadmin bypasses all plan checks
        if ($user && $user->role === 'superadmin') {
            return $next($request);
        }

        $tenantId = config('app.current_tenant_id');
        if (!$tenantId) {
            return $next($request);
        }

        $tenant = Tenant::find($tenantId);
        if (!$tenant) {
            return $next($request);
        }

        $planWeights = [
            'free' => 0,
            'basic' => 1,
            'pro' => 2,
        ];

        $currentPlan = $tenant->plan ?: 'free';
        $currentWeight = $planWeights[$currentPlan] ?? 0;
        $requiredWeight = $planWeights[$requiredPlan] ?? 0;

        if ($currentWeight < $requiredWeight) {
            return response()->json([
                'success' => false,
                'message' => 'Fitur ini membutuhkan paket ' . ucfirst($requiredPlan) . '. Silakan upgrade akun Anda.',
                'code' => 'PLAN_INSUFFICIENT',
                'required_plan' => $requiredPlan,
                'current_plan' => $currentPlan
            ], 403);
        }

        return $next($request);
    }
}
