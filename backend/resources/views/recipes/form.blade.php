@extends('layouts.app')
@section('title', isset($recipe) ? 'Edit Resep' : 'Tambah Resep')
@section('content')
<div style="max-width:720px;">
    <div class="card">
        <div class="card-header"><span class="card-title">{{ isset($recipe) ? 'Edit Resep' : 'Tambah Resep Baru' }}</span></div>
        <div class="card-body">
            <form method="POST" action="{{ isset($recipe) ? route('recipes.update', $recipe->id) : route('recipes.store') }}">
                @csrf
                @if(isset($recipe)) @method('PUT') @endif
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div class="form-group">
                        <label class="form-label">Nama Resep *</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $recipe->name ?? '') }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tipe *</label>
                        <select name="type" class="form-control" required>
                            <option value="product" {{ old('type', $recipe->type ?? '') === 'product' ? 'selected' : '' }}>Produk</option>
                            <option value="production" {{ old('type', $recipe->type ?? '') === 'production' ? 'selected' : '' }}>Produksi</option>
                        </select>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div class="form-group">
                        <label class="form-label">Produk</label>
                        <select name="product_id" class="form-control">
                            <option value="">Tidak ada</option>
                            @foreach($products as $p)
                            <option value="{{ $p->id }}" {{ old('product_id', $recipe->product_id ?? '') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Harga Jual *</label>
                        <input type="number" name="selling_price" class="form-control" value="{{ old('selling_price', $recipe->selling_price ?? 0) }}" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Deskripsi</label>
                    <textarea name="description" class="form-control" rows="2">{{ old('description', $recipe->description ?? '') }}</textarea>
                </div>

                <!-- Ingredients -->
                <div style="margin:24px 0 16px;"><strong style="font-size:15px;">Bahan-bahan</strong></div>
                <div id="ingredientsList">
                    @if(isset($recipe))
                        @foreach($recipe->items as $i => $item)
                        <div class="ingredient-row" style="display:grid;grid-template-columns:120px 1fr 100px 100px 40px;gap:8px;margin-bottom:8px;align-items:end;">
                            <div class="form-group" style="margin:0;">
                                <label class="form-label" style="font-size:11px;">Sumber</label>
                                <select name="items[{{ $i }}][ingredient_type]" class="form-control">
                                    <option value="gudang" {{ $item->ingredient_type === 'gudang' ? 'selected' : '' }}>Gudang</option>
                                    <option value="kitchen" {{ $item->ingredient_type === 'kitchen' ? 'selected' : '' }}>Dapur</option>
                                </select>
                            </div>
                            <div class="form-group" style="margin:0;">
                                <label class="form-label" style="font-size:11px;">Bahan</label>
                                <input type="number" name="items[{{ $i }}][ingredient_id]" class="form-control" value="{{ $item->ingredient_id }}" required>
                            </div>
                            <div class="form-group" style="margin:0;">
                                <label class="form-label" style="font-size:11px;">Qty</label>
                                <input type="number" name="items[{{ $i }}][quantity]" class="form-control" step="0.0001" value="{{ $item->quantity }}" required>
                            </div>
                            <div class="form-group" style="margin:0;">
                                <label class="form-label" style="font-size:11px;">Satuan</label>
                                <input type="text" name="items[{{ $i }}][unit]" class="form-control" value="{{ $item->unit }}" required>
                            </div>
                            <button type="button" class="btn btn-sm btn-danger btn-icon" onclick="this.closest('.ingredient-row').remove()" style="height:38px;"><i data-lucide="x" style="width:14px;height:14px;"></i></button>
                        </div>
                        @endforeach
                    @endif
                </div>
                <button type="button" class="btn btn-sm btn-secondary" onclick="addIngredient()" style="margin-bottom:20px;">
                    <i data-lucide="plus" style="width:14px;height:14px;"></i> Tambah Bahan
                </button>

                <div style="display:flex;gap:8px;">
                    <button type="submit" class="btn btn-primary">{{ isset($recipe) ? 'Perbarui' : 'Simpan' }}</button>
                    <a href="{{ route('recipes.index') }}" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
let ingredientIndex = {{ isset($recipe) ? $recipe->items->count() : 0 }};
function addIngredient() {
    const list = document.getElementById('ingredientsList');
    const html = `<div class="ingredient-row" style="display:grid;grid-template-columns:120px 1fr 100px 100px 40px;gap:8px;margin-bottom:8px;align-items:end;">
        <div class="form-group" style="margin:0;"><label class="form-label" style="font-size:11px;">Sumber</label><select name="items[${ingredientIndex}][ingredient_type]" class="form-control"><option value="gudang">Gudang</option><option value="kitchen">Dapur</option></select></div>
        <div class="form-group" style="margin:0;"><label class="form-label" style="font-size:11px;">Bahan ID</label><input type="number" name="items[${ingredientIndex}][ingredient_id]" class="form-control" required></div>
        <div class="form-group" style="margin:0;"><label class="form-label" style="font-size:11px;">Qty</label><input type="number" name="items[${ingredientIndex}][quantity]" class="form-control" step="0.0001" required></div>
        <div class="form-group" style="margin:0;"><label class="form-label" style="font-size:11px;">Satuan</label><input type="text" name="items[${ingredientIndex}][unit]" class="form-control" required></div>
        <button type="button" class="btn btn-sm btn-danger btn-icon" onclick="this.closest('.ingredient-row').remove()" style="height:38px;"><i data-lucide="x" style="width:14px;height:14px;"></i></button>
    </div>`;
    list.insertAdjacentHTML('beforeend', html);
    ingredientIndex++;
    lucide.createIcons();
}
</script>
@endpush
