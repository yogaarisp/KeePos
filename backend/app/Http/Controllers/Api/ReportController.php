<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\StokGudang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function salesSummary(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        $sales = Sale::completed()
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->get();

        $totalSales = $sales->sum('total_amount');
        $totalTransactions = $sales->count();
        $avgTransaction = $totalTransactions > 0 ? $totalSales / $totalTransactions : 0;

        $salesByMethod = $sales->groupBy('payment_method')->map(function ($group) {
            return [
                'count' => $group->count(),
                'total' => $group->sum('total_amount'),
            ];
        });

        $salesByDate = $sales->groupBy(fn($s) => $s->created_at->format('Y-m-d'))->map(function ($group) {
            return [
                'count' => $group->count(),
                'total' => $group->sum('total_amount'),
            ];
        });

        // Top Products
        $topProducts = DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('sales', 'order_items.sale_id', '=', 'sales.id')
            ->where('sales.status', 'completed')
            ->whereBetween('sales.created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->select('products.name', DB::raw('SUM(order_items.quantity) as total_qty'), DB::raw('SUM(order_items.subtotal) as total_revenue'))
            ->groupBy('products.id', 'products.name')
            ->orderBy('total_qty', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => [
                    'total_sales' => $totalSales,
                    'total_transactions' => $totalTransactions,
                    'avg_transaction' => $avgTransaction,
                ],
                'sales_by_method' => $salesByMethod,
                'sales_by_date' => $salesByDate,
                'top_products' => $topProducts,
                'date_range' => ['start' => $startDate, 'end' => $endDate]
            ]
        ]);
    }

    /**
     * Profit & Margin Report (BASIC+ plan)
     */
    public function profitReport(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->get('end_date', now()->format('Y-m-d'));

        // Get sold items with product cost_price
        $items = DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('sales', 'order_items.sale_id', '=', 'sales.id')
            ->where('sales.status', 'completed')
            ->whereBetween('sales.created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->select(
                'products.id',
                'products.name',
                'products.cost_price',
                DB::raw('SUM(order_items.quantity) as total_qty'),
                DB::raw('SUM(order_items.subtotal) as total_revenue'),
                DB::raw('SUM(order_items.quantity * products.cost_price) as total_cogs')
            )
            ->groupBy('products.id', 'products.name', 'products.cost_price')
            ->orderBy('total_revenue', 'desc')
            ->get();

        $totalRevenue = $items->sum('total_revenue');
        $totalCogs    = $items->sum('total_cogs');
        $grossProfit  = $totalRevenue - $totalCogs;
        $margin       = $totalRevenue > 0 ? round(($grossProfit / $totalRevenue) * 100, 1) : 0;

        // Products with no cost_price set (incomplete data warning)
        $missingCostCount = $items->where('cost_price', 0)->count();

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => [
                    'total_revenue'    => $totalRevenue,
                    'total_cogs'       => $totalCogs,
                    'gross_profit'     => $grossProfit,
                    'gross_margin_pct' => $margin,
                    'missing_cost_count' => $missingCostCount,
                ],
                'products' => $items->map(fn($i) => [
                    'id'            => $i->id,
                    'name'          => $i->name,
                    'total_qty'     => $i->total_qty,
                    'total_revenue' => $i->total_revenue,
                    'total_cogs'    => $i->total_cogs,
                    'gross_profit'  => $i->total_revenue - $i->total_cogs,
                    'margin_pct'    => $i->total_revenue > 0
                        ? round((($i->total_revenue - $i->total_cogs) / $i->total_revenue) * 100, 1)
                        : 0,
                    'has_cost_price' => $i->cost_price > 0,
                ]),
                'date_range' => ['start' => $startDate, 'end' => $endDate],
            ]
        ]);
    }

    public function stockSummary()
    {
        $warehouseStock = StokGudang::with('category')->get();
        
        $totalValue = $warehouseStock->sum(function($item) {
            return $item->stock * $item->price_per_unit;
        });

        $lowStockItems = $warehouseStock->filter(fn($item) => $item->stock <= $item->min_stock)->values();

        return response()->json([
            'success' => true,
            'data' => [
                'total_value' => $totalValue,
                'item_count' => $warehouseStock->count(),
                'low_stock_count' => $lowStockItems->count(),
                'low_stock_items' => $lowStockItems
            ]
        ]);
    }

    /**
     * Export reports to Excel format (BASIC plan feature)
     */
    public function exportExcel(Request $request)
    {
        $type = $request->get('type', 'sales'); // sales, stock, inventory
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        switch ($type) {
            case 'sales':
                return $this->exportSalesExcel($startDate, $endDate);
            case 'stock':
                return $this->exportStockExcel();
            case 'inventory':
                return $this->exportInventoryExcel();
            default:
                return response()->json(['success' => false, 'message' => 'Invalid export type'], 400);
        }
    }

    private function exportSalesExcel($startDate, $endDate)
    {
        $sales = Sale::completed()
            ->with(['orderItems.product'])
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->get();

        $csvData = [];
        $csvData[] = ['Tanggal', 'No. Transaksi', 'Produk', 'Qty', 'Harga', 'Subtotal', 'Total', 'Metode Bayar'];

        foreach ($sales as $sale) {
            foreach ($sale->orderItems as $item) {
                $csvData[] = [
                    $sale->created_at->format('Y-m-d H:i:s'),
                    $sale->id,
                    $item->product->name ?? 'Unknown',
                    $item->quantity,
                    number_format($item->price, 0, ',', '.'),
                    number_format($item->subtotal, 0, ',', '.'),
                    number_format($sale->total_amount, 0, ',', '.'),
                    $sale->payment_method
                ];
            }
        }

        return $this->generateCsvResponse($csvData, "sales_report_{$startDate}_to_{$endDate}.csv");
    }

    private function exportStockExcel()
    {
        $stocks = StokGudang::with('category')->get();

        $csvData = [];
        $csvData[] = ['Nama Item', 'Kategori', 'Stok', 'Unit', 'Harga per Unit', 'Total Value', 'Min Stock', 'Status'];

        foreach ($stocks as $stock) {
            $totalValue = $stock->stock * $stock->price_per_unit;
            $status = $stock->stock <= $stock->min_stock ? 'Low Stock' : 'Normal';

            $csvData[] = [
                $stock->name,
                $stock->category->name ?? 'Uncategorized',
                $stock->stock,
                $stock->unit,
                number_format($stock->price_per_unit, 0, ',', '.'),
                number_format($totalValue, 0, ',', '.'),
                $stock->min_stock,
                $status
            ];
        }

        return $this->generateCsvResponse($csvData, "stock_report_" . now()->format('Y-m-d') . ".csv");
    }

    private function exportInventoryExcel()
    {
        // Combine warehouse and kitchen stock if available
        $warehouseStock = StokGudang::with('category')->get();
        
        $csvData = [];
        $csvData[] = ['Lokasi', 'Nama Item', 'Kategori', 'Stok', 'Unit', 'Harga per Unit', 'Total Value'];

        foreach ($warehouseStock as $stock) {
            $totalValue = $stock->stock * $stock->price_per_unit;
            $csvData[] = [
                'Warehouse',
                $stock->name,
                $stock->category->name ?? 'Uncategorized',
                $stock->stock,
                $stock->unit,
                number_format($stock->price_per_unit, 0, ',', '.'),
                number_format($totalValue, 0, ',', '.')
            ];
        }

        // Add kitchen stock if PRO plan
        $tenant = request()->user()->tenant;
        if ($tenant && $tenant->plan === 'pro') {
            $kitchenStock = \App\Models\KitchenStock::with(['warehouseItem.category'])->get();
            foreach ($kitchenStock as $stock) {
                $csvData[] = [
                    'Kitchen',
                    $stock->warehouseItem->name ?? 'Unknown',
                    $stock->warehouseItem->category->name ?? 'Uncategorized',
                    $stock->stock,
                    $stock->unit,
                    '-',
                    '-'
                ];
            }
        }

        return $this->generateCsvResponse($csvData, "inventory_report_" . now()->format('Y-m-d') . ".csv");
    }

    private function generateCsvResponse($data, $filename)
    {
        $output = fopen('php://temp', 'r+');
        
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }
}
