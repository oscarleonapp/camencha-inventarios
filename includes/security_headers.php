<?php
/**
 * Configuración de cabeceras de seguridad
 */

function configurarCabecerasSeguridad() {
    // Configurar UTF-8 para la respuesta
    header("Content-Type: text/html; charset=UTF-8");
    
    // Content Security Policy - Prevenir XSS
    $csp = "default-src 'self'; " .
           "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; " .
           "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; " .
           "font-src 'self' https://cdnjs.cloudflare.com; " .
           "img-src 'self' data:; " .
           "connect-src 'self'; " .
           "frame-ancestors 'none'; " .
           "base-uri 'self';";
    
    header("Content-Security-Policy: " . $csp);
    
    // Prevenir clickjacking
    header("X-Frame-Options: DENY");
    
    // Prevenir MIME sniffing
    header("X-Content-Type-Options: nosniff");
    
    // Forzar HTTPS (si está disponible)
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
    }
    
    // XSS Protection (navegadores antiguos)
    header("X-XSS-Protection: 1; mode=block");
    
    // Referrer Policy
    header("Referrer-Policy: strict-origin-when-cross-origin");
    
    // Permissions Policy (Feature Policy)
    header("Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()");
    
    // Cache control para páginas sensibles
    header("Cache-Control: no-cache, no-store, must-revalidate");
    header("Pragma: no-cache");
    header("Expires: 0");
}

// Auto-configurar cabeceras al incluir este archivo
configurarCabecerasSeguridad();
?>