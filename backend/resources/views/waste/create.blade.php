@extends('layouts.app')
@section('title', 'Tambah Laporan Waste')
@section('content')
<div style="max-width:540px;">
    <div class="card">
        <div class="card-header"><span class="card-title">Laporkan Waste</span></div>
        <div class="card-body">
            <form method="POST" action="{{ route('waste.store') }}">
                @csrf
                <div class="form-group">
                    <label class="form-label">Sumber *</label>
                    <select name="source_type" class="form-control" id="sourceType" onchange="toggleSource()" required>
                        <option value="gudang">Gudang</option>
                        <option value="kitchen">Dapur</option>
                    </select>
                </div>
                <div class="form-group" id="gudangGroup">
                    <label class="form-label">Item Gudang *</label>
                    <select name="source_id" class="form-control source-select" id="gudangSelect">
                        <option value="">Pilih item</option>
                        @foreach($gudangItems as $item)
                        <option value="{{ $item->id }}">{{ $item->name }} ({{ number_format($item->stock, 2) }} {{ $item->unit }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group" id="kitchenGroup" style="display:none;">
                    <label class="form-label">Item Dapur *</label>
                    <select class="form-control source-select" id="kitchenSelect" disabled>
                        <option value="">Pilih item</option>
                        @foreach($kitchenItems as $item)
                        <option value="{{ $item->id }}">{{ $item->name }} ({{ number_format($item->stock, 2) }} {{ $item->unit }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Jumlah *</label>
                    <input type="number" name="quantity" class="form-control" step="0.01" min="0.01" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Alasan</label>
                    <select name="reason" class="form-control">
                        <option value="">Pilih alasan</option>
                        <option value="expired">Kadaluarsa</option>
                        <option value="damaged">Rusak</option>
                        <option value="spilled">Tumpah</option>
                        <option value="burnt">Gosong</option>
                        <option value="other">Lainnya</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Catatan</label>
                    <textarea name="notes" class="form-control" rows="2"></textarea>
                </div>
                <div style="display:flex;gap:8px;">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                    <a href="{{ route('waste.index') }}" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
function toggleSource() {
    const type = document.getElementById('sourceType').value;
    const gGroup = document.getElementById('gudangGroup');
    const kGroup = document.getElementById('kitchenGroup');
    const gSelect = document.getElementById('gudangSelect');
    const kSelect = document.getElementById('kitchenSelect');
    if (type === 'gudang') {
        gGroup.style.display = ''; kGroup.style.display = 'none';
        gSelect.name = 'source_id'; gSelect.disabled = false;
        kSelect.name = ''; kSelect.disabled = true;
    } else {
        gGroup.style.display = 'none'; kGroup.style.display = '';
        kSelect.name = 'source_id'; kSelect.disabled = false;
        gSelect.name = ''; gSelect.disabled = true;
    }
}
</script>
@endpush
