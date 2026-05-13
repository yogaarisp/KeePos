<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StockTransaction;
use App\Models\KitchenStockTransaction;
use Illuminate\Http\Request;

class InventoryReportController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $location = $request->input('location');
        $type = $request->input('type');

        $warehouseQuery = StockTransaction::with(['item', 'user']);

        if ($startDate) {
            $warehouseQuery->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $warehouseQuery->whereDate('created_at', '<=', $endDate);
        }
        if ($type) {
            $warehouseQuery->where('type', $type);
        }

        $kitchenQuery = KitchenStockTransaction::with(['kitchenStock.warehouseItem', 'kitchenStock.conversions', 'user']);

        if ($startDate) {
            $kitchenQuery->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $kitchenQuery->whereDate('created_at', '<=', $endDate);
        }
        if ($type) {
            $kitchenQuery->where('type', $type);
        }

        $transactions = collect();

        if (!$location || $location === 'warehouse') {
            $warehouseTransactions = $warehouseQuery->get()->map(function ($transaction) {
                return [
                    'id' => 'w_' . $transaction->id,
                    'date' => $transaction->created_at,
                    'location' => 'Gudang',
                    'item_name' => $transaction->item ? $transaction->item->name : '-',
                    'type' => $transaction->type,
                    'type_label' => $this->getTypeLabel($transaction->type),
                    'quantity' => $transaction->quantity,
                    'unit' => $transaction->item ? $transaction->item->unit : '-',
                    'notes' => $transaction->notes,
                    'user_name' => $transaction->user ? $transaction->user->full_name : ($transaction->user ? $transaction->user->username : '-'),
                ];
            });
            $transactions = $transactions->concat($warehouseTransactions);
        }

        if (!$location || $location === 'kitchen') {
            $kitchenTransactions = $kitchenQuery->get()->map(function ($transaction) {
                $itemName = '-';
                $unit = '-';
                $quantity = (float)$transaction->quantity;
                
                if ($transaction->kitchenStock) {
                    if ($transaction->kitchenStock->is_manual) {
                        $itemName = $transaction->kitchenStock->manual_item_name ?: ($transaction->kitchenStock->name ?: '-');
                    } else {
                        if ($transaction->kitchenStock->warehouseItem) {
                            $itemName = $transaction->kitchenStock->warehouseItem->name;
                        } else {
                            $itemName = $transaction->kitchenStock->name ?: '-';
                        }
                    }
                    $unit = $transaction->kitchenStock->unit ?: '-';

                    // Automatic conversion to primary display unit
                    if ($transaction->kitchenStock->conversions && $transaction->kitchenStock->conversions->isNotEmpty()) {
                        $custom = $transaction->kitchenStock->conversions->filter(function($c) {
                            return !($c->base_unit === $c->convert_to_unit && (float)$c->ratio === 1.0);
                        });
                        if ($custom->isNotEmpty()) {
                            $c = $custom->first();
                            $quantity = $quantity * (float)$c->ratio;
                            $unit = $c->convert_to_unit;
                        }
                    }
                }

                return [
                    'id' => 'k_' . $transaction->id,
                    'date' => $transaction->created_at,
                    'location' => 'Dapur',
                    'item_name' => $itemName,
                    'type' => $transaction->type,
                    'type_label' => $this->getTypeLabel($transaction->type),
                    'quantity' => $quantity,
                    'unit' => $unit,
                    'notes' => $transaction->notes,
                    'user_name' => $transaction->user ? $transaction->user->full_name : ($transaction->user ? $transaction->user->username : '-'),
                ];
            });
            $transactions = $transactions->concat($kitchenTransactions);
        }

        $transactions = $transactions->sortByDesc('date')->values();

        $totalIn = $transactions->where('type', 'in')->sum('quantity');
        $totalOut = $transactions->whereIn('type', ['out', 'consume', 'return'])->sum('quantity');
        $totalTransactions = $transactions->count();

        return response()->json([
            'transactions' => $transactions,
            'summary' => [
                'total_in' => $totalIn,
                'total_out' => $totalOut,
                'total_transactions' => $totalTransactions,
            ]
        ]);
    }

    private function getTypeLabel($type)
    {
        $labels = [
            'in' => 'Masuk',
            'out' => 'Keluar',
            'consume' => 'Konsumsi',
            'transfer' => 'Transfer',
            'return' => 'Return',
            'adjustment' => 'Penyesuaian',
        ];

        return $labels[$type] ?? ucfirst($type);
    }
}
