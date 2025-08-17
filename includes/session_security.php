<?php
/**
 * Configuración de seguridad para sesiones
 */

// Configurar sesiones seguras
function configurarSesionSegura() {
    // Solo si la sesión no ha sido iniciada
    if (session_status() === PHP_SESSION_NONE) {
        // Configuraciones de seguridad
        ini_set('session.cookie_httponly', 1); // Previene acceso via JavaScript
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 1 : 0); // Solo HTTPS si está disponible
        ini_set('session.use_strict_mode', 1); // Rechazar IDs de sesión no inicializados
        ini_set('session.cookie_samesite', 'Strict'); // Protección CSRF adicional
        ini_set('session.gc_maxlifetime', 3600); // Sesión expira en 1 hora
        ini_set('session.cookie_lifetime', 0); // Cookie expira al cerrar navegador
        
        // Regenerar ID de sesión periódicamente para prevenir session fixation
        session_start();
        
        // Regenerar ID cada 30 minutos o en login
        if (!isset($_SESSION['regenerated'])) {
            session_regenerate_id(true);
            $_SESSION['regenerated'] = time();
        } elseif (time() - $_SESSION['regenerated'] > 1800) { // 30 minutos
            session_regenerate_id(true);
            $_SESSION['regenerated'] = time();
        }
        
        // Validar IP del usuario (opcional - puede causar problemas con proxies)
        if (!isset($_SESSION['user_ip'])) {
            $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        } elseif ($_SESSION['user_ip'] !== ($_SERVER['REMOTE_ADDR'] ?? 'unknown')) {
            // IP cambió - posible session hijacking
            // session_destroy();
            // header('Location: login.php?error=session_invalid');
            // exit();
            // Comentado por compatibilidad con NAT/Proxies
        }
        
        // Timeout de inactividad
        if (isset($_SESSION['last_activity'])) {
            if (time() - $_SESSION['last_activity'] > 3600) { // 1 hora
                session_destroy();
                header('Location: login.php?error=timeout');
                exit();
            }
        }
        $_SESSION['last_activity'] = time();
    }
}

// Función para destruir sesión seguramente
function destruirSesionSegura() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        // Limpiar variables de sesión
        $_SESSION = array();
        
        // Eliminar cookie de sesión
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Destruir sesión
        session_destroy();
    }
}

// Validar que la sesión sea válida
function validarSesion() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Verificar timeout
    if (isset($_SESSION['last_activity'])) {
        if (time() - $_SESSION['last_activity'] > 3600) {
            destruirSesionSegura();
            return false;
        }
    }
    
    // Actualizar última actividad
    $_SESSION['last_activity'] = time();
    
    return isset($_SESSION['usuario_id']);
}

// Auto-configurar al incluir este archivo
configurarSesionSegura();
?>