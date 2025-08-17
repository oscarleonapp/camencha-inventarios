<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Debug Completo de Productos.php</h2>";

try {
    echo "<p>1. Iniciando includes...</p>";
    
    $titulo = "Productos - Sistema de Inventarios";
    require_once 'includes/auth.php';
    echo "<p>✅ auth.php cargado</p>";
    
    require_once 'config/database.php';
    echo "<p>✅ database.php cargado</p>";
    
    require_once 'includes/config_functions.php';
    echo "<p>✅ config_functions.php cargado</p>";
    
    require_once 'includes/csrf_protection.php';
    echo "<p>✅ csrf_protection.php cargado</p>";

    echo "<p>2. Verificando login y permisos...</p>";
    verificarLogin();
    echo "<p>✅ Login verificado</p>";
    
    verificarPermiso('productos_ver');
    echo "<p>✅ Permiso productos_ver verificado</p>";

    echo "<p>3. Conectando a base de datos...</p>";
    $database = new Database();
    $db = $database->getConnection();
    echo "<p>✅ Conexión a BD establecida</p>";

    echo "<p>4. Ejecutando consulta de productos...</p>";
    $query_productos = "SELECT * FROM productos WHERE activo = 1 ORDER BY nombre";
    $stmt_productos = $db->prepare($query_productos);
    $stmt_productos->execute();
    $productos = $stmt_productos->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>✅ Consulta ejecutada. Productos encontrados: " . count($productos) . "</p>";

    echo "<p>5. Probando función formatearMoneda...</p>";
    if (function_exists('formatearMoneda')) {
        $test_precio = formatearMoneda(100.50);
        echo "<p>✅ formatearMoneda(100.50) = " . htmlspecialchars($test_precio) . "</p>";
    } else {
        echo "<p>❌ función formatearMoneda NO existe</p>";
    }

    echo "<p>6. Mostrando productos encontrados:</p>";
    if (count($productos) > 0) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Código</th><th>Nombre</th><th>Tipo</th><th>Precio Venta</th><th>Precio Compra</th></tr>";
        
        foreach ($productos as $producto) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($producto['id']) . "</td>";
            echo "<td>" . htmlspecialchars($producto['codigo']) . "</td>";
            echo "<td>" . htmlspecialchars($producto['nombre']) . "</td>";
            echo "<td>" . htmlspecialchars($producto['tipo']) . "</td>";
            
            // Probar formatearMoneda individualmente
            try {
                echo "<td>" . formatearMoneda($producto['precio_venta']) . "</td>";
                echo "<td>" . formatearMoneda($producto['precio_compra']) . "</td>";
            } catch (Exception $e) {
                echo "<td>ERROR: " . $e->getMessage() . "</td>";
                echo "<td>ERROR: " . $e->getMessage() . "</td>";
            }
            
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>❌ No hay productos para mostrar</p>";
    }

    echo "<p>7. Verificando include de layout_header...</p>";
    ob_start();
    include 'includes/layout_header.php';
    $header_content = ob_get_clean();
    echo "<p>✅ layout_header.php incluido correctamente (" . strlen($header_content) . " caracteres)</p>";

} catch (Exception $e) {
    echo "<p>❌ ERROR: " . $e->getMessage() . "</p>";
    echo "<p>Trace: " . $e->getTraceAsString() . "</p>";
}

echo "<hr>";
echo "<h3>Información adicional:</h3>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Error reporting: " . error_reporting() . "</p>";
echo "<p>Display errors: " . ini_get('display_errors') . "</p>";
?>