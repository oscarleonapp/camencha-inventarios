<?php
$titulo = "Control de Stock - Sistema de Inventarios";
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'includes/config_functions.php';
require_once 'includes/csrf_protection.php';
require_once 'includes/tienda_security.php';

verificarLogin();
verificarPermiso('inventarios_ver');

$database = new Database();
$db = $database->getConnection();

if ($_POST && isset($_POST['action'])) {
    // Validar CSRF token
    validarCSRF();
    
    if ($_POST['action'] == 'ajustar_inventario') {
        $tienda_id = $_POST['tienda_id'];
        $producto_id = $_POST['producto_id'];
        $nueva_cantidad = (int)$_POST['nueva_cantidad']; // cantidad disponible deseada
        $usuario_id = $_SESSION['usuario_id'];
        
        // Validar que el usuario tiene acceso a esta tienda
        try {
            validarAccesoTienda($db, $usuario_id, $tienda_id, 'ajustar inventario');
        } catch (Exception $e) {
            $error = $e->getMessage();
            goto skip_process;
        }
        
        // Tomamos cantidad total y en reparación para calcular la disponible actual
        $query_check = "SELECT cantidad, COALESCE(cantidad_reparacion, 0) AS cantidad_reparacion FROM inventarios WHERE tienda_id = ? AND producto_id = ?";
        $stmt_check = $db->prepare($query_check);
        $stmt_check->execute([$tienda_id, $producto_id]);
        $inventario_actual = $stmt_check->fetch(PDO::FETCH_ASSOC);
        
        if ($inventario_actual) {
            $actual_disponible = (int)$inventario_actual['cantidad'] - (int)$inventario_actual['cantidad_reparacion'];
            $diferencia = $nueva_cantidad - $actual_disponible; // ajuste sobre el disponible
            $tipo_movimiento = $diferencia > 0 ? 'entrada' : 'salida';
            $cantidad_movimiento = abs($diferencia);
            
            // Nuevo total mantiene unidades en reparación intactas
            $nuevo_total = (int)$inventario_actual['cantidad'] + $diferencia;
            if ($nuevo_total < 0) { $nuevo_total = 0; }
            
            $query_update = "UPDATE inventarios SET cantidad = ? WHERE tienda_id = ? AND producto_id = ?";
            $stmt_update = $db->prepare($query_update);
            $stmt_update->execute([$nuevo_total, $tienda_id, $producto_id]);
            
            if ($cantidad_movimiento > 0) {
                $query_movimiento = "INSERT INTO movimientos_inventario (tipo_movimiento, producto_id, tienda_destino_id, cantidad, motivo, usuario_id) 
                                    VALUES (?, ?, ?, ?, 'Ajuste de inventario', ?)";
                $stmt_movimiento = $db->prepare($query_movimiento);
                $stmt_movimiento->execute([$tipo_movimiento, $producto_id, $tienda_id, $cantidad_movimiento, $usuario_id]);
            }
        } else {
            // Crear registro nuevo: todo disponible, nada en reparación
            $query_insert = "INSERT INTO inventarios (tienda_id, producto_id, cantidad, cantidad_reparacion) VALUES (?, ?, ?, 0)";
            $stmt_insert = $db->prepare($query_insert);
            $stmt_insert->execute([$tienda_id, $producto_id, $nueva_cantidad]);
            
            if ($nueva_cantidad > 0) {
                $query_movimiento = "INSERT INTO movimientos_inventario (tipo_movimiento, producto_id, tienda_destino_id, cantidad, motivo, usuario_id) 
                                    VALUES ('entrada', ?, ?, ?, 'Inventario inicial', ?)";
                $stmt_movimiento = $db->prepare($query_movimiento);
                $stmt_movimiento->execute([$producto_id, $tienda_id, $nueva_cantidad, $usuario_id]);
            }
        }
        
        $success = "Inventario ajustado exitosamente";
    }
}

skip_process:

// Obtener filtros de tienda para el usuario actual
$usuario_id = $_SESSION['usuario_id'];
$filtro_tiendas = getFiltroTiendas($db, $usuario_id, 'i.tienda_id');

$where_adicional = '';
$params_inventarios = [];
if (!empty($filtro_tiendas['where'])) {
    $where_adicional = ' AND ' . $filtro_tiendas['where'];
    $params_inventarios = $filtro_tiendas['params'];
}

$query_inventarios = "SELECT i.*, t.nombre as tienda_nombre, p.codigo, p.nombre as producto_nombre, p.tipo,
                             COALESCE(i.cantidad_reparacion, 0) as cantidad_reparacion,
                             (i.cantidad - COALESCE(i.cantidad_reparacion, 0)) as cantidad_disponible,
                             i.cantidad as cantidad_total,
                             i.cantidad_minima as cantidad_minima,
                             (SELECT COUNT(*) FROM reparaciones r WHERE r.tienda_id = i.tienda_id AND r.producto_id = i.producto_id AND r.estado IN ('enviado', 'en_reparacion')) as reparaciones_activas
                      FROM inventarios i 
                      JOIN tiendas t ON i.tienda_id = t.id 
                      JOIN productos p ON i.producto_id = p.id 
                      WHERE t.activo = 1 AND p.activo = 1 $where_adicional
                      ORDER BY t.nombre, p.nombre";
$stmt_inventarios = $db->prepare($query_inventarios);
$stmt_inventarios->execute($params_inventarios);
$inventarios = $stmt_inventarios->fetchAll(PDO::FETCH_ASSOC);

// Solo mostrar tiendas asignadas al usuario
$tiendas = getTiendasUsuarioCompleta($db, $usuario_id);

$query_productos = "SELECT * FROM productos WHERE activo = 1 ORDER BY nombre";
$stmt_productos = $db->prepare($query_productos);
$stmt_productos->execute();
$productos = $stmt_productos->fetchAll(PDO::FETCH_ASSOC);

include 'includes/layout_header.php';
?>

    <div class="d-flex justify-content-between align-items-center mb-4 rs-wrap-sm">
        <h2><i class="fas fa-boxes"></i> <span class="editable" data-label="inventarios_titulo">Control de Stock</span></h2>
        <div class="btn-group rs-wrap-sm">
            <button class="btn btn-outline-primary" onclick="exportarInventario()">
                <i class="fas fa-download"></i> Exportar
            </button>
        </div>
    </div>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <!-- Formulario de Ajuste de Inventario - Compacto -->
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h6 class="mb-0">
                    <i class="fas fa-edit"></i>
                    Ajustar Inventario
                </h6>
            </div>
            <div class="card-body">
                <form method="POST" class="row g-3 align-items-end">
                    <input type="hidden" name="action" value="ajustar_inventario">
                    <?php echo campoCSRF(); ?>
                    
                    <div class="col-md-3">
                        <label class="form-label small">
                            <i class="fas fa-store"></i> Tienda
                        </label>
                        <select class="form-select form-select-sm" name="tienda_id" required>
                            <option value="">Seleccionar Tienda</option>
                            <?php foreach ($tiendas as $tienda): ?>
                                <option value="<?php echo $tienda['id']; ?>">
                                    <?php echo $tienda['nombre']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label small">
                            <i class="fas fa-box"></i> Producto
                        </label>
                        <div class="position-relative">
                            <input type="text" class="form-control form-control-sm" id="producto-search" 
                                   placeholder="Buscar por código o nombre..." autocomplete="off">
                            <input type="hidden" name="producto_id" id="producto-id" required>
                            <div id="producto-results" class="position-absolute w-100 bg-white border rounded shadow-sm d-none" style="z-index: 1050; max-height: 200px; overflow-y: auto;">
                                <!-- Resultados de búsqueda aquí -->
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label small">
                            <i class="fas fa-sort-numeric-up"></i> Nueva Cantidad
                        </label>
                        <input type="number" class="form-control form-control-sm" name="nueva_cantidad" min="0" required placeholder="0">
                    </div>
                    
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-success btn-sm w-100">
                            <i class="fas fa-save"></i> Ajustar
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Estado del Inventario por Tienda</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive-md">
                            <table class="table align-middle accessibility-fix">
                                <thead class="table-light">
                                    <tr>
                                        <th>Tienda</th>
                                        <th>Código</th>
                                        <th>Producto</th>
                                        <th>Tipo</th>
                                        <th>Disponible</th>
                                        <th>En Reparación</th>
                                        <th>Total</th>
                                        <th>Mínimo</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($inventarios as $inventario): ?>
                                    <tr data-tienda-id="<?php echo $inventario['tienda_id']; ?>" data-producto-id="<?php echo $inventario['producto_id']; ?>" data-stock-min="<?php echo $inventario['cantidad_minima']; ?>">
                                        <td><?php echo $inventario['tienda_nombre']; ?></td>
                                        <td><?php echo $inventario['codigo']; ?></td>
                                        <td><?php echo $inventario['producto_nombre']; ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $inventario['tipo'] == 'elemento' ? 'primary' : 'success'; ?>">
                                                <?php echo ucfirst($inventario['tipo']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="fw-bold editable-stock" 
                                                  data-disponible="<?php echo $inventario['cantidad_disponible']; ?>"
                                                  data-reparacion="<?php echo (int)$inventario['cantidad_reparacion']; ?>"
                                                  title="Click para editar"
                                                 >
                                                <?php echo $inventario['cantidad_disponible']; ?>
                                            </span>
                                            <button class="btn btn-sm btn-link text-decoration-none edit-stock-btn" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </td>
                                        <td>
                                            <span class="badge bg-warning text-dark me-1">
                                                <i class="fas fa-tools"></i>
                                                <span class="editable-rep"><?php echo (int)$inventario['cantidad_reparacion']; ?></span>
                                            </span>
                                            <button class="btn btn-sm btn-link text-decoration-none edit-rep-btn" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php if ($inventario['reparaciones_activas'] > 0): ?>
                                                <br><small class="text-muted" style="color: #6c757d !important;"><?php echo $inventario['reparaciones_activas']; ?> activas</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong class="cantidad-total-cell"><?php echo $inventario['cantidad_total']; ?></strong>
                                        </td>
                                        <td><?php echo $inventario['cantidad_minima']; ?></td>
                                        <td class="estado-cell">
                                            <?php 
                                            if ($inventario['cantidad_disponible'] <= 0 && $inventario['cantidad_reparacion'] > 0) {
                                                echo '<span class="badge bg-danger">Sin Stock (En Reparación)</span>';
                                            } elseif ($inventario['cantidad_disponible'] <= $inventario['cantidad_minima'] && $inventario['cantidad_reparacion'] > 0) {
                                                echo '<span class="badge bg-warning text-dark">Stock Bajo + Reparación</span>';
                                            } elseif ($inventario['cantidad_disponible'] <= $inventario['cantidad_minima']) {
                                                echo '<span class="badge bg-danger">Stock Bajo</span>';
                                            } elseif ($inventario['cantidad_disponible'] <= ($inventario['cantidad_minima'] * 2)) {
                                                echo '<span class="badge bg-warning text-dark">Stock Medio</span>';
                                            } elseif ($inventario['cantidad_reparacion'] > 0) {
                                                echo '<span class="badge bg-info">Stock Normal + Reparación</span>';
                                            } else {
                                                echo '<span class="badge bg-success">Stock Bueno</span>';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
<script>
// Datos de productos para búsqueda
const productos = <?php echo json_encode($productos); ?>;

// Búsqueda de productos
document.getElementById('producto-search').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase().trim();
    const resultsDiv = document.getElementById('producto-results');
    
    if (searchTerm.length < 2) {
        resultsDiv.classList.add('d-none');
        return;
    }
    
    // Filtrar productos
    const filtered = productos.filter(producto => 
        producto.codigo.toLowerCase().includes(searchTerm) || 
        producto.nombre.toLowerCase().includes(searchTerm)
    );
    
    // Mostrar resultados
    if (filtered.length > 0) {
        resultsDiv.innerHTML = filtered.slice(0, 10).map(producto => 
            `<div class="p-2 border-bottom cursor-pointer resultado-producto" 
                  data-id="${producto.id}" 
                  data-codigo="${producto.codigo}" 
                  data-nombre="${producto.nombre}">
                <strong>[${producto.codigo}]</strong> ${producto.nombre}
                <small class="text-muted d-block">${producto.tipo}</small>
            </div>`
        ).join('');
        
        if (filtered.length > 10) {
            resultsDiv.innerHTML += `<div class="p-2 text-muted text-center">
                <small>Mostrando 10 de ${filtered.length} resultados</small>
            </div>`;
        }
        
        resultsDiv.classList.remove('d-none');
        
        // Agregar eventos de click
        document.querySelectorAll('.resultado-producto').forEach(item => {
            item.addEventListener('click', function() {
                const id = this.dataset.id;
                const codigo = this.dataset.codigo;
                const nombre = this.dataset.nombre;
                
                document.getElementById('producto-search').value = `[${codigo}] ${nombre}`;
                document.getElementById('producto-id').value = id;
                resultsDiv.classList.add('d-none');
            });
            
            // Hover effect
            item.addEventListener('mouseenter', function() {
                this.style.backgroundColor = '#f8f9fa';
            });
            item.addEventListener('mouseleave', function() {
                this.style.backgroundColor = '';
            });
        });
    } else {
        resultsDiv.innerHTML = '<div class="p-2 text-muted text-center">No se encontraron productos</div>';
        resultsDiv.classList.remove('d-none');
    }
});

// Cerrar resultados al hacer click fuera
document.addEventListener('click', function(e) {
    if (!e.target.closest('.position-relative')) {
        document.getElementById('producto-results').classList.add('d-none');
    }
});

// Limpiar selección al cambiar búsqueda
document.getElementById('producto-search').addEventListener('keydown', function() {
    document.getElementById('producto-id').value = '';
});

function exportarInventario() {
    showToast('Funcionalidad de exportación en desarrollo', 'info');
}

// Inline edit de stock disponible
document.querySelectorAll('.edit-stock-btn, .editable-stock').forEach(el => {
    el.addEventListener('click', function() {
        const row = this.closest('tr');
        const span = row.querySelector('.editable-stock');
        if (row.querySelector('.inline-input')) return; // ya editando
        const valorActual = parseInt(span.dataset.disponible || span.textContent.trim()) || 0;
        const input = document.createElement('input');
        input.type = 'number';
        input.min = '0';
        input.value = valorActual;
        input.className = 'form-control form-control-sm inline-input';
        span.replaceWith(input);
        input.focus();

        const finalizar = (guardar) => {
            const nuevo = parseInt(input.value || '0');
            if (!guardar || nuevo === valorActual) {
                // Restaurar
                const s = document.createElement('span');
                s.className = 'fw-bold editable-stock';
                s.dataset.disponible = valorActual;
                s.dataset.reparacion = row.querySelector('.editable-stock')?.dataset.reparacion || '0';
                s.textContent = valorActual;
                input.replaceWith(s);
                s.addEventListener('click', ()=>{});
                return;
            }
            // Preparar POST
            const tiendaId = row.getAttribute('data-tienda-id');
            const productoId = row.getAttribute('data-producto-id');
            const token = document.querySelector('input[name="csrf_token"]').value;
            const form = new FormData();
            form.append('csrf_token', token);
            form.append('tienda_id', tiendaId);
            form.append('producto_id', productoId);
            form.append('nueva_cantidad', String(nuevo));
            fetch('includes/update_inventario.php', { method: 'POST', body: form })
              .then(r => r.json())
              .then(data => {
                if (data && data.success) {
                    // Actualizar UI
                    const s = document.createElement('span');
                    s.className = 'fw-bold editable-stock';
                    s.dataset.disponible = data.cantidad_disponible;
                    s.dataset.reparacion = data.cantidad_reparacion;
                    s.textContent = data.cantidad_disponible;
                    input.replaceWith(s);
                    row.querySelector('.cantidad-total-cell').textContent = data.cantidad_total;
                    // Estado badge
                    const min = parseInt(row.getAttribute('data-stock-min')) || 5;
                    const disp = data.cantidad_disponible;
                    const rep = data.cantidad_reparacion;
                    let html = '';
                    if (disp <= 0 && rep > 0) html = '<span class="badge bg-danger">Sin Stock (En Reparación)</span>';
                    else if (disp <= min && rep > 0) html = '<span class="badge bg-warning text-dark">Stock Bajo + Reparación</span>';
                    else if (disp <= min) html = '<span class="badge bg-danger">Stock Bajo</span>';
                    else if (disp <= (min*2)) html = '<span class="badge bg-warning text-dark">Stock Medio</span>';
                    else if (rep > 0) html = '<span class="badge bg-info">Stock Normal + Reparación</span>';
                    else html = '<span class="badge bg-success">Stock Bueno</span>';
                    row.querySelector('.estado-cell').innerHTML = html;
                    if (typeof showSuccess === 'function') showSuccess('Stock actualizado'); else alert('Stock actualizado');
                } else {
                    if (typeof showError === 'function') showError(data.error || 'Error al actualizar'); else alert('Error al actualizar');
                    input.focus();
                }
              })
              .catch(() => {
                if (typeof showError === 'function') showError('Error de red al actualizar'); else alert('Error de red al actualizar');
                input.focus();
              });
        };

        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') { finalizar(true); }
            if (e.key === 'Escape') { finalizar(false); }
        });
        input.addEventListener('blur', () => finalizar(true));
    });
});

// Inline edit de en reparación
document.querySelectorAll('.edit-rep-btn, .editable-rep').forEach(el => {
    el.addEventListener('click', function() {
        const row = this.closest('tr');
        const spanRep = row.querySelector('.editable-rep');
        if (!spanRep || row.querySelector('.inline-input-rep')) return;
        const valorActual = parseInt(spanRep.textContent.trim()) || 0;
        const input = document.createElement('input');
        input.type = 'number';
        input.min = '0';
        input.value = valorActual;
        input.className = 'form-control form-control-sm inline-input-rep d-inline-block';
        spanRep.replaceWith(input);
        input.focus();

        const finalizar = (guardar) => {
            const nuevo = parseInt(input.value || '0');
            if (!guardar || nuevo === valorActual) {
                const s = document.createElement('span');
                s.className = 'editable-rep';
                s.textContent = valorActual;
                input.replaceWith(s);
                return;
            }
            const tiendaId = row.getAttribute('data-tienda-id');
            const productoId = row.getAttribute('data-producto-id');
            const token = document.querySelector('input[name="csrf_token"]').value;
            const form = new FormData();
            form.append('csrf_token', token);
            form.append('tienda_id', tiendaId);
            form.append('producto_id', productoId);
            form.append('nueva_reparacion', String(nuevo));
            fetch('includes/update_inventario.php', { method: 'POST', body: form })
              .then(r => r.json())
              .then(data => {
                if (data && data.success) {
                    // Update rep span
                    const s = document.createElement('span');
                    s.className = 'editable-rep';
                    s.textContent = data.cantidad_reparacion;
                    input.replaceWith(s);
                    // Update disponible cell
                    const dispSpan = row.querySelector('.editable-stock');
                    if (dispSpan) {
                        dispSpan.textContent = data.cantidad_disponible;
                        dispSpan.dataset.disponible = data.cantidad_disponible;
                        dispSpan.dataset.reparacion = data.cantidad_reparacion;
                    }
                    // Update total cell
                    row.querySelector('.cantidad-total-cell').textContent = data.cantidad_total;
                    // Update estado badge
                    const min = parseInt(row.getAttribute('data-stock-min')) || 5;
                    const disp = data.cantidad_disponible;
                    const rep = data.cantidad_reparacion;
                    let html = '';
                    if (disp <= 0 && rep > 0) html = '<span class="badge bg-danger">Sin Stock (En Reparación)</span>';
                    else if (disp <= min && rep > 0) html = '<span class="badge bg-warning text-dark">Stock Bajo + Reparación</span>';
                    else if (disp <= min) html = '<span class="badge bg-danger">Stock Bajo</span>';
                    else if (disp <= (min*2)) html = '<span class="badge bg-warning text-dark">Stock Medio</span>';
                    else if (rep > 0) html = '<span class="badge bg-info">Stock Normal + Reparación</span>';
                    else html = '<span class="badge bg-success">Stock Bueno</span>';
                    row.querySelector('.estado-cell').innerHTML = html;
                    if (typeof showSuccess === 'function') showSuccess('Reparación actualizada'); else alert('Reparación actualizada');
                } else {
                    if (typeof showError === 'function') showError(data.error || 'Error al actualizar'); else alert('Error al actualizar');
                    input.focus();
                }
              })
              .catch(() => {
                if (typeof showError === 'function') showError('Error de red al actualizar'); else alert('Error de red al actualizar');
                input.focus();
              });
        };

        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') { finalizar(true); }
            if (e.key === 'Escape') { finalizar(false); }
        });
        input.addEventListener('blur', () => finalizar(true));
    });
});
</script>

<style>
.cursor-pointer {
    cursor: pointer;
}
.resultado-producto:hover {
    background-color: #f8f9fa;
}
</style>

<?php include 'includes/layout_footer.php'; ?>
