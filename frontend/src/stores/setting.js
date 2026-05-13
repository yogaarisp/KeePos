import { defineStore } from 'pinia';
import api from '../api';

export const useSettingStore = defineStore('setting', {
    state: () => ({
        settings: {}, // Flattened settings object
        paymentMethods: [],
        printerName: localStorage.getItem('printer_name') || null,
        paperSize: localStorage.getItem('paper_size') || '58',
        autoPrint: localStorage.getItem('auto_print') === 'true',
        printerAutoReconnect: localStorage.getItem('printer_auto_reconnect') === 'true',
        openCashDrawer: localStorage.getItem('open_cash_drawer') === 'true',

        // Google Sheets
        googleSheetId: '',
        googleServiceAccountJson: '',
        googleSheetSyncEnabled: false,

        loading: false,
        error: null,
    }),

    getters: {
        printerStatus: (state) => state.printerName ? `Terhubung: ${state.printerName}` : 'Printer Belum Terhubung',
    },

    actions: {
        async fetchSettings() {
            this.loading = true;
            try {
                const response = await api.get('/settings');
                if (response.data.success) {
                    const { settings, payment_methods, profile, tenant } = response.data.data;

                    // Flatten settings array to object for easy access
                    const flattened = {};
                    settings.forEach(s => {
                        flattened[s.key] = s.value;
                    });

                    // Merge profile data ke settings untuk backward compatibility
                    if (profile) {
                        flattened.shop_name = profile.shop_name;
                        flattened.shop_logo = profile.shop_logo;
                        flattened.shop_favicon = profile.shop_favicon;
                        flattened.shop_tagline = profile.shop_tagline;
                        flattened.shop_address = profile.shop_address;
                        flattened.shop_phone = profile.shop_phone;
                        flattened.shop_email = profile.shop_email;
                        flattened.primary_color = profile.primary_color;
                        flattened.secondary_color = profile.secondary_color;
                    }

                    // Fallback ke tenant name jika shop_name kosong
                    if (!flattened.shop_name && tenant) {
                        flattened.shop_name = tenant.name;
                    }

                    this.settings = flattened;
                    this.paymentMethods = payment_methods;
                }
            } catch (err) {
                this.error = 'Gagal memuat pengaturan';
            } finally {
                this.loading = false;
            }
        },

        async fetchPublicSettings() {
            try {
                // Use a different URL that doesn't require login
                const response = await api.get('/settings/public');
                if (response.data.success) {
                    this.settings = { ...this.settings, ...response.data.data };
                }
            } catch (err) {
                console.error('Failed to fetch public settings:', err);
            }
        },

        async saveSettings(formData) {
            this.loading = true;
            try {
                const response = await api.post('/settings', formData, {
                    headers: { 'Content-Type': 'multipart/form-data' }
                });
                if (response.data.success) {
                    await this.fetchSettings();
                    return true;
                }
            } catch (err) {
                this.error = 'Gagal menyimpan pengaturan';
                return false;
            } finally {
                this.loading = false;
            }
        },

        async savePaymentMethod(methodData) {
            try {
                const url = methodData.id ? `/settings/payment-methods/${methodData.id}` : '/settings/payment-methods';
                const method = methodData.id ? 'put' : 'post';
                const response = await api[method](url, methodData);
                if (response.data.success) {
                    await this.fetchSettings();
                    return true;
                }
            } catch (err) {
                alert('Gagal menyimpan metode pembayaran');
                return false;
            }
        },

        async deletePaymentMethod(id) {
            if (!confirm('Hapus metode pembayaran ini?')) return;
            try {
                await api.delete(`/settings/payment-methods/${id}`);
                await this.fetchSettings();
                return true;
            } catch (err) {
                alert('Gagal menghapus metode pembayaran');
                return false;
            }
        },

        async exportDatabase() {
            this.loading = true;
            try {
                const response = await api.get('/settings/db/export', { responseType: 'blob' });
                const url = window.URL.createObjectURL(new Blob([response.data]));
                const link = document.createElement('a');
                link.href = url;
                link.setAttribute('download', `backup-${new Date().toISOString().slice(0, 19).replace(/:/g, '-')}.sql`);
                document.body.appendChild(link);
                link.click();
                link.remove();
                return true;
            } catch (err) {
                alert('Gagal mengekspor database. Pastikan mysqldump terinstal di server.');
                return false;
            } finally {
                this.loading = false;
            }
        },

        async importDatabase(file) {
            this.loading = true;
            const formData = new FormData();
            formData.append('database_file', file);
            try {
                const response = await api.post('/settings/db/import', formData, {
                    headers: { 'Content-Type': 'multipart/form-data' }
                });
                if (response.data.success) {
                    alert('Database berhasil diimpor');
                    return true;
                }
            } catch (err) {
                alert('Gagal mengimpor database: ' + (err.response?.data?.message || err.message));
                return false;
            } finally {
                this.loading = false;
            }
        },

        async syncGoogleSheet() {
            this.loading = true;
            try {
                const response = await api.post('/settings/sync-google-sheet');
                if (response.data.success) {
                    return { success: true, message: response.data.message };
                }
            } catch (err) {
                const msg = err.response?.data?.message || 'Gagal sinkronisasi Google Sheets';
                return { success: false, message: msg };
            } finally {
                this.loading = false;
            }
        },

        setPrinterPreference(key, value) {
            this[key] = value;
            let storageKey = key;
            if (key === 'autoPrint') storageKey = 'auto_print';
            else if (key === 'paperSize') storageKey = 'paper_size';
            else if (key === 'printerName') storageKey = 'printer_name';
            else if (key === 'printerAutoReconnect') storageKey = 'printer_auto_reconnect';
            else if (key === 'openCashDrawer') storageKey = 'open_cash_drawer';

            localStorage.setItem(storageKey, value);
        }
    }
});
