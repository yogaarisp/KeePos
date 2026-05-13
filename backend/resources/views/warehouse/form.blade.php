@extends('layouts.app')
@section('title', isset($item) ? 'Edit Bahan' : 'Tambah Bahan')

@section('content')
<div style="max-width:640px;">
    <div class="card">
        <div class="card-header">
            <span class="card-title">{{ isset($item) ? 'Edit Bahan' : 'Tambah Bahan Baru' }}</span>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ isset($item) ? route('warehouse.update', $item->id) : route('warehouse.store') }}">
                @csrf
                @if(isset($item)) @method('PUT') @endif

                <div class="form-group">
                    <label class="form-label">Nama Bahan *</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $item->name ?? '') }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Kategori *</label>
                    <select name="category_id" class="form-control" required>
                        <option value="">Pilih Kategori</option>
                        @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ old('category_id', $item->category_id ?? '') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                @if(!isset($item))
                <div class="form-group">
                    <label class="form-label">Stok Awal *</label>
                    <input type="number" name="stock" class="form-control" step="0.01" value="{{ old('stock', 0) }}" required>
                </div>
                @endif
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div class="form-group">
                        <label class="form-label">Satuan *</label>
                        <input type="text" name="unit" class="form-control" placeholder="kg, liter, pcs..." value="{{ old('unit', $item->unit ?? '') }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Harga per Unit *</label>
                        <input type="number" name="price_per_unit" class="form-control" step="1" value="{{ old('price_per_unit', $item->price_per_unit ?? 0) }}" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Minimum Stok</label>
                    <input type="number" name="min_stock" class="form-control" step="0.01" value="{{ old('min_stock', $item->min_stock ?? 0) }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Catatan</label>
                    <textarea name="notes" class="form-control" rows="2">{{ old('notes', $item->notes ?? '') }}</textarea>
                </div>

                <div style="display:flex;gap:8px;">
                    <button type="submit" class="btn btn-primary">{{ isset($item) ? 'Perbarui' : 'Simpan' }}</button>
                    <a href="{{ route('warehouse.index') }}" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
