<?php
// cajero_api.php — JSON API para la vista del cajero
// Acciones: listar, cambiar_estado, crear_venta, listar_productos, buscar_qr, reclamar

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/conexion.php';

// --- Autenticación: solo cajero ---
// 401 = sin sesión; 403 = sesión válida pero rol equivocado
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'auth', 'message' => 'Debes iniciar sesión.']);
    exit;
}
if (($_SESSION['usuario_rol'] ?? '') !== 'cajero') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'forbidden', 'message' => 'Acceso restringido al rol cajero.']);
    exit;
}

$cajero_id = (int)$_SESSION['usuario_id'];
$action = $_GET['action'] ?? '';

// Mapa de transiciones válidas (solo avance)
$transitions = [
    'pendiente'      => 'en_preparacion',
    'en_preparacion' => 'listo',
    'listo'          => 'entregado',
];

// --- Router ---
switch ($action) {
    case 'listar':
        actionListar($pdo);
        break;

    case 'cambiar_estado':
        actionCambiarEstado($pdo, $cajero_id, $transitions);
        break;

    case 'crear_venta':
        actionCrearVenta($pdo, $cajero_id);
        break;

    case 'listar_productos':
        actionListarProductos($pdo);
        break;

    case 'buscar_qr':
        actionBuscarQR($pdo);
        break;

    case 'reclamar':
        actionReclamar($pdo, $cajero_id);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Acción no válida.']);
        break;
}

// -------------------------------------------------------
// GET ?action=listar — Pedidos activos agrupados por estado
// -------------------------------------------------------
function actionListar(PDO $pdo): void {
    $stmt = $pdo->prepare("
        SELECT o.id, o.codigo_qr, o.total, o.estado, o.fecha_creacion, o.tipo_pedido,
               u.usuario AS cliente_nombre
        FROM ordenes o
        LEFT JOIN usuarios u ON o.cliente_id = u.id
        WHERE o.estado IN ('pendiente', 'en_preparacion', 'listo')
        ORDER BY o.fecha_creacion ASC
    ");
    $stmt->execute();
    $ordenes = $stmt->fetchAll();

    // Adjuntar items de cada orden
    $stmtDetalle = $pdo->prepare("
        SELECT od.producto_id, od.cantidad, od.precio_unitario,
               p.nombre AS producto_nombre
        FROM orden_detalles od
        JOIN productos p ON od.producto_id = p.id
        WHERE od.orden_id = ?
    ");

    foreach ($ordenes as &$orden) {
        $stmtDetalle->execute([$orden['id']]);
        $orden['items'] = $stmtDetalle->fetchAll();

        // Cliente nombre para venta rápida
        if ($orden['tipo_pedido'] === 'venta_rapida') {
            $orden['cliente_nombre'] = 'Venta rápida';
        }

        // Tiempo transcurrido en minutos
        $diff = time() - strtotime($orden['fecha_creacion']);
        $orden['minutos'] = (int)floor($diff / 60);
    }
    unset($orden);

    echo json_encode(['success' => true, 'ordenes' => $ordenes]);
}

// -------------------------------------------------------
// POST ?action=cambiar_estado — Avanzar estado con lock optimista
// Body JSON: { orden_id, estado_nuevo }
// -------------------------------------------------------
function actionCambiarEstado(PDO $pdo, int $cajero_id, array $transitions): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Método no permitido.']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || empty($input['orden_id']) || empty($input['estado_nuevo'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Datos incompletos.']);
        return;
    }

    $orden_id = (int)$input['orden_id'];
    $estado_nuevo = $input['estado_nuevo'];

    // Validar que la transición sea permitida
    $estado_actual_valido = null;
    foreach ($transitions as $from => $to) {
        if ($to === $estado_nuevo) {
            $estado_actual_valido = $from;
            break;
        }
    }

    if ($estado_actual_valido === null) {
        // Verificar si el estado_nuevo existe en el ENUM pero no como destino válido
        $estados_permitidos = array_values($transitions);
        if (!in_array($estado_nuevo, $estados_permitidos)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Estado no válido.']);
            return;
        }
    }

    // Obtener estado actual para la transición esperada
    $from_estado = $estado_actual_valido;

    // Lock optimista: UPDATE ... WHERE id=? AND estado=?
    $stmt = $pdo->prepare("UPDATE ordenes SET estado = ?, cajero_id = ? WHERE id = ? AND estado = ?");
    $stmt->execute([$estado_nuevo, $cajero_id, $orden_id, $from_estado]);

    if ($stmt->rowCount() === 0) {
        // Verificar si la orden existe
        $stmtCheck = $pdo->prepare("SELECT estado FROM ordenes WHERE id = ?");
        $stmtCheck->execute([$orden_id]);
        $orden = $stmtCheck->fetch();

        if (!$orden) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Pedido no encontrado.']);
            return;
        }

        // La orden existe pero el estado no coincide (conflicto)
        http_response_code(409);
        echo json_encode(['success' => false, 'error' => 'El pedido ya fue actualizado.']);
        return;
    }

    echo json_encode(['success' => true]);
}

// -------------------------------------------------------
// POST ?action=crear_venta — Venta rápida (sin QR, sin cliente)
// Body JSON: { items: [{ producto_id, cantidad }] }
// -------------------------------------------------------
function actionCrearVenta(PDO $pdo, int $cajero_id): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Método no permitido.']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || empty($input['items']) || !is_array($input['items'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'El carrito está vacío.']);
        return;
    }

    try {
        $pdo->beginTransaction();

        $total = 0;
        $detalles = [];

        foreach ($input['items'] as $item) {
            $producto_id = (int)($item['producto_id'] ?? 0);
            $cantidad = (int)($item['cantidad'] ?? 0);

            if ($producto_id <= 0 || $cantidad <= 0) {
                $pdo->rollBack();
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Datos del carrito inválidos.']);
                exit;
            }

            $stmt = $pdo->prepare("SELECT id, nombre, precio, stock FROM productos WHERE id = ?");
            $stmt->execute([$producto_id]);
            $producto = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$producto) {
                $pdo->rollBack();
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Producto no encontrado.']);
                exit;
            }

            if ($producto['stock'] < $cantidad) {
                $pdo->rollBack();
                http_response_code(409);
                echo json_encode(['success' => false, 'error' => "{$producto['nombre']} sin stock suficiente. Quedan {$producto['stock']} unidades."]);
                exit;
            }

            $subtotal = $producto['precio'] * $cantidad;
            $total += $subtotal;
            $detalles[] = [
                'producto_id' => $producto['id'],
                'nombre' => $producto['nombre'],
                'cantidad' => $cantidad,
                'precio_unitario' => $producto['precio']
            ];
        }

        // Insertar cabecera: venta rápida, sin cliente, sin QR, estado pendiente
        $stmtOrden = $pdo->prepare("
            INSERT INTO ordenes (cliente_id, cajero_id, tipo_pedido, codigo_qr, total, estado, fecha_creacion)
            VALUES (NULL, ?, 'venta_rapida', NULL, ?, 'pendiente', NOW())
        ");
        $stmtOrden->execute([$cajero_id, $total]);
        $orden_id = (int)$pdo->lastInsertId();

        // Insertar detalles y descontar stock atómicamente
        $stmtDetalle = $pdo->prepare("INSERT INTO orden_detalles (orden_id, producto_id, cantidad, precio_unitario) VALUES (?, ?, ?, ?)");
        $stmtStock = $pdo->prepare("UPDATE productos SET stock = stock - ? WHERE id = ? AND stock >= ?");

        foreach ($detalles as $detalle) {
            $stmtDetalle->execute([$orden_id, $detalle['producto_id'], $detalle['cantidad'], $detalle['precio_unitario']]);

            $stmtStock->execute([$detalle['cantidad'], $detalle['producto_id'], $detalle['cantidad']]);
            if ($stmtStock->rowCount() === 0) {
                $pdo->rollBack();
                http_response_code(409);
                echo json_encode(['success' => false, 'error' => "{$detalle['nombre']} sin stock suficiente."]);
                exit;
            }
        }

        $pdo->commit();

        echo json_encode(['success' => true, 'orden_id' => $orden_id, 'total' => $total]);

    } catch (RuntimeException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Error interno del servidor.']);
    }
}

// -------------------------------------------------------
// GET ?action=listar_productos — Catálogo de productos con categoría
// -------------------------------------------------------
function actionListarProductos(PDO $pdo): void {
    $stmt = $pdo->query("
        SELECT p.id, p.nombre, p.precio, p.stock, p.imagen, c.id AS categoria_id, c.nombre AS categoria_nombre
        FROM productos p
        JOIN categorias c ON p.categoria_id = c.id
        ORDER BY c.nombre, p.nombre
    ");
    $productos = $stmt->fetchAll();

    // Lista de categorías únicas en orden de aparición
    $categorias = [];
    foreach ($productos as $p) {
        $found = false;
        foreach ($categorias as &$cat) {
            if ($cat['id'] === $p['categoria_id']) {
                $found = true;
                break;
            }
        }
        unset($cat);
        if (!$found) {
            $categorias[] = ['id' => (int)$p['categoria_id'], 'nombre' => $p['categoria_nombre']];
        }
    }

    echo json_encode(['success' => true, 'categorias' => $categorias, 'productos' => $productos]);
}

// -------------------------------------------------------
// POST ?action=buscar_qr — Buscar orden por código QR
// Body JSON: { codigo_qr }
// Devuelve los detalles de la orden (cliente, items, total, estado, fecha).
// 404 si no existe, 400 si ya fue entregado.
// -------------------------------------------------------
function actionBuscarQR(PDO $pdo): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Método no permitido.']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || empty($input['codigo_qr'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Código QR vacío.']);
        return;
    }

    $codigo = trim((string)$input['codigo_qr']);

    // Buscar la orden por código QR con JOIN del cliente
    $stmt = $pdo->prepare("
        SELECT o.id, o.codigo_qr, o.total, o.estado, o.fecha_creacion, o.tipo_pedido,
               u.usuario AS cliente_nombre
        FROM ordenes o
        LEFT JOIN usuarios u ON o.cliente_id = u.id
        WHERE o.codigo_qr = ?
        LIMIT 1
    ");
    $stmt->execute([$codigo]);
    $orden = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$orden) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Código QR no encontrado.']);
        return;
    }

    // Un pedido ya entregado no se puede reclamar de nuevo
    if ($orden['estado'] === 'entregado') {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'entregado',
            'message' => 'Este pedido ya fue entregado.',
            'estado' => $orden['estado']
        ]);
        return;
    }

    // Etiqueta legible de cliente para venta rápida
    if ($orden['tipo_pedido'] === 'venta_rapida') {
        $orden['cliente_nombre'] = 'Venta rápida';
    }

    // Adjuntar items (orden_detalles JOIN productos)
    $stmtItems = $pdo->prepare("
        SELECT od.producto_id, od.cantidad, od.precio_unitario,
               p.nombre AS producto_nombre
        FROM orden_detalles od
        JOIN productos p ON od.producto_id = p.id
        WHERE od.orden_id = ?
    ");
    $stmtItems->execute([$orden['id']]);
    $orden['items'] = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'orden' => $orden]);
}

// -------------------------------------------------------
// POST ?action=reclamar — Entregar pedido listo -> entregado
// Body JSON: { orden_id }
// Lock optimista: WHERE id=? AND estado='listo'
// 404 si no existe, 409 si ya fue entregado, 400 si no está listo.
// -------------------------------------------------------
function actionReclamar(PDO $pdo, int $cajero_id): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Método no permitido.']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || empty($input['orden_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Falta el ID del pedido.']);
        return;
    }

    $orden_id = (int)$input['orden_id'];

    // Lock optimista: solo transición listo -> entregado
    $stmt = $pdo->prepare("UPDATE ordenes SET estado = 'entregado', cajero_id = ? WHERE id = ? AND estado = 'listo'");
    $stmt->execute([$cajero_id, $orden_id]);

    if ($stmt->rowCount() === 0) {
        // Verificar si la orden existe y su estado actual
        $stmtCheck = $pdo->prepare("SELECT id, estado FROM ordenes WHERE id = ?");
        $stmtCheck->execute([$orden_id]);
        $orden = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if (!$orden) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Pedido no encontrado.']);
            return;
        }

        // Ya fue entregado por otro cajero (conflicto)
        if ($orden['estado'] === 'entregado') {
            http_response_code(409);
            echo json_encode([
                'success' => false,
                'error' => 'conflict',
                'message' => 'Este pedido ya fue entregado por otro cajero.'
            ]);
            return;
        }

        // Existe pero no está listo todavía
        $estadoLabel = [
            'pendiente'      => 'pendiente',
            'en_preparacion' => 'en preparación',
        ];
        $estado = $orden['estado'];
        $label = $estadoLabel[$estado] ?? $estado;
        http_response_code(400);
        echo json_encode([
            'success'  => false,
            'error'    => 'not_ready',
            'message'  => "Este pedido aún no está listo (estado: {$label}).",
            'estado'   => $estado
        ]);
        return;
    }

    echo json_encode(['success' => true, 'message' => "Pedido #{$orden_id} entregado."]);
}