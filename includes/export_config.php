<?php
session_start();
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config_functions.php';

// Verificar permisos
if (!isset($_SESSION['usuario_id']) || !tienePermiso('config_sistema')) {
    http_response_code(403);
    exit('Sin permisos');
}

$configuracion = exportarConfiguracion();

header('Content-Type: application/json');
header('Content-Disposition: attachment; filename="configuracion_sistema_' . date('Y-m-d') . '.json"');
header('Cache-Control: no-cache, must-revalidate');

echo json_encode($configuracion, JSON_PRETTY_PRINT);
?>