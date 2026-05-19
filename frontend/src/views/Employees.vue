<template>
  <div class="employees-page">
    <!-- Hero -->
    <div class="page-hero">
      <div class="hero-content">
        <div class="hero-icon-wrap"><Users :size="24" /></div>
        <div>
          <h1 class="hero-title">Manajemen Karyawan</h1>
          <p class="hero-subtitle">Kelola data karyawan toko Anda.</p>
        </div>
      </div>
      <button class="btn-primary" @click="openModal()">
        <Plus :size="18" /> Tambah Karyawan
      </button>
    </div>

    <!-- Search -->
    <div class="filter-bar">
      <div class="search-box">
        <Search :size="16" class="search-icon" />
        <input v-model="search" type="text" placeholder="Cari nama atau jabatan..." class="search-input" @input="fetchEmployees">
      </div>
      <select v-model="filterStatus" @change="fetchEmployees" class="filter-select">
        <option value="">Semua Status</option>
        <option value="active">Aktif</option>
        <option value="inactive">Nonaktif</option>
      </select>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="loading-state">
      <RefreshCw :size="28" class="spin" />
    </div>

    <!-- Employee Grid -->
    <div v-else-if="employees.length" class="employee-grid">
      <div v-for="emp in employees" :key="emp.id" class="emp-card">
        <div class="emp-avatar-wrap">
          <img v-if="emp.photo_url" :src="emp.photo_url" class="emp-photo" :alt="emp.name">
          <div v-else class="emp-initial">{{ emp.name.charAt(0) }}</div>
          <span class="emp-status-dot" :class="emp.status"></span>
        </div>
        <div class="emp-info">
          <h3 class="emp-name">{{ emp.name }}</h3>
          <p class="emp-position">{{ emp.position || 'Karyawan' }}</p>
          <p class="emp-dept" v-if="emp.department">{{ emp.department }}</p>
          <p class="emp-phone" v-if="emp.phone">📞 {{ emp.phone }}</p>
          <p class="emp-join" v-if="emp.join_date">📅 Bergabung {{ formatDate(emp.join_date) }}</p>
        </div>
        <div class="emp-actions">
          <button class="btn-icon" @click="openModal(emp)" title="Edit"><Edit3 :size="15" /></button>
          <button class="btn-icon" @click="goAttendance(emp.id)" title="Lihat Absensi"><Calendar :size="15" /></button>
          <button class="btn-icon danger" @click="deleteEmployee(emp)" title="Hapus"><Trash2 :size="15" /></button>
        </div>
      </div>
    </div>

    <!-- Empty -->
    <div v-else class="empty-state">
      <Users :size="48" class="empty-icon" />
      <h3>Belum Ada Karyawan</h3>
      <p>Tambahkan data karyawan pertama Anda.</p>
      <button class="btn-primary" @click="openModal()"><Plus :size="16" /> Tambah Karyawan</button>
    </div>

    <!-- Modal -->
    <Teleport to="body">
      <Transition name="modal-fade">
        <div v-if="modal.show" class="modal-overlay" @click.self="modal.show = false">
          <div class="modal-box">
            <header class="modal-header">
              <div class="modal-icon-wrap"><Users :size="18" /></div>
              <div>
                <h3>{{ modal.form.id ? 'Edit Karyawan' : 'Tambah Karyawan' }}</h3>
              </div>
              <button class="btn-close" @click="modal.show = false"><X :size="18" /></button>
            </header>
            <div class="modal-body">
              <!-- Photo Upload -->
              <div class="photo-upload" @click="$refs.photoInput.click()">
                <img v-if="photoPreview || modal.form.photo_url" :src="photoPreview || modal.form.photo_url" class="photo-preview">
                <div v-else class="photo-placeholder"><Camera :size="24" /><span>Foto</span></div>
                <input type="file" ref="photoInput" @change="handlePhoto" accept="image/*" hidden>
              </div>

              <div class="form-grid">
                <div class="field-group full">
                  <label>Nama Lengkap *</label>
                  <input v-model="modal.form.name" type="text" class="modern-input" placeholder="Nama karyawan">
                </div>
                <div class="field-group">
                  <label>Jabatan</label>
                  <input v-model="modal.form.position" type="text" class="modern-input" placeholder="Kasir, Chef, dll">
                </div>
                <div class="field-group">
                  <label>Departemen</label>
                  <input v-model="modal.form.department" type="text" class="modern-input" placeholder="Dapur, Pelayanan, dll">
                </div>
                <div class="field-group">
                  <label>No. Telepon</label>
                  <input v-model="modal.form.phone" type="text" class="modern-input" placeholder="08xx-xxxx-xxxx">
                </div>
                <div class="field-group">
                  <label>Tanggal Bergabung</label>
                  <input v-model="modal.form.join_date" type="date" class="modern-input">
                </div>
                <div class="field-group">
                  <label>Gaji Pokok (Rp)</label>
                  <input v-model="modal.form.base_salary" type="number" class="modern-input" placeholder="0">
                </div>
                <div class="field-group">
                  <label>Status</label>
                  <select v-model="modal.form.status" class="modern-input">
                    <option value="active">Aktif</option>
                    <option value="inactive">Nonaktif</option>
                  </select>
                </div>
                <div class="field-group full">
                  <label>Catatan</label>
                  <textarea v-model="modal.form.notes" rows="2" class="modern-input" placeholder="Catatan tambahan..."></textarea>
                </div>
              </div>
            </div>
            <footer class="modal-footer">
              <button class="btn-cancel" @click="modal.show = false">Batal</button>
              <button class="btn-save" @click="saveEmployee" :disabled="saving">
                <RefreshCw v-if="saving" :size="16" class="spin" />
                <span v-else>{{ modal.form.id ? 'Simpan Perubahan' : 'Tambah Karyawan' }}</span>
              </button>
            </footer>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '../api';
import { showConfirm, showSuccess, showError } from '../utils/swal';
import { Users, Plus, Search, Edit3, Trash2, Calendar, RefreshCw, X, Camera } from 'lucide-vue-next';

const router   = useRouter();
const employees = ref([]);
const loading   = ref(false);
const saving    = ref(false);
const search    = ref('');
const filterStatus = ref('');
const photoFile = ref(null);
const photoPreview = ref(null);

const modal = reactive({
  show: false,
  form: { id: null, name: '', position: '', department: '', phone: '', join_date: '', base_salary: 0, status: 'active', notes: '', photo_url: null },
});

const fetchEmployees = async () => {
  loading.value = true;
  try {
    const res = await api.get('/employees', { params: { search: search.value, status: filterStatus.value } });
    employees.value = res.data.data || [];
  } catch (err) {
    if (err.response?.status !== 403 && err.response?.data?.code !== 'PLAN_INSUFFICIENT') {
      showError('Gagal memuat data karyawan');
    } else {
      console.warn('Employees access blocked: Insufficient plan.');
    }
  } finally {
    loading.value = false;
  }
};

onMounted(fetchEmployees);

const openModal = (emp = null) => {
  photoFile.value = null;
  photoPreview.value = null;
  if (emp) {
    modal.form = { ...emp };
  } else {
    modal.form = { id: null, name: '', position: '', department: '', phone: '', join_date: '', base_salary: 0, status: 'active', notes: '', photo_url: null };
  }
  modal.show = true;
};

const handlePhoto = (e) => {
  const file = e.target.files[0];
  if (file) { photoFile.value = file; photoPreview.value = URL.createObjectURL(file); }
};

const saveEmployee = async () => {
  if (!modal.form.name) return showError('Nama karyawan wajib diisi');
  saving.value = true;
  try {
    const fd = new FormData();
    Object.entries(modal.form).forEach(([k, v]) => {
      if (v !== null && v !== undefined && k !== 'photo_url') fd.append(k, v);
    });
    if (photoFile.value) fd.append('photo', photoFile.value);
    if (modal.form.id) fd.append('_method', 'PUT');

    const url = modal.form.id ? `/employees/${modal.form.id}` : '/employees';
    const res = await api.post(url, fd, { headers: { 'Content-Type': 'multipart/form-data' } });
    if (res.data.success) {
      showSuccess(modal.form.id ? 'Data karyawan diperbarui!' : 'Karyawan berhasil ditambahkan!');
      modal.show = false;
      fetchEmployees();
    }
  } catch (err) { showError(err.response?.data?.message || 'Gagal menyimpan'); }
  finally { saving.value = false; }
};

const deleteEmployee = async (emp) => {
  const r = await showConfirm({ title: 'Hapus Karyawan?', text: `"${emp.name}" akan dihapus permanen.`, icon: 'warning', confirmText: 'Hapus' });
  if (!r.isConfirmed) return;
  try {
    await api.delete(`/employees/${emp.id}`);
    showSuccess('Karyawan dihapus');
    fetchEmployees();
  } catch { showError('Gagal menghapus'); }
};

const goAttendance = (id) => router.push({ name: 'Attendance', query: { employee_id: id } });

const formatDate = (d) => d ? new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '-';
</script>

<style scoped>
.employees-page { animation: fadeIn 0.4s ease; }
.page-hero { display: flex; align-items: center; justify-content: space-between; background: linear-gradient(135deg, rgba(249,115,22,0.08), rgba(249,115,22,0.02)); border: 1px solid rgba(249,115,22,0.1); border-radius: 20px; padding: 24px 28px; margin-bottom: 20px; }
.hero-content { display: flex; align-items: center; gap: 16px; }
.hero-icon-wrap { width: 50px; height: 50px; border-radius: 14px; background: linear-gradient(135deg, var(--accent), #fb923c); display: flex; align-items: center; justify-content: center; color: #fff; }
.hero-title { font-size: 20px; font-weight: 700; margin: 0 0 4px; }
.hero-subtitle { font-size: 13px; color: var(--text-muted); margin: 0; }

.filter-bar { display: flex; gap: 12px; margin-bottom: 20px; }
.search-box { position: relative; flex: 1; }
.search-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); }
.search-input { width: 100%; height: 42px; padding: 0 12px 0 36px; border-radius: 12px; border: 1.5px solid var(--border-color); background: var(--bg-card); color: var(--text-primary); font-size: 13px; outline: none; }
.search-input:focus { border-color: var(--accent); }
.filter-select { height: 42px; padding: 0 14px; border-radius: 12px; border: 1.5px solid var(--border-color); background: var(--bg-card); color: var(--text-primary); font-size: 13px; outline: none; }

.loading-state { display: flex; justify-content: center; padding: 60px; color: var(--text-muted); }
.spin { animation: spin 1s linear infinite; }

.employee-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 16px; }
.emp-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 18px; padding: 20px; display: flex; flex-direction: column; align-items: center; gap: 12px; text-align: center; transition: 0.2s; }
.emp-card:hover { border-color: var(--accent); transform: translateY(-3px); }
.emp-avatar-wrap { position: relative; }
.emp-photo { width: 72px; height: 72px; border-radius: 50%; object-fit: cover; border: 3px solid var(--border-color); }
.emp-initial { width: 72px; height: 72px; border-radius: 50%; background: var(--accent); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 28px; font-weight: 800; }
.emp-status-dot { position: absolute; bottom: 4px; right: 4px; width: 12px; height: 12px; border-radius: 50%; border: 2px solid var(--bg-card); }
.emp-status-dot.active { background: #22c55e; }
.emp-status-dot.inactive { background: #94a3b8; }
.emp-name { font-size: 15px; font-weight: 700; margin: 0; }
.emp-position { font-size: 12px; color: var(--accent); font-weight: 600; margin: 2px 0; }
.emp-dept, .emp-phone, .emp-join { font-size: 11px; color: var(--text-muted); margin: 1px 0; }
.emp-actions { display: flex; gap: 8px; }
.btn-icon { width: 34px; height: 34px; border-radius: 10px; border: 1px solid var(--border-color); background: var(--bg-primary); color: var(--text-secondary); cursor: pointer; display: flex; align-items: center; justify-content: center; transition: 0.2s; }
.btn-icon:hover { border-color: var(--accent); color: var(--accent); }
.btn-icon.danger:hover { border-color: #ef4444; color: #ef4444; }

.empty-state { text-align: center; padding: 80px 20px; color: var(--text-muted); }
.empty-icon { opacity: 0.3; margin-bottom: 16px; }
.empty-state h3 { font-size: 18px; font-weight: 600; margin-bottom: 8px; }
.empty-state p { margin-bottom: 20px; }

/* Modal */
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.55); backdrop-filter: blur(6px); display: flex; align-items: center; justify-content: center; z-index: 2000; padding: 20px; }
.modal-box { background: var(--bg-card); width: 100%; max-width: 520px; border-radius: 20px; border: 1px solid var(--border-color); max-height: 90vh; overflow-y: auto; animation: slideUp 0.3s cubic-bezier(0.16,1,0.3,1); }
.modal-header { padding: 20px 24px; display: flex; align-items: center; gap: 12px; border-bottom: 1px solid var(--border-color); }
.modal-icon-wrap { width: 38px; height: 38px; border-radius: 10px; background: rgba(249,115,22,0.1); color: var(--accent); display: flex; align-items: center; justify-content: center; }
.modal-header h3 { font-size: 16px; font-weight: 700; margin: 0; flex: 1; }
.btn-close { width: 30px; height: 30px; border-radius: 50%; border: none; background: var(--bg-primary); color: var(--text-muted); cursor: pointer; display: flex; align-items: center; justify-content: center; }
.modal-body { padding: 20px 24px; }
.photo-upload { width: 80px; height: 80px; border-radius: 50%; border: 2px dashed var(--border-color); cursor: pointer; overflow: hidden; margin: 0 auto 16px; display: flex; align-items: center; justify-content: center; }
.photo-preview { width: 100%; height: 100%; object-fit: cover; }
.photo-placeholder { display: flex; flex-direction: column; align-items: center; gap: 4px; color: var(--text-muted); font-size: 10px; }
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.field-group { display: flex; flex-direction: column; gap: 6px; }
.field-group.full { grid-column: span 2; }
.field-group label { font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; }
.modern-input { width: 100%; padding: 9px 12px; border-radius: 10px; border: 1.5px solid var(--border-color); background: var(--bg-primary); color: var(--text-primary); font-size: 13px; outline: none; }
.modern-input:focus { border-color: var(--accent); }
.modal-footer { padding: 16px 24px; display: flex; gap: 10px; border-top: 1px solid var(--border-color); }
.btn-cancel { flex: 1; height: 42px; border-radius: 12px; border: 1.5px solid var(--border-color); background: transparent; font-weight: 700; cursor: pointer; }
.btn-save { flex: 2; height: 42px; border-radius: 12px; border: none; background: var(--accent); color: #fff; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; }
.btn-save:disabled { opacity: 0.6; cursor: not-allowed; }

.modal-fade-enter-active { transition: opacity 0.25s; }
.modal-fade-leave-active { transition: opacity 0.2s; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; }
@keyframes slideUp { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }
@keyframes fadeIn  { from { opacity: 0; } to { opacity: 1; } }
@keyframes spin    { to { transform: rotate(360deg); } }

@media (max-width: 768px) {
  .page-hero { flex-direction: column; gap: 16px; }
  .employee-grid { grid-template-columns: 1fr 1fr; }
  .form-grid { grid-template-columns: 1fr; }
  .field-group.full { grid-column: span 1; }
}
</style>
