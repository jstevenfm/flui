<?php
// cliente.php — Catálogo de productos y carrito de compras (rol: cliente)
require_once __DIR__ . '/auth.php';
checkRole('cliente');
require_once __DIR__ . '/conexion.php';

// Cargar productos con su categoría, agrupados por categoría
$stmt = $pdo->query("
    SELECT p.id, p.nombre, p.precio, p.stock, p.imagen, c.nombre AS categoria_nombre
    FROM productos p
    JOIN categorias c ON p.categoria_id = c.id
    ORDER BY c.nombre, p.nombre
");
$productos = $stmt->fetchAll();

// Lista de categorías (orden de aparición) y productos agrupados
$categorias = [];
$productos_por_categoria = [];
foreach ($productos as $p) {
    $cat = $p['categoria_nombre'];
    if (!in_array($cat, $categorias, true)) {
        $categorias[] = $cat;
        $productos_por_categoria[$cat] = [];
    }
    $productos_por_categoria[$cat][] = $p;
}
$nombre = $_SESSION['usuario_nombre'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flui — Catálogo</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="cliente.css">
</head>
<body>

    <header class="cliente-header">
        <div class="cliente-brand">
            <span class="brand-icon"><i class="fa-solid fa-mug-hot"></i></span>
            <h1>Flui</h1>
        </div>
        <div class="cliente-user">
            <span>Hola, <strong><?php echo htmlspecialchars($nombre); ?></strong></span>
            <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Cerrar sesión</a>
        </div>
    </header>

    <nav class="cliente-nav">
        <a href="cliente.php" class="active">Catálogo</a>
        <a href="historial.php">Mis pedidos</a>
    </nav>

    <div class="search-wrap">
        <div class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="busqueda" placeholder="Buscar producto...">
        </div>
    </div>

    <div class="categoria-tabs" id="categoria-tabs">
        <button class="active" data-categoria="todas">Todas</button>
        <?php foreach ($categorias as $cat): ?>
            <button data-categoria="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></button>
        <?php endforeach; ?>
    </div>

    <div class="productos-grid" id="catalogo">
        <?php foreach ($productos as $p): ?>
            <?php
                $tieneImagen = !empty($p['imagen']);
                $stock = (int)$p['stock'];
                if ($stock > 10) { $claseStock = 'stock-disponible'; $textoStock = 'Disponible'; }
                elseif ($stock > 0) { $claseStock = 'stock-bajo'; $textoStock = 'Pocas unidades'; }
                else { $claseStock = 'stock-agotado'; $textoStock = 'Agotado'; }
            ?>
            <article class="producto-card" data-categoria="<?php echo htmlspecialchars($p['categoria_nombre']); ?>" data-nombre="<?php echo htmlspecialchars(strtolower($p['nombre'])); ?>">
                <div class="producto-imagen">
                    <?php if ($tieneImagen): ?>
                        <img src="img/<?php echo htmlspecialchars($p['imagen']); ?>" alt="<?php echo htmlspecialchars($p['nombre']); ?>" onerror="this.style.display='none'; this.parentElement.querySelector('.placeholder').style.display='flex';">
                        <span class="placeholder" style="display:none;"><i class="fa-solid fa-mug-saucer"></i></span>
                    <?php else: ?>
                        <span class="placeholder"><i class="fa-solid fa-mug-saucer"></i></span>
                    <?php endif; ?>
                </div>
                <div class="producto-body">
                    <div class="producto-nombre"><?php echo htmlspecialchars($p['nombre']); ?></div>
                    <div class="producto-precio">$<?php echo number_format($p['precio'], 0, ',', '.'); ?></div>
                    <span class="producto-stock <?php echo $claseStock; ?>"><?php echo $textoStock; ?></span>
                    <div class="producto-actions">
                        <button class="btn-agregar" data-id="<?php echo $p['id']; ?>" data-nombre="<?php echo htmlspecialchars($p['nombre']); ?>" data-precio="<?php echo $p['precio']; ?>" data-stock="<?php echo $stock; ?>" <?php echo $stock === 0 ? 'disabled' : ''; ?>>
                            <?php if ($stock === 0): ?>
                                <i class="fa-solid fa-ban"></i> Agotado
                            <?php else: ?>
                                <i class="fa-solid fa-plus"></i> Agregar
                            <?php endif; ?>
                        </button>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <button id="cart-toggle" class="cart-fab"><i class="fas fa-shopping-cart"></i> <span id="cart-count">0</span></button>

    <div class="cart-overlay" id="cart-overlay"></div>
    <aside class="cart-sidebar" id="cart-sidebar">
        <div class="cart-sidebar-header">
            <h2><i class="fa-solid fa-cart-shopping"></i> Tu carrito</h2>
            <button class="cart-close" id="cart-close">&times;</button>
        </div>
        <div class="cart-items" id="cart-items"></div>
        <div class="cart-sidebar-footer">
            <div class="cart-mensaje" id="cart-mensaje"></div>
            <div class="cart-total"><span>Total</span><span id="cart-total">$0</span></div>
            <div class="cart-actions">
                <button class="btn-vaciar" id="btn-vaciar"><i class="fa-solid fa-trash"></i> Vaciar</button>
                <button class="btn-confirmar" id="btn-confirmar"><i class="fa-solid fa-check"></i> Confirmar Pedido</button>
            </div>
        </div>
    </aside>

<script>
// Catálogo serializado desde PHP para el JS
const CATALOGO = <?php echo json_encode(array_map(function($p) {
    return ['id' => (int)$p['id'], 'nombre' => $p['nombre'], 'precio' => (float)$p['precio'], 'stock' => (int)$p['stock']];
}, $productos)); ?>;

// ---------- Estado del carrito en localStorage ----------
let cart = JSON.parse(localStorage.getItem('flui_cart') || '[]');

function guardarCart() {
    localStorage.setItem('flui_cart', JSON.stringify(cart));
}

function addToCart(producto) {
    const existente = cart.find(i => i.producto_id === producto.id);
    if (existente) {
        if (existente.cantidad >= 20) return;   // máximo 20 unidades
        existente.cantidad += 1;
    } else {
        if (producto.stock === 0) return;
        cart.push({ producto_id: producto.id, nombre: producto.nombre, precio: producto.precio, cantidad: 1 });
    }
    guardarCart();
    renderCart();
}

function removeFromCart(index) {
    cart.splice(index, 1);
    guardarCart();
    renderCart();
}

function updateQuantity(index, delta) {
    cart[index].cantidad += delta;
    if (cart[index].cantidad <= 0) {
        removeFromCart(index);
        return;
    }
    if (cart[index].cantidad > 20) cart[index].cantidad = 20;
    guardarCart();
    renderCart();
}

function vaciarCarrito() {
    cart = [];
    localStorage.removeItem('flui_cart');
    renderCart();
}

function renderCart() {
    const cont = document.getElementById('cart-items');
    const totalEl = document.getElementById('cart-total');
    const countEl = document.getElementById('cart-count');
    let total = 0;
    let count = 0;

    if (cart.length === 0) {
        cont.innerHTML = '<div class="cart-vacio"><i class="fa-solid fa-basket-shopping"></i><p>El carrito está vacío</p></div>';
    } else {
        cont.innerHTML = cart.map((item, idx) => {
            const subtotal = item.precio * item.cantidad;
            total += subtotal;
            count += item.cantidad;
            return `
                <div class="cart-item">
                    <div class="cart-item-top">
                        <span class="cart-item-nombre">${escapeHtml(item.nombre)}</span>
                        <span class="cart-item-precio">$${formato(item.precio)}</span>
                    </div>
                    <div class="cart-item-controls">
                        <button class="qty-btn" onclick="updateQuantity(${idx}, -1)">&minus;</button>
                        <span class="cart-item-qty">${item.cantidad}</span>
                        <button class="qty-btn" onclick="updateQuantity(${idx}, 1)">+</button>
                        <span class="cart-item-subtotal">Subtotal: $${formato(subtotal)}</span>
                        <button class="cart-item-remove" onclick="removeFromCart(${idx})" title="Quitar"><i class="fa-solid fa-xmark"></i></button>
                    </div>
                </div>`;
        }).join('');
    }
    totalEl.textContent = '$' + formato(total);
    countEl.textContent = count;
}

function toggleCart(abrir) {
    const sidebar = document.getElementById('cart-sidebar');
    const overlay = document.getElementById('cart-overlay');
    const activo = (abrir === undefined) ? !sidebar.classList.contains('active') : abrir;
    sidebar.classList.toggle('active', activo);
    overlay.classList.toggle('active', activo);
    // limpiar mensajes al abrir
    document.getElementById('cart-mensaje').className = 'cart-mensaje';
    document.getElementById('cart-mensaje').textContent = '';
}

function filtrarCatalogo() {
    const texto = document.getElementById('busqueda').value.trim().toLowerCase();
    const tabActiva = document.querySelector('.categoria-tabs button.active');
    const cat = tabActiva ? tabActiva.dataset.categoria : 'todas';
    document.querySelectorAll('.producto-card').forEach(card => {
        const coincideTexto = texto === '' || card.dataset.nombre.includes(texto);
        const coincideCat = cat === 'todas' || card.dataset.categoria === cat;
        card.style.display = (coincideTexto && coincideCat) ? '' : 'none';
    });
}

async function confirmarPedido() {
    const msj = document.getElementById('cart-mensaje');
    const btn = document.getElementById('btn-confirmar');
    if (cart.length === 0) {
        msj.className = 'cart-mensaje error';
        msj.textContent = 'El carrito está vacío.';
        return;
    }
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Enviando...';
    msj.className = 'cart-mensaje';
    try {
        const resp = await fetch('crear_orden.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ items: cart.map(i => ({ producto_id: i.producto_id, cantidad: i.cantidad })) })
        });
        const data = await resp.json();
        if (data.success) {
            vaciarCarrito();
            window.location.href = 'confirmacion.php?id=' + data.orden_id;
        } else {
            msj.className = 'cart-mensaje error';
            msj.textContent = data.error || 'No se pudo crear el pedido.';
        }
    } catch (e) {
        msj.className = 'cart-mensaje error';
        msj.textContent = 'Error de conexión. Intenta de nuevo.';
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-check"></i> Confirmar Pedido';
    }
}

// ---------- Utilidades ----------
function formato(n) { return Number(n).toLocaleString('es-CO'); }
function escapeHtml(s) {
    const d = document.createElement('div'); d.textContent = s; return d.innerHTML;
}

// ---------- Listeners ----------
document.getElementById('categoria-tabs').addEventListener('click', e => {
    if (e.target.tagName !== 'BUTTON') return;
    document.querySelectorAll('.categoria-tabs button').forEach(b => b.classList.remove('active'));
    e.target.classList.add('active');
    filtrarCatalogo();
});
document.getElementById('busqueda').addEventListener('input', filtrarCatalogo);
document.getElementById('cart-toggle').addEventListener('click', () => toggleCart(true));
document.getElementById('cart-close').addEventListener('click', () => toggleCart(false));
document.getElementById('cart-overlay').addEventListener('click', () => toggleCart(false));
document.getElementById('btn-vaciar').addEventListener('click', vaciarCarrito);
document.getElementById('btn-confirmar').addEventListener('click', confirmarPedido);

// Agregar al carrito desde cualquier botón del catálogo
document.getElementById('catalogo').addEventListener('click', e => {
    const btn = e.target.closest('.btn-agregar');
    if (!btn || btn.disabled) return;
    addToCart({
        id: parseInt(btn.dataset.id, 10),
        nombre: btn.dataset.nombre,
        precio: parseFloat(btn.dataset.precio),
        stock: parseInt(btn.dataset.stock, 10)
    });
    // mini feedback visual
    const count = document.getElementById('cart-count');
    count.style.transform = 'scale(1.4)';
    setTimeout(() => count.style.transform = '', 150);
});

// Render inicial
renderCart();
</script>
</body>
</html>