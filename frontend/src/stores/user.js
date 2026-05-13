import { defineStore } from 'pinia';
import api from '../api';

export const useUserStore = defineStore('user', {
    state: () => ({
        users: [],
        pagination: {
            current_page: 1,
            last_page: 1,
            total: 0,
        },
        filters: {
            search: '',
            role: '',
        },
        loading: false,
        error: null,
    }),

    actions: {
        async fetchUsers(page = 1) {
            this.loading = true;
            try {
                const response = await api.get('/users', {
                    params: {
                        page,
                        search: this.filters.search,
                        role: this.filters.role,
                    }
                });
                if (response.data.success) {
                    const { data } = response.data;
                    this.users = data.data;
                    this.pagination = {
                        current_page: data.current_page,
                        last_page: data.last_page,
                        total: data.total,
                    };
                }
            } catch (err) {
                this.error = 'Gagal memuat data user';
            } finally {
                this.loading = false;
            }
        },

        async saveUser(userData) {
            this.loading = true;
            try {
                const url = userData.id ? `/users/${userData.id}` : '/users';
                const method = userData.id ? 'put' : 'post';
                const response = await api[method](url, userData);
                if (response.data.success) {
                    await this.fetchUsers(this.pagination.current_page);
                    return true;
                }
            } catch (err) {
                this.error = err.response?.data?.message || 'Gagal menyimpan user';
                return false;
            } finally {
                this.loading = false;
            }
        },

        async deleteUser(id) {
            if (!confirm('Hapus user ini?')) return;
            try {
                await api.delete(`/users/${id}`);
                await this.fetchUsers();
                return true;
            } catch (err) {
                alert(err.response?.data?.message || 'Gagal menghapus user');
                return false;
            }
        },

        resetFilters() {
            this.filters = {
                search: '',
                role: '',
            };
        }
    }
});
