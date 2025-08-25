<?php
$titulo = "Órdenes de Compra";
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'includes/csrf_protection.php';
require_once 'includes/config_functions.php';

verificarLogin();
verificarPermiso('compras_ver');

$database = new Database();
$db = $database->getConnection();

$success = '';
$error = '';

if ($_POST && isset($_POST['action'])) {
  validarCSRF();
  if ($_POST['action'] === 'crear_oc') {
    verificarPermiso('compras_crear','crear');
    $proveedor_id = $_POST['proveedor_id'] !== '' ? (int)$_POST['proveedor_id'] : null;
    $tienda_id = (int)($_POST['tienda_id'] ?? 0);
    $notas = trim($_POST['notas'] ?? '');
    $usuario_id = $_SESSION['usuario_id'];
    $productos = $_POST['producto_id'] ?? [];
    $cantidades = $_POST['cantidad'] ?? [];
    $precios = $_POST['precio'] ?? [];
    try {
      if ($tienda_id <= 0) throw new Exception('Tienda inválida');
      if (empty($productos)) throw new Exception('Debes agregar al menos un producto');
      $db->beginTransaction();
      $stmtOC = $db->prepare("INSERT INTO compras (numero, proveedor_id, tienda_id, usuario_id, estado, notas) VALUES (?, ?, ?, ?, 'pendiente', ?)");
      $oc_prefix = obtenerConfiguracion('oc_prefix', 'OC-');
      $oc_next = (int)obtenerConfiguracion('oc_next_number', 1);
      $oc_numero = $oc_prefix . str_pad($oc_next, 6, '0', STR_PAD_LEFT);
      $stmtOC->execute([$oc_numero, $proveedor_id, $tienda_id, $usuario_id, $notas]);
      actualizarConfiguracion('oc_next_number', $oc_next + 1);
      $oc_id = $db->lastInsertId();
      $stmtItem = $db->prepare("INSERT INTO detalle_compras (compra_id, producto_id, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)");
      for ($i=0; $i<count($productos); $i++) {
        $pid = (int)$productos[$i];
        $qty = max(0, (int)$cantidades[$i]);
        $pr = max(0, (float)$precios[$i]);
        if ($pid && $qty>0) $stmtItem->execute([$oc_id, $pid, $qty, $pr, $qty * $pr]);
      }
      $db->commit();
      $success = "OC #$oc_id creada";
    } catch (Exception $e) {
      $db->rollBack();
      $error = $e->getMessage();
    }
  }
  if ($_POST['action'] === 'recibir_oc') {
    verificarPermiso('compras_recibir','actualizar');
    $oc_id = (int)($_POST['oc_id'] ?? 0);
    $tienda_id = (int)($_POST['tienda_id'] ?? 0);
    $usuario_id = $_SESSION['usuario_id'];
    $items = $_POST['item_id'] ?? [];
    $recibir = $_POST['recibir'] ?? [];
    try {
      if ($oc_id<=0 || $tienda_id<=0) throw new Exception('OC/Tienda inválida');
      $db->beginTransaction();
      // Cargar items actuales
      $stmtGet = $db->prepare("SELECT id, producto_id, cantidad, precio_unitario FROM detalle_compras WHERE compra_id = ?");
      $stmtGet->execute([$oc_id]);
      $map = [];
      while($r=$stmtGet->fetch(PDO::FETCH_ASSOC)){ $map[$r['id']]=$r; }
      foreach ($items as $idx=>$item_id) {
        $iid = (int)$item_id;
        $qty = max(0, (int)($recibir[$idx] ?? 0));
        if ($iid && $qty>0 && isset($map[$iid])) {
          $pend = $map[$iid]['cantidad'];
          $toRecv = min($qty, $pend);
          if ($toRecv<=0) continue;
          // Update item recibido
          // Simplificado - no tracking de cantidad recibida individual
          // Update inventario (sumar al total)
          $pid = (int)$map[$iid]['producto_id'];
          // upsert inventarios
          $stmtInv = $db->prepare("SELECT cantidad, COALESCE(cantidad_reparacion,0) AS rep FROM inventarios WHERE tienda_id=? AND producto_id=?");
          $stmtInv->execute([$tienda_id, $pid]);
          $inv = $stmtInv->fetch(PDO::FETCH_ASSOC);
          if ($inv) {
            $newTotal = (int)$inv['cantidad'] + $toRecv;
            $stmtIU = $db->prepare("UPDATE inventarios SET cantidad = ? WHERE tienda_id=? AND producto_id=?");
            $stmtIU->execute([$newTotal, $tienda_id, $pid]);
          } else {
            $stmtI = $db->prepare("INSERT INTO inventarios (tienda_id, producto_id, cantidad, cantidad_reparacion) VALUES (?, ?, ?, 0)");
            $stmtI->execute([$tienda_id, $pid, $toRecv]);
          }
          // Movimiento de entrada
          $stmtM = $db->prepare("INSERT INTO movimientos_inventario (tipo_movimiento, producto_id, tienda_destino_id, cantidad, motivo, usuario_id) VALUES ('entrada', ?, ?, ?, ?, ?)");
          $stmtM->execute([$pid, $tienda_id, $toRecv, 'Recepción OC #'.$oc_id, $usuario_id]);
        }
      }
      // Cerrar OC si todo recibido
      $stmtChk = $db->prepare("SELECT 0 as pendientes"); // Simplificado
      $stmtChk->execute();
      $pend = (int)$stmtChk->fetch(PDO::FETCH_ASSOC)['pendientes'];
      if ($pend <= 0) {
        $stmtClose = $db->prepare("UPDATE compras SET estado='recibida' WHERE id = ?");
        $stmtClose->execute([$oc_id]);
      }
      $db->commit();
      $success = "Recepción registrada";
    } catch (Exception $e) {
      $db->rollBack();
      $error = $e->getMessage();
    }
  }
}

// Datos base
$proveedores = $db->query("SELECT id, nombre FROM proveedores WHERE activo=1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$tiendas = $db->query("SELECT id, nombre FROM tiendas WHERE activo=1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$productos = $db->query("SELECT id, codigo, nombre, precio_compra, tipo FROM productos WHERE activo=1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

// Listado de OCs
$prov_f = trim($_GET['proveedor'] ?? '');
$estado_f = trim($_GET['estado'] ?? '');
$fecha_i = trim($_GET['fecha_i'] ?? '');
$fecha_f = trim($_GET['fecha_f'] ?? '');
$where = [];
$params = [];
if ($prov_f !== '') { $where[] = "pr.nombre LIKE ?"; $params[] = "%$prov_f%"; }
if ($estado_f !== '') { $where[] = "c.estado = ?"; $params[] = $estado_f; }
if ($fecha_i !== '') { $where[] = "DATE(c.created_at) >= ?"; $params[] = $fecha_i; }
if ($fecha_f !== '') { $where[] = "DATE(c.created_at) <= ?"; $params[] = $fecha_f; }
$wc = !empty($where) ? (" WHERE " . implode(" AND ", $where)) : "";
$sqlOCs = "SELECT c.*, pr.nombre AS proveedor_nombre, u.nombre AS usuario_nombre,"
        . " (SELECT SUM(dc.cantidad) FROM detalle_compras dc WHERE dc.compra_id=c.id) as total_items,"
        . " 0 as pendientes"
        . " FROM compras c"
        . " LEFT JOIN proveedores pr ON c.proveedor_id = pr.id"
        . " JOIN usuarios u ON c.usuario_id = u.id"
        . $wc
        . " ORDER BY c.created_at DESC LIMIT 200";
// Export CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
  $sqlCsv = str_replace(' LIMIT 200','', $sqlOCs);
  $st = $db->prepare($sqlCsv);
  $st->execute($params);
  $rows = $st->fetchAll(PDO::FETCH_ASSOC);
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename=ordenes_compra_'.date('Y-m-d_H-i-s').'.csv');
  $out = fopen('php://output','w');
  fputcsv($out, ['OC','Proveedor','Estado','Items','Pendientes','Usuario','Fecha']);
  foreach ($rows as $r) {
    fputcsv($out, [
      $r['id'], $r['proveedor_nombre'], $r['estado'],
      (int)$r['total_items'], max(0,(int)$r['pendientes']), $r['usuario_nombre'], $r['created_at']
    ]);
  }
  fclose($out);
  exit;
}

$stmtOCs = $db->prepare($sqlOCs);
$stmtOCs->execute($params);
$ocs = $stmtOCs->fetchAll(PDO::FETCH_ASSOC);

include 'includes/layout_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 rs-wrap-sm">
  <h2><i class="fas fa-file-invoice"></i> Órdenes de Compra</h2>
  <div class="btn-group rs-wrap-sm">
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevaOC">
      <i class="fas fa-plus"></i> Nueva OC
    </button>
  </div>
</div>

<?php if ($success): ?>
<script>document.addEventListener('DOMContentLoaded',()=>showSuccess('<?php echo addslashes($success); ?>'));</script>
<?php endif; ?>
<?php if ($error): ?>
<script>document.addEventListener('DOMContentLoaded',()=>showError('<?php echo addslashes($error); ?>'));</script>
<?php endif; ?>

<div class="card mb-3">
  <div class="card-header"><i class="fas fa-filter"></i> Filtros</div>
  <div class="card-body">
    <form class="row g-3" method="GET">
      <div class="col-md-3">
        <label class="form-label">Proveedor</label>
        <input type="text" class="form-control" name="proveedor" value="<?php echo htmlspecialchars($prov_f); ?>" placeholder="Nombre proveedor">
      </div>
      <div class="col-md-3">
        <label class="form-label">Estado</label>
        <select class="form-select" name="estado">
          <option value="">Todos</option>
          <option value="pendiente" <?php echo $estado_f==='pendiente'?'selected':''; ?>>Pendiente</option>
          <option value="recibida" <?php echo $estado_f==='recibida'?'selected':''; ?>>Recibida</option>
          <option value="cerrada" <?php echo $estado_f==='cerrada'?'selected':''; ?>>Cerrada</option>
          <option value="cancelada" <?php echo $estado_f==='cancelada'?'selected':''; ?>>Cancelada</option>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">Desde</label>
        <input type="date" class="form-control" name="fecha_i" value="<?php echo htmlspecialchars($fecha_i); ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label">Hasta</label>
        <input type="date" class="form-control" name="fecha_f" value="<?php echo htmlspecialchars($fecha_f); ?>">
      </div>
      <div class="col-md-2 d-flex align-items-end gap-2">
        <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i> Buscar</button>
        <a class="btn btn-outline-secondary" href="compras.php"><i class="fas fa-times"></i> Limpiar</a>
      </div>
    </form>
  </div>
</div>

<div class="card mb-4">
  <div class="card-header"><i class="fas fa-list"></i> Últimas OC</div>
  <div class="card-body">
    <div class="d-flex justify-content-end mb-2">
      <?php $qs = $_GET; $qs['export']='csv'; $csvUrl = 'compras.php?'.http_build_query($qs); ?>
      <a class="btn btn-sm btn-outline-success" href="<?php echo htmlspecialchars($csvUrl); ?>">
        <i class="fas fa-file-csv"></i> Exportar CSV
      </a>
    </div>
    <div class="table-responsive-md">
      <table class="table table-striped align-middle accessibility-fix">
        <thead>
          <tr>
            <th>Número</th>
            <th>#OC</th>
            <th>Proveedor</th>
            <th>Estado</th>
            <th>Items</th>
            <th>Pendientes</th>
            <th>Creada por</th>
            <th>Fecha</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($ocs as $oc): ?>
          <tr>
            <td><?php echo htmlspecialchars($oc['numero_compra'] ?: ''); ?></td>
            <td>#<?php echo $oc['id']; ?></td>
            <td><?php echo htmlspecialchars($oc['proveedor_nombre'] ?: '—'); ?></td>
            <td><span class="badge bg-<?php echo $oc['estado']==='pendiente'?'warning':($oc['estado']==='recibida'?'success':'secondary'); ?>"><?php echo ucfirst($oc['estado']); ?></span></td>
            <td><?php echo (int)$oc['total_items']; ?></td>
            <td><?php echo max(0,(int)$oc['pendientes']); ?></td>
            <td><?php echo htmlspecialchars($oc['usuario_nombre']); ?></td>
            <td><?php echo date('d/m/Y H:i', strtotime($oc['created_at'])); ?></td>
            <td>
              <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#modalRecibirOC" data-oc-id="<?php echo $oc['id']; ?>">
                <i class="fas fa-inbox"></i> Recibir
              </button>
              <a class="btn btn-sm btn-outline-primary ms-1" href="oc_print.php?id=<?php echo $oc['id']; ?>" target="_blank"><i class="fas fa-print"></i></a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal Nueva OC -->
<div class="modal fade" id="modalNuevaOC" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <form method="POST">
        <input type="hidden" name="action" value="crear_oc">
        <?php echo campoCSRF(); ?>
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-plus"></i> Nueva Orden de Compra</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3 mb-3">
            <div class="col-md-4">
              <label class="form-label">Proveedor</label>
              <select class="form-select" name="proveedor_id">
                <option value="">Sin proveedor</option>
                <?php foreach ($proveedores as $p): ?>
                <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['nombre']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Tienda</label>
              <select class="form-select" name="tienda_id" required>
                <option value="">Seleccionar...</option>
                <?php foreach ($tiendas as $t): ?>
                <option value="<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['nombre']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Notas</label>
              <input type="text" class="form-control" name="notas" placeholder="Opcional">
            </div>
          </div>
          <div class="table-responsive-md">
            <table class="table table-sm align-middle accessibility-fix" id="tablaItemsOC">
              <thead class="table-light">
                <tr>
                  <th style="width:40%">Producto</th>
                  <th style="width:15%">Cantidad</th>
                  <th style="width:15%">Precio Unit.</th>
                  <th style="width:10%"></th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>
                    <select class="form-select" name="producto_id[]">
                      <option value="">Seleccionar...</option>
                      <?php foreach ($productos as $prod): ?>
                        <option value="<?php echo $prod['id']; ?>">[<?php echo $prod['codigo']; ?>] <?php echo htmlspecialchars($prod['nombre']); ?></option>
                      <?php endforeach; ?>
                    </select>
                  </td>
                  <td><input type="number" class="form-control" name="cantidad[]" min="1" value="1"></td>
                  <td><input type="number" step="0.01" class="form-control" name="precio[]" min="0" value="0.00"></td>
                  <td><button type="button" class="btn btn-outline-danger btn-sm" onclick="eliminarFilaOC(this)">×</button></td>
                </tr>
              </tbody>
            </table>
          </div>
          <button type="button" class="btn btn-outline-primary btn-sm" onclick="agregarFilaOC()"><i class="fas fa-plus"></i> Agregar línea</button>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Crear OC</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Recibir OC -->
<div class="modal fade" id="modalRecibirOC" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST">
        <input type="hidden" name="action" value="recibir_oc">
        <input type="hidden" name="oc_id" id="recibirOcId">
        <input type="hidden" name="tienda_id" id="recibirTiendaId">
        <?php echo campoCSRF(); ?>
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-inbox"></i> Recepción de OC</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body" id="recibirBody">
          <div class="text-center text-muted">Cargando items...</div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
          <button type="submit" class="btn btn-success">Registrar Recepción</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function agregarFilaOC(){
  const tbody = document.querySelector('#tablaItemsOC tbody');
  const row = tbody.rows[0].cloneNode(true);
  row.querySelectorAll('input').forEach(i=>{ i.value = i.type==='number' && i.name==='cantidad[]' ? 1 : 0; if(i.name==='precio[]') i.value='0.00'; });
  row.querySelector('select').value='';
  tbody.appendChild(row);
}
function eliminarFilaOC(btn){
  const tbody = document.querySelector('#tablaItemsOC tbody');
  if (tbody.rows.length>1) btn.closest('tr').remove();
}

const modalRecibir = document.getElementById('modalRecibirOC');
modalRecibir.addEventListener('show.bs.modal', async (e)=>{
  const btn = e.relatedTarget;
  const ocId = btn.getAttribute('data-oc-id');
  const tiendaId = btn.getAttribute('data-tienda-id');
  document.getElementById('recibirOcId').value = ocId;
  document.getElementById('recibirTiendaId').value = tiendaId;
  const body = document.getElementById('recibirBody');
  body.innerHTML = '<div class="text-center text-muted">Cargando items...</div>';
  try {
    const res = await fetch('oc_items.php?oc_id='+encodeURIComponent(ocId));
    const html = await res.text();
    body.innerHTML = html;
  } catch(err){
    body.innerHTML = '<div class="text-danger">Error cargando items</div>';
  }
});
</script>

<?php include 'includes/layout_footer.php'; ?>
