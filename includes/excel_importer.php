<?php
/**
 * Importador de productos desde archivos Excel
 * Soporta tanto elementos individuales como conjuntos
 */

class ExcelImporter {
    private $db;
    private $codigoGenerator;
    private $errores = [];
    private $advertencias = [];
    private $procesados = 0;
    private $saltados = 0;
    
    public function __construct($database_connection, $codigo_generator) {
        $this->db = $database_connection;
        $this->codigoGenerator = $codigo_generator;
    }
    
    /**
     * Procesa un archivo Excel y importa los productos
     */
    public function procesarArchivo($archivo_path, $opciones = []) {
        $resultado = [
            'exito' => false,
            'procesados' => 0,
            'saltados' => 0,
            'errores' => [],
            'advertencias' => [],
            'detalles' => []
        ];
        
        try {
            // Verificar que el archivo existe y es válido
            if (!file_exists($archivo_path)) {
                throw new Exception("El archivo no existe: $archivo_path");
            }
            
            // Leer el archivo Excel usando SimpleXLSX
            $datos = $this->leerArchivoExcel($archivo_path);
            
            if (empty($datos)) {
                throw new Exception("El archivo Excel no contiene datos válidos");
            }
            
            // Validar estructura del Excel
            $this->validarEstructura($datos);
            
            // Procesar los datos en transacción
            $this->db->beginTransaction();
            
            $detalles = $this->procesarDatos($datos, $opciones);
            
            $this->db->commit();
            
            $resultado = [
                'exito' => true,
                'procesados' => $this->procesados,
                'saltados' => $this->saltados,
                'errores' => $this->errores,
                'advertencias' => $this->advertencias,
                'detalles' => $detalles
            ];
            
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            
            $resultado['errores'][] = $e->getMessage();
            
            // Log del error
            require_once 'logger.php';
            getLogger()->error('excel_import_error', 'productos', 
                "Error en importación Excel: {$e->getMessage()}", [
                    'archivo' => $archivo_path,
                    'trace' => $e->getTraceAsString()
                ]
            );
        }
        
        return $resultado;
    }
    
    /**
     * Lee el archivo Excel usando diferentes métodos según disponibilidad
     */
    private function leerArchivoExcel($archivo_path) {
        $extension = strtolower(pathinfo($archivo_path, PATHINFO_EXTENSION));
        
        if ($extension === 'csv') {
            return $this->leerCSV($archivo_path);
        }
        
        // Intentar con SimpleXLSX primero
        if (class_exists('SimpleXLSX')) {
            return $this->leerConSimpleXLSX($archivo_path);
        }
        
        // Si no está disponible SimpleXLSX, intentar convertir a CSV
        return $this->leerComCSVConvertido($archivo_path);
    }
    
    /**
     * Lee archivo CSV
     */
    private function leerCSV($archivo_path) {
        $datos = [];
        $fila = 0;
        
        if (($handle = fopen($archivo_path, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $datos[] = $data;
                $fila++;
            }
            fclose($handle);
        }
        
        return $datos;
    }
    
    /**
     * Lee Excel con SimpleXLSX (si está disponible)
     */
    private function leerConSimpleXLSX($archivo_path) {
        try {
            require_once 'SimpleXLSX.php';
            
            if ($xlsx = SimpleXLSX::parse($archivo_path)) {
                return $xlsx->rows();
            } else {
                throw new Exception('Error leyendo Excel: ' . SimpleXLSX::parseError());
            }
        } catch (Exception $e) {
            throw new Exception("Error procesando Excel: {$e->getMessage()}");
        }
    }
    
    /**
     * Método alternativo para leer Excel como CSV
     */
    private function leerComCSVConvertido($archivo_path) {
        // Instrucciones para el usuario sobre conversión manual
        throw new Exception("Para importar archivos Excel (.xlsx/.xls), por favor conviértelos a formato CSV primero. Pasos: 1) Abrir archivo en Excel, 2) Guardar como CSV, 3) Subir el archivo CSV.");
    }
    
    /**
     * Valida que el Excel tenga la estructura correcta
     */
    private function validarEstructura($datos) {
        if (count($datos) < 2) {
            throw new Exception("El archivo debe tener al menos una fila de encabezados y una fila de datos");
        }
        
        $encabezados = $datos[0];
        $campos_requeridos = ['nombre', 'tipo', 'precio_venta', 'precio_compra'];
        
        foreach ($campos_requeridos as $campo) {
            if (!in_array($campo, $encabezados)) {
                throw new Exception("Falta el campo requerido: '$campo' en los encabezados");
            }
        }
        
        return true;
    }
    
    /**
     * Procesa los datos del Excel
     */
    private function procesarDatos($datos, $opciones) {
        $encabezados = array_shift($datos); // Remover encabezados
        $detalles = [];
        
        // Filtrar filas de instrucciones y separadores de la plantilla
        $datos_filtrados = [];
        foreach ($datos as $fila) {
            // Saltar filas vacías o que son instrucciones/separadores
            if (empty($fila[0]) || 
                strpos($fila[0], 'REQUERIDO:') !== false || 
                strpos($fila[0], '====') !== false ||
                strpos($fila[0], 'OPCIONAL:') !== false) {
                continue;
            }
            $datos_filtrados[] = $fila;
        }
        
        foreach ($datos_filtrados as $fila_num => $fila) {
            $fila_real = $fila_num + 2; // +2 porque removimos encabezados y Excel empieza en 1
            
            try {
                $detalle_fila = $this->procesarFila($fila, $encabezados, $fila_real, $opciones);
                $detalles[] = $detalle_fila;
                
                if ($detalle_fila['procesado']) {
                    $this->procesados++;
                } else {
                    $this->saltados++;
                }
                
            } catch (Exception $e) {
                $this->errores[] = "Fila $fila_real: {$e->getMessage()}";
                $this->saltados++;
                
                $detalles[] = [
                    'fila' => $fila_real,
                    'procesado' => false,
                    'error' => $e->getMessage(),
                    'datos' => $this->mapearFila($fila, $encabezados)
                ];
            }
        }
        
        return $detalles;
    }
    
    /**
     * Procesa una fila individual
     */
    private function procesarFila($fila, $encabezados, $fila_num, $opciones) {
        $datos = $this->mapearFila($fila, $encabezados);
        
        // Validar datos básicos
        $this->validarDatosFila($datos, $fila_num);
        
        // Determinar tipo de producto
        $tipo = strtolower(trim($datos['tipo']));
        if (!in_array($tipo, ['elemento', 'conjunto'])) {
            throw new Exception("Tipo de producto inválido: '{$datos['tipo']}'. Debe ser 'elemento' o 'conjunto'");
        }
        
        // Generar código automático
        $tipo_codigo = ($tipo === 'conjunto') ? 'conjunto' : 'producto';
        $codigo = $this->codigoGenerator->generarCodigo($tipo_codigo);
        
        // Procesar proveedor
        $proveedor_id = $this->procesarProveedor($datos);
        
        // Crear el producto
        $producto_id = $this->crearProducto([
            'codigo' => $codigo,
            'nombre' => trim($datos['nombre']),
            'descripcion' => trim($datos['descripcion'] ?? ''),
            'precio_venta' => (float)$datos['precio_venta'],
            'precio_compra' => (float)$datos['precio_compra'],
            'tipo' => $tipo,
            'proveedor_id' => $proveedor_id
        ]);
        
        $detalle = [
            'fila' => $fila_num,
            'procesado' => true,
            'producto_id' => $producto_id,
            'codigo_generado' => $codigo,
            'nombre' => $datos['nombre'],
            'tipo' => $tipo,
            'componentes_agregados' => 0
        ];
        
        // Si es conjunto, procesar componentes
        if ($tipo === 'conjunto') {
            $componentes_agregados = $this->procesarComponentes($producto_id, $datos, $fila_num);
            $detalle['componentes_agregados'] = $componentes_agregados;
        }
        
        return $detalle;
    }
    
    /**
     * Mapea una fila de datos con los encabezados
     */
    private function mapearFila($fila, $encabezados) {
        $datos = [];
        
        foreach ($encabezados as $index => $encabezado) {
            $valor = isset($fila[$index]) ? trim($fila[$index]) : '';
            $datos[strtolower(trim($encabezado))] = $valor;
        }
        
        return $datos;
    }
    
    /**
     * Valida los datos de una fila
     */
    private function validarDatosFila($datos, $fila_num) {
        // Validar nombre
        if (empty($datos['nombre'])) {
            throw new Exception("El nombre del producto es requerido");
        }
        
        // Validar precios
        if (!is_numeric($datos['precio_venta']) || (float)$datos['precio_venta'] < 0) {
            throw new Exception("Precio de venta inválido: '{$datos['precio_venta']}'");
        }
        
        if (!is_numeric($datos['precio_compra']) || (float)$datos['precio_compra'] < 0) {
            throw new Exception("Precio de compra inválido: '{$datos['precio_compra']}'");
        }
        
        // Validar que precio de venta >= precio de compra
        if ((float)$datos['precio_venta'] < (float)$datos['precio_compra']) {
            $this->advertencias[] = "Fila $fila_num: Precio de venta menor que precio de compra";
        }
    }
    
    /**
     * Procesa el proveedor (busca por nombre o crea referencia)
     */
    private function procesarProveedor($datos) {
        if (empty($datos['proveedor'])) {
            return null;
        }
        
        $nombre_proveedor = trim($datos['proveedor']);
        
        // Buscar proveedor existente
        $stmt = $this->db->prepare("SELECT id FROM proveedores WHERE nombre = ? AND activo = 1");
        $stmt->execute([$nombre_proveedor]);
        $proveedor = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($proveedor) {
            return $proveedor['id'];
        }
        
        // Si no existe, agregarlo a advertencias pero no crear automáticamente
        $this->advertencias[] = "Proveedor '{$nombre_proveedor}' no encontrado - producto creado sin proveedor";
        return null;
    }
    
    /**
     * Crea un producto en la base de datos
     */
    private function crearProducto($datos) {
        $query = "INSERT INTO productos (codigo, nombre, descripcion, precio_venta, precio_compra, tipo, proveedor_id, activo) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, 1)";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $datos['codigo'],
            $datos['nombre'],
            $datos['descripcion'],
            $datos['precio_venta'],
            $datos['precio_compra'],
            $datos['tipo'],
            $datos['proveedor_id']
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Procesa componentes para conjuntos
     */
    private function procesarComponentes($producto_conjunto_id, $datos, $fila_num) {
        $componentes_agregados = 0;
        
        // Buscar columnas de componentes (componente_1, componente_2, etc.)
        $patron_componente = '/^componente_(\d+)$/';
        $patron_cantidad = '/^cantidad_(\d+)$/';
        
        $componentes = [];
        
        foreach ($datos as $campo => $valor) {
            if (preg_match($patron_componente, $campo, $matches)) {
                $numero = $matches[1];
                $componentes[$numero]['elemento'] = trim($valor);
            } elseif (preg_match($patron_cantidad, $campo, $matches)) {
                $numero = $matches[1];
                $componentes[$numero]['cantidad'] = (int)trim($valor);
            }
        }
        
        foreach ($componentes as $comp) {
            if (empty($comp['elemento'])) {
                continue;
            }
            
            $cantidad = $comp['cantidad'] ?? 1;
            
            // Buscar el elemento por código o nombre
            $elemento_id = $this->buscarElemento($comp['elemento']);
            
            if (!$elemento_id) {
                $this->advertencias[] = "Fila $fila_num: Componente '{$comp['elemento']}' no encontrado - saltado";
                continue;
            }
            
            // Agregar componente
            $stmt = $this->db->prepare("INSERT INTO producto_componentes (producto_conjunto_id, producto_elemento_id, cantidad) VALUES (?, ?, ?)");
            $stmt->execute([$producto_conjunto_id, $elemento_id, $cantidad]);
            
            $componentes_agregados++;
        }
        
        return $componentes_agregados;
    }
    
    /**
     * Busca un elemento por código o nombre
     */
    private function buscarElemento($busqueda) {
        // Buscar por código primero
        $stmt = $this->db->prepare("SELECT id FROM productos WHERE codigo = ? AND tipo = 'elemento' AND activo = 1");
        $stmt->execute([$busqueda]);
        $producto = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($producto) {
            return $producto['id'];
        }
        
        // Buscar por nombre
        $stmt = $this->db->prepare("SELECT id FROM productos WHERE nombre = ? AND tipo = 'elemento' AND activo = 1");
        $stmt->execute([$busqueda]);
        $producto = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $producto ? $producto['id'] : null;
    }
    
    /**
     * Genera una plantilla Excel de ejemplo completa
     */
    public function generarPlantilla() {
        $plantilla = [
            // Encabezados con descripción
            ['nombre', 'tipo', 'descripcion', 'precio_venta', 'precio_compra', 'proveedor', 'componente_1', 'cantidad_1', 'componente_2', 'cantidad_2', 'componente_3', 'cantidad_3', 'componente_4', 'cantidad_4', 'componente_5', 'cantidad_5'],
            
            // Fila de instrucciones (será eliminada automáticamente)
            ['REQUERIDO: Nombre del producto', 'REQUERIDO: elemento o conjunto', 'OPCIONAL: Descripción detallada', 'REQUERIDO: Precio en números', 'REQUERIDO: Precio en números', 'OPCIONAL: Nombre del proveedor', 'OPCIONAL: Código o nombre del componente', 'OPCIONAL: Cantidad numérica', 'Componente 2 (opcional)', 'Cantidad 2', 'Componente 3 (opcional)', 'Cantidad 3', 'Componente 4 (opcional)', 'Cantidad 4', 'Componente 5 (opcional)', 'Cantidad 5'],
            
            // Separador visual
            ['==== EJEMPLOS DE ELEMENTOS INDIVIDUALES ====', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
            
            // Ejemplos de elementos
            ['Laptop Dell Inspiron 15', 'elemento', 'Laptop para oficina con procesador Intel i5, 8GB RAM, 256GB SSD', '15000.00', '12000.00', 'Dell Technologies', '', '', '', '', '', '', '', '', '', ''],
            ['Mouse Inalámbrico Logitech', 'elemento', 'Mouse óptico inalámbrico con receptor USB', '250.00', '180.00', 'Logitech', '', '', '', '', '', '', '', '', '', ''],
            ['Teclado Mecánico RGB', 'elemento', 'Teclado mecánico con switches Cherry MX Blue', '800.00', '600.00', 'Corsair', '', '', '', '', '', '', '', '', '', ''],
            ['Monitor Samsung 24"', 'elemento', 'Monitor LED 24 pulgadas Full HD', '2500.00', '2000.00', 'Samsung', '', '', '', '', '', '', '', '', '', ''],
            ['Alfombrilla Gaming', 'elemento', 'Alfombrilla antideslizante para gaming', '150.00', '100.00', 'Razer', '', '', '', '', '', '', '', '', '', ''],
            ['Webcam HD 1080p', 'elemento', 'Webcam con micrófono integrado', '800.00', '600.00', 'Logitech', '', '', '', '', '', '', '', '', '', ''],
            ['Audífonos Bluetooth', 'elemento', 'Audífonos inalámbricos con cancelación de ruido', '1200.00', '900.00', 'Sony', '', '', '', '', '', '', '', '', '', ''],
            
            // Separador visual
            ['==== EJEMPLOS DE CONJUNTOS/KITS ====', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
            
            // Ejemplos de conjuntos
            ['Kit Oficina Completo', 'conjunto', 'Kit completo para estación de trabajo', '19000.00', '15000.00', '', 'Laptop Dell Inspiron 15', '1', 'Mouse Inalámbrico Logitech', '1', 'Teclado Mecánico RGB', '1', '', '', '', ''],
            ['Kit Gaming Pro', 'conjunto', 'Kit profesional para gamers', '5000.00', '3800.00', '', 'Teclado Mecánico RGB', '1', 'Mouse Inalámbrico Logitech', '1', 'Alfombrilla Gaming', '1', 'Audífonos Bluetooth', '1', '', ''],
            ['Kit Home Office', 'conjunto', 'Kit básico para trabajo desde casa', '18500.00', '14500.00', '', 'Laptop Dell Inspiron 15', '1', 'Monitor Samsung 24"', '1', 'Webcam HD 1080p', '1', '', '', '', ''],
            ['Kit Doble Pantalla', 'conjunto', 'Kit de productividad con doble monitor', '21000.00', '16000.00', '', 'Laptop Dell Inspiron 15', '1', 'Monitor Samsung 24"', '2', 'Teclado Mecánico RGB', '1', 'Mouse Inalámbrico Logitech', '1', '', ''],
            
            // Separador visual
            ['==== PLANTILLA VACÍA PARA SUS PRODUCTOS ====', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
            
            // Filas vacías para que el cliente llene
            ['', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
            ['', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
            ['', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
            ['', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
            ['', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
            ['', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
            ['', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
            ['', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
            ['', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
            ['', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '']
        ];
        
        return $plantilla;
    }
    
    /**
     * Obtiene estadísticas de la importación
     */
    public function obtenerEstadisticas() {
        return [
            'procesados' => $this->procesados,
            'saltados' => $this->saltados,
            'errores' => count($this->errores),
            'advertencias' => count($this->advertencias)
        ];
    }
}
?>