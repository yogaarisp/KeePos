<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KitchenStock;
use App\Models\KitchenStockTransaction;
use App\Models\KitchenUnitConversion;
use App\Models\Category;
use App\Models\StokGudang;
use App\Services\StockService;
use App\Services\PlanService;
use Illuminate\Http\Request;

class KitchenController extends Controller
{
    protected $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    /**
     * List all kitchen stock items with filters, search, sort, pagination
     */
    public function index(Request $request)
    {
        $query = KitchenStock::with(['category', 'conversions', 'warehouseItem']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('manual_item_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('is_manual')) {
            $query->where('is_manual', $request->is_manual);
        }

        if ($request->filled('low_stock')) {
            $query->lowStock();
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'name');
        $sortDir = $request->get('sort_dir', 'asc');
        $allowedSorts = ['name', 'stock', 'cost_price', 'unit', 'created_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'desc' ? 'desc' : 'asc');
        }

        $items = $query->paginate($request->get('limit', 50));
        $categories = Category::active()->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $items,
                'categories' => $categories
            ]
        ]);
    }

    /**
     * Add manual kitchen stock (not from warehouse)
     */
    public function store(Request $request)
    {
        $tenant = $request->user()->tenant;
        if (!PlanService::canAddKitchenItem($tenant)) {
            return response()->json([
                'success' => false,
                'message' => 'Kuota item dapur Anda sudah penuh. Silakan upgrade plan Anda untuk menambah lebih banyak item.'
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'stock' => 'required|numeric|min:0',
            'unit' => 'required|string|max:50',
            'cost_price' => 'required|numeric|min:0',
            'min_stock' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $item = KitchenStock::create([
            'name' => $validated['name'],
            'manual_item_name' => $validated['name'],
            'category_id' => $validated['category_id'] ?? null,
            'stock' => $validated['stock'],
            'unit' => $validated['unit'],
            'cost_price' => $validated['cost_price'],
            'min_stock' => $validated['min_stock'] ?? 0,
            'notes' => $validated['notes'] ?? null,
            'is_manual' => true,
        ]);

        // Create default 1:1 conversion
        KitchenUnitConversion::create([
            'kitchen_item_id' => $item->id,
            'base_unit' => $validated['unit'],
            'convert_to_unit' => $validated['unit'],
            'ratio' => 1,
        ]);

        // Log transaction
        KitchenStockTransaction::create([
            'kitchen_stock_id' => $item->id,
            'type' => 'in',
            'quantity' => $validated['stock'],
            'stock_before' => 0,
            'stock_after' => $validated['stock'],
            'notes' => 'Input manual: ' . ($validated['notes'] ?? ''),
            'user_id' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Stok dapur berhasil ditambahkan',
            'data' => $item->load(['category', 'conversions'])
        ]);
    }

    /**
     * Update kitchen stock details
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'unit' => 'required|string|max:50',
            'cost_price' => 'nullable|numeric|min:0',
            'min_stock' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $item = KitchenStock::findOrFail($id);

        $updateData = [
            'name' => $validated['name'],
            'category_id' => $validated['category_id'] ?? $item->category_id,
            'unit' => $validated['unit'],
            'min_stock' => $validated['min_stock'] ?? $item->min_stock,
            'notes' => $validated['notes'] ?? $item->notes,
        ];

        if (isset($validated['cost_price'])) {
            $updateData['cost_price'] = $validated['cost_price'];
        }

        if ($item->is_manual) {
            $updateData['manual_item_name'] = $validated['name'];
        }

        $item->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Stok dapur berhasil diperbarui',
            'data' => $item->load(['category', 'conversions'])
        ]);
    }

    /**
     * Delete kitchen stock item
     */
    public function destroy($id)
    {
        $item = KitchenStock::findOrFail($id);
        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'Stok dapur berhasil dihapus'
        ]);
    }

    /**
     * Transfer stock from warehouse to kitchen
     */
    public function transfer(Request $request)
    {
        $request->validate([
            'warehouse_item_id' => 'required|exists:stock_gudang,id',
            'quantity' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string',
        ]);

        $tenant = $request->user()->tenant;
        $existsInKitchen = KitchenStock::where('warehouse_item_id', $request->warehouse_item_id)->exists();
        
        if (!$existsInKitchen && !PlanService::canAddKitchenItem($tenant)) {
            return response()->json([
                'success' => false,
                'message' => 'Kuota item dapur Anda sudah penuh. Silakan upgrade plan Anda untuk menambah lebih banyak item.'
            ], 403);
        }

        try {
            $result = $this->stockService->transferToKitchen(
                $request->warehouse_item_id,
                $request->quantity,
                $request->notes
            );

            return response()->json([
                'success' => true,
                'message' => "Transfer berhasil! {$request->quantity} telah ditransfer ke dapur",
                'data' => $result->load(['category', 'conversions'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Consume stock (use for cooking)
     */
    public function consume(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string',
        ]);

        try {
            $result = $this->stockService->consumeKitchenStock(
                $id,
                $request->quantity,
                $request->notes
            );

            return response()->json([
                'success' => true,
                'message' => "Stok berhasil dikonsumsi! Sisa: {$result->stock} {$result->unit}",
                'data' => $result->load(['category', 'conversions'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Return stock from kitchen to warehouse
     */
    public function returnToWarehouse(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string',
        ]);

        try {
            $result = $this->stockService->returnToWarehouse(
                $id,
                $request->quantity,
                $request->notes
            );

            return response()->json([
                'success' => true,
                'message' => "Berhasil return {$request->quantity} ke gudang",
                'data' => $result->load(['category', 'conversions'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
    
    /**
     * Add stock manually to kitchen item
     */
    public function addStock(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'required|numeric|min:0.01',
            'cost_price' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        try {
            $result = $this->stockService->topUpKitchenStock(
                $id,
                $request->quantity,
                $request->cost_price,
                $request->notes
            );

            return response()->json([
                'success' => true,
                'message' => "Stok berhasil ditambahkan! Stok saat ini: {$result->stock} {$result->unit}",
                'data' => $result->load(['category', 'conversions'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Unit Conversion CRUD
     */
    public function getConversions($id)
    {
        $item = KitchenStock::with('conversions')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'item' => $item,
                'conversions' => $item->conversions
            ]
        ]);
    }

    public function addConversion(Request $request, $id)
    {
        $request->validate([
            'convert_to_unit' => 'required|string|max:50',
            'ratio' => 'required|numeric|min:0.001',
        ]);

        $item = KitchenStock::findOrFail($id);

        // Check duplicate
        $exists = KitchenUnitConversion::where('kitchen_item_id', $id)
            ->where('convert_to_unit', $request->convert_to_unit)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => "Konversi ke satuan '{$request->convert_to_unit}' sudah ada"
            ], 400);
        }

        $conversion = KitchenUnitConversion::create([
            'kitchen_item_id' => $id,
            'base_unit' => $item->unit,
            'convert_to_unit' => $request->convert_to_unit,
            'ratio' => $request->ratio,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Konversi berhasil ditambahkan',
            'data' => $conversion
        ]);
    }

    public function updateConversion(Request $request, $id, $conversionId)
    {
        $request->validate([
            'ratio' => 'required|numeric|min:0.001',
        ]);

        $conversion = KitchenUnitConversion::where('kitchen_item_id', $id)
            ->findOrFail($conversionId);

        $conversion->update(['ratio' => $request->ratio]);

        return response()->json([
            'success' => true,
            'message' => 'Konversi berhasil diperbarui',
            'data' => $conversion
        ]);
    }

    public function deleteConversion($id, $conversionId)
    {
        $conversion = KitchenUnitConversion::where('kitchen_item_id', $id)
            ->findOrFail($conversionId);

        // Don't allow deleting 1:1 default conversion
        if ($conversion->base_unit === $conversion->convert_to_unit && $conversion->ratio == 1) {
            return response()->json([
                'success' => false,
                'message' => 'Konversi default (1:1) tidak bisa dihapus'
            ], 400);
        }

        $conversion->delete();

        return response()->json([
            'success' => true,
            'message' => 'Konversi berhasil dihapus'
        ]);
    }

    /**
     * Get transaction history for a kitchen item
     */
    public function transactions($id)
    {
        $item = KitchenStock::findOrFail($id);
        $transactions = KitchenStockTransaction::where('kitchen_stock_id', $id)
            ->with(['user', 'sourceGudang'])
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => [
                'item' => $item,
                'transactions' => $transactions
            ]
        ]);
    }

    /**
     * Get warehouse items available for transfer
     */
    public function warehouseItems()
    {
        $items = StokGudang::with('category')
            ->where('stock', '>', 0)
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $items
        ]);
    }
}
