<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

verificarLogin();
verificarPermiso('ventas_ver');

$database = new Database();
$db = $database->getConnection();

// Estadísticas básicas
$stats_query = "SELECT 
                   COUNT(*) as total_reportes,
                   COUNT(CASE WHEN estado = 'pendiente' THEN 1 END) as pendientes,
                   COUNT(CASE WHEN estado = 'aprobado' THEN 1 END) as aprobados,
                   COUNT(CASE WHEN estado = 'rechazado' THEN 1 END) as rechazados
                FROM ventas_reportadas_vendedor 
                WHERE DATE(fecha_reporte) = CURDATE()";
$stats = $db->query($stats_query)->fetch(PDO::FETCH_ASSOC);

// Obtener reportes pendientes con información de matches
$reportes_query = "SELECT 
                      vrv.id,
                      vrv.venta_sistema_id,
                      vrv.vendedor_id,
                      vrv.fecha_venta,
                      vrv.total_reportado,
                      vrv.descripcion,
                      vrv.observaciones_verificacion,
                      vrv.fecha_reporte,
                      vrv.estado,
                      vrv.diferencia,
                      v.nombre as vendedor_nombre,
                      vt.id as venta_sistema_id,
                      vt.total as total_sistema,
                      vt.fecha as fecha_sistema,
                      t.nombre as tienda_nombre
                   FROM ventas_reportadas_vendedor vrv
                   JOIN vendedores v ON vrv.vendedor_id = v.id
                   LEFT JOIN ventas vt ON vrv.venta_sistema_id = vt.id
                   LEFT JOIN tiendas t ON vt.tienda_id = t.id
                   WHERE vrv.estado = 'pendiente'
                   ORDER BY vrv.fecha_reporte DESC
                   LIMIT 20";
$reportes_pendientes = $db->query($reportes_query)->fetchAll(PDO::FETCH_ASSOC);

include_once 'includes/layout_header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-clipboard-check me-2"></i>Aprobación de Ventas de Vendedores</h2>
                <div class="btn-group">
                    <button class="btn btn-outline-primary" onclick="cargarReportes('pendiente')">
                        <i class="fas fa-clock me-1"></i>Pendientes
                    </button>
                    <button class="btn btn-outline-success" onclick="cargarReportes('aprobado')">
                        <i class="fas fa-check me-1"></i>Aprobados
                    </button>
                    <button class="btn btn-outline-danger" onclick="cargarReportes('rechazado')">
                        <i class="fas fa-times me-1"></i>Rechazados
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Estadísticas del día -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-file-alt fa-2x"></i>
                        </div>
                        <div>
                            <h5 class="card-title mb-0"><?= $stats['total_reportes'] ?></h5>
                            <p class="card-text">Total Reportes</p>
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
        <div class="col-md-3">
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
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-times-circle fa-2x"></i>
                        </div>
                        <div>
                            <h5 class="card-title mb-0"><?= $stats['rechazados'] ?></h5>
                            <p class="card-text">Rechazados</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de reportes -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-list me-2"></i>Reportes Pendientes de Aprobación
                <span class="badge bg-warning ms-2"><?= count($reportes_pendientes) ?></span>
            </h5>
        </div>
        <div class="card-body">
            <?php if (empty($reportes_pendientes)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No hay reportes pendientes de aprobación</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Fecha Reporte</th>
                                <th>Vendedor</th>
                                <th>Fecha Venta</th>
                                <th>Total Reportado</th>
                                <th>Match Sistema</th>
                                <th>Confianza</th>
                                <th>Descripción</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reportes_pendientes as $reporte): ?>
                                <tr>
                                    <td>
                                        <small class="text-muted">
                                            <?= date('d/m/Y H:i', strtotime($reporte['fecha_reporte'])) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($reporte['vendedor_nombre']) ?></strong>
                                    </td>
                                    <td>
                                        <?= date('d/m/Y', strtotime($reporte['fecha_venta'])) ?>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-primary">Q <?= number_format($reporte['total_reportado'], 2) ?></span>
                                    </td>
                                    <td>
                                        <?php if ($reporte['venta_sistema_id']): ?>
                                            <div class="d-flex flex-column">
                                                <span class="badge bg-success mb-1">Match Encontrado</span>
                                                <small>Venta #<?= $reporte['venta_sistema_id'] ?></small>
                                                <small>Q <?= number_format($reporte['total_sistema'], 2) ?></small>
                                            </div>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Sin Match</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($reporte['diferencia'] !== null): ?>
                                            <?php 
                                            $diferencia = abs($reporte['diferencia']);
                                            $color = $diferencia <= 1 ? 'success' : ($diferencia <= 10 ? 'warning' : 'danger');
                                            ?>
                                            <span class="badge bg-<?= $color ?>">
                                                Q <?= number_format($diferencia, 2) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">Sin match</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?= htmlspecialchars(substr($reporte['descripcion'], 0, 50)) ?>
                                            <?= strlen($reporte['descripcion']) > 50 ? '...' : '' ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-info" onclick="verDetalle(<?= $reporte['id'] ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-outline-success" onclick="aprobarReporte(<?= $reporte['id'] ?>)">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button class="btn btn-outline-danger" onclick="rechazarReporte(<?= $reporte['id'] ?>)">
                                                <i class="fas fa-times"></i>
                                            </button>
                                            <?php if (!$reporte['venta_sistema_id']): ?>
                                            <button class="btn btn-outline-warning" onclick="buscarMatch(<?= $reporte['id'] ?>)">
                                                <i class="fas fa-search"></i>
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

<!-- Modal para detalle del reporte -->
<div class="modal fade" id="modalDetalle" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalle del Reporte de Venta</h5>
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

<!-- Modal para búsqueda manual de match -->
<div class="modal fade" id="modalBuscarMatch" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Buscar Match Manual</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalBuscarMatchContent">
                <!-- Contenido cargado via AJAX -->
            </div>
        </div>
    </div>
</div>

<!-- Modal para rechazar con motivo -->
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
                        <option value="datos_incorrectos">Datos incorrectos</option>
                        <option value="venta_no_encontrada">Venta no encontrada en sistema</option>
                        <option value="montos_no_coinciden">Montos no coinciden</option>
                        <option value="fecha_incorrecta">Fecha incorrecta</option>
                        <option value="vendedor_no_autorizado">Vendedor no autorizado</option>
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

// Ver detalle del reporte
function verDetalle(reporteId) {
    reporteActualId = reporteId;
    
    fetch('ajax/get_reporte_detalle.php', {
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

// Aprobar reporte
function aprobarReporte(reporteId) {
    if (!confirm('¿Está seguro de aprobar este reporte?')) return;
    
    procesarAprobacion(reporteId, 'aprobado');
}

// Rechazar reporte
function rechazarReporte(reporteId) {
    reporteActualId = reporteId;
    new bootstrap.Modal(document.getElementById('modalRechazar')).show();
}

// Buscar match manual
function buscarMatch(reporteId) {
    reporteActualId = reporteId;
    
    fetch('ajax/buscar_match_manual.php', {
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
            document.getElementById('modalBuscarMatchContent').innerHTML = data.html;
            new bootstrap.Modal(document.getElementById('modalBuscarMatch')).show();
        } else {
            mostrarToast(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarToast('Error al buscar matches', 'error');
    });
}

// Aprobar desde modal
function aprobarDesdeModal() {
    if (reporteActualId) {
        procesarAprobacion(reporteActualId, 'aprobado');
        bootstrap.Modal.getInstance(document.getElementById('modalDetalle')).hide();
    }
}

// Rechazar desde modal
function rechazarDesdeModal() {
    if (reporteActualId) {
        bootstrap.Modal.getInstance(document.getElementById('modalDetalle')).hide();
        setTimeout(() => {
            new bootstrap.Modal(document.getElementById('modalRechazar')).show();
        }, 500);
    }
}

// Confirmar rechazo con motivo
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
    
    procesarAprobacion(reporteActualId, 'rechazado', motivoFinal);
    bootstrap.Modal.getInstance(document.getElementById('modalRechazar')).hide();
}

// Procesar aprobación/rechazo
function procesarAprobacion(reporteId, estado, motivo = null) {
    fetch('ajax/procesar_aprobacion_venta.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            reporte_id: reporteId,
            estado: estado,
            motivo_rechazo: motivo
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

// Función para mostrar toast
function mostrarToast(mensaje, tipo = 'info') {
    // Crear elemento toast
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
    
    // Agregar al contenedor de toasts
    let toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        toastContainer.style.zIndex = '1060';
        document.body.appendChild(toastContainer);
    }
    
    toastContainer.insertAdjacentHTML('beforeend', toastHtml);
    
    // Mostrar toast
    const toastElement = toastContainer.lastElementChild;
    const toast = new bootstrap.Toast(toastElement, {
        autohide: true,
        delay: tipo === 'error' ? 5000 : 3000
    });
    toast.show();
    
    // Limpiar después de ocultar
    toastElement.addEventListener('hidden.bs.toast', function() {
        toastElement.remove();
    });
}

// Cargar reportes por estado
function cargarReportes(estado) {
    window.location.href = `?estado=${estado}`;
}
</script>

<?php include_once 'includes/layout_footer.php'; ?>