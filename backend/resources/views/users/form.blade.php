@extends('layouts.app')
@section('title', isset($user) ? 'Edit User' : 'Tambah User')
@section('content')
<div style="max-width:540px;">
    <div class="card">
        <div class="card-header"><span class="card-title">{{ isset($user) ? 'Edit User' : 'Tambah User Baru' }}</span></div>
        <div class="card-body">
            <form method="POST" action="{{ isset($user) ? route('users.update', $user->id) : route('users.store') }}">
                @csrf
                @if(isset($user)) @method('PUT') @endif
                <div class="form-group">
                    <label class="form-label">Nama Lengkap *</label>
                    <input type="text" name="full_name" class="form-control" value="{{ old('full_name', $user->full_name ?? '') }}" required>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div class="form-group">
                        <label class="form-label">Username *</label>
                        <input type="text" name="username" class="form-control" value="{{ old('username', $user->username ?? '') }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Role *</label>
                        <select name="role" class="form-control" required>
                            <option value="kasir" {{ old('role', $user->role ?? '') === 'kasir' ? 'selected' : '' }}>Kasir</option>
                            <option value="admin" {{ old('role', $user->role ?? '') === 'admin' ? 'selected' : '' }}>Admin</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Email *</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email', $user->email ?? '') }}" required>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div class="form-group">
                        <label class="form-label">Password {{ isset($user) ? '(kosongkan jika tidak diubah)' : '*' }}</label>
                        <input type="password" name="password" class="form-control" {{ isset($user) ? '' : 'required' }}>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Konfirmasi Password</label>
                        <input type="password" name="password_confirmation" class="form-control" {{ isset($user) ? '' : 'required' }}>
                    </div>
                </div>
                @if(isset($user))
                <div class="form-group">
                    <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--text-secondary);cursor:pointer;">
                        <input type="checkbox" name="is_active" value="1" {{ $user->is_active ? 'checked' : '' }} style="accent-color:var(--accent);">
                        Aktif
                    </label>
                </div>
                @endif
                <div style="display:flex;gap:8px;">
                    <button type="submit" class="btn btn-primary">{{ isset($user) ? 'Perbarui' : 'Simpan' }}</button>
                    <a href="{{ route('users.index') }}" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
