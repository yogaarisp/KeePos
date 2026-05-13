<template>
  <div class="units-container">
    <!-- Header Hero Section -->
    <div class="page-hero">
      <div class="hero-content">
        <div class="hero-icon-wrap">
          <Scale :size="24" />
        </div>
        <div>
          <h1 class="hero-title">Satuan Ukur</h1>
          <p class="hero-subtitle">Kelola satuan pengukuran (Kg, Liter, Pcs, dll) untuk standarisasi bahan dan produk.</p>
        </div>
      </div>
      <div class="hero-actions">
        <button class="btn-primary" @click="openModal()">
          <Plus :size="18" /> Tambah Satuan
        </button>
      </div>
    </div>

    <!-- Main Content -->
    <div v-if="unitStore.loading && !unitStore.units.length" class="loading-state">
      <RefreshCw :size="40" class="spinning" />
      <p>Memuat satuan...</p>
    </div>

    <div v-else-if="unitStore.units.length" class="unit-grid">
      <div v-for="(unit, idx) in unitStore.units" :key="unit.id" 
           class="unit-card" :style="{ animationDelay: (idx * 0.05) + 's' }">
        <div class="card-body">
          <div class="unit-icon-wrap">
            <Ruler :size="24" />
          </div>
          <div class="unit-info">
            <h3 class="unit-name">{{ unit.name }}</h3>
            <div class="unit-abbrev-badge">{{ unit.abbreviation }}</div>
          </div>
        </div>
        <div class="card-footer">
          <div class="unit-id">ID: #{{ unit.id }}</div>
          <div class="card-actions">
            <button class="action-btn edit" @click="openModal(unit)" title="Edit">
              <Edit3 :size="15" />
            </button>
            <button class="action-btn delete" @click="handleDelete(unit.id)" title="Hapus">
              <Trash2 :size="15" />
            </button>
          </div>
        </div>
      </div>
    </div>

    <div v-else class="empty-placeholder">
      <div class="empty-icon">
        <Scale :size="48" />
      </div>
      <h3>Belum ada satuan ukur</h3>
      <p>Mulai tambahkan satuan seperti KG, Liter, atau PCS untuk bahan Anda.</p>
      <button class="btn-primary" @click="openModal()">
        <Plus :size="18" /> Tambah Satuan Pertama
      </button>
    </div>

    <!-- Unit Modal -->
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
                  <PlusCircle :size="20" />
                </div>
                <div class="modal-title-area">
                  <h3 class="modal-title">{{ modal.form.id ? 'Perbarui Satuan' : 'Tambah Satuan Baru' }}</h3>
                  <p class="modal-desc">Gunakan nama lengkap dan singkatan yang mudah dikenali.</p>
                </div>
              </div>
            </div>

            <div class="modal-content">
              <div class="form-grid">
                <div class="input-group">
                  <label class="input-label">Nama Lengkap Satuan</label>
                  <input type="text" v-model="modal.form.name" class="premium-input" placeholder="Contoh: Kilogram, Liter, Bungkus">
                </div>
                
                <div class="input-group">
                  <label class="input-label">Singkatan Satuan</label>
                  <input type="text" v-model="modal.form.abbreviation" class="premium-input" placeholder="Contoh: kg, ltr, bks">
                </div>
              </div>
            </div>

            <div class="modal-bottom">
              <button class="btn-save w-100 justify-content-center" @click="handleSave" :disabled="unitStore.loading || !modal.form.name || !modal.form.abbreviation">
                <Save :size="18" v-if="!unitStore.loading" />
                <RefreshCw :size="18" class="spinning" v-else />
                {{ unitStore.loading ? 'Menyimpan...' : (modal.form.id ? 'Perbarui Satuan' : 'Simpan Satuan') }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { onMounted, reactive, watch, onUnmounted } from 'vue';
import { useUnitStore } from '../stores/unit';
import { showConfirm, showSuccess, showError } from '../utils/swal';
import { 
  Scale, Plus, Edit3, Trash2, ArrowLeft, PlusCircle, 
  Ruler, Save, RefreshCw 
} from 'lucide-vue-next';

const unitStore = useUnitStore();

const modal = reactive({
  show: false,
  form: {
    id: null,
    name: '',
    abbreviation: ''
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
  unitStore.fetchUnits();
});

const openModal = (unit = null) => {
  if (unit) {
    modal.form = { ...unit };
  } else {
    modal.form = {
      id: null,
      name: '',
      abbreviation: ''
    };
  }
  modal.show = true;
};

const handleSave = async () => {
  const success = await unitStore.saveUnit(modal.form);
  if (success) {
    modal.show = false;
    showSuccess(modal.mode === 'add' ? 'Satuan berhasil ditambahkan!' : 'Satuan berhasil diperbarui!');
  } else {
    showError(unitStore.error || 'Gagal menyimpan satuan');
  }
};

const handleDelete = async (id) => {
  const result = await showConfirm({
    title: 'Hapus Satuan',
    text: 'Apakah Anda yakin ingin menghapus satuan ini?',
    icon: 'warning',
    confirmText: 'Ya, Hapus',
    cancelText: 'Batal'
  });
  
  if (result.isConfirmed) {
    const success = await unitStore.deleteUnit(id);
    if (success) {
      showSuccess('Satuan berhasil dihapus!');
    } else {
      showError(unitStore.error || 'Gagal menghapus satuan');
    }
  }
};
</script>

<style scoped>
.units-container { padding: 0; animation: fadeIn 0.4s ease; }

/* ── Hero ── */
.page-hero {
  display: flex; align-items: center; justify-content: space-between;
  background: linear-gradient(135deg, rgba(16, 185, 129, 0.08) 0%, rgba(16, 185, 129, 0.02) 100%);
  border: 1px solid rgba(16, 185, 129, 0.1); border-radius: 20px;
  padding: 28px 32px; margin-bottom: 24px;
}
.hero-content { display: flex; align-items: center; gap: 18px; }
.hero-icon-wrap {
  width: 54px; height: 54px; border-radius: 16px;
  background: linear-gradient(135deg, #10b981, #34d399);
  display: flex; align-items: center; justify-content: center;
  color: #fff; box-shadow: 0 8px 24px rgba(16, 185, 129, 0.25);
}
.hero-title { font-size: 22px; font-weight: 600; color: var(--text-primary); margin-bottom: 4px; }
.hero-subtitle { font-size: 14px; color: var(--text-secondary); font-weight: 500; }

.btn-primary {
  display: flex; align-items: center; gap: 10px;
  background: linear-gradient(135deg, var(--accent), #fb923c);
  color: #fff; border: none; padding: 12px 24px; border-radius: 12px;
  font-size: 14px; font-weight: 700; cursor: pointer;
  transition: all 0.3s; box-shadow: 0 4px 14px rgba(249,115,22,0.3);
}

/* ── Grid ── */
.unit-grid {
  display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;
}
.unit-card {
  background: var(--bg-card); border: 1px solid var(--border-color);
  border-radius: 20px; overflow: hidden; transition: all 0.3s;
  animation: slideUp 0.5s ease both;
  display: flex; flex-direction: column;
}
.unit-card:hover { transform: translateY(-5px); border-color: var(--accent); box-shadow: 0 10px 30px rgba(0,0,0,0.05); }

.card-body { padding: 24px; display: flex; align-items: center; gap: 16px; }
.unit-icon-wrap {
  width: 48px; height: 48px; border-radius: 12px;
  background: var(--accent-light); color: var(--accent);
  display: flex; align-items: center; justify-content: center;
}
.unit-info { flex: 1; }
.unit-name { font-size: 16px; font-weight: 700; color: var(--text-primary); margin-bottom: 4px; }
.unit-abbrev-badge {
  display: inline-block; padding: 4px 10px; border-radius: 8px;
  background: var(--bg-primary); font-size: 12px; font-weight: 800;
  color: var(--accent); border: 1.5px solid var(--border-color);
  text-transform: uppercase;
}

.card-footer {
  padding: 12px 20px; background: rgba(0,0,0,0.02);
  display: flex; align-items: center; justify-content: space-between;
  border-top: 1px solid var(--border-color);
}
.unit-id { font-size: 11px; font-weight: 700; color: var(--text-muted); }

.card-actions { display: flex; gap: 8px; }
.action-btn {
  width: 32px; height: 32px; border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  border: 1px solid var(--border-color); background: var(--bg-primary);
  color: var(--text-secondary); cursor: pointer; transition: all 0.2s;
}
.action-btn.edit:hover { background: var(--accent); color: #fff; border-color: var(--accent); }
.action-btn.delete:hover { background: var(--danger); color: #fff; border-color: var(--danger); }

/* ── Modal ── */
.modal-backdrop {
  position: fixed; inset: 0; z-index: 500;
  background: rgba(0,0,0,0.4); backdrop-filter: blur(8px);
  display: flex; align-items: center; justify-content: center; padding: 20px;
}
.modal-panel {
  width: 100%; max-width: 440px; background: var(--bg-card);
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

.modal-content { padding: 28px; }
.form-grid { display: flex; flex-direction: column; gap: 20px; }
.input-label { font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; margin-bottom: 8px; display: block; }
.premium-input {
  width: 100%; padding: 12px 16px; border-radius: 14px;
  background: var(--bg-primary); border: 1px solid var(--border-color);
  color: var(--text-primary); font-size: 14px; font-weight: 600; outline: none; transition: 0.2s;
}
.premium-input:focus { border-color: var(--accent); box-shadow: 0 0 0 4px var(--accent-light); }

.modal-bottom { display: flex; justify-content: center; gap: 12px; padding: 24px 28px; border-top: 1px solid var(--border-color); }
.modal-bottom button { flex: 1; }
.btn-cancel { padding: 12px 24px; border-radius: 14px; background: var(--bg-primary); border: 1px solid var(--border-color); color: var(--text-secondary); font-weight: 700; cursor: pointer; }
.btn-save {
  padding: 12px 28px; border-radius: 14px; background: var(--accent); color: #fff; border: none; font-weight: 700; cursor: pointer;
  display: flex; align-items: center; justify-content: center; gap: 10px; transition: 0.2s;
}
.btn-save:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 8px 20px rgba(249,115,22,0.3); }

.empty-placeholder { text-align: center; padding: 80px 20px; }
.loading-state { text-align: center; padding: 80px; color: var(--text-muted); }
.spinning { animation: spin 1s linear infinite; }

@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

/* Responsive */
@media (max-width: 768px) {
  .page-hero { flex-direction: column; text-align: center; gap: 16px; padding: 20px; align-items: stretch; margin-bottom: 16px; }
  .hero-content { align-items: center; text-align: center; flex-direction: column; }
  .hero-icon-wrap { width: 48px; height: 48px; border-radius: 14px; margin-bottom: 8px; }
  .hero-icon-wrap svg { width: 24px; height: 24px; }
  .hero-title { font-size: 20px; }
  .hero-subtitle { font-size: 13px; opacity: 0.8; margin-top: 4px; display: block; }
  .hero-actions { display: block; width: 100%; margin-top: 8px; }
  .hero-actions .btn-primary { width: 100%; justify-content: center; height: 48px; display: flex; align-items: center; gap: 10px; border-radius: 14px; font-weight: 700; cursor: pointer; background: var(--accent); color: #fff; border: none; }

  .unit-grid { grid-template-columns: 1fr; gap: 12px; }
  .unit-card { border-radius: 16px; }

  .modal-panel { 
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
  /* Modal is tucked under footer, so we use padding to push content above it */
  .modal-bottom { 
    padding: 16px 24px calc(16px + env(safe-area-inset-bottom));
    background: var(--bg-primary);
    justify-content: center;
  }
  .modal-bottom .btn-save {
    width: 100%;
    flex: 1;
    display: flex; align-items: center; justify-content: center; gap: 10px;
  }
}

@media (max-width: 480px) {
  .hero-title { font-size: 14px; }
  .unit-grid { gap: 10px; }
}
</style>
