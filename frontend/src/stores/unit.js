import { defineStore } from 'pinia';
import api from '../api';

export const useUnitStore = defineStore('unit', {
    state: () => ({
        units: [],
        loading: false,
        error: null,
    }),

    actions: {
        async fetchUnits() {
            this.loading = true;
            this.error = null;
            try {
                const response = await api.get('/units');
                if (response.data.success) {
                    this.units = response.data.data;
                }
            } catch (err) {
                this.error = 'Gagal memuat data satuan';
                console.error(err);
            } finally {
                this.loading = false;
            }
        },

        async saveUnit(data) {
            this.loading = true;
            this.error = null;
            try {
                let response;
                if (data.id) {
                    response = await api.put(`/units/${data.id}`, data);
                } else {
                    response = await api.post('/units', data);
                }

                if (response.data.success) {
                    await this.fetchUnits();
                    return true;
                }
                return false;
            } catch (err) {
                this.error = err.response?.data?.message || 'Gagal menyimpan satuan';
                return false;
            } finally {
                this.loading = false;
            }
        },

        async deleteUnit(id) {
            this.loading = true;
            this.error = null;
            try {
                const response = await api.delete(`/units/${id}`);
                if (response.data.success) {
                    await this.fetchUnits();
                    return true;
                }
                return false;
            } catch (err) {
                this.error = err.response?.data?.message || 'Gagal menghapus satuan';
                return false;
            } finally {
                this.loading = false;
            }
        }
    }
});
