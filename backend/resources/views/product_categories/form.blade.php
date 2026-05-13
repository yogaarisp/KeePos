@extends('layouts.app')
@section('title', isset($category) ? 'Edit Kategori Menu' : 'Tambah Kategori Menu')
@section('content')
<div style="max-width:480px;">
    <div class="card">
        <div class="card-header"><span class="card-title">{{ isset($category) ? 'Edit Kategori Menu' : 'Tambah Kategori Menu Baru' }}</span></div>
        <div class="card-body">
            <form method="POST" action="{{ isset($category) ? route('product-categories.update', $category->id) : route('product-categories.store') }}">
                @csrf
                @if(isset($category)) @method('PUT') @endif
                <div class="form-group">
                    <label class="form-label">Nama Kategori Menu *</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $category->name ?? '') }}" required placeholder="Contoh: Makanan Berat">
                </div>
                <div class="form-group">
                    <label class="form-label">Ikon (Emoji / Teks)</label>
                    <input type="text" name="icon" class="form-control" value="{{ old('icon', $category->icon ?? '') }}" placeholder="Contoh: 🍱">
                </div>
                <div class="form-group">
                    <label class="form-label">Urutan Tampil</label>
                    <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $category->sort_order ?? 0) }}">
                </div>
                <div class="form-group">
                    <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--text-secondary);cursor:pointer;">
                        <input type="checkbox" name="is_active" value="1"
                            {{ old('is_active', $category->is_active ?? true) ? 'checked' : '' }}
                            style="accent-color:var(--accent);">
                        Aktifkan Kategori
                    </label>
                </div>
                <div style="display:flex;gap:8px;margin-top:24px;">
                    <button type="submit" class="btn btn-primary">{{ isset($category) ? 'Perbarui' : 'Simpan' }}</button>
                    <a href="{{ route('product-categories.index') }}" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
