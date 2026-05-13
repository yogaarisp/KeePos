<template>
  <div class="settings-page">
    <!-- Header Hero Section -->
    <div class="page-hero">
      <div class="hero-content">
        <div class="hero-icon-wrap">
          <Settings2 :size="24" />
        </div>
        <div>
          <h1 class="hero-title">Pengaturan Sistem</h1>
          <p class="hero-subtitle">Kelola informasi toko, metode pembayaran, dan konfigurasi sistem lainnya.</p>
        </div>
      </div>
    </div>

    <div class="settings-container">
      <!-- Tab Navigation (Horizontal Pill Style) -->
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
          <!-- Profil Toko -->
          <section v-if="activeTab === 'shop'" key="shop" class="settings-section">
            <header class="section-header">
              <h1 class="section-title">Profil Toko</h1>
              <p class="section-subtitle">Kelola informasi toko Anda yang ditampilkan di sistem</p>
            </header>

            <div class="section-body">
              <!-- Logo & Favicon Upload -->
              <div class="upload-grid">
                <div class="upload-item full-width-mobile">
                  <label class="field-label">Logo Toko</label>
                  <div class="upload-box" @click="$refs.logoInput.click()">
                    <div class="upload-preview" v-if="logoPreview || shopForm.shop_logo_url">
                      <img :src="logoPreview || baseUrl + '/storage/' + shopForm.shop_logo_url" alt="Logo">
                    </div>
                    <div v-else class="upload-placeholder">
                      <Camera :size="24" />
                      <span>Upload Logo</span>
                    </div>
                    <input type="file" ref="logoInput" @change="e => handleFile(e, 'logo')" accept="image/*" hidden>
                  </div>
                  <p class="upload-hint">Format: JPG, PNG. Maks 2MB.</p>
                </div>
              </div>

              <div class="divider-line"></div>

              <!-- Shop Info Form -->
              <div class="form-grid">
                <div class="field-group">
                  <label class="field-label">Nama Toko</label>
                  <input type="text" v-model="shopForm.shop_name" class="modern-input" placeholder="Contoh: Warteg Bahari">
                </div>
                <div class="field-group">
                  <label class="field-label">Nomor Telepon</label>
                  <input type="text" v-model="shopForm.shop_phone" class="modern-input" placeholder="08XX-XXXX-XXXX">
                </div>
                <div class="field-group">
                  <label class="field-label">Email Toko</label>
                  <input type="email" v-model="shopForm.shop_email" class="modern-input" placeholder="toko@email.com">
                </div>
                <div class="field-group">
                  <label class="field-label">Tagline / Slogan</label>
                  <input type="text" v-model="shopForm.shop_tagline" class="modern-input" placeholder="Contoh: Enak, Murah, Cepat">
                </div>
                <div class="field-group full-width">
                  <label class="field-label">Alamat Lengkap</label>
                  <textarea v-model="shopForm.shop_address" rows="3" class="modern-input modern-textarea" placeholder="Jl. Raya No. 123, Jakarta..."></textarea>
                </div>
              </div>
            </div>

            <footer class="section-footer">
              <div class="footer-info">
                <Info :size="14" />
                <span>Perubahan akan langsung diterapkan pada struk dan laporan.</span>
              </div>
              <button class="btn-primary" @click="saveShopSettings" :disabled="settingStore.loading">
                <Save :size="18" v-if="!settingStore.loading" />
                <RefreshCw :size="18" class="spin" v-else />
                {{ settingStore.loading ? 'Menyimpan...' : 'Simpan Perubahan' }}
              </button>
            </footer>
          </section>

          <!-- Metode Pembayaran -->
          <section v-else-if="activeTab === 'payment'" key="payment" class="settings-section">
            <header class="section-header-flex">
              <div>
                <h1 class="section-title">Metode Pembayaran</h1>
                <p class="section-subtitle">Atur metode pembayaran yang diterima di kasir.</p>
              </div>
              <button class="btn-primary" @click="openPaymentModal()">
                <Plus :size="18" /> Tambah Metode
              </button>
            </header>

            <div class="section-body no-padding">
              <div v-if="settingStore.paymentMethods.length === 0" class="empty-state">
                <div class="empty-icon-box">
                  <CreditCard :size="32" />
                </div>
                <h3>Belum Ada Metode Pembayaran</h3>
                <p>Tambahkan metode pembayaran pertama untuk mulai menerima transaksi.</p>
                <button class="btn-outline" @click="openPaymentModal()">
                  <Plus :size="16" /> Tambah Metode
                </button>
              </div>

              <div v-else class="payment-table">
                <div v-for="m in settingStore.paymentMethods" :key="m.id" class="payment-row">
                  <div class="payment-item-main">
                    <div class="payment-type-icon" :class="m.type">
                      <Banknote v-if="m.type === 'cash'" :size="18" />
                      <Smartphone v-else-if="m.type === 'e-wallet'" :size="18" />
                      <Building2 v-else :size="18" />
                    </div>
                    <div class="payment-item-info">
                      <span class="payment-item-name">{{ m.name }}</span>
                      <span class="payment-item-meta">{{ getTypeLabel(m.type) }} • {{ m.account_number || 'Tanpa No. Rek' }}</span>
                    </div>
                  </div>
                  <div class="payment-item-actions">
                    <div class="status-chip" :class="{ active: m.is_active }">
                      {{ m.is_active ? 'Aktif' : 'Nonaktif' }}
                    </div>
                    <button class="icon-btn" @click="openPaymentModal(m)">
                      <Edit2 :size="16" />
                    </button>
                    <button class="icon-btn delete" @click="settingStore.deletePaymentMethod(m.id)">
                      <Trash2 :size="16" />
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </section>

          <!-- Email (SMTP) Settings -->
          <section v-else-if="activeTab === 'email'" key="email" class="settings-section">
            <header class="section-header">
              <h1 class="section-title">Pengaturan Email (SMTP)</h1>
              <p class="section-subtitle">Konfigurasi server email untuk pengiriman struk digital & notifikasi.</p>
            </header>

            <div class="section-body">
              <div class="form-grid">
                <div class="field-group">
                  <label class="field-label">SMTP Host</label>
                  <input type="text" v-model="smtpForm.smtp_host" class="modern-input" placeholder="smtp.gmail.com">
                </div>
                <div class="field-group">
                  <label class="field-label">SMTP Port</label>
                  <input type="text" v-model="smtpForm.smtp_port" class="modern-input" placeholder="587">
                </div>
                <div class="field-group">
                  <label class="field-label">Username</label>
                  <input type="text" v-model="smtpForm.smtp_username" class="modern-input" placeholder="email@gmail.com">
                </div>
                <div class="field-group">
                  <label class="field-label">Password</label>
                  <input type="password" v-model="smtpForm.smtp_password" class="modern-input" placeholder="••••••••">
                </div>
                <div class="field-group">
                  <label class="field-label">Encryption</label>
                  <select v-model="smtpForm.smtp_encryption" class="modern-input select-input">
                    <option value="tls">TLS</option>
                    <option value="ssl">SSL</option>
                    <option value="none">None</option>
                  </select>
                </div>
                <div class="field-group">
                  <label class="field-label">From Name</label>
                  <input type="text" v-model="smtpForm.smtp_from_name" class="modern-input" placeholder="Kee POS Premium">
                </div>
                <div class="field-group full-width">
                  <label class="field-label">From Address</label>
                  <input type="email" v-model="smtpForm.smtp_from_address" class="modern-input" placeholder="noreply@wartegkee.com">
                </div>
              </div>
            </div>

            <footer class="section-footer">
              <div class="footer-info">
                <Shield :size="14" />
                <span>Pastikan port SMTP terbuka pada firewall server Anda.</span>
              </div>
              <button class="btn-primary" @click="saveEmailSettings" :disabled="settingStore.loading">
                <Save :size="18" v-if="!settingStore.loading" />
                <RefreshCw :size="18" class="spin" v-else />
                {{ settingStore.loading ? 'Menyimpan...' : 'Simpan Konfigurasi' }}
              </button>
            </footer>
          </section>

          <!-- Database Settings -->
          <section v-else-if="activeTab === 'database'" key="database" class="settings-section">
            <header class="section-header">
              <h1 class="section-title">Manajemen Database</h1>
              <p class="section-subtitle">Cadangkan data Anda secara berkala atau pulihkan dari file backup.</p>
            </header>

            <div class="section-body">
              <div class="db-grid">
                <div class="db-card">
                  <div class="db-card-icon blue">
                    <Download :size="24" />
                  </div>
                  <div class="db-card-info">
                    <h3>Ekspor Data Toko</h3>
                    <p>Unduh data toko Anda (Produk, Transaksi, Stok, dll) dalam format .SQL.</p>
                    <button class="btn-outline" @click="settingStore.exportDatabase()" :disabled="settingStore.loading">
                      <Download :size="16" /> Download Backup
                    </button>
                  </div>
                </div>

                <div class="db-card">
                  <div class="db-card-icon orange">
                    <RefreshCw :size="24" />
                  </div>
                  <div class="db-card-info">
                    <h3>Pulihkan Data Toko</h3>
                    <p>Impor file backup (.SQL) untuk mengembalikan data toko Anda. Berhati-hatilah!</p>
                    <input type="file" ref="dbFileInput" @change="handleImport" accept=".sql" hidden>
                    <button class="btn-outline" @click="$refs.dbFileInput.click()" :disabled="settingStore.loading">
                      <RefreshCw :size="16" /> Upload & Restore
                    </button>
                  </div>
                </div>
              </div>

              <div class="alert-box warning">
                <Info :size="18" />
                <div>
                  <strong>Perhatian:</strong>
                  <p>Proses pemulihan database akan menghapus semua data operasional toko ini (Produk, Transaksi, Stok) dan menggantinya dengan data dari file backup.</p>
                </div>
              </div>
            </div>
          </section>

          <!-- Printer Settings (Hybrid Web-Native) -->
          <section v-else-if="activeTab === 'printer'" key="printer" class="settings-section printer-section-premium">
            <header class="section-header">
              <div class="header-with-badge">
                <h1 class="section-title">Konfigurasi Printer Thermal</h1>
                <div :class="['path-badge', printer.isNative.value ? 'native' : 'web']">
                  <div class="badge-dot"></div>
                  <span>{{ printer.isNative.value ? 'Path B: Android Native' : 'Path A: Web Bluetooth' }}</span>
                </div>
              </div>
              <p class="section-subtitle">Sistem menggunakan jalur Hybrid untuk kestabilan maksimal di Browser maupun APK.</p>
            </header>

            <div class="section-body">
              <div class="printer-main-layout">
                <!-- Left Column: Settings & Troubleshooting -->
                <div class="printer-settings-flow">
                  <!-- Status Printer Row (Premium Glass) -->
                  <div class="ptr-status-row-premium" :class="{ connected: printer.connected.value }">
                    <div class="ptr-visual">
                      <div class="ptr-icon-ring">
                        <Printer :size="28" />
                      </div>
                      <div class="ptr-connection-line" v-if="printer.connected.value">
                        <div class="line-glow"></div>
                      </div>
                    </div>
                    
                    <div class="ptr-info-main">
                      <div class="ptr-status-tag">
                        {{ printer.connected.value ? 'PRINTER ONLINE' : 'PRINTER OFFLINE' }}
                      </div>
                      <h3 class="ptr-device-name">
                        {{ printer.connected.value ? printer.printerName.value : 'Belum Ada Perangkat' }}
                      </h3>
                      <p class="ptr-device-meta" v-if="printer.connected.value">
                        {{ printer.isNative.value ? 'Terhubung via Android Interface Bridge' : 'Terhubung via Web Bluetooth GATT' }}
                      </p>
                      <p class="ptr-device-meta" v-else>
                        Klik tombol scan di bawah untuk mencari printer
                      </p>
                    </div>

                    <div class="ptr-status-indicator">
                      <div class="pulse-ring" v-if="printer.connected.value"></div>
                      <div class="status-dot"></div>
                    </div>
                  </div>

                  <!-- Action Buttons (Floating Style) -->
                  <div class="ptr-actions-premium">
                    <button 
                      v-if="!printer.connected.value"
                      class="btn-ptr-scan" 
                      @click="connectPrinter" 
                      :disabled="printer.connecting.value"
                    >
                      <div class="btn-content">
                        <Bluetooth v-if="!printer.connecting.value" :size="20" />
                        <RefreshCw v-else :size="20" class="spin" />
                        <span>{{ printer.connecting.value ? 'Mencari...' : 'Scan & Hubungkan' }}</span>
                      </div>
                      <div class="btn-shimmer"></div>
                    </button>

                    <button 
                      v-else
                      class="btn-ptr-scan disconnect-mode" 
                      @click="disconnectPrinter"
                    >
                      <div class="btn-content">
                        <X :size="20" />
                        <span>Putuskan Koneksi</span>
                      </div>
                      <div class="btn-shimmer"></div>
                    </button>

                    <div class="btn-group-secondary">
                      <button class="btn-ptr-outline" @click="handleTestPrint" :disabled="!printer.connected.value">
                        <Printer :size="18" /> <span>Test Print</span>
                      </button>
                    </div>
                  </div>

                  <!-- Preferences Grid -->
                  <div class="ptr-prefs-premium">
                    <!-- Paper Size -->
                    <div class="pref-card-premium">
                      <div class="pref-card-header">
                        <div class="pref-icon"><FileSpreadsheet :size="18" /></div>
                        <label>Ukuran Kertas</label>
                      </div>
                      <div class="paper-toggle-group">
                        <button 
                          :class="['paper-btn', { active: settingStore.paperSize === '58' }]"
                          @click="settingStore.setPrinterPreference('paperSize', '58')"
                        >
                          <span class="p-val">58<sub>mm</sub></span>
                          <span class="p-lab">Standard</span>
                          <Check v-if="settingStore.paperSize === '58'" :size="14" class="p-check" />
                        </button>
                        <button 
                          :class="['paper-btn', { active: settingStore.paperSize === '80' }]"
                          @click="settingStore.setPrinterPreference('paperSize', '80')"
                        >
                          <span class="p-val">80<sub>mm</sub></span>
                          <span class="p-lab">Large</span>
                          <Check v-if="settingStore.paperSize === '80'" :size="14" class="p-check" />
                        </button>
                      </div>
                    </div>

                    <!-- Auto Print Toggle -->
                    <div class="pref-card-premium">
                      <div class="pref-card-header">
                        <div class="pref-icon"><Wifi :size="18" /></div>
                        <label>Auto-Print Flow</label>
                      </div>
                      <div class="auto-print-content">
                        <div class="auto-print-info">
                          <strong>Cetak Otomatis</strong>
                          <p>Keluarkan struk langsung setelah transaksi</p>
                        </div>
                        <label class="premium-switch">
                          <input type="checkbox" :checked="settingStore.autoPrint" @change="e => settingStore.setPrinterPreference('autoPrint', e.target.checked)">
                          <span class="switch-slider"></span>
                        </label>
                      </div>
                    </div>

                    <!-- Cash Drawer Toggle -->
                    <div class="pref-card-premium">
                      <div class="pref-card-header">
                        <div class="pref-icon"><DollarSign :size="18" /></div>
                        <label>Laci Kasir</label>
                      </div>
                      <div class="auto-print-content">
                        <div class="auto-print-info">
                          <strong>Buka Laci Otomatis</strong>
                          <p>Buka laci kasir saat cetak struk pembayaran</p>
                        </div>
                        <label class="premium-switch">
                          <input type="checkbox" :checked="settingStore.openCashDrawer" @change="e => settingStore.setPrinterPreference('openCashDrawer', e.target.checked)">
                          <span class="switch-slider"></span>
                        </label>
                      </div>
                    </div>
                  </div>

                  <!-- Troubleshooting (Modern Card) -->
                  <div class="troubleshoot-glass full-width">
                    <div class="ts-header">
                      <Info :size="18" />
                      <span>Troubleshooting & Tips</span>
                    </div>
                    <div class="ts-grid">
                      <div class="ts-item"><strong>Gagal Scan?</strong><p>Pastikan HTTPS aktif di browser.</p></div>
                      <div class="ts-item"><strong>Teks Terpotong?</strong><p>Pilih ukuran 58mm untuk printer kecil.</p></div>
                    </div>
                  </div>
                </div>

                <!-- Right Column: Live Receipt Preview -->
                <div class="receipt-preview-sidebar">
                  <div class="preview-label">
                    <Eye :size="16" />
                    <span>Live Struk Preview</span>
                  </div>
                  <div class="thermal-receipt" :class="settingStore.paperSize === '80' ? 'p-80mm' : 'p-58mm'">
                    <div class="r-header">
                      <!-- Logo Toko -->
                      <div v-if="logoPreview || shopForm.shop_logo_url" class="r-logo">
                        <img :src="logoPreview || (baseUrl + '/storage/' + shopForm.shop_logo_url)" alt="Logo" class="receipt-logo">
                      </div>
                      <h2 class="r-shop-name">{{ shopForm.shop_name || 'Kee POS' }}</h2>
                      <p class="r-shop-addr">{{ shopForm.shop_address || 'Jl. Raya Digital No. 1' }}</p>
                      <p class="r-shop-telp">Telp: {{ shopForm.shop_phone || '0812-xxxx-xxxx' }}</p>
                    </div>
                    <div class="r-divider">--------------------------------</div>
                    <div class="r-meta">
                      <span>No: #2026-0001</span>
                      <span>{{ new Date().toLocaleDateString('id-ID') }} {{ new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) }}</span>
                      <span>Kasir: Admin</span>
                    </div>
                    <div class="r-divider">--------------------------------</div>
                    <div class="r-items">
                      <div class="r-item">
                        <div class="r-item-top"><span>Nasi Ayam Geprek</span><span>20.000</span></div>
                        <div class="r-item-bottom"><span>1 x 20.000</span></div>
                      </div>
                    </div>
                    <div class="r-divider">--------------------------------</div>
                    <div class="r-totals">
                      <div class="r-total-row bold"><span>TOTAL</span><span>25.000</span></div>
                    </div>
                    <div class="r-divider">--------------------------------</div>
                    <div class="r-footer">
                      <p>Terima Kasih</p>
                      <p>Selamat Menikmati!</p>
                      <p class="powered-by">Powered by {{ shopForm.shop_name || 'Kee POS' }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </section>


          <!-- Google Sheets Settings -->
          <section v-else-if="activeTab === 'googlesheet'" key="googlesheet" class="settings-section">
            <header class="section-header">
              <h1 class="section-title">Sinkronisasi Google Sheets</h1>
              <p class="section-subtitle">Otomatisasi pencatatan transaksi dan stok ke Google Spreadsheet.</p>
            </header>

            <div class="section-body">
              <div class="gs-config-card">
                <!-- Inputs Section -->
                <div class="field-group full-width">
                  <div class="field-header">
                    <label class="field-label">Spreadsheet ID</label>
                    <span class="field-hint">Ambil dari URL Spreadsheet Anda</span>
                  </div>
                  <input 
                    type="text" 
                    class="modern-input" 
                    v-model="shopForm.google_spreadsheet_id" 
                    placeholder="Contoh: 1a2b3c4d5e6f7g8h9i0j..."
                  >
                </div>

                <div class="field-group full-width">
                  <div class="field-header">
                    <label class="field-label">Service Account JSON (Key)</label>
                    <div class="header-actions-inline">
                      <button class="btn-text-action" @click="$refs.jsonFileInput.click()">
                        <Upload :size="14" /> Upload File JSON
                      </button>
                      <button v-if="shopForm.google_service_account_json" class="btn-text-action danger" @click="shopForm.google_service_account_json = ''">
                        <Trash2 :size="14" /> Hapus
                      </button>
                    </div>
                  </div>
                  <input type="file" ref="jsonFileInput" @change="handleJsonPartUpload" hidden accept=".json">
                  <textarea 
                    class="modern-input modern-textarea code-area" 
                    v-model="shopForm.google_service_account_json" 
                    rows="8" 
                    placeholder='{ "type": "service_account", ... }'
                  ></textarea>
                  <p class="field-info-text">Silakan upload file .json yang didownload dari Google Console, atau tempelkan isinya di sini.</p>
                </div>

                <div class="gs-sync-row">
                  <div class="pref-info">
                    <label class="field-label-bold">Aktifkan Sinkronisasi Otomatis</label>
                    <p class="pref-desc">Jika aktif, data transaksi akan dikirim ke Spreadsheet setiap kali checkout.</p>
                  </div>
                  <label class="modern-switch">
                    <input type="checkbox" v-model="shopForm.google_sync_enabled">
                    <span class="switch-slider"></span>
                  </label>
                </div>

                <div class="divider-line"></div>

                <!-- Action Buttons -->
                <div class="gs-action-box">
                  <button class="btn-primary" @click="saveShopSettings" :disabled="settingStore.loading">
                    <Save :size="18" /> Simpan Konfigurasi
                  </button>
                  <button class="btn-outline" @click="handleSyncAll" :disabled="!shopForm.google_spreadsheet_id">
                    <RefreshCw :size="18" /> Sync Semua Data Sekarang
                  </button>
                </div>

                <div class="divider-line"></div>

                <!-- Info / Tips Section -->
                <div class="gs-tips-section">
                  <div class="tips-header">
                    <div class="tips-icon-wrap">
                      <Info :size="18" />
                    </div>
                    <strong>PETUNJUK PENGGUNAAN:</strong>
                  </div>
                  
                  <div class="tips-content">
                    <ul class="tips-list">
                      <li>Pastikan email Service Account sudah ditambahkan sebagai <strong>Editor</strong> di menu Share Spreadsheet.</li>
                      <li>Sistem akan otomatis membuat sheet/tab baru (Transaksi, Stok, Produk, Resep) jika belum ada.</li>
                      <li>Gunakan tombol "Sync Semua Data" untuk pertama kalinya agar seluruh data ter-upload.</li>
                    </ul>
                    
                    <div class="tips-footer">
                      <button class="help-link-btn" @click="showGuide">
                        <Download :size="14" />
                        Lihat Panduan Detail
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </section>
        </Transition>
      </main>
    </div>

    <!-- Payment Modal (Modern SaaS Style) -->
    <Teleport to="body">
      <Transition name="modal-fade">
        <div v-if="paymentModal.show" class="modal-overlay" @click.self="paymentModal.show = false">
          <div class="modal-container">
            <header class="modal-header">
              <div class="modal-header-content">
                <button class="btn-back-header" @click="paymentModal.show = false">
                  <ArrowLeft :size="20" />
                </button>
                <div class="modal-icon-wrap">
                  <CreditCard :size="20" />
                </div>
                <div class="modal-title-area">
                  <h3 class="modal-title">{{ paymentModal.form.id ? 'Edit Metode' : 'Tambah Metode Baru' }}</h3>
                  <p class="modal-subtitle">Atur rincian metode pembayaran di bawah ini</p>
                </div>
              </div>
            </header>
            
            <div class="modal-body">
              <div class="field-group">
                <label class="field-label">Nama Metode</label>
                <input type="text" v-model="paymentModal.form.name" class="modern-input" placeholder="Contoh: Dana, BCA, Tunai...">
              </div>
              
              <div class="field-group">
                <label class="field-label">Tipe Pembayaran</label>
                <div class="modern-radio-group">
                  <button 
                    v-for="tp in typeOptions" 
                    :key="tp.value"
                    :class="['radio-btn', { active: paymentModal.form.type === tp.value }]"
                    @click="paymentModal.form.type = tp.value"
                  >
                    <component :is="tp.icon" :size="16" />
                    <span>{{ tp.label }}</span>
                  </button>
                </div>
              </div>

              <Transition name="slide-up">
                <div v-if="paymentModal.form.type !== 'cash'" class="form-grid">
                  <div class="field-group">
                    <label class="field-label">No. Rekening / HP</label>
                    <input type="text" v-model="paymentModal.form.account_number" class="modern-input" placeholder="081234567890">
                  </div>
                  <div class="field-group">
                    <label class="field-label">Atas Nama</label>
                    <input type="text" v-model="paymentModal.form.account_name" class="modern-input" placeholder="Nama pemilik">
                  </div>
                </div>
              </Transition>

              <div class="divider-line"></div>

              <div class="flex-between">
                <div>
                  <span class="field-label">Status Aktif</span>
                  <p class="field-desc">Tampilkan metode ini di halaman checkout</p>
                </div>
                <label class="modern-switch">
                  <input type="checkbox" v-model="paymentModal.form.is_active">
                  <span class="switch-slider"></span>
                </label>
              </div>
            </div>

            <footer class="modal-footer">
              <button class="btn-primary" @click="handleSavePayment">
                {{ paymentModal.form.id ? 'Perbarui' : 'Simpan Metode' }}
              </button>
            </footer>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref, computed, markRaw, watch } from 'vue';
import { useSettingStore } from '../stores/setting';
import { useAuthStore } from '../stores/auth';
import { useRouter } from 'vue-router';
import { showConfirm, showSuccess, showError } from '../utils/swal';
import { 
  Printer, Bluetooth, Check, Wifi, ArrowLeft, X, FileSpreadsheet,
  Settings2, Store, CreditCard, Mail, Database, Camera, Image, Info, Plus, Edit2, Trash2, Banknote, Smartphone, Building2, Save, RefreshCw, Shield, Download, Upload, Eye, ShieldCheck, DollarSign
} from 'lucide-vue-next';
import { usePrinter } from '../composables/usePrinter';
import { baseUrl } from '../api';
import api from '../api';

const settingStore = useSettingStore();
const auth = useAuthStore();
const router = useRouter();
const printer = usePrinter();

const allTabs = [
  { id: 'shop', label: 'Profil Toko', desc: 'Nama, alamat, logo', icon: markRaw(Store), color: 'orange' },
  { id: 'payment', label: 'Pembayaran', desc: 'Metode & rekening', icon: markRaw(CreditCard), color: 'blue' },
  { id: 'email', label: 'Email (SMTP)', desc: 'Konfigurasi email sistem', icon: markRaw(Mail), color: 'purple' },
  { id: 'database', label: 'Database', desc: 'Backup & Restore data', icon: markRaw(Database), color: 'red' },
  { id: 'printer', label: 'Printer', desc: 'Pengaturan printer struk', icon: markRaw(Printer), color: 'orange' },
  { id: 'googlesheet', label: 'Google Sheets', desc: 'Sinkronisasi data ke cloud', icon: markRaw(FileSpreadsheet), color: 'green' },
];

// Filter tabs based on user role
const tabs = computed(() => {
  let filtered = [...allTabs];

  if (auth.user?.role === 'kasir') {
    // Kasir only sees Printer tab
    return filtered.filter(tab => tab.id === 'printer');
  }
  
  // Hide database tab for regular tenant admins, only superadmin can see it
  if (auth.user?.role !== 'superadmin') {
    filtered = filtered.filter(tab => tab.id !== 'database');
  }

  return filtered;
});

const typeOptions = [
  { value: 'cash', label: 'Tunai', icon: markRaw(Banknote) },
  { value: 'e-wallet', label: 'E-Wallet', icon: markRaw(Smartphone) },
  { value: 'transfer', label: 'Transfer', icon: markRaw(Building2) },
];

// Set default active tab based on role
const activeTab = ref(auth.user?.role === 'kasir' ? 'printer' : 'shop');
const logoPreview = ref(null);
const logoFile = ref(null);

const shopForm = reactive({
  shop_name: '',
  shop_phone: '',
  shop_email: '',
  shop_tagline: '',
  shop_address: '',
  shop_logo_url: '', // For displaying existing logo
  google_spreadsheet_id: '',
  google_service_account_json: '',
  google_sync_enabled: false,
});

const smtpForm = reactive({
  smtp_host: '',
  smtp_port: '',
  smtp_username: '',
  smtp_password: '',
  smtp_encryption: 'tls',
  smtp_from_address: '',
  smtp_from_name: ''
});


const paymentModal = reactive({
  show: false,
  form: {
    id: null,
    name: '',
    type: 'cash',
    account_number: '',
    account_name: '',
    is_active: true
  }
});




const syncForm = () => {
  const st = settingStore.settings;
  shopForm.shop_name = st.shop_name || '';
  shopForm.shop_phone = st.shop_phone || '';
  shopForm.shop_email = st.shop_email || '';
  shopForm.shop_tagline = st.shop_tagline || '';
  shopForm.shop_address = st.shop_address || '';
  shopForm.shop_logo_url = st.shop_logo || null;
  shopForm.shop_favicon_url = st.shop_favicon || null;
  shopForm.google_spreadsheet_id = st.google_spreadsheet_id || '';
  shopForm.google_service_account_json = st.google_service_account_json || '';
  shopForm.google_sync_enabled = st.google_sync_enabled === 'true' || st.google_sync_enabled === true;
  
  // SMTP
  smtpForm.smtp_host = st.smtp_host || '';
  smtpForm.smtp_port = st.smtp_port || '';
  smtpForm.smtp_username = st.smtp_username || '';
  smtpForm.smtp_password = st.smtp_password || '';
  smtpForm.smtp_encryption = st.smtp_encryption || 'tls';
  smtpForm.smtp_from_address = st.smtp_from_address || '';
  smtpForm.smtp_from_name = st.smtp_from_name || '';
};

const handleFile = (e, type) => {
  const file = e.target.files[0];
  if (file) {
    if (type === 'logo') {
      logoFile.value = file;
      logoPreview.value = URL.createObjectURL(file);
    }
  }
};

const connectPrinter = async () => {
  try {
    await printer.connect();
    settingStore.setPrinterPreference('printerName', printer.printerName.value);
    showSuccess('Printer berhasil terhubung!');
  } catch (err) {
    showError('Koneksi printer gagal: ' + err.message);
  }
};

const disconnectPrinter = async () => {
  await printer.disconnect();
  settingStore.setPrinterPreference('printerName', null);
  showSuccess('Printer diputuskan (hard reset).');
};

const handleTestPrint = async () => {
  try {
    await printer.testPrint();
    showSuccess('Test print berhasil!');
  } catch (err) {
    showError('Test print gagal: ' + err.message);
  }
};

const saveShopSettings = async () => {
  const formData = new FormData();
  
  const settings = {
    shop_name: shopForm.shop_name,
    shop_phone: shopForm.shop_phone,
    shop_email: shopForm.shop_email,
    shop_tagline: shopForm.shop_tagline,
    shop_address: shopForm.shop_address,
    google_spreadsheet_id: shopForm.google_spreadsheet_id,
    google_service_account_json: shopForm.google_service_account_json,
    google_sync_enabled: shopForm.google_sync_enabled
  };
  
  formData.append('settings', JSON.stringify(settings));
  if (logoFile.value) {
    formData.append('shop_logo', logoFile.value);
  }

  const success = await settingStore.saveSettings(formData);
  if (success) {
    showSuccess('Pengaturan toko disimpan!');
  } else {
    showError('Gagal menyimpan pengaturan toko');
  }
};

const saveEmailSettings = async () => {
  const formData = new FormData();
  const settings = {
    smtp_host: smtpForm.smtp_host,
    smtp_port: smtpForm.smtp_port,
    smtp_username: smtpForm.smtp_username,
    smtp_password: smtpForm.smtp_password,
    smtp_encryption: smtpForm.smtp_encryption,
    smtp_from_address: smtpForm.smtp_from_address,
    smtp_from_name: smtpForm.smtp_from_name
  };
  
  formData.append('settings', JSON.stringify(settings));
  
  if (await settingStore.saveSettings(formData)) {
    showSuccess('Pengaturan Email SMTP berhasil disimpan!');
  }
};

onMounted(async () => {
  await settingStore.fetchSettings();
  syncForm();
  
  // Cooling Down (500ms) sebelum auto-connect agar traffic Bluetooth stabil
  setTimeout(() => {
    printer.autoConnect();
  }, 500);
});

const handleSyncAll = async () => {
  try {
    const result = await showConfirm({
      title: 'Sinkronisasi Seluruh Data?',
      text: 'Sistem akan mengirim seluruh data Transaksi, Stok, Produk, dan Resep ke Google Sheets. Ini mungkin memakan waktu beberapa saat.',
      confirmText: 'Ya, Sync Sekarang'
    });

    if (result.isConfirmed) {
      const resp = await settingStore.syncGoogleSheet();
      if (resp.success) {
        showSuccess(resp.message);
      } else {
        showError(resp.message);
      }
    }
  } catch (err) {
    showError('Sinkronisasi gagal: ' + err.message);
  }
};

const handleJsonPartUpload = (e) => {
  const file = e.target.files[0];
  if (!file) return;

  const reader = new FileReader();
  reader.onload = (res) => {
    try {
      // Validate JSON
      const json = JSON.parse(res.target.result);
      shopForm.google_service_account_json = JSON.stringify(json, null, 2);
      showSuccess('File JSON berhasil dimuat!');
    } catch (err) {
      showError('File bukan format JSON yang valid');
    }
  };
  reader.readAsText(file);
};

const showGuide = () => {
  window.open('https://github.com/your-repo/blob/main/Google_Sheets_Implementation.md', '_blank');
};

const handleImport = async (e) => {
  const file = e.target.files[0];
  if (!file) return;

  const result = await showConfirm({
    title: 'Konfirmasi Restore',
    text: 'Apakah Anda yakin ingin melakukan pemulihan database? Data saat ini akan ditimpa.',
    icon: 'warning'
  });

  if (result.isConfirmed) {
    const formData = new FormData();
    formData.append('database_file', file);
    if (await settingStore.importDatabase(formData)) {
      showSuccess('Database berhasil dipulihkan!');
      window.location.reload();
    }
  }
  e.target.value = '';
};

const openPaymentModal = (method = null) => {
  if (method) {
    paymentModal.form = { ...method };
  } else {
    paymentModal.form = {
      id: null,
      name: '',
      type: 'cash',
      account_number: '',
      account_name: '',
      is_active: true
    };
  }
  paymentModal.show = true;
};

const handleSavePayment = async () => {
  const success = await settingStore.savePaymentMethod(paymentModal.form);
  if (success) {
    paymentModal.show = false;
  }
};

const getTypeLabel = (type) => {
  if (type === 'cash') return 'Tunai';
  if (type === 'e-wallet') return 'E-Wallet';
  return 'Transfer Bank';
};

// Watch modal to toggle mobile nav and body scroll
watch(() => paymentModal.show, (val) => {
  if (val) {
    document.body.classList.add('hide-mobile-nav');
    document.body.style.overflow = 'hidden';
  } else {
    document.body.classList.remove('hide-mobile-nav');
    document.body.style.overflow = '';
  }
});
</script>

<style scoped>
/* ── Global Page Style ── */
.settings-page {
  min-height: 100vh;
  padding: 0;
  background-color: transparent;
  color: var(--text-primary);
  font-family: 'Plus Jakarta Sans', sans-serif;
  -webkit-font-smoothing: antialiased;
  animation: fadeIn 0.4s ease;
}

/* ── Hero Section ── */
.page-hero {
  display: flex; align-items: center; justify-content: space-between;
  background: linear-gradient(135deg, rgba(249,115,22,0.08) 0%, rgba(249,115,22,0.02) 100%);
  border: 1px solid rgba(249,115,22,0.1); border-radius: 20px;
  padding: 28px 32px; margin-bottom: 24px;
}
.hero-content { display: flex; align-items: center; gap: 18px; }
.hero-icon-wrap {
  width: 54px; height: 54px; border-radius: 16px;
  background: linear-gradient(135deg, var(--accent), #fb923c);
  display: flex; align-items: center; justify-content: center;
  color: #fff; box-shadow: 0 8px 24px rgba(249,115,22,0.25);
}
.hero-title { font-size: 22px; font-weight: 600; color: var(--text-primary); margin-bottom: 4px; }
.hero-subtitle { font-size: 14px; color: var(--text-secondary); font-weight: 500; }

/* ── Container ── */
.settings-container {
  max-width: 100%;
  margin: 0;
  padding: 0;
  animation: slideUp 0.5s ease both;
}

/* ── Tabs Navigation ── */
.settings-tabs-nav {
  margin-bottom: 32px;
  border-bottom: 1px solid var(--border-color);
  background: var(--bg-card);
  border-radius: 18px;
  padding: 4px;
  border: 1px solid var(--border-color);
}

.tabs-scroll-container {
  display: flex;
  gap: 8px;
  overflow-x: auto;
  scrollbar-width: none;
  padding: 4px;
}

.tabs-scroll-container::-webkit-scrollbar {
  display: none;
}

.tab-pill {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 20px;
  border: none;
  background: transparent;
  color: var(--text-secondary);
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  border-radius: 12px;
  white-space: nowrap;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
}

.tab-pill:hover {
  background-color: var(--bg-card-hover);
  color: var(--text-primary);
}

.tab-pill.active {
  background-color: var(--accent);
  color: #fff;
  box-shadow: 0 8px 16px rgba(249, 115, 22, 0.2);
}

.tab-underline {
  display: none; /* Pill style doesn't need underline */
}

/* ── Content Sections ── */
.settings-section {
  max-width: 100%;
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: 24px;
  padding: 32px;
  margin-bottom: 24px;
}

.section-header {
  margin-bottom: 32px;
  border-bottom: 1px solid var(--border-color);
  padding: 4px 0 20px 0;
}

.section-header-flex {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 20px;
  margin-bottom: 32px;
  border-bottom: 1px solid var(--border-color);
  padding: 4px 0 20px 0;
}

.section-title {
  font-size: 20px;
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: 6px;
}

.section-subtitle {
  font-size: 13px;
  color: var(--text-muted);
}

.section-body {
  padding-bottom: 0;
}

.section-body.no-padding {
  padding-bottom: 0;
}

.divider-line {
  height: 1px;
  background-color: var(--border-color);
  margin: 32px 0;
}

/* ── Forms ── */
.form-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 24px;
}

.field-group {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.field-group.full-width {
  grid-column: span 2;
}

.field-label {
  font-size: 12px;
  font-weight: 700;
  color: var(--text-muted);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.field-desc {
  font-size: 12px;
  color: var(--text-muted);
}

.modern-input {
  height: 48px;
  padding: 0 16px;
  border: 1px solid var(--border-color);
  border-radius: 14px;
  font-size: 14px;
  font-weight: 600;
  color: var(--text-primary);
  background-color: var(--bg-primary);
  transition: all 0.2s;
  width: 100%;
}

.modern-input:focus {
  outline: none;
  border-color: var(--accent);
  box-shadow: 0 0 0 4px var(--accent-light);
}

.modern-textarea {
  height: auto;
  padding: 14px;
  resize: vertical;
  min-height: 100px;
}

.select-input {
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236B7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 14px center;
  background-size: 16px;
  padding-right: 44px;
}

/* ── Buttons ── */
.btn-primary {
  height: 48px;
  padding: 0 28px;
  background: linear-gradient(135deg, var(--accent), #fb923c);
  color: #FFFFFF;
  border: none;
  border-radius: 14px;
  font-size: 14px;
  font-weight: 700;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  cursor: pointer;
  transition: all 0.3s;
  box-shadow: 0 8px 20px -6px rgba(249, 115, 22, 0.4);
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 24px -8px rgba(249, 115, 22, 0.5);
  filter: brightness(1.05);
}

.btn-primary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

.btn-outline {
  height: 44px;
  padding: 0 20px;
  background-color: var(--bg-primary);
  color: var(--text-secondary);
  border: 1px solid var(--border-color);
  border-radius: 12px;
  font-size: 13px;
  font-weight: 700;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-outline:hover {
  border-color: var(--accent);
  color: var(--accent);
  background: var(--accent-light);
}

.btn-outline.danger {
  border-color: rgba(239, 68, 68, 0.3);
  color: var(--danger, #ef4444);
}

.btn-outline.danger:hover {
  border-color: var(--danger, #ef4444);
  color: #fff;
  background: var(--danger, #ef4444);
}

.btn-ghost {
  height: 44px;
  padding: 0 20px;
  background: transparent;
  color: var(--text-muted);
  border: none;
  border-radius: 12px;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-ghost:hover {
  background-color: var(--bg-card-hover);
  color: var(--text-primary);
}

.icon-btn {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  border: 1px solid var(--border-color);
  background: var(--bg-primary);
  color: var(--text-muted);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
}

.icon-btn:hover {
  border-color: var(--accent);
  color: var(--accent);
}

.icon-btn.delete:hover {
  background-color: var(--danger-bg);
  border-color: var(--danger);
  color: var(--danger);
}

/* ── Upload Section ── */
.upload-grid {
  display: flex;
  align-items: center;
  gap: 32px;
}

.upload-box {
  width: 120px;
  height: 120px;
  border: 2.5px dashed var(--border-color);
  border-radius: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
  overflow: hidden;
  background-color: var(--bg-primary);
}

.upload-box:hover {
  border-color: var(--accent);
  background-color: var(--accent-light);
}

.upload-preview img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  padding: 12px;
}

.upload-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  color: var(--text-muted);
}

.upload-placeholder span {
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
}

.upload-hint {
  font-size: 12px;
  color: var(--text-muted);
  margin-top: 4px;
  line-height: 1.5;
}

/* ── Footer Actions ── */
.section-footer {
  border-top: 1px solid var(--border-color);
  padding-top: 24px;
  margin-top: 32px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
}

.footer-info {
  display: flex;
  align-items: center;
  gap: 8px;
  color: var(--text-muted);
  font-size: 13px;
  font-weight: 500;
}

/* ── Payment List ── */
.payment-table {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.payment-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 20px;
  background: var(--bg-primary);
  border: 1px solid var(--border-color);
  border-radius: 18px;
  transition: all 0.2s;
}

.payment-row:hover {
  border-color: var(--accent);
}

.payment-item-main {
  display: flex;
  align-items: center;
  gap: 16px;
}

.payment-type-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.payment-type-icon.cash { background-color: var(--success-bg); color: var(--success); }
.payment-type-icon.e-wallet { background-color: var(--info-bg); color: var(--info); }
.payment-type-icon.transfer { background-color: var(--info-bg); color: var(--info); }

.payment-item-info {
  display: flex;
  flex-direction: column;
}

.payment-item-name {
  font-size: 15px;
  font-weight: 700;
  color: var(--text-primary);
}

.payment-item-meta {
  font-size: 12px;
  color: var(--text-muted);
  font-weight: 500;
}

.payment-item-actions {
  display: flex;
  align-items: center;
  gap: 12px;
}

.status-chip {
  padding: 4px 12px;
  border-radius: 100px;
  font-size: 11px;
  font-weight: 700;
  background-color: var(--bg-card-hover);
  color: var(--text-muted);
}

.status-chip.active {
  background-color: var(--success-bg);
  color: var(--success);
}

/* ── Database Grid ── */
.db-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 24px;
  margin-bottom: 32px;
}

.db-card {
  display: flex;
  gap: 20px;
  padding: 24px;
  border: 1px solid var(--border-color);
  border-radius: 20px;
  background-color: var(--bg-primary);
  transition: all 0.2s;
}

.db-card:hover {
  border-color: var(--accent);
}

.db-card-icon {
  width: 54px;
  height: 54px;
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.db-card-icon.blue { background-color: var(--info-bg); color: var(--info); }
.db-card-icon.orange { background-color: var(--accent-light); color: var(--accent); }

.db-card-info h3 {
  font-size: 16px;
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: 6px;
}

.db-card-info p {
  font-size: 13px;
  color: var(--text-muted);
  margin-bottom: 20px;
  line-height: 1.6;
}

/* ── Printer (Redesigned) ── */

/* Status Row */
.ptr-status-row {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 20px 24px;
  background: var(--bg-primary);
  border: 1px solid var(--border-color);
  border-radius: 16px;
  margin-bottom: 16px;
}

.ptr-status-icon {
  width: 48px;
  height: 48px;
  border-radius: 14px;
  background: var(--bg-card-hover);
  border: 1.5px solid var(--border-color);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--text-muted);
  flex-shrink: 0;
}

.ptr-status-row.connected .ptr-status-icon {
  background: var(--success-bg);
  border-color: var(--success);
  color: var(--success);
}

.ptr-status-info h4 {
  font-size: 15px;
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: 2px;
}

.ptr-status-info p {
  font-size: 13px;
  color: var(--text-muted);
  font-weight: 500;
}

.ptr-status-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: var(--border-color);
  margin-left: auto;
  flex-shrink: 0;
}

.ptr-status-row.connected .ptr-status-dot {
  background: var(--success);
  box-shadow: 0 0 0 4px var(--success-bg);
}

/* Action Buttons */
.ptr-actions-row {
  display: flex;
  gap: 12px;
  margin-bottom: 32px;
}

.ptr-btn-scan {
  flex: 1;
  height: 50px;
  padding: 0 24px;
  background: linear-gradient(135deg, var(--accent), #fb923c);
  color: #fff;
  border: none;
  border-radius: 14px;
  font-size: 15px;
  font-weight: 700;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  transition: all 0.25s;
  box-shadow: 0 4px 16px rgba(249, 115, 22, 0.3);
}

.ptr-btn-scan:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 8px 24px rgba(249, 115, 22, 0.4);
}

.ptr-btn-scan:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.ptr-btn-test {
  height: 50px;
  padding: 0 20px;
  background: var(--bg-primary);
  color: var(--text-secondary);
  border: 1.5px solid var(--border-color);
  border-radius: 14px;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: all 0.2s;
  white-space: nowrap;
}

.ptr-btn-test:hover:not(:disabled) {
  border-color: var(--accent);
  color: var(--accent);
  background: var(--accent-light);
}

.ptr-btn-test:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.ptr-btn-disconnect {
  height: 50px;
  padding: 0 16px;
  background: var(--bg-primary);
  color: var(--danger, #ef4444);
  border: 1.5px solid rgba(239, 68, 68, 0.3);
  border-radius: 14px;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 6px;
  transition: all 0.2s;
  white-space: nowrap;
}

.ptr-btn-disconnect:hover {
  border-color: var(--danger, #ef4444);
  background: var(--danger, #ef4444);
  color: #fff;
}

/* Preferences Grid (Paper + AutoPrint side by side) */
.ptr-prefs-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 24px;
  margin-bottom: 24px;
}

.ptr-pref-block {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.ptr-pref-label {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 14px;
  font-weight: 700;
  color: var(--text-primary);
}

.label-info-icon {
  color: var(--text-muted);
  opacity: 0.6;
}

/* ── Printer Main Layout (Desktop Split) ── */
.printer-main-layout {
  display: grid;
  grid-template-columns: 1fr 320px;
  gap: 32px;
  align-items: start;
}

.printer-settings-flow {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.receipt-preview-sidebar {
  position: sticky;
  top: 32px;
}

@media (max-width: 1024px) {
  .printer-main-layout {
    grid-template-columns: 1fr;
    gap: 24px;
  }
  .receipt-preview-sidebar {
    order: -1; /* Preview on top for mobile */
    margin-bottom: 32px !important;
    position: relative !important; /* Force disable sticky on mobile */
    top: 0 !important;
  }
}


/* ── Receipt Preview ── */
.receipt-preview-container {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.preview-label {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  font-weight: 700;
  color: var(--text-muted);
}

.thermal-receipt {
  background: #fff;
  color: #000;
  padding: 24px 16px;
  box-shadow: 0 10px 30px rgba(0,0,0,0.1);
  border-radius: 4px;
  font-family: 'Courier New', Courier, monospace;
  font-size: 12px;
  line-height: 1.4;
  position: relative;
  overflow: hidden;
}

.dark .thermal-receipt {
  box-shadow: 0 10px 40px rgba(0,0,0,0.3);
}

.p-58mm { width: 100%; max-width: 240px; margin: 0 auto; }
.p-80mm { width: 100%; max-width: 300px; margin: 0 auto; }

.thermal-receipt::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 4px;
  background: repeating-linear-gradient(90deg, #ccc, #ccc 2px, transparent 2px, transparent 4px);
}

.r-header { text-align: center; margin-bottom: 12px; }
.r-logo { 
  display: flex; 
  justify-content: center; 
  margin-bottom: 12px; 
}
.receipt-logo { 
  max-width: 80px; 
  max-height: 80px; 
  object-fit: contain; 
  border-radius: 8px;
}
.r-shop-name { font-size: 16px; font-weight: 900; margin: 0 0 4px; text-transform: uppercase; }
.r-shop-addr, .r-shop-telp { font-size: 10px; margin: 0; opacity: 0.8; }

.r-divider { text-align: center; overflow: hidden; white-space: nowrap; margin: 8px 0; color: #555; }

.r-meta { display: flex; flex-direction: column; gap: 2px; font-size: 11px; }

.r-items { margin: 12px 0; }
.r-item { margin-bottom: 8px; }
.r-item-top { display: flex; justify-content: space-between; font-weight: 700; }
.r-item-bottom { font-size: 10px; }

.r-total-row { display: flex; justify-content: space-between; margin-bottom: 2px; }
.r-total-row.bold { font-weight: 900; font-size: 14px; margin: 4px 0; padding: 4px 0; border-top: 1px dashed #ccc; border-bottom: 1px dashed #ccc; }

.r-footer { text-align: center; margin-top: 20px; font-size: 11px; }
.r-footer p { margin: 2px 0; }
.powered-by { font-size: 9px; margin-top: 8px !important; opacity: 0.5; font-style: italic; }

.ptr-pref-label-bold {
  font-size: 16px;
  font-weight: 700;
  color: var(--text-primary);
}

/* Paper Size Cards */
.ptr-paper-cards {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.ptr-paper-card {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 20px 16px;
  border: 2px solid var(--border-color);
  border-radius: 16px;
  cursor: pointer;
  transition: all 0.25s;
  background: var(--bg-primary);
}

.ptr-paper-card:hover {
  border-color: rgba(249, 115, 22, 0.4);
}

.ptr-paper-card.active {
  border-color: var(--accent);
  background: rgba(249, 115, 22, 0.04);
}

.ptr-paper-size {
  font-size: 24px;
  font-weight: 800;
  color: var(--text-muted);
  line-height: 1;
  margin-bottom: 6px;
}

.ptr-paper-card.active .ptr-paper-size {
  color: var(--accent);
}

.ptr-paper-label {
  font-size: 10px;
  font-weight: 700;
  color: var(--text-muted);
  text-transform: uppercase;
  letter-spacing: 1.5px;
}

.ptr-paper-card.active .ptr-paper-label {
  color: var(--accent);
  opacity: 0.7;
}

.ptr-paper-check {
  position: absolute;
  bottom: 8px;
  right: 8px;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  background: var(--accent);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 2px 8px rgba(249, 115, 22, 0.3);
  animation: checkPop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes checkPop {
  from { transform: scale(0); }
  to { transform: scale(1); }
}

/* Auto Print Block */
.ptr-auto-content {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 24px;
  background: var(--bg-primary);
  border: 1px solid var(--border-color);
  border-radius: 16px;
  height: 100%;
}

.ptr-auto-info {
  flex: 1;
}

.ptr-auto-desc {
  font-size: 13px;
  color: var(--text-muted);
  margin-top: 6px;
  font-weight: 500;
  line-height: 1.5;
}

/* Tips Box */
.ptr-tips-box {
  padding: 20px 24px;
  background: var(--info-bg, rgba(59, 130, 246, 0.05));
  border: 1px solid var(--info, rgba(59, 130, 246, 0.2));
  border-radius: 16px;
  color: var(--info, #3b82f6);
}

.ptr-tips-header {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 14px;
}

.ptr-tips-header strong {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.8px;
}

.ptr-tips-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 8px 32px;
}

.ptr-tips-grid ul {
  margin: 0;
  padding-left: 18px;
}

.ptr-tips-grid li {
  font-size: 12px;
  font-weight: 500;
  margin-bottom: 6px;
  line-height: 1.5;
}

.alert-box strong { font-weight: 700; }

.help-content ul {
  margin: 10px 0 0;
  padding-left: 18px;
}

.help-content li { margin-bottom: 6px; }

/* ── Switch Slider ── */
.modern-switch {
  position: relative;
  display: inline-block;
  width: 48px;
  height: 26px;
  flex-shrink: 0;
}

.modern-switch input { opacity: 0; width: 0; height: 0; }

.switch-slider {
  position: absolute;
  cursor: pointer;
  inset: 0;
  background-color: var(--border-color);
  transition: .4s;
  border-radius: 34px;
}

.switch-slider:before {
  position: absolute;
  content: "";
  height: 20px;
  width: 20px;
  left: 3px;
  bottom: 3px;
  background-color: white;
  transition: .4s;
  border-radius: 50%;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

input:checked + .switch-slider { background-color: var(--success); }
input:checked + .switch-slider:before { transform: translateX(22px); }

/* ── Modal (SaaS Style) ── */
.modal-overlay {
  position: fixed;
  inset: 0;
  background-color: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
}

.modal-container {
  background: var(--bg-card);
  width: 100%;
  max-width: 520px;
  border-radius: 24px;
  box-shadow: var(--shadow-lg);
  display: flex;
  flex-direction: column;
  max-height: 90vh;
  border: 1px solid var(--border-color);
  animation: slideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

.modal-header {
  padding: 24px;
  border-bottom: 1px solid var(--border-color);
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.modal-header-content {
  display: flex;
  align-items: center;
  gap: 16px;
  width: 100%;
}

.code-area {
  font-family: 'Courier New', Courier, monospace;
  font-size: 12px;
  background: var(--bg-primary);
  color: var(--text-primary);
  line-height: 1.5;
  border: 1.5px solid var(--border-color);
}

.gs-config-card {
  padding: clamp(20px, 5vw, 40px);
  background: var(--bg-primary);
  border: 1px solid var(--border-color);
  border-radius: 28px;
  display: flex;
  flex-direction: column;
  gap: 32px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
}

.help-link {
  display: inline-block;
  margin-top: 10px;
  font-weight: 700;
  color: var(--accent);
  text-decoration: underline;
}

.gs-sync-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
  padding: 24px;
  background: var(--bg-card-hover);
  border: 1.5px dashed var(--border-color);
  border-radius: 20px;
  transition: all 0.3s;
}

.gs-sync-row:hover {
  border-color: var(--accent);
  background: var(--accent-light);
}

.field-label-bold {
  font-size: 15px;
  font-weight: 800;
  color: var(--text-primary);
  display: block;
}

.pref-desc {
  font-size: 13px;
  color: var(--text-muted);
  margin-top: 6px;
  font-weight: 500;
}

.field-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  margin-bottom: 14px;
  flex-wrap: wrap;
  gap: 12px;
}

.field-header .field-label {
  margin-bottom: 0;
  font-size: 11px;
  letter-spacing: 0.5px;
  font-weight: 800;
  text-transform: uppercase;
  color: var(--text-muted);
}

.field-hint {
  font-size: 11px;
  color: var(--text-muted);
  font-weight: 600;
  background: var(--bg-card-hover);
  padding: 5px 12px;
  border-radius: 10px;
  border: 1px solid var(--border-color);
}

.gs-action-box {
  display: flex;
  gap: 16px;
  padding: 0 4px;
}

.header-actions-inline {
  display: flex;
  gap: 12px;
}

.btn-text-action {
  background: none;
  border: none;
  color: var(--accent);
  font-size: 11px;
  font-weight: 700;
  padding: 0;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 4px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.btn-text-action:hover {
  text-decoration: underline;
}

.btn-text-action.danger {
  color: var(--danger);
}

.field-info-text {
  font-size: 11px;
  color: var(--text-muted);
  margin-top: 8px;
  font-style: italic;
}

/* GS Tips Section */
.gs-tips-section {
  padding-top: 8px;
}

.tips-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 20px;
}

.tips-icon-wrap {
  width: 32px;
  height: 32px;
  background: var(--accent-light);
  color: var(--accent);
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.tips-header strong {
  font-size: 11px;
  font-weight: 800;
  color: var(--text-secondary);
  text-transform: uppercase;
  letter-spacing: 1px;
}

.tips-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.tips-list li {
  font-size: 13px;
  color: var(--text-secondary);
  padding-left: 20px;
  position: relative;
  line-height: 1.5;
}

.tips-list li::before {
  content: "";
  position: absolute;
  left: 0;
  top: 8px;
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: var(--accent);
  opacity: 0.6;
}

.tips-footer {
  margin-top: 24px;
  padding-top: 20px;
  border-top: 1px solid var(--border-color);
}

.help-link-btn {
  background: transparent;
  border: none;
  color: var(--accent);
  font-size: 13px;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  padding: 0;
  transition: all 0.2s;
}

.help-link-btn:hover {
  text-decoration: underline;
  opacity: 0.8;
}

.modal-icon-wrap {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: rgba(249,115,22,0.1);
  color: var(--accent);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.modal-title-area {
  flex: 1;
}

.modal-title {
  font-size: 18px;
  font-weight: 600;
  color: var(--text-primary);
}

.modal-subtitle {
  font-size: 13px;
  color: var(--text-muted);
}

.btn-back-header {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  border: none;
  background: var(--bg-primary);
  color: var(--text-muted);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
  position: relative;
  z-index: 10;
}

.btn-back-header:hover {
  background-color: var(--bg-card-hover);
  color: var(--text-primary);
}

.modal-body {
  padding: 28px;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.modal-footer {
  padding: 20px 28px 28px;
  border-top: 1px solid var(--border-color);
  display: flex;
  justify-content: center;
}

.modal-footer button {
  width: 100%;
  padding: 12px 32px;
}

/* Radio Group */
.modern-radio-group {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 10px;
}

.radio-btn {
  padding: 12px;
  border: 1.5px solid var(--border-color);
  border-radius: 14px;
  background: var(--bg-primary);
  color: var(--text-muted);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  transition: all 0.2s;
}

.radio-btn span { font-size: 12px; font-weight: 700; }

.radio-btn:hover {
  border-color: var(--accent);
  background-color: var(--accent-light);
}

.radio-btn.active {
  border-color: var(--accent);
  background-color: var(--accent-light);
  color: var(--accent);
}

/* ── Transitions & Animations ── */
.fade-fast-enter-active, .fade-fast-leave-active { transition: opacity 0.2s ease; }
.fade-fast-enter-from, .fade-fast-leave-to { opacity: 0; }

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes slideUp {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}

.spin { animation: spin 1s linear infinite; }
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

/* ── Mobile Responsive ── */
@media (max-width: 768px) {
  .settings-page { padding: 4px; }
  
  .page-hero {
    flex-direction: column;
    text-align: center;
    padding: 24px;
    gap: 16px;
    border-radius: 20px;
  }
  .hero-content { flex-direction: column; text-align: center; }
  .hero-icon-wrap { width: 48px; height: 48px; border-radius: 14px; }
  .hero-title { font-size: 20px; }
  .hero-subtitle { font-size: 13px; }

  .settings-container { padding: 0; }
  
  .settings-tabs-nav { border-radius: 16px; margin-bottom: 20px; }
  .tab-pill { padding: 8px 16px; font-size: 13px; }

  .settings-section { padding: 20px; border-radius: 20px; margin-bottom: 16px; }
  .section-header, .section-header-flex { margin-bottom: 24px; }
  .section-title { font-size: 18px; }

  .form-grid { grid-template-columns: 1fr; gap: 20px; }
  .field-group.full-width { grid-column: span 1; }
  
  .upload-grid { flex-direction: column; align-items: stretch; gap: 24px; }
  .upload-box { width: 100px; height: 100px; margin: 0 auto; }
  .upload-hint { text-align: center; }

  .db-grid { grid-template-columns: 1fr; }
  .db-card { padding: 20px; flex-direction: column; align-items: center; text-align: center; }
  .db-card-info button { width: 100%; }
  
  .section-footer {
    flex-direction: column-reverse;
    align-items: stretch;
    margin-top: 24px;
    padding-top: 20px;
  }
  
  .btn-primary { width: 100%; height: 50px; }
  .footer-info { justify-content: center; }
  
  /* Printer mobile */
  .ptr-actions-row { flex-direction: column; }
  .ptr-btn-scan { font-size: 14px; }
  .ptr-prefs-grid { grid-template-columns: 1fr; }
  .ptr-tips-grid { grid-template-columns: 1fr; }
  .ptr-paper-card { padding: 16px 12px; }
  .ptr-paper-size { font-size: 20px; }
  
  .modern-radio-group { grid-template-columns: 1fr; }
  
  .section-header-flex {
    flex-direction: column;
    align-items: stretch;
  }
  .section-header-flex .btn-primary { width: 100%; margin-top: 12px; }

  .modal-overlay { 
    align-items: flex-end; 
    padding: 0; 
    background: rgba(0,0,0,0.35);
    backdrop-filter: blur(2px);
  }
  .modal-container { 
    max-width: 100%; 
    border-radius: 24px 24px 0 0; 
    margin: 0;
  }

  /* Google Sheets Mobile */
  .gs-config-card { padding: 20px; gap: 24px; }
  .gs-sync-row { flex-direction: column; align-items: stretch; text-align: center; }
  .gs-action-box { flex-direction: column; }
  .gs-action-box button { width: 100%; }
  
  .field-header { align-items: flex-start; }
  .field-header .field-label { margin-bottom: 4px; }
  .field-hint { width: 100%; text-align: center; }
  
  .tips-header { justify-content: center; }
  .tips-list li { text-align: left; }
  .help-link-btn { justify-content: center; width: 100%; }
}


/* ─────────────────────────────────────────────────────────────────────────────
   PREMIUM PRINTER UI STYLES (Hybrid Path)
   ───────────────────────────────────────────────────────────────────────────── */

.printer-section-premium {
  --ptr-accent: #f97316;
  --ptr-accent-glow: rgba(249, 115, 22, 0.2);
  --ptr-success: #10b981;
  --ptr-bg: var(--bg-card);
  --ptr-border: var(--border-color);
}

.header-with-badge {
  display: flex;
  align-items: center;
  gap: 16px;
  flex-wrap: wrap;
  margin-bottom: 8px;
}

.path-badge {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 6px 14px;
  border-radius: 100px;
  font-size: 11px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  border: 1px solid transparent;
}

.path-badge.web {
  background: rgba(59, 130, 246, 0.1);
  color: #3b82f6;
  border-color: rgba(59, 130, 246, 0.2);
}

.path-badge.native {
  background: rgba(16, 185, 129, 0.1);
  color: #10b981;
  border-color: rgba(16, 185, 129, 0.2);
}

.badge-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: currentColor;
  animation: pulse-dot 2s infinite;
}

@keyframes pulse-dot {
  0% { opacity: 1; transform: scale(1); }
  50% { opacity: 0.4; transform: scale(0.8); }
  100% { opacity: 1; transform: scale(1); }
}

/* Premium Status Row (Glassmorphism) */
.ptr-status-row-premium {
  position: relative;
  display: flex;
  align-items: center;
  gap: 24px;
  padding: 32px;
  border-radius: 28px;
  background: linear-gradient(135deg, var(--bg-card) 0%, var(--bg-primary) 100%);
  border: 1px solid var(--ptr-border);
  margin-bottom: 32px;
  overflow: hidden;
  transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
  box-shadow: 0 4px 20px rgba(0,0,0,0.03);
}

.ptr-status-row-premium.connected {
  border-color: var(--ptr-success);
  box-shadow: 0 10px 30px rgba(16, 185, 129, 0.08);
}

.ptr-visual {
  position: relative;
  display: flex;
  align-items: center;
}

.ptr-icon-ring {
  width: 64px;
  height: 64px;
  border-radius: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--bg-primary);
  border: 1px solid var(--ptr-border);
  color: var(--text-muted);
  transition: all 0.4s;
}

.connected .ptr-icon-ring {
  background: var(--ptr-success);
  color: white;
  border-color: var(--ptr-success);
  box-shadow: 0 0 20px rgba(16, 185, 129, 0.3);
}

.ptr-info-main {
  flex: 1;
}

.ptr-status-tag {
  font-size: 10px;
  font-weight: 800;
  letter-spacing: 1px;
  color: var(--text-muted);
  margin-bottom: 6px;
}

.connected .ptr-status-tag {
  color: var(--ptr-success);
}

.ptr-device-name {
  font-size: 20px;
  font-weight: 800;
  color: var(--text-primary);
  margin: 0 0 4px 0;
}

.ptr-device-meta {
  font-size: 13px;
  color: var(--text-muted);
  font-weight: 500;
}

.ptr-status-indicator {
  position: relative;
  width: 12px;
  height: 12px;
}

.status-dot {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  background: #cbd5e1;
}

.connected .status-dot {
  background: var(--ptr-success);
}

.pulse-ring {
  position: absolute;
  top: 0; left: 0; width: 100%; height: 100%;
  border-radius: 50%;
  background: var(--ptr-success);
  animation: pulse-ring 2s infinite;
}

@keyframes pulse-ring {
  0% { transform: scale(1); opacity: 0.5; }
  100% { transform: scale(3.5); opacity: 0; }
}

/* Premium Actions */
.ptr-actions-premium {
  display: flex;
  align-items: center;
  gap: 20px;
  margin-bottom: 40px;
}

.btn-ptr-scan {
  position: relative;
  height: 56px;
  padding: 0 32px;
  border: none;
  border-radius: 16px;
  background: #1e293b;
  color: white;
  font-weight: 700;
  cursor: pointer;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s;
  box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}

.btn-ptr-scan:hover {
  transform: translateY(-2px);
  box-shadow: 0 15px 25px rgba(0,0,0,0.15);
  background: #0f172a;
}

.btn-ptr-scan.disconnect-mode {
  background: rgba(239, 68, 68, 0.05);
  border: 1.5px solid rgba(239, 68, 68, 0.2);
  color: #ef4444;
  box-shadow: none;
}

.btn-ptr-scan.disconnect-mode:hover {
  background: rgba(239, 68, 68, 0.1);
  border-color: rgba(239, 68, 68, 0.4);
  color: #dc2626;
  box-shadow: 0 8px 16px rgba(239, 68, 68, 0.1);
}

.btn-content {
  display: flex;
  align-items: center;
  gap: 12px;
  z-index: 2;
}

.btn-shimmer {
  position: absolute;
  top: 0; left: -100%; width: 100%; height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
  animation: shimmer 3s infinite;
}

@keyframes shimmer {
  to { left: 100%; }
}

.btn-group-secondary {
  display: flex;
  gap: 12px;
}

.btn-ptr-outline {
  height: 56px;
  padding: 0 24px;
  border: 1px solid var(--ptr-border);
  border-radius: 16px;
  background: var(--bg-card);
  color: var(--text-primary);
  font-weight: 700;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 10px;
  transition: all 0.2s;
}

.btn-ptr-outline:hover:not(:disabled) {
  background: var(--bg-card-hover);
  border-color: var(--text-muted);
}

.btn-ptr-outline:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.btn-ptr-outline.danger:hover {
  border-color: #ef4444;
  color: #ef4444;
  background: rgba(239, 68, 68, 0.05);
}

/* Preferences Cards */
.ptr-prefs-premium {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
  margin-bottom: 40px;
}

.pref-card-premium {
  padding: 24px;
  border-radius: 24px;
  background: var(--bg-primary);
  border: 1px solid var(--ptr-border);
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.pref-card-header {
  display: flex;
  align-items: center;
  gap: 12px;
}

.pref-icon {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: var(--bg-card);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--ptr-accent);
}

.pref-card-header label {
  font-size: 14px;
  font-weight: 800;
  color: var(--text-secondary);
}

/* Paper Toggle */
.paper-toggle-group {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
}

.paper-btn {
  position: relative;
  padding: 16px;
  border-radius: 16px;
  border: 1px solid var(--ptr-border);
  background: var(--bg-card);
  cursor: pointer;
  display: flex;
  flex-direction: column;
  align-items: center;
  transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.paper-btn.active {
  border-color: var(--ptr-accent);
  background: rgba(249, 115, 22, 0.04);
  transform: scale(1.02);
}

.p-val {
  font-size: 20px;
  font-weight: 800;
  color: var(--text-primary);
}

.p-val sub { font-size: 10px; margin-left: 2px; }

.p-lab {
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  color: var(--text-muted);
}

.p-check {
  position: absolute;
  top: 8px; right: 8px;
  color: var(--ptr-accent);
}

/* Auto Print Content */
.auto-print-content {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.auto-print-info strong {
  display: block;
  font-size: 15px;
  color: var(--text-primary);
}

.auto-print-info p {
  font-size: 12px;
  color: var(--text-muted);
  margin-top: 4px;
}

/* Premium Switch */
.premium-switch {
  position: relative;
  display: inline-block;
  width: 52px;
  height: 28px;
}

.premium-switch input { opacity: 0; width: 0; height: 0; }

.switch-slider {
  position: absolute;
  cursor: pointer;
  inset: 0;
  background-color: #e2e8f0;
  transition: .4s;
  border-radius: 34px;
}

.switch-slider:before {
  position: absolute;
  content: "";
  height: 22px; width: 22px;
  left: 3px; bottom: 3px;
  background-color: white;
  transition: .4s;
  border-radius: 50%;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

input:checked + .switch-slider { background-color: var(--ptr-success); }
input:checked + .switch-slider:before { transform: translateX(24px); }

/* Troubleshooting Glass */
.troubleshoot-glass {
  padding: 32px;
  border-radius: 28px;
  background: rgba(255,255,255,0.02);
  border: 1px solid var(--ptr-border);
  backdrop-filter: blur(10px);
}

.ts-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 24px;
  color: var(--text-secondary);
  font-weight: 800;
  text-transform: uppercase;
  font-size: 12px;
  letter-spacing: 1px;
}

.ts-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 24px;
}

.ts-item strong {
  display: block;
  font-size: 14px;
  color: var(--text-primary);
  margin-bottom: 6px;
}

.ts-item p {
  font-size: 13px;
  color: var(--text-muted);
  line-height: 1.6;
}

@media (max-width: 768px) {
  .header-with-badge { flex-direction: column; align-items: flex-start; }
  .ptr-status-row-premium { padding: 20px; flex-direction: column; text-align: center; }
  .ptr-actions-premium { flex-direction: column; align-items: stretch; }
  .btn-group-secondary { flex-direction: column; }
  .ptr-prefs-premium { grid-template-columns: 1fr; }
  .ts-grid { grid-template-columns: 1fr; }
}
/* ── Billing & Subscription Styles ── */
.billing-content {
  padding: 32px;
}

.current-plan-card {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 32px;
  border-radius: 24px;
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  margin-bottom: 40px;
  gap: 40px;
}

.current-plan-card.free { border-left: 6px solid #94a3b8; }
.current-plan-card.basic { border-left: 6px solid var(--accent); }
.current-plan-card.pro { border-left: 6px solid #a855f7; }

.plan-main {
  display: flex;
  align-items: center;
  gap: 20px;
}

.plan-icon {
  width: 54px;
  height: 54px;
  border-radius: 16px;
  background: var(--accent-light);
  color: var(--accent);
  display: flex;
  align-items: center;
  justify-content: center;
}

.plan-tag {
  font-size: 10px;
  font-weight: 800;
  letter-spacing: 1px;
  color: var(--text-muted);
  display: block;
  margin-bottom: 4px;
}

.plan-text h3 {
  font-size: 20px;
  font-weight: 800;
  color: var(--text-primary);
  margin: 0;
}

.plan-text p {
  font-size: 13px;
  color: var(--text-muted);
  margin-top: 4px;
}

.plan-limits {
  flex: 1;
  max-width: 300px;
}

.limit-info {
  display: flex;
  justify-content: space-between;
  font-size: 13px;
  font-weight: 700;
  margin-bottom: 8px;
}

.limit-bar {
  height: 8px;
  background: var(--bg-primary);
  border-radius: 10px;
  overflow: hidden;
}

.limit-fill {
  height: 100%;
  background: var(--accent);
  border-radius: 10px;
  transition: width 0.5s ease;
}

.pricing-matrix {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 24px;
}

.price-tier {
  padding: 32px;
  border-radius: 24px;
  background: var(--bg-primary);
  border: 1px solid var(--border-color);
  display: flex;
  flex-direction: column;
  transition: all 0.3s;
  position: relative;
}

.price-tier.featured {
  border-color: var(--accent);
  box-shadow: 0 20px 40px rgba(249, 115, 22, 0.08);
}

.price-tier.current {
  background: var(--bg-card-hover);
}

.popular-ribbon {
  position: absolute;
  top: 12px;
  right: 12px;
  background: var(--accent);
  color: white;
  font-size: 10px;
  font-weight: 800;
  padding: 4px 10px;
  border-radius: 20px;
}

.tier-head h4 {
  font-size: 12px;
  font-weight: 800;
  color: var(--text-muted);
  margin-bottom: 12px;
}

.tier-price {
  font-size: 28px;
  font-weight: 800;
  color: var(--text-primary);
  margin-bottom: 24px;
}

.tier-price span {
  font-size: 14px;
  color: var(--text-muted);
  font-weight: 500;
}

.tier-features {
  list-style: none;
  padding: 0;
  margin: 0 0 32px 0;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.tier-features li {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 13px;
  color: var(--text-secondary);
  font-weight: 500;
}

.tier-features li.disabled {
  opacity: 0.4;
  text-decoration: line-through;
}

.tier-features li svg { color: var(--success); }
.tier-features li.disabled svg { color: var(--text-muted); }

.btn-tier {
  width: 100%;
  padding: 14px;
  border-radius: 14px;
  border: 1.5px solid var(--border-color);
  background: white;
  color: var(--text-primary);
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
  margin-top: auto;
}

.btn-tier.primary {
  background: var(--accent);
  border-color: var(--accent);
  color: white;
}

.btn-tier:hover:not(:disabled) {
  transform: translateY(-2px);
  border-color: var(--accent);
  color: var(--accent);
}

.btn-tier.primary:hover {
  background: #ea580c;
  color: white;
}

@media (max-width: 1024px) {
  .pricing-matrix { grid-template-columns: 1fr; }
  .current-plan-card { flex-direction: column; align-items: flex-start; gap: 24px; }
  .plan-limits { max-width: 100%; }
}

</style>


