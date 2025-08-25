<?php
$titulo = "Recibir de Reparación - Sistema de Inventarios";
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'includes/config_functions.php';

verificarLogin();
verificarPermiso('reparaciones_actualizar');

$database = new Database();
$db = $database->getConnection();

$success = '';
$error = '';

if ($_POST && isset($_POST['action']) && $_POST['action'] == 'recibir_reparacion') {
    $reparacion_id = (int)$_POST['reparacion_id'];
    $estado_final = $_POST['estado_final']; // 'completado' o 'perdido'
    $costo_reparacion = floatval($_POST['costo_reparacion'] ?? 0);
    $notas = trim($_POST['notas'] ?? '');
    $usuario_id = $_SESSION['usuario_id'];
    
    if (!in_array($estado_final, ['completado', 'perdido'])) {
        $error = "Estado final inválido";
    } else {
        $db->beginTransaction();
        
        try {
            // Obtener datos de la reparación
            $query_reparacion = "SELECT * FROM reparaciones WHERE id = ? AND estado IN ('enviado', 'en_reparacion')";
            $stmt_reparacion = $db->prepare($query_reparacion);
            $stmt_reparacion->execute([$reparacion_id]);
            $reparacion = $stmt_reparacion->fetch(PDO::FETCH_ASSOC);
            
            if (!$reparacion) {
                throw new Exception("Reparación no encontrada o ya fue procesada");
            }
            
            // Actualizar estado de la reparación
            $query_update_reparacion = "UPDATE reparaciones 
                                       SET estado = ?, fecha_retorno = NOW(), costo_reparacion = ?, 
                                           notas = ?, usuario_retorno_id = ?
                                       WHERE id = ?";
            $stmt_update_reparacion = $db->prepare($query_update_reparacion);
            $stmt_update_reparacion->execute([$estado_final, $costo_reparacion, $notas, $usuario_id, $reparacion_id]);
            
            // Actualizar inventario según el estado final
            if ($estado_final === 'completado') {
                // Reparación exitosa: restar de cantidad_reparacion (regresa al stock disponible)
                $query_update_inventario = "UPDATE inventarios 
                                           SET cantidad_reparacion = cantidad_reparacion - ? 
                                           WHERE tienda_id = ? AND producto_id = ?";
                $stmt_update_inventario = $db->prepare($query_update_inventario);
                $stmt_update_inventario->execute([$reparacion['cantidad'], $reparacion['tienda_id'], $reparacion['producto_id']]);
                
                // Registrar movimiento de inventario (entrada)
                $query_movimiento = "INSERT INTO movimientos_inventario (tipo_movimiento, producto_id, tienda_destino_id, cantidad, motivo, referencia_id, referencia_tipo, usuario_id, fecha)
                                    VALUES ('entrada', ?, ?, ?, 'Retorno de reparación exitosa', ?, 'reparacion', ?, NOW())";
                $stmt_movimiento = $db->prepare($query_movimiento);
                $stmt_movimiento->execute([$reparacion['producto_id'], $reparacion['tienda_id'], $reparacion['cantidad'], $reparacion_id, $usuario_id]);
                
            } else { // perdido
                // Producto perdido: restar de cantidad_reparacion Y de cantidad total (pérdida definitiva)
                $query_update_inventario = "UPDATE inventarios 
                                           SET cantidad_reparacion = cantidad_reparacion - ?, 
                                               cantidad = cantidad - ?
                                           WHERE tienda_id = ? AND producto_id = ?";
                $stmt_update_inventario = $db->prepare($query_update_inventario);
                $stmt_update_inventario->execute([$reparacion['cantidad'], $reparacion['cantidad'], $reparacion['tienda_id'], $reparacion['producto_id']]);
                
                // Registrar movimiento de inventario (pérdida)
                $query_movimiento = "INSERT INTO movimientos_inventario (tipo_movimiento, producto_id, tienda_origen_id, cantidad, motivo, referencia_id, referencia_tipo, usuario_id, fecha)
                                    VALUES ('ajuste', ?, ?, ?, 'Pérdida en reparación', ?, 'reparacion', ?, NOW())";
                $stmt_movimiento = $db->prepare($query_movimiento);
                $stmt_movimiento->execute([$reparacion['producto_id'], $reparacion['tienda_id'], $reparacion['cantidad'], $reparacion_id, $usuario_id]);
            }
            
            $db->commit();
            $success = "Reparación procesada exitosamente como: " . ($estado_final === 'completado' ? 'Completada' : 'Perdida');
            
            // Limpiar formulario
            $_POST = [];
            
        } catch (Exception $e) {
            $db->rollBack();
            $error = "Error al procesar reparación: " . $e->getMessage();
        }
    }
}

// Obtener reparaciones pendientes
$query_reparaciones = "SELECT r.*, p.nombre as producto_nombre, p.codigo, t.nombre as tienda_nombre,
                       u1.nombre as usuario_envio, u2.nombre as usuario_retorno,
                       DATEDIFF(NOW(), r.fecha_envio) as dias_reparacion
                       FROM reparaciones r
                       JOIN productos p ON r.producto_id = p.id
                       JOIN tiendas t ON r.tienda_id = t.id
                       JOIN usuarios u1 ON r.usuario_envio_id = u1.id
                       LEFT JOIN usuarios u2 ON r.usuario_retorno_id = u2.id
                       WHERE r.estado IN ('enviado', 'en_reparacion')
                       ORDER BY r.fecha_envio ASC";
$stmt_reparaciones = $db->prepare($query_reparaciones);
$stmt_reparaciones->execute();
$reparaciones_pendientes = $stmt_reparaciones->fetchAll(PDO::FETCH_ASSOC);

include 'includes/layout_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 rs-wrap-sm">
    <h2><i class="fas fa-check-circle"></i> Recibir de Reparación</h2>
    <div class="btn-group rs-wrap-sm">
        <a href="reparaciones.php" class="btn btn-outline-secondary">
            <i class="fas fa-list"></i> Ver Reparaciones
        </a>
        <a href="reparaciones_enviar.php" class="btn btn-outline-primary">
            <i class="fas fa-tools"></i> Enviar a Reparación
        </a>
    </div>
</div>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?php echo $success; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card">
            <div class="card-header rs-wrap-sm">
        <h5 class="mb-0"><i class="fas fa-clock"></i> Reparaciones Pendientes</h5>
    </div>
    <div class="card-body">
        <?php if (!empty($reparaciones_pendientes)): ?>
            <div class="table-responsive-md">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Producto</th>
                            <th>Tienda</th>
                            <th>Cantidad</th>
                            <th>Estado</th>
                            <th>Días</th>
                            <th>Enviado por</th>
                            <th>Problema</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reparaciones_pendientes as $rep): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($rep['codigo']); ?></td>
                                <td><?php echo htmlspecialchars($rep['producto_nombre']); ?></td>
                                <td><?php echo htmlspecialchars($rep['tienda_nombre']); ?></td>
                                <td>
                                    <span class="badge bg-info"><?php echo $rep['cantidad']; ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $rep['estado'] === 'enviado' ? 'warning' : 'primary'; ?>">
                                        <?php echo ucfirst($rep['estado']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $rep['dias_reparacion'] > 30 ? 'danger' : ($rep['dias_reparacion'] > 15 ? 'warning' : 'success'); ?>">
                                        <?php echo $rep['dias_reparacion']; ?> días
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($rep['usuario_envio']); ?></td>
                                <td>
                                    <small class="text-muted">
                                        <?php echo htmlspecialchars(substr($rep['notas'] ?? '', 0, 50)) . (strlen($rep['notas'] ?? '') > 50 ? '...' : ''); ?>
                                    </small>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-success" 
                                            onclick="abrirModalRecibir(<?php echo htmlspecialchars(json_encode($rep)); ?>)">
                                        <i class="fas fa-check"></i> Recibir
                                    </button>
                                    
                                    <?php if ($rep['estado'] === 'enviado'): ?>
                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                onclick="cambiarEstado(<?php echo $rep['id']; ?>, 'en_reparacion')">
                                            <i class="fas fa-wrench"></i> En Reparación
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-4">
                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                <h5>No hay reparaciones pendientes</h5>
                <p class="text-muted">Todas las reparaciones han sido procesadas</p>
                <a href="reparaciones_enviar.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Enviar Nueva Reparación
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal para recibir reparación -->
<div class="modal fade" id="modalRecibir" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Recibir de Reparación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="form-recibir">
                <div class="modal-body">
                    <input type="hidden" name="action" value="recibir_reparacion">
                    <input type="hidden" name="reparacion_id" id="modal-reparacion-id">
                    
                    <div id="modal-info" class="alert alert-info">
                        <!-- Información de la reparación se llenará dinámicamente -->
                    </div>
                    
                    <div class="mb-3">
                        <label for="estado_final" class="form-label">Estado Final</label>
                        <select class="form-select" name="estado_final" id="estado_final" required>
                            <option value="">Seleccionar resultado...</option>
                            <option value="completado">✅ Reparación Completada (devolver al stock)</option>
                            <option value="perdido">❌ Producto Perdido/Irreparable (dar de baja)</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="costo_reparacion" class="form-label">Costo de Reparación</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control" name="costo_reparacion" id="costo_reparacion" 
                                   min="0" step="0.01" placeholder="0.00">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notas" class="form-label">Notas Adicionales</label>
                        <textarea class="form-control" name="notas" id="notas" rows="3" 
                                  placeholder="Detalles sobre la reparación, condiciones del producto, etc."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Procesar Reparación
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function abrirModalRecibir(reparacion) {
    document.getElementById('modal-reparacion-id').value = reparacion.id;
    
    const info = `
        <strong>Producto:</strong> ${reparacion.codigo} - ${reparacion.producto_nombre}<br>
        <strong>Tienda:</strong> ${reparacion.tienda_nombre}<br>
        <strong>Cantidad:</strong> ${reparacion.cantidad} unidades<br>
        <strong>Problema:</strong> ${reparacion.notas || 'No especificado'}<br>
        <strong>Técnico:</strong> ${reparacion.proveedor_reparacion || 'No especificado'}<br>
        <strong>Enviado:</strong> ${new Date(reparacion.fecha_envio).toLocaleDateString()} por ${reparacion.usuario_envio}
    `;
    
    document.getElementById('modal-info').innerHTML = info;
    
    // Resetear formulario
    document.getElementById('form-recibir').reset();
    document.getElementById('modal-reparacion-id').value = reparacion.id;
    
    new bootstrap.Modal(document.getElementById('modalRecibir')).show();
}

function cambiarEstado(reparacionId, nuevoEstado) {
    if (confirm('¿Está seguro de cambiar el estado a "' + nuevoEstado.replace('_', ' ') + '"?')) {
        // Crear formulario dinámico para cambiar estado
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        
        const actionInput = document.createElement('input');
        actionInput.name = 'action';
        actionInput.value = 'cambiar_estado';
        
        const idInput = document.createElement('input');
        idInput.name = 'reparacion_id';
        idInput.value = reparacionId;
        
        const estadoInput = document.createElement('input');
        estadoInput.name = 'nuevo_estado';
        estadoInput.value = nuevoEstado;
        
        form.appendChild(actionInput);
        form.appendChild(idInput);
        form.appendChild(estadoInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}

// Manejar cambio de estado simple
<?php if (isset($_POST['action']) && $_POST['action'] == 'cambiar_estado'): ?>
    <?php
    $reparacion_id = (int)$_POST['reparacion_id'];
    $nuevo_estado = $_POST['nuevo_estado'];
    
    if (in_array($nuevo_estado, ['en_reparacion'])) {
        try {
            $query_update = "UPDATE reparaciones SET estado = ? WHERE id = ?";
            $stmt_update = $db->prepare($query_update);
            $stmt_update->execute([$nuevo_estado, $reparacion_id]);
            echo "window.location.reload();";
        } catch (Exception $e) {
            echo "showToast('Error al cambiar estado: " . addslashes($e->getMessage()) . "', 'error');";
        }
    }
    ?>
<?php endif; ?>
</script>

<?php include 'includes/layout_footer.php'; ?>
