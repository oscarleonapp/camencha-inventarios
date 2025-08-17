<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

verificarLogin();
verificarPermiso('config_roles');

$database = new Database();
$db = $database->getConnection();

if ($_POST && isset($_POST['action'])) {
    if ($_POST['action'] == 'crear_rol') {
        $nombre = $_POST['nombre'];
        $descripcion = $_POST['descripcion'];
        
        $query = "INSERT INTO roles (nombre, descripcion, es_sistema) VALUES (?, ?, FALSE)";
        $stmt = $db->prepare($query);
        $stmt->execute([$nombre, $descripcion]);
        
        $success = "Rol creado exitosamente";
    }
    
    if ($_POST['action'] == 'actualizar_permisos') {
        $rol_id = $_POST['rol_id'];
        $permisos = isset($_POST['permisos']) ? $_POST['permisos'] : [];
        
        $db->beginTransaction();
        
        try {
            $query_delete = "DELETE FROM rol_permisos WHERE rol_id = ?";
            $stmt_delete = $db->prepare($query_delete);
            $stmt_delete->execute([$rol_id]);
            
            foreach ($permisos as $permiso_id => $acciones) {
                $puede_crear = isset($acciones['crear']) ? 1 : 0;
                $puede_leer = isset($acciones['leer']) ? 1 : 0;
                $puede_actualizar = isset($acciones['actualizar']) ? 1 : 0;
                $puede_eliminar = isset($acciones['eliminar']) ? 1 : 0;
                
                if ($puede_leer || $puede_crear || $puede_actualizar || $puede_eliminar) {
                    $query_insert = "INSERT INTO rol_permisos (rol_id, permiso_id, puede_crear, puede_leer, puede_actualizar, puede_eliminar) 
                                    VALUES (?, ?, ?, ?, ?, ?)";
                    $stmt_insert = $db->prepare($query_insert);
                    $stmt_insert->execute([$rol_id, $permiso_id, $puede_crear, $puede_leer, $puede_actualizar, $puede_eliminar]);
                }
            }
            
            $db->commit();
            $success = "Permisos actualizados exitosamente";
            
        } catch (Exception $e) {
            $db->rollBack();
            $error = "Error al actualizar permisos: " . $e->getMessage();
        }
    }
}

$query_roles = "SELECT * FROM roles WHERE activo = 1 ORDER BY es_sistema DESC, nombre";
$stmt_roles = $db->prepare($query_roles);
$stmt_roles->execute();
$roles = $stmt_roles->fetchAll(PDO::FETCH_ASSOC);

$query_permisos = "SELECT * FROM permisos WHERE activo = 1 ORDER BY modulo, orden, nombre";
$stmt_permisos = $db->prepare($query_permisos);
$stmt_permisos->execute();
$permisos = $stmt_permisos->fetchAll(PDO::FETCH_ASSOC);

$permisos_por_modulo = [];
foreach ($permisos as $permiso) {
    $permisos_por_modulo[$permiso['modulo']][] = $permiso;
}

$rol_seleccionado = isset($_GET['rol']) ? $_GET['rol'] : null;
$permisos_rol = [];
if ($rol_seleccionado) {
    $query_permisos_rol = "SELECT permiso_id, puede_crear, puede_leer, puede_actualizar, puede_eliminar 
                          FROM rol_permisos WHERE rol_id = ?";
    $stmt_permisos_rol = $db->prepare($query_permisos_rol);
    $stmt_permisos_rol->execute([$rol_seleccionado]);
    
    while ($row = $stmt_permisos_rol->fetch(PDO::FETCH_ASSOC)) {
        $permisos_rol[$row['permiso_id']] = $row;
    }
}

$css_adicional = ['assets/css/roles.css'];
include 'includes/layout_header.php';
?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-user-shield"></i> <span class="editable" data-label="roles_titulo">Gestión de Roles y Permisos</span></h2>
        <button class="btn btn-outline-info" onclick="mostrarAyuda()">
            <i class="fas fa-question-circle"></i> Ayuda
        </button>
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
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5>Crear Nuevo Rol</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="crear_rol">
                            <div class="mb-3">
                                <label class="form-label">Nombre del Rol</label>
                                <input type="text" class="form-control" name="nombre" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Descripción</label>
                                <textarea class="form-control" name="descripcion" rows="3"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Crear Rol</button>
                        </form>
                    </div>
                </div>
                
                <div class="card mt-3">
                    <div class="card-header">
                        <h5>Roles Existentes</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            <?php foreach ($roles as $rol): ?>
                                <a href="?rol=<?php echo $rol['id']; ?>" 
                                   class="list-group-item list-group-item-action <?php echo $rol_seleccionado == $rol['id'] ? 'active' : ''; ?>">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1">
                                            <?php echo $rol['nombre']; ?>
                                            <?php if ($rol['es_sistema']): ?>
                                                <small class="badge bg-secondary ms-2">Sistema</small>
                                            <?php endif; ?>
                                        </h6>
                                    </div>
                                    <p class="mb-1 small"><?php echo $rol['descripcion']; ?></p>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <?php if ($rol_seleccionado): ?>
                    <?php 
                    $rol_actual = array_filter($roles, function($r) use ($rol_seleccionado) {
                        return $r['id'] == $rol_seleccionado;
                    });
                    $rol_actual = reset($rol_actual);
                    ?>
                    
                    <div class="card">
                        <div class="card-header">
                            <h5>Configurar Permisos: <?php echo $rol_actual['nombre']; ?></h5>
                            <small class="text-muted"><?php echo $rol_actual['descripcion']; ?></small>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="actualizar_permisos">
                                <input type="hidden" name="rol_id" value="<?php echo $rol_seleccionado; ?>">
                                
                                <div class="mb-3">
                                    <div class="row fw-bold">
                                        <div class="col-5">Permiso</div>
                                        <div class="col-7">
                                            <div class="checkbox-group">
                                                <small>Crear</small>
                                                <small>Leer</small>
                                                <small>Editar</small>
                                                <small>Eliminar</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php foreach ($permisos_por_modulo as $modulo => $permisos_modulo): ?>
                                    <div class="modulo-header">
                                        <strong><?php echo ucfirst(str_replace('_', ' ', $modulo)); ?></strong>
                                    </div>
                                    
                                    <?php foreach ($permisos_modulo as $permiso): ?>
                                        <div class="permiso-row">
                                            <div class="row align-items-center">
                                                <div class="col-5">
                                                    <small class="text-muted"><?php echo $permiso['nombre']; ?></small><br>
                                                    <span><?php echo $permiso['descripcion']; ?></span>
                                                </div>
                                                <div class="col-7">
                                                    <div class="checkbox-group">
                                                        <div class="form-check">
                                                            <input type="checkbox" 
                                                                   class="form-check-input" 
                                                                   name="permisos[<?php echo $permiso['id']; ?>][crear]"
                                                                   <?php echo isset($permisos_rol[$permiso['id']]) && $permisos_rol[$permiso['id']]['puede_crear'] ? 'checked' : ''; ?>>
                                                        </div>
                                                        <div class="form-check">
                                                            <input type="checkbox" 
                                                                   class="form-check-input" 
                                                                   name="permisos[<?php echo $permiso['id']; ?>][leer]"
                                                                   <?php echo isset($permisos_rol[$permiso['id']]) && $permisos_rol[$permiso['id']]['puede_leer'] ? 'checked' : ''; ?>>
                                                        </div>
                                                        <div class="form-check">
                                                            <input type="checkbox" 
                                                                   class="form-check-input" 
                                                                   name="permisos[<?php echo $permiso['id']; ?>][actualizar]"
                                                                   <?php echo isset($permisos_rol[$permiso['id']]) && $permisos_rol[$permiso['id']]['puede_actualizar'] ? 'checked' : ''; ?>>
                                                        </div>
                                                        <div class="form-check">
                                                            <input type="checkbox" 
                                                                   class="form-check-input" 
                                                                   name="permisos[<?php echo $permiso['id']; ?>][eliminar]"
                                                                   <?php echo isset($permisos_rol[$permiso['id']]) && $permisos_rol[$permiso['id']]['puede_eliminar'] ? 'checked' : ''; ?>>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                                
                                <div class="mt-4">
                                    <button type="submit" class="btn btn-success">Guardar Permisos</button>
                                    <button type="button" class="btn btn-secondary ms-2" onclick="marcarTodos(true)">Marcar Todos</button>
                                    <button type="button" class="btn btn-secondary ms-2" onclick="marcarTodos(false)">Desmarcar Todos</button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-body text-center">
                            <h5>Selecciona un Rol</h5>
                            <p class="text-muted">Selecciona un rol de la lista para configurar sus permisos.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
<script>
function marcarTodos(marcar) {
    const checkboxes = document.querySelectorAll('input[type="checkbox"]');
    checkboxes.forEach(checkbox => {
        checkbox.checked = marcar;
    });
}

function mostrarAyuda() {
    const helpContent = `
        <div class="modal fade" id="helpModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Ayuda - Gestión de Roles y Permisos</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <h6>Niveles de Acceso:</h6>
                        <ul>
                            <li><strong>Crear:</strong> Puede crear nuevos registros</li>
                            <li><strong>Leer:</strong> Puede ver la información</li>
                            <li><strong>Actualizar:</strong> Puede modificar registros existentes</li>
                            <li><strong>Eliminar:</strong> Puede eliminar registros</li>
                        </ul>
                        <h6>Roles Predefinidos:</h6>
                        <ul>
                            <li><strong>Administrador:</strong> Acceso total al sistema</li>
                            <li><strong>Gerente:</strong> Acceso completo excepto configuración crítica</li>
                            <li><strong>Encargado de Tienda:</strong> Operaciones de tienda</li>
                            <li><strong>Vendedor:</strong> Solo ventas y consultas básicas</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remover modal existente
    const existingModal = document.getElementById('helpModal');
    if (existingModal) existingModal.remove();
    
    // Agregar nuevo modal
    document.body.insertAdjacentHTML('beforeend', helpContent);
    const modal = new bootstrap.Modal(document.getElementById('helpModal'));
    modal.show();
}

// Auto-marcar "leer" cuando se marca cualquier otra acción
document.addEventListener('change', function(e) {
    if (e.target.type === 'checkbox' && (e.target.name.includes('[crear]') || 
        e.target.name.includes('[actualizar]') || e.target.name.includes('[eliminar]'))) {
        if (e.target.checked) {
            const leerCheckbox = e.target.name.replace(/\[(crear|actualizar|eliminar)\]/, '[leer]');
            const leerInput = document.querySelector(`input[name="${leerCheckbox}"]`);
            if (leerInput) {
                leerInput.checked = true;
            }
        }
    }
});
</script>

<style>
.modulo-header {
    background: #f8f9fa;
    padding: 10px;
    margin-bottom: 10px;
    border-left: 4px solid var(--primary-color);
    border-radius: 0 8px 8px 0;
}
.permiso-row {
    padding: 8px 0;
    border-bottom: 1px solid #eee;
    border-radius: 4px;
    margin: 2px 0;
}
.permiso-row:hover {
    background-color: #f8f9fa;
}
.checkbox-group {
    display: flex;
    gap: 15px;
}
.form-check-input:checked {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
}
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
</style>

<?php include 'includes/layout_footer.php'; ?>