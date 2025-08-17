<?php
session_start();
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config_functions.php';

// Verificar permisos
if (!isset($_SESSION['usuario_id']) || !tienePermiso('config_sistema')) {
    echo json_encode(['success' => false, 'error' => 'Sin permisos']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['label']) || !isset($input['value'])) {
        echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
        exit();
    }
    
    $label = $input['label'];
    $value = trim($input['value']);
    
    if (empty($value)) {
        echo json_encode(['success' => false, 'error' => 'El valor no puede estar vacío']);
        exit();
    }
    
    // Verificar si es una etiqueta o configuración
    if (strpos($label, 'menu_') === 0 || strpos($label, 'producto_') === 0 || strpos($label, 'inventario_') === 0) {
        // Es una etiqueta personalizable
        $success = actualizarEtiqueta($label, $value, $_SESSION['usuario_id']);
    } else {
        // Es una configuración del sistema
        $success = actualizarConfiguracion($label, $value, $_SESSION['usuario_id']);
    }
    
    if ($success) {
        // Limpiar cache
        limpiarCacheConfiguracion();
        echo json_encode(['success' => true, 'message' => 'Etiqueta actualizada correctamente']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al actualizar la etiqueta']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
}
?>