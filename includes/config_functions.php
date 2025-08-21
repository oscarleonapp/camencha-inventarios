<?php
require_once __DIR__ . '/../config/database.php';

function cargarConfiguracion() {
    static $config_cache = null;
    
    if ($config_cache !== null) {
        return $config_cache;
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT clave, valor, tipo FROM configuraciones";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $config = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $valor = $row['valor'];
        
        // Convertir según el tipo
        switch ($row['tipo']) {
            case 'numero':
                $valor = is_numeric($valor) ? (float)$valor : 0;
                break;
            case 'booleano':
                $valor = (bool)$valor;
                break;
            case 'json':
                $valor = json_decode($valor, true) ?: [];
                break;
        }
        
        $config[$row['clave']] = $valor;
    }
    
    $config_cache = $config;
    return $config;
}

function obtenerConfiguracion($clave, $default = null) {
    $config = cargarConfiguracion();
    return isset($config[$clave]) ? $config[$clave] : $default;
}

function actualizarConfiguracion($clave, $valor, $usuario_id = null) {
    $database = new Database();
    $db = $database->getConnection();
    
    // Obtener valor anterior para el log
    $valor_anterior = obtenerConfiguracion($clave);
    
    $query = "INSERT INTO configuraciones (clave, valor, tipo) 
              VALUES (?, ?, 'texto') 
              ON DUPLICATE KEY UPDATE valor = VALUES(valor)";
    
    $stmt = $db->prepare($query);
    $resultado = $stmt->execute([$clave, $valor]);
    
    // Log del cambio de configuración
    if ($resultado && class_exists('Logger')) {
        require_once __DIR__ . '/logger.php';
        getLogger()->config($clave, $valor_anterior, $valor);
    }
    
    return $resultado;
}

function cargarEtiquetas() {
    static $etiquetas_cache = null;
    
    if ($etiquetas_cache !== null) {
        return $etiquetas_cache;
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT clave, COALESCE(valor_personalizado, valor_original) as valor FROM etiquetas_personalizadas";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $etiquetas = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $etiquetas[$row['clave']] = $row['valor'];
    }
    
    $etiquetas_cache = $etiquetas;
    return $etiquetas;
}

function obtenerEtiqueta($clave, $default = null) {
    $etiquetas = cargarEtiquetas();
    return isset($etiquetas[$clave]) ? $etiquetas[$clave] : $default;
}

function actualizarEtiqueta($clave, $valor, $usuario_id = null) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "UPDATE etiquetas_personalizadas 
              SET valor_personalizado = ? 
              WHERE clave = ?";
    
    $stmt = $db->prepare($query);
    return $stmt->execute([$valor, $clave]);
}

function formatearMoneda($cantidad, $incluir_simbolo = true) {
    $config = cargarConfiguracion();
    
    $cantidad = (float)$cantidad;
    $decimales = (int)($config['decimales_mostrar'] ?? 2);
    $sep_decimal = $config['separador_decimal'] ?? '.';
    $sep_miles = $config['separador_miles'] ?? ',';
    
    $numero_formateado = number_format($cantidad, $decimales, $sep_decimal, $sep_miles);
    
    if ($incluir_simbolo) {
        // Compatibilidad: aceptar 'moneda_simbolo' y 'simbolo_moneda'
        $simbolo = $config['simbolo_moneda'] ?? ($config['moneda_simbolo'] ?? 'Q');
        $posicion = $config['posicion_simbolo'] ?? 'antes';
        
        if ($posicion === 'despues') {
            return $numero_formateado . ' ' . $simbolo;
        } else {
            return $simbolo . ' ' . $numero_formateado;
        }
    }
    
    return $numero_formateado;
}

function obtenerTemas() {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT * FROM temas_sistema WHERE es_activo = 1 ORDER BY nombre";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function obtenerTemaActual() {
    $tema_nombre = obtenerConfiguracion('tema_actual', 'default');
    
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT * FROM temas_sistema WHERE nombre = ? AND es_activo = 1";
    $stmt = $db->prepare($query);
    $stmt->execute([$tema_nombre]);
    
    $tema = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$tema) {
        // Si no se encuentra el tema, usar el primero disponible
        $query = "SELECT * FROM temas_sistema WHERE es_activo = 1 ORDER BY nombre LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $tema = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    return $tema;
}

function aplicarTema($tema_id) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT nombre FROM temas_sistema WHERE id = ? AND es_activo = 1";
    $stmt = $db->prepare($query);
    $stmt->execute([$tema_id]);
    
    $tema = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($tema) {
        return actualizarConfiguracion('tema_actual', $tema['nombre']);
    }
    
    return false;
}

function limpiarCacheConfiguracion() {
    // Limpiar variables estáticas
    $reflection = new ReflectionFunction('cargarConfiguracion');
    $closure = $reflection->getClosure();
    $closure->bindTo(null);
    
    $reflection = new ReflectionFunction('cargarEtiquetas');
    $closure = $reflection->getClosure();
    $closure->bindTo(null);
}

function exportarConfiguracion() {
    $config = cargarConfiguracion();
    $etiquetas = cargarEtiquetas();
    $temas = obtenerTemas();
    
    return [
        'configuraciones' => $config,
        'etiquetas' => $etiquetas,
        'temas' => $temas,
        'version' => obtenerConfiguracion('version_sistema', '2.0.0'),
        'fecha_exportacion' => date('Y-m-d H:i:s')
    ];
}

function importarConfiguracion($datos, $sobrescribir = false) {
    if (!isset($datos['configuraciones']) || !isset($datos['etiquetas'])) {
        return false;
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        $db->beginTransaction();
        
        // Importar configuraciones
        if ($sobrescribir || !empty($datos['configuraciones'])) {
            foreach ($datos['configuraciones'] as $clave => $valor) {
                actualizarConfiguracion($clave, $valor);
            }
        }
        
        // Importar etiquetas
        if ($sobrescribir || !empty($datos['etiquetas'])) {
            foreach ($datos['etiquetas'] as $clave => $valor) {
                actualizarEtiqueta($clave, $valor);
            }
        }
        
        $db->commit();
        limpiarCacheConfiguracion();
        return true;
        
    } catch (Exception $e) {
        $db->rollBack();
        return false;
    }
}

// Función helper para obtener colores CSS dinámicos
function obtenerColoresCSS() {
    $tema = obtenerTemaActual();
    $config = cargarConfiguracion();
    
    return [
        '--primary-color' => $config['color_primario'] ?? $tema['color_primario'] ?? '#007bff',
        '--secondary-color' => $config['color_secundario'] ?? $tema['color_secundario'] ?? '#6c757d',
        '--success-color' => $tema['color_success'] ?? '#28a745',
        '--danger-color' => $tema['color_danger'] ?? '#dc3545',
        '--warning-color' => $tema['color_warning'] ?? '#ffc107',
        '--info-color' => $tema['color_info'] ?? '#17a2b8',
        '--sidebar-color' => $config['sidebar_color'] ?? $tema['sidebar_color'] ?? '#2c3e50',
        '--topbar-color' => $config['topbar_color'] ?? $tema['topbar_color'] ?? '#007bff',
        '--sidebar-width' => $config['sidebar_width'] ?? '280px'
    ];
}
?>
