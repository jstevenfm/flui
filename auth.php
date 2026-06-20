<?php
// auth.php — Guardia de sesión y verificación de roles
// Incluir al inicio de cada página protegida: require_once 'auth.php'; checkRole('admin');

function checkRole($roles): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['usuario_id'])) {
        $_SESSION['mensaje_error'] = 'Debes iniciar sesión.';
        header('Location: login.php');
        exit;
    }

    if (!is_array($roles)) {
        $roles = [$roles];
    }

    if (!in_array($_SESSION['usuario_rol'], $roles)) {
        $_SESSION['mensaje_error'] = 'No tienes permiso para acceder.';
        header('Location: login.php');
        exit;
    }
}