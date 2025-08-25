<?php
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'includes/csrf_protection.php';

verificarLogin();
verificarPermiso('cotizaciones_ver');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id<=0) { die('Cotización inválida'); }

$database = new Database();
$db = $database->getConnection();

$stmt = $db->prepare("SELECT * FROM cotizaciones WHERE id = ?");
$stmt->execute([$id]);
$ctz = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$ctz) { die('No encontrada'); }

$sti = $db->prepare("SELECT ci.*, p.codigo, p.nombre as producto_nombre FROM detalle_cotizaciones ci JOIN productos p ON p.id = ci.producto_id WHERE ci.cotizacion_id = ?");
$sti->execute([$id]);
$items = $sti->fetchAll(PDO::FETCH_ASSOC);

include 'includes/layout_header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h2><i class="fas fa-exchange-alt"></i> Convertir Cotización #<?php echo $ctz['id']; ?> a Venta</h2>
</div>

<div class="card">
  <div class="card-body">
    <form method="POST" action="ventas.php">
      <input type="hidden" name="action" value="realizar_venta">
      <input type="hidden" name="desde_cotizacion_id" value="<?php echo $ctz['id']; ?>">
      <div class="row g-3 mb-3">
        <div class="col-md-4">
          <label class="form-label">Tienda</label>
          <select class="form-select" name="tienda_id" required>
            <option value="">Seleccionar tienda...</option>
            <?php $tiendas = $db->query("SELECT id, nombre FROM tiendas WHERE activo=1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
              foreach ($tiendas as $t): ?>
              <option value="<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['nombre']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Vendedor (opcional)</label>
          <select class="form-select" name="vendedor_id">
            <option value="">Sin vendedor</option>
            <?php $vendedores = $db->query("SELECT id, nombre FROM vendedores WHERE activo=1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
              foreach ($vendedores as $v): ?>
              <option value="<?php echo $v['id']; ?>"><?php echo htmlspecialchars($v['nombre']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="table-responsive-md">
        <table class="table table-sm align-middle">
          <thead class="table-light">
            <tr>
              <th>Producto</th>
              <th>Cantidad</th>
              <th>Precio</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($items as $it): ?>
            <tr>
              <td>
                <input type="hidden" name="productos[]" value="<?php echo $it['producto_id']; ?>">
                <strong>[<?php echo htmlspecialchars($it['codigo']); ?>]</strong> <?php echo htmlspecialchars($it['producto_nombre']); ?>
              </td>
              <td><input type="number" class="form-control" name="cantidades[]" min="1" value="<?php echo (int)$it['cantidad']; ?>"></td>
              <td><input type="number" step="0.01" class="form-control" name="precios[]" min="0" value="<?php echo (float)$it['precio_unitario']; ?>"></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="text-end">
        <button type="submit" class="btn btn-primary">Crear Venta</button>
      </div>
    </form>
  </div>
</div>

<?php include 'includes/layout_footer.php'; ?>
