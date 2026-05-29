const CACHE_NAME = 'larpmanager-v1';

const STATIC_ASSETS = [
  '/login',
  '/manifest.json',
  '/icons/icon-192.svg',
  '/icons/icon-512.svg',
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => cache.addAll(STATIC_ASSETS))
  );
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k)))
    )
  );
  self.clients.claim();
});

self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);

  // Ignorer les requêtes non-GET et les requêtes vers d'autres domaines
  if (request.method !== 'GET' || url.origin !== self.location.origin) return;

  // Ignorer le profiler Symfony et les assets de debug
  if (url.pathname.startsWith('/_')) return;

  const isAsset = /\.(css|js|svg|png|jpg|ico|woff2?)(\?|$)/.test(url.pathname);

  if (isAsset) {
    // Cache First pour les assets statiques
    event.respondWith(
      caches.match(request).then((cached) => cached || fetch(request).then((response) => {
        const clone = response.clone();
        caches.open(CACHE_NAME).then((cache) => cache.put(request, clone));
        return response;
      }))
    );
  } else {
    // Network First pour les pages HTML
    event.respondWith(
      fetch(request).catch(() => caches.match(request).then((cached) => cached || caches.match('/login')))
    );
  }
});
