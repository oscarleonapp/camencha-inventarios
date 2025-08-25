<?php
require_once __DIR__ . '/utf8_config.php'; // Configurar UTF-8
require_once __DIR__ . '/security_headers.php'; // Configurar cabeceras de seguridad

// Verificar logout ANTES de cualquier output
if (isset($_GET['logout'])) {
    require_once __DIR__ . '/auth.php';
    logout();
}

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit();
}

// Cargar configuración del sistema
require_once __DIR__ . '/config_functions.php';
$config = cargarConfiguracion();
$menu_modulos = obtenerMenuModulos();
$modo_edicion = isset($_SESSION['modo_edicion']) && $_SESSION['modo_edicion'];
?>
<!DOCTYPE html>
<html lang="es" data-theme="<?php echo $config['tema'] ?? 'default'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titulo ?? 'Sistema de Inventarios'; ?></title>
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="<?php echo $config['color_primario'] ?? '#007bff'; ?>">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Inventario">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="msapplication-TileColor" content="<?php echo $config['color_primario'] ?? '#007bff'; ?>">
    <meta name="msapplication-config" content="browserconfig.xml">
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="manifest.json">
    
    <!-- PWA Icons -->
    <link rel="icon" type="image/png" sizes="72x72" href="assets/icons/icon-72x72.png">
    <link rel="icon" type="image/png" sizes="96x96" href="assets/icons/icon-96x96.png">
    <link rel="icon" type="image/png" sizes="128x128" href="assets/icons/icon-128x128.png">
    <link rel="icon" type="image/png" sizes="144x144" href="assets/icons/icon-144x144.png">
    <link rel="icon" type="image/png" sizes="152x152" href="assets/icons/icon-152x152.png">
    <link rel="icon" type="image/png" sizes="192x192" href="assets/icons/icon-192x192.png">
    <link rel="icon" type="image/png" sizes="384x384" href="assets/icons/icon-384x384.png">
    <link rel="icon" type="image/png" sizes="512x512" href="assets/icons/icon-512x512.png">
    
    <!-- Apple Touch Icons -->
    <link rel="apple-touch-icon" sizes="72x72" href="assets/icons/icon-72x72.png">
    <link rel="apple-touch-icon" sizes="96x96" href="assets/icons/icon-96x96.png">
    <link rel="apple-touch-icon" sizes="128x128" href="assets/icons/icon-128x128.png">
    <link rel="apple-touch-icon" sizes="144x144" href="assets/icons/icon-144x144.png">
    <link rel="apple-touch-icon" sizes="152x152" href="assets/icons/icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/icons/icon-192x192.png">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Base layout styles (structure) -->
    <?php
        $css_ver = function($path) { return file_exists($path) ? filemtime($path) : time(); };
    ?>
    <link rel="stylesheet" href="assets/css/admin.css?v=<?php echo $css_ver(__DIR__ . '/../assets/css/admin.css'); ?>">
    <!-- Theme tokens and components (colors/semantics) -->
    <link rel="stylesheet" href="assets/css/modern-theme.css?v=<?php echo $css_ver(__DIR__ . '/../assets/css/modern-theme.css'); ?>">
    <!-- Estilos Dinámicos Personalizados -->
    <link rel="stylesheet" href="estilos_dinamicos.css.php?v=<?php echo time(); ?>">
    <!-- Accesibilidad (último para pequeños ajustes, sin sobrescribir el tema) -->
    <link rel="stylesheet" href="assets/css/accessibility-fixes.css?v=<?php echo $css_ver(__DIR__ . '/../assets/css/accessibility-fixes.css'); ?>">
    <link rel="stylesheet" href="assets/css/accessibility-subtle.css?v=<?php echo $css_ver(__DIR__ . '/../assets/css/accessibility-subtle.css'); ?>"><?php
    // Cargar configuración visual para favicon y fuentes
    require_once __DIR__ . '/estilos_dinamicos.php';
    $config_visual = obtenerConfiguracionVisual();
    
    // Favicon dinámico
    if (!empty($config_visual['favicon'])): ?>
    <link rel="icon" type="image/x-icon" href="uploads/branding/<?php echo $config_visual['favicon']; ?>">
    <?php else: ?>
    <link rel="icon" type="image/x-icon" href="assets/img/favicon.ico">
    <?php endif;
    
    // Cargar fuentes de Google Fonts si es necesario
    $fuentes_google = ['Inter', 'Roboto', 'Open Sans', 'Lato', 'Source Sans Pro', 'Montserrat', 'Nunito', 'Poppins'];
    $fuentes_necesarias = [];
    
    if (in_array($config_visual['fuente_principal'], $fuentes_google)) {
        $fuentes_necesarias[] = str_replace(' ', '+', $config_visual['fuente_principal']) . ':400,500,600,700';
    }
    if (in_array($config_visual['fuente_secundaria'], $fuentes_google) && !in_array($config_visual['fuente_secundaria'], $fuentes_necesarias)) {
        $fuentes_necesarias[] = str_replace(' ', '+', $config_visual['fuente_secundaria']) . ':400,500,600';
    }
    
    if (!empty($fuentes_necesarias)): ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=<?php echo implode('&family=', $fuentes_necesarias); ?>&display=swap" rel="stylesheet">
    <?php endif; ?>
    <?php if (isset($css_adicional)): ?>
        <?php foreach ($css_adicional as $css): ?>
            <link rel="stylesheet" href="<?php echo $css; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    <style>
        :root {
            --primary-color: <?php echo $config['color_primario'] ?? '#007bff'; ?>;
            --secondary-color: <?php echo $config['color_secundario'] ?? '#6c757d'; ?>;
            --sidebar-width: <?php echo $config['sidebar_width'] ?? '280px'; ?>;
        }
        
        .moneda::before {
            content: "<?php echo ($config['simbolo_moneda'] ?? ($config['moneda_simbolo'] ?? 'Q')); ?>";
        }
        
        /* Toast Notifications Mejorados */
        .toast-container {
            max-width: 400px;
        }
        
        .toast {
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.1);
        }
        
        .toast.bg-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%) !important;
        }
        
        .toast.bg-danger {
            background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%) !important;
        }
        
        .toast.bg-warning {
            background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%) !important;
        }
        
        .toast.bg-info {
            background: linear-gradient(135deg, #17a2b8 0%, #6610f2 100%) !important;
        }
        
        .toast.bg-primary {
            background: linear-gradient(135deg, #007bff 0%, #6610f2 100%) !important;
        }
        
        .toast-body {
            font-weight: 500;
            padding: 12px 15px;
        }
        
        .toast-body i {
            margin-right: 8px;
            opacity: 0.9;
        }
        
        .toast .fw-bold {
            font-size: 0.95em;
            margin-bottom: 4px;
        }
        
        @keyframes toastSlideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .toast.showing {
            animation: toastSlideIn 0.3s ease-out;
        }
    </style>
    
    <!-- jsQR Library para escaneo de códigos QR -->
    <script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
</head>
<body class="<?php echo isset($_COOKIE['sidebar_state']) ? $_COOKIE['sidebar_state'] : 'sidebar-collapsed'; ?> <?php echo $modo_edicion ? 'edit-mode' : ''; ?>">

<?php if ($modo_edicion): ?>
    <div class="edit-indicator">
        <i class="fas fa-edit"></i>
        Modo Edición Activado
        <button onclick="toggleEditMode(false)" class="btn btn-sm btn-outline-dark ms-2">
            <i class="fas fa-times"></i>
        </button>
    </div>
<?php endif; ?>

<!-- Overlay para mobile -->
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>

<!-- Topbar -->
<div class="topbar">
    <button class="topbar-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>
    
    <div class="topbar-brand">
        <span class="editable" data-label="sistema_nombre">
            <?php echo $config['nombre_sistema'] ?? 'Sistema de Inventarios'; ?>
        </span>
    </div>
    
    <div class="topbar-user">
        <div class="dropdown">
            <button class="user-dropdown dropdown-toggle" data-bs-toggle="dropdown">
                <i class="fas fa-user-circle"></i>
                <?php echo $_SESSION['usuario_nombre']; ?>
                <small class="d-block text-white-50"><?php echo obtenerNombreRol(); ?></small>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><h6 class="dropdown-header">Mi Cuenta</h6></li>
                <li><span class="dropdown-item-text small">
                    <strong>Rol:</strong> <?php echo obtenerNombreRol(); ?><br>
                    <strong>Email:</strong> <?php echo $_SESSION['usuario_email']; ?>
                </span></li>
                <li><hr class="dropdown-divider"></li>
                
                <?php if (tienePermiso('config_sistema')): ?>
                    <li><a class="dropdown-item" href="configuracion.php">
                        <i class="fas fa-cog"></i> Configuración
                    </a></li>
                    <li><a class="dropdown-item" href="#" onclick="toggleEditMode()">
                        <i class="fas fa-edit"></i> 
                        <?php echo $modo_edicion ? 'Salir del' : 'Activar'; ?> Modo Edición
                    </a></li>
                    <li><hr class="dropdown-divider"></li>
                <?php endif; ?>
                
                <li><a class="dropdown-item" href="?logout=1">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a></li>
            </ul>
        </div>
    </div>
</div>

<!-- Sidebar -->
<nav class="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <i class="fas fa-boxes"></i>
            <span class="editable" data-label="sistema_nombre">
                <?php echo $config['nombre_sistema'] ?? 'Inventarios'; ?>
            </span>
        </div>
    </div>
    
    <ul class="sidebar-nav">
        <?php foreach ($menu_modulos as $item): ?>
            <li class="nav-item">
                <?php if (isset($item['submenu'])): ?>
                    <!-- Item con submenú -->
                    <a href="#" class="nav-link submenu-toggle" data-bs-toggle="collapse" data-bs-target="#submenu-<?php echo $item['id']; ?>">
                        <i class="<?php echo $item['icono']; ?> nav-icon"></i>
                        <span class="nav-text editable" data-label="menu_<?php echo $item['id']; ?>">
                            <?php echo $item['nombre']; ?>
                        </span>
                        <i class="fas fa-chevron-down submenu-arrow"></i>
                    </a>
                    <div class="collapse submenu" id="submenu-<?php echo $item['id']; ?>">
                        <ul class="submenu-list">
                            <?php foreach ($item['submenu'] as $subitem): ?>
                                <li class="submenu-item">
                                    <a href="<?php echo $subitem['url']; ?>" class="submenu-link <?php echo basename($_SERVER['PHP_SELF']) == basename($subitem['url']) ? 'active' : ''; ?>">
                                        <i class="<?php echo $subitem['icono']; ?> submenu-icon"></i>
                                        <span class="submenu-text"><?php echo $subitem['nombre']; ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php else: ?>
                    <!-- Item simple -->
                    <a href="<?php echo $item['url']; ?>" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == basename($item['url']) ? 'active' : ''; ?>">
                        <i class="<?php echo $item['icono']; ?> nav-icon"></i>
                        <span class="nav-text editable" data-label="menu_<?php echo $item['id']; ?>">
                            <?php echo $item['nombre']; ?>
                        </span>
                    </a>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>

<!-- Main Content -->
<div class="main-content">
