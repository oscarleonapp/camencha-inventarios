<?php
/**
 * AJAX handler para acciones de códigos QR
 */

require_once '../includes/auth.php';
require_once '../config/database.php';
require_once '../includes/qr_generator.php';
require_once '../includes/logger.php';

// Verificar autenticación
verificarLogin();

// Configurar respuesta JSON
header('Content-Type: application/json');

// Leer input JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['action'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Acción no especificada']);
    exit;
}

$action = $input['action'];
$producto_id = $input['producto_id'] ?? null;

if (!$producto_id || !is_numeric($producto_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID de producto inválido']);
    exit;
}

$database = new Database();
$qr_generator = new QRGenerator($database->getConnection());

try {
    switch ($action) {
        case 'generar':
            // Verificar permisos
            verificarPermiso('productos_qr_generar');
            
            $resultado = $qr_generator->generarQRProducto($producto_id);
            
            if ($resultado['success']) {
                // Log de la acción
                getLogger()->info('qr_generar', 'productos', 
                    "QR generado para producto ID {$producto_id}", [
                        'producto_id' => $producto_id,
                        'qr_code' => $resultado['qr_code']
                    ]
                );
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Código QR generado exitosamente',
                    'qr_code' => $resultado['qr_code']
                ]);
            } else {
                echo json_encode($resultado);
            }
            break;
            
        case 'regenerar':
            // Verificar permisos
            verificarPermiso('productos_qr_generar');
            
            $resultado = $qr_generator->regenerarQR($producto_id);
            
            if ($resultado['success']) {
                // Log de la acción
                getLogger()->info('qr_regenerar', 'productos', 
                    "QR regenerado para producto ID {$producto_id}", [
                        'producto_id' => $producto_id,
                        'nuevo_qr_code' => $resultado['qr_code']
                    ]
                );
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Código QR regenerado exitosamente',
                    'qr_code' => $resultado['qr_code']
                ]);
            } else {
                echo json_encode($resultado);
            }
            break;
            
        case 'estadisticas':
            // Verificar permisos
            verificarPermiso('productos_qr_reportes');
            
            $stats = $qr_generator->obtenerEstadisticasQR($producto_id);
            
            echo json_encode([
                'success' => true,
                'estadisticas' => $stats
            ]);
            break;
            
        case 'escaneos_recientes':
            // Verificar permisos
            verificarPermiso('productos_qr_reportes');
            
            $db = $database->getConnection();
            $query = "SELECT qe.*, u.nombre as usuario_nombre
                      FROM qr_escaneos qe
                      LEFT JOIN usuarios u ON qe.usuario_id = u.id
                      WHERE qe.producto_id = ?
                      ORDER BY qe.created_at DESC
                      LIMIT 10";
            
            $stmt = $db->prepare($query);
            $stmt->execute([$producto_id]);
            $escaneos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'escaneos' => $escaneos
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Acción no válida']);
            break;
    }
    
} catch (Exception $e) {
    // Log del error
    getLogger()->error('qr_ajax_error', 'productos', 
        "Error en acción QR: {$e->getMessage()}", [
            'action' => $action,
            'producto_id' => $producto_id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]
    );
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor: ' . $e->getMessage()
    ]);
}
?>