<template>
  <div class="settings-page">
    <!-- Header Hero Section -->
    <div class="page-hero">
      <div class="hero-content">
        <div class="hero-icon-wrap">
          <Settings2 :size="24" />
        </div>
        <div>
          <h1 class="hero-title">SaaS Konfigurasi</h1>
          <p class="hero-subtitle">Kelola identitas platform, payment gateway, dan harga paket langganan SaaS.</p>
        </div>
      </div>
    </div>

    <div class="settings-container">
      <!-- Tab Navigation -->
      <nav class="settings-tabs-nav">
        <div class="tabs-scroll-container">
          <button 
            v-for="t in tabs" 
            :key="t.id"
            :class="['tab-pill', { active: activeTab === t.id }]"
            @click="activeTab = t.id"
          >
            <component :is="t.icon" :size="16" />
            <span>{{ t.label }}</span>
          </button>
        </div>
      </nav>

      <!-- Main Content Area -->
      <main class="settings-content">
        <Transition name="fade-fast" mode="out-in">
          <!-- Platform Identity -->
          <section v-if="activeTab === 'identity'" key="identity" class="settings-section identity-redesign">
            <header class="section-header">
              <h1 class="section-title">Identitas Platform</h1>
              <p class="section-subtitle">Atur branding utama yang muncul di landing page dan sistem pendukung.</p>
            </header>

            <div class="section-body">
              <div class="config-card-group">
                <!-- Branding Context Card -->
                <div class="premium-config-card">
                  <div class="card-header-icon">
                    <Globe :size="20" />
                  </div>
                  <div class="card-main-content">
                    <div class="field-group w-full">
                      <label class="field-label">Nama Aplikasi SaaS</label>
                      <input type="text" v-model="form.app_name" class="modern-input" placeholder="Contoh: Kee POS">
                      <p class="input-hint-premium">Nama ini akan menjadi Title utama pada seluruh ekosistem SaaS Anda.</p>
                    </div>
                    <div class="field-group w-full mt-4">
                      <label class="field-label">Nomor WhatsApp Admin (Hubungi Kami)</label>
                      <input type="text" v-model="form.app_whatsapp" class="modern-input" placeholder="Contoh: 628123456789">
                      <p class="input-hint-premium">Gunakan format internasional tanpa tanda '+'. Contoh: 628123456789</p>
                    </div>
                  </div>
                </div>

                <!-- Media Upload Group -->
                <div class="upload-premium-container">
                  <div class="upload-card">
                    <div class="upload-label-group">
                      <label class="field-label">Logo Utama</label>
                      <p>Format horizontal / persegi (PNG/JPG)</p>
                    </div>
                    <div class="premium-upload-box" @click="$refs.logoInput.click()">
                      <div class="upload-overlay">
                        <Camera :size="20" />
                        <span>Ganti Logo</span>
                      </div>
                      <div class="preview-area" v-if="logoPreview || form.app_logo_url">
                        <img :src="logoPreview || baseUrl + '/storage/' + form.app_logo_url" alt="Logo">
                      </div>
                      <div v-else class="placeholder-area">
                        <Camera :size="32" />
                        <span>Upload Logo</span>
                      </div>
                      <input type="file" ref="logoInput" @change="e => handleFile(e, 'logo')" accept="image/*" hidden>
                    </div>
                  </div>

                  <div class="upload-card favicon-card">
                    <div class="upload-label-group">
                      <label class="field-label">Favicon</label>
                      <p>Icon kecil untuk browser tab</p>
                    </div>
                    <div class="premium-upload-box favicon" @click="$refs.faviconInput.click()">
                      <div class="upload-overlay">
                        <Edit3 :size="16" />
                      </div>
                      <div class="preview-area" v-if="faviconPreview || form.app_favicon_url">
                        <img :src="faviconPreview || baseUrl + '/storage/' + form.app_favicon_url" alt="Favicon">
                      </div>
                      <div v-else class="placeholder-area">
                        <Image :size="24" />
                      </div>
                      <input type="file" ref="faviconInput" @change="e => handleFile(e, 'favicon')" accept="image/*,image/x-icon" hidden>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <footer class="section-footer-premium">
              <button class="btn-premium-save" @click="saveConfigs" :disabled="loading">
                <div class="btn-shine"></div>
                <Save :size="18" v-if="!loading" />
                <RefreshCw :size="18" class="spin" v-else />
                <span>Simpan Branding</span>
              </button>
            </footer>
          </section>

          <!-- Payment Gateway -->
          <section v-else-if="activeTab === 'payment'" key="payment" class="settings-section payment-redesign">
            <header class="section-header">
              <h1 class="section-title">Payment Gateway (Midtrans)</h1>
              <p class="section-subtitle">Otomasi pembayaran paket langganan menggunakan infrastruktur Midtrans.</p>
            </header>

            <div class="section-body">
              <div class="gateway-config-card">
                <div class="gateway-header">
                  <div class="midtrans-logo-mock">MIDTRANS</div>
                  <div class="connection-status">
                    <span class="status-dot"></span>
                    API Connected
                  </div>
                </div>

                <div class="form-grid-premium">
                  <div class="input-premium-group">
                    <label>Server Key</label>
                    <div class="input-conceal">
                      <Lock :size="16" />
                      <input type="password" v-model="form.midtrans_server_key" placeholder="SB-Mid-server-...">
                    </div>
                  </div>

                  <div class="input-premium-group">
                    <label>Client Key</label>
                    <div class="input-conceal">
                      <ShieldCheck :size="16" />
                      <input type="password" v-model="form.midtrans_client_key" placeholder="SB-Mid-client-...">
                    </div>
                  </div>

                  <div class="input-premium-group full-width">
                    <label>Environment</label>
                    <div class="env-toggle">
                      <button 
                        class="env-btn" 
                        :class="{ active: !form.midtrans_is_production }" 
                        @click="form.midtrans_is_production = false"
                      >Sandbox</button>
                      <button 
                        class="env-btn production" 
                        :class="{ active: form.midtrans_is_production }" 
                        @click="form.midtrans_is_production = true"
                      >Production</button>
                    </div>
                  </div>
                </div>

                <div class="webhook-url-preview">
                  <label>Notification URL (Daftarkan di Dashboard Midtrans)</label>
                  <div class="url-copy-box">
                    <code>{{ baseUrl }}/api/subscriptions/webhook</code>
                  </div>
                </div>
              </div>

              <div class="security-warning-card mt-6">
                <div class="warning-icon">
                  <ShieldCheck :size="20" />
                </div>
                <div class="warning-text">
                  <h4>Data Terenkripsi</h4>
                  <p>Secret key Anda disimpan dengan enkripsi standar industri. Jangan pernah memindahkan key ini ke lingkungan yang tidak aman.</p>
                </div>
              </div>
            </div>

            <footer class="section-footer-premium">
              <button class="btn-premium-save" @click="saveConfigs" :disabled="loading">
                <div class="btn-shine"></div>
                <Save :size="18" v-if="!loading" />
                <RefreshCw :size="18" class="spin" v-else />
                <span>Simpan Kunci API</span>
              </button>
            </footer>
          </section>

          <!-- Email SMTP Config -->
          <div v-else-if="activeTab === 'email'" key="email" class="email-settings-wrapper">
            <!-- 1. SMTP Main Configuration Section -->
            <section class="settings-section">
              <header class="section-header">
                <h1 class="section-title">SMTP Configuration</h1>
              </header>

              <div class="section-body">
                <!-- Info Box: Common Settings -->
                <div class="common-smtp-info">
                  <div class="info-header">
                    <Info :size="14" />
                    <span>Common SMTP Settings</span>
                  </div>
                  <div class="info-grid">
                    <div class="info-col">
                      <strong>Gmail:</strong>
                      <ul>
                        <li>Host: <code>smtp.gmail.com</code></li>
                        <li>Port: <code>587</code> (TLS) or <code>465</code> (SSL)</li>
                        <li>Encryption: <code>TLS</code> or <code>SSL</code></li>
                        <li>Use App Password, not regular password</li>
                      </ul>
                    </div>
                    <div class="info-col">
                      <strong>Outlook/Hotmail:</strong>
                      <ul>
                        <li>Host: <code>smtp-mail.outlook.com</code></li>
                        <li>Port: <code>587</code></li>
                        <li>Encryption: <code>TLS</code></li>
                      </ul>
                    </div>
                  </div>
                </div>

                <div class="form-grid-image-style">
                  <div class="field-group-row">
                    <div class="field-group">
                      <label class="field-label-small">SMTP Host</label>
                      <input type="text" v-model="form.smtp_host" class="modern-input-small" placeholder="smtp.gmail.com">
                    </div>
                    <div class="field-group" style="flex: 0 0 120px;">
                      <label class="field-label-small">Port</label>
                      <input type="text" v-model="form.smtp_port" class="modern-input-small" placeholder="587">
                    </div>
                    <div class="field-group">
                      <label class="field-label-small">Encryption</label>
                      <select v-model="form.smtp_encryption" class="modern-input-small select-input">
                        <option value="tls">TLS</option>
                        <option value="ssl">SSL</option>
                        <option value="none">None</option>
                      </select>
                    </div>
                  </div>

                  <div class="field-group-row">
                    <div class="field-group">
                      <label class="field-label-small">Username/Email</label>
                      <input type="text" v-model="form.smtp_username" class="modern-input-small" placeholder="email@example.com">
                    </div>
                    <div class="field-group">
                      <label class="field-label-small">Password/App Password</label>
                      <div class="input-with-icon">
                        <input :type="showPassword ? 'text' : 'password'" v-model="form.smtp_password" class="modern-input-small" placeholder="••••••••">
                        <button class="icon-toggle" @click="showPassword = !showPassword">
                          <Eye v-if="!showPassword" :size="16" />
                          <EyeOff v-else :size="16" />
                        </button>
                      </div>
                      <p class="field-hint-small">For Gmail, use App Password instead of regular password</p>
                    </div>
                  </div>

                  <div class="field-group-row">
                    <div class="field-group">
                      <label class="field-label-small">From Email</label>
                      <input type="email" v-model="form.smtp_from_address" class="modern-input-small" placeholder="noreply@domain.com">
                    </div>
                    <div class="field-group">
                      <label class="field-label-small">From Name</label>
                      <input type="text" v-model="form.smtp_from_name" class="modern-input-small" placeholder="Brand Name">
                    </div>
                  </div>
                </div>
              </div>

              <footer class="section-footer-premium">
                <button class="btn-premium-save" @click="saveConfigs" :disabled="loading">
                  <div class="btn-shine"></div>
                  <Save :size="18" v-if="!loading" />
                  <RefreshCw :size="18" class="spin" v-else />
                  <span>Simpan Konfigurasi SMTP</span>
                </button>
              </footer>
            </section>

            <!-- 2. Test Email Configuration Section -->
            <section class="settings-section mt-6">
              <header class="section-header">
                <div class="title-with-icon">
                  <Send :size="18" />
                  <h1 class="section-title">Test Email Configuration</h1>
                </div>
              </header>

              <div class="section-body">
                <div class="test-email-alert">
                  <Info :size="16" />
                  <span>Send a test email to verify your SMTP configuration is working correctly.</span>
                </div>

                <div class="test-email-form">
                  <div class="field-group w-full">
                    <label class="field-label-small">Test Email Address</label>
                    <div class="input-group-test">
                      <input type="email" v-model="testEmailAddress" class="modern-input-small flex-1" placeholder="test@example.com">
                      <button class="btn-test-email-solid" @click="testSMTP" :disabled="loading || testingSmtp">
                        <Send :size="16" v-if="!testingSmtp" />
                        <RefreshCw :size="16" class="spin" v-else />
                        Send Test Email
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </section>
          </div>

          <!-- Billing Plans -->
          <section v-else-if="activeTab === 'plans'" key="plans" class="settings-section plans-redesign">
            <header class="section-header">
              <div class="header-with-badge">
                <h1 class="section-title">Harga Paket Billing</h1>
                <span class="badge-premium">Live Update</span>
              </div>
              <p class="section-subtitle">Tentukan harga langganan bulanan. Perubahan akan langsung berdampak pada invoice baru.</p>
            </header>

            <div class="section-body">
              <!-- Trial & Global Settings -->
              <div class="global-plans-settings mb-8">
                <div class="setting-card-mini">
                  <div class="s-info">
                    <label>Masa Trial Default</label>
                    <p>Jumlah hari paket FREE dapat digunakan setelah registrasi.</p>
                  </div>
                  <div class="s-input">
                    <input type="number" v-model="form.default_trial_days" min="1" max="365">
                    <span class="unit">Hari</span>
                  </div>
                </div>
              </div>

              <!-- Pricing Config Grid -->
              <div class="plans-grid-premium">
                <!-- Basic Plan Card -->
                <div class="plan-configure-card basic-theme">
                  <div class="card-bg-glow"></div>
                  <div class="card-content">
                    <div class="plan-header">
                      <div class="plan-icon">
                        <Layers :size="20" />
                      </div>
                      <div class="plan-info">
                        <h3>Paket BASIC</h3>
                        <p>Fitur Dasar & Menengah</p>
                      </div>
                    </div>
                    
                    <div class="price-input-wrapper">
                      <label>Harga Langganan (Bulanan)</label>
                      <div class="fancy-input-group">
                        <span class="currency">Rp</span>
                        <input type="number" v-model="form.plan_basic_price" placeholder="0">
                        <span class="period">/ akun</span>
                      </div>
                    </div>

                    <div class="current-badge">
                      <div class="pulse-dot"></div>
                      <span>Harga Aktif: Rp {{ formatNumber(form.plan_basic_price) }}</span>
                    </div>
                  </div>
                </div>

                <!-- Pro Plan Card -->
                <div class="plan-configure-card pro-theme">
                  <div class="card-bg-glow"></div>
                  <div class="card-content">
                    <div class="plan-header">
                      <div class="plan-icon">
                        <Zap :size="20" />
                      </div>
                      <div class="plan-info">
                        <h3>Paket PRO</h3>
                        <p>Fitur Lengkap (Enterprise)</p>
                      </div>
                    </div>
                    
                    <div class="price-input-wrapper">
                      <label>Harga Langganan (Bulanan)</label>
                      <div class="fancy-input-group">
                        <span class="currency">Rp</span>
                        <input type="number" v-model="form.plan_pro_price" placeholder="0">
                        <span class="period">/ akun</span>
                      </div>
                    </div>

                    <div class="current-badge">
                      <div class="pulse-dot"></div>
                      <span>Harga Aktif: Rp {{ formatNumber(form.plan_pro_price) }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Feature Management -->
              <div class="features-config-grid mt-10">
                <!-- Free Features -->
                <div class="feature-manager-card">
                  <header>
                    <div class="f-icon-wrap"><Package :size="18" /></div>
                    <h3>Fitur Paket FREE</h3>
                  </header>
                  <div class="f-list-editor">
                    <div v-for="(feature, idx) in form.plan_free_features" :key="idx" class="f-input-row">
                      <input type="text" v-model="form.plan_free_features[idx]" placeholder="Contoh: 1 Akun Owner">
                      <button @click="removeFeature('free', idx)" class="btn-remove-f"><Trash2 :size="14" /></button>
                    </div>
                    <button @click="addFeature('free')" class="btn-add-f">
                      <Plus :size="14" /> Tambah Fitur
                    </button>
                  </div>
                </div>

                <!-- Basic Features -->
                <div class="feature-manager-card">
                  <header>
                    <div class="f-icon-wrap"><Layers :size="18" /></div>
                    <h3>Fitur Paket BASIC</h3>
                  </header>
                  <div class="f-list-editor">
                    <div v-for="(feature, idx) in form.plan_basic_features" :key="idx" class="f-input-row">
                      <input type="text" v-model="form.plan_basic_features[idx]" placeholder="Contoh: Stok Gudang">
                      <button @click="removeFeature('basic', idx)" class="btn-remove-f"><Trash2 :size="14" /></button>
                    </div>
                    <button @click="addFeature('basic')" class="btn-add-f">
                      <Plus :size="14" /> Tambah Fitur
                    </button>
                  </div>
                </div>

                <!-- Pro Features -->
                <div class="feature-manager-card">
                  <header>
                    <div class="f-icon-wrap"><Zap :size="18" /></div>
                    <h3>Fitur Paket PRO</h3>
                  </header>
                  <div class="f-list-editor">
                    <div v-for="(feature, idx) in form.plan_pro_features" :key="idx" class="f-input-row">
                      <input type="text" v-model="form.plan_pro_features[idx]" placeholder="Contoh: Support Prioritas">
                      <button @click="removeFeature('pro', idx)" class="btn-remove-f"><Trash2 :size="14" /></button>
                    </div>
                    <button @click="addFeature('pro')" class="btn-add-f">
                      <Plus :size="14" /> Tambah Fitur
                    </button>
                  </div>
                </div>
              </div>

              <div class="alert-premium mt-8">
                <div class="alert-icon">
                  <Info :size="18" />
                </div>
                <div class="alert-text">
                  <strong>Catatan:</strong> Harga dan list fitur yang Anda simpan di sini akan muncul di Landing Page dan halaman Billing tenant.
                </div>
              </div>
            </div>

            <footer class="section-footer-premium">
              <button class="btn-premium-save" @click="saveConfigs" :disabled="loading">
                <div class="btn-shine"></div>
                <Save :size="18" v-if="!loading" />
                <RefreshCw :size="18" class="spin" v-else />
                <span>{{ loading ? 'Menyimpan...' : 'Simpan Perubahan SaaS' }}</span>
              </button>
            </footer>
          </section>

          <!-- Maintenance / Backup -->
          <section v-else-if="activeTab === 'maintenance'" key="maintenance" class="settings-section maintenance-redesign">
            <header class="section-header">
              <div class="header-with-badge">
                <h1 class="section-title">Maintenance & Backup</h1>
                <span class="badge-maintenance">Admin Only</span>
              </div>
              <p class="section-subtitle">Kelola backup, restore, system info, dan optimasi platform Anda.</p>
            </header>

            <div class="section-body">
              <!-- System Info Card -->
              <div class="maintenance-card mb-6" v-if="sysInfo">
                <div class="maintenance-card-header sysinfo-header">
                  <div class="maintenance-icon-wrap sysinfo-icon">
                    <Monitor :size="24" />
                  </div>
                  <div class="maintenance-info">
                    <h3>Informasi Sistem</h3>
                    <p>Ringkasan teknis environment server dan statistik platform.</p>
                  </div>
                  <button class="btn-refresh-sysinfo" @click="fetchSystemInfo" :disabled="loadingSysInfo">
                    <RefreshCw :size="14" :class="{ spin: loadingSysInfo }" />
                  </button>
                </div>

                <div class="sysinfo-grid">
                  <div class="sysinfo-group">
                    <h4><Server :size="14" /> Server</h4>
                    <div class="sysinfo-items">
                      <div class="sysinfo-item">
                        <span class="si-label">PHP</span>
                        <span class="si-value">{{ sysInfo.php_version }}</span>
                      </div>
                      <div class="sysinfo-item">
                        <span class="si-label">Laravel</span>
                        <span class="si-value">v{{ sysInfo.laravel_version }}</span>
                      </div>
                      <div class="sysinfo-item">
                        <span class="si-label">OS</span>
                        <span class="si-value">{{ sysInfo.server_os }}</span>
                      </div>
                      <div class="sysinfo-item">
                        <span class="si-label">Environment</span>
                        <span class="si-value si-env" :class="sysInfo.app_env">{{ sysInfo.app_env }}</span>
                      </div>
                    </div>
                  </div>
                  <div class="sysinfo-group">
                    <h4><Database :size="14" /> Database</h4>
                    <div class="sysinfo-items">
                      <div class="sysinfo-item">
                        <span class="si-label">Nama DB</span>
                        <span class="si-value mono">{{ sysInfo.db_name }}</span>
                      </div>
                      <div class="sysinfo-item">
                        <span class="si-label">Ukuran DB</span>
                        <span class="si-value highlight">{{ sysInfo.db_size_formatted }}</span>
                      </div>
                      <div class="sysinfo-item">
                        <span class="si-label">Jumlah Tabel</span>
                        <span class="si-value">{{ sysInfo.table_count }}</span>
                      </div>
                      <div class="sysinfo-item">
                        <span class="si-label">Storage</span>
                        <span class="si-value">{{ sysInfo.storage_size_formatted }}</span>
                      </div>
                    </div>
                  </div>
                  <div class="sysinfo-group">
                    <h4><Users :size="14" /> Data Platform</h4>
                    <div class="sysinfo-items">
                      <div class="sysinfo-item">
                        <span class="si-label">Total Tenant</span>
                        <span class="si-value highlight">{{ sysInfo.total_tenants }}</span>
                      </div>
                      <div class="sysinfo-item">
                        <span class="si-label">Total User</span>
                        <span class="si-value">{{ sysInfo.total_users }}</span>
                      </div>
                      <div class="sysinfo-item">
                        <span class="si-label">Total Produk</span>
                        <span class="si-value">{{ sysInfo.total_products }}</span>
                      </div>
                      <div class="sysinfo-item">
                        <span class="si-label">Total Order</span>
                        <span class="si-value">{{ sysInfo.total_orders }}</span>
                      </div>
                    </div>
                  </div>
                  <div class="sysinfo-group">
                    <h4><Settings2 :size="14" /> Drivers</h4>
                    <div class="sysinfo-items">
                      <div class="sysinfo-item">
                        <span class="si-label">Cache</span>
                        <span class="si-value mono">{{ sysInfo.cache_driver }}</span>
                      </div>
                      <div class="sysinfo-item">
                        <span class="si-label">Session</span>
                        <span class="si-value mono">{{ sysInfo.session_driver }}</span>
                      </div>
                      <div class="sysinfo-item">
                        <span class="si-label">Queue</span>
                        <span class="si-value mono">{{ sysInfo.queue_driver }}</span>
                      </div>
                      <div class="sysinfo-item">
                        <span class="si-label">Memory Limit</span>
                        <span class="si-value">{{ sysInfo.memory_limit }}</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Loading state for system info -->
              <div class="maintenance-card mb-6 loading-sysinfo" v-else>
                <div class="maintenance-card-header sysinfo-header">
                  <div class="maintenance-icon-wrap sysinfo-icon">
                    <Monitor :size="24" />
                  </div>
                  <div class="maintenance-info">
                    <h3>Informasi Sistem</h3>
                    <p>Memuat data sistem...</p>
                  </div>
                </div>
                <div class="sysinfo-loading">
                  <RefreshCw :size="20" class="spin" />
                  <span>Mengambil data server...</span>
                </div>
              </div>

              <!-- Backup & Restore Row -->
              <div class="maintenance-grid mb-6">
                <!-- Database Backup Card -->
                <div class="maintenance-card">
                  <div class="maintenance-card-header">
                    <div class="maintenance-icon-wrap">
                      <Download :size="24" />
                    </div>
                    <div class="maintenance-info">
                      <h3>Backup Database</h3>
                      <p>Unduh seluruh data database dalam format SQL.</p>
                    </div>
                  </div>

                  <div class="backup-details">
                    <div class="detail-row">
                      <div class="detail-item">
                        <span class="detail-label">Format</span>
                        <span class="detail-value">.sql</span>
                      </div>
                      <div class="detail-item">
                        <span class="detail-label">Includes</span>
                        <span class="detail-value">Full Dump + Triggers</span>
                      </div>
                    </div>
                  </div>

                  <div class="backup-actions">
                    <button class="btn-backup" @click="backupDatabase" :disabled="backingUp">
                      <div class="btn-shine"></div>
                      <Download :size="18" v-if="!backingUp" />
                      <RefreshCw :size="18" class="spin" v-else />
                      <span>{{ backingUp ? 'Memproses...' : 'Download Backup' }}</span>
                    </button>
                  </div>
                </div>

                <!-- Restore Database Card -->
                <div class="maintenance-card">
                  <div class="maintenance-card-header restore-header">
                    <div class="maintenance-icon-wrap restore-icon">
                      <Upload :size="24" />
                    </div>
                    <div class="maintenance-info">
                      <h3>Restore Database</h3>
                      <p>Upload file .sql backup untuk menimpa database saat ini.</p>
                    </div>
                  </div>

                  <div class="backup-details">
                    <div class="detail-row">
                      <div class="detail-item">
                        <span class="detail-label">Format</span>
                        <span class="detail-value">.sql only</span>
                      </div>
                      <div class="detail-item">
                        <span class="detail-label">Max Size</span>
                        <span class="detail-value">100 MB</span>
                      </div>
                    </div>
                  </div>

                  <div class="backup-actions">
                    <button class="btn-restore" @click="$refs.restoreInput.click()" :disabled="restoring">
                      <Upload :size="18" v-if="!restoring" />
                      <RefreshCw :size="18" class="spin" v-else />
                      <span>{{ restoring ? 'Restoring...' : 'Pilih File & Restore' }}</span>
                    </button>
                    <input type="file" ref="restoreInput" @change="handleRestore" accept=".sql" hidden>
                  </div>
                </div>
              </div>

              <!-- Quick Actions -->
              <div class="maintenance-card mb-6">
                <div class="maintenance-card-header actions-header">
                  <div class="maintenance-icon-wrap actions-icon">
                    <Zap :size="24" />
                  </div>
                  <div class="maintenance-info">
                    <h3>Quick Actions</h3>
                    <p>Perintah cepat untuk maintenance server tanpa akses SSH.</p>
                  </div>
                </div>

                <div class="quick-actions-grid">
                  <div class="quick-action-card" @click="clearCacheAction" :class="{ disabled: clearingCache }">
                    <div class="qa-icon trash-icon">
                      <Trash2 :size="20" />
                    </div>
                    <div class="qa-info">
                      <h4>Clear Cache</h4>
                      <p>Bersihkan cache config, route, view, dan application.</p>
                    </div>
                    <div class="qa-status" v-if="clearingCache">
                      <RefreshCw :size="16" class="spin" />
                    </div>
                    <div class="qa-arrow" v-else>
                      <ChevronRight :size="18" />
                    </div>
                  </div>

                  <div class="quick-action-card" @click="optimizeAction" :class="{ disabled: optimizing }">
                    <div class="qa-icon optimize-icon">
                      <Rocket :size="20" />
                    </div>
                    <div class="qa-info">
                      <h4>Optimize App</h4>
                      <p>Cache konfigurasi, route, dan view untuk performa maksimal.</p>
                    </div>
                    <div class="qa-status" v-if="optimizing">
                      <RefreshCw :size="16" class="spin" />
                    </div>
                    <div class="qa-arrow" v-else>
                      <ChevronRight :size="18" />
                    </div>
                  </div>
                </div>
              </div>

              <!-- Warning -->
              <div class="alert-premium">
                <div class="alert-icon">
                  <Info :size="18" />
                </div>
                <div class="alert-text">
                  <strong>Catatan Penting:</strong> Restore database akan <strong>menimpa seluruh data</strong> yang ada. Pastikan Anda sudah membuat backup terlebih dahulu. File backup berisi data sensitif — simpan di tempat yang aman.
                </div>
              </div>
            </div>
          </section>
        </Transition>
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, reactive, markRaw } from 'vue';
import api from '../../api';
import { apiUrl } from '../../api';
import { 
  Settings2, Camera, Image, Save, RefreshCw, 
  CreditCard, Info, ShieldCheck, Layers, Zap, Globe, Mail,
  Eye, EyeOff, Send, Database, Download, Wrench,
  Lock, Edit3, Monitor, Server, Users, Upload, Trash2,
  ChevronRight, Rocket, Package, Plus, Phone
} from 'lucide-vue-next';
import { showSuccess, showError } from '../../utils/swal';
import { baseUrl } from '../../api';

const loading = ref(false);
const testingSmtp = ref(false);
const backingUp = ref(false);
const restoring = ref(false);
const clearingCache = ref(false);
const optimizing = ref(false);
const loadingSysInfo = ref(false);
const sysInfo = ref(null);
const showPassword = ref(false);
const testEmailAddress = ref('');
const activeTab = ref('identity');
const logoPreview = ref(null);
const logoFile = ref(null);
const faviconPreview = ref(null);
const faviconFile = ref(null);

const tabs = [
  { id: 'identity', label: 'Branding', icon: markRaw(Globe) },
  { id: 'payment', label: 'Midtrans', icon: markRaw(CreditCard) },
  { id: 'email', label: 'Email SMTP', icon: markRaw(Mail) },
  { id: 'plans', label: 'Harga Paket', icon: markRaw(Layers) },
  { id: 'maintenance', label: 'Maintenance', icon: markRaw(Wrench) },
];

const form = reactive({
  app_name: 'Kee POS',
  app_whatsapp: '',
  app_logo_url: '',
  app_favicon_url: '',
  midtrans_server_key: '',
  midtrans_client_key: '',
  midtrans_is_production: false,
  plan_basic_price: 149000,
  plan_pro_price: 299000,
  plan_free_features: [],
  plan_basic_features: [],
  plan_pro_features: [],
  default_trial_days: 20,
  smtp_host: '',
  smtp_port: '587',
  smtp_username: '',
  smtp_password: '',
  smtp_encryption: 'tls',
  smtp_from_address: '',
  smtp_from_name: ''
});

const fetchData = async () => {
  try {
    const res = await api.get('/admin/saas/config');
    if (res.data.success) {
      form.app_name = res.data.data.app_name || 'Kee POS';
      form.app_whatsapp = res.data.data.app_whatsapp || '';
      form.app_logo_url = res.data.data.app_logo;
      form.app_favicon_url = res.data.data.app_favicon;
      form.midtrans_server_key = res.data.data.midtrans_server_key;
      form.midtrans_client_key = res.data.data.midtrans_client_key;
      form.midtrans_is_production = res.data.data.midtrans_is_production === '1' || res.data.data.midtrans_is_production === true;
      form.plan_basic_price = res.data.data.plan_basic_price;
      form.plan_pro_price = res.data.data.plan_pro_price;
      form.plan_free_features = res.data.data.plan_free_features || [];
      form.plan_basic_features = res.data.data.plan_basic_features || [];
      form.plan_pro_features = res.data.data.plan_pro_features || [];
      form.default_trial_days = res.data.data.default_trial_days || 20;
      
      // SMTP
      form.smtp_host = res.data.data.smtp_host || '';
      form.smtp_port = res.data.data.smtp_port || '587';
      form.smtp_username = res.data.data.smtp_username || '';
      form.smtp_password = res.data.data.smtp_password || '';
      form.smtp_encryption = res.data.data.smtp_encryption || 'tls';
      form.smtp_from_address = res.data.data.smtp_from_address || '';
      form.smtp_from_name = res.data.data.smtp_from_name || '';
    }
  } catch (err) {
    console.error('Failed to fetch SaaS configs', err);
  }
};

onMounted(() => {
  fetchData();
  fetchSystemInfo();
});

const handleFile = (e, type) => {
  const file = e.target.files[0];
  if (file) {
    if (type === 'logo') {
      logoFile.value = file;
      logoPreview.value = URL.createObjectURL(file);
    } else {
      faviconFile.value = file;
      faviconPreview.value = URL.createObjectURL(file);
    }
  }
};

const saveConfigs = async () => {
  loading.value = true;
  const formData = new FormData();
  formData.append('app_name', form.app_name);
  formData.append('app_whatsapp', form.app_whatsapp);
  formData.append('midtrans_server_key', form.midtrans_server_key || '');
  formData.append('midtrans_client_key', form.midtrans_client_key || '');
  formData.append('midtrans_is_production', form.midtrans_is_production ? '1' : '0');
  formData.append('plan_basic_price', form.plan_basic_price);
  formData.append('plan_pro_price', form.plan_pro_price);
  formData.append('plan_free_features', JSON.stringify(form.plan_free_features));
  formData.append('plan_basic_features', JSON.stringify(form.plan_basic_features));
  formData.append('plan_pro_features', JSON.stringify(form.plan_pro_features));
  formData.append('default_trial_days', form.default_trial_days);
  
  // SMTP
  formData.append('smtp_host', form.smtp_host || '');
  formData.append('smtp_port', form.smtp_port || '');
  formData.append('smtp_username', form.smtp_username || '');
  formData.append('smtp_password', form.smtp_password || '');
  formData.append('smtp_encryption', form.smtp_encryption || '');
  formData.append('smtp_from_address', form.smtp_from_address || '');
  formData.append('smtp_from_name', form.smtp_from_name || '');

  if (logoFile.value) formData.append('app_logo', logoFile.value);
  if (faviconFile.value) formData.append('app_favicon', faviconFile.value);

  try {
    const res = await api.post('/admin/saas/config', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });
    if (res.data.success) {
      showSuccess('Konfigurasi SaaS telah diperbarui.');
      setTimeout(() => {
        window.location.reload();
      }, 1000);
    }
  } catch (err) {
    showError('Terjadi kesalahan saat menyimpan.');
  } finally {
    loading.value = false;
  }
};

const addFeature = (plan) => {
  if (plan === 'free') form.plan_free_features.push('');
  else if (plan === 'basic') form.plan_basic_features.push('');
  else if (plan === 'pro') form.plan_pro_features.push('');
};

const removeFeature = (plan, index) => {
  if (plan === 'free') form.plan_free_features.splice(index, 1);
  else if (plan === 'basic') form.plan_basic_features.splice(index, 1);
  else if (plan === 'pro') form.plan_pro_features.splice(index, 1);
};

const testSMTP = async () => {
  if (!form.smtp_host || !form.smtp_username || !form.smtp_password || !form.smtp_from_address) {
    showError('Mohon lengkapi data SMTP sebelum mengetes.');
    return;
  }

  if (!testEmailAddress.value) {
    showError('Masukkan alamat email penerima untuk tes.');
    return;
  }

  testingSmtp.value = true;
  try {
    const res = await api.post('/admin/saas/test-smtp', {
      smtp_host: form.smtp_host,
      smtp_port: form.smtp_port,
      smtp_username: form.smtp_username,
      smtp_password: form.smtp_password,
      smtp_encryption: form.smtp_encryption,
      smtp_from_address: form.smtp_from_address,
      smtp_from_name: form.smtp_from_name,
      recipient: testEmailAddress.value
    });
    if (res.data.success) {
      showSuccess(res.data.message);
    }
  } catch (err) {
    const msg = err.response?.data?.message || 'Gagal mengetes koneksi SMTP.';
    showError(msg);
  } finally {
    testingSmtp.value = false;
  }
};

const formatNumber = (num) => {
  return new Intl.NumberFormat('id-ID').format(num);
};

const backupDatabase = async () => {
  backingUp.value = true;
  try {
    const token = localStorage.getItem('auth_token');
    const user = JSON.parse(localStorage.getItem('user') || 'null');
    
    const response = await fetch(apiUrl + '/admin/saas/db/backup', {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/sql',
        ...(user?.tenant ? { 'X-Tenant-Slug': user.tenant.slug } : {})
      }
    });

    if (!response.ok) {
      const errorData = await response.json();
      throw new Error(errorData.message || 'Backup gagal');
    }

    const blob = await response.blob();
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    
    // Better filename extraction
    let filename = `full-backup-${new Date().toISOString().slice(0,19).replace(/:/g, '-')}.sql`;
    const disposition = response.headers.get('content-disposition');
    if (disposition && disposition.indexOf('attachment') !== -1) {
      const filenameRegex = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/;
      const matches = filenameRegex.exec(disposition);
      if (matches != null && matches[1]) { 
        filename = matches[1].replace(/['"]/g, '');
      }
    }
    
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    a.remove();
    window.URL.revokeObjectURL(url);
    showSuccess('Backup database berhasil diunduh!');
  } catch (err) {
    showError(err.message || 'Gagal membuat backup database.');
  } finally {
    backingUp.value = false;
  }
};

const fetchSystemInfo = async () => {
  loadingSysInfo.value = true;
  try {
    const res = await api.get('/admin/saas/system-info');
    if (res.data.success) {
      sysInfo.value = res.data.data;
    }
  } catch (err) {
    console.error('Failed to fetch system info', err);
  } finally {
    loadingSysInfo.value = false;
  }
};

const handleRestore = async (e) => {
  const file = e.target.files[0];
  if (!file) return;

  // Confirm with SweetAlert
  const { default: Swal } = await import('sweetalert2');
  const result = await Swal.fire({
    title: 'Konfirmasi Restore Database',
    html: `<p style="margin:0">Anda akan menimpa <strong>seluruh database</strong> dengan file:</p><p style="margin:8px 0 0;font-family:monospace;color:#f97316">${file.name}</p><p style="margin:12px 0 0;color:#ef4444;font-size:13px">⚠️ Tindakan ini tidak dapat dibatalkan!</p>`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#6b7280',
    confirmButtonText: 'Ya, Restore Sekarang',
    cancelButtonText: 'Batal',
    reverseButtons: true
  });

  if (!result.isConfirmed) {
    e.target.value = '';
    return;
  }

  restoring.value = true;
  const formData = new FormData();
  formData.append('sql_file', file);

  try {
    const res = await api.post('/admin/saas/db/restore', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });
    if (res.data.success) {
      showSuccess(res.data.message);
      fetchSystemInfo(); // Refresh system info
    }
  } catch (err) {
    showError(err.response?.data?.message || 'Gagal restore database.');
  } finally {
    restoring.value = false;
    e.target.value = '';
  }
};

const clearCacheAction = async () => {
  if (clearingCache.value) return;
  clearingCache.value = true;
  try {
    const res = await api.post('/admin/saas/cache/clear');
    if (res.data.success) {
      showSuccess(res.data.message);
    }
  } catch (err) {
    showError(err.response?.data?.message || 'Gagal membersihkan cache.');
  } finally {
    clearingCache.value = false;
  }
};

const optimizeAction = async () => {
  if (optimizing.value) return;
  optimizing.value = true;
  try {
    const res = await api.post('/admin/saas/optimize');
    if (res.data.success) {
      showSuccess(res.data.message);
    }
  } catch (err) {
    showError(err.response?.data?.message || 'Gagal mengoptimasi aplikasi.');
  } finally {
    optimizing.value = false;
  }
};
</script>

<style scoped>
/* ── Layout Framework (Consistent with Settings.vue) ── */
.settings-page {
  padding: 24px;
  animation: fadeIn 0.4s ease;
}

.page-hero {
  display: flex; align-items: center; justify-content: space-between;
  background: linear-gradient(135deg, rgba(249,115,22,0.08) 0%, rgba(249,115,22,0.02) 100%);
  border: 1px solid rgba(249,115,22,0.1); border-radius: 20px;
  padding: 24px 32px; margin-bottom: 24px;
}
.hero-content { display: flex; align-items: center; gap: 18px; }
.hero-icon-wrap {
  width: 48px; height: 48px; border-radius: 14px;
  background: linear-gradient(135deg, var(--accent), #fb923c);
  display: flex; align-items: center; justify-content: center;
  color: #fff; box-shadow: 0 8px 16px rgba(249, 115, 22, 0.2);
}
.hero-title { font-size: 20px; font-weight: 800; color: var(--text-primary); margin: 0; }
.hero-subtitle { font-size: 14px; color: var(--text-secondary); margin: 4px 0 0; }

.settings-container { display: flex; flex-direction: column; gap: 24px; }

/* ── Tab Pill Navigation ── */
.settings-tabs-nav {
  background: var(--bg-card); border: 1px solid var(--border-color);
  padding: 8px; border-radius: 20px; overflow: hidden;
}
.tabs-scroll-container { display: flex; gap: 8px; overflow-x: auto; -ms-overflow-style: none; scrollbar-width: none; }
.tabs-scroll-container::-webkit-scrollbar { display: none; }
.tab-pill {
  display: flex; align-items: center; gap: 10px; padding: 10px 20px;
  border-radius: 14px; border: none; background: transparent;
  color: var(--text-secondary); font-size: 14px; font-weight: 600;
  cursor: pointer; transition: 0.2s; white-space: nowrap;
}
.tab-pill:hover { background: var(--bg-primary); color: var(--text-primary); }
.tab-pill.active { background: var(--accent); color: #fff; box-shadow: 0 4px 12px rgba(249,115,22,0.2); }

/* ── Content Section ── */
.settings-content { flex: 1; }
.settings-section {
  background: var(--bg-card); border: 1px solid var(--border-color);
  border-radius: 24px;
  display: flex; flex-direction: column;
}

.section-header { padding: 32px; border-bottom: 1px solid var(--border-color); }
.section-title { font-size: 18px; font-weight: 700; color: var(--text-primary); margin: 0; }
.section-subtitle { font-size: 13px; color: var(--text-muted); margin: 6px 0 0; }

.section-body { padding: 32px; flex: 1; }

.section-footer {
  padding: 24px 32px; background: var(--bg-primary);
  border-top: 1px solid var(--border-color);
  display: flex; justify-content: space-between; align-items: center;
}
.footer-info { display: flex; align-items: center; gap: 8px; font-size: 12px; color: var(--text-muted); }
.footer-actions { display: flex; align-items: center; gap: 12px; margin-left: auto; width: 100%; justify-content: flex-end; }

/* ── Forms ── */
.form-grid { display: flex; flex-direction: column; gap: 24px; }
.field-group .field-label { 
  display: block;
  font-size: 11px; 
  font-weight: 700; 
  color: var(--text-muted); 
  text-transform: uppercase; 
  letter-spacing: 0.5px;
  margin-bottom: 6px;
}
.modern-input {
  width: 100%; 
  padding: 12px 16px; 
  border-radius: 12px;
  background: var(--bg-primary); 
  border: 1px solid var(--border-color);
  color: var(--text-primary); 
  font-size: 13.5px; 
  font-weight: 500;
  outline: none;
  transition: all 0.2s;
}
.modern-input:focus {
  border-color: var(--accent);
  box-shadow: 0 0 0 4px var(--accent-light);
}

/* ── Upload Box ── */
.upload-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
.upload-box {
  height: 140px; border: 2px dashed var(--border-color); border-radius: 16px;
  display: flex; align-items: center; justify-content: center; cursor: pointer;
  transition: 0.2s; background: var(--bg-primary); overflow: hidden;
}
.upload-box:hover { border-color: var(--accent); background: rgba(249,115,22,0.02); }
.upload-preview img { width: 100%; height: 100%; object-fit: contain; }
.upload-placeholder { display: flex; flex-direction: column; align-items: center; gap: 8px; color: var(--text-muted); }
.upload-placeholder span { font-size: 12px; font-weight: 700; }
.upload-hint { font-size: 11px; color: var(--text-muted); margin-top: 4px; }

/* ── Utilities ── */
.divider-line { height: 1px; background: var(--border-color); }
.input-with-side { position: relative; display: flex; align-items: center; }
.side-text { position: absolute; left: 16px; font-weight: 700; color: var(--text-muted); }
.pl-12 { padding-left: 48px !important; }

.alert-box-info {
  display: flex; gap: 14px; padding: 16px 20px;
  background: rgba(14,165,233,0.05); border: 1px solid rgba(14,165,233,0.1);
  border-radius: 16px; color: #0369a1;
}
.alert-box-info p { margin: 4px 0 0; font-size: 13px; opacity: 0.8; }

.plans-preview-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.preview-card {
  padding: 24px; border-radius: 20px; border: 1.5px solid var(--border-color);
  display: flex; flex-direction: column; align-items: center; gap: 12px;
}
.preview-card.basic { color: #2563eb; background: rgba(37,99,235,0.03); }
.preview-card.pro { color: #f97316; background: rgba(249,115,22,0.03); }
.preview-card h4 { margin: 0; font-weight: 800; font-size: 16px; letter-spacing: 1px; }
.preview-card p { margin: 0; font-size: 13px; color: var(--text-muted); }

.btn-primary {
  height: 48px; padding: 0 24px; border-radius: 12px;
  background: var(--accent); color: #fff; font-weight: 700; font-size: 14px;
  display: flex; align-items: center; gap: 8px; border: none; cursor: pointer;
  transition: 0.2s;
}
.btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 16px rgba(249,115,22,0.3); }
.btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }

.btn-outline-accent {
  height: 48px; padding: 0 24px; border-radius: 12px;
  background: transparent; color: var(--accent); font-weight: 700; font-size: 14px;
  display: flex; align-items: center; gap: 8px; border: 1.5px solid var(--accent); cursor: pointer;
  transition: 0.2s;
}
.btn-outline-accent:hover { background: rgba(249,115,22,0.05); transform: translateY(-2px); }
.btn-outline-accent:disabled { opacity: 0.6; cursor: not-allowed; }

.spin { animation: spin 1s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

.fade-fast-enter-active, .fade-fast-leave-active { transition: opacity 0.15s ease, transform 0.15s ease; }
.fade-fast-enter-from, .fade-fast-leave-to { opacity: 0; transform: translateY(4px); }

@media (max-width: 900px) {
  .settings-page { padding: 16px; }
  .page-hero { 
    flex-direction: column; align-items: center; text-align: center; gap: 20px; 
    padding: 32px 24px; 
  }
  .hero-content { flex-direction: column; }
}

@media (max-width: 768px) {
  .settings-page { padding: 8px; }
  .hero-title { font-size: 18px; }
  .hero-subtitle { font-size: 13px; }
  
  .settings-container { gap: 16px; }
  
  .section-header { padding: 24px 20px; }
  .section-body { padding: 24px 20px; }
  .section-footer { 
    padding: 20px; 
    flex-direction: column-reverse; 
    gap: 16px; 
    align-items: stretch; 
    text-align: center;
  }
  .footer-info { justify-content: center; }
  
  .upload-grid, .plans-preview-grid { grid-template-columns: 1fr; gap: 24px; }
  .upload-box { height: 120px; max-width: 160px; margin: 0 auto; }
  .upload-item .field-label { text-align: center; }
  .upload-hint { text-align: center; }
  
  .btn-primary { width: 100%; justify-content: center; height: 50px; }
  .btn-outline-accent { width: 100%; justify-content: center; height: 50px; }
  
  .tab-pill { padding: 8px 16px; font-size: 13px; }
}

@media (max-width: 480px) {
  .settings-page { padding: 4px; }
  .hero-icon-wrap { width: 44px; height: 44px; }
  .hero-title { font-size: 16px; }
  
  .section-title { font-size: 16px; }
  .section-subtitle { font-size: 12px; }
  
  .modern-input { height: 50px; }
}

/* Email SMTP Premium Tweaks */
.settings-container .settings-section { 
  border: none !important; background: transparent !important; 
}
.settings-container .settings-section .section-header { padding: 4px 0 24px 0; border: none; }
.settings-container .settings-section .section-body { 
  background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 20px;
  padding: 32px;
}
.settings-container .settings-section footer { margin-top: 24px; }

/* ── Identity Redesign ── */
.identity-redesign { border: none !important; background: transparent !important; }
.identity-redesign .section-header { padding: 4px 0 32px 0; border: none; }
.identity-redesign .section-body { padding: 0; }

.config-card-group { display: flex; flex-direction: column; gap: 24px; }

.premium-config-card {
  display: flex; gap: 20px; padding: 28px; border-radius: 20px;
  background: var(--bg-card); border: 1px solid var(--border-color);
}
.card-header-icon {
  width: 44px; height: 44px; border-radius: 12px; background: var(--bg-primary);
  display: flex; align-items: center; justify-content: center; color: var(--accent);
  border: 1px solid var(--border-color); flex-shrink: 0;
}
.card-main-content { flex: 1; }

.upload-premium-container { display: grid; grid-template-columns: 2fr 1fr; gap: 24px; }
.upload-card {
  padding: 24px; border-radius: 20px; background: var(--bg-card);
  border: 1px solid var(--border-color); display: flex; flex-direction: column; gap: 16px;
}
.upload-label-group label { display: block; font-size: 14px; font-weight: 800; color: var(--text-primary); margin-bottom: 4px; }
.upload-label-group p { font-size: 12px; color: var(--text-muted); margin: 0; }

.premium-upload-box {
  position: relative; height: 160px; border-radius: 16px; background: var(--bg-primary);
  border: 2px dashed var(--border-color); cursor: pointer; overflow: hidden;
  transition: all 0.2s; display: flex; align-items: center; justify-content: center;
}
.premium-upload-box:hover { border-color: var(--accent); background: rgba(249,115,22,0.02); }

.upload-overlay {
  position: absolute; inset: 0; background: rgba(0,0,0,0.4); backdrop-filter: blur(4px);
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  gap: 8px; color: white; opacity: 0; transition: 0.3s; z-index: 2;
}
.premium-upload-box:hover .upload-overlay { opacity: 1; }
.upload-overlay span { font-size: 11px; font-weight: 700; text-transform: uppercase; }

.preview-area { width: 100%; height: 100%; padding: 20px; display: flex; align-items: center; justify-content: center; }
.preview-area img { max-width: 100%; max-height: 100%; object-fit: contain; }

.placeholder-area { display: flex; flex-direction: column; align-items: center; gap: 12px; color: var(--text-muted); }
.placeholder-area span { font-size: 13px; font-weight: 700; }

.favicon-card .premium-upload-box { height: 100px; width: 100px; margin: 0 auto; border-radius: 12px; }

/* ── Payment Redesign ── */
.payment-redesign { border: none !important; background: transparent !important; }
.payment-redesign .section-header { padding: 4px 0 32px 0; border: none; }
.payment-redesign .section-body { padding: 0; }

.gateway-config-card {
  background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 24px;
  overflow: hidden; display: flex; flex-direction: column;
}
.gateway-header {
  padding: 24px 32px; background: #4f46e5; color: white;
  display: flex; justify-content: space-between; align-items: center;
}
.midtrans-logo-mock { font-weight: 900; letter-spacing: 2px; font-size: 18px; }
.gateway-header { background: #0d9488 !important; }

/* Midtrans Environment Toggle */
.full-width { grid-column: 1 / -1; }
.env-toggle { display: flex; gap: 8px; }
.env-btn {
  flex: 1; padding: 10px 16px; border-radius: 10px; border: 1.5px solid var(--border-color);
  background: var(--bg-primary); font-weight: 700; font-size: 13px; cursor: pointer;
  transition: 0.2s; color: var(--text-secondary);
}
.env-btn:hover { border-color: var(--text-muted); }
.env-btn.active { border-color: #0d9488; background: rgba(13, 148, 136, 0.08); color: #0d9488; }
.env-btn.production.active { border-color: #ea580c; background: rgba(234, 88, 12, 0.08); color: #ea580c; }
.connection-status { display: flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 700; color: rgba(255,255,255,0.8); }
.status-dot { width: 8px; height: 8px; border-radius: 50%; background: #10b981; box-shadow: 0 0 8px #10b981; }

.form-grid-premium { padding: 32px; display: grid; grid-template-columns: 1fr 1fr; gap: 24px; border-bottom: 1px solid var(--border-color); }
.input-premium-group label { display: block; font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 10px; }
.input-conceal {
  position: relative; display: flex; align-items: center;
  background: var(--bg-primary); border: 1.5px solid var(--border-color); border-radius: 14px;
  padding: 0 16px; transition: 0.2s;
}
.input-conceal:focus-within { border-color: #4f46e5; background: var(--bg-card); }
.input-conceal input { flex: 1; height: 48px; border: none; background: transparent; color: var(--text-primary); font-family: monospace; outline: none; padding-left: 12px; }
.input-conceal svg { color: var(--text-muted); }

.webhook-url-preview { padding: 24px 32px; background: var(--bg-primary); }
.webhook-url-preview label { display: block; font-size: 11px; font-weight: 700; color: var(--text-muted); margin-bottom: 12px; }
.url-copy-box { background: var(--bg-card); border: 1px solid var(--border-color); padding: 12px 16px; border-radius: 8px; color: var(--text-primary); font-size: 13px; cursor: pointer; }

.security-warning-card {
  display: flex; gap: 16px; padding: 20px; border-radius: 16px;
  background: rgba(16, 185, 129, 0.02); border: 1px solid rgba(16, 185, 129, 0.1);
  color: #059669;
}
.warning-icon { flex-shrink: 0; }
.warning-text h4 { margin: 0 0 4px 0; font-size: 14px; font-weight: 700; }
.warning-text p { margin: 0; font-size: 13px; opacity: 0.8; }

/* ── Plans Redesign ── */
.plans-redesign { border: none !important; background: transparent !important; box-shadow: none !important; }
.plans-redesign .section-header { padding: 4px 0 32px 0; border: none; }
.plans-redesign .section-body { padding: 0; }
.plans-redesign .section-footer-premium { padding: 32px 0; border: none; background: transparent; display: flex; justify-content: flex-start; }

.header-with-badge { display: flex; align-items: center; gap: 12px; }
.badge-premium { 
  background: rgba(249,115,22,0.1); color: #f97316; 
  padding: 4px 10px; border-radius: 100px; font-size: 10px; font-weight: 800; text-transform: uppercase; 
}

.plans-grid-premium { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }

.plan-configure-card {
  position: relative; border-radius: 24px; background: var(--bg-card); 
  border: 1px solid var(--border-color); overflow: hidden; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.plan-configure-card:hover { transform: translateY(-4px); border-color: var(--accent); box-shadow: 0 20px 40px -12px rgba(0,0,0,0.1); }

.card-bg-glow {
  position: absolute; top: 0; right: 0; width: 120px; height: 120px;
  background: radial-gradient(circle, var(--accent-light) 0%, transparent 70%);
  filter: blur(40px); opacity: 0.3; z-index: 0; pointer-events: none;
}
.basic-theme .card-bg-glow { background: radial-gradient(circle, rgba(37,99,235,0.1) 0%, transparent 70%); }
.pro-theme .card-bg-glow { background: radial-gradient(circle, rgba(249,115,22,0.1) 0%, transparent 70%); }

.card-content { position: relative; z-index: 1; padding: 28px; display: flex; flex-direction: column; gap: 24px; }

.plan-header { display: flex; align-items: center; gap: 16px; }
.plan-icon { 
  width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center;
  background: var(--bg-primary); border: 1px solid var(--border-color);
}
.basic-theme .plan-icon { color: #2563eb; border-color: rgba(37,99,235,0.2); background: rgba(37,99,235,0.02); }
.pro-theme .plan-icon { color: #f97316; border-color: rgba(249,115,22,0.2); background: rgba(249,115,22,0.02); }

.plan-info h3 { margin: 0; font-size: 18px; font-weight: 800; color: var(--text-primary); }
.plan-info p { margin: 2px 0 0; font-size: 13px; color: var(--text-muted); }

.price-input-wrapper label { font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 10px; display: block; }
.fancy-input-group {
  display: flex; align-items: center; background: var(--bg-primary); 
  border: 1.5px solid var(--border-color); border-radius: 16px; padding: 0 16px;
  transition: all 0.2s;
}
.fancy-input-group:focus-within { border-color: var(--accent); background: var(--bg-card); box-shadow: 0 0 0 4px rgba(249,115,22,0.05); }

.currency { font-weight: 800; color: var(--text-muted); font-size: 15px; margin-right: 12px; }
.fancy-input-group input {
  flex: 1; height: 52px; border: none; background: transparent; 
  font-size: 18px; font-weight: 800; color: var(--text-primary); outline: none;
}
.period { font-size: 13px; font-weight: 600; color: var(--text-muted); margin-left: 12px; }

.current-badge {
  display: flex; align-items: center; gap: 8px; padding: 8px 12px;
  background: var(--bg-primary); border-radius: 10px; border: 1px solid var(--border-color);
  font-size: 12px; font-weight: 600; color: var(--text-secondary);
}
.pulse-dot { width: 6px; height: 6px; border-radius: 50%; background: #34d399; animation: pulse 2s infinite; }
@keyframes pulse { 0% { opacity: 0.4; transform: scale(0.8); } 50% { opacity: 1; transform: scale(1.2); } 100% { opacity: 0.4; transform: scale(0.8); } }

.alert-premium { 
  display: flex; gap: 16px; padding: 20px; border-radius: 16px;
  background: rgba(147, 51, 234, 0.02); border: 1px solid rgba(147, 51, 234, 0.1);
  color: #7c3aed; line-height: 1.5;
}
.alert-icon { flex-shrink: 0; }
.alert-text { font-size: 13px; }

.btn-premium-save {
  position: relative; height: 56px; padding: 0 32px; border-radius: 16px;
  background: #1e293b; color: #fff; font-weight: 700; border: none;
  cursor: pointer; overflow: hidden; display: flex; align-items: center; gap: 12px;
  transition: all 0.3s;
}
.dark .btn-premium-save { background: var(--accent); }
.btn-premium-save:hover { transform: translateY(-2px); box-shadow: 0 12px 24px -6px rgba(0,0,0,0.2); }
.btn-premium-save:active { transform: translateY(0); }

.btn-shine {
  position: absolute; top: 0; left: -100%; width: 100%; height: 100%;
  background: linear-gradient(120deg, transparent, rgba(255,255,255,0.1), transparent);
  transition: all 0.6s;
}
.btn-premium-save:hover .btn-shine { left: 100%; }

@media (max-width: 900px) {
  .upload-premium-container { grid-template-columns: 1fr; }
  .form-grid-premium { grid-template-columns: 1fr; }
  .plans-grid-premium { grid-template-columns: 1fr; }
  .btn-premium-save { width: 100%; justify-content: center; }
}

/* Tab Navigation Consistency */
.tab-pill-premium {
  display: flex; flex-direction: column; align-items: center; gap: 8px;
  padding: 16px; border-radius: 16px; background: var(--bg-card);
  border: 1.5px solid var(--border-color); color: var(--text-muted);
  cursor: pointer; transition: 0.2s;
}
.tab-pill-premium.active { border-color: var(--accent); color: var(--accent); background: var(--accent-light); }

/* Common SMTP Info Premium */
.common-smtp-info {
  background: var(--bg-primary); border: 1.5px solid var(--border-color);
  padding: 24px; border-radius: 20px; margin-bottom: 32px;
}
.info-header { display: flex; align-items: center; gap: 8px; color: #0369a1; font-weight: 700; font-size: 13px; margin-bottom: 12px; }
.info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.info-col strong { display: block; font-size: 12px; margin-bottom: 6px; }
.info-col ul { margin: 0; padding-left: 16px; font-size: 12px; color: var(--text-secondary); line-height: 1.6; }
.info-col code { background: rgba(0,0,0,0.05); padding: 2px 4px; border-radius: 4px; font-family: monospace; }

.form-grid-image-style { display: flex; flex-direction: column; gap: 24px; }
.field-group-row { display: flex; gap: 20px; }
.field-group-row .field-group { flex: 1; }

.field-label-small { 
  font-size: 11px; 
  font-weight: 700; 
  color: var(--text-muted); 
  text-transform: uppercase; 
  letter-spacing: 0.5px;
  margin-bottom: 6px; 
}
.modern-input-small { 
  height: 42px; padding: 0 14px; border-radius: 10px; border: 1px solid var(--border-color);
  font-size: 13px; font-weight: 500; width: 100%; outline: none; transition: 0.2s;
  background: var(--bg-primary); color: var(--text-primary);
}
.modern-input-small:focus { border-color: var(--accent); box-shadow: 0 0 0 4px var(--accent-light); }

.field-hint-small { font-size: 11px; color: var(--text-muted); margin-top: 4px; }

.input-with-icon { position: relative; display: flex; align-items: center; }
.icon-toggle { position: absolute; right: 8px; background: none; border: none; cursor: pointer; color: var(--text-muted); padding: 4px; display: flex; align-items: center; }

.section-footer-simple { padding: 16px 32px; border-top: 1px solid var(--border-color); display: flex; justify-content: flex-end; }
.btn-image-style-save { 
  background: var(--accent); color: white; border: none; padding: 10px 24px; border-radius: 10px;
  font-weight: 700; font-size: 13px; display: flex; align-items: center; gap: 8px; cursor: pointer;
  transition: 0.2s;
}
.btn-image-style-save:hover { background: #ea580c; transform: translateY(-1px); }

.title-with-icon { display: flex; align-items: center; gap: 10px; color: var(--text-primary); }

.test-email-alert { 
  display: flex; align-items: center; gap: 12px; padding: 12px 16px; 
  background: rgba(147, 51, 234, 0.03); color: #7c3aed;
  border-radius: 10px; font-size: 13px; margin-bottom: 16px; 
}
.test-email-form { display: flex; flex-direction: column; gap: 16px; }
.input-group-test { display: flex; gap: 12px; align-items: center; }
.btn-test-email-solid {
  background: #059669; color: white; border: none; padding: 0 20px; height: 40px; border-radius: 8px;
  font-weight: 700; font-size: 13px; display: flex; align-items: center; gap: 8px; cursor: pointer; transition: 0.2s;
}
.btn-test-email-solid:hover { background: #047857; transform: translateY(-1px); }

@media (max-width: 768px) {
  .field-group-row { flex-direction: column; gap: 16px; }
  .field-group-row .field-group { flex: none; }
  .info-grid { grid-template-columns: 1fr; }
  .input-group-test { flex-direction: column; align-items: stretch; }
  .btn-test-email-solid { width: 100%; justify-content: center; }
}

/* ── Maintenance / Backup Tab ── */
.maintenance-redesign { border: none !important; background: transparent !important; box-shadow: none !important; }
.maintenance-redesign .section-header { padding: 4px 0 32px 0; border: none; }
.maintenance-redesign .section-body { padding: 0; }

.badge-maintenance {
  background: rgba(239, 68, 68, 0.1); color: #ef4444;
  padding: 4px 10px; border-radius: 100px; font-size: 10px; font-weight: 800; text-transform: uppercase;
}

.mb-6 { margin-bottom: 24px; }

.maintenance-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }

.maintenance-card {
  background: var(--bg-card); border: 1px solid var(--border-color);
  border-radius: 24px; overflow: hidden; transition: all 0.3s;
}
.maintenance-card:hover { border-color: rgba(59, 130, 246, 0.2); box-shadow: 0 8px 32px -8px rgba(59, 130, 246, 0.06); }

.maintenance-card-header {
  padding: 28px 32px; display: flex; gap: 20px; align-items: center;
  border-bottom: 1px solid var(--border-color);
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.03) 0%, transparent 100%);
}
.maintenance-icon-wrap {
  width: 52px; height: 52px; border-radius: 14px;
  background: linear-gradient(135deg, #3b82f6, #6366f1);
  display: flex; align-items: center; justify-content: center;
  color: #fff; flex-shrink: 0;
  box-shadow: 0 8px 20px -4px rgba(59, 130, 246, 0.3);
}
.maintenance-info { flex: 1; }
.maintenance-info h3 { margin: 0 0 4px 0; font-size: 16px; font-weight: 800; color: var(--text-primary); }
.maintenance-info p { margin: 0; font-size: 12px; color: var(--text-muted); line-height: 1.5; }

/* Restore Header */
.restore-header { background: linear-gradient(135deg, rgba(249, 115, 22, 0.03) 0%, transparent 100%); }
.restore-icon { background: linear-gradient(135deg, #f97316, #ea580c); box-shadow: 0 8px 20px -4px rgba(249, 115, 22, 0.3); }

/* System Info Header */
.sysinfo-header { background: linear-gradient(135deg, rgba(16, 185, 129, 0.03) 0%, transparent 100%); }
.sysinfo-icon { background: linear-gradient(135deg, #10b981, #059669); box-shadow: 0 8px 20px -4px rgba(16, 185, 129, 0.3); }

/* Actions Header */
.actions-header { background: linear-gradient(135deg, rgba(168, 85, 247, 0.03) 0%, transparent 100%); }
.actions-icon { background: linear-gradient(135deg, #a855f7, #7c3aed); box-shadow: 0 8px 20px -4px rgba(168, 85, 247, 0.3); }

.btn-refresh-sysinfo {
  width: 36px; height: 36px; border-radius: 10px; border: 1px solid var(--border-color);
  background: var(--bg-primary); cursor: pointer; display: flex; align-items: center; justify-content: center;
  color: var(--text-muted); transition: 0.2s; flex-shrink: 0;
}
.btn-refresh-sysinfo:hover { border-color: var(--accent); color: var(--accent); }

/* System Info Grid */
.sysinfo-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0; }
.sysinfo-group {
  padding: 24px 28px; border-bottom: 1px solid var(--border-color);
  border-right: 1px solid var(--border-color);
}
.sysinfo-group:nth-child(2n) { border-right: none; }
.sysinfo-group:nth-last-child(-n+2) { border-bottom: none; }
.sysinfo-group h4 {
  display: flex; align-items: center; gap: 8px;
  font-size: 12px; font-weight: 700; color: var(--text-muted);
  text-transform: uppercase; letter-spacing: 0.5px; margin: 0 0 16px 0;
}
.sysinfo-items { display: flex; flex-direction: column; gap: 10px; }
.sysinfo-item { display: flex; justify-content: space-between; align-items: center; }
.si-label { font-size: 13px; color: var(--text-secondary); }
.si-value { font-size: 13px; font-weight: 700; color: var(--text-primary); }
.si-value.mono { font-family: 'SF Mono', 'Fira Code', monospace; font-size: 12px; }
.si-value.highlight { color: var(--accent); }
.si-env { padding: 2px 8px; border-radius: 6px; font-size: 11px; text-transform: uppercase; }
.si-env.local { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
.si-env.production { background: rgba(16, 185, 129, 0.1); color: #059669; }

.sysinfo-loading {
  padding: 48px; display: flex; flex-direction: column; align-items: center; gap: 12px;
  color: var(--text-muted); font-size: 13px;
}

.backup-details { padding: 20px 32px; background: var(--bg-primary); border-bottom: 1px solid var(--border-color); }
.detail-row { display: flex; gap: 32px; flex-wrap: wrap; }
.detail-item { display: flex; flex-direction: column; gap: 4px; }
.detail-label { font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; }
.detail-value { font-size: 13px; font-weight: 600; color: var(--text-primary); }

.backup-actions { padding: 20px 32px; display: flex; align-items: center; gap: 16px; }

.btn-backup {
  position: relative; height: 48px; padding: 0 28px; border-radius: 14px;
  background: linear-gradient(135deg, #3b82f6, #6366f1); color: #fff;
  font-weight: 700; border: none; cursor: pointer; overflow: hidden;
  display: flex; align-items: center; gap: 10px;
  transition: all 0.3s; font-size: 13px;
}
.btn-backup:hover { transform: translateY(-2px); box-shadow: 0 12px 24px -6px rgba(59, 130, 246, 0.4); }
.btn-backup:active { transform: translateY(0); }
.btn-backup:disabled { opacity: 0.7; cursor: not-allowed; transform: none; }
.btn-backup .btn-shine {
  position: absolute; top: 0; left: -100%; width: 100%; height: 100%;
  background: linear-gradient(120deg, transparent, rgba(255,255,255,0.15), transparent);
  transition: all 0.6s;
}
.btn-backup:hover .btn-shine { left: 100%; }

.btn-restore {
  height: 48px; padding: 0 28px; border-radius: 14px;
  background: linear-gradient(135deg, #f97316, #ea580c); color: #fff;
  font-weight: 700; border: none; cursor: pointer;
  display: flex; align-items: center; gap: 10px;
  transition: all 0.3s; font-size: 13px;
}
.btn-restore:hover { transform: translateY(-2px); box-shadow: 0 12px 24px -6px rgba(249, 115, 22, 0.4); }
.btn-restore:disabled { opacity: 0.7; cursor: not-allowed; transform: none; }

/* Quick Actions */
.quick-actions-grid { display: flex; flex-direction: column; }
.quick-action-card {
  display: flex; align-items: center; gap: 16px; padding: 20px 28px;
  border-bottom: 1px solid var(--border-color); cursor: pointer;
  transition: all 0.2s;
}
.quick-action-card:last-child { border-bottom: none; }
.quick-action-card:hover { background: var(--bg-primary); }
.quick-action-card.disabled { opacity: 0.6; cursor: not-allowed; pointer-events: none; }

.qa-icon {
  width: 44px; height: 44px; border-radius: 12px;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.trash-icon { background: rgba(239, 68, 68, 0.08); color: #ef4444; }
.optimize-icon { background: rgba(16, 185, 129, 0.08); color: #059669; }

.qa-info { flex: 1; }
.qa-info h4 { margin: 0 0 2px 0; font-size: 14px; font-weight: 700; color: var(--text-primary); }
.qa-info p { margin: 0; font-size: 12px; color: var(--text-muted); }
.qa-arrow { color: var(--text-muted); flex-shrink: 0; }
.qa-status { color: var(--accent); flex-shrink: 0; }

@media (max-width: 900px) {
  .maintenance-grid { grid-template-columns: 1fr; }
  .sysinfo-grid { grid-template-columns: 1fr; }
  .sysinfo-group { border-right: none !important; }
  .sysinfo-group:nth-last-child(-n+2) { border-bottom: 1px solid var(--border-color); }
  .sysinfo-group:last-child { border-bottom: none; }
}

@media (max-width: 768px) {
  .maintenance-card-header { padding: 20px; }
  .backup-details { padding: 16px 20px; }
  .detail-row { gap: 20px; }
  .backup-actions { padding: 16px 20px; flex-direction: column; }
  .btn-backup, .btn-restore { width: 100%; justify-content: center; }
  .sysinfo-group { padding: 20px; }
  .quick-action-card { padding: 16px 20px; }
}/* ── Global Settings ── */
.global-plans-settings {
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: 16px;
  padding: 16px 24px;
}
.setting-card-mini {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 20px;
}
.setting-card-mini .s-info label { display: block; font-weight: 800; font-size: 14px; color: var(--text-primary); margin-bottom: 2px; }
.setting-card-mini .s-info p { margin: 0; font-size: 12px; color: var(--text-muted); }
.setting-card-mini .s-input { display: flex; align-items: center; gap: 10px; }
.setting-card-mini .s-input input {
  width: 80px; height: 40px; border-radius: 10px; border: 1px solid var(--border-color);
  background: var(--bg-primary); text-align: center; font-weight: 800; color: var(--accent);
}
.setting-card-mini .unit { font-size: 13px; font-weight: 700; color: var(--text-muted); }

/* ── Feature Manager ── */
.features-config-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 20px;
}
.feature-manager-card {
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: 20px;
  overflow: hidden;
  transition: 0.3s;
}
.feature-manager-card:hover { border-color: var(--accent); }
.feature-manager-card header {
  padding: 16px 20px;
  background: var(--bg-primary);
  display: flex;
  align-items: center;
  gap: 12px;
  border-bottom: 1px solid var(--border-color);
}
.f-icon-wrap {
  width: 32px; height: 32px; border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  background: var(--bg-card); color: var(--accent);
}
.feature-manager-card h3 { font-size: 14px; font-weight: 800; margin: 0; }
.f-list-editor { padding: 20px; display: flex; flex-direction: column; gap: 10px; }
.f-input-row { display: flex; gap: 8px; }
.f-input-row input {
  flex: 1; height: 40px; border-radius: 10px; border: 1px solid var(--border-color);
  background: var(--bg-primary); padding: 0 14px; font-size: 13px; color: var(--text-primary);
  transition: 0.2s;
}
.f-input-row input:focus { border-color: var(--accent); outline: none; background: var(--bg-card); }
.btn-remove-f {
  width: 40px; height: 40px; border-radius: 10px; border: none;
  background: rgba(239, 68, 68, 0.1); color: #ef4444; cursor: pointer;
  display: flex; align-items: center; justify-content: center; transition: 0.2s;
}
.btn-remove-f:hover { background: #ef4444; color: #fff; }
.btn-add-f {
  margin-top: 5px; height: 40px; border-radius: 10px; border: 1.5px dashed var(--border-color);
  background: transparent; color: var(--text-muted); font-size: 13px; font-weight: 700;
  cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;
  transition: 0.2s;
}
.btn-add-f:hover { border-color: var(--accent); color: var(--accent); background: rgba(249, 115, 22, 0.05); }

</style>
