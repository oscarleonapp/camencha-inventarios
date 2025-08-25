<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

// Verificar que es una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

verificarLogin();
verificarPermiso('inventarios_transferir');

$database = new Database();
$db = $database->getConnection();

// Obtener datos JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['orden_id']) || !is_numeric($input['orden_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de orden inválido']);
    exit;
}

$orden_id = (int)$input['orden_id'];

try {
    // Obtener datos de la orden
    $query_orden = "SELECT oc.*, 
                           ts.nombre as tienda_solicitante_nombre,
                           ts.direccion as tienda_solicitante_direccion,
                           tp.nombre as tienda_proveedora_nombre,
                           tp.direccion as tienda_proveedora_direccion,
                           us.nombre as usuario_solicitante_nombre,
                           ua.nombre as usuario_aprobador_nombre
                    FROM ordenes_compra_internas oc
                    JOIN tiendas ts ON oc.tienda_solicitante_id = ts.id
                    JOIN tiendas tp ON oc.tienda_proveedora_id = tp.id
                    JOIN usuarios us ON oc.usuario_solicitante_id = us.id
                    LEFT JOIN usuarios ua ON oc.usuario_aprobador_id = ua.id
                    WHERE oc.id = ?";
    $stmt_orden = $db->prepare($query_orden);
    $stmt_orden->execute([$orden_id]);
    $orden = $stmt_orden->fetch(PDO::FETCH_ASSOC);
    
    if (!$orden) {
        echo json_encode(['success' => false, 'message' => 'Orden no encontrada']);
        exit;
    }
    
    // Obtener productos de la orden
    $query_productos = "SELECT doc.*, 
                               p.nombre as producto_nombre,
                               p.codigo as producto_codigo,
                               p.tipo as producto_tipo,
                               i.cantidad as stock_disponible,
                               COALESCE(i.cantidad_reparacion, 0) as cantidad_reparacion
                        FROM detalle_ordenes_compra_internas doc
                        JOIN productos p ON doc.producto_id = p.id
                        LEFT JOIN inventarios i ON (i.producto_id = p.id AND i.tienda_id = ?)
                        WHERE doc.orden_compra_id = ?
                        ORDER BY p.nombre";
    $stmt_productos = $db->prepare($query_productos);
    $stmt_productos->execute([$orden['tienda_proveedora_id'], $orden_id]);
    $productos = $stmt_productos->fetchAll(PDO::FETCH_ASSOC);
    
    // Calcular stock disponible real para cada producto
    foreach ($productos as &$producto) {
        $stock_total = (int)$producto['stock_disponible'];
        $cantidad_reparacion = (int)$producto['cantidad_reparacion'];
        $producto['stock_disponible_real'] = max(0, $stock_total - $cantidad_reparacion);
    }
    
    // Obtener información del traslado si existe
    $traslado = null;
    if (in_array($orden['estado'], ['en_transito', 'completada'])) {
        $query_traslado = "SELECT t.*, 
                                  ue.nombre as usuario_envio_nombre,
                                  ur.nombre as usuario_recepcion_nombre
                           FROM traslados t
                           LEFT JOIN usuarios ue ON t.usuario_envio_id = ue.id
                           LEFT JOIN usuarios ur ON t.usuario_recepcion_id = ur.id
                           WHERE t.orden_compra_id = ?";
        $stmt_traslado = $db->prepare($query_traslado);
        $stmt_traslado->execute([$orden_id]);
        $traslado = $stmt_traslado->fetch(PDO::FETCH_ASSOC);
    }
    
    echo json_encode([
        'success' => true,
        'orden' => $orden,
        'productos' => $productos,
        'traslado' => $traslado
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener detalle de orden: ' . $e->getMessage()
    ]);
}
?>