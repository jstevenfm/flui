<?php
// 1. Inicializar el entorno de sesión para poder manipularlo
session_start();

// 2. Desvincular todas las variables globales de la sesión actual
session_unset();

// 3. Destruir físicamente la sesión guardada en el servidor
session_destroy();

// 4. Limpiar la cookie de sesión en el navegador (Buena práctica de seguridad)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 5. Redirigir al usuario al formulario de login limpio
header("Location: login.php");
exit;
?>