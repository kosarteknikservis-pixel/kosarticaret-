/* Koşar destek asistanı bildirimleri. Yalnızca push gösterir; sayfa isteklerine (fetch) karışmaz. */
'use strict';

self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', (event) => event.waitUntil(self.clients.claim()));

self.addEventListener('push', (event) => {
    let data = {};
    try {
        data = event.data ? event.data.json() : {};
    } catch (e) {
        data = { body: event.data ? event.data.text() : '' };
    }

    const url = new URL(data.url || '/#destek-asistani', self.location.origin);
    const show = self.registration.showNotification(data.title || 'Koşar Ticaret', {
        body: data.body || 'Destek ekibimiz sorunuza yanıt verdi.',
        icon: data.icon || undefined,
        tag: data.tag || 'kc-support',
        renotify: true,
        data: { url: url.origin === self.location.origin ? url.href : self.location.origin + '/#destek-asistani' },
    });
    const ping = self.clients.matchAll({ type: 'window', includeUncontrolled: true })
        .then((list) => list.forEach((client) => client.postMessage({ type: 'kc-support-reply' })));

    event.waitUntil(Promise.all([show, ping]));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const target = (event.notification.data && event.notification.data.url) || self.location.origin + '/#destek-asistani';

    event.waitUntil(self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((list) => {
        const client = list.find((item) => new URL(item.url).origin === self.location.origin);
        if (client && 'focus' in client) {
            client.postMessage({ type: 'kc-support-open' });
            return client.focus();
        }
        return self.clients.openWindow(target);
    }));
});
