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
                 rd.*,
                 t.nombre as tienda_nombre,
                 t.direccion as tienda_direccion,
                 u_encargado.nombre as encargado_nombre,
                 u_encargado.email as encargado_email,
                 u_gerente.nombre as gerente_nombre
              FROM reportes_diarios_encargado rd
              JOIN tiendas t ON rd.tienda_id = t.id
              JOIN usuarios u_encargado ON rd.encargado_id = u_encargado.id
              LEFT JOIN usuarios u_gerente ON rd.gerente_id = u_gerente.id
              WHERE rd.id = ?";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$reporte_id]);
    $reporte = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reporte) {
        echo json_encode(['success' => false, 'message' => 'Reporte no encontrado']);
        exit;
    }
    
    // Obtener datos del sistema para comparación
    $query_sistema = "SELECT 
                         COUNT(*) as cantidad_ventas,
                         COALESCE(SUM(total), 0) as total_sistema,
                         COALESCE(SUM(CASE WHEN metodo_pago = 'efectivo' THEN total ELSE 0 END), 0) as efectivo_sistema,
                         COALESCE(SUM(CASE WHEN metodo_pago = 'tarjeta' THEN total ELSE 0 END), 0) as tarjeta_sistema,
                         COALESCE(SUM(CASE WHEN metodo_pago = 'transferencia' THEN total ELSE 0 END), 0) as transferencia_sistema,
                         COALESCE(AVG(total), 0) as ticket_promedio
                      FROM ventas 
                      WHERE tienda_id = ? AND DATE(fecha) = ? AND estado = 'completada'";
    $stmt_sistema = $db->prepare($query_sistema);
    $stmt_sistema->execute([$reporte['tienda_id'], $reporte['fecha_reporte']]);
    $sistema = $stmt_sistema->fetch(PDO::FETCH_ASSOC);
    
    // Obtener ventas detalladas del día
    $query_ventas = "SELECT 
                        id, fecha, total, metodo_pago,
                        CASE WHEN vendedor_id IS NOT NULL THEN 
                            (SELECT nombre FROM vendedores WHERE id = vendedor_id)
                        ELSE 'Sin vendedor' END as vendedor_nombre
                     FROM ventas 
                     WHERE tienda_id = ? AND DATE(fecha) = ? AND estado = 'completada'
                     ORDER BY fecha DESC
                     LIMIT 20";
    $stmt_ventas = $db->prepare($query_ventas);
    $stmt_ventas->execute([$reporte['tienda_id'], $reporte['fecha_reporte']]);
    $ventas_detalle = $stmt_ventas->fetchAll(PDO::FETCH_ASSOC);
    
    // Calcular diferencias
    $diferencia_total = $reporte['total_general'] - $sistema['total_sistema'];
    $diferencia_efectivo = $reporte['total_efectivo'] - $sistema['efectivo_sistema'];
    $diferencia_tarjeta = $reporte['total_tarjeta'] - $sistema['tarjeta_sistema'];
    $diferencia_transferencia = $reporte['total_transferencia'] - $sistema['transferencia_sistema'];
    
    // Generar HTML del detalle
    ob_start();
    ?>
    <div class="row">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-store me-1"></i>Datos del Reporte
                    </h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <td><strong>Tienda:</strong></td>
                            <td><?= htmlspecialchars($reporte['tienda_nombre']) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Dirección:</strong></td>
                            <td><?= htmlspecialchars($reporte['tienda_direccion'] ?? 'No disponible') ?></td>
                        </tr>
                        <tr>
                            <td><strong>Encargado:</strong></td>
                            <td><?= htmlspecialchars($reporte['encargado_nombre']) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Email:</strong></td>
                            <td><?= htmlspecialchars($reporte['encargado_email'] ?? 'No disponible') ?></td>
                        </tr>
                        <tr>
                            <td><strong>Fecha Reporte:</strong></td>
                            <td><?= date('d/m/Y', strtotime($reporte['fecha_reporte'])) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Fecha Creación:</strong></td>
                            <td><?= date('d/m/Y H:i', strtotime($reporte['fecha_creacion'])) ?></td>
                        </tr>
                    </table>
                    
                    <h6 class="text-primary mt-3">Desglose Reportado</h6>
                    <table class="table table-sm">
                        <tr>
                            <td><i class="fas fa-money-bill text-success"></i> Efectivo:</td>
                            <td class="fw-bold">Q <?= number_format($reporte['total_efectivo'], 2) ?></td>
                        </tr>
                        <tr>
                            <td><i class="fas fa-credit-card text-primary"></i> Tarjeta:</td>
                            <td class="fw-bold">Q <?= number_format($reporte['total_tarjeta'], 2) ?></td>
                        </tr>
                        <tr>
                            <td><i class="fas fa-exchange-alt text-info"></i> Transferencia:</td>
                            <td class="fw-bold">Q <?= number_format($reporte['total_transferencia'], 2) ?></td>
                        </tr>
                        <tr>
                            <td><i class="fas fa-ellipsis-h text-secondary"></i> Otros:</td>
                            <td class="fw-bold">Q <?= number_format($reporte['total_otros'], 2) ?></td>
                        </tr>
                        <tr class="table-success">
                            <td><strong>Total General:</strong></td>
                            <td class="fw-bold">Q <?= number_format($reporte['total_general'], 2) ?></td>
                        </tr>
                    </table>
                    
                    <?php if ($reporte['observaciones']): ?>
                    <div class="mt-3">
                        <strong>Observaciones:</strong>
                        <p class="text-muted mt-1"><?= nl2br(htmlspecialchars($reporte['observaciones'])) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-desktop me-1"></i>Datos del Sistema
                    </h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <td><strong>Ventas Registradas:</strong></td>
                            <td><?= $sistema['cantidad_ventas'] ?></td>
                        </tr>
                        <tr>
                            <td><strong>Ticket Promedio:</strong></td>
                            <td>Q <?= number_format($sistema['ticket_promedio'], 2) ?></td>
                        </tr>
                    </table>
                    
                    <h6 class="text-info mt-3">Desglose Sistema</h6>
                    <table class="table table-sm">
                        <tr>
                            <td><i class="fas fa-money-bill text-success"></i> Efectivo:</td>
                            <td class="fw-bold">Q <?= number_format($sistema['efectivo_sistema'], 2) ?></td>
                        </tr>
                        <tr>
                            <td><i class="fas fa-credit-card text-primary"></i> Tarjeta:</td>
                            <td class="fw-bold">Q <?= number_format($sistema['tarjeta_sistema'], 2) ?></td>
                        </tr>
                        <tr>
                            <td><i class="fas fa-exchange-alt text-info"></i> Transferencia:</td>
                            <td class="fw-bold">Q <?= number_format($sistema['transferencia_sistema'], 2) ?></td>
                        </tr>
                        <tr class="table-info">
                            <td><strong>Total Sistema:</strong></td>
                            <td class="fw-bold">Q <?= number_format($sistema['total_sistema'], 2) ?></td>
                        </tr>
                    </table>
                    
                    <?php if (!empty($ventas_detalle)): ?>
                    <div class="mt-3">
                        <h6 class="text-info">Últimas Ventas Registradas</h6>
                        <div class="table-responsive" style="max-height: 200px; overflow-y: auto;">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Hora</th>
                                        <th>Total</th>
                                        <th>Método</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ventas_detalle as $venta): ?>
                                    <tr>
                                        <td>#<?= $venta['id'] ?></td>
                                        <td><?= date('H:i', strtotime($venta['fecha'])) ?></td>
                                        <td>Q <?= number_format($venta['total'], 2) ?></td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                <?= ucfirst($venta['metodo_pago']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h6 class="mb-0">
                        <i class="fas fa-balance-scale me-1"></i>Análisis de Diferencias
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="text-center">
                                <?php
                                $color_total = abs($diferencia_total) <= 10 ? 'success' : (abs($diferencia_total) <= 50 ? 'warning' : 'danger');
                                $icon_total = $diferencia_total >= 0 ? 'fa-arrow-up' : 'fa-arrow-down';
                                ?>
                                <i class="fas fa-dollar-sign fa-2x text-<?= $color_total ?> mb-2"></i>
                                <h6>Diferencia Total</h6>
                                <span class="badge bg-<?= $color_total ?>">
                                    <i class="fas <?= $icon_total ?>"></i>
                                    Q <?= number_format(abs($diferencia_total), 2) ?>
                                </span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <?php
                                $color_efectivo = abs($diferencia_efectivo) <= 10 ? 'success' : 'warning';
                                $icon_efectivo = $diferencia_efectivo >= 0 ? 'fa-arrow-up' : 'fa-arrow-down';
                                ?>
                                <i class="fas fa-money-bill fa-2x text-<?= $color_efectivo ?> mb-2"></i>
                                <h6>Diferencia Efectivo</h6>
                                <span class="badge bg-<?= $color_efectivo ?>">
                                    <i class="fas <?= $icon_efectivo ?>"></i>
                                    Q <?= number_format(abs($diferencia_efectivo), 2) ?>
                                </span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <?php
                                $color_tarjeta = abs($diferencia_tarjeta) <= 5 ? 'success' : 'warning';
                                $icon_tarjeta = $diferencia_tarjeta >= 0 ? 'fa-arrow-up' : 'fa-arrow-down';
                                ?>
                                <i class="fas fa-credit-card fa-2x text-<?= $color_tarjeta ?> mb-2"></i>
                                <h6>Diferencia Tarjeta</h6>
                                <span class="badge bg-<?= $color_tarjeta ?>">
                                    <i class="fas <?= $icon_tarjeta ?>"></i>
                                    Q <?= number_format(abs($diferencia_tarjeta), 2) ?>
                                </span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <?php
                                $porcentaje_diferencia = $sistema['total_sistema'] > 0 
                                    ? (abs($diferencia_total) / $sistema['total_sistema']) * 100 
                                    : 0;
                                $color_porcentaje = $porcentaje_diferencia <= 2 ? 'success' : ($porcentaje_diferencia <= 5 ? 'warning' : 'danger');
                                ?>
                                <i class="fas fa-percentage fa-2x text-<?= $color_porcentaje ?> mb-2"></i>
                                <h6>% de Diferencia</h6>
                                <span class="badge bg-<?= $color_porcentaje ?>">
                                    <?= number_format($porcentaje_diferencia, 2) ?>%
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <h6>Recomendación del Sistema:</h6>
                        <?php if (abs($diferencia_total) <= 10 && $porcentaje_diferencia <= 2): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                <strong>Diferencias mínimas:</strong> Se recomienda aprobar este reporte.
                            </div>
                        <?php elseif (abs($diferencia_total) <= 50 && $porcentaje_diferencia <= 5): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Diferencias moderadas:</strong> Revisar con el encargado antes de aprobar.
                            </div>
                        <?php else: ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-times-circle me-2"></i>
                                <strong>Diferencias significativas:</strong> Se requiere investigación antes de aprobar.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php if ($reporte['estado'] !== 'pendiente'): ?>
    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-history me-1"></i>Historial de Aprobaciones
                    </h6>
                </div>
                <div class="card-body">
                    <?php if ($reporte['gerente_nombre']): ?>
                    <div class="mb-2">
                        <strong>Gerente:</strong> <?= htmlspecialchars($reporte['gerente_nombre']) ?>
                        <span class="badge bg-<?= $reporte['estado'] === 'aprobado_gerente' ? 'success' : 'danger' ?> ms-2">
                            <?= $reporte['estado'] === 'aprobado_gerente' ? 'Aprobado' : 'Rechazado' ?>
                        </span>
                        <br><small class="text-muted">
                            <?= date('d/m/Y H:i', strtotime($reporte['fecha_revision_gerente'])) ?>
                        </small>
                        <?php if ($reporte['observaciones_gerente']): ?>
                            <br><em>"<?= htmlspecialchars($reporte['observaciones_gerente']) ?>"</em>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
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