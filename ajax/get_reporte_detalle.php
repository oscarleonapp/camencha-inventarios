<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

verificarLogin();
verificarPermiso('ventas_ver');

$database = new Database();
$db = $database->getConnection();

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['reporte_id']) || !is_numeric($input['reporte_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de reporte inválido']);
    exit;
}

$reporte_id = (int)$input['reporte_id'];

try {
    // Obtener detalles del reporte
    $query = "SELECT 
                 vrv.*,
                 v.nombre as vendedor_nombre,
                 v.email as vendedor_email,
                 v.telefono as vendedor_telefono,
                 vt.id as venta_sistema_id,
                 vt.total as total_sistema,
                 vt.fecha as fecha_sistema,
                 vt.usuario_id,
                 vt.tienda_id,
                 t.nombre as tienda_nombre,
                 u.nombre as usuario_sistema_nombre
              FROM ventas_reportadas_vendedor vrv
              JOIN vendedores v ON vrv.vendedor_id = v.id
              LEFT JOIN ventas vt ON vrv.venta_sistema_id = vt.id
              LEFT JOIN tiendas t ON vt.tienda_id = t.id
              LEFT JOIN usuarios u ON vt.usuario_id = u.id
              WHERE vrv.id = ?";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$reporte_id]);
    $reporte = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reporte) {
        echo json_encode(['success' => false, 'message' => 'Reporte no encontrado']);
        exit;
    }
    
    // Si hay match con venta del sistema, obtener detalles de productos
    $productos_sistema = [];
    if ($reporte['venta_sistema_id']) {
        $query_productos = "SELECT 
                               dv.cantidad,
                               dv.precio_unitario,
                               dv.subtotal,
                               p.nombre as producto_nombre,
                               p.codigo as producto_codigo
                            FROM detalle_ventas dv
                            JOIN productos p ON dv.producto_id = p.id
                            WHERE dv.venta_id = ?
                            ORDER BY p.nombre";
        $stmt_productos = $db->prepare($query_productos);
        $stmt_productos->execute([$reporte['venta_sistema_id']]);
        $productos_sistema = $stmt_productos->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Calcular diferencias
    $diferencia_total = 0;
    $diferencia_fecha = null;
    if ($reporte['venta_sistema_id']) {
        $diferencia_total = abs($reporte['total_reportado'] - $reporte['total_sistema']);
        $fecha_reporte = new DateTime($reporte['fecha_venta']);
        $fecha_sistema = new DateTime($reporte['fecha_sistema']);
        $diferencia_fecha = $fecha_reporte->diff($fecha_sistema)->days;
    }
    
    // Generar HTML del detalle
    ob_start();
    ?>
    <div class="row">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-user me-1"></i>Reporte del Vendedor
                    </h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <td><strong>Vendedor:</strong></td>
                            <td><?= htmlspecialchars($reporte['vendedor_nombre']) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Email:</strong></td>
                            <td><?= htmlspecialchars($reporte['vendedor_email'] ?? 'No disponible') ?></td>
                        </tr>
                        <tr>
                            <td><strong>Teléfono:</strong></td>
                            <td><?= htmlspecialchars($reporte['vendedor_telefono'] ?? 'No disponible') ?></td>
                        </tr>
                        <tr>
                            <td><strong>Fecha Venta:</strong></td>
                            <td><?= date('d/m/Y', strtotime($reporte['fecha_venta'])) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Total Reportado:</strong></td>
                            <td class="fw-bold text-primary">Q <?= number_format($reporte['total_reportado'], 2) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Fecha Reporte:</strong></td>
                            <td><?= date('d/m/Y H:i', strtotime($reporte['fecha_reporte'])) ?></td>
                        </tr>
                    </table>
                    
                    <div class="mt-3">
                        <strong>Descripción de Productos:</strong>
                        <p class="text-muted mt-1"><?= nl2br(htmlspecialchars($reporte['descripcion'])) ?></p>
                    </div>
                    
                    <?php if ($reporte['observaciones_verificacion']): ?>
                    <div class="mt-3">
                        <strong>Observaciones:</strong>
                        <p class="text-muted mt-1"><?= nl2br(htmlspecialchars($reporte['observaciones_verificacion'])) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <?php if ($reporte['venta_sistema_id']): ?>
            <div class="card h-100">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-cash-register me-1"></i>Venta en Sistema
                    </h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <td><strong>ID Venta:</strong></td>
                            <td>#<?= $reporte['venta_sistema_id'] ?></td>
                        </tr>
                        <tr>
                            <td><strong>Tienda:</strong></td>
                            <td><?= htmlspecialchars($reporte['tienda_nombre']) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Usuario Sistema:</strong></td>
                            <td><?= htmlspecialchars($reporte['usuario_sistema_nombre'] ?? 'No disponible') ?></td>
                        </tr>
                        <tr>
                            <td><strong>Fecha Sistema:</strong></td>
                            <td><?= date('d/m/Y H:i', strtotime($reporte['fecha_sistema'])) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Total Sistema:</strong></td>
                            <td class="fw-bold text-success">Q <?= number_format($reporte['total_sistema'], 2) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Diferencia:</strong></td>
                            <td>
                                <?php 
                                $diferencia = abs($reporte['diferencia']);
                                $color = $diferencia <= 1 ? 'success' : ($diferencia <= 10 ? 'warning' : 'danger');
                                ?>
                                <span class="badge bg-<?= $color ?>">
                                    Q <?= number_format($diferencia, 2) ?>
                                </span>
                            </td>
                        </tr>
                    </table>
                    
                    <?php if (!empty($productos_sistema)): ?>
                    <div class="mt-3">
                        <strong>Productos en Sistema:</strong>
                        <div class="table-responsive mt-2" style="max-height: 200px; overflow-y: auto;">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th>Cantidad</th>
                                        <th>Precio</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($productos_sistema as $producto): ?>
                                    <tr>
                                        <td>
                                            <small>
                                                <?= htmlspecialchars($producto['producto_nombre']) ?>
                                                <br><span class="text-muted"><?= htmlspecialchars($producto['producto_codigo']) ?></span>
                                            </small>
                                        </td>
                                        <td><?= $producto['cantidad'] ?></td>
                                        <td>Q <?= number_format($producto['precio_unitario'], 2) ?></td>
                                        <td>Q <?= number_format($producto['subtotal'], 2) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php else: ?>
            <div class="card h-100">
                <div class="card-header bg-danger text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-exclamation-triangle me-1"></i>Sin Match en Sistema
                    </h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-search me-2"></i>
                        No se encontró una venta coincidente en el sistema automáticamente.
                    </div>
                    <p class="text-muted">
                        Puede usar la función de búsqueda manual para intentar encontrar 
                        una venta que coincida con este reporte.
                    </p>
                    <button class="btn btn-warning" onclick="buscarMatch(<?= $reporte_id ?>)">
                        <i class="fas fa-search me-1"></i>Buscar Match Manual
                    </button>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if ($reporte['venta_sistema_id']): ?>
    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-analytics me-1"></i>Análisis de Coincidencia
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="text-center">
                                <i class="fas fa-money-bill-wave fa-2x text-<?= $diferencia_total <= 1 ? 'success' : ($diferencia_total <= 10 ? 'warning' : 'danger') ?> mb-2"></i>
                                <h6>Diferencia en Total</h6>
                                <span class="badge bg-<?= $diferencia_total <= 1 ? 'success' : ($diferencia_total <= 10 ? 'warning' : 'danger') ?>">
                                    Q <?= number_format($diferencia_total, 2) ?>
                                </span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center">
                                <i class="fas fa-calendar fa-2x text-<?= $diferencia_fecha <= 1 ? 'success' : ($diferencia_fecha <= 7 ? 'warning' : 'danger') ?> mb-2"></i>
                                <h6>Diferencia en Fechas</h6>
                                <span class="badge bg-<?= $diferencia_fecha <= 1 ? 'success' : ($diferencia_fecha <= 7 ? 'warning' : 'danger') ?>">
                                    <?= $diferencia_fecha ?> día<?= $diferencia_fecha != 1 ? 's' : '' ?>
                                </span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center">
                                <?php 
                                $diferencia = abs($reporte['diferencia']);
                                $color_diferencia = $diferencia <= 1 ? 'success' : ($diferencia <= 10 ? 'warning' : 'danger');
                                ?>
                                <i class="fas fa-balance-scale fa-2x text-<?= $color_diferencia ?> mb-2"></i>
                                <h6>Precisión del Match</h6>
                                <span class="badge bg-<?= $color_diferencia ?>">
                                    Q <?= number_format($diferencia, 2) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <h6>Recomendación del Sistema:</h6>
                        <?php if ($diferencia_total <= 1 && $diferencia_fecha <= 1): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                <strong>Alta precisión:</strong> Se recomienda aprobar automáticamente este match.
                            </div>
                        <?php elseif ($diferencia_total <= 10 && $diferencia_fecha <= 7): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Precisión media:</strong> Revisar detalles antes de aprobar.
                            </div>
                        <?php else: ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-times-circle me-2"></i>
                                <strong>Baja precisión:</strong> Verificar cuidadosamente o buscar otro match.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <?php
    $html = ob_get_clean();
    
    echo json_encode([
        'success' => true,
        'html' => $html
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener detalle: ' . $e->getMessage()
    ]);
}
?>