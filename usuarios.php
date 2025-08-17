<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

verificarLogin();
verificarPermiso('usuarios_ver');

$database = new Database();
$db = $database->getConnection();

if ($_POST && isset($_POST['action'])) {
    if ($_POST['action'] == 'crear_usuario' && tienePermiso('usuarios_crear', 'crear')) {
        $nombre = $_POST['nombre'];
        $email = $_POST['email'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $rol_id = $_POST['rol_id'];
        
        $query = "INSERT INTO usuarios (nombre, email, password, rol_id) VALUES (?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->execute([$nombre, $email, $password, $rol_id]);
        
        $success = "Usuario creado exitosamente";
    }
    
    if ($_POST['action'] == 'cambiar_rol' && tienePermiso('usuarios_editar', 'actualizar')) {
        $usuario_id = $_POST['usuario_id'];
        $nuevo_rol_id = $_POST['nuevo_rol_id'];
        
        $query = "UPDATE usuarios SET rol_id = ? WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$nuevo_rol_id, $usuario_id]);
        
        $success = "Rol actualizado exitosamente";
    }
    
    if ($_POST['action'] == 'cambiar_estado' && tienePermiso('usuarios_editar', 'actualizar')) {
        $usuario_id = $_POST['usuario_id'];
        $nuevo_estado = $_POST['nuevo_estado'];
        
        $query = "UPDATE usuarios SET activo = ? WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$nuevo_estado, $usuario_id]);
        
        $success = "Estado del usuario actualizado";
    }
}

$query_usuarios = "SELECT u.*, r.nombre as rol_nombre 
                   FROM usuarios u 
                   LEFT JOIN roles r ON u.rol_id = r.id 
                   ORDER BY u.fecha_creacion DESC";
$stmt_usuarios = $db->prepare($query_usuarios);
$stmt_usuarios->execute();
$usuarios = $stmt_usuarios->fetchAll(PDO::FETCH_ASSOC);

$query_roles = "SELECT * FROM roles WHERE activo = 1 ORDER BY nombre";
$stmt_roles = $db->prepare($query_roles);
$stmt_roles->execute();
$roles = $stmt_roles->fetchAll(PDO::FETCH_ASSOC);

$query_tiendas = "SELECT * FROM tiendas WHERE activo = 1 ORDER BY nombre";
$stmt_tiendas = $db->prepare($query_tiendas);
$stmt_tiendas->execute();
$tiendas = $stmt_tiendas->fetchAll(PDO::FETCH_ASSOC);

include 'includes/layout_header.php';
?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-users"></i> <span class="editable" data-label="usuarios_titulo">Gestión de Usuarios</span></h2>
        <?php if (tienePermiso('usuarios_crear', 'crear')): ?>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#nuevoUsuarioModal">
            <i class="fas fa-user-plus"></i> Nuevo Usuario
        </button>
        <?php endif; ?>
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
        
        <!-- Modal para Crear Usuario -->
        <?php if (tienePermiso('usuarios_crear', 'crear')): ?>
        <div class="modal fade" id="nuevoUsuarioModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-user-plus"></i> Crear Nuevo Usuario
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="action" value="crear_usuario">
                            
                            <!-- Fila 1: Información Personal -->
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Nombre Completo</label>
                                    <input type="text" class="form-control" name="nombre" required 
                                           placeholder="Ej: Juan Pérez López">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email" required 
                                           placeholder="usuario@empresa.com">
                                </div>
                            </div>
                            
                            <!-- Fila 2: Credenciales y Permisos -->
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Contraseña</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" name="password" 
                                               required minlength="6" placeholder="Mínimo 6 caracteres" id="password">
                                        <button type="button" class="btn btn-outline-secondary" 
                                                onclick="togglePassword()" id="toggleBtn">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="form-text">
                                        <small>Debe tener al menos 6 caracteres</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Rol</label>
                                    <select class="form-select" name="rol_id" required>
                                        <option value="">Seleccionar Rol</option>
                                        <?php foreach ($roles as $rol): ?>
                                            <option value="<?php echo $rol['id']; ?>">
                                                <?php echo $rol['nombre']; ?>
                                                <?php if ($rol['es_sistema']): ?>
                                                    (Sistema)
                                                <?php endif; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times"></i> Cancelar
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-user-plus"></i> Crear Usuario
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-users"></i> Lista de Usuarios
                    <span class="badge bg-primary ms-2"><?php echo count($usuarios); ?> total</span>
                </h5>
                <div class="text-muted small">
                    <i class="fas fa-info-circle"></i> 
                    Gestiona usuarios y sus permisos
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead class="table-light">
                            <tr>
                                <th width="50">#</th>
                                <th>Usuario</th>
                                <th width="150">Rol</th>
                                <th width="100">Estado</th>
                                <th width="110">Registro</th>
                                <th width="120">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $usuario): ?>
                            <tr>
                                <td class="text-muted fw-bold"><?php echo $usuario['id']; ?></td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="fw-bold"><?php echo $usuario['nombre']; ?></span>
                                        <small class="text-muted"><?php echo $usuario['email']; ?></small>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($usuario['rol_nombre']): ?>
                                        <span class="badge bg-primary"><?php echo $usuario['rol_nombre']; ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Sin rol</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $usuario['activo'] ? 'success' : 'danger'; ?>">
                                        <i class="fas fa-<?php echo $usuario['activo'] ? 'check' : 'times'; ?>"></i>
                                        <?php echo $usuario['activo'] ? 'Activo' : 'Inactivo'; ?>
                                    </span>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?php echo date('d/m/Y', strtotime($usuario['fecha_creacion'])); ?>
                                    </small>
                                </td>
                                <td>
                                    <?php if (tienePermiso('usuarios_editar', 'actualizar') && $usuario['id'] != $_SESSION['usuario_id']): ?>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                                                <i class="fas fa-cog"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li>
                                                    <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#cambiarRolModal<?php echo $usuario['id']; ?>">
                                                        Cambiar Rol
                                                    </a>
                                                </li>
                                                <li>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="cambiar_estado">
                                                        <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                                                        <input type="hidden" name="nuevo_estado" value="<?php echo $usuario['activo'] ? '0' : '1'; ?>">
                                                        <button type="submit" class="dropdown-item">
                                                            <?php echo $usuario['activo'] ? 'Desactivar' : 'Activar'; ?>
                                                        </button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                        
                                        <!-- Modal para cambiar rol -->
                                        <div class="modal fade" id="cambiarRolModal<?php echo $usuario['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Cambiar Rol - <?php echo $usuario['nombre']; ?></h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form method="POST">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="action" value="cambiar_rol">
                                                            <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label">Nuevo Rol</label>
                                                                <select class="form-control" name="nuevo_rol_id" required>
                                                                    <option value="">Seleccionar Rol</option>
                                                                    <?php foreach ($roles as $rol): ?>
                                                                        <option value="<?php echo $rol['id']; ?>" 
                                                                                <?php echo $usuario['rol_id'] == $rol['id'] ? 'selected' : ''; ?>>
                                                                            <?php echo $rol['nombre']; ?>
                                                                            <?php if ($rol['es_sistema']): ?>
                                                                                (Sistema)
                                                                            <?php endif; ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                            <button type="submit" class="btn btn-primary">Cambiar Rol</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Estadísticas de Usuarios por Rol</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php
                            $stats_por_rol = [];
                            foreach ($usuarios as $usuario) {
                                $rol_nombre = $usuario['rol_nombre'] ?: 'Sin rol';
                                $stats_por_rol[$rol_nombre] = ($stats_por_rol[$rol_nombre] ?? 0) + 1;
                            }
                            
                            foreach ($stats_por_rol as $rol => $cantidad):
                            ?>
                                <div class="col-md-3 mb-3">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h4><?php echo $cantidad; ?></h4>
                                            <p class="mb-0"><?php echo $rol; ?></p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
<script>
// Función para mostrar/ocultar contraseña
function togglePassword() {
    const passwordInput = document.getElementById('password');
    const toggleBtn = document.getElementById('toggleBtn');
    const icon = toggleBtn.querySelector('i');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Limpiar formulario cuando se cierra el modal
document.getElementById('nuevoUsuarioModal').addEventListener('hidden.bs.modal', function () {
    this.querySelector('form').reset();
    // Resetear campo de contraseña a tipo password
    const passwordInput = document.getElementById('password');
    const toggleBtn = document.getElementById('toggleBtn');
    const icon = toggleBtn.querySelector('i');
    
    passwordInput.type = 'password';
    icon.classList.remove('fa-eye-slash');
    icon.classList.add('fa-eye');
});

// Agregar tooltips a los botones de acciones
document.addEventListener('DOMContentLoaded', function() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>

<?php include 'includes/layout_footer.php'; ?>