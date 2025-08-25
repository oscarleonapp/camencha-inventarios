<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

verificarLogin();
verificarPermiso('ventas_crear');

$database = new Database();
$db = $database->getConnection();

// Obtener tienda del usuario actual (si es encargado de una tienda específica)
$usuario_id = $_SESSION['usuario_id'];
$tienda_usuario_query = "SELECT tienda_id FROM usuarios WHERE id = ? AND tienda_id IS NOT NULL";
$stmt_tienda = $db->prepare($tienda_usuario_query);
$stmt_tienda->execute([$usuario_id]);
$tienda_asignada = $stmt_tienda->fetchColumn();

// Si es admin, puede elegir tienda, si es encargado solo su tienda
$tiendas = [];
if (!$tienda_asignada) {
    $tiendas_query = "SELECT id, nombre FROM tiendas WHERE activo = 1 ORDER BY nombre";
    $tiendas = $db->query($tiendas_query)->fetchAll(PDO::FETCH_ASSOC);
}

// Fecha por defecto es hoy
$fecha_hoy = date('Y-m-d');

// Verificar si ya existe reporte para hoy
$reporte_existente = null;
if ($tienda_asignada) {
    $query_existente = "SELECT * FROM reportes_diarios_encargado 
                        WHERE tienda_id = ? AND fecha_reporte = ? AND encargado_id = ?";
    $stmt_existente = $db->prepare($query_existente);
    $stmt_existente->execute([$tienda_asignada, $fecha_hoy, $usuario_id]);
    $reporte_existente = $stmt_existente->fetch(PDO::FETCH_ASSOC);
}

// Obtener datos del sistema para comparación
$ventas_sistema = null;
if ($tienda_asignada) {
    $query_sistema = "SELECT 
                         COUNT(*) as cantidad_ventas,
                         SUM(total) as total_sistema,
                         SUM(CASE WHEN metodo_pago = 'efectivo' THEN total ELSE 0 END) as efectivo_sistema,
                         SUM(CASE WHEN metodo_pago = 'tarjeta' THEN total ELSE 0 END) as tarjeta_sistema,
                         SUM(CASE WHEN metodo_pago = 'transferencia' THEN total ELSE 0 END) as transferencia_sistema
                      FROM ventas 
                      WHERE tienda_id = ? AND DATE(fecha) = ? AND estado = 'completada'";
    $stmt_sistema = $db->prepare($query_sistema);
    $stmt_sistema->execute([$tienda_asignada, $fecha_hoy]);
    $ventas_sistema = $stmt_sistema->fetch(PDO::FETCH_ASSOC);
}

include_once 'includes/layout_header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-cash-register me-2"></i>Reporte de Ingresos Diario</h2>
                <div class="d-flex gap-2">
                    <?php if ($reporte_existente): ?>
                        <span class="badge bg-info fs-6">
                            <i class="fas fa-info-circle me-1"></i>
                            Ya reportaste hoy: <?= date('H:i', strtotime($reporte_existente['fecha_creacion'])) ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if ($ventas_sistema): ?>
    <!-- Datos del sistema para referencia -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-info">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-desktop me-2"></i>Referencia del Sistema - <?= date('d/m/Y') ?>
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-2">
                            <div class="text-center">
                                <h4 class="text-info"><?= $ventas_sistema['cantidad_ventas'] ?></h4>
                                <small>Ventas Registradas</small>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="text-center">
                                <h4 class="text-success">Q <?= number_format($ventas_sistema['total_sistema'], 2) ?></h4>
                                <small>Total Sistema</small>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="text-center">
                                <h5 class="text-success">Q <?= number_format($ventas_sistema['efectivo_sistema'], 2) ?></h5>
                                <small>Efectivo</small>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="text-center">
                                <h5 class="text-primary">Q <?= number_format($ventas_sistema['tarjeta_sistema'], 2) ?></h5>
                                <small>Tarjeta</small>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="text-center">
                                <h5 class="text-info">Q <?= number_format($ventas_sistema['transferencia_sistema'], 2) ?></h5>
                                <small>Transferencia</small>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="text-center">
                                <div class="alert alert-info py-1 px-2">
                                    <small><i class="fas fa-lightbulb me-1"></i>Usa estos datos como referencia</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Formulario de reporte -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-file-invoice-dollar me-2"></i>
                <?= $reporte_existente ? 'Actualizar' : 'Crear' ?> Reporte de Ingresos
            </h5>
        </div>
        <div class="card-body">
            <form id="formReporte" method="POST">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="fecha_reporte" class="form-label">Fecha del Reporte</label>
                            <input type="date" class="form-control" id="fecha_reporte" name="fecha_reporte" 
                                   value="<?= $reporte_existente['fecha_reporte'] ?? $fecha_hoy ?>" 
                                   max="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>
                    
                    <?php if (!$tienda_asignada): ?>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="tienda_id" class="form-label">Tienda</label>
                            <select class="form-select" id="tienda_id" name="tienda_id" required>
                                <option value="">Seleccionar tienda...</option>
                                <?php foreach ($tiendas as $tienda): ?>
                                    <option value="<?= $tienda['id'] ?>" 
                                            <?= ($reporte_existente && $reporte_existente['tienda_id'] == $tienda['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($tienda['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <?php else: ?>
                        <input type="hidden" name="tienda_id" value="<?= $tienda_asignada ?>">
                    <?php endif; ?>
                </div>

                <!-- Desglose por método de pago -->
                <div class="row">
                    <div class="col-12">
                        <h6 class="text-primary mb-3">
                            <i class="fas fa-money-bill-wave me-2"></i>Desglose por Método de Pago
                        </h6>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="total_efectivo" class="form-label">
                                <i class="fas fa-money-bill text-success me-1"></i>Total Efectivo
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">Q</span>
                                <input type="number" step="0.01" class="form-control" id="total_efectivo" 
                                       name="total_efectivo" value="<?= $reporte_existente['total_efectivo'] ?? '0.00' ?>" 
                                       required oninput="calcularTotal()">
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="total_tarjeta" class="form-label">
                                <i class="fas fa-credit-card text-primary me-1"></i>Total Tarjeta
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">Q</span>
                                <input type="number" step="0.01" class="form-control" id="total_tarjeta" 
                                       name="total_tarjeta" value="<?= $reporte_existente['total_tarjeta'] ?? '0.00' ?>" 
                                       required oninput="calcularTotal()">
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="total_transferencia" class="form-label">
                                <i class="fas fa-exchange-alt text-info me-1"></i>Transferencias
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">Q</span>
                                <input type="number" step="0.01" class="form-control" id="total_transferencia" 
                                       name="total_transferencia" value="<?= $reporte_existente['total_transferencia'] ?? '0.00' ?>" 
                                       required oninput="calcularTotal()">
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="total_otros" class="form-label">
                                <i class="fas fa-ellipsis-h text-secondary me-1"></i>Otros Métodos
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">Q</span>
                                <input type="number" step="0.01" class="form-control" id="total_otros" 
                                       name="total_otros" value="<?= $reporte_existente['total_otros'] ?? '0.00' ?>" 
                                       required oninput="calcularTotal()">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total calculado -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label"><strong>Total General</strong></label>
                            <div class="input-group">
                                <span class="input-group-text bg-success text-white">Q</span>
                                <input type="text" class="form-control bg-light fw-bold text-success" 
                                       id="total_calculado" readonly>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($ventas_sistema): ?>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label"><strong>Diferencia vs Sistema</strong></label>
                            <div class="input-group">
                                <span class="input-group-text" id="diferencia-icon">Q</span>
                                <input type="text" class="form-control fw-bold" id="diferencia_calculada" readonly>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Observaciones -->
                <div class="row">
                    <div class="col-12">
                        <div class="mb-3">
                            <label for="observaciones" class="form-label">Observaciones</label>
                            <textarea class="form-control" id="observaciones" name="observaciones" rows="3" 
                                      placeholder="Incluye cualquier información adicional, incidencias o aclaraciones..."><?= $reporte_existente['observaciones'] ?? '' ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Botones -->
                <div class="row">
                    <div class="col-12">
                        <div class="d-flex justify-content-between">
                            <div>
                                <?php if ($reporte_existente): ?>
                                    <span class="text-muted">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Estado: <?= ucfirst(str_replace('_', ' ', $reporte_existente['estado'])) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div>
                                <button type="button" class="btn btn-secondary me-2" onclick="limpiarFormulario()">
                                    <i class="fas fa-eraser me-1"></i>Limpiar
                                </button>
                                <button type="submit" class="btn btn-primary" id="btnSubmit">
                                    <i class="fas fa-save me-1"></i>
                                    <?= $reporte_existente ? 'Actualizar Reporte' : 'Guardar Reporte' ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Historial de reportes recientes -->
    <?php if ($tienda_asignada): ?>
    <div class="card mt-4">
        <div class="card-header">
            <h6 class="mb-0">
                <i class="fas fa-history me-2"></i>Mis Reportes Recientes
            </h6>
        </div>
        <div class="card-body">
            <div id="historialReportes">
                <div class="text-center">
                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
const totalSistema = <?= $ventas_sistema['total_sistema'] ?? 0 ?>;

function calcularTotal() {
    const efectivo = parseFloat(document.getElementById('total_efectivo').value) || 0;
    const tarjeta = parseFloat(document.getElementById('total_tarjeta').value) || 0;
    const transferencia = parseFloat(document.getElementById('total_transferencia').value) || 0;
    const otros = parseFloat(document.getElementById('total_otros').value) || 0;
    
    const total = efectivo + tarjeta + transferencia + otros;
    
    document.getElementById('total_calculado').value = 'Q ' + total.toLocaleString('es-GT', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
    
    // Calcular diferencia si hay datos del sistema
    if (totalSistema > 0) {
        const diferencia = total - totalSistema;
        const diferenciaCampo = document.getElementById('diferencia_calculada');
        const diferenciaIcon = document.getElementById('diferencia-icon');
        
        let color = 'text-success';
        let icon = 'fas fa-equals';
        
        if (diferencia > 0) {
            color = 'text-warning';
            icon = 'fas fa-arrow-up';
        } else if (diferencia < 0) {
            color = 'text-danger';
            icon = 'fas fa-arrow-down';
        }
        
        diferenciaCampo.value = (diferencia >= 0 ? '+' : '') + 'Q ' + Math.abs(diferencia).toLocaleString('es-GT', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
        diferenciaCampo.className = `form-control fw-bold ${color}`;
        diferenciaIcon.innerHTML = `<i class="${icon}"></i>`;
        diferenciaIcon.className = `input-group-text ${color.replace('text-', 'bg-')} text-white`;
    }
}

function limpiarFormulario() {
    if (confirm('¿Está seguro de limpiar el formulario?')) {
        document.getElementById('total_efectivo').value = '0.00';
        document.getElementById('total_tarjeta').value = '0.00';
        document.getElementById('total_transferencia').value = '0.00';
        document.getElementById('total_otros').value = '0.00';
        document.getElementById('observaciones').value = '';
        calcularTotal();
    }
}

// Manejar envío del formulario
document.getElementById('formReporte').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const btnSubmit = document.getElementById('btnSubmit');
    const originalContent = btnSubmit.innerHTML;
    
    btnSubmit.disabled = true;
    btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Guardando...';
    
    fetch('ajax/procesar_reporte_ingresos.php', {
        method: 'POST',
        body: formData
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
        mostrarToast('Error al procesar el reporte', 'error');
    })
    .finally(() => {
        btnSubmit.disabled = false;
        btnSubmit.innerHTML = originalContent;
    });
});

// Cargar historial al inicializar
<?php if ($tienda_asignada): ?>
cargarHistorialReportes();
<?php endif; ?>

function cargarHistorialReportes() {
    fetch('ajax/get_historial_reportes.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            tienda_id: <?= $tienda_asignada ?>
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('historialReportes').innerHTML = data.html;
        } else {
            document.getElementById('historialReportes').innerHTML = 
                '<p class="text-muted">No se pudieron cargar los reportes</p>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('historialReportes').innerHTML = 
            '<p class="text-muted">Error al cargar el historial</p>';
    });
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

// Inicializar cálculo
calcularTotal();
</script>

<?php include_once 'includes/layout_footer.php'; ?>