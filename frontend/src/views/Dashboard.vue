<template>
  <div class="app-root" :class="[{ 'pos-mode': isPOSPage }, theme.isLight ? 'light' : 'dark']">
    <!-- Sidebar (Hidden on POS) -->
    <aside v-if="!isPOSPage" class="app-sidebar" :class="{ 'mobile-open': sidebarOpen }">
      <!-- Sidebar Header -->
      <div class="sidebar-top">
        <div class="brand-box">
          <div class="brand-logo">
            <img v-if="settingsStore.settings.shop_logo" :src="baseUrl + '/storage/' + settingsStore.settings.shop_logo" class="dynamic-logo">
            <template v-else>
              <img v-if="isSuperAdmin && settingsStore.settings.app_logo" :src="baseUrl + '/storage/' + settingsStore.settings.app_logo" class="dynamic-logo">
              <div v-else class="initial-logo">{{ shopInitial }}</div>
            </template>
            <div class="logo-glow"></div>
          </div>
          <div class="brand-info">
            <h1 class="brand-name">{{ settingsStore.settings.shop_name || settingsStore.settings.app_name || 'Kee POS' }}</h1>
            <span class="brand-status">Smart POS System</span>
          </div>
        </div>
      </div>

      <!-- Navigation Area -->
      <nav class="sidebar-nav custom-scrollbar">
        <template v-for="section in menuSections" :key="section.id">
          <div v-if="section.show" class="nav-section">
          <div 
            v-if="section.title" 
            class="section-group-label" 
            :class="{ 'is-active': !collapsedSections.includes(section.id) }"
            @click="toggleSection(section.id)"
          >
            <span>{{ section.title }}</span>
            <ChevronDown 
              :size="14" 
              class="section-chevron" 
              :class="{ collapsed: collapsedSections.includes(section.id) }"
            />
          </div>
          
          <div 
            class="section-collapse-wrapper" 
            :class="{ 'is-expanded': !collapsedSections.includes(section.id) }"
          >
            <div class="section-content-inner">
              <div class="section-links">
                <router-link 
                  v-for="link in section.links" 
                  :key="link.to" 
                  :to="link.to" 
                  class="nav-link" 
                  active-class="active"
                  exact-active-class="exact-active"
                  @click="onNavLinkClick(link, $event)"
                >
                  <div class="link-icon-wrap">
                    <component :is="link.icon" :size="20" />
                  </div>
                  <span class="link-label">{{ link.label }}</span>
                  <div v-if="link.plan && !isPlanMet(link.plan)" class="plan-badge-sidebar" :class="link.plan">
                    {{ link.plan.toUpperCase() }}
                  </div>
                  <ChevronRight :size="16" class="active-arrow" />
                </router-link>
              </div>
            </div>
          </div>
        </div>
      </template>
      </nav>
    </aside>

    <!-- Main Content Area -->
    <div class="app-viewport">
      <!-- Subscription Warning Banner (Expiring Soon) -->
      <Transition name="slide-down">
        <div v-if="subscriptionWarning" class="global-announcement warning">
          <div class="announcement-content">
            <div class="announcement-icon">
              <AlertTriangle :size="18" />
            </div>
            <div class="announcement-text">
              <span class="a-title">PENGINGAT LANGGANAN:</span>
              <span class="a-desc">Layanan Anda akan berakhir dalam <b>{{ subscriptionDaysLeft }} hari lagi</b> ({{ formatDateSimple(auth.user?.tenant?.subscription_ends_at) }}). Perbarui paket sekarang untuk menghindari gangguan.</span>
            </div>
            <router-link to="/app/billing" class="btn-announcement" @click="sidebarOpen = false">
              Perbarui Sekarang
            </router-link>
          </div>
        </div>
      </Transition>

      <!-- Top Header (Hidden on POS) -->
      <header v-show="!isPOSPage" class="app-header">
        <div class="header-left">
          <!-- Sidebar Toggle Hamburger (Tablet & Mobile) -->
          <button class="btn-header-toggle" @click="sidebarOpen = !sidebarOpen" title="Buka Menu">
            <Menu :size="24" />
          </button>

          <!-- Brand Box in Header (Visible on Mobile) -->
          <div class="header-brand-box" @click="router.push('/app')">
            <div class="h-brand-logo">
              <img v-if="settingsStore.settings.shop_logo" :src="baseUrl + '/storage/' + settingsStore.settings.shop_logo" class="dynamic-logo">
              <template v-else>
                <img v-if="isSuperAdmin && settingsStore.settings.app_logo" :src="baseUrl + '/storage/' + settingsStore.settings.app_logo" class="dynamic-logo">
                <div v-else class="h-initial-logo">{{ shopInitial }}</div>
              </template>
            </div>
            <div class="h-brand-info">
              <h1 class="h-brand-name">{{ settingsStore.settings.shop_name || settingsStore.settings.app_name || 'Kee POS' }}</h1>
              <span class="h-brand-status">Smart POS System</span>
            </div>
          </div>
          <div class="header-breadcrumb">
            <span class="bc-parent">Main</span>
            <ChevronRight :size="12" />
            <span class="bc-current">{{ currentTitle }}</span>
          </div>
        </div>

        <div class="header-right">
          <div class="header-actions-group">
            <!-- Theme Toggle -->
            <button class="header-action-btn theme-btn" @click="theme.toggle()" :title="theme.isLight ? 'Dark Mode' : 'Light Mode'">
              <Sun v-if="theme.isLight" :size="18" />
              <Moon v-else :size="18" />
            </button>
            
            <!-- Mount point for page-specific actions -->
            <div id="header-actions"></div>
          </div>

          <div class="header-date">
            <Calendar :size="14" />
            <span>{{ todayDate }}</span>
          </div>

          <div class="header-divider"></div>

          <!-- User Profile moved from sidebar -->
          <div class="user-header-profile">
            <div class="user-avatar-small">
              {{ (auth.user?.full_name || 'A').charAt(0).toUpperCase() }}
            </div>
            <div class="user-header-info">
              <span class="h-user-name">{{ auth.user?.full_name || auth.user?.username }}</span>
              <span class="h-user-role">{{ auth.user?.role }}</span>
            </div>
            <button class="btn-header-logout" @click="handleLogout" title="Keluar">
              <LogOut :size="16" />
            </button>
          </div>
        </div>
      </header>

      <!-- Page Content -->
      <main class="app-content" :class="{ 'no-padding': isPOSPage, 'is-blurred': isFeatureLocked }">
        <router-view v-slot="{ Component }">
          <Transition name="page-fade" mode="out-in">
            <component :is="Component" />
          </Transition>
        </router-view>
        
        <!-- Premium Locked Overlay -->
        <Transition name="fade">
          <PremiumLockedOverlay 
            v-if="isFeatureLocked" 
            :pageTitle="currentTitle"
            :requiredPlan="route.meta.requiredPlan"
          />
        </Transition>
      </main>

      <!-- Bottom Spacer to prevent content being hidden by floating nav -->
      <div v-if="!isPOSPage" class="mobile-bottom-spacer"></div>

      <!-- Premium Mobile Bottom Navigation -->
      <nav class="mobile-bottom-nav" v-if="!isPOSPage">
        <router-link to="/app" class="m-nav-item" active-class="active">
          <LayoutGrid :size="20" />
          <span class="m-nav-label">Home</span>
          <div class="active-dot"></div>
        </router-link>
        
        <router-link to="/app/orders" class="m-nav-item" active-class="active">
          <ClipboardList :size="20" />
          <span class="m-nav-label">Orders</span>
          <div class="active-dot"></div>
        </router-link>

        <!-- Elevated Floating Center Button -->
        <div class="m-nav-fab-wrapper">
          <router-link to="/app/pos" class="m-nav-fab" @click="onNavLinkClick({ to: '/app/pos' }, $event)" :class="{ active: route.path === '/app/pos' }">
            <ShoppingCart :size="24" />
          </router-link>
        </div>

        <router-link to="/app/users" v-if="isAdmin" class="m-nav-item" active-class="active">
          <UsersIcon :size="20" />
          <span class="m-nav-label">Users</span>
          <div class="active-dot"></div>
        </router-link>
        <router-link to="/app/settings" v-else class="m-nav-item" active-class="active">
          <Settings :size="20" />
          <span class="m-nav-label">Settings</span>
          <div class="active-dot"></div>
        </router-link>

        <button class="m-nav-item btn-menu-trigger" @click="sidebarOpen = true">
          <Menu :size="20" />
          <span class="m-nav-label">Menu</span>
        </button>
      </nav>
    </div>

    <!-- Mobile Sidebar Overlay -->
    <Transition name="fade">
      <div v-if="sidebarOpen" class="sidebar-backdrop" @click="sidebarOpen = false"></div>
    </Transition>

    <!-- Auto Shift Modal for Kasir on Login -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="showAutoShiftModal" class="modal-backdrop shift-backdrop">
          <div class="modal-panel-shift ripple">
            <div class="modal-shift-header">
              <h2 class="modal-shift-title">Mulai Shift Baru</h2>
            </div>
            
            <div class="modal-shift-body">
              <div class="welcome-alert">
                <div class="welcome-icon">
                  <Info :size="16" />
                </div>
                <div class="welcome-text">
                  <h4 class="welcome-title">SELAMAT DATANG!</h4>
                  <p class="welcome-desc">Anda perlu membuka shift terlebih dahulu sebelum dapat melakukan transaksi POS.</p>
                </div>
              </div>

              <div class="shift-input-group">
                <div class="shift-label-icon">
                  <Wallet :size="16" />
                  <span class="shift-label">Modal Awal / Uang Laci <span class="text-red">*</span></span>
                </div>
                <div class="shift-input-wrapper">
                  <span class="shift-currency">Rp</span>
                  <input 
                    type="number" 
                    v-model="autoShiftForm.initial_cash" 
                    class="shift-premium-input" 
                    placeholder="0"
                  >
                </div>
                <p class="shift-hint">* Masukkan jumlah uang tunai awal di laci kasir</p>
              </div>
            </div>

            <div class="modal-shift-footer">
              <button class="btn-shift-start" @click="handleAutoShiftSubmit">
                <Play :size="18" fill="currentColor" />
                <span>Mulai Shift Sekarang</span>
              </button>
              <button class="btn-shift-later" @click="showAutoShiftModal = false">
                Nanti Saja
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, reactive, inject, provide, computed, onMounted, watch } from 'vue';
import { useAuthStore } from '../stores/auth';
import { useSettingStore } from '../stores/setting';
import { usePOSStore } from '../stores/pos'; // Moved up
import { useRoute, useRouter } from 'vue-router';
import { showConfirm } from '../utils/swal';
import { baseUrl } from '../api';
import { 
  LayoutGrid, ShoppingCart, ClipboardList, Package, ChefHat, Folder,
  Layers, Tag, Table2, Clock, BookOpen, Trash2, 
  BarChart2, Users as UsersIcon, Settings, 
  ChevronDown, ChevronRight, LogOut, Menu, Sun, Moon,
  Utensils, X, Calendar, Search, Activity, Box, Truck,
  History, UserCheck, Warehouse, Smartphone, Monitor, Scale,
  AlertTriangle, Play, Info, Wallet, CreditCard, Lock
} from 'lucide-vue-next';
import PremiumLockedOverlay from '../components/PremiumLockedOverlay.vue';

const auth = useAuthStore();
const settingsStore = useSettingStore();
const theme = inject('theme');
const route = useRoute();
const router = useRouter();

const sidebarOpen = ref(false);
const isPlanMet = (requiredPlan) => {
  if (isSuperAdmin.value) return true;
  const currentPlan = auth.user?.tenant?.plan || 'free';
  const planWeights = { 'free': 0, 'basic': 1, 'pro': 2 };
  return planWeights[currentPlan] >= planWeights[requiredPlan];
};

const collapsedSections = ref([]);
const isPOSPage = computed(() => route.path === '/app/pos');
const currentTitle = computed(() => route.meta.title || route.name || 'Dashboard');

const shopInitial = computed(() => {
  const name = settingsStore.settings.shop_name || auth.user?.tenant?.name || 'K';
  return name.charAt(0).toUpperCase();
});

const isSectionActive = (section) => {
  if (!section.links) return false;
  return section.links.some(link => route.path === link.to);
};

const isFeatureLocked = computed(() => {
  if (isSuperAdmin.value) return false;
  const requiredPlan = route.meta.requiredPlan;
  if (!requiredPlan) return false;
  return !isPlanMet(requiredPlan);
});

const toggleSection = (sectionId) => {
  const isCurrentlyCollapsed = collapsedSections.value.includes(sectionId);
  
  if (isCurrentlyCollapsed) {
    // Expanding this section: collapse all sections first, then remove this one from collapsed list
    const allSectionIds = menuSections.value.map(s => s.id);
    collapsedSections.value = allSectionIds.filter(id => id !== sectionId);
  } else {
    // Collapsing this section: just add it to the collapsed list if not already there
    if (!collapsedSections.value.includes(sectionId)) {
      collapsedSections.value.push(sectionId);
    }
  }
  
  // Save to localStorage
  localStorage.setItem('collapsed_sections', JSON.stringify(collapsedSections.value));
};

onMounted(async () => {
  settingsStore.fetchSettings();
  // Restore collapsed sections from localStorage
  const saved = localStorage.getItem('collapsed_sections');
  if (saved) {
    try {
      collapsedSections.value = JSON.parse(saved);
    } catch (e) {
      collapsedSections.value = [];
    }
  }
  // For cashier role, check and show the shift modal immediately if no active shift exists
  checkShiftOnLogin();
});

watch(currentTitle, (newTitle) => {
  const shopName = settingsStore.settings.shop_name || 'Kee POS';
  document.title = `${newTitle} | ${shopName}`;
});

// Subscription Alert Logic
const subscriptionDaysLeft = computed(() => {
  if (isSuperAdmin.value || !auth.user?.tenant?.subscription_ends_at) return null;
  const endsAt = new Date(auth.user.tenant.subscription_ends_at);
  const now = new Date();
  const diffTime = endsAt - now;
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
  return diffDays;
});

const subscriptionWarning = computed(() => {
  if (subscriptionDaysLeft.value === null) return false;
  // Show warning if 7 days or less
  return subscriptionDaysLeft.value <= 7 && subscriptionDaysLeft.value > 0;
});

const formatDateSimple = (dateStr) => {
  if (!dateStr) return '';
  return new Date(dateStr).toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  });
};


const todayDate = computed(() => {
  return new Date().toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short'
  });
});

const isAdmin = computed(() => {
  const role = auth.user?.role?.toLowerCase();
  return role === 'admin' || role === 'superadmin';
});
const isSuperAdmin = computed(() => auth.user?.role?.toLowerCase() === 'superadmin');

const menuSections = computed(() => {
  // If user is Kasir, show simplified single section
  if (auth.user?.role?.toLowerCase() === 'kasir') {
    return [
      {
        id: 'kasir-main',
        title: '',
        show: true,
        links: [
          { to: '/app', label: 'Dashboard', icon: LayoutGrid },
          { to: '/app/pos', label: 'POS', icon: ShoppingCart },
          { to: '/app/orders', label: 'Transaksi', icon: ClipboardList },
          { to: '/app/products', label: 'Produk', icon: Package },
          { to: '/app/reports', label: 'Laporan Penjualan', icon: BarChart2 },
          { to: '/app/shifts', label: 'Manajemen Shift', icon: Clock },
          { to: '/app/waste', label: 'Laporan Reject', icon: AlertTriangle },
          { to: '/app/settings', label: 'Pengaturan', icon: Settings },
        ]
      }
    ];
  }

  // Admin view (Grouped)
  return [
    {
      id: 'main',
      title: 'MENU UTAMA',
      show: true,
      links: [
        { to: '/app', label: 'Dashboard', icon: LayoutGrid },
        { to: '/app/pos', label: 'Kasir POS', icon: ShoppingCart },
        { to: '/app/orders', label: 'Riwayat Pesanan', icon: ClipboardList },
      ]
    },
    {
      id: 'inventory',
      title: 'INVENTORI',
      show: true,
      links: [
        { to: '/app/warehouse', label: 'Stok Gudang', icon: Package, plan: 'basic' },
        { to: '/app/kitchen', label: 'Stok Dapur', icon: ChefHat, plan: 'pro' },
        { to: '/app/suppliers', label: 'Kelola Supplier', icon: Truck, plan: 'pro' },
        { to: '/app/material-categories', label: 'Kategori Bahan', icon: Folder },
        { to: '/app/units', label: 'Satuan Ukur', icon: Scale },
      ]
    },
    {
      id: 'catalog',
      title: 'KATALOG PRODUK',
      show: true,
      links: [
        { to: '/app/products', label: 'Daftar Menu', icon: Layers },
        { to: '/app/product-categories', label: 'Kategori Menu', icon: Tag },
      ]
    },
    {
      id: 'operational',
      title: 'OPERASIONAL',
      show: true,
      links: [
        { to: '/app/tables', label: 'Kelola Meja', icon: Table2 },
        { to: '/app/shifts', label: 'Kelola Shift', icon: Clock },
        { to: '/app/recipes', label: 'Data Resep', icon: BookOpen, plan: 'pro' },
      ]
    },
    {
      id: 'analytics',
      title: 'ANALISA BISNIS',
      show: true,
      links: [
        { to: '/app/reports', label: 'Laporan Penjualan', icon: BarChart2 },
        { to: '/app/inventory-report', label: 'Laporan Transaksi Inventori', icon: Package, plan: 'basic' },
        { to: '/app/waste', label: 'Food Waste', icon: Trash2 },
      ]
    },
    {
      id: 'system',
      title: 'KONFIGURASI',
      show: true,
      links: [
        { to: '/app/users', label: 'Manajemen User', icon: UsersIcon },
        {
          to: '/app/settings',
          label: 'Pengaturan Toko',
          icon: Settings
        },
        {
          to: '/app/billing',
          label: 'Langganan & Billing',
          icon: CreditCard
        },
      ]
    },
    {
      id: 'saas',
      title: 'SAAS OWNER',
      show: auth.user?.role?.toLowerCase() === 'superadmin',
      links: [
        {
          to: '/app/admin/tenants',
          label: 'Manajemen Toko',
          icon: Activity
        },
        {
          to: '/app/admin/tenants/stats',
          label: 'Statistik Platform',
          icon: BarChart2
        },
        {
          to: '/app/admin/saas/config',
          label: 'SaaS Konfigurasi',
          icon: Settings
        },
        {
          to: '/app/admin/invoices',
          label: 'History Invoice',
          icon: History
        },
      ]
    }
  ];
});

const posStore = usePOSStore();
const showAutoShiftModal = ref(false);
const autoShiftForm = reactive({ initial_cash: 0 });

const checkShiftOnLogin = async () => {
  if (!isAdmin.value) {
    await posStore.fetchContent();
    if (!posStore.activeShift) {
      showAutoShiftModal.value = true;
    }
  }
};

const handleAutoShiftSubmit = async () => {
  const success = await posStore.startShift(autoShiftForm.initial_cash);
  if (success) {
    showAutoShiftModal.value = false;
    // After starting shift, we stay on the current page (Dashboard).
    // The user can then click "POS" or "Buka Kasir" to go to POS.
  }
};

const handleLogout = async () => {
  const result = await showConfirm({
    title: 'Konfirmasi Logout',
    text: 'Apakah Anda yakin ingin keluar?',
    icon: 'question',
    confirmText: 'Ya, Keluar',
    cancelText: 'Batal'
  });
  
  if (result.isConfirmed) {
    const success = await auth.logout();
    if (success) {
      router.push('/login');
    }
  }
};

// Global POS Navigation Check
const handlePOSNavigation = (e) => {
  if (!isAdmin.value && !posStore.activeShift) {
    if (e) e.preventDefault();
    showAutoShiftModal.value = true;
    return false;
  }
  if (!e) router.push('/app/pos');
  return true;
};

const onNavLinkClick = (link, e) => {
  if (link.to === '/app/pos') {
    handlePOSNavigation(e);
  }
  sidebarOpen.value = false;
};

// Provide to child components (like Home.vue)
provide('handlePOSNavigation', handlePOSNavigation);
</script>

<style scoped>
/* ── Main Container ── */
.app-root {
  display: flex;
  min-height: 100vh;
  background: var(--bg-primary);
  color: var(--text-primary);
}

/* ── Sidebar ── */
.app-sidebar {
  width: 260px; /* Slightly wider for better breathing room */
  background: #ffffff;
  border-right: 1px solid rgba(0,0,0,0.04);
  display: flex;
  flex-direction: column;
  position: fixed;
  top: 0; left: 0; bottom: 0;
  z-index: 100;
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 4px 0 20px rgba(0,0,0,0.01);
}

/* ── Global Announcement Banner ── */
.global-announcement {
  padding: 12px 24px;
  background: var(--bg-card);
  border-bottom: 1px solid var(--border-color);
  position: sticky;
  top: 0;
  z-index: 90;
  animation: slideDown 0.5s cubic-bezier(0.16, 1, 0.3, 1);
}

.global-announcement.warning {
  background: #fffbeb;
  border-bottom: 1px solid #fde68a;
  color: #92400e;
}

.dark .global-announcement.warning {
  background: #451a03;
  border-bottom: 1px solid #78350f;
  color: #fbbf24;
}

.announcement-content {
  max-width: 1200px;
  margin: 0 auto;
  display: flex;
  align-items: center;
  gap: 16px;
  flex-wrap: wrap;
}

.announcement-icon {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  background: rgba(245, 158, 11, 0.1);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.announcement-text {
  flex: 1;
  font-size: 13.5px;
  line-height: 1.4;
}

.a-title {
  font-weight: 800;
  margin-right: 8px;
  letter-spacing: 0.5px;
}

.a-desc b {
  font-weight: 800;
  text-decoration: underline;
}

.btn-announcement {
  padding: 6px 16px;
  background: #f59e0b;
  color: #fff;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 700;
  text-decoration: none;
  transition: all 0.2s;
  box-shadow: 0 4px 12px rgba(245, 158, 11, 0.2);
}

.btn-announcement:hover {
  background: #d97706;
  transform: translateY(-1px);
  box-shadow: 0 6px 16px rgba(245, 158, 11, 0.3);
}

/* Transitions */
.slide-down-enter-active, .slide-down-leave-active {
  transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}
.slide-down-enter-from, .slide-down-leave-to {
  transform: translateY(-100%);
  opacity: 0;
}

@keyframes slideDown {
  from { transform: translateY(-100%); }
  to { transform: translateY(0); }
}

@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

@media (max-width: 768px) {
  .announcement-content {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }
  .btn-announcement {
    width: 100%;
    text-align: center;
  }
}


/* Override background for Dark Mode */
.dark .app-sidebar {
  background: var(--bg-secondary);
  border-right: 1px solid var(--border-color);
}

.sidebar-top {
  padding: 24px;
}
.brand-box {
  display: flex;
  align-items: center;
  gap: 14px;
}
.brand-logo {
  width: 42px;
  height: 42px;
  display: flex; align-items: center; justify-content: center;
  color: var(--accent); position: relative;
  overflow: hidden;
  border-radius: 12px;
}
.dynamic-logo { width: 100%; height: 100%; object-fit: contain; }
.initial-logo {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #f97316, #ea580c);
  color: white;
  font-size: 20px;
  font-weight: 800;
  border-radius: 12px;
  text-shadow: 0 2px 4px rgba(0,0,0,0.2);
}
.logo-glow {
  display: none;
}
.brand-name {
  font-size: 15px;
  font-weight: 800;
  color: var(--text-primary);
  letter-spacing: -0.3px;
  line-height: 1.2;
}
.brand-status {
  display: inline-block;
  font-size: 10px;
  font-weight: 700;
  color: #f97316;
  background: rgba(249, 115, 22, 0.05);
  padding: 2px 8px;
  border-radius: 6px;
  margin-top: 4px;
}

/* ── Navigation ── */
.sidebar-nav {
  flex: 1;
  padding: 8px 12px;
  overflow-y: auto;
}
.nav-section {
  margin-bottom: 4px;
}
.section-group-label {
  font-size: 11.5px;
  font-weight: 800;
  color: var(--text-muted);
  letter-spacing: 1px;
  text-transform: uppercase;
  margin-top: 20px;
  margin-bottom: 4px;
  padding: 4px 12px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  cursor: pointer;
  user-select: none;
  transition: color 0.2s;
}

.section-group-label:hover {
  color: var(--text-primary);
}

.section-group-label.is-active {
  color: #f97316;
}

.section-chevron {
  transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  opacity: 0.5;
}

.section-group-label.is-active .section-chevron {
  opacity: 1;
  color: var(--accent);
}

.section-chevron.collapsed {
  transform: rotate(-90deg);
}

/* Section collapse animation - The Modern "Grid" Way (Ultra Smooth) */
.section-collapse-wrapper {
  display: grid;
  grid-template-rows: 0fr;
  transition: grid-template-rows 0.35s cubic-bezier(0.4, 0, 0.2, 1);
  overflow: hidden;
}

.section-collapse-wrapper.is-expanded {
  grid-template-rows: 1fr;
}

.section-content-inner {
  min-height: 0;
  transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
}

.section-links {
  display: flex;
  flex-direction: column;
  gap: 2px;
  padding: 2px 0 8px 14px;
  border-left: 1px dashed rgba(0,0,0,0.05);
  margin-left: 14px;
}

.dark .section-links {
  border-left: 1px dashed rgba(255,255,255,0.05);
}

.nav-link {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 14px;
  color: var(--text-secondary);
  text-decoration: none;
  font-size: 13.5px;
  font-weight: 500;
  border-radius: 8px;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
}

.nav-link:hover {
  background: var(--bg-card-hover);
  color: var(--text-primary);
}

.nav-link.exact-active {
  background: #fff7ed;
  color: #ea580c;
  font-weight: 500; /* same weight - no layout shift */
}

/* Subtle active indicator bar */
.nav-link.exact-active::before {
  content: '';
  position: absolute;
  left: 0;
  top: 8px;
  bottom: 8px;
  width: 3px;
  background: #f97316;
  border-radius: 0 3px 3px 0;
}

.link-icon-wrap {
  width: 20px;
  height: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.nav-link.exact-active .link-icon-wrap {
  color: #f97316;
}

.link-label {
  flex: 1;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  line-height: 1.3;
}

.active-arrow {
  margin-left: auto;
  opacity: 0;
  transition: 0.2s;
  flex-shrink: 0;
}

.nav-link.exact-active .active-arrow {
  opacity: 0.6;
  color: #ea580c;
}

.sidebar-footer {
  padding: 20px 16px;
  background: var(--bg-secondary);
}
.user-profile-card {
  background: var(--bg-primary);
  border-radius: 16px;
  padding: 12px;
  display: flex;
  align-items: center;
  gap: 12px;
  border: 1px solid var(--border-color);
}
.sidebar-footer {
  display: none;
}

/* ── Header User Profile ── */
.user-header-profile {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 4px 6px 4px 12px;
  background: var(--bg-card-hover);
  border-radius: 12px;
  border: 1px solid var(--border-color);
}
.user-avatar-small {
  width: 28px;
  height: 28px;
  border-radius: 8px;
  background: var(--accent);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: 700;
}
.user-header-info {
  display: flex;
  flex-direction: column;
}
.h-user-name {
  font-size: 13px;
  font-weight: 700;
  color: var(--text-primary);
  line-height: 1.2;
}
.h-user-role {
  font-size: 10.5px;
  color: var(--text-muted);
  font-weight: 500;
  text-transform: capitalize;
}
.btn-header-logout {
  width: 32px;
  height: 32px;
  border: none;
  background: transparent;
  color: var(--text-muted);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  transition: 0.2s;
}
.btn-header-logout:hover {
  background: var(--danger-bg);
  color: var(--danger);
}

.user-role-badge {
  font-size: 10.5px;
  font-weight: 700;
  color: var(--text-muted);
  text-transform: capitalize;
}
.btn-logout-icon {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  border: none;
  background: transparent;
  color: var(--text-muted);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}
.btn-logout-icon:hover {
  background: var(--danger-bg);
  color: var(--danger);
}

/* ── Viewport Area ── */
.app-viewport {
  flex: 1;
  margin-left: 260px; /* Synchronized with sidebar width */
  display: flex;
  flex-direction: column;
  min-height: 100vh;
  transition: margin 0.4s ease;
}
.pos-mode .app-viewport {
  margin-left: 0;
}

/* ── Header ── */
.app-header {
  height: 80px;
  background: rgba(var(--bg-primary-rgb), 0.8);
  backdrop-filter: blur(12px);
  border-bottom: 1px solid var(--border-color);
  padding: 0 16px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  position: sticky;
  top: 0;
  z-index: 90;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 16px;
}
/* ── Header Brand (Mobile Only) ── */
.header-brand-box {
  display: none;
  align-items: center;
  gap: 10px;
  cursor: pointer;
  padding: 4px 8px;
  border-radius: 10px;
  transition: background 0.2s;
}

.header-brand-box:hover {
  background: var(--bg-secondary);
}

.h-brand-logo {
  width: 32px;
  height: 32px;
  background: var(--accent-light);
  color: var(--accent);
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

.h-initial-logo {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #f97316, #ea580c);
  color: white;
  font-size: 14px;
  font-weight: 800;
}

.h-brand-info {
  display: flex;
  flex-direction: column;
}

.h-brand-name {
  font-size: 13.5px;
  font-weight: 800;
  color: var(--text-primary);
  letter-spacing: -0.3px;
  line-height: 1;
}

.h-brand-status {
  font-size: 8px;
  font-weight: 700;
  color: var(--accent);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  opacity: 0.8;
  margin-top: 2px;
}

.btn-header-toggle {
  display: none;
  align-items: center;
  justify-content: center;
  width: 42px;
  height: 42px;
  border: none;
  background: transparent;
  color: var(--text-primary);
  cursor: pointer;
  border-radius: 12px;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  margin-right: 8px;
  flex-shrink: 0;
}

.btn-header-toggle:hover {
  background: var(--bg-card-hover);
  color: var(--accent);
  transform: scale(1.05);
}

.btn-header-toggle:active {
  transform: scale(0.95);
}

.btn-sidebar-toggle {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  border: 1px solid var(--border-color);
  background: var(--bg-secondary);
  color: var(--text-secondary);
  cursor: pointer;
  display: none;
  align-items: center;
  justify-content: center;
}
.header-breadcrumb {
  display: flex;
  align-items: center;
  gap: 10px;
}
.bc-parent {
  font-size: 13px;
  font-weight: 600;
  color: var(--text-muted);
}
.bc-current {
  font-size: 13px;
  font-weight: 700;
  color: var(--text-primary);
}

.header-right {
  display: flex;
  align-items: center;
  gap: 28px;
}
.header-actions-group {
  display: flex;
  align-items: center;
  gap: 12px;
}
.header-action-btn {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  border: 1px solid var(--border-color);
  background: var(--bg-secondary);
  color: var(--text-secondary);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}
.header-action-btn:hover {
  border-color: var(--accent);
  color: var(--accent);
  background: var(--accent-light);
}
.header-divider {
  width: 1px;
  height: 24px;
  background: var(--border-color);
}
.header-date {
  display: flex;
  align-items: center;
  gap: 8px;
  color: var(--text-muted);
  font-size: 13px;
  font-weight: 700;
}

/* ── Content ── */
.app-content {
  flex: 1;
  padding: 16px;
  max-width: 1600px;
  width: 100%;
  margin: 0 auto;
}
.app-content.no-padding {
  padding: 0;
  max-width: none;
  margin: 0;
}

.app-content.is-blurred {
  position: relative;
  overflow: hidden;
  user-select: none;
}

.app-content.is-blurred :deep(> *:not(.premium-locked-overlay)) {
  filter: blur(8px) grayscale(0.5);
  opacity: 0.7;
  transition: filter 0.5s ease;
  pointer-events: none; /* Only disable clicks for the blurred part */
}

.page-fade-enter-active, .page-fade-leave-active {
  transition: opacity 0.3s, transform 0.3s;
}
.page-fade-enter-from { opacity: 0; transform: translateY(10px); }
.page-fade-leave-to { opacity: 0; transform: translateY(-10px); }

/* ── Mobile Bottom Navigation ── */
.mobile-bottom-nav {
  display: none;
  position: fixed;
  bottom: 20px; /* Floating from bottom */
  left: 20px;
  right: 20px;
  height: 68px;
  background: rgba(255, 255, 255, 0.82);
  backdrop-filter: blur(15px);
  -webkit-backdrop-filter: blur(15px);
  border: 1px solid rgba(255, 255, 255, 0.4);
  padding: 0 12px;
  align-items: center;
  justify-content: space-around;
  z-index: 1000;
  box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.1);
  border-radius: 28px; /* Extra rounded */
}

.dark .mobile-bottom-nav {
  background: rgba(30, 41, 59, 0.8);
  border-color: rgba(255, 255, 255, 0.08);
  box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.3);
}

.m-nav-item {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 2px;
  text-decoration: none;
  color: #94a3b8;
  height: 48px;
  border-radius: 18px;
  position: relative;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  background: transparent;
  border: none;
  padding: 0;
  cursor: pointer;
}

.m-nav-item.active {
  color: var(--accent);
  background: rgba(249, 115, 22, 0.08); /* Subtle active pill background */
}

.dark .m-nav-item.active {
  background: rgba(249, 115, 22, 0.15);
}

.m-nav-label {
  font-size: 9px;
  font-weight: 700;
  letter-spacing: 0.3px;
  margin-top: 1px;
}

.active-dot {
  display: none; /* Removed dot in favor of pill background */
}

/* FAB Center Button Modernization */
.m-nav-fab-wrapper {
  position: relative;
  width: 72px;
  display: flex;
  justify-content: center;
  z-index: 1010;
}

.m-nav-fab {
  width: 58px;
  height: 58px;
  background: linear-gradient(135deg, #f97316, #ea580c);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 4px solid var(--bg-primary); /* Seamless with background color */
  box-shadow: 0 8px 24px rgba(249, 115, 22, 0.45);
  transform: translateY(-28px);
  cursor: pointer;
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  color: white !important;
  text-decoration: none;
}

.m-nav-fab:hover, .m-nav-fab:active {
  transform: translateY(-32px) scale(1.05);
  box-shadow: 0 12px 28px rgba(249, 115, 22, 0.55);
}

.m-nav-fab.active {
  box-shadow: 0 0 0 6px rgba(249, 115, 22, 0.1), 0 8px 25px rgba(249, 115, 22, 0.45);
}

.btn-menu-trigger {
  color: #64748b;
}

/* Responsive visibility */
/* ── Responsive Layout (Tablet & Mobile) ── */
@media (max-width: 1200px) {
  .app-sidebar {
    position: fixed;
    left: 0; top: 0; bottom: 0;
    width: 280px;
    transform: translateX(-102%);
    visibility: hidden;
    z-index: 2000;
    display: flex !important;
    transition: transform 0.5s cubic-bezier(0.32, 0.72, 0, 1), 
                visibility 0.5s, 
                box-shadow 0.5s;
    box-shadow: none;
  }

  .app-sidebar.mobile-open {
    transform: translateX(0);
    visibility: visible;
    box-shadow: 20px 0 50px rgba(0,0,0,0.15);
  }

  .app-sidebar.mobile-open .nav-link {
    animation: sideSlide 0.5s cubic-bezier(0.32, 0.72, 0, 1) both;
  }

  .app-sidebar.mobile-open .nav-link:nth-child(1) { animation-delay: 0.1s; }
  .app-sidebar.mobile-open .nav-link:nth-child(2) { animation-delay: 0.15s; }
  .app-sidebar.mobile-open .nav-link:nth-child(3) { animation-delay: 0.2s; }
  .app-sidebar.mobile-open .nav-link:nth-child(4) { animation-delay: 0.25s; }
  .app-sidebar.mobile-open .nav-link:nth-child(5) { animation-delay: 0.3s; }

  @keyframes sideSlide {
    from { transform: translateX(-20px); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
  }

  .app-viewport {
    margin-left: 0 !important;
    max-width: 100vw;
    width: 100%;
  }

  .sidebar-top { padding: 20px 16px; }
  .brand-status { font-size: 9px; padding: 2px 6px; }
  .sidebar-nav { padding: 4px 8px; }
  .brand-name { font-size: 14px; }
  .nav-link { padding: 8px 12px; font-size: 13px; }
  .section-group-label { font-size: 12px; padding: 4px 12px; margin-top: 16px; }

  .app-header {
    height: 70px;
    padding: 0 12px;
  }

  .btn-header-toggle {
    display: flex !important;
  }

  .header-brand-box {
    display: flex !important;
  }

  .header-breadcrumb {
    display: none !important;
  }

  .header-date {
    display: none !important;
  }

  .header-divider {
    display: none !important;
  }

  .sidebar-backdrop {
    display: block !important;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
    backdrop-filter: blur(4px);
    z-index: 1900;
  }

  .app-content {
    padding: 12px;
  }
}

/* ── Mobile Specific Adjustments ── */
@media (max-width: 768px) {
  .mobile-bottom-nav {
    display: flex;
  }

  .app-content {
    padding-bottom: 120px !important; 
  }

  .header-right {
    gap: 12px;
  }

  .user-header-info {
    display: none;
  }

  .user-header-profile {
    padding: 4px;
    border-radius: 10px;
  }

  .btn-header-toggle {
    display: none !important;
  }
}

@media (max-width: 480px) {
  .app-header {
    height: 64px;
  }
}

@keyframes modalIn {
  from { opacity: 0; transform: scale(0.9) translateY(20px); }
  to { opacity: 1; transform: scale(1) translateY(0); }
}
</style>

<!-- Non-scoped styles for Teleported Shift Modal -->
<style>
.modal-backdrop.shift-backdrop {
  position: fixed;
  inset: 0;
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
  backdrop-filter: blur(2px) !important;
  background: rgba(0, 0, 0, 0.35) !important;
  padding: 20px;
}

.modal-panel-shift {
  width: 100%;
  max-width: 460px; /* Lebarkan ke kanan dan kiri (Melebar) */
  background: #fff;
  border-radius: 20px;
  padding: 18px 28px;
  box-shadow: 0 20px 40px -10px rgba(0,0,0,0.1);
  position: relative;
  border: 3px solid #f1f5f9;
}

/* Modal Transition Animations */
.modal-enter-active, .modal-leave-active {
  transition: opacity 0.4s ease;
}
.modal-enter-from, .modal-leave-to {
  opacity: 0;
}
.modal-enter-active .modal-panel-shift {
  animation: modalIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.modal-leave-active .modal-panel-shift {
  animation: modalIn 0.3s reverse ease-in forwards;
}

body.hide-mobile-nav .mobile-bottom-nav {
  display: none !important;
}

.modal-shift-header {
  text-align: center;
  margin-bottom: 12px;
}
.modal-shift-title {
  font-size: 18px;
  font-weight: 800;
  color: #1e293b;
  letter-spacing: -0.5px;
}

.welcome-alert {
  background: #fff7ed;
  border: 1px solid #ffedd5;
  border-radius: 10px;
  padding: 8px 12px;
  display: flex;
  gap: 8px;
  margin-bottom: 12px;
}
.welcome-icon {
  width: 24px; height: 24px;
  background: #f97316;
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  color: #fff; flex-shrink: 0; margin-top: 2px;
}
.welcome-title {
  font-size: 13px;
  font-weight: 800;
  color: #c2410c;
  margin-bottom: 4px;
  letter-spacing: 0.5px;
}
.welcome-desc {
  font-size: 12px;
  color: #7c2d12;
  line-height: 1.4;
  font-weight: 500;
}

.shift-input-group {
  margin-bottom: 16px;
}
.shift-label-icon {
  display: flex;
  align-items: center;
  gap: 6px;
  color: #f97316;
  margin-bottom: 8px;
}
.shift-label {
  font-size: 13px;
  font-weight: 800;
  color: #334155;
}
.text-red { color: #ef4444; }

.shift-input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}
.shift-currency {
  position: absolute;
  left: 14px;
  font-size: 14px;
  font-weight: 800;
  color: #94a3b8;
}
.shift-premium-input {
  width: 100%;
  height: 48px;
  background: #fff;
  border: 2px solid #f1f5f9;
  border-radius: 12px;
  padding: 0 14px 0 40px;
  font-size: 15px;
  font-weight: 800;
  color: #1e293b;
  outline: none;
  transition: all 0.2s;
}
.shift-premium-input:focus {
  border-color: #f97316;
  box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.1);
}
.shift-hint {
  font-size: 11px;
  color: #94a3b8;
  margin-top: 6px;
  font-style: italic;
  font-weight: 500;
}

.modal-shift-footer {
  display: flex;
  gap: 10px;
}
.btn-shift-start {
  flex: 1.5;
  height: 44px;
  background: linear-gradient(135deg, #f97316, #ea580c);
  color: #fff;
  border: none;
  border-radius: 10px;
  font-size: 13px;
  font-weight: 800;
  display: flex; align-items: center; justify-content: center;
  gap: 6px;
  cursor: pointer;
  transition: all 0.3s;
  box-shadow: 0 6px 12px -3px rgba(249, 115, 22, 0.3);
}
.btn-shift-start:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 20px -3px rgba(249, 115, 22, 0.4);
}
.btn-shift-later {
  flex: 1;
  height: 44px;
  background: #64748b;
  color: #fff;
  border: none;
  border-radius: 10px;
  font-size: 13px;
  font-weight: 800;
  cursor: pointer;
  transition: all 0.3s;
}
.btn-shift-later:hover {
  background: #475569;
}

.plan-badge-sidebar {
  font-size: 8px;
  font-weight: 800;
  padding: 2px 6px;
  border-radius: 4px;
  margin-left: auto;
  letter-spacing: 0.5px;
  border: 1px solid currentColor;
}

.plan-badge-sidebar.basic {
  background: rgba(37, 99, 235, 0.05);
  color: #2563eb;
  border-color: rgba(37, 99, 235, 0.2);
}

.plan-badge-sidebar.pro {
  background: rgba(249, 115, 22, 0.05);
  color: #f97316;
  border-color: rgba(249, 115, 22, 0.2);
}

.nav-link.exact-active .plan-badge-sidebar {
  background: rgba(249, 115, 22, 0.1);
  border-color: transparent;
}

/* Fade Transition */
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.4s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>
