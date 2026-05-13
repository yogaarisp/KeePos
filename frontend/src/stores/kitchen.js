import { defineStore } from 'pinia';
import api from '../api';

export const useKitchenStore = defineStore('kitchen', {
    state: () => ({
        items: [],
        categories: [],
        warehouseItems: [],
        pagination: {
            current_page: 1,
            last_page: 1,
            total: 0,
        },
        filters: {
            search: '',
            category_id: '',
            low_stock: false,
        },
        sortBy: 'name',
        sortDir: 'asc',
        loading: false,
        error: null,
    }),

    actions: {
        async fetchItems(page = 1) {
            this.loading = true;
            this.error = null;
            try {
                const response = await api.get('/kitchen', {
                    params: {
                        page,
                        limit: 50,
                        search: this.filters.search,
                        category_id: this.filters.category_id,
                        low_stock: this.filters.low_stock ? 1 : '',
                        sort_by: this.sortBy,
                        sort_dir: this.sortDir,
                    }
                });
                if (response.data.success) {
                    const { items, categories } = response.data.data;
                    this.items = items.data;
                    this.pagination = {
                        current_page: items.current_page,
                        last_page: items.last_page,
                        total: items.total,
                    };
                    this.categories = categories;
                }
            } catch (err) {
                this.error = 'Gagal memuat stok dapur';
            } finally {
                this.loading = false;
            }
        },

        async fetchWarehouseItems() {
            try {
                const response = await api.get('/kitchen/warehouse-items');
                if (response.data.success) {
                    this.warehouseItems = response.data.data;
                }
            } catch (err) {
                console.error('Gagal memuat item gudang:', err);
            }
        },

        async addManualItem(data) {
            this.loading = true;
            try {
                const response = await api.post('/kitchen', data);
                if (response.data.success) {
                    await this.fetchItems(this.pagination.current_page);
                    return { success: true, message: response.data.message };
                }
            } catch (err) {
                return { success: false, message: err.response?.data?.message || 'Gagal menambah stok dapur' };
            } finally {
                this.loading = false;
            }
        },

        async updateItem(id, data) {
            this.loading = true;
            try {
                const response = await api.put(`/kitchen/${id}`, data);
                if (response.data.success) {
                    await this.fetchItems(this.pagination.current_page);
                    return { success: true, message: response.data.message };
                }
            } catch (err) {
                return { success: false, message: err.response?.data?.message || 'Gagal update stok dapur' };
            } finally {
                this.loading = false;
            }
        },

        async deleteItem(id) {
            try {
                const response = await api.delete(`/kitchen/${id}`);
                if (response.data.success) {
                    await this.fetchItems(this.pagination.current_page);
                    return true;
                }
            } catch (err) {
                this.error = 'Gagal menghapus item';
                return false;
            }
        },

        async transferFromWarehouse(data) {
            this.loading = true;
            try {
                const response = await api.post('/kitchen/transfer', data);
                if (response.data.success) {
                    await this.fetchItems(this.pagination.current_page);
                    await this.fetchWarehouseItems();
                    return { success: true, message: response.data.message };
                }
            } catch (err) {
                return { success: false, message: err.response?.data?.message || 'Gagal melakukan transfer' };
            } finally {
                this.loading = false;
            }
        },

        async consumeStock(id, data) {
            this.loading = true;
            try {
                const response = await api.post(`/kitchen/${id}/consume`, data);
                if (response.data.success) {
                    await this.fetchItems(this.pagination.current_page);
                    return { success: true, message: response.data.message };
                }
            } catch (err) {
                return { success: false, message: err.response?.data?.message || 'Gagal konsumsi stok' };
            } finally {
                this.loading = false;
            }
        },

        async addStock(id, data) {
            this.loading = true;
            try {
                const response = await api.post(`/kitchen/${id}/add-stock`, data);
                if (response.data.success) {
                    await this.fetchItems(this.pagination.current_page);
                    return { success: true, message: response.data.message };
                }
            } catch (err) {
                return { success: false, message: err.response?.data?.message || 'Gagal tambah stok' };
            } finally {
                this.loading = false;
            }
        },

        async returnToWarehouse(id, data) {
            this.loading = true;
            try {
                const response = await api.post(`/kitchen/${id}/return`, data);
                if (response.data.success) {
                    await this.fetchItems(this.pagination.current_page);
                    await this.fetchWarehouseItems();
                    return { success: true, message: response.data.message };
                }
            } catch (err) {
                return { success: false, message: err.response?.data?.message || 'Gagal return ke gudang' };
            } finally {
                this.loading = false;
            }
        },

        // Conversion actions
        async getConversions(id) {
            try {
                const response = await api.get(`/kitchen/${id}/conversions`);
                if (response.data.success) {
                    return response.data.data;
                }
            } catch (err) {
                return null;
            }
        },

        async addConversion(id, data) {
            try {
                const response = await api.post(`/kitchen/${id}/conversions`, data);
                if (response.data.success) {
                    return { success: true, message: response.data.message };
                }
            } catch (err) {
                return { success: false, message: err.response?.data?.message || 'Gagal tambah konversi' };
            }
        },

        async updateConversion(itemId, conversionId, data) {
            try {
                const response = await api.put(`/kitchen/${itemId}/conversions/${conversionId}`, data);
                if (response.data.success) {
                    return { success: true, message: response.data.message };
                }
            } catch (err) {
                return { success: false, message: err.response?.data?.message || 'Gagal update konversi' };
            }
        },

        async deleteConversion(itemId, conversionId) {
            try {
                const response = await api.delete(`/kitchen/${itemId}/conversions/${conversionId}`);
                if (response.data.success) {
                    return { success: true, message: response.data.message };
                }
            } catch (err) {
                return { success: false, message: err.response?.data?.message || 'Gagal hapus konversi' };
            }
        },

        setSort(field) {
            if (this.sortBy === field) {
                this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortBy = field;
                this.sortDir = 'asc';
            }
            this.fetchItems(1);
        },

        resetFilters() {
            this.filters = { search: '', category_id: '', low_stock: false };
            this.sortBy = 'name';
            this.sortDir = 'asc';
        }
    }
});
