// sw.js - BULLETPROOF VERSION
const CACHE_NAME = "aegis-v3"; // Version bump to force update

// EXACT paths are critical. 
// If your file is named "App.php" (capital A), this will fail.
const ASSETS = [
    "/",
    "/app.php",
    "/app.js",
    "/manifest.json",
    "https://cdn.tailwindcss.com"
];

// 1. Install Phase (The Critical Part)
self.addEventListener("install", (event) => {
    console.log("[SW] Installing... Starting Cache.");

    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            // We use 'return' to make sure the promise completes
            return cache.addAll(ASSETS).then(() => {
                console.log("[SW] All files cached successfully!");
                self.skipWaiting(); // Force activation
            }).catch((err) => {
                console.error("[SW] Cache Failed!", err);
            });
        })
    );
});

// 2. Activate Phase (Clean up old versions)
self.addEventListener("activate", (event) => {
    console.log("[SW] Activated. Cleaning old caches.");
    event.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys.map((key) => {
                    if (key !== CACHE_NAME) return caches.delete(key);
                })
            );
        })
    );
    self.clients.claim();
});

// 3. Fetch Phase (Serve from Cache if offline)
self.addEventListener("fetch", (event) => {
    // Only intercept GET requests
    if (event.request.method !== 'GET') return;

    event.respondWith(
        fetch(event.request)
            .then((response) => {
                // If network works, return response AND update cache
                // CHECK SCHEME: Only cache http and https
                if (event.request.url.startsWith('http')) {
                    const resClone = response.clone();
                    caches.open(CACHE_NAME).then((cache) => {
                        cache.put(event.request, resClone);
                    });
                }
                return response;
            })
            .catch(() => {
                // If network fails, look in cache
                return caches.match(event.request).then((response) => {
                    if (response) return response;
                    // Fallback for root URL to app.php
                    if (event.request.mode === 'navigate') {
                        return caches.match('/app.php');
                    }
                });
            })
    );
});