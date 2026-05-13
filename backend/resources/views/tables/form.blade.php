@extends('layouts.app')
@section('title', isset($table) ? 'Edit Meja' : 'Tambah Meja')
@section('content')
<div style="max-width:480px;">
    <div class="card">
        <div class="card-header"><span class="card-title">{{ isset($table) ? 'Edit Meja' : 'Tambah Meja Baru' }}</span></div>
        <div class="card-body">
            <form method="POST" action="{{ isset($table) ? route('tables.update', $table->id) : route('tables.store') }}">
                @csrf
                @if(isset($table)) @method('PUT') @endif
                <div class="form-group">
                    <label class="form-label">Nomor / Nama Meja *</label>
                    <input type="text" name="table_number" class="form-control" value="{{ old('table_number', $table->table_number ?? '') }}" required placeholder="Contoh: 01">
                </div>
                <div class="form-group">
                    <label class="form-label">Kapasitas (Orang) *</label>
                    <input type="number" name="capacity" class="form-control" value="{{ old('capacity', $table->capacity ?? 4) }}" required min="1">
                </div>
                <div class="form-group">
                    <label class="form-label">Status Meja</label>
                    <select name="status" class="form-control" required>
                        <option value="available" {{ old('status', $table->status ?? '') == 'available' ? 'selected' : '' }}>Tersedia</option>
                        <option value="occupied" {{ old('status', $table->status ?? '') == 'occupied' ? 'selected' : '' }}>Terisi</option>
                        <option value="reserved" {{ old('status', $table->status ?? '') == 'reserved' ? 'selected' : '' }}>Dipesan</option>
                    </select>
                </div>
                <div style="display:flex;gap:8px;margin-top:24px;">
                    <button type="submit" class="btn btn-primary">{{ isset($table) ? 'Perbarui' : 'Simpan' }}</button>
                    <a href="{{ route('tables.index') }}" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
