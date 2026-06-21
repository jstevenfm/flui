<?php
require __DIR__ . '/conexion.php';
$mensaje = '';

if ($_SERVER["REQUEST_METHOD"] == 'POST') {
    $nombre = trim($_POST["name"] ?? '');
    $email = strtolower(trim($_POST["email"] ?? ''));
    $email_confirm = strtolower(trim($_POST["repeat-email"] ?? ''));
    $password = $_POST["password"] ?? '';
    $password_confirm = $_POST["repeat-password"] ?? '';

    // Validaciones del Backend
    if (empty($nombre) || empty($email) || empty($password)) {
        $mensaje = "<div class='alert alert-danger'>Por favor, rellena todos los campos.</div>";
    } elseif ($email !== $email_confirm) {
        $mensaje = "<div class='alert alert-danger'>Los correos electrónicos no coinciden.</div>";
    } elseif ($password !== $password_confirm) {
        $mensaje = "<div class='alert alert-danger'>Las contraseñas no coinciden.</div>";
    } else {
        $password_encriptada = password_hash($password, PASSWORD_BCRYPT);

        try {
            $stmt = $pdo->prepare("INSERT INTO usuarios (usuario, email, password, rol) VALUES (?, ?, ?, 'cliente')");
            $stmt->execute([$nombre, $email, $password_encriptada]);
            
            $mensaje = "<div class='alert alert-success'>¡Registro exitoso! <a href='login.php' class='link-green' style='text-decoration: underline;'>Inicia sesión aquí</a></div>";
        } catch (\PDOException $e) {
            if ($e->getCode() == 23000) { 
                $mensaje = "<div class='alert alert-danger'>El correo electrónico ya está registrado.</div>";
            } else {
                $mensaje = "<div class='alert alert-danger'>Error al registrar el usuario. Por favor, intente más tarde.</div>";
            }
        }
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
                <p class="subtitle">Essential POS System</p>
            </div>

            <?php echo $mensaje; ?>

            <form class="form-content" id="signup-form" method="post" action="signup.php">
                <div class="input-group">
                    <label>Nombre</label>
                    <input name="name" id="name" type="text" placeholder="Ej: Pepito Perez" required value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                </div>
                <div class="input-group">
                    <label>Correo electrónico</label>
                    <input name="email" id="email" type="email" placeholder="Ej: pepito@perez.com" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                <div class="input-group">
                    <label>Repetir correo electrónico</label>
                    <input name="repeat-email" id="repeat-email" type="email" placeholder="Ej: pepito@perez.com" required>
                </div>

                <div class="input-group">
                    <label>Contraseña</label>
                    <input name="password" id="password" type="password" placeholder="Contraseña" required>
                </div>
                <div class="input-group">
                    <label>Repetir contraseña</label>
                    <input name="repeat-password" id="repeat-password" type="password" placeholder="Contraseña" required>
                </div>
                
                <button type="submit" class="btn">Registrarse &rarr;</button>
            </form>
            <a class="singup" href="login.php" style="display: block; text-align: center; margin-top: 15px;">¿Ya tienes cuenta? Ingresa</a>
        </section>

    </div>

</body>

</html>