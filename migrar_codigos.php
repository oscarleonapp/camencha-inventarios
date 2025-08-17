<?php
/**
 * Script para migrar códigos existentes al nuevo formato automático
 * URL: http://localhost/inventario-claude/migrar_codigos.php
 */

require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'includes/codigo_generator.php';

// Verificar que el usuario tenga permisos de administrador
verificarLogin();
verificarPermiso('productos_ver');

$database = new Database();
$db = $database->getConnection();
$codigoGenerator = new CodigoGenerator($db);

$dry_run = isset($_GET['dry_run']) && $_GET['dry_run'] === '0' ? false : true;

echo "<h2>Migración de Códigos al Nuevo Formato</h2>";

if ($dry_run) {
    echo "<div style='background: #fff3cd; border: 1px solid #ffeeba; color: #856404; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
    echo "<h4>⚠️ Modo de Vista Previa (Dry Run)</h4>";
    echo "<p>Este es un modo de vista previa. No se harán cambios reales en la base de datos.</p>";
    echo "<p><a href='?dry_run=0' style='color: #007bff;'>Ejecutar migración real</a></p>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
    echo "<h4>🚨 Modo de Migración Real</h4>";
    echo "<p>Se realizarán cambios permanentes en la base de datos.</p>";
    echo "<p><a href='?dry_run=1' style='color: #007bff;'>Volver al modo de vista previa</a></p>";
    echo "</div>";
}

try {
    echo "<h3>Migración de Productos</h3>";
    $resultado_productos = $codigoGenerator->migrarCodigosExistentes('producto', $dry_run);
    
    echo "<h4>Resultados:</h4>";
    echo "<p><strong>Total de registros a procesar:</strong> {$resultado_productos['total_registros']}</p>";
    
    if (!empty($resultado_productos['codigos_generados'])) {
        echo "<h5>Códigos generados:</h5>";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>ID</th><th>Código Anterior</th><th>Código Nuevo</th></tr>";
        foreach ($resultado_productos['codigos_generados'] as $item) {
            echo "<tr>";
            echo "<td>{$item['id']}</td>";
            echo "<td>" . ($item['codigo_anterior'] ?: '<em>Sin código</em>') . "</td>";
            echo "<td><strong>{$item['codigo_nuevo']}</strong></td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: green;'>✅ No hay productos que requieran migración.</p>";
    }
    
    if (!empty($resultado_productos['errores'])) {
        echo "<h5>Errores encontrados:</h5>";
        foreach ($resultado_productos['errores'] as $error) {
            echo "<p style='color: red;'>❌ $error</p>";
        }
    }
    
    echo "<h3>Verificación de Otros Módulos</h3>";
    
    // Verificar otros tipos de entidades
    $tipos_verificar = ['venta', 'usuario', 'tienda', 'vendedor', 'reparacion', 'boleta'];
    
    foreach ($tipos_verificar as $tipo) {
        echo "<h4>Verificando: $tipo</h4>";
        try {
            $resultado = $codigoGenerator->migrarCodigosExistentes($tipo, true); // Solo vista previa
            echo "<p>Registros que requieren migración: {$resultado['total_registros']}</p>";
            
            if ($resultado['total_registros'] > 0 && !$dry_run) {
                echo "<p><em>Ejecutando migración...</em></p>";
                $resultado_real = $codigoGenerator->migrarCodigosExistentes($tipo, false);
                echo "<p>✅ Migrados: " . count($resultado_real['codigos_generados']) . " registros</p>";
            }
        } catch (Exception $e) {
            echo "<p style='color: orange;'>⚠️ Error verificando $tipo: {$e->getMessage()}</p>";
        }
    }
    
    echo "<h3>Estadísticas Finales</h3>";
    $stats_finales = $codigoGenerator->obtenerEstadisticas();
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Tipo</th><th>Total Registros</th><th>Con Formato Nuevo</th><th>Porcentaje</th></tr>";
    foreach ($stats_finales as $tipo => $stat) {
        $porcentaje = $stat['total'] > 0 ? round(($stat['con_patron'] / $stat['total']) * 100, 2) : 0;
        $color = $porcentaje == 100 ? 'green' : ($porcentaje > 0 ? 'orange' : 'red');
        echo "<tr>";
        echo "<td>$tipo</td>";
        echo "<td>{$stat['total']}</td>";
        echo "<td>{$stat['con_patron']}</td>";
        echo "<td style='color: $color;'>{$porcentaje}%</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    if (!$dry_run) {
        echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; margin: 20px 0; border-radius: 5px;'>";
        echo "<h4>🎉 Migración Completada</h4>";
        echo "<p>Los códigos han sido migrados exitosamente al nuevo formato automático.</p>";
        echo "<p><strong>Beneficios del nuevo sistema:</strong></p>";
        echo "<ul>";
        echo "<li>Códigos únicos garantizados automáticamente</li>";
        echo "<li>Formato consistente en todo el sistema</li>";
        echo "<li>Trazabilidad temporal con año incluido</li>";
        echo "<li>Prevención de conflictos en la base de datos</li>";
        echo "</ul>";
        echo "</div>";
        
        // Log de la migración
        require_once 'includes/logger.php';
        getLogger()->info('migracion_codigos', 'sistema', 'Migración de códigos completada', [
            'productos_migrados' => count($resultado_productos['codigos_generados']),
            'usuario_ejecutor' => $_SESSION['user_id'] ?? 'desconocido'
        ]);
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; margin: 20px 0; border-radius: 5px;'>";
    echo "<h4>❌ Error en la migración</h4>";
    echo "<p>{$e->getMessage()}</p>";
    echo "</div>";
}

echo "<div style='margin: 20px 0;'>";
echo "<p><a href='productos.php'>← Volver a Gestión de Productos</a></p>";
echo "<p><a href='test_codigo_generator.php'>Probar Generador de Códigos</a></p>";
echo "</div>";
?>