<?php
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'includes/config_functions.php';
require_once 'includes/exportacion.php';

verificarLogin();

// Verificar que tenga permisos para exportar
if (!esAdmin() && !tienePermiso('reportes', 'leer')) {
    header('Location: sin_permisos.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();
$exportacion = new Exportacion($db);

// Procesar la exportación si se envía el formulario
if ($_POST && isset($_POST['tipo_exportacion'])) {
    $tipo = $_POST['tipo_exportacion'];
    $formato = $_POST['formato'] ?? 'csv';
    $filtros = [];
    
    // Recoger filtros según el tipo de exportación
    switch ($tipo) {
        case 'usuarios':
            if (!empty($_POST['rol_id'])) $filtros['rol_id'] = $_POST['rol_id'];
            if (!empty($_POST['activo'])) $filtros['activo'] = $_POST['activo'];
            $exportacion->exportarUsuarios($formato, $filtros);
            break;
            
        case 'productos':
            if (!empty($_POST['categoria'])) $filtros['categoria'] = $_POST['categoria'];
            if (!empty($_POST['tipo_producto'])) $filtros['tipo'] = $_POST['tipo_producto'];
            $exportacion->exportarProductos($formato, $filtros);
            break;
            
        case 'ventas':
            if (!empty($_POST['fecha_inicio'])) $filtros['fecha_inicio'] = $_POST['fecha_inicio'];
            if (!empty($_POST['fecha_fin'])) $filtros['fecha_fin'] = $_POST['fecha_fin'];
            if (!empty($_POST['tienda_id'])) $filtros['tienda_id'] = $_POST['tienda_id'];
            $exportacion->exportarVentas($formato, $filtros);
            break;
            
        case 'inventarios':
            if (!empty($_POST['tienda_id'])) $filtros['tienda_id'] = $_POST['tienda_id'];
            if (!empty($_POST['categoria'])) $filtros['categoria'] = $_POST['categoria'];
            if (!empty($_POST['stock_bajo'])) $filtros['stock_bajo'] = true;
            $exportacion->exportarInventarios($formato, $filtros);
            break;
    }
}

// Obtener datos para los filtros
$roles = $db->query("SELECT * FROM roles WHERE activo = 1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$tiendas = $db->query("SELECT * FROM tiendas WHERE activo = 1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$categorias = $db->query("SELECT DISTINCT c.nombre FROM productos p LEFT JOIN categorias c ON p.categoria_id = c.id WHERE c.nombre IS NOT NULL ORDER BY c.nombre")->fetchAll(PDO::FETCH_COLUMN);

require_once 'includes/layout_header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>
                    <i class="fas fa-download"></i> 
                    <span class="editable" data-label="exportacion_titulo">Exportación de Datos</span>
                </h2>
                <div class="text-muted">
                    <i class="fas fa-info-circle"></i>
                    Descarga información del sistema en diferentes formatos
                </div>
            </div>

            <!-- Información importante -->
            <div class="alert alert-info">
                <h6><i class="fas fa-info-circle"></i> Información sobre las exportaciones</h6>
                <ul class="mb-0">
                    <li><strong>CSV:</strong> Archivo separado por comas, compatible con Excel y hojas de cálculo</li>
                    <li><strong>Excel:</strong> Archivo .xls que se abre directamente en Microsoft Excel</li>
                    <li><strong>JSON:</strong> Formato de datos estructurado para desarrolladores</li>
                    <li><strong>PDF:</strong> Documento listo para imprimir (se abre ventana de impresión)</li>
                </ul>
            </div>

            <!-- Tabs de exportación -->
            <div class="card">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs" id="exportTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="usuarios-tab" data-bs-toggle="tab" data-bs-target="#usuarios" type="button">
                                <i class="fas fa-users"></i> Usuarios
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="productos-tab" data-bs-toggle="tab" data-bs-target="#productos" type="button">
                                <i class="fas fa-box"></i> Productos
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="ventas-tab" data-bs-toggle="tab" data-bs-target="#ventas" type="button">
                                <i class="fas fa-shopping-cart"></i> Ventas
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="inventarios-tab" data-bs-toggle="tab" data-bs-target="#inventarios" type="button">
                                <i class="fas fa-warehouse"></i> Inventarios
                            </button>
                        </li>
                    </ul>
                </div>
                
                <div class="card-body">
                    <div class="tab-content" id="exportTabsContent">
                        
                        <!-- Exportación de Usuarios -->
                        <div class="tab-pane fade show active" id="usuarios" role="tabpanel">
                            <h5><i class="fas fa-users"></i> Exportar Usuarios</h5>
                            <p class="text-muted">Exporta la lista de usuarios del sistema con sus roles y estado.</p>
                            
                            <form method="POST" class="row g-3">
                                <input type="hidden" name="tipo_exportacion" value="usuarios">
                                
                                <div class="col-md-4">
                                    <label class="form-label">Formato de exportación</label>
                                    <select name="formato" class="form-select" required>
                                        <option value="csv">CSV (Excel)</option>
                                        <option value="excel">Excel (.xls)</option>
                                        <option value="json">JSON</option>
                                        <option value="pdf">PDF</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-4">
                                    <label class="form-label">Filtrar por rol</label>
                                    <select name="rol_id" class="form-select">
                                        <option value="">Todos los roles</option>
                                        <?php foreach ($roles as $rol): ?>
                                            <option value="<?php echo $rol['id']; ?>"><?php echo $rol['nombre']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-4">
                                    <label class="form-label">Estado</label>
                                    <select name="activo" class="form-select">
                                        <option value="">Todos los estados</option>
                                        <option value="1">Solo activos</option>
                                        <option value="0">Solo inactivos</option>
                                    </select>
                                </div>
                                
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-download"></i> Exportar Usuarios
                                    </button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Exportación de Productos -->
                        <div class="tab-pane fade" id="productos" role="tabpanel">
                            <h5><i class="fas fa-box"></i> Exportar Productos</h5>
                            <p class="text-muted">Exporta el catálogo completo de productos con precios y categorías.</p>
                            
                            <form method="POST" class="row g-3">
                                <input type="hidden" name="tipo_exportacion" value="productos">
                                
                                <div class="col-md-4">
                                    <label class="form-label">Formato de exportación</label>
                                    <select name="formato" class="form-select" required>
                                        <option value="csv">CSV (Excel)</option>
                                        <option value="excel">Excel (.xls)</option>
                                        <option value="json">JSON</option>
                                        <option value="pdf">PDF</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-4">
                                    <label class="form-label">Filtrar por categoría</label>
                                    <select name="categoria" class="form-select">
                                        <option value="">Todas las categorías</option>
                                        <?php foreach ($categorias as $categoria): ?>
                                            <option value="<?php echo $categoria; ?>"><?php echo $categoria; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-4">
                                    <label class="form-label">Tipo de producto</label>
                                    <select name="tipo_producto" class="form-select">
                                        <option value="">Todos los tipos</option>
                                        <option value="elemento">Solo elementos</option>
                                        <option value="conjunto">Solo conjuntos</option>
                                    </select>
                                </div>
                                
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-download"></i> Exportar Productos
                                    </button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Exportación de Ventas -->
                        <div class="tab-pane fade" id="ventas" role="tabpanel">
                            <h5><i class="fas fa-shopping-cart"></i> Exportar Ventas</h5>
                            <p class="text-muted">Exporta el historial de ventas con detalles de vendedores y totales.</p>
                            
                            <form method="POST" class="row g-3">
                                <input type="hidden" name="tipo_exportacion" value="ventas">
                                
                                <div class="col-md-3">
                                    <label class="form-label">Formato de exportación</label>
                                    <select name="formato" class="form-select" required>
                                        <option value="csv">CSV (Excel)</option>
                                        <option value="excel">Excel (.xls)</option>
                                        <option value="json">JSON</option>
                                        <option value="pdf">PDF</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-3">
                                    <label class="form-label">Fecha inicio</label>
                                    <input type="date" name="fecha_inicio" class="form-control">
                                </div>
                                
                                <div class="col-md-3">
                                    <label class="form-label">Fecha fin</label>
                                    <input type="date" name="fecha_fin" class="form-control">
                                </div>
                                
                                <div class="col-md-3">
                                    <label class="form-label">Filtrar por tienda</label>
                                    <select name="tienda_id" class="form-select">
                                        <option value="">Todas las tiendas</option>
                                        <?php foreach ($tiendas as $tienda): ?>
                                            <option value="<?php echo $tienda['id']; ?>"><?php echo $tienda['nombre']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-download"></i> Exportar Ventas
                                    </button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Exportación de Inventarios -->
                        <div class="tab-pane fade" id="inventarios" role="tabpanel">
                            <h5><i class="fas fa-warehouse"></i> Exportar Inventarios</h5>
                            <p class="text-muted">Exporta el estado actual de inventarios por tienda con alertas de stock.</p>
                            
                            <form method="POST" class="row g-3">
                                <input type="hidden" name="tipo_exportacion" value="inventarios">
                                
                                <div class="col-md-4">
                                    <label class="form-label">Formato de exportación</label>
                                    <select name="formato" class="form-select" required>
                                        <option value="csv">CSV (Excel)</option>
                                        <option value="excel">Excel (.xls)</option>
                                        <option value="json">JSON</option>
                                        <option value="pdf">PDF</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-4">
                                    <label class="form-label">Filtrar por tienda</label>
                                    <select name="tienda_id" class="form-select">
                                        <option value="">Todas las tiendas</option>
                                        <?php foreach ($tiendas as $tienda): ?>
                                            <option value="<?php echo $tienda['id']; ?>"><?php echo $tienda['nombre']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-4">
                                    <label class="form-label">Filtrar por categoría</label>
                                    <select name="categoria" class="form-select">
                                        <option value="">Todas las categorías</option>
                                        <?php foreach ($categorias as $categoria): ?>
                                            <option value="<?php echo $categoria; ?>"><?php echo $categoria; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="stock_bajo" value="1" id="stockBajo">
                                        <label class="form-check-label" for="stockBajo">
                                            Solo productos con stock bajo (cantidad ≤ mínimo)
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-download"></i> Exportar Inventarios
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Estadísticas de exportación -->
            <div class="row mt-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body text-center">
                            <i class="fas fa-users fa-2x mb-2"></i>
                            <h4><?php echo $db->query("SELECT COUNT(*) FROM usuarios")->fetchColumn(); ?></h4>
                            <p class="mb-0">Usuarios totales</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body text-center">
                            <i class="fas fa-box fa-2x mb-2"></i>
                            <h4><?php echo $db->query("SELECT COUNT(*) FROM productos WHERE activo = 1")->fetchColumn(); ?></h4>
                            <p class="mb-0">Productos activos</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body text-center">
                            <i class="fas fa-shopping-cart fa-2x mb-2"></i>
                            <h4><?php echo $db->query("SELECT COUNT(*) FROM ventas")->fetchColumn(); ?></h4>
                            <p class="mb-0">Ventas registradas</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body text-center">
                            <i class="fas fa-warehouse fa-2x mb-2"></i>
                            <h4><?php echo $db->query("SELECT COUNT(*) FROM inventarios")->fetchColumn(); ?></h4>
                            <p class="mb-0">Items en inventario</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Prevenir doble envío de formularios
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Exportando...';
                submitBtn.disabled = true;
                
                // Reactivar después de 5 segundos en caso de error
                setTimeout(() => {
                    submitBtn.innerHTML = '<i class="fas fa-download"></i> Exportar';
                    submitBtn.disabled = false;
                }, 5000);
            }
        });
    });
});
</script>

<?php require_once 'includes/layout_footer.php'; ?>