<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "<h3>Debug de Productos</h3>";

// Verificar si la tabla productos existe
try {
    $check_table = $db->query("SHOW TABLES LIKE 'productos'");
    if ($check_table->rowCount() > 0) {
        echo "<p>✅ Tabla 'productos' existe</p>";
    } else {
        echo "<p>❌ Tabla 'productos' NO existe</p>";
        exit;
    }
} catch (Exception $e) {
    echo "<p>❌ Error verificando tabla: " . $e->getMessage() . "</p>";
    exit;
}

// Verificar estructura de la tabla
try {
    $structure = $db->query("DESCRIBE productos");
    echo "<h4>Estructura de la tabla productos:</h4>";
    echo "<table border='1'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Default</th></tr>";
    while ($row = $structure->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p>❌ Error obteniendo estructura: " . $e->getMessage() . "</p>";
}

// Contar total de productos
try {
    $count_query = "SELECT COUNT(*) as total FROM productos";
    $count_stmt = $db->prepare($count_query);
    $count_stmt->execute();
    $count = $count_stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p><strong>Total productos en BD: " . $count['total'] . "</strong></p>";
} catch (Exception $e) {
    echo "<p>❌ Error contando productos: " . $e->getMessage() . "</p>";
}

// Contar productos activos
try {
    $active_query = "SELECT COUNT(*) as activos FROM productos WHERE activo = 1";
    $active_stmt = $db->prepare($active_query);
    $active_stmt->execute();
    $active = $active_stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p><strong>Productos activos: " . $active['activos'] . "</strong></p>";
} catch (Exception $e) {
    echo "<p>❌ Error contando productos activos: " . $e->getMessage() . "</p>";
}

// Mostrar todos los productos
try {
    $query = "SELECT id, codigo, nombre, tipo, activo FROM productos LIMIT 10";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h4>Primeros 10 productos:</h4>";
    if (count($productos) > 0) {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Código</th><th>Nombre</th><th>Tipo</th><th>Activo</th></tr>";
        foreach ($productos as $producto) {
            echo "<tr>";
            echo "<td>" . $producto['id'] . "</td>";
            echo "<td>" . $producto['codigo'] . "</td>";
            echo "<td>" . $producto['nombre'] . "</td>";
            echo "<td>" . $producto['tipo'] . "</td>";
            echo "<td>" . ($producto['activo'] ? '✅' : '❌') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>❌ No hay productos en la base de datos</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Error obteniendo productos: " . $e->getMessage() . "</p>";
}

// Verificar la consulta exacta que usa productos.php
try {
    $query_productos = "SELECT * FROM productos WHERE activo = 1 ORDER BY nombre";
    $stmt_productos = $db->prepare($query_productos);
    $stmt_productos->execute();
    $productos_activos = $stmt_productos->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h4>Productos activos (consulta de productos.php):</h4>";
    echo "<p>Cantidad encontrada: " . count($productos_activos) . "</p>";
} catch (Exception $e) {
    echo "<p>❌ Error con consulta de productos.php: " . $e->getMessage() . "</p>";
}
?>