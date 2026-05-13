/**
 * Thermal Printer Service — Hybrid (Web Bluetooth + Android Native Bridge)
 * Based on Thermal_printer_guide.md and System Documentation
 * 
 * Features:
 * - Dual-Path: Web Bluetooth API (Browser) & Android Interface Bridge (APK)
 * - Anti-Lag: 512 bytes chunking with 50ms delay
 * - Auto-Reconnect: Cooling down 500ms + Retry strategy
 * - Manual space padding for 58mm centering
 */

const SERVICE_UUID = '000018f0-0000-1000-8000-00805f9b34fb';
const CHARACTERISTIC_UUID = '00002af1-0000-1000-8000-00805f9b34fb';
const CHUNK_SIZE = 512; // 512 bytes per chunk
const CHUNK_DELAY = 50; // 50ms delay between chunks

class ThermalPrinterService {
    constructor() {
        this.device = null;
        this.server = null;
        this.characteristic = null;
        this.connected = false;
        this.connecting = false;
        this.printerName = localStorage.getItem('printer_name') || null;
        this.isNative = typeof window.AndroidInterface !== 'undefined';
        this._onStatusChange = null;
        this._retryCount = 0;
        this._maxRetries = 5;

        console.log(`[Printer] Initialized. Mode: ${this.isNative ? 'Native (Android Bridge)' : 'Web (Bluetooth API)'}`);

        // Listener for Native Bridge Success
        if (this.isNative) {
            window.addEventListener('printer-connected', (e) => {
                this.connected = true;
                this.connecting = false;
                this.printerName = e.detail.name || 'Android Native Printer';
                localStorage.setItem('printer_name', this.printerName);
                localStorage.setItem('printer_auto_reconnect', 'true');
                this._syncNativeSettings();
                this._notifyStatusChange();
            });
        }

        // Try to auto-connect on initialization (Page Refresh)
        // Only if not explicitly disabled by manual disconnect
        const shouldAutoConnect = localStorage.getItem('printer_auto_reconnect') !== 'false';
        if (shouldAutoConnect) {
            setTimeout(() => this.autoConnect(), 500);
        }
    }

    /**
     * Register a callback for connection status changes
     */
    onStatusChange(callback) {
        this._onStatusChange = callback;
    }

    _notifyStatusChange() {
        if (this._onStatusChange) {
            this._onStatusChange({
                connected: this.connected,
                connecting: this.connecting,
                printerName: this.printerName,
                isNative: this.isNative
            });
        }
    }

    // ═══════════════════════════════════════════════════════
    // CONNECTION
    // ═══════════════════════════════════════════════════════

    isBluetoothSupported() {
        return 'bluetooth' in navigator;
    }

    /**
     * Manual connect — shows device picker dialog
     */
    async connect() {
        if (this.connecting || this.connected) return false;
        this.connecting = true;
        this._notifyStatusChange();

        try {
            if (this.isNative) {
                console.log('[Printer] Native Scan Requested...');
                window.AndroidInterface.scanPrinters();

                // Native picker is async, we stop 'connecting' after 30s if nothing happens
                setTimeout(() => {
                    if (this.connecting && !this.connected) {
                        this.connecting = false;
                        this._notifyStatusChange();
                    }
                }, 30000);

                return false; // Result handled by event listener
            }

            if (!this.isBluetoothSupported()) {
                throw new Error('Web Bluetooth tidak didukung di browser ini. Gunakan Chrome atau Edge.');
            }

            console.log('[Printer] Requesting Web Bluetooth Device...');
            const device = await navigator.bluetooth.requestDevice({
                filters: [
                    { services: [SERVICE_UUID] },
                    { namePrefix: 'BT-Printer' },
                    { namePrefix: 'MPT' },
                    { namePrefix: 'Inner' },
                    { namePrefix: 'POS' },
                    { namePrefix: 'RPP' },
                    { namePrefix: 'XP-' },
                    { namePrefix: 'MTP' },
                    { namePrefix: 'BlueTooth' },
                    { namePrefix: 'Printer' }
                ],
                optionalServices: [SERVICE_UUID, '00001101-0000-1000-8000-00805f9b34fb']
            });

            const success = await this._connectToDevice(device);
            if (success) {
                localStorage.setItem('printer_auto_reconnect', 'true');
            }
            return success;
        } catch (error) {
            console.error('[Printer] Connection failed:', error);
            this.connected = false;
            throw error;
        } finally {
            this.connecting = false;
            this._notifyStatusChange();
        }
    }

    /**
     * Auto-reconnect to the last used printer on page load.
     * Uses watchAdvertisements() if available, falls back to retry loop.
     */
    async autoConnect() {
        if (this.connecting || this.connected) return false;
        this.connecting = true;
        this._notifyStatusChange();

        const shouldReconnect = localStorage.getItem('printer_auto_reconnect') !== 'false';
        if (!shouldReconnect) {
            this.connecting = false;
            this._notifyStatusChange();
            return false;
        }

        try {
            // Jeda 500ms (Cooling Down) agar trafik Bluetooth stabil
            await new Promise(resolve => setTimeout(resolve, 500));

            if (this.isNative) {
                console.log('[Printer] Native Auto-Connecting...');
                try {
                    const success = await window.AndroidInterface.autoReconnect();
                    if (success) {
                        this.connected = true;
                        this.printerName = 'Android Native Printer';
                        this._syncNativeSettings();
                    }
                    return success;
                } catch (err) {
                    console.warn('[Printer] Native auto-connect failed:', err);
                    return false;
                }
            }

            if (!this.isBluetoothSupported() || !navigator.bluetooth.getDevices) {
                return false;
            }

            const devices = await navigator.bluetooth.getDevices();
            if (devices.length === 0) return false;

            // Connect to the first remembered device
            const device = devices[0];
            console.log('[Printer] Found remembered device:', device.name || device.id);

            // Strategy 1: Use watchAdvertisements (most reliable for auto-connect)
            if (device.watchAdvertisements) {
                console.log('[Printer] Using watchAdvertisements strategy...');
                return await this._connectViaAdvertisement(device);
            }

            // Strategy 2: Direct connect with retry (fallback)
            return await this._reconnectWithRetry(device, 5, 1500);
        } catch (error) {
            console.warn('[Printer] AutoConnect failed:', error.message);
            return false;
        } finally {
            this.connecting = false;
            this._notifyStatusChange();
        }
    }

    /**
     * Wait for BLE advertisement from device, then connect.
     */
    _connectViaAdvertisement(device) {
        return new Promise((resolve) => {
            const timeout = setTimeout(() => {
                console.warn('[Printer] Advertisement timeout, trying direct connect...');
                this._reconnectWithRetry(device, 3, 1500).then(resolve).catch(() => resolve(false));
            }, 3000);

            const onAdReceived = async () => {
                clearTimeout(timeout);
                device.removeEventListener('advertisementreceived', onAdReceived);
                console.log('[Printer] Advertisement received from:', device.name);
                try {
                    await this._connectToDevice(device);
                    resolve(true);
                } catch (err) {
                    console.warn('[Printer] Connect after ad failed:', err.message);
                    resolve(false);
                }
            };

            device.addEventListener('advertisementreceived', onAdReceived);
            device.watchAdvertisements().catch((err) => {
                clearTimeout(timeout);
                console.warn('[Printer] watchAdvertisements failed:', err.message);
                this._reconnectWithRetry(device, 3, 1500).then(resolve).catch(() => resolve(false));
            });
        });
    }

    /**
     * Retry connecting to a device multiple times with delay.
     */
    async _reconnectWithRetry(device, maxRetries = 5, delayMs = 1500) {
        for (let attempt = 1; attempt <= maxRetries; attempt++) {
            if (this.connected) return true;
            try {
                console.log(`[Printer] Reconnect attempt ${attempt}/${maxRetries}...`);
                await this._connectToDevice(device);
                console.log('[Printer] Reconnect Success!');
                return true;
            } catch (error) {
                console.warn(`[Printer] Attempt ${attempt} failed:`, error.message);
                if (attempt < maxRetries) {
                    await new Promise(r => setTimeout(r, delayMs));
                }
            }
        }
        return false;
    }

    /**
     * Internal: establish GATT connection to device (Web Only)
     */
    async _connectToDevice(device) {
        this.device = device;

        // Always attempt GATT connect with retry for stability
        console.log('[Printer] Connecting to GATT Server...');
        this.server = await this.device.gatt.connect();

        // Listen for disconnection
        this.device.removeEventListener('gattserverdisconnected', this._onDisconnected);
        this.device.addEventListener('gattserverdisconnected', () => this._onDisconnected());

        // Find service (with fallback)
        const serviceUUIDs = [SERVICE_UUID, '49535343-fe7d-4ae5-8fa9-9fafd205e455'];
        let service = null;
        for (const uuid of serviceUUIDs) {
            try {
                service = await this.server.getPrimaryService(uuid);
                if (service) break;
            } catch (e) { continue; }
        }

        if (!service) throw new Error('Service printer tidak ditemukan');

        // Find characteristic
        const characteristics = await service.getCharacteristics();
        this.characteristic = characteristics.find(c => c.properties.write || c.properties.writeWithoutResponse);

        if (!this.characteristic) throw new Error('Write characteristic tidak ditemukan');

        this.connected = true;
        this.printerName = this.device.name || 'Unknown Printer';
        localStorage.setItem('printer_name', this.printerName);
        localStorage.setItem('printer_auto_reconnect', 'true');

        console.log(`[Printer] Connected to: ${this.printerName}`);
        this._notifyStatusChange();
        return true;
    }

    /**
     * Handle disconnection and trigger auto-retry
     */
    _onDisconnected() {
        console.log('[Printer] Disconnected unexpectedly.');
        this.connected = false;
        this.characteristic = null;
        this.server = null;
        this._notifyStatusChange();

        // Auto-reconnect after UNEXPECTED disconnect (not manual)
        const shouldReconnect = localStorage.getItem('printer_auto_reconnect') !== 'false';
        if (shouldReconnect && this.device) {
            console.warn('[Printer] Triggering auto-retry loop...');
            this._reconnectWithRetry(this.device, 10, 2000);
        }
    }

    /**
     * Sync settings to Android Bridge
     */
    _syncNativeSettings() {
        if (!this.isNative) return;
        const width = this._getPaperWidth();
        const charLimit = width === 80 ? 48 : 32;
        window.AndroidInterface.setPrinterSettings(203, width, charLimit);
    }

    /**
     * Hard Disconnect (Reset)
     */
    async disconnect() {
        // Mark as manual disconnect FIRST
        localStorage.setItem('printer_auto_reconnect', 'false');
        localStorage.removeItem('printer_name');

        if (this.isNative) {
            if (window.AndroidInterface.disconnect) {
                await window.AndroidInterface.disconnect();
            }
        } else if (this.device) {
            try {
                if (this.device.gatt && this.device.gatt.connected) {
                    await this.device.gatt.disconnect();
                }
                // clear browser permission
                if (this.device.forget) {
                    await this.device.forget();
                }
            } catch (err) {
                console.warn('[Printer] Disconnect cleanup error:', err);
            }
        }

        this.device = null;
        this.server = null;
        this.characteristic = null;
        this.connected = false;
        this.printerName = null;

        console.log('[Printer] Fully disconnected (Reset).');
        this._notifyStatusChange();
    }

    // ═══════════════════════════════════════════════════════
    // DATA WRITE (Chunking logic)
    // ═══════════════════════════════════════════════════════

    /**
     * Send raw bytes to printer in chunks (Anti-Lag)
     */
    async write(data) {
        if (!this.connected) {
            const reconnected = await this.autoConnect();
            if (!reconnected) throw new Error('Printer tidak terhubung.');
        }

        if (this.isNative) {
            // Android bridge usually expects Base64 or Hex for raw bytes
            // Assuming it can handle byte array or we convert it
            const base64Data = btoa(String.fromCharCode(...data));
            await window.AndroidInterface.printRaw(base64Data);
            return;
        }

        if (!this.characteristic) throw new Error('Printer characteristic not found.');

        for (let i = 0; i < data.length; i += CHUNK_SIZE) {
            const chunk = data.slice(i, i + CHUNK_SIZE);
            await this.characteristic.writeValue(chunk);

            // Wait 50ms for printer buffer to breathe
            if (i + CHUNK_SIZE < data.length) {
                await new Promise(resolve => setTimeout(resolve, CHUNK_DELAY));
            }
        }
    }

    // ═══════════════════════════════════════════════════════
    // TEXT HELPERS & ESC/POS
    // ═══════════════════════════════════════════════════════

    get ESC() {
        return {
            INIT: [0x1B, 0x40],
            BOLD_ON: [0x1B, 0x45, 0x01],
            BOLD_OFF: [0x1B, 0x45, 0x00],
            DOUBLE_HEIGHT: [0x1D, 0x21, 0x01],
            DOUBLE_WH: [0x1D, 0x21, 0x11],
            NORMAL: [0x1D, 0x21, 0x00],
            ALIGN_LEFT: [0x1B, 0x61, 0x00],
            ALIGN_CENTER: [0x1B, 0x61, 0x01],
            ALIGN_RIGHT: [0x1B, 0x61, 0x02],
            CUT: [0x1D, 0x56, 0x00],
            FEED_LINE: [0x0A],
            LINE_SPACING: [0x1B, 0x33, 0x20],
        };
    }

    _padCenter(text, maxWidth = 32) {
        const spaces = Math.max(0, Math.floor((maxWidth - text.length) / 2));
        return ' '.repeat(spaces) + text;
    }

    _padRight(left, right, maxWidth = 32) {
        const spaces = maxWidth - left.length - right.length;
        return left + ' '.repeat(Math.max(1, spaces)) + right;
    }

    _getPaperWidth() {
        return parseInt(localStorage.getItem('paper_size') || '58');
    }

    _getCharLimit() {
        return this._getPaperWidth() === 80 ? 48 : 32;
    }

    // ═══════════════════════════════════════════════════════
    // PRINT FUNCTIONS
    // ═══════════════════════════════════════════════════════

    async printTest() {
        const encoder = new TextEncoder();
        const charLimit = this._getCharLimit();
        const is58 = charLimit === 32;
        const divider = '-'.repeat(charLimit);

        let commands = [
            ...this.ESC.INIT,
            ...this.ESC.LINE_SPACING,
            ...this.ESC.ALIGN_LEFT,
        ];

        if (is58) {
            commands.push(...this.ESC.DOUBLE_HEIGHT);
            commands.push(...encoder.encode(this._padCenter('Kee POS', charLimit) + '\n'));
            commands.push(...this.ESC.NORMAL);
            commands.push(...encoder.encode(divider + '\n'));
            commands.push(...encoder.encode(this._padCenter('PRINTER TEST BERHASIL!', charLimit) + '\n'));
            commands.push(...encoder.encode(this._padCenter(this.isNative ? 'Android Native Path' : 'Web Bluetooth Path', charLimit) + '\n'));
        } else {
            commands.push(...this.ESC.ALIGN_CENTER);
            commands.push(...this.ESC.DOUBLE_WH);
            commands.push(...encoder.encode('Kee POS\n'));
            commands.push(...this.ESC.NORMAL);
            commands.push(...encoder.encode(divider + '\n'));
            commands.push(...encoder.encode('PRINTER TEST BERHASIL!\n'));
            commands.push(...encoder.encode(this.isNative ? 'Android Native Path\n' : 'Web Bluetooth Path\n'));
        }

        commands.push(...encoder.encode(divider + '\n'));
        commands.push(...this.ESC.FEED_LINE, ...this.ESC.FEED_LINE, ...this.ESC.FEED_LINE);
        commands.push(...this.ESC.CUT);

        await this.write(new Uint8Array(commands));
    }

    async printReceipt(receiptData, width = null) {
        const encoder = new TextEncoder();
        const paperWidth = width || this._getPaperWidth();
        const charLimit = paperWidth === 80 ? 48 : 32;
        const is58 = paperWidth === 58;
        const divider = '-'.repeat(charLimit);

        let commands = [
            ...this.ESC.INIT,
            ...this.ESC.LINE_SPACING,
        ];

        // ── HEADER ──
        if (is58) {
            commands.push(...this.ESC.ALIGN_LEFT);
            commands.push(...this.ESC.DOUBLE_HEIGHT);
            const shopName = (receiptData.shopName || 'Kee POS').toUpperCase();
            commands.push(...encoder.encode(this._padCenter(shopName, charLimit) + '\n'));
            commands.push(...this.ESC.NORMAL);

            if (receiptData.shopAddress) {
                commands.push(...encoder.encode(this._padCenter(receiptData.shopAddress, charLimit) + '\n'));
            }
            if (receiptData.shopPhone) {
                commands.push(...encoder.encode(this._padCenter('Tel: ' + receiptData.shopPhone, charLimit) + '\n'));
            }
        } else {
            commands.push(...this.ESC.ALIGN_CENTER);
            commands.push(...this.ESC.DOUBLE_WH);
            commands.push(...encoder.encode((receiptData.shopName || 'Kee POS').toUpperCase() + '\n'));
            commands.push(...this.ESC.NORMAL);

            if (receiptData.shopAddress) {
                commands.push(...encoder.encode(receiptData.shopAddress + '\n'));
            }
            if (receiptData.shopPhone) {
                commands.push(...encoder.encode('Tel: ' + receiptData.shopPhone + '\n'));
            }
        }

        commands.push(...this.ESC.ALIGN_LEFT);
        commands.push(...encoder.encode(divider + '\n'));

        // ── META INFO ──
        commands.push(...encoder.encode('No: ' + (receiptData.invoiceNo || '-') + '\n'));
        commands.push(...encoder.encode('Tgl: ' + (receiptData.date || '-') + '\n'));
        commands.push(...encoder.encode('Kasir: ' + (receiptData.cashierName || '-') + '\n'));
        if (receiptData.tableName) {
            commands.push(...encoder.encode('Meja: ' + receiptData.tableName + '\n'));
        }
        if (receiptData.paymentMethod) {
            commands.push(...encoder.encode('Bayar: ' + receiptData.paymentMethod + '\n'));
        }
        commands.push(...encoder.encode(divider + '\n'));

        // ── ITEMS ──
        receiptData.items.forEach(item => {
            const name = item.name.length > charLimit ? item.name.substring(0, charLimit) : item.name;
            commands.push(...encoder.encode(name + '\n'));

            const qtyPrice = `  ${item.qty} x ${item.price}`;
            const subtotal = item.subtotal.toString();
            commands.push(...encoder.encode(this._padRight(qtyPrice, subtotal, charLimit) + '\n'));
        });

        commands.push(...encoder.encode(divider + '\n'));

        // ── TOTALS ──
        const addLine = (label, value) => {
            commands.push(...encoder.encode(this._padRight(label, value, charLimit) + '\n'));
        };

        addLine('Subtotal', receiptData.subtotal);
        if (receiptData.discount && receiptData.discount !== '0' && receiptData.discount !== 'Rp0') {
            addLine('Diskon', '-' + receiptData.discount);
        }
        if (receiptData.tax && receiptData.tax !== '0' && receiptData.tax !== 'Rp0') {
            addLine('Pajak', receiptData.tax);
        }

        commands.push(...this.ESC.BOLD_ON);
        addLine('TOTAL', receiptData.total);
        commands.push(...this.ESC.BOLD_OFF);

        commands.push(...this.ESC.FEED_LINE);
        addLine('Bayar', receiptData.paid || '0');
        addLine('Kembali', receiptData.change || '0');

        // ── FOOTER ──
        commands.push(...this.ESC.FEED_LINE);
        if (is58) {
            commands.push(...encoder.encode(this._padCenter(receiptData.footer || 'Terima Kasih Banyak!', charLimit) + '\n'));
        } else {
            commands.push(...this.ESC.ALIGN_CENTER);
            commands.push(...encoder.encode((receiptData.footer || 'Terima Kasih Banyak!') + '\n'));
        }

        commands.push(...this.ESC.FEED_LINE, ...this.ESC.FEED_LINE, ...this.ESC.FEED_LINE, ...this.ESC.FEED_LINE);
        commands.push(...this.ESC.CUT);

        await this.write(new Uint8Array(commands));
    }
}

export const thermalPrinter = new ThermalPrinterService();

