<?php
$menu_modulos = obtenerMenuModulos();
?>
<nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <?php 
            // Cargar configuración visual
            require_once __DIR__ . '/estilos_dinamicos.php';
            $config_visual = obtenerConfiguracionVisual();
            
            if (!empty($config_visual['logo_pequeno'])): ?>
                <img src="uploads/branding/<?php echo $config_visual['logo_pequeno']; ?>" alt="Logo" style="height: 32px; margin-right: 8px;">
            <?php else: ?>
                <i class="fas fa-boxes"></i>
            <?php endif; ?>
            <?php echo $config_visual['nombre_empresa']; ?>
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <?php foreach ($menu_modulos as $modulo => $info): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $info['url']; ?>">
                            <i class="<?php echo $info['icono']; ?>"></i>
                            <?php echo $info['nombre']; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
            
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle"></i>
                        <?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?>
                        <small class="d-block text-muted"><?php echo htmlspecialchars(obtenerNombreRol()); ?></small>
                    </a>
                    <ul class="dropdown-menu">
                        <li><h6 class="dropdown-header">Mi Cuenta</h6></li>
                        <li><span class="dropdown-item-text small">
                            <strong>Rol:</strong> <?php echo htmlspecialchars(obtenerNombreRol()); ?><br>
                            <strong>Email:</strong> <?php echo htmlspecialchars($_SESSION['usuario_email']); ?>
                        </span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="?logout=1">
                            <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                        </a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<?php
if (isset($_GET['logout'])) {
    logout();
}
?>

<!-- Agregamos Font Awesome si no está incluido -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
