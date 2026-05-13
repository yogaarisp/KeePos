@extends('layouts.app')
@section('title', 'Laporan Waste')
@section('content')
<form class="filter-bar" method="GET">
    <div class="form-group">
        <label class="form-label">Dari</label>
        <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
    </div>
    <div class="form-group">
        <label class="form-label">Sampai</label>
        <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
    </div>
    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
</form>

<div class="stats-grid" style="margin-bottom:24px;">
    <div class="stat-card">
        <div class="stat-icon red"><i data-lucide="trending-down"></i></div>
        <div class="stat-info">
            <div class="stat-label">Total Kerugian</div>
            <div class="stat-value">Rp {{ number_format($totalLoss, 0, ',', '.') }}</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body" style="padding:0;">
        <table class="data-table">
            <thead><tr><th>Tanggal</th><th>Item</th><th>Sumber</th><th style="text-align:right">Qty</th><th style="text-align:right">Kerugian</th><th>Alasan</th><th>User</th></tr></thead>
            <tbody>
                @forelse($wasteReports as $report)
                <tr>
                    <td>{{ $report->created_at->format('d/m/Y') }}</td>
                    <td><strong style="color:var(--text-primary);">{{ $report->item_name }}</strong></td>
                    <td><span class="badge badge-{{ $report->source_type === 'gudang' ? 'info' : 'warning' }}">{{ ucfirst($report->source_type) }}</span></td>
                    <td style="text-align:right">{{ number_format($report->quantity, 2) }} {{ $report->unit }}</td>
                    <td style="text-align:right;color:var(--danger);font-weight:600;">Rp {{ number_format($report->estimated_loss, 0, ',', '.') }}</td>
                    <td>{{ $report->reason ?? '-' }}</td>
                    <td>{{ $report->user->full_name ?? '-' }}</td>
                </tr>
                @empty
                <tr><td colspan="7" class="empty-state">Tidak ada data waste</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
