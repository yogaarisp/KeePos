<template>
  <div class="products-container">
    <!-- Header Hero Section -->
    <div class="page-hero">
      <div class="hero-content">
        <div class="hero-icon-wrap">
          <Utensils :size="24" />
        </div>
        <div>
          <h1 class="hero-title">Daftar Menu</h1>
          <p class="hero-subtitle">Kelola menu masakan, harga, dan ketersediaan stok Anda.</p>
        </div>
      </div>
      <div class="hero-actions">
        <div v-if="!canAddProduct" class="limit-warning-badge">
          <ShieldAlert :size="14" />
          <span>Menu penuh</span>
        </div>
        <button 
          :class="['btn-primary', { 'btn-disabled': !canAddProduct }]" 
          @click="canAddProduct ? openModal() : null"
          :title="!canAddProduct ? 'Kuota menu Anda sudah penuh' : ''"
        >
          <Plus :size="18" /> Tambah Menu Baru
        </button>
      </div>
    </div>

    <!-- Filter & search bar -->
    <div class="filter-glass-bar">
      <div class="search-box">
        <Search :size="18" class="search-icon" />
        <input 
          type="text" 
          v-model="prodStore.filters.search" 
          placeholder="Cari menu favorit..." 
          @input="debouncedSearch"
        >
      </div>
      <div class="filter-group">
        <select v-model="prodStore.filters.category_id" @change="refresh" class="filter-select">
          <option value="">Semua Kategori</option>
          <option v-for="cat in prodStore.categories" :key="cat.id" :value="cat.id">
            {{ cat.name }}
          </option>
        </select>
        <button class="btn-refresh" @click="refresh" :class="{ spinning: prodStore.loading }">
          <RefreshCw :size="18" />
        </button>
      </div>
    </div>

    <!-- Main Content -->
    <div v-if="prodStore.loading && !prodStore.products.length" class="loading-grid">
      <div v-for="i in 8" :key="i" class="skeleton-card"></div>
    </div>

    <div v-else-if="prodStore.products.length" class="menu-grid">
      <div v-for="(product, idx) in prodStore.products" :key="product.id" 
           class="menu-card" :style="{ animationDelay: (idx * 0.05) + 's' }">
        <div class="card-image">
          <img v-if="product.image" :src="'/storage/' + product.image" :alt="product.name" loading="lazy">
          <div v-else class="image-empty">
            <Utensils :size="40" />
          </div>
        </div>
        
        <div class="card-content">
          <div class="card-meta">
            <span class="category-tag">{{ product.category?.name || 'Lainnya' }}</span>
          </div>
          <h3 class="card-name">{{ product.name }}</h3>
          <div class="card-footer">
            <div class="price-wrap">
              <span class="price-label">Harga</span>
              <span class="price-val">{{ formatCurrency(product.price) }}</span>
            </div>
            <div class="card-actions">
              <button class="action-btn edit" @click="openModal(product)" title="Edit Menu">
                <Edit3 :size="15" />
              </button>
              <button class="action-btn delete" @click="handleDelete(product.id)" title="Hapus Menu">
                <Trash2 :size="15" />
              </button>
            </div>
          </div>
        </div>

        <!-- Position Badge at Top Right -->
        <div class="card-badge" :class="{ unavailable: !product.is_available }">
          <span class="dot"></span>
          {{ product.is_available ? 'Tersedia' : 'Habis' }}
        </div>
      </div>
    </div>

    <div v-else class="empty-placeholder">
      <div class="empty-icon">
        <ChefHat :size="48" />
      </div>
      <h3>Menu belum tersedia</h3>
      <p>Mulai tambahkan menu masakan terbaik Anda hari ini.</p>
      <button 
        :class="['btn-primary', { 'btn-disabled': !canAddProduct }]" 
        @click="canAddProduct ? openModal() : null"
        :title="!canAddProduct ? 'Kuota menu Anda sudah penuh' : ''"
      >
        <Plus :size="18" /> {{ !canAddProduct ? 'Kuota Penuh' : 'Tambah Menu Pertama' }}
      </button>
    </div>

    <!-- Modern Product Modal -->
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
                  <Utensils :size="20" />
                </div>
                <div class="modal-title-area">
                  <h3 class="modal-title">{{ modal.form.id ? 'Perbarui Menu' : 'Tambah Menu Baru' }}</h3>
                  <p class="modal-desc">{{ modal.form.id ? 'Ubah informasi menu yang sudah ada.' : 'Masukkan detail menu masakan baru Anda.' }}</p>
                </div>
              </div>
            </div>

            <div class="modal-content">
              <!-- Image Upload -->
              <div class="upload-section">
                <div class="upload-preview" @click="$refs.fileInput.click()">
                  <img v-if="imagePreview || modal.form.image" :src="imagePreview || baseUrl + '/storage/' + modal.form.image" class="full-img">
                  <div v-else class="upload-placeholder">
                    <Camera :size="32" />
                    <span>Upload Foto Menu</span>
                  </div>
                  <input type="file" ref="fileInput" @change="handleFile" hidden accept="image/*">
                </div>
                <div class="upload-info">
                  <label class="input-label">Foto Menu</label>
                  <p>Gunakan foto berkualitas tinggi (JPG/PNG, Maks 2MB).</p>
                  <button class="btn-secondary-sm" @click="$refs.fileInput.click()">
                    <Upload :size="14" /> {{ (imagePreview || modal.form.image) ? 'Ganti Foto' : 'Pilih Foto' }}
                  </button>
                </div>
              </div>

              <div class="divider"></div>

              <!-- Form Fields -->
              <div class="form-grid">
                <div class="input-group">
                  <label class="input-label">Nama Menu</label>
                  <input type="text" v-model="modal.form.name" class="premium-input" placeholder="Contoh: Ayam Goreng Kremes">
                </div>
                
                <div class="form-row-2">
                  <div class="input-group">
                    <label class="input-label">Harga (Rp)</label>
                    <div class="currency-input">
                      <span class="curr-symbol">Rp</span>
                      <input type="number" v-model="modal.form.price" class="premium-input pl-10" placeholder="0">
                    </div>
                  </div>
                  <div class="input-group">
                    <label class="input-label">Kategori</label>
                    <select v-model="modal.form.category_id" class="premium-input">
                      <option value="">Pilih Kategori</option>
                      <option v-for="cat in prodStore.categories" :key="cat.id" :value="cat.id">
                        {{ cat.name }}
                      </option>
                    </select>
                  </div>
                </div>

                <div class="form-row-2">
                  <div class="toggle-group-premium full-width">
                    <div class="toggle-info">
                      <span class="toggle-label-main">Status Stok</span>
                      <span class="toggle-desc">{{ modal.form.is_available ? 'Menu Tersedia' : 'Menu Habis' }}</span>
                    </div>
                    <label class="switch">
                      <input type="checkbox" v-model="modal.form.is_available">
                      <span class="slider"></span>
                    </label>
                  </div>
                </div>
              </div>
            </div>

            <div class="modal-bottom">
              <button class="btn-save" @click="save" :disabled="prodStore.loading">
                <Save :size="18" v-if="!prodStore.loading" />
                <RefreshCw :size="18" class="spinning" v-else />
                {{ prodStore.loading ? 'Menyimpan...' : (modal.form.id ? 'Perbarui Menu' : 'Simpan Menu') }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref, markRaw, watch, onUnmounted, computed } from 'vue';
import { useProductStore } from '../stores/product';
import { useAuthStore } from '../stores/auth';
import { showConfirm, showSuccess, showError } from '../utils/swal';
import { 
  Plus, Search, Utensils, Edit3, Trash2, LayoutGrid, RefreshCw, 
  ArrowLeft, Camera, Upload, Save, ChefHat, ShieldAlert
} from 'lucide-vue-next';
import { baseUrl } from '../api';

const authStore = useAuthStore();

const prodStore = useProductStore();
const imagePreview = ref(null);
const imageFile = ref(null);

const modal = reactive({
  show: false,
  form: {
    id: null,
    name: '',
    price: 0,
    category_id: '',
    image: '',
    is_available: true
  }
});

const canAddProduct = computed(() => {
  const tenant = authStore.user?.tenant;
  if (!tenant) return true;
  
  const plan = tenant.plan || 'free';
  let limit = 10;
  if (plan === 'basic') limit = 100;
  if (plan === 'pro') return true;
  
  return prodStore.products.length < limit;
});

const refresh = () => prodStore.fetchProducts();
const reset = () => {
  prodStore.filters.search = '';
  prodStore.filters.category_id = '';
  refresh();
};

let searchTimeout;
const debouncedSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(refresh, 500);
};

onMounted(() => {
  prodStore.fetchCategories();
  refresh();
});

const openModal = (product = null) => {
  imagePreview.value = null;
  imageFile.value = null;
  
  if (product) {
    modal.form = { ...product };
  } else {
    modal.form = {
      id: null,
      name: '',
      price: 0,
      category_id: '',
      image: '',
      is_available: true
    };
  }
  modal.show = true;
};

const handleFile = (e) => {
  const file = e.target.files[0];
  if (file) {
    imageFile.value = file;
    imagePreview.value = URL.createObjectURL(file);
  }
};

const handleDelete = async (id) => {
  const result = await showConfirm({
    title: 'Hapus Menu',
    text: 'Hapus menu ini secara permanen?',
    icon: 'warning',
    confirmText: 'Ya, Hapus',
    cancelText: 'Batal'
  });
  
  if (result.isConfirmed) {
    const success = await prodStore.deleteProduct(id);
    if (success) {
      showSuccess('Menu berhasil dihapus!');
    }
  }
};

const save = async () => {
  if (!modal.form.name || !modal.form.price || !modal.form.category_id) {
    showError('Mohon lengkapi semua field yang wajib diisi');
    return;
  }

  const formData = new FormData();
  const data = { ...modal.form };
  delete data.image; // handled separately
  delete data.category; // remove relation object
  
  Object.keys(data).forEach(key => {
    if (data[key] !== null && data[key] !== undefined) {
      formData.append(key, data[key]);
    }
  });
  
  if (imageFile.value) {
    formData.append('image', imageFile.value);
  }

  const isEdit = !!modal.form.id;
  const success = await prodStore.saveProduct(formData, modal.form.id);
  
  if (success) {
    modal.show = false;
    showSuccess(isEdit ? 'Menu berhasil diperbarui!' : 'Menu berhasil ditambahkan!');
  } else {
    showError(prodStore.error || 'Gagal menyimpan menu');
  }
};

const formatCurrency = (val) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
  }).format(val);
};

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
</script>

<style scoped>
.products-container { padding: 0; animation: fadeIn 0.4s ease; }

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

.btn-primary.btn-disabled {
  background: var(--border-color);
  cursor: not-allowed;
  opacity: 0.7;
  box-shadow: none;
}
.btn-primary.btn-disabled:hover { transform: none; }

.limit-warning-badge {
  display: flex;
  align-items: center;
  gap: 6px;
  background: rgba(239, 68, 68, 0.1);
  color: #ef4444;
  padding: 8px 14px;
  border-radius: 10px;
  font-size: 11px;
  font-weight: 700;
  border: 1px solid rgba(239, 68, 68, 0.2);
}

.hero-actions { display: flex; align-items: center; gap: 12px; }

/* ── Filter Bar ── */
.filter-glass-bar {
  display: flex; align-items: center; gap: 16px;
  background: var(--bg-card); border: 1px solid var(--border-color);
  padding: 12px 16px; border-radius: 18px; margin-bottom: 24px;
}
.search-box {
  flex: 1; position: relative;
}
.search-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-muted); }
.search-box input {
  width: 100%; height: 44px; padding-left: 44px;
  background: var(--bg-primary); border: 1px solid var(--border-color);
  border-radius: 14px; font-size: 14px; color: var(--text-primary); outline: none;
  transition: border-color 0.2s;
}
.search-box input:focus { border-color: var(--accent); }

.filter-group { display: flex; align-items: center; gap: 10px; }
.filter-pill-label {
  display: flex; align-items: center; gap: 6px;
  font-size: 12px; font-weight: 700; color: var(--text-muted);
  text-transform: uppercase; letter-spacing: 0.5px;
}
.filter-select {
  height: 44px; padding: 0 16px; border-radius: 14px;
  background: var(--bg-primary); border: 1px solid var(--border-color);
  color: var(--text-primary); font-weight: 600; font-size: 13px; outline: none; cursor: pointer;
}

.btn-refresh {
  width: 44px; height: 44px; border-radius: 14px;
  background: var(--bg-primary); border: 1px solid var(--border-color);
  color: var(--text-secondary); cursor: pointer; transition: all 0.2s;
  display: flex; align-items: center; justify-content: center;
}
.btn-refresh:hover { border-color: var(--accent); color: var(--accent); }
.spinning { animation: spin 1s linear infinite; }

/* ── Menu Grid ── */
.menu-grid {
  display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 24px;
}
.menu-card {
  background: var(--bg-card); border: 1px solid var(--border-color);
  border-radius: 24px; overflow: hidden; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  display: flex; flex-direction: column; animation: slideUp 0.5s ease both;
  position: relative;
}
.menu-card:hover { transform: translateY(-8px); border-color: var(--accent); box-shadow: 0 12px 30px -10px rgba(0,0,0,0.1); }

.card-image {
  aspect-ratio: 4/3; background: var(--bg-primary); position: relative; overflow: hidden;
}
.card-image img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s; }
.menu-card:hover .card-image img { transform: scale(1.1); }
.image-empty { height: 100%; display: flex; align-items: center; justify-content: center; color: var(--text-muted); opacity: 0.3; }

.card-badge {
  position: absolute; top: 12px; left: 12px;
  z-index: 10;
  padding: 4px 12px; border-radius: 100px; font-size: 11px; font-weight: 600;
  background: var(--success-bg); color: var(--success);
  display: flex; align-items: center; gap: 6px; backdrop-filter: blur(8px);
}
.card-badge.unavailable { background: var(--danger-bg); color: var(--danger); }
.dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }

.card-content { padding: 20px; flex: 1; display: flex; flex-direction: column; }
.card-meta { display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; }
.category-tag { font-size: 10px; font-weight: 600; color: var(--accent); text-transform: uppercase; letter-spacing: 0.5px; }
.sort-id { font-size: 11px; color: var(--text-muted); font-weight: 700; }
.card-name { font-size: 16px; font-weight: 600; color: var(--text-primary); margin-bottom: 16px; }

.card-footer {
  margin-top: auto; display: flex; align-items: center; justify-content: space-between;
  padding-top: 16px; border-top: 1px solid var(--border-color);
}
.price-wrap { display: flex; flex-direction: column; }
.price-label { font-size: 10px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; }
.price-val { font-size: 16px; font-weight: 700; color: var(--text-primary); }

.card-actions { display: flex; gap: 8px; }
.action-btn {
  width: 32px; height: 32px; border-radius: 10px;
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
  width: 100%; max-width: 580px; background: var(--bg-card);
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

/* Upload Area */
.upload-section { display: flex; align-items: center; gap: 24px; }
.upload-preview {
  width: 120px; height: 120px; border-radius: 20px; background: var(--bg-primary);
  border: 2.5px dashed var(--border-color); overflow: hidden; cursor: pointer;
  display: flex; align-items: center; justify-content: center; position: relative; transition: all 0.2s;
}
.upload-preview:hover { border-color: var(--accent); }
.full-img { width: 100%; height: 100%; object-fit: cover; }
.upload-placeholder { display: flex; flex-direction: column; align-items: center; gap: 8px; color: var(--text-muted); font-size: 10px; font-weight: 700; text-transform: uppercase; }
.upload-info h4 { font-size: 14px; font-weight: 600; margin-bottom: 4px; }
.upload-info p { font-size: 12px; color: var(--text-muted); margin-bottom: 12px; line-height: 1.5; }
.btn-secondary-sm {
  background: rgba(255, 255, 255, 0.03);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  color: var(--text-secondary);
  border: 1px solid var(--border-color);
  padding: 8px 16px; border-radius: 10px;
  font-size: 12px; font-weight: 700; cursor: pointer;
  display: flex; align-items: center; gap: 6px;
  transition: all 0.2s;
}
.btn-secondary-sm:hover {
  background: rgba(255, 255, 255, 0.08);
  border-color: var(--accent);
  color: var(--accent);
}
:root.light .btn-secondary-sm { background: rgba(0, 0, 0, 0.02); }
:root.light .btn-secondary-sm:hover { background: rgba(0, 0, 0, 0.05); }

.divider { height: 1px; background: var(--border-color); margin: 24px 0; }

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
.premium-input:focus { 
  border-color: var(--accent); 
  box-shadow: 0 0 0 4px var(--accent-light); 
}

.currency-input { position: relative; }
.curr-symbol { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); font-weight: 600; color: var(--text-muted); font-size: 13px; }
.pl-10 { padding-left: 42px !important; }

/* Toggle Switch Group */
.toggle-group-premium {
  display: flex; align-items: center; justify-content: space-between;
  padding: 14px 18px; background: var(--bg-primary); border-radius: 14px; border: 1px solid var(--border-color);
}
.toggle-group-premium.full-width { grid-column: span 2; }
@media (max-width: 768px) {
  .toggle-group-premium.full-width { grid-column: span 1; }
}
.toggle-label-main { display: block; font-size: 13px; font-weight: 700; color: var(--text-primary); }
.toggle-desc { display: block; font-size: 11px; color: var(--text-muted); font-weight: 500; }

.switch { position: relative; width: 44px; height: 24px; cursor: pointer; }
.switch input { opacity: 0; width: 0; height: 0; }
.slider { position: absolute; inset: 0; background: var(--border-color); border-radius: 12px; transition: 0.3s; }
.slider:before { content: ""; position: absolute; height: 18px; width: 18px; left: 3px; bottom: 3px; background: white; border-radius: 50%; transition: 0.3s; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
input:checked + .slider { background: var(--success); }
input:checked + .slider:before { transform: translateX(20px); }

.modal-bottom { display: flex; justify-content: center; gap: 12px; padding: 24px 28px; border-top: 1px solid var(--border-color); }
.modal-bottom button { flex: 1; }
.btn-cancel {
  background: rgba(255, 255, 255, 0.03);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  color: var(--text-secondary);
  border: 1.5px solid var(--border-color);
  padding: 12px 24px; border-radius: 14px;
  font-size: 14px; font-weight: 700; cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.btn-cancel:hover {
  background: rgba(255, 255, 255, 0.06);
  border-color: var(--accent);
  color: var(--accent);
  transform: translateY(-2px);
  box-shadow: 0 8px 20px -10px rgba(0, 0, 0, 0.3);
}
:root.light .btn-cancel { background: rgba(0, 0, 0, 0.02); }
:root.light .btn-cancel:hover { background: rgba(0, 0, 0, 0.05); }
.btn-save {
  padding: 12px 28px; border-radius: 14px; background: var(--accent); color: #fff; border: none; font-weight: 700; cursor: pointer;
  display: flex; align-items: center; justify-content: center; gap: 10px; transition: all 0.2s;
}
.btn-save:hover { background: var(--accent-hover); transform: translateY(-1px); }

/* Placeholder */
.empty-placeholder { text-align: center; padding: 80px 20px; }
.empty-icon { width: 80px; height: 80px; border-radius: 50%; background: var(--bg-primary); display: flex; align-items: center; justify-content: center; color: var(--text-muted); margin: 0 auto 20px; }
.empty-placeholder h3 { font-size: 18px; font-weight: 600; margin-bottom: 8px; }
.empty-placeholder p { color: var(--text-muted); margin-bottom: 24px; }

/* Animations */
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

/* Responsive */
@media (max-width: 768px) {
  .products-container { padding: 4px; overflow-x: hidden; }
  
  .page-hero { 
    flex-direction: column; 
    text-align: center; 
    gap: 16px; 
    padding: 24px 20px; 
    align-items: stretch; 
    margin-bottom: 16px; 
  }
  .hero-content { align-items: center; text-align: center; flex-direction: column; }
  .hero-icon-wrap { width: 48px; height: 48px; border-radius: 14px; margin-bottom: 8px; }
  .hero-icon-wrap svg { width: 24px; height: 24px; }
  .hero-title { font-size: 20px; }
  .hero-subtitle { display: block; font-size: 13px; opacity: 0.8; margin-top: 4px; }
  .hero-actions { display: block; width: 100%; margin-top: 8px; }
  .hero-actions .btn-primary { 
    width: 100%; 
    justify-content: center; 
    height: 50px; 
    display: flex; 
    align-items: center; 
    gap: 10px; 
    border-radius: 14px; 
    font-weight: 700; 
    font-size: 15px;
  }

  .filter-glass-bar { 
    flex-direction: column; 
    align-items: stretch; 
    gap: 8px; 
    padding: 8px; 
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 16px; 
    margin-bottom: 16px; 
    box-shadow: 0 4px 12px rgba(0,0,0,0.03);
  }
  .search-box { width: 100%; }
  .search-box input { height: 40px; border-radius: 12px; font-size: 13px; }
  .filter-group { 
    display: flex; 
    gap: 8px; 
    width: 100%;
  }
  .filter-pill-label { display: none; }
  .filter-select { flex: 1; height: 40px; font-size: 12px; border-radius: 12px; }
  .btn-refresh { width: 40px; height: 40px; border-radius: 12px; flex-shrink: 0; }
  .menu-grid { grid-template-columns: 1fr; gap: 12px; }
  .menu-card { 
    flex-direction: row; 
    height: auto; 
    min-height: 110px;
    padding: 12px;
    gap: 16px;
    border-radius: 20px;
  }
  .card-image { width: 90px; height: 90px; border-radius: 14px; flex-shrink: 0; aspect-ratio: 1/1; }
  .card-content { padding: 0; flex: 1; display: flex; flex-direction: column; justify-content: center; }
  .card-meta { margin-bottom: 4px; }
  .card-name { font-size: 15px; margin-bottom: 8px; line-height: 1.3; color: var(--text-primary); }
  .card-footer { border-top: none; padding-top: 0; margin-top: auto; }
  .price-label { display: none; }
  .price-val { font-size: 16px; }
  .card-badge { 
    position: absolute; 
    top: 12px;
    right: 12px;
    left: auto;
    padding: 3px 10px; 
    font-size: 9px; 
    backdrop-filter: none;
    z-index: 20;
  }
  .form-row-2 { 
    grid-template-columns: 1fr; 
    gap: 16px; 
  }
  
  .upload-section { flex-direction: column; text-align: center; gap: 16px; }
  .upload-preview { width: 120px; height: 120px; margin: 0 auto; }
  .upload-info p { display: none; }
}

@media (max-width: 480px) {
  .hero-title { font-size: 18px; }
  .card-image { width: 80px; height: 80px; }
}
</style>

<style>
/* Responsive for Teleported Modals in Products.vue */
@media (max-width: 768px) {
  .modal-backdrop { 
    align-items: flex-end; 
    padding: 0; 
    z-index: 500;
    background: rgba(0,0,0,0.35) !important;
    backdrop-filter: blur(2px) !important;
  }
  .modal-panel { 
    max-width: 100% !important; 
    border-radius: 24px 24px 0 0 !important; 
    position: fixed; 
    bottom: 0; 
    left: 0; 
    right: 0; 
    width: 100%; 
    animation: slideUpProducts 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    overflow: hidden;
    margin: 0;
  }
  .modal-top { 
    padding: 24px; 
    flex-direction: column;
    gap: 16px;
  }
  .btn-back-header { width: 36px; height: 36px; }

  .modal-content { 
    padding: 20px 24px; 
    max-height: 60vh; 
    overflow-y: auto; 
    display: block;
    gap: 0;
  }
  
  .modal-bottom { 
    padding: 16px 24px 24px;
    background: var(--bg-primary);
    justify-content: center;
    flex-direction: row;
    margin-bottom: 0;
  }
  .modal-bottom .btn-save {
    width: 100%;
    flex: 1;
    height: auto;
    display: flex; align-items: center; justify-content: center; gap: 10px;
  }

  @keyframes slideUpProducts {
    from { transform: translateY(100%); }
    to { transform: translateY(0); }
  }
}
</style>
