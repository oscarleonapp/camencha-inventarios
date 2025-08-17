<?php
session_start();
require_once 'config/database.php';
require_once 'includes/logger.php';

if ($_POST) {
    $database = new Database();
    $db = $database->getConnection();
    $logger = new Logger($db);
    
    // Validar y sanitizar entrada
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    
    // Validaciones básicas
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Email válido es requerido";
        $logger->warning('login_validation_error', 'auth', "Intento de login con email inválido: $email");
    } elseif (empty($password) || strlen($password) < 1) {
        $error = "Contraseña es requerida";
        $logger->warning('login_validation_error', 'auth', "Intento de login sin contraseña para email: $email");
    } elseif (strlen($password) > 255) {
        $error = "Contraseña demasiado larga";
        $logger->warning('login_validation_error', 'auth', "Intento de login con contraseña muy larga para email: $email");
    }
    
    if (!isset($error)) {
        $query = "SELECT u.id, u.nombre, u.email, u.password, u.rol, u.rol_id, r.nombre as rol_nombre 
                  FROM usuarios u 
                  LEFT JOIN roles r ON u.rol_id = r.id 
                  WHERE u.email = ? AND u.activo = 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($password, $row['password'])) {
                // Regenerar ID de sesión por seguridad
                session_regenerate_id(true);
                
                $_SESSION['usuario_id'] = $row['id'];
                $_SESSION['usuario_nombre'] = $row['nombre'];
                $_SESSION['usuario_email'] = $row['email'];
                $_SESSION['rol'] = $row['rol'];
                $_SESSION['rol_id'] = $row['rol_id'];
                $_SESSION['rol_nombre'] = $row['rol_nombre'];
                
                // Log login exitoso
                $logger->login($email, true);
                
                header('Location: index.php');
                exit();
            } else {
                $error = "Credenciales incorrectas";
                $logger->login($email, false);
            }
        } else {
            $error = "Credenciales incorrectas";
            $logger->warning('login_user_not_found', 'auth', "Intento de login con usuario inexistente: $email");
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Inventarios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card mt-5">
                    <div class="card-header">
                        <h4 class="text-center">Iniciar Sesión</h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Contraseña</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Ingresar</button>
                        </form>
                        
                        <div class="mt-3">
                            <small class="text-muted">
                                <strong>Usuario demo:</strong><br>
                                Email: admin@inventario.com<br>
                                Contraseña: password
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>