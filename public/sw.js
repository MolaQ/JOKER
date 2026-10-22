// Service worker strony Joker Piła: wyświetla powiadomienia push i otwiera stronę po kliknięciu.
self.addEventListener('push', (event) => {
    let data = {};
    try {
        data = event.data ? event.data.json() : {};
    } catch (error) {
        data = { title: 'Joker Piła', body: event.data ? event.data.text() : '' };
    }

    event.waitUntil(self.registration.showNotification(data.title || 'Joker Piła', {
        body: data.body || '',
        icon: data.icon,
        badge: data.badge,
        tag: data.tag || undefined,
        data: { url: data.url || self.registration.scope },
    }));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const url = event.notification.data && event.notification.data.url ? event.notification.data.url : self.registration.scope;

    event.waitUntil(self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windows) => {
        for (const client of windows) {
            if (client.url === url && 'focus' in client) {
                return client.focus();
            }
        }

        return self.clients.openWindow(url);
    }));
});
