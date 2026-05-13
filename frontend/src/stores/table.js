import { defineStore } from 'pinia';
import api from '../api';

export const useTableStore = defineStore('table', {
    state: () => ({
        tables: [],
        loading: false,
        error: null,
    }),

    actions: {
        async fetchTables() {
            this.loading = true;
            try {
                const response = await api.get('/tables');
                if (response.data.success) {
                    this.tables = response.data.data;
                }
            } catch (err) {
                this.error = 'Gagal memuat data meja';
            } finally {
                this.loading = false;
            }
        },

        async saveTable(tableData) {
            this.loading = true;
            try {
                const url = tableData.id ? `/tables/${tableData.id}` : '/tables';
                const method = tableData.id ? 'put' : 'post';
                const response = await api[method](url, tableData);
                if (response.data.success) {
                    this.fetchTables();
                    return true;
                }
            } catch (err) {
                this.error = err.response?.data?.message || 'Gagal menyimpan data meja';
                return false;
            } finally {
                this.loading = false;
            }
        },

        async updateStatus(id, status) {
            try {
                const response = await api.patch(`/tables/${id}/status`, { status });
                if (response.data.success) {
                    const index = this.tables.findIndex(t => t.id === id);
                    if (index !== -1) {
                        this.tables[index].status = status;
                    }
                    return true;
                }
            } catch (err) {
                alert('Gagal mengupdate status meja');
                return false;
            }
        },

        async deleteTable(id) {
            this.loading = true;
            try {
                const response = await api.delete(`/tables/${id}`);
                if (response.data.success) {
                    await this.fetchTables();
                    return true;
                }
                return false;
            } catch (err) {
                this.error = err.response?.data?.message || 'Gagal menghapus meja';
                console.error('Delete table error:', err);
                return false;
            } finally {
                this.loading = false;
            }
        }
    }
});
