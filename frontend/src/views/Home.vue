<template>
  <div class="home-container">
    <!-- Welcome Header -->
    <header class="home-header">
      <div class="welcome-box">
        <h1 class="welcome-title">Selamat Datang, <span>{{ auth.user?.full_name || 'Admin' }}</span>! 👋</h1>
        <p class="welcome-subtitle">
          <template v-if="isSuperAdmin">Berikut ringkasan performa platform Kee POS hari ini.</template>
          <template v-else>Berikut ringkasan performa Kee POS hari ini, {{ currentFullDate }}.</template>
        </p>
      </div>
      <div class="header-actions">
        <div class="time-display">
          <Clock :size="16" />
          <span>{{ liveTime }}</span>
        </div>
        <router-link v-if="!isSuperAdmin" to="/app/pos" class="btn-pos-launch" @click.prevent="handlePOSNavigation">
          <ShoppingCart :size="18" /> Buka Kasir
        </router-link>
        <router-link v-else to="/app/admin/tenants" class="btn-pos-launch blue">
          <Store :size="18" /> Manajemen Toko
        </router-link>
      </div>
    </header>

    <!-- Stats Dashboard -->
    <section class="stats-section">
      <div class="stats-grid">
        <div class="premium-stat-card orange" v-for="(stat, idx) in statList" :key="idx" :style="{ animationDelay: (idx * 0.1) + 's' }">
          <div class="stat-main">
            <div class="stat-icon-wrap" :class="stat.color">
              <component :is="stat.icon" :size="22" />
            </div>
            <div class="stat-data">
              <span class="stat-label">{{ stat.label }}</span>
              <div class="stat-value-group">
                <h2 class="stat-value">{{ stat.value }}</h2>
                <span v-if="stat.trend" class="stat-trend" :class="stat.trend > 0 ? 'up' : 'down'">
                  <TrendingUp v-if="stat.trend > 0" :size="12" />
                  <TrendingDown v-else :size="12" />
                  {{ Math.abs(stat.trend) }}%
                </span>
              </div>
            </div>
          </div>
          <div class="stat-footer">
            <span class="stat-desc">{{ stat.desc }}</span>
          </div>
        </div>
      </div>
    </section>

    <!-- Main Dashboard Grid -->
    <div class="dashboard-layout mt-8">
      <!-- Left: Sales Chart -->
      <div class="dashboard-main-col">
        <div class="glass-card chart-panel">
          <div class="card-top">
            <div class="card-top-left">
              <div class="panel-icon orange">
                <BarChart3 v-if="!isSuperAdmin" :size="18" />
                <TrendingUp v-else :size="18" />
              </div>
              <h3 class="panel-title">{{ isSuperAdmin ? 'Pertumbuhan Toko (30 Hari)' : 'Penjualan 7 Hari Terakhir' }}</h3>
            </div>
            <div class="card-top-right">
              <div class="chart-legend">
                <span class="legend-dot"></span>
                Total Omzet
              </div>
            </div>
          </div>
          
          <div class="chart-container">
            <div v-if="!stats.chart_data.length" class="chart-loading">
               <div class="spinner-small"></div>
               <span>Memuat data grafik...</span>
            </div>
            <div v-else class="bar-chart-premium">
              <div v-for="(day, idx) in stats.chart_data" :key="idx" class="bar-col">
                <div class="bar-track">
                  <div class="bar-fill" :style="{ height: (day.total / maxChartValue * 100) + '%' }">
                    <div class="bar-tooltip">{{ formatCurrency(day.total) }}</div>
                  </div>
                </div>
                <span class="bar-day">{{ formatDate(day.date) }}</span>
              </div>
            </div>
          </div>

          <div class="chart-insight">
            <Zap :size="14" />
            <span>Tren penjualan meningkat <b>12%</b> dibandingkan periode sebelumnya.</span>
          </div>
        </div>
      </div>

      <!-- Right: Recent Activity -->
      <div class="dashboard-side-col">
        <div class="glass-card activity-panel">
          <div class="card-top">
            <div class="card-top-left">
              <div class="panel-icon blue">
                <Activity v-if="!isSuperAdmin" :size="18" />
                <Store v-else :size="18" />
              </div>
              <h3 class="panel-title">{{ isSuperAdmin ? 'Pendaftaran Terbaru' : 'Transaksi Terbaru' }}</h3>
            </div>
            <router-link :to="isSuperAdmin ? '/app/admin/tenants' : '/app/orders'" class="view-all-link">Lihat Semua</router-link>
          </div>

          <div class="activity-list" v-if="!isSuperAdmin && stats.recent_sales.length">
            <div v-for="sale in stats.recent_sales" :key="sale.id" class="activity-item">
              <div class="activity-icon" :class="sale.payment_method?.toLowerCase() || 'cash'">
                <Banknote v-if="sale.payment_method === 'cash' || !sale.payment_method" :size="14" />
                <Smartphone v-else :size="14" />
              </div>
              <div class="activity-info">
                <div class="activity-header">
                  <span class="activity-name">{{ sale.invoice_number || 'INV-' + sale.id }}</span>
                  <span class="activity-price">{{ formatCurrency(sale.total_amount) }}</span>
                </div>
                <div class="activity-meta">
                  <span class="activity-time">{{ formatTime(sale.created_at) }}</span>
                  <span class="activity-method">{{ sale.payment_method?.toUpperCase() }}</span>
                </div>
              </div>
            </div>
          </div>
          <div class="activity-list" v-else-if="isSuperAdmin && adminStats.recent_tenants.length">
            <div v-for="tenant in adminStats.recent_tenants" :key="tenant.id" class="activity-item">
              <div class="activity-icon qris">
                <Store :size="14" />
              </div>
              <div class="activity-info">
                <div class="activity-header">
                  <span class="activity-name">{{ tenant.name }}</span>
                  <span class="activity-badge" :class="tenant.plan">{{ tenant.plan.toUpperCase() }}</span>
                </div>
                <div class="activity-meta">
                  <span class="activity-time">{{ formatDateLong(tenant.created_at) }}</span>
                  <span class="activity-method">{{ tenant.slug }}</span>
                </div>
              </div>
            </div>
          </div>
          <div v-else class="empty-recent">
            <div class="empty-icon-box">
              <Inbox :size="32" />
            </div>
            <p>Belum ada aktivitas hari ini</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, reactive, computed, ref, onUnmounted, inject } from 'vue';
import api from '../api';
import { useAuthStore } from '../stores/auth';
import { 
  Banknote, Package, AlertTriangle, Clock, ShoppingCart, 
  TrendingUp, TrendingDown, BarChart3, Activity, Zap,
  Inbox, Smartphone, LayoutDashboard, Store, CreditCard, Users
} from 'lucide-vue-next';

const auth = useAuthStore();
const handlePOSNavigation = inject('handlePOSNavigation');
const stats = reactive({
  today_sales: 0,
  today_transactions: 0,
  total_products: 0,
  low_stock: 0,
  active_shifts: 0,
  recent_sales: [],
  chart_data: []
});

const liveTime = ref('');
const currentFullDate = computed(() => {
  return new Date().toLocaleDateString('id-ID', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  });
});

const isSuperAdmin = computed(() => auth.user?.role === 'superadmin');

const statList = computed(() => {
  if (isSuperAdmin.value) {
    return [
      { label: 'Total Toko', value: adminStats.metrics.total_tenants || 0, desc: `${adminStats.metrics.active_tenants || 0} Toko Aktif`, icon: Store, color: 'blue' },
      { label: 'Total Omzet (Global)', value: formatCurrency(adminStats.metrics.total_sales || 0), desc: `${adminStats.metrics.total_transactions || 0} Transaksi`, icon: Banknote, color: 'green' },
      { label: 'Estimasi Pendapatan', value: formatCurrency(adminStats.metrics.estimated_revenue_monthly || 0), desc: 'Berdasarkan plan aktif', icon: CreditCard, color: 'orange' },
      { label: 'Total User', value: adminStats.metrics.total_users || 0, desc: 'Penyewa & Karyawan', icon: Users, color: 'purple' }
    ];
  }
  return [
    { label: 'Penjualan Hari Ini', value: formatCurrency(stats.today_sales), desc: `${stats.today_transactions} transaksi berhasil`, icon: Banknote, color: 'orange', trend: 8.4 },
    { label: 'Total Produk', value: stats.total_products, desc: 'Aktif di etalase POS', icon: Package, color: 'blue', trend: 0 },
    { label: 'Stok Kritis', value: stats.low_stock, desc: 'Butuh segera diisi ulang', icon: AlertTriangle, color: 'red', trend: stats.low_stock > 0 ? -12 : 0 },
    { label: 'Shift Kasir', value: stats.active_shifts, desc: 'Sedang berjalan saat ini', icon: Clock, color: 'green', trend: 0 }
  ];
});

const adminStats = reactive({
  metrics: {},
  recent_tenants: [],
  growth: [],
  top_tenants: [],
  plan_distribution: []
});

const updateTime = () => {
  liveTime.value = new Date().toLocaleTimeString('id-ID', {
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit'
  });
};

const fetchAdminDashboard = async () => {
  try {
    const res = await api.get('/admin/dashboard');
    if (res.data.success) {
      Object.assign(adminStats, res.data.data);
      // Map growth to chart_data for reuse
      stats.chart_data = adminStats.growth.map(g => ({
        date: g.date,
        total: g.count
      }));
    }
  } catch (err) {
    console.error('Failed to fetch admin dashboard', err);
  }
};

const fetchTenantDashboard = async () => {
  try {
    const today = new Date().toISOString().split('T')[0];
    const res = await api.get('/reports/sales', {
      params: { start_date: today, end_date: today }
    });
    if (res.data.success) {
      const data = res.data.data;
      stats.today_sales = data.summary.total_sales;
      stats.today_transactions = data.summary.total_transactions;
      
      stats.chart_data = Object.entries(data.sales_by_date).map(([date, values]) => ({
        date,
        ...values
      })).sort((a, b) => a.date.localeCompare(b.date));
    }
    
    // Fetch recent transactions
    const ordersRes = await api.get('/orders', {
      params: { per_page: 5 }
    });
    if (ordersRes.data.success) {
      stats.recent_sales = ordersRes.data.data.data || [];
    }
    
    const prodRes = await api.get('/products');
    stats.total_products = prodRes.data.data.products.total;

    const stockRes = await api.get('/reports/stock');
    stats.low_stock = stockRes.data.data.low_stock_count;

    // Active shifts
    const shiftRes = await api.get('/shifts');
    if (shiftRes.data.success) {
      stats.active_shifts = shiftRes.data.data.filter(s => s.status === 'open').length;
    }
  } catch (err) {
    console.error(err);
  }
};

let timer;
onMounted(async () => {
  updateTime();
  timer = setInterval(updateTime, 1000);
  
  if (isSuperAdmin.value) {
    await fetchAdminDashboard();
  } else {
    await fetchTenantDashboard();
  }
});

onUnmounted(() => clearInterval(timer));

const maxChartValue = computed(() => {
  return Math.max(...stats.chart_data.map(d => d.total), 1);
});

const formatCurrency = (val) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);

const formatTime = (dateStr) => {
  if (!dateStr) return '-';
  const date = new Date(dateStr);
  return date.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
};

const formatDate = (dateStr) => {
  const date = new Date(dateStr);
  return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
};
const formatDateLong = (dateStr) => {
  if (!dateStr) return '-';
  return new Date(dateStr).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
};
</script>

<style scoped>
.btn-pos-launch.blue {
  background: linear-gradient(135deg, #3b82f6, #0ea5e9);
  box-shadow: 0 8px 20px rgba(59,130,246,0.3);
}

.activity-badge {
  font-size: 10px; font-weight: 800; padding: 2px 6px; border-radius: 4px;
}
.activity-badge.free { background: #f1f5f9; color: #64748b; }
.activity-badge.basic { background: #eff6ff; color: #3b82f6; }
.activity-badge.pro { background: #fffbeb; color: #d97706; }

.home-container { padding: 0; animation: fadeIn 0.4s ease; }

/* ── Welcome Header ── */
.home-header {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 32px; flex-wrap: wrap; gap: 20px;
}
.welcome-title { font-size: 26px; font-weight: 600; color: var(--text-primary); margin-bottom: 6px; }
.welcome-title span { color: var(--accent); }
.welcome-subtitle { font-size: 14px; color: var(--text-muted); font-weight: 500; }

.header-actions { display: flex; align-items: center; gap: 16px; }
.time-display {
  display: flex; align-items: center; gap: 8px;
  padding: 10px 16px; border-radius: 12px;
  background: var(--bg-card); border: 1px solid var(--border-color);
  color: var(--text-secondary); font-size: 14px; font-weight: 700;
  font-family: 'Courier New', Courier, monospace;
}
.btn-pos-launch {
  display: flex; align-items: center; gap: 10px;
  background: linear-gradient(135deg, var(--accent), #fb923c);
  color: #fff; padding: 12px 24px; border-radius: 14px;
  font-weight: 700; text-decoration: none; font-size: 14px;
  box-shadow: 0 8px 20px rgba(249,115,22,0.3);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.btn-pos-launch:hover { transform: translateY(-2px); box-shadow: 0 12px 28px rgba(249,115,22,0.45); }

/* ── Stats Section ── */
.stats-grid { 
  display: grid; 
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); 
  gap: 20px; 
}
.premium-stat-card {
  background: var(--bg-card); border: 1px solid var(--border-color);
  border-radius: 24px; padding: 24px;
  display: flex; flex-direction: column; gap: 16px;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  animation: slideInUp 0.6s ease both;
}
.premium-stat-card:hover { transform: translateY(-6px); border-color: var(--accent); box-shadow: 0 12px 30px -10px rgba(0,0,0,0.1); }

.stat-main { display: flex; align-items: flex-start; gap: 16px; }
.stat-icon-wrap {
  width: 52px; height: 52px; border-radius: 16px;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.stat-icon-wrap.orange { background: rgba(249,115,22,0.1); color: var(--accent); }
.stat-icon-wrap.blue { background: rgba(14,165,233,0.1); color: var(--info); }
.stat-icon-wrap.red { background: rgba(239,68,68,0.1); color: var(--danger); }
.stat-icon-wrap.green { background: rgba(34,197,94,0.1); color: var(--success); }

.stat-data { flex: 1; }
.stat-label { font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; display: block; }
.stat-value-group { display: flex; align-items: baseline; gap: 8px; flex-wrap: wrap; }
.stat-value { font-size: 24px; font-weight: 700; color: var(--text-primary); }

.stat-trend {
  font-size: 11px; font-weight: 600; padding: 2px 6px; border-radius: 6px;
  display: flex; align-items: center; gap: 3px;
}
.stat-trend.up { background: var(--success-bg); color: var(--success); }
.stat-trend.down { background: var(--danger-bg); color: var(--danger); }

.stat-footer { padding-top: 14px; border-top: 1px solid var(--border-color); }
.stat-desc { font-size: 12px; color: var(--text-muted); font-weight: 500; }

/* ── Dashboard Layout ── */
.dashboard-layout { display: grid; grid-template-columns: 1.6fr 1fr; gap: 24px; }

.glass-card {
  background: var(--bg-card); border: 1px solid var(--border-color);
  border-radius: 24px; display: flex; flex-direction: column; overflow: hidden;
}
.card-top {
  padding: 24px; border-bottom: 1px solid var(--border-color);
  display: flex; align-items: center; justify-content: space-between;
}
.card-top-left { display: flex; align-items: center; gap: 12px; }
.panel-icon {
  width: 38px; height: 38px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
}
.panel-icon.orange { background: rgba(249,115,22,0.1); color: var(--accent); }
.panel-icon.blue { background: rgba(14,165,233,0.1); color: var(--info); }
.panel-title { font-size: 16px; font-weight: 600; color: var(--text-primary); }

/* ── Chart Panel ── */
.chart-container { height: 320px; padding: 24px 32px; overflow: hidden; }
.chart-loading { height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 12px; color: var(--text-muted); }
.spinner-small { width: 20px; height: 20px; border: 2px solid var(--accent-light); border-top-color: var(--accent); border-radius: 50%; animation: spin 0.8s linear infinite; }

.bar-chart-premium { display: flex; align-items: flex-end; justify-content: space-between; height: 100%; gap: 16px; }
.bar-col { flex: 1; display: flex; flex-direction: column; align-items: center; height: 100%; justify-content: flex-end; }
.bar-track {
  width: 100%; height: 100%; background: var(--bg-primary); 
  border-radius: 12px; position: relative; display: flex; align-items: flex-end;
  overflow: visible;
}
.bar-fill {
  width: 100%; background: linear-gradient(to top, var(--accent), #fb923c);
  border-radius: 10px; min-height: 4px; position: relative;
  transition: height 1s cubic-bezier(0.34, 1.56, 0.64, 1);
  cursor: pointer;
}
.bar-fill:hover { filter: brightness(1.1); }
.bar-tooltip {
  position: absolute; top: -40px; left: 50%; transform: translateX(-50%);
  background: var(--text-primary); color: var(--bg-primary);
  padding: 5px 10px; border-radius: 8px; font-size: 10px; font-weight: 600;
  opacity: 0; pointer-events: none; transition: 0.2s; white-space: nowrap;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.bar-fill:hover .bar-tooltip { opacity: 1; transform: translateX(-50%) translateY(-10px); }
.bar-day { font-size: 11px; font-weight: 700; color: var(--text-muted); margin-top: 12px; }

.chart-insight {
  display: flex; align-items: center; gap: 10px;
  padding: 16px 24px; background: rgba(0,0,0,0.02);
  border-top: 1px solid var(--border-color); font-size: 12px; color: var(--text-secondary);
}
:root:not(.light) .chart-insight { background: rgba(255,255,255,0.02); }
.chart-insight b { color: var(--success); }

.legend-dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: var(--accent); margin-right: 6px; }
.chart-legend { font-size: 12px; font-weight: 600; color: var(--text-muted); }

/* ── Activity Panel ── */
.activity-list { display: flex; flex-direction: column; padding: 8px 0; }
.activity-item {
  display: flex; align-items: center; gap: 14px;
  padding: 16px 24px; border-bottom: 1px solid var(--border-color);
  transition: all 0.2s;
}
.activity-item:last-child { border-bottom: none; }
.activity-item:hover { background: var(--bg-primary); }

.activity-icon {
  width: 36px; height: 36px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.activity-icon.cash { background: rgba(34,197,94,0.1); color: var(--success); }
.activity-icon.transfer, .activity-icon.qris { background: rgba(14,165,233,0.1); color: var(--info); }

.activity-info { flex: 1; min-width: 0; }
.activity-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px; }
.activity-name { font-size: 14px; font-weight: 700; color: var(--text-primary); }
.activity-price { font-size: 14px; font-weight: 600; color: var(--accent); }
.activity-meta { display: flex; align-items: center; gap: 8px; font-size: 11px; color: var(--text-muted); font-weight: 500; }

.view-all-link { font-size: 12px; font-weight: 700; color: var(--accent); text-decoration: none; }
.view-all-link:hover { text-decoration: underline; }

.empty-recent { padding: 60px 20px; text-align: center; color: var(--text-muted); }
.empty-icon-box { margin-bottom: 16px; opacity: 0.2; }

/* ── Animations ── */
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes slideInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
@keyframes spin { to { transform: rotate(360deg); } }

/* ── Responsive ── */
@media (max-width: 1200px) {
  .dashboard-layout { grid-template-columns: 1fr; }
}

@media (max-width: 768px) {
  .home-header { flex-direction: column; align-items: stretch; gap: 12px; margin-bottom: 16px; }
  .welcome-title { font-size: 18px; margin-bottom: 2px; }
  .welcome-subtitle { font-size: 12px; }
  .header-actions { width: 100%; justify-content: space-between; }
  .time-display { padding: 8px 12px; font-size: 12px; }
  .btn-pos-launch { flex: 1; justify-content: center; padding: 10px 16px; font-size: 13px; border-radius: 12px; }

  .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; }
  .premium-stat-card { padding: 16px; border-radius: 16px; gap: 10px; }
  .stat-icon-wrap { width: 40px; height: 40px; border-radius: 12px; }
  .stat-icon-wrap svg { width: 18px; height: 18px; }
  .stat-value { font-size: 18px; }
  .stat-label { font-size: 10px; }
  .stat-footer { padding-top: 10px; }
  .stat-desc { font-size: 10px; }

  .glass-card { border-radius: 16px; }
  .card-top { padding: 16px; }
  .panel-title { font-size: 14px; }
  .chart-container { height: 220px; padding: 16px; }
  .bar-chart-premium { gap: 8px; }
  .bar-day { font-size: 9px; }
  .chart-insight { padding: 12px 16px; font-size: 11px; }

  .activity-item { padding: 12px 16px; gap: 10px; }
  .activity-name { font-size: 13px; }
  .activity-price { font-size: 13px; }

  .mt-8 { margin-top: 16px; }
}

@media (max-width: 480px) {
  .welcome-title { font-size: 16px; }
  .stats-grid { grid-template-columns: 1fr 1fr; gap: 10px; }
  .stat-value { font-size: 16px; }
  .stat-trend { display: none; }
  .chart-container { height: 180px; padding: 12px; }
  .bar-tooltip { display: none; }
}

.mt-8 { margin-top: 32px; }
</style>

