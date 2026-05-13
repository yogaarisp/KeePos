<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Recipe;
use App\Models\RecipeItem;
use App\Models\StokGudang;
use App\Models\KitchenStock;
use App\Models\SaleMissingRecipe;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecipeController extends Controller
{
    public function index(Request $request)
    {
        $query = Recipe::with(['product', 'items']);

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $recipes = $query->latest()->paginate($request->get('limit', 20));

        return response()->json([
            'success' => true,
            'data' => $recipes
        ]);
    }

    public function show($id)
    {
        $recipe = Recipe::with(['product', 'items'])->findOrFail($id);

        // Manually load ingredient names
        $items = $recipe->items->map(function ($item) {
            if ($item->ingredient_type === 'gudang') {
                $ingredient = StokGudang::find($item->ingredient_id);
            } else {
                $ingredient = KitchenStock::find($item->ingredient_id);
            }
            $item->ingredient_name = $ingredient ? $ingredient->name : 'Unknown';
            $item->ingredient_unit = $ingredient ? $ingredient->unit : '-';
            return $item;
        });

        $recipe->setRelation('items', $items);

        return response()->json([
            'success' => true,
            'data' => $recipe
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'description' => 'nullable|string',
            'product_id' => 'nullable|exists:products,id',
            'hpp' => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'type' => 'required|in:product,production',
            'is_active' => 'boolean',
            'items' => 'required|array|min:1',
            'items.*.ingredient_type' => 'required|in:gudang,kitchen',
            'items.*.ingredient_id' => 'required|integer',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.unit' => 'required|string|max:50',
            'items.*.cost' => 'nullable|numeric|min:0',
        ]);

        try {
            $recipe = DB::transaction(function () use ($validated) {
                $recipe = Recipe::create([
                    'name' => $validated['name'],
                    'description' => $validated['description'] ?? null,
                    'product_id' => $validated['product_id'] ?? null,
                    'hpp' => $validated['hpp'] ?? 0,
                    'selling_price' => $validated['selling_price'] ?? 0,
                    'type' => $validated['type'],
                    'is_active' => $validated['is_active'] ?? true,
                ]);

                foreach ($validated['items'] as $item) {
                    RecipeItem::create([
                        'recipe_id' => $recipe->id,
                        'ingredient_type' => $item['ingredient_type'],
                        'ingredient_id' => $item['ingredient_id'],
                        'quantity' => $item['quantity'],
                        'unit' => $item['unit'],
                        'cost' => $item['cost'] ?? 0,
                    ]);
                }

                // Auto-calculate HPP
                $totalCost = $recipe->items()->sum('cost');
                $recipe->update(['hpp' => $totalCost]);

                return $recipe->load('items');
            });

            return response()->json([
                'success' => true,
                'message' => 'Resep berhasil ditambahkan',
                'data' => $recipe
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan resep: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $recipe = Recipe::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'description' => 'nullable|string',
            'product_id' => 'nullable|exists:products,id',
            'hpp' => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'type' => 'required|in:product,production',
            'is_active' => 'boolean',
            'items' => 'required|array|min:1',
            'items.*.ingredient_type' => 'required|in:gudang,kitchen',
            'items.*.ingredient_id' => 'required|integer',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.unit' => 'required|string|max:50',
            'items.*.cost' => 'nullable|numeric|min:0',
        ]);

        try {
            $recipe = DB::transaction(function () use ($recipe, $validated) {
                $recipe->update([
                    'name' => $validated['name'],
                    'description' => $validated['description'] ?? null,
                    'product_id' => $validated['product_id'] ?? null,
                    'selling_price' => $validated['selling_price'] ?? 0,
                    'type' => $validated['type'],
                    'is_active' => $validated['is_active'] ?? true,
                ]);

                // Delete old items and re-create
                $recipe->items()->delete();

                foreach ($validated['items'] as $item) {
                    RecipeItem::create([
                        'recipe_id' => $recipe->id,
                        'ingredient_type' => $item['ingredient_type'],
                        'ingredient_id' => $item['ingredient_id'],
                        'quantity' => $item['quantity'],
                        'unit' => $item['unit'],
                        'cost' => $item['cost'] ?? 0,
                    ]);
                }

                // Auto-calculate HPP
                $totalCost = $recipe->items()->sum('cost');
                $recipe->update(['hpp' => $totalCost]);

                return $recipe->load('items');
            });

            return response()->json([
                'success' => true,
                'message' => 'Resep berhasil diperbarui',
                'data' => $recipe
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui resep: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $recipe = Recipe::findOrFail($id);
        $recipe->items()->delete();
        $recipe->delete();

        return response()->json([
            'success' => true,
            'message' => 'Resep berhasil dihapus'
        ]);
    }

    // Get ingredients list for dropdowns
    public function ingredients()
    {
        $gudang = StokGudang::select('id', 'name', 'unit', 'price_per_unit')
            ->orderBy('name')->get()
            ->map(fn($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'unit' => $item->unit,
                'cost' => $item->price_per_unit,
                'type' => 'gudang',
                'conversions' => [],
            ]);

        $kitchen = KitchenStock::with('conversions')
            ->orderBy('name')->get()
            ->map(fn($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'unit' => $item->unit,
                'cost' => $item->cost_price,
                'type' => 'kitchen',
                'conversions' => $item->conversions,
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'gudang' => $gudang,
                'kitchen' => $kitchen,
            ]
        ]);
    }

    /**
     * Get products sold without recipes (missing recipe log).
     */
    public function missingRecipes(Request $request)
    {
        $query = SaleMissingRecipe::query();

        // Filter by date range
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        // Group by product and get totals
        $missing = $query->selectRaw('product_id, product_name, SUM(quantity) as total_qty, COUNT(*) as total_transactions, MAX(created_at) as last_sold_at')
            ->groupBy('product_id', 'product_name')
            ->orderByDesc('total_qty')
            ->get();

        // Count products that have NO recipe at all
        $productsWithoutRecipe = Product::whereDoesntHave('recipe', function ($q) {
            $q->where('is_active', true);
        })->count();

        return response()->json([
            'success' => true,
            'data' => [
                'missing_items' => $missing,
                'products_without_recipe' => $productsWithoutRecipe,
                'total_missing_products' => $missing->count(),
            ]
        ]);
    }

    /**
     * Dismiss/clear missing recipe logs for a specific product.
     */
    public function dismissMissingRecipe($productId)
    {
        SaleMissingRecipe::where('product_id', $productId)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Log produk tanpa resep berhasil dihapus'
        ]);
    }
}
