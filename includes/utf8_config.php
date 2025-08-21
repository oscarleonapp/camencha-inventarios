<?php
/**
 * Configuración UTF-8 para el sistema
 * Este archivo debe incluirse al inicio de cada página PHP
 */

// Configurar la codificación interna de PHP
ini_set('default_charset', 'UTF-8');

// Configurar mbstring si está disponible
if (extension_loaded('mbstring')) {
    mb_internal_encoding('UTF-8');
    mb_http_output('UTF-8');
    mb_regex_encoding('UTF-8');
}

// Configurar codificación para htmlspecialchars y htmlentities
ini_set('default_charset', 'UTF-8');

// Forzar UTF-8 en la salida
if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
}

/**
 * Función para limpiar y convertir texto a UTF-8
 * @param string $text
 * @return string
 */
if (!function_exists('limpiarUTF8')) {
function limpiarUTF8($text) {
    if (empty($text)) return $text;
    
    // Detectar y convertir codificación
    $encoding = mb_detect_encoding($text, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
    
    if ($encoding !== 'UTF-8') {
        $text = mb_convert_encoding($text, 'UTF-8', $encoding);
    }
    
    return $text;
}
}

/**
 * Función para escapar texto de forma segura con UTF-8
 * @param string $text
 * @return string
 */
if (!function_exists('escaparUTF8')) {
function escaparUTF8($text) {
    return htmlspecialchars(limpiarUTF8($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
}
}

/**
 * Función para mostrar texto con acentos correctamente
 * @param string $text
 * @return string
 */
if (!function_exists('mostrarTexto')) {
function mostrarTexto($text) {
    return escaparUTF8($text);
}
}

/**
 * Función para corregir textos específicos con problemas de codificación
 * @param string $texto
 * @return string
 */
if (!function_exists('corregirTextoSistema')) {
function corregirTextoSistema($texto) {
    if (empty($texto)) return $texto;
    
    // Mapeo de correcciones comunes
    $correcciones = [
        // Problemas comunes de codificación
        'Configuraci¾n del sistema' => 'Configuración del sistema',
        'Gesti¾n de roles y permisos' => 'Gestión de roles y permisos',
        'Configuracion del sistema' => 'Configuración del sistema',
        'Gestion de roles y permisos' => 'Gestión de roles y permisos',
        'Enviar a reparacion' => 'Enviar a reparación',
        'Recibir de reparacion' => 'Recibir de reparación',
        'Creaci¾n' => 'Creación',
        'Eliminaci¾n' => 'Eliminación',
        'Modificaci¾n' => 'Modificación',
        'Actualizaci¾n' => 'Actualización',
        'Administraci¾n' => 'Administración',
        'Operaci¾n' => 'Operación',
        'Exportaci¾n' => 'Exportación',
        'Importaci¾n' => 'Importación',
        'Verificaci¾n' => 'Verificación',
        'Notificaci¾n' => 'Notificación',
        'Validaci¾n' => 'Validación'
    ];
    
    // Buscar coincidencia exacta primero
    if (isset($correcciones[$texto])) {
        return $correcciones[$texto];
    }
    
    // Buscar coincidencias parciales
    foreach ($correcciones as $problematico => $correcto) {
        if (strpos($texto, $problematico) !== false) {
            $texto = str_replace($problematico, $correcto, $texto);
        }
    }
    
    return $texto;
}
}
?>