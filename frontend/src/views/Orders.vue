<template>
  <div class="orders-container">
    <!-- Hero Header -->
    <div class="page-hero">
      <div class="hero-content">
        <div class="hero-icon-wrap">
          <History :size="24" />
        </div>
        <div>
          <h1 class="hero-title">Riwayat Pesanan</h1>
          <p class="hero-subtitle">Pantau dan kelola semua transaksi yang telah dilakukan di sistem POS.</p>
        </div>
      </div>
    </div>

    <!-- Filter Bar -->
    <div class="filter-glass-bar">
      <div class="filter-grid">
        <div class="filter-group-main">
          <div class="search-wrap">
            <Search :size="16" class="search-icon" />
            <input 
              type="text" 
              v-model="orderStore.filters.search" 
              placeholder="Cari no. invoice..." 
              @keyup.enter="refresh"
            >
          </div>
        </div>

        <div class="filter-row">
          <div class="filter-group">
            <label><Calendar :size="12" /> Dari</label>
            <input type="date" v-model="orderStore.filters.start_date" class="date-input">
          </div>
          <div class="filter-group">
            <label><Calendar :size="12" /> Sampai</label>
            <input type="date" v-model="orderStore.filters.end_date" class="date-input">
          </div>
          <div class="filter-group">
            <label><Filter :size="12" /> Status</label>
            <select v-model="orderStore.filters.status" class="status-select-main">
              <option value="">Semua Status</option>
              <option value="completed">Selesai</option>
              <option value="pending">Pending</option>
              <option value="cancelled">Batal</option>
            </select>
          </div>
          <div class="filter-actions-wrap">
            <button class="btn-apply" @click="refresh">
              <Check :size="16" /> Terapkan
            </button>
            <button class="btn-reset-icon" @click="reset" title="Reset Filter">
              <RotateCcw :size="16" />
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Orders Table Area -->
    <div class="table-outer-card">
      <div v-if="orderStore.loading && !orderStore.orders.length" class="table-loading">
        <div class="loading-spinner"></div>
        <span>Memuat data pesanan...</span>
      </div>

      <div v-else-if="!orderStore.orders.length" class="table-empty">
        <div class="empty-illu">
          <FileX :size="48" />
        </div>
        <h3>Pesanan Tidak Ditemukan</h3>
        <p>Gunakan filter lain atau reset pencarian Anda.</p>
        <button class="btn-secondary" @click="reset">Reset Semua Filter</button>
      </div>

      <div v-else class="table-scroll-wrap">
        <table class="premium-table">
          <thead>
            <tr>
              <th><div class="th-inner">Invoice</div></th>
              <th class="text-right"><div class="th-inner justify-end">Total</div></th>
              <th class="text-center"><div class="th-inner justify-center">Status</div></th>
              <th><div class="th-inner">Waktu</div></th>
              <th class="text-center"><div class="th-inner justify-center">Opsi</div></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="order in orderStore.orders" :key="order.id" class="table-row">
              <td data-label="Invoice">
                <div class="invoice-badge">
                  {{ order.invoice_number || 'ORD-' + String(order.id).padStart(6, '0') }}
                </div>
              </td>
              <td data-label="Total" class="text-right">
                <div class="total-amount-val">{{ formatCurrency(order.total_amount) }}</div>
              </td>
              <td data-label="Status" class="text-center">
                <div class="status-pill" :class="order.status">
                  <span class="dot-status"></span>
                  {{ translateStatus(order.status) }}
                </div>
              </td>
              <td data-label="Waktu">
                <div class="time-wrap">
                  <div class="date-val">{{ formatDate(order.created_at).split(',')[0] }}</div>
                  <div class="hour-val">{{ formatDate(order.created_at).split(',')[1] }}</div>
                </div>
              </td>
              <td class="text-center">
                <button class="btn-detail" @click="showDetails(order.id)">
                  Lihat Detail
                  <ChevronRight :size="14" />
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="pagination-area" v-if="orderStore.pagination.last_page > 1">
        <div class="pagin-info">
          Menampilkan halaman <strong>{{ orderStore.pagination.current_page }}</strong> dari <strong>{{ orderStore.pagination.last_page }}</strong>
        </div>
        <div class="pagin-btns">
          <button 
            class="pagin-btn"
            :disabled="orderStore.pagination.current_page === 1" 
            @click="orderStore.fetchOrders(orderStore.pagination.current_page - 1)"
          >
            <ChevronLeft :size="18" />
          </button>
          
          <button 
            v-for="p in visiblePages" 
            :key="p"
            :class="['pagin-btn-num', { active: p === orderStore.pagination.current_page }]"
            @click="orderStore.fetchOrders(p)"
          >
            {{ p }}
          </button>

          <button 
            class="pagin-btn"
            :disabled="orderStore.pagination.current_page === orderStore.pagination.last_page" 
            @click="orderStore.fetchOrders(orderStore.pagination.current_page + 1)"
          >
            <ChevronRight :size="18" />
          </button>
        </div>
      </div>
    </div>

    <!-- Order Details Modal -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="orderStore.selectedOrder" class="modal-backdrop" @click.self="orderStore.selectedOrder = null">
          <div class="modal-panel-large">
            <div class="modal-top">
              <div class="modal-top-left">
                <div class="invoice-icon">
                  <FileText :size="20" />
                </div>
                <div>
                  <h3 class="modal-title">Detail Transaksi</h3>
                  <p class="modal-id">#{{ orderStore.selectedOrder.order.invoice_number }}</p>
                </div>
              </div>
              <div class="modal-top-right">
                <div class="status-manage-wrap">
                  <span class="status-label-mini">Status Pesanan:</span>
                  <select 
                    :value="orderStore.selectedOrder.order.status" 
                    @change="e => orderStore.updateOrderStatus(orderStore.selectedOrder.order.id, e.target.value)"
                    class="status-select-inline"
                    :class="orderStore.selectedOrder.order.status"
                  >
                    <option value="pending">Pending</option>
                    <option value="completed">Selesai</option>
                    <option value="cancelled">Dibatalkan</option>
                  </select>
                </div>
                <button class="modal-close" @click="orderStore.selectedOrder = null">
                  <X :size="18" />
                </button>
              </div>
            </div>

            <div class="modal-content-grid">
              <!-- Left: Order Info -->
              <div class="modal-left-col">
                <div class="info-card-premium">
                  <div class="info-section">
                    <h4 class="info-title">Penerima / Order</h4>
                    <div class="info-row">
                      <span class="info-label">Pelanggan</span>
                      <span class="info-val">{{ orderStore.selectedOrder.order.customer_name || '-' }}</span>
                    </div>
                    <div class="info-row">
                      <span class="info-label">Tipe Order</span>
                      <span class="info-val">
                        {{ orderStore.selectedOrder.order.order_type === 'dine_in' ? 'Makan di Tempat' : 'Bawa Pulang' }}
                      </span>
                    </div>
                    <div v-if="orderStore.selectedOrder.order.table" class="info-row">
                      <span class="info-label">Meja</span>
                      <span class="info-val">Meja {{ orderStore.selectedOrder.order.table.table_number }}</span>
                    </div>
                  </div>

                  <div class="info-divider"></div>

                  <div class="info-section">
                    <h4 class="info-title">Transaksi</h4>
                    <div class="info-row">
                      <span class="info-label">Metode Bayar</span>
                      <span class="info-val-badge">{{ orderStore.selectedOrder.order.payment_method.toUpperCase() }}</span>
                    </div>
                    <div class="info-row">
                      <span class="info-label">Kasir</span>
                      <span class="info-val">{{ orderStore.selectedOrder.order.cashier_name || '-' }}</span>
                    </div>
                    <div class="info-row">
                      <span class="info-label">Tanggal</span>
                      <span class="info-val">{{ formatDate(orderStore.selectedOrder.order.created_at) }}</span>
                    </div>
                  </div>
                </div>

                <div class="print-actions">
                  <button class="btn-print-outline" @click="reprintReceipt">
                    <Printer :size="16" /> Cetak Struk
                  </button>
                  <button class="btn-print-outline">
                    <Share2 :size="16" /> Bagikan
                  </button>
                </div>
              </div>

              <!-- Right: Items -->
              <div class="modal-right-col">
                <div class="items-panel">
                  <h4 class="items-title">Rincian Menu <span class="badge-count">{{ orderStore.selectedOrder.order.items.length }}</span></h4>
                  <div class="items-scroll">
                    <div v-for="item in orderStore.selectedOrder.order.items" :key="item.id" class="receipt-item">
                      <div class="ri-img">
                        <img v-if="item.product?.image" :src="'/storage/' + item.product.image" alt="">
                        <Utensils v-else :size="16" />
                      </div>
                      <div class="ri-info">
                        <div class="ri-name">{{ item.product ? item.product.name : 'Produk Dihapus' }}</div>
                        <div class="ri-meta">
                          <span class="ri-price">{{ formatCurrency(item.price) }}</span>
                          <span class="ri-qty">x{{ item.quantity }}</span>
                        </div>
                      </div>
                      <div class="ri-total">
                        {{ formatCurrency(item.price * item.quantity) }}
                      </div>
                    </div>
                  </div>

                  <div class="modal-totals">
                    <div class="m-total-row">
                      <span>Subtotal</span>
                      <span>{{ formatCurrency(orderStore.selectedOrder.order.subtotal) }}</span>
                    </div>
                    <div class="m-total-row" v-if="orderStore.selectedOrder.order.discount > 0">
                      <span>Diskon</span>
                      <span class="minus">-{{ formatCurrency(orderStore.selectedOrder.order.discount) }}</span>
                    </div>
                    <div class="m-divider"></div>
                    <div class="m-total-row grand">
                      <span>Total Bayar</span>
                      <span class="val">{{ formatCurrency(orderStore.selectedOrder.order.total_amount) }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { onMounted, computed, watch, onUnmounted } from 'vue';
import { useOrderStore } from '../stores/order';
import { useAuthStore } from '../stores/auth';
import { useSettingStore } from '../stores/setting';
import { usePrinter } from '../composables/usePrinter';
import { showSuccess, showError, showConfirm } from '../utils/swal';
import { 
  History, Search, Eye, X, Filter, Calendar, RotateCcw, 
  Check, FileX, ChevronLeft, ChevronRight, Utensils,
  ShoppingBag, Printer, Share2, FileText
} from 'lucide-vue-next';

const orderStore = useOrderStore();
const auth = useAuthStore();
const settingStore = useSettingStore();
const printer = usePrinter();

// Watch selectedOrder to toggle mobile nav and body scroll
watch(() => orderStore.selectedOrder, (val) => {
  if (val) {
    document.body.classList.add('hide-mobile-nav');
    document.body.style.overflow = 'hidden';
  } else {
    document.body.classList.remove('hide-mobile-nav');
    document.body.style.overflow = '';
  }
});

onUnmounted(() => {
  document.body.classList.remove('hide-mobile-nav');
  document.body.style.overflow = '';
});

onMounted(() => {
  orderStore.fetchOrders();
  
  // Auto-connect printer on page load/refresh
  printer.autoConnect();
});

const refresh = () => {
  orderStore.fetchOrders(1);
};

const reset = () => {
  orderStore.resetFilters();
  refresh();
};

const showDetails = (id) => {
  orderStore.fetchOrderDetails(id);
};

const formatCurrency = (value) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
  }).format(value);
};

const formatDate = (dateStr) => {
  const date = new Date(dateStr);
  return date.toLocaleDateString('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  }).replace('.', ':');
};

const translateStatus = (status) => {
  const map = {
    completed: 'Selesai',
    pending: 'Tertunda',
    cancelled: 'Dibatalkan'
  };
  return map[status] || status;
};

const reprintReceipt = async () => {
  if (!orderStore.selectedOrder) return;

  // Check if printer is connected
  if (!printer.connected.value) {
    const result = await showError({
      title: 'Printer Tidak Terhubung',
      text: 'Hubungkan printer thermal Bluetooth terlebih dahulu di menu Pengaturan.',
      confirmText: 'OK'
    });
    return;
  }

  try {
    const order = orderStore.selectedOrder.order;
    
    const receiptData = {
      shopName: settingStore.settings?.shop_name || 'Kee POS',
      shopLogo: settingStore.settings?.shop_logo || null,
      shopAddress: settingStore.settings?.shop_address || '',
      shopPhone: settingStore.settings?.shop_phone || '',
      invoiceNo: order.invoice_number,
      date: new Date(order.created_at).toLocaleString('id-ID'),
      cashierName: order.cashier_name || order.user?.full_name || '-',
      customerName: order.customer_name || '-',
      tableName: order.table?.table_number ? `Meja ${order.table.table_number}` : (order.order_type === 'takeaway' ? 'TAKEAWAY' : '-'),
      items: order.items.map(i => ({
        name: i.product?.name || 'Item',
        qty: i.quantity,
        price: formatCurrency(i.price).replace('Rp\u00A0', ''),
        subtotal: formatCurrency(i.price * i.quantity).replace('Rp\u00A0', '')
      })),
      subtotal: formatCurrency(order.subtotal).replace('Rp\u00A0', ''),
      discount: formatCurrency(order.discount || 0).replace('Rp\u00A0', ''),
      tax: formatCurrency(order.tax || 0).replace('Rp\u00A0', ''),
      total: formatCurrency(order.total_amount).replace('Rp\u00A0', ''),
      paid: formatCurrency(order.payment_amount).replace('Rp\u00A0', ''),
      change: formatCurrency(order.change_amount || 0).replace('Rp\u00A0', ''),
      paymentMethod: order.payment_method.toUpperCase(),
      footer: 'Terima Kasih Atas Kunjungannya'
    };
    
    await printer.printReceipt(receiptData, parseInt(settingStore.paperSize) || 58, false); // Don't open drawer on reprint
    
    await showSuccess({
      title: 'Struk Tercetak',
      text: 'Struk berhasil dicetak ulang ke printer thermal.',
      timer: 2000
    });
  } catch (error) {
    console.error('Print failed:', error);
    await showError({
      title: 'Gagal Mencetak',
      text: error.message || 'Terjadi kesalahan saat mencetak struk.',
      confirmText: 'OK'
    });
  }
};

const visiblePages = computed(() => {
  const current = orderStore.pagination.current_page;
  const total = orderStore.pagination.last_page;
  const pages = [];
  
  for (let i = Math.max(1, current - 2); i <= Math.min(total, current + 2); i++) {
    pages.push(i);
  }
  return pages;
});
</script>

<style scoped>
.orders-container { padding: 0; animation: fadeIn 0.4s ease; }

/* ── Hero ── */
.page-hero {
  display: flex; align-items: center; justify-content: space-between;
  background: linear-gradient(135deg, rgba(249,115,22,0.08) 0%, rgba(249,115,22,0.02) 100%);
  border: 1px solid rgba(249,115,22,0.1); border-radius: 20px;
  padding: 20px 24px; margin-bottom: 12px;
}
.hero-content { display: flex; align-items: center; gap: 18px; }
.hero-icon-wrap {
  width: 48px; height: 48px; border-radius: 14px;
  background: linear-gradient(135deg, var(--accent), #fb923c);
  display: flex; align-items: center; justify-content: center;
  color: #fff; box-shadow: 0 8px 24px rgba(249,115,22,0.25);
}
.hero-title { font-size: 20px; font-weight: 600; color: var(--text-primary); margin-bottom: 2px; }
.hero-subtitle { font-size: 13px; color: var(--text-secondary); font-weight: 500; }

/* ── Filter Bar ── */
.filter-glass-bar {
  background: var(--bg-card); border: 1px solid var(--border-color);
  padding: 16px 20px; border-radius: 20px; margin-bottom: 12px;
}
.filter-grid { display: flex; flex-direction: column; gap: 16px; }

.search-wrap { position: relative; }
.search-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-muted); }
.search-wrap input {
  width: 100%; height: 42px; padding-left: 44px;
  background: var(--bg-primary); border: 1px solid var(--border-color);
  border-radius: 14px; font-size: 14px; color: var(--text-primary); outline: none;
  transition: all 0.2s;
}
.search-wrap input:focus { border-color: var(--accent); }

.filter-row { display: flex; align-items: flex-end; gap: 12px; flex-wrap: wrap; }
.filter-group { display: flex; flex-direction: column; gap: 6px; flex: 1; min-width: 150px; }
.filter-group label {
  display: flex; align-items: center; gap: 6px;
  font-size: 10px; font-weight: 600; color: var(--text-muted);
  text-transform: uppercase; letter-spacing: 0.8px;
}
.date-input, .status-select-main {
  height: 44px; padding: 0 14px; border-radius: 12px;
  background: var(--bg-primary); border: 1px solid var(--border-color);
  color: var(--text-primary); font-size: 13px; font-weight: 600; outline: none;
}
.filter-actions-wrap { display: flex; gap: 8px; }

.btn-apply {
  height: 44px; padding: 0 20px; border-radius: 12px;
  background: var(--accent); color: #fff; border: none;
  font-size: 13px; font-weight: 700; cursor: pointer;
  display: flex; align-items: center; gap: 8px; transition: all 0.2s;
}
.btn-apply:hover { background: var(--accent-hover); transform: translateY(-1px); }

.btn-reset-icon {
  width: 44px; height: 44px; border-radius: 12px;
  background: var(--bg-primary); border: 1px solid var(--border-color);
  color: var(--text-secondary); cursor: pointer; transition: all 0.2s;
}
.btn-reset-icon:hover { border-color: var(--danger); color: var(--danger); }

/* ── Table ── */
.table-outer-card {
  background: var(--bg-card); border: 1px solid var(--border-color);
  border-radius: 24px; overflow: hidden;
}
.table-scroll-wrap { overflow-x: auto; }
.premium-table { width: 100%; border-collapse: collapse; text-align: left; }

.premium-table thead { background: rgba(0,0,0,0.02); }
:root:not(.light) .premium-table thead { background: rgba(255,255,255,0.02); }

.th-inner { padding: 16px 24px; font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.8px; display: flex; align-items: center; gap: 6px; }

.table-row { transition: all 0.2s; }
.table-row:hover { background: var(--bg-primary); }
.table-row:not(:last-child) { border-bottom: 1px solid var(--border-color); }
.premium-table td { padding: 14px 24px; }

.invoice-badge {
  font-family: 'Courier New', Courier, monospace;
  font-weight: 600; color: var(--text-primary); font-size: 13px;
  background: var(--bg-secondary); padding: 4px 10px; border-radius: 8px;
  border: none; outline: none; box-shadow: none;
}
.invoice-badge:hover {
  border: none; outline: none; box-shadow: none;
}

.cashier-wrap { display: flex; align-items: center; gap: 10px; font-size: 13px; font-weight: 600; }

.type-badge {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 4px 10px; border-radius: 100px; font-size: 11px; font-weight: 700;
  background: var(--bg-primary); color: var(--text-secondary); border: 1px solid var(--border-color);
}
.type-badge.dine_in { color: #8b5cf6; border-color: rgba(139, 92, 246, 0.2); background: rgba(139, 92, 246, 0.05); }

.payment-tag { font-size: 11px; font-weight: 600; color: var(--text-muted); padding: 2px 8px; background: var(--bg-primary); border-radius: 4px; }

.total-amount-val { font-size: 14px; font-weight: 600; color: var(--accent); }

.status-pill {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700;
  background: var(--bg-primary); color: var(--text-muted);
}
.status-pill.completed { background: var(--success-bg); color: var(--success); }
.status-pill.pending { background: var(--warning-bg); color: var(--warning); }
.status-pill.cancelled { background: var(--danger-bg); color: var(--danger); }
.dot-status { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }

.time-wrap { font-size: 12px; }
.date-val { font-weight: 700; color: var(--text-primary); }
.hour-val { color: var(--text-muted); font-size: 11px; }

.btn-detail {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 6px 12px; border-radius: 8px; border: 1px solid var(--border-color);
  background: transparent; color: var(--text-secondary); font-size: 12px; font-weight: 700;
  cursor: pointer; transition: all 0.2s;
}
.btn-detail:hover { border-color: var(--accent); color: var(--accent); background: var(--accent-light); }

/* Pagination */
.pagination-area {
  display: flex; align-items: center; justify-content: space-between;
  padding: 20px 24px; background: rgba(0,0,0,0.01); border-top: 1px solid var(--border-color);
}
.pagin-info { font-size: 13px; color: var(--text-muted); }
.pagin-btns { display: flex; gap: 6px; }
.pagin-btn, .pagin-btn-num {
  width: 36px; height: 36px; border-radius: 10px;
  border: 1px solid var(--border-color); background: var(--bg-card);
  color: var(--text-secondary); cursor: pointer; transition: all 0.2s;
  display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px;
}
.pagin-btn:hover:not(:disabled), .pagin-btn-num:hover { border-color: var(--accent); color: var(--accent); }
.pagin-btn-num.active { background: var(--accent); color: #fff; border-color: var(--accent); }
.pagin-btn:disabled { opacity: 0.3; cursor: not-allowed; }

/* ── Modal ── */
.modal-backdrop {
  position: fixed; inset: 0; z-index: 200;
  background: rgba(0,0,0,0.6); backdrop-filter: blur(6px);
  display: flex; align-items: center; justify-content: center; padding: 20px;
}
.modal-panel-large {
  width: 100%; max-width: 820px; background: var(--bg-card);
  border-radius: 28px; border: 1px solid var(--border-color);
  box-shadow: 0 25px 50px -12px rgba(0,0,0,0.3); overflow: hidden;
  animation: modalScale 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}
@keyframes modalScale { from { transform: scale(0.95); opacity: 0; } to { transform: scale(1); opacity: 1; } }

.modal-top { display: flex; align-items: center; justify-content: space-between; padding: 24px 28px; border-bottom: 1px solid var(--border-color); }
.modal-top-left { display: flex; align-items: center; gap: 14px; }
.invoice-icon { width: 44px; height: 44px; border-radius: 12px; background: var(--accent-light); color: var(--accent); display: flex; align-items: center; justify-content: center; }
.modal-title { font-size: 17px; font-weight: 600; color: var(--text-primary); margin-bottom: 2px; }
.modal-id { font-size: 13px; color: var(--text-muted); font-weight: 600; }

.modal-top-right { display: flex; align-items: center; gap: 20px; }
.status-manage-wrap { display: flex; align-items: center; gap: 10px; }
.status-label-mini { font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; }

.modal-close {
  width: 36px; height: 36px; border-radius: 10px;
  background: var(--bg-primary); border: 1px solid var(--border-color);
  color: var(--text-secondary); cursor: pointer; transition: all 0.2s;
  display: flex; align-items: center; justify-content: center;
}
.modal-close:hover { 
  background: var(--danger-bg); 
  border-color: var(--danger); 
  color: var(--danger);
}

.status-select-inline {
  padding: 6px 12px; border-radius: 8px; border: 1px solid var(--border-color);
  font-size: 12px; font-weight: 700; cursor: pointer; outline: none; transition: 0.2s;
}
.status-select-inline.completed { background: var(--success-bg); color: var(--success); border-color: rgba(34, 197, 94, 0.2); }
.status-select-inline.pending { background: var(--warning-bg); color: var(--warning); border-color: rgba(234, 179, 8, 0.2); }
.status-select-inline.cancelled { background: var(--danger-bg); color: var(--danger); border-color: rgba(239, 68, 68, 0.2); }

.modal-content-grid { display: grid; grid-template-columns: 300px 1fr; border-top: 1px solid var(--border-color); }

.modal-left-col { padding: 28px; background: rgba(0,0,0,0.01); border-right: 1px solid var(--border-color); }
.info-card-premium { display: flex; flex-direction: column; gap: 24px; }
.info-title { font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 12px; }
.info-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
.info-label { font-size: 13px; color: var(--text-muted); font-weight: 500; }
.info-val { font-size: 13px; color: var(--text-primary); font-weight: 700; }
.info-val-badge { font-size: 11px; font-weight: 600; color: #fff; background: var(--accent); padding: 2px 8px; border-radius: 4px; }
.info-divider { height: 1px; background: var(--border-color); }

.print-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 32px; }
.btn-print-outline {
  display: flex; align-items: center; justify-content: center; gap: 8px;
  padding: 10px; border-radius: 12px; border: 1px solid var(--border-color);
  background: var(--bg-primary); color: var(--text-secondary); font-size: 12px; font-weight: 700; cursor: pointer;
}
.btn-print-outline:hover { border-color: var(--accent); color: var(--accent); }

.modal-right-col { padding: 28px; display: flex; flex-direction: column; }
.items-title { display: flex; align-items: center; gap: 10px; font-size: 15px; font-weight: 600; margin-bottom: 20px; }
.badge-count { font-size: 11px; background: var(--accent-light); color: var(--accent); padding: 2px 8px; border-radius: 100px; }

.items-scroll { flex: 1; max-height: 280px; overflow-y: auto; padding-right: 10px; margin-bottom: 24px; }
.receipt-item { display: flex; align-items: center; gap: 12px; padding: 12px 0; border-bottom: 1px solid var(--border-color); }
.ri-img { width: 40px; height: 40px; border-radius: 8px; background: var(--bg-primary); display: flex; align-items: center; justify-content: center; color: var(--text-muted); overflow: hidden; flex-shrink: 0; }
.ri-img img { width: 100%; height: 100%; object-fit: cover; }
.ri-info { flex: 1; }
.ri-name { font-size: 13px; font-weight: 700; color: var(--text-primary); margin-bottom: 2px; }
.ri-meta { font-size: 12px; color: var(--text-muted); display: flex; gap: 8px; }
.ri-price { color: var(--accent); font-weight: 600; }
.ri-total { font-size: 14px; font-weight: 600; color: var(--text-primary); }

.modal-totals { background: var(--bg-primary); padding: 20px; border-radius: 16px; border: 1px solid var(--border-color); }
.m-total-row { display: flex; justify-content: space-between; font-size: 14px; font-weight: 600; margin-bottom: 8px; }
.m-total-row.grand { margin-top: 12px; padding-top: 12px; border-top: 2px dashed var(--border-color); }
.m-total-row .val { color: var(--accent); font-size: 20px; font-weight: 700; }
.m-total-row .minus { color: var(--danger); }

/* States */
.table-loading { padding: 80px; text-align: center; display: flex; flex-direction: column; align-items: center; gap: 16px; color: var(--text-muted); }
.loading-spinner { width: 32px; height: 32px; border: 3px solid var(--accent-light); border-top-color: var(--accent); border-radius: 50%; animation: spin 0.8s linear infinite; }
.table-empty { text-align: center; padding: 60px 20px; }
.empty-illu { color: var(--text-muted); opacity: 0.2; margin-bottom: 16px; }

@keyframes spin { to { transform: rotate(360deg); } }
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

/* ── Responsive ── */
@media (max-width: 1200px) {
  .filter-row { 
    display: grid; 
    grid-template-columns: repeat(3, 1fr); 
    gap: 12px;
  }
  .filter-actions-wrap { grid-column: span 3; display: flex; justify-content: flex-end; margin-top: 8px; }
}

@media (max-width: 1024px) {
  .filter-row { grid-template-columns: repeat(2, 1fr); }
  .filter-actions-wrap { grid-column: span 2; }
  .th-inner, .premium-table td { padding: 12px 14px; }
}

@media (max-width: 768px) {
  .orders-container { padding: 4px; }
  .page-hero { padding: 16px; flex-direction: column; align-items: flex-start; text-align: left; gap: 12px; }
  .hero-icon-wrap { width: 36px; height: 36px; border-radius: 10px; }
  .hero-icon-wrap svg { width: 18px; height: 18px; }
  .hero-title { font-size: 16px; }
  .hero-subtitle { font-size: 11px; }

  .filter-glass-bar { padding: 12px; }
  .filter-grid { gap: 10px; }
  .filter-row { grid-template-columns: 1fr 1fr; }
  .filter-actions-wrap { 
    grid-column: span 2; 
    display: grid; 
    grid-template-columns: 1fr 44px; 
    gap: 8px; 
    margin-top: 4px;
  }
  .btn-apply { width: 100%; justify-content: center; height: 40px; }
  .btn-reset-icon { width: 44px; height: 40px; }
  
  .table-outer-card { border-radius: 16px; }
  
  /* Card Layout untuk Mobile */
  .table-scroll-wrap { padding: 12px; }
  .premium-table thead { display: none; }
  .premium-table tbody { display: flex; flex-direction: column; gap: 12px; }
  
  .table-row { 
    display: flex !important; 
    flex-direction: column; 
    background: var(--bg-primary); 
    border: 1px solid var(--border-color); 
    border-radius: 16px; 
    padding: 16px; 
    gap: 12px;
  }
  .table-row:hover { background: var(--bg-secondary); }
  
  .premium-table td { 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    padding: 0; 
    border: none;
  }
  
  .premium-table td::before {
    content: attr(data-label);
    font-size: 11px;
    font-weight: 600;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }
  
  .premium-table td:last-child { justify-content: flex-end; }
  .premium-table td:last-child::before { display: none; }

  .pagination-area { padding: 16px; flex-direction: column; gap: 12px; }
  .pagin-btns { width: 100%; justify-content: center; }
  .pagin-btn, .pagin-btn-num { width: 32px; height: 32px; font-size: 12px; }

  /* Modal Mobile */
  .modal-backdrop { padding: 0; align-items: flex-end; background: rgba(0,0,0,0.35); backdrop-filter: blur(2px); }
  .modal-panel-large { 
    max-width: 100%; 
    border-radius: 20px 20px 0 0; 
    animation: slideUp 0.3s ease-out;
    max-height: 94vh;
    display: flex;
    flex-direction: column;
  }
  .modal-top { padding: 16px 20px; }
  .modal-top-right { gap: 10px; }
  .status-manage-wrap { display: none; } 
  
  .modal-content-grid { grid-template-columns: 1fr; overflow-y: auto; flex: 1; }
  .modal-left-col { padding: 20px; border-right: none; border-bottom: 1px solid var(--border-color); order: 2; }
  .modal-right-col { padding: 20px; order: 1; }
  
  .receipt-item { gap: 10px; padding: 10px 0; }
  .ri-img { width: 32px; height: 32px; }
  .ri-name { font-size: 12px; }
  .ri-total { font-size: 13px; }
  .modal-totals { padding: 16px; }
  .m-total-row .val { font-size: 18px; }
}

@media (max-width: 480px) {
  .hero-subtitle { display: none; }
  .pagin-info { display: none; }
}

@keyframes slideUp {
  from { transform: translateY(100%); }
  to { transform: translateY(0); }
}
</style>
