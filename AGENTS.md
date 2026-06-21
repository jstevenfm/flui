# Flui — Agent Instructions

## What this is

Flui is a cloud POS (Point of Sale) system with remote ordering — a university team project.
**Stack:** plain PHP 8+, MySQL, vanilla CSS/JS. No framework, no build tools, no package manager, no tests.

Team: Hernán, Jeferson, Jeisson (jstevenfm), Oscar.
Repo: `https://github.com/jstevenfm/flui.git` — branch `main`.

## How to run

Requires PHP 8+, MySQL, and a web server (Apache/Nginx) serving from `/project/` (the URLs assume `http://localhost/project/`).

1. Create a MySQL database named `sistema_usuarios` (or override via env vars).
2. Run `bd.sql` for schema completo (5 tablas: usuarios, categorias, productos, ordenes, orden_detalles + datos semilla de cafetería).
3. `conexion.php` reads `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` from `$_ENV`. Falls back to `localhost` / `root` / empty password. No `.env` file mechanism — use web server env or edit defaults directly.
4. Crear directorio `img/` con permisos de escritura para uploads de productos (el `.htaccess` ya está incluido).
5. El `.gitignore` excluye `img/*` (uploads) pero conserva `img/.htaccess`.

## File map and routing flow

```
index.html        — Landing con botón "Iniciar Sesión"
login.php         — Login (session-based). Redirige por rol: admin→adm.php, cajero→cajero.php, cliente→cliente.php.
signup.php        — Registro (solo rol cliente). Form valida email, contraseña ≥6, email único.
password.php      — Cambio de contraseña (requiere sesión, cualquier rol).
logout.php        — Destruye sesión, limpia cookie, redirige a login.
auth.php          — Guardia de sesión: checkRole('admin'|'cajero'|'cliente'|[...]).
conexion.php      — Conexión PDO. Requerido por todas las páginas que tocan BD.
bd.sql            — Schema completo (5 tablas) + datos semilla (4 categorías, 12 productos, 2 usuarios seed).
admin_api.php     — JSON API del admin: 18 acciones (dashboard, CRUD cajeros/categorías/productos, reportes, QR).
adm.php           — Dashboard admin: 6 tabs (Dashboard, Cajeros, Categorías, Productos, Reportes, Escanear QR).
adm.css           — Estilos del dashboard admin (tabs, modales, tablas, reportes, scanner).
cajero_api.php    — JSON API del cajero: 6 acciones (listar pedidos, cambiar estado, venta rápida, QR).
cajero.php        — Dashboard cajero: 3 tabs (Pedidos, Nueva Venta, Escanear QR).
cajero.css        — Estilos del dashboard cajero.
cliente.php       — Vista cliente: catálogo por categorías, carrito, confirmar pedido.
crear_orden.php   — API: crea orden desde el carrito del cliente (genera QR, descuenta stock).
confirmacion.php  — Pantalla de confirmación post-pedido (QR + código + estado).
historial.php     — Historial de pedidos del cliente.
estado_orden.php  — API: consulta estado de una orden.
qr-scanner.js     — Módulo compartido de escáner QR (html5-qrcode). Usado por cajero.php y adm.php.
login.css         — Estilos de auth (dark theme, Inter font).
styles.css        — Estilos del landing (Montserrat, glassmorphism).
img/.htaccess     — Protege el directorio de uploads (bloquea ejecución PHP).
```

## Known limitations (no bloquean)

- **No CSRF protection** en ningún formulario — limitación heredada del codebase original. Aceptable para proyecto universitario.
- **`ordenes.estado` incluye `'cancelada'`** en el ENUM pero no existe feature de cancelación aún (reservado para futuro).
- **`fecha_creacion` sin índice** en `ordenes` — los reportes hacen table scan. Aceptable para el dataset de demo.
- **Cualquiera puede self-registrarse** (solo como cliente desde Fase 2). Esto puede ser intencional para un proyecto de estudio.

## Conventions

- **Language:** all code, comments, variable names, and UI labels are in Spanish.
- **Commit style:** conventional commits en español: `type(scope): descripción`.
- **Styling:** canonical `--accent: #70ffbd` unificado en todos los CSS (adm.css, cajero.css, cliente.css, login.css).
- **Database access:** always through `conexion.php` which provides `$pdo` (PDO instance). All queries use prepared statements.
- **No Composer, no autoloader, no namespaces.** Pure procedural PHP with `require __DIR__ . '/...'` includes.
- **API pattern:** JSON API con action router (`$_GET['action']`), auth gate 401/403 sin redirect, errores amigables sin leak de detalles internos.

## Si estás agregando features

- Usá `require_once __DIR__ . '/auth.php'; checkRole('...');` para páginas protegidas.
- Usá `require_once __DIR__ . '/conexion.php';` para acceso a BD.
- APIs JSON: auth manual con 401/403 + `json_encode`, nunca `header('Location')`.
- Imágenes: validar MIME con `finfo_file` + `getimagesize()`, máximo 2MB, `move_uploaded_file` a `img/`.
- No hay tests automatizados. Las pruebas son manuales.

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
