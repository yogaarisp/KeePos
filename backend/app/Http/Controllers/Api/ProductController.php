<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\CategoryKasir;
use App\Services\PlanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['category', 'customCategories.options']);

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $products = $query->ordered()->paginate($request->get('limit', 10));
        $categories = CategoryKasir::ordered()->get();

        return response()->json([
            'success' => true,
            'data' => [
                'products' => $products,
                'categories' => $categories
            ]
        ]);
    }

    public function store(Request $request)
    {
        $tenant = $request->user()->tenant;
        if (!PlanService::canAddProduct($tenant)) {
            return response()->json([
                'success' => false,
                'message' => 'Kuota produk Anda sudah penuh. Silakan upgrade plan Anda untuk menambah lebih banyak produk.'
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'category_id' => 'required|exists:categories_product,id',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
            'is_available' => 'nullable',
            'sort_order' => 'nullable|integer',
        ]);

        // Convert is_available to boolean
        $validated['is_available'] = filter_var($request->is_available ?? true, FILTER_VALIDATE_BOOLEAN);
        $validated['sort_order'] = $request->sort_order ?? 0;

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('products', 'public');
        }

        $product = Product::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Menu berhasil ditambahkan',
            'data' => $product->load('category')
        ]);
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'category_id' => 'required|exists:categories_product,id',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
            'is_available' => 'nullable',
            'sort_order' => 'nullable|integer',
        ]);

        // Convert is_available to boolean
        $validated['is_available'] = filter_var($request->is_available ?? true, FILTER_VALIDATE_BOOLEAN);
        $validated['sort_order'] = $request->sort_order ?? 0;

        if ($request->hasFile('image')) {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $validated['image'] = $request->file('image')->store('products', 'public');
        }

        $product->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Menu berhasil diperbarui',
            'data' => $product->load('category')
        ]);
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }
        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Menu berhasil dihapus'
        ]);
    }

    // Category Methods
    public function categories()
    {
        $categories = CategoryKasir::ordered()->withCount('products')->get();
        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ]);

        $category = CategoryKasir::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Kategori berhasil ditambahkan',
            'data' => $category
        ]);
    }

    public function updateCategory(Request $request, $id)
    {
        $category = CategoryKasir::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ]);

        $category->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Kategori berhasil diperbarui',
            'data' => $category
        ]);
    }

    public function destroyCategory($id)
    {
        $category = CategoryKasir::findOrFail($id);
        
        // Check if has products
        if ($category->products()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Kategori tidak dapat dihapus karena masih memiliki produk'
            ], 400);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Kategori berhasil dihapus'
        ]);
    }
}
