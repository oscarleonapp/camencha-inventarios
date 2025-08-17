<?php
$titulo = "Detalle de Venta - Sistema de Inventarios";
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'includes/config_functions.php';

verificarLogin();
verificarPermiso('ventas_ver');

$database = new Database();
$db = $database->getConnection();

if (!isset($_GET['id'])) {
    header('Location: ventas.php');
    exit();
}

$venta_id = $_GET['id'];

// Procesar acciones POST
if ($_POST && isset($_POST['action'])) {
    if ($_POST['action'] == 'marcar_entregada') {
        $query_update = "UPDATE ventas SET estado = 'entregada', fecha_entrega = NOW() WHERE id = ?";
        $stmt_update = $db->prepare($query_update);
        if ($stmt_update->execute([$venta_id])) {
            $success = "Venta marcada como entregada exitosamente";
        } else {
            $error = "Error al marcar la venta como entregada";
        }
    }
    
    if ($_POST['action'] == 'reembolsar') {
        $razon_reembolso = $_POST['razon_reembolso'] ?? 'No especificada';
        
        $db->beginTransaction();
        
        try {
            // Obtener detalles de la venta
            $query_venta = "SELECT * FROM ventas WHERE id = ?";
            $stmt_venta = $db->prepare($query_venta);
            $stmt_venta->execute([$venta_id]);
            $venta = $stmt_venta->fetch(PDO::FETCH_ASSOC);
            
            $query_detalles = "SELECT dv.*, p.nombre, p.tipo 
                              FROM detalle_ventas dv 
                              JOIN productos p ON dv.producto_id = p.id 
                              WHERE dv.venta_id = ?";
            $stmt_detalles = $db->prepare($query_detalles);
            $stmt_detalles->execute([$venta_id]);
            $detalles = $stmt_detalles->fetchAll(PDO::FETCH_ASSOC);
            
            // Reintegrar productos al inventario
            foreach ($detalles as $detalle) {
                if ($detalle['tipo'] == 'conjunto') {
                    // Si es un conjunto, reintegrar componentes
                    $query_componentes = "SELECT pc.producto_elemento_id, pc.cantidad 
                                         FROM producto_componentes pc 
                                         WHERE pc.producto_conjunto_id = ?";
                    $stmt_componentes = $db->prepare($query_componentes);
                    $stmt_componentes->execute([$detalle['producto_id']]);
                    $componentes = $stmt_componentes->fetchAll(PDO::FETCH_ASSOC);
                    
                    foreach ($componentes as $componente) {
                        $cantidad_reintegrar = $componente['cantidad'] * $detalle['cantidad'];
                        
                        // Verificar si existe registro de inventario
                        $query_check = "SELECT id FROM inventarios WHERE tienda_id = ? AND producto_id = ?";
                        $stmt_check = $db->prepare($query_check);
                        $stmt_check->execute([$venta['tienda_id'], $componente['producto_elemento_id']]);
                        
                        if ($stmt_check->rowCount() > 0) {
                            $query_update_inv = "UPDATE inventarios SET cantidad = cantidad + ? WHERE tienda_id = ? AND producto_id = ?";
                            $stmt_update_inv = $db->prepare($query_update_inv);
                            $stmt_update_inv->execute([$cantidad_reintegrar, $venta['tienda_id'], $componente['producto_elemento_id']]);
                        } else {
                            $query_insert_inv = "INSERT INTO inventarios (tienda_id, producto_id, cantidad, cantidad_reparacion) VALUES (?, ?, ?, 0)";
                            $stmt_insert_inv = $db->prepare($query_insert_inv);
                            $stmt_insert_inv->execute([$venta['tienda_id'], $componente['producto_elemento_id'], $cantidad_reintegrar]);
                        }
                        
                        // Registrar movimiento
                        $query_movimiento = "INSERT INTO movimientos_inventario (tipo, producto_id, tienda_destino_id, cantidad, motivo, referencia_id, referencia_tipo, usuario_id) 
                                           VALUES ('entrada', ?, ?, ?, 'Reembolso de venta', ?, 'devolucion', ?)";
                        $stmt_movimiento = $db->prepare($query_movimiento);
                        $stmt_movimiento->execute([$componente['producto_elemento_id'], $venta['tienda_id'], $cantidad_reintegrar, $venta_id, $_SESSION['usuario_id']]);
                    }
                } else {
                    // Si es elemento individual
                    $query_check = "SELECT id FROM inventarios WHERE tienda_id = ? AND producto_id = ?";
                    $stmt_check = $db->prepare($query_check);
                    $stmt_check->execute([$venta['tienda_id'], $detalle['producto_id']]);
                    
                    if ($stmt_check->rowCount() > 0) {
                        $query_update_inv = "UPDATE inventarios SET cantidad = cantidad + ? WHERE tienda_id = ? AND producto_id = ?";
                        $stmt_update_inv = $db->prepare($query_update_inv);
                        $stmt_update_inv->execute([$detalle['cantidad'], $venta['tienda_id'], $detalle['producto_id']]);
                    } else {
                        $query_insert_inv = "INSERT INTO inventarios (tienda_id, producto_id, cantidad, cantidad_reparacion) VALUES (?, ?, ?, 0)";
                        $stmt_insert_inv = $db->prepare($query_insert_inv);
                        $stmt_insert_inv->execute([$venta['tienda_id'], $detalle['producto_id'], $detalle['cantidad']]);
                    }
                    
                    // Registrar movimiento
                    $query_movimiento = "INSERT INTO movimientos_inventario (tipo, producto_id, tienda_destino_id, cantidad, motivo, referencia_id, referencia_tipo, usuario_id) 
                                       VALUES ('entrada', ?, ?, ?, 'Reembolso de venta', ?, 'devolucion', ?)";
                    $stmt_movimiento = $db->prepare($query_movimiento);
                    $stmt_movimiento->execute([$detalle['producto_id'], $venta['tienda_id'], $detalle['cantidad'], $venta_id, $_SESSION['usuario_id']]);
                }
            }
            
            // Marcar venta como reembolsada con razón
            $query_update_venta = "UPDATE ventas SET estado = 'reembolsada', fecha_reembolso = NOW(), razon_reembolso = ? WHERE id = ?";
            $stmt_update_venta = $db->prepare($query_update_venta);
            $stmt_update_venta->execute([$razon_reembolso, $venta_id]);
            
            $db->commit();
            $success = "Venta reembolsada exitosamente. Productos reintegrados al inventario.";
            
        } catch (Exception $e) {
            $db->rollBack();
            $error = "Error al procesar el reembolso: " . $e->getMessage();
        }
    }
}

// Obtener información de la venta
$query_venta = "SELECT v.*, t.nombre as tienda_nombre, u.nombre as vendedor_nombre 
                FROM ventas v 
                JOIN tiendas t ON v.tienda_id = t.id 
                JOIN usuarios u ON v.usuario_id = u.id 
                WHERE v.id = ?";
$stmt_venta = $db->prepare($query_venta);
$stmt_venta->execute([$venta_id]);
$venta = $stmt_venta->fetch(PDO::FETCH_ASSOC);

if (!$venta) {
    header('Location: ventas.php');
    exit();
}

// Obtener detalles de la venta
$query_detalles = "SELECT dv.*, p.nombre, p.codigo, p.tipo 
                   FROM detalle_ventas dv 
                   JOIN productos p ON dv.producto_id = p.id 
                   WHERE dv.venta_id = ?";
$stmt_detalles = $db->prepare($query_detalles);
$stmt_detalles->execute([$venta_id]);
$detalles = $stmt_detalles->fetchAll(PDO::FETCH_ASSOC);

require_once 'includes/layout_header.php';
?>

<div class="container-fluid py-4">
    <?php if (isset($success)): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-receipt"></i>
                        Detalle de Venta #<?php echo $venta['id']; ?>
                    </h5>
                    <span class="badge bg-<?php echo $venta['estado'] == 'pendiente' ? 'warning' : ($venta['estado'] == 'entregada' ? 'success' : 'danger'); ?> fs-6">
                        <?php echo ucfirst($venta['estado'] ?? 'pendiente'); ?>
                    </span>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6>Información de Venta</h6>
                            <p><strong>Fecha:</strong> <?php echo date('d/m/Y H:i', strtotime($venta['fecha'])); ?></p>
                            <p><strong>Tienda:</strong> <?php echo $venta['tienda_nombre']; ?></p>
                            <p><strong>Vendedor:</strong> <?php echo $venta['vendedor_nombre']; ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6>Estados</h6>
                            <?php if (isset($venta['fecha_entrega'])): ?>
                                <p><strong>Fecha Entrega:</strong> <?php echo date('d/m/Y H:i', strtotime($venta['fecha_entrega'])); ?></p>
                            <?php endif; ?>
                            <?php if (isset($venta['fecha_reembolso'])): ?>
                                <p><strong>Fecha Reembolso:</strong> <?php echo date('d/m/Y H:i', strtotime($venta['fecha_reembolso'])); ?></p>
                                <?php if ($venta['razon_reembolso']): ?>
                                    <p><strong>Razón del Reembolso:</strong> <?php echo htmlspecialchars($venta['razon_reembolso']); ?></p>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <h6>Productos Vendidos</h6>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Producto</th>
                                    <th>Tipo</th>
                                    <th>Cantidad</th>
                                    <th>Precio Unit.</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($detalles as $detalle): ?>
                                <tr>
                                    <td><?php echo $detalle['codigo']; ?></td>
                                    <td>
                                        <?php echo $detalle['nombre']; ?>
                                        <?php if ($detalle['tipo'] == 'conjunto'): ?>
                                            <br><small class="text-muted">
                                                <i class="fas fa-puzzle-piece"></i> Conjunto
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $detalle['tipo'] == 'conjunto' ? 'info' : 'secondary'; ?>">
                                            <?php echo ucfirst($detalle['tipo']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $detalle['cantidad']; ?></td>
                                    <td class="moneda"><?php echo number_format($detalle['precio_unitario'], 2); ?></td>
                                    <td class="moneda"><?php echo number_format($detalle['subtotal'], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-info">
                                    <th colspan="5" class="text-end">Total:</th>
                                    <th class="moneda"><?php echo number_format($venta['total'], 2); ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-cogs"></i>
                        Acciones
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (!isset($venta['estado']) || $venta['estado'] == 'pendiente'): ?>
                        <?php if (tienePermiso('ventas_actualizar')): ?>
                        <form method="POST" class="mb-3">
                            <input type="hidden" name="action" value="marcar_entregada">
                            <button type="submit" class="btn btn-success w-100" onclick="return confirm('¿Marcar esta venta como entregada?')">
                                <i class="fas fa-check"></i>
                                Marcar como Entregada
                            </button>
                        </form>
                        <?php endif; ?>

                        <?php if (tienePermiso('ventas_eliminar')): ?>
                        <button class="btn btn-danger w-100" onclick="abrirModalReembolso()">
                            <i class="fas fa-undo"></i>
                            Reembolsar Venta
                        </button>
                        <?php endif; ?>
                    <?php elseif ($venta['estado'] == 'entregada'): ?>
                        <div class="alert alert-success mb-0">
                            <i class="fas fa-check-circle"></i>
                            Venta entregada exitosamente
                        </div>
                    <?php elseif ($venta['estado'] == 'reembolsada'): ?>
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-undo"></i>
                            Venta reembolsada. Productos reintegrados al inventario.
                        </div>
                    <?php endif; ?>

                    <hr>
                    <a href="ventas.php" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-arrow-left"></i>
                        Volver a Ventas
                    </a>
                </div>
            </div>

            <?php if ($venta['estado'] == 'reembolsada'): ?>
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-info-circle"></i>
                        Información del Reembolso
                    </h6>
                </div>
                <div class="card-body">
                    <p class="small text-muted">
                        Cuando se reembolsa una venta, todos los productos se reintegran automáticamente al inventario de la tienda donde se realizó la venta original.
                    </p>
                    <p class="small text-muted">
                        Para conjuntos/kits, se reintegran los componentes individuales según las cantidades definidas.
                    </p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Reembolso -->
<div class="modal fade" id="modalReembolso" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-undo"></i>
                    Reembolsar Venta
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="reembolsar">
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>¡Atención!</strong> Esta acción reintegrará todos los productos al inventario y marcará la venta como reembolsada.
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-comment"></i>
                            Razón del Reembolso *
                        </label>
                        <select class="form-select" name="razon_tipo" onchange="toggleRazonPersonalizada(this)" required>
                            <option value="">Seleccionar razón...</option>
                            <option value="Producto defectuoso">Producto defectuoso</option>
                            <option value="No cumple expectativas">No cumple expectativas</option>
                            <option value="Entrega tardía">Entrega tardía</option>
                            <option value="Error en el pedido">Error en el pedido</option>
                            <option value="Solicitud del cliente">Solicitud del cliente</option>
                            <option value="Garantía">Garantía</option>
                            <option value="Personalizada">Otra razón</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-align-left"></i>
                            Descripción Detallada
                        </label>
                        <textarea class="form-control" name="razon_reembolso" rows="4" placeholder="Describir la razón específica del reembolso..." required></textarea>
                        <div class="form-text">Este campo será registrado para el historial de la venta.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i>
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-undo"></i>
                        Confirmar Reembolso
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function abrirModalReembolso() {
    new bootstrap.Modal(document.getElementById('modalReembolso')).show();
}

function toggleRazonPersonalizada(select) {
    const textarea = document.querySelector('textarea[name="razon_reembolso"]');
    if (select.value === 'Personalizada') {
        textarea.placeholder = 'Especificar razón personalizada del reembolso...';
    } else if (select.value) {
        textarea.placeholder = 'Describir detalles específicos: ' + select.value.toLowerCase();
    } else {
        textarea.placeholder = 'Describir la razón específica del reembolso...';
    }
}
</script>

<?php require_once 'includes/layout_footer.php'; ?>
