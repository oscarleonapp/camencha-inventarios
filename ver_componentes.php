<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

verificarLogin();

$database = new Database();
$db = $database->getConnection();

$producto_id = $_GET['id'];

$query_producto = "SELECT * FROM productos WHERE id = ? AND tipo = 'conjunto'";
$stmt_producto = $db->prepare($query_producto);
$stmt_producto->bindParam(1, $producto_id);
$stmt_producto->execute();
$producto = $stmt_producto->fetch(PDO::FETCH_ASSOC);

if (!$producto) {
    header('Location: productos.php');
    exit();
}

$query_componentes = "SELECT pc.*, p.codigo, p.nombre, p.precio_venta 
                      FROM producto_componentes pc 
                      JOIN productos p ON pc.producto_elemento_id = p.id 
                      WHERE pc.producto_conjunto_id = ?";
$stmt_componentes = $db->prepare($query_componentes);
$stmt_componentes->bindParam(1, $producto_id);
$stmt_componentes->execute();
$componentes = $stmt_componentes->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Componentes del Producto - Sistema de Inventarios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Componentes de: <?php echo $producto['nombre']; ?></h2>
            <a href="productos.php" class="btn btn-secondary">Volver a Productos</a>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5>Información del Conjunto</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3"><strong>Código:</strong></div>
                    <div class="col-md-9"><?php echo $producto['codigo']; ?></div>
                </div>
                <div class="row">
                    <div class="col-md-3"><strong>Nombre:</strong></div>
                    <div class="col-md-9"><?php echo $producto['nombre']; ?></div>
                </div>
                <div class="row">
                    <div class="col-md-3"><strong>Descripción:</strong></div>
                    <div class="col-md-9"><?php echo $producto['descripcion']; ?></div>
                </div>
                <div class="row">
                    <div class="col-md-3"><strong>Precio de Venta:</strong></div>
                    <div class="col-md-9">$<?php echo number_format($producto['precio_venta'], 2); ?></div>
                </div>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header">
                <h5>Componentes del Conjunto</h5>
            </div>
            <div class="card-body">
                <?php if (count($componentes) > 0): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Nombre del Componente</th>
                                    <th>Cantidad Necesaria</th>
                                    <th>Precio Unitario</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $total_componentes = 0;
                                foreach ($componentes as $componente): 
                                    $subtotal = $componente['cantidad'] * $componente['precio_venta'];
                                    $total_componentes += $subtotal;
                                ?>
                                <tr>
                                    <td><?php echo $componente['codigo']; ?></td>
                                    <td><?php echo $componente['nombre']; ?></td>
                                    <td><?php echo $componente['cantidad']; ?></td>
                                    <td>$<?php echo number_format($componente['precio_venta'], 2); ?></td>
                                    <td>$<?php echo number_format($subtotal, 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-info">
                                    <td colspan="4"><strong>Total Costo de Componentes:</strong></td>
                                    <td><strong>$<?php echo number_format($total_componentes, 2); ?></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <div class="alert alert-info">
                        <strong>Análisis de Precios:</strong><br>
                        Costo total de componentes: $<?php echo number_format($total_componentes, 2); ?><br>
                        Precio de venta del conjunto: $<?php echo number_format($producto['precio_venta'], 2); ?><br>
                        Margen de ganancia: $<?php echo number_format($producto['precio_venta'] - $total_componentes, 2); ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        Este conjunto no tiene componentes definidos. 
                        <a href="productos.php">Agregar componentes</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>