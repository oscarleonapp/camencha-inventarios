<?php
$titulo = "Reportes de Vendedores - Sistema de Inventarios";
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'includes/config_functions.php';

verificarLogin();
verificarPermiso('ventas_ver');

$database = new Database();
$db = $database->getConnection();

// Filtros
$mes_seleccionado = $_GET['mes'] ?? date('Y-m');
$vendedor_filtro = $_GET['vendedor'] ?? '';
$tienda_filtro = $_GET['tienda'] ?? '';

// Obtener datos para filtros
$query_vendedores = "SELECT * FROM vendedores WHERE activo = 1 ORDER BY nombre";
$stmt_vendedores = $db->prepare($query_vendedores);
$stmt_vendedores->execute();
$vendedores = $stmt_vendedores->fetchAll(PDO::FETCH_ASSOC);

$query_tiendas = "SELECT * FROM tiendas WHERE activo = 1 ORDER BY nombre";
$stmt_tiendas = $db->prepare($query_tiendas);
$stmt_tiendas->execute();
$tiendas = $stmt_tiendas->fetchAll(PDO::FETCH_ASSOC);

// Construir filtros para consultas
$where_conditions = ["1=1"];
$params = [];

if ($vendedor_filtro) {
    $where_conditions[] = "v.vendedor_id = ?";
    $params[] = $vendedor_filtro;
}

if ($tienda_filtro) {
    $where_conditions[] = "v.tienda_id = ?";
    $params[] = $tienda_filtro;
}

// Agregar filtro de mes
$where_conditions[] = "DATE_FORMAT(v.fecha, '%Y-%m') = ?";
$params[] = $mes_seleccionado;

$where_clause = implode(' AND ', $where_conditions);

// Ranking de vendedores del mes
$query_ranking = "SELECT 
                      vend.id,
                      vend.nombre as nombre_completo,
                      'vendedor' as tipo_vendedor,
                      vend.comision_porcentaje,
                      'N/A' as tienda_principal,
                      COUNT(v.id) as total_ventas,
                      COALESCE(SUM(v.total), 0) as total_facturado,
                      COALESCE(AVG(v.total), 0) as promedio_venta,
                      COALESCE(SUM(cv.monto_comision), 0) as total_comisiones,
                      COUNT(CASE WHEN v.estado = 'entregada' THEN 1 END) as ventas_entregadas,
                      COUNT(CASE WHEN v.estado = 'reembolsada' THEN 1 END) as ventas_reembolsadas
                  FROM vendedores vend
                  LEFT JOIN ventas v ON vend.id = v.vendedor_id AND {$where_clause}
                  LEFT JOIN comisiones_vendedores cv ON vend.id = cv.vendedor_id
                  WHERE vend.activo = 1
                  GROUP BY vend.id, vend.nombre, vend.comision_porcentaje
                  HAVING total_ventas > 0
                  ORDER BY total_facturado DESC";

$ranking_params = $params;
$stmt_ranking = $db->prepare($query_ranking);
$stmt_ranking->execute($ranking_params);
$ranking_vendedores = $stmt_ranking->fetchAll(PDO::FETCH_ASSOC);

// Estadísticas generales del mes
$query_stats = "SELECT 
                    COUNT(DISTINCT v.vendedor_id) as vendedores_activos,
                    COUNT(v.id) as total_ventas,
                    COALESCE(SUM(v.total), 0) as total_facturado,
                    COALESCE(SUM(cv.monto_comision), 0) as total_comisiones,
                    COALESCE(AVG(v.total), 0) as promedio_venta_general
                FROM ventas v
                LEFT JOIN comisiones_vendedores cv ON v.id = cv.venta_id
                WHERE {$where_clause} AND v.vendedor_id IS NOT NULL";

$stmt_stats = $db->prepare($query_stats);
$stmt_stats->execute($params);
$estadisticas = $stmt_stats->fetch(PDO::FETCH_ASSOC);

// Ventas por día del mes (para gráfico)
$query_ventas_diarias = "SELECT 
                            DATE(v.fecha) as fecha,
                            COUNT(v.id) as cantidad_ventas,
                            COALESCE(SUM(v.total), 0) as total_dia
                         FROM ventas v
                         WHERE {$where_clause} AND v.vendedor_id IS NOT NULL
                         GROUP BY DATE(v.fecha)
                         ORDER BY fecha";

$stmt_ventas_diarias = $db->prepare($query_ventas_diarias);
$stmt_ventas_diarias->execute($params);
$ventas_diarias = $stmt_ventas_diarias->fetchAll(PDO::FETCH_ASSOC);

require_once 'includes/layout_header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 rs-wrap-sm">
        <h2>
            <i class="fas fa-chart-line"></i>
            <span class="editable" data-label="reportes_vendedores_titulo">Reportes de Vendedores</span>
        </h2>
        <div class="btn-group rs-wrap-sm">
            <button class="btn btn-outline-primary" onclick="exportarReporte()">
                <i class="fas fa-download"></i> Exportar
            </button>
            <button class="btn btn-outline-secondary" onclick="window.print()">
                <i class="fas fa-print"></i> Imprimir
            </button>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="mb-0">
                <i class="fas fa-filter"></i>
                Filtros de Reporte
            </h6>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Mes</label>
                    <input type="month" name="mes" class="form-control" value="<?php echo $mes_seleccionado; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Vendedor</label>
                    <select name="vendedor" class="form-select">
                        <option value="">Todos los vendedores</option>
                        <?php foreach ($vendedores as $vendedor): ?>
                            <option value="<?php echo $vendedor['id']; ?>" <?php echo $vendedor_filtro == $vendedor['id'] ? 'selected' : ''; ?>>
                                <?php echo $vendedor['nombre']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tienda</label>
                    <select name="tienda" class="form-select">
                        <option value="">Todas las tiendas</option>
                        <?php foreach ($tiendas as $tienda): ?>
                            <option value="<?php echo $tienda['id']; ?>" <?php echo $tienda_filtro == $tienda['id'] ? 'selected' : ''; ?>>
                                <?php echo $tienda['nombre']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-search"></i> Filtrar
                    </button>
                    <a href="reportes_vendedores.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i> Limpiar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Estadísticas Generales -->
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h4><?php echo $estadisticas['vendedores_activos']; ?></h4>
                    <small>Vendedores Activos</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h4><?php echo $estadisticas['total_ventas']; ?></h4>
                    <small>Total Ventas</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h5 class="moneda"><?php echo number_format($estadisticas['total_facturado'], 2); ?></h5>
                    <small>Total Facturado</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-warning text-dark">
                <div class="card-body text-center">
                    <h5 class="moneda"><?php echo number_format($estadisticas['total_comisiones'], 2); ?></h5>
                    <small>Total Comisiones</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-secondary text-white">
                <div class="card-body text-center">
                    <h5 class="moneda"><?php echo number_format($estadisticas['promedio_venta_general'], 2); ?></h5>
                    <small>Promedio por Venta</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-light text-dark border">
                <div class="card-body text-center">
                    <h5 class="mb-1"><?php echo date('M Y', strtotime($mes_seleccionado . '-01')); ?></h5>
                    <small class="text-muted">Período</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Ranking de Vendedores -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-trophy"></i>
                        Ranking de Vendedores - <?php echo date('M Y', strtotime($mes_seleccionado . '-01')); ?>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($ranking_vendedores)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-user-slash fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No hay ventas de vendedores en el período seleccionado</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive-md">
                            <table class="table table-striped accessibility-fix">
                                <thead class="thead-titulos">
                                    <tr>
                                        <th>Pos.</th>
                                        <th>Vendedor</th>
                                        <th>Tipo</th>
                                        <th>Tienda</th>
                                        <th>Ventas</th>
                                        <th>Facturado</th>
                                        <th>Promedio</th>
                                        <th>Comisiones</th>
                                        <th>Efectividad</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ranking_vendedores as $index => $vendedor): ?>
                                    <tr class="<?php echo $index < 3 ? 'table-warning' : ''; ?>">
                                        <td>
                                            <strong>
                                                <?php if ($index == 0): ?>
                                                    <i class="fas fa-trophy text-warning"></i>
                                                <?php elseif ($index == 1): ?>
                                                    <i class="fas fa-medal text-secondary"></i>
                                                <?php elseif ($index == 2): ?>
                                                    <i class="fas fa-award text-warning"></i>
                                                <?php endif; ?>
                                                #<?php echo $index + 1; ?>
                                            </strong>
                                        </td>
                                        <td>
                                            <strong><?php echo $vendedor['nombre_completo']; ?></strong>
                                            <?php if ($vendedor['comision_porcentaje'] > 0): ?>
                                                <br><small class="text-muted"><?php echo $vendedor['comision_porcentaje']; ?>% comisión</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $vendedor['tipo_vendedor'] == 'empleado' ? 'primary' : 'info'; ?>">
                                                <?php echo ucfirst($vendedor['tipo_vendedor']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small><?php echo $vendedor['tienda_principal'] ?: 'Múltiples'; ?></small>
                                        </td>
                                        <td>
                                            <strong><?php echo $vendedor['total_ventas']; ?></strong>
                                            <?php if ($vendedor['ventas_reembolsadas'] > 0): ?>
                                                <br><small class="text-danger"><?php echo $vendedor['ventas_reembolsadas']; ?> reemb.</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong class="moneda"><?php echo number_format($vendedor['total_facturado'], 2); ?></strong>
                                        </td>
                                        <td class="moneda"><?php echo number_format($vendedor['promedio_venta'], 2); ?></td>
                                        <td>
                                            <strong class="moneda text-success"><?php echo number_format($vendedor['total_comisiones'], 2); ?></strong>
                                        </td>
                                        <td>
                                            <?php 
                                            $efectividad = $vendedor['total_ventas'] > 0 ? 
                                                ($vendedor['ventas_entregadas'] / $vendedor['total_ventas']) * 100 : 0;
                                            ?>
                                            <span class="badge bg-<?php echo $efectividad >= 90 ? 'success' : ($efectividad >= 70 ? 'warning' : 'danger'); ?>">
                                                <?php echo number_format($efectividad, 1); ?>%
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Top 10 del Mes -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-star"></i>
                        Top 10 del Mes
                    </h6>
                </div>
                <div class="card-body">
                    <?php $top10 = array_slice($ranking_vendedores, 0, 10); ?>
                    <?php if (empty($top10)): ?>
                        <p class="text-muted text-center">Sin datos para mostrar</p>
                    <?php else: ?>
                        <?php foreach ($top10 as $index => $vendedor): ?>
                            <div class="d-flex justify-content-between align-items-center mb-2 p-2 <?php echo $index < 3 ? 'bg-light rounded' : ''; ?>">
                                <div>
                                    <small class="fw-bold text-primary">#<?php echo $index + 1; ?></small>
                                    <strong><?php echo $vendedor['nombre_completo']; ?></strong>
                                </div>
                                <div class="text-end">
                                    <div class="moneda fw-bold"><?php echo number_format($vendedor['total_facturado'], 0); ?></div>
                                    <small class="text-muted"><?php echo $vendedor['total_ventas']; ?> ventas</small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Gráfico de Ventas Diarias -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-chart-line"></i>
                        Ventas por Día
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="ventasDiariasChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function exportarReporte() {
    const params = new URLSearchParams(window.location.search);
    params.append('export', 'excel');
    window.open('reportes_vendedores.php?' + params.toString(), '_blank');
}

// Crear gráfico simple (sin Chart.js para simplificar)
document.addEventListener('DOMContentLoaded', function() {
    const canvas = document.getElementById('ventasDiariasChart');
    const ctx = canvas.getContext('2d');
    
    // Datos del servidor
    const ventasDiarias = <?php echo json_encode($ventas_diarias); ?>;
    
    // Dibujar gráfico simple
    if (ventasDiarias.length > 0) {
        // Esta es una implementación básica - en producción usarías Chart.js
        ctx.fillStyle = '#007bff';
        ctx.fillText('Gráfico de ventas diarias', 10, 20);
        ctx.fillText(`${ventasDiarias.length} días con ventas`, 10, 40);
    } else {
        ctx.fillStyle = '#6c757d';
        ctx.fillText('Sin datos para el período', 10, 20);
    }
});
</script>

<style>
@media print {
    .btn, .card-header { display: none !important; }
    .card { border: none !important; box-shadow: none !important; }
}
</style>

<?php require_once 'includes/layout_footer.php'; ?>
