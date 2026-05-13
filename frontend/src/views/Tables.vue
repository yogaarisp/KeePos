<template>
  <div class="tables-container">
    <!-- Header Hero -->
    <div class="page-hero">
      <div class="hero-content">
        <div class="hero-icon-wrap">
          <Table2 :size="24" />
        </div>
        <div>
          <h1 class="hero-title">Manajemen Meja</h1>
          <p class="hero-subtitle">Atur tata letak meja, kapasitas pelanggan, dan pantau ketersediaan secara real-time.</p>
        </div>
      </div>
      <div class="hero-actions">
        <button class="btn-primary" @click="openModal()">
          <Plus :size="18" /> Tambah Meja Baru
        </button>
      </div>
    </div>

    <!-- Status Overview Stats -->
    <div class="status-overview">
      <div class="overview-pill available">
        <span class="pill-dot"></span>
        <span class="pill-label">Tersedia: <b>{{ countByStatus('available') }}</b></span>
      </div>
      <div class="overview-pill occupied">
        <span class="pill-dot"></span>
        <span class="pill-label">Terisi: <b>{{ countByStatus('occupied') }}</b></span>
      </div>
      <div class="overview-pill reserved">
        <span class="pill-dot"></span>
        <span class="pill-label">Dipesan: <b>{{ countByStatus('reserved') }}</b></span>
      </div>
    </div>

    <!-- Grid Layout -->
    <div class="tables-grid-area">
      <div v-if="tableStore.loading && !tableStore.tables.length" class="loading-grid">
        <div v-for="i in 8" :key="i" class="skeleton-card"></div>
      </div>

      <div v-else-if="tableStore.tables.length" class="tables-grid">
        <div 
          v-for="(table, idx) in tableStore.tables" 
          :key="table.id" 
          class="premium-table-card"
          :class="table.status"
          :style="{ animationDelay: (idx * 0.05) + 's' }"
        >
          <div class="t-card-top">
            <div class="t-num-badge">{{ table.table_number }}</div>
            <div class="t-actions">
              <button class="t-action-btn edit" @click="openModal(table)" title="Edit">
                <Edit3 :size="14" />
              </button>
              <button class="t-action-btn delete" @click="handleDelete(table.id)" title="Hapus">
                <Trash2 :size="14" />
              </button>
            </div>
          </div>

          <div class="t-card-body">
            <div class="t-main-icon">
              <!-- Illustrative icon based on capacity -->
              <Users v-if="table.capacity > 4" :size="32" />
              <User v-else-if="table.capacity <= 2" :size="32" />
              <Table2 v-else :size="32" />
            </div>
            
            <div class="t-capacity-info">
              <span class="cap-label">Kapasitas</span>
              <span class="cap-value">{{ table.capacity }} Orang</span>
            </div>

            <div class="t-status-manage">
              <button 
                @click="tableStore.updateStatus(table.id, 'available')"
                class="t-status-btn"
                :class="{ active: table.status === 'available' }"
              >
                <span class="status-dot available"></span>
                Tersedia
              </button>
              <button 
                @click="tableStore.updateStatus(table.id, 'occupied')"
                class="t-status-btn"
                :class="{ active: table.status === 'occupied' }"
              >
                <span class="status-dot occupied"></span>
                Terisi
              </button>
              <button 
                @click="tableStore.updateStatus(table.id, 'reserved')"
                class="t-status-btn"
                :class="{ active: table.status === 'reserved' }"
              >
                <span class="status-dot reserved"></span>
                Dipesan
              </button>
            </div>
          </div>

          <div class="t-card-footer">
            <div class="pulse-indicator"></div>
            <span class="status-tag-text">{{ translateStatus(table.status) }}</span>
          </div>
        </div>
      </div>

      <div v-else class="empty-placeholder">
        <div class="empty-icon">
          <Layout :size="48" />
        </div>
        <h3>Meja Belum Diatur</h3>
        <p>Silakan buat konfigurasi meja pertama Anda untuk memulai operasional POS.</p>
        <button class="btn-primary" @click="openModal()">
          <Plus :size="18" /> Tambah Meja Pertama
        </button>
      </div>
    </div>

    <!-- Modern Table Modal -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="modal.show" class="modal-backdrop" @click.self="modal.show = false">
          <div class="modal-panel">
            <div class="modal-top">
              <div class="modal-header-content">
                <button class="btn-back-header" @click="modal.show = false">
                  <ArrowLeft :size="20" />
                </button>
                <div class="modal-icon-wrap">
                  <Table2 :size="20" />
                </div>
                <div class="modal-title-area">
                  <h3 class="modal-title">{{ modal.form.id ? 'Perbarui Meja' : 'Buat Meja Baru' }}</h3>
                  <p class="modal-desc">{{ modal.form.id ? 'Ubah pengaturan meja nomor ' + modal.form.table_number : 'Tambahkan meja atau area pelanggan baru.' }}</p>
                </div>
              </div>
            </div>

            <div class="modal-content">
              <div class="form-grid">
                <div class="input-group">
                  <label class="input-label">Nomor / Label Meja</label>
                  <input type="text" v-model="modal.form.table_number" class="premium-input" placeholder="Contoh: 01, VIP-1, Meja Bundar">
                </div>
                
                <div class="form-row-2">
                  <div class="input-group">
                    <label class="input-label">Kapasitas (Orang)</label>
                    <div class="input-with-side">
                      <Users :size="16" class="side-icon" />
                      <input type="number" v-model="modal.form.capacity" class="premium-input pl-10" min="1">
                    </div>
                  </div>
                  <div class="input-group">
                    <label class="input-label">Status Awal</label>
                    <select v-model="modal.form.status" class="premium-input">
                      <option value="available">Tersedia</option>
                      <option value="occupied">Terisi</option>
                      <option value="reserved">Dipesan</option>
                    </select>
                  </div>
                </div>
              </div>
            </div>

            <div class="modal-bottom">
              <button class="btn-save" @click="handleSave" :disabled="tableStore.loading">
                <Save :size="18" v-if="!tableStore.loading" />
                <RefreshCw :size="18" class="spinning" v-else />
                {{ tableStore.loading ? 'Menyimpan...' : 'Simpan Konfigurasi' }}
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
import { useTableStore } from '../stores/table';
import { showConfirm, showSuccess, showError } from '../utils/swal';
import { 
  Plus, Users, Edit3, Trash2, ArrowLeft, Layout, 
  Table2, User, Save, RefreshCw 
} from 'lucide-vue-next';

const tableStore = useTableStore();
const modal = reactive({
  show: false,
  form: {
    id: null,
    table_number: '',
    capacity: 2,
    status: 'available'
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

onMounted(() => {
  tableStore.fetchTables();
});

const countByStatus = (status) => {
  return tableStore.tables.filter(t => t.status === status).length;
};

const openModal = (table = null) => {
  if (table) {
    modal.form = { ...table };
  } else {
    modal.form = {
      id: null,
      table_number: '',
      capacity: 2,
      status: 'available'
    };
  }
  modal.show = true;
};

const handleSave = async () => {
  if (!modal.form.table_number) {
    showError('Nomor meja harus diisi!');
    return;
  }
  const success = await tableStore.saveTable(modal.form);
  if (success) {
    modal.show = false;
    showSuccess(modal.mode === 'add' ? 'Meja berhasil ditambahkan!' : 'Meja berhasil diperbarui!');
  }
};

const handleDelete = async (id) => {
  const result = await showConfirm({
    title: 'Hapus Meja',
    text: 'Hapus konfigurasi meja ini? Semua riwayat tetap tersimpan di laporan.',
    icon: 'warning',
    confirmText: 'Ya, Hapus',
    cancelText: 'Batal'
  });
  
  if (result.isConfirmed) {
    const success = await tableStore.deleteTable(id);
    if (success) {
      showSuccess('Meja berhasil dihapus!');
    } else {
      showError(tableStore.error || 'Gagal menghapus meja');
    }
  }
};

const translateStatus = (status) => {
  const map = {
    available: 'KOSONG',
    occupied: 'DITEMPATI',
    reserved: 'DIPESAN'
  };
  return map[status] || status;
};

const getStatusLabel = (status) => {
  const map = {
    available: 'Tersedia',
    occupied: 'Terisi',
    reserved: 'Dipesan'
  };
  return map[status] || status;
};

const cycleStatus = async (table) => {
  const statusCycle = ['available', 'occupied', 'reserved'];
  const currentIndex = statusCycle.indexOf(table.status);
  const nextIndex = (currentIndex + 1) % statusCycle.length;
  const nextStatus = statusCycle[nextIndex];
  
  await tableStore.updateStatus(table.id, nextStatus);
};
</script>

<style scoped>
.tables-container { padding: 0; animation: fadeIn 0.4s ease; }

/* ── Hero ── */
.page-hero {
  display: flex; align-items: center; justify-content: space-between;
  background: linear-gradient(135deg, rgba(139,92,246,0.08) 0%, rgba(139,92,246,0.02) 100%);
  border: 1px solid rgba(139,92,246,0.1); border-radius: 20px;
  padding: 28px 32px; margin-bottom: 24px;
}
.hero-content { display: flex; align-items: center; gap: 18px; }
.hero-icon-wrap {
  width: 52px; height: 52px; border-radius: 16px;
  background: linear-gradient(135deg, #8b5cf6, #c084fc);
  display: flex; align-items: center; justify-content: center;
  color: #fff; box-shadow: 0 8px 24px rgba(139, 92, 246, 0.25);
}
.hero-title { font-size: 22px; font-weight: 600; color: var(--text-primary); margin-bottom: 4px; }
.hero-subtitle { font-size: 14px; color: var(--text-secondary); font-weight: 500; }

.btn-primary {
  display: flex; align-items: center; gap: 10px;
  background: linear-gradient(135deg, var(--accent), #fb923c);
  color: #fff; border: none; padding: 12px 24px; border-radius: 12px;
  font-size: 14px; font-weight: 700; cursor: pointer;
}

/* ── Status Overview ── */
.status-overview { display: flex; gap: 12px; margin-bottom: 24px; }
.overview-pill {
  display: flex; align-items: center; gap: 8px;
  padding: 8px 16px; border-radius: 100px;
  background: var(--bg-card); border: 1px solid var(--border-color);
}
.pill-dot { width: 8px; height: 8px; border-radius: 50%; }
.pill-label { font-size: 12px; font-weight: 600; color: var(--text-secondary); }
.pill-label b { color: var(--text-primary); margin-left: 4px; }

.available .pill-dot { background: var(--success); }
.occupied .pill-dot { background: var(--danger); box-shadow: 0 0 10px rgba(239, 68, 68, 0.4); }
.reserved .pill-dot { background: var(--warning); }

/* ── Grid ── */
.tables-grid {
  display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 24px;
}
.premium-table-card {
  background: var(--bg-card); border: 1px solid var(--border-color);
  border-radius: 28px; padding: 20px; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  display: flex; flex-direction: column; gap: 20px;
  animation: slideUp 0.5s ease both;
  position: relative; overflow: hidden;
}
.premium-table-card:hover { transform: translateY(-8px); border-color: var(--accent); box-shadow: 0 12px 30px -10px rgba(0,0,0,0.1); }

/* Decor Background for status */
.premium-table-card::after {
  content: ''; position: absolute; right: -20px; top: -20px; width: 80px; height: 80px;
  border-radius: 50%; background: currentColor; opacity: 0.03;
}

.t-card-top { display: flex; align-items: center; justify-content: space-between; position: relative; z-index: 10; }
.t-num-badge {
  font-size: 18px; font-weight: 700; color: var(--text-primary);
  background: var(--bg-primary); width: 44px; height: 44px; border-radius: 14px;
  display: flex; align-items: center; justify-content: center; border: 1px solid var(--border-color);
}
.t-actions { display: flex; gap: 8px; position: relative; z-index: 10; }
.t-action-btn {
  width: 32px; height: 32px; border-radius: 10px; border: 1px solid var(--border-color);
  background: var(--bg-card); color: var(--text-muted); cursor: pointer; transition: 0.2s;
  display: flex; align-items: center; justify-content: center;
  position: relative; z-index: 10;
}
.t-action-btn:hover { color: var(--accent); border-color: var(--accent); background: var(--accent-light); }
.t-action-btn.delete:hover { color: var(--danger); border-color: var(--danger); background: var(--danger-bg); }

.t-card-body { text-align: center; }
.t-main-icon {
  margin: 0 auto 16px; width: 64px; height: 64px; border-radius: 20px;
  background: var(--bg-primary); display: flex; align-items: center; justify-content: center;
  color: var(--text-secondary); transition: 0.3s;
}
.premium-table-card:hover .t-main-icon { transform: scale(1.1); color: var(--accent); }

.t-capacity-info { display: flex; flex-direction: column; margin-bottom: 20px; }
.cap-label { font-size: 10px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; }
.cap-value { font-size: 15px; font-weight: 700; color: var(--text-primary); }

.t-status-manage { 
  display: grid; 
  grid-template-columns: repeat(3, 1fr); 
  gap: 6px; 
}

.t-status-btn {
  height: 36px; 
  border-radius: 10px; 
  border: 1.5px solid var(--border-color);
  padding: 0 6px; 
  font-size: 10px; 
  font-weight: 700; 
  outline: none; 
  cursor: pointer;
  background: var(--bg-primary); 
  color: var(--text-muted); 
  transition: all 0.2s;
  display: flex; 
  align-items: center; 
  justify-content: center; 
  gap: 4px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.t-status-btn:hover { 
  border-color: var(--text-muted);
  transform: translateY(-1px);
}

.t-status-btn.active { 
  transform: translateY(0);
}

.t-status-btn.active:hover {
  transform: translateY(-1px);
}

.status-dot { 
  width: 6px; 
  height: 6px; 
  border-radius: 50%; 
  opacity: 0.4;
}

.t-status-btn.active .status-dot {
  opacity: 1;
  animation: pulse 2s infinite;
}

.status-dot.available { background: var(--success); }
.status-dot.occupied { background: var(--danger); }
.status-dot.reserved { background: var(--warning); }

.t-status-btn.active:has(.status-dot.available) { 
  border-color: rgba(34, 197, 94, 0.4); 
  background: var(--success-bg);
  color: var(--success);
}

.t-status-btn.active:has(.status-dot.occupied) { 
  border-color: rgba(239, 68, 68, 0.4); 
  background: var(--danger-bg);
  color: var(--danger);
}

.t-status-btn.active:has(.status-dot.reserved) { 
  border-color: rgba(234, 179, 8, 0.4); 
  background: var(--warning-bg);
  color: var(--warning);
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}

.t-card-footer {
  display: flex; align-items: center; justify-content: center; gap: 8px;
  padding-top: 16px; border-top: 1px solid var(--border-color);
}
.pulse-indicator { width: 8px; height: 8px; border-radius: 50%; position: relative; }
.status-tag-text { font-size: 11px; font-weight: 600; letter-spacing: 0.5px; color: var(--text-muted); }

/* Animation for pulses */
.occupied .pulse-indicator { background: var(--danger); }
.occupied .pulse-indicator::after { 
  content: ''; position: absolute; inset: -4px; border-radius: 50%; border: 2px solid var(--danger);
  animation: pulse 2s infinite; opacity: 0;
}
.available .pulse-indicator { background: var(--success); }
.reserved .pulse-indicator { background: var(--warning); }

.premium-table-card.available { color: var(--success); }
.premium-table-card.occupied { color: var(--danger); }
.premium-table-card.reserved { color: var(--warning); }

/* ── Modal ── */
.modal-backdrop {
  position: fixed; inset: 0; z-index: 200;
  background: rgba(0,0,0,0.6); backdrop-filter: blur(6px);
  display: flex; align-items: center; justify-content: center; padding: 20px;
}
.modal-panel {
  width: 100%; max-width: 480px; background: var(--bg-card);
  border-radius: 28px; border: 1px solid var(--border-color);
  box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); overflow: hidden;
}
.modal-top { display: flex; flex-direction: column; gap: 16px; padding: 24px; border-bottom: 1px solid var(--border-color); }
.modal-header-content { display: flex; align-items: center; gap: 16px; width: 100%; }
.modal-icon-wrap {
  width: 44px; height: 44px; border-radius: 12px; background: rgba(249,115,22,0.1); color: var(--accent);
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
.form-grid { display: flex; flex-direction: column; gap: 20px; }
.form-row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
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
.premium-input { 
  width: 100%; 
  padding: 10px 14px; 
  border-radius: 12px; 
  background: var(--bg-primary); 
  border: 1px solid var(--border-color); 
  color: var(--text-primary); 
  font-size: 13.5px; 
  font-weight: 500; 
  outline: none; 
  transition: all 0.2s;
}
.input-with-side { position: relative; }
.side-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-muted); }
.pl-10 { padding-left: 42px !important; }

.modal-bottom { display: flex; justify-content: center; padding: 20px 24px; border-top: 1px solid var(--border-color); }
.btn-save {
  width: 100%; padding: 12px 32px; border-radius: 12px; background: var(--accent); color: #fff; border: none; font-weight: 700; cursor: pointer;
  display: flex; align-items: center; justify-content: center; gap: 8px;
}

@keyframes pulse { 0% { transform: scale(1); opacity: 0.5; } 100% { transform: scale(2.5); opacity: 0; } }
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
@keyframes spin { to { transform: rotate(360deg); } }

.empty-placeholder { 
  text-align: center; 
  padding: 80px 20px; 
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}
.empty-icon { opacity: 0.2; margin-bottom: 20px; color: var(--text-muted); display: flex; justify-content: center; }

/* Responsive */
@media (max-width: 768px) {
  .tables-container { padding: 4px; }
  
  .page-hero { 
    flex-direction: column; 
    text-align: center; 
    gap: 16px; 
    padding: 24px 20px; 
    align-items: center; 
    margin-bottom: 24px;
  }
  .hero-content { flex-direction: column; text-align: center; }
  .hero-icon-wrap { width: 44px; height: 44px; border-radius: 12px; margin-bottom: 8px; }
  .hero-icon-wrap svg { width: 20px; height: 20px; }
  .hero-title { font-size: 18px; }
  .hero-subtitle { display: block; font-size: 13px; opacity: 0.8; margin-top: 4px; }
  .hero-actions { display: block; width: 100%; margin-top: 8px; }
  .hero-actions .btn-primary { 
    width: 100%; 
    justify-content: center; 
    height: 48px; 
    border-radius: 12px; 
  }
  
  .status-overview { 
    flex-wrap: wrap; 
    gap: 8px; 
    margin-bottom: 16px; 
  }
  .overview-pill { 
    flex: 1; 
    min-width: calc(50% - 4px); 
    justify-content: center; 
    padding: 10px 12px; 
  }
  .pill-label { font-size: 11px; }
  
  .tables-grid { grid-template-columns: 1fr; gap: 12px; }
  .premium-table-card { 
    border-radius: 20px; 
    padding: 16px; 
  }
  .t-num-badge { width: 40px; height: 40px; font-size: 16px; }
  .t-main-icon { width: 56px; height: 56px; margin-bottom: 12px; }
  .t-status-manage { gap: 6px; }
  .t-status-btn { height: 34px; font-size: 10px; padding: 0 6px; }

  .modal-backdrop { 
    align-items: flex-end; 
    padding: 0; 
    background: rgba(0,0,0,0.35);
    backdrop-filter: blur(2px);
  }
  .modal-panel { 
    max-width: 100% !important; 
    border-radius: 24px 24px 0 0; 
    overflow: hidden;
    box-shadow: 0 -10px 40px rgba(0,0,0,0.15);
  }
  .modal-top { 
    padding: 24px; 
    flex-direction: column;
    gap: 16px;
  }
  .btn-back-header { width: 36px; height: 36px; }
  
  .modal-content { 
    padding: 20px 24px; 
    max-height: 55vh; 
    overflow-y: auto; 
  }
  .modal-bottom { 
    padding: 16px 24px calc(16px + env(safe-area-inset-bottom));
    justify-content: center;
    background: var(--bg-primary);
  }
  .modal-bottom button { 
    height: 48px; 
    border-radius: 12px; 
    font-size: 14px; 
    justify-content: center;
  }
  .btn-cancel { display: none; }
  .btn-save { width: 100%; }
  .form-row-2 { grid-template-columns: 1fr; }
}

@media (max-width: 480px) {
  .hero-title { font-size: 16px; }
}
</style>
