# Repository Guidelines

## Project Structure & Module Organization
- Root: feature pages like `index.php`, `productos.php`, `ventas.php`.
- `includes/`: shared modules — `auth.php`, `logger.php`, `layout_header.php`, `layout_footer.php`, `csrf_protection.php`, `security_headers.php`.
- `config/database.php`: local DB connection (keep real credentials out of Git).
- `assets/`: static files (`assets/css/`, `assets/js/`).
- `uploads/`: only writable directory for user files; validate file types/sizes.
- SQL utilities in root: `inventario_sistema.sql`, `agregar_*.sql`.

## Build, Test, and Development Commands
- Dev server: `php -S 127.0.0.1:8000 -t .` (serves repo root).
- Apache/XAMPP: place under `htdocs/inventario-claude` → open `http://localhost/inventario-claude`.
- Initialize/refresh DB: `mysql -u root -p inventario < inventario_sistema.sql`.
- Typical flow: create a feature page in root, include `includes/layout_header.php`/`layout_footer.php`, require `config/database.php`, use prepared statements for queries.

## Coding Style & Naming Conventions
- PHP: PSR-12, 4-space indent; variables/functions in `camelCase`.
- Pages: lowercase with underscores (e.g., `reparaciones_enviar.php`).
- SQL: `snake_case` tables/columns, UPPERCASE keywords; prepared statements only.
- Assets: place JS/CSS in `assets/`; reuse `includes/layout_*`; avoid inline scripts/styles.

## Testing Guidelines
- Approach: targeted manual tests via lightweight pages prefixed `test_*.php` (e.g., `test_permisos.php`).
- Cover flows: auth/login, productos CRUD, ventas, exportación, logs.
- Run locally using the dev server or Apache. Clean up any test data after runs.

## Commit & Pull Request Guidelines
- Commits: imperative, concise scope. Examples: `productos: corrige cálculo de stock`, `logs: agrega detalle modal y paginación`.
- Pull Requests: summary + rationale, linked issue, screenshots/GIFs for UI, DB changes with `.sql` scripts and rollback notes, and clear test steps (pages touched, expected outcomes).

## Security & Configuration Tips
- Never commit real credentials; keep them local in `config/database.php`.
- Always include security helpers on new pages: `includes/auth.php`, `csrf_protection.php`, `security_headers.php`.
- Validate and escape all inputs; restrict writable paths to `uploads/` and validate file types/sizes.

## Upgrade Plan
- For the roadmap of visual, performance, PWA, security, and tooling improvements, see `UPGRADE_PLAN.md`.
