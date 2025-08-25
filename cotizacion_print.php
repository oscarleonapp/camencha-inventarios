<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

verificarLogin();
verificarPermiso('cotizaciones_ver');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id<=0) { die('Cotización inválida'); }

$database = new Database();
$db = $database->getConnection();

$stmt = $db->prepare("SELECT c.*, u.nombre AS usuario_nombre
                      FROM cotizaciones c
                      JOIN usuarios u ON c.usuario_id = u.id
                      WHERE c.id = ?");
$stmt->execute([$id]);
$ctz = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$ctz) { die('No encontrada'); }

$sti = $db->prepare("SELECT ci.*, p.codigo, p.nombre as producto_nombre
                     FROM detalle_cotizaciones ci
                     JOIN productos p ON p.id = ci.producto_id
                     WHERE ci.cotizacion_id = ?");
$sti->execute([$id]);
$items = $sti->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Cotización #<?php echo $ctz['id']; ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <style>
    @media print { .no-print { display:none; } body { -webkit-print-color-adjust: exact; } }
    .table th, .table td { vertical-align: middle; }
    /* Responsive helpers for tablet */
    @media (max-width: 1024px) {
      .table-responsive-md { overflow-x: auto; -webkit-overflow-scrolling: touch; }
      .table-responsive-md > table { min-width: 900px; }
      .table-responsive-md th, .table-responsive-md td { white-space: nowrap; }
      /* Descripción con elipsis */
      .table-responsive-md td:nth-child(2) { max-width: 420px; overflow: hidden; text-overflow: ellipsis; }
    }
  </style>
  </head>
<body class="p-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="m-0">Cotización <?php echo $ctz['numero_cotizacion'] ? '#'.htmlspecialchars($ctz['numero_cotizacion']) : '#'.$ctz['id']; ?></h3>
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
      <h6 class="text-muted">Empresa</h6>
      <div><strong>Sistema de Inventarios</strong></div>
    </div>
    <div class="col">
      <h6 class="text-muted">Cliente</h6>
      <div><strong><?php echo htmlspecialchars($ctz['cliente_nombre']); ?></strong></div>
      <div class="text-muted small"><?php echo htmlspecialchars($ctz['cliente_email'] ?: ''); ?> <?php echo htmlspecialchars($ctz['cliente_telefono'] ?: ''); ?></div>
    </div>
    <div class="col">
      <h6 class="text-muted">Datos</h6>
      <div>Fecha: <?php echo date('d/m/Y', strtotime($ctz['fecha_cotizacion'])); ?></div>
      <div>Vendedor: <?php echo htmlspecialchars($ctz['usuario_nombre']); ?></div>
    </div>
  </div>

  <div class="table-responsive-md">
    <table class="table table-bordered">
      <thead class="table-light">
        <tr>
          <th style="width:15%">Código</th>
          <th>Descripción</th>
          <th style="width:10%" class="text-end">Cant.</th>
          <th style="width:15%" class="text-end">Precio</th>
          <th style="width:15%" class="text-end">Subtotal</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $it): ?>
        <tr>
          <td><?php echo htmlspecialchars($it['codigo']); ?></td>
          <td><?php echo htmlspecialchars($it['producto_nombre']); ?></td>
          <td class="text-end"><?php echo (int)$it['cantidad']; ?></td>
          <td class="text-end"><?php echo number_format($it['precio_unitario'],2); ?></td>
          <td class="text-end"><?php echo number_format($it['subtotal'],2); ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr>
          <th colspan="4" class="text-end">Subtotal</th>
          <th class="text-end"><?php echo number_format($ctz['subtotal'],2); ?></th>
        </tr>
        <tr>
          <th colspan="4" class="text-end">Descuento</th>
          <th class="text-end"><?php echo number_format($ctz['descuento'],2); ?></th>
        </tr>
        <tr>
          <th colspan="4" class="text-end">Total</th>
          <th class="text-end"><?php echo number_format($ctz['total'],2); ?></th>
        </tr>
      </tfoot>
    </table>
  </div>

  <?php if (!empty($ctz['notas'])): ?>
  <div class="mt-3">
    <h6 class="text-muted">Notas</h6>
    <div><?php echo nl2br(htmlspecialchars($ctz['notas'])); ?></div>
  </div>
  <?php endif; ?>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/js/all.min.js"></script>
  <script>
  async function enviarEmail(e){
    e.preventDefault();
    const email = document.getElementById('emailDestino').value.trim();
    if(!email){ alert('Ingrese un email válido'); return false; }
    const fd = new FormData();
    fd.append('csrf_token', '<?php require_once 'includes/csrf_protection.php'; echo generarTokenCSRF(); ?>');
    fd.append('tipo','ctz'); fd.append('id','<?php echo (int)$ctz['id']; ?>'); fd.append('para', email);
    try {
      const res = await fetch('includes/send_email.php', { method:'POST', body: fd });
      console.log('Response status:', res.status);
      const text = await res.text();
      console.log('Response text:', text);
      const json = JSON.parse(text);
      if(json.success){ 
        alert('Email enviado correctamente a ' + email); 
      } else { 
        alert('No se pudo enviar: ' + (json.error || 'Error desconocido')); 
      }
    } catch(err) {
      console.error('Error:', err);
      alert('Error de comunicación: ' + err.message);
    }
    return false;
  }
  </script>
</body>
</html>
