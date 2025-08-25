<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

verificarLogin();
verificarPermiso('ventas_ver');

$database = new Database();
$db = $database->getConnection();

// Parámetros de filtro
$fecha_filtro = $_GET['fecha'] ?? date('Y-m-d');
$tienda_filtro = $_GET['tienda'] ?? '';

// Obtener tiendas para filtro
$tiendas_query = "SELECT id, nombre FROM tiendas WHERE activo = 1 ORDER BY nombre";
$tiendas = $db->query($tiendas_query)->fetchAll(PDO::FETCH_ASSOC);

// Construir query para reportes de encargados
$where_conditions = ["rd.fecha_reporte = ?"];
$params = [$fecha_filtro];

if ($tienda_filtro) {
    $where_conditions[] = "rd.tienda_id = ?";
    $params[] = $tienda_filtro;
}

// Obtener reportes diarios de encargados
$reportes_query = "SELECT 
                      rd.*,
                      t.nombre as tienda_nombre,
                      u_encargado.nombre as encargado_nombre,
                      u_gerente.nombre as gerente_nombre,
                      -- Comparar con ventas del sistema
                      COALESCE(vs.total_sistema, 0) as total_sistema,
                      COALESCE(vs.ventas_cantidad, 0) as ventas_cantidad,
                      COALESCE(vs.total_efectivo_sistema, 0) as total_efectivo_sistema,
                      COALESCE(vs.total_tarjeta_sistema, 0) as total_tarjeta_sistema,
                      -- Calcular diferencias
                      (rd.total_general - COALESCE(vs.total_sistema, 0)) as diferencia_total,
                      ABS((rd.total_general - COALESCE(vs.total_sistema, 0))) as diferencia_absoluta
                   FROM reportes_diarios_encargado rd
                   JOIN tiendas t ON rd.tienda_id = t.id
                   JOIN usuarios u_encargado ON rd.encargado_id = u_encargado.id
                   LEFT JOIN usuarios u_gerente ON rd.gerente_id = u_gerente.id
                   LEFT JOIN (
                       SELECT 
                           v.tienda_id,
                           DATE(v.fecha) as fecha_venta,
                           SUM(v.total) as total_sistema,
                           COUNT(v.id) as ventas_cantidad,
                           SUM(CASE WHEN v.metodo_pago = 'efectivo' THEN v.total ELSE 0 END) as total_efectivo_sistema,
                           SUM(CASE WHEN v.metodo_pago = 'tarjeta' THEN v.total ELSE 0 END) as total_tarjeta_sistema
                       FROM ventas v
                       WHERE DATE(v.fecha) = ? AND v.estado = 'completada'
                       GROUP BY v.tienda_id, DATE(v.fecha)
                   ) vs ON rd.tienda_id = vs.tienda_id AND rd.fecha_reporte = vs.fecha_venta
                   WHERE " . implode(' AND ', $where_conditions) . "
                   ORDER BY t.nombre, rd.fecha_reporte DESC";

$stmt_reportes = $db->prepare($reportes_query);
$stmt_reportes->execute(array_merge([$fecha_filtro], $params));
$reportes = $stmt_reportes->fetchAll(PDO::FETCH_ASSOC);

// Estadísticas generales
$stats_query = "SELECT 
                   COUNT(DISTINCT rd.tienda_id) as tiendas_reportadas,
                   COUNT(*) as reportes_totales,
                   COUNT(CASE WHEN rd.estado = 'pendiente' THEN 1 END) as pendientes,
                   COUNT(CASE WHEN rd.estado = 'aprobado_gerente' THEN 1 END) as aprobados,
                   COUNT(CASE WHEN rd.estado = 'rechazado_gerente' THEN 1 END) as rechazados,
                   SUM(rd.total_general) as total_reportado,
                   SUM(COALESCE(vs.total_sistema, 0)) as total_sistema,
                   SUM(ABS(rd.total_general - COALESCE(vs.total_sistema, 0))) as diferencias_totales
                FROM reportes_diarios_encargado rd
                LEFT JOIN (
                    SELECT 
                        v.tienda_id,
                        DATE(v.fecha) as fecha_venta,
                        SUM(v.total) as total_sistema
                    FROM ventas v
                    WHERE DATE(v.fecha) = ? AND v.estado = 'completada'
                    GROUP BY v.tienda_id, DATE(v.fecha)
                ) vs ON rd.tienda_id = vs.tienda_id AND rd.fecha_reporte = vs.fecha_venta
                WHERE rd.fecha_reporte = ?" . ($tienda_filtro ? " AND rd.tienda_id = ?" : "");

$params_stats = [$fecha_filtro, $fecha_filtro];
if ($tienda_filtro) {
    $params_stats[] = $tienda_filtro;
}

$stmt_stats = $db->prepare($stats_query);
$stmt_stats->execute($params_stats);
$stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);

include_once 'includes/layout_header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4 rs-wrap-sm">
                <h2><i class="fas fa-building me-2"></i>Dashboard Gerencial - Control Diario</h2>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary" onclick="generarReporteGerencial()">
                        <i class="fas fa-file-pdf me-1"></i>Generar Reporte
                    </button>
                    <button class="btn btn-outline-success" onclick="exportarDatos()">
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
                <div class="col-md-4">
                    <label for="fecha" class="form-label">Fecha</label>
                    <input type="date" class="form-control" name="fecha" id="fecha" 
                           value="<?= $fecha_filtro ?>" max="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-md-6">
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

    <!-- Estadísticas del día -->
    <div class="row mb-4">
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-store fa-2x"></i>
                        </div>
                        <div>
                            <h5 class="card-title mb-0"><?= $stats['tiendas_reportadas'] ?></h5>
                            <p class="card-text">Tiendas</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-clock fa-2x"></i>
                        </div>
                        <div>
                            <h5 class="card-title mb-0"><?= $stats['pendientes'] ?></h5>
                            <p class="card-text">Pendientes</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                        <div>
                            <h5 class="card-title mb-0"><?= $stats['aprobados'] ?></h5>
                            <p class="card-text">Aprobados</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-dollar-sign fa-2x"></i>
                        </div>
                        <div>
                            <h5 class="card-title mb-0">Q <?= number_format($stats['total_reportado'], 0) ?></h5>
                            <p class="card-text">Total Reportado</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="card bg-secondary text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-desktop fa-2x"></i>
                        </div>
                        <div>
                            <h5 class="card-title mb-0">Q <?= number_format($stats['total_sistema'], 0) ?></h5>
                            <p class="card-text">Total Sistema</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-exclamation-triangle fa-2x"></i>
                        </div>
                        <div>
                            <h5 class="card-title mb-0">Q <?= number_format($stats['diferencias_totales'], 0) ?></h5>
                            <p class="card-text">Diferencias</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de reportes por tienda -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-chart-bar me-2"></i>Reportes Diarios - <?= date('d/m/Y', strtotime($fecha_filtro)) ?>
                <span class="badge bg-primary ms-2"><?= count($reportes) ?> reporte<?= count($reportes) != 1 ? 's' : '' ?></span>
            </h5>
        </div>
        <div class="card-body">
            <?php if (empty($reportes)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No hay reportes para la fecha seleccionada</p>
                </div>
            <?php else: ?>
                <div class="table-responsive-md">
                    <table class="table table-hover">
                        <thead class="thead-titulos">
                            <tr>
                                <th>Tienda</th>
                                <th>Encargado</th>
                                <th>Total Reportado</th>
                                <th>Total Sistema</th>
                                <th>Diferencia</th>
                                <th>Desglose Reportado</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reportes as $reporte): ?>
                            <tr class="<?= abs($reporte['diferencia_absoluta']) > 50 ? 'table-warning' : '' ?>">
                                <td>
                                    <strong><?= htmlspecialchars($reporte['tienda_nombre']) ?></strong>
                                    <br><small class="text-muted">ID: <?= $reporte['tienda_id'] ?></small>
                                </td>
                                <td>
                                    <?= htmlspecialchars($reporte['encargado_nombre']) ?>
                                    <br><small class="text-muted">
                                        Reportado: <?= date('H:i', strtotime($reporte['fecha_creacion'])) ?>
                                    </small>
                                </td>
                                <td>
                                    <strong class="text-primary">Q <?= number_format($reporte['total_general'], 2) ?></strong>
                                </td>
                                <td>
                                    <strong class="text-info">Q <?= number_format($reporte['total_sistema'], 2) ?></strong>
                                    <br><small class="text-muted"><?= $reporte['ventas_cantidad'] ?> venta<?= $reporte['ventas_cantidad'] != 1 ? 's' : '' ?></small>
                                </td>
                                <td>
                                    <?php
                                    $diferencia = $reporte['diferencia_total'];
                                    $color = abs($diferencia) <= 10 ? 'success' : (abs($diferencia) <= 50 ? 'warning' : 'danger');
                                    $icon = $diferencia >= 0 ? 'fa-arrow-up' : 'fa-arrow-down';
                                    ?>
                                    <span class="text-<?= $color ?>">
                                        <i class="fas <?= $icon ?>"></i>
                                        Q <?= number_format(abs($diferencia), 2) ?>
                                    </span>
                                    <?php if (abs($diferencia) > 50): ?>
                                        <br><span class="badge bg-warning">Revisar</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small>
                                        <i class="fas fa-money-bill text-success"></i> Q <?= number_format($reporte['total_efectivo'], 0) ?>
                                        <br><i class="fas fa-credit-card text-primary"></i> Q <?= number_format($reporte['total_tarjeta'], 0) ?>
                                        <?php if ($reporte['total_transferencia'] > 0): ?>
                                        <br><i class="fas fa-exchange-alt text-info"></i> Q <?= number_format($reporte['total_transferencia'], 0) ?>
                                        <?php endif; ?>
                                        <?php if ($reporte['total_otros'] > 0): ?>
                                        <br><i class="fas fa-ellipsis-h text-secondary"></i> Q <?= number_format($reporte['total_otros'], 0) ?>
                                        <?php endif; ?>
                                    </small>
                                </td>
                                <td>
                                    <?php
                                    $estado_badge = [
                                        'pendiente' => 'bg-warning',
                                        'aprobado_gerente' => 'bg-success',
                                        'rechazado_gerente' => 'bg-danger',
                                        'aprobado_contabilidad' => 'bg-info',
                                        'rechazado_contabilidad' => 'bg-dark'
                                    ];
                                    $estado_texto = [
                                        'pendiente' => 'Pendiente',
                                        'aprobado_gerente' => 'Aprobado',
                                        'rechazado_gerente' => 'Rechazado',
                                        'aprobado_contabilidad' => 'En Contabilidad',
                                        'rechazado_contabilidad' => 'Rechazado Contabilidad'
                                    ];
                                    ?>
                                    <span class="badge <?= $estado_badge[$reporte['estado']] ?>">
                                        <?= $estado_texto[$reporte['estado']] ?>
                                    </span>
                                    <?php if ($reporte['estado'] == 'aprobado_gerente' && $reporte['gerente_nombre']): ?>
                                        <br><small class="text-muted">Por: <?= htmlspecialchars($reporte['gerente_nombre']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group-vertical btn-group-sm">
                                        <button class="btn btn-outline-info" onclick="verDetalle(<?= $reporte['id'] ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php if ($reporte['estado'] == 'pendiente'): ?>
                                        <button class="btn btn-outline-success" onclick="aprobarReporte(<?= $reporte['id'] ?>)">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button class="btn btn-outline-danger" onclick="rechazarReporte(<?= $reporte['id'] ?>)">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        <?php endif; ?>
                                        <button class="btn btn-outline-primary" onclick="verComparacion(<?= $reporte['id'] ?>)">
                                            <i class="fas fa-balance-scale"></i>
                                        </button>
                                    </div>
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

<!-- Modal para detalle del reporte -->
<div class="modal fade" id="modalDetalle" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalle del Reporte Diario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalDetalleContent">
                <!-- Contenido cargado via AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success" id="btnAprobarModal" onclick="aprobarDesdeModal()">
                    <i class="fas fa-check me-1"></i>Aprobar
                </button>
                <button type="button" class="btn btn-danger" id="btnRechazarModal" onclick="rechazarDesdeModal()">
                    <i class="fas fa-times me-1"></i>Rechazar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para comparación detallada -->
<div class="modal fade" id="modalComparacion" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Comparación Reportado vs Sistema</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalComparacionContent">
                <!-- Contenido cargado via AJAX -->
            </div>
        </div>
    </div>
</div>

<!-- Modal para rechazar -->
<div class="modal fade" id="modalRechazar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Rechazar Reporte</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="motivoRechazo" class="form-label">Motivo del rechazo</label>
                    <select class="form-select" id="motivoRechazo" required>
                        <option value="">Seleccionar motivo...</option>
                        <option value="diferencia_significativa">Diferencia significativa con sistema</option>
                        <option value="datos_incompletos">Datos incompletos</option>
                        <option value="error_calculo">Error en cálculos</option>
                        <option value="falta_documentacion">Falta documentación</option>
                        <option value="inconsistencia_metodos_pago">Inconsistencia en métodos de pago</option>
                        <option value="otro">Otro motivo</option>
                    </select>
                </div>
                <div class="mb-3" id="otroMotivoDiv" style="display: none;">
                    <label for="otroMotivo" class="form-label">Especificar otro motivo</label>
                    <textarea class="form-control" id="otroMotivo" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" onclick="confirmarRechazo()">
                    <i class="fas fa-times me-1"></i>Rechazar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let reporteActualId = null;

function verDetalle(reporteId) {
    reporteActualId = reporteId;
    
    fetch('ajax/get_reporte_gerencial_detalle.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            reporte_id: reporteId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('modalDetalleContent').innerHTML = data.html;
            new bootstrap.Modal(document.getElementById('modalDetalle')).show();
        } else {
            mostrarToast(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarToast('Error al cargar el detalle', 'error');
    });
}

function verComparacion(reporteId) {
    fetch('ajax/get_comparacion_detallada.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            reporte_id: reporteId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('modalComparacionContent').innerHTML = data.html;
            new bootstrap.Modal(document.getElementById('modalComparacion')).show();
        } else {
            mostrarToast(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarToast('Error al cargar la comparación', 'error');
    });
}

function aprobarReporte(reporteId) {
    if (!confirm('¿Está seguro de aprobar este reporte?')) return;
    
    procesarAprobacion(reporteId, 'aprobado_gerente');
}

function rechazarReporte(reporteId) {
    reporteActualId = reporteId;
    new bootstrap.Modal(document.getElementById('modalRechazar')).show();
}

function aprobarDesdeModal() {
    if (reporteActualId) {
        procesarAprobacion(reporteActualId, 'aprobado_gerente');
        bootstrap.Modal.getInstance(document.getElementById('modalDetalle')).hide();
    }
}

function rechazarDesdeModal() {
    if (reporteActualId) {
        bootstrap.Modal.getInstance(document.getElementById('modalDetalle')).hide();
        setTimeout(() => {
            new bootstrap.Modal(document.getElementById('modalRechazar')).show();
        }, 500);
    }
}

function confirmarRechazo() {
    const motivo = document.getElementById('motivoRechazo').value;
    const otroMotivo = document.getElementById('otroMotivo').value;
    
    if (!motivo) {
        mostrarToast('Debe seleccionar un motivo de rechazo', 'error');
        return;
    }
    
    if (motivo === 'otro' && !otroMotivo.trim()) {
        mostrarToast('Debe especificar el otro motivo', 'error');
        return;
    }
    
    const motivoFinal = motivo === 'otro' ? otroMotivo : motivo;
    
    procesarAprobacion(reporteActualId, 'rechazado_gerente', motivoFinal);
    bootstrap.Modal.getInstance(document.getElementById('modalRechazar')).hide();
}

function procesarAprobacion(reporteId, estado, motivo = null) {
    fetch('ajax/procesar_aprobacion_gerencial.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            reporte_id: reporteId,
            estado: estado,
            observaciones: motivo
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mostrarToast(data.message, 'success');
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            mostrarToast(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarToast('Error al procesar la acción', 'error');
    });
}

// Mostrar/ocultar campo "otro motivo"
document.getElementById('motivoRechazo').addEventListener('change', function() {
    const otroMotivoDiv = document.getElementById('otroMotivoDiv');
    if (this.value === 'otro') {
        otroMotivoDiv.style.display = 'block';
    } else {
        otroMotivoDiv.style.display = 'none';
    }
});

function generarReporteGerencial() {
    const params = new URLSearchParams({
        fecha: '<?= $fecha_filtro ?>',
        tienda: '<?= $tienda_filtro ?>'
    });
    
    window.open(`ajax/generar_reporte_gerencial.php?${params.toString()}`, '_blank');
}

function exportarDatos() {
    const params = new URLSearchParams({
        fecha: '<?= $fecha_filtro ?>',
        tienda: '<?= $tienda_filtro ?>'
    });
    
    window.open(`ajax/exportar_datos_gerenciales.php?${params.toString()}`, '_blank');
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
