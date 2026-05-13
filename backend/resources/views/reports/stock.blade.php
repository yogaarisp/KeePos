@extends('layouts.app')
@section('title', 'Laporan Stok')
@section('content')
<div class="stats-grid" style="margin-bottom:24px;">
    <div class="stat-card">
        <div class="stat-icon orange"><i data-lucide="warehouse"></i></div>
        <div class="stat-info">
            <div class="stat-label">Total Nilai Gudang</div>
            <div class="stat-value">Rp {{ number_format($warehouseTotalValue, 0, ',', '.') }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i data-lucide="alert-triangle"></i></div>
        <div class="stat-info">
            <div class="stat-label">Stok Rendah Gudang</div>
            <div class="stat-value">{{ $warehouseLowStock->count() }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon yellow"><i data-lucide="alert-triangle"></i></div>
        <div class="stat-info">
            <div class="stat-label">Stok Rendah Dapur</div>
            <div class="stat-value">{{ $kitchenLowStock->count() }}</div>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
    <div class="card">
        <div class="card-header"><span class="card-title">Stok Gudang ({{ $warehouseStock->count() }})</span></div>
        <div class="card-body" style="padding:0;max-height:500px;overflow-y:auto;">
            <table class="data-table">
                <thead><tr><th>Nama</th><th style="text-align:right">Stok</th><th style="text-align:right">Nilai</th><th style="text-align:center">Status</th></tr></thead>
                <tbody>
                    @foreach($warehouseStock as $item)
                    <tr>
                        <td style="color:var(--text-primary);font-weight:500;">{{ $item->name }}</td>
                        <td style="text-align:right">{{ number_format($item->stock, 2) }} {{ $item->unit }}</td>
                        <td style="text-align:right">Rp {{ number_format($item->total_value, 0, ',', '.') }}</td>
                        <td style="text-align:center">
                            <span class="badge badge-{{ $item->stock <= $item->min_stock ? 'danger' : 'success' }}">{{ $item->stock <= $item->min_stock ? 'Low' : 'OK' }}</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><span class="card-title">Stok Dapur ({{ $kitchenStock->count() }})</span></div>
        <div class="card-body" style="padding:0;max-height:500px;overflow-y:auto;">
            <table class="data-table">
                <thead><tr><th>Nama</th><th style="text-align:right">Stok</th><th style="text-align:center">Status</th></tr></thead>
                <tbody>
                    @foreach($kitchenStock as $item)
                    <tr>
                        <td style="color:var(--text-primary);font-weight:500;">{{ $item->name }}</td>
                        <td style="text-align:right">{{ number_format($item->stock, 2) }} {{ $item->unit }}</td>
                        <td style="text-align:center">
                            <span class="badge badge-{{ $item->stock <= $item->min_stock ? 'danger' : 'success' }}">{{ $item->stock <= $item->min_stock ? 'Low' : 'OK' }}</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
