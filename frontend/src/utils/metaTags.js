/**
 * Utility untuk update meta tags, favicon, dan OG image secara dinamis
 * dari data platform settings (database)
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
 * Update atau buat link tag (favicon, apple-touch-icon, dll)
 */
function setLink(rel, href, sizes = null, type = null) {
    if (!href) return;
    // Cache-bust dengan timestamp agar browser tidak pakai cache lama
    const bustedHref = href.includes('?') ? href : `${href}?v=${Date.now()}`;
    let el = document.querySelector(`link[rel="${rel}"]${sizes ? `[sizes="${sizes}"]` : ''}`);
    if (!el) {
        el = document.createElement('link');
        el.rel = rel;
        if (sizes) el.sizes = sizes;
        if (type) el.type = type;
        document.head.appendChild(el);
    }
    el.href = bustedHref;
}

/**
 * Update semua meta tags dari platform settings
 * @param {Object} settings - Data dari /settings/public API
 * @param {string} storageBaseUrl - Base URL untuk storage files
 */
export function updatePlatformMeta(settings, storageBaseUrl = '') {
    const appName = settings.shop_name || settings.app_name || 'Kee POS';
    const description = `${appName}: Sistem kasir digital berbasis cloud untuk bisnis kuliner.`;

    // Resolve logo URL - prioritas: app_logo dari DB, fallback ke file statis
    const logoPath = settings.app_logo || settings.shop_logo;
    const logoUrl = logoPath
        ? `${storageBaseUrl}/storage/${logoPath}`
        : `${BASE_URL}/logo-192.png`;

    const faviconPath = settings.app_favicon || settings.shop_favicon;
    const faviconUrl = faviconPath
        ? `${storageBaseUrl}/storage/${faviconPath}`
        : logoUrl; // fallback ke logo utama

    // ── Title ──
    document.title = `${appName} — Kasir Digital untuk Bisnis Kuliner`;

    // ── Favicon (dengan cache-bust agar langsung update) ──
    setLink('icon', faviconUrl, '512x512', 'image/png');
    setLink('icon', faviconUrl, '192x192', 'image/png');
    setLink('shortcut icon', faviconUrl, null, 'image/png');
    setLink('apple-touch-icon', faviconUrl, '180x180');

    // ── SEO ──
    setMeta('meta[name="description"]', 'content', description);
    setMeta('meta[name="author"]', 'content', appName);

    // ── Open Graph ──
    setMeta('meta[property="og:title"]', 'content', `${appName} — Kasir Digital untuk Bisnis Kuliner`);
    setMeta('meta[property="og:description"]', 'content', description);
    setMeta('meta[property="og:image"]', 'content', logoUrl);
    setMeta('meta[property="og:site_name"]', 'content', appName);
    setMeta('meta[property="og:url"]', 'content', BASE_URL);

    // ── Twitter Card ──
    setMeta('meta[name="twitter:title"]', 'content', `${appName} — Kasir Digital untuk Bisnis Kuliner`);
    setMeta('meta[name="twitter:description"]', 'content', description);
    setMeta('meta[name="twitter:image"]', 'content', logoUrl);

    // ── Apple PWA ──
    setMeta('meta[name="apple-mobile-web-app-title"]', 'content', appName);
}
