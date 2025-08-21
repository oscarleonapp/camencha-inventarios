<?php
$titulo = "Cambios de Productos";
require_once 'includes/auth.php';
require_once 'config/database.php';

verificarLogin();
// Permiso: ver productos o logs del sistema
if (!tienePermiso('productos_ver') && !tienePermiso('logs_sistema')) {
    header('Location: sin_permisos.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Filtros básicos
$fecha_inicio = $_GET['fecha_inicio'] ?? '';
$fecha_fin = $_GET['fecha_fin'] ?? '';
$accion = $_GET['accion'] ?? '';
$usuario = $_GET['usuario'] ?? '';

$where = ["l.mensaje LIKE '%producto%'"];
$params = [];
if ($fecha_inicio) { $where[] = "DATE(l.created_at) >= ?"; $params[] = $fecha_inicio; }
if ($fecha_fin) { $where[] = "DATE(l.created_at) <= ?"; $params[] = $fecha_fin; }
if ($accion) { $where[] = "l.nivel LIKE ?"; $params[] = "%$accion%"; }
if ($usuario) { $where[] = "l.usuario_id = ?"; $params[] = $usuario; }
$where_clause = 'WHERE ' . implode(' AND ', $where);

$pagina = max(1, intval($_GET['pagina'] ?? 1));
$por_pagina = 50;
$offset = ($pagina - 1) * $por_pagina;

$sql = "SELECT l.created_at as fecha, u.nombre as usuario_nombre, l.nivel as accion, l.mensaje as descripcion, '' as datos_anteriores, '' as datos_nuevos 
        FROM logs_sistema l 
        LEFT JOIN usuarios u ON l.usuario_id = u.id 
        $where_clause ORDER BY l.created_at DESC LIMIT $por_pagina OFFSET $offset";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$count_sql = "SELECT COUNT(*) FROM logs_sistema l LEFT JOIN usuarios u ON l.usuario_id = u.id $where_clause";
$stmtc = $db->prepare($count_sql);
$stmtc->execute($params);
$total = (int)$stmtc->fetchColumn();
$total_paginas = max(1, (int)ceil($total / $por_pagina));

// Datos para filtros
$usuarios = $db->query("SELECT DISTINCT l.usuario_id, u.nombre as usuario_nombre FROM logs_sistema l LEFT JOIN usuarios u ON l.usuario_id = u.id WHERE l.mensaje LIKE '%producto%' AND l.usuario_id IS NOT NULL ORDER BY u.nombre")->fetchAll(PDO::FETCH_ASSOC);

// Exportar CSV con filtros
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
  $exp_sql = "SELECT l.created_at as fecha, u.nombre as usuario_nombre, l.nivel as accion, l.mensaje as descripcion, '' as datos_anteriores, '' as datos_nuevos FROM logs_sistema l LEFT JOIN usuarios u ON l.usuario_id = u.id $where_clause ORDER BY l.created_at DESC";
  $stmte = $db->prepare($exp_sql);
  $stmte->execute($params);
  $rows = $stmte->fetchAll(PDO::FETCH_ASSOC);

  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename=logs_productos_' . date('Y-m-d_H-i-s') . '.csv');
  $out = fopen('php://output', 'w');
  fputcsv($out, ['Fecha','Usuario','Accion','Descripcion','Cambios']);
  foreach ($rows as $r) {
    $cambios = '';
    $antes = $r['datos_anteriores'] ? json_decode($r['datos_anteriores'], true) : null;
    $despues = $r['datos_nuevos'] ? json_decode($r['datos_nuevos'], true) : null;
    if ($antes !== null && $despues !== null) {
      $keys = array_unique(array_merge(array_keys($antes), array_keys($despues)));
      $pairs = [];
      foreach ($keys as $k) {
        $va = $antes[$k] ?? '';
        $vn = $despues[$k] ?? '';
        if ($va !== $vn) $pairs[] = "$k: $va -> $vn";
      }
      $cambios = implode(' | ', $pairs);
    } else {
      $cambios = $r['datos_nuevos'] ?: $r['datos_anteriores'] ?: '';
    }
    fputcsv($out, [
      $r['fecha'], $r['usuario_nombre'], $r['accion'], $r['descripcion'], $cambios
    ]);
  }
  fclose($out);
  exit;
}

include 'includes/layout_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h2><i class="fas fa-history"></i> Cambios de Productos</h2>
  <div class="btn-group">
    <a class="btn btn-outline-success" href="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?' . (($_SERVER['QUERY_STRING'] ?? '') ? $_SERVER['QUERY_STRING'] . '&' : '') . 'export=csv'); ?>">
      <i class="fas fa-file-csv"></i> Exportar CSV
    </a>
    <a class="btn btn-outline-secondary" href="logs.php?modulo=productos"><i class="fas fa-external-link-alt"></i> Ver en Logs</a>
  </div>
 </div>

<div class="card mb-3">
  <div class="card-header"><i class="fas fa-filter"></i> Filtros</div>
  <div class="card-body">
    <form method="GET" class="row g-3">
      <div class="col-md-3">
        <label class="form-label">Fecha inicio</label>
        <input type="date" name="fecha_inicio" value="<?php echo htmlspecialchars($fecha_inicio); ?>" class="form-control">
      </div>
      <div class="col-md-3">
        <label class="form-label">Fecha fin</label>
        <input type="date" name="fecha_fin" value="<?php echo htmlspecialchars($fecha_fin); ?>" class="form-control">
      </div>
      <div class="col-md-3">
        <label class="form-label">Acción</label>
        <input type="text" name="accion" value="<?php echo htmlspecialchars($accion); ?>" placeholder="ej: cambio_estado" class="form-control">
      </div>
      <div class="col-md-3">
        <label class="form-label">Usuario</label>
        <select name="usuario" class="form-select">
          <option value="">Todos</option>
          <?php foreach ($usuarios as $u): ?>
            <option value="<?php echo $u['usuario_id']; ?>" <?php echo $usuario == $u['usuario_id'] ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($u['usuario_nombre']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-12 d-flex gap-2">
        <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i> Filtrar</button>
        <a href="logs_productos.php" class="btn btn-outline-secondary"><i class="fas fa-times"></i> Limpiar</a>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0"><i class="fas fa-list"></i> Registros (<?php echo number_format($total); ?>)</h5>
    <small class="text-muted">Página <?php echo $pagina; ?> de <?php echo $total_paginas; ?></small>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead class="table-dark">
          <tr>
            <th>Fecha/Hora</th>
            <th>Usuario</th>
            <th>Acción</th>
            <th>Descripción</th>
            <th>Detalles</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($logs as $log): ?>
          <tr>
            <td><?php echo date('d/m/Y H:i:s', strtotime($log['fecha'])); ?></td>
            <td><?php echo htmlspecialchars($log['usuario_nombre']); ?></td>
            <td><span class="badge bg-info"><?php echo htmlspecialchars($log['accion']); ?></span></td>
            <td><?php echo htmlspecialchars($log['descripcion']); ?></td>
            <td>
              <?php 
              $antes = $log['datos_anteriores'] ? json_decode($log['datos_anteriores'], true) : null;
              $despues = $log['datos_nuevos'] ? json_decode($log['datos_nuevos'], true) : null;
              if ($antes !== null && $despues !== null) {
                  $keys = array_unique(array_merge(array_keys($antes), array_keys($despues)));
                  $changes = [];
                  foreach ($keys as $k) {
                      $va = $antes[$k] ?? '';
                      $vn = $despues[$k] ?? '';
                      if ($va !== $vn) {
                          $changes[] = '<div><strong>' . htmlspecialchars($k) . ':</strong> ' . htmlspecialchars((string)$va) . ' → ' . htmlspecialchars((string)$vn) . '</div>';
                      }
                  }
                  echo !empty($changes) ? implode('', $changes) : '<span class="text-muted">Sin cambios</span>';
              } else {
                  $det = $log['datos_nuevos'] ?: $log['datos_anteriores'];
                  if ($det) {
                      $json = json_decode($det, true);
                      echo '<code class="small">' . htmlspecialchars(json_encode($json, JSON_UNESCAPED_UNICODE)) . '</code>';
                  } else {
                      echo '<span class="text-muted">—</span>';
                  }
              }
              ?>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($logs)): ?>
          <tr><td colspan="5" class="text-center text-muted">Sin registros</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php if ($total_paginas > 1): ?>
  <div class="card-footer">
    <nav>
      <ul class="pagination pagination-sm mb-0">
        <?php $base = 'logs_productos.php'; $qs = $_GET; ?>
        <li class="page-item <?php echo $pagina<=1?'disabled':''; ?>">
          <a class="page-link" href="<?php $qs['pagina']=max(1,$pagina-1); echo $base.'?'.http_build_query($qs); ?>">&laquo;</a>
        </li>
        <?php for ($p=1;$p<=$total_paginas;$p++): $qs['pagina']=$p; ?>
          <li class="page-item <?php echo $p==$pagina?'active':''; ?>">
            <a class="page-link" href="<?php echo $base.'?'.http_build_query($qs); ?>"><?php echo $p; ?></a>
          </li>
        <?php endfor; ?>
        <li class="page-item <?php echo $pagina>=$total_paginas?'disabled':''; ?>">
          <a class="page-link" href="<?php $qs['pagina']=min($total_paginas,$pagina+1); echo $base.'?'.http_build_query($qs); ?>">&raquo;</a>
        </li>
      </ul>
    </nav>
  </div>
  <?php endif; ?>
</div>

<?php include 'includes/layout_footer.php'; ?>
