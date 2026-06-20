<?php
// estado_orden.php — Endpoint JSON: consulta el estado actual de una orden
// Usado por confirmacion.php para el auto-refresh cada 30s
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/conexion.php';

// Solo cliente autenticado
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'cliente') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Debes iniciar sesión.']);
    exit;
}

$orden_id = (int)($_GET['id'] ?? 0);

if ($orden_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID de orden inválido.']);
    exit;
}

// La orden debe pertenecer al cliente actual
$stmt = $pdo->prepare("SELECT estado FROM ordenes WHERE id = ? AND cliente_id = ?");
$stmt->execute([$orden_id, $_SESSION['usuario_id']]);
$orden = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$orden) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Orden no encontrada.']);
    exit;
}

echo json_encode(['success' => true, 'estado' => $orden['estado']]);
