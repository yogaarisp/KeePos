<template>
  <div class="production-container">
    <!-- Header Hero Section -->
    <div class="page-hero">
      <div class="hero-content">
        <div class="hero-icon-wrap">
          <Play :size="24" fill="currentColor" />
        </div>
        <div>
          <h1 class="hero-title">Produksi Batch</h1>
          <p class="hero-subtitle">Catat aktivitas masak massal, kurangi stok bahan baku otomatis, dan tambahkan hasil masakan ke stok dapur.</p>
        </div>
      </div>
      <div class="hero-actions">
        <button class="btn-primary" @click="openProduceModal()">
          <Play :size="18" fill="currentColor" /> Catat Produksi Baru
        </button>
      </div>
    </div>

    <!-- Main Navigation Tabs -->
    <div class="glass-tabs">
      <button 
        class="tab-btn" 
        :class="{ active: activeTab === 'history' }"
        @click="activeTab = 'history'"
      >
        <History :size="16" /> Riwayat Produksi
      </button>
      <button 
        class="tab-btn" 
        :class="{ active: activeTab === 'recipes' }"
        @click="activeTab = 'recipes'"
      >
        <BookOpen :size="16" /> Resep Produksi Massal
      </button>
    </div>

    <!-- Tab Content: History -->
    <div v-if="activeTab === 'history'" class="tab-content-wrapper">
      <div class="filter-glass-bar">
        <div class="date-range-filter">
          <div class="date-input-wrap">
            <span class="date-label">Mulai</span>
            <input type="date" v-model="filters.start_date" class="date-input" @change="fetchTransactions">
          </div>
          <div class="date-input-wrap">
            <span class="date-label">Selesai</span>
            <input type="date" v-model="filters.end_date" class="date-input" @change="fetchTransactions">
          </div>
        </div>
        <button class="btn-refresh" @click="fetchTransactions" :class="{ spinning: loading }">
          <RefreshCw :size="18" />
        </button>
      </div>

      <!-- Loading State -->
      <div v-if="loading && !transactions.length" class="loading-grid">
        <div v-for="i in 4" :key="i" class="skeleton-card"></div>
      </div>

      <!-- Table View -->
      <div v-else-if="transactions.length" class="table-card-wrapper custom-scrollbar">
        <table class="premium-table">
          <thead>
            <tr>
              <th>Tanggal</th>
              <th>Resep Masakan</th>
              <th>Hasil Produksi</th>
              <th>Total Biaya Bahan</th>
              <th>Operator</th>
              <th>Catatan</th>
              <th class="text-right">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="tx in transactions" :key="tx.id" class="table-row">
              <td class="cell-date">
                <span class="primary-text">{{ formatDate(tx.created_at) }}</span>
                <span class="secondary-text">{{ formatTime(tx.created_at) }}</span>
              </td>
              <td>
                <div class="recipe-cell">
                  <span class="recipe-name">{{ tx.production_recipe?.recipe?.name || 'Resep Terhapus' }}</span>
                  <span class="recipe-badge">Massal</span>
                </div>
              </td>
              <td>
                <span class="qty-produced">
                  +{{ formatDecimal(tx.quantity_produced) }} {{ tx.production_recipe?.output_kitchen_stock?.unit || 'pcs' }}
                </span>
                <span class="target-stock-name">
                  ke {{ tx.production_recipe?.output_kitchen_stock?.name || 'Dapur' }}
                </span>
              </td>
              <td class="cell-cost">
                {{ formatCurrency(tx.total_cost) }}
              </td>
              <td>
                <span class="operator-badge">{{ tx.user?.full_name || tx.user?.username || 'System' }}</span>
              </td>
              <td class="cell-notes">
                <span class="notes-text" :title="tx.notes">{{ tx.notes || '-' }}</span>
              </td>
              <td class="text-right">
                <button 
                  class="btn-table-action delete" 
                  title="Batalkan & Kembalikan Stok" 
                  @click="rollbackTransaction(tx)"
                >
                  <RotateCcw :size="14" /> Batalkan
                </button>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination -->
        <div class="pagination-bar" v-if="pagination.total > pagination.per_page">
          <span class="pagination-info">Menampilkan {{ pagination.from }}-{{ pagination.to }} dari {{ pagination.total }} baris</span>
          <div class="pagination-buttons">
            <button 
              class="btn-pagination" 
              :disabled="pagination.current_page === 1" 
              @click="changePage(pagination.current_page - 1)"
            >
              Sebelumnya
            </button>
            <button 
              class="btn-pagination" 
              :disabled="pagination.current_page === pagination.last_page" 
              @click="changePage(pagination.current_page + 1)"
            >
              Berikutnya
            </button>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="empty-placeholder">
        <div class="empty-icon"><History :size="48" /></div>
        <h3>Belum ada riwayat produksi</h3>
        <p>Catat masakan batch pertama Anda untuk melacak mutasi stok bahan secara otomatis.</p>
        <button class="btn-primary" @click="openProduceModal()">
          <Play :size="18" fill="currentColor" /> Mulai Produksi
        </button>
      </div>
    </div>

    <!-- Tab Content: Recipes Setup -->
    <div v-if="activeTab === 'recipes'" class="tab-content-wrapper">
      <div class="section-actions-bar">
        <h3 class="section-title-tab"><BookOpen :size="18" /> Konfigurasi Porsi Resep Produksi</h3>
        <button class="btn-primary" @click="openRecipeModal()">
          <Plus :size="16" /> Tambah Hubungan Produksi
        </button>
      </div>

      <!-- Loading State -->
      <div v-if="loading && !prodRecipes.length" class="loading-grid">
        <div v-for="i in 3" :key="i" class="skeleton-card"></div>
      </div>

      <!-- Recipes Grid -->
      <div v-else-if="prodRecipes.length" class="recipe-grid">
        <div v-for="pr in prodRecipes" :key="pr.id" class="recipe-card">
          <div class="card-header">
            <div class="card-icon production">
              <ChefHat :size="20" />
            </div>
            <div class="card-info">
              <h3 class="card-name">{{ pr.recipe?.name || 'Resep Tidak Ditemukan' }}</h3>
              <p class="card-product">Output standard produksi massal</p>
            </div>
            <div class="type-badge production">PRODUKSI</div>
          </div>

          <div class="card-body">
            <div class="stat-row">
              <div class="stat">
                <span class="stat-label">Porsi / Batch</span>
                <span class="stat-value text-accent">{{ formatDecimal(pr.output_quantity) }} {{ pr.output_unit }}</span>
              </div>
              <div class="stat">
                <span class="stat-label">Target Stok Dapur</span>
                <span class="stat-value">{{ pr.output_kitchen_stock?.name || 'Stok dapur' }}</span>
              </div>
            </div>
            <div class="stat-row mt-12">
              <div class="stat full-width">
                <span class="stat-label">Bahan Baku Yang Dibutuhkan</span>
                <div class="mini-ingredients-list">
                  <span 
                    v-for="item in pr.recipe?.items" 
                    :key="item.id" 
                    class="mini-ing-badge"
                  >
                    {{ item.ingredient_name || ('Bahan #' + item.ingredient_id) }}: {{ formatDecimal(item.quantity) }} {{ item.unit }}
                  </span>
                  <span v-if="!pr.recipe?.items?.length" class="text-muted text-xs">Tidak ada bahan terdaftar</span>
                </div>
              </div>
            </div>
          </div>

          <div class="card-footer">
            <div class="status-dot active">Terdaftar</div>
            <div class="card-actions">
              <button class="action-btn edit" @click="editRecipe(pr)" title="Edit">
                <Edit3 :size="15" />
              </button>
              <button class="action-btn delete" @click="deleteRecipe(pr.id)" title="Hapus">
                <Trash2 :size="15" />
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="empty-placeholder">
        <div class="empty-icon"><BookOpen :size="48" /></div>
        <h3>Belum ada resep terdaftar</h3>
        <p>Daftarkan resep masakan Anda untuk dikaitkan dengan output stok dapur.</p>
        <button class="btn-primary" @click="openRecipeModal()">
          <Plus :size="16" /> Tambah Hubungan Pertama
        </button>
      </div>
    </div>

    <!-- MODAL 1: RECORD NEW BATCH PRODUCTION -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="produceModal.show" class="modal-backdrop" @click.self="produceModal.show = false">
          <div class="modal-panel form-panel">
            <div class="modal-top">
              <div class="modal-header-content">
                <button class="btn-back-header" @click="produceModal.show = false">
                  <ArrowLeft :size="20" />
                </button>
                <div class="modal-icon-wrap">
                  <Play :size="20" fill="currentColor" />
                </div>
                <div class="modal-title-area">
                  <h3 class="modal-title">Catat Produksi Massal (Batch)</h3>
                  <p class="modal-desc">Pilih resep produksi, masukkan jumlah batch masakan, sistem akan otomatis menghitung pemakaian bahan.</p>
                </div>
              </div>
            </div>

            <div class="modal-content modal-scrollable">
              <div class="form-grid">
                <!-- Dropdown Resep Produksi -->
                <div class="input-group">
                  <label class="input-label">Resep Produksi Massal</label>
                  <select 
                    v-model="produceModal.form.production_recipe_id" 
                    class="premium-input" 
                    @change="onSelectedRecipeChange"
                  >
                    <option value="">-- Pilih Resep Produksi --</option>
                    <option v-for="pr in prodRecipes" :key="pr.id" :value="pr.id">
                      {{ pr.recipe?.name }} (Standard: {{ formatDecimal(pr.output_quantity) }} {{ pr.output_unit }} => {{ pr.output_kitchen_stock?.name }})
                    </option>
                  </select>
                </div>

                <!-- Input Quantity Produced -->
                <div class="form-row-2" v-if="selectedRecipe">
                  <div class="input-group">
                    <label class="input-label">Jumlah Hasil Masak ({{ selectedRecipe.output_unit }})</label>
                    <input 
                      type="number" 
                      v-model.number="produceModal.form.quantity_produced" 
                      class="premium-input" 
                      placeholder="Masukkan total kuantitas..."
                      step="any"
                      min="0.01"
                      @input="recalculateRequiredIngredients"
                    />
                    <span class="input-hint">Kuantitas hasil akhir masakan yang akan dimasukkan ke stok dapur.</span>
                  </div>

                  <div class="input-group">
                    <label class="input-label">Kelipatan Resep / Batch</label>
                    <div class="multiplier-display">
                      <span>{{ formatDecimal(recipeMultiplier) }}x</span>
                      <small>dari standard porsi resep</small>
                    </div>
                  </div>
                </div>

                <!-- Ingredients Preview (With stock availability indicators) -->
                <div class="ingredients-section mt-16" v-if="selectedRecipe && requiredIngredients.length">
                  <div class="section-header">
                    <h4><Layers :size="16" /> Preview Kebutuhan & Ketersediaan Bahan</h4>
                    <span class="status-summary-badge" :class="hasEnoughIngredients ? 'success' : 'danger'">
                      {{ hasEnoughIngredients ? 'Bahan Baku Siap & Cukup' : 'Stok Beberapa Bahan Kurang' }}
                    </span>
                  </div>

                  <div class="ingredients-preview-table">
                    <div class="preview-header">
                      <span>Nama Bahan</span>
                      <span>Jenis</span>
                      <span class="text-right">Dibutuhkan</span>
                      <span class="text-right">Tersedia Saat Ini</span>
                      <span class="text-right">Status</span>
                    </div>
                    <div 
                      v-for="ing in requiredIngredients" 
                      :key="ing.id" 
                      class="preview-row"
                      :class="{ 'insufficient': !ing.hasEnough }"
                    >
                      <span class="ing-name">{{ ing.name }}</span>
                      <span class="ing-type-badge" :class="ing.type">{{ ing.type === 'gudang' ? 'Gudang' : 'Dapur' }}</span>
                      <span class="text-right ing-qty-val">{{ formatDecimal(ing.required) }} {{ ing.unit }}</span>
                      <span class="text-right ing-qty-val font-semibold">{{ formatDecimal(ing.available) }} {{ ing.unit }}</span>
                      <span class="text-right cell-status">
                        <Check v-if="ing.hasEnough" class="text-success" :size="16" />
                        <span v-else class="status-warning-pill">Kurang {{ formatDecimal(ing.required - ing.available) }}</span>
                      </span>
                    </div>
                  </div>
                </div>

                <!-- Notes Input -->
                <div class="input-group" v-if="selectedRecipe">
                  <label class="input-label">Catatan Tambahan (Opsional)</label>
                  <textarea 
                    v-model="produceModal.form.notes" 
                    class="premium-input textarea" 
                    rows="2" 
                    placeholder="Contoh: dimasak oleh chef Budi, untuk catering arisan..."
                  ></textarea>
                </div>
              </div>
            </div>

            <div class="modal-bottom">
              <button 
                class="btn-save" 
                @click="submitProduction" 
                :disabled="loading || !selectedRecipe || !hasEnoughIngredients"
              >
                <Play :size="18" fill="currentColor" v-if="!loading" />
                <RefreshCw :size="18" class="spinning" v-else />
                {{ loading ? 'Mengeksekusi Produksi...' : 'Mulai & Catat Produksi' }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- MODAL 2: REGISTER / EDIT PRODUCTION RECIPE MAP -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="recipeModal.show" class="modal-backdrop" @click.self="recipeModal.show = false">
          <div class="modal-panel detail-panel">
            <div class="modal-top">
              <div class="modal-header-content">
                <button class="btn-back-header" @click="recipeModal.show = false">
                  <ArrowLeft :size="20" />
                </button>
                <div class="modal-icon-wrap">
                  <BookOpen :size="20" />
                </div>
                <div class="modal-title-area">
                  <h3 class="modal-title">{{ recipeModal.form.id ? 'Edit Hubungan Produksi' : 'Tambah Hubungan Produksi Baru' }}</h3>
                  <p class="modal-desc">Kaitkan resep masakan dengan item stok dapur sasaran beserta porsi standardnya.</p>
                </div>
              </div>
            </div>

            <div class="modal-content">
              <div class="form-grid">
                <!-- Select Standard Recipe -->
                <div class="input-group">
                  <label class="input-label">Pilih Resep Masakan</label>
                  <select v-model="recipeModal.form.recipe_id" class="premium-input" :disabled="!!recipeModal.form.id">
                    <option value="">-- Pilih Resep --</option>
                    <option v-for="r in standardRecipes" :key="r.id" :value="r.id">
                      {{ r.name }} ({{ r.type === 'product' ? 'Menu POS' : 'Bahan Semi-Jadi' }} - {{ r.items?.length || 0 }} bahan)
                    </option>
                  </select>
                </div>

                <!-- Select Target Kitchen Stock -->
                <div class="input-group">
                  <label class="input-label">Item Target Stok Dapur Sasaran</label>
                  <select v-model="recipeModal.form.output_kitchen_stock_id" class="premium-input">
                    <option value="">-- Pilih Item Stok Dapur --</option>
                    <option v-for="ks in kitchenStocks" :key="ks.id" :value="ks.id">
                      {{ ks.name }} ({{ ks.unit }})
                    </option>
                  </select>
                  <span class="input-hint">Kuantitas hasil jadi produksi akan dimasukkan langsung ke item ini di dapur.</span>
                </div>

                <!-- Porsi Standard Output -->
                <div class="form-row-2">
                  <div class="input-group">
                    <label class="input-label">Kuantitas Output Standard (1 Batch)</label>
                    <input 
                      type="number" 
                      v-model.number="recipeModal.form.output_quantity" 
                      class="premium-input" 
                      placeholder="Contoh: 50"
                      step="any"
                      min="0.01"
                    />
                  </div>
                  <div class="input-group">
                    <label class="input-label">Satuan Output</label>
                    <input 
                      type="text" 
                      v-model="recipeModal.form.output_unit" 
                      class="premium-input" 
                      placeholder="Contoh: pcs, bungkus, liter"
                      maxlength="50"
                    />
                  </div>
                </div>
              </div>
            </div>

            <div class="modal-bottom">
              <button 
                class="btn-save" 
                @click="saveRecipeMap" 
                :disabled="loading || !recipeModal.form.recipe_id || !recipeModal.form.output_kitchen_stock_id || !recipeModal.form.output_quantity || !recipeModal.form.output_unit"
              >
                <Save :size="18" v-if="!loading" />
                <RefreshCw :size="18" class="spinning" v-else />
                {{ loading ? 'Menyimpan...' : 'Simpan Hubungan Produksi' }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue';
import api from '../api';
import { showConfirm, showSuccess, showError } from '../utils/swal';
import { 
  Play, Plus, History, BookOpen, RefreshCw, Layers, Save,
  ChefHat, Edit3, Trash2, ArrowLeft, Check, RotateCcw
} from 'lucide-vue-next';

// Tab Control
const activeTab = ref('history');
const loading = ref(false);

// Core lists
const transactions = ref([]);
const prodRecipes = ref([]);
const standardRecipes = ref([]);
const kitchenStocks = ref([]);

// Filter & Pagination
const filters = reactive({
  start_date: '',
  end_date: '',
  page: 1,
  limit: 15
});

const pagination = reactive({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0,
  from: 1,
  to: 1
});

// Modal Controls
const produceModal = reactive({
  show: false,
  form: {
    production_recipe_id: '',
    quantity_produced: 1,
    notes: ''
  }
});

const recipeModal = reactive({
  show: false,
  form: {
    id: null,
    recipe_id: '',
    output_kitchen_stock_id: '',
    output_quantity: 1,
    output_unit: ''
  }
});

// Dynamic calculations inside produce modal
const selectedRecipe = computed(() => {
  return prodRecipes.value.find(pr => pr.id === produceModal.form.production_recipe_id) || null;
});

const recipeMultiplier = computed(() => {
  if (!selectedRecipe.value || !produceModal.form.quantity_produced) return 1;
  return Number(produceModal.form.quantity_produced) / Number(selectedRecipe.value.output_quantity);
});

const requiredIngredients = ref([]);
const hasEnoughIngredients = computed(() => {
  if (!requiredIngredients.value.length) return true;
  return requiredIngredients.value.every(ing => ing.hasEnough);
});

// Methods: Recalculate Ingredients
const recalculateRequiredIngredients = () => {
  if (!selectedRecipe.value) {
    requiredIngredients.value = [];
    return;
  }

  const recipe = selectedRecipe.value.recipe;
  if (!recipe || !recipe.items) {
    requiredIngredients.value = [];
    return;
  }

  const mult = recipeMultiplier.value;

  requiredIngredients.value = recipe.items.map(item => {
    const requiredQty = Number(item.quantity) * mult;
    let availableQty = 0;
    let ingredientName = item.ingredient_name || 'Item';
    
    // Find current stock in corresponding list
    if (item.ingredient_type === 'gudang') {
      // Find from warehouse stock or query from our active lists
      availableQty = Number(item.ingredient?.stock || 0);
      ingredientName = item.ingredient?.name || item.ingredient_name || 'Bahan Gudang';
    } else {
      // Find from kitchen stock list
      const kitchenItem = kitchenStocks.value.find(ks => ks.id === item.ingredient_id);
      availableQty = kitchenItem ? Number(kitchenItem.stock) : 0;
      ingredientName = kitchenItem ? kitchenItem.name : (item.ingredient_name || 'Bahan Dapur');
    }

    return {
      id: item.id,
      name: ingredientName,
      type: item.ingredient_type,
      required: requiredQty,
      available: availableQty,
      unit: item.unit,
      hasEnough: availableQty >= requiredQty
    };
  });
};

const onSelectedRecipeChange = () => {
  if (selectedRecipe.value) {
    produceModal.form.quantity_produced = Number(selectedRecipe.value.output_quantity);
  } else {
    produceModal.form.quantity_produced = 1;
  }
  recalculateRequiredIngredients();
};

// Fetch Transactions
const fetchTransactions = async () => {
  loading.value = true;
  try {
    const params = {
      page: filters.page,
      limit: filters.limit
    };
    if (filters.start_date) params.start_date = filters.start_date;
    if (filters.end_date) params.end_date = filters.end_date;

    const res = await api.get('/production/transactions', { params });
    if (res.data.success) {
      transactions.value = res.data.data.data || [];
      // Populate pagination
      const data = res.data.data;
      pagination.current_page = data.current_page || 1;
      pagination.last_page = data.last_page || 1;
      pagination.per_page = data.per_page || 15;
      pagination.total = data.total || 0;
      pagination.from = data.from || 1;
      pagination.to = data.to || 1;
    }
  } catch (err) {
    console.warn('Failed to load production transactions:', err);
    transactions.value = [];
  } finally {
    loading.value = false;
  }
};

// Change page
const changePage = (page) => {
  filters.page = page;
  fetchTransactions();
};

// Fetch Production Recipes
const fetchProductionRecipes = async () => {
  loading.value = true;
  try {
    const res = await api.get('/production/recipes');
    if (res.data.success) {
      prodRecipes.value = res.data.data || [];
    }
  } catch (err) {
    console.warn('Failed to load production recipes:', err);
    prodRecipes.value = [];
  } finally {
    loading.value = false;
  }
};

// Fetch standard recipes and kitchen stocks for dropdown options
const fetchSupportData = async () => {
  try {
    // Standard recipes
    const recipesRes = await api.get('/recipes', { params: { limit: 100 } });
    if (recipesRes.data.success) {
      standardRecipes.value = recipesRes.data.data || [];
    }

    // Kitchen Stocks
    const kitchenRes = await api.get('/kitchen');
    if (kitchenRes.data.success) {
      kitchenStocks.value = kitchenRes.data.data || [];
    }
  } catch (err) {
    // Ignore quietly, support data load failure handled at modal open
  }
};

// Open modals
const openProduceModal = async () => {
  await fetchSupportData();
  await fetchProductionRecipes();
  produceModal.form = {
    production_recipe_id: '',
    quantity_produced: 1,
    notes: ''
  };
  requiredIngredients.value = [];
  produceModal.show = true;
};

const openRecipeModal = async () => {
  await fetchSupportData();
  recipeModal.form = {
    id: null,
    recipe_id: '',
    output_kitchen_stock_id: '',
    output_quantity: 1,
    output_unit: ''
  };
  recipeModal.show = true;
};

// Edit Recipe Map
const editRecipe = async (pr) => {
  await fetchSupportData();
  recipeModal.form = {
    id: pr.id,
    recipe_id: pr.recipe_id,
    output_kitchen_stock_id: pr.output_kitchen_stock_id,
    output_quantity: Number(pr.output_quantity),
    output_unit: pr.output_unit
  };
  recipeModal.show = true;
};

// Delete Recipe Map
const deleteRecipe = async (id) => {
  const isConfirmed = await showConfirm(
    'Hapus Hubungan Produksi?',
    'Apakah Anda yakin ingin menghapus hubungan resep produksi ini?'
  );
  if (!isConfirmed) return;

  loading.value = true;
  try {
    const res = await api.delete(`/production/recipes/${id}`);
    if (res.data.success) {
      showSuccess(res.data.message || 'Resep produksi berhasil dihapus');
      fetchProductionRecipes();
    }
  } catch (err) {
    showError(err.response?.data?.message || 'Gagal menghapus resep produksi');
  } finally {
    loading.value = false;
  }
};

// Save Recipe Map
const saveRecipeMap = async () => {
  loading.value = true;
  try {
    let res;
    if (recipeModal.form.id) {
      res = await api.put(`/production/recipes/${recipeModal.form.id}`, recipeModal.form);
    } else {
      res = await api.post('/production/recipes', recipeModal.form);
    }

    if (res.data.success) {
      showSuccess(res.data.message || 'Berhasil menyimpan resep produksi');
      recipeModal.show = false;
      fetchProductionRecipes();
    }
  } catch (err) {
    showError(err.response?.data?.message || 'Gagal menyimpan resep produksi');
  } finally {
    loading.value = false;
  }
};

// Submit Production batch transaction
const submitProduction = async () => {
  if (!hasEnoughIngredients.value) {
    showError('Tidak dapat melanjutkan karena stok beberapa bahan baku tidak mencukupi.');
    return;
  }

  loading.value = true;
  try {
    const res = await api.post('/production/transactions', produceModal.form);
    if (res.data.success) {
      showSuccess(res.data.message || 'Aktivitas produksi massal berhasil dicatat!');
      produceModal.show = false;
      fetchTransactions();
    }
  } catch (err) {
    showError(err.response?.data?.message || 'Gagal mencatat produksi batch');
  } finally {
    loading.value = false;
  }
};

// Rollback / Cancel batch transaction
const rollbackTransaction = async (tx) => {
  const isConfirmed = await showConfirm(
    'Batalkan & Rollback Produksi?',
    `Apakah Anda yakin ingin membatalkan produksi ${tx.production_recipe?.recipe?.name || ''}? Stok bahan baku akan dikembalikan dan stok hasil dapur akan dikurangi.`
  );
  if (!isConfirmed) return;

  loading.value = true;
  try {
    const res = await api.delete(`/production/transactions/${tx.id}`);
    if (res.data.success) {
      showSuccess(res.data.message || 'Transaksi produksi berhasil dibatalkan');
      fetchTransactions();
    }
  } catch (err) {
    showError(err.response?.data?.message || 'Gagal membatalkan transaksi produksi');
  } finally {
    loading.value = false;
  }
};

// Formatting helpers
const formatDate = (dateStr) => {
  if (!dateStr) return '';
  return new Date(dateStr).toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  });
};

const formatTime = (dateStr) => {
  if (!dateStr) return '';
  return new Date(dateStr).toLocaleTimeString('id-ID', {
    hour: '2-digit',
    minute: '2-digit'
  });
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

// Life Cycle Hooks
onMounted(() => {
  fetchTransactions();
  fetchProductionRecipes();
});

// Watch modal state for overflow behavior
watch([() => produceModal.show, () => recipeModal.show], ([pVal, rVal]) => {
  if (pVal || rVal) {
    document.body.classList.add('hide-mobile-nav');
    document.body.style.overflow = 'hidden';
  } else {
    document.body.classList.remove('hide-mobile-nav');
    document.body.style.overflow = '';
  }
});
</script>

<style scoped>
.production-container { padding: 0; animation: fadeIn 0.4s ease; }

/* ── Hero ── */
.page-hero {
  display: flex; align-items: center; justify-content: space-between;
  background: linear-gradient(135deg, rgba(59,130,246,0.08) 0%, rgba(59,130,246,0.02) 100%);
  border: 1px solid rgba(59,130,246,0.1); border-radius: 20px;
  padding: 28px 32px; margin-bottom: 24px;
}
.hero-content { display: flex; align-items: center; gap: 18px; }
.hero-icon-wrap {
  width: 54px; height: 54px; border-radius: 16px;
  background: linear-gradient(135deg, #3b82f6, #60a5fa);
  display: flex; align-items: center; justify-content: center;
  color: #fff; box-shadow: 0 8px 24px rgba(59,130,246,0.25);
}
.hero-title { font-size: 22px; font-weight: 600; color: var(--text-primary); margin-bottom: 4px; }
.hero-subtitle { font-size: 14px; color: var(--text-secondary); font-weight: 500; }
.btn-primary {
  display: flex; align-items: center; gap: 10px;
  background: linear-gradient(135deg, #3b82f6, #60a5fa);
  color: #fff; border: none; padding: 12px 24px; border-radius: 12px;
  font-size: 14px; font-weight: 700; cursor: pointer;
  transition: all 0.3s; box-shadow: 0 4px 14px rgba(59,130,246,0.3);
}
.btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(59,130,246,0.4); }

/* ── Tabs ── */
.glass-tabs {
  display: flex; gap: 8px; padding: 6px;
  background: var(--bg-card); border: 1px solid var(--border-color);
  border-radius: 14px; margin-bottom: 24px; max-width: fit-content;
}
.tab-btn {
  display: flex; align-items: center; gap: 8px;
  padding: 10px 20px; border-radius: 10px; border: none;
  background: transparent; color: var(--text-secondary);
  font-size: 13.5px; font-weight: 700; cursor: pointer; transition: all 0.3s;
}
.tab-btn.active {
  background: var(--bg-primary); color: #3b82f6;
  box-shadow: 0 4px 12px rgba(0,0,0,0.02);
}
.tab-btn:hover:not(.active) {
  color: var(--text-primary); background: rgba(0,0,0,0.02);
}

/* ── Filter Glass Bar ── */
.filter-glass-bar {
  display: flex; align-items: center; justify-content: space-between; gap: 16px;
  background: var(--bg-card); border: 1px solid var(--border-color);
  padding: 12px 18px; border-radius: 18px; margin-bottom: 24px;
}
.date-range-filter { display: flex; align-items: center; gap: 16px; }
.date-input-wrap { display: flex; align-items: center; gap: 8px; }
.date-label { font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; }
.date-input {
  height: 40px; padding: 0 12px; border-radius: 10px;
  background: var(--bg-primary); border: 1px solid var(--border-color);
  color: var(--text-primary); font-size: 13px; outline: none;
}
.btn-refresh {
  width: 40px; height: 40px; border-radius: 10px;
  background: var(--bg-primary); border: 1px solid var(--border-color);
  color: var(--text-secondary); cursor: pointer; transition: all 0.2s;
  display: flex; align-items: center; justify-content: center;
}
.btn-refresh:hover { border-color: #3b82f6; color: #3b82f6; }
.btn-refresh.spinning svg { animation: spin 1s linear infinite; }

/* ── Table Card View ── */
.table-card-wrapper {
  background: var(--bg-card); border: 1px solid var(--border-color);
  border-radius: 20px; overflow-x: auto; box-shadow: 0 4px 20px rgba(0,0,0,0.01);
}
.premium-table { width: 100%; border-collapse: collapse; text-align: left; }
.premium-table th {
  padding: 16px 20px; background: var(--bg-primary);
  color: var(--text-muted); font-size: 11px; font-weight: 800;
  text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid var(--border-color);
}
.table-row { border-bottom: 1px solid var(--border-color); transition: background 0.2s; }
.table-row:hover { background: rgba(0,0,0,0.01); }
.table-row td { padding: 18px 20px; font-size: 13.5px; vertical-align: middle; }

.cell-date { display: flex; flex-direction: column; }
.primary-text { font-weight: 600; color: var(--text-primary); }
.secondary-text { font-size: 11px; color: var(--text-muted); margin-top: 2px; }

.recipe-cell { display: flex; align-items: center; gap: 8px; }
.recipe-name { font-weight: 600; color: var(--text-primary); }
.recipe-badge {
  padding: 2px 6px; border-radius: 4px; font-size: 9px; font-weight: 700;
  background: rgba(59,130,246,0.1); color: #3b82f6; text-transform: uppercase;
}

.qty-produced { font-weight: 700; color: #22c55e; display: block; }
.target-stock-name { font-size: 11.5px; color: var(--text-muted); }

.cell-cost { font-weight: 700; color: var(--text-primary); }
.operator-badge {
  padding: 4px 8px; border-radius: 6px; font-size: 11px; font-weight: 600;
  background: var(--bg-primary); border: 1px solid var(--border-color);
}
.notes-text {
  max-width: 150px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  display: block; color: var(--text-secondary);
}

.btn-table-action.delete {
  padding: 6px 12px; border-radius: 8px; border: 1px solid rgba(239,68,68,0.2);
  background: rgba(239,68,68,0.05); color: #ef4444; font-size: 11.5px; font-weight: 700;
  cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; gap: 6px;
}
.btn-table-action.delete:hover {
  background: #ef4444; color: #fff; border-color: #ef4444;
}

.text-right { text-align: right; }

/* ── Pagination ── */
.pagination-bar {
  display: flex; align-items: center; justify-content: space-between;
  padding: 16px 20px; border-top: 1px solid var(--border-color);
}
.pagination-info { font-size: 12.5px; color: var(--text-muted); }
.pagination-buttons { display: flex; gap: 8px; }
.btn-pagination {
  padding: 6px 14px; border-radius: 8px; border: 1px solid var(--border-color);
  background: var(--bg-primary); color: var(--text-secondary); font-size: 12px;
  font-weight: 700; cursor: pointer; transition: all 0.2s;
}
.btn-pagination:hover:not(:disabled) { border-color: #3b82f6; color: #3b82f6; }
.btn-pagination:disabled { opacity: 0.5; cursor: not-allowed; }

/* ── Recipes View ── */
.section-actions-bar {
  display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;
}
.section-title-tab {
  font-size: 16px; font-weight: 600; color: var(--text-primary);
  display: flex; align-items: center; gap: 8px;
}

/* ── Recipe Grid ── */
.recipe-grid {
  display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px;
}
.recipe-card {
  background: var(--bg-card); border: 1px solid var(--border-color);
  border-radius: 20px; transition: all 0.3s; animation: slideUp 0.5s ease both;
}
.recipe-card:hover { transform: translateY(-5px); border-color: #3b82f6; }

.card-header {
  display: flex; align-items: center; gap: 14px;
  padding: 20px 20px 0;
}
.card-icon {
  width: 44px; height: 44px; border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
}
.card-icon.production { background: rgba(59,130,246,0.1); color: #3b82f6; }
.card-info { flex: 1; min-width: 0; }
.card-name { font-size: 15px; font-weight: 600; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.card-product { font-size: 11px; color: var(--text-muted); margin-top: 2px; }
.type-badge {
  padding: 4px 10px; border-radius: 100px; font-size: 10px; font-weight: 700; text-transform: uppercase;
}
.type-badge.production { background: rgba(59,130,246,0.1); color: #3b82f6; }

.card-body { padding: 16px 20px; }
.stat-row { display: flex; gap: 16px; }
.stat-row.mt-12 { margin-top: 12px; }
.stat { flex: 1; }
.stat.full-width { flex: 0 0 100%; }
.stat-label { font-size: 10px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; display: block; margin-bottom: 6px; }
.stat-value { font-size: 14px; font-weight: 700; color: var(--text-primary); }
.stat-value.text-accent { color: #3b82f6; }

.mini-ingredients-list { display: flex; flex-wrap: wrap; gap: 6px; }
.mini-ing-badge {
  font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 6px;
  background: var(--bg-primary); border: 1px solid var(--border-color); color: var(--text-secondary);
}

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
.action-btn.edit:hover { background: #3b82f6; color: #fff; border-color: #3b82f6; }
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
  width: 44px; height: 44px; border-radius: 12px; background: rgba(59,130,246,0.1); color: #3b82f6;
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
.modal-scrollable { overflow-y: auto; flex: 1; }

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
.input-hint { font-size: 11px; color: var(--text-muted); }
.premium-input {
  width: 100%; padding: 10px 14px; border-radius: 12px;
  background: var(--bg-primary); border: 1px solid var(--border-color);
  color: var(--text-primary); font-size: 13.5px; outline: none; transition: all 0.2s;
}
.premium-input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
.premium-input.textarea { resize: vertical; min-height: 80px; }

.multiplier-display {
  display: flex; flex-direction: column; justify-content: center; height: 44px;
  padding: 0 14px; border-radius: 12px; background: rgba(59,130,246,0.05);
  border: 1px solid rgba(59,130,246,0.1);
}
.multiplier-display span { font-size: 16px; font-weight: 800; color: #3b82f6; }
.multiplier-display small { font-size: 10px; color: var(--text-muted); }

/* Preview Ingredients list */
.ingredients-section {
  border: 1px solid var(--border-color); border-radius: 16px; padding: 18px;
  background: var(--bg-primary);
}
.ingredients-section .section-header {
  display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px;
}
.ingredients-section h4 {
  font-size: 13px; font-weight: 700; color: var(--text-primary); display: flex; align-items: center; gap: 8px;
}
.status-summary-badge {
  padding: 4px 10px; border-radius: 8px; font-size: 11px; font-weight: 700;
}
.status-summary-badge.success { background: rgba(34,197,94,0.1); color: #22c55e; }
.status-summary-badge.danger { background: rgba(239,68,68,0.1); color: #ef4444; }

.ingredients-preview-table { border: 1px solid var(--border-color); border-radius: 10px; overflow: hidden; }
.preview-header {
  display: grid; grid-template-columns: 2fr 1fr 1fr 1fr 1fr; padding: 8px 12px;
  background: var(--bg-secondary); color: var(--text-muted); font-size: 10px; font-weight: 800; text-transform: uppercase;
}
.preview-row {
  display: grid; grid-template-columns: 2fr 1fr 1fr 1fr 1fr; padding: 10px 12px;
  border-top: 1px solid var(--border-color); font-size: 12.5px; align-items: center; background: var(--bg-card);
}
.preview-row.insufficient { background: rgba(239,68,68,0.02); }
.ing-type-badge {
  font-size: 10px; font-weight: 700; padding: 2px 6px; border-radius: 4px; display: inline-block; width: fit-content; text-transform: uppercase;
}
.ing-type-badge.gudang { background: rgba(107,114,128,0.1); color: #6b7280; }
.ing-type-badge.kitchen { background: rgba(245,158,11,0.1); color: #f59e0b; }

.ing-qty-val { font-family: monospace; }
.status-warning-pill {
  padding: 2px 6px; border-radius: 4px; font-size: 10px; font-weight: 700;
  background: rgba(239,68,68,0.1); color: #ef4444;
}
.text-success { color: #22c55e; }

.modal-bottom { padding: 24px; border-top: 1px solid var(--border-color); display: flex; justify-content: flex-end; }
.btn-save {
  display: flex; align-items: center; gap: 8px;
  background: linear-gradient(135deg, #3b82f6, #60a5fa);
  color: #fff; border: none; padding: 12px 24px; border-radius: 12px;
  font-size: 14px; font-weight: 700; cursor: pointer; transition: all 0.2s;
}
.btn-save:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(59,130,246,0.3); }
.btn-save:disabled { opacity: 0.5; cursor: not-allowed; }

/* Skeleton loaders */
.loading-grid { display: grid; grid-template-columns: 1fr; gap: 16px; }
.skeleton-card { height: 80px; border-radius: 14px; background: var(--border-color); opacity: 0.6; animation: pulse 1.5s infinite; }

/* Empty state */
.empty-placeholder {
  text-align: center; padding: 60px 20px; background: var(--bg-card);
  border: 1px dashed var(--border-color); border-radius: 24px;
}
.empty-icon {
  width: 80px; height: 80px; border-radius: 50%; background: var(--bg-primary);
  display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; color: var(--text-muted);
}
.empty-placeholder h3 { font-size: 18px; font-weight: 600; color: var(--text-primary); margin-bottom: 8px; }
.empty-placeholder p { font-size: 13.5px; color: var(--text-secondary); margin-bottom: 24px; max-width: 400px; margin-left: auto; margin-right: auto; }

/* Animations */
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
@keyframes pulse { 0% { opacity: 0.6; } 50% { opacity: 0.3; } 100% { opacity: 0.6; } }
@keyframes slideUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

/* Light / Dark Mode Tokens mapping */
.dark .tab-btn.active {
  background: rgba(255,255,255,0.05); color: #60a5fa;
}
</style>
