/* MTRTS Technician Ops — Offline cache (PWA) */
const CACHE_NAME = 'mtrts-technician-v1';

// Keep this list small and high-value; pages will still be cached opportunistically.
const CORE_ASSETS = [
  '/mtrts-main/modules/technician/index.php',
  '/mtrts-main/modules/technician/view.php',
  '/mtrts-main/public/manifest.json',
  '/mtrts-main/public/assets/images/logo.png',
  '/mtrts-main/public/assets/js/technician/offline.js',
  '/mtrts-main/public/assets/js/technician/jobs.js',
  '/mtrts-main/public/assets/js/technician/workorder.js',
  '/mtrts-main/public/assets/js/technician/signature.js'
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => cache.addAll(CORE_ASSETS))
      .then(() => self.skipWaiting())
      .catch(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(keys.map((k) => (k === CACHE_NAME ? null : caches.delete(k)))))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  const url = new URL(event.request.url);

  // Network-first for dynamic/PHP routes and APIs.
  const isPhp = url.pathname.endsWith('.php');
  const isApi = url.pathname.includes('/modules/technician/') && (
    url.pathname.endsWith('/sync.php') || url.pathname.endsWith('/ping.php')
  );

  if (isApi || isPhp) {
    event.respondWith(
      fetch(event.request)
        .then((resp) => {
          const copy = resp.clone();
          caches.open(CACHE_NAME).then((cache) => cache.put(event.request, copy)).catch(() => {});
          return resp;
        })
        .catch(() => caches.match(event.request))
    );
    return;
  }

  // Cache-first for static assets.
  event.respondWith(
    caches.match(event.request).then((cached) => {
      if (cached) return cached;
      return fetch(event.request).then((resp) => {
        const copy = resp.clone();
        caches.open(CACHE_NAME).then((cache) => cache.put(event.request, copy)).catch(() => {});
        return resp;
      });
    })
  );
});

