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
    $query_reporte = "SELECT vrv.*, v.nombre as vendedor_nombre
                      FROM ventas_reportadas_vendedor vrv
                      JOIN vendedores v ON vrv.vendedor_id = v.id
                      WHERE vrv.id = ?";
    $stmt_reporte = $db->prepare($query_reporte);
    $stmt_reporte->execute([$reporte_id]);
    $reporte = $stmt_reporte->fetch(PDO::FETCH_ASSOC);
    
    if (!$reporte) {
        echo json_encode(['success' => false, 'message' => 'Reporte no encontrado']);
        exit;
    }
    
    // Buscar ventas similares en un rango de fechas (±3 días)
    $fecha_inicio = date('Y-m-d', strtotime($reporte['fecha_venta'] . ' -3 days'));
    $fecha_fin = date('Y-m-d', strtotime($reporte['fecha_venta'] . ' +3 days'));
    
    $query_ventas = "SELECT 
                        v.id,
                        v.fecha,
                        v.total,
                        v.vendedor_id,
                        v.tienda_id,
                        t.nombre as tienda_nombre,
                        vd.nombre as vendedor_actual,
                        u.nombre as usuario_nombre,
                        COUNT(dv.id) as cantidad_productos,
                        GROUP_CONCAT(CONCAT(p.nombre, ' (', dv.cantidad, ')') SEPARATOR ', ') as productos,
                        -- Calcular similitud
                        ABS(v.total - ?) as diferencia_total,
                        ABS(DATEDIFF(v.fecha, ?)) as diferencia_dias,
                        CASE 
                            WHEN ABS(v.total - ?) <= 1 THEN 0.4
                            WHEN ABS(v.total - ?) <= 5 THEN 0.3
                            WHEN ABS(v.total - ?) <= 10 THEN 0.2
                            WHEN ABS(v.total - ?) <= 50 THEN 0.1
                            ELSE 0
                        END +
                        CASE 
                            WHEN ABS(DATEDIFF(v.fecha, ?)) = 0 THEN 0.4
                            WHEN ABS(DATEDIFF(v.fecha, ?)) <= 1 THEN 0.3
                            WHEN ABS(DATEDIFF(v.fecha, ?)) <= 3 THEN 0.2
                            ELSE 0
                        END +
                        CASE 
                            WHEN v.vendedor_id = ? THEN 0.2
                            ELSE 0
                        END as puntaje_similitud
                     FROM ventas v
                     JOIN tiendas t ON v.tienda_id = t.id
                     JOIN usuarios u ON v.usuario_id = u.id
                     LEFT JOIN vendedores vd ON v.vendedor_id = vd.id
                     LEFT JOIN detalle_ventas dv ON v.id = dv.venta_id
                     LEFT JOIN productos p ON dv.producto_id = p.id
                     WHERE DATE(v.fecha) BETWEEN ? AND ?
                     AND v.estado = 'completada'
                     AND v.id NOT IN (
                         SELECT venta_id FROM ventas_reportadas_vendedor 
                         WHERE venta_id IS NOT NULL AND estado IN ('aprobado', 'pendiente')
                     )
                     GROUP BY v.id
                     HAVING puntaje_similitud > 0
                     ORDER BY puntaje_similitud DESC, diferencia_total ASC
                     LIMIT 10";
    
    $stmt_ventas = $db->prepare($query_ventas);
    $stmt_ventas->execute([
        $reporte['total_reportado'], // diferencia_total
        $reporte['fecha_venta'], // diferencia_dias
        $reporte['total_reportado'], // similitud total <= 1
        $reporte['total_reportado'], // similitud total <= 5
        $reporte['total_reportado'], // similitud total <= 10
        $reporte['total_reportado'], // similitud total <= 50
        $reporte['fecha_venta'], // similitud fecha = 0
        $reporte['fecha_venta'], // similitud fecha <= 1
        $reporte['fecha_venta'], // similitud fecha <= 3
        $reporte['vendedor_id'], // mismo vendedor
        $fecha_inicio,
        $fecha_fin
    ]);
    $ventas_candidatas = $stmt_ventas->fetchAll(PDO::FETCH_ASSOC);
    
    // Generar HTML
    ob_start();
    ?>
    <div class="row mb-3">
        <div class="col-12">
            <div class="alert alert-info">
                <h6><i class="fas fa-info-circle me-1"></i>Buscando matches para:</h6>
                <ul class="mb-0">
                    <li><strong>Vendedor:</strong> <?= htmlspecialchars($reporte['vendedor_nombre']) ?></li>
                    <li><strong>Fecha:</strong> <?= date('d/m/Y', strtotime($reporte['fecha_venta'])) ?></li>
                    <li><strong>Total:</strong> Q <?= number_format($reporte['total_reportado'], 2) ?></li>
                </ul>
            </div>
        </div>
    </div>
    
    <?php if (empty($ventas_candidatas)): ?>
        <div class="text-center py-4">
            <i class="fas fa-search fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">No se encontraron ventas similares</h5>
            <p class="text-muted">
                No hay ventas en el sistema que coincidan con los criterios de búsqueda 
                en el rango de fechas (±3 días).
            </p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Similitud</th>
                        <th>Venta ID</th>
                        <th>Fecha</th>
                        <th>Total</th>
                        <th>Diferencia</th>
                        <th>Tienda</th>
                        <th>Vendedor Actual</th>
                        <th>Productos</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ventas_candidatas as $venta): ?>
                    <tr>
                        <td>
                            <?php 
                            $puntaje = round($venta['puntaje_similitud'] * 100);
                            $color = $puntaje >= 80 ? 'success' : ($puntaje >= 60 ? 'warning' : 'danger');
                            ?>
                            <span class="badge bg-<?= $color ?>"><?= $puntaje ?>%</span>
                        </td>
                        <td>
                            <strong>#<?= $venta['id'] ?></strong>
                            <br><small class="text-muted">por <?= htmlspecialchars($venta['usuario_nombre']) ?></small>
                        </td>
                        <td>
                            <?= date('d/m/Y', strtotime($venta['fecha'])) ?>
                            <br><small class="text-muted">
                                <?php if ($venta['diferencia_dias'] == 0): ?>
                                    <span class="text-success">Mismo día</span>
                                <?php else: ?>
                                    <?= $venta['diferencia_dias'] ?> día<?= $venta['diferencia_dias'] != 1 ? 's' : '' ?> de diferencia
                                <?php endif; ?>
                            </small>
                        </td>
                        <td>
                            <strong>Q <?= number_format($venta['total'], 2) ?></strong>
                        </td>
                        <td>
                            <?php if ($venta['diferencia_total'] <= 1): ?>
                                <span class="text-success">
                                    <i class="fas fa-check-circle"></i>
                                    Q <?= number_format($venta['diferencia_total'], 2) ?>
                                </span>
                            <?php elseif ($venta['diferencia_total'] <= 10): ?>
                                <span class="text-warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    Q <?= number_format($venta['diferencia_total'], 2) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-danger">
                                    <i class="fas fa-times-circle"></i>
                                    Q <?= number_format($venta['diferencia_total'], 2) ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= htmlspecialchars($venta['tienda_nombre']) ?>
                        </td>
                        <td>
                            <?php if ($venta['vendedor_actual']): ?>
                                <?= htmlspecialchars($venta['vendedor_actual']) ?>
                                <?php if ($venta['vendedor_id'] == $reporte['vendedor_id']): ?>
                                    <br><span class="badge bg-success">Mismo vendedor</span>
                                <?php else: ?>
                                    <br><span class="badge bg-warning">Vendedor diferente</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">Sin vendedor</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <small class="text-muted">
                                <?= htmlspecialchars(substr($venta['productos'] ?: 'No disponible', 0, 100)) ?>
                                <?= strlen($venta['productos'] ?: '') > 100 ? '...' : '' ?>
                            </small>
                            <br><small>(<?= $venta['cantidad_productos'] ?> producto<?= $venta['cantidad_productos'] != 1 ? 's' : '' ?>)</small>
                        </td>
                        <td>
                            <button class="btn btn-primary btn-sm" 
                                    onclick="asignarMatch(<?= $reporte_id ?>, <?= $venta['id'] ?>, <?= round($venta['puntaje_similitud'], 2) ?>)">
                                <i class="fas fa-link me-1"></i>Asignar
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="mt-3">
            <div class="alert alert-secondary">
                <h6><i class="fas fa-lightbulb me-1"></i>Criterios de Similitud:</h6>
                <ul class="mb-0">
                    <li><strong>Total:</strong> Diferencia ≤ Q1.00 (40%), ≤ Q5.00 (30%), ≤ Q10.00 (20%), ≤ Q50.00 (10%)</li>
                    <li><strong>Fecha:</strong> Mismo día (40%), ±1 día (30%), ±3 días (20%)</li>
                    <li><strong>Vendedor:</strong> Mismo vendedor (+20%)</li>
                </ul>
            </div>
        </div>
    <?php endif; ?>
    
    <script>
    function asignarMatch(reporteId, ventaId, confianza) {
        if (!confirm('¿Está seguro de asignar esta venta al reporte del vendedor?')) {
            return;
        }
        
        fetch('../ajax/asignar_match_manual.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                reporte_id: reporteId,
                venta_id: ventaId,
                confianza_match: confianza
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarToast(data.message, 'success');
                setTimeout(() => {
                    bootstrap.Modal.getInstance(document.getElementById('modalBuscarMatch')).hide();
                    location.reload();
                }, 1500);
            } else {
                mostrarToast(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarToast('Error al asignar match', 'error');
        });
    }
    </script>
    <?php
    $html = ob_get_clean();
    
    echo json_encode([
        'success' => true,
        'html' => $html
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error en búsqueda: ' . $e->getMessage()
    ]);
}
?>