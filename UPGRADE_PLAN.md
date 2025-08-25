# Upgrade Plan

## Current State
- PHP app with shared includes, PWA basics, heavy table views, CSV exports, role-based modules.
- Recent UX wins: horizontal scroll on small screens, min-width per table, sticky headers, better touch targets, consistent light thead.

## UX & Visuals
- Table toolkit: column visibility toggle; density toggle (compact/comfortable); client-side search highlight; saved filters/views.
- Action bars: sticky toolbar/footer for bulk actions + pagination.
- States: friendly empty/error states; inline help; skeleton loaders during fetch.

## Tablet Experience
- Touch targets: larger switches/selects; numeric keyboards on numeric fields (`inputmode`, `pattern`).
- Optional: pin first column (ID/Producto) on wide tables where helpful.

## Performance
- Lazy-load: `loading="lazy"` on table images; defer/async non-critical scripts.
- Server-side pagination/sort unified across big lists; cap page sizes (50–100).
- DB: indices for hot columns (ventas: `fecha`, `tienda_id`; productos: `codigo`, `activo`); audit slow queries with EXPLAIN.

## Accessibility
- Tables: add `scope="col"` on `th`; `aria-sort` on sortable headers.
- Navigation: skip-to-content link; robust focus management in modals.
- Contrast/motion: ensure AA tokens; respect `prefers-reduced-motion`.

## PWA & Mobile
- SW strategies: NetworkFirst for APIs; Stale-While-Revalidate for assets.
- Background Sync: queue uploads (boletas), aprobaciones y reintentos en reconexión.
- Optional push notifications for approvals/errors.

## Data & Reporting
- Scheduled reports: daily/weekly CSV/XLSX/PDF via email (respect filters).
- Audit panel: searchable logs with filters and export.

## Security
- CSP allowlist (self + used CDNs), `SameSite=Strict` cookies, `X-Frame-Options: DENY`.
- Rate limiting and cooldown on login; optional 2FA (TOTP).
- Uploads: real MIME validation, size caps, optional antivirus (ClamAV).

## Tooling & Process
- Lint/format: PHP-CS-Fixer; static analysis (PHPStan/Psalm); Stylelint; `.editorconfig`.
- CI: GitHub Actions with lint + static analysis + Lighthouse CI (perf/a11y) on key pages.
- DB migrations: Phinx/Doctrine Migrations replacing ad‑hoc SQL.

## Observability
- Error tracking for PHP/JS (Sentry or GlitchTip); Core Web Vitals capture.
- Health checks and uptime monitoring; structured JSON logs where useful.

## Roadmap (Phases)
- Phase 1 (0–2 w): table toolkit (visibility/density), a11y on `th`, mobile-friendly inputs, lazy-load images.
- Phase 2 (3–6 w): unified server-side paginate/sort, DB indices, scheduled reports.
- Phase 3 (6–8 w): PWA Background Sync, optimized SW strategies, CI with Lighthouse + static analysis.
- Phase 4 (8–10 w): 2FA + CSP tightening, full DB migrations, audit panel.
