<?php
// adm.php — Panel de administración (solo admin)
require_once 'auth.php';
checkRole('admin');
require 'conexion.php';

$mensaje_cajero = '';

// Procesar formularios de gestión de cajeros
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'crear') {
        $usuario_cajero = trim($_POST['usuario'] ?? '');
        $email_cajero = strtolower(trim($_POST['email'] ?? ''));
        $password_cajero = $_POST['password'] ?? '';

        if (empty($usuario_cajero) || empty($email_cajero) || empty($password_cajero)) {
            $mensaje_cajero = "<div class='alert alert-danger'>Por favor, rellena todos los campos.</div>";
        } elseif (strlen($password_cajero) < 6) {
            $mensaje_cajero = "<div class='alert alert-danger'>La contraseña debe tener al menos 6 caracteres.</div>";
        } else {
            $password_hash = password_hash($password_cajero, PASSWORD_BCRYPT);
            try {
                $stmt = $pdo->prepare("INSERT INTO usuarios (usuario, email, password, rol) VALUES (?, ?, ?, 'cajero')");
                $stmt->execute([$usuario_cajero, $email_cajero, $password_hash]);
                $mensaje_cajero = "<div class='alert alert-success'>Cajero creado exitosamente.</div>";
            } catch (\PDOException $e) {
                if ($e->getCode() == 23000) {
                    $mensaje_cajero = "<div class='alert alert-danger'>El correo electrónico ya está registrado.</div>";
                } else {
                    $mensaje_cajero = "<div class='alert alert-danger'>Error al crear el cajero. Intente más tarde.</div>";
                }
            }
        }
    } elseif ($accion === 'desactivar') {
        $id_cajero = (int)($_POST['id_cajero'] ?? 0);
        if ($id_cajero > 0) {
            $stmt = $pdo->prepare("UPDATE usuarios SET activo = 0 WHERE id = ? AND rol = 'cajero'");
            $stmt->execute([$id_cajero]);
            $mensaje_cajero = "<div class='alert alert-success'>Cajero desactivado exitosamente.</div>";
        }
    }
}

// Obtener lista de cajeros
$cajeros = $pdo->query("SELECT id, usuario, email, activo FROM usuarios WHERE rol = 'cajero' ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flui POS - Dashboard</title>
    <link rel="stylesheet" href="adm.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .alert { padding: 10px 15px; margin-bottom: 15px; border-radius: 8px; font-size: 14px; }
        .alert-danger { background: #f8d7da; color: #721c24; }
        .alert-success { background: #d4edda; color: #155724; }
        .cajero-section { background: var(--bg-card); border-radius: 20px; padding: 20px; margin-bottom: 30px; border: 1px solid var(--border); }
        .cajero-section h3 { margin-bottom: 15px; }
        .cajero-form { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 20px; align-items: flex-end; }
        .cajero-form .field { display: flex; flex-direction: column; gap: 5px; }
        .cajero-form .field label { color: var(--text-dim); font-size: 0.8rem; }
        .cajero-form .field input { background: var(--bg-dark); border: 1px solid var(--border); color: white; padding: 10px; border-radius: 8px; font-size: 0.9rem; width: 200px; }
        .btn-crear { background: var(--accent); color: black; border: none; padding: 10px 20px; border-radius: 8px; font-weight: bold; cursor: pointer; }
        .btn-desactivar { background: #e74c3c; color: white; border: none; padding: 6px 14px; border-radius: 6px; cursor: pointer; font-size: 0.8rem; }
        .btn-desactivar:hover { background: #c0392b; }
        .estado-activo { color: var(--accent); font-size: 0.85rem; }
        .estado-inactivo { color: #e74c3c; font-size: 0.85rem; }
    </style>
</head>
<body>

    <div class="app-layout">
        <aside class="sidebar">
            <div class="logo">
                <div class="logo-icon"><i class="fa-solid fa-cash-register"></i></div>
                <div>
                    <h3>Flui POS</h3>
                    <p>Admin Dashboard</p>
                </div>
            </div>
            <nav class="side-nav">
                <a href="#" class="active"><i class="fa-solid fa-table-cells-large"></i> Dashboard</a>
                <a href="#"><i class="fa-solid fa-receipt"></i> Transactions</a>
                <a href="#"><i class="fa-solid fa-cart-shopping"></i> Orders</a>
                <a href="#cajeros"><i class="fa-solid fa-users"></i> Cajeros</a>
                <a href="#"><i class="fa-solid fa-box"></i> Inventory</a>
                <a href="#"><i class="fa-solid fa-chart-line"></i> Reports</a>
            </nav>
            <div class="sidebar-footer">
                <a href="#"><i class="fa-solid fa-gear"></i> Settings</a>
                <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Cerrar Sesión</a>
                <div class="user-profile">
                    <img src="https://via.placeholder.com/35" alt="User">
                    <div>
                        <h4>Alex Chen</h4>
                        <p>Store Manager</p>
                    </div>
                </div>
            </div>
        </aside>

        <main class="main-content">
            <header class="top-header">
                <div class="header-search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" placeholder="Search data...">
                </div>
                <div class="header-actions">
                    <button class="icon-btn"><i class="fa-regular fa-bell"></i></button>
                    <button class="icon-btn"><i class="fa-regular fa-calendar"></i></button>
                    <div class="mobile-avatar">
                        <img src="https://via.placeholder.com/35" alt="User">
                    </div>
                </div>
            </header>

            <section class="content-body">
                <div class="welcome-row">
                    <h2>Dashboard Overview</h2>
                    <div class="action-buttons">
                        <button class="btn-secondary">Export CSV</button>
                        <button class="btn-primary">New Order</button>
                    </div>
                </div>

                <!-- Sección: Gestionar Cajeros -->
                <div class="cajero-section" id="cajeros">
                    <h3><i class="fa-solid fa-users" style="color: var(--accent);"></i> Gestionar Cajeros</h3>
                    <?php echo $mensaje_cajero; ?>
                    <form method="POST" class="cajero-form">
                        <input type="hidden" name="accion" value="crear">
                        <div class="field">
                            <label>Nombre de usuario</label>
                            <input type="text" name="usuario" placeholder="Ej: cajero1" required>
                        </div>
                        <div class="field">
                            <label>Correo electrónico</label>
                            <input type="email" name="email" placeholder="cajero@flui.com" required>
                        </div>
                        <div class="field">
                            <label>Contraseña</label>
                            <input type="password" name="password" placeholder="Mínimo 6 caracteres" required minlength="6">
                        </div>
                        <button type="submit" class="btn-crear"><i class="fa-solid fa-plus"></i> Crear Cajero</button>
                    </form>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>USUARIO</th>
                                    <th>CORREO</th>
                                    <th>ESTADO</th>
                                    <th>ACCIÓN</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($cajeros)): ?>
                                <tr><td colspan="5" style="text-align:center;color:var(--text-dim);">No hay cajeros registrados.</td></tr>
                                <?php else: ?>
                                <?php foreach ($cajeros as $c): ?>
                                <tr>
                                    <td>#<?php echo $c['id']; ?></td>
                                    <td><?php echo htmlspecialchars($c['usuario']); ?></td>
                                    <td><?php echo htmlspecialchars($c['email']); ?></td>
                                    <td>
                                        <?php if ($c['activo']): ?>
                                        <span class="estado-activo">Activo</span>
                                        <?php else: ?>
                                        <span class="estado-inactivo">Inactivo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($c['activo']): ?>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('¿Desactivar este cajero?');">
                                            <input type="hidden" name="accion" value="desactivar">
                                            <input type="hidden" name="id_cajero" value="<?php echo $c['id']; ?>">
                                            <button type="submit" class="btn-desactivar">Desactivar</button>
                                        </form>
                                        <?php else: ?>
                                        <span style="color:var(--text-dim);font-size:0.8rem;">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="card-top">
                            <span class="card-icon green-bg"><i class="fa-solid fa-wallet"></i></span>
                            <span class="trend positive">↗ 12.5%</span>
                        </div>
                        <p>Total Revenue</p>
                        <h3>$42,500.00</h3>
                    </div>
                    <div class="stat-card">
                        <div class="card-top">
                            <span class="card-icon blue-bg"><i class="fa-solid fa-file-invoice"></i></span>
                            <span class="trend negative">↘ 2.4%</span>
                        </div>
                        <p>Transactions</p>
                        <h3>1,284</h3>
                    </div>
                    <div class="stat-card">
                        <div class="card-top">
                            <span class="card-icon orange-bg"><i class="fa-solid fa-tag"></i></span>
                            <span class="trend positive">↗ 5.1%</span>
                        </div>
                        <p>Avg Ticket</p>
                        <h3>$33.10</h3>
                    </div>
                    <div class="stat-card">
                        <div class="card-top">
                            <span class="card-icon purple-bg"><i class="fa-solid fa-user-group"></i></span>
                            <span class="trend neutral">0%</span>
                        </div>
                        <p>Staff Active</p>
                        <h3>12</h3>
                    </div>
                </div>

                <div class="orders-container">
                    <div class="orders-header">
                        <h3>Recent Orders</h3>
                        <a href="#">View All</a>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>ORDER ID</th>
                                    <th>CUSTOMER</th>
                                    <th>TIME</th>
                                    <th>AMOUNT</th>
                                    <th>STATUS</th>
                                    <th>ACTION</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>#ORD-7721</td>
                                    <td>James Smith</td>
                                    <td>10:45 AM</td>
                                    <td>$54.20</td>
                                    <td><span class="status completed">Completed</span></td>
                                    <td><i class="fa-solid fa-ellipsis-vertical"></i></td>
                                </tr>
                                </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </main>

        <nav class="mobile-nav">
            <a href="#" class="active"><i class="fa-solid fa-house"></i></a>
            <a href="#"><i class="fa-solid fa-clock-rotate-left"></i></a>
            <a href="#"><i class="fa-solid fa-box-archive"></i></a>
            <a href="#"><i class="fa-solid fa-ellipsis"></i></a>
        </nav>
    </div>
<div id="new-order-modal" class="modal-overlay">
    <div class="modal-container">
        
        <header class="modal-header">
            <div class="modal-title">
                <i class="fa-solid fa-cart-plus"></i>
                <div>
                    <h3>Nueva Orden</h3>
                    <p id="order-type-label">Modo: Venta Rápida</p>
                </div>
            </div>
            <button class="close-modal-btn" id="close-order-modal">&times;</button>
        </header>

        <div class="modal-body">
            
            <div class="catalog-column">
                <div class="search-box">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="product-search" placeholder="Buscar producto por nombre o código...">
                </div>
                
                <div class="products-grid" id="products-grid">
                    <div class="product-item" data-id="1">
                        <div class="product-info">
                            <h4>Hamburguesa Flui Double</h4>
                            <span class="product-stock">Stock: 50 u.</span>
                        </div>
                        <div class="product-footer">
                            <span class="product-price">$12.50</span>
                            <button class="add-product-btn"><i class="fa-solid fa-plus"></i></button>
                        </div>
                    </div>
                    <div class="product-item" data-id="2">
                        <div class="product-info">
                            <h4>Papas Fritas Medianas</h4>
                            <span class="product-stock">Stock: 100 u.</span>
                        </div>
                        <div class="product-footer">
                            <span class="product-price">$4.00</span>
                            <button class="add-product-btn"><i class="fa-solid fa-plus"></i></button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="cart-column">
                <h4>Resumen del Pedido</h4>
                
                <div class="cart-items-container" id="cart-items-container">
                    <div class="empty-cart-notice">
                        <i class="fa-solid fa-basket-shopping"></i>
                        <p>El carrito está vacío</p>
                    </div>
                </div>

                <div class="cart-summary">
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span id="summary-subtotal">$0.00</span>
                    </div>
                    <div class="summary-row total">
                        <span>Total</span>
                        <span id="summary-total">$0.00</span>
                    </div>
                    <button class="btn-checkout" id="btn-submit-order">
                        <i class="fa-solid fa-cash-register"></i> Completar Venta
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    // Seleccionar los elementos del DOM
    const openModalBtn = document.querySelector('.btn-primary'); // Tu botón "New Order"
    const closeModalBtn = document.getElementById('close-order-modal');
    const modalOverlay = document.getElementById('new-order-modal');

    // Función para abrir el modal
    if (openModalBtn && modalOverlay) {
        openModalBtn.addEventListener('click', (e) => {
            e.preventDefault();
            modalOverlay.classList.add('active');
        });
    }

    // Función para cerrar el modal con la "X"
    if (closeModalBtn && modalOverlay) {
        closeModalBtn.addEventListener('click', () => {
            modalOverlay.classList.remove('active');
        });
    }

    // Función para cerrar el modal si se hace clic en el fondo oscurecido
    if (modalOverlay) {
        modalOverlay.addEventListener('click', (e) => {
            // Si el clic fue en el fondo (.modal-overlay) y no dentro de la tarjeta
            if (e.target === modalOverlay) {
                modalOverlay.classList.remove('active');
            }
        });
    }
</script>

</body>

</html>