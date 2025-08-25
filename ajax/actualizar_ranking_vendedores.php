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
$mes = $input['mes'] ?? date('n');
$ano = $input['ano'] ?? date('Y');

$meses = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
];

// Crear período en formato YYYY-MM
$periodo = sprintf("%04d-%02d", $ano, $mes);

try {
    $db->beginTransaction();
    
    // Obtener todos los vendedores activos
    $query_vendedores = "SELECT id FROM vendedores WHERE activo = 1";
    $vendedores = $db->query($query_vendedores)->fetchAll(PDO::FETCH_COLUMN);
    
    $vendedores_actualizados = 0;
    
    foreach ($vendedores as $vendedor_id) {
        // Calcular estadísticas del vendedor para el mes/año especificado
        $query_stats = "SELECT 
                           COUNT(*) as ventas_validadas,
                           SUM(monto_venta) as total_ventas,
                           AVG(monto_venta) as promedio_venta,
                           SUM(monto_comision) as total_comisiones,
                           MIN(monto_venta) as venta_minima,
                           MAX(monto_venta) as venta_maxima,
                           COUNT(DISTINCT DATE(fecha_venta)) as dias_activos
                        FROM comisiones_vendedores 
                        WHERE vendedor_id = ? 
                        AND YEAR(fecha_venta) = ? 
                        AND MONTH(fecha_venta) = ?";
        $stmt_stats = $db->prepare($query_stats);
        $stmt_stats->execute([$vendedor_id, $ano, $mes]);
        $stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);
        
        // Solo procesar si tiene ventas
        if ($stats['ventas_validadas'] > 0) {
            // Calcular puntos de ranking usando algoritmo mejorado
            $puntos_ventas = $stats['ventas_validadas'] * 10; // 10 puntos por venta
            $puntos_volumen = $stats['total_ventas'] / 100; // 1 punto por cada Q100 vendidos
            $bonus_consistencia = $stats['dias_activos'] >= 15 ? 50 : ($stats['dias_activos'] * 2); // Bonus por consistencia
            $bonus_ticket_alto = $stats['promedio_venta'] > 200 ? 25 : 0; // Bonus por ticket alto
            
            $puntos_ranking = $puntos_ventas + $puntos_volumen + $bonus_consistencia + $bonus_ticket_alto;
            
            // Actualizar o insertar ranking
            $query_ranking = "INSERT INTO ranking_vendedores 
                              (vendedor_id, periodo, total_ventas, cantidad_ventas,
                               comision_ganada, puntos_ranking, fecha_calculo)
                              VALUES (?, ?, ?, ?, ?, ?, NOW())
                              ON DUPLICATE KEY UPDATE
                              total_ventas = VALUES(total_ventas),
                              cantidad_ventas = VALUES(cantidad_ventas),
                              comision_ganada = VALUES(comision_ganada),
                              puntos_ranking = VALUES(puntos_ranking),
                              fecha_calculo = NOW()";
            
            $stmt_ranking = $db->prepare($query_ranking);
            $stmt_ranking->execute([
                $vendedor_id,
                $periodo,
                $stats['total_ventas'],
                $stats['ventas_validadas'],
                $stats['total_comisiones'],
                $puntos_ranking
            ]);
            
            $vendedores_actualizados++;
        } else {
            // Si no tiene ventas, eliminar registro anterior si existe
            $query_delete = "DELETE FROM ranking_vendedores 
                            WHERE vendedor_id = ? AND periodo = ?";
            $stmt_delete = $db->prepare($query_delete);
            $stmt_delete->execute([$vendedor_id, $periodo]);
        }
    }
    
    // Calcular posiciones de ranking
    $query_posiciones = "SELECT id, puntos_ranking,
                            ROW_NUMBER() OVER (ORDER BY puntos_ranking DESC, total_ventas DESC) as nueva_posicion
                         FROM ranking_vendedores 
                         WHERE periodo = ?
                         ORDER BY puntos_ranking DESC, total_ventas DESC";
    $stmt_posiciones = $db->prepare($query_posiciones);
    $stmt_posiciones->execute([$periodo]);
    $posiciones = $stmt_posiciones->fetchAll(PDO::FETCH_ASSOC);
    
    // Actualizar posiciones
    foreach ($posiciones as $posicion) {
        $query_update_pos = "UPDATE ranking_vendedores 
                            SET posicion_ranking = ? 
                            WHERE id = ?";
        $stmt_update_pos = $db->prepare($query_update_pos);
        $stmt_update_pos->execute([$posicion['nueva_posicion'], $posicion['id']]);
    }
    
    // Registrar en log
    $query_log = "INSERT INTO logs_sistema (usuario_id, accion, tabla, registro_id, detalles)
                  VALUES (?, ?, ?, ?, ?)";
    $stmt_log = $db->prepare($query_log);
    $detalles = json_encode([
        'accion' => 'actualizar_ranking_vendedores',
        'mes' => $mes,
        'ano' => $ano,
        'vendedores_actualizados' => $vendedores_actualizados,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    $stmt_log->execute([$_SESSION['usuario_id'], 'ranking', 'ranking_vendedores', 0, $detalles]);
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => "Ranking actualizado exitosamente. Se procesaron {$vendedores_actualizados} vendedores para {$meses[$mes]} {$ano}.",
        'vendedores_actualizados' => $vendedores_actualizados
    ]);
    
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode([
        'success' => false,
        'message' => 'Error al actualizar ranking: ' . $e->getMessage()
    ]);
}

?>