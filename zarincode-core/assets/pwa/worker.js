/* CONFIG is supplied by the same-origin WordPress endpoint. */
'use strict';
const CACHE = CONFIG.prefix + CONFIG.version;
const offlineURL = new URL(CONFIG.offline, self.location.href).href;
const staticURLs = new Set(CONFIG.assets.map(url => new URL(url, self.location.href).href));
self.addEventListener('install', event => {
    if (!CONFIG.enabled) { self.skipWaiting(); return; }
    event.waitUntil((async () => {
        const cache = await caches.open(CACHE);
        // A failed offline shell install must not replace a working worker.
        const response = await fetch(offlineURL, {cache: 'reload', credentials: 'omit'});
        if (!response.ok || !response.headers.get('content-type')?.includes('text/html')) throw new Error('Offline shell unavailable');
        await cache.put(offlineURL, response);
        await Promise.all([...staticURLs].map(async url => {
            try {
                const asset = await fetch(url, {credentials: 'omit', cache: 'reload'});
                if (asset.ok && asset.type !== 'opaque') await cache.put(url, asset);
            } catch (_) { /* Icon failure must not prevent offline recovery. */ }
        }));
    })());
});
self.addEventListener('activate', event => event.waitUntil((async () => {
    await Promise.all((await caches.keys()).filter(key => key.startsWith(CONFIG.prefix) && (!CONFIG.enabled || key !== CACHE)).map(key => caches.delete(key)));
    if (!CONFIG.enabled) { await self.registration.unregister(); return; }
    await self.clients.claim();
})()));
self.addEventListener('message', event => {
    if (event.data?.type === 'ZC_ACTIVATE_UPDATE') self.skipWaiting();
});
self.addEventListener('fetch', event => {
    const request = event.request;
    const url = new URL(request.url);
    if (!CONFIG.enabled || request.method !== 'GET' || url.origin !== self.location.origin) return;
    // All navigations are network-only. No account/cart/checkout, nonce, REST,
    // API or HTML response is ever written to CacheStorage.
    if (request.mode === 'navigate') {
        event.respondWith(fetch(request).catch(async () =>
            (await caches.open(CACHE)).match(offlineURL).then(response => response || new Response('Offline', {status: 503, headers: {'Content-Type': 'text/plain; charset=utf-8'}}))
        ));
        return;
    }
    // Exact allowlist, including query string. Never cache arbitrary uploads.
    if (staticURLs.has(url.href)) {
        event.respondWith((async () => (await caches.open(CACHE)).match(request).then(hit => hit || fetch(request)))());
    }
});
