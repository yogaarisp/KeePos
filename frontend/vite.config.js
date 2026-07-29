import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import path from 'path';

export default defineConfig({
    plugins: [vue()],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, './src'),
        },
    },
    define: {
        // Fix for the Vue hydration feature flag warning
        '__VUE_PROD_HYDRATION_MISMATCH_DETAILS__': false,
    },
    build: {
        outDir: '../backend/public',
        emptyOutDir: false, // Penting agar file laravel seperti index.php dan folder storage tidak terhapus
        rollupOptions: {
            output: {
                manualChunks: {
                    'vendor-vue': ['vue', 'vue-router', 'pinia'],
                    'vendor-utils': ['axios', 'sweetalert2', 'lucide-vue-next'],
                    'vendor-exports': ['jspdf', 'jspdf-autotable', 'xlsx'],
                    'vendor-capacitor': ['@capacitor/core', '@capacitor/app', '@capacitor/haptics', '@capacitor/keyboard', '@capacitor/status-bar', '@capacitor/splash-screen']
                }
            }
        }
    },
    server: {
        host: '0.0.0.0',
        port: 3000,
        strictPort: true,
        proxy: {
            '/api': {
                target: 'http://127.0.0.1:8000',
                changeOrigin: true,
            },
            '/storage': {
                target: 'http://127.0.0.1:8000',
                changeOrigin: true,
            },
            '/sanctum': {
                target: 'http://127.0.0.1:8000',
                changeOrigin: true,
            }
        }
    }
});
