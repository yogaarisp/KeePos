import { defineStore } from 'pinia';
import api from '../api';

export const useWarehouseStore = defineStore('warehouse', {
    state: () => ({
        items: [],
        categories: [],
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
        loading: false,
        error: null,
    }),

    actions: {
        async fetchItems(page = 1) {
            this.loading = true;
            try {
                const response = await api.get('/warehouse', {
                    params: {
                        page,
                        search: this.filters.search,
                        category_id: this.filters.category_id,
                        low_stock: this.filters.low_stock ? 1 : ''
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
                this.error = 'Gagal memuat data stok';
            } finally {
                this.loading = false;
            }
        },

        async fetchCategories() {
            try {
                const response = await api.get('/material-categories');
                if (response.data.success) {
                    this.categories = response.data.data;
                }
            } catch (err) {
                console.error('Gagal memuat kategori bahan:', err);
            }
        },

        async addStock(id, data) {
            try {
                const response = await api.post(`/warehouse/${id}/add-stock`, data);
                if (response.data.success) {
                    const index = this.items.findIndex(item => item.id === id);
                    if (index !== -1) {
                        this.items[index] = response.data.data;
                    }
                    return true;
                }
            } catch (err) {
                this.error = err.response?.data?.message || 'Gagal menambah stok';
                return false;
            }
        },

        async reduceStock(id, data) {
            try {
                const response = await api.post(`/warehouse/${id}/reduce-stock`, data);
                if (response.data.success) {
                    const index = this.items.findIndex(item => item.id === id);
                    if (index !== -1) {
                        this.items[index] = response.data.data;
                    }
                    return true;
                }
            } catch (err) {
                this.error = err.response?.data?.message || 'Gagal mengurangi stok';
                return false;
            }
        },

        async addItem(data) {
            this.loading = true;
            try {
                const response = await api.post('/warehouse', data);
                if (response.data.success) {
                    await this.fetchItems(this.pagination.current_page);
                    return true;
                }
            } catch (err) {
                this.error = err.response?.data?.message || 'Gagal menambah bahan';
                return false;
            } finally {
                this.loading = false;
            }
        },

        async updateItem(id, data) {
            this.loading = true;
            try {
                const response = await api.put(`/warehouse/${id}`, data);
                if (response.data.success) {
                    await this.fetchItems(this.pagination.current_page);
                    return true;
                }
            } catch (err) {
                this.error = err.response?.data?.message || 'Gagal memperbarui bahan';
                return false;
            } finally {
                this.loading = false;
            }
        },

        async deleteItem(id) {
            if (!confirm('Apakah Anda yakin ingin menghapus bahan ini? Semua riwayat stok juga akan terpengaruh.')) return false;
            this.loading = true;
            try {
                const response = await api.delete(`/warehouse/${id}`);
                if (response.data.success) {
                    await this.fetchItems(this.pagination.current_page);
                    return true;
                }
            } catch (err) {
                this.error = 'Gagal menghapus bahan';
                return false;
            } finally {
                this.loading = false;
            }
        },

        resetFilters() {
            this.filters = {
                search: '',
                category_id: '',
                low_stock: false,
            };
        }
    }
});
