@extends('layouts.app')
@section('title', 'Kelola Meja')
@section('header-actions')
<a href="{{ route('tables.create') }}" class="btn btn-primary btn-sm">
    <i data-lucide="plus" style="width:16px;height:16px;"></i> Tambah Meja
</a>
@endsection

@section('content')
<div class="card">
    <div class="card-body" style="padding:0;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Nomor Meja</th>
                    <th>Kapasitas</th>
                    <th style="text-align:center">Status</th>
                    <th style="text-align:center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tables as $table)
                <tr>
                    <td style="font-weight:700; font-size:16px; color:var(--accent);">Meja {{ $table->table_number }}</td>
                    <td>{{ $table->capacity }} Orang</td>
                    <td style="text-align:center">
                        @php
                            $statusClass = [
                                'available' => 'success',
                                'occupied' => 'danger',
                                'reserved' => 'warning'
                            ][$table->status] ?? 'info';
                            $statusLabel = [
                                'available' => 'Tersedia',
                                'occupied' => 'Terisi',
                                'reserved' => 'Dipesan'
                            ][$table->status] ?? $table->status;
                        @endphp
                        <span class="badge badge-{{ $statusClass }}">{{ $statusLabel }}</span>
                    </td>
                    <td style="text-align:center">
                        <div style="display:flex;gap:4px;justify-content:center;">
                            <a href="{{ route('tables.edit', $table->id) }}" class="btn btn-sm btn-secondary btn-icon">
                                <i data-lucide="edit" style="width:14px;height:14px;"></i>
                            </a>
                            <form method="POST" action="{{ route('tables.destroy', $table->id) }}" onsubmit="return confirm('Hapus meja ini?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger btn-icon">
                                    <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="empty-state">Belum ada meja</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
