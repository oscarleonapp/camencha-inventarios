<?php
$titulo = "Cotizaciones";
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'includes/csrf_protection.php';
require_once 'includes/config_functions.php';

verificarLogin();
verificarPermiso('cotizaciones_ver');

$database = new Database();
$db = $database->getConnection();

$success = '';
$error = '';

if ($_POST && isset($_POST['action']) && $_POST['action']==='crear_cotizacion') {
  validarCSRF();
  verificarPermiso('cotizaciones_crear','crear');
  // Removida referencia a tienda_id - no existe en la tabla
  $cliente_nombre = trim($_POST['cliente_nombre'] ?? '');
  $cliente_email = trim($_POST['cliente_email'] ?? '');
  $cliente_telefono = trim($_POST['cliente_telefono'] ?? '');
  $notas = trim($_POST['notas'] ?? '');
  $producto_id = $_POST['producto_id'] ?? [];
  $cantidades = $_POST['cantidad'] ?? [];
  $precios = $_POST['precio'] ?? [];

  try {
    // Validación de tienda removida - no aplica
    if ($cliente_nombre==='') throw new Exception('Nombre de cliente requerido');
    if (empty($producto_id)) throw new Exception('Debe agregar al menos un producto');

    $db->beginTransaction();
    // Calcular totales
    $subtotal = 0.00;
    for ($i=0;$i<count($producto_id);$i++) {
      $qty = max(0,(int)$cantidades[$i]);
      $pr = max(0,(float)$precios[$i]);
      $subtotal += $qty * $pr;
    }
    $descuento = (float)($_POST['descuento'] ?? 0);
    if ($descuento<0) $descuento=0;
    $total = max(0, $subtotal - $descuento);

    $stmtC = $db->prepare("INSERT INTO cotizaciones (numero_cotizacion, usuario_id, cliente_nombre, cliente_email, cliente_telefono, notas, subtotal, descuento, total, fecha_cotizacion, fecha_vencimiento) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY))");
    $ctz_prefix = obtenerConfiguracion('ctz_prefix', 'CTZ-');
    $ctz_next = (int)obtenerConfiguracion('ctz_next_number', 1);
    $ctz_numero = $ctz_prefix . str_pad($ctz_next, 6, '0', STR_PAD_LEFT);
    $stmtC->execute([$ctz_numero, $_SESSION['usuario_id'], $cliente_nombre, $cliente_email, $cliente_telefono, $notas, $subtotal, $descuento, $total]);
    actualizarConfiguracion('ctz_next_number', $ctz_next + 1);
    $ctz_id = $db->lastInsertId();

    $stmtI = $db->prepare("INSERT INTO detalle_cotizaciones (cotizacion_id, producto_id, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)");
    for ($i=0;$i<count($producto_id);$i++) {
      $pid = (int)$producto_id[$i];
      $qty = max(0,(int)$cantidades[$i]);
      $pr = max(0,(float)$precios[$i]);
      if ($pid && $qty>0) {
        $stmtI->execute([$ctz_id, $pid, $qty, $pr, $qty*$pr]);
      }
    }
    $db->commit();
    $success = "Cotización #$ctz_id creada";
  } catch (Exception $e) {
    $db->rollBack();
    $error = $e->getMessage();
  }
}

// Datos base
$productos = $db->query("SELECT id, codigo, nombre, precio_venta FROM productos WHERE activo=1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

// Listado cotizaciones
$stmtL = $db->query("SELECT c.*, u.nombre AS usuario_nombre FROM cotizaciones c JOIN usuarios u ON c.usuario_id=u.id ORDER BY c.fecha_cotizacion DESC LIMIT 100");
$cotizaciones = $stmtL->fetchAll(PDO::FETCH_ASSOC);

include 'includes/layout_header.php';
?>

<?php if (isset($_GET['duplicada'])): ?>
<script>document.addEventListener('DOMContentLoaded',()=>showSuccess('Cotización #<?php echo (int)$_GET['duplicada']; ?> duplicada'));</script>
<?php endif; ?>
<?php if (isset($_GET['error'])): ?>
<script>document.addEventListener('DOMContentLoaded',()=>showError('<?php echo addslashes($_GET['error']); ?>'));</script>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-4 rs-wrap-sm">
  <h2><i class="fas fa-file-signature"></i> Cotizaciones</h2>
  <div class="btn-group rs-wrap-sm">
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevaCotizacion">
      <i class="fas fa-plus"></i> Nueva Cotización
    </button>
  </div>
</div>

<?php if ($success): ?><script>document.addEventListener('DOMContentLoaded',()=>showSuccess('<?php echo addslashes($success); ?>'));</script><?php endif; ?>
<?php if ($error): ?><script>document.addEventListener('DOMContentLoaded',()=>showError('<?php echo addslashes($error); ?>'));</script><?php endif; ?>

<div class="card mb-4">
  <div class="card-header"><i class="fas fa-list"></i> Últimas Cotizaciones</div>
  <div class="card-body">
    <div class="table-responsive-md">
      <table class="table table-striped align-middle accessibility-fix">
        <thead>
          <tr>
            <th>Número</th>
            <th>#</th>
            <th>Fecha</th>
            <th>Estado</th>
            <th>Cliente</th>
            <th>Subtotal</th>
            <th>Descuento</th>
            <th>Total</th>
            <th>Usuario</th>
                      <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($cotizaciones as $c): ?>
          <tr>
            <td><?php echo htmlspecialchars($c['numero_cotizacion'] ?: ''); ?></td>
            <td>#<?php echo $c['id']; ?></td>
            <td><?php echo date('d/m/Y', strtotime($c['fecha_cotizacion'])); ?></td>
            <td><span class="badge bg-<?php echo $c['estado']=='borrador'?'secondary':($c['estado']=='enviada'?'primary':($c['estado']=='aceptada'?'success':'danger')); ?>"><?php echo ucfirst($c['estado']); ?></span></td>
            <td>
              <strong><?php echo htmlspecialchars($c['cliente_nombre']); ?></strong><br>
              <small class="text-muted"><?php echo htmlspecialchars($c['cliente_email'] ?: ''); ?><?php echo $c['cliente_telefono']? ' · '.htmlspecialchars($c['cliente_telefono']):''; ?></small>
            </td>
            <td><?php echo formatearMoneda($c['subtotal']); ?></td>
            <td><?php echo formatearMoneda($c['descuento']); ?></td>
            <td><?php echo formatearMoneda($c['total']); ?></td>
            <td><?php echo htmlspecialchars($c['usuario_nombre']); ?></td>
            <td>
              <a class="btn btn-sm btn-outline-primary" href="cotizacion_print.php?id=<?php echo $c['id']; ?>" target="_blank">
                <i class="fas fa-print"></i> Imprimir
              </a>
            </td>
            <td>
              <a class="btn btn-sm btn-outline-primary" href="cotizacion_print.php?id=<?php echo $c['id']; ?>" target="_blank"><i class="fas fa-print"></i></a>
              <a class="btn btn-sm btn-outline-success" href="cotizacion_convertir.php?id=<?php echo $c['id']; ?>"><i class="fas fa-exchange-alt"></i></a>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($cotizaciones)): ?>
          <tr><td colspan="9" class="text-center text-muted">Sin cotizaciones</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal Nueva Cotización -->
<div class="modal fade" id="modalNuevaCotizacion" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <form method="POST" id="formNuevaCotizacion">
        <input type="hidden" name="action" value="crear_cotizacion">
        <?php echo campoCSRF(); ?>
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-file-signature"></i> Nueva Cotización</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3 mb-3">
            <div class="col-md-4">
              <label class="form-label">Cliente *</label>
              <input type="text" class="form-control" name="cliente_nombre" required placeholder="Nombre del cliente">
            </div>
            <div class="col-md-4">
              <label class="form-label">Email</label>
              <input type="email" class="form-control" name="cliente_email" placeholder="email@ejemplo.com">
            </div>
            <div class="col-md-4">
              <label class="form-label">Teléfono</label>
              <input type="text" class="form-control" name="cliente_telefono" placeholder="Teléfono de contacto">
            </div>
          </div>
          <div class="table-responsive-md">
            <table class="table table-sm align-middle accessibility-fix" id="tablaItemsCtz">
              <thead class="table-light">
                <tr>
                  <th style="width:40%">Producto</th>
                  <th style="width:15%">Cantidad</th>
                  <th style="width:20%">Precio (editable)</th>
                  <th style="width:15%">Subtotal</th>
                  <th style="width:10%"></th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>
                    <select class="form-select prod-select" name="producto_id[]" onchange="syncPrecio(this)">
                      <option value="">Seleccionar...</option>
                      <?php foreach ($productos as $p): ?>
                        <option value="<?php echo $p['id']; ?>" data-precio="<?php echo $p['precio_venta']; ?>">[<?php echo $p['codigo']; ?>] <?php echo htmlspecialchars($p['nombre']); ?></option>
                      <?php endforeach; ?>
                    </select>
                  </td>
                  <td><input type="number" class="form-control cant-input" name="cantidad[]" min="1" value="1" oninput="recalcularLinea(this)"></td>
                  <td><input type="number" step="0.01" class="form-control precio-input" name="precio[]" min="0" value="0.00" oninput="recalcularLinea(this)"></td>
                  <td><input type="text" class="form-control subtotal-linea" value="0.00" readonly></td>
                  <td><button type="button" class="btn btn-outline-danger btn-sm" onclick="eliminarFilaCtz(this)">×</button></td>
                </tr>
              </tbody>
            </table>
          </div>
          <button type="button" class="btn btn-outline-primary btn-sm" onclick="agregarFilaCtz()"><i class="fas fa-plus"></i> Agregar línea</button>
          <hr>
          <div class="row g-3">
            <div class="col-md-3 ms-auto">
              <label class="form-label">Descuento</label>
              <input type="number" step="0.01" class="form-control" name="descuento" id="ctzDescuento" value="0.00" oninput="recalcTotales()">
            </div>
            <div class="col-md-3">
              <label class="form-label">Subtotal</label>
              <input type="text" class="form-control" id="ctzSubtotal" value="0.00" readonly>
            </div>
            <div class="col-md-3">
              <label class="form-label">Total</label>
              <input type="text" class="form-control" id="ctzTotal" value="0.00" readonly>
            </div>
          </div>
          <div class="mt-3">
            <label class="form-label">Notas</label>
            <textarea class="form-control" name="notas" rows="2" placeholder="Condiciones, validez, etc."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Guardar Cotización</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function agregarFilaCtz(){
  const tbody = document.querySelector('#tablaItemsCtz tbody');
  const row = tbody.rows[0].cloneNode(true);
  row.querySelector('.prod-select').value='';
  row.querySelector('.cant-input').value=1;
  row.querySelector('.precio-input').value='0.00';
  row.querySelector('.subtotal-linea').value='0.00';
  tbody.appendChild(row);
}
function eliminarFilaCtz(btn){
  const tbody = document.querySelector('#tablaItemsCtz tbody');
  if (tbody.rows.length>1) btn.closest('tr').remove();
  recalcTotales();
}
function syncPrecio(sel){
  const precio = sel.options[sel.selectedIndex]?.getAttribute('data-precio') || '0.00';
  const row = sel.closest('tr');
  row.querySelector('.precio-input').value = parseFloat(precio).toFixed(2);
  recalcLineaDom(row);
}
function recalcLineaDom(row){
  const qty = parseFloat(row.querySelector('.cant-input').value || '0');
  const pr = parseFloat(row.querySelector('.precio-input').value || '0');
  row.querySelector('.subtotal-linea').value = (qty*pr).toFixed(2);
  recalcTotales();
}
function recalcularLinea(input){
  const row = input.closest('tr');
  recalcLineaDom(row);
}
function recalcTotales(){
  let subtotal = 0;
  document.querySelectorAll('.subtotal-linea').forEach(s=>{ subtotal += parseFloat(s.value||'0'); });
  document.getElementById('ctzSubtotal').value = subtotal.toFixed(2);
  const desc = parseFloat(document.getElementById('ctzDescuento').value || '0');
  const total = Math.max(0, subtotal - desc);
  document.getElementById('ctzTotal').value = total.toFixed(2);
}
document.getElementById('formNuevaCotizacion').addEventListener('submit', function(e){
  // Asegurar subtotales
  recalcTotales();
});
</script>

<?php include 'includes/layout_footer.php'; ?>
