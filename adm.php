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
        <div class="tab-placeholder">
            <i class="fa-solid fa-users"></i>
            <p>Gestión de cajeros — próximamente</p>
        </div>
    </div>

    <!-- Pane: Categorías -->
    <div class="admin-pane" id="pane-categorias">
        <div class="pane-error hidden" id="categorias-error">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span id="categorias-error-text"></span>
        </div>
        <div class="tab-placeholder">
            <i class="fa-solid fa-tags"></i>
            <p>Gestión de categorías — próximamente</p>
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

// === Cajeros: placeholder (implements in Slice 2) ===
function cargarCajeros() {
    // Will be implemented in Slice 2
}
</script>

</body>
</html>