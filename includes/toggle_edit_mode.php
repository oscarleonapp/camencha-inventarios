<?php
session_start();
require_once __DIR__ . '/auth.php';

// Verificar autenticación y permisos
verificarLogin();
if (!tienePermiso('config_sistema')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Sin permisos para cambiar modo de edición']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validar entrada
    if (!isset($input['edit_mode'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Parámetro edit_mode requerido']);
        exit();
    }
    
    $edit_mode = (bool)$input['edit_mode'];
    $_SESSION['modo_edicion'] = $edit_mode;
    
    echo json_encode(['success' => true, 'edit_mode' => $edit_mode]);
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
}
?>