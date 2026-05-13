<template>
  <div class="inventory-report-container">
    <!-- Header Hero Section -->
    <div class="page-hero">
      <div class="hero-content">
        <div class="hero-icon-wrap">
          <Package :size="24" />
        </div>
        <div>
          <h1 class="hero-title">Laporan Transaksi Inventori</h1>
          <p class="hero-subtitle">Riwayat lengkap keluar masuk stok dari gudang dan dapur dengan fitur export Excel & PDF</p>
        </div>
      </div>
      <div class="hero-actions">
        <div class="export-btn-group">
          <button class="btn-export gsheet" @click="syncToGSheet" :disabled="!inventoryData?.transactions?.length || syncLoading">
            <CloudUpload :size="16" v-if="!syncLoading" />
            <RefreshCw :size="16" class="spinning" v-else />
            {{ syncLoading ? 'Syncing...' : 'GSheet' }}
          </button>
          <button class="btn-export excel" @click="exportToExcel" :disabled="!inventoryData?.transactions?.length">
            <FileSpreadsheet :size="16" /> Excel
          </button>
          <button class="btn-export pdf" @click="exportToPDF" :disabled="!inventoryData?.transactions?.length">
            <FileText :size="16" /> PDF
          </button>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="filter-glass-bar">
      <div class="filter-row">
        <div class="filter-item">
          <label class="filter-label-mini">Dari Tanggal</label>
          <input type="date" v-model="inventoryFilters.start_date" @change="fetchInventoryReport" class="filter-input-mini">
        </div>
        <div class="filter-item">
          <label class="filter-label-mini">Sampai Tanggal</label>
          <input type="date" v-model="inventoryFilters.end_date" @change="fetchInventoryReport" class="filter-input-mini">
        </div>
        <div class="filter-item">
          <label class="filter-label-mini">Lokasi</label>
          <select v-model="inventoryFilters.location" @change="fetchInventoryReport" class="filter-input-mini">
            <option value="">Semua</option>
            <option value="warehouse">Gudang</option>
            <option value="kitchen">Dapur</option>
          </select>
        </div>
        <div class="filter-item">
          <label class="filter-label-mini">Tipe</label>
          <select v-model="inventoryFilters.type" @change="fetchInventoryReport" class="filter-input-mini">
            <option value="">Semua</option>
            <option value="in">Masuk</option>
            <option value="out">Keluar</option>
            <option value="consume">Konsumsi</option>
            <option value="transfer">Transfer</option>
            <option value="return">Return</option>
          </select>
        </div>
        <button class="btn-filter-reset" @click="resetInventoryFilters" title="Reset Filter">
          <RotateCcw :size="16" />
        </button>
      </div>
    </div>

    <!-- Summary Cards -->
    <div v-if="inventoryData" class="inventory-summary-grid">
      <div class="inv-sum-card">
        <div class="inv-sum-icon in">
          <TrendingUp :size="20" />
        </div>
        <div class="inv-sum-info">
          <span class="inv-sum-label">Total Masuk</span>
          <h3 class="inv-sum-value">{{ formatQuantity(inventoryData.summary.total_in) }}</h3>
        </div>
      </div>
      <div class="inv-sum-card">
        <div class="inv-sum-icon out">
          <TrendingUp :size="20" style="transform: rotate(180deg)" />
        </div>
        <div class="inv-sum-info">
          <span class="inv-sum-label">Total Keluar</span>
          <h3 class="inv-sum-value">{{ formatQuantity(inventoryData.summary.total_out) }}</h3>
        </div>
      </div>
      <div class="inv-sum-card">
        <div class="inv-sum-icon total">
          <Activity :size="20" />
        </div>
        <div class="inv-sum-info">
          <span class="inv-sum-label">Total Transaksi</span>
          <h3 class="inv-sum-value">{{ inventoryData.summary.total_transactions }}</h3>
        </div>
      </div>
    </div>

    <!-- Transactions Table -->
    <div class="table-outer-card">
      <div v-if="inventoryLoading" class="inv-loading">
        <div class="loading-spinner"></div>
        <span>Memuat data transaksi...</span>
      </div>

      <div v-else-if="!inventoryData?.transactions?.length" class="inv-empty">
        <Package :size="48" style="opacity: 0.2" />
        <h3>Tidak Ada Transaksi</h3>
        <p>Tidak ada transaksi inventori pada periode yang dipilih</p>
      </div>

      <div v-else class="table-scroll-wrap">
        <table class="premium-table">
          <thead>
            <tr>
              <th><div class="th-inner">Tanggal & Waktu</div></th>
              <th><div class="th-inner">Lokasi</div></th>
              <th><div class="th-inner">Nama Bahan</div></th>
              <th><div class="th-inner">Tipe</div></th>
              <th class="text-right"><div class="th-inner justify-end">Jumlah</div></th>
              <th><div class="th-inner">Catatan</div></th>
              <th><div class="th-inner">Kasir</div></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(trans, idx) in inventoryData.transactions" :key="trans.id" class="table-row" :style="{ animationDelay: (idx * 0.02) + 's' }">
              <td :data-label="'Tanggal'">
                <div class="date-cell">
                  <Clock :size="14" />
                  <span>{{ new Date(trans.date).toLocaleString('id-ID', { dateStyle: 'short', timeStyle: 'short' }) }}</span>
                </div>
              </td>
              <td :data-label="'Lokasi'">
                <span class="location-badge" :class="trans.location.toLowerCase()">{{ trans.location }}</span>
              </td>
              <td :data-label="'Bahan'"><span class="item-name-text">{{ trans.item_name }}</span></td>
              <td :data-label="'Tipe'">
                <span class="type-badge" :class="trans.type">{{ trans.type_label }}</span>
              </td>
              <td class="text-right" :data-label="'Jumlah'">
                <span class="qty-text">{{ formatQuantity(trans.quantity) }} <small>{{ trans.unit }}</small></span>
              </td>
              <td :data-label="'Catatan'"><span class="notes-text">{{ trans.notes || '-' }}</span></td>
              <td :data-label="'Kasir'"><span class="user-text">{{ trans.user_name }}</span></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref, reactive } from 'vue';
import { useAuthStore } from '../stores/auth';
import { 
  Package, FileSpreadsheet, FileText, RotateCcw, Clock,
  TrendingUp, Activity, CloudUpload, RefreshCw
} from 'lucide-vue-next';
import * as XLSX from 'xlsx';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';

const authStore = useAuthStore();
const syncLoading = ref(false);

const inventoryData = ref(null);
const inventoryLoading = ref(false);
const inventoryFilters = reactive({
  start_date: new Date(new Date().setDate(new Date().getDate() - 30)).toISOString().split('T')[0],
  end_date: new Date().toISOString().split('T')[0],
  location: '',
  type: ''
});

onMounted(() => {
  fetchInventoryReport();
});

const fetchInventoryReport = async () => {
  inventoryLoading.value = true;
  try {
    const params = new URLSearchParams();
    if (inventoryFilters.start_date) params.append('start_date', inventoryFilters.start_date);
    if (inventoryFilters.end_date) params.append('end_date', inventoryFilters.end_date);
    if (inventoryFilters.location) params.append('location', inventoryFilters.location);
    if (inventoryFilters.type) params.append('type', inventoryFilters.type);

    console.log('Fetching inventory report with params:', params.toString());
    const response = await fetch(`${import.meta.env.VITE_API_URL}/reports/inventory?${params}`, {
      headers: {
        'Authorization': `Bearer ${authStore.token}`,
        'Accept': 'application/json'
      }
    });

    console.log('Response status:', response.status);
    if (response.ok) {
      const data = await response.json();
      console.log('Inventory data received:', data);
      inventoryData.value = data;
    } else {
      const errorText = await response.text();
      console.error('Error response:', errorText);
    }
  } catch (error) {
    console.error('Failed to fetch inventory report:', error);
  } finally {
    inventoryLoading.value = false;
  }
};

const formatQuantity = (value) => {
  return Number(value || 0).toLocaleString('id-ID', { 
    minimumFractionDigits: 0, 
    maximumFractionDigits: 2 
  });
};

const resetInventoryFilters = () => {
  inventoryFilters.start_date = new Date(new Date().setDate(new Date().getDate() - 30)).toISOString().split('T')[0];
  inventoryFilters.end_date = new Date().toISOString().split('T')[0];
  inventoryFilters.location = '';
  inventoryFilters.type = '';
  fetchInventoryReport();
};

const exportToExcel = () => {
  if (!inventoryData.value?.transactions) return;

  const data = inventoryData.value.transactions.map(t => ({
    'Tanggal': new Date(t.date).toLocaleString('id-ID'),
    'Lokasi': t.location,
    'Nama Bahan': t.item_name,
    'Tipe': t.type_label,
    'Jumlah': parseFloat(Number(t.quantity || 0).toFixed(2)),
    'Satuan': t.unit,
    'Catatan': t.notes || '-',
    'Kasir': t.user_name
  }));

  const ws = XLSX.utils.json_to_sheet(data);
  const wb = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(wb, ws, 'Laporan Inventori');
  
  const fileName = `Laporan_Inventori_${inventoryFilters.start_date}_${inventoryFilters.end_date}.xlsx`;
  XLSX.writeFile(wb, fileName);
};

const syncToGSheet = async () => {
  if (!inventoryData.value?.transactions) return;
  
  syncLoading.value = true;
  try {
    const response = await fetch(`${import.meta.env.VITE_API_URL}/settings/sync-inventory-gsheet`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${authStore.token}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        transactions: inventoryData.value.transactions
      })
    });

    const result = await response.json();
    if (response.ok) {
      alert(result.message || 'Laporan Inventori berhasil disinkronkan ke Google Sheets!');
    } else {
      alert(result.message || 'Gagal sinkronisasi Google Sheets.');
    }
  } catch (error) {
    console.error('Failed to sync to GSheet:', error);
    alert('Terjadi kesalahan saat sinkronisasi.');
  } finally {
    syncLoading.value = false;
  }
};

const exportToPDF = () => {
  if (!inventoryData.value?.transactions) return;

  const doc = new jsPDF();
  
  doc.setFontSize(16);
  doc.text('Laporan Transaksi Inventori', 14, 15);
  
  doc.setFontSize(10);
  doc.text(`Periode: ${inventoryFilters.start_date} s/d ${inventoryFilters.end_date}`, 14, 22);
  
  doc.text(`Total Masuk: ${formatQuantity(inventoryData.value.summary.total_in)}`, 14, 28);
  doc.text(`Total Keluar: ${formatQuantity(inventoryData.value.summary.total_out)}`, 14, 33);
  doc.text(`Total Transaksi: ${inventoryData.value.summary.total_transactions}`, 14, 38);
  
  const tableData = inventoryData.value.transactions.map(t => [
    new Date(t.date).toLocaleDateString('id-ID'),
    t.location,
    t.item_name,
    t.type_label,
    `${formatQuantity(t.quantity)} ${t.unit}`,
    t.notes || '-',
    t.user_name
  ]);
  
  autoTable(doc, {
    startY: 45,
    head: [['Tanggal', 'Lokasi', 'Bahan', 'Tipe', 'Jumlah', 'Catatan', 'Kasir']],
    body: tableData,
    styles: { fontSize: 8 },
    headStyles: { fillColor: [34, 197, 94] }
  });
  
  const fileName = `Laporan_Inventori_${inventoryFilters.start_date}_${inventoryFilters.end_date}.pdf`;
  doc.save(fileName);
};
</script>

<style scoped>
.inventory-report-container { padding: 0; animation: fadeIn 0.4s ease; }

/* Hero */
.page-hero {
  display: flex; align-items: center; justify-content: space-between;
  background: linear-gradient(135deg, rgba(34,197,94,0.08) 0%, rgba(34,197,94,0.02) 100%);
  border: 1px solid rgba(34,197,94,0.1); border-radius: 20px;
  padding: 20px 24px; margin-bottom: 16px;
}
.hero-content { display: flex; align-items: center; gap: 18px; }
.hero-icon-wrap {
  width: 48px; height: 48px; border-radius: 14px;
  background: linear-gradient(135deg, #22c55e, #4ade80);
  display: flex; align-items: center; justify-content: center;
  color: #fff; box-shadow: 0 8px 24px rgba(34, 197, 94, 0.25);
}
.hero-title { font-size: 20px; font-weight: 600; color: var(--text-primary); margin-bottom: 2px; }
.hero-subtitle { font-size: 13px; color: var(--text-secondary); font-weight: 500; }

.export-btn-group { display: flex; gap: 10px; }
.btn-export {
  display: flex; align-items: center; gap: 8px;
  padding: 10px 18px; border-radius: 12px; border: 1px solid var(--border-color);
  background: var(--bg-card); color: var(--text-primary);
  font-size: 13px; font-weight: 700; cursor: pointer; transition: 0.2s;
}
.btn-export:hover:not(:disabled) { background: var(--bg-primary); transform: translateY(-2px); }
.btn-export:disabled { opacity: 0.5; cursor: not-allowed; }
.btn-export.excel:hover:not(:disabled) { border-color: #10b981; color: #10b981; }
.btn-export.gsheet:hover:not(:disabled) { border-color: #4285F4; color: #4285F4; }
.btn-export.pdf:hover:not(:disabled) { border-color: #ef4444; color: #ef4444; }

/* Filter Bar */
.filter-glass-bar {
  display: flex; align-items: center; gap: 16px;
  background: var(--bg-card); border: 1px solid var(--border-color);
  padding: 16px; border-radius: 18px; margin-bottom: 16px;
}
.filter-row { display: flex; align-items: flex-end; gap: 12px; flex-wrap: wrap; width: 100%; }
.filter-item { display: flex; flex-direction: column; gap: 6px; }
.filter-label-mini {
  font-size: 10px; font-weight: 600; color: var(--text-muted);
  text-transform: uppercase; letter-spacing: 0.5px;
}
.filter-input-mini {
  height: 38px; padding: 0 12px; border-radius: 10px;
  background: var(--bg-primary); border: 1px solid var(--border-color);
  color: var(--text-primary); font-size: 13px; font-weight: 600; outline: none;
}
.btn-filter-reset {
  width: 38px; height: 38px; border-radius: 10px;
  background: var(--bg-primary); border: 1px solid var(--border-color);
  color: var(--text-secondary); cursor: pointer; transition: 0.2s;
  display: flex; align-items: center; justify-content: center;
}
.btn-filter-reset:hover { background: var(--success-bg); border-color: var(--success); color: var(--success); }

/* Summary Cards */
.inventory-summary-grid {
  display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;
  margin-bottom: 16px;
}
.inv-sum-card {
  display: flex; align-items: center; gap: 14px;
  background: var(--bg-card); padding: 20px; border-radius: 20px;
  border: 1px solid var(--border-color);
}
.inv-sum-icon {
  width: 48px; height: 48px; border-radius: 14px;
  display: flex; align-items: center; justify-content: center;
}
.inv-sum-icon.in { background: rgba(34,197,94,0.1); color: var(--success); }
.inv-sum-icon.out { background: rgba(239,68,68,0.1); color: var(--danger); }
.inv-sum-icon.total { background: rgba(14,165,233,0.1); color: var(--info); }
.inv-sum-label {
  display: block; font-size: 11px; font-weight: 600;
  color: var(--text-muted); text-transform: uppercase; margin-bottom: 4px;
}
.inv-sum-value { font-size: 22px; font-weight: 700; color: var(--text-primary); }

/* Table */
.table-outer-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 24px; overflow: hidden; }
.table-scroll-wrap { overflow-x: auto; }
.premium-table { width: 100%; border-collapse: collapse; }
.th-inner { padding: 16px 20px; font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.8px; display: flex; align-items: center; gap: 6px; }
.table-row { border-bottom: 1px solid var(--border-color); transition: 0.2s; animation: slideUp 0.4s ease both; }
.table-row:hover { background: var(--bg-primary); }
.premium-table td { padding: 14px 20px; vertical-align: middle; }

.date-cell { display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; color: var(--text-secondary); }
.location-badge {
  display: inline-block; padding: 4px 10px; border-radius: 8px;
  font-size: 11px; font-weight: 700; text-transform: uppercase;
}
.location-badge.gudang { background: rgba(14,165,233,0.1); color: var(--info); }
.location-badge.dapur { background: rgba(249,115,22,0.1); color: var(--accent); }
.item-name-text { font-size: 13px; font-weight: 700; color: var(--text-primary); }
.type-badge {
  display: inline-block; padding: 4px 10px; border-radius: 8px;
  font-size: 11px; font-weight: 700;
}
.type-badge.in { background: rgba(34,197,94,0.1); color: var(--success); }
.type-badge.out, .type-badge.consume { background: rgba(239,68,68,0.1); color: var(--danger); }
.type-badge.transfer { background: rgba(14,165,233,0.1); color: var(--info); }
.type-badge.return { background: rgba(168,85,247,0.1); color: #a855f7; }
.qty-text { font-size: 14px; font-weight: 700; color: var(--text-primary); }
.qty-text small { font-size: 11px; color: var(--text-muted); font-weight: 600; }
.notes-text, .user-text { font-size: 12px; color: var(--text-secondary); }

.inv-loading, .inv-empty {
  padding: 80px 20px; text-align: center; color: var(--text-muted);
}
.loading-spinner {
  width: 32px; height: 32px; border: 3px solid var(--success-bg);
  border-top-color: var(--success); border-radius: 50%;
  animation: spin 0.8s linear infinite; margin: 0 auto 16px;
}

@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
@keyframes spin { to { transform: rotate(360deg); } }

/* Responsive */
@media (max-width: 1200px) {
  .inventory-summary-grid { grid-template-columns: 1fr; }
}

@media (max-width: 768px) {
  .page-hero { flex-direction: column; gap: 12px; padding: 16px; align-items: flex-start; }
  .hero-icon-wrap { width: 36px; height: 36px; border-radius: 10px; }
  .hero-icon-wrap svg { width: 18px; height: 18px; }
  .hero-title { font-size: 16px; }
  .hero-subtitle { display: none; }
  
  .export-btn-group { width: 100%; }
  .btn-export { flex: 1; justify-content: center; }
  
  .filter-glass-bar { padding: 12px; }
  .filter-row { gap: 8px; }
  .filter-item { flex: 1; min-width: 120px; }
  
  /* Mobile card layout */
  .premium-table thead { display: none; }
  .table-row {
    display: block; padding: 16px; margin-bottom: 12px;
    background: var(--bg-primary); border-radius: 16px;
    border: 1px solid var(--border-color);
  }
  .table-row td {
    display: flex; justify-content: space-between; align-items: center;
    padding: 8px 0; border-bottom: 1px solid var(--border-color);
  }
  .table-row td:last-child { border-bottom: none; }
  .table-row td::before {
    content: attr(data-label); font-size: 10px; font-weight: 600;
    color: var(--text-muted); text-transform: uppercase;
  }
  .table-row td.text-right { justify-content: space-between; }
}
</style>
