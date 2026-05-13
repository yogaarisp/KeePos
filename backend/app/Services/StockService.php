<?php

namespace App\Services;

use App\Models\StokGudang;
use App\Models\StockTransaction;
use App\Models\KitchenStock;
use App\Models\KitchenStockTransaction;
use App\Models\KitchenUnitConversion;
use Illuminate\Support\Facades\DB;

class StockService
{
    public function addStock($itemId, $quantity, $notes = null, $supplierId = null, $purchasePrice = null)
    {
        return DB::transaction(function () use ($itemId, $quantity, $notes, $supplierId, $purchasePrice) {
            $item = StokGudang::findOrFail($itemId);
            $stockBefore = $item->stock;

            // Update current stock and price per unit if purchase price provided
            $item->increment('stock', $quantity);
            if ($purchasePrice !== null && $purchasePrice > 0) {
                $item->update(['price_per_unit' => $purchasePrice]);
            }

            StockTransaction::create([
                'item_id' => $itemId,
                'type' => 'in',
                'quantity' => $quantity,
                'stock_before' => $stockBefore,
                'stock_after' => $stockBefore + $quantity,
                'notes' => $notes,
                'supplier_id' => $supplierId,
                'purchase_price' => $purchasePrice,
                'user_id' => auth()->id(),
            ]);

            return $item->fresh();
        });
    }

    public function reduceStock($itemId, $quantity, $notes = null)
    {
        return DB::transaction(function () use ($itemId, $quantity, $notes) {
            $item = StokGudang::findOrFail($itemId);

            if ($item->stock < $quantity) {
                throw new \Exception('Stok tidak mencukupi. Stok saat ini: ' . $item->stock);
            }

            $stockBefore = $item->stock;
            $item->decrement('stock', $quantity);

            StockTransaction::create([
                'item_id' => $itemId,
                'type' => 'out',
                'quantity' => $quantity,
                'stock_before' => $stockBefore,
                'stock_after' => $stockBefore - $quantity,
                'notes' => $notes,
                'user_id' => auth()->id(),
            ]);

            return $item->fresh();
        });
    }

    public function transferToKitchen($gudangItemId, $quantity, $notes = null)
    {
        return DB::transaction(function () use ($gudangItemId, $quantity, $notes) {
            $warehouseItem = StokGudang::findOrFail($gudangItemId);

            if ($warehouseItem->stock < $quantity) {
                throw new \Exception("Stok gudang tidak cukup! Tersedia: {$warehouseItem->stock} {$warehouseItem->unit}, diminta: {$quantity}");
            }

            // Find or create kitchen item linked to this warehouse item
            $kitchenStock = KitchenStock::where('warehouse_item_id', $gudangItemId)->first();

            if ($kitchenStock) {
                $stockBefore = $kitchenStock->stock;
                $kitchenStock->increment('stock', $quantity);
            } else {
                $stockBefore = 0;
                $kitchenStock = KitchenStock::create([
                    'warehouse_item_id' => $gudangItemId,
                    'name' => $warehouseItem->name,
                    'category_id' => $warehouseItem->category_id,
                    'stock' => $quantity,
                    'unit' => $warehouseItem->unit,
                    'cost_price' => $warehouseItem->price_per_unit,
                    'min_stock' => 0,
                    'is_manual' => false,
                ]);

                // Create default 1:1 conversion
                KitchenUnitConversion::create([
                    'kitchen_item_id' => $kitchenStock->id,
                    'base_unit' => $warehouseItem->unit,
                    'convert_to_unit' => $warehouseItem->unit,
                    'ratio' => 1,
                ]);
            }

            // Reduce warehouse stock
            $this->reduceStock($gudangItemId, $quantity, 'Transfer ke dapur: ' . $notes);

            // Log kitchen transaction
            KitchenStockTransaction::create([
                'kitchen_stock_id' => $kitchenStock->id,
                'type' => 'transfer',
                'quantity' => $quantity,
                'stock_before' => $stockBefore,
                'stock_after' => $stockBefore + $quantity,
                'source_gudang_id' => $gudangItemId,
                'notes' => $notes,
                'user_id' => auth()->id(),
            ]);

            return $kitchenStock->fresh();
        });
    }

    public function consumeKitchenStock($kitchenItemId, $quantity, $notes = null)
    {
        return DB::transaction(function () use ($kitchenItemId, $quantity, $notes) {
            $item = KitchenStock::findOrFail($kitchenItemId);

            if ($item->stock < $quantity) {
                throw new \Exception("Stok tidak cukup! Tersedia: {$item->stock} {$item->unit}");
            }

            $stockBefore = $item->stock;
            $item->decrement('stock', $quantity);

            KitchenStockTransaction::create([
                'kitchen_stock_id' => $kitchenItemId,
                'type' => 'consumption',
                'quantity' => $quantity,
                'stock_before' => $stockBefore,
                'stock_after' => $stockBefore - $quantity,
                'notes' => $notes,
                'user_id' => auth()->id(),
            ]);

            return $item->fresh();
        });
    }

    public function returnToWarehouse($kitchenItemId, $quantity, $notes = null)
    {
        return DB::transaction(function () use ($kitchenItemId, $quantity, $notes) {
            $kitchenItem = KitchenStock::findOrFail($kitchenItemId);

            if ($kitchenItem->is_manual) {
                throw new \Exception('Item manual tidak bisa di-return ke gudang!');
            }

            if (!$kitchenItem->warehouse_item_id) {
                throw new \Exception('Item ini tidak memiliki sumber gudang');
            }

            if ($kitchenItem->stock < $quantity) {
                throw new \Exception("Stok dapur tidak cukup! Tersedia: {$kitchenItem->stock} {$kitchenItem->unit}");
            }

            $warehouseItem = StokGudang::findOrFail($kitchenItem->warehouse_item_id);

            // Return to warehouse
            $stockBefore = $kitchenItem->stock;
            $kitchenItem->decrement('stock', $quantity);

            $whStockBefore = $warehouseItem->stock;
            $warehouseItem->increment('stock', $quantity);

            // Log warehouse transaction
            StockTransaction::create([
                'item_id' => $warehouseItem->id,
                'type' => 'in',
                'quantity' => $quantity,
                'stock_before' => $whStockBefore,
                'stock_after' => $whStockBefore + $quantity,
                'notes' => 'Return dari dapur: ' . $notes,
                'user_id' => auth()->id(),
            ]);

            // Log kitchen transaction
            KitchenStockTransaction::create([
                'kitchen_stock_id' => $kitchenItemId,
                'type' => 'out',
                'quantity' => $quantity,
                'stock_before' => $stockBefore,
                'stock_after' => $stockBefore - $quantity,
                'source_gudang_id' => $warehouseItem->id,
                'notes' => 'Return ke gudang: ' . $notes,
                'user_id' => auth()->id(),
            ]);

            return $kitchenItem->fresh();
        });
    }

    public function topUpKitchenStock($kitchenItemId, $quantity, $costPrice = null, $notes = null)
    {
        return DB::transaction(function () use ($kitchenItemId, $quantity, $costPrice, $notes) {
            $item = KitchenStock::findOrFail($kitchenItemId);
            $stockBefore = $item->stock;

            $updateData = ['stock' => $stockBefore + $quantity];
            if ($costPrice !== null && $costPrice > 0) {
                $updateData['cost_price'] = $costPrice;
            }

            $item->update($updateData);

            KitchenStockTransaction::create([
                'kitchen_stock_id' => $kitchenItemId,
                'type' => 'in',
                'quantity' => $quantity,
                'stock_before' => $stockBefore,
                'stock_after' => $stockBefore + $quantity,
                'notes' => 'Penambahan stok' . ($notes ? ': ' . $notes : ''),
                'user_id' => auth()->id(),
            ]);

            return $item->fresh();
        });
    }

    public function getLowStockItems()
    {
        return StokGudang::lowStock()->with('category')->get();
    }
}
