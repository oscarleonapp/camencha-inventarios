<?php
/**
 * Generador automático de códigos únicos para el sistema
 * Previene conflictos en base de datos y mantiene control centralizado
 */

class CodigoGenerator {
    private $db;
    private $cache = [];
    
    // Patrones de códigos por tipo de entidad
    private $patrones = [
        'producto' => ['prefijo' => 'PROD', 'longitud' => 8],
        'venta' => ['prefijo' => 'VTA', 'longitud' => 10],
        'usuario' => ['prefijo' => 'USR', 'longitud' => 6],
        'tienda' => ['prefijo' => 'TDA', 'longitud' => 4],
        'vendedor' => ['prefijo' => 'VND', 'longitud' => 6],
        'reparacion' => ['prefijo' => 'REP', 'longitud' => 8],
        'boleta' => ['prefijo' => 'BOL', 'longitud' => 8],
        'conjunto' => ['prefijo' => 'CNJ', 'longitud' => 8]
    ];
    
    public function __construct($database_connection) {
        $this->db = $database_connection;
    }
    
    /**
     * Genera un código único para el tipo de entidad especificado
     */
    public function generarCodigo($tipo_entidad, $parametros = []) {
        if (!isset($this->patrones[$tipo_entidad])) {
            throw new Exception("Tipo de entidad no soportado: $tipo_entidad");
        }
        
        $patron = $this->patrones[$tipo_entidad];
        $prefijo = $parametros['prefijo'] ?? $patron['prefijo'];
        $longitud = $parametros['longitud'] ?? $patron['longitud'];
        $incluir_fecha = $parametros['incluir_fecha'] ?? true;
        
        $intentos = 0;
        $max_intentos = 100;
        
        do {
            $codigo = $this->construirCodigo($prefijo, $longitud, $incluir_fecha, $tipo_entidad);
            $intentos++;
            
            if ($intentos > $max_intentos) {
                throw new Exception("No se pudo generar un código único después de $max_intentos intentos");
            }
            
        } while ($this->codigoExiste($codigo, $tipo_entidad));
        
        // Cachear el código generado temporalmente
        $this->cache[$tipo_entidad][] = $codigo;
        
        return $codigo;
    }
    
    /**
     * Construye el código basado en el patrón especificado
     */
    private function construirCodigo($prefijo, $longitud, $incluir_fecha, $tipo_entidad) {
        $codigo = $prefijo;
        
        if ($incluir_fecha) {
            $codigo .= '-' . date('Y');
        }
        
        // Obtener el siguiente número secuencial
        $siguiente_numero = $this->obtenerSiguienteNumero($tipo_entidad, $prefijo);
        $numero_formateado = str_pad($siguiente_numero, $longitud, '0', STR_PAD_LEFT);
        
        $codigo .= '-' . $numero_formateado;
        
        return $codigo;
    }
    
    /**
     * Obtiene el siguiente número secuencial para el tipo de entidad
     */
    private function obtenerSiguienteNumero($tipo_entidad, $prefijo) {
        $year = date('Y');
        $patron_busqueda = $prefijo . '-' . $year . '-%';
        
        $tabla = $this->obtenerTablaParaTipo($tipo_entidad);
        $campo_codigo = $this->obtenerCampoCodigoParaTipo($tipo_entidad);
        
        $query = "SELECT $campo_codigo FROM $tabla 
                  WHERE $campo_codigo LIKE ? 
                  ORDER BY id DESC LIMIT 1";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$patron_busqueda]);
        $ultimo_codigo = $stmt->fetchColumn();
        
        if (!$ultimo_codigo) {
            return 1;
        }
        
        // Extraer el número del último código
        $partes = explode('-', $ultimo_codigo);
        $ultimo_numero = intval(end($partes));
        
        return $ultimo_numero + 1;
    }
    
    /**
     * Verifica si un código ya existe en la base de datos
     */
    private function codigoExiste($codigo, $tipo_entidad) {
        // Verificar en cache primero
        if (isset($this->cache[$tipo_entidad]) && in_array($codigo, $this->cache[$tipo_entidad])) {
            return true;
        }
        
        $tabla = $this->obtenerTablaParaTipo($tipo_entidad);
        $campo_codigo = $this->obtenerCampoCodigoParaTipo($tipo_entidad);
        
        $query = "SELECT COUNT(*) FROM $tabla WHERE $campo_codigo = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$codigo]);
        
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Obtiene la tabla correspondiente al tipo de entidad
     */
    private function obtenerTablaParaTipo($tipo_entidad) {
        $tablas = [
            'producto' => 'productos',
            'venta' => 'ventas', 
            'usuario' => 'usuarios',
            'tienda' => 'tiendas',
            'vendedor' => 'vendedores',
            'reparacion' => 'reparaciones',
            'boleta' => 'boletas',
            'conjunto' => 'productos'
        ];
        
        return $tablas[$tipo_entidad] ?? 'productos';
    }
    
    /**
     * Obtiene el campo de código correspondiente al tipo de entidad
     */
    private function obtenerCampoCodigoParaTipo($tipo_entidad) {
        $campos = [
            'producto' => 'codigo',
            'venta' => 'codigo_venta',
            'usuario' => 'codigo_usuario', 
            'tienda' => 'codigo',
            'vendedor' => 'codigo_vendedor',
            'reparacion' => 'codigo_reparacion',
            'boleta' => 'numero',
            'conjunto' => 'codigo'
        ];
        
        return $campos[$tipo_entidad] ?? 'codigo';
    }
    
    /**
     * Genera múltiples códigos de una vez (útil para operaciones en lote)
     */
    public function generarCodigosLote($tipo_entidad, $cantidad, $parametros = []) {
        $codigos = [];
        
        for ($i = 0; $i < $cantidad; $i++) {
            $codigos[] = $this->generarCodigo($tipo_entidad, $parametros);
        }
        
        return $codigos;
    }
    
    /**
     * Valida el formato de un código según el tipo de entidad
     */
    public function validarFormatoCodigo($codigo, $tipo_entidad) {
        if (!isset($this->patrones[$tipo_entidad])) {
            return false;
        }
        
        $patron = $this->patrones[$tipo_entidad];
        $prefijo = $patron['prefijo'];
        
        // Patrón básico: PREFIJO-YYYY-NNNNNNNN
        $regex = '/^' . preg_quote($prefijo) . '-\d{4}-\d{' . $patron['longitud'] . '}$/';
        
        return preg_match($regex, $codigo) === 1;
    }
    
    /**
     * Obtiene estadísticas de códigos generados
     */
    public function obtenerEstadisticas($tipo_entidad = null) {
        $estadisticas = [];
        
        $tipos = $tipo_entidad ? [$tipo_entidad] : array_keys($this->patrones);
        
        foreach ($tipos as $tipo) {
            $tabla = $this->obtenerTablaParaTipo($tipo);
            $campo_codigo = $this->obtenerCampoCodigoParaTipo($tipo);
            $prefijo = $this->patrones[$tipo]['prefijo'];
            
            $query = "SELECT 
                        COUNT(*) as total,
                        COUNT(CASE WHEN $campo_codigo LIKE ? THEN 1 END) as con_patron,
                        MIN($campo_codigo) as primer_codigo,
                        MAX($campo_codigo) as ultimo_codigo
                      FROM $tabla 
                      WHERE $campo_codigo IS NOT NULL";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$prefijo . '-%']);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $estadisticas[$tipo] = $stats;
        }
        
        return $estadisticas;
    }
    
    /**
     * Migra códigos existentes al nuevo formato (si es necesario)
     */
    public function migrarCodigosExistentes($tipo_entidad, $dry_run = true) {
        $tabla = $this->obtenerTablaParaTipo($tipo_entidad);
        $campo_codigo = $this->obtenerCampoCodigoParaTipo($tipo_entidad);
        
        // Buscar registros sin código o con código inválido
        $query = "SELECT id, $campo_codigo FROM $tabla 
                  WHERE $campo_codigo IS NULL 
                     OR $campo_codigo = '' 
                     OR $campo_codigo NOT REGEXP '^[A-Z]{3}-[0-9]{4}-[0-9]+$'";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $resultado = [
            'total_registros' => count($registros),
            'codigos_generados' => [],
            'errores' => []
        ];
        
        if (!$dry_run && !empty($registros)) {
            $this->db->beginTransaction();
            
            try {
                foreach ($registros as $registro) {
                    $nuevo_codigo = $this->generarCodigo($tipo_entidad);
                    
                    $update_query = "UPDATE $tabla SET $campo_codigo = ? WHERE id = ?";
                    $update_stmt = $this->db->prepare($update_query);
                    $update_stmt->execute([$nuevo_codigo, $registro['id']]);
                    
                    $resultado['codigos_generados'][] = [
                        'id' => $registro['id'],
                        'codigo_anterior' => $registro[$campo_codigo],
                        'codigo_nuevo' => $nuevo_codigo
                    ];
                }
                
                $this->db->commit();
                
            } catch (Exception $e) {
                $this->db->rollback();
                $resultado['errores'][] = $e->getMessage();
            }
        }
        
        return $resultado;
    }
    
    /**
     * Limpia la cache de códigos
     */
    public function limpiarCache($tipo_entidad = null) {
        if ($tipo_entidad) {
            unset($this->cache[$tipo_entidad]);
        } else {
            $this->cache = [];
        }
    }
}
?>