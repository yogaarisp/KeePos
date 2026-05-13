// Service Worker for WartegKee POS - Hybrid Version
const CACHE_NAME = 'wartegkee-pos-v1.2'; // Update versi ini setiap kali ada perubahan besar
const ASSETS_TO_CACHE = [
  '/',
  '/index.html',
  '/manifest.json',
  '/logo-192.png',
  '/logo-512.png',
  '/app/pos'
];

// Install: Simpan aset ke cache
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      console.log('[SW] Caching layout assets');
      return cache.addAll(ASSETS_TO_CACHE);
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

// Fetch: Strategi Cache First, then Network
self.addEventListener('fetch', (event) => {
  // Hanya cache permintaan GET
  if (event.request.method !== 'GET') return;

  event.respondWith(
    caches.match(event.request).then((response) => {
      // Jika ada di cache, kembalikan. Jika tidak, ambil dari network.
      return response || fetch(event.request).catch(() => {
        // Fallback jika offline dan aset tidak ada di cache
        if (event.request.mode === 'navigate') {
          return caches.match('/index.html');
        }
      });
    })
  );
});
