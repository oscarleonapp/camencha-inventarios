<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

verificarLogin();
verificarPermiso('cotizaciones_crear','crear');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id<=0) { header('Location: cotizaciones.php'); exit; }

$database = new Database();
$db = $database->getConnection();

try {
  $db->beginTransaction();
  $c = $db->prepare("SELECT * FROM cotizaciones WHERE id = ?");
  $c->execute([$id]);
  $ctz = $c->fetch(PDO::FETCH_ASSOC);
  if (!$ctz) throw new Exception('Cotización no encontrada');

  $ins = $db->prepare("INSERT INTO cotizaciones (numero_cotizacion, usuario_id, cliente_nombre, cliente_email, cliente_telefono, notas, subtotal, descuento, total, fecha_cotizacion, fecha_vencimiento) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY))");
  $notas = trim(($ctz['notas'] ?? ''));
  $notas = ($notas ? ($notas.' | ') : '').'Duplicada de #'.$id;
  
  // Generar nuevo número de cotización
  require_once 'includes/config_functions.php';
  $ctz_prefix = obtenerConfiguracion('ctz_prefix', 'CTZ-');
  $ctz_next = (int)obtenerConfiguracion('ctz_next_number', 1);
  $ctz_numero = $ctz_prefix . str_pad($ctz_next, 6, '0', STR_PAD_LEFT);
  
  $ins->execute([$ctz_numero, $_SESSION['usuario_id'], $ctz['cliente_nombre'], $ctz['cliente_email'], $ctz['cliente_telefono'], $notas, $ctz['subtotal'], $ctz['descuento'], $ctz['total']]);
  actualizarConfiguracion('ctz_next_number', $ctz_next + 1);
  $new_id = $db->lastInsertId();

  $items = $db->prepare("SELECT * FROM detalle_cotizaciones WHERE cotizacion_id = ?");
  $items->execute([$id]);
  $insI = $db->prepare("INSERT INTO detalle_cotizaciones (cotizacion_id, producto_id, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)");
  while ($it = $items->fetch(PDO::FETCH_ASSOC)) {
    $insI->execute([$new_id, $it['producto_id'], $it['cantidad'], $it['precio_unitario'], $it['subtotal']]);
  }

  $db->commit();
  header('Location: cotizaciones.php?duplicada='.$new_id);
  exit;
} catch (Exception $e) {
  $db->rollBack();
  header('Location: cotizaciones.php?error='.urlencode($e->getMessage()));
  exit;
}

