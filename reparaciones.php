<?php
$titulo = "Reparaciones - Sistema de Inventarios";
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'includes/config_functions.php';

verificarLogin();
verificarPermiso('reparaciones_leer');

$database = new Database();
$db = $database->getConnection();

// Obtener estadísticas de reparaciones
$query_stats = "SELECT 
                   COUNT(*) as total_reparaciones,
                   SUM(CASE WHEN estado = 'enviado' THEN 1 ELSE 0 END) as enviadas,
                   SUM(CASE WHEN estado = 'en_reparacion' THEN 1 ELSE 0 END) as en_proceso,
                   SUM(CASE WHEN estado = 'completado' THEN 1 ELSE 0 END) as completadas,
                   SUM(CASE WHEN estado = 'perdido' THEN 1 ELSE 0 END) as perdidas,
                   SUM(CASE WHEN estado IN ('enviado', 'en_reparacion') THEN cantidad ELSE 0 END) as productos_pendientes,
                   AVG(CASE WHEN estado = 'completado' THEN DATEDIFF(fecha_completado, fecha_envio) ELSE NULL END) as tiempo_promedio
                FROM reparaciones";
$stmt_stats = $db->prepare($query_stats);
$stmt_stats->execute();
$stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);

// Obtener reparaciones recientes
$query_recientes = "SELECT r.*, p.nombre as producto_nombre, p.codigo, t.nombre as tienda_nombre,
                    u1.nombre as usuario_envio, u2.nombre as usuario_retorno,
                    DATEDIFF(NOW(), r.fecha_envio) as dias_transcurridos
                    FROM reparaciones r
                    JOIN productos p ON r.producto_id = p.id
                    JOIN tiendas t ON r.tienda_id = t.id
                    JOIN usuarios u1 ON r.usuario_envio_id = u1.id
                    LEFT JOIN usuarios u2 ON r.usuario_retorno_id = u2.id
                    ORDER BY r.fecha_envio DESC
                    LIMIT 10";
$stmt_recientes = $db->prepare($query_recientes);
$stmt_recientes->execute();
$reparaciones_recientes = $stmt_recientes->fetchAll(PDO::FETCH_ASSOC);

// Obtener productos con más reparaciones
$query_productos_problemas = "SELECT p.codigo, p.nombre, COUNT(*) as total_reparaciones,
                              SUM(CASE WHEN r.estado = 'perdido' THEN 1 ELSE 0 END) as perdidas,
                              AVG(CASE WHEN r.estado = 'completado' THEN DATEDIFF(r.fecha_completado, r.fecha_envio) END) as tiempo_promedio
                              FROM reparaciones r
                              JOIN productos p ON r.producto_id = p.id
                              WHERE r.fecha_envio >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                              GROUP BY p.id, p.codigo, p.nombre
                              HAVING total_reparaciones > 0
                              ORDER BY total_reparaciones DESC
                              LIMIT 10";
$stmt_productos = $db->prepare($query_productos_problemas);
$stmt_productos->execute();
$productos_problemas = $stmt_productos->fetchAll(PDO::FETCH_ASSOC);

// Obtener inventarios con productos en reparación
$query_inventarios_reparacion = "SELECT i.*, p.nombre as producto_nombre, p.codigo, t.nombre as tienda_nombre,
                                  (i.cantidad - i.cantidad_reparacion) as stock_disponible
                                  FROM inventarios i
                                  JOIN productos p ON i.producto_id = p.id
                                  JOIN tiendas t ON i.tienda_id = t.id
                                  WHERE i.cantidad_reparacion > 0 AND p.activo = 1 AND t.activo = 1
                                  ORDER BY i.cantidad_reparacion DESC, t.nombre, p.nombre";
$stmt_inventarios = $db->prepare($query_inventarios_reparacion);
$stmt_inventarios->execute();
$inventarios_reparacion = $stmt_inventarios->fetchAll(PDO::FETCH_ASSOC);

include 'includes/layout_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-tools"></i> Centro de Reparaciones</h2>
    <div class="btn-group">
        <a href="reparaciones_enviar.php" class="btn btn-primary">
            <i class="fas fa-paper-plane"></i> Enviar a Reparación
        </a>
        <a href="reparaciones_recibir.php" class="btn btn-success">
            <i class="fas fa-check-circle"></i> Recibir de Reparación
        </a>
    </div>
</div>

<!-- Estadísticas -->
<div class="row mb-4">
    <div class="col-md-2">
        <div class="card text-center">
            <div class="card-body">
                <h4 class="text-primary"><?php echo $stats['total_reparaciones']; ?></h4>
                <small class="text-muted">Total Reparaciones</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center">
            <div class="card-body">
                <h4 class="text-warning"><?php echo $stats['enviadas']; ?></h4>
                <small class="text-muted">Enviadas</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center">
            <div class="card-body">
                <h4 class="text-info"><?php echo $stats['en_proceso']; ?></h4>
                <small class="text-muted">En Proceso</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center">
            <div class="card-body">
                <h4 class="text-success"><?php echo $stats['completadas']; ?></h4>
                <small class="text-muted">Completadas</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center">
            <div class="card-body">
                <h4 class="text-danger"><?php echo $stats['perdidas']; ?></h4>
                <small class="text-muted">Perdidas</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center">
            <div class="card-body">
                <h4 class="text-secondary"><?php echo $stats['productos_pendientes']; ?></h4>
                <small class="text-muted">Productos Pendientes</small>
            </div>
        </div>
    </div>
</div>

<!-- Accesos Rápidos -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="fas fa-paper-plane fa-3x text-primary mb-3"></i>
                <h5>Enviar a Reparación</h5>
                <p class="text-muted">Envía productos defectuosos o dañados al proceso de reparación</p>
                <a href="reparaciones_enviar.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Nuevo Envío
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                <h5>Recibir de Reparación</h5>
                <p class="text-muted">Procesa productos que regresan de reparación</p>
                <a href="reparaciones_recibir.php" class="btn btn-success">
                    <i class="fas fa-check"></i> Procesar Retornos
                    <?php if ($stats['enviadas'] + $stats['en_proceso'] > 0): ?>
                        <span class="badge bg-white text-success"><?php echo $stats['enviadas'] + $stats['en_proceso']; ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-history"></i> Reparaciones Recientes</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($reparaciones_recientes)): ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Estado</th>
                                    <th>Días</th>
                                    <th>Cantidad</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reparaciones_recientes as $rep): ?>
                                    <tr>
                                        <td>
                                            <small>
                                                <strong><?php echo htmlspecialchars($rep['codigo']); ?></strong><br>
                                                <?php echo htmlspecialchars(substr($rep['producto_nombre'], 0, 25)); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $rep['estado'] === 'enviado' ? 'warning' : 
                                                    ($rep['estado'] === 'en_reparacion' ? 'info' : 
                                                    ($rep['estado'] === 'completado' ? 'success' : 'danger')); ?>">
                                                <?php echo ucfirst($rep['estado']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small class="text-muted"><?php echo $rep['dias_transcurridos']; ?> días</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary"><?php echo $rep['cantidad']; ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-3">
                        <i class="fas fa-info-circle fa-2x text-muted mb-2"></i>
                        <p class="text-muted">No hay reparaciones registradas</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-exclamation-triangle text-warning"></i> Productos con Más Reparaciones</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($productos_problemas)): ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Total</th>
                                    <th>Perdidas</th>
                                    <th>Tiempo Prom.</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($productos_problemas as $prod): ?>
                                    <tr>
                                        <td>
                                            <small>
                                                <strong><?php echo htmlspecialchars($prod['codigo']); ?></strong><br>
                                                <?php echo htmlspecialchars(substr($prod['nombre'], 0, 25)); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $prod['total_reparaciones'] > 5 ? 'danger' : ($prod['total_reparaciones'] > 2 ? 'warning' : 'info'); ?>">
                                                <?php echo $prod['total_reparaciones']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($prod['perdidas'] > 0): ?>
                                                <span class="badge bg-danger"><?php echo $prod['perdidas']; ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">0</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?php echo $prod['tiempo_promedio'] ? round($prod['tiempo_promedio']) . ' días' : '-'; ?>
                                            </small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-3">
                        <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                        <p class="text-muted">No hay productos con problemas frecuentes</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($inventarios_reparacion)): ?>
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="fas fa-tools"></i> 
                        Productos Actualmente en Reparación
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Tienda</th>
                                    <th>Código</th>
                                    <th>Producto</th>
                                    <th>Stock Disponible</th>
                                    <th>En Reparación</th>
                                    <th>Stock Total</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($inventarios_reparacion as $inv): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($inv['tienda_nombre']); ?></td>
                                        <td>
                                            <code><?php echo htmlspecialchars($inv['codigo']); ?></code>
                                        </td>
                                        <td><?php echo htmlspecialchars($inv['producto_nombre']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $inv['stock_disponible'] > 5 ? 'success' : ($inv['stock_disponible'] > 0 ? 'warning' : 'danger'); ?>">
                                                <?php echo $inv['stock_disponible']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">
                                                <i class="fas fa-tools"></i> <?php echo $inv['cantidad_reparacion']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary"><?php echo $inv['cantidad']; ?></span>
                                        </td>
                                        <td>
                                            <?php 
                                            $porcentaje_reparacion = ($inv['cantidad_reparacion'] / $inv['cantidad']) * 100;
                                            if ($porcentaje_reparacion > 50): ?>
                                                <span class="badge bg-danger">
                                                    <i class="fas fa-exclamation-triangle"></i> Crítico
                                                </span>
                                            <?php elseif ($porcentaje_reparacion > 25): ?>
                                                <span class="badge bg-warning">
                                                    <i class="fas fa-exclamation"></i> Alerta
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-info">
                                                    <i class="fas fa-info-circle"></i> Normal
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-3">
                        <small class="text-muted">
                            <i class="fas fa-info-circle"></i>
                            <strong>Stock Disponible</strong> = Stock Total - Cantidad en Reparación. 
                            Los productos en reparación no están disponibles para venta hasta que regresen como "completados".
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body text-center">
                <h6>Tiempo Promedio de Reparación</h6>
                <h4 class="text-primary">
                    <?php 
                    if ($stats['tiempo_promedio']) {
                        echo round($stats['tiempo_promedio']) . ' días';
                    } else {
                        echo 'Sin datos';
                    }
                    ?>
                </h4>
                <small class="text-muted">Basado en reparaciones completadas</small>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/layout_footer.php'; ?>