<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/csrf_protection.php';

header('Content-Type: application/json; charset=utf-8');

try {
  verificarLogin();
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['success'=>false,'error'=>'Método no permitido']); exit; }
  validarCSRF();

  $tipo = $_POST['tipo'] ?? '';
  $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
  $para = trim($_POST['para'] ?? '');
  if (!filter_var($para, FILTER_VALIDATE_EMAIL)) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'Email inválido']); exit; }
  if ($id<=0 || ($tipo!=='oc' && $tipo!=='ctz')) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'Parámetros inválidos']); exit; }

  $database = new Database();
  $db = $database->getConnection();

  $asunto = '';
  $html = '';
  if ($tipo==='ctz') {
    $stmt = $db->prepare("SELECT c.*, t.nombre AS tienda_nombre FROM cotizaciones c JOIN tiendas t ON c.tienda_id=t.id WHERE c.id=?");
    $stmt->execute([$id]);
    $c = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$c) { throw new Exception('Cotización no encontrada'); }
    $its = $db->prepare("SELECT ci.*, p.codigo FROM cotizacion_items ci JOIN productos p ON p.id=ci.producto_id WHERE ci.cotizacion_id=?");
    $its->execute([$id]);
    $items = $its->fetchAll(PDO::FETCH_ASSOC);
    $numero = $c['numero'] ? $c['numero'] : $c['id'];
    $asunto = 'Cotización ' . $numero;
    ob_start();
    echo '<h3>Cotización '.htmlspecialchars($numero).'</h3>';
    echo '<div><strong>Tienda:</strong> '.htmlspecialchars($c['tienda_nombre']).'</div>';
    echo '<div><strong>Cliente:</strong> '.htmlspecialchars($c['cliente_nombre']).'</div>';
    echo '<table border="1" cellpadding="6" cellspacing="0" width="100%"><thead><tr><th>Código</th><th>Descripción</th><th>Cant.</th><th>Precio</th><th>Subtotal</th></tr></thead><tbody>';
    foreach ($items as $it) {
      echo '<tr><td>'.htmlspecialchars($it['codigo']).'</td><td>'.htmlspecialchars($it['descripcion']).'</td><td align="right">'.(int)$it['cantidad'].'</td><td align="right">'.number_format($it['precio_unitario'],2).'</td><td align="right">'.number_format($it['subtotal'],2).'</td></tr>';
    }
    echo '</tbody></table>';
    echo '<p><strong>Total:</strong> '.number_format($c['total'],2).'</p>';
    $html = ob_get_clean();
  } else {
    $stmt = $db->prepare("SELECT oc.*, COALESCE(pr.nombre,'') AS proveedor_nombre, t.nombre AS tienda_nombre FROM ordenes_compra oc LEFT JOIN proveedores pr ON oc.proveedor_id=pr.id JOIN tiendas t ON oc.tienda_id=t.id WHERE oc.id=?");
    $stmt->execute([$id]);
    $oc = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$oc) { throw new Exception('OC no encontrada'); }
    $its = $db->prepare("SELECT oci.*, p.codigo, p.nombre FROM orden_compra_items oci JOIN productos p ON p.id=oci.producto_id WHERE oci.oc_id=?");
    $its->execute([$id]);
    $items = $its->fetchAll(PDO::FETCH_ASSOC);
    $numero = $oc['numero'] ? $oc['numero'] : $oc['id'];
    $asunto = 'Orden de Compra ' . $numero;
    ob_start();
    echo '<h3>Orden de Compra '.htmlspecialchars($numero).'</h3>';
    echo '<div><strong>Tienda:</strong> '.htmlspecialchars($oc['tienda_nombre']).'</div>';
    echo '<div><strong>Proveedor:</strong> '.htmlspecialchars($oc['proveedor_nombre']).'</div>';
    echo '<table border="1" cellpadding="6" cellspacing="0" width="100%"><thead><tr><th>Código</th><th>Producto</th><th>Solicitado</th><th>Precio</th><th>Subtotal</th></tr></thead><tbody>';
    $total=0; foreach ($items as $it) { $sub = (float)$it['precio_unitario']*(int)$it['cantidad_solicitada']; $total += $sub;
      echo '<tr><td>'.htmlspecialchars($it['codigo']).'</td><td>'.htmlspecialchars($it['nombre']).'</td><td align="right">'.(int)$it['cantidad_solicitada'].'</td><td align="right">'.number_format($it['precio_unitario'],2).'</td><td align="right">'.number_format($sub,2).'</td></tr>';
    }
    echo '</tbody></table>';
    echo '<p><strong>Total:</strong> '.number_format($total,2).'</p>';
    $html = ob_get_clean();
  }

  $headers  = "MIME-Version: 1.0\r\n";
  $headers .= "Content-type: text/html; charset=utf-8\r\n";
  $headers .= "From: Inventario <no-reply@local>\r\n";
  $ok = @mail($para, $asunto, $html, $headers);
  echo json_encode(['success' => (bool)$ok]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}

