<?php
$titulo = "Nuevo Encargado - Sistema de Inventarios";
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'includes/csrf_protection.php';

verificarLogin();
// Usamos permiso de crear tiendas para gestionar encargados asociados
verificarPermiso('tiendas_crear', 'crear');

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'crear_encargado') {
    validarCSRF();
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password_raw = $_POST['password'] ?? '';

    if ($nombre === '' || $email === '' || $password_raw === '') {
        $error = 'Todos los campos son obligatorios';
    } else {
        try {
            $password = password_hash($password_raw, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO usuarios (nombre, email, password, rol, activo) VALUES (?, ?, ?, 'encargado', 1)");
            $stmt->execute([$nombre, $email, $password]);
            $success = 'Encargado creado exitosamente';
        } catch (Exception $e) {
            $error = 'Error al crear encargado: ' . $e->getMessage();
        }
    }
}

include 'includes/layout_header.php';
?>

    <div class="d-flex justify-content-between align-items-center mb-4 rs-wrap-sm">
        <h2><i class="fas fa-user-plus"></i> Nuevo Encargado</h2>
        <a href="lista_encargados.php" class="btn btn-outline-secondary"><i class="fas fa-users"></i> Lista de Encargados</a>
    </div>

    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h5>Datos del Encargado</h5>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="crear_encargado">
                <?php echo campoCSRF(); ?>
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
                <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Crear Encargado</button>
            </form>
        </div>
    </div>

<?php include 'includes/layout_footer.php'; ?>
