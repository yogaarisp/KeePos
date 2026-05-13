# 💰 Cash Drawer Implementation

## 📋 Overview

Fitur **Cash Drawer** (Laci Kasir) sudah diimplementasikan untuk membuka laci kasir otomatis saat transaksi pembayaran berhasil.

## ✅ Fitur yang Sudah Diimplementasikan

### 1. ESC/POS Command untuk Cash Drawer

**File:** `frontend/src/services/printer.js`

Menambahkan method `openCashDrawer()`:
```javascript
async openCashDrawer(pin = 0) {
    // ESC p m t1 t2
    // 0x1B 0x70 0x00 0x19 0xFA (pin 2, 25ms pulse, 250ms wait)
    const command = new Uint8Array([
        0x1B, 0x70, pin, 0x19, 0xFA
    ]);
    await this.write(command);
}
```

**ESC/POS Command Explanation:**
- `0x1B 0x70` = ESC p (Open cash drawer command)
- `pin` = 0 (pin 2) atau 1 (pin 5) - Most cash drawers use pin 2
- `0x19` = 25ms pulse ON time
- `0xFA` = 250ms pulse OFF time

### 2. Integrasi dengan Print Receipt

Method `printReceipt()` sekarang menerima parameter `openDrawer`:
```javascript
async printReceipt(receiptData, width = 58, openDrawer = false) {
    let commands = [0x1b, 0x40, 0x1b, 0x33, 0x20];
    
    // Open cash drawer FIRST if requested
    if (openDrawer) {
        commands.push(0x1B, 0x70, 0x00, 0x19, 0xFA);
    }
    
    // ... rest of receipt printing
}
```

**⚠️ PENTING:** Cash drawer dibuka **SEBELUM** print struk, bukan setelah!

### 3. Setting Store

**File:** `frontend/src/stores/setting.js`

Menambahkan state `openCashDrawer`:
```javascript
state: () => ({
    openCashDrawer: localStorage.getItem('open_cash_drawer') === 'true',
    // ...
})
```

Method `setPrinterPreference()` sudah support `openCashDrawer`.

### 4. UI Settings

**File:** `frontend/src/views/Settings.vue`

Menambahkan toggle switch di tab Printer:
```vue
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
      <input type="checkbox" :checked="settingStore.openCashDrawer" 
             @change="e => settingStore.setPrinterPreference('openCashDrawer', e.target.checked)">
      <span class="switch-slider"></span>
    </label>
  </div>
</div>
```

### 5. POS Checkout Flow

**File:** `frontend/src/views/POS.vue`

Saat checkout berhasil dan auto-print aktif:
```javascript
await printer.printReceipt(
    receiptData, 
    parseInt(settingStore.paperSize) || 58, 
    settingStore.openCashDrawer  // ← Pass setting
);

await showSuccess({
    title: 'Struk Sudah Keluar!',
    text: settingStore.openCashDrawer 
        ? 'Laci kasir terbuka. Silakan ambil struk dari printer.' 
        : 'Silakan ambil struk dari printer.',
    timer: 1500
});
```

### 6. Reprint Receipt

**TIDAK** membuka laci saat reprint:
```javascript
// POS.vue - reprintReceipt()
await printer.printReceipt(receiptData, paperSize, false); // ← false = don't open drawer

// Orders.vue - reprintReceipt()
await printer.printReceipt(receiptData, paperSize, false); // ← false = don't open drawer
```

## 🎯 Flow Diagram

```
┌─────────────────────────────────────────────────────┐
│              CHECKOUT BERHASIL                      │
└─────────────────┬───────────────────────────────────┘
                  │
                  ▼
         ┌────────────────────┐
         │ Auto Print Aktif?  │
         └────────┬───────────┘
                  │ YES
                  ▼
         ┌────────────────────┐
         │ Printer Connected? │
         └────────┬───────────┘
                  │ YES
                  ▼
         ┌────────────────────────┐
         │ Open Drawer Setting?   │
         └────────┬───────────────┘
                  │ YES
                  ▼
    ┌─────────────────────────────────┐
    │  1. BUKA LACI KASIR (ESC p)     │ ← FIRST!
    │  2. PRINT STRUK                 │
    │  3. CUT PAPER                   │
    └─────────────────────────────────┘
                  │
                  ▼
         ┌────────────────────┐
         │ Show Success Alert │
         │ "Laci kasir terbuka"│
         └────────────────────┘
```

## 🔧 Cara Menggunakan

### 1. Aktifkan Fitur

1. Buka **Pengaturan** → Tab **Printer**
2. Scroll ke bawah ke section **"Laci Kasir"**
3. Toggle **"Buka Laci Otomatis"** → ON
4. Pastikan **"Cetak Otomatis"** juga ON

### 2. Test Cash Drawer

Di halaman POS:
1. Tambahkan produk ke cart
2. Klik **Bayar**
3. Pilih metode pembayaran
4. Klik **Proses Pembayaran**
5. Laci kasir akan terbuka **SEBELUM** struk keluar

### 3. Troubleshooting

**Laci tidak terbuka?**

1. **Check Hardware:**
   - Pastikan cash drawer terhubung ke printer (RJ11/RJ12 cable)
   - Pastikan cash drawer compatible dengan ESC/POS command
   - Test manual: Beberapa printer punya tombol test di bawah

2. **Check Setting:**
   - Pastikan toggle "Buka Laci Otomatis" ON
   - Pastikan "Cetak Otomatis" juga ON
   - Check localStorage: `localStorage.getItem('open_cash_drawer')` harus `'true'`

3. **Check Printer:**
   - Beberapa printer butuh pin 5 instead of pin 2
   - Edit `printer.js` line 207: ubah `0x00` jadi `0x01`
   ```javascript
   commands.push(0x1B, 0x70, 0x01, 0x19, 0xFA); // pin 5
   ```

4. **Check Console:**
   - Buka DevTools (F12) → Console
   - Look for errors saat checkout
   - Check: `Cash drawer opened (pin 2)` message

## 📝 Technical Details

### ESC/POS Cash Drawer Command

**Format:** `ESC p m t1 t2`

| Byte | Hex  | Decimal | Description |
|------|------|---------|-------------|
| ESC  | 0x1B | 27      | Escape character |
| p    | 0x70 | 112     | Cash drawer command |
| m    | 0x00 | 0       | Pin 2 (or 0x01 for pin 5) |
| t1   | 0x19 | 25      | Pulse ON time (25ms) |
| t2   | 0xFA | 250     | Pulse OFF time (250ms) |

**Common Variations:**
- Pin 2: `0x1B 0x70 0x00 0x19 0xFA` (Most common)
- Pin 5: `0x1B 0x70 0x01 0x19 0xFA` (Alternative)
- Short pulse: `0x1B 0x70 0x00 0x0A 0x0A` (10ms/10ms)
- Long pulse: `0x1B 0x70 0x00 0x32 0xFF` (50ms/255ms)

### Timing

**Kenapa buka laci DULU baru print?**

1. ✅ **User Experience:** Kasir bisa ambil uang sambil struk keluar
2. ✅ **Hardware:** Beberapa printer delay saat buka laci
3. ✅ **Safety:** Jika print gagal, laci sudah terbuka (customer sudah bayar)
4. ❌ **Jika sebaliknya:** Print dulu baru buka laci = kasir tunggu struk selesai baru bisa ambil uang

### Reprint Behavior

**Kenapa reprint TIDAK buka laci?**

1. ✅ Reprint = struk duplikat, bukan transaksi baru
2. ✅ Uang sudah diambil saat transaksi pertama
3. ✅ Mencegah buka laci tidak perlu
4. ✅ Security: Hanya transaksi baru yang buka laci

## 🎨 UI/UX

### Success Message

**Saat laci terbuka:**
```
✅ Struk Sudah Keluar!
Laci kasir terbuka. Silakan ambil struk dari printer.
```

**Saat laci tidak aktif:**
```
✅ Struk Sudah Keluar!
Silakan ambil struk dari printer.
```

### Settings UI

```
┌─────────────────────────────────────────┐
│  💵 Laci Kasir                          │
│  ┌───────────────────────────────────┐  │
│  │ Buka Laci Otomatis            [ON]│  │
│  │ Buka laci kasir saat cetak struk  │  │
│  │ pembayaran                         │  │
│  └───────────────────────────────────┘  │
└─────────────────────────────────────────┘
```

## 🔐 Security Considerations

1. **Hanya saat checkout:** Laci hanya buka saat transaksi baru, bukan reprint
2. **Require printer:** Laci hanya buka jika printer connected
3. **User control:** User bisa disable fitur via settings
4. **Audit trail:** Transaction log tetap tercatat di database

## 📊 Compatibility

**Tested with:**
- ✅ ESC/POS thermal printers (58mm & 80mm)
- ✅ Bluetooth printers with cash drawer port
- ✅ Standard RJ11/RJ12 cash drawers

**Requirements:**
- Printer must support ESC/POS commands
- Cash drawer must be connected to printer (not standalone)
- Printer must have cash drawer port (RJ11/RJ12)

## 🚀 Future Enhancements

Possible improvements:
1. **Pin selection:** Let user choose pin 2 or pin 5 in settings
2. **Pulse timing:** Adjustable pulse duration
3. **Test button:** "Test Cash Drawer" button in settings
4. **Standalone drawer:** Support USB cash drawers (not via printer)
5. **Audit log:** Log setiap kali laci dibuka

## ✅ Checklist Implementation

- [x] Add `openCashDrawer()` method to printer service
- [x] Integrate with `printReceipt()` method
- [x] Add `openCashDrawer` state to settings store
- [x] Add UI toggle in Settings page
- [x] Update POS checkout flow
- [x] Update success message
- [x] Prevent drawer open on reprint
- [x] Add documentation

## 📞 Support

Jika ada masalah:
1. Check hardware connection (printer ↔ cash drawer)
2. Check settings (toggle ON)
3. Check console for errors
4. Try different pin (0x00 vs 0x01)
5. Test with manual command

---

**Status:** ✅ IMPLEMENTED & READY TO USE

**Last Updated:** 2026-03-13
