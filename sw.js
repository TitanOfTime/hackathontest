// sw.js
const CACHE_NAME = "aegis-v1";
const ASSETS = [
    "/app.php",
    "/app.js",
    "https://cdn.tailwindcss.com"
];

self.addEventListener("install", e => {
    e.waitUntil(caches.open(CACHE_NAME).then(cache => cache.addAll(ASSETS)));
});

self.addEventListener("fetch", e => {
    e.respondWith(
        fetch(e.request).catch(() => caches.match(e.request))
    );
});