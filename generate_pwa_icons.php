<?php
// Generador de iconos PWA para el Sistema de Inventarios
require_once 'includes/auth.php';
verificarLogin();
verificarPermiso('config_sistema');

// Tamaños de iconos requeridos
$sizes = [72, 96, 128, 144, 152, 192, 384, 512];

// Crear directorio si no existe
$iconDir = 'assets/icons/';
if (!is_dir($iconDir)) {
    mkdir($iconDir, 0755, true);
}

// Función para generar icono SVG
function generateIconSVG($size, $color = '#007bff') {
    $svg = '<?xml version="1.0" encoding="UTF-8"?>
<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 ' . $size . ' ' . $size . '" xmlns="http://www.w3.org/2000/svg">
    <defs>
        <linearGradient id="bgGradient" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" style="stop-color:' . $color . ';stop-opacity:1" />
            <stop offset="100%" style="stop-color:' . adjustBrightness($color, -20) . ';stop-opacity:1" />
        </linearGradient>
    </defs>
    
    <!-- Fondo con gradiente -->
    <rect width="' . $size . '" height="' . $size . '" rx="' . ($size * 0.15) . '" fill="url(#bgGradient)"/>
    
    <!-- Icono de cajas (inventario) -->
    <g transform="translate(' . ($size * 0.2) . ',' . ($size * 0.2) . ') scale(' . ($size / 200) . ')">
        <!-- Caja principal -->
        <rect x="20" y="40" width="80" height="60" fill="white" opacity="0.9" rx="4"/>
        <rect x="20" y="40" width="80" height="15" fill="white" opacity="0.7"/>
        
        <!-- Caja secundaria -->
        <rect x="60" y="20" width="80" height="60" fill="white" opacity="0.8" rx="4"/>
        <rect x="60" y="20" width="80" height="15" fill="white" opacity="0.6"/>
        
        <!-- Caja terciaria -->
        <rect x="40" y="60" width="80" height="60" fill="white" opacity="0.7" rx="4"/>
        <rect x="40" y="60" width="80" height="15" fill="white" opacity="0.5"/>
        
        <!-- Detalles decorativos -->
        <circle cx="50" cy="47" r="3" fill="' . $color . '" opacity="0.8"/>
        <circle cx="90" cy="27" r="3" fill="' . $color . '" opacity="0.8"/>
        <circle cx="70" cy="67" r="3" fill="' . $color . '" opacity="0.8"/>
    </g>
    
    <!-- Texto "I" estilizado en la esquina -->
    <text x="' . ($size * 0.85) . '" y="' . ($size * 0.25) . '" 
          font-family="Arial, sans-serif" 
          font-size="' . ($size * 0.15) . '" 
          font-weight="bold" 
          fill="white" 
          text-anchor="middle">I</text>
</svg>';
    
    return $svg;
}

// Función para ajustar brillo de color
function adjustBrightness($hex, $percent) {
    $hex = str_replace('#', '', $hex);
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    
    $r = max(0, min(255, $r + ($r * $percent / 100)));
    $g = max(0, min(255, $g + ($g * $percent / 100)));
    $b = max(0, min(255, $b + ($b * $percent / 100)));
    
    return sprintf("#%02x%02x%02x", $r, $g, $b);
}

// Función para convertir SVG a PNG (requiere imagick o alternativa)
function svgToPng($svgContent, $outputPath, $size) {
    // Si Imagick está disponible
    if (extension_loaded('imagick')) {
        try {
            $imagick = new Imagick();
            $imagick->readImageBlob($svgContent);
            $imagick->setImageFormat('png');
            $imagick->resizeImage($size, $size, Imagick::FILTER_LANCZOS, 1);
            $imagick->writeImage($outputPath);
            $imagick->clear();
            return true;
        } catch (Exception $e) {
            // Fallar silenciosamente y usar alternativa
        }
    }
    
    // Alternativa: guardar como SVG y usar conversión del lado cliente
    $svgPath = str_replace('.png', '.svg', $outputPath);
    file_put_contents($svgPath, $svgContent);
    
    // Para desarrollo, crear un PNG simple con GD
    if (function_exists('imagecreate')) {
        $image = imagecreatetruecolor($size, $size);
        
        // Colores
        $blue = imagecolorallocate($image, 0, 123, 255);
        $white = imagecolorallocate($image, 255, 255, 255);
        $darkBlue = imagecolorallocate($image, 0, 86, 179);
        
        // Fondo con gradiente simulado
        imagefill($image, 0, 0, $blue);
        
        // Rectángulos simulando cajas
        $boxSize = $size * 0.15;
        $margin = $size * 0.2;
        
        // Caja 1
        imagefilledrectangle($image, $margin, $margin + $boxSize, $margin + $boxSize * 2, $margin + $boxSize * 3, $white);
        
        // Caja 2
        imagefilledrectangle($image, $margin + $boxSize, $margin, $margin + $boxSize * 3, $margin + $boxSize * 2, $white);
        
        // Caja 3
        imagefilledrectangle($image, $margin + $boxSize * 0.5, $margin + $boxSize * 2, $margin + $boxSize * 2.5, $margin + $boxSize * 4, $white);
        
        // Texto "I"
        if ($size >= 72) {
            $fontSize = max(12, $size / 8);
            $textX = $size * 0.85;
            $textY = $size * 0.25;
            
            if (function_exists('imagettftext')) {
                // Usar fuente TTF si está disponible
                imagettftext($image, $fontSize, 0, $textX, $textY, $white, __DIR__ . '/assets/fonts/arial.ttf', 'I');
            } else {
                // Usar fuente built-in
                imagestring($image, 5, $textX, $textY, 'I', $white);
            }
        }
        
        // Guardar
        imagepng($image, $outputPath);
        imagedestroy($image);
        return true;
    }
    
    return false;
}

$generatedIcons = [];
$errors = [];

// Obtener color primario del sistema
require_once 'includes/config_functions.php';
$config = cargarConfiguracion();
$primaryColor = $config['color_primario'] ?? '#007bff';

// Generar iconos
foreach ($sizes as $size) {
    $fileName = "icon-{$size}x{$size}.png";
    $filePath = $iconDir . $fileName;
    
    // Generar SVG
    $svgContent = generateIconSVG($size, $primaryColor);
    
    // Convertir a PNG
    if (svgToPng($svgContent, $filePath, $size)) {
        $generatedIcons[] = [
            'size' => $size,
            'file' => $fileName,
            'path' => $filePath,
            'url' => $fileName
        ];
    } else {
        $errors[] = "Error generando icono {$size}x{$size}";
    }
}

// Si es una petición AJAX
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => !empty($generatedIcons),
        'icons' => $generatedIcons,
        'errors' => $errors
    ]);
    exit;
}

// HTML para mostrar resultado
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generador de Iconos PWA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-mobile-alt"></i> Generador de Iconos PWA</h3>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($generatedIcons)): ?>
                            <div class="alert alert-success">
                                <h5><i class="fas fa-check-circle"></i> Iconos Generados Exitosamente</h5>
                                <p>Se generaron <?php echo count($generatedIcons); ?> iconos PWA usando el color primario del sistema.</p>
                            </div>
                            
                            <div class="row">
                                <?php foreach ($generatedIcons as $icon): ?>
                                    <div class="col-md-3 col-sm-4 col-6 mb-3">
                                        <div class="card text-center">
                                            <div class="card-body">
                                                <img src="<?php echo $iconDir . $icon['file']; ?>" 
                                                     alt="Icono <?php echo $icon['size']; ?>x<?php echo $icon['size']; ?>"
                                                     class="img-fluid mb-2"
                                                     style="max-width: 64px;">
                                                <small class="d-block"><?php echo $icon['size']; ?>x<?php echo $icon['size']; ?></small>
                                                <small class="text-muted"><?php echo $icon['file']; ?></small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-warning">
                                <h5><i class="fas fa-exclamation-triangle"></i> Advertencias</h5>
                                <ul>
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <div class="row mt-4">
                            <div class="col-md-6">
                                <h5>Información PWA</h5>
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item">
                                        <strong>Manifest:</strong> 
                                        <a href="manifest.json" target="_blank">manifest.json</a>
                                    </li>
                                    <li class="list-group-item">
                                        <strong>Service Worker:</strong> 
                                        <a href="sw.js" target="_blank">sw.js</a>
                                    </li>
                                    <li class="list-group-item">
                                        <strong>Página Offline:</strong> 
                                        <a href="offline.php" target="_blank">offline.php</a>
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h5>Instrucciones de Instalación</h5>
                                <ol>
                                    <li>Abre el sistema en un navegador compatible</li>
                                    <li>Busca el prompt "Instalar App" o el icono <i class="fas fa-download"></i></li>
                                    <li>Acepta la instalación</li>
                                    <li>La app aparecerá en tu pantalla de inicio</li>
                                </ol>
                            </div>
                        </div>
                        
                        <div class="text-center mt-4">
                            <a href="index.php" class="btn btn-primary">
                                <i class="fas fa-home"></i> Volver al Dashboard
                            </a>
                            <button onclick="regenerateIcons()" class="btn btn-outline-secondary">
                                <i class="fas fa-sync-alt"></i> Regenerar Iconos
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function regenerateIcons() {
            if (confirm('¿Regenerar todos los iconos PWA? Esto sobrescribirá los iconos existentes.')) {
                window.location.reload();
            }
        }
    </script>
</body>
</html>