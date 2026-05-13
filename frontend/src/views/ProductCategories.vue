<template>
  <div class="categories-container">
    <!-- Header Hero Section -->
    <div class="page-hero">
      <div class="hero-content">
        <div class="hero-icon-wrap">
          <Tag :size="24" />
        </div>
        <div>
          <h1 class="hero-title">Kategori Menu</h1>
          <p class="hero-subtitle">Kelola kategori untuk mengelompokkan menu masakan Anda.</p>
        </div>
      </div>
      <div class="hero-actions">
        <button class="btn-primary" @click="openModal()">
          <Plus :size="18" /> Tambah Kategori
        </button>
      </div>
    </div>

    <!-- Main Content -->
    <div v-if="prodStore.loading && !prodStore.categories.length" class="loading-state">
      <RefreshCw :size="40" class="spinning" />
      <p>Memuat kategori...</p>
    </div>

    <div v-else-if="prodStore.categories.length" class="category-grid">
      <div v-for="(cat, idx) in prodStore.categories" :key="cat.id" 
           class="category-card" :style="{ animationDelay: (idx * 0.05) + 's' }">
        <div class="card-body">
          <div class="cat-icon-wrap">
            <component :is="getIcon(cat.icon)" :size="24" />
          </div>
          <div class="cat-info">
            <h3 class="cat-name">{{ cat.name }}</h3>
            <p class="cat-count">{{ cat.products_count || 0 }} Produk</p>
          </div>
          <div class="cat-badge" :class="{ inactive: !cat.is_active }">
            {{ cat.is_active ? 'Aktif' : 'Nonaktif' }}
          </div>
        </div>
        <div class="card-footer">
          <div class="sort-order">Urutan: {{ cat.sort_order }}</div>
          <div class="card-actions">
            <button class="action-btn edit" @click="openModal(cat)" title="Edit">
              <Edit3 :size="15" />
            </button>
            <button class="action-btn delete" @click="handleDelete(cat.id)" title="Hapus">
              <Trash2 :size="15" />
            </button>
          </div>
        </div>
      </div>
    </div>

    <div v-else class="empty-placeholder">
      <div class="empty-icon">
        <Tag :size="48" />
      </div>
      <h3>Belum ada kategori</h3>
      <p>Mulai tambahkan kategori untuk merapikan daftar menu Anda.</p>
      <button class="btn-primary" @click="openModal()">
        <Plus :size="18" /> Tambah Kategori Pertama
      </button>
    </div>

    <!-- Category Modal -->
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
                  <component :is="getIcon(modal.form.icon)" :size="20" />
                </div>
                <div class="modal-title-area">
                  <h3 class="modal-title">{{ modal.form.id ? 'Perbarui Kategori' : 'Tambah Kategori Baru' }}</h3>
                  <p class="modal-desc">Detail informasi kategori produk.</p>
                </div>
              </div>
            </div>

            <div class="modal-content">
              <div class="form-grid">
                <div class="input-group">
                  <label class="input-label">Nama Kategori</label>
                  <input type="text" v-model="modal.form.name" class="premium-input" placeholder="Contoh: Makanan Berat">
                </div>
                
                <div class="form-row-2">
                  <div class="input-group">
                    <label class="input-label">Icon (Lucide Name)</label>
                    <input type="text" v-model="modal.form.icon" class="premium-input" placeholder="Utensils, Coffee, etc.">
                  </div>
                  <div class="input-group">
                    <label class="input-label">Urutan Tampil</label>
                    <input type="number" v-model="modal.form.sort_order" class="premium-input">
                  </div>
                </div>

                <div class="toggle-group-premium">
                  <div class="toggle-info">
                    <span class="toggle-label-main">Status Kategori</span>
                    <span class="toggle-desc">{{ modal.form.is_active ? 'Kategori Aktif & Muncul di POS' : 'Kategori Dinonaktifkan' }}</span>
                  </div>
                  <label class="switch">
                    <input type="checkbox" v-model="modal.form.is_active">
                    <span class="slider"></span>
                  </label>
                </div>
              </div>
            </div>

            <div class="modal-bottom">
              <button class="btn-save" @click="save" :disabled="prodStore.loading">
                <Save :size="18" v-if="!prodStore.loading" />
                <RefreshCw :size="18" class="spinning" v-else />
                {{ prodStore.loading ? 'Menyimpan...' : (modal.form.id ? 'Perbarui Kategori' : 'Simpan Kategori') }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref, watch, onUnmounted } from 'vue';
import { useProductStore } from '../stores/product';
import { 
  Plus, Tag, Edit3, Trash2, RefreshCw, ArrowLeft, Save, 
  Utensils, Coffee, Sandwich, Pizza, Wine, IceCream
} from 'lucide-vue-next';

const prodStore = useProductStore();

const modal = reactive({
  show: false,
  form: {
    id: null,
    name: '',
    icon: 'Utensils',
    sort_order: 0,
    is_active: true
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

const icons = {
  Utensils, Coffee, Sandwich, Pizza, Wine, IceCream, Tag
};

const getIcon = (name) => {
  return icons[name] || icons.Tag;
};

onMounted(() => {
  prodStore.fetchCategories();
});

const openModal = (cat = null) => {
  if (cat) {
    modal.form = { ...cat };
  } else {
    modal.form = {
      id: null,
      name: '',
      icon: 'Utensils',
      sort_order: prodStore.categories.length + 1,
      is_active: true
    };
  }
  modal.show = true;
};

const handleDelete = async (id) => {
  await prodStore.deleteCategory(id);
};

const save = async () => {
  const success = await prodStore.saveCategory(modal.form);
  if (success) {
    modal.show = false;
  }
};
</script>

<style scoped>
.categories-container { padding: 0; animation: fadeIn 0.4s ease; }

/* ── Hero ── */
.page-hero {
  display: flex; align-items: center; justify-content: space-between;
  background: linear-gradient(135deg, rgba(249,115,22,0.08) 0%, rgba(249,115,22,0.02) 100%);
  border: 1px solid rgba(249,115,22,0.1); border-radius: 20px;
  padding: 28px 32px; margin-bottom: 24px;
}
.hero-content { display: flex; align-items: center; gap: 18px; }
.hero-icon-wrap {
  width: 54px; height: 54px; border-radius: 16px;
  background: linear-gradient(135deg, var(--accent), #fb923c);
  display: flex; align-items: center; justify-content: center;
  color: #fff; box-shadow: 0 8px 24px rgba(249,115,22,0.25);
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
.category-grid {
  display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;
}
.category-card {
  background: var(--bg-card); border: 1px solid var(--border-color);
  border-radius: 20px; overflow: hidden; transition: all 0.3s;
  animation: slideUp 0.5s ease both;
}
.category-card:hover { transform: translateY(-5px); border-color: var(--accent); }

.card-body { padding: 24px; display: flex; align-items: center; gap: 16px; position: relative; }
.cat-icon-wrap {
  width: 48px; height: 48px; border-radius: 12px;
  background: var(--accent-light); color: var(--accent);
  display: flex; align-items: center; justify-content: center;
}
.cat-info { flex: 1; }
.cat-name { font-size: 16px; font-weight: 600; color: var(--text-primary); margin-bottom: 2px; }
.cat-count { font-size: 12px; color: var(--text-muted); font-weight: 500; }

.cat-badge {
  padding: 4px 10px; border-radius: 100px; font-size: 10px; font-weight: 700;
  background: var(--success-bg); color: var(--success);
}
.cat-badge.inactive { background: var(--danger-bg); color: var(--danger); }

.card-footer {
  padding: 12px 20px; background: rgba(0,0,0,0.02);
  display: flex; align-items: center; justify-content: space-between;
  border-top: 1px solid var(--border-color);
}
.sort-order { font-size: 11px; font-weight: 600; color: var(--text-muted); }

.card-actions { display: flex; gap: 8px; }
.action-btn {
  width: 30px; height: 30px; border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  border: 1px solid var(--border-color); background: var(--bg-primary);
  color: var(--text-secondary); cursor: pointer; transition: all 0.2s;
}
.action-btn.edit:hover { background: var(--accent); color: #fff; border-color: var(--accent); }
.action-btn.delete:hover { background: var(--danger); color: #fff; border-color: var(--danger); }

/* ── Modal ── */
.modal-backdrop {
  position: fixed; inset: 0; z-index: 200;
  background: rgba(0,0,0,0.6); backdrop-filter: blur(6px);
  display: flex; align-items: center; justify-content: center; padding: 20px;
}
.modal-panel {
  width: 100%; max-width: 500px; background: var(--bg-card);
  border-radius: 24px; border: 1px solid var(--border-color);
  box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
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
.form-grid { display: flex; flex-direction: column; gap: 16px; }
.form-row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.input-label { 
  font-size: 11px; 
  font-weight: 700; 
  color: var(--text-muted); 
  text-transform: uppercase; 
  letter-spacing: 0.5px;
  margin-bottom: 2px;
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
.premium-input:focus { 
  border-color: var(--accent); 
  box-shadow: 0 0 0 4px var(--accent-light);
}

.toggle-group-premium {
  display: flex; align-items: center; justify-content: space-between;
  padding: 14px; background: var(--bg-primary); border-radius: 12px; border: 1px solid var(--border-color);
}
.toggle-label-main { display: block; font-size: 13px; font-weight: 700; }
.toggle-desc { display: block; font-size: 11px; color: var(--text-muted); }

.switch { position: relative; width: 44px; height: 24px; cursor: pointer; }
.switch input { opacity: 0; width: 0; height: 0; }
.slider { position: absolute; inset: 0; background: var(--border-color); border-radius: 12px; transition: 0.3s; }
.slider:before { content: ""; position: absolute; height: 18px; width: 18px; left: 3px; bottom: 3px; background: white; border-radius: 50%; transition: 0.3s; }
input:checked + .slider { background: var(--success); }
input:checked + .slider:before { transform: translateX(20px); }

.modal-bottom { display: flex; justify-content: center; gap: 12px; padding: 24px 28px; border-top: 1px solid var(--border-color); }
.modal-bottom button { flex: 1; }
.btn-cancel { padding: 12px 24px; border-radius: 14px; background: var(--bg-primary); border: 1px solid var(--border-color); color: var(--text-secondary); font-weight: 700; cursor: pointer; }
.btn-save {
  padding: 12px 28px; border-radius: 14px; background: var(--accent); color: #fff; border: none; font-weight: 700; cursor: pointer;
  display: flex; align-items: center; justify-content: center; gap: 10px;
  min-width: 200px;
}

.empty-placeholder { 
  text-align: center; 
  padding: 80px 20px; 
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}
.loading-state { text-align: center; padding: 60px; color: var(--text-muted); }
.spinning { animation: spin 1s linear infinite; margin-bottom: 12px; }

@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

/* Responsive */
@media (max-width: 768px) {
  .page-hero { 
    flex-direction: column; 
    text-align: center; 
    gap: 16px; 
    padding: 24px 20px; 
    align-items: center; 
    margin-bottom: 24px;
  }
  .hero-content { flex-direction: column; text-align: center; }
  .hero-actions { display: block; width: 100%; }
  .hero-actions .btn-primary { width: 100%; justify-content: center; }
  .category-grid { grid-template-columns: 1fr; gap: 12px; }
  .category-card { border-radius: 16px; }

  /* Modal Mobile */
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
    z-index: 1100;
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
}

@media (max-width: 480px) {
  .hero-title { font-size: 14px; }
  .category-grid { gap: 10px; }
}
</style>
