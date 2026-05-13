@extends('layouts.app')
@section('title', isset($product) ? 'Edit Produk' : 'Tambah Produk')
@section('content')
<div style="max-width:640px;">
    <div class="card">
        <div class="card-header"><span class="card-title">{{ isset($product) ? 'Edit Produk' : 'Tambah Produk Baru' }}</span></div>
        <div class="card-body">
            <form method="POST" action="{{ isset($product) ? route('products.update', $product->id) : route('products.store') }}" enctype="multipart/form-data">
                @csrf
                @if(isset($product)) @method('PUT') @endif
                <div class="form-group">
                    <label class="form-label">Nama Produk *</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $product->name ?? '') }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Deskripsi</label>
                    <textarea name="description" class="form-control" rows="2">{{ old('description', $product->description ?? '') }}</textarea>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div class="form-group">
                        <label class="form-label">Harga *</label>
                        <input type="number" name="price" class="form-control" value="{{ old('price', $product->price ?? 0) }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Kategori *</label>
                        <select name="category_id" class="form-control" required>
                            <option value="">Pilih</option>
                            @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('category_id', $product->category_id ?? '') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Gambar</label>
                    <input type="file" name="image" class="form-control" accept="image/*">
                    @if(isset($product) && $product->image)
                    <div style="margin-top:8px;"><img src="{{ asset('storage/' . $product->image) }}" style="width:80px;height:80px;border-radius:8px;object-fit:cover;"></div>
                    @endif
                </div>
                @if(isset($customCategories) && $customCategories->count())
                <div class="form-group">
                    <label class="form-label">Kustomisasi</label>
                    @foreach($customCategories as $cc)
                    <label style="display:flex;align-items:center;gap:8px;padding:6px 0;font-size:13px;color:var(--text-secondary);cursor:pointer;">
                        <input type="checkbox" name="custom_categories[]" value="{{ $cc->id }}"
                            {{ isset($product) && $product->customCategories->contains($cc->id) ? 'checked' : '' }}
                            style="accent-color:var(--accent);">
                        {{ $cc->name }} ({{ $cc->type }})
                    </label>
                    @endforeach
                </div>
                @endif
                <div class="form-group">
                    <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--text-secondary);cursor:pointer;">
                        <input type="checkbox" name="is_available" value="1"
                            {{ old('is_available', $product->is_available ?? true) ? 'checked' : '' }}
                            style="accent-color:var(--accent);">
                        Tersedia
                    </label>
                </div>
                <div style="display:flex;gap:8px;">
                    <button type="submit" class="btn btn-primary">{{ isset($product) ? 'Perbarui' : 'Simpan' }}</button>
                    <a href="{{ route('products.index') }}" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
