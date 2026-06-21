# Flui POS

Sistema de punto de venta en la nube con pedidos remotos y código QR.

## ⚡ 5-Minute Setup

1. **Crear base de datos** — `mysql -u root -e "CREATE DATABASE sistema_usuarios;"`
2. **Importar schema + datos** — `mysql -u root sistema_usuarios < bd.sql`
3. **Configurar conexión** — Editar `conexion.php` o definir variables de entorno (ver [Configuración](#configuración))
4. **Permisos de uploads** — `mkdir -p img && chmod 777 img`
5. **Servir** — Apuntar el web server a la raíz del proyecto y abrir `http://localhost/project/`

Listo. Ingresá con `admin@flui.com` / `admin123` (ver [Credenciales seed](#credenciales-seed)).

---

## Requisitos

| Requisito  | Versión mínima |
|------------|----------------|
| PHP        | 8.0+           |
| MySQL      | 5.7+           |
| Web server | Apache o Nginx |

## Configuración

`conexion.php` lee las siguientes variables de `$_ENV` con los fallback que se muestran:

| Variable  | Default           | Descripción                    |
|-----------|-------------------|--------------------------------|
| `DB_HOST` | `localhost`       | Host de MySQL                  |
| `DB_NAME` | `sistema_usuarios`| Nombre de la base de datos     |
| `DB_USER` | `root`            | Usuario de MySQL               |
| `DB_PASS` | *(vacío)*         | Contraseña de MySQL            |

> No hay archivo `.env`. Configurá las variables vía el web server (SetEnv en Apache, env en Nginx/PHP-FPM) o editá los defaults directamente en `conexion.php`.

## Base de Datos

```bash
mysql -u root -e "CREATE DATABASE sistema_usuarios CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root sistema_usuarios < bd.sql
```

`bd.sql` crea 5 tablas (`usuarios`, `categorias`, `ordenes`, `orden_detalles`, `productos`) e inserta datos semilla: 4 categorías, 12 productos de cafetería y 2 usuarios de prueba. Es idempotente — los inserts de usuarios usan `INSERT IGNORE` y se puede re-ejecutar sin errores.

### Credenciales seed

| Email            | Contraseña | Rol     |
|------------------|------------|---------|
| `admin@flui.com` | `admin123` | admin   |
| `cajero@flui.com`| `cajero123`| cajero  |

> Los clientes se registran desde `signup.php` (no hay usuarios seed con rol cliente).

## Estructura del Proyecto

| Archivo            | Descripción |
|--------------------|-------------|
| `index.html`       | Landing — botón "Iniciar Sesión" |
| `login.php`        | Login (redirige por rol: admin → `adm.php`, cajero → `cajero.php`, cliente → `cliente.php`) |
| `signup.php`       | Registro (solo rol cliente) |
| `password.php`     | Cambio de contraseña (requiere sesión) |
| `logout.php`       | Destruye sesión y redirige a login |
| `auth.php`         | Guardia de sesión y roles |
| `conexion.php`     | Conexión PDO (lee env vars) |
| `bd.sql`           | Schema completo + datos semilla |
| `cliente.php`      | Catálogo, carrito y pedidos (cliente) |
| `crear_orden.php`  | API: crea orden, genera QR, descuenta stock |
| `confirmacion.php` | Confirmación de pedido (QR + código + estado) |
| `historial.php`    | Historial de pedidos del cliente |
| `estado_orden.php` | API: consulta estado de una orden |
| `cajero.php`       | Dashboard del cajero (pedidos, venta rápida, escaneo QR) |
| `cajero_api.php`   | API del cajero (6 acciones) |
| `cajero.css`       | Estilos del dashboard cajero |
| `adm.php`          | Dashboard del admin (6 tabs) |
| `admin_api.php`    | API del admin (18 acciones) |
| `adm.css`          | Estilos del dashboard admin |
| `qr-scanner.js`    | Módulo compartido de escáner QR (html5-qrcode) |
| `login.css`        | Estilos de autenticación (dark theme, Inter) |
| `styles.css`       | Estilos del landing (Montserrat, glassmorphism) |
| `img/.htaccess`    | Bloquea ejecución PHP en uploads |

## Dependencias CDN

El proyecto carga librerías externas por CDN. Se necesita conexión a internet.

| Librería        | Versión | CDN | Usada por |
|-----------------|---------|-----|-----------|
| Font Awesome    | 6.4.0   | `cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/all.min.css` | Todas las vistas |
| Font Awesome    | 6.0.0   | `cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/all.min.css` | `login.php`, `signup.php`, `password.php` |
| Google Fonts Inter | — | `fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700` | Todas las vistas |
| Google Fonts Montserrat | — | `fonts.googleapis.com/css2?family=Montserrat` | `index.html` |
| html5-qrcode    | 2.3.8   | `cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js` | `adm.php` |
| html5-qrcode    | latest  | `unpkg.com/html5-qrcode` | `cajero.php` |
| qrcodejs        | 1.0.0   | `cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js` | `confirmacion.php` |

## Demo Walkthrough

Para probar el flujo completo del sistema, ver [`docs/demo.md`](docs/demo.md).

## Solución de Problemas

| Problema | Solución |
|----------|----------|
| "Error crítico de conexión a la base de datos" | Verificá que MySQL esté corriendo y las credenciales en `conexion.php` sean correctas |
| La cámara no funciona para escanear QR | Usá el input manual del código alfanumérico como alternativa |
| Imágenes de productos no se muestran | Verificá que `img/` exista y tenga permisos de escritura (`chmod 777 img`) |
| La base de datos se llama distinto | Poné `DB_NAME` como variable de entorno o editá el default en `conexion.php` |

## Limitaciones Conocidas

- **Sin protección CSRF** — Los formularios no tienen token CSRF. Aceptable para proyecto universitario, NO para producción.
- **Sin cancelar órdenes** — El ENUM `estado` incluye `cancelada` pero no hay feature de cancelación aún.
- **Sin índice en `fecha_creacion`** — Los reportes hacen table scan sobre `ordenes`. Aceptable para datos de demo.
- **Sin tests automatizados** — Solo pruebas manuales.
- **Dependencias CDN en runtime** — Sin internet, los íconos, fuentes y escáner QR no cargan.