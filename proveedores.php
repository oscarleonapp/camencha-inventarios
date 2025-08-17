<?php
$titulo = "Proveedores - Sistema de Inventarios";
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'includes/config_functions.php';
require_once 'includes/csrf_protection.php';

verificarLogin();
verificarPermiso('proveedores_ver');

$database = new Database();
$db = $database->getConnection();

// Exportación CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $stmt = $db->prepare("SELECT id, nombre, email, telefono, direccion, activo, fecha_creacion FROM proveedores ORDER BY nombre");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=proveedores_' . date('Y-m-d_H-i-s') . '.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID','Nombre','Email','Telefono','Direccion','Activo','Fecha Creacion']);
    foreach ($rows as $r) {
        fputcsv($out, [
            $r['id'], $r['nombre'], $r['email'], $r['telefono'], $r['direccion'],
            $r['activo'] ? '1' : '0', $r['fecha_creacion']
        ]);
    }
    fclose($out);
    exit;
}

if ($_POST && isset($_POST['action'])) {
    validarCSRF();
    if ($_POST['action'] === 'crear_proveedor') {
        verificarPermiso('proveedores_crear', 'crear');
        $nombre = trim($_POST['nombre'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $direccion = trim($_POST['direccion'] ?? '');
        if ($nombre === '') {
            $error = 'El nombre del proveedor es obligatorio';
        } else {
            $stmt = $db->prepare("INSERT INTO proveedores (nombre, email, telefono, direccion, activo) VALUES (?, ?, ?, ?, 1)");
            $stmt->execute([$nombre, $email, $telefono, $direccion]);
            $success = 'Proveedor creado exitosamente';
        }
    } elseif ($_POST['action'] === 'editar_proveedor') {
        verificarPermiso('proveedores_actualizar', 'actualizar');
        $id = (int)($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $direccion = trim($_POST['direccion'] ?? '');
        if ($id <= 0 || $nombre === '') {
            $error = 'Datos inválidos para actualizar proveedor';
        } else {
            $stmt = $db->prepare("UPDATE proveedores SET nombre = ?, email = ?, telefono = ?, direccion = ? WHERE id = ?");
            $stmt->execute([$nombre, $email, $telefono, $direccion, $id]);
            $success = 'Proveedor actualizado correctamente';
        }
    } elseif ($_POST['action'] === 'toggle_estado') {
        verificarPermiso('proveedores_actualizar', 'actualizar');
        $id = (int)($_POST['id'] ?? 0);
        $nuevo = (int)($_POST['nuevo_estado'] ?? 1);
        if ($id > 0) {
            $stmt = $db->prepare("UPDATE proveedores SET activo = ? WHERE id = ?");
            $stmt->execute([$nuevo, $id]);
            $success = $nuevo ? 'Proveedor activado' : 'Proveedor inactivado';
        } else {
            $error = 'ID de proveedor inválido';
        }
    } elseif ($_POST['action'] === 'eliminar_proveedor') {
        verificarPermiso('proveedores_eliminar', 'eliminar');
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            // Verificar uso en productos
            $stmtC = $db->prepare("SELECT COUNT(*) FROM productos WHERE proveedor_id = ?");
            $stmtC->execute([$id]);
            $count = (int)$stmtC->fetchColumn();
            if ($count > 0) {
                $error = 'No se puede eliminar: hay productos asociados. Inactivalo o reasigna los productos.';
            } else {
                $stmtDel = $db->prepare("DELETE FROM proveedores WHERE id = ?");
                $stmtDel->execute([$id]);
                $success = 'Proveedor eliminado definitivamente';
            }
        } else {
            $error = 'ID de proveedor inválido';
        }
    } elseif ($_POST['action'] === 'reasignar_productos') {
        verificarPermiso('proveedores_actualizar', 'actualizar');
        $origen_id = (int)($_POST['origen_id'] ?? 0);
        $destino_raw = $_POST['destino_id'] ?? '';
        $to_null = ($destino_raw === 'null' || $destino_raw === '');
        $destino_id = $to_null ? null : (int)$destino_raw;
        if ($origen_id <= 0 || (!$to_null && ($destino_id <= 0 || $origen_id === $destino_id))) {
            $error = 'Parámetros de reasignación inválidos';
        } else {
            if ($to_null) {
                $stmtUpd = $db->prepare("UPDATE productos SET proveedor_id = NULL WHERE proveedor_id = ?");
                $stmtUpd->execute([$origen_id]);
                $movidos = $stmtUpd->rowCount();
                $success = "Productos reasignados a 'Sin proveedor': $movidos";
                require_once 'includes/logger.php';
                getLogger()->info('reasignacion_proveedor', 'productos', 'Reasignación a Sin proveedor', [
                    'proveedor_origen' => $origen_id,
                    'proveedor_destino' => null,
                    'movidos' => $movidos
                ]);
            } else {
                // Validar que destino exista
                $stmtV = $db->prepare("SELECT id FROM proveedores WHERE id = ? AND activo = 1");
                $stmtV->execute([$destino_id]);
                if ($stmtV->rowCount() === 0) {
                    $error = 'Proveedor destino no válido';
                } else {
                    $stmtUpd = $db->prepare("UPDATE productos SET proveedor_id = ? WHERE proveedor_id = ?");
                    $stmtUpd->execute([$destino_id, $origen_id]);
                    $movidos = $stmtUpd->rowCount();
                    $success = "Productos reasignados: $movidos";
                    require_once 'includes/logger.php';
                    getLogger()->info('reasignacion_proveedor', 'productos', 'Reasignación de proveedor', [
                        'proveedor_origen' => $origen_id,
                        'proveedor_destino' => $destino_id,
                        'movidos' => $movidos
                    ]);
                }
            }
        }
    }
}

$stmt_proveedores = $db->prepare("SELECT p.*, COALESCE(prod.cnt,0) AS productos_asociados
                                  FROM proveedores p
                                  LEFT JOIN (
                                    SELECT proveedor_id, COUNT(*) AS cnt
                                    FROM productos
                                    WHERE proveedor_id IS NOT NULL
                                    GROUP BY proveedor_id
                                  ) prod ON prod.proveedor_id = p.id
                                  ORDER BY p.activo DESC, p.nombre");
$stmt_proveedores->execute();
$proveedores = $stmt_proveedores->fetchAll(PDO::FETCH_ASSOC);

include 'includes/layout_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-truck"></i> <span class="editable" data-label="proveedores_titulo">Proveedores</span></h2>
    <div class="btn-group">
        <a class="btn btn-outline-success" href="proveedores.php?export=csv">
            <i class="fas fa-file-csv"></i> Exportar CSV
        </a>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalProveedor">
            <i class="fas fa-plus"></i> Nuevo Proveedor
        </button>
    </div>
  </div>

<?php if (isset($success)): ?>
<script>document.addEventListener('DOMContentLoaded',()=>showSuccess('<?php echo addslashes($success); ?>'));</script>
<?php endif; ?>
<?php if (isset($error)): ?>
<script>document.addEventListener('DOMContentLoaded',()=>showError('<?php echo addslashes($error); ?>'));</script>
<?php endif; ?>

<div class="card">
  <div class="card-header"><i class="fas fa-list"></i> Lista de Proveedores</div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-striped">
        <thead>
          <tr>
            <th>Nombre</th>
            <th>Email</th>
            <th>Teléfono</th>
            <th>Dirección</th>
            <th>Productos</th>
            <th>Estado</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($proveedores as $prov): ?>
          <tr>
            <td><?php echo htmlspecialchars($prov['nombre']); ?></td>
            <td><?php echo htmlspecialchars($prov['email']); ?></td>
            <td><?php echo htmlspecialchars($prov['telefono']); ?></td>
            <td><?php echo htmlspecialchars($prov['direccion']); ?></td>
            <td>
              <span class="badge bg-info"><?php echo (int)$prov['productos_asociados']; ?></span>
            </td>
            <td>
              <?php if ($prov['activo']): ?>
                <span class="badge bg-success">Activo</span>
              <?php else: ?>
                <span class="badge bg-secondary">Inactivo</span>
              <?php endif; ?>
            </td>
            <td>
              <div class="btn-group btn-group-sm">
                <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEditarProveedor"
                        data-id="<?php echo $prov['id']; ?>"
                        data-nombre="<?php echo htmlspecialchars($prov['nombre']); ?>"
                        data-email="<?php echo htmlspecialchars($prov['email']); ?>"
                        data-telefono="<?php echo htmlspecialchars($prov['telefono']); ?>"
                        data-direccion="<?php echo htmlspecialchars($prov['direccion']); ?>">
                  <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#modalReasignarProveedor"
                        data-id="<?php echo $prov['id']; ?>" data-nombre="<?php echo htmlspecialchars($prov['nombre']); ?>"
                        title="Reasignar productos" <?php echo ((int)$prov['productos_asociados']>0 && count($proveedores)>1) ? '' : 'disabled'; ?>>
                  <i class="fas fa-random"></i>
                </button>
                <form method="POST" class="d-inline" onsubmit="return confirm('¿Confirmar cambio de estado?');">
                  <input type="hidden" name="action" value="toggle_estado">
                  <input type="hidden" name="id" value="<?php echo $prov['id']; ?>">
                  <input type="hidden" name="nuevo_estado" value="<?php echo $prov['activo'] ? 0 : 1; ?>">
                  <?php echo campoCSRF(); ?>
                  <button class="btn btn-outline-<?php echo $prov['activo'] ? 'secondary' : 'success'; ?>" type="submit" title="<?php echo $prov['activo'] ? 'Inactivar' : 'Activar'; ?>">
                    <i class="fas fa-toggle-<?php echo $prov['activo'] ? 'off' : 'on'; ?>"></i>
                  </button>
                </form>
                <form method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar proveedor? Esta acción no se puede deshacer');">
                  <input type="hidden" name="action" value="eliminar_proveedor">
                  <input type="hidden" name="id" value="<?php echo $prov['id']; ?>">
                  <?php echo campoCSRF(); ?>
                  <button class="btn btn-outline-danger" type="submit" title="Eliminar" <?php echo ((int)$prov['productos_asociados']>0) ? 'disabled' : ''; ?>>
                    <i class="fas fa-trash"></i>
                  </button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($proveedores)): ?>
          <tr><td colspan="6" class="text-center text-muted">Sin proveedores</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal Crear Proveedor -->
<div class="modal fade" id="modalProveedor" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <input type="hidden" name="action" value="crear_proveedor">
        <?php echo campoCSRF(); ?>
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-truck"></i> Nuevo Proveedor</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Nombre *</label>
            <input type="text" class="form-control" name="nombre" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" name="email">
          </div>
          <div class="mb-3">
            <label class="form-label">Teléfono</label>
            <input type="text" class="form-control" name="telefono">
          </div>
          <div class="mb-3">
            <label class="form-label">Dirección</label>
            <input type="text" class="form-control" name="direccion">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Crear</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include 'includes/layout_footer.php'; ?>

<script>
// Rellenar modal de edición con datos del proveedor
const modalEditar = document.getElementById('modalEditarProveedor');
if (modalEditar) {
  modalEditar.addEventListener('show.bs.modal', event => {
    const button = event.relatedTarget;
    const id = button.getAttribute('data-id');
    const nombre = button.getAttribute('data-nombre');
    const email = button.getAttribute('data-email');
    const telefono = button.getAttribute('data-telefono');
    const direccion = button.getAttribute('data-direccion');
    modalEditar.querySelector('input[name="id"]').value = id;
    modalEditar.querySelector('input[name="nombre"]').value = nombre || '';
    modalEditar.querySelector('input[name="email"]').value = email || '';
    modalEditar.querySelector('input[name="telefono"]').value = telefono || '';
    modalEditar.querySelector('input[name="direccion"]').value = direccion || '';
  });
}

// Rellenar modal de reasignación
const modalReasignar = document.getElementById('modalReasignarProveedor');
if (modalReasignar) {
  modalReasignar.addEventListener('show.bs.modal', event => {
    const button = event.relatedTarget;
    const id = button.getAttribute('data-id');
    const nombre = button.getAttribute('data-nombre');
    modalReasignar.querySelector('input[name="origen_id"]').value = id;
    modalReasignar.querySelector('#proveedorOrigenNombre').textContent = nombre || '';
    const select = modalReasignar.querySelector('select[name="destino_id"]');
    // Habilitar todas y luego deshabilitar la opción igual al origen
    Array.from(select.options).forEach(opt => {
      opt.disabled = false;
      if (opt.value === id) opt.disabled = true;
    });
    // Seleccionar primer destino válido
    const firstValid = Array.from(select.options).find(opt => !opt.disabled && opt.value !== '');
    if (firstValid) select.value = firstValid.value;
  });
}
</script>

<!-- Modal Editar Proveedor -->
<div class="modal fade" id="modalEditarProveedor" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <input type="hidden" name="action" value="editar_proveedor">
        <input type="hidden" name="id">
        <?php echo campoCSRF(); ?>
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-pen"></i> Editar Proveedor</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Nombre *</label>
            <input type="text" class="form-control" name="nombre" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" name="email">
          </div>
          <div class="mb-3">
            <label class="form-label">Teléfono</label>
            <input type="text" class="form-control" name="telefono">
          </div>
          <div class="mb-3">
            <label class="form-label">Dirección</label>
            <input type="text" class="form-control" name="direccion">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Guardar Cambios</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Reasignar Productos -->
<div class="modal fade" id="modalReasignarProveedor" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <input type="hidden" name="action" value="reasignar_productos">
        <input type="hidden" name="origen_id">
        <?php echo campoCSRF(); ?>
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-random"></i> Reasignar productos</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-warning small">
            Reasignará todos los productos del proveedor <strong id="proveedorOrigenNombre"></strong> al proveedor destino seleccionado.
          </div>
          <div class="mb-3">
            <label class="form-label">Proveedor destino</label>
            <select class="form-select" name="destino_id" required>
              <option value="">Seleccionar proveedor...</option>
              <option value="null">Sin proveedor</option>
              <?php foreach ($proveedores as $p): ?>
                <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['nombre']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Reasignar</button>
        </div>
      </form>
    </div>
  </div>
</div>
