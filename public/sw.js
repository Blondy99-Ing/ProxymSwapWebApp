const CACHE_NAME = "proxymswap-v2";
const OFFLINE_URL = "/offline.html";

// 🔹 Installation : pré-charger les ressources essentielles
self.addEventListener("install", (event) => {
  self.skipWaiting(); // force la mise à jour immédiate du SW
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll([
        "/offline.html",
        "/manifest.webmanifest",
        "/css/app.css",
        "/js/app.js"
      ]);
    })
  );
});

// 🔹 Activation : supprimer les anciens caches
self.addEventListener("activate", (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(
        keys
          .filter((k) => k !== CACHE_NAME)
          .map((k) => caches.delete(k))
      )
    )
  );
  self.clients.claim(); // activer immédiatement le nouveau SW
});

// 🔹 Fetch : gestion des requêtes
self.addEventListener("fetch", (event) => {
  const req = event.request;

  // 🚫 Ne pas mettre en cache les requêtes dynamiques (API /swap etc.)
  if (req.method !== "GET" || req.url.includes("/swap")) {
    event.respondWith(fetch(req).catch(() => caches.match(OFFLINE_URL)));
    return;
  }

  // 🔹 Pour les pages (mode navigation)
  if (req.mode === "navigate") {
    event.respondWith(
      fetch(req)
        .then((res) => {
          const copy = res.clone();
          caches.open(CACHE_NAME).then((c) => c.put(req, copy));
          return res;
        })
        .catch(() => caches.match(OFFLINE_URL))
    );
    return;
  }

  // 🔹 Pour les assets statiques
  event.respondWith(
    caches.match(req).then((cached) => {
      if (cached) return cached;
      return fetch(req).then((res) => {
        const copy = res.clone();
        caches.open(CACHE_NAME).then((c) => c.put(req, copy));
        return res;
      });
    })
  );
});
