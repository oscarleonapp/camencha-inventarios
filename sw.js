// Service Worker para PWA - Sistema de Inventarios
const CACHE_NAME = 'inventario-claude-v1.3';
const OFFLINE_URL = '/inventario-claude/offline.php';

// Archivos críticos para cachear
const CORE_CACHE_FILES = [
  '/inventario-claude/',
  '/inventario-claude/index.php',
  '/inventario-claude/login.php',
  '/inventario-claude/pos.php',
  '/inventario-claude/inventarios.php',
  '/inventario-claude/productos.php',
  '/inventario-claude/qr_scan.php',
  '/inventario-claude/manual.php',
  '/inventario-claude/offline.php',
  
  // CSS (precache solo lo mínimo necesario; el resto usa network-first)
  '/inventario-claude/assets/css/admin.css',
  '/inventario-claude/estilos_dinamicos.css.php',
  
  // JavaScript
  '/inventario-claude/assets/js/admin.js',
  '/inventario-claude/assets/js/qr-scanner.min.js',
  
  // Librerías externas críticas
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
  'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
  'https://code.jquery.com/jquery-3.6.0.min.js',
  
  // Iconos PWA
  '/inventario-claude/assets/icons/icon-192x192.png',
  '/inventario-claude/assets/icons/icon-512x512.png'
];

// Archivos de datos para cachear temporalmente
const DATA_CACHE_FILES = [
  '/inventario-claude/ajax/',
  '/inventario-claude/includes/',
  '/inventario-claude/api/'
];

// Instalación del Service Worker
self.addEventListener('install', event => {
  console.log('[SW] Instalando Service Worker...');
  
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('[SW] Cacheando archivos críticos...');
        return cache.addAll(CORE_CACHE_FILES);
      })
      .then(() => {
        console.log('[SW] Instalación completada');
        return self.skipWaiting();
      })
      .catch(error => {
        console.error('[SW] Error durante instalación:', error);
      })
  );
});

// Activación del Service Worker
self.addEventListener('activate', event => {
  console.log('[SW] Activando Service Worker...');
  
  event.waitUntil(
    caches.keys()
      .then(cacheNames => {
        return Promise.all(
          cacheNames.map(cacheName => {
            if (cacheName !== CACHE_NAME) {
              console.log('[SW] Eliminando caché obsoleto:', cacheName);
              return caches.delete(cacheName);
            }
          })
        );
      })
      .then(() => {
        console.log('[SW] Activación completada');
        return self.clients.claim();
      })
  );
});

// Estrategia de cache para diferentes tipos de archivos
self.addEventListener('fetch', event => {
  const request = event.request;
  const url = new URL(request.url);
  
  // Solo manejar requests del mismo origen o CDNs específicos
  if (!url.origin.includes(location.origin) && 
      !url.origin.includes('cdn.jsdelivr.net') && 
      !url.origin.includes('cdnjs.cloudflare.com') &&
      !url.origin.includes('code.jquery.com')) {
    return;
  }
  
  // Estrategia diferente según el tipo de archivo
  if (request.method === 'GET') {
    // CSS: usar network-first para obtener estilos más recientes
    if (isCSS(request)) {
      event.respondWith(networkFirstStrategy(request));
    }
    
    // Para archivos estáticos (CSS, JS, imágenes)
    else if (isStaticAsset(request)) {
      event.respondWith(cacheFirstStrategy(request));
    }
    
    // Para páginas HTML principales
    else if (isNavigationRequest(request)) {
      event.respondWith(networkFirstStrategy(request));
    }
    
    // Para APIs y datos dinámicos
    else if (isAPIRequest(request)) {
      event.respondWith(networkOnlyStrategy(request));
    }
    
    // Para requests de login/autenticación
    else if (isAuthRequest(request)) {
      event.respondWith(networkOnlyStrategy(request));
    }
    
    // Fallback general
    else {
      event.respondWith(networkFirstStrategy(request));
    }
  }
});

// Estrategia Cache First (para assets estáticos)
async function cacheFirstStrategy(request) {
  try {
    const cachedResponse = await caches.match(request);
    if (cachedResponse) {
      return cachedResponse;
    }
    
    const networkResponse = await fetch(request);
    if (networkResponse.ok) {
      const cache = await caches.open(CACHE_NAME);
      cache.put(request, networkResponse.clone());
    }
    return networkResponse;
    
  } catch (error) {
    console.log('[SW] Cache First falló:', error);
    return caches.match(request);
  }
}

// Estrategia Network First (para contenido dinámico)
async function networkFirstStrategy(request) {
  try {
    const networkResponse = await fetch(request);
    
    if (networkResponse.ok) {
      const cache = await caches.open(CACHE_NAME);
      cache.put(request, networkResponse.clone());
    }
    return networkResponse;
    
  } catch (error) {
    console.log('[SW] Network falló, buscando en cache:', error);
    const cachedResponse = await caches.match(request);
    
    if (cachedResponse) {
      return cachedResponse;
    }
    
    // Página offline para navegación
    if (isNavigationRequest(request)) {
      return caches.match(OFFLINE_URL);
    }
    
    throw error;
  }
}

// Estrategia Network Only (para datos críticos)
async function networkOnlyStrategy(request) {
  try {
    return await fetch(request);
  } catch (error) {
    console.log('[SW] Network Only falló:', error);
    
    // Para requests críticos, retornar error específico
    return new Response(
      JSON.stringify({ 
        error: 'Sin conexión', 
        message: 'Esta función requiere conexión a internet',
        offline: true 
      }), 
      { 
        status: 503,
        statusText: 'Service Unavailable',
        headers: { 'Content-Type': 'application/json' }
      }
    );
  }
}

// Helpers para identificar tipos de requests
function isStaticAsset(request) {
  const url = new URL(request.url);
  return url.pathname.match(/\.(css|js|png|jpg|jpeg|gif|svg|ico|woff2?|ttf|eot)$/);
}

function isCSS(request) {
  const url = new URL(request.url);
  return url.pathname.endsWith('.css');
}

function isNavigationRequest(request) {
  return request.mode === 'navigate' || 
         (request.method === 'GET' && request.headers.get('accept').includes('text/html'));
}

function isAPIRequest(request) {
  const url = new URL(request.url);
  return url.pathname.includes('/ajax/') || 
         url.pathname.includes('/api/') ||
         url.pathname.includes('/includes/') ||
         url.searchParams.has('action');
}

function isAuthRequest(request) {
  const url = new URL(request.url);
  return url.pathname.includes('login') || 
         url.pathname.includes('logout') ||
         url.pathname.includes('auth');
}

// Manejo de mensajes desde la aplicación
self.addEventListener('message', event => {
  if (event.data && event.data.type) {
    
    switch (event.data.type) {
      case 'SKIP_WAITING':
        self.skipWaiting();
        break;
        
      case 'CACHE_UPDATE':
        updateCache();
        break;
        
      case 'CLEAR_CACHE':
        clearAllCaches();
        break;
        
      case 'GET_CACHE_STATUS':
        getCacheStatus().then(status => {
          event.ports[0].postMessage(status);
        });
        break;
    }
  }
});

// Función para actualizar cache manualmente
async function updateCache() {
  try {
    const cache = await caches.open(CACHE_NAME);
    await cache.addAll(CORE_CACHE_FILES);
    console.log('[SW] Cache actualizado exitosamente');
  } catch (error) {
    console.error('[SW] Error actualizando cache:', error);
  }
}

// Función para limpiar todas las caches
async function clearAllCaches() {
  try {
    const cacheNames = await caches.keys();
    await Promise.all(cacheNames.map(name => caches.delete(name)));
    console.log('[SW] Todas las caches eliminadas');
  } catch (error) {
    console.error('[SW] Error limpiando caches:', error);
  }
}

// Función para obtener estado del cache
async function getCacheStatus() {
  try {
    const cache = await caches.open(CACHE_NAME);
    const keys = await cache.keys();
    return {
      cacheName: CACHE_NAME,
      cachedFiles: keys.length,
      isOnline: navigator.onLine
    };
  } catch (error) {
    return {
      cacheName: CACHE_NAME,
      cachedFiles: 0,
      isOnline: navigator.onLine,
      error: error.message
    };
  }
}

// Sincronización en background (cuando esté disponible)
self.addEventListener('sync', event => {
  console.log('[SW] Background Sync:', event.tag);
  
  if (event.tag === 'inventory-sync') {
    event.waitUntil(syncInventoryData());
  }
});

// Función para sincronizar datos de inventario
async function syncInventoryData() {
  try {
    // Aquí podrías implementar lógica para sincronizar
    // datos pendientes cuando se recupere la conexión
    console.log('[SW] Sincronizando datos de inventario...');
    
    // Ejemplo: enviar datos guardados offline
    // const pendingData = await getPendingData();
    // await sendDataToServer(pendingData);
    
  } catch (error) {
    console.error('[SW] Error en sincronización:', error);
    throw error;
  }
}

// Notificaciones push (para futuras implementaciones)
self.addEventListener('push', event => {
  if (event.data) {
    const data = event.data.json();
    
    const options = {
      body: data.body || 'Nueva notificación del sistema',
      icon: '/inventario-claude/assets/icons/icon-192x192.png',
      badge: '/inventario-claude/assets/icons/icon-72x72.png',
      vibrate: [200, 100, 200],
      data: data.data || {},
      actions: [
        {
          action: 'open',
          title: 'Abrir Sistema',
          icon: '/inventario-claude/assets/icons/icon-96x96.png'
        },
        {
          action: 'close',
          title: 'Cerrar'
        }
      ]
    };
    
    event.waitUntil(
      self.registration.showNotification(data.title || 'Sistema de Inventarios', options)
    );
  }
});

// Manejo de clicks en notificaciones
self.addEventListener('notificationclick', event => {
  event.notification.close();
  
  if (event.action === 'open' || !event.action) {
    event.waitUntil(
      clients.openWindow('/inventario-claude/')
    );
  }
});

console.log('[SW] Service Worker cargado exitosamente');
