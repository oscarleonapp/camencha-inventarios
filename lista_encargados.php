<?php
$titulo = "Lista de Encargados - Sistema de Inventarios";
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'includes/csrf_protection.php';

verificarLogin();
verificarPermiso('tiendas_ver');

$database = new Database();
$db = $database->getConnection();

// Filtros y acciones
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$estado = isset($_GET['estado']) ? $_GET['estado'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['id'])) {
    validarCSRF();
    verificarPermiso('tiendas_crear', 'actualizar');
    $id = (int)$_POST['id'];
    if ($_POST['action'] === 'toggle_activo') {
        $stmt = $db->prepare("UPDATE usuarios SET activo = 1 - activo WHERE id = ? AND rol = 'encargado'");
        $stmt->execute([$id]);
    }
}

$where = ["rol = 'encargado'"];
$params = [];
if ($estado === '0' || $estado === '1') {
    $where[] = 'activo = ?';
    $params[] = (int)$estado;
}
if ($q !== '') {
    $where[] = '(nombre LIKE ? OR email LIKE ?)';
    $params[] = "%$q%";
    $params[] = "%$q%";
}
$where_clause = 'WHERE ' . implode(' AND ', $where);

$stmt = $db->prepare("SELECT id, nombre, email, created_at as fecha_creacion, activo FROM usuarios $where_clause ORDER BY nombre");
$stmt->execute($params);
$encargados = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/layout_header.php';
?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-users"></i> Lista de Encargados</h2>
        <?php if (tienePermiso('tiendas_crear', 'crear')): ?>
        <a href="nuevo_encargado.php" class="btn btn-success"><i class="fas fa-user-plus"></i> Nuevo Encargado</a>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0">Encargados registrados</h5>
            <form class="d-flex gap-2" method="GET" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                <input type="text" name="q" class="form-control form-control-sm" placeholder="Buscar nombre o email" value="<?php echo htmlspecialchars($q); ?>">
                <select name="estado" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <option value="1" <?php echo $estado==='1'?'selected':''; ?>>Activos</option>
                    <option value="0" <?php echo $estado==='0'?'selected':''; ?>>Inactivos</option>
                </select>
                <button class="btn btn-sm btn-primary" type="submit"><i class="fas fa-search"></i></button>
                <?php if ($q !== '' || $estado !== ''): ?>
                    <a class="btn btn-sm btn-outline-secondary" href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">Limpiar</a>
                <?php endif; ?>
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Estado</th>
                            <th>Fecha creación</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($encargados as $enc): ?>
                        <tr>
                            <td><?php echo $enc['id']; ?></td>
                            <td><?php echo htmlspecialchars($enc['nombre']); ?></td>
                            <td><?php echo htmlspecialchars($enc['email']); ?></td>
                            <td>
                                <?php if ($enc['activo']): ?>
                                    <span class="badge bg-success">Activo</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($enc['fecha_creacion'])); ?></td>
                            <td>
                                <a href="editar_encargado.php?id=<?php echo $enc['id']; ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php if (tienePermiso('tiendas_crear', 'actualizar')): ?>
                                <form method="POST" style="display:inline-block">
                                    <?php echo campoCSRF(); ?>
                                    <input type="hidden" name="action" value="toggle_activo">
                                    <input type="hidden" name="id" value="<?php echo $enc['id']; ?>">
                                    <button type="submit" class="btn btn-sm <?php echo $enc['activo'] ? 'btn-outline-warning' : 'btn-outline-success'; ?>">
                                        <i class="fas <?php echo $enc['activo'] ? 'fa-ban' : 'fa-check'; ?>"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (count($encargados) === 0): ?>
                        <tr><td colspan="6" class="text-center text-muted">No hay encargados registrados</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php include 'includes/layout_footer.php'; ?>
