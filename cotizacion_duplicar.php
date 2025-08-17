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

  $ins = $db->prepare("INSERT INTO cotizaciones (tienda_id, usuario_id, cliente_nombre, cliente_email, cliente_telefono, notas, subtotal, descuento, total) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
  $notas = trim(($ctz['notas'] ?? ''));
  $notas = ($notas ? ($notas.' | ') : '').'Duplicada de #'.$id;
  $ins->execute([$ctz['tienda_id'], $_SESSION['usuario_id'], $ctz['cliente_nombre'], $ctz['cliente_email'], $ctz['cliente_telefono'], $notas, $ctz['subtotal'], $ctz['descuento'], $ctz['total']]);
  $new_id = $db->lastInsertId();

  $items = $db->prepare("SELECT * FROM cotizacion_items WHERE cotizacion_id = ?");
  $items->execute([$id]);
  $insI = $db->prepare("INSERT INTO cotizacion_items (cotizacion_id, producto_id, descripcion, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
  while ($it = $items->fetch(PDO::FETCH_ASSOC)) {
    $insI->execute([$new_id, $it['producto_id'], $it['descripcion'], $it['cantidad'], $it['precio_unitario'], $it['subtotal']]);
  }

  $db->commit();
  header('Location: cotizaciones.php?duplicada='.$new_id);
  exit;
} catch (Exception $e) {
  $db->rollBack();
  header('Location: cotizaciones.php?error='.urlencode($e->getMessage()));
  exit;
}

