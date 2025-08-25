<?php
// Script para generar datos de prueba para el dashboard
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'includes/tienda_security.php';

verificarLogin();

// Solo admin puede generar datos de prueba
if (!esAdmin()) {
    die("Solo administradores pueden ejecutar este script.");
}

$database = new Database();
$db = $database->getConnection();

echo "<h1>🧪 Generar Datos de Prueba para Dashboard</h1>";

try {
    $db->beginTransaction();
    
    // 1. Verificar que hay tiendas
    $query_tiendas = "SELECT id, nombre FROM tiendas WHERE activo = 1 LIMIT 3";
    $stmt_tiendas = $db->prepare($query_tiendas);
    $stmt_tiendas->execute();
    $tiendas = $stmt_tiendas->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($tiendas)) {
        echo "<p class='text-danger'>❌ No hay tiendas activas para generar datos</p>";
        $db->rollBack();
        exit;
    }
    
    echo "<h2>🏪 Tiendas encontradas:</h2>";
    foreach ($tiendas as $tienda) {
        echo "<p>- {$tienda['nombre']} (ID: {$tienda['id']})</p>";
    }
    
    // 2. Verificar que hay productos
    $query_productos = "SELECT id, nombre, precio_venta FROM productos WHERE activo = 1 LIMIT 5";
    $stmt_productos = $db->prepare($query_productos);
    $stmt_productos->execute();
    $productos = $stmt_productos->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($productos)) {
        echo "<p class='text-danger'>❌ No hay productos activos para generar ventas</p>";
        $db->rollBack();
        exit;
    }
    
    echo "<h2>📦 Productos encontrados:</h2>";
    foreach ($productos as $producto) {
        echo "<p>- {$producto['nombre']} - Q" . number_format($producto['precio_venta'], 2) . " (ID: {$producto['id']})</p>";
    }
    
    // 3. Crear algunos inventarios si no existen
    echo "<h2>📊 Creando inventarios...</h2>";
    foreach ($tiendas as $tienda) {
        foreach ($productos as $producto) {
            // Verificar si ya existe inventario
            $query_check_inv = "SELECT id FROM inventarios WHERE tienda_id = ? AND producto_id = ?";
            $stmt_check = $db->prepare($query_check_inv);
            $stmt_check->execute([$tienda['id'], $producto['id']]);
            
            if (!$stmt_check->fetch()) {
                // Crear inventario con stock aleatorio
                $cantidad = rand(5, 50);
                $cantidad_min = rand(2, 8);
                $query_inv = "INSERT INTO inventarios (tienda_id, producto_id, cantidad, cantidad_minima, ubicacion) 
                              VALUES (?, ?, ?, ?, ?)";
                $stmt_inv = $db->prepare($query_inv);
                $stmt_inv->execute([
                    $tienda['id'], 
                    $producto['id'], 
                    $cantidad, 
                    $cantidad_min, 
                    "Estante-" . chr(65 + rand(0, 4)) . rand(1, 9)
                ]);
                echo "<p>✅ Creado inventario: {$producto['nombre']} en {$tienda['nombre']} - Stock: $cantidad</p>";
            }
        }
    }
    
    // 4. Crear algunas ventas de prueba (últimos 30 días)
    echo "<h2>💰 Creando ventas de prueba...</h2>";
    
    // Obtener vendedores
    $query_vendedores = "SELECT id FROM vendedores WHERE activo = 1 LIMIT 3";
    $stmt_vend = $db->prepare($query_vendedores);
    $stmt_vend->execute();
    $vendedores = $stmt_vend->fetchAll(PDO::FETCH_COLUMN);
    
    for ($i = 0; $i < 15; $i++) {
        // Fecha aleatoria en los últimos 30 días
        $dias_atras = rand(0, 30);
        $fecha = date('Y-m-d H:i:s', strtotime("-$dias_atras days"));
        
        // Tienda aleatoria
        $tienda = $tiendas[array_rand($tiendas)];
        
        // Vendedor aleatorio (opcional)
        $vendedor_id = !empty($vendedores) && rand(0, 1) ? $vendedores[array_rand($vendedores)] : null;
        
        // Productos aleatorios para la venta
        $productos_venta = array_rand($productos, rand(1, min(3, count($productos))));
        if (!is_array($productos_venta)) {
            $productos_venta = [$productos_venta];
        }
        
        $subtotal = 0;
        $items_venta = [];
        
        foreach ($productos_venta as $idx) {
            $producto = $productos[$idx];
            $cantidad = rand(1, 3);
            $precio = $producto['precio_venta'];
            $subtotal_item = $cantidad * $precio;
            $subtotal += $subtotal_item;
            
            $items_venta[] = [
                'producto_id' => $producto['id'],
                'cantidad' => $cantidad,
                'precio' => $precio,
                'subtotal' => $subtotal_item
            ];
        }
        
        $descuento = rand(0, 1) ? rand(0, $subtotal * 0.1) : 0;
        $total = $subtotal - $descuento;
        
        // Crear venta
        $query_venta = "INSERT INTO ventas (fecha, subtotal, descuento, total, tienda_id, usuario_id, vendedor_id) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt_venta = $db->prepare($query_venta);
        $stmt_venta->execute([$fecha, $subtotal, $descuento, $total, $tienda['id'], $_SESSION['usuario_id'], $vendedor_id]);
        
        $venta_id = $db->lastInsertId();
        
        // Crear detalles
        foreach ($items_venta as $item) {
            $query_detalle = "INSERT INTO detalle_ventas (venta_id, producto_id, cantidad, precio_unitario, subtotal) 
                              VALUES (?, ?, ?, ?, ?)";
            $stmt_detalle = $db->prepare($query_detalle);
            $stmt_detalle->execute([
                $venta_id, 
                $item['producto_id'], 
                $item['cantidad'], 
                $item['precio'], 
                $item['subtotal']
            ]);
        }
        
        echo "<p>✅ Venta #$venta_id creada: Q" . number_format($total, 2) . " en {$tienda['nombre']} ($fecha)</p>";
    }
    
    // 5. Estadísticas finales
    echo "<h2>📈 Estadísticas Generadas</h2>";
    
    $query_stats = "SELECT 
                        COUNT(*) as total_ventas,
                        SUM(total) as total_dinero,
                        DATE(MIN(fecha)) as primera_venta,
                        DATE(MAX(fecha)) as ultima_venta
                    FROM ventas 
                    WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    $stmt_stats = $db->prepare($query_stats);
    $stmt_stats->execute();
    $stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Total ventas generadas:</strong> {$stats['total_ventas']}</p>";
    echo "<p><strong>Total facturado:</strong> Q" . number_format($stats['total_dinero'], 2) . "</p>";
    echo "<p><strong>Rango de fechas:</strong> {$stats['primera_venta']} a {$stats['ultima_venta']}</p>";
    
    $db->commit();
    echo "<h2>✅ Datos de prueba generados exitosamente</h2>";
    
} catch (Exception $e) {
    $db->rollBack();
    echo "<h2>❌ Error al generar datos</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='index.php'>← Ver Dashboard</a> | <a href='debug_dashboard.php'>Debug Dashboard</a></p>";
?>