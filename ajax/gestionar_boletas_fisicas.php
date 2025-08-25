<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

verificarLogin();
verificarPermiso('config_sistema');

$database = new Database();
$db = $database->getConnection();

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['reconciliacion_id']) || !is_numeric($input['reconciliacion_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de reconciliación inválido']);
    exit;
}

$reconciliacion_id = (int)$input['reconciliacion_id'];

try {
    // Obtener detalles de la reconciliación
    $query = "SELECT 
                 rb.*,
                 t.nombre as tienda_nombre,
                 rd.total_general as total_reportado,
                 rd.encargado_id,
                 u_encargado.nombre as encargado_nombre
              FROM reconciliacion_boletas rb
              JOIN tiendas t ON rb.tienda_id = t.id
              JOIN reportes_diarios_encargado rd ON rb.reporte_diario_id = rd.id
              JOIN usuarios u_encargado ON rd.encargado_id = u_encargado.id
              WHERE rb.id = ?";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$reconciliacion_id]);
    $reconciliacion = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reconciliacion) {
        echo json_encode(['success' => false, 'message' => 'Reconciliación no encontrada']);
        exit;
    }
    
    // Obtener boletas físicas ya registradas
    $query_boletas = "SELECT * FROM detalle_boletas_fisicas 
                      WHERE reconciliacion_id = ? 
                      ORDER BY fecha_boleta DESC";
    $stmt_boletas = $db->prepare($query_boletas);
    $stmt_boletas->execute([$reconciliacion_id]);
    $boletas_registradas = $stmt_boletas->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener ventas del sistema para comparación
    $query_ventas_sistema = "SELECT 
                                id, fecha, total, metodo_pago,
                                CASE WHEN vendedor_id IS NOT NULL THEN 
                                    (SELECT nombre FROM vendedores WHERE id = vendedor_id)
                                ELSE 'Sin vendedor' END as vendedor_nombre
                             FROM ventas 
                             WHERE tienda_id = ? AND DATE(fecha) = ? AND estado = 'completada'
                             ORDER BY fecha ASC";
    $stmt_ventas = $db->prepare($query_ventas_sistema);
    $stmt_ventas->execute([$reconciliacion['tienda_id'], $reconciliacion['fecha_reconciliacion']]);
    $ventas_sistema = $stmt_ventas->fetchAll(PDO::FETCH_ASSOC);
    
    // Generar HTML
    ob_start();
    ?>
    <div class="row mb-3">
        <div class="col-12">
            <div class="alert alert-info">
                <h6><i class="fas fa-info-circle me-1"></i>Información de Reconciliación:</h6>
                <ul class="mb-0">
                    <li><strong>Tienda:</strong> <?= htmlspecialchars($reconciliacion['tienda_nombre']) ?></li>
                    <li><strong>Fecha:</strong> <?= date('d/m/Y', strtotime($reconciliacion['fecha_reconciliacion'])) ?></li>
                    <li><strong>Total Sistema:</strong> Q <?= number_format($reconciliacion['total_sistema'], 2) ?></li>
                    <li><strong>Ventas en Sistema:</strong> <?= $reconciliacion['ventas_sistema'] ?></li>
                    <li><strong>Estado:</strong> <?= ucfirst($reconciliacion['estado']) ?></li>
                </ul>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <!-- Formulario para agregar nueva boleta -->
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-plus me-1"></i>Registrar Nueva Boleta Física
                    </h6>
                </div>
                <div class="card-body">
                    <form id="formNuevaBoleta">
                        <input type="hidden" name="reconciliacion_id" value="<?= $reconciliacion_id ?>">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="numero_boleta" class="form-label">Número de Boleta</label>
                                    <input type="text" class="form-control" name="numero_boleta" required 
                                           placeholder="Ej: 001234">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="fecha_boleta" class="form-label">Fecha y Hora</label>
                                    <input type="datetime-local" class="form-control" name="fecha_boleta" required 
                                           value="<?= date('Y-m-d\T09:00', strtotime($reconciliacion['fecha_reconciliacion'])) ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="total_boleta" class="form-label">Total de la Boleta</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Q</span>
                                        <input type="number" step="0.01" class="form-control" name="total_boleta" required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="metodo_pago" class="form-label">Método de Pago</label>
                                    <select class="form-select" name="metodo_pago" required>
                                        <option value="">Seleccionar...</option>
                                        <option value="efectivo">Efectivo</option>
                                        <option value="tarjeta">Tarjeta</option>
                                        <option value="transferencia">Transferencia</option>
                                        <option value="otros">Otros</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="observaciones" class="form-label">Observaciones</label>
                            <textarea class="form-control" name="observaciones" rows="2" 
                                      placeholder="Observaciones adicionales..."></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="imagen_boleta" class="form-label">Imagen de la Boleta (Opcional)</label>
                            <input type="file" class="form-control" name="imagen_boleta" 
                                   accept="image/jpeg,image/png,image/gif">
                            <small class="text-muted">Máximo 5MB, formatos: JPG, PNG, GIF</small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Registrar Boleta
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Sugerencias automáticas basadas en el sistema -->
            <?php if (!empty($ventas_sistema)): ?>
            <div class="card mt-3">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-lightbulb me-1"></i>Sugerencias del Sistema
                    </h6>
                </div>
                <div class="card-body">
                    <p class="small mb-2">Ventas registradas en el sistema que podrían corresponder a boletas físicas:</p>
                    <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Hora</th>
                                    <th>Total</th>
                                    <th>Método</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ventas_sistema as $venta): ?>
                                <tr>
                                    <td>#<?= $venta['id'] ?></td>
                                    <td><?= date('H:i', strtotime($venta['fecha'])) ?></td>
                                    <td>Q <?= number_format($venta['total'], 2) ?></td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <?= ucfirst($venta['metodo_pago']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-success" 
                                                onclick="usarSugerencia(<?= $venta['id'] ?>, '<?= $venta['fecha'] ?>', <?= $venta['total'] ?>, '<?= $venta['metodo_pago'] ?>')">
                                            <i class="fas fa-magic"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="col-md-6">
            <!-- Lista de boletas ya registradas -->
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-receipt me-1"></i>Boletas Físicas Registradas
                        <span class="badge bg-light text-dark ms-2"><?= count($boletas_registradas) ?></span>
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (empty($boletas_registradas)): ?>
                        <div class="text-center py-3">
                            <i class="fas fa-inbox fa-3x text-muted mb-2"></i>
                            <p class="text-muted">No hay boletas registradas aún</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                            <table class="table table-sm">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th>Número</th>
                                        <th>Fecha/Hora</th>
                                        <th>Total</th>
                                        <th>Método</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($boletas_registradas as $boleta): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($boleta['numero_boleta']) ?></strong>
                                        </td>
                                        <td>
                                            <small>
                                                <?= date('d/m H:i', strtotime($boleta['fecha_boleta'])) ?>
                                            </small>
                                        </td>
                                        <td>
                                            <strong class="text-success">Q <?= number_format($boleta['total_boleta'], 2) ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                <?= ucfirst($boleta['metodo_pago']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($boleta['verificado']): ?>
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check"></i> Verificado
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">
                                                    <i class="fas fa-clock"></i> Pendiente
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <?php if ($boleta['imagen_boleta']): ?>
                                                <button class="btn btn-outline-info" onclick="verImagenBoleta('<?= $boleta['imagen_boleta'] ?>')">
                                                    <i class="fas fa-image"></i>
                                                </button>
                                                <?php endif; ?>
                                                
                                                <?php if (!$boleta['verificado'] && $reconciliacion['estado'] !== 'aprobado'): ?>
                                                <button class="btn btn-outline-success" onclick="verificarBoleta(<?= $boleta['id'] ?>)">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button class="btn btn-outline-danger" onclick="eliminarBoleta(<?= $boleta['id'] ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Resumen de boletas registradas -->
                        <div class="mt-3 p-3 bg-light rounded">
                            <?php 
                            $total_registrado = array_sum(array_column($boletas_registradas, 'total_boleta'));
                            $boletas_verificadas = array_filter($boletas_registradas, function($b) { return $b['verificado']; });
                            ?>
                            <div class="row">
                                <div class="col-6">
                                    <small>Total Registrado:</small>
                                    <br><strong class="text-success">Q <?= number_format($total_registrado, 2) ?></strong>
                                </div>
                                <div class="col-6">
                                    <small>Verificadas:</small>
                                    <br><strong><?= count($boletas_verificadas) ?> de <?= count($boletas_registradas) ?></strong>
                                </div>
                            </div>
                            
                            <div class="mt-2">
                                <small>Diferencia vs Sistema:</small>
                                <?php 
                                $diferencia = $total_registrado - $reconciliacion['total_sistema'];
                                $color = abs($diferencia) <= 10 ? 'success' : (abs($diferencia) <= 50 ? 'warning' : 'danger');
                                ?>
                                <br><span class="badge bg-<?= $color ?>">
                                    <?= $diferencia >= 0 ? '+' : '' ?>Q <?= number_format($diferencia, 2) ?>
                                </span>
                            </div>
                        </div>
                        
                        <?php if (count($boletas_registradas) > 0): ?>
                        <div class="mt-3 text-center">
                            <button class="btn btn-outline-primary" onclick="actualizarTotalesReconciliacion()">
                                <i class="fas fa-sync-alt me-1"></i>Actualizar Totales de Reconciliación
                            </button>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    // Función para usar sugerencia del sistema
    function usarSugerencia(ventaId, fecha, total, metodoPago) {
        document.querySelector('input[name="numero_boleta"]').value = 'SIS-' + ventaId;
        document.querySelector('input[name="fecha_boleta"]').value = fecha.replace(' ', 'T');
        document.querySelector('input[name="total_boleta"]').value = total;
        document.querySelector('select[name="metodo_pago"]').value = metodoPago;
        document.querySelector('textarea[name="observaciones"]').value = 'Generado automáticamente desde venta del sistema #' + ventaId;
        
        // Scroll al formulario
        document.querySelector('#formNuevaBoleta').scrollIntoView({ behavior: 'smooth' });
    }
    
    // Manejar envío del formulario
    document.getElementById('formNuevaBoleta').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const btnSubmit = this.querySelector('button[type="submit"]');
        const originalContent = btnSubmit.innerHTML;
        
        btnSubmit.disabled = true;
        btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Registrando...';
        
        fetch('../ajax/registrar_boleta_fisica.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarToast(data.message, 'success');
                // Recargar el modal
                setTimeout(() => {
                    gestionarBoletas(<?= $reconciliacion_id ?>);
                }, 1000);
            } else {
                mostrarToast(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarToast('Error al registrar boleta', 'error');
        })
        .finally(() => {
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = originalContent;
        });
    });
    
    function verificarBoleta(boletaId) {
        if (!confirm('¿Está seguro de verificar esta boleta?')) return;
        
        fetch('../ajax/verificar_boleta_fisica.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                boleta_id: boletaId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarToast(data.message, 'success');
                gestionarBoletas(<?= $reconciliacion_id ?>);
            } else {
                mostrarToast(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarToast('Error al verificar boleta', 'error');
        });
    }
    
    function eliminarBoleta(boletaId) {
        if (!confirm('¿Está seguro de eliminar esta boleta? Esta acción no se puede deshacer.')) return;
        
        fetch('../ajax/eliminar_boleta_fisica.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                boleta_id: boletaId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarToast(data.message, 'success');
                gestionarBoletas(<?= $reconciliacion_id ?>);
            } else {
                mostrarToast(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarToast('Error al eliminar boleta', 'error');
        });
    }
    
    function verImagenBoleta(imagen) {
        const modalHtml = `
            <div class="modal fade" id="modalImagenBoleta" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Imagen de Boleta</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body text-center">
                            <img src="../uploads/boletas_fisicas/${imagen}" class="img-fluid" alt="Boleta">
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        const modal = new bootstrap.Modal(document.getElementById('modalImagenBoleta'));
        modal.show();
        
        document.getElementById('modalImagenBoleta').addEventListener('hidden.bs.modal', function() {
            this.remove();
        });
    }
    
    function actualizarTotalesReconciliacion() {
        fetch('../ajax/actualizar_totales_reconciliacion.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                reconciliacion_id: <?= $reconciliacion_id ?>
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarToast(data.message, 'success');
                // Cerrar modal y recargar la página principal
                setTimeout(() => {
                    bootstrap.Modal.getInstance(document.getElementById('modalBoletas')).hide();
                    window.location.reload();
                }, 1000);
            } else {
                mostrarToast(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarToast('Error al actualizar totales', 'error');
        });
    }
    </script>
    <?php
    $html = ob_get_clean();
    
    echo json_encode([
        'success' => true,
        'html' => $html
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al cargar boletas: ' . $e->getMessage()
    ]);
}
?>