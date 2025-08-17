<?php
$titulo = "Ingreso por Devolución - Inventarios";
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'includes/config_functions.php';
require_once 'includes/csrf_protection.php';

verificarLogin();
verificarPermiso('inventarios_ver');

$database = new Database();
$db = $database->getConnection();

if ($_POST && isset($_POST['action'])) {
    // Validar CSRF token
    validarCSRF();
    
    if ($_POST['action'] == 'ingreso_devolucion') {
        verificarPermiso('inventarios_actualizar');
        
        $tienda_id = $_POST['tienda_id'];
        $producto_id = $_POST['producto_id'];
        $cantidad_devolucion = (int)$_POST['cantidad_devolucion'];
        $motivo_devolucion = $_POST['motivo_devolucion'] ?? '';
        $referencia_venta = $_POST['referencia_venta'] ?? '';
        $usuario_id = $_SESSION['usuario_id'];
        
        if ($cantidad_devolucion <= 0) {
            $error = "La cantidad debe ser mayor a 0";
        } else {
            try {
                $db->beginTransaction();
                
                // Verificar si existe el inventario
                $query_check = "SELECT cantidad, COALESCE(cantidad_reparacion,0) AS cantidad_reparacion FROM inventarios WHERE tienda_id = ? AND producto_id = ?";
                $stmt_check = $db->prepare($query_check);
                $stmt_check->execute([$tienda_id, $producto_id]);
                $inventario_actual = $stmt_check->fetch(PDO::FETCH_ASSOC);
                
                if ($inventario_actual) {
                    // Actualizar inventario existente (sumar al total)
                    $nueva_cantidad_total = (int)$inventario_actual['cantidad'] + $cantidad_devolucion;
                    $query_update = "UPDATE inventarios SET cantidad = ? WHERE tienda_id = ? AND producto_id = ?";
                    $stmt_update = $db->prepare($query_update);
                    $stmt_update->execute([$nueva_cantidad_total, $tienda_id, $producto_id]);
                } else {
                    // Crear nuevo registro de inventario
                    $query_insert = "INSERT INTO inventarios (tienda_id, producto_id, cantidad, cantidad_reparacion) VALUES (?, ?, ?, 0)";
                    $stmt_insert = $db->prepare($query_insert);
                    $stmt_insert->execute([$tienda_id, $producto_id, $cantidad_devolucion]);
                }
                
                // Registrar movimiento de inventario alineado al esquema
                $query_movimiento = "INSERT INTO movimientos_inventario 
                                    (tipo, producto_id, tienda_destino_id, cantidad, motivo, referencia_id, referencia_tipo, usuario_id, notas) 
                                    VALUES ('devolucion', ?, ?, ?, ?, ?, 'devolucion', ?, ?)";
                $stmt_movimiento = $db->prepare($query_movimiento);
                $notas = "Ingreso por devolución - " . $motivo_devolucion;
                $ref_id = is_numeric($referencia_venta) ? (int)$referencia_venta : null;
                $stmt_movimiento->execute([
                    $producto_id,
                    $tienda_id,
                    $cantidad_devolucion,
                    $notas,
                    $ref_id,
                    $usuario_id,
                    $notas
                ]);
                
                $db->commit();
                $success = "Devolución registrada exitosamente. Se agregaron $cantidad_devolucion unidades al inventario.";
                
            } catch (Exception $e) {
                $db->rollBack();
                $error = "Error al procesar la devolución: " . $e->getMessage();
            }
        }
    }
}

// Datos para selects
$query_tiendas = "SELECT * FROM tiendas WHERE activo = 1 ORDER BY nombre";
$stmt_tiendas = $db->prepare($query_tiendas);
$stmt_tiendas->execute();
$tiendas = $stmt_tiendas->fetchAll(PDO::FETCH_ASSOC);

$query_productos = "SELECT * FROM productos WHERE activo = 1 ORDER BY nombre";
$stmt_productos = $db->prepare($query_productos);
$stmt_productos->execute();
$productos = $stmt_productos->fetchAll(PDO::FETCH_ASSOC);

include 'includes/layout_header.php';
?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-undo"></i> <span class="editable" data-label="ingreso_dev_titulo">Ingreso por Devolución</span></h2>
    </div>

    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card border-info">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-undo"></i>
                        Registrar devolución al inventario
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="ingreso_devolucion">
                        <?php echo campoCSRF(); ?>
                        
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-store"></i>
                                Tienda
                            </label>
                            <select class="form-control" name="tienda_id" required>
                                <option value="">Seleccionar tienda...</option>
                                <?php foreach ($tiendas as $tienda): ?>
                                    <option value="<?php echo $tienda['id']; ?>"><?php echo htmlspecialchars($tienda['nombre']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-box"></i>
                                Producto
                            </label>
                            <select class="form-control" name="producto_id" required>
                                <option value="">Seleccionar producto...</option>
                                <?php foreach ($productos as $producto): ?>
                                    <option value="<?php echo $producto['id']; ?>">
                                        [<?php echo $producto['codigo']; ?>] <?php echo $producto['nombre']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-sort-numeric-up"></i>
                                Cantidad devuelta
                            </label>
                            <input type="number" class="form-control" name="cantidad_devolucion" min="1" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-comment-dots"></i>
                                Motivo de la devolución
                            </label>
                            <select class="form-control" name="motivo_devolucion" required>
                                <option value="">Seleccionar motivo...</option>
                                <option value="Producto defectuoso">Producto defectuoso</option>
                                <option value="Cliente no satisfecho">Cliente no satisfecho</option>
                                <option value="Error en la venta">Error en la venta</option>
                                <option value="Otro">Otro</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-receipt"></i>
                                Referencia de venta (opcional)
                            </label>
                            <input type="text" class="form-control" name="referencia_venta" placeholder="Ej.: #F-10023">
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-save"></i>
                                Registrar Devolución
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

<?php include 'includes/layout_footer.php'; ?>
