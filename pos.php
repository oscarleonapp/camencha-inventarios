<?php
$titulo = "POS - Punto de Venta";
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'includes/config_functions.php';
require_once 'includes/tienda_security.php';
require_once 'includes/csrf_protection.php';

verificarLogin();
verificarPermiso('ventas_crear');

$database = new Database();
$db = $database->getConnection();

// Obtener tienda principal del usuario o la primera asignada
$usuario_id = $_SESSION['usuario_id'];
$tienda_principal = null; // Inicializar variable

// Verificar si se seleccionó una tienda específica via GET
$tienda_seleccionada = isset($_GET['tienda']) ? (int)$_GET['tienda'] : null;

if (esAdmin()) {
    // Admin puede seleccionar cualquier tienda
    if ($tienda_seleccionada) {
        // Verificar que la tienda seleccionada existe y está activa
        $query_verificar = "SELECT id FROM tiendas WHERE id = ? AND activo = 1";
        $stmt_verificar = $db->prepare($query_verificar);
        $stmt_verificar->execute([$tienda_seleccionada]);
        if ($stmt_verificar->fetch()) {
            $tienda_principal = $tienda_seleccionada;
        } else {
            $error = "Tienda seleccionada no válida o inactiva";
            $tienda_seleccionada = null;
        }
    }
    
    // Si no hay tienda seleccionada válida, usar la primera activa
    if (!$tienda_principal) {
        $query_primera_tienda = "SELECT id FROM tiendas WHERE activo = 1 ORDER BY id LIMIT 1";
        $stmt_primera = $db->prepare($query_primera_tienda);
        $stmt_primera->execute();
        $primera_tienda = $stmt_primera->fetch(PDO::FETCH_ASSOC);
        $tienda_principal = $primera_tienda['id'] ?? 1;
    }
} else {
    // Para usuarios normales, verificar acceso a la tienda seleccionada
    $tiendas_usuario_ids = getTiendasUsuario($db, $usuario_id);
    
    if ($tienda_seleccionada && in_array($tienda_seleccionada, $tiendas_usuario_ids)) {
        $tienda_principal = $tienda_seleccionada;
    } else {
        // Usar tienda principal o primera asignada
        $tienda_principal = getTiendaPrincipalUsuario($db, $usuario_id);
        if (!$tienda_principal && !empty($tiendas_usuario_ids)) {
            $tienda_principal = $tiendas_usuario_ids[0];
        }
    }
    
    if (!$tienda_principal) {
        $error = "No tienes tiendas asignadas. Contacta al administrador.";
    }
}

if ($_POST && isset($_POST['action']) && $_POST['action'] == 'pos_venta') {
    validarCSRF();
    
    $tienda_id = $_POST['tienda_id'] ?? $tienda_principal;
    $items = json_decode($_POST['items'], true);
    $vendedor_id = isset($_POST['vendedor_id']) && $_POST['vendedor_id'] !== '' ? (int)$_POST['vendedor_id'] : null;
    $descuento = floatval($_POST['descuento'] ?? 0);
    $notas = trim($_POST['notas'] ?? '');
    
    try {
        // Validar acceso a tienda
        validarAccesoTienda($db, $usuario_id, $tienda_id, 'realizar ventas POS');
        
        if (empty($items)) {
            throw new Exception("No hay productos en la venta");
        }
        
        $db->beginTransaction();
        
        $subtotal = 0;
        $productos_validados = [];
        
        // Validar productos y stock
        foreach ($items as $item) {
            $producto_id = (int)$item['id'];
            $cantidad = (int)$item['cantidad'];
            $precio_unitario = floatval($item['precio']);
            
            if ($cantidad <= 0) {
                throw new Exception("Cantidad inválida para producto ID: $producto_id");
            }
            
            // Verificar stock disponible
            $query_stock = "SELECT i.cantidad, COALESCE(i.cantidad_reparacion, 0) as cantidad_reparacion,
                                   i.cantidad - COALESCE(i.cantidad_reparacion, 0) as disponible, 
                                   p.nombre
                           FROM inventarios i 
                           JOIN productos p ON i.producto_id = p.id
                           WHERE i.tienda_id = ? AND i.producto_id = ?";
            $stmt_stock = $db->prepare($query_stock);
            $stmt_stock->execute([$tienda_id, $producto_id]);
            $stock = $stmt_stock->fetch(PDO::FETCH_ASSOC);
            
            // Debug detallado del stock
            error_log("POS Stock Debug - Producto ID: $producto_id, Tienda ID: $tienda_id");
            if ($stock) {
                error_log("POS Stock Debug - {$stock['nombre']}: Total={$stock['cantidad']}, Reparacion={$stock['cantidad_reparacion']}, Disponible={$stock['disponible']}");
            } else {
                error_log("POS Stock Debug - No se encontró inventario para producto ID $producto_id en tienda ID $tienda_id");
            }
            
            if (!$stock || $stock['disponible'] < $cantidad) {
                $disponible = $stock ? $stock['disponible'] : 0;
                $nombre_producto = $stock ? $stock['nombre'] : "Producto ID $producto_id";
                throw new Exception("Stock insuficiente para $nombre_producto. Disponible: $disponible, Solicitado: $cantidad");
            }
            
            $productos_validados[] = [
                'id' => $producto_id,
                'cantidad' => $cantidad,
                'precio' => $precio_unitario,
                'subtotal_item' => $cantidad * $precio_unitario
            ];
            
            $subtotal += $cantidad * $precio_unitario;
        }
        
        $total = $subtotal - $descuento;
        if ($total < 0) $total = 0;
        
        // Insertar venta
        $query_venta = "INSERT INTO ventas (fecha, subtotal, descuento, total, tienda_id, usuario_id, vendedor_id, notas) 
                        VALUES (NOW(), ?, ?, ?, ?, ?, ?, ?)";
        $stmt_venta = $db->prepare($query_venta);
        $stmt_venta->execute([$subtotal, $descuento, $total, $tienda_id, $usuario_id, $vendedor_id, $notas]);
        $venta_id = $db->lastInsertId();
        
        // Insertar detalles y actualizar inventario
        foreach ($productos_validados as $producto) {
            // Detalle de venta
            $query_detalle = "INSERT INTO detalle_ventas (venta_id, producto_id, cantidad, precio_unitario, subtotal) 
                             VALUES (?, ?, ?, ?, ?)";
            $stmt_detalle = $db->prepare($query_detalle);
            $stmt_detalle->execute([
                $venta_id, 
                $producto['id'], 
                $producto['cantidad'], 
                $producto['precio'], 
                $producto['subtotal_item']
            ]);
            
            // Actualizar inventario
            $query_update_inv = "UPDATE inventarios SET cantidad = cantidad - ? 
                                WHERE tienda_id = ? AND producto_id = ?";
            $stmt_update_inv = $db->prepare($query_update_inv);
            $stmt_update_inv->execute([$producto['cantidad'], $tienda_id, $producto['id']]);
        }
        
        $db->commit();
        $success = "Venta POS #$venta_id procesada exitosamente - Total: Q" . number_format($total, 2);
        $venta_exitosa = true;
        
        // Limpiar datos para nueva venta
        $_POST = [];
        
    } catch (Exception $e) {
        $db->rollBack();
        $error = "Error en venta POS: " . $e->getMessage();
    }
}

// Obtener datos para POS
$tiendas = getTiendasUsuarioCompleta($db, $usuario_id);

// Productos con stock de la tienda seleccionada
$tienda_para_productos = $tienda_principal;

// Debug: verificar tienda seleccionada
error_log("POS Debug - Usuario ID: $usuario_id, Tienda para productos: $tienda_para_productos");

$query_productos = "SELECT p.id, p.codigo, p.nombre, p.precio_venta, p.tipo,
                           COALESCE(i.cantidad, 0) as cantidad_total,
                           COALESCE(i.cantidad_reparacion, 0) as cantidad_reparacion,
                           COALESCE(i.cantidad - COALESCE(i.cantidad_reparacion, 0), 0) as stock_disponible,
                           i.tienda_id
                    FROM productos p
                    LEFT JOIN inventarios i ON p.id = i.producto_id AND i.tienda_id = ?
                    WHERE p.activo = 1 
                    ORDER BY p.nombre";
$stmt_productos = $db->prepare($query_productos);
$stmt_productos->execute([$tienda_para_productos]);
$productos = $stmt_productos->fetchAll(PDO::FETCH_ASSOC);

// Debug: verificar algunos productos
error_log("POS Debug - Total productos encontrados: " . count($productos));
if (count($productos) > 0) {
    error_log("POS Debug - Primer producto: " . json_encode($productos[0]));
}

$query_vendedores = "SELECT * FROM vendedores WHERE activo = 1 ORDER BY nombre";
$stmt_vendedores = $db->prepare($query_vendedores);
$stmt_vendedores->execute();
$vendedores = $stmt_vendedores->fetchAll(PDO::FETCH_ASSOC);

include 'includes/layout_header.php';
?>

<!-- QR Scanner Library -->
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

<style>
.pos-container {
    background: #f8f9fa;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.producto-card {
    border: 2px solid #e9ecef;
    border-radius: 10px;
    transition: all 0.3s ease;
    cursor: pointer;
    height: 120px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    text-align: center;
}

.producto-card:hover {
    border-color: var(--primary-color, #007bff);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,123,255,0.2);
}

.producto-card.sin-stock {
    opacity: 0.5;
    cursor: not-allowed;
}

.producto-card.sin-stock:hover {
    transform: none;
    border-color: #dc3545;
}

.carrito-item {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 10px;
    margin-bottom: 8px;
    background: white;
}

.pos-totales {
    background: linear-gradient(135deg, var(--primary-color, #007bff) 0%, #0056b3 100%);
    color: white;
    border-radius: 10px;
    padding: 20px;
}

.btn-pos {
    border-radius: 8px;
    font-weight: 600;
    padding: 10px 20px;
    transition: all 0.3s ease;
}

.btn-pos:hover {
    transform: translateY(-1px);
}

.search-productos {
    border-radius: 25px;
    border: 2px solid #e9ecef;
    padding: 10px 20px;
    font-size: 16px;
}

.search-productos:focus {
    border-color: var(--primary-color, #007bff);
    box-shadow: 0 0 10px rgba(0,123,255,0.1);
}

/* Estilos para escáner QR */
.qr-scanner-container {
    background: white;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.qr-scanner-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.8);
    z-index: 9999;
    display: none;
    align-items: center;
    justify-content: center;
}

.qr-scanner-content {
    background: white;
    border-radius: 15px;
    padding: 20px;
    max-width: 500px;
    width: 90%;
    position: relative;
}

#qr-reader {
    width: 100%;
    border-radius: 10px;
    overflow: hidden;
}

.scanner-status {
    text-align: center;
    margin-top: 15px;
    padding: 10px;
    border-radius: 8px;
}

.scanner-status.success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.scanner-status.error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.btn-scanner {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    border: none;
    color: white;
    border-radius: 10px;
    padding: 12px 20px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-scanner:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(40,167,69,0.3);
    color: white;
}

.btn-scanner:active {
    transform: translateY(0);
}

.scanner-close {
    position: absolute;
    top: 10px;
    right: 15px;
    background: none;
    border: none;
    font-size: 24px;
    color: #6c757d;
    cursor: pointer;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.3s ease;
}

.scanner-close:hover {
    background: #f8f9fa;
    color: #dc3545;
}

@media (max-width: 768px) {
    .producto-card {
        height: 100px;
        font-size: 0.9rem;
    }
    
    .pos-container {
        margin: 0 -15px;
        border-radius: 0;
    }
    
    .qr-scanner-content {
        margin: 10px;
        width: calc(100% - 20px);
    }
}
</style>

<div class="d-flex justify-content-between align-items-center mb-4 rs-wrap-sm">
    <h2><i class="fas fa-cash-register text-success"></i> Punto de Venta POS</h2>
    <div class="btn-group rs-wrap-sm">
        <button class="btn btn-outline-info btn-sm" data-bs-toggle="collapse" data-bs-target="#ayudaAtajos">
            <i class="fas fa-keyboard"></i> Atajos
        </button>
        <a href="ventas.php" class="btn btn-outline-secondary">
            <i class="fas fa-list"></i> Ver Ventas
        </a>
    </div>
</div>

<!-- Panel de ayuda de atajos (colapsable) -->
<div class="collapse mb-3" id="ayudaAtajos">
    <div class="card card-body bg-light">
        <h6><i class="fas fa-keyboard"></i> Atajos de Teclado</h6>
        <div class="row">
            <div class="col-md-3">
                <kbd>F1</kbd> Buscar productos
            </div>
            <div class="col-md-3">
                <kbd>F2</kbd> Procesar venta
            </div>
            <div class="col-md-3">
                <kbd>F3</kbd> Escáner QR
            </div>
            <div class="col-md-3">
                <kbd>Esc</kbd> Limpiar carrito
            </div>
        </div>
    </div>
</div>

    <?php if (isset($success)): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($tienda_principal || esAdmin()): ?>
    <div class="row">
        <!-- Panel de Productos -->
        <div class="col-lg-8">
            <div class="pos-container p-4 mb-4">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <h5><i class="fas fa-search"></i> Buscar Productos</h5>
                        <input type="text" id="searchProductos" class="form-control search-productos" 
                               placeholder="Buscar por nombre o código...">
                    </div>
                    <div class="col-md-2">
                        <h5><i class="fas fa-qrcode"></i> Escáner QR</h5>
                        <button class="btn btn-scanner w-100" onclick="iniciarEscanerQR()">
                            <i class="fas fa-camera"></i> Escanear
                        </button>
                    </div>
                    <div class="col-md-6">
                        <h5><i class="fas fa-store"></i> Tienda Seleccionada</h5>
                        <select id="tiendaSelect" class="form-select" onchange="cambiarTienda()">
                            <?php foreach ($tiendas as $tienda): ?>
                                <option value="<?php echo $tienda['id']; ?>" 
                                        <?php echo ($tienda['id'] == $tienda_principal) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($tienda['nombre']); ?>
                                    <?php if (isset($tienda['es_principal']) && $tienda['es_principal']): ?>
                                        (Principal)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-info d-block mt-1">
                            <i class="fas fa-info-circle"></i> Stock mostrado para: 
                            <strong><?php 
                                $tienda_seleccionada_info = array_filter($tiendas, fn($t) => $t['id'] == $tienda_principal);
                                if (!empty($tienda_seleccionada_info)) {
                                    $tienda_info = current($tienda_seleccionada_info);
                                    echo $tienda_info['nombre'];
                                    if (isset($tienda_info['es_principal']) && $tienda_info['es_principal']) {
                                        echo ' (Tu tienda principal)';
                                    }
                                } else {
                                    echo 'Tienda ID ' . $tienda_principal;
                                }
                            ?></strong>
                        </small>
                        <?php if (isset($_GET['tienda'])): ?>
                            <small class="text-success d-block">
                                <i class="fas fa-check-circle"></i> Tienda cambiada correctamente
                            </small>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="row" id="productosGrid">
                    <?php foreach ($productos as $producto): ?>
                        <div class="col-md-4 col-sm-6 mb-3 producto-item" 
                             data-nombre="<?php echo strtolower($producto['nombre']); ?>"
                             data-codigo="<?php echo strtolower($producto['codigo']); ?>"
                             data-producto='<?php echo json_encode($producto, JSON_HEX_APOS | JSON_HEX_QUOT); ?>'>
                            <div class="producto-card <?php echo ($producto['stock_disponible'] <= 0) ? 'sin-stock' : ''; ?>"
                                 onclick="<?php echo ($producto['stock_disponible'] > 0) ? 'agregarProductoFromElement(this)' : ''; ?>">
                                <div>
                                    <strong><?php echo htmlspecialchars($producto['nombre']); ?></strong><br>
                                    <small class="text-muted"><?php echo $producto['codigo']; ?></small><br>
                                    <span class="text-success fw-bold">Q<?php echo number_format($producto['precio_venta'], 2); ?></span><br>
                                    <small class="<?php echo ($producto['stock_disponible'] <= 0) ? 'text-danger' : 'text-info'; ?>">
                                        Stock: <?php echo $producto['stock_disponible']; ?>
                                        <?php if ($producto['tienda_id']): ?>
                                            <br><span class="text-muted" style="font-size: 0.8em;">
                                                T:<?php echo $producto['cantidad_total']; ?> | R:<?php echo $producto['cantidad_reparacion']; ?>
                                            </span>
                                        <?php else: ?>
                                            <br><span class="text-warning" style="font-size: 0.8em;">Sin inventario</span>
                                        <?php endif; ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Panel de Carrito -->
        <div class="col-lg-4">
            <div class="pos-container p-4">
                <h5><i class="fas fa-shopping-cart"></i> Carrito de Venta</h5>
                
                <form method="POST" id="posForm">
                    <?php echo campoCSRF(); ?>
                    <input type="hidden" name="action" value="pos_venta">
                    <input type="hidden" name="tienda_id" id="tiendaIdHidden" value="<?php echo $tienda_principal; ?>">
                    <input type="hidden" name="items" id="itemsHidden">

                    <div class="mb-3">
                        <label class="form-label">Vendedor (Opcional)</label>
                        <select name="vendedor_id" class="form-select">
                            <option value="">Sin vendedor</option>
                            <?php foreach ($vendedores as $vendedor): ?>
                                <option value="<?php echo $vendedor['id']; ?>">
                                    <?php echo htmlspecialchars($vendedor['nombre']); ?>
                                    (<?php echo $vendedor['comision_porcentaje']; ?>%)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div id="carritoItems" class="mb-3">
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-cart-plus fa-3x mb-2"></i><br>
                            Carrito vacío
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Descuento (Q)</label>
                        <input type="number" name="descuento" id="descuentoInput" class="form-control" 
                               value="0" min="0" step="0.01" onchange="calcularTotal()">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Notas</label>
                        <textarea name="notas" class="form-control" rows="2" 
                                  placeholder="Notas adicionales..."></textarea>
                    </div>

                    <div class="pos-totales mb-3">
                        <div class="d-flex justify-content-between mb-2 rs-wrap-sm">
                            <span>Subtotal:</span>
                            <span id="subtotalDisplay">Q0.00</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2 rs-wrap-sm">
                            <span>Descuento:</span>
                            <span id="descuentoDisplay">Q0.00</span>
                        </div>
                        <hr class="my-2" style="border-color: rgba(255,255,255,0.3);">
                        <div class="d-flex justify-content-between rs-wrap-sm">
                            <strong style="font-size: 1.2rem;">TOTAL:</strong>
                            <strong id="totalDisplay" style="font-size: 1.4rem;">Q0.00</strong>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-success btn-pos btn-lg" id="btnProcesar" disabled>
                            <i class="fas fa-credit-card"></i> Procesar Venta
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-pos" onclick="limpiarCarrito()">
                            <i class="fas fa-trash"></i> Limpiar Carrito
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

<!-- Modal del Escáner QR -->
<div class="qr-scanner-modal" id="qrScannerModal">
    <div class="qr-scanner-content">
        <button class="scanner-close" onclick="cerrarEscanerQR()">
            <i class="fas fa-times"></i>
        </button>
        
        <div class="text-center mb-3">
            <h4><i class="fas fa-qrcode text-success"></i> Escanear Código QR</h4>
            <p class="text-muted">Apunta la cámara hacia el código QR del producto</p>
        </div>
        
        <div id="qr-reader"></div>
        
        <div id="scanner-status" class="scanner-status" style="display: none;"></div>
        
        <div class="text-center mt-3">
            <button class="btn btn-secondary" onclick="cerrarEscanerQR()">
                <i class="fas fa-times"></i> Cancelar
            </button>
        </div>
    </div>
</div>

<script>
let carrito = [];
let subtotal = 0;
let html5QrCode = null;
let escanerActivo = false;

// Búsqueda de productos
document.getElementById('searchProductos').addEventListener('input', function() {
    const busqueda = this.value.toLowerCase();
    const productos = document.querySelectorAll('.producto-item');
    
    productos.forEach(producto => {
        const nombre = producto.dataset.nombre;
        const codigo = producto.dataset.codigo;
        
        if (nombre.includes(busqueda) || codigo.includes(busqueda)) {
            producto.style.display = 'block';
        } else {
            producto.style.display = 'none';
        }
    });
});

function agregarProductoFromElement(element) {
    const productoData = element.closest('.producto-item').dataset.producto;
    try {
        const producto = JSON.parse(productoData);
        agregarProducto(producto);
    } catch (e) {
        console.error('Error al parsear datos del producto:', e);
        alert('Error al agregar producto al carrito');
    }
}

function agregarProducto(producto) {
    console.log('Agregando producto:', producto);
    console.log('Stock disponible tipo:', typeof producto.stock_disponible, 'valor:', producto.stock_disponible);
    
    // Convertir a número por si viene como string
    const stockDisponible = parseInt(producto.stock_disponible);
    
    if (stockDisponible <= 0) {
        alert('Producto sin stock disponible');
        return;
    }
    
    // Buscar si ya existe en el carrito
    const existente = carrito.find(item => item.id === parseInt(producto.id));
    
    if (existente) {
        if (existente.cantidad < stockDisponible) {
            existente.cantidad++;
            existente.subtotal = existente.cantidad * existente.precio;
        } else {
            alert(`Stock máximo disponible: ${stockDisponible}`);
            return;
        }
    } else {
        carrito.push({
            id: parseInt(producto.id),
            nombre: producto.nombre,
            codigo: producto.codigo,
            precio: parseFloat(producto.precio_venta),
            cantidad: 1,
            stock_max: stockDisponible,
            subtotal: parseFloat(producto.precio_venta)
        });
    }
    
    actualizarCarrito();
}

function actualizarCantidad(index, nuevaCantidad) {
    if (nuevaCantidad <= 0) {
        eliminarProducto(index);
        return;
    }
    
    if (nuevaCantidad > carrito[index].stock_max) {
        alert(`Stock máximo disponible: ${carrito[index].stock_max}`);
        return;
    }
    
    carrito[index].cantidad = parseInt(nuevaCantidad);
    carrito[index].subtotal = carrito[index].cantidad * carrito[index].precio;
    
    actualizarCarrito();
}

function eliminarProducto(index) {
    carrito.splice(index, 1);
    actualizarCarrito();
}

function actualizarCarrito() {
    const container = document.getElementById('carritoItems');
    
    if (carrito.length === 0) {
        container.innerHTML = `
            <div class="text-center text-muted py-4">
                <i class="fas fa-cart-plus fa-3x mb-2"></i><br>
                Carrito vacío
            </div>
        `;
        document.getElementById('btnProcesar').disabled = true;
    } else {
        let html = '';
        carrito.forEach((item, index) => {
            html += `
                <div class="carrito-item">
                    <div class="d-flex justify-content-between align-items-center mb-2 rs-wrap-sm">
                        <strong>${item.nombre}</strong>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="eliminarProducto(${index})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    <div class="row g-2">
                        <div class="col-4">
                            <input type="number" class="form-control form-control-sm" 
                                   value="${item.cantidad}" min="1" max="${item.stock_max}"
                                   onchange="actualizarCantidad(${index}, this.value)">
                        </div>
                        <div class="col-4">
                            <span class="form-control form-control-sm text-center">Q${item.precio.toFixed(2)}</span>
                        </div>
                        <div class="col-4">
                            <span class="form-control form-control-sm text-center fw-bold">Q${item.subtotal.toFixed(2)}</span>
                        </div>
                    </div>
                </div>
            `;
        });
        container.innerHTML = html;
        document.getElementById('btnProcesar').disabled = false;
    }
    
    calcularTotal();
    
    // Actualizar campo hidden para envío
    document.getElementById('itemsHidden').value = JSON.stringify(carrito);
}

function calcularTotal() {
    subtotal = carrito.reduce((sum, item) => sum + item.subtotal, 0);
    const descuento = parseFloat(document.getElementById('descuentoInput').value) || 0;
    const total = Math.max(0, subtotal - descuento);
    
    document.getElementById('subtotalDisplay').textContent = `Q${subtotal.toFixed(2)}`;
    document.getElementById('descuentoDisplay').textContent = `Q${descuento.toFixed(2)}`;
    document.getElementById('totalDisplay').textContent = `Q${total.toFixed(2)}`;
}

function limpiarCarrito() {
    if (carrito.length > 0 && confirm('¿Confirmas limpiar el carrito?')) {
        carrito = [];
        actualizarCarrito();
    }
}

function cambiarTienda() {
    const tiendaId = document.getElementById('tiendaSelect').value;
    
    // Verificar si hay productos en carrito
    if (carrito.length > 0) {
        if (!confirm('Cambiar de tienda limpiará el carrito actual. ¿Continuar?')) {
            // Restaurar selección anterior
            document.getElementById('tiendaSelect').value = '<?php echo $tienda_principal; ?>';
            return;
        }
    }
    
    // Actualizar campo oculto
    document.getElementById('tiendaIdHidden').value = tiendaId;
    
    // Mostrar loading
    const select = document.getElementById('tiendaSelect');
    const originalText = select.options[select.selectedIndex].text;
    select.options[select.selectedIndex].text = 'Cargando...';
    select.disabled = true;
    
    // Recargar página con nueva tienda
    window.location.href = `pos.php?tienda=${tiendaId}`;
}

// Variable para controlar si se está procesando una venta
let procesandoVenta = false;

// Confirmar antes de cerrar si hay productos en carrito
window.addEventListener('beforeunload', function(e) {
    if (carrito.length > 0 && !procesandoVenta) {
        e.preventDefault();
        e.returnValue = '';
    }
});

// Marcar cuando se procesa una venta
document.getElementById('posForm').addEventListener('submit', function(e) {
    if (carrito.length === 0) {
        e.preventDefault();
        alert('No hay productos en el carrito');
        return false;
    }
    
    procesandoVenta = true;
    
    // Cambiar texto del botón mientras procesa
    const btnProcesar = document.getElementById('btnProcesar');
    btnProcesar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
    btnProcesar.disabled = true;
    
    // Actualizar datos del carrito antes de enviar
    document.getElementById('itemsHidden').value = JSON.stringify(carrito);
});

// Atajos de teclado
document.addEventListener('keydown', function(e) {
    // F1 - Enfocar búsqueda
    if (e.key === 'F1') {
        e.preventDefault();
        document.getElementById('searchProductos').focus();
    }
    
    // F2 - Procesar venta
    if (e.key === 'F2' && carrito.length > 0) {
        e.preventDefault();
        document.getElementById('posForm').submit();
    }
    
    // F3 - Abrir escáner QR
    if (e.key === 'F3') {
        e.preventDefault();
        iniciarEscanerQR();
    }
    
    // Escape - Limpiar carrito (solo si escáner no está activo)
    if (e.key === 'Escape' && carrito.length > 0 && !escanerActivo) {
        limpiarCarrito();
    }
});

// Inicializar descuento
document.getElementById('descuentoInput').addEventListener('input', calcularTotal);

// Inicialización al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    // Limpiar carrito si la venta fue exitosa
    <?php if (isset($venta_exitosa) && $venta_exitosa): ?>
    carrito = [];
    actualizarCarrito();
    procesandoVenta = false;
    <?php endif; ?>
    
    // Asegurar que el select de tienda esté correctamente seleccionado
    const tiendaSelect = document.getElementById('tiendaSelect');
    const tiendaIdHidden = document.getElementById('tiendaIdHidden');
    
    // Sincronizar valores
    tiendaSelect.value = '<?php echo $tienda_principal; ?>';
    tiendaIdHidden.value = '<?php echo $tienda_principal; ?>';
    
    console.log('POS iniciado - Tienda actual:', tiendaSelect.value);
});

// ========== FUNCIONES DEL ESCÁNER QR ==========

function iniciarEscanerQR() {
    if (escanerActivo) {
        return;
    }
    
    const modal = document.getElementById('qrScannerModal');
    const statusDiv = document.getElementById('scanner-status');
    
    // Mostrar modal
    modal.style.display = 'flex';
    statusDiv.style.display = 'none';
    statusDiv.className = 'scanner-status';
    
    // Inicializar escáner
    html5QrCode = new Html5Qrcode("qr-reader");
    escanerActivo = true;
    
    // Configuración del escáner
    const config = {
        fps: 10,
        qrbox: { width: 250, height: 250 },
        aspectRatio: 1.0,
        disableFlip: false
    };
    
    // Función de éxito al escanear
    function onScanSuccess(decodedText, decodedResult) {
        console.log('QR Escaneado:', decodedText);
        
        // Buscar producto por código QR
        buscarProductoPorQR(decodedText);
    }
    
    // Función de error (opcional - para debug)
    function onScanError(errorMessage) {
        // Ignorar errores menores
    }
    
    // Intentar obtener cámara trasera primero
    Html5Qrcode.getCameras().then(devices => {
        if (devices && devices.length) {
            let cameraId = devices[0].id;
            
            // Buscar cámara trasera
            const backCamera = devices.find(device => 
                device.label.toLowerCase().includes('back') || 
                device.label.toLowerCase().includes('rear') ||
                device.label.toLowerCase().includes('environment')
            );
            
            if (backCamera) {
                cameraId = backCamera.id;
            }
            
            // Iniciar escáner
            html5QrCode.start(cameraId, config, onScanSuccess, onScanError)
                .then(() => {
                    mostrarStatusEscaner('Escáner iniciado. Apunta hacia el código QR', 'info');
                })
                .catch(err => {
                    console.error('Error iniciando escáner:', err);
                    mostrarStatusEscaner('Error iniciando la cámara: ' + err, 'error');
                    escanerActivo = false;
                });
        } else {
            mostrarStatusEscaner('No se encontraron cámaras disponibles', 'error');
            escanerActivo = false;
        }
    }).catch(err => {
        console.error('Error obteniendo cámaras:', err);
        mostrarStatusEscaner('Error accediendo a las cámaras: ' + err, 'error');
        escanerActivo = false;
    });
}

function cerrarEscanerQR() {
    if (html5QrCode && escanerActivo) {
        html5QrCode.stop().then(() => {
            html5QrCode.clear();
            html5QrCode = null;
            escanerActivo = false;
            document.getElementById('qrScannerModal').style.display = 'none';
        }).catch(err => {
            console.error('Error cerrando escáner:', err);
            html5QrCode = null;
            escanerActivo = false;
            document.getElementById('qrScannerModal').style.display = 'none';
        });
    } else {
        document.getElementById('qrScannerModal').style.display = 'none';
        escanerActivo = false;
    }
}

function mostrarStatusEscaner(mensaje, tipo = 'info') {
    const statusDiv = document.getElementById('scanner-status');
    statusDiv.style.display = 'block';
    statusDiv.textContent = mensaje;
    statusDiv.className = 'scanner-status ' + tipo;
    
    if (tipo === 'success') {
        setTimeout(() => {
            statusDiv.style.display = 'none';
        }, 2000);
    }
}

function buscarProductoPorQR(codigoQR) {
    mostrarStatusEscaner('Buscando producto...', 'info');
    
    // Buscar el producto en la lista de productos cargados
    const productos = <?php echo json_encode($productos); ?>;
    let productoEncontrado = null;
    
    // Buscar por código QR, código de producto, o ID
    for (let producto of productos) {
        if (producto.codigo === codigoQR || 
            producto.id == codigoQR ||
            codigoQR.includes(producto.codigo)) {
            productoEncontrado = producto;
            break;
        }
    }
    
    if (productoEncontrado) {
        // Producto encontrado - agregarlo al carrito
        if (parseInt(productoEncontrado.stock_disponible) <= 0) {
            mostrarStatusEscaner('Producto sin stock disponible', 'error');
            return;
        }
        
        // Agregar al carrito
        agregarProducto(productoEncontrado);
        
        mostrarStatusEscaner(`✅ ${productoEncontrado.nombre} agregado al carrito`, 'success');
        
        // Cerrar escáner después de un momento
        setTimeout(() => {
            cerrarEscanerQR();
        }, 1500);
        
    } else {
        // Buscar en servidor si no se encuentra localmente
        buscarProductoEnServidor(codigoQR);
    }
}

function buscarProductoEnServidor(codigoQR) {
    mostrarStatusEscaner('Consultando servidor...', 'info');
    
    const tiendaId = document.getElementById('tiendaSelect').value;
    
    fetch('ajax/buscar_producto_qr.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            codigo_qr: codigoQR,
            tienda_id: tiendaId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.producto) {
            const producto = data.producto;
            
            if (parseInt(producto.stock_disponible) <= 0) {
                mostrarStatusEscaner('Producto sin stock disponible', 'error');
                return;
            }
            
            // Agregar al carrito
            agregarProducto(producto);
            
            mostrarStatusEscaner(`✅ ${producto.nombre} agregado al carrito`, 'success');
            
            // Cerrar escáner después de un momento
            setTimeout(() => {
                cerrarEscanerQR();
            }, 1500);
            
        } else {
            mostrarStatusEscaner('Producto no encontrado: ' + codigoQR, 'error');
        }
    })
    .catch(error => {
        console.error('Error buscando producto:', error);
        mostrarStatusEscaner('Error de conexión', 'error');
    });
}

// Cerrar escáner con tecla Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && escanerActivo) {
        cerrarEscanerQR();
    }
});

// Cerrar escáner al hacer clic fuera del modal
document.getElementById('qrScannerModal').addEventListener('click', function(e) {
    if (e.target === this) {
        cerrarEscanerQR();
    }
});

</script>

<?php include 'includes/layout_footer.php'; ?>
