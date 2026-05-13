<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Services\POSService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    protected $posService;

    public function __construct(POSService $posService)
    {
        $this->posService = $posService;
    }

    public function index(Request $request)
    {
        $query = Sale::with(['user', 'table', 'items.product', 'tenant'])
            ->where('total_amount', '>=', 0);

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59'
            ]);
        } else {
            $query->whereDate('created_at', now()->today());
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhere('cashier_name', 'like', "%{$search}%")
                  ->orWhereHas('user', fn($q) => $q->where('full_name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate($request->get('limit', 20));

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    public function show($id)
    {
        $order = Sale::with([
            'user', 'table',
            'items.product',
            'items.customizations.option'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'order' => $order,
                'receipt' => $this->posService->formatReceiptData($order),
            ]
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,completed,cancelled',
        ]);

        $order = Sale::findOrFail($id);
        $order->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'message' => 'Status order berhasil diupdate',
            'data' => $order
        ]);
    }
}
