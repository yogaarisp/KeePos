@extends('layouts.app')
@section('title', 'Pesanan')
@section('content')
<form class="filter-bar" method="GET">
    <div class="form-group">
        <label class="form-label">Cari</label>
        <input type="text" name="search" class="form-control" placeholder="Invoice / kasir..." value="{{ request('search') }}">
    </div>
    <div class="form-group">
        <label class="form-label">Dari</label>
        <input type="date" name="start_date" class="form-control" value="{{ request('start_date', now()->format('Y-m-d')) }}">
    </div>
    <div class="form-group">
        <label class="form-label">Sampai</label>
        <input type="date" name="end_date" class="form-control" value="{{ request('end_date', now()->format('Y-m-d')) }}">
    </div>
    <div class="form-group">
        <label class="form-label">Status</label>
        <select name="status" class="form-control">
            <option value="">Semua</option>
            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Selesai</option>
            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
            <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Batal</option>
        </select>
    </div>
    <div class="form-group">
        <label class="form-label">Pembayaran</label>
        <select name="payment_method" class="form-control">
            <option value="">Semua</option>
            <option value="cash" {{ request('payment_method') === 'cash' ? 'selected' : '' }}>Cash</option>
            <option value="qris" {{ request('payment_method') === 'qris' ? 'selected' : '' }}>QRIS</option>
            <option value="card" {{ request('payment_method') === 'card' ? 'selected' : '' }}>Card</option>
        </select>
    </div>
    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
</form>

<div class="card">
    <div class="card-body" style="padding:0;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Invoice</th>
                    <th>Kasir</th>
                    <th>Tipe</th>
                    <th>Pembayaran</th>
                    <th style="text-align:right">Total</th>
                    <th style="text-align:center">Status</th>
                    <th>Waktu</th>
                    <th style="text-align:center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                <tr>
                    <td><strong style="color:var(--text-primary);">{{ $order->receipt_number }}</strong></td>
                    <td>{{ $order->cashier_name ?? $order->user->full_name ?? '-' }}</td>
                    <td><span class="badge badge-info">{{ $order->order_type === 'dine_in' ? 'Dine In' : 'Take Away' }}</span></td>
                    <td><span class="badge badge-{{ $order->payment_method === 'cash' ? 'success' : 'info' }}">{{ strtoupper($order->payment_method) }}</span></td>
                    <td style="text-align:right;font-weight:600;color:var(--accent);">{{ $order->formatted_total }}</td>
                    <td style="text-align:center">
                        @if($order->status === 'completed') <span class="badge badge-success">Selesai</span>
                        @elseif($order->status === 'pending') <span class="badge badge-warning">Pending</span>
                        @else <span class="badge badge-danger">Batal</span>
                        @endif
                    </td>
                    <td style="font-size:12px;color:var(--text-muted);">{{ $order->created_at->format('d/m H:i') }}</td>
                    <td style="text-align:center">
                        <a href="{{ route('orders.show', $order->id) }}" class="btn btn-sm btn-secondary btn-icon" title="Detail">
                            <i data-lucide="eye" style="width:14px;height:14px;"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="empty-state">Tidak ada pesanan ditemukan</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="pagination">{{ $orders->withQueryString()->links() }}</div>
@endsection
