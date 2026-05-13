<template>
  <div class="login-root" :class="theme.isLight ? 'light' : 'dark'">
    <button class="theme-toggle" @click="theme.toggle()">
      <Sun v-if="theme.isLight" :size="20" />
      <Moon v-else :size="20" />
    </button>

    <div class="login-split-container">
      <section class="brand-panel">
        <div class="brand-bg-overlay"></div>
        <div class="brand-content">
          <div class="brand-main-text text-center">
            <h1 class="hero-h1">Verifikasi <br><span class="text-orange">berhasil</span>.</h1>
            <p class="hero-p">Terima kasih telah memverifikasi email Anda. Anda sekarang dapat menggunakan semua fitur kami.</p>
          </div>
        </div>
      </section>

      <section class="form-panel">
        <div class="form-card-minimal">
          <div class="form-header">
            <div class="icon-circle mb-4" :class="{ 'verify-pulse': status === 'loading' }">
              <MailOpen v-if="status === 'loading'" :size="32" class="text-muted" />
              <CheckCircle v-else-if="status === 'success'" :size="32" class="text-orange" />
              <AlertCircle v-else :size="32" class="text-danger" />
            </div>
            
            <h2 v-if="status === 'loading'">Sedang Memverifikasi...</h2>
            <h2 v-else-if="status === 'success'">YAY! Email Terverifikasi</h2>
            <h2 v-else>Verifikasi Gagal</h2>
            
            <p v-if="status === 'loading'">Mohon tunggu sebentar, kami sedang memproses data Anda.</p>
            <p v-else-if="status === 'success'">Akun Anda telah aktif sepenuhnya. Mengalihkan ke Dashboard...</p>
            <p v-else>{{ error || 'Link verifikasi mungkin sudah kadaluarsa atau tidak valid.' }}</p>
          </div>

          <div v-if="status === 'success'" class="mt-8">
             <router-link to="/app" class="btn-clean-submit block no-underline">
                Buka Dashboard Sekarang
             </router-link>
          </div>

          <div v-if="status === 'error'" class="mt-8">
             <router-link to="/login" class="btn-clean-outline block no-underline text-center">
                Kembali ke Login
             </router-link>
          </div>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, inject } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../api';
import { 
  Sun, Moon, MailOpen, CheckCircle, AlertCircle
} from 'lucide-vue-next';

const theme = inject('theme');
const route = useRoute();
const router = useRouter();

const status = ref('loading'); // loading, success, error
const error = ref(null);

onMounted(async () => {
  const { id, hash } = route.params;
  const { expires, signature } = route.query;

  try {
    // We pass everything to our verify endpoint
    await api.get(`/email/verify/${id}/${hash}`, {
      params: { expires, signature }
    });
    status.value = 'success';
    
    // Auto redirect after 3s
    setTimeout(() => {
      router.push('/app');
    }, 3000);
  } catch (err) {
    status.value = 'error';
    error.value = err.response?.data?.message || 'Gagal memverifikasi email.';
  }
});
</script>

<style scoped>
.icon-circle {
  width: 64px;
  height: 64px;
  background: var(--bg-primary);
  border-radius: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 20px;
}

@keyframes pulse {
  0% { transform: scale(1); opacity: 0.8; }
  50% { transform: scale(1.1); opacity: 1; }
  100% { transform: scale(1); opacity: 0.8; }
}
.verify-pulse {
  animation: pulse 1.5s infinite ease-in-out;
}

.btn-clean-submit.block { display: flex; text-decoration: none; justify-content: center; align-items: center; }
.btn-clean-outline {
  width: 100%;
  height: 52px;
  border: 1px solid var(--border-color);
  background: transparent;
  color: var(--text-primary);
  border-radius: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
  text-decoration: none;
}
</style>
