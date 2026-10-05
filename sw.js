var CACHE_NAME = 'pdfvault-v4';

self.addEventListener('install', function(event) {
    self.skipWaiting();
});

self.addEventListener('activate', function(event) {
    event.waitUntil(
        caches.keys().then(function(names) {
            return Promise.all(
                names.filter(function(n) { return n !== CACHE_NAME; })
                     .map(function(n) { return caches.delete(n); })
            );
        })
    );
    self.clients.claim();
});

self.addEventListener('fetch', function(event) {
    // Never cache PHP / dynamic requests
    if (event.request.url.indexOf('.php') !== -1 || event.request.method !== 'GET') {
        return;
    }

    event.respondWith(
        caches.match(event.request).then(function(cached) {
            var fetchPromise = fetch(event.request).then(function(response) {
                if (response && response.status === 200) {
                    var clone = response.clone();
                    caches.open(CACHE_NAME).then(function(cache) {
                        cache.put(event.request, clone);
                    });
                }
                return response;
            }).catch(function() {
                return cached;
            });
            return cached || fetchPromise;
        })
    );
});

// ── Notification click → focus or open the app ──
self.addEventListener('notificationclick', function(event) {
    event.notification.close();

    var target = './index.php';
    if (event.notification.data && event.notification.data.url) {
        target = event.notification.data.url;
    }

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function(list) {
            for (var i = 0; i < list.length; i++) {
                var c = list[i];
                if ('focus' in c) {
                    if ('navigate' in c && target) {
                        try { c.navigate(target); } catch (e) {}
                    }
                    return c.focus();
                }
            }
            if (clients.openWindow) {
                return clients.openWindow(target);
            }
        })
    );
});

// ── Optional: real Web Push payload support ──
self.addEventListener('push', function(event) {
    var data = { title: 'مخزن PDF', body: '', url: './index.php' };
    if (event.data) {
        try { data = Object.assign(data, event.data.json()); }
        catch (e) { data.body = event.data.text(); }
    }
    event.waitUntil(
        self.registration.showNotification(data.title, {
            body: data.body,
            icon: './icon.png',
            badge: './icon.png',
            dir: 'rtl',
            lang: 'fa',
            vibrate: [90, 45, 90],
            data: { url: data.url }
        })
    );
});
