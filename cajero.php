<?php
// cajero.php — Panel del cajero: dashboard de pedidos, venta rápida, escaneo QR
require_once __DIR__ . '/auth.php';
checkRole('cajero');
$nombre = $_SESSION['usuario_nombre'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flui POS - Cajero</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="cajero.css">
</head>
<body>

    <!-- Header -->
    <header class="cajero-header">
        <div class="cajero-brand">
            <span class="brand-icon"><i class="fa-solid fa-cash-register"></i></span>
            <h1>Flui</h1>
        </div>
        <div class="cajero-user">
            <span>Hola, <strong><?php echo htmlspecialchars($nombre); ?></strong></span>
            <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Cerrar sesión</a>
        </div>
    </header>

    <!-- Tab bar -->
    <nav class="cajero-tabs">
        <button class="cajero-tab active" data-tab="pedidos">
            <i class="fa-solid fa-clipboard-list"></i> Pedidos
        </button>
        <button class="cajero-tab" data-tab="nueva-venta">
            <i class="fa-solid fa-cart-plus"></i> Nueva Venta
        </button>
        <button class="cajero-tab" data-tab="escanear">
            <i class="fa-solid fa-qrcode"></i> Escanear QR
        </button>
    </nav>

    <!-- Pane: Pedidos -->
    <div class="cajero-pane active" id="pane-pedidos">
        <div class="pedidos-header">
            <h2><i class="fa-solid fa-clipboard-list"></i> Pedidos Activos</h2>
            <button class="btn-refresh" id="btn-refresh" onclick="cargarPedidos()">
                <i class="fa-solid fa-arrows-rotate"></i> Actualizar
            </button>
        </div>

        <div class="pedidos-error hidden" id="pedidos-error">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span id="pedidos-error-text"></span>
        </div>

        <div class="pedidos-columns" id="pedidos-columns">
            <!-- Pendiente -->
            <div class="status-column col-pendiente">
                <div class="status-column-header">
                    <h3><i class="fa-solid fa-clock"></i> Pendientes</h3>
                    <span class="count" id="count-pendiente">0</span>
                </div>
                <div class="status-list" id="col-pendiente">
                    <div class="empty-state">
                        <i class="fa-solid fa-inbox"></i>
                        <p>Sin pedidos pendientes</p>
                    </div>
                </div>
            </div>

            <!-- En Preparación -->
            <div class="status-column col-en_preparacion">
                <div class="status-column-header">
                    <h3><i class="fa-solid fa-fire-burner"></i> En Preparación</h3>
                    <span class="count" id="count-en_preparacion">0</span>
                </div>
                <div class="status-list" id="col-en_preparacion">
                    <div class="empty-state">
                        <i class="fa-solid fa-inbox"></i>
                        <p>Sin pedidos en preparación</p>
                    </div>
                </div>
            </div>

            <!-- Listos -->
            <div class="status-column col-listo">
                <div class="status-column-header">
                    <h3><i class="fa-solid fa-circle-check"></i> Listos</h3>
                    <span class="count" id="count-listo">0</span>
                </div>
                <div class="status-list" id="col-listo">
                    <div class="empty-state">
                        <i class="fa-solid fa-inbox"></i>
                        <p>Sin pedidos listos</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pane: Nueva Venta -->
    <div class="cajero-pane" id="pane-nueva-venta">
        <div class="venta-layout">
            <!-- Catálogo -->
            <div class="venta-catalogo">
                <div class="venta-header">
                    <h2><i class="fa-solid fa-cart-plus"></i> Nueva Venta</h2>
                </div>

                <div class="venta-busqueda">
                    <div class="search-box">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" id="venta-busqueda" placeholder="Buscar producto...">
                    </div>
                </div>

                <div class="venta-categorias" id="venta-categorias">
                    <!-- Se llena dinámicamente desde la API -->
                </div>

                <div class="venta-productos" id="venta-productos">
                    <div class="empty-state">
                        <i class="fa-solid fa-spinner fa-spin"></i>
                        <p>Cargando productos...</p>
                    </div>
                </div>
            </div>

            <!-- Carrito -->
            <div class="venta-carrito">
                <div class="carrito-header">
                    <h3><i class="fa-solid fa-receipt"></i> Carrito</h3>
                    <button class="btn-carrito-vaciar" id="btn-vaciar-venta" title="Vaciar carrito">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
                <div class="carrito-items" id="carrito-items">
                    <div class="carrito-vacio">
                        <i class="fa-solid fa-basket-shopping"></i>
                        <p>Agrega productos al carrito</p>
                    </div>
                </div>
                <div class="carrito-footer">
                    <div class="carrito-mensaje" id="carrito-mensaje"></div>
                    <div class="carrito-total">
                        <span>Total</span>
                        <span id="carrito-total">$0</span>
                    </div>
                    <button class="btn-completar-venta" id="btn-completar-venta" disabled>
                        <i class="fa-solid fa-cash-register"></i> Completar Venta
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Pane: Escanear QR -->
    <div class="cajero-pane" id="pane-escanear">
        <div class="escanear-header">
            <h2><i class="fa-solid fa-qrcode"></i> Escanear QR</h2>
            <p class="escanear-subtitle">Escanea el código del cliente para buscar un pedido listo</p>
        </div>

        <!-- Mensajes de estado (éxito/error) -->
        <div class="escanear-mensaje hidden" id="escanear-mensaje"></div>

        <!-- Sección del escáner webcam -->
        <div class="escanear-scanner-container" id="escaner-container">
            <div class="escanear-status" id="escaner-status">
                <i class="fa-solid fa-camera"></i> <span>Escáner detenido</span>
            </div>
            <div id="qr-reader" class="qr-reader"></div>
            <div class="escaner-controls">
                <button class="btn-escanear" id="btn-iniciar-escaner" type="button">
                    <i class="fa-solid fa-camera"></i> Iniciar Escáner
                </button>
                <button class="btn-escanear hidden" id="btn-detener-escaner" type="button">
                    <i class="fa-solid fa-stop"></i> Detener
                </button>
            </div>
            <!-- Error de permisos: el fallback manual siempre está visible debajo -->
            <div class="escanear-camara-error hidden" id="camara-error">
                <i class="fa-solid fa-camera-slash"></i>
                <p>No se pudo acceder a la cámara. Usa el ingreso manual debajo.</p>
            </div>
        </div>

        <!-- Ingreso manual (siempre visible — accesibilidad) -->
        <div class="escanear-manual">
            <h3><i class="fa-solid fa-keyboard"></i> Ingreso manual</h3>
            <div class="escanear-manual-input">
                <input type="text" id="codigo-manual" placeholder="Ej: FLUI-ABC123" autocomplete="off" />
                <button class="btn-manual" id="btn-buscar-manual" type="button">
                    <i class="fa-solid fa-magnifying-glass"></i> Buscar
                </button>
            </div>
        </div>

        <!-- Panel de verificación (oculto hasta encontrar la orden) -->
        <div class="escanear-verificacion hidden" id="verificacion-panel">
            <div class="verificacion-card">
                <div class="verificacion-header">
                    <h3><i class="fa-solid fa-receipt"></i> Verificar Pedido</h3>
                    <button class="btn-verificacion-cerrar" id="btn-cancelar-verificacion" type="button" title="Cancelar">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <div class="verificacion-body" id="verificacion-body">
                    <!-- Se llena dinámicamente tras buscar_qr -->
                </div>
                <div class="verificacion-actions">
                    <button class="btn-reclamar" id="btn-reclamar" type="button">
                        <i class="fa-solid fa-hand-holding-heart"></i> Reclamar Pedido
                    </button>
                </div>
            </div>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
// === Estado global ===
let pedidosData = [];
let refreshTimer = null;

// === Inicializar ===
cargarPedidos();
// Auto-refresh cada 60 segundos
refreshTimer = setInterval(cargarPedidos, 60000);

// === Cargar pedidos desde la API ===
async function cargarPedidos() {
    const btn = document.getElementById('btn-refresh');
    const errorBox = document.getElementById('pedidos-error');
    const errorText = document.getElementById('pedidos-error-text');

    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-arrows-rotate fa-spin"></i> Actualizando...';
    errorBox.classList.add('hidden');

    try {
        const resp = await fetch('cajero_api.php?action=listar');
        const data = await resp.json();

        if (!data.success) {
            if (resp.status === 401) {
                window.location.href = 'login.php';
                return;
            }
            throw new Error(data.error || 'Error al cargar pedidos.');
        }

        pedidosData = data.ordenes;
        renderPedidos();
    } catch (e) {
        errorText.textContent = e.message || 'Error de conexión.';
        errorBox.classList.remove('hidden');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-arrows-rotate"></i> Actualizar';
    }
}

// === Renderizar pedidos en columnas ===
function renderPedidos() {
    const estados = ['pendiente', 'en_preparacion', 'listo'];
    const counts = { pendiente: 0, en_preparacion: 0, listo: 0 };

    // Agrupar por estado
    const grupos = { pendiente: [], en_preparacion: [], listo: [] };
    pedidosData.forEach(p => {
        if (grupos[p.estado]) {
            grupos[p.estado].push(p);
            counts[p.estado]++;
        }
    });

    estados.forEach(estado => {
        const col = document.getElementById('col-' + estado);
        const countEl = document.getElementById('count-' + estado);
        countEl.textContent = counts[estado];

        if (grupos[estado].length === 0) {
            col.innerHTML = `
                <div class="empty-state">
                    <i class="fa-solid fa-inbox"></i>
                    <p>Sin pedidos ${estado === 'pendiente' ? 'pendientes' : estado === 'en_preparacion' ? 'en preparación' : 'listos'}</p>
                </div>`;
        } else {
            col.innerHTML = grupos[estado].map(p => renderOrdenCard(p)).join('');
        }
    });
}

// === Renderizar una tarjeta de orden ===
function renderOrdenCard(p) {
    const itemsText = p.items && p.items.length > 0
        ? p.items.map(i => `<span>${escapeHtml(i.producto_nombre)}</span> x${i.cantidad}`).join(', ')
        : 'Sin detalles';

    const minutos = p.minutos !== undefined ? p.minutos : calcularMinutos(p.fecha_creacion);
    const tiempoStr = minutos < 60 ? `Hace ${minutos} min` : `Hace ${Math.floor(minutos/60)}h ${minutos%60}m`;

    const estadoLabel = {
        pendiente: 'Pendiente',
        en_preparacion: 'En preparación',
        listo: 'Listo',
        entregado: 'Entregado'
    };

    // Botón de transición según estado
    let btnHtml = '';
    if (p.estado === 'pendiente') {
        btnHtml = `<button class="btn-estado btn-preparar" onclick="cambiarEstado(${p.id}, '${p.estado}', 'en_preparacion', this)">
            <i class="fa-solid fa-fire-burner"></i> Preparar</button>`;
    } else if (p.estado === 'en_preparacion') {
        btnHtml = `<button class="btn-estado btn-listo" onclick="cambiarEstado(${p.id}, '${p.estado}', 'listo', this)">
            <i class="fa-solid fa-check"></i> Listo</button>`;
    } else if (p.estado === 'listo') {
        btnHtml = `<button class="btn-estado btn-entregar" onclick="cambiarEstado(${p.id}, '${p.estado}', 'entregado', this)">
            <i class="fa-solid fa-hand-holding-heart"></i> Entregar</button>`;
    }

    return `
    <div class="orden-card" id="orden-${p.id}">
        <div class="orden-card-top">
            <span class="orden-card-id">#${p.id}</span>
            <span class="orden-card-time"><i class="fa-regular fa-clock"></i> ${tiempoStr}</span>
        </div>
        <div class="orden-card-client">${escapeHtml(p.cliente_nombre || 'Venta rápida')}</div>
        <div class="orden-card-items">${itemsText}</div>
        <div class="orden-card-bottom">
            <span class="orden-card-total">$${formatoPrecio(p.total)}</span>
            <span class="estado-badge estado-${p.estado}">${estadoLabel[p.estado] || p.estado}</span>
        </div>
        ${btnHtml ? `<div style="margin-top:10px; text-align:right;">${btnHtml}</div>` : ''}
    </div>`;
}

// === Cambiar estado de una orden ===
async function cambiarEstado(ordenId, estadoActual, estadoNuevo, btnEl) {
    btnEl.disabled = true;
    const originalHtml = btnEl.innerHTML;
    btnEl.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Procesando...';

    try {
        const resp = await fetch('cajero_api.php?action=cambiar_estado', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ orden_id: ordenId, estado_nuevo: estadoNuevo })
        });

        const data = await resp.json();

        if (data.success) {
            // Animar la tarjeta y recargar
            const card = document.getElementById('orden-' + ordenId);
            if (card) {
                card.classList.add('fade-out');
                setTimeout(() => cargarPedidos(), 400);
            } else {
                cargarPedidos();
            }
        } else {
            // Re-habilitar botón
            btnEl.disabled = false;
            btnEl.innerHTML = originalHtml;

            if (resp.status === 409) {
                mostrarError('El pedido ya fue actualizado por otro cajero. Actualizando lista...');
                setTimeout(() => cargarPedidos(), 1500);
            } else if (resp.status === 401) {
                window.location.href = 'login.php';
            } else {
                mostrarError(data.error || 'Error al cambiar el estado.');
            }
        }
    } catch (e) {
        btnEl.disabled = false;
        btnEl.innerHTML = originalHtml;
        mostrarError('Error de conexión. Intenta de nuevo.');
    }
}

// === Mostrar error en la UI ===
function mostrarError(msg) {
    const errorBox = document.getElementById('pedidos-error');
    const errorText = document.getElementById('pedidos-error-text');
    errorText.textContent = msg;
    errorBox.classList.remove('hidden');
    setTimeout(() => errorBox.classList.add('hidden'), 5000);
}

// === Utilidades ===
function formatoPrecio(n) {
    return Number(n).toLocaleString('es-CO');
}

function escapeHtml(s) {
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
}

function calcularMinutos(fechaCreacion) {
    const creado = new Date(fechaCreacion + (fechaCreacion.indexOf('Z') === -1 ? 'Z' : ''));
    const diff = Math.floor((Date.now() - creado.getTime()) / 60000);
    return Math.max(0, diff);
}

// === Tab switching ===
document.querySelectorAll('.cajero-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        if (this.disabled) return;
        document.querySelectorAll('.cajero-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.cajero-pane').forEach(p => p.classList.remove('active'));
        this.classList.add('active');
        const paneId = 'pane-' + this.dataset.tab;
        document.getElementById(paneId).classList.add('active');

        // Cargar productos al abrir la pestaña de Nueva Venta
        if (this.dataset.tab === 'nueva-venta' && !productosCargados) {
            cargarProductos();
        }
    });
});

// ═══════════════════════════════════════════════════════
// NUEVA VENTA — Catálogo y carrito
// ═══════════════════════════════════════════════════════

let productosCargados = false;
let productosData = [];
let categoriasData = [];
let carritoVenta = []; // [{producto_id, nombre, precio, cantidad, stock}]

// === Cargar productos desde la API ===
async function cargarProductos() {
    const cont = document.getElementById('venta-productos');
    try {
        const resp = await fetch('cajero_api.php?action=listar_productos');
        const data = await resp.json();
        if (!data.success) {
            if (resp.status === 401) { window.location.href = 'login.php'; return; }
            throw new Error(data.error || 'Error al cargar productos.');
        }
        productosData = data.productos;
        categoriasData = data.categorias;
        renderCategorias();
        renderProductos('todas');
        productosCargados = true;
    } catch (e) {
        cont.innerHTML = `<div class="empty-state"><i class="fa-solid fa-triangle-exclamation"></i><p>${escapeHtml(e.message)}</p></div>`;
    }
}

// === Renderizar tabs de categoría ===
function renderCategorias() {
    const cont = document.getElementById('venta-categorias');
    let html = '<button class="venta-cat-btn active" data-cat="todas">Todas</button>';
    categoriasData.forEach(cat => {
        html += `<button class="venta-cat-btn" data-cat="${cat.id}">${escapeHtml(cat.nombre)}</button>`;
    });
    cont.innerHTML = html;

    cont.addEventListener('click', e => {
        if (e.target.tagName !== 'BUTTON') return;
        cont.querySelectorAll('.venta-cat-btn').forEach(b => b.classList.remove('active'));
        e.target.classList.add('active');
        filtrarProductos();
    });
}

// === Renderizar tarjetas de productos ===
function renderProductos(categoria) {
    const cont = document.getElementById('venta-productos');
    const busqueda = document.getElementById('venta-busqueda').value.trim().toLowerCase();

    let filtrados = productosData;
    if (categoria !== 'todas') {
        filtrados = filtrados.filter(p => String(p.categoria_id) === String(categoria));
    }
    if (busqueda) {
        filtrados = filtrados.filter(p => p.nombre.toLowerCase().includes(busqueda));
    }

    if (filtrados.length === 0) {
        cont.innerHTML = '<div class="empty-state"><i class="fa-solid fa-magnifying-glass"></i><p>Sin resultados</p></div>';
        return;
    }

    cont.innerHTML = filtrados.map(p => {
        const stock = parseInt(p.stock);
        const agotado = stock === 0;
        const stockClase = stock > 10 ? 'stock-disponible' : stock > 0 ? 'stock-bajo' : 'stock-agotado';
        const stockTexto = stock > 10 ? 'Disponible' : stock > 0 ? 'Pocas unidades' : 'Agotado';
        const tieneImagen = p.imagen && p.imagen !== null;
        return `
        <div class="venta-producto-card" data-id="${p.id}">
            <div class="venta-producto-imagen">
                ${tieneImagen
                    ? `<img src="img/${escapeHtml(p.imagen)}" alt="${escapeHtml(p.nombre)}" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                       <span class="venta-img-placeholder" style="display:none;"><i class="fa-solid fa-mug-saucer"></i></span>`
                    : `<span class="venta-img-placeholder"><i class="fa-solid fa-mug-saucer"></i></span>`
                }
            </div>
            <div class="venta-producto-info">
                <div class="venta-producto-nombre">${escapeHtml(p.nombre)}</div>
                <div class="venta-producto-precio">$${formatoPrecio(p.precio)}</div>
                <span class="producto-stock ${stockClase}">${stockTexto}</span>
                <button class="btn-agregar-venta" data-id="${p.id}" data-nombre="${escapeHtml(p.nombre)}" data-precio="${p.precio}" data-stock="${stock}" ${agotado ? 'disabled' : ''}>
                    ${agotado ? '<i class="fa-solid fa-ban"></i> Agotado' : '<i class="fa-solid fa-plus"></i> Agregar'}
                </button>
            </div>
        </div>`;
    }).join('');
}

// === Filtrar productos por categoría y búsqueda ===
function filtrarProductos() {
    const catBtn = document.querySelector('#venta-categorias .venta-cat-btn.active');
    const cat = catBtn ? catBtn.dataset.cat : 'todas';
    renderProductos(cat);
}

// === Carrito de venta rápida ===
function agregarAlCarrito(id, nombre, precio, stock) {
    const existente = carritoVenta.find(i => i.producto_id === id);
    if (existente) {
        if (existente.cantidad >= stock) return; // no más que el stock
        existente.cantidad += 1;
    } else {
        if (stock === 0) return;
        carritoVenta.push({ producto_id: id, nombre, precio: parseFloat(precio), cantidad: 1, stock: parseInt(stock) });
    }
    renderCarrito();
}

function quitarDelCarrito(index) {
    carritoVenta.splice(index, 1);
    renderCarrito();
}

function cambiarCantidadCarrito(index, delta) {
    const item = carritoVenta[index];
    item.cantidad += delta;
    if (item.cantidad <= 0) {
        quitarDelCarrito(index);
        return;
    }
    if (item.cantidad > item.stock) item.cantidad = item.stock;
    renderCarrito();
}

function vaciarCarritoVenta() {
    carritoVenta = [];
    renderCarrito();
}

function renderCarrito() {
    const cont = document.getElementById('carrito-items');
    const totalEl = document.getElementById('carrito-total');
    const btnVenta = document.getElementById('btn-completar-venta');
    const msj = document.getElementById('carrito-mensaje');

    // Limpiar mensajes previos
    msj.className = 'carrito-mensaje';
    msj.textContent = '';

    let total = 0;

    if (carritoVenta.length === 0) {
        cont.innerHTML = '<div class="carrito-vacio"><i class="fa-solid fa-basket-shopping"></i><p>Agrega productos al carrito</p></div>';
        btnVenta.disabled = true;
    } else {
        btnVenta.disabled = false;
        cont.innerHTML = carritoVenta.map((item, idx) => {
            const subtotal = item.precio * item.cantidad;
            total += subtotal;
            return `
            <div class="carrito-item">
                <div class="carrito-item-top">
                    <span class="carrito-item-nombre">${escapeHtml(item.nombre)}</span>
                    <span class="carrito-item-precio">$${formatoPrecio(item.precio)}</span>
                </div>
                <div class="carrito-item-controls">
                    <button class="qty-btn" onclick="cambiarCantidadCarrito(${idx}, -1)">&minus;</button>
                    <span class="carrito-item-qty">${item.cantidad}</span>
                    <button class="qty-btn" onclick="cambiarCantidadCarrito(${idx}, 1)">+</button>
                    <span class="carrito-item-subtotal">$${formatoPrecio(subtotal)}</span>
                    <button class="carrito-item-remove" onclick="quitarDelCarrito(${idx})" title="Quitar"><i class="fa-solid fa-xmark"></i></button>
                </div>
            </div>`;
        }).join('');
    }

    totalEl.textContent = '$' + formatoPrecio(total);
}

// === Completar venta ===
async function completarVenta() {
    const msj = document.getElementById('carrito-mensaje');
    const btn = document.getElementById('btn-completar-venta');

    if (carritoVenta.length === 0) {
        msj.className = 'carrito-mensaje error';
        msj.textContent = 'El carrito está vacío.';
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Procesando...';
    msj.className = 'carrito-mensaje';
    msj.textContent = '';

    try {
        const items = carritoVenta.map(i => ({
            producto_id: i.producto_id,
            cantidad: i.cantidad
        }));

        const resp = await fetch('cajero_api.php?action=crear_venta', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ items })
        });

        const data = await resp.json();

        if (data.success) {
            msj.className = 'carrito-mensaje exito';
            msj.textContent = `¡Venta #${data.orden_id} creada! Total: $${formatoPrecio(data.total)}`;
            carritoVenta = [];
            renderCarrito();
            // Actualizar pedidos en la pestaña de Pedidos
            cargarPedidos();
        } else {
            if (resp.status === 401) {
                window.location.href = 'login.php';
                return;
            }
            msj.className = 'carrito-mensaje error';
            msj.textContent = data.error || 'Error al crear la venta.';
        }
    } catch (e) {
        msj.className = 'carrito-mensaje error';
        msj.textContent = 'Error de conexión. Intenta de nuevo.';
    } finally {
        btn.disabled = carritoVenta.length === 0;
        btn.innerHTML = '<i class="fa-solid fa-cash-register"></i> Completar Venta';
    }
}

// === Búsqueda con debounce ===
let ventaDebounceTimer;
document.getElementById('venta-busqueda').addEventListener('input', () => {
    clearTimeout(ventaDebounceTimer);
    ventaDebounceTimer = setTimeout(filtrarProductos, 300);
});

// === Delegación de eventos: agregar al carrito ===
document.getElementById('venta-productos').addEventListener('click', e => {
    const btn = e.target.closest('.btn-agregar-venta');
    if (!btn || btn.disabled) return;
    agregarAlCarrito(
        parseInt(btn.dataset.id, 10),
        btn.dataset.nombre,
        parseFloat(btn.dataset.precio),
        parseInt(btn.dataset.stock, 10)
    );
});

// === Botones del carrito ===
document.getElementById('btn-vaciar-venta').addEventListener('click', vaciarCarritoVenta);
document.getElementById('btn-completar-venta').addEventListener('click', completarVenta);

// ═══════════════════════════════════════════════════════
// ESCANEAR QR — Escáner webcam + ingreso manual + reclamar
// ═══════════════════════════════════════════════════════

let html5QrCode = null;
let escanerActivo = false;
let ordenActualId = null; // orden mostrada en el panel de verificación

// === Mostrar mensaje en el bloque de estado de escaneo ===
function mostrarMensajeEscaneo(tipo, texto) {
    const box = document.getElementById('escanear-mensaje');
    box.className = 'escanear-mensaje ' + tipo;
    box.innerHTML = (tipo === 'exito'
        ? '<i class="fa-solid fa-circle-check"></i> '
        : '<i class="fa-solid fa-triangle-exclamation"></i> ')
        + escapeHtml(texto);
    if (tipo === 'exito') {
        setTimeout(() => box.classList.add('hidden'), 5000);
    }
}

// === Set estado del escáner ===
function setEscanerStatus(texto, icono) {
    const status = document.getElementById('escaner-status');
    status.innerHTML = '<i class="fa-solid fa-' + icono + '"></i> <span>' + escapeHtml(texto) + '</span>';
}

// === Iniciar escáner de cámara ===
async function iniciarEscanner() {
    if (escanerActivo) return;

    const reader = document.getElementById('qr-reader');
    reader.innerHTML = '';

    setEscanerStatus('Iniciando cámara...', 'spinner fa-spin');
    document.getElementById('camara-error').classList.add('hidden');

    try {
        html5QrCode = new Html5Qrcode('qr-reader');
        const config = { fps: 10, qrbox: { width: 250, height: 250 } };

        await html5QrCode.start(
            { facingMode: 'environment' },
            config,
            onScanExitoso
        );

        escanerActivo = true;
        setEscanerStatus('Escaneando...', 'camera');
        document.getElementById('btn-iniciar-escaner').classList.add('hidden');
        document.getElementById('btn-detener-escaner').classList.remove('hidden');
    } catch (err) {
        console.error('Error al iniciar cámara:', err);
        setEscanerStatus('Cámara no disponible', 'camera-slash');
        document.getElementById('camara-error').classList.remove('hidden');
        mostrarMensajeEscaneo('error', 'No se pudo acceder a la cámara. Usa el ingreso manual.');
    }
}

// === Detener escáner ===
async function detenerEscanner() {
    if (!escanerActivo || !html5QrCode) return;
    try {
        await html5QrCode.stop();
    } catch (e) { /* ignorar si ya está detenido */ }
    html5QrCode = null;
    escanerActivo = false;
    document.getElementById('qr-reader').innerHTML = '';
    setEscanerStatus('Escáner detenido', 'camera');
    document.getElementById('btn-iniciar-escaner').classList.remove('hidden');
    document.getElementById('btn-detener-escaner').classList.add('hidden');
}

// === Callback tras escaneo exitoso ===
async function onScanExitoso(codigo) {
    // Detener para no escanear varias veces seguidas
    await detenerEscanner();
    await buscarOrdenQR(codigo);
}

// === Buscar orden por código QR ===
async function buscarOrdenQR(codigo) {
    mostrarMensajeEscaneo('info', 'Buscando pedido: ' + codigo);
    try {
        const resp = await fetch('cajero_api.php?action=buscar_qr', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ codigo_qr: codigo })
        });

        const data = await resp.json();

        if (resp.status === 401) { window.location.href = 'login.php'; return; }
        if (resp.status === 403) { window.location.href = 'login.php'; return; }

        if (!data.success) {
            ocultarVerificacion();
            if (resp.status === 404) {
                mostrarMensajeEscaneo('error', 'Código QR no encontrado.');
            } else if (data.error === 'entregado') {
                mostrarMensajeEscaneo('error', 'Este pedido ya fue entregado.');
            } else {
                mostrarMensajeEscaneo('error', data.message || data.error || 'Error al buscar el pedido.');
            }
            return;
        }

        mostrarVerificacion(data.orden);
    } catch (e) {
        mostrarMensajeEscaneo('error', 'Error de conexión. Intenta de nuevo.');
    }
}

// === Mostrar panel de verificación ===
function mostrarVerificacion(orden) {
    ordenActualId = orden.id;

    const estadoLabel = {
        pendiente: 'Pendiente',
        en_preparacion: 'En preparación',
        listo: 'Listo',
        entregado: 'Entregado'
    };
    const label = estadoLabel[orden.estado] || orden.estado;

    let itemsHtml = (orden.items && orden.items.length > 0)
        ? orden.items.map(i =>
            `<li><span class="vi-nombre">${escapeHtml(i.producto_nombre)}</span>
                 <span class="vi-cant">x${i.cantidad}</span>
                 <span class="vi-precio">$${formatoPrecio(i.precio_unitario)}</span></li>`).join('')
        : '<li class="vi-vacio">Sin detalles</li>';

    let fechaStr = '';
    try {
        const f = new Date(orden.fecha_creacion + (orden.fecha_creacion.indexOf('Z') === -1 ? 'Z' : ''));
        fechaStr = f.toLocaleString('es-CO');
    } catch (e) { fechaStr = orden.fecha_creacion; }

    const body = document.getElementById('verificacion-body');
    body.innerHTML = `
        <div class="vi-fila"><span class="vi-label">Pedido</span><span class="vi-valor">#${orden.id}</span></div>
        <div class="vi-fila"><span class="vi-label">Cliente</span><span class="vi-valor">${escapeHtml(orden.cliente_nombre || 'Venta rápida')}</span></div>
        <div class="vi-fila"><span class="vi-label">Fecha</span><span class="vi-valor">${escapeHtml(fechaStr)}</span></div>
        <div class="vi-fila"><span class="vi-label">Estado</span><span class="vi-valor"><span class="estado-badge estado-${orden.estado}">${label}</span></span></div>
        <div class="vi-subtitulo">Items</div>
        <ul class="vi-items">${itemsHtml}</ul>
        <div class="vi-total"><span>Total</span><span>$${formatoPrecio(orden.total)}</span></div>
    `;

    const panel = document.getElementById('verificacion-panel');
    const btnReclamar = document.getElementById('btn-reclamar');

    // Solo se puede reclamar si está listo
    if (orden.estado === 'listo') {
        btnReclamar.disabled = false;
        btnReclamar.classList.remove('hidden');
        mostrarMensajeEscaneo('success-info', 'Pedido listo para reclamar.');
    } else {
        btnReclamar.disabled = true;
        btnReclamar.classList.remove('hidden');
        mostrarMensajeEscaneo('error', `Este pedido aún no está listo (estado: ${label}).`);
    }

    panel.classList.remove('hidden');
    panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// === Ocultar panel de verificación y resetear ===
function ocultarVerificacion() {
    ordenActualId = null;
    const panel = document.getElementById('verificacion-panel');
    panel.classList.add('hidden');
    document.getElementById('verificacion-body').innerHTML = '';
}

// === Reclamar (entregar) pedido ===
async function reclamarPedido() {
    if (!ordenActualId) return;
    const btn = document.getElementById('btn-reclamar');
    btn.disabled = true;
    const original = btn.innerHTML;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Entregando...';

    try {
        const resp = await fetch('cajero_api.php?action=reclamar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ orden_id: ordenActualId })
        });

        const data = await resp.json();

        if (resp.status === 401 || resp.status === 403) { window.location.href = 'login.php'; return; }

        if (data.success) {
            mostrarMensajeEscaneo('exito', data.message || 'Pedido entregado.');
            ocultarVerificacion();
            // Refrescar lista de pedidos para que el pedido desaparezca de la columna Listos
            cargarPedidos();
            // Volver al escáner si estaba activo
            setTimeout(() => iniciarEscanner(), 800);
        } else {
            if (resp.status === 404) {
                mostrarMensajeEscaneo('error', 'Pedido no encontrado.');
            } else if (resp.status === 409) {
                mostrarMensajeEscaneo('error', data.message || 'Este pedido ya fue entregado por otro cajero.');
            } else if (data.error === 'not_ready') {
                mostrarMensajeEscaneo('error', data.message || 'El pedido aún no está listo.');
            } else {
                mostrarMensajeEscaneo('error', data.message || data.error || 'Error al reclamar.');
            }
            btn.disabled = false;
        }
    } catch (e) {
        mostrarMensajeEscaneo('error', 'Error de conexión. Intenta de nuevo.');
        btn.disabled = false;
    } finally {
        if (ordenActualId !== null) {
            btn.innerHTML = original;
        }
    }
}

// === Listeners del escáner ===
document.getElementById('btn-iniciar-escaner').addEventListener('click', iniciarEscanner);
document.getElementById('btn-detener-escaner').addEventListener('click', detenerEscanner);
document.getElementById('btn-buscar-manual').addEventListener('click', () => {
    const cod = document.getElementById('codigo-manual').value.trim();
    if (cod) buscarOrdenQR(cod);
});
document.getElementById('codigo-manual').addEventListener('keydown', e => {
    if (e.key === 'Enter') {
        const cod = e.target.value.trim();
        if (cod) buscarOrdenQR(cod);
    }
});
document.getElementById('btn-reclamar').addEventListener('click', reclamarPedido);
document.getElementById('btn-cancelar-verificacion').addEventListener('click', () => {
    ocultarVerificacion();
    mostrarMensajeEscaneo('info', 'Operación cancelada.');
    // Reiniciar escáner si estaba activo
    if (!escanerActivo) iniciarEscanner();
});
</script>

</body>
</html>