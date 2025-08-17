<?php
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'includes/qr_generator.php';
require_once 'includes/logger.php';

verificarLogin();
verificarPermiso('productos_qr_descargar');

$producto_id = $_GET['producto_id'] ?? null;
$qr_code = $_GET['qr_code'] ?? null;
$formato = $_GET['formato'] ?? 'png';
$size = max(100, min(1000, intval($_GET['size'] ?? 300)));
$tipo_descarga = $_GET['tipo'] ?? 'imagen'; // imagen, etiqueta, hoja

if (!$producto_id && !$qr_code) {
    header('HTTP/1.1 400 Bad Request');
    exit('Parámetros inválidos');
}

$database = new Database();
$qr_generator = new QRGenerator($database->getConnection());

// Obtener producto
if ($producto_id) {
    $query = "SELECT * FROM productos WHERE id = ? AND activo = 1";
    $stmt = $database->getConnection()->prepare($query);
    $stmt->execute([$producto_id]);
    $producto = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    $producto = $qr_generator->obtenerProductoPorQR($qr_code);
}

if (!$producto) {
    header('HTTP/1.1 404 Not Found');
    exit('Producto no encontrado');
}

// Generar QR si no existe
if (empty($producto['qr_code'])) {
    $resultado = $qr_generator->generarQRProducto($producto['id']);
    if (!$resultado['success']) {
        header('HTTP/1.1 500 Internal Server Error');
        exit('Error generando QR: ' . $resultado['error']);
    }
    $producto['qr_code'] = $resultado['qr_code'];
}

// Log de descarga
getLogger()->info('qr_descarga', 'productos', 
    "Descarga QR para producto {$producto['codigo']}", [
        'producto_id' => $producto['id'],
        'tipo_descarga' => $tipo_descarga,
        'formato' => $formato,
        'size' => $size
    ]
);

if ($tipo_descarga === 'imagen') {
    // Descargar solo la imagen QR
    $qr_url = $qr_generator->obtenerURLImagenQR($producto['qr_code'], $size, $formato);
    
    // Obtener imagen y enviarla
    $imagen_data = file_get_contents($qr_url);
    if ($imagen_data === false) {
        // Fallback a Google Charts
        $qr_url = $qr_generator->obtenerURLImagenQRFallback($producto['qr_code'], $size);
        $imagen_data = file_get_contents($qr_url);
    }
    
    if ($imagen_data !== false) {
        $filename = "QR_{$producto['codigo']}.{$formato}";
        
        header('Content-Type: image/' . $formato);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($imagen_data));
        
        echo $imagen_data;
        exit;
    } else {
        header('HTTP/1.1 500 Internal Server Error');
        exit('Error generando imagen QR');
    }

} elseif ($tipo_descarga === 'etiqueta') {
    // Generar etiqueta HTML para impresión
    $qr_url = $qr_generator->obtenerURLImagenQR($producto['qr_code'], 200, 'png');
    
    $html = generarEtiquetaHTML($producto, $qr_url);
    
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: attachment; filename="Etiqueta_' . $producto['codigo'] . '.html"');
    
    echo $html;
    exit;

} elseif ($tipo_descarga === 'hoja') {
    // Generar hoja con múltiples etiquetas
    $qr_url = $qr_generator->obtenerURLImagenQR($producto['qr_code'], 150, 'png');
    
    $html = generarHojaEtiquetasHTML($producto, $qr_url);
    
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: attachment; filename="Hoja_Etiquetas_' . $producto['codigo'] . '.html"');
    
    echo $html;
    exit;
}

function generarEtiquetaHTML($producto, $qr_url) {
    $config = cargarConfiguracion();
    $empresa = $config['empresa_nombre'] ?? 'Sistema de Inventario';
    
    return "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Etiqueta QR - {$producto['codigo']}</title>
    <style>
        @page {
            size: 4in 2in;
            margin: 0.1in;
        }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 10px;
            background: white;
        }
        .etiqueta {
            width: 100%;
            height: 100%;
            border: 1px solid #ccc;
            display: flex;
            align-items: center;
            padding: 10px;
            box-sizing: border-box;
        }
        .qr-section {
            flex: 0 0 auto;
            margin-right: 15px;
        }
        .qr-section img {
            width: 80px;
            height: 80px;
            display: block;
        }
        .info-section {
            flex: 1;
        }
        .codigo {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .nombre {
            font-size: 11px;
            margin-bottom: 5px;
            line-height: 1.2;
        }
        .precio {
            font-size: 12px;
            font-weight: bold;
            color: #d9534f;
        }
        .empresa {
            font-size: 8px;
            color: #666;
            margin-top: 5px;
        }
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
    </style>
    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</head>
<body>
    <div class='etiqueta'>
        <div class='qr-section'>
            <img src='{$qr_url}' alt='QR Code'>
        </div>
        <div class='info-section'>
            <div class='codigo'>{$producto['codigo']}</div>
            <div class='nombre'>" . htmlspecialchars($producto['nombre']) . "</div>
            <div class='precio'>Q " . number_format($producto['precio_venta'], 2) . "</div>
            <div class='empresa'>{$empresa}</div>
        </div>
    </div>
</body>
</html>";
}

function generarHojaEtiquetasHTML($producto, $qr_url) {
    $config = cargarConfiguracion();
    $empresa = $config['empresa_nombre'] ?? 'Sistema de Inventario';
    
    $etiqueta_html = "
        <div class='etiqueta'>
            <div class='qr-section'>
                <img src='{$qr_url}' alt='QR Code'>
            </div>
            <div class='info-section'>
                <div class='codigo'>{$producto['codigo']}</div>
                <div class='nombre'>" . htmlspecialchars($producto['nombre']) . "</div>
                <div class='precio'>Q " . number_format($producto['precio_venta'], 2) . "</div>
                <div class='empresa'>{$empresa}</div>
            </div>
        </div>
    ";
    
    // Generar 15 etiquetas por hoja (3x5)
    $etiquetas = str_repeat($etiqueta_html, 15);
    
    return "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Hoja Etiquetas QR - {$producto['codigo']}</title>
    <style>
        @page {
            size: A4;
            margin: 0.5in;
        }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: white;
        }
        .hoja {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            grid-template-rows: repeat(5, 1fr);
            gap: 10px;
            width: 100%;
            height: 100vh;
        }
        .etiqueta {
            border: 1px solid #ccc;
            display: flex;
            align-items: center;
            padding: 8px;
            box-sizing: border-box;
            background: white;
        }
        .qr-section {
            flex: 0 0 auto;
            margin-right: 10px;
        }
        .qr-section img {
            width: 60px;
            height: 60px;
            display: block;
        }
        .info-section {
            flex: 1;
            overflow: hidden;
        }
        .codigo {
            font-size: 10px;
            font-weight: bold;
            margin-bottom: 3px;
        }
        .nombre {
            font-size: 8px;
            margin-bottom: 3px;
            line-height: 1.1;
        }
        .precio {
            font-size: 9px;
            font-weight: bold;
            color: #d9534f;
        }
        .empresa {
            font-size: 6px;
            color: #666;
            margin-top: 2px;
        }
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
    </style>
    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</head>
<body>
    <div class='hoja'>
        {$etiquetas}
    </div>
</body>
</html>";
}
?>