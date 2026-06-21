# Flui — Guía de Demo

Checklist para probar el flujo completo del sistema cliente → cajero → admin.

## Setup

- ☐ Importar `bd.sql` en MySQL: `mysql -u root sistema_usuarios < bd.sql`
- ☐ Verificar que las tablas se crearon: `mysql -u root sistema_usuarios -e "SHOW TABLES;"`
- ☐ Ingresar como admin: `admin@flui.com` / `admin123` → redirige a `adm.php`
- ☐ Ingresar como cajero: `cajero@flui.com` / `cajero123` → redirige a `cajero.php`
- ☐ Cerrar sesión y registrar un cliente nuevo desde `signup.php` → redirige a `cliente.php`
- ☐ Verificar que el catálogo muestra productos organizados por categoría

---

## Flujo Cliente

### Registro e ingreso

- ☐ Desde la landing, hacer clic en "Iniciar Sesión"
- ☐ En login, ir a "Registrarse" y crear una cuenta cliente (cualquier email válido, contraseña ≥6 caracteres)
- ☐ Expected: registro exitoso, redirige a `cliente.php`

### Catálogo y carrito

- ☐ Navegar categorías en el catálogo (Bebidas Calientes, Bebidas Frías, Postres, Snacks)
- ☐ Agregar 2+ productos al carrito
- ☐ Verificar que el carrito muestra items con cantidad, precio unitario y subtotal
- ☐ Cambiar cantidades y eliminar un item
- ☐ Expected: el total se recalcula correctamente

### Pedido del cliente

- ☐ Hacer clic en "Confirmar pedido"
- ☐ Expected: pantalla de confirmación con:
  - Código QR generado (imagen escaneable)
  - Código alfanumérico de respaldo (ej: `ORD-ABC123`)
  - Estado: "pendiente"
  - Resumen del pedido con productos y total

### Historial

- ☐ Ir a "Historial de pedidos"
- ☐ Expected: lista de órdenes con número, fecha, estado y total
- ☐ Hacer clic en "Ver QR y confirmación" en una orden
- ☐ Expected: pantalla de confirmación con QR y código

---

## Flujo Cajero

### Panel de pedidos

- ☐ Ingresar como cajero: `cajero@flui.com` / `cajero123`
- ☐ Expected: tab "Pedidos" muestra órdenes pendientes con auto-refresh cada 60s
- ☐ Si existe una orden remota del cliente, aparece en la lista

### Cambio de estado

- ☐ Seleccionar una orden pendiente → cambiar estado a "En preparación"
- ☐ Expected: la orden cambia de sección visualmente
- ☐ Cambiar estado a "Listo"
- ☐ Expected: la orden aparece en la sección de listos
- ☐ Cambiar estado a "Entregado"
- ☐ Expected: la orden desaparece del panel activo

### Escaneo QR (cámara)

- ☐ Ir al tab "Escanear QR"
- ☐ Hacer clic en "Escanear QR" para activar la cámara
- ☐ Apuntar la cámara al QR generado en la confirmación del cliente
- ☐ Expected: el código se lee y la orden se marca como reclamada/entregada

> **Fallback si no hay cámara:** Usar el input manual. Ingresar el código alfanumérico mostrado en la confirmación del cliente (ej: `ORD-ABC123`). Expected: mismo resultado que escaneo por cámara.

### Venta rápida

- ☐ Ir al tab "Nueva Venta"
- ☐ Buscar productos y agregar al carrito de venta rápida
- ☐ Confirmar la venta
- ☐ Expected: se crea una orden `tipo_pedido = 'venta_rapida'`, sin QR, registrando `cajero_id`

---

## Flujo Admin

### Dashboard

- ☐ Ingresar como admin: `admin@flui.com` / `admin123`
- ☐ Expected: dashboard muestra métricas del día (total ventas, número de órdenes, productos más vendidos)

### CRUD Categorías

- ☐ Ir al tab "Categorías"
- ☐ Crear una categoría nueva (ej: "Bebidas Especiales")
- ☐ Editar la categoría creada
- ☐ Eliminar la categoría
- ☐ Expected: cada operación CRUD se refleja inmediatamente

### CRUD Productos

- ☐ Ir al tab "Productos"
- ☐ Crear un producto nuevo con imagen (o sin imagen)
- ☐ Editar el producto (cambiar precio, stock)
- ☐ Eliminar el producto
- ☐ Expected: cada operación CRUD se refleja en el catálogo del cliente

### CRUD Cajeros

- ☐ Ir al tab "Cajeros"
- ☐ Crear un nuevo cajero (email + contraseña)
- ☐ Desactivar un cajero (cambiar `activo` a FALSE)
- ☐ Expected: el cajero desactivado no puede iniciar sesión

### Reportes

- ☐ Ir al tab "Reportes"
- ☐ Seleccionar período (día, semana, mes, año)
- ☐ Expected: muestra ventas totales, top productos y ventas por cajero para el período seleccionado

### Escaneo QR

- ☐ Ir al tab "Escanear QR"
- ☐ Usar la cámara o input manual para escanear un código de orden
- ☐ Expected: muestra detalle de la orden y permite avanzar el estado