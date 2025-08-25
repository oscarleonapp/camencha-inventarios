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

if (!isset($input['estado']) || !in_array($input['estado'], ['aprobado', 'rechazado'])) {
    echo json_encode(['success' => false, 'message' => 'Estado inválido']);
    exit;
}

$reporte_id = (int)$input['reporte_id'];
$estado = $input['estado'];
$motivo_rechazo = $input['motivo_rechazo'] ?? null;
$usuario_id = $_SESSION['usuario_id'];

try {
    $db->beginTransaction();
    
    // Obtener información del reporte
    $query = "SELECT vrv.*, v.nombre as vendedor_nombre 
              FROM ventas_reportadas_vendedor vrv
              JOIN vendedores v ON vrv.vendedor_id = v.id
              WHERE vrv.id = ? AND vrv.estado = 'pendiente'";
    $stmt = $db->prepare($query);
    $stmt->execute([$reporte_id]);
    $reporte = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reporte) {
        throw new Exception('Reporte no encontrado o ya procesado');
    }
    
    // Actualizar estado del reporte
    $query_update = "UPDATE ventas_reportadas_vendedor 
                     SET estado = ?, 
                         usuario_aprobador_id = ?, 
                         fecha_procesamiento = NOW(),
                         motivo_rechazo = ?
                     WHERE id = ?";
    $stmt_update = $db->prepare($query_update);
    $stmt_update->execute([$estado, $usuario_id, $motivo_rechazo, $reporte_id]);
    
    if ($estado === 'aprobado') {
        // Si se aprueba y hay match con venta del sistema
        if ($reporte['venta_id']) {
            // Actualizar tabla de comisiones si existe el registro
            $query_comision = "UPDATE comisiones_vendedores 
                              SET estado = 'pagada'
                              WHERE venta_id = ? AND vendedor_id = ?";
            $stmt_comision = $db->prepare($query_comision);
            $stmt_comision->execute([$reporte['venta_id'], $reporte['vendedor_id']]);
            
            // Si no existe registro de comisión, crearlo
            if ($stmt_comision->rowCount() === 0) {
                // Obtener datos de la venta para calcular comisión
                $query_venta = "SELECT v.*, vd.comision_porcentaje 
                               FROM ventas v 
                               JOIN vendedores vd ON v.vendedor_id = vd.id 
                               WHERE v.id = ?";
                $stmt_venta = $db->prepare($query_venta);
                $stmt_venta->execute([$reporte['venta_id']]);
                $venta = $stmt_venta->fetch(PDO::FETCH_ASSOC);
                
                if ($venta) {
                    $monto_comision = ($venta['total'] * $venta['comision_porcentaje']) / 100;
                    
                    $query_insert_comision = "INSERT INTO comisiones_vendedores 
                                             (vendedor_id, venta_id, monto_venta, porcentaje_comision, 
                                              monto_comision, fecha_venta, estado)
                                             VALUES (?, ?, ?, ?, ?, ?, 'pendiente')";
                    $stmt_insert = $db->prepare($query_insert_comision);
                    $stmt_insert->execute([
                        $reporte['vendedor_id'], 
                        $reporte['venta_id'], 
                        $venta['total'], 
                        $venta['comision_porcentaje'], 
                        $monto_comision,
                        $venta['fecha']
                    ]);
                }
            }
        }
        
        // Actualizar ranking del vendedor
        actualizarRankingVendedor($db, $reporte['vendedor_id']);
        
        $mensaje = 'Reporte aprobado exitosamente';
    } else {
        $mensaje = 'Reporte rechazado: ' . ($motivo_rechazo ?: 'Sin motivo especificado');
    }
    
    // Registrar en log de auditoría
    $query_log = "INSERT INTO logs_sistema (usuario_id, accion, tabla, registro_id, detalles)
                  VALUES (?, ?, ?, ?, ?)";
    $stmt_log = $db->prepare($query_log);
    $detalles = json_encode([
        'accion' => $estado === 'aprobado' ? 'aprobar_reporte_venta' : 'rechazar_reporte_venta',
        'vendedor' => $reporte['vendedor_nombre'],
        'total' => $reporte['total_reportado'],
        'motivo_rechazo' => $motivo_rechazo
    ]);
    $stmt_log->execute([$usuario_id, 'ventas_reportadas', 'ventas_reportadas_vendedor', $reporte_id, $detalles]);
    
    $db->commit();
    
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

// Función para actualizar ranking del vendedor
function actualizarRankingVendedor($db, $vendedor_id) {
    // Calcular estadísticas del mes actual
    $query_stats = "SELECT 
                       COUNT(*) as ventas_validadas,
                       SUM(monto_venta) as total_ventas,
                       AVG(monto_venta) as promedio_venta,
                       SUM(monto_comision) as total_comisiones
                    FROM comisiones_vendedores 
                    WHERE vendedor_id = ? 
                    AND estado = 'pagada' 
                    AND YEAR(fecha_venta) = YEAR(CURDATE()) 
                    AND MONTH(fecha_venta) = MONTH(CURDATE())";
    $stmt_stats = $db->prepare($query_stats);
    $stmt_stats->execute([$vendedor_id]);
    $stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);
    $stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);
    
    // Actualizar o insertar ranking
    $periodo = sprintf("%04d-%02d", date('Y'), date('n'));
    $query_ranking = "INSERT INTO ranking_vendedores 
                      (vendedor_id, periodo, cantidad_ventas, total_ventas, 
                       comision_ganada, puntos_ranking, fecha_calculo)
                      VALUES (?, ?, ?, ?, ?, ?, NOW())
                      ON DUPLICATE KEY UPDATE
                      cantidad_ventas = VALUES(cantidad_ventas),
                      total_ventas = VALUES(total_ventas),
                      comision_ganada = VALUES(comision_ganada),
                      puntos_ranking = VALUES(puntos_ranking),
                      fecha_calculo = NOW()";
    
    // Calcular puntos de ranking (ejemplo: ventas * 10 + total_ventas / 100)
    $puntos = ($stats['ventas_validadas'] * 10) + ($stats['total_ventas'] / 100);
    
    $stmt_ranking = $db->prepare($query_ranking);
    $stmt_ranking->execute([
        $vendedor_id,
        $periodo,
        $stats['ventas_validadas'],
        $stats['total_ventas'],
        $stats['total_comisiones'],
        $puntos
    ]);
}
?>