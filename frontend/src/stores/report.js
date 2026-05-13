import { defineStore } from 'pinia';
import api from '../api';

export const useReportStore = defineStore('report', {
    state: () => ({
        salesData: null,
        stockData: null,
        filters: {
            start_date: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0],
            end_date: new Date().toISOString().split('T')[0],
        },
        loading: false,
        error: null,
    }),

    actions: {
        async fetchSalesSummary() {
            this.loading = true;
            try {
                const response = await api.get('/reports/sales', { params: this.filters });
                if (response.data.success) {
                    this.salesData = response.data.data;
                }
            } catch (err) {
                this.error = 'Gagal memuat laporan penjualan';
            } finally {
                this.loading = false;
            }
        },

        async fetchStockSummary() {
            try {
                const response = await api.get('/reports/stock');
                if (response.data.success) {
                    this.stockData = response.data.data;
                }
            } catch (err) {
                this.error = 'Gagal memuat laporan stok';
            }
        }
    }
});
