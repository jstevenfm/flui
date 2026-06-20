# Flui — Agent Instructions

## What this is

Flui is a cloud POS (Point of Sale) system with remote ordering — a university team project.
**Stack:** plain PHP 8+, MySQL, vanilla CSS/JS. No framework, no build tools, no package manager, no tests.

Team: Hernán, Jeferson, Jeisson (jstevenfm), Oscar.
Repo: `https://github.com/jstevenfm/flui.git` — branch `main`.

## How to run

Requires PHP 8+, MySQL, and a web server (Apache/Nginx) serving from `/project/` (the URLs assume `http://localhost/project/`).

1. Create a MySQL database named `sistema_usuarios` (or override via env vars).
2. Run `bd.sql` for base tables — BUT see "Gotchas" below.
3. The `usuarios` table is NOT in `bd.sql`. It must already exist or be created manually. Expected schema (inferred from `signup.php` and `login.php`):
   ```sql
   CREATE TABLE usuarios (
       id INT AUTO_INCREMENT PRIMARY KEY,
       usuario VARCHAR(100) NOT NULL,
       email VARCHAR(255) NOT NULL UNIQUE,
       password VARCHAR(255) NOT NULL,
       rol ENUM('cliente', 'admin') NOT NULL DEFAULT 'cliente'
   );
   ```
4. `conexion.php` reads `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` from `$_ENV`. Falls back to `localhost` / `root` / empty password. No `.env` file mechanism — use web server env or edit defaults directly.
5. There is no `.gitignore`, no Docker setup, no migration system.

## File map and routing flow

```
index.html       — Landing / marketing page
login.php        — Login (session-based). POST to self.
signup.php       — Self-registration with role selector (cliente/admin).
                   CRITICAL BUG: form action="singup.php" — typo, should be "signup.php".
password.php     — Change password (requires session). Redirects non-logged-in to login.
logout.php       — Destroys session, clears cookie, redirects to login.
adm.php          — Admin dashboard (purely static HTML/CSS mockup, no PHP backend logic).
conexion.php     — PDO connection. Included by login/signup/password.
bd.sql           — Partial schema: productos, ordenes, orden_detalles. Missing usuarios table.
login.css        — Auth pages styling (dark theme, Inter font).
styles.css       — Landing page styling (Montserrat font, glassmorphism nav).
adm.css          — Admin dashboard styling (includes modal for "New Order").
```

## Known bugs / traps

- **`admin_dashboard.php` does not exist.** `login.php` line 9 redirects admin-role users to `admin_dashboard.php` (404). The actual admin UI is `adm.php` but it is NOT wired to login — it's a standalone HTML file with no session check, no PHP logic, no logout link.
- **`signup.php` form action typo:** `<form action="singup.php">` on line 81. The file is `signup.php` (with 'g'). This form will POST to a 404.
- **`bd.sql` is incomplete:** Missing the `usuarios` table that every PHP file depends on. See schema above.
- **Anyone can self-register as admin** — no approval or invite flow. This may be intentional for a study project but is worth flagging.
- **No CSRF protection** on any form.
- **No `.gitignore`** — `conexion.php` env fallbacks are checked in (not sensitive since they're defaults, but be aware).

## Conventions

- **Language:** all code, comments, variable names, and UI labels are in Spanish.
- **Commit style:** informal Spanish messages (e.g. "agregué js para darle funcionalidad a la modalidad de new order"). No conventional commits enforced.
- **Styling:** two separate CSS files with slightly different design tokens (`login.css` uses `--primary-green: #70ffbd`, `adm.css` uses `--accent: #5effc4`). Be consistent when adding features.
- **Database access:** always through `conexion.php` which provides `$pdo` (PDO instance). All queries use prepared statements.
- **No Composer, no autoloader, no namespaces.** Pure procedural PHP with `require` includes.

## If you're adding features

- Wire `adm.php` into the login session flow before adding backend logic to it.
- Fix the `signup.php` form action typo and the `admin_dashboard.php` redirect before anything else.
- Add `usuarios` table to `bd.sql` so the schema is self-contained.
- There are no tests. If adding backend logic to `adm.php`, consider whether testing infrastructure is in scope.
