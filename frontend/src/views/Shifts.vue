<template>
  <div class="shifts-container">
    <!-- Hero Header -->
    <div class="shifts-hero">
      <div class="hero-left">
        <div class="hero-icon-box">
          <Clock :size="24" />
        </div>
        <div class="hero-text">
          <h1 class="hero-title">Manajemen Shift</h1>
          <p class="hero-subtitle">Kelola jam operasional dan rekonsiliasi kas kasir secara real-time.</p>
        </div>
      </div>
      <div class="hero-actions">
        <button v-if="!activeShift" class="btn-open-shift" @click="showOpenModal = true">
          <Play :size="18" />
          <span>MULAI SHIFT BARU</span>
        </button>
      </div>
    </div>

    <!-- Active Status Card (If any) -->
    <div v-if="activeShift" class="active-shift-summary ripple">
      <div class="summary-left">
        <div class="pulse-status">
          <div class="pulse-dot"></div>
          <span>Shift Aktif</span>
        </div>
        <div class="summary-info">
          <h3>{{ activeShift.user_name || auth.user.full_name }}</h3>
          <p>Dibuka sejak {{ formatDateTime(activeShift.opened_at) }}</p>
        </div>
      </div>
      <div class="summary-stats">
        <div class="s-stat">
          <span class="s-lab">Modal Awal</span>
          <span class="s-val">{{ formatCurrency(activeShift.initial_cash) }}</span>
        </div>
        <div class="s-divider"></div>
        <div class="s-stat">
          <span class="s-lab">Total Penjualan</span>
          <span class="s-val">{{ formatCurrency(activeShift.total_sales || 0) }}</span>
        </div>
      </div>
      <button class="btn-close-active" @click="openCloseModal(activeShift)">
        <Square :size="18" />
        <span>TUTUP SHIFT</span>
      </button>
    </div>

    <!-- Filter Bar -->
    <div class="shifts-filter-bar">
      <div class="search-input-wrap">
        <Search :size="18" class="search-icon" />
        <input v-model="searchQuery" placeholder="Cari riwayat shift..." type="text">
      </div>
      <div class="filter-actions">
        <button class="btn-refresh" @click="fetchShifts" :disabled="loading">
          <RefreshCw :size="18" :class="{ spinning: loading }" />
        </button>
      </div>
    </div>

    <!-- Shift History Table (Desktop) -->
    <div class="shifts-table-card card-premium desktop-only">
      <div class="table-responsive custom-scrollbar">
        <table class="premium-table">
          <thead>
            <tr>
              <th>Operator</th>
              <th>Dibuka</th>
              <th>Ditutup</th>
              <th class="text-right">Modal Awal</th>
              <th class="text-right">Total Transaksi</th>
              <th class="text-right">Selisih (Variance)</th>
              <th class="text-center">Status</th>
              <th class="text-center">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading && !shifts.length">
              <td colspan="8" class="text-center py-8">
                <RefreshCw :size="24" class="spinning opacity-20" />
                <p class="mt-2 text-muted">Memproses data...</p>
              </td>
            </tr>
            <tr v-else-if="!filteredShifts.length">
              <td colspan="8" class="text-center py-12">
                <div class="empty-illu">
                  <CalendarX :size="48" style="opacity: 0.1;" />
                </div>
                <p class="mt-4 text-muted">Belum ada riwayat shift yang sesuai.</p>
              </td>
            </tr>
            <tr v-for="shift in filteredShifts" :key="shift.id" :class="{ 'row-active': !shift.closed_at }">
              <td>
                <div class="user-cell">
                  <div class="user-avatar-small">{{ shift.user_name?.charAt(0) || 'U' }}</div>
                  <span class="user-name">{{ shift.user_name }}</span>
                </div>
              </td>
              <td>
                <div class="date-cell">
                  <span class="d-date">{{ formatDateShort(shift.opened_at) }}</span>
                  <span class="d-time">{{ formatTimeOnly(shift.opened_at) }}</span>
                </div>
              </td>
              <td>
                <div v-if="shift.closed_at" class="date-cell">
                  <span class="d-date">{{ formatDateShort(shift.closed_at) }}</span>
                  <span class="d-time">{{ formatTimeOnly(shift.closed_at) }}</span>
                </div>
                <span v-else class="text-muted italic">Masih Terbuka</span>
              </td>
              <td class="text-right font-bold">{{ formatCurrency(shift.initial_cash) }}</td>
              <td class="text-right">
                <div class="sales-cell">
                  <span class="s-total">{{ formatCurrency(shift.total_sales || 0) }}</span>
                  <span class="s-count">{{ shift.total_transactions || 0 }} Transaksi</span>
                </div>
              </td>
              <td class="text-right">
                <div v-if="shift.closed_at" :class="getVarianceClass(shift.variance)">
                  {{ formatCurrency(shift.variance) }}
                </div>
                <span v-else>-</span>
              </td>
              <td class="text-center">
                <span :class="['badge-pill', shift.closed_at ? 'success' : 'warning']">
                  {{ shift.closed_at ? 'Selesai' : 'Aktif' }}
                </span>
              </td>
              <td class="text-center">
                <div class="table-actions">
                  <button v-if="!shift.closed_at" class="btn-action close" @click="openCloseModal(shift)" title="Tutup Shift">
                    <Square :size="16" />
                  </button>
                  <button class="btn-action view" @click="viewDetail(shift)" title="Lihat Detail">
                    <Eye :size="16" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Shift History List (Mobile) -->
    <div class="shifts-mobile-list mobile-only">
      <div v-if="loading && !shifts.length" class="text-center py-12">
        <RefreshCw :size="32" class="spinning opacity-20" />
        <p class="mt-4 text-muted">Memuat data...</p>
      </div>
      <div v-else-if="!filteredShifts.length" class="text-center py-12">
        <CalendarX :size="48" style="opacity: 0.1;" />
        <p class="mt-4 text-muted">Belum ada riwayat shift.</p>
      </div>
      <div 
        v-for="shift in filteredShifts" 
        :key="shift.id" 
        class="shift-mobile-card ripple" 
        :class="{ 'card-active': !shift.closed_at }"
        @click="viewDetail(shift)"
      >
        <div class="smc-header">
          <div class="user-cell">
            <div class="user-avatar-small">{{ shift.user_name?.charAt(0) || 'U' }}</div>
            <span class="user-name">{{ shift.user_name }}</span>
          </div>
          <span :class="['badge-pill-small', shift.closed_at ? 'success-alt' : 'warning-alt']">
            {{ shift.closed_at ? 'Selesai' : 'Aktif' }}
          </span>
        </div>
        <div class="smc-body">
          <div class="smc-info-grid">
            <div class="smc-item">
              <span class="smc-label">Waktu</span>
              <span class="smc-value">{{ formatDateShort(shift.opened_at) }} • {{ formatTimeOnly(shift.opened_at) }}</span>
            </div>
            <div class="smc-item">
              <span class="smc-label">Penjualan</span>
              <span class="smc-value font-bold text-accent">{{ formatCurrency(shift.total_sales || 0) }}</span>
            </div>
            <div class="smc-item" v-if="shift.closed_at">
              <span class="smc-label">Selisih</span>
              <span :class="['smc-value', getVarianceClass(shift.variance)]">{{ formatCurrency(shift.variance) }}</span>
            </div>
          </div>
        </div>
        <div class="smc-footer" v-if="!shift.closed_at">
          <button class="btn-close-sm" @click.stop="openCloseModal(shift)">
            <Square :size="14" />
            <span>TUTUP SHIFT</span>
          </button>
        </div>
        <div class="card-tap-hint">
          <Eye :size="12" />
          <span>Tap untuk detail</span>
        </div>
      </div>
    </div>

    <!-- Modals -->
    <Teleport to="body">
      <!-- Open Shift Modal -->
      <Transition name="modal">
        <div v-if="showOpenModal" class="modal-backdrop" @click.self="showOpenModal = false">
          <div class="modal-panel-premium small">
            <div class="modal-premium-header">
              <button class="m-close" @click="showOpenModal = false"><ArrowLeft :size="20" /></button>
              <div class="m-title-icon orange">
                <Play :size="20" />
              </div>
              <div class="m-title-group">
                <h3>Buka Shift Baru</h3>
                <p>Masukkan modal awal (uang laci) untuk memulai operasional POS.</p>
              </div>
            </div>
            
            <div class="modal-premium-body">
              <div class="alert-premium info mb-6">
                <Info :size="18" />
                <p>Membuka shift diperlukan agar Anda dapat mencatat transaksi di halaman Kasir (POS).</p>
              </div>

              <div class="input-modern">
                <label>Modal Awal / Uang Laci (Cash)</label>
                <div class="input-with-prefix">
                  <span class="prefix">Rp</span>
                  <input 
                    type="number" 
                    v-model="openForm.initial_cash" 
                    placeholder="0"
                    @keyup.enter="handleOpenShift"
                    ref="initialCashInput"
                  >
                </div>
                <p class="input-hint">Ketik jumlah uang tunai fisik yang ada di laci saat ini.</p>
              </div>
            </div>

            <div class="modal-premium-footer">
              <button class="btn-secondary-modern" @click="showOpenModal = false">Batal</button>
              <button class="btn-primary-modern orange" @click="handleOpenShift" :disabled="submitting">
                <RefreshCw v-if="submitting" :size="18" class="spinning" />
                <span v-else>Mulai Shift Sekarang</span>
              </button>
            </div>
          </div>
        </div>
      </Transition>

      <!-- Close Shift Modal -->
      <Transition name="modal">
        <div v-if="showCloseModal" class="modal-backdrop" @click.self="showCloseModal = false">
          <div class="modal-panel-premium medium">
            <div class="modal-premium-header">
              <button class="m-close" @click="showCloseModal = false"><ArrowLeft :size="20" /></button>
              <div class="m-title-icon red">
                <Square :size="20" />
              </div>
              <div class="m-title-group">
                <h3>Tutup & Rekonsiliasi Shift</h3>
                <p>Hitung total uang fisik di laci untuk menyelesaikan shift ini.</p>
              </div>
            </div>

            <div class="modal-premium-body">
              <div class="close-shift-grid">
                <div class="close-info-panel">
                  <div class="info-row">
                    <span>Modal Awal</span>
                    <span>{{ formatCurrency(selectedShift?.initial_cash || 0) }}</span>
                  </div>
                  <div class="info-row">
                    <span>Penjualan Tunai</span>
                    <span class="text-success">{{ formatCurrency(selectedShift?.total_sales || 0) }}</span>
                  </div>
                  <div class="info-row total">
                    <span>Ekspektasi Kas</span>
                    <span>{{ formatCurrency(Number(selectedShift?.initial_cash || 0) + Number(selectedShift?.total_sales || 0)) }}</span>
                  </div>
                </div>

                <div class="close-form-panel">
                  <div class="input-modern">
                    <label>Jumlah Uang Tunai Aktual (Fisik)</label>
                    <div class="input-with-prefix">
                      <span class="prefix">Rp</span>
                      <input type="number" v-model="closeForm.actual_cash" placeholder="0">
                    </div>
                  </div>
                  <div class="input-modern">
                    <label>Catatan (Opsional)</label>
                    <textarea v-model="closeForm.notes" placeholder="Tulis catatan jika ada selisih..." rows="3"></textarea>
                  </div>
                  
                  <div class="variance-preview" :class="variancePreview >= 0 ? 'ok' : 'warning'">
                    <span class="v-label">Potensi Selisih (Variance)</span>
                    <span class="v-val">{{ formatCurrency(variancePreview) }}</span>
                  </div>
                </div>
              </div>
            </div>

            <div class="modal-premium-footer">
              <button class="btn-secondary-modern" @click="showCloseModal = false">Batal</button>
              <button class="btn-primary-modern red" @click="handleCloseShift" :disabled="submitting">
                <RefreshCw v-if="submitting" :size="18" class="spinning" />
                <span v-else>Konfirmasi & Tutup Shift</span>
              </button>
            </div>
          </div>
        </div>
      </Transition>

      <!-- Detail Shift Modal -->
      <Transition name="modal">
        <div v-if="showDetailModal && selectedShift" class="modal-backdrop" @click.self="showDetailModal = false">
          <div class="modal-panel-premium large">
            <div class="modal-premium-header">
              <button class="m-close" @click="showDetailModal = false"><ArrowLeft :size="20" /></button>
              <div class="m-title-icon blue">
                <History :size="20" />
              </div>
              <div class="m-title-group">
                <h3>Detail Shift</h3>
                <p>Informasi lengkap shift dan transaksi yang terjadi</p>
              </div>
            </div>
            
            <div class="modal-premium-body">
              <!-- Shift Info Grid -->
              <div class="detail-grid">
                <div class="detail-card">
                  <div class="dc-header">
                    <div class="dc-icon orange">
                      <Clock :size="18" />
                    </div>
                    <h4>Informasi Shift</h4>
                  </div>
                  <div class="dc-body">
                    <div class="info-row">
                      <span class="ir-label">Operator</span>
                      <span class="ir-value">{{ selectedShift.user_name }}</span>
                    </div>
                    <div class="info-row">
                      <span class="ir-label">Dibuka</span>
                      <span class="ir-value">{{ formatDateTime(selectedShift.opened_at) }}</span>
                    </div>
                    <div class="info-row">
                      <span class="ir-label">Ditutup</span>
                      <span class="ir-value">{{ selectedShift.closed_at ? formatDateTime(selectedShift.closed_at) : 'Masih Aktif' }}</span>
                    </div>
                    <div class="info-row">
                      <span class="ir-label">Durasi</span>
                      <span class="ir-value">{{ calculateDuration(selectedShift.opened_at, selectedShift.closed_at) }}</span>
                    </div>
                  </div>
                </div>

                <div class="detail-card">
                  <div class="dc-header">
                    <div class="dc-icon green">
                      <Banknote :size="18" />
                    </div>
                    <h4>Ringkasan Keuangan</h4>
                  </div>
                  <div class="dc-body">
                    <div class="info-row">
                      <span class="ir-label">Modal Awal</span>
                      <span class="ir-value">{{ formatCurrency(selectedShift.initial_cash) }}</span>
                    </div>
                    <div class="info-row">
                      <span class="ir-label">Total Penjualan</span>
                      <span class="ir-value">{{ formatCurrency(selectedShift.total_sales || 0) }}</span>
                    </div>
                    <div class="info-row">
                      <span class="ir-label">Total Transaksi</span>
                      <span class="ir-value">{{ selectedShift.total_transactions || 0 }} Transaksi</span>
                    </div>
                    <div v-if="selectedShift.closed_at" class="info-row highlight">
                      <span class="ir-label">Kas Akhir (Aktual)</span>
                      <span class="ir-value">{{ formatCurrency(selectedShift.actual_cash || 0) }}</span>
                    </div>
                    <div v-if="selectedShift.closed_at" class="info-row" :class="selectedShift.variance >= 0 ? 'success' : 'danger'">
                      <span class="ir-label">Selisih (Variance)</span>
                      <span class="ir-value">{{ formatCurrency(selectedShift.variance || 0) }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Transactions List -->
              <div class="detail-transactions">
                <div class="dt-header">
                  <h4>Daftar Transaksi</h4>
                  <span class="badge-count">{{ shiftTransactions.length }}</span>
                </div>
                <div v-if="shiftTransactions.length === 0" class="dt-empty">
                  <CalendarX :size="32" style="opacity: 0.2;" />
                  <p>Belum ada transaksi pada shift ini</p>
                </div>
                <div v-else class="dt-list custom-scrollbar">
                  <div v-for="trx in shiftTransactions" :key="trx.id" class="dt-item">
                    <div class="dt-left">
                      <div class="dt-invoice">{{ trx.invoice_number }}</div>
                      <div class="dt-time">{{ formatTimeOnly(trx.created_at) }}</div>
                    </div>
                    <div class="dt-center">
                      <div class="dt-payment">{{ trx.payment_method.toUpperCase() }}</div>
                      <div class="dt-type">{{ trx.order_type === 'dine_in' ? 'Dine In' : 'Takeaway' }}</div>
                    </div>
                    <div class="dt-right">
                      <div class="dt-amount">{{ formatCurrency(trx.total_amount) }}</div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Notes if any -->
              <div v-if="selectedShift.notes" class="detail-notes">
                <div class="dn-header">
                  <AlertCircle :size="16" />
                  <span>Catatan</span>
                </div>
                <p>{{ selectedShift.notes }}</p>
              </div>
            </div>

            <div class="modal-premium-footer">
              <button class="btn-secondary-modern" @click="showDetailModal = false">Tutup</button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, inject, watch, onUnmounted } from 'vue';
import { useAuthStore } from '../stores/auth';
import api from '../api';
import { showConfirm, showSuccess, showError, showInfo } from '../utils/swal';
import { 
  Clock, Play, Square, Search, RefreshCw, Eye, X, Info, 
  CalendarX, AlertCircle, Banknote, History, ArrowLeft 
} from 'lucide-vue-next';

const auth = useAuthStore();
const theme = inject('theme');

// State
const shifts = ref([]);
const activeShift = ref(null);
const loading = ref(false);
const submitting = ref(false);
const searchQuery = ref('');
const showOpenModal = ref(false);
const showCloseModal = ref(false);
const showDetailModal = ref(false);
const selectedShift = ref(null);
const shiftTransactions = ref([]);

const openForm = reactive({ initial_cash: 0 });
const closeForm = reactive({ actual_cash: 0, notes: '' });

// Watch modals to toggle mobile nav and body scroll
watch([showOpenModal, showCloseModal, showDetailModal], ([val1, val2, val3]) => {
  if (val1 || val2 || val3) {
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

// Computed
const filteredShifts = computed(() => {
  if (!searchQuery.value) return shifts.value;
  const q = searchQuery.value.toLowerCase();
  return shifts.value.filter(s => 
    s.user_name?.toLowerCase().includes(q) || 
    s.status?.toLowerCase().includes(q)
  );
});

const variancePreview = computed(() => {
  if (!selectedShift.value) return 0;
  const expected = Number(selectedShift.value.initial_cash) + Number(selectedShift.value.total_sales || 0);
  return Number(closeForm.actual_cash) - expected;
});

// Methods
const fetchShifts = async () => {
  loading.value = true;
  try {
    const [resAll, resActive] = await Promise.all([
      api.get('/shifts'),
      api.get('/shifts/active')
    ]);
    if (resAll.data.success) shifts.value = resAll.data.data;
    if (resActive.data.success) activeShift.value = resActive.data.data;
  } catch (err) {
    console.error('Failed to fetch shifts', err);
  } finally {
    loading.value = false;
  }
};

const handleOpenShift = async () => {
  submitting.value = true;
  try {
    const res = await api.post('/shifts', openForm);
    if (res.data.success) {
      showOpenModal.value = false;
      openForm.initial_cash = 0;
      await fetchShifts();
      showSuccess('Shift berhasil dibuka');
    }
  } catch (err) {
    showError(err.response?.data?.message || 'Gagal membuka shift');
  } finally {
    submitting.value = false;
  }
};

const openCloseModal = (shift) => {
  selectedShift.value = shift;
  closeForm.actual_cash = Number(shift.initial_cash) + Number(shift.total_sales || 0);
  closeForm.notes = '';
  showCloseModal.value = true;
};

const handleCloseShift = async () => {
  const result = await showConfirm({
    title: 'Tutup Shift',
    text: 'Anda yakin ingin menutup shift ini?',
    icon: 'warning',
    confirmText: 'Ya, Tutup',
    cancelText: 'Batal'
  });
  
  if (!result.isConfirmed) return;
  
  submitting.value = true;
  try {
    const res = await api.post(`/shifts/${selectedShift.value.id}/close`, closeForm);
    if (res.data.success) {
      showCloseModal.value = false;
      await fetchShifts();
      showSuccess('Shift berhasil ditutup');
    }
  } catch (err) {
    showError(err.response?.data?.message || 'Gagal menutup shift');
  } finally {
    submitting.value = false;
  }
};

const viewDetail = async (shift) => {
  selectedShift.value = shift;
  showDetailModal.value = true;
  
  // Fetch shift transactions
  try {
    const res = await api.get(`/shifts/${shift.id}/transactions`);
    if (res.data.success) {
      shiftTransactions.value = res.data.data;
    }
  } catch (err) {
    console.error('Failed to fetch shift transactions', err);
    shiftTransactions.value = [];
  }
};

// Formatting
const formatCurrency = (val) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);
const formatDateTime = (dateStr) => {
  if (!dateStr) return '-';
  return new Date(dateStr).toLocaleString('id-ID', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' });
};
const formatDateShort = (dateStr) => {
  if (!dateStr) return '-';
  return new Date(dateStr).toLocaleDateString('id-ID', { day: '2-digit', month: 'short' });
};
const formatTimeOnly = (dateStr) => {
  if (!dateStr) return '-';
  return new Date(dateStr).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
};
const getVarianceClass = (v) => {
  if (v > 0) return 'text-success font-bold';
  if (v < 0) return 'text-danger font-bold';
  return 'text-secondary';
};

const calculateDuration = (start, end) => {
  if (!start) return '-';
  const startDate = new Date(start);
  const endDate = end ? new Date(end) : new Date();
  const diff = endDate - startDate;
  
  const hours = Math.floor(diff / (1000 * 60 * 60));
  const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
  
  if (hours > 0) {
    return `${hours} jam ${minutes} menit`;
  }
  return `${minutes} menit`;
};

onMounted(fetchShifts);
</script>

<style scoped>
.shifts-container { animation: fadeIn 0.4s ease; padding-bottom: 40px; }

/* ── Hero ── */
.shifts-hero { display: flex; align-items: center; justify-content: space-between; margin-bottom: 32px; gap: 20px; flex-wrap: wrap; }
.hero-left { display: flex; align-items: center; gap: 20px; }
.hero-icon-box { width: 56px; height: 56px; border-radius: 18px; background: var(--accent-light); color: var(--accent); display: flex; align-items: center; justify-content: center; }
.hero-title { font-size: 28px; font-weight: 700; color: var(--text-primary); margin: 0; line-height: 1.1; }
.hero-subtitle { font-size: 14px; color: var(--text-muted); font-weight: 500; margin-top: 4px; }

.btn-open-shift {
  display: flex; align-items: center; gap: 12px; background: linear-gradient(135deg, var(--accent), #fb923c);
  color: #fff; padding: 14px 28px; border-radius: 16px; border: none; font-weight: 600;
  cursor: pointer; box-shadow: 0 10px 25px -10px rgba(249, 115, 22, 0.4); transition: 0.3s;
}
.btn-open-shift:hover { transform: translateY(-3px); box-shadow: 0 15px 30px -10px rgba(249, 115, 22, 0.5); }

/* ── Active Status ── */
.active-shift-summary {
  display: flex; align-items: center; justify-content: space-between;
  background: var(--bg-card); border: 2px solid var(--success);
  border-radius: 28px; padding: 24px 32px; margin-bottom: 32px;
  position: relative; overflow: hidden;
}
.active-shift-summary::after {
  content: ''; position: absolute; inset: 0; background: linear-gradient(135deg, var(--success-bg) 0%, transparent 60%);
  z-index: 0; pointer-events: none; opacity: 0.5;
}
.summary-left { display: flex; flex-direction: column; gap: 12px; position: relative; z-index: 1; }
.pulse-status { display: flex; align-items: center; gap: 8px; font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--success); }
.pulse-dot { width: 8px; height: 8px; border-radius: 50%; background: currentColor; animation: pulse 2s infinite; }
.summary-info h3 { font-size: 22px; font-weight: 700; color: var(--text-primary); margin: 0; }
.summary-info p { font-size: 13px; color: var(--text-muted); margin-top: 2px; }

.summary-stats { display: flex; align-items: center; gap: 40px; position: relative; z-index: 1; }
.s-stat { display: flex; flex-direction: column; }
.s-lab { font-size: 11px; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
.s-val { font-size: 20px; font-weight: 700; color: var(--text-primary); }
.s-divider { width: 1px; height: 32px; background: var(--border-color); }

.btn-close-active {
  position: relative; z-index: 1; display: flex; align-items: center; gap: 10px;
  background: var(--danger-bg); color: var(--danger); border: 1.5px solid var(--danger);
  padding: 12px 24px; border-radius: 14px; font-weight: 600; cursor: pointer; transition: 0.2s;
}
.btn-close-active:hover { background: var(--danger); color: #fff; transform: translateY(-2px); }

/* ── Filter Bar ── */
.shifts-filter-bar { display: flex; gap: 16px; margin-bottom: 24px; align-items: center; }
.search-input-wrap { flex: 1; position: relative; display: flex; align-items: center; }
.search-icon { position: absolute; left: 16px; color: var(--text-muted); transition: 0.2s; z-index: 1; }
.search-input-wrap input {
  width: 100%; height: 50px; border-radius: 16px; border: 1.5px solid var(--border-color);
  background: var(--bg-card); padding-left: 52px; font-size: 14px; font-weight: 500;
  color: var(--text-primary); outline: none; transition: 0.2s;
}
.search-input-wrap input:focus { border-color: var(--accent); background: var(--bg-primary); box-shadow: 0 0 0 4px var(--accent-bg); }
.btn-refresh { width: 50px; height: 50px; border-radius: 16px; border: 1.5px solid var(--border-color); background: var(--bg-card); color: var(--text-muted); cursor: pointer; display: flex; align-items: center; justify-content: center; transition: 0.2s; }
.btn-refresh:hover { border-color: var(--accent); color: var(--accent); transform: rotate(180deg); }

.desktop-only { display: block; }
.mobile-only { display: none; }


/* ── Table Styling ── */
.shifts-table-card { padding: 8px; }
.premium-table { width: 100%; border-collapse: separate; border-spacing: 0 8px; }
.premium-table th { padding: 16px 20px; text-transform: uppercase; font-size: 11px; font-weight: 600; color: var(--text-muted); text-align: left; }
.premium-table td { padding: 18px 20px; background: var(--bg-primary); border-top: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color); transition: 0.2s; }
.premium-table td:first-child { border-left: 1px solid var(--border-color); border-top-left-radius: 16px; border-bottom-left-radius: 16px; }
.premium-table td:last-child { border-right: 1px solid var(--border-color); border-top-right-radius: 16px; border-bottom-right-radius: 16px; }

.premium-table tr:hover td { background: var(--bg-secondary); border-color: var(--accent-light); }
.row-active td { background: var(--success-bg); border-color: rgba(34, 197, 94, 0.2); }

.user-cell { display: flex; align-items: center; gap: 12px; }
.user-avatar-small { width: 32px; height: 32px; border-radius: 10px; background: var(--accent-light); color: var(--accent); display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; }
.user-name { font-weight: 700; color: var(--text-primary); font-size: 14px; }

.date-cell { display: flex; flex-direction: column; }
.d-date { font-weight: 700; color: var(--text-primary); font-size: 13px; }
.d-time { font-size: 11px; color: var(--text-muted); font-weight: 600; }

.sales-cell { display: flex; flex-direction: column; }
.s-total { font-weight: 600; color: var(--accent); }
.s-count { font-size: 11px; color: var(--text-muted); font-weight: 600; margin-top: 2px; }

.table-actions { display: flex; gap: 8px; justify-content: center; }
.btn-action { width: 34px; height: 34px; border-radius: 10px; display: flex; align-items: center; justify-content: center; border: none; cursor: pointer; transition: 0.2s; }
.btn-action.close { background: var(--danger-bg); color: var(--danger); }
.btn-action.view { background: var(--info-bg); color: var(--info); }
.btn-action:hover { transform: scale(1.15); filter: brightness(0.9); }

/* ── Modals Specific ── */
.close-shift-grid { display: grid; grid-template-columns: 1fr 1.2fr; gap: 32px; }
.close-info-panel { background: var(--bg-secondary); border-radius: 20px; padding: 24px; display: flex; flex-direction: column; gap: 16px; }
.info-row { display: flex; justify-content: space-between; align-items: center; font-size: 14px; color: var(--text-secondary); font-weight: 600; }
.info-row span:last-child { color: var(--text-primary); font-weight: 700; }
.info-row.total { margin-top: 8px; padding-top: 16px; border-top: 2px dashed var(--border-color); }
.info-row.total span:last-child { font-size: 20px; color: var(--accent); }

.variance-preview { margin-top: 24px; padding: 16px 20px; border-radius: 16px; display: flex; flex-direction: column; border: 2px solid transparent; }
.variance-preview.ok { background: var(--success-bg); border-color: var(--success); color: var(--success); }
.variance-preview.warning { background: var(--danger-bg); border-color: var(--danger); color: var(--danger); }
.v-label { font-size: 11px; font-weight: 600; text-transform: uppercase; margin-bottom: 4px; opacity: 0.8; }
.v-val { font-size: 20px; font-weight: 700; }

@keyframes pulse { 0% { transform: scale(1); opacity: 1; } 50% { transform: scale(1.4); opacity: 0.6; } 100% { transform: scale(1); opacity: 1; } }
@keyframes spin { to { transform: rotate(360deg); } }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
.spinning { animation: spin 0.8s linear infinite; }

/* Utility Classes */
.text-center { text-align: center; }
.text-right { text-align: right; }
.text-muted { color: var(--text-muted); }
.text-success { color: var(--success); }
.text-danger { color: var(--danger); }
.text-secondary { color: var(--text-secondary); }
.font-bold { font-weight: 700; }
.italic { font-style: italic; }
.py-8 { padding-top: 2rem; padding-bottom: 2rem; }
.py-12 { padding-top: 3rem; padding-bottom: 3rem; }
.mt-2 { margin-top: 0.5rem; }
.mt-4 { margin-top: 1rem; }
.mb-6 { margin-bottom: 1.5rem; }
.opacity-20 { opacity: 0.2; }

/* Badge Pills */
.badge-pill {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 6px 14px; border-radius: 100px; font-size: 11px; font-weight: 700;
  text-transform: uppercase; letter-spacing: 0.5px;
}
.badge-pill.success { background: var(--success-bg); color: var(--success); }
.badge-pill.warning { background: var(--warning-bg); color: var(--warning); }
.badge-pill.danger { background: var(--danger-bg); color: var(--danger); }

/* Card Premium */
.card-premium {
  background: var(--bg-card); border: 1px solid var(--border-color);
  border-radius: 24px; overflow: hidden;
}

/* Table Responsive */
.table-responsive {
  overflow-x: auto;
}
.custom-scrollbar::-webkit-scrollbar { height: 8px; }
.custom-scrollbar::-webkit-scrollbar-track { background: var(--bg-primary); border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: var(--border-color); border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: var(--text-muted); }

/* Empty State */
.empty-illu { display: flex; justify-content: center; color: var(--text-muted); }

/* Modal Premium Styles */
.modal-backdrop {
  position: fixed; inset: 0; z-index: 200;
  background: rgba(0,0,0,0.6); backdrop-filter: blur(6px);
  display: flex; align-items: center; justify-content: center; padding: 20px;
}

.modal-panel-premium {
  width: 100%; background: var(--bg-card);
  border-radius: 24px; border: 1px solid var(--border-color);
  box-shadow: 0 25px 50px -12px rgba(0,0,0,0.3);
  animation: modalScale 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
  overflow: hidden;
}
.modal-panel-premium.small { max-width: 480px; }
.modal-panel-premium.medium { max-width: 720px; }

@keyframes modalScale { from { transform: scale(0.95); opacity: 0; } to { transform: scale(1); opacity: 1; } }

.modal-premium-header {
  display: flex; align-items: center; gap: 16px;
  padding: 20px 28px; border-bottom: 1px solid var(--border-color);
  background: var(--bg-primary);
}

.m-title-icon {
  width: 48px; height: 48px; border-radius: 14px;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.m-title-icon.orange { background: var(--accent-light); color: var(--accent); }
.m-title-icon.red { background: var(--danger-bg); color: var(--danger); }

.m-title-group { flex: 1; }
.m-title-group h3 { font-size: 18px; font-weight: 600; color: var(--text-primary); margin: 0 0 4px 0; }
.m-title-group p { font-size: 13px; color: var(--text-muted); margin: 0; }

.m-close {
  width: 36px; height: 36px; border-radius: 10px;
  background: var(--bg-card); border: 1px solid var(--border-color);
  color: var(--text-secondary); cursor: pointer; transition: all 0.2s;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0; margin-right: 4px;
}
.m-close:hover {
  background: var(--accent-light);
  border-color: var(--accent);
  color: var(--accent);
}

.modal-premium-body {
  padding: 28px;
}

.modal-premium-footer {
  padding: 20px 28px; border-top: 1px solid var(--border-color);
  background: var(--bg-primary);
  display: flex; gap: 12px; justify-content: flex-end;
}

/* Alert Premium */
.alert-premium {
  display: flex; align-items: flex-start; gap: 12px;
  padding: 16px 20px; border-radius: 14px; font-size: 13px;
}
.alert-premium.info {
  background: rgba(59, 130, 246, 0.1);
  border: 1px solid rgba(59, 130, 246, 0.2);
  color: #3b82f6;
}
.alert-premium p { margin: 0; line-height: 1.5; }

/* Input Modern */
.input-modern {
  display: flex; flex-direction: column; gap: 8px;
}
.input-modern label {
  font-size: 12px; font-weight: 600; color: var(--text-muted);
  text-transform: uppercase; letter-spacing: 0.5px;
}
.input-modern input,
.input-modern textarea {
  width: 100%; padding: 12px 16px; border-radius: 12px;
  background: var(--bg-primary); border: 1px solid var(--border-color);
  color: var(--text-primary); font-size: 14px; font-weight: 600;
  outline: none; transition: 0.2s; font-family: inherit;
}
.input-modern input:focus,
.input-modern textarea:focus {
  border-color: var(--accent);
}
.input-modern textarea {
  resize: vertical; min-height: 80px;
}

.input-with-prefix {
  position: relative; display: flex; align-items: center;
}
.input-with-prefix .prefix {
  position: absolute; left: 16px; top: 50%; transform: translateY(-50%);
  font-size: 14px; font-weight: 600; color: var(--text-muted);
}
.input-with-prefix input {
  padding-left: 48px;
}

.input-hint {
  font-size: 11px; color: var(--text-muted); margin: 0;
}

/* Buttons Modern */
.btn-secondary-modern {
  display: inline-flex; align-items: center; justify-content: center;
  gap: 8px; height: 44px; padding: 0 20px; border-radius: 12px;
  background: var(--bg-card); border: 1px solid var(--border-color);
  color: var(--text-secondary); font-size: 13px; font-weight: 700;
  cursor: pointer; transition: all 0.2s; outline: none;
}
.btn-secondary-modern:hover {
  border-color: var(--text-muted);
  color: var(--text-primary);
}

.btn-primary-modern {
  display: inline-flex; align-items: center; justify-content: center;
  gap: 8px; height: 44px; padding: 0 24px; border-radius: 12px;
  border: none; font-size: 13px; font-weight: 700;
  cursor: pointer; transition: all 0.2s; outline: none;
  color: #fff;
}
.btn-primary-modern.orange {
  background: linear-gradient(135deg, var(--accent), #fb923c);
}
.btn-primary-modern.red {
  background: linear-gradient(135deg, var(--danger), #dc2626);
}
.btn-primary-modern:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px -8px rgba(0,0,0,0.3);
}
.btn-primary-modern:disabled {
  opacity: 0.5; cursor: not-allowed; transform: none;
}

/* Modal Transitions */
.modal-enter-active, .modal-leave-active {
  transition: opacity 0.3s ease;
}
.modal-enter-from, .modal-leave-to {
  opacity: 0;
}
.modal-enter-active .modal-panel-premium,
.modal-leave-active .modal-panel-premium {
  transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.modal-enter-from .modal-panel-premium,
.modal-leave-to .modal-panel-premium {
  transform: scale(0.9);
}

@media (max-width: 1024px) {
  .summary-stats { gap: 20px; }
  .s-val { font-size: 16px; }
}

@media (max-width: 768px) {
  .desktop-only { display: none; }
  .mobile-only { display: block; }

  .shifts-hero { gap: 12px; margin-bottom: 20px; }
  .hero-left { gap: 12px; }
  .hero-icon-box { width: 44px; height: 44px; border-radius: 12px; }
  .hero-icon-box svg { width: 20px; height: 20px; }
  .hero-title { font-size: 20px; }
  .hero-subtitle { display: none; }
  
  .btn-open-shift { padding: 10px 16px; border-radius: 12px; font-size: 13px; }
  
  .active-shift-summary { 
    flex-direction: column; 
    align-items: stretch;
    gap: 20px; 
    padding: 20px; 
    border-radius: 20px; 
    margin-bottom: 20px; 
    text-align: left; 
    border-width: 1px;
  }
  .summary-left { text-align: center; align-items: center; }
  .summary-stats { 
    display: grid; 
    grid-template-columns: 1fr 1fr; 
    gap: 12px; 
    background: var(--bg-primary);
    padding: 16px;
    border-radius: 16px;
  }
  .s-divider { display: none; }
  .s-stat { align-items: center; }
  .s-lab { font-size: 10px; }
  .s-val { font-size: 14px; }
  
  .btn-close-active { width: 100%; justify-content: center; padding: 14px; border-radius: 12px; }

  .shifts-filter-bar { margin-bottom: 16px; gap: 8px; }
  .search-input-wrap input { height: 46px; border-radius: 14px; font-size: 13px; padding-left: 44px; }
  .search-icon { left: 14px; width: 16px; }
  .btn-refresh { width: 46px; height: 46px; border-radius: 14px; }

  /* Mobile Cards */
  .shift-mobile-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 18px;
    padding: 16px;
    margin-bottom: 12px;
    position: relative;
    transition: 0.2s;
  }
  .shift-mobile-card:active { transform: scale(0.98); }
  .shift-mobile-card.card-active {
    border-color: var(--warning);
    background: var(--warning-bg);
  }
  
  .smc-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
  .badge-pill-small {
    padding: 4px 10px; border-radius: 100px; font-size: 10px; font-weight: 700; text-transform: uppercase;
  }
  .badge-pill-small.success-alt { background: var(--success); color: #fff; }
  .badge-pill-small.warning-alt { background: var(--warning); color: #fff; }

  .smc-info-grid { display: grid; grid-template-columns: 1.2fr 1fr; gap: 12px; }
  .smc-item { display: flex; flex-direction: column; gap: 2px; }
  .smc-label { font-size: 10px; color: var(--text-muted); font-weight: 600; text-transform: uppercase; }
  .smc-value { font-size: 13px; color: var(--text-primary); font-weight: 600; }
  .text-accent { color: var(--accent); }

  .smc-footer { margin-top: 16px; padding-top: 12px; border-top: 1px solid var(--border-color); }
  .btn-close-sm {
    width: 100%; display: flex; align-items: center; justify-content: center; gap: 8px;
    background: var(--danger); color: #fff; border: none; padding: 10px; border-radius: 10px;
    font-size: 11px; font-weight: 700;
  }
  
  .card-tap-hint {
    position: absolute; right: 16px; bottom: 16px; display: flex; align-items: center; gap: 4px;
    font-size: 10px; color: var(--text-muted); opacity: 0.6; pointer-events: none;
  }
  .smc-footer + .card-tap-hint { bottom: 65px; }

  .close-shift-grid { grid-template-columns: 1fr; gap: 16px; }
  
  /* Detail Modal Mobile */
  .detail-grid { grid-template-columns: 1fr; }
  .dt-item { padding: 12px; }
  .dt-invoice { font-size: 12px; }
  .dt-amount { font-size: 13px; }
}

@media (max-width: 480px) {
  .hero-title { font-size: 18px; }
  .smc-info-grid { grid-template-columns: 1fr; gap: 8px; }
  .modal-premium-header { padding: 16px 20px; }
  .modal-premium-body { padding: 20px; }
  .modal-premium-footer { padding: 16px 20px; }
}

@media (max-width: 768px) {
  .modal-backdrop {
    background: rgba(0,0,0,0.35) !important;
    backdrop-filter: blur(2px) !important;
  }
  .modal-backdrop:has(.modal-panel-premium.large) {
    padding: 0 !important;
  }
  
  .modal-panel-premium.large {
    width: 100% !important;
    height: 100% !important;
    max-width: none !important;
    border-radius: 0 !important;
    margin: 0 !important;
    display: flex;
    flex-direction: column;
    animation: modalSlideInBottom 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
  }

  .modal-panel-premium.large .modal-premium-body {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
    -webkit-overflow-scrolling: touch;
  }

  .modal-panel-premium.large .modal-premium-header {
    flex-shrink: 0;
    border-radius: 0;
  }

  .modal-panel-premium.large .modal-premium-footer {
    flex-shrink: 0;
    border-radius: 0;
    padding: 16px 20px;
  }
}

@keyframes modalSlideInBottom {
  from { transform: translateY(100%); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}



/* Detail Modal Styles */
.detail-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 16px;
  margin-bottom: 24px;
}

.detail-card {
  background: var(--bg-secondary);
  border: 1px solid var(--border-color);
  border-radius: 12px;
  overflow: hidden;
}

.dc-header {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 16px;
  border-bottom: 1px solid var(--border-color);
}

.dc-icon {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
}

.dc-icon.orange { background: linear-gradient(135deg, #f97316, #fb923c); }
.dc-icon.green { background: linear-gradient(135deg, #10b981, #34d399); }
.dc-icon.blue { background: linear-gradient(135deg, #3b82f6, #60a5fa); }

.dc-header h4 {
  font-size: 14px;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0;
}

.dc-body {
  padding: 16px;
}

.info-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 0;
  border-bottom: 1px solid var(--border-color);
}

.info-row:last-child {
  border-bottom: none;
}

.info-row.highlight {
  background: var(--accent-bg);
  margin: 0 -16px;
  padding: 10px 16px;
}

.info-row.success .ir-value {
  color: var(--success);
  font-weight: 700;
}

.info-row.danger .ir-value {
  color: var(--danger);
  font-weight: 700;
}

.ir-label {
  font-size: 13px;
  color: var(--text-muted);
}

.ir-value {
  font-size: 14px;
  font-weight: 600;
  color: var(--text-primary);
}

.detail-transactions {
  background: var(--bg-secondary);
  border: 1px solid var(--border-color);
  border-radius: 12px;
  overflow: hidden;
  margin-bottom: 16px;
}

.dt-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px;
  border-bottom: 1px solid var(--border-color);
}

.dt-header h4 {
  font-size: 14px;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0;
}

.badge-count {
  background: var(--accent);
  color: #fff;
  padding: 4px 12px;
  border-radius: 100px;
  font-size: 12px;
  font-weight: 700;
}

.dt-empty {
  padding: 48px 24px;
  text-align: center;
  color: var(--text-muted);
}

.dt-empty p {
  margin-top: 12px;
  font-size: 14px;
}

.dt-list {
  max-height: 400px;
  overflow-y: auto;
}

.dt-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 16px;
  border-bottom: 1px solid var(--border-color);
  transition: background 0.2s;
}

.dt-item:hover {
  background: var(--bg-primary);
}

.dt-item:last-child {
  border-bottom: none;
}

.dt-left {
  flex: 1;
}

.dt-invoice {
  font-size: 13px;
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: 4px;
}

.dt-time {
  font-size: 12px;
  color: var(--text-muted);
}

.dt-center {
  flex: 1;
  text-align: center;
}

.dt-payment {
  font-size: 12px;
  font-weight: 600;
  color: var(--accent);
  margin-bottom: 4px;
}

.dt-type {
  font-size: 11px;
  color: var(--text-muted);
}

.dt-right {
  flex: 1;
  text-align: right;
}

.dt-amount {
  font-size: 14px;
  font-weight: 700;
  color: var(--text-primary);
}

.detail-notes {
  background: var(--warning-bg);
  border: 1px solid var(--warning);
  border-radius: 8px;
  padding: 12px 16px;
}

.dn-header {
  display: flex;
  align-items: center;
  gap: 8px;
  color: var(--warning);
  font-size: 13px;
  font-weight: 700;
  margin-bottom: 8px;
}

.detail-notes p {
  font-size: 13px;
  color: var(--text-secondary);
  margin: 0;
  line-height: 1.6;
}

.modal-panel-premium.large {
  max-width: 900px;
  width: 90%;
}
</style>
