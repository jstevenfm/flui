<?php
session_start();
require_once __DIR__ . '/conexion.php';
$mensaje = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"] ?? '');

    if (!empty($email)) {
        // Verificar si el email existe y la cuenta está activa
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND activo = TRUE");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // Generar token de recuperación
            $token = bin2hex(random_bytes(32));
            $stmt = $pdo->prepare("UPDATE usuarios SET reset_token = ?, reset_token_expires = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id = ?");
            $stmt->execute([$token, $user['id']]);
        }

        // Mensaje genérico anti-enumeración (siempre el mismo)
        $mensaje = "<div class='alert alert-success'>Si el correo existe, recibirás instrucciones de recuperación.</div>";

        // Modo Demo: mostrar URL cuando se genera un token
        if (isset($token)) {
            $reset_url = "reset.php?token=" . $token;
            $mensaje .= "<div class='alert alert-info'><strong>Modo Demo:</strong><br><a href='" . htmlspecialchars($reset_url) . "'>" . htmlspecialchars($reset_url) . "</a></div>";
        }
    } else {
        $mensaje = "<div class='alert alert-danger'>Por favor, ingresa tu correo electrónico.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flui POS - Recuperar Contraseña</title>
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
                <p class="subtitle">Recuperar Contraseña</p>
            </div>

            <?php echo $mensaje; ?>

            <form class="form-content" id="recover-form" method="POST" action="recuperar.php">
                <div class="input-group">
                    <label>Correo electrónico</label>
                    <input name="email" id="email" type="email" placeholder="Ingresa tu correo electrónico" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>

                <button type="submit" class="btn">Enviar instrucciones &rarr;</button>
            </form>
            <a class="singup" href="login.php">Volver a iniciar sesión</a>
        </section>
    </div>

</body>

</html>