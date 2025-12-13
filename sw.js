// sw.js
const CACHE_NAME = "aegis-v2"; // Changed version to force update
const ASSETS = [
    "/",
    "/app.php",
    "/app.js",
    "https://cdn.tailwindcss.com"
];

// 1. Install: Cache files
self.addEventListener("install", e => {
    console.log("[SW] Installing...");
    e.waitUntil(
        caches.open(CACHE_NAME).then(cache => {
            console.log("[SW] Caching files");
            return cache.addAll(ASSETS);
        })
    );
    self.skipWaiting(); // Force activation immediately
});

// 2. Activate: Clean old caches
self.addEventListener("activate", e => {
    console.log("[SW] Activated");
    e.waitUntil(
        caches.keys().then(keys => {
            return Promise.all(keys.map(key => {
                if(key !== CACHE_NAME) return caches.delete(key);
            }));
        })
    );
    return self.clients.claim();
});

// 3. Fetch: Network First, then Cache (Better for dev)
self.addEventListener("fetch", e => {
    e.respondWith(
        fetch(e.request)
            .then(res => {
                // If online, clone response to cache and return
                const resClone = res.clone();
                caches.open(CACHE_NAME).then(cache => {
                    cache.put(e.request, resClone);
                });
                return res;
            })
            .catch(() => caches.match(e.request)) // If offline, return cache
    );
});