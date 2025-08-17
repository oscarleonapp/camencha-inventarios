<?php
session_start();
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config_functions.php';

// Verificar permisos
if (!isset($_SESSION['usuario_id']) || !tienePermiso('config_sistema')) {
    echo json_encode(['success' => false, 'error' => 'Sin permisos']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['config_file'])) {
    echo json_encode(['success' => false, 'error' => 'Archivo no recibido']);
    exit();
}

$file = $_FILES['config_file'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'Error en la carga del archivo']);
    exit();
}

$content = file_get_contents($file['tmp_name']);
$data = json_decode($content, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'error' => 'Archivo JSON inválido']);
    exit();
}

if (!isset($data['configuraciones']) || !isset($data['etiquetas'])) {
    echo json_encode(['success' => false, 'error' => 'Formato de archivo incorrecto']);
    exit();
}

try {
    if (importarConfiguracion($data, true)) {
        echo json_encode(['success' => true, 'message' => 'Configuración importada correctamente']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al importar la configuración']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Error: ' . $e->getMessage()]);
}
?>