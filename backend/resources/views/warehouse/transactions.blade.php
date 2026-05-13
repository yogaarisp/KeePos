@extends('layouts.app')
@section('title', 'Riwayat Transaksi - ' . $item->name)

@section('content')
<div class="mb-4">
    <a href="{{ route('warehouse.index') }}" class="btn btn-secondary btn-sm">
        <i data-lucide="arrow-left" style="width:16px;height:16px;"></i> Kembali
    </a>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">Riwayat: {{ $item->name }} (Stok: {{ number_format($item->stock, 2) }} {{ $item->unit }})</span>
    </div>
    <div class="card-body" style="padding:0;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Tipe</th>
                    <th style="text-align:right">Qty</th>
                    <th style="text-align:right">Sebelum</th>
                    <th style="text-align:right">Sesudah</th>
                    <th>Catatan</th>
                    <th>User</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $tx)
                <tr>
                    <td>{{ $tx->created_at->format('d/m/Y H:i') }}</td>
                    <td>
                        @if($tx->type === 'in') <span class="badge badge-success">Masuk</span>
                        @elseif($tx->type === 'out') <span class="badge badge-danger">Keluar</span>
                        @elseif($tx->type === 'transfer') <span class="badge badge-info">Transfer</span>
                        @else <span class="badge badge-warning">Adjustment</span>
                        @endif
                    </td>
                    <td style="text-align:right">{{ number_format($tx->quantity, 2) }}</td>
                    <td style="text-align:right">{{ number_format($tx->stock_before, 2) }}</td>
                    <td style="text-align:right">{{ number_format($tx->stock_after, 2) }}</td>
                    <td>{{ $tx->notes ?? '-' }}</td>
                    <td>{{ $tx->user->full_name ?? '-' }}</td>
                </tr>
                @empty
                <tr><td colspan="7" class="empty-state">Belum ada transaksi</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="pagination">{{ $transactions->links() }}</div>
@endsection
