<?php
/**
 * Sistema de Estilos Dinámicos
 * Genera CSS personalizado basado en la configuración visual del sistema
 */

function generarEstilosDinamicos() {
    // Cargar configuración visual
    $database = new Database();
    $db = $database->getConnection();
    
    $config = [];
    try {
        $stmt = $db->prepare("SELECT clave, valor FROM configuraciones WHERE categoria = 'visual'");
        $stmt->execute();
    } catch (Throwable $e) {
        // Compatibilidad: si no existe la columna 'categoria', cargar todas las configuraciones
        $stmt = $db->prepare("SELECT clave, valor FROM configuraciones");
        $stmt->execute();
    }
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $config[$row['clave']] = $row['valor'];
    }
    
    // Valores por defecto
    $config = array_merge([
        'color_primario' => '#007bff',
        'color_secundario' => '#6c757d',
        'color_exito' => '#28a745',
        'color_peligro' => '#dc3545',
        'color_advertencia' => '#ffc107',
        'color_info' => '#17a2b8',
        'color_sidebar' => '#343a40',
        'color_navbar' => '#ffffff',
        'color_texto' => '#212529',
        'color_fondo' => '#f8f9fa',
        'logo_principal' => '',
        'logo_pequeno' => '',
        'favicon' => '',
        'nombre_empresa' => 'Sistema de Inventarios',
        'eslogan_empresa' => 'Gestión inteligente de inventarios',
        'fuente_principal' => 'Inter',
        'fuente_secundaria' => 'system-ui',
        'sidebar_estilo' => 'oscuro',
        'navbar_estilo' => 'claro',
        'bordes_redondeados' => '0.375rem',
        'sombras_activas' => '1',
        'animaciones_activas' => '1',
        'modo_compacto' => '0'
    ], $config);
    
    // Generar CSS dinámico
    $css = generarCSS($config);
    
    return $css;
}

function generarCSS($config) {
    $css = "/* Estilos Dinámicos del Sistema */\n";
    
    // Variables CSS personalizadas
    $css .= ":root {\n";
    $css .= "    --color-primario: {$config['color_primario']};\n";
    $css .= "    --color-secundario: {$config['color_secundario']};\n";
    $css .= "    --color-exito: {$config['color_exito']};\n";
    $css .= "    --color-peligro: {$config['color_peligro']};\n";
    $css .= "    --color-advertencia: {$config['color_advertencia']};\n";
    $css .= "    --color-info: {$config['color_info']};\n";
    $css .= "    --color-sidebar: {$config['color_sidebar']};\n";
    $css .= "    --color-navbar: {$config['color_navbar']};\n";
    $css .= "    --color-texto: {$config['color_texto']};\n";
    $css .= "    --color-fondo: {$config['color_fondo']};\n";
    $css .= "    --fuente-principal: '{$config['fuente_principal']}', -apple-system, BlinkMacSystemFont, sans-serif;\n";
    $css .= "    --fuente-secundaria: '{$config['fuente_secundaria']}', -apple-system, BlinkMacSystemFont, sans-serif;\n";
    $css .= "    --bordes-redondeados: {$config['bordes_redondeados']};\n";
    
    // Configurar sombras
    if ($config['sombras_activas']) {
        $css .= "    --sombra-card: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);\n";
        $css .= "    --sombra-hover: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);\n";
        $css .= "    --sombra-modal: 0 0.5rem 3rem rgba(0, 0, 0, 0.176);\n";
    } else {
        $css .= "    --sombra-card: none;\n";
        $css .= "    --sombra-hover: none;\n";
        $css .= "    --sombra-modal: none;\n";
    }
    
    // Configurar transiciones
    if ($config['animaciones_activas']) {
        $css .= "    --transicion-rapida: 0.15s ease-in-out;\n";
        $css .= "    --transicion-normal: 0.3s ease-in-out;\n";
        $css .= "    --transicion-lenta: 0.5s ease-in-out;\n";
    } else {
        $css .= "    --transicion-rapida: none;\n";
        $css .= "    --transicion-normal: none;\n";
        $css .= "    --transicion-lenta: none;\n";
    }
    
    // Configurar espaciado
    if ($config['modo_compacto']) {
        $css .= "    --espaciado-xs: 0.25rem;\n";
        $css .= "    --espaciado-sm: 0.5rem;\n";
        $css .= "    --espaciado-md: 0.75rem;\n";
        $css .= "    --espaciado-lg: 1rem;\n";
        $css .= "    --espaciado-xl: 1.5rem;\n";
    } else {
        $css .= "    --espaciado-xs: 0.5rem;\n";
        $css .= "    --espaciado-sm: 0.75rem;\n";
        $css .= "    --espaciado-md: 1rem;\n";
        $css .= "    --espaciado-lg: 1.5rem;\n";
        $css .= "    --espaciado-xl: 2rem;\n";
    }
    
    $css .= "}\n\n";
    
    // Sobrescribir colores de Bootstrap
    $css .= "/* Colores Bootstrap Personalizados */\n";
    $css .= ".btn-primary, .bg-primary { background-color: var(--color-primario) !important; border-color: var(--color-primario) !important; }\n";
    $css .= ".btn-secondary, .bg-secondary { background-color: var(--color-secundario) !important; border-color: var(--color-secundario) !important; }\n";
    $css .= ".btn-success, .bg-success { background-color: var(--color-exito) !important; border-color: var(--color-exito) !important; }\n";
    $css .= ".btn-danger, .bg-danger { background-color: var(--color-peligro) !important; border-color: var(--color-peligro) !important; }\n";
    $css .= ".btn-warning, .bg-warning { background-color: var(--color-advertencia) !important; border-color: var(--color-advertencia) !important; }\n";
    $css .= ".btn-info, .bg-info { background-color: var(--color-info) !important; border-color: var(--color-info) !important; }\n\n";
    
    $css .= ".text-primary { color: var(--color-primario) !important; }\n";
    $css .= ".text-secondary { color: var(--color-secundario) !important; }\n";
    $css .= ".text-success { color: var(--color-exito) !important; }\n";
    $css .= ".text-danger { color: var(--color-peligro) !important; }\n";
    $css .= ".text-warning { color: var(--color-advertencia) !important; }\n";
    $css .= ".text-info { color: var(--color-info) !important; }\n\n";
    
    // Configuración del body
    $css .= "/* Configuración Global */\n";
    $css .= "body {\n";
    $css .= "    font-family: var(--fuente-secundaria);\n";
    $css .= "    background-color: var(--color-fondo);\n";
    $css .= "    color: var(--color-texto);\n";
    $css .= "    transition: var(--transicion-normal);\n";
    $css .= "}\n\n";
    
    // Fuentes
    $css .= "/* Tipografía Personalizada */\n";
    $css .= "h1, h2, h3, h4, h5, h6, .h1, .h2, .h3, .h4, .h5, .h6 {\n";
    $css .= "    font-family: var(--fuente-principal);\n";
    $css .= "}\n\n";
    
    // Sidebar
    $css .= "/* Sidebar Personalizado */\n";
    $css .= ".sidebar {\n";
    $css .= "    background-color: var(--color-sidebar) !important;\n";
    
    if ($config['sidebar_estilo'] === 'claro') {
        $css .= "    color: var(--color-texto) !important;\n";
        $css .= "}\n";
        $css .= ".sidebar .nav-link {\n";
        $css .= "    color: var(--color-texto) !important;\n";
        $css .= "}\n";
        $css .= ".sidebar .nav-link:hover {\n";
        $css .= "    background-color: rgba(0,0,0,0.1) !important;\n";
        $css .= "}\n";
    } elseif ($config['sidebar_estilo'] === 'colorido') {
        $css .= "    background: linear-gradient(135deg, var(--color-primario), " . adjustBrightness($config['color_primario'], -30) . ") !important;\n";
        $css .= "    color: white !important;\n";
        $css .= "}\n";
        $css .= ".sidebar .nav-link {\n";
        $css .= "    color: rgba(255,255,255,0.9) !important;\n";
        $css .= "}\n";
        $css .= ".sidebar .nav-link:hover {\n";
        $css .= "    background-color: rgba(255,255,255,0.15) !important;\n";
        $css .= "    color: white !important;\n";
        $css .= "}\n";
    } else { // oscuro
        $css .= "    color: rgba(255,255,255,0.9) !important;\n";
        $css .= "}\n";
        $css .= ".sidebar .nav-link {\n";
        $css .= "    color: rgba(255,255,255,0.7) !important;\n";
        $css .= "}\n";
        $css .= ".sidebar .nav-link:hover {\n";
        $css .= "    background-color: rgba(255,255,255,0.1) !important;\n";
        $css .= "    color: rgba(255,255,255,0.9) !important;\n";
        $css .= "}\n";
    }
    
    $css .= ".sidebar .nav-link.active {\n";
    $css .= "    background-color: var(--color-primario) !important;\n";
    $css .= "    color: white !important;\n";
    $css .= "}\n\n";
    
    // Navbar
    $css .= "/* Navbar Personalizado */\n";
    $css .= ".navbar, .topbar {\n";
    $css .= "    background-color: var(--color-navbar) !important;\n";
    
    if ($config['navbar_estilo'] === 'oscuro') {
        $css .= "    color: rgba(255,255,255,0.9) !important;\n";
        $css .= "}\n";
        $css .= ".navbar .navbar-brand, .topbar .topbar-brand {\n";
        $css .= "    color: rgba(255,255,255,0.9) !important;\n";
        $css .= "}\n";
    } elseif ($config['navbar_estilo'] === 'colorido') {
        $css .= "    background: linear-gradient(90deg, var(--color-primario), " . adjustBrightness($config['color_primario'], 20) . ") !important;\n";
        $css .= "    color: white !important;\n";
        $css .= "}\n";
        $css .= ".navbar .navbar-brand, .topbar .topbar-brand {\n";
        $css .= "    color: white !important;\n";
        $css .= "}\n";
    } else { // claro
        $css .= "    color: var(--color-texto) !important;\n";
        $css .= "    border-bottom: 1px solid rgba(0,0,0,0.1);\n";
        $css .= "}\n";
        $css .= ".navbar .navbar-brand, .topbar .topbar-brand {\n";
        $css .= "    color: var(--color-texto) !important;\n";
        $css .= "}\n";
    }
    
    $css .= "\n";
    
    // Cards y elementos
    $css .= "/* Cards y Elementos */\n";
    $css .= ".card {\n";
    $css .= "    border-radius: var(--bordes-redondeados);\n";
    $css .= "    box-shadow: var(--sombra-card);\n";
    $css .= "    transition: var(--transicion-normal);\n";
    $css .= "}\n";
    $css .= ".card:hover {\n";
    $css .= "    box-shadow: var(--sombra-hover);\n";
    $css .= "}\n\n";
    
    $css .= ".btn {\n";
    $css .= "    border-radius: var(--bordes-redondeados);\n";
    $css .= "    transition: var(--transicion-rapida);\n";
    $css .= "}\n\n";
    
    $css .= ".form-control, .form-select {\n";
    $css .= "    border-radius: var(--bordes-redondeados);\n";
    $css .= "    transition: var(--transicion-rapida);\n";
    $css .= "}\n\n";
    
    $css .= ".modal-content {\n";
    $css .= "    border-radius: var(--bordes-redondeados);\n";
    $css .= "    box-shadow: var(--sombra-modal);\n";
    $css .= "}\n\n";
    
    // Espaciado compacto
    if ($config['modo_compacto']) {
        $css .= "/* Modo Compacto */\n";
        $css .= ".card-body { padding: var(--espaciado-md); }\n";
        $css .= ".btn { padding: 0.25rem 0.5rem; font-size: 0.875rem; }\n";
        $css .= ".table td, .table th { padding: 0.5rem; }\n";
        $css .= "h1 { font-size: 1.75rem; }\n";
        $css .= "h2 { font-size: 1.5rem; }\n";
        $css .= "h3 { font-size: 1.25rem; }\n";
        $css .= ".mb-4 { margin-bottom: 1rem !important; }\n";
        $css .= ".mt-4 { margin-top: 1rem !important; }\n";
        $css .= ".p-4 { padding: 1rem !important; }\n\n";
    }
    
    // Links de navegación personalizados
    $css .= "/* Enlaces de Navegación */\n";
    $css .= "a {\n";
    $css .= "    color: var(--color-primario);\n";
    $css .= "    transition: var(--transicion-rapida);\n";
    $css .= "}\n";
    $css .= "a:hover {\n";
    $css .= "    color: " . adjustBrightness($config['color_primario'], -20) . ";\n";
    $css .= "}\n\n";
    
    // Badges
    $css .= "/* Badges Personalizados */\n";
    $css .= ".badge {\n";
    $css .= "    border-radius: calc(var(--bordes-redondeados) * 0.5);\n";
    $css .= "}\n\n";
    
    // Progress bars
    $css .= "/* Barras de Progreso */\n";
    $css .= ".progress {\n";
    $css .= "    border-radius: var(--bordes-redondeados);\n";
    $css .= "}\n";
    $css .= ".progress-bar {\n";
    $css .= "    background-color: var(--color-primario);\n";
    $css .= "}\n\n";
    
    // Alertas
    $css .= "/* Alertas Personalizadas */\n";
    $css .= ".alert {\n";
    $css .= "    border-radius: var(--bordes-redondeados);\n";
    $css .= "}\n\n";
    
    // Toasts
    $css .= "/* Toasts Personalizados */\n";
    $css .= ".toast {\n";
    $css .= "    border-radius: var(--bordes-redondeados);\n";
    $css .= "    box-shadow: var(--sombra-card);\n";
    $css .= "}\n\n";
    
    return $css;
}

function adjustBrightness($hexColor, $percent) {
    // Convertir hex a RGB
    $hex = str_replace('#', '', $hexColor);
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    
    // Ajustar brillo
    $r = max(0, min(255, $r + (255 * $percent / 100)));
    $g = max(0, min(255, $g + (255 * $percent / 100)));
    $b = max(0, min(255, $b + (255 * $percent / 100)));
    
    // Convertir de vuelta a hex
    return sprintf("#%02x%02x%02x", $r, $g, $b);
}

function obtenerConfiguracionVisual($clave = null) {
    static $config = null;
    
    if ($config === null) {
        $database = new Database();
        $db = $database->getConnection();
        
        $config = [];
        try {
            $stmt = $db->prepare("SELECT clave, valor FROM configuraciones WHERE categoria = 'visual'");
            $stmt->execute();
        } catch (Throwable $e) {
            $stmt = $db->prepare("SELECT clave, valor FROM configuraciones");
            $stmt->execute();
        }
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $config[$row['clave']] = $row['valor'];
        }
        
        // Valores por defecto
        $config = array_merge([
            'color_primario' => '#007bff',
            'color_secundario' => '#6c757d',
            'color_exito' => '#28a745',
            'color_peligro' => '#dc3545',
            'color_advertencia' => '#ffc107',
            'color_info' => '#17a2b8',
            'color_sidebar' => '#343a40',
            'color_navbar' => '#ffffff',
            'color_texto' => '#212529',
            'color_fondo' => '#f8f9fa',
            'logo_principal' => '',
            'logo_pequeno' => '',
            'favicon' => '',
            'nombre_empresa' => 'Sistema de Inventarios',
            'eslogan_empresa' => 'Gestión inteligente de inventarios',
            'fuente_principal' => 'Inter',
            'fuente_secundaria' => 'system-ui',
            'sidebar_estilo' => 'oscuro',
            'navbar_estilo' => 'claro',
            'bordes_redondeados' => '0.375rem',
            'sombras_activas' => '1',
            'animaciones_activas' => '1',
            'modo_compacto' => '0'
        ], $config);
    }
    
    if ($clave) {
        return $config[$clave] ?? null;
    }
    
    return $config;
}

function mostrarEstilosDinamicos() {
    header('Content-Type: text/css');
    header('Cache-Control: max-age=3600'); // Cache por 1 hora
    
    echo generarEstilosDinamicos();
}
?>