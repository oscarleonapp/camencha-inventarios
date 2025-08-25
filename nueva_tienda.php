<?php
$titulo = "Nueva Tienda - Sistema de Inventarios";
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'includes/csrf_protection.php';

verificarLogin();
verificarPermiso('tiendas_crear', 'crear');

$database = new Database();
$db = $database->getConnection();

// Encargados serán configurados en futuras versiones

if ($_POST && isset($_POST['action']) && $_POST['action'] === 'crear_tienda') {
    validarCSRF();
    $nombre = trim($_POST['nombre'] ?? '');

    if ($nombre === '') {
        $error = 'El nombre de la tienda es obligatorio';
    } else {
        try {
            $stmt = $db->prepare("INSERT INTO tiendas (nombre, activo) VALUES (?, 1)");
            $stmt->execute([$nombre]);
            $success = 'Tienda creada exitosamente';
        } catch (Exception $e) {
            $error = 'Error al crear tienda: ' . $e->getMessage();
        }
    }
}

include 'includes/layout_header.php';
?>

    <div class="d-flex justify-content-between align-items-center mb-4 rs-wrap-sm">
        <h2><i class="fas fa-store"></i> Nueva Tienda</h2>
        <a href="lista_tiendas.php" class="btn btn-outline-secondary"><i class="fas fa-list"></i> Lista de Tiendas</a>
    </div>

    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h5>Datos de la Tienda</h5>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="crear_tienda">
                <?php echo campoCSRF(); ?>
                <div class="mb-3">
                    <label class="form-label">Nombre de la Tienda</label>
                    <input type="text" class="form-control" name="nombre" required>
                </div>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    Solo se requiere el nombre de la tienda. Los campos adicionales se configurarán en futuras versiones.
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Crear Tienda</button>
            </form>
        </div>
    </div>

<?php include 'includes/layout_footer.php'; ?>
