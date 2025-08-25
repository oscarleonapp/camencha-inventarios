<?php
$titulo = "Historial de Ventas - Sistema de Inventarios";
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'includes/config_functions.php';

verificarLogin();
verificarPermiso('ventas_ver');

$database = new Database();
$db = $database->getConnection();

// Filtros de búsqueda
$filtro_tienda = isset($_GET['tienda']) ? $_GET['tienda'] : '';
$filtro_estado = isset($_GET['estado']) ? $_GET['estado'] : '';
$filtro_fecha_desde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : '';
$filtro_fecha_hasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : '';
$filtro_vendedor = isset($_GET['vendedor']) ? $_GET['vendedor'] : '';

// Construir consulta con filtros
$where_conditions = ['1=1'];
$params = [];

if ($filtro_tienda) {
    $where_conditions[] = "v.tienda_id = ?";
    $params[] = $filtro_tienda;
}

if ($filtro_estado) {
    $where_conditions[] = "v.estado = ?";
    $params[] = $filtro_estado;
}

if ($filtro_fecha_desde) {
    $where_conditions[] = "DATE(v.fecha) >= ?";
    $params[] = $filtro_fecha_desde;
}

if ($filtro_fecha_hasta) {
    $where_conditions[] = "DATE(v.fecha) <= ?";
    $params[] = $filtro_fecha_hasta;
}

if ($filtro_vendedor) {
    $where_conditions[] = "v.usuario_id = ?";
    $params[] = $filtro_vendedor;
}

$where_clause = implode(' AND ', $where_conditions);

// Obtener ventas con filtros
$query_ventas = "SELECT v.*, t.nombre as tienda_nombre, u.nombre as usuario_nombre,
                        COUNT(dv.id) as total_productos,
                        GROUP_CONCAT(CONCAT(p.nombre, ' (', dv.cantidad, ')') SEPARATOR ', ') as productos_resumen
                FROM ventas v 
                JOIN tiendas t ON v.tienda_id = t.id 
                JOIN usuarios u ON v.usuario_id = u.id 
                LEFT JOIN detalle_ventas dv ON v.id = dv.venta_id
                LEFT JOIN productos p ON dv.producto_id = p.id
                WHERE {$where_clause}
                GROUP BY v.id
                ORDER BY v.fecha DESC";

$stmt_ventas = $db->prepare($query_ventas);
$stmt_ventas->execute($params);
$ventas = $stmt_ventas->fetchAll(PDO::FETCH_ASSOC);

// Obtener estadísticas
$query_stats = "SELECT 
                    COUNT(*) as total_ventas,
                    COALESCE(SUM(total), 0) as total_ingresos,
                    COALESCE(AVG(total), 0) as promedio_venta,
                    COUNT(CASE WHEN estado = 'pendiente' THEN 1 END) as ventas_pendientes,
                    COUNT(CASE WHEN estado = 'entregada' THEN 1 END) as ventas_entregadas,
                    COUNT(CASE WHEN estado = 'reembolsada' THEN 1 END) as ventas_reembolsadas
                FROM ventas v
                WHERE {$where_clause}";

$stmt_stats = $db->prepare($query_stats);
$stmt_stats->execute($params);
$estadisticas = $stmt_stats->fetch(PDO::FETCH_ASSOC);

// Obtener datos para filtros
$query_tiendas = "SELECT * FROM tiendas WHERE activo = 1 ORDER BY nombre";
$stmt_tiendas = $db->prepare($query_tiendas);
$stmt_tiendas->execute();
$tiendas = $stmt_tiendas->fetchAll(PDO::FETCH_ASSOC);

$query_vendedores = "SELECT DISTINCT u.id, u.nombre FROM usuarios u 
                     JOIN ventas v ON u.id = v.usuario_id 
                     ORDER BY u.nombre";
$stmt_vendedores = $db->prepare($query_vendedores);
$stmt_vendedores->execute();
$vendedores = $stmt_vendedores->fetchAll(PDO::FETCH_ASSOC);

require_once 'includes/layout_header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 rs-wrap-sm">
        <h2>
            <i class="fas fa-history"></i>
            Historial de Ventas
        </h2>
        <div>
            <button class="btn btn-outline-primary" onclick="exportarExcel()">
                <i class="fas fa-file-excel"></i> Exportar Excel
            </button>
            <button class="btn btn-outline-secondary" onclick="window.print()">
                <i class="fas fa-print"></i> Imprimir
            </button>
        </div>
    </div>

    <!-- Estadísticas -->
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h4><?php echo $estadisticas['total_ventas']; ?></h4>
                    <small>Total Ventas</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h5 class="moneda"><?php echo number_format($estadisticas['total_ingresos'], 2); ?></h5>
                    <small>Ingresos Totales</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h5 class="moneda"><?php echo number_format($estadisticas['promedio_venta'], 2); ?></h5>
                    <small>Promedio por Venta</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-warning text-dark">
                <div class="card-body text-center">
                    <h4><?php echo $estadisticas['ventas_pendientes']; ?></h4>
                    <small>Pendientes</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h4><?php echo $estadisticas['ventas_entregadas']; ?></h4>
                    <small>Entregadas</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-danger text-white">
                <div class="card-body text-center">
                    <h4><?php echo $estadisticas['ventas_reembolsadas']; ?></h4>
                    <small>Reembolsadas</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="mb-0">
                <i class="fas fa-filter"></i>
                Filtros de Búsqueda
            </h6>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">Tienda</label>
                    <select name="tienda" class="form-select">
                        <option value="">Todas las tiendas</option>
                        <?php foreach ($tiendas as $tienda): ?>
                            <option value="<?php echo $tienda['id']; ?>" <?php echo $filtro_tienda == $tienda['id'] ? 'selected' : ''; ?>>
                                <?php echo $tienda['nombre']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Estado</label>
                    <select name="estado" class="form-select">
                        <option value="">Todos los estados</option>
                        <option value="pendiente" <?php echo $filtro_estado == 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                        <option value="entregada" <?php echo $filtro_estado == 'entregada' ? 'selected' : ''; ?>>Entregada</option>
                        <option value="reembolsada" <?php echo $filtro_estado == 'reembolsada' ? 'selected' : ''; ?>>Reembolsada</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Vendedor</label>
                    <select name="vendedor" class="form-select">
                        <option value="">Todos los vendedores</option>
                        <?php foreach ($vendedores as $vendedor): ?>
                            <option value="<?php echo $vendedor['id']; ?>" <?php echo $filtro_vendedor == $vendedor['id'] ? 'selected' : ''; ?>>
                                <?php echo $vendedor['nombre']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Desde</label>
                    <input type="date" name="fecha_desde" class="form-control" value="<?php echo $filtro_fecha_desde; ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Hasta</label>
                    <input type="date" name="fecha_hasta" class="form-control" value="<?php echo $filtro_fecha_hasta; ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-search"></i> Filtrar
                    </button>
                    <a href="historial_ventas.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i> Limpiar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de Ventas -->
    <div class="card">
        <div class="card-header">
            <h6 class="mb-0">
                <i class="fas fa-list"></i>
                Listado de Ventas (<?php echo count($ventas); ?> registros)
            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive-md">
                <table class="table table-striped table-hover accessibility-fix">
                    <thead class="thead-titulos">
                        <tr>
                            <th>ID</th>
                            <th>Fecha/Hora</th>
                            <th>Tienda</th>
                            <th>Vendedor</th>
                            <th>Productos</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($ventas)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted">
                                <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                No se encontraron ventas con los filtros seleccionados
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($ventas as $venta): ?>
                            <tr>
                                <td><strong>#<?php echo $venta['id']; ?></strong></td>
                                <td>
                                    <?php echo date('d/m/Y', strtotime($venta['fecha'])); ?><br>
                                    <small class="text-muted"><?php echo date('H:i:s', strtotime($venta['fecha'])); ?></small>
                                </td>
                                <td><?php echo $venta['tienda_nombre']; ?></td>
                                <td><?php echo $venta['usuario_nombre']; ?></td>
                                <td>
                                    <small class="text-muted">
                                        <?php 
                                        $productos = explode(', ', $venta['productos_resumen']);
                                        if (count($productos) > 2) {
                                            echo implode(', ', array_slice($productos, 0, 2)) . '...';
                                        } else {
                                            echo $venta['productos_resumen'];
                                        }
                                        ?>
                                        <br><span class="badge bg-secondary"><?php echo $venta['total_productos']; ?> item(s)</span>
                                    </small>
                                </td>
                                <td>
                                    <strong class="moneda"><?php echo number_format($venta['total'], 2); ?></strong>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo ($venta['estado'] ?? 'pendiente') == 'pendiente' ? 'warning' : (($venta['estado'] ?? 'pendiente') == 'entregada' ? 'success' : 'danger'); ?>">
                                        <?php echo ucfirst($venta['estado'] ?? 'pendiente'); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="detalle_venta.php?id=<?php echo $venta['id']; ?>" class="btn btn-outline-info" title="Ver Detalle">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if (tienePermiso('ventas_actualizar') && ($venta['estado'] ?? 'pendiente') == 'pendiente'): ?>
                                        <button class="btn btn-outline-success" onclick="marcarEntregada(<?php echo $venta['id']; ?>)" title="Marcar Entregada">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function exportarExcel() {
    // Obtener parámetros actuales
    const params = new URLSearchParams(window.location.search);
    params.append('export', 'excel');
    
    // Redirigir con parámetros
    window.open('historial_ventas.php?' + params.toString(), '_blank');
}

function marcarEntregada(ventaId) {
    if (confirm('¿Marcar esta venta como entregada?')) {
        window.location.href = `detalle_venta.php?id=${ventaId}&action=marcar_entregada`;
    }
}

// Configurar fechas por defecto (último mes)
document.addEventListener('DOMContentLoaded', function() {
    const fechaDesde = document.querySelector('input[name="fecha_desde"]');
    const fechaHasta = document.querySelector('input[name="fecha_hasta"]');
    
    if (!fechaDesde.value && !fechaHasta.value) {
        const hoy = new Date();
        const hace30dias = new Date();
        hace30dias.setDate(hoy.getDate() - 30);
        
        fechaHasta.value = hoy.toISOString().split('T')[0];
        fechaDesde.value = hace30dias.toISOString().split('T')[0];
    }
});
</script>

<style>
@media print {
    .btn, .card-header, .pagination {
        display: none !important;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
    }
}
</style>

<?php require_once 'includes/layout_footer.php'; ?>
