<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\OrderItem;
use App\Models\OrderCustomization;
use App\Models\TenantSetting;
use App\Models\Recipe;
use App\Models\KitchenStock;
use App\Models\KitchenStockTransaction;
use App\Models\PlatformSetting;
use App\Models\SaleMissingRecipe;
use App\Models\Product;
use App\Models\KitchenUnitConversion;
use App\Services\GoogleSheetService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class POSService
{
    protected $gsService;

    public function __construct(GoogleSheetService $gsService)
    {
        $this->gsService = $gsService;
    }

    public function processOrder(array $data)
    {
        return DB::transaction(function () use ($data) {
            $subtotal = 0;

            // Calculate subtotal
            foreach ($data['items'] as &$item) {
                $itemSubtotal = $item['price'] * $item['quantity'];

                // Add customization prices
                if (isset($item['customizations'])) {
                    foreach ($item['customizations'] as $custom) {
                        $itemSubtotal += $custom['price'] * $item['quantity'];
                    }
                }

                $item['subtotal'] = $itemSubtotal;
                $subtotal += $itemSubtotal;
            }

            $discount = $data['discount'] ?? 0;
            $tax = $data['tax'] ?? 0;
            $totalAmount = $subtotal - $discount + $tax;

            // Create sale
            $sale = Sale::create([
                'user_id' => auth()->id(),
                'shift_id' => $data['shift_id'] ?? null,
                'table_id' => $data['table_id'] ?? null,
                'invoice_number' => Sale::generateInvoiceNumber(),
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => $tax,
                'total_amount' => $totalAmount,
                'payment_method' => $data['payment_method'],
                'payment_amount' => $data['payment_amount'],
                'change_amount' => $data['payment_amount'] - $totalAmount,
                'status' => 'completed',
                'order_type' => $data['order_type'] ?? 'takeaway',
                'cashier_name' => auth()->user()->full_name,
                'customer_name' => $data['customer_name'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            // Create order items
            foreach ($data['items'] as $item) {
                $orderItem = OrderItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'subtotal' => $item['subtotal'],
                    'notes' => $item['notes'] ?? null,
                ]);

                // Create customizations
                if (isset($item['customizations'])) {
                    foreach ($item['customizations'] as $custom) {
                        OrderCustomization::create([
                            'order_item_id' => $orderItem->id,
                            'custom_option_id' => $custom['option_id'],
                            'price' => $custom['price'],
                        ]);
                    }
                }
            }

            // ═══════════════════════════════════════════════════════
            // AUTO DEDUCT KITCHEN STOCK BASED ON RECIPES
            // ═══════════════════════════════════════════════════════
            $this->deductKitchenStock($sale, $data['items']);

            // Update shift totals if shift exists
            if ($sale->shift_id) {
                $shift = $sale->shift;
                $shift->increment('total_sales', $totalAmount);
                $shift->increment('total_transactions');
            }

            // Sync to Google Sheets if enabled
            $this->gsService->syncTransaction($sale);

            return $sale->load('items.product', 'items.customizations.option');
        });
    }

    /**
     * Deduct kitchen stock based on product recipes.
     * If a product has no recipe, log it to sale_missing_recipes.
     */
    private function deductKitchenStock(Sale $sale, array $items): void
    {
        foreach ($items as $item) {
            $productId = $item['product_id'];
            $qtySold = $item['quantity'];

            // Find active recipe for this product
            $recipe = Recipe::where('product_id', $productId)
                ->where('is_active', true)
                ->with('items')
                ->first();

            if (!$recipe || $recipe->items->isEmpty()) {
                // No recipe found — log it silently
                $product = Product::find($productId);
                if ($product) {
                    SaleMissingRecipe::create([
                        'sale_id' => $sale->id,
                        'product_id' => $productId,
                        'product_name' => $product->name,
                        'quantity' => $qtySold,
                    ]);
                }

                Log::info("POS Auto-Deduct: Produk '{$product->name}' (ID:{$productId}) terjual {$qtySold}x tanpa resep aktif.");
                continue;
            }

            // Recipe exists — deduct each ingredient
            foreach ($recipe->items as $recipeItem) {
                // Only deduct kitchen stock (type = 'kitchen')
                if ($recipeItem->ingredient_type !== 'kitchen') {
                    continue;
                }

                $kitchenStock = KitchenStock::find($recipeItem->ingredient_id);
                if (!$kitchenStock) {
                    Log::warning("POS Auto-Deduct: Bahan dapur ID:{$recipeItem->ingredient_id} tidak ditemukan untuk resep '{$recipe->name}'.");
                    continue;
                }

                $deductQty = $recipeItem->quantity * $qtySold;

                // Check if unit conversion is needed
                if ($recipeItem->unit !== $kitchenStock->unit) {
                    $conversion = KitchenUnitConversion::where('kitchen_item_id', $kitchenStock->id)
                        ->where('convert_to_unit', $recipeItem->unit)
                        ->first();
                    
                    if ($conversion && $conversion->ratio > 0) {
                        $deductQty = $deductQty / $conversion->ratio;
                    } else {
                        Log::warning("POS Auto-Deduct: Konversi satuan dari {$recipeItem->unit} ke {$kitchenStock->unit} tidak ditemukan untuk {$kitchenStock->name}.");
                    }
                }

                $stockBefore = $kitchenStock->stock;
                $stockAfter = max(0, $stockBefore - $deductQty);

                // Update stock
                $kitchenStock->update(['stock' => $stockAfter]);

                // Record transaction
                KitchenStockTransaction::create([
                    'kitchen_stock_id' => $kitchenStock->id,
                    'type' => 'consumption',
                    'quantity' => $deductQty,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                    'notes' => "Auto-deduct: {$qtySold}x {$recipe->name} (INV#{$sale->invoice_number})",
                    'user_id' => auth()->id(),
                ]);

                Log::info("POS Auto-Deduct: {$kitchenStock->name} -{$deductQty} {$kitchenStock->unit} (sisa: {$stockAfter}) | INV#{$sale->invoice_number}");
            }
        }
    }

    public function formatReceiptData($sale)
    {
        // Get shop info from tenant profile (SaaS-aware)
        $user = auth()->user();
        $profile = $user->tenant ? $user->tenant->getOrCreateProfile() : null;

        $receiptData = [
            'shop_name' => $profile->shop_name ?? $user->tenant->name ?? PlatformSetting::getValue('app_name', 'Kee POS'),
            'shop_logo' => $profile->shop_logo ?? PlatformSetting::getValue('app_logo'),
            'shop_address' => $profile->shop_address ?? '',
            'shop_phone' => $profile->shop_phone ?? '',
            'receipt_number' => $sale->receipt_number,
            'date' => $sale->formatted_date,
            'cashier' => $sale->cashier_name ?? $sale->user->full_name,
            'table' => $sale->table ? $sale->table->table_number : '-',
            'order_type' => $sale->order_type,
            'items' => [],
            'subtotal' => $sale->subtotal,
            'discount' => $sale->discount,
            'tax' => $sale->tax,
            'total' => $sale->total_amount,
            'payment_method' => $sale->payment_method,
            'payment_amount' => $sale->payment_amount,
            'change' => $sale->change_amount,
        ];

        foreach ($sale->items as $item) {
            $itemData = [
                'name' => $item->product->name,
                'quantity' => $item->quantity,
                'price' => $item->price,
                'subtotal' => $item->subtotal,
                'customizations' => [],
            ];

            foreach ($item->customizations as $custom) {
                $itemData['customizations'][] = [
                    'name' => $custom->option->name ?? '-',
                    'price' => $custom->price,
                ];
            }

            $receiptData['items'][] = $itemData;
        }

        return $receiptData;
    }
}
