<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\CategoryKasir;
use App\Models\Table;
use App\Models\PaymentMethod;
use App\Models\TenantSetting;
use App\Models\PlatformSetting;
use App\Services\POSService;
use App\Services\ShiftService;
use App\Services\GoogleSheetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class POSController extends Controller
{
    protected $posService;
    protected $shiftService;

    public function __construct(POSService $posService, ShiftService $shiftService)
    {
        $this->posService = $posService;
        $this->shiftService = $shiftService;
    }

    public function init()
    {
        $categories = CategoryKasir::active()->ordered()
            ->with(['products' => fn($q) => $q->available()->ordered()])
            ->get();

        $tables = Table::available()->get();
        $paymentMethods = PaymentMethod::where('is_active', true)->orderBy('sort_order')->get();
        $activeShift = $this->shiftService->getActiveShift(auth()->id());

        return response()->json([
            'success' => true,
            'data' => [
                'categories' => $categories,
                'tables' => $tables,
                'payment_methods' => $paymentMethods,
                'active_shift' => $activeShift,
                'shop_settings' => $this->getShopSettings()
            ]
        ]);
    }

    public function checkout(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.customizations' => 'nullable|array',
            'payment_method' => 'required|string',
            'payment_amount' => 'required|numeric|min:0',
            'table_id' => 'nullable|exists:tables,id',
            'order_type' => 'nullable|in:dine_in,takeaway',
            'customer_name' => 'nullable|string|max:255',
            'discount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $activeShift = $this->shiftService->getActiveShift(auth()->id());
        $validated['shift_id'] = $activeShift?->id;

        try {
            $order = $this->posService->processOrder($validated);

            // Sync to Google Sheets if enabled
            try {
                $gsService = app(GoogleSheetService::class);
                if ($gsService->isEnabled()) {
                    $gsService->syncTransaction($order->load(['items.product', 'user']));
                }
            } catch (\Exception $gsEx) {
                Log::error("Automatic GS Sync Failed: " . $gsEx->getMessage());
                // Don't fail the checkout if GS sync fails
            }

            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil diproses',
                'data' => $order->load(['items.product', 'table', 'user'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get shop settings from tenant profile (SaaS-aware).
     */
    protected function getShopSettings(): array
    {
        $user = auth()->user();
        $profile = $user->tenant ? $user->tenant->getOrCreateProfile() : null;

        return [
            'shop_name' => $profile->shop_name ?? $user->tenant->name ?? PlatformSetting::getValue('app_name', 'Kee POS'),
            'shop_logo' => $profile->shop_logo ?? PlatformSetting::getValue('app_logo'),
            'shop_address' => $profile->shop_address ?? '',
            'shop_phone' => $profile->shop_phone ?? '',
            'currency_symbol' => 'Rp',
        ];
    }
}
