<template>
  <div class="login-root" :class="theme.isLight ? 'light' : 'dark'">
    <!-- Theme Toggle -->
    <button class="theme-toggle" @click="theme.toggle()">
      <Sun v-if="theme.isLight" :size="20" />
      <Moon v-else :size="20" />
    </button>

    <div class="login-split-container">
      <!-- Left side: Brand Panel -->
      <section class="brand-panel">
        <div class="brand-bg-overlay"></div>
        <div class="brand-content">
          <!-- Logo for Mobile -->
          <div class="brand-top-logo desktop-hide">
            <div class="logo-circle">
               <img v-if="shopInfo?.shop_logo" :src="baseUrl + '/storage/' + shopInfo.shop_logo" :alt="shopInfo?.shop_name" class="side-logo">
               <img v-else-if="shopInfo?.app_logo" :src="baseUrl + '/storage/' + shopInfo.app_logo" :alt="shopInfo?.app_name" class="side-logo">
               <Utensils v-else :size="30" />
            </div>
            <span class="logo-name-text">Kee POS Premium</span>
          </div>

          <div class="brand-main-text">
            <h1 class="hero-h1">Amankan <br><span class="text-orange">kembali</span> <br>akun Anda.</h1>
            <p class="hero-p">Gunakan password yang kuat dan unik untuk keamanan maksimal akun bisnis Anda.</p>
          </div>
          
          <div class="brand-footer-info">
            <div class="info-item">
              <ShieldCheck :size="16" />
              <span>Secured</span>
            </div>
            <div class="info-divider"></div>
            <div class="info-item">
              <Clock :size="16" />
              <span>Real-time</span>
            </div>
          </div>
        </div>
      </section>

      <!-- Right side: Form Panel -->
      <section class="form-panel">
        <div class="form-card-minimal">
          <!-- Logo for Desktop -->
          <div class="brand-top-logo mobile-hide">
            <div class="logo-circle">
               <img v-if="shopInfo?.shop_logo" :src="baseUrl + '/storage/' + shopInfo.shop_logo" :alt="shopInfo?.shop_name" class="side-logo">
               <img v-else-if="shopInfo?.app_logo" :src="baseUrl + '/storage/' + shopInfo.app_logo" :alt="shopInfo?.app_name" class="side-logo">
               <Utensils v-else :size="30" />
            </div>
            <span class="logo-name-text">Kee POS Premium</span>
          </div>

          <div class="form-header">
            <div class="icon-circle">
              <Key :size="32" class="text-orange" />
            </div>
            <h2>Reset Password</h2>
            <p>Silakan buat password baru Anda.</p>
          </div>

          <Transition name="slide-fade">
            <div v-if="error" class="minimal-error">
              <AlertCircle :size="18" />
              <span>{{ error }}</span>
            </div>
          </Transition>

          <Transition name="slide-fade">
            <div v-if="successMsg" class="minimal-success">
              <CheckCircle :size="18" />
              <span>{{ successMsg }}</span>
            </div>
          </Transition>

          <form v-if="!successMsg" @submit.prevent="handleSubmit" class="clean-form">
            <div class="field-wrap">
              <div class="input-inner">
                <Lock :size="18" class="i-icon" />
                <input 
                  type="password" 
                  v-model="form.password" 
                  placeholder="Password Baru" 
                  required
                  :disabled="loading"
                >
              </div>
            </div>

            <div class="field-wrap">
              <div class="input-inner">
                <Lock :size="18" class="i-icon" />
                <input 
                  type="password" 
                  v-model="form.password_confirmation" 
                  placeholder="Konfirmasi Password Baru" 
                  required
                  :disabled="loading"
                >
              </div>
            </div>

            <button type="submit" class="btn-clean-submit" :disabled="loading">
              <RefreshCw v-if="loading" :size="20" class="spinning" />
              <span v-else>Simpan Password Baru</span>
            </button>
          </form>

          <div v-if="successMsg" class="mt-8 text-center">
            <router-link to="/login" class="btn-clean-submit block no-underline">
              Kembali ke Login
            </router-link>
          </div>

          <footer class="form-footer-minimal" v-if="!successMsg">
             <p>Ingat password Anda? <router-link to="/login" class="minimal-link">Kembali ke Login</router-link></p>
          </footer>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref, onMounted, inject } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../api';
import { 
  Sun, Moon, ShieldCheck, Mail, Lock, Key, Utensils, Clock,
  RefreshCw, AlertCircle, CheckCircle
} from 'lucide-vue-next';
import { baseUrl } from '../api';

const theme = inject('theme');
const route = useRoute();
const router = useRouter();

const shopInfo = ref({
  shop_name: 'Kee POS',
  shop_logo: null,
  app_logo: null,
  app_name: 'Kee POS'
});

const form = reactive({
  token: '',
  email: '',
  password: '',
  password_confirmation: ''
});

const loading = ref(false);
const error = ref(null);
const successMsg = ref(null);

onMounted(async () => {
  // Fetch Public Settings
  try {
    const res = await api.get('/settings/public');
    if (res.data.success) {
      shopInfo.value = res.data.data;
    }
  } catch (err) {
    console.warn('Failed to load shop settings');
  }

  form.token = route.params.token || route.query.token;
  form.email = route.query.email;

  if (!form.token || !form.email) {
    error.value = 'Link reset password tidak valid atau sudah kadaluarsa.';
  }
});

const handleSubmit = async () => {
  if (form.password !== form.password_confirmation) {
    error.value = 'Password konfirmasi tidak cocok.';
    return;
  }

  loading.value = true;
  error.value = null;

  try {
    const res = await api.post('/reset-password', form);
    successMsg.value = res.data.message;
    setTimeout(() => {
      router.push('/login');
    }, 3000);
  } catch (err) {
    error.value = err.response?.data?.message || 'Gagal mengatur ulang password.';
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600&display=swap');

.login-root {
  min-height: 100vh;
  background: var(--bg-primary);
  font-family: 'Plus Jakarta Sans', sans-serif;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 40px;
}

.login-split-container {
  width: 100%;
  max-width: 1100px;
  min-height: 640px;
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: 32px;
  display: flex;
  overflow: hidden;
  box-shadow: 0 40px 100px -30px rgba(0,0,0,0.1);
  animation: fadeIn 0.8s ease;
}

/* ── Left: Brand Panel ── */
.brand-panel {
  flex: 1.2;
  position: relative;
  background-color: #0f172a;
  background-image: linear-gradient(rgba(15, 23, 42, 0.4), rgba(15, 23, 42, 0.4)), url('/images/login-bg.png');
  background-size: cover;
  background-position: center;
  padding: 60px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  color: #fff;
  overflow: hidden;
}

.brand-bg-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(rgba(15, 23, 42, 0.75), rgba(15, 23, 42, 0.9));
  z-index: 1;
}

.brand-content {
  position: relative;
  z-index: 5;
  height: 100%;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.hero-h1 {
  font-size: 42px;
  font-weight: 700;
  line-height: 1.1;
  margin: 0;
  letter-spacing: -1.2px;
}
.hero-h1 .text-orange { color: #ff7e33; }
.hero-p {
  font-size: 16px;
  line-height: 1.6;
  color: rgba(255, 255, 255, 0.8);
  margin-top: 24px;
  max-width: 440px;
}

.brand-footer-info {
  margin-top: auto;
  display: flex;
  align-items: center;
  gap: 20px;
  font-size: 13px;
  color: rgba(255, 255, 255, 0.6);
}
.info-item { display: flex; align-items: center; gap: 8px; }
.info-divider { width: 1px; height: 12px; background: rgba(255, 255, 255, 0.2); }

/* ── Logo ── */
.brand-top-logo { display: flex; align-items: center; gap: 12px; margin-bottom: 24px; }
.mobile-hide { display: flex !important; justify-content: center; margin-bottom: 20px; }
.desktop-hide { display: flex; margin-bottom: 30px; }

@media (min-width: 901px) {
  .brand-panel .desktop-hide { display: none !important; }
}

.logo-circle { width: 42px; height: 42px; display: flex; align-items: center; justify-content: center; overflow: hidden; }
.side-logo { width: 100%; height: 100%; object-fit: contain; }
.logo-name-text { font-size: 16px; font-weight: 700; letter-spacing: -0.3px; color: #fff; }
.light .form-card-minimal .logo-name-text { color: var(--text-primary); }

/* ── Right: Form Panel ── */
.form-panel {
  flex: 1;
  background: var(--bg-card);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 60px;
}

.form-card-minimal { width: 100%; max-width: 380px; }

.icon-circle {
  width: 64px;
  height: 64px;
  background: var(--bg-primary);
  border: 1px solid var(--border-color);
  border-radius: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 20px;
}

.form-header { margin-bottom: 24px; text-align: center; }
.form-header h2 { font-size: 26px; font-weight: 600; color: var(--text-primary); margin: 0; }
.form-header p { font-size: 14px; color: var(--text-muted); margin-top: 6px; }

.clean-form { display: flex; flex-direction: column; gap: 16px; }

.input-inner { position: relative; display: flex; align-items: center; }
.i-icon { position: absolute; left: 16px; color: var(--text-muted); }
.input-inner input {
  width: 100%; height: 54px; 
  background: var(--bg-primary); border: 1px solid var(--border-color);
  border-radius: 14px; padding-left: 50px;
  font-size: 14px; font-weight: 500; color: var(--text-primary);
  outline: none; transition: 0.2s;
}
.input-inner input:focus { border-color: var(--accent); background: var(--bg-card); }

.btn-clean-submit {
  width: 100%; height: 56px; border: none; border-radius: 14px;
  background: var(--accent); color: #fff; font-size: 15px; font-weight: 600;
  cursor: pointer; transition: 0.2s; display: flex; align-items: center; justify-content: center;
  box-shadow: 0 10px 25px -5px rgba(249,115,22,0.3);
}
.btn-clean-submit:hover:not(:disabled) { transform: translateY(-2px); filter: brightness(1.05); }

.minimal-error {
  background: var(--danger-bg); color: var(--danger);
  padding: 14px 20px; border-radius: 12px; font-size: 13px;
  display: flex; align-items: center; gap: 12px; margin-bottom: 24px;
}

.minimal-success {
  background: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.2);
  padding: 14px 20px; border-radius: 12px; font-size: 13px; font-weight: 500;
  display: flex; align-items: center; gap: 12px; margin-bottom: 24px;
}

.form-footer-minimal { margin-top: 24px; text-align: center; }
.form-footer-minimal p { font-size: 13px; color: var(--text-muted); }
.minimal-link { font-weight: 600; color: var(--accent); text-decoration: none; }

.theme-toggle {
  position: fixed; top: 32px; right: 32px; z-index: 100;
  width: 44px; height: 44px; border-radius: 12px; border: 1px solid var(--border-color);
  background: var(--bg-card); color: var(--text-secondary); cursor: pointer;
  display: flex; align-items: center; justify-content: center;
}

.spinning { animation: spin 0.8s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

/* ── Mobile Responsive ── */
@media (max-width: 900px) {
  .login-root { padding: 0; height: 100vh; overflow: hidden; display: flex; flex-direction: column; }
  .login-split-container { flex-direction: column; border-radius: 0; border: none; height: 100vh; max-width: 100%; box-shadow: none; animation: none; }
  .brand-panel { flex: 0 0 30%; padding: 20px; display: flex; flex-direction: column; justify-content: flex-end; }
  .brand-top-logo { position: absolute; top: 20px; left: 20px; display: flex; align-items: center; }
  .brand-content { padding: 0; display: flex; flex-direction: column; justify-content: flex-end; }
  .hero-h1 { font-size: 22px; line-height: 1.1; margin-bottom: 30px; }
  .hero-p, .brand-footer-info { display: none; }
  .form-panel { flex: 1; padding: 0; background: #000; display: block; }
  .form-card-minimal { width: 100%; max-width: 100%; height: calc(100% + 40px); background: var(--bg-card); border-radius: 40px 40px 0 0; padding: 40px 24px 20px; margin-top: -40px; position: relative; z-index: 10; display: flex; flex-direction: column; justify-content: flex-start; box-shadow: 0 -15px 40px rgba(0,0,0,0.2); }
  .form-header { margin-bottom: 25px; }
  .form-header h2 { font-size: 22px; }
  .form-header p { font-size: 13px; }
  .clean-form { gap: 16px; }
  .input-inner input { height: 48px; }
  .btn-clean-submit { height: 52px; margin-top: 10px; }
  .form-footer-minimal { margin-top: 20px; padding: 10px 0; text-align: center; }
  .theme-toggle { top: 20px; right: 20px; width: 38px; height: 38px; border-radius: 12px; }
}
</style>
