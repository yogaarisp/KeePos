import { defineStore } from 'pinia';
import api from '../api';

export const useMaterialCategoryStore = defineStore('materialCategory', {
    state: () => ({
        categories: [],
        loading: false,
        error: null
    }),

    actions: {
        async fetchCategories() {
            this.loading = true;
            try {
                const response = await api.get('/material-categories');
                this.categories = response.data.data;
            } catch (error) {
                this.error = error.response?.data?.message || 'Gagal mengambil data kategori';
            } finally {
                this.loading = false;
            }
        },

        async saveCategory(category) {
            this.loading = true;
            try {
                let response;
                if (category.id) {
                    response = await api.put(`/material-categories/${category.id}`, category);
                } else {
                    response = await api.post('/material-categories', category);
                }
                return true;
            } catch (error) {
                this.error = error.response?.data?.message || 'Gagal menyimpan kategori';
                return false;
            } finally {
                this.loading = false;
            }
        },

        async deleteCategory(id) {
            this.loading = true;
            try {
                await api.delete(`/material-categories/${id}`);
                return true;
            } catch (error) {
                this.error = error.response?.data?.message || 'Gagal menghapus kategori';
                return false;
            } finally {
                this.loading = false;
            }
        }
    }
});
