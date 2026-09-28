{{-- Service worker of the courier app (F-125): network first, so the courier always sees fresh deliveries; a
     simple offline message when the network drops. Nothing personal is cached. --}}
const OFFLINE = `<!doctype html><html lang="fr"><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Hors connexion</title><body style="font:16px system-ui;padding:24px;text-align:center"><h1 style="font-size:20px">Pas de connexion</h1><p>Vérifiez vos données mobiles puis réessayez.</p><button onclick="location.reload()" style="min-height:52px;padding:0 24px;border:0;border-radius:12px;background:#1d3fbf;color:#fff;font:inherit;font-weight:700">Réessayer</button></body></html>`;

self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', (event) => event.waitUntil(self.clients.claim()));

self.addEventListener('fetch', (event) => {
    if (event.request.mode !== 'navigate') {
        return;
    }

    event.respondWith(fetch(event.request).catch(() => new Response(OFFLINE, { headers: { 'Content-Type': 'text/html; charset=utf-8' } })));
});
