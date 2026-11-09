self.addEventListener('install', (event) => {
    event.waitUntil(self.skipWaiting());
});

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('push', (event) => {
    let message = { title: 'Daily Planner reminder', body: 'You have a task coming up.' };
    try {
        if (event.data) message = { ...message, ...event.data.json() };
    } catch {
        if (event.data) message.body = event.data.text();
    }
    event.waitUntil(self.registration.showNotification(message.title, {
        body: message.body,
        icon: message.icon || '/icons/planner-192.png',
        badge: '/icons/planner-192.png',
        tag: message.tag || 'planner-reminder',
        data: { url: message.url || '/' },
    }));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const target = new URL(event.notification.data?.url || '/', self.location.origin).href;
    event.waitUntil(self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clients) => {
        const existing = clients.find((client) => client.url === target);
        return existing ? existing.focus() : self.clients.openWindow(target);
    }));
});
