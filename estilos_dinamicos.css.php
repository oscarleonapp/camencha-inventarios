<?php
/**
 * Endpoint CSS para estilos dinámicos
 * Sirve CSS personalizado basado en la configuración del sistema
 */

require_once 'config/database.php';
require_once 'includes/estilos_dinamicos.php';

// Configurar headers para CSS
header('Content-Type: text/css; charset=utf-8');
header('Cache-Control: public, max-age=3600'); // Cache por 1 hora
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');

// Generar y mostrar CSS dinámico
echo generarEstilosDinamicos();
?>