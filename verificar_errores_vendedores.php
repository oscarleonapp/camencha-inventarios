<?php
// Script de verificación para errores de vendedores corregidos
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>Verificación de Errores de Vendedores Corregidos</h2>";
    
    // Verificar estructura de tabla vendedores
    echo "<h3>1. Verificando estructura de tabla vendedores...</h3>";
    $stmt = $db->prepare("DESCRIBE vendedores");
    $stmt->execute();
    $columnas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $tiene_apellido = false;
    foreach ($columnas as $columna) {
        if ($columna['Field'] == 'apellido') {
            $tiene_apellido = true;
            break;
        }
    }
    
    echo "- Columna 'apellido' existe: " . ($tiene_apellido ? "❌ SÍ EXISTE (problema)" : "✅ NO EXISTE (correcto)") . "<br>";
    echo "- Columnas disponibles: ";
    foreach ($columnas as $columna) {
        echo $columna['Field'] . ", ";
    }
    echo "<br>";
    
    // Verificar estructura de tabla ventas
    echo "<h3>2. Verificando estructura de tabla ventas...</h3>";
    $stmt = $db->prepare("DESCRIBE ventas");
    $stmt->execute();
    $columnas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $tiene_fecha = false;
    $tiene_fecha_venta = false;
    foreach ($columnas as $columna) {
        if ($columna['Field'] == 'fecha') {
            $tiene_fecha = true;
        }
        if ($columna['Field'] == 'fecha_venta') {
            $tiene_fecha_venta = true;
        }
    }
    
    echo "- Columna 'fecha' existe: " . ($tiene_fecha ? "✅ SÍ EXISTE (correcto)" : "❌ NO EXISTE (problema)") . "<br>";
    echo "- Columna 'fecha_venta' existe: " . ($tiene_fecha_venta ? "❌ SÍ EXISTE (problema)" : "✅ NO EXISTE (correcto)") . "<br>";
    
    // Probar consultas corregidas
    echo "<h3>3. Probando consultas corregidas...</h3>";
    
    // Consulta de vendedores
    try {
        $stmt = $db->prepare("SELECT id, nombre, email, comision_porcentaje FROM vendedores WHERE activo = 1 ORDER BY nombre LIMIT 5");
        $stmt->execute();
        $vendedores = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "✅ Consulta de vendedores: OK (" . count($vendedores) . " vendedores encontrados)<br>";
        
        if (count($vendedores) > 0) {
            echo "- Ejemplo: " . htmlspecialchars($vendedores[0]['nombre']) . "<br>";
        }
    } catch (Exception $e) {
        echo "❌ Error en consulta de vendedores: " . htmlspecialchars($e->getMessage()) . "<br>";
    }
    
    // Consulta de ventas
    try {
        $stmt = $db->prepare("SELECT v.id, v.fecha, v.total, vend.nombre as vendedor_nombre 
                              FROM ventas v 
                              LEFT JOIN vendedores vend ON v.vendedor_id = vend.id 
                              ORDER BY v.fecha DESC LIMIT 5");
        $stmt->execute();
        $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "✅ Consulta de ventas: OK (" . count($ventas) . " ventas encontradas)<br>";
        
        if (count($ventas) > 0) {
            echo "- Ejemplo: Venta #" . $ventas[0]['id'] . " - " . $ventas[0]['fecha'] . "<br>";
        }
    } catch (Exception $e) {
        echo "❌ Error en consulta de ventas: " . htmlspecialchars($e->getMessage()) . "<br>";
    }
    
    // Verificar archivos corregidos
    echo "<h3>4. Verificando archivos corregidos...</h3>";
    $archivos_verificar = [
        'ventas.php',
        'reportes_vendedores.php', 
        'historial_ventas.php',
        'detalle_venta.php'
    ];
    
    foreach ($archivos_verificar as $archivo) {
        if (file_exists($archivo)) {
            $contenido = file_get_contents($archivo);
            $tiene_apellido = strpos($contenido, 'apellido') !== false;
            $tiene_fecha_venta = strpos($contenido, 'fecha_venta') !== false;
            
            echo "- $archivo:<br>";
            echo "  * 'apellido' encontrado: " . ($tiene_apellido ? "❌ AÚN EXISTE" : "✅ Corregido") . "<br>";
            echo "  * 'fecha_venta' encontrado: " . ($tiene_fecha_venta ? "❌ AÚN EXISTE" : "✅ Corregido") . "<br>";
        } else {
            echo "- $archivo: ❌ Archivo no encontrado<br>";
        }
    }
    
    echo "<h3>✅ Verificación completada</h3>";
    echo "<p>Si todos los elementos muestran ✅, los errores de vendedores han sido corregidos exitosamente.</p>";
    
    echo "<h4>Recordatorio:</h4>";
    echo "<ul>";
    echo "<li><strong>Vendedores</strong>: Solo tienen campo 'nombre' (sin apellido)</li>";
    echo "<li><strong>Ventas</strong>: Usan campo 'fecha' (no fecha_venta)</li>";
    echo "<li><strong>Consultas</strong>: Ya no concatenan nombre + apellido</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<h3>❌ Error durante la verificación:</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2 { color: #2c3e50; }
h3 { color: #34495e; }
ul { margin: 10px 0; }
li { margin: 5px 0; }
</style>