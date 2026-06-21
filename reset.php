<?php
session_start();
require_once __DIR__ . '/conexion.php';
$mensaje = '';
$token_valido = false;
$user = null;

// GET: validar token
$token = $_GET['token'] ?? '';

if ($_SERVER["REQUEST_METHOD"] !== "POST" && !empty($token)) {
    // Validar formato del token (64 hex chars)
    if (strlen($token) === 64 && ctype_xdigit($token)) {
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE reset_token = ? AND reset_token_expires > NOW() AND activo = TRUE");
        $stmt->execute([$token]);
        $user = $stmt->fetch();

        if ($user) {
            $token_valido = true;
        } else {
            // Distinguir tipo de error: expirado, usado o inválido
            $stmt = $pdo->prepare("SELECT id, reset_token_expires FROM usuarios WHERE reset_token = ?");
            $stmt->execute([$token]);
            $check = $stmt->fetch();

            if ($check) {
                if ($check['reset_token_expires'] && strtotime($check['reset_token_expires']) < time()) {
                    $mensaje = "<div class='alert alert-danger'>El enlace de recuperación ha expirado. <a href='recuperar.php'>Solicita uno nuevo</a>.</div>";
                } else {
                    $mensaje = "<div class='alert alert-danger'>El enlace ya fue utilizado. <a href='recuperar.php'>Solicita uno nuevo</a>.</div>";
                }
            } else {
                $mensaje = "<div class='alert alert-danger'>Enlace inválido. <a href='recuperar.php'>Solicita uno nuevo</a>.</div>";
            }
        }
    } else {
        $mensaje = "<div class='alert alert-danger'>Enlace inválido. <a href='recuperar.php'>Solicita uno nuevo</a>.</div>";
    }
}

// POST: procesar nueva contraseña
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $token = $_POST['token'] ?? '';
    $password_nueva = $_POST['password_nueva'] ?? '';

    // Re-validar token en POST para seguridad
    if (!empty($token) && strlen($token) === 64 && ctype_xdigit($token)) {
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE reset_token = ? AND reset_token_expires > NOW() AND activo = TRUE");
        $stmt->execute([$token]);
        $user = $stmt->fetch();

        if ($user) {
            $token_valido = true;

            if (!empty($password_nueva)) {
                if (strlen($password_nueva) >= 6) {
                    // Hashear y actualizar contraseña, anular token
                    $hash = password_hash($password_nueva, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("UPDATE usuarios SET password = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?");
                    $stmt->execute([$hash, $user['id']]);
                    $mensaje = "<div class='alert alert-success'>Contraseña actualizada correctamente. <a href='login.php'>Iniciar sesión</a>.</div>";
                    $token_valido = false; // Ocultar formulario tras éxito
                } else {
                    $mensaje = "<div class='alert alert-danger'>La contraseña debe tener al menos 6 caracteres.</div>";
                }
            } else {
                $mensaje = "<div class='alert alert-danger'>Por favor, completa todos los campos.</div>";
            }
        } else {
            // Token no válido en POST
            $mensaje = "<div class='alert alert-danger'>El enlace de recuperación no es válido o ha expirado. <a href='recuperar.php'>Solicita uno nuevo</a>.</div>";
            $token_valido = false;
        }
    } else {
        $mensaje = "<div class='alert alert-danger'>Enlace inválido. <a href='recuperar.php'>Solicita uno nuevo</a>.</div>";
        $token_valido = false;
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flui POS - Restablecer Contraseña</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="login.css">
    <style>
        .alert { padding: 10px; margin-bottom: 15px; border-radius: 4px; font-size: 14px; text-align: center; }
        .alert-danger { background: #f8d7da; color: #721c24; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-info { background: #d1ecf1; color: #0c5460; margin-top: 10px; }
    </style>
</head>

<body>

    <div class="main-container">
        <section class="card">
            <div class="card-header">
                <div class="icon-circle">
                    <img src="https://img.icons8.com/ios-filled/50/70ffbd/cash-register.png" alt="Icono POS" width="30">
                </div>
                <h1>Flui</h1>
                <p class="subtitle">Restablecer Contraseña</p>
            </div>

            <?php echo $mensaje; ?>

            <?php if ($token_valido): ?>
            <form class="form-content" id="reset-form" method="POST" action="reset.php">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                <div class="input-group">
                    <label>Nueva Contraseña</label>
                    <input name="password_nueva" id="password_nueva" type="password" placeholder="Mínimo 6 caracteres" required minlength="6">
                </div>

                <button type="submit" class="btn">Actualizar contraseña &rarr;</button>
            </form>
            <?php endif; ?>

            <?php if (!$token_valido && empty($mensaje)): ?>
            <div class="alert alert-danger">Enlace inválido. <a href='recuperar.php'>Solicita uno nuevo</a>.</div>
            <?php endif; ?>

            <a class="singup" href="login.php">Volver a iniciar sesión</a>
        </section>
    </div>

</body>

</html>