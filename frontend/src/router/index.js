import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import Login from '../views/Login.vue';
import Dashboard from '../views/Dashboard.vue';
import LandingView from '../views/LandingView.vue';

const routes = [
    {
        path: '/',
        name: 'Landing',
        component: LandingView,
        meta: { guest: false } // Landing can be seen by both
    },
    {
        path: '/login',
        name: 'Login',
        component: Login,
        meta: { guest: true }
    },
    {
        path: '/register',
        name: 'Register',
        component: () => import('../views/Register.vue'),
        meta: { guest: true }
    },
    {
        path: '/forgot-password',
        name: 'ForgotPassword',
        component: () => import('../views/ForgotPassword.vue'),
        meta: { guest: true }
    },
    {
        path: '/reset-password/:token',
        name: 'ResetPassword',
        component: () => import('../views/ResetPassword.vue'),
        meta: { guest: true }
    },
    {
        path: '/verify-email/:id/:hash',
        name: 'VerifyEmail',
        component: () => import('../views/VerifyEmail.vue'),
        meta: { guest: true }
    },
    {
        path: '/app',
        component: Dashboard,
        meta: { auth: true },
        children: [
            {
                path: '',
                name: 'Dashboard',
                component: () => import('../views/Home.vue'),
                meta: { title: 'Dashboard' }
            },
            {
                path: 'pos',
                name: 'POS',
                component: () => import('../views/POS.vue'),
                meta: { title: 'POS' }
            },
            {
                path: 'orders',
                name: 'Orders',
                component: () => import('../views/Orders.vue'),
                meta: { title: 'Riwayat Pesanan' }
            },
            {
                path: 'warehouse',
                name: 'Warehouse',
                component: () => import('../views/Warehouse.vue'),
                meta: { title: 'Stok Gudang', requiredPlan: 'basic' }
            },
            {
                path: 'products',
                name: 'Products',
                component: () => import('../views/Products.vue'),
                meta: { title: 'Kelola Produk' }
            },
            {
                path: 'tables',
                name: 'Tables',
                component: () => import('../views/Tables.vue'),
                meta: { title: 'Kelola Meja' }
            },
            {
                path: 'reports',
                name: 'Reports',
                component: () => import('../views/Reports.vue'),
                meta: { title: 'Laporan Penjualan' }
            },
            {
                path: 'inventory-report',
                name: 'InventoryReport',
                component: () => import('../views/InventoryReport.vue'),
                meta: { title: 'Laporan Transaksi Inventori', requiredPlan: 'basic' }
            },
            {
                path: 'settings',
                name: 'Settings',
                component: () => import('../views/Settings.vue'),
                meta: { title: 'Pengaturan Sistem' }
            },
            {
                path: 'users',
                name: 'Users',
                component: () => import('../views/Users.vue'),
                meta: { title: 'Manajemen User' }
            },
            {
                path: 'kitchen',
                name: 'Kitchen',
                component: () => import('../views/Kitchen.vue'),
                meta: { title: 'Stok Dapur', requiredPlan: 'pro' }
            },
            {
                path: 'suppliers',
                name: 'Suppliers',
                component: () => import('../views/Suppliers.vue'),
                meta: { title: 'Manajemen Supplier', requiredPlan: 'pro' }
            },
            {
                path: 'waste',
                name: 'Waste',
                component: () => import('../views/Waste.vue'),
                meta: { title: 'Food Waste' }
            },
            {
                path: 'material-categories',
                name: 'MaterialCategories',
                component: () => import('../views/MaterialCategories.vue'),
                meta: { title: 'Kategori Bahan' }
            },
            {
                path: 'product-categories',
                name: 'ProductCategories',
                component: () => import('../views/ProductCategories.vue'),
                meta: { title: 'Kategori Menu' }
            },
            {
                path: 'shifts',
                name: 'Shifts',
                component: () => import('../views/Shifts.vue'),
                meta: { title: 'Manajemen Shift' }
            },
            {
                path: 'recipes',
                name: 'Recipes',
                component: () => import('../views/Recipes.vue'),
                meta: { title: 'Resep Masakan', requiredPlan: 'pro' }
            },
            {
                path: 'units',
                name: 'Units',
                component: () => import('../views/Units.vue'),
                meta: { title: 'Satuan Ukur' }
            },
            {
                path: 'billing',
                name: 'Billing',
                component: () => import('../views/Billing.vue'),
                meta: { title: 'Langganan & Billing', auth: true }
            },
            {
                path: 'admin/tenants',
                name: 'AdminTenants',
                component: () => import('../views/admin/Tenants.vue'),
                meta: { title: 'Manajemen SaaS', role: 'superadmin', auth: true }
            },
            {
                path: 'admin/tenants/stats',
                name: 'AdminStats',
                component: () => import('../views/admin/Stats.vue'),
                meta: { title: 'Statistik Platform', role: 'superadmin', auth: true }
            },
            {
                path: 'admin/saas/config',
                name: 'AdminSaaSConfig',
                component: () => import('../views/admin/SaaSConfigs.vue'),
                meta: { title: 'SaaS Konfigurasi', role: 'superadmin', auth: true }
            },
            {
                path: 'admin/invoices',
                name: 'AdminInvoices',
                component: () => import('../views/admin/Invoices.vue'),
                meta: { title: 'History Invoice Global', role: 'superadmin', auth: true }
            }
        ]
    }
];

const router = createRouter({
    history: createWebHistory(),
    routes
});

router.beforeEach((to, from, next) => {
    const auth = useAuthStore();

    // If authenticated and trying to go to Landing, Login or Register, go to Dashboard
    if (auth.isAuthenticated && (to.name === 'Landing' || to.name === 'Login' || to.name === 'Register')) {
        next('/app');
    } else if (to.meta.auth && !auth.isAuthenticated) {
        next('/login');
    } else if (to.meta.role && auth.user?.role !== to.meta.role) {
        // Role mismatch: redirect to dashboard
        next('/app');
    } else {
        next();
    }
});

export default router;
