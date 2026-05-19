<template>
  <div class="attendance-page">
    <!-- Hero -->
    <div class="page-hero">
      <div class="hero-content">
        <div class="hero-icon-wrap"><CalendarCheck :size="24" /></div>
        <div>
          <h1 class="hero-title">Absensi Karyawan</h1>
          <p class="hero-subtitle">Catat dan pantau kehadiran karyawan.</p>
        </div>
      </div>
      <button class="btn-primary" @click="openModal()"><Plus :size="18" /> Catat Absensi</button>
    </div>

    <!-- Filters -->
    <div class="filter-bar">
      <input v-model="filterMonth" type="month" class="filter-input" @change="fetchAttendance">
      <select v-model="filterEmployee" @change="fetchAttendance" class="filter-select">
        <option value="">Semua Karyawan</option>
        <option v-for="e in employees" :key="e.id" :value="e.id">{{ e.name }}</option>
      </select>
      <button class="btn-summary" @click="showSummary = !showSummary">
        <BarChart2 :size="16" /> {{ showSummary ? 'Sembunyikan' : 'Lihat' }} Rekap
      </button>
    </div>

    <!-- Monthly Summary -->
    <Transition name="slide-down">
      <div v-if="showSummary && summary.length" class="summary-card">
        <h3 class="summary-title">Rekap Bulan {{ filterMonth }}</h3>
        <div class="summary-table-wrap">
          <table class="summary-table">
            <thead>
              <tr>
                <th>Karyawan</th>
                <th>Jabatan</th>
                <th class="center">Hadir</th>
                <th class="center">Terlambat</th>
                <th class="center">Absen</th>
                <th class="center">Izin</th>
                <th class="center">Jam Kerja</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="s in summary" :key="s.employee_id">
                <td class="td-name">{{ s.employee_name }}</td>
                <td class="td-pos">{{ s.position || '-' }}</td>
                <td class="center"><span class="badge present">{{ s.present }}</span></td>
                <td class="center"><span class="badge late">{{ s.late }}</span></td>
                <td class="center"><span class="badge absent">{{ s.absent }}</span></td>
                <td class="center"><span class="badge leave">{{ s.leave }}</span></td>
                <td class="center">{{ s.work_hours?.toFixed(1) || 0 }} jam</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </Transition>

    <!-- Attendance Table -->
    <div class="section-card">
      <div v-if="loading" class="loading-state"><RefreshCw :size="28" class="spin" /></div>
      <div v-else-if="records.length" class="table-wrap">
        <table class="att-table">
          <thead>
            <tr>
              <th>Tanggal</th>
              <th>Karyawan</th>
              <th>Masuk</th>
              <th>Keluar</th>
              <th>Jam Kerja</th>
              <th>Status</th>
              <th>Catatan</th>
              <th class="text-right">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="r in records" :key="r.id">
              <td class="td-date">{{ formatDate(r.date) }}</td>
              <td class="td-name">{{ r.employee?.name }}</td>
              <td>{{ r.check_in || '-' }}</td>
              <td>{{ r.check_out || '-' }}</td>
              <td>{{ r.work_hours ? r.work_hours + ' jam' : '-' }}</td>
              <td><span class="status-badge" :class="r.status">{{ statusLabel(r.status) }}</span></td>
              <td class="td-notes">{{ r.notes || '-' }}</td>
              <td class="text-right">
                <div class="row-actions">
                  <button class="btn-icon" @click="openModal(r)"><Edit3 :size="14" /></button>
                  <button class="btn-icon danger" @click="deleteRecord(r)"><Trash2 :size="14" /></button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else class="empty-state">
        <CalendarCheck :size="40" class="empty-icon" />
        <p>Belum ada data absensi untuk periode ini.</p>
      </div>
    </div>

    <!-- Modal -->
    <Teleport to="body">
      <Transition name="modal-fade">
        <div v-if="modal.show" class="modal-overlay" @click.self="modal.show = false">
          <div class="modal-box">
            <header class="modal-header">
              <div class="modal-icon-wrap"><CalendarCheck :size="18" /></div>
              <h3>{{ modal.form.id ? 'Edit Absensi' : 'Catat Absensi' }}</h3>
              <button class="btn-close" @click="modal.show = false"><X :size="18" /></button>
            </header>
            <div class="modal-body">
              <div class="form-grid">
                <div class="field-group">
                  <label>Karyawan *</label>
                  <select v-model="modal.form.employee_id" class="modern-input">
                    <option value="">Pilih Karyawan</option>
                    <option v-for="e in employees" :key="e.id" :value="e.id">{{ e.name }}</option>
                  </select>
                </div>
                <div class="field-group">
                  <label>Tanggal *</label>
                  <input v-model="modal.form.date" type="date" class="modern-input">
                </div>
                <div class="field-group">
                  <label>Jam Masuk</label>
                  <input v-model="modal.form.check_in" type="time" class="modern-input">
                </div>
                <div class="field-group">
                  <label>Jam Keluar</label>
                  <input v-model="modal.form.check_out" type="time" class="modern-input">
                </div>
                <div class="field-group full">
                  <label>Status *</label>
                  <div class="status-options">
                    <button v-for="s in statusOptions" :key="s.value"
                      :class="['status-opt', s.value, { active: modal.form.status === s.value }]"
                      @click="modal.form.status = s.value" type="button">
                      {{ s.label }}
                    </button>
                  </div>
                </div>
                <div class="field-group full">
                  <label>Catatan</label>
                  <textarea v-model="modal.form.notes" rows="2" class="modern-input" placeholder="Catatan..."></textarea>
                </div>
              </div>
            </div>
            <footer class="modal-footer">
              <button class="btn-cancel" @click="modal.show = false">Batal</button>
              <button class="btn-save" @click="saveRecord" :disabled="saving">
                <RefreshCw v-if="saving" :size="16" class="spin" />
                <span v-else>Simpan</span>
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
import { useRoute } from 'vue-router';
import api from '../api';
import { showConfirm, showSuccess, showError } from '../utils/swal';
import { CalendarCheck, Plus, BarChart2, Edit3, Trash2, RefreshCw, X } from 'lucide-vue-next';

const route = useRoute();
const records  = ref([]);
const employees = ref([]);
const summary  = ref([]);
const loading  = ref(false);
const saving   = ref(false);
const showSummary = ref(false);

const filterMonth    = ref(new Date().toISOString().slice(0, 7));
const filterEmployee = ref(route.query.employee_id || '');

const statusOptions = [
  { value: 'present', label: '✅ Hadir' },
  { value: 'late',    label: '⏰ Terlambat' },
  { value: 'absent',  label: '❌ Absen' },
  { value: 'leave',   label: '📋 Izin' },
  { value: 'holiday', label: '🎉 Libur' },
];

const modal = reactive({
  show: false,
  form: { id: null, employee_id: '', date: new Date().toISOString().slice(0, 10), check_in: '', check_out: '', status: 'present', notes: '' },
});

const fetchEmployees = async () => {
  try {
    const res = await api.get('/employees', { params: { status: 'active' } });
    employees.value = res.data.data || [];
  } catch (err) {
    if (err.response?.status !== 403 && err.response?.data?.code !== 'PLAN_INSUFFICIENT') {
      console.error('Gagal memuat data karyawan:', err);
    }
  }
};

const fetchAttendance = async () => {
  loading.value = true;
  try {
    const res = await api.get('/attendance', {
      params: { month: filterMonth.value, employee_id: filterEmployee.value || undefined },
    });
    const data = res.data.data;
    records.value = Array.isArray(data) ? data : (data.data || []);
  } catch (err) {
    console.warn('Failed to load attendance:', err);
    records.value = [];
  } finally {
    loading.value = false;
  }
};

const fetchSummary = async () => {
  try {
    const res = await api.get('/attendance/summary/monthly', { params: { month: filterMonth.value } });
    summary.value = res.data.data?.summary || [];
  } catch (err) {
    if (err.response?.status !== 403 && err.response?.data?.code !== 'PLAN_INSUFFICIENT') {
      console.error('Gagal memuat summary absensi:', err);
    }
  }
};

onMounted(async () => {
  await fetchEmployees();
  await fetchAttendance();
  await fetchSummary();
});

const openModal = (r = null) => {
  if (r) {
    modal.form = {
      id: r.id, employee_id: r.employee_id, date: r.date?.slice(0, 10) || '',
      check_in: r.check_in || '', check_out: r.check_out || '',
      status: r.status, notes: r.notes || '',
    };
  } else {
    modal.form = { id: null, employee_id: filterEmployee.value || '', date: new Date().toISOString().slice(0, 10), check_in: '', check_out: '', status: 'present', notes: '' };
  }
  modal.show = true;
};

const saveRecord = async () => {
  if (!modal.form.employee_id || !modal.form.date) return showError('Karyawan dan tanggal wajib diisi');
  saving.value = true;
  try {
    const payload = { ...modal.form };
    let res;
    if (modal.form.id) {
      res = await api.put(`/attendance/${modal.form.id}`, payload);
    } else {
      res = await api.post('/attendance', payload);
    }
    if (res.data.success) {
      showSuccess('Absensi berhasil disimpan!');
      modal.show = false;
      fetchAttendance();
      fetchSummary();
    }
  } catch (err) { showError(err.response?.data?.message || 'Gagal menyimpan'); }
  finally { saving.value = false; }
};

const deleteRecord = async (r) => {
  const res = await showConfirm({ title: 'Hapus Absensi?', text: 'Data absensi ini akan dihapus.', icon: 'warning', confirmText: 'Hapus' });
  if (!res.isConfirmed) return;
  await api.delete(`/attendance/${r.id}`);
  showSuccess('Absensi dihapus');
  fetchAttendance();
  fetchSummary();
};

const formatDate = (d) => d ? new Date(d).toLocaleDateString('id-ID', { weekday: 'short', day: 'numeric', month: 'short' }) : '-';
const statusLabel = (s) => ({ present: 'Hadir', late: 'Terlambat', absent: 'Absen', leave: 'Izin', holiday: 'Libur' }[s] || s);
</script>

<style scoped>
.attendance-page { animation: fadeIn 0.4s ease; }
.page-hero { display: flex; align-items: center; justify-content: space-between; background: linear-gradient(135deg, rgba(249,115,22,0.08), rgba(249,115,22,0.02)); border: 1px solid rgba(249,115,22,0.1); border-radius: 20px; padding: 24px 28px; margin-bottom: 20px; }
.hero-content { display: flex; align-items: center; gap: 16px; }
.hero-icon-wrap { width: 50px; height: 50px; border-radius: 14px; background: linear-gradient(135deg, var(--accent), #fb923c); display: flex; align-items: center; justify-content: center; color: #fff; }
.hero-title { font-size: 20px; font-weight: 700; margin: 0 0 4px; }
.hero-subtitle { font-size: 13px; color: var(--text-muted); margin: 0; }

.filter-bar { display: flex; gap: 12px; margin-bottom: 16px; flex-wrap: wrap; }
.filter-input, .filter-select { height: 40px; padding: 0 12px; border-radius: 10px; border: 1.5px solid var(--border-color); background: var(--bg-card); color: var(--text-primary); font-size: 13px; outline: none; }
.filter-select { min-width: 180px; }
.btn-summary { height: 40px; padding: 0 16px; border-radius: 10px; border: 1.5px solid var(--border-color); background: var(--bg-card); color: var(--text-secondary); font-size: 13px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: 0.2s; }
.btn-summary:hover { border-color: var(--accent); color: var(--accent); }

/* Summary */
.summary-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 20px; margin-bottom: 20px; }
.summary-title { font-size: 15px; font-weight: 700; margin: 0 0 14px; }
.summary-table-wrap { overflow-x: auto; }
.summary-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.summary-table th { padding: 10px 14px; text-align: left; font-size: 11px; font-weight: 800; color: var(--text-muted); text-transform: uppercase; border-bottom: 1px solid var(--border-color); }
.summary-table td { padding: 10px 14px; border-bottom: 1px solid var(--border-color); }
.summary-table .center { text-align: center; }
.td-name { font-weight: 700; }
.td-pos { color: var(--text-muted); font-size: 12px; }
.badge { display: inline-block; padding: 2px 8px; border-radius: 6px; font-size: 11px; font-weight: 800; }
.badge.present { background: rgba(34,197,94,0.1); color: #16a34a; }
.badge.late    { background: rgba(234,179,8,0.1); color: #b45309; }
.badge.absent  { background: rgba(239,68,68,0.1); color: #dc2626; }
.badge.leave   { background: rgba(99,102,241,0.1); color: #4f46e5; }

/* Table */
.section-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; overflow: hidden; }
.loading-state { display: flex; justify-content: center; padding: 60px; }
.table-wrap { overflow-x: auto; }
.att-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.att-table th { padding: 12px 16px; text-align: left; font-size: 11px; font-weight: 800; color: var(--text-muted); text-transform: uppercase; border-bottom: 1px solid var(--border-color); }
.att-table td { padding: 12px 16px; border-bottom: 1px solid var(--border-color); vertical-align: middle; }
.td-date { font-weight: 600; white-space: nowrap; }
.td-notes { color: var(--text-muted); font-size: 12px; max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.text-right { text-align: right; }
.row-actions { display: flex; gap: 6px; justify-content: flex-end; }
.btn-icon { width: 30px; height: 30px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-primary); color: var(--text-secondary); cursor: pointer; display: flex; align-items: center; justify-content: center; transition: 0.2s; }
.btn-icon:hover { border-color: var(--accent); color: var(--accent); }
.btn-icon.danger:hover { border-color: #ef4444; color: #ef4444; }

.status-badge { display: inline-block; padding: 3px 10px; border-radius: 50px; font-size: 11px; font-weight: 800; }
.status-badge.present { background: rgba(34,197,94,0.1); color: #16a34a; }
.status-badge.late    { background: rgba(234,179,8,0.1); color: #b45309; }
.status-badge.absent  { background: rgba(239,68,68,0.1); color: #dc2626; }
.status-badge.leave   { background: rgba(99,102,241,0.1); color: #4f46e5; }
.status-badge.holiday { background: rgba(249,115,22,0.1); color: var(--accent); }

.empty-state { text-align: center; padding: 60px 20px; color: var(--text-muted); }
.empty-icon { opacity: 0.3; margin-bottom: 12px; }

/* Modal */
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.55); backdrop-filter: blur(6px); display: flex; align-items: center; justify-content: center; z-index: 2000; padding: 20px; }
.modal-box { background: var(--bg-card); width: 100%; max-width: 480px; border-radius: 20px; border: 1px solid var(--border-color); animation: slideUp 0.3s cubic-bezier(0.16,1,0.3,1); }
.modal-header { padding: 18px 22px; display: flex; align-items: center; gap: 12px; border-bottom: 1px solid var(--border-color); }
.modal-icon-wrap { width: 36px; height: 36px; border-radius: 10px; background: rgba(249,115,22,0.1); color: var(--accent); display: flex; align-items: center; justify-content: center; }
.modal-header h3 { font-size: 15px; font-weight: 700; margin: 0; flex: 1; }
.btn-close { width: 28px; height: 28px; border-radius: 50%; border: none; background: var(--bg-primary); color: var(--text-muted); cursor: pointer; display: flex; align-items: center; justify-content: center; }
.modal-body { padding: 18px 22px; }
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.field-group { display: flex; flex-direction: column; gap: 6px; }
.field-group.full { grid-column: span 2; }
.field-group label { font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; }
.modern-input { width: 100%; padding: 9px 12px; border-radius: 10px; border: 1.5px solid var(--border-color); background: var(--bg-primary); color: var(--text-primary); font-size: 13px; outline: none; }
.modern-input:focus { border-color: var(--accent); }
.status-options { display: flex; gap: 8px; flex-wrap: wrap; }
.status-opt { padding: 7px 14px; border-radius: 10px; border: 1.5px solid var(--border-color); background: var(--bg-primary); font-size: 12px; font-weight: 700; cursor: pointer; transition: 0.2s; }
.status-opt.active.present { background: rgba(34,197,94,0.15); border-color: #22c55e; color: #16a34a; }
.status-opt.active.late    { background: rgba(234,179,8,0.15); border-color: #eab308; color: #b45309; }
.status-opt.active.absent  { background: rgba(239,68,68,0.15); border-color: #ef4444; color: #dc2626; }
.status-opt.active.leave   { background: rgba(99,102,241,0.15); border-color: #6366f1; color: #4f46e5; }
.status-opt.active.holiday { background: rgba(249,115,22,0.15); border-color: var(--accent); color: var(--accent); }
.modal-footer { padding: 14px 22px; display: flex; gap: 10px; border-top: 1px solid var(--border-color); }
.btn-cancel { flex: 1; height: 42px; border-radius: 12px; border: 1.5px solid var(--border-color); background: transparent; font-weight: 700; cursor: pointer; }
.btn-save { flex: 2; height: 42px; border-radius: 12px; border: none; background: var(--accent); color: #fff; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; }
.btn-save:disabled { opacity: 0.6; cursor: not-allowed; }

.slide-down-enter-active, .slide-down-leave-active { transition: all 0.3s ease; }
.slide-down-enter-from, .slide-down-leave-to { opacity: 0; transform: translateY(-10px); }
.modal-fade-enter-active { transition: opacity 0.25s; }
.modal-fade-leave-active { transition: opacity 0.2s; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; }
@keyframes slideUp { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }
@keyframes fadeIn  { from { opacity: 0; } to { opacity: 1; } }
.spin { animation: spin 1s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

@media (max-width: 768px) {
  .page-hero { flex-direction: column; gap: 16px; }
  .form-grid { grid-template-columns: 1fr; }
  .field-group.full { grid-column: span 1; }
}
</style>
