<?php
require_once 'auth.php';
require_once '../config/database.php';

verificarLogin();
verificarPermiso('logs_sistema');

$id = intval($_GET['id'] ?? 0);

if (!$id) {
    echo '<div class="alert alert-danger">ID de log inválido</div>';
    exit;
}

$database = new Database();
$db = $database->getConnection();

$query = "SELECT * FROM logs_sistema WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$id]);
$log = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$log) {
    echo '<div class="alert alert-danger">Log no encontrado</div>';
    exit;
}

// Formatear datos JSON
$datos_anteriores = $log['datos_anteriores'] ? json_decode($log['datos_anteriores'], true) : null;
$datos_nuevos = $log['datos_nuevos'] ? json_decode($log['datos_nuevos'], true) : null;
?>

<div class="row">
    <div class="col-md-6">
        <h6><i class="fas fa-info-circle"></i> Información General</h6>
        <table class="table table-sm">
            <tr>
                <td><strong>ID:</strong></td>
                <td><?php echo $log['id']; ?></td>
            </tr>
            <tr>
                <td><strong>Fecha/Hora:</strong></td>
                <td><?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?></td>
            </tr>
            <tr>
                <td><strong>Usuario:</strong></td>
                <td>
                    <?php if ($log['usuario_nombre']): ?>
                        <?php echo htmlspecialchars($log['usuario_nombre']); ?><br>
                        <small class="text-muted"><?php echo htmlspecialchars($log['usuario_email']); ?></small>
                    <?php else: ?>
                        <em>Sistema</em>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td><strong>Módulo:</strong></td>
                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($log['modulo']); ?></span></td>
            </tr>
            <tr>
                <td><strong>Acción:</strong></td>
                <td><code><?php echo htmlspecialchars($log['accion']); ?></code></td>
            </tr>
            <tr>
                <td><strong>Nivel:</strong></td>
                <td>
                    <?php
                    $nivel_class = [
                        'debug' => 'bg-light text-dark',
                        'info' => 'bg-primary',
                        'warning' => 'bg-warning text-dark',
                        'error' => 'bg-danger',
                        'critical' => 'bg-danger text-white'
                    ];
                    ?>
                    <span class="badge <?php echo $nivel_class[$log['nivel']] ?? 'bg-secondary'; ?>">
                        <?php echo strtoupper($log['nivel']); ?>
                    </span>
                </td>
            </tr>
            <tr>
                <td><strong>Estado:</strong></td>
                <td>
                    <?php
                    $estado_class = [
                        'exitoso' => 'bg-success',
                        'fallido' => 'bg-danger',
                        'pendiente' => 'bg-warning text-dark'
                    ];
                    ?>
                    <span class="badge <?php echo $estado_class[$log['estado']] ?? 'bg-secondary'; ?>">
                        <?php echo ucfirst($log['estado']); ?>
                    </span>
                </td>
            </tr>
        </table>
    </div>
    
    <div class="col-md-6">
        <h6><i class="fas fa-network-wired"></i> Información Técnica</h6>
        <table class="table table-sm">
            <tr>
                <td><strong>IP:</strong></td>
                <td><code><?php echo htmlspecialchars($log['ip_address']); ?></code></td>
            </tr>
            <tr>
                <td><strong>URL:</strong></td>
                <td><code class="small"><?php echo htmlspecialchars($log['url']); ?></code></td>
            </tr>
            <tr>
                <td><strong>Método HTTP:</strong></td>
                <td><span class="badge bg-info"><?php echo htmlspecialchars($log['metodo_http']); ?></span></td>
            </tr>
            <tr>
                <td><strong>User Agent:</strong></td>
                <td><small class="text-muted"><?php echo htmlspecialchars(substr($log['user_agent'], 0, 100)); ?><?php echo strlen($log['user_agent']) > 100 ? '...' : ''; ?></small></td>
            </tr>
            <?php if ($log['tiempo_ejecucion']): ?>
            <tr>
                <td><strong>Tiempo Ejecución:</strong></td>
                <td><?php echo number_format($log['tiempo_ejecucion'], 4); ?> segundos</td>
            </tr>
            <?php endif; ?>
        </table>
    </div>
</div>

<div class="row mt-3">
    <div class="col-12">
        <h6><i class="fas fa-comment"></i> Descripción</h6>
        <div class="alert alert-light">
            <?php echo nl2br(htmlspecialchars($log['descripcion'])); ?>
        </div>
    </div>
</div>

<?php if ($datos_anteriores || $datos_nuevos): ?>
<div class="row mt-3">
    <?php if ($datos_anteriores): ?>
    <div class="col-md-6">
        <h6><i class="fas fa-history"></i> Datos Anteriores</h6>
        <pre class="bg-light p-3 rounded small"><?php echo htmlspecialchars(json_encode($datos_anteriores, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
    </div>
    <?php endif; ?>
    
    <?php if ($datos_nuevos): ?>
    <div class="col-md-6">
        <h6><i class="fas fa-plus-circle"></i> Datos Nuevos</h6>
        <pre class="bg-light p-3 rounded small"><?php echo htmlspecialchars(json_encode($datos_nuevos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>
