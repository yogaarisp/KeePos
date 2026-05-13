@extends('layouts.app')

@section('title', 'Pengaturan')

@push('styles')
<style>
    .settings-header {
        margin-bottom: 24px;
    }
    .settings-subtitle {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: var(--text-muted);
        margin-top: -4px;
        display: block;
    }

    /* Tabs Styling */
    .tabs-nav {
        display: flex;
        gap: 8px;
        margin-bottom: 24px;
        overflow-x: auto;
        padding-bottom: 4px;
        border-bottom: 1px solid var(--border-color);
    }
    .tab-btn {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        background: transparent;
        border: 1px solid transparent;
        border-radius: 8px 8px 0 0;
        color: var(--text-secondary);
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        white-space: nowrap;
        transition: all 0.2s;
    }
    .tab-btn:hover {
        color: var(--text-primary);
        background: var(--bg-card-hover);
    }
    .tab-btn.active {
        color: var(--accent);
        background: var(--bg-card);
        border-color: var(--border-color);
        border-bottom-color: var(--bg-card);
        margin-bottom: -1px;
    }
    .tab-btn i {
        width: 16px;
        height: 16px;
    }

    /* Form Design */
    .settings-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
    }
    .upload-box {
        border: 2px dashed var(--border-color);
        border-radius: 12px;
        padding: 32px;
        text-align: center;
        background: rgba(0,0,0,0.1);
        cursor: pointer;
        transition: all 0.2s;
        position: relative;
    }
    .upload-box:hover {
        border-color: var(--accent);
        background: rgba(249,115,22,0.05);
    }
    .upload-preview {
        max-width: 100%;
        max-height: 120px;
        margin-top: 12px;
        border-radius: 8px;
    }
    .remove-upload {
        position: absolute;
        top: 8px;
        right: 8px;
        background: var(--danger);
        color: #fff;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        border: none;
        cursor: pointer;
    }

    .form-section-title {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 16px;
        font-weight: 700;
        margin-bottom: 20px;
        color: var(--text-primary);
    }
    .form-section-icon {
        width: 32px;
        height: 32px;
        background: var(--accent-light);
        color: var(--accent);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .settings-footer {
        margin-top: 32px;
        padding-top: 24px;
        border-top: 1px solid var(--border-color);
    }

    .tab-content {
        display: none;
        animation: fadeIn 0.3s ease;
    }
    .tab-content.active {
        display: block;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(5px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Payment Method Cards */
    .payment-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }
    .payment-method-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .payment-method-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-lg);
    }
    .pm-header {
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 16px;
        position: relative;
    }
    .pm-icon {
        width: 44px;
        height: 44px;
        background: var(--surface-accent);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
    }
    .pm-info h4 { font-size: 16px; font-weight: 700; margin-bottom: 2px; }
    .pm-info p { font-size: 11px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; }
    
    .pm-status {
        position: absolute;
        top: 20px;
        right: 20px;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
    }
    .pm-status.active { background: var(--success-bg); color: var(--success); }
    .pm-status.inactive { background: var(--danger-bg); color: var(--danger); }

    .pm-body {
        padding: 0 20px 20px;
        flex: 1;
        min-height: 80px;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    .pm-detail-box {
        background: var(--bg-primary);
        border-radius: 12px;
        padding: 16px;
    }
    .pm-detail-label { font-size: 10px; font-weight: 800; color: var(--text-muted); text-transform: uppercase; margin-bottom: 4px; }
    .pm-detail-value { font-size: 14px; font-weight: 700; color: var(--text-primary); }
    .pm-no-details { font-size: 12px; color: var(--text-muted); font-style: italic; display: flex; align-items: center; gap: 8px; }

    .pm-footer {
        display: flex;
        border-top: 1px solid var(--border-color);
        background: rgba(0,0,0,0.02);
    }
    .pm-action-btn {
        flex: 1;
        padding: 12px;
        background: transparent;
        border: none;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        color: var(--text-muted);
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }
    .pm-action-btn:hover { background: var(--bg-card-hover); color: var(--text-primary); }
    .pm-action-btn.delete:hover { color: var(--danger); }
    .pm-action-divider { width: 1px; background: var(--border-color); }

    @media (max-width: 768px) {
        .settings-grid { grid-template-columns: 1fr; }
        .payment-grid { grid-template-columns: 1fr; }
    }

    /* Printer Specific Styles */
    .printer-status-panel {
        background: var(--bg-primary);
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 24px;
    }
    .printer-info-row {
        display: flex;
        align-items: center;
        gap: 16px;
        margin-bottom: 20px;
    }
    .printer-icon-box {
        width: 48px;
        height: 48px;
        background: var(--surface-accent);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--text-muted);
    }
    .status-text h4 { font-size: 15px; font-weight: 700; margin-bottom: 2px; }
    .status-text p { font-size: 12px; color: var(--text-muted); }

    .printer-actions {
        display: flex;
        gap: 12px;
    }
    .btn-scan {
        flex: 1;
        justify-content: center;
        background: var(--accent);
        color: white;
        font-weight: 700;
        padding: 12px;
    }

    .paper-size-selector {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
        margin-top: 12px;
    }
    .paper-option {
        border: 2px solid var(--border-color);
        border-radius: 12px;
        padding: 20px;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s;
        position: relative;
    }
    .paper-option.active {
        border-color: var(--accent);
        background: var(--accent-light);
    }
    .paper-option h3 { font-size: 20px; font-weight: 800; color: var(--text-muted); }
    .paper-option.active h3 { color: var(--accent); }
    .paper-option span { font-size: 10px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; }
    .check-mark {
        position: absolute;
        top: 10px; right: 10px;
        width: 20px; height: 20px;
        background: var(--accent);
        color: white;
        border-radius: 50%;
        display: none; align-items: center; justify-content: center;
    }
    .paper-option.active .check-mark { display: flex; }

    .toggle-card {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px;
        background: var(--bg-primary);
        border-radius: 12px;
        border: 1px solid var(--border-color);
    }
    .toggle-label h4 { font-size: 14px; font-weight: 700; margin-bottom: 4px; }
    .toggle-label p { font-size: 12px; color: var(--text-muted); }

    /* Custom Switch */
    .switch {
        position: relative;
        display: inline-block;
        width: 44px;
        height: 24px;
    }
    .switch input { opacity: 0; width: 0; height: 0; }
    .slider {
        position: absolute;
        cursor: pointer;
        top: 0; left: 0; right: 0; bottom: 0;
        background-color: #cbd5e1;
        transition: .4s;
        border-radius: 24px;
    }
    .slider:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: .4s;
        border-radius: 50%;
    }
    input:checked + .slider { background-color: var(--accent); }
    input:checked + .slider:before { transform: translateX(20px); }

    .printer-guide {
        margin-top: 24px;
        padding: 20px;
        background: var(--info-bg);
        border-radius: 12px;
        display: flex;
        gap: 16px;
    }
    .guide-content {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px 30px;
        width: 100%;
    }
    .guide-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 12px;
        color: var(--info);
        font-weight: 600;
    }
    .guide-item span { width: 4px; height: 4px; background: var(--info); border-radius: 50%; }
    .guide-title {
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        color: var(--info);
        margin-bottom: 8px;
        display: block;
    }
</style>
@endpush

@section('content')
<div class="settings-header">
</div>

<div class="tabs-nav">
    <button class="tab-btn active" data-tab="toko">
        <i data-lucide="store"></i> TOKO
    </button>
    <button class="tab-btn" data-tab="bayar">
        <i data-lucide="credit-card"></i> BAYAR
    </button>
    <button class="tab-btn" data-tab="printer">
        <i data-lucide="printer"></i> PRINTER
    </button>
    <button class="tab-btn" data-tab="email">
        <i data-lucide="mail"></i> EMAIL
    </button>
    <button class="tab-btn" data-tab="database">
        <i data-lucide="database"></i> DATABASE
    </button>
</div>

<form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data">
    @csrf
    
    <!-- TOKO TAB -->
    <div id="toko" class="tab-content active">
        <div class="card">
            <div class="card-body">
                <div class="form-section-title">
                    <div class="form-section-icon"><i data-lucide="info"></i></div>
                    Profil Toko
                </div>

                <div class="settings-grid">
                    <div>
                        <label class="form-label">LOGO TOKO</label>
                        <div class="upload-box" onclick="document.getElementById('logo_input').click()">
                            <input type="file" id="logo_input" name="shop_logo" style="display:none" onchange="previewImage(this, 'logo_preview')">
                            <i data-lucide="image" style="margin-bottom:8px; opacity:0.5;"></i>
                            <p style="font-size:12px; color:var(--text-muted)">Klik untuk upload logo</p>
                            @php $logo = \App\Models\Setting::getValue('shop_logo'); @endphp
                            <img id="logo_preview" class="upload-preview" src="{{ $logo ? asset('storage/'.$logo) : '#' }}" style="{{ $logo ? '' : 'display:none' }}">
                        </div>
                    </div>
                    <div>
                        <label class="form-label">FAVICON WEBSITE</label>
                        <div class="upload-box" onclick="document.getElementById('favicon_input').click()">
                            <input type="file" id="favicon_input" name="shop_favicon" style="display:none" onchange="previewImage(this, 'favicon_preview')">
                            <i data-lucide="image" style="margin-bottom:8px; opacity:0.5;"></i>
                            <p style="font-size:12px; color:var(--text-muted)">Klik untuk upload favicon</p>
                            @php $favicon = \App\Models\Setting::getValue('shop_favicon'); @endphp
                            <img id="favicon_preview" class="upload-preview" src="{{ $favicon ? asset('storage/'.$favicon) : '#' }}" style="{{ $favicon ? '' : 'display:none' }}">
                        </div>
                    </div>
                </div>

                <div class="form-group" style="margin-top:24px;">
                    <label class="form-label">NAMA TOKO</label>
                    <input type="text" name="settings[shop_name]" class="form-control" value="{{ \App\Models\Setting::getValue('shop_name') }}">
                </div>

                <div class="settings-grid" style="margin-top:16px;">
                    <div class="form-group">
                        <label class="form-label">ALAMAT TOKO</label>
                        <textarea name="settings[shop_address]" class="form-control" rows="3">{{ \App\Models\Setting::getValue('shop_address') }}</textarea>
                    </div>
                    <div>
                        <div class="form-group">
                            <label class="form-label">NO. WHATSAPP</label>
                            <div class="input-wrapper">
                                <input type="text" name="settings[shop_whatsapp]" class="form-control" value="{{ \App\Models\Setting::getValue('shop_whatsapp') }}">
                                <i data-lucide="phone"></i>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">INSTAGRAM</label>
                            <div class="input-wrapper">
                                <input type="text" name="settings[shop_instagram]" class="form-control" value="{{ \App\Models\Setting::getValue('shop_instagram') }}">
                                <i data-lucide="instagram"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- BAYAR TAB -->
    <div id="bayar" class="tab-content">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
            <div class="form-section-title" style="margin-bottom:0;">
                <div class="form-section-icon"><i data-lucide="credit-card"></i></div>
                Metode Pembayaran
            </div>
            <button type="button" class="btn btn-primary btn-sm" onclick="showAddPaymentModal()">
                <i data-lucide="plus"></i> Tambah Metode
            </button>
        </div>

        <div class="payment-grid">
            @forelse($paymentMethods as $pm)
            <div class="payment-method-card">
                <div class="pm-header">
                    <div class="pm-icon">
                        @php
                            $icons = [
                                'cash' => '🏦',
                                'qris' => '📱',
                                'transfer' => '🏛️',
                                'card' => '💳'
                            ];
                        @endphp
                        {{ $icons[strtolower($pm->type)] ?? '💰' }}
                    </div>
                    <div class="pm-info">
                        <h4>{{ $pm->name }}</h4>
                        <p>{{ $pm->type }}</p>
                    </div>
                    <div class="pm-status {{ $pm->is_active ? 'active' : 'inactive' }}">
                        {{ $pm->is_active ? 'AKTIF' : 'NONAKTIF' }}
                    </div>
                </div>
                <div class="pm-body">
                    @if($pm->account_number)
                    <div class="pm-detail-box">
                        <div class="form-group" style="margin-bottom:12px;">
                            <div class="pm-detail-label">NO. REK / ID</div>
                            <div class="pm-detail-value">{{ $pm->account_number }}</div>
                        </div>
                        <div>
                            <div class="pm-detail-label">ATAS NAMA</div>
                            <div class="pm-detail-value">{{ $pm->account_name }}</div>
                        </div>
                    </div>
                    @else
                    <div class="pm-no-details">
                        <i data-lucide="info" style="width:14px; height:14px;"></i>
                        Detail tidak tersedia
                    </div>
                    @endif
                </div>
                <div class="pm-footer">
                    <button type="button" class="pm-action-btn" onclick="editPaymentMethod({{ $pm->id }}, '{{ addslashes($pm->name) }}', '{{ $pm->account_number }}', '{{ $pm->account_name }}', {{ $pm->is_active ? 'true' : 'false' }})">
                        EDIT
                    </button>
                    <div class="pm-action-divider"></div>
                    <form action="{{ route('settings.payments.delete', $pm->id) }}" method="POST" style="display:flex; flex:0 0 auto;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="pm-action-btn delete" onclick="return confirm('Hapus metode ini?')">
                            <i data-lucide="trash-2" style="width:14px; height:14px;"></i>
                        </button>
                    </form>
                </div>
            </div>
            @empty
            <div class="card" style="grid-column: 1 / -1; padding: 40px; text-align: center;">
                <p style="color:var(--text-muted);">Belum ada metode pembayaran kustom.</p>
            </div>
            @endforelse
        </div>

        <div class="card" style="margin-top:24px;">
            <div class="card-body">
                <div class="form-section-title">
                    <div class="form-section-icon"><i data-lucide="settings"></i></div>
                    Konfigurasi Billing
                </div>
                <div class="form-group">
                    <label class="form-label">Pajak (%)</label>
                    <input type="number" name="settings[tax_percentage]" class="form-control" value="{{ \App\Models\Setting::getValue('tax_percentage', 0) }}">
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label">Catatan di Struk (Footer)</label>
                    <textarea name="settings[receipt_footer]" class="form-control">{{ \App\Models\Setting::getValue('receipt_footer') }}</textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- PRINTER TAB -->
    <div id="printer" class="tab-content">
        <div class="printer-status-panel">
            <div class="printer-info-row">
                <div class="printer-icon-box">
                    <i data-lucide="printer"></i>
                </div>
                <div class="status-text">
                    <h4>Status Printer</h4>
                    <p id="printerStatus">Printer Belum Terhubung</p>
                </div>
            </div>
            <div class="printer-actions">
                <button type="button" class="btn btn-scan">
                    Scan & Hubungkan Printer
                </button>
                <button type="button" class="btn btn-secondary" style="padding: 12px 24px;">
                    <i data-lucide="printer"></i> Test Print
                </button>
            </div>
        </div>

        <div class="settings-grid">
            <div class="card">
                <div class="card-body">
                    <label class="form-label" style="display:flex; align-items:center; gap:6px;">
                        Ukuran Kertas <i data-lucide="info" style="width:14px; height:14px; opacity:0.5;"></i>
                    </label>
                    <div class="paper-size-selector">
                        @php $size = \App\Models\Setting::getValue('printer_paper_size', '58'); @endphp
                        <input type="hidden" name="settings[printer_paper_size]" id="paperSizeInput" value="{{ $size }}">
                        
                        <div class="paper-option {{ $size == '58' ? 'active' : '' }}" onclick="selectPaperSize('58', this)">
                            <h3>58mm</h3>
                            <span>STANDARD</span>
                            <div class="check-mark"><i data-lucide="check" style="width:12px; height:12px;"></i></div>
                        </div>
                        
                        <div class="paper-option {{ $size == '80' ? 'active' : '' }}" onclick="selectPaperSize('80', this)">
                            <h3>80mm</h3>
                            <span>LARGE</span>
                            <div class="check-mark"><i data-lucide="check" style="width:12px; height:12px;"></i></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="toggle-card">
                <div class="toggle-label">
                    <h4>Auto Print Struk</h4>
                    <p>Cetak otomatis setelah transaksi berhasil diselesaikan</p>
                </div>
                <label class="switch">
                    @php $autoPrint = \App\Models\Setting::getValue('auto_print', '0'); @endphp
                    <input type="hidden" name="settings[auto_print]" value="0">
                    <input type="checkbox" name="settings[auto_print]" value="1" {{ $autoPrint == '1' ? 'checked' : '' }}>
                    <span class="slider"></span>
                </label>
            </div>
        </div>

        <div class="printer-guide">
            <div style="color:var(--info);"><i data-lucide="info" style="width:20px; height:20px;"></i></div>
            <div style="flex:1;">
                <span class="guide-title">Petunjuk Penggunaan Printer:</span>
                <div class="guide-content">
                    <div class="guide-item"><span></span> Pastikan Bluetooth perangkat menyala</div>
                    <div class="guide-item"><span></span> Printer harus mendukung perintah ESC/POS</div>
                    <div class="guide-item"><span></span> Gunakan Chrome atau Edge (Web Bluetooth)</div>
                    <div class="guide-item"><span></span> Pastikan printer sudah di-pairing dengan perangkat</div>
                </div>
            </div>
        </div>
    </div>

    <!-- EMAIL TAB -->
    <div id="email" class="tab-content">
        <div class="card">
            <div class="card-body">
                <div class="form-section-title">
                    <div class="form-section-icon"><i data-lucide="mail"></i></div>
                    SMTP Email
                </div>
                <div class="settings-grid">
                    <div class="form-group">
                        <label class="form-label">SMTP Host</label>
                        <input type="text" name="settings[mail_host]" class="form-control" value="{{ \App\Models\Setting::getValue('mail_host') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">SMTP Port</label>
                        <input type="text" name="settings[mail_port]" class="form-control" value="{{ \App\Models\Setting::getValue('mail_port') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Username</label>
                        <input type="text" name="settings[mail_username]" class="form-control" value="{{ \App\Models\Setting::getValue('mail_username') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <input type="password" name="settings[mail_password]" class="form-control" value="{{ \App\Models\Setting::getValue('mail_password') }}">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="settings-footer">
        <button type="submit" class="btn btn-primary" style="width:100%; padding:14px; font-weight:700; text-transform:uppercase; letter-spacing:1px;">
            Simpan Perubahan
        </button>
    </div>
</form>

<!-- DATABASE TAB (Outside main form because it has its own actions) -->
<div id="database" class="tab-content">
    <div class="card">
        <div class="card-body">
            <div class="form-section-title">
                <div class="form-section-icon"><i data-lucide="database"></i></div>
                Database Management
            </div>
            
            <div class="settings-grid">
                <div>
                    <h4 style="margin-bottom:8px; font-size:14px;">Backup Data</h4>
                    <p style="font-size:13px; color:var(--text-muted); margin-bottom:16px;">Ekspor seluruh database ke file .sql untuk cadangan.</p>
                    <a href="{{ route('settings.db.export') }}" class="btn btn-secondary">
                        <i data-lucide="download"></i> Ekspor Database
                    </a>
                </div>
                <div>
                    <h4 style="margin-bottom:8px; font-size:14px;">Restore Data</h4>
                    <p style="font-size:13px; color:var(--text-muted); margin-bottom:16px;">Impor data dari file .sql untuk memulihkan cadangan.</p>
                    <form method="POST" action="{{ route('settings.db.import') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group">
                            <input type="file" name="database_file" class="form-control" accept=".sql" required>
                        </div>
                        <button type="submit" class="btn btn-danger" onclick="return confirm('Peringatan: Ini akan menimpa seluruh database saat ini. Lanjutkan?')">
                            <i data-lucide="upload"></i> Impor Database
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

<!-- Modals -->
<div class="modal-overlay" id="addPaymentModal">
    <div class="modal" style="max-width:400px;">
        <form action="{{ route('settings.payments.store') }}" method="POST">
            @csrf
            <div class="modal-header">
                <h3>Tambah Metode Bayar</h3>
                <button type="button" class="header-btn" onclick="closeModal('addPaymentModal')"><i data-lucide="x"></i></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Nama (Contoh: BCA, Mandiri)</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Tipe</label>
                    <select name="type" class="form-control">
                        <option value="cash">Cash</option>
                        <option value="transfer">Transfer Bank</option>
                        <option value="qris">QRIS</option>
                        <option value="card">Debit/Credit Card</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">No. Rekening / ID (Opsional)</label>
                    <input type="text" name="account_number" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Atas Nama (Opsional)</label>
                    <input type="text" name="account_name" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addPaymentModal')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="editPaymentModal">
    <div class="modal" style="max-width:400px;">
        <form id="editPaymentForm" method="POST">
            @csrf
            @method('PATCH')
            <div class="modal-header">
                <h3>Edit Metode Bayar</h3>
                <button type="button" class="header-btn" onclick="closeModal('editPaymentModal')"><i data-lucide="x"></i></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Nama</label>
                    <input type="text" name="name" id="edit_pm_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">No. Rekening / ID</label>
                    <input type="text" name="account_number" id="edit_pm_number" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Atas Nama</label>
                    <input type="text" name="account_name" id="edit_pm_account" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="is_active" id="edit_pm_status" class="form-control">
                        <option value="1">Aktif</option>
                        <option value="0">Nonaktif</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editPaymentModal')">Batal</button>
                <button type="submit" class="btn btn-primary">Update</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    // Tab functionality
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const tabId = btn.dataset.tab;
            
            // Toggle active button
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            
            // Toggle active content
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            document.getElementById(tabId).classList.add('active');
        });
    });

    // Modal management
    function showAddPaymentModal() {
        document.getElementById('addPaymentModal').classList.add('active');
    }
    
    function closeModal(id) {
        document.getElementById(id).classList.remove('active');
    }

    function editPaymentMethod(id, name, number, account, isActive) {
        const form = document.getElementById('editPaymentForm');
        form.action = `/settings/payments/${id}`;
        
        document.getElementById('edit_pm_name').value = name;
        document.getElementById('edit_pm_number').value = number;
        document.getElementById('edit_pm_account').value = account;
        document.getElementById('edit_pm_status').value = isActive ? '1' : '0';
        
        document.getElementById('editPaymentModal').classList.add('active');
    }

    // Image preview
    function previewImage(input, previewId) {
        const preview = document.getElementById(previewId);
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Printer Paper Selection
    function selectPaperSize(size, element) {
        document.getElementById('paperSizeInput').value = size;
        document.querySelectorAll('.paper-option').forEach(opt => opt.classList.remove('active'));
        element.classList.add('active');
    }
</script>
@endpush
