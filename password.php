<?php
session_start();
require 'conexion.php';

// 1. Validar autenticación
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

// 2. Generar Token CSRF si no existe
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$mensaje = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $password_actual = $_POST['password_actual'] ?? '';
    $password_nueva = $_POST['password_nueva'] ?? '';
    $password_confirmar = $_POST['password_confirmar'] ?? '';
    $csrf_token_post = $_POST['csrf_token'] ?? '';

    // 3. Validar Token CSRF
    if (!hash_equals($_SESSION['csrf_token'], $csrf_token_post)) {
        die("<div class='alert alert-danger'>Error de validación de seguridad (CSRF).</div>");
    }

    if (!empty($password_actual) && !empty($password_nueva) && !empty($password_confirmar)) {
        
        // 4. Validar que la nueva contraseña coincida con la confirmación
        if ($password_nueva !== $password_confirmar) {
            $mensaje = "<div class='alert alert-danger'>La nueva contraseña y su confirmación no coinciden.</div>";
        } 
        // 5. Validar políticas de complejidad (Min 8 caracteres, letras y números)
        define('MIN_LENGHT', 8);
        if (strlen($password_nueva) < MIN_LENGHT || !preg_match('/[A-Za-z]/', $password_nueva) || !preg_match('/[0-9]/', $password_nueva)) {
            $mensaje = "<div class='alert alert-danger'>La nueva contraseña debe tener al menos 8 caracteres, incluyendo letras y números.</div>";
        } else {
            
            // Buscar la contraseña actual en la BD
            $stmt = $pdo->prepare("SELECT password FROM usuarios WHERE id = ?");
            $stmt->execute([$_SESSION['usuario_id']]);
            $user = $stmt->fetch();

            if ($user && password_verify($password_actual, $user['password'])) {
                // Usar PASSWORD_DEFAULT (PHP actualizará automáticamente el algoritmo si BCRYPT queda obsoleto)
                $nueva_encriptada = password_hash($password_nueva, PASSWORD_DEFAULT);
                
                $update_stmt = $pdo->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
                $update_stmt->execute([$nueva_encriptada, $_SESSION['usuario_id']]);
                
                // 6. Regenerar token CSRF tras éxito para evitar re-envíos
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                
                $mensaje = "<div class='alert alert-success'>Contraseña actualizada correctamente. Recomendamos iniciar sesión nuevamente.</div>";
            } else {
                $mensaje = "<div class='alert alert-danger'>La contraseña actual es incorrecta.</div>";
            }
        }
    } else {
        $mensaje = "<div class='alert alert-danger'>Por favor, completa todos los campos.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flui POS - Módulos Integrados</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="login.css">
    <style>
        .alert { padding: 10px; margin-bottom: 15px; border-radius: 4px; font-size: 14px; text-align: center; }
        .alert-danger { background: #f8d7da; color: #721c24; }
        .alert-success { background: #d4edda; color: #155724; }
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
                <p class="subtitle">Gestión de Seguridad</p>
                <p class="subtitle">Hola, <strong><?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?></strong>. Actualiza tus credenciales desde aquí.</p>
            </div>

            <?php echo $mensaje; ?>

          <form class="form-content" id="password-form" method="POST" action="password.php">
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

    <div class="input-group">
        <label>Contraseña Actual</label>
        <input name="password_actual" id="password_actual" type="password" placeholder="Tu contraseña actual" required>
    </div>

    <div class="input-group">
        <label>Nueva Contraseña</label>
        <input name="password_nueva" id="password_nueva" type="password" placeholder="Mínimo 8 caracteres con letras y números" required>
    </div>

    <div class="input-group">
        <label>Confirmar Nueva Contraseña</label>
        <input name="password_confirmar" id="password_confirmar" type="password" placeholder="Repite tu nueva contraseña" required>
    </div>

    <button type="submit" class="btn">Actualizar contraseña &rarr;</button>
</form>
            <a class="singup" href="logout.php" style="color: #ff7070;">Cerrar Sesión de Flui</a>
        </section>

    </div>

</body>

</html>