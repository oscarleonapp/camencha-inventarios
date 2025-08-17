<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/csrf_protection.php';

header('Content-Type: application/json; charset=utf-8');

try {
    verificarLogin();
    verificarPermiso('inventarios_actualizar', 'actualizar');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Método no permitido']);
        exit;
    }

    validarCSRF();

    $tienda_id = isset($_POST['tienda_id']) ? (int)$_POST['tienda_id'] : 0;
    $producto_id = isset($_POST['producto_id']) ? (int)$_POST['producto_id'] : 0;
    $nueva_cantidad = isset($_POST['nueva_cantidad']) ? (int)$_POST['nueva_cantidad'] : null;
    $nueva_reparacion = isset($_POST['nueva_reparacion']) ? (int)$_POST['nueva_reparacion'] : null;

    if ($tienda_id <= 0 || $producto_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Parámetros inválidos']);
        exit;
    }

    $database = new Database();
    $db = $database->getConnection();

    // Obtener inventario actual
    $stmt = $db->prepare("SELECT cantidad, COALESCE(cantidad_reparacion,0) AS cantidad_reparacion FROM inventarios WHERE tienda_id = ? AND producto_id = ?");
    $stmt->execute([$tienda_id, $producto_id]);
    $inv = $stmt->fetch(PDO::FETCH_ASSOC);

    $usuario_id = $_SESSION['usuario_id'] ?? null;

    if ($inv) {
        $actual_total = (int)$inv['cantidad'];
        $en_reparacion = (int)$inv['cantidad_reparacion'];
        $actual_disponible = $actual_total - $en_reparacion;

        // Ajuste de disponible (ajusta total)
        if ($nueva_cantidad !== null) {
            if ($nueva_cantidad < 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Cantidad inválida']);
                exit;
            }
            $diferencia = $nueva_cantidad - $actual_disponible;
            if ($diferencia !== 0) {
                $nuevo_total = max(0, $actual_total + $diferencia);
                $stmtU = $db->prepare("UPDATE inventarios SET cantidad = ? WHERE tienda_id = ? AND producto_id = ?");
                $stmtU->execute([$nuevo_total, $tienda_id, $producto_id]);

                // Registrar movimiento entrada/salida
                $tipo = $diferencia > 0 ? 'entrada' : 'salida';
                $cant_mov = abs($diferencia);
                if ($cant_mov > 0) {
                    $stmtM = $db->prepare("INSERT INTO movimientos_inventario (tipo, producto_id, tienda_destino_id, cantidad, motivo, usuario_id) VALUES (?, ?, ?, ?, 'Ajuste inline', ?)");
                    $stmtM->execute([$tipo, $producto_id, $tienda_id, $cant_mov, $usuario_id]);
                }

                echo json_encode([
                    'success' => true,
                    'cantidad_total' => $nuevo_total,
                    'cantidad_reparacion' => $en_reparacion,
                    'cantidad_disponible' => $nuevo_total - $en_reparacion
                ]);
                exit;
            } else {
                echo json_encode([
                    'success' => true,
                    'cantidad_total' => $actual_total,
                    'cantidad_reparacion' => $en_reparacion,
                    'cantidad_disponible' => $actual_disponible
                ]);
                exit;
            }
        }

        // Ajuste de en reparación (mantiene total)
        if ($nueva_reparacion !== null) {
            if ($nueva_reparacion < 0 || $nueva_reparacion > $actual_total) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'En reparación inválido']);
                exit;
            }
            $dif_rep = $nueva_reparacion - $en_reparacion;
            if ($dif_rep !== 0) {
                $stmtU = $db->prepare("UPDATE inventarios SET cantidad_reparacion = ? WHERE tienda_id = ? AND producto_id = ?");
                $stmtU->execute([$nueva_reparacion, $tienda_id, $producto_id]);

                // Registrar ajuste reparación
                $cant_mov = abs($dif_rep);
                $stmtM = $db->prepare("INSERT INTO movimientos_inventario (tipo, producto_id, tienda_destino_id, cantidad, motivo, usuario_id) VALUES ('ajuste', ?, ?, ?, 'Ajuste reparación inline', ?)");
                $stmtM->execute([$producto_id, $tienda_id, $cant_mov, $usuario_id]);

                echo json_encode([
                    'success' => true,
                    'cantidad_total' => $actual_total,
                    'cantidad_reparacion' => $nueva_reparacion,
                    'cantidad_disponible' => $actual_total - $nueva_reparacion
                ]);
                exit;
            } else {
                echo json_encode([
                    'success' => true,
                    'cantidad_total' => $actual_total,
                    'cantidad_reparacion' => $en_reparacion,
                    'cantidad_disponible' => $actual_disponible
                ]);
                exit;
            }
        }

        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Sin cambios']);
        exit;
    } else {
        // Crear registro
        $cant_init = $nueva_cantidad !== null ? $nueva_cantidad : 0;
        $rep_init = $nueva_reparacion !== null ? max(0, (int)$nueva_reparacion) : 0;
        if ($rep_init > $cant_init) { $rep_init = $cant_init; }
        $stmtI = $db->prepare("INSERT INTO inventarios (tienda_id, producto_id, cantidad, cantidad_reparacion) VALUES (?, ?, ?, ?)");
        $stmtI->execute([$tienda_id, $producto_id, $cant_init, $rep_init]);

        // Movimiento de entrada inicial
        if ($cant_init > 0) {
            $stmtM = $db->prepare("INSERT INTO movimientos_inventario (tipo, producto_id, tienda_destino_id, cantidad, motivo, usuario_id) VALUES ('entrada', ?, ?, ?, 'Inventario inicial inline', ?)");
            $stmtM->execute([$producto_id, $tienda_id, $cant_init, $usuario_id]);
        }

        echo json_encode([
            'success' => true,
            'cantidad_total' => $cant_init,
            'cantidad_reparacion' => $rep_init,
            'cantidad_disponible' => $cant_init - $rep_init
        ]);
        exit;
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error en el servidor', 'detalle' => $e->getMessage()]);
}
