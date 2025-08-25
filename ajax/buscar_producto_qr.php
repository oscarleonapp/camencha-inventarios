<?php
// API para buscar productos por código QR
header('Content-Type: application/json');

require_once '../includes/auth.php';
require_once '../config/database.php';
require_once '../includes/tienda_security.php';

// Verificar sesión activa
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

verificarPermiso('ventas_crear');

$database = new Database();
$db = $database->getConnection();

// Leer datos JSON del POST
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['codigo_qr']) || !isset($input['tienda_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    exit;
}

$codigo_qr = trim($input['codigo_qr']);
$tienda_id = (int)$input['tienda_id'];
$usuario_id = $_SESSION['usuario_id'];

try {
    // Validar acceso a la tienda
    validarAccesoTienda($db, $usuario_id, $tienda_id, 'buscar productos QR');
    
    // Buscar producto por diferentes criterios
    $query = "SELECT p.id, p.codigo, p.nombre, p.precio_venta, p.tipo,
                     COALESCE(i.cantidad, 0) as cantidad_total,
                     COALESCE(i.cantidad_reparacion, 0) as cantidad_reparacion,
                     COALESCE(i.cantidad - COALESCE(i.cantidad_reparacion, 0), 0) as stock_disponible,
                     i.tienda_id
              FROM productos p
              LEFT JOIN inventarios i ON p.id = i.producto_id AND i.tienda_id = ?
              WHERE p.activo = 1 AND (
                  p.codigo = ? OR 
                  p.id = ? OR
                  p.codigo LIKE ? OR
                  p.nombre LIKE ?
              )
              ORDER BY 
                  CASE 
                      WHEN p.codigo = ? THEN 1
                      WHEN p.id = ? THEN 2
                      WHEN p.codigo LIKE ? THEN 3
                      ELSE 4
                  END
              LIMIT 1";

    $like_codigo = "%$codigo_qr%";
    $like_nombre = "%$codigo_qr%";
    
    $stmt = $db->prepare($query);
    $stmt->execute([
        $tienda_id,
        $codigo_qr,
        $codigo_qr,
        $like_codigo,
        $like_nombre,
        $codigo_qr,
        $codigo_qr,
        $like_codigo
    ]);
    
    $producto = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($producto) {
        // Producto encontrado
        echo json_encode([
            'success' => true,
            'producto' => $producto,
            'mensaje' => 'Producto encontrado'
        ]);
    } else {
        // Producto no encontrado
        echo json_encode([
            'success' => false,
            'error' => 'Producto no encontrado',
            'codigo_buscado' => $codigo_qr
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>