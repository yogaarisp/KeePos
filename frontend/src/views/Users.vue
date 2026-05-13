<template>
  <div class="users-container">
    <!-- Header Hero -->
    <div class="page-hero">
      <div class="hero-content">
        <div class="hero-icon-wrap">
          <UsersIcon :size="24" />
        </div>
        <div>
          <h1 class="hero-title">Manajemen Pengguna</h1>
          <p class="hero-subtitle">Kelola hak akses administrator dan kasir Anda untuk keamanan sistem.</p>
        </div>
      </div>
      <div class="hero-actions">
        <div v-if="!canAddUser" class="limit-warning-badge">
          <ShieldAlert :size="14" />
          <span>Kuota user penuh</span>
        </div>
        <button 
          :class="['btn-primary', { 'btn-disabled': !canAddUser }]" 
          @click="canAddUser ? openModal() : null"
          :title="!canAddUser ? 'Kuota user Anda sudah penuh' : ''"
        >
          <UserPlus :size="18" /> Tambah User Baru
        </button>
      </div>
    </div>

    <!-- Filter Bar -->
    <div class="filter-glass-bar">
      <div class="search-box">
        <Search :size="18" class="search-icon" />
        <input 
          type="text" 
          v-model="userStore.filters.search" 
          placeholder="Cari nama, username, atau email..." 
          @keyup.enter="refresh"
        >
      </div>
      <div class="filter-group">
        <div class="filter-label"><Filter :size="14" /> Role</div>
        <select v-model="userStore.filters.role" @change="refresh" class="filter-select">
          <option value="">Semua Role</option>
          <option value="admin">Administrator</option>
          <option value="kasir">Kasir</option>
        </select>
      </div>
      <button class="btn-refresh" @click="reset" title="Reset Filter">
        <RotateCcw :size="16" />
      </button>
    </div>

    <!-- Users Table Card -->
    <div class="table-outer-card">
      <div v-if="userStore.loading && !userStore.users.length" class="table-loading">
        <div class="loading-spinner"></div>
        <span>Memuat data pengguna...</span>
      </div>

      <div v-else-if="!userStore.users.length" class="table-empty">
        <div class="empty-illu">
          <UsersIcon :size="48" />
        </div>
        <h3>Pengguna Tidak Ditemukan</h3>
        <p>Coba gunakan kata kunci pencarian yang berbeda.</p>
        <button class="btn-secondary" @click="reset">Reset Pencarian</button>
      </div>

      <div v-else class="table-scroll-wrap">
        <table class="premium-table">
          <thead>
            <tr>
              <th width="350"><div class="th-inner">Informasi Pengguna</div></th>
              <th v-if="authStore.user?.role === 'superadmin'"><div class="th-inner">Toko / Tenant</div></th>
              <th><div class="th-inner">Hak Akses / Role</div></th>
              <th class="text-center"><div class="th-inner justify-center">Status</div></th>
              <th class="text-center"><div class="th-inner justify-center">Opsi</div></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(u, idx) in userStore.users" :key="u.id" class="table-row" :style="{ animationDelay: (idx * 0.05) + 's' }">
              <td data-label="Pengguna">
                <div class="user-meta-wrap">
                  <div class="user-avatar-premium" :class="{ 'is-me': u.id === authStore.user?.id }">
                    {{ u.full_name.charAt(0).toUpperCase() }}
                    <div v-if="u.id === authStore.user?.id" class="me-indicator">Anda</div>
                  </div>
                  <div class="user-details">
                    <span class="u-name">{{ u.full_name }}</span>
                    <div class="u-sub">
                      <span class="u-username">@{{ u.username }}</span>
                      <span class="dot"></span>
                      <span class="u-email">{{ u.email }}</span>
                    </div>
                  </div>
                </div>
              </td>
              <td v-if="authStore.user?.role === 'superadmin'" data-label="Tenant">
                <div class="tenant-info-badge" v-if="u.tenant">
                  <Store :size="12" />
                  {{ u.tenant.name }}
                </div>
                <span v-else class="text-muted text-xs">Pusat / SaaS</span>
              </td>
              <td data-label="Role">
                <div class="role-badge-premium" :class="u.role">
                  <ShieldCheck v-if="u.role === 'admin'" :size="12" />
                  <ShieldAlert v-else-if="u.role === 'superadmin'" :size="12" />
                  <User :size="12" v-else />
                  {{ u.role === 'admin' ? 'Administrator' : (u.role === 'superadmin' ? 'SaaS Owner' : 'Kasir') }}
                </div>
              </td>
              <td data-label="Status" class="text-center">
                <div class="status-pill-premium" :class="u.is_active ? 'active' : 'inactive'">
                  <div class="s-dot"></div>
                  {{ u.is_active ? 'Aktif' : 'Non-aktif' }}
                </div>
              </td>
              <td class="text-center">
                <div class="action-btn-group">
                  <button class="btn-action edit" @click="openModal(u)" title="Edit User">
                    <Edit3 :size="14" />
                  </button>
                  <button 
                    v-if="u.id !== authStore.user?.id" 
                    class="btn-action delete" 
                    @click="handleDelete(u.id)"
                    title="Hapus User"
                  >
                    <Trash2 :size="14" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="pagination-area" v-if="userStore.pagination.last_page > 1">
        <div class="pagin-info">
          Menampilkan <strong>{{ userStore.users.length }}</strong> pengguna di halaman ini
        </div>
        <div class="pagin-btns">
          <button 
            class="pagin-btn"
            :disabled="userStore.pagination.current_page === 1" 
            @click="userStore.fetchUsers(userStore.pagination.current_page - 1)"
          >
            <ChevronLeft :size="18" />
          </button>
          <span class="page-current">{{ userStore.pagination.current_page }} / {{ userStore.pagination.last_page }}</span>
          <button 
            class="pagin-btn"
            :disabled="userStore.pagination.current_page === userStore.pagination.last_page" 
            @click="userStore.fetchUsers(userStore.pagination.current_page + 1)"
          >
            <ChevronRight :size="18" />
          </button>
        </div>
      </div>
    </div>

    <!-- Modern User Modal -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="modal.show" class="modal-backdrop" @click.self="modal.show = false">
          <div class="modal-panel-medium">
            <div class="modal-top">
              <div class="modal-header-content">
                <button class="btn-back-header" @click="modal.show = false">
                  <ArrowLeft :size="20" />
                </button>
                <div class="modal-icon-wrap">
                  <UserPlus v-if="!modal.form.id" :size="20" />
                  <UserCog v-else :size="20" />
                </div>
                <div class="modal-title-area">
                  <h3 class="modal-title">{{ modal.form.id ? 'Perbarui Akses' : 'Tambah User Baru' }}</h3>
                  <p class="modal-desc">{{ modal.form.id ? 'Ubah profil atau hak akses ' + modal.form.full_name : 'Berikan akses sistem kepada staf atau admin baru.' }}</p>
                </div>
              </div>
            </div>

            <div class="modal-content">
              <div class="form-grid">
                <div class="input-group">
                  <label class="input-label">Nama Lengkap</label>
                  <input type="text" v-model="modal.form.full_name" class="premium-input" placeholder="Contoh: Budi Santoso">
                </div>
                
                <div class="form-row-2">
                  <div class="input-group">
                    <label class="input-label">Username</label>
                    <div class="input-with-side">
                      <AtSign :size="14" class="side-icon" />
                      <input type="text" v-model="modal.form.username" class="premium-input pl-10" placeholder="budi_kasir">
                    </div>
                  </div>
                  <div class="input-group">
                    <label class="input-label">Email</label>
                    <input type="email" v-model="modal.form.email" class="premium-input" placeholder="budi@example.com">
                  </div>
                </div>

                <div class="form-row-2">
                  <div class="input-group">
                    <label class="input-label">Peran / Role</label>
                    <select v-model="modal.form.role" class="premium-input">
                      <option value="kasir">Kasir (Akses Terbatas)</option>
                      <option value="admin">Administrator (Akses Penuh)</option>
                    </select>
                  </div>
                  <div class="input-group">
                    <label class="input-label">{{ modal.form.id ? 'Password Baru' : 'Password' }}</label>
                    <div class="input-with-side">
                      <Lock :size="14" class="side-icon" />
                      <input type="password" v-model="modal.form.password" class="premium-input pl-10" placeholder="••••••••">
                    </div>
                    <span v-if="modal.form.id" class="input-hint">Biarkan kosong jika tidak ingin mengubah password.</span>
                  </div>
                </div>

                <div class="toggle-switch-card">
                  <div class="toggle-info">
                    <span class="toggle-l">Akses Login Aktif</span>
                    <span class="toggle-d">Matikan untuk menonaktifkan akun sementara.</span>
                  </div>
                  <label class="switch">
                    <input type="checkbox" v-model="modal.form.is_active">
                    <span class="slider"></span>
                  </label>
                </div>
              </div>
            </div>

            <div class="modal-bottom">
              <button class="btn-save" @click="handleSave" :disabled="userStore.loading">
                <Save :size="18" v-if="!userStore.loading" />
                <RefreshCw :size="18" class="spinning" v-else />
                {{ userStore.loading ? 'Menyimpan...' : (modal.form.id ? 'Perbarui Akses' : 'Buat User Baru') }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { onMounted, reactive, watch, onUnmounted, computed } from 'vue';
import { useUserStore } from '../stores/user';
import { showConfirm, showSuccess, showError } from '../utils/swal';
import { useAuthStore } from '../stores/auth';
import { 
  Users as UsersIcon, UserPlus, Search, Edit3, Trash2, X, Filter, 
  RotateCcw, ShieldCheck, User, Save, RefreshCw, ChevronLeft, 
  ChevronRight, AtSign, Lock, UserCog, ArrowLeft, ShieldAlert, Store
} from 'lucide-vue-next';

const userStore = useUserStore();
const authStore = useAuthStore();

const modal = reactive({
  show: false,
  form: {
    id: null,
    full_name: '',
    username: '',
    email: '',
    role: 'kasir',
    password: '',
    is_active: true
  }
});

const canAddUser = computed(() => {
  const tenant = authStore.user?.tenant;
  if (!tenant) return true;
  
  const plan = tenant.plan || 'free';
  let limit = 1;
  if (plan === 'basic') limit = 2;
  if (plan === 'pro') return true;
  
  return userStore.users.length < limit;
});

// Watch modal to toggle mobile nav and body scroll
watch(() => modal.show, (val) => {
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
  userStore.fetchUsers();
});

const refresh = () => userStore.fetchUsers(1);
const reset = () => {
  userStore.resetFilters();
  refresh();
};

const openModal = (user = null) => {
  if (user) {
    modal.form = { ...user, password: '' };
  } else {
    modal.form = {
      id: null,
      full_name: '',
      username: '',
      email: '',
      role: 'kasir',
      password: '',
      is_active: true
    };
  }
  modal.show = true;
};

const handleSave = async () => {
  if (!modal.form.full_name || !modal.form.username) {
    showError('Nama dan username wajib diisi!');
    return;
  }
  const success = await userStore.saveUser(modal.form);
  if (success) {
    modal.show = false;
    showSuccess(modal.mode === 'add' ? 'Pengguna berhasil ditambahkan!' : 'Pengguna berhasil diperbarui!');
  }
};

const handleDelete = async (id) => {
  const result = await showConfirm({
    title: 'Hapus Pengguna',
    text: 'Apakah Anda yakin ingin menghapus pengguna ini secara permanen?',
    icon: 'warning',
    confirmText: 'Ya, Hapus',
    cancelText: 'Batal'
  });
  
  if (result.isConfirmed) {
    const success = await userStore.deleteUser(id);
    if (success) {
      showSuccess('Pengguna berhasil dihapus!');
    }
  }
};
</script>

<style scoped>
.users-container { padding: 0; animation: fadeIn 0.4s ease; }

/* ── Hero ── */
.page-hero {
  display: flex; align-items: center; justify-content: space-between;
  background: linear-gradient(135deg, rgba(14,165,233,0.08) 0%, rgba(14,165,233,0.02) 100%);
  border: 1px solid rgba(14,165,233,0.1); border-radius: 20px;
  padding: 24px 32px; margin-bottom: 24px;
  overflow: hidden;
}
.hero-content { display: flex; align-items: center; gap: 18px; }
.hero-icon-wrap {
  width: 52px; height: 52px; border-radius: 16px;
  background: linear-gradient(135deg, #0ea5e9, #38bdf8);
  display: flex; align-items: center; justify-content: center;
  color: #fff; box-shadow: 0 8px 24px rgba(14, 165, 233, 0.25);
}
.hero-title { font-size: 22px; font-weight: 600; color: var(--text-primary); margin-bottom: 4px; }
.hero-subtitle { font-size: 14px; color: var(--text-secondary); font-weight: 500; }

.btn-primary.btn-disabled {
  background: var(--border-color);
  cursor: not-allowed;
  opacity: 0.7;
}

.limit-warning-badge {
  display: flex;
  align-items: center;
  gap: 6px;
  background: rgba(239, 68, 68, 0.1);
  color: #ef4444;
  padding: 8px 14px;
  border-radius: 10px;
  font-size: 11px;
  font-weight: 700;
  border: 1px solid rgba(239, 68, 68, 0.2);
}

.hero-actions { display: flex; align-items: center; gap: 12px; }

/* ── Filter Bar ── */
.filter-glass-bar {
  display: flex; align-items: center; gap: 16px;
  background: var(--bg-card); border: 1px solid var(--border-color);
  padding: 12px 16px; border-radius: 18px; margin-bottom: 24px;
}
.search-box { flex: 1; position: relative; }
.search-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-muted); }
.search-box input {
  width: 100%; height: 44px; padding-left: 44px;
  background: var(--bg-primary); border: 1px solid var(--border-color);
  border-radius: 14px; font-size: 14px; color: var(--text-primary); outline: none;
  transition: all 0.2s;
}
.filter-group { display: flex; align-items: center; gap: 10px; }
.filter-label { font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; display: flex; align-items: center; gap: 6px; }
.filter-select {
  height: 44px; padding: 0 16px; border-radius: 14px; background: var(--bg-primary); 
  border: 1px solid var(--border-color); color: var(--text-primary); font-weight: 700; font-size: 13px; outline: none;
}
.btn-refresh {
  width: 44px; height: 44px; border-radius: 14px; background: var(--bg-primary);
  border: 1px solid var(--border-color); color: var(--text-secondary); cursor: pointer;
}

/* ── Table ── */
.table-outer-card { 
  background: var(--bg-card); 
  border: 1px solid var(--border-color); 
  border-radius: 24px; 
  overflow: hidden;
  max-width: 100%;
}
.table-scroll-wrap { 
  overflow-x: auto; 
  -webkit-overflow-scrolling: touch;
}
.premium-table { width: 100%; border-collapse: collapse; text-align: left; }
.th-inner { padding: 16px 24px; font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.8px; display: flex; align-items: center; gap: 6px; }
.table-row { border-bottom: 1px solid var(--border-color); transition: 0.2s; animation: slideUp 0.4s ease both; }
.table-row:hover { background: var(--bg-primary); }
.premium-table td { padding: 14px 24px; vertical-align: middle; }

.user-meta-wrap { display: flex; align-items: center; gap: 14px; }
.user-avatar-premium {
  width: 44px; height: 44px; border-radius: 12px;
  background: var(--accent-light); color: var(--accent);
  display: flex; align-items: center; justify-content: center;
  font-weight: 600; font-size: 16px; position: relative;
}
.user-avatar-premium.is-me { background: var(--success-bg); color: var(--success); }
.me-indicator {
  position: absolute; bottom: -8px; left: 50%; transform: translateX(-50%);
  background: var(--success); color: #fff; font-size: 8px; font-weight: 700;
  padding: 2px 6px; border-radius: 100px; text-transform: uppercase;
}

.u-name { display: block; font-size: 14px; font-weight: 700; color: var(--text-primary); margin-bottom: 2px; }
.u-sub { display: flex; align-items: center; gap: 6px; font-size: 11px; color: var(--text-muted); }
.dot { width: 3px; height: 3px; background: currentColor; border-radius: 50%; }

.role-badge-premium {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 4px 12px; border-radius: 100px; font-size: 11px; font-weight: 700;
}
.role-badge-premium.admin { background: rgba(79,70,229,0.1); color: #4f46e5; }
.role-badge-premium.superadmin { background: rgba(249,115,22,0.1); color: #f97316; }
.role-badge-premium.kasir { background: rgba(34,197,94,0.1); color: #16a34a; }

.tenant-info-badge {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 4px 10px; border-radius: 8px; font-size: 11px; font-weight: 600;
  background: var(--bg-primary); border: 1px solid var(--border-color);
  color: var(--text-secondary);
}

.status-pill-premium {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 4px 12px; border-radius: 100px; font-size: 11px; font-weight: 600;
}
.status-pill-premium.active { background: var(--success-bg); color: var(--success); }
.status-pill-premium.inactive { background: var(--danger-bg); color: var(--danger); }
.s-dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }

.action-btn-group { display: flex; justify-content: center; gap: 8px; }
.btn-action {
  width: 32px; height: 32px; border-radius: 10px; border: 1px solid var(--border-color);
  background: var(--bg-primary); color: var(--text-muted); cursor: pointer; transition: 0.2s;
  display: flex; align-items: center; justify-content: center;
}
.btn-action:hover { border-color: var(--accent); color: var(--accent); background: var(--accent-light); }
.btn-action.delete:hover { border-color: var(--danger); color: var(--danger); background: var(--danger-bg); }

/* Pagination */
.pagination-area { padding: 20px 24px; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border-color); }
.pagin-info { font-size: 12px; color: var(--text-muted); font-weight: 600; }
.pagin-btns { display: flex; align-items: center; gap: 12px; }
.pagin-btn { width: 36px; height: 36px; border-radius: 10px; border: 1px solid var(--border-color); background: var(--bg-card); cursor: pointer; }
.page-current { font-size: 13px; font-weight: 600; color: var(--text-primary); }

/* ── Modal ── */
.modal-backdrop {
  position: fixed; inset: 0; z-index: 200;
  background: rgba(0,0,0,0.6); backdrop-filter: blur(6px);
  display: flex; align-items: center; justify-content: center; padding: 20px;
}
.modal-panel-medium {
  width: 100%; max-width: 580px; background: var(--bg-card);
  border-radius: 28px; border: 1px solid var(--border-color);
  box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); overflow: hidden;
}
.modal-top { display: flex; flex-direction: column; gap: 16px; padding: 24px; border-bottom: 1px solid var(--border-color); }
.modal-header-content { display: flex; align-items: center; gap: 16px; width: 100%; }
.modal-icon-wrap {
  width: 44px; height: 44px; border-radius: 12px; background: rgba(249,115,22,0.1); color: var(--accent);
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
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

.modal-content { padding: 24px; }
.form-grid { display: flex; flex-direction: column; gap: 20px; }
.form-row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.input-group { display: flex; flex-direction: column; gap: 8px; }
.input-label { 
  font-size: 11px; 
  font-weight: 700; 
  color: var(--text-muted); 
  text-transform: uppercase; 
  letter-spacing: 0.6px;
  margin-bottom: 4px;
}

.premium-input { 
  width: 100%; padding: 10px 14px; border-radius: 12px; 
  background: var(--bg-primary); border: 1px solid var(--border-color); 
  color: var(--text-primary); font-size: 13.5px; font-weight: 500; outline: none; 
  transition: 0.2s; 
}
.premium-input:focus { border-color: var(--accent); box-shadow: 0 0 0 4px var(--accent-light); }

.input-with-side { position: relative; }
.side-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-muted); }
.pl-10 { padding-left: 42px !important; }
.input-hint { font-size: 10px; color: var(--text-muted); font-style: italic; margin-top: 4px; }

.toggle-switch-card {
  display: flex; align-items: center; justify-content: space-between;
  padding: 16px 20px; background: var(--bg-primary); border-radius: 16px; border: 1px solid var(--border-color);
}
.toggle-l { display: block; font-size: 14px; font-weight: 700; color: var(--text-primary); }
.toggle-d { display: block; font-size: 11px; color: var(--text-muted); }

.switch { position: relative; width: 44px; height: 24px; cursor: pointer; }
.switch input { opacity: 0; width: 0; height: 0; }
.slider { position: absolute; inset: 0; background: var(--border-color); border-radius: 12px; transition: 0.3s; }
.slider:before { content: ""; position: absolute; height: 18px; width: 18px; left: 3px; bottom: 3px; background: white; border-radius: 50%; transition: 0.3s; }
input:checked + .slider { background: var(--success); }
input:checked + .slider:before { transform: translateX(20px); }

.modal-bottom { display: flex; justify-content: center; padding: 20px 24px; border-top: 1px solid var(--border-color); }
.btn-save {
  width: 100%; padding: 12px 32px; border-radius: 12px; background: var(--accent); color: #fff; border: none; font-weight: 700; cursor: pointer;
  display: flex; align-items: center; justify-content: center; gap: 8px;
}

@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
@keyframes spin { to { transform: rotate(360deg); } }

.table-loading { padding: 80px; text-align: center; }
.loading-spinner { width: 32px; height: 32px; border: 3px solid var(--accent-light); border-top-color: var(--accent); border-radius: 50%; animation: spin 0.8s linear infinite; margin: 0 auto 16px; }
.table-empty { text-align: center; padding: 60px 20px; }
.empty-illu { opacity: 0.2; margin-bottom: 16px; color: var(--text-muted); display: flex; justify-content: center; }

/* ── Responsive ── */
@media (max-width: 768px) {
  .users-container {
    overflow-x: hidden;
    padding: 4px;
  }
  .page-hero { flex-direction: row; gap: 12px; padding: 16px; align-items: center; margin-bottom: 12px; border-radius: 16px; }
  .hero-icon-wrap { width: 36px; height: 36px; border-radius: 10px; }
  .hero-icon-wrap svg { width: 18px; height: 18px; }
  .hero-title { font-size: 16px; }
  .hero-subtitle { display: none; }
  .hero-actions { display: none; }

  .filter-glass-bar { flex-direction: column; align-items: stretch; gap: 10px; padding: 12px; border-radius: 16px; margin-bottom: 12px; }
  .filter-select { height: 40px; font-size: 12px; }
  .btn-refresh { width: 40px; height: 40px; }

  .table-outer-card { border-radius: 16px; border: none; background: transparent; }
  
  /* Card Layout for Mobile (Like Orders.vue) */
  .table-scroll-wrap { padding: 0; overflow: visible; }
  .premium-table thead { display: none; }
  .premium-table tbody { display: flex; flex-direction: column; gap: 12px; }
  
  .table-row { 
    display: flex !important; 
    flex-direction: column; 
    background: var(--bg-card); 
    border: 1px solid var(--border-color); 
    border-radius: 16px; 
    padding: 16px; 
    gap: 12px;
    animation: slideUp 0.4s ease both;
  }
  .table-row:hover { background: var(--bg-card); }
  
  .premium-table td { 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    padding: 0 !important; 
    border: none;
    width: 100% !important;
  }
  
  .premium-table td::before {
    content: attr(data-label);
    font-size: 11px;
    font-weight: 600;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }
  
  .premium-table td:first-child { 
    flex-direction: column; 
    align-items: flex-start; 
    gap: 12px; 
    padding-bottom: 12px !important; 
    border-bottom: 1px solid var(--border-color);
  }
  .premium-table td:first-child::before { display: none; }
  
  .premium-table td:last-child { justify-content: center; padding-top: 8px !important; border-top: 1px dashed var(--border-color); }
  .premium-table td:last-child::before { display: none; }

  .user-avatar-premium { width: 40px; height: 40px; font-size: 14px; border-radius: 10px; }
  .u-name { font-size: 14px; }
  .u-sub { flex-direction: column; align-items: flex-start; gap: 2px; }
  .dot { display: none; }
  
  .pagination-area { flex-direction: column; gap: 12px; padding: 16px; align-items: center; border: none; }
  .pagin-btns { width: 100%; justify-content: center; }

  /* Modal Mobile */
  .modal-panel-medium { max-width: 100%; border-radius: 24px 24px 0 0; }
  .modal-backdrop { align-items: flex-end; padding: 0; background: rgba(0,0,0,0.35); backdrop-filter: blur(2px); }
  .modal-top { 
    padding: 24px; 
    flex-direction: column;
    gap: 16px;
  }
  .btn-back-header { width: 36px; height: 36px; }
  .modal-content { padding: 20px; }
  .modal-bottom { padding: 16px 20px 32px; }
  .form-row-2 { grid-template-columns: 1fr; }
}

@media (max-width: 480px) {
  .hero-title { font-size: 14px; }
  .btn-action.delete { display: flex; } /* Keep delete button visible but maybe smaller */
}
</style>
