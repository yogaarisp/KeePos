<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Tenant;

class IdentifyTenant
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $tenantId = null;
            $tenant = null;

            // 1. Identifikasi berdasarkan Subdomain (misal: toko1.wartegkee.com)
            $host = $request->getHost();
            
            // Lewati jika host adalah alamat IP (untuk menghindari salah deteksi di local IP)
            if (!filter_var($host, FILTER_VALIDATE_IP)) {
                $parts = explode('.', $host);
                
                // Asumsi: subdomain ada di bagian pertama jika ada > 2 bagian
                if (count($parts) > 2) {
                    $slug = $parts[0];
                    $tenant = Tenant::where('slug', $slug)->first();
                    if ($tenant) {
                        $tenantId = $tenant->id;
                    }
                }
            }

            // 2. Identifikasi berdasarkan Header (untuk Mobile App atau Testing)
            if (!$tenantId && $request->hasHeader('X-Tenant-Slug')) {
                $slug = $request->header('X-Tenant-Slug');
                $tenant = Tenant::where('slug', $slug)->first();
                if ($tenant) {
                    $tenantId = $tenant->id;
                }
            }

            // 3. Identifikasi berdasarkan User yang sedang login (Fallback)
            // IMPORTANT: Use withoutGlobalScope to avoid circular dependency
            if (!$tenantId && $request->bearerToken()) {
                try {
                    $user = Auth::guard('sanctum')->user();
                    if ($user && $user->tenant_id) {
                        $tenantId = $user->tenant_id;
                        if (!$tenant) {
                            $tenant = Tenant::find($tenantId);
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to identify tenant from user: ' . $e->getMessage());
                }
            }

            // Jika ditemukan, simpan ke config untuk akses global yang aman
            if ($tenantId) {
                config(['app.current_tenant_id' => $tenantId]);
                
                // Log untuk debugging
                Log::debug('Tenant identified', [
                    'tenant_id' => $tenantId,
                    'tenant_slug' => $tenant ? $tenant->slug : null,
                    'url' => $request->fullUrl()
                ]);
            }

            return $next($request);
        } catch (\Exception $e) {
            Log::error('IdentifyTenant Middleware Error: ' . $e->getMessage(), [
                'url' => $request->fullUrl(),
                'trace' => $e->getTraceAsString()
            ]);
            return $next($request); // Continue anyway as fallback
        }
    }
}
