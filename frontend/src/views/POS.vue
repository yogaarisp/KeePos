<template>
  <div class="pos-root immersive" :class="theme.isLight ? 'light' : 'dark'">
    <!-- Top Bar -->
    <header class="pos-top-bar">
      <div class="top-bar-left">
        <router-link to="/" class="pos-logo">
          <div class="logo-box">
            <img v-if="pos.shopSettings?.shop_logo" :src="baseUrl + '/storage/' + pos.shopSettings.shop_logo" class="dynamic-logo">
            <Utensils v-else :size="20" />
            <div class="logo-glow"></div>
          </div>
          <div class="logo-text">
            <h2>{{ shopName }}</h2>
            <span>Smart POS System</span>
          </div>
        </router-link>
      </div>

      <div class="top-bar-center">
        <div class="pos-clock-glass">
          <Clock :size="16" />
          <span>{{ currentTime }}</span>
        </div>
      </div>

      <div class="top-bar-right">
        <div v-if="pos.activeShift" class="shift-pill">
          <div class="pulse-dot"></div>
          <span>Shift Aktif</span>
        </div>

        <button class="top-action-btn" @click="theme.toggle()" :title="theme.isLight ? 'Dark Mode' : 'Light Mode'">
          <Sun v-if="theme.isLight" :size="18" />
          <Moon v-else :size="18" />
        </button>

        <div class="pos-user-card">
          <div class="u-avatar-wrapper">
            <div class="u-avatar">{{ (auth.user?.full_name || 'A').charAt(0).toUpperCase() }}</div>
            <div class="online-dot"></div>
          </div>
          <div class="u-info">
            <span class="u-name">{{ auth.user?.full_name || auth.user?.username }}</span>
            <span class="u-status"><div class="s-dot"></div> Online</span>
          </div>
       </div>
      </div>
    </header>

    <!-- Main POS Body -->
    <div class="pos-main">
      <!-- Left: Products Area -->
      <section class="pos-products-section">
        <!-- Filter & Search Bar -->
        <div class="pos-filter-bar">
          <div class="category-scroll-container custom-scrollbar" :style="{ opacity: searchExpanded ? 0.3 : 1 }">
            <button 
              class="cat-chip" 
              :class="{ active: pos.activeCategoryId === null }"
              @click="pos.activeCategoryId = null"
            >
              Semua Menu
            </button>
            <button 
              v-for="cat in pos.categories" 
              :key="cat.id"
              class="cat-chip" 
              :class="{ active: pos.activeCategoryId === cat.id }"
              @click="pos.activeCategoryId = cat.id"
            >
              {{ cat.name }}
            </button>
          </div>

          <div class="pos-search-wrapper" :class="{ expanded: searchExpanded }">
            <button class="btn-search-toggle" @click="toggleSearch" :title="pos.searchQuery ? 'Reset Pencarian' : 'Cari Menu'">
              <Search v-if="!searchExpanded && !pos.searchQuery" :size="20" />
              <X v-else :size="20" />
            </button>
            <div class="search-input-slide">
              <input 
                ref="searchInput"
                type="text" 
                v-model="pos.searchQuery" 
                placeholder="Cari masakan..."
                @blur="onSearchBlur"
                @keyup.enter="searchExpanded = false"
              >
            </div>
          </div>
        </div>

        <!-- Product Grid -->
        <div class="product-grid-wrap custom-scrollbar">
          <div v-if="pos.loading && !pos.filteredProducts.length" class="grid-loading">
            <div v-for="i in 12" :key="i" class="skeleton-card"></div>
          </div>

          <div v-else-if="pos.filteredProducts.length" class="premium-product-grid">
            <div 
              v-for="(product, idx) in pos.filteredProducts" 
              :key="product.id"
              class="p-card-premium"
              :class="{ 'out-of-stock': !product.is_available, 'added': addedProductId === product.id }"
              :style="{ animationDelay: (idx * 0.04) + 's' }"
              @click="product.is_available && handleAddToCart(product)"
            >
              <div class="p-card-img">
                <img v-if="product.image" :src="baseUrl + '/storage/' + product.image" :alt="product.name" loading="lazy">
                <div v-else class="p-img-placeholder">
                  <ChefHat :size="32" />
                </div>
                <div class="p-card-overlay">
                  <div class="add-indicator">
                    <Plus :size="24" />
                  </div>
                </div>
                <div v-if="!product.is_available" class="sold-out-badge">Habis</div>
              </div>

              <div class="p-card-info">
                <h4 class="p-title">{{ product.name }}</h4>
                <div class="p-bottom">
                  <span class="p-price">{{ formatCurrency(product.price) }}</span>
                  <div class="p-type-badge">{{ product.category?.name || 'Menu' }}</div>
                </div>
              </div>
            </div>
          </div>

          <div v-else class="grid-empty">
            <div class="empty-illu">
              <Search :size="48" />
            </div>
            <h3>Menu Tidak Ditemukan</h3>
            <p>Kata kunci "{{ pos.searchQuery }}" tidak cocok dengan menu manapun.</p>
          </div>
        </div>
      </section>

      <!-- Right: Cart Sidebar -->
      <aside class="pos-cart-section">
        <div class="cart-premium-card">
          <div class="cart-p-header">
            <div class="cart-p-title">
              <div class="cart-icon-box">
                <ShoppingCart :size="18" />
              </div>
              <div class="cart-title-text">
                <h3>Keranjang</h3>
                <span v-if="pos.cart.length">{{ pos.cart.reduce((s, i) => s + i.quantity, 0) }} item dipilih</span>
              </div>
            </div>
            <button v-if="pos.cart.length" class="btn-clear-cart" @click="pos.clearCart" title="Kosongkan">
              <RotateCcw :size="15" />
            </button>
          </div>

          <div class="cart-p-body custom-scrollbar">


            <TransitionGroup name="cart-item-anim" tag="div" class="cart-item-list">
              <div v-for="item in pos.cart" :key="item.product_id" class="cart-item-premium">
                <div class="ci-img">
                  <img v-if="item.image" :src="baseUrl + '/storage/' + item.image" :alt="item.name" class="ci-actual-img">
                  <div v-else class="ci-icon"><Utensils :size="14" /></div>
                </div>
                <div class="ci-details">
                  <span class="ci-name">{{ item.name }}</span>
                  <span class="ci-unit-price">{{ formatCurrency(item.price) }} x {{ item.quantity }}</span>
                </div>
                <div class="ci-controls">
                  <div class="qty-stepper">
                    <button class="step-btn" @click.stop="pos.updateQuantity(item.product_id, -1)"><Minus :size="12" /></button>
                    <span class="qty-val">{{ item.quantity }}</span>
                    <button class="step-btn" @click.stop="pos.updateQuantity(item.product_id, 1)"><Plus :size="12" /></button>
                  </div>
                  <button class="btn-ci-delete" @click.stop="pos.removeFromCart(item.product_id)">
                    <Trash2 :size="14" />
                  </button>
                </div>
              </div>
            </TransitionGroup>
          </div>

          <div class="cart-p-footer">
            <div class="summary-box">
              <div class="sum-row">
                <span>Subtotal</span>
                <span>{{ formatCurrency(pos.total) }}</span>
              </div>
              <div class="sum-row total">
                <span>Total Bayar</span>
                <span>{{ formatCurrency(pos.total) }}</span>
              </div>
            </div>
            <button class="btn-checkout-premium" @click="showCheckout = true" :disabled="!pos.cart.length">
              <div class="btn-c-content">
                <span class="btn-c-total">Preview Pesanan</span>
              </div>
              <div class="btn-c-icon">
                <ArrowRight :size="20" />
              </div>
            </button>
          </div>
        </div>
      </aside>
    </div>

    <!-- Mobile Drawer Cart -->
    <Teleport to="body">
      <Transition name="drawer">
        <div v-if="showMobileCart && pos.cart.length" class="mobile-cart-drawer">
          <div class="drawer-header">
            <div class="drawer-title">
              <ShoppingCart :size="18" />
              <h3>Daftar Pesanan</h3>
              <span class="item-count">{{ pos.cart.reduce((s, i) => s + i.quantity, 0) }} item</span>
            </div>
            <button class="btn-close-drawer" @click="showMobileCart = false">
              <Minus :size="24" />
            </button>
          </div>
          
          <div class="drawer-body custom-scrollbar">
            <div v-for="item in pos.cart" :key="'mob-'+item.product_id" class="drawer-item">
              <div class="di-left">
                <div class="di-icon-box">
                  <img v-if="item.image" :src="baseUrl + '/storage/' + item.image" :alt="item.name" class="di-actual-img">
                  <Utensils v-else :size="14" />
                </div>
                <div class="di-details">
                  <span class="di-name">{{ item.name }}</span>
                  <span class="di-price-info">{{ formatCurrency(item.price) }} x {{ item.quantity }}</span>
                </div>
              </div>
              <div class="di-right">
                <div class="di-qty-pill">
                  <button class="qty-btn" @click.stop="pos.updateQuantity(item.product_id, -1)"><Minus :size="14" /></button>
                  <span class="qty-num">{{ item.quantity }}</span>
                  <button class="qty-btn" @click.stop="pos.updateQuantity(item.product_id, 1)"><Plus :size="14" /></button>
                </div>
                <button class="btn-di-delete" @click.stop="pos.removeFromCart(item.product_id)">
                  <Trash2 :size="16" />
                </button>
              </div>
            </div>
          </div>
        </div>
      </Transition>
      <div v-if="showMobileCart" class="drawer-backdrop" @click="showMobileCart = false"></div>
    </Teleport>

    <!-- Mobile Navigation Bottom Bar -->
    <div v-if="pos.cart.length" class="mobile-pay-bar mobile-safe-bottom" @click="showMobileCart = !showMobileCart">
        <div class="m-pay-left">
          <div class="m-pay-badge-wrap">
            <div class="m-pay-badge">{{ pos.cart.reduce((s, i) => s + i.quantity, 0) }}</div>
            <ChevronUp :size="14" class="m-chevron" :class="{ rotated: showMobileCart }" />
          </div>
          <div class="m-pay-total">
            <span class="m-total-label">Total Pembayaran</span>
            <span class="m-total-val">{{ formatCurrency(pos.total) }}</span>
          </div>
        </div>
       <div class="m-pay-btn" @click.stop="showCheckout = true; showMobileCart = false">
         Bayar <ArrowRight :size="18" />
       </div>
    </div>

    <!-- Premium Checkout Modal -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="showCheckout" class="modal-backdrop" @click.self="showCheckout = false">
          <div class="modal-panel-checkout">
            <div class="checkout-header">
              <div class="ch-left">
                <button class="btn-back-header" @click="showCheckout = false">
                  <ChevronLeft :size="22" />
                </button>
                <div class="ch-icon-wrap">
                  <CheckCircle2 :size="24" />
                </div>
                <div class="ch-title-area">
                  <h3 class="ch-title">Preview Pesanan</h3>
                  <p class="ch-desc">Selesaikan pesanan dan proses pembayaran.</p>
                </div>
              </div>

              <div class="header-toggle">
                <button 
                  class="toggle-opt" 
                  :class="{ active: pos.orderType === 'dine_in' }"
                  @click="pos.setOrderType('dine_in')"
                >
                  <Utensils :size="14" />
                  <span>Dine In</span>
                </button>
                <button 
                  class="toggle-opt" 
                  :class="{ active: pos.orderType === 'takeaway' }"
                  @click="pos.setOrderType('takeaway')"
                >
                  <ShoppingBag :size="14" />
                  <span>Takeaway</span>
                </button>
              </div>
            </div>

            <div class="checkout-body">
              <div class="ch-summary-area">
                <div class="summary-glass-card">
                  <h4 class="sum-title">Ringkasan Pesanan</h4>
                  <div class="sum-items-list custom-scrollbar">
                    <div v-for="item in pos.cart" :key="'sum-'+item.product_id" class="sum-item">
                      <div class="sum-item-left">
                        <div class="sum-item-img">
                          <img v-if="item.image" :src="baseUrl + '/storage/' + item.image" :alt="item.name">
                          <Utensils v-else :size="12" />
                        </div>
                        <span class="sum-item-q">{{ item.quantity }}x</span>
                        <span class="sum-item-n">{{ item.name }}</span>
                      </div>
                      <span class="sum-item-p">{{ formatCurrency(item.price * item.quantity) }}</span>
                    </div>
                  </div>
                  <div class="sum-divider"></div>
                  <div class="sum-footer">
                    <div class="sum-final-row">
                      <span>Total Tagihan</span>
                      <span class="sum-total-val">{{ formatCurrency(pos.total) }}</span>
                    </div>
                    <button 
                      class="btn-pay-final" 
                      @click="handleCheckout" 
                      :disabled="isProcessing || (checkoutForm.payment_method === 'cash' && checkoutForm.payment_amount < pos.total)"
                    >
                      <RefreshCw v-if="isProcessing" :size="20" class="spinner" />
                      <span v-else>KONFIRMASI & BAYAR</span>
                    </button>
                  </div>
                </div>
              </div>

              <div class="ch-form-area">
                <div class="ch-input-grid">
                  <div class="ch-input-group">
                    <label>Nama Pelanggan (Opsional)</label>
                    <div class="input-wrap">
                      <User :size="16" class="i-icon" />
                      <input type="text" v-model="checkoutForm.customer_name" placeholder="E.g. Kee Pratama">
                    </div>
                  </div>

                  <div v-if="pos.orderType === 'dine_in'" class="ch-input-group">
                    <label>Meja / No. Pager</label>
                    <button class="table-select-trigger" @click="showTableModal = true">
                      <Table2 :size="16" />
                      <span v-if="pos.selectedTableId">Meja {{ pos.tables.find(t => t.id === pos.selectedTableId)?.table_number }}</span>
                      <span v-else class="placeholder">Pilih Meja / Pager...</span>
                      <ChevronRight :size="16" class="m-left-auto" />
                    </button>
                  </div>
                </div>

                <div class="payment-method-area">
                  <label class="section-label">Pilih Metode Pembayaran</label>
                  <div class="payment-grid">
                    <div 
                      v-for="cat in paymentCategories" 
                      :key="cat.id" 
                      class="pay-card" 
                      :class="{ active: selectedCategory === cat.id }"
                      @click="handleSelectPayment(cat.id)"
                    >
                      <div class="pay-check-mark"><Check :size="12" /></div>
                      <component :is="cat.icon" :size="24" class="pay-icon" />
                      <span class="pay-name">{{ cat.label }}</span>
                    </div>
                  </div>

                  <!-- Sub-method Selection (e.g. BCA, Mandiri) -->
                  <Transition name="fade-slide">
                    <div v-if="subMethods.length > 1" class="sub-method-grid mt-4">
                      <button 
                        v-for="m in subMethods" 
                        :key="m.id"
                        class="sub-pay-btn"
                        :class="{ active: checkoutForm.payment_method === m.id }"
                        @click="selectSubMethod(m.id)"
                      >
                        <div class="sub-pay-content">
                          <span class="sub-pay-name">{{ m.name }}</span>
                          <span v-if="m.account_number" class="sub-pay-meta">{{ m.account_number }}</span>
                          <span v-if="m.account_name" class="sub-pay-meta-name">a/n {{ m.account_name }}</span>
                        </div>
                        <div v-if="checkoutForm.payment_method === m.id" class="sub-check">
                          <Check :size="14" />
                        </div>
                      </button>
                    </div>
                  </Transition>
                </div>

                <Transition name="fade-slide">
                  <div v-if="selectedCategory === 'cash'" class="cash-advanced-area">

                    <div class="quick-cash-row">
                      <button v-for="nom in quickNominals" :key="nom" class="q-cash-btn" @click="checkoutForm.payment_amount = nom; showNumpad = false">
                        {{ formatCurrency(nom) }}
                      </button>
                    </div>
                    <div class="ch-input-group mt-4">
                      <label>Jumlah Uang Tunai</label>
                      <div class="input-wrap large" @click="showNumpad = true">
                        <Banknote :size="20" class="i-icon" />
                        <input 
                        type="number" 
                        v-model.number="checkoutForm.payment_amount" 
                        placeholder="0"
                        readonly
                        inputmode="none"
                      >
                      </div>
                    </div>

                    <!-- Custom Numpad Grid -->
                    <Transition name="fade-slide">
                      <div v-if="showNumpad" class="numpad-grid mt-4">
                        <button v-for="n in [1,2,3,4,5,6,7,8,9]" :key="n" class="num-btn" @click="onNumpadClick(n)">{{ n }}</button>
                        <button class="num-btn special" @click="onNumpadClick('CLR')">C</button>
                        <button class="num-btn" @click="onNumpadClick(0)">0</button>
                        <button class="num-btn special" @click="onNumpadClick('DEL')">
                          <Delete :size="18" />
                        </button>
                        <button class="num-btn triple-zero" @click="onNumpadClick('000')">.000</button>
                      </div>
                    </Transition>
                    
                    <div class="change-display mt-4" :class="{ ok: checkoutForm.payment_amount >= pos.total }">
                      <div class="change-info">
                        <span class="c-label">Uang Kembalian</span>
                        <h4 class="c-val">{{ formatCurrency(Math.max(0, checkoutForm.payment_amount - pos.total)) }}</h4>
                      </div>
                      <div class="change-status-icon">
                        <CheckCircle2 v-if="checkoutForm.payment_amount >= pos.total" :size="24" />
                        <AlertCircle v-else :size="24" />
                      </div>
                    </div>
                  </div>
                </Transition>
              </div>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Premium Table Selection Modal -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="showTableModal" class="modal-backdrop" @click.self="showTableModal = false">
          <div class="modal-panel-small">
            <div class="modal-top">
              <div class="modal-icon-wrap info">
                <Table2 :size="20" />
              </div>
              <div class="modal-title-area">
                <h3 class="modal-title">Pilih Meja / Pager</h3>
                <p class="modal-desc">Pilih nomor meja atau alat pemanggil pelanggan.</p>
              </div>
              <button class="modal-close" @click="showTableModal = false"><X :size="20" /></button>
            </div>
            
            <div class="modal-content">
              <div class="table-search-box">
                <Search :size="16" class="ts-icon" />
                <input v-model="tableSearch" placeholder="Cari nomor meja...">
              </div>
              
              <div class="table-selection-grid custom-scrollbar">
                <div 
                  class="t-item-choice" 
                  :class="{ active: pos.selectedTableId === null }"
                  @click="pos.setSelectedTable(null); showTableModal = false"
                >
                  <XCircle :size="24" />
                  <span>Tanpa Meja</span>
                </div>
                <div 
                  v-for="table in filteredTables" 
                  :key="table.id" 
                  class="t-item-choice" 
                  :class="{ active: pos.selectedTableId === table.id }" 
                  @click="pos.setSelectedTable(table.id); showTableModal = false"
                >
                  <div class="t-number">{{ table.table_number }}</div>
                  <span>Meja {{ table.table_number }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Digital Receipt Modal - Premium Redesign -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="showReceiptModal" class="modal-backdrop" @click.self="showReceiptModal = false">
          <div class="modal-panel-receipt">
            <div class="receipt-card-wrapper">
              <div class="receipt-paper" id="digital-receipt-canvas">
                <!-- Top Zig Zag -->
                <div class="paper-edge top"></div>

                <div class="receipt-header">
                  <div class="receipt-logo-wrap">
                    <img v-if="pos.shopSettings?.shop_logo" :src="baseUrl + '/storage/' + pos.shopSettings.shop_logo" class="receipt-logo">
                    <div v-else class="receipt-icon-fallback"><Utensils :size="40" /></div>
                  </div>
                  <h2 class="receipt-shop-name">{{ pos.shopSettings?.shop_name || 'Kee POS' }}</h2>
                  <p class="receipt-shop-info">{{ pos.shopSettings?.shop_address || 'Jl. Contoh No. 123, Jakarta' }}</p>
                  <p class="receipt-shop-info" v-if="pos.shopSettings?.shop_phone">Tel: {{ pos.shopSettings?.shop_phone }}</p>
                </div>

                <div class="receipt-divider-dotted"></div>

                <div class="receipt-meta font-mono">
                  <div class="meta-row">
                    <span>Invoice</span>
                    <strong>#{{ lastTransaction?.invoice_number }}</strong>
                  </div>
                  <div class="meta-row">
                    <span>Tanggal</span>
                    <span>{{ formatDate(lastTransaction?.created_at) }}</span>
                  </div>
                  <div class="meta-row">
                    <span>Kasir</span>
                    <span>{{ (auth.user?.full_name || auth.user?.username).toUpperCase() }}</span>
                  </div>
                  <div class="meta-row" v-if="lastTransaction?.customer_name">
                    <span>Pelanggan</span>
                    <span>{{ lastTransaction.customer_name.toUpperCase() }}</span>
                  </div>
                </div>

                <div class="receipt-divider-solid"></div>

                <div class="receipt-items font-mono">
                  <div v-for="item in lastTransaction?.items" :key="item.id" class="r-item">
                    <div class="r-item-main">
                      <span class="r-item-name">{{ item.product?.name || item.name }}</span>
                      <span class="r-item-total">{{ formatCurrency(item.price * item.quantity).replace('Rp\u00A0', '') }}</span>
                    </div>
                    <div class="r-item-sub">
                      <span>{{ item.quantity }} x {{ formatCurrency(item.price).replace('Rp\u00A0', '') }}</span>
                    </div>
                  </div>
                </div>

                <div class="receipt-divider-solid"></div>

                <div class="receipt-summary font-mono">
                  <div class="r-sum-row">
                    <span>Subtotal</span>
                    <span>{{ formatCurrency(lastTransaction?.subtotal).replace('Rp\u00A0', '') }}</span>
                  </div>
                  <div class="r-sum-row" v-if="lastTransaction?.discount > 0">
                    <span>Diskon</span>
                    <span>-{{ formatCurrency(lastTransaction.discount).replace('Rp\u00A0', '') }}</span>
                  </div>
                  <div class="r-divider-thin"></div>
                  <div class="r-sum-row grand-total">
                    <span>TOTAL</span>
                    <span>{{ formatCurrency(lastTransaction?.total_amount) }}</span>
                  </div>
                  <div class="receipt-divider-dotted" style="margin: 12px 0;"></div>
                  <div class="r-sum-row">
                    <span>Bayar ({{ lastTransaction?.payment_method.toUpperCase() }})</span>
                    <span>{{ formatCurrency(lastTransaction?.payment_amount).replace('Rp\u00A0', '') }}</span>
                  </div>
                  <div class="r-sum-row" v-if="lastTransaction?.payment_method === 'cash'">
                    <span>Kembali</span>
                    <span>{{ formatCurrency(lastTransaction.change_amount).replace('Rp\u00A0', '') }}</span>
                  </div>
                </div>

                <div class="receipt-footer">
                  <div class="qr-placeholder" v-if="lastTransaction?.invoice_number">
                    <!-- QR Placeholder or small message -->
                  </div>
                  <p class="footer-msg">Terima Kasih Atas Kunjungan Anda</p>
                  <p class="powered-by">Powered by Kee POS · smartpos.id</p>
                </div>

                <!-- Bottom Zig Zag -->
                <div class="paper-edge bottom"></div>
              </div>

              <div class="receipt-actions">
                <button class="btn-receipt-action print" @click="reprintReceipt">
                  <div class="btn-icon"><Printer :size="18" /></div>
                  <span>Thermal</span>
                </button>
                <button class="btn-receipt-action browser" @click="printer.printStandardBrowser()">
                  <div class="btn-icon"><Monitor :size="18" /></div>
                  <span>Browser</span>
                </button>
                <button class="btn-receipt-action share" @click="shareReceipt">
                  <div class="btn-icon"><Share2 :size="18" /></div>
                  <span>Bagikan</span>
                </button>
                <button class="btn-receipt-action success-full" @click="showReceiptModal = false">
                  <CheckCircle2 :size="20" />
                  <span>Selesai</span>
                </button>
              </div>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, onUnmounted, inject, computed, nextTick } from 'vue';
import { usePOSStore } from '../stores/pos';
import { useAuthStore } from '../stores/auth';
import { useRouter } from 'vue-router';
import { usePrinter } from '../composables/usePrinter';
import { useSettingStore } from '../stores/setting';
import { showConfirm, showSuccess, showError } from '../utils/swal';
import { baseUrl } from '../api';
import { 
  Clock, Sun, Moon, Search, Utensils, ShoppingCart, RotateCcw, Plus, Minus, X, Trash2, 
  ArrowRight, ChefHat, CheckCircle2, User, Table2, ChevronRight, ChevronLeft, ShoppingBag, 
  Banknote, Smartphone, Building2, Check, Hash, RefreshCw, AlertCircle, XCircle, Delete, LogOut, ChevronUp,
  Share2, Printer, Monitor
} from 'lucide-vue-next';

const pos = usePOSStore();
const auth = useAuthStore();
const router = useRouter();
const theme = inject('theme');
const printer = usePrinter();

const searchExpanded = ref(false);
const searchInput = ref(null);
const currentTime = ref('');
const showCheckout = ref(false);
const showMobileCart = ref(false);
const showTableModal = ref(false);
const showReceiptModal = ref(false);
const showNumpad = ref(false);
const isProcessing = ref(false);
const lastTransaction = ref(null);
const addedProductId = ref(null);
const tableSearch = ref('');

const toggleSearch = () => {
  if (pos.searchQuery) {
    pos.searchQuery = '';
    searchExpanded.value = false;
    return;
  }
  searchExpanded.value = !searchExpanded.value;
  if (searchExpanded.value) {
    nextTick(() => {
      searchInput.value?.focus();
    });
  } else {
    pos.searchQuery = '';
  }
};

const onSearchBlur = () => {
  if (!pos.searchQuery) {
    searchExpanded.value = false;
  }
};

const checkoutForm = reactive({
  customer_name: '',
  payment_method: 'cash',
  payment_amount: 0
});

const selectedCategory = ref('cash');

const paymentCategories = [
  { id: 'cash', label: 'Tunai / Cash', icon: Banknote },
  { id: 'qris', label: 'QRIS / Digital', icon: Smartphone },
  { id: 'transfer', label: 'Bank Transfer', icon: Building2 }
];

const subMethods = computed(() => {
  return pos.paymentMethods.filter(m => {
    if (selectedCategory.value === 'cash') return m.type === 'cash' && m.is_active;
    if (selectedCategory.value === 'qris') return m.type === 'e-wallet' && m.is_active;
    if (selectedCategory.value === 'transfer') return m.type === 'transfer' && m.is_active;
    return false;
  });
});


// Computed property for shop name with fallback
const shopName = computed(() => {
  return pos.shopSettings?.shop_name || 
         auth.user?.tenant?.name || 
         'Kee POS';
});

const filteredTables = computed(() => {
  if (!tableSearch.value) return pos.tables;
  const q = tableSearch.value.toLowerCase();
  return pos.tables.filter(t => String(t.table_number).toLowerCase().includes(q));
});

const quickNominals = computed(() => {
  const total = pos.total;
  if (!total) return [10000, 20000, 50000, 100000];
  const vals = [total];
  if (total < 20000) vals.push(20000);
  if (total < 50000) vals.push(50000);
  if (total < 100000) vals.push(100000);
  return [...new Set(vals)].sort((a,b) => a-b);
});

const handleAddToCart = (product) => {
  pos.addToCart(product);
  addedProductId.value = product.id;
  setTimeout(() => { addedProductId.value = null; }, 300);
};

const onNumpadClick = (val) => {
  let current = String(checkoutForm.payment_amount || '');
  if (val === 'DEL') {
    current = current.slice(0, -1);
  } else if (val === 'CLR') {
    current = '0';
  } else {
    if (current === '0') current = '';
    current += val;
  }
  checkoutForm.payment_amount = parseInt(current) || 0;
};

const handleSelectPayment = (catId) => {
  selectedCategory.value = catId;
  showNumpad.value = false;
  
  if (catId !== 'cash') {
    checkoutForm.payment_amount = pos.total;
  } else {
    checkoutForm.payment_amount = 0;
  }

  // Auto-pick if only one specific method exists in this category
  if (subMethods.value.length === 1) {
    checkoutForm.payment_method = subMethods.value[0].id;
  } else {
    checkoutForm.payment_method = null;
  }
};

const selectSubMethod = (methodId) => {
  checkoutForm.payment_method = methodId;
};


const updateClock = () => {
  currentTime.value = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
};

let timer;
onMounted(() => {
  pos.fetchContent();
  updateClock();
  timer = setInterval(updateClock, 1000);
  
  // Auto-connect printer on page load/refresh
  printer.autoConnect();
});

onUnmounted(() => clearInterval(timer));

const formatDate = (dateStr) => {
  if (!dateStr) return '-';
  const date = new Date(dateStr);
  return date.toLocaleDateString('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  }).replace('.', ':');
};

const reprintReceipt = async () => {
    if (!lastTransaction.value) return;
    const transaction = lastTransaction.value;
    const settingStore = useSettingStore();
    
    // Check if printer is connected
    if (!printer.connected.value) {
        await showError({
            title: 'Printer Tidak Terhubung',
            text: 'Hubungkan printer thermal Bluetooth terlebih dahulu di menu Pengaturan.',
            confirmText: 'OK'
        });
        return;
    }

    try {
        const receiptData = {
            shopName: shopName.value,
            shopLogo: pos.shopSettings?.shop_logo || null,
            shopAddress: pos.shopSettings?.shop_address || '',
            shopPhone: pos.shopSettings?.shop_phone || '',
            invoiceNo: transaction.invoice_number,
            date: new Date(transaction.created_at).toLocaleString('id-ID'),
            cashierName: auth.user?.full_name || auth.user?.username,
            customerName: transaction.customer_name || '-',
            tableName: transaction.table?.table_number ? `Meja ${transaction.table.table_number}` : (transaction.order_type === 'takeaway' ? 'TAKEAWAY' : '-'),
            items: transaction.items.map(i => ({
                name: i.product?.name || i.name || 'Item',
                qty: i.quantity,
                price: formatCurrency(i.price).replace('Rp\u00A0', ''),
                subtotal: formatCurrency(i.price * i.quantity).replace('Rp\u00A0', '')
            })),
            subtotal: formatCurrency(transaction.subtotal).replace('Rp\u00A0', ''),
            discount: formatCurrency(transaction.discount || 0).replace('Rp\u00A0', ''),
            tax: formatCurrency(transaction.tax || 0).replace('Rp\u00A0', ''),
            total: formatCurrency(transaction.total_amount).replace('Rp\u00A0', ''),
            paid: formatCurrency(transaction.payment_amount).replace('Rp\u00A0', ''),
            change: formatCurrency(transaction.change_amount || 0).replace('Rp\u00A0', ''),
            paymentMethod: transaction.payment_method.toUpperCase(),
            footer: 'Terima Kasih Atas Kunjungannya'
        };
        
        await printer.printReceipt(receiptData, parseInt(settingStore.paperSize) || 58, false); // Don't open drawer on reprint
        
        await showSuccess({
            title: 'Struk Tercetak',
            text: 'Struk berhasil dikirim ke printer.',
            timer: 1500
        });
    } catch (printErr) {
        console.error('Print failed:', printErr);
        await showError({
            title: 'Gagal Mencetak',
            text: 'Terjadi kesalahan saat mencetak struk.',
            confirmText: 'OK'
        });
    }
};

const shareReceipt = () => {
  if (navigator.share) {
    navigator.share({
      title: `Invoice ${lastTransaction.value?.invoice_number}`,
      text: `Terima kasih telah berbelanja di ${pos.shopSettings?.shop_name}. Total: ${formatCurrency(lastTransaction.value?.total_amount)}`,
    }).catch(console.error);
  } else {
    alert('Browser Anda tidak mendukung fitur berbagi.');
  }
};

const handleLogout = async () => {
  const result = await showConfirm({
    title: 'Konfirmasi Logout',
    text: 'Apakah Anda yakin ingin keluar?',
    icon: 'question',
    confirmText: 'Ya, Keluar',
    cancelText: 'Batal'
  });
  
  if (result.isConfirmed) {
    const success = await auth.logout();
    if (success) {
      router.push('/login');
    }
  }
};

const formatCurrency = (val) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);

const handleCheckout = async () => {
  if (!pos.activeShift) {
    const result = await showConfirm({
      title: 'Shift Belum Dibuka',
      text: 'Anda harus membuka shift terlebih dahulu untuk dapat melakukan transaksi. Buka shift sekarang?',
      icon: 'warning',
      confirmText: 'Buka Shift',
      cancelText: 'Nanti'
    });
    
    if (result.isConfirmed) {
      router.push('/app/shifts');
    }
    return;
  }
  
  if (checkoutForm.payment_method === 'cash' && checkoutForm.payment_amount < pos.total) {
    await showError({
      title: 'Uang Tidak Cukup',
      text: 'Jumlah uang tunai yang diberikan kurang dari total tagihan.'
    });
    return;
  }

  isProcessing.value = true;
  
  try {
    const transaction = await pos.checkout({
      customer_name: checkoutForm.customer_name,
      payment_method: checkoutForm.payment_method,
      payment_amount: checkoutForm.payment_amount
    });
    
    isProcessing.value = false;
    
    if (transaction) {
      lastTransaction.value = transaction;
      showCheckout.value = false;
      showReceiptModal.value = true;
      
      // Auto Print Logic
      const settingStore = useSettingStore();
      if (settingStore.autoPrint && printer.connected.value) {
        try {
          const receiptData = {
            shopName: shopName.value,
            shopLogo: pos.shopSettings?.shop_logo || null,
            shopAddress: pos.shopSettings?.shop_address || '',
            shopPhone: pos.shopSettings?.shop_phone || '',
            invoiceNo: transaction.invoice_number,
            date: new Date(transaction.created_at).toLocaleString('id-ID'),
            cashierName: auth.user?.full_name || auth.user?.username,
            customerName: transaction.customer_name || '-',
            tableName: transaction.table?.table_number ? `Meja ${transaction.table.table_number}` : (transaction.order_type === 'takeaway' ? 'TAKEAWAY' : '-'),
            items: transaction.items.map(i => ({
              name: i.product?.name || i.name || 'Item',
              qty: i.quantity,
              price: formatCurrency(i.price).replace('Rp\u00A0', ''),
              subtotal: formatCurrency(i.price * i.quantity).replace('Rp\u00A0', '')
            })),
            subtotal: formatCurrency(transaction.subtotal).replace('Rp\u00A0', ''),
            discount: formatCurrency(transaction.discount || 0).replace('Rp\u00A0', ''),
            tax: formatCurrency(transaction.tax || 0).replace('Rp\u00A0', ''),
            total: formatCurrency(transaction.total_amount).replace('Rp\u00A0', ''),
            paid: formatCurrency(transaction.payment_amount).replace('Rp\u00A0', ''),
            change: formatCurrency(transaction.change_amount || 0).replace('Rp\u00A0', ''),
            paymentMethod: transaction.payment_method.toUpperCase(),
            footer: 'Terima Kasih Atas Kunjungannya'
          };
          
          await printer.printReceipt(receiptData, parseInt(settingStore.paperSize) || 58, settingStore.openCashDrawer);
          
          await showSuccess({
            title: 'Struk Sudah Keluar!',
            text: settingStore.openCashDrawer ? 'Laci kasir terbuka. Silakan ambil struk dari printer.' : 'Silakan ambil struk dari printer.',
            timer: 1500
          });
        } catch (printErr) {
          console.error('Auto print failed:', printErr);
          await showError({
            title: 'Waduh, Printer Bermasalah',
            text: 'Pesanan sudah masuk kok, tapi printernya error. Coba cek koneksi printer ya.',
            confirmText: 'OK'
          });
        }
      } else if (settingStore.autoPrint && !printer.connected.value) {
        await showError({
          title: 'Printer Belum Nyambung',
          text: 'Pesanan sudah masuk, tapi printer belum terhubung. Hubungkan dulu di menu Pengaturan ya.',
          confirmText: 'OK'
        });
      }

      // Reset form
      checkoutForm.customer_name = '';
      checkoutForm.payment_amount = 0;
      checkoutForm.payment_method = 'cash';
    }
  } catch (error) {
    isProcessing.value = false;
    await showError({
      title: 'Transaksi Gagal',
      text: error.message || 'Terjadi kesalahan saat memproses transaksi.',
      confirmText: 'OK'
    });
  }
};
</script>

<style scoped>
.pos-root {
  height: 100vh;
  display: flex;
  flex-direction: column;
  background: var(--bg-primary);
  overflow: hidden;
  max-width: 100vw;
  position: relative;
}

/* ── Top Bar ── */
.pos-top-bar {
  height: 72px;
  background: var(--bg-secondary);
  border-bottom: 1px solid var(--border-color);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 24px;
  z-index: 50;
  position: sticky;
  top: 0;
}
.pos-logo {
  display: flex; align-items: center; gap: 14px; text-decoration: none;
}
.logo-box {
  width: 40px; height: 40px; border-radius: 12px;
  background: transparent;
  display: flex; align-items: center; justify-content: center;
  color: #fff; position: relative; overflow: hidden; border: none;
}
.dynamic-logo { width: 100%; height: 100%; object-fit: cover; }
.logo-glow { position: absolute; inset: -4px; background: var(--accent); filter: blur(10px); opacity: 0.2; }
.logo-text h2 { font-size: 16px; font-weight: 600; color: var(--text-primary); margin: 0; line-height: 1; }
.logo-text span { font-size: 10px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; }

.top-bar-center { flex: 1; display: flex; justify-content: center; }
.pos-clock-glass {
  background: var(--bg-primary); border: 1px solid var(--border-color);
  padding: 8px 16px; border-radius: 100px; display: flex; align-items: center; gap: 10px;
  font-family: 'JetBrains Mono', monospace; font-size: 14px; font-weight: 700; color: var(--text-secondary);
}

.top-bar-right { display: flex; align-items: center; gap: 20px; }
.shift-pill {
  display: flex; align-items: center; gap: 8px;
  background: var(--success-bg); color: var(--success);
  padding: 6px 14px; border-radius: 100px; font-size: 11px; font-weight: 600;
}
.pulse-dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; animation: pulse 2s infinite; }

.top-action-btn {
  width: 40px; height: 40px; border-radius: 12px; border: 1px solid var(--border-color);
  background: var(--bg-primary); color: var(--text-secondary); cursor: pointer;
  display: flex; align-items: center; justify-content: center; transition: 0.2s;
}

.pos-user-card {
  display: flex; align-items: center; gap: 12px; background: var(--bg-primary);
  padding: 6px 6px 6px 6px; border-radius: 14px; border: 1px solid var(--border-color);
}
.u-avatar-wrapper { position: relative; }
.u-avatar {
  width: 32px; height: 32px; border-radius: 10px; background: var(--accent-light);
  color: var(--accent); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px;
}
.online-dot {
  position: absolute; bottom: -2px; right: -2px;
  width: 10px; height: 10px; border-radius: 50%;
  background: #22c55e; border: 2px solid var(--bg-primary);
}
.u-info { padding: 0 4px; }
.u-name { display: block; font-size: 12px; font-weight: 700; color: var(--text-primary); }
.u-status { font-size: 10px; color: var(--success); font-weight: 700; display: flex; align-items: center; gap: 4px; }
.s-dot { width: 5px; height: 5px; border-radius: 50%; background: currentColor; }

.btn-pos-logout {
  width: 32px; height: 32px; border: none; background: transparent;
  color: var(--text-muted); cursor: pointer; display: flex; align-items: center; justify-content: center;
  border-radius: 8px; transition: 0.2s;
}
.btn-pos-logout:hover { background: var(--danger-bg); color: var(--danger); }

@media (max-width: 768px) {
  .top-bar-center { display: none; }
  .pos-user-card { gap: 4px; padding: 4px; border-radius: 12px; }
  .u-info { display: none; }
}
/* ── Main POS Layout ── */
.pos-main { flex: 1; display: flex; overflow: hidden; }

/* ── Left: Products ── */
.pos-products-section { flex: 1; display: flex; flex-direction: column; background: var(--bg-primary); border-right: 1px solid var(--border-color); }

.pos-filter-bar { 
  padding: 16px 24px; 
  display: flex; 
  align-items: center; 
  justify-content: space-between; 
  background: var(--bg-secondary); 
  border-bottom: 1px solid var(--border-color);
  gap: 16px;
}
.category-scroll-container { 
  flex: 1; 
  display: flex; 
  gap: 10px; 
  overflow-x: auto; 
  padding-bottom: 4px;
  transition: opacity 0.3s;
  min-width: 0; /* Prevents flex items from stretching parent */
}
.cat-chip {
  white-space: nowrap; padding: 10px 20px; border-radius: 100px;
  background: var(--bg-primary); border: 1px solid var(--border-color);
  font-size: 13px; font-weight: 700; color: var(--text-secondary); cursor: pointer; transition: 0.2s;
}
.cat-chip:hover { border-color: var(--accent); color: var(--text-primary); }
.cat-chip.active { background: var(--accent); color: #fff; border-color: var(--accent); box-shadow: 0 4px 12px rgba(249, 115, 22, 0.3); }

.pos-search-wrapper {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: flex-end;
}

.btn-search-toggle {
  width: 44px;
  height: 44px;
  border-radius: 14px;
  background: var(--bg-primary);
  border: 1.5px solid var(--border-color);
  color: var(--text-secondary);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  z-index: 10;
  transition: 0.3s;
}
.pos-search-wrapper.expanded .btn-search-toggle {
  background: var(--accent);
  color: #fff;
  border-color: var(--accent);
  transform: rotate(90deg);
}

.search-input-slide {
  position: absolute;
  right: 0;
  width: 44px;
  height: 44px;
  opacity: 0;
  pointer-events: none;
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.pos-search-wrapper.expanded .search-input-slide {
  width: 250px;
  opacity: 1;
  pointer-events: all;
  padding-right: 50px;
}

.search-input-slide input {
  width: 100%;
  height: 100%;
  border-radius: 14px;
  border: 1.5px solid var(--accent);
  background: var(--bg-card);
  padding: 0 16px;
  font-size: 14px;
  font-weight: 600;
  color: var(--text-primary);
  outline: none;
  box-shadow: 0 8px 20px -5px rgba(249, 115, 22, 0.2);
}

.product-grid-wrap { flex: 1; padding: 24px; overflow-y: auto; }
.premium-product-grid {
  display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 20px;
}

.p-card-premium {
  background: var(--bg-card); border: 1px solid var(--border-color);
  border-radius: 20px; overflow: hidden; cursor: pointer; transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  display: flex; flex-direction: column; animation: slideInUp 0.5s ease both;
}
.p-card-premium:hover { transform: translateY(-8px); border-color: var(--accent); box-shadow: 0 12px 24px -10px rgba(0,0,0,0.1); }
.p-card-premium.added { transform: scale(0.95); border-color: var(--success); }

.p-card-img { height: 140px; position: relative; overflow: hidden; background: var(--bg-secondary); }
.p-card-img img { width: 100%; height: 100%; object-fit: cover; transition: 0.5s; }
.p-card-premium:hover .p-card-img img { transform: scale(1.1); }
.p-card-overlay { position: absolute; inset: 0; background: rgba(0,0,0,0.4); opacity: 0; transition: 0.3s; display: flex; align-items: center; justify-content: center; color: #fff; }
.p-card-premium:hover .p-card-overlay { opacity: 1; }
.p-img-placeholder { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: var(--text-muted); opacity: 0.3; }

.sold-out-badge { position: absolute; top: 12px; right: 12px; background: #ef4444; color: #fff; padding: 4px 10px; border-radius: 8px; font-size: 10px; font-weight: 600; text-transform: uppercase; }
.out-of-stock { opacity: 0.6; cursor: not-allowed; filter: grayscale(1); }

.p-card-info { padding: 16px; display: flex; flex-direction: column; gap: 8px; }
.p-title { font-size: 14px; font-weight: 700; color: var(--text-primary); margin: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.p-bottom { display: flex; justify-content: space-between; align-items: center; }
.p-price { font-size: 15px; font-weight: 600; color: var(--accent); }
.p-type-badge { font-size: 9px; font-weight: 600; background: var(--bg-secondary); padding: 2px 6px; border-radius: 4px; color: var(--text-muted); text-transform: uppercase; }

/* ── Right: Cart Sidebar ── */
.pos-cart-section { width: 400px; background: var(--bg-secondary); padding: 20px; display: flex; flex-direction: column; }
.cart-premium-card { flex: 1; background: var(--bg-card); border-radius: 28px; border: 1px solid var(--border-color); display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 10px 30px -15px rgba(0,0,0,0.1); }

.cart-p-header { padding: 24px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; }
.cart-p-title { display: flex; align-items: center; gap: 14px; }
.cart-icon-box { width: 40px; height: 40px; border-radius: 12px; background: var(--accent-light); color: var(--accent); display: flex; align-items: center; justify-content: center; }
.cart-title-text h3 { font-size: 16px; font-weight: 600; color: var(--text-primary); margin: 0; }
.cart-title-text span { font-size: 12px; color: var(--text-muted); font-weight: 500; }
.btn-clear-cart { width: 32px; height: 32px; border-radius: 8px; border: none; background: var(--bg-primary); color: var(--text-muted); cursor: pointer; display: flex; align-items: center; justify-content: center; }

.cart-p-body { flex: 1; overflow-y: auto; padding: 20px; }
.cart-item-premium { 
  display: flex; align-items: center; gap: 12px; padding: 14px; 
  background: var(--bg-primary); border: 1px solid var(--border-color);
  border-radius: 16px; margin-bottom: 12px; position: relative;
}
.ci-img { width: 32px; height: 32px; border-radius: 8px; background: var(--bg-secondary); display: flex; align-items: center; justify-content: center; border: 1px solid var(--border-color); overflow: hidden; }
.ci-actual-img { width: 100%; height: 100%; object-fit: cover; }
.ci-details { flex: 1; min-width: 0; }
.ci-name { display: block; font-size: 13px; font-weight: 700; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ci-unit-price { font-size: 11px; color: var(--text-muted); font-weight: 500; }

.ci-controls { display: flex; align-items: center; gap: 10px; }
.qty-stepper { display: flex; align-items: center; background: var(--bg-secondary); border-radius: 8px; padding: 2px; }
.step-btn { width: 24px; height: 24px; border-radius: 6px; border: none; background: var(--bg-card); color: var(--text-primary); cursor: pointer; display: flex; align-items: center; justify-content: center; }
.qty-val { font-size: 12px; font-weight: 600; min-width: 24px; text-align: center; }

.btn-ci-delete {
  width: 30px; height: 30px; border-radius: 8px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  border: 1px solid rgba(239,68,68,0.2); background: rgba(239,68,68,0.05);
  color: #ef4444; cursor: pointer; transition: all 0.2s;
}
.btn-ci-delete:hover { background: #ef4444; color: #fff; border-color: #ef4444; transform: scale(1.1); }

.cart-p-footer { padding: 24px; background: var(--bg-secondary); border-top: 1px solid var(--border-color); }
.summary-box { margin-bottom: 20px; }
.sum-row { display: flex; justify-content: space-between; font-size: 14px; color: var(--text-secondary); margin-bottom: 8px; }
.sum-row.total { font-size: 18px; font-weight: 700; color: var(--text-primary); padding-top: 12px; border-top: 2px dashed var(--border-color); margin-top: 12px; }

.btn-checkout-premium {
  width: 100%; height: 64px; border-radius: 18px; border: none;
  background: linear-gradient(135deg, var(--accent), #fb923c);
  color: #fff; cursor: pointer; display: flex; align-items: center; overflow: hidden;
  box-shadow: 0 10px 25px -10px rgba(249, 115, 22, 0.4); transition: 0.3s;
}
.btn-checkout-premium:hover { transform: translateY(-4px); box-shadow: 0 15px 30px -10px rgba(249, 115, 22, 0.5); }
.btn-checkout-premium:disabled { opacity: 0.5; cursor: not-allowed; transform: none; box-shadow: none; filter: grayscale(0.5); }
.btn-c-content { flex: 1; display: flex; flex-direction: column; align-items: flex-start; justify-content: center; padding-left: 24px; }
.btn-c-text { font-size: 11px; font-weight: 600; opacity: 0.9; letter-spacing: 0.5px; }
.btn-c-total { font-size: 18px; font-weight: 700; line-height: 1; }
.btn-c-icon { width: 64px; height: 100%; background: rgba(0,0,0,0.1); display: flex; align-items: center; justify-content: center; }


@keyframes pulse { 0% { transform: scale(1); opacity: 1; } 50% { transform: scale(1.5); opacity: 0.5; } 100% { transform: scale(1); opacity: 1; } }
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes slideInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
@keyframes spin { to { transform: rotate(360deg); } }
.spinner { animation: spin 0.8s linear infinite; }

.cart-item-anim-enter-active, .cart-item-anim-leave-active { transition: all 0.3s ease; }
.cart-item-anim-enter-from { opacity: 0; transform: translateX(30px); }
.cart-item-anim-leave-to { opacity: 0; transform: translateX(-30px); }

/* Mobile Payment Bar (Stick to bottom) */
.mobile-pay-bar {
  display: none; position: fixed; bottom: 0; left: 0; right: 0; 
  height: auto; min-height: 72px; background: #ffffff; border-top: 1px solid rgba(0,0,0,0.05);
  padding: 12px 20px; align-items: center; justify-content: space-between; z-index: 2000;
  box-shadow: 0 -10px 30px rgba(0,0,0,0.08);
}
.dark .mobile-pay-bar { background: var(--bg-secondary); border-color: var(--border-color); }
.m-pay-left { display: flex; align-items: center; gap: 12px; }
.m-pay-badge { width: 24px; height: 24px; background: var(--accent); color: #fff; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 800; }
.m-pay-total { display: flex; flex-direction: column; line-height: 1.2; }
.m-total-label { font-size: 10px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; }
.m-total-val { font-size: 15px; font-weight: 800; color: #1e293b; }
.dark .m-total-val { color: var(--text-primary); }
.m-pay-btn { background: var(--accent); color: #fff; padding: 10px 18px; border-radius: 12px; font-weight: 800; display: flex; align-items: center; gap: 6px; font-size: 14px; box-shadow: 0 4px 12px rgba(249, 115, 22, 0.3); }

@media (max-width: 1024px) {
  .pos-cart-section { display: none; }
  .mobile-pay-bar { display: flex; }
}

/* Specific mobile layout optimization (Targeting phones and small screens up to 600px) */
@media (max-width: 600px) {
  .pos-root { overflow: hidden; } /* Prevent root scrolling, use nested scrolling */
  .pos-top-bar { 
    position: fixed; 
    top: 0; 
    left: 0; 
    right: 0; 
    height: calc(56px + var(--safe-top)); 
    padding: var(--safe-top) 12px 0; 
    border-bottom: 1px solid var(--border-color); 
    background: var(--bg-secondary); 
    z-index: 150; 
    width: 100%; 
    box-sizing: border-box; 
  }
  .pos-main { padding-top: calc(56px + var(--safe-top)); display: flex; flex-direction: column; height: 100vh; height: 100dvh; overflow: hidden; }
  .pos-products-section { overflow-y: auto; flex: 1; }
  
  .shift-pill { padding: 4px 8px; }
  .shift-pill span:not(.pulse-dot) { display: none; }
  
  /* Show and shrink clock for mobile */
  .top-bar-center { display: flex !important; flex: 1; justify-content: center; }
  .pos-clock-glass { 
    padding: 4px 10px; 
    font-size: 11px; 
    gap: 6px; 
    border-radius: 50px;
    background: var(--bg-primary);
  }
  .pos-clock-glass svg { width: 12px; height: 12px; }
  
  /* Compact User Card for Mobile (FORCED VISIBILITY) */
  .pos-user-card { 
    display: flex !important; 
    visibility: visible !important;
    gap: 8px; 
    padding: 2px !important; 
    border-radius: 100px; 
    background: transparent !important; 
    border: none !important; 
    box-shadow: none !important;
  }
  .u-avatar { width: 34px; height: 34px; border-radius: 50%; font-size: 13px; }
  .online-dot { width: 12px; height: 12px; border-width: 2px; }
  .u-info { display: none !important; }
  .btn-pos-logout { width: 34px; height: 34px; background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 50%; }
  
  .pos-logo { gap: 8px; }
  .logo-box { width: 32px; height: 32px; border-radius: 10px; }
  .logo-text h2 { font-size: 13px; margin: 0; font-weight: 700; }
  .logo-text span { display: block !important; font-size: 7px; letter-spacing: 0.8px; opacity: 0.7; margin-top: 1px; }
  
  .pos-filter-bar { 
    padding: 8px 12px; 
    position: sticky; 
    top: 0; /* Header is fixed, so sticky to top 0 of parent scroll section */
    z-index: 40;
    background: var(--bg-secondary);
    width: 100%;
    box-sizing: border-box;
    border-bottom: 1px solid var(--border-color);
  }
  
  .top-bar-right { gap: 8px; }
  
  .product-grid-wrap { padding: 14px 12px 100px 12px; }
  .premium-product-grid { gap: 14px; }
  
  .p-card-premium { border-radius: 16px; border: 1px solid rgba(0,0,0,0.06); }
  .p-card-img { height: 95px; }
  .p-card-info { padding: 12px; gap: 4px; }
  .p-title { 
    font-size: 13px; 
    font-weight: 700; 
    white-space: normal; 
    display: -webkit-box; 
    -webkit-line-clamp: 2; 
    line-clamp: 2; 
    -webkit-box-orient: vertical; 
    height: 34px; 
    line-height: 1.3; 
    margin-bottom: 2px;
  }
  .p-price { font-size: 14px; font-weight: 800; color: var(--accent); }
  .p-type-badge { font-size: 9px; font-weight: 700; background: var(--bg-secondary); padding: 2px 6px; }
  
  /* Filter bar tidy up */
  .pos-filter-bar { padding: 12px; gap: 10px; }
  .cat-chip { padding: 8px 16px; font-size: 13px; border-radius: 12px; font-weight: 700; }

  /* Mobile Pay Bar Refinement */
  .mobile-pay-bar { 
    height: 84px; 
    padding: 0 16px; 
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-top: 1px solid var(--border-color); 
    box-shadow: 0 -8px 25px rgba(0,0,0,0.06);
    cursor: pointer; 
  }
  .dark .mobile-pay-bar { background: rgba(30, 41, 59, 0.95); }
  .m-pay-badge-wrap { display: flex; align-items: center; gap: 6px; }
  .m-pay-badge { width: 30px; height: 30px; font-size: 13px; font-weight: 800; }
  .m-total-label { font-size: 9px; }
  .m-total-val { font-size: 18px; color: var(--accent) !important; }
  .m-pay-btn { padding: 12px 20px; font-size: 14px; border-radius: 14px; font-weight: 800; }
}

</style>

<!-- Non-scoped styles for Teleported modals (they live outside the component DOM tree) -->
<style>
/* ── Modal Backdrop (Teleported) ── */
.modal-backdrop, .drawer-backdrop {
  position: fixed;
  inset: 0;
  z-index: 200;
  background: rgba(0, 0, 0, 0.4);
  backdrop-filter: blur(4px);
}
.drawer-backdrop { z-index: 1500; } /* Below drawer and pay bar */

/* ── Mobile Cart Drawer ── */
.mobile-cart-drawer {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  background: var(--bg-card);
  border-top-left-radius: 28px;
  border-top-right-radius: 28px;
  z-index: 1800;
  padding: 0 0 80px 0; /* Match pay bar height exactly */
  box-shadow: 0 -10px 40px rgba(0,0,0,0.15);
  display: flex;
  flex-direction: column;
  max-height: 80vh;
}

.drawer-header {
  padding: 24px 24px 16px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid var(--border-color);
}
.drawer-title { display: flex; align-items: center; gap: 10px; color: var(--accent); }
.drawer-title h3 { font-size: 16px; font-weight: 700; color: var(--text-primary); margin: 0; }
.item-count { font-size: 12px; color: var(--text-muted); font-weight: 600; }
.btn-close-drawer { border: none; background: transparent; color: var(--text-muted); padding: 4px; }

.drawer-body { padding: 12px 20px; flex: 1; overflow-y: auto; }
.drawer-item { 
  display: flex; align-items: center; justify-content: space-between; 
  padding: 16px; border: 1px solid var(--border-color); 
  background: var(--bg-primary); 
  border-radius: 20px; 
  margin-bottom: 12px;
}
.di-left { display: flex; align-items: center; gap: 14px; }
.di-icon-box { 
  width: 36px; height: 36px; border-radius: 10px; 
  border: 1px solid var(--border-color); 
  display: flex; align-items: center; justify-content: center; 
  color: var(--text-primary); background: #fff;
  overflow: hidden;
}
.di-actual-img { width: 100%; height: 100%; object-fit: cover; }
.dark .di-icon-box { background: var(--bg-card); }

.di-details { display: flex; flex-direction: column; gap: 2px; }
.di-name { font-size: 13px; font-weight: 700; color: var(--text-primary); }
.di-price-info { font-size: 11px; color: var(--text-muted); font-weight: 600; }

.di-right { display: flex; align-items: center; gap: 12px; }
.di-qty-pill { 
  display: flex; align-items: center; background: #fff; 
  border-radius: 14px; padding: 4px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.03);
}
.dark .di-qty-pill { background: var(--bg-secondary); }

.qty-btn { 
  width: 24px; height: 24px; border-radius: 8px; border: none; 
  background: transparent; color: var(--text-primary); cursor: pointer;
  display: flex; align-items: center; justify-content: center;
}
.qty-num { font-size: 12px; font-weight: 700; padding: 0 8px; min-width: 20px; text-align: center; }

.btn-di-delete {
  width: 34px; height: 34px; border-radius: 10px; 
  display: flex; align-items: center; justify-content: center;
  border: 1.5px solid rgba(239, 68, 68, 0.2); 
  background: rgba(239, 68, 68, 0.05);
  color: #ef4444; cursor: pointer;
}

/* Drawer Animation */
.drawer-enter-active, .drawer-leave-active { transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1); }
.drawer-enter-from, .drawer-leave-to { transform: translateY(100%); }

.modal-backdrop {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

/* ── Modal Transition ── */
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s ease;
}
.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}
.modal-enter-active .modal-panel-checkout,
.modal-enter-active .modal-panel-small {
  animation: modalSlideIn 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.modal-leave-active .modal-panel-checkout,
.modal-leave-active .modal-panel-small {
  animation: modalSlideOut 0.2s ease forwards;
}

@keyframes modalSlideIn {
  from {
    opacity: 0;
    transform: scale(0.92) translateY(20px);
  }
  to {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}
@keyframes modalSlideOut {
  from {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
  to {
    opacity: 0;
    transform: scale(0.92) translateY(20px);
  }
}

/* ── Fade-Slide Transition (Cash input area) ── */
.fade-slide-enter-active,
.fade-slide-leave-active {
  transition: all 0.3s ease;
}
.fade-slide-enter-from,
.fade-slide-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

/* ── Modal top bar shared (used in Table Modal) ── */
.modal-top {
  display: flex;
  align-items: flex-start;
  gap: 16px;
  padding: 24px;
  border-bottom: 1px solid var(--border-color);
}
.modal-icon-wrap {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.modal-icon-wrap.info {
  background: var(--accent-light);
  color: var(--accent);
}
.modal-title-area {
  flex: 1;
}
.modal-title {
  font-size: 18px;
  font-weight: 600;
  color: var(--text-primary);
  margin: 0;
}
.modal-desc {
  font-size: 13px;
  color: var(--text-muted);
  margin: 0;
}
.modal-close {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  border: none;
  background: var(--bg-primary);
  color: var(--text-muted);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}
.modal-content {
  padding: 24px;
}

/* ── Checkout Modal Responsive ── */
@media (max-width: 900px) {
  .modal-panel-checkout {
    max-width: 100%;
    max-height: 95vh;
    border-radius: 24px;
  }
  .checkout-body {
    grid-template-columns: 1fr !important;
  }
  .ch-summary-area {
    border-right: none !important;
    border-bottom: 1px solid var(--border-color);
  }
}

@media (max-width: 600px) {
  .modal-backdrop { 
    padding: 0 !important; 
    background: rgba(0,0,0,0.35) !important; 
    backdrop-filter: blur(2px) !important; 
    z-index: 2100; 
  }
  .modal-panel-checkout { 
    height: 100vh !important; 
    max-height: 100vh !important;
    border-radius: 0 !important;
    border: none !important;
    background: var(--bg-primary) !important;
  }
  .checkout-header {
    padding: 12px 14px !important;
    background: var(--bg-card) !important;
    position: sticky;
    top: 0;
    z-index: 50;
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    flex-wrap: nowrap !important;
    border-bottom: 1px solid var(--border-color);
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
  }
  .ch-left { gap: 6px !important; flex: 1; min-width: 0; }
  .ch-icon-wrap { 
    width: 24px !important; 
    height: 24px !important; 
    border-radius: 6px !important; 
    background: var(--success-bg) !important;
    color: var(--success) !important;
    display: none !important; /* Hide small icon to save space in row */
  }
  .ch-title { font-size: 13px !important; font-weight: 800 !important; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .ch-desc { display: none !important; }
  .header-toggle { padding: 3px !important; border-radius: 10px !important; flex-shrink: 0; margin-left: 8px; }
  .toggle-opt { padding: 5px 8px !important; font-size: 9px !important; border-radius: 7px !important; gap: 4px !important; }
  .toggle-opt span { display: none; } /* Show only icons on very small screens to keep it aligned, or keep them if they fit. Let's try keeping them but very compact. Actually user image shows text. Let's try keeping text but smaller. */
  .toggle-opt.active span { display: inline-block; } /* Logic: show text only for active mode to save space */
  .toggle-opt svg { width: 12px !important; height: 12px !important; }
  .btn-back-header { width: 34px !important; height: 34px !important; border-radius: 10px !important; margin-right: 2px !important; }
  .btn-back-header svg { width: 18px !important; height: 18px !important; }
  
  .checkout-body { 
    height: calc(100vh - 57px); 
    overflow-y: auto; 
    display: flex !important;
    flex-direction: column;
    padding-bottom: 100px;
    background: var(--bg-primary);
  }
  .ch-summary-area { 
    padding: 24px 16px !important; 
    background: var(--bg-card) !important;
    border-bottom: 8px solid var(--bg-primary); /* Section separator */
  }
  .summary-glass-card { background: transparent !important; box-shadow: none !important; border: none !important; }
  .ch-form-area { padding: 24px 16px !important; flex: 1; background: var(--bg-primary) !important; }
  
  .sum-items-list { overflow: visible !important; margin-bottom: 16px !important; }
  .sum-title { 
    font-size: 11px !important; 
    margin-bottom: 12px !important; 
    text-transform: uppercase; 
    letter-spacing: 0.8px; 
    color: var(--text-muted) !important; 
    font-weight: 800 !important;
  }
  .sum-item { 
    margin-bottom: 12px !important; 
    border-bottom: 1px dashed var(--border-color); 
    padding-bottom: 12px !important;
    align-items: center !important;
  }
  .sum-item:last-child { border-bottom: none !important; margin-bottom: 0 !important; }
  .sum-item-img { width: 36px !important; height: 36px !important; border-radius: 8px !important; }
  .sum-item-n { font-size: 14px !important; color: var(--text-primary) !important; font-weight: 700 !important; }
  .sum-item-p { font-size: 14px !important; color: var(--text-primary) !important; font-weight: 800 !important; }
  .sum-item-q { font-size: 12px !important; background: var(--accent-light); color: var(--accent) !important; padding: 2px 6px; border-radius: 6px; font-weight: 800 !important; }
  .sum-final-row { margin-bottom: 0 !important; padding: 20px 0 0 !important; border-top: 2px dashed var(--border-color); }
  .sum-total-val { font-size: 24px !important; color: var(--accent) !important; font-family: 'JetBrains Mono', monospace; }
  .sum-final-row span:first-child { font-size: 12px !important; color: var(--text-muted) !important; font-weight: 700 !important; text-transform: uppercase; letter-spacing: 1px; }
  .sum-divider { display: none !important; }

  /* Input and Payment Grid */
  .ch-input-grid { grid-template-columns: 1fr !important; gap: 12px !important; margin-bottom: 16px !important; }
  .ch-input-group label, .section-label { font-size: 11px !important; margin-bottom: 4px !important; }
  .input-wrap input { height: 44px !important; font-size: 13px !important; padding-left: 40px !important; }
  .input-wrap .i-icon { left: 14px !important; width: 14px !important; }
  .input-wrap.large input { height: 50px !important; font-size: 18px !important; }
  
  .table-select-trigger { height: 44px !important; font-size: 13px !important; padding: 0 12px !important; }
  
  .payment-grid { 
    grid-template-columns: repeat(3, 1fr) !important; 
    gap: 8px !important; 
    margin-top: 10px !important; 
  }
  .pay-card { 
    padding: 10px 4px !important;
    height: auto !important; 
    min-height: 70px !important;
    border-radius: 14px !important; 
    gap: 6px !important; 
  }
  .pay-icon { width: 18px !important; height: 18px !important; }
  .pay-name { font-size: 9px !important; line-height: 1.2; }

  .cash-advanced-area { 
    padding: 20px !important; 
    border-radius: 24px !important; 
    border: 1px solid var(--border-color);
  }
  .q-cash-btn { height: 42px !important; font-size: 11px !important; border-radius: 12px !important; font-weight: 700 !important; }
  .num-btn { height: 40px !important; font-size: 14px !important; }
  
  .change-display { 
    padding: 16px 20px !important; 
    border-radius: 20px !important; 
    margin-top: 16px !important; 
    border: 2px solid var(--border-color) !important;
  }
  .change-display.ok { border-color: var(--success) !important; background: var(--success-bg) !important; }
  .c-val { font-size: 24px !important; font-weight: 800 !important; }
  .c-label { font-size: 10px !important; font-weight: 700 !important; letter-spacing: 0.5px; }
  
  .btn-pay-final { 
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    z-index: 100;
    height: calc(64px + env(safe-area-inset-bottom)) !important;
    padding-bottom: env(safe-area-inset-bottom) !important;
    font-size: 14px !important;
    font-weight: 800 !important;
    letter-spacing: 0.5px;
    border-radius: 0 !important;
    background: linear-gradient(135deg, var(--accent), #fb923c) !important;
    box-shadow: 0 -10px 30px rgba(0,0,0,0.1);
    display: flex !important;
    align-items: center;
    justify-content: center;
    transition: 0.2s;
  }
  .btn-pay-final:active { transform: scale(0.98); opacity: 0.9; }
}

/* ── Section label utility ── */
.section-label {
  display: block;
  font-size: 11px;
  font-weight: 600;
  color: var(--text-muted);
  text-transform: uppercase;
  margin-bottom: 4px;
}

.mt-4 { margin-top: 16px; }
.m-left-auto { margin-left: auto; }

/* ── Numpad Grid ── */
.numpad-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 10px;
}
.num-btn {
  height: 44px;
  border-radius: 10px;
  border: 1px solid var(--border-color);
  background: var(--bg-card);
  color: var(--text-primary);
  font-size: 16px;
  font-weight: 700;
  cursor: pointer;
  transition: 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
}
.num-btn:active {
  transform: scale(0.95);
  background: var(--bg-secondary);
}
.num-btn.special {
  color: var(--accent);
  background: var(--accent-light);
  border-color: var(--accent);
}
.num-btn.triple-zero {
  grid-column: span 3;
  height: 38px;
  font-size: 14px;
  color: var(--accent);
  background: var(--bg-primary);
}

/* ── Checkout Modal ── */
.modal-panel-checkout { 
  width: 100%; 
  max-width: 900px; 
  max-height: 92vh;
  background: var(--bg-card); 
  border-radius: 32px; 
  overflow: hidden; 
  display: flex; 
  flex-direction: column; 
}
.checkout-header { padding: 24px 32px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border-color); }
.ch-left { display: flex; align-items: center; gap: 20px; }
.ch-icon-wrap { width: 50px; height: 50px; border-radius: 16px; background: var(--success-bg); color: var(--success); display: flex; align-items: center; justify-content: center; }
.ch-title { font-size: 20px; font-weight: 600; color: var(--text-primary); margin: 0; }
.ch-desc { font-size: 13px; color: var(--text-muted); margin: 0; }
.btn-back-header { width: 40px; height: 40px; border-radius: 12px; background: var(--bg-primary); border: none; color: var(--text-muted); cursor: pointer; display: flex; align-items: center; justify-content: center; transition: 0.2s; margin-right: 4px; }
.btn-back-header:hover { background: var(--bg-secondary); color: var(--text-primary); }

.checkout-body { 
  display: grid; 
  grid-template-columns: 340px 1fr; 
  overflow-y: auto;
  flex: 1;
}
.ch-form-area { padding: 24px 32px; background: var(--bg-primary); }

.ch-right { display: flex; align-items: center; gap: 16px; }
.header-toggle { 
  display: flex; 
  background: var(--bg-primary); 
  padding: 4px; 
  border-radius: 12px; 
  border: 1px solid var(--border-color);
}
.toggle-opt {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 6px 12px;
  border-radius: 8px;
  border: none;
  background: transparent;
  color: var(--text-muted);
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: 0.2s;
}
.toggle-opt.active {
  background: var(--accent);
  color: #fff;
  box-shadow: 0 4px 12px rgba(249, 115, 22, 0.2);
}
.toggle-opt i, .toggle-opt svg { flex-shrink: 0; }

.ch-input-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 18px; }
.ch-input-group label { display: block; font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; margin-bottom: 5px; }
.input-wrap { position: relative; display: flex; align-items: center; }
.input-wrap .i-icon { position: absolute; left: 16px; color: var(--text-muted); }
.input-wrap input { width: 100%; height: 50px; border-radius: 16px; border: 1.5px solid var(--border-color); background: var(--bg-card); padding-left: 48px; font-size: 14px; font-weight: 600; color: var(--text-primary); outline: none; transition: 0.2s; }
.input-wrap input:focus { border-color: var(--accent); }
.input-wrap.large input { height: 60px; font-size: 20px; font-weight: 600; font-family: 'JetBrains Mono', monospace; }

.table-select-trigger { width: 100%; height: 50px; border-radius: 16px; border: 1.5px solid var(--border-color); background: var(--bg-card); padding: 0 16px; display: flex; align-items: center; gap: 12px; font-size: 14px; font-weight: 700; color: var(--text-primary); cursor: pointer; }
.placeholder { color: var(--text-muted); font-weight: 500; font-size: 13px; }

.payment-method-area { margin-bottom: 12px; }
.payment-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-top: 12px; }
.pay-card {
  position: relative; height: 90px; border-radius: 20px; border: 2px solid var(--border-color);
  background: var(--bg-card); cursor: pointer; display: flex; flex-direction: column;
  align-items: center; justify-content: center; gap: 10px; transition: 0.2s;
}
.pay-card.active { border-color: var(--success); background: var(--success-bg); color: var(--success); }
.pay-check-mark { position: absolute; top: 8px; right: 8px; background: var(--success); color: #fff; width: 20px; height: 20px; border-radius: 50%; display: none; align-items: center; justify-content: center; }
.pay-card.active .pay-check-mark { display: flex; }
.pay-name { font-size: 11px; font-weight: 600; text-transform: uppercase; }

/* ── Sub-method Selection ── */
.sub-method-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
  gap: 10px;
  background: var(--bg-secondary);
  padding: 14px;
  border-radius: 18px;
  border: 1px dashed var(--border-color);
}
.sub-pay-btn {
  position: relative;
  height: auto;
  min-height: 54px;
  padding: 10px 12px;
  border-radius: 14px;
  border: 1.5px solid var(--border-color);
  background: var(--bg-card);
  color: var(--text-secondary);
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
  text-align: center;
}
.sub-pay-content {
  display: flex;
  flex-direction: column;
  gap: 1px;
}
.sub-pay-name {
  font-size: 13px;
  font-weight: 800;
  text-transform: uppercase;
}
.sub-pay-meta {
  font-size: 11px;
  font-family: 'JetBrains Mono', monospace;
  opacity: 0.8;
}
.sub-pay-meta-name {
  font-size: 9px;
  text-transform: uppercase;
  opacity: 0.6;
  font-weight: 600;
}
.sub-pay-btn:hover {
  border-color: var(--accent);
  color: var(--accent);
}
.sub-pay-btn.active {
  background: var(--accent);
  color: #fff;
  border-color: var(--accent);
  box-shadow: 0 4px 12px rgba(249, 115, 22, 0.2);
}
.sub-check {
  position: absolute;
  top: -6px;
  right: -6px;
  width: 20px;
  height: 20px;
  background: var(--success);
  color: #fff;
  border: 2px solid var(--bg-card);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2;
}


.cash-advanced-area { background: var(--bg-secondary); border-radius: 24px; padding: 24px; }
.quick-cash-row { display: flex; gap: 8px; flex-wrap: wrap; }
.q-cash-btn { flex: 1; height: 44px; border-radius: 12px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-secondary); font-size: 12px; font-weight: 600; cursor: pointer; transition: 0.2s; }
.q-cash-btn:hover { border-color: var(--accent); color: var(--accent); }

.change-display { margin-top: 20px; background: var(--bg-card); border-radius: 18px; padding: 16px 20px; border: 1.5px solid var(--border-color); display: flex; align-items: center; justify-content: space-between; }
.change-display.ok { background: var(--success-bg); border-color: var(--success); color: var(--success); }
.c-label { display: block; font-size: 11px; font-weight: 600; text-transform: uppercase; margin-bottom: 4px; }
.c-val { font-size: 24px; font-weight: 700; line-height: 1; }

.ch-summary-area { padding: 32px; background: var(--bg-secondary); border-right: 1px solid var(--border-color); }
.summary-glass-card { height: 100%; display: flex; flex-direction: column; }
.sum-title { font-size: 15px; font-weight: 600; color: var(--text-primary); margin-bottom: 20px; }
.sum-items-list { flex: 1; overflow-y: auto; margin-bottom: 24px; padding-right: 8px; }
.sum-item { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
.sum-item-left { display: flex; align-items: center; gap: 10px; }
.sum-item-img { width: 28px; height: 28px; border-radius: 6px; background: var(--bg-primary); border: 1px solid var(--border-color); overflow: hidden; display: flex; align-items: center; justify-content: center; color: var(--text-muted); }
.sum-item-img img { width: 100%; height: 100%; object-fit: cover; }
.sum-item-q { font-weight: 600; color: var(--accent); font-size: 13px; }
.sum-item-n { font-weight: 600; font-size: 13px; color: var(--text-secondary); }
.sum-item-p { font-weight: 700; font-size: 13px; color: var(--text-primary); }

.sum-divider { height: 2px; border-bottom: 1px dashed var(--border-color); margin-bottom: 20px; }
.sum-final-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
.sum-final-row span:first-child { font-size: 13px; font-weight: 700; color: var(--text-muted); }
.sum-total-val { font-size: 24px; font-weight: 700; color: var(--accent); }

.btn-pay-final {
  width: 100%; height: 60px; border-radius: 18px; border: none;
  background: linear-gradient(135deg, var(--accent), #fb923c);
  color: #fff; font-size: 14px; font-weight: 700; cursor: pointer;
  box-shadow: 0 10px 20px -10px rgba(249, 115, 22, 0.4);
}
.btn-pay-final:disabled { opacity: 0.5; cursor: not-allowed; transform: none; box-shadow: none; }

/* ── Modal Small (Table Selection) ── */
.modal-panel-small { width: 100%; max-width: 440px; background: var(--bg-card); border-radius: 32px; overflow: hidden; }
.table-search-box { margin: 24px 28px 16px; position: relative; }
.ts-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-muted); }
.table-search-box input { width: 100%; height: 44px; border-radius: 12px; border: 1px solid var(--border-color); background: var(--bg-primary); padding-left: 40px; font-size: 14px; color: var(--text-primary); outline: none; }
.table-selection-grid { max-height: 400px; overflow-y: auto; display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; padding: 0 28px 28px; }

.t-item-choice {
  height: 100px; border-radius: 18px; border: 1.5px solid var(--border-color);
  background: var(--bg-primary); cursor: pointer; display: flex; flex-direction: column;
  align-items: center; justify-content: center; gap: 8px; transition: 0.2s;
}
.t-item-choice.active { border-color: var(--accent); background: var(--accent-light); color: var(--accent); }
.t-number { width: 40px; height: 40px; border-radius: 10px; background: var(--bg-secondary); display: flex; align-items: center; justify-content: center; font-size: 18px; font-weight: 700; color: var(--text-primary); transition: 0.2s; }
.t-item-choice.active .t-number { background: var(--accent); color: #fff; }
.t-item-choice span { font-size: 11px; font-weight: 700; color: var(--text-muted); }
/* ── Responsive ── */
@media (max-width: 1024px) {
  .pos-top-bar { padding: 0 16px; }
  .pos-cart-section { width: 350px; }
}

@media (max-width: 768px) {
  .top-bar-center { display: none; }
  .pos-cart-section { display: none; }
  .top-bar-right { gap: 8px; }
  
  .pos-root { height: 100vh; overflow-y: auto; overflow-x: hidden; width: 100%; }
  .pos-main { flex: none; display: block; overflow: visible; width: 100%; }
  .pos-products-section { border-right: none; overflow: visible; width: 100%; }
  
  .product-grid-wrap { padding: 12px; padding-bottom: 100px; overflow: visible; width: 100%; box-sizing: border-box; }
  .premium-product-grid { 
    grid-template-columns: repeat(2, 1fr); 
    gap: 10px; 
    width: 100%;
    margin: 0;
  }
  .mobile-pay-bar { display: flex; }
}

/* ── Compact & Colorful Digital Receipt Redesign ── */
.modal-panel-receipt {
  width: 100%; max-width: 380px; /* Diperkecil */
  animation: modalScale 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
  overflow: visible;
}

.receipt-card-wrapper {
  display: flex; flex-direction: column; gap: 16px;
}

.receipt-paper {
  background: #fff; 
  color: #1a1a1a;
  padding: 32px 24px; /* Lebih ramping */
  border-radius: 4px;
  box-shadow: 0 15px 40px rgba(0,0,0,0.12);
  position: relative;
}

/* Torn Paper Edge Effect */
.paper-edge {
  position: absolute;
  left: 0; right: 0;
  height: 10px;
  background-size: 14px 14px;
  z-index: 10;
}

.paper-edge.top {
  top: -5px;
  background-image: radial-gradient(circle at 7px -2px, transparent 7px, #fff 8px);
}

.paper-edge.bottom {
  bottom: -5px;
  background-image: radial-gradient(circle at 7px 12px, transparent 7px, #fff 8px);
}

.receipt-header { text-align: center; margin-bottom: 18px; }
.receipt-logo-wrap { display: flex; justify-content: center; margin-bottom: 12px; }
.receipt-logo { max-height: 60px; max-width: 120px; object-fit: contain; } /* Grayscale dihapus */
.receipt-icon-fallback { 
  width: 50px; height: 50px; border: 2px solid var(--accent); border-radius: 50%;
  display: flex; align-items: center; justify-content: center; color: var(--accent);
}

.receipt-shop-name { 
  font-size: 18px; font-weight: 800; color: #1a1a1a; margin: 0 0 4px 0; 
  text-transform: uppercase; letter-spacing: 0.5px;
}
.receipt-shop-info { font-size: 11px; color: #666; margin: 0; line-height: 1.4; font-weight: 500; }

.font-mono { font-family: 'JetBrains Mono', 'Courier New', Courier, monospace; }

.receipt-divider-dotted { 
  border-top: 1.5px dotted #ddd; margin: 16px 0; 
}
.receipt-divider-solid { 
  border-top: 1px solid #efefef; margin: 16px 0; 
}

.receipt-meta { font-size: 12px; line-height: 1.5; }
.meta-row { display: flex; justify-content: space-between; margin-bottom: 3px; }
.meta-row span { color: #666; font-weight: 500; }
.meta-row strong { color: var(--accent); font-weight: 800; } /* Warna accent di Invoice */

.receipt-items { margin: 16px 0; }
.r-item { margin-bottom: 10px; }
.r-item-main { display: flex; justify-content: space-between; font-weight: 700; font-size: 13px; color: #000; }
.r-item-total { color: #1a1a1a; }
.r-item-sub { font-size: 11px; color: #777; margin-top: 1px; font-weight: 500; }

.receipt-summary { font-size: 13px; }
.r-sum-row { display: flex; justify-content: space-between; margin-bottom: 6px; font-weight: 600; }
.r-divider-thin { border-top: 1px dashed #eee; margin: 10px 0; }

.r-sum-row.grand-total { 
  margin-top: 4px; padding: 10px 0; 
  font-size: 20px; font-weight: 900; color: var(--accent); /* Warna accent di Total */
  border-top: 1px solid #000; border-bottom: 1px solid #000;
}

.receipt-footer { text-align: center; margin-top: 24px; }
.footer-msg { font-size: 11px; font-weight: 700; color: #1a1a1a; margin-bottom: 6px; text-transform: uppercase; }
.powered-by { font-weight: 600; font-size: 9px; color: #bbb; letter-spacing: 0.5px; }

.receipt-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.btn-receipt-action {
  height: 48px; border-radius: 14px; border: none;
  display: flex; align-items: center; justify-content: center; gap: 10px;
  font-size: 13px; font-weight: 700; cursor: pointer; transition: 0.2s;
}

.btn-receipt-action.print, .btn-receipt-action.share { 
  background: #fff; 
  color: #1e293b; 
  border: 1.5px solid #e1e8f0; 
}
.btn-receipt-action.success-full { 
  background: #10b981; color: #fff; grid-column: span 2; 
  box-shadow: 0 8px 20px rgba(16, 185, 129, 0.2);
}

.btn-receipt-action:active { transform: scale(0.96); }

@media (max-width: 480px) {
  .modal-panel-receipt { max-width: 100%; padding: 0 10px; }
  .receipt-paper { padding: 24px 20px; }
  .receipt-shop-name { font-size: 16px; }
  .r-sum-row.grand-total { font-size: 18px; }
}

</style>
