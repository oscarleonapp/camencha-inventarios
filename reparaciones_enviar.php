<?php
$titulo = "Enviar a Reparación - Sistema de Inventarios";
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'includes/config_functions.php';

verificarLogin();
verificarPermiso('reparaciones_crear');

$database = new Database();
$db = $database->getConnection();

$success = '';
$error = '';

if ($_POST && isset($_POST['action']) && $_POST['action'] == 'enviar_reparacion') {
    $tienda_id = (int)$_POST['tienda_id'];
    $producto_id = (int)$_POST['producto_id'];
    $cantidad = (int)$_POST['cantidad'];
    $descripcion_problema = trim($_POST['descripcion_problema']);
    $tecnico_proveedor = trim($_POST['tecnico_proveedor'] ?? '');
    $usuario_id = $_SESSION['usuario_id'];
    
    if ($cantidad <= 0) {
        $error = "La cantidad debe ser mayor a 0";
    } elseif (empty($descripcion_problema)) {
        $error = "La descripción del problema es obligatoria";
    } else {
        $db->beginTransaction();
        
        try {
            // Verificar stock disponible (cantidad normal - cantidad_reparacion)
            $query_check = "SELECT cantidad, cantidad_reparacion FROM inventarios WHERE tienda_id = ? AND producto_id = ?";
            $stmt_check = $db->prepare($query_check);
            $stmt_check->execute([$tienda_id, $producto_id]);
            $inventario = $stmt_check->fetch(PDO::FETCH_ASSOC);
            
            if (!$inventario) {
                throw new Exception("No existe inventario para este producto en esta tienda");
            }
            
            $stock_disponible = $inventario['cantidad'] - $inventario['cantidad_reparacion'];
            
            if ($stock_disponible < $cantidad) {
                throw new Exception("No hay suficiente stock disponible. Stock actual: $stock_disponible");
            }
            
            // Actualizar inventario: sumar a cantidad_reparacion
            $query_update_inventario = "UPDATE inventarios SET cantidad_reparacion = cantidad_reparacion + ? WHERE tienda_id = ? AND producto_id = ?";
            $stmt_update_inventario = $db->prepare($query_update_inventario);
            $stmt_update_inventario->execute([$cantidad, $tienda_id, $producto_id]);
            
            // Crear registro de reparación
            $query_reparacion = "INSERT INTO reparaciones (producto_id, tienda_id, cantidad, estado, descripcion_problema, tecnico_proveedor, usuario_envio_id, fecha_envio) 
                                VALUES (?, ?, ?, 'enviado', ?, ?, ?, NOW())";
            $stmt_reparacion = $db->prepare($query_reparacion);
            $stmt_reparacion->execute([$producto_id, $tienda_id, $cantidad, $descripcion_problema, $tecnico_proveedor, $usuario_id]);
            
            $reparacion_id = $db->lastInsertId();
            
            // Registrar movimiento de inventario
            $query_movimiento = "INSERT INTO movimientos_inventario (tipo, producto_id, tienda_origen_id, cantidad, motivo, referencia_id, referencia_tipo, usuario_id, fecha)
                                VALUES ('salida', ?, ?, ?, 'Envío a reparación', ?, 'reparacion', ?, NOW())";
            $stmt_movimiento = $db->prepare($query_movimiento);
            $stmt_movimiento->execute([$producto_id, $tienda_id, $cantidad, $reparacion_id, $usuario_id]);
            
            $db->commit();
            $success = "Producto enviado a reparación exitosamente";
            
            // Limpiar formulario
            $_POST = [];
            
        } catch (Exception $e) {
            $db->rollBack();
            $error = "Error al enviar a reparación: " . $e->getMessage();
        }
    }
}

// Obtener datos para formularios
$query_tiendas = "SELECT * FROM tiendas WHERE activo = 1 ORDER BY nombre";
$stmt_tiendas = $db->prepare($query_tiendas);
$stmt_tiendas->execute();
$tiendas = $stmt_tiendas->fetchAll(PDO::FETCH_ASSOC);

$query_productos = "SELECT * FROM productos WHERE activo = 1 ORDER BY nombre";
$stmt_productos = $db->prepare($query_productos);
$stmt_productos->execute();
$productos = $stmt_productos->fetchAll(PDO::FETCH_ASSOC);

// Obtener inventarios con stock disponible
$query_inventarios = "SELECT i.*, p.nombre as producto_nombre, p.codigo, t.nombre as tienda_nombre,
                      (i.cantidad - i.cantidad_reparacion) as stock_disponible
                      FROM inventarios i
                      JOIN productos p ON i.producto_id = p.id
                      JOIN tiendas t ON i.tienda_id = t.id
                      WHERE p.activo = 1 AND t.activo = 1 AND (i.cantidad - i.cantidad_reparacion) > 0
                      ORDER BY t.nombre, p.nombre";
$stmt_inventarios = $db->prepare($query_inventarios);
$stmt_inventarios->execute();
$inventarios = $stmt_inventarios->fetchAll(PDO::FETCH_ASSOC);

include 'includes/layout_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-tools"></i> Enviar a Reparación</h2>
    <div class="btn-group">
        <a href="reparaciones.php" class="btn btn-outline-secondary">
            <i class="fas fa-list"></i> Ver Reparaciones
        </a>
        <a href="reparaciones_recibir.php" class="btn btn-outline-success">
            <i class="fas fa-check-circle"></i> Recibir de Reparación
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

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-plus-circle"></i> Nuevo Envío a Reparación</h5>
            </div>
            <div class="card-body">
                <form method="POST" id="form-enviar-reparacion">
                    <input type="hidden" name="action" value="enviar_reparacion">
                    
                    <div class="mb-3">
                        <label for="tienda_id" class="form-label">Tienda</label>
                        <select class="form-select" id="tienda_id" name="tienda_id" required onchange="cargarProductosPorTienda()">
                            <option value="">Seleccionar tienda...</option>
                            <?php foreach ($tiendas as $tienda): ?>
                                <option value="<?php echo $tienda['id']; ?>" <?php echo (isset($_POST['tienda_id']) && $_POST['tienda_id'] == $tienda['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($tienda['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="producto_id" class="form-label">Producto</label>
                        <select class="form-select" id="producto_id" name="producto_id" required onchange="mostrarStockDisponible()">
                            <option value="">Seleccionar producto...</option>
                            <?php foreach ($productos as $producto): ?>
                                <option value="<?php echo $producto['id']; ?>" <?php echo (isset($_POST['producto_id']) && $_POST['producto_id'] == $producto['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($producto['codigo'] . ' - ' . $producto['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div id="stock-info" class="mb-3" style="display: none;">
                        <div class="alert alert-info">
                            <strong>Stock disponible: </strong><span id="stock-cantidad">0</span> unidades
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="cantidad" class="form-label">Cantidad a Enviar</label>
                        <input type="number" class="form-control" id="cantidad" name="cantidad" min="1" required 
                               value="<?php echo htmlspecialchars($_POST['cantidad'] ?? ''); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="descripcion_problema" class="form-label">Descripción del Problema</label>
                        <textarea class="form-control" id="descripcion_problema" name="descripcion_problema" rows="3" required 
                                  placeholder="Describe el problema o motivo del envío a reparación..."><?php echo htmlspecialchars($_POST['descripcion_problema'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="tecnico_proveedor" class="form-label">Técnico/Proveedor (Opcional)</label>
                        <input type="text" class="form-control" id="tecnico_proveedor" name="tecnico_proveedor" 
                               placeholder="Nombre del técnico o proveedor de reparación"
                               value="<?php echo htmlspecialchars($_POST['tecnico_proveedor'] ?? ''); ?>">
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Enviar a Reparación
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-warehouse"></i> Stock Disponible por Tienda</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Tienda</th>
                                <th>Código</th>
                                <th>Producto</th>
                                <th>Stock Disponible</th>
                                <th>En Reparación</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($inventarios as $inv): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($inv['tienda_nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($inv['codigo']); ?></td>
                                    <td><?php echo htmlspecialchars($inv['producto_nombre']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $inv['stock_disponible'] > 10 ? 'success' : ($inv['stock_disponible'] > 5 ? 'warning' : 'danger'); ?>">
                                            <?php echo $inv['stock_disponible']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($inv['cantidad_reparacion'] > 0): ?>
                                            <span class="badge bg-info"><?php echo $inv['cantidad_reparacion']; ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">0</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (empty($inventarios)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-info-circle fa-2x text-muted mb-2"></i>
                        <p class="text-muted">No hay productos disponibles para enviar a reparación</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Datos para JavaScript
const inventarios = <?php echo json_encode($inventarios); ?>;

function cargarProductosPorTienda() {
    mostrarStockDisponible();
}

function mostrarStockDisponible() {
    const tiendaId = document.getElementById('tienda_id').value;
    const productoId = document.getElementById('producto_id').value;
    const stockInfo = document.getElementById('stock-info');
    const stockCantidad = document.getElementById('stock-cantidad');
    const cantidadInput = document.getElementById('cantidad');
    
    if (tiendaId && productoId) {
        const inventario = inventarios.find(inv => 
            inv.tienda_id == tiendaId && inv.producto_id == productoId
        );
        
        if (inventario) {
            stockCantidad.textContent = inventario.stock_disponible;
            cantidadInput.max = inventario.stock_disponible;
            stockInfo.style.display = 'block';
            
            // Si la cantidad actual es mayor al stock disponible, ajustarla
            if (parseInt(cantidadInput.value) > inventario.stock_disponible) {
                cantidadInput.value = inventario.stock_disponible;
            }
        } else {
            stockCantidad.textContent = '0';
            cantidadInput.max = '0';
            stockInfo.style.display = 'block';
        }
    } else {
        stockInfo.style.display = 'none';
        cantidadInput.removeAttribute('max');
    }
}

// Validación del formulario
document.getElementById('form-enviar-reparacion').addEventListener('submit', function(e) {
    const tiendaId = document.getElementById('tienda_id').value;
    const productoId = document.getElementById('producto_id').value;
    const cantidad = parseInt(document.getElementById('cantidad').value);
    
    if (tiendaId && productoId) {
        const inventario = inventarios.find(inv => 
            inv.tienda_id == tiendaId && inv.producto_id == productoId
        );
        
        if (inventario && cantidad > inventario.stock_disponible) {
            e.preventDefault();
            showToast('La cantidad no puede ser mayor al stock disponible (' + inventario.stock_disponible + ')', 'error');
            return false;
        }
    }
});
</script>

<?php include 'includes/layout_footer.php'; ?>