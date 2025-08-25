<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

verificarLogin();
verificarPermiso('ventas_crear','crear');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id<=0) { header('Location: cotizaciones.php'); exit; }

$database = new Database();
$db = $database->getConnection();

$ctz = $db->prepare("SELECT * FROM cotizaciones WHERE id = ?");
$ctz->execute([$id]);
$c = $ctz->fetch(PDO::FETCH_ASSOC);
if (!$c) { header('Location: cotizaciones.php?error=Cotizacion no encontrada'); exit; }

$its = $db->prepare("SELECT ci.*, p.codigo, p.nombre as producto_nombre FROM detalle_cotizaciones ci JOIN productos p ON p.id = ci.producto_id WHERE ci.cotizacion_id = ?");
$its->execute([$id]);
$items = $its->fetchAll(PDO::FETCH_ASSOC);

?><!doctype html>
<html><head><meta charset="utf-8"><title>Convertir Cotización</title></head>
<body>
<form id="convertForm" method="POST" action="ventas.php">
  <input type="hidden" name="action" value="realizar_venta">
  <!-- Tienda debe ser seleccionada manualmente ya que las cotizaciones no tienen tienda_id -->
  <input type="hidden" name="tienda_id" value="1">
  <input type="hidden" name="desde_cotizacion_id" value="<?php echo (int)$c['id']; ?>">
  <?php foreach ($items as $it): ?>
    <input type="hidden" name="productos[]" value="<?php echo (int)$it['producto_id']; ?>">
    <input type="hidden" name="cantidades[]" value="<?php echo (int)$it['cantidad']; ?>">
    <input type="hidden" name="precios[]" value="<?php echo (float)$it['precio_unitario']; ?>">
  <?php endforeach; ?>
</form>
<script>
if (confirm('¿Convertir la cotización #<?php echo (int)$c['id']; ?> a Venta y descontar stock?')) {
  document.getElementById('convertForm').submit();
} else {
  window.location.href = 'cotizaciones.php';
}
</script>
</body></html>

