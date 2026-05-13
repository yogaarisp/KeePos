@extends('layouts.app')
@section('title', 'Laporan Penjualan')
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

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon orange"><i data-lucide="banknote"></i></div>
        <div class="stat-info">
            <div class="stat-label">Total Penjualan</div>
            <div class="stat-value">Rp {{ number_format($totalSales, 0, ',', '.') }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><i data-lucide="receipt"></i></div>
        <div class="stat-info">
            <div class="stat-label">Total Transaksi</div>
            <div class="stat-value">{{ $totalTransactions }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i data-lucide="trending-up"></i></div>
        <div class="stat-info">
            <div class="stat-label">Rata-rata per Transaksi</div>
            <div class="stat-value">Rp {{ number_format($avgTransaction, 0, ',', '.') }}</div>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:24px;">
    <div class="card">
        <div class="card-header"><span class="card-title">Per Metode Pembayaran</span></div>
        <div class="card-body" style="padding:0;">
            <table class="data-table">
                <thead><tr><th>Metode</th><th style="text-align:center">Jumlah</th><th style="text-align:right">Total</th></tr></thead>
                <tbody>
                    @foreach($salesByMethod as $method => $data)
                    <tr>
                        <td><span class="badge badge-info">{{ strtoupper($method) }}</span></td>
                        <td style="text-align:center">{{ $data['count'] }}</td>
                        <td style="text-align:right;font-weight:600;color:var(--accent);">Rp {{ number_format($data['total'], 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><span class="card-title">Penjualan Harian</span></div>
        <div class="card-body">
            <canvas id="dailyChart" height="200"></canvas>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><span class="card-title">Detail Transaksi</span></div>
    <div class="card-body" style="padding:0;">
        <table class="data-table">
            <thead><tr><th>Invoice</th><th>Kasir</th><th>Pembayaran</th><th style="text-align:right">Total</th><th>Waktu</th></tr></thead>
            <tbody>
                @forelse($sales as $sale)
                <tr>
                    <td><strong style="color:var(--text-primary);">{{ $sale->receipt_number }}</strong></td>
                    <td>{{ $sale->cashier_name ?? '-' }}</td>
                    <td><span class="badge badge-{{ $sale->payment_method === 'cash' ? 'success' : 'info' }}">{{ strtoupper($sale->payment_method) }}</span></td>
                    <td style="text-align:right;font-weight:600;color:var(--accent);">{{ $sale->formatted_total }}</td>
                    <td style="font-size:12px;color:var(--text-muted);">{{ $sale->created_at->format('d/m H:i') }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="empty-state">Tidak ada data penjualan</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
@push('scripts')
<script>
const dailyData = @json($salesByDate);
const labels = Object.keys(dailyData).map(d => { const dt = new Date(d); return dt.toLocaleDateString('id-ID', {day:'numeric',month:'short'}); });
const values = Object.values(dailyData).map(d => d.total);
new Chart(document.getElementById('dailyChart'), {
    type: 'line', data: { labels, datasets: [{
        label: 'Penjualan', data: values,
        borderColor: '#f97316', backgroundColor: 'rgba(249,115,22,0.1)',
        fill: true, tension: 0.4, borderWidth: 2, pointRadius: 4, pointBackgroundColor: '#f97316'
    }]},
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } },
        scales: { x: { grid: { display: false }, ticks: { color: '#64748b', font: { size: 11 } } },
            y: { grid: { color: 'rgba(51,65,85,0.5)' }, ticks: { color: '#64748b', callback: v => 'Rp '+(v/1000)+'K' } }
        }
    }
});
</script>
@endpush
