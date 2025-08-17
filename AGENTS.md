# Repository Guidelines

## Project Structure & Module Organization
- Root: feature pages (e.g., `index.php`, `productos.php`, `ventas.php`).
- `includes/`: shared modules (`auth.php`, `logger.php`, `layout_header.php`, `layout_footer.php`, `csrf_protection.php`, `security_headers.php`).
- `config/database.php`: local DB connection (keep secrets out of Git).
- `assets/`: static files (`assets/css/`, `assets/js/`).
- `uploads/`: user-generated files; only writable directory.
- SQL utilities at root (e.g., `inventario_sistema.sql`, `agregar_*.sql`).

## Build, Test, and Development Commands
- Serve with XAMPP/Apache: place under `htdocs/inventario-claude` and open `http://localhost/inventario-claude`.
- Quick PHP server: `php -S 127.0.0.1:8000 -t .` (serves the repo root).
- Initialize/refresh DB: `mysql -u root -p inventario < inventario_sistema.sql`.
- Typical workflow: create feature page in root, include shared header/footer, wire to DB via `config/database.php`.

## Coding Style & Naming Conventions
- PHP: 4-space indent, PSR-12; `camelCase` for variables/functions.
- Pages: lowercase with underscores (e.g., `reparaciones_enviar.php`).
- SQL: `snake_case` tables/columns, UPPERCASE keywords, use prepared statements exclusively.
- Assets: put JS/CSS in `assets/`; reuse `includes/layout_*`; avoid inline scripts/styles.

## Testing Guidelines
- No framework; use targeted manual tests.
- Add lightweight pages prefixed `test_*.php` (e.g., `test_permisos.php`) to validate modules.
- Cover: auth/login, productos CRUD, ventas, exportación, logs. Remove test data after runs.
- Run locally with either Apache (recommended) or the PHP server above.

## Commit & Pull Request Guidelines
- Commits: imperative, concise scope. Examples: `productos: corrige cálculo de stock`, `logs: agrega detalle modal y paginación`.
- Pull Requests include: summary and rationale, linked issue, screenshots/GIFs for UI, DB changes with `.sql` scripts and rollback notes, and clear test steps (pages touched, expected outcomes).

## Security & Configuration Tips
- Do not commit real credentials; keep them local in `config/database.php`.
- Always include security helpers on new pages: `includes/auth.php`, `csrf_protection.php`, `security_headers.php`.
- Validate and escape all inputs; restrict writable paths to `uploads/` and validate file types/sizes.

