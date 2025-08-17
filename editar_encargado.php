<?php
$titulo = "Editar Encargado - Sistema de Inventarios";
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'includes/csrf_protection.php';

verificarLogin();
verificarPermiso('tiendas_crear', 'actualizar');

$database = new Database();
$db = $database->getConnection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { header('Location: lista_encargados.php'); exit; }

$stmt = $db->prepare("SELECT id, nombre, email, activo FROM usuarios WHERE id = ? AND rol = 'encargado'");
$stmt->execute([$id]);
$enc = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$enc) { header('Location: lista_encargados.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'guardar') {
    validarCSRF();
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $activo = isset($_POST['activo']) ? 1 : 0;
    $newpass = $_POST['password'] ?? '';

    if ($nombre === '' || $email === '') {
        $error = 'Nombre y email son obligatorios';
    } else {
        try {
            if ($newpass !== '') {
                $hash = password_hash($newpass, PASSWORD_DEFAULT);
                $upd = $db->prepare("UPDATE usuarios SET nombre = ?, email = ?, password = ?, activo = ? WHERE id = ? AND rol = 'encargado'");
                $upd->execute([$nombre, $email, $hash, $activo, $id]);
            } else {
                $upd = $db->prepare("UPDATE usuarios SET nombre = ?, email = ?, activo = ? WHERE id = ? AND rol = 'encargado'");
                $upd->execute([$nombre, $email, $activo, $id]);
            }
            $success = 'Encargado actualizado';
            $stmt->execute([$id]);
            $enc = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $error = 'Error al actualizar: ' . $e->getMessage();
        }
    }
}

include 'includes/layout_header.php';
?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-user"></i> Editar Encargado</h2>
        <a href="lista_encargados.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
    </div>

    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header"><h5>Datos del Encargado</h5></div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="guardar">
                <?php echo campoCSRF(); ?>
                <div class="mb-3">
                    <label class="form-label">Nombre</label>
                    <input class="form-control" name="nombre" value="<?php echo htmlspecialchars($enc['nombre']); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input class="form-control" type="email" name="email" value="<?php echo htmlspecialchars($enc['email']); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nueva contraseña (opcional)</label>
                    <input class="form-control" type="password" name="password" placeholder="Dejar en blanco para mantener">
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="activo" name="activo" <?php echo $enc['activo'] ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="activo">Activo</label>
                </div>
                <button class="btn btn-primary" type="submit"><i class="fas fa-save"></i> Guardar</button>
            </form>
        </div>
    </div>

<?php include 'includes/layout_footer.php'; ?>

