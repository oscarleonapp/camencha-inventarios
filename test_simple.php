<?php
// Test simple de la función de corrección
function corregirTextoSistema($texto) {
    if (empty($texto)) return $texto;
    
    $correcciones = [
        'Configuracion del sistema' => 'Configuración del sistema',
        'Gestion de roles y permisos' => 'Gestión de roles y permisos'
    ];
    
    return isset($correcciones[$texto]) ? $correcciones[$texto] : $texto;
}

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Test</title></head><body>";
echo "<h1>Test de Corrección UTF-8</h1>";

$textos_prueba = [
    'Configuracion del sistema',
    'Gestion de roles y permisos',
    'Texto normal'
];

foreach ($textos_prueba as $texto) {
    echo "<p>Original: " . $texto . "</p>";
    echo "<p>Corregido: " . corregirTextoSistema($texto) . "</p>";
    echo "<hr>";
}

echo "</body></html>";
?>