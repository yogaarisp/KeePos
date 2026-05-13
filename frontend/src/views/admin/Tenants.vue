<template>
  <div class="tenants-view">
    <!-- Header Summary Stats -->
    <div class="stats-grid">
      <div class="stat-card premium-card">
        <div class="stat-icon-bg purple">
          <Store :size="24" />
        </div>
        <div class="stat-info">
          <h4 class="stat-label">Total Toko</h4>
          <p class="stat-value">{{ stats.total_tenants }}</p>
          <span class="stat-trend positive">
            <TrendingUp :size="12" /> +{{ newTenantsThisMonth }} bln ini
          </span>
        </div>
      </div>

      <div class="stat-card premium-card">
        <div class="stat-icon-bg green">
          <Activity :size="24" />
        </div>
        <div class="stat-info">
          <h4 class="stat-label">Toko Aktif</h4>
          <p class="stat-value">{{ stats.active_tenants }}</p>
          <span class="stat-sub">{{ activeRate }}% dari total</span>
        </div>
      </div>

      <div class="stat-card premium-card">
        <div class="stat-icon-bg orange">
          <CreditCard :size="24" />
        </div>
        <div class="stat-info">
          <h4 class="stat-label">Estimasi Pendapatan</h4>
          <p class="stat-value">Rp {{ formatNumber(estimatedRevenue) }}</p>
          <span class="stat-sub">Berdasarkan plan aktif</span>
        </div>
      </div>
    </div>

    <!-- Plan Distribution -->
    <div class="plan-dist-bar premium-card">
      <div class="dist-header">
        <h4>Distribusi Paket</h4>
      </div>
      <div class="dist-progress">
        <div 
          v-for="plan in stats.plan_distribution" 
          :key="plan.plan"
          class="dist-segment"
          :class="plan.plan"
          :style="{ width: (plan.count / stats.total_tenants * 100) + '%' }"
          :title="`${plan.plan}: ${plan.count}`"
        ></div>
      </div>
      <div class="dist-legend">
        <div v-for="plan in stats.plan_distribution" :key="plan.plan" class="legend-item">
          <span class="dot" :class="plan.plan"></span>
          <span class="label">{{ plan.plan.toUpperCase() }}: {{ plan.count }}</span>
        </div>
      </div>
    </div>

    <!-- Tenants Table -->
    <div class="table-container premium-card shadow-sm">
      <div class="table-header">
        <div class="header-main">
          <h3>Daftar Penyewa (Tenants)</h3>
          <p>Kelola semua toko yang terdaftar di platform</p>
        </div>
        <div class="header-actions">
          <div class="search-input">
            <Search :size="18" />
            <input type="text" v-model="search" placeholder="Cari nama toko or slug...">
          </div>
        </div>
      </div>

      <div class="custom-table-wrapper">
        <table class="premium-table">
          <thead>
            <tr>
              <th>TOKO</th>
              <th>PAKET</th>
              <th>STATUS</th>
              <th>USER</th>
              <th>TRIAL/EXPIRES</th>
              <th class="text-right">AKSI</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="tenant in filteredTenants" :key="tenant.id">
              <td data-label="TOKO">
                <div class="tenant-identity">
                  <div class="tenant-avatar">{{ tenant.name.charAt(0) }}</div>
                  <div class="tenant-text">
                    <span class="t-name">{{ tenant.name }}</span>
                    <span class="t-slug">{{ tenant.slug }}.wartegkee.com</span>
                  </div>
                </div>
              </td>
              <td data-label="PAKET">
                <div class="plan-badge" :class="tenant.plan">
                  {{ tenant.plan.toUpperCase() }}
                </div>
              </td>
              <td data-label="STATUS">
                <div class="status-toggle" @click="toggleTenantStatus(tenant)">
                  <span class="status-dot" :class="{ active: tenant.is_active }"></span>
                  {{ tenant.is_active ? 'Aktif' : 'Non-aktif' }}
                </div>
              </td>
              <td data-label="USER">
                <div class="user-count">
                  <Users :size="14" /> {{ tenant.users_count }}
                </div>
              </td>
              <td data-label="TRIAL/EXPIRES">
                <div class="date-info">
                  <span v-if="tenant.plan === 'free'">Trial Ends: {{ formatDate(tenant.trial_ends_at) }}</span>
                  <span v-else>Expires: {{ formatDate(tenant.subscription_ends_at) }}</span>
                </div>
              </td>
              <td class="text-right" data-label="AKSI">
                <div class="action-buttons">
                  <button class="btn-icon" @click="editTenant(tenant)" title="Edit Paket">
                    <Edit2 :size="16" />
                  </button>
                  <button class="btn-icon danger" @click="confirmDelete(tenant)" title="Hapus Toko">
                    <Trash2 :size="16" />
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="filteredTenants.length === 0">
              <td colspan="6" class="empty-state">
                <Search :size="48" />
                <p>Tidak ada tenant yang ditemukan</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Edit Modal -->
    <Teleport to="body">
      <Transition name="modal-fade">
        <div v-if="showEditModal" class="modal-overlay" @click.self="showEditModal = false">
          <div class="modal-container">
            <header class="modal-header">
              <div class="modal-header-content">
                <button class="btn-back-header" @click="showEditModal = false">
                  <ArrowLeft :size="20" />
                </button>
                <div class="modal-icon-wrap">
                  <Edit2 :size="18" />
                </div>
                <div class="modal-title-area">
                  <h3 class="modal-title">Update Tenant</h3>
                  <p class="modal-subtitle">{{ selectedTenant?.name }}</p>
                </div>
              </div>
            </header>

            <div class="modal-body">
              <div class="field-group">
                <label class="field-label-premium">Nama Toko</label>
                <input type="text" v-model="editForm.name" class="modern-input-premium">
              </div>

              <div class="field-group">
                <label class="field-label-premium">Paket Layanan</label>
                <select v-model="editForm.plan" class="modern-select-premium">
                  <option value="free">FREE</option>
                  <option value="basic">BASIC</option>
                  <option value="pro">PRO</option>
                </select>
              </div>

              <div class="field-group">
                <div class="status-config-row">
                  <div class="status-text">
                    <label class="field-label-premium">Status Akun</label>
                    <p class="field-desc">Tentukan apakah toko ini aktif atau dibekukan.</p>
                  </div>
                  <div class="toggle-wrap">
                    <span class="status-label" :class="{ active: editForm.is_active }">
                      {{ editForm.is_active ? 'Aktif' : 'Non-aktif' }}
                    </span>
                    <label class="modern-switch">
                      <input type="checkbox" v-model="editForm.is_active">
                      <span class="switch-slider"></span>
                    </label>
                  </div>
                </div>
              </div>

              <div class="field-group" v-if="editForm.plan === 'free'">
                <label class="field-label-premium">Batas Trial (Trial Ends)</label>
                <input type="date" v-model="editForm.trial_ends_at" class="modern-input-premium">
                <p class="field-hint-premium">Hanya berlaku untuk paket FREE</p>
              </div>
              <div class="field-group" v-else>
                <label class="field-label-premium">Batas Langganan (Subscription Ends)</label>
                <input type="date" v-model="editForm.subscription_ends_at" class="modern-input-premium">
                <p class="field-hint-premium">Berlaku untuk paket berbayar (BASIC/PRO)</p>
              </div>
            </div>

            <footer class="modal-footer-premium">
              <button class="btn-save-premium" @click="updateTenant" :disabled="loading">
                <RefreshCw v-if="loading" :size="18" class="spin" />
                <span v-else>Simpan Perubahan</span>
              </button>
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
  Store, Activity, CreditCard, Search, TrendingUp,
  Users, Edit2, Trash2, X, RefreshCw, ArrowLeft
} from 'lucide-vue-next';
import { showConfirm, showAlert } from '../../utils/swal';

const tenants = ref([]);
const stats = ref({
  total_tenants: 0,
  active_tenants: 0,
  plan_distribution: []
});
const search = ref('');
const loading = ref(false);
const showEditModal = ref(false);
const selectedTenant = ref(null);
const editForm = reactive({
  name: '',
  plan: '',
  is_active: true,
  trial_ends_at: '',
  subscription_ends_at: ''
});

onMounted(() => {
  fetchData();
  fetchStats();
});

const fetchData = async () => {
  try {
    const res = await api.get('/admin/tenants');
    // Handle both paginated and non-paginated response
    const data = res.data.data;
    tenants.value = data?.data ?? data ?? [];
  } catch (err) {
    console.error('Failed to fetch tenants', err);
    console.error('Response:', err.response?.data);
  }
};

const fetchStats = async () => {
  try {
    const res = await api.get('/admin/tenants/stats');
    stats.value = res.data.data;
  } catch (err) {
    console.error('Failed to fetch stats', err);
  }
};

const filteredTenants = computed(() => {
  if (!search.value) return tenants.value;
  const s = search.value.toLowerCase();
  return tenants.value.filter(t => 
    t.name.toLowerCase().includes(s) || 
    t.slug.toLowerCase().includes(s)
  );
});

const activeRate = computed(() => {
  if (stats.value.total_tenants === 0) return 0;
  return Math.round((stats.value.active_tenants / stats.value.total_tenants) * 100);
});

const estimatedRevenue = computed(() => {
  // Gunakan data dari stats API jika tersedia
  if (stats.value.estimated_revenue) return stats.value.estimated_revenue;
  
  // Fallback hitung manual dari distribusi plan
  let revenue = 0;
  stats.value.plan_distribution.forEach(p => {
    if (p.plan === 'basic') revenue += p.count * 99000;
    if (p.plan === 'pro') revenue += p.count * 299000;
  });
  return revenue;
});

const newTenantsThisMonth = ref(3); // Mock for UI

const formatDate = (date) => {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  });
};

const formatNumber = (num) => {
  return new Intl.NumberFormat('id-ID').format(num);
};

const editTenant = (tenant) => {
  selectedTenant.value = tenant;
  editForm.name = tenant.name;
  editForm.plan = tenant.plan;
  editForm.is_active = !!tenant.is_active;
  editForm.trial_ends_at = tenant.trial_ends_at ? tenant.trial_ends_at.split('T')[0] : '';
  editForm.subscription_ends_at = tenant.subscription_ends_at ? tenant.subscription_ends_at.split('T')[0] : '';
  showEditModal.value = true;
};

const updateTenant = async () => {
  loading.value = true;
  try {
    await api.put(`/admin/tenants/${selectedTenant.value.id}`, editForm);
    showEditModal.value = false;
    fetchData();
    fetchStats();
    showAlert({ title: 'Berhasil', text: 'Data tenant diperbarui', icon: 'success' });
  } catch (err) {
    showAlert({ title: 'Gagal', text: 'Terjadi kesalahan', icon: 'error' });
  } finally {
    loading.value = false;
  }
};

const toggleTenantStatus = async (tenant) => {
  try {
    const newStatus = !tenant.is_active;
    await api.put(`/admin/tenants/${tenant.id}`, {
      name: tenant.name,
      plan: tenant.plan,
      is_active: newStatus
    });
    tenant.is_active = newStatus;
    fetchStats();
  } catch (err) {
    showAlert({ title: 'Gagal', text: 'Terjadi kesalahan', icon: 'error' });
  }
};

const confirmDelete = async (tenant) => {
  const result = await showConfirm({
    title: 'Hapus Tenant?',
    text: `Toko "${tenant.name}" dan SEMUA datanya akan dihapus permanen!`,
    icon: 'warning',
    confirmText: 'Ya, Hapus'
  });

  if (result.isConfirmed) {
    try {
      await api.delete(`/admin/tenants/${tenant.id}`);
      fetchData();
      fetchStats();
      showAlert({ title: 'Hapus', text: 'Tenant berhasil dihapus', icon: 'success' });
    } catch (err) {
      showAlert({ title: 'Gagal', text: err.response?.data?.message || 'Gagal menghapus', icon: 'error' });
    }
  }
};
</script>

<style scoped>
.tenants-view {
  padding: 24px;
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 20px;
}

.premium-card {
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: 20px;
  padding: 24px;
  transition: transform 0.2s;
}

.stat-card {
  display: flex;
  align-items: center;
  gap: 20px;
}

.stat-icon-bg {
  width: 56px;
  height: 56px;
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
}

.stat-icon-bg.purple { background: linear-gradient(135deg, #a855f7, #7e22ce); }
.stat-icon-bg.green { background: linear-gradient(135deg, #22c55e, #15803d); }
.stat-icon-bg.orange { background: linear-gradient(135deg, #f97316, #c2410c); }

.stat-label { font-size: 14px; font-weight: 600; color: var(--text-muted); margin: 0; }
.stat-value { font-size: 28px; font-weight: 800; color: var(--text-primary); margin: 4px 0; }
.stat-trend { font-size: 12px; font-weight: 600; display: flex; align-items: center; gap: 4px; }
.stat-trend.positive { color: #22c55e; }
.stat-sub { font-size: 12px; color: var(--text-muted); }

.plan-dist-bar {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.dist-progress {
  height: 12px;
  background: var(--bg-primary);
  border-radius: 100px;
  display: flex;
  overflow: hidden;
}

.dist-segment.free { background: #94a3b8; }
.dist-segment.basic { background: #3b82f6; }
.dist-segment.pro { background: #f59e0b; }

.dist-legend {
  display: flex;
  gap: 20px;
}

.legend-item { display: flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 600; }
.legend-item .dot { width: 8px; height: 8px; border-radius: 50%; }
.legend-item .dot.free { background: #94a3b8; }
.legend-item .dot.basic { background: #3b82f6; }
.legend-item .dot.pro { background: #f59e0b; }

.table-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.header-main h3 { margin: 0; font-size: 20px; font-weight: 700; }
.header-main p { margin: 4px 0 0; color: var(--text-muted); font-size: 13px; }

.search-input {
  position: relative;
  display: flex;
  align-items: center;
}

.search-input svg { position: absolute; left: 16px; color: var(--text-muted); }
.search-input input {
  height: 44px;
  padding: 0 16px 0 48px;
  background: var(--bg-primary);
  border: 1px solid var(--border-color);
  border-radius: 12px;
  width: 300px;
  outline: none;
}

.custom-table-wrapper { overflow-x: auto; }
.premium-table { width: 100%; border-collapse: collapse; }
.premium-table th {
  text-align: left;
  padding: 16px;
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  color: var(--text-muted);
  border-bottom: 1px solid var(--border-color);
}
.premium-table td { padding: 16px; border-bottom: 1px solid var(--border-color); font-size: 14px; }

.tenant-identity { display: flex; align-items: center; gap: 12px; }
.tenant-avatar {
  width: 40px; height: 40px; border-radius: 10px;
  background: var(--accent-light); color: var(--accent);
  display: flex; align-items: center; justify-content: center;
  font-weight: 800;
}
.tenant-text { display: flex; flex-direction: column; }
.t-name { font-weight: 700; color: var(--text-primary); }
.t-slug { font-size: 12px; color: var(--text-muted); }

.plan-badge {
  display: inline-block; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 800;
}
.plan-badge.free { background: #f1f5f9; color: #64748b; }
.plan-badge.basic { background: #eff6ff; color: #3b82f6; }
.plan-badge.pro { background: #fffbeb; color: #d97706; }

.status-toggle {
  display: flex; align-items: center; gap: 8px; cursor: pointer;
  font-weight: 600; color: var(--text-secondary);
}
.status-dot { width: 8px; height: 8px; border-radius: 50%; background: #94a3b8; }
.status-dot.active { background: #22c55e; box-shadow: 0 0 8px rgba(34,197,94,0.5); }

.action-buttons { display: flex; gap: 8px; justify-content: flex-end; }
.btn-icon {
  width: 36px; height: 36px; border-radius: 10px; border: 1px solid var(--border-color);
  background: var(--bg-primary); color: var(--text-secondary); cursor: pointer;
  display: flex; align-items: center; justify-content: center; transition: 0.2s;
}
.btn-icon:hover { border-color: var(--accent); color: var(--accent); }
.btn-icon.danger:hover { border-color: var(--danger); color: var(--danger); }

/* ── Modal Premium (Matching Settings.vue Pattern) ── */
.modal-overlay {
  position: fixed; inset: 0;
  background-color: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(8px);
  display: flex; align-items: center; justify-content: center;
  z-index: 1000; padding: 20px;
}
.modal-container {
  background: var(--bg-card); width: 100%; max-width: 520px;
  border-radius: 24px; box-shadow: 0 30px 60px rgba(0,0,0,0.25);
  display: flex; flex-direction: column; max-height: 90vh;
  border: 1px solid var(--border-color);
  animation: slideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

.modal-header {
  padding: 24px; border-bottom: 1px solid var(--border-color);
  display: flex; flex-direction: column; gap: 16px;
}
.modal-header-content {
  display: flex; align-items: center; gap: 16px; width: 100%;
}
.btn-back-header {
  width: 40px; height: 40px; border-radius: 12px; border: 1px solid var(--border-color);
  background: var(--bg-primary); color: var(--text-secondary);
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; transition: all 0.2s; flex-shrink: 0;
}
.btn-back-header:hover { border-color: var(--accent); color: var(--accent); background: var(--accent-light); }

.modal-icon-wrap {
  width: 40px; height: 40px; border-radius: 12px;
  background: var(--accent-light); color: var(--accent);
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.modal-title-area { flex: 1; min-width: 0; }
.modal-title { font-size: 16px; font-weight: 800; margin: 0; color: var(--text-primary); }
.modal-subtitle { font-size: 12px; color: var(--text-muted); margin: 2px 0 0; font-weight: 600; }

.modal-body { padding: 24px; display: flex; flex-direction: column; gap: 20px; overflow-y: auto; }

.field-group { display: flex; flex-direction: column; gap: 8px; }
.field-label-premium { font-size: 12px; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; }

.modern-input-premium, .modern-select-premium {
  height: 48px; width: 100%; border-radius: 14px; border: 1px solid var(--border-color);
  background: var(--bg-primary); padding: 0 16px; font-size: 14px; font-weight: 600;
  color: var(--text-primary); outline: none; transition: 0.2s;
}
.modern-input-premium:focus, .modern-select-premium:focus { border-color: var(--accent); box-shadow: 0 0 0 4px var(--accent-light); }

.status-config-row {
  display: flex; justify-content: space-between; align-items: center;
  padding: 16px; background: var(--bg-primary); border-radius: 14px; border: 1px solid var(--border-color);
}
.status-text { flex: 1; }
.field-desc { font-size: 12px; color: var(--text-muted); margin-top: 4px; }
.toggle-wrap { display: flex; align-items: center; gap: 12px; }
.status-label { font-size: 13px; font-weight: 700; color: var(--text-muted); }
.status-label.active { color: var(--success); }

/* Switch Styles */
.modern-switch { position: relative; display: inline-block; width: 48px; height: 26px; flex-shrink: 0; }
.modern-switch input { opacity: 0; width: 0; height: 0; }
.switch-slider {
  position: absolute; cursor: pointer; inset: 0; background-color: var(--border-color);
  transition: .4s; border-radius: 34px;
}
.switch-slider:before {
  position: absolute; content: ""; height: 20px; width: 20px; left: 3px; bottom: 3px;
  background-color: white; transition: .4s; border-radius: 50%;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
input:checked + .switch-slider { background-color: var(--success); }
input:checked + .switch-slider:before { transform: translateX(22px); }

.field-hint-premium { font-size: 11px; color: var(--text-muted); margin-top: 2px; font-style: italic; }

.modal-footer-premium {
  padding: 20px 24px; border-top: 1px solid var(--border-color);
  background: var(--bg-primary);
}
.btn-save-premium {
  height: 48px; width: 100%; padding: 0 28px; border-radius: 14px; border: none;
  background: linear-gradient(135deg, var(--accent), #fb923c); color: #fff;
  font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px;
  box-shadow: 0 8px 20px -6px rgba(249,115,22,0.4); transition: 0.2s; font-size: 14px;
}
.btn-save-premium:hover { transform: translateY(-2px); box-shadow: 0 12px 24px -8px rgba(249,115,22,0.5); }
.btn-save-premium:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }

/* Modal Transition */
.modal-fade-enter-active { transition: opacity 0.3s ease; }
.modal-fade-enter-active .modal-container { animation: slideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1); }
.modal-fade-leave-active { transition: opacity 0.2s ease; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; }

@keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
.spin { animation: spin 1s linear infinite; }
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

@media (max-width: 900px) {
  .tenants-view { padding: 12px; gap: 16px; }
  .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; }
  .table-header { flex-direction: column; align-items: stretch; gap: 16px; }
  .search-input { width: 100%; }
  .search-input input { width: 100%; }
  .stat-card { padding: 16px; gap: 12px; }
  .stat-icon-bg { width: 44px; height: 44px; border-radius: 12px; }
  .stat-icon-bg svg { width: 20px; height: 20px; }
  .stat-value { font-size: 22px; }
}

@media (max-width: 768px) {
  /* Table to Cards Conversion */
  .custom-table-wrapper { overflow: visible; }
  .premium-table { display: block; width: 100%; border: none; }
  .premium-table thead { display: none; }
  .premium-table tbody { display: grid; grid-template-columns: 1fr; gap: 12px; }
  .premium-table tr { 
    display: flex; 
    flex-direction: column; 
    background: var(--bg-primary); 
    border: 1px solid var(--border-color); 
    border-radius: 16px; 
    padding: 16px;
    position: relative;
    gap: 12px;
  }
  .premium-table td { 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    padding: 0; 
    border: none;
    min-height: 24px;
    text-align: right !important;
  }
  
  .premium-table td:not(:first-child):not(:last-child)::before {
    content: attr(data-label);
    font-size: 11px;
    font-weight: 800;
    color: var(--text-muted);
    text-transform: uppercase;
    flex: 1;
    text-align: left;
  }

  .premium-table td:first-child { 
    padding-bottom: 12px; 
    border-bottom: 1px solid var(--border-color);
    margin-bottom: 4px;
    display: block;
    text-align: left !important;
  }
  
  .action-buttons {
    width: 100%;
    margin-top: 8px;
    padding-top: 12px;
    border-top: 1px solid var(--border-color);
    justify-content: center;
  }
  
  .btn-icon { flex: 1; height: 40px; }

  .tenant-text { max-width: none; }
  .t-name { font-size: 15px; }
  .t-slug { font-size: 11px; }
}

@media (max-width: 640px) {
  .modal-overlay { 
    align-items: flex-end !important; 
    padding: 0; 
    background: rgba(0,0,0,0.5);
    backdrop-filter: blur(8px);
  }
  .modal-container { 
    max-width: 100%; 
    border-radius: 32px 32px 0 0; 
    margin: 0;
    max-height: 94vh;
    animation: slideUpMobile 0.4s cubic-bezier(0.16, 1, 0.3, 1);
  }
  
  @keyframes slideUpMobile {
    from { transform: translateY(100%); }
    to { transform: translateY(0); }
  }

  .modal-body { padding: 20px; gap: 20px; }
  .modal-footer-premium { padding: 16px 20px env(safe-area-inset-bottom); }
  
  .stats-grid { grid-template-columns: 1fr; }
  .plan-dist-bar { padding: 16px; }
  .dist-legend { gap: 12px; }
  .legend-item { font-size: 11px; }

  .header-main h3 { font-size: 18px; }
  .header-main p { font-size: 12px; }
  
  .date-info { font-size: 12px; }
}
</style>
