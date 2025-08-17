<?php
require_once 'includes/auth.php';
verificarLogin();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sin Permisos - Sistema de Inventarios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="fas fa-exclamation-triangle fa-5x text-warning mb-4"></i>
                        <h3>Sin Permisos</h3>
                        <p class="text-muted">No tienes permisos para acceder a esta sección del sistema.</p>
                        <p><strong>Tu rol actual:</strong> <?php echo obtenerNombreRol(); ?></p>
                        <hr>
                        <p>Si necesitas acceso a esta funcionalidad, contacta con el administrador del sistema.</p>
                        <a href="index.php" class="btn btn-primary">Volver al Dashboard</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js"></script>
</body>
</html>