<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sin Conexión - Sistema de Inventarios</title>
    
    <!-- Meta tags PWA -->
    <meta name="theme-color" content="#007bff">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Inventario">
    
    <!-- CSS inline para funcionar offline -->
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #333;
        }
        
        .offline-container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            max-width: 500px;
            width: 90%;
            margin: 20px;
        }
        
        .offline-icon {
            font-size: 80px;
            color: #007bff;
            margin-bottom: 20px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        h1 {
            color: #333;
            margin-bottom: 15px;
            font-size: 2rem;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 1.1rem;
            line-height: 1.5;
        }
        
        .features {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin: 25px 0;
            text-align: left;
        }
        
        .features h3 {
            color: #007bff;
            margin-bottom: 15px;
            font-size: 1.2rem;
        }
        
        .features ul {
            list-style: none;
            padding: 0;
        }
        
        .features li {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
            position: relative;
            padding-left: 25px;
        }
        
        .features li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #28a745;
            font-weight: bold;
        }
        
        .features li:last-child {
            border-bottom: none;
        }
        
        .btn-group {
            margin-top: 30px;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 25px;
            margin: 10px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 1rem;
        }
        
        .btn:hover {
            background: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,123,255,0.3);
        }
        
        .btn-outline {
            background: transparent;
            border: 2px solid #007bff;
            color: #007bff;
        }
        
        .btn-outline:hover {
            background: #007bff;
            color: white;
        }
        
        .connection-status {
            margin-top: 20px;
            padding: 15px;
            border-radius: 10px;
            font-weight: 500;
        }
        
        .online {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .offline {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #007bff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 10px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        @media (max-width: 600px) {
            .offline-container {
                padding: 30px 20px;
            }
            
            h1 {
                font-size: 1.5rem;
            }
            
            .offline-icon {
                font-size: 60px;
            }
            
            .btn {
                display: block;
                margin: 10px 0;
            }
        }
    </style>
</head>
<body>
    <div class="offline-container">
        <div class="offline-icon">📡</div>
        
        <h1>Sin Conexión a Internet</h1>
        <p class="subtitle">
            No tienes conexión a internet en este momento, pero puedes seguir usando algunas funciones del sistema.
        </p>
        
        <div class="features">
            <h3>Funciones Disponibles Sin Conexión:</h3>
            <ul>
                <li>Consultar productos en cache</li>
                <li>Ver manual de usuario completo</li>
                <li>Acceder a páginas visitadas recientemente</li>
                <li>Usar escáner QR (productos en cache)</li>
                <li>Ver información básica del sistema</li>
            </ul>
        </div>
        
        <div class="features">
            <h3>Se Sincronizará Automáticamente:</h3>
            <ul>
                <li>Inventarios y stock actualizado</li>
                <li>Nuevas ventas y transacciones</li>
                <li>Cambios de configuración</li>
                <li>Reportes en tiempo real</li>
            </ul>
        </div>
        
        <div id="connectionStatus" class="connection-status offline">
            🔴 Sin conexión a internet
        </div>
        
        <div class="btn-group rs-wrap-sm">
            <button onclick="checkConnection()" class="btn">
                <span id="checkLoader" style="display:none;" class="loading"></span>
                Verificar Conexión
            </button>
            
            <a href="manual.php" class="btn btn-outline">
                📖 Ver Manual
            </a>
        </div>
        
        <div class="btn-group rs-wrap-sm">
            <button onclick="goHome()" class="btn btn-outline">
                🏠 Ir al Inicio
            </button>
            
            <button onclick="clearCache()" class="btn btn-outline">
                🔄 Limpiar Cache
            </button>
        </div>
    </div>

    <script>
        // Verificar conexión automáticamente
        let connectionCheckInterval;
        
        function updateConnectionStatus() {
            const status = document.getElementById('connectionStatus');
            
            if (navigator.onLine) {
                status.className = 'connection-status online';
                status.innerHTML = '🟢 Conexión restaurada - Redirigiendo...';
                
                // Redirigir después de 2 segundos si hay conexión
                setTimeout(() => {
                    window.location.href = '/inventario-claude/';
                }, 2000);
                
            } else {
                status.className = 'connection-status offline';
                status.innerHTML = '🔴 Sin conexión a internet';
            }
        }
        
        function checkConnection() {
            const loader = document.getElementById('checkLoader');
            loader.style.display = 'inline-block';
            
            // Intentar fetch a un recurso ligero
            fetch('/inventario-claude/manifest.json', { 
                cache: 'no-cache',
                mode: 'no-cors'
            })
            .then(() => {
                updateConnectionStatus();
            })
            .catch(() => {
                updateConnectionStatus();
            })
            .finally(() => {
                setTimeout(() => {
                    loader.style.display = 'none';
                }, 1000);
            });
        }
        
        function goHome() {
            // Intentar ir al inicio, fallback a cache
            window.location.href = '/inventario-claude/';
        }
        
        function clearCache() {
            if ('serviceWorker' in navigator && 'caches' in window) {
                caches.keys().then(cacheNames => {
                    return Promise.all(
                        cacheNames.map(cacheName => caches.delete(cacheName))
                    );
                }).then(() => {
                    alert('Cache limpiado. La página se recargará.');
                    window.location.reload();
                });
            } else {
                alert('Cache no disponible en este navegador.');
            }
        }
        
        // Listeners para cambios de conexión
        window.addEventListener('online', updateConnectionStatus);
        window.addEventListener('offline', updateConnectionStatus);
        
        // Verificar conexión cada 30 segundos
        connectionCheckInterval = setInterval(checkConnection, 30000);
        
        // Verificación inicial
        updateConnectionStatus();
        
        // Limpiar interval cuando se abandona la página
        window.addEventListener('beforeunload', () => {
            if (connectionCheckInterval) {
                clearInterval(connectionCheckInterval);
            }
        });
        
        // PWA: Manejar instalación si está disponible
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            const installButton = document.createElement('button');
            installButton.className = 'btn';
            installButton.innerHTML = '📱 Instalar App';
            installButton.onclick = () => {
                e.prompt();
                e.userChoice.then((choiceResult) => {
                    if (choiceResult.outcome === 'accepted') {
                        console.log('App instalada');
                    }
                });
            };
            
            document.querySelector('.btn-group').appendChild(installButton);
        });
    </script>
</body>
</html>
