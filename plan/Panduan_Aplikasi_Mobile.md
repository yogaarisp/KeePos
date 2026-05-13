# Panduan Aplikasi Mobile (Android & iOS) - Wartegkee POS

## ✅ Status: Capacitor Sudah Terinstall & Terkonfigurasi

Proyek ini sudah dikonfigurasi dengan **Capacitor.js** untuk menghasilkan aplikasi Android (.apk/.aab) dan iOS (.ipa).

---

## Struktur Proyek Mobile

```
frontend/
├── android/                  ← Proyek Android (siap buka di Android Studio)
│   ├── app/
│   │   ├── src/main/assets/  ← Isi web app (hasil build)
│   │   └── build.gradle
│   └── ...
├── capacitor.config.json     ← Konfigurasi Capacitor
├── src/
│   └── utils/native.js       ← Integrasi fitur native (StatusBar, Keyboard, dll)
└── dist/                     ← Hasil build Vue (web assets)
```

---

## Fitur Native yang Sudah Terintegrasi

| Fitur | Plugin | Fungsi |
| :--- | :--- | :--- |
| **Splash Screen** | `@capacitor/splash-screen` | Tampilan loading saat buka app |
| **Status Bar** | `@capacitor/status-bar` | Warna bar atas sesuai tema dark |
| **Keyboard** | `@capacitor/keyboard` | Sembunyikan bottom bar saat keyboard tampil |
| **Back Button** | `@capacitor/app` | Tombol back Android berfungsi navigasi |
| **Haptic Feedback** | `@capacitor/haptics` | Getaran halus saat tap (opsional) |
| **Safe Area** | CSS `env()` | Hindari notch/home bar iPhone & Android |

---

## Cara Build APK (Android)

### Prasyarat:
- [Android Studio](https://developer.android.com/studio) sudah terinstall
- Java JDK 17+ sudah terinstall
- Android SDK sudah terkonfigurasi

### Langkah:

1. **Build & Sync** (dari terminal di folder `frontend`):
   ```bash
   npm run build
   npx cap sync android
   ```

2. **Buka di Android Studio**:
   ```bash
   npx cap open android
   ```
   Ini akan membuka folder `android/` di Android Studio.

3. **Generate APK** (di Android Studio):
   - Menu: `Build > Build Bundle(s)/APK(s) > Build APK(s)`
   - File `.apk` siap install di HP Android

4. **Generate AAB** (untuk Play Store):
   - Menu: `Build > Generate Signed Bundle/APK`
   - Pilih "Android App Bundle"
   - Ikuti wizard untuk menandatangani dengan keystore

---

## Cara Build IPA (iOS)

> ⚠️ **Membutuhkan Mac** dengan Xcode terinstall.

1. Install platform iOS:
   ```bash
   npm install @capacitor/ios
   npx cap add ios
   ```

2. Build & Sync:
   ```bash
   npm run build
   npx cap sync ios
   ```

3. Buka di Xcode:
   ```bash
   npx cap open ios
   ```

4. Di Xcode, pilih device target lalu `Product > Archive`.

---

## Perintah Sehari-hari

| Perintah | Fungsi |
| :--- | :--- |
| `npm run build` | Build frontend Vue |
| `npx cap sync android` | Sync perubahan ke proyek Android |
| `npx cap open android` | Buka proyek di Android Studio |
| `npx cap run android` | Build & install langsung ke HP (USB) |

---

## Biaya Publish ke Store

| Store | Biaya | Keterangan |
| :--- | :--- | :--- |
| **Google Play Store** | $25 (sekali) | Akun developer seumur hidup |
| **Apple App Store** | $99 (tahunan) | Butuh Mac untuk build |

---

## Catatan Penting

> [!IMPORTANT]
> Setiap kali Anda mengubah kode Vue (frontend), jalankan:
> ```bash
> npm run build
> npx cap sync android
> ```
> Baru kemudian build ulang APK dari Android Studio.

> [!TIP]
> Untuk testing cepat tanpa Android Studio, gunakan:
> ```bash
> npx cap run android --target=DEVICE_ID
> ```
> Ini langsung install ke HP yang terhubung via USB debugging.
