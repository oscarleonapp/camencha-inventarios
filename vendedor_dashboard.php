<?php
$titulo = "Dashboard Vendedor - Sistema de Inventarios";
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'includes/config_functions.php';
require_once 'includes/csrf_protection.php';

verificarLogin();

$database = new Database();
$db = $database->getConnection();

// Verificar que el usuario es un vendedor
$query_vendedor = "SELECT v.*, u.nombre as usuario_nombre 
                   FROM vendedores v 
                   JOIN usuarios u ON v.usuario_id = u.id 
                   WHERE v.usuario_id = ? AND v.activo = 1";
$stmt_vendedor = $db->prepare($query_vendedor);
$stmt_vendedor->execute([$_SESSION['usuario_id']]);
$vendedor = $stmt_vendedor->fetch(PDO::FETCH_ASSOC);

if (!$vendedor) {
    header('Location: index.php');
    exit('Acceso denegado: Usuario no es vendedor');
}

$vendedor_id = $vendedor['id'];

if ($_POST && isset($_POST['action'])) {
    validarCSRF();
    
    if ($_POST['action'] == 'reportar_venta') {
        $fecha_venta = $_POST['fecha_venta'];
        $total_reportado = (float)$_POST['total_reportado'];
        $numero_factura = trim($_POST['numero_factura'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        
        if (empty($fecha_venta) || $total_reportado <= 0) {
            $error = "Fecha y total son obligatorios";
        } else {
            try {
                // Verificar que no se duplique el reporte del mismo día
                $query_check = "SELECT id FROM ventas_reportadas_vendedor 
                               WHERE vendedor_id = ? AND fecha_venta = ? AND estado != 'rechazada'";
                $stmt_check = $db->prepare($query_check);
                $stmt_check->execute([$vendedor_id, $fecha_venta]);
                
                if ($stmt_check->rowCount() > 0) {
                    $error = "Ya existe un reporte de ventas para esta fecha";
                } else {
                    // Insertar reporte de venta
                    $query_insert = "INSERT INTO ventas_reportadas_vendedor 
                                    (vendedor_id, fecha_venta, total_reportado, numero_factura, descripcion) 
                                    VALUES (?, ?, ?, ?, ?)";
                    $stmt_insert = $db->prepare($query_insert);
                    $stmt_insert->execute([$vendedor_id, $fecha_venta, $total_reportado, $numero_factura, $descripcion]);
                    
                    // Intentar hacer match automático con ventas del sistema
                    $venta_reporte_id = $db->lastInsertId();
                    intentarMatchAutomatico($db, $venta_reporte_id, $vendedor_id, $fecha_venta, $total_reportado);
                    
                    $success = "Venta reportada exitosamente. El sistema intentará hacer match automático.";
                }
            } catch (Exception $e) {
                $error = "Error al reportar venta: " . $e->getMessage();
            }
        }
    }
}

// Función para intentar match automático
function intentarMatchAutomatico($db, $reporte_id, $vendedor_id, $fecha_venta, $total_reportado) {
    // Buscar ventas del sistema del mismo día y vendedor con tolerancia de ±5%
    $tolerancia = 0.05; // 5%
    $min_total = $total_reportado * (1 - $tolerancia);
    $max_total = $total_reportado * (1 + $tolerancia);
    
    $query_match = "SELECT v.* FROM ventas v 
                    WHERE v.vendedor_id = ? 
                    AND DATE(v.fecha) = ? 
                    AND v.total BETWEEN ? AND ?
                    AND v.id NOT IN (
                        SELECT venta_sistema_id FROM ventas_reportadas_vendedor 
                        WHERE venta_sistema_id IS NOT NULL AND estado != 'rechazada'
                    )
                    ORDER BY ABS(v.total - ?) ASC 
                    LIMIT 1";
    $stmt_match = $db->prepare($query_match);
    $stmt_match->execute([$vendedor_id, $fecha_venta, $min_total, $max_total, $total_reportado]);
    $venta_sistema = $stmt_match->fetch(PDO::FETCH_ASSOC);
    
    if ($venta_sistema) {
        $diferencia = abs($total_reportado - $venta_sistema['total']);
        
        // Si la diferencia es muy pequeña (menos de Q1), auto-aprobar
        if ($diferencia < 1.00) {
            $query_update = "UPDATE ventas_reportadas_vendedor 
                            SET venta_sistema_id = ?, diferencia = ?, estado = 'verificada' 
                            WHERE id = ?";
            $stmt_update = $db->prepare($query_update);
            $stmt_update->execute([$venta_sistema['id'], $diferencia, $reporte_id]);
        } else {
            // Marcar para revisión manual
            $query_update = "UPDATE ventas_reportadas_vendedor 
                            SET venta_sistema_id = ?, diferencia = ? 
                            WHERE id = ?";
            $stmt_update = $db->prepare($query_update);
            $stmt_update->execute([$venta_sistema['id'], $diferencia, $reporte_id]);
        }
    }
}

// Obtener reportes del vendedor
$query_reportes = "SELECT vr.*, v.fecha as fecha_venta_sistema, v.total as total_venta_sistema,
                          CASE 
                            WHEN vr.estado = 'verificada' THEN 'Verificada'
                            WHEN vr.estado = 'rechazada' THEN 'Rechazada'
                            WHEN vr.venta_sistema_id IS NOT NULL THEN 'Pendiente Aprobación'
                            ELSE 'Sin Match'
                          END as estado_descripcion
                   FROM ventas_reportadas_vendedor vr
                   LEFT JOIN ventas v ON vr.venta_sistema_id = v.id
                   WHERE vr.vendedor_id = ?
                   ORDER BY vr.fecha_venta DESC
                   LIMIT 30";
$stmt_reportes = $db->prepare($query_reportes);
$stmt_reportes->execute([$vendedor_id]);
$reportes = $stmt_reportes->fetchAll(PDO::FETCH_ASSOC);

// Obtener estadísticas del vendedor
$query_stats = "SELECT 
                COUNT(*) as total_reportes,
                SUM(CASE WHEN estado = 'verificada' THEN 1 ELSE 0 END) as verificadas,
                SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                SUM(CASE WHEN estado = 'verificada' THEN total_reportado ELSE 0 END) as total_verificado,
                AVG(CASE WHEN estado = 'verificada' THEN diferencia ELSE NULL END) as promedio_diferencia
                FROM ventas_reportadas_vendedor 
                WHERE vendedor_id = ? AND fecha_venta >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
$stmt_stats = $db->prepare($query_stats);
$stmt_stats->execute([$vendedor_id]);
$stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);

// Obtener ranking del vendedor
$query_ranking = "SELECT 
                    r.posicion_ranking,
                    r.puntos_ranking,
                    r.total_ventas,
                    r.cantidad_ventas,
                    r.comision_ganada,
                    (SELECT COUNT(*) FROM ranking_vendedores WHERE periodo = r.periodo) as total_vendedores
                  FROM ranking_vendedores r 
                  WHERE r.vendedor_id = ? AND r.periodo = ?
                  ORDER BY r.fecha_calculo DESC 
                  LIMIT 1";
$periodo_actual = date('Y-m');
$stmt_ranking = $db->prepare($query_ranking);
$stmt_ranking->execute([$vendedor_id, $periodo_actual]);
$ranking = $stmt_ranking->fetch(PDO::FETCH_ASSOC);

// Obtener top 10 vendedores para mostrar competencia
$query_top_vendedores = "SELECT r.*, v.nombre as vendedor_nombre
                         FROM ranking_vendedores r
                         JOIN vendedores v ON r.vendedor_id = v.id
                         WHERE r.periodo = ?
                         ORDER BY r.posicion_ranking ASC
                         LIMIT 10";
$stmt_top = $db->prepare($query_top_vendedores);
$stmt_top->execute([$periodo_actual]);
$top_vendedores = $stmt_top->fetchAll(PDO::FETCH_ASSOC);

include 'includes/layout_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 rs-wrap-sm">
    <h2><i class="fas fa-user-tie"></i> Dashboard de Vendedor - <?php echo htmlspecialchars($vendedor['nombre']); ?></h2>
    <div class="btn-group rs-wrap-sm">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalReportarVenta">
            <i class="fas fa-plus"></i> Reportar Venta
        </button>
    </div>
</div>

<!-- Mensajes -->
<?php if (isset($success)): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            showToast('<?php echo addslashes($success); ?>', 'success');
        });
    </script>
<?php endif; ?>
<?php if (isset($error)): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            showToast('<?php echo addslashes($error); ?>', 'danger');
        });
    </script>
<?php endif; ?>

<!-- Estadísticas del vendedor -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4><?php echo $stats['total_reportes']; ?></h4>
                        <p class="mb-0">Reportes Total</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-clipboard-list fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4><?php echo $stats['verificadas']; ?></h4>
                        <p class="mb-0">Verificadas</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-check-circle fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4><?php echo $stats['pendientes']; ?></h4>
                        <p class="mb-0">Pendientes</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-clock fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4>Q<?php echo number_format($stats['total_verificado'], 2); ?></h4>
                        <p class="mb-0">Total Verificado</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-dollar-sign fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Panel de reportes de ventas -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-list"></i> Mis Reportes de Ventas</h5>
            </div>
            <div class="card-body">
                <?php if (empty($reportes)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-clipboard fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No has reportado ventas aún</p>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalReportarVenta">
                            <i class="fas fa-plus"></i> Reportar primera venta
                        </button>
                    </div>
                <?php else: ?>
                    <div class="table-responsive-md">
                        <table class="table table-striped table-hover">
                            <thead class="thead-titulos">
                                <tr>
                                    <th>Fecha</th>
                                    <th>Total Reportado</th>
                                    <th>Total Sistema</th>
                                    <th>Diferencia</th>
                                    <th>Estado</th>
                                    <th>Factura</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reportes as $reporte): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($reporte['fecha_venta'])); ?></td>
                                    <td><strong>Q<?php echo number_format($reporte['total_reportado'], 2); ?></strong></td>
                                    <td>
                                        <?php if ($reporte['total_venta_sistema']): ?>
                                            Q<?php echo number_format($reporte['total_venta_sistema'], 2); ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($reporte['diferencia'] > 0): ?>
                                            <span class="text-warning">Q<?php echo number_format($reporte['diferencia'], 2); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $badge_class = [
                                            'Verificada' => 'success',
                                            'Rechazada' => 'danger',
                                            'Pendiente Aprobación' => 'warning',
                                            'Sin Match' => 'secondary'
                                        ];
                                        ?>
                                        <span class="badge bg-<?php echo $badge_class[$reporte['estado_descripcion']]; ?>">
                                            <?php echo $reporte['estado_descripcion']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($reporte['numero_factura']): ?>
                                            <code><?php echo htmlspecialchars($reporte['numero_factura']); ?></code>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Panel de ranking -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-trophy"></i> Mi Ranking</h5>
            </div>
            <div class="card-body">
                <?php if ($ranking): ?>
                    <div class="text-center mb-4">
                        <div class="display-4 text-primary">
                            #<?php echo $ranking['posicion_ranking']; ?>
                        </div>
                        <p class="text-muted">de <?php echo $ranking['total_vendedores']; ?> vendedores</p>
                        
                        <div class="progress mb-3">
                            <div class="progress-bar bg-success" 
                                 style="width: <?php echo (($ranking['total_vendedores'] - $ranking['posicion_ranking']) / $ranking['total_vendedores']) * 100; ?>%">
                            </div>
                        </div>
                        
                        <div class="row text-center">
                            <div class="col-6">
                                <small class="text-muted">Ventas</small>
                                <div><strong><?php echo $ranking['cantidad_ventas']; ?></strong></div>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Total</small>
                                <div><strong>Q<?php echo number_format($ranking['total_ventas'], 0); ?></strong></div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted">
                        <i class="fas fa-chart-line fa-3x mb-3"></i>
                        <p>No hay datos de ranking aún</p>
                    </div>
                <?php endif; ?>
                
                <hr>
                
                <h6 class="text-muted mb-3">Top 10 Vendedores</h6>
                <?php if (!empty($top_vendedores)): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($top_vendedores as $index => $top_vendedor): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center p-2 <?php echo $top_vendedor['vendedor_id'] == $vendedor_id ? 'bg-light border-primary' : ''; ?>">
                            <div class="d-flex align-items-center">
                                <span class="badge bg-<?php echo $index < 3 ? 'warning' : 'secondary'; ?> me-2">
                                    <?php echo $top_vendedor['posicion_ranking']; ?>
                                </span>
                                <div>
                                    <div class="fw-bold <?php echo $top_vendedor['vendedor_id'] == $vendedor_id ? 'text-primary' : ''; ?>">
                                        <?php echo htmlspecialchars($top_vendedor['vendedor_nombre']); ?>
                                        <?php if ($top_vendedor['vendedor_id'] == $vendedor_id): ?>
                                            <i class="fas fa-user text-primary ms-1"></i>
                                        <?php endif; ?>
                                    </div>
                                    <small class="text-muted">
                                        <?php echo $top_vendedor['cantidad_ventas']; ?> ventas
                                    </small>
                                </div>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold">Q<?php echo number_format($top_vendedor['total_ventas'], 0); ?></div>
                                <small class="text-success">Q<?php echo number_format($top_vendedor['comision_ganada'], 2); ?></small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center">No hay datos de ranking disponibles</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal Reportar Venta -->
<div class="modal fade" id="modalReportarVenta" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="reportar_venta">
                <?php echo campoCSRF(); ?>
                
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus"></i> Reportar Venta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Importante:</strong> Reporta tus ventas diarias para que puedan ser verificadas y puedas recibir tu comisión.
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Fecha de la Venta</label>
                        <input type="date" class="form-control" name="fecha_venta" required max="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Total de la Venta</label>
                        <div class="input-group">
                            <span class="input-group-text">Q</span>
                            <input type="number" step="0.01" min="0.01" class="form-control" name="total_reportado" required placeholder="0.00">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Número de Factura (Opcional)</label>
                        <input type="text" class="form-control" name="numero_factura" placeholder="Ej: F001-123456">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Descripción/Productos (Opcional)</label>
                        <textarea class="form-control" name="descripcion" rows="3" placeholder="Descripción de los productos vendidos..."></textarea>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Reportar Venta
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Toast helper function
function showToast(message, type) {
    if (typeof window.showToast === 'function') {
        window.showToast(message, type);
    } else {
        alert(message);
    }
}

// Auto-seleccionar fecha actual al abrir modal
document.getElementById('modalReportarVenta').addEventListener('show.bs.modal', function() {
    const today = new Date().toISOString().split('T')[0];
    document.querySelector('input[name="fecha_venta"]').value = today;
});
</script>

<?php include 'includes/layout_footer.php'; ?>
