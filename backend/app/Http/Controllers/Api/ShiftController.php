<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use App\Services\ShiftService;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function index()
    {
        $shifts = Shift::with('user')->orderBy('opened_at', 'desc')->get();
        
        $data = $shifts->map(function ($shift) {
            return [
                'id' => $shift->id,
                'user_id' => $shift->user_id,
                'user_name' => $shift->user->full_name,
                'opened_at' => $shift->opened_at,
                'closed_at' => $shift->closed_at,
                'status' => $shift->closed_at ? 'closed' : 'open',
                'initial_cash' => $shift->initial_cash,
                'total_sales' => $shift->total_sales,
                'total_transactions' => $shift->total_transactions,
                'actual_cash' => $shift->actual_cash,
                'variance' => $shift->variance,
                'notes' => $shift->notes,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function active()
    {
        $activeShift = Shift::with('user')->whereNull('closed_at')->where('user_id', auth()->id())->first();
        
        if ($activeShift) {
            $activeShift->user_name = $activeShift->user->full_name;
        }

        return response()->json([
            'success' => true,
            'data' => $activeShift
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'initial_cash' => 'required|numeric|min:0',
        ]);

        try {
            $shiftService = app(ShiftService::class);
            $shift = $shiftService->openShift(auth()->id(), $request->initial_cash);

            return response()->json([
                'success' => true,
                'message' => 'Shift berhasil dibuka',
                'data' => $shift
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function close(Request $request, $id)
    {
        $request->validate([
            'actual_cash' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        try {
            $shiftService = app(ShiftService::class);
            $shift = $shiftService->closeShift($id, $request->actual_cash, $request->notes);

            return response()->json([
                'success' => true,
                'message' => 'Shift berhasil ditutup',
                'data' => $shift
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
        $shift = Shift::findOrFail($id);
        
        $transactions = $shift->sales()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($sale) {
                return [
                    'id' => $sale->id,
                    'invoice_number' => $sale->invoice_number,
                    'total_amount' => $sale->total_amount,
                    'payment_method' => $sale->payment_method,
                    'order_type' => $sale->order_type,
                    'created_at' => $sale->created_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $transactions
        ]);
    }
}
