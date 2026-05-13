<?php

namespace App\Services;

use App\Models\Shift;
use App\Models\ShiftTransaction;
use Illuminate\Support\Facades\DB;

class ShiftService
{
    public function openShift($userId, $initialCash)
    {
        $activeShift = Shift::where('user_id', $userId)
            ->whereNull('closed_at')
            ->first();

        if ($activeShift) {
            throw new \Exception('Anda sudah memiliki shift aktif');
        }

        return Shift::create([
            'user_id' => $userId,
            'opened_at' => now(),
            'initial_cash' => $initialCash,
            'expected_cash' => $initialCash,
        ]);
    }

    public function closeShift($shiftId, $actualCash, $notes = null)
    {
        return DB::transaction(function () use ($shiftId, $actualCash, $notes) {
            $shift = Shift::findOrFail($shiftId);

            // Calculate expected cash from cash sales
            $cashSales = $shift->sales()
                ->where('payment_method', 'cash')
                ->where('status', 'completed')
                ->sum('total_amount');

            $cashChange = $shift->sales()
                ->where('payment_method', 'cash')
                ->where('status', 'completed')
                ->sum('change_amount');

            $expectedCash = $shift->initial_cash + $cashSales - $cashChange;
            $variance = $actualCash - $expectedCash;

            $totalSales = $shift->sales()
                ->where('status', 'completed')
                ->sum('total_amount');

            $totalTransactions = $shift->sales()
                ->where('status', 'completed')
                ->count();

            $shift->update([
                'closed_at' => now(),
                'expected_cash' => $expectedCash,
                'actual_cash' => $actualCash,
                'variance' => $variance,
                'total_sales' => $totalSales,
                'total_transactions' => $totalTransactions,
                'notes' => $notes,
            ]);

            return $shift;
        });
    }

    public function getActiveShift($userId)
    {
        return Shift::where('user_id', $userId)
            ->whereNull('closed_at')
            ->first();
    }

    public function getShiftSummary($shiftId)
    {
        $shift = Shift::with('sales.items.product')->findOrFail($shiftId);

        $salesByMethod = $shift->sales()
            ->where('status', 'completed')
            ->selectRaw('payment_method, COUNT(*) as count, SUM(total_amount) as total')
            ->groupBy('payment_method')
            ->get();

        return [
            'shift' => $shift,
            'sales_by_method' => $salesByMethod,
            'total_sales' => $shift->total_sales,
            'total_transactions' => $shift->total_transactions,
        ];
    }
}
