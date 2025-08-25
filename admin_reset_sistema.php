<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/config_functions.php';
require_once 'includes/csrf_protection.php';

// Verificar que sea administrador
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header('Location: sin_permisos.php');
    exit;
}

// Verificar permisos específicos de sistema (solo admins pueden hacer reset)
if (!esAdmin()) {
    header('Location: sin_permisos.php');
    exit;
}

$config = cargarConfiguracion();
$message = '';
$error = '';

// Procesar reset del sistema
if ($_POST && verificarTokenCSRF($_POST['csrf_token'] ?? '')) {
    
    if (isset($_POST['confirmar_reset']) && $_POST['confirmar_reset'] === 'CONFIRMAR LIMPIEZA') {
        
        try {
            $database = new Database();
            $pdo = $database->getConnection();
            
            // Iniciar transacción
            $pdo->beginTransaction();
            
            // Leer y ejecutar el script de limpieza
            $script_sql = file_get_contents(__DIR__ . '/limpiar_datos_demo.sql');
            
            // Dividir por statements (separados por punto y coma)
            $statements = array_filter(array_map('trim', explode(';', $script_sql)));
            
            foreach ($statements as $statement) {
                // Saltar comentarios y líneas vacías
                if (empty($statement) || strpos($statement, '--') === 0 || strpos($statement, 'USE ') === 0) {
                    continue;
                }
                
                $pdo->exec($statement);
            }
            
            // Confirmar transacción
            $pdo->commit();
            
            // Log de la acción
            error_log("Sistema reset ejecutado por usuario ID: " . $_SESSION['usuario_id'] . " (" . ($_SESSION['usuario_email'] ?? 'email_no_disponible') . ")");
            
            $message = "✅ Sistema limpiado exitosamente. Todos los datos demo han sido eliminados.";
            
        } catch (Exception $e) {
            // Revertir cambios
            $pdo->rollback();
            $error = "❌ Error al limpiar el sistema: " . htmlspecialchars($e->getMessage());
            error_log("Error en reset del sistema: " . $e->getMessage());
        }
        
    } else {
        $error = "❌ Debe escribir exactamente 'CONFIRMAR LIMPIEZA' para proceder.";
    }
}

require_once 'includes/layout_header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card border-danger">
                <div class="card-header bg-danger text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Reset del Sistema - ZONA PELIGROSA
                    </h4>
                </div>
                <div class="card-body">
                    
                    <?php if ($message): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <?php echo $message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="alert alert-warning">
                        <h5><i class="fas fa-exclamation-triangle"></i> ADVERTENCIA IMPORTANTE</h5>
                        <p class="mb-0">
                            Esta función eliminará <strong>TODOS</strong> los datos demo del sistema de forma <strong>PERMANENTE</strong>.
                            Esta acción <strong>NO se puede deshacer</strong>.
                        </p>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="text-danger">Datos que SE ELIMINARÁN:</h5>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item">✗ Todos los productos demo</li>
                                <li class="list-group-item">✗ Todos los inventarios</li>
                                <li class="list-group-item">✗ Todas las ventas registradas</li>
                                <li class="list-group-item">✗ Todos los vendedores demo</li>
                                <li class="list-group-item">✗ Todas las boletas subidas</li>
                                <li class="list-group-item">✗ Todas las reparaciones</li>
                                <li class="list-group-item">✗ Todos los movimientos de inventario</li>
                                <li class="list-group-item">✗ Usuarios demo (excepto admin)</li>
                                <li class="list-group-item">✗ Tiendas demo (excepto principal)</li>
                            </ul>
                        </div>
                        
                        <div class="col-md-6">
                            <h5 class="text-success">Datos que SE PRESERVARÁN:</h5>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item">✓ Usuario administrador actual</li>
                                <li class="list-group-item">✓ Configuración del sistema</li>
                                <li class="list-group-item">✓ Roles y permisos</li>
                                <li class="list-group-item">✓ Estructura de la base de datos</li>
                                <li class="list-group-item">✓ Configuración de moneda (Quetzal)</li>
                                <li class="list-group-item">✓ Temas del sistema</li>
                                <li class="list-group-item">✓ Una tienda principal limpia</li>
                            </ul>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="card border-danger">
                        <div class="card-header bg-danger text-white">
                            <h5 class="mb-0">Ejecutar Limpieza del Sistema</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" id="resetForm" onsubmit="return confirmarReset()">
                                <?php echo generarTokenCSRF(); ?>
                                
                                <div class="mb-3">
                                    <label class="form-label">
                                        <strong>Para confirmar, escriba exactamente:</strong> 
                                        <code>CONFIRMAR LIMPIEZA</code>
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           name="confirmar_reset" 
                                           placeholder="Escriba: CONFIRMAR LIMPIEZA"
                                           required
                                           autocomplete="off">
                                    <div class="form-text text-muted">
                                        Debe escribir exactamente "CONFIRMAR LIMPIEZA" (sin comillas) para proceder.
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="entiendo" required>
                                        <label class="form-check-label" for="entiendo">
                                            Entiendo que esta acción eliminará TODOS los datos demo de forma PERMANENTE
                                        </label>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="backup" required>
                                        <label class="form-check-label" for="backup">
                                            Confirmo que he realizado un backup de la base de datos si es necesario
                                        </label>
                                    </div>
                                </div>

                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <a href="configuracion.php" class="btn btn-secondary me-md-2">
                                        <i class="fas fa-arrow-left"></i> Cancelar
                                    </a>
                                    <button type="submit" class="btn btn-danger">
                                        <i class="fas fa-trash-alt"></i> EJECUTAR LIMPIEZA
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="mt-4">
                        <h6>Información Técnica:</h6>
                        <ul class="small text-muted">
                            <li>El script ejecutará: <code>limpiar_datos_demo.sql</code></li>
                            <li>Se preservará la integridad referencial</li>
                            <li>Los contadores AUTO_INCREMENT se reiniciarán</li>
                            <li>La acción se registrará en el log del sistema</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function confirmarReset() {
    const confirmText = document.querySelector('input[name="confirmar_reset"]').value;
    const entiendo = document.getElementById('entiendo').checked;
    const backup = document.getElementById('backup').checked;
    
    if (confirmText !== 'CONFIRMAR LIMPIEZA') {
        alert('❌ Debe escribir exactamente "CONFIRMAR LIMPIEZA" para proceder.');
        return false;
    }
    
    if (!entiendo || !backup) {
        alert('❌ Debe confirmar todas las casillas de verificación.');
        return false;
    }
    
    // Confirmación final
    const confirmar = confirm(
        '🚨 ÚLTIMA CONFIRMACIÓN 🚨\n\n' +
        'Está a punto de ELIMINAR PERMANENTEMENTE todos los datos demo del sistema.\n\n' +
        '¿Está ABSOLUTAMENTE SEGURO de que desea continuar?\n\n' +
        'Esta acción NO se puede deshacer.'
    );
    
    if (confirmar) {
        // Mostrar indicador de carga
        const submitBtn = document.querySelector('button[type="submit"]');
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Ejecutando...';
        submitBtn.disabled = true;
        
        return true;
    }
    
    return false;
}

// Prevenir envío accidental del formulario
document.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && e.target.form && e.target.form.id === 'resetForm') {
        e.preventDefault();
    }
});
</script>

<?php require_once 'includes/layout_footer.php'; ?>
