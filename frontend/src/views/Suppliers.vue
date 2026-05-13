<template>
  <div class="suppliers-container">
    <!-- Header Hero Section -->
    <div class="page-hero">
      <div class="hero-content">
        <div class="hero-icon-wrap">
          <Truck :size="24" />
        </div>
        <div>
          <h1 class="hero-title">Manajemen Supplier</h1>
          <p class="hero-subtitle">Kelola mitra pemasok bahan baku, pantau performa pengadaan, dan histori transaksi.</p>
        </div>
      </div>
      <div class="hero-actions">
        <button class="btn-primary" @click="openModal()">
          <Plus :size="18" /> Tambah Supplier Baru
        </button>
      </div>
    </div>

    <!-- Stats Summary Row -->
    <div class="stats-grid mb-4">
      <div class="stat-glass-card">
        <div class="stat-icon-box orange">
          <UsersIcon :size="20" />
        </div>
        <div class="stat-info">
          <span class="stat-label">Total Supplier</span>
          <h3 class="stat-value">{{ supplierStore.suppliers.length }}</h3>
        </div>
      </div>
      <div class="stat-glass-card">
        <div class="stat-icon-box green">
          <TrendingUp :size="20" />
        </div>
        <div class="stat-info">
          <span class="stat-label">Total Pengadaan</span>
          <h3 class="stat-value text-green">{{ formatCurrency(totalProcurement) }}</h3>
        </div>
      </div>
      <div class="stat-glass-card">
        <div class="stat-icon-box blue">
          <Activity :size="20" />
        </div>
        <div class="stat-info">
          <span class="stat-label">Total Transaksi</span>
          <h3 class="stat-value">{{ totalTransactions }}</h3>
        </div>
      </div>
    </div>

    <!-- Filter Bar -->
    <div class="filter-glass-bar">
      <div class="search-box">
        <Search :size="18" class="search-icon" />
        <input 
          type="text" 
          v-model="searchQuery" 
          placeholder="Cari nama supplier, PIC, atau no hp..." 
        >
      </div>
      <div class="filter-group">
        <button class="btn-refresh" @click="searchQuery = ''" title="Reset Filter">
          <RotateCcw :size="16" />
        </button>
      </div>
    </div>

    <!-- Data Table -->
    <div class="table-outer-card">
      <div v-if="supplierStore.loading" class="table-loading">
        <div class="loading-spinner"></div>
        <span>Memuat data supplier...</span>
      </div>

      <div v-else-if="filteredSuppliers.length === 0" class="table-empty">
        <div class="empty-illu">
          <Truck :size="48" />
        </div>
        <h3>Belum Ada Supplier</h3>
        <p>Mulai kelola rantai pasok Anda dengan menambahkan supplier pertama.</p>
        <button class="btn-secondary" @click="openModal()">Tambah Supplier</button>
      </div>

      <div v-else class="table-scroll-wrap">
        <table class="premium-table">
          <thead>
            <tr>
              <th width="300"><div class="th-inner">Nama Supplier</div></th>
              <th><div class="th-inner">PIC / Kontak Person</div></th>
              <th><div class="th-inner">Informasi Kontak</div></th>
              <th class="text-right"><div class="th-inner justify-end">Transaksi</div></th>
              <th class="text-center"><div class="th-inner justify-center">Status</div></th>
              <th class="text-center"><div class="th-inner justify-center">Aksi</div></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(supplier, idx) in filteredSuppliers" :key="supplier.id" class="table-row" :style="{ animationDelay: (idx * 0.05) + 's' }">
              <td data-label="Supplier">
                <div class="item-info-wrap">
                  <div class="item-icon-box">
                    <div class="avatar-text">{{ supplier.name.charAt(0) }}</div>
                  </div>
                  <div class="item-text">
                    <span class="i-name">{{ supplier.name }}</span>
                    <span class="i-unit" v-if="supplier.notes">{{ supplier.notes }}</span>
                  </div>
                </div>
              </td>
              <td data-label="PIC">
                <span class="pic-text">{{ supplier.contact_person || '-' }}</span>
              </td>
              <td data-label="Kontak">
                <div class="contact-stack">
                  <div v-if="supplier.phone" class="c-line"><Phone :size="12" /> {{ supplier.phone }}</div>
                  <div v-if="supplier.email" class="c-line"><Mail :size="12" /> {{ supplier.email }}</div>
                </div>
              </td>
              <td data-label="Transaksi" class="text-right">
                <div class="stat-inline">
                  <span class="val">{{ getSupplierStats(supplier.id).transactions_count }}</span>
                  <span class="lbl">KALI</span>
                </div>
              </td>
              <td data-label="Status" class="text-center">
                <div class="status-pill-premium" :class="supplier.is_active ? 'success' : 'danger'">
                  <div class="s-dot"></div>
                  {{ supplier.is_active ? 'AKTIF' : 'NON-AKTIF' }}
                </div>
              </td>
              <td data-label="Aksi" class="text-center">
                <div class="action-btn-group">
                  <button class="btn-stock edit" @click="openModal(supplier)" title="Edit">
                    <Edit2 :size="14" />
                  </button>
                  <button class="btn-stock delete" @click="handleDelete(supplier)" title="Hapus">
                    <Trash2 :size="14" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Form -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="showModal" class="modal-backdrop" @click.self="showModal = false">
          <div class="modal-panel-stock">
            <div class="modal-top">
              <div class="modal-header-content">
                <button class="btn-back-header" @click="showModal = false">
                  <ArrowLeft :size="20" />
                </button>
                <div class="modal-icon-wrap" style="background: var(--accent-light); color: var(--accent);">
                  <Truck :size="20" />
                </div>
                <div class="modal-title-area">
                  <h3 class="modal-title">{{ editMode ? 'Edit Supplier' : 'Registrasi Supplier' }}</h3>
                  <p class="modal-desc">Lengkapi informasi dasar dan kontak mitra pemasok.</p>
                </div>
              </div>
            </div>

            <div class="modal-content">
              <div class="form-grid-2">
                <div class="input-group">
                  <label class="input-label">Nama Supplier / Perusahaan</label>
                  <input type="text" v-model="form.name" class="premium-input" placeholder="E.g. CV Beras Makmur">
                </div>
                <div class="input-group">
                  <label class="input-label">Nama PIC</label>
                  <input type="text" v-model="form.contact_person" class="premium-input" placeholder="Person In Charge">
                </div>
              </div>

              <div class="form-grid-2 mt-4">
                <div class="input-group">
                  <label class="input-label">No. Telepon / WA</label>
                  <input type="text" v-model="form.phone" class="premium-input" placeholder="08xxxxxxxxxxx">
                </div>
                <div class="input-group">
                  <label class="input-label">Email</label>
                  <input type="email" v-model="form.email" class="premium-input" placeholder="supplier@email.com">
                </div>
              </div>

              <div class="input-group mt-4">
                <label class="input-label">Alamat Lengkap</label>
                <textarea v-model="form.address" class="premium-input text-area" rows="2" placeholder="Alamat gudang atau kantor supplier..."></textarea>
              </div>

              <div class="input-group mt-4">
                <label class="input-label">Catatan (Terlihat saat stok masuk)</label>
                <textarea v-model="form.notes" class="premium-input text-area" rows="2" placeholder="Informasi tambahan lain..."></textarea>
              </div>

              <div class="input-group mt-4">
                <label class="premium-toggle-label">
                  <span class="lbl-txt">Status Supplier Aktif</span>
                  <div class="tgl-switch">
                    <input type="checkbox" v-model="form.is_active" hidden>
                    <div class="tgl-slider"></div>
                  </div>
                </label>
              </div>
            </div>

            <div class="modal-bottom">
              <button class="btn-save-primary" @click="handleSubmit" :disabled="saving">
                <RefreshCw v-if="saving" :size="18" class="spinner" />
                <Check v-else :size="18" />
                {{ editMode ? 'Simpan Perubahan' : 'Daftarkan Supplier' }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, reactive, watch, onUnmounted } from 'vue';
import { useSupplierStore } from '../stores/supplier';
import { 
  Plus, Search, Truck, Users as UsersIcon, TrendingUp, Phone, Mail, 
  Edit2, Trash2, X, Activity, Check, RotateCcw, ArrowLeft, RefreshCw 
} from 'lucide-vue-next';
import { showToast, showConfirm } from '../utils/swal';

const supplierStore = useSupplierStore();
const searchQuery = ref('');
const showModal = ref(false);
const editMode = ref(false);
const saving = ref(false);

const form = reactive({
  id: null,
  name: '',
  contact_person: '',
  phone: '',
  email: '',
  address: '',
  notes: '',
  is_active: true
});

onMounted(() => {
  supplierStore.fetchSuppliers();
  supplierStore.fetchStats();
});

const filteredSuppliers = computed(() => {
  if (!searchQuery.value) return supplierStore.suppliers;
  const q = searchQuery.value.toLowerCase();
  return supplierStore.suppliers.filter(s => 
    s.name.toLowerCase().includes(q) || 
    (s.contact_person && s.contact_person.toLowerCase().includes(q)) ||
    (s.phone && s.phone.includes(q))
  );
});

const totalProcurement = computed(() => {
  return supplierStore.stats.reduce((sum, s) => sum + (Number(s.total_spend) || 0), 0);
});

const totalTransactions = computed(() => {
  return supplierStore.stats.reduce((sum, s) => sum + (Number(s.transactions_count) || 0), 0);
});

const getSupplierStats = (id) => {
  return supplierStore.stats.find(s => s.id === id) || { transactions_count: 0, total_spend: 0 };
}

const formatCurrency = (val) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
  }).format(val);
};

const openModal = (supplier = null) => {
  if (supplier) {
    editMode.value = true;
    form.id = supplier.id;
    form.name = supplier.name;
    form.contact_person = supplier.contact_person;
    form.phone = supplier.phone;
    form.email = supplier.email;
    form.address = supplier.address;
    form.notes = supplier.notes;
    form.is_active = !!supplier.is_active;
  } else {
    editMode.value = false;
    form.id = null;
    form.name = '';
    form.contact_person = '';
    form.phone = '';
    form.email = '';
    form.address = '';
    form.notes = '';
    form.is_active = true;
  }
  showModal.value = true;
};

const handleSubmit = async () => {
  saving.value = true;
  let res;
  if (editMode.value) {
    res = await supplierStore.updateSupplier(form.id, { ...form });
  } else {
    res = await supplierStore.addSupplier({ ...form });
  }

  if (res.success) {
    showToast('success', res.message);
    showModal.value = false;
    supplierStore.fetchStats();
  } else {
    showToast('error', res.message);
  }
  saving.value = false;
};

const handleDelete = async (supplier) => {
  const result = await showConfirm({
    title: 'Hapus Supplier?',
    text: `Hapus "${supplier.name}"? Data transaksi yang ada akan tetap tersimpan di histori.`,
    icon: 'warning',
    confirmText: 'Ya, Hapus'
  });

  if (result.isConfirmed) {
    const res = await supplierStore.deleteSupplier(supplier.id);
    if (res.success) {
      showToast('success', res.message);
      supplierStore.fetchStats();
    } else {
      showToast('error', res.message);
    }
  }
};

// Modal Scroll Lock
watch(() => showModal.value, (val) => {
  if (val) {
    document.body.style.overflow = 'hidden';
  } else {
    document.body.style.overflow = '';
  }
});

onUnmounted(() => {
  document.body.style.overflow = '';
});
</script>

<style scoped>
.suppliers-container { animation: fadeIn 0.4s ease; padding: 0; }

/* Hero */
.page-hero {
  display: flex; align-items: center; justify-content: space-between;
  background: linear-gradient(135deg, rgba(249,115,22,0.08) 0%, rgba(249,115,22,0.02) 100%);
  border: 1px solid rgba(249,115,22,0.1); border-radius: 20px;
  padding: 20px 24px; margin-bottom: 20px;
}
.hero-content { display: flex; align-items: center; gap: 18px; }
.hero-icon-wrap {
  width: 48px; height: 48px; border-radius: 14px;
  background: linear-gradient(135deg, var(--accent), #fb923c);
  display: flex; align-items: center; justify-content: center;
  color: #fff; box-shadow: 0 8px 24px rgba(249, 115, 22, 0.25);
}
.hero-title { font-size: 20px; font-weight: 600; color: var(--text-primary); margin-bottom: 2px; }
.hero-subtitle { font-size: 13px; color: var(--text-secondary); font-weight: 500; }

.btn-primary {
  display: flex; align-items: center; gap: 10px;
  background: linear-gradient(135deg, var(--accent), #fb923c);
  color: #fff; border: none; padding: 12px 24px; border-radius: 12px;
  font-size: 14px; font-weight: 700; cursor: pointer;
}

/* Stats Row */
.stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; }
.stat-glass-card {
  padding: 20px; border-radius: 18px; background: var(--bg-card); border: 1px solid var(--border-color);
  display: flex; align-items: center; gap: 16px;
}
.stat-icon-box {
  width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center;
}
.stat-icon-box.orange { background: #fff7ed; color: #f97316; }
.stat-icon-box.green { background: #f0fdf4; color: #10b981; }
.stat-icon-box.blue { background: #eff6ff; color: #3b82f6; }

.stat-label { font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; }
.stat-value { font-size: 18px; font-weight: 800; color: var(--text-primary); margin-top: 2px; }
.text-green { color: #10b981; }

/* Filter Bar */
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
.btn-refresh {
  width: 44px; height: 44px; border-radius: 14px; background: var(--bg-primary);
  border: 1px solid var(--border-color); color: var(--text-secondary); cursor: pointer;
}

/* Table */
.table-outer-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 20px; overflow: hidden; }
.table-scroll-wrap { overflow-x: auto; }
.premium-table { width: 100%; border-collapse: collapse; }
.th-inner { padding: 16px 24px; font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; display: flex; align-items: center; gap: 6px; }
.table-row { border-bottom: 1px solid var(--border-color); transition: 0.2s; animation: slideUp 0.4s ease both; }
.table-row:hover { background: var(--bg-primary); }
.premium-table td { padding: 14px 24px; vertical-align: middle; }

.item-info-wrap { display: flex; align-items: center; gap: 14px; }
.item-icon-box {
  width: 38px; height: 38px; border-radius: 10px; background: var(--bg-primary);
  display: flex; align-items: center; justify-content: center;
}
.avatar-text { font-size: 16px; font-weight: 800; color: var(--accent); }
.i-name { display: block; font-size: 14px; font-weight: 700; color: var(--text-primary); }
.i-unit { font-size: 11px; color: var(--text-muted); }

.pic-text { font-size: 13px; font-weight: 600; color: var(--text-secondary); }
.contact-stack { display: flex; flex-direction: column; gap: 2px; }
.c-line { font-size: 12px; color: var(--text-muted); display: flex; align-items: center; gap: 6px; }

.stat-inline { display: flex; flex-direction: column; align-items: flex-end; }
.stat-inline .val { font-size: 15px; font-weight: 700; color: var(--text-primary); }
.stat-inline .lbl { font-size: 9px; font-weight: 800; color: var(--text-muted); }

.status-pill-premium {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 4px 10px; border-radius: 100px; font-size: 10px; font-weight: 700;
}
.status-pill-premium.success { background: #f0fdf4; color: #16a34a; }
.status-pill-premium.danger { background: #fef2f2; color: #dc2626; }
.s-dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }

.action-btn-group { display: flex; justify-content: center; gap: 6px; }
.btn-stock {
  width: 32px; height: 32px; border-radius: 10px; border: 1px solid var(--border-color);
  background: var(--bg-primary); cursor: pointer; transition: 0.2s;
  display: flex; align-items: center; justify-content: center; color: var(--text-muted);
}
.btn-stock:hover { background: var(--bg-card-hover); color: var(--text-primary); transform: translateY(-1px); }
.btn-stock.delete:hover { border-color: #ef4444; color: #ef4444; }

/* Modal */
.modal-backdrop {
  position: fixed; inset: 0; z-index: 9999;
  background: rgba(0,0,0,0.4); backdrop-filter: blur(8px);
  display: flex; align-items: center; justify-content: center; padding: 20px;
}
.modal-panel-stock { 
  width: 100%; max-width: 520px; background: var(--bg-card); 
  border-radius: 24px; border: 1px solid var(--border-color); 
  box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); overflow: hidden;
  animation: modalScale 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.modal-top { display: flex; flex-direction: column; gap: 16px; padding: 24px; border-bottom: 1px solid var(--border-color); }
.modal-header-content { display: flex; align-items: center; gap: 16px; width: 100%; }
.modal-icon-wrap { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.modal-title-area { flex: 1; }
.modal-title { font-size: 18px; font-weight: 600; color: var(--text-primary); }
.modal-desc { font-size: 13px; color: var(--text-muted); }
.btn-back-header { width: 36px; height: 36px; border-radius: 10px; border: none; background: var(--bg-primary); cursor: pointer; display: flex; align-items: center; justify-content: center; }

.modal-content { padding: 24px; }
.form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.input-group { display: flex; flex-direction: column; gap: 6px; }
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
.premium-input:focus { border-color: var(--accent); }
.text-area { resize: none; }

.premium-toggle-label {
  display: flex; align-items: center; justify-content: space-between;
  padding: 14px; background: var(--bg-primary); border-radius: 14px; cursor: pointer;
}
.lbl-txt { font-size: 13px; font-weight: 700; color: var(--text-secondary); }
.tgl-switch { width: 40px; height: 20px; background: #cbd5e1; border-radius: 20px; position: relative; transition: 0.3s; }
.tgl-slider { width: 16px; height: 16px; background: #fff; border-radius: 50%; position: absolute; top: 2px; left: 2px; transition: 0.3s; }
input:checked + .tgl-slider { left: 22px; }
.premium-toggle-label:has(input:checked) .tgl-switch { background: #10b981; }

.modal-bottom { padding: 20px 24px; border-top: 1px solid var(--border-color); background: var(--bg-primary); }
.btn-save-primary {
  width: 100%; height: 48px; border-radius: 14px; border: none;
  background: linear-gradient(135deg, var(--accent), #fb923c);
  color: #fff; font-weight: 700; display: flex; align-items: center; justify-content: center; gap: 10px; cursor: pointer;
}

@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes slideUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
@keyframes modalScale { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
.spinner { animation: spin 1s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

.table-loading, .table-empty { padding: 60px; text-align: center; color: var(--text-muted); }
.loading-spinner { width: 30px; height: 30px; border: 3px solid #f3f4f6; border-top-color: var(--accent); border-radius: 50%; animation: spin 0.8s linear infinite; margin: 0 auto 12px; }
.empty-illu { opacity: 0.2; margin-bottom: 12px; }

@keyframes slideUp { from { transform: translateY(100%); } to { transform: translateY(0); } }

@media (max-width: 768px) {
  .suppliers-container { padding: 4px; overflow-x: hidden; }
  .page-hero { flex-direction: column; text-align: center; gap: 16px; padding: 20px; align-items: stretch; margin-bottom: 16px; }
  .hero-content { align-items: center; text-align: center; flex-direction: column; }
  .hero-icon-wrap { width: 48px; height: 48px; border-radius: 14px; margin-bottom: 8px; }
  .hero-icon-wrap svg { width: 24px; height: 24px; }
  .hero-title { font-size: 20px; }
  .hero-subtitle { font-size: 13px; opacity: 0.8; margin-top: 4px; }
  .hero-actions { display: block; width: 100%; margin-top: 8px; }
  .hero-actions .btn-primary { width: 100%; justify-content: center; height: 46px; }
  
  .stats-grid { grid-template-columns: 1fr; gap: 10px; }
  .filter-glass-bar { flex-direction: column; align-items: stretch; gap: 8px; padding: 12px; }
  
  /* Modal Bottom Sheet Style */
  .modal-backdrop { 
    align-items: flex-end; 
    padding: 0; 
    z-index: 2100;
    background: rgba(0,0,0,0.35);
    backdrop-filter: blur(2px);
  }
  .modal-panel-stock { 
    max-width: 100% !important; 
    border-radius: 24px 24px 0 0; 
    position: fixed; 
    bottom: 0; left: 0; right: 0;
    width: 100%;
    margin: 0;
    animation: slideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    max-height: 85vh;
    display: flex;
    flex-direction: column;
    border-bottom: none;
  }
  .modal-top { 
    padding: 24px; 
    border-bottom: 1px solid var(--border-color);
  }
  .modal-header-content {
    gap: 12px;
  }
  .modal-icon-wrap { width: 40px; height: 40px; }
  .modal-title { font-size: 16px; }
  .modal-desc { font-size: 12px; }

  .modal-content { 
    padding: 24px; 
    overflow-y: auto; 
    max-height: 60vh; 
  }
  .modal-bottom { 
    padding: 16px 24px calc(16px + env(safe-area-inset-bottom));
    background: var(--bg-primary);
  }
  
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
    animation: slideUp 0.4s ease both;
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
  
  .item-info-wrap, .pic-text, .contact-stack, .stat-inline, .status-pill-premium, .action-btn-group {
    margin-left: auto;
    text-align: right;
  }
}

@media (max-width: 480px) {
  .hero-subtitle { display: none; }
  .stat-glass-card { padding: 14px; gap: 12px; }
  .stat-value { font-size: 16px; }
  .modal-top { padding: 20px 16px; }
  .modal-content { padding: 20px 16px; }
  .modal-bottom { padding: 12px 16px 24px; }
}
</style>
