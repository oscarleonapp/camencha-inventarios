<?php
// Script de verificación de errores corregidos
// Ejecutar después de corregir los problemas de fecha_creacion

require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>Verificación de Errores Corregidos</h2>";
    
    // Verificar que las consultas funcionan correctamente
    echo "<h3>1. Verificando tabla usuarios...</h3>";
    $stmt = $db->prepare("SELECT id, nombre, email, fecha_creacion, activo FROM usuarios LIMIT 1");
    $stmt->execute();
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($usuario) {
        echo "✅ Tabla usuarios: OK<br>";
        echo "- Campo fecha_creacion existe: " . (isset($usuario['fecha_creacion']) ? "✅" : "❌") . "<br>";
    } else {
        echo "❌ No se pudieron obtener datos de usuarios<br>";
    }
    
    echo "<h3>2. Verificando tabla tiendas...</h3>";
    $stmt = $db->prepare("SELECT id, nombre, fecha_creacion FROM tiendas LIMIT 1");
    $stmt->execute();
    $tienda = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($tienda) {
        echo "✅ Tabla tiendas: OK<br>";
        echo "- Campo fecha_creacion existe: " . (isset($tienda['fecha_creacion']) ? "✅" : "❌") . "<br>";
    } else {
        echo "❌ No se pudieron obtener datos de tiendas<br>";
    }
    
    echo "<h3>3. Verificando variables de sesión...</h3>";
    session_start();
    $session_vars = ['usuario_id', 'usuario_nombre', 'usuario_email', 'rol', 'rol_id'];
    foreach ($session_vars as $var) {
        echo "- \$_SESSION['$var']: " . (isset($_SESSION[$var]) ? "✅ {$_SESSION[$var]}" : "❌ No definida") . "<br>";
    }
    
    echo "<h3>4. Verificando archivos corregidos...</h3>";
    $archivos_verificar = [
        'lista_encargados.php',
        'tiendas.php', 
        'usuarios.php',
        'configuracion.php',
        'admin_reset_sistema.php'
    ];
    
    foreach ($archivos_verificar as $archivo) {
        if (file_exists($archivo)) {
            $contenido = file_get_contents($archivo);
            $tiene_fecha_creacion = strpos($contenido, 'fecha_creacion') !== false;
            $tiene_usuario_rol = strpos($contenido, 'usuario_rol') !== false;
            
            echo "- $archivo:<br>";
            echo "  * fecha_creacion encontrada: " . ($tiene_fecha_creacion ? "✅ Corregido" : "❌ NO ENCONTRADO") . "<br>";
            echo "  * usuario_rol encontrada: " . ($tiene_usuario_rol ? "❌ AÚN EXISTE" : "✅ Corregido") . "<br>";
        } else {
            echo "- $archivo: ❌ Archivo no encontrado<br>";
        }
    }
    
    echo "<h3>✅ Verificación completada</h3>";
    echo "<p>Si todos los elementos muestran ✅, los errores han sido corregidos exitosamente.</p>";
    
} catch (Exception $e) {
    echo "<h3>❌ Error durante la verificación:</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2 { color: #2c3e50; }
h3 { color: #34495e; }
</style>