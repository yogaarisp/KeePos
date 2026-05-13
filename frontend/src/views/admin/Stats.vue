<template>
  <div class="stats-view">
    <!-- Header Page -->
    <div class="page-header-premium">
      <div class="header-left">
        <div class="header-icon-box">
          <BarChart3 :size="24" />
        </div>
        <div class="header-text">
          <h1>Statistik Platform</h1>
          <p>Pantau performa bisnis SaaS Kee POS secara real-time.</p>
        </div>
      </div>
      <div class="header-right">
        <button class="btn-refresh-stats" @click="fetchData" :disabled="loading">
          <RefreshCw :size="18" :class="{ spin: loading }" />
          Segarkan Data
        </button>
      </div>
    </div>

    <!-- Main Statistics Grid -->
    <div class="metrics-grid">
      <div v-for="metric in metricCards" :key="metric.label" class="metric-card">
        <div class="metric-icon-wrap" :class="metric.color">
          <component :is="metric.icon" :size="20" />
        </div>
        <div class="metric-content">
          <span class="m-label">{{ metric.label }}</span>
          <h3 class="m-value">{{ metric.value }}</h3>
          <p class="m-sub">{{ metric.desc }}</p>
        </div>
      </div>
    </div>

    <!-- Charts & Analytics -->
    <div class="analytics-layout">
      <!-- Left: Revenue & Growth Charts -->
      <div class="analytics-main">
        <div class="chart-card">
          <div class="card-title-bar">
            <h3>Estimasi Arus Kas Bulanan</h3>
            <span class="badge-blue">Total {{ formatCurrency(stats.metrics.estimated_revenue_monthly || 0) }}</span>
          </div>
          <div class="chart-box">
            <!-- Simulated Bar Chart for Revenue per Plan -->
            <div class="revenue-bars">
              <div v-for="plan in stats.plan_distribution" :key="plan.plan" class="bar-item">
                <div class="bar-info">
                  <span class="plan-name">{{ plan.plan.toUpperCase() }}</span>
                  <span class="plan-price">{{ plan.plan === 'free' ? 'Rp 0' : (plan.plan === 'basic' ? formatCurrency(stats.metrics.pricing?.basic || 0) : formatCurrency(stats.metrics.pricing?.pro || 0)) }}</span>
                </div>
                <div class="bar-track">
                  <div 
                    class="bar-fill" 
                    :class="plan.plan"
                    :style="{ width: plan.count > 0 ? (plan.count / stats.metrics.total_tenants * 100) + '%' : '2%' }"
                  ></div>
                </div>
                <span class="plan-count">{{ plan.count }} Toko</span>
              </div>
            </div>
          </div>
        </div>

        <div class="chart-card mt-6">
          <div class="card-title-bar">
            <h3>Top Performing Stores</h3>
            <p class="subtitle text-sm text-gray-400">Toko dengan volume transaksi tertinggi</p>
          </div>
          <div class="top-stores-list">
            <div v-for="(store, idx) in stats.top_tenants" :key="store.id" class="store-performance-item">
              <div class="rank">{{ idx + 1 }}</div>
              <div class="store-main">
                <span class="s-name font-bold">{{ store.name }}</span>
                <span class="s-slug text-xs text-gray-500">{{ store.slug }}</span>
              </div>
              <div class="store-metric">
                <span class="s-val text-green-500 font-bold">{{ formatCurrency(store.sales_sum_total_amount || 0) }}</span>
                <span class="s-label text-xs">Omzet</span>
              </div>
            </div>
            <div v-if="!stats.top_tenants?.length" class="empty-list">
              Belum ada data transaksi dari toko.
            </div>
          </div>
        </div>
      </div>

      <!-- Right: Plan Distribution & Recent Activity -->
      <div class="analytics-side">
        <div class="chart-card full-h">
          <div class="card-title-bar">
            <h3>Penyebaran Paket</h3>
          </div>
          <!-- Donut simulated chart -->
          <div class="donut-wrap">
             <div class="donut-circle">
                <div class="donut-inner">
                   <h2 class="text-2xl font-bold">{{ stats.metrics.total_tenants }}</h2>
                   <span class="text-xs uppercase text-gray-400">Total Toko</span>
                </div>
             </div>
          </div>
          <div class="legend-list">
            <div v-for="plan in stats.plan_distribution" :key="plan.plan" class="legend-item-v2">
              <span class="l-dot" :class="plan.plan"></span>
              <span class="l-name">{{ plan.plan.toUpperCase() }}</span>
              <span class="l-val">{{ plan.count }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, reactive } from 'vue';
import api from '../../api';
import { 
  BarChart3, Store, Banknote, CreditCard, Users, RefreshCw, 
  TrendingUp, Activity, CheckCircle, Package
} from 'lucide-vue-next';

const loading = ref(false);
const stats = reactive({
  metrics: {},
  recent_tenants: [],
  growth: [],
  top_tenants: [],
  plan_distribution: []
});

const fetchData = async () => {
  loading.value = true;
  try {
    const res = await api.get('/admin/dashboard');
    if (res.data.success) {
      Object.assign(stats, res.data.data);
    }
  } catch (err) {
    console.error('Failed to fetch platform stats', err);
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchData();
});

const metricCards = computed(() => [
  { label: 'Total Omzet Global', value: formatCurrency(stats.metrics.total_sales || 0), desc: 'Seluruh transaksi platform', icon: Banknote, color: 'green' },
  { label: 'Total Transaksi', value: (stats.metrics.total_transactions || 0).toLocaleString(), desc: 'Pesanan terselesaikan', icon: Activity, color: 'blue' },
  { label: 'Total Pengguna', value: (stats.metrics.total_users || 0).toLocaleString(), desc: 'User aktif seluruh toko', icon: Users, color: 'purple' },
  { label: 'Estimasi Revenue', value: formatCurrency(stats.metrics.estimated_revenue_monthly || 0), desc: 'Pendapatan langganan', icon: CreditCard, color: 'orange' }
]);

const formatCurrency = (val) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
  }).format(val);
};
</script>

<style scoped>
.stats-view {
  padding: 24px;
  animation: fadeIn 0.4s ease;
}

.page-header-premium {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 30px;
}

.header-left { display: flex; align-items: center; gap: 16px; }
.header-icon-box {
  width: 50px; height: 50px; border-radius: 14px;
  background: linear-gradient(135deg, var(--accent), #fb923c);
  display: flex; align-items: center; justify-content: center;
  color: #fff; box-shadow: 0 10px 20px rgba(249,115,22,0.2);
}
.header-text h1 { font-size: 24px; font-weight: 800; margin: 0; }
.header-text p { font-size: 14px; color: var(--text-muted); margin: 4px 0 0; }

.btn-refresh-stats {
  height: 44px; padding: 0 20px; border-radius: 12px;
  border: 1px solid var(--border-color); background: var(--bg-card);
  display: flex; align-items: center; gap: 10px; font-weight: 700;
  cursor: pointer; transition: 0.2s; color: var(--text-primary);
}
.btn-refresh-stats:hover { background: var(--bg-primary); border-color: var(--accent); }
.spin { animation: spin 1s linear infinite; }

.metrics-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 20px;
  margin-bottom: 30px;
}

.metric-card {
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: 20px;
  padding: 24px;
  display: flex;
  gap: 16px;
  transition: transform 0.2s;
}
.metric-card:hover { transform: translateY(-4px); }

.metric-icon-wrap {
  width: 48px; height: 48px; border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
}
.metric-icon-wrap.green { background: rgba(34,197,94,0.1); color: #22c55e; }
.metric-icon-wrap.blue { background: rgba(59,130,246,0.1); color: #3b82f6; }
.metric-icon-wrap.purple { background: rgba(168,85,247,0.1); color: #a855f7; }
.metric-icon-wrap.orange { background: rgba(249,115,22,0.1); color: #f97316; }

.m-label { font-size: 13px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; }
.m-value { font-size: 24px; font-weight: 800; margin: 4px 0; color: var(--text-primary); }
.m-sub { font-size: 12px; color: var(--text-muted); }

.analytics-layout {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 24px;
}

.chart-card {
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: 24px;
  padding: 24px;
}

.full-h { height: 100%; min-height: 400px; }

.card-title-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}
.card-title-bar h3 { font-size: 18px; font-weight: 700; margin: 0; }

.badge-blue {
  background: #eff6ff; color: #3b82f6;
  padding: 4px 12px; border-radius: 100px; font-size: 12px; font-weight: 800;
}

/* Revenue Bars Styles */
.revenue-bars { display: flex; flex-direction: column; gap: 20px; }
.bar-item { display: flex; align-items: center; gap: 15px; }
.bar-info { width: 100px; display: flex; flex-direction: column; }
.plan-name { font-size: 12px; font-weight: 800; color: var(--text-primary); }
.plan-price { font-size: 10px; color: var(--text-muted); }
.bar-track { flex: 1; height: 12px; background: var(--bg-primary); border-radius: 100px; overflow: hidden; }
.bar-fill { height: 100%; border-radius: 100px; transition: 1s ease; }
.bar-fill.free { background: #94a3b8; }
.bar-fill.basic { background: #3b82f6; }
.bar-fill.pro { background: #f59e0b; }
.plan-count { font-size: 13px; font-weight: 700; width: 60px; text-align: right; }

/* Top Stores List */
.top-stores-list { display: flex; flex-direction: column; gap: 12px; }
.store-performance-item {
  display: flex; align-items: center; gap: 16px;
  padding: 16px; background: var(--bg-primary); border-radius: 16px;
  border: 1px solid var(--border-color);
}
.rank {
  width: 32px; height: 32px; border-radius: 80%;
  background: var(--bg-card); border: 1.5px solid var(--border-color);
  display: flex; align-items: center; justify-content: center;
  font-weight: 800; font-size: 14px; color: var(--accent);
}
.store-main { flex: 1; display: flex; flex-direction: column; }
.store-metric { display: flex; flex-direction: column; align-items: flex-end; }

/* Donut Simulation */
.donut-wrap { 
   padding: 40px 0; 
   display: flex; 
   justify-content: center; 
}
.donut-circle {
   width: 180px; height: 180px;
   border-radius: 50%;
   border: 20px solid #3b82f6; /* Simplified visualization */
   border-top-color: #f59e0b;
   border-right-color: #94a3b8;
   display: flex; align-items: center; justify-content: center;
}
.donut-inner { text-align: center; }

.legend-list { display: flex; flex-direction: column; gap: 12px; margin-top: 20px; }
.legend-item-v2 { display: flex; align-items: center; gap: 12px; font-weight: 700; font-size: 14px; }
.l-dot { width: 12px; height: 12px; border-radius: 4px; }
.l-dot.free { background: #94a3b8; }
.l-dot.basic { background: #3b82f6; }
.l-dot.pro { background: #f59e0b; }
.l-val { margin-left: auto; color: var(--text-muted); }

@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes spin { to { transform: rotate(360deg); } }

@media (max-width: 1200px) {
  .metrics-grid { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 1024px) {
  .analytics-layout { grid-template-columns: 1fr; gap: 20px; }
  .full-h { min-height: auto; }
  .donut-wrap { padding: 20px 0; }
}

@media (max-width: 768px) {
  .stats-view { padding: 16px 16px calc(16px + env(safe-area-inset-bottom)); }
  .page-header-premium { flex-direction: column; align-items: stretch; gap: 20px; margin-bottom: 24px; }
  .btn-refresh-stats { width: 100%; justify-content: center; height: 48px; order: 2; }
  .header-left { order: 1; }
  
  .metrics-grid { grid-template-columns: 1fr; gap: 12px; }
  .metric-card { padding: 16px; align-items: center; }
  .metric-icon-wrap { width: 44px; height: 44px; flex-shrink: 0; }
  .m-value { font-size: 20px; }
  
  .chart-card { padding: 16px; border-radius: 20px; }
  .card-title-bar { flex-direction: column; align-items: flex-start; gap: 8px; margin-bottom: 16px; }
  .badge-blue { align-self: flex-start; font-size: 11px; }

  .bar-item { gap: 10px; }
  .bar-info { width: 70px; }
  .plan-name { font-size: 11px; }
  .plan-count { font-size: 11px; width: 50px; }

  .store-performance-item { padding: 12px; gap: 12px; border-radius: 12px; }
  .rank { width: 28px; height: 28px; font-size: 12px; }
  .s-name { font-size: 13px; }
  .s-val { font-size: 13px; }
}

@media (max-width: 480px) {
  .stats-view { padding: 12px 12px calc(12px + env(safe-area-inset-bottom)); }
  .header-icon-box { width: 44px; height: 44px; }
  .header-text h1 { font-size: 18px; }
  .header-text p { font-size: 12px; }
  
  .m-value { font-size: 18px; }
  .m-label { font-size: 11px; }
  
  .donut-circle { width: 130px; height: 130px; border-width: 14px; }
  .donut-inner h2 { font-size: 18px; }
  
  .legend-list { display: grid; grid-template-columns: 1fr; gap: 8px; }
  .legend-item-v2 { font-size: 12px; }
}
</style>
