import { defineStore } from 'pinia';
import api from '../api';

export const useProductStore = defineStore('product', {
    state: () => ({
        products: [],
        categories: [],
        pagination: {
            current_page: 1,
            last_page: 1,
            total: 0,
        },
        filters: {
            search: '',
            category_id: '',
        },
        loading: false,
        error: null,
    }),

    actions: {
        async fetchProducts(page = 1) {
            this.loading = true;
            try {
                const response = await api.get('/products', {
                    params: {
                        page,
                        search: this.filters.search,
                        category_id: this.filters.category_id,
                    }
                });
                if (response.data.success) {
                    const { products, categories } = response.data.data;
                    this.products = products.data;
                    this.pagination = {
                        current_page: products.current_page,
                        last_page: products.last_page,
                        total: products.total,
                    };
                    this.categories = categories;
                }
            } catch (err) {
                this.error = 'Gagal memuat daftar menu';
            } finally {
                this.loading = false;
            }
        },
        async fetchCategories() {
            try {
                const response = await api.get('/categories');
                if (response.data.success) {
                    this.categories = response.data.data;
                }
            } catch (err) {
                console.error('Gagal memuat kategori:', err);
            }
        },

        async saveProduct(formData) {
            this.loading = true;
            this.error = null;
            try {
                const url = formData.get('id') ? `/products/${formData.get('id')}` : '/products';

                // Laravel spoofing for PUT if editing with files
                if (formData.get('id')) {
                    formData.append('_method', 'PUT');
                }

                const response = await api.post(url, formData, {
                    headers: { 'Content-Type': 'multipart/form-data' }
                });

                if (response.data.success) {
                    await this.fetchProducts(this.pagination.current_page);
                    return true;
                }
                return false;
            } catch (err) {
                console.error('Save product error:', err);
                this.error = err.response?.data?.message || 'Gagal menyimpan menu';
                return false;
            } finally {
                this.loading = false;
            }
        },

        async deleteProduct(id) {
            try {
                await api.delete(`/products/${id}`);
                this.fetchProducts();
                return true;
            } catch (err) {
                this.error = 'Gagal menghapus menu';
                return false;
            }
        },

        async saveCategory(category) {
            this.loading = true;
            try {
                let response;
                if (category.id) {
                    response = await api.put(`/categories/${category.id}`, category);
                } else {
                    response = await api.post('/categories', category);
                }
                if (response.data.success) {
                    await this.fetchCategories();
                    return true;
                }
            } catch (err) {
                this.error = err.response?.data?.message || 'Gagal menyimpan kategori';
                return false;
            } finally {
                this.loading = false;
            }
        },

        async deleteCategory(id) {
            if (!confirm('Hapus kategori ini?')) return;
            this.loading = true;
            try {
                const response = await api.delete(`/categories/${id}`);
                if (response.data.success) {
                    await this.fetchCategories();
                    return true;
                }
            } catch (err) {
                alert(err.response?.data?.message || 'Gagal menghapus kategori');
                return false;
            } finally {
                this.loading = false;
            }
        },

        resetFilters() {
            this.filters = {
                search: '',
                category_id: '',
            };
        }
    }
});
