// Service worker JogjaTrip — cache halaman yang pernah dibuka (terutama
// itinerary) supaya tetap bisa diakses saat sinyal lemah/offline, dan
// tampilkan halaman fallback kalau suatu halaman belum pernah tersimpan.

const CACHE_VERSION = 'jogjatrip-v2';
const PAGES_CACHE = `${CACHE_VERSION}-pages`;
const ASSETS_CACHE = `${CACHE_VERSION}-assets`;
const OFFLINE_URL = '/offline';

const PRECACHE_URLS = [
    OFFLINE_URL,
    '/manifest.json',
    '/icons/icon-192.png',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(PAGES_CACHE)
            .then((cache) => cache.addAll(PRECACHE_URLS))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(
                keys
                    .filter((key) => key.startsWith('jogjatrip-') && key !== PAGES_CACHE && key !== ASSETS_CACHE)
                    .map((key) => caches.delete(key))
            ))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const { request } = event;

    // Hanya tangani request same-origin GET; biarkan request ke API pihak
    // ketiga (Gemini, Open-Meteo, tile peta, font) lewat apa adanya.
    if (request.method !== 'GET' || new URL(request.url).origin !== self.location.origin) {
        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(networkFirstForPages(request));
        return;
    }

    if (['style', 'script', 'image', 'font'].includes(request.destination)) {
        event.respondWith(cacheFirstForAssets(request));
    }
});

async function networkFirstForPages(request) {
    try {
        const response = await fetch(request);

        // Jangan simpan halaman pribadi (no-store), hasil redirect, atau respons non-basic.
        const cacheControl = response.headers.get('Cache-Control') || '';
        if (response.ok && !response.redirected && response.type === 'basic' && !cacheControl.includes('no-store') && (response.headers.get('Content-Type') || '').includes('text/html')) {
            const cache = await caches.open(PAGES_CACHE);
            cache.put(request, response.clone());
        }

        return response;
    } catch (error) {
        const cached = await caches.match(request);

        return cached || caches.match(OFFLINE_URL);
    }
}

async function cacheFirstForAssets(request) {
    const cached = await caches.match(request);

    if (cached) {
        return cached;
    }

    try {
        const response = await fetch(request);
        const cache = await caches.open(ASSETS_CACHE);
        cache.put(request, response.clone());

        return response;
    } catch (error) {
        return cached;
    }
}
