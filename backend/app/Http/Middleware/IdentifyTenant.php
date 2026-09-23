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

            // 1. Tenant dari user yang sedang login (PASTI - otoritatif)
            //    Header/subdomain TIDAK boleh mengesampingkan tenant milik user,
            //    untuk mencegah akses lintas tenant (cross-tenant takeover).
            if ($request->bearerToken()) {
                $user = Auth::guard('sanctum')->user();
                if ($user && $user->tenant_id) {
                    $tenantId = $user->tenant_id;
                    $tenant = Tenant::find($tenantId);
                }
            }

            // 2. Identifikasi berdasarkan Subdomain (hanya untuk request publik/unauthenticated,
            //    misal landing page & public settings)
            if (!$tenantId && !$request->bearerToken()) {
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
            }

            // 3. Identifikasi berdasarkan Header (hanya jika belum teridentifikasi
            //    dan request TIDAK terautentikasi - untuk Mobile App / testing publik)
            if (!$tenantId && !$request->bearerToken() && $request->hasHeader('X-Tenant-Slug')) {
                $slug = $request->header('X-Tenant-Slug');
                $tenant = Tenant::where('slug', $slug)->first();
                if ($tenant) {
                    $tenantId = $tenant->id;
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
