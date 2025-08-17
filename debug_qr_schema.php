<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

try {
    // Check if qr_code column exists in productos table
    $stmt = $db->prepare("SHOW COLUMNS FROM productos LIKE 'qr_code'");
    $stmt->execute();
    $qr_column = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h3>QR Schema Debug</h3>";
    
    if ($qr_column) {
        echo "<p style='color: green;'>✅ Column 'qr_code' exists in productos table</p>";
        
        // Show column details
        echo "<h4>Column Details:</h4>";
        echo "<pre>" . print_r($qr_column, true) . "</pre>";
        
        // Check for qr_generado_en column too
        $stmt2 = $db->prepare("SHOW COLUMNS FROM productos LIKE 'qr_generado_en'");
        $stmt2->execute();
        $qr_date_column = $stmt2->fetch(PDO::FETCH_ASSOC);
        
        if ($qr_date_column) {
            echo "<p style='color: green;'>✅ Column 'qr_generado_en' also exists</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ Column 'qr_generado_en' is missing</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ Column 'qr_code' does NOT exist in productos table</p>";
        
        // Show current table structure
        echo "<h4>Current productos table structure:</h4>";
        $stmt_all = $db->prepare("DESCRIBE productos");
        $stmt_all->execute();
        $columns = $stmt_all->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td>{$col['Field']}</td>";
            echo "<td>{$col['Type']}</td>";
            echo "<td>{$col['Null']}</td>";
            echo "<td>{$col['Key']}</td>";
            echo "<td>{$col['Default']}</td>";
            echo "<td>{$col['Extra']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Check if qr_escaneos table exists
    echo "<h4>Checking qr_escaneos table:</h4>";
    try {
        $stmt3 = $db->prepare("SHOW TABLES LIKE 'qr_escaneos'");
        $stmt3->execute();
        $qr_table = $stmt3->fetch(PDO::FETCH_ASSOC);
        
        if ($qr_table) {
            echo "<p style='color: green;'>✅ Table 'qr_escaneos' exists</p>";
        } else {
            echo "<p style='color: red;'>❌ Table 'qr_escaneos' does NOT exist</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error checking qr_escaneos table: " . $e->getMessage() . "</p>";
    }
    
    // Check QR permissions
    echo "<h4>Checking QR permissions:</h4>";
    try {
        $stmt4 = $db->prepare("SELECT nombre FROM permisos WHERE nombre LIKE '%qr%'");
        $stmt4->execute();
        $qr_perms = $stmt4->fetchAll(PDO::FETCH_COLUMN);
        
        if (!empty($qr_perms)) {
            echo "<p style='color: green;'>✅ QR permissions found:</p>";
            echo "<ul>";
            foreach ($qr_perms as $perm) {
                echo "<li>$perm</li>";
            }
            echo "</ul>";
        } else {
            echo "<p style='color: red;'>❌ No QR permissions found</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error checking QR permissions: " . $e->getMessage() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Database error: " . $e->getMessage() . "</p>";
}
?>