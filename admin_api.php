<?php
// admin_api.php — JSON API para la vista del administrador
// Acciones: dashboard_stats, listar_cajeros, crear_cajero, editar_cajero, toggle_cajero,
//           listar_categorias, crear_categoria, editar_categoria, eliminar_categoria,
//           listar_productos, crear_producto, editar_producto, eliminar_producto,
//           reporte_ventas, reporte_productos, reporte_cajeros, buscar_qr, reclamar

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/conexion.php';

// --- Autenticación: solo admin ---
// 401 = sin sesión; 403 = sesión válida pero rol equivocado
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'auth', 'message' => 'Debes iniciar sesión.']);
    exit;
}
if (($_SESSION['usuario_rol'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'forbidden', 'message' => 'Acceso restringido al rol admin.']);
    exit;
}

$admin_nombre = $_SESSION['usuario_nombre'];
$action = $_GET['action'] ?? '';

// --- Router ---
switch ($action) {
    case 'dashboard_stats':
        actionDashboardStats($pdo);
        break;

    case 'listar_cajeros':
        actionListarCajeros($pdo);
        break;

    case 'crear_cajero':
        actionCrearCajero($pdo);
        break;

    case 'editar_cajero':
        actionEditarCajero($pdo);
        break;

    case 'toggle_cajero':
        actionToggleCajero($pdo);
        break;

    case 'listar_categorias':
        actionListarCategorias($pdo);
        break;

    case 'crear_categoria':
        actionCrearCategoria($pdo);
        break;

    case 'editar_categoria':
        actionEditarCategoria($pdo);
        break;

    case 'eliminar_categoria':
        actionEliminarCategoria($pdo);
        break;

    case 'listar_productos':
        actionListarProductos($pdo);
        break;

    case 'crear_producto':
        actionCrearProducto($pdo);
        break;

    case 'editar_producto':
        actionEditarProducto($pdo);
        break;

    case 'eliminar_producto':
        actionEliminarProducto($pdo);
        break;

    case 'reporte_ventas':
        actionReporteVentas($pdo);
        break;

    case 'reporte_productos':
        actionReporteProductos($pdo);
        break;

    case 'reporte_cajeros':
        actionReporteCajeros($pdo);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Acción no válida.']);
        break;
}

// -------------------------------------------------------
// GET ?action=dashboard_stats — Estadísticas del día actual
// -------------------------------------------------------
function actionDashboardStats(PDO $pdo): void {
    // 1. Total ventas y órdenes entregadas hoy
    $stmt = $pdo->query("
        SELECT COALESCE(SUM(total), 0) AS total_ventas,
               COUNT(*) AS total_ordenes
        FROM ordenes
        WHERE estado = 'entregado' AND DATE(fecha_creacion) = CURDATE()
    ");
    $ventas = $stmt->fetch();

    // 2. Top 5 productos vendidos hoy
    $stmt = $pdo->query("
        SELECT p.nombre, SUM(od.cantidad) AS cantidad
        FROM orden_detalles od
        JOIN ordenes o ON od.orden_id = o.id
        JOIN productos p ON od.producto_id = p.id
        WHERE o.estado = 'entregado' AND DATE(o.fecha_creacion) = CURDATE()
        GROUP BY p.id
        ORDER BY cantidad DESC
        LIMIT 5
    ");
    $top_productos = $stmt->fetchAll();

    // 3. Órdenes pendientes
    $stmt = $pdo->query("
        SELECT COUNT(*) AS pendientes
        FROM ordenes
        WHERE estado = 'pendiente'
    ");
    $pendientes = $stmt->fetch();

    // 4. Últimas 20 órdenes
    $stmt = $pdo->query("
        SELECT o.id, o.total, o.estado, o.fecha_creacion, o.tipo_pedido, o.codigo_qr,
               u.usuario AS cliente_nombre
        FROM ordenes o
        LEFT JOIN usuarios u ON o.cliente_id = u.id
        ORDER BY o.fecha_creacion DESC
        LIMIT 20
    ");
    $ordenes_recientes = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'ventas_hoy' => (float)$ventas['total_ventas'],
        'ordenes_hoy' => (int)$ventas['total_ordenes'],
        'top_productos' => $top_productos,
        'pendientes' => (int)$pendientes['pendientes'],
        'ordenes_recientes' => $ordenes_recientes
    ]);
}

// -------------------------------------------------------
// GET ?action=listar_cajeros — Lista de cajeros (rol='cajero')
// -------------------------------------------------------
function actionListarCajeros(PDO $pdo): void {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Método no permitido. Use GET.']);
        return;
    }

    $stmt = $pdo->query("
        SELECT id, usuario, email, activo
        FROM usuarios
        WHERE rol = 'cajero'
        ORDER BY id DESC
    ");
    $cajeros = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'cajeros' => $cajeros,
    ]);
}

// -------------------------------------------------------
// POST ?action=crear_cajero — Crea un nuevo cajero
// Body JSON: { usuario, email, password }
// -------------------------------------------------------
function actionCrearCajero(PDO $pdo): void {
    if (($_SERVER['REQUEST_METHOD'] ?? 'POST') !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Método no permitido. Use POST.']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $usuario = trim((string)($input['usuario'] ?? ''));
    $email = strtolower(trim((string)($input['email'] ?? '')));
    $password = (string)($input['password'] ?? '');

    // --- Validaciones ---
    if ($usuario === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'El nombre de usuario es obligatorio.']);
        return;
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Debe ingresar un correo electrónico válido.']);
        return;
    }
    if (strlen($password) < 6) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'La contraseña debe tener al menos 6 caracteres.']);
        return;
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);

    try {
        $stmt = $pdo->prepare(
            "INSERT INTO usuarios (usuario, email, password, rol, activo)
             VALUES (?, ?, ?, 'cajero', 1)"
        );
        $stmt->execute([$usuario, $email, $hash]);
        $id = (int)$pdo->lastInsertId();

        echo json_encode([
            'success' => true,
            'message' => 'Cajero creado exitosamente.',
            'id' => $id,
        ]);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            http_response_code(409);
            echo json_encode(['success' => false, 'error' => 'El correo electrónico ya está registrado.']);
            return;
        }
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Error interno del servidor.']);
    }
}

// -------------------------------------------------------
// POST ?action=editar_cajero — Edita cajero existente
// Body JSON: { id, usuario, email, password? }
// password opcional: si viene no vacío se actualiza el hash
// -------------------------------------------------------
function actionEditarCajero(PDO $pdo): void {
    if (($_SERVER['REQUEST_METHOD'] ?? 'POST') !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Método no permitido. Use POST.']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $id = (int)($input['id'] ?? 0);

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID de cajero no válido.']);
        return;
    }

    $usuario = trim((string)($input['usuario'] ?? ''));
    $email = strtolower(trim((string)($input['email'] ?? '')));
    $password = (string)($input['password'] ?? '');

    // --- Validaciones ---
    if ($usuario === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'El nombre de usuario es obligatorio.']);
        return;
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Debe ingresar un correo electrónico válido.']);
        return;
    }
    if ($password !== '' && strlen($password) < 6) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'La contraseña debe tener al menos 6 caracteres.']);
        return;
    }

    // Construir SET dinámico: usuario y email siempre; password solo si viene
    $setClause = 'usuario = ?, email = ?';
    $params = [$usuario, $email];

    if ($password !== '') {
        $setClause .= ', password = ?';
        $params[] = password_hash($password, PASSWORD_BCRYPT);
    }

    $params[] = $id;

    try {
        $stmt = $pdo->prepare(
            "UPDATE usuarios SET {$setClause} WHERE id = ? AND rol = 'cajero'"
        );
        $stmt->execute($params);

        if ($stmt->rowCount() === 0) {
            // WHERE rol='cajero' previene editar admins: si el id no es cajero, rowCount=0
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Cajero no encontrado o no se pueden editar cuentas de administrador.']);
            return;
        }

        echo json_encode([
            'success' => true,
            'message' => 'Cajero actualizado exitosamente.',
        ]);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            http_response_code(409);
            echo json_encode(['success' => false, 'error' => 'El correo electrónico ya está registrado.']);
            return;
        }
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Error interno del servidor.']);
    }
}

// -------------------------------------------------------
// POST ?action=toggle_cajero — Activa/desactiva un cajero
// Body JSON: { id, activo: bool }
// -------------------------------------------------------
function actionToggleCajero(PDO $pdo): void {
    if (($_SERVER['REQUEST_METHOD'] ?? 'POST') !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Método no permitido. Use POST.']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $id = (int)($input['id'] ?? 0);
    // activo puede venir como bool o como 0/1; normalizamos a int
    $activo = !empty($input['activo']) ? 1 : 0;

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID de cajero no válido.']);
        return;
    }

    try {
        $stmt = $pdo->prepare(
            "UPDATE usuarios SET activo = ? WHERE id = ? AND rol = 'cajero'"
        );
        $stmt->execute([$activo, $id]);

        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Cajero no encontrado.']);
            return;
        }

        echo json_encode([
            'success' => true,
            'message' => 'Estado actualizado.',
            'activo' => (bool)$activo,
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Error interno del servidor.']);
    }
}

function actionListarCategorias(PDO $pdo): void {
    http_response_code(501);
    echo json_encode(['success' => false, 'error' => 'Acción no implementada aún.']);
}

function actionCrearCategoria(PDO $pdo): void {
    http_response_code(501);
    echo json_encode(['success' => false, 'error' => 'Acción no implementada aún.']);
}

function actionEditarCategoria(PDO $pdo): void {
    http_response_code(501);
    echo json_encode(['success' => false, 'error' => 'Acción no implementada aún.']);
}

function actionEliminarCategoria(PDO $pdo): void {
    http_response_code(501);
    echo json_encode(['success' => false, 'error' => 'Acción no implementada aún.']);
}

function actionListarProductos(PDO $pdo): void {
    http_response_code(501);
    echo json_encode(['success' => false, 'error' => 'Acción no implementada aún.']);
}

function actionCrearProducto(PDO $pdo): void {
    http_response_code(501);
    echo json_encode(['success' => false, 'error' => 'Acción no implementada aún.']);
}

function actionEditarProducto(PDO $pdo): void {
    http_response_code(501);
    echo json_encode(['success' => false, 'error' => 'Acción no implementada aún.']);
}

function actionEliminarProducto(PDO $pdo): void {
    http_response_code(501);
    echo json_encode(['success' => false, 'error' => 'Acción no implementada aún.']);
}

function actionReporteVentas(PDO $pdo): void {
    http_response_code(501);
    echo json_encode(['success' => false, 'error' => 'Acción no implementada aún.']);
}

function actionReporteProductos(PDO $pdo): void {
    http_response_code(501);
    echo json_encode(['success' => false, 'error' => 'Acción no implementada aún.']);
}

function actionReporteCajeros(PDO $pdo): void {
    http_response_code(501);
    echo json_encode(['success' => false, 'error' => 'Acción no implementada aún.']);
}