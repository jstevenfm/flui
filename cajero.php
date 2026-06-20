<?php
// cajero.php — Panel del cajero: dashboard de pedidos, venta rápida, escaneo QR
require_once 'auth.php';
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
        <button class="cajero-tab" data-tab="nueva-venta" disabled>
            <i class="fa-solid fa-cart-plus"></i> Nueva Venta
        </button>
        <button class="cajero-tab" data-tab="escanear" disabled>
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

    <!-- Pane: Nueva Venta (placeholder - PR2) -->
    <div class="cajero-pane" id="pane-nueva-venta">
        <div class="tab-placeholder">
            <i class="fa-solid fa-cart-plus"></i>
            <p>Nueva Venta</p>
            <p style="font-size: 0.85rem;">Próximamente...</p>
        </div>
    </div>

    <!-- Pane: Escanear QR (placeholder - PR3) -->
    <div class="cajero-pane" id="pane-escanear">
        <div class="tab-placeholder">
            <i class="fa-solid fa-qrcode"></i>
            <p>Escanear QR</p>
            <p style="font-size: 0.85rem;">Próximamente...</p>
        </div>
    </div>

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
    });
});
</script>

</body>
</html>