import { defineStore } from 'pinia';
import api from '../api';

const getSavedData = (key, defaultValue) => {
    try {
        const data = localStorage.getItem(key);
        if (data === null || data === undefined || data === 'undefined') return defaultValue;
        return JSON.parse(data);
    } catch (e) {
        console.error(`Error loading ${key} from localStorage`, e);
        return defaultValue;
    }
};

export const usePOSStore = defineStore('pos', {
    state: () => ({
        categories: [],
        tables: [],
        paymentMethods: [],
        activeShift: null,
        shopSettings: {},
        cart: getSavedData('pos_cart', []),
        loading: false,
        error: null,
        activeCategoryId: null,
        searchQuery: '',
        selectedTableId: getSavedData('pos_table_id', null),
        orderType: localStorage.getItem('pos_order_type') || 'dine_in',
        paymentAmount: 0,
        paymentMethod: 'cash',
        discount: 0,
        notes: '',
    }),

    getters: {
        filteredProducts: (state) => {
            let products = [];
            state.categories.forEach(cat => {
                products = [...products, ...(cat.products || [])];
            });

            if (state.activeCategoryId) {
                products = products.filter(p => p.category_id === state.activeCategoryId);
            }

            if (state.searchQuery) {
                const query = state.searchQuery.toLowerCase();
                products = products.filter(p => p.name.toLowerCase().includes(query));
            }

            return products;
        },

        total: (state) => {
            return state.cart.reduce((total, item) => total + (item.price * item.quantity), 0);
        },

        cartSubtotal: (state) => {
            return state.cart.reduce((total, item) => total + (item.price * item.quantity), 0);
        },

        cartTotal: (state) => {
            const subtotal = state.cart.reduce((total, item) => total + (item.price * item.quantity), 0);
            return Math.max(0, subtotal - (state.discount || 0));
        },

        changeAmount: (state) => {
            const subtotal = state.cart.reduce((total, item) => total + (item.price * item.quantity), 0);
            const total = subtotal - (state.discount || 0);
            return Math.max(0, (state.paymentAmount || 0) - total);
        }
    },

    actions: {
        async fetchContent() {
            this.loading = true;
            try {
                const res = await api.get('/pos/init');
                if (res.data.success) {
                    const d = res.data.data;
                    this.categories = d.categories;
                    this.tables = d.tables;
                    this.paymentMethods = d.payment_methods;
                    this.activeShift = d.active_shift;
                    this.shopSettings = d.shop_settings;
                }
            } catch (err) {
                this.error = 'Gagal memuat data POS';
            } finally {
                this.loading = false;
            }
        },

        saveToStorage() {
            try {
                localStorage.setItem('pos_cart', JSON.stringify(this.cart));
                localStorage.setItem('pos_table_id', JSON.stringify(this.selectedTableId));
                localStorage.setItem('pos_order_type', this.orderType);
            } catch (e) {
                console.error('Error saving to storage', e);
            }
        },

        addToCart(product) {
            const existingItem = this.cart.find(item => item.product_id === product.id);
            if (existingItem) {
                existingItem.quantity += 1;
            } else {
                this.cart.push({
                    product_id: product.id,
                    name: product.name,
                    price: product.price,
                    quantity: 1,
                    image: product.image
                });
            }
            this.saveToStorage();
        },

        removeFromCart(productId) {
            this.cart = this.cart.filter(item => item.product_id !== productId);
            this.saveToStorage();
        },

        updateQuantity(productId, delta) {
            const item = this.cart.find(item => item.product_id === productId);
            if (item) {
                item.quantity = Math.max(1, item.quantity + delta);
                if (item.quantity < 1) {
                    this.removeFromCart(productId);
                    return;
                }
            }
            this.saveToStorage();
        },

        clearCart() {
            this.cart = [];
            this.discount = 0;
            this.notes = '';
            this.paymentAmount = 0;
            this.selectedTableId = null;
            this.saveToStorage();
        },

        setSelectedTable(id) {
            this.selectedTableId = id;
            this.saveToStorage();
        },

        setOrderType(type) {
            this.orderType = type;
            this.saveToStorage();
        },

        async startShift(initialCash) {
            this.loading = true;
            try {
                const res = await api.post('/shifts', { initial_cash: initialCash });
                if (res.data.success) {
                    this.activeShift = res.data.data;
                    return true;
                }
            } catch (err) {
                this.error = err.response?.data?.message || 'Gagal membuka shift';
                return false;
            } finally {
                this.loading = false;
            }
        },

        async checkout(formData) {
            this.loading = true;
            this.error = null;
            try {
                const payload = {
                    items: this.cart,
                    customer_name: formData.customer_name,
                    payment_method: formData.payment_method,
                    payment_amount: formData.payment_amount,
                    discount: this.discount,
                    notes: this.notes,
                    table_id: this.selectedTableId,
                    order_type: this.orderType
                };

                const response = await api.post('/pos/checkout', payload);
                if (response.data.success) {
                    const transaction = response.data.data;
                    this.clearCart();
                    return transaction;
                }
            } catch (err) {
                this.error = err.response?.data?.message || 'Gagal memproses checkout';
                throw new Error(this.error);
            } finally {
                this.loading = false;
            }
        }
    }
});
