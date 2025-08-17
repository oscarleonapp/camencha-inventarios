<?php
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'includes/config_functions.php';

verificarLogin();

$database = new Database();
$db = $database->getConnection();

$query_productos = "SELECT * FROM productos WHERE activo = 1 ORDER BY nombre";
$stmt_productos = $db->prepare($query_productos);
$stmt_productos->execute();
$productos = $stmt_productos->fetchAll(PDO::FETCH_ASSOC);

echo "<!DOCTYPE html>";
echo "<html><head><title>Test Productos</title>";
echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">';
echo "</head><body>";
echo "<div class='container mt-4'>";
echo "<h2>Test de Productos</h2>";

echo "<p><strong>Productos encontrados: " . count($productos) . "</strong></p>";

if (count($productos) > 0) {
    echo "<div class='table-responsive'>";
    echo "<table class='table table-striped'>";
    echo "<thead><tr><th>ID</th><th>Código</th><th>Nombre</th><th>Tipo</th><th>Precio Venta</th><th>Precio Compra</th><th>Activo</th></tr></thead>";
    echo "<tbody>";
    
    foreach ($productos as $producto) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($producto['id']) . "</td>";
        echo "<td>" . htmlspecialchars($producto['codigo']) . "</td>";
        echo "<td>" . htmlspecialchars($producto['nombre']) . "</td>";
        echo "<td>" . htmlspecialchars($producto['tipo']) . "</td>";
        echo "<td>$" . htmlspecialchars($producto['precio_venta']) . "</td>";
        echo "<td>$" . htmlspecialchars($producto['precio_compra']) . "</td>";
        echo "<td>" . ($producto['activo'] ? 'Sí' : 'No') . "</td>";
        echo "</tr>";
    }
    
    echo "</tbody></table>";
    echo "</div>";
} else {
    echo "<div class='alert alert-warning'>No se encontraron productos activos</div>";
}

echo "</div>";
echo "</body></html>";
?>