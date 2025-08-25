<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

verificarLogin();
verificarPermiso('config_sistema'); // Contabilidad requiere permisos administrativos

$database = new Database();
$db = $database->getConnection();

// Parámetros de filtro
$fecha_filtro = $_GET['fecha'] ?? date('Y-m-d');
$tienda_filtro = $_GET['tienda'] ?? '';
$estado_filtro = $_GET['estado'] ?? '';

// Obtener tiendas para filtro
$tiendas_query = "SELECT id, nombre FROM tiendas WHERE activo = 1 ORDER BY nombre";
$tiendas = $db->query($tiendas_query)->fetchAll(PDO::FETCH_ASSOC);

// Construir query para reconciliaciones
$where_conditions = ["rb.fecha_reconciliacion = ?"];
$params = [$fecha_filtro];

if ($tienda_filtro) {
    $where_conditions[] = "rb.tienda_id = ?";
    $params[] = $tienda_filtro;
}

if ($estado_filtro) {
    $where_conditions[] = "rb.estado = ?";
    $params[] = $estado_filtro;
}

// Obtener reconciliaciones pendientes y en proceso
$reconciliaciones_query = "SELECT 
                              rb.*,
                              t.nombre as tienda_nombre,
                              rd.total_general as total_reportado_encargado,
                              rd.encargado_id,
                              u_encargado.nombre as encargado_nombre,
                              rd.gerente_id,
                              u_gerente.nombre as gerente_nombre,
                              u_contabilidad.nombre as contabilidad_nombre,
                              -- Calcular diferencias
                              (rb.total_boletas_fisicas - rb.total_sistema) as diferencia_vs_sistema,
                              (rb.total_boletas_fisicas - rd.total_general) as diferencia_vs_reportado,
                              (rd.total_general - rb.total_sistema) as diferencia_reportado_sistema,
                              -- Contar boletas físicas registradas
                              COALESCE(boletas_count.total_boletas, 0) as boletas_registradas
                           FROM reconciliacion_boletas rb
                           JOIN tiendas t ON rb.tienda_id = t.id
                           JOIN reportes_diarios_encargado rd ON rb.reporte_diario_id = rd.id
                           JOIN usuarios u_encargado ON rd.encargado_id = u_encargado.id
                           LEFT JOIN usuarios u_gerente ON rd.gerente_id = u_gerente.id
                           JOIN usuarios u_contabilidad ON rb.usuario_contabilidad_id = u_contabilidad.id
                           LEFT JOIN (
                               SELECT 
                                   reconciliacion_id,
                                   COUNT(*) as total_boletas,
                                   SUM(total_boleta) as suma_boletas
                               FROM detalle_boletas_fisicas 
                               GROUP BY reconciliacion_id
                           ) boletas_count ON rb.id = boletas_count.reconciliacion_id
                           WHERE " . implode(' AND ', $where_conditions) . "
                           ORDER BY 
                               CASE rb.estado 
                                   WHEN 'pendiente' THEN 1
                                   WHEN 'revisando' THEN 2
                                   ELSE 3
                               END,
                               rb.fecha_reconciliacion DESC";

$stmt_reconciliaciones = $db->prepare($reconciliaciones_query);
$stmt_reconciliaciones->execute($params);
$reconciliaciones = $stmt_reconciliaciones->fetchAll(PDO::FETCH_ASSOC);

// Estadísticas generales
$stats_query = "SELECT 
                   COUNT(*) as total_reconciliaciones,
                   COUNT(CASE WHEN rb.estado = 'pendiente' THEN 1 END) as pendientes,
                   COUNT(CASE WHEN rb.estado = 'revisando' THEN 1 END) as revisando,
                   COUNT(CASE WHEN rb.estado = 'aprobado' THEN 1 END) as aprobados,
                   COUNT(CASE WHEN rb.estado = 'con_diferencias' THEN 1 END) as con_diferencias,
                   COUNT(CASE WHEN rb.estado = 'rechazado' THEN 1 END) as rechazados,
                   SUM(rb.total_sistema) as total_sistema_global,
                   SUM(rb.total_boletas_fisicas) as total_boletas_global,
                   SUM(ABS(rb.total_boletas_fisicas - rb.total_sistema)) as diferencias_totales
                FROM reconciliacion_boletas rb
                WHERE rb.fecha_reconciliacion = ?" . ($tienda_filtro ? " AND rb.tienda_id = ?" : "");

$params_stats = [$fecha_filtro];
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
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-calculator me-2"></i>Contabilidad - Reconciliación de Boletas</h2>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary" onclick="generarReporteContabilidad()">
                        <i class="fas fa-file-excel me-1"></i>Reporte Excel
                    </button>
                    <button class="btn btn-outline-success" onclick="exportarReconciliaciones()">
                        <i class="fas fa-download me-1"></i>Exportar Datos
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
                    <label for="fecha" class="form-label">Fecha</label>
                    <input type="date" class="form-control" name="fecha" id="fecha" 
                           value="<?= $fecha_filtro ?>" max="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-md-3">
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
                <div class="col-md-4">
                    <label for="estado" class="form-label">Estado</label>
                    <select class="form-select" name="estado" id="estado">
                        <option value="">Todos los estados</option>
                        <option value="pendiente" <?= $estado_filtro === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                        <option value="revisando" <?= $estado_filtro === 'revisando' ? 'selected' : '' ?>>Revisando</option>
                        <option value="aprobado" <?= $estado_filtro === 'aprobado' ? 'selected' : '' ?>>Aprobado</option>
                        <option value="con_diferencias" <?= $estado_filtro === 'con_diferencias' ? 'selected' : '' ?>>Con Diferencias</option>
                        <option value="rechazado" <?= $estado_filtro === 'rechazado' ? 'selected' : '' ?>>Rechazado</option>
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
                            <i class="fas fa-receipt fa-2x"></i>
                        </div>
                        <div>
                            <h5 class="card-title mb-0"><?= $stats['total_reconciliaciones'] ?></h5>
                            <p class="card-text">Total</p>
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
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-search fa-2x"></i>
                        </div>
                        <div>
                            <h5 class="card-title mb-0"><?= $stats['revisando'] ?></h5>
                            <p class="card-text">Revisando</p>
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
            <div class="card bg-secondary text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-exclamation-triangle fa-2x"></i>
                        </div>
                        <div>
                            <h5 class="card-title mb-0"><?= $stats['con_diferencias'] ?></h5>
                            <p class="card-text">Diferencias</p>
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
                            <i class="fas fa-times-circle fa-2x"></i>
                        </div>
                        <div>
                            <h5 class="card-title mb-0">Q <?= number_format($stats['diferencias_totales'], 0) ?></h5>
                            <p class="card-text">Dif. Total</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de reconciliaciones -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-balance-scale me-2"></i>Reconciliaciones - <?= date('d/m/Y', strtotime($fecha_filtro)) ?>
                <span class="badge bg-primary ms-2"><?= count($reconciliaciones) ?> reconciliación<?= count($reconciliaciones) != 1 ? 'es' : '' ?></span>
            </h5>
        </div>
        <div class="card-body">
            <?php if (empty($reconciliaciones)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No hay reconciliaciones para los filtros seleccionados</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="thead-titulos">
                            <tr>
                                <th>Tienda</th>
                                <th>Flujo Completo</th>
                                <th>Totales</th>
                                <th>Diferencias</th>
                                <th>Boletas</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reconciliaciones as $reconciliacion): ?>
                            <tr class="<?= abs($reconciliacion['diferencia_vs_sistema']) > 100 ? 'table-warning' : '' ?>">
                                <td>
                                    <strong><?= htmlspecialchars($reconciliacion['tienda_nombre']) ?></strong>
                                    <br><small class="text-muted">
                                        <i class="fas fa-user me-1"></i><?= htmlspecialchars($reconciliacion['encargado_nombre']) ?>
                                    </small>
                                </td>
                                <td>
                                    <small>
                                        <div class="mb-1">
                                            <span class="badge bg-primary">Encargado</span>
                                            Q <?= number_format($reconciliacion['total_reportado_encargado'], 2) ?>
                                        </div>
                                        <div class="mb-1">
                                            <span class="badge bg-info">Sistema</span>
                                            Q <?= number_format($reconciliacion['total_sistema'], 2) ?>
                                        </div>
                                        <div>
                                            <span class="badge bg-success">Boletas</span>
                                            Q <?= number_format($reconciliacion['total_boletas_fisicas'], 2) ?>
                                        </div>
                                    </small>
                                </td>
                                <td>
                                    <div class="mb-1">
                                        <small>Sistema: <strong class="text-info">Q <?= number_format($reconciliacion['total_sistema'], 2) ?></strong></small>
                                    </div>
                                    <div class="mb-1">
                                        <small>Boletas: <strong class="text-success">Q <?= number_format($reconciliacion['total_boletas_fisicas'], 2) ?></strong></small>
                                    </div>
                                    <div>
                                        <small><?= $reconciliacion['ventas_sistema'] ?> ventas sistema</small>
                                    </div>
                                </td>
                                <td>
                                    <?php
                                    $dif_sistema = $reconciliacion['diferencia_vs_sistema'];
                                    $dif_reportado = $reconciliacion['diferencia_vs_reportado'];
                                    $color_sistema = abs($dif_sistema) <= 10 ? 'success' : (abs($dif_sistema) <= 50 ? 'warning' : 'danger');
                                    $color_reportado = abs($dif_reportado) <= 10 ? 'success' : (abs($dif_reportado) <= 50 ? 'warning' : 'danger');
                                    ?>
                                    <div class="mb-1">
                                        <small>vs Sistema:</small>
                                        <span class="badge bg-<?= $color_sistema ?>">
                                            <?= $dif_sistema >= 0 ? '+' : '' ?>Q <?= number_format($dif_sistema, 2) ?>
                                        </span>
                                    </div>
                                    <div>
                                        <small>vs Reportado:</small>
                                        <span class="badge bg-<?= $color_reportado ?>">
                                            <?= $dif_reportado >= 0 ? '+' : '' ?>Q <?= number_format($dif_reportado, 2) ?>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <div class="mb-1">
                                        <strong><?= $reconciliacion['boletas_registradas'] ?></strong> boletas
                                    </div>
                                    <div>
                                        <small><?= $reconciliacion['cantidad_boletas_fisicas'] ?> declaradas</small>
                                    </div>
                                    <?php if ($reconciliacion['boletas_registradas'] < $reconciliacion['cantidad_boletas_fisicas']): ?>
                                        <small class="text-danger">
                                            <i class="fas fa-exclamation-triangle"></i> Faltan boletas
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $estado_badges = [
                                        'pendiente' => 'bg-warning',
                                        'revisando' => 'bg-info',
                                        'aprobado' => 'bg-success',
                                        'con_diferencias' => 'bg-secondary',
                                        'rechazado' => 'bg-danger'
                                    ];
                                    $estado_textos = [
                                        'pendiente' => 'Pendiente',
                                        'revisando' => 'Revisando',
                                        'aprobado' => 'Aprobado',
                                        'con_diferencias' => 'Con Diferencias',
                                        'rechazado' => 'Rechazado'
                                    ];
                                    ?>
                                    <span class="badge <?= $estado_badges[$reconciliacion['estado']] ?>">
                                        <?= $estado_textos[$reconciliacion['estado']] ?>
                                    </span>
                                    <?php if ($reconciliacion['fecha_revision']): ?>
                                        <br><small class="text-muted">
                                            <?= date('d/m H:i', strtotime($reconciliacion['fecha_revision'])) ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group-vertical btn-group-sm">
                                        <button class="btn btn-outline-info" onclick="verReconciliacion(<?= $reconciliacion['id'] ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php if (in_array($reconciliacion['estado'], ['pendiente', 'revisando'])): ?>
                                        <button class="btn btn-outline-primary" onclick="gestionarBoletas(<?= $reconciliacion['id'] ?>)">
                                            <i class="fas fa-receipt"></i>
                                        </button>
                                        <button class="btn btn-outline-success" onclick="aprobarReconciliacion(<?= $reconciliacion['id'] ?>)">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button class="btn btn-outline-danger" onclick="rechazarReconciliacion(<?= $reconciliacion['id'] ?>)">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        <?php endif; ?>
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

<!-- Modal para detalle de reconciliación -->
<div class="modal fade" id="modalReconciliacion" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalle de Reconciliación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalReconciliacionContent">
                <!-- Contenido cargado via AJAX -->
            </div>
        </div>
    </div>
</div>

<!-- Modal para gestionar boletas físicas -->
<div class="modal fade" id="modalBoletas" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Gestionar Boletas Físicas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalBoletasContent">
                <!-- Contenido cargado via AJAX -->
            </div>
        </div>
    </div>
</div>

<script>
let reconciliacionActualId = null;

function verReconciliacion(reconciliacionId) {
    reconciliacionActualId = reconciliacionId;
    
    fetch('ajax/get_reconciliacion_detalle.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            reconciliacion_id: reconciliacionId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('modalReconciliacionContent').innerHTML = data.html;
            new bootstrap.Modal(document.getElementById('modalReconciliacion')).show();
        } else {
            mostrarToast(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarToast('Error al cargar el detalle', 'error');
    });
}

function gestionarBoletas(reconciliacionId) {
    reconciliacionActualId = reconciliacionId;
    
    fetch('ajax/gestionar_boletas_fisicas.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            reconciliacion_id: reconciliacionId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('modalBoletasContent').innerHTML = data.html;
            new bootstrap.Modal(document.getElementById('modalBoletas')).show();
        } else {
            mostrarToast(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarToast('Error al cargar boletas', 'error');
    });
}

function aprobarReconciliacion(reconciliacionId) {
    if (!confirm('¿Está seguro de aprobar esta reconciliación?')) return;
    
    procesarReconciliacion(reconciliacionId, 'aprobado');
}

function rechazarReconciliacion(reconciliacionId) {
    const motivo = prompt('Ingrese el motivo del rechazo:');
    if (!motivo) return;
    
    procesarReconciliacion(reconciliacionId, 'rechazado', motivo);
}

function procesarReconciliacion(reconciliacionId, estado, observaciones = null) {
    fetch('ajax/procesar_reconciliacion.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            reconciliacion_id: reconciliacionId,
            estado: estado,
            observaciones: observaciones
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
        mostrarToast('Error al procesar la reconciliación', 'error');
    });
}

function generarReporteContabilidad() {
    const params = new URLSearchParams({
        fecha: '<?= $fecha_filtro ?>',
        tienda: '<?= $tienda_filtro ?>',
        estado: '<?= $estado_filtro ?>'
    });
    
    window.open(`ajax/generar_reporte_contabilidad.php?${params.toString()}`, '_blank');
}

function exportarReconciliaciones() {
    const params = new URLSearchParams({
        fecha: '<?= $fecha_filtro ?>',
        tienda: '<?= $tienda_filtro ?>',
        estado: '<?= $estado_filtro ?>'
    });
    
    window.open(`ajax/exportar_reconciliaciones.php?${params.toString()}`, '_blank');
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
