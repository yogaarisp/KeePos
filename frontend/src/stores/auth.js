import { defineStore } from 'pinia';
import axios from 'axios';
import api from '../api';
import { showAlert } from '../utils/swal';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: JSON.parse(localStorage.getItem('user')) || null,
        token: localStorage.getItem('auth_token') || null,
        loading: false,
        error: null,
    }),
    getters: {
        isAuthenticated: (state) => !!state.token,
    },
    actions: {
        async login(credentials) {
            this.loading = true;
            this.error = null;
            try {
                // Fetch CSRF cookie first (Sanctum SPA requirement)
                // Use baseUrl to get the backend URL without /api
                const baseUrl = api.defaults.baseURL.replace(/\/api\/?$/, '');
                await axios.get(`${baseUrl}/sanctum/csrf-cookie`, { withCredentials: true });

                const response = await api.post('/login', credentials);
                this.user = response.data.user;
                this.token = response.data.access_token;

                localStorage.setItem('auth_token', this.token);
                localStorage.setItem('user', JSON.stringify(this.user));

                return true;
            } catch (err) {
                this.error = err.response?.data?.message || 'Login failed';
                throw err; // Propagate error for specific handling (like 403 verification)
            } finally {
                this.loading = false;
            }
        },
        async logout() {
            try {
                // Check if there's an active shift
                const shiftResponse = await api.get('/shifts/active');
                if (shiftResponse.data.success && shiftResponse.data.data) {
                    const activeShift = shiftResponse.data.data;
                    const openedDate = new Date(activeShift.opened_at).toLocaleString('id-ID');
                    
                    await showAlert({
                        title: 'Shift Masih Aktif',
                        text: `Anda masih memiliki shift aktif yang dibuka pada ${openedDate}.\n\nAnda harus menutup shift terlebih dahulu sebelum logout.`,
                        icon: 'warning',
                        confirmText: 'Ke Halaman Shift'
                    });
                    
                    // Redirect to shifts page
                    window.location.href = '/shifts';
                    return false; // Prevent logout
                }

                // No active shift, proceed with logout
                await api.post('/logout');
                
                // Clear auth data
                this.user = null;
                this.token = null;
                localStorage.removeItem('auth_token');
                localStorage.removeItem('user');
                
                return true;
            } catch (err) {
                console.error('Logout error:', err);
                // If error checking shift, still allow logout
                this.user = null;
                this.token = null;
                localStorage.removeItem('auth_token');
                localStorage.removeItem('user');
                return true;
            }
        },
        async fetchUser() {
            try {
                const response = await api.get('/me');
                this.user = response.data;
                localStorage.setItem('user', JSON.stringify(this.user));
            } catch (err) {
                this.logout();
            }
        }
    }
});
