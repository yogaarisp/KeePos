<template>
  <div class="kitchen-container">
    <!-- Hero Section -->
    <div class="page-hero">
      <div class="hero-content">
        <div class="hero-icon-wrap">
          <ChefHat :size="24" />
        </div>
        <div>
          <h1 class="hero-title">Stok Dapur</h1>
          <p class="hero-subtitle">Kelola stok dapur, transfer dari gudang, konversi satuan, dan tracking penggunaan bahan.</p>
        </div>
      </div>
      <div class="hero-actions">
        <button class="btn-secondary" @click="openTransferModal()">
          <ArrowRightLeft :size="18" /> Transfer Gudang
        </button>
        <button class="btn-primary" @click="openManualModal()">
          <PlusCircle :size="18" /> Tambah Bahan
        </button>
      </div>
    </div>

    <!-- Toast Notification -->
    <Transition name="toast">
      <div v-if="toast.show" class="toast-bar" :class="toast.type">
        <CheckCircle2 v-if="toast.type === 'success'" :size="18" />
        <AlertCircle v-else :size="18" />
        <span>{{ toast.message }}</span>
        <button class="toast-close" @click="toast.show = false"><X :size="14" /></button>
      </div>
    </Transition>

    <!-- Filter Bar -->
    <div class="filter-glass-bar">
      <div class="search-box">
        <Search :size="18" class="search-icon" />
        <input type="text" v-model="store.filters.search" placeholder="Cari bahan dapur..." @keyup.enter="refresh">
        <button v-if="store.filters.search" class="clear-search" @click="store.filters.search = ''; refresh()"><X :size="14" /></button>
      </div>
      <div class="filter-group">
        <select v-model="store.filters.category_id" @change="refresh" class="filter-select">
          <option value="">Semua Kategori</option>
          <option v-for="cat in store.categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
        </select>
        <button class="btn-refresh" @click="resetAll" title="Reset"><RotateCcw :size="16" /></button>
      </div>
      <label class="toggle-pill" :class="{ active: store.filters.low_stock }">
        <input type="checkbox" v-model="store.filters.low_stock" @change="refresh" hidden>
        <AlertTriangle :size="14" /> Stok Rendah
      </label>
    </div>

    <!-- Data Table -->
    <div class="table-outer-card">
      <div v-if="store.loading && !store.items.length" class="table-loading">
        <div class="loading-spinner"></div>
        <span>Memuat data stok dapur...</span>
      </div>

      <div v-else-if="!store.items.length" class="table-empty">
        <div class="empty-illu"><ChefHat :size="48" /></div>
        <h3>Dapur Kosong</h3>
        <p>Belum ada bahan di dapur. Transfer dari gudang atau input manual.</p>
        <div style="display:flex;gap:12px;justify-content:center;margin-top:16px">
          <button class="btn-secondary" @click="openManualModal()">Input Manual</button>
          <button class="btn-primary" @click="openTransferModal()">Transfer Gudang</button>
        </div>
      </div>

      <div v-else class="table-scroll-wrap">
        <table class="premium-table">
          <thead>
            <tr>
              <th @click="store.setSort('name')" class="sortable">
                <div class="th-inner">Bahan Dapur <SortIcon :field="'name'" :current="store.sortBy" :dir="store.sortDir" /></div>
              </th>
              <th><div class="th-inner">Kategori</div></th>
              <th @click="store.setSort('stock')" class="sortable text-right">
                <div class="th-inner justify-end">Stok <SortIcon :field="'stock'" :current="store.sortBy" :dir="store.sortDir" /></div>
              </th>
              <th @click="store.setSort('cost_price')" class="sortable text-right">
                <div class="th-inner justify-end">Harga Beli <SortIcon :field="'cost_price'" :current="store.sortBy" :dir="store.sortDir" /></div>
              </th>
              <th class="text-right"><div class="th-inner justify-end">Total Nilai</div></th>
              <th class="text-center"><div class="th-inner justify-center">Sumber</div></th>
              <th class="text-center"><div class="th-inner justify-center">Konversi</div></th>
              <th class="text-center"><div class="th-inner justify-center">Aksi</div></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(item, idx) in store.items" :key="item.id" class="table-row" :style="{ animationDelay: (idx * 0.03) + 's' }">
              <td data-label="Bahan Dapur">
                <div class="item-info-wrap">
                  <div class="item-icon-box" :class="item.is_manual ? 'manual' : 'transfer'">
                    <Pencil v-if="item.is_manual" :size="16" />
                    <Warehouse v-else :size="16" />
                  </div>
                  <div class="item-text">
                    <span class="i-name">{{ item.manual_item_name || item.name }}</span>
                    <span class="i-unit">{{ getPrimaryConversionValue(item)?.unit || item.unit }}</span>
                  </div>
                </div>
              </td>
              <td data-label="Kategori"><span class="cat-badge" v-if="item.category">{{ item.category.name }}</span><span v-else class="cat-badge muted">—</span></td>
              <td data-label="Stok" class="text-right">
                <div class="stock-val-wrap" :class="{ low: Number(item.stock) <= Number(item.min_stock) && item.min_stock > 0 }">
                  <template v-if="getPrimaryConversionValue(item)">
                    <div class="s-main-row">
                      <span class="s-main">{{ getPrimaryConversionValue(item).value }}</span>
                      <span class="s-unit">{{ getPrimaryConversionValue(item).unit }}</span>
                    </div>
                    <!-- Original warehouse stock as secondary -->
                    <div class="s-secondary">
                      <ArrowRightLeft :size="10" /> {{ formatDecimal(item.stock) }} {{ item.unit }}
                    </div>
                  </template>
                  <template v-else>
                    <div class="s-main-row">
                      <span class="s-main">{{ formatDecimal(item.stock) }}</span>
                      <span class="s-unit">{{ item.unit }}</span>
                    </div>
                  </template>
                </div>
              </td>
              <td data-label="Harga Pokok" class="text-right"><span class="price-val">{{ formatCurrency(item.cost_price) }}</span></td>
              <td data-label="Total Nilai" class="text-right"><span class="total-val">{{ formatCurrency(item.stock * item.cost_price) }}</span></td>
              <td data-label="Sumber" class="text-center">
                <span class="source-badge" :class="item.is_manual ? 'manual' : 'gudang'">
                  {{ item.is_manual ? 'Manual' : 'Gudang' }}
                </span>
              </td>
              <td data-label="Konversi" class="text-center">
                <button class="conv-btn" @click="openConversionModal(item)">
                  <RefreshCw :size="12" /> {{ getCustomConversionCount(item.conversions) }}
                </button>
              </td>
              <td data-label="Aksi" class="text-center">
                <div class="action-btn-group">
                  <button class="btn-act plus" @click="openTopupModal(item)" title="Tambah Stok"><Plus :size="14" /></button>
                  <button class="btn-act consume" @click="openConsumeModal(item)" title="Konsumsi"><Flame :size="14" /></button>
                  <button v-if="!item.is_manual" class="btn-act return" @click="openReturnModal(item)" title="Return Gudang"><Undo2 :size="14" /></button>
                  <button class="btn-act edit" @click="openEditModal(item)" title="Edit"><Edit3 :size="14" /></button>
                  <button class="btn-act delete" @click="handleDelete(item)" title="Hapus"><Trash2 :size="14" /></button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="pagination-area" v-if="store.pagination.last_page > 1">
        <div class="pagin-info">Menampilkan <strong>{{ store.items.length }}</strong> dari <strong>{{ store.pagination.total }}</strong> bahan</div>
        <div class="pagin-btns">
          <button class="pagin-btn" :disabled="store.pagination.current_page === 1" @click="store.fetchItems(store.pagination.current_page - 1)"><ChevronLeft :size="18" /></button>
          <span class="pagin-current">{{ store.pagination.current_page }} / {{ store.pagination.last_page }}</span>
          <button class="pagin-btn" :disabled="store.pagination.current_page === store.pagination.last_page" @click="store.fetchItems(store.pagination.current_page + 1)"><ChevronRight :size="18" /></button>
        </div>
      </div>
    </div>

    <!-- ==== MODALS (Teleported) ==== -->
    <Teleport to="body">
      <!-- Transfer Modal -->
      <Transition name="modal">
        <div v-if="transferModal.show" class="modal-backdrop" @click.self="transferModal.show = false">
          <div class="modal-panel">
            <div class="modal-top">
              <div class="modal-header-content">
                <button class="btn-back-header" @click="transferModal.show = false">
                  <ArrowLeft :size="20" />
                </button>
                <div class="modal-icon-wrap green">
                  <ArrowRightLeft :size="20" />
                </div>
                <div class="modal-title-area">
                  <h3 class="modal-title">Transfer dari Gudang</h3>
                  <p class="modal-desc">Pindahkan bahan dari gudang ke stok dapur</p>
                </div>
              </div>
            </div>
            <div class="modal-content">
              <div class="input-group">
                <label class="input-label">Pilih Bahan dari Gudang</label>
                <select v-model="transferModal.form.warehouse_item_id" class="premium-input" @change="onSelectWarehouseItem">
                  <option value="">-- Pilih Bahan --</option>
                  <option v-for="g in store.warehouseItems" :key="g.id" :value="g.id">
                    {{ g.name }} (Stok: {{ formatDecimal(g.stock) }} {{ g.unit }} @ {{ formatCurrency(g.price_per_unit) }})
                  </option>
                </select>
              </div>
              <div v-if="selectedWarehouseItem" class="info-card mt-3">
                <div class="info-row"><span>Bahan</span><strong>{{ selectedWarehouseItem.name }}</strong></div>
                <div class="info-row"><span>Stok Gudang</span><strong>{{ formatDecimal(selectedWarehouseItem.stock) }} {{ selectedWarehouseItem.unit }}</strong></div>
                <div class="info-row"><span>Harga/Unit</span><strong>{{ formatCurrency(selectedWarehouseItem.price_per_unit) }}</strong></div>
              </div>
              <div class="form-grid-2 mt-3">
                <div class="input-group">
                  <label class="input-label">Jumlah Transfer</label>
                  <input type="number" v-model="transferModal.form.quantity" class="premium-input" step="0.01" placeholder="0">
                </div>
                <div class="input-group">
                  <label class="input-label">Satuan</label>
                  <input type="text" :value="selectedWarehouseItem?.unit || '-'" class="premium-input" disabled>
                </div>
              </div>
              <div class="input-group mt-3">
                <label class="input-label">Catatan</label>
                <textarea v-model="transferModal.form.notes" class="premium-input text-area" rows="2" placeholder="Catatan transfer..."></textarea>
              </div>
            </div>
            <div class="modal-bottom">
              <button class="btn-action green w-100 justify-content-center" @click="handleTransfer" :disabled="store.loading || !transferModal.form.warehouse_item_id || transferModal.form.quantity <= 0">
                <ArrowRightLeft :size="18" /> Transfer ke Dapur
              </button>
            </div>
          </div>
        </div>
      </Transition>

      <!-- Manual Input Modal -->
      <Transition name="modal">
        <div v-if="manualModal.show" class="modal-backdrop" @click.self="manualModal.show = false">
          <div class="modal-panel">
            <div class="modal-top">
              <div class="modal-header-content">
                <button class="btn-back-header" @click="manualModal.show = false">
                  <ArrowLeft :size="20" />
                </button>
                <div class="modal-icon-wrap orange">
                  <PlusCircle :size="20" />
                </div>
                <div class="modal-title-area">
                  <h3 class="modal-title">{{ manualModal.mode === 'edit' ? 'Edit Bahan Dapur' : 'Input Manual Stok' }}</h3>
                  <p class="modal-desc">{{ manualModal.mode === 'edit' ? 'Ubah detail bahan yang sudah ada' : 'Tambah bahan langsung ke dapur tanpa melalui gudang' }}</p>
                </div>
              </div>
            </div>
            <div class="modal-content">
              <div class="form-grid-2">
                <div class="input-group">
                  <label class="input-label">Nama Bahan *</label>
                  <input type="text" v-model="manualModal.form.name" class="premium-input" placeholder="E.g. Bawang Merah">
                </div>
                <div class="input-group">
                  <label class="input-label">Kategori</label>
                  <select v-model="manualModal.form.category_id" class="premium-input">
                    <option value="">Pilih Kategori</option>
                    <option v-for="cat in store.categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                  </select>
                </div>
              </div>
              <div class="form-grid-2 mt-3">
                <div class="input-group">
                  <label class="input-label">Satuan *</label>
                  <select v-model="manualModal.form.unit" class="premium-input">
                    <option value="" disabled>Pilih Satuan</option>
                    <option v-for="u in unitStore.units" :key="u.id" :value="u.abbreviation">
                      {{ u.name }} ({{ u.abbreviation }})
                    </option>
                  </select>
                </div>
                <div class="input-group">
                  <label class="input-label">Harga Beli / Unit *</label>
                  <input type="number" v-model="manualModal.form.cost_price" class="premium-input" placeholder="0">
                </div>
              </div>
              <div class="form-grid-2 mt-3" v-if="manualModal.mode === 'add'">
                <div class="input-group">
                  <label class="input-label">Stok Awal *</label>
                  <input type="number" v-model="manualModal.form.stock" class="premium-input" step="0.01" placeholder="0">
                </div>
                <div class="input-group">
                  <label class="input-label">Min. Stok (Peringatan)</label>
                  <input type="number" v-model="manualModal.form.min_stock" class="premium-input" placeholder="0">
                </div>
              </div>
              <div v-if="manualModal.mode === 'edit'" class="input-group mt-3">
                <label class="input-label">Min. Stok (Peringatan)</label>
                <input type="number" v-model="manualModal.form.min_stock" class="premium-input" placeholder="0">
              </div>
              <div class="input-group mt-3">
                <label class="input-label">Catatan</label>
                <textarea v-model="manualModal.form.notes" class="premium-input text-area" rows="2" placeholder="Supplier, lokasi beli, dll..."></textarea>
              </div>
            </div>
            <div class="modal-bottom">
              <button class="btn-action orange w-100 justify-content-center" @click="handleManualSave" :disabled="store.loading || !manualModal.form.name || !manualModal.form.unit">
                <Save :size="18" /> {{ manualModal.mode === 'edit' ? 'Update' : 'Simpan' }}
              </button>
            </div>
          </div>
        </div>
      </Transition>

      <!-- Consume Modal -->
      <Transition name="modal">
        <div v-if="consumeModal.show" class="modal-backdrop" @click.self="consumeModal.show = false">
          <div class="modal-panel small">
            <div class="modal-top">
              <div class="modal-header-content">
                <button class="btn-back-header" @click="consumeModal.show = false">
                  <ArrowLeft :size="20" />
                </button>
                <div class="modal-icon-wrap red">
                  <Flame :size="20" />
                </div>
                <div class="modal-title-area">
                  <h3 class="modal-title">Konsumsi Stok</h3>
                  <p class="modal-desc" v-if="consumeModal.item">
                    {{ consumeModal.item.name }} — 
                    <template v-if="getPrimaryConversionValue(consumeModal.item)">
                      Stok: {{ getPrimaryConversionValue(consumeModal.item).value }} {{ getPrimaryConversionValue(consumeModal.item).unit }}
                      <span class="opacity-70">(= {{ formatDecimal(consumeModal.item.stock) }} {{ consumeModal.item.unit }})</span>
                    </template>
                    <template v-else>
                      Stok: {{ formatDecimal(consumeModal.item.stock) }} {{ consumeModal.item.unit }}
                    </template>
                  </p>
                </div>
              </div>
            </div>
            <div class="modal-content">
              <div class="input-group">
                <label class="input-label">Jumlah yang Dipakai ({{ consumeModal.item?.unit }})</label>
                <input type="number" v-model="consumeModal.quantity" class="premium-input" step="0.01" placeholder="0">
              </div>
              <div class="input-group mt-3">
                <label class="input-label">Catatan</label>
                <textarea v-model="consumeModal.notes" class="premium-input text-area" rows="2" placeholder="E.g. Untuk produksi Nasi Goreng"></textarea>
              </div>
              <div v-if="consumeModal.quantity > 0" class="result-preview mt-3">
                Sisa stok: <strong>{{ formatDecimal(consumeModal.item?.stock - consumeModal.quantity) }} {{ consumeModal.item?.unit }}</strong>
              </div>
            </div>
            <div class="modal-bottom">
              <button class="btn-action red w-100 justify-content-center" @click="handleConsume" :disabled="store.loading || consumeModal.quantity <= 0 || consumeModal.quantity > consumeModal.item?.stock">
                <Flame :size="18" /> Konfirmasi Konsumsi
              </button>
            </div>
          </div>
        </div>
      </Transition>

      <!-- Return Modal -->
      <Transition name="modal">
        <div v-if="returnModal.show" class="modal-backdrop" @click.self="returnModal.show = false">
          <div class="modal-panel small">
            <div class="modal-top">
              <div class="modal-header-content">
                <button class="btn-back-header" @click="returnModal.show = false">
                  <ArrowLeft :size="20" />
                </button>
                <div class="modal-icon-wrap blue">
                  <Undo2 :size="20" />
                </div>
                <div class="modal-title-area">
                  <h3 class="modal-title">Return ke Gudang</h3>
                  <p class="modal-desc" v-if="returnModal.item">
                    {{ returnModal.item.name }} — 
                    <template v-if="getPrimaryConversionValue(returnModal.item)">
                      Stok dapur: {{ getPrimaryConversionValue(returnModal.item).value }} {{ getPrimaryConversionValue(returnModal.item).unit }}
                      <span class="opacity-70">(= {{ formatDecimal(returnModal.item.stock) }} {{ returnModal.item.unit }})</span>
                    </template>
                    <template v-else>
                      Stok dapur: {{ formatDecimal(returnModal.item.stock) }} {{ returnModal.item.unit }}
                    </template>
                  </p>
                </div>
              </div>
            </div>
            <div class="modal-content">
              <div class="input-group">
                <label class="input-label">Jumlah Return ({{ returnModal.item?.unit }})</label>
                <input type="number" v-model="returnModal.quantity" class="premium-input" step="0.01" placeholder="0">
              </div>
              <div class="input-group mt-3">
                <label class="input-label">Catatan</label>
                <textarea v-model="returnModal.notes" class="premium-input text-area" rows="2" placeholder="E.g. Kelebihan stok"></textarea>
              </div>
            </div>
            <div class="modal-bottom">
              <button class="btn-action blue w-100 justify-content-center" @click="handleReturn" :disabled="store.loading || returnModal.quantity <= 0 || returnModal.quantity > returnModal.item?.stock">
                <Undo2 :size="18" /> Return ke Gudang
              </button>
            </div>
          </div>
        </div>
      </Transition>

      <!-- Conversion Modal -->
      <Transition name="modal">
        <div v-if="convModal.show" class="modal-backdrop" @click.self="convModal.show = false">
          <div class="modal-panel">
            <div class="modal-top">
              <div class="modal-header-content">
                <button class="btn-back-header" @click="convModal.show = false">
                  <ArrowLeft :size="20" />
                </button>
                <div class="modal-icon-wrap purple">
                  <RefreshCw :size="20" />
                </div>
                <div class="modal-title-area">
                  <h3 class="modal-title">Konversi Satuan</h3>
                  <p class="modal-desc">{{ convModal.item?.name }} — {{ formatDecimal(convModal.item?.stock) }} {{ convModal.item?.unit }}</p>
                </div>
              </div>
            </div>
            <div class="modal-content">
              <!-- Existing conversions -->
              <div v-if="convModal.conversions.some(c => !(c.base_unit === c.convert_to_unit && Number(c.ratio) === 1))" class="conv-list">
                <template v-for="c in convModal.conversions" :key="c.id">
                  <div v-if="!(c.base_unit === c.convert_to_unit && Number(c.ratio) === 1)" class="conv-item-premium">
                    <div class="conv-main-info">
                      <div class="conv-top-row">
                        <span class="conv-formula-tag">1 {{ c.base_unit }} = {{ formatDecimal(c.ratio) }} {{ c.convert_to_unit }}</span>
                        <div class="conv-actions-wrap">
                          <button class="btn-icon-tiny edit" @click="startEditConv(c)"><Edit3 :size="12" /></button>
                          <button class="btn-icon-tiny delete" @click="handleDeleteConv(c)"><Trash2 :size="12" /></button>
                        </div>
                      </div>
                      <div class="conv-details-grid">
                        <div class="conv-detail-card">
                          <span class="detail-label">ESTIMASI STOK</span>
                          <span class="detail-value">{{ formatDecimal(convModal.item?.stock * c.ratio) }} {{ c.convert_to_unit }}</span>
                        </div>
                        <div class="conv-detail-card highlight">
                          <span class="detail-label">ESTIMASI HPP / {{ c.convert_to_unit }}</span>
                          <span class="detail-value">{{ formatCurrency(convModal.item?.cost_price / c.ratio) }}</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </template>
              </div>
              <div v-else class="empty-conv">
                <p>Belum ada konversi khusus untuk bahan ini.</p>
              </div>



              <!-- Add/Edit conversion form -->
              <div v-if="convModal.showForm" class="conv-add-section mt-4 pt-4">
                <div class="section-title-wrap mb-3" v-if="convModal.mode === 'edit'">
                   <span class="input-label-premium text-orange">EDIT KONVERSI</span>
                </div>

                <div class="form-grid-2">
                  <div class="input-group">
                    <label class="input-label">SATUAN DASAR <span class="text-red">*</span></label>
                    <div class="disabled-pill">{{ convModal.item?.unit }}</div>
                  </div>
                  <div class="input-group">
                    <label class="input-label">SATUAN TUJUAN <span class="text-red">*</span></label>
                    <select v-model="convModal.newUnit" class="premium-input" :disabled="convModal.mode === 'edit'">
                      <option value="" disabled>Pilih Satuan Tujuan</option>
                      <option v-for="u in unitStore.units" :key="u.id" :value="u.abbreviation">
                        {{ u.name }} ({{ u.abbreviation }})
                      </option>
                    </select>
                  </div>
                </div>

                <div class="input-group mt-3">
                  <label class="input-label">RASIO KONVERSI <span class="text-red">*</span></label>
                  <div class="ratio-input-wrapper">
                    <div class="ratio-tag">1 {{ convModal.item?.unit }} =</div>
                    <input type="number" v-model="convModal.newRatio" class="premium-input" step="0.001" placeholder="Masukkan angka rasio...">
                    <div class="ratio-unit-tag">{{ convModal.newUnit || '...' }}</div>
                  </div>
                  <p class="input-hint">Contoh: 1 Dus = 24 Botol, maka isi angka 24.</p>
                </div>

                <!-- Live Preview Calculation -->
                <div v-if="convModal.newRatio > 0 && convModal.newUnit" class="preview-calc-card mt-3">
                  <div class="preview-header">
                    <CheckCircle2 :size="14" />
                    <span>PREVIEW PERHITUNGAN</span>
                  </div>
                  <div class="preview-body">
                    <div class="p-item">
                      <span>Total Stok jika dikonversi</span>
                      <strong>{{ formatDecimal(convModal.item?.stock * convModal.newRatio) }} {{ convModal.newUnit }}</strong>
                    </div>
                    <div class="p-item">
                      <span>HPP per {{ convModal.newUnit }}</span>
                      <strong class="text-orange">{{ formatCurrency(convModal.item?.cost_price / convModal.newRatio) }}</strong>
                    </div>
                  </div>
                </div>

                <div class="flex-btn-group mt-4">
                   <button class="btn-action green" style="flex: 1" @click="handleSaveConv" :disabled="!convModal.newUnit || convModal.newRatio <= 0">
                     <Save v-if="convModal.mode === 'edit'" :size="18" />
                     <Plus v-else :size="18" />
                     {{ convModal.mode === 'edit' ? 'Simpan Perubahan' : 'Tambah Konversi' }}
                   </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Topup Modal -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="topupModal.show" class="modal-backdrop" @click.self="topupModal.show = false">
          <div class="modal-panel">
            <div class="modal-top">
              <div class="modal-header-content">
                <button class="btn-back-header" @click="topupModal.show = false">
                  <ArrowLeft :size="20" />
                </button>
                <div class="modal-icon-wrap orange">
                  <PlusCircle :size="24" />
                </div>
                <div class="modal-title-area">
                  <h3 class="modal-title">Tambah Stok</h3>
                  <p class="modal-desc">Perbarui jumlah bahan</p>
                </div>
              </div>

              <!-- Header Toggle -->
              <div v-if="!topupModal.item?.is_manual" class="segmented-control-header">
                <div class="seg-item" :class="{ active: topupModal.mode === 'manual' }" @click="topupModal.mode = 'manual'">
                  <span>Beli</span>
                </div>
                <div class="seg-item" :class="{ active: topupModal.mode === 'transfer' }" @click="topupModal.mode = 'transfer'; topupModal.selectedUnit = topupModal.item.unit">
                  <span>Gudang</span>
                </div>
              </div>
            </div>
            
            <div class="modal-content">


              <!-- Item Info Summary Card -->
              <div v-if="topupModal.item" class="info-card mb-6 mt-0">
                <div class="flex items-center gap-3 mb-3">
                  <div class="source-badge" :class="topupModal.item.is_manual ? 'manual' : 'gudang'">
                    {{ topupModal.item.is_manual ? 'Input Manual' : 'Sync Gudang' }}
                  </div>
                  <strong class="text-primary">{{ topupModal.item.name }}</strong>
                </div>
                
                <div class="form-grid-2 border-t pt-3 border-dashed">
                  <div>
                    <div class="text-[10px] font-bold text-muted uppercase tracking-wider mb-1">STOK DAPUR</div>
                    <div class="flex flex-col">
                      <template v-if="getPrimaryConversionValue(topupModal.item)">
                        <div class="text-sm font-bold text-orange-600">
                          {{ getPrimaryConversionValue(topupModal.item).value }} {{ getPrimaryConversionValue(topupModal.item).unit }}
                        </div>
                        <div class="text-[11px] font-bold text-muted mt-0.5 opacity-80">
                          = {{ formatDecimal(topupModal.item.stock) }} {{ topupModal.item.unit }}
                        </div>
                      </template>
                      <template v-else>
                        <div class="text-sm font-bold text-orange-600">
                          {{ formatDecimal(topupModal.item.stock) }} {{ topupModal.item.unit }}
                        </div>
                      </template>
                    </div>
                  </div>
                  <div v-if="!topupModal.item.is_manual">
                    <div class="text-[10px] font-bold text-muted uppercase tracking-wider mb-1">STOK GUDANG</div>
                    <div class="text-sm font-bold text-blue-600">{{ formatDecimal(topupModal.warehouseStock) }} {{ topupModal.item.unit }}</div>
                  </div>
                </div>
              </div>

              <!-- Main Input Fields -->
              <div class="form-grid-2">
                <div class="input-group">
                  <div class="flex justify-between items-center mb-2">
                    <label class="input-label">JUMLAH {{ topupModal.mode === 'transfer' ? 'AMBIL' : 'TAMBAHAN' }} <span class="text-red">*</span></label>
                  </div>
                  <div class="ratio-input-wrapper">
                    <input type="number" v-model="topupModal.quantity" class="premium-input" placeholder="0" step="0.01">
                    <span class="ratio-unit-tag">{{ topupModal.item?.unit }}</span>
                  </div>
                </div>
                <div class="input-group">
                  <label class="input-label">{{ topupModal.mode === 'transfer' ? 'TANGGAL TRANSFER' : 'TANGGAL PEMBELIAN' }}</label>
                  <input type="date" v-model="topupModal.date" class="premium-input">
                </div>
              </div>

              <!-- Optional Technical Fields -->
              <div class="form-grid-2 mt-4">
                <div v-if="topupModal.mode === 'manual'" class="input-group">
                  <label class="input-label">HARGA BELI BARU (OPSIONAL)</label>
                  <div class="ratio-input-wrapper">
                    <span class="ratio-tag">Rp</span>
                    <input type="number" v-model="topupModal.cost_price" class="premium-input" placeholder="0">
                  </div>
                  <p v-if="topupModal.selectedUnit !== topupModal.item?.unit" class="text-[10px] font-bold text-muted mt-1">
                    Satuan: {{ topupModal.selectedUnit }}
                  </p>
                </div>
                <div class="input-group" :class="{ 'col-span-2': topupModal.mode === 'transfer' }">
                  <label class="input-label">CATATAN</label>
                  <input type="text" v-model="topupModal.notes" class="premium-input" :placeholder="topupModal.mode === 'transfer' ? 'Contoh: Persiapan stok hari ini...' : 'Pembelian dadakan...'">
                </div>
              </div>
            </div>

            <div class="modal-bottom">
              <button class="btn-action green w-100 justify-content-center" style="padding-left: 32px; padding-right: 32px;" @click="handleTopup" :disabled="topupModal.quantity <= 0 || store.loading">
                <Check :size="18" /> Simpan Stok
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { onMounted, reactive, computed, watch, onUnmounted, h } from 'vue';
import { useKitchenStore } from '../stores/kitchen';
import { showConfirm, showSuccess, showError } from '../utils/swal';
import { useUnitStore } from '../stores/unit';
import {
  ChefHat, ArrowRightLeft, PlusCircle, Search, ArrowLeft, Edit3, Trash2,
  Plus, Minus, ChevronLeft, ChevronRight, RotateCcw, AlertTriangle,
  RefreshCw, Flame, Undo2, Save, ArrowUpDown, ArrowUp, ArrowDown,
  CheckCircle2, AlertCircle, Check, Pencil, Warehouse, X
} from 'lucide-vue-next';

const store = useKitchenStore();
const unitStore = useUnitStore();

// Sort icon helper component (inline)
const SortIcon = (props) => {
  if (props.current !== props.field) return null;
  return props.dir === 'asc'
    ? { render: () => h(ArrowUp, { size: 12 }) }
    : { render: () => h(ArrowDown, { size: 12 }) };
};

// Toast
const toast = reactive({ show: false, message: '', type: 'success' });
const showToast = (message, type = 'success') => {
  toast.message = message;
  toast.type = type;
  toast.show = true;
  setTimeout(() => { toast.show = false; }, 3000);
};

// Transfer Modal
const transferModal = reactive({
  show: false,
  form: { warehouse_item_id: '', quantity: 0, notes: '' }
});

const selectedWarehouseItem = computed(() =>
  store.warehouseItems.find(i => i.id == transferModal.form.warehouse_item_id)
);

const onSelectWarehouseItem = () => {
  transferModal.form.quantity = 0;
};

// Manual/Edit Modal
const manualModal = reactive({
  show: false,
  mode: 'add',
  id: null,
  form: { name: '', category_id: '', unit: '', cost_price: 0, stock: 0, min_stock: 0, notes: '' }
});

// Consume Modal
const consumeModal = reactive({ show: false, item: null, quantity: 0, notes: '' });

// Return Modal
const returnModal = reactive({ show: false, item: null, quantity: 0, notes: '' });

// Conversion Modal
const convModal = reactive({
  show: false, item: null, conversions: [],
  mode: 'add', editId: null,
  newRatio: 0, newUnit: '',
  showForm: false
});

// Topup Modal
const topupModal = reactive({
  show: false,
  item: null,
  quantity: 1,
  date: new Date().toISOString().split('T')[0],
  cost_price: 0,
  notes: '',
  selectedUnit: '',
  warehouseStock: 0,
  mode: 'manual', // 'manual' or 'transfer'
});

// Watch modals to toggle mobile nav and body scroll
watch(() => transferModal.show, (val) => {
  if (val) {
    document.body.classList.add('hide-mobile-nav');
    document.body.style.overflow = 'hidden';
  } else {
    document.body.classList.remove('hide-mobile-nav');
    document.body.style.overflow = '';
  }
});

watch(() => manualModal.show, (val) => {
  if (val) {
    document.body.classList.add('hide-mobile-nav');
    document.body.style.overflow = 'hidden';
  } else {
    document.body.classList.remove('hide-mobile-nav');
    document.body.style.overflow = '';
  }
});

watch(() => topupModal.show, (val) => {
  if (val) {
    document.body.classList.add('hide-mobile-nav');
    document.body.style.overflow = 'hidden';
  } else {
    document.body.classList.remove('hide-mobile-nav');
    document.body.style.overflow = '';
  }
});

watch(() => consumeModal.show, (val) => {
  if (val) {
    document.body.classList.add('hide-mobile-nav');
    document.body.style.overflow = 'hidden';
  } else {
    document.body.classList.remove('hide-mobile-nav');
    document.body.style.overflow = '';
  }
});

watch(() => returnModal.show, (val) => {
  if (val) {
    document.body.classList.add('hide-mobile-nav');
    document.body.style.overflow = 'hidden';
  } else {
    document.body.classList.remove('hide-mobile-nav');
    document.body.style.overflow = '';
  }
});

watch(() => convModal.show, (val) => {
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
  store.fetchItems();
  store.fetchWarehouseItems();
  unitStore.fetchUnits();
});

const refresh = () => store.fetchItems(1);
const resetAll = () => { store.resetFilters(); refresh(); };

// --- Modal Openers ---
const openTransferModal = () => {
  transferModal.form = { warehouse_item_id: '', quantity: 0, notes: '' };
  store.fetchWarehouseItems();
  transferModal.show = true;
};

const openManualModal = () => {
  manualModal.mode = 'add';
  manualModal.id = null;
  manualModal.form = { name: '', category_id: '', unit: '', cost_price: 0, stock: 0, min_stock: 0, notes: '' };
  manualModal.show = true;
};

const openEditModal = (item) => {
  manualModal.mode = 'edit';
  manualModal.id = item.id;
  manualModal.form = {
    name: item.manual_item_name || item.name,
    category_id: item.category_id || '',
    unit: item.unit,
    cost_price: item.cost_price,
    stock: item.stock,
    min_stock: item.min_stock,
    notes: item.notes || ''
  };
  manualModal.show = true;
};

const openConsumeModal = (item) => {
  consumeModal.item = item;
  consumeModal.quantity = 0;
  consumeModal.notes = '';
  consumeModal.show = true;
};

const openReturnModal = (item) => {
  returnModal.item = item;
  returnModal.quantity = 0;
  returnModal.notes = '';
  returnModal.show = true;
};

const openTopupModal = (item) => {
  topupModal.item = item;
  topupModal.quantity = 1;
  topupModal.date = new Date().toISOString().split('T')[0];
  topupModal.cost_price = item.cost_price;
  topupModal.notes = '';
  topupModal.selectedUnit = item.unit;
  topupModal.warehouseStock = 0;
  topupModal.mode = item.is_manual ? 'manual' : 'transfer'; // Default based on item type
  
  // Find warehouse stock if available
  if (item.warehouse_item_id) {
    const whItem = store.warehouseItems.find(i => i.id == item.warehouse_item_id);
    if (whItem) topupModal.warehouseStock = whItem.stock;
  }
  
  topupModal.show = true;
};

const openConversionModal = async (item) => {
  convModal.item = item;
  convModal.conversions = item.conversions || [];
  convModal.newRatio = 0;
  convModal.newUnit = '';
  convModal.mode = 'add';
  convModal.showForm = false;
  convModal.show = true;
  
  // Custom logic: show form automatically if no conversions exist
  const customCount = getCustomConversionCount(convModal.conversions);
  if (customCount === 0) convModal.showForm = true;

  // Fetch latest conversions
  const data = await store.getConversions(item.id);
  if (data) {
    convModal.conversions = data.conversions;
  }
};

// --- Handlers ---
const handleTransfer = async () => {
  const result = await store.transferFromWarehouse(transferModal.form);
  if (result?.success) {
    transferModal.show = false;
    showToast(result.message);
  } else {
    showToast(result?.message || 'Gagal transfer', 'error');
  }
};

const handleManualSave = async () => {
  let result;
  if (manualModal.mode === 'add') {
    result = await store.addManualItem(manualModal.form);
  } else {
    result = await store.updateItem(manualModal.id, manualModal.form);
  }
  if (result?.success) {
    manualModal.show = false;
    showToast(result.message);
  } else {
    showToast(result?.message || 'Gagal menyimpan', 'error');
  }
};

const handleConsume = async () => {
  const result = await store.consumeStock(consumeModal.item.id, {
    quantity: consumeModal.quantity,
    notes: consumeModal.notes
  });
  if (result?.success) {
    consumeModal.show = false;
    showToast(result.message);
  } else {
    showToast(result?.message || 'Gagal konsumsi stok', 'error');
  }
};

const handleReturn = async () => {
  const result = await store.returnToWarehouse(returnModal.item.id, {
    quantity: returnModal.quantity,
    notes: returnModal.notes
  });
  if (result?.success) {
    returnModal.show = false;
    showToast(result.message);
  } else {
    showToast(result?.message || 'Gagal return', 'error');
  }
};

const handleDelete = async (item) => {
  const result = await showConfirm({
    title: 'Hapus Bahan',
    text: `Hapus "${item.manual_item_name || item.name}" dari stok dapur?`,
    icon: 'warning',
    confirmText: 'Ya, Hapus',
    cancelText: 'Batal'
  });
  
  if (!result.isConfirmed) return;
  
  const ok = await store.deleteItem(item.id);
  if (ok) showSuccess('Bahan berhasil dihapus');
  else showError('Gagal menghapus bahan');
};

const handleTopup = async () => {
  let finalQty = topupModal.quantity;
  
  // Apply conversion if selected unit is different from base unit
  if (topupModal.selectedUnit !== topupModal.item.unit) {
    const conv = topupModal.item.conversions?.find(c => c.convert_to_unit === topupModal.selectedUnit);
    if (conv) finalQty = topupModal.quantity * conv.ratio;
  }

  let result;
  if (topupModal.mode === 'transfer') {
    // Mode Transfer dari Gudang
    result = await store.transferFromWarehouse({
      warehouse_item_id: topupModal.item.warehouse_item_id,
      quantity: finalQty,
      notes: topupModal.notes
    });
  } else {
    // Mode Penambahan Manual (Beli)
    result = await store.addStock(topupModal.item.id, {
      quantity: finalQty,
      cost_price: topupModal.cost_price,
      notes: topupModal.notes
    });
  }

  if (result?.success) {
    topupModal.show = false;
    showToast(result.message);
  } else {
    showToast(result?.message || 'Gagal perbarui stok', 'error');
  }
};

const openGlobalAddModal = () => {
  // Show a simpler selector first or just pick transfer as safer default
  openTransferModal(); // Or logic to show combined modal
};

// Conversion handlers
const handleSaveConv = async () => {
  let result;
  if (convModal.mode === 'add') {
    result = await store.addConversion(convModal.item.id, {
      convert_to_unit: convModal.newUnit,
      ratio: convModal.newRatio
    });
  } else {
    result = await store.updateConversion(convModal.item.id, convModal.editId, { 
      ratio: convModal.newRatio 
    });
  }

  if (result?.success) {
    const data = await store.getConversions(convModal.item.id);
    if (data) convModal.conversions = data.conversions;
    
    // Reset form
    convModal.newRatio = 0;
    convModal.newUnit = '';
    convModal.mode = 'add';
    
    // Hide form if we have conversions now
    if (getCustomConversionCount(convModal.conversions) > 0) {
      convModal.showForm = false;
    }
    
    showToast(result.message);
    store.fetchItems(store.pagination.current_page);
  } else {
    showToast(result?.message || 'Gagal', 'error');
  }
};

const startEditConv = (c) => {
  convModal.mode = 'edit';
  convModal.editId = c.id;
  convModal.newRatio = c.ratio;
  convModal.newUnit = c.convert_to_unit;
  convModal.showForm = true;
};

const cancelConvForm = () => {
  convModal.mode = 'add';
  convModal.newRatio = 0;
  convModal.newUnit = '';
  if (getCustomConversionCount(convModal.conversions) > 0) {
    convModal.showForm = false;
  }
};

const handleUpdateConv = async () => {
  const result = await store.updateConversion(convModal.item.id, convModal.editId, { ratio: convModal.editRatio });
  if (result?.success) {
    const data = await store.getConversions(convModal.item.id);
    if (data) convModal.conversions = data.conversions;
    convModal.editing = false;
    showToast(result.message);
  } else {
    showToast(result?.message || 'Gagal', 'error');
  }
};

const handleDeleteConv = async (c) => {
  const result = await showConfirm({
    title: 'Hapus Konversi',
    text: `Hapus konversi ke "${c.convert_to_unit}"?`,
    icon: 'warning',
    confirmText: 'Ya, Hapus',
    cancelText: 'Batal'
  });
  
  if (!result.isConfirmed) return;
  
  const result2 = await store.deleteConversion(convModal.item.id, c.id);
  if (result2?.success) {
    const data = await store.getConversions(convModal.item.id);
    if (data) convModal.conversions = data.conversions;
    showToast(result.message);
    store.fetchItems(store.pagination.current_page);
  }
};

// Formatters
const formatCurrency = (value) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value || 0);
const formatDecimal = (value) => Number(value || 0).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 });

const getCustomConversionCount = (conversions) => {
  if (!conversions) return 0;
  return conversions.filter(c => !(c.base_unit === c.convert_to_unit && Number(c.ratio) === 1)).length;
};

const getPrimaryConversionValue = (item) => {
  if (!item.conversions || item.conversions.length === 0) return null;
  const custom = item.conversions.filter(c => !(c.base_unit === c.convert_to_unit && Number(c.ratio) === 1));
  if (custom.length === 0) return null;
  
  const c = custom[0];
  const convertedValue = item.stock * c.ratio;
  return {
    value: formatDecimal(convertedValue),
    unit: c.convert_to_unit
  };
};

const getConvertedDisplay = (item, quantity) => {
  if (!item || !item.conversions || item.conversions.length === 0) return null;
  const custom = item.conversions.filter(c => !(c.base_unit === c.convert_to_unit && Number(c.ratio) === 1));
  if (custom.length === 0) return null;
  const c = custom[0];
  return `${formatDecimal(quantity * c.ratio)} ${c.convert_to_unit}`;
};
</script>

<style scoped>
.kitchen-container { padding: 0; animation: fadeIn 0.4s ease; }

/* Hero */
.page-hero {
  display: flex; align-items: center; justify-content: space-between;
  background: linear-gradient(135deg, rgba(249,115,22,0.08) 0%, rgba(249,115,22,0.02) 100%);
  border: 1px solid rgba(249,115,22,0.1); border-radius: 20px;
  padding: 28px 32px; margin-bottom: 24px;
}
.hero-content { display: flex; align-items: center; gap: 18px; }
.hero-icon-wrap {
  width: 52px; height: 52px; border-radius: 16px;
  background: linear-gradient(135deg, #f97316, #fb923c);
  display: flex; align-items: center; justify-content: center;
  color: #fff; box-shadow: 0 8px 24px rgba(249,115,22,0.25);
}
.hero-title { font-size: 22px; font-weight: 600; color: var(--text-primary); margin-bottom: 4px; }
.hero-subtitle { font-size: 14px; color: var(--text-secondary); font-weight: 500; max-width: 500px; }
.hero-actions { display: flex; align-items: center; }

.segmented-action-group {
  display: flex; align-items: center; background: var(--bg-card); 
  border: 1.5px solid var(--border-color); border-radius: 16px; padding: 4px;
}
.seg-action-btn {
  display: flex; align-items: center; gap: 8px; padding: 10px 20px; 
  border: none; background: transparent; color: var(--text-secondary);
  font-size: 13px; font-weight: 700; cursor: pointer; border-radius: 12px;
  transition: 0.2s;
}
.seg-action-btn:hover { background: var(--bg-primary); color: var(--accent); }
.seg-action-divider { width: 1.5px; height: 20px; background: var(--border-color); margin: 0 2px; }


.btn-primary {
  display: flex; align-items: center; gap: 10px;
  background: linear-gradient(135deg, var(--accent), #fb923c);
  color: #fff; border: none; padding: 12px 24px; border-radius: 12px;
  font-size: 14px; font-weight: 700; cursor: pointer; transition: 0.2s;
}
.btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(249,115,22,0.3); }
.btn-secondary {
  display: flex; align-items: center; gap: 10px;
  background: var(--bg-primary); border: 1.5px solid var(--border-color);
  color: var(--text-primary); padding: 12px 24px; border-radius: 12px;
  font-size: 14px; font-weight: 700; cursor: pointer; transition: 0.2s;
}
.btn-secondary:hover { border-color: var(--accent); color: var(--accent); }

/* Toast */
.toast-bar {
  position: fixed; top: 24px; right: 24px; z-index: 9999;
  display: flex; align-items: center; gap: 12px;
  padding: 14px 20px; border-radius: 14px; font-size: 14px; font-weight: 600;
  box-shadow: 0 12px 30px rgba(0,0,0,0.15); min-width: 280px;
}
.toast-bar.success { background: var(--success); color: #fff; }
.toast-bar.error { background: var(--danger); color: #fff; }
.toast-close { background: none; border: none; color: inherit; cursor: pointer; margin-left: auto; opacity: 0.7; }
.toast-enter-active, .toast-leave-active { transition: all 0.3s ease; }
.toast-enter-from { opacity: 0; transform: translateY(-20px) translateX(20px); }
.toast-leave-to { opacity: 0; transform: translateX(40px); }

/* Filter */
.filter-glass-bar {
  display: flex; align-items: center; gap: 16px;
  background: var(--bg-card); border: 1px solid var(--border-color);
  padding: 12px 16px; border-radius: 18px; margin-bottom: 24px;
}
.search-box { flex: 1; position: relative; }
.search-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-muted); }
.search-box input {
  width: 100%; height: 44px; padding-left: 44px; padding-right: 36px;
  background: var(--bg-primary); border: 1px solid var(--border-color);
  border-radius: 14px; font-size: 14px; color: var(--text-primary); outline: none;
}
.clear-search { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-muted); cursor: pointer; }
.filter-group { display: flex; align-items: center; gap: 10px; }
.filter-label { font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; display: flex; align-items: center; gap: 6px; }
.filter-select {
  height: 44px; padding: 0 16px; border-radius: 14px; background: var(--bg-primary);
  border: 1px solid var(--border-color); color: var(--text-primary); font-weight: 700; font-size: 13px; outline: none;
}
.toggle-pill {
  display: flex; align-items: center; gap: 8px; padding: 10px 16px;
  border-radius: 12px; border: 1.5px solid var(--border-color);
  font-size: 12px; font-weight: 600; cursor: pointer; transition: 0.2s;
}
.toggle-pill.active { background: var(--danger-bg); border-color: var(--danger); color: var(--danger); }
.btn-refresh {
  width: 44px; height: 44px; border-radius: 14px; background: var(--bg-primary);
  border: 1px solid var(--border-color); color: var(--text-secondary); cursor: pointer;
  display: flex; align-items: center; justify-content: center;
}

/* Table */
.table-outer-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 24px; overflow: hidden; }
.table-scroll-wrap { overflow-x: auto; }
.premium-table { width: 100%; border-collapse: collapse; text-align: left; }
.th-inner {
  padding: 16px 20px; font-size: 11px; font-weight: 600; color: var(--text-muted);
  text-transform: uppercase; letter-spacing: 0.8px; display: flex; align-items: center; gap: 6px;
}
.sortable { cursor: pointer; }
.sortable:hover .th-inner { color: var(--accent); }
.table-row { border-bottom: 1px solid var(--border-color); transition: 0.2s; animation: slideUp 0.4s ease both; }
.table-row:hover { background: var(--bg-primary); }
.premium-table td { padding: 12px 20px; vertical-align: middle; }

.item-info-wrap { display: flex; align-items: center; gap: 12px; }
.item-icon-box {
  width: 36px; height: 36px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.item-icon-box.transfer { background: var(--success-bg); color: var(--success); }
.item-icon-box.manual { background: var(--accent-light); color: var(--accent); }
.i-name { display: block; font-size: 14px; font-weight: 700; color: var(--text-primary); margin-bottom: 1px; }
.i-unit { display: block; font-size: 10px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; }

.cat-badge { display: inline-block; padding: 4px 10px; background: var(--bg-primary); border-radius: 8px; font-size: 11px; font-weight: 700; color: var(--text-secondary); border: 1px solid var(--border-color); }
.cat-badge.muted { opacity: 0.4; }

.stock-val-wrap { display: flex; flex-direction: column; align-items: flex-end; gap: 2px; }
.stock-val-wrap .s-main { font-size: 16px; font-weight: 700; color: var(--text-primary); }
.stock-val-wrap .s-unit { font-size: 10px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-left: 3px; }
.stock-val-wrap .s-main-row { display: flex; align-items: baseline; }
.stock-val-wrap .s-secondary { 
  font-size: 11px; font-weight: 700; color: var(--accent); 
  display: flex; align-items: center; gap: 4px;
  background: var(--accent-light); padding: 2px 8px; border-radius: 6px;
  margin-top: 2px;
}
.stock-val-wrap.low .s-main { color: var(--danger); }
.stock-val-wrap.low .s-secondary { background: var(--danger-bg); color: var(--danger); }

.price-val { font-size: 13px; font-weight: 700; color: var(--text-secondary); }
.total-val { font-size: 13px; font-weight: 600; color: var(--text-primary); }

.source-badge {
  display: inline-block; padding: 4px 10px; border-radius: 8px; font-size: 10px; font-weight: 700; text-transform: uppercase;
}
.source-badge.gudang { background: var(--success-bg); color: var(--success); }
.source-badge.manual { background: var(--accent-light); color: var(--accent); }

.conv-btn {
  display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 8px;
  background: rgba(139,92,246,0.1); color: #8b5cf6; border: 1px solid rgba(139,92,246,0.2);
  font-size: 11px; font-weight: 700; cursor: pointer; transition: 0.2s;
}
.conv-btn:hover { background: #8b5cf6; color: #fff; border-color: #8b5cf6; }

.action-btn-group { display: flex; justify-content: center; gap: 5px; }
.btn-act {
  width: 30px; height: 30px; border-radius: 8px; border: 1px solid var(--border-color);
  background: var(--bg-primary); cursor: pointer; transition: 0.2s;
  display: flex; align-items: center; justify-content: center;
}
.btn-act.small { width: 26px; height: 26px; border-radius: 6px; }
.btn-act.consume { color: #ef4444; }
.btn-act.consume:hover { background: #ef4444; color: #fff; border-color: #ef4444; }
.btn-act.return { color: #3b82f6; }
.btn-act.return:hover { background: #3b82f6; color: #fff; border-color: #3b82f6; }
.btn-act.edit:hover { background: var(--bg-card-hover); color: var(--text-primary); }
.btn-act.delete { color: var(--text-muted); }
.btn-act.delete:hover { background: #ef4444; color: #fff; border-color: #ef4444; }

/* Pagination */
.pagination-area { padding: 20px 24px; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border-color); }
.pagin-info { font-size: 12px; color: var(--text-muted); font-weight: 600; }
.pagin-btns { display: flex; align-items: center; gap: 12px; }
.pagin-btn { width: 36px; height: 36px; border-radius: 10px; border: 1px solid var(--border-color); background: var(--bg-card); cursor: pointer; display: flex; align-items: center; justify-content: center; }
.pagin-current { font-size: 13px; font-weight: 600; color: var(--text-primary); }

/* Loading & Empty */
.table-loading, .table-empty { padding: 80px; text-align: center; }
.loading-spinner { width: 32px; height: 32px; border: 3px solid var(--accent-light); border-top-color: var(--accent); border-radius: 50%; animation: spin 0.8s linear infinite; margin: 0 auto 16px; }
.empty-illu { opacity: 0.2; margin-bottom: 16px; color: var(--text-muted); display: flex; justify-content: center; }

/* Utilities */
.text-right { text-align: right; }
.text-center { text-align: center; }
.justify-end { justify-content: flex-end; }
.justify-center { justify-content: center; }
.mt-3 { margin-top: 12px; }
.mt-4 { margin-top: 16px; }

@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
@keyframes spin { to { transform: rotate(360deg); } }

/* ── Responsive ── */
@media (max-width: 768px) {
  .page-hero { flex-direction: column; gap: 16px; padding: 20px; align-items: stretch; margin-bottom: 16px; }
  .hero-content { align-items: center; text-align: center; flex-direction: column; }
  .hero-icon-wrap { width: 48px; height: 48px; border-radius: 14px; margin-bottom: 8px; }
  .hero-icon-wrap svg { width: 24px; height: 24px; }
  .hero-title { font-size: 20px; }
  .hero-subtitle { display: block; font-size: 12px; opacity: 0.8; margin-top: 4px; }
  .hero-actions { display: grid; grid-template-columns: 1fr; gap: 10px; width: 100%; margin-top: 8px; }
  .hero-actions .btn-primary, .hero-actions .btn-secondary { width: 100%; justify-content: center; height: 48px; display: flex; align-items: center; gap: 10px; border-radius: 14px; font-weight: 700; cursor: pointer; }
  .hero-actions .btn-primary { background: #f97316; color: #fff; border: none; }
  .hero-actions .btn-secondary { background: var(--bg-primary); color: var(--text-primary); border: 1.5px solid var(--border-color); }

  .filter-glass-bar { flex-direction: column; align-items: stretch; gap: 10px; padding: 12px; border-radius: 16px; margin-bottom: 12px; }
  .filter-select { height: 40px; font-size: 12px; }
  .toggle-pill { height: 40px; font-size: 11px; justify-content: center; }
  .btn-refresh { width: 40px; height: 40px; }

  .table-outer-card { border-radius: 16px; }
  .th-inner { padding: 12px 14px; font-size: 10px; }
  .premium-table td { padding: 10px 14px; }
  .i-name { font-size: 13px; }
  .stock-val-wrap .s-main { font-size: 14px; }

  /* Show core columns on mobile */
  .premium-table th:nth-child(2), .premium-table td:nth-child(2) { display: flex; } /* Kategori */
  .premium-table th:nth-child(7), .premium-table td:nth-child(7) { display: flex; } /* Konversi */
  
  /* Still hide less important ones for compactness if needed, or show them as cards */
  .premium-table th:nth-child(4), .premium-table td:nth-child(4), /* Harga Beli */
  .premium-table th:nth-child(5), .premium-table td:nth-child(5), /* Total Nilai */
  .premium-table th:nth-child(6), .premium-table td:nth-child(6)  /* Sumber */
  { display: none; }

  .action-btn-group { gap: 4px; }
  .btn-act { width: 28px; height: 28px; border-radius: 6px; }

  .pagination-area { flex-direction: column; gap: 12px; padding: 16px; align-items: center; }
  .pagin-btns { width: 100%; justify-content: center; }

  .toast-bar { right: 12px; left: 12px; min-width: auto; }
}

@media (max-width: 480px) {
  .hero-title { font-size: 14px; }
  .premium-table td { padding: 8px 10px; }
  .i-name { font-size: 12px; }
  .item-icon-box { width: 28px; height: 28px; border-radius: 8px; }
}
</style>

<!-- Non-scoped styles for Teleported modals -->
<style>
/* Kitchen Modal Styles */
.modal-backdrop {
  position: fixed; inset: 0; z-index: 9999;
  background: rgba(0,0,0,0.4); backdrop-filter: blur(8px);
  display: flex; align-items: center; justify-content: center; padding: 20px;
}
.modal-panel {
  width: 100%; max-width: 560px; background: var(--bg-card); border-radius: 28px;
  border: 1px solid var(--border-color); box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); overflow: hidden;
}
.modal-panel.small { max-width: 440px; }
.modal-top { display: flex; flex-direction: column; gap: 16px; padding: 24px; border-bottom: 1px solid var(--border-color); }
.modal-header-content { display: flex; align-items: center; gap: 16px; width: 100%; }
.modal-icon-wrap {
  width: 44px; height: 44px; border-radius: 12px;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.modal-icon-wrap.green { background: rgba(34,197,94,0.1); color: #22c55e; }
.modal-icon-wrap.orange { background: rgba(249,115,22,0.1); color: #f97316; }
.modal-icon-wrap.red { background: rgba(239,68,68,0.1); color: #ef4444; }
.modal-icon-wrap.blue { background: rgba(59,130,246,0.1); color: #3b82f6; }
.modal-icon-wrap.purple { background: rgba(139,92,246,0.1); color: #8b5cf6; }

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
.modal-content { padding: 24px 28px; }

.input-group { margin-bottom: 0; }
.input-label { font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; display: block; }
.premium-input {
  width: 100%; padding: 12px 16px; border-radius: 14px; background: var(--bg-primary);
  border: 1px solid var(--border-color); color: var(--text-primary); font-size: 14px; font-weight: 600; outline: none; transition: 0.2s;
}
.premium-input:focus { border-color: var(--accent); box-shadow: 0 0 0 4px var(--accent-light); }
.premium-input.small { padding: 8px 12px; font-size: 13px; border-radius: 10px; }
.text-area { resize: none; font-family: inherit; }
.form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }

.modal-bottom { 
  display: flex; 
  gap: 12px; 
  justify-content: center;
  padding: 20px 28px; 
  border-top: 1px solid var(--border-color); 
}

.modal-bottom button {
  flex: 1;
}
.btn-cancel { 
  background: rgba(255, 255, 255, 0.03);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  color: var(--text-secondary);
  border: 1.5px solid var(--border-color);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  height: 46px;
  padding: 0 24px;
  border-radius: 14px;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  outline: none;
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
.btn-action {
  display: flex; align-items: center; justify-content: center; gap: 10px; padding: 12px 28px; border-radius: 14px;
  border: none; font-weight: 700; cursor: pointer; color: #fff; transition: 0.2s;
}
.btn-action.small { padding: 6px 12px; border-radius: 8px; font-size: 12px; gap: 4px; }
.btn-action.green { background: #22c55e; }
.btn-action.orange { background: linear-gradient(135deg, #f97316, #fb923c); }
.btn-action.red { background: #ef4444; }
.btn-action.blue { background: #3b82f6; }
.btn-action:disabled { opacity: 0.5; cursor: not-allowed; }

/* Info Card */
.info-card { background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 14px; padding: 16px; }
.info-row { display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 6px; color: var(--text-secondary); }
.info-row:last-child { margin-bottom: 0; }

/* Conversion styles */
.conv-list { display: flex; flex-direction: column; gap: 12px; }
.conv-item-premium {
  background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 16px; 
  padding: 16px; transition: 0.2s;
}
.conv-item-premium:hover { border-color: var(--accent); transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.05); }

.conv-top-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
.conv-formula-tag { font-size: 14px; font-weight: 800; color: var(--text-primary); }
.conv-actions-wrap { display: flex; gap: 6px; }

.conv-details-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.conv-detail-card {
  background: rgba(0,0,0,0.02); padding: 10px; border-radius: 10px;
  display: flex; flex-direction: column; gap: 2px;
}
.conv-detail-card.highlight { background: rgba(139,92,246,0.06); border: 1px solid rgba(139,92,246,0.1); }
.detail-label { font-size: 9px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; }
.detail-value { font-size: 13px; font-weight: 700; color: var(--text-primary); }
.conv-detail-card.highlight .detail-value { color: #8b5cf6; }

.btn-icon-tiny {
  width: 28px; height: 28px; border-radius: 8px; border: 1px solid var(--border-color);
  background: var(--bg-primary); color: var(--text-muted); cursor: pointer;
  display: flex; align-items: center; justify-content: center; transition: 0.2s;
}
.btn-icon-tiny:hover.edit { background: var(--accent-light); color: var(--accent); border-color: var(--accent); }
.btn-icon-tiny:hover.delete { background: #fee2e2; color: #ef4444; border-color: #fca5a5; }

/* Preview Calc Card */
.preview-calc-card {
  background: linear-gradient(135deg, rgba(34,197,94,0.05) 0%, rgba(34,197,94,0.02) 100%);
  border: 1.5px dashed #22c55e; border-radius: 16px; padding: 16px;
}
.preview-header { 
  display: flex; align-items: center; gap: 8px; font-size: 11px; font-weight: 800; 
  color: #166534; text-transform: uppercase; margin-bottom: 10px;
}
.preview-body { display: flex; flex-direction: column; gap: 8px; }
.p-item { display: flex; justify-content: space-between; font-size: 13px; color: var(--text-secondary); font-weight: 500; }
.p-item strong { color: var(--text-primary); font-weight: 700; }

.empty-conv { 
  text-align: center; padding: 24px; background: var(--bg-primary); 
  border-radius: 12px; border: 1px dashed var(--border-color);
  color: var(--text-muted); font-size: 13px; font-style: italic; 
}

.conv-edit-row {
  display: flex; align-items: center; gap: 8px; padding: 12px; background: rgba(139,92,246,0.06);
  border: 1px dashed rgba(139,92,246,0.3); border-radius: 12px; font-size: 13px; font-weight: 600; color: var(--text-primary);
}

.conv-add-section { border-top: 1px solid var(--border-color); }
.section-title-wrap { display: flex; align-items: center; gap: 8px; }
.input-label-premium { font-size: 11px; font-weight: 800; color: var(--text-secondary); letter-spacing: 1px; }

.disabled-pill {
  width: 100%; padding: 12px 16px; border-radius: 14px; background: rgba(0,0,0,0.03);
  border: 1px solid var(--border-color); color: var(--text-muted); font-size: 14px; font-weight: 700;
  cursor: not-allowed;
}

.ratio-input-wrapper { display: flex; align-items: center; position: relative; }
.ratio-tag {
  position: absolute; left: 16px; font-size: 13px; font-weight: 700; color: var(--text-muted);
  pointer-events: none;
}
.ratio-input-wrapper .premium-input { padding-left: 70px; padding-right: 60px; }
.ratio-unit-tag {
  position: absolute; right: 16px; font-size: 13px; font-weight: 800; color: var(--accent);
  background: var(--accent-light); padding: 4px 10px; border-radius: 8px;
}

.input-hint { font-size: 12px; color: var(--text-muted); margin-top: 8px; font-weight: 500; line-height: 1.4; }
.text-red { color: #ef4444; }
.text-orange { color: #f97316; }
.w-100 { width: 100%; }
.flex { display: flex; }
.justify-center { justify-content: center; }
.flex-btn-group { display: flex; gap: 12px; align-items: center; }

.result-preview {
  background: rgba(239,68,68,0.06); border: 1px solid rgba(239,68,68,0.2);
  border-radius: 12px; padding: 14px 16px; font-size: 14px; color: var(--text-primary);
}

/* Modal animations */
.modal-enter-active, .modal-leave-active { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
.modal-enter-from, .modal-leave-to { opacity: 0; }
.modal-enter-from .modal-panel, .modal-leave-to .modal-panel { transform: scale(0.95) translateY(20px); }
.modal-enter-from .premium-modal-panel, .modal-leave-to .premium-modal-panel { transform: scale(0.95) translateY(20px); }

.premium-modal-panel { overflow: hidden; border-radius: 32px !important; }

/* Premium Modal Additions */
.gradient-orange {
  background: linear-gradient(135deg, #f97316 0%, #ef4444 100%);
  padding: 24px 28px; display: flex; justify-content: space-between; align-items: center;
}
.header-content { display: flex; align-items: center; gap: 16px; }
.header-icon-circle {
  width: 48px; height: 48px; border-radius: 14px; background: rgba(255,255,255,0.2);
  display: flex; align-items: center; justify-content: center; color: #fff;
}
.modal-close-white {
  width: 32px; height: 32px; border-radius: 8px; border: none;
  background: rgba(255,255,255,0.1); color: #fff; cursor: pointer;
  display: flex; align-items: center; justify-content: center; transition: 0.2s;
}
.modal-close-white:hover { background: rgba(255,255,255,0.2); }

.info-card-topup {
  background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 20px; padding: 20px;
}
.item-visual-info { display: flex; align-items: center; gap: 16px; }
.item-icon-box {
  width: 44px; height: 44px; border-radius: 12px; background: #fff7ed; color: #f97316;
  display: flex; align-items: center; justify-content: center; border: 1px solid #ffedd5;
}
.t-name { font-size: 16px; font-weight: 700; color: #1e293b; }
.t-stock-details { margin-top: 6px; }
.t-stock-row { font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
.text-orange-bold { color: #f97316; }
.text-blue-bold { color: #3b82f6; }
.badge-source { font-size: 9px; font-weight: 800; padding: 3px 10px; border-radius: 6px; text-transform: uppercase; }
.badge-source.manual { background: #dbeafe; color: #2563eb; }
.badge-source.gudang { background: #dcfce7; color: #166534; }

.mini-unit-select {
  padding: 4px 8px; border-radius: 8px; border: 1.5px solid #e2e8f0;
  background: #f8fafc; font-size: 11px; font-weight: 800; color: #64748b;
  text-transform: uppercase; cursor: pointer; outline: none; transition: 0.2s;
}
.mini-unit-select:hover { border-color: #f97316; }
.no-margin { margin-bottom: 0 !important; }
.items-center { align-items: center; }

.input-label-premium {
  font-size: 11px; font-weight: 800; color: #94a3b8; text-transform: uppercase; 
  letter-spacing: 0.5px; margin-bottom: 8px; display: block;
}
.premium-input-box {
  width: 100%; padding: 14px 18px; border-radius: 16px; border: 1.5px solid #e2e8f0;
  background: #fff; font-size: 15px; font-weight: 600; color: #1e293b; transition: 0.2s;
}
.premium-input-box:focus { border-color: #f97316; outline: none; box-shadow: 0 0 0 4px rgba(249,115,22,0.1); }

.unit-input-wrapper, .currency-input-wrapper { position: relative; display: flex; align-items: center; }
.unit-text-tag {
  position: absolute; right: 18px; font-size: 13px; font-weight: 700; color: #94a3b8;
}
.currency-prefix {
  position: absolute; left: 18px; font-size: 14px; font-weight: 700; color: #94a3b8;
}
.pl-10 { padding-left: 45px !important; }

.modal-bottom-premium {
  padding: 20px 28px; border-top: 1px solid #f1f5f9; display: flex; gap: 12px;
}
.btn-cancel-premium {
  flex: 1; padding: 14px; border-radius: 14px; border: 1.5px solid #e2e8f0;
  background: #fff; color: #64748b; font-weight: 700; cursor: pointer; transition: 0.2s;
}
.btn-cancel-premium:hover { background: #f8fafc; border-color: #cbd5e1; }
.btn-save-premium {
  flex: 2; padding: 14px; border-radius: 14px; border: none;
  background: linear-gradient(135deg, #f97316 0%, #ef4444 100%);
  color: #fff; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: 0.3s;
}
.btn-save-premium:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(239,68,68,0.3); }
.btn-save-premium:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }

.text-area-fix { resize: none; line-height: 1.5; }
.opacity-80 { opacity: 0.8; }
.btn-act.plus { color: #f97316; }
.btn-act.plus:hover { background: #f97316; color: #fff; border-color: #f97316; }

.modal-toggle-wrap { display: flex; justify-content: center; }
.segmented-control-header {
  display: flex; background: var(--bg-primary); padding: 4px; 
  border-radius: 12px; border: 1px solid var(--border-color);
  margin: 0 auto;
  width: fit-content;
}

.segmented-control-header .seg-item {
  padding: 6px 16px; border-radius: 8px; font-size: 11px; font-weight: 800; 
  color: var(--text-muted); cursor: pointer; transition: 0.2s;
  text-transform: uppercase;
}
.segmented-control-header .seg-item.active {
  background: #fff; color: var(--accent);
  box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}

/* Modal Mobile Responsive */
@media (max-width: 1200px) {
  .hero-subtitle { display: none; }
  .filter-glass-bar { flex-direction: row; flex-wrap: nowrap; gap: 12px; }
  .search-box { min-width: auto; flex: 1; }
  .filter-group { flex-shrink: 0; }
  
  .premium-table th:nth-child(5), .premium-table td:nth-child(5) { display: none; } /* Total Nilai */
}

@media (max-width: 1024px) {
  .premium-table th:nth-child(2), .premium-table td:nth-child(2), /* Kategori */
  .premium-table th:nth-child(4), .premium-table td:nth-child(4)  /* Harga Beli */
  {
    display: none;
  }
  .th-inner, .premium-table td { padding: 12px 14px; }
}

@media (max-width: 768px) {
  .kitchen-container { padding: 4px; overflow-x: hidden; }
  .page-hero { padding: 16px; flex-direction: row; align-items: center; justify-content: flex-start; gap: 12px; margin-bottom: 12px; }
  .hero-icon-wrap { width: 36px; height: 36px; border-radius: 10px; }
  .hero-title { font-size: 16px; }
  .hero-actions { display: none; }

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
  .filter-select { height: 40px; font-size: 12px; flex: 1; border-radius: 12px; }
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
  .source-badge,
  .conv-btn,
  .action-btn-group {
    margin-left: auto;
  }

  .pagination-area { flex-direction: column; gap: 12px; padding: 16px; align-items: center; }
  .pagin-btns { width: 100%; justify-content: center; }

  .modal-panel { max-width: 100% !important; border-radius: 24px 24px 0 0; position: fixed; bottom: 0; left: 0; right: 0; width: 100%; animation: slideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1); }
  .modal-backdrop { align-items: flex-end; padding: 0; background: rgba(0,0,0,0.35); backdrop-filter: blur(2px); }
  .modal-top { 
    padding: 24px; 
    flex-direction: column;
    gap: 16px;
  }
  .btn-back-header { width: 36px; height: 36px; }
  .segmented-control-header { margin: 8px auto 0; width: fit-content; }

  .modal-content { padding: 20px 24px; max-height: 60vh; overflow-y: auto; }
  /* Modal is tucked under footer, so we use padding to push content above it */
  .modal-bottom { padding: 16px 24px 24px; }
  .form-grid-2 { grid-template-columns: 1fr; gap: 16px; }
  .modal-bottom-premium { padding: 16px 24px 24px; }
  .modal-panel-premium { border-radius: 24px 24px 0 0 !important; }
  
  /* Transition fix for mobile bottom sheet */
  .modal-enter-from .modal-panel, .modal-leave-to .modal-panel { transform: translateY(100%); opacity: 1; }
  .modal-enter-to .modal-panel { transform: translateY(0); }
}

@media (max-width: 480px) {
  .hero-title { font-size: 14px; }
  .i-name { font-size: 12px; }
  .pagin-info { display: none; }
}

@keyframes slideUp {
  from { transform: translateY(100%); }
  to { transform: translateY(0); }
}
</style>
