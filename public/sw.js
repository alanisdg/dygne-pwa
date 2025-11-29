const CACHE_NAME = 'dygne-pwa-v2';
const PRECACHE_URLS = [
  '/',
  '/manifest.json',
  '/favicon.ico',
];

console.log('[SW] Script loaded, CACHE_NAME =', CACHE_NAME);

self.addEventListener("notificationclick", function (event) {
  console.log('[SW] notificationclick fired', {
    action: event.action,
    notification: {
      title: event.notification && event.notification.title,
      body: event.notification && event.notification.body,
      data: event.notification && event.notification.data,
    },
  });

  event.notification.close();

  const data = event.notification.data || {};
  console.log('[SW] notificationclick data', data);

  // Si el usuario presiona el botón de la notificación
  if (event.action === "open_notification") {
    const targetUrl = data.url || "/";
    console.log('[SW] notificationclick with action open_notification, opening', targetUrl);
    event.waitUntil(clients.openWindow(targetUrl));
    return;
  }

  // Si solo toca la notificación sin tocar el botón
  const url = data.url || "/";
  console.log('[SW] notificationclick without specific action, fallback URL', url);

  event.waitUntil(
    clients.matchAll({ type: "window", includeUncontrolled: true }).then(clientList => {
      console.log('[SW] notificationclick clients.matchAll result length', clientList.length);
      // Si la app ya está abierta => solo enfoca
    if (clientList.length > 0) {
      console.log('[SW] focusing existing client and navigating to URL', url);
        const client = clientList[0];
        client.focus();
        client.navigate(url);   // ESTE ES EL QUE FALTABA ✨
        return;
    }

      // Si no hay ventanas abiertas => abre la página
      console.log('[SW] no clients found, opening new window', url);
      return clients.openWindow(url);
    }).catch((err) => {
      console.error('[SW] Error in notificationclick clients.matchAll', err);
    })
  );
});

self.addEventListener('install', (event) => {
  console.log('[SW] install event');
  self.skipWaiting();
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => {
        console.log('[SW] opened cache on install, adding PRECACHE_URLS', PRECACHE_URLS);
        return cache.addAll(PRECACHE_URLS);
      })
      .then(() => {
        console.log('[SW] precache completed');
      })
      .catch((err) => {
        console.error('[SW] error during install precache', err);
      })
  );
});

self.addEventListener("push", (event) => {
  console.log('[SW] push event received, raw event:', event);

  const data = event.data ? event.data.json() : {};
  console.log('[SW] push data parsed', data);

  const title = data.title || "Notificación";
  const options = {
    body: data.body || "Contenido",
    icon: "/icons/icon-192x192.png",
    badge: "/icons/icon-96x96.png",
    data: data.data || {}
  };

  console.log('[SW] showing notification with title & options', title, options);

  event.waitUntil(
    self.registration.showNotification(title, options).then(() => {
      console.log('[SW] notification shown successfully');
    }).catch((err) => {
      console.error('[SW] error showing notification', err);
    })
  );
});

self.addEventListener('activate', (event) => {
  console.log('[SW] activate event');
  event.waitUntil(
    (async () => {
      try {
        const keys = await caches.keys();
        console.log('[SW] existing cache keys', keys);
        await Promise.all(
          keys.map((k) => {
            if (k === CACHE_NAME) {
              console.log('[SW] keeping cache', k);
              return null;
            }
            console.log('[SW] deleting old cache', k);
            return caches.delete(k);
          })
        );
        await self.clients.claim();
        console.log('[SW] clients claimed after activate');
      } catch (err) {
        console.error('[SW] error in activate handler', err);
      }
    })()
  );
});

self.addEventListener('fetch', (event) => {
  const req = event.request;
  console.log('[SW] fetch event', {
    url: req.url,
    method: req.method,
    mode: req.mode,
    destination: req.destination,
  });

  // Only handle same-origin GET requests
  const url = new URL(req.url);
  if (req.method !== 'GET' || url.origin !== self.location.origin) {
    console.log('[SW] fetch: ignoring request, not same-origin GET', {
      method: req.method,
      origin: url.origin,
      swOrigin: self.location.origin,
    });
    return; // Let the browser handle it
  }

  // Network-first for HTML (navigation)
  if (req.mode === 'navigate') {
    console.log('[SW] fetch: navigation request, letting network handle it directly', req.url);
    return;
  }

  // For static assets: try cache, then network, with safe fallback
  console.log('[SW] fetch: handling static asset request', req.url);
  event.respondWith(
    caches.match(req).then((cached) => {
      if (cached) {
        console.log('[SW] fetch: cache hit for', req.url);
        return cached;
      }
      console.log('[SW] fetch: cache miss for', req.url, '-> going to network');
      return fetch(req)
        .then(async (res) => {
          console.log('[SW] fetch: network response received', {
            url: req.url,
            status: res.status,
            type: res.type,
          });
          // Only cache valid basic (same-origin) responses
          if (res && res.status === 200 && res.type === 'basic') {
            try {
              const cache = await caches.open(CACHE_NAME);
              await cache.put(req, res.clone());
              console.log('[SW] fetch: response cached for', req.url);
            } catch (err) {
              console.error('[SW] fetch: error caching response for', req.url, err);
            }
          } else {
            console.log('[SW] fetch: response NOT cached (status/type not eligible)', {
              status: res && res.status,
              type: res && res.type,
            });
          }
          return res;
        })
        .catch((err) => {
          console.error('[SW] fetch: network error for', req.url, err);
          return new Response('', { status: 504, statusText: 'offline' });
        });
    }).catch((err) => {
      console.error('[SW] fetch: error in caches.match chain for', req.url, err);
      return new Response('', { status: 500, statusText: 'sw-error' });
    })
  );
});
