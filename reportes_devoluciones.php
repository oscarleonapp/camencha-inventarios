<?php
$titulo = "Reportes de Devoluciones - Sistema de Inventarios";
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'includes/config_functions.php';
require_once 'includes/csrf_protection.php';

verificarLogin();
verificarPermiso('inventarios_ver');

$database = new Database();
$db = $database->getConnection();

// Filtros
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01'); // Primer día del mes actual
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d'); // Día actual
$tienda_filtro = $_GET['tienda_id'] ?? '';
$producto_filtro = $_GET['producto_id'] ?? '';

// Construir consulta con filtros
$where_conditions = ["m.tipo_movimiento = 'devolucion'"];
$params = [];

if ($fecha_inicio) {
    $where_conditions[] = "DATE(m.fecha) >= ?";
    $params[] = $fecha_inicio;
}

if ($fecha_fin) {
    $where_conditions[] = "DATE(m.fecha) <= ?";
    $params[] = $fecha_fin;
}

if ($tienda_filtro) {
    $where_conditions[] = "m.tienda_destino_id = ?";
    $params[] = $tienda_filtro;
}

if ($producto_filtro) {
    $where_conditions[] = "m.producto_id = ?";
    $params[] = $producto_filtro;
}

$where_clause = implode(' AND ', $where_conditions);

// Consulta principal de devoluciones
$query_devoluciones = "SELECT 
                        m.id,
                        m.tipo_movimiento,
                        m.producto_id,
                        m.tienda_destino_id,
                        m.cantidad,
                        m.motivo,
                        m.referencia_id,
                        m.referencia_tipo,
                        m.usuario_id,
                        m.motivo as notas,
                        m.fecha,
                        t.nombre as tienda_nombre,
                        p.codigo as producto_codigo,
                        p.nombre as producto_nombre,
                        p.precio_venta,
                        u.nombre as usuario_nombre,
                        DATE(m.fecha) as fecha_devolucion,
                        TIME(m.fecha) as hora_devolucion
                      FROM movimientos_inventario m
                      JOIN tiendas t ON m.tienda_destino_id = t.id
                      JOIN productos p ON m.producto_id = p.id
                      JOIN usuarios u ON m.usuario_id = u.id
                      WHERE $where_clause
                      ORDER BY m.fecha DESC";

$stmt_devoluciones = $db->prepare($query_devoluciones);
$stmt_devoluciones->execute($params);
$devoluciones = $stmt_devoluciones->fetchAll(PDO::FETCH_ASSOC);

// Estadísticas de devoluciones
$query_stats = "SELECT 
                  COUNT(*) as total_devoluciones,
                  SUM(m.cantidad) as total_unidades_devueltas,
                  SUM(m.cantidad * p.precio_venta) as valor_total_devuelto,
                  AVG(m.cantidad) as promedio_cantidad
                FROM movimientos_inventario m
                JOIN productos p ON m.producto_id = p.id
                WHERE $where_clause";

$stmt_stats = $db->prepare($query_stats);
$stmt_stats->execute($params);
$stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);

// Top productos devueltos
$query_top_productos = "SELECT 
                         p.codigo,
                         p.nombre,
                         COUNT(*) as num_devoluciones,
                         SUM(m.cantidad) as total_unidades,
                         SUM(m.cantidad * p.precio_venta) as valor_total
                       FROM movimientos_inventario m
                       JOIN productos p ON m.producto_id = p.id
                       WHERE $where_clause
                       GROUP BY p.id, p.codigo, p.nombre
                       ORDER BY num_devoluciones DESC, total_unidades DESC
                       LIMIT 10";

$stmt_top_productos = $db->prepare($query_top_productos);
$stmt_top_productos->execute($params);
$top_productos = $stmt_top_productos->fetchAll(PDO::FETCH_ASSOC);

// Devoluciones por motivo
$query_motivos = "SELECT 
                   SUBSTRING_INDEX(SUBSTRING_INDEX(m.motivo, ' - ', -1), '.', 1) as motivo_detallado,
                   COUNT(*) as cantidad,
                   SUM(m.cantidad) as total_unidades
                 FROM movimientos_inventario m
                 WHERE $where_clause AND m.motivo LIKE 'Ingreso por devolución - %'
                 GROUP BY motivo_detallado
                 ORDER BY cantidad DESC";

$stmt_motivos = $db->prepare($query_motivos);
$stmt_motivos->execute($params);
$motivos = $stmt_motivos->fetchAll(PDO::FETCH_ASSOC);

// Obtener listas para filtros
$query_tiendas = "SELECT * FROM tiendas WHERE activo = 1 ORDER BY nombre";
$stmt_tiendas = $db->prepare($query_tiendas);
$stmt_tiendas->execute();
$tiendas = $stmt_tiendas->fetchAll(PDO::FETCH_ASSOC);

$query_productos = "SELECT * FROM productos WHERE activo = 1 ORDER BY nombre";
$stmt_productos = $db->prepare($query_productos);
$stmt_productos->execute();
$productos = $stmt_productos->fetchAll(PDO::FETCH_ASSOC);

include 'includes/layout_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 rs-wrap-sm">
    <h2><i class="fas fa-undo-alt"></i> Reportes de Devoluciones</h2>
    <div class="btn-group rs-wrap-sm">
        <button class="btn btn-outline-success" onclick="exportarReporte()">
            <i class="fas fa-file-excel"></i> Exportar Excel
        </button>
        <button class="btn btn-outline-primary" onclick="imprimirReporte()">
            <i class="fas fa-print"></i> Imprimir
        </button>
    </div>
</div>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-filter"></i> Filtros de Búsqueda</h5>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Fecha Inicio</label>
                <input type="date" class="form-control" name="fecha_inicio" value="<?php echo $fecha_inicio; ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Fecha Fin</label>
                <input type="date" class="form-control" name="fecha_fin" value="<?php echo $fecha_fin; ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Tienda</label>
                <select class="form-control" name="tienda_id">
                    <option value="">Todas las tiendas</option>
                    <?php foreach ($tiendas as $tienda): ?>
                        <option value="<?php echo $tienda['id']; ?>" <?php echo $tienda_filtro == $tienda['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($tienda['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Producto</label>
                <select class="form-control" name="producto_id">
                    <option value="">Todos los productos</option>
                    <?php foreach ($productos as $producto): ?>
                        <option value="<?php echo $producto['id']; ?>" <?php echo $producto_filtro == $producto['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($producto['codigo'] . ' - ' . $producto['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Aplicar Filtros
                </button>
                <a href="reportes_devoluciones.php" class="btn btn-outline-secondary">
                    <i class="fas fa-times"></i> Limpiar Filtros
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Estadísticas Generales -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between rs-wrap-sm">
                    <div>
                        <h4><?php echo number_format($stats['total_devoluciones']); ?></h4>
                        <p class="mb-0">Total Devoluciones</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-undo fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-dark">
            <div class="card-body">
                <div class="d-flex justify-content-between rs-wrap-sm">
                    <div>
                        <h4><?php echo number_format($stats['total_unidades_devueltas']); ?></h4>
                        <p class="mb-0">Unidades Devueltas</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-boxes fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-danger text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between rs-wrap-sm">
                    <div>
                        <h4><?php echo formatearMoneda($stats['valor_total_devuelto']); ?></h4>
                        <p class="mb-0">Valor Total Devuelto</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-dollar-sign fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between rs-wrap-sm">
                    <div>
                        <h4><?php echo number_format($stats['promedio_cantidad'], 1); ?></h4>
                        <p class="mb-0">Promedio por Devolución</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-chart-bar fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Lista de Devoluciones -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-list"></i> Historial de Devoluciones</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive-md">
                    <table class="table table-striped table-hover" id="tablaDevoluciones">
                        <thead class="thead-titulos">
                            <tr>
                                <th>Fecha</th>
                                <th>Hora</th>
                                <th>Tienda</th>
                                <th>Producto</th>
                                <th>Cantidad</th>
                                <th>Valor</th>
                                <th>Motivo</th>
                                <th>Usuario</th>
                                <th>Referencia</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($devoluciones as $dev): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($dev['fecha_devolucion'])); ?></td>
                                <td><?php echo date('H:i', strtotime($dev['hora_devolucion'])); ?></td>
                                <td><?php echo htmlspecialchars($dev['tienda_nombre']); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($dev['producto_codigo']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($dev['producto_nombre']); ?></small>
                                </td>
                                <td><span class="badge bg-info"><?php echo $dev['cantidad']; ?></span></td>
                                <td><?php echo formatearMoneda($dev['cantidad'] * $dev['precio_venta']); ?></td>
                                <td>
                                    <small>
                                        <?php 
                                        if (strpos($dev['notas'], 'Ingreso por devolución - ') !== false) {
                                            echo htmlspecialchars(str_replace('Ingreso por devolución - ', '', $dev['notas']));
                                        } else {
                                            echo htmlspecialchars($dev['notas']);
                                        }
                                        ?>
                                    </small>
                                </td>
                                <td><?php echo htmlspecialchars($dev['usuario_nombre']); ?></td>
                                <td>
                                    <?php if (!empty($dev['referencia_id'])): ?>
                                        <span class="badge bg-secondary">#<?php echo htmlspecialchars($dev['referencia_id']); ?></span>
                                    <?php else: ?>
                                        <small class="text-muted">Sin referencia</small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <?php if (empty($devoluciones)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-inbox fa-3x text-muted"></i>
                            <h5 class="mt-3 text-muted">No hay devoluciones registradas</h5>
                            <p class="text-muted">Con los filtros seleccionados no se encontraron devoluciones.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Panel Lateral con Estadísticas -->
    <div class="col-md-4">
        <!-- Top Productos Devueltos -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-trophy"></i> Productos Más Devueltos</h6>
            </div>
            <div class="card-body">
                <?php foreach ($top_productos as $index => $producto): ?>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <strong><?php echo ($index + 1); ?>. <?php echo htmlspecialchars($producto['codigo']); ?></strong>
                            <br>
                            <small class="text-muted"><?php echo htmlspecialchars($producto['nombre']); ?></small>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-warning"><?php echo $producto['num_devoluciones']; ?></span><br>
                            <small class="text-muted"><?php echo $producto['total_unidades']; ?> unidades</small>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php if (empty($top_productos)): ?>
                    <p class="text-muted text-center">Sin datos disponibles</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Motivos de Devolución -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-chart-pie"></i> Motivos de Devolución</h6>
            </div>
            <div class="card-body">
                <?php foreach ($motivos as $motivo): ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span><?php echo htmlspecialchars($motivo['motivo']); ?></span>
                            <span class="badge bg-secondary"><?php echo $motivo['cantidad']; ?></span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-info" style="width: <?php echo ($stats['total_devoluciones'] > 0) ? ($motivo['cantidad'] / $stats['total_devoluciones'] * 100) : 0; ?>%"></div>
                        </div>
                        <small class="text-muted"><?php echo $motivo['total_unidades']; ?> unidades</small>
                    </div>
                <?php endforeach; ?>
                
                <?php if (empty($motivos)): ?>
                    <p class="text-muted text-center">Sin datos disponibles</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function exportarReporte() {
    // Implementar exportación a Excel
    alert('Función de exportar a Excel en desarrollo');
}

function imprimirReporte() {
    window.print();
}

// DataTable para mejor navegación
$(document).ready(function() {
    $('#tablaDevoluciones').DataTable({
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json'
        },
        order: [[0, 'desc']],
        pageLength: 25,
        responsive: true
    });
});
</script>

<?php include 'includes/layout_footer.php'; ?>
