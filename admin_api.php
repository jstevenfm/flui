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

    case 'buscar_qr':
        actionBuscarQr($pdo);
        break;

    case 'reclamar':
        actionReclamar($pdo);
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
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Método no permitido. Use GET.']);
        return;
    }

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

// -------------------------------------------------------
// GET ?action=listar_categorias — Lista de categorías con conteo de productos
// -------------------------------------------------------
function actionListarCategorias(PDO $pdo): void {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Método no permitido. Use GET.']);
        return;
    }

    $stmt = $pdo->query("
        SELECT c.id, c.nombre, c.descripcion,
               COUNT(p.id) AS total_productos
        FROM categorias c
        LEFT JOIN productos p ON p.categoria_id = c.id
        GROUP BY c.id
        ORDER BY c.nombre ASC
    ");
    $categorias = $stmt->fetchAll();

    // Normalizar tipos: total_productos viene como string desde MySQL
    foreach ($categorias as &$cat) {
        $cat['total_productos'] = (int)$cat['total_productos'];
    }
    unset($cat);

    echo json_encode([
        'success' => true,
        'categorias' => $categorias,
    ]);
}

// -------------------------------------------------------
// POST ?action=crear_categoria — Crea una categoría nueva
// Body JSON: { nombre, descripcion? }
// -------------------------------------------------------
function actionCrearCategoria(PDO $pdo): void {
    if (($_SERVER['REQUEST_METHOD'] ?? 'POST') !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Método no permitido. Use POST.']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $nombre = trim((string)($input['nombre'] ?? ''));
    $descripcion = trim((string)($input['descripcion'] ?? ''));

    if ($nombre === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'El nombre de la categoría es obligatorio.']);
        return;
    }

    try {
        $stmt = $pdo->prepare(
            "INSERT INTO categorias (nombre, descripcion) VALUES (?, ?)"
        );
        $stmt->execute([$nombre, $descripcion]);
        $id = (int)$pdo->lastInsertId();

        echo json_encode([
            'success' => true,
            'message' => 'Categoría creada.',
            'id' => $id,
        ]);
    } catch (PDOException $e) {
        // 1062 = violación de UNIQUE(nombre)
        if ($e->getCode() == 23000 || (string)$e->errorInfo[1] === '1062') {
            http_response_code(409);
            echo json_encode(['success' => false, 'error' => 'Ya existe una categoría con ese nombre.']);
            return;
        }
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Error interno del servidor.']);
    }
}

// -------------------------------------------------------
// POST ?action=editar_categoria — Edita una categoría existente
// Body JSON: { id, nombre, descripcion? }
// -------------------------------------------------------
function actionEditarCategoria(PDO $pdo): void {
    if (($_SERVER['REQUEST_METHOD'] ?? 'POST') !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Método no permitido. Use POST.']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $id = (int)($input['id'] ?? 0);
    $nombre = trim((string)($input['nombre'] ?? ''));
    $descripcion = trim((string)($input['descripcion'] ?? ''));

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID de categoría no válido.']);
        return;
    }
    if ($nombre === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'El nombre de la categoría es obligatorio.']);
        return;
    }

    try {
        $stmt = $pdo->prepare(
            "UPDATE categorias SET nombre = ?, descripcion = ? WHERE id = ?"
        );
        $stmt->execute([$nombre, $descripcion, $id]);

        // rowCount=0 puede significar "id no existe" o "datos idénticos".
        // Para distinguir, verificamos existencia explícitamente.
        if ($stmt->rowCount() === 0) {
            $check = $pdo->prepare("SELECT COUNT(*) FROM categorias WHERE id = ?");
            $check->execute([$id]);
            if ((int)$check->fetchColumn() === 0) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Categoría no encontrada.']);
                return;
            }
            // Existe y no cambió — tratamos como éxito (idempotente)
        }

        echo json_encode([
            'success' => true,
            'message' => 'Categoría actualizada.',
        ]);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000 || (string)($e->errorInfo[1] ?? '') === '1062') {
            http_response_code(409);
            echo json_encode(['success' => false, 'error' => 'Ya existe una categoría con ese nombre.']);
            return;
        }
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Error interno del servidor.']);
    }
}

// -------------------------------------------------------
// POST ?action=eliminar_categoria — Elimina categoría con guard FK
// Body JSON: { id }
// Bloquea si tiene productos asociados (FK RESTRICT ya lo protege,
// pero devolvemos 409 amigable antes del DELETE)
// -------------------------------------------------------
function actionEliminarCategoria(PDO $pdo): void {
    if (($_SERVER['REQUEST_METHOD'] ?? 'POST') !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Método no permitido. Use POST.']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $id = (int)($input['id'] ?? 0);

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID de categoría no válido.']);
        return;
    }

    // --- FK GUARD: contar productos asociados ---
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM productos WHERE categoria_id = ?");
    $stmt->execute([$id]);
    $totalProductos = (int)$stmt->fetchColumn();

    if ($totalProductos > 0) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'error' => 'La categoría tiene ' . $totalProductos . ' producto(s) asociado(s). No se puede eliminar.',
        ]);
        return;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM categorias WHERE id = ?");
        $stmt->execute([$id]);

        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Categoría no encontrada.']);
            return;
        }

        echo json_encode([
            'success' => true,
            'message' => 'Categoría eliminada.',
        ]);
    } catch (PDOException $e) {
        // Doble safety net: si por race condition se agregó un producto,
        // el FK RESTRICT lanza 23000 — lo traducimos al mismo 409 amigable.
        if ($e->getCode() == 23000) {
            http_response_code(409);
            echo json_encode([
                'success' => false,
                'error' => 'La categoría tiene producto(s) asociado(s). No se puede eliminar.',
            ]);
            return;
        }
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Error interno del servidor.']);
    }
}

// -------------------------------------------------------
// Helper: procesarImagen — valida y mueve archivo subido
// Retorna ['success'=>true, 'filename'=>$name] o ['success'=>false, 'error'=>$msg]
// Solo acepta JPEG y PNG, máximo 2MB
// -------------------------------------------------------
function procesarImagen(array $archivo): array {
    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Error al subir la imagen.'];
    }

    // Validar MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $archivo['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, ['image/jpeg', 'image/png'], true)) {
        return ['success' => false, 'error' => 'Solo se permiten imágenes JPEG o PNG.'];
    }

    // Verificación secundaria: ¿es realmente una imagen decodificable?
    $imageInfo = @getimagesize($archivo['tmp_name']);
    if ($imageInfo === false) {
        return ['success' => false, 'error' => 'El archivo no es una imagen válida.'];
    }

    // Validar tamaño (2MB)
    if ($archivo['size'] > 2 * 1024 * 1024) {
        return ['success' => false, 'error' => 'La imagen no debe exceder 2MB.'];
    }

    // Generar nombre seguro
    $safeBasename = preg_replace('/[^a-zA-Z0-9_.-]/', '', basename($archivo['name']));
    $filename = uniqid('prod_', true) . '_' . $safeBasename;
    $destination = __DIR__ . '/img/' . $filename;

    if (!move_uploaded_file($archivo['tmp_name'], $destination)) {
        return ['success' => false, 'error' => 'Error al guardar la imagen en el servidor.'];
    }

    return ['success' => true, 'filename' => $filename];
}

// -------------------------------------------------------
// GET ?action=listar_productos — Lista productos con categoría
// -------------------------------------------------------
function actionListarProductos(PDO $pdo): void {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Método no permitido. Use GET.']);
        return;
    }

    $stmt = $pdo->query("
        SELECT p.id, p.nombre, p.precio, p.stock, p.imagen, p.categoria_id,
               c.nombre AS categoria_nombre
        FROM productos p
        JOIN categorias c ON p.categoria_id = c.id
        ORDER BY p.id DESC
    ");
    $productos = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'productos' => $productos,
    ]);
}

// -------------------------------------------------------
// POST ?action=crear_producto — Crea producto (multipart/form-data)
// Campos: nombre, precio, categoria_id, stock, imagen (file, opcional)
// -------------------------------------------------------
function actionCrearProducto(PDO $pdo): void {
    if (($_SERVER['REQUEST_METHOD'] ?? 'POST') !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Método no permitido. Use POST.']);
        return;
    }

    $nombre = trim((string)($_POST['nombre'] ?? ''));
    $precio = $_POST['precio'] ?? '';
    $categoria_id = (int)($_POST['categoria_id'] ?? 0);
    $stock = $_POST['stock'] ?? '';

    // --- Validaciones ---
    if ($nombre === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'El nombre del producto es obligatorio.']);
        return;
    }
    if ($precio === '' || !is_numeric($precio) || (float)$precio <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'El precio debe ser un número mayor a 0.']);
        return;
    }
    if ($categoria_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Debe seleccionar una categoría.']);
        return;
    }
    if ($stock === '' || !is_numeric($stock) || (int)$stock < 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'El stock debe ser un número mayor o igual a 0.']);
        return;
    }

    // --- Manejo de imagen ---
    $imagen = null;
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $resultado = procesarImagen($_FILES['imagen']);
        if (!$resultado['success']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $resultado['error']]);
            return;
        }
        $imagen = $resultado['filename'];
    } elseif (isset($_FILES['imagen']) && $_FILES['imagen']['error'] !== UPLOAD_ERR_NO_FILE) {
        // Error de upload que no es "no file" — ej. excede upload_max_filesize
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Error al subir la imagen.']);
        return;
    }
    // UPLOAD_ERR_NO_FILE o no hay $_FILES['imagen'] → imagen queda NULL

    try {
        $stmt = $pdo->prepare(
            "INSERT INTO productos (nombre, precio, categoria_id, stock, imagen) VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$nombre, (float)$precio, $categoria_id, (int)$stock, $imagen]);
        $id = (int)$pdo->lastInsertId();

        echo json_encode([
            'success' => true,
            'message' => 'Producto creado.',
            'id' => $id,
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Error interno del servidor.']);
    }
}

// -------------------------------------------------------
// POST ?action=editar_producto — Edita producto (multipart/form-data)
// Campos: id, nombre, precio, categoria_id, stock, imagen (file, opcional)
// Si no se sube nueva imagen, se mantiene la actual.
// -------------------------------------------------------
function actionEditarProducto(PDO $pdo): void {
    if (($_SERVER['REQUEST_METHOD'] ?? 'POST') !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Método no permitido. Use POST.']);
        return;
    }

    $id = (int)($_POST['id'] ?? 0);
    $nombre = trim((string)($_POST['nombre'] ?? ''));
    $precio = $_POST['precio'] ?? '';
    $categoria_id = (int)($_POST['categoria_id'] ?? 0);
    $stock = $_POST['stock'] ?? '';

    // --- Validaciones ---
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID de producto no válido.']);
        return;
    }
    if ($nombre === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'El nombre del producto es obligatorio.']);
        return;
    }
    if ($precio === '' || !is_numeric($precio) || (float)$precio <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'El precio debe ser un número mayor a 0.']);
        return;
    }
    if ($categoria_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Debe seleccionar una categoría.']);
        return;
    }
    if ($stock === '' || !is_numeric($stock) || (int)$stock < 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'El stock debe ser un número mayor o igual a 0.']);
        return;
    }

    // --- Manejo de imagen (opcional: solo si se subió una nueva) ---
    $imagenSet = '';
    $imagenValue = null;
    $oldImagen = null;

    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        // Consultar imagen actual para eliminarla después del update
        $stmt = $pdo->prepare("SELECT imagen FROM productos WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        $oldImagen = $row ? $row['imagen'] : null;

        $resultado = procesarImagen($_FILES['imagen']);
        if (!$resultado['success']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $resultado['error']]);
            return;
        }
        $imagenValue = $resultado['filename'];
        $imagenSet = ', imagen = ?';
    } elseif (isset($_FILES['imagen']) && $_FILES['imagen']['error'] !== UPLOAD_ERR_NO_FILE) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Error al subir la imagen.']);
        return;
    }
    // Si no hay nueva imagen → no tocar columna imagen

    try {
        $sql = "UPDATE productos SET nombre = ?, precio = ?, categoria_id = ?, stock = ?{$imagenSet} WHERE id = ?";
        $params = [$nombre, (float)$precio, $categoria_id, (int)$stock];
        if ($imagenValue !== null) {
            $params[] = $imagenValue;
        }
        $params[] = $id;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        if ($stmt->rowCount() === 0) {
            // Verificar si el producto existe o si los datos son idénticos
            $check = $pdo->prepare("SELECT COUNT(*) FROM productos WHERE id = ?");
            $check->execute([$id]);
            if ((int)$check->fetchColumn() === 0) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Producto no encontrado.']);
                return;
            }
            // Existe pero datos idénticos — éxito idempotente
        }

        // Eliminar imagen anterior si se reemplazó
        if ($oldImagen !== null) {
            $oldFile = __DIR__ . '/img/' . $oldImagen;
            if (file_exists($oldFile)) {
                @unlink($oldFile);
            }
        }

        echo json_encode([
            'success' => true,
            'message' => 'Producto actualizado.',
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Error interno del servidor.']);
    }
}

// -------------------------------------------------------
// POST ?action=eliminar_producto — Elimina producto con FK guard
// Body JSON: { id }
// Si el producto tiene órdenes en orden_detalles, PDOException 23000 → 409
// -------------------------------------------------------
function actionEliminarProducto(PDO $pdo): void {
    if (($_SERVER['REQUEST_METHOD'] ?? 'POST') !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Método no permitido. Use POST.']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $id = (int)($input['id'] ?? 0);

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID de producto no válido.']);
        return;
    }

    // Consultar imagen actual para eliminar archivo
    $stmt = $pdo->prepare("SELECT imagen FROM productos WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    $imagen = $row ? $row['imagen'] : null;

    try {
        $stmt = $pdo->prepare("DELETE FROM productos WHERE id = ?");
        $stmt->execute([$id]);

        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Producto no encontrado.']);
            return;
        }

        // Eliminar archivo de imagen si existe
        if ($imagen !== null) {
            $filePath = __DIR__ . '/img/' . $imagen;
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
        }

        echo json_encode([
            'success' => true,
            'message' => 'Producto eliminado.',
        ]);
    } catch (PDOException $e) {
        // FK RESTRICT: producto_referenciado en orden_detalles
        if ($e->getCode() == 23000) {
            http_response_code(409);
            echo json_encode([
                'success' => false,
                'error' => 'No se puede eliminar: el producto tiene órdenes asociadas.',
            ]);
            return;
        }
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Error interno del servidor.']);
    }
}

// -------------------------------------------------------
// Helper: validarRangoFechas — valida y normaliza desde/hasta (Y-m-d)
// Retorna ['ok'=>bool, 'desde'=>string, 'hasta'=>string, 'error'=>string]
// ok=false si formato inválido o desde > hasta.
// -------------------------------------------------------
function validarRangoFechas(): array {
    $desde = trim((string)($_GET['desde'] ?? ''));
    $hasta = trim((string)($_GET['hasta'] ?? ''));

    $d = DateTime::createFromFormat('Y-m-d', $desde);
    $h = DateTime::createFromFormat('Y-m-d', $hasta);
    // createFromFormat admite fechas con warnings (ej. día 31 en mes de 30); getLastErrors() las detecta.
    $dErrors = $d ? $d::getLastErrors() : false;
    $hErrors = $h ? $h::getLastErrors() : false;

    if (!$d || ($dErrors && ($dErrors['warning_count'] > 0 || $dErrors['error_count'] > 0))) {
        return ['ok' => false, 'error' => 'La fecha "desde" no es válida. Use el formato AAAA-MM-DD.'];
    }
    if (!$h || ($hErrors && ($hErrors['warning_count'] > 0 || $hErrors['error_count'] > 0))) {
        return ['ok' => false, 'error' => 'La fecha "hasta" no es válida. Use el formato AAAA-MM-DD.'];
    }
    if ($d > $h) {
        return ['ok' => false, 'error' => 'La fecha "desde" no puede ser mayor que "hasta".'];
    }

    return ['ok' => true, 'desde' => $desde, 'hasta' => $hasta];
}

// -------------------------------------------------------
// GET ?action=reporte_ventas — Ventas por día en un rango
// Params: desde, hasta (AAAA-MM-DD). Solo órdenes entregadas.
// -------------------------------------------------------
function actionReporteVentas(PDO $pdo): void {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Método no permitido. Use GET.']);
        return;
    }

    $r = validarRangoFechas();
    if (!$r['ok']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $r['error']]);
        return;
    }

    try {
        $stmt = $pdo->prepare("
            SELECT DATE(o.fecha_creacion) AS fecha,
                   COUNT(*) AS ordenes,
                   COALESCE(SUM(o.total), 0) AS total
            FROM ordenes o
            WHERE o.estado = 'entregado'
              AND o.fecha_creacion BETWEEN ? AND ?
            GROUP BY DATE(o.fecha_creacion)
            ORDER BY fecha DESC
        ");
        // BETWEEN intervalo completo del día inicial al último segundo del día final
        $stmt->execute([
            $r['desde'] . ' 00:00:00',
            $r['hasta'] . ' 23:59:59',
        ]);
        $ventas = $stmt->fetchAll();

        // Normalizar tipos numéricos (PDO-mysql returns strings por defecto)
        foreach ($ventas as &$row) {
            $row['ordenes'] = (int)$row['ordenes'];
            $row['total'] = (float)$row['total'];
        }
        unset($row);

        echo json_encode([
            'success' => true,
            'desde' => $r['desde'],
            'hasta' => $r['hasta'],
            'ventas' => $ventas,
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Error interno del servidor.']);
    }
}

// -------------------------------------------------------
// GET ?action=reporte_productos — Top 20 productos por cantidad vendida
// Params: desde, hasta (AAAA-MM-DD). Solo órdenes entregadas.
// -------------------------------------------------------
function actionReporteProductos(PDO $pdo): void {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Método no permitido. Use GET.']);
        return;
    }

    $r = validarRangoFechas();
    if (!$r['ok']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $r['error']]);
        return;
    }

    try {
        $stmt = $pdo->prepare("
            SELECT p.nombre,
                   SUM(od.cantidad) AS cantidad,
                   SUM(od.cantidad * od.precio_unitario) AS total
            FROM orden_detalles od
            JOIN ordenes o ON od.orden_id = o.id
            JOIN productos p ON od.producto_id = p.id
            WHERE o.estado = 'entregado'
              AND o.fecha_creacion BETWEEN ? AND ?
            GROUP BY p.id
            ORDER BY cantidad DESC
            LIMIT 20
        ");
        $stmt->execute([
            $r['desde'] . ' 00:00:00',
            $r['hasta'] . ' 23:59:59',
        ]);
        $productos = $stmt->fetchAll();

        foreach ($productos as &$row) {
            $row['cantidad'] = (int)$row['cantidad'];
            $row['total'] = (float)$row['total'];
        }
        unset($row);

        echo json_encode([
            'success' => true,
            'desde' => $r['desde'],
            'hasta' => $r['hasta'],
            'productos' => $productos,
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Error interno del servidor.']);
    }
}

// -------------------------------------------------------
// GET ?action=reporte_cajeros — Ventas por cajero en un rango
// Params: desde, hasta (AAAA-MM-DD). Solo órdenes entregadas.
// Incluye venta_rapida (cajero_id siempre asignado).
// -------------------------------------------------------
function actionReporteCajeros(PDO $pdo): void {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Método no permitido. Use GET.']);
        return;
    }

    $r = validarRangoFechas();
    if (!$r['ok']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $r['error']]);
        return;
    }

    try {
        $stmt = $pdo->prepare("
            SELECT u.usuario,
                   COUNT(*) AS ordenes,
                   COALESCE(SUM(o.total), 0) AS total
            FROM ordenes o
            JOIN usuarios u ON o.cajero_id = u.id
            WHERE o.estado = 'entregado'
              AND o.fecha_creacion BETWEEN ? AND ?
              AND o.cajero_id IS NOT NULL
            GROUP BY u.id
            ORDER BY total DESC
        ");
        $stmt->execute([
            $r['desde'] . ' 00:00:00',
            $r['hasta'] . ' 23:59:59',
        ]);
        $cajeros = $stmt->fetchAll();

        foreach ($cajeros as &$row) {
            $row['ordenes'] = (int)$row['ordenes'];
            $row['total'] = (float)$row['total'];
        }
        unset($row);

        echo json_encode([
            'success' => true,
            'desde' => $r['desde'],
            'hasta' => $r['hasta'],
            'cajeros' => $cajeros,
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Error interno del servidor.']);
    }
}

// -------------------------------------------------------
// POST ?action=buscar_qr — Busca una orden por su código QR
// Body JSON: { codigo_qr }
// Retorna detalles de la orden (id, cliente, tipo, total, estado, items).
// 404 si el código no existe en ordenes.codigo_qr.
// -------------------------------------------------------
function actionBuscarQr(PDO $pdo): void {
    if (($_SERVER['REQUEST_METHOD'] ?? 'POST') !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Método no permitido. Use POST.']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $codigo = trim((string)($input['codigo_qr'] ?? ''));

    if ($codigo === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'El código QR es obligatorio.']);
        return;
    }

    try {
        // Cabecera de la orden
        $stmt = $pdo->prepare("
            SELECT o.id, o.total, o.estado, o.fecha_creacion, o.tipo_pedido, o.cliente_id, o.cajero_id,
                   u.usuario AS cliente_nombre
            FROM ordenes o
            LEFT JOIN usuarios u ON o.cliente_id = u.id
            WHERE o.codigo_qr = ?
            LIMIT 1
        ");
        $stmt->execute([$codigo]);
        $orden = $stmt->fetch();

        if (!$orden) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Orden no encontrada.']);
            return;
        }

        // Detalle de la orden
        $stmt = $pdo->prepare("
            SELECT p.nombre, od.cantidad, od.precio_unitario
            FROM orden_detalles od
            JOIN productos p ON od.producto_id = p.id
            WHERE od.orden_id = ?
            ORDER BY p.nombre
        ");
        $stmt->execute([$orden['id']]);
        $detalles = $stmt->fetchAll();

        // Normalizar tipos numéricos
        $orden['id'] = (int)$orden['id'];
        $orden['total'] = (float)$orden['total'];
        $orden['cliente_id'] = $orden['cliente_id'] !== null ? (int)$orden['cliente_id'] : null;
        $orden['cajero_id'] = $orden['cajero_id'] !== null ? (int)$orden['cajero_id'] : null;
        foreach ($detalles as &$d) {
            $d['cantidad'] = (int)$d['cantidad'];
            $d['precio_unitario'] = (float)$d['precio_unitario'];
        }
        unset($d);

        echo json_encode([
            'success' => true,
            'orden' => $orden,
            'detalles' => $detalles,
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Error interno del servidor.']);
    }
}

// -------------------------------------------------------
// POST ?action=reclamar — Marca una orden como entregada
// Body JSON: { orden_id }
// Optimistic lock: solo actualiza si estado='listo'.
// rowCount=0 → la orden ya no está 'listo' (ya reclamada or otro estado).
// Registra cajero_id = el usuario admin que reclama ($_SESSION['usuario_id']).
// -------------------------------------------------------
function actionReclamar(PDO $pdo): void {
    if (($_SERVER['REQUEST_METHOD'] ?? 'POST') !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Método no permitido. Use POST.']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $orden_id = (int)($input['orden_id'] ?? 0);

    if ($orden_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID de orden no válido.']);
        return;
    }

    $cajero_id = (int)($_SESSION['usuario_id'] ?? 0);

    try {
        // Optimistic lock: WHERE estado='listo' —— si alguien más reclamó
        // primero, la fila ya no cumple la condición y rowCount=0.
        $stmt = $pdo->prepare(
            "UPDATE ordenes
             SET estado = 'entregado', cajero_id = ?
             WHERE id = ? AND estado = 'listo'"
        );
        $stmt->execute([$cajero_id, $orden_id]);

        if ($stmt->rowCount() === 0) {
            // Distinguir: ¿existe la orden y no está 'listo', o no existe?
            $check = $pdo->prepare("SELECT estado FROM ordenes WHERE id = ?");
            $check->execute([$orden_id]);
            $estado = $check->fetchColumn();

            http_response_code(409);
            if ($estado === false) {
                echo json_encode(['success' => false, 'error' => 'La orden no existe.']);
            } elseif ($estado === 'entregado') {
                echo json_encode(['success' => false, 'error' => 'Esta orden ya fue reclamada.']);
            } else {
                $labels = [
                    'pendiente' => 'pendiente',
                    'en_preparacion' => 'en preparación',
                    'cancelada' => 'cancelada',
                ];
                $lbl = $labels[$estado] ?? $estado;
                echo json_encode(['success' => false, 'error' => 'La orden no está lista para reclamar (estado: ' . $lbl . ').']);
            }
            return;
        }

        echo json_encode([
            'success' => true,
            'message' => 'Orden reclamada exitosamente.',
            'orden_id' => $orden_id,
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Error interno del servidor.']);
    }
}