<?php
// adm.php — Panel de administración (solo admin)
require_once __DIR__ . '/auth.php';
checkRole('admin');
$nombre = $_SESSION['usuario_nombre'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flui POS - Admin</title>
    <link rel="stylesheet" href="adm.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
</head>
<body>

    <!-- Header -->
    <header class="admin-header">
        <div class="admin-brand">
            <span class="brand-icon"><i class="fa-solid fa-cash-register"></i></span>
            <h1>Flui</h1>
        </div>
        <div class="admin-user">
            <span>Hola, <strong><?php echo htmlspecialchars($nombre); ?></strong></span>
            <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Cerrar sesión</a>
        </div>
    </header>

    <!-- Tab bar -->
    <nav class="admin-tabs">
        <button class="admin-tab active" data-tab="dashboard">
            <i class="fa-solid fa-chart-line"></i> Dashboard
        </button>
        <button class="admin-tab" data-tab="cajeros">
            <i class="fa-solid fa-users"></i> Cajeros
        </button>
        <button class="admin-tab" data-tab="categorias">
            <i class="fa-solid fa-tags"></i> Categorías
        </button>
        <button class="admin-tab" data-tab="productos">
            <i class="fa-solid fa-box"></i> Productos
        </button>
        <button class="admin-tab" data-tab="reportes">
            <i class="fa-solid fa-file-invoice"></i> Reportes
        </button>
        <button class="admin-tab" data-tab="escanear">
            <i class="fa-solid fa-qrcode"></i> Escanear QR
        </button>
    </nav>

    <!-- Pane: Dashboard -->
    <div class="admin-pane active" id="pane-dashboard">
        <div class="pane-error hidden" id="dashboard-error">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span id="dashboard-error-text"></span>
            <button onclick="cargarDashboard()" class="btn-reintento"><i class="fa-solid fa-arrows-rotate"></i> Reintentar</button>
        </div>

        <!-- Stats cards -->
        <div class="stats-grid" id="dashboard-stats">
            <div class="stat-card">
                <div class="card-icon-wrap"><i class="fa-solid fa-wallet"></i></div>
                <p class="stat-label">Ventas Hoy</p>
                <h3 class="stat-value" id="stat-ventas">$0</h3>
            </div>
            <div class="stat-card">
                <div class="card-icon-wrap"><i class="fa-solid fa-receipt"></i></div>
                <p class="stat-label">Órdenes Hoy</p>
                <h3 class="stat-value" id="stat-ordenes">0</h3>
            </div>
            <div class="stat-card">
                <div class="card-icon-wrap"><i class="fa-solid fa-clock"></i></div>
                <p class="stat-label">Pendientes</p>
                <h3 class="stat-value" id="stat-pendientes">0</h3>
            </div>
        </div>

        <!-- Top products -->
        <div class="dashboard-section">
            <h2 class="section-title"><i class="fa-solid fa-trophy"></i> Top 5 Productos Hoy</h2>
            <div id="top-productos-container">
                <div class="spinner"><i class="fa-solid fa-spinner fa-spin"></i> Cargando...</div>
            </div>
        </div>

        <!-- Recent orders -->
        <div class="dashboard-section">
            <h2 class="section-title"><i class="fa-solid fa-list"></i> Últimas Órdenes</h2>
            <div class="table-responsive" id="ordenes-recientes-container">
                <div class="spinner"><i class="fa-solid fa-spinner fa-spin"></i> Cargando...</div>
            </div>
        </div>
    </div>

    <!-- Pane: Cajeros -->
    <div class="admin-pane" id="pane-cajeros">
        <div class="pane-error hidden" id="cajeros-error">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span id="cajeros-error-text"></span>
            <button onclick="cargarCajeros()" class="btn-reintento"><i class="fa-solid fa-arrows-rotate"></i> Reintentar</button>
        </div>

        <div class="pane-head">
            <h2 class="section-title"><i class="fa-solid fa-users"></i> Gestionar Cajeros</h2>
            <button class="btn-primary" onclick="abrirModalCrearCajero()">
                <i class="fa-solid fa-user-plus"></i> Nuevo Cajero
            </button>
        </div>

        <div class="table-responsive" id="cajeros-container">
            <div class="spinner"><i class="fa-solid fa-spinner fa-spin"></i> Cargando...</div>
        </div>
    </div>

    <!-- Modal: Crear cajero -->
    <div class="modal-overlay" id="modal-crear-cajero">
        <div class="modal-content">
            <button class="modal-close" onclick="cerrarModal('modal-crear-cajero')">&times;</button>
            <div class="modal-header">
                <h3><i class="fa-solid fa-user-plus"></i> Nuevo Cajero</h3>
            </div>
            <div class="modal-body">
                <form id="form-crear-cajero" onsubmit="guardarCajero(event)">
                    <div class="form-group">
                        <label for="cajero-nuevo-usuario">Usuario</label>
                        <input type="text" id="cajero-nuevo-usuario" name="usuario" required autocomplete="username">
                    </div>
                    <div class="form-group">
                        <label for="cajero-nuevo-email">Correo electrónico</label>
                        <input type="email" id="cajero-nuevo-email" name="email" required autocomplete="email">
                    </div>
                    <div class="form-group">
                        <label for="cajero-nuevo-password">Contraseña (mínimo 6 caracteres)</label>
                        <input type="password" id="cajero-nuevo-password" name="password" required minlength="6" autocomplete="new-password">
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn-secondary" onclick="cerrarModal('modal-crear-cajero')">Cancelar</button>
                        <button type="submit" class="btn-primary"><i class="fa-solid fa-floppy-disk"></i> Crear</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Editar cajero -->
    <div class="modal-overlay" id="modal-editar-cajero">
        <div class="modal-content">
            <button class="modal-close" onclick="cerrarModal('modal-editar-cajero')">&times;</button>
            <div class="modal-header">
                <h3><i class="fa-solid fa-user-pen"></i> Editar Cajero</h3>
            </div>
            <div class="modal-body">
                <form id="form-editar-cajero" onsubmit="actualizarCajero(event)">
                    <input type="hidden" id="cajero-editar-id" name="id">
                    <div class="form-group">
                        <label for="cajero-editar-usuario">Usuario</label>
                        <input type="text" id="cajero-editar-usuario" name="usuario" required autocomplete="username">
                    </div>
                    <div class="form-group">
                        <label for="cajero-editar-email">Correo electrónico</label>
                        <input type="email" id="cajero-editar-email" name="email" required autocomplete="email">
                    </div>
                    <div class="form-group">
                        <label for="cajero-editar-password">Nueva contraseña</label>
                        <input type="password" id="cajero-editar-password" name="password" placeholder="Dejar vacío para mantener la actual" autocomplete="new-password">
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn-secondary" onclick="cerrarModal('modal-editar-cajero')">Cancelar</button>
                        <button type="submit" class="btn-primary"><i class="fa-solid fa-floppy-disk"></i> Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Pane: Categorías -->
    <div class="admin-pane" id="pane-categorias">
        <div class="pane-error hidden" id="categorias-error">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span id="categorias-error-text"></span>
            <button onclick="cargarCategorias()" class="btn-reintento"><i class="fa-solid fa-arrows-rotate"></i> Reintentar</button>
        </div>

        <div class="pane-head">
            <h2 class="section-title"><i class="fa-solid fa-tags"></i> Gestionar Categorías</h2>
            <button class="btn-primary" onclick="abrirModalCrearCategoria()">
                <i class="fa-solid fa-plus"></i> Nueva Categoría
            </button>
        </div>

        <div class="table-responsive" id="categorias-container">
            <div class="spinner"><i class="fa-solid fa-spinner fa-spin"></i> Cargando...</div>
        </div>
    </div>

    <!-- Modal: Crear categoría -->
    <div class="modal-overlay" id="modal-crear-categoria">
        <div class="modal-content">
            <button class="modal-close" onclick="cerrarModal('modal-crear-categoria')">&times;</button>
            <div class="modal-header">
                <h3><i class="fa-solid fa-tag"></i> Nueva Categoría</h3>
            </div>
            <div class="modal-body">
                <form id="form-crear-categoria" onsubmit="guardarCategoria(event)">
                    <div class="form-group">
                        <label for="categoria-nueva-nombre">Nombre</label>
                        <input type="text" id="categoria-nueva-nombre" name="nombre" required maxlength="100">
                    </div>
                    <div class="form-group">
                        <label for="categoria-nueva-descripcion">Descripción (opcional)</label>
                        <textarea id="categoria-nueva-descripcion" name="descripcion" class="form-textarea" maxlength="500" placeholder="Breve descripción de la categoría..."></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn-secondary" onclick="cerrarModal('modal-crear-categoria')">Cancelar</button>
                        <button type="submit" class="btn-primary"><i class="fa-solid fa-floppy-disk"></i> Crear</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Editar categoría -->
    <div class="modal-overlay" id="modal-editar-categoria">
        <div class="modal-content">
            <button class="modal-close" onclick="cerrarModal('modal-editar-categoria')">&times;</button>
            <div class="modal-header">
                <h3><i class="fa-solid fa-tag"></i> Editar Categoría</h3>
            </div>
            <div class="modal-body">
                <form id="form-editar-categoria" onsubmit="actualizarCategoria(event)">
                    <input type="hidden" id="categoria-editar-id" name="id">
                    <div class="form-group">
                        <label for="categoria-editar-nombre">Nombre</label>
                        <input type="text" id="categoria-editar-nombre" name="nombre" required maxlength="100">
                    </div>
                    <div class="form-group">
                        <label for="categoria-editar-descripcion">Descripción (opcional)</label>
                        <textarea id="categoria-editar-descripcion" name="descripcion" class="form-textarea" maxlength="500" placeholder="Breve descripción de la categoría..."></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn-secondary" onclick="cerrarModal('modal-editar-categoria')">Cancelar</button>
                        <button type="submit" class="btn-primary"><i class="fa-solid fa-floppy-disk"></i> Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Pane: Productos -->
    <div class="admin-pane" id="pane-productos">
        <div class="pane-error hidden" id="productos-error">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span id="productos-error-text"></span>
        </div>
        <div class="tab-placeholder">
            <i class="fa-solid fa-box"></i>
            <p>Gestión de productos — próximamente</p>
        </div>
    </div>

    <!-- Pane: Reportes -->
    <div class="admin-pane" id="pane-reportes">
        <div class="pane-error hidden" id="reportes-error">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span id="reportes-error-text"></span>
        </div>
        <div class="tab-placeholder">
            <i class="fa-solid fa-file-invoice"></i>
            <p>Reportes de ventas — próximamente</p>
        </div>
    </div>

    <!-- Pane: Escanear QR -->
    <div class="admin-pane" id="pane-escanear">
        <div class="pane-error hidden" id="escanear-error">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span id="escanear-error-text"></span>
        </div>
        <div class="tab-placeholder">
            <i class="fa-solid fa-qrcode"></i>
            <p>Escáner QR — próximamente</p>
        </div>
    </div>

    <!-- Toast container for notifications -->
    <div id="toast-container"></div>

<script>
// Debug: admin session
console.log('Admin:', <?php echo json_encode($nombre); ?>);

// === Utilidades ===
function escapeHtml(s) {
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
}

function formatoPrecio(n) {
    return Number(n).toLocaleString('es-CO');
}

function mostrarToast(tipo, mensaje) {
    const container = document.getElementById('toast-container');
    const toast = document.createElement('div');
    toast.className = 'toast toast-' + tipo;
    toast.innerHTML = '<i class="fa-solid ' + (tipo === 'exito' ? 'fa-circle-check' : 'fa-circle-xmark') + '"></i> ' + escapeHtml(mensaje);
    container.appendChild(toast);
    setTimeout(() => {
        toast.classList.add('toast-fade-out');
        setTimeout(() => toast.remove(), 400);
    }, 3000);
}

// === Tab switching with hash persistence + lazy load ===
const paneLoaded = {
    dashboard: false,
    cajeros: false,
    categorias: false,
    productos: false,
    reportes: false,
    escanear: false
};

function activarTab(tabName) {
    // Update tab buttons
    document.querySelectorAll('.admin-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.admin-pane').forEach(p => p.classList.remove('active'));

    const tabBtn = document.querySelector('.admin-tab[data-tab="' + tabName + '"]');
    const pane = document.getElementById('pane-' + tabName);

    if (tabBtn) tabBtn.classList.add('active');
    if (pane) pane.classList.add('active');

    // Lazy load
    if (!paneLoaded[tabName]) {
        paneLoaded[tabName] = true;
        switch (tabName) {
            case 'dashboard': cargarDashboard(); break;
            case 'cajeros': cargarCajeros(); break;
            case 'categorias': cargarCategorias(); break;
            // Other tabs will load in future slices
        }
    }
}

// Click handler
document.querySelectorAll('.admin-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        const tabName = this.dataset.tab;
        window.location.hash = tabName;
        activarTab(tabName);
    });
});

// Hash persistence on load
const hashTab = window.location.hash.replace('#', '');
if (hashTab && paneLoaded.hasOwnProperty(hashTab)) {
    activarTab(hashTab);
} else {
    activarTab('dashboard');
    paneLoaded.dashboard = true;
    cargarDashboard();
}

// Hash change handler
window.addEventListener('hashchange', () => {
    const h = window.location.hash.replace('#', '');
    if (h && paneLoaded.hasOwnProperty(h)) {
        activarTab(h);
    }
});

// === Dashboard: cargar estadísticas ===
async function cargarDashboard() {
    const errorBox = document.getElementById('dashboard-error');
    const errorText = document.getElementById('dashboard-error-text');
    errorBox.classList.add('hidden');

    try {
        const resp = await fetch('admin_api.php?action=dashboard_stats');
        const data = await resp.json();

        if (!data.success) {
            if (resp.status === 401 || resp.status === 403) {
                window.location.href = 'login.php';
                return;
            }
            throw new Error(data.error || data.message || 'Error al cargar estadísticas.');
        }

        // Fill stat cards
        document.getElementById('stat-ventas').textContent = '$' + formatoPrecio(data.ventas_hoy);
        document.getElementById('stat-ordenes').textContent = data.ordenes_hoy;
        document.getElementById('stat-pendientes').textContent = data.pendientes;

        // Top products
        const topContainer = document.getElementById('top-productos-container');
        if (data.top_productos && data.top_productos.length > 0) {
            let html = '<table class="admin-table"><thead><tr><th>Producto</th><th>Cantidad</th></tr></thead><tbody>';
            data.top_productos.forEach(p => {
                html += '<tr><td>' + escapeHtml(p.nombre) + '</td><td>' + p.cantidad + '</td></tr>';
            });
            html += '</tbody></table>';
            topContainer.innerHTML = html;
        } else {
            topContainer.innerHTML = '<div class="empty-state"><i class="fa-solid fa-chart-simple"></i><p>Sin datos disponibles</p></div>';
        }

        // Recent orders
        const ordenesContainer = document.getElementById('ordenes-recientes-container');
        if (data.ordenes_recientes && data.ordenes_recientes.length > 0) {
            const estadoLabel = {
                pendiente: 'Pendiente',
                en_preparacion: 'En preparación',
                listo: 'Listo',
                entregado: 'Entregado',
                cancelada: 'Cancelada'
            };
            let html = '<table class="admin-table"><thead><tr><th>#</th><th>Cliente</th><th>Hora</th><th>Total</th><th>Estado</th></tr></thead><tbody>';
            data.ordenes_recientes.forEach(o => {
                const cliente = o.tipo_pedido === 'venta_rapida' ? 'Venta rápida' : (o.cliente_nombre || '—');
                let fechaStr = '';
                try {
                    const f = new Date(o.fecha_creacion + (o.fecha_creacion.indexOf('Z') === -1 ? 'Z' : ''));
                    fechaStr = f.toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit' });
                } catch (e) { fechaStr = o.fecha_creacion; }

                const label = estadoLabel[o.estado] || o.estado;
                html += '<tr>'
                    + '<td>#' + o.id + '</td>'
                    + '<td>' + escapeHtml(cliente) + '</td>'
                    + '<td>' + escapeHtml(fechaStr) + '</td>'
                    + '<td>$' + formatoPrecio(o.total) + '</td>'
                    + '<td><span class="estado-badge estado-' + o.estado + '">' + label + '</span></td>'
                    + '</tr>';
            });
            html += '</tbody></table>';
            ordenesContainer.innerHTML = html;
        } else {
            ordenesContainer.innerHTML = '<div class="empty-state"><i class="fa-solid fa-inbox"></i><p>Sin órdenes recientes</p></div>';
        }

    } catch (e) {
        errorText.textContent = e.message || 'Error de conexión.';
        errorBox.classList.remove('hidden');
    }
}

// =========================================================
// Cajeros CRUD (Slice 2)
// =========================================================

// Cache en memoria de los cajeros cargados (para botones de acción)
let cajerosCache = [];

// --- Cargar lista de cajeros ---
async function cargarCajeros() {
    const container = document.getElementById('cajeros-container');
    const errorBox = document.getElementById('cajeros-error');
    const errorText = document.getElementById('cajeros-error-text');
    errorBox.classList.add('hidden');
    container.innerHTML = '<div class="spinner"><i class="fa-solid fa-spinner fa-spin"></i> Cargando...</div>';

    try {
        const resp = await fetch('admin_api.php?action=listar_cajeros');
        const data = await resp.json();

        if (!data.success) {
            if (resp.status === 401 || resp.status === 403) {
                window.location.href = 'login.php';
                return;
            }
            throw new Error(data.error || data.message || 'Error al cargar cajeros.');
        }

        cajerosCache = data.cajeros || [];
        renderTablaCajeros(cajerosCache);
    } catch (e) {
        errorText.textContent = e.message || 'Error de conexión.';
        errorBox.classList.remove('hidden');
        container.innerHTML = '';
    }
}

// --- Renderizar tabla de cajeros ---
function renderTablaCajeros(cajeros) {
    const container = document.getElementById('cajeros-container');

    if (!cajeros || cajeros.length === 0) {
        container.innerHTML =
            '<table class="admin-table"><thead><tr>'
            + '<th>ID</th><th>Usuario</th><th>Email</th><th>Estado</th><th>Acciones</th>'
            + '</tr></thead><tbody>'
            + '<tr><td colspan="5" class="empty-state"><i class="fa-solid fa-user-slash"></i><p>No hay cajeros registrados.</p></td></tr>'
            + '</tbody></table>';
        return;
    }

    let html = '<table class="admin-table"><thead><tr>'
        + '<th>ID</th><th>Usuario</th><th>Email</th><th>Estado</th><th>Acciones</th>'
        + '</tr></thead><tbody>';

    cajeros.forEach(c => {
        // activo puede venir como 0/1 (MySQL BOOLEAN) o true/false — normalizar a bool
        const activo = c.activo == 1 || c.activo === true;
        const badge = activo
            ? '<span class="badge-activo">Activo</span>'
            : '<span class="badge-inactivo">Inactivo</span>';
        const toggleLabel = activo ? 'Desactivar' : 'Activar';
        const toggleIcon = activo ? 'fa-circle-minus' : 'fa-circle-check';
        const toggleClass = activo ? 'btn-danger' : 'btn-primary';

        html += '<tr>'
            + '<td data-label="ID">#' + c.id + '</td>'
            + '<td data-label="Usuario">' + escapeHtml(c.usuario) + '</td>'
            + '<td data-label="Email">' + escapeHtml(c.email) + '</td>'
            + '<td data-label="Estado">' + badge + '</td>'
            + '<td data-label="Acciones" class="acciones-cell">'
            +   '<button class="btn-icon ' + toggleClass + '" onclick="toggleCajero(' + c.id + ', ' + (activo ? '0' : '1') + ')">'
            +     '<i class="fa-solid ' + toggleIcon + '"></i> ' + toggleLabel
            +   '</button>'
            +   '<button class="btn-icon btn-secondary" onclick="abrirModalEditarCajero(' + c.id + ')">'
            +     '<i class="fa-solid fa-user-pen"></i> Editar'
            +   '</button>'
            + '</td>'
            + '</tr>';
    });

    html += '</tbody></table>';
    container.innerHTML = html;
}

// --- Abrir modal crear cajero ---
function abrirModalCrearCajero() {
    document.getElementById('form-crear-cajero').reset();
    abrirModal('modal-crear-cajero');
}

// --- Guardar (crear) cajero ---
async function guardarCajero(event) {
    event.preventDefault();
    const usuario = document.getElementById('cajero-nuevo-usuario').value.trim();
    const email = document.getElementById('cajero-nuevo-email').value.trim();
    const password = document.getElementById('cajero-nuevo-password').value;

    if (password.length < 6) {
        mostrarToast('error', 'La contraseña debe tener al menos 6 caracteres.');
        return;
    }

    try {
        const resp = await apiFetch('admin_api.php?action=crear_cajero', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ usuario, email, password })
        });

        if (resp.status === 401 || resp.status === 403) {
            window.location.href = 'login.php';
            return;
        }
        const data = await resp.json();

        if (!resp.ok || !data.success) {
            mostrarToast('error', data.error || 'No se pudo crear el cajero.');
            return;
        }

        mostrarToast('exito', 'Cajero creado exitosamente.');
        cerrarModal('modal-crear-cajero');
        cargarCajeros();
    } catch (e) {
        mostrarToast('error', 'Error de conexión.');
    }
}

// --- Abrir modal editar cajero ---
function abrirModalEditarCajero(id) {
    const cajero = cajerosCache.find(c => c.id == id);
    if (!cajero) {
        mostrarToast('error', 'Cajero no encontrado.');
        return;
    }
    document.getElementById('cajero-editar-id').value = cajero.id;
    document.getElementById('cajero-editar-usuario').value = cajero.usuario || '';
    document.getElementById('cajero-editar-email').value = cajero.email || '';
    document.getElementById('cajero-editar-password').value = '';
    abrirModal('modal-editar-cajero');
}

// --- Actualizar (editar) cajero ---
async function actualizarCajero(event) {
    event.preventDefault();
    const id = document.getElementById('cajero-editar-id').value;
    const usuario = document.getElementById('cajero-editar-usuario').value.trim();
    const email = document.getElementById('cajero-editar-email').value.trim();
    const password = document.getElementById('cajero-editar-password').value;

    if (password !== '' && password.length < 6) {
        mostrarToast('error', 'La contraseña debe tener al menos 6 caracteres.');
        return;
    }

    // Construimos el body: password solo se envía si el usuario escribió algo
    const body = { id: parseInt(id, 10), usuario, email };
    if (password !== '') body.password = password;

    try {
        const resp = await apiFetch('admin_api.php?action=editar_cajero', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        });

        if (resp.status === 401 || resp.status === 403) {
            window.location.href = 'login.php';
            return;
        }
        const data = await resp.json();

        if (!resp.ok || !data.success) {
            mostrarToast('error', data.error || 'No se pudo actualizar el cajero.');
            return;
        }

        mostrarToast('exito', 'Cajero actualizado exitosamente.');
        cerrarModal('modal-editar-cajero');
        cargarCajeros();
    } catch (e) {
        mostrarToast('error', 'Error de conexión.');
    }
}

// --- Activar/desactivar cajero ---
async function toggleCajero(id, nuevoActivo) {
    const accion = nuevoActivo == 1 ? 'activar' : 'desactivar';
    if (!confirm('¿Confirmas que deseas ' + accion + ' este cajero?')) return;

    try {
        const resp = await apiFetch('admin_api.php?action=toggle_cajero', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: parseInt(id, 10), activo: nuevoActivo == 1 })
        });

        if (resp.status === 401 || resp.status === 403) {
            window.location.href = 'login.php';
            return;
        }
        const data = await resp.json();

        if (!resp.ok || !data.success) {
            mostrarToast('error', data.error || 'No se pudo actualizar el estado.');
            return;
        }

        mostrarToast('exito', 'Estado actualizado.');
        cargarCajeros();
    } catch (e) {
        mostrarToast('error', 'Error de conexión.');
    }
}

// =========================================================
// Categorías CRUD (Slice 3)
// =========================================================

// Cache en memoria de las categorías cargadas (para botones de acción)
let categoriasCache = [];

// --- Cargar lista de categorías ---
async function cargarCategorias() {
    const container = document.getElementById('categorias-container');
    const errorBox = document.getElementById('categorias-error');
    const errorText = document.getElementById('categorias-error-text');
    errorBox.classList.add('hidden');
    container.innerHTML = '<div class="spinner"><i class="fa-solid fa-spinner fa-spin"></i> Cargando...</div>';

    try {
        const resp = await fetch('admin_api.php?action=listar_categorias');
        const data = await resp.json();

        if (!data.success) {
            if (resp.status === 401 || resp.status === 403) {
                window.location.href = 'login.php';
                return;
            }
            throw new Error(data.error || data.message || 'Error al cargar categorías.');
        }

        categoriasCache = data.categorias || [];
        renderTablaCategorias(categoriasCache);
    } catch (e) {
        errorText.textContent = e.message || 'Error de conexión.';
        errorBox.classList.remove('hidden');
        container.innerHTML = '';
    }
}

// --- Renderizar tabla de categorías ---
function renderTablaCategorias(categorias) {
    const container = document.getElementById('categorias-container');

    if (!categorias || categorias.length === 0) {
        container.innerHTML =
            '<table class="admin-table"><thead><tr>'
            + '<th>Nombre</th><th>Descripción</th><th>Productos</th><th>Acciones</th>'
            + '</tr></thead><tbody>'
            + '<tr><td colspan="4" class="empty-state"><i class="fa-solid fa-tags"></i><p>No hay categorías registradas.</p></td></tr>'
            + '</tbody></table>';
        return;
    }

    let html = '<table class="admin-table"><thead><tr>'
        + '<th>Nombre</th><th>Descripción</th><th>Productos</th><th>Acciones</th>'
        + '</tr></thead><tbody>';

    categorias.forEach(c => {
        const total = parseInt(c.total_productos || 0, 10);
        const countBadge = '<span class="product-count' + (total > 0 ? ' product-count-active' : '') + '">' + total + '</span>';
        const desc = c.descripcion ? escapeHtml(c.descripcion) : '<span class="text-muted">—</span>';

        html += '<tr>'
            + '<td data-label="Nombre"><strong>' + escapeHtml(c.nombre) + '</strong></td>'
            + '<td data-label="Descripción">' + desc + '</td>'
            + '<td data-label="Productos">' + countBadge + '</td>'
            + '<td data-label="Acciones" class="acciones-cell">'
            +   '<button class="btn-icon btn-secondary" onclick="abrirModalEditarCategoria(' + c.id + ')">'
            +     '<i class="fa-solid fa-pen"></i> Editar'
            +   '</button>'
            +   '<button class="btn-icon btn-danger" onclick="eliminarCategoria(' + c.id + ')">'
            +     '<i class="fa-solid fa-trash"></i> Eliminar'
            +   '</button>'
            + '</td>'
            + '</tr>';
    });

    html += '</tbody></table>';
    container.innerHTML = html;
}

// --- Abrir modal crear categoría ---
function abrirModalCrearCategoria() {
    document.getElementById('form-crear-categoria').reset();
    abrirModal('modal-crear-categoria');
}

// --- Guardar (crear) categoría ---
async function guardarCategoria(event) {
    event.preventDefault();
    const nombre = document.getElementById('categoria-nueva-nombre').value.trim();
    const descripcion = document.getElementById('categoria-nueva-descripcion').value.trim();

    try {
        const resp = await apiFetch('admin_api.php?action=crear_categoria', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ nombre, descripcion })
        });

        if (resp.status === 401 || resp.status === 403) {
            window.location.href = 'login.php';
            return;
        }
        const data = await resp.json();

        if (!resp.ok || !data.success) {
            mostrarToast('error', data.error || 'No se pudo crear la categoría.');
            return;
        }

        mostrarToast('exito', 'Categoría creada exitosamente.');
        cerrarModal('modal-crear-categoria');
        cargarCategorias();
    } catch (e) {
        mostrarToast('error', 'Error de conexión.');
    }
}

// --- Abrir modal editar categoría ---
function abrirModalEditarCategoria(id) {
    const cat = categoriasCache.find(c => c.id == id);
    if (!cat) {
        mostrarToast('error', 'Categoría no encontrada.');
        return;
    }
    document.getElementById('categoria-editar-id').value = cat.id;
    document.getElementById('categoria-editar-nombre').value = cat.nombre || '';
    document.getElementById('categoria-editar-descripcion').value = cat.descripcion || '';
    abrirModal('modal-editar-categoria');
}

// --- Actualizar (editar) categoría ---
async function actualizarCategoria(event) {
    event.preventDefault();
    const id = parseInt(document.getElementById('categoria-editar-id').value, 10);
    const nombre = document.getElementById('categoria-editar-nombre').value.trim();
    const descripcion = document.getElementById('categoria-editar-descripcion').value.trim();

    try {
        const resp = await apiFetch('admin_api.php?action=editar_categoria', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, nombre, descripcion })
        });

        if (resp.status === 401 || resp.status === 403) {
            window.location.href = 'login.php';
            return;
        }
        const data = await resp.json();

        if (!resp.ok || !data.success) {
            mostrarToast('error', data.error || 'No se pudo actualizar la categoría.');
            return;
        }

        mostrarToast('exito', 'Categoría actualizada exitosamente.');
        cerrarModal('modal-editar-categoria');
        cargarCategorias();
    } catch (e) {
        mostrarToast('error', 'Error de conexión.');
    }
}

// --- Eliminar categoría ---
async function eliminarCategoria(id) {
    const cat = categoriasCache.find(c => c.id == id);
    const nombreCat = cat ? cat.nombre : '';
    if (!confirm('¿Eliminar la categoría "' + nombreCat + '"?\nSi tiene productos asociados no se podrá eliminar.')) return;

    try {
        const resp = await apiFetch('admin_api.php?action=eliminar_categoria', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: parseInt(id, 10) })
        });

        if (resp.status === 401 || resp.status === 403) {
            window.location.href = 'login.php';
            return;
        }
        const data = await resp.json();

        if (!resp.ok || !data.success) {
            // 409 con productos asociados — el mensaje ya incluye el conteo
            mostrarToast('error', data.error || 'No se pudo eliminar la categoría.');
            return;
        }

        mostrarToast('exito', 'Categoría eliminada.');
        cargarCategorias();
    } catch (e) {
        mostrarToast('error', 'Error de conexión.');
    }
}

// =========================================================
// Utilidades de API y modales (reutilizables — Slice 3+)
// =========================================================

// fetch wrapper central: aquí se podría añadir credenciales/headers comunes.
async function apiFetch(url, options) {
    return fetch(url, options);
}

// Abrir cualquier modal por id
function abrirModal(modalId) {
    document.getElementById(modalId).classList.add('active');
    document.body.style.overflow = 'hidden';
}

// Cerrar modal por id
function cerrarModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
    // Solo restaurar scroll si no quedan otros modales abiertos
    const quedanAbiertos = document.querySelectorAll('.modal-overlay.active').length > 0;
    if (!quedanAbiertos) document.body.style.overflow = '';
}

// Cerrar modal con tecla Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const abiertos = document.querySelectorAll('.modal-overlay.active');
        if (abiertos.length > 0) {
            // Cierra el último abierto (top-most)
            abiertos[abiertos.length - 1].classList.remove('active');
            const quedanAbiertos = document.querySelectorAll('.modal-overlay.active').length > 0;
            if (!quedanAbiertos) document.body.style.overflow = '';
        }
    }
});

// Cerrar modal al hacer clic en el overlay (fuera del contenido)
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.classList.remove('active');
        const quedanAbiertos = document.querySelectorAll('.modal-overlay.active').length > 0;
        if (!quedanAbiertos) document.body.style.overflow = '';
    }
});
</script>

</body>
</html>