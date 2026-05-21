<template>
  <div class="notif-bell-wrap" v-click-outside="notifStore.closeDropdown">
    <button
      class="header-action-btn notif-btn"
      :class="{ 'is-active': notifStore.dropdownOpen }"
      @click="notifStore.toggleDropdown()"
      title="Notifikasi"
    >
      <div class="bell-wrapper">
        <svg class="bell-icon-custom" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <!-- Main Bell Body -->
          <path class="bell-body" d="M12 2C10.34 2 9 3.34 9 5V5.5C6.76 6.22 5 8.36 5 11V16.5L3.59 17.91C3.21 18.29 3 18.8 3 19.33V19.5C3 19.78 3.22 20 3.5 20H20.5C20.78 20 21 19.78 21 19.5V19.33C21 18.8 20.79 18.29 20.41 17.91L19 16.5V11C19 8.36 17.24 6.22 15 5.5V5C15 3.34 13.66 2 12 2Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
          <path class="bell-fill" d="M12 2C10.34 2 9 3.34 9 5V5.5C6.76 6.22 5 8.36 5 11V16.5L3.59 17.91C3.21 18.29 3 18.8 3 19.33V19.5C3 19.78 3.22 20 3.5 20H20.5C20.78 20 21 19.78 21 19.5V19.33C21 18.8 20.79 18.29 20.41 17.91L19 16.5V11C19 8.36 17.24 6.22 15 5.5V5C15 3.34 13.66 2 12 2Z" fill="currentColor"/>
          <!-- Clapper -->
          <path class="bell-clapper" d="M10 21C10.55 21.62 11.24 22 12 22C12.76 22 13.45 21.62 14 21" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </div>
      <span v-if="notifStore.unreadCount > 0" class="notif-badge">
        {{ notifStore.unreadCount > 9 ? '9+' : notifStore.unreadCount }}
      </span>
    </button>

    <Transition name="dropdown-fade">
      <div v-if="notifStore.dropdownOpen" class="notif-dropdown">
        <div class="notif-header">
          <span class="notif-title">Notifikasi</span>
          <button
            v-if="notifStore.unreadCount > 0"
            class="btn-mark-all"
            @click="notifStore.markAllRead()"
          >
            Tandai semua dibaca
          </button>
        </div>

        <div class="notif-list custom-scrollbar">
          <div v-if="notifStore.loading" class="notif-empty">
            <div class="notif-loading-dots">
              <span></span><span></span><span></span>
            </div>
          </div>

          <div v-else-if="notifStore.notifications.length === 0" class="notif-empty">
            <BellOff :size="32" class="notif-empty-icon" />
            <p>Tidak ada notifikasi</p>
          </div>

          <div
            v-else
            v-for="n in notifStore.notifications"
            :key="n.id"
            class="notif-item"
            :class="{ unread: !n.read_at }"
            @click="handleNotifClick(n)"
          >
            <div class="notif-dot" v-if="!n.read_at"></div>
            <div class="notif-content">
              <p class="notif-item-title">{{ n.data.title }}</p>
              <p class="notif-item-msg">{{ n.data.message }}</p>
              <span class="notif-time">{{ formatTime(n.created_at) }}</span>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { BellOff } from 'lucide-vue-next';
import { useNotificationStore } from '../stores/notification';

const notifStore = useNotificationStore();
const router = useRouter();

// Custom v-click-outside directive
const vClickOutside = {
  mounted(el, binding) {
    el._clickOutside = (e) => {
      if (!el.contains(e.target)) binding.value();
    };
    document.addEventListener('click', el._clickOutside);
  },
  unmounted(el) {
    document.removeEventListener('click', el._clickOutside);
  },
};

onMounted(() => {
  notifStore.fetchNotifications();
  // Poll every 60 seconds
  setInterval(() => notifStore.fetchNotifications(), 60000);
});

const handleNotifClick = async (n) => {
  if (!n.read_at) await notifStore.markRead(n.id);
  if (n.data.url) {
    router.push(n.data.url);
    notifStore.closeDropdown();
  }
};

const formatTime = (dateStr) => {
  if (!dateStr) return '';
  const date = new Date(dateStr);
  const now = new Date();
  const diff = Math.floor((now - date) / 1000);
  if (diff < 60) return 'Baru saja';
  if (diff < 3600) return Math.floor(diff / 60) + ' menit lalu';
  if (diff < 86400) return Math.floor(diff / 3600) + ' jam lalu';
  return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
};
</script>

<style scoped>
.notif-bell-wrap {
  position: relative;
}

.notif-btn {
  position: relative;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  border-radius: 50% !important;
}

.notif-btn.is-active {
  border-color: var(--accent);
  background: var(--accent-light);
  box-shadow: 0 0 0 3.5px rgba(var(--accent-rgb, 249, 115, 22), 0.12);
  transform: translateY(-1px);
}

.notif-btn:hover {
  transform: translateY(-2px);
}

.bell-wrapper {
  position: relative;
  width: 22px;
  height: 22px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.bell-icon-custom {
  width: 20px;
  height: 20px;
  color: var(--text-secondary);
  transform-origin: top center;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.bell-fill {
  fill-opacity: 0;
  transition: fill-opacity 0.3s ease;
}

.notif-btn:hover .bell-icon-custom,
.notif-btn.is-active .bell-icon-custom {
  color: var(--accent);
}

.notif-btn:hover .bell-fill {
  fill-opacity: 0.15;
}

.notif-btn.is-active .bell-fill {
  fill-opacity: 0.25;
}

.notif-btn:hover .bell-icon-custom {
  animation: gentle-swing 1s cubic-bezier(0.36, 0.07, 0.19, 0.97) both;
}

/* Glowing pulsing notification badge */
.notif-badge {
  position: absolute;
  top: -2px;
  right: -2px;
  min-width: 16px;
  height: 16px;
  background: #ef4444;
  color: #fff;
  font-size: 8.5px;
  font-weight: 800;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 3.5px;
  border: 2px solid var(--bg-card);
  line-height: 1;
  box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.5);
  animation: badge-pulse 2s infinite cubic-bezier(0.4, 0, 0.6, 1);
}

@keyframes gentle-swing {
  0% { transform: rotate(0); }
  20% { transform: rotate(10deg); }
  40% { transform: rotate(-8deg); }
  60% { transform: rotate(4deg); }
  80% { transform: rotate(-2deg); }
  100% { transform: rotate(0); }
}

/* Subtle badge pulse keyframe */
@keyframes badge-pulse {
  0% {
    box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.6);
  }
  70% {
    box-shadow: 0 0 0 5px rgba(239, 68, 68, 0);
  }
  100% {
    box-shadow: 0 0 0 0 rgba(239, 68, 68, 0);
  }
}

.notif-dropdown {
  position: absolute;
  top: calc(100% + 12px);
  right: 0;
  width: 340px;
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: 20px;
  box-shadow: 0 20px 60px rgba(0,0,0,0.15);
  z-index: 200;
  overflow: hidden;
}

.notif-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 20px;
  border-bottom: 1px solid var(--border-color);
}

.notif-title {
  font-size: 14px;
  font-weight: 700;
  color: var(--text-primary);
}

.btn-mark-all {
  font-size: 11px;
  font-weight: 600;
  color: var(--accent);
  background: none;
  border: none;
  cursor: pointer;
  padding: 0;
}

.btn-mark-all:hover {
  text-decoration: underline;
}

.notif-list {
  max-height: 360px;
  overflow-y: auto;
}

.notif-empty {
  padding: 40px 20px;
  text-align: center;
  color: var(--text-muted);
  font-size: 13px;
}

.notif-empty-icon {
  margin: 0 auto 12px;
  opacity: 0.25;
  stroke-width: 1.5 !important;
}

.notif-loading-dots {
  display: flex;
  justify-content: center;
  gap: 6px;
}

.notif-loading-dots span {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: var(--accent);
  animation: bounce 0.6s infinite alternate;
}

.notif-loading-dots span:nth-child(2) { animation-delay: 0.2s; }
.notif-loading-dots span:nth-child(3) { animation-delay: 0.4s; }

@keyframes bounce { to { transform: translateY(-6px); opacity: 0.5; } }

.notif-item {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 14px 20px;
  cursor: pointer;
  transition: background 0.15s;
  border-bottom: 1px solid var(--border-color);
  position: relative;
}

.notif-item:last-child {
  border-bottom: none;
}

.notif-item:hover {
  background: var(--bg-primary);
}

.notif-item.unread {
  background: rgba(79, 70, 229, 0.04);
}

.notif-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: var(--accent);
  flex-shrink: 0;
  margin-top: 5px;
}

.notif-content {
  flex: 1;
  min-width: 0;
}

.notif-item-title {
  font-size: 13px;
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: 2px;
}

.notif-item-msg {
  font-size: 12px;
  color: var(--text-secondary);
  line-height: 1.4;
  margin-bottom: 4px;
}

.notif-time {
  font-size: 10px;
  color: var(--text-muted);
  font-weight: 500;
}

/* Dropdown animation */
.dropdown-fade-enter-active,
.dropdown-fade-leave-active {
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.dropdown-fade-enter-from,
.dropdown-fade-leave-to {
  opacity: 0;
  transform: translateY(-8px) scale(0.97);
}
</style>
