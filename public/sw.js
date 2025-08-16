const CACHE_NAME = 'alumni-platform-v2';
const STATIC_CACHE = 'alumni-static-v2';
const DYNAMIC_CACHE = 'alumni-dynamic-v2';
const IMAGE_CACHE = 'alumni-images-v2';
const API_CACHE = 'alumni-api-v2';

// Static assets to cache
const STATIC_ASSETS = [
  '/',
  '/offline',
  '/favicon.ico',
  '/favicon.svg',
  '/manifest.json',
  '/apple-touch-icon.png',
  '/android-chrome-192x192.png',
  '/android-chrome-512x512.png',
  // Critical pages for offline access
  '/dashboard',
  '/social/timeline',
  '/alumni/directory',
  '/profile',
  // Add critical CSS and JS files here
  // These will be populated by the build process
];

// API endpoints that should be cached
const CACHEABLE_APIS = [
  '/api/notifications',
  '/api/user/profile',
  '/api/alumni/directory',
  '/api/jobs/dashboard',
  '/api/events',
  '/api/search/global',
  '/api/social/timeline',
  '/api/connections',
  '/api/groups',
  '/api/circles'
];

// API endpoints that should never be cached
const NON_CACHEABLE_APIS = [
  '/api/auth/',
  '/api/logout',
  '/api/csrf-token',
  '/api/push/',
  '/sanctum/csrf-cookie'
];

// Cache expiration times (in milliseconds)
const CACHE_EXPIRATION = {
  static: 7 * 24 * 60 * 60 * 1000, // 7 days
  dynamic: 24 * 60 * 60 * 1000, // 24 hours
  api: 5 * 60 * 1000, // 5 minutes
  images: 30 * 24 * 60 * 60 * 1000 // 30 days
};

// Install event - cache static assets
self.addEventListener('install', event => {
  console.log('Service Worker: Installing...');
  
  event.waitUntil(
    caches.open(STATIC_CACHE)
      .then(cache => {
        console.log('Service Worker: Caching static assets');
        return cache.addAll(STATIC_ASSETS);
      })
      .then(() => {
        console.log('Service Worker: Static assets cached');
        return self.skipWaiting();
      })
      .catch(error => {
        console.error('Service Worker: Failed to cache static assets', error);
      })
  );
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
  console.log('Service Worker: Activating...');
  
  event.waitUntil(
    Promise.all([
      // Clean up old caches
      caches.keys().then(cacheNames => {
        return Promise.all(
          cacheNames.map(cacheName => {
            if (![STATIC_CACHE, DYNAMIC_CACHE, IMAGE_CACHE, API_CACHE].includes(cacheName)) {
              console.log('Service Worker: Deleting old cache', cacheName);
              return caches.delete(cacheName);
            }
          })
        );
      }),
      // Initialize IndexedDB for offline actions
      initializeOfflineStorage(),
      // Clean up expired cache entries
      cleanupExpiredCaches()
    ]).then(() => {
      console.log('Service Worker: Activated');
      return self.clients.claim();
    })
  );
});

// Fetch event - implement caching strategies
self.addEventListener('fetch', event => {
  const { request } = event;
  const url = new URL(request.url);
  
  // Skip non-GET requests for caching (but handle POST for offline queueing)
  if (request.method !== 'GET') {
    if (request.method === 'POST' || request.method === 'PUT' || request.method === 'DELETE') {
      event.respondWith(handleMutationRequest(request));
    }
    return;
  }
  
  // Skip chrome-extension and other non-http requests
  if (!url.protocol.startsWith('http')) {
    return;
  }
  
  // Handle different types of requests
  if (isStaticAsset(request)) {
    event.respondWith(cacheFirstStrategy(request, STATIC_CACHE));
  } else if (isImageRequest(request)) {
    event.respondWith(cacheFirstStrategy(request, IMAGE_CACHE));
  } else if (isAPIRequest(request)) {
    if (isCacheableAPI(request)) {
      event.respondWith(networkFirstWithTTL(request, API_CACHE, CACHE_EXPIRATION.api));
    } else {
      event.respondWith(networkOnlyStrategy(request));
    }
  } else if (isNavigationRequest(request)) {
    event.respondWith(navigationStrategy(request));
  } else {
    event.respondWith(staleWhileRevalidateStrategy(request, DYNAMIC_CACHE));
  }
});

// Cache-first strategy for static assets
async function cacheFirstStrategy(request, cacheName) {
  try {
    const cache = await caches.open(cacheName);
    const cachedResponse = await cache.match(request);
    
    if (cachedResponse) {
      return cachedResponse;
    }
    
    const networkResponse = await fetch(request);
    
    if (networkResponse.ok) {
      cache.put(request, networkResponse.clone());
    }
    
    return networkResponse;
  } catch (error) {
    console.error('Cache-first strategy failed:', error);
    return new Response('Offline content not available', { status: 503 });
  }
}

// Network-first strategy for API requests
async function networkFirstStrategy(request, cacheName) {
  try {
    const networkResponse = await fetch(request);
    
    if (networkResponse.ok) {
      const cache = await caches.open(cacheName);
      cache.put(request, networkResponse.clone());
    }
    
    return networkResponse;
  } catch (error) {
    console.log('Network failed, trying cache:', error);
    
    const cache = await caches.open(cacheName);
    const cachedResponse = await cache.match(request);
    
    if (cachedResponse) {
      return cachedResponse;
    }
    
    // Return offline response for API requests
    return new Response(JSON.stringify({
      error: 'Offline',
      message: 'This content is not available offline'
    }), {
      status: 503,
      headers: { 'Content-Type': 'application/json' }
    });
  }
}

// Navigation strategy for page requests
async function navigationStrategy(request) {
  try {
    const networkResponse = await fetch(request);
    
    if (networkResponse.ok) {
      const cache = await caches.open(DYNAMIC_CACHE);
      cache.put(request, networkResponse.clone());
    }
    
    return networkResponse;
  } catch (error) {
    console.log('Navigation failed, trying cache:', error);
    
    const cache = await caches.open(DYNAMIC_CACHE);
    const cachedResponse = await cache.match(request);
    
    if (cachedResponse) {
      return cachedResponse;
    }
    
    // Return offline page
    const offlineResponse = await cache.match('/offline');
    return offlineResponse || new Response('Offline', { status: 503 });
  }
}

// Stale-while-revalidate strategy
async function staleWhileRevalidateStrategy(request, cacheName) {
  const cache = await caches.open(cacheName);
  const cachedResponse = await cache.match(request);
  
  const fetchPromise = fetch(request).then(networkResponse => {
    if (networkResponse.ok) {
      cache.put(request, networkResponse.clone());
    }
    return networkResponse;
  }).catch(() => cachedResponse);
  
  return cachedResponse || fetchPromise;
}

// Helper functions
function isStaticAsset(request) {
  const url = new URL(request.url);
  return url.pathname.match(/\.(css|js|woff|woff2|ttf|eot|ico)$/);
}

function isImageRequest(request) {
  const url = new URL(request.url);
  return url.pathname.match(/\.(png|jpg|jpeg|gif|svg|webp|avif)$/);
}

function isAPIRequest(request) {
  const url = new URL(request.url);
  return url.pathname.startsWith('/api/');
}

function isCacheableAPI(request) {
  const url = new URL(request.url);
  
  // Check if it's in the non-cacheable list
  if (NON_CACHEABLE_APIS.some(api => url.pathname.startsWith(api))) {
    return false;
  }
  
  // Check if it's in the cacheable list
  return CACHEABLE_APIS.some(api => url.pathname.startsWith(api));
}

function isNavigationRequest(request) {
  return request.mode === 'navigate' || 
         (request.method === 'GET' && request.headers.get('accept').includes('text/html'));
}

// Background sync for offline actions
self.addEventListener('sync', event => {
  console.log('Service Worker: Background sync triggered', event.tag);
  
  if (event.tag === 'background-sync') {
    event.waitUntil(doBackgroundSync());
  }
});

async function doBackgroundSync() {
  try {
    // Process any queued offline actions
    const offlineActions = await getOfflineActions();
    
    for (const action of offlineActions) {
      try {
        await processOfflineAction(action);
        await removeOfflineAction(action.id);
      } catch (error) {
        console.error('Failed to process offline action:', error);
      }
    }
  } catch (error) {
    console.error('Background sync failed:', error);
  }
}

async function getOfflineActions() {
  // This would retrieve queued actions from IndexedDB
  return [];
}

async function processOfflineAction(action) {
  // This would process the queued action
  console.log('Processing offline action:', action);
}

async function removeOfflineAction(actionId) {
  // This would remove the processed action from IndexedDB
  console.log('Removing offline action:', actionId);
}

// Enhanced caching strategies
async function networkFirstWithTTL(request, cacheName, ttl) {
  try {
    const networkResponse = await fetch(request);
    
    if (networkResponse.ok) {
      const cache = await caches.open(cacheName);
      const responseToCache = networkResponse.clone();
      
      // Add timestamp for TTL
      const headers = new Headers(responseToCache.headers);
      headers.set('sw-cache-timestamp', Date.now().toString());
      
      const modifiedResponse = new Response(responseToCache.body, {
        status: responseToCache.status,
        statusText: responseToCache.statusText,
        headers: headers
      });
      
      cache.put(request, modifiedResponse);
    }
    
    return networkResponse;
  } catch (error) {
    console.log('Network failed, trying cache:', error);
    
    const cache = await caches.open(cacheName);
    const cachedResponse = await cache.match(request);
    
    if (cachedResponse) {
      // Check if cache is still valid
      const cacheTimestamp = cachedResponse.headers.get('sw-cache-timestamp');
      if (cacheTimestamp && (Date.now() - parseInt(cacheTimestamp)) < ttl) {
        return cachedResponse;
      } else {
        // Cache expired, remove it
        cache.delete(request);
      }
    }
    
    // Return offline response for API requests
    return new Response(JSON.stringify({
      error: 'Offline',
      message: 'This content is not available offline',
      cached: false
    }), {
      status: 503,
      headers: { 'Content-Type': 'application/json' }
    });
  }
}

async function networkOnlyStrategy(request) {
  try {
    return await fetch(request);
  } catch (error) {
    return new Response(JSON.stringify({
      error: 'Network Error',
      message: 'Unable to complete request while offline'
    }), {
      status: 503,
      headers: { 'Content-Type': 'application/json' }
    });
  }
}

async function handleMutationRequest(request) {
  try {
    // Try network first
    const response = await fetch(request);
    
    if (response.ok) {
      // Notify clients of successful action
      notifyClients({
        type: 'MUTATION_SUCCESS',
        url: request.url,
        method: request.method
      });
    }
    
    return response;
  } catch (error) {
    console.log('Mutation request failed, queueing for later:', error);
    
    // Queue the action for later
    await queueOfflineAction(request);
    
    // Return a response indicating the action was queued
    return new Response(JSON.stringify({
      success: true,
      queued: true,
      message: 'Action queued for when you\'re back online'
    }), {
      status: 202,
      headers: { 'Content-Type': 'application/json' }
    });
  }
}

// Offline storage management
async function initializeOfflineStorage() {
  // Initialize IndexedDB for offline actions
  return new Promise((resolve, reject) => {
    const request = indexedDB.open('AlumniPlatformOffline', 1);
    
    request.onerror = () => reject(request.error);
    request.onsuccess = () => resolve(request.result);
    
    request.onupgradeneeded = (event) => {
      const db = event.target.result;
      
      // Create object store for offline actions
      if (!db.objectStoreNames.contains('offlineActions')) {
        const store = db.createObjectStore('offlineActions', { keyPath: 'id', autoIncrement: true });
        store.createIndex('timestamp', 'timestamp', { unique: false });
        store.createIndex('type', 'type', { unique: false });
      }
      
      // Create object store for cached data
      if (!db.objectStoreNames.contains('cachedData')) {
        const store = db.createObjectStore('cachedData', { keyPath: 'key' });
        store.createIndex('timestamp', 'timestamp', { unique: false });
      }
    };
  });
}

async function queueOfflineAction(request) {
  try {
    const db = await initializeOfflineStorage();
    const transaction = db.transaction(['offlineActions'], 'readwrite');
    const store = transaction.objectStore('offlineActions');
    
    const action = {
      url: request.url,
      method: request.method,
      headers: Object.fromEntries(request.headers.entries()),
      body: request.method !== 'GET' ? await request.text() : null,
      timestamp: Date.now(),
      type: 'api_request'
    };
    
    await store.add(action);
    
    // Notify clients
    notifyClients({
      type: 'OFFLINE_ACTION_QUEUED',
      action: action
    });
    
    console.log('Offline action queued:', action);
  } catch (error) {
    console.error('Failed to queue offline action:', error);
  }
}

async function getOfflineActions() {
  try {
    const db = await initializeOfflineStorage();
    const transaction = db.transaction(['offlineActions'], 'readonly');
    const store = transaction.objectStore('offlineActions');
    
    return new Promise((resolve, reject) => {
      const request = store.getAll();
      request.onsuccess = () => resolve(request.result);
      request.onerror = () => reject(request.error);
    });
  } catch (error) {
    console.error('Failed to get offline actions:', error);
    return [];
  }
}

async function processOfflineAction(action) {
  try {
    const request = new Request(action.url, {
      method: action.method,
      headers: action.headers,
      body: action.body
    });
    
    const response = await fetch(request);
    
    if (response.ok) {
      console.log('Offline action processed successfully:', action);
      return true;
    } else {
      console.error('Offline action failed:', response.status, response.statusText);
      return false;
    }
  } catch (error) {
    console.error('Failed to process offline action:', error);
    return false;
  }
}

async function removeOfflineAction(actionId) {
  try {
    const db = await initializeOfflineStorage();
    const transaction = db.transaction(['offlineActions'], 'readwrite');
    const store = transaction.objectStore('offlineActions');
    
    await store.delete(actionId);
    console.log('Offline action removed:', actionId);
  } catch (error) {
    console.error('Failed to remove offline action:', error);
  }
}

async function cleanupExpiredCaches() {
  try {
    const cacheNames = [API_CACHE, DYNAMIC_CACHE, IMAGE_CACHE];
    
    for (const cacheName of cacheNames) {
      const cache = await caches.open(cacheName);
      const requests = await cache.keys();
      
      for (const request of requests) {
        const response = await cache.match(request);
        if (response) {
          const cacheTimestamp = response.headers.get('sw-cache-timestamp');
          if (cacheTimestamp) {
            const age = Date.now() - parseInt(cacheTimestamp);
            let maxAge = CACHE_EXPIRATION.dynamic;
            
            if (cacheName === API_CACHE) maxAge = CACHE_EXPIRATION.api;
            else if (cacheName === IMAGE_CACHE) maxAge = CACHE_EXPIRATION.images;
            
            if (age > maxAge) {
              await cache.delete(request);
              console.log('Expired cache entry removed:', request.url);
            }
          }
        }
      }
    }
  } catch (error) {
    console.error('Cache cleanup failed:', error);
  }
}

function notifyClients(message) {
  self.clients.matchAll().then(clients => {
    clients.forEach(client => {
      client.postMessage(message);
    });
  });
}

// Push notification handling
self.addEventListener('push', event => {
  console.log('Service Worker: Push notification received');
  
  const options = {
    body: 'You have new updates in your alumni network',
    icon: '/images/icons/icon-192x192.png',
    badge: '/images/icons/badge-72x72.png',
    vibrate: [100, 50, 100],
    data: {
      dateOfArrival: Date.now(),
      primaryKey: 1
    },
    actions: [
      {
        action: 'explore',
        title: 'View Updates',
        icon: '/images/icons/checkmark.png'
      },
      {
        action: 'close',
        title: 'Close',
        icon: '/images/icons/xmark.png'
      }
    ]
  };
  
  if (event.data) {
    const payload = event.data.json();
    options.body = payload.body || options.body;
    options.data = { ...options.data, ...payload.data };
  }
  
  event.waitUntil(
    self.registration.showNotification('Alumni Platform', options)
  );
});

// Notification click handling
self.addEventListener('notificationclick', event => {
  console.log('Service Worker: Notification clicked');
  
  event.notification.close();
  
  if (event.action === 'explore') {
    event.waitUntil(
      clients.openWindow('/dashboard')
    );
  } else if (event.action === 'close') {
    // Just close the notification
  } else {
    // Default action - open the app
    event.waitUntil(
      clients.openWindow('/')
    );
  }
});

console.log('Service Worker: Loaded');
