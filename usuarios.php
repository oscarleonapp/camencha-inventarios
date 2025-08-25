<?php
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'includes/csrf_protection.php';

verificarLogin();
verificarPermiso('usuarios_ver');

$database = new Database();
$db = $database->getConnection();

if ($_POST && isset($_POST['action'])) {
    if ($_POST['action'] == 'crear_usuario' && tienePermiso('usuarios_crear', 'crear')) {
        validarCSRF();
        
        $nombre = trim($_POST['nombre']);
        $email = trim($_POST['email']);
        $telefono = trim($_POST['telefono'] ?? '');
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $rol_id = $_POST['rol_id'];
        $tipo_comision = $_POST['tipo_comision'] ?? 'ninguna';
        $comision_porcentaje = $tipo_comision === 'porcentaje' ? floatval($_POST['comision_porcentaje'] ?? 0) : 0.00;
        $tiendas_asignadas = $_POST['tiendas_asignadas'] ?? [];
        $tienda_principal = $_POST['tienda_principal'] ?? null;
        
        try {
            $db->beginTransaction();
            
            $query = "INSERT INTO usuarios (nombre, email, telefono, password, rol_id, tipo_comision, comision_porcentaje) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            $stmt->execute([$nombre, $email, $telefono, $password, $rol_id, $tipo_comision, $comision_porcentaje]);
            
            $usuario_id = $db->lastInsertId();
            
            // Asignar tiendas
            if (!empty($tiendas_asignadas)) {
                $query_tienda = "INSERT INTO usuario_tiendas (usuario_id, tienda_id, es_principal) VALUES (?, ?, ?)";
                $stmt_tienda = $db->prepare($query_tienda);
                
                foreach ($tiendas_asignadas as $tienda_id) {
                    $es_principal = ($tienda_id == $tienda_principal) ? 1 : 0;
                    $stmt_tienda->execute([$usuario_id, $tienda_id, $es_principal]);
                }
            }
            
            $db->commit();
            $success = "Usuario creado exitosamente con tiendas asignadas";
            
        } catch (Exception $e) {
            $db->rollBack();
            $error = "Error al crear usuario: " . $e->getMessage();
        }
    }
    
    if ($_POST['action'] == 'cambiar_rol' && tienePermiso('usuarios_editar', 'actualizar')) {
        validarCSRF();
        
        $usuario_id = $_POST['usuario_id'];
        $nuevo_rol_id = $_POST['nuevo_rol_id'];
        
        $query = "UPDATE usuarios SET rol_id = ? WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$nuevo_rol_id, $usuario_id]);
        
        $success = "Rol actualizado exitosamente";
    }
    
    if ($_POST['action'] == 'actualizar_tiendas' && tienePermiso('usuarios_editar', 'actualizar')) {
        validarCSRF();
        
        $usuario_id = $_POST['usuario_id'];
        $tiendas_asignadas = $_POST['tiendas_asignadas'] ?? [];
        $tienda_principal = $_POST['tienda_principal'] ?? null;
        $tipo_comision = $_POST['tipo_comision'] ?? 'ninguna';
        $comision_porcentaje = $tipo_comision === 'porcentaje' ? floatval($_POST['comision_porcentaje'] ?? 0) : 0.00;
        
        try {
            $db->beginTransaction();
            
            // Actualizar información de comisión del usuario
            $query = "UPDATE usuarios SET tipo_comision = ?, comision_porcentaje = ? WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$tipo_comision, $comision_porcentaje, $usuario_id]);
            
            // Eliminar asignaciones actuales
            $query_delete = "DELETE FROM usuario_tiendas WHERE usuario_id = ?";
            $stmt_delete = $db->prepare($query_delete);
            $stmt_delete->execute([$usuario_id]);
            
            // Agregar nuevas asignaciones
            if (!empty($tiendas_asignadas)) {
                $query_insert = "INSERT INTO usuario_tiendas (usuario_id, tienda_id, es_principal) VALUES (?, ?, ?)";
                $stmt_insert = $db->prepare($query_insert);
                
                foreach ($tiendas_asignadas as $tienda_id) {
                    $es_principal = ($tienda_id == $tienda_principal) ? 1 : 0;
                    $stmt_insert->execute([$usuario_id, $tienda_id, $es_principal]);
                }
            }
            
            $db->commit();
            $success = "Tiendas y comisiones actualizadas exitosamente";
            
        } catch (Exception $e) {
            $db->rollBack();
            $error = "Error al actualizar asignaciones: " . $e->getMessage();
        }
    }
    
    if ($_POST['action'] == 'cambiar_estado' && tienePermiso('usuarios_editar', 'actualizar')) {
        validarCSRF();
        
        $usuario_id = $_POST['usuario_id'];
        $nuevo_estado = $_POST['nuevo_estado'];
        
        $query = "UPDATE usuarios SET activo = ? WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$nuevo_estado, $usuario_id]);
        
        $success = "Estado del usuario actualizado";
    }
}

// Filtros y búsqueda
$busqueda = isset($_GET['q']) ? trim($_GET['q']) : '';
$filtro_rol = isset($_GET['rol']) ? $_GET['rol'] : '';
$filtro_estado = isset($_GET['estado']) ? $_GET['estado'] : '';
$filtro_tienda = isset($_GET['tienda']) ? $_GET['tienda'] : '';
$filtro_comision = isset($_GET['comision']) ? $_GET['comision'] : '';

// Construir WHERE dinámico
$where_conditions = [];
$params_usuarios = [];

if (!empty($busqueda)) {
    $where_conditions[] = "(u.nombre LIKE ? OR u.email LIKE ?)";
    $params_usuarios[] = "%$busqueda%";
    $params_usuarios[] = "%$busqueda%";
}

if (!empty($filtro_rol)) {
    $where_conditions[] = "u.rol_id = ?";
    $params_usuarios[] = $filtro_rol;
}

if ($filtro_estado !== '') {
    $where_conditions[] = "u.activo = ?";
    $params_usuarios[] = (int)$filtro_estado;
}

if (!empty($filtro_tienda)) {
    $where_conditions[] = "ut.tienda_id = ?";
    $params_usuarios[] = $filtro_tienda;
}

if (!empty($filtro_comision)) {
    if ($filtro_comision === 'con_comision') {
        $where_conditions[] = "u.tipo_comision != 'ninguna'";
    } else if ($filtro_comision === 'sin_comision') {
        $where_conditions[] = "(u.tipo_comision = 'ninguna' OR u.tipo_comision IS NULL)";
    }
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

$query_usuarios = "SELECT u.*, r.nombre as rol_nombre,
                          GROUP_CONCAT(
                              CONCAT(t.nombre, IF(ut.es_principal, ' (Principal)', ''))
                              ORDER BY ut.es_principal DESC, t.nombre
                              SEPARATOR ', '
                          ) as tiendas_asignadas
                   FROM usuarios u 
                   LEFT JOIN roles r ON u.rol_id = r.id
                   LEFT JOIN usuario_tiendas ut ON u.id = ut.usuario_id
                   LEFT JOIN tiendas t ON ut.tienda_id = t.id AND t.activo = 1
                   $where_clause
                   GROUP BY u.id
                   ORDER BY u.created_at DESC";
$stmt_usuarios = $db->prepare($query_usuarios);
$stmt_usuarios->execute($params_usuarios);
$usuarios = $stmt_usuarios->fetchAll(PDO::FETCH_ASSOC);

// Contar total de usuarios (sin filtros) para mostrar estadísticas
$query_total = "SELECT COUNT(*) as total FROM usuarios";
$stmt_total = $db->prepare($query_total);
$stmt_total->execute();
$total_usuarios = $stmt_total->fetch(PDO::FETCH_ASSOC)['total'];

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

    <div class="d-flex justify-content-between align-items-center mb-4 rs-wrap-sm">
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
            <div class="modal-dialog modal-xl">
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
                                <div class="col-md-4">
                                    <label class="form-label">Nombre Completo</label>
                                    <input type="text" class="form-control" name="nombre" required 
                                           placeholder="Ej: Juan Pérez López">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email" required 
                                           placeholder="usuario@empresa.com">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Teléfono <small class="text-muted">(Opcional)</small></label>
                                    <input type="tel" class="form-control" name="telefono" 
                                           placeholder="+502 1234-5678">
                                </div>
                            </div>
                            
                            <!-- Fila 2: Credenciales y Rol -->
                            <div class="row g-3 mb-3">
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
                            
                            <!-- Sección: Comisiones -->
                            <div class="card mb-3">
                                <div class="card-header bg-light py-2">
                                    <h6 class="mb-0"><i class="fas fa-percentage"></i> Configuración de Comisiones</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Tipo de Comisión</label>
                                            <select class="form-select" name="tipo_comision" id="tipoComision" onchange="toggleComision()">
                                                <option value="ninguna">Sin Comisión</option>
                                                <option value="porcentaje">Porcentaje de Ventas</option>
                                                <option value="fija">Cantidad Fija (Futuro)</option>
                                            </select>
                                            <div class="form-text">
                                                <small>Seleccione si el usuario recibirá comisiones por ventas</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6" id="comisionPorcentajeDiv" style="display: none;">
                                            <label class="form-label">Porcentaje de Comisión (%)</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" name="comision_porcentaje" 
                                                       min="0" max="100" step="0.01" placeholder="0.00">
                                                <span class="input-group-text">%</span>
                                            </div>
                                            <div class="form-text">
                                                <small>Ejemplo: 5.50 para 5.50% de comisión sobre ventas</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Sección: Asignación de Tiendas -->
                            <div class="card mb-3">
                                <div class="card-header bg-light py-2">
                                    <h6 class="mb-0"><i class="fas fa-store"></i> Asignación de Tiendas</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col-12">
                                            <label class="form-label">Seleccionar Tiendas</label>
                                            <div class="form-text mb-3">
                                                <small><i class="fas fa-info-circle"></i> 
                                                Seleccione todas las tiendas a las que tendrá acceso este usuario</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <?php foreach ($tiendas as $tienda): ?>
                                        <div class="col-md-6 mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" 
                                                       name="tiendas_asignadas[]" value="<?php echo $tienda['id']; ?>"
                                                       id="nueva_tienda_<?php echo $tienda['id']; ?>">
                                                <label class="form-check-label" for="nueva_tienda_<?php echo $tienda['id']; ?>">
                                                    <strong><?php echo htmlspecialchars($tienda['nombre']); ?></strong>
                                                    <?php if (!empty($tienda['direccion'])): ?>
                                                        <br><small class="text-muted"><?php echo htmlspecialchars($tienda['direccion']); ?></small>
                                                    <?php endif; ?>
                                                </label>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <hr class="my-3">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <label class="form-label">Tienda Principal</label>
                                            <select class="form-select" name="tienda_principal" id="tiendaPrincipal">
                                                <option value="">Sin tienda principal</option>
                                                <?php foreach ($tiendas as $tienda): ?>
                                                <option value="<?php echo $tienda['id']; ?>">
                                                    <?php echo htmlspecialchars($tienda['nombre']); ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="form-text">
                                                <small><i class="fas fa-star text-warning"></i> 
                                                Tienda por defecto al iniciar sesión</small>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="alert alert-info p-2 mb-0 mt-4">
                                                <small>
                                                    <i class="fas fa-lightbulb"></i>
                                                    <strong>Tip:</strong> La tienda principal debe estar seleccionada arriba.
                                                </small>
                                            </div>
                                        </div>
                                    </div>
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
        
        <!-- Panel de Filtros y Búsqueda -->
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Buscar Usuario</label>
                        <div class="input-group">
                            <input type="text" class="form-control" name="q" 
                                   value="<?php echo htmlspecialchars($busqueda); ?>" 
                                   placeholder="Nombre o email...">
                            <button class="btn btn-outline-primary" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Rol</label>
                        <select class="form-select" name="rol">
                            <option value="">Todos los roles</option>
                            <?php foreach ($roles as $rol): ?>
                            <option value="<?php echo $rol['id']; ?>" 
                                    <?php echo $filtro_rol == $rol['id'] ? 'selected' : ''; ?>>
                                <?php echo $rol['nombre']; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Estado</label>
                        <select class="form-select" name="estado">
                            <option value="">Todos</option>
                            <option value="1" <?php echo $filtro_estado === '1' ? 'selected' : ''; ?>>Activos</option>
                            <option value="0" <?php echo $filtro_estado === '0' ? 'selected' : ''; ?>>Inactivos</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Tienda</label>
                        <select class="form-select" name="tienda">
                            <option value="">Todas las tiendas</option>
                            <?php foreach ($tiendas as $tienda): ?>
                            <option value="<?php echo $tienda['id']; ?>" 
                                    <?php echo $filtro_tienda == $tienda['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($tienda['nombre']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Comisiones</label>
                        <select class="form-select" name="comision">
                            <option value="">Todos</option>
                            <option value="con_comision" <?php echo $filtro_comision === 'con_comision' ? 'selected' : ''; ?>>Con comisión</option>
                            <option value="sin_comision" <?php echo $filtro_comision === 'sin_comision' ? 'selected' : ''; ?>>Sin comisión</option>
                        </select>
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <div class="btn-group w-100 rs-wrap-sm">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i>
                            </button>
                            <?php if (!empty($busqueda) || !empty($filtro_rol) || $filtro_estado !== '' || !empty($filtro_tienda) || !empty($filtro_comision)): ?>
                            <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center rs-wrap-sm">
                <h5 class="mb-0">
                    <i class="fas fa-users"></i> Lista de Usuarios
                    <span class="badge bg-primary ms-2"><?php echo count($usuarios); ?> resultados</span>
                    <span class="badge bg-secondary ms-1">de <?php echo $total_usuarios; ?> total</span>
                </h5>
                <div class="text-muted small">
                    <i class="fas fa-info-circle"></i> 
                    <?php if (!empty($busqueda) || !empty($filtro_rol) || $filtro_estado !== '' || !empty($filtro_tienda) || !empty($filtro_comision)): ?>
                        Filtros activos
                    <?php else: ?>
                        Gestiona usuarios y sus permisos
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                        <div class="table-responsive-md">
                    <table class="table table-hover table-sm accessibility-fix">
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
                                        <?php echo date('d/m/Y', strtotime($usuario['created_at'])); ?>
                                    </small>
                                </td>
                                <td>
                                    <?php if (tienePermiso('usuarios_editar', 'actualizar') && $usuario['id'] != $_SESSION['usuario_id']): ?>
                                        <div class="btn-group rs-wrap-sm">
                                            <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                                                <i class="fas fa-cog"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li>
                                                    <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#cambiarRolModal<?php echo $usuario['id']; ?>">
                                                        <i class="fas fa-user-tag"></i> Cambiar Rol
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#gestionarTiendasModal<?php echo $usuario['id']; ?>">
                                                        <i class="fas fa-store"></i> Gestionar Tiendas
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="cambiar_estado">
                                                        <?php echo campoCSRF(); ?>
                                                        <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                                                        <input type="hidden" name="nuevo_estado" value="<?php echo $usuario['activo'] ? '0' : '1'; ?>">
                                                        <button type="submit" class="dropdown-item text-<?php echo $usuario['activo'] ? 'danger' : 'success'; ?>">
                                                            <i class="fas fa-<?php echo $usuario['activo'] ? 'times' : 'check'; ?>"></i>
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
                                                            <?php echo campoCSRF(); ?>
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
                            
                            <?php if (empty($usuarios)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="fas fa-search fa-2x mb-2"></i>
                                        <p class="mb-0">
                                            <?php if (!empty($busqueda) || !empty($filtro_rol) || $filtro_estado !== '' || !empty($filtro_tienda) || !empty($filtro_comision)): ?>
                                                No se encontraron usuarios con los filtros aplicados
                                            <?php else: ?>
                                                No hay usuarios registrados
                                            <?php endif; ?>
                                        </p>
                                        <?php if (!empty($busqueda) || !empty($filtro_rol) || $filtro_estado !== '' || !empty($filtro_tienda) || !empty($filtro_comision)): ?>
                                        <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-sm btn-outline-primary mt-2">
                                            <i class="fas fa-times"></i> Limpiar filtros
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <?php if (!empty($usuarios)): ?>
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5>
                            <i class="fas fa-chart-bar"></i> 
                            <?php if (!empty($busqueda) || !empty($filtro_rol) || $filtro_estado !== '' || !empty($filtro_tienda) || !empty($filtro_comision)): ?>
                                Estadísticas de Resultados Filtrados
                            <?php else: ?>
                                Estadísticas de Usuarios por Rol
                            <?php endif; ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php
                            $stats_por_rol = [];
                            $users_con_comision = 0;
                            $users_con_tiendas = 0;
                            
                            foreach ($usuarios as $usuario) {
                                $rol_nombre = $usuario['rol_nombre'] ?: 'Sin rol';
                                $stats_por_rol[$rol_nombre] = ($stats_por_rol[$rol_nombre] ?? 0) + 1;
                                
                                if (($usuario['tipo_comision'] ?? 'ninguna') !== 'ninguna') {
                                    $users_con_comision++;
                                }
                                
                                if (!empty($usuario['tiendas_asignadas'])) {
                                    $users_con_tiendas++;
                                }
                            }
                            
                            // Estadísticas por rol
                            foreach ($stats_por_rol as $rol => $cantidad):
                            ?>
                                <div class="col-md-3 mb-3">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h4 class="text-primary"><?php echo $cantidad; ?></h4>
                                            <p class="mb-0"><?php echo $rol; ?></p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Estadísticas adicionales -->
                        <div class="row mt-3">
                            <div class="col-md-4">
                                <div class="card border-success">
                                    <div class="card-body text-center">
                                        <h5 class="text-success"><?php echo $users_con_comision; ?></h5>
                                        <small class="text-muted">Con comisiones configuradas</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border-info">
                                    <div class="card-body text-center">
                                        <h5 class="text-info"><?php echo $users_con_tiendas; ?></h5>
                                        <small class="text-muted">Con tiendas asignadas</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border-warning">
                                    <div class="card-body text-center">
                                        <h5 class="text-warning"><?php echo count($usuarios) - count(array_filter($usuarios, function($u) { return $u['activo']; })); ?></h5>
                                        <small class="text-muted">Usuarios inactivos</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Modales para Gestionar Tiendas -->
        <?php foreach ($usuarios as $usuario): ?>
            <?php if (tienePermiso('usuarios_editar', 'actualizar') && $usuario['id'] != $_SESSION['usuario_id']): ?>
            <!-- Modal para gestionar tiendas del usuario <?php echo $usuario['id']; ?> -->
            <div class="modal fade" id="gestionarTiendasModal<?php echo $usuario['id']; ?>" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-store"></i> 
                                Gestionar Tiendas y Comisiones - <?php echo htmlspecialchars($usuario['nombre']); ?>
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form method="POST">
                            <div class="modal-body">
                                <input type="hidden" name="action" value="actualizar_tiendas">
                                <?php echo campoCSRF(); ?>
                                <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                                
                                <!-- Configuración de Comisiones -->
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label">Tipo de Comisión</label>
                                        <select class="form-select" name="tipo_comision" 
                                                id="tipoComisionEdit<?php echo $usuario['id']; ?>"
                                                onchange="toggleComisionEdit(<?php echo $usuario['id']; ?>)">
                                            <option value="ninguna" <?php echo ($usuario['tipo_comision'] ?? 'ninguna') === 'ninguna' ? 'selected' : ''; ?>>Sin Comisión</option>
                                            <option value="porcentaje" <?php echo ($usuario['tipo_comision'] ?? '') === 'porcentaje' ? 'selected' : ''; ?>>Porcentaje de Ventas</option>
                                            <option value="fija" <?php echo ($usuario['tipo_comision'] ?? '') === 'fija' ? 'selected' : ''; ?>>Cantidad Fija (Futuro)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6" id="comisionEdit<?php echo $usuario['id']; ?>" 
                                         style="display: <?php echo ($usuario['tipo_comision'] ?? 'ninguna') === 'porcentaje' ? 'block' : 'none'; ?>;">
                                        <label class="form-label">Porcentaje de Comisión (%)</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" name="comision_porcentaje" 
                                                   min="0" max="100" step="0.01"
                                                   value="<?php echo $usuario['comision_porcentaje'] ?? '0.00'; ?>" 
                                                   placeholder="0.00">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <hr>
                                
                                <!-- Asignación de Tiendas -->
                                <div class="mb-3">
                                    <label class="form-label">Tiendas Asignadas</label>
                                    <div class="card">
                                        <div class="card-body p-3">
                                            <?php
                                            // Obtener tiendas actuales del usuario
                                            $query_user_stores = "SELECT ut.tienda_id, ut.es_principal 
                                                                   FROM usuario_tiendas ut 
                                                                   WHERE ut.usuario_id = ?";
                                            $stmt_user_stores = $db->prepare($query_user_stores);
                                            $stmt_user_stores->execute([$usuario['id']]);
                                            $tiendas_usuario = [];
                                            $tienda_principal_actual = null;
                                            while ($row = $stmt_user_stores->fetch(PDO::FETCH_ASSOC)) {
                                                $tiendas_usuario[] = $row['tienda_id'];
                                                if ($row['es_principal']) {
                                                    $tienda_principal_actual = $row['tienda_id'];
                                                }
                                            }
                                            ?>
                                            <div class="row">
                                                <?php foreach ($tiendas as $tienda): ?>
                                                <div class="col-md-4 mb-2">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" 
                                                               name="tiendas_asignadas[]" value="<?php echo $tienda['id']; ?>"
                                                               id="edit_tienda_<?php echo $usuario['id']; ?>_<?php echo $tienda['id']; ?>"
                                                               <?php echo in_array($tienda['id'], $tiendas_usuario) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" 
                                                               for="edit_tienda_<?php echo $usuario['id']; ?>_<?php echo $tienda['id']; ?>"
                                                              >
                                                            <?php echo htmlspecialchars($tienda['nombre']); ?>
                                                            <?php if ($tienda['id'] == $tienda_principal_actual): ?>
                                                                <span class="badge bg-warning text-dark text-fix">Principal</span>
                                                            <?php endif; ?>
                                                        </label>
                                                    </div>
                                                </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <hr class="my-3">
                                            <div class="mb-0">
                                                <label class="form-label mb-2">Tienda Principal</label>
                                                <select class="form-select" name="tienda_principal">
                                                    <option value="">Sin tienda principal</option>
                                                    <?php foreach ($tiendas as $tienda): ?>
                                                    <option value="<?php echo $tienda['id']; ?>"
                                                            <?php echo $tienda['id'] == $tienda_principal_actual ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($tienda['nombre']); ?>
                                                    </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <div class="form-text">
                                                    <small><i class="fas fa-info-circle"></i> 
                                                    La tienda principal debe estar seleccionada arriba</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Actualizar Asignaciones
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        <?php endforeach; ?>
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

// Función para mostrar/ocultar campo de comisión en formulario de crear
function toggleComision() {
    const tipoComision = document.getElementById('tipoComision').value;
    const comisionDiv = document.getElementById('comisionPorcentajeDiv');
    
    if (tipoComision === 'porcentaje') {
        comisionDiv.style.display = 'block';
        comisionDiv.querySelector('input').required = true;
    } else {
        comisionDiv.style.display = 'none';
        comisionDiv.querySelector('input').required = false;
        comisionDiv.querySelector('input').value = '';
    }
}

// Función para mostrar/ocultar campo de comisión en modal de edición
function toggleComisionEdit(userId) {
    const select = document.getElementById(`tipoComisionEdit${userId}`);
    const comisionDiv = document.getElementById(`comisionEdit${userId}`);
    
    if (!select || !comisionDiv) {
        console.error('No se encontraron elementos para usuario:', userId);
        return;
    }
    
    if (select.value === 'porcentaje') {
        comisionDiv.style.display = 'block';
    } else {
        comisionDiv.style.display = 'none';
        const input = comisionDiv.querySelector('input[name="comision_porcentaje"]');
        if (input) {
            input.value = '0.00';
        }
    }
}

// Validar que la tienda principal esté seleccionada
function validateStoreSelection(formElement) {
    const checkedStores = formElement.querySelectorAll('input[name="tiendas_asignadas[]"]:checked');
    const principalStore = formElement.querySelector('select[name="tienda_principal"]').value;
    
    if (principalStore && checkedStores.length === 0) {
        alert('Debe seleccionar al menos una tienda si especifica una tienda principal');
        return false;
    }
    
    if (principalStore) {
        const selectedStoreIds = Array.from(checkedStores).map(cb => cb.value);
        if (!selectedStoreIds.includes(principalStore)) {
            alert('La tienda principal debe estar seleccionada en la lista de tiendas asignadas');
            return false;
        }
    }
    
    return true;
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
    
    // Ocultar campo de comisión
    const comisionDiv = document.getElementById('comisionPorcentajeDiv');
    comisionDiv.style.display = 'none';
});

// Función para aplicar filtros automáticamente
function autoSubmitFilter(element) {
    // Enviar formulario automáticamente cuando cambian los selects
    element.form.submit();
}

// Validar formularios antes del envío
document.addEventListener('DOMContentLoaded', function() {
    // Validar formulario de crear usuario
    const createForm = document.querySelector('#nuevoUsuarioModal form');
    if (createForm) {
        createForm.addEventListener('submit', function(e) {
            if (!validateStoreSelection(this)) {
                e.preventDefault();
            }
        });
    }
    
    // Validar formularios de edición de tiendas
    const editForms = document.querySelectorAll('form');
    editForms.forEach(form => {
        const actionInput = form.querySelector('input[name="action"]');
        if (actionInput && actionInput.value === 'actualizar_tiendas') {
            form.addEventListener('submit', function(e) {
                if (!validateStoreSelection(this)) {
                    e.preventDefault();
                }
            });
        }
    });
    
    // Auto-envío de filtros cuando cambian los selects
    document.querySelectorAll('select[name="rol"], select[name="estado"], select[name="tienda"], select[name="comision"]').forEach(select => {
        select.addEventListener('change', function() {
            autoSubmitFilter(this);
        });
    });
    
    // Búsqueda con Enter
    const searchInput = document.querySelector('input[name="q"]');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.form.submit();
            }
        });
    }
    
    // Limpiar modales de gestión de tiendas al cerrar
    document.querySelectorAll('[id^="gestionarTiendasModal"]').forEach(modal => {
        modal.addEventListener('hidden.bs.modal', function() {
            // Resetear formulario
            const form = this.querySelector('form');
            if (form) {
                // No resetear completamente, solo limpiar estados de error
                form.querySelectorAll('.is-invalid').forEach(el => {
                    el.classList.remove('is-invalid');
                });
                form.querySelectorAll('.invalid-feedback').forEach(el => {
                    el.remove();
                });
            }
        });
    });
    
    // Agregar tooltips a los botones de acciones
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>

<?php include 'includes/layout_footer.php'; ?>
