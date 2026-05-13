<template>
  <div class="landing-page">
    <!-- Navbar -->
    <nav class="navbar" :class="{ 'scrolled': isScrolled, 'mobile-open': isMobileMenuOpen }">
      <div class="container nav-container">
        <router-link to="/" class="logo" @click="scrollToTop(); isMobileMenuOpen = false">
          <div class="logo-icon">
            <template v-if="shopInfo.shop_logo || shopInfo.app_logo">
              <img :src="baseUrl + '/storage/' + (shopInfo.shop_logo || shopInfo.app_logo)" :alt="shopInfo.shop_name" class="logo-img">
            </template>
            <Utensils v-else :size="24" />
          </div>
          <span>{{ shopInfo.shop_name || shopInfo.app_name || 'Kee POS' }}</span>
        </router-link>

        <!-- Desktop Links -->
        <div class="nav-links desktop-only">
          <a href="#features">Fitur</a>
          <a href="#pricing">Harga</a>
          <a :href="whatsappLink" target="_blank">Kontak</a>
        </div>

        <div class="nav-actions">
          <div class="nav-auth desktop-only">
            <router-link to="/login" class="btn-login">Masuk</router-link>
            <router-link to="/register" class="btn-register">Coba Gratis</router-link>
          </div>

          <!-- Mobile Toggle -->
          <button class="mobile-toggle" @click="isMobileMenuOpen = !isMobileMenuOpen" aria-label="Toggle Menu">
            <Menu v-if="!isMobileMenuOpen" :size="28" />
            <X v-else :size="28" />
          </button>
        </div>
      </div>

    </nav>
 
    <!-- Premium Mobile Sidebar Drawer -->
    <transition name="drawer">
      <div v-if="isMobileMenuOpen" class="mobile-drawer">
        <div class="drawer-header">
          <router-link to="/" class="logo" @click="scrollToTop(); isMobileMenuOpen = false">
            <div class="logo-icon">
              <template v-if="shopInfo.shop_logo || shopInfo.app_logo">
                <img :src="baseUrl + '/storage/' + (shopInfo.shop_logo || shopInfo.app_logo)" :alt="shopInfo.shop_name" class="logo-img">
              </template>
              <Utensils v-else :size="20" />
            </div>
            <span>{{ shopInfo.shop_name || 'Kee POS' }}</span>
          </router-link>
          <button class="close-drawer-btn" @click="isMobileMenuOpen = false">
            <X :size="24" />
          </button>
        </div>

        <div class="drawer-nav">
          <a href="#features" class="drawer-link" @click="isMobileMenuOpen = false">
            <Layout :size="18" />
            <span>Fitur Unggulan</span>
          </a>
          <a href="#pricing" class="drawer-link" @click="isMobileMenuOpen = false">
            <Tag :size="18" />
            <span>Paket Harga</span>
          </a>
          <a :href="whatsappLink" target="_blank" class="drawer-link" @click="isMobileMenuOpen = false">
            <Mail :size="18" />
            <span>Hubungi Kami</span>
          </a>
        </div>

        <div class="drawer-footer">
          <router-link to="/login" class="drawer-btn-login" @click="isMobileMenuOpen = false">
            Masuk Akun
          </router-link>
          <router-link to="/register" class="drawer-btn-register" @click="isMobileMenuOpen = false">
            Coba Gratis
          </router-link>
          <p class="drawer-copyright">&copy; 2026 Kee POS Premium</p>
        </div>
      </div>
    </transition>

    <!-- Overlay for backdrop -->
    <transition name="fade">
      <div v-if="isMobileMenuOpen" class="drawer-overlay" @click="isMobileMenuOpen = false"></div>
    </transition>

    <!-- Hero Section -->
    <header class="hero">
      <div class="container hero-grid">
        <div class="hero-text">
          <!-- Badge Removed -->
          <h1>Kelola Bisnis Kuliner Anda <br><span class="gradient-text">Lebih Cerdas & Cepat</span></h1>
          <p>{{ shopInfo.shop_name || shopInfo.app_name }} adalah solusi kasir cloud (SaaS) terlengkap untuk warung, restoran, dan cafe. Pantau stok, resep, dan laporan penjualan secara real-time dari mana saja.</p>
          <div class="hero-btns">
            <router-link to="/register" class="btn-primary-lg">Daftar Sekarang — Gratis 14 Hari</router-link>
            <a href="#features" class="btn-secondary-lg">Lihat Fitur</a>
          </div>
          <div class="hero-stats">
            <div class="stat">
              <strong>10</strong>
              <span>Toko Aktif</span>
            </div>
            <div class="stat">
              <strong>99.9%</strong>
              <span>Uptime</span>
            </div>
          </div>
        </div>
        <div class="hero-image">
          <div class="image-wrapper">
             <img src="https://images.unsplash.com/photo-1556742044-3c52d6e88c62?q=80&w=2070&auto=format&fit=crop" alt="POS Dashboard">
             <div class="floating-card c1">
                <TrendingUp :size="16" />
                <span>Profit +24%</span>
             </div>
             <div class="floating-card c2">
                <Package :size="16" />
                <span>Stok Aman</span>
             </div>
          </div>
        </div>
      </div>
    </header>

    <!-- Features Section -->
    <section id="features" class="features">
      <div class="container overflow-hidden">
        <div class="section-head">
          <span class="sub">Kenapa Pilih {{ shopInfo.shop_name || shopInfo.app_name }}?</span>
          <h2>Fitur Unggulan Untuk Bisnis Anda</h2>
        </div>
        
        <div class="features-slider-container">
          <div 
            class="features-slider-track" 
            :style="{ transform: `translateX(-${currentSlide * slideWidth}%)` }"
            @touchstart="handleTouchStart"
            @touchend="handleTouchEnd"
          >
            <div 
              v-for="(feature, index) in features" 
              :key="index"
              class="feature-slide"
            >
              <div class="feature-card h-full">
                <div class="f-icon">
                  <component :is="feature.icon" :size="24" />
                </div>
                <h3>{{ feature.title }}</h3>
                <p>{{ feature.description }}</p>
              </div>
            </div>
          </div>

          <!-- Slider Dots -->
          <div class="slider-dots">
            <button 
              v-for="(_, index) in Array.from({ length: totalSlides })" 
              :key="index"
              class="dot"
              :class="{ active: currentSlide === index }"
              @click="goToSlide(index)"
              :aria-label="`Go to slide ${index + 1}`"
            ></button>
          </div>
        </div>
      </div>
    </section>

    <!-- Pricing Section -->
    <section id="pricing" class="pricing">
      <div class="container">
        <div class="section-head">
          <span>Pilih Paket Anda</span>
          <h2>Harga Transparan, Tanpa Biaya Tersembunyi</h2>
        </div>
        <div class="pricing-grid">
          <div class="price-card">
            <div class="price-header">
              <h3>TRIAL</h3>
              <div class="price">
                <span class="old-price">Rp 50.000</span>
                Rp 0<span>/akun</span>
              </div>
              <p>Coba gratis fitur premium selama {{ shopInfo.default_trial_days || 20 }} hari</p>
            </div>
            <ul>
              <li v-for="f in (shopInfo.plan_free_features || [])" :key="f">
                <Check :size="16" /> {{ f }}
              </li>
            </ul>
            <router-link to="/register" class="btn-price">Mulai Gratis</router-link>
          </div>
          <div class="price-card">
            <div class="price-header">
              <h3>BASIC</h3>
              <div class="price">
                {{ formatPrice(shopInfo.plan_basic_price) }}<span>/akun</span>
              </div>
              <p>Untuk bisnis yang berkembang</p>
            </div>
            <ul>
              <li v-for="f in (shopInfo.plan_basic_features || [])" :key="f">
                <Check :size="16" /> {{ f }}
              </li>
            </ul>
            <router-link to="/register" class="btn-price">Pilih Paket</router-link>
          </div>
          <div class="price-card featured">
            <div class="popular">POPULER</div>
            <div class="price-header">
              <h3>PRO</h3>
              <div class="price">
                {{ formatPrice(shopInfo.plan_pro_price) }}<span>/akun</span>
              </div>
              <p>Solusi lengkap bisnis kuliner</p>
            </div>
            <ul>
              <li v-for="f in (shopInfo.plan_pro_features || [])" :key="f">
                <Check :size="16" /> {{ f }}
              </li>
            </ul>
            <router-link to="/register" class="btn-price">Pilih Paket</router-link>
          </div>
        </div>
      </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
      <div class="container">
        <div class="footer-grid">
          <div class="footer-brand">
             <router-link to="/" class="logo" @click="scrollToTop(); isMobileMenuOpen = false">
                <div class="logo-icon">
                  <template v-if="shopInfo.shop_logo || shopInfo.app_logo">
                    <img :src="baseUrl + '/storage/' + (shopInfo.shop_logo || shopInfo.app_logo)" :alt="shopInfo.shop_name" class="logo-img">
                  </template>
                  <Utensils v-else :size="20" />
                </div>
                <span>{{ shopInfo.shop_name || 'Kee POS' }}</span>
              </router-link>
              <p>Solusi manajemen kuliner modern untuk masa depan bisnis Anda.</p>
          </div>
          <div class="footer-links">
              <h4>Produk</h4>
              <a href="#features">Fitur</a>
              <a href="#pricing">Harga</a>
              <a href="#">Demo</a>
          </div>
          <div class="footer-links">
              <h4>Perusahaan</h4>
              <a href="#">Tentang Kami</a>
              <a href="#">Karir</a>
              <a :href="whatsappLink" target="_blank">Kontak</a>
          </div>
        </div>
        <div class="footer-bottom">
          <p>&copy; 2026 {{ shopInfo.shop_name }}. All rights reserved.</p>
          <div class="social-links">
            <a href="#">Terms</a>
            <a href="#">Privacy</a>
            <a href="#">Cookies</a>
          </div>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';
import { 
  Utensils, TrendingUp, Package, Layout, Database, 
  FileText, ShieldCheck, Check, Menu, X, Tag, Mail
} from 'lucide-vue-next';
import api, { baseUrl } from '../api';
import { updatePlatformMeta } from '../utils/metaTags';

const isScrolled = ref(false);
const isMobileMenuOpen = ref(false);

const features = [
  {
    icon: Layout,
    title: 'Kasir Cloud (POS)',
    description: 'Transaksi lancar dengan tampilan modern yang mudah digunakan oleh siapapun.'
  },
  {
    icon: Database,
    title: 'Manajemen Stok',
    description: 'Pantau stok bahan baku dari gudang hingga dapur secara otomatis.'
  },
  {
    icon: FileText,
    title: 'Laporan Otomatis',
    description: 'Laporan harian, mingguan, hingga bulanan yang dapat diekspor ke Excel.'
  },
  {
    icon: ShieldCheck,
    title: 'Keamanan Data',
    description: 'Data Anda tersimpan aman di cloud dengan cadangan harian otomatis.'
  }
];

const currentSlide = ref(0);
const slideWidth = ref(100); // 100% width per slide
const itemsToShow = ref(3); // Default for desktop

const totalSlides = ref(Math.ceil(features.length / 1)); // This will be updated on mount

const updateSliderConfig = () => {
  if (window.innerWidth < 768) {
    itemsToShow.value = 1;
    slideWidth.value = 100;
  } else if (window.innerWidth < 1024) {
    itemsToShow.value = 2;
    slideWidth.value = 50;
  } else {
    itemsToShow.value = 3;
    slideWidth.value = 33.333;
  }
  totalSlides.value = Math.max(0, features.length - itemsToShow.value + 1);
};

const goToSlide = (index) => {
  currentSlide.value = index;
};

// Touch Swiping Logic
const touchStartX = ref(0);
const touchEndX = ref(0);

const handleTouchStart = (e) => {
  touchStartX.value = e.touches[0].clientX;
};

const handleTouchEnd = (e) => {
  touchEndX.value = e.changedTouches[0].clientX;
  handleSwipe();
};

const handleSwipe = () => {
  const swipeDistance = touchStartX.value - touchEndX.value;
  const minSwipeDistance = 50; // Minimum pixels to trigger swipe

  if (swipeDistance > minSwipeDistance) {
    // Swipe Left -> Next Slide
    if (currentSlide.value < totalSlides.value - 1) {
      currentSlide.value++;
    } else {
      currentSlide.value = 0; // Loop back
    }
    // Reset auto-slide timer if user interacts
    if (sliderInterval) {
      clearInterval(sliderInterval);
      startAutoSlide();
    }
  } else if (swipeDistance < -minSwipeDistance) {
    // Swipe Right -> Previous Slide
    if (currentSlide.value > 0) {
      currentSlide.value--;
    } else {
      currentSlide.value = totalSlides.value - 1; // Go to end
    }
    // Reset auto-slide timer
    if (sliderInterval) {
      clearInterval(sliderInterval);
      startAutoSlide();
    }
  }
};

let sliderInterval = null;

const startAutoSlide = () => {
  sliderInterval = setInterval(() => {
    if (totalSlides.value > 0) {
      currentSlide.value = (currentSlide.value + 1) % totalSlides.value;
    }
  }, 4000);
};

// Lock body scroll when mobile menu is open
watch(isMobileMenuOpen, (val) => {
  if (val) {
    document.body.style.overflow = 'hidden';
  } else {
    document.body.style.overflow = '';
  }
});

const shopInfo = ref({
  shop_name: 'Kee POS',
  shop_logo: null,
  app_logo: null,
  app_name: 'Kee POS',
  plan_basic_price: 99000,
  plan_pro_price: 249000
});

const whatsappLink = ref('https://wa.me/628123456789');

watch(() => shopInfo.value.app_whatsapp, (val) => {
  if (val) {
    whatsappLink.value = `https://wa.me/${val.replace(/\D/g, '')}`;
  }
}, { immediate: true });

const formatPrice = (price) => {
  if (!price) return 'Rp 0';
  if (price >= 1000000) {
    return 'Rp ' + (price / 1000000).toFixed(1).replace('.0', '') + 'jt';
  }
  if (price >= 1000) {
    return 'Rp ' + (price / 1000).toFixed(0) + 'rb';
  }
  return 'Rp ' + price;
};

const handleScroll = () => {
  isScrolled.value = window.scrollY > 50;
};

const scrollToTop = () => {
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

onMounted(async () => {
  window.addEventListener('scroll', handleScroll);
  window.addEventListener('resize', updateSliderConfig);
  updateSliderConfig();
  startAutoSlide();
  
  try {
    const res = await api.get('/settings/public');
    if (res.data.success) {
      shopInfo.value = res.data.data;
      // Update semua meta tags, favicon, OG image dari database
      updatePlatformMeta(res.data.data, baseUrl);
    }
  } catch (err) {
    console.warn('Failed to load platform settings', err);
  }
});

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll);
  window.removeEventListener('resize', updateSliderConfig);
  if (sliderInterval) clearInterval(sliderInterval);
});
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

.landing-page {
  font-family: 'Plus Jakarta Sans', sans-serif;
  color: #0f172a;
  background: #fff;
  overflow-x: hidden;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 24px;
}

/* Navbar */
.navbar {
  position: fixed;
  top: 0; left: 0; right: 0;
  height: 80px;
  display: flex;
  align-items: center;
  z-index: 1000;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.navbar.scrolled {
  background: rgba(255, 255, 255, 0.85);
  backdrop-filter: blur(15px);
  height: 70px;
  box-shadow: 0 10px 30px rgba(0,0,0,0.05);
}

.nav-container {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.logo {
  display: flex;
  align-items: center;
  gap: 12px;
  font-weight: 800;
  font-size: clamp(18px, 2vw, 22px);
  color: #f97316;
  flex-shrink: 0;
  text-decoration: none;
  cursor: pointer;
}

.logo-icon {
  width: 40px;
  height: 40px;
  background: #fff7ed;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  box-shadow: 0 4px 10px rgba(249, 115, 22, 0.1);
}

.logo-img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.nav-links {
  display: flex;
  gap: 40px;
}

.nav-links a {
  text-decoration: none;
  font-weight: 600;
  color: #475569;
  font-size: 14px;
  transition: color 0.2s;
}

.nav-links a:hover { color: #f97316; }

.nav-actions {
  display: flex;
  align-items: center;
  gap: 16px;
}

.nav-auth {
  display: flex;
  align-items: center;
  gap: 16px;
}

.btn-login {
  text-decoration: none;
  font-weight: 600;
  color: #475569;
  padding: 10px 20px;
  transition: color 0.2s;
}

.btn-login:hover { color: #0f172a; }

.btn-register {
  text-decoration: none;
  background: #f97316;
  color: #fff;
  font-weight: 700;
  padding: 12px 24px;
  border-radius: 12px;
  box-shadow: 0 10px 20px rgba(249, 115, 22, 0.2);
  transition: all 0.3s;
}

.btn-register:hover { 
  transform: translateY(-2px); 
  box-shadow: 0 15px 25px rgba(249, 115, 22, 0.3);
  filter: brightness(1.1);
}

/* Mobile Toggle */
.mobile-toggle {
  display: none;
  background: none;
  border: none;
  color: #475569;
  cursor: pointer;
  padding: 8px;
  z-index: 1001;
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.mobile-toggle:active {
  transform: scale(0.9) rotate(90deg);
}

/* --- Premium Mobile Sidebar Drawer --- */
.mobile-drawer {
  position: fixed;
  top: 0; right: 0; bottom: 0;
  width: min(320px, 85vw);
  background: rgba(255, 255, 255, 0.98);
  backdrop-filter: blur(25px);
  -webkit-backdrop-filter: blur(25px);
  z-index: 5000;
  display: flex;
  flex-direction: column;
  box-shadow: -10px 0 40px rgba(0,0,0,0.1);
  padding: 0;
}

.dark .mobile-drawer {
  background: rgba(15, 23, 42, 0.98);
  border-left: 1px solid rgba(255, 255, 255, 0.05);
}

.drawer-header {
  height: 80px;
  padding: 0 24px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid rgba(0,0,0,0.05);
}

.close-drawer-btn {
  background: rgba(0,0,0,0.04);
  border: none;
  width: 36px;
  height: 36px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #64748b;
  cursor: pointer;
  transition: all 0.2s;
}

.drawer-nav {
  padding: 32px 16px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.drawer-link {
  text-decoration: none;
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 14px 16px;
  border-radius: 12px;
  color: #475569;
  font-size: 16px;
  font-weight: 600;
  transition: all 0.2s;
}

.dark .drawer-link { color: #cbd5e1; }

.drawer-link:active {
  background: rgba(249, 115, 22, 0.08);
  color: #f97316;
}

.drawer-link svg {
  color: #94a3b8;
  transition: color 0.2s;
}

.drawer-link:active svg {
  color: #f97316;
}

.drawer-footer {
  margin-top: auto;
  padding: 32px 24px 48px;
  display: flex;
  flex-direction: column;
  gap: 12px;
  background: rgba(0,0,0,0.02);
}

.drawer-btn-login {
  text-decoration: none;
  height: 52px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f1f5f9;
  color: #475569;
  border-radius: 12px;
  font-weight: 700;
  font-size: 15px;
}

.drawer-btn-register {
  text-decoration: none;
  height: 52px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f97316;
  color: #fff;
  border-radius: 12px;
  font-weight: 800;
  font-size: 15px;
  box-shadow: 0 10px 20px rgba(249, 115, 22, 0.2);
}

.drawer-copyright {
  text-align: center;
  font-size: 11px;
  color: #94a3b8;
  margin-top: 12px;
}

.drawer-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.4);
  backdrop-filter: blur(6px);
  z-index: 4999;
}

/* Animations */
.drawer-enter-active, .drawer-leave-active { transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1); }
.drawer-enter-from, .drawer-leave-to { transform: translateX(100%); }

.fade-enter-active, .fade-leave-active { transition: opacity 0.4s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

/* Hero */
.hero {
  padding: clamp(140px, 15vh, 200px) 0 clamp(60px, 10vh, 100px);
  background: linear-gradient(180deg, #fff7ed 0%, #fff 100%);
}

.hero-grid {
  display: grid;
  grid-template-columns: 1.2fr 1fr;
  align-items: center;
  gap: clamp(40px, 5vw, 80px);
}

.badge {
  display: inline-block;
  background: rgba(249, 115, 22, 0.1);
  color: #f97316;
  padding: 6px 16px;
  border-radius: 100px;
  font-weight: 700;
  font-size: 12px;
  margin-bottom: 24px;
}

.hero-text h1 {
  font-size: clamp(28px, 4vw, 48px); /* Reduced from 56px max */
  line-height: 1.15;
  font-weight: 800;
  margin: 0 0 16px;
  letter-spacing: -0.04em;
}

.gradient-text {
  background: linear-gradient(90deg, #f97316, #ef4444);
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
}

.hero-text p {
  font-size: 16px; /* Optimized for desktop reading */
  line-height: 1.6;
  color: #475569;
  margin-bottom: 32px;
  max-width: 540px;
}

.hero-btns {
  display: flex;
  flex-wrap: wrap; /* Ensure responsive wrapping */
  align-items: center;
  gap: 12px;
  margin-bottom: 40px;
}

.btn-primary-lg {
  text-decoration: none;
  background: #f97316;
  color: #fff;
  font-weight: 800;
  padding: 16px 36px;
  border-radius: 14px;
  font-size: 16px;
  text-align: center;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  white-space: nowrap;
}

@media (max-width: 480px) {
  .btn-primary-lg { white-space: normal; }
}

.btn-primary-lg:hover {
  transform: translateY(-2px);
  box-shadow: 0 15px 30px rgba(249, 115, 22, 0.3);
  filter: brightness(1.1);
}

.btn-secondary-lg {
  text-decoration: none;
  background: #fff;
  color: #0f172a;
  font-weight: 700;
  padding: 16px 32px;
  border-radius: 14px;
  border: 1.5px solid #e2e8f0;
  font-size: 16px;
  text-align: center;
  transition: all 0.3s;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.btn-secondary-lg:hover {
  background: #f8fafc;
  border-color: #cbd5e1;
  transform: translateY(-2px);
}

.hero-stats {
  display: flex;
  gap: 48px;
}

.stat { display: flex; flex-direction: column; }
.stat strong { font-size: 24px; font-weight: 800; color: #0f172a; }
.stat span { font-size: 14px; color: #64748b; }

.hero-image { position: relative; width: 100%; }
.image-wrapper {
  position: relative;
  background: #fff;
  border-radius: 32px;
  padding: 12px;
  box-shadow: 0 40px 80px rgba(0,0,0,0.1);
  transform: rotate(2deg);
  max-width: 500px;
  margin: 0 auto;
}

.image-wrapper img {
  width: 100%;
  aspect-ratio: 16/10;
  object-fit: cover;
  border-radius: 20px;
}

.floating-card {
  position: absolute;
  background: #fff;
  padding: clamp(10px, 2vw, 16px);
  border-radius: 16px;
  box-shadow: 0 20px 40px rgba(0,0,0,0.1);
  display: flex;
  align-items: center;
  gap: 12px;
  font-weight: 700;
  font-size: 13px;
  white-space: nowrap;
}

.c1 { top: 10%; left: -5%; color: #10b981; }
.c2 { bottom: 15%; right: -5%; color: #f97316; }

/* Features */
.features { padding: clamp(60px, 10vh, 120px) 0; }
.section-head { text-align: center; margin-bottom: clamp(40px, 8vh, 80px); }
.section-head span { color: #f97316; font-weight: 700; font-size: 14px; text-transform: uppercase; letter-spacing: 2px; }
.section-head h2 { font-size: clamp(28px, 4vw, 40px); font-weight: 800; margin-top: 12px; letter-spacing: -0.02em; }
/* ── Features Section ── */
.features {
  padding: 80px 0;
  background: #f8fafc;
}

.overflow-hidden {
  overflow: hidden;
}

.features-slider-container {
  position: relative;
  width: 100%;
  margin-top: 50px;
}

.features-slider-track {
  display: flex;
  transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
  will-change: transform;
}

.feature-slide {
  flex: 0 0 33.333%; /* Default for desktop */
  padding: 0 12px;
  box-sizing: border-box;
}

.feature-card {
  padding: 40px;
  background: #fff;
  border-radius: 24px;
  border: 1px solid #e2e8f0;
  transition: 0.3s;
  height: 100%;
  display: flex;
  flex-direction: column;
}

.feature-card:hover {
  transform: translateY(-8px);
  box-shadow: 0 20px 40px -10px rgba(0,0,0,0.05);
  border-color: var(--accent);
}

.f-icon {
  width: 56px;
  height: 56px;
  background: rgba(249, 115, 22, 0.1);
  color: var(--accent);
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 24px;
}

.feature-card h3 {
  font-size: 20px;
  font-weight: 700;
  margin-bottom: 12px;
}

.feature-card p {
  color: #64748b;
  line-height: 1.6;
  font-size: 15px;
}

/* Slider Dots */
.slider-dots {
  display: flex;
  justify-content: center;
  gap: 10px;
  margin-top: 40px;
}

.dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: #cbd5e1;
  border: none;
  cursor: pointer;
  padding: 0;
  transition: 0.3s;
}

.dot.active {
  background: var(--accent);
  width: 24px;
  border-radius: 10px;
}

@media (max-width: 1024px) {
  .feature-slide {
    flex: 0 0 50%;
  }
}

@media (max-width: 768px) {
  .features { padding: 60px 0; }
  .feature-slide {
    flex: 0 0 100%;
  }
  .feature-card { padding: 30px; }
}

/* Pricing */
.pricing { padding: 80px 0; background: #fafafa; }
.pricing-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 24px;
  max-width: 1080px;
  margin: 0 auto;
  position: relative;
}

/* Base card state */
.price-card {
  background: #fff;
  padding: 40px;
  border-radius: 32px;
  border: 1px solid #e2e8f0;
  display: flex;
  flex-direction: column;
  height: 100%;
  transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
  position: relative;
  cursor: pointer;
}

/* Hover effect for all cards */
.price-card:hover {
  transform: translateY(-12px) scale(1.02);
  border-color: #f97316;
  box-shadow: 0 40px 70px -15px rgba(249, 115, 22, 0.15);
  z-index: 10;
}

/* Non-hovered cards dimming effect */
.pricing-grid:hover .price-card:not(:hover) {
  opacity: 0.7;
  filter: grayscale(0.2) blur(0.5px);
  transform: scale(0.98);
}

.price-header { margin-bottom: 32px; }

.price-card.featured {
  border-color: #e2e8f0;
  border-width: 1px;
  background: #fff;
  transform: scale(1.05); /* Keep it slightly larger to denote importance */
  z-index: 2;
}

.price-card.featured:hover {
  transform: translateY(-16px) scale(1.08); 
  border-color: #f97316;
  box-shadow: 0 50px 80px -20px rgba(249, 115, 22, 0.25);
}

.popular {
  position: absolute;
  top: 24px; right: 24px;
  background: #f97316;
  color: #fff;
  padding: 6px 14px;
  border-radius: 100px;
  font-size: 11px;
  font-weight: 800;
}

.price-card h3 { font-size: 14px; font-weight: 800; color: #64748b; margin-bottom: 16px; letter-spacing: 1px; }
.price { font-size: clamp(28px, 3vw, 36px); font-weight: 800; margin-bottom: 8px; color: #0f172a; display: flex; align-items: baseline; gap: 8px; }
.price span { font-size: 14px; color: #64748b; font-weight: 500; }
.old-price { font-size: 16px; color: #94a3b8; text-decoration: line-through; font-weight: 500; }
.price-card p { font-size: 14px; color: #64748b; }

.price-card ul { list-style: none; padding: 0; margin: 0 0 32px; flex-grow: 1; }
.price-card li { display: flex; align-items: flex-start; gap: 12px; font-size: 14px; color: #475569; margin-bottom: 16px; }
.price-card li svg { color: #10b981; flex-shrink: 0; margin-top: 2px; }

.btn-price {
  text-decoration: none;
  background: #f1f5f9;
  color: #475569;
  text-align: center;
  padding: 16px;
  border-radius: 14px;
  font-weight: 700;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.price-card:hover .btn-price {
  background: #f97316;
  color: #fff;
  box-shadow: 0 10px 20px rgba(249, 115, 22, 0.2);
  transform: translateY(-2px);
}

.btn-price:hover {
  filter: brightness(1.1);
}

.btn-price-primary {
  text-decoration: none;
  background: #f97316;
  color: #fff;
  text-align: center;
  padding: 16px;
  border-radius: 14px;
  font-weight: 800;
  box-shadow: 0 10px 20px rgba(249, 115, 22, 0.2);
  transition: all 0.3s;
}

.price-card:hover .btn-price-primary {
  transform: translateY(-2px);
  filter: brightness(1.1);
}

/* Footer */
.footer { padding: 80px 0 40px; border-top: 1px solid #f1f5f9; background: #fff; }

.footer-grid {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr;
  gap: 60px;
  margin-bottom: 60px;
}

.footer-brand p {
  margin-top: 24px;
  color: #64748b;
  line-height: 1.6;
  max-width: 300px;
  font-size: 15px;
}

.footer-links h4 {
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
  margin-bottom: 24px;
}

.footer-links {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.footer-links a {
  text-decoration: none;
  color: #64748b;
  font-size: 14px;
  transition: color 0.2s;
}

.footer-links a:hover { color: #f97316; }

.footer-bottom {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 40px;
  border-top: 1px solid #f1f5f9;
  font-size: 14px;
  color: #94a3b8;
}

.social-links { display: flex; gap: 24px; }
.social-links a { text-decoration: none; color: #94a3b8; }

/* Animations */
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

.slide-enter-active, .slide-leave-active { transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1); }
.slide-enter-from, .slide-leave-to { transform: translateX(100%); }

/* Responsive Queries */
@media (max-width: 1024px) {
  .pricing-grid { gap: 16px; }
  .price-card { padding: 32px; }
}

@media (max-width: 991px) {
  .desktop-only { display: none; }
  .mobile-toggle { display: block; }
  
  .hero-grid { grid-template-columns: 1fr; text-align: center; gap: 60px; }
  .hero-text h1 { margin-left: auto; margin-right: auto; }
  .hero-text p { margin-left: auto; margin-right: auto; }
  .hero-btns { justify-content: center; }
  .hero-stats { justify-content: center; }
  .image-wrapper { transform: rotate(0); }
  
  .pricing-grid { grid-template-columns: 1fr; max-width: 500px; }
  .price-card.featured { transform: none; box-shadow: none; border-width: 1px; }
  .price-card.featured:hover { transform: translateY(-8px); }
  .pricing-grid:hover .price-card:not(:hover) { opacity: 1; filter: none; transform: none; }
  
  .footer-grid { grid-template-columns: 1fr 1fr; gap: 40px; }
  .footer-brand { grid-column: span 2; }
}

@media (max-width: 640px) {
  .container { padding: 0 20px; }
  .hero { padding: 100px 0 40px; }
  .features, .pricing { padding: 40px 0; }
  .section-head { margin-bottom: 24px; }
  .hero-text h1 { font-size: 26px !important; margin-bottom: 12px; line-height: 1.2; }
  .hero-text p { font-size: 14px !important; margin-bottom: 24px; line-height: 1.5; }
  .hero-btns { gap: 12px; margin-bottom: 32px; }
  .hero-stats { gap: 24px; margin-bottom: 20px; }
  .btn-primary-lg, .btn-secondary-lg { width: 100%; white-space: normal; padding: 14px 24px; font-size: 15px; }
  
  .footer-grid { grid-template-columns: 1fr; }
  .footer-brand { grid-column: span 1; }
  .footer-bottom { flex-direction: column; gap: 20px; text-align: center; }
  
  .floating-card { padding: 8px 12px; font-size: 11px; }
}
</style>

