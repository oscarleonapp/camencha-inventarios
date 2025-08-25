<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

verificarLogin();
verificarPermiso('ventas_ver');

$database = new Database();
$db = $database->getConnection();

// Parámetros de filtro
$mes_actual = $_GET['mes'] ?? date('n');
$ano_actual = $_GET['ano'] ?? date('Y');
$tienda_filtro = $_GET['tienda'] ?? '';

// Obtener tiendas para filtro
$tiendas_query = "SELECT id, nombre FROM tiendas WHERE activo = 1 ORDER BY nombre";
$tiendas = $db->query($tiendas_query)->fetchAll(PDO::FETCH_ASSOC);

// Construir filtro de período
$periodo_filtro = sprintf("%04d-%02d", $ano_actual, $mes_actual);
$periodo_anterior = sprintf("%04d-%02d", $ano_actual, $mes_actual - 1);
if ($mes_actual == 1) {
    $periodo_anterior = sprintf("%04d-%02d", $ano_actual - 1, 12);
}

$where_conditions = ["rv.periodo = ?"];
$params = [
    $ano_actual,          // Para stats subquery YEAR(cv.fecha_venta) = ?
    $mes_actual,          // Para stats subquery MONTH(cv.fecha_venta) = ?
    $periodo_anterior,    // Para rv_anterior.periodo = ?
    $periodo_filtro       // Para rv.periodo = ?
];

// Obtener ranking de vendedores del mes/año seleccionado
$ranking_query = "SELECT 
                     rv.vendedor_id,
                     rv.cantidad_ventas as ventas_validadas,
                     rv.total_ventas,
                     CASE WHEN rv.cantidad_ventas > 0 THEN rv.total_ventas / rv.cantidad_ventas ELSE 0 END as promedio_venta,
                     rv.comision_ganada as total_comisiones,
                     rv.puntos_ranking,
                     rv.posicion_ranking,
                     vd.nombre as vendedor_nombre,
                     vd.email,
                     vd.telefono,
                     vd.comision_porcentaje,
                     -- Obtener estadísticas adicionales del período actual
                     COALESCE(stats.ticket_promedio, 0) as ticket_promedio,
                     COALESCE(stats.venta_maxima, 0) as venta_maxima,
                     COALESCE(stats.venta_minima, 0) as venta_minima,
                     COALESCE(stats.dias_activos, 0) as dias_activos,
                     COALESCE(stats.tiendas_activas, 0) as tiendas_activas,
                     COALESCE(stats.tiendas_nombres, 'Sin asignar') as tiendas_nombres,
                     -- Performance vs mes anterior
                     rv_anterior.total_ventas as ventas_mes_anterior
                  FROM ranking_vendedores rv
                  JOIN vendedores vd ON rv.vendedor_id = vd.id
                  LEFT JOIN (
                      SELECT 
                          cv.vendedor_id,
                          AVG(cv.monto_venta) as ticket_promedio,
                          MAX(cv.monto_venta) as venta_maxima,
                          MIN(cv.monto_venta) as venta_minima,
                          COUNT(DISTINCT DATE(cv.fecha_venta)) as dias_activos,
                          COUNT(DISTINCT v.tienda_id) as tiendas_activas,
                          GROUP_CONCAT(DISTINCT t.nombre SEPARATOR ', ') as tiendas_nombres
                      FROM comisiones_vendedores cv
                      LEFT JOIN ventas v ON cv.venta_id = v.id
                      LEFT JOIN tiendas t ON v.tienda_id = t.id
                      WHERE YEAR(cv.fecha_venta) = ? 
                      AND MONTH(cv.fecha_venta) = ?
                      GROUP BY cv.vendedor_id
                  ) stats ON rv.vendedor_id = stats.vendedor_id
                  LEFT JOIN ranking_vendedores rv_anterior ON (
                      rv.vendedor_id = rv_anterior.vendedor_id 
                      AND rv_anterior.periodo = ?
                  )
                  WHERE " . implode(' AND ', $where_conditions) . "
                  AND vd.activo = 1
                  ORDER BY rv.puntos_ranking DESC, rv.total_ventas DESC";

$stmt_ranking = $db->prepare($ranking_query);
$stmt_ranking->execute($params);
$ranking = $stmt_ranking->fetchAll(PDO::FETCH_ASSOC);

// Calcular estadísticas globales
$stats_query = "SELECT 
                   COUNT(DISTINCT rv.vendedor_id) as total_vendedores,
                   SUM(rv.total_ventas) as ventas_totales,
                   AVG(rv.promedio_venta) as ticket_promedio_global,
                   SUM(rv.total_comisiones) as comisiones_totales,
                   MAX(rv.puntos_ranking) as max_puntos
                FROM ranking_vendedores rv
                JOIN vendedores vd ON rv.vendedor_id = vd.id
                WHERE MONTH(DATE(CONCAT(rv.anio, '-', rv.mes, '-01'))) = ?
                AND YEAR(DATE(CONCAT(rv.anio, '-', rv.mes, '-01'))) = ?
                AND vd.activo = 1";

$stmt_stats = $db->prepare($stats_query);
$stmt_stats->execute([$mes_actual, $ano_actual]);
$stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);

include_once 'includes/layout_header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4 rs-wrap-sm">
                <h2><i class="fas fa-trophy me-2 text-warning"></i>Ranking de Vendedores</h2>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary" onclick="actualizarRanking()">
                        <i class="fas fa-sync-alt me-1"></i>Actualizar Ranking
                    </button>
                    <button class="btn btn-outline-success" onclick="exportarRanking()">
                        <i class="fas fa-download me-1"></i>Exportar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="mes" class="form-label">Mes</label>
                    <select class="form-select" name="mes" id="mes">
                        <?php
                        $meses = [
                            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
                        ];
                        foreach ($meses as $num => $nombre) {
                            $selected = $num == $mes_actual ? 'selected' : '';
                            echo "<option value='{$num}' {$selected}>{$nombre}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="ano" class="form-label">Año</label>
                    <select class="form-select" name="ano" id="ano">
                        <?php
                        $ano_inicio = date('Y') - 2;
                        $ano_fin = date('Y') + 1;
                        for ($ano = $ano_fin; $ano >= $ano_inicio; $ano--) {
                            $selected = $ano == $ano_actual ? 'selected' : '';
                            echo "<option value='{$ano}' {$selected}>{$ano}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="tienda" class="form-label">Tienda</label>
                    <select class="form-select" name="tienda" id="tienda">
                        <option value="">Todas las tiendas</option>
                        <?php foreach ($tiendas as $tienda): ?>
                            <option value="<?= $tienda['id'] ?>" <?= $tienda['id'] == $tienda_filtro ? 'selected' : '' ?>>
                                <?= htmlspecialchars($tienda['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary d-block w-100">
                        <i class="fas fa-filter me-1"></i>Filtrar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Estadísticas globales -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-users fa-2x"></i>
                        </div>
                        <div>
                            <h5 class="card-title mb-0"><?= $stats['total_vendedores'] ?></h5>
                            <p class="card-text">Vendedores Activos</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-dollar-sign fa-2x"></i>
                        </div>
                        <div>
                            <h5 class="card-title mb-0">Q <?= number_format($stats['ventas_totales'], 0) ?></h5>
                            <p class="card-text">Ventas Totales</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-chart-line fa-2x"></i>
                        </div>
                        <div>
                            <h5 class="card-title mb-0">Q <?= number_format($stats['ticket_promedio_global'], 0) ?></h5>
                            <p class="card-text">Ticket Promedio</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-coins fa-2x"></i>
                        </div>
                        <div>
                            <h5 class="card-title mb-0">Q <?= number_format($stats['comisiones_totales'], 0) ?></h5>
                            <p class="card-text">Comisiones Totales</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Podio Top 3 -->
    <?php if (count($ranking) >= 3): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="fas fa-medal me-2"></i>Podio del Mes
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <!-- Segundo lugar -->
                        <div class="col-md-4 order-md-1">
                            <div class="podio-card h-100 d-flex flex-column justify-content-end">
                                <div class="podio-medal bg-secondary text-white">
                                    <i class="fas fa-medal fa-3x"></i>
                                    <div class="position-number">2°</div>
                                </div>
                                <h5 class="mt-3"><?= htmlspecialchars($ranking[1]['vendedor_nombre']) ?></h5>
                                <p class="text-muted mb-2"><?= $ranking[1]['ventas_validadas'] ?> ventas</p>
                                <h6 class="text-success">Q <?= number_format($ranking[1]['total_ventas'], 0) ?></h6>
                                <div class="podio-height-2"></div>
                            </div>
                        </div>
                        
                        <!-- Primer lugar -->
                        <div class="col-md-4 order-md-2">
                            <div class="podio-card h-100 d-flex flex-column justify-content-end">
                                <div class="podio-medal bg-warning text-dark">
                                    <i class="fas fa-crown fa-3x"></i>
                                    <div class="position-number">1°</div>
                                </div>
                                <h4 class="mt-3 fw-bold"><?= htmlspecialchars($ranking[0]['vendedor_nombre']) ?></h4>
                                <p class="text-muted mb-2"><?= $ranking[0]['ventas_validadas'] ?> ventas</p>
                                <h5 class="text-success">Q <?= number_format($ranking[0]['total_ventas'], 0) ?></h5>
                                <div class="podio-height-1"></div>
                            </div>
                        </div>
                        
                        <!-- Tercer lugar -->
                        <div class="col-md-4 order-md-3">
                            <div class="podio-card h-100 d-flex flex-column justify-content-end">
                                <div class="podio-medal bg-dark text-white">
                                    <i class="fas fa-medal fa-3x"></i>
                                    <div class="position-number">3°</div>
                                </div>
                                <h5 class="mt-3"><?= htmlspecialchars($ranking[2]['vendedor_nombre']) ?></h5>
                                <p class="text-muted mb-2"><?= $ranking[2]['ventas_validadas'] ?> ventas</p>
                                <h6 class="text-success">Q <?= number_format($ranking[2]['total_ventas'], 0) ?></h6>
                                <div class="podio-height-3"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Tabla completa de ranking -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-list-ol me-2"></i>Ranking Completo - <?= $meses[$mes_actual] ?> <?= $ano_actual ?>
            </h5>
        </div>
        <div class="card-body">
            <?php if (empty($ranking)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No hay datos de ranking para el período seleccionado</p>
                </div>
            <?php else: ?>
                <div class="table-responsive-md">
                    <table class="table table-hover">
                        <thead class="thead-titulos">
                            <tr>
                                <th>Posición</th>
                                <th>Vendedor</th>
                                <th>Ventas</th>
                                <th>Total Vendido</th>
                                <th>Ticket Promedio</th>
                                <th>Comisiones</th>
                                <th>Puntos</th>
                                <th>Performance</th>
                                <th>Tiendas</th>
                                <th>Contacto</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ranking as $index => $vendedor): ?>
                            <tr class="<?= $index < 3 ? 'table-success' : '' ?>">
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php if ($index == 0): ?>
                                            <i class="fas fa-crown text-warning me-2"></i>
                                        <?php elseif ($index == 1): ?>
                                            <i class="fas fa-medal text-secondary me-2"></i>
                                        <?php elseif ($index == 2): ?>
                                            <i class="fas fa-medal text-dark me-2"></i>
                                        <?php else: ?>
                                            <span class="me-3"><?= $index + 1 ?>°</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <strong><?= htmlspecialchars($vendedor['vendedor_nombre']) ?></strong>
                                        <br><small class="text-muted"><?= $vendedor['comision_porcentaje'] ?>% comisión</small>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-primary"><?= $vendedor['ventas_validadas'] ?></span>
                                    <br><small class="text-muted"><?= $vendedor['dias_activos'] ?> días activos</small>
                                </td>
                                <td>
                                    <strong class="text-success">Q <?= number_format($vendedor['total_ventas'], 0) ?></strong>
                                    <br><small class="text-muted">
                                        Min: Q <?= number_format($vendedor['venta_minima'], 0) ?> - 
                                        Max: Q <?= number_format($vendedor['venta_maxima'], 0) ?>
                                    </small>
                                </td>
                                <td>
                                    Q <?= number_format($vendedor['ticket_promedio'], 0) ?>
                                </td>
                                <td>
                                    <strong class="text-warning">Q <?= number_format($vendedor['total_comisiones'], 2) ?></strong>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="progress me-2" style="width: 60px; height: 20px;">
                                            <div class="progress-bar bg-info" 
                                                 style="width: <?= min(100, ($vendedor['puntos_ranking'] / $stats['max_puntos']) * 100) ?>%">
                                            </div>
                                        </div>
                                        <span class="fw-bold"><?= number_format($vendedor['puntos_ranking'], 0) ?></span>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($vendedor['ventas_mes_anterior']): ?>
                                        <?php 
                                        $cambio = (($vendedor['total_ventas'] - $vendedor['ventas_mes_anterior']) / $vendedor['ventas_mes_anterior']) * 100;
                                        $color = $cambio >= 0 ? 'success' : 'danger';
                                        $icono = $cambio >= 0 ? 'fa-arrow-up' : 'fa-arrow-down';
                                        ?>
                                        <span class="text-<?= $color ?>">
                                            <i class="fas <?= $icono ?>"></i>
                                            <?= abs(round($cambio, 1)) ?>%
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">Nuevo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small><?= htmlspecialchars($vendedor['tiendas_nombres'] ?: 'No asignado') ?></small>
                                    <br><span class="badge bg-secondary"><?= $vendedor['tiendas_activas'] ?> tienda<?= $vendedor['tiendas_activas'] != 1 ? 's' : '' ?></span>
                                </td>
                                <td>
                                    <small>
                                        <?= htmlspecialchars($vendedor['email'] ?: 'Sin email') ?>
                                        <?php if ($vendedor['telefono']): ?>
                                            <br><?= htmlspecialchars($vendedor['telefono']) ?>
                                        <?php endif; ?>
                                    </small>
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

<style>
.podio-card {
    padding: 20px;
    position: relative;
}

.podio-medal {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}

.position-number {
    position: absolute;
    bottom: -5px;
    right: -5px;
    background: rgba(0,0,0,0.7);
    color: white;
    border-radius: 50%;
    width: 25px;
    height: 25px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: bold;
}

.podio-height-1 {
    height: 60px;
    background: linear-gradient(to top, #ffc107, #fff3cd);
    margin-top: 20px;
}

.podio-height-2 {
    height: 40px;
    background: linear-gradient(to top, #6c757d, #e2e3e5);
    margin-top: 20px;
}

.podio-height-3 {
    height: 30px;
    background: linear-gradient(to top, #495057, #e2e3e5);
    margin-top: 20px;
}

.progress {
    border-radius: 10px;
}

.table-success {
    background-color: rgba(25, 135, 84, 0.1);
}
</style>

<script>
function actualizarRanking() {
    if (!confirm('¿Está seguro de actualizar el ranking? Este proceso puede tardar unos minutos.')) {
        return;
    }
    
    const btn = event.target.closest('button');
    const originalContent = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Actualizando...';
    
    fetch('ajax/actualizar_ranking_vendedores.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            mes: <?= $mes_actual ?>,
            ano: <?= $ano_actual ?>
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mostrarToast(data.message, 'success');
            setTimeout(() => {
                location.reload();
            }, 2000);
        } else {
            mostrarToast(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarToast('Error al actualizar ranking', 'error');
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = originalContent;
    });
}

function exportarRanking() {
    const params = new URLSearchParams({
        mes: <?= $mes_actual ?>,
        ano: <?= $ano_actual ?>,
        tienda: '<?= $tienda_filtro ?>'
    });
    
    window.open(`ajax/exportar_ranking.php?${params.toString()}`, '_blank');
}

// Función para mostrar toast
function mostrarToast(mensaje, tipo = 'info') {
    const toastHtml = `
        <div class="toast align-items-center text-bg-${tipo === 'error' ? 'danger' : (tipo === 'success' ? 'success' : 'primary')} border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-${tipo === 'error' ? 'exclamation-circle' : (tipo === 'success' ? 'check-circle' : 'info-circle')} me-2"></i>
                    ${mensaje}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;
    
    let toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        toastContainer.style.zIndex = '1060';
        document.body.appendChild(toastContainer);
    }
    
    toastContainer.insertAdjacentHTML('beforeend', toastHtml);
    const toastElement = toastContainer.lastElementChild;
    const toast = new bootstrap.Toast(toastElement, {
        autohide: true,
        delay: tipo === 'error' ? 5000 : 3000
    });
    toast.show();
    
    toastElement.addEventListener('hidden.bs.toast', function() {
        toastElement.remove();
    });
}
</script>

<?php include_once 'includes/layout_footer.php'; ?>
