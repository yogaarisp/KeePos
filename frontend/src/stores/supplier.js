import { defineStore } from 'pinia';
import api from '../api';

export const useSupplierStore = defineStore('supplier', {
  state: () => ({
    suppliers: [],
    stats: [],
    loading: false,
    error: null
  }),

  actions: {
    async fetchSuppliers() {
      this.loading = true;
      try {
        const response = await api.get('/suppliers');
        this.suppliers = response.data.data;
      } catch (err) {
        this.error = err.response?.data?.message || 'Gagal mengambil data supplier';
      } finally {
        this.loading = false;
      }
    },

    async fetchStats() {
      try {
        const response = await api.get('/suppliers/stats');
        this.stats = response.data.data;
      } catch (err) {
        console.error('Failed to fetch supplier stats:', err);
      }
    },

    async addSupplier(data) {
      try {
        const response = await api.post('/suppliers', data);
        this.suppliers.push(response.data.data);
        return { success: true, message: response.data.message };
      } catch (err) {
        return { 
          success: false, 
          message: err.response?.data?.message || 'Gagal menambah supplier' 
        };
      }
    },

    async updateSupplier(id, data) {
      try {
        const response = await api.put(`/suppliers/${id}`, data);
        const index = this.suppliers.findIndex(s => s.id === id);
        if (index !== -1) {
          this.suppliers[index] = response.data.data;
        }
        return { success: true, message: response.data.message };
      } catch (err) {
        return { 
          success: false, 
          message: err.response?.data?.message || 'Gagal memperbarui supplier' 
        };
      }
    },

    async deleteSupplier(id) {
      try {
        const response = await api.delete(`/suppliers/${id}`);
        this.suppliers = this.suppliers.filter(s => s.id !== id);
        return { success: true, message: response.data.message };
      } catch (err) {
        return { 
          success: false, 
          message: err.response?.data?.message || 'Gagal menghapus supplier' 
        };
      }
    }
  }
});
