<?php
require_once 'includes/utf8_config.php';
require_once 'config/database.php';

echo "<!DOCTYPE html>";
echo "<html lang='es'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<title>Test UTF-8</title>";
echo "</head>";
echo "<body>";
echo "<h1>Prueba de Codificación UTF-8</h1>";

$database = new Database();
$db = $database->getConnection();

$query = "SELECT id, nombre, descripcion FROM permisos WHERE id IN (31, 32)";
$stmt = $db->prepare($query);
$stmt->execute();
$permisos = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>Permisos desde Base de Datos (sin corrección):</h2>";
foreach ($permisos as $permiso) {
    echo "<p>ID: " . $permiso['id'] . " - " . $permiso['descripcion'] . "</p>";
}

echo "<h2>Permisos corregidos con función:</h2>";
foreach ($permisos as $permiso) {
    echo "<p>ID: " . $permiso['id'] . " - " . corregirTextoSistema($permiso['descripcion']) . "</p>";
}

echo "<h2>Pruebas de acentos directos:</h2>";
echo "<p>Configuración del sistema</p>";
echo "<p>Gestión de roles y permisos</p>";
echo "<p>Reparación de equipos</p>";
echo "<p>Administración general</p>";

echo "</body></html>";
?>