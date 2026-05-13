@extends('layouts.app')
@section('title', 'POS Kasir')

@push('styles')
<style>
    /* Immersive POS Mode - Edge to Edge */
    body { overflow: hidden; background: var(--bg-primary); margin: 0; padding: 0; }
    .sidebar, .header { display: none !important; }
    .main-content { margin-left: 0 !important; padding: 0 !important; height: 100vh; width: 100vw; overflow: hidden; display: flex; flex-direction: column; }
    .content { padding: 0 !important; flex: 1; display: flex; flex-direction: column; overflow: hidden; }

    /* Truly Flush POS Header */
    .pos-top-bar {
        height: 60px; background: var(--bg-secondary); border-bottom: 1px solid var(--border-color);
        display: flex; align-items: center; justify-content: space-between; padding: 0 24px; flex-shrink: 0;
    }
    .pos-brand { display: flex; align-items: center; gap: 12px; }
    .pos-brand .logo-circle {
        width: 34px; height: 34px; background: linear-gradient(135deg, var(--accent), #fb923c); border-radius: 10px;
        display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 800; font-size: 18px;
        box-shadow: 0 3px 10px rgba(249, 115, 22, 0.2);
    }
    .pos-brand h2 { font-size: 18px; font-weight: 800; color: var(--text-primary); margin: 0; }
    .pos-top-right { display: flex; align-items: center; gap: 16px; }
    .pos-clock { 
        font-size: 14px; font-weight: 700; color: var(--text-secondary); background: var(--bg-primary); 
        padding: 8px 16px; border-radius: 12px; font-family: 'Courier New', monospace;
    }

    .pos-container { display: grid; grid-template-columns: 1fr 400px; flex: 1; overflow: hidden; }
    .pos-products { display: flex; flex-direction: column; height: 100%; background: var(--bg-primary); padding: 24px; overflow: hidden; }

    /* Category & Search Row */
    .pos-category-bar {
        display: flex;
        align-items: center;
        gap: 16px;
        margin-bottom: 24px;
        position: relative;
    }
    .cat-tabs { 
        flex: 1;
        display: flex; 
        gap: 10px; 
        overflow-x: auto; 
        padding-bottom: 4px;
        scrollbar-width: none;
    }
    .cat-tabs::-webkit-scrollbar { display: none; }
    .cat-tab {
        padding: 10px 22px; border-radius: 100px; font-size: 13px; font-weight: 700;
        background: var(--bg-secondary); border: 2px solid transparent; color: var(--text-secondary); cursor: pointer; white-space: nowrap;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .cat-tab.active { background: var(--accent); color: #fff; border-color: var(--accent); box-shadow: 0 4px 12px rgba(249, 115, 22, 0.2); }

    /* Interactive Search */
    .search-container { display: flex; align-items: center; position: relative; }
    .search-toggle-btn {
        width: 42px; height: 42px; border-radius: 12px;
        background: var(--bg-secondary); border: 1px solid var(--border-color);
        display: flex; align-items: center; justify-content: center;
        color: var(--text-secondary); cursor: pointer; transition: all 0.3s;
        z-index: 10;
    }
    .search-toggle-btn:hover { border-color: var(--accent); color: var(--accent); }
    .search-toggle-btn.active { background: var(--accent); color: white; border-color: var(--accent); box-shadow: 0 4px 12px rgba(249, 115, 22, 0.2); }
    
    .pos-search-wrapper {
        width: 0; opacity: 0; overflow: hidden;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        position: absolute; right: 0; visibility: hidden;
    }
    .pos-search-wrapper.active {
        width: 280px; opacity: 1; right: 50px; visibility: visible;
    }
    .pos-search-input {
        width: 100%; height: 42px; background: var(--bg-secondary);
        border: 2px solid var(--accent); border-radius: 100px;
        padding: 0 20px; font-size: 14px; font-weight: 600;
        color: var(--text-primary); outline: none;
        box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    }

    /* Product UI */
    .product-list-area { flex: 1; overflow-y: auto; padding-right: 8px; scrollbar-width: thin; scrollbar-color: var(--border-color) transparent; }
    .product-list-area::-webkit-scrollbar { width: 6px; }
    .product-list-area::-webkit-scrollbar-thumb { background: var(--border-color); border-radius: 10px; }
    .product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 20px; }
    .product-card {
        background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 20px; padding: 14px; cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); text-align: left; display: flex; flex-direction: column; gap: 10px;
    }
    .product-card:hover { border-color: var(--accent); box-shadow: 0 15px 30px rgba(0,0,0,0.1); background: var(--bg-card-hover); }
    .product-card.unavailable { opacity: 0.4; pointer-events: none; }
    .p-img { width: 100%; aspect-ratio: 1/1; border-radius: 16px; background: var(--bg-primary); display: flex; align-items: center; justify-content: center; overflow: hidden; }
    .p-img img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.6s; }
    .product-card:hover .p-img img { transform: scale(1.1); }
    .p-name { font-size: 14px; font-weight: 700; color: var(--text-primary); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.4; height: 40px; }
    .p-price { font-size: 16px; color: var(--accent); font-weight: 800; }

    /* Cart UI Redesign */
    .pos-cart { background: #fff; border-left: 1px solid var(--border-color); display: flex; flex-direction: column; height: 100%; position: relative; border-radius: 24px 0 0 0; }
    .cart-header { padding: 24px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid rgba(0,0,0,0.03); }
    .cart-header-title { display: flex; align-items: center; gap: 16px; }
    .cart-icon-circle {
        width: 40px; height: 40px; background: #fff7ed; border-radius: 12px;
        display: flex; align-items: center; justify-content: center; color: #f97316;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
    }
    .cart-header h3 { font-size: 17px; font-weight: 800; margin: 0; color: #1e293b; }
    .btn-reset { 
        width: 32px; height: 32px; border-radius: 10px; background: #fff7ed; color: #f97316; 
        border: 1px solid rgba(249, 115, 22, 0.1); cursor: pointer; display: flex; 
        align-items: center; justify-content: center;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .btn-reset:hover { background: #ffedd5; color: #ea580c; transform: rotate(180deg); }
    .btn-reset i { width: 18px; height: 20px; }



    .cart-items { flex: 1; overflow-y: auto; padding: 14px 24px 24px; scrollbar-width: none; }
    .cart-items::-webkit-scrollbar { display: none; }
    
    .cart-item { 
        display: flex; align-items: center; gap: 14px; padding: 14px; border-radius: 18px; margin-bottom: 8px; 
        background: #fff; border: 1px solid #f1f5f9; transition: transform 0.3s ease;
        position: relative; overflow: hidden; touch-action: pan-y;
    }
    
    /* Swipe Delete Style */
    .swipe-delete-action {
        position: absolute; right: 0; top: 0; bottom: 0; width: 60px;
        background: #ef4444; color: white; display: flex; align-items: center; justify-content: center;
        transform: translateX(100%); transition: transform 0.3s ease; cursor: pointer;
    }
    .cart-item.swiped { transform: translateX(-60px); }
    .cart-item.swiped .swipe-delete-action { transform: translateX(0); }

    .cart-item-info { flex: 1; min-width: 0; }
    .cart-item-name { font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 4px; display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .cart-item-price { font-size: 12px; font-weight: 700; color: #f97316; }
    
    .cart-qty { display: flex; align-items: center; background: #f8fafc; border-radius: 12px; padding: 3px; border: 1px solid #f1f5f9; margin-right: 8px; }
    .cart-qty button { width: 28px; height: 28px; border-radius: 9px; cursor: pointer; border: none; background: transparent; color: #64748b; font-weight: 800; display: flex; align-items: center; justify-content: center; transition: all 0.2s; }
    .cart-qty button:hover { background: var(--accent); color: white; border-radius: 9px; }
    .cart-qty span { font-size: 14px; font-weight: 800; min-width: 24px; text-align: center; color: #334155; }

    .btn-delete-item {
        width: 32px; height: 32px; border-radius: 10px; background: #fef2f2; color: #ef4444;
        border: none; display: flex; align-items: center; justify-content: center; cursor: pointer;
        transition: all 0.2s;
    }
    .btn-delete-item:hover { background: #ef4444; color: #fff; }

    /* Cart footer with summary box */
    .cart-footer { 
        padding: 20px 24px 24px; border-top: 1px solid #f1f5f9; background: #fff; 
        margin-top: auto; z-index: 5;
    }
    .cart-summary { background: #f8fafc; padding: 16px 20px; border-radius: 18px; border: 1px solid #f1f5f9; position: relative; }
    .summary-label-total { font-size: 10px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1.2px; margin-bottom: 2px; }
    .summary-item-count { 
        display: inline-block; padding: 4px 10px; border-radius: 8px; background: #ecfdf5; color: #059669; 
        font-size: 11px; font-weight: 800; margin-top: 6px; border: 1px solid #d1fae5;
    }
    .summary-total-price { position: absolute; right: 20px; top: 50%; transform: translateY(-50%); font-size: 24px; font-weight: 900; color: #f97316; }

    .btn-confirm-order {
        width: 100%; background: #fdba74; color: #fff; border: none; border-radius: 16px; padding: 16px; 
        font-size: 15px; font-weight: 800; margin-top: 16px; cursor: pointer; display: flex; 
        align-items: center; justify-content: center; gap: 10px; transition: all 0.2s;
    }
    .btn-confirm-order:hover { background: #fb923c; transform: translateY(-2px); box-shadow: 0 8px 20px rgba(251,146,60,0.3); }
    .btn-confirm-order i { width: 18px; }

    .empty-cart-state {
        display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; text-align: center; color: #94a3b8;
    }
    .empty-cart-icon {
        width: 90px; height: 90px; background: #f8fafc; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 24px;
    }
    .empty-cart-icon i { width: 40px; height: 40px; color: #e2e8f0; }
    .empty-cart-text { font-size: 16px; font-weight: 800; color: #64748b; margin-bottom: 6px; }
    .empty-cart-subtext { font-size: 12px; font-weight: 600; color: #94a3b8; }

    /* Premium Preview Modal (Checkout) */
    .preview-modal { max-width: 900px !important; border-radius: 28px !important; border: none !important; }
    .pm-header { padding: 24px 32px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #f1f5f9; }
    .pm-title { font-size: 20px; font-weight: 800; color: #1e293b; margin: 0; }
    .pm-type-toggle { background: #f1f5f9; padding: 4px; border-radius: 12px; display: flex; gap: 4px; }
    .pm-type-btn { padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 700; border: none; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 6px; }
    .pm-type-btn.active { background: #f97316; color: #fff; box-shadow: 0 4px 10px rgba(249, 115, 22, 0.2); }
    .pm-type-btn.inactive { background: transparent; color: #64748b; }
    
    .pm-body { padding: 32px; display: grid; grid-template-columns: 1fr 1fr; gap: 48px; }
    .pm-section-title { font-size: 11px; font-weight: 800; color: #f97316; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
    .pm-section-title::before { content: ''; width: 6px; height: 6px; background: #f97316; border-radius: 50%; }

    .input-group-premium { margin-bottom: 24px; position: relative; }
    .input-label-premium { display: block; font-size: 10px; font-weight: 800; color: #94a3b8; text-transform: uppercase; margin-bottom: 8px; letter-spacing: 0.5px; }
    .input-premium { width: 100%; padding: 14px 18px; border-radius: 12px; border: 1px solid #e2e8f0; background: #fff; font-size: 14px; font-weight: 600; color: #334155; transition: all 0.2s; }
    .input-premium:focus { border-color: #f97316; box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.05); outline: none; }
    .input-premium::placeholder { color: #cbd5e1; }

    .order-summary-box { background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 16px; padding: 20px; }
    .needs-pay-badge { font-size: 9px; font-weight: 800; color: #f97316; text-transform: uppercase; margin-bottom: 12px; opacity: 0.8; }
    .summary-item-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
    .summary-item-row.total { margin-top: 24px; padding-top: 16px; border-top: 1px solid #e2e8f0; }
    
    .quick-cash-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 20px; }
    .quick-btn { padding: 10px; border-radius: 10px; border: 1px solid #ffedd5; background: #fff7ed; color: #9a3412; font-size: 13px; font-weight: 700; cursor: pointer; transition: all 0.2s; }
    .quick-btn:hover { background: #ffedd5; transform: translateY(-1px); }

    .change-box { background: #f0fdf4; border: 1px solid #dcfce7; border-radius: 14px; padding: 18px; display: flex; justify-content: space-between; align-items: center; margin-top: 20px; }
    .change-label { font-size: 15px; font-weight: 700; color: #166534; }
    .change-val { font-size: 22px; font-weight: 900; color: #15803d; }

    .btn-checkout-final { width: 100%; padding: 18px; border-radius: 18px; background: #f97316; color: #fff; border: none; font-size: 16px; font-weight: 800; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 12px; transition: all 0.2s; margin-top: 32px; }
    .btn-checkout-final:hover { background: #ea580c; transform: translateY(-2px); box-shadow: 0 10px 20px rgba(249, 115, 22, 0.3); }

    /* Table Selection Styles */
    .table-grid-modal { display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 12px; margin-top: 20px; }
    .table-item-card {
        padding: 16px; border-radius: 16px; border: 2px solid #f1f5f9; background: #fff;
        text-align: center; cursor: pointer; transition: all 0.2s;
        display: flex; flex-direction: column; align-items: center; gap: 8px;
    }
    .table-item-card:hover { border-color: #f97316; transform: translateY(-2px); }
    .table-item-card.active { border-color: #f97316; background: #fff7ed; box-shadow: 0 4px 10px rgba(249, 115, 22, 0.1); }
    .table-item-card i { color: #94a3b8; }
    .table-item-card.active i { color: #f97316; }
    .table-item-name { font-size: 13px; font-weight: 800; color: #334155; }
    
    .clickable-box {
        cursor: pointer; display: flex; align-items: center; justify-content: space-between;
    }

    @media (max-width: 1024px) {
        .pm-body { grid-template-columns: 1fr; gap: 24px; }
    }
</style>
@endpush

@section('content')
@php 
    $shopName = \App\Models\Setting::getValue('shop_name', config('app.name', 'Kee POS'));
    $logo = \App\Models\Setting::getValue('shop_logo');
@endphp
<div class="pos-top-bar">
    <div class="pos-brand">
        <a href="{{ route('dashboard') }}" style="color:inherit;text-decoration:none;display:flex;align-items:center;gap:12px;">
            <div class="logo-circle">{{ substr($shopName ?? config('app.name'), 0, 1) }}</div>
            <h2>{{ $shopName ?? config('app.name') }} POS</h2>
        </a>
    </div>
    <div class="pos-top-right">
        <div class="pos-clock" id="posClock">00:00:00</div>
        <button class="header-btn theme-toggle" onclick="toggleTheme()" style="border-radius:12px; width:40px; height:40px; border:1px solid var(--border-color); background:transparent; color:var(--text-secondary); cursor:pointer; display:flex; align-items:center; justify-content:center;">
            <i data-lucide="sun" class="sun-icon"></i><i data-lucide="moon" class="moon-icon"></i>
        </button>
        <div style="height:24px; width:1px; background:var(--border-color);"></div>
        <div style="display:flex; align-items:center; gap:8px;">
            <div style="text-align:right;">
                <div style="font-size:12px; font-weight:700; color:var(--text-primary);">{{ auth()->user()->full_name }}</div>
                <div style="font-size:10px; color:var(--text-muted);">{{ ucfirst(auth()->user()->role) }}</div>
            </div>
            <div style="width:36px; height:36px; border-radius:50%; background:var(--accent); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:14px; box-shadow:0 3px 10px rgba(249, 115, 22, 0.2);">
                {{ substr(auth()->user()->full_name, 0, 1) }}
            </div>
        </div>
    </div>
</div>

<div class="pos-container">
    <div class="pos-products">
        <div class="pos-header">
            @if($activeShift)
            <div class="shift-mini active">
                <div style="display:flex; align-items:center; gap:8px;"><span class="shift-dot"></span><span>Shift: <strong>{{ auth()->user()->full_name }}</strong></span></div>
                <span>Mulai: {{ $activeShift->opened_at->format('H:i') }}</span>
            </div>
            @endif

            <div class="pos-category-bar">
                <div class="cat-tabs">
                    <button class="cat-tab active" onclick="filterCategory('all', this)">Semua Menu</button>
                    @foreach($categories as $cat)
                    <button class="cat-tab" onclick="filterCategory({{ $cat->id }}, this)">{{ $cat->name }}</button>
                    @endforeach
                </div>
                <div class="search-container">
                    <div class="pos-search-wrapper" id="searchWrapper">
                        <input type="text" class="pos-search-input" id="productSearch" placeholder="Cari menu..." oninput="searchProducts(this.value)">
                    </div>
                    <button class="search-toggle-btn" onclick="toggleSearch()" id="searchToggle">
                        <i data-lucide="search"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="product-list-area">
            <div class="product-grid" id="productGrid">
                @foreach($products as $product)
                <div class="product-card {{ !$product->is_available ? 'unavailable' : '' }}"
                     data-category="{{ $product->category_id }}"
                     data-name="{{ strtolower($product->name) }}"
                     onclick="addToCart({{ $product->id }}, '{{ addslashes($product->name) }}', {{ $product->price }})">
                    <div class="p-img">
                        @if($product->image)
                            <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}">
                        @else
                            <i data-lucide="utensils" style="width:32px;height:32px;color:var(--text-muted);opacity:0.3;"></i>
                        @endif
                    </div>
                    <div class="p-info">
                        <div class="p-name">{{ $product->name }}</div>
                        <div class="p-price">{{ $product->formatted_price }}</div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="pos-cart">
        <div class="cart-card" style="height:100%; display:flex; flex-direction:column;">
            <div class="cart-header">
                <div class="cart-header-title">
                    <div class="cart-icon-circle">
                        <i data-lucide="shopping-cart"></i>
                    </div>
                    <h3>Keranjang</h3>
                </div>
                <button class="btn-reset" onclick="clearCart()" id="clearCartBtn" style="display:none;" title="Bersihkan Keranjang">
                    <i data-lucide="refresh-cw"></i>
                </button>
            </div>





            <div class="cart-items">
                <div class="empty-cart-state" id="cartEmpty">
                    <div class="empty-cart-icon">
                        <i data-lucide="shopping-cart"></i>
                    </div>
                    <div class="empty-cart-text">Belum ada item.</div>
                    <div class="empty-cart-subtext">Pilih menu di kiri</div>
                </div>
                <div id="cartProductList"></div>
            </div>

            <div class="cart-footer">
                <div class="cart-summary">
                    <div class="summary-label-total">TOTAL TAGIHAN</div>
                    <div class="summary-item-count" id="itemCountBadge">0 Item</div>
                    <div class="summary-total-price" id="cartTotal">Rp 0</div>
                </div>
                <button class="btn-confirm-order" onclick="showCheckoutModal()" id="checkoutBtn" disabled>
                    Konfirmasi Order <i data-lucide="arrow-right-circle"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- All Modals Restored -->
<!-- Premium Order Preview Modal -->
<div class="modal-overlay" id="checkoutModal">
    <div class="modal preview-modal">
        <div class="pm-header">
            <h3 class="pm-title">Preview Pesanan</h3>
            <div style="display: flex; align-items: center; gap: 16px;">
                <div class="pm-type-toggle">
                    @php $orderType = session('order_type', 'dine_in'); @endphp
                    <button type="button" class="pm-type-btn {{ $orderType == 'dine_in' ? 'active' : 'inactive' }}" onclick="setOrderType('dine_in', this)">
                        <i data-lucide="utensils" style="width:14px;"></i> Dine In
                    </button>
                    <button type="button" class="pm-type-btn {{ $orderType == 'takeaway' ? 'active' : 'inactive' }}" onclick="setOrderType('takeaway', this)">
                        <i data-lucide="shopping-bag" style="width:14px;"></i> Takeaway
                    </button>
                </div>
                <button class="btn-reset" onclick="closeCheckoutModal()" style="border:none;background:transparent;"><i data-lucide="x"></i></button>
            </div>
        </div>
        
        <div class="pm-body">
            <!-- Left Column: Order Info -->
            <div class="pm-left">
                <div class="pm-section-title">Informasi Pesanan</div>
                
                <div class="input-group-premium">
                    <label class="input-label-premium">Nama Pelanggan</label>
                    <input type="text" id="customerName" class="input-premium" placeholder="Contoh: Budi...">
                </div>

                <div class="input-group-premium" id="tableSelectionGroup">
                    <label class="input-label-premium">Meja / No. Alarm Pager</label>
                    <div class="input-premium clickable-box" onclick="openTableModal()">
                        <span id="selectedTableText" style="color:#cbd5e1;">Pilih Meja / No. Alarm...</span>
                        <input type="hidden" id="tableId" value="">
                        <i data-lucide="chevron-right" style="width:16px; color:#cbd5e1;"></i>
                    </div>
                </div>

                <div class="pm-section-title">Detail Pesanan</div>
                <div class="order-summary-box">
                    <div class="needs-pay-badge">PERLU DIBAYAR</div>
                    <div id="modalOrderItems">
                        <!-- Filled by JS -->
                    </div>
                    <div class="summary-item-row" style="margin-top:16px; font-size:12px; color:#94a3b8; font-weight:700;">
                        <span>Subtotal <span id="modalItemCount">(0 Item)</span></span>
                        <span id="modalSubtotal">Rp 0</span>
                    </div>
                    <div class="summary-item-row total">
                        <span style="font-size:16px; font-weight:800; color:#1e293b;">Total Pembayaran</span>
                        <span style="font-size:20px; font-weight:900; color:#334155;" id="modalTotal">Rp 0</span>
                    </div>
                </div>
            </div>

            <!-- Right Column: Payment -->
            <div class="pm-right">
                <div class="pm-section-title">Pembayaran</div>
                
                <div class="input-group-premium">
                    <label class="input-label-premium">Metode Pembayaran</label>
                    <select id="paymentMethod" class="input-premium" onchange="updatePaymentUI()">
                        @foreach($paymentMethods as $pm)
                        <option value="{{ strtolower($pm->name) }}" {{ strtolower($pm->name) == 'tunai' || strtolower($pm->name) == 'cash' ? 'selected' : '' }}>{{ $pm->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div id="cashInterface">
                    <div class="input-label-premium">Pilihan Nominal:</div>
                    <div class="quick-cash-grid" id="quickNominals">
                        <!-- Filled by JS -->
                    </div>

                    <div class="input-group-premium">
                        <label class="input-label-premium">Jumlah Bayar</label>
                        <input type="number" id="paymentAmount" class="input-premium" style="font-size:20px; font-weight:800; text-align:right;" oninput="calculateChange()" placeholder="0">
                    </div>

                    <div class="change-box" id="changeDisplay">
                        <div class="change-label">Kembalian</div>
                        <div class="change-val" id="changeAmount">Rp 0</div>
                    </div>
                </div>
            </div>
        </div>

        <div style="padding:0 32px 32px;">
            <button class="btn-checkout-final" onclick="processCheckout()" id="processBtn">
                <i data-lucide="check-circle" style="width:20px;"></i> Konfirmasi & Bayar Pesanan
            </button>
        </div>
    </div>
</div>

<!-- Table Selection Modal -->
<div class="modal-overlay" id="tableModal" style="z-index: 1000;">
    <div class="modal" style="max-width: 500px; border-radius: 28px;">
        <div class="pm-header">
            <h3 class="pm-title">Pilih Meja</h3>
            <button class="btn-reset" onclick="closeTableModal()" style="border:none;background:transparent;"><i data-lucide="x"></i></button>
        </div>
        <div class="modal-body" style="padding: 24px;">
            <div class="input-group-premium">
                <input type="text" id="tableSearch" class="input-premium" placeholder="Cari nomor meja..." oninput="filterTables(this.value)">
            </div>
            <div class="table-grid-modal" id="tablesContainer">
                @foreach($tables as $table)
                <div class="table-item-card" onclick="selectTable({{ $table->id }}, '{{ $table->table_number }}')" data-name="{{ strtolower($table->table_number) }}">
                    <i data-lucide="table-2"></i>
                    <div class="table-item-name">Meja {{ $table->table_number }}</div>
                </div>
                @endforeach
                <!-- Special option for No Table if needed -->
                <div class="table-item-card" onclick="selectTable('', 'Tanpa Meja')">
                    <i data-lucide="slash"></i>
                    <div class="table-item-name">Tanpa Meja</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal-overlay" id="openShiftModal">
    <div class="modal shift-modal">
        <div class="modal-header"><h2 class="modal-title">Buka Shift Kasir</h2></div>
        <div class="modal-body">
            <div style="background:rgba(249,115,22,0.1); color:#f97316; padding:16px; border-radius:16px; margin-bottom:24px; font-size:13px; font-weight:700;">
                <h4 style="margin:0 0 4px 0;">PENTING</h4>
                <p style="margin:0;">Mulai shift untuk mencatat transaksi hari ini secara otomatis.</p>
            </div>
            <form method="POST" action="{{ route('shifts.open') }}">
                @csrf
                <div style="margin-bottom:24px;">
                    <label style="display:block; font-size:13px; font-weight:700; margin-bottom:8px; color:#64748b;">Modal Tunai Awal</label>
                    <div style="position:relative;">
                        <span style="position:absolute; left:16px; top:50%; transform:translateY(-50%); font-weight:800; color:#334155;">Rp</span>
                        <input type="number" name="initial_cash" style="width:100%; padding:14px 14px 14px 44px; border-radius:14px; border:1px solid #e2e8f0; font-size:18px; font-weight:800;" value="0" required onclick="this.select()">
                    </div>
                </div>
                <button type="submit" style="width:100%; padding:16px; border-radius:16px; background:#f97316; color:#fff; border:none; font-weight:800; cursor:pointer;">KONFIRMASI BUKA SHIFT</button>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function updateClock() {
        const now = new Date();
        const time = now.toLocaleTimeString('id-ID', { hour12: false });
        if (document.getElementById('posClock')) document.getElementById('posClock').innerText = time;
    }
    setInterval(updateClock, 1000); updateClock();

    let cart = JSON.parse(localStorage.getItem('pos_cart')) || [];
    let selectedOrderType = 'dine_in';

    // Initial render
    window.addEventListener('DOMContentLoaded', () => {
        renderCart();
    });

    function showOpenShiftModal() { document.getElementById('openShiftModal').classList.add('active'); lucide.createIcons(); }
    function closeOpenShiftModal() { document.getElementById('openShiftModal').classList.remove('active'); }
    function formatRp(n) { return 'Rp ' + n.toLocaleString('id-ID'); }

    function toggleSearch() {
        const wrapper = document.getElementById('searchWrapper');
        const btn = document.getElementById('searchToggle');
        const input = document.getElementById('productSearch');
        
        wrapper.classList.toggle('active');
        btn.classList.toggle('active');
        
        if (wrapper.classList.contains('active')) {
            setTimeout(() => input.focus(), 100);
        } else {
            input.value = '';
            searchProducts('');
        }
    }

    function filterCategory(catId, btn) {
        document.querySelectorAll('.cat-tab').forEach(t => t.classList.remove('active'));
        btn.classList.add('active');
        document.querySelectorAll('.product-card').forEach(card => card.style.display = (catId === 'all' || card.dataset.category == catId) ? '' : 'none');
    }

    function searchProducts(q) {
        q = q.toLowerCase();
        document.querySelectorAll('.product-card').forEach(card => card.style.display = card.dataset.name.includes(q) ? '' : 'none');
    }

    function addToCart(id, name, price) {
        const item = cart.find(i => i.product_id === id);
        if (item) item.quantity++; else cart.push({ product_id: id, name, price, quantity: 1 });
        renderCart();
    }

    function updateQty(index, delta) {
        cart[index].quantity += delta;
        if (cart[index].quantity <= 0) cart.splice(index, 1);
        renderCart();
    }

    function removeItem(index) {
        cart.splice(index, 1);
        renderCart();
    }

    function saveCart() {
        localStorage.setItem('pos_cart', JSON.stringify(cart));
    }

    function clearCart() { 
        if (confirm('Kosongkan keranjang?')) { 
            cart = []; 
            localStorage.removeItem('pos_cart');
            renderCart(); 
        } 
    }

    // Swipe to delete logic
    let touchStartX = 0;
    let touchEndX = 0;

    function handleTouchStart(e, index) {
        touchStartX = e.changedTouches[0].screenX;
    }

    function handleTouchMove(e, index) {
        touchEndX = e.changedTouches[0].screenX;
        const diff = touchStartX - touchEndX;
        const el = document.getElementById(`cart-item-${index}`);
        if (diff > 30) {
            el.classList.add('swiped');
        } else if (diff < -30) {
            el.classList.remove('swiped');
        }
    }

    function handleTouchEnd(e, index) {
        // Handled in move for real-time feel
    }

    function renderCart() {
        const container = document.getElementById('cartProductList');
        const empty = document.getElementById('cartEmpty');
        const clearBtn = document.getElementById('clearCartBtn');
        const checkoutBtn = document.getElementById('checkoutBtn');

        if (cart.length === 0) {
            container.innerHTML = ''; 
            empty.style.display = 'flex'; 
            clearBtn.style.display = 'none'; 
            checkoutBtn.disabled = true;
        } else {
            empty.style.display = 'none'; 
            clearBtn.style.display = 'block'; 
            checkoutBtn.disabled = false;
            container.innerHTML = cart.map((item, i) => `
                <div class="cart-item" 
                     ontouchstart="handleTouchStart(event, ${i})" 
                     ontouchmove="handleTouchMove(event, ${i})" 
                     ontouchend="handleTouchEnd(event, ${i})"
                     id="cart-item-${i}">
                    <div class="cart-item-info">
                        <div class="cart-item-name">${item.name}</div>
                        <div class="cart-item-price">${formatRp(item.price)}</div>
                    </div>
                    <div class="cart-qty">
                        <button onclick="updateQty(${i}, -1)">-</button>
                        <span>${item.quantity}</span>
                        <button onclick="updateQty(${i}, 1)">+</button>
                    </div>
                    <button class="btn-delete-item" onclick="removeItem(${i})" title="Hapus">
                        <i data-lucide="trash-2" style="width:14px;"></i>
                    </button>
                    <div class="swipe-delete-action" onclick="removeItem(${i})">
                        <i data-lucide="trash-2"></i>
                    </div>
                </div>
            `).join('');
            lucide.createIcons();
        }
        const total = cart.reduce((s, i) => s + i.price * i.quantity, 0);
        const count = cart.reduce((s, i) => s + i.quantity, 0);
        
        document.getElementById('itemCountBadge').textContent = `${count} Item`;
        document.getElementById('cartTotal').textContent = formatRp(total);
        saveCart();
    }

    function showCheckoutModal() {
        const total = cart.reduce((s, i) => s + i.price * i.quantity, 0);
        const count = cart.reduce((s, i) => s + i.quantity, 0);
        
        document.getElementById('modalTotal').textContent = formatRp(total);
        document.getElementById('modalSubtotal').textContent = formatRp(total);
        document.getElementById('modalItemCount').textContent = `(${count} Item)`;
        
        // Render detailed items in modal
        document.getElementById('modalOrderItems').innerHTML = cart.map(item => `
            <div class="summary-item-row" style="margin-bottom:8px;">
                <span style="font-size:13px; font-weight:700; color:#334155;">${item.name} x ${item.quantity}</span>
                <span style="font-size:13px; font-weight:700; color:#f97316;">${formatRp(item.price * item.quantity)}</span>
            </div>
        `).join('');

        // Pre-fill quick cash buttons
        const nominals = [total];
        if (total < 20000) nominals.push(20000);
        if (total < 50000) nominals.push(50000);
        if (total < 100000) nominals.push(100000);
        
        // Custom logic for quick buttons based on total
        document.getElementById('quickNominals').innerHTML = [...new Set(nominals)].sort((a,b) => a-b).map(n => `
            <button class="quick-btn" onclick="setPaymentAmount(${n})">${formatRp(n)}</button>
        `).join('');

        document.getElementById('checkoutModal').classList.add('active');
        updatePaymentUI(); // Ensure correct UI shows up initially
        lucide.createIcons();
    }

    function setPaymentAmount(amount) {
        document.getElementById('paymentAmount').value = amount;
        calculateChange();
    }

    function setOrderType(type, btn) {
        selectedOrderType = type;
        document.querySelectorAll('.pm-type-btn').forEach(b => {
            b.classList.remove('active');
            b.classList.add('inactive');
        });
        btn.classList.add('active');
        btn.classList.remove('inactive');
        
        // Show/Hide table field based on type
        const tableGroup = document.getElementById('tableSelectionGroup');
        if (type === 'takeaway') {
            tableGroup.style.display = 'none';
            // Clear selection if hidden
            selectTable('', 'Pilih Meja / No. Alarm...');
            document.getElementById('selectedTableText').style.color = '#cbd5e1';
        } else {
            tableGroup.style.display = 'block';
        }
    }

    function openTableModal() {
        document.getElementById('tableModal').classList.add('active');
        lucide.createIcons();
    }

    function closeTableModal() {
        document.getElementById('tableModal').classList.remove('active');
    }

    function selectTable(id, name) {
        document.getElementById('tableId').value = id;
        const text = document.getElementById('selectedTableText');
        text.innerText = name;
        text.style.color = '#334155';
        
        // Visual feedback on cards
        document.querySelectorAll('.table-item-card').forEach(c => c.classList.remove('active'));
        // Find by name matching or something similar, but simpler to just close
        closeTableModal();
    }

    function filterTables(q) {
        q = q.toLowerCase();
        document.querySelectorAll('.table-item-card').forEach(card => {
            const name = card.dataset.name || '';
            card.style.display = name.includes(q) ? 'flex' : 'none';
        });
    }

    function closeCheckoutModal() { document.getElementById('checkoutModal').classList.remove('active'); }

    function updatePaymentUI() {
        const method = document.getElementById('paymentMethod').value;
        document.getElementById('cashInterface').style.display = method === 'tunai' || method === 'cash' ? 'block' : 'none';
        calculateChange();
    }

    function calculateChange() {
        const total = cart.reduce((s, i) => s + i.price * i.quantity, 0);
        const amount = parseInt(document.getElementById('paymentAmount').value) || 0;
        const change = amount - total;
        
        document.getElementById('changeAmount').textContent = formatRp(Math.max(0, change));
    }

    function processCheckout() {
        const total = cart.reduce((s, i) => s + i.price * i.quantity, 0);
        const method = document.getElementById('paymentMethod').value;
        const cash_amount = parseInt(document.getElementById('paymentAmount').value) || 0;
        const customer_name = document.getElementById('customerName').value;
        const table_id = document.getElementById('tableId').value;
        
        if ((method === 'tunai' || method === 'cash') && cash_amount < total) {
            alert('Uang tunai tidak cukup!'); return;
        }

        const btn = document.getElementById('processBtn');
        btn.disabled = true; btn.innerText = 'Memproses...';

        fetch("{{ route('pos.checkout') }}", {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ 
                items: cart, 
                payment_method: method, 
                payment_amount: cash_amount,
                customer_name: customer_name,
                table_id: table_id,
                order_type: selectedOrderType
            })
        }).then(res => res.json()).then(data => {
            if (data.success) {
                localStorage.removeItem('pos_cart'); // Clear after success
                alert('Transaksi Berhasil!'); window.location.reload();
            } else {
                alert('Gagal: ' + data.message); btn.disabled = false; btn.innerHTML = '<i data-lucide="check-circle" style="width:20px;"></i> Konfirmasi & Bayar Pesanan'; lucide.createIcons();
            }
        });
    }
</script>
@endpush
