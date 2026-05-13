@extends('layouts.app')
@section('title', 'Detail Resep - ' . $recipe->name)
@section('content')
<div class="mb-4"><a href="{{ route('recipes.index') }}" class="btn btn-secondary btn-sm"><i data-lucide="arrow-left" style="width:16px;height:16px;"></i> Kembali</a></div>
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon red"><i data-lucide="calculator"></i></div>
        <div class="stat-info"><div class="stat-label">HPP</div><div class="stat-value">Rp {{ number_format($profitability['hpp'], 0, ',', '.') }}</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i data-lucide="tag"></i></div>
        <div class="stat-info"><div class="stat-label">Harga Jual</div><div class="stat-value">Rp {{ number_format($profitability['selling_price'], 0, ',', '.') }}</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i data-lucide="trending-up"></i></div>
        <div class="stat-info"><div class="stat-label">Profit</div><div class="stat-value">Rp {{ number_format($profitability['profit'], 0, ',', '.') }}</div><div class="stat-detail">Margin: {{ $profitability['margin'] }}%</div></div>
    </div>
</div>
<div class="card">
    <div class="card-header"><span class="card-title">Bahan-bahan</span></div>
    <div class="card-body" style="padding:0;">
        <table class="data-table">
            <thead><tr><th>Sumber</th><th>ID Bahan</th><th style="text-align:right">Qty</th><th>Satuan</th><th style="text-align:right">Biaya</th></tr></thead>
            <tbody>
                @foreach($recipe->items as $item)
                <tr>
                    <td><span class="badge badge-{{ $item->ingredient_type === 'gudang' ? 'info' : 'warning' }}">{{ ucfirst($item->ingredient_type) }}</span></td>
                    <td>{{ $item->ingredient_id }}</td>
                    <td style="text-align:right">{{ number_format($item->quantity, 4) }}</td>
                    <td>{{ $item->unit }}</td>
                    <td style="text-align:right">Rp {{ number_format($item->cost ?? 0, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
