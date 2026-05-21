// Service Worker for WartegKee POS - Hybrid Version
const CACHE_NAME = 'wartegkee-pos-v1.3';
const ASSETS_TO_CACHE = [
  '/',
  '/index.html',
  '/manifest.json',
  '/logo-192.png',
  '/app/pos'
];

// Install: Simpan aset ke cache
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      console.log('[SW] Caching layout assets');
      // Gunakan addAll dengan catch per-item agar tidak gagal total
      return Promise.all(
        ASSETS_TO_CACHE.map((url) =>
          cache.add(url).catch((err) => {
            console.warn('[SW] Failed to cache:', url, err);
          })
        )
      );
    })
  );
  // Langsung aktifkan SW baru tanpa menunggu tab ditutup
  self.skipWaiting();
});

// Activate: Bersihkan cache lama
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cache) => {
          if (cache !== CACHE_NAME) {
            console.log('[SW] Deleting old cache:', cache);
            return caches.delete(cache);
          }
        })
      );
    })
  );
  return self.clients.claim();
});

// Fetch: Strategi Network First, fallback ke Cache
self.addEventListener('fetch', (event) => {
  // Hanya cache permintaan GET
  if (event.request.method !== 'GET') return;

  // Jangan intercept request ke API
  if (event.request.url.includes('/api/')) return;

  event.respondWith(
    fetch(event.request)
      .then((networkResponse) => {
        // Simpan response baru ke cache
        if (networkResponse && networkResponse.status === 200) {
          const responseClone = networkResponse.clone();
          caches.open(CACHE_NAME).then((cache) => {
            cache.put(event.request, responseClone);
          });
        }
        return networkResponse;
      })
      .catch(() => {
        // Fallback ke cache jika offline
        return caches.match(event.request).then((cachedResponse) => {
          if (cachedResponse) return cachedResponse;
          // Fallback navigasi ke index.html (SPA)
          if (event.request.mode === 'navigate') {
            return caches.match('/index.html');
          }
          // Kembalikan response kosong agar tidak error
          return new Response('', { status: 503, statusText: 'Offline' });
        });
      })
  );
});
