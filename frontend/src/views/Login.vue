<template>
  <div class="login-root">
    <!-- Theme Toggle -->
    <button class="theme-toggle-fixed" @click="theme.toggle()" title="Ganti Tema">
      <Sun v-if="theme.isLight" class="sun-icon" :size="20" />
      <Moon v-else class="moon-icon" :size="20" />
    </button>

    <!-- Animated Background Blobs -->
    <div class="decor-blobs">
      <div class="blob blob-1"></div>
      <div class="blob blob-2"></div>
    </div>

    <div class="login-wrapper">
      <!-- Left side: Brand Panel -->
      <section class="brand-side">
        <div class="brand-content">
          <div class="brand-header-top">
            <div class="logo-box" :class="{ 'no-bg': settingsStore.settings.app_logo }">
              <img v-if="settingsStore.settings.app_logo" :src="baseUrl + '/storage/' + settingsStore.settings.app_logo" class="saas-logo-img">
              <Utensils v-else :size="32" />
            </div>
            <h1 class="brand-title">Kee POS<br>Modern Ecosystem</h1>
          </div>
          <div class="brand-main-text">
            <h1 class="hero-h1">Kelola <br><span class="text-orange">bisnis Anda</span> <br>dengan mudah.</h1>
            <p class="hero-p">
              Sistem Manajemen Restoran Pintar. Kelola operasional harian Anda dengan lebih efisien, aman, dan modern.
            </p>
          </div>
          <div class="brand-footer">
            <div class="stat-item">
              <span class="stat-value">Cloud</span>
              <span class="stat-label">System</span>
            </div>
            <div class="stat-item">
              <span class="stat-value">Secure</span>
              <span class="stat-label">Platform</span>
            </div>
          </div>
        </div>
      </section>

      <!-- Right side: Login Form -->
      <section class="login-side">
        <div class="login-card">
          <div class="login-header">
            <h2>{{ show2FA ? 'Verifikasi Keamanan' : 'Masuk Akun' }}</h2>
            <p>{{ show2FA ? 'Masukkan kode 6 digit yang dikirim ke email Anda' : 'Masukkan akses Anda untuk mengelola POS' }}</p>
          </div>

          <!-- Error Alert -->
          <Transition name="slide-fade">
            <div v-if="auth.error" class="minimal-error">
              <AlertCircle :size="18" />
              <span>{{ auth.error }}</span>
            </div>
          </Transition>

          <!-- Standard Login Form -->
          <form v-if="!show2FA" @submit.prevent="handleLogin" class="clean-form">
            <div class="form-group">
              <label class="form-label">Alamat Email</label>
              <div class="input-wrapper">
                <input 
                  type="email" 
                  v-model="form.email" 
                  placeholder="name@company.com" 
                  required
                  :disabled="auth.loading"
                  class="form-control"
                >
                <Mail :size="18" class="i-icon" />
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Kata Sandi</label>
              <div class="input-wrapper">
                <input 
                  :type="showPassword ? 'text' : 'password'" 
                  v-model="form.password" 
                  placeholder="••••••••" 
                  required
                  :disabled="auth.loading"
                  class="form-control"
                >
                <Lock :size="18" class="i-icon" />
                <button type="button" class="eye-btn" @click="showPassword = !showPassword">
                  <Eye v-if="showPassword" :size="18" />
                  <EyeOff v-else :size="18" />
                </button>
              </div>
            </div>

            <div class="remember-row">
              <label class="check-container">
                <input type="checkbox" v-model="form.remember">
                <div class="checkmark">
                  <Check v-if="form.remember" :size="12" />
                </div>
                <span>Ingat saya</span>
              </label>
              <router-link to="/forgot-password" class="minimal-link">Lupa sandi?</router-link>
            </div>

            <button type="submit" class="btn-submit" :disabled="auth.loading">
              <RefreshCw v-if="auth.loading" :size="18" class="spinning" />
              <template v-else>
                <span>Masuk Sekarang</span>
                <ArrowRight :size="18" />
              </template>
            </button>
          </form>

          <!-- 2FA OTP Code Verification Form -->
          <form v-else @submit.prevent="handleVerify2FA" class="clean-form">
            <div class="form-group text-center">
              <div class="two-factor-icon-wrapper">
                <ShieldAlert :size="48" class="text-orange" />
              </div>
              <p class="verification-desc">
                Kode OTP telah dikirim ke: <br><span class="highlight-email">{{ pendingEmail }}</span>
              </p>
            </div>

            <div class="form-group">
              <label class="form-label text-center">Kode Verifikasi 2FA</label>
              <div class="input-wrapper">
                <input 
                  type="text" 
                  v-model="otpCode" 
                  placeholder="000000" 
                  required
                  maxlength="6"
                  pattern="[0-9]*"
                  autofocus
                  :disabled="auth.loading"
                  class="form-control text-center otp-input"
                >
                <KeyRound :size="18" class="i-icon" />
              </div>
            </div>

            <button type="submit" class="btn-submit" :disabled="auth.loading || otpCode.length !== 6">
              <RefreshCw v-if="auth.loading" :size="18" class="spinning" />
              <template v-else>
                <span>Verifikasi Kode</span>
                <Check :size="18" />
              </template>
            </button>

            <button type="button" class="btn-back-login" @click="backToLogin" :disabled="auth.loading">
              Kembali ke Login
            </button>
          </form>

          <footer class="form-footer-minimal">
            <p v-if="!show2FA">Belum punya akun? <router-link to="/register" class="minimal-link">Daftar Toko</router-link></p>
            <p style="margin-top: 16px;"><router-link to="/" class="minimal-link flex-link"><ArrowLeft :size="14" /> Kembali ke Halaman Utama</router-link></p>
            <p style="margin-top: 12px; opacity: 0.5;">&copy; 2026 Keetech. Smart POS Ecosystem.</p>
          </footer>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup>
import { reactive, inject, onMounted, ref } from 'vue';
import { useAuthStore } from '../stores/auth';
import { useSettingStore } from '../stores/setting';
import { useRouter } from 'vue-router';
import api from '../api';
import { 
  Sun, Moon, Utensils, Mail, Lock, 
  RefreshCw, AlertCircle, ArrowRight, ArrowLeft, Check,
  ShieldAlert, KeyRound, Eye, EyeOff
} from 'lucide-vue-next';
import { baseUrl } from '../api';

const auth = useAuthStore();
const settingsStore = useSettingStore();
const router = useRouter();
const theme = inject('theme');

const form = reactive({
  email: '',
  password: '',
  remember: false
});

const showPassword = ref(false);
const show2FA = ref(false);
const pendingEmail = ref('');
const otpCode = ref('');

onMounted(async () => {
  document.title = 'Login | Kee POS Premium';
  await settingsStore.fetchPublicSettings();
});

const handleLogin = async () => {
  try {
    const result = await auth.login(form);
    if (result && result.two_factor_required) {
      show2FA.value = true;
      pendingEmail.value = result.email;
      auth.error = null;
    } else if (result === true) {
      router.push('/app');
    }
  } catch (err) {
    if (err.response?.status === 403 && err.response?.data?.needs_verification) {
      sessionStorage.setItem('pending_email', err.response.data.email);
      router.push('/register');
    }
  }
};

const handleVerify2FA = async () => {
  if (otpCode.value.length !== 6) {
    auth.error = 'Kode OTP harus berupa 6 digit angka.';
    return;
  }
  try {
    const success = await auth.verify2fa(pendingEmail.value, otpCode.value);
    if (success) {
      router.push('/app');
    }
  } catch (err) {
    // handled by store
  }
};

const backToLogin = () => {
  show2FA.value = false;
  otpCode.value = '';
  auth.error = null;
};
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap');

.login-root {
  --primary: #f97316;
  --primary-hover: #ea580c;
  --primary-glow: rgba(249, 115, 22, 0.3);
  --bg-dark: #070a13;
  --surface: #101624;
  --surface-accent: #1a2235;
  --text-main: #f1f5f9;
  --text-dim: #94a3b8;
  --border: rgba(255, 255, 255, 0.08);
  --card-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
  --font-display: 'Outfit', sans-serif;
  --font-body: 'Plus Jakarta Sans', sans-serif;
  --brand-bg: #101624;
  
  min-height: 100vh;
  background-color: var(--bg-dark);
  color: var(--text-main);
  font-family: var(--font-body);
  transition: all 0.4s ease;
  position: relative;
  overflow: hidden;
}

.light .login-root {
  --bg-dark: #f8fafc;
  --surface: #ffffff;
  --surface-accent: #f1f5f9;
  --text-main: #0f172a;
  --text-dim: #64748b;
  --border: rgba(0, 0, 0, 0.06);
  --card-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
  --primary-glow: rgba(249, 115, 22, 0.15);
  --brand-bg: #0f172a;
}

/* --- Theme Toggle --- */
.theme-toggle-fixed {
  position: fixed;
  top: 24px;
  right: 24px;
  z-index: 100;
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: var(--surface);
  border: 1px solid var(--border);
  color: var(--text-dim);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: var(--card-shadow);
}
.theme-toggle-fixed:hover {
  transform: translateY(-2px);
  color: var(--primary);
  border-color: var(--primary);
}

/* --- Animations --- */
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
@keyframes blob {
  0% { transform: translate(0px, 0px) scale(1); }
  33% { transform: translate(30px, -50px) scale(1.1); }
  66% { transform: translate(-20px, 20px) scale(0.9); }
  100% { transform: translate(0px, 0px) scale(1); }
}

/* --- Background Blobs --- */
.decor-blobs {
  position: absolute;
  inset: 0;
  z-index: 0;
  pointer-events: none;
}
.blob {
  position: absolute;
  width: 600px;
  height: 600px;
  background: radial-gradient(circle, var(--primary-glow) 0%, transparent 70%);
  filter: blur(80px);
  border-radius: 50%;
  opacity: 0.6;
  animation: blob 20s infinite alternate cubic-bezier(0.4, 0, 0.2, 1);
}
.blob-1 { top: -10%; right: -10%; }
.blob-2 { bottom: -10%; left: -10%; animation-delay: -5s; }

/* --- Layout --- */
.login-wrapper {
  display: flex;
  width: 100%;
  height: 100vh;
  position: relative;
  z-index: 1;
}

.brand-side {
  flex: 1;
  background-color: var(--brand-bg);
  background-image: linear-gradient(rgba(7, 10, 19, 0.5), rgba(7, 10, 19, 0.7)), url('/images/login-bg.png');
  background-size: cover;
  background-position: center;
  display: flex;
  flex-direction: column;
  justify-content: center;
  padding: 60px 80px;
  position: relative;
  overflow: hidden;
  transition: all 0.4s ease;
}
.brand-side::before {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, rgba(249, 115, 22, 0.12) 0%, transparent 60%);
}

.login-side {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 60px 80px;
  position: relative;
}

/* --- Brand Content --- */
.brand-content {
  position: relative;
  z-index: 2;
  animation: fadeIn 1s ease-out;
}
.logo-box {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 64px;
  height: 64px;
  background: linear-gradient(135deg, var(--primary), #fb923c);
  border-radius: 18px;
  margin-bottom: 28px;
  box-shadow: 0 10px 30px var(--primary-glow);
  color: white;
}
.logo-box.no-bg {
  background: transparent;
  box-shadow: none;
  width: auto;
  min-width: 64px;
}
.saas-logo-img {
  height: 64px;
  width: auto;
  object-fit: contain;
}
.brand-title {
  font-family: var(--font-display);
  font-size: 42px;
  font-weight: 800;
  line-height: 1.1;
  margin-bottom: 16px;
  background: linear-gradient(to right, #ffffff, #cbd5e1);
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
}
.hero-h1 {
  font-size: 42px;
  font-weight: 700;
  line-height: 1.1;
  margin: 32px 0 0 0;
  letter-spacing: -1.2px;
  color: #fff;
}
.hero-h1 .text-orange { color: #ff7e33; }
.hero-p {
  font-size: 16px;
  color: #94a3b8;
  max-width: 440px;
  line-height: 1.6;
  margin-top: 24px;
}
.brand-footer {
  margin-top: 60px;
  display: flex;
  gap: 32px;
}
.stat-item { display: flex; flex-direction: column; }
.stat-value { font-size: 18px; font-weight: 700; color: #ffffff; }
.stat-label { font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 1.5px; }

/* --- Login Card --- */
.login-card, .form-card-minimal {
  width: 100%;
  max-width: 560px;
  background: var(--surface);
  backdrop-filter: blur(20px);
  border: 1px solid var(--border);
  border-radius: 28px;
  padding: 48px;
  box-shadow: var(--card-shadow);
  animation: fadeIn 0.8s ease-out both;
}
.login-header { margin-bottom: 32px; text-align: center; }
.login-header h2 { font-family: var(--font-display); font-size: 26px; font-weight: 700; color: var(--text-main); margin-bottom: 8px; }
.login-header p { color: var(--text-dim); font-size: 14px; }

.form-group { margin-bottom: 20px; }
.form-label { display: block; font-size: 12px; font-weight: 600; color: var(--text-dim); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 1px; }

.input-wrapper { position: relative; display: flex; align-items: center; }
.i-icon { position: absolute; left: 16px; color: var(--text-dim); transition: 0.2s; pointer-events: none; }
.eye-btn {
  position: absolute;
  right: 16px;
  background: transparent;
  border: none;
  color: var(--text-dim);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0;
  transition: color 0.2s ease;
}
.eye-btn:hover {
  color: var(--text-main);
}
.form-control {
  width: 100%;
  height: 54px;
  background-color: var(--surface-accent);
  border: 1px solid transparent;
  border-radius: 14px;
  padding: 0 44px 0 48px;
  color: var(--text-main);
  font-size: 14px;
  transition: all 0.2s ease;
  outline: none;
}
.form-control:focus {
  border-color: var(--primary);
  background-color: var(--surface);
  box-shadow: 0 0 0 4px var(--primary-glow);
}
.form-control:focus + .i-icon { color: var(--primary); }

.remember-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; }
.check-container { display: flex; align-items: center; gap: 10px; cursor: pointer; font-size: 14px; color: var(--text-dim); }
.check-container input { display: none; }
.checkmark { width: 20px; height: 20px; border: 2px solid var(--border); border-radius: 6px; display: flex; align-items: center; justify-content: center; transition: 0.2s; }
.check-container input:checked + .checkmark { background-color: var(--primary); border-color: var(--primary); color: white; }

.btn-submit {
  width: 100%;
  height: 56px;
  background: linear-gradient(135deg, var(--primary), var(--primary-hover));
  color: white;
  border: none;
  border-radius: 14px;
  font-size: 15px;
  font-weight: 700;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  transition: all 0.3s ease;
  box-shadow: 0 10px 25px var(--primary-glow);
}
.btn-submit:hover:not(:disabled) { transform: translateY(-2px); filter: brightness(1.1); box-shadow: 0 15px 30px var(--primary-glow); }
.btn-submit:disabled { opacity: 0.7; cursor: not-allowed; }

.minimal-error {
  background: rgba(239, 68, 68, 0.1);
  border: 1px solid rgba(239, 68, 68, 0.2);
  color: #ef4444;
  padding: 14px;
  border-radius: 12px;
  font-size: 13px;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 24px;
}

.form-footer-minimal { margin-top: 24px; text-align: center; }
.form-footer-minimal p { font-size: 13px; color: var(--text-dim); }
.minimal-link { color: var(--primary); text-decoration: none; font-weight: 600; transition: 0.2s; }
.minimal-link:hover { text-decoration: underline; opacity: 0.8; }
.flex-link { display: inline-flex; align-items: center; gap: 6px; }

.spinning { animation: spin 1s linear infinite; }
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

/* --- 2FA Specific --- */
.two-factor-icon-wrapper {
  display: flex;
  justify-content: center;
  align-items: center;
  width: 80px;
  height: 80px;
  border-radius: 24px;
  background: rgba(249, 115, 22, 0.1);
  margin: 0 auto 16px;
  color: var(--primary);
  border: 1px solid rgba(249, 115, 22, 0.2);
}
.verification-desc {
  font-size: 14.5px;
  color: var(--text-dim);
  line-height: 1.5;
}
.highlight-email {
  color: var(--text-main);
  font-weight: 700;
}
.otp-input {
  letter-spacing: 8px;
  font-size: 24px;
  font-weight: 800;
  font-family: monospace;
}
.btn-back-login {
  width: 100%;
  height: 48px;
  background: transparent;
  color: var(--text-dim);
  border: 1px solid var(--border);
  border-radius: 14px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  margin-top: 12px;
  transition: all 0.2s ease;
}
.btn-back-login:hover:not(:disabled) {
  border-color: var(--primary);
  color: var(--primary);
  background: rgba(249, 115, 22, 0.05);
}
.text-center {
  text-align: center;
}
.text-orange {
  color: var(--primary);
}

/* --- Mobile Responsive --- */
@media (max-width: 1024px) {
  .login-root { overflow-y: auto; min-height: 100dvh; height: auto; }
  .login-wrapper { flex-direction: column; min-height: 100dvh; height: auto; }
  .brand-side { 
    flex: 0 0 auto;
    min-height: 35dvh;
    width: 100%; 
    padding: 40px 24px 32px; 
    justify-content: center; 
    background-attachment: scroll;
  }
  .brand-content {
    height: auto;
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 20px;
  }
  .brand-header-top {
    display: flex;
    flex-direction: row;
    align-items: center;
    gap: 16px;
    margin-bottom: 4px;
  }
  .logo-box { width: 52px; height: 52px; margin-bottom: 0; }
  .saas-logo-img { height: 52px; }
  .brand-title { font-size: 22px; margin-bottom: 0; line-height: 1.2; }
  .hero-h1 { font-size: 28px; line-height: 1.1; margin: 12px 0 16px 0; }
  .hero-p { display: block; font-size: 14px; margin: 0; line-height: 1.5; max-width: 100%; }
  .brand-footer { display: none; } 
  
  .login-side { 
    flex: 1; 
    width: 100%; 
    padding: 0; 
    margin-top: -24px; 
    z-index: 5;
    display: flex;
    flex-direction: column;
  }
  .login-card { 
    flex: 1;
    max-width: 100%; 
    border-radius: 32px 32px 0 0; 
    padding: 40px 24px 48px; 
    min-height: auto; 
    box-shadow: 0 -15px 40px rgba(0,0,0,0.15);
  }

  .login-header { margin-bottom: 24px; }
  .login-header h2 { font-size: 24px; }
  .form-group { margin-bottom: 16px; }
  .btn-submit { height: 54px; margin-top: 10px; }
}

</style>
