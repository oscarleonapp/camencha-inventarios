<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

verificarLogin();
verificarPermiso('ventas_crear');

$database = new Database();
$db = $database->getConnection();

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['tienda_id']) || !is_numeric($input['tienda_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de tienda inválido']);
    exit;
}

$tienda_id = (int)$input['tienda_id'];
$usuario_id = $_SESSION['usuario_id'];

try {
    // Verificar que el usuario tenga acceso a esta tienda
    $rol_usuario = $_SESSION['rol'] ?? '';
    if ($rol_usuario !== 'admin') {
        $query_acceso = "SELECT tienda_id FROM usuarios WHERE id = ? AND (tienda_id = ? OR tienda_id IS NULL)";
        $stmt_acceso = $db->prepare($query_acceso);
        $stmt_acceso->execute([$usuario_id, $tienda_id]);
        if (!$stmt_acceso->fetchColumn()) {
            echo json_encode(['success' => false, 'message' => 'No tienes acceso a esta tienda']);
            exit;
        }
    }
    
    // Obtener historial de reportes recientes (últimos 10)
    $query = "SELECT 
                 rd.*,
                 t.nombre as tienda_nombre,
                 u_gerente.nombre as gerente_nombre
              FROM reportes_diarios_encargado rd
              JOIN tiendas t ON rd.tienda_id = t.id
              LEFT JOIN usuarios u_gerente ON rd.gerente_id = u_gerente.id
              WHERE rd.tienda_id = ? 
              ORDER BY rd.fecha_reporte DESC
              LIMIT 10";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$tienda_id]);
    $reportes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Generar HTML
    ob_start();
    
    if (empty($reportes)) {
        echo '<p class="text-muted text-center py-3">No hay reportes anteriores</p>';
    } else {
        ?>
        <div class="table-responsive">
            <table class="table table-sm table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Fecha</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th>Aprobado Por</th>
                        <th>Fecha Creación</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reportes as $reporte): ?>
                    <tr>
                        <td>
                            <strong><?= date('d/m/Y', strtotime($reporte['fecha_reporte'])) ?></strong>
                        </td>
                        <td>
                            <strong class="text-primary">Q <?= number_format($reporte['total_general'], 2) ?></strong>
                            <br>
                            <small class="text-muted">
                                E:<?= number_format($reporte['total_efectivo'], 0) ?> |
                                T:<?= number_format($reporte['total_tarjeta'], 0) ?> |
                                Tr:<?= number_format($reporte['total_transferencia'], 0) ?>
                            </small>
                        </td>
                        <td>
                            <?php
                            $estado_badges = [
                                'pendiente' => 'bg-warning',
                                'aprobado_gerente' => 'bg-success',
                                'rechazado_gerente' => 'bg-danger',
                                'aprobado_contabilidad' => 'bg-info',
                                'rechazado_contabilidad' => 'bg-dark'
                            ];
                            $estado_textos = [
                                'pendiente' => 'Pendiente',
                                'aprobado_gerente' => 'Aprobado',
                                'rechazado_gerente' => 'Rechazado',
                                'aprobado_contabilidad' => 'En Contabilidad',
                                'rechazado_contabilidad' => 'Rechazado Final'
                            ];
                            $badge_class = $estado_badges[$reporte['estado']] ?? 'bg-secondary';
                            $estado_texto = $estado_textos[$reporte['estado']] ?? ucfirst($reporte['estado']);
                            ?>
                            <span class="badge <?= $badge_class ?>"><?= $estado_texto ?></span>
                        </td>
                        <td>
                            <?= $reporte['gerente_nombre'] 
                                ? htmlspecialchars($reporte['gerente_nombre']) 
                                : '<span class="text-muted">-</span>' ?>
                            <?php if ($reporte['fecha_revision_gerente']): ?>
                                <br><small class="text-muted">
                                    <?= date('d/m H:i', strtotime($reporte['fecha_revision_gerente'])) ?>
                                </small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <small><?= date('d/m/Y H:i', strtotime($reporte['fecha_creacion'])) ?></small>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="mt-3">
            <div class="row text-center">
                <div class="col-3">
                    <small class="text-muted">Total Reportes</small>
                    <br><strong><?= count($reportes) ?></strong>
                </div>
                <div class="col-3">
                    <small class="text-muted">Aprobados</small>
                    <br><strong class="text-success">
                        <?= count(array_filter($reportes, fn($r) => in_array($r['estado'], ['aprobado_gerente', 'aprobado_contabilidad']))) ?>
                    </strong>
                </div>
                <div class="col-3">
                    <small class="text-muted">Pendientes</small>
                    <br><strong class="text-warning">
                        <?= count(array_filter($reportes, fn($r) => $r['estado'] === 'pendiente')) ?>
                    </strong>
                </div>
                <div class="col-3">
                    <small class="text-muted">Promedio</small>
                    <br><strong class="text-info">
                        Q <?= number_format(array_sum(array_column($reportes, 'total_general')) / count($reportes), 0) ?>
                    </strong>
                </div>
            </div>
        </div>
        <?php
    }
    
    $html = ob_get_clean();
    
    echo json_encode([
        'success' => true,
        'html' => $html
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener historial: ' . $e->getMessage()
    ]);
}
?>