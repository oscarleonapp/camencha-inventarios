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
    
    if ($_POST['action'] == 'procesar_devolucion') {
        verificarPermiso('inventarios_actualizar');
        
        $venta_id = $_POST['venta_id'];
        $detalle_venta_id = $_POST['detalle_venta_id'];
        $cantidad_devuelta = (int)$_POST['cantidad_devuelta'];
        $motivo = $_POST['motivo'];
        $estado_producto = $_POST['estado_producto'];
        $notas = $_POST['notas'] ?? '';
        $usuario_id = $_SESSION['usuario_id'];
        
        // Obtener datos de la venta
        $query_detalle = "SELECT dv.*, v.tienda_id, v.total, p.nombre as producto_nombre, p.codigo as producto_codigo
                         FROM detalle_ventas dv 
                         JOIN ventas v ON dv.venta_id = v.id 
                         JOIN productos p ON dv.producto_id = p.id
                         WHERE dv.id = ?";
        $stmt_detalle = $db->prepare($query_detalle);
        $stmt_detalle->execute([$detalle_venta_id]);
        $detalle = $stmt_detalle->fetch(PDO::FETCH_ASSOC);
        
        if (!$detalle) {
            $error = "No se encontró el detalle de venta especificado.";
        } elseif ($cantidad_devuelta <= 0 || $cantidad_devuelta > $detalle['cantidad']) {
            $error = "La cantidad a devolver debe ser entre 1 y " . $detalle['cantidad'] . ".";
        } else {
            // Manejar subida de imagen del estado del producto
            $imagen_estado = null;
            if (isset($_FILES['imagen_estado']) && $_FILES['imagen_estado']['error'] === UPLOAD_ERR_OK) {
                $archivo = $_FILES['imagen_estado'];
                $tipos_permitidos = ['image/jpeg', 'image/png', 'image/gif'];
                $tamaño_maximo = 5 * 1024 * 1024; // 5MB
                
                if (!in_array($archivo['type'], $tipos_permitidos)) {
                    $error = "Tipo de archivo no permitido. Solo se aceptan JPG, PNG y GIF.";
                } elseif ($archivo['size'] > $tamaño_maximo) {
                    $error = "El archivo es demasiado grande. Máximo 5MB permitido.";
                } else {
                    $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
                    $imagen_estado = 'dev_' . $venta_id . '_' . $detalle_venta_id . '_' . time() . '.' . $extension;
                    $ruta_destino = 'uploads/devoluciones/' . $imagen_estado;
                    
                    if (!move_uploaded_file($archivo['tmp_name'], $ruta_destino)) {
                        $error = "Error al subir la imagen. Inténtalo de nuevo.";
                    }
                }
            }
            
            if (!isset($error)) {
                try {
                    $db->beginTransaction();
                    
                    // Calcular monto a devolver
                    $monto_devuelto = $detalle['precio_unitario'] * $cantidad_devuelta;
                    
                    // Registrar la devolución
                    $query_devolucion = "INSERT INTO devoluciones 
                                        (venta_id, detalle_venta_id, producto_id, tienda_id, cantidad_devuelta, 
                                         motivo, estado_producto, imagen_estado, monto_devuelto, usuario_id, notas, estado) 
                                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'aprobada')";
                    $stmt_devolucion = $db->prepare($query_devolucion);
                    $stmt_devolucion->execute([
                        $venta_id, $detalle_venta_id, $detalle['producto_id'], $detalle['tienda_id'],
                        $cantidad_devuelta, $motivo, $estado_producto, $imagen_estado, 
                        $monto_devuelto, $usuario_id, $notas
                    ]);
                    
                    // Actualizar inventario - devolver al stock
                    $query_check_inventario = "SELECT cantidad FROM inventarios WHERE tienda_id = ? AND producto_id = ?";
                    $stmt_check_inventario = $db->prepare($query_check_inventario);
                    $stmt_check_inventario->execute([$detalle['tienda_id'], $detalle['producto_id']]);
                    $inventario_actual = $stmt_check_inventario->fetch(PDO::FETCH_ASSOC);
                    
                    if ($inventario_actual) {
                        // Actualizar inventario existente
                        $nueva_cantidad = $inventario_actual['cantidad'] + $cantidad_devuelta;
                        $query_update_inventario = "UPDATE inventarios SET cantidad = ? WHERE tienda_id = ? AND producto_id = ?";
                        $stmt_update_inventario = $db->prepare($query_update_inventario);
                        $stmt_update_inventario->execute([$nueva_cantidad, $detalle['tienda_id'], $detalle['producto_id']]);
                    } else {
                        // Crear nuevo registro de inventario
                        $query_insert_inventario = "INSERT INTO inventarios (tienda_id, producto_id, cantidad) VALUES (?, ?, ?)";
                        $stmt_insert_inventario = $db->prepare($query_insert_inventario);
                        $stmt_insert_inventario->execute([$detalle['tienda_id'], $detalle['producto_id'], $cantidad_devuelta]);
                    }
                    
                    // Registrar movimiento de inventario
                    $query_movimiento = "INSERT INTO movimientos_inventario 
                                        (tipo_movimiento, producto_id, tienda_id, tienda_destino_id, cantidad, motivo, referencia_id, referencia_tipo, usuario_id) 
                                        VALUES ('devolucion', ?, ?, ?, ?, ?, ?, 'devolucion', ?)";
                    $stmt_movimiento = $db->prepare($query_movimiento);
                    $notas_movimiento = "Devolución de venta #$venta_id - $motivo";
                    $stmt_movimiento->execute([
                        $detalle['producto_id'], $detalle['tienda_id'], $detalle['tienda_id'], $cantidad_devuelta,
                        $notas_movimiento, $venta_id, $usuario_id
                    ]);
                    
                    $db->commit();
                    $success = "Devolución procesada exitosamente. Se devolvieron $cantidad_devuelta unidades al inventario. Monto: Q" . number_format($monto_devuelto, 2);
                    
                } catch (Exception $e) {
                    $db->rollBack();
                    $error = "Error al procesar la devolución: " . $e->getMessage();
                }
            }
        }
    }
}

// Obtener ventas recientes para seleccionar
$query_ventas = "SELECT v.id, v.fecha, v.total, u.nombre as vendedor
                FROM ventas v 
                LEFT JOIN usuarios u ON v.usuario_id = u.id 
                WHERE v.estado = 'completada' 
                ORDER BY v.fecha DESC 
                LIMIT 50";
$stmt_ventas = $db->prepare($query_ventas);
$stmt_ventas->execute();
$ventas = $stmt_ventas->fetchAll(PDO::FETCH_ASSOC);

include 'includes/layout_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 rs-wrap-sm">
    <h2><i class="fas fa-undo"></i> <span class="editable" data-label="ingreso_dev_titulo">Ingreso por Devolución</span></h2>
    <a href="reportes_devoluciones.php" class="btn btn-outline-info">
        <i class="fas fa-chart-line"></i> Ver Reportes
    </a>
</div>

<!-- Los mensajes ahora se muestran via Toast -->
<?php if (isset($success)): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            showSuccess('<?php echo addslashes($success); ?>');
        });
    </script>
<?php endif; ?>
<?php if (isset($error)): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            showError('<?php echo addslashes($error); ?>');
        });
    </script>
<?php endif; ?>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-undo"></i>
                    Procesar Devolución de Producto Vendido
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data" id="formDevolucion">
                    <input type="hidden" name="action" value="procesar_devolucion">
                    <?php echo campoCSRF(); ?>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="fas fa-receipt"></i>
                                    Venta de Origen
                                </label>
                                <select class="form-select" name="venta_id" id="ventaSelect" required>
                                    <option value="">Seleccionar venta...</option>
                                    <?php foreach ($ventas as $venta): ?>
                                        <option value="<?php echo $venta['id']; ?>">
                                            Venta #<?php echo $venta['id']; ?> - <?php echo date('d/m/Y', strtotime($venta['fecha'])); ?> 
                                            - Q<?php echo number_format($venta['total'], 2); ?>
                                            <?php if ($venta['vendedor']): ?> - <?php echo htmlspecialchars($venta['vendedor']); ?><?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="fas fa-box"></i>
                                    Producto Vendido
                                </label>
                                <select class="form-select" name="detalle_venta_id" id="productoSelect" required disabled>
                                    <option value="">Primero selecciona una venta...</option>
                                </select>
                                <div id="productoInfo" class="form-text d-none"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="fas fa-sort-numeric-up"></i>
                                    Cantidad a Devolver
                                </label>
                                <input type="number" class="form-control" name="cantidad_devuelta" id="cantidadDevuelta" min="1" required disabled>
                                <div class="form-text" id="cantidadMaxima"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="fas fa-comment-dots"></i>
                                    Motivo de Devolución
                                </label>
                                <select class="form-select" name="motivo" required>
                                    <option value="">Seleccionar motivo...</option>
                                    <option value="Producto defectuoso">Producto defectuoso</option>
                                    <option value="Cliente no satisfecho">Cliente no satisfecho</option>
                                    <option value="Error en la venta">Error en la venta</option>
                                    <option value="Producto equivocado">Producto equivocado</option>
                                    <option value="Cambio de opinion">Cambio de opinión del cliente</option>
                                    <option value="Garantia">Garantía</option>
                                    <option value="Otro">Otro</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="fas fa-heart-broken"></i>
                                    Estado del Producto
                                </label>
                                <select class="form-select" name="estado_producto" required>
                                    <option value="">Seleccionar estado...</option>
                                    <option value="bueno">Bueno - Se puede revender</option>
                                    <option value="usado">Usado - Reventa con descuento</option>
                                    <option value="defectuoso">Defectuoso - Enviar a reparación</option>
                                    <option value="danado">Dañado - No se puede revender</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-camera"></i>
                            Imagen del Estado del Producto *
                        </label>
                        <input type="file" class="form-control" name="imagen_estado" accept="image/*" id="imagenEstado" required>
                        <div class="form-text">
                            <i class="fas fa-info-circle"></i> 
                            Sube una foto del estado actual del producto. Formatos: JPG, PNG, GIF. Máximo 5MB.
                        </div>
                        <div id="previewImagenEstado" class="mt-2 d-none">
                            <img id="imagenEstadoPreview" src="" alt="Vista previa" class="img-thumbnail" style="max-height: 200px;">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-sticky-note"></i>
                            Notas Adicionales
                        </label>
                        <textarea class="form-control" name="notas" rows="3" placeholder="Detalles adicionales sobre la devolución..."></textarea>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Información:</strong> Al procesar la devolución, el producto se reintegrará automáticamente al inventario de la tienda correspondiente.
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-success btn-lg" id="btnProcesar" disabled>
                            <i class="fas fa-check"></i>
                            Procesar Devolución
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card bg-light">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-info-circle"></i> Información de la Devolución</h6>
            </div>
            <div class="card-body">
                <div id="resumenDevolucion" class="d-none">
                    <h6>Resumen:</h6>
                    <ul class="list-unstyled">
                        <li><strong>Venta:</strong> <span id="resumenVenta">-</span></li>
                        <li><strong>Producto:</strong> <span id="resumenProducto">-</span></li>
                        <li><strong>Cantidad:</strong> <span id="resumenCantidad">-</span></li>
                        <li><strong>Monto a devolver:</strong> <span id="resumenMonto" class="text-success fw-bold">Q0.00</span></li>
                    </ul>
                </div>
                
                <div class="small text-muted">
                    <h6>Proceso de Devolución:</h6>
                    <ol>
                        <li>Selecciona la venta original</li>
                        <li>Elige el producto a devolver</li>
                        <li>Especifica cantidad y motivo</li>
                        <li>Indica el estado del producto</li>
                        <li>Sube una foto del estado</li>
                        <li>El sistema reintegrará al inventario</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ventaSelect = document.getElementById('ventaSelect');
    const productoSelect = document.getElementById('productoSelect');
    const cantidadInput = document.getElementById('cantidadDevuelta');
    const btnProcesar = document.getElementById('btnProcesar');
    
    // Cargar productos cuando se selecciona una venta
    ventaSelect.addEventListener('change', function() {
        const ventaId = this.value;
        
        if (ventaId) {
            // Hacer petición AJAX para obtener productos de la venta
            fetch('ajax/get_venta_productos.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ venta_id: ventaId })
            })
            .then(response => response.json())
            .then(data => {
                productoSelect.innerHTML = '<option value="">Seleccionar producto...</option>';
                
                if (data.success && data.productos.length > 0) {
                    data.productos.forEach(producto => {
                        const option = document.createElement('option');
                        option.value = producto.detalle_id;
                        const cantidadTexto = producto.cantidad_devuelta_previa > 0 
                            ? `Disponible: ${producto.cantidad_disponible} de ${producto.cantidad} (${producto.cantidad_devuelta_previa} ya devuelta)`
                            : `Disponible: ${producto.cantidad_disponible}`;
                        option.textContent = `${producto.codigo} - ${producto.nombre} (${cantidadTexto}, Precio: Q${producto.precio_unitario})`;
                        option.dataset.cantidad = producto.cantidad_disponible;
                        option.dataset.precio = producto.precio_unitario;
                        option.dataset.nombre = producto.nombre;
                        option.dataset.codigo = producto.codigo;
                        productoSelect.appendChild(option);
                    });
                    productoSelect.disabled = false;
                } else if (data.message) {
                    productoSelect.innerHTML = `<option value="">${data.message}</option>`;
                    productoSelect.disabled = true;
                } else {
                    productoSelect.innerHTML = '<option value="">No hay productos disponibles para devolver</option>';
                    productoSelect.disabled = true;
                }
                
                // Reset campos dependientes
                cantidadInput.disabled = true;
                cantidadInput.value = '';
                btnProcesar.disabled = true;
                document.getElementById('resumenDevolucion').classList.add('d-none');
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Error al cargar productos de la venta');
            });
        } else {
            productoSelect.innerHTML = '<option value="">Primero selecciona una venta...</option>';
            productoSelect.disabled = true;
            cantidadInput.disabled = true;
            btnProcesar.disabled = true;
        }
    });
    
    // Habilitar cantidad cuando se selecciona producto
    productoSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        
        if (this.value) {
            const maxCantidad = parseInt(selectedOption.dataset.cantidad);
            cantidadInput.max = maxCantidad;
            cantidadInput.disabled = false;
            document.getElementById('cantidadMaxima').textContent = `Máximo: ${maxCantidad} unidades`;
            
            // Mostrar info del producto
            document.getElementById('productoInfo').textContent = `Precio unitario: Q${selectedOption.dataset.precio}`;
            document.getElementById('productoInfo').classList.remove('d-none');
        } else {
            cantidadInput.disabled = true;
            cantidadInput.value = '';
            document.getElementById('cantidadMaxima').textContent = '';
            document.getElementById('productoInfo').classList.add('d-none');
        }
        
        actualizarResumen();
    });
    
    // Actualizar resumen cuando cambia la cantidad
    cantidadInput.addEventListener('input', function() {
        actualizarResumen();
        
        // Habilitar/deshabilitar botón
        const formularioCompleto = ventaSelect.value && productoSelect.value && this.value && this.value > 0;
        btnProcesar.disabled = !formularioCompleto;
    });
    
    function actualizarResumen() {
        const ventaTexto = ventaSelect.options[ventaSelect.selectedIndex]?.textContent || '';
        const productoOption = productoSelect.options[productoSelect.selectedIndex];
        const cantidad = parseInt(cantidadInput.value) || 0;
        
        if (ventaSelect.value && productoSelect.value && cantidad > 0) {
            const precio = parseFloat(productoOption.dataset.precio) || 0;
            const montoTotal = precio * cantidad;
            
            document.getElementById('resumenVenta').textContent = ventaTexto;
            document.getElementById('resumenProducto').textContent = productoOption.textContent;
            document.getElementById('resumenCantidad').textContent = cantidad + ' unidades';
            document.getElementById('resumenMonto').textContent = 'Q' + montoTotal.toFixed(2);
            
            document.getElementById('resumenDevolucion').classList.remove('d-none');
        } else {
            document.getElementById('resumenDevolucion').classList.add('d-none');
        }
    }
    
    // Vista previa de imagen
    document.getElementById('imagenEstado').addEventListener('change', function(e) {
        const file = e.target.files[0];
        const preview = document.getElementById('previewImagenEstado');
        const img = document.getElementById('imagenEstadoPreview');
        
        if (file) {
            if (file.size > 5 * 1024 * 1024) {
                showError('El archivo es demasiado grande. Máximo 5MB permitido.');
                e.target.value = '';
                preview.classList.add('d-none');
                return;
            }
            
            if (!file.type.match(/^image\/(jpeg|png|gif)$/)) {
                showError('Tipo de archivo no permitido. Solo se aceptan JPG, PNG y GIF.');
                e.target.value = '';
                preview.classList.add('d-none');
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                img.src = e.target.result;
                preview.classList.remove('d-none');
            };
            reader.readAsDataURL(file);
        } else {
            preview.classList.add('d-none');
        }
    });
});

// Funciones helper para toast
function showSuccess(mensaje) {
    if (typeof showToast === 'function') {
        showToast(mensaje, 'success');
    } else {
        alert(mensaje);
    }
}

function showError(mensaje) {
    if (typeof showToast === 'function') {
        showToast(mensaje, 'danger');
    } else {
        alert(mensaje);
    }
}
</script>

<?php include 'includes/layout_footer.php'; ?>
