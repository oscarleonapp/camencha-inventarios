# Auditoría Responsive — Fase 1

Este documento inventaría páginas y componentes clave, fija breakpoints objetivo y criterios de aceptación para el rediseño responsive.

## Alcance y Breakpoints
- Breakpoints (Bootstrap 5):
  - xs: <576px (móvil pequeño)
  - sm: ≥576px (móvil estándar)
  - md: ≥768px (tablet vertical)
  - lg: ≥992px (tablet horizontal / laptop chica)
  - xl: ≥1200px (desktop)
- Objetivo: Sin scroll horizontal en xs–md; tap targets ≥44px; tipografía base 16px; contraste AA.

## Criterios de Aceptación
- Layout:
  - Sidebar colapsa correctamente en ≤md, overlay accesible; topbar estable (sin saltos).
  - Contenido principal sin desbordes; `container(-fluid)` + grid con `row/col` y `gap`.
- Tablas:
  - `table-responsive-{bp}` aplicado; columnas esenciales visibles; truncado (`text-truncate`) y envoltura (`text-wrap`) según caso.
  - Alternativa tipo “cards” en móvil si la tabla es muy ancha.
- Formularios:
  - Inputs 100% width en xs–sm; labels arriba; `g-*` consistente; botones accesibles (alineación y tamaños).
- Componentes:
  - Cards/stats reordenan info secundaria abajo en móvil.
  - Modales: `modal-dialog-scrollable` y tamaños adecuados; toasts con fuente ≥14px.

## Inventario de Vistas (prioridad alta → baja)
- Alta (core):
  - `index.php` (Dashboard)
  - `productos.php`, `inventarios.php`, `ventas.php`, `pos.php`
  - `reportes_vendedores.php`, `historial_ventas.php`
- Media:
  - `reparaciones.php`, `reparaciones_enviar.php`, `reparaciones_recibir.php`
  - `importar_productos.php`, `exportar.php`, `proveedores.php`, `tiendas.php`
  - `logs.php`, `logs_productos.php`
- Baja / soporte:
  - `usuarios.php`, `roles.php`, `configuracion.php`, `personalizacion_visual.php`
  - `lista_*`, `nueva_*`, `editar_*`, `detalle_venta.php`
  - Páginas test/demo (`test_*`, `demo-*`, `debug_*`)

## Componentes a Revisar
- Layout global: topbar, sidebar, `.main-content`, `container(-fluid)`, espaciados y `gap`.
- Tablas: listas grandes (productos, inventarios, logs, reportes) y headers `.thead-titulos`.
- Cards y stats: grids, iconos, badges, encabezados de card.
- Formularios: filtros, creación/edición (productos, tiendas, usuarios), selects.
- Modales, toasts y alerts: tamaños, espaciados y legibilidad.

## Matriz de Pruebas (ejemplos)
- `index.php`:
  - xs/sm: Sin scroll; “Performance por Tienda” y “Top Vendedores” con tarjetas alineadas (1 col), textos legibles.
  - md: 2 columnas; tablas en `table-responsive-md`.
- `productos.php`:
  - xs/sm: filtros en stack, tabla con desplazamiento horizontal controlado (dentro de `.table-responsive`).
  - md+: filtro en línea, paginación visible, acciones accesibles.
- `ventas.php`/`pos.php`:
  - xs/sm: controles reachables con pulgar; botones grandes; teclado no tapa inputs (móvil).

## Entregables de Fase 2 (preparación)
- Lista de issues por vista con capturas en xs/sm/md.
- Propuesta de utilidades responsive (helpers CSS si hiciera falta).
- Orden de implementación (core → media → baja) y criterios de done.
