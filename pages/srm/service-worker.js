/**
 * service-worker.js — StokPro Cache Manager
 * 
 * Di-generate otomatis oleh sw-register snippet di setiap halaman.
 * CACHE_VERSION di-inject dari PHP saat halaman dimuat, sehingga
 * setiap deploy baru langsung membatalkan cache lama.
 */

// CACHE_VERSION di-replace saat runtime via sw-register di halaman
const CACHE_VERSION = self.__CACHE_VERSION || 'v1';
const CACHE_NAME = 'stokpro-' + CACHE_VERSION;

// Aset yang di-cache saat install
const PRECACHE_ASSETS = [
    'style.css',
    'app.js',
];

// ── Install: cache aset statis ─────────────────────────────────────────────
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => {
            // Cache aset dengan versi query string yang sudah ada di URL
            return cache.addAll(PRECACHE_ASSETS).catch(() => {
                // Jika gagal, lanjut saja (offline support opsional)
            });
        }).then(() => self.skipWaiting())
    );
});

// ── Activate: hapus cache lama ─────────────────────────────────────────────
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys =>
            Promise.all(
                keys
                    .filter(key => key.startsWith('stokpro-') && key !== CACHE_NAME)
                    .map(key => {
                        console.log('[SW] Menghapus cache lama:', key);
                        return caches.delete(key);
                    })
            )
        ).then(() => self.clients.claim())
    );
});

// ── Fetch: Network-first untuk PHP, Cache-first untuk aset statis ──────────
self.addEventListener('fetch', event => {
    const url = new URL(event.request.url);

    // Abaikan request non-GET dan request ke domain lain
    if (event.request.method !== 'GET') return;
    if (url.origin !== self.location.origin) return;

    const isAsset = /\.(css|js|woff2?|ttf|svg|png|ico)(\?.*)?$/.test(url.pathname);
    const isPhp   = url.pathname.endsWith('.php') || url.pathname === '/';

    if (isPhp) {
        // PHP halaman: Network-first, fallback ke cache
        event.respondWith(
            fetch(event.request)
                .then(response => {
                    const clone = response.clone();
                    caches.open(CACHE_NAME).then(cache => cache.put(event.request, clone));
                    return response;
                })
                .catch(() => caches.match(event.request))
        );
    } else if (isAsset) {
        // Aset CSS/JS: Cache-first, refetch jika tidak ada
        event.respondWith(
            caches.match(event.request).then(cached => {
                if (cached) return cached;
                return fetch(event.request).then(response => {
                    const clone = response.clone();
                    caches.open(CACHE_NAME).then(cache => cache.put(event.request, clone));
                    return response;
                });
            })
        );
    }
});
