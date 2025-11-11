const CACHE_NAME = 'dygne-pwa-v2';
const PRECACHE_URLS = [
  '/',
  '/manifest.json',
  '/favicon.ico',
];

self.addEventListener('install', (event) => {
  self.skipWaiting();
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => cache.addAll(PRECACHE_URLS)).catch(() => {})
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    (async () => {
      const keys = await caches.keys();
      await Promise.all(keys.map((k) => (k === CACHE_NAME ? null : caches.delete(k))));
      await self.clients.claim();
    })()
  );
});

self.addEventListener('fetch', (event) => {
  const req = event.request;

  // Only handle same-origin GET requests
  const url = new URL(req.url);
  if (req.method !== 'GET' || url.origin !== self.location.origin) {
    return; // Let the browser handle it
  }

  // Network-first for HTML (navigation)
  if (req.mode === 'navigate') {
    event.respondWith(
      fetch(req)
        .then(async (res) => {
          const cache = await caches.open(CACHE_NAME);
          cache.put(req, res.clone());
          return res;
        })
        .catch(async () => {
          const cached = await caches.match(req);
          return cached || caches.match('/');
        })
    );
    return;
  }

  // For static assets: try cache, then network, with safe fallback
  event.respondWith(
    caches.match(req).then((cached) => {
      if (cached) return cached;
      return fetch(req)
        .then(async (res) => {
          // Only cache valid basic (same-origin) responses
          if (res && res.status === 200 && res.type === 'basic') {
            const cache = await caches.open(CACHE_NAME);
            cache.put(req, res.clone());
          }
          return res;
        })
        .catch(() => new Response('', { status: 504, statusText: 'offline' }));
    })
  );
});
