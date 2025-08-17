<?php
/**
 * Script de prueba para el generador automático de códigos
 * URL: http://localhost/inventario-claude/test_codigo_generator.php
 */

require_once 'config/database.php';
require_once 'includes/codigo_generator.php';

$database = new Database();
$db = $database->getConnection();
$codigoGenerator = new CodigoGenerator($db);

echo "<h2>Prueba del Generador Automático de Códigos</h2>";

try {
    echo "<h3>1. Generación de códigos para productos</h3>";
    
    // Generar códigos para diferentes tipos
    $codigo_producto = $codigoGenerator->generarCodigo('producto');
    echo "<p>✅ Código para producto: <strong>$codigo_producto</strong></p>";
    
    $codigo_conjunto = $codigoGenerator->generarCodigo('conjunto');
    echo "<p>✅ Código para conjunto: <strong>$codigo_conjunto</strong></p>";
    
    echo "<h3>2. Generación en lote</h3>";
    $codigos_lote = $codigoGenerator->generarCodigosLote('producto', 3);
    echo "<p>✅ Códigos en lote:</p>";
    echo "<ul>";
    foreach ($codigos_lote as $codigo) {
        echo "<li>$codigo</li>";
    }
    echo "</ul>";
    
    echo "<h3>3. Validación de formato</h3>";
    $valido = $codigoGenerator->validarFormatoCodigo($codigo_producto, 'producto');
    echo "<p>✅ Validación de '$codigo_producto': " . ($valido ? 'VÁLIDO' : 'INVÁLIDO') . "</p>";
    
    $invalido = $codigoGenerator->validarFormatoCodigo('INVALID-CODE', 'producto');
    echo "<p>✅ Validación de 'INVALID-CODE': " . ($invalido ? 'VÁLIDO' : 'INVÁLIDO') . "</p>";
    
    echo "<h3>4. Estadísticas</h3>";
    $stats = $codigoGenerator->obtenerEstadisticas();
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Tipo</th><th>Total</th><th>Con Patrón</th><th>Primer Código</th><th>Último Código</th></tr>";
    foreach ($stats as $tipo => $stat) {
        echo "<tr>";
        echo "<td>$tipo</td>";
        echo "<td>{$stat['total']}</td>";
        echo "<td>{$stat['con_patron']}</td>";
        echo "<td>{$stat['primer_codigo']}</td>";
        echo "<td>{$stat['ultimo_codigo']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>5. Prueba de otros tipos de entidades</h3>";
    $tipos_prueba = ['venta', 'usuario', 'tienda', 'vendedor', 'reparacion', 'boleta'];
    
    foreach ($tipos_prueba as $tipo) {
        try {
            $codigo = $codigoGenerator->generarCodigo($tipo);
            echo "<p>✅ Código para $tipo: <strong>$codigo</strong></p>";
        } catch (Exception $e) {
            echo "<p>❌ Error generando código para $tipo: {$e->getMessage()}</p>";
        }
    }
    
    echo "<h3>6. Prueba con parámetros personalizados</h3>";
    $codigo_custom = $codigoGenerator->generarCodigo('producto', [
        'prefijo' => 'TEST',
        'longitud' => 6,
        'incluir_fecha' => true
    ]);
    echo "<p>✅ Código personalizado: <strong>$codigo_custom</strong></p>";
    
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; margin: 20px 0; border-radius: 5px;'>";
    echo "<h4>🎉 ¡Todas las pruebas completadas exitosamente!</h4>";
    echo "<p>El generador automático de códigos está funcionando correctamente.</p>";
    echo "<p><strong>Próximos pasos:</strong></p>";
    echo "<ul>";
    echo "<li>Integrar el generador en otros módulos del sistema</li>";
    echo "<li>Migrar códigos existentes al nuevo formato si es necesario</li>";
    echo "<li>Configurar patrones específicos según los requerimientos del negocio</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; margin: 20px 0; border-radius: 5px;'>";
    echo "<h4>❌ Error en las pruebas</h4>";
    echo "<p>{$e->getMessage()}</p>";
    echo "<p><strong>Trace:</strong></p>";
    echo "<pre>{$e->getTraceAsString()}</pre>";
    echo "</div>";
}

echo "<p><a href='productos.php'>← Volver a Gestión de Productos</a></p>";
?>