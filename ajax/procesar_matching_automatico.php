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

try {
    $db->beginTransaction();
    
    // Obtener reportes sin match pendientes
    $query_reportes = "SELECT * FROM ventas_reportadas_vendedor 
                       WHERE estado = 'pendiente' 
                       AND venta_id IS NULL 
                       AND match_manual = 0
                       ORDER BY fecha_reporte ASC";
    $stmt_reportes = $db->query($query_reportes);
    $reportes = $stmt_reportes->fetchAll(PDO::FETCH_ASSOC);
    
    $matches_encontrados = 0;
    $reportes_procesados = 0;
    
    foreach ($reportes as $reporte) {
        $reportes_procesados++;
        
        // Buscar ventas en un rango de fechas (±2 días)
        $fecha_inicio = date('Y-m-d', strtotime($reporte['fecha_venta'] . ' -2 days'));
        $fecha_fin = date('Y-m-d', strtotime($reporte['fecha_venta'] . ' +2 days'));
        
        // Algoritmo de matching mejorado
        $query_ventas = "SELECT 
                            v.id,
                            v.fecha,
                            v.total,
                            v.vendedor_id,
                            v.tienda_id,
                            -- Calcular score de confianza
                            CASE 
                                -- Puntos por diferencia de monto
                                WHEN ABS(v.total - ?) <= 0.50 THEN 0.5
                                WHEN ABS(v.total - ?) <= 1.00 THEN 0.4
                                WHEN ABS(v.total - ?) <= 2.00 THEN 0.3
                                WHEN ABS(v.total - ?) <= 5.00 THEN 0.2
                                WHEN ABS(v.total - ?) <= 10.00 THEN 0.1
                                ELSE 0
                            END +
                            CASE 
                                -- Puntos por diferencia de fecha
                                WHEN DATE(v.fecha) = ? THEN 0.3
                                WHEN ABS(DATEDIFF(v.fecha, ?)) <= 1 THEN 0.2
                                WHEN ABS(DATEDIFF(v.fecha, ?)) <= 2 THEN 0.1
                                ELSE 0
                            END +
                            CASE 
                                -- Puntos por mismo vendedor
                                WHEN v.vendedor_id = ? THEN 0.2
                                WHEN v.vendedor_id IS NULL THEN 0.1
                                ELSE 0
                            END as confianza_score
                         FROM ventas v
                         WHERE DATE(v.fecha) BETWEEN ? AND ?
                         AND v.estado = 'completada'
                         AND v.id NOT IN (
                             SELECT venta_id FROM ventas_reportadas_vendedor 
                             WHERE venta_id IS NOT NULL 
                             AND estado IN ('aprobado', 'pendiente')
                         )
                         HAVING confianza_score >= 0.7
                         ORDER BY confianza_score DESC
                         LIMIT 1";
        
        $stmt_ventas = $db->prepare($query_ventas);
        $stmt_ventas->execute([
            $reporte['total_reportado'], // monto <= 0.50
            $reporte['total_reportado'], // monto <= 1.00
            $reporte['total_reportado'], // monto <= 2.00
            $reporte['total_reportado'], // monto <= 5.00
            $reporte['total_reportado'], // monto <= 10.00
            $reporte['fecha_venta'], // fecha exacta
            $reporte['fecha_venta'], // fecha ±1 día
            $reporte['fecha_venta'], // fecha ±2 días
            $reporte['vendedor_id'], // mismo vendedor
            $fecha_inicio,
            $fecha_fin
        ]);
        
        $match = $stmt_ventas->fetch(PDO::FETCH_ASSOC);
        
        if ($match) {
            // Actualizar reporte con el match encontrado
            $query_update = "UPDATE ventas_reportadas_vendedor 
                            SET venta_id = ?, 
                                confianza_match = ?,
                                fecha_match = NOW()
                            WHERE id = ?";
            $stmt_update = $db->prepare($query_update);
            $stmt_update->execute([
                $match['id'], 
                $match['confianza_score'], 
                $reporte['id']
            ]);
            
            // Si la venta no tiene vendedor asignado, asignar el del reporte
            if (!$match['vendedor_id']) {
                $query_update_venta = "UPDATE ventas SET vendedor_id = ? WHERE id = ?";
                $stmt_update_venta = $db->prepare($query_update_venta);
                $stmt_update_venta->execute([$reporte['vendedor_id'], $match['id']]);
            }
            
            $matches_encontrados++;
        }
    }
    
    // Registrar en log
    $query_log = "INSERT INTO logs_sistema (usuario_id, accion, tabla, registro_id, detalles)
                  VALUES (?, ?, ?, ?, ?)";
    $stmt_log = $db->prepare($query_log);
    $detalles = json_encode([
        'accion' => 'matching_automatico',
        'reportes_procesados' => $reportes_procesados,
        'matches_encontrados' => $matches_encontrados,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    $stmt_log->execute([$_SESSION['usuario_id'], 'sistema', 'ventas_reportadas_vendedor', 0, $detalles]);
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => "Proceso completado. Se procesaron {$reportes_procesados} reportes y se encontraron {$matches_encontrados} matches automáticos.",
        'reportes_procesados' => $reportes_procesados,
        'matches_encontrados' => $matches_encontrados
    ]);
    
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode([
        'success' => false,
        'message' => 'Error en el matching automático: ' . $e->getMessage()
    ]);
}
?>