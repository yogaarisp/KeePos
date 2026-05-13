@extends('layouts.app')
@section('title', 'Resep')
@section('header-actions')
<a href="{{ route('recipes.create') }}" class="btn btn-primary btn-sm"><i data-lucide="plus" style="width:16px;height:16px;"></i> Tambah Resep</a>
@endsection
@section('content')
<div class="card">
    <div class="card-body" style="padding:0;">
        <table class="data-table">
            <thead><tr><th>Nama</th><th>Produk</th><th>Tipe</th><th style="text-align:right">HPP</th><th style="text-align:right">Harga Jual</th><th style="text-align:right">Margin</th><th style="text-align:center">Aksi</th></tr></thead>
            <tbody>
                @forelse($recipes as $recipe)
                <tr>
                    <td><strong style="color:var(--text-primary);">{{ $recipe->name }}</strong></td>
                    <td>{{ $recipe->product->name ?? '-' }}</td>
                    <td><span class="badge badge-{{ $recipe->type === 'product' ? 'info' : 'warning' }}">{{ ucfirst($recipe->type) }}</span></td>
                    <td style="text-align:right">Rp {{ number_format($recipe->hpp, 0, ',', '.') }}</td>
                    <td style="text-align:right;font-weight:600;">Rp {{ number_format($recipe->selling_price, 0, ',', '.') }}</td>
                    <td style="text-align:right;color:{{ $recipe->margin > 30 ? 'var(--success)' : ($recipe->margin > 15 ? 'var(--warning)' : 'var(--danger)') }};">{{ $recipe->margin }}%</td>
                    <td style="text-align:center">
                        <div style="display:flex;gap:4px;justify-content:center;">
                            <a href="{{ route('recipes.show', $recipe->id) }}" class="btn btn-sm btn-secondary btn-icon"><i data-lucide="eye" style="width:14px;height:14px;"></i></a>
                            <a href="{{ route('recipes.edit', $recipe->id) }}" class="btn btn-sm btn-secondary btn-icon"><i data-lucide="edit" style="width:14px;height:14px;"></i></a>
                            <form method="POST" action="{{ route('recipes.destroy', $recipe->id) }}" onsubmit="return confirm('Hapus resep?')">@csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger btn-icon"><i data-lucide="trash-2" style="width:14px;height:14px;"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="empty-state">Belum ada resep</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="pagination">{{ $recipes->links() }}</div>
@endsection
