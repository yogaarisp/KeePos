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
          <div class="brand-top-logo desktop-hide">
            <div class="logo-circle">
               <img v-if="settingsStore.settings.shop_logo" :src="baseUrl + '/storage/' + settingsStore.settings.shop_logo" class="side-logo">
               <img v-else-if="settingsStore.settings.app_logo" :src="baseUrl + '/storage/' + settingsStore.settings.app_logo" class="side-logo">
               <Utensils v-else :size="30" />
            </div>
            <span class="logo-name-text">Kee POS<br>Modern Ecosystem</span>
          </div>
          <div class="brand-main-text">
            <h1 class="hero-h1">Menemukan <br><span class="text-orange">kembali</span> <br>akses Anda.</h1>
            <p class="hero-p">Masukkan email Anda untuk menerima link reset kata sandi dan akses kembali bisnis Anda.</p>
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
          <div class="form-header">
            <div class="icon-circle">
              <Key :size="32" class="text-orange" />
            </div>
            <h2>Lupa Kata Sandi?</h2>
            <p>Kami akan mengirimkan instruksi ke email Anda.</p>
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
                <Mail :size="18" class="i-icon" />
                <input 
                  type="email" 
                  v-model="email" 
                  placeholder="Alamat Email Terdaftar" 
                  required
                  :disabled="loading"
                >
              </div>
            </div>

            <button type="submit" class="btn-clean-submit" :disabled="loading">
              <RefreshCw v-if="loading" :size="20" class="spinning" />
              <span v-else>Kirim Link Reset</span>
            </button>
          </form>

          <div v-if="successMsg" class="mt-6">
            <p class="resend-hint">Link tidak sampai? Cek folder Spam atau tunggu beberapa menit.</p>
            <button @click="successMsg = null" class="btn-clean-outline">
              Coba Email Lain
            </button>
          </div>

          <footer class="form-footer-minimal">
            <p>Ingat password Anda? <router-link to="/login" class="minimal-link">Kembali ke Login</router-link></p>
          </footer>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup>
import { ref, inject, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useSettingStore } from '../stores/setting';
import api, { baseUrl } from '../api';
import { 
  Sun, Moon, Key, Mail, Utensils,
  RefreshCw, AlertCircle, CheckCircle, ShieldCheck, Clock
} from 'lucide-vue-next';

const theme = inject('theme');
const settingsStore = useSettingStore();
const email = ref('');
const loading = ref(false);
const error = ref(null);
const successMsg = ref(null);

onMounted(async () => {
  await settingsStore.fetchPublicSettings();
});

const handleSubmit = async () => {
  loading.value = true;
  error.value = null;
  successMsg.value = null;

  try {
    const res = await api.post('/forgot-password', { email: email.value });
    successMsg.value = res.data.message;
  } catch (err) {
    error.value = err.response?.data?.message || 'Gagal mengirim email reset password.';
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
  min-height: 600px;
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: 32px;
  display: flex;
  overflow: hidden;
  box-shadow: 0 40px 100px -30px rgba(0,0,0,0.1);
  animation: fadeIn 0.8s ease;
}

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

.desktop-hide { display: flex; }

@media (min-width: 1025px) {
  .brand-panel .desktop-hide { display: none !important; }
}

.brand-top-logo {
  display: flex;
  align-items: center;
  gap: 12px;
}

.logo-circle {
  width: auto;
  min-width: 64px;
  height: 64px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.side-logo { height: 64px; width: auto; object-fit: contain; }
.logo-name-text { font-size: 16px; font-weight: 700; color: #fff; }

.dark .logo-name-text { color: var(--text-primary); }
.light .mobile-hide .logo-name-text { color: var(--text-primary); }

.hero-h1 {
  font-size: 42px;
  font-weight: 700;
  line-height: 1.1;
  margin: 0;
  letter-spacing: -1.2px;
  color: #fff;
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

.btn-clean-outline {
  width: 100%; height: 52px; border: 1px solid var(--border-color);
  background: transparent; color: var(--text-primary); border-radius: 14px;
  font-weight: 600; cursor: pointer; transition: 0.2s;
}
.btn-clean-outline:hover { background: var(--bg-primary); border-color: var(--accent); }

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

.resend-hint { text-align: center; font-size: 13px; color: var(--text-muted); margin-bottom: 20px; }

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

@media (max-width: 1024px) {
  .login-root { padding: 0; height: 100vh; overflow: hidden; }
  .login-split-container { 
    flex-direction: column; border-radius: 0; border: none; 
    height: 100vh; max-width: 100%; box-shadow: none; animation: none;
  }
  .brand-panel { 
    flex: 0 0 38vh; 
    width: 100%; 
    padding: 32px 24px 30px; 
    justify-content: flex-start; 
    background-attachment: scroll;
  }
  
  .mobile-hide { display: none !important; }
  .desktop-hide { display: flex !important; margin-bottom: 8px; }
  
  .brand-top-logo { position: relative; top: auto; left: auto; flex-direction: row; align-items: center; gap: 16px; margin-bottom: 0; color: #fff; }
  .logo-name-text { font-size: 20px; line-height: 1.2; }
  
  .brand-content { 
    padding: 0; 
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 24px;
    height: 100%;
  }
  
  .hero-h1 { font-size: 24px; line-height: 1.1; margin: 0 0 16px 0; }
  .hero-p { display: block; font-size: 13px; margin: 0; line-height: 1.4; max-width: 80%; }
  
  .brand-footer-info { display: none; }

  .form-panel { 
    flex: 1; 
    width: 100%; 
    padding: 0; 
    background: transparent;
    margin-top: -15px; 
    z-index: 5;
    display: flex;
    flex-direction: column;
  }
  
  .form-card-minimal { 
    flex: 1;
    width: 100%; 
    max-width: 100%; 
    height: auto; 
    background: var(--bg-card);
    border-radius: 32px 32px 0 0; 
    padding: 32px 24px; 
    margin-top: 0; 
    position: relative; 
    z-index: 10;
    box-shadow: 0 -15px 40px rgba(0,0,0,0.1);
  }
}
</style>
