<?php
$titulo = "Traslados - Sistema de Inventarios";
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'includes/config_functions.php';
require_once 'includes/csrf_protection.php';

verificarLogin();
verificarPermiso('inventarios_transferir');

$database = new Database();
$db = $database->getConnection();

// Función para generar número de orden
function generarNumeroOrden($db) {
    $año = date('Y');
    $query = "SELECT MAX(CAST(SUBSTRING(numero_orden, 5) AS UNSIGNED)) as ultimo_numero 
              FROM ordenes_compra_internas 
              WHERE numero_orden LIKE 'OC{$año}%'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    $siguiente = ($resultado['ultimo_numero'] ?? 0) + 1;
    return 'OC' . $año . str_pad($siguiente, 4, '0', STR_PAD_LEFT);
}

// Función para generar número de traslado
function generarNumeroTraslado($db) {
    $año = date('Y');
    $query = "SELECT MAX(CAST(SUBSTRING(numero_traslado, 5) AS UNSIGNED)) as ultimo_numero 
              FROM traslados 
              WHERE numero_traslado LIKE 'TR{$año}%'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    $siguiente = ($resultado['ultimo_numero'] ?? 0) + 1;
    return 'TR' . $año . str_pad($siguiente, 4, '0', STR_PAD_LEFT);
}

if ($_POST && isset($_POST['action'])) {
    validarCSRF();
    
    if ($_POST['action'] == 'crear_orden_compra') {
        $tienda_solicitante = $_SESSION['tienda_id'] ?? $_POST['tienda_solicitante']; // Si el usuario tiene tienda asignada
        $tienda_proveedora = $_POST['tienda_proveedora'];
        $numero_talonario = trim($_POST['numero_talonario']);
        $motivo_solicitud = trim($_POST['motivo_solicitud']);
        $observaciones = trim($_POST['observaciones'] ?? '');
        $productos = $_POST['productos'] ?? [];
        $cantidades = $_POST['cantidades'] ?? [];
        $usuario_id = $_SESSION['usuario_id'];
        
        if (empty($numero_talonario)) {
            $error = "El número de talonario es obligatorio";
        } elseif ($tienda_solicitante == $tienda_proveedora) {
            $error = "La tienda solicitante y proveedora no pueden ser la misma";
        } elseif (empty($productos) || empty($cantidades)) {
            $error = "Debe agregar al menos un producto a la orden";
        } else {
            try {
                $db->beginTransaction();
                
                // Verificar que el talonario no esté duplicado
                $query_check = "SELECT id FROM ordenes_compra_internas WHERE numero_talonario = ?";
                $stmt_check = $db->prepare($query_check);
                $stmt_check->execute([$numero_talonario]);
                if ($stmt_check->rowCount() > 0) {
                    throw new Exception("El número de talonario ya existe");
                }
                
                $numero_orden = generarNumeroOrden($db);
                
                // Crear orden de compra
                $query_orden = "INSERT INTO ordenes_compra_internas 
                               (numero_orden, numero_talonario, tienda_solicitante_id, tienda_proveedora_id, 
                                usuario_solicitante_id, motivo_solicitud, observaciones, total_productos) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt_orden = $db->prepare($query_orden);
                $total_productos = count($productos);
                $stmt_orden->execute([
                    $numero_orden, $numero_talonario, $tienda_solicitante, $tienda_proveedora,
                    $usuario_id, $motivo_solicitud, $observaciones, $total_productos
                ]);
                
                $orden_id = $db->lastInsertId();
                
                // Agregar detalles de productos
                $query_detalle = "INSERT INTO detalle_ordenes_compra_internas 
                                 (orden_compra_id, producto_id, cantidad_solicitada) 
                                 VALUES (?, ?, ?)";
                $stmt_detalle = $db->prepare($query_detalle);
                
                for ($i = 0; $i < count($productos); $i++) {
                    $producto_id = $productos[$i];
                    $cantidad = (int)$cantidades[$i];
                    if ($cantidad > 0) {
                        $stmt_detalle->execute([$orden_id, $producto_id, $cantidad]);
                    }
                }
                
                $db->commit();
                $success = "Orden de compra #{$numero_orden} creada exitosamente con talonario #{$numero_talonario}";
                
            } catch (Exception $e) {
                $db->rollBack();
                $error = "Error al crear orden de compra: " . $e->getMessage();
            }
        }
    }
    
    if ($_POST['action'] == 'aprobar_orden') {
        $orden_id = (int)$_POST['orden_id'];
        $productos_aprobados = $_POST['productos_aprobados'] ?? [];
        $cantidades_aprobadas = $_POST['cantidades_aprobadas'] ?? [];
        $usuario_id = $_SESSION['usuario_id'];
        
        try {
            $db->beginTransaction();
            
            // Verificar que la orden existe y está pendiente
            $query_orden = "SELECT * FROM ordenes_compra_internas WHERE id = ? AND estado = 'pendiente'";
            $stmt_orden = $db->prepare($query_orden);
            $stmt_orden->execute([$orden_id]);
            $orden = $stmt_orden->fetch(PDO::FETCH_ASSOC);
            
            if (!$orden) {
                throw new Exception("Orden no encontrada o ya procesada");
            }
            
            // Verificar stock disponible
            foreach ($productos_aprobados as $i => $producto_id) {
                $cantidad_aprobada = (int)$cantidades_aprobadas[$i];
                if ($cantidad_aprobada > 0) {
                    $query_stock = "SELECT cantidad, COALESCE(cantidad_reparacion,0) AS cantidad_reparacion 
                                   FROM inventarios 
                                   WHERE tienda_id = ? AND producto_id = ?";
                    $stmt_stock = $db->prepare($query_stock);
                    $stmt_stock->execute([$orden['tienda_proveedora_id'], $producto_id]);
                    $stock = $stmt_stock->fetch(PDO::FETCH_ASSOC);
                    $disponible = $stock ? ((int)$stock['cantidad'] - (int)$stock['cantidad_reparacion']) : 0;
                    
                    if ($disponible < $cantidad_aprobada) {
                        $query_producto = "SELECT nombre FROM productos WHERE id = ?";
                        $stmt_producto = $db->prepare($query_producto);
                        $stmt_producto->execute([$producto_id]);
                        $producto_nombre = $stmt_producto->fetchColumn();
                        throw new Exception("Stock insuficiente para {$producto_nombre}. Disponible: {$disponible}, Solicitado: {$cantidad_aprobada}");
                    }
                    
                    // Actualizar cantidad aprobada
                    $query_update = "UPDATE detalle_ordenes_compra_internas 
                                    SET cantidad_aprobada = ? 
                                    WHERE orden_compra_id = ? AND producto_id = ?";
                    $stmt_update = $db->prepare($query_update);
                    $stmt_update->execute([$cantidad_aprobada, $orden_id, $producto_id]);
                }
            }
            
            // Aprobar orden
            $query_aprobar = "UPDATE ordenes_compra_internas 
                             SET estado = 'aprobada', fecha_aprobacion = NOW(), usuario_aprobador_id = ? 
                             WHERE id = ?";
            $stmt_aprobar = $db->prepare($query_aprobar);
            $stmt_aprobar->execute([$usuario_id, $orden_id]);
            
            $db->commit();
            $success = "Orden de compra #{$orden['numero_orden']} aprobada exitosamente";
            
        } catch (Exception $e) {
            $db->rollBack();
            $error = "Error al aprobar orden: " . $e->getMessage();
        }
    }
    
    if ($_POST['action'] == 'rechazar_orden') {
        $orden_id = (int)$_POST['orden_id'];
        $motivo_rechazo = trim($_POST['motivo_rechazo']);
        $usuario_id = $_SESSION['usuario_id'];
        
        if (empty($motivo_rechazo)) {
            $error = "Debe especificar el motivo del rechazo";
        } else {
            try {
                $query_rechazar = "UPDATE ordenes_compra_internas 
                                  SET estado = 'rechazada', motivo_rechazo = ?, usuario_aprobador_id = ? 
                                  WHERE id = ? AND estado = 'pendiente'";
                $stmt_rechazar = $db->prepare($query_rechazar);
                $stmt_rechazar->execute([$motivo_rechazo, $usuario_id, $orden_id]);
                
                if ($stmt_rechazar->rowCount() > 0) {
                    $success = "Orden de compra rechazada";
                } else {
                    $error = "No se pudo rechazar la orden";
                }
            } catch (Exception $e) {
                $error = "Error al rechazar orden: " . $e->getMessage();
            }
        }
    }
    
    if ($_POST['action'] == 'procesar_traslado') {
        $orden_id = (int)$_POST['orden_id'];
        $usuario_id = $_SESSION['usuario_id'];
        
        try {
            $db->beginTransaction();
            
            // Verificar orden aprobada
            $query_orden = "SELECT * FROM ordenes_compra_internas WHERE id = ? AND estado = 'aprobada'";
            $stmt_orden = $db->prepare($query_orden);
            $stmt_orden->execute([$orden_id]);
            $orden = $stmt_orden->fetch(PDO::FETCH_ASSOC);
            
            if (!$orden) {
                throw new Exception("Orden no encontrada o no aprobada");
            }
            
            // Obtener productos aprobados
            $query_productos = "SELECT doc.*, p.nombre as producto_nombre 
                               FROM detalle_ordenes_compra_internas doc
                               JOIN productos p ON doc.producto_id = p.id
                               WHERE doc.orden_compra_id = ? AND doc.cantidad_aprobada > 0";
            $stmt_productos = $db->prepare($query_productos);
            $stmt_productos->execute([$orden_id]);
            $productos_orden = $stmt_productos->fetchAll(PDO::FETCH_ASSOC);
            
            // Crear traslado
            $numero_traslado = generarNumeroTraslado($db);
            $query_traslado = "INSERT INTO traslados 
                              (orden_compra_id, numero_traslado, tienda_origen_id, tienda_destino_id, 
                               usuario_envio_id, fecha_envio, estado) 
                              VALUES (?, ?, ?, ?, ?, NOW(), 'en_transito')";
            $stmt_traslado = $db->prepare($query_traslado);
            $stmt_traslado->execute([
                $orden_id, $numero_traslado, $orden['tienda_proveedora_id'], 
                $orden['tienda_solicitante_id'], $usuario_id
            ]);
            
            // Procesar inventarios
            foreach ($productos_orden as $producto) {
                $cantidad = $producto['cantidad_aprobada'];
                
                // Restar de inventario origen
                $query_restar = "UPDATE inventarios 
                                SET cantidad = cantidad - ? 
                                WHERE tienda_id = ? AND producto_id = ?";
                $stmt_restar = $db->prepare($query_restar);
                $stmt_restar->execute([$cantidad, $orden['tienda_proveedora_id'], $producto['producto_id']]);
                
                // Sumar a inventario destino
                $query_check_destino = "SELECT id FROM inventarios 
                                       WHERE tienda_id = ? AND producto_id = ?";
                $stmt_check_destino = $db->prepare($query_check_destino);
                $stmt_check_destino->execute([$orden['tienda_solicitante_id'], $producto['producto_id']]);
                
                if ($stmt_check_destino->rowCount() > 0) {
                    $query_sumar = "UPDATE inventarios 
                                   SET cantidad = cantidad + ? 
                                   WHERE tienda_id = ? AND producto_id = ?";
                    $stmt_sumar = $db->prepare($query_sumar);
                    $stmt_sumar->execute([$cantidad, $orden['tienda_solicitante_id'], $producto['producto_id']]);
                } else {
                    $query_crear = "INSERT INTO inventarios (tienda_id, producto_id, cantidad) 
                                   VALUES (?, ?, ?)";
                    $stmt_crear = $db->prepare($query_crear);
                    $stmt_crear->execute([$orden['tienda_solicitante_id'], $producto['producto_id'], $cantidad]);
                }
                
                // Registrar movimiento
                $query_movimiento = "INSERT INTO movimientos_inventario 
                                    (tipo_movimiento, producto_id, tienda_id, tienda_origen_id, tienda_destino_id, 
                                     cantidad, motivo, referencia_id, referencia_tipo, usuario_id) 
                                    VALUES ('transferencia', ?, ?, ?, ?, ?, ?, ?, 'orden_compra', ?)";
                $stmt_movimiento = $db->prepare($query_movimiento);
                $motivo = "Traslado por orden #{$orden['numero_orden']} - Talonario #{$orden['numero_talonario']}";
                $stmt_movimiento->execute([
                    $producto['producto_id'], $orden['tienda_proveedora_id'], 
                    $orden['tienda_proveedora_id'], $orden['tienda_solicitante_id'],
                    $cantidad, $motivo, $orden_id, $usuario_id
                ]);
                
                // Actualizar cantidad transferida
                $query_actualizar = "UPDATE detalle_ordenes_compra_internas 
                                    SET cantidad_transferida = ? 
                                    WHERE id = ?";
                $stmt_actualizar = $db->prepare($query_actualizar);
                $stmt_actualizar->execute([$cantidad, $producto['id']]);
            }
            
            // Marcar orden como en tránsito
            $query_update_orden = "UPDATE ordenes_compra_internas 
                                  SET estado = 'en_transito' 
                                  WHERE id = ?";
            $stmt_update_orden = $db->prepare($query_update_orden);
            $stmt_update_orden->execute([$orden_id]);
            
            $db->commit();
            $success = "Traslado #{$numero_traslado} procesado exitosamente para orden #{$orden['numero_orden']}";
            
        } catch (Exception $e) {
            $db->rollBack();
            $error = "Error al procesar traslado: " . $e->getMessage();
        }
    }
}

// Obtener tiendas
$query_tiendas = "SELECT * FROM tiendas WHERE activo = 1 ORDER BY nombre";
$stmt_tiendas = $db->prepare($query_tiendas);
$stmt_tiendas->execute();
$tiendas = $stmt_tiendas->fetchAll(PDO::FETCH_ASSOC);

// Obtener productos activos
$query_productos = "SELECT * FROM productos WHERE activo = 1 ORDER BY nombre";
$stmt_productos = $db->prepare($query_productos);
$stmt_productos->execute();
$productos = $stmt_productos->fetchAll(PDO::FETCH_ASSOC);

// Obtener órdenes de compra según el rol del usuario
$where_clause = "";
$params = [];

// Si el usuario tiene tienda asignada, filtrar por sus órdenes
if (isset($_SESSION['tienda_id'])) {
    $where_clause = "WHERE (oc.tienda_solicitante_id = ? OR oc.tienda_proveedora_id = ?)";
    $params = [$_SESSION['tienda_id'], $_SESSION['tienda_id']];
}

$query_ordenes = "SELECT oc.*, 
                         ts.nombre as tienda_solicitante_nombre,
                         tp.nombre as tienda_proveedora_nombre,
                         us.nombre as usuario_solicitante_nombre,
                         ua.nombre as usuario_aprobador_nombre
                  FROM ordenes_compra_internas oc
                  JOIN tiendas ts ON oc.tienda_solicitante_id = ts.id
                  JOIN tiendas tp ON oc.tienda_proveedora_id = tp.id
                  JOIN usuarios us ON oc.usuario_solicitante_id = us.id
                  LEFT JOIN usuarios ua ON oc.usuario_aprobador_id = ua.id
                  {$where_clause}
                  ORDER BY oc.created_at DESC
                  LIMIT 50";
$stmt_ordenes = $db->prepare($query_ordenes);
$stmt_ordenes->execute($params);
$ordenes = $stmt_ordenes->fetchAll(PDO::FETCH_ASSOC);

include 'includes/layout_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 rs-wrap-sm">
    <h2><i class="fas fa-truck"></i> Sistema de Traslados por Orden de Compra</h2>
    <div class="btn-group rs-wrap-sm">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearOrden">
            <i class="fas fa-plus"></i> Nueva Orden de Compra
        </button>
    </div>
</div>

<!-- Mensajes -->
<?php if (isset($success)): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            showToast('<?php echo addslashes($success); ?>', 'success');
        });
    </script>
<?php endif; ?>
<?php if (isset($error)): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            showToast('<?php echo addslashes($error); ?>', 'danger');
        });
    </script>
<?php endif; ?>

<!-- Panel de órdenes de compra -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-clipboard-list"></i> Órdenes de Compra Internas</h5>
            </div>
            <div class="card-body">
                <?php if (empty($ordenes)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No hay órdenes de compra registradas</p>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearOrden">
                            <i class="fas fa-plus"></i> Crear primera orden
                        </button>
                    </div>
                <?php else: ?>
                    <div class="table-responsive-md">
                        <table class="table table-striped">
                            <thead class="thead-titulos">
                                <tr>
                                    <th>Orden #</th>
                                    <th>Talonario #</th>
                                    <th>Tiendas</th>
                                    <th>Estado</th>
                                    <th>Fecha</th>
                                    <th>Productos</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ordenes as $orden): ?>
                                <tr>
                                    <td><strong><?php echo $orden['numero_orden']; ?></strong></td>
                                    <td><code><?php echo $orden['numero_talonario']; ?></code></td>
                                    <td>
                                        <small>
                                            <strong>De:</strong> <?php echo $orden['tienda_solicitante_nombre']; ?><br>
                                            <strong>Para:</strong> <?php echo $orden['tienda_proveedora_nombre']; ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php
                                        $badge_class = [
                                            'pendiente' => 'warning',
                                            'aprobada' => 'success', 
                                            'rechazada' => 'danger',
                                            'en_transito' => 'info',
                                            'completada' => 'primary'
                                        ];
                                        ?>
                                        <span class="badge bg-<?php echo $badge_class[$orden['estado']]; ?>">
                                            <?php echo ucfirst($orden['estado']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small>
                                            <?php echo date('d/m/Y H:i', strtotime($orden['fecha_solicitud'])); ?>
                                            <br><span class="text-muted"><?php echo $orden['usuario_solicitante_nombre']; ?></span>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?php echo $orden['total_productos']; ?></span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-info" onclick="verDetalleOrden(<?php echo $orden['id']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php if ($orden['estado'] == 'pendiente'): ?>
                                                <button class="btn btn-outline-success" onclick="aprobarOrden(<?php echo $orden['id']; ?>)">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button class="btn btn-outline-danger" onclick="rechazarOrden(<?php echo $orden['id']; ?>)">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            <?php elseif ($orden['estado'] == 'aprobada'): ?>
                                                <button class="btn btn-outline-primary" onclick="procesarTraslado(<?php echo $orden['id']; ?>)">
                                                    <i class="fas fa-shipping-fast"></i> Trasladar
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
</div>

<!-- Modal Crear Orden de Compra -->
<div class="modal fade" id="modalCrearOrden" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="crear_orden_compra">
                <?php echo campoCSRF(); ?>
                
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus"></i> Nueva Orden de Compra Interna</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Proceso:</strong> La tienda que necesita productos debe crear una orden de compra dirigida a otra tienda. La tienda proveedora debe aprobar la orden antes de procesar el traslado.
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Tienda Solicitante</label>
                                <select class="form-select" name="tienda_solicitante" required>
                                    <option value="">Seleccionar...</option>
                                    <?php foreach ($tiendas as $tienda): ?>
                                        <option value="<?php echo $tienda['id']; ?>"><?php echo $tienda['nombre']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Tienda Proveedora</label>
                                <select class="form-select" name="tienda_proveedora" required>
                                    <option value="">Seleccionar...</option>
                                    <?php foreach ($tiendas as $tienda): ?>
                                        <option value="<?php echo $tienda['id']; ?>"><?php echo $tienda['nombre']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-receipt"></i>
                            Número de Talonario Físico *
                        </label>
                        <input type="text" class="form-control" name="numero_talonario" required 
                               placeholder="Ej: 001-001-000123">
                        <div class="form-text">
                            Número del talonario físico que se usará para registrar esta orden
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Motivo de la Solicitud</label>
                        <textarea class="form-control" name="motivo_solicitud" rows="2" required 
                                  placeholder="Ej: Reposición de stock por alta demanda"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Productos Solicitados</label>
                        <div id="productosContainer">
                            <div class="producto-item border rounded p-3 mb-2">
                                <div class="row">
                                    <div class="col-md-8">
                                        <select class="form-select" name="productos[]" required>
                                            <option value="">Seleccionar producto...</option>
                                            <?php foreach ($productos as $producto): ?>
                                                <option value="<?php echo $producto['id']; ?>">
                                                    [<?php echo $producto['codigo']; ?>] <?php echo $producto['nombre']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <input type="number" class="form-control" name="cantidades[]" min="1" 
                                               placeholder="Cantidad" required>
                                    </div>
                                    <div class="col-md-1">
                                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="eliminarProducto(this)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="agregarProducto()">
                            <i class="fas fa-plus"></i> Agregar Producto
                        </button>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Observaciones Adicionales</label>
                        <textarea class="form-control" name="observaciones" rows="2" 
                                  placeholder="Observaciones adicionales..."></textarea>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Crear Orden de Compra
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Ver Detalle de Orden -->
<div class="modal fade" id="modalDetalleOrden" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-eye"></i> Detalle de Orden de Compra</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detalleOrdenContent">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Aprobar Orden -->
<div class="modal fade" id="modalAprobarOrden" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" id="formAprobarOrden">
                <input type="hidden" name="action" value="aprobar_orden">
                <input type="hidden" name="orden_id" id="aprobarOrdenId">
                <?php echo campoCSRF(); ?>
                
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-check"></i> Aprobar Orden de Compra</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body" id="aprobarOrdenContent">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success" id="btnConfirmarAprobacion">
                        <i class="fas fa-check"></i> Aprobar Orden
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function agregarProducto() {
    const container = document.getElementById('productosContainer');
    const template = container.querySelector('.producto-item').cloneNode(true);
    
    // Limpiar valores
    template.querySelectorAll('select, input').forEach(input => input.value = '');
    
    container.appendChild(template);
}

function eliminarProducto(btn) {
    const container = document.getElementById('productosContainer');
    if (container.children.length > 1) {
        btn.closest('.producto-item').remove();
    } else {
        alert('Debe mantener al menos un producto en la orden');
    }
}

function verDetalleOrden(ordenId) {
    const modal = new bootstrap.Modal(document.getElementById('modalDetalleOrden'));
    const content = document.getElementById('detalleOrdenContent');
    
    // Mostrar spinner
    content.innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
        </div>
    `;
    
    modal.show();
    
    // Cargar datos
    fetch('ajax/get_orden_detalle.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ orden_id: ordenId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            content.innerHTML = generarHTMLDetalleOrden(data.orden, data.productos, data.traslado);
        } else {
            content.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> ${data.message}
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        content.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> Error al cargar el detalle de la orden
            </div>
        `;
    });
}

function aprobarOrden(ordenId) {
    const modal = new bootstrap.Modal(document.getElementById('modalAprobarOrden'));
    const content = document.getElementById('aprobarOrdenContent');
    document.getElementById('aprobarOrdenId').value = ordenId;
    
    // Mostrar spinner
    content.innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
        </div>
    `;
    
    modal.show();
    
    // Cargar datos para aprobación
    fetch('ajax/get_orden_detalle.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ orden_id: ordenId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            content.innerHTML = generarHTMLAprobacionOrden(data.orden, data.productos);
        } else {
            content.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> ${data.message}
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        content.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> Error al cargar datos para aprobación
            </div>
        `;
    });
}

function generarHTMLDetalleOrden(orden, productos, traslado) {
    const estadoBadges = {
        'pendiente': 'warning',
        'aprobada': 'success',
        'rechazada': 'danger',
        'en_transito': 'info',
        'completada': 'primary'
    };
    
    let html = `
        <div class="row">
            <div class="col-md-6">
                <h6 class="text-muted">INFORMACIÓN GENERAL</h6>
                <table class="table table-sm">
                    <tr><td><strong>Orden #:</strong></td><td>${orden.numero_orden}</td></tr>
                    <tr><td><strong>Talonario #:</strong></td><td><code>${orden.numero_talonario}</code></td></tr>
                    <tr><td><strong>Estado:</strong></td><td><span class="badge bg-${estadoBadges[orden.estado]}">${orden.estado.charAt(0).toUpperCase() + orden.estado.slice(1)}</span></td></tr>
                    <tr><td><strong>Fecha Solicitud:</strong></td><td>${new Date(orden.fecha_solicitud).toLocaleString()}</td></tr>
                    ${orden.fecha_aprobacion ? `<tr><td><strong>Fecha Aprobación:</strong></td><td>${new Date(orden.fecha_aprobacion).toLocaleString()}</td></tr>` : ''}
                </table>
            </div>
            <div class="col-md-6">
                <h6 class="text-muted">TIENDAS INVOLUCRADAS</h6>
                <div class="mb-3">
                    <strong>Tienda Solicitante:</strong><br>
                    <i class="fas fa-store text-warning"></i> ${orden.tienda_solicitante_nombre}<br>
                    <small class="text-muted">${orden.tienda_solicitante_direccion || 'Sin dirección'}</small>
                </div>
                <div class="mb-3">
                    <strong>Tienda Proveedora:</strong><br>
                    <i class="fas fa-store text-success"></i> ${orden.tienda_proveedora_nombre}<br>
                    <small class="text-muted">${orden.tienda_proveedora_direccion || 'Sin dirección'}</small>
                </div>
            </div>
        </div>
        
        <hr>
        
        <div class="row">
            <div class="col-md-6">
                <h6 class="text-muted">MOTIVO DE SOLICITUD</h6>
                <p class="bg-light p-2 rounded">${orden.motivo_solicitud}</p>
                ${orden.observaciones ? `
                <h6 class="text-muted">OBSERVACIONES</h6>
                <p class="bg-light p-2 rounded">${orden.observaciones}</p>
                ` : ''}
            </div>
            <div class="col-md-6">
                <h6 class="text-muted">USUARIOS</h6>
                <p><strong>Solicitado por:</strong> ${orden.usuario_solicitante_nombre}</p>
                ${orden.usuario_aprobador_nombre ? `<p><strong>Aprobado por:</strong> ${orden.usuario_aprobador_nombre}</p>` : ''}
                ${orden.motivo_rechazo ? `
                <div class="alert alert-danger">
                    <strong>Motivo de Rechazo:</strong><br>
                    ${orden.motivo_rechazo}
                </div>
                ` : ''}
            </div>
        </div>
        
        <hr>
        
        <h6 class="text-muted">PRODUCTOS SOLICITADOS</h6>
        <div class="table-responsive-md">
            <table class="table table-sm table-striped">
                <thead class="thead-titulos">
                    <tr>
                        <th>Código</th>
                        <th>Producto</th>
                        <th>Tipo</th>
                        <th>Cantidad Solicitada</th>
                        ${orden.estado !== 'pendiente' ? '<th>Cantidad Aprobada</th>' : ''}
                        ${orden.estado === 'completada' ? '<th>Cantidad Transferida</th>' : ''}
                        <th>Stock Disponible</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    productos.forEach(producto => {
        const stockClass = producto.stock_disponible_real >= producto.cantidad_solicitada ? 'text-success' : 'text-danger';
        html += `
            <tr>
                <td><code>${producto.producto_codigo}</code></td>
                <td>${producto.producto_nombre}</td>
                <td><span class="badge bg-${producto.producto_tipo === 'elemento' ? 'primary' : 'success'}">${producto.producto_tipo}</span></td>
                <td><strong>${producto.cantidad_solicitada}</strong></td>
                ${orden.estado !== 'pendiente' ? `<td><strong class="text-success">${producto.cantidad_aprobada}</strong></td>` : ''}
                ${orden.estado === 'completada' ? `<td><strong class="text-info">${producto.cantidad_transferida}</strong></td>` : ''}
                <td><span class="${stockClass}">${producto.stock_disponible_real}</span></td>
            </tr>
        `;
    });
    
    html += `
                </tbody>
            </table>
        </div>
    `;
    
    // Información del traslado si existe
    if (traslado) {
        html += `
            <hr>
            <h6 class="text-muted">INFORMACIÓN DEL TRASLADO</h6>
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Número de Traslado:</strong> <code>${traslado.numero_traslado}</code></p>
                    <p><strong>Estado:</strong> <span class="badge bg-info">${traslado.estado}</span></p>
                    ${traslado.fecha_envio ? `<p><strong>Fecha de Envío:</strong> ${new Date(traslado.fecha_envio).toLocaleString()}</p>` : ''}
                    ${traslado.fecha_entrega ? `<p><strong>Fecha de Entrega:</strong> ${new Date(traslado.fecha_entrega).toLocaleString()}</p>` : ''}
                </div>
                <div class="col-md-6">
                    ${traslado.usuario_envio_nombre ? `<p><strong>Enviado por:</strong> ${traslado.usuario_envio_nombre}</p>` : ''}
                    ${traslado.usuario_recepcion_nombre ? `<p><strong>Recibido por:</strong> ${traslado.usuario_recepcion_nombre}</p>` : ''}
                    ${traslado.observaciones_envio ? `<p><strong>Obs. Envío:</strong> ${traslado.observaciones_envio}</p>` : ''}
                    ${traslado.observaciones_recepcion ? `<p><strong>Obs. Recepción:</strong> ${traslado.observaciones_recepcion}</p>` : ''}
                </div>
            </div>
        `;
    }
    
    return html;
}

function generarHTMLAprobacionOrden(orden, productos) {
    let html = `
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            <strong>Orden:</strong> ${orden.numero_orden} | <strong>Talonario:</strong> ${orden.numero_talonario}<br>
            <strong>De:</strong> ${orden.tienda_solicitante_nombre} → <strong>Para:</strong> ${orden.tienda_proveedora_nombre}
        </div>
        
        <h6>Productos a Aprobar:</h6>
        <div class="table-responsive-md">
            <table class="table table-sm">
                <thead class="table-light">
                    <tr>
                        <th>Producto</th>
                        <th>Solicitado</th>
                        <th>Stock Disponible</th>
                        <th>Cantidad a Aprobar</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    productos.forEach((producto, index) => {
        const stockClass = producto.stock_disponible_real >= producto.cantidad_solicitada ? 'text-success' : 'text-danger';
        const maxAprobable = Math.min(producto.cantidad_solicitada, producto.stock_disponible_real);
        
        html += `
            <tr>
                <td>
                    <strong>[${producto.producto_codigo}]</strong><br>
                    <small>${producto.producto_nombre}</small>
                    <input type="hidden" name="productos_aprobados[]" value="${producto.producto_id}">
                </td>
                <td><strong>${producto.cantidad_solicitada}</strong></td>
                <td><span class="${stockClass}">${producto.stock_disponible_real}</span></td>
                <td>
                    <input type="number" class="form-control form-control-sm" 
                           name="cantidades_aprobadas[]" 
                           min="0" 
                           max="${maxAprobable}"
                           value="${maxAprobable}"
                           ${producto.stock_disponible_real === 0 ? 'disabled' : ''}>
                </td>
            </tr>
        `;
    });
    
    html += `
                </tbody>
            </table>
        </div>
        
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>Importante:</strong> Solo se aprobarán las cantidades especificadas. 
            Verifique que hay suficiente stock disponible antes de aprobar.
        </div>
    `;
    
    return html;
}

function rechazarOrden(ordenId) {
    const motivo = prompt('Motivo del rechazo:');
    if (motivo && motivo.trim()) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="rechazar_orden">
            <input type="hidden" name="orden_id" value="${ordenId}">
            <input type="hidden" name="motivo_rechazo" value="${motivo}">
            <?php echo campoCSRF(); ?>
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function procesarTraslado(ordenId) {
    if (confirm('¿Confirma que desea procesar el traslado de esta orden? Esta acción moverá los productos entre inventarios.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="procesar_traslado">
            <input type="hidden" name="orden_id" value="${ordenId}">
            <?php echo campoCSRF(); ?>
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// Toast helper function
function showToast(message, type) {
    if (typeof window.showToast === 'function') {
        window.showToast(message, type);
    } else {
        alert(message);
    }
}
</script>

<?php include 'includes/layout_footer.php'; ?>
