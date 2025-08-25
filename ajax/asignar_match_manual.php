<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

verificarLogin();
verificarPermiso('ventas_ver');

$database = new Database();
$db = $database->getConnection();

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['reporte_id']) || !is_numeric($input['reporte_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de reporte inválido']);
    exit;
}

if (!isset($input['venta_id']) || !is_numeric($input['venta_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de venta inválido']);
    exit;
}

$reporte_id = (int)$input['reporte_id'];
$venta_id = (int)$input['venta_id'];
$usuario_id = $_SESSION['usuario_id'];

try {
    $db->beginTransaction();
    
    // Verificar que el reporte existe y está pendiente
    $query_reporte = "SELECT * FROM ventas_reportadas_vendedor 
                      WHERE id = ? AND estado = 'pendiente'";
    $stmt_reporte = $db->prepare($query_reporte);
    $stmt_reporte->execute([$reporte_id]);
    $reporte = $stmt_reporte->fetch(PDO::FETCH_ASSOC);
    
    if (!$reporte) {
        throw new Exception('Reporte no encontrado o ya procesado');
    }
    
    // Verificar que la venta existe y está disponible
    $query_venta = "SELECT v.*, t.nombre as tienda_nombre 
                    FROM ventas v
                    JOIN tiendas t ON v.tienda_id = t.id
                    WHERE v.id = ? 
                    AND v.estado = 'completada'
                    AND v.id NOT IN (
                        SELECT venta_sistema_id FROM ventas_reportadas_vendedor 
                        WHERE venta_sistema_id IS NOT NULL 
                        AND estado IN ('aprobado_encargado', 'pendiente')
                        AND id != ?
                    )";
    $stmt_venta = $db->prepare($query_venta);
    $stmt_venta->execute([$venta_id, $reporte_id]);
    $venta = $stmt_venta->fetch(PDO::FETCH_ASSOC);
    
    if (!$venta) {
        throw new Exception('Venta no encontrada o ya asignada a otro reporte');
    }
    
    // Actualizar el reporte con el match manual
    $query_update = "UPDATE ventas_reportadas_vendedor 
                     SET venta_sistema_id = ?, 
                         verificada_por = ?,
                         fecha_verificacion = NOW()
                     WHERE id = ?";
    $stmt_update = $db->prepare($query_update);
    $stmt_update->execute([$venta_id, $usuario_id, $reporte_id]);
    
    // Si la venta no tiene vendedor asignado, asignar el del reporte
    if (!$venta['vendedor_id']) {
        $query_update_venta = "UPDATE ventas SET vendedor_id = ? WHERE id = ?";
        $stmt_update_venta = $db->prepare($query_update_venta);
        $stmt_update_venta->execute([$reporte['vendedor_id'], $venta_id]);
    }
    
    // Registrar en log de auditoría
    $query_log = "INSERT INTO logs_sistema (usuario_id, accion, tabla, registro_id, detalles)
                  VALUES (?, ?, ?, ?, ?)";
    $stmt_log = $db->prepare($query_log);
    $detalles = json_encode([
        'accion' => 'asignar_match_manual',
        'reporte_id' => $reporte_id,
        'venta_id' => $venta_id,
        'venta_total' => $venta['total'],
        'reporte_total' => $reporte['total_reportado'],
        'diferencia' => abs($venta['total'] - $reporte['total_reportado']),
        'tienda' => $venta['tienda_nombre']
    ]);
    $stmt_log->execute([$usuario_id, 'ventas_reportadas', 'ventas_reportadas_vendedor', $reporte_id, $detalles]);
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Match asignado exitosamente. El reporte ahora está vinculado a la venta #' . $venta_id
    ]);
    
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode([
        'success' => false,
        'message' => 'Error al asignar match: ' . $e->getMessage()
    ]);
}
?>