<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

verificarLogin();
verificarPermiso('compras_ver');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id<=0) { die('OC inválida'); }

$database = new Database();
$db = $database->getConnection();

$stmt = $db->prepare("SELECT c.*, COALESCE(pr.nombre,'') AS proveedor_nombre, t.nombre AS tienda_nombre, u.nombre AS usuario_nombre
                      FROM compras c
                      LEFT JOIN proveedores pr ON c.proveedor_id = pr.id
                      JOIN tiendas t ON c.tienda_id = t.id
                      JOIN usuarios u ON c.usuario_id = u.id
                      WHERE c.id = ?");
$stmt->execute([$id]);
$oc = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$oc) { die('OC no encontrada'); }

$items = $db->prepare("SELECT dc.*, p.codigo, p.nombre
                       FROM detalle_compras dc JOIN productos p ON p.id = dc.producto_id
                       WHERE dc.compra_id = ? ORDER BY p.nombre");
$items->execute([$id]);
$rows = $items->fetchAll(PDO::FETCH_ASSOC);

// calcular totales
$total = 0.0; $total_rec = 0; $total_sol = 0;
foreach ($rows as $r) { $total += (float)$r['precio_unitario'] * (int)$r['cantidad_solicitada']; $total_sol += (int)$r['cantidad_solicitada']; $total_rec += (int)$r['cantidad_recibida']; }
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>OC #<?php echo $oc['id']; ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <style>
    @media print{.no-print{display:none} body{-webkit-print-color-adjust:exact}}
    /* Responsive helpers for tablet */
    @media (max-width: 1024px) {
      .table-responsive-md { overflow-x: auto; -webkit-overflow-scrolling: touch; }
      .table-responsive-md > table { min-width: 900px; }
      .table-responsive-md th, .table-responsive-md td { white-space: nowrap; }
      /* Producto: permitir elipsis */
      .table-responsive-md td:nth-child(2) { max-width: 360px; overflow: hidden; text-overflow: ellipsis; }
    }
  </style>
</head>
<body class="p-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="m-0">Orden de Compra <?php echo $oc['numero'] ? '#'.htmlspecialchars($oc['numero']) : '#'.$oc['id']; ?></h3>
    <div class="no-print d-flex gap-2">
      <form class="d-flex gap-2" onsubmit="return enviarEmail(event)">
        <input type="email" class="form-control" id="emailDestino" placeholder="Enviar a email">
        <button class="btn btn-outline-secondary" type="submit"><i class="fas fa-paper-plane"></i> Enviar</button>
      </form>
      <button class="btn btn-primary" onclick="window.print()"><i class="fas fa-print"></i> Imprimir</button>
    </div>
  </div>
  <div class="row mb-3">
    <div class="col">
      <h6 class="text-muted">Proveedor</h6>
      <div><strong><?php echo htmlspecialchars($oc['proveedor_nombre'] ?: '—'); ?></strong></div>
    </div>
    <div class="col">
      <h6 class="text-muted">Tienda</h6>
      <div><strong><?php echo htmlspecialchars($oc['tienda_nombre']); ?></strong></div>
    </div>
    <div class="col">
      <h6 class="text-muted">Datos</h6>
      <div>Fecha: <?php echo date('d/m/Y H:i', strtotime($oc['fecha_creacion'])); ?></div>
      <div>Estado: <?php echo ucfirst($oc['estado']); ?></div>
      <div>Usuario: <?php echo htmlspecialchars($oc['usuario_nombre']); ?></div>
    </div>
  </div>
  <?php if (!empty($oc['notas'])): ?>
  <div class="mb-3"><strong>Notas:</strong> <?php echo nl2br(htmlspecialchars($oc['notas'])); ?></div>
  <?php endif; ?>
  <div class="table-responsive-md">
    <table class="table table-bordered align-middle">
      <thead class="table-light"><tr>
        <th>Código</th><th>Producto</th><th class="text-end">Solicitado</th><th class="text-end">Recibido</th><th class="text-end">Precio</th><th class="text-end">Subtotal</th>
      </tr></thead>
      <tbody>
        <?php foreach ($rows as $r): $sub = (float)$r['precio_unitario'] * (int)$r['cantidad_solicitada']; ?>
        <tr>
          <td><?php echo htmlspecialchars($r['codigo']); ?></td>
          <td><?php echo htmlspecialchars($r['nombre']); ?></td>
          <td class="text-end"><?php echo (int)$r['cantidad_solicitada']; ?></td>
          <td class="text-end"><?php echo (int)$r['cantidad_recibida']; ?></td>
          <td class="text-end"><?php echo number_format((float)$r['precio_unitario'],2); ?></td>
          <td class="text-end"><?php echo number_format($sub,2); ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr><th colspan="2" class="text-end">Total items</th><th class="text-end"><?php echo $total_sol; ?></th><th class="text-end"><?php echo $total_rec; ?></th><th></th><th class="text-end"><?php echo number_format($total,2); ?></th></tr>
      </tfoot>
    </table>
  </div>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/js/all.min.js"></script>
  <script>
  async function enviarEmail(e){
    e.preventDefault();
    const email = document.getElementById('emailDestino').value.trim();
    if(!email){ alert('Ingrese un email válido'); return false; }
    const fd = new FormData();
    fd.append('csrf_token', '<?php require_once 'includes/csrf_protection.php'; echo generarTokenCSRF(); ?>');
    fd.append('tipo','oc'); fd.append('id','<?php echo (int)$oc['id']; ?>'); fd.append('para', email);
    const res = await fetch('includes/send_email.php', { method:'POST', body: fd });
    const json = await res.json();
    if(json.success){ alert('Email enviado'); } else { alert('No se pudo enviar: '+(json.error||'')); }
    return false;
  }
  </script>
</body>
</html>
