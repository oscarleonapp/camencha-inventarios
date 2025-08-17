<?php
$titulo = "Productos - Sistema de Inventarios";
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'includes/config_functions.php';
require_once 'includes/csrf_protection.php';
require_once 'includes/qr_generator.php';
require_once 'includes/codigo_generator.php';

verificarLogin();
verificarPermiso('productos_ver');

$database = new Database();
$db = $database->getConnection();

// Inicializar generador de códigos
$codigoGenerator = new CodigoGenerator($db);

// Filtros y paginación
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$tipo_filtro = isset($_GET['tipo']) ? trim($_GET['tipo']) : '';
$estado_filtro = isset($_GET['estado']) ? trim($_GET['estado']) : 'activos';
$proveedor_filtro = isset($_GET['proveedor_id']) ? trim($_GET['proveedor_id']) : '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 10; // valor por defecto; puede configurarse en BD

if ($_POST && isset($_POST['action'])) {
    // Validar CSRF token
    validarCSRF();
    if ($_POST['action'] == 'editar_producto') {
        verificarPermiso('productos_actualizar', 'actualizar');
        $id = (int)($_POST['id'] ?? 0);
        $codigo = trim($_POST['codigo'] ?? '');
        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $precio_venta = (float)($_POST['precio_venta'] ?? 0);
        $precio_compra = (float)($_POST['precio_compra'] ?? 0);
        $tipo = $_POST['tipo'] ?? 'elemento';
        $proveedor_id = isset($_POST['proveedor_id']) && $_POST['proveedor_id'] !== '' ? (int)$_POST['proveedor_id'] : null;
        if ($id <= 0 || $codigo === '' || $nombre === '') {
            $error = 'Datos inválidos para actualizar el producto';
        } else {
            if ($proveedor_id !== null) {
                $stmtProv = $db->prepare("SELECT id FROM proveedores WHERE id = ? AND activo = 1");
                $stmtProv->execute([$proveedor_id]);
                if ($stmtProv->rowCount() === 0) {
                    $error = "Proveedor seleccionado no válido";
                }
            }
            // Código único (excluyendo el propio producto)
            if (!isset($error)) {
                $stmtChk = $db->prepare("SELECT id FROM productos WHERE codigo = ? AND id <> ?");
                $stmtChk->execute([$codigo, $id]);
                if ($stmtChk->rowCount() > 0) {
                    $error = "Ya existe un producto con el código '$codigo'. Use un código diferente.";
                }
            }
            if (!isset($error)) {
                // Obtener datos anteriores
                $stmtOld = $db->prepare("SELECT * FROM productos WHERE id = ?");
                $stmtOld->execute([$id]);
                $old = $stmtOld->fetch(PDO::FETCH_ASSOC);
                
                $stmtUp = $db->prepare("UPDATE productos SET codigo = ?, nombre = ?, descripcion = ?, precio_venta = ?, precio_compra = ?, tipo = ?, proveedor_id = ? WHERE id = ?");
                $stmtUp->execute([$codigo, $nombre, $descripcion, $precio_venta, $precio_compra, $tipo, $proveedor_id, $id]);
                
                $success = 'Producto actualizado correctamente';
                // Log de cambios antes/después
                require_once 'includes/logger.php';
                getLogger()->crud('update', 'productos', 'productos', $id, $old, [
                    'codigo' => $codigo,
                    'nombre' => $nombre,
                    'descripcion' => $descripcion,
                    'precio_venta' => $precio_venta,
                    'precio_compra' => $precio_compra,
                    'tipo' => $tipo,
                    'proveedor_id' => $proveedor_id
                ]);
            }
        }
    }
    if ($_POST['action'] == 'asignar_proveedor_masivo') {
        verificarPermiso('productos_actualizar', 'actualizar');
        $ids = isset($_POST['producto_ids']) && is_array($_POST['producto_ids']) ? array_filter($_POST['producto_ids']) : [];
        $nuevo_raw = $_POST['nuevo_proveedor_id'] ?? '';
        $to_null = ($nuevo_raw === 'null' || $nuevo_raw === '');
        $nuevo_proveedor_id = $to_null ? null : (int)$nuevo_raw;

        if (empty($ids)) {
            $error = "Selecciona al menos un producto";
        } else if (!$to_null) {
            $stmtProv = $db->prepare("SELECT id FROM proveedores WHERE id = ? AND activo = 1");
            $stmtProv->execute([$nuevo_proveedor_id]);
            if ($stmtProv->rowCount() === 0) {
                $error = "Proveedor destino no válido";
            }
        }

        if (!isset($error)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            if ($to_null) {
                $sql = "UPDATE productos SET proveedor_id = NULL WHERE id IN ($placeholders)";
                $stmt = $db->prepare($sql);
                $stmt->execute($ids);
            } else {
                $sql = "UPDATE productos SET proveedor_id = ? WHERE id IN ($placeholders)";
                $stmt = $db->prepare($sql);
                $stmt->execute(array_merge([$nuevo_proveedor_id], $ids));
            }
            $success = "Proveedor asignado a " . count($ids) . " producto(s)";
            require_once 'includes/logger.php';
            getLogger()->info('asignacion_proveedor_masiva', 'productos', 'Asignación masiva de proveedor', [
                'producto_ids' => $ids,
                'proveedor_id' => $to_null ? null : $nuevo_proveedor_id
            ]);
        }
    }
    if ($_POST['action'] == 'eliminar_productos_masivo') {
        verificarPermiso('productos_eliminar', 'eliminar');
        $ids = isset($_POST['producto_ids']) && is_array($_POST['producto_ids']) ? array_filter($_POST['producto_ids']) : [];
        if (empty($ids)) {
            $error = "Selecciona al menos un producto";
        } else {
            $deleted = 0;
            $skipped = [];
            $stmtDet = $db->prepare("SELECT COUNT(*) FROM detalle_ventas WHERE producto_id = ?");
            $stmtInv = $db->prepare("SELECT COUNT(*) FROM inventarios WHERE producto_id = ?");
            $stmtComp1 = $db->prepare("SELECT COUNT(*) FROM producto_componentes WHERE producto_conjunto_id = ?");
            $stmtComp2 = $db->prepare("SELECT COUNT(*) FROM producto_componentes WHERE producto_elemento_id = ?");
            $stmtMov = $db->prepare("SELECT COUNT(*) FROM movimientos_inventario WHERE producto_id = ?");
            $stmtRep = $db->prepare("SELECT COUNT(*) FROM reparaciones WHERE producto_id = ?");
            $stmtDel = $db->prepare("DELETE FROM productos WHERE id = ?");
            foreach ($ids as $pid) {
                $pid = (int)$pid;
                $blocked = 0;
                foreach ([$stmtDet,$stmtInv,$stmtComp1,$stmtComp2,$stmtMov,$stmtRep] as $stmt) {
                    $stmt->execute([$pid]);
                    $blocked += (int)$stmt->fetchColumn();
                }
                if ($blocked === 0) {
                    $stmtDel->execute([$pid]);
                    $deleted++;
                } else {
                    $skipped[] = $pid;
                }
            }
            if ($deleted > 0) {
                $success = "Eliminados $deleted producto(s)";
            }
            if (!empty($skipped)) {
                $error = "No se pudieron eliminar " . count($skipped) . " producto(s) por tener referencias";
            }
        }
    }
    if ($_POST['action'] == 'cambiar_estado_masivo') {
        verificarPermiso('productos_actualizar', 'actualizar');
        $ids = isset($_POST['producto_ids']) && is_array($_POST['producto_ids']) ? array_filter($_POST['producto_ids']) : [];
        $nuevo_estado = isset($_POST['nuevo_estado']) && $_POST['nuevo_estado'] == '1' ? 1 : 0;
        if (empty($ids)) {
            $error = "Selecciona al menos un producto";
        } else {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $sql = "UPDATE productos SET activo = ? WHERE id IN ($placeholders)";
            $stmt = $db->prepare($sql);
            $stmt->execute(array_merge([$nuevo_estado], $ids));
            $success = ($nuevo_estado ? 'Activados' : 'Desactivados') . " " . count($ids) . " producto(s)";
            require_once 'includes/logger.php';
            getLogger()->info('cambio_estado_masivo', 'productos', 'Cambio de estado masivo', [
                'producto_ids' => $ids,
                'nuevo_estado' => $nuevo_estado
            ]);
        }
    }
    if ($_POST['action'] == 'crear_producto') {
        // Generar código automáticamente basado en el tipo
        $tipo = $_POST['tipo'];
        $tipo_codigo = ($tipo === 'conjunto') ? 'conjunto' : 'producto';
        $codigo = $codigoGenerator->generarCodigo($tipo_codigo);
        
        $nombre = $_POST['nombre'];
        $descripcion = $_POST['descripcion'];
        $precio_venta = $_POST['precio_venta'];
        $precio_compra = $_POST['precio_compra'];
        $proveedor_id = isset($_POST['proveedor_id']) && $_POST['proveedor_id'] !== '' ? (int)$_POST['proveedor_id'] : null;
        
        // Para conjuntos, permitir proveedor nulo si hay múltiples proveedores
        $permitir_proveedor_nulo = ($tipo === 'conjunto');
        
        if ($proveedor_id !== null) {
            // Validar proveedor si se especificó
            $stmtProv = $db->prepare("SELECT id FROM proveedores WHERE id = ? AND activo = 1");
            $stmtProv->execute([$proveedor_id]);
            if ($stmtProv->rowCount() === 0) {
                $error = "Proveedor seleccionado no válido";
            }
        } elseif (!$permitir_proveedor_nulo) {
            // Para elementos individuales, el proveedor es requerido
            $error = "El proveedor es requerido para elementos individuales";
        }
        
        // Verificar si el código ya existe
        $check_query = "SELECT id FROM productos WHERE codigo = ?";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->execute([$codigo]);
        
        if (!isset($error) && $check_stmt->rowCount() > 0) {
            $error = "Ya existe un producto con el código '$codigo'. Use un código diferente.";
        } else {
            // Usar transacción para crear producto y sus componentes
            $db->beginTransaction();
            
            try {
                // Forzar producto activo al crear para que aparezca en el listado
                $query = "INSERT INTO productos (codigo, nombre, descripcion, precio_venta, precio_compra, tipo, proveedor_id, activo) VALUES (?, ?, ?, ?, ?, ?, ?, 1)";
                $stmt = $db->prepare($query);
                $stmt->execute([$codigo, $nombre, $descripcion, $precio_venta, $precio_compra, $tipo, $proveedor_id]);
                $producto_id = $db->lastInsertId();
                
                // Si es un conjunto, procesar los componentes
                if ($tipo === 'conjunto' && isset($_POST['componentes_elementos']) && is_array($_POST['componentes_elementos'])) {
                    $componentes_elementos = $_POST['componentes_elementos'];
                    $componentes_cantidades = $_POST['componentes_cantidades'] ?? [];
                    
                    $componentes_agregados = 0;
                    
                    for ($i = 0; $i < count($componentes_elementos); $i++) {
                        $elemento_id = $componentes_elementos[$i];
                        $cantidad = isset($componentes_cantidades[$i]) ? (int)$componentes_cantidades[$i] : 1;
                        
                        // Solo procesar si se seleccionó un elemento y la cantidad es válida
                        if (!empty($elemento_id) && $cantidad > 0) {
                            // Verificar que el elemento existe
                            $verify_query = "SELECT id FROM productos WHERE id = ? AND tipo = 'elemento' AND activo = 1";
                            $verify_stmt = $db->prepare($verify_query);
                            $verify_stmt->execute([$elemento_id]);
                            
                            if ($verify_stmt->rowCount() > 0) {
                                // Agregar componente
                                $comp_query = "INSERT INTO producto_componentes (producto_conjunto_id, producto_elemento_id, cantidad) VALUES (?, ?, ?)";
                                $comp_stmt = $db->prepare($comp_query);
                                $comp_stmt->execute([$producto_id, $elemento_id, $cantidad]);
                                $componentes_agregados++;
                            }
                        }
                    }
                    
                    // Si es un conjunto pero no se agregaron componentes, mostrar advertencia
                    if ($componentes_agregados === 0) {
                        throw new Exception("Un conjunto debe tener al menos un componente válido");
                    }
                }
                
                $db->commit();
                
                $success = "Producto creado exitosamente";
                if ($tipo === 'conjunto' && isset($componentes_agregados) && $componentes_agregados > 0) {
                    $success .= " con {$componentes_agregados} componente(s)";
                }
                
                require_once 'includes/logger.php';
                getLogger()->crud('create', 'productos', 'productos', $producto_id, null, [
                    'codigo' => $codigo,
                    'nombre' => $nombre,
                    'tipo' => $tipo,
                    'proveedor_id' => $proveedor_id,
                    'componentes' => $tipo === 'conjunto' ? ($componentes_agregados ?? 0) : 0
                ]);
                
            } catch (Exception $e) {
                $db->rollBack();
                $error = $e->getMessage();
            }
        }
    }
    
    if ($_POST['action'] == 'agregar_componente') {
        $producto_conjunto_id = $_POST['producto_conjunto_id'];
        $producto_elemento_id = $_POST['producto_elemento_id'];
        $cantidad = $_POST['cantidad'];
        
        $query = "INSERT INTO producto_componentes (producto_conjunto_id, producto_elemento_id, cantidad) VALUES (?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->execute([$producto_conjunto_id, $producto_elemento_id, $cantidad]);
        
        $success = "Componente agregado exitosamente";
    }
}

// Construcción dinámica de filtros
$conditions = []; 
$params = [];
if ($q !== '') {
    $conditions[] = "(p.codigo LIKE ? OR p.nombre LIKE ?)";
    $params[] = "%$q%";
    $params[] = "%$q%";
}
if ($tipo_filtro === 'elemento' || $tipo_filtro === 'conjunto') {
    $conditions[] = "p.tipo = ?";
    $params[] = $tipo_filtro;
}
// Filtro de estado: activos (default), inactivos, todos
if ($estado_filtro === 'activos') {
    $conditions[] = 'p.activo = 1';
} elseif ($estado_filtro === 'inactivos') {
    $conditions[] = 'p.activo = 0';
}
if ($proveedor_filtro !== '') {
    if ($proveedor_filtro === 'null') {
        $conditions[] = "p.proveedor_id IS NULL";
    } else {
        $conditions[] = "p.proveedor_id = ?";
        $params[] = $proveedor_filtro;
    }
}
$where = implode(' AND ', $conditions);

// Conteo total y paginación
$count_sql = "SELECT COUNT(*) FROM productos p WHERE $where";
$stmt_count = $db->prepare($count_sql);
$stmt_count->execute($params);
$total = (int)$stmt_count->fetchColumn();
$total_pages = max(1, (int)ceil($total / $per_page));
if ($page > $total_pages) { $page = $total_pages; }
$offset = ($page - 1) * $per_page;

// Consulta paginada
$query_productos = "SELECT p.*, pr.nombre AS proveedor_nombre 
                    FROM productos p 
                    LEFT JOIN proveedores pr ON p.proveedor_id = pr.id 
                    WHERE $where ORDER BY p.nombre LIMIT $per_page OFFSET $offset";
$stmt_productos = $db->prepare($query_productos);
$stmt_productos->execute($params);
$productos = $stmt_productos->fetchAll(PDO::FETCH_ASSOC);

// Exportación CSV con filtros actuales
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $export_sql = "SELECT p.codigo, p.nombre, p.tipo, p.precio_compra, p.precio_venta, 
                          COALESCE(pr.nombre, '') AS proveedor,
                          CASE WHEN p.activo=1 THEN 'activo' ELSE 'inactivo' END AS estado
                   FROM productos p
                   LEFT JOIN proveedores pr ON p.proveedor_id = pr.id
                   WHERE $where
                   ORDER BY p.nombre";
    $stmt_exp = $db->prepare($export_sql);
    $stmt_exp->execute($params);
    $rows = $stmt_exp->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=productos_' . date('Y-m-d_H-i-s') . '.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Codigo','Nombre','Tipo','PrecioCompra','PrecioVenta','Proveedor','Estado']);
    foreach ($rows as $r) {
        fputcsv($out, [$r['codigo'], $r['nombre'], $r['tipo'], $r['precio_compra'], $r['precio_venta'], $r['proveedor'], $r['estado']]);
    }
    fclose($out);
    exit;
}

$query_elementos = "SELECT p.*, pr.nombre as proveedor_nombre FROM productos p 
                    LEFT JOIN proveedores pr ON p.proveedor_id = pr.id 
                    WHERE p.tipo = 'elemento' AND p.activo = 1 ORDER BY p.nombre";
$stmt_elementos = $db->prepare($query_elementos);
$stmt_elementos->execute();
$elementos = $stmt_elementos->fetchAll(PDO::FETCH_ASSOC);

$query_conjuntos = "SELECT * FROM productos WHERE tipo = 'conjunto' AND activo = 1 ORDER BY nombre";
$stmt_conjuntos = $db->prepare($query_conjuntos);
$stmt_conjuntos->execute();
$conjuntos = $stmt_conjuntos->fetchAll(PDO::FETCH_ASSOC);

// Proveedores para selector
$stmt_proveedores = $db->prepare("SELECT id, nombre FROM proveedores WHERE activo = 1 ORDER BY nombre");
$stmt_proveedores->execute();
$proveedores = $stmt_proveedores->fetchAll(PDO::FETCH_ASSOC);

include 'includes/layout_header.php';
?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-box"></i> <span class="editable" data-label="productos_titulo">Gestión de Productos</span></h2>
        <?php if (tienePermiso('productos_crear', 'crear')): ?>
        <div class="btn-group">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTipoProducto">
                <i class="fas fa-plus"></i> Nuevo Producto
            </button>
            <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalAgregarComponente">
                <i class="fas fa-link"></i> Agregar Componente
            </button>
        </div>
        <?php endif; ?>
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
        
        
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0"><i class="fas fa-list"></i> Lista de Productos</h5>
                <form class="d-flex gap-2 flex-wrap" method="GET" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                    <input type="text" name="q" class="form-control form-control-sm" placeholder="Buscar código o nombre" value="<?php echo htmlspecialchars($q); ?>">
                    <select name="tipo" class="form-select form-select-sm">
                        <option value="">Todos los tipos</option>
                        <option value="elemento" <?php echo $tipo_filtro==='elemento'?'selected':''; ?>>Elemento</option>
                        <option value="conjunto" <?php echo $tipo_filtro==='conjunto'?'selected':''; ?>>Conjunto</option>
                    </select>
                    <select name="proveedor_id" class="form-select form-select-sm">
                        <option value="">Todos los proveedores</option>
                        <option value="null" <?php echo ($proveedor_filtro === 'null') ? 'selected' : ''; ?>>Sin proveedor</option>
                        <?php foreach ($proveedores as $prov): ?>
                            <option value="<?php echo $prov['id']; ?>" <?php echo ($proveedor_filtro !== '' && $proveedor_filtro == $prov['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($prov['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select name="estado" class="form-select form-select-sm">
                        <option value="activos" <?php echo $estado_filtro==='activos'?'selected':''; ?>>Activos</option>
                        <option value="inactivos" <?php echo $estado_filtro==='inactivos'?'selected':''; ?>>Inactivos</option>
                        <option value="todos" <?php echo $estado_filtro==='todos'?'selected':''; ?>>Todos</option>
                    </select>
                    <button class="btn btn-sm btn-primary" type="submit"><i class="fas fa-search"></i></button>
                    <?php if ($q !== '' || ($tipo_filtro !== '') || ($proveedor_filtro !== '') || ($estado_filtro !== 'activos')): ?>
                        <a class="btn btn-sm btn-outline-secondary" href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">Limpiar</a>
                    <?php endif; ?>
                </form>
            </div>
                    <div class="card-body">
                        <div class="mb-2 small text-muted d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                Mostrando <?php echo count($productos); ?> de <?php echo $total; ?> resultados
                                <?php if ($total_pages > 1): ?>
                                    (página <?php echo $page; ?> de <?php echo $total_pages; ?>)
                                <?php endif; ?>
                            </div>
                            <div>
                                <?php $qs_current = $_SERVER['QUERY_STRING'] ?? ''; $exportHref = htmlspecialchars($_SERVER['PHP_SELF'] . '?' . ($qs_current ? $qs_current . '&' : '') . 'export=csv'); ?>
                                <a class="btn btn-sm btn-outline-success" href="<?php echo $exportHref; ?>">
                                    <i class="fas fa-file-csv"></i> Exportar CSV
                                </a>
                            </div>
                            <div id="bulkIdsContainer" class="d-none"></div>
                        </div>
                        <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                            <form method="POST" id="bulkProveedorForm" class="d-flex align-items-center gap-2 bulk-form">
                                <input type="hidden" name="action" value="asignar_proveedor_masivo">
                                <?php echo campoCSRF(); ?>
                                <label class="small text-muted">Asignar proveedor a seleccionados:</label>
                                <select class="form-select form-select-sm" name="nuevo_proveedor_id" id="bulkProveedorSelect">
                                    <option value="">Seleccionar…</option>
                                    <option value="null">Sin proveedor</option>
                                    <?php foreach ($proveedores as $prov): ?>
                                        <option value="<?php echo $prov['id']; ?>"><?php echo htmlspecialchars($prov['nombre']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn btn-sm btn-outline-primary">Aplicar</button>
                            </form>
                            <form method="POST" id="bulkEstadoForm" class="d-flex align-items-center gap-2 bulk-form">
                                <input type="hidden" name="action" value="cambiar_estado_masivo">
                                <?php echo campoCSRF(); ?>
                                <label class="small text-muted">Cambiar estado:</label>
                                <input type="hidden" name="nuevo_estado" id="nuevoEstadoHidden" value="">
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="submit" class="btn btn-outline-success" onclick="document.getElementById('nuevoEstadoHidden').value='1'">Activar</button>
                                    <button type="submit" class="btn btn-outline-secondary" onclick="document.getElementById('nuevoEstadoHidden').value='0'">Desactivar</button>
                                </div>
                            </form>
                            <form method="POST" id="bulkDeleteForm" class="d-flex align-items-center gap-2 bulk-form" onsubmit="return confirm('¿Eliminar productos seleccionados? Esta acción no se puede deshacer');">
                                <input type="hidden" name="action" value="eliminar_productos_masivo">
                                <?php echo campoCSRF(); ?>
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="fas fa-trash"></i> Eliminar seleccionados
                                </button>
                            </form>
                        </div>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th style="width:32px;"><input type="checkbox" id="selectAllProductos"></th>
                                        <th>Código</th>
                                        <th>Nombre</th>
                                        <th>Tipo</th>
                                        <th>Precio Venta</th>
                                        <th>Precio Compra</th>
                                        <th>Proveedor</th>
                                        <th>QR Code</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($productos) === 0): ?>
                                    <tr>
                                        <td colspan="9" class="text-center text-muted">No se encontraron productos con los filtros aplicados</td>
                                    </tr>
                                    <?php endif; ?>
                                    <?php foreach ($productos as $producto): ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="prod-checkbox" value="<?php echo $producto['id']; ?>">
                                        </td>
                                        <td><?php echo $producto['codigo']; ?></td>
                                        <td><?php echo $producto['nombre']; ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $producto['tipo'] == 'elemento' ? 'primary' : 'success'; ?>">
                                                <?php echo ucfirst($producto['tipo']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo formatearMoneda($producto['precio_venta']); ?></td>
                                        <td><?php echo formatearMoneda($producto['precio_compra']); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($producto['proveedor_nombre'] ?? '—'); ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($producto['qr_code'])): ?>
                                                <span class="badge bg-success">
                                                    <i class="fas fa-qrcode"></i> Generado
                                                </span>
                                                <div class="btn-group btn-group-sm mt-1">
                                                    <?php if (tienePermiso('productos_qr_descargar')): ?>
                                                        <button class="btn btn-outline-primary btn-sm" onclick="descargarQR(<?php echo $producto['id']; ?>, 'imagen')" title="Descargar QR">
                                                            <i class="fas fa-download"></i>
                                                        </button>
                                                        <button class="btn btn-outline-success btn-sm" onclick="descargarQR(<?php echo $producto['id']; ?>, 'etiqueta')" title="Descargar Etiqueta">
                                                            <i class="fas fa-tag"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    <?php if (tienePermiso('productos_qr_generar')): ?>
                                                        <button class="btn btn-outline-warning btn-sm" onclick="regenerarQR(<?php echo $producto['id']; ?>)" title="Regenerar QR">
                                                            <i class="fas fa-sync"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Sin QR</span>
                                                <?php if (tienePermiso('productos_qr_generar')): ?>
                                                    <div class="mt-1">
                                                        <button class="btn btn-success btn-sm" onclick="generarQR(<?php echo $producto['id']; ?>)" title="Generar QR">
                                                            <i class="fas fa-qrcode"></i> Generar
                                                        </button>
                                                    </div>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEditarProducto"
                                                    data-id="<?php echo $producto['id']; ?>"
                                                    data-codigo="<?php echo htmlspecialchars($producto['codigo']); ?>"
                                                    data-nombre="<?php echo htmlspecialchars($producto['nombre']); ?>"
                                                    data-descripcion="<?php echo htmlspecialchars($producto['descripcion'] ?? ''); ?>"
                                                    data-precioventa="<?php echo $producto['precio_venta']; ?>"
                                                    data-preciocompra="<?php echo $producto['precio_compra']; ?>"
                                                    data-tipo="<?php echo $producto['tipo']; ?>"
                                                    data-proveedor="<?php echo $producto['proveedor_id'] ?? ''; ?>"
                                                >
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if ($producto['tipo'] == 'conjunto'): ?>
                                                    <a href="ver_componentes.php?id=<?php echo $producto['id']; ?>" class="btn btn-info">Ver Componentes</a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if ($total_pages > 1): ?>
                        <nav aria-label="Paginación productos" class="mt-3">
                            <ul class="pagination pagination-sm mb-0">
                                <?php 
                                    $base = htmlspecialchars($_SERVER['PHP_SELF']);
                                    $qs = [];
                                    if ($q !== '') $qs['q'] = $q;
                                    if ($tipo_filtro !== '') $qs['tipo'] = $tipo_filtro;
                                    if ($proveedor_filtro !== '') $qs['proveedor_id'] = $proveedor_filtro;
                                    if ($estado_filtro !== 'activos') $qs['estado'] = $estado_filtro;
                                    $build = function($p) use ($base, $qs) {
                                        $qs['page'] = $p;
                                        return $base . '?' . http_build_query($qs);
                                    };
                                ?>
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="<?php echo $page <= 1 ? '#' : $build($page-1); ?>" tabindex="-1">&laquo;</a>
                                </li>
                                <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                                    <li class="page-item <?php echo $p == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="<?php echo $build($p); ?>"><?php echo $p; ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="<?php echo $page >= $total_pages ? '#' : $build($page+1); ?>">&raquo;</a>
                                </li>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    </div>
                </div>

<!-- Modal Selector de Tipo de Producto -->
<div class="modal fade" id="modalTipoProducto" tabindex="-1" aria-labelledby="modalTipoProductoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTipoProductoLabel">
                    <i class="fas fa-plus"></i> Seleccionar Tipo de Producto
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-4">¿Qué tipo de producto deseas crear?</p>
                <div class="row g-3">
                    <div class="col-6">
                        <div class="card h-100 border-primary card-hover" onclick="abrirModalProducto('elemento')" style="cursor: pointer;">
                            <div class="card-body text-center">
                                <i class="fas fa-cube fa-3x text-primary mb-3"></i>
                                <h6 class="card-title">Elemento Individual</h6>
                                <p class="card-text small text-muted">
                                    Producto único que no depende de otros elementos
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card h-100 border-success card-hover" onclick="abrirModalProducto('conjunto')" style="cursor: pointer;">
                            <div class="card-body text-center">
                                <i class="fas fa-cubes fa-3x text-success mb-3"></i>
                                <h6 class="card-title">Conjunto/Kit</h6>
                                <p class="card-text small text-muted">
                                    Producto compuesto por múltiples elementos
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Cancelar
                </button>
            </div>
</div>
</div>
</div>

<!-- Modal Editar Producto -->
<div class="modal fade" id="modalEditarProducto" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST" id="formEditarProducto">
        <input type="hidden" name="action" value="editar_producto">
        <input type="hidden" name="id" id="editarProductoId">
        <?php echo campoCSRF(); ?>
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-pen"></i> Editar Producto</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label">Código</label>
                <input type="text" class="form-control" name="codigo" id="editarCodigo" required>
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label">Nombre</label>
                <input type="text" class="form-control" name="nombre" id="editarNombre" required>
              </div>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Descripción</label>
            <textarea class="form-control" name="descripcion" id="editarDescripcion" rows="3"></textarea>
          </div>
          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label">Precio Venta</label>
                <div class="input-group">
                  <span class="input-group-text">Q</span>
                  <input type="number" step="0.01" min="0" class="form-control" name="precio_venta" id="editarPrecioVenta" required>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label">Precio Compra</label>
                <div class="input-group">
                  <span class="input-group-text">Q</span>
                  <input type="number" step="0.01" min="0" class="form-control" name="precio_compra" id="editarPrecioCompra" required>
                </div>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label">Tipo</label>
                <select class="form-select" name="tipo" id="editarTipo">
                  <option value="elemento">Elemento</option>
                  <option value="conjunto">Conjunto</option>
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label">Proveedor</label>
                <select class="form-select" name="proveedor_id" id="editarProveedor">
                  <option value="">Sin proveedor</option>
                  <?php foreach ($proveedores as $prov): ?>
                    <option value="<?php echo $prov['id']; ?>"><?php echo htmlspecialchars($prov['nombre']); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Guardar Cambios</button>
        </div>
      </form>
    </div>
  </div>
  </div>
<!-- Modal Crear Producto -->
<div class="modal fade" id="modalCrearProducto" tabindex="-1" aria-labelledby="modalCrearProductoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" id="formCrearProducto">
                <input type="hidden" name="action" value="crear_producto">
                <input type="hidden" name="tipo" id="tipoProductoSeleccionado">
                <?php echo campoCSRF(); ?>
                
                <div class="modal-header">
                    <h5 class="modal-title" id="modalCrearProductoLabel">
                        <i class="fas fa-plus-square"></i> 
                        <span id="tituloModalProducto">Crear Producto</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                
                <div class="modal-body">
                    <div class="alert alert-success">
                        <i class="fas fa-magic me-2"></i>
                        <strong>Código Automático:</strong> El sistema generará automáticamente un código único para este producto.
                        No necesitas especificar un código manualmente.
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="fas fa-tag text-muted me-1"></i>
                                    Nombre del Producto
                                </label>
                                <input type="text" class="form-control" name="nombre" required 
                                       placeholder="Nombre descriptivo" maxlength="100">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-align-left text-muted me-1"></i>
                            Descripción
                        </label>
                        <textarea class="form-control" name="descripcion" rows="3" 
                                  placeholder="Descripción detallada del producto (opcional)"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="fas fa-truck text-muted me-1"></i>
                                    Proveedor
                                    <span id="proveedorIndicador" class="badge bg-info ms-2 d-none">Auto-detectado</span>
                                </label>
                                <select class="form-select" name="proveedor_id" id="proveedorSelect">
                                    <option value="">Seleccionar proveedor...</option>
                                    <?php foreach ($proveedores as $prov): ?>
                                        <option value="<?php echo $prov['id']; ?>"><?php echo htmlspecialchars($prov['nombre']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text" id="proveedorInfo">
                                    <span id="proveedorTextoElemento" class="d-none">
                                        <i class="fas fa-info-circle"></i> Para elementos individuales, selecciona el proveedor manualmente.
                                    </span>
                                    <span id="proveedorTextoConjunto" class="d-none">
                                        <i class="fas fa-magic"></i> El proveedor se seleccionará automáticamente basado en los componentes.
                                    </span>
                                    <span id="proveedorDetalleMixto" class="d-none text-warning">
                                        <i class="fas fa-exclamation-triangle"></i> <strong>Componentes de múltiples proveedores:</strong> <span id="listaProveedores"></span>
                                    </span>
                                    <span id="proveedorDetalleUnico" class="d-none text-success">
                                        <i class="fas fa-check-circle"></i> <strong>Todos los componentes del mismo proveedor:</strong> <span id="proveedorUnico"></span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="fas fa-shopping-cart text-muted me-1"></i>
                                    Precio de Venta
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">Q</span>
                                    <input type="number" step="0.01" min="0" class="form-control" 
                                           name="precio_venta" required placeholder="0.00">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="fas fa-dollar-sign text-muted me-1"></i>
                                    Precio de Compra
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">Q</span>
                                    <input type="number" step="0.01" min="0" class="form-control" 
                                           name="precio_compra" required placeholder="0.00">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info" id="infoTipoProducto">
                        <!-- Se llenará dinámicamente con JavaScript -->
                    </div>
                    
                    <!-- Sección de Componentes para Conjuntos -->
                    <div id="seccionComponentes" class="d-none">
                        <hr>
                        <h6 class="text-success mb-3">
                            <i class="fas fa-cubes"></i> Componentes del Conjunto
                        </h6>
                        <div class="alert alert-warning">
                            <i class="fas fa-info-circle"></i>
                            <strong>Importante:</strong> Agrega los elementos que formarán parte de este conjunto. 
                            Puedes agregar múltiples componentes con sus respectivas cantidades.
                        </div>
                        
                        <div id="componentesContainer">
                            <div class="componente-row mb-3">
                                <div class="row">
                                    <div class="col-md-7">
                                        <label class="form-label small">Elemento Componente</label>
                                        <select class="form-select" name="componentes_elementos[]" onchange="actualizarProveedorConjunto()">
                                            <option value="">Seleccionar elemento...</option>
                                            <?php foreach ($elementos as $elemento): ?>
                                                <option value="<?php echo $elemento['id']; ?>" 
                                                        data-proveedor-id="<?php echo $elemento['proveedor_id'] ?? ''; ?>"
                                                        data-proveedor-nombre="<?php echo htmlspecialchars($elemento['proveedor_nombre'] ?? 'Sin proveedor'); ?>">
                                                    <?php echo htmlspecialchars($elemento['codigo'] . ' - ' . $elemento['nombre']); ?>
                                                    <?php if ($elemento['proveedor_nombre']): ?>
                                                        (<?php echo htmlspecialchars($elemento['proveedor_nombre']); ?>)
                                                    <?php endif; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small">Cantidad</label>
                                        <input type="number" class="form-control" name="componentes_cantidades[]" 
                                               min="1" value="1" placeholder="1">
                                    </div>
                                    <div class="col-md-2 d-flex align-items-end">
                                        <button type="button" class="btn btn-outline-danger btn-sm w-100" 
                                                onclick="eliminarComponente(this)" title="Eliminar componente">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <button type="button" class="btn btn-outline-success btn-sm" onclick="agregarComponente()">
                                <i class="fas fa-plus"></i> Agregar Otro Componente
                            </button>
                            <small class="text-muted">
                                <?php if (empty($elementos)): ?>
                                    <i class="fas fa-exclamation-triangle text-warning"></i>
                                    No hay elementos disponibles. Crea elementos primero.
                                <?php else: ?>
                                    Elementos disponibles: <?php echo count($elementos); ?>
                                <?php endif; ?>
                            </small>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="button" class="btn btn-outline-primary" onclick="volverATipoProducto()">
                        <i class="fas fa-arrow-left"></i> Cambiar Tipo
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Crear Producto
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Agregar Componente -->
<div class="modal fade" id="modalAgregarComponente" tabindex="-1" aria-labelledby="modalAgregarComponenteLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="agregar_componente">
                <?php echo campoCSRF(); ?>
                
                <div class="modal-header">
                    <h5 class="modal-title" id="modalAgregarComponenteLabel">
                        <i class="fas fa-link"></i> Agregar Componente a Conjunto
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Información:</strong> Los conjuntos están formados por elementos individuales. 
                        Selecciona el conjunto al que quieres agregar un componente y el elemento que lo formará.
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="fas fa-cubes text-muted me-1"></i>
                                    Producto Conjunto
                                </label>
                                <select class="form-select" name="producto_conjunto_id" required>
                                    <option value="">Seleccionar Conjunto</option>
                                    <?php foreach ($conjuntos as $conjunto): ?>
                                        <option value="<?php echo $conjunto['id']; ?>">
                                            <?php echo htmlspecialchars($conjunto['nombre']); ?> 
                                            (<?php echo htmlspecialchars($conjunto['codigo']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (empty($conjuntos)): ?>
                                    <div class="form-text text-warning">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        No hay conjuntos disponibles. Crea un conjunto primero.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="fas fa-cube text-muted me-1"></i>
                                    Elemento Componente
                                </label>
                                <select class="form-select" name="producto_elemento_id" required>
                                    <option value="">Seleccionar Elemento</option>
                                    <?php foreach ($elementos as $elemento): ?>
                                        <option value="<?php echo $elemento['id']; ?>">
                                            <?php echo htmlspecialchars($elemento['nombre']); ?> 
                                            (<?php echo htmlspecialchars($elemento['codigo']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (empty($elementos)): ?>
                                    <div class="form-text text-warning">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        No hay elementos disponibles. Crea elementos primero.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="fas fa-sort-numeric-up text-muted me-1"></i>
                                    Cantidad
                                </label>
                                <input type="number" class="form-control" name="cantidad" value="1" min="1" required>
                                <div class="form-text">Cantidad de este elemento en el conjunto</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-success" <?php echo (empty($conjuntos) || empty($elementos)) ? 'disabled' : ''; ?>>
                        <i class="fas fa-link"></i> Agregar Componente
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.card-hover {
    transition: all 0.3s ease;
}

.card-hover:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.modal-lg {
    max-width: 800px;
}

.input-group-text {
    font-weight: 600;
}
</style>

<script>
function abrirModalProducto(tipo) {
    // Cerrar modal de selección
    const modalTipo = bootstrap.Modal.getInstance(document.getElementById('modalTipoProducto'));
    modalTipo.hide();
    
    // Configurar el modal de creación según el tipo
    document.getElementById('tipoProductoSeleccionado').value = tipo;
    
    const titulo = document.getElementById('tituloModalProducto');
    const info = document.getElementById('infoTipoProducto');
    const seccionComponentes = document.getElementById('seccionComponentes');
    
    if (tipo === 'elemento') {
        titulo.innerHTML = '<i class="fas fa-cube text-primary"></i> Crear Elemento Individual';
        info.innerHTML = `
            <i class="fas fa-cube text-primary me-2"></i>
            <strong>Elemento Individual:</strong> Este será un producto único que no depende de otros elementos. 
            Podrás usarlo directamente en ventas o como componente de conjuntos.
        `;
        info.className = 'alert alert-primary';
        seccionComponentes.classList.add('d-none');
        
        // Mostrar texto para elementos
        mostrarTextoProveedor('elemento');
        
        // Hacer proveedor requerido para elementos
        document.getElementById('proveedorSelect').setAttribute('required', 'required');
    } else {
        titulo.innerHTML = '<i class="fas fa-cubes text-success"></i> Crear Conjunto/Kit';
        info.innerHTML = `
            <i class="fas fa-cubes text-success me-2"></i>
            <strong>Conjunto/Kit:</strong> Este será un producto compuesto por múltiples elementos. 
            Puedes agregar los componentes directamente mientras creas el conjunto.
        `;
        info.className = 'alert alert-success';
        seccionComponentes.classList.remove('d-none');
        
        // Mostrar texto para conjuntos y resetear componentes
        mostrarTextoProveedor('conjunto');
        resetearComponentes();
        
        // Limpiar selección de proveedor
        document.getElementById('proveedorSelect').value = '';
        document.getElementById('proveedorIndicador').classList.add('d-none');
        
        // Para conjuntos, el proveedor no es requerido inicialmente
        document.getElementById('proveedorSelect').removeAttribute('required');
    }
    
    // Abrir modal de creación
    setTimeout(() => {
        new bootstrap.Modal(document.getElementById('modalCrearProducto')).show();
    }, 300);
}

function volverATipoProducto() {
    // Cerrar modal de creación
    const modalCrear = bootstrap.Modal.getInstance(document.getElementById('modalCrearProducto'));
    modalCrear.hide();
    
    // Abrir modal de selección
    setTimeout(() => {
        new bootstrap.Modal(document.getElementById('modalTipoProducto')).show();
    }, 300);
}

// Validación del formulario
document.getElementById('formCrearProducto').addEventListener('submit', function(e) {
    const precioVenta = parseFloat(document.querySelector('[name="precio_venta"]').value);
    const precioCompra = parseFloat(document.querySelector('[name="precio_compra"]').value);
    
    if (precioVenta < precioCompra) {
        e.preventDefault();
        showWarning('El precio de venta no puede ser menor que el precio de compra');
        return false;
    }
    
    // Deshabilitar botón de envío para evitar doble submit
    const submitBtn = this.querySelector('[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creando...';
});
// Bulk assign helpers
const selectAll = document.getElementById('selectAllProductos');
const checkboxes = () => Array.from(document.querySelectorAll('.prod-checkbox'));
const bulkForms = Array.from(document.querySelectorAll('.bulk-form'));

if (selectAll) {
  selectAll.addEventListener('change', (e) => {
    checkboxes().forEach(cb => { cb.checked = e.target.checked; });
  });
}

bulkForms.forEach(form => {
  form.addEventListener('submit', (e) => {
    const seleccionados = checkboxes().filter(cb => cb.checked).map(cb => cb.value);
    if (seleccionados.length === 0) {
      e.preventDefault();
      if (typeof showToast === 'function') showToast('Selecciona al menos un producto', 'warning');
      else alert('Selecciona al menos un producto');
      return;
    }
    // Limpiar inputs previos y agregar inputs hidden dentro del propio form
    Array.from(form.querySelectorAll('input[name="producto_ids[]"]')).forEach(n => n.remove());
    seleccionados.forEach(id => {
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'producto_ids[]';
      input.value = id;
      form.appendChild(input);
    });
  });
});
</script>

<script>
// Rellenar modal de edición con datos del producto
const modalEditarProducto = document.getElementById('modalEditarProducto');
if (modalEditarProducto) {
  modalEditarProducto.addEventListener('show.bs.modal', event => {
    const btn = event.relatedTarget;
    document.getElementById('editarProductoId').value = btn.getAttribute('data-id') || '';
    document.getElementById('editarCodigo').value = btn.getAttribute('data-codigo') || '';
    document.getElementById('editarNombre').value = btn.getAttribute('data-nombre') || '';
    document.getElementById('editarDescripcion').value = btn.getAttribute('data-descripcion') || '';
    document.getElementById('editarPrecioVenta').value = btn.getAttribute('data-precioventa') || '';
    document.getElementById('editarPrecioCompra').value = btn.getAttribute('data-preciocompra') || '';
    document.getElementById('editarTipo').value = btn.getAttribute('data-tipo') || 'elemento';
    const prov = btn.getAttribute('data-proveedor') || '';
    document.getElementById('editarProveedor').value = prov;
  });

  // Validación simple precios
  document.getElementById('formEditarProducto').addEventListener('submit', function(e){
    const pv = parseFloat(document.getElementById('editarPrecioVenta').value || '0');
    const pc = parseFloat(document.getElementById('editarPrecioCompra').value || '0');
    if (pv < pc) {
      e.preventDefault();
      if (typeof showWarning === 'function') showWarning('El precio de venta no puede ser menor que el precio de compra');
      else alert('El precio de venta no puede ser menor que el precio de compra');
    }
  });
}

// ===== FUNCIONES DE GESTIÓN QR =====
async function generarQR(productoId) {
    try {
        const response = await fetch('ajax/qr_actions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'generar',
                producto_id: productoId
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showSuccess('Código QR generado exitosamente');
            // Recargar página para mostrar el QR generado
            setTimeout(() => location.reload(), 1500);
        } else {
            showError('Error generando QR: ' + (result.error || 'Error desconocido'));
        }
    } catch (error) {
        console.error('Error:', error);
        showError('Error de conexión al generar QR');
    }
}

async function regenerarQR(productoId) {
    if (!confirm('¿Estás seguro de regenerar el código QR? El código anterior quedará inválido.')) {
        return;
    }
    
    try {
        const response = await fetch('ajax/qr_actions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'regenerar',
                producto_id: productoId
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showSuccess('Código QR regenerado exitosamente');
            // Recargar página para mostrar el nuevo QR
            setTimeout(() => location.reload(), 1500);
        } else {
            showError('Error regenerando QR: ' + (result.error || 'Error desconocido'));
        }
    } catch (error) {
        console.error('Error:', error);
        showError('Error de conexión al regenerar QR');
    }
}

function descargarQR(productoId, tipo) {
    // Crear URL de descarga
    const url = `qr_download.php?producto_id=${productoId}&tipo=${tipo}`;
    
    // Crear enlace temporal y hacer clic
    const link = document.createElement('a');
    link.href = url;
    link.download = true;
    link.style.display = 'none';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    showInfo(`Descargando ${tipo === 'imagen' ? 'imagen QR' : 'etiqueta imprimible'}...`);
}

// Función helper para mostrar mensajes
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

function showInfo(mensaje) {
    if (typeof showToast === 'function') {
        showToast(mensaje, 'info');
    } else {
        alert(mensaje);
    }
}

// ===== FUNCIONES DE GESTIÓN DE COMPONENTES =====
function resetearComponentes() {
    const container = document.getElementById('componentesContainer');
    const elementosOptions = `
        <option value="">Seleccionar elemento...</option>
        <?php foreach ($elementos as $elemento): ?>
            <option value="<?php echo $elemento['id']; ?>" 
                    data-proveedor-id="<?php echo $elemento['proveedor_id'] ?? ''; ?>"
                    data-proveedor-nombre="<?php echo htmlspecialchars($elemento['proveedor_nombre'] ?? 'Sin proveedor'); ?>">
                <?php echo htmlspecialchars($elemento['codigo'] . ' - ' . $elemento['nombre']); ?>
                <?php if ($elemento['proveedor_nombre']): ?>
                    (<?php echo htmlspecialchars($elemento['proveedor_nombre']); ?>)
                <?php endif; ?>
            </option>
        <?php endforeach; ?>
    `;
    
    container.innerHTML = `
        <div class="componente-row mb-3">
            <div class="row">
                <div class="col-md-7">
                    <label class="form-label small">Elemento Componente</label>
                    <select class="form-select" name="componentes_elementos[]" onchange="actualizarProveedorConjunto()">
                        ${elementosOptions}
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Cantidad</label>
                    <input type="number" class="form-control" name="componentes_cantidades[]" 
                           min="1" value="1" placeholder="1">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="button" class="btn btn-outline-danger btn-sm w-100" 
                            onclick="eliminarComponente(this)" title="Eliminar componente">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
}

function agregarComponente() {
    const container = document.getElementById('componentesContainer');
    const elementosOptions = `
        <option value="">Seleccionar elemento...</option>
        <?php foreach ($elementos as $elemento): ?>
            <option value="<?php echo $elemento['id']; ?>" 
                    data-proveedor-id="<?php echo $elemento['proveedor_id'] ?? ''; ?>"
                    data-proveedor-nombre="<?php echo htmlspecialchars($elemento['proveedor_nombre'] ?? 'Sin proveedor'); ?>">
                <?php echo htmlspecialchars($elemento['codigo'] . ' - ' . $elemento['nombre']); ?>
                <?php if ($elemento['proveedor_nombre']): ?>
                    (<?php echo htmlspecialchars($elemento['proveedor_nombre']); ?>)
                <?php endif; ?>
            </option>
        <?php endforeach; ?>
    `;
    
    const nuevaFila = document.createElement('div');
    nuevaFila.className = 'componente-row mb-3';
    nuevaFila.innerHTML = `
        <div class="row">
            <div class="col-md-7">
                <label class="form-label small">Elemento Componente</label>
                <select class="form-select" name="componentes_elementos[]" onchange="actualizarProveedorConjunto()">
                    ${elementosOptions}
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small">Cantidad</label>
                <input type="number" class="form-control" name="componentes_cantidades[]" 
                       min="1" value="1" placeholder="1">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="button" class="btn btn-outline-danger btn-sm w-100" 
                        onclick="eliminarComponente(this)" title="Eliminar componente">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `;
    
    container.appendChild(nuevaFila);
}

function eliminarComponente(button) {
    const container = document.getElementById('componentesContainer');
    const filas = container.querySelectorAll('.componente-row');
    
    // No permitir eliminar si solo hay una fila
    if (filas.length > 1) {
        button.closest('.componente-row').remove();
        // Actualizar proveedor después de eliminar
        actualizarProveedorConjunto();
    } else {
        showInfo('Debe haber al menos un componente en el conjunto');
    }
}

// ===== FUNCIONES DE GESTIÓN AUTOMÁTICA DE PROVEEDORES =====
function mostrarTextoProveedor(tipo) {
    // Ocultar todos los textos
    document.getElementById('proveedorTextoElemento').classList.add('d-none');
    document.getElementById('proveedorTextoConjunto').classList.add('d-none');
    document.getElementById('proveedorDetalleMixto').classList.add('d-none');
    document.getElementById('proveedorDetalleUnico').classList.add('d-none');
    
    // Mostrar el texto apropiado
    if (tipo === 'elemento') {
        document.getElementById('proveedorTextoElemento').classList.remove('d-none');
        document.getElementById('proveedorSelect').removeAttribute('disabled');
    } else if (tipo === 'conjunto') {
        document.getElementById('proveedorTextoConjunto').classList.remove('d-none');
    }
}

function actualizarProveedorConjunto() {
    const componentesSelects = document.querySelectorAll('select[name="componentes_elementos[]"]');
    const proveedores = new Map(); // Map para almacenar proveedores únicos
    const elementosSeleccionados = [];
    
    // Recopilar información de proveedores de elementos seleccionados
    componentesSelects.forEach(select => {
        const selectedOption = select.options[select.selectedIndex];
        if (selectedOption && selectedOption.value) {
            const proveedorId = selectedOption.getAttribute('data-proveedor-id');
            const proveedorNombre = selectedOption.getAttribute('data-proveedor-nombre');
            
            elementosSeleccionados.push({
                elemento: selectedOption.textContent,
                proveedorId: proveedorId || null,
                proveedorNombre: proveedorNombre || 'Sin proveedor'
            });
            
            // Agregar proveedor al Map (clave: ID, valor: nombre)
            if (proveedorId) {
                proveedores.set(proveedorId, proveedorNombre);
            } else {
                proveedores.set('null', 'Sin proveedor');
            }
        }
    });
    
    // No hacer nada si no hay elementos seleccionados
    if (elementosSeleccionados.length === 0) {
        mostrarTextoProveedor('conjunto');
        return;
    }
    
    const proveedorSelect = document.getElementById('proveedorSelect');
    const proveedorIndicador = document.getElementById('proveedorIndicador');
    
    // Ocultar todos los textos de estado
    mostrarTextoProveedor('ninguno');
    
    if (proveedores.size === 1) {
        // Todos los componentes son del mismo proveedor
        const [proveedorId, proveedorNombre] = proveedores.entries().next().value;
        
        // Actualizar select automáticamente
        if (proveedorId !== 'null') {
            proveedorSelect.value = proveedorId;
            proveedorIndicador.classList.remove('d-none');
            proveedorSelect.setAttribute('disabled', 'disabled');
            
            // Mostrar mensaje de éxito
            document.getElementById('proveedorDetalleUnico').classList.remove('d-none');
            document.getElementById('proveedorUnico').textContent = proveedorNombre;
        } else {
            // Todos sin proveedor
            proveedorSelect.value = '';
            proveedorIndicador.classList.add('d-none');
            proveedorSelect.removeAttribute('disabled');
            
            document.getElementById('proveedorDetalleMixto').classList.remove('d-none');
            document.getElementById('listaProveedores').textContent = 'Todos los elementos sin proveedor asignado';
        }
    } else {
        // Múltiples proveedores
        proveedorSelect.value = '';
        proveedorIndicador.classList.add('d-none');
        proveedorSelect.removeAttribute('disabled');
        
        // Mostrar lista de proveedores
        const listaProveedoresNombres = Array.from(proveedores.values()).join(', ');
        document.getElementById('proveedorDetalleMixto').classList.remove('d-none');
        document.getElementById('listaProveedores').textContent = listaProveedoresNombres;
    }
}
</script>

<?php include 'includes/layout_footer.php'; ?>
