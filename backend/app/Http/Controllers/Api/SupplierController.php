<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index()
    {
        $suppliers = Supplier::orderBy('name')->get();
        return response()->json([
            'success' => true,
            'data' => $suppliers
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $supplier = Supplier::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Supplier berhasil ditambahkan',
            'data' => $supplier
        ], 201);
    }

    public function show(Supplier $supplier)
    {
        return response()->json([
            'success' => true,
            'data' => $supplier
        ]);
    }

    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $supplier->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Supplier berhasil diperbarui',
            'data' => $supplier
        ]);
    }

    public function destroy(Supplier $supplier)
    {
        // Check if supplier has transactions before deleting (optional, soft delete handles this)
        $supplier->delete();

        return response()->json([
            'success' => true,
            'message' => 'Supplier berhasil dihapus'
        ]);
    }

    /**
     * Get procurement statistics for suppliers.
     */
    public function stats()
    {
        $stats = Supplier::withCount('transactions')
            ->with(['transactions' => function($query) {
                $query->where('type', 'in');
            }])
            ->get()
            ->map(function ($supplier) {
                $totalBelanja = $supplier->transactions
                    ->where('type', 'in')
                    ->sum(function ($transaction) {
                        return $transaction->quantity * $transaction->purchase_price;
                    });

                return [
                    'id' => $supplier->id,
                    'name' => $supplier->name,
                    'transactions_count' => $supplier->transactions_count,
                    'total_spend' => $totalBelanja,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
