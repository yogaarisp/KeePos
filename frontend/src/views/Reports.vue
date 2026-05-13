<template>
  <div class="reports-container">
    <!-- Header Hero Section -->
    <div class="page-hero">
      <div class="hero-content">
        <div class="hero-icon-wrap">
          <BarChart2 :size="24" />
        </div>
        <div>
          <h1 class="hero-title">Laporan & Analitik</h1>
          <p class="hero-subtitle">Analisis performa bisnis, pantau stok kritis, dan tinjau metode pembayaran terpopuler.</p>
        </div>
      </div>
      <div class="hero-actions">
        <div class="premium-date-range">
          <div class="dr-input">
            <Calendar :size="14" />
            <input type="date" v-model="reportStore.filters.start_date" @change="refresh">
          </div>
          <span class="dr-divider">s/d</span>
          <div class="dr-input">
            <Calendar :size="14" />
            <input type="date" v-model="reportStore.filters.end_date" @change="refresh">
          </div>
        </div>
      </div>
    </div>

    <div v-if="reportStore.salesData" class="reports-content-grid">
      <!-- Summary Metrics Grid -->
      <section class="metrics-area">
        <div class="metrics-grid">
          <div v-for="(metric, idx) in summaryMetrics" :key="idx" class="metric-card" :style="{ animationDelay: (idx * 0.1) + 's' }">
            <div class="metric-icon" :class="metric.color">
              <component :is="metric.icon" :size="22" />
            </div>
            <div class="metric-info">
              <span class="metric-label">{{ metric.label }}</span>
              <h2 class="metric-value">{{ metric.value }}</h2>
            </div>
          </div>
        </div>
      </section>

      <!-- Main Analytical Area -->
      <div class="analytical-layout mt-6">
        <!-- Left: Top Selling Products -->
        <div class="analytical-col main">
          <div class="glass-panel">
            <div class="panel-top">
              <div class="panel-top-left">
                <div class="panel-pish info">
                  <Flame :size="18" />
                </div>
                <div>
                  <h3 class="panel-title">Menu Terlaris</h3>
                  <p class="panel-desc">Menu dengan performa penjualan tertinggi pada periode ini.</p>
                </div>
              </div>
              <div class="panel-badge highlight">Top Performance</div>
            </div>

            <div class="panel-body">
              <div class="table-scroll-wrap">
                <table class="analytical-table">
                  <thead>
                    <tr>
                      <th>Peringkat & Nama Menu</th>
                      <th class="text-center">Kuantitas</th>
                      <th class="text-right">Total Pendapatan</th>
                      <th class="text-right">Share</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(item, idx) in reportStore.salesData.top_products" :key="idx" class="a-row">
                      <td class="product-cell">
                        <div class="rank-badge" :class="'rank-' + (idx + 1)">#{{ idx + 1 }}</div>
                        <span class="p-name">{{ item.name }}</span>
                      </td>
                      <td class="text-center">
                        <span class="qty-pill">{{ item.total_qty }} <small>porsi</small></span>
                      </td>
                      <td class="text-right">
                        <div class="revenue-val">{{ formatCurrency(item.total_revenue) }}</div>
                      </td>
                      <td class="text-right">
                        <div class="share-cell">
                          <div class="share-bar">
                            <div class="share-fill" :style="{ width: ((item.total_revenue / reportStore.salesData.summary.total_sales) * 100) + '%' }"></div>
                          </div>
                          <span class="share-text">{{ Math.round((item.total_revenue / reportStore.salesData.summary.total_sales) * 100) }}%</span>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- Right: Payment & Method Breakdown -->
        <div class="analytical-col side">
          <div class="glass-panel methods-panel">
            <div class="panel-top">
              <h3 class="panel-title">Distribusi Pembayaran</h3>
            </div>
            <div class="panel-body">
              <div class="methods-pie-list">
                <div v-for="(data, method) in reportStore.salesData.sales_by_method" :key="method" class="m-method-item">
                  <div class="m-method-info">
                    <div class="m-method-name">
                      <component :is="method.toLowerCase() === 'cash' ? Banknote : Smartphone" :size="14" />
                      {{ method.toUpperCase() }}
                    </div>
                    <span class="m-method-val">{{ formatCurrency(data.total) }}</span>
                  </div>
                  <div class="m-progress-wrap">
                    <div class="m-progress-bg">
                      <div class="m-progress-fill" :style="{ width: calculatePercentage(data.total) + '%' }"></div>
                    </div>
                    <span class="m-percent">{{ Math.round(calculatePercentage(data.total)) }}%</span>
                  </div>
                </div>
              </div>
            </div>
            <div class="panel-footer-info">
               <Info :size="12" /> Total omzet periode ini berdasarkan metode bayar yang digunakan.
            </div>
          </div>
        </div>
      </div>

      <!-- Inventory Insights Area -->
      <section v-if="reportStore.stockData && reportStore.stockData.low_stock_items.length" class="inventory-insights-area mt-6">
        <div class="insight-notification-card">
          <div class="insight-top">
            <div class="insight-title-wrap">
              <div class="insight-icon alert">
                <AlertTriangle :size="20" />
              </div>
              <div>
                <h3 class="insight-title">Perhatian Stok Kritis!</h3>
                <p class="insight-desc">Ada {{ reportStore.stockData.low_stock_items.length }} bahan baku yang berada di bawah batas minimum.</p>
              </div>
            </div>
            <router-link to="/warehouse" class="btn-insight-action">
              Buka Gudang <ArrowRight :size="16" />
            </router-link>
          </div>

          <div class="insight-body">
            <div class="low-stock-grid">
              <div v-for="item in reportStore.stockData.low_stock_items" :key="item.id" class="stock-item-pill">
                <div class="si-info">
                  <span class="si-name">{{ item.name }}</span>
                  <span class="si-qty">{{ item.stock }} <small>{{ item.unit }}</small></span>
                </div>
                <div class="si-progress">
                  <div class="si-bar" :style="{ width: (item.stock / item.min_stock * 100) + '%' }"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>

    <!-- Loading State -->
    <div v-else class="analytical-loading">
      <div class="loading-visual">
        <div class="orb"></div>
        <div class="orb"></div>
        <div class="orb"></div>
      </div>
      <h3>Menyusun Analisa Data...</h3>
      <p>Kami sedang memproses laporan berdasarkan periode yang dipilih.</p>
    </div>
  </div>
</template>

<script setup>
import { onMounted, computed } from 'vue';
import { useReportStore } from '../stores/report';
import { 
  BarChart2, Calendar, TrendingUp, ShoppingCart, Activity, 
  AlertTriangle, ArrowRight, Flame, Info, Banknote, 
  Smartphone, Wallet 
} from 'lucide-vue-next';

const reportStore = useReportStore();

const summaryMetrics = computed(() => {
  if (!reportStore.salesData) return [];
  return [
    { label: 'Total Penjualan Omzet', value: formatCurrency(reportStore.salesData.summary.total_sales), icon: TrendingUp, color: 'orange' },
    { label: 'Cakupan Transaksi', value: reportStore.salesData.summary.total_transactions + ' Transaksi', icon: ShoppingCart, color: 'blue' },
    { label: 'Average Value (AOV)', value: formatCurrency(reportStore.salesData.summary.avg_transaction), icon: Activity, color: 'green' }
  ];
});

onMounted(() => {
  reportStore.fetchSalesSummary();
  reportStore.fetchStockSummary();
});

const refresh = () => reportStore.fetchSalesSummary();

const formatCurrency = (value) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
  }).format(value);
};

const calculatePercentage = (value) => {
  if (!reportStore.salesData.summary.total_sales) return 0;
  return (value / reportStore.salesData.summary.total_sales) * 100;
};
</script>

<style scoped>
.reports-container { padding: 0; animation: fadeIn 0.4s ease; }

/* ── Hero ── */
.page-hero {
  display: flex; align-items: center; justify-content: space-between;
  background: linear-gradient(135deg, rgba(79,70,229,0.08) 0%, rgba(79,70,229,0.02) 100%);
  border: 1px solid rgba(79,70,229,0.1); border-radius: 20px;
  padding: 28px 32px; margin-bottom: 24px;
}
.hero-content { display: flex; align-items: center; gap: 18px; }
.hero-icon-wrap {
  width: 52px; height: 52px; border-radius: 16px;
  background: linear-gradient(135deg, #4f46e5, #6366f1);
  display: flex; align-items: center; justify-content: center;
  color: #fff; box-shadow: 0 8px 24px rgba(79, 70, 229, 0.25);
}
.hero-title { font-size: 22px; font-weight: 600; color: var(--text-primary); margin-bottom: 4px; }
.hero-subtitle { font-size: 14px; color: var(--text-secondary); font-weight: 500; }

.premium-date-range {
  display: flex; align-items: center; gap: 10px;
  background: var(--bg-card); padding: 8px 12px;
  border-radius: 14px; border: 1px solid var(--border-color);
  box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
}
.dr-input { display: flex; align-items: center; gap: 8px; color: var(--text-muted); }
.dr-input input {
  background: transparent; border: none; color: var(--text-primary);
  font-size: 13px; font-weight: 700; outline: none; cursor: pointer;
}
.dr-divider { font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; }

/* ── Metrics Grid ── */
.metrics-grid {
  display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;
}
.metric-card {
  background: var(--bg-card); border: 1px solid var(--border-color);
  border-radius: 24px; padding: 24px; display: flex; align-items: center; gap: 18px;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  animation: slideInUp 0.6s ease both;
}
.metric-card:hover { transform: translateY(-4px); border-color: var(--accent); box-shadow: 0 10px 25px -10px rgba(0,0,0,0.1); }

.metric-icon {
  width: 54px; height: 54px; border-radius: 16px;
  display: flex; align-items: center; justify-content: center;
}
.metric-icon.orange { background: rgba(249,115,22,0.1); color: var(--accent); }
.metric-icon.blue { background: rgba(14,165,233,0.1); color: var(--info); }
.metric-icon.green { background: rgba(34,197,94,0.1); color: var(--success); }

.metric-label { display: block; font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
.metric-value { font-size: 22px; font-weight: 700; color: var(--text-primary); }

/* ── Analytical Layout ── */
.analytical-layout { display: grid; grid-template-columns: 1.6fr 1fr; gap: 24px; }

.glass-panel {
  background: var(--bg-card); border: 1px solid var(--border-color);
  border-radius: 28px; overflow: hidden; display: flex; flex-direction: column;
}
.panel-top { padding: 24px 28px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border-color); }
.panel-top-left { display: flex; align-items: center; gap: 16px; }
.panel-pish { width: 40px; height: 40px; border-radius: 12px; display: flex; align-items: center; justify-content: center; }
.panel-pish.info { background: rgba(249,115,22,0.1); color: var(--accent); }

.panel-title { font-size: 16px; font-weight: 600; color: var(--text-primary); }
.panel-desc { font-size: 12px; color: var(--text-muted); font-weight: 500; }
.panel-badge { font-size: 10px; font-weight: 600; padding: 4px 10px; border-radius: 100px; text-transform: uppercase; }
.panel-badge.highlight { background: var(--accent-light); color: var(--accent); border: 1px solid rgba(249,115,22,0.2); }

.panel-body { padding: 24px; }

/* ── Table Analytical ── */
.analytical-table { width: 100%; border-collapse: collapse; }
.analytical-table th { text-align: left; padding: 0 16px 16px; font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.8px; }
.a-row { border-bottom: 1px solid var(--border-color); transition: 0.2s; }
.a-row:last-child { border-bottom: none; }
.a-row:hover { background: var(--bg-primary); }
.a-row td { padding: 14px 16px; }

.product-cell { display: flex; align-items: center; gap: 14px; }
.rank-badge {
  width: 28px; height: 28px; border-radius: 8px; 
  display: flex; align-items: center; justify-content: center;
  font-size: 12px; font-weight: 600; border: 1px solid var(--border-color);
}
.rank-1 { background: #fef3c7; color: #92400e; border-color: #f59e0b; }
.rank-2 { background: #f1f5f9; color: #475569; }
.rank-3 { background: #ffedd5; color: #9a3412; }

.p-name { font-size: 14px; font-weight: 700; color: var(--text-primary); }
.qty-pill { font-size: 13px; font-weight: 700; color: var(--text-secondary); background: var(--bg-primary); padding: 4px 12px; border-radius: 8px; }
.qty-pill small { color: var(--text-muted); font-weight: 500; }
.revenue-val { font-size: 14px; font-weight: 600; color: var(--accent); }

.share-cell { display: flex; align-items: center; gap: 10px; min-width: 120px; }
.share-bar { flex: 1; height: 6px; background: var(--bg-primary); border-radius: 10px; overflow: hidden; }
.share-fill { height: 100%; background: var(--accent); border-radius: 10px; }
.share-text { font-size: 11px; font-weight: 600; color: var(--text-muted); min-width: 30px; }

/* ── Methods Side Panel ── */
.methods-pie-list { display: flex; flex-direction: column; gap: 20px; }
.m-method-item { display: flex; flex-direction: column; gap: 8px; }
.m-method-info { display: flex; justify-content: space-between; align-items: center; }
.m-method-name { display: flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 600; color: var(--text-primary); }
.m-method-val { font-size: 13px; font-weight: 700; color: var(--text-secondary); }
.m-progress-wrap { display: flex; align-items: center; gap: 12px; }
.m-progress-bg { flex: 1; height: 10px; background: var(--bg-primary); border-radius: 20px; overflow: hidden; }
.m-progress-fill { height: 100%; background: var(--info); border-radius: 20px; }
.m-percent { font-size: 11px; font-weight: 600; color: var(--info); width: 34px; }

.panel-footer-info { padding: 16px 24px; font-size: 11px; color: var(--text-muted); display: flex; gap: 8px; align-items: center; border-top: 1px solid var(--border-color); }

/* ── Inventory Insights ── */
.insight-notification-card {
  background: linear-gradient(to right, #fef2f2, #fff);
  border: 1px solid #fecaca; border-radius: 28px; padding: 28px;
}
:root:not(.light) .insight-notification-card { background: linear-gradient(to right, rgba(239,68,68,0.1), rgba(239,68,68,0.02)); border-color: rgba(239,68,68,0.2); }

.insight-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; }
.insight-title-wrap { display: flex; align-items: center; gap: 16px; }
.insight-icon.alert { width: 48px; height: 48px; border-radius: 14px; background: #fee2e2; color: #ef4444; display: flex; align-items: center; justify-content: center; }
.insight-title { font-size: 18px; font-weight: 600; color: #b91c1c; margin-bottom: 2px; }
:root:not(.light) .insight-title { color: #f87171; }
.insight-desc { font-size: 13px; color: #7f1d1d; font-weight: 500; opacity: 0.8; }
:root:not(.light) .insight-desc { color: #fca5a5; }

.btn-insight-action {
  display: flex; align-items: center; gap: 8px;
  background: #ef4444; color: #fff; border: none; padding: 10px 20px;
  border-radius: 12px; font-size: 13px; font-weight: 700; cursor: pointer; text-decoration: none;
  transition: 0.2s;
}
.btn-insight-action:hover { background: #dc2626; transform: translateX(4px); }

.low-stock-grid { display: flex; gap: 16px; overflow-x: auto; padding-bottom: 8px; }
.stock-item-pill {
  min-width: 180px; background: var(--bg-card); border: 1px solid var(--border-color);
  padding: 16px; border-radius: 18px; display: flex; flex-direction: column; gap: 10px;
}
.si-info { display: flex; justify-content: space-between; align-items: center; }
.si-name { font-size: 13px; font-weight: 700; color: var(--text-primary); }
.si-qty { font-size: 12px; font-weight: 600; color: #ef4444; }
.si-qty small { font-size: 10px; opacity: 0.7; }
.si-progress { height: 6px; background: var(--bg-primary); border-radius: 10px; overflow: hidden; }
.si-bar { height: 100%; background: #ef4444; border-radius: 10px; }

/* ── Loading ── */
.analytical-loading { padding: 100px 20px; text-align: center; }
.loading-visual { display: flex; justify-content: center; gap: 12px; margin-bottom: 24px; }
.orb { width: 14px; height: 14px; border-radius: 50%; background: var(--accent); animation: bounce 0.6s infinite alternate; }
.orb:nth-child(2) { animation-delay: 0.2s; background: var(--info); }
.orb:nth-child(3) { animation-delay: 0.4s; background: var(--success); }

@keyframes bounce { to { transform: translateY(-15px); opacity: 0.6; } }
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes slideInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
@keyframes spin { to { transform: rotate(360deg); } }

/* ── Responsive ── */
@media (max-width: 1200px) {
  .metrics-grid { grid-template-columns: 1fr; }
  .analytical-layout { grid-template-columns: 1fr; }
  .inventory-summary-grid { grid-template-columns: 1fr; }
}

@media (max-width: 768px) {
  .page-hero { flex-direction: column; gap: 16px; padding: 16px; margin-bottom: 16px; text-align: center; }
  .hero-content { flex-direction: row; text-align: left; gap: 12px; }
  .hero-icon-wrap { width: 36px; height: 36px; border-radius: 10px; }
  .hero-icon-wrap svg { width: 18px; height: 18px; }
  .hero-title { font-size: 16px; }
  .hero-subtitle { display: none; }

  .premium-date-range { width: 100%; justify-content: center; padding: 6px 10px; border-radius: 12px; }
  .dr-input input { font-size: 12px; }
  .dr-divider { font-size: 10px; }

  .metrics-grid { gap: 12px; }
  .metric-card { padding: 16px; border-radius: 16px; gap: 14px; }
  .metric-icon { width: 40px; height: 40px; border-radius: 12px; }
  .metric-icon svg { width: 18px; height: 18px; }
  .metric-value { font-size: 18px; }
  .metric-label { font-size: 10px; }

  .glass-panel { border-radius: 20px; }
  .panel-top { padding: 16px; flex-direction: column; gap: 10px; align-items: flex-start; }
  .panel-body { padding: 16px; }
  .panel-badge { display: none; }

  .analytical-table th:nth-child(4), .a-row td:nth-child(4) { display: none; }
  .analytical-table th { padding: 0 12px 12px; font-size: 10px; }
  .a-row td { padding: 12px; }
  .p-name { font-size: 13px; }
  .rank-badge { width: 24px; height: 24px; font-size: 11px; }

  .insight-notification-card { padding: 20px; border-radius: 20px; }
  .insight-top { flex-direction: column; gap: 12px; align-items: flex-start; }
  .insight-icon.alert { width: 36px; height: 36px; border-radius: 10px; }
  .insight-title { font-size: 15px; }
  .low-stock-grid { gap: 10px; }
  .stock-item-pill { min-width: 150px; padding: 12px; border-radius: 14px; }

  .export-btn-group { width: 100%; }
  .btn-export { flex: 1; justify-content: center; }
  .inventory-filter-bar { padding: 16px; }
  .filter-row { gap: 8px; }
  .filter-item { flex: 1; min-width: 120px; }
  
  /* Mobile card layout for inventory table */
  .inventory-table thead { display: none; }
  .inventory-table .table-row {
    display: block; padding: 16px; margin-bottom: 12px;
    background: var(--bg-primary); border-radius: 16px;
    border: 1px solid var(--border-color);
  }
  .inventory-table .table-row td {
    display: flex; justify-content: space-between; align-items: center;
    padding: 8px 0; border-bottom: 1px solid var(--border-color);
  }
  .inventory-table .table-row td:last-child { border-bottom: none; }
  .inventory-table .table-row td::before {
    content: attr(data-label); font-size: 10px; font-weight: 600;
    color: var(--text-muted); text-transform: uppercase;
  }
  .inventory-table .table-row td.text-right { justify-content: space-between; }

  .mt-6 { margin-top: 16px; }
}

@media (max-width: 480px) {
  .hero-title { font-size: 14px; }
  .metric-value { font-size: 16px; }
  .analytical-table th:nth-child(3), .a-row td:nth-child(3) { display: none; }
}

.mt-6 { margin-top: 24px; }
</style>
