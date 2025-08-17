<?php
require_once 'auth.php';
require_once '../config/database.php';
require_once 'logger.php';

verificarLogin();

// Solo administradores pueden limpiar logs
if (!esAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'No tienes permisos para realizar esta acción']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

try {
    $database = new Database();
    $eliminados = Logger::limpiarLogsAntiguos($database, 90);
    
    // Log de la acción de limpieza
    getLogger()->info('logs_limpieza', 'logs', "Limpieza de logs antiguos ejecutada, $eliminados registros eliminados");
    
    echo json_encode([
        'success' => true,
        'eliminados' => $eliminados,
        'message' => "Se eliminaron $eliminados logs antiguos (más de 90 días)"
    ]);
    
} catch (Exception $e) {
    getLogger()->error('logs_limpieza_error', 'logs', "Error al limpiar logs: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'error' => 'Error al limpiar logs: ' . $e->getMessage()
    ]);
}
?>