<?php
/**
 * Generador de códigos QR para productos
 * Utiliza la API de QR Server para generar códigos QR
 */

class QRGenerator {
    private $db;
    private $base_url;
    
    public function __construct($database) {
        $this->db = $database;
        // URL base del sistema para QR codes
        $this->base_url = $this->getBaseUrl();
    }
    
    private function getBaseUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $path = dirname($_SERVER['SCRIPT_NAME']) ?? '';
        return $protocol . '://' . $host . $path;
    }
    
    /**
     * Generar código QR único para un producto
     */
    public function generarQRProducto($producto_id) {
        try {
            // Obtener datos del producto
            $query = "SELECT * FROM productos WHERE id = ? AND activo = 1";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$producto_id]);
            $producto = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$producto) {
                throw new Exception("Producto no encontrado o inactivo");
            }
            
            // Generar código QR único si no existe
            if (empty($producto['qr_code'])) {
                $qr_code = $this->generarCodigoUnico($producto);
                
                // Actualizar en base de datos
                $update_query = "UPDATE productos SET qr_code = ?, qr_generado_en = NOW() WHERE id = ?";
                $update_stmt = $this->db->prepare($update_query);
                $update_stmt->execute([$qr_code, $producto_id]);
                
                $producto['qr_code'] = $qr_code;
                $producto['qr_generado_en'] = date('Y-m-d H:i:s');
            }
            
            return [
                'success' => true,
                'qr_code' => $producto['qr_code'],
                'qr_data' => $this->construirDatosQR($producto),
                'producto' => $producto
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Generar código único para QR
     */
    private function generarCodigoUnico($producto) {
        $timestamp = time();
        $random = substr(md5(uniqid(rand(), true)), 0, 8);
        $product_hash = substr(md5($producto['codigo'] . $producto['nombre']), 0, 6);
        
        return 'PROD_' . str_pad($producto['id'], 6, '0', STR_PAD_LEFT) . '_' . $product_hash . '_' . $random;
    }
    
    /**
     * Construir datos que irán en el QR
     */
    private function construirDatosQR($producto) {
        return json_encode([
            'type' => 'product',
            'id' => $producto['id'],
            'code' => $producto['codigo'],
            'qr' => $producto['qr_code'],
            'system' => 'inventario_sistema',
            'url' => $this->base_url . '/qr_scan.php?qr=' . $producto['qr_code'],
            'timestamp' => time()
        ], JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * Obtener URL de imagen QR usando QR Server API
     */
    public function obtenerURLImagenQR($qr_code, $size = 200, $formato = 'png') {
        $producto = $this->obtenerProductoPorQR($qr_code);
        if (!$producto) {
            return false;
        }
        
        $qr_data = $this->construirDatosQR($producto);
        $encoded_data = urlencode($qr_data);
        
        // Usar QR Server API (gratuita)
        return "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&format={$formato}&data={$encoded_data}";
    }
    
    /**
     * Generar imagen QR localmente usando Google Charts API como fallback
     */
    public function obtenerURLImagenQRFallback($qr_code, $size = 200) {
        $producto = $this->obtenerProductoPorQR($qr_code);
        if (!$producto) {
            return false;
        }
        
        $qr_data = $this->construirDatosQR($producto);
        $encoded_data = urlencode($qr_data);
        
        // Google Charts API (también gratuita)
        return "https://chart.googleapis.com/chart?chs={$size}x{$size}&cht=qr&chl={$encoded_data}";
    }
    
    /**
     * Obtener producto por código QR
     */
    public function obtenerProductoPorQR($qr_code) {
        $query = "SELECT * FROM productos WHERE qr_code = ? AND activo = 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$qr_code]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Validar código QR
     */
    public function validarQR($qr_code) {
        $producto = $this->obtenerProductoPorQR($qr_code);
        return $producto !== false;
    }
    
    /**
     * Registrar escaneo de QR
     */
    public function registrarEscaneo($qr_code, $tipo_escaneo = 'consulta', $datos_adicionales = null) {
        try {
            $producto = $this->obtenerProductoPorQR($qr_code);
            if (!$producto) {
                throw new Exception("Código QR inválido");
            }
            
            $query = "INSERT INTO qr_escaneos 
                     (producto_id, qr_code, usuario_id, ip_address, user_agent, tipo_escaneo, datos_adicionales) 
                     VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $producto['id'],
                $qr_code,
                $_SESSION['usuario_id'] ?? null,
                $this->obtenerIP(),
                $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
                $tipo_escaneo,
                $datos_adicionales ? json_encode($datos_adicionales, JSON_UNESCAPED_UNICODE) : null
            ]);
            
            return [
                'success' => true,
                'producto' => $producto,
                'escaneo_id' => $this->db->lastInsertId()
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtener estadísticas de escaneos QR
     */
    public function obtenerEstadisticasQR($producto_id = null, $fecha_inicio = null, $fecha_fin = null) {
        $where_conditions = [];
        $params = [];
        
        if ($producto_id) {
            $where_conditions[] = "producto_id = ?";
            $params[] = $producto_id;
        }
        
        if ($fecha_inicio) {
            $where_conditions[] = "DATE(created_at) >= ?";
            $params[] = $fecha_inicio;
        }
        
        if ($fecha_fin) {
            $where_conditions[] = "DATE(created_at) <= ?";
            $params[] = $fecha_fin;
        }
        
        $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
        
        $query = "SELECT 
                    COUNT(*) as total_escaneos,
                    COUNT(DISTINCT producto_id) as productos_escaneados,
                    COUNT(DISTINCT usuario_id) as usuarios_unicos,
                    COUNT(CASE WHEN tipo_escaneo = 'venta' THEN 1 END) as escaneos_venta,
                    COUNT(CASE WHEN tipo_escaneo = 'consulta' THEN 1 END) as escaneos_consulta,
                    COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as escaneos_hoy
                  FROM qr_escaneos $where_clause";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener escaneos recientes
     */
    public function obtenerEscaneosRecientes($limite = 10) {
        $query = "SELECT qe.*, p.codigo, p.nombre as producto_nombre, u.nombre as usuario_nombre
                  FROM qr_escaneos qe
                  JOIN productos p ON qe.producto_id = p.id
                  LEFT JOIN usuarios u ON qe.usuario_id = u.id
                  ORDER BY qe.created_at DESC
                  LIMIT ?";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$limite]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Regenerar código QR para un producto
     */
    public function regenerarQR($producto_id) {
        try {
            // Generar nuevo código
            $query = "SELECT * FROM productos WHERE id = ? AND activo = 1";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$producto_id]);
            $producto = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$producto) {
                throw new Exception("Producto no encontrado");
            }
            
            $nuevo_qr = $this->generarCodigoUnico($producto);
            
            // Actualizar en base de datos
            $update_query = "UPDATE productos SET qr_code = ?, qr_generado_en = NOW() WHERE id = ?";
            $update_stmt = $this->db->prepare($update_query);
            $update_stmt->execute([$nuevo_qr, $producto_id]);
            
            return [
                'success' => true,
                'qr_code' => $nuevo_qr,
                'mensaje' => 'Código QR regenerado exitosamente'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtener IP del cliente
     */
    private function obtenerIP() {
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
     * Generar QR masivo para todos los productos sin QR
     */
    public function generarQRMasivo() {
        try {
            $query = "SELECT id FROM productos WHERE (qr_code IS NULL OR qr_code = '') AND activo = 1";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $productos = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $generados = 0;
            $errores = [];
            
            foreach ($productos as $producto_id) {
                $resultado = $this->generarQRProducto($producto_id);
                if ($resultado['success']) {
                    $generados++;
                } else {
                    $errores[] = "Producto ID $producto_id: " . $resultado['error'];
                }
            }
            
            return [
                'success' => true,
                'generados' => $generados,
                'total_productos' => count($productos),
                'errores' => $errores
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}

/**
 * Función helper para obtener instancia del generador QR
 */
function getQRGenerator() {
    static $qr_generator = null;
    if ($qr_generator === null) {
        $database = new Database();
        $qr_generator = new QRGenerator($database->getConnection());
    }
    return $qr_generator;
}
?>