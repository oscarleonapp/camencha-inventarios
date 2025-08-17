<?php
$titulo = "Reorden Sugerido";
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'includes/csrf_protection.php';

verificarLogin();
verificarPermiso('compras_ver');

$database = new Database();
$db = $database->getConnection();

// Filtros
$tienda_id = isset($_GET['tienda_id']) ? (int)$_GET['tienda_id'] : 0;
if ($tienda_id <= 0) {
  $tienda_id = (int)$db->query("SELECT id FROM tiendas WHERE activo=1 ORDER BY nombre LIMIT 1")->fetchColumn();
}

// Crear OC desde sugerencias
if ($_POST && isset($_POST['action']) && $_POST['action']==='crear_oc_sugerida') {
  validarCSRF();
  verificarPermiso('compras_crear','crear');
  $prov_id = $_POST['proveedor_id'] !== '' ? (int)$_POST['proveedor_id'] : null;
  $tienda = (int)$_POST['tienda_id'];
  $pids = $_POST['producto_id'] ?? [];
  $qtys = $_POST['cantidad'] ?? [];
  $prices = $_POST['precio'] ?? [];
  try {
    if ($tienda<=0) throw new Exception('Tienda inválida');
    if (empty($pids)) throw new Exception('Sin productos a ordenar');
    $db->beginTransaction();
    $stmtOC = $db->prepare("INSERT INTO compras (proveedor_id, tienda_id, usuario_id, estado, notas) VALUES (?, ?, ?, 'pendiente', 'Compra creada desde reorden')");
    $stmtOC->execute([$prov_id, $tienda, $_SESSION['usuario_id']]);
    $oc_id = $db->lastInsertId();
    $stmtItem = $db->prepare("INSERT INTO detalle_compras (compra_id, producto_id, cantidad, precio_unitario) VALUES (?, ?, ?, ?)");
    for ($i=0; $i<count($pids); $i++) {
      $pid=(int)$pids[$i]; $q=max(0,(int)$qtys[$i]); $pr=max(0,(float)$prices[$i]);
      if ($pid && $q>0) $stmtItem->execute([$oc_id, $pid, $q, $pr]);
    }
    $db->commit();
    $success = "OC #$oc_id creada desde sugerencias";
  } catch (Exception $e) {
    $db->rollBack();
    $error = $e->getMessage();
  }
}

$tiendas = $db->query("SELECT id, nombre FROM tiendas WHERE activo=1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

$stmtSug = $db->prepare("SELECT p.id as producto_id, p.codigo, p.nombre, p.precio_compra, p.proveedor_id,
       pr.nombre AS proveedor_nombre,
       i.cantidad, COALESCE(i.cantidad_reparacion,0) AS rep, i.cantidad_minima,
       (i.cantidad - COALESCE(i.cantidad_reparacion,0)) AS disponible
     FROM inventarios i
     JOIN productos p ON p.id = i.producto_id
     LEFT JOIN proveedores pr ON pr.id = p.proveedor_id
     WHERE i.tienda_id = ? AND (i.cantidad - COALESCE(i.cantidad_reparacion,0)) <= i.cantidad_minima
     ORDER BY pr.nombre, p.nombre");
$stmtSug->execute([$tienda_id]);
$rows = $stmtSug->fetchAll(PDO::FETCH_ASSOC);

$grupos = [];
foreach ($rows as $r) {
  $key = $r['proveedor_id'] ?: 0;
  if (!isset($grupos[$key])) $grupos[$key] = ['proveedor_id'=>$r['proveedor_id'],'proveedor_nombre'=>$r['proveedor_nombre']?:'Sin proveedor','items'=>[]];
  $sugerido = max(($r['cantidad_minima']*2) - $r['disponible'], 0);
  $r['sugerido'] = $sugerido;
  $grupos[$key]['items'][] = $r;
}

include 'includes/layout_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h2><i class="fas fa-lightbulb"></i> Reorden Sugerido</h2>
  <form method="GET" class="d-flex gap-2">
    <select name="tienda_id" class="form-select" onchange="this.form.submit()">
      <?php foreach ($tiendas as $t): ?>
        <option value="<?php echo $t['id']; ?>" <?php echo $tienda_id==$t['id']?'selected':''; ?>><?php echo htmlspecialchars($t['nombre']); ?></option>
      <?php endforeach; ?>
    </select>
  </form>
</div>

<?php if (!empty($success)): ?><script>document.addEventListener('DOMContentLoaded',()=>showSuccess('<?php echo addslashes($success); ?>'));</script><?php endif; ?>
<?php if (!empty($error)): ?><script>document.addEventListener('DOMContentLoaded',()=>showError('<?php echo addslashes($error); ?>'));</script><?php endif; ?>

<?php if (empty($rows)): ?>
  <div class="alert alert-success">No hay productos por debajo del mínimo en esta tienda.</div>
<?php endif; ?>

<?php foreach ($grupos as $prov_id => $grupo): ?>
  <div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0"><i class="fas fa-truck"></i> <?php echo htmlspecialchars($grupo['proveedor_nombre']); ?></h5>
      <span class="text-muted small">Proveedor ID: <?php echo $grupo['proveedor_id'] ?: '—'; ?></span>
    </div>
    <div class="card-body">
      <form method="POST">
        <input type="hidden" name="action" value="crear_oc_sugerida">
        <input type="hidden" name="tienda_id" value="<?php echo $tienda_id; ?>">
        <input type="hidden" name="proveedor_id" value="<?php echo $grupo['proveedor_id']; ?>">
        <?php echo campoCSRF(); ?>
        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead class="table-light">
              <tr>
                <th>Producto</th>
                <th>Disponible</th>
                <th>Mínimo</th>
                <th>Sugerido</th>
                <th>Ordenar</th>
                <th>Precio Compra</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($grupo['items'] as $it): ?>
              <tr>
                <td>
                  <input type="hidden" name="producto_id[]" value="<?php echo $it['producto_id']; ?>">
                  <strong>[<?php echo htmlspecialchars($it['codigo']); ?>]</strong> <?php echo htmlspecialchars($it['nombre']); ?>
                </td>
                <td><?php echo (int)$it['disponible']; ?></td>
                <td><?php echo (int)$it['cantidad_minima']; ?></td>
                <td><span class="badge bg-warning text-dark"><?php echo (int)$it['sugerido']; ?></span></td>
                <td style="width:140px;"><input type="number" class="form-control form-control-sm" name="cantidad[]" min="0" value="<?php echo (int)$it['sugerido']; ?>"></td>
                <td style="width:160px;"><input type="number" class="form-control form-control-sm" step="0.01" name="precio[]" min="0" value="<?php echo (float)$it['precio_compra']; ?>"></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="text-end">
          <button type="submit" class="btn btn-primary">Crear OC para este proveedor</button>
        </div>
      </form>
    </div>
  </div>
<?php endforeach; ?>

<?php include 'includes/layout_footer.php'; ?>

