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
                $costPerUnit = $item->price_per_unit ?? 0;
                $estimatedLoss = $validated['quantity'] * $costPerUnit;

                if ($item->stock >= $validated['quantity']) {
                    $item->decrement('stock', $validated['quantity']);
                } else {
                    throw new \Exception('Stok gudang tidak mencukupi');
                }
            } else {
                $item = KitchenStock::with('warehouseItem')->findOrFail($validated['source_id']);
                $costPerUnit = $item->cost_price ?? 0;
                if ($costPerUnit == 0 && $item->warehouseItem) {
                    $costPerUnit = $item->warehouseItem->price_per_unit ?? 0;
                }
                $estimatedLoss = $validated['quantity'] * $costPerUnit;

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
                'cost_per_unit' => $costPerUnit,
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

    public function summary(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->get('end_date', now()->format('Y-m-d'));

        $query = WasteReport::whereBetween('created_at', [
            $startDate . ' 00:00:00',
            $endDate . ' 23:59:59',
        ]);

        $totalLoss     = $query->sum('estimated_loss');
        $totalItems    = $query->count();
        $bySource      = WasteReport::whereBetween('created_at', [
                            $startDate . ' 00:00:00',
                            $endDate . ' 23:59:59',
                         ])
                         ->selectRaw('source_type, COUNT(*) as count, SUM(estimated_loss) as total_loss')
                         ->groupBy('source_type')
                         ->get();
        $byReason      = WasteReport::whereBetween('created_at', [
                            $startDate . ' 00:00:00',
                            $endDate . ' 23:59:59',
                         ])
                         ->selectRaw('reason, COUNT(*) as count, SUM(estimated_loss) as total_loss')
                         ->whereNotNull('reason')
                         ->groupBy('reason')
                         ->orderByDesc('total_loss')
                         ->limit(5)
                         ->get();

        $topWasteItems = WasteReport::whereBetween('created_at', [
                            $startDate . ' 00:00:00',
                            $endDate . ' 23:59:59',
                        ])
                        ->selectRaw('item_name, unit, SUM(quantity) as total_qty, SUM(estimated_loss) as total_loss')
                        ->groupBy('item_name', 'unit')
                        ->orderByDesc('total_loss')
                        ->limit(5)
                        ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'total_loss'      => $totalLoss,
                'total_items'     => $totalItems,
                'by_source'       => $bySource,
                'by_reason'       => $byReason,
                'top_waste_items' => $topWasteItems,
                'date_range'      => ['start' => $startDate, 'end' => $endDate],
            ],
        ]);
    }
}
