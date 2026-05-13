import { defineStore } from 'pinia';
import api from '../api';

export const useRecipeStore = defineStore('recipe', {
    state: () => ({
        recipes: [],
        ingredients: { gudang: [], kitchen: [] },
        pagination: {
            current_page: 1,
            last_page: 1,
            total: 0,
        },
        filters: {
            search: '',
            type: '',
        },
        loading: false,
        error: null,
        missingRecipes: {
            items: [],
            total_missing: 0,
            products_without_recipe_count: 0
        }
    }),

    actions: {
        async fetchMissingRecipes() {
            try {
                const response = await api.get('/missing-recipes');
                if (response.data.success) {
                    this.missingRecipes = {
                        items: response.data.data.missing_items,
                        total_missing: response.data.data.total_missing_products,
                        products_without_recipe_count: response.data.data.products_without_recipe
                    };
                }
            } catch (err) {
                console.error('Gagal memuat log resep hilang:', err);
            }
        },

        async dismissMissingRecipe(productId) {
            try {
                const response = await api.delete(`/missing-recipes/${productId}`);
                if (response.data.success) {
                    await this.fetchMissingRecipes();
                    return true;
                }
            } catch (err) {
                console.error('Gagal menghapus log resep hilang:', err);
                return false;
            }
        },
        async fetchRecipes(page = 1) {
            this.loading = true;
            try {
                const response = await api.get('/recipes', {
                    params: {
                        page,
                        search: this.filters.search,
                        type: this.filters.type,
                    }
                });
                if (response.data.success) {
                    const data = response.data.data;
                    this.recipes = data.data;
                    this.pagination = {
                        current_page: data.current_page,
                        last_page: data.last_page,
                        total: data.total,
                    };
                }
            } catch (err) {
                this.error = 'Gagal memuat daftar resep';
            } finally {
                this.loading = false;
            }
        },

        async fetchRecipe(id) {
            try {
                const response = await api.get(`/recipes/${id}`);
                if (response.data.success) {
                    return response.data.data;
                }
            } catch (err) {
                this.error = 'Gagal memuat detail resep';
                return null;
            }
        },

        async fetchIngredients() {
            try {
                const response = await api.get('/recipes/ingredients');
                if (response.data.success) {
                    this.ingredients = response.data.data;
                }
            } catch (err) {
                console.error('Gagal memuat bahan:', err);
            }
        },

        async saveRecipe(recipeData) {
            this.loading = true;
            try {
                let response;
                if (recipeData.id) {
                    response = await api.put(`/recipes/${recipeData.id}`, recipeData);
                } else {
                    response = await api.post('/recipes', recipeData);
                }
                if (response.data.success) {
                    await this.fetchRecipes(this.pagination.current_page);
                    return true;
                }
            } catch (err) {
                this.error = err.response?.data?.message || 'Gagal menyimpan resep';
                return false;
            } finally {
                this.loading = false;
            }
        },

        async deleteRecipe(id) {
            if (!confirm('Hapus resep ini?')) return;
            this.loading = true;
            try {
                const response = await api.delete(`/recipes/${id}`);
                if (response.data.success) {
                    await this.fetchRecipes(this.pagination.current_page);
                    return true;
                }
            } catch (err) {
                alert(err.response?.data?.message || 'Gagal menghapus resep');
                return false;
            } finally {
                this.loading = false;
            }
        },

        resetFilters() {
            this.filters = { search: '', type: '' };
        }
    }
});
