# Guía de Paleta de Colores

Esta guía explica cómo usar la nueva paleta basada en variables CSS (tokens) y el mapeo nativo de Bootstrap. Evita `!important`; confía en la cascada y utilidades.

## Índice
- [Tokens Principales](#tokens-principales-css-variables)
- [Uso con Bootstrap](#mapeo-con-bootstrap-ya-configurado)
- [Patrones de Uso](#patrones-de-uso)
- [Ejemplos de Componentes](#ejemplos-de-componentes)
- [Extender o Cambiar Colores](#extender-o-cambiar-colores)

## Tokens Principales (CSS Variables)
- Marca: `--primary-color` (#4f46e5), `--secondary-color` (#475569)
- Estados: `--success-color` (#059669), `--danger-color` (#e11d48), `--warning-color` (#d97706), `--info-color` (#0369a1)
- Texto: `--text-primary` (#0f172a), `--text-secondary` (#334155), `--text-muted` (#64748b)
- Superficies: `--surface` (#ffffff), `--surface-subtle` (#f8fafc), `--border-color` (#e5e7eb)

Ejemplo (CSS propio):
```
.mi-titulo { color: var(--text-primary); }
.caja { background: var(--surface); border: 1px solid var(--border-color); }
.link-accion { color: var(--primary-color); }
```

## Mapeo con Bootstrap (ya configurado)
- Variables `--bs-*` derivan de los tokens; utilidades/componentes adoptan la paleta automáticamente.
- Usa clases estándar:
  - Botones: `btn btn-primary|secondary|success|danger|warning|info`
  - Badges: `badge badge-primary|success|danger|warning|info`
  - Fondos: `bg-primary|success|danger|warning|info|light|white`
  - Texto: `text-primary|secondary|success|danger|warning|info|muted`

Notas de contraste:
- En `bg-warning` usa texto oscuro: `text-dark`.
- Encabezados de tabla: usar `.thead-titulos` (fondo claro + texto negro).

## Patrones de Uso
- Superficies: tarjetas en `var(--surface)`, áreas sutiles en `var(--surface-subtle)`.
- Bordes: siempre `var(--border-color)`.
- Enlaces: color por defecto `--primary-color` (hover más oscuro por cascada).
- Formularios/foco: foco accesible ya configurado; no uses `!important`.

## Ejemplos de Componentes
Card básica (superficies y bordes):
```
<div class="card" style="background: var(--surface); border: 1px solid var(--border-color)">
  <div class="card-header" style="background: var(--surface-subtle)">Título</div>
  <div class="card-body">
    <button class="btn btn-primary">Acción</button>
  </div>
  <div class="card-footer" style="background: var(--surface-subtle)">Pie</div>
  </div>
```

Tabla (encabezado claro recomendado):
```
<table class="table">
  <thead>
    <tr><th>Col A</th><th>Col B</th></tr>
  </thead>
  <tbody>
    <tr><td>1</td><td>2</td></tr>
  </tbody>
</table>

<table class="table">
  <thead class="thead-titulos">
    <tr><th>Col A</th><th>Col B</th></tr>
  </thead>
  <tbody>
    <tr><td>1</td><td>2</td></tr>
  </tbody>
</table>
```

Badges y advertencias:
```
<span class="badge badge-primary">Nuevo</span>
<div class="p-2 bg-warning text-dark">Atención</div>
```

## Extender o Cambiar Colores
- Edita solo los tokens en `assets/css/modern-theme.css` (`:root`).
- Para variantes (hover/active), usa los tonos existentes (`--primary-700`, etc.) o define una variable nueva.
- No dupliques hex en componentes; referencia variables.
