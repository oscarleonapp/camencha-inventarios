<?php
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'includes/qr_generator.php';
require_once 'includes/logger.php';

// Permitir acceso público para escaneos externos, pero registrar si hay sesión
$publico = !isset($_SESSION['usuario_id']);

$database = new Database();
$qr_generator = new QRGenerator($database->getConnection());

$qr_code = $_GET['qr'] ?? $_POST['qr'] ?? null;
$action = $_GET['action'] ?? $_POST['action'] ?? 'view';

if (!$qr_code) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Código QR requerido']);
    exit;
}

// Procesar acción
switch ($action) {
    case 'validate':
        // Solo validar si el QR existe
        $valido = $qr_generator->validarQR($qr_code);
        header('Content-Type: application/json');
        echo json_encode(['valid' => $valido]);
        exit;
        
    case 'info':
        // Obtener información del producto
        $producto = $qr_generator->obtenerProductoPorQR($qr_code);
        if (!$producto) {
            header('HTTP/1.1 404 Not Found');
            echo json_encode(['error' => 'Producto no encontrado']);
            exit;
        }
        
        // Registrar escaneo
        $qr_generator->registrarEscaneo($qr_code, 'consulta', [
            'publico' => $publico,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ]);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'producto' => [
                'id' => $producto['id'],
                'codigo' => $producto['codigo'],
                'nombre' => $producto['nombre'],
                'descripcion' => $producto['descripcion'],
                'precio_venta' => $producto['precio_venta'],
                'tipo' => $producto['tipo']
            ]
        ]);
        exit;
        
    case 'add_to_sale':
        // Agregar a venta (requiere login)
        if ($publico) {
            header('HTTP/1.1 401 Unauthorized');
            echo json_encode(['error' => 'Acceso no autorizado']);
            exit;
        }
        
        verificarPermiso('productos_qr_escanear');
        
        $producto = $qr_generator->obtenerProductoPorQR($qr_code);
        if (!$producto) {
            echo json_encode(['error' => 'Producto no encontrado']);
            exit;
        }
        
        // Registrar escaneo para venta
        $qr_generator->registrarEscaneo($qr_code, 'venta', [
            'accion' => 'agregar_a_venta',
            'timestamp' => time()
        ]);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'producto' => $producto,
            'message' => 'Producto agregado a la venta'
        ]);
        exit;
        
    default:
        // Vista pública del producto
        $producto = $qr_generator->obtenerProductoPorQR($qr_code);
        if (!$producto) {
            header('HTTP/1.1 404 Not Found');
            include '404.php';
            exit;
        }
        
        // Registrar escaneo
        $qr_generator->registrarEscaneo($qr_code, 'consulta', [
            'publico' => $publico,
            'vista' => 'web'
        ]);
}

// Si llegamos aquí, mostrar página de producto
$titulo = "Producto: " . $producto['nombre'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($titulo); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .producto-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 400px;
            width: 100%;
            margin: 20px;
        }
        .producto-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .producto-header h1 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            font-weight: 600;
        }
        .producto-header .codigo {
            font-size: 1.1rem;
            opacity: 0.9;
            font-weight: 300;
        }
        .producto-body {
            padding: 30px 20px;
        }
        .precio {
            font-size: 2rem;
            font-weight: bold;
            color: #28a745;
            text-align: center;
            margin-bottom: 20px;
        }
        .descripcion {
            color: #666;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        .tipo-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            margin-bottom: 20px;
        }
        .tipo-elemento {
            background: #e3f2fd;
            color: #1976d2;
        }
        .tipo-conjunto {
            background: #e8f5e8;
            color: #388e3c;
        }
        .actions {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .qr-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
            text-align: center;
        }
        .powered-by {
            text-align: center;
            margin-top: 20px;
            color: #666;
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
    <div class="producto-card">
        <div class="producto-header">
            <h1><?php echo htmlspecialchars($producto['nombre']); ?></h1>
            <div class="codigo">
                <i class="fas fa-barcode"></i>
                <?php echo htmlspecialchars($producto['codigo']); ?>
            </div>
        </div>
        
        <div class="producto-body">
            <div class="precio">
                Q <?php echo number_format($producto['precio_venta'], 2); ?>
            </div>
            
            <div class="tipo-badge <?php echo $producto['tipo'] === 'elemento' ? 'tipo-elemento' : 'tipo-conjunto'; ?>">
                <i class="fas fa-<?php echo $producto['tipo'] === 'elemento' ? 'cube' : 'cubes'; ?>"></i>
                <?php echo ucfirst($producto['tipo']); ?>
            </div>
            
            <?php if (!empty($producto['descripcion'])): ?>
            <div class="descripcion">
                <?php echo nl2br(htmlspecialchars($producto['descripcion'])); ?>
            </div>
            <?php endif; ?>
            
            <div class="qr-info">
                <i class="fas fa-qrcode fa-2x text-primary mb-2"></i>
                <div><strong>Código QR Válido</strong></div>
                <small class="text-muted">Producto verificado en el sistema</small>
            </div>
            
            <?php if (!$publico): ?>
            <div class="actions">
                <a href="productos.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left"></i> Volver a Productos
                </a>
                <a href="ventas.php?add_product=<?php echo $producto['id']; ?>" class="btn btn-success">
                    <i class="fas fa-shopping-cart"></i> Agregar a Venta
                </a>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="powered-by">
            <i class="fas fa-cog"></i>
            Powered by Sistema de Inventario
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>