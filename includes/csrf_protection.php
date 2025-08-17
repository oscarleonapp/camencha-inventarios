<?php
/**
 * Protección CSRF para formularios
 */

// Generar token CSRF
function generarTokenCSRF() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

// Verificar token CSRF
function verificarTokenCSRF($token) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        return false;
    }
    
    return true;
}

// Generar campo oculto para formularios
function campoCSRF() {
    $token = generarTokenCSRF();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

// Verificar CSRF en POST
function validarCSRF() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        
        if (!verificarTokenCSRF($token)) {
            http_response_code(403);
            die('Error de seguridad: Token CSRF inválido.');
        }
    }
}

// Regenerar token después de uso
function regenerarTokenCSRF() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}
?>