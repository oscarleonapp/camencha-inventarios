<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

verificarLogin();
verificarPermiso('config_sistema');

$database = new Database();
$db = $database->getConnection();

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['reconciliacion_id']) || !is_numeric($input['reconciliacion_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de reconciliación inválido']);
    exit;
}

$reconciliacion_id = (int)$input['reconciliacion_id'];

try {
    $db->beginTransaction();
    
    // Obtener totales de boletas físicas registradas
    $query_totales = "SELECT 
                         COUNT(*) as cantidad_boletas,
                         SUM(total_boleta) as total_boletas
                      FROM detalle_boletas_fisicas 
                      WHERE reconciliacion_id = ?";
    $stmt_totales = $db->prepare($query_totales);
    $stmt_totales->execute([$reconciliacion_id]);
    $totales = $stmt_totales->fetch(PDO::FETCH_ASSOC);
    
    // Actualizar tabla de reconciliación
    $query_update = "UPDATE reconciliacion_boletas 
                     SET total_boletas_fisicas = ?,
                         cantidad_boletas_fisicas = ?,
                         fecha_revision = NOW(),
                         estado = CASE 
                             WHEN estado = 'pendiente' THEN 'revisando'
                             ELSE estado 
                         END
                     WHERE id = ?";
    $stmt_update = $db->prepare($query_update);
    $stmt_update->execute([
        $totales['total_boletas'] ?? 0,
        $totales['cantidad_boletas'] ?? 0,
        $reconciliacion_id
    ]);
    
    // Registrar en log
    $query_log = "INSERT INTO logs_sistema (usuario_id, accion, tabla, registro_id, detalles)
                  VALUES (?, ?, ?, ?, ?)";
    $stmt_log = $db->prepare($query_log);
    $detalles = json_encode([
        'accion' => 'actualizar_totales_reconciliacion',
        'cantidad_boletas' => $totales['cantidad_boletas'],
        'total_boletas' => $totales['total_boletas'],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    $stmt_log->execute([
        $_SESSION['usuario_id'],
        'reconciliacion',
        'reconciliacion_boletas',
        $reconciliacion_id,
        $detalles
    ]);
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Totales de reconciliación actualizados exitosamente',
        'cantidad_boletas' => $totales['cantidad_boletas'],
        'total_boletas' => $totales['total_boletas']
    ]);
    
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode([
        'success' => false,
        'message' => 'Error al actualizar totales: ' . $e->getMessage()
    ]);
}
?>