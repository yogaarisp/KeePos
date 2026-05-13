@extends('layouts.app')
@section('title', 'Detail Shift')
@section('content')
<div class="mb-4">
    <a href="{{ route('shifts.index') }}" class="btn btn-secondary btn-sm"><i data-lucide="arrow-left" style="width:16px;height:16px;"></i> Kembali</a>
</div>

<div class="stats-grid" style="margin-bottom:24px;">
    <div class="stat-card">
        <div class="stat-icon orange"><i data-lucide="banknote"></i></div>
        <div class="stat-info">
            <div class="stat-label">Total Penjualan</div>
            <div class="stat-value">Rp {{ number_format($total_sales, 0, ',', '.') }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><i data-lucide="receipt"></i></div>
        <div class="stat-info">
            <div class="stat-label">Total Transaksi</div>
            <div class="stat-value">{{ $total_transactions }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon {{ $shift->variance >= 0 ? 'green' : 'red' }}"><i data-lucide="trending-up"></i></div>
        <div class="stat-info">
            <div class="stat-label">Selisih Kas</div>
            <div class="stat-value">Rp {{ number_format($shift->variance ?? 0, 0, ',', '.') }}</div>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
    <div class="card">
        <div class="card-header"><span class="card-title">Info Shift</span></div>
        <div class="card-body">
            <div style="display:flex;flex-direction:column;gap:12px;font-size:14px;">
                <div style="display:flex;justify-content:space-between;"><span class="text-muted">Kasir</span><span>{{ $shift->user->full_name ?? '-' }}</span></div>
                <div style="display:flex;justify-content:space-between;"><span class="text-muted">Dibuka</span><span>{{ $shift->opened_at->format('d/m/Y H:i') }}</span></div>
                <div style="display:flex;justify-content:space-between;"><span class="text-muted">Ditutup</span><span>{{ $shift->closed_at ? $shift->closed_at->format('d/m/Y H:i') : 'Masih aktif' }}</span></div>
                <div style="display:flex;justify-content:space-between;"><span class="text-muted">Durasi</span><span>{{ $shift->duration ?? '-' }}</span></div>
                <div style="display:flex;justify-content:space-between;"><span class="text-muted">Modal Awal</span><span>Rp {{ number_format($shift->initial_cash, 0, ',', '.') }}</span></div>
                <div style="display:flex;justify-content:space-between;"><span class="text-muted">Kas Diharapkan</span><span>Rp {{ number_format($shift->expected_cash, 0, ',', '.') }}</span></div>
                <div style="display:flex;justify-content:space-between;"><span class="text-muted">Kas Aktual</span><span>Rp {{ number_format($shift->actual_cash ?? 0, 0, ',', '.') }}</span></div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><span class="card-title">Penjualan per Metode</span></div>
        <div class="card-body" style="padding:0;">
            <table class="data-table">
                <thead><tr><th>Metode</th><th style="text-align:center">Jumlah</th><th style="text-align:right">Total</th></tr></thead>
                <tbody>
                    @foreach($sales_by_method as $method)
                    <tr>
                        <td><span class="badge badge-info">{{ strtoupper($method->payment_method) }}</span></td>
                        <td style="text-align:center">{{ $method->count }}</td>
                        <td style="text-align:right;font-weight:600;color:var(--accent);">Rp {{ number_format($method->total, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
