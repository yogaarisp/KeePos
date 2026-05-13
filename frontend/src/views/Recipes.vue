<template>
  <div class="recipes-container">
    <!-- Header Hero Section -->
    <div class="page-hero">
      <div class="hero-content">
        <div class="hero-icon-wrap">
          <ChefHat :size="24" />
        </div>
        <div>
          <h1 class="hero-title">Resep Masakan</h1>
          <p class="hero-subtitle">Kelola resep dan komposisi bahan untuk setiap menu.</p>
        </div>
      </div>
      <div class="hero-actions">
        <button class="btn-primary" @click="openModal()">
          <Plus :size="18" /> Tambah Resep
        </button>
      </div>
    </div>

    <!-- Missing Recipes Alert -->
    <div v-if="recipeStore.missingRecipes.total_missing > 0" class="alert-missing-recipes">
      <div class="alert-content">
        <AlertCircle :size="20" class="alert-icon" />
        <div class="alert-text">
          <strong>Beberapa produk terjual belum memiliki resep!</strong>
          <span>Ada {{ recipeStore.missingRecipes.total_missing }} produk yang terjual namun stok dapur belum terpotong otomatis.</span>
        </div>
      </div>
      <button class="btn-alert-detail" @click="missingModal.show = true">
        Lihat Detail 
        <ChevronDown :size="14" style="transform: rotate(-90deg)" />
      </button>
    </div>

    <!-- Filter bar -->
    <div class="filter-glass-bar">
      <div class="search-box">
        <Search :size="18" class="search-icon" />
        <input type="text" v-model="recipeStore.filters.search" placeholder="Cari resep..." @input="debouncedSearch">
      </div>
      <div class="filter-group">
        <select v-model="recipeStore.filters.type" @change="refresh" class="filter-select">
          <option value="">Semua Tipe</option>
          <option value="product">Produk</option>
          <option value="production">Produksi</option>
        </select>
      </div>
      <button class="btn-refresh" @click="refresh" :class="{ spinning: recipeStore.loading }">
        <RefreshCw :size="18" />
      </button>
    </div>

    <!-- Loading -->
    <div v-if="recipeStore.loading && !recipeStore.recipes.length" class="loading-grid">
      <div v-for="i in 6" :key="i" class="skeleton-card"></div>
    </div>

    <!-- Recipe List -->
    <div v-else-if="recipeStore.recipes.length" class="recipe-grid">
      <div v-for="(recipe, idx) in recipeStore.recipes" :key="recipe.id"
           class="recipe-card" :style="{ animationDelay: (idx * 0.05) + 's' }">
        <div class="card-header">
          <div class="card-icon" :class="recipe.type">
            <ChefHat :size="20" v-if="recipe.type === 'product'" />
            <FlaskConical :size="20" v-else />
          </div>
          <div class="card-info">
            <h3 class="card-name">{{ recipe.name }}</h3>
            <p class="card-product" v-if="recipe.product">
              <LinkIcon :size="12" /> {{ recipe.product.name }}
            </p>
          </div>
          <div class="type-badge" :class="recipe.type">
            {{ recipe.type === 'product' ? 'Menu' : 'Produksi' }}
          </div>
        </div>

        <div class="card-body">
          <div class="stat-row">
            <div class="stat">
              <span class="stat-label">HPP</span>
              <span class="stat-value cost">{{ formatCurrency(recipe.hpp) }}</span>
            </div>
            <div class="stat">
              <span class="stat-label">Harga Jual</span>
              <span class="stat-value price">{{ formatCurrency(recipe.selling_price) }}</span>
            </div>
          </div>
          <div class="stat-row">
            <div class="stat">
              <span class="stat-label">Margin</span>
              <span class="stat-value margin" :class="{ negative: (recipe.selling_price - recipe.hpp) < 0 }">
                {{ recipe.selling_price > 0 ? Math.round(((recipe.selling_price - recipe.hpp) / recipe.selling_price) * 100) : 0 }}%
              </span>
            </div>
            <div class="stat">
              <span class="stat-label">Bahan</span>
              <span class="stat-value">{{ recipe.items?.length || 0 }} item</span>
            </div>
          </div>
        </div>

        <div class="card-footer">
          <div class="status-dot" :class="{ active: recipe.is_active }">
            {{ recipe.is_active ? 'Aktif' : 'Nonaktif' }}
          </div>
          <div class="card-actions">
            <button class="action-btn view" @click="viewRecipe(recipe)" title="Lihat Detail">
              <Eye :size="15" />
            </button>
            <button class="action-btn edit" @click="editRecipe(recipe)" title="Edit">
              <Edit3 :size="15" />
            </button>
            <button class="action-btn delete" @click="recipeStore.deleteRecipe(recipe.id)" title="Hapus">
              <Trash2 :size="15" />
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty -->
    <div v-else class="empty-placeholder">
      <div class="empty-icon"><ChefHat :size="48" /></div>
      <h3>Belum ada resep</h3>
      <p>Mulai buat resep masakan pertama Anda.</p>
      <button class="btn-primary" @click="openModal()">
        <Plus :size="18" /> Buat Resep Pertama
      </button>
    </div>

    <!-- Detail Modal -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="detailModal.show" class="modal-backdrop" @click.self="detailModal.show = false">
          <div class="modal-panel detail-panel">
            <div class="modal-top">
              <div class="modal-header-content">
                <button class="btn-back-header" @click="detailModal.show = false">
                  <ArrowLeft :size="20" />
                </button>
                <div class="modal-icon-wrap">
                  <ChefHat :size="20" />
                </div>
                <div class="modal-title-area">
                  <h3 class="modal-title">{{ detailModal.recipe?.name }}</h3>
                  <p class="modal-desc">Detail komposisi bahan resep</p>
                </div>
              </div>
            </div>
            <div class="modal-content" v-if="detailModal.recipe">
              <div class="detail-stats">
                <div class="d-stat"><span>HPP</span><strong>{{ formatCurrency(detailModal.recipe.hpp) }}</strong></div>
                <div class="d-stat"><span>Harga Jual</span><strong>{{ formatCurrency(detailModal.recipe.selling_price) }}</strong></div>
                <div class="d-stat"><span>Profit</span><strong>{{ formatCurrency(detailModal.recipe.selling_price - detailModal.recipe.hpp) }}</strong></div>
              </div>
              <p class="desc-text" v-if="detailModal.recipe.description">{{ detailModal.recipe.description }}</p>
              <h4 class="section-title"><Package :size="16" /> Komposisi Bahan</h4>
              <div class="ingredients-table">
                <div class="ing-header">
                  <span>Bahan Dapur</span><span>Qty</span><span>Biaya</span>
                </div>
                <div v-for="item in detailModal.recipe.items" :key="item.id" class="ing-row">
                  <span class="ing-name">{{ item.ingredient_name || 'ID: ' + item.ingredient_id }}</span>
                  <span class="ing-qty">{{ formatDecimal(item.quantity) }} {{ item.unit }}</span>
                  <span class="ing-cost">{{ formatCurrency(item.cost) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Create/Edit Modal -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="modal.show" class="modal-backdrop" @click.self="modal.show = false">
          <div class="modal-panel form-panel">
            <div class="modal-top">
              <div class="modal-header-content">
                <button class="btn-back-header" @click="modal.show = false">
                  <ArrowLeft :size="20" />
                </button>
                <div class="modal-icon-wrap">
                  <ChefHat :size="20" />
                </div>
                <div class="modal-title-area">
                  <h3 class="modal-title">{{ modal.form.id ? 'Edit Resep' : 'Buat Resep Baru' }}</h3>
                  <p class="modal-desc">Masukkan detail resep dan komposisi bahannya.</p>
                </div>
              </div>
            </div>

            <div class="modal-content modal-scrollable">
              <div class="form-grid">
                <div class="input-group">
                  <label class="input-label">Produk Terkait (Menu POS)</label>
                  <div class="custom-select-wrap">
                    <div class="selected-box premium-input" :class="{ 'bg-muted': !!modal.form.id && modal.form.type === 'product' }" @click="!modal.form.id && (isProdDropdownOpen = !isProdDropdownOpen)">
                      <span>{{ getSelectedProductName() }}</span>
                      <ChevronDown :size="16" :class="{ rotate: isProdDropdownOpen }" />
                    </div>
                    
                    <div v-if="isProdDropdownOpen" class="select-dropdown">
                      <div class="select-search">
                        <Search :size="14" />
                        <input type="text" v-model="productSearch" placeholder="Cari..." @click.stop autofocus>
                      </div>
                      <div class="options-list custom-scrollbar">
                        <div class="option-item" @click="selectProduct(null)">-- Pilih Produk --</div>
                        <div v-for="p in filteredProducts" :key="p.id" class="option-item" :class="{ active: modal.form.product_id === p.id }" @click="selectProduct(p)">
                          {{ p.name }}
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="input-group">
                  <label class="input-label">Nama Resep</label>
                  <input type="text" v-model="modal.form.name" class="premium-input" placeholder="Contoh: Nasi Goreng Spesial" :readonly="!!modal.form.product_id" :class="{ 'bg-muted': !!modal.form.product_id }">
                </div>

                <div class="form-row-2">
                  <div class="input-group">
                    <label class="input-label">Tipe</label>
                    <select v-model="modal.form.type" class="premium-input">
                      <option value="product">Produk (Menu)</option>
                      <option value="production">Produksi (Semi)</option>
                    </select>
                  </div>
                  <div class="input-group">
                    <label class="input-label">Harga Jual (Rp)</label>
                    <input type="number" v-model="modal.form.selling_price" class="premium-input">
                  </div>
                </div>

                <div class="form-row-2">
                  <div class="input-group">
                    <label class="input-label">Deskripsi (Opsional)</label>
                    <textarea v-model="modal.form.description" class="premium-input textarea" rows="2" placeholder="Catatan resep..."></textarea>
                  </div>
                  <div class="toggle-group-premium">
                    <div class="toggle-info">
                      <span class="toggle-label-main">Status</span>
                      <span class="toggle-desc">{{ modal.form.is_active ? 'Aktif' : 'Nonaktif' }}</span>
                    </div>
                    <label class="switch">
                      <input type="checkbox" v-model="modal.form.is_active">
                      <span class="slider"></span>
                    </label>
                  </div>
                </div>

                <!-- Ingredients Section -->
                <div class="ingredients-section">
                  <div class="section-header">
                    <h4><Package :size="16" /> Komposisi Bahan</h4>
                    <button class="btn-add-item" @click="addItem">
                      <Plus :size="14" /> Tambah Bahan
                    </button>
                  </div>

                  <div v-for="(item, idx) in modal.form.items" :key="idx" class="ingredient-row">
                    <div class="ing-fields">
                      <select v-model="item.ingredient_id" class="premium-input" @change="onIngredientChange(item)">
                        <option value="">Pilih Bahan Dapur</option>
                        <option v-for="ing in recipeStore.ingredients.kitchen" :key="ing.id" :value="ing.id">
                          {{ ing.name }}
                        </option>
                      </select>
                      <input type="number" v-model.number="item.quantity" class="premium-input sm" placeholder="Qty" step="any" @input="updateItemCost(item)">
                      
                      <select v-model="item.unit" class="premium-input sm" @change="updateItemCost(item)" v-if="getAvailableUnits(item.ingredient_id).length > 1">
                        <option v-for="u in getAvailableUnits(item.ingredient_id)" :key="u.value" :value="u.value">{{ u.label }}</option>
                      </select>
                      <input type="text" v-model="item.unit" class="premium-input sm" placeholder="Unit" readonly v-else>

                      <input type="number" v-model.number="item.cost" class="premium-input sm" placeholder="Biaya" step="any">
                      <button class="btn-remove-item" @click="removeItem(idx)"><Trash2 :size="14" /></button>
                    </div>
                  </div>

                  <div v-if="!modal.form.items.length" class="no-items">
                    Belum ada bahan. Klik "Tambah Bahan" untuk memulai.
                  </div>

                  <div class="hpp-summary" v-if="modal.form.items.length">
                    <span>Total HPP:</span>
                    <strong>{{ formatCurrency(Math.round(calculatedHPP)) }}</strong>
                  </div>
                </div>
              </div>
            </div>

            <div class="modal-bottom">
              <button class="btn-save" @click="save" :disabled="recipeStore.loading">
                <Save :size="18" v-if="!recipeStore.loading" />
                <RefreshCw :size="18" class="spinning" v-else />
                {{ recipeStore.loading ? 'Menyimpan...' : (modal.form.id ? 'Perbarui' : 'Simpan Resep') }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Missing Recipes Modal -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="missingModal.show" class="modal-backdrop" @click.self="missingModal.show = false">
          <div class="modal-panel missing-list-panel">
            <div class="modal-top">
              <div class="modal-header-content">
                <button class="btn-back-header" @click="missingModal.show = false">
                  <ArrowLeft :size="20" />
                </button>
                <div class="modal-icon-wrap warn">
                  <AlertCircle :size="20" />
                </div>
                <div class="modal-title-area">
                  <h3 class="modal-title">Log Produk Terjual Tanpa Resep</h3>
                  <p class="modal-desc">Daftar produk yang terjual tapi resepnya belum diatur.</p>
                </div>
              </div>
            </div>

            <div class="modal-content modal-scrollable">
              <div class="missing-items-list">
                <div v-for="item in recipeStore.missingRecipes.items" :key="item.product_id" class="missing-item-row">
                  <div class="mi-info">
                    <span class="mi-name">{{ item.product_name }}</span>
                    <span class="mi-meta">Terjual: <strong>{{ item.total_qty }}x</strong> • Terakhir: {{ new Date(item.last_sold_at).toLocaleDateString('id-ID') }}</span>
                  </div>
                  <div class="mi-actions">
                    <button class="btn-create-recipe" @click="createNewRecipeFromMissing(item)">
                      <Plus :size="14" /> Buat Resep
                    </button>
                    <button class="btn-dismiss" title="Hapus Log" @click="dismissLog(item)">
                      <Trash2 :size="14" />
                    </button>
                  </div>
                </div>
              </div>
              <div v-if="!recipeStore.missingRecipes.items.length" class="empty-state">
                <CheckCircle2 :size="40" color="#22c55e" />
                <p>Semua produk terjual sudah memiliki resep!</p>
              </div>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref, computed, markRaw, watch } from 'vue';
import { useRecipeStore } from '../stores/recipe';
import { useProductStore } from '../stores/product';
import api from '../api';
import { showConfirm, showSuccess, showError } from '../utils/swal';
import {
  Plus, Search, ChefHat, Edit3, Trash2, RefreshCw, Save,
  Eye, Package, FlaskConical, Link as LinkIcon, ArrowLeft, ChevronDown,
  AlertCircle, X, CheckCircle2
} from 'lucide-vue-next';

const recipeStore = useRecipeStore();
const prodStore = useProductStore();
const products = ref([]);

const detailModal = reactive({ show: false, recipe: null });
const modal = reactive({
  show: false,
  form: getEmptyForm()
});

// Missing modal
const missingModal = reactive({
  show: false
});

const isProdDropdownOpen = ref(false);
const productSearch = ref('');

const filteredProducts = computed(() => {
  if (!productSearch.value) return products.value;
  const q = productSearch.value.toLowerCase();
  return products.value.filter(p => p.name.toLowerCase().includes(q));
});

const getSelectedProductName = () => {
  const p = products.value.find(p => p.id === modal.form.product_id);
  return p ? p.name : '-- Pilih Produk --';
};

const selectProduct = (p) => {
  modal.form.product_id = p ? p.id : null;
  onProductChange();
  isProdDropdownOpen.value = false;
  productSearch.value = '';
};

function getEmptyForm() {
  return {
    id: null,
    name: '',
    description: '',
    product_id: null,
    selling_price: 0,
    type: 'product',
    is_active: true,
    items: []
  };
}

const calculatedHPP = computed(() => {
  return modal.form.items.reduce((sum, item) => sum + (parseFloat(item.cost) || 0), 0);
});

let searchTimeout;
const debouncedSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(refresh, 500);
};
const refresh = () => recipeStore.fetchRecipes();

const onProductChange = () => {
  const selectedProduct = products.value.find(p => p.id === modal.form.product_id);
  if (selectedProduct) {
    // Force sync recipe name and price with product
    modal.form.name = selectedProduct.name;
    modal.form.selling_price = selectedProduct.price;
  }
};

const dismissLog = async (item) => {
  if (await showConfirm(`Hapus log untuk ${item.product_name}?`, 'Log ini tidak akan muncul lagi di daftar ini.')) {
    await recipeStore.dismissMissingRecipe(item.product_id);
  }
};

const createNewRecipeFromMissing = (item) => {
  missingModal.show = false;
  openModal();
  modal.form.name = 'Resep ' + item.product_name;
  modal.form.product_id = item.product_id;
};

onMounted(async () => {
  recipeStore.fetchRecipes();
  recipeStore.fetchIngredients();
  recipeStore.fetchMissingRecipes();
  // Fetch products for linking
  try {
    const res = await api.get('/products', { params: { limit: 100 } });
    if (res.data.success) products.value = res.data.data.products.data;
  } catch (e) { /* ignore */ }

  // Close dropdown on click outside
  window.addEventListener('mousedown', (e) => {
    if (isProdDropdownOpen.value && !e.target.closest('.custom-select-wrap')) {
      isProdDropdownOpen.value = false;
    }
  });
});

const onIngredientChange = (item) => {
  const options = recipeStore.ingredients.kitchen;
  const found = options.find(o => o.id == item.ingredient_id);
  if (found) {
    item.unit = found.unit;
    item.ingredient_type = 'kitchen'; // Force to kitchen
    item.base_unit = found.unit;
    item.cost_per_base = found.cost || 0;
    
    // Auto calculate cost
    updateItemCost(item);
  }
};

const updateItemCost = (item) => {
  const options = recipeStore.ingredients.kitchen;
  const found = options.find(o => o.id == item.ingredient_id);
  if (!found) return;

  let ratio = 1;
  if (item.unit !== found.unit) {
    const conv = found.conversions?.find(c => c.convert_to_unit === item.unit);
    if (conv) ratio = conv.ratio;
  }

  // Cost for the given quantity in selected unit
  // If unit is gram and base is kg (ratio 1000), then cost = (cost_per_kg / 1000) * quantity_gram
  // Actually, ratio in our system is usually "1 base = X converted" (e.g. 1 kg = 1000 gram)
  // So cost per converted unit = cost_per_base / ratio
  item.cost = Math.round((found.cost / ratio) * (item.quantity || 0));
};

const getAvailableUnits = (ingredientId) => {
  const options = recipeStore.ingredients.kitchen;
  const found = options.find(o => o.id == ingredientId);
  if (!found) return [];
  
  const units = [{ label: found.unit, value: found.unit }];
  if (found.conversions) {
    found.conversions.forEach(c => {
      if (c.convert_to_unit !== found.unit) {
        units.push({ label: c.convert_to_unit, value: c.convert_to_unit });
      }
    });
  }
  return units;
};

const addItem = () => {
  modal.form.items.push({
    ingredient_type: 'kitchen', 
    ingredient_id: '',
    quantity: 1,
    unit: '',
    cost: 0
  });
};

const removeItem = (idx) => {
  modal.form.items.splice(idx, 1);
};

const openModal = () => {
  Object.assign(modal.form, getEmptyForm());
  modal.form.items = [];
  modal.show = true;
};

const editRecipe = async (recipe) => {
  const detail = await recipeStore.fetchRecipe(recipe.id);
  if (detail) {
    modal.form = {
      id: detail.id,
      name: detail.name,
      description: detail.description || '',
      product_id: detail.product_id,
      selling_price: detail.selling_price,
      type: detail.type,
      is_active: detail.is_active,
      items: detail.items.map(it => ({
        ingredient_type: it.ingredient_type,
        ingredient_id: it.ingredient_id,
        quantity: Number(it.quantity), // Remove trailing zeros from string decimals
        unit: it.unit,
        cost: Number(it.cost)
      }))
    };
    modal.show = true;
  }
};

const viewRecipe = async (recipe) => {
  const detail = await recipeStore.fetchRecipe(recipe.id);
  if (detail) {
    detailModal.recipe = detail;
    detailModal.show = true;
  }
};

const save = async () => {
  if (!modal.form.name) {
    showError('Nama resep wajib diisi');
    return;
  }
  if (!modal.form.items.length) {
    showError('Minimal harus ada 1 bahan');
    return;
  }

  const data = { ...modal.form, hpp: calculatedHPP.value };
  const success = await recipeStore.saveRecipe(data);
  if (success) {
    modal.show = false;
    showSuccess(modal.mode === 'add' ? 'Resep berhasil ditambahkan!' : 'Resep berhasil diperbarui!');
  } else {
    showError(recipeStore.error || 'Gagal menyimpan resep');
  }
};

const formatCurrency = (val) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency', currency: 'IDR', minimumFractionDigits: 0
  }).format(val || 0);
};

const formatDecimal = (val) => {
  const num = Number(val);
  return new Intl.NumberFormat('id-ID', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 4
  }).format(isNaN(num) ? 0 : num);
};

// Watch modals to toggle mobile nav and body scroll
watch([() => modal.show, () => detailModal.show, () => missingModal.show], ([modalVal, detailVal, missingVal]) => {
  if (modalVal || detailVal || missingVal) {
    document.body.classList.add('hide-mobile-nav');
    document.body.style.overflow = 'hidden';
  } else {
    document.body.classList.remove('hide-mobile-nav');
    document.body.style.overflow = '';
  }
});
</script>

<style scoped>
.recipes-container { padding: 0; animation: fadeIn 0.4s ease; }

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
.btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(249,115,22,0.4); }

/* ── Filter ── */
.filter-glass-bar {
  display: flex; align-items: center; gap: 16px;
  background: var(--bg-card); border: 1px solid var(--border-color);
  padding: 12px 16px; border-radius: 18px; margin-bottom: 24px;
}
.search-box { flex: 1; position: relative; }
.search-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-muted); }
.search-box input {
  width: 100%; height: 44px; padding-left: 44px;
  background: var(--bg-primary); border: 1px solid var(--border-color);
  border-radius: 14px; font-size: 14px; color: var(--text-primary); outline: none;
}
.search-box input:focus { border-color: var(--accent); }
.filter-group { display: flex; align-items: center; gap: 10px; }
.filter-select {
  height: 44px; padding: 0 16px; border-radius: 14px;
  background: var(--bg-primary); border: 1px solid var(--border-color);
  color: var(--text-primary); font-size: 13px; outline: none; cursor: pointer;
}
.btn-refresh {
  width: 44px; height: 44px; border-radius: 14px;
  background: var(--bg-primary); border: 1px solid var(--border-color);
  color: var(--text-secondary); cursor: pointer; transition: all 0.2s;
  display: flex; align-items: center; justify-content: center;
}
.btn-refresh:hover { border-color: var(--accent); color: var(--accent); }

/* ── Recipe Grid ── */
.recipe-grid {
  display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px;
}
.recipe-card {
  background: var(--bg-card); border: 1px solid var(--border-color);
  border-radius: 20px; transition: all 0.3s; animation: slideUp 0.5s ease both;
}
.recipe-card:hover { transform: translateY(-5px); border-color: var(--accent); }

.card-header {
  display: flex; align-items: center; gap: 14px;
  padding: 20px 20px 0;
}
.card-icon {
  width: 44px; height: 44px; border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
}
.card-icon.product { background: rgba(249,115,22,0.1); color: var(--accent); }
.card-icon.production { background: rgba(59,130,246,0.1); color: #3b82f6; }
.card-info { flex: 1; min-width: 0; }
.card-name { font-size: 15px; font-weight: 600; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.card-product { font-size: 11px; color: var(--text-muted); margin-top: 2px; display: flex; align-items: center; gap: 4px; }
.type-badge {
  padding: 4px 10px; border-radius: 100px; font-size: 10px; font-weight: 700; text-transform: uppercase;
}
.type-badge.product { background: rgba(249,115,22,0.1); color: var(--accent); }
.type-badge.production { background: rgba(59,130,246,0.1); color: #3b82f6; }

.card-body { padding: 16px 20px; }
.stat-row { display: flex; gap: 16px; margin-bottom: 8px; }
.stat { flex: 1; }
.stat-label { font-size: 10px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; display: block; }
.stat-value { font-size: 14px; font-weight: 700; color: var(--text-primary); }
.stat-value.cost { color: #ef4444; }
.stat-value.price { color: #22c55e; }
.stat-value.margin { color: var(--accent); }
.stat-value.negative { color: #ef4444; }

.card-footer {
  display: flex; align-items: center; justify-content: space-between;
  padding: 12px 20px; border-top: 1px solid var(--border-color);
}
.status-dot { font-size: 11px; font-weight: 600; color: var(--text-muted); display: flex; align-items: center; gap: 6px; }
.status-dot::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: var(--text-muted); }
.status-dot.active { color: #22c55e; }
.status-dot.active::before { background: #22c55e; }

.card-actions { display: flex; gap: 6px; }
.action-btn {
  width: 30px; height: 30px; border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  border: 1px solid var(--border-color); background: var(--bg-primary);
  color: var(--text-secondary); cursor: pointer; transition: all 0.2s;
}
.action-btn.view:hover { background: #3b82f6; color: #fff; border-color: #3b82f6; }
.action-btn.edit:hover { background: var(--accent); color: #fff; border-color: var(--accent); }
.action-btn.delete:hover { background: #ef4444; color: #fff; border-color: #ef4444; }

/* ── Modal Shared ── */
.modal-backdrop {
  position: fixed; inset: 0; z-index: 200;
  background: rgba(0,0,0,0.6); backdrop-filter: blur(6px);
  display: flex; align-items: center; justify-content: center; padding: 20px;
}
.modal-panel {
  width: 100%; background: var(--bg-card);
  border-radius: 24px; border: 1px solid var(--border-color);
  box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); overflow: hidden;
}
.detail-panel { max-width: 560px; }
.form-panel { max-width: 850px; max-height: 90vh; display: flex; flex-direction: column; }
.modal-top { display: flex; flex-direction: column; gap: 16px; padding: 24px; border-bottom: 1px solid var(--border-color); }
.modal-header-content { display: flex; align-items: center; gap: 16px; width: 100%; }
.modal-icon-wrap {
  width: 44px; height: 44px; border-radius: 12px; background: rgba(249,115,22,0.1); color: var(--accent);
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.modal-icon-wrap.warn { background: rgba(239,68,68,0.1); color: #ef4444; }
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
.modal-scrollable { overflow-y: auto; flex: 1; }

/* ── Detail Modal ── */
.detail-stats { display: flex; gap: 12px; margin-bottom: 20px; }
.d-stat {
  flex: 1; text-align: center; padding: 12px; border-radius: 12px;
  background: var(--bg-primary); border: 1px solid var(--border-color);
}
.d-stat span { font-size: 10px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; display: block; }
.d-stat strong { font-size: 14px; color: var(--text-primary); }
.desc-text { font-size: 13px; color: var(--text-secondary); margin-bottom: 16px; line-height: 1.6; }
.section-title { font-size: 13px; font-weight: 700; color: var(--text-primary); margin-bottom: 12px; display: flex; align-items: center; gap: 8px; }

.ingredients-table { border: 1px solid var(--border-color); border-radius: 12px; overflow: hidden; }
.ing-header, .ing-row { display: grid; grid-template-columns: 1.5fr 1fr 1fr; padding: 10px 14px; font-size: 12px; }
.ing-header { background: var(--bg-primary); font-weight: 700; color: var(--text-muted); text-transform: uppercase; font-size: 10px; }
.ing-row { border-top: 1px solid var(--border-color); }
.ing-row:hover { background: var(--bg-primary); }
.ing-name { font-weight: 600; color: var(--text-primary); }
.ing-qty { font-weight: 600; }
.ing-cost { font-weight: 600; color: var(--accent); }

/* ── Form Modal ── */
.form-grid { display: flex; flex-direction: column; gap: 16px; }
.form-row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
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
.premium-input:focus { border-color: var(--accent); box-shadow: 0 0 0 4px var(--accent-light); }
.premium-input.bg-muted { background: var(--bg-card-hover); cursor: not-allowed; border-color: var(--border-color); opacity: 0.8; }
.premium-input.sm { min-width: 0; }
.textarea { resize: vertical; font-family: inherit; }

.toggle-group-premium {
  display: flex; align-items: center; justify-content: space-between;
  padding: 12px 14px; background: var(--bg-primary); border-radius: 12px; border: 1px solid var(--border-color);
}
.toggle-label-main { display: block; font-size: 13px; font-weight: 700; }
.toggle-desc { display: block; font-size: 11px; color: var(--text-muted); }
.switch { position: relative; width: 44px; height: 24px; cursor: pointer; }
.switch input { opacity: 0; width: 0; height: 0; }
.slider { position: absolute; inset: 0; background: var(--border-color); border-radius: 12px; transition: 0.3s; }
.slider:before { content: ""; position: absolute; height: 18px; width: 18px; left: 3px; bottom: 3px; background: white; border-radius: 50%; transition: 0.3s; }
input:checked + .slider { background: #22c55e; }
input:checked + .slider:before { transform: translateX(20px); }

/* Ingredients Section */
.ingredients-section { border: 1.5px solid var(--border-color); border-radius: 20px; padding: 20px; background: var(--bg-primary); }
.section-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; }
.section-header h4 { font-size: 14px; font-weight: 800; color: var(--text-primary); text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; gap: 10px; margin: 0; }
.btn-add-item {
  display: flex; align-items: center; gap: 8px; padding: 8px 16px; border-radius: 10px;
  background: var(--accent); color: #fff; border: none; font-size: 12px; font-weight: 700; cursor: pointer;
  transition: all 0.2s; box-shadow: 0 4px 10px rgba(249,115,22,0.2);
}
.btn-add-item:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(249,115,22,0.3); }

.ingredient-row { 
  margin-bottom: 12px; padding: 12px; background: var(--bg-card); 
  border: 1px solid var(--border-color); border-radius: 14px;
  transition: 0.2s;
}
.ingredient-row:focus-within { border-color: var(--accent); box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
.ing-fields {
  display: flex; gap: 10px; align-items: center;
}
.ing-fields select { flex: 2; min-width: 0; }
.ing-fields .sm { width: 90px; flex: 1 1 90px; min-width: 0; }
.btn-remove-item {
  width: 32px; height: 32px; border-radius: 8px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  border: 1px solid rgba(239,68,68,0.2); background: rgba(239,68,68,0.05);
  color: #ef4444; cursor: pointer;
}
.no-items { text-align: center; padding: 20px; font-size: 13px; color: var(--text-muted); }
.hpp-summary {
  display: flex; align-items: center; justify-content: space-between;
  padding: 12px 14px; margin-top: 12px; border-radius: 10px;
  background: rgba(249,115,22,0.05); border: 1px solid rgba(249,115,22,0.15);
  font-size: 14px;
}
.hpp-summary strong { color: var(--accent); font-size: 16px; }

.modal-bottom { display: flex; justify-content: center; gap: 12px; padding: 20px 24px; border-top: 1px solid var(--border-color); }
.btn-cancel { padding: 10px 20px; border-radius: 12px; background: var(--bg-primary); border: 1px solid var(--border-color); color: var(--text-secondary); cursor: pointer; font-weight: 600; }
.btn-save {
  width: 100%; padding: 12px 32px; border-radius: 12px; background: var(--accent); color: #fff; border: none; font-weight: 700; cursor: pointer;
  display: flex; align-items: center; justify-content: center; gap: 8px;
}
.btn-save:hover { filter: brightness(1.1); }

/* Empty / Loading */
.empty-placeholder { 
  text-align: center; 
  padding: 80px 20px; 
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}
.empty-icon { width: 80px; height: 80px; border-radius: 50%; background: var(--bg-primary); display: flex; align-items: center; justify-content: center; color: var(--text-muted); margin: 0 auto 20px; }
.empty-placeholder h3 { font-size: 18px; font-weight: 600; margin-bottom: 8px; }
.empty-placeholder p { color: var(--text-muted); margin-bottom: 24px; }
.loading-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; }
.skeleton-card { height: 200px; border-radius: 20px; background: var(--bg-card); border: 1px solid var(--border-color); animation: pulse 1.5s infinite; }

.spinning { animation: spin 1s linear infinite; }
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
@keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }

/* Modal Transitions */
.modal-enter-active, .modal-leave-active { transition: all 0.3s; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
.modal-enter-from .modal-panel, .modal-leave-to .modal-panel { transform: scale(0.95) translateY(10px); }

/* ── Custom Select ── */
.custom-select-wrap { position: relative; width: 100%; }
.selected-box {
  display: flex; align-items: center; justify-content: space-between;
  cursor: pointer; user-select: none;
}
.selected-box .rotate { transform: rotate(180deg); }
.selected-box span { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

.select-dropdown {
  position: absolute; top: calc(100% + 5px); left: 0; right: 0;
  background: var(--bg-card); border: 1px solid var(--border-color);
  border-radius: 14px; box-shadow: 0 10px 25px rgba(0,0,0,0.15);
  z-index: 100; overflow: hidden; animation: slideDown 0.2s ease-out;
}
.select-search {
  display: flex; align-items: center; gap: 8px; padding: 10px 14px;
  border-bottom: 1px solid var(--border-color); background: var(--bg-primary);
}
.select-search input {
  flex: 1; border: none; background: transparent; color: var(--text-primary);
  font-size: 13px; font-weight: 600; outline: none;
}
.options-list { max-height: 200px; overflow-y: auto; padding: 6px; }
.option-item {
  padding: 10px 14px; border-radius: 10px; font-size: 13px; font-weight: 600;
  color: var(--text-primary); cursor: pointer; transition: 0.2s;
}
.option-item:hover { background: var(--bg-primary); color: var(--accent); }
.option-item.active { background: var(--accent-light); color: var(--accent); }

@keyframes slideDown {
  from { opacity: 0; transform: translateY(-5px); }
  to { opacity: 1; transform: translateY(0); }
}

/* ── Missing Recipes Alert (Premium) ── */
.alert-missing-recipes {
  display: flex; align-items: center; justify-content: space-between;
  background: linear-gradient(135deg, #fff5f5 0%, #fff 100%);
  border: 1px solid #fee2e2; border-radius: 20px;
  padding: 18px 24px; margin-bottom: 24px;
  box-shadow: 0 10px 25px -5px rgba(220, 38, 38, 0.08);
  animation: slideDown 0.5s ease-out;
  position: relative; overflow: hidden;
}
.dark .alert-missing-recipes { 
  background: linear-gradient(135deg, #2d1a1a 0%, #1a1a1a 100%); 
  border-color: #450a0a; 
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3);
}

.alert-missing-recipes::before {
  content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 4px;
  background: #ef4444; border-radius: 4px 0 0 4px;
}

.alert-content { display: flex; align-items: center; gap: 16px; }
.alert-icon { 
  color: #ef4444; background: rgba(239, 68, 68, 0.1); 
  padding: 8px; border-radius: 12px; width: 40px; height: 40px;
  display: flex; align-items: center; justify-content: center;
}
.alert-text { display: flex; flex-direction: column; gap: 2px; }
.alert-text strong { font-size: 15px; color: #b91c1c; font-weight: 700; }
.dark .alert-text strong { color: #f87171; }
.alert-text span { font-size: 13px; color: var(--text-muted); font-weight: 500; }

.btn-alert-detail {
  padding: 10px 20px; border-radius: 12px; border: 1.5px solid #fca5a5;
  background: #fff; color: #b91c1c; font-size: 13px; font-weight: 700;
  cursor: pointer; transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  display: flex; align-items: center; gap: 8px;
  box-shadow: 0 2px 8px rgba(220, 38, 38, 0.05);
}
.dark .btn-alert-detail { background: #1a1a1a; color: #f87171; border-color: #450a0a; }
.btn-alert-detail:hover { 
  background: #ef4444; color: #fff; border-color: #ef4444;
  transform: translateY(-2px); box-shadow: 0 8px 16px rgba(239, 68, 68, 0.2);
}

/* Missing Modal Details */
.missing-list-panel { width: 100%; max-width: 520px; }
.missing-items-list { display: flex; flex-direction: column; gap: 14px; }
.missing-item-row {
  display: flex; align-items: center; justify-content: space-between;
  padding: 16px 20px; background: var(--bg-card); 
  border: 1px solid var(--border-color); border-radius: 18px;
  transition: all 0.2s;
}
.missing-item-row:hover { border-color: var(--accent); transform: scale(1.02); }

.mi-info { display: flex; flex-direction: column; gap: 4px; }
.mi-name { font-size: 15px; font-weight: 700; color: var(--text-primary); }
.mi-meta { font-size: 12px; color: var(--text-muted); font-weight: 500; }
.mi-meta strong { color: var(--accent); }
.mi-actions { display: flex; gap: 10px; }

.btn-create-recipe {
  display: flex; align-items: center; gap: 8px; padding: 8px 16px;
  background: var(--accent); color: #fff; border: none; border-radius: 10px;
  font-size: 12px; font-weight: 700; cursor: pointer; transition: 0.2s;
  box-shadow: 0 4px 12px rgba(249, 115, 22, 0.2);
}
.btn-create-recipe:hover { transform: translateY(-2px); background: #f97316; box-shadow: 0 6px 15px rgba(249, 115, 22, 0.3); }

.btn-dismiss {
  width: 36px; height: 36px; border-radius: 10px; border: 1.5px solid var(--border-color);
  background: var(--bg-primary); color: var(--text-muted); cursor: pointer;
  display: flex; align-items: center; justify-content: center; transition: 0.2s;
}
.btn-dismiss:hover { background: #fee2e2; color: #ef4444; border-color: #fca5a5; }

.empty-state {
  display: flex; flex-direction: column; align-items: center; gap: 16px;
  padding: 40px 0; text-align: center;
}
.empty-state p { font-size: 14px; color: var(--text-muted); font-weight: 600; }

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
  
  .alert-missing-recipes { flex-direction: column; gap: 16px; padding: 16px; align-items: stretch; }
  .alert-content { gap: 12px; }
  .alert-icon { width: 32px; height: 32px; padding: 6px; }
  .alert-text strong { font-size: 14px; }
  .alert-text span { font-size: 12px; }
  .btn-alert-detail { width: 100%; justify-content: center; }

  .filter-glass-bar { flex-direction: row; flex-wrap: wrap; gap: 10px; padding: 12px; border-radius: 16px; margin-bottom: 12px; }
  .search-box { width: 100%; flex: none; }
  .filter-group { flex: 1; }
  .filter-select { width: 100%; }
  .btn-refresh { flex-shrink: 0; }

  .recipe-grid { grid-template-columns: 1fr; gap: 12px; }
  .recipe-card { border-radius: 16px; }
  .card-header { padding: 16px 16px 0; gap: 10px; }
  .card-icon { width: 36px; height: 36px; border-radius: 10px; }
  .card-body { padding: 12px 16px; }
  .card-footer { padding: 10px 16px; }

  .form-row-2 { grid-template-columns: 1fr; }
  .ing-fields { flex-wrap: wrap; }
  .ing-fields select, .ing-fields .sm { flex: 1 1 100%; width: 100%; }

  /* Modal bottom-sheet style */
  .modal-panel { 
    max-width: 100% !important; 
    border-radius: 24px 24px 0 0 !important; 
    margin: 0 !important;
    max-height: 92vh !important;
  }
  .modal-backdrop { 
    align-items: flex-end !important; 
    padding: 0 !important; 
    background: rgba(0,0,0,0.35) !important;
    backdrop-filter: blur(2px) !important;
  }
  .modal-content { padding: 20px; }
  .modal-bottom { 
    padding: 16px 20px 32px; 
    flex-direction: column-reverse;
  }
  .modal-bottom button { width: 100%; }

  /* Detail modal table */
  .ing-header, .ing-row { grid-template-columns: 1.5fr 0.8fr 1fr; font-size: 11px; padding: 8px 10px; }
  .detail-stats { gap: 8px; }
  .d-stat { padding: 10px 8px; }
}

@media (max-width: 480px) {
  .hero-title { font-size: 14px; }
  .ing-header span:nth-child(2), .ing-row .ing-type { display: none; }
  .ing-header, .ing-row { grid-template-columns: 1.5fr 1fr; }
}
</style>
