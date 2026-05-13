import { defineStore } from 'pinia';
import api from '../api';

export const useOrderStore = defineStore('order', {
    state: () => ({
        orders: [],
        pagination: {
            current_page: 1,
            last_page: 1,
            total: 0,
            per_page: 20
        },
        filters: {
            search: '',
            start_date: new Date().toISOString().split('T')[0],
            end_date: new Date().toISOString().split('T')[0],
            status: '',
            payment_method: '',
        },
        selectedOrder: null,
        loading: false,
        error: null,
    }),

    actions: {
        async fetchOrders(page = 1) {
            this.loading = true;
            try {
                const response = await api.get('/orders', {
                    params: {
                        page,
                        ...this.filters
                    }
                });
                if (response.data.success) {
                    const { data, ...pagination } = response.data.data;
                    this.orders = data;
                    this.pagination = pagination;
                }
            } catch (err) {
                this.error = 'Gagal memuat daftar pesanan';
            } finally {
                this.loading = false;
            }
        },

        async fetchOrderDetails(id) {
            this.loading = true;
            try {
                const response = await api.get(`/orders/${id}`);
                if (response.data.success) {
                    this.selectedOrder = response.data.data;
                    return response.data.data;
                }
            } catch (err) {
                this.error = 'Gagal memuat detail pesanan';
            } finally {
                this.loading = false;
            }
        },

        async updateOrderStatus(id, status) {
            try {
                const response = await api.post(`/orders/${id}/status`, { status });
                if (response.data.success) {
                    // Update local state
                    const index = this.orders.findIndex(o => o.id === id);
                    if (index !== -1) {
                        this.orders[index].status = status;
                    }
                    if (this.selectedOrder && this.selectedOrder.order.id === id) {
                        this.selectedOrder.order.status = status;
                    }
                    return true;
                }
            } catch (err) {
                this.error = 'Gagal mengupdate status pesanan';
                return false;
            }
        },

        resetFilters() {
            const today = new Date().toISOString().split('T')[0];
            this.filters = {
                search: '',
                start_date: today,
                end_date: today,
                status: '',
                payment_method: '',
            };
        }
    }
});
