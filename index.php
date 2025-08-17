<?php
$titulo = "Dashboard - Sistema de Inventarios";
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'includes/config_functions.php';

verificarLogin();
verificarPermiso('dashboard');

$database = new Database();
$db = $database->getConnection();

$query_ventas_hoy = "SELECT COUNT(*) as total_ventas, COALESCE(SUM(total), 0) as total_dinero 
                     FROM ventas 
                     WHERE DATE(fecha) = CURDATE()";
$stmt_ventas_hoy = $db->prepare($query_ventas_hoy);
$stmt_ventas_hoy->execute();
$ventas_hoy = $stmt_ventas_hoy->fetch(PDO::FETCH_ASSOC);

$query_ventas_mes = "SELECT COUNT(*) as total_ventas, COALESCE(SUM(total), 0) as total_dinero 
                     FROM ventas 
                     WHERE MONTH(fecha) = MONTH(CURDATE()) AND YEAR(fecha) = YEAR(CURDATE())";
$stmt_ventas_mes = $db->prepare($query_ventas_mes);
$stmt_ventas_mes->execute();
$ventas_mes = $stmt_ventas_mes->fetch(PDO::FETCH_ASSOC);

$query_productos_bajo_stock = "SELECT COUNT(*) as total 
                               FROM inventarios i 
                               JOIN productos p ON i.producto_id = p.id 
                               WHERE i.cantidad <= i.cantidad_minima AND p.activo = 1";
$stmt_bajo_stock = $db->prepare($query_productos_bajo_stock);
$stmt_bajo_stock->execute();
$productos_bajo_stock = $stmt_bajo_stock->fetch(PDO::FETCH_ASSOC)['total'];

$query_total_tiendas = "SELECT COUNT(*) as total FROM tiendas WHERE activo = 1";
$stmt_total_tiendas = $db->prepare($query_total_tiendas);
$stmt_total_tiendas->execute();
$total_tiendas = $stmt_total_tiendas->fetch(PDO::FETCH_ASSOC)['total'];

$query_ventas_por_tienda = "SELECT t.nombre, COUNT(v.id) as total_ventas, COALESCE(SUM(v.total), 0) as total_dinero
                           FROM tiendas t 
                           LEFT JOIN ventas v ON t.id = v.tienda_id AND DATE(v.fecha) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                           WHERE t.activo = 1 
                           GROUP BY t.id, t.nombre 
                           ORDER BY total_dinero DESC";
$stmt_ventas_tienda = $db->prepare($query_ventas_por_tienda);
$stmt_ventas_tienda->execute();
$ventas_por_tienda = $stmt_ventas_tienda->fetchAll(PDO::FETCH_ASSOC);

$query_productos_mas_vendidos = "SELECT p.nombre, p.codigo, SUM(dv.cantidad) as total_vendido
                                FROM detalle_ventas dv 
                                JOIN productos p ON dv.producto_id = p.id 
                                JOIN ventas v ON dv.venta_id = v.id 
                                WHERE v.fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                                GROUP BY p.id, p.nombre, p.codigo 
                                ORDER BY total_vendido DESC 
                                LIMIT 10";
$stmt_mas_vendidos = $db->prepare($query_productos_mas_vendidos);
$stmt_mas_vendidos->execute();
$productos_mas_vendidos = $stmt_mas_vendidos->fetchAll(PDO::FETCH_ASSOC);

$query_stock_critico = "SELECT i.*, t.nombre as tienda_nombre, p.codigo, p.nombre as producto_nombre
                       FROM inventarios i 
                       JOIN tiendas t ON i.tienda_id = t.id 
                       JOIN productos p ON i.producto_id = p.id 
                       WHERE i.cantidad <= i.cantidad_minima AND p.activo = 1 AND t.activo = 1
                       ORDER BY i.cantidad ASC";
$stmt_stock_critico = $db->prepare($query_stock_critico);
$stmt_stock_critico->execute();
$stock_critico = $stmt_stock_critico->fetchAll(PDO::FETCH_ASSOC);

$query_movimientos_recientes = "SELECT mi.*, t1.nombre as tienda_origen, t2.nombre as tienda_destino, 
                               p.codigo, p.nombre as producto_nombre, u.nombre as usuario_nombre
                               FROM movimientos_inventario mi 
                               LEFT JOIN tiendas t1 ON mi.tienda_origen_id = t1.id 
                               LEFT JOIN tiendas t2 ON mi.tienda_destino_id = t2.id 
                               JOIN productos p ON mi.producto_id = p.id 
                               JOIN usuarios u ON mi.usuario_id = u.id 
                               ORDER BY mi.fecha DESC LIMIT 10";
$stmt_movimientos = $db->prepare($query_movimientos_recientes);
$stmt_movimientos->execute();
$movimientos_recientes = $stmt_movimientos->fetchAll(PDO::FETCH_ASSOC);

// Top 10 vendedores del mes
$query_top_vendedores = "SELECT 
                            vend.nombre as nombre_completo,
                            vend.comision_porcentaje,
                            COUNT(v.id) as total_ventas,
                            COALESCE(SUM(v.total), 0) as total_facturado,
                            COALESCE(SUM(cv.monto_comision), 0) as total_comisiones
                         FROM vendedores vend
                         LEFT JOIN ventas v ON vend.id = v.vendedor_id 
                             AND MONTH(v.fecha) = MONTH(CURDATE()) 
                             AND YEAR(v.fecha) = YEAR(CURDATE())
                         LEFT JOIN comisiones_vendedores cv ON vend.id = cv.vendedor_id 
                         WHERE vend.activo = 1
                         GROUP BY vend.id, vend.nombre, vend.comision_porcentaje
                         HAVING total_ventas > 0
                         ORDER BY total_facturado DESC 
                         LIMIT 10";
$stmt_top_vendedores = $db->prepare($query_top_vendedores);
$stmt_top_vendedores->execute();
$top_vendedores = $stmt_top_vendedores->fetchAll(PDO::FETCH_ASSOC);

include 'includes/layout_header.php';
?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-tachometer-alt"></i> <span class="editable" data-label="dashboard_titulo">Dashboard</span></h2>
        <div class="btn-group">
            <button class="btn btn-outline-primary btn-sm" onclick="location.reload()">
                <i class="fas fa-sync-alt"></i> Actualizar
            </button>
            <button class="btn btn-outline-success btn-sm" onclick="exportarDatos()">
                <i class="fas fa-download"></i> Exportar
            </button>
        </div>
    </div>
        
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card card-stats">
                    <div class="card-body text-center">
                        <h3><?php echo $ventas_hoy['total_ventas']; ?></h3>
                        <p class="editable" data-label="dashboard_ventas_hoy">Ventas Hoy</p>
                        <small><?php echo formatearMoneda($ventas_hoy['total_dinero']); ?></small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-success">
                    <div class="card-body text-center">
                        <h3><?php echo $ventas_mes['total_ventas']; ?></h3>
                        <p class="editable" data-label="dashboard_ventas_mes">Ventas Este Mes</p>
                        <small><?php echo formatearMoneda($ventas_mes['total_dinero']); ?></small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-warning">
                    <div class="card-body text-center">
                        <h3><?php echo $productos_bajo_stock; ?></h3>
                        <p class="editable" data-label="dashboard_stock_bajo">Productos Bajo Stock</p>
                        <small class="editable" data-label="dashboard_requieren_atencion">Requieren atención</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-danger">
                    <div class="card-body text-center">
                        <h3><?php echo $total_tiendas; ?></h3>
                        <p class="editable" data-label="dashboard_tiendas_activas">Tiendas Activas</p>
                        <small class="editable" data-label="dashboard_en_operacion">En operación</small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Ventas por Tienda (Últimos 7 días)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Tienda</th>
                                        <th>Ventas</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ventas_por_tienda as $venta): ?>
                                    <tr>
                                        <td><?php echo $venta['nombre']; ?></td>
                                        <td><?php echo $venta['total_ventas']; ?></td>
                                        <td><?php echo formatearMoneda($venta['total_dinero']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Productos Más Vendidos (30 días)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>Producto</th>
                                        <th>Vendidos</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($productos_mas_vendidos as $producto): ?>
                                    <tr>
                                        <td><?php echo $producto['codigo']; ?></td>
                                        <td><?php echo $producto['nombre']; ?></td>
                                        <td><?php echo $producto['total_vendido']; ?></td>
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
                        <div class="table-responsive">
                            <table class="table table-sm">
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
                        <div class="row">
                            <?php foreach (array_slice($top_vendedores, 0, 10) as $index => $vendedor): ?>
                            <div class="col-lg-6 col-xl-4 mb-3">
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
                                                <small class="text-muted">
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
                                                <div class="fw-bold moneda text-success"><?php echo number_format($vendedor['total_facturado'], 0); ?></div>
                                                <small class="text-muted">Facturado</small>
                                            </div>
                                            <div class="col-4">
                                                <div class="fw-bold moneda text-warning"><?php echo number_format($vendedor['total_comisiones'], 0); ?></div>
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
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Tipo</th>
                                        <th>Producto</th>
                                        <th>Cantidad</th>
                                        <th>Origen</th>
                                        <th>Destino</th>
                                        <th>Usuario</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($movimientos_recientes as $movimiento): ?>
                                    <tr>
                                        <td><?php echo date('d/m H:i', strtotime($movimiento['fecha'])); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $movimiento['tipo'] == 'entrada' ? 'success' : 
                                                    ($movimiento['tipo'] == 'salida' ? 'danger' : 
                                                    ($movimiento['tipo'] == 'transferencia' ? 'info' : 'warning')); ?>">
                                                <?php echo ucfirst($movimiento['tipo']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $movimiento['codigo'] . ' - ' . $movimiento['producto_nombre']; ?></td>
                                        <td><?php echo $movimiento['cantidad']; ?></td>
                                        <td><?php echo $movimiento['tienda_origen'] ?: '-'; ?></td>
                                        <td><?php echo $movimiento['tienda_destino'] ?: '-'; ?></td>
                                        <td><?php echo $movimiento['usuario_nombre']; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
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