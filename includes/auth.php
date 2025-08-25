<?php
require_once __DIR__ . '/session_security.php'; // Configura sesión segura automáticamente
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/logger.php';

function verificarLogin() {
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: login.php');
        exit();
    }
}

function esAdmin() {
    return isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin';
}

function tienePermiso($permiso, $accion = 'leer') {
    if (!isset($_SESSION['usuario_id'])) {
        return false;
    }
    
    if (esAdmin()) {
        return true;
    }
    
    if (!isset($_SESSION['permisos'])) {
        cargarPermisos();
    }
    
    $campo_accion = 'puede_' . $accion;
    
    return isset($_SESSION['permisos'][$permiso]) && $_SESSION['permisos'][$permiso][$campo_accion];
}

function cargarPermisos() {
    if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['rol_id'])) {
        return;
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT p.nombre, rp.puede_crear, rp.puede_leer, rp.puede_actualizar, rp.puede_eliminar
              FROM permisos p 
              JOIN rol_permisos rp ON p.id = rp.permiso_id 
              WHERE rp.rol_id = ? AND p.activo = 1";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$_SESSION['rol_id']]);
    
    $_SESSION['permisos'] = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $_SESSION['permisos'][$row['nombre']] = [
            'puede_crear' => $row['puede_crear'],
            'puede_leer' => $row['puede_leer'],
            'puede_actualizar' => $row['puede_actualizar'],
            'puede_eliminar' => $row['puede_eliminar']
        ];
    }
}

function verificarPermiso($permiso, $accion = 'leer') {
    if (!tienePermiso($permiso, $accion)) {
        // Log acceso denegado
        getLogger()->accesoDenegado($_SERVER['REQUEST_URI'] ?? 'unknown', $permiso . '_' . $accion);
        header('Location: sin_permisos.php');
        exit();
    }
}

function obtenerMenuModulos() {
    if (!isset($_SESSION['usuario_id'])) {
        return [];
    }
    
    if (!isset($_SESSION['permisos'])) {
        cargarPermisos();
    }
    
    $menu_items = [
        [
            'id' => 'dashboard',
            'nombre' => 'Dashboard',
            'url' => 'index.php',
            'icono' => 'fas fa-tachometer-alt',
            'permiso' => 'dashboard'
        ],
        [
            'id' => 'productos',
            'nombre' => 'Productos',
            'icono' => 'fas fa-boxes',
            'submenu' => [
                [
                    'nombre' => 'Gestión de Productos',
                    'url' => 'productos.php',
                    'icono' => 'fas fa-box',
                    'permiso' => 'productos_ver'
                ],
                [
                    'nombre' => 'Importar Productos',
                    'url' => 'importar_productos.php',
                    'icono' => 'fas fa-upload',
                    'permiso' => 'productos_crear'
                ],
                [
                    'nombre' => 'Cambios de Productos',
                    'url' => 'logs_productos.php',
                    'icono' => 'fas fa-history',
                    'permiso' => 'productos_ver'
                ],
                [
                    'nombre' => 'Proveedores',
                    'url' => 'proveedores.php',
                    'icono' => 'fas fa-truck',
                    'permiso' => 'proveedores_ver'
                ]
            ]
        ],
        [
            'id' => 'inventarios',
            'nombre' => 'Inventarios',
            'icono' => 'fas fa-warehouse',
            'submenu' => [
                [
                    'nombre' => 'Control de Stock',
                    'url' => 'inventarios.php',
                    'icono' => 'fas fa-boxes',
                    'permiso' => 'inventarios_ver'
                ],
                [
                    'nombre' => 'Ingreso por Devolución',
                    'url' => 'ingreso_devolucion.php',
                    'icono' => 'fas fa-undo',
                    'permiso' => 'inventarios_ver'
                ],
                [
                    'nombre' => 'Traslados',
                    'url' => 'traslados.php',
                    'icono' => 'fas fa-exchange-alt',
                    'permiso' => 'inventarios_transferir'
                ],
                [
                    'nombre' => 'Reporte Devoluciones',
                    'url' => 'reportes_devoluciones.php',
                    'icono' => 'fas fa-undo-alt',
                    'permiso' => 'inventarios_ver'
                ]
            ]
        ],
        [
            'id' => 'ventas',
            'nombre' => 'Ventas',
            'icono' => 'fas fa-shopping-cart',
            'submenu' => [
                [
                    'nombre' => 'POS - Punto de Venta',
                    'url' => 'pos.php',
                    'icono' => 'fas fa-credit-card',
                    'permiso' => 'ventas_crear'
                ],
                [
                    'nombre' => 'Realizar Ventas',
                    'url' => 'ventas.php',
                    'icono' => 'fas fa-cash-register',
                    'permiso' => 'ventas_crear'
                ],
                [
                    'nombre' => 'Historial de Ventas',
                    'url' => 'historial_ventas.php',
                    'icono' => 'fas fa-history',
                    'permiso' => 'ventas_ver'
                ],
                [
                    'nombre' => 'Reportes de Vendedores',
                    'url' => 'reportes_vendedores.php',
                    'icono' => 'fas fa-chart-line',
                    'permiso' => 'ventas_ver'
                ]
            ]
        ],
        [
            'id' => 'tiendas',
            'nombre' => 'Tiendas',
            'icono' => 'fas fa-store',
            'submenu' => [
                [
                    'nombre' => 'Nueva Tienda',
                    'url' => 'nueva_tienda.php',
                    'icono' => 'fas fa-plus',
                    'permiso' => 'tiendas_crear'
                ],
                [
                    'nombre' => 'Lista de Tiendas',
                    'url' => 'lista_tiendas.php',
                    'icono' => 'fas fa-list',
                    'permiso' => 'tiendas_ver'
                ],
                [
                    'nombre' => 'Lista de Encargados',
                    'url' => 'lista_encargados.php',
                    'icono' => 'fas fa-users',
                    'permiso' => 'tiendas_ver'
                ]
            ]
        ],
        [
            'id' => 'cotizaciones',
            'nombre' => 'Cotizaciones',
            'url' => 'cotizaciones.php',
            'icono' => 'fas fa-file-signature',
            'permiso' => 'cotizaciones_ver'
        ],
        [
            'id' => 'vendedores',
            'nombre' => 'Vendedores',
            'icono' => 'fas fa-user-tie',
            'submenu' => [
                [
                    'nombre' => 'Dashboard Vendedor',
                    'url' => 'vendedor_dashboard.php',
                    'icono' => 'fas fa-chart-line',
                    'permiso' => 'ventas_crear'
                ],
                [
                    'nombre' => 'Aprobar Ventas',
                    'url' => 'aprobacion_ventas_vendedor.php',
                    'icono' => 'fas fa-clipboard-check',
                    'permiso' => 'ventas_ver'
                ],
                [
                    'nombre' => 'Ranking Vendedores',
                    'url' => 'ranking_vendedores.php',
                    'icono' => 'fas fa-trophy',
                    'permiso' => 'ventas_ver'
                ]
            ]
        ],
        [
            'id' => 'control_ingresos',
            'nombre' => 'Control Ingresos',
            'icono' => 'fas fa-money-check-alt',
            'submenu' => [
                [
                    'nombre' => 'Reporte Ingresos Diario',
                    'url' => 'reporte_ingresos_diario.php',
                    'icono' => 'fas fa-cash-register',
                    'permiso' => 'ventas_crear'
                ],
                [
                    'nombre' => 'Dashboard Gerencial',
                    'url' => 'gerente_dashboard.php',
                    'icono' => 'fas fa-building',
                    'permiso' => 'ventas_ver'
                ],
                [
                    'nombre' => 'Reconciliación Contabilidad',
                    'url' => 'contabilidad_reconciliacion.php',
                    'icono' => 'fas fa-calculator',
                    'permiso' => 'config_sistema'
                ]
            ]
        ],
        [
            'id' => 'reparaciones',
            'nombre' => 'Reparaciones',
            'url' => 'reparaciones.php',
            'icono' => 'fas fa-tools',
            'permiso' => 'reparaciones_ver'
        ],
        [
            'id' => 'boletas',
            'nombre' => 'Boletas',
            'url' => 'boletas.php',
            'icono' => 'fas fa-receipt',
            'permiso' => 'boletas_ver'
        ],
        [
            'id' => 'compras',
            'nombre' => 'Compras',
            'icono' => 'fas fa-file-invoice',
            'submenu' => [
                [
                    'nombre' => 'Órdenes de Compra',
                    'url' => 'compras.php',
                    'icono' => 'fas fa-file-invoice-dollar',
                    'permiso' => 'compras_ver'
                ],
                [
                    'nombre' => 'Reorden Sugerido',
                    'url' => 'reorden.php',
                    'icono' => 'fas fa-lightbulb',
                    'permiso' => 'compras_ver'
                ]
            ]
        ],
        [
            'id' => 'exportacion',
            'nombre' => 'Exportar Datos',
            'url' => 'exportar.php',
            'icono' => 'fas fa-download',
            'permiso' => 'exportacion_general'
        ],
        [
            'id' => 'logs',
            'nombre' => 'Logs del Sistema',
            'url' => 'logs.php',
            'icono' => 'fas fa-clipboard-list',
            'permiso' => 'logs_sistema'
        ],
        [
            'id' => 'manual',
            'nombre' => 'Manual de Uso',
            'icono' => 'fas fa-book-open',
            'submenu' => [
                [
                    'nombre' => 'Guía Completa',
                    'url' => 'manual.php',
                    'icono' => 'fas fa-book',
                    'permiso' => 'dashboard'
                ],
                [
                    'nombre' => 'Inicio Rápido',
                    'url' => 'manual.php?seccion=inicio-rapido',
                    'icono' => 'fas fa-rocket',
                    'permiso' => 'dashboard'
                ],
                [
                    'nombre' => 'Gestión de Productos',
                    'url' => 'manual.php?seccion=productos',
                    'icono' => 'fas fa-boxes',
                    'permiso' => 'productos_ver'
                ],
                [
                    'nombre' => 'Control de Inventarios',
                    'url' => 'manual.php?seccion=inventarios',
                    'icono' => 'fas fa-warehouse',
                    'permiso' => 'inventarios_ver'
                ],
                [
                    'nombre' => 'Punto de Venta (POS)',
                    'url' => 'manual.php?seccion=pos',
                    'icono' => 'fas fa-cash-register',
                    'permiso' => 'ventas_crear'
                ],
                [
                    'nombre' => 'Escáner QR',
                    'url' => 'manual.php?seccion=qr',
                    'icono' => 'fas fa-qrcode',
                    'permiso' => 'ventas_crear'
                ],
                [
                    'nombre' => 'Preguntas Frecuentes',
                    'url' => 'manual.php?seccion=faq',
                    'icono' => 'fas fa-question-circle',
                    'permiso' => 'dashboard'
                ]
            ]
        ],
        [
            'id' => 'administracion',
            'nombre' => 'Administración',
            'icono' => 'fas fa-cogs',
            'submenu' => [
                [
                    'nombre' => 'Usuarios',
                    'url' => 'usuarios.php',
                    'icono' => 'fas fa-users',
                    'permiso' => 'usuarios_ver'
                ],
                [
                    'nombre' => 'Roles y Permisos',
                    'url' => 'roles.php',
                    'icono' => 'fas fa-user-shield',
                    'permiso' => 'config_roles'
                ],
                [
                    'nombre' => 'Configuración',
                    'url' => 'configuracion.php',
                    'icono' => 'fas fa-sliders-h',
                    'permiso' => 'config_sistema'
                ],
                [
                    'nombre' => 'Personalización Visual',
                    'url' => 'personalizacion_visual.php',
                    'icono' => 'fas fa-palette',
                    'permiso' => 'config_sistema'
                ],
                [
                    'nombre' => 'Demo Diseño Moderno',
                    'url' => 'demo-visual.php',
                    'icono' => 'fas fa-eye',
                    'permiso' => 'config_sistema'
                ]
            ]
        ]
    ];
    
    $menu_disponible = [];
    
    foreach ($menu_items as $item) {
        $item_disponible = false;
        
        // Verificar permiso principal
        if (isset($item['permiso']) && tienePermiso($item['permiso'])) {
            $item_disponible = true;
        }
        
        // Verificar submenús
        if (isset($item['submenu'])) {
            $submenu_disponible = [];
            foreach ($item['submenu'] as $subitem) {
                if (tienePermiso($subitem['permiso'])) {
                    $submenu_disponible[] = $subitem;
                    $item_disponible = true;
                }
            }
            if (!empty($submenu_disponible)) {
                $item['submenu'] = $submenu_disponible;
            } else {
                unset($item['submenu']);
            }
        }
        
        if ($item_disponible) {
            $menu_disponible[] = $item;
        }
    }
    
    return $menu_disponible;
}

function logout() {
    // Log logout antes de destruir la sesión
    getLogger()->logout();
    session_destroy();
    header('Location: login.php');
    exit();
}

function obtenerNombreRol() {
    if (!isset($_SESSION['rol_nombre'])) {
        return isset($_SESSION['rol']) ? ucfirst($_SESSION['rol']) : 'Usuario';
    }
    return $_SESSION['rol_nombre'];
}
?>
