<?php
/**
 * Sistema de Logging para el Sistema de Inventario
 * Registra todas las acciones críticas y errores del sistema
 */

class Logger {
    private $db;
    private $usuario_id;
    private $usuario_nombre;
    private $usuario_email;
    private $ip_address;
    private $user_agent;
    private $url;
    private $metodo_http;
    private $tiempo_inicio;
    
    public function __construct($database = null) {
        if ($database) {
            $this->db = $database;
        } else {
            $db_instance = new Database();
            $this->db = $db_instance->getConnection();
        }
        
        $this->inicializarContexto();
    }
    
    private function inicializarContexto() {
        // Información del usuario
        $this->usuario_id = $_SESSION['usuario_id'] ?? null;
        $this->usuario_nombre = $_SESSION['usuario_nombre'] ?? 'Sistema';
        $this->usuario_email = $_SESSION['usuario_email'] ?? 'sistema@inventario.com';
        
        // Información de la petición
        $this->ip_address = $this->obtenerIP();
        $this->user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Sistema Interno';
        $this->url = $_SERVER['REQUEST_URI'] ?? '';
        $this->metodo_http = $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN';
        $this->tiempo_inicio = microtime(true);
    }
    
    private function obtenerIP() {
        // Obtener IP real considerando proxies
        $ip_keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                $ip = trim($ips[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
    
    /**
     * Registrar acción general
     */
    public function log($accion, $modulo, $descripcion, $nivel = 'info', $datos_anteriores = null, $datos_nuevos = null, $estado = 'exitoso') {
        try {
            $tiempo_ejecucion = microtime(true) - $this->tiempo_inicio;
            
            $query = "INSERT INTO logs_sistema (
                usuario_id, tipo, modulo, mensaje, ip, fecha
            ) VALUES (?, ?, ?, ?, ?, NOW())";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $this->usuario_id,
                $nivel, // Se mapea a 'tipo'
                $modulo,
                $descripcion, // Se mapea a 'mensaje'
                $this->ip_address // Se mapea a 'ip'
            ]);
            
            return true;
        } catch (Exception $e) {
            // En caso de error en el logging, escribir a archivo
            error_log("Error en Logger: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Registrar información general
     */
    public function info($accion, $modulo, $descripcion, $datos = null) {
        return $this->log($accion, $modulo, $descripcion, 'info', null, $datos);
    }
    
    /**
     * Registrar advertencia
     */
    public function warning($accion, $modulo, $descripcion, $datos = null) {
        return $this->log($accion, $modulo, $descripcion, 'warning', null, $datos);
    }
    
    /**
     * Registrar error
     */
    public function error($accion, $modulo, $descripcion, $error_data = null) {
        return $this->log($accion, $modulo, $descripcion, 'error', null, $error_data, 'fallido');
    }
    
    /**
     * Registrar error crítico
     */
    public function critical($accion, $modulo, $descripcion, $error_data = null) {
        return $this->log($accion, $modulo, $descripcion, 'critical', null, $error_data, 'fallido');
    }
    
    /**
     * Registrar debug
     */
    public function debug($accion, $modulo, $descripcion, $datos = null) {
        return $this->log($accion, $modulo, $descripcion, 'debug', null, $datos);
    }
    
    /**
     * Registrar operación CRUD
     */
    public function crud($operacion, $modulo, $tabla, $registro_id, $datos_anteriores = null, $datos_nuevos = null) {
        $descripcion = sprintf(
            "Operación %s en tabla %s, registro ID: %s",
            strtoupper($operacion),
            $tabla,
            $registro_id
        );
        
        return $this->log($operacion, $modulo, $descripcion, 'info', $datos_anteriores, $datos_nuevos);
    }
    
    /**
     * Registrar inicio de sesión
     */
    public function login($email, $exitoso = true) {
        $descripcion = $exitoso ? 
            "Usuario iniciado sesión exitosamente: $email" : 
            "Intento fallido de inicio de sesión: $email";
            
        return $this->log('login', 'auth', $descripcion, 'info', null, 
            ['email' => $email, 'exitoso' => $exitoso], 
            $exitoso ? 'exitoso' : 'fallido'
        );
    }
    
    /**
     * Registrar cierre de sesión
     */
    public function logout() {
        return $this->log('logout', 'auth', 
            "Usuario cerró sesión: {$this->usuario_email}");
    }
    
    /**
     * Registrar cambio de configuración
     */
    public function config($clave, $valor_anterior, $valor_nuevo) {
        $descripcion = "Configuración modificada: $clave";
        return $this->log('config_change', 'configuracion', $descripcion, 'info',
            ['clave' => $clave, 'valor' => $valor_anterior],
            ['clave' => $clave, 'valor' => $valor_nuevo]
        );
    }
    
    /**
     * Registrar venta
     */
    public function venta($venta_id, $total, $productos_count, $tienda_id) {
        $descripcion = "Venta registrada ID: $venta_id, Total: Q$total, Productos: $productos_count";
        return $this->log('venta_creada', 'ventas', $descripcion, 'info', null, [
            'venta_id' => $venta_id,
            'total' => $total,
            'productos_count' => $productos_count,
            'tienda_id' => $tienda_id
        ]);
    }
    
    /**
     * Registrar movimiento de inventario
     */
    public function inventario($tipo, $producto_id, $cantidad, $tienda_id, $detalles = []) {
        $descripcion = "Movimiento de inventario: $tipo, Producto: $producto_id, Cantidad: $cantidad, Tienda: $tienda_id";
        return $this->log('inventario_' . $tipo, 'inventarios', $descripcion, 'info', null, array_merge([
            'tipo' => $tipo,
            'producto_id' => $producto_id,
            'cantidad' => $cantidad,
            'tienda_id' => $tienda_id
        ], $detalles));
    }
    
    /**
     * Registrar acceso denegado
     */
    public function accesoDenegado($recurso, $permiso_requerido) {
        $descripcion = "Acceso denegado al recurso: $recurso, Permiso requerido: $permiso_requerido";
        return $this->log('acceso_denegado', 'auth', $descripcion, 'warning', null, [
            'recurso' => $recurso,
            'permiso_requerido' => $permiso_requerido
        ], 'fallido');
    }
    
    /**
     * Registrar exportación de datos
     */
    public function exportacion($tipo, $formato, $registros_count, $filtros = []) {
        $descripcion = "Exportación de $tipo en formato $formato, $registros_count registros";
        return $this->log('exportacion', 'exportacion', $descripcion, 'info', null, [
            'tipo' => $tipo,
            'formato' => $formato,
            'registros_count' => $registros_count,
            'filtros' => $filtros
        ]);
    }
    
    /**
     * Registrar error de sistema
     */
    public function sistemaError($error_message, $file = null, $line = null, $trace = null) {
        $descripcion = "Error del sistema: $error_message";
        if ($file) $descripcion .= " en $file";
        if ($line) $descripcion .= " línea $line";
        
        return $this->error('sistema_error', 'sistema', $descripcion, [
            'error_message' => $error_message,
            'file' => $file,
            'line' => $line,
            'trace' => $trace
        ]);
    }
    
    /**
     * Obtener estadísticas de logs
     */
    public static function obtenerEstadisticas($database) {
        $db = $database->getConnection();
        
        $query = "SELECT 
            COUNT(*) as total,
            COUNT(CASE WHEN tipo = 'error' THEN 1 END) as errores,
            COUNT(CASE WHEN tipo = 'warning' THEN 1 END) as advertencias,
            COUNT(CASE WHEN tipo = 'debug' THEN 1 END) as criticos,
            COUNT(CASE WHEN DATE(fecha) = CURDATE() THEN 1 END) as hoy,
            COUNT(CASE WHEN fecha >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as ultima_semana
        FROM logs_sistema";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Limpiar logs antiguos
     */
    public static function limpiarLogsAntiguos($database, $dias = 90) {
        $db = $database->getConnection();
        
        $query = "DELETE FROM logs_sistema WHERE fecha < DATE_SUB(NOW(), INTERVAL ? DAY)";
        $stmt = $db->prepare($query);
        $result = $stmt->execute([$dias]);
        
        return $stmt->rowCount();
    }
}

/**
 * Función helper para obtener instancia global del logger
 */
function getLogger() {
    static $logger = null;
    if ($logger === null) {
        $logger = new Logger();
    }
    return $logger;
}

/**
 * Funciones de conveniencia
 */
function logInfo($accion, $modulo, $descripcion, $datos = null) {
    return getLogger()->info($accion, $modulo, $descripcion, $datos);
}

function logWarning($accion, $modulo, $descripcion, $datos = null) {
    return getLogger()->warning($accion, $modulo, $descripcion, $datos);
}

function logError($accion, $modulo, $descripcion, $error_data = null) {
    return getLogger()->error($accion, $modulo, $descripcion, $error_data);
}

function logCritical($accion, $modulo, $descripcion, $error_data = null) {
    return getLogger()->critical($accion, $modulo, $descripcion, $error_data);
}
?>