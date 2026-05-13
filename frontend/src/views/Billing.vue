<template>
  <div class="billing-page">
    <!-- Hero Header -->
    <div class="page-hero">
      <div class="hero-content">
        <div class="hero-icon-wrap">
          <CreditCard :size="24" />
        </div>
        <div>
          <h1 class="hero-title">Langganan & Billing</h1>
          <p class="hero-subtitle">Kelola paket langganan dan riwayat pembayaran Anda.</p>
        </div>
      </div>
    </div>

    <!-- Current Subscription Status -->
    <div class="status-card">
      <div class="status-row">
        <div class="status-block">
          <span class="status-label">PAKET SAAT INI</span>
          <div class="status-plan-row">
            <h2 class="plan-name" :class="tenant?.plan">{{ (tenant?.plan === 'free' ? 'TRIAL' : (tenant?.plan || 'TRIAL')).toUpperCase() }}</h2>
            <div class="status-tag" :class="{ active: tenant?.is_active && !isTrial }">
              <span class="dot-pulse"></span>
              {{ tenant?.is_active && !isTrial ? 'Berlangganan' : 'Masa Trial' }}
            </div>
          </div>
        </div>

        <div class="status-sep"></div>

        <div class="status-block">
          <div class="info-row">
            <div class="info-icon-sm"><Calendar :size="16" /></div>
            <div>
              <span class="status-label">{{ isTrial ? 'TRIAL BERAKHIR' : 'AKTIF SAMPAI' }}</span>
              <span class="info-val">{{ formatDate(isTrial ? tenant?.trial_ends_at : tenant?.subscription_ends_at) }}</span>
            </div>
          </div>
        </div>

        <div v-if="isNearingEnd" class="expiry-warning">
          <AlertTriangle :size="14" />
          <span>Berakhir dalam <strong>{{ remainingDays }} hari</strong></span>
          <button class="btn-renew-sm" @click="scrollToPlans">Perpanjang</button>
        </div>
      </div>
    </div>

    <!-- Plans Section -->
    <div id="pricing-section" class="section-block">
      <div class="section-head">
        <h2 class="section-title">Pilih Paket Langganan</h2>
        <p class="section-desc">Tingkatkan efisiensi bisnis Anda dengan paket yang tepat.</p>
      </div>

      <div class="plans-grid">
        <!-- Error State -->
        <div v-if="fetchError" class="error-state">
          <AlertTriangle :size="32" class="error-icon" />
          <h4>Gagal Memuat Data</h4>
          <p>{{ fetchError }}</p>
          <button class="btn-retry" @click="fetchData">Coba Lagi</button>
        </div>

        <!-- Loading Skeletons -->
        <template v-else-if="Object.keys(availablePlans).length === 0">
          <div v-for="i in 3" :key="i" class="plan-skeleton"></div>
        </template>

        <!-- Plan Cards -->
        <div
          v-for="(p, key) in availablePlans"
          :key="key"
          class="plan-card"
          :class="{
            'is-active': tenant?.plan === key,
            'is-featured': key === 'pro'
          }"
        >
          <div v-if="key === 'pro'" class="featured-badge">REKOMENDASI</div>

          <div class="pc-header">
            <div class="pc-icon">
              <Zap v-if="key === 'pro'" :size="20" />
              <Layers v-else-if="key === 'basic'" :size="20" />
              <Package v-else :size="20" />
            </div>
            <div>
              <h3 class="pc-name">{{ p.name === 'Free' ? 'TRIAL' : p.name }}</h3>
              <span class="pc-tagline">{{ key === 'pro' ? 'Solusi Bisnis Lengkap' : (key === 'basic' ? 'Esensial untuk Toko' : 'Masa Percobaan') }}</span>
            </div>
          </div>

          <div class="pc-price">
            <div class="price-strikethrough" v-if="key === 'free'">Rp 50.000</div>
            <div class="main-price">
              <span class="price-currency">Rp</span>
              <span class="price-amount">{{ formatNumber(p.price) }}</span>
              <span class="price-period">{{ key === 'free' ? '/ akun' : '/ bln' }}</span>
            </div>
          </div>

          <div class="pc-features">
            <div v-for="f in p.features" :key="f" class="feature-item">
              <div class="check-icon"><Check :size="10" /></div>
              <span>{{ f }}</span>
            </div>
          </div>

          <div class="pc-footer">
            <button
              v-if="key !== 'free'"
              class="btn-plan"
              :class="key === 'pro' ? 'btn-primary-glow' : 'btn-outline-plan'"
              :disabled="loadingCheckout"
              @click="initiateCheckout(key)"
            >
              <div v-if="loadingCheckout && selectedPlan === key" class="mini-spin"></div>
              <span v-else>{{ tenant?.plan === key ? 'Perpanjang' : 'Pilih Paket' }}</span>
            </button>
            <div v-else class="free-label">Masa Uji Coba (Trial)</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Invoice History -->
    <div class="section-block">
      <div class="section-head">
        <h2 class="section-title">Riwayat Pembayaran</h2>
        <p class="section-desc">Pantau seluruh invoice dan status pembayaran Anda di sini.</p>
      </div>

      <div class="invoice-card">
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>No. Invoice</th>
                <th>Paket</th>
                <th>Durasi</th>
                <th>Jumlah</th>
                <th>Status</th>
                <th>Tanggal</th>
                <th class="text-right">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="inv in invoices" :key="inv.id">
                <td class="td-mono">#{{ inv.external_id ? inv.external_id.split('-').pop() : inv.invoice_number }}</td>
                <td>
                  <div class="plan-badge" :class="inv.plan">{{ inv.plan.toUpperCase() }}</div>
                </td>
                <td>{{ inv.months || 1 }} Bulan</td>
                <td class="td-bold">Rp {{ formatNumber(inv.amount) }}</td>
                <td>
                  <div class="status-pill" :class="inv.status">
                    <span class="pill-dot"></span>
                    {{ formatStatus(inv.status) }}
                  </div>
                </td>
                <td class="td-muted">{{ formatDate(inv.paid_at || inv.created_at) }}</td>
                <td class="text-right">
                  <a v-if="inv.status === 'pending'" :href="inv.payment_url" target="_blank" class="btn-pay-sm">
                    <CreditCard :size="12" /> Bayar
                  </a>
                  <span v-else class="td-muted text-xs">Selesai</span>
                </td>
              </tr>
              <tr v-if="invoices.length === 0">
                <td colspan="7" class="empty-cell">
                  <div class="empty-state">
                    <ClipboardList :size="32" class="empty-icon" />
                    <h4>Belum Ada Transaksi</h4>
                    <p>Riwayat pembayaran akan muncul setelah Anda melakukan pembelian paket.</p>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Checkout Modal -->
    <Transition name="modal-fade">
      <div v-if="showCheckoutModal" class="modal-overlay" @click.self="showCheckoutModal = false">
        <div class="modal-box">
          <header class="modal-header">
            <div class="modal-header-left">
              <div class="modal-icon"><Zap :size="18" /></div>
              <h2>Konfigurasi Upgrade</h2>
            </div>
            <button class="btn-close" @click="showCheckoutModal = false"><X :size="18" /></button>
          </header>

          <div class="modal-body">
            <div class="plan-brief">
              <span class="brief-label">PAKET TERPILIH</span>
              <div class="brief-row">
                <h3 :class="selectedPlan">{{ selectedPlan.toUpperCase() }}</h3>
                <span class="brief-price">Rp {{ formatNumber(availablePlans[selectedPlan]?.price) }} / bln</span>
              </div>
            </div>

            <div class="duration-section">
              <span class="brief-label">PILIH DURASI</span>
              <div class="duration-grid">
                <button
                  v-for="m in [1, 3, 6, 12]"
                  :key="m"
                  class="dur-btn"
                  :class="{ active: selectedMonths === m }"
                  @click="selectedMonths = m"
                >
                  <span class="dur-num">{{ m }}</span>
                  <span class="dur-label">Bulan</span>
                  <div class="dur-save" v-if="m >= 6">{{ m === 12 ? '-20%' : '-10%' }}</div>
                </button>
              </div>
            </div>

            <div class="price-summary">
              <div class="sum-row">
                <span>Subtotal</span>
                <span>Rp {{ formatNumber(availablePlans[selectedPlan]?.price * selectedMonths) }}</span>
              </div>
              <div class="sum-row discount" v-if="selectedMonths >= 6">
                <span>Potongan Durasi ({{ selectedMonths === 12 ? '20%' : '10%' }})</span>
                <span>- Rp {{ formatNumber((availablePlans[selectedPlan]?.price * selectedMonths) - totalPrice) }}</span>
              </div>
              <div class="sum-line"></div>
              <div class="sum-row total">
                <span>Total Pembayaran</span>
                <span class="total-amount">Rp {{ formatNumber(totalPrice) }}</span>
              </div>
            </div>

            <div class="info-note">
              <Info :size="14" />
              <p>Anda akan diarahkan ke Midtrans Secure Checkout untuk menyelesaikan pembayaran.</p>
            </div>
          </div>

          <footer class="modal-footer">
            <button class="btn-cancel" @click="showCheckoutModal = false">Kembali</button>
            <button class="btn-confirm" @click="processPayment" :disabled="loadingCheckout">
              <RefreshCw v-if="loadingCheckout" :size="16" class="spin" />
              <span v-else>Proses Pembayaran</span>
            </button>
          </footer>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useAuthStore } from '../stores/auth';
import api from '../api';
import {
  CreditCard, Check, AlertTriangle, X, RefreshCw,
  Zap, Layers, Package, Calendar, ClipboardList, Info
} from 'lucide-vue-next';
import { showSuccess, showError } from '../utils/swal';

const auth = useAuthStore();
const tenant = ref(auth.user?.tenant || {});
const invoices = ref([]);
const availablePlans = ref({});
const loadingCheckout = ref(false);
const fetchError = ref(null);

const showCheckoutModal = ref(false);
const selectedPlan = ref('basic');
const selectedMonths = ref(1);

const isTrial = computed(() => {
  if (!tenant.value || !tenant.value.plan) return false;
  return tenant.value.plan === 'free' || (!tenant.value.subscription_ends_at && tenant.value.trial_ends_at);
});

const remainingDays = computed(() => {
  if (!tenant.value) return 999;
  const targetDate = isTrial.value ? tenant.value.trial_ends_at : tenant.value.subscription_ends_at;
  if (!targetDate) return 999;
  const diffTime = new Date(targetDate) - new Date();
  const days = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
  return days > 0 ? days : 0;
});

const isNearingEnd = computed(() => remainingDays.value >= 0 && remainingDays.value <= 7);

const totalPrice = computed(() => {
  const price = availablePlans.value[selectedPlan.value]?.price || 0;
  let multiplier = 1;
  if (selectedMonths.value === 6) multiplier = 0.9;
  if (selectedMonths.value === 12) multiplier = 0.8;
  return (price * selectedMonths.value) * multiplier;
});

const fetchData = async () => {
  try {
    const [plansRes, invoicesRes, meRes] = await Promise.all([
      api.get('/subscriptions/plans/public'),
      api.get('/subscriptions/invoices'),
      api.get('/me')
    ]);

    availablePlans.value = plansRes.data.data;
    invoices.value = invoicesRes.data.data;
    tenant.value = meRes.data.tenant || {};
    fetchError.value = null;
  } catch (err) {
    console.error('Failed to fetch billing data', err);
    fetchError.value = err.response?.data?.message || 'Gagal mengambil data paket langganan. Pastikan koneksi internet Anda stabil.';
    
    // If it's a 401, the interceptor will handle redirect
  }
};

onMounted(fetchData);

const scrollToPlans = () => {
  const el = document.getElementById('pricing-section');
  if (el) el.scrollIntoView({ behavior: 'smooth' });
};

const initiateCheckout = (plan) => {
  selectedPlan.value = plan;
  showCheckoutModal.value = true;
};

const processPayment = async () => {
  loadingCheckout.value = true;
  try {
    const res = await api.post('/subscriptions/checkout', {
      plan: selectedPlan.value,
      months: selectedMonths.value
    });

    if (res.data.success) {
      showCheckoutModal.value = false;
      showSuccess('Invoice berhasil dibuat. Anda akan diarahkan ke gerbang pembayaran.');
      setTimeout(() => {
        window.open(res.data.data.payment_url, '_blank');
        fetchData();
      }, 1200);
    }
  } catch (err) {
    showError(err.response?.data?.message || 'Gagal membuat invoice.');
  } finally {
    loadingCheckout.value = false;
  }
};

const formatNumber = (num) => {
  if (num === null || num === undefined) return '0';
  return new Intl.NumberFormat('id-ID').format(Math.round(num));
};

const formatDate = (date) => {
  if (!date) return 'Tanpa Batas';
  return new Date(date).toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
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
/* ── Page ── */
.billing-page {
  min-height: 100vh;
  padding: 0;
  color: var(--text-primary);
  font-family: 'Plus Jakarta Sans', sans-serif;
  -webkit-font-smoothing: antialiased;
  animation: fadeIn 0.4s ease;
}

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
.hero-title { font-size: 22px; font-weight: 600; color: var(--text-primary); margin: 0 0 4px; }
.hero-subtitle { font-size: 14px; color: var(--text-secondary); font-weight: 500; margin: 0; }

/* ── Status Card ── */
.status-card {
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: 20px;
  padding: 24px 28px;
  margin-bottom: 24px;
  box-shadow: var(--shadow);
}
.status-row {
  display: flex; align-items: center; gap: 28px; flex-wrap: wrap;
}
.status-block { display: flex; flex-direction: column; gap: 8px; }
.status-label {
  font-size: 11px; font-weight: 800; color: var(--text-muted);
  letter-spacing: 1.5px; text-transform: uppercase;
}
.status-plan-row { display: flex; align-items: center; gap: 14px; }
.plan-name { font-size: 28px; font-weight: 900; letter-spacing: -1px; margin: 0; }
.plan-name.free { color: var(--text-muted); }
.plan-name.basic { color: #2563eb; }
.plan-name.pro {
  background: linear-gradient(to right, #f97316, #ec4899);
  -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;
}
.status-tag {
  display: flex; align-items: center; gap: 6px;
  padding: 5px 12px; border-radius: 50px;
  background: var(--bg-primary); border: 1px solid var(--border-color);
  font-size: 12px; font-weight: 700; color: var(--text-secondary);
}
.status-tag.active { border-color: #22c55e; color: #166534; }
.dot-pulse { width: 6px; height: 6px; border-radius: 50%; background: #94a3b8; }
.status-tag.active .dot-pulse { background: #22c55e; animation: pulse 2s infinite; }
@keyframes pulse { 0%,100% { opacity: 1; } 50% { opacity: 0.4; } }

.status-sep { width: 1px; height: 40px; background: var(--border-color); }

.info-row { display: flex; align-items: center; gap: 12px; }
.info-icon-sm {
  width: 36px; height: 36px; border-radius: 10px;
  background: var(--bg-primary); display: flex; align-items: center; justify-content: center;
  color: var(--text-muted);
}
.info-val { font-size: 14px; font-weight: 800; color: var(--text-primary); display: block; margin-top: 2px; }

.expiry-warning {
  padding: 10px 16px; border-radius: 12px;
  background: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.15);
  color: #b91c1c; display: flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 600;
  margin-left: auto;
}
.btn-renew-sm {
  padding: 4px 10px; border-radius: 6px; border: none;
  background: #b91c1c; color: #fff; font-size: 10px; font-weight: 800; cursor: pointer;
}

/* ── Section Block ── */
.section-block { margin-bottom: 24px; }
.section-head { margin-bottom: 20px; }
.section-title {
  font-size: 18px; font-weight: 700; color: var(--text-primary); margin: 0 0 4px;
  display: flex; align-items: center; gap: 14px;
}
.section-title::after { content: ''; flex: 1; height: 1px; background: var(--border-color); }
.section-desc { font-size: 13px; color: var(--text-muted); margin: 0; }

/* ── Plans Grid ── */
.plans-grid {
  display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 20px;
}
.plan-card {
  background: var(--bg-card); border: 1.5px solid var(--border-color);
  border-radius: 20px; padding: 28px;
  position: relative; transition: all 0.3s ease;
  display: flex; flex-direction: column;
}
.plan-card:hover { transform: translateY(-4px); border-color: var(--accent); box-shadow: 0 12px 30px -8px rgba(249,115,22,0.1); }
.plan-card.is-featured {
  border: 2px solid #fdba74;
  background: linear-gradient(180deg, var(--bg-card) 0%, rgba(249,115,22,0.03) 100%);
}

.featured-badge {
  position: absolute; top: -10px; left: 50%; transform: translateX(-50%);
  background: #f97316; color: #fff; font-size: 10px; font-weight: 900;
  padding: 4px 14px; border-radius: 50px; letter-spacing: 0.8px;
}

.pc-header { display: flex; align-items: center; gap: 14px; margin-bottom: 20px; }
.pc-icon {
  width: 44px; height: 44px; border-radius: 14px;
  background: var(--bg-primary); display: flex; align-items: center; justify-content: center;
  color: #f97316;
}
.pc-name { margin: 0; font-size: 17px; font-weight: 800; }
.pc-tagline { font-size: 12px; color: var(--text-muted); font-weight: 600; }

.pc-price { margin-bottom: 24px; position: relative; }
.price-strikethrough { 
  font-size: 13px; text-decoration: line-through; color: var(--text-muted); 
  opacity: 0.7; margin-bottom: -4px; font-weight: 600;
}
.main-price { display: flex; align-items: baseline; gap: 4px; }
.price-currency { font-size: 14px; font-weight: 800; color: var(--text-muted); }
.price-amount { font-size: 32px; font-weight: 900; letter-spacing: -1px; color: var(--text-primary); }
.price-period { font-size: 13px; color: var(--text-muted); font-weight: 600; }

.pc-features { flex: 1; display: flex; flex-direction: column; gap: 10px; margin-bottom: 24px; }
.feature-item { display: flex; align-items: center; gap: 10px; font-size: 13px; color: var(--text-secondary); font-weight: 500; }
.check-icon {
  width: 18px; height: 18px; border-radius: 50%;
  background: rgba(34, 197, 94, 0.1); color: #166534;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}

.btn-plan {
  width: 100%; height: 44px; border-radius: 14px; font-weight: 700; font-size: 13px;
  cursor: pointer; transition: 0.3s; display: flex; align-items: center; justify-content: center;
}
.btn-primary-glow {
  background: linear-gradient(135deg, #f97316, #ea580c);
  color: #fff; border: none; box-shadow: 0 8px 20px -4px rgba(234, 88, 12, 0.3);
}
.btn-primary-glow:hover { transform: scale(1.02); box-shadow: 0 10px 24px -4px rgba(234, 88, 12, 0.4); }
.btn-outline-plan {
  background: transparent; border: 1.5px solid var(--border-color); color: var(--text-primary);
}
.btn-outline-plan:hover { border-color: #f97316; color: #f97316; }
.free-label { text-align: center; font-size: 12px; font-weight: 700; color: var(--text-muted); font-style: italic; }

/* ── Invoice Table ── */
.invoice-card {
  background: var(--bg-card); border-radius: 20px; border: 1px solid var(--border-color);
  overflow: hidden; box-shadow: var(--shadow);
}
.table-responsive { overflow-x: auto; }
.data-table { width: 100%; border-collapse: collapse; }
.data-table th {
  padding: 14px 20px; text-align: left; font-size: 11px; font-weight: 800;
  background: var(--bg-primary); color: var(--text-muted); text-transform: uppercase;
  letter-spacing: 0.8px; border-bottom: 1px solid var(--border-color);
}
.data-table td {
  padding: 14px 20px; border-bottom: 1px solid var(--border-color);
  vertical-align: middle; font-size: 13px;
}

.td-mono { font-family: 'JetBrains Mono', monospace; font-weight: 800; font-size: 12px; color: #f97316; }
.td-bold { font-weight: 800; color: var(--text-primary); }
.td-muted { color: var(--text-muted); font-size: 12px; }

.plan-badge {
  display: inline-block; padding: 3px 10px; border-radius: 6px;
  font-size: 10px; font-weight: 900;
}
.plan-badge.free { background: #f1f5f9; color: #64748b; }
.plan-badge.basic { background: rgba(37, 99, 235, 0.1); color: #2563eb; }
.plan-badge.pro { background: rgba(234, 88, 12, 0.1); color: #ea580c; }

.status-pill {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 4px 10px; border-radius: 8px; font-size: 11px; font-weight: 800;
}
.pill-dot { width: 5px; height: 5px; border-radius: 50%; background: currentColor; }
.status-pill.settled, .status-pill.paid { background: rgba(34, 197, 94, 0.1); color: #166534; }
.status-pill.pending { background: rgba(234, 179, 8, 0.1); color: #854d0e; }
.status-pill.expired { background: rgba(239, 68, 68, 0.1); color: #991b1b; }

.btn-pay-sm {
  display: inline-flex; align-items: center; gap: 6px;
  background: #f97316; color: #fff; padding: 6px 12px; border-radius: 8px;
  text-decoration: none; font-size: 11px; font-weight: 800;
}

.empty-cell { padding: 48px 0 !important; }
.empty-state { display: flex; flex-direction: column; align-items: center; color: var(--text-muted); }
.empty-icon { opacity: 0.3; margin-bottom: 12px; }
.empty-state h4 { margin: 0; font-size: 15px; font-weight: 800; color: var(--text-secondary); }
.empty-state p { margin: 6px 0 0; font-size: 12px; max-width: 260px; text-align: center; }

/* ── Checkout Modal ── */
.modal-overlay {
  position: fixed; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(8px);
  display: flex; align-items: center; justify-content: center; z-index: 2000; padding: 20px;
}
.modal-box {
  background: var(--bg-card); width: 100%; max-width: 480px; border-radius: 24px;
  box-shadow: 0 30px 60px rgba(0,0,0,0.3); overflow: hidden; border: 1px solid var(--border-color);
  animation: slideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}
.modal-header {
  padding: 20px 24px; display: flex; align-items: center; justify-content: space-between;
  border-bottom: 1px solid var(--border-color); background: var(--bg-primary);
}
.modal-header-left { display: flex; align-items: center; gap: 12px; }
.modal-header h2 { font-size: 16px; font-weight: 800; margin: 0; }
.modal-icon {
  width: 36px; height: 36px; border-radius: 10px; background: rgba(249,115,22,0.1);
  color: #f97316; display: flex; align-items: center; justify-content: center;
}
.btn-close {
  width: 30px; height: 30px; border-radius: 50%; border: none;
  background: var(--bg-card-hover); color: var(--text-muted); cursor: pointer;
  display: flex; align-items: center; justify-content: center; transition: 0.2s;
}
.btn-close:hover { background: var(--danger-bg); color: var(--danger); transform: rotate(90deg); }

.modal-body { padding: 24px; }

.plan-brief {
  background: var(--bg-primary); border: 1px solid var(--border-color);
  border-radius: 14px; padding: 16px;
}
.brief-label { display: block; font-size: 10px; font-weight: 800; color: var(--text-muted); letter-spacing: 1px; margin-bottom: 6px; }
.brief-row { display: flex; justify-content: space-between; align-items: center; }
.brief-row h3 { margin: 0; font-size: 18px; font-weight: 900; letter-spacing: -0.5px; }
.brief-price { font-size: 13px; font-weight: 700; color: var(--text-muted); }

.duration-section { margin-top: 20px; }
.duration-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin-top: 8px; }
.dur-btn {
  padding: 12px 0; border: 2px solid var(--border-color); border-radius: 12px;
  background: var(--bg-card); cursor: pointer; transition: 0.2s;
  display: flex; flex-direction: column; align-items: center; position: relative;
}
.dur-btn:hover { border-color: #f97316; }
.dur-btn.active { border-color: #f97316; background: rgba(249, 115, 22, 0.04); }
.dur-num { font-size: 16px; font-weight: 900; color: var(--text-primary); }
.dur-btn.active .dur-num { color: #f97316; }
.dur-label { font-size: 10px; font-weight: 700; color: var(--text-muted); }
.dur-save {
  position: absolute; top: -6px; right: -6px; padding: 2px 5px;
  background: #22c55e; color: #fff; font-size: 8px; font-weight: 900; border-radius: 4px;
}

.price-summary { border-top: 1px dashed var(--border-color); padding-top: 16px; margin-top: 20px; }
.sum-row { display: flex; justify-content: space-between; font-size: 13px; color: var(--text-secondary); margin-bottom: 8px; }
.sum-row.discount { color: #22c55e; font-weight: 700; }
.sum-line { height: 1px; background: var(--border-color); margin: 10px 0; }
.sum-row.total { font-size: 15px; font-weight: 800; color: var(--text-primary); }
.total-amount { font-size: 20px; color: #f97316; font-weight: 900; letter-spacing: -0.5px; }

.info-note { display: flex; gap: 8px; font-size: 11px; color: var(--text-muted); padding: 12px; background: var(--bg-primary); border-radius: 10px; margin-top: 16px; }
.info-note p { margin: 0; }

.modal-footer {
  padding: 16px 24px; display: grid; grid-template-columns: 1fr 2fr; gap: 12px;
  border-top: 1px solid var(--border-color); background: var(--bg-primary);
}
.btn-cancel {
  height: 44px; border-radius: 12px; border: 1.5px solid var(--border-color);
  background: transparent; font-weight: 700; cursor: pointer; transition: 0.2s;
}
.btn-cancel:hover { border-color: var(--text-muted); }
.btn-confirm {
  height: 44px; border-radius: 12px; border: none;
  background: linear-gradient(135deg, #f97316, #ea580c); color: #fff;
  font-weight: 700; cursor: pointer; box-shadow: 0 8px 16px rgba(249, 115, 22, 0.2);
  display: flex; align-items: center; justify-content: center; gap: 8px; transition: 0.2s;
}
.btn-confirm:hover { transform: translateY(-1px); }
.btn-confirm:disabled { opacity: 0.6; cursor: not-allowed; }

/* ── Utilities ── */
.text-right { text-align: right; }
.text-xs { font-size: 11px; }

/* ── Animations ── */
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
.spin { animation: spin 1s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

.modal-fade-enter-active { animation: slideUp 0.3s ease-out; }
.modal-fade-leave-active { animation: slideUp 0.2s ease-in reverse; }

.mini-spin { width: 16px; height: 16px; border: 2px solid rgba(255,255,255,0.3); border-radius: 50%; border-top-color: #fff; animation: spin 0.8s linear infinite; }

.error-state {
  grid-column: 1 / -1;
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  padding: 40px; background: rgba(239, 68, 68, 0.05); border: 1px dashed rgba(239, 68, 68, 0.3);
  border-radius: 20px; text-align: center;
}
.error-icon { color: #ef4444; margin-bottom: 12px; }
.error-state h4 { margin: 0; color: #b91c1c; font-size: 16px; }
.error-state p { margin: 8px 0 16px; font-size: 13px; color: var(--text-muted); max-width: 300px; }
.btn-retry {
  padding: 8px 16px; border-radius: 8px; border: none;
  background: #ef4444; color: #fff; font-weight: 700; font-size: 12px; cursor: pointer;
}

.plan-skeleton { height: 280px; border-radius: 20px; background: linear-gradient(90deg, #f1f5f9 25%, #f8fafc 50%, #f1f5f9 75%); background-size: 200% 100%; animation: shimmer 1.5s infinite; }
@keyframes shimmer { from { background-position: 200% 0; } to { background-position: -200% 0; } }

/* ── Responsive ── */
@media (max-width: 768px) {
  .status-row { flex-direction: column; align-items: flex-start; }
  .status-sep { display: none; }
  .expiry-warning { margin-left: 0; width: 100%; }
  .plans-grid { grid-template-columns: 1fr; }
}
</style>
