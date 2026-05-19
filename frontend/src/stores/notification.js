import { defineStore } from 'pinia';
import api from '../api';

export const useNotificationStore = defineStore('notification', {
    state: () => ({
        notifications: [],
        unreadCount: 0,
        loading: false,
        dropdownOpen: false,
    }),

    actions: {
        async fetchNotifications() {
            this.loading = true;
            try {
                const res = await api.get('/notifications');
                if (res.data.success) {
                    this.notifications = res.data.data;
                    this.unreadCount = res.data.unread_count;
                }
            } catch (err) {
                // silently fail
            } finally {
                this.loading = false;
            }
        },

        async markRead(id) {
            try {
                await api.patch(`/notifications/${id}/read`);
                const n = this.notifications.find(n => n.id === id);
                if (n) {
                    n.read_at = new Date().toISOString();
                    this.unreadCount = Math.max(0, this.unreadCount - 1);
                }
            } catch (err) {}
        },

        async markAllRead() {
            try {
                await api.patch('/notifications/read-all');
                this.notifications.forEach(n => {
                    if (!n.read_at) n.read_at = new Date().toISOString();
                });
                this.unreadCount = 0;
            } catch (err) {}
        },

        toggleDropdown() {
            this.dropdownOpen = !this.dropdownOpen;
            if (this.dropdownOpen) {
                this.fetchNotifications();
            }
        },

        closeDropdown() {
            this.dropdownOpen = false;
        },
    },
});
