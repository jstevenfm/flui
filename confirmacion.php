<?php
// confirmacion.php — Pantalla de confirmación con QR y resumen del pedido (rol: cliente)
require_once __DIR__ . '/auth.php';
checkRole('cliente');
require_once __DIR__ . '/conexion.php';

$orden_id = (int)($_GET['id'] ?? 0);
$orden = null;
$detalles = [];

if ($orden_id > 0) {
    // La orden debe pertenecer al cliente actual
    $stmt = $pdo->prepare("SELECT * FROM ordenes WHERE id = ? AND cliente_id = ?");
    $stmt->execute([$orden_id, $_SESSION['usuario_id']]);
    $orden = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($orden) {
        $stmt = $pdo->prepare("
            SELECT d.cantidad, d.precio_unitario, p.nombre
            FROM orden_detalles d
            JOIN productos p ON d.producto_id = p.id
            WHERE d.orden_id = ?
            ORDER BY p.nombre
        ");
        $stmt->execute([$orden_id]);
        $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

$nombre = $_SESSION['usuario_nombre'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flui — Confirmación</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="cliente.css">
    <style>
        .confirm-tarjeta {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 26px;
            max-width: 460px;
            margin: 0 auto;
            text-align: center;
            box-shadow: var(--shadow);
        }
        .confirm-icon { font-size: 3rem; color: var(--accent); margin-bottom: 10px; }
        .confirm-tarjeta h1 { font-size: 1.5rem; margin-bottom: 6px; }
        .confirm-tarjeta .sub { color: var(--text-dim); font-size: 0.9rem; margin-bottom: 20px; }
        #qrcode { display: inline-block; padding: 12px; background: #fff; border-radius: 12px; margin-bottom: 16px; }
        .codigo-qr {
            font-family: monospace;
            font-size: 1.4rem;
            font-weight: 700;
            letter-spacing: 2px;
            color: var(--accent);
            margin-bottom: 18px;
        }
        .resumen-tabla { width: 100%; border-collapse: collapse; margin-bottom: 16px; text-align: left; }
        .resumen-tabla th, .resumen-tabla td { padding: 8px 6px; border-bottom: 1px solid var(--border); font-size: 0.88rem; }
        .resumen-tabla th { color: var(--text-dim); font-size: 0.75rem; }
        .resumen-tabla td.r { text-align: right; }
        .resumen-total { display: flex; justify-content: space-between; font-weight: 700; font-size: 1.15rem; margin-bottom: 20px; }
        .resumen-total span:last-child { color: var(--accent); }
        .confirm-acciones { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
        .btn-link {
            background: var(--accent); color: #000; text-decoration: none;
            padding: 12px 18px; border-radius: var(--radius-sm); font-weight: 700; font-size: 0.85rem;
        }
        .btn-link.secundario { background: transparent; color: var(--text-white); border: 1px solid var(--border); }
        .estado-linea { margin-bottom: 18px; }
        .error-box { text-align: center; color: var(--text-dim); margin-top: 60px; }
        .error-box i { font-size: 3rem; color: var(--danger); margin-bottom: 12px; }
    </style>
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
        <a href="cliente.php">Catálogo</a>
        <a href="historial.php">Mis pedidos</a>
    </nav>

    <div class="page-container">
        <?php if (!$orden): ?>
            <div class="error-box">
                <i class="fa-solid fa-circle-exclamation"></i>
                <h2>Pedido no encontrado</h2>
                <p>No encontramos este pedido o no tienes permiso para verlo.</p>
                <p style="margin-top:18px;"><a href="cliente.php" class="btn-link">Volver al catálogo</a></p>
            </div>
        <?php else: ?>
            <div class="confirm-tarjeta">
                <div class="confirm-icon"><i class="fa-solid fa-circle-check"></i></div>
                <h1>¡Pedido confirmado!</h1>
                <p class="sub">Muestra este QR en el mostrador para reclamar tu pedido.</p>

                <div id="qrcode"></div>
                <div class="codigo-qr" id="codigo-qr-texto"><?php echo htmlspecialchars($orden['codigo_qr']); ?></div>

                <div class="estado-linea">
                    Estado:
                    <span class="estado-badge estado-<?php echo htmlspecialchars($orden['estado']); ?>"><?php echo htmlspecialchars($orden['estado']); ?></span>
                </div>

                <table class="resumen-tabla">
                    <thead>
                        <tr><th>Producto</th><th class="r">Cant.</th><th class="r">Precio</th><th class="r">Subtotal</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($detalles as $d): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($d['nombre']); ?></td>
                                <td class="r"><?php echo (int)$d['cantidad']; ?></td>
                                <td class="r">$<?php echo number_format($d['precio_unitario'], 0, ',', '.'); ?></td>
                                <td class="r">$<?php echo number_format($d['precio_unitario'] * $d['cantidad'], 0, ',', '.'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="resumen-total">
                    <span>Total</span>
                    <span>$<?php echo number_format($orden['total'], 0, ',', '.'); ?></span>
                </div>

                <div class="confirm-acciones">
                    <a href="historial.php" class="btn-link secundario"><i class="fa-solid fa-clock-rotate-left"></i> Ver mis pedidos</a>
                    <a href="cliente.php" class="btn-link"><i class="fa-solid fa-plus"></i> Nuevo pedido</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($orden): ?>
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <script>
        new QRCode(document.getElementById('qrcode'), {
            text: <?php echo json_encode($orden['codigo_qr']); ?>,
            width: 200,
            height: 200
        });

        // Auto-refresh del estado cada 30s
        async function refrescarEstado() {
            try {
                const resp = await fetch('estado_orden.php?id=' + <?php echo (int)$orden_id; ?>);
                if (!resp.ok) return;
                const data = await resp.json();
                if (data && data.estado) {
                    const badge = document.querySelector('.estado-badge');
                    badge.className = 'estado-badge estado-' + data.estado;
                    badge.textContent = data.estado;
                }
            } catch (e) { /* silencioso */ }
        }
        setInterval(refrescarEstado, 30000);
    </script>
    <?php endif; ?>

</body>
</html>