<?php
// Script de debug para verificar dashboard
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'includes/tienda_security.php';

verificarLogin();

$database = new Database();
$db = $database->getConnection();

$usuario_id = $_SESSION['usuario_id'];

echo "<h1>🔍 Debug Dashboard - Usuario: {$_SESSION['usuario_nombre']}</h1>";
echo "<p><strong>Rol:</strong> {$_SESSION['rol']}</p>";
echo "<p><strong>Es Admin:</strong> " . (esAdmin() ? '✅ Sí' : '❌ No') . "</p>";

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

// 2. Verificar filtros generados
echo "<h2>🔍 Filtros de Seguridad</h2>";
$filtro_tiendas = getFiltroTiendas($db, $usuario_id, 'v.tienda_id');
echo "<p><strong>Filtro WHERE:</strong> " . ($filtro_tiendas['where'] ?: 'Sin filtros (Admin)') . "</p>";
echo "<p><strong>Parámetros:</strong> " . json_encode($filtro_tiendas['params']) . "</p>";

// 3. Probar consultas principales del dashboard
echo "<h2>📊 Consultas del Dashboard</h2>";

// Ventas de hoy
$where_ventas = '';
$params_ventas = [];
if (!empty($filtro_tiendas['where'])) {
    $where_ventas = ' AND ' . $filtro_tiendas['where'];
    $params_ventas = $filtro_tiendas['params'];
}

echo "<h3>💰 Ventas de Hoy</h3>";
$query_ventas_hoy = "SELECT COUNT(*) as total_ventas, COALESCE(SUM(v.total), 0) as total_dinero 
                     FROM ventas v
                     WHERE DATE(v.fecha) = CURDATE() $where_ventas";

echo "<p><strong>Query:</strong> <code>$query_ventas_hoy</code></p>";
echo "<p><strong>Params:</strong> " . json_encode($params_ventas) . "</p>";

try {
    $stmt_ventas_hoy = $db->prepare($query_ventas_hoy);
    $stmt_ventas_hoy->execute($params_ventas);
    $ventas_hoy = $stmt_ventas_hoy->fetch(PDO::FETCH_ASSOC);
    echo "<p><strong>Resultado:</strong> {$ventas_hoy['total_ventas']} ventas, Q" . number_format($ventas_hoy['total_dinero'], 2) . "</p>";
} catch (Exception $e) {
    echo "<p class='text-danger'>❌ Error: {$e->getMessage()}</p>";
}

// Ventas del mes
echo "<h3>📈 Ventas del Mes</h3>";
$query_ventas_mes = "SELECT COUNT(*) as total_ventas, COALESCE(SUM(v.total), 0) as total_dinero 
                     FROM ventas v
                     WHERE MONTH(v.fecha) = MONTH(CURDATE()) AND YEAR(v.fecha) = YEAR(CURDATE()) $where_ventas";

try {
    $stmt_ventas_mes = $db->prepare($query_ventas_mes);
    $stmt_ventas_mes->execute($params_ventas);
    $ventas_mes = $stmt_ventas_mes->fetch(PDO::FETCH_ASSOC);
    echo "<p><strong>Resultado:</strong> {$ventas_mes['total_ventas']} ventas, Q" . number_format($ventas_mes['total_dinero'], 2) . "</p>";
} catch (Exception $e) {
    echo "<p class='text-danger'>❌ Error: {$e->getMessage()}</p>";
}

// Stock bajo
echo "<h3>⚠️ Productos con Stock Bajo</h3>";
$filtro_tiendas_inv = getFiltroTiendas($db, $usuario_id, 'i.tienda_id');
$where_inventarios = '';
$params_inventarios = [];
if (!empty($filtro_tiendas_inv['where'])) {
    $where_inventarios = ' AND ' . $filtro_tiendas_inv['where'];
    $params_inventarios = $filtro_tiendas_inv['params'];
}

$query_productos_bajo_stock = "SELECT COUNT(*) as total 
                               FROM inventarios i 
                               JOIN productos p ON i.producto_id = p.id 
                               WHERE i.cantidad <= i.cantidad_minima AND p.activo = 1 $where_inventarios";

try {
    $stmt_bajo_stock = $db->prepare($query_productos_bajo_stock);
    $stmt_bajo_stock->execute($params_inventarios);
    $productos_bajo_stock = $stmt_bajo_stock->fetch(PDO::FETCH_ASSOC)['total'];
    echo "<p><strong>Resultado:</strong> $productos_bajo_stock productos con stock bajo</p>";
} catch (Exception $e) {
    echo "<p class='text-danger'>❌ Error: {$e->getMessage()}</p>";
}

// Total tiendas
echo "<h3>🏢 Total de Tiendas</h3>";
if (esAdmin()) {
    $query_total_tiendas = "SELECT COUNT(*) as total FROM tiendas WHERE activo = 1";
    $stmt_total_tiendas = $db->prepare($query_total_tiendas);
    $stmt_total_tiendas->execute();
    $total_tiendas = $stmt_total_tiendas->fetch(PDO::FETCH_ASSOC)['total'];
    echo "<p><strong>Admin ve:</strong> $total_tiendas tiendas (todas)</p>";
} else {
    $tiendas_usuario_ids = getTiendasUsuario($db, $usuario_id);
    $total_tiendas = count($tiendas_usuario_ids);
    echo "<p><strong>Usuario ve:</strong> $total_tiendas tiendas (solo asignadas)</p>";
}

// Ventas por tienda
echo "<h3>🏪 Ventas por Tienda (últimos 7 días)</h3>";
$filtro_tiendas_t = getFiltroTiendas($db, $usuario_id, 't.id');
$where_tiendas = '';
$params_tiendas = [];
if (!empty($filtro_tiendas_t['where'])) {
    $where_tiendas = ' AND ' . $filtro_tiendas_t['where'];
    $params_tiendas = $filtro_tiendas_t['params'];
}

$query_ventas_por_tienda = "SELECT t.nombre, COUNT(v.id) as total_ventas, COALESCE(SUM(v.total), 0) as total_dinero
                           FROM tiendas t 
                           LEFT JOIN ventas v ON t.id = v.tienda_id AND DATE(v.fecha) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                           WHERE t.activo = 1 $where_tiendas
                           GROUP BY t.id, t.nombre 
                           ORDER BY total_dinero DESC";

try {
    $stmt_ventas_tienda = $db->prepare($query_ventas_por_tienda);
    $stmt_ventas_tienda->execute($params_tiendas);
    $ventas_por_tienda = $stmt_ventas_tienda->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($ventas_por_tienda)) {
        echo "<p>❌ No hay datos de ventas por tienda</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; margin-bottom: 20px;'>";
        echo "<tr><th>Tienda</th><th>Ventas</th><th>Total Q</th></tr>";
        foreach ($ventas_por_tienda as $tienda_venta) {
            echo "<tr>";
            echo "<td>{$tienda_venta['nombre']}</td>";
            echo "<td>{$tienda_venta['total_ventas']}</td>";
            echo "<td>Q" . number_format($tienda_venta['total_dinero'], 2) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p class='text-danger'>❌ Error: {$e->getMessage()}</p>";
}

// Verificar si hay datos reales
echo "<h2>💾 Verificación de Datos</h2>";

// Contar total de ventas
$query_count_ventas = "SELECT COUNT(*) as total FROM ventas";
$stmt_count = $db->prepare($query_count_ventas);
$stmt_count->execute();
$total_ventas_sistema = $stmt_count->fetch()['total'];
echo "<p><strong>Total ventas en sistema:</strong> $total_ventas_sistema</p>";

// Contar inventarios
$query_count_inv = "SELECT COUNT(*) as total FROM inventarios WHERE cantidad > 0";
$stmt_count_inv = $db->prepare($query_count_inv);
$stmt_count_inv->execute();
$total_inventarios = $stmt_count_inv->fetch()['total'];
echo "<p><strong>Total inventarios con stock:</strong> $total_inventarios</p>";

// Productos activos
$query_count_prod = "SELECT COUNT(*) as total FROM productos WHERE activo = 1";
$stmt_count_prod = $db->prepare($query_count_prod);
$stmt_count_prod->execute();
$total_productos = $stmt_count_prod->fetch()['total'];
echo "<p><strong>Total productos activos:</strong> $total_productos</p>";

echo "<hr>";
echo "<p><a href='index.php'>← Volver al Dashboard</a></p>";
?>