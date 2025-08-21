<?php
$titulo = "Traslados - Sistema de Inventarios";
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'includes/config_functions.php';

verificarLogin();
verificarPermiso('inventarios_transferir');

$database = new Database();
$db = $database->getConnection();

if ($_POST && isset($_POST['action'])) {
    if ($_POST['action'] == 'transferir') {
        $tienda_origen = $_POST['tienda_origen'];
        $tienda_destino = $_POST['tienda_destino'];
        $producto_id = $_POST['producto_id'];
        $cantidad = $_POST['cantidad'];
        $motivo = $_POST['motivo'] ?? 'Traslado entre tiendas';
        $usuario_id = $_SESSION['usuario_id'];
        
        // Validación básica
        if ($tienda_origen == $tienda_destino) {
            $error = "La tienda origen y destino no pueden ser la misma";
        } else {
            $db->beginTransaction();
            
            try {
                // Verificar stock disponible: cantidad - cantidad_reparacion
                $query_check = "SELECT cantidad, COALESCE(cantidad_reparacion,0) AS cantidad_reparacion FROM inventarios WHERE tienda_id = ? AND producto_id = ?";
                $stmt_check = $db->prepare($query_check);
                $stmt_check->execute([$tienda_origen, $producto_id]);
                $inventario_origen = $stmt_check->fetch(PDO::FETCH_ASSOC);
                $disponible_origen = $inventario_origen ? ((int)$inventario_origen['cantidad'] - (int)$inventario_origen['cantidad_reparacion']) : 0;
                if ($disponible_origen < $cantidad) {
                    throw new Exception("No hay suficiente stock en la tienda origen");
                }
                
                // Actualizar inventario origen
                $query_update_origen = "UPDATE inventarios SET cantidad = cantidad - ? WHERE tienda_id = ? AND producto_id = ?";
                $stmt_update_origen = $db->prepare($query_update_origen);
                $stmt_update_origen->execute([$cantidad, $tienda_origen, $producto_id]);
                
                // Verificar si existe inventario en destino
                $query_check_destino = "SELECT id FROM inventarios WHERE tienda_id = ? AND producto_id = ?";
                $stmt_check_destino = $db->prepare($query_check_destino);
                $stmt_check_destino->execute([$tienda_destino, $producto_id]);
                
                if ($stmt_check_destino->rowCount() > 0) {
                    // Actualizar inventario existente
                    $query_update_destino = "UPDATE inventarios SET cantidad = cantidad + ? WHERE tienda_id = ? AND producto_id = ?";
                    $stmt_update_destino = $db->prepare($query_update_destino);
                    $stmt_update_destino->execute([$cantidad, $tienda_destino, $producto_id]);
                } else {
                    // Crear nuevo registro de inventario
                    $query_insert_destino = "INSERT INTO inventarios (tienda_id, producto_id, cantidad, cantidad_reparacion) VALUES (?, ?, ?, 0)";
                    $stmt_insert_destino = $db->prepare($query_insert_destino);
                    $stmt_insert_destino->execute([$tienda_destino, $producto_id, $cantidad]);
                }
                
                // Registrar movimiento
                $query_movimiento = "INSERT INTO movimientos_inventario (tipo, producto_id, tienda_origen_id, tienda_destino_id, cantidad, motivo, usuario_id) 
                                    VALUES ('transferencia', ?, ?, ?, ?, ?, ?)";
                $stmt_movimiento = $db->prepare($query_movimiento);
                $stmt_movimiento->execute([$producto_id, $tienda_origen, $tienda_destino, $cantidad, $motivo, $usuario_id]);
                
                $db->commit();
                $success = "Traslado realizado exitosamente";
                
            } catch (Exception $e) {
                $db->rollBack();
                $error = $e->getMessage();
            }
        }
    }
}

// Obtener datos para formularios
$query_tiendas = "SELECT * FROM tiendas WHERE activo = 1 ORDER BY nombre";
$stmt_tiendas = $db->prepare($query_tiendas);
$stmt_tiendas->execute();
$tiendas = $stmt_tiendas->fetchAll(PDO::FETCH_ASSOC);

// Obtener productos con su inventario total por tienda
$query_productos = "SELECT p.*, 
    (SELECT GROUP_CONCAT(CONCAT(t.nombre, ':', COALESCE(i.cantidad - COALESCE(i.cantidad_reparacion, 0), 0)) SEPARATOR ' | ') 
     FROM tiendas t 
     LEFT JOIN inventarios i ON t.id = i.tienda_id AND i.producto_id = p.id 
     WHERE t.activo = 1) as stock_por_tienda
FROM productos p 
WHERE p.activo = 1 
ORDER BY p.nombre";
$stmt_productos = $db->prepare($query_productos);
$stmt_productos->execute();
$productos = $stmt_productos->fetchAll(PDO::FETCH_ASSOC);

// Obtener historial de traslados
$query_traslados = "SELECT mi.*, 
                           t_origen.nombre as tienda_origen_nombre,
                           t_destino.nombre as tienda_destino_nombre,
                           p.nombre as producto_nombre,
                           p.codigo as producto_codigo,
                           u.nombre as usuario_nombre
                    FROM movimientos_inventario mi
                    LEFT JOIN tiendas t_origen ON mi.tienda_origen_id = t_origen.id
                    LEFT JOIN tiendas t_destino ON mi.tienda_destino_id = t_destino.id
                    JOIN productos p ON mi.producto_id = p.id
                    JOIN usuarios u ON mi.usuario_id = u.id
                    WHERE mi.tipo_movimiento = 'transferencia'
                    ORDER BY mi.fecha DESC
                    LIMIT 50";
$stmt_traslados = $db->prepare($query_traslados);
$stmt_traslados->execute();
$traslados = $stmt_traslados->fetchAll(PDO::FETCH_ASSOC);

require_once 'includes/layout_header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>
            <i class="fas fa-exchange-alt"></i>
            <span class="editable" data-label="traslados_titulo">Traslados de Productos</span>
        </h2>
        <div class="btn-group">
            <button class="btn btn-outline-primary" onclick="exportarTraslados()">
                <i class="fas fa-download"></i> Exportar
            </button>
        </div>
    </div>
    
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
        <!-- Formulario de Traslado -->
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-truck"></i>
                        Transferir Productos entre Tiendas
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" id="formTraslado">
                        <input type="hidden" name="action" value="transferir">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="fas fa-store text-warning"></i>
                                    Tienda Origen
                                </label>
                                <select class="form-select" name="tienda_origen" id="tiendaOrigen" required>
                                    <option value="">Seleccionar origen...</option>
                                    <?php foreach ($tiendas as $tienda): ?>
                                        <option value="<?php echo $tienda['id']; ?>">
                                            <?php echo $tienda['nombre']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="fas fa-store text-success"></i>
                                    Tienda Destino
                                </label>
                                <select class="form-select" name="tienda_destino" id="tiendaDestino" required>
                                    <option value="">Seleccionar destino...</option>
                                    <?php foreach ($tiendas as $tienda): ?>
                                        <option value="<?php echo $tienda['id']; ?>">
                                            <?php echo $tienda['nombre']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-box"></i>
                                Producto
                            </label>
                            <select class="form-select" name="producto_id" id="productoSelect" required>
                                <option value="">Seleccionar producto...</option>
                                <?php foreach ($productos as $producto): ?>
                                    <option value="<?php echo $producto['id']; ?>" 
                                            data-tipo="<?php echo $producto['tipo']; ?>"
                                            data-stock="<?php echo htmlspecialchars($producto['stock_por_tienda'] ?? ''); ?>">
                                        <?php 
                                        $stock_info = '';
                                        if ($producto['stock_por_tienda']) {
                                            $stock_info = ' [Stock: ' . $producto['stock_por_tienda'] . ']';
                                        } else {
                                            $stock_info = ' [Sin stock]';
                                        }
                                        echo '[' . $producto['codigo'] . '] ' . $producto['nombre'] . ' (' . ucfirst($producto['tipo']) . ')' . $stock_info; 
                                        ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div id="stockInfo" class="mt-2"></div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-sort-numeric-up"></i>
                                Cantidad a Trasladar
                            </label>
                            <input type="number" class="form-control" name="cantidad" id="cantidadInput" min="1" required>
                            <div class="form-text">La cantidad debe ser mayor a 0</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-comment"></i>
                                Motivo del Traslado
                            </label>
                            <select class="form-select" name="motivo" id="motivoSelect">
                                <option value="Reposición de stock">Reposición de stock</option>
                                <option value="Redistribución de inventario">Redistribución de inventario</option>
                                <option value="Solicitud de tienda">Solicitud de tienda</option>
                                <option value="Optimización de espacios">Optimización de espacios</option>
                                <option value="Otro">Otro motivo</option>
                            </select>
                            <input type="text" class="form-control mt-2 d-none" id="motivoOtro" placeholder="Especificar motivo...">
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-paper-plane"></i>
                                Realizar Traslado
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Historial de Traslados -->
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-history"></i>
                        Historial de Traslados Recientes
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($traslados)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No hay traslados registrados</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Producto</th>
                                        <th>Origen → Destino</th>
                                        <th>Cantidad</th>
                                        <th>Usuario</th>
                                        <th>Motivo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($traslados as $traslado): ?>
                                    <tr>
                                        <td>
                                            <small>
                                                <?php echo date('d/m/Y', strtotime($traslado['fecha'])); ?><br>
                                                <span class="text-muted"><?php echo date('H:i:s', strtotime($traslado['fecha'])); ?></span>
                                            </small>
                                        </td>
                                        <td>
                                            <strong>[<?php echo $traslado['producto_codigo']; ?>]</strong><br>
                                            <small><?php echo $traslado['producto_nombre']; ?></small>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <span class="badge bg-warning me-2"><?php echo $traslado['tienda_origen_nombre']; ?></span>
                                                <i class="fas fa-arrow-right mx-1"></i>
                                                <span class="badge bg-success ms-2"><?php echo $traslado['tienda_destino_nombre']; ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary fs-6">
                                                <?php echo $traslado['cantidad']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small><?php echo $traslado['usuario_nombre']; ?></small>
                                        </td>
                                        <td>
                                            <small class="text-muted"><?php echo $traslado['motivo']; ?></small>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="text-center mt-3">
                            <small class="text-muted">
                                Mostrando los últimos 50 traslados. 
                                <a href="#" onclick="verTodosLosTraslados()" class="text-primary">Ver todos</a>
                            </small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Verificar stock disponible cuando cambie la tienda origen o producto
document.getElementById('tiendaOrigen').addEventListener('change', verificarStock);
document.getElementById('productoSelect').addEventListener('change', verificarStock);

// Manejar motivo "Otro"
document.getElementById('motivoSelect').addEventListener('change', function() {
    const motivoOtro = document.getElementById('motivoOtro');
    if (this.value === 'Otro') {
        motivoOtro.classList.remove('d-none');
        motivoOtro.setAttribute('name', 'motivo');
        this.removeAttribute('name');
    } else {
        motivoOtro.classList.add('d-none');
        motivoOtro.removeAttribute('name');
        this.setAttribute('name', 'motivo');
    }
});

// Validar que origen y destino sean diferentes
document.getElementById('tiendaDestino').addEventListener('change', function() {
    const origen = document.getElementById('tiendaOrigen').value;
    if (origen && origen === this.value) {
        alert('La tienda origen y destino deben ser diferentes');
        this.value = '';
    }
});

document.getElementById('tiendaOrigen').addEventListener('change', function() {
    const destino = document.getElementById('tiendaDestino').value;
    if (destino && destino === this.value) {
        alert('La tienda origen y destino deben ser diferentes');
        this.value = '';
    }
});

function verificarStock() {
    const tiendaOrigenSelect = document.getElementById('tiendaOrigen');
    const productoSelect = document.getElementById('productoSelect');
    const stockInfo = document.getElementById('stockInfo');
    const cantidadInput = document.querySelector('input[name="cantidad"]');
    
    if (!tiendaOrigenSelect.value || !productoSelect.value) {
        stockInfo.innerHTML = '';
        if (cantidadInput) {
            cantidadInput.max = '';
            cantidadInput.title = '';
        }
        return;
    }
    
    const tiendaNombre = tiendaOrigenSelect.options[tiendaOrigenSelect.selectedIndex].text;
    const option = productoSelect.options[productoSelect.selectedIndex];
    const stockData = option.dataset.stock;
    
    // Buscar el stock para la tienda origen seleccionada
    let stockDisponible = 0;
    if (stockData) {
        const stocks = stockData.split(' | ');
        const stockTienda = stocks.find(s => s.startsWith(tiendaNombre + ':'));
        if (stockTienda) {
            stockDisponible = parseInt(stockTienda.split(':')[1]) || 0;
        }
    }
    
    // Mostrar información del stock
    if (stockDisponible > 0) {
        stockInfo.innerHTML = `
            <div class="alert alert-success small">
                <i class="fas fa-box text-success"></i> 
                Stock disponible en <strong>${tiendaNombre}</strong>: <strong>${stockDisponible}</strong> unidades
            </div>`;
        
        // Actualizar límite máximo del input de cantidad
        if (cantidadInput) {
            cantidadInput.max = stockDisponible;
            cantidadInput.title = `Máximo disponible: ${stockDisponible}`;
            
            // Ajustar cantidad si excede el máximo
            if (parseInt(cantidadInput.value) > stockDisponible) {
                cantidadInput.value = stockDisponible;
            }
        }
    } else {
        stockInfo.innerHTML = `
            <div class="alert alert-danger small">
                <i class="fas fa-exclamation-triangle text-danger"></i> 
                Sin stock disponible en <strong>${tiendaNombre}</strong>
            </div>`;
        
        // Limpiar límites
        if (cantidadInput) {
            cantidadInput.max = 0;
            cantidadInput.value = '';
            cantidadInput.title = 'Sin stock disponible';
        }
    }
    
    // Mostrar stock de todas las tiendas
    if (stockData) {
        const stocks = stockData.split(' | ');
        let detalleStock = '<div class="mt-2"><small class="text-muted"><strong>Stock por tienda:</strong><br>';
        stocks.forEach(stock => {
            const [tienda, cantidad] = stock.split(':');
            const cantidad_num = parseInt(cantidad) || 0;
            const color = cantidad_num > 0 ? 'text-success' : 'text-muted';
            detalleStock += `<span class="${color}">${tienda}: ${cantidad_num}</span><br>`;
        });
        detalleStock += '</small></div>';
        stockInfo.innerHTML += detalleStock;
    }
}

function exportarTraslados() {
    alert('Funcionalidad de exportación en desarrollo');
}

function verTodosLosTraslados() {
    alert('Funcionalidad para ver todos los traslados en desarrollo');
}

// Validación del formulario
document.getElementById('formTraslado').addEventListener('submit', function(e) {
    const origen = document.getElementById('tiendaOrigen').value;
    const destino = document.getElementById('tiendaDestino').value;
    const cantidadInput = document.querySelector('input[name="cantidad"]');
    const cantidad = parseInt(cantidadInput.value) || 0;
    const maxStock = parseInt(cantidadInput.max) || 0;
    
    if (origen === destino) {
        e.preventDefault();
        alert('La tienda origen y destino deben ser diferentes');
        return false;
    }
    
    if (cantidad <= 0) {
        e.preventDefault();
        alert('La cantidad debe ser mayor a 0');
        return false;
    }
    
    if (cantidad > maxStock) {
        e.preventDefault();
        alert(`La cantidad solicitada (${cantidad}) excede el stock disponible (${maxStock})`);
        return false;
    }
    
    if (maxStock === 0) {
        e.preventDefault();
        alert('No hay stock disponible en la tienda origen para realizar el traslado');
        return false;
    }
});
</script>

<?php require_once 'includes/layout_footer.php'; ?>
