<?php
/**
 * Script para aplicar el esquema QR faltante
 * Ejecutar desde el navegador: http://localhost/inventario-claude/apply_qr_schema.php
 */

require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "<h2>Aplicando Esquema QR</h2>";

try {
    // Read the SQL file
    $sql_content = file_get_contents('fix_qr_schema.sql');
    
    if (!$sql_content) {
        throw new Exception("No se pudo leer el archivo fix_qr_schema.sql");
    }
    
    // Split by semicolons and execute each statement
    $statements = explode(';', $sql_content);
    $executed = 0;
    $errors = [];
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        
        // Skip empty statements and comments
        if (empty($statement) || strpos($statement, '--') === 0 || strpos($statement, 'USE ') === 0) {
            continue;
        }
        
        // Skip SET statements that are part of the dynamic SQL
        if (strpos($statement, 'SET @sql') !== false || 
            strpos($statement, 'PREPARE stmt') !== false || 
            strpos($statement, 'EXECUTE stmt') !== false ||
            strpos($statement, 'DEALLOCATE PREPARE') !== false) {
            continue;
        }
        
        try {
            echo "<p>Ejecutando: " . substr($statement, 0, 80) . "...</p>";
            $db->exec($statement);
            $executed++;
            echo "<p style='color: green;'>✅ Ejecutado correctamente</p>";
        } catch (Exception $e) {
            $errors[] = "Error ejecutando: " . substr($statement, 0, 50) . " - " . $e->getMessage();
            echo "<p style='color: orange;'>⚠️ " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<h3>Ejecutando ALTER TABLE statements manualmente...</h3>";
    
    // Manually add columns if they don't exist
    try {
        // Check if qr_code column exists
        $stmt = $db->prepare("SHOW COLUMNS FROM productos LIKE 'qr_code'");
        $stmt->execute();
        if ($stmt->rowCount() == 0) {
            echo "<p>Agregando columna qr_code...</p>";
            $db->exec("ALTER TABLE productos ADD COLUMN qr_code VARCHAR(255) NULL AFTER codigo");
            echo "<p style='color: green;'>✅ Columna qr_code agregada</p>";
        } else {
            echo "<p style='color: blue;'>ℹ️ Columna qr_code ya existe</p>";
        }
        
        // Check if qr_generado_en column exists
        $stmt = $db->prepare("SHOW COLUMNS FROM productos LIKE 'qr_generado_en'");
        $stmt->execute();
        if ($stmt->rowCount() == 0) {
            echo "<p>Agregando columna qr_generado_en...</p>";
            $db->exec("ALTER TABLE productos ADD COLUMN qr_generado_en TIMESTAMP NULL AFTER qr_code");
            echo "<p style='color: green;'>✅ Columna qr_generado_en agregada</p>";
        } else {
            echo "<p style='color: blue;'>ℹ️ Columna qr_generado_en ya existe</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error agregando columnas: " . $e->getMessage() . "</p>";
    }
    
    // Create qr_escaneos table
    try {
        echo "<p>Creando tabla qr_escaneos...</p>";
        $create_table_sql = "CREATE TABLE IF NOT EXISTS `qr_escaneos` (
          `id` bigint(20) PRIMARY KEY AUTO_INCREMENT,
          `producto_id` int(11) NOT NULL,
          `qr_code` varchar(255) NOT NULL,
          `usuario_id` int(11) DEFAULT NULL,
          `ip_address` varchar(45) DEFAULT NULL,
          `user_agent` text DEFAULT NULL,
          `tipo_escaneo` enum('venta','consulta','inventario') DEFAULT 'consulta',
          `datos_adicionales` longtext DEFAULT NULL,
          `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
          INDEX `idx_producto_id` (`producto_id`),
          INDEX `idx_qr_code` (`qr_code`),
          INDEX `idx_usuario_id` (`usuario_id`),
          INDEX `idx_fecha` (`created_at`),
          INDEX `idx_tipo` (`tipo_escaneo`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $db->exec($create_table_sql);
        echo "<p style='color: green;'>✅ Tabla qr_escaneos creada/verificada</p>";
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠️ Error creando tabla qr_escaneos: " . $e->getMessage() . "</p>";
    }
    
    // Add permissions
    try {
        echo "<p>Agregando permisos QR...</p>";
        $permisos_sql = "INSERT IGNORE INTO permisos (nombre, descripcion, modulo, orden, activo) VALUES
        ('productos_qr_generar', 'Generar códigos QR para productos', 'productos', 150, 1),
        ('productos_qr_descargar', 'Descargar códigos QR de productos', 'productos', 151, 1),
        ('productos_qr_escanear', 'Escanear códigos QR en ventas', 'ventas', 152, 1),
        ('productos_qr_reportes', 'Ver reportes de escaneos QR', 'reportes', 153, 1)";
        
        $db->exec($permisos_sql);
        echo "<p style='color: green;'>✅ Permisos QR agregados</p>";
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠️ Error agregando permisos: " . $e->getMessage() . "</p>";
    }
    
    // Assign permissions to admin role
    try {
        echo "<p>Asignando permisos al rol admin...</p>";
        $admin_perms_sql = "INSERT IGNORE INTO rol_permisos (rol_id, permiso_id, puede_crear, puede_leer, puede_actualizar, puede_eliminar)
        SELECT 1, p.id, 1, 1, 1, 1
        FROM permisos p 
        WHERE p.nombre IN ('productos_qr_generar', 'productos_qr_descargar', 'productos_qr_escanear', 'productos_qr_reportes')";
        
        $db->exec($admin_perms_sql);
        echo "<p style='color: green;'>✅ Permisos asignados al rol admin</p>";
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠️ Error asignando permisos: " . $e->getMessage() . "</p>";
    }
    
    echo "<h3>Resumen:</h3>";
    echo "<p>Statements ejecutados: $executed</p>";
    
    if (!empty($errors)) {
        echo "<h4>Errores encontrados:</h4>";
        foreach ($errors as $error) {
            echo "<p style='color: orange;'>⚠️ $error</p>";
        }
    }
    
    // Final verification
    echo "<h3>Verificación Final:</h3>";
    
    // Check columns
    $stmt = $db->prepare("SHOW COLUMNS FROM productos WHERE Field IN ('qr_code', 'qr_generado_en')");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Columnas QR encontradas en productos:</p>";
    foreach ($columns as $col) {
        echo "<p style='color: green;'>✅ {$col['Field']} - {$col['Type']}</p>";
    }
    
    // Check table
    $stmt = $db->prepare("SHOW TABLES LIKE 'qr_escaneos'");
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✅ Tabla qr_escaneos existe</p>";
    } else {
        echo "<p style='color: red;'>❌ Tabla qr_escaneos NO existe</p>";
    }
    
    // Check permissions
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM permisos WHERE nombre LIKE '%qr%'");
    $stmt->execute();
    $perm_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "<p style='color: green;'>✅ Permisos QR encontrados: $perm_count</p>";
    
    echo "<h3 style='color: green;'>🎉 Esquema QR aplicado exitosamente!</h3>";
    echo "<p><a href='productos.php'>Ir a Gestión de Productos</a> para probar la funcionalidad QR</p>";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ Error crítico:</h3>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
}
?>