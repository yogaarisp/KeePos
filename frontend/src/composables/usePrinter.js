/**
 * usePrinter Composable
 * Vue 3 reactive hook for connecting thermalPrinter.js to UI
 * 
 * Provides reactive state (connected, printerName, connecting)
 * and actions (connect, disconnect, autoConnect, testPrint, printReceipt)
 */
import { ref, readonly, onMounted } from 'vue';
import { thermalPrinter } from '../services/thermalPrinter';

// Shared reactive state across all components
const connected = ref(false);
const printerName = ref(localStorage.getItem('printer_name') || null);
const connecting = ref(false);
const isNative = ref(typeof window.AndroidInterface !== 'undefined');

// Register status change listener (once)
thermalPrinter.onStatusChange((status) => {
    connected.value = status.connected;
    printerName.value = status.printerName;
    isNative.value = status.isNative;
});

export function usePrinter() {

    /**
     * Manual connect — shows Bluetooth device picker
     */
    const connect = async () => {
        connecting.value = true;
        try {
            await thermalPrinter.connect();
            return true;
        } catch (error) {
            throw error;
        } finally {
            connecting.value = false;
        }
    };

    /**
     * Hard disconnect — forget device, remove permissions
     */
    const disconnect = async () => {
        await thermalPrinter.disconnect();
    };

    /**
     * Auto-reconnect on page load (using watchAdvertisements)
     */
    const autoConnect = async () => {
        if (!connected.value) {
            try {
                await thermalPrinter.autoConnect();
            } catch (err) {
                console.warn('[usePrinter] Auto-connect failed:', err);
            }
        }
    };

    /**
     * Test print
     */
    const testPrint = async () => {
        await thermalPrinter.printTest();
    };

    /**
     * Print receipt
     */
    const printReceipt = async (receiptData, width = 58) => {
        await thermalPrinter.printReceipt(receiptData, width);
    };

    return {
        // Reactive state (readonly to prevent external mutation)
        connected: readonly(connected),
        printerName: readonly(printerName),
        connecting: readonly(connecting),
        isNative: readonly(isNative),

        // Actions
        connect,
        disconnect,
        autoConnect,
        testPrint,
        printReceipt,

        // Direct access to service (if needed for advanced use)
        service: thermalPrinter,
    };
}
