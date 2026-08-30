// Santa Fe Beach Club - Lightweight Service Worker (PWA Offline / Cache Support)
const CACHE_NAME = 'sbc-cache-v1';
const ASSETS_TO_CACHE = [
    './assets/logo.jpg',
    './assets/css/style.css',
    './assets/js/dark-mode-toggle.js'
];

self.addEventListener('install', (event) => {
    self.skipWaiting();
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(ASSETS_TO_CACHE).catch(() => {});
        })
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))
            );
        })
    );
});

self.addEventListener('fetch', (event) => {
    // Let normal network requests proceed
    event.respondWith(
        fetch(event.request).catch(() => caches.match(event.request))
    );
});
