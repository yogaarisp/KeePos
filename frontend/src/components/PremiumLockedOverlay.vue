<template>
  <div class="premium-locked-overlay">
    <div class="locked-content">
      <div class="locked-icon-pulse">
        <div class="icon-orb">
          <Lock :size="32" />
        </div>
        <div class="pulse-ring"></div>
      </div>
      
      <h2 class="premium-title">Fitur Premium Terdeteksi</h2>
      <p class="premium-desc">
        Halaman <strong>{{ pageTitle }}</strong> memerlukan paket 
        <span class="plan-badge-inline" :class="requiredPlan">{{ requiredPlan.toUpperCase() }}</span>.
      </p>
      
      <div class="benefits-mini">
        <div class="benefit-item">
          <CheckCircle :size="14" />
          <span>Laporan Inventori Realtime</span>
        </div>
        <div class="benefit-item">
          <CheckCircle :size="14" />
          <span>Manajemen Stok Lanjutan</span>
        </div>
        <div class="benefit-item">
          <CheckCircle :size="14" />
          <span>Sinkronisasi Google Sheets</span>
        </div>
      </div>

      <div class="action-buttons">
        <router-link to="/app/billing" class="btn-upgrade-premium">
          <Zap :size="18" fill="currentColor" />
          <span>Upgrade Sekarang</span>
        </router-link>
        <button @click="$router.back()" class="btn-back-soft">
          Kembali
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Lock, Zap, CheckCircle } from 'lucide-vue-next';

defineProps({
  pageTitle: {
    type: String,
    default: 'Fitur'
  },
  requiredPlan: {
    type: String,
    default: 'basic'
  }
});
</script>

<style scoped>
.premium-locked-overlay {
  position: fixed;
  inset: 0;
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
  pointer-events: auto;
}

@media (min-width: 1201px) {
  .premium-locked-overlay {
    margin-left: 260px; /* Sidebar width */
    margin-top: 80px;   /* Header height */
  }
}

@media (max-width: 1200px) {
  .premium-locked-overlay {
    margin-top: 70px;   /* Mobile/Tablet header height */
  }
}

@media (max-width: 768px) {
  .premium-locked-overlay {
    margin-bottom: 70px; /* Mobile bottom nav height */
    margin-top: 64px;    /* Mobile header height */
  }
}

.locked-content {
  background: rgba(255, 255, 255, 0.9);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(249, 115, 22, 0.2);
  border-radius: 32px;
  padding: 48px 32px;
  max-width: 440px;
  width: 100%;
  text-align: center;
  box-shadow: 0 25px 50px -12px rgba(249, 115, 22, 0.2);
  animation: scaleUp 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.dark .locked-content {
  background: rgba(30, 41, 59, 0.85);
  border-color: rgba(249, 115, 22, 0.3);
}

.locked-icon-pulse {
  position: relative;
  width: 80px;
  height: 80px;
  margin: 0 auto 24px;
}

.icon-orb {
  position: relative;
  z-index: 2;
  width: 100%;
  height: 100%;
  background: linear-gradient(135deg, #f97316, #ea580c);
  border-radius: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  box-shadow: 0 10px 20px -5px rgba(234, 88, 12, 0.5);
}

.pulse-ring {
  position: absolute;
  inset: -8px;
  border-radius: 30px;
  border: 2px solid #f97316;
  opacity: 0;
  animation: pulseRotate 2s infinite;
}

@keyframes pulseRotate {
  0% { transform: scale(0.9); opacity: 0.8; }
  100% { transform: scale(1.2); opacity: 0; }
}

.premium-title {
  font-size: 24px;
  font-weight: 800;
  color: #1e293b;
  margin-bottom: 12px;
  letter-spacing: -0.5px;
}

.dark .premium-title { color: #f8fafc; }

.premium-desc {
  font-size: 15px;
  color: #64748b;
  line-height: 1.6;
  margin-bottom: 24px;
}

.plan-badge-inline {
  padding: 2px 8px;
  border-radius: 6px;
  font-weight: 800;
  font-size: 12px;
}

.plan-badge-inline.basic { background: rgba(37, 99, 235, 0.1); color: #2563eb; }
.plan-badge-inline.pro { background: rgba(249, 115, 22, 0.1); color: #f97316; }

.benefits-mini {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-bottom: 32px;
  background: rgba(241, 245, 249, 0.5);
  border-radius: 16px;
  padding: 16px;
}

.dark .benefits-mini { background: rgba(15, 23, 42, 0.4); }

.benefit-item {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 13px;
  font-weight: 600;
  color: #475569;
}

.dark .benefit-item { color: #94a3b8; }

.benefit-item svg { color: #22c55e; }

.action-buttons {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.btn-upgrade-premium {
  height: 54px;
  background: linear-gradient(135deg, #f97316, #ea580c);
  color: white;
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  font-weight: 800;
  text-decoration: none;
  transition: all 0.3s;
  box-shadow: 0 10px 20px -5px rgba(234, 88, 12, 0.4);
}

.btn-upgrade-premium:hover {
  transform: translateY(-2px);
  box-shadow: 0 15px 25px -5px rgba(234, 88, 12, 0.5);
}

.btn-back-soft {
  height: 48px;
  background: transparent;
  color: #64748b;
  border: none;
  font-weight: 700;
  cursor: pointer;
  transition: 0.2s;
}

.btn-back-soft:hover { color: #1e293b; }

@keyframes scaleUp {
  from { opacity: 0; transform: scale(0.9); }
  to { opacity: 1; transform: scale(1); }
}
</style>
