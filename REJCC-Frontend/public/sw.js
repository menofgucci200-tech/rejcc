/*
 * Service worker de l'application REJCC : notifications sur l'appareil
 * (Web Push) et page de secours quand le réseau est coupé. Les pages ne
 * sont pas mises en cache : le contenu affiché est toujours celui du jour.
 */
const VERSION = 'rejcc-v1';
const HORS_LIGNE = '/hors-ligne.html';

self.addEventListener('install', (e) => {
    e.waitUntil(caches.open(VERSION).then((c) => c.addAll([HORS_LIGNE, '/app/icone-192.png'])).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (e) => {
    e.waitUntil(caches.keys().then((cles) => Promise.all(cles.filter((c) => c !== VERSION).map((c) => caches.delete(c)))).then(() => self.clients.claim()));
});

// Navigation sans réseau : page de secours plutôt que l'erreur du navigateur.
self.addEventListener('fetch', (e) => {
    if (e.request.mode !== 'navigate') return;
    e.respondWith(fetch(e.request).catch(() => caches.match(HORS_LIGNE)));
});

self.addEventListener('push', (e) => {
    let d = {};
    try { d = e.data ? e.data.json() : {}; } catch (_) { d = { texte: e.data ? e.data.text() : '' }; }
    e.waitUntil(self.registration.showNotification(d.titre || 'REJCC', {
        body: d.texte || '',
        icon: '/app/icone-192.png',
        badge: '/app/badge-96.png',
        tag: d.tag || undefined,
        renotify: Boolean(d.tag),
        lang: 'fr',
        data: { lien: d.lien || '/espace-membre/notifications' },
    }));
});

self.addEventListener('notificationclick', (e) => {
    e.notification.close();
    const lien = new URL(e.notification.data?.lien || '/espace-membre', self.location.origin).href;
    e.waitUntil(self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((fenetres) => {
        const ouverte = fenetres.find((f) => f.url.startsWith(self.location.origin));
        if (ouverte) {
            return ouverte.focus().then((f) => (f && 'navigate' in f ? f.navigate(lien) : null));
        }
        return self.clients.openWindow(lien);
    }));
});
