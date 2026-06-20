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

// --- Stubs: se implementarán en slices posteriores ---

function actionListarCajeros(PDO $pdo): void {
    http_response_code(501);
    echo json_encode(['success' => false, 'error' => 'Acción no implementada aún.']);
}

function actionCrearCajero(PDO $pdo): void {
    http_response_code(501);
    echo json_encode(['success' => false, 'error' => 'Acción no implementada aún.']);
}

function actionEditarCajero(PDO $pdo): void {
    http_response_code(501);
    echo json_encode(['success' => false, 'error' => 'Acción no implementada aún.']);
}

function actionToggleCajero(PDO $pdo): void {
    http_response_code(501);
    echo json_encode(['success' => false, 'error' => 'Acción no implementada aún.']);
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