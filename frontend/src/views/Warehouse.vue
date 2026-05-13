<template>
  <div class="warehouse-container">
    <!-- Header Hero Section -->
    <div class="page-hero">
      <div class="hero-content">
        <div class="hero-icon-wrap">
          <Package :size="24" />
        </div>
        <div>
          <h1 class="hero-title">Manajemen Stok Gudang</h1>
          <p class="hero-subtitle">Kontrol inventori bahan baku, pantau tingkat stok, dan catat riwayat keluar masuk barang.</p>
        </div>
      </div>
      <div class="hero-actions">
        <button class="btn-primary" @click="openItemModal()">
          <Plus :size="18" /> Tambah Bahan Baru
        </button>
      </div>
    </div>

    <!-- Filter Bar -->
    <div class="filter-glass-bar">
      <div class="search-box">
        <Search :size="18" class="search-icon" />
        <input 
          type="text" 
          v-model="whStore.filters.search" 
          placeholder="Cari nama bahan baku..." 
          @keyup.enter="refresh"
        >
      </div>
      <div class="filter-group">
        <select v-model="whStore.filters.category_id" @change="refresh" class="filter-select">
          <option value="">Semua Kategori</option>
          <option v-for="cat in whStore.categories" :key="cat.id" :value="cat.id">
            {{ cat.name }}
          </option>
        </select>
        <button class="btn-refresh" @click="reset" title="Reset Filter">
          <RotateCcw :size="16" />
        </button>
      </div>
      <div class="filter-toggle-wrap">
        <label class="toggle-pill" :class="{ active: whStore.filters.low_stock }">
          <input type="checkbox" v-model="whStore.filters.low_stock" @change="refresh" hidden>
          <AlertTriangle :size="14" /> Stok Rendah
        </label>
      </div>
    </div>

    <!-- Warehouse Data Table -->
    <div class="table-outer-card">
      <div v-if="whStore.loading && !whStore.items.length" class="table-loading">
        <div class="loading-spinner"></div>
        <span>Memuat data inventori...</span>
      </div>

      <div v-else-if="!whStore.items.length" class="table-empty">
        <div class="empty-illu">
          <PackageOpen :size="48" />
        </div>
        <h3>Gudang Kosong</h3>
        <p>Belum ada bahan baku yang terdaftar di sistem.</p>
        <button class="btn-secondary" @click="openItemModal()">Tambah Bahan Pertama</button>
      </div>

      <div v-else class="table-scroll-wrap">
        <table class="premium-table">
          <thead>
            <tr>
              <th width="300"><div class="th-inner">Bahan Baku</div></th>
              <th><div class="th-inner">Kategori</div></th>
              <th class="text-right"><div class="th-inner justify-end">Stok Saat Ini</div></th>
              <th class="text-right"><div class="th-inner justify-end">Harga / Unit</div></th>
              <th class="text-right"><div class="th-inner justify-end">Total Nilai</div></th>
              <th class="text-center"><div class="th-inner justify-center">Status</div></th>
              <th class="text-center"><div class="th-inner justify-center">Kelola Stok</div></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(item, idx) in whStore.items" :key="item.id" class="table-row" :style="{ animationDelay: (idx * 0.05) + 's' }">
              <td data-label="Bahan Baku">
                <div class="item-info-wrap">
                  <div class="item-icon-box">
                    <Box :size="18" />
                  </div>
                  <div class="item-text">
                    <span class="i-name">{{ item.name }}</span>
                    <span class="i-unit">Basis: {{ item.unit }}</span>
                  </div>
                </div>
              </td>
              <td data-label="Kategori">
                <div class="cat-badge">{{ item.category ? item.category.name : 'Uncategorized' }}</div>
              </td>
              <td data-label="Stok Saat Ini" class="text-right">
                <div class="stock-val-wrap" :class="{ low: Number(item.stock) <= Number(item.min_stock) }">
                  <span class="s-main">{{ formatDecimal(item.stock) }}</span>
                  <span class="s-unit">{{ item.unit }}</span>
                </div>
              </td>
              <td data-label="Harga / Unit" class="text-right">
                <span class="price-val">{{ formatCurrency(item.price_per_unit) }}</span>
              </td>
              <td data-label="Total Nilai" class="text-right">
                <span class="total-val">{{ formatCurrency(item.stock * item.price_per_unit) }}</span>
              </td>
              <td data-label="Status" class="text-center">
                <div class="status-pill-premium" :class="Number(item.stock) <= Number(item.min_stock) ? 'danger' : 'success'">
                  <div class="s-dot"></div>
                  {{ Number(item.stock) <= Number(item.min_stock) ? 'KRITIS' : 'AMAN' }}
                </div>
              </td>
              <td data-label="Aksi" class="text-center">
                <div class="action-btn-group">
                  <button class="btn-stock add" @click="openStockModal(item, 'add')" title="Tambah Stok">
                    <Plus :size="14" />
                  </button>
                  <button class="btn-stock reduce" @click="openStockModal(item, 'reduce')" title="Kurangi Stok">
                    <Minus :size="14" />
                  </button>
                  <button class="btn-stock edit" @click="openItemModal(item)" title="Edit Detail">
                    <Edit3 :size="14" />
                  </button>
                  <button class="btn-stock delete" @click="handleDeleteItem(item)" title="Hapus Bahan">
                    <Trash2 :size="14" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="pagination-area" v-if="whStore.pagination.last_page > 1">
        <div class="pagin-info">
          Menampilkan <strong>{{ whStore.items.length }}</strong> bahan dari total pencarian
        </div>
        <div class="pagin-btns">
          <button 
            class="pagin-btn"
            :disabled="whStore.pagination.current_page === 1" 
            @click="whStore.fetchItems(whStore.pagination.current_page - 1)"
          >
            <ChevronLeft :size="18" />
          </button>
          <span class="pagin-current">{{ whStore.pagination.current_page }} / {{ whStore.pagination.last_page }}</span>
          <button 
            class="pagin-btn"
            :disabled="whStore.pagination.current_page === whStore.pagination.last_page" 
            @click="whStore.fetchItems(whStore.pagination.current_page + 1)"
          >
            <ChevronRight :size="18" />
          </button>
        </div>
      </div>
    </div>

    <!-- Stock Adjustment Modal -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="stockModal.show" class="modal-backdrop" @click.self="stockModal.show = false">
          <div class="modal-panel-stock">
            <div class="modal-top">
              <div class="modal-header-content">
                <button class="btn-back-header" @click="stockModal.show = false">
                  <ArrowLeft :size="20" />
                </button>
                <div class="modal-icon-wrap" :class="stockModal.type">
                  <TrendingUp v-if="stockModal.type === 'add'" :size="20" />
                  <TrendingDown v-else :size="20" />
                </div>
                <div class="modal-title-area">
                  <h3 class="modal-title">Stok {{ stockModal.type === 'add' ? 'Masuk' : 'Keluar' }}</h3>
                  <p class="modal-desc">
                    {{ stockModal.item?.name }} - {{ stockModal.type === 'add' ? 'Tambah persediaan' : 'Kurangi persediaan' }}
                  </p>
                </div>
              </div>
            </div>

            <div class="modal-content">
              <div class="form-grid">
                <div class="input-group">
                  <label class="input-label">Jumlah ({{ stockModal.item.unit }})</label>
                  <div class="input-with-side">
                    <Hash :size="16" class="side-icon" />
                    <input type="number" v-model="stockModal.quantity" step="0.01" class="premium-input pl-10" placeholder="0.00">
                  </div>
                </div>

                <!-- Pro Features: Supplier & Price -->
                <div v-if="stockModal.type === 'add'" class="pro-features-area mt-4">
                  <div class="pro-tag-row">
                    <span class="pro-label">FITUR PRO</span>
                    <span v-if="!isPro" class="pro-locked-badge">LOCKED</span>
                  </div>
                  
                  <div class="form-grid-2">
                    <div class="input-group">
                      <label class="input-label">Supplier Pemberian</label>
                      <select v-model="stockModal.supplier_id" class="premium-input" :disabled="!isPro">
                        <option value="">Pilih Supplier</option>
                        <option v-for="sup in supplierStore.suppliers" :key="sup.id" :value="sup.id">
                          {{ sup.name }}
                        </option>
                      </select>
                    </div>
                    <div class="input-group">
                      <label class="input-label">Harga Beli / Unit</label>
                      <div class="input-with-side">
                        <span class="side-text">Rp</span>
                        <input type="number" v-model="stockModal.purchase_price" class="premium-input pl-12" placeholder="0" :disabled="!isPro">
                      </div>
                    </div>
                  </div>
                  <p v-if="!isPro" class="pro-hint">Upgrade ke Paket PRO untuk mengaktifkan pelacakan harga beli dan riwayat supplier.</p>
                </div>

                <div class="input-group mt-4">
                  <label class="input-label">Catatan Transaksi</label>
                  <textarea v-model="stockModal.notes" class="premium-input text-area" rows="3" placeholder="Contoh: Belanja dari Supplier ABC, Barang rusak, dll..."></textarea>
                </div>
              </div>
            </div>

            <div class="modal-bottom">
              <button :class="['btn-save-stock', stockModal.type]" @click="handleStockAdjustment" :disabled="stockModal.quantity <= 0">
                <Check :size="18" />
                Konfirmasi {{ stockModal.type === 'add' ? 'Stok Masuk' : 'Stok Keluar' }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
    
    <!-- Item Add/Edit Modal -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="itemModal.show" class="modal-backdrop" @click.self="itemModal.show = false">
          <div class="modal-panel-stock">
            <div class="modal-top">
              <div class="modal-header-content">
                <button class="btn-back-header" @click="itemModal.show = false">
                  <ArrowLeft :size="20" />
                </button>
                <div class="modal-icon-wrap" style="background: var(--accent-light); color: var(--accent);">
                  <Box :size="20" v-if="itemModal.mode === 'add'" />
                  <Edit3 :size="20" v-else />
                </div>
                <div class="modal-title-area">
                  <h3 class="modal-title">{{ itemModal.mode === 'add' ? 'Tambah Bahan Baru' : 'Edit Detail Bahan' }}</h3>
                  <p class="modal-desc">Atur informasi dasar, kategori, dan kebijakan stok minimum.</p>
                </div>
              </div>
            </div>
            
            <div class="modal-content">
              <div class="form-grid-2">
                <div class="input-group">
                  <label class="input-label">Nama Bahan Baku</label>
                  <input type="text" v-model="itemModal.form.name" class="premium-input" placeholder="E.g. Beras Pandan Wangi">
                </div>
                <div class="input-group">
                  <label class="input-label">Kategori</label>
                  <select v-model="itemModal.form.category_id" class="premium-input">
                    <option value="" disabled>Pilih Kategori</option>
                    <option v-for="cat in whStore.categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                  </select>
                </div>
              </div>
              
              <div class="form-grid-2 mt-4">
                <div class="input-group">
                  <label class="input-label">Satuan (Unit)</label>
                  <select v-model="itemModal.form.unit" class="premium-input">
                    <option value="" disabled>Pilih Satuan</option>
                    <option v-for="u in unitStore.units" :key="u.id" :value="u.abbreviation">
                      {{ u.name }} ({{ u.abbreviation }})
                    </option>
                  </select>
                </div>
                <div class="input-group">
                  <label class="input-label">Min. Stok (Peringatan)</label>
                  <input type="number" v-model="itemModal.form.min_stock" class="premium-input" placeholder="0">
                </div>
              </div>

              <div class="form-grid-2 mt-4">
                <div class="input-group">
                  <label class="input-label">Harga per Unit</label>
                  <div class="input-with-side">
                    <span class="side-text">Rp</span>
                    <input type="number" v-model="itemModal.form.price_per_unit" class="premium-input pl-12" placeholder="0">
                  </div>
                </div>
                <div class="input-group">
                  <label class="input-label">Default Supplier (Pro)</label>
                  <select v-model="itemModal.form.default_supplier_id" class="premium-input" :disabled="!isPro">
                    <option value="">Pilih Default Supplier</option>
                    <option v-for="sup in supplierStore.suppliers" :key="sup.id" :value="sup.id">{{ sup.name }}</option>
                  </select>
                </div>
              </div>
              <div v-if="itemModal.mode === 'add'" class="form-grid-2 mt-4">
                <div class="input-group">
                  <label class="input-label">Stok Awal</label>
                  <input type="number" v-model="itemModal.form.stock" class="premium-input" placeholder="0">
                </div>
              </div>
              
              <div class="input-group mt-4">
                <label class="input-label">Catatan Tambahan</label>
                <textarea v-model="itemModal.form.notes" class="premium-input text-area" rows="2" placeholder="Informasi tambahan..."></textarea>
              </div>
            </div>
            
            <div class="modal-bottom">
              <button class="btn-save-primary" @click="handleItemAction" :disabled="whStore.loading">
                <RefreshCw v-if="whStore.loading" :size="18" class="spinner" />
                <Save v-else :size="18" />
                {{ itemModal.mode === 'add' ? 'Simpan Bahan' : 'Update Bahan' }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { onMounted, reactive, watch, onUnmounted, computed } from 'vue';
import { useWarehouseStore } from '../stores/warehouse';
import { useUnitStore } from '../stores/unit';
import { showSuccess, showError, showConfirm } from '../utils/swal';
import { 
  Plus, Minus, Edit3, ArrowLeft, Search, Package, Filter as FilterIcon, 
  RotateCcw, AlertTriangle, PackageOpen, Box, ChevronLeft, 
  ChevronRight, TrendingUp, TrendingDown, Hash, Check, Save, RefreshCw, Trash2,
  Truck, DollarSign
} from 'lucide-vue-next';
import { useSupplierStore } from '../stores/supplier';
import { useAuthStore } from '../stores/auth';

const whStore = useWarehouseStore();
const unitStore = useUnitStore();
const supplierStore = useSupplierStore();
const authStore = useAuthStore();

const isPro = computed(() => {
  const plan = authStore.user?.tenant?.plan || 'free';
  return plan === 'pro' || authStore.user?.role === 'superadmin';
});

const stockModal = reactive({
  show: false,
  type: 'add',
  item: null,
  quantity: 0,
  purchase_price: 0,
  supplier_id: '',
  notes: ''
});

const itemModal = reactive({
  show: false,
  mode: 'add',
  id: null,
  form: {
    name: '',
    category_id: '',
    unit: '',
    price_per_unit: 0,
    min_stock: 0,
    default_supplier_id: '',
    stock: 0,
    notes: ''
  }
});

onMounted(() => {
  whStore.fetchItems();
  whStore.fetchCategories();
  unitStore.fetchUnits();
  if (isPro.value) {
    supplierStore.fetchSuppliers();
  }
});

const refresh = () => whStore.fetchItems(1);
const reset = () => {
  whStore.resetFilters();
  refresh();
};

const openStockModal = (item, type) => {
  stockModal.item = item;
  stockModal.type = type;
  stockModal.quantity = 0;
  stockModal.purchase_price = item.price_per_unit || 0;
  stockModal.supplier_id = item.default_supplier_id || '';
  stockModal.notes = '';
  stockModal.show = true;
};

const openItemModal = (item = null) => {
  if (item) {
    itemModal.mode = 'edit';
    itemModal.id = item.id;
    itemModal.form = {
      name: item.name,
      category_id: item.category_id,
      unit: item.unit,
      price_per_unit: item.price_per_unit,
      min_stock: item.min_stock,
      default_supplier_id: item.default_supplier_id || '',
      stock: item.stock,
      notes: item.notes
    };
  } else {
    itemModal.mode = 'add';
    itemModal.id = null;
    itemModal.form = {
      name: '',
      category_id: '',
      unit: '',
      price_per_unit: 0,
      min_stock: 0,
      default_supplier_id: '',
      stock: 0,
      notes: ''
    };
  }
  itemModal.show = true;
};

const handleItemAction = async () => {
  let success = false;
  if (itemModal.mode === 'add') {
    success = await whStore.addItem(itemModal.form);
  } else {
    success = await whStore.updateItem(itemModal.id, itemModal.form);
  }
  
  if (success) {
    itemModal.show = false;
    refresh();
    showSuccess(itemModal.mode === 'add' ? 'Bahan baku berhasil ditambahkan!' : 'Bahan baku berhasil diperbarui!');
  } else {
    showError(whStore.error || 'Gagal menyimpan data');
  }
};

const handleStockAdjustment = async () => {
  const data = {
    quantity: stockModal.quantity,
    purchase_price: stockModal.type === 'add' ? stockModal.purchase_price : null,
    supplier_id: stockModal.type === 'add' ? stockModal.supplier_id : null,
    notes: stockModal.notes
  };

  let success = false;
  if (stockModal.type === 'add') {
    success = await whStore.addStock(stockModal.item.id, data);
  } else {
    success = await whStore.reduceStock(stockModal.item.id, data);
  }

  if (success) {
    stockModal.show = false;
    refresh();
    showSuccess(stockModal.type === 'add' ? 'Stok berhasil ditambahkan!' : 'Stok berhasil dikurangi!');
  } else {
    showError(whStore.error || 'Gagal mengupdate stok');
  }
};

const handleDeleteItem = async (item) => {
  const result = await showConfirm({
    title: 'Hapus Bahan Baku',
    text: `Hapus "${item.name}" dari gudang?`,
    icon: 'warning',
    confirmText: 'Ya, Hapus',
    cancelText: 'Batal'
  });
  
  if (result.isConfirmed) {
    const success = await whStore.deleteItem(item.id);
    if (success) {
      refresh();
      showSuccess('Bahan baku berhasil dihapus!');
    } else {
      showError(whStore.error || 'Gagal menghapus bahan baku');
    }
  }
};

const formatCurrency = (value) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
  }).format(value);
};

const formatDecimal = (value) => {
  return Number(value).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
};

// Toggle mobile bottom nav visibility and body scroll
watch(() => stockModal.show, (val) => {
  if (val) {
    document.body.classList.add('hide-mobile-nav');
    document.body.style.overflow = 'hidden';
  } else {
    document.body.classList.remove('hide-mobile-nav');
    document.body.style.overflow = '';
  }
});

watch(() => itemModal.show, (val) => {
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
</script>

<style scoped>
.warehouse-container { padding: 0; animation: fadeIn 0.4s ease; }

/* ── Hero ── */
.page-hero {
  display: flex; align-items: center; justify-content: space-between;
  background: linear-gradient(135deg, rgba(34,197,94,0.08) 0%, rgba(34,197,94,0.02) 100%);
  border: 1px solid rgba(34,197,94,0.1); border-radius: 20px;
  padding: 20px 24px; margin-bottom: 12px;
}
.hero-content { display: flex; align-items: center; gap: 18px; }
.hero-icon-wrap {
  width: 48px; height: 48px; border-radius: 14px;
  background: linear-gradient(135deg, #22c55e, #4ade80);
  display: flex; align-items: center; justify-content: center;
  color: #fff; box-shadow: 0 8px 24px rgba(34, 197, 94, 0.25);
}
.hero-title { font-size: 20px; font-weight: 600; color: var(--text-primary); margin-bottom: 2px; }
.hero-subtitle { font-size: 13px; color: var(--text-secondary); font-weight: 500; }

.btn-primary {
  display: flex; align-items: center; gap: 10px;
  background: linear-gradient(135deg, var(--accent), #fb923c);
  color: #fff; border: none; padding: 12px 24px; border-radius: 12px;
  font-size: 14px; font-weight: 700; cursor: pointer;
}

/* ── Filter Bar ── */
.filter-glass-bar {
  display: flex; align-items: center; gap: 16px;
  background: var(--bg-card); border: 1px solid var(--border-color);
  padding: 12px 16px; border-radius: 18px; margin-bottom: 12px;
}
.search-box { flex: 1; position: relative; }
.search-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-muted); }
.search-box input {
  width: 100%; height: 44px; padding-left: 44px;
  background: var(--bg-primary); border: 1px solid var(--border-color);
  border-radius: 14px; font-size: 14px; color: var(--text-primary); outline: none;
}
.filter-group { display: flex; align-items: center; gap: 10px; }
.input-label { 
  font-size: 11px; 
  font-weight: 700; 
  color: var(--text-muted); 
  text-transform: uppercase; 
  letter-spacing: 0.6px;
  margin-bottom: 4px;
}
.premium-input {
  width: 100%; padding: 10px 14px; border-radius: 12px;
  background: var(--bg-primary); border: 1px solid var(--border-color);
  color: var(--text-primary); font-size: 13.5px; font-weight: 500; outline: none;
}
.filter-toggle-wrap { display: flex; align-items: center; }
.toggle-pill {
  display: flex; align-items: center; gap: 8px; padding: 10px 16px;
  border-radius: 12px; border: 1.5px solid var(--border-color);
  font-size: 12px; font-weight: 600; cursor: pointer; transition: 0.2s;
}
.toggle-pill:hover { background: var(--bg-primary); }
.toggle-pill.active { background: var(--danger-bg); border-color: var(--danger); color: var(--danger); }

.btn-refresh {
  width: 44px; height: 44px; border-radius: 14px; background: var(--bg-primary);
  border: 1px solid var(--border-color); color: var(--text-secondary); cursor: pointer;
}

/* ── Table ── */
.table-outer-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 24px; overflow: hidden; }
.table-scroll-wrap { overflow-x: auto; }
.premium-table { width: 100%; border-collapse: collapse; text-align: left; }
.th-inner { padding: 16px 24px; font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.8px; display: flex; align-items: center; gap: 6px; }
.table-row { border-bottom: 1px solid var(--border-color); transition: 0.2s; animation: slideUp 0.4s ease both; }
.table-row:hover { background: var(--bg-primary); }
.premium-table td { padding: 14px 24px; vertical-align: middle; }

.item-info-wrap { display: flex; align-items: center; gap: 14px; }
.item-icon-box {
  width: 38px; height: 38px; border-radius: 10px; background: var(--bg-primary);
  display: flex; align-items: center; justify-content: center; color: var(--text-secondary);
}
.i-name { display: block; font-size: 14px; font-weight: 700; color: var(--text-primary); margin-bottom: 2px; }
.i-unit { display: block; font-size: 10px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; }

.cat-badge { display: inline-block; padding: 4px 10px; background: var(--bg-primary); border-radius: 8px; font-size: 11px; font-weight: 700; color: var(--text-secondary); border: 1px solid var(--border-color); }

.stock-val-wrap { display: flex; flex-direction: column; align-items: flex-end; }
.stock-val-wrap .s-main { font-size: 16px; font-weight: 600; color: var(--text-primary); }
.stock-val-wrap .s-unit { font-size: 10px; font-weight: 700; color: var(--text-muted); }
.stock-val-wrap.low .s-main { color: var(--danger); font-size: 18px; }

.price-val, .total-val { font-size: 14px; font-weight: 700; color: var(--text-secondary); }
.total-val { font-weight: 600; color: var(--text-primary); }

.status-pill-premium {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 4px 12px; border-radius: 100px; font-size: 10px; font-weight: 700;
}
.status-pill-premium.success { background: var(--success-bg); color: var(--success); }
.status-pill-premium.danger { background: var(--danger-bg); color: var(--danger); }
.s-dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }

.action-btn-group { display: flex; justify-content: center; gap: 6px; }
.btn-stock {
  width: 32px; height: 32px; border-radius: 10px; border: 1px solid var(--border-color);
  background: var(--bg-primary); cursor: pointer; transition: 0.2s;
  display: flex; align-items: center; justify-content: center;
}
.btn-stock.add { color: var(--success); }
.btn-stock.add:hover { background: var(--success); color: #fff; border-color: var(--success); }
.btn-stock.reduce { color: var(--danger); }
.btn-stock.reduce:hover { background: var(--danger); color: #fff; border-color: var(--danger); }
.btn-stock.delete:hover { background: var(--danger); color: #fff; border-color: var(--danger); }
.btn-stock.edit:hover { background: var(--bg-card-hover); color: var(--text-primary); }

.form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.side-text { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 13px; font-weight: 700; }
.pl-12 { padding-left: 48px !important; }

.btn-save-primary {
  display: flex; align-items: center; justify-content: center; gap: 10px; padding: 12px 28px; border-radius: 14px; border: none;
  background: linear-gradient(135deg, var(--accent), #fb923c);
  color: #fff; font-weight: 700; cursor: pointer;
  box-shadow: 0 10px 15px -3px rgba(249, 115, 22, 0.2);
}
.btn-save-primary:disabled { opacity: 0.5; cursor: not-allowed; }

.spinner { animation: spin 0.8s linear infinite; }

/* Pagination */
.pagination-area { padding: 20px 24px; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border-color); }
.pagin-info { font-size: 12px; color: var(--text-muted); font-weight: 600; }
.pagin-btns { display: flex; align-items: center; gap: 12px; }
.pagin-btn { width: 36px; height: 36px; border-radius: 10px; border: 1px solid var(--border-color); background: var(--bg-card); cursor: pointer; }
.pagin-current { font-size: 13px; font-weight: 600; color: var(--text-primary); }

/* Pro Features Styling */
.pro-features-area {
  padding: 16px;
  background: rgba(249, 115, 22, 0.03);
  border: 1px dashed rgba(249, 115, 22, 0.2);
  border-radius: 16px;
}

.pro-tag-row {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 12px;
}

.pro-label {
  font-size: 10px;
  font-weight: 800;
  color: #f97316;
  letter-spacing: 1px;
}

.pro-locked-badge {
  background: #f1f5f9;
  color: #64748b;
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 9px;
  font-weight: 800;
}

.pro-hint {
  font-size: 11px;
  color: #94a3b8;
  margin-top: 10px;
  line-height: 1.4;
}

/* ── Modal ── */
.modal-backdrop {
  position: fixed; inset: 0; z-index: 9999;
  background: rgba(0,0,0,0.4); backdrop-filter: blur(8px);
  display: flex; align-items: center; justify-content: center; padding: 20px;
}
.modal-panel-stock { 
  width: 100%; max-width: 520px; 
  background: var(--bg-card); 
  border-radius: 24px; 
  border: 1px solid var(--border-color); 
  box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); 
  overflow: hidden;
  animation: modalScale 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.modal-top { display: flex; flex-direction: column; gap: 16px; padding: 24px; border-bottom: 1px solid var(--border-color); }
.modal-header-content { display: flex; align-items: center; gap: 16px; width: 100%; }
.modal-icon-wrap {
  width: 44px; height: 44px; border-radius: 12px;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.modal-icon-wrap.add { background: var(--success-bg); color: var(--success); }
.modal-icon-wrap.reduce { background: var(--danger-bg); color: var(--danger); }
.modal-title-area { flex: 1; }
.modal-title { font-size: 18px; font-weight: 600; color: var(--text-primary); }
.modal-desc { font-size: 13px; color: var(--text-muted); }
.btn-back-header {
  width: 36px; height: 36px; border-radius: 10px; border: none; background: var(--bg-primary);
  color: var(--text-muted); cursor: pointer; display: flex; align-items: center; justify-content: center;
  transition: all 0.2s; position: relative; z-index: 10;
}
.btn-back-header:hover {
  background-color: var(--bg-card-hover);
  color: var(--text-primary);
}

.modal-content { padding: 28px; }
.modal-bottom { 
  padding: 20px 28px; 
  border-top: 1px solid var(--border-color); 
  display: flex; 
  gap: 12px; 
  justify-content: center;
  background: var(--bg-primary);
}

.modal-bottom button {
  flex: 1;
}

.input-label { 
  font-size: 11px; 
  font-weight: 600; 
  color: var(--text-muted); 
  text-transform: uppercase; 
  letter-spacing: 0.5px; 
  margin-bottom: 8px; 
  display: block; 
}

.premium-input { 
  width: 100%; 
  padding: 12px 16px; 
  border-radius: 12px; 
  background: var(--bg-primary); 
  border: 1px solid var(--border-color); 
  color: var(--text-primary); 
  font-size: 14px; 
  font-weight: 600; 
  outline: none; 
  transition: 0.2s; 
}
.premium-input:focus { border-color: var(--accent); }
.text-area { resize: none; font-family: inherit; }
.pl-10 { padding-left: 42px !important; }
.pl-12 { padding-left: 48px !important; }

.input-with-side { position: relative; }
.side-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-muted); }
.side-text { 
  position: absolute; 
  left: 16px; 
  top: 50%; 
  transform: translateY(-50%); 
  color: var(--text-muted); 
  font-size: 14px; 
  font-weight: 600;
}

.btn-cancel { 
  background: var(--bg-card);
  color: var(--text-secondary);
  border: 1px solid var(--border-color);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  height: 44px;
  padding: 0 20px;
  border-radius: 12px;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
  outline: none;
}
.btn-cancel:hover {
  border-color: var(--text-muted);
  color: var(--text-primary);
}
.btn-save-stock { 
  display: flex; 
  align-items: center; 
  justify-content: center;
  gap: 10px; 
  padding: 12px 28px; 
  border-radius: 14px; 
  border: none; 
  font-weight: 700; 
  cursor: pointer; 
  color: #fff; 
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
}
.btn-save-stock.add { background: var(--success); }
.btn-save-stock.reduce { background: var(--danger); }
.btn-save-stock:disabled { opacity: 0.5; cursor: not-allowed; }

@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
@keyframes spin { to { transform: rotate(360deg); } }

.table-loading, .table-empty { padding: 80px; text-align: center; }
.loading-spinner { width: 32px; height: 32px; border: 3px solid var(--accent-light); border-top-color: var(--accent); border-radius: 50%; animation: spin 0.8s linear infinite; margin: 0 auto 16px; }
.empty-illu { opacity: 0.2; margin-bottom: 16px; color: var(--text-muted); display: flex; justify-content: center; }
@media (max-width: 1200px) {
  .hero-subtitle { display: none; }
  .filter-glass-bar { flex-wrap: wrap; gap: 10px; }
  .search-box { min-width: 100%; order: 1; }
  .filter-group { order: 2; flex: 1; }
  .filter-toggle-wrap { order: 3; }
  .btn-refresh { order: 4; }
  
  .premium-table th:nth-child(5), .premium-table td:nth-child(5) { display: none; } /* Total Nilai */
}

@media (max-width: 1024px) {
  .premium-table th:nth-child(2), .premium-table td:nth-child(2), /* Kategori */
  .premium-table th:nth-child(4), .premium-table td:nth-child(4)  /* Harga */
  {
    display: none;
  }
  .th-inner, .premium-table td { padding: 12px 14px; }
}

@media (max-width: 768px) {
  .warehouse-container { padding: 4px; overflow-x: hidden; }
  .page-hero { flex-direction: column; text-align: center; gap: 16px; padding: 20px; align-items: stretch; margin-bottom: 16px; }
  .hero-content { align-items: center; text-align: center; flex-direction: column; }
  .hero-icon-wrap { width: 48px; height: 48px; border-radius: 14px; margin-bottom: 8px; }
  .hero-icon-wrap svg { width: 24px; height: 24px; }
  .hero-title { font-size: 20px; }
  .hero-subtitle { font-size: 13px; opacity: 0.8; margin-top: 4px; }
  .hero-actions { display: block; width: 100%; margin-top: 8px; }
  .hero-actions .btn-primary { width: 100%; justify-content: center; height: 48px; display: flex; align-items: center; gap: 10px; border-radius: 14px; font-weight: 700; cursor: pointer; background: #22c55e; color: #fff; border: none; }
  
  .filter-glass-bar { 
    flex-direction: column; 
    align-items: stretch; 
    gap: 8px; 
    padding: 8px; 
    border-radius: 16px;
    background: var(--bg-card);
    border: 1px solid var(--border-color);
  }
  .filter-group { 
    display: flex; 
    gap: 8px; 
    width: 100%;
    align-items: center; 
  }
  .filter-label { display: none; }
  .filter-select { height: 40px; font-size: 12px; flex: 1; border-radius: 12px; }
  .filter-toggle-wrap { display: flex; gap: 8px; }
  .toggle-pill { flex: 1; justify-content: center; height: 40px; padding: 0 12px; font-size: 11px; border-radius: 12px; }
  .btn-refresh { width: 40px; height: 40px; flex-shrink: 0; border-radius: 12px; }
  
  .table-outer-card { border-radius: 16px; overflow: visible; }
  
  /* Card Layout for Mobile */
  .premium-table { border: none; }
  .premium-table thead { display: none; }
  .premium-table tbody { display: block; }
  .premium-table tr { 
    display: block; 
    background: var(--bg-secondary); 
    border: 1px solid var(--border-color); 
    border-radius: 12px; 
    margin-bottom: 12px; 
    padding: 14px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
  }
  .premium-table td { 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    padding: 10px 0; 
    border: none; 
    text-align: right !important;
  }
  .premium-table td:not(:last-child) { 
    border-bottom: 1px solid var(--border-color); 
  }
  .premium-table td::before {
    content: attr(data-label);
    flex: 0 0 40%;
    font-size: 11px;
    font-weight: 600;
    color: var(--text-muted);
    text-align: left;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }
  
  /* Adjust content alignment */
  .item-info-wrap,
  .cat-badge,
  .stock-val-wrap,
  .price-val,
  .total-val,
  .status-pill-premium,
  .action-btn-group {
    margin-left: auto;
  }

  .pagination-area { flex-direction: column; gap: 12px; padding: 16px; align-items: center; text-align: center; }
  .pagin-btns { width: 100%; justify-content: center; }

  /* Modal Mobile */
  .modal-panel-stock { 
    max-width: 100% !important; 
    border-radius: 24px 24px 0 0; 
    position: fixed; 
    bottom: 0; 
    left: 0;
    right: 0;
    width: 100%;
    animation: slideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    max-height: 80vh;
    margin: 0;
    overflow: hidden;
  }
  .modal-backdrop { 
    align-items: flex-end; 
    padding: 0; 
    z-index: 500;
    background: rgba(0,0,0,0.35);
    backdrop-filter: blur(2px);
  }
  .modal-top { 
    padding: 24px; 
    flex-direction: column;
    gap: 16px;
  }
  .btn-back-header { width: 36px; height: 36px; }

  .modal-content { padding: 20px 24px; max-height: 60vh; overflow-y: auto; }
  /* Modal is tucked under footer, so we use padding to push content above it */
  .modal-bottom { 
    padding: 16px 24px calc(16px + env(safe-area-inset-bottom));
    background: var(--bg-primary);
  }
}

@media (max-width: 480px) {
  .premium-table th:nth-child(6), .premium-table td:nth-child(6) { display: none; } /* Status Pill */
  
  .hero-title { font-size: 14px; }
  .i-name { font-size: 12px; }
  .premium-table td { padding: 10px 12px; }
  .modal-top { padding: 16px; }
  .modal-content { padding: 16px; }
  .modal-bottom { padding: 12px 16px 24px; }
  .pagin-info { display: none; }
}

@keyframes slideUp { from { transform: translateY(100%); } to { transform: translateY(0); } }
</style>
