/**
 * Utility untuk update meta tags, favicon, dan OG image secara dinamis
 * dari data platform settings (database).
 *
 * Priority:
 * - app_favicon  → favicon yang diset superadmin di SaaS Config
 * - app_logo     → logo yang diset superadmin di SaaS Config
 * - shop_favicon → favicon tenant (jika ada)
 * - shop_logo    → logo tenant (jika ada)
 * - fallback     → file statis /logo-192.png
 */

const BASE_URL = import.meta.env.VITE_APP_URL || 'https://pos.keetech.my.id';

/**
 * Update atau buat meta tag
 */
function setMeta(selector, attr, value) {
    if (!value) return;
    let el = document.querySelector(selector);
    if (!el) {
        el = document.createElement('meta');
        const parts = selector.match(/\[([^\]]+)="([^\]]+)"\]/);
        if (parts) el.setAttribute(parts[1], parts[2]);
        document.head.appendChild(el);
    }
    el.setAttribute(attr, value);
}

/**
 * Update atau buat link tag dengan cache-busting
 */
function setLink(rel, href, sizes = null, type = null) {
    if (!href) return;
    // Cache-bust agar browser tidak pakai cache lama
    const bustedHref = href.includes('?') ? href : `${href}?v=${Date.now()}`;

    // Hapus semua link dengan rel yang sama dulu untuk menghindari duplikat
    document.querySelectorAll(`link[rel="${rel}"]`).forEach(el => el.remove());

    const el = document.createElement('link');
    el.rel = rel;
    if (sizes) el.sizes = sizes;
    if (type) el.type = type;
    el.href = bustedHref;
    document.head.appendChild(el);
}

/**
 * Resolve URL dari path storage atau URL absolut
 */
function resolveUrl(path, storageBaseUrl) {
    if (!path) return null;
    if (path.startsWith('http')) return path;
    return `${storageBaseUrl}/storage/${path}`;
}

/**
 * Update semua meta tags dari platform/tenant settings
 *
 * @param {Object} settings - Data dari /settings/public API
 * @param {string} storageBaseUrl - Base URL untuk storage files (dari api.js baseUrl)
 */
export function updatePlatformMeta(settings, storageBaseUrl = '') {
    if (!settings) return;

    const appName = settings.app_name || settings.shop_name || 'Kee POS';
    const description = `${appName}: Sistem kasir digital berbasis cloud untuk bisnis kuliner.`;

    // ── Resolve Logo URL ──
    // Prioritas: app_logo (superadmin) > shop_logo (tenant) > fallback statis
    const logoUrl =
        resolveUrl(settings.app_logo, storageBaseUrl) ||
        resolveUrl(settings.shop_logo, storageBaseUrl) ||
        `${BASE_URL}/logo-192.png`;

    // ── Resolve Favicon URL ──
    // Prioritas: app_favicon (superadmin) > shop_favicon (tenant) > logo > fallback statis
    const faviconUrl =
        resolveUrl(settings.app_favicon, storageBaseUrl) ||
        resolveUrl(settings.shop_favicon, storageBaseUrl) ||
        logoUrl;

    // ── Title ──
    document.title = `${appName} — Kasir Digital untuk Bisnis Kuliner`;

    // ── Favicon (semua format, dengan cache-bust) ──
    setLink('icon', faviconUrl, '512x512', 'image/png');
    setLink('shortcut icon', faviconUrl, null, 'image/png');
    setLink('apple-touch-icon', faviconUrl, '180x180');

    // ── PWA Manifest ──
    setLink('manifest', '/manifest.json');

    // ── SEO ──
    setMeta('meta[name="description"]', 'content', description);
    setMeta('meta[name="author"]', 'content', appName);

    // ── Open Graph (WhatsApp, Telegram, Facebook preview) ──
    setMeta('meta[property="og:title"]', 'content', `${appName} — Kasir Digital untuk Bisnis Kuliner`);
    setMeta('meta[property="og:description"]', 'content', description);
    // Tambahkan timestamp agar WhatsApp/Telegram fetch ulang image terbaru
    const ogImageUrl = logoUrl.includes('?') ? logoUrl : `${logoUrl}?t=${Math.floor(Date.now()/86400000)}`;
    setMeta('meta[property="og:image"]', 'content', ogImageUrl);
    setMeta('meta[property="og:site_name"]', 'content', appName);
    setMeta('meta[property="og:url"]', 'content', BASE_URL);

    // ── Twitter Card ──
    setMeta('meta[name="twitter:title"]', 'content', `${appName} — Kasir Digital untuk Bisnis Kuliner`);
    setMeta('meta[name="twitter:description"]', 'content', description);
    setMeta('meta[name="twitter:image"]', 'content', logoUrl);

    // ── Apple PWA ──
    setMeta('meta[name="apple-mobile-web-app-title"]', 'content', appName);
}
