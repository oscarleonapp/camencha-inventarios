<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

verificarLogin();
verificarPermiso('compras_recibir');

$oc_id = isset($_GET['oc_id']) ? (int)$_GET['oc_id'] : 0;
if ($oc_id <= 0) {
  echo '<div class="text-danger">OC inválida</div>';
  exit;
}

$database = new Database();
$db = $database->getConnection();

$stmt = $db->prepare("SELECT oci.id, oci.producto_id, p.codigo, p.nombre, oci.cantidad_solicitada, oci.cantidad_recibida, oci.precio_unitario
                      FROM orden_compra_items oci
                      JOIN productos p ON p.id = oci.producto_id
                      WHERE oci.oc_id = ? ORDER BY p.nombre");
$stmt->execute([$oc_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($items)) {
  echo '<div class="text-muted">Sin items para esta OC</div>';
  exit;
}
?>
<div class="table-responsive">
  <table class="table table-sm align-middle">
    <thead class="table-light">
      <tr>
        <th>Producto</th>
        <th>Solicitado</th>
        <th>Recibido</th>
        <th>Pendiente</th>
        <th>Recibir ahora</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($items as $it): $pend = max(0, $it['cantidad_solicitada'] - $it['cantidad_recibida']); ?>
      <tr>
        <td><strong>[<?php echo htmlspecialchars($it['codigo']); ?>]</strong> <?php echo htmlspecialchars($it['nombre']); ?></td>
        <td><?php echo (int)$it['cantidad_solicitada']; ?></td>
        <td><?php echo (int)$it['cantidad_recibida']; ?></td>
        <td><?php echo $pend; ?></td>
        <td style="width:140px;">
          <input type="hidden" name="item_id[]" value="<?php echo $it['id']; ?>">
          <input type="number" name="recibir[]" class="form-control form-control-sm" min="0" max="<?php echo $pend; ?>" value="<?php echo $pend; ?>" <?php echo $pend<=0?'disabled':''; ?>>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

