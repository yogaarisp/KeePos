// Service Worker - Self Unregister
// Hapus semua cache lama dan unregister SW ini
self.addEventListener('install', () => {
  self.skipWaiting();
});

self.addEventListener('activate', async () => {
  // Hapus semua cache
  const keys = await caches.keys();
  await Promise.all(keys.map(key => caches.delete(key)));
  // Unregister SW ini sendiri
  await self.registration.unregister();
  // Paksa semua tab reload
  const clients = await self.clients.matchAll({ type: 'window' });
  clients.forEach(client => client.navigate(client.url));
});
