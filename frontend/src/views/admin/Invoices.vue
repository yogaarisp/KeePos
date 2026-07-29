<template>
  <div class="admin-invoices">
    <!-- Hero Header -->
    <div class="page-hero">
      <div class="hero-content">
        <div class="hero-icon-wrap"><Receipt :size="24" /></div>
        <div>
          <h1 class="hero-title">History Invoice Global</h1>
          <p class="hero-subtitle">Pantau & konfirmasi seluruh transaksi dari semua tenant.</p>
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
          <span class="sm-label">MENUNGGU KONFIRMASI</span>
          <h4 class="sm-val">{{ stats.pending_manual_count }} Invoice</h4>
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

    <!-- Filter Tabs -->
    <div class="filter-tabs">
      <button
        v-for="tab in filterTabs" :key="tab.value"
        :class="['tab-btn', { active: activeFilter === tab.value }]"
        @click="activeFilter = tab.value"
      >
        {{ tab.label }}
        <span v-if="tab.value === 'pending_manual' && stats.pending_manual_count > 0" class="tab-badge">
          {{ stats.pending_manual_count }}
        </span>
      </button>
    </div>

    <!-- Invoices Table -->
    <div class="section-card">
      <div class="card-header">
        <div class="header-left">
          <h2 class="card-title">Daftar Transaksi</h2>
          <span class="card-badge">{{ filteredInvoices.length }} Invoice</span>
        </div>
        <div class="search-box">
          <Search :size="16" class="search-icon" />
          <input type="text" v-model="searchQuery" placeholder="Cari tenant atau #invoice..." class="search-input">
        </div>
      </div>

      <div class="table-responsive">
        <table class="premium-table">
          <thead>
            <tr>
              <th># INVOICE</th>
              <th>TENANT</th>
              <th>PAKET</th>
              <th>DURASI</th>
              <th>NOMINAL</th>
              <th>METODE</th>
              <th>STATUS</th>
              <th>BUKTI</th>
              <th class="text-right">AKSI</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="9" class="loading-cell">
                <RefreshCw :size="28" class="spin" />
                <p>Memuat data...</p>
              </td>
            </tr>
            <template v-else>
              <tr v-for="inv in filteredInvoices" :key="inv.id" class="data-row"
                  :class="{ 'row-highlight': inv.status === 'pending' && inv.payment_method === 'manual' }">
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
                <td><div class="plan-badge" :class="inv.plan">{{ inv.plan?.toUpperCase() }}</div></td>
                <td class="td-medium">{{ inv.months || 1 }} Bln</td>
                <td class="td-bold">Rp {{ formatNumber(inv.amount) }}</td>
                <td>
                  <span class="method-tag" :class="inv.payment_method === 'manual' ? 'manual' : 'midtrans'">
                    {{ inv.payment_method === 'manual' ? 'Transfer Manual' : (inv.payment_method || 'Midtrans') }}
                  </span>
                </td>
                <td>
                  <div class="status-pill" :class="inv.status">
                    <span class="pill-dot"></span>
                    {{ formatStatus(inv.status) }}
                  </div>
                </td>
                <td>
                  <a v-if="inv.payment_proof_url" href="#"
                     @click.prevent="openProof(inv)" class="link-proof">
                    <Eye :size="14" /> Lihat
                  </a>
                  <span v-else class="text-muted">—</span>
                </td>
                <td class="text-right">
                  <div class="action-btns" v-if="inv.status === 'pending' && inv.payment_method === 'manual'">
                    <button class="btn-approve" @click="openApprove(inv)" :disabled="processing === inv.id">
                      <CheckCircle :size="14" /> Konfirmasi
                    </button>
                    <button class="btn-reject" @click="openReject(inv)" :disabled="processing === inv.id">
                      <XCircle :size="14" /> Tolak
                    </button>
                  </div>
                  <span v-else class="text-muted text-xs">{{ formatDate(inv.paid_at || inv.created_at) }}</span>
                </td>
              </tr>
              <tr v-if="filteredInvoices.length === 0">
                <td colspan="9" class="empty-cell">
                  <ClipboardList :size="40" class="empty-icon" />
                  <p>Tidak ada invoice yang sesuai.</p>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Approve Modal -->
    <Teleport to="body">
      <Transition name="modal-fade">
        <div v-if="approveModal.show" class="modal-overlay" @click.self="approveModal.show = false">
          <div class="modal-box">
            <header class="modal-header">
              <div class="modal-icon approve"><CheckCircle :size="20" /></div>
              <div>
                <h3>Konfirmasi Pembayaran</h3>
                <p>{{ approveModal.invoice?.tenant?.name }}</p>
              </div>
              <button class="btn-close" @click="approveModal.show = false"><X :size="18" /></button>
            </header>
            <div class="modal-body">
              <div class="confirm-summary">
                <div class="cs-row"><span>Invoice</span><strong>#{{ approveModal.invoice?.invoice_number }}</strong></div>
                <div class="cs-row"><span>Paket</span><strong>{{ approveModal.invoice?.plan?.toUpperCase() }}</strong></div>
                <div class="cs-row"><span>Durasi</span><strong>{{ approveModal.invoice?.months || 1 }} Bulan</strong></div>
                <div class="cs-row"><span>Nominal</span><strong>Rp {{ formatNumber(approveModal.invoice?.amount) }}</strong></div>
              </div>
              <div class="confirm-note">
                <CheckCircle :size="14" />
                <span>Tenant akan langsung diaktifkan dan mendapat email konfirmasi.</span>
              </div>
            </div>
            <footer class="modal-footer">
              <button class="btn-cancel" @click="approveModal.show = false">Batal</button>
              <button class="btn-confirm-approve" @click="doApprove" :disabled="processing">
                <RefreshCw v-if="processing" :size="16" class="spin" />
                <span v-else>✅ Ya, Konfirmasi</span>
              </button>
            </footer>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Reject Modal -->
    <Teleport to="body">
      <Transition name="modal-fade">
        <div v-if="rejectModal.show" class="modal-overlay" @click.self="rejectModal.show = false">
          <div class="modal-box">
            <header class="modal-header">
              <div class="modal-icon reject"><XCircle :size="20" /></div>
              <div>
                <h3>Tolak Pembayaran</h3>
                <p>{{ rejectModal.invoice?.tenant?.name }}</p>
              </div>
              <button class="btn-close" @click="rejectModal.show = false"><X :size="18" /></button>
            </header>
            <div class="modal-body">
              <div class="field-group">
                <label class="field-label">Alasan Penolakan (opsional)</label>
                <textarea v-model="rejectModal.reason" rows="3" class="modern-textarea"
                  placeholder="Contoh: Bukti transfer tidak jelas, nominal tidak sesuai..."></textarea>
              </div>
              <div class="reject-note">
                <XCircle :size="14" />
                <span>Tenant akan mendapat email pemberitahuan penolakan.</span>
              </div>
            </div>
            <footer class="modal-footer">
              <button class="btn-cancel" @click="rejectModal.show = false">Batal</button>
              <button class="btn-confirm-reject" @click="doReject" :disabled="processing">
                <RefreshCw v-if="processing" :size="16" class="spin" />
                <span v-else>❌ Tolak Invoice</span>
              </button>
            </footer>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Proof Preview Modal -->
    <Teleport to="body">
      <Transition name="modal-fade">
        <div v-if="proofModal.show" class="modal-overlay" @click.self="proofModal.show = false">
          <div class="modal-box proof-modal-box">
            <header class="modal-header">
              <div class="modal-icon approve"><Eye :size="20" /></div>
              <div>
                <h3>Bukti Pembayaran</h3>
                <p>Invoice #{{ proofModal.invoiceNumber }}</p>
              </div>
              <button class="btn-close" @click="proofModal.show = false"><X :size="18" /></button>
            </header>
            <div class="modal-body proof-modal-body">
              <div v-if="isPdf(proofModal.url)" class="pdf-container">
                <iframe :src="proofModal.url" width="100%" height="450px" frameborder="0"></iframe>
              </div>
              <div v-else class="image-container">
                <img :src="proofModal.url" alt="Bukti Pembayaran" class="proof-img-preview" />
              </div>
            </div>
            <footer class="modal-footer proof-modal-footer">
              <a :href="proofModal.url" download class="btn-download" target="_blank">
                Tampilkan Penuh / Unduh
              </a>
              <button class="btn-close-preview" @click="proofModal.show = false">Tutup</button>
            </footer>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, reactive } from 'vue';
import api from '../../api';
import {
  Receipt, Search, CheckCircle, Clock, DollarSign,
  RefreshCw, ClipboardList, Eye, XCircle, X
} from 'lucide-vue-next';
import { showSuccess, showError } from '../../utils/swal';

const loading   = ref(true);
const invoices  = ref([]);
const searchQuery  = ref('');
const activeFilter = ref('all');
const processing   = ref(null);

const filterTabs = [
  { label: 'Semua',              value: 'all' },
  { label: '⏳ Perlu Konfirmasi', value: 'pending_manual' },
  { label: '✅ Lunas',           value: 'paid' },
  { label: '❌ Ditolak',         value: 'rejected' },
  { label: '⌛ Kadaluarsa',      value: 'expired' },
];

const approveModal = reactive({ show: false, invoice: null });
const rejectModal  = reactive({ show: false, invoice: null, reason: '' });
const proofModal   = reactive({ show: false, url: '', invoiceNumber: '' });

// ── Computed ──────────────────────────────────────────────
const stats = computed(() => {
  const paid          = invoices.value.filter(i => i.status === 'paid' || i.status === 'settled');
  const pendingManual = invoices.value.filter(i => i.status === 'pending' && i.payment_method === 'manual');
  return {
    paid_count:           paid.length,
    pending_manual_count: pendingManual.length,
    total_amount:         paid.reduce((acc, i) => acc + parseFloat(i.amount || 0), 0),
  };
});

const filteredInvoices = computed(() => {
  let list = invoices.value;

  if (activeFilter.value === 'pending_manual') {
    list = list.filter(i => i.status === 'pending' && i.payment_method === 'manual');
  } else if (activeFilter.value !== 'all') {
    list = list.filter(i => i.status === activeFilter.value);
  }

  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase();
    list = list.filter(i =>
      i.tenant?.name?.toLowerCase().includes(q) ||
      i.invoice_number?.toLowerCase().includes(q) ||
      i.external_id?.toLowerCase().includes(q)
    );
  }

  return list;
});

// ── Fetch ─────────────────────────────────────────────────
const fetchData = async () => {
  loading.value = true;
  try {
    const res = await api.get('/admin/invoices');
    if (res.data.success) {
      const data = res.data.data;
      invoices.value = Array.isArray(data) ? data : (data.data || []);
    }
  } catch (err) {
    console.error('Failed to fetch invoices', err);
  } finally {
    loading.value = false;
  }
};

onMounted(fetchData);

// ── Approve ───────────────────────────────────────────────
const openApprove = (inv) => {
  approveModal.invoice = inv;
  approveModal.show    = true;
};

const doApprove = async () => {
  processing.value = approveModal.invoice.id;
  try {
    const res = await api.patch(`/admin/invoices/${approveModal.invoice.id}/approve`);
    if (res.data.success) {
      showSuccess('Pembayaran dikonfirmasi! Tenant telah diaktifkan.');
      approveModal.show = false;
      await fetchData();
    }
  } catch (err) {
    showError(err.response?.data?.message || 'Gagal mengkonfirmasi pembayaran.');
  } finally {
    processing.value = null;
  }
};

// ── Reject ────────────────────────────────────────────────
const openReject = (inv) => {
  rejectModal.invoice = inv;
  rejectModal.reason  = '';
  rejectModal.show    = true;
};

const doReject = async () => {
  processing.value = rejectModal.invoice.id;
  try {
    const res = await api.patch(`/admin/invoices/${rejectModal.invoice.id}/reject`, {
      reason: rejectModal.reason,
    });
    if (res.data.success) {
      showSuccess('Invoice ditolak. Tenant telah diberitahu via email.');
      rejectModal.show = false;
      await fetchData();
    }
  } catch (err) {
    showError(err.response?.data?.message || 'Gagal menolak invoice.');
  } finally {
    processing.value = null;
  }
};

const openProof = (inv) => {
  proofModal.url = inv.payment_proof_url;
  proofModal.invoiceNumber = inv.external_id ? inv.external_id.split('-').pop() : inv.invoice_number;
  proofModal.show = true;
};

const isPdf = (url) => {
  if (!url) return false;
  return url.toLowerCase().endsWith('.pdf') || url.includes('.pdf?');
};

// ── Helpers ───────────────────────────────────────────────
const formatNumber = (num) => {
  if (!num) return '0';
  return new Intl.NumberFormat('id-ID').format(Math.round(num));
};

const formatDate = (date) => {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('id-ID', {
    day: 'numeric', month: 'short', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  });
};

const formatStatus = (s) => ({
  settled: 'Lunas', paid: 'Lunas', pending: 'Menunggu',
  expired: 'Kadaluarsa', rejected: 'Ditolak',
}[s] || s);
</script>

<style scoped>
.admin-invoices { animation: fadeIn 0.4s ease; }

/* Hero */
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
.hero-subtitle { font-size: 13px; color: var(--text-muted); margin: 0; }

/* Stats */
.stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 20px; }
.stat-mini-card { background: var(--bg-card); border: 1px solid var(--border-color); padding: 16px; border-radius: 16px; display: flex; align-items: center; gap: 16px; }
.sm-icon { width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; }
.sm-icon.paid    { background: rgba(34,197,94,0.1); color: #22c55e; }
.sm-icon.pending { background: rgba(234,179,8,0.1); color: #eab308; }
.sm-icon.amount  { background: rgba(249,115,22,0.1); color: var(--accent); }
.sm-label { font-size: 10px; font-weight: 800; color: var(--text-muted); letter-spacing: 0.5px; display: block; }
.sm-val   { font-size: 16px; font-weight: 800; color: var(--text-primary); margin: 2px 0 0; }

/* Filter Tabs */
.filter-tabs { display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap; }
.tab-btn {
  padding: 8px 16px; border-radius: 10px; border: 1.5px solid var(--border-color);
  background: var(--bg-card); color: var(--text-secondary); font-size: 13px; font-weight: 600;
  cursor: pointer; transition: 0.2s; display: flex; align-items: center; gap: 6px;
}
.tab-btn:hover  { border-color: var(--accent); color: var(--accent); }
.tab-btn.active { background: var(--accent); color: #fff; border-color: var(--accent); }
.tab-badge {
  background: #ef4444; color: #fff; font-size: 10px; font-weight: 800;
  padding: 2px 6px; border-radius: 50px; min-width: 18px; text-align: center;
}

/* Card */
.section-card { background: var(--bg-card); border-radius: 20px; border: 1px solid var(--border-color); overflow: hidden; }
.card-header {
  padding: 18px 24px; display: flex; align-items: center; justify-content: space-between;
  border-bottom: 1px solid var(--border-color); background: var(--bg-primary); flex-wrap: wrap; gap: 12px;
}
.card-title { font-size: 16px; font-weight: 700; margin: 0; }
.card-badge { font-size: 11px; font-weight: 700; color: var(--text-muted); background: var(--border-color); padding: 3px 10px; border-radius: 50px; margin-left: 10px; }
.search-box { position: relative; }
.search-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); }
.search-input { height: 38px; padding: 0 12px 0 36px; border-radius: 10px; background: var(--bg-card); border: 1.5px solid var(--border-color); font-size: 13px; color: var(--text-primary); outline: none; width: 260px; }
.search-input:focus { border-color: var(--accent); }

/* Table */
.table-responsive { overflow-x: auto; }
.premium-table { width: 100%; border-collapse: collapse; }
.premium-table th { padding: 14px 20px; text-align: left; font-size: 11px; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid var(--border-color); }
.data-row { border-bottom: 1px solid var(--border-color); transition: 0.15s; }
.data-row:hover { background: var(--bg-primary); }
.data-row td { padding: 14px 20px; vertical-align: middle; font-size: 13px; }
.row-highlight { background: rgba(234,179,8,0.04); }
.td-mono  { font-family: monospace; color: var(--accent); font-weight: 700; font-size: 12px; }
.td-bold  { font-weight: 800; color: var(--text-primary); }
.td-medium { color: var(--text-secondary); }
.text-right { text-align: right; }
.text-muted { color: var(--text-muted); font-size: 12px; }
.text-xs { font-size: 11px; }

/* Tenant cell */
.tenant-cell { display: flex; align-items: center; gap: 10px; }
.tenant-avatar { width: 34px; height: 34px; border-radius: 10px; background: var(--accent); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 15px; }
.tenant-name { font-weight: 700; color: var(--text-primary); font-size: 13px; }
.tenant-id   { font-size: 10px; color: var(--text-muted); }

/* Badges */
.plan-badge { display: inline-block; padding: 3px 9px; border-radius: 6px; font-size: 10px; font-weight: 900; }
.plan-badge.free  { background: #f1f5f9; color: #64748b; }
.plan-badge.basic { background: rgba(37,99,235,0.1); color: #2563eb; }
.plan-badge.pro   { background: rgba(249,115,22,0.1); color: var(--accent); }

.status-pill { display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 50px; font-size: 11px; font-weight: 800; }
.pill-dot { width: 5px; height: 5px; border-radius: 50%; background: currentColor; }
.status-pill.paid, .status-pill.settled  { background: rgba(34,197,94,0.1); color: #22c55e; }
.status-pill.pending  { background: rgba(234,179,8,0.1); color: #eab308; }
.status-pill.expired  { background: rgba(239,68,68,0.1); color: #ef4444; }
.status-pill.rejected { background: rgba(239,68,68,0.1); color: #ef4444; }

.method-tag { font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 6px; }
.method-tag.manual   { background: rgba(139,92,246,0.1); color: #7c3aed; }
.method-tag.midtrans { background: rgba(37,99,235,0.1); color: #2563eb; }

.link-proof { display: inline-flex; align-items: center; gap: 4px; font-size: 12px; font-weight: 700; color: #2563eb; text-decoration: none; }
.link-proof:hover { text-decoration: underline; }

/* Action buttons */
.action-btns { display: flex; gap: 6px; justify-content: flex-end; }
.btn-approve {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 6px 12px; border-radius: 8px; border: none;
  background: rgba(34,197,94,0.1); color: #16a34a;
  font-size: 12px; font-weight: 700; cursor: pointer; transition: 0.2s;
}
.btn-approve:hover:not(:disabled) { background: #22c55e; color: #fff; }
.btn-reject {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 6px 12px; border-radius: 8px; border: none;
  background: rgba(239,68,68,0.1); color: #dc2626;
  font-size: 12px; font-weight: 700; cursor: pointer; transition: 0.2s;
}
.btn-reject:hover:not(:disabled) { background: #ef4444; color: #fff; }
.btn-approve:disabled, .btn-reject:disabled { opacity: 0.5; cursor: not-allowed; }

/* Loading / Empty */
.loading-cell, .empty-cell { padding: 60px 0; text-align: center; color: var(--text-muted); }
.loading-cell { display: flex; flex-direction: column; align-items: center; gap: 10px; }
.empty-icon { opacity: 0.3; margin-bottom: 8px; }

/* Modal */
.modal-overlay {
  position: fixed; inset: 0; background: rgba(0,0,0,0.55); backdrop-filter: blur(6px);
  display: flex; align-items: center; justify-content: center; z-index: 2000; padding: 20px;
}
.modal-box {
  background: var(--bg-card); width: 100%; max-width: 440px; border-radius: 20px;
  border: 1px solid var(--border-color); box-shadow: 0 24px 48px rgba(0,0,0,0.2);
  animation: slideUp 0.3s cubic-bezier(0.16,1,0.3,1);
}
.modal-header {
  padding: 20px 24px; display: flex; align-items: center; gap: 14px;
  border-bottom: 1px solid var(--border-color);
}
.modal-icon { width: 40px; height: 40px; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.modal-icon.approve { background: rgba(34,197,94,0.1); color: #22c55e; }
.modal-icon.reject  { background: rgba(239,68,68,0.1); color: #ef4444; }
.modal-header h3 { font-size: 16px; font-weight: 800; margin: 0; }
.modal-header p  { font-size: 12px; color: var(--text-muted); margin: 2px 0 0; }
.btn-close { margin-left: auto; width: 30px; height: 30px; border-radius: 50%; border: none; background: var(--bg-primary); color: var(--text-muted); cursor: pointer; display: flex; align-items: center; justify-content: center; }

.modal-body { padding: 20px 24px; }
.confirm-summary { background: var(--bg-primary); border-radius: 12px; padding: 14px; margin-bottom: 14px; }
.cs-row { display: flex; justify-content: space-between; font-size: 13px; padding: 4px 0; color: var(--text-secondary); }
.cs-row strong { color: var(--text-primary); font-weight: 700; }
.confirm-note, .reject-note {
  display: flex; align-items: center; gap: 8px; font-size: 12px; padding: 10px 12px; border-radius: 10px;
}
.confirm-note { background: rgba(34,197,94,0.08); color: #16a34a; }
.reject-note  { background: rgba(239,68,68,0.08); color: #dc2626; margin-top: 12px; }

.field-group { display: flex; flex-direction: column; gap: 6px; }
.field-label { font-size: 11px; font-weight: 800; color: var(--text-muted); text-transform: uppercase; }
.modern-textarea {
  width: 100%; padding: 10px 12px; border-radius: 10px; border: 1.5px solid var(--border-color);
  background: var(--bg-primary); color: var(--text-primary); font-size: 13px; resize: vertical; outline: none;
}
.modern-textarea:focus { border-color: var(--accent); }

.modal-footer { padding: 16px 24px; display: flex; gap: 10px; border-top: 1px solid var(--border-color); }
.btn-cancel { flex: 1; height: 42px; border-radius: 12px; border: 1.5px solid var(--border-color); background: transparent; font-weight: 700; cursor: pointer; }
.btn-confirm-approve { flex: 2; height: 42px; border-radius: 12px; border: none; background: #22c55e; color: #fff; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; }
.btn-confirm-reject  { flex: 2; height: 42px; border-radius: 12px; border: none; background: #ef4444; color: #fff; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; }
.btn-confirm-approve:disabled, .btn-confirm-reject:disabled { opacity: 0.6; cursor: not-allowed; }

/* Animations */
.modal-fade-enter-active { transition: opacity 0.25s; }
.modal-fade-leave-active { transition: opacity 0.2s; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; }
@keyframes slideUp { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }
@keyframes fadeIn  { from { opacity: 0; } to { opacity: 1; } }
.spin { animation: spin 1s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

@media (max-width: 768px) {
  .stats-row { grid-template-columns: 1fr; }
  .search-input { width: 100%; }
  .filter-tabs { gap: 6px; }
  .tab-btn { font-size: 12px; padding: 6px 12px; }
}

/* Proof Modal Styling */
.proof-modal-box {
  max-width: 600px;
  width: 90%;
}
.proof-modal-body {
  padding: 16px;
  display: flex;
  justify-content: center;
  align-items: center;
  background: var(--bg-primary);
  max-height: 70vh;
  overflow-y: auto;
}
.image-container {
  width: 100%;
  display: flex;
  justify-content: center;
}
.proof-img-preview {
  max-width: 100%;
  max-height: 60vh;
  border-radius: 12px;
  box-shadow: 0 8px 24px rgba(0,0,0,0.15);
  object-fit: contain;
  transition: transform 0.2s;
}
.proof-img-preview:hover {
  transform: scale(1.02);
}
.pdf-container {
  width: 100%;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 8px 24px rgba(0,0,0,0.15);
}
.proof-modal-footer {
  display: flex;
  gap: 12px;
  justify-content: space-between;
}
.btn-download {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 10px 18px;
  border-radius: 12px;
  background: var(--accent);
  color: #fff;
  font-weight: 700;
  text-decoration: none;
  font-size: 13px;
  transition: 0.2s;
  flex: 1;
  text-align: center;
}
.btn-download:hover {
  background: #ea580c;
  transform: translateY(-1px);
}
.btn-close-preview {
  flex: 1;
  height: 42px;
  border-radius: 12px;
  border: 1.5px solid var(--border-color);
  background: var(--bg-card);
  color: var(--text-primary);
  font-weight: 700;
  cursor: pointer;
  transition: 0.2s;
}
.btn-close-preview:hover {
  background: var(--bg-primary);
}
</style>
