<template>
  <div class="waste-container">
    <!-- Hero Header -->
    <div class="page-hero">
      <div class="hero-content">
        <div class="hero-icon-wrap">
          <Trash2 :size="24" />
        </div>
        <div>
          <h1 class="hero-title">Laporan Waste</h1>
          <p class="hero-subtitle">Catat dan pantau barang rusak atau hilang dari gudang dan dapur.</p>
        </div>
      </div>
      <button class="btn-add-waste" @click="openModal()">
        <Plus :size="18" />
        <span>Laporkan Waste</span>
      </button>
    </div>

    <!-- Stats Overview -->
    <div class="stats-grid">
      <div class="stat-card danger">
        <div class="stat-icon">
          <AlertTriangle :size="20" />
        </div>
        <div class="stat-info">
          <span class="stat-label">Total Waste</span>
          <span class="stat-value">{{ wasteStore.reports.length }}</span>
        </div>
      </div>
      <div class="stat-card warning">
        <div class="stat-icon">
          <DollarSign :size="20" />
        </div>
        <div class="stat-info">
          <span class="stat-label">Est. Kerugian</span>
          <span class="stat-value">{{ formatCurrency(totalLoss) }}</span>
        </div>
      </div>
    </div>

    <!-- Table -->
    <div class="table-outer-card">
      <div v-if="wasteStore.loading && !wasteStore.reports.length" class="table-loading">
        <div class="loading-spinner"></div>
        <span>Memuat data...</span>
      </div>

      <div v-else-if="!wasteStore.reports.length" class="table-empty">
        <div class="empty-illu">
          <FileX :size="48" />
        </div>
        <h3>Belum Ada Laporan Waste</h3>
        <p>Klik tombol "Laporkan Waste" untuk mencatat barang rusak atau hilang.</p>
      </div>

      <div v-else class="table-scroll-wrap">
        <table class="premium-table">
          <thead>
            <tr>
              <th><div class="th-inner">Tanggal</div></th>
              <th><div class="th-inner">Bahan</div></th>
              <th><div class="th-inner">Asal</div></th>
              <th class="text-right"><div class="th-inner justify-end">Qty</div></th>
              <th><div class="th-inner">Alasan</div></th>
              <th class="text-right"><div class="th-inner justify-end">Est. Rugi</div></th>
              <th class="text-center"><div class="th-inner justify-center">Aksi</div></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="r in wasteStore.reports" :key="r.id" class="table-row">
              <td data-label="Tanggal">
                <div class="date-cell">
                  <span class="date-val">{{ formatDateShort(r.created_at) }}</span>
                  <span class="user-val">{{ r.user?.full_name }}</span>
                </div>
              </td>
              <td data-label="Bahan">
                <span class="item-name">{{ r.item_name }}</span>
              </td>
              <td data-label="Asal">
                <span :class="['source-badge', r.source_type]">
                  {{ r.source_type === 'gudang' ? 'Gudang' : 'Dapur' }}
                </span>
              </td>
              <td data-label="Qty" class="text-right">
                <span class="qty-val">{{ formatDecimal(r.quantity) }} {{ r.unit }}</span>
              </td>
              <td data-label="Alasan">
                <span class="reason-text">{{ r.reason || '-' }}</span>
              </td>
              <td data-label="Est. Rugi" class="text-right">
                <span class="loss-value">{{ formatCurrency(r.estimated_loss) }}</span>
              </td>
              <td class="text-center">
                <button class="btn-delete" @click="handleDelete(r.id)" title="Hapus">
                  <Trash2 :size="14" />
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Waste Modal -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="modal.show" class="modal-backdrop" @click.self="modal.show = false">
          <div class="modal-panel-medium">
            <div class="modal-top">
              <div class="modal-header-content">
                <button class="btn-back-header" @click="modal.show = false">
                  <ArrowLeft :size="20" />
                </button>
                <div class="modal-icon-wrap">
                  <Trash2 :size="20" />
                </div>
                <div class="modal-title-area">
                  <h3 class="modal-title">Lapor Barang Rusak/Hilang</h3>
                  <p class="modal-desc">Catat waste untuk tracking kerugian dan analisis.</p>
                </div>
              </div>
            </div>

            <div class="modal-content">
              <div class="form-grid">
                <div class="input-group">
                  <label class="input-label">Asal Stok</label>
                  <div class="radio-group">
                    <label :class="{ active: modal.form.source_type === 'gudang' }">
                      <input type="radio" v-model="modal.form.source_type" value="gudang">
                      <Package :size="16" />
                      <span>Gudang</span>
                    </label>
                    <label :class="{ active: modal.form.source_type === 'kitchen' }">
                      <input type="radio" v-model="modal.form.source_type" value="kitchen">
                      <ChefHat :size="16" />
                      <span>Dapur</span>
                    </label>
                  </div>
                </div>

                <div class="input-group">
                  <label class="input-label">Pilih Bahan</label>
                  <select v-model="modal.form.source_id" class="premium-input">
                    <option value="">-- Pilih Bahan --</option>
                    <template v-if="modal.form.source_type === 'gudang'">
                      <option v-for="i in warehouseStore.items" :key="i.id" :value="i.id">
                        {{ i.name }} (Stok: {{ formatDecimal(i.stock) }} {{ i.unit }})
                      </option>
                    </template>
                    <template v-else>
                      <option v-for="i in kitchenStore.items" :key="i.id" :value="i.id">
                        {{ i.name }} (Stok: {{ formatDecimal(i.stock) }} {{ i.unit }})
                      </option>
                    </template>
                  </select>
                </div>

                <div class="input-group">
                  <label class="input-label">Jumlah Waste</label>
                  <input type="number" v-model="modal.form.quantity" class="premium-input" placeholder="0" step="0.01">
                </div>

                <div class="input-group">
                  <label class="input-label">Alasan</label>
                  <textarea v-model="modal.form.reason" class="premium-input text-area" rows="3" placeholder="Misal: Busuk, Tumpah, Expired, Rusak..."></textarea>
                </div>
              </div>
            </div>

            <div class="modal-bottom">
              <button class="btn-save" @click="handleSubmit" :disabled="wasteStore.loading">
                <Save :size="18" v-if="!wasteStore.loading" />
                <RefreshCw :size="18" class="spinning" v-else />
                {{ wasteStore.loading ? 'Menyimpan...' : 'Simpan Laporan Waste' }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { onMounted, reactive, computed, watch, onUnmounted } from 'vue';
import { useWasteStore } from '../stores/waste';
import { useWarehouseStore } from '../stores/warehouse';
import { useKitchenStore } from '../stores/kitchen';
import { showConfirm, showSuccess, showError } from '../utils/swal';
import { 
  Trash2, X, Plus, AlertTriangle, DollarSign, FileX, 
  Package, ChefHat, Check, Save, RefreshCw, ArrowLeft
} from 'lucide-vue-next';

const wasteStore = useWasteStore();
const warehouseStore = useWarehouseStore();
const kitchenStore = useKitchenStore();

const modal = reactive({
  show: false,
  form: {
    source_type: 'gudang',
    source_id: '',
    quantity: 1,
    reason: '',
  }
});

// Watch modal to toggle mobile nav and body scroll
watch(() => modal.show, (val) => {
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

const totalLoss = computed(() => {
  return wasteStore.reports.reduce((sum, r) => sum + Number(r.estimated_loss || 0), 0);
});

onMounted(() => {
  wasteStore.fetchReports();
  warehouseStore.fetchItems();
  kitchenStore.fetchItems();
});

const openModal = () => {
  modal.form = { source_type: 'gudang', source_id: '', quantity: 1, reason: '' };
  modal.show = true;
};

const handleSubmit = async () => {
  if (!modal.form.source_id) {
    showError('Pilih bahan terlebih dahulu');
    return;
  }
  if (!modal.form.quantity || modal.form.quantity <= 0) {
    showError('Jumlah harus lebih dari 0');
    return;
  }

  const success = await wasteStore.submitReport(modal.form);
  if (success) {
    modal.show = false;
    showSuccess('Laporan waste berhasil dicatat!');
  } else {
    showError(wasteStore.error || 'Gagal menyimpan laporan');
  }
};

const handleDelete = async (id) => {
  const result = await showConfirm({
    title: 'Hapus Laporan',
    text: 'Hapus laporan waste ini?',
    icon: 'warning',
    confirmText: 'Ya, Hapus',
    cancelText: 'Batal'
  });
  
  if (result.isConfirmed) {
    const success = await wasteStore.deleteReport(id);
    if (success) {
      showSuccess('Laporan berhasil dihapus!');
    } else {
      showError('Gagal menghapus laporan');
    }
  }
};

const formatDateShort = (date) => new Date(date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
const formatCurrency = (val) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);
const formatDecimal = (val) => Number(val || 0).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
</script>

<style scoped>
.waste-container { padding: 0; animation: fadeIn 0.4s ease; }

/* ── Hero ── */
.page-hero {
  display: flex; align-items: center; justify-content: space-between;
  background: linear-gradient(135deg, rgba(239,68,68,0.08) 0%, rgba(239,68,68,0.02) 100%);
  border: 1px solid rgba(239,68,68,0.1); border-radius: 20px;
  padding: 20px 24px; margin-bottom: 16px; flex-wrap: wrap; gap: 16px;
}
.hero-content { display: flex; align-items: center; gap: 18px; }
.hero-icon-wrap {
  width: 48px; height: 48px; border-radius: 14px;
  background: linear-gradient(135deg, var(--danger), #dc2626);
  display: flex; align-items: center; justify-content: center;
  color: #fff; box-shadow: 0 8px 24px rgba(239,68,68,0.25);
}
.hero-title { font-size: 20px; font-weight: 600; color: var(--text-primary); margin-bottom: 2px; }
.hero-subtitle { font-size: 13px; color: var(--text-secondary); font-weight: 500; }

.btn-add-waste {
  display: flex; align-items: center; gap: 10px;
  background: linear-gradient(135deg, var(--danger), #dc2626);
  color: #fff; padding: 12px 24px; border-radius: 14px; border: none;
  font-size: 13px; font-weight: 700; cursor: pointer; transition: 0.2s;
  box-shadow: 0 8px 20px -8px rgba(239,68,68,0.4);
}
.btn-add-waste:hover { transform: translateY(-2px); box-shadow: 0 12px 24px -8px rgba(239,68,68,0.5); }

/* ── Stats ── */
.stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 16px; }
.stat-card {
  background: var(--bg-card); border: 1px solid var(--border-color);
  border-radius: 16px; padding: 16px 20px; display: flex; align-items: center; gap: 14px;
  transition: 0.2s;
}
.stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 20px -8px rgba(0,0,0,0.1); }
.stat-icon {
  width: 44px; height: 44px; border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
}
.stat-card.danger .stat-icon { background: var(--danger-bg); color: var(--danger); }
.stat-card.warning .stat-icon { background: var(--warning-bg); color: var(--warning); }
.stat-info { display: flex; flex-direction: column; }
.stat-label { font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; }
.stat-value { font-size: 20px; font-weight: 700; color: var(--text-primary); margin-top: 2px; }

/* ── Table ── */
.table-outer-card {
  background: var(--bg-card); border: 1px solid var(--border-color);
  border-radius: 24px; overflow: hidden;
}
.table-scroll-wrap { overflow-x: auto; }
.premium-table { width: 100%; border-collapse: collapse; text-align: left; }

.premium-table thead { background: rgba(0,0,0,0.02); }
:root:not(.light) .premium-table thead { background: rgba(255,255,255,0.02); }

.th-inner { 
  padding: 16px 24px; font-size: 11px; font-weight: 600; 
  color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.8px; 
  display: flex; align-items: center; gap: 6px; 
}
.th-inner.justify-end { justify-content: flex-end; }
.th-inner.justify-center { justify-content: center; }

.table-row { border-bottom: 1px solid var(--border-color); transition: all 0.2s; }
.table-row:hover { background: var(--bg-primary); }
.premium-table td { padding: 14px 24px; }

.date-cell { display: flex; flex-direction: column; }
.date-val { font-size: 13px; font-weight: 700; color: var(--text-primary); }
.user-val { font-size: 11px; color: var(--text-muted); font-weight: 600; }

.item-name { font-size: 13px; font-weight: 700; color: var(--text-primary); }

.source-badge {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 4px 10px; border-radius: 100px; font-size: 11px; font-weight: 700;
  text-transform: uppercase;
}
.source-badge.gudang { background: var(--info-bg); color: var(--info); border: 1px solid rgba(14,165,233,0.2); }
.source-badge.kitchen { background: var(--warning-bg); color: var(--warning); border: 1px solid rgba(234,179,8,0.2); }

.qty-val { font-size: 13px; font-weight: 700; color: var(--text-primary); }
.reason-text { font-size: 13px; color: var(--text-muted); font-style: italic; }
.loss-value { font-size: 14px; font-weight: 700; color: var(--danger); }

.btn-delete {
  width: 32px; height: 32px; border-radius: 10px;
  background: var(--bg-primary); border: 1px solid var(--border-color);
  color: var(--text-muted); cursor: pointer; transition: 0.2s;
  display: flex; align-items: center; justify-content: center;
}
.btn-delete:hover { background: var(--danger-bg); border-color: var(--danger); color: var(--danger); }

/* Empty & Loading States */
.table-loading { 
  padding: 80px; text-align: center; 
  display: flex; flex-direction: column; align-items: center; gap: 16px; 
  color: var(--text-muted); 
}
.loading-spinner { 
  width: 32px; height: 32px; border: 3px solid var(--accent-light); 
  border-top-color: var(--accent); border-radius: 50%; 
  animation: spin 0.8s linear infinite; 
}
.table-empty { text-align: center; padding: 60px 20px; }
.empty-illu { color: var(--text-muted); opacity: 0.2; margin-bottom: 16px; }
.table-empty h3 { font-size: 18px; font-weight: 600; color: var(--text-primary); margin-bottom: 8px; }
.table-empty p { font-size: 14px; color: var(--text-muted); }

/* ── Modal ── */
.modal-backdrop {
  position: fixed; inset: 0; z-index: 200;
  background: rgba(0,0,0,0.6); backdrop-filter: blur(6px);
  display: flex; align-items: center; justify-content: center; padding: 20px;
}

.modal-panel-medium {
  width: 100%; max-width: 520px; background: var(--bg-card);
  border-radius: 28px; border: 1px solid var(--border-color);
  box-shadow: 0 25px 50px -12px rgba(0,0,0,0.3);
  animation: modalScale 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
  overflow: hidden;
}

.modal-top {
  display: flex; flex-direction: column; gap: 16px; padding: 24px; border-bottom: 1px solid var(--border-color);
}

.modal-header-content { display: flex; align-items: center; gap: 16px; width: 100%; }

.modal-icon-wrap {
  width: 44px; height: 44px; border-radius: 12px; background: rgba(239,68,68,0.1); color: var(--danger);
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}

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

.modal-content { padding: 24px; }
.modal-bottom {
  display: flex; justify-content: center; padding: 20px 24px; border-top: 1px solid var(--border-color);
}

.form-grid { display: flex; flex-direction: column; gap: 20px; }
.input-group { display: flex; flex-direction: column; gap: 8px; }
.input-label { 
  font-size: 11px; 
  font-weight: 700; 
  color: var(--text-muted); 
  text-transform: uppercase; 
  letter-spacing: 0.6px;
  margin-bottom: 4px;
  display: block;
}

.radio-group { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.radio-group label {
  display: flex; align-items: center; justify-content: center; gap: 8px;
  padding: 12px; border: 1.5px solid var(--border-color); border-radius: 12px;
  font-size: 13px; font-weight: 700; color: var(--text-muted);
  cursor: pointer; transition: 0.2s; background: var(--bg-primary);
}
.radio-group label:hover { border-color: var(--accent); }
.radio-group label.active {
  background: var(--accent); color: #fff; border-color: var(--accent);
}
.radio-group input { display: none; }

.premium-input { 
  width: 100%; padding: 10px 14px; border-radius: 12px; 
  background: var(--bg-primary); border: 1px solid var(--border-color); 
  color: var(--text-primary); font-size: 13.5px; font-weight: 500; outline: none; 
  transition: 0.2s; font-family: inherit;
}
.premium-input:focus { border-color: var(--accent); }
.text-area { resize: vertical; min-height: 80px; }

.btn-save {
  width: 100%; padding: 12px 32px; border-radius: 12px; background: var(--accent); color: #fff; border: none; font-weight: 700; cursor: pointer;
  display: flex; align-items: center; justify-content: center; gap: 8px;
  transition: all 0.2s;
}

.btn-save:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px -8px rgba(249, 115, 22, 0.4);
}

.btn-save:disabled {
  opacity: 0.7;
  cursor: not-allowed;
  transform: none;
}

/* Animations */
@keyframes modalScale { from { transform: scale(0.95); opacity: 0; } to { transform: scale(1); opacity: 1; } }

/* Modal Transitions */
.modal-enter-active, .modal-leave-active { transition: opacity 0.3s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
.modal-enter-active .modal-panel-medium,
.modal-leave-active .modal-panel-medium { transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); }
.modal-enter-from .modal-panel-medium,
.modal-leave-to .modal-panel-medium { transform: scale(0.9); }

/* Utility */
.text-right { text-align: right; }
.text-center { text-align: center; }

/* ── Responsive ── */
@media (max-width: 768px) {
  .waste-container { padding: 4px; }
  .page-hero { padding: 16px; flex-direction: column; align-items: flex-start; }
  .hero-icon-wrap { width: 36px; height: 36px; }
  .hero-icon-wrap svg { width: 18px; height: 18px; }
  .hero-title { font-size: 16px; }
  .hero-subtitle { font-size: 11px; }
  .btn-add-waste { width: 100%; justify-content: center; padding: 10px 20px; }

  .stats-grid { grid-template-columns: 1fr; gap: 12px; }
  .stat-card { padding: 14px 16px; }
  .stat-icon { width: 36px; height: 36px; }
  .stat-value { font-size: 18px; }

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

  .modal-backdrop { align-items: flex-end; padding: 0; background: rgba(0,0,0,0.35); backdrop-filter: blur(2px); }
  .modal-panel-medium { max-width: 100%; border-radius: 24px 24px 0 0; }
  .modal-top { 
    padding: 24px; 
    flex-direction: column;
    gap: 16px;
  }
  .btn-back-header { width: 36px; height: 36px; }
  .modal-content { padding: 20px; }
  .modal-bottom { padding: 16px 20px 32px; }
}

@keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes spin { to { transform: rotate(360deg); } }
</style>
