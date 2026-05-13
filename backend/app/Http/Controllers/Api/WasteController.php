<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WasteReport;
use App\Models\StokGudang;
use App\Models\KitchenStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WasteController extends Controller
{
    public function index(Request $request)
    {
        $query = WasteReport::with('user');

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59',
            ]);
        }

        if ($request->filled('source_type')) {
            $query->where('source_type', $request->source_type);
        }

        $reports = $query->latest()->paginate($request->get('limit', 20));

        return response()->json([
            'success' => true,
            'data' => $reports
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'source_type' => 'required|in:gudang,kitchen',
            'source_id' => 'required|integer',
            'quantity' => 'required|numeric|min:0.01',
            'reason' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $report = DB::transaction(function () use ($validated) {
            if ($validated['source_type'] === 'gudang') {
                $item = StokGudang::findOrFail($validated['source_id']);
                $estimatedLoss = $validated['quantity'] * $item->price_per_unit;

                if ($item->stock >= $validated['quantity']) {
                    $item->decrement('stock', $validated['quantity']);
                } else {
                    throw new \Exception('Stok gudang tidak mencukupi');
                }
            } else {
                $item = KitchenStock::findOrFail($validated['source_id']);
                $estimatedLoss = 0; // Kitchen stock might not have price_per_unit directly or simplified loss

                if ($item->stock >= $validated['quantity']) {
                    $item->decrement('stock', $validated['quantity']);
                } else {
                    throw new \Exception('Stok dapur tidak mencukupi');
                }
            }

            return WasteReport::create([
                'source_type' => $validated['source_type'],
                'source_id' => $validated['source_id'],
                'item_name' => $item->name,
                'quantity' => $validated['quantity'],
                'unit' => $item->unit,
                'estimated_loss' => $estimatedLoss,
                'reason' => $validated['reason'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'user_id' => auth()->id(),
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Laporan waste berhasil ditambahkan',
            'data' => $report
        ]);
    }

    public function destroy($id)
    {
        WasteReport::findOrFail($id)->delete();
        return response()->json([
            'success' => true,
            'message' => 'Laporan waste berhasil dihapus'
        ]);
    }
}
