import { defineStore } from 'pinia';
import api from '../api';

export const useWasteStore = defineStore('waste', {
    state: () => ({
        reports: [],
        pagination: {
            current_page: 1,
            last_page: 1,
            total: 0,
        },
        filters: {
            start_date: '',
            end_date: '',
            source_type: '',
        },
        loading: false,
        error: null,
    }),

    actions: {
        async fetchReports(page = 1) {
            this.loading = true;
            try {
                const response = await api.get('/waste', {
                    params: {
                        page,
                        ...this.filters
                    }
                });
                if (response.data.success) {
                    const { data, current_page, last_page, total } = response.data.data;
                    this.reports = data;
                    this.pagination = { current_page, last_page, total };
                }
            } catch (err) {
                this.error = 'Gagal memuat laporan waste';
            } finally {
                this.loading = false;
            }
        },

        async submitReport(reportData) {
            this.loading = true;
            try {
                const response = await api.post('/waste', reportData);
                if (response.data.success) {
                    await this.fetchReports();
                    return true;
                }
            } catch (err) {
                this.error = err.response?.data?.message || 'Gagal mengirim laporan';
                return false;
            } finally {
                this.loading = false;
            }
        },

        async deleteReport(id) {
            if (!confirm('Hapus laporan ini?')) return;
            try {
                await api.delete(`/waste/${id}`);
                await this.fetchReports(this.pagination.current_page);
                return true;
            } catch (err) {
                alert('Gagal menghapus laporan');
                return false;
            }
        },

        resetFilters() {
            this.filters = {
                start_date: '',
                end_date: '',
                source_type: '',
            };
        }
    }
});
