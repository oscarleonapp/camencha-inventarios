<?php
// Script de prueba para verificar restricciones por tienda
require_once 'config/database.php';
require_once 'includes/tienda_security.php';

$database = new Database();
$db = $database->getConnection();

echo "<h1>Test de Restricciones por Tienda</h1>";

// Simular diferentes usuarios
$usuarios_test = [
    ['id' => 6, 'nombre' => 'Usuario Tienda 1', 'rol' => 'encargado'],
    ['id' => 7, 'nombre' => 'Usuario Tienda 2', 'rol' => 'encargado'], 
    ['id' => 8, 'nombre' => 'Usuario Multi-tienda', 'rol' => 'encargado'],
    ['id' => 1, 'nombre' => 'Admin', 'rol' => 'admin']
];

foreach ($usuarios_test as $usuario) {
    echo "<h2>👤 {$usuario['nombre']} (ID: {$usuario['id']}, Rol: {$usuario['rol']})</h2>";
    
    // Simular sesión de usuario
    $_SESSION['usuario_id'] = $usuario['id'];
    $_SESSION['rol'] = $usuario['rol'];
    
    echo "<h3>🏪 Tiendas Asignadas:</h3>";
    $tiendas_usuario = getTiendasUsuarioCompleta($db, $usuario['id']);
    if (empty($tiendas_usuario)) {
        echo "<p>❌ Sin tiendas asignadas</p>";
    } else {
        foreach ($tiendas_usuario as $tienda) {
            $es_principal = isset($tienda['es_principal']) && $tienda['es_principal'] ? ' (Principal)' : '';
            echo "<p>✅ {$tienda['nombre']}{$es_principal}</p>";
        }
    }
    
    echo "<h3>📦 Inventarios que puede ver:</h3>";
    
    // Usar la misma lógica que inventarios.php
    $filtro_tiendas = getFiltroTiendas($db, $usuario['id'], 'i.tienda_id');
    
    $where_adicional = '';
    $params_inventarios = [];
    if (!empty($filtro_tiendas['where'])) {
        $where_adicional = ' AND ' . $filtro_tiendas['where'];
        $params_inventarios = $filtro_tiendas['params'];
    }
    
    $query_inventarios = "SELECT i.*, t.nombre as tienda_nombre, p.nombre as producto_nombre, i.cantidad
                          FROM inventarios i 
                          JOIN tiendas t ON i.tienda_id = t.id 
                          JOIN productos p ON i.producto_id = p.id 
                          WHERE t.activo = 1 AND p.activo = 1 AND i.cantidad > 0 $where_adicional
                          ORDER BY t.nombre, p.nombre";
    
    $stmt_inventarios = $db->prepare($query_inventarios);
    $stmt_inventarios->execute($params_inventarios);
    $inventarios = $stmt_inventarios->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($inventarios)) {
        echo "<p>❌ No puede ver inventarios (sin tiendas asignadas)</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; margin-bottom: 20px;'>";
        echo "<tr><th>Tienda</th><th>Producto</th><th>Cantidad</th></tr>";
        foreach ($inventarios as $inv) {
            echo "<tr>";
            echo "<td>{$inv['tienda_nombre']}</td>";
            echo "<td>{$inv['producto_nombre']}</td>";
            echo "<td>{$inv['cantidad']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h3>💰 Ventas que puede ver:</h3>";
    
    // Usar la misma lógica que ventas.php
    $filtro_tiendas_ventas = getFiltroTiendas($db, $usuario['id'], 'v.tienda_id');
    
    $where_ventas = '';
    $params_ventas = [];
    if (!empty($filtro_tiendas_ventas['where'])) {
        $where_ventas = ' WHERE ' . $filtro_tiendas_ventas['where'];
        $params_ventas = $filtro_tiendas_ventas['params'];
    }
    
    $query_ventas = "SELECT v.id, v.total, t.nombre as tienda_nombre, v.fecha
                     FROM ventas v 
                     JOIN tiendas t ON v.tienda_id = t.id 
                     $where_ventas
                     ORDER BY v.fecha DESC LIMIT 10";
    
    $stmt_ventas = $db->prepare($query_ventas);
    $stmt_ventas->execute($params_ventas);
    $ventas = $stmt_ventas->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($ventas)) {
        echo "<p>❌ No puede ver ventas (sin tiendas asignadas o sin ventas)</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; margin-bottom: 20px;'>";
        echo "<tr><th>ID Venta</th><th>Tienda</th><th>Total</th><th>Fecha</th></tr>";
        foreach ($ventas as $venta) {
            echo "<tr>";
            echo "<td>#{$venta['id']}</td>";
            echo "<td>{$venta['tienda_nombre']}</td>";
            echo "<td>$" . number_format($venta['total'], 2) . "</td>";
            echo "<td>{$venta['fecha']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h3>🔐 Pruebas de Acceso a Tiendas:</h3>";
    $todas_tiendas = [1 => 'Tienda Principal', 2 => 'Tienda Norte', 3 => 'Tienda Sur', 4 => 'Tienda Centro', 6 => 'Nissi'];
    
    foreach ($todas_tiendas as $tienda_id => $tienda_nombre) {
        $tiene_acceso = tieneAccesoTienda($db, $usuario['id'], $tienda_id);
        $icono = $tiene_acceso ? '✅' : '❌';
        echo "<p>$icono $tienda_nombre (ID: $tienda_id)</p>";
    }
    
    echo "<hr style='margin: 30px 0;'>";
}

// Limpiar sesión
unset($_SESSION['usuario_id']);
unset($_SESSION['rol']);

echo "<h2>✅ Test Completado</h2>";
echo "<p><strong>Resultados esperados:</strong></p>";
echo "<ul>";
echo "<li>Usuario Tienda 1: Solo ve inventarios y ventas de Tienda Principal</li>";
echo "<li>Usuario Tienda 2: Solo ve inventarios y ventas de Tienda Norte</li>";
echo "<li>Usuario Multi-tienda: Ve inventarios y ventas de Tienda Principal, Norte y Sur</li>";
echo "<li>Admin: Ve todo (todas las tiendas)</li>";
echo "</ul>";

echo "<p><a href='inventarios.php'>← Volver a Inventarios</a> | <a href='pos.php'>← Ir a POS</a></p>";
?>