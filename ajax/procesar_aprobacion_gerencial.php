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

if (!isset($input['estado']) || !in_array($input['estado'], ['aprobado_gerente', 'rechazado_gerente'])) {
    echo json_encode(['success' => false, 'message' => 'Estado inválido']);
    exit;
}

$reporte_id = (int)$input['reporte_id'];
$estado = $input['estado'];
$observaciones = $input['observaciones'] ?? null;
$gerente_id = $_SESSION['usuario_id'];

try {
    $db->beginTransaction();
    
    // Obtener información del reporte
    $query_reporte = "SELECT rd.*, t.nombre as tienda_nombre, u.nombre as encargado_nombre 
                      FROM reportes_diarios_encargado rd
                      JOIN tiendas t ON rd.tienda_id = t.id
                      JOIN usuarios u ON rd.encargado_id = u.id
                      WHERE rd.id = ? AND rd.estado = 'pendiente'";
    $stmt_reporte = $db->prepare($query_reporte);
    $stmt_reporte->execute([$reporte_id]);
    $reporte = $stmt_reporte->fetch(PDO::FETCH_ASSOC);
    
    if (!$reporte) {
        throw new Exception('Reporte no encontrado o ya procesado');
    }
    
    // Actualizar estado del reporte
    $query_update = "UPDATE reportes_diarios_encargado 
                     SET estado = ?, 
                         gerente_id = ?, 
                         fecha_revision_gerente = NOW(),
                         observaciones_gerente = ?
                     WHERE id = ?";
    $stmt_update = $db->prepare($query_update);
    $stmt_update->execute([$estado, $gerente_id, $observaciones, $reporte_id]);
    
    // Si se aprueba, crear registro para contabilidad
    if ($estado === 'aprobado_gerente') {
        // Obtener datos del sistema para comparación
        $query_sistema = "SELECT 
                             COUNT(*) as ventas_cantidad,
                             COALESCE(SUM(total), 0) as total_sistema
                          FROM ventas 
                          WHERE tienda_id = ? AND DATE(fecha) = ? AND estado = 'completada'";
        $stmt_sistema = $db->prepare($query_sistema);
        $stmt_sistema->execute([$reporte['tienda_id'], $reporte['fecha_reporte']]);
        $datos_sistema = $stmt_sistema->fetch(PDO::FETCH_ASSOC);
        
        // Crear registro de reconciliación pendiente para contabilidad
        $query_reconciliacion = "INSERT INTO reconciliacion_boletas 
                                (tienda_id, fecha_reconciliacion, reporte_diario_id, 
                                 total_sistema, ventas_sistema, total_boletas_fisicas, 
                                 cantidad_boletas_fisicas, usuario_contabilidad_id, estado)
                                VALUES (?, ?, ?, ?, ?, 0, 0, 
                                       (SELECT id FROM usuarios WHERE rol = 'admin' LIMIT 1), 
                                       'pendiente')";
        $stmt_reconciliacion = $db->prepare($query_reconciliacion);
        $stmt_reconciliacion->execute([
            $reporte['tienda_id'],
            $reporte['fecha_reporte'],
            $reporte_id,
            $datos_sistema['total_sistema'],
            $datos_sistema['ventas_cantidad']
        ]);
    }
    
    // Registrar en log de auditoría
    $query_log = "INSERT INTO logs_sistema (usuario_id, accion, tabla, registro_id, detalles)
                  VALUES (?, ?, ?, ?, ?)";
    $stmt_log = $db->prepare($query_log);
    $detalles = json_encode([
        'accion' => $estado === 'aprobado_gerente' ? 'aprobar_reporte_gerencial' : 'rechazar_reporte_gerencial',
        'tienda' => $reporte['tienda_nombre'],
        'encargado' => $reporte['encargado_nombre'],
        'fecha_reporte' => $reporte['fecha_reporte'],
        'total_reportado' => $reporte['total_general'],
        'observaciones' => $observaciones
    ]);
    $stmt_log->execute([
        $gerente_id,
        'reportes_gerenciales',
        'reportes_diarios_encargado',
        $reporte_id,
        $detalles
    ]);
    
    $db->commit();
    
    $mensaje = $estado === 'aprobado_gerente' 
        ? 'Reporte aprobado exitosamente. Se ha enviado a contabilidad para reconciliación.'
        : 'Reporte rechazado: ' . ($observaciones ?: 'Sin motivo especificado');
    
    echo json_encode([
        'success' => true,
        'message' => $mensaje
    ]);
    
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode([
        'success' => false,
        'message' => 'Error al procesar: ' . $e->getMessage()
    ]);
}
?>