<?php
$titulo = "Gestión de Tiendas - Sistema de Inventarios";
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'includes/config_functions.php';

verificarLogin();
verificarPermiso('tiendas_ver');

$database = new Database();
$db = $database->getConnection();

if ($_POST && isset($_POST['action'])) {
    if ($_POST['action'] == 'crear_tienda') {
        $nombre = $_POST['nombre'];
        
        $query = "INSERT INTO tiendas (nombre, activo) VALUES (?, 1)";
        $stmt = $db->prepare($query);
        $stmt->execute([$nombre]);
        
        $success = "Tienda creada exitosamente";
    }
    
    if ($_POST['action'] == 'crear_encargado') {
        $nombre = $_POST['nombre'];
        $email = $_POST['email'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        
        $query = "INSERT INTO usuarios (nombre, email, password, rol) VALUES (?, ?, ?, 'encargado')";
        $stmt = $db->prepare($query);
        $stmt->execute([$nombre, $email, $password]);
        
        $success = "Encargado creado exitosamente";
    }
}

$query_tiendas = "SELECT t.*
                  FROM tiendas t 
                  WHERE t.activo = 1 
                  ORDER BY t.fecha_creacion DESC";
$stmt_tiendas = $db->prepare($query_tiendas);
$stmt_tiendas->execute();
$tiendas = $stmt_tiendas->fetchAll(PDO::FETCH_ASSOC);

// Encargados serán configurados en futuras versiones

include 'includes/layout_header.php';
?>

    <div class="d-flex justify-content-between align-items-center mb-4 rs-wrap-sm">
        <h2><i class="fas fa-store"></i> <span class="editable" data-label="tiendas_titulo">Gestión de Tiendas y Encargados</span></h2>
        <?php if (tienePermiso('tiendas_crear', 'crear')): ?>
        <div class="btn-group rs-wrap-sm">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#nuevaTiendaModal">
                <i class="fas fa-plus"></i> Nueva Tienda
            </button>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#nuevoEncargadoModal">
                <i class="fas fa-user-plus"></i> Nuevo Encargado
            </button>
        </div>
        <?php endif; ?>
    </div>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Nueva Tienda</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="crear_tienda">
                            <div class="mb-3">
                                <label class="form-label">Nombre de la Tienda</label>
                                <input type="text" class="form-control" name="nombre" required>
                            </div>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                Solo se requiere el nombre de la tienda. Los campos adicionales se configurarán en futuras versiones.
                            </div>
                            <button type="submit" class="btn btn-primary">Crear Tienda</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Nuevo Encargado</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="crear_encargado">
                            <div class="mb-3">
                                <label class="form-label">Nombre</label>
                                <input type="text" class="form-control" name="nombre" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Contraseña</label>
                                <input type="password" class="form-control" name="password" required>
                            </div>
                            <button type="submit" class="btn btn-success">Crear Encargado</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Lista de Tiendas</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive-md">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre</th>
                                        <th>Estado</th>
                                        <th>Fecha Creación</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tiendas as $tienda): ?>
                                    <tr>
                                        <td><?php echo $tienda['id']; ?></td>
                                        <td><?php echo htmlspecialchars($tienda['nombre']); ?></td>
                                        <td>
                                            <?php if ($tienda['activo']): ?>
                                                <span class="badge bg-success">Activa</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Inactiva</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($tienda['fecha_creacion'])); ?></td>
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
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
