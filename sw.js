// Anant Staff — minimal service worker (makes the staff app installable; always uses the network)
self.addEventListener('install', e => self.skipWaiting());
self.addEventListener('activate', e => self.clients.claim());
self.addEventListener('fetch', () => {});
