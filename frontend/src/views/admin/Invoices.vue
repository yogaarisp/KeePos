<template>
  <div class="admin-invoices">
    <!-- Hero Header -->
    <div class="page-hero">
      <div class="hero-content">
        <div class="hero-icon-wrap">
          <Receipt :size="24" />
        </div>
        <div>
          <h1 class="hero-title">History Invoice Global</h1>
          <p class="hero-subtitle">Pantau seluruh transaksi masuk dari semua tenant secara real-time.</p>
        </div>
      </div>
    </div>

    <!-- Stats Overview -->
    <div class="stats-row">
      <div class="stat-mini-card">
        <div class="sm-icon paid"><CheckCircle :size="18" /></div>
        <div class="sm-info">
          <span class="sm-label">TOTAL PAID</span>
          <h4 class="sm-val">{{ stats.paid_count }} Transaksi</h4>
        </div>
      </div>
      <div class="stat-mini-card">
        <div class="sm-icon pending"><Clock :size="18" /></div>
        <div class="sm-info">
          <span class="sm-label">TOTAL PENDING</span>
          <h4 class="sm-val">{{ stats.pending_count }} Transaksi</h4>
        </div>
      </div>
      <div class="stat-mini-card">
        <div class="sm-icon amount"><DollarSign :size="18" /></div>
        <div class="sm-info">
          <span class="sm-label">EST. REVENUE (PAID)</span>
          <h4 class="sm-val">Rp {{ formatNumber(stats.total_amount) }}</h4>
        </div>
      </div>
    </div>

    <!-- Global Invoices Card -->
    <div class="section-card">
      <div class="card-header">
        <div class="header-left">
          <h2 class="card-title">Daftar Transaksi</h2>
          <span class="card-badge">{{ invoices.length }} Invoice Terakhir</span>
        </div>
        <div class="header-right">
          <div class="search-box">
            <Search :size="16" class="search-icon" />
            <input type="text" v-model="searchQuery" placeholder="Cari tenant atau #invoice..." class="search-input">
          </div>
        </div>
      </div>

      <div class="table-responsive">
        <table class="premium-table">
          <thead>
            <tr>
              <th># INVOICE</th>
              <th>TENANT / TOKO</th>
              <th>PAKET</th>
              <th>DURASI</th>
              <th>NOMINAL</th>
              <th>STATUS</th>
              <th class="text-right">TANGGAL</th>
              <th class="text-right">BUKTI</th>
            </tr>
          </thead>
          <tbody>
            <!-- Loading State -->
            <tr v-if="loading">
              <td colspan="8" class="loading-cell">
                <div class="loader-wrap">
                  <RefreshCw :size="32" class="spin" />
                  <p>Memuat data transaksi...</p>
                </div>
              </td>
            </tr>

            <!-- Data Rows -->
            <template v-else>
              <tr v-for="inv in filteredInvoices" :key="inv.id" class="data-row">
                <td class="td-mono">#{{ inv.external_id ? inv.external_id.split('-').pop() : inv.invoice_number }}</td>
                <td>
                  <div class="tenant-cell">
                    <div class="tenant-avatar">{{ inv.tenant?.name?.charAt(0) || 'T' }}</div>
                    <div class="tenant-info">
                      <span class="tenant-name">{{ inv.tenant?.name || 'Unknown' }}</span>
                      <span class="tenant-id">ID: {{ inv.tenant_id }}</span>
                    </div>
                  </div>
                </td>
                <td>
                  <div class="plan-badge" :class="inv.plan">{{ inv.plan.toUpperCase() }}</div>
                </td>
                <td class="td-medium">{{ inv.months || 1 }} Bulan</td>
                <td class="td-bold">Rp {{ formatNumber(inv.amount) }}</td>
                <td>
                  <div class="status-pill" :class="inv.status">
                    <span class="pill-dot"></span>
                    {{ formatStatus(inv.status) }}
                  </div>
                </td>
                <td class="text-right text-muted td-date">
                  {{ formatDate(inv.paid_at || inv.created_at) }}
                </td>
                <td class="text-right td-proof">
                  <a
                    v-if="inv.payment_proof_url"
                    :href="inv.payment_proof_url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="link-proof-admin"
                  >Lihat bukti</a>
                  <span v-else class="text-muted td-proof-empty">—</span>
                </td>
              </tr>

              <!-- Empty State -->
              <tr v-if="filteredInvoices.length === 0">
                <td colspan="8" class="empty-cell">
                  <div class="empty-wrapper">
                    <ClipboardList :size="48" class="empty-icon" />
                    <h4>Tidak Ada Transaksi</h4>
                    <p>Belum ada data transaksi yang sesuai dengan pencarian Anda.</p>
                  </div>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import api from '../../api';
import { 
  Receipt, Search, CheckCircle, Clock, DollarSign, 
  RefreshCw, ClipboardList
} from 'lucide-vue-next';

const loading = ref(true);
const invoices = ref([]);
const searchQuery = ref('');

const stats = computed(() => {
  const paid = invoices.value.filter(i => i.status === 'paid' || i.status === 'settled');
  const pending = invoices.value.filter(i => i.status === 'pending');
  return {
    paid_count: paid.length,
    pending_count: pending.length,
    total_amount: paid.reduce((acc, i) => acc + parseFloat(i.amount), 0)
  };
});

const filteredInvoices = computed(() => {
  if (!searchQuery.value) return invoices.value;
  const q = searchQuery.value.toLowerCase();
  return invoices.value.filter(i => 
    i.tenant?.name?.toLowerCase().includes(q) || 
    i.invoice_number.toLowerCase().includes(q) ||
    i.external_id?.toLowerCase().includes(q)
  );
});

const fetchData = async () => {
  loading.value = true;
  try {
    const res = await api.get('/admin/invoices');
    if (res.data.success) {
      // API returns paginated data: res.data.data.data
      invoices.value = Array.isArray(res.data.data) ? res.data.data : (res.data.data.data || []);
    }
  } catch (err) {
    console.error('Failed to fetch global invoices', err);
  } finally {
    loading.value = false;
  }
};

onMounted(fetchData);

const formatNumber = (num) => {
  if (num === null || num === undefined) return '0';
  return new Intl.NumberFormat('id-ID').format(Math.round(num));
};

const formatDate = (date) => {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const formatStatus = (s) => {
  const maps = {
    'settled': 'Lunas',
    'paid': 'Lunas',
    'pending': 'Menunggu',
    'expired': 'Kadaluarsa'
  };
  return maps[s] || s;
};
</script>

<style scoped>
.admin-invoices {
  animation: fadeIn 0.4s ease;
}

/* ── Hero ── */
.page-hero {
  background: linear-gradient(135deg, rgba(249,115,22,0.1) 0%, rgba(249,115,22,0.02) 100%);
  border: 1px solid rgba(249,115,22,0.1); border-radius: 20px;
  padding: 24px 28px; margin-bottom: 24px;
}
.hero-content { display: flex; align-items: center; gap: 18px; }
.hero-icon-wrap {
  width: 50px; height: 50px; border-radius: 14px;
  background: linear-gradient(135deg, var(--accent), #fb923c);
  display: flex; align-items: center; justify-content: center;
  color: #fff; box-shadow: 0 8px 20px rgba(249,115,22,0.2);
}
.hero-title { font-size: 20px; font-weight: 700; color: var(--text-primary); margin: 0 0 4px; }
.hero-subtitle { font-size: 13.5px; color: var(--text-muted); font-weight: 500; margin: 0; }

/* ── Stats ── */
.stats-row {
  display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px;
  margin-bottom: 24px;
}
.stat-mini-card {
  background: var(--bg-card); border: 1px solid var(--border-color);
  padding: 16px; border-radius: 16px; display: flex; align-items: center; gap: 16px;
}
.sm-icon {
  width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center;
}
.sm-icon.paid { background: rgba(34, 197, 94, 0.1); color: #22c55e; }
.sm-icon.pending { background: rgba(234, 179, 8, 0.1); color: #eab308; }
.sm-icon.amount { background: rgba(249, 115, 22, 0.1); color: var(--accent); }

.sm-label { font-size: 10px; font-weight: 800; color: var(--text-dim); letter-spacing: 0.5px; }
.sm-val { font-size: 16px; font-weight: 800; color: var(--text-primary); margin: 2px 0 0; }

/* ── Card ── */
.section-card {
  background: var(--bg-card); border-radius: 24px; border: 1px solid var(--border-color);
  overflow: hidden; box-shadow: var(--shadow);
}
.card-header {
  padding: 20px 24px; display: flex; align-items: center; justify-content: space-between;
  border-bottom: 1px solid var(--border-color); background: var(--bg-primary);
  flex-wrap: wrap; gap: 16px;
}
.card-title { font-size: 16px; font-weight: 700; margin: 0; }
.card-badge { 
  font-size: 11px; font-weight: 700; color: var(--text-muted); 
  background: var(--border-color); padding: 4px 10px; border-radius: 50px;
  margin-left: 12px;
}

.search-box { position: relative; width: 100%; max-width: 300px; }
.search-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-dim); }
.search-input {
  width: 100%; height: 40px; padding: 0 14px 0 40px; border-radius: 12px;
  background: var(--bg-card); border: 1.5px solid var(--border-color);
  font-size: 13.5px; color: var(--text-primary); outline: none; transition: 0.2s;
}
.search-input:focus { border-color: var(--accent); box-shadow: 0 0 0 4px var(--accent-light); }

/* ── Table ── */
.premium-table { width: 100%; border-collapse: collapse; }
.premium-table th {
  padding: 16px 24px; text-align: left; font-size: 11px; font-weight: 800;
  color: var(--text-dim); text-transform: uppercase; letter-spacing: 0.5px;
  background: rgba(0,0,0,0.02); border-bottom: 1px solid var(--border-color);
}
.dark .premium-table th { background: rgba(255,255,255,0.02); }

.data-row { transition: 0.2s; border-bottom: 1px solid var(--border-color); }
.data-row:hover { background: var(--bg-primary); }
.data-row td { padding: 16px 24px; vertical-align: middle; font-size: 13.5px; }

.td-mono { font-family: 'JetBrains Mono', monospace; color: var(--accent); font-weight: 700; font-size: 12px; }
.td-bold { font-weight: 800; color: var(--text-primary); }
.td-date { font-size: 12px; }

/* ── Tenant Cell ── */
.tenant-cell { display: flex; align-items: center; gap: 12px; }
.tenant-avatar {
  width: 36px; height: 36px; border-radius: 10px; background: var(--accent);
  color: #fff; display: flex; align-items: center; justify-content: center;
  font-weight: 800; font-size: 16px;
}
.tenant-info { display: flex; flex-direction: column; }
.tenant-name { font-weight: 700; color: var(--text-primary); line-height: 1.2; }
.tenant-id { font-size: 10px; color: var(--text-dim); font-weight: 600; margin-top: 2px; }

/* ── Badges ── */
.plan-badge {
  display: inline-block; padding: 4px 10px; border-radius: 6px; font-size: 10px; font-weight: 900;
}
.plan-badge.free { background: #f1f5f9; color: #64748b; }
.plan-badge.basic { background: rgba(37, 99, 235, 0.1); color: #2563eb; }
.plan-badge.pro { background: rgba(249, 115, 22, 0.1); color: var(--accent); }

.status-pill {
  display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 50px;
  font-size: 11px; font-weight: 800;
}
.pill-dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
.status-pill.paid, .status-pill.settled { background: rgba(34, 197, 94, 0.1); color: #22c55e; }
.status-pill.pending { background: rgba(234, 179, 8, 0.1); color: #eab308; }
.status-pill.expired { background: rgba(239, 68, 68, 0.1); color: #ef4444; }

/* ── Empty/Loading ── */
.loading-cell, .empty-cell { padding: 60px 0; text-align: center; }
.loader-wrap { display: flex; flex-direction: column; align-items: center; gap: 12px; color: var(--text-dim); }
.spin { animation: spin 1s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

.empty-wrapper { display: flex; flex-direction: column; align-items: center; gap: 12px; color: var(--text-dim); }
.empty-icon { opacity: 0.3; }
.empty-wrapper h4 { margin: 0; color: var(--text-secondary); }
.empty-wrapper p { margin: 0; font-size: 13px; max-width: 300px; }

.link-proof-admin {
  font-size: 12px;
  font-weight: 700;
  color: #2563eb;
  text-decoration: none;
}
.link-proof-admin:hover { text-decoration: underline; }
.td-proof-empty { font-size: 12px; }

@media (max-width: 768px) {
  .stats-row { grid-template-columns: 1fr; }
  .card-header { flex-direction: column; align-items: flex-start; }
  .search-box { max-width: 100%; }
}
</style>
