@extends('layouts.app')
@section('title', 'Stok Dapur')

@section('header-actions')
<a href="{{ route('kitchen.create') }}" class="btn btn-primary btn-sm">
    <i data-lucide="plus" style="width:16px;height:16px;"></i> Tambah Stok
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
    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
</form>

<div class="card">
    <div class="card-body" style="padding:0;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Kategori</th>
                    <th style="text-align:right">Stok</th>
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
                    <td style="text-align:center">
                        @if($item->stock <= $item->min_stock)
                            <span class="badge badge-danger">Rendah</span>
                        @else
                            <span class="badge badge-success">Aman</span>
                        @endif
                    </td>
                    <td style="text-align:center">
                        <div style="display:flex;gap:4px;justify-content:center;">
                            <a href="{{ route('kitchen.edit', $item->id) }}" class="btn btn-sm btn-secondary btn-icon" title="Edit">
                                <i data-lucide="edit" style="width:14px;height:14px;"></i>
                            </a>
                            <a href="{{ route('kitchen.transactions', $item->id) }}" class="btn btn-sm btn-secondary btn-icon" title="Riwayat">
                                <i data-lucide="history" style="width:14px;height:14px;"></i>
                            </a>
                            <form method="POST" action="{{ route('kitchen.destroy', $item->id) }}" onsubmit="return confirm('Hapus item ini?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger btn-icon" title="Hapus">
                                    <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="empty-state">Belum ada data stok dapur</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="pagination">{{ $items->withQueryString()->links() }}</div>
@endsection
