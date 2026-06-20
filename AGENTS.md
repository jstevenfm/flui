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

---

## Plan de Acción — Flui POS

**Deadline:** Julio 2, 2026 · **Días hábiles:** ~13 · **Equipo:** 4 activos.

### Fase 0 — Arreglar lo que ya está roto *(hoy)*

Estos bugs bloquean TODO lo demás. Sin esto, cualquier feature nueva hereda los problemas.

| # | Bug | Impacto | Esfuerzo |
|---|-----|---------|----------|
| 0.1 | `login.php` redirige a `admin_dashboard.php` (404) | Admin no puede entrar | 5 min |
| 0.2 | `signup.php` form action dice `singup.php` (typo) | Nadie puede registrarse | 1 min |
| 0.3 | `login.php` link a "Registrarse" apunta a `singup.php` (typo) | Mismo bug | 5 min |
| 0.4 | Tabla `usuarios` no está en `bd.sql` | Setup roto para nuevos | 5 min |
| 0.5 | `index.html` — cambiar "Quiero pedir" / "Voy a despachar" por un solo botón "Iniciar Sesión" | Confunde al usuario | 10 min |

### Fase 1 — Base de datos completa *(hoy o mañana)*

Requerimientos de la BD final:
- **Tabla `usuarios`** con roles: `admin`, `cajero`, `cliente`. El signup solo permite `cliente`.
- **Tabla `categorias`**: `id`, `nombre`, `descripcion`.
- **Tabla `productos`**: quitar `codigo_barras`, agregar `categoria_id` (FK a `categorias`).
- **Tabla `ordenes`**: agregar `cajero_id` (FK a `usuarios`) y `codigo_qr` (VARCHAR único).
- **Tabla `orden_detalles`**: sin cambios estructurales.

### Fase 2 — Flujo de autenticación corregido *(mañana)*

| # | Feature |
|---|---------|
| 2.1 | Login redirige por rol real: `admin` → dashboard admin, `cajero` → vista cajero, `cliente` → vista de pedidos |
| 2.2 | Signup solo permite rol `cliente`. El admin NO se auto-registra |
| 2.3 | Vista admin: CRUD de cajeros (crear cuentas con email + contraseña + rol `cajero`) |

### Fase 3 — Vista del Cliente (Pedidos QR) *(prioridad #1, ~4 días)*

Core del proyecto. Lo que diferencia a Flui.

| # | Feature |
|---|---------|
| 3.1 | Catálogo de productos organizado por categorías (foto, nombre, precio, stock, buscador) |
| 3.2 | Carrito de compras (JS vanilla: agregar/quitar, subtotal, total) |
| 3.3 | Confirmar pedido: inserta `ordenes` + `orden_detalles`, genera `codigo_qr`, actualiza stock |
| 3.4 | Pantalla de confirmación: QR (imagen) + código alfanumérico de respaldo + estado "pendiente" |
| 3.5 | Historial de pedidos del cliente con estado actual |

### Fase 4 — Vista del Cajero *(prioridad #2, ~3 días)*

| # | Feature |
|---|---------|
| 4.1 | Panel de pedidos con auto-refresh cada 60s (pendientes, en preparación, listos) |
| 4.2 | Cambiar estado: `pendiente` → `en_preparacion` → `listo` → `entregado` |
| 4.3 | Escanear QR para reclamar (webcam o input manual del código) |
| 4.4 | Venta rápida en mostrador: `tipo_pedido = 'venta_rapida'`, no genera QR |
| 4.5 | Cada orden registra `cajero_id` del que la creó/despachó |

### Fase 5 — Vista del Administrador *(prioridad #3, ~3 días)*

| # | Feature |
|---|---------|
| 5.1 | Dashboard: ventas del día (total $, # órdenes), productos más vendidos hoy |
| 5.2 | CRUD de catálogo: productos y categorías (crear, editar, eliminar, con imagen) |
| 5.3 | CRUD de cajeros: crear, editar, desactivar cuentas |
| 5.4 | Reportes: ventas por día/semana/mes/año, top productos por período, ventas por cajero |
| 5.5 | Lectura de QR (mismo escáner que el cajero) |

### Fase 6 — Pulido y Demo *(últimos 2-3 días)*

| # | Tarea |
|---|-------|
| 6.1 | README con instrucciones de setup (BD, webserver, PHP) |
| 6.2 | `bd.sql` completo y funcional (todo el schema junto) |
| 6.3 | Datos semilla: 10-15 productos de cafetería de ejemplo |
| 6.4 | `.gitignore` básico |
| 6.5 | Prueba de demo: flujo completo cliente → cajero → admin |

### Orden de implementación

```
Fase 0 → Fase 1 → Fase 2 → Fase 3 → Fase 4 → Fase 5 → Fase 6
  │        │        │        │        │        │        │
  Bugs   Schema   Auth    Cliente  Cajero   Admin   Pulido
```

### Riesgos y mitigaciones

| Riesgo | Mitigación |
|--------|-----------|
| Deadline apretado (13 días, 3 vistas) | Priorizar Core sobre Nice-to-have. Si algo se cae, que sea reportes avanzados, no el flujo de pedidos |
| QR sin Composer | Usar `qrcode.js` (CDN, JS puro) en vez de librería PHP |
| Escaneo QR por webcam | Tener fallback manual (input de texto) por si la cámara falla en la demo |
| 4 personas + ramas separadas | Definir ownership claro por fase y mergear frecuentemente a `main` |
| Sin tests | Pruebas manuales son suficientes para entrega universitaria |
