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
verificarPermiso('inventarios_ver');

$database = new Database();
$db = $database->getConnection();

// Obtener datos JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['venta_id']) || !is_numeric($input['venta_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de venta inválido']);
    exit;
}

$venta_id = (int)$input['venta_id'];

try {
    // Obtener productos de la venta específica
    $query = "SELECT 
                dv.id as detalle_id,
                dv.producto_id,
                dv.cantidad,
                dv.precio_unitario,
                p.nombre,
                p.codigo,
                p.imagen,
                v.fecha,
                v.tienda_id,
                t.nombre as tienda_nombre
              FROM detalle_ventas dv
              JOIN productos p ON dv.producto_id = p.id
              JOIN ventas v ON dv.venta_id = v.id
              JOIN tiendas t ON v.tienda_id = t.id
              WHERE dv.venta_id = ? AND v.estado = 'completada'
              ORDER BY p.nombre";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$venta_id]);
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Verificar que existen productos devueltos previamente para restar la cantidad
    foreach ($productos as &$producto) {
        $query_devueltos = "SELECT COALESCE(SUM(cantidad_devuelta), 0) as total_devuelto 
                           FROM devoluciones 
                           WHERE detalle_venta_id = ? AND estado != 'rechazada'";
        $stmt_devueltos = $db->prepare($query_devueltos);
        $stmt_devueltos->execute([$producto['detalle_id']]);
        $devueltos = $stmt_devueltos->fetchColumn();
        
        // Cantidad disponible para devolver
        $producto['cantidad_disponible'] = $producto['cantidad'] - $devueltos;
        $producto['cantidad_devuelta_previa'] = $devueltos;
        
        // Solo mostrar productos que aún tienen cantidad disponible para devolver
        if ($producto['cantidad_disponible'] <= 0) {
            continue;
        }
    }
    
    // Filtrar productos que aún se pueden devolver
    $productos = array_filter($productos, function($producto) {
        return $producto['cantidad_disponible'] > 0;
    });
    
    // Re-indexar array
    $productos = array_values($productos);
    
    if (empty($productos)) {
        echo json_encode([
            'success' => true, 
            'productos' => [], 
            'message' => 'Todos los productos de esta venta ya han sido devueltos completamente'
        ]);
    } else {
        echo json_encode([
            'success' => true, 
            'productos' => $productos
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Error al obtener productos: ' . $e->getMessage()
    ]);
}
?>