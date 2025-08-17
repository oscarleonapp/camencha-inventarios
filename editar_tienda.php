<?php
$titulo = "Editar Tienda - Sistema de Inventarios";
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'includes/csrf_protection.php';

verificarLogin();
verificarPermiso('tiendas_crear', 'actualizar');

$database = new Database();
$db = $database->getConnection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: lista_tiendas.php');
    exit;
}

// Cargar tienda
$stmt = $db->prepare("SELECT * FROM tiendas WHERE id = ?");
$stmt->execute([$id]);
$tienda = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$tienda) {
    header('Location: lista_tiendas.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'guardar') {
    validarCSRF();
    $nombre = trim($_POST['nombre'] ?? '');
    $activo = isset($_POST['activo']) ? 1 : 0;

    if ($nombre === '') {
        $error = 'El nombre es obligatorio';
    } else {
        try {
            $stmt_up = $db->prepare("UPDATE tiendas SET nombre = ?, activo = ? WHERE id = ?");
            $stmt_up->execute([$nombre, $activo, $id]);
            
            // Log de la actualización
            if (class_exists('Logger')) {
                require_once 'includes/logger.php';
                getLogger()->crud('actualizar', 'tiendas', 'tiendas', $id, $tienda, [
                    'nombre' => $nombre,
                    'activo' => $activo
                ]);
            }
            
            $success = 'Tienda actualizada correctamente';
            // refrescar datos
            $stmt->execute([$id]);
            $tienda = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $error = 'Error al actualizar: ' . $e->getMessage();
            
            // Log del error
            if (class_exists('Logger')) {
                require_once 'includes/logger.php';
                getLogger()->error('tienda_update_error', 'tiendas', "Error al actualizar tienda ID $id: " . $e->getMessage());
            }
        }
    }
}

include 'includes/layout_header.php';
?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-store"></i> Editar Tienda</h2>
        <a href="lista_tiendas.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
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

    <div class="card">
        <div class="card-header">
            <h5>Datos de la Tienda</h5>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="guardar">
                <?php echo campoCSRF(); ?>
                <div class="mb-3">
                    <label class="form-label">Nombre</label>
                    <input type="text" class="form-control" name="nombre" value="<?php echo htmlspecialchars($tienda['nombre']); ?>" required>
                </div>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    Solo se puede editar el nombre y estado de la tienda. Los campos adicionales se configurarán en futuras versiones.
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="activo" id="activo" <?php echo $tienda['activo'] ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="activo">Tienda activa</label>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar</button>
            </form>
        </div>
    </div>

<?php include 'includes/layout_footer.php'; ?>

