@extends('layouts.app')
@section('title', 'Kategori Bahan')
@section('header-actions')
<a href="{{ route('material-categories.create') }}" class="btn btn-primary btn-sm">
    <i data-lucide="plus" style="width:16px;height:16px;"></i> Tambah Kategori Bahan
</a>
@endsection

@section('content')
<div class="card">
    <div class="card-body" style="padding:0;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Nama Kategori</th>
                    <th>Deskripsi</th>
                    <th style="text-align:center">Status</th>
                    <th style="text-align:center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($categories as $category)
                <tr>
                    <td style="font-weight:600; color:var(--text-primary);">{{ $category->name }}</td>
                    <td>{{ $category->description ?: '-' }}</td>
                    <td style="text-align:center">
                        <span class="badge badge-{{ $category->is_active ? 'success' : 'danger' }}">
                            {{ $category->is_active ? 'Aktif' : 'Non-aktif' }}
                        </span>
                    </td>
                    <td style="text-align:center">
                        <div style="display:flex;gap:4px;justify-content:center;">
                            <a href="{{ route('material-categories.edit', $category->id) }}" class="btn btn-sm btn-secondary btn-icon">
                                <i data-lucide="edit" style="width:14px;height:14px;"></i>
                            </a>
                            <form method="POST" action="{{ route('material-categories.destroy', $category->id) }}" onsubmit="return confirm('Hapus kategori ini?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger btn-icon">
                                    <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="empty-state">Belum ada kategori bahan</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
