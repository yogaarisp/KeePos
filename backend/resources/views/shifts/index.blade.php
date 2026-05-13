@extends('layouts.app')
@section('title', 'Shift Management')

@push('styles')
<style>
    /* Premium Shift Modal Styles */
    .shift-modal {
        max-width: 460px !important;
        border-radius: 32px !important;
        overflow: hidden;
        border: none !important;
    }
    .shift-modal .modal-header {
        border-bottom: none;
        padding: 32px 32px 16px;
        justify-content: center;
    }
    .shift-modal .modal-title {
        font-size: 22px;
        font-weight: 800;
        color: #1e293b;
    }
    .shift-modal .modal-body {
        padding: 0 32px 32px;
    }
    
    .welcome-alert {
        background: #fff7ed;
        border: 1px solid #ffedd5;
        border-radius: 16px;
        padding: 16px;
        margin-bottom: 24px;
        display: flex;
        gap: 12px;
    }
    .welcome-alert .alert-icon {
        width: 24px;
        height: 24px;
        background: #f97316;
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 14px;
        font-weight: bold;
    }
    .welcome-alert .alert-content h4 {
        color: #ea580c;
        font-size: 13px;
        font-weight: 800;
        margin-bottom: 4px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .welcome-alert .alert-content p {
        color: #7c4a03;
        font-size: 13px;
        line-height: 1.5;
        font-weight: 500;
    }

    .shift-input-group {
        margin-bottom: 8px;
    }
    .shift-input-label {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        font-weight: 700;
        color: #334155;
        margin-bottom: 12px;
    }
    .shift-input-label i { color: #f97316; }
    .shift-input-label span { color: #ef4444; }

    .amount-input-wrapper {
        position: relative;
        background: #f8fafc;
        border: 2px solid #e2e8f0;
        border-radius: 16px;
        padding: 12px 20px;
        display: flex;
        align-items: center;
        transition: all 0.2s;
    }
    .amount-input-wrapper:focus-within {
        border-color: #f97316;
        background: #fff;
        box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.1);
    }
    .amount-prefix {
        font-size: 18px;
        font-weight: 800;
        color: #94a3b8;
        margin-right: 12px;
    }
    .amount-input {
        background: transparent;
        border: none;
        width: 100%;
        font-size: 20px;
        font-weight: 800;
        color: #1e293b;
        outline: none;
        padding: 0;
    }

    .helper-text {
        font-size: 12px;
        color: #94a3b8;
        font-style: italic;
        margin-bottom: 32px;
    }

    .modal-actions {
        display: flex;
        gap: 12px;
    }
    .btn-start-shift {
        flex: 1.5;
        background: linear-gradient(135deg, #f97316, #fb923c);
        color: white;
        border: none;
        border-radius: 16px;
        padding: 16px;
        font-size: 15px;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        cursor: pointer;
        transition: all 0.2s;
        box-shadow: 0 4px 12px rgba(249, 115, 22, 0.3);
    }
    .btn-start-shift:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(249, 115, 22, 0.4);
    }
    .btn-later {
        flex: 1;
        background: #64748b;
        color: white;
        border: none;
        border-radius: 16px;
        padding: 16px;
        font-size: 15px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s;
    }
    .btn-later:hover {
        background: #475569;
    }

    /* Light mode specific for the modal */
    .light .shift-modal {
        background: #fff !important;
    }
    .light .shift-modal .modal-title { color: #1e293b; }
</style>
@endpush

@section('content')
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:24px;">
    <div class="card">
        <div class="card-header">
            <span class="card-title">Manajemen Shift</span>
        </div>
        <div class="card-body">
            <div id="activeShiftContainer">
                <!-- Will be populated by JS -->
                <p class="text-muted">Memuat status shift...</p>
            </div>
        </div>
    </div>
    <div class="card" style="display:flex; align-items:center; justify-content:center; background:var(--accent-light);">
        <button class="btn btn-primary" onclick="showOpenShiftModal()" style="padding:12px 24px; font-weight:700;">
            <i data-lucide="play" style="width:18px;height:18px;"></i> MULAI SHIFT BARU
        </button>
    </div>
</div>

<!-- Shift History -->
<div class="card">
    <div class="card-header"><span class="card-title">Riwayat Shift</span></div>
    <div class="card-body" style="padding:0;">
        <table class="data-table">
            <thead><tr><th>User</th><th>Dibuka</th><th>Ditutup</th><th style="text-align:right">Modal</th><th style="text-align:right">Total Sales</th><th style="text-align:right">Selisih</th><th style="text-align:center">Status</th><th style="text-align:center">Aksi</th></tr></thead>
            <tbody>
                @forelse($shifts as $shift)
                <tr>
                    <td><strong style="color:var(--text-primary);">{{ $shift->user->full_name ?? '-' }}</strong></td>
                    <td>{{ $shift->opened_at->format('d/m H:i') }}</td>
                    <td>{{ $shift->closed_at ? $shift->closed_at->format('d/m H:i') : '-' }}</td>
                    <td style="text-align:right">Rp {{ number_format($shift->initial_cash, 0, ',', '.') }}</td>
                    <td style="text-align:right;font-weight:600;color:var(--accent);">Rp {{ number_format($shift->total_sales, 0, ',', '.') }}</td>
                    <td style="text-align:right">
                        @if($shift->closed_at)
                        <span style="color:{{ $shift->variance >= 0 ? 'var(--success)' : 'var(--danger)' }}">
                            Rp {{ number_format($shift->variance, 0, ',', '.') }}
                        </span>
                        @else - @endif
                    </td>
                    <td style="text-align:center">
                        @if($shift->closed_at)
                            <span class="badge badge-success">Selesai</span>
                        @else
                            <span class="badge badge-warning">Aktif</span>
                        @endif
                    </td>
                    <td style="text-align:center">
                        <div style="display:flex;gap:4px;justify-content:center;">
                            @if(!$shift->closed_at)
                            <button class="btn btn-sm btn-danger" onclick="showCloseModal({{ $shift->id }})">
                                <i data-lucide="square" style="width:14px;height:14px;"></i> Tutup
                            </button>
                            @endif
                            <a href="{{ route('shifts.show', $shift->id) }}" class="btn btn-sm btn-secondary btn-icon"><i data-lucide="eye" style="width:14px;height:14px;"></i></a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="empty-state">Belum ada shift</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="pagination">{{ $shifts->links() }}</div>

<!-- NEW OPEN SHIFT MODAL (Image Reference) -->
<div class="modal-overlay" id="openShiftModal">
    <div class="modal shift-modal">
        <div class="modal-header">
            <h2 class="modal-title">Mulai Shift Baru</h2>
        </div>
        <form method="POST" action="{{ route('shifts.open') }}">
            @csrf
            <div class="modal-body">
                <div class="welcome-alert">
                    <div class="alert-icon">i</div>
                    <div class="alert-content">
                        <h4>SELAMAT DATANG!</h4>
                        <p>Anda perlu membuka shift terlebih dahulu sebelum dapat melakukan transaksi POS.</p>
                    </div>
                </div>

                <div class="shift-input-group">
                    <label class="shift-input-label">
                        <i data-lucide="wallet" style="width:18px;height:18px;"></i>
                        Modal Awal / Uang Laci <span>*</span>
                    </label>
                    <div class="amount-input-wrapper">
                        <span class="amount-prefix">Rp</span>
                        <input type="number" name="initial_cash" class="amount-input" value="0" required onclick="this.select()">
                    </div>
                </div>
                <p class="helper-text">* Masukkan jumlah uang tunai awal di laci kasir</p>

                <div class="modal-actions">
                    <button type="submit" class="btn-start-shift">
                        <i data-lucide="play-circle" style="width:20px;height:20px;"></i>
                        Mulai Shift Sekarang
                    </button>
                    <button type="button" class="btn-later" onclick="closeModal('openShiftModal')">
                        Nanti Saja
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Close Shift Modal -->
<div class="modal-overlay" id="closeModal">
    <div class="modal">
        <div class="modal-header">
            <h3>Tutup Shift</h3>
            <button class="header-btn" onclick="closeModal('closeModal')" style="width:32px;height:32px;"><i data-lucide="x" style="width:16px;height:16px;"></i></button>
        </div>
        <form id="closeForm" method="POST">
            @csrf
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Uang Aktual di Laci</label>
                    <input type="number" name="actual_cash" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Catatan</label>
                    <textarea name="notes" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('closeModal')">Batal</button>
                <button type="submit" class="btn btn-danger">Tutup Shift</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function showOpenShiftModal() {
    document.getElementById('openShiftModal').classList.add('active');
    lucide.createIcons();
}

function showCloseModal(id) {
    document.getElementById('closeForm').action = `/shifts/${id}/close`;
    document.getElementById('closeModal').classList.add('active');
    lucide.createIcons();
}

function closeModal(id) {
    document.getElementById(id).classList.remove('active');
}

// Load active shift
fetch('{{ route("shifts.active") }}')
    .then(r => r.json())
    .then(data => {
        const el = document.getElementById('activeShiftContainer');
        if (data.has_active_shift) {
            el.innerHTML = `<div style="display:flex;align-items:center;gap:12px; padding:10px; background:var(--bg-primary); border-radius:12px;">
                <div style="width:12px;height:12px;border-radius:50%;background:var(--success);animation:pulse 2s infinite; flex-shrink:0;"></div>
                <div style="flex:1;">
                    <div style="font-size:14px; font-weight:700; color:var(--success);">Shift Aktif Sedang Berjalan</div>
                    <div class="text-muted" style="font-size:12px; margin-top:2px;">Dibuka: ${new Date(data.shift.opened_at).toLocaleString('id-ID')}</div>
                    <div style="font-size:13px; font-weight:600; margin-top:4px;">Modal: Rp ${Number(data.shift.initial_cash).toLocaleString('id-ID')}</div>
                </div>
                <button class="btn btn-sm btn-danger" onclick="showCloseModal(${data.shift.id})">Tutup</button>
            </div>`;
        } else {
            el.innerHTML = '<p class="text-muted">Tidak ada shift aktif. Silakan mulai shift baru untuk mengakses fitur kasir.</p>';
        }
    });
</script>
@endpush

