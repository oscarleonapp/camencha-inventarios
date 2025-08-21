<?php
/**
 * Script para corregir problemas de UTF-8 en la base de datos
 * Ejecutar una sola vez para limpiar datos corruptos
 */

require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "<h1>Corrección masiva de UTF-8</h1>";

// Correcciones para la tabla permisos
$correcciones_permisos = [
    ['id' => 31, 'descripcion' => 'Configuración del sistema'],
    ['id' => 32, 'descripcion' => 'Gestión de roles y permisos'],
    ['id' => 15, 'descripcion' => 'Enviar a reparación'],
    ['id' => 16, 'descripcion' => 'Recibir de reparación']
];

echo "<h2>Corrigiendo permisos...</h2>";
foreach ($correcciones_permisos as $correccion) {
    try {
        $query = "UPDATE permisos SET descripcion = ? WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$correccion['descripcion'], $correccion['id']]);
        echo "<p>✅ Permiso ID {$correccion['id']}: {$correccion['descripcion']}</p>";
    } catch (Exception $e) {
        echo "<p>❌ Error en permiso ID {$correccion['id']}: " . $e->getMessage() . "</p>";
    }
}

// Verificar roles
echo "<h2>Verificando roles...</h2>";
$query_roles = "SELECT id, nombre, descripcion FROM roles";
$stmt_roles = $db->prepare($query_roles);
$stmt_roles->execute();
$roles = $stmt_roles->fetchAll(PDO::FETCH_ASSOC);

foreach ($roles as $rol) {
    echo "<p>Rol: {$rol['nombre']} - {$rol['descripcion']}</p>";
}

echo "<h2>Corrección completada</h2>";
echo "<p><a href='roles.php'>Ir a Gestión de Roles</a></p>";
?>