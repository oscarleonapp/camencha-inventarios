<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

verificarLogin();
verificarPermiso('ventas_crear');

$database = new Database();
$db = $database->getConnection();

// Obtener datos del formulario
$fecha_reporte = $_POST['fecha_reporte'] ?? '';
$tienda_id = $_POST['tienda_id'] ?? '';
$total_efectivo = floatval($_POST['total_efectivo'] ?? 0);
$total_tarjeta = floatval($_POST['total_tarjeta'] ?? 0);
$total_transferencia = floatval($_POST['total_transferencia'] ?? 0);
$total_otros = floatval($_POST['total_otros'] ?? 0);
$observaciones = $_POST['observaciones'] ?? '';

$encargado_id = $_SESSION['usuario_id'];

// Validaciones
if (empty($fecha_reporte) || empty($tienda_id)) {
    echo json_encode(['success' => false, 'message' => 'Fecha y tienda son requeridos']);
    exit;
}

if (strtotime($fecha_reporte) > strtotime(date('Y-m-d'))) {
    echo json_encode(['success' => false, 'message' => 'No se puede reportar una fecha futura']);
    exit;
}

// Verificar si el usuario tiene acceso a esta tienda
$usuario_rol = $_SESSION['rol'] ?? '';
if ($usuario_rol !== 'admin') {
    $query_acceso = "SELECT tienda_id FROM usuarios WHERE id = ? AND (tienda_id = ? OR tienda_id IS NULL)";
    $stmt_acceso = $db->prepare($query_acceso);
    $stmt_acceso->execute([$encargado_id, $tienda_id]);
    if (!$stmt_acceso->fetchColumn()) {
        echo json_encode(['success' => false, 'message' => 'No tienes permiso para reportar esta tienda']);
        exit;
    }
}

try {
    $db->beginTransaction();
    
    // Verificar si ya existe un reporte para esta fecha y tienda
    $query_existe = "SELECT id, estado FROM reportes_diarios_encargado 
                     WHERE tienda_id = ? AND fecha_reporte = ?";
    $stmt_existe = $db->prepare($query_existe);
    $stmt_existe->execute([$tienda_id, $fecha_reporte]);
    $reporte_existente = $stmt_existe->fetch(PDO::FETCH_ASSOC);
    
    if ($reporte_existente) {
        // Verificar si se puede actualizar
        if (in_array($reporte_existente['estado'], ['aprobado_gerente', 'aprobado_contabilidad'])) {
            throw new Exception('No se puede modificar un reporte ya aprobado');
        }
        
        // Actualizar reporte existente
        $query_update = "UPDATE reportes_diarios_encargado 
                        SET total_efectivo = ?,
                            total_tarjeta = ?,
                            total_transferencia = ?,
                            total_otros = ?,
                            observaciones = ?,
                            updated_at = CURRENT_TIMESTAMP
                        WHERE id = ?";
        $stmt_update = $db->prepare($query_update);
        $stmt_update->execute([
            $total_efectivo,
            $total_tarjeta,
            $total_transferencia,
            $total_otros,
            $observaciones,
            $reporte_existente['id']
        ]);
        
        $reporte_id = $reporte_existente['id'];
        $accion = 'actualizar';
    } else {
        // Crear nuevo reporte
        $query_insert = "INSERT INTO reportes_diarios_encargado 
                        (tienda_id, fecha_reporte, encargado_id, total_efectivo, 
                         total_tarjeta, total_transferencia, total_otros, observaciones, estado)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pendiente')";
        $stmt_insert = $db->prepare($query_insert);
        $stmt_insert->execute([
            $tienda_id,
            $fecha_reporte,
            $encargado_id,
            $total_efectivo,
            $total_tarjeta,
            $total_transferencia,
            $total_otros,
            $observaciones
        ]);
        
        $reporte_id = $db->lastInsertId();
        $accion = 'crear';
    }
    
    // Obtener información de la tienda para el log
    $query_tienda = "SELECT nombre FROM tiendas WHERE id = ?";
    $stmt_tienda = $db->prepare($query_tienda);
    $stmt_tienda->execute([$tienda_id]);
    $tienda_nombre = $stmt_tienda->fetchColumn();
    
    // Registrar en log
    $total_general = $total_efectivo + $total_tarjeta + $total_transferencia + $total_otros;
    $query_log = "INSERT INTO logs_sistema (usuario_id, accion, tabla, registro_id, detalles)
                  VALUES (?, ?, ?, ?, ?)";
    $stmt_log = $db->prepare($query_log);
    $detalles = json_encode([
        'accion' => $accion . '_reporte_ingresos',
        'tienda' => $tienda_nombre,
        'fecha_reporte' => $fecha_reporte,
        'total_efectivo' => $total_efectivo,
        'total_tarjeta' => $total_tarjeta,
        'total_transferencia' => $total_transferencia,
        'total_otros' => $total_otros,
        'total_general' => $total_general
    ]);
    $stmt_log->execute([
        $encargado_id,
        'reportes_ingresos',
        'reportes_diarios_encargado',
        $reporte_id,
        $detalles
    ]);
    
    $db->commit();
    
    $mensaje = $accion === 'crear' 
        ? 'Reporte de ingresos creado exitosamente'
        : 'Reporte de ingresos actualizado exitosamente';
    
    echo json_encode([
        'success' => true,
        'message' => $mensaje,
        'reporte_id' => $reporte_id,
        'total_general' => $total_general
    ]);
    
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode([
        'success' => false,
        'message' => 'Error al procesar reporte: ' . $e->getMessage()
    ]);
}
?>