<?php
$titulo = "Configuración del Sistema";
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'includes/config_functions.php';

verificarLogin();
verificarPermiso('config_sistema');

$database = new Database();
$db = $database->getConnection();

if ($_POST && isset($_POST['action'])) {
    try {
        if ($_POST['action'] == 'guardar_general') {
            $configuraciones = [
                'nombre_sistema' => $_POST['nombre_sistema'],
                'empresa_nombre' => $_POST['empresa_nombre'],
                'empresa_direccion' => $_POST['empresa_direccion'],
                'empresa_telefono' => $_POST['empresa_telefono'],
                'empresa_email' => $_POST['empresa_email']
            ];
            
            foreach ($configuraciones as $clave => $valor) {
                actualizarConfiguracion($clave, $valor, $_SESSION['usuario_id']);
            }
            
            $success = "Configuración general guardada correctamente";
        }
        
        if ($_POST['action'] == 'guardar_moneda') {
            $configuraciones = [
                'moneda_codigo' => $_POST['moneda_codigo'],
                'moneda_nombre' => $_POST['moneda_nombre'],
                'simbolo_moneda' => $_POST['simbolo_moneda'],
                'posicion_simbolo' => $_POST['posicion_simbolo'],
                'separador_decimal' => $_POST['separador_decimal'],
                'separador_miles' => $_POST['separador_miles'],
                'decimales_mostrar' => $_POST['decimales_mostrar']
            ];
            
            foreach ($configuraciones as $clave => $valor) {
                actualizarConfiguracion($clave, $valor, $_SESSION['usuario_id']);
            }
            
            $success = "Configuración de moneda guardada correctamente";
        }
        
        if ($_POST['action'] == 'guardar_colores') {
            $configuraciones = [
                'color_primario' => $_POST['color_primario'],
                'color_secundario' => $_POST['color_secundario'],
                'sidebar_color' => $_POST['sidebar_color'],
                'topbar_color' => $_POST['topbar_color']
            ];
            
            foreach ($configuraciones as $clave => $valor) {
                actualizarConfiguracion($clave, $valor, $_SESSION['usuario_id']);
            }
            
            $success = "Colores del sistema actualizados correctamente";
        }
        
        if ($_POST['action'] == 'aplicar_tema') {
            if (aplicarTema($_POST['tema_id'])) {
                $success = "Tema aplicado correctamente";
            } else {
                $error = "Error al aplicar el tema";
            }
        }
        
        if ($_POST['action'] == 'guardar_inventario') {
            $configuraciones = [
                'stock_minimo_default' => $_POST['stock_minimo_default'],
                'permitir_stock_negativo' => isset($_POST['permitir_stock_negativo']) ? '1' : '0',
                'alerta_stock_bajo' => isset($_POST['alerta_stock_bajo']) ? '1' : '0'
            ];
            
            foreach ($configuraciones as $clave => $valor) {
                actualizarConfiguracion($clave, $valor, $_SESSION['usuario_id']);
            }
            
            $success = "Configuración de inventario guardada correctamente";
        }
        
        limpiarCacheConfiguracion();
        
    } catch (Exception $e) {
        $error = "Error al guardar la configuración: " . $e->getMessage();
    }
}

$config = cargarConfiguracion();
$temas = obtenerTemas();
$tema_actual = obtenerTemaActual();
$colores_css = obtenerColoresCSS();

include 'includes/layout_header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-cogs"></i> Configuración del Sistema</h2>
        <div class="btn-group">
            <button class="btn btn-outline-primary" onclick="exportarConfiguracion()">
                <i class="fas fa-download"></i> Exportar
            </button>
            <button class="btn btn-outline-success" onclick="document.getElementById('importFile').click()">
                <i class="fas fa-upload"></i> Importar
            </button>
            <input type="file" id="importFile" style="display: none" accept=".json" onchange="importarConfiguracion(this.files[0])">
        </div>
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
    
    <!-- Pestañas de configuración -->
    <nav>
        <div class="nav nav-pills mb-4" id="nav-tab" role="tablist">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#general" type="button">
                <i class="fas fa-info-circle"></i> General
            </button>
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#moneda" type="button">
                <i class="fas fa-dollar-sign"></i> Moneda
            </button>
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#temas" type="button">
                <i class="fas fa-palette"></i> Temas y Colores
            </button>
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#inventario" type="button">
                <i class="fas fa-warehouse"></i> Inventario
            </button>
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#avanzado" type="button">
                <i class="fas fa-cog"></i> Avanzado
            </button>
            <?php if (esAdmin()): ?>
            <button class="nav-link text-danger" data-bs-toggle="tab" data-bs-target="#sistema" type="button">
                <i class="fas fa-exclamation-triangle"></i> Sistema
            </button>
            <?php endif; ?>
        </div>
    </nav>
    
    <div class="tab-content">
        <!-- Configuración General -->
        <div class="tab-pane fade show active" id="general">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-info-circle"></i> Configuración General</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="guardar_general">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nombre del Sistema</label>
                                    <input type="text" class="form-control" name="nombre_sistema" 
                                           value="<?php echo htmlspecialchars($config['nombre_sistema'] ?? ''); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Nombre de la Empresa</label>
                                    <input type="text" class="form-control" name="empresa_nombre" 
                                           value="<?php echo htmlspecialchars($config['empresa_nombre'] ?? ''); ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email de la Empresa</label>
                                    <input type="email" class="form-control" name="empresa_email" 
                                           value="<?php echo htmlspecialchars($config['empresa_email'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Teléfono</label>
                                    <input type="text" class="form-control" name="empresa_telefono" 
                                           value="<?php echo htmlspecialchars($config['empresa_telefono'] ?? ''); ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Dirección</label>
                                    <textarea class="form-control" name="empresa_direccion" rows="3"><?php echo htmlspecialchars($config['empresa_direccion'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Guardar Configuración General
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Configuración de Moneda -->
        <div class="tab-pane fade" id="moneda">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-dollar-sign"></i> Configuración de Moneda</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="guardar_moneda">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Código de Moneda</label>
                                    <select class="form-control" name="moneda_codigo">
                                        <option value="GTQ" <?php echo ($config['moneda_codigo'] ?? '') == 'GTQ' ? 'selected' : ''; ?>>GTQ - Quetzal Guatemalteco</option>
                                        <option value="USD" <?php echo ($config['moneda_codigo'] ?? '') == 'USD' ? 'selected' : ''; ?>>USD - Dólar Americano</option>
                                        <option value="EUR" <?php echo ($config['moneda_codigo'] ?? '') == 'EUR' ? 'selected' : ''; ?>>EUR - Euro</option>
                                        <option value="MXN" <?php echo ($config['moneda_codigo'] ?? '') == 'MXN' ? 'selected' : ''; ?>>MXN - Peso Mexicano</option>
                                        <option value="COP" <?php echo ($config['moneda_codigo'] ?? '') == 'COP' ? 'selected' : ''; ?>>COP - Peso Colombiano</option>
                                        <option value="ARS" <?php echo ($config['moneda_codigo'] ?? '') == 'ARS' ? 'selected' : ''; ?>>ARS - Peso Argentino</option>
                                        <option value="CLP" <?php echo ($config['moneda_codigo'] ?? '') == 'CLP' ? 'selected' : ''; ?>>CLP - Peso Chileno</option>
                                        <option value="PEN" <?php echo ($config['moneda_codigo'] ?? '') == 'PEN' ? 'selected' : ''; ?>>PEN - Sol Peruano</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Nombre de la Moneda</label>
                                    <input type="text" class="form-control" name="moneda_nombre" 
                                           value="<?php echo htmlspecialchars($config['moneda_nombre'] ?? ''); ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Símbolo de Moneda</label>
                                    <input type="text" class="form-control" name="simbolo_moneda" 
                                           value="<?php echo htmlspecialchars($config['simbolo_moneda'] ?? 'Q'); ?>" maxlength="5">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Posición del Símbolo</label>
                                    <select class="form-control" name="posicion_simbolo">
                                        <option value="antes" <?php echo ($config['posicion_simbolo'] ?? 'antes') == 'antes' ? 'selected' : ''; ?>>Antes ($100.00)</option>
                                        <option value="despues" <?php echo ($config['posicion_simbolo'] ?? 'antes') == 'despues' ? 'selected' : ''; ?>>Después (100.00 $)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Separador Decimal</label>
                                    <select class="form-control" name="separador_decimal">
                                        <option value="." <?php echo ($config['separador_decimal'] ?? '.') == '.' ? 'selected' : ''; ?>>Punto (.)</option>
                                        <option value="," <?php echo ($config['separador_decimal'] ?? '.') == ',' ? 'selected' : ''; ?>>Coma (,)</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Separador de Miles</label>
                                    <select class="form-control" name="separador_miles">
                                        <option value="," <?php echo ($config['separador_miles'] ?? ',') == ',' ? 'selected' : ''; ?>>Coma (,)</option>
                                        <option value="." <?php echo ($config['separador_miles'] ?? ',') == '.' ? 'selected' : ''; ?>>Punto (.)</option>
                                        <option value=" " <?php echo ($config['separador_miles'] ?? ',') == ' ' ? 'selected' : ''; ?>>Espacio ( )</option>
                                        <option value="" <?php echo ($config['separador_miles'] ?? ',') == '' ? 'selected' : ''; ?>>Sin separador</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Decimales a Mostrar</label>
                                    <select class="form-control" name="decimales_mostrar">
                                        <option value="0" <?php echo ($config['decimales_mostrar'] ?? '2') == '0' ? 'selected' : ''; ?>>0</option>
                                        <option value="2" <?php echo ($config['decimales_mostrar'] ?? '2') == '2' ? 'selected' : ''; ?>>2</option>
                                        <option value="3" <?php echo ($config['decimales_mostrar'] ?? '2') == '3' ? 'selected' : ''; ?>>3</option>
                                        <option value="4" <?php echo ($config['decimales_mostrar'] ?? '2') == '4' ? 'selected' : ''; ?>>4</option>
                                    </select>
                                </div>
                                <div class="alert alert-info">
                                    <h6>Vista previa:</h6>
                                    <span id="preview-moneda"><?php echo formatearMoneda(1234.56); ?></span>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Guardar Configuración de Moneda
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Temas y Colores -->
        <div class="tab-pane fade" id="temas">
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-palette"></i> Temas Predefinidos</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="aplicar_tema">
                                <div class="row">
                                    <?php foreach ($temas as $tema): ?>
                                        <div class="col-6 mb-3">
                                            <div class="card theme-card <?php echo $tema['nombre'] == $tema_actual['nombre'] ? 'border-primary' : ''; ?>">
                                                <div class="card-body text-center p-3">
                                                    <div class="theme-preview mb-2">
                                                        <div class="theme-colors d-flex justify-content-center gap-1">
                                                            <div class="color-dot" style="background: <?php echo $tema['color_primario']; ?>"></div>
                                                            <div class="color-dot" style="background: <?php echo $tema['sidebar_color']; ?>"></div>
                                                            <div class="color-dot" style="background: <?php echo $tema['topbar_color']; ?>"></div>
                                                        </div>
                                                    </div>
                                                    <h6><?php echo $tema['nombre']; ?></h6>
                                                    <p class="small text-muted"><?php echo $tema['descripcion']; ?></p>
                                                    <?php if ($tema['nombre'] == $tema_actual['nombre']): ?>
                                                        <button type="button" class="btn btn-sm btn-primary" disabled>
                                                            <i class="fas fa-check"></i> Activo
                                                        </button>
                                                    <?php else: ?>
                                                        <button type="submit" name="tema_id" value="<?php echo $tema['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                            Aplicar
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-paint-brush"></i> Personalizar Colores</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="guardar_colores">
                                <div class="mb-3">
                                    <label class="form-label">Color Primario</label>
                                    <div class="input-group">
                                        <input type="color" class="form-control form-control-color" name="color_primario" 
                                               value="<?php echo $config['color_primario'] ?? '#007bff'; ?>">
                                        <input type="text" class="form-control" name="color_primario_text" 
                                               value="<?php echo $config['color_primario'] ?? '#007bff'; ?>">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Color Secundario</label>
                                    <div class="input-group">
                                        <input type="color" class="form-control form-control-color" name="color_secundario" 
                                               value="<?php echo $config['color_secundario'] ?? '#6c757d'; ?>">
                                        <input type="text" class="form-control" name="color_secundario_text" 
                                               value="<?php echo $config['color_secundario'] ?? '#6c757d'; ?>">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Color del Sidebar</label>
                                    <div class="input-group">
                                        <input type="color" class="form-control form-control-color" name="sidebar_color" 
                                               value="<?php echo $config['sidebar_color'] ?? '#2c3e50'; ?>">
                                        <input type="text" class="form-control" name="sidebar_color_text" 
                                               value="<?php echo $config['sidebar_color'] ?? '#2c3e50'; ?>">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Color de la Barra Superior</label>
                                    <div class="input-group">
                                        <input type="color" class="form-control form-control-color" name="topbar_color" 
                                               value="<?php echo $config['topbar_color'] ?? '#007bff'; ?>">
                                        <input type="text" class="form-control" name="topbar_color_text" 
                                               value="<?php echo $config['topbar_color'] ?? '#007bff'; ?>">
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Guardar Colores
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Configuración de Inventario -->
        <div class="tab-pane fade" id="inventario">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-warehouse"></i> Configuración de Inventario</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="guardar_inventario">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Stock Mínimo por Defecto</label>
                                    <input type="number" class="form-control" name="stock_minimo_default" 
                                           value="<?php echo $config['stock_minimo_default'] ?? 5; ?>" min="0">
                                    <small class="text-muted">Cantidad mínima que se asignará a nuevos productos</small>
                                </div>
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" name="permitir_stock_negativo" 
                                               <?php echo ($config['permitir_stock_negativo'] ?? false) ? 'checked' : ''; ?>>
                                        <label class="form-check-label">Permitir Stock Negativo</label>
                                    </div>
                                    <small class="text-muted">Permitir realizar ventas aunque no haya suficiente stock</small>
                                </div>
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" name="alerta_stock_bajo" 
                                               <?php echo ($config['alerta_stock_bajo'] ?? true) ? 'checked' : ''; ?>>
                                        <label class="form-check-label">Mostrar Alertas de Stock Bajo</label>
                                    </div>
                                    <small class="text-muted">Mostrar notificaciones cuando el stock esté por debajo del mínimo</small>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Guardar Configuración de Inventario
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Configuración Avanzada -->
        <div class="tab-pane fade" id="avanzado">
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-download"></i> Exportar/Importar</h5>
                        </div>
                        <div class="card-body">
                            <p>Exporta o importa toda la configuración del sistema, incluyendo temas, colores y etiquetas personalizadas.</p>
                            <div class="d-grid gap-2">
                                <button class="btn btn-outline-primary" onclick="exportarConfiguracion()">
                                    <i class="fas fa-download"></i> Exportar Configuración
                                </button>
                                <button class="btn btn-outline-success" onclick="document.getElementById('importFileAdvanced').click()">
                                    <i class="fas fa-upload"></i> Importar Configuración
                                </button>
                                <input type="file" id="importFileAdvanced" style="display: none" accept=".json" onchange="importarConfiguracion(this.files[0])">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-info-circle"></i> Información del Sistema</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Versión:</strong></td>
                                    <td><?php echo $config['version_sistema'] ?? '2.0.0'; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>PHP:</strong></td>
                                    <td><?php echo phpversion(); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Base de Datos:</strong></td>
                                    <td><?php echo $db->getAttribute(PDO::ATTR_SERVER_VERSION); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Usuario Actual:</strong></td>
                                    <td><?php echo htmlspecialchars($_SESSION['usuario_nombre'] ?? 'N/A'); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Tema Actual:</strong></td>
                                    <td><?php echo $tema_actual['nombre'] ?? 'Default'; ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Configuración del Sistema (Solo Administradores) -->
        <?php if (esAdmin()): ?>
        <div class="tab-pane fade" id="sistema">
            <div class="card border-danger">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-exclamation-triangle"></i> 
                        Administración del Sistema
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <h6><i class="fas fa-info-circle"></i> Solo para Administradores</h6>
                        <p class="mb-0">
                            Esta sección contiene herramientas avanzadas de administración que pueden 
                            afectar permanentemente el sistema. Use con precaución.
                        </p>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card border-warning">
                                <div class="card-header bg-warning">
                                    <h6 class="mb-0">
                                        <i class="fas fa-trash-alt"></i> 
                                        Limpiar Datos Demo
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <p class="card-text small">
                                        Elimina todos los datos de demostración para entregar 
                                        un sistema completamente limpio al cliente.
                                    </p>
                                    <ul class="small text-muted">
                                        <li>Elimina productos, ventas, inventarios demo</li>
                                        <li>Preserva configuración y usuario admin</li>
                                        <li>Acción permanente - no reversible</li>
                                    </ul>
                                    <a href="admin_reset_sistema.php" class="btn btn-warning btn-sm">
                                        <i class="fas fa-external-link-alt"></i>
                                        Acceder a Reset del Sistema
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card border-info">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0">
                                        <i class="fas fa-info-circle"></i> 
                                        Información del Sistema
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm">
                                        <tr>
                                            <td><strong>Versión PHP:</strong></td>
                                            <td><?php echo phpversion(); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Base de Datos:</strong></td>
                                            <td>inventario_sistema</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Usuario Actual:</strong></td>
                                            <td><?php echo htmlspecialchars($_SESSION['usuario_email'] ?? 'N/A'); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Rol:</strong></td>
                                            <td>
                                                <span class="badge bg-danger">
                                                    <?php echo htmlspecialchars($_SESSION['rol'] ?? 'N/A'); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.theme-card {
    cursor: pointer;
    transition: all 0.3s;
}

.theme-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.color-dot {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    border: 2px solid #fff;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.form-control-color {
    width: 50px;
    height: 38px;
    border-radius: 6px 0 0 6px;
}
</style>

<script>
// Sincronizar colores entre input color y text
document.querySelectorAll('input[type="color"]').forEach(colorInput => {
    const textInput = document.querySelector(`input[name="${colorInput.name}_text"]`);
    if (textInput) {
        colorInput.addEventListener('input', () => {
            textInput.value = colorInput.value;
        });
        textInput.addEventListener('input', () => {
            if (/^#[0-9A-F]{6}$/i.test(textInput.value)) {
                colorInput.value = textInput.value;
            }
        });
    }
});

// Preview de moneda
function updateMoneyPreview() {
    // Esta función se podría implementar para mostrar vista previa en tiempo real
}

// Exportar configuración
function exportarConfiguracion() {
    fetch('includes/export_config.php')
        .then(response => response.blob())
        .then(blob => {
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'configuracion_sistema_' + new Date().toISOString().split('T')[0] + '.json';
            a.click();
            window.URL.revokeObjectURL(url);
            showToast('Configuración exportada correctamente', 'success');
        })
        .catch(error => {
            showToast('Error al exportar configuración', 'danger');
        });
}

// Importar configuración
function importarConfiguracion(file) {
    if (!file) return;
    
    const formData = new FormData();
    formData.append('config_file', file);
    
    fetch('includes/import_config.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Configuración importada correctamente', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast(data.error || 'Error al importar configuración', 'danger');
        }
    })
    .catch(error => {
        showToast('Error al importar configuración', 'danger');
    });
}
</script>

<?php include 'includes/layout_footer.php'; ?>