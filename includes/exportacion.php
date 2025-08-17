<?php
/**
 * Clase para manejo de exportaciones del sistema
 * Soporta múltiples formatos: CSV, Excel, JSON, PDF
 */

class Exportacion {
    private $db;
    private $config;
    
    public function __construct($database) {
        $this->db = $database;
        $this->config = cargarConfiguracion();
    }
    
    /**
     * Exportar datos a CSV
     */
    public function exportarCSV($datos, $headers, $nombreArchivo) {
        // Configurar headers para descarga
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $nombreArchivo . '.csv"');
        header('Cache-Control: max-age=0');
        
        // Crear output
        $output = fopen('php://output', 'w');
        
        // BOM para UTF-8 (para Excel)
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Escribir headers
        fputcsv($output, $headers, ';');
        
        // Escribir datos
        foreach ($datos as $fila) {
            fputcsv($output, $fila, ';');
        }
        
        fclose($output);
        exit();
    }
    
    /**
     * Exportar datos a JSON
     */
    public function exportarJSON($datos, $nombreArchivo) {
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $nombreArchivo . '.json"');
        header('Cache-Control: max-age=0');
        
        $json = json_encode([
            'exportado_en' => date('Y-m-d H:i:s'),
            'sistema' => $this->config['empresa_nombre'] ?? 'Sistema de Inventario',
            'total_registros' => count($datos),
            'datos' => $datos
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        echo $json;
        exit();
    }
    
    /**
     * Exportar datos a Excel (HTML table que Excel puede abrir)
     */
    public function exportarExcel($datos, $headers, $nombreArchivo, $titulo = '') {
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $nombreArchivo . '.xls"');
        header('Cache-Control: max-age=0');
        
        echo "\xEF\xBB\xBF"; // BOM para UTF-8
        
        echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel">';
        echo '<head><meta charset="UTF-8"></head>';
        echo '<body>';
        
        if ($titulo) {
            echo '<h1>' . htmlspecialchars($titulo) . '</h1>';
        }
        
        echo '<p>Exportado el: ' . date('d/m/Y H:i:s') . '</p>';
        echo '<p>Sistema: ' . htmlspecialchars($this->config['empresa_nombre'] ?? 'Sistema de Inventario') . '</p>';
        echo '<br>';
        
        echo '<table border="1">';
        echo '<thead><tr style="background-color: #f0f0f0; font-weight: bold;">';
        foreach ($headers as $header) {
            echo '<th>' . htmlspecialchars($header) . '</th>';
        }
        echo '</tr></thead>';
        
        echo '<tbody>';
        foreach ($datos as $fila) {
            echo '<tr>';
            foreach ($fila as $celda) {
                echo '<td>' . htmlspecialchars($celda) . '</td>';
            }
            echo '</tr>';
        }
        echo '</tbody>';
        
        echo '</table>';
        echo '</body></html>';
        exit();
    }
    
    /**
     * Generar PDF básico (requiere biblioteca externa en implementación completa)
     */
    public function exportarPDF($datos, $headers, $nombreArchivo, $titulo = '') {
        // Por ahora generaremos un HTML que puede convertirse a PDF
        header('Content-Type: text/html; charset=utf-8');
        
        echo '<!DOCTYPE html>';
        echo '<html><head>';
        echo '<meta charset="UTF-8">';
        echo '<title>' . htmlspecialchars($titulo ?: $nombreArchivo) . '</title>';
        echo '<style>';
        echo 'body { font-family: Arial, sans-serif; margin: 20px; }';
        echo 'table { width: 100%; border-collapse: collapse; margin-top: 20px; }';
        echo 'th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }';
        echo 'th { background-color: #f2f2f2; font-weight: bold; }';
        echo 'tr:nth-child(even) { background-color: #f9f9f9; }';
        echo '.header { margin-bottom: 20px; }';
        echo '@media print { body { margin: 0; } }';
        echo '</style>';
        echo '<script>window.onload = function() { window.print(); }</script>';
        echo '</head><body>';
        
        echo '<div class="header">';
        echo '<h1>' . htmlspecialchars($titulo ?: $nombreArchivo) . '</h1>';
        echo '<p><strong>Empresa:</strong> ' . htmlspecialchars($this->config['empresa_nombre'] ?? 'Sistema de Inventario') . '</p>';
        echo '<p><strong>Fecha de exportación:</strong> ' . date('d/m/Y H:i:s') . '</p>';
        echo '<p><strong>Total de registros:</strong> ' . count($datos) . '</p>';
        echo '</div>';
        
        echo '<table>';
        echo '<thead><tr>';
        foreach ($headers as $header) {
            echo '<th>' . htmlspecialchars($header) . '</th>';
        }
        echo '</tr></thead>';
        
        echo '<tbody>';
        foreach ($datos as $fila) {
            echo '<tr>';
            foreach ($fila as $celda) {
                echo '<td>' . htmlspecialchars($celda) . '</td>';
            }
            echo '</tr>';
        }
        echo '</tbody>';
        
        echo '</table>';
        echo '</body></html>';
        exit();
    }
    
    /**
     * Exportar usuarios
     */
    public function exportarUsuarios($formato = 'csv', $filtros = []) {
        $query = "SELECT u.id, u.nombre, u.email, r.nombre as rol, 
                         CASE WHEN u.activo = 1 THEN 'Activo' ELSE 'Inactivo' END as estado,
                         DATE_FORMAT(u.fecha_creacion, '%d/%m/%Y %H:%i') as fecha_registro
                  FROM usuarios u 
                  LEFT JOIN roles r ON u.rol_id = r.id";
        
        $where = [];
        $params = [];
        
        if (!empty($filtros['rol_id'])) {
            $where[] = "u.rol_id = ?";
            $params[] = $filtros['rol_id'];
        }
        
        if (!empty($filtros['activo'])) {
            $where[] = "u.activo = ?";
            $params[] = $filtros['activo'];
        }
        
        if (!empty($where)) {
            $query .= " WHERE " . implode(" AND ", $where);
        }
        
        $query .= " ORDER BY u.nombre";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $headers = ['ID', 'Nombre', 'Email', 'Rol', 'Estado', 'Fecha Registro'];
        $datos = [];
        
        foreach ($usuarios as $usuario) {
            $datos[] = [
                $usuario['id'],
                $usuario['nombre'],
                $usuario['email'],
                $usuario['rol'] ?: 'Sin rol',
                $usuario['estado'],
                $usuario['fecha_registro']
            ];
        }
        
        $nombreArchivo = 'usuarios_' . date('Y-m-d_H-i-s');
        
        switch ($formato) {
            case 'excel':
                $this->exportarExcel($datos, $headers, $nombreArchivo, 'Lista de Usuarios');
                break;
            case 'json':
                $this->exportarJSON($usuarios, $nombreArchivo);
                break;
            case 'pdf':
                $this->exportarPDF($datos, $headers, $nombreArchivo, 'Lista de Usuarios');
                break;
            default:
                $this->exportarCSV($datos, $headers, $nombreArchivo);
        }
    }
    
    /**
     * Exportar productos
     */
    public function exportarProductos($formato = 'csv', $filtros = []) {
        $query = "SELECT p.id, p.codigo, p.nombre, p.descripcion, COALESCE(c.nombre, 'Sin categoría') as categoria,
                         CASE WHEN p.tipo = 'elemento' THEN 'Elemento' ELSE 'Conjunto' END as tipo,
                         p.precio_compra, p.precio_venta,
                         CASE WHEN p.activo = 1 THEN 'Activo' ELSE 'Inactivo' END as estado,
                         DATE_FORMAT(p.fecha_creacion, '%d/%m/%Y') as fecha_creacion
                  FROM productos p
                  LEFT JOIN categorias c ON p.categoria_id = c.id";
        
        $where = [];
        $params = [];
        
        if (!empty($filtros['categoria'])) {
            $where[] = "c.nombre = ?";
            $params[] = $filtros['categoria'];
        }
        
        if (!empty($filtros['tipo'])) {
            $where[] = "p.tipo = ?";
            $params[] = $filtros['tipo'];
        }
        
        if (!empty($where)) {
            $query .= " WHERE " . implode(" AND ", $where);
        }
        
        $query .= " ORDER BY p.nombre";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $headers = ['ID', 'Código', 'Nombre', 'Descripción', 'Categoría', 'Tipo', 'Precio Compra', 'Precio Venta', 'Estado', 'Fecha Creación'];
        $datos = [];
        
        foreach ($productos as $producto) {
            $datos[] = [
                $producto['id'],
                $producto['codigo'],
                $producto['nombre'],
                $producto['descripcion'],
                $producto['categoria'],
                $producto['tipo'],
                number_format($producto['precio_compra'], 2),
                number_format($producto['precio_venta'], 2),
                $producto['estado'],
                $producto['fecha_creacion']
            ];
        }
        
        $nombreArchivo = 'productos_' . date('Y-m-d_H-i-s');
        
        switch ($formato) {
            case 'excel':
                $this->exportarExcel($datos, $headers, $nombreArchivo, 'Catálogo de Productos');
                break;
            case 'json':
                $this->exportarJSON($productos, $nombreArchivo);
                break;
            case 'pdf':
                $this->exportarPDF($datos, $headers, $nombreArchivo, 'Catálogo de Productos');
                break;
            default:
                $this->exportarCSV($datos, $headers, $nombreArchivo);
        }
    }
    
    /**
     * Exportar ventas
     */
    public function exportarVentas($formato = 'csv', $filtros = []) {
        $query = "SELECT v.id, DATE_FORMAT(v.fecha, '%d/%m/%Y %H:%i') as fecha,
                         t.nombre as tienda, u.nombre as usuario,
                         COALESCE(vend.nombre, 'Sin vendedor') as vendedor,
                         v.total, v.descuento, v.subtotal,
                         CASE 
                           WHEN v.estado = 'completada' THEN 'Completada'
                           WHEN v.estado = 'reembolsada' THEN 'Reembolsada'
                           ELSE 'Pendiente'
                         END as estado
                  FROM ventas v
                  JOIN tiendas t ON v.tienda_id = t.id
                  JOIN usuarios u ON v.usuario_id = u.id
                  LEFT JOIN vendedores vend ON v.vendedor_id = vend.id";
        
        $where = [];
        $params = [];
        
        if (!empty($filtros['fecha_inicio'])) {
            $where[] = "DATE(v.fecha) >= ?";
            $params[] = $filtros['fecha_inicio'];
        }
        
        if (!empty($filtros['fecha_fin'])) {
            $where[] = "DATE(v.fecha) <= ?";
            $params[] = $filtros['fecha_fin'];
        }
        
        if (!empty($filtros['tienda_id'])) {
            $where[] = "v.tienda_id = ?";
            $params[] = $filtros['tienda_id'];
        }
        
        if (!empty($where)) {
            $query .= " WHERE " . implode(" AND ", $where);
        }
        
        $query .= " ORDER BY v.fecha DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $headers = ['ID', 'Fecha', 'Tienda', 'Usuario', 'Vendedor', 'Total', 'Descuento', 'Subtotal', 'Estado'];
        $datos = [];
        
        foreach ($ventas as $venta) {
            $datos[] = [
                $venta['id'],
                $venta['fecha'],
                $venta['tienda'],
                $venta['usuario'],
                $venta['vendedor'],
                number_format($venta['total'], 2),
                number_format($venta['descuento'], 2),
                number_format($venta['subtotal'], 2),
                $venta['estado']
            ];
        }
        
        $nombreArchivo = 'ventas_' . date('Y-m-d_H-i-s');
        
        switch ($formato) {
            case 'excel':
                $this->exportarExcel($datos, $headers, $nombreArchivo, 'Reporte de Ventas');
                break;
            case 'json':
                $this->exportarJSON($ventas, $nombreArchivo);
                break;
            case 'pdf':
                $this->exportarPDF($datos, $headers, $nombreArchivo, 'Reporte de Ventas');
                break;
            default:
                $this->exportarCSV($datos, $headers, $nombreArchivo);
        }
    }
    
    /**
     * Exportar inventarios
     */
    public function exportarInventarios($formato = 'csv', $filtros = []) {
        $query = "SELECT p.codigo, p.nombre as producto, COALESCE(c.nombre, 'Sin categoría') as categoria,
                         t.nombre as tienda, i.cantidad, i.cantidad_minima,
                         (i.cantidad - i.cantidad_reparacion) as cantidad_disponible,
                         i.cantidad_reparacion,
                         CASE 
                           WHEN i.cantidad <= i.cantidad_minima THEN 'Stock Bajo'
                           WHEN i.cantidad = 0 THEN 'Sin Stock'
                           ELSE 'Stock Normal'
                         END as estado_stock,
                         DATE_FORMAT(i.updated_at, '%d/%m/%Y %H:%i') as ultima_actualizacion
                  FROM inventarios i
                  JOIN productos p ON i.producto_id = p.id
                  JOIN tiendas t ON i.tienda_id = t.id
                  LEFT JOIN categorias c ON p.categoria_id = c.id";
        
        $where = [];
        $params = [];
        
        if (!empty($filtros['tienda_id'])) {
            $where[] = "i.tienda_id = ?";
            $params[] = $filtros['tienda_id'];
        }
        
        if (!empty($filtros['categoria'])) {
            $where[] = "c.nombre = ?";
            $params[] = $filtros['categoria'];
        }
        
        if (!empty($filtros['stock_bajo'])) {
            $where[] = "i.cantidad <= i.cantidad_minima";
        }
        
        if (!empty($where)) {
            $query .= " WHERE " . implode(" AND ", $where);
        }
        
        $query .= " ORDER BY t.nombre, p.nombre";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $inventarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $headers = ['Código', 'Producto', 'Categoría', 'Tienda', 'Cantidad', 'Cantidad Mínima', 'Disponible', 'En Reparación', 'Estado Stock', 'Última Actualización'];
        $datos = [];
        
        foreach ($inventarios as $inventario) {
            $datos[] = [
                $inventario['codigo'],
                $inventario['producto'],
                $inventario['categoria'],
                $inventario['tienda'],
                $inventario['cantidad'],
                $inventario['cantidad_minima'],
                $inventario['cantidad_disponible'],
                $inventario['cantidad_reparacion'],
                $inventario['estado_stock'],
                $inventario['ultima_actualizacion']
            ];
        }
        
        $nombreArchivo = 'inventarios_' . date('Y-m-d_H-i-s');
        
        switch ($formato) {
            case 'excel':
                $this->exportarExcel($datos, $headers, $nombreArchivo, 'Estado de Inventarios');
                break;
            case 'json':
                $this->exportarJSON($inventarios, $nombreArchivo);
                break;
            case 'pdf':
                $this->exportarPDF($datos, $headers, $nombreArchivo, 'Estado de Inventarios');
                break;
            default:
                $this->exportarCSV($datos, $headers, $nombreArchivo);
        }
    }
}
?>