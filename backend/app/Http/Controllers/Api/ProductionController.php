<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductionRecipe;
use App\Models\ProductionTransaction;
use App\Models\Recipe;
use App\Models\KitchenStock;
use App\Models\StokGudang;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductionController extends Controller
{
    protected $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    /**
     * Get list of production recipes
     */
    public function getRecipes(Request $request)
    {
        $recipes = ProductionRecipe::with(['recipe', 'outputKitchenStock'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $recipes
        ]);
    }

    /**
     * Store new production recipe
     */
    public function storeRecipe(Request $request)
    {
        $validated = $request->validate([
            'recipe_id' => 'required|exists:recipes,id|unique:production_recipes,recipe_id',
            'output_quantity' => 'required|numeric|min:0.01',
            'output_unit' => 'required|string|max:50',
            'output_kitchen_stock_id' => 'required|exists:stock_dapur,id',
        ], [
            'recipe_id.unique' => 'Resep ini sudah didaftarkan untuk produksi massal.',
        ]);

        $prodRecipe = ProductionRecipe::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Resep Produksi berhasil ditambahkan',
            'data' => $prodRecipe->load(['recipe', 'outputKitchenStock'])
        ]);
    }

    /**
     * Update production recipe
     */
    public function updateRecipe(Request $request, $id)
    {
        $prodRecipe = ProductionRecipe::findOrFail($id);

        $validated = $request->validate([
            'recipe_id' => 'required|exists:recipes,id|unique:production_recipes,recipe_id,' . $id,
            'output_quantity' => 'required|numeric|min:0.01',
            'output_unit' => 'required|string|max:50',
            'output_kitchen_stock_id' => 'required|exists:stock_dapur,id',
        ]);

        $prodRecipe->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Resep Produksi berhasil diperbarui',
            'data' => $prodRecipe->load(['recipe', 'outputKitchenStock'])
        ]);
    }

    /**
     * Delete production recipe
     */
    public function destroyRecipe($id)
    {
        $prodRecipe = ProductionRecipe::findOrFail($id);
        
        // Prevent deletion if transactions exist
        if ($prodRecipe->transactions()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak bisa menghapus resep produksi karena sudah memiliki transaksi produksi.'
            ], 400);
        }

        $prodRecipe->delete();

        return response()->json([
            'success' => true,
            'message' => 'Resep Produksi berhasil dihapus'
        ]);
    }

    /**
     * List all production transactions
     */
    public function index(Request $request)
    {
        $query = ProductionTransaction::with([
            'productionRecipe.recipe',
            'productionRecipe.outputKitchenStock',
            'user'
        ]);

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59',
            ]);
        }

        $transactions = $query->latest()->paginate($request->get('limit', 20));

        return response()->json([
            'success' => true,
            'data' => $transactions
        ]);
    }

    /**
     * Execute a production batch (Record a production transaction)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'production_recipe_id' => 'required|exists:production_recipes,id',
            'quantity_produced' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string',
        ]);

        try {
            $transaction = DB::transaction(function () use ($validated) {
                $prodRecipe = ProductionRecipe::with(['recipe.items.ingredient'])->findOrFail($validated['production_recipe_id']);
                
                $recipe = $prodRecipe->recipe;
                $outputQty = $prodRecipe->output_quantity;
                $qtyProduced = $validated['quantity_produced'];
                
                // Calculate multiplier (how many times the standard recipe output is produced)
                $multiplier = $qtyProduced / $outputQty;
                
                $totalCost = 0;
                $insufficientItems = [];

                // Step 1: Pre-validation of all ingredient stock
                foreach ($recipe->items as $item) {
                    $requiredQty = floatval($item->quantity) * $multiplier;
                    
                    if ($item->ingredient_type === 'gudang') {
                        $stockItem = StokGudang::find($item->ingredient_id);
                        if (!$stockItem || floatval($stockItem->stock) < $requiredQty) {
                            $insufficientItems[] = [
                                'name' => $stockItem ? $stockItem->name : 'Bahan Gudang #' . $item->ingredient_id,
                                'available' => $stockItem ? floatval($stockItem->stock) : 0,
                                'required' => $requiredQty,
                                'unit' => $item->unit
                            ];
                        }
                    } else {
                        $stockItem = KitchenStock::find($item->ingredient_id);
                        if (!$stockItem || floatval($stockItem->stock) < $requiredQty) {
                            $insufficientItems[] = [
                                'name' => $stockItem ? $stockItem->name : 'Bahan Dapur #' . $item->ingredient_id,
                                'available' => $stockItem ? floatval($stockItem->stock) : 0,
                                'required' => $requiredQty,
                                'unit' => $item->unit
                            ];
                        }
                    }
                }

                if (!empty($insufficientItems)) {
                    $msg = 'Stok bahan tidak mencukupi: ';
                    $details = [];
                    foreach ($insufficientItems as $ii) {
                        $details[] = "{$ii['name']} (Tersedia: {$ii['available']} {$ii['unit']}, Butuh: {$ii['required']} {$ii['unit']})";
                    }
                    throw new \Exception($msg . implode(', ', $details));
                }

                // Step 2: Deduct ingredients stock & Sum cost
                foreach ($recipe->items as $item) {
                    $requiredQty = floatval($item->quantity) * $multiplier;
                    $itemCost = floatval($item->cost) * $multiplier;
                    $totalCost += $itemCost;

                    $notesStr = "Produksi Massal: {$recipe->name} (#{$prodRecipe->id})";

                    if ($item->ingredient_type === 'gudang') {
                        $this->stockService->reduceStock($item->ingredient_id, $requiredQty, $notesStr);
                    } else {
                        $this->stockService->consumeKitchenStock($item->ingredient_id, $requiredQty, $notesStr);
                    }
                }

                // Step 3: Top up output kitchen stock
                // Compute unit cost for output stock
                $unitCost = $totalCost / $qtyProduced;

                $this->stockService->topUpKitchenStock(
                    $prodRecipe->output_kitchen_stock_id,
                    $qtyProduced,
                    $unitCost,
                    "Hasil Produksi Massal: {$recipe->name} (#{$prodRecipe->id})"
                );

                // Step 4: Record Production Transaction
                return ProductionTransaction::create([
                    'production_recipe_id' => $prodRecipe->id,
                    'quantity_produced' => $qtyProduced,
                    'total_cost' => $totalCost,
                    'user_id' => auth()->id(),
                    'notes' => $validated['notes'] ?? null,
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Produksi massal berhasil dicatat!',
                'data' => $transaction->load(['productionRecipe.recipe', 'productionRecipe.outputKitchenStock'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Rollback/Delete a production transaction
     */
    public function destroy($id)
    {
        try {
            DB::transaction(function () use ($id) {
                $transaction = ProductionTransaction::findOrFail($id);
                $prodRecipe = $transaction->productionRecipe;
                
                if (!$prodRecipe) {
                    throw new \Exception('Resep produksi tidak ditemukan.');
                }

                $recipe = $prodRecipe->recipe;
                $outputQty = $prodRecipe->output_quantity;
                $qtyProduced = $transaction->quantity_produced;
                
                $multiplier = $qtyProduced / $outputQty;

                // 1. Verify and reduce output stock
                $outputStock = KitchenStock::findOrFail($prodRecipe->output_kitchen_stock_id);
                if (floatval($outputStock->stock) < floatval($qtyProduced)) {
                    throw new \Exception("Tidak bisa membatalkan produksi karena stok produk hasil produksi ({$outputStock->name}) kurang dari jumlah yang diproduksi ({$qtyProduced} {$outputStock->unit}).");
                }

                // Deduct output stock
                $this->stockService->consumeKitchenStock(
                    $prodRecipe->output_kitchen_stock_id,
                    $qtyProduced,
                    "Pembatalan Produksi Massal ID #{$id}"
                );

                // 2. Return ingredients to stock
                foreach ($recipe->items as $item) {
                    $requiredQty = floatval($item->quantity) * $multiplier;
                    $notesStr = "Pembatalan Produksi Massal ID #{$id}";

                    if ($item->ingredient_type === 'gudang') {
                        $this->stockService->addStock($item->ingredient_id, $requiredQty, $notesStr);
                    } else {
                        $this->stockService->topUpKitchenStock($item->ingredient_id, $requiredQty, null, $notesStr);
                    }
                }

                // 3. Delete transaction
                $transaction->delete();
            });

            return response()->json([
                'success' => true,
                'message' => 'Transaksi produksi berhasil dibatalkan dan stok dikembalikan!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
