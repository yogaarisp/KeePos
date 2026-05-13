<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StokGudang;
use App\Models\Category;
use App\Models\StockTransaction;
use App\Services\StockService;
use App\Services\PlanService;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    protected $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    public function index(Request $request)
    {
        $query = StokGudang::with('category');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('low_stock')) {
            $query->lowStock();
        }

        $items = $query->orderBy('name')->paginate($request->get('limit', 20));
        $categories = Category::active()->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $items,
                'categories' => $categories
            ]
        ]);
    }

    public function store(Request $request)
    {
        $tenant = $request->user()->tenant;
        if (!PlanService::canAddWarehouseItem($tenant)) {
            return response()->json([
                'success' => false,
                'message' => 'Kuota item gudang Anda sudah penuh. Silakan upgrade plan Anda untuk menambah lebih banyak item.'
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'category_id' => 'required|exists:categories,id',
            'stock' => 'required|numeric|min:0',
            'unit' => 'required|string|max:50',
            'price_per_unit' => 'required|numeric|min:0',
            'min_stock' => 'nullable|numeric|min:0',
            'default_supplier_id' => 'nullable|exists:suppliers,id',
            'notes' => 'nullable|string',
        ]);

        $item = StokGudang::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Bahan berhasil ditambahkan',
            'data' => $item
        ]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'category_id' => 'required|exists:categories,id',
            'unit' => 'required|string|max:50',
            'price_per_unit' => 'required|numeric|min:0',
            'min_stock' => 'nullable|numeric|min:0',
            'default_supplier_id' => 'nullable|exists:suppliers,id',
            'notes' => 'nullable|string',
        ]);

        $item = StokGudang::findOrFail($id);
        $item->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Bahan berhasil diperbarui',
            'data' => $item
        ]);
    }

    public function destroy($id)
    {
        $item = StokGudang::findOrFail($id);
        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'Bahan berhasil dihapus'
        ]);
    }

    public function addStock(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'required|numeric|min:0.01',
            'purchase_price' => 'nullable|numeric|min:0',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'notes' => 'nullable|string',
        ]);

        try {
            $this->stockService->addStock(
                $id, 
                $request->quantity, 
                $request->notes,
                $request->supplier_id,
                $request->purchase_price
            );
            $item = StokGudang::with('category')->find($id);
            return response()->json([
                'success' => true,
                'message' => 'Stok berhasil ditambahkan',
                'data' => $item
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function reduceStock(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string',
        ]);

        try {
            $this->stockService->reduceStock($id, $request->quantity, $request->notes);
            $item = StokGudang::with('category')->find($id);
            return response()->json([
                'success' => true,
                'message' => 'Stok berhasil dikurangi',
                'data' => $item
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function transactions($id)
    {
        $item = StokGudang::findOrFail($id);
        $transactions = StockTransaction::where('item_id', $id)
            ->with('user')
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
}
