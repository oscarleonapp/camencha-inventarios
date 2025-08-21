<?php
require_once 'includes/utf8_config.php';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Test Functions</title></head><body>";
echo "<h1>Test de Funciones UTF-8</h1>";

// Verificar que las funciones existen
$funciones = ['limpiarUTF8', 'escaparUTF8', 'mostrarTexto', 'corregirTextoSistema'];

foreach ($funciones as $funcion) {
    if (function_exists($funcion)) {
        echo "<p>✅ Función $funcion existe</p>";
    } else {
        echo "<p>❌ Función $funcion NO existe</p>";
    }
}

// Test de la función principal
echo "<h2>Test de corregirTextoSistema:</h2>";
$texto = 'Configuracion del sistema';
echo "<p>Original: $texto</p>";
echo "<p>Corregido: " . corregirTextoSistema($texto) . "</p>";

echo "</body></html>";
?>