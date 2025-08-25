<?php
// Script de debug para verificar stock en POS
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'includes/tienda_security.php';

verificarLogin();

$database = new Database();
$db = $database->getConnection();

$usuario_id = $_SESSION['usuario_id'];

echo "<h1>🔍 Debug Stock POS - Usuario: {$_SESSION['usuario_nombre']}</h1>";

// 1. Verificar tiendas del usuario
echo "<h2>🏪 Tiendas del Usuario</h2>";
$tiendas_usuario = getTiendasUsuarioCompleta($db, $usuario_id);
if (empty($tiendas_usuario)) {
    echo "<p class='text-danger'>❌ Usuario sin tiendas asignadas</p>";
} else {
    echo "<table border='1' style='border-collapse: collapse; margin-bottom: 20px;'>";
    echo "<tr><th>ID</th><th>Nombre</th><th>Es Principal</th></tr>";
    foreach ($tiendas_usuario as $tienda) {
        $es_principal = isset($tienda['es_principal']) && $tienda['es_principal'] ? '✅ Sí' : '❌ No';
        echo "<tr><td>{$tienda['id']}</td><td>{$tienda['nombre']}</td><td>$es_principal</td></tr>";
    }
    echo "</table>";
}

// 2. Tienda principal detectada
$tienda_principal = getTiendaPrincipalUsuario($db, $usuario_id);
if (!$tienda_principal) {
    $tiendas_ids = getTiendasUsuario($db, $usuario_id);
    $tienda_principal = !empty($tiendas_ids) ? $tiendas_ids[0] : null;
}

echo "<h2>🎯 Tienda Principal Detectada</h2>";
echo "<p><strong>Tienda ID:</strong> " . ($tienda_principal ?? 'NULL') . "</p>";

if (!$tienda_principal) {
    echo "<p class='text-danger'>❌ No se pudo determinar tienda principal</p>";
    exit;
}

// 3. Verificar inventarios en la tienda
echo "<h2>📦 Inventarios en Tienda ID: $tienda_principal</h2>";
$query_inventarios = "SELECT i.producto_id, p.nombre, p.codigo, 
                             i.cantidad as total, 
                             COALESCE(i.cantidad_reparacion, 0) as reparacion,
                             (i.cantidad - COALESCE(i.cantidad_reparacion, 0)) as disponible
                      FROM inventarios i 
                      JOIN productos p ON i.producto_id = p.id
                      WHERE i.tienda_id = ? AND p.activo = 1
                      ORDER BY p.nombre";
$stmt_inv = $db->prepare($query_inventarios);
$stmt_inv->execute([$tienda_principal]);
$inventarios = $stmt_inv->fetchAll(PDO::FETCH_ASSOC);

if (empty($inventarios)) {
    echo "<p class='text-warning'>⚠️ No hay inventarios en esta tienda</p>";
} else {
    echo "<table border='1' style='border-collapse: collapse; margin-bottom: 20px;'>";
    echo "<tr><th>ID Producto</th><th>Código</th><th>Nombre</th><th>Total</th><th>En Reparación</th><th>Disponible</th></tr>";
    foreach ($inventarios as $inv) {
        $color = $inv['disponible'] > 0 ? 'green' : 'red';
        echo "<tr>";
        echo "<td>{$inv['producto_id']}</td>";
        echo "<td>{$inv['codigo']}</td>";
        echo "<td>{$inv['nombre']}</td>";
        echo "<td>{$inv['total']}</td>";
        echo "<td>{$inv['reparacion']}</td>";
        echo "<td style='color: $color; font-weight: bold;'>{$inv['disponible']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 4. Simular consulta POS
echo "<h2>🖥️ Simulación Consulta POS</h2>";
$query_pos = "SELECT p.id, p.codigo, p.nombre, p.precio_venta, p.tipo,
                     COALESCE(i.cantidad, 0) as cantidad_total,
                     COALESCE(i.cantidad_reparacion, 0) as cantidad_reparacion,
                     COALESCE(i.cantidad - COALESCE(i.cantidad_reparacion, 0), 0) as stock_disponible,
                     i.tienda_id
              FROM productos p
              LEFT JOIN inventarios i ON p.id = i.producto_id AND i.tienda_id = ?
              WHERE p.activo = 1 
              ORDER BY p.nombre
              LIMIT 10";
$stmt_pos = $db->prepare($query_pos);
$stmt_pos->execute([$tienda_principal]);
$productos_pos = $stmt_pos->fetchAll(PDO::FETCH_ASSOC);

echo "<p><strong>Tienda consultada:</strong> $tienda_principal</p>";
echo "<p><strong>Productos encontrados:</strong> " . count($productos_pos) . "</p>";

if (!empty($productos_pos)) {
    echo "<table border='1' style='border-collapse: collapse; margin-bottom: 20px;'>";
    echo "<tr><th>ID</th><th>Código</th><th>Nombre</th><th>Precio</th><th>Total</th><th>Reparación</th><th>Disponible</th><th>Tienda ID</th></tr>";
    foreach ($productos_pos as $prod) {
        $color = $prod['stock_disponible'] > 0 ? 'green' : 'red';
        echo "<tr>";
        echo "<td>{$prod['id']}</td>";
        echo "<td>{$prod['codigo']}</td>";
        echo "<td>{$prod['nombre']}</td>";
        echo "<td>Q" . number_format($prod['precio_venta'], 2) . "</td>";
        echo "<td>{$prod['cantidad_total']}</td>";
        echo "<td>{$prod['cantidad_reparacion']}</td>";
        echo "<td style='color: $color; font-weight: bold;'>{$prod['stock_disponible']}</td>";
        echo "<td>" . ($prod['tienda_id'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 5. Verificar si hay diferencias
echo "<h2>⚠️ Problemas Detectados</h2>";
$problemas = [];

if (empty($tiendas_usuario)) {
    $problemas[] = "Usuario sin tiendas asignadas";
}

if (!$tienda_principal) {
    $problemas[] = "No se pudo determinar tienda principal";
}

$productos_sin_inventario = array_filter($productos_pos, fn($p) => is_null($p['tienda_id']));
if (!empty($productos_sin_inventario)) {
    $problemas[] = count($productos_sin_inventario) . " productos sin inventario en la tienda $tienda_principal";
}

$productos_con_stock_diferente = [];
foreach ($productos_pos as $prod_pos) {
    $inv = array_filter($inventarios, fn($i) => $i['producto_id'] == $prod_pos['id']);
    if (!empty($inv)) {
        $inv_real = current($inv);
        if ($inv_real['disponible'] != $prod_pos['stock_disponible']) {
            $productos_con_stock_diferente[] = [
                'producto' => $prod_pos['nombre'],
                'pos_stock' => $prod_pos['stock_disponible'],
                'real_stock' => $inv_real['disponible']
            ];
        }
    }
}

if (!empty($productos_con_stock_diferente)) {
    $problemas[] = count($productos_con_stock_diferente) . " productos con stock diferente entre POS e inventario real";
}

if (empty($problemas)) {
    echo "<p class='text-success'>✅ No se detectaron problemas</p>";
} else {
    echo "<ul>";
    foreach ($problemas as $problema) {
        echo "<li class='text-danger'>❌ $problema</li>";
    }
    echo "</ul>";
    
    if (!empty($productos_con_stock_diferente)) {
        echo "<h3>📊 Diferencias de Stock Detalladas</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Producto</th><th>Stock en POS</th><th>Stock Real</th></tr>";
        foreach ($productos_con_stock_diferente as $diff) {
            echo "<tr>";
            echo "<td>{$diff['producto']}</td>";
            echo "<td>{$diff['pos_stock']}</td>";
            echo "<td>{$diff['real_stock']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
}

echo "<hr>";
echo "<p><a href='pos.php'>← Volver a POS</a> | <a href='inventarios.php'>← Ver Inventarios</a></p>";
?>