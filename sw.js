const CACHE_NAME = 'cyber-cafe-v2';
const APP_PREFIX = '/Cyber%20Cafe%20Management%20System/';

const urlsToCache = [
  APP_PREFIX + 'offline.html',
  APP_PREFIX + 'manifest.json',
  APP_PREFIX + 'login.php'
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('Opened cache');
        return cache.addAll(urlsToCache);
      })
  );
});

self.addEventListener('fetch', event => {
  event.respondWith(
    caches.match(event.request)
      .then(response => {
        if (response) {
          return response;
        }
        return fetch(event.request).catch(() => {
            // Return offline page for navigation requests
            if (event.request.mode === 'navigate') {
                return caches.match(APP_PREFIX + 'offline.html');
            }
        });
      })
  );
});

// Activate: Clean old caches
self.addEventListener('activate', event => {
  const cacheWhitelist = [CACHE_NAME];
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheWhitelist.indexOf(cacheName) === -1) {
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
});

// Push Notification Event (Placeholder for future)
self.addEventListener('push', function(event) {
  const title = 'Cyber Cafe Update';
  const options = {
    body: event.data ? event.data.text() : 'New notification',
    icon: 'https://cdn-icons-png.flaticon.com/512/2936/2936886.png',
    badge: 'https://cdn-icons-png.flaticon.com/512/2936/2936886.png'
  };
  event.waitUntil(self.registration.showNotification(title, options));
});
