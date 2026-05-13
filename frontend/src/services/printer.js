
/**
 * Printer Service for Web Bluetooth ESC/POS Printing
 */
class PrinterService {
    constructor() {
        this.device = null;
        this.server = null;
        this.characteristic = null;
        this.connected = false;
        this.printerName = null;
    }

    /**
     * Request Bluetooth device and connect
     */
    async connect() {
        try {
            if (!navigator.bluetooth) {
                throw new Error('Web Bluetooth tidak didukung di browser ini. Gunakan Chrome atau Edge.');
            }

            console.log('Requesting Bluetooth Device...');
            const device = await navigator.bluetooth.requestDevice({
                filters: [
                    { services: ['000018f0-0000-1000-8000-00805f9b34fb'] }, // Common for printers
                    { namePrefix: 'BT-Printer' },
                    { namePrefix: 'MPT' },
                    { namePrefix: 'Inner' },
                    { namePrefix: 'POS' },
                    { namePrefix: 'RPP' },
                    { namePrefix: 'XP-' },
                    { namePrefix: 'MTP' }
                ],
                optionalServices: ['000018f0-0000-1000-8000-00805f9b34fb']
            });

            return await this._connectToDevice(device);
        } catch (error) {
            console.error('Connection failed:', error);
            this.connected = false;
            throw error;
        }
    }

    /**
     * Attempt to reconnect to a previously authorized device
     */
    async autoConnect() {
        if (!navigator.bluetooth || !navigator.bluetooth.getDevices) {
            console.warn('Web Bluetooth getDevices() is not supported.');
            return false;
        }

        try {
            const devices = await navigator.bluetooth.getDevices();
            const lastPrinterName = localStorage.getItem('printer_name');

            if (devices.length > 0) {
                console.log(`Found ${devices.length} authorized devices. Searching for ${lastPrinterName}...`);

                // Try to find the last used device by name or ID
                let device = devices.find(d => d.name === lastPrinterName);

                // Fallback to the first available printer if only one is paired
                if (!device && devices.length === 1) {
                    device = devices[0];
                }

                if (device) {
                    console.log('Attempting to reconnect to:', device.name);
                    return await this._connectToDevice(device);
                }
            }
            return false;
        } catch (error) {
            console.warn('Auto-reconnect failed silently:', error);
            return false;
        }
    }

    /**
     * Internal method to establish GATT connection
     */
    async _connectToDevice(device) {
        this.device = device;

        this.device.addEventListener('gattserverdisconnected', () => {
            this.onDisconnected();
        });

        console.log('Connecting to GATT Server...');
        this.server = await this.device.gatt.connect();

        console.log('Getting Service...');
        // Some printers might use different service IDs, but 18f0 is very common
        const service = await this.server.getPrimaryService('000018f0-0000-1000-8000-00805f9b34fb');

        console.log('Getting Characteristic...');
        this.characteristic = await service.getCharacteristic('00002af1-0000-1000-8000-00805f9b34fb');

        this.connected = true;
        this.printerName = this.device.name;

        // Save to localStorage for auto-reconnect
        localStorage.setItem('printer_name', this.printerName);

        return true;
    }

    onDisconnected() {
        console.log('Printer disconnected');
        this.connected = false;
        this.characteristic = null;
        this.server = null;
        this.device = null;

        // Optionally try autoConnect after a delay if it was an accidental disconnect
        setTimeout(() => {
            if (!this.connected && localStorage.getItem('printer_name')) {
                console.log('Attempting background auto-reconnect...');
                this.autoConnect();
            }
        }, 3000);
    }

    async disconnect() {
        if (this.device && this.device.gatt.connected) {
            this.device.gatt.disconnect();
        }
        this.connected = false;
        localStorage.removeItem('printer_name'); // Clear so it doesn't auto-reconnect if user explicitly disconnected
    }

    /**
     * Send data to printer
     * @param {Uint8Array} data 
     */
    async write(data) {
        if (!this.connected || !this.characteristic) {
            // Try one quick auto-connect if we have a saved printer
            const reconnected = await this.autoConnect();
            if (!reconnected) {
                throw new Error('Printer tidak terhubung. Silakan hubungkan ulang.');
            }
        }

        // Some printers have limits on chunk size (usually 20 bytes)
        const CHUNK_SIZE = 20;
        for (let i = 0; i < data.length; i += CHUNK_SIZE) {
            const chunk = data.slice(i, i + CHUNK_SIZE);
            await this.characteristic.writeValue(chunk);
        }
    }

    /**
     * ESC/POS Commands
     */
    async printTest() {
        const encoder = new TextEncoder();
        const data = new Uint8Array([
            0x1b, 0x40, // Initialize
            0x1b, 0x61, 0x01, // Center align
            ...encoder.encode('Kee POS\n'),
            ...encoder.encode('--------------------------------\n'),
            ...encoder.encode('PRINTER TEST BERHASIL!\n'),
            0x1b, 0x61, 0x01, // Center
            ...encoder.encode('Bluetooth ESC/POS\n'),
            ...encoder.encode('--------------------------------\n'),
            0x0a, 0x0a, 0x0a, // Feed lines
            0x1b, 0x69 // Paper cut if supported
        ]);

        await this.write(data);
    }

    /**
     * Open cash drawer
     * ESC/POS command: ESC p m t1 t2
     * 0x1B 0x70 0x00 0x19 0xFA (pin 2, 25ms pulse, 250ms wait)
     * 0x1B 0x70 0x01 0x19 0xFA (pin 5, 25ms pulse, 250ms wait)
     */
    async openCashDrawer(pin = 0) {
        if (!this.connected || !this.characteristic) {
            throw new Error('Printer tidak terhubung. Silakan hubungkan ulang.');
        }

        // Most cash drawers use pin 2 (m=0) or pin 5 (m=1)
        // t1 = pulse ON time (25ms = 0x19)
        // t2 = pulse OFF time (250ms = 0xFA)
        const command = new Uint8Array([
            0x1B, 0x70, pin, 0x19, 0xFA
        ]);

        await this.write(command);
        console.log(`Cash drawer opened (pin ${pin === 0 ? 2 : 5})`);
    }

    /**
     * Format receipt and print
     * @param {Object} receiptData 
     * @param {number} width 58 or 80
     * @param {boolean} openDrawer Open cash drawer before printing
     */
    async printReceipt(receiptData, width = 58, openDrawer = false) {
        const encoder = new TextEncoder();
        const charLimit = width === 58 ? 32 : 48;
        const divider = '-'.repeat(charLimit) + '\n';

        let commands = [
            0x1b, 0x40, // Init
            0x1b, 0x33, 0x20, // Line spacing
        ];

        // Open cash drawer FIRST if requested
        if (openDrawer) {
            commands.push(0x1B, 0x70, 0x00, 0x19, 0xFA); // Open drawer pin 2
        }

        // Header (Center)
        commands.push(0x1b, 0x61, 0x01); // Center
        commands.push(0x1b, 0x21, 0x30); // Double height/width
        commands.push(...encoder.encode((receiptData.shopName || 'Kee POS').toUpperCase() + '\n'));
        commands.push(0x1b, 0x21, 0x00); // Normal text
        if (receiptData.shopAddress) commands.push(...encoder.encode(receiptData.shopAddress + '\n'));
        if (receiptData.shopPhone) commands.push(...encoder.encode('Tel: ' + receiptData.shopPhone + '\n'));
        commands.push(...encoder.encode(divider));

        // Meta Info (Left)
        commands.push(0x1b, 0x61, 0x00); // Left align
        commands.push(...encoder.encode('No: ' + (receiptData.invoiceNo || '-') + '\n'));
        commands.push(...encoder.encode('Tgl: ' + (receiptData.date || '-') + '\n'));
        commands.push(...encoder.encode('Kasir: ' + (receiptData.cashierName || '-') + '\n'));
        if (receiptData.tableName) commands.push(...encoder.encode('Meja: ' + receiptData.tableName + '\n'));
        commands.push(...encoder.encode(divider));

        // Items
        receiptData.items.forEach(item => {
            // Line 1: Name
            commands.push(...encoder.encode(item.name + '\n'));

            // Line 2: Qty x Price ... Subtotal
            const qtyPrice = `  ${item.qty} x ${item.price}`;
            const subtotal = item.subtotal.toString();
            const spaces = charLimit - qtyPrice.length - subtotal.length;
            commands.push(...encoder.encode(qtyPrice + ' '.repeat(Math.max(1, spaces)) + subtotal + '\n'));
        });
        commands.push(...encoder.encode(divider));

        // Totals
        const addTotalLine = (label, value) => {
            const valStr = value.toString();
            const spaces = charLimit - label.length - valStr.length;
            commands.push(...encoder.encode(label + ' '.repeat(Math.max(1, spaces)) + valStr + '\n'));
        };

        addTotalLine('Subtotal', receiptData.subtotal);
        if (receiptData.discount > 0) addTotalLine('Diskon', '-' + receiptData.discount);
        if (receiptData.tax > 0) addTotalLine('Pajak', receiptData.tax);

        // Total Bold
        commands.push(0x1b, 0x21, 0x08); // Emphasis on
        addTotalLine('TOTAL', receiptData.total);
        commands.push(0x1b, 0x21, 0x00); // Emphasis off

        commands.push(0x0a); // Feed
        addTotalLine('Bayar', receiptData.paid || 0);
        addTotalLine('Kembali', receiptData.change || 0);

        // Footer
        commands.push(0x0a);
        commands.push(0x1b, 0x61, 0x01); // Center
        commands.push(...encoder.encode(receiptData.footer || 'Terima Kasih Atas Kunjungannya\n'));
        commands.push(0x0a, 0x0a, 0x0a, 0x0a); // Feed for cutting
        commands.push(0x1b, 0x69); // Cut paper

        await this.write(new Uint8Array(commands));
    }
}

export const printerService = new PrinterService();
