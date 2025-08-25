<?php
// Script simple para crear iconos PWA básicos
$sizes = [72, 96, 128, 144, 152, 192, 384, 512];
$iconDir = 'assets/icons/';

// Crear directorio si no existe
if (!is_dir($iconDir)) {
    mkdir($iconDir, 0755, true);
}

foreach ($sizes as $size) {
    $fileName = "icon-{$size}x{$size}.png";
    $filePath = $iconDir . $fileName;
    
    // Crear imagen con GD
    $image = imagecreatetruecolor($size, $size);
    
    // Colores
    $blue = imagecolorallocate($image, 0, 123, 255);
    $white = imagecolorallocate($image, 255, 255, 255);
    $lightBlue = imagecolorallocate($image, 108, 177, 255);
    
    // Fondo azul
    imagefill($image, 0, 0, $blue);
    
    // Borde redondeado simulado
    $radius = $size * 0.1;
    
    // Icono de cajas simplificado
    $boxSize = $size * 0.12;
    $margin = $size * 0.25;
    
    // Caja 1 (atrás)
    imagefilledrectangle($image, 
        $margin, 
        $margin + $boxSize, 
        $margin + $boxSize * 1.8, 
        $margin + $boxSize * 3, 
        $white
    );
    
    // Líneas de la caja 1
    imagefilledrectangle($image, 
        $margin, 
        $margin + $boxSize, 
        $margin + $boxSize * 1.8, 
        $margin + $boxSize * 1.3, 
        $lightBlue
    );
    
    // Caja 2 (medio)
    imagefilledrectangle($image, 
        $margin + $boxSize * 0.7, 
        $margin, 
        $margin + $boxSize * 2.5, 
        $margin + $boxSize * 2.2, 
        $white
    );
    
    // Líneas de la caja 2
    imagefilledrectangle($image, 
        $margin + $boxSize * 0.7, 
        $margin, 
        $margin + $boxSize * 2.5, 
        $margin + $boxSize * 0.3, 
        $lightBlue
    );
    
    // Caja 3 (frente)
    imagefilledrectangle($image, 
        $margin + $boxSize * 1.4, 
        $margin + $boxSize * 1.5, 
        $margin + $boxSize * 3.2, 
        $margin + $boxSize * 3.7, 
        $white
    );
    
    // Líneas de la caja 3
    imagefilledrectangle($image, 
        $margin + $boxSize * 1.4, 
        $margin + $boxSize * 1.5, 
        $margin + $boxSize * 3.2, 
        $margin + $boxSize * 1.8, 
        $lightBlue
    );
    
    // Texto "I" para indicar Inventario (solo en iconos grandes)
    if ($size >= 96) {
        $fontSize = max(5, $size / 12);
        $textX = $size * 0.8;
        $textY = $size * 0.2;
        imagestring($image, $fontSize, $textX, $textY, 'I', $white);
    }
    
    // Guardar imagen
    imagepng($image, $filePath);
    imagedestroy($image);
    
    echo "Creado: $fileName\n";
}

echo "Iconos PWA creados exitosamente!\n";
?>