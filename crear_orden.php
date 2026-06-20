<?php
// crear_orden.php — Endpoint JSON: crea una orden remota del cliente (con QR)
header('Content-Type: application/json');

// session_start() manual en vez de checkRole() porque este es un endpoint JSON.
// checkRole() haría header('Location: login.php') y corrompería la respuesta JSON.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/conexion.php';

// Validar sesión y rol cliente
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'cliente') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Debes iniciar sesión.']);
    exit;
}

// Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido.']);
    exit;
}

// Leer cuerpo JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['items'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'El carrito está vacío.']);
    exit;
}

// Generar código QR único con reintentos
function generarCodigoQR(PDO $pdo, int $maxAttempts = 5): string {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    for ($i = 0; $i < $maxAttempts; $i++) {
        $code = 'FLUI-' . substr(str_shuffle($chars), 0, 6);
        $stmt = $pdo->prepare("SELECT id FROM ordenes WHERE codigo_qr = ?");
        $stmt->execute([$code]);
        if ($stmt->rowCount() === 0) return $code;
    }
    throw new RuntimeException("No se pudo generar un código QR único.");
}

try {
    $pdo->beginTransaction();

    $total = 0;
    $detalles = [];

    // Validar cada item contra stock y existencia
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
            http_response_code(409);
            echo json_encode(['success' => false, 'error' => 'Producto no encontrado.']);
            exit;
        }

        if ($producto['stock'] < $cantidad) {
            $pdo->rollBack();
            http_response_code(409);
            echo json_encode(['success' => false, 'error' => "{$producto['nombre']} sin stock suficiente."]);
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

    // Generar código QR único e insertar cabecera de la orden
    // La generación usa SELECT previo (fast path) + catch de UNIQUE en INSERT (TOCTOU safety)
    $maxQR = 5;
    $orden_id = null;
    $codigo_qr = null;
    for ($qrAttempt = 0; $qrAttempt < $maxQR; $qrAttempt++) {
        $codigo_qr = generarCodigoQR($pdo);
        try {
            $stmt = $pdo->prepare("INSERT INTO ordenes (cliente_id, tipo_pedido, codigo_qr, total, estado, fecha_creacion) VALUES (?, 'remoto', ?, ?, 'pendiente', NOW())");
            $stmt->execute([$_SESSION['usuario_id'], $codigo_qr, $total]);
            $orden_id = $pdo->lastInsertId();
            break;
        } catch (PDOException $e) {
            // SQLSTATE 23000 = violación de integridad (código duplicado por inserción concurrente)
            if (($e->getCode() == 23000 || str_contains($e->getMessage(), 'Duplicate')) && $qrAttempt < $maxQR - 1) {
                continue;
            }
            throw $e;
        }
    }
    if ($orden_id === null) {
        throw new RuntimeException("No se pudo generar un código QR único.");
    }

    // Insertar detalles y descontar stock de forma atómica
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

    echo json_encode(['success' => true, 'orden_id' => $orden_id, 'codigo_qr' => $codigo_qr]);

} catch (RuntimeException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error interno del servidor.']);
}