<?php
$titulo = "Sistema de Logs";
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'includes/logger.php';

verificarLogin();
verificarPermiso('logs_sistema');

$database = new Database();
$db = $database->getConnection();

// Parámetros de filtros
$filtro_modulo = $_GET['modulo'] ?? '';
$filtro_nivel = $_GET['nivel'] ?? '';
$filtro_usuario = $_GET['usuario'] ?? '';
$filtro_fecha_inicio = $_GET['fecha_inicio'] ?? '';
$filtro_fecha_fin = $_GET['fecha_fin'] ?? '';
$filtro_accion = $_GET['accion'] ?? '';
$busqueda = $_GET['busqueda'] ?? '';

// Paginación
$pagina = max(1, intval($_GET['pagina'] ?? 1));
$por_pagina = 50;
$offset = ($pagina - 1) * $por_pagina;

// Construir query con filtros
$where_conditions = [];
$params = [];

// Modulo functionality removed - column doesn't exist in current schema
// if (!empty($filtro_modulo)) {
//     $where_conditions[] = "l.modulo = ?";
//     $params[] = $filtro_modulo;
// }

if (!empty($filtro_nivel)) {
    $where_conditions[] = "l.nivel = ?";
    $params[] = $filtro_nivel;
}

if (!empty($filtro_usuario)) {
    $where_conditions[] = "l.usuario_id = ?";
    $params[] = $filtro_usuario;
}

if (!empty($filtro_fecha_inicio)) {
    $where_conditions[] = "DATE(l.created_at) >= ?";
    $params[] = $filtro_fecha_inicio;
}

if (!empty($filtro_fecha_fin)) {
    $where_conditions[] = "DATE(l.created_at) <= ?";
    $params[] = $filtro_fecha_fin;
}

if (!empty($filtro_accion)) {
    $where_conditions[] = "l.mensaje LIKE ?";
    $params[] = "%$filtro_accion%";
}

if (!empty($busqueda)) {
    $where_conditions[] = "(l.mensaje LIKE ? OR u.nombre LIKE ? OR l.ip_address LIKE ?)";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Query principal con paginación
$query = "SELECT l.*, u.nombre as usuario_nombre, u.email as usuario_email 
          FROM logs_sistema l 
          LEFT JOIN usuarios u ON l.usuario_id = u.id 
          $where_clause ORDER BY l.created_at DESC LIMIT $por_pagina OFFSET $offset";
$stmt = $db->prepare($query);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Query para contar total
$count_query = "SELECT COUNT(*) as total FROM logs_sistema l LEFT JOIN usuarios u ON l.usuario_id = u.id $where_clause";
$count_stmt = $db->prepare($count_query);
$count_stmt->execute($params);
$total_logs = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_paginas = ceil($total_logs / $por_pagina);

// Obtener datos para filtros
// $modulos = $db->query("SELECT DISTINCT modulo FROM logs_sistema WHERE modulo IS NOT NULL ORDER BY modulo")->fetchAll(PDO::FETCH_COLUMN);
$modulos = []; // Empty array since modulo column doesn't exist
$usuarios = $db->query("SELECT DISTINCT l.usuario_id, u.nombre as usuario_nombre FROM logs_sistema l LEFT JOIN usuarios u ON l.usuario_id = u.id WHERE l.usuario_id IS NOT NULL ORDER BY u.nombre")->fetchAll(PDO::FETCH_ASSOC);

// Estadísticas
$stats = Logger::obtenerEstadisticas($database);

require_once 'includes/layout_header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-clipboard-list"></i> Sistema de Logs</h2>
        <div class="btn-group">
            <button class="btn btn-outline-primary" onclick="location.reload()">
                <i class="fas fa-sync-alt"></i> Actualizar
            </button>
            <button class="btn btn-outline-danger" onclick="limpiarLogs()" <?php echo !esAdmin() ? 'disabled' : ''; ?>>
                <i class="fas fa-trash"></i> Limpiar Antiguos
            </button>
        </div>
    </div>

    <!-- Estadísticas -->
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h4><?php echo number_format($stats['total']); ?></h4>
                    <p class="mb-0">Total Logs</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-danger text-white">
                <div class="card-body text-center">
                    <h4><?php echo number_format($stats['errores']); ?></h4>
                    <p class="mb-0">Errores</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <h4><?php echo number_format($stats['advertencias']); ?></h4>
                    <p class="mb-0">Advertencias</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-dark text-white">
                <div class="card-body text-center">
                    <h4><?php echo number_format($stats['criticos']); ?></h4>
                    <p class="mb-0">Críticos</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h4><?php echo number_format($stats['hoy']); ?></h4>
                    <p class="mb-0">Hoy</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h4><?php echo number_format($stats['ultima_semana']); ?></h4>
                    <p class="mb-0">Esta Semana</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-header">
            <h5><i class="fas fa-filter"></i> Filtros de Búsqueda</h5>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">Módulo</label>
                    <select name="modulo" class="form-select">
                        <option value="">Todos</option>
                        <?php foreach ($modulos as $modulo): ?>
                            <option value="<?php echo $modulo; ?>" <?php echo $filtro_modulo == $modulo ? 'selected' : ''; ?>>
                                <?php echo ucfirst($modulo); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Nivel</label>
                    <select name="nivel" class="form-select">
                        <option value="">Todos</option>
                        <option value="debug" <?php echo $filtro_nivel == 'debug' ? 'selected' : ''; ?>>Debug</option>
                        <option value="info" <?php echo $filtro_nivel == 'info' ? 'selected' : ''; ?>>Info</option>
                        <option value="warning" <?php echo $filtro_nivel == 'warning' ? 'selected' : ''; ?>>Warning</option>
                        <option value="error" <?php echo $filtro_nivel == 'error' ? 'selected' : ''; ?>>Error</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Usuario</label>
                    <select name="usuario" class="form-select">
                        <option value="">Todos</option>
                        <?php foreach ($usuarios as $usuario): ?>
                            <option value="<?php echo $usuario['usuario_id']; ?>" <?php echo $filtro_usuario == $usuario['usuario_id'] ? 'selected' : ''; ?>>
                                <?php echo $usuario['usuario_nombre']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Fecha Inicio</label>
                    <input type="date" name="fecha_inicio" class="form-control" value="<?php echo $filtro_fecha_inicio; ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Fecha Fin</label>
                    <input type="date" name="fecha_fin" class="form-control" value="<?php echo $filtro_fecha_fin; ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Acción</label>
                    <input type="text" name="accion" class="form-control" value="<?php echo htmlspecialchars($filtro_accion); ?>" placeholder="Filtrar acción">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Búsqueda General</label>
                    <input type="text" name="busqueda" class="form-control" value="<?php echo htmlspecialchars($busqueda); ?>" placeholder="Buscar en descripción, usuario o IP...">
                </div>
                <div class="col-md-6 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Filtrar
                    </button>
                    <a href="logs.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i> Limpiar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de Logs -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5><i class="fas fa-list"></i> Registros de Logs (<?php echo number_format($total_logs); ?> total)</h5>
            <small class="text-muted">Página <?php echo $pagina; ?> de <?php echo $total_paginas; ?></small>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Fecha/Hora</th>
                            <th>Usuario</th>
                            <th>Módulo</th>
                            <th>Tipo</th>
                            <th>Mensaje</th>
                            <th>IP</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <i class="fas fa-info-circle text-muted"></i>
                                    No se encontraron logs con los filtros aplicados
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td>
                                        <small><?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?></small>
                                    </td>
                                    <td>
                                        <?php if ($log['usuario_nombre']): ?>
                                            <div class="fw-bold"><?php echo htmlspecialchars($log['usuario_nombre']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($log['usuario_email']); ?></small>
                                        <?php else: ?>
                                            <span class="text-muted">Sistema</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">Sistema</span>
                                    </td>
                                    <td>
                                        <?php
                                        $tipo_class = [
                                            'debug' => 'bg-light text-dark',
                                            'info' => 'bg-primary',
                                            'warning' => 'bg-warning text-dark',
                                            'error' => 'bg-danger'
                                        ];
                                        ?>
                                        <span class="badge <?php echo $tipo_class[$log['nivel']] ?? 'bg-secondary'; ?>">
                                            <?php echo strtoupper($log['nivel']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="descripcion-log"><?php echo htmlspecialchars($log['mensaje']); ?></div>
                                    </td>
                                    <td>
                                        <code class="small"><?php echo htmlspecialchars($log['ip_address'] ?? 'N/A'); ?></code>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" onclick="verDetalleLog(<?php echo $log['id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Paginación -->
    <?php if ($total_paginas > 1): ?>
        <nav class="mt-4">
            <ul class="pagination justify-content-center">
                <?php if ($pagina > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina - 1])); ?>">Anterior</a>
                    </li>
                <?php endif; ?>
                
                <?php for ($i = max(1, $pagina - 5); $i <= min($total_paginas, $pagina + 5); $i++): ?>
                    <li class="page-item <?php echo $i == $pagina ? 'active' : ''; ?>">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $i])); ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                
                <?php if ($pagina < $total_paginas): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina + 1])); ?>">Siguiente</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<!-- Modal para detalle de log -->
<div class="modal fade" id="logDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalle del Log</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="logDetailContent">
                <!-- Contenido cargado por AJAX -->
            </div>
        </div>
    </div>
</div>

<style>
.descripcion-log {
    max-width: 300px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
</style>

<script>
function verDetalleLog(id) {
    // Mostrar loading
    const loadingHtml = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="mt-2">Cargando detalles del log...</p>
        </div>
    `;
    
    document.getElementById('logDetailContent').innerHTML = loadingHtml;
    new bootstrap.Modal(document.getElementById('logDetailModal')).show();
    
    fetch(`includes/get_log_detail.php?id=${id}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Error en la respuesta del servidor');
            }
            return response.text();
        })
        .then(html => {
            document.getElementById('logDetailContent').innerHTML = html;
            showInfo('Detalles del log cargados');
        })
        .catch(error => {
            document.getElementById('logDetailContent').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    Error al cargar los detalles del log
                </div>
            `;
            showError('Error al cargar detalle del log');
        });
}

function limpiarLogs() {
    if (confirm('¿Estás seguro de limpiar logs antiguos (más de 90 días)?\n\nEsta acción no se puede deshacer.')) {
        showInfo('Iniciando limpieza de logs antiguos...', {duration: 2000});
        
        fetch('includes/limpiar_logs.php', { method: 'POST' })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Limpieza Completada', 
                        `Se eliminaron ${data.eliminados} logs antiguos correctamente`, 
                        'success', 
                        {duration: 6000}
                    );
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showError(data.error || 'Error al limpiar logs');
                }
            })
            .catch(error => {
                showError('Error de conexión al limpiar logs');
            });
    }
}

// Auto-refresh cada 30 segundos si no hay filtros
<?php if (empty($_GET)): ?>
setTimeout(() => location.reload(), 30000);
<?php endif; ?>
</script>

<?php require_once 'includes/layout_footer.php'; ?>