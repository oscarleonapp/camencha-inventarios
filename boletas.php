<?php
$titulo = "Gestión de Boletas - Sistema de Inventarios";
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'includes/csrf_protection.php';

verificarLogin();
verificarPermiso('boletas_ver');

$database = new Database();
$db = $database->getConnection();

$success = '';
$error = '';

// Procesar subida de boleta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'subir_boleta') {
    validarCSRF();
    
    $numero_boleta = trim($_POST['numero_boleta'] ?? '');
    $fecha = $_POST['fecha'] ?? '';
    $proveedor = trim($_POST['proveedor'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $usuario_id = $_SESSION['usuario_id'];
    
    // Validar campos obligatorios
    if (empty($numero_boleta) || empty($fecha) || empty($proveedor) || empty($descripcion)) {
        $error = "Todos los campos son obligatorios";
    } 
    // Validar que se haya subido una imagen
    elseif (!isset($_FILES['imagen']) || $_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
        $error = "Debe seleccionar una imagen válida";
    } 
    else {
        try {
            $db->beginTransaction();
            
            // Verificar que el número de boleta no exista
            $stmt_check = $db->prepare("SELECT id FROM boletas WHERE numero_boleta = ?");
            $stmt_check->execute([$numero_boleta]);
            if ($stmt_check->fetch()) {
                throw new Exception("El número de boleta ya existe en el sistema");
            }
            
            // Validar archivo de imagen
            $archivo = $_FILES['imagen'];
            $nombre_original = $archivo['name'];
            $tamaño = $archivo['size'];
            $tipo = $archivo['type'];
            $tmp_name = $archivo['tmp_name'];
            
            // Validar tipo de archivo
            $tipos_permitidos = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            if (!in_array($tipo, $tipos_permitidos)) {
                throw new Exception("Solo se permiten archivos de imagen (JPG, PNG, GIF)");
            }
            
            // Validar tamaño (máximo 5MB)
            if ($tamaño > 5 * 1024 * 1024) {
                throw new Exception("El archivo no puede ser mayor a 5MB");
            }
            
            // Generar nombre único para el archivo
            $extension = pathinfo($nombre_original, PATHINFO_EXTENSION);
            $nombre_nuevo = $numero_boleta . '_' . date('YmdHis') . '.' . $extension;
            $ruta_destino = 'uploads/boletas/' . $nombre_nuevo;
            
            // Mover archivo a la carpeta de destino
            if (!move_uploaded_file($tmp_name, $ruta_destino)) {
                throw new Exception("Error al subir el archivo");
            }
            
            // Insertar en la base de datos
            $stmt_insert = $db->prepare("INSERT INTO boletas (numero_boleta, fecha_boleta, proveedor, descripcion, imagen_path, usuario_id) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_insert->execute([$numero_boleta, $fecha, $proveedor, $descripcion, $ruta_destino, $usuario_id]);
            
            $db->commit();
            $success = "Boleta subida correctamente";
            
            // Limpiar formulario
            $_POST = [];
            
        } catch (Exception $e) {
            $db->rollBack();
            
            // Eliminar archivo si se subió pero falló la BD
            if (isset($ruta_destino) && file_exists($ruta_destino)) {
                unlink($ruta_destino);
            }
            
            $error = "Error al subir la boleta: " . $e->getMessage();
        }
    }
}

// Procesar eliminación de boleta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'eliminar_boleta') {
    validarCSRF();
    verificarPermiso('boletas_eliminar');
    
    $boleta_id = (int)$_POST['boleta_id'];
    
    try {
        $db->beginTransaction();
        
        // Obtener datos de la boleta para eliminar archivo
        $stmt_get = $db->prepare("SELECT imagen_path FROM boletas WHERE id = ?");
        $stmt_get->execute([$boleta_id]);
        $boleta = $stmt_get->fetch(PDO::FETCH_ASSOC);
        
        if (!$boleta) {
            throw new Exception("Boleta no encontrada");
        }
        
        // Eliminar registro de la base de datos
        $stmt_delete = $db->prepare("DELETE FROM boletas WHERE id = ?");
        $stmt_delete->execute([$boleta_id]);
        
        // Eliminar archivo físico
        if (file_exists($boleta['imagen_path'])) {
            unlink($boleta['imagen_path']);
        }
        
        $db->commit();
        $success = "Boleta eliminada correctamente";
        
    } catch (Exception $e) {
        $db->rollBack();
        $error = "Error al eliminar la boleta: " . $e->getMessage();
    }
}

// Obtener filtros
$filtro_numero = $_GET['numero'] ?? '';
$filtro_proveedor = $_GET['proveedor'] ?? '';
$filtro_fecha_desde = $_GET['fecha_desde'] ?? '';
$filtro_fecha_hasta = $_GET['fecha_hasta'] ?? '';

// Construir query con filtros
$where_conditions = [];
$params = [];

if (!empty($filtro_numero)) {
    $where_conditions[] = "b.numero_boleta LIKE ?";
    $params[] = "%$filtro_numero%";
}

if (!empty($filtro_proveedor)) {
    $where_conditions[] = "b.proveedor LIKE ?";
    $params[] = "%$filtro_proveedor%";
}

if (!empty($filtro_fecha_desde)) {
    $where_conditions[] = "b.fecha_boleta >= ?";
    $params[] = $filtro_fecha_desde;
}

if (!empty($filtro_fecha_hasta)) {
    $where_conditions[] = "b.fecha_boleta <= ?";
    $params[] = $filtro_fecha_hasta;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Obtener boletas
$query_boletas = "SELECT b.*, b.created_at as fecha_creacion, u.nombre as usuario_nombre 
                  FROM boletas b 
                  JOIN usuarios u ON b.usuario_id = u.id 
                  $where_clause 
                  ORDER BY b.created_at DESC";

$stmt_boletas = $db->prepare($query_boletas);
$stmt_boletas->execute($params);
$boletas = $stmt_boletas->fetchAll(PDO::FETCH_ASSOC);

// Estadísticas
$stmt_stats = $db->prepare("SELECT 
    COUNT(*) as total_boletas,
    COUNT(DISTINCT proveedor) as total_proveedores,
    0 as total_size
    FROM boletas");
$stmt_stats->execute();
$stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);

include 'includes/layout_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-receipt"></i> Gestión de Boletas</h2>
    <div class="btn-group">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalSubirBoleta">
            <i class="fas fa-plus"></i> Subir Boleta
        </button>
        <button class="btn btn-outline-info" onclick="mostrarEstadisticas()">
            <i class="fas fa-chart-bar"></i> Estadísticas
        </button>
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

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label small">Número de Boleta</label>
                <input type="text" class="form-control form-control-sm" name="numero" 
                       value="<?php echo htmlspecialchars($filtro_numero); ?>" placeholder="Buscar número...">
            </div>
            <div class="col-md-3">
                <label class="form-label small">Proveedor</label>
                <input type="text" class="form-control form-control-sm" name="proveedor" 
                       value="<?php echo htmlspecialchars($filtro_proveedor); ?>" placeholder="Buscar proveedor...">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Desde</label>
                <input type="date" class="form-control form-control-sm" name="fecha_desde" 
                       value="<?php echo htmlspecialchars($filtro_fecha_desde); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Hasta</label>
                <input type="date" class="form-control form-control-sm" name="fecha_hasta" 
                       value="<?php echo htmlspecialchars($filtro_fecha_hasta); ?>">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary btn-sm me-2">
                    <i class="fas fa-search"></i> Buscar
                </button>
                <a href="boletas.php" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-times"></i> Limpiar
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Lista de boletas -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-list"></i> Boletas Registradas 
            <span class="badge bg-primary"><?php echo count($boletas); ?></span>
        </h5>
    </div>
    <div class="card-body">
        <?php if (!empty($boletas)): ?>
            <div class="table-responsive-md">
                <table class="table table-hover accessibility-fix">
                    <thead>
                        <tr>
                            <th>Número</th>
                            <th>Fecha</th>
                            <th>Proveedor</th>
                            <th>Descripción</th>
                            <th>Imagen</th>
                            <th>Subido por</th>
                            <th>Fecha Subida</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($boletas as $boleta): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($boleta['numero_boleta']); ?></strong>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($boleta['fecha_boleta'])); ?></td>
                                <td><?php echo htmlspecialchars($boleta['proveedor']); ?></td>
                                <td>
                                    <span title="<?php echo htmlspecialchars($boleta['descripcion']); ?>">
                                        <?php echo htmlspecialchars(substr($boleta['descripcion'], 0, 50)) . (strlen($boleta['descripcion']) > 50 ? '...' : ''); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-info" 
                                            onclick="verImagen('<?php echo htmlspecialchars($boleta['imagen_path']); ?>', '<?php echo htmlspecialchars($boleta['numero_boleta']); ?>')">
                                        <i class="fas fa-image"></i> Ver
                                    </button>
                                </td>
                                <td><?php echo htmlspecialchars($boleta['usuario_nombre']); ?></td>
                                <td>
                                    <small class="text-muted">
                                        <?php echo date('d/m/Y H:i', strtotime($boleta['fecha_creacion'])); ?>
                                    </small>
                                </td>
                                <td>
                                    <?php if (tienePermiso('boletas_eliminar')): ?>
                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                onclick="confirmarEliminar(<?php echo $boleta['id']; ?>, '<?php echo addslashes($boleta['numero_boleta']); ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-4">
                <i class="fas fa-receipt fa-3x text-muted mb-3"></i>
                <h5>No hay boletas registradas</h5>
                <p class="text-muted">Haz clic en "Subir Boleta" para agregar la primera boleta</p>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalSubirBoleta">
                    <i class="fas fa-plus"></i> Subir Primera Boleta
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal para subir boleta -->
<div class="modal fade" id="modalSubirBoleta" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-upload"></i> Subir Nueva Boleta
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="subir_boleta">
                    <?php echo campoCSRF(); ?>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="numero_boleta" class="form-label">
                                    <i class="fas fa-hashtag"></i> Número de Boleta <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" name="numero_boleta" id="numero_boleta" 
                                       placeholder="Ej: BOL-2024-001" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="fecha" class="form-label">
                                    <i class="fas fa-calendar"></i> Fecha <span class="text-danger">*</span>
                                </label>
                                <input type="date" class="form-control" name="fecha" id="fecha" 
                                       value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="proveedor" class="form-label">
                            <i class="fas fa-building"></i> Proveedor <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" name="proveedor" id="proveedor" 
                               placeholder="Nombre del proveedor o empresa" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="descripcion" class="form-label">
                            <i class="fas fa-align-left"></i> Descripción <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control" name="descripcion" id="descripcion" rows="3" 
                                  placeholder="Descripción de lo que se compró o el motivo de la boleta" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="imagen" class="form-label">
                            <i class="fas fa-image"></i> Imagen de la Boleta <span class="text-danger">*</span>
                        </label>
                        <input type="file" class="form-control" name="imagen" id="imagen" 
                               accept="image/*" required>
                        <div class="form-text">
                            Formatos permitidos: JPG, PNG, GIF. Tamaño máximo: 5MB
                        </div>
                    </div>
                    
                    <div id="preview-container" class="d-none">
                        <label class="form-label">Vista Previa:</label>
                        <div class="text-center">
                            <img id="preview-image" src="" alt="Vista previa" class="img-thumbnail" style="max-height: 200px;">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload"></i> Subir Boleta
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para ver imagen -->
<div class="modal fade" id="modalVerImagen" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-imagen-titulo">Ver Imagen de Boleta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img id="modal-imagen" src="" alt="Imagen de boleta" class="img-fluid">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <a id="modal-descargar" href="" download class="btn btn-primary">
                    <i class="fas fa-download"></i> Descargar
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Modal de estadísticas -->
<div class="modal fade" id="modalEstadisticas" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-chart-bar"></i> Estadísticas de Boletas
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row text-center">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="text-primary"><?php echo $stats['total_boletas']; ?></h4>
                                <small>Total Boletas</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="text-success"><?php echo $stats['total_proveedores']; ?></h4>
                                <small>Proveedores</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="text-info"><?php echo round(($stats['total_size'] ?? 0) / 1024 / 1024, 2); ?> MB</h4>
                                <small>Espacio Usado</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Formulario oculto para eliminar -->
<form id="form-eliminar" method="POST" style="display: none;">
    <?php echo campoCSRF(); ?>
    <input type="hidden" name="action" value="eliminar_boleta">
    <input type="hidden" name="boleta_id" id="boleta-id-eliminar">
</form>

<script>
// Vista previa de imagen
document.getElementById('imagen').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const previewContainer = document.getElementById('preview-container');
    const previewImage = document.getElementById('preview-image');
    
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImage.src = e.target.result;
            previewContainer.classList.remove('d-none');
        };
        reader.readAsDataURL(file);
    } else {
        previewContainer.classList.add('d-none');
    }
});

// Ver imagen en modal
function verImagen(ruta, numero) {
    document.getElementById('modal-imagen').src = ruta;
    document.getElementById('modal-imagen-titulo').textContent = 'Boleta: ' + numero;
    document.getElementById('modal-descargar').href = ruta;
    new bootstrap.Modal(document.getElementById('modalVerImagen')).show();
}

// Mostrar estadísticas
function mostrarEstadisticas() {
    new bootstrap.Modal(document.getElementById('modalEstadisticas')).show();
}

// Confirmar eliminación
function confirmarEliminar(boletaId, numeroBoleta) {
    if (confirm(`¿Está seguro de eliminar la boleta "${numeroBoleta}"?\n\nEsta acción NO se puede deshacer y eliminará tanto el registro como la imagen.`)) {
        document.getElementById('boleta-id-eliminar').value = boletaId;
        document.getElementById('form-eliminar').submit();
    }
}

// Limpiar formulario al cerrar modal
document.getElementById('modalSubirBoleta').addEventListener('hidden.bs.modal', function() {
    document.querySelector('#modalSubirBoleta form').reset();
    document.getElementById('preview-container').classList.add('d-none');
});
</script>

<?php include 'includes/layout_footer.php'; ?>
