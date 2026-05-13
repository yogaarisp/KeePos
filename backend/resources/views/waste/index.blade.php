@extends('layouts.app')
@section('title', 'Laporan Waste')
@section('header-actions')
<a href="{{ route('waste.create') }}" class="btn btn-primary btn-sm"><i data-lucide="plus" style="width:16px;height:16px;"></i> Tambah Laporan</a>
@endsection
@section('content')
<form class="filter-bar" method="GET">
    <div class="form-group">
        <label class="form-label">Dari</label>
        <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
    </div>
    <div class="form-group">
        <label class="form-label">Sampai</label>
        <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
    </div>
    <div class="form-group">
        <label class="form-label">Sumber</label>
        <select name="source_type" class="form-control">
            <option value="">Semua</option>
            <option value="gudang" {{ request('source_type') === 'gudang' ? 'selected' : '' }}>Gudang</option>
            <option value="kitchen" {{ request('source_type') === 'kitchen' ? 'selected' : '' }}>Dapur</option>
        </select>
    </div>
    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
</form>

<div class="card">
    <div class="card-body" style="padding:0;">
        <table class="data-table">
            <thead><tr><th>Tanggal</th><th>Item</th><th>Sumber</th><th style="text-align:right">Qty</th><th style="text-align:right">Kerugian</th><th>Alasan</th><th>User</th><th style="text-align:center">Aksi</th></tr></thead>
            <tbody>
                @forelse($reports as $report)
                <tr>
                    <td>{{ $report->created_at->format('d/m/Y') }}</td>
                    <td><strong style="color:var(--text-primary);">{{ $report->item_name }}</strong></td>
                    <td><span class="badge badge-{{ $report->source_type === 'gudang' ? 'info' : 'warning' }}">{{ ucfirst($report->source_type) }}</span></td>
                    <td style="text-align:right">{{ number_format($report->quantity, 2) }} {{ $report->unit }}</td>
                    <td style="text-align:right;color:var(--danger);font-weight:600;">Rp {{ number_format($report->estimated_loss, 0, ',', '.') }}</td>
                    <td>{{ $report->reason ?? '-' }}</td>
                    <td>{{ $report->user->full_name ?? '-' }}</td>
                    <td style="text-align:center">
                        <form method="POST" action="{{ route('waste.destroy', $report->id) }}" onsubmit="return confirm('Hapus?')">@csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger btn-icon"><i data-lucide="trash-2" style="width:14px;height:14px;"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="empty-state">Belum ada laporan waste</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="pagination">{{ $reports->links() }}</div>
@endsection
