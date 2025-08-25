<?php
$titulo = "Dashboard - Sistema de Inventarios";
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'includes/config_functions.php';
require_once 'includes/tienda_security.php';

verificarLogin();
verificarPermiso('dashboard');

$database = new Database();
$db = $database->getConnection();

// Obtener filtros de tienda para el usuario actual
$usuario_id = $_SESSION['usuario_id'];
$filtro_tiendas = getFiltroTiendas($db, $usuario_id, 'v.tienda_id');

$where_ventas = '';
$params_ventas = [];
if (!empty($filtro_tiendas['where'])) {
    $where_ventas = ' AND ' . $filtro_tiendas['where'];
    $params_ventas = $filtro_tiendas['params'];
}

$query_ventas_hoy = "SELECT COUNT(*) as total_ventas, COALESCE(SUM(v.total), 0) as total_dinero 
                     FROM ventas v
                     WHERE DATE(v.fecha) = CURDATE() $where_ventas";
$stmt_ventas_hoy = $db->prepare($query_ventas_hoy);
$stmt_ventas_hoy->execute($params_ventas);
$ventas_hoy = $stmt_ventas_hoy->fetch(PDO::FETCH_ASSOC);

$query_ventas_mes = "SELECT COUNT(*) as total_ventas, COALESCE(SUM(v.total), 0) as total_dinero 
                     FROM ventas v
                     WHERE MONTH(v.fecha) = MONTH(CURDATE()) AND YEAR(v.fecha) = YEAR(CURDATE()) $where_ventas";
$stmt_ventas_mes = $db->prepare($query_ventas_mes);
$stmt_ventas_mes->execute($params_ventas);
$ventas_mes = $stmt_ventas_mes->fetch(PDO::FETCH_ASSOC);

// Filtros para inventarios
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
$stmt_bajo_stock = $db->prepare($query_productos_bajo_stock);
$stmt_bajo_stock->execute($params_inventarios);
$productos_bajo_stock = $stmt_bajo_stock->fetch(PDO::FETCH_ASSOC)['total'];

// Total tiendas - solo mostrar las asignadas al usuario
if (esAdmin()) {
    $query_total_tiendas = "SELECT COUNT(*) as total FROM tiendas WHERE activo = 1";
    $stmt_total_tiendas = $db->prepare($query_total_tiendas);
    $stmt_total_tiendas->execute();
    $total_tiendas = $stmt_total_tiendas->fetch(PDO::FETCH_ASSOC)['total'];
} else {
    $tiendas_usuario = getTiendasUsuario($db, $usuario_id);
    $total_tiendas = count($tiendas_usuario);
}

// Ventas por tienda - solo mostrar tiendas del usuario
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
$stmt_ventas_tienda = $db->prepare($query_ventas_por_tienda);
$stmt_ventas_tienda->execute($params_tiendas);
$ventas_por_tienda = $stmt_ventas_tienda->fetchAll(PDO::FETCH_ASSOC);

// Productos más vendidos - filtrar por tiendas del usuario
$query_productos_mas_vendidos = "SELECT p.nombre, p.codigo, SUM(dv.cantidad) as total_vendido
                                FROM detalle_ventas dv 
                                JOIN productos p ON dv.producto_id = p.id 
                                JOIN ventas v ON dv.venta_id = v.id 
                                WHERE v.fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) $where_ventas
                                GROUP BY p.id, p.nombre, p.codigo 
                                ORDER BY total_vendido DESC 
                                LIMIT 10";
$stmt_mas_vendidos = $db->prepare($query_productos_mas_vendidos);
$stmt_mas_vendidos->execute($params_ventas);
$productos_mas_vendidos = $stmt_mas_vendidos->fetchAll(PDO::FETCH_ASSOC);

// Stock crítico - filtrar por tiendas del usuario
$query_stock_critico = "SELECT i.*, t.nombre as tienda_nombre, p.codigo, p.nombre as producto_nombre
                       FROM inventarios i 
                       JOIN tiendas t ON i.tienda_id = t.id 
                       JOIN productos p ON i.producto_id = p.id 
                       WHERE i.cantidad <= i.cantidad_minima AND p.activo = 1 AND t.activo = 1 $where_inventarios
                       ORDER BY i.cantidad ASC
                       LIMIT 15";
$stmt_stock_critico = $db->prepare($query_stock_critico);
$stmt_stock_critico->execute($params_inventarios);
$stock_critico = $stmt_stock_critico->fetchAll(PDO::FETCH_ASSOC);

// Movimientos recientes - filtrar por tiendas del usuario
$filtro_movimientos = getFiltroTiendas($db, $usuario_id, 'mi.tienda_origen_id');
$where_movimientos = '';
$params_movimientos = [];

if (!esAdmin()) {
    // Para usuarios no admin, mostrar movimientos donde origen O destino sea una de sus tiendas
    $tiendas_usuario_ids = getTiendasUsuario($db, $usuario_id);
    if (!empty($tiendas_usuario_ids)) {
        $placeholders = str_repeat('?,', count($tiendas_usuario_ids) - 1) . '?';
        $where_movimientos = " WHERE (mi.tienda_origen_id IN ($placeholders) OR mi.tienda_destino_id IN ($placeholders))";
        $params_movimientos = array_merge($tiendas_usuario_ids, $tiendas_usuario_ids);
    } else {
        $where_movimientos = " WHERE 1=0"; // Sin tiendas asignadas = sin movimientos
        $params_movimientos = [];
    }
}

$query_movimientos_recientes = "SELECT mi.*, 
                               mi.tipo_movimiento as tipo,
                               t1.nombre as tienda_origen, 
                               t2.nombre as tienda_destino, 
                               p.codigo, 
                               p.nombre as producto_nombre, 
                               u.nombre as usuario_nombre,
                               CASE 
                                   WHEN mi.tienda_origen_id IS NOT NULL THEN t1.nombre 
                                   ELSE 'Sistema' 
                               END as origen_nombre,
                               CASE 
                                   WHEN mi.tienda_destino_id IS NOT NULL THEN t2.nombre 
                                   ELSE 'Sistema' 
                               END as destino_nombre
                               FROM movimientos_inventario mi 
                               LEFT JOIN tiendas t1 ON mi.tienda_origen_id = t1.id 
                               LEFT JOIN tiendas t2 ON mi.tienda_destino_id = t2.id 
                               JOIN productos p ON mi.producto_id = p.id 
                               JOIN usuarios u ON mi.usuario_id = u.id 
                               $where_movimientos
                               ORDER BY mi.fecha DESC LIMIT 10";
$stmt_movimientos = $db->prepare($query_movimientos_recientes);
$stmt_movimientos->execute($params_movimientos);
$movimientos_recientes = $stmt_movimientos->fetchAll(PDO::FETCH_ASSOC);

// Top 10 vendedores del mes - filtrar por tiendas del usuario
$where_vendedores = '';
$params_vendedores = [];

if (!esAdmin()) {
    $tiendas_usuario_ids = getTiendasUsuario($db, $usuario_id);
    if (!empty($tiendas_usuario_ids)) {
        $placeholders = str_repeat('?,', count($tiendas_usuario_ids) - 1) . '?';
        $where_vendedores = " AND v.tienda_id IN ($placeholders)";
        $params_vendedores = $tiendas_usuario_ids;
    } else {
        $where_vendedores = " AND 1=0"; // Sin tiendas = sin vendedores
        $params_vendedores = [];
    }
}

$query_top_vendedores = "SELECT 
                            vend.nombre as nombre_completo,
                            vend.comision_porcentaje,
                            COUNT(v.id) as total_ventas,
                            COALESCE(SUM(v.total), 0) as total_facturado,
                            COALESCE(SUM(cv.monto_comision), 0) as total_comisiones
                         FROM vendedores vend
                         LEFT JOIN ventas v ON vend.id = v.vendedor_id 
                             AND MONTH(v.fecha) = MONTH(CURDATE()) 
                             AND YEAR(v.fecha) = YEAR(CURDATE()) $where_vendedores
                         LEFT JOIN comisiones_vendedores cv ON vend.id = cv.vendedor_id 
                         WHERE vend.activo = 1
                         GROUP BY vend.id, vend.nombre, vend.comision_porcentaje
                         HAVING total_ventas > 0
                         ORDER BY total_facturado DESC 
                         LIMIT 10";
$stmt_top_vendedores = $db->prepare($query_top_vendedores);
$stmt_top_vendedores->execute($params_vendedores);
$top_vendedores = $stmt_top_vendedores->fetchAll(PDO::FETCH_ASSOC);

include 'includes/layout_header.php';
?>

    <!-- Header moderno del dashboard -->
        <div class="d-flex justify-content-between align-items-center mb-4 animate-fade-in-up rs-wrap-sm">
        <div>
            <h1 class="text-gradient mb-2">
                <i class="fas fa-chart-line me-3"></i>
                <span class="editable" data-label="dashboard_titulo">Dashboard Ejecutivo</span>
            </h1>
            <p class="text-muted mb-0">Resumen en tiempo real de tu operación</p>
        </div>
        <div class="d-flex gap-3">
            <button class="btn btn-secondary interactive-hover" onclick="location.reload()">
                <i class="fas fa-sync-alt me-2"></i> Actualizar
            </button>
            <button class="btn btn-primary interactive-hover" onclick="exportarDatos()">
                <i class="fas fa-download me-2"></i> Exportar Datos
            </button>
        </div>
    </div>

    <!-- Stats cards modernas -->
    <div class="stats-grid animate-fade-in-up">
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up"></i>
                    <span>+12%</span>
                </div>
            </div>
            <div class="stat-value"><?php echo number_format($ventas_hoy['total_ventas']); ?></div>
            <div class="stat-label editable" data-label="dashboard_ventas_hoy">Ventas de Hoy</div>
            <div class="mt-3 d-flex justify-content-between align-items-center">
                <small class="text-muted">Total facturado</small>
                <strong class="text-primary"><?php echo formatearMoneda($ventas_hoy['total_dinero']); ?></strong>
            </div>
        </div>

        <div class="stat-card success">
            <div class="stat-header">
                <div class="stat-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up"></i>
                    <span>+8%</span>
                </div>
            </div>
            <div class="stat-value"><?php echo number_format($ventas_mes['total_ventas']); ?></div>
            <div class="stat-label editable" data-label="dashboard_ventas_mes">Ventas del Mes</div>
            <div class="mt-3 d-flex justify-content-between align-items-center">
                <small class="text-muted">Ingresos mensuales</small>
                <strong class="text-success"><?php echo formatearMoneda($ventas_mes['total_dinero']); ?></strong>
            </div>
        </div>

        <div class="stat-card warning">
            <div class="stat-header">
                <div class="stat-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-change negative">
                    <i class="fas fa-arrow-down"></i>
                    <span><?php echo $productos_bajo_stock; ?></span>
                </div>
            </div>
            <div class="stat-value"><?php echo $productos_bajo_stock; ?></div>
            <div class="stat-label editable" data-label="dashboard_stock_bajo">Stock Crítico</div>
            <div class="mt-3">
                <small class="text-muted editable" data-label="dashboard_requieren_atencion">Productos requieren reabastecimiento</small>
            </div>
        </div>

        <div class="stat-card info">
            <div class="stat-header">
                <div class="stat-icon">
                    <i class="fas fa-store"></i>
                </div>
                <div class="stat-change">
                    <i class="fas fa-check-circle"></i>
                    <span>Activas</span>
                </div>
            </div>
            <div class="stat-value"><?php echo $total_tiendas; ?></div>
            <div class="stat-label editable" data-label="dashboard_tiendas_activas">Tiendas en Operación</div>
            <div class="mt-3">
                <small class="text-muted editable" data-label="dashboard_en_operacion">Sucursales funcionando</small>
            </div>
        </div>
    </div>
        
    <!-- Sección de análisis y reportes -->
    <div class="row mt-5 animate-slide-in-right">
        <div class="col-lg-6 mb-4">
            <div class="card interactive-hover">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-store-alt text-primary me-2" style="color: white;"></i>
                            Performance por Tienda
                        </h5>
                        <span class="badge badge-info">Últimos 7 días</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive-md">
                        <table class="table accessibility-fix performance-tiendas-table">
                            <thead>
                                <tr>
                                    <th class="border-0">Tienda</th>
                                    <th class="border-0 text-center">Ventas</th>
                                    <th class="border-0 text-end">Total Facturado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ventas_por_tienda as $index => $venta): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="stat-icon me-3" style="width: 40px; height: 40px; font-size: 1rem;">
                                                <i class="fas fa-building"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold"><?php echo $venta['nombre']; ?></div>
                                                <small class="text-muted">Sucursal #<?php echo $index + 1; ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-primary"><?php echo $venta['total_ventas']; ?></span>
                                    </td>
                                    <td class="text-end">
                                        <strong class="text-success"><?php echo formatearMoneda($venta['total_dinero']); ?></strong>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6 mb-4">
            <div class="card interactive-hover">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-fire text-danger me-2"></i>
                            Productos Top Ventas
                        </h5>
                        <span class="badge badge-success">Últimos 30 días</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive-md">
                        <table class="table accessibility-fix">
                            <thead>
                                <tr>
                                    <th class="border-0">Producto</th>
                                    <th class="border-0 text-center">Código</th>
                                    <th class="border-0 text-end">Vendidos</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($productos_mas_vendidos as $index => $producto): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="text-primary me-3">
                                                <?php if ($index < 3): ?>
                                                    <i class="fas fa-trophy"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-box"></i>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <div class="fw-bold"><?php echo $producto['nombre']; ?></div>
                                                <small class="text-muted">Rank #<?php echo $index + 1; ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <code class="bg-light px-2 py-1 rounded"><?php echo $producto['codigo']; ?></code>
                                    </td>
                                    <td class="text-end">
                                        <span class="badge badge-warning"><?php echo number_format($producto['total_vendido']); ?> unidades</span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
        
        <?php if (count($stock_critico) > 0): ?>
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-warning">
                        <h5 class="text-dark">⚠️ Productos con Stock Crítico</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive-md">
                            <table class="table table-sm accessibility-fix">
                                <thead>
                                    <tr>
                                        <th>Tienda</th>
                                        <th>Código</th>
                                        <th>Producto</th>
                                        <th>Stock Actual</th>
                                        <th>Stock Mínimo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($stock_critico as $item): ?>
                                    <tr class="<?php echo $item['cantidad'] == 0 ? 'table-danger' : 'table-warning'; ?>">
                                        <td><?php echo $item['tienda_nombre']; ?></td>
                                        <td><?php echo $item['codigo']; ?></td>
                                        <td><?php echo $item['producto_nombre']; ?></td>
                                        <td><?php echo $item['cantidad']; ?></td>
                                        <td><?php echo $item['cantidad_minima']; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Top 10 Vendedores del Mes -->
        <?php if (count($top_vendedores) > 0): ?>
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-trophy"></i>
                            🏆 Top 10 Mejores Vendedores del Mes - <?php echo date('M Y'); ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3">
                            <?php foreach (array_slice($top_vendedores, 0, 10) as $index => $vendedor): ?>
                            <div class="col-lg-6 col-xl-4 mb-3" style="color: white;">
                                <div class="card h-100 <?php echo $index < 3 ? 'border-warning' : 'border-light'; ?>">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div>
                                                <h6 class="mb-1">
                                                    <?php if ($index == 0): ?>
                                                        <i class="fas fa-trophy text-warning"></i>
                                                    <?php elseif ($index == 1): ?>
                                                        <i class="fas fa-medal text-secondary"></i>
                                                    <?php elseif ($index == 2): ?>
                                                        <i class="fas fa-award text-warning"></i>
                                                    <?php else: ?>
                                                        <span class="badge bg-primary">#<?php echo $index + 1; ?></span>
                                                    <?php endif; ?>
                                                    <?php echo $vendedor['nombre_completo']; ?>
                                                </h6>
                                                <small class="text-muted" style="color: white important!;">
                                                    Vendedor
                                                    <?php if ($vendedor['comision_porcentaje'] > 0): ?>
                                                        • <?php echo $vendedor['comision_porcentaje']; ?>% comisión
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                        </div>
                                        <div class="row text-center">
                                            <div class="col-4">
                                                <div class="fw-bold text-primary"><?php echo $vendedor['total_ventas']; ?></div>
                                                <small class="text-muted">Ventas</small>
                                            </div>
                                            <div class="col-4">
                                                <div class="fw-bold text-success"><?php echo formatearMoneda($vendedor['total_facturado']); ?></div>
                                                <small class="text-muted">Facturado</small>
                                            </div>
                                            <div class="col-4">
                                                <div class="fw-bold text-warning"><?php echo formatearMoneda($vendedor['total_comisiones']); ?></div>
                                                <small class="text-muted">Comisión</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="text-center mt-3">
                            <a href="reportes_vendedores.php" class="btn btn-outline-success">
                                <i class="fas fa-chart-line"></i>
                                Ver Reporte Completo
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Movimientos Recientes de Inventario</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive-md accessibility-fix">
                            <table class="table table-sm movimientos-log-table accessibility-fix">
                                <thead class="thead-titulos">
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Tipo</th>
                                        <th>Producto</th>
                                        <th>Cambio</th>
                                        <th>Origen/Destino</th>
                                        <th>Usuario</th>
                                        <th>Motivo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($movimientos_recientes)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">
                                            <i class="fas fa-info-circle"></i> No hay movimientos de inventario recientes
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                        <?php foreach ($movimientos_recientes as $movimiento): ?>
                                        <tr>
                                            <td>
                                                <?php echo date('d/m/Y H:i', strtotime($movimiento['fecha'])); ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $movimiento['tipo'] == 'entrada' ? 'success' : 
                                                        ($movimiento['tipo'] == 'salida' ? 'danger' : 
                                                        ($movimiento['tipo'] == 'transferencia' ? 'info' : 
                                                        ($movimiento['tipo'] == 'venta' ? 'primary' :
                                                        ($movimiento['tipo'] == 'devolucion' ? 'secondary' : 'warning')))); ?>">
                                                    <?php 
                                                    $tipos = [
                                                        'entrada' => 'Entrada',
                                                        'salida' => 'Salida', 
                                                        'transferencia' => 'Transferencia',
                                                        'ajuste' => 'Ajuste',
                                                        'venta' => 'Venta',
                                                        'devolucion' => 'Devolución'
                                                    ];
                                                    echo $tipos[$movimiento['tipo']] ?? ucfirst($movimiento['tipo']); 
                                                    ?>
                                                </span>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($movimiento['codigo']); ?></strong><br>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars($movimiento['producto_nombre']); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <?php 
                                                $cantidad_cambio = '';
                                                if ($movimiento['tipo'] == 'entrada' || $movimiento['tipo'] == 'devolucion') {
                                                    $cantidad_cambio = '<span class="text-success">+' . $movimiento['cantidad'] . '</span>';
                                                } elseif ($movimiento['tipo'] == 'salida' || $movimiento['tipo'] == 'venta') {
                                                    $cantidad_cambio = '<span class="text-danger">-' . $movimiento['cantidad'] . '</span>';
                                                } else {
                                                    $cantidad_cambio = $movimiento['cantidad'];
                                                }
                                                echo $cantidad_cambio;
                                                ?>
                                                <br>
                                                <small class="text-muted">
                                                    <?php echo $movimiento['cantidad_anterior']; ?> → <?php echo $movimiento['cantidad_nueva']; ?>
                                                </small>
                                            </td>
                                            <td>
                                                <?php 
                                                if ($movimiento['tipo'] == 'transferencia') {
                                                    echo '<small>De:</small> ' . ($movimiento['tienda_origen'] ?: 'Sistema') . '<br>';
                                                    echo '<small>A:</small> ' . ($movimiento['tienda_destino'] ?: 'Sistema');
                                                } elseif ($movimiento['tipo'] == 'entrada' || $movimiento['tipo'] == 'devolucion') {
                                                    echo '<i class="fas fa-arrow-right text-success"></i> ' . ($movimiento['tienda_destino'] ?: 'Sistema');
                                                } elseif ($movimiento['tipo'] == 'salida' || $movimiento['tipo'] == 'venta') {
                                                    echo '<i class="fas fa-arrow-left text-danger"></i> ' . ($movimiento['tienda_origen'] ?: 'Sistema');
                                                } else {
                                                    echo $movimiento['tienda_origen'] ?: $movimiento['tienda_destino'] ?: 'Sistema';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <i class="fas fa-user"></i>
                                                <?php echo htmlspecialchars($movimiento['usuario_nombre']); ?>
                                            </td>
                                            <td>
                                                <small>
                                                    <?php 
                                                    if (!empty($movimiento['motivo'])) {
                                                        echo htmlspecialchars($movimiento['motivo']);
                                                    } elseif (!empty($movimiento['referencia_tipo']) && !empty($movimiento['referencia_id'])) {
                                                        echo ucfirst($movimiento['referencia_tipo']) . ' #' . $movimiento['referencia_id'];
                                                    } else {
                                                        echo 'Sin especificar';
                                                    }
                                                    ?>
                                                </small>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-center mt-3">
                            <a href="logs_productos.php" class="btn btn-outline-primary">
                                <i class="fas fa-history"></i> Ver Historial Completo de Movimientos
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
<script>
function exportarDatos() {
    window.location.href = 'exportar.php';
}
</script>

<?php include 'includes/layout_footer.php'; ?>
