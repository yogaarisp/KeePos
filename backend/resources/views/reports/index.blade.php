@extends('layouts.app')
@section('title', 'Laporan')
@section('content')
<div class="stats-grid">
    <a href="{{ route('reports.sales') }}" class="stat-card" style="text-decoration:none;">
        <div class="stat-icon orange"><i data-lucide="bar-chart-3"></i></div>
        <div class="stat-info">
            <div class="stat-label">Laporan Penjualan</div>
            <div class="stat-value" style="font-size:16px;">Sales Report</div>
            <div class="stat-detail">Lihat detail penjualan per periode</div>
        </div>
    </a>
    <a href="{{ route('reports.stock') }}" class="stat-card" style="text-decoration:none;">
        <div class="stat-icon blue"><i data-lucide="package"></i></div>
        <div class="stat-info">
            <div class="stat-label">Laporan Stok</div>
            <div class="stat-value" style="font-size:16px;">Stock Report</div>
            <div class="stat-detail">Gudang & dapur inventory</div>
        </div>
    </a>
    <a href="{{ route('reports.waste') }}" class="stat-card" style="text-decoration:none;">
        <div class="stat-icon red"><i data-lucide="trash-2"></i></div>
        <div class="stat-info">
            <div class="stat-label">Laporan Waste</div>
            <div class="stat-value" style="font-size:16px;">Waste Report</div>
            <div class="stat-detail">Kerugian dari waste</div>
        </div>
    </a>
</div>
@endsection
