<?php
$titulo = "Ventas - Sistema de Inventarios";
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'includes/config_functions.php';

verificarLogin();
verificarPermiso('ventas_ver');

$database = new Database();
$db = $database->getConnection();

if ($_POST && isset($_POST['action'])) {
    if ($_POST['action'] == 'realizar_venta') {
        $tienda_id = $_POST['tienda_id'];
        $productos = $_POST['productos'];
        $cantidades = $_POST['cantidades'];
        $precios_override = $_POST['precios'] ?? null;
        // Normalizar vendedor_id: vacío -> NULL; validar existencia
        $vendedor_id = isset($_POST['vendedor_id']) && $_POST['vendedor_id'] !== '' ? (int)$_POST['vendedor_id'] : null;
        if ($vendedor_id !== null) {
            $stmt_v = $db->prepare("SELECT id FROM vendedores WHERE id = ?");
            $stmt_v->execute([$vendedor_id]);
            if ($stmt_v->rowCount() === 0) {
                // Si el vendedor no existe, evitar romper la FK
                $vendedor_id = null;
            }
        }
        $usuario_id = $_SESSION['usuario_id'];
        
        $db->beginTransaction();
        
        try {
            $total_venta = 0;
            
            for ($i = 0; $i < count($productos); $i++) {
                if ($productos[$i] && $cantidades[$i] > 0) {
                    $producto_id = $productos[$i];
                    $cantidad = $cantidades[$i];
                    
                    $query_producto = "SELECT * FROM productos WHERE id = ?";
                    $stmt_producto = $db->prepare($query_producto);
                    $stmt_producto->execute([$producto_id]);
                    $producto = $stmt_producto->fetch(PDO::FETCH_ASSOC);
                    
                    if ($producto['tipo'] == 'conjunto') {
                        $query_componentes = "SELECT pc.*, p.nombre FROM producto_componentes pc 
                                            JOIN productos p ON pc.producto_elemento_id = p.id 
                                            WHERE pc.producto_conjunto_id = ?";
                        $stmt_componentes = $db->prepare($query_componentes);
                        $stmt_componentes->execute([$producto_id]);
                        $componentes = $stmt_componentes->fetchAll(PDO::FETCH_ASSOC);
                        
                        foreach ($componentes as $componente) {
                            $cantidad_necesaria = $componente['cantidad'] * $cantidad;
                            // Chequear disponible: cantidad - cantidad_reparacion
                            $query_check = "SELECT cantidad, COALESCE(cantidad_reparacion,0) AS cantidad_reparacion FROM inventarios WHERE tienda_id = ? AND producto_id = ?";
                            $stmt_check = $db->prepare($query_check);
                            $stmt_check->execute([$tienda_id, $componente['producto_elemento_id']]);
                            $inventario = $stmt_check->fetch(PDO::FETCH_ASSOC);
                            $disponible = $inventario ? ((int)$inventario['cantidad'] - (int)$inventario['cantidad_reparacion']) : 0;
                            if ($disponible < $cantidad_necesaria) {
                                throw new Exception("No hay suficiente stock de " . $componente['nombre'] . " para completar la venta");
                            }
                        }
                        
                        foreach ($componentes as $componente) {
                            $cantidad_necesaria = $componente['cantidad'] * $cantidad;
                            // Descontar del total, unidades en reparación se mantienen
                            $query_update = "UPDATE inventarios SET cantidad = cantidad - ? 
                                           WHERE tienda_id = ? AND producto_id = ?";
                            $stmt_update = $db->prepare($query_update);
                            $stmt_update->execute([$cantidad_necesaria, $tienda_id, $componente['producto_elemento_id']]);
                            
                            $query_movimiento = "INSERT INTO movimientos_inventario (tipo, producto_id, tienda_origen_id, cantidad, motivo, usuario_id) 
                                               VALUES ('salida', ?, ?, ?, 'Venta de conjunto', ?)";
                            $stmt_movimiento = $db->prepare($query_movimiento);
                            $stmt_movimiento->execute([$componente['producto_elemento_id'], $tienda_id, $cantidad_necesaria, $usuario_id]);
                        }
                    } else {
                        $query_check = "SELECT cantidad, COALESCE(cantidad_reparacion,0) AS cantidad_reparacion FROM inventarios WHERE tienda_id = ? AND producto_id = ?";
                        $stmt_check = $db->prepare($query_check);
                        $stmt_check->execute([$tienda_id, $producto_id]);
                        $inventario = $stmt_check->fetch(PDO::FETCH_ASSOC);
                        $disponible = $inventario ? ((int)$inventario['cantidad'] - (int)$inventario['cantidad_reparacion']) : 0;
                        if ($disponible < $cantidad) {
                            throw new Exception("No hay suficiente stock de " . $producto['nombre']);
                        }
                        
                        $query_update = "UPDATE inventarios SET cantidad = cantidad - ? 
                                       WHERE tienda_id = ? AND producto_id = ?";
                        $stmt_update = $db->prepare($query_update);
                        $stmt_update->execute([$cantidad, $tienda_id, $producto_id]);
                        
                        $query_movimiento = "INSERT INTO movimientos_inventario (tipo, producto_id, tienda_origen_id, cantidad, motivo, usuario_id) 
                                           VALUES ('salida', ?, ?, ?, 'Venta directa', ?)";
                        $stmt_movimiento = $db->prepare($query_movimiento);
                        $stmt_movimiento->execute([$producto_id, $tienda_id, $cantidad, $usuario_id]);
                    }
                    
                    // total se acumula al insertar detalle con precio definido
                }
            }
            
            $query_venta = "INSERT INTO ventas (tienda_id, usuario_id, vendedor_id, total, notas) VALUES (?, ?, ?, ?, ?)";
            $stmt_venta = $db->prepare($query_venta);
            $notas_venta = isset($_POST['desde_cotizacion_id']) ? ('Venta desde cotización #'.((int)$_POST['desde_cotizacion_id'])) : ($_POST['notas'] ?? null);
            $stmt_venta->execute([$tienda_id, $usuario_id, $vendedor_id, 0, $notas_venta]);
            $venta_id = $db->lastInsertId();
            
            for ($i = 0; $i < count($productos); $i++) {
                if ($productos[$i] && $cantidades[$i] > 0) {
                    $producto_id = $productos[$i];
                    $cantidad = $cantidades[$i];
                    
                    if ($precios_override && isset($precios_override[$i]) && is_numeric($precios_override[$i])) {
                        $precio = (float)$precios_override[$i];
                    } else {
                        $query_producto = "SELECT precio_venta FROM productos WHERE id = ?";
                        $stmt_producto = $db->prepare($query_producto);
                        $stmt_producto->execute([$producto_id]);
                        $precio = $stmt_producto->fetch(PDO::FETCH_ASSOC)['precio_venta'];
                    }
                    
                    $subtotal = $precio * $cantidad;
                    $total_venta += $subtotal;
                    
                    $query_detalle = "INSERT INTO detalle_ventas (venta_id, producto_id, cantidad, precio_unitario, subtotal) 
                                     VALUES (?, ?, ?, ?, ?)";
                    $stmt_detalle = $db->prepare($query_detalle);
                    $stmt_detalle->execute([$venta_id, $producto_id, $cantidad, $precio, $subtotal]);
                }
            }
            
            $stmt_up_total = $db->prepare("UPDATE ventas SET total = ? WHERE id = ?");
            $stmt_up_total->execute([$total_venta, $venta_id]);
            $db->commit();
            $success = "Venta realizada exitosamente. Total: $" . number_format($total_venta, 2);
            
        } catch (Exception $e) {
            $db->rollBack();
            $error = $e->getMessage();
        }
    }
}

$query_ventas = "SELECT v.*, t.nombre as tienda_nombre, u.nombre as usuario_nombre,
                        vend.nombre as vendedor_nombre,
                        vend.comision_porcentaje
                FROM ventas v 
                JOIN tiendas t ON v.tienda_id = t.id 
                JOIN usuarios u ON v.usuario_id = u.id 
                LEFT JOIN vendedores vend ON v.vendedor_id = vend.id
                ORDER BY v.fecha DESC LIMIT 50";
$stmt_ventas = $db->prepare($query_ventas);
$stmt_ventas->execute();
$ventas = $stmt_ventas->fetchAll(PDO::FETCH_ASSOC);

$query_tiendas = "SELECT * FROM tiendas WHERE activo = 1 ORDER BY nombre";
$stmt_tiendas = $db->prepare($query_tiendas);
$stmt_tiendas->execute();
$tiendas = $stmt_tiendas->fetchAll(PDO::FETCH_ASSOC);

$query_productos = "SELECT * FROM productos WHERE activo = 1 ORDER BY nombre";
$stmt_productos = $db->prepare($query_productos);
$stmt_productos->execute();
$productos = $stmt_productos->fetchAll(PDO::FETCH_ASSOC);

$query_vendedores = "SELECT * FROM vendedores WHERE activo = 1 ORDER BY nombre";
$stmt_vendedores = $db->prepare($query_vendedores);
$stmt_vendedores->execute();
$vendedores = $stmt_vendedores->fetchAll(PDO::FETCH_ASSOC);

include 'includes/layout_header.php';
?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-shopping-cart"></i> <span class="editable" data-label="ventas_titulo">Sistema de Ventas</span></h2>
        <?php if (tienePermiso('ventas_crear', 'crear')): ?>
        <button class="btn btn-success" data-bs-toggle="collapse" data-bs-target="#nuevaVentaForm">
            <i class="fas fa-plus"></i> Nueva Venta
        </button>
        <?php endif; ?>
    </div>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (tienePermiso('ventas_crear', 'crear')): ?>
        <div class="collapse mb-4" id="nuevaVentaForm">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-cash-register"></i> Nueva Venta</h5>
                </div>
            <div class="card-body">
                <form method="POST" id="ventaForm">
                    <input type="hidden" name="action" value="realizar_venta">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="fas fa-store"></i>
                                    Tienda *
                                </label>
                                <select class="form-select" name="tienda_id" required>
                                    <option value="">Seleccionar Tienda</option>
                                    <?php foreach ($tiendas as $tienda): ?>
                                        <option value="<?php echo $tienda['id']; ?>">
                                            <?php echo $tienda['nombre']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="fas fa-user-tie"></i>
                                    Vendedor
                                </label>
                                <select class="form-select" name="vendedor_id">
                                    <option value="">Sin vendedor asignado</option>
                                    <?php foreach ($vendedores as $vendedor): ?>
                                        <option value="<?php echo $vendedor['id']; ?>" data-comision="<?php echo $vendedor['comision_porcentaje']; ?>">
                                            <?php echo $vendedor['nombre']; ?>
                                            <?php if ($vendedor['comision_porcentaje'] > 0): ?>
                                                (<?php echo $vendedor['comision_porcentaje']; ?>% comisión)
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Opcional: Asignar vendedor para calcular comisiones</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label mb-0">Productos</label>
                            <div class="btn-group">
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="agregarProducto()">
                                    <i class="fas fa-plus"></i> Agregar Producto
                                </button>
                                <?php if (tienePermiso('productos_qr_escanear')): ?>
                                <button type="button" class="btn btn-outline-success btn-sm" onclick="abrirEscanerQR()">
                                    <i class="fas fa-qrcode"></i> Escanear QR
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div id="productosContainer">
                            <div class="row mb-2">
                                <div class="col-7">
                                    <select class="form-control" name="productos[]">
                                        <option value="">Seleccionar Producto</option>
                                        <?php foreach ($productos as $producto): ?>
                                            <option value="<?php echo $producto['id']; ?>" data-precio="<?php echo $producto['precio_venta']; ?>" data-codigo="<?php echo $producto['codigo']; ?>">
                                                <?php echo $producto['codigo'] . ' - ' . $producto['nombre'] . ' (Q' . number_format($producto['precio_venta'], 2) . ')'; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-2">
                                    <input type="number" class="form-control" name="cantidades[]" placeholder="Cantidad" min="1" value="1">
                                </div>
                                <div class="col-2">
                                    <input type="text" class="form-control bg-light" readonly placeholder="Q 0.00">
                                </div>
                                <div class="col-1">
                                    <button type="button" class="btn btn-danger btn-sm" onclick="eliminarProducto(this)">×</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Total: <span class="moneda" id="total">0.00</span></strong>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted">
                                    Comisión estimada: <span class="moneda" id="comisionEstimada">0.00</span>
                                    <span id="porcentajeComision"></span>
                                </small>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Realizar Venta</button>
                </form>
            </div>
        </div>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h5>Historial de Ventas</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Fecha</th>
                                <th>Tienda</th>
                                <th>Vendedor</th>
                                <th>Total</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ventas as $venta): ?>
                            <tr>
                                <td><?php echo $venta['id']; ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($venta['fecha'])); ?></td>
                                <td><?php echo $venta['tienda_nombre']; ?></td>
                                <td>
                                    <?php if ($venta['vendedor_nombre']): ?>
                                        <strong><?php echo $venta['vendedor_nombre']; ?></strong>
                                        <?php if ($venta['comision_porcentaje'] > 0): ?>
                                            <br><small class="text-muted"><?php echo $venta['comision_porcentaje']; ?>% comisión</small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">Sin vendedor</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo formatearMoneda($venta['total']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo ($venta['estado'] ?? 'pendiente') == 'pendiente' ? 'warning' : (($venta['estado'] ?? 'pendiente') == 'entregada' ? 'success' : 'danger'); ?>">
                                        <?php echo ucfirst($venta['estado'] ?? 'pendiente'); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="detalle_venta.php?id=<?php echo $venta['id']; ?>" class="btn btn-sm btn-info">Ver Detalle</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Escáner QR -->
    <div class="modal fade" id="qrScannerModal" tabindex="-1" aria-labelledby="qrScannerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="qrScannerModalLabel">
                        <i class="fas fa-qrcode"></i> Escáner de Código QR
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <p class="text-muted">Posiciona el código QR frente a la cámara para escanearlo</p>
                    </div>
                    
                    <!-- Contenedor de video -->
                    <div class="position-relative mb-3">
                        <video id="qrVideo" class="w-100" style="max-height: 400px; border-radius: 8px;" autoplay muted playsinline></video>
                        <div id="qrOverlay" class="position-absolute top-50 start-50 translate-middle" style="width: 200px; height: 200px; border: 2px solid #28a745; border-radius: 10px; display: none;"></div>
                    </div>
                    
                    <!-- Canvas oculto para procesamiento -->
                    <canvas id="qrCanvas" style="display: none;"></canvas>
                    
                    <!-- Estado del escáner -->
                    <div class="text-center">
                        <div id="qrStatus" class="alert alert-info">
                            <i class="fas fa-camera"></i> Iniciando cámara...
                        </div>
                    </div>
                    
                    <!-- Resultado del escaneo -->
                    <div id="qrResult" class="d-none">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title">Producto Escaneado</h6>
                                <div id="qrProductInfo"></div>
                                <div class="mt-3">
                                    <button type="button" class="btn btn-success me-2" onclick="agregarProductoEscaneado()">
                                        <i class="fas fa-plus"></i> Agregar a Venta
                                    </button>
                                    <button type="button" class="btn btn-secondary" onclick="continuarEscaneando()">
                                        <i class="fas fa-qrcode"></i> Escanear Otro
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary" onclick="alternarCamara()" id="toggleCameraBtn">
                        <i class="fas fa-camera"></i> Alternar Cámara
                    </button>
                </div>
            </div>
        </div>
    </div>
    
<script>
function agregarProducto() {
    const container = document.getElementById('productosContainer');
    const newRow = container.firstElementChild.cloneNode(true);
    newRow.querySelectorAll('input, select').forEach(input => input.value = '');
    container.appendChild(newRow);
    calcularTotal();
}

function eliminarProducto(button) {
    const container = document.getElementById('productosContainer');
    if (container.children.length > 1) {
        button.closest('.row').remove();
        calcularTotal();
    }
}

function calcularTotal() {
    let total = 0;
    const rows = document.querySelectorAll('#productosContainer .row');
    rows.forEach(row => {
        const select = row.querySelector('select[name="productos[]"]');
        const cantidad = row.querySelector('input[name="cantidades[]"]');
        if (select.value && cantidad.value) {
            const precio = parseFloat(select.options[select.selectedIndex].dataset.precio || 0);
            total += precio * parseInt(cantidad.value);
        }
    });
    document.getElementById('total').textContent = formatCurrency(total);
    
    // Calcular comisión estimada
    calcularComisionEstimada(total);
}

function calcularComisionEstimada(total) {
    const vendedorSelect = document.querySelector('select[name="vendedor_id"]');
    const comisionElement = document.getElementById('comisionEstimada');
    const porcentajeElement = document.getElementById('porcentajeComision');
    
    if (vendedorSelect.value) {
        const porcentaje = parseFloat(vendedorSelect.options[vendedorSelect.selectedIndex].dataset.comision || 0);
        const comision = (total * porcentaje / 100);
        comisionElement.textContent = formatCurrency(comision);
        porcentajeElement.textContent = `(${porcentaje}%)`;
    } else {
        comisionElement.textContent = formatCurrency(0);
        porcentajeElement.textContent = '';
    }
}

// Recalcular comisión cuando cambie el vendedor
document.querySelector('select[name="vendedor_id"]').addEventListener('change', function() {
    const total = parseFloat(document.getElementById('total').textContent.replace(/[^\d.-]/g, ''));
    calcularComisionEstimada(total);
});

document.addEventListener('change', calcularTotal);
document.addEventListener('input', calcularTotal);

// ===== FUNCIONALIDAD DE ESCÁNER QR =====
let qrStream = null;
let qrScanning = false;
let productoEscaneado = null;

function abrirEscanerQR() {
    const modal = new bootstrap.Modal(document.getElementById('qrScannerModal'));
    modal.show();
    
    // Iniciar cámara cuando se abra el modal
    document.getElementById('qrScannerModal').addEventListener('shown.bs.modal', function () {
        iniciarCamara();
    });
    
    // Detener cámara cuando se cierre el modal
    document.getElementById('qrScannerModal').addEventListener('hidden.bs.modal', function () {
        detenerCamara();
    });
}

async function iniciarCamara() {
    const video = document.getElementById('qrVideo');
    const status = document.getElementById('qrStatus');
    const overlay = document.getElementById('qrOverlay');
    
    try {
        status.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Iniciando cámara...';
        status.className = 'alert alert-info';
        
        // Solicitar acceso a la cámara
        qrStream = await navigator.mediaDevices.getUserMedia({
            video: { 
                facingMode: 'environment', // Cámara trasera preferida
                width: { ideal: 640 },
                height: { ideal: 480 }
            }
        });
        
        video.srcObject = qrStream;
        
        video.onloadedmetadata = () => {
            video.play();
            overlay.style.display = 'block';
            status.innerHTML = '<i class="fas fa-qrcode"></i> Busca un código QR...';
            status.className = 'alert alert-success';
            
            // Iniciar escaneo
            qrScanning = true;
            escanearQR();
        };
        
    } catch (error) {
        console.error('Error accediendo a la cámara:', error);
        status.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Error: No se pudo acceder a la cámara. Verifica los permisos.';
        status.className = 'alert alert-danger';
    }
}

function detenerCamara() {
    qrScanning = false;
    
    if (qrStream) {
        qrStream.getTracks().forEach(track => track.stop());
        qrStream = null;
    }
    
    const video = document.getElementById('qrVideo');
    video.srcObject = null;
    
    const overlay = document.getElementById('qrOverlay');
    overlay.style.display = 'none';
    
    // Resetear UI
    document.getElementById('qrResult').classList.add('d-none');
    document.getElementById('qrStatus').classList.remove('d-none');
}

function escanearQR() {
    if (!qrScanning) return;
    
    const video = document.getElementById('qrVideo');
    const canvas = document.getElementById('qrCanvas');
    const context = canvas.getContext('2d');
    
    if (video.readyState === video.HAVE_ENOUGH_DATA) {
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        context.drawImage(video, 0, 0, canvas.width, canvas.height);
        
        const imageData = context.getImageData(0, 0, canvas.width, canvas.height);
        const code = jsQR(imageData.data, imageData.width, imageData.height);
        
        if (code) {
            procesarQRCode(code.data);
            return;
        }
    }
    
    // Continuar escaneando
    if (qrScanning) {
        requestAnimationFrame(escanearQR);
    }
}

async function procesarQRCode(qrData) {
    qrScanning = false; // Pausar escaneo
    
    const status = document.getElementById('qrStatus');
    status.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando código QR...';
    status.className = 'alert alert-info';
    
    try {
        // Extraer código QR del JSON o usar directamente
        let qrCode = qrData;
        try {
            const qrDataObj = JSON.parse(qrData);
            if (qrDataObj.qr) {
                qrCode = qrDataObj.qr;
            }
        } catch (e) {
            // Si no es JSON válido, usar qrData directamente
        }
        
        // Consultar información del producto
        const response = await fetch(`qr_scan.php?action=info&qr=${encodeURIComponent(qrCode)}`);
        const result = await response.json();
        
        if (result.success && result.producto) {
            mostrarProductoEscaneado(result.producto);
        } else {
            throw new Error(result.error || 'Código QR no válido');
        }
        
    } catch (error) {
        console.error('Error procesando QR:', error);
        status.innerHTML = '<i class="fas fa-exclamation-triangle"></i> ' + (error.message || 'Error procesando código QR');
        status.className = 'alert alert-danger';
        
        // Reiniciar escaneo después de 2 segundos
        setTimeout(() => {
            if (document.getElementById('qrScannerModal').classList.contains('show')) {
                qrScanning = true;
                escanearQR();
                status.innerHTML = '<i class="fas fa-qrcode"></i> Busca un código QR...';
                status.className = 'alert alert-success';
            }
        }, 2000);
    }
}

function mostrarProductoEscaneado(producto) {
    productoEscaneado = producto;
    
    const qrStatus = document.getElementById('qrStatus');
    const qrResult = document.getElementById('qrResult');
    const qrProductInfo = document.getElementById('qrProductInfo');
    
    // Ocultar estado y mostrar resultado
    qrStatus.classList.add('d-none');
    qrResult.classList.remove('d-none');
    
    // Mostrar información del producto
    qrProductInfo.innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <strong>Código:</strong> ${producto.codigo}<br>
                <strong>Nombre:</strong> ${producto.nombre}
            </div>
            <div class="col-md-6">
                <strong>Precio:</strong> Q ${Number(producto.precio_venta).toFixed(2)}<br>
                <strong>Tipo:</strong> <span class="badge bg-${producto.tipo === 'elemento' ? 'primary' : 'success'}">${producto.tipo}</span>
            </div>
        </div>
        ${producto.descripcion ? `<div class="mt-2"><strong>Descripción:</strong> ${producto.descripcion}</div>` : ''}
    `;
}

function agregarProductoEscaneado() {
    if (!productoEscaneado) return;
    
    // Buscar una fila vacía o crear una nueva
    const productosContainer = document.getElementById('productosContainer');
    let filaVacia = null;
    
    // Buscar fila vacía
    const filas = productosContainer.querySelectorAll('.row');
    for (let fila of filas) {
        const select = fila.querySelector('select[name="productos[]"]');
        if (!select.value) {
            filaVacia = fila;
            break;
        }
    }
    
    // Si no hay fila vacía, crear una nueva
    if (!filaVacia) {
        agregarProducto();
        const nuevasFilas = productosContainer.querySelectorAll('.row');
        filaVacia = nuevasFilas[nuevasFilas.length - 1];
    }
    
    // Seleccionar el producto en la fila
    const select = filaVacia.querySelector('select[name="productos[]"]');
    select.value = productoEscaneado.id;
    
    // Configurar cantidad a 1
    const cantidadInput = filaVacia.querySelector('input[name="cantidades[]"]');
    cantidadInput.value = 1;
    
    // Recalcular total
    calcularTotal();
    
    // Cerrar modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('qrScannerModal'));
    modal.hide();
    
    // Mostrar mensaje de éxito
    mostrarToast('Producto agregado exitosamente: ' + productoEscaneado.nombre, 'success');
}

function continuarEscaneando() {
    // Ocultar resultado y mostrar estado
    document.getElementById('qrResult').classList.add('d-none');
    document.getElementById('qrStatus').classList.remove('d-none');
    
    const status = document.getElementById('qrStatus');
    status.innerHTML = '<i class="fas fa-qrcode"></i> Busca un código QR...';
    status.className = 'alert alert-success';
    
    // Reiniciar escaneo
    qrScanning = true;
    escanearQR();
}

async function alternarCamara() {
    try {
        // Obtener dispositivos de video disponibles
        const devices = await navigator.mediaDevices.enumerateDevices();
        const videoDevices = devices.filter(device => device.kind === 'videoinput');
        
        if (videoDevices.length > 1) {
            detenerCamara();
            // Aquí podrías implementar lógica para alternar entre cámaras
            setTimeout(() => iniciarCamara(), 500);
        }
    } catch (error) {
        console.error('Error alternando cámara:', error);
    }
}

// Función helper para mostrar toasts
function mostrarToast(mensaje, tipo = 'info') {
    // Si existe sistema de toast, usarlo
    if (typeof showToast === 'function') {
        showToast(mensaje, tipo);
    } else {
        // Fallback a alert
        alert(mensaje);
    }
}

// Validación: exigir selección de vendedor antes de enviar
document.getElementById('ventaForm')?.addEventListener('submit', function(e) {
    const vendedorSelect = document.querySelector('select[name="vendedor_id"]');
    if (vendedorSelect && (!vendedorSelect.value || vendedorSelect.value === '')) {
        e.preventDefault();
        mostrarToast('Es necesario seleccionar un vendedor', 'warning');
        vendedorSelect.focus();
    }
});
</script>

<?php include 'includes/layout_footer.php'; ?>
