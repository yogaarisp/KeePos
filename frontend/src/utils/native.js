/**
 * Capacitor Native Integration
 * Handles platform-specific behavior for Android/iOS
 * Falls back gracefully when running in browser
 */
import { Capacitor } from '@capacitor/core';

export const isNative = Capacitor.isNativePlatform();
export const platform = Capacitor.getPlatform(); // 'android' | 'ios' | 'web'

/**
 * Initialize all native plugins
 * Call this once in main.js after app mount
 */
export async function initNativeApp() {
    if (!isNative) return;

    try {
        // Status Bar — edge-to-edge fullscreen
        const { StatusBar, Style } = await import('@capacitor/status-bar');
        await StatusBar.setStyle({ style: Style.Dark });
        await StatusBar.setBackgroundColor({ color: '#00000000' }); // Transparent
        await StatusBar.setOverlaysWebView({ overlay: true }); // Content goes behind status bar

        // Add class so CSS can apply safe-area padding
        document.body.classList.add('native-app');
    } catch (e) {
        console.warn('[Native] StatusBar plugin not available:', e.message);
    }

    try {
        // Keyboard behavior
        const { Keyboard } = await import('@capacitor/keyboard');
        Keyboard.addListener('keyboardWillShow', () => {
            document.body.classList.add('keyboard-open');
        });
        Keyboard.addListener('keyboardWillHide', () => {
            document.body.classList.remove('keyboard-open');
        });
    } catch (e) {
        console.warn('[Native] Keyboard plugin not available:', e.message);
    }

    try {
        // Splash Screen (auto-hide after app is ready)
        const { SplashScreen } = await import('@capacitor/splash-screen');
        await SplashScreen.hide({ fadeOutDuration: 300 });
    } catch (e) {
        console.warn('[Native] SplashScreen plugin not available:', e.message);
    }

    try {
        // App plugin: handle back button on Android
        const { App } = await import('@capacitor/app');
        App.addListener('backButton', ({ canGoBack }) => {
            if (canGoBack) {
                window.history.back();
            } else {
                App.exitApp();
            }
        });
    } catch (e) {
        console.warn('[Native] App plugin not available:', e.message);
    }
}

/**
 * Trigger haptic feedback (light tap)
 */
export async function hapticTap() {
    if (!isNative) return;
    try {
        const { Haptics, ImpactStyle } = await import('@capacitor/haptics');
        await Haptics.impact({ style: ImpactStyle.Light });
    } catch (e) {
        // Silently fail on web
    }
}

/**
 * Trigger haptic feedback (success)
 */
export async function hapticSuccess() {
    if (!isNative) return;
    try {
        const { Haptics, NotificationType } = await import('@capacitor/haptics');
        await Haptics.notification({ type: NotificationType.Success });
    } catch (e) {
        // Silently fail
    }
}
