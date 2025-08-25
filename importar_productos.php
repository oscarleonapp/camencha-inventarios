<?php
$titulo = "Importar Productos - Sistema de Inventarios";
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'includes/config_functions.php';
require_once 'includes/csrf_protection.php';
require_once 'includes/excel_importer.php';
require_once 'includes/codigo_generator.php';

verificarLogin();
verificarPermiso('productos_crear');

$database = new Database();
$db = $database->getConnection();
$codigoGenerator = new CodigoGenerator($db);
$excelImporter = new ExcelImporter($db, $codigoGenerator);

$resultado_importacion = null;

// Procesar subida de archivo
if ($_POST && isset($_FILES['archivo_excel'])) {
    validarCSRF();
    
    try {
        $archivo = $_FILES['archivo_excel'];
        
        // Validar archivo subido
        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Error en la subida del archivo: ' . $archivo['error']);
        }
        
        // Validar tamaño (máximo 10MB)
        if ($archivo['size'] > 10 * 1024 * 1024) {
            throw new Exception('El archivo es demasiado grande. Máximo 10MB permitido.');
        }
        
        // Validar extensión
        $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, ['csv', 'xlsx', 'xls'])) {
            throw new Exception('Formato de archivo no válido. Solo se permiten archivos CSV, Excel (.xlsx) o Excel (.xls)');
        }
        
        // Mover archivo a directorio temporal
        $upload_dir = 'uploads/temp/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $nombre_archivo = 'import_' . date('Y-m-d_H-i-s') . '_' . uniqid() . '.' . $extension;
        $ruta_archivo = $upload_dir . $nombre_archivo;
        
        if (!move_uploaded_file($archivo['tmp_name'], $ruta_archivo)) {
            throw new Exception('Error moviendo el archivo subido');
        }
        
        // Procesar archivo
        $opciones = [
            'sobrescribir_duplicados' => isset($_POST['sobrescribir_duplicados']),
            'crear_proveedores' => isset($_POST['crear_proveedores']),
            'validar_componentes' => isset($_POST['validar_componentes'])
        ];
        
        $resultado_importacion = $excelImporter->procesarArchivo($ruta_archivo, $opciones);
        
        // Limpiar archivo temporal
        unlink($ruta_archivo);
        
        // Log de la importación
        require_once 'includes/logger.php';
        getLogger()->info('import_productos', 'productos', 
            "Importación masiva completada", [
                'archivo' => $archivo['name'],
                'procesados' => $resultado_importacion['procesados'],
                'saltados' => $resultado_importacion['saltados'],
                'errores' => count($resultado_importacion['errores'])
            ]
        );
        
    } catch (Exception $e) {
        $error = $e->getMessage();
        
        // Limpiar archivo temporal si existe
        if (isset($ruta_archivo) && file_exists($ruta_archivo)) {
            unlink($ruta_archivo);
        }
    }
}

// Generar plantilla CSV si se solicita
if (isset($_GET['descargar_plantilla'])) {
    $plantilla = $excelImporter->generarPlantilla();
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=plantilla_productos_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    
    // BOM para UTF-8 (ayuda con acentos en Excel)
    fputs($output, "\xEF\xBB\xBF");
    
    foreach ($plantilla as $fila) {
        fputcsv($output, $fila);
    }
    
    fclose($output);
    exit;
}

include 'includes/layout_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 rs-wrap-sm">
    <h2><i class="fas fa-upload"></i> <span class="editable" data-label="importar_titulo">Importar Productos Masivamente</span></h2>
    <div class="btn-group rs-wrap-sm">
        <a href="productos.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Volver a Productos
        </a>
        <a href="?descargar_plantilla=1" class="btn btn-success">
            <i class="fas fa-download"></i> Descargar Plantilla
        </a>
    </div>
</div>

<!-- Mensajes de resultado -->
<?php if (isset($error)): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-triangle"></i>
        <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<!-- Resultados de importación -->
<?php if ($resultado_importacion): ?>
    <div class="card mb-4">
        <div class="card-header bg-<?php echo $resultado_importacion['exito'] ? 'success' : 'danger'; ?> text-white">
            <h5 class="mb-0">
                <i class="fas fa-<?php echo $resultado_importacion['exito'] ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                Resultado de la Importación
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="text-center">
                        <h3 class="text-success"><?php echo $resultado_importacion['procesados']; ?></h3>
                        <p class="text-muted">Productos Creados</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center">
                        <h3 class="text-warning"><?php echo $resultado_importacion['saltados']; ?></h3>
                        <p class="text-muted">Filas Saltadas</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center">
                        <h3 class="text-danger"><?php echo count($resultado_importacion['errores']); ?></h3>
                        <p class="text-muted">Errores</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center">
                        <h3 class="text-info"><?php echo count($resultado_importacion['advertencias']); ?></h3>
                        <p class="text-muted">Advertencias</p>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($resultado_importacion['errores'])): ?>
                <div class="mt-4">
                    <h6 class="text-danger">Errores Encontrados:</h6>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($resultado_importacion['errores'] as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($resultado_importacion['advertencias'])): ?>
                <div class="mt-4">
                    <h6 class="text-warning">Advertencias:</h6>
                    <div class="alert alert-warning">
                        <ul class="mb-0">
                            <?php foreach ($resultado_importacion['advertencias'] as $advertencia): ?>
                                <li><?php echo htmlspecialchars($advertencia); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Detalles de productos creados -->
            <?php if (!empty($resultado_importacion['detalles'])): ?>
                <div class="mt-4">
                    <h6>Detalles de Procesamiento:</h6>
                    <div class="table-responsive-md">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Fila</th>
                                    <th>Estado</th>
                                    <th>Nombre</th>
                                    <th>Código Generado</th>
                                    <th>Tipo</th>
                                    <th>Componentes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($resultado_importacion['detalles'] as $detalle): ?>
                                    <tr class="<?php echo $detalle['procesado'] ? 'table-success' : 'table-danger'; ?>">
                                        <td><?php echo $detalle['fila']; ?></td>
                                        <td>
                                            <i class="fas fa-<?php echo $detalle['procesado'] ? 'check text-success' : 'times text-danger'; ?>"></i>
                                            <?php echo $detalle['procesado'] ? 'Procesado' : 'Error'; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($detalle['nombre'] ?? 'N/A'); ?></td>
                                        <td>
                                            <?php if (isset($detalle['codigo_generado'])): ?>
                                                <code><?php echo $detalle['codigo_generado']; ?></code>
                                            <?php else: ?>
                                                —
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (isset($detalle['tipo'])): ?>
                                                <span class="badge bg-<?php echo $detalle['tipo'] === 'elemento' ? 'primary' : 'success'; ?>">
                                                    <?php echo ucfirst($detalle['tipo']); ?>
                                                </span>
                                            <?php else: ?>
                                                —
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (isset($detalle['componentes_agregados']) && $detalle['componentes_agregados'] > 0): ?>
                                                <span class="badge bg-info"><?php echo $detalle['componentes_agregados']; ?> componentes</span>
                                            <?php else: ?>
                                                —
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Información sobre el formato -->
<div class="row">
    <div class="col-md-8">
        <!-- Formulario de subida -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-file-upload"></i> Subir Archivo de Productos</h5>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data" id="formImportar">
                    <?php echo campoCSRF(); ?>
                    
                    <div class="mb-4">
                        <label class="form-label">
                            <i class="fas fa-file-excel text-success me-1"></i>
                            Archivo Excel o CSV
                        </label>
                        <input type="file" class="form-control" name="archivo_excel" required
                               accept=".csv,.xlsx,.xls">
                        <div class="form-text">
                            <strong>Formatos soportados:</strong> CSV (.csv), Excel (.xlsx, .xls)<br>
                            <strong>Tamaño máximo:</strong> 10MB
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h6>Opciones de Importación:</h6>
                        
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="sobrescribir_duplicados" id="sobrescribir">
                            <label class="form-check-label" for="sobrescribir">
                                Sobrescribir productos duplicados (por nombre)
                            </label>
                        </div>
                        
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="crear_proveedores" id="crearProveedores">
                            <label class="form-check-label" for="crearProveedores">
                                Crear proveedores automáticamente si no existen
                            </label>
                        </div>
                        
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="validar_componentes" id="validarComponentes" checked>
                            <label class="form-check-label" for="validarComponentes">
                                Validar que componentes existan antes de crear conjuntos
                            </label>
                        </div>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-upload"></i> Importar Productos
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <!-- Guía de formato -->
        <div class="card">
            <div class="card-header bg-info text-white">
                <h6 class="mb-0"><i class="fas fa-info-circle"></i> Formato del Archivo</h6>
            </div>
            <div class="card-body">
                <h6>Columnas Requeridas:</h6>
                <ul class="list-unstyled">
                    <li><code>nombre</code> - Nombre del producto</li>
                    <li><code>tipo</code> - "elemento" o "conjunto"</li>
                    <li><code>precio_venta</code> - Precio de venta</li>
                    <li><code>precio_compra</code> - Precio de compra</li>
                </ul>
                
                <h6>Columnas Opcionales:</h6>
                <ul class="list-unstyled">
                    <li><code>descripcion</code> - Descripción</li>
                    <li><code>proveedor</code> - Nombre del proveedor</li>
                    <li><code>componente_1</code> - Código/nombre del componente 1</li>
                    <li><code>cantidad_1</code> - Cantidad del componente 1</li>
                    <li><code>componente_2</code> - Código/nombre del componente 2</li>
                    <li><code>cantidad_2</code> - Cantidad del componente 2</li>
                    <li>... (hasta componente_10)</li>
                </ul>
                
                <div class="alert alert-warning">
                    <i class="fas fa-lightbulb"></i>
                    <strong>Tip:</strong> Descarga la plantilla para ver el formato exacto con ejemplos.
                </div>
            </div>
        </div>
        
        <!-- Instrucciones para Excel -->
        <div class="card mt-3">
            <div class="card-header bg-warning text-dark">
                <h6 class="mb-0"><i class="fas fa-file-excel"></i> Archivos Excel</h6>
            </div>
            <div class="card-body">
                <p class="small">Para mejores resultados con archivos Excel:</p>
                <ol class="small">
                    <li>Abrir archivo en Excel</li>
                    <li>Guardar como → CSV (delimitado por comas)</li>
                    <li>Subir el archivo CSV</li>
                </ol>
                <p class="small text-muted">Esto garantiza compatibilidad y evita problemas de encoding.</p>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('formImportar').addEventListener('submit', function(e) {
    const submitBtn = this.querySelector('button[type="submit"]');
    const archivo = this.querySelector('input[name="archivo_excel"]').files[0];
    
    if (!archivo) {
        e.preventDefault();
        alert('Por favor selecciona un archivo');
        return;
    }
    
    // Verificar tamaño
    if (archivo.size > 10 * 1024 * 1024) {
        e.preventDefault();
        alert('El archivo es demasiado grande. Máximo 10MB permitido.');
        return;
    }
    
    // Deshabilitar botón y mostrar progreso
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
    
    // Mostrar mensaje de espera
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-info mt-3';
    alertDiv.innerHTML = '<i class="fas fa-clock"></i> <strong>Procesando archivo...</strong> Esto puede tomar unos minutos dependiendo del tamaño del archivo.';
    this.appendChild(alertDiv);
});

// Drag and drop para el archivo
const fileInput = document.querySelector('input[name="archivo_excel"]');
const dropZone = fileInput.parentElement;

['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
    dropZone.addEventListener(eventName, preventDefaults, false);
});

function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
}

['dragenter', 'dragover'].forEach(eventName => {
    dropZone.addEventListener(eventName, highlight, false);
});

['dragleave', 'drop'].forEach(eventName => {
    dropZone.addEventListener(eventName, unhighlight, false);
});

function highlight(e) {
    dropZone.classList.add('border-primary', 'bg-light');
}

function unhighlight(e) {
    dropZone.classList.remove('border-primary', 'bg-light');
}

dropZone.addEventListener('drop', handleDrop, false);

function handleDrop(e) {
    const dt = e.dataTransfer;
    const files = dt.files;
    
    if (files.length > 0) {
        fileInput.files = files;
        
        // Mostrar nombre del archivo
        const fileName = files[0].name;
        const fileInfo = document.createElement('div');
        fileInfo.className = 'mt-2 text-success';
        fileInfo.innerHTML = `<i class="fas fa-file"></i> ${fileName}`;
        
        // Remover info anterior si existe
        const existingInfo = dropZone.querySelector('.text-success');
        if (existingInfo) {
            existingInfo.remove();
        }
        
        dropZone.appendChild(fileInfo);
    }
}
</script>

<style>
.card {
    border: none;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.form-control {
    border-radius: 0.5rem;
}

.btn {
    border-radius: 0.5rem;
}

.table-responsive {
    max-height: 400px;
    overflow-y: auto;
}

code {
    background-color: #f8f9fa;
    padding: 0.2rem 0.4rem;
    border-radius: 0.25rem;
    font-size: 0.875em;
}
</style>

<?php include 'includes/layout_footer.php'; ?>
