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
    $stmt = $db->prepare("SELECT c.*, u.nombre AS usuario_nombre FROM cotizaciones c JOIN usuarios u ON c.usuario_id=u.id WHERE c.id=?");
    $stmt->execute([$id]);
    $c = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$c) { throw new Exception('Cotización no encontrada'); }
    $its = $db->prepare("SELECT ci.*, p.codigo, p.nombre as producto_nombre FROM detalle_cotizaciones ci JOIN productos p ON p.id=ci.producto_id WHERE ci.cotizacion_id=?");
    $its->execute([$id]);
    $items = $its->fetchAll(PDO::FETCH_ASSOC);
    $numero = $c['numero_cotizacion'] ? $c['numero_cotizacion'] : $c['id'];
    $asunto = 'Cotización ' . $numero;
    ob_start();
    echo '<h3>Cotización '.htmlspecialchars($numero).'</h3>';
    echo '<div><strong>Empresa:</strong> Sistema de Inventarios</div>';
    echo '<div><strong>Cliente:</strong> '.htmlspecialchars($c['cliente_nombre']).'</div>';
    echo '<table border="1" cellpadding="6" cellspacing="0" width="100%"><thead><tr><th>Código</th><th>Descripción</th><th>Cant.</th><th>Precio</th><th>Subtotal</th></tr></thead><tbody>';
    foreach ($items as $it) {
      echo '<tr><td>'.htmlspecialchars($it['codigo']).'</td><td>'.htmlspecialchars($it['producto_nombre']).'</td><td align="right">'.(int)$it['cantidad'].'</td><td align="right">'.number_format($it['precio_unitario'],2).'</td><td align="right">'.number_format($it['subtotal'],2).'</td></tr>';
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
  
  // Verificar si estamos en desarrollo local
  $is_local = isset($_SERVER['HTTP_HOST']) && (
    strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || 
    strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false ||
    strpos($_SERVER['HTTP_HOST'], '::1') !== false
  );
  
  if ($is_local) {
    // En desarrollo local, simular envío exitoso y guardar en log
    try {
      $log_dir = __DIR__ . '/../uploads/temp/';
      if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
      }
      
      $log_file = $log_dir . 'email_debug.log';
      $log_content = "=== EMAIL ENVIADO " . date('Y-m-d H:i:s') . " ===\n";
      $log_content .= "Para: $para\n";
      $log_content .= "Asunto: $asunto\n";
      $log_content .= "Tipo: $tipo\n";
      $log_content .= "ID: $id\n";
      $log_content .= "Host: " . ($_SERVER['HTTP_HOST'] ?? 'unknown') . "\n";
      $log_content .= "Contenido HTML:\n$html\n";
      $log_content .= "===============================================\n\n";
      
      file_put_contents($log_file, $log_content, FILE_APPEND | LOCK_EX);
      $ok = true; // Simular éxito en desarrollo local
    } catch (Exception $e) {
      throw new Exception("Error simulando envío de email: " . $e->getMessage());
    }
  } else {
    // En producción, intentar envío real
    $ok = @mail($para, $asunto, $html, $headers);
    if (!$ok) {
      throw new Exception("Error enviando email: función mail() falló");
    }
  }
  
  echo json_encode(['success' => (bool)$ok]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}

