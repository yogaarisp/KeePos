@extends('layouts.app')
@section('title', 'Stok Gudang')

@section('header-actions')
<a href="{{ route('warehouse.create') }}" class="btn btn-primary btn-sm">
    <i data-lucide="plus" style="width:16px;height:16px;"></i> Tambah Bahan
</a>
@endsection

@section('content')
<form class="filter-bar" method="GET">
    <div class="form-group">
        <label class="form-label">Cari</label>
        <input type="text" name="search" class="form-control" placeholder="Nama bahan..." value="{{ request('search') }}">
    </div>
    <div class="form-group">
        <label class="form-label">Kategori</label>
        <select name="category_id" class="form-control">
            <option value="">Semua</option>
            @foreach($categories as $cat)
            <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group">
        <label class="form-label">Filter</label>
        <label style="display:flex;align-items:center;gap:6px;padding:10px 0;font-size:13px;color:var(--text-secondary);cursor:pointer;">
            <input type="checkbox" name="low_stock" value="1" {{ request('low_stock') ? 'checked' : '' }} style="accent-color:var(--accent);">
            Stok rendah saja
        </label>
    </div>
    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
</form>

<div class="card">
    <div class="card-body" style="padding:0;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Nama Bahan</th>
                    <th>Kategori</th>
                    <th style="text-align:right">Stok</th>
                    <th style="text-align:right">Harga/Unit</th>
                    <th style="text-align:right">Total Nilai</th>
                    <th style="text-align:center">Status</th>
                    <th style="text-align:center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                <tr>
                    <td><strong style="color:var(--text-primary);">{{ $item->name }}</strong></td>
                    <td>{{ $item->category->name ?? '-' }}</td>
                    <td style="text-align:right">{{ number_format($item->stock, 2) }} {{ $item->unit }}</td>
                    <td style="text-align:right">Rp {{ number_format($item->price_per_unit, 0, ',', '.') }}</td>
                    <td style="text-align:right">Rp {{ number_format($item->total_value, 0, ',', '.') }}</td>
                    <td style="text-align:center">
                        @if($item->stock <= $item->min_stock)
                            <span class="badge badge-danger">Rendah</span>
                        @else
                            <span class="badge badge-success">Aman</span>
                        @endif
                    </td>
                    <td style="text-align:center">
                        <div style="display:flex;gap:4px;justify-content:center;">
                            <button class="btn btn-sm btn-success btn-icon" onclick="showStockModal({{ $item->id }}, 'add')" title="Tambah Stok">
                                <i data-lucide="plus" style="width:14px;height:14px;"></i>
                            </button>
                            <button class="btn btn-sm btn-danger btn-icon" onclick="showStockModal({{ $item->id }}, 'reduce')" title="Kurangi Stok">
                                <i data-lucide="minus" style="width:14px;height:14px;"></i>
                            </button>
                            <a href="{{ route('warehouse.edit', $item->id) }}" class="btn btn-sm btn-secondary btn-icon" title="Edit">
                                <i data-lucide="edit" style="width:14px;height:14px;"></i>
                            </a>
                            <a href="{{ route('warehouse.transactions', $item->id) }}" class="btn btn-sm btn-secondary btn-icon" title="Riwayat">
                                <i data-lucide="history" style="width:14px;height:14px;"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="empty-state">Belum ada data bahan</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="pagination">{{ $items->withQueryString()->links() }}</div>

<!-- Stock Modal -->
<div class="modal-overlay" id="stockModal">
    <div class="modal">
        <div class="modal-header">
            <h3 id="stockModalTitle">Tambah Stok</h3>
            <button class="header-btn" onclick="closeStockModal()" style="width:32px;height:32px;">
                <i data-lucide="x" style="width:16px;height:16px;"></i>
            </button>
        </div>
        <form id="stockForm" method="POST">
            @csrf
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Jumlah</label>
                    <input type="number" name="quantity" class="form-control" step="0.01" min="0.01" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Catatan</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Opsional..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeStockModal()">Batal</button>
                <button type="submit" class="btn btn-primary" id="stockSubmitBtn">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function showStockModal(id, type) {
    const modal = document.getElementById('stockModal');
    const form = document.getElementById('stockForm');
    const title = document.getElementById('stockModalTitle');
    const btn = document.getElementById('stockSubmitBtn');

    if (type === 'add') {
        form.action = `/warehouse/${id}/add-stock`;
        title.textContent = 'Tambah Stok';
        btn.textContent = 'Tambah';
        btn.className = 'btn btn-success';
    } else {
        form.action = `/warehouse/${id}/reduce-stock`;
        title.textContent = 'Kurangi Stok';
        btn.textContent = 'Kurangi';
        btn.className = 'btn btn-danger';
    }

    modal.classList.add('active');
    lucide.createIcons();
}

function closeStockModal() {
    document.getElementById('stockModal').classList.remove('active');
}
</script>
@endpush
