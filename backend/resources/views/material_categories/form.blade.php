@extends('layouts.app')
@section('title', isset($category) ? 'Edit Kategori Bahan' : 'Tambah Kategori Bahan')
@section('content')
<div style="max-width:480px;">
    <div class="card">
        <div class="card-header"><span class="card-title">{{ isset($category) ? 'Edit Kategori Bahan' : 'Tambah Kategori Bahan Baru' }}</span></div>
        <div class="card-body">
            <form method="POST" action="{{ isset($category) ? route('material-categories.update', $category->id) : route('material-categories.store') }}">
                @csrf
                @if(isset($category)) @method('PUT') @endif
                <div class="form-group">
                    <label class="form-label">Nama Kategori Bahan *</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $category->name ?? '') }}" required placeholder="Contoh: Sayuran, Sembako, Daging">
                </div>
                <div class="form-group">
                    <label class="form-label">Deskripsi</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Penjelasan singkat tentang kategori ini...">{{ old('description', $category->description ?? '') }}</textarea>
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
                    <a href="{{ route('material-categories.index') }}" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
