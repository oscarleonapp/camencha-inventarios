<?php
$titulo = "Lista de Tiendas - Sistema de Inventarios";
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'includes/csrf_protection.php';

verificarLogin();
verificarPermiso('tiendas_ver');

$database = new Database();
$db = $database->getConnection();

// Filtros
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$estado = isset($_GET['estado']) ? $_GET['estado'] : '1';

// Acciones de activar/desactivar y eliminar
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['id'])) {
    verificarPermiso('tiendas_crear', 'actualizar');
    $id = (int)$_POST['id'];
    
    if ($_POST['action'] === 'toggle_activa') {
        $stmt = $db->prepare("UPDATE tiendas SET activo = 1 - activo WHERE id = ?");
        $stmt->execute([$id]);
        $success = "Estado de la tienda actualizado correctamente";
        
    } elseif ($_POST['action'] === 'eliminar') {
        verificarPermiso('tiendas_crear', 'eliminar');
        
        try {
            $db->beginTransaction();
            
            // Verificar si la tienda tiene inventarios asociados
            $stmt_check = $db->prepare("SELECT COUNT(*) as total FROM inventarios WHERE tienda_id = ?");
            $stmt_check->execute([$id]);
            $inventarios = $stmt_check->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Verificar si la tienda tiene ventas asociadas
            $stmt_check_ventas = $db->prepare("SELECT COUNT(*) as total FROM ventas WHERE tienda_id = ?");
            $stmt_check_ventas->execute([$id]);
            $ventas = $stmt_check_ventas->fetch(PDO::FETCH_ASSOC)['total'];
            
            if ($inventarios > 0 || $ventas > 0) {
                throw new Exception("No se puede eliminar la tienda porque tiene inventarios o ventas asociados. Total inventarios: $inventarios, Total ventas: $ventas");
            }
            
            // Eliminar la tienda
            $stmt_delete = $db->prepare("DELETE FROM tiendas WHERE id = ?");
            $stmt_delete->execute([$id]);
            
            $db->commit();
            $success = "Tienda eliminada correctamente";
            
        } catch (Exception $e) {
            $db->rollBack();
            $error = "Error al eliminar la tienda: " . $e->getMessage();
        }
    }
}

$where = [];
$params = [];
if ($estado === '0' || $estado === '1') {
    $where[] = 't.activo = ?';
    $params[] = (int)$estado;
}
if ($q !== '') {
    $where[] = 't.nombre LIKE ?';
    $params[] = "%$q%";
}
$where_clause = count($where) ? ('WHERE ' . implode(' AND ', $where)) : '';

$query_tiendas = "SELECT t.*, t.created_at as fecha_creacion
                  FROM tiendas t
                  $where_clause
                  ORDER BY t.created_at DESC";
$stmt_tiendas = $db->prepare($query_tiendas);
$stmt_tiendas->execute($params);
$tiendas = $stmt_tiendas->fetchAll(PDO::FETCH_ASSOC);

include 'includes/layout_header.php';
?>

    <div class="d-flex justify-content-between align-items-center mb-4 rs-wrap-sm">
        <h2><i class="fas fa-store"></i> Lista de Tiendas</h2>
        <?php if (tienePermiso('tiendas_crear', 'crear')): ?>
        <a href="nueva_tienda.php" class="btn btn-primary"><i class="fas fa-plus"></i> Nueva Tienda</a>
        <?php endif; ?>
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

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0">Tiendas</h5>
            <form class="d-flex gap-2" method="GET" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                <input type="text" name="q" class="form-control form-control-sm" placeholder="Buscar por nombre" value="<?php echo htmlspecialchars($q); ?>">
                <select name="estado" class="form-select form-select-sm">
                    <option value="">Todas</option>
                    <option value="1" <?php echo $estado==='1'? 'selected':''; ?>>Activas</option>
                    <option value="0" <?php echo $estado==='0'? 'selected':''; ?>>Inactivas</option>
                </select>
                <button class="btn btn-sm btn-primary" type="submit"><i class="fas fa-search"></i></button>
                <?php if ($q !== '' || $estado !== '1'): ?>
                    <a class="btn btn-sm btn-outline-secondary" href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?estado=1">Limpiar</a>
                <?php endif; ?>
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive-md">
                <table class="table table-striped accessibility-fix">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Dirección</th>
                            <th>Teléfono</th>
                            <th>Encargado</th>
                            <th>Fecha Creación</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tiendas as $tienda): ?>
                        <tr>
                            <td><?php echo $tienda['id']; ?></td>
                            <td><?php echo htmlspecialchars($tienda['nombre']); ?></td>
                            <td><span class="text-muted">No configurado</span></td>
                            <td><span class="text-muted">No configurado</span></td>
                            <td><span class="text-muted">No configurado</span></td>
                            <td><?php echo date('d/m/Y', strtotime($tienda['fecha_creacion'])); ?></td>
                            <td>
                                <?php if ($tienda['activo']): ?>
                                    <span class="badge bg-success">Activa</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inactiva</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="editar_tienda.php?id=<?php echo $tienda['id']; ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php if (tienePermiso('tiendas_crear', 'actualizar')): ?>
                                <form method="POST" style="display:inline-block">
                                    <?php echo campoCSRF(); ?>
                                    <input type="hidden" name="action" value="toggle_activa">
                                    <input type="hidden" name="id" value="<?php echo $tienda['id']; ?>">
                                    <button type="submit" class="btn btn-sm <?php echo $tienda['activo'] ? 'btn-outline-warning' : 'btn-outline-success'; ?>">
                                        <i class="fas <?php echo $tienda['activo'] ? 'fa-ban' : 'fa-check'; ?>"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                                
                                <?php if (tienePermiso('tiendas_crear', 'eliminar')): ?>
                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                        onclick="confirmarEliminacion(<?php echo $tienda['id']; ?>, '<?php echo addslashes($tienda['nombre']); ?>')">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (count($tiendas) === 0): ?>
                        <tr><td colspan="8" class="text-center text-muted">No hay tiendas registradas</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Formulario oculto para eliminación -->
    <form id="form-eliminar" method="POST" style="display: none;">
        <?php echo campoCSRF(); ?>
        <input type="hidden" name="action" value="eliminar">
        <input type="hidden" name="id" id="tienda-id-eliminar">
    </form>

    <script>
    function confirmarEliminacion(tiendaId, nombreTienda) {
        const mensaje = `¿Está seguro que desea eliminar la tienda "${nombreTienda}"?\n\n` +
                       `Esta acción NO se puede deshacer.\n\n` +
                       `ADVERTENCIA: Solo se puede eliminar si no tiene inventarios o ventas asociados.`;
        
        if (confirm(mensaje)) {
            // Mostrar confirmación adicional para mayor seguridad
            const confirmacion = prompt(`Para confirmar, escriba "ELIMINAR" (en mayúsculas):`);
            
            if (confirmacion === "ELIMINAR") {
                document.getElementById('tienda-id-eliminar').value = tiendaId;
                document.getElementById('form-eliminar').submit();
            } else if (confirmacion !== null) {
                alert('Eliminación cancelada. Debe escribir exactamente "ELIMINAR" para confirmar.');
            }
        }
    }
    </script>

<?php include 'includes/layout_footer.php'; ?>
