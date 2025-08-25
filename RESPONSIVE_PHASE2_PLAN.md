# Responsive Design — Fase 2 (Plan por vistas)

Este plan prioriza Dashboard, Productos e Inventarios. Cada ítem incluye cambios sugeridos y snippets listos para aplicar en PRs pequeños.

## 1) Dashboard (`index.php`)
- Grids de tarjetas: usar “row-cols” adaptativos.
  - Reemplazar `col-lg-6` por `row row-cols-1 row-cols-md-2 g-4` donde aplique.
  - En “Top Vendedores”: `row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3` + quitar `col-*` manuales.
- Tablas: `table-responsive-md` donde el contenido sea ancho, para evitar contenedor horizontal en desktop.
- Botones superiores: añadir `class="rs-wrap-sm"` al contenedor para que envuelvan en móvil.

Snippets:
```
<div class="row row-cols-1 row-cols-md-2 g-4"> ... </div>
<div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3"> ... </div>
<div class="d-flex gap-3 rs-wrap-sm"> ... </div>
```

## 2) Productos (`productos.php`)
- Filtros: stack en móvil con `row g-2` + `col-12 col-md-*` por campo; botones con `rs-wrap-sm`.
- Tabla: envolver con `table-responsive-md` y aplicar `text-truncate` en columnas largas (nombre/desc.).
- Acciones: `btn-group btn-group-sm rs-wrap-sm` para evitar desbordes.

Snippets:
```
<form class="row g-2 align-items-end">
  <div class="col-12 col-md-3">...</div>
  <div class="col-12 col-md-3">...</div>
  <div class="col-12 col-md-3">...</div>
  <div class="col-12 col-md-3 d-flex gap-2 rs-wrap-sm">...</div>
</form>
<div class="table-responsive-md"> ... </div>
<td class="text-truncate" style="max-width: 220px;">...</td>
```

## 3) Inventarios (`inventarios.php`)
- Filtros inline → stack en móvil (`row g-2`, `col-12 col-md-*`).
- Tabla: `table-responsive-md`; columnas numéricas con ancho mínimo (`style="min-width: 90px"`).
- Edición inline: inputs con ancho fijo (`.inline-input{min-width:90px}` ya presente; verificar en móvil).

Snippets:
```
<div class="table-responsive-md"> ... </div>
<td style="min-width: 90px;">...</td>
```

## 4) Ventas / POS (`ventas.php`, `pos.php`)
- Controles principales en stack para xs–sm; `btn` tamaño estándar en móvil, `btn-sm` solo ≥md.
- Inputs numéricos cómodos en móvil (padding vertical generoso), evitar doble scroll (contenedor único).
- Barra de acciones: usar `rs-wrap-sm` para permitir múltiple línea.

## 5) Reportes (`reportes_vendedores.php`, `historial_ventas.php`)
- Tablas grandes con `table-responsive-md`; truncado en columnas no esenciales.
- Badges y totales con `row-cols` para no quebrar layout en xs.

## 6) Formularios CRUD (tiendas, proveedores, usuarios)
- Reglas generales: `row g-3`, cada control `col-12 col-md-6` o similar; selects/inputs a 100%.
- Modales: `modal-dialog-scrollable` + evitar `modal-lg` en xs cuando tape la pantalla (clase condicional si aplica).

## 7) Utilidades Responsive incluidas
- `assets/css/modern-theme.css`:
  - `.rs-wrap-sm`: envuelve elementos en contenedores flex en <=576px.
  - `.rs-full-sm`: fuerza ancho 100% en <=576px.
  - Grupos de botones envuelven automáticamente en móvil.

## 8) Proceso de implementación
- Orden: `index.php` → `productos.php` → `inventarios.php` → `ventas.php` → resto.
- PR por vista: aplicar row-cols, table-responsive-md, stack de filtros, y utilidades.
- QA por vista (xs/sm/md):
  - Sin scroll horizontal; botones no “saltan” ni se cortan; tablas desplazables dentro del contenedor; foco accesible.

