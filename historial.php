<?php
// historial.php — Historial de pedidos del cliente (rol: cliente)
require_once __DIR__ . '/auth.php';
checkRole('cliente');
require_once __DIR__ . '/conexion.php';

// Órdenes del cliente, recientes primero
$stmt = $pdo->prepare("
    SELECT o.id, o.codigo_qr, o.total, o.estado, o.fecha_creacion,
           (SELECT COUNT(*) FROM orden_detalles d WHERE d.orden_id = o.id) AS num_items
    FROM ordenes o
    WHERE o.cliente_id = ?
    ORDER BY o.fecha_creacion DESC
");
$stmt->execute([$_SESSION['usuario_id']]);
$ordenes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Mapa de detalles por orden (cargado en una sola consulta para la expansión JS)
$detalles_por_orden = [];
if (!empty($ordenes)) {
    $ids = array_column($ordenes, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("
        SELECT d.orden_id, d.cantidad, d.precio_unitario, p.nombre
        FROM orden_detalles d
        JOIN productos p ON d.producto_id = p.id
        WHERE d.orden_id IN ($placeholders)
        ORDER BY d.orden_id, p.nombre
    ");
    $stmt->execute($ids);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $d) {
        $detalles_por_orden[$d['orden_id']][] = $d;
    }
}

$nombre = $_SESSION['usuario_nombre'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flui — Mis pedidos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="cliente.css">
    <style>
        .orden-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            margin-bottom: 14px;
            overflow: hidden;
            transition: border 0.2s;
        }
        .orden-card:hover { border-color: var(--accent); }
        .orden-resumen {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 16px 18px;
            cursor: pointer;
        }
        .orden-info { display: flex; flex-direction: column; gap: 4px; }
        .orden-numero { font-weight: 700; font-size: 0.95rem; }
        .orden-fecha { color: var(--text-dim); font-size: 0.78rem; }
        .orden-items { color: var(--text-dim); font-size: 0.78rem; }
        .orden-derecha { display: flex; align-items: center; gap: 12px; }
        .orden-total { font-weight: 700; color: var(--accent); font-size: 1.05rem; }
        .orden-toggle { color: var(--text-dim); transition: transform 0.2s; }
        .orden-card.expandida .orden-toggle { transform: rotate(180deg); }

        .orden-detalles {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
            border-top: 1px solid transparent;
        }
        .orden-card.expandida .orden-detalles {
            max-height: 400px;
            border-top-color: var(--border);
        }
        .orden-detalles-tabla { width: 100%; border-collapse: collapse; padding: 0 18px; }
        .orden-detalles-tabla th, .orden-detalles-tabla td {
            padding: 8px 18px; font-size: 0.85rem; border-bottom: 1px solid var(--border);
        }
        .orden-detalles-tabla th { color: var(--text-dim); font-size: 0.72rem; text-align: left; }
        .orden-detalles-tabla td.r { text-align: right; }
        .orden-codigo { padding: 12px 18px; color: var(--text-dim); font-family: monospace; font-size: 0.85rem; }
        .orden-acciones { padding: 0 18px 16px; }
        .orden-acciones a { color: var(--accent); text-decoration: none; font-size: 0.82rem; }
        .orden-acciones a:hover { text-decoration: underline; }

        .vacio {
            text-align: center; color: var(--text-dim); margin-top: 80px;
        }
        .vacio i { font-size: 3rem; opacity: 0.5; display: block; margin-bottom: 14px; }
        .vacio a { display: inline-block; margin-top: 20px; }
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
        <a href="historial.php" class="active">Mis pedidos</a>
    </nav>

    <div class="page-container">
        <h2 class="page-title"><i class="fa-solid fa-clock-rotate-left"></i> Mis pedidos</h2>

        <?php if (empty($ordenes)): ?>
            <div class="vacio">
                <i class="fa-solid fa-receipt"></i>
                <p>No tienes pedidos todavía.</p>
                <a href="cliente.php" class="btn-link">Volver al catálogo</a>
            </div>
        <?php else: ?>
            <?php foreach ($ordenes as $o): ?>
                <?php
                    $fecha = (new DateTime($o['fecha_creacion']))->format('d/m/Y H:i');
                    $detalles = $detalles_por_orden[$o['id']] ?? [];
                ?>
                <div class="orden-card" data-id="<?php echo (int)$o['id']; ?>">
                    <div class="orden-resumen" onclick="toggleOrden(this)">
                        <div class="orden-info">
                            <span class="orden-numero">Pedido #<?php echo (int)$o['id']; ?></span>
                            <span class="orden-fecha"><i class="fa-regular fa-calendar"></i> <?php echo $fecha; ?></span>
                            <span class="orden-items"><?php echo (int)$o['num_items']; ?> producto(s)</span>
                        </div>
                        <div class="orden-derecha">
                            <span class="orden-total">$<?php echo number_format($o['total'], 0, ',', '.'); ?></span>
                            <span class="estado-badge estado-<?php echo htmlspecialchars($o['estado']); ?>"><?php echo htmlspecialchars($o['estado']); ?></span>
                            <i class="fa-solid fa-chevron-down orden-toggle"></i>
                        </div>
                    </div>
                    <div class="orden-detalles">
                        <div class="orden-codigo">Código QR: <strong><?php echo htmlspecialchars($o['codigo_qr']); ?></strong></div>
                        <table class="orden-detalles-tabla">
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
                        <div class="orden-acciones">
                            <a href="confirmacion.php?id=<?php echo (int)$o['id']; ?>"><i class="fa-solid fa-qrcode"></i> Ver QR y confirmación</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <p style="margin-top:20px;"><a href="cliente.php" class="btn-link"><i class="fa-solid fa-arrow-left"></i> Volver al catálogo</a></p>
        <?php endif; ?>
    </div>

<script>
// Expandir/contraer los detalles de una orden
function toggleOrden(encabezado) {
    const card = encabezado.parentElement;
    card.classList.toggle('expandida');
}
</script>
</body>
</html>