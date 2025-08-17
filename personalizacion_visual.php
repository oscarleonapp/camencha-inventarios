<?php
$titulo = "Personalización Visual - Sistema de Inventarios";
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'includes/config_functions.php';
require_once 'includes/csrf_protection.php';

verificarLogin();
verificarPermiso('config_sistema');

$database = new Database();
$db = $database->getConnection();

// Cargar configuración actual
$config_visual = cargarConfiguracionVisual();

// Procesar formulario
if ($_POST && isset($_POST['action'])) {
    validarCSRF();
    
    switch ($_POST['action']) {
        case 'guardar_colores':
            guardarColores($_POST);
            break;
        case 'guardar_branding':
            guardarBranding($_POST, $_FILES);
            break;
        case 'guardar_tipografia':
            guardarTipografia($_POST);
            break;
        case 'guardar_layout':
            guardarLayout($_POST);
            break;
        case 'exportar_tema':
            exportarTema();
            break;
        case 'importar_tema':
            importarTema($_FILES);
            break;
        case 'resetear_tema':
            resetearTema();
            break;
    }
}

function clavesVisuales() {
    return [
        'color_primario', 'color_secundario', 'color_exito', 'color_peligro',
        'color_advertencia', 'color_info', 'color_sidebar', 'color_navbar',
        'color_texto', 'color_fondo',
        'logo_principal', 'logo_pequeno', 'favicon',
        'nombre_empresa', 'eslogan_empresa',
        'fuente_principal', 'fuente_secundaria',
        'sidebar_estilo', 'navbar_estilo', 'bordes_redondeados',
        'sombras_activas', 'animaciones_activas', 'modo_compacto'
    ];
}

function cargarConfiguracionVisual() {
    global $db;
    
    $config = [];
    $claves = clavesVisuales();
    $placeholders = implode(',', array_fill(0, count($claves), '?'));
    $stmt = $db->prepare("SELECT clave, valor FROM configuraciones WHERE clave IN ($placeholders)");
    $stmt->execute($claves);
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $config[$row['clave']] = $row['valor'];
    }
    
    // Valores por defecto
    $defaults = [
        'color_primario' => '#007bff',
        'color_secundario' => '#6c757d',
        'color_exito' => '#28a745',
        'color_peligro' => '#dc3545',
        'color_advertencia' => '#ffc107',
        'color_info' => '#17a2b8',
        'color_sidebar' => '#343a40',
        'color_navbar' => '#ffffff',
        'color_texto' => '#212529',
        'color_fondo' => '#f8f9fa',
        'logo_principal' => '',
        'logo_pequeno' => '',
        'favicon' => '',
        'nombre_empresa' => 'Sistema de Inventarios',
        'eslogan_empresa' => 'Gestión inteligente de inventarios',
        'fuente_principal' => 'Inter',
        'fuente_secundaria' => 'system-ui',
        'sidebar_estilo' => 'oscuro',
        'navbar_estilo' => 'claro',
        'bordes_redondeados' => '0.375rem',
        'sombras_activas' => '1',
        'animaciones_activas' => '1',
        'modo_compacto' => '0'
    ];
    
    return array_merge($defaults, $config);
}

function guardarColores($datos) {
    global $db;
    
    $colores = [
        'color_primario', 'color_secundario', 'color_exito', 'color_peligro',
        'color_advertencia', 'color_info', 'color_sidebar', 'color_navbar',
        'color_texto', 'color_fondo'
    ];
    
    try {
        $db->beginTransaction();
        
        foreach ($colores as $color) {
            if (isset($datos[$color])) {
                $stmt = $db->prepare("
                    INSERT INTO configuraciones (categoria, clave, valor) 
                    VALUES ('visual', ?, ?) 
                    ON DUPLICATE KEY UPDATE valor = VALUES(valor)
                ");
                $stmt->execute([$color, $datos[$color]]);
            }
        }
        
        $db->commit();
        $_SESSION['mensaje_exito'] = 'Colores guardados exitosamente';
        
        // Log del cambio
        require_once 'includes/logger.php';
        getLogger()->config('colores_sistema', 'anterior', json_encode($datos));
        
    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['mensaje_error'] = 'Error al guardar colores: ' . $e->getMessage();
    }
}

function guardarBranding($datos, $archivos) {
    global $db;
    
    try {
        $db->beginTransaction();
        
        // Guardar textos
        $textos = ['nombre_empresa', 'eslogan_empresa'];
        foreach ($textos as $campo) {
            if (isset($datos[$campo])) {
                $stmt = $db->prepare("
                    INSERT INTO configuraciones (categoria, clave, valor) 
                    VALUES ('visual', ?, ?) 
                    ON DUPLICATE KEY UPDATE valor = VALUES(valor)
                ");
                $stmt->execute([$campo, $datos[$campo]]);
            }
        }
        
        // Procesar archivos de imagen
        $campos_imagen = ['logo_principal', 'logo_pequeno', 'favicon'];
        
        foreach ($campos_imagen as $campo) {
            if (isset($archivos[$campo]) && $archivos[$campo]['error'] === UPLOAD_ERR_OK) {
                $resultado = procesarImagenBranding($archivos[$campo], $campo);
                if ($resultado['success']) {
                    $stmt = $db->prepare("
                        INSERT INTO configuraciones (categoria, clave, valor) 
                        VALUES ('visual', ?, ?) 
                        ON DUPLICATE KEY UPDATE valor = VALUES(valor)
                    ");
                    $stmt->execute([$campo, $resultado['ruta']]);
                }
            }
        }
        
        $db->commit();
        $_SESSION['mensaje_exito'] = 'Branding guardado exitosamente';
        
    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['mensaje_error'] = 'Error al guardar branding: ' . $e->getMessage();
    }
}

function procesarImagenBranding($archivo, $tipo) {
    $upload_dir = 'uploads/branding/';
    
    // Crear directorio si no existe
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Validar tipo de archivo
    $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'ico'];
    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    
    if (!in_array($extension, $extensiones_permitidas)) {
        return ['success' => false, 'error' => 'Formato de archivo no permitido'];
    }
    
    // Validar tamaño según tipo
    $max_sizes = [
        'logo_principal' => 2 * 1024 * 1024, // 2MB
        'logo_pequeno' => 1 * 1024 * 1024,   // 1MB
        'favicon' => 512 * 1024               // 512KB
    ];
    
    if ($archivo['size'] > $max_sizes[$tipo]) {
        return ['success' => false, 'error' => 'Archivo demasiado grande'];
    }
    
    // Generar nombre único
    $nombre_archivo = $tipo . '_' . time() . '.' . $extension;
    $ruta_completa = $upload_dir . $nombre_archivo;
    
    // Mover archivo
    if (move_uploaded_file($archivo['tmp_name'], $ruta_completa)) {
        return ['success' => true, 'ruta' => $nombre_archivo];
    } else {
        return ['success' => false, 'error' => 'Error al subir archivo'];
    }
}

function guardarTipografia($datos) {
    global $db;
    
    $campos = ['fuente_principal', 'fuente_secundaria'];
    
    try {
        $db->beginTransaction();
        
        foreach ($campos as $campo) {
            if (isset($datos[$campo])) {
                $stmt = $db->prepare("
                    INSERT INTO configuraciones (categoria, clave, valor) 
                    VALUES ('visual', ?, ?) 
                    ON DUPLICATE KEY UPDATE valor = VALUES(valor)
                ");
                $stmt->execute([$campo, $datos[$campo]]);
            }
        }
        
        $db->commit();
        $_SESSION['mensaje_exito'] = 'Tipografía guardada exitosamente';
        
    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['mensaje_error'] = 'Error al guardar tipografía: ' . $e->getMessage();
    }
}

function guardarLayout($datos) {
    global $db;
    
    $campos = [
        'sidebar_estilo', 'navbar_estilo', 'bordes_redondeados',
        'sombras_activas', 'animaciones_activas', 'modo_compacto'
    ];
    
    try {
        $db->beginTransaction();
        
        foreach ($campos as $campo) {
            if (isset($datos[$campo])) {
                $stmt = $db->prepare("
                    INSERT INTO configuraciones (categoria, clave, valor) 
                    VALUES ('visual', ?, ?) 
                    ON DUPLICATE KEY UPDATE valor = VALUES(valor)
                ");
                $stmt->execute([$campo, $datos[$campo]]);
            }
        }
        
        $db->commit();
        $_SESSION['mensaje_exito'] = 'Layout guardado exitosamente';
        
    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['mensaje_error'] = 'Error al guardar layout: ' . $e->getMessage();
    }
}

function exportarTema() {
    global $config_visual;
    
    $tema = [
        'nombre' => 'Tema Personalizado',
        'version' => '1.0',
        'fecha_exportacion' => date('Y-m-d H:i:s'),
        'configuracion' => $config_visual
    ];
    
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="tema_personalizado_' . date('Y-m-d') . '.json"');
    
    echo json_encode($tema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

function importarTema($archivos) {
    global $db;
    
    if (!isset($archivos['archivo_tema']) || $archivos['archivo_tema']['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['mensaje_error'] = 'Error al subir archivo de tema';
        return;
    }
    
    $contenido = file_get_contents($archivos['archivo_tema']['tmp_name']);
    $tema = json_decode($contenido, true);
    
    if (!$tema || !isset($tema['configuracion'])) {
        $_SESSION['mensaje_error'] = 'Archivo de tema inválido';
        return;
    }
    
    try {
        $db->beginTransaction();
        
        foreach ($tema['configuracion'] as $clave => $valor) {
            $stmt = $db->prepare("
                INSERT INTO configuraciones (categoria, clave, valor) 
                VALUES ('visual', ?, ?) 
                ON DUPLICATE KEY UPDATE valor = VALUES(valor)
            ");
            $stmt->execute([$clave, $valor]);
        }
        
        $db->commit();
        $_SESSION['mensaje_exito'] = 'Tema importado exitosamente';
        
    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['mensaje_error'] = 'Error al importar tema: ' . $e->getMessage();
    }
}

function resetearTema() {
    global $db;
    
    try {
        $stmt = $db->prepare("DELETE FROM configuraciones WHERE categoria = 'visual'");
        $stmt->execute();
        
        $_SESSION['mensaje_exito'] = 'Tema reseteado a valores por defecto';
        
    } catch (Exception $e) {
        $_SESSION['mensaje_error'] = 'Error al resetear tema: ' . $e->getMessage();
    }
}

include 'includes/layout_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-palette"></i> Personalización Visual</h2>
    <div class="btn-group">
        <a href="configuracion.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Volver a Configuración
        </a>
        <button class="btn btn-success" onclick="previsualizarCambios()">
            <i class="fas fa-eye"></i> Vista Previa
        </button>
    </div>
</div>

<!-- Mensajes -->
<?php if (isset($_SESSION['mensaje_exito'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle"></i> <?php echo $_SESSION['mensaje_exito']; unset($_SESSION['mensaje_exito']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['mensaje_error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-triangle"></i> <?php echo $_SESSION['mensaje_error']; unset($_SESSION['mensaje_error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Navegación por pestañas -->
<div class="card">
    <div class="card-header">
        <ul class="nav nav-tabs card-header-tabs" id="personalizacionTabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" id="colores-tab" data-bs-toggle="tab" data-bs-target="#colores" type="button">
                    <i class="fas fa-palette"></i> Colores
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="branding-tab" data-bs-toggle="tab" data-bs-target="#branding" type="button">
                    <i class="fas fa-building"></i> Branding
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="tipografia-tab" data-bs-toggle="tab" data-bs-target="#tipografia" type="button">
                    <i class="fas fa-font"></i> Tipografía
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="layout-tab" data-bs-toggle="tab" data-bs-target="#layout" type="button">
                    <i class="fas fa-layout"></i> Layout
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="temas-tab" data-bs-toggle="tab" data-bs-target="#temas" type="button">
                    <i class="fas fa-download"></i> Temas
                </button>
            </li>
        </ul>
    </div>

    <div class="card-body">
        <div class="tab-content" id="personalizacionTabContent">
            <!-- Pestaña Colores -->
            <div class="tab-pane fade show active" id="colores" role="tabpanel">
                <form method="POST" id="formColores">
                    <input type="hidden" name="action" value="guardar_colores">
                    <?php echo campoCSRF(); ?>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h5><i class="fas fa-paint-brush"></i> Colores Principales</h5>
                            
                            <div class="mb-3">
                                <label class="form-label">Color Primario</label>
                                <div class="input-group">
                                    <input type="color" class="form-control form-control-color" name="color_primario" value="<?php echo $config_visual['color_primario']; ?>" onchange="actualizarVistaPrevia()">
                                    <input type="text" class="form-control" value="<?php echo $config_visual['color_primario']; ?>" readonly>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Color Secundario</label>
                                <div class="input-group">
                                    <input type="color" class="form-control form-control-color" name="color_secundario" value="<?php echo $config_visual['color_secundario']; ?>" onchange="actualizarVistaPrevia()">
                                    <input type="text" class="form-control" value="<?php echo $config_visual['color_secundario']; ?>" readonly>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Color de Éxito</label>
                                <div class="input-group">
                                    <input type="color" class="form-control form-control-color" name="color_exito" value="<?php echo $config_visual['color_exito']; ?>" onchange="actualizarVistaPrevia()">
                                    <input type="text" class="form-control" value="<?php echo $config_visual['color_exito']; ?>" readonly>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Color de Peligro</label>
                                <div class="input-group">
                                    <input type="color" class="form-control form-control-color" name="color_peligro" value="<?php echo $config_visual['color_peligro']; ?>" onchange="actualizarVistaPrevia()">
                                    <input type="text" class="form-control" value="<?php echo $config_visual['color_peligro']; ?>" readonly>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Color de Advertencia</label>
                                <div class="input-group">
                                    <input type="color" class="form-control form-control-color" name="color_advertencia" value="<?php echo $config_visual['color_advertencia']; ?>" onchange="actualizarVistaPrevia()">
                                    <input type="text" class="form-control" value="<?php echo $config_visual['color_advertencia']; ?>" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <h5><i class="fas fa-fill-drip"></i> Colores de Interface</h5>
                            
                            <div class="mb-3">
                                <label class="form-label">Color de Información</label>
                                <div class="input-group">
                                    <input type="color" class="form-control form-control-color" name="color_info" value="<?php echo $config_visual['color_info']; ?>" onchange="actualizarVistaPrevia()">
                                    <input type="text" class="form-control" value="<?php echo $config_visual['color_info']; ?>" readonly>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Color Sidebar</label>
                                <div class="input-group">
                                    <input type="color" class="form-control form-control-color" name="color_sidebar" value="<?php echo $config_visual['color_sidebar']; ?>" onchange="actualizarVistaPrevia()">
                                    <input type="text" class="form-control" value="<?php echo $config_visual['color_sidebar']; ?>" readonly>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Color Navbar</label>
                                <div class="input-group">
                                    <input type="color" class="form-control form-control-color" name="color_navbar" value="<?php echo $config_visual['color_navbar']; ?>" onchange="actualizarVistaPrevia()">
                                    <input type="text" class="form-control" value="<?php echo $config_visual['color_navbar']; ?>" readonly>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Color de Texto</label>
                                <div class="input-group">
                                    <input type="color" class="form-control form-control-color" name="color_texto" value="<?php echo $config_visual['color_texto']; ?>" onchange="actualizarVistaPrevia()">
                                    <input type="text" class="form-control" value="<?php echo $config_visual['color_texto']; ?>" readonly>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Color de Fondo</label>
                                <div class="input-group">
                                    <input type="color" class="form-control form-control-color" name="color_fondo" value="<?php echo $config_visual['color_fondo']; ?>" onchange="actualizarVistaPrevia()">
                                    <input type="text" class="form-control" value="<?php echo $config_visual['color_fondo']; ?>" readonly>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-outline-secondary" onclick="restaurarColoresDefecto()">
                            <i class="fas fa-undo"></i> Restaurar Defecto
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Guardar Colores
                        </button>
                    </div>
                </form>
            </div>

            <!-- Pestaña Branding -->
            <div class="tab-pane fade" id="branding" role="tabpanel">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="guardar_branding">
                    <?php echo campoCSRF(); ?>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h5><i class="fas fa-building"></i> Información de la Empresa</h5>
                            
                            <div class="mb-3">
                                <label class="form-label">Nombre de la Empresa</label>
                                <input type="text" class="form-control" name="nombre_empresa" value="<?php echo htmlspecialchars($config_visual['nombre_empresa']); ?>" placeholder="Sistema de Inventarios">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Eslogan/Descripción</label>
                                <input type="text" class="form-control" name="eslogan_empresa" value="<?php echo htmlspecialchars($config_visual['eslogan_empresa']); ?>" placeholder="Gestión inteligente de inventarios">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <h5><i class="fas fa-image"></i> Logos e Imágenes</h5>
                            
                            <div class="mb-3">
                                <label class="form-label">Logo Principal</label>
                                <input type="file" class="form-control" name="logo_principal" accept="image/*">
                                <div class="form-text">Recomendado: 200x60px, máximo 2MB</div>
                                <?php if ($config_visual['logo_principal']): ?>
                                    <div class="mt-2">
                                        <img src="uploads/branding/<?php echo $config_visual['logo_principal']; ?>" alt="Logo Principal" style="max-height: 60px;">
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Logo Pequeño</label>
                                <input type="file" class="form-control" name="logo_pequeno" accept="image/*">
                                <div class="form-text">Recomendado: 40x40px, máximo 1MB</div>
                                <?php if ($config_visual['logo_pequeno']): ?>
                                    <div class="mt-2">
                                        <img src="uploads/branding/<?php echo $config_visual['logo_pequeno']; ?>" alt="Logo Pequeño" style="max-height: 40px;">
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Favicon</label>
                                <input type="file" class="form-control" name="favicon" accept=".ico,.png,.svg">
                                <div class="form-text">Recomendado: 32x32px, máximo 512KB</div>
                                <?php if ($config_visual['favicon']): ?>
                                    <div class="mt-2">
                                        <img src="uploads/branding/<?php echo $config_visual['favicon']; ?>" alt="Favicon" style="max-height: 32px;">
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Guardar Branding
                        </button>
                    </div>
                </form>
            </div>

            <!-- Pestaña Tipografía -->
            <div class="tab-pane fade" id="tipografia" role="tabpanel">
                <form method="POST">
                    <input type="hidden" name="action" value="guardar_tipografia">
                    <?php echo campoCSRF(); ?>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h5><i class="fas fa-font"></i> Fuentes del Sistema</h5>
                            
                            <div class="mb-3">
                                <label class="form-label">Fuente Principal</label>
                                <select class="form-select" name="fuente_principal">
                                    <option value="Inter" <?php echo $config_visual['fuente_principal'] === 'Inter' ? 'selected' : ''; ?>>Inter (Recomendado)</option>
                                    <option value="system-ui" <?php echo $config_visual['fuente_principal'] === 'system-ui' ? 'selected' : ''; ?>>System UI</option>
                                    <option value="Roboto" <?php echo $config_visual['fuente_principal'] === 'Roboto' ? 'selected' : ''; ?>>Roboto</option>
                                    <option value="Open Sans" <?php echo $config_visual['fuente_principal'] === 'Open Sans' ? 'selected' : ''; ?>>Open Sans</option>
                                    <option value="Lato" <?php echo $config_visual['fuente_principal'] === 'Lato' ? 'selected' : ''; ?>>Lato</option>
                                    <option value="Source Sans Pro" <?php echo $config_visual['fuente_principal'] === 'Source Sans Pro' ? 'selected' : ''; ?>>Source Sans Pro</option>
                                    <option value="Montserrat" <?php echo $config_visual['fuente_principal'] === 'Montserrat' ? 'selected' : ''; ?>>Montserrat</option>
                                    <option value="Nunito" <?php echo $config_visual['fuente_principal'] === 'Nunito' ? 'selected' : ''; ?>>Nunito</option>
                                    <option value="Poppins" <?php echo $config_visual['fuente_principal'] === 'Poppins' ? 'selected' : ''; ?>>Poppins</option>
                                    <option value="Arial" <?php echo $config_visual['fuente_principal'] === 'Arial' ? 'selected' : ''; ?>>Arial</option>
                                    <option value="Helvetica" <?php echo $config_visual['fuente_principal'] === 'Helvetica' ? 'selected' : ''; ?>>Helvetica</option>
                                </select>
                                <div class="form-text">Fuente principal para títulos y elementos importantes</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Fuente Secundaria</label>
                                <select class="form-select" name="fuente_secundaria">
                                    <option value="system-ui" <?php echo $config_visual['fuente_secundaria'] === 'system-ui' ? 'selected' : ''; ?>>System UI (Recomendado)</option>
                                    <option value="Inter" <?php echo $config_visual['fuente_secundaria'] === 'Inter' ? 'selected' : ''; ?>>Inter</option>
                                    <option value="Roboto" <?php echo $config_visual['fuente_secundaria'] === 'Roboto' ? 'selected' : ''; ?>>Roboto</option>
                                    <option value="Open Sans" <?php echo $config_visual['fuente_secundaria'] === 'Open Sans' ? 'selected' : ''; ?>>Open Sans</option>
                                    <option value="Lato" <?php echo $config_visual['fuente_secundaria'] === 'Lato' ? 'selected' : ''; ?>>Lato</option>
                                    <option value="Arial" <?php echo $config_visual['fuente_secundaria'] === 'Arial' ? 'selected' : ''; ?>>Arial</option>
                                    <option value="Helvetica" <?php echo $config_visual['fuente_secundaria'] === 'Helvetica' ? 'selected' : ''; ?>>Helvetica</option>
                                    <option value="Georgia" <?php echo $config_visual['fuente_secundaria'] === 'Georgia' ? 'selected' : ''; ?>>Georgia</option>
                                    <option value="Times New Roman" <?php echo $config_visual['fuente_secundaria'] === 'Times New Roman' ? 'selected' : ''; ?>>Times New Roman</option>
                                </select>
                                <div class="form-text">Fuente para texto general y contenido</div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <h5><i class="fas fa-text-height"></i> Vista Previa de Fuentes</h5>
                            
                            <div class="card">
                                <div class="card-body">
                                    <div id="previsualizacionFuente">
                                        <h4 style="font-family: var(--fuente-principal, Inter); margin-bottom: 10px;">
                                            Título Principal
                                        </h4>
                                        <h6 style="font-family: var(--fuente-principal, Inter); margin-bottom: 15px; color: #6c757d;">
                                            Subtítulo o encabezado secundario
                                        </h6>
                                        <p style="font-family: var(--fuente-secundaria, system-ui); margin-bottom: 10px;">
                                            Este es un párrafo de ejemplo que muestra cómo se ve el texto normal del sistema. 
                                            Incluye texto de diferentes longitudes para evaluar la legibilidad.
                                        </p>
                                        <div style="font-family: var(--fuente-secundaria, system-ui); font-size: 0.875rem; color: #6c757d;">
                                            <strong>Información adicional:</strong> Texto más pequeño para detalles y notas.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Guardar Tipografía
                        </button>
                    </div>
                </form>
            </div>

            <!-- Pestaña Layout -->
            <div class="tab-pane fade" id="layout" role="tabpanel">
                <form method="POST">
                    <input type="hidden" name="action" value="guardar_layout">
                    <?php echo campoCSRF(); ?>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h5><i class="fas fa-cogs"></i> Configuración de Layout</h5>
                            
                            <div class="mb-3">
                                <label class="form-label">Estilo de Sidebar</label>
                                <select class="form-select" name="sidebar_estilo">
                                    <option value="oscuro" <?php echo $config_visual['sidebar_estilo'] === 'oscuro' ? 'selected' : ''; ?>>Oscuro (Recomendado)</option>
                                    <option value="claro" <?php echo $config_visual['sidebar_estilo'] === 'claro' ? 'selected' : ''; ?>>Claro</option>
                                    <option value="colorido" <?php echo $config_visual['sidebar_estilo'] === 'colorido' ? 'selected' : ''; ?>>Colorido (Usa color primario)</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Estilo de Navbar</label>
                                <select class="form-select" name="navbar_estilo">
                                    <option value="claro" <?php echo $config_visual['navbar_estilo'] === 'claro' ? 'selected' : ''; ?>>Claro (Recomendado)</option>
                                    <option value="oscuro" <?php echo $config_visual['navbar_estilo'] === 'oscuro' ? 'selected' : ''; ?>>Oscuro</option>
                                    <option value="colorido" <?php echo $config_visual['navbar_estilo'] === 'colorido' ? 'selected' : ''; ?>>Colorido (Usa color primario)</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Bordes Redondeados</label>
                                <div class="input-group">
                                    <input type="range" class="form-range" name="bordes_redondeados" min="0" max="1" step="0.125" value="<?php echo str_replace('rem', '', $config_visual['bordes_redondeados']); ?>" oninput="actualizarValorBordes(this.value)">
                                    <span class="input-group-text" id="valorBordes"><?php echo $config_visual['bordes_redondeados']; ?></span>
                                </div>
                                <div class="form-text">Controla qué tan redondeados son los bordes de elementos</div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <h5><i class="fas fa-magic"></i> Efectos Visuales</h5>
                            
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="sombras_activas" value="1" <?php echo $config_visual['sombras_activas'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label">
                                        <strong>Sombras Activas</strong><br>
                                        <small class="text-muted">Agrega sombras a tarjetas y elementos flotantes</small>
                                    </label>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="animaciones_activas" value="1" <?php echo $config_visual['animaciones_activas'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label">
                                        <strong>Animaciones Activas</strong><br>
                                        <small class="text-muted">Habilita transiciones y animaciones suaves</small>
                                    </label>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="modo_compacto" value="1" <?php echo $config_visual['modo_compacto'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label">
                                        <strong>Modo Compacto</strong><br>
                                        <small class="text-muted">Reduce espacios y padding para pantallas pequeñas</small>
                                    </label>
                                </div>
                            </div>

                            <div class="card">
                                <div class="card-body">
                                    <h6>Vista Previa de Layout</h6>
                                    <div id="previsualizacionLayout" style="border: 1px solid #dee2e6; border-radius: var(--bordes-layout, 0.375rem); padding: 15px; background: #f8f9fa;">
                                        <div style="background: white; padding: 10px; border-radius: var(--bordes-layout, 0.375rem); box-shadow: var(--sombras-layout, 0 0.125rem 0.25rem rgba(0,0,0,0.075)); margin-bottom: 10px;">
                                            Tarjeta de ejemplo
                                        </div>
                                        <button class="btn btn-primary btn-sm" style="border-radius: var(--bordes-layout, 0.375rem);">
                                            Botón de prueba
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Guardar Layout
                        </button>
                    </div>
                </form>
            </div>

            <!-- Pestaña Temas -->
            <div class="tab-pane fade" id="temas" role="tabpanel">
                <div class="row">
                    <div class="col-md-6">
                        <h5><i class="fas fa-download"></i> Exportar/Importar Temas</h5>
                        
                        <div class="card mb-3">
                            <div class="card-body">
                                <h6><i class="fas fa-file-export"></i> Exportar Tema Actual</h6>
                                <p class="text-muted">Descarga la configuración visual actual como archivo JSON</p>
                                <form method="POST">
                                    <input type="hidden" name="action" value="exportar_tema">
                                    <?php echo campoCSRF(); ?>
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-download"></i> Descargar Tema
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div class="card mb-3">
                            <div class="card-body">
                                <h6><i class="fas fa-file-import"></i> Importar Tema</h6>
                                <p class="text-muted">Carga un tema desde un archivo JSON</p>
                                <form method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="action" value="importar_tema">
                                    <?php echo campoCSRF(); ?>
                                    <div class="mb-3">
                                        <input type="file" class="form-control" name="archivo_tema" accept=".json" required>
                                    </div>
                                    <button type="submit" class="btn btn-info">
                                        <i class="fas fa-upload"></i> Importar Tema
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <h5><i class="fas fa-palette"></i> Temas Predefinidos</h5>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="card tema-card" onclick="aplicarTema('azul_profesional')">
                                    <div class="card-body text-center p-3">
                                        <div class="tema-preview mb-2">
                                            <div style="background: linear-gradient(135deg, #007bff, #0056b3); height: 60px; border-radius: 8px; position: relative;">
                                                <div style="position: absolute; bottom: 5px; left: 5px; right: 5px; background: white; height: 20px; border-radius: 4px; opacity: 0.9;"></div>
                                            </div>
                                        </div>
                                        <h6 class="mb-1">Azul Profesional</h6>
                                        <small class="text-muted">Clásico y confiable</small>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="card tema-card" onclick="aplicarTema('verde_moderno')">
                                    <div class="card-body text-center p-3">
                                        <div class="tema-preview mb-2">
                                            <div style="background: linear-gradient(135deg, #28a745, #1e7e34); height: 60px; border-radius: 8px; position: relative;">
                                                <div style="position: absolute; bottom: 5px; left: 5px; right: 5px; background: white; height: 20px; border-radius: 4px; opacity: 0.9;"></div>
                                            </div>
                                        </div>
                                        <h6 class="mb-1">Verde Moderno</h6>
                                        <small class="text-muted">Fresco y natural</small>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="card tema-card" onclick="aplicarTema('gris_minimalista')">
                                    <div class="card-body text-center p-3">
                                        <div class="tema-preview mb-2">
                                            <div style="background: linear-gradient(135deg, #6c757d, #495057); height: 60px; border-radius: 8px; position: relative;">
                                                <div style="position: absolute; bottom: 5px; left: 5px; right: 5px; background: white; height: 20px; border-radius: 4px; opacity: 0.9;"></div>
                                            </div>
                                        </div>
                                        <h6 class="mb-1">Gris Minimalista</h6>
                                        <small class="text-muted">Limpio y simple</small>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="card tema-card" onclick="aplicarTema('oscuro_elegante')">
                                    <div class="card-body text-center p-3">
                                        <div class="tema-preview mb-2">
                                            <div style="background: linear-gradient(135deg, #343a40, #212529); height: 60px; border-radius: 8px; position: relative;">
                                                <div style="position: absolute; bottom: 5px; left: 5px; right: 5px; background: #495057; height: 20px; border-radius: 4px; opacity: 0.9;"></div>
                                            </div>
                                        </div>
                                        <h6 class="mb-1">Oscuro Elegante</h6>
                                        <small class="text-muted">Sofisticado y moderno</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card mt-3 border-danger">
                            <div class="card-body">
                                <h6 class="text-danger"><i class="fas fa-exclamation-triangle"></i> Zona de Peligro</h6>
                                <p class="text-muted mb-3">Esta acción restaurará todos los valores visuales a los predeterminados del sistema.</p>
                                <form method="POST" onsubmit="return confirm('¿Estás seguro? Esto eliminará toda la personalización visual.')">
                                    <input type="hidden" name="action" value="resetear_tema">
                                    <?php echo campoCSRF(); ?>
                                    <button type="submit" class="btn btn-outline-danger">
                                        <i class="fas fa-undo"></i> Resetear a Predeterminado
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Vista previa flotante -->
<div id="vistaPrevia" class="position-fixed" style="top: 20px; right: 20px; width: 300px; z-index: 1050; display: none;">
    <div class="card shadow">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Vista Previa</h6>
            <button type="button" class="btn-close" onclick="cerrarVistaPrevia()"></button>
        </div>
        <div class="card-body p-2">
            <div id="miniaturaSistema" style="transform: scale(0.3); transform-origin: top left; width: 333%; height: 300px; overflow: hidden;">
                <!-- Aquí se cargará la miniatura del sistema -->
            </div>
        </div>
    </div>
</div>

<script>
function actualizarVistaPrevia() {
    // Obtener todos los colores del formulario
    const colores = {};
    document.querySelectorAll('input[type="color"]').forEach(input => {
        colores[input.name] = input.value;
        // Actualizar campo de texto asociado
        const textInput = input.closest('.input-group').querySelector('input[type="text"]');
        if (textInput) {
            textInput.value = input.value;
        }
    });
    
    // Aplicar colores temporalmente
    aplicarColoresTemporales(colores);
}

function aplicarColoresTemporales(colores) {
    let style = document.getElementById('temporal-styles');
    if (!style) {
        style = document.createElement('style');
        style.id = 'temporal-styles';
        document.head.appendChild(style);
    }
    
    style.textContent = `
        :root {
            --bs-primary: ${colores.color_primario || '#007bff'};
            --bs-secondary: ${colores.color_secundario || '#6c757d'};
            --bs-success: ${colores.color_exito || '#28a745'};
            --bs-danger: ${colores.color_peligro || '#dc3545'};
            --bs-warning: ${colores.color_advertencia || '#ffc107'};
            --bs-info: ${colores.color_info || '#17a2b8'};
            --sidebar-bg: ${colores.color_sidebar || '#343a40'};
            --navbar-bg: ${colores.color_navbar || '#ffffff'};
            --text-color: ${colores.color_texto || '#212529'};
            --bg-color: ${colores.color_fondo || '#f8f9fa'};
        }
        
        .sidebar { background-color: var(--sidebar-bg) !important; }
        .navbar { background-color: var(--navbar-bg) !important; }
        body { color: var(--text-color) !important; background-color: var(--bg-color) !important; }
        .btn-primary { background-color: var(--bs-primary); border-color: var(--bs-primary); }
    `;
}

function previsualizarCambios() {
    const vistaPrevia = document.getElementById('vistaPrevia');
    vistaPrevia.style.display = vistaPrevia.style.display === 'none' ? 'block' : 'none';
    
    if (vistaPrevia.style.display === 'block') {
        cargarMiniaturaSistema();
    }
}

function cargarMiniaturaSistema() {
    // Simulación de la interfaz del sistema en miniatura
    const miniatura = document.getElementById('miniaturaSistema');
    miniatura.innerHTML = `
        <div style="display: flex; height: 300px;">
            <div style="width: 250px; background: var(--sidebar-bg, #343a40); color: white;">
                <div style="padding: 15px; border-bottom: 1px solid rgba(255,255,255,0.1);">
                    <strong>Sistema</strong>
                </div>
                <div style="padding: 10px;">
                    <div style="margin: 5px 0; padding: 8px; background: rgba(255,255,255,0.1); border-radius: 4px;">Dashboard</div>
                    <div style="margin: 5px 0; padding: 8px;">Productos</div>
                    <div style="margin: 5px 0; padding: 8px;">Inventarios</div>
                    <div style="margin: 5px 0; padding: 8px;">Ventas</div>
                </div>
            </div>
            <div style="flex: 1; background: var(--bg-color, #f8f9fa);">
                <div style="background: var(--navbar-bg, white); padding: 15px; border-bottom: 1px solid #dee2e6;">
                    <strong style="color: var(--text-color, #212529);">Dashboard</strong>
                </div>
                <div style="padding: 20px;">
                    <div style="background: var(--bs-primary, #007bff); color: white; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                        <strong>Tarjeta Principal</strong><br>
                        <span style="opacity: 0.9;">Información importante</span>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <div style="background: var(--bs-success, #28a745); color: white; padding: 10px; border-radius: 4px; flex: 1; text-align: center;">
                            <small>Éxito</small>
                        </div>
                        <div style="background: var(--bs-warning, #ffc107); color: black; padding: 10px; border-radius: 4px; flex: 1; text-align: center;">
                            <small>Advertencia</small>
                        </div>
                        <div style="background: var(--bs-danger, #dc3545); color: white; padding: 10px; border-radius: 4px; flex: 1; text-align: center;">
                            <small>Peligro</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
}

function cerrarVistaPrevia() {
    document.getElementById('vistaPrevia').style.display = 'none';
}

function restaurarColoresDefecto() {
    const coloresDefecto = {
        color_primario: '#007bff',
        color_secundario: '#6c757d',
        color_exito: '#28a745',
        color_peligro: '#dc3545',
        color_advertencia: '#ffc107',
        color_info: '#17a2b8',
        color_sidebar: '#343a40',
        color_navbar: '#ffffff',
        color_texto: '#212529',
        color_fondo: '#f8f9fa'
    };
    
    for (const [campo, color] of Object.entries(coloresDefecto)) {
        const input = document.querySelector(`input[name="${campo}"]`);
        if (input) {
            input.value = color;
            const textInput = input.closest('.input-group').querySelector('input[type="text"]');
            if (textInput) {
                textInput.value = color;
            }
        }
    }
    
    actualizarVistaPrevia();
}

function actualizarValorBordes(valor) {
    document.getElementById('valorBordes').textContent = valor + 'rem';
    document.documentElement.style.setProperty('--bordes-layout', valor + 'rem');
}

function aplicarTema(tema) {
    const temas = {
        azul_profesional: {
            color_primario: '#007bff',
            color_secundario: '#6c757d',
            color_exito: '#28a745',
            color_peligro: '#dc3545',
            color_advertencia: '#ffc107',
            color_info: '#17a2b8',
            color_sidebar: '#343a40',
            color_navbar: '#ffffff',
            color_texto: '#212529',
            color_fondo: '#f8f9fa'
        },
        verde_moderno: {
            color_primario: '#28a745',
            color_secundario: '#20c997',
            color_exito: '#20c997',
            color_peligro: '#dc3545',
            color_advertencia: '#fd7e14',
            color_info: '#20c997',
            color_sidebar: '#155724',
            color_navbar: '#f8f9fa',
            color_texto: '#155724',
            color_fondo: '#f8fcf9'
        },
        gris_minimalista: {
            color_primario: '#6c757d',
            color_secundario: '#adb5bd',
            color_exito: '#28a745',
            color_peligro: '#dc3545',
            color_advertencia: '#ffc107',
            color_info: '#17a2b8',
            color_sidebar: '#495057',
            color_navbar: '#ffffff',
            color_texto: '#495057',
            color_fondo: '#f8f9fa'
        },
        oscuro_elegante: {
            color_primario: '#6f42c1',
            color_secundario: '#6c757d',
            color_exito: '#28a745',
            color_peligro: '#dc3545',
            color_advertencia: '#ffc107',
            color_info: '#17a2b8',
            color_sidebar: '#212529',
            color_navbar: '#343a40',
            color_texto: '#ffffff',
            color_fondo: '#343a40'
        }
    };
    
    if (temas[tema]) {
        // Actualizar campos de color
        for (const [campo, color] of Object.entries(temas[tema])) {
            const input = document.querySelector(`input[name="${campo}"]`);
            if (input) {
                input.value = color;
                const textInput = input.closest('.input-group').querySelector('input[type="text"]');
                if (textInput) {
                    textInput.value = color;
                }
            }
        }
        
        // Aplicar vista previa
        aplicarColoresTemporales(temas[tema]);
        
        // Mostrar mensaje
        alert(`Tema "${tema.replace('_', ' ')}" aplicado. Recuerda guardar los cambios.`);
    }
}

// Event listeners para fuentes
document.addEventListener('change', function(e) {
    if (e.target.name === 'fuente_principal' || e.target.name === 'fuente_secundaria') {
        actualizarVistaPrevia();
        actualizarPrevisualizacionFuente();
    }
});

function actualizarPrevisualizacionFuente() {
    const principal = document.querySelector('select[name="fuente_principal"]').value;
    const secundaria = document.querySelector('select[name="fuente_secundaria"]').value;
    
    document.documentElement.style.setProperty('--fuente-principal', principal);
    document.documentElement.style.setProperty('--fuente-secundaria', secundaria);
}

// Event listeners para layout
document.addEventListener('change', function(e) {
    if (e.target.name === 'sombras_activas') {
        const sombras = e.target.checked ? '0 0.125rem 0.25rem rgba(0,0,0,0.075)' : 'none';
        document.documentElement.style.setProperty('--sombras-layout', sombras);
    }
    
    if (e.target.name === 'animaciones_activas') {
        const transicion = e.target.checked ? 'all 0.3s ease' : 'none';
        document.documentElement.style.setProperty('--transicion-layout', transicion);
    }
    
    if (e.target.name === 'modo_compacto') {
        const padding = e.target.checked ? '8px' : '15px';
        document.documentElement.style.setProperty('--padding-layout', padding);
    }
});

// Inicializar vista previa al cargar
document.addEventListener('DOMContentLoaded', function() {
    actualizarVistaPrevia();
    actualizarPrevisualizacionFuente();
    
    // Agregar estilos CSS para las tarjetas de tema
    const estilosCSS = `
        <style>
        .tema-card {
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .tema-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .tema-preview {
            border-radius: 8px;
            overflow: hidden;
        }
        
        .form-range {
            width: 100%;
        }
        
        #vistaPrevia {
            border-radius: 8px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.12);
        }
        
        #temporal-styles {
            /* Estilos temporales aplicados dinámicamente */
        }
        
        /* Animaciones suaves para cambios de color */
        * {
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
        }
        </style>
    `;
    
    document.head.insertAdjacentHTML('beforeend', estilosCSS);
});
</script>

<?php include 'includes/layout_footer.php'; ?>
