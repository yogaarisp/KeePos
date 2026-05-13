@php
    /** @var \Illuminate\Pagination\LengthAwarePaginator $products */
    /** @var \App\Models\CategoryKasir[] $categories */
@endphp
@extends('layouts.app')
@section('title', 'Produk')
@section('header-actions')
<a href="{{ route('products.create') }}" class="btn btn-primary btn-sm">
    <i data-lucide="plus" style="width:16px;height:16px;"></i> Tambah Produk
</a>
@endsection

@section('content')
<form class="filter-bar" method="GET">
    <div class="form-group">
        <label class="form-label">Cari</label>
        <input type="text" name="search" class="form-control" placeholder="Nama produk..." value="{{ request('search') }}">
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
            <thead><tr><th>Produk</th><th>Kategori</th><th style="text-align:right">Harga</th><th style="text-align:center">Status</th><th style="text-align:center">Aksi</th></tr></thead>
            <tbody>
                @forelse($products as $product)
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:12px;">
                            <div style="width:40px;height:40px;border-radius:8px;background:var(--bg-card-hover);overflow:hidden;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                @if($product->image)
                                <img src="{{ asset('storage/' . $product->image) }}" style="width:100%;height:100%;object-fit:cover;">
                                @else
                                <i data-lucide="utensils" style="width:18px;height:18px;color:var(--text-muted);"></i>
                                @endif
                            </div>
                            <div>
                                <div style="font-weight:600;color:var(--text-primary);">{{ $product->name }}</div>
                                @if($product->description)
                                <div style="font-size:12px;color:var(--text-muted);">{{ Str::limit($product->description, 40) }}</div>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td>{{ $product->category->name ?? '-' }}</td>
                    <td style="text-align:right;font-weight:600;color:var(--accent);">{{ $product->formatted_price }}</td>
                    <td style="text-align:center">
                        <span class="badge badge-{{ $product->is_available ? 'success' : 'danger' }}">{{ $product->is_available ? 'Tersedia' : 'Habis' }}</span>
                    </td>
                    <td style="text-align:center">
                        <div style="display:flex;gap:4px;justify-content:center;">
                            <a href="{{ route('products.edit', $product->id) }}" class="btn btn-sm btn-secondary btn-icon"><i data-lucide="edit" style="width:14px;height:14px;"></i></a>
                            <form method="POST" action="{{ route('products.destroy', $product->id) }}" onsubmit="return confirm('Hapus produk ini?')">@csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger btn-icon"><i data-lucide="trash-2" style="width:14px;height:14px;"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="empty-state">Belum ada produk</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="pagination">{{ $products->withQueryString()->links() }}</div>
@endsection
