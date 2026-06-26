<?php
session_start();
require __DIR__ . '/conexion.php';
$mensaje = '';

// Consumir mensaje flash de error de auth.php
if (isset($_SESSION['mensaje_error'])) {
    $mensaje = "<div class='alert alert-danger'>" . htmlspecialchars($_SESSION['mensaje_error']) . "</div>";
    unset($_SESSION['mensaje_error']);
}

// Redirigir usuario ya autenticado según su rol
if (isset($_SESSION['usuario_id']) && isset($_SESSION['usuario_rol'])) {
    switch ($_SESSION['usuario_rol']) {
        case 'admin':
            header('Location: adm.php');
            break;
        case 'cajero':
            header('Location: cajero.php');
            break;
        case 'cliente':
            header('Location: cliente.php');
            break;
        default:
            $_SESSION = [];
            session_destroy();
            session_start();
            $_SESSION['mensaje_error'] = 'Rol no válido. Contacta al administrador.';
            header('Location: login.php');
            exit;
    }
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"] ?? '');
    $password = $_POST["password"] ?? '';

    if (!empty($email) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? AND activo = TRUE");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);

            $_SESSION['usuario_id'] = $user['id'];
            $_SESSION['usuario_nombre'] = $user['usuario'];
            $_SESSION['usuario_rol'] = $user['rol'];

            switch ($_SESSION['usuario_rol']) {
                case 'admin':
                    header('Location: adm.php');
                    break;
                case 'cajero':
                    header('Location: cajero.php');
                    break;
                case 'cliente':
                    header('Location: cliente.php');
                    break;
                default:
                    $_SESSION = [];
                    session_destroy();
                    session_start();
                    $_SESSION['mensaje_error'] = 'Rol no válido. Contacta al administrador.';
                    header('Location: login.php');
                    exit;
            }
            exit;
        } else {
            $mensaje = "<div class='alert alert-danger'>Correo electrónico o contraseña incorrectos.</div>";
        }
    } else {
        $mensaje = "<div class='alert alert-danger'>Por favor, rellena todos los campos.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flui POS - Iniciar Sesión</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="login.css">
    <style>
        .alert { padding: 10px; margin-bottom: 15px; border-radius: 4px; font-size: 14px; text-align: center; }
        .alert-danger { background: #f8d7da; color: #721c24; }
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
                <p class="subtitle">Inicio de sesión</p>
            </div>

            <?php echo $mensaje; ?>

            <form class="form-content" id="login-form" method="POST" action="login.php">
                <div class="input-group">
                    <label>Correo electrónico</label>
                    <input name="email" id="email" type="email" placeholder="Correo electrónico" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>

                <div class="input-group">
                    <div class="label-row">
                        <label>Contraseña</label>
                        <a href="recuperar.php" class="link-green">¿Olvidaste tu contraseña?</a>
                    </div>
                    <input name="password" id="password" type="password" placeholder="Contraseña" required>
                </div>

                <button type="submit" class="btn">Ingresar &rarr;</button>
            </form>
            <a class="singup" href="signup.php">Registrarse</a>
        </section>
    </div>

</body>

</html>