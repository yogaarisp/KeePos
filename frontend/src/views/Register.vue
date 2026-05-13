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
          <div v-if="step === 'register'" class="brand-top-logo desktop-hide">
            <div class="logo-circle">
               <img v-if="shopInfo.shop_logo" :src="baseUrl + '/storage/' + shopInfo.shop_logo" :alt="shopInfo.shop_name" class="side-logo">
               <img v-else-if="shopInfo.app_logo" :src="baseUrl + '/storage/' + shopInfo.app_logo" :alt="shopInfo.app_name" class="side-logo">
               <Utensils v-else :size="30" />
            </div>
            <span class="logo-name-text">Kee POS<br>Modern Ecosystem</span>
          </div>

          <div class="brand-main-text">
            <h1 class="hero-h1">
              Mulai bisnis <br>
              <span class="text-orange">digital Anda</span> <br>
              hari ini.
            </h1>
            <p class="hero-p">
              Daftarkan toko Anda dalam hitungan detik. Kelola stok, 
              penjualan, dan karyawan dengan lebih cerdas.
            </p>
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
            <div class="info-divider"></div>
            <div class="info-item">
              <span>&copy; 2026</span>
            </div>
          </div>
        </div>
      </section>

      <!-- Right side: Form Panel -->
      <section class="form-panel">
        <div class="form-card-minimal">
          <!-- Logo for Desktop -->
          <div v-if="step === 'register'" class="brand-top-logo mobile-hide">
            <div class="logo-circle">
               <img v-if="shopInfo.shop_logo" :src="baseUrl + '/storage/' + shopInfo.shop_logo" :alt="shopInfo.shop_name" class="side-logo">
               <img v-else-if="shopInfo.app_logo" :src="baseUrl + '/storage/' + shopInfo.app_logo" :alt="shopInfo.app_name" class="side-logo">
               <Utensils v-else :size="30" />
            </div>
            <span class="logo-name-text">Kee POS<br>Modern Ecosystem</span>
          </div>

          <!-- 1. Normal Registration Form -->
          <div v-if="step === 'register'">
            <div class="form-header">
              <h2>Daftar Toko Baru</h2>
              <p>Lengkapi data di bawah untuk memulai</p>
            </div>

            <Transition name="slide-fade">
              <div v-if="error" class="minimal-error">
                <AlertCircle :size="18" />
                <span>{{ error }}</span>
              </div>
            </Transition>

            <form @submit.prevent="handleRegister" class="clean-form">
              <div class="field-wrap">
                <div class="input-inner">
                  <Store :size="18" class="i-icon" />
                  <input type="text" v-model="form.store_name" placeholder="Nama Toko / Bisnis" required :disabled="loading">
                </div>
              </div>

              <div class="field-wrap">
                <div class="input-inner">
                  <User :size="18" class="i-icon" />
                  <input type="text" v-model="form.full_name" placeholder="Nama Lengkap Anda" required :disabled="loading">
                </div>
              </div>

              <div class="field-wrap">
                <div class="input-inner">
                  <Mail :size="18" class="i-icon" />
                  <input type="email" v-model="form.email" placeholder="Alamat Email" required :disabled="loading">
                </div>
              </div>

              <div class="field-wrap">
                <div class="input-inner">
                  <Lock :size="18" class="i-icon" />
                  <input type="password" v-model="form.password" placeholder="Buat Kata Sandi" required :disabled="loading">
                </div>
              </div>

              <button type="submit" class="btn-clean-submit" :disabled="loading">
                <RefreshCw v-if="loading" :size="20" class="spinning" />
                <span v-else>Daftar & Trial 14 Hari</span>
              </button>
            </form>

            <footer class="form-footer-minimal">
              <p>Sudah punya akun? <router-link to="/login" class="minimal-link">Masuk di sini</router-link></p>
            </footer>
          </div>

          <!-- 2. OTP Verification Form -->
          <div v-if="step === 'otp'" class="otp-verification-ui">
            <div class="form-header otp-header">
              <div class="otp-logo-box">
                <div class="logo-circle small-logo">
                  <img v-if="shopInfo.shop_logo" :src="baseUrl + '/storage/' + shopInfo.shop_logo" :alt="shopInfo.shop_name" class="side-logo">
                  <img v-else-if="shopInfo.app_logo" :src="baseUrl + '/storage/' + shopInfo.app_logo" :alt="shopInfo.app_name" class="side-logo">
                  <Utensils v-else :size="20" />
                </div>
                <span class="logo-name-text-otp">Kee POS<br>Modern Ecosystem</span>
              </div>
              <h2>Verifikasi OTP</h2>
              <p>Masukkan 6 digit kode yang dikirim ke email Anda</p>
            </div>

            <Transition name="slide-fade">
              <div v-if="error" class="minimal-error">
                <AlertCircle :size="18" />
                <span>{{ error }}</span>
              </div>
            </Transition>

            <form @submit.prevent="handleVerifyOTP" class="clean-form">
              <div class="field-wrap">
                <div class="otp-input-wrapper">
                  <input 
                    type="text" 
                    v-model="otpCode" 
                    placeholder="0 0 0 0 0 0" 
                    maxlength="6" 
                    class="otp-digit-input-premium"
                    required
                    autofocus
                    :disabled="loading"
                  >
                </div>
              </div>

              <button type="submit" class="btn-black-submit" :disabled="loading">
                <RefreshCw v-if="loading" :size="20" class="spinning" />
                <span v-else>Verifikasi & Masuk</span>
              </button>

              <div class="resend-container-minimal">
                <span v-if="resendTimer > 0" class="timer-text">Kirim ulang dalam {{ resendTimer }} detik</span>
                <p v-else class="resend-text">
                  Tidak menerima kode? 
                  <button type="button" @click="handleResendOTP" class="kirim-ulang-btn" :disabled="loading">
                    Kirim ulang
                  </button>
                </p>
              </div>

              <button type="button" class="btn-back-to-reg" @click="resetRegistration" :disabled="loading">
                Ganti Email / Kembali ke Pendaftaran
              </button>
            </form>
          </div>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup>
import { reactive, inject, ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import api from '../api';
import { 
  Sun, Moon, Utensils, User, Lock, Store, Mail,
  RefreshCw, AlertCircle, ShieldCheck, Clock
} from 'lucide-vue-next';
import { baseUrl } from '../api';

const router = useRouter();
const theme = inject('theme');
const auth = useAuthStore();

const step = ref('register'); // 'register' or 'otp'
const otpCode = ref('');
const loading = ref(false);
const error = ref(null);
const resendTimer = ref(0);

const shopInfo = ref({
  shop_name: 'Kee POS',
  shop_logo: null,
  app_logo: null,
  app_name: 'Kee POS'
});

const form = reactive({
  store_name: '',
  full_name: '',
  username: '', // Default to email or generated
  email: sessionStorage.getItem('pending_email') || '',
  password: ''
});

if (sessionStorage.getItem('pending_email')) {
  step.value = 'otp';
}

onMounted(async () => {
  try {
    const res = await api.get('/settings/public');
    if (res.data.success) {
      shopInfo.value = res.data.data;
      document.title = `Daftar | ${shopInfo.value.shop_name || shopInfo.value.app_name}`;
    }
  } catch (err) {
    console.warn('Failed to load shop settings', err);
  }
});

const handleRegister = async () => {
  loading.value = true;
  error.value = null;

  // Generate a temporary username if empty
  if (!form.username) {
    form.username = form.email.split('@')[0] + Math.floor(Math.random() * 1000);
  }

  try {
    const res = await api.post('/register', form);
    if (res.data.requires_otp) {
      step.value = 'otp';
      sessionStorage.setItem('pending_email', res.data.email);
      startResendTimer();
    }
  } catch (err) {
    console.error('Registration error details:', err.response?.data);
    error.value = err.response?.data?.message || 'Pendaftaran gagal.';
    if (err.response?.data?.errors) {
       error.value = Object.values(err.response.data.errors)[0][0];
    }
  } finally {
    loading.value = false;
  }
};

const handleVerifyOTP = async () => {
  if (otpCode.value.length < 6) return;
  
  loading.value = true;
  error.value = null;
  try {
    const res = await api.post('/email/verify-otp', {
      email: form.email || sessionStorage.getItem('pending_email'),
      code: otpCode.value
    });
    
    // Auth success - backend now returns token and user
    localStorage.setItem('auth_token', res.data.access_token);
    localStorage.setItem('user', JSON.stringify(res.data.user));
    auth.token = res.data.access_token;
    auth.user = res.data.user;
    
    sessionStorage.removeItem('pending_email');
    router.push('/app');
  } catch (err) {
    error.value = err.response?.data?.message || 'Kode OTP salah.';
  } finally {
    loading.value = false;
  }
};

const handleResendOTP = async () => {
  loading.value = true;
  try {
    await api.post('/email/resend', { 
      email: form.email || sessionStorage.getItem('pending_email') 
    });
    startResendTimer();
  } catch (err) {
    error.value = 'Gagal mengirim ulang kode.';
  } finally {
    loading.value = false;
  }
};

const startResendTimer = () => {
  resendTimer.value = 60;
  const interval = setInterval(() => {
    resendTimer.value--;
    if (resendTimer.value <= 0) clearInterval(interval);
  }, 1000);
};

const resetRegistration = () => {
  sessionStorage.removeItem('pending_email');
  step.value = 'register';
  form.email = '';
};
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600&display=swap');

.login-root {
  min-height: 100vh;
  min-height: 100dvh;
  background: var(--bg-primary);
  font-family: 'Plus Jakarta Sans', sans-serif;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 40px;
}

/* ── Split Container (Same as Login) ── */
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
  flex: 1;
  position: relative;
  background-color: #0f172a;
  background-image: linear-gradient(rgba(15, 23, 42, 0.4), rgba(15, 23, 42, 0.4)), url('/images/register-bg.png');
  background-size: cover;
  background-position: center;
  padding: 60px 80px;
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
  justify-content: flex-start;
  padding-top: 140px;
}

/* ── Logo variants ── */
.mobile-hide { display: flex !important; margin: 0 auto 20px auto; justify-content: center; }
.desktop-hide { display: flex; }

@media (min-width: 901px) {
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

/* ── Right: Form Panel ── */
.form-panel {
  flex: 1;
  background: var(--bg-card);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 60px 80px;
}

.form-card-minimal { width: 100%; max-width: 560px; }
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

.form-footer-minimal { margin-top: 18px; text-align: center; }
.form-footer-minimal p { font-size: 12px; color: var(--text-muted); }
.minimal-link { font-weight: 600; color: var(--accent); text-decoration: none; }

/* ── OTP UI Specifics (Screenshot Match) ── */
.otp-logo-box { display: flex; align-items: center; justify-content: center; gap: 10px; margin-bottom: 24px; }
.small-logo { width: auto; min-width: 32px; height: 32px; }
.small-logo .side-logo { height: 32px; }
.logo-name-text-otp { font-size: 16px; font-weight: 700; color: var(--text-primary); }

.otp-header h2 { font-size: 26px; color: var(--text-primary); font-weight: 700; margin: 0; }
.otp-header p { font-size: 14px; color: var(--text-muted); margin-top: 6px; }

.otp-digit-input-premium {
  width: 100%; height: 60px; 
  text-align: center; font-size: 28px; font-weight: 700;
  letter-spacing: 12px; border-radius: 16px;
  background: var(--bg-primary); border: 1px solid var(--border-color);
  color: var(--text-primary); outline: none; transition: 0.3s;
  padding-left: 12px;
}
.otp-digit-input-premium:focus { border-color: var(--accent); background: var(--bg-card); box-shadow: 0 0 0 4px rgba(249,115,22,0.1); }
.otp-digit-input-premium::placeholder { color: var(--text-muted); opacity: 0.4; }

.btn-black-submit {
  width: 100%; height: 52px; border: none; border-radius: 14px;
  background: #000; color: #fff; font-size: 15px; font-weight: 600;
  cursor: pointer; transition: 0.3s; display: flex; align-items: center; justify-content: center;
  margin-top: 10px;
}
.btn-black-submit:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(0,0,0,0.15); }

.resend-container-minimal { text-align: center; margin-top: 24px; font-size: 14px; }
.resend-text { color: #64748b; }
.kirim-ulang-btn { 
  background: none; border: none; color: #0f172a; font-weight: 700; 
  cursor: pointer; padding: 0 4px; text-decoration: none;
}
.kirim-ulang-btn:hover { text-decoration: underline; }
.timer-text { color: #94a3b8; font-weight: 500; }

.btn-back-to-reg {
  width: 100%; background: none; border: none; font-size: 14px;
  color: #94a3b8; font-weight: 500; margin-top: 20px; cursor: pointer;
}

/* ── Utilities ── */
.theme-toggle {
  position: fixed; top: 32px; right: 32px; z-index: 100;
  width: 44px; height: 44px; border-radius: 12px; border: 1px solid var(--border-color);
  background: var(--bg-card); color: var(--text-secondary); cursor: pointer;
  display: flex; align-items: center; justify-content: center;
}
.spinning { animation: spin 0.8s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

/* ── Mobile Responsive (Matches Login) ── */
@media (max-width: 1024px) {
  .login-root { padding: 0; min-height: 100dvh; display: block; overflow-y: auto; }
  .login-split-container { 
    flex-direction: column; border-radius: 0; border: none; 
    min-height: 100dvh; max-width: 100%; box-shadow: none; animation: none;
    overflow-y: visible;
  }
  .brand-panel { 
    flex: 0 0 auto;
    min-height: 35vh;
    width: 100%; 
    padding: 40px 24px 45px; 
    justify-content: flex-start; 
    background-attachment: scroll;
  }
  .brand-panel:has(+ .form-panel .otp-verification-ui) { padding-bottom: 30px; }
  
  .mobile-hide { display: none !important; }
  .desktop-hide { display: flex !important; margin-bottom: 8px; }
  
  .brand-top-logo { position: relative; top: auto; left: auto; flex-direction: row; align-items: center; gap: 16px; margin-bottom: 0; }
  .logo-name-text, .logo-name-text-otp { font-size: 20px; line-height: 1.2; }
  
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
