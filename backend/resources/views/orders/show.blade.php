@extends('layouts.app')
@section('title', 'Detail Pesanan')
@section('content')
<div class="mb-4">
    <a href="{{ route('orders.index') }}" class="btn btn-secondary btn-sm">
        <i data-lucide="arrow-left" style="width:16px;height:16px;"></i> Kembali
    </a>
</div>

<div style="display:grid;grid-template-columns:1.5fr 1fr;gap:16px;">
    <!-- Order details -->
    <div class="card">
        <div class="card-header"><span class="card-title">Invoice {{ $order->receipt_number }}</span></div>
        <div class="card-body" style="padding:0;">
            <table class="data-table">
                <thead><tr><th>Produk</th><th style="text-align:center">Qty</th><th style="text-align:right">Harga</th><th style="text-align:right">Subtotal</th></tr></thead>
                <tbody>
                    @foreach($order->items as $item)
                    <tr>
                        <td>
                            <strong style="color:var(--text-primary);">{{ $item->product->name ?? '-' }}</strong>
                            @if($item->customizations->count())
                            <div style="font-size:11px;color:var(--accent);margin-top:2px;">
                                {{ $item->customizations->map(fn($c) => $c->option->name ?? '')->join(', ') }}
                            </div>
                            @endif
                        </td>
                        <td style="text-align:center">{{ $item->quantity }}</td>
                        <td style="text-align:right">Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                        <td style="text-align:right;font-weight:600;">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Order summary -->
    <div class="card">
        <div class="card-header"><span class="card-title">Ringkasan</span></div>
        <div class="card-body">
            <div style="display:flex;flex-direction:column;gap:12px;font-size:14px;">
                <div style="display:flex;justify-content:space-between;"><span class="text-muted">Status</span>
                    <span class="badge badge-{{ $order->status === 'completed' ? 'success' : ($order->status === 'pending' ? 'warning' : 'danger') }}">{{ ucfirst($order->status) }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;"><span class="text-muted">Kasir</span><span>{{ $order->cashier_name ?? '-' }}</span></div>
                <div style="display:flex;justify-content:space-between;"><span class="text-muted">Tipe</span><span>{{ $order->order_type === 'dine_in' ? 'Dine In' : 'Take Away' }}</span></div>
                <div style="display:flex;justify-content:space-between;"><span class="text-muted">Meja</span><span>{{ $order->table ? 'Meja ' . $order->table->table_number : '-' }}</span></div>
                <div style="display:flex;justify-content:space-between;"><span class="text-muted">Pembayaran</span><span class="badge badge-info">{{ strtoupper($order->payment_method) }}</span></div>
                <div style="display:flex;justify-content:space-between;"><span class="text-muted">Waktu</span><span>{{ $order->created_at->format('d/m/Y H:i') }}</span></div>
                <hr style="border-color:var(--border-color);margin:4px 0;">
                <div style="display:flex;justify-content:space-between;"><span class="text-muted">Subtotal</span><span>Rp {{ number_format($order->subtotal, 0, ',', '.') }}</span></div>
                <div style="display:flex;justify-content:space-between;"><span class="text-muted">Diskon</span><span>Rp {{ number_format($order->discount, 0, ',', '.') }}</span></div>
                <div style="display:flex;justify-content:space-between;"><span class="text-muted">Pajak</span><span>Rp {{ number_format($order->tax, 0, ',', '.') }}</span></div>
                <div style="display:flex;justify-content:space-between;font-size:18px;font-weight:700;color:var(--accent);padding-top:8px;border-top:1px solid var(--border-color);">
                    <span>Total</span><span>{{ $order->formatted_total }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;"><span class="text-muted">Dibayar</span><span>Rp {{ number_format($order->payment_amount, 0, ',', '.') }}</span></div>
                <div style="display:flex;justify-content:space-between;"><span class="text-muted">Kembalian</span><span>Rp {{ number_format($order->change_amount, 0, ',', '.') }}</span></div>
            </div>
            @if($order->notes)
            <div style="margin-top:16px;padding:12px;background:var(--bg-primary);border-radius:8px;font-size:13px;color:var(--text-secondary);">
                <strong>Catatan:</strong> {{ $order->notes }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
