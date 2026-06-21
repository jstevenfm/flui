<?php
require __DIR__ . '/conexion.php';
$mensaje = '';

if ($_SERVER["REQUEST_METHOD"] == 'POST') {
    $nombre    = trim($_POST["name"] ?? '');
    $email     = strtolower(trim($_POST["email"] ?? ''));
    $email_confirm = strtolower(trim($_POST["repeat-email"] ?? ''));
    $password  = $_POST["password"] ?? '';
    $password_confirm = $_POST["repeat-password"] ?? '';

    // Validaciones del Backend
    if (empty($nombre) || empty($email) || empty($password)) {
        $mensaje = "<div class='alert alert-danger'>Por favor, rellena todos los campos.</div>";
    } elseif ($email !== $email_confirm) {
        $mensaje = "<div class='alert alert-danger'>Los correos electrónicos no coinciden.</div>";
    } elseif ($password !== $password_confirm) {
        $mensaje = "<div class='alert alert-danger'>Las contraseñas no coinciden.</div>";
    } elseif (strlen($password) < 6) {
        $mensaje = "<div class='alert alert-danger'>La contraseña debe tener al menos 6 caracteres.</div>";
    } else {
        $password_encriptada = password_hash($password, PASSWORD_BCRYPT);

        try {
            $stmt = $pdo->prepare(
                "INSERT INTO usuarios (usuario, email, password, rol) VALUES (?, ?, ?, 'admin')"
            );
            $stmt->execute([$nombre, $email, $password_encriptada]);

            $mensaje = "<div class='alert alert-success'>¡Tienda creada con éxito! <a href='login.php' class='link-green' style='text-decoration: underline;'>Inicia sesión aquí</a> para configurar tu negocio.</div>";
        } catch (\PDOException $e) {
            if ($e->getCode() == 23000) {
                $mensaje = "<div class='alert alert-danger'>El correo electrónico ya está registrado. Si ya tienes una cuenta, <a href='login.php' class='link-green' style='text-decoration: underline;'>inicia sesión</a>.</div>";
            } else {
                $mensaje = "<div class='alert alert-danger'>Error al crear la tienda. Por favor, intente más tarde.</div>";
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
    <title>Flui — Abre tu tienda</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="login.css">
    <style>
        .alert { padding: 10px; margin-bottom: 15px; border-radius: 4px; font-size: 14px; text-align: center; }
        .alert-danger { background: #f8d7da; color: #721c24; }
        .alert-success { background: #d4edda; color: #155724; }
        a.link-green { color: #155724; }
    </style>
</head>

<body>

    <div class="main-container">

        <section class="card">
            <div class="card-header">
                <div class="icon-circle">
                    <i class="fa-solid fa-store" style="font-size: 30px; color: var(--primary-green);"></i>
                </div>
                <h1>Flui</h1>
                <p class="subtitle">Abre tu tienda y empieza a vender</p>
            </div>

            <?php echo $mensaje; ?>

            <form class="form-content" id="admin-signup-form" method="post" action="admin-signup.php">
                <div class="input-group">
                    <label>Nombre de la tienda</label>
                    <input name="name" id="name" type="text" placeholder="Ej: Cafetería El Aroma" required value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                </div>
                <div class="input-group">
                    <label>Correo electrónico</label>
                    <input name="email" id="email" type="email" placeholder="Ej: admin@cafeteria.com" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                <div class="input-group">
                    <label>Repetir correo electrónico</label>
                    <input name="repeat-email" id="repeat-email" type="email" placeholder="Ej: admin@cafeteria.com" required>
                </div>

                <div class="input-group">
                    <label>Contraseña</label>
                    <input name="password" id="password" type="password" placeholder="Contraseña" required>
                </div>
                <div class="input-group">
                    <label>Repetir contraseña</label>
                    <input name="repeat-password" id="repeat-password" type="password" placeholder="Contraseña" required>
                </div>

                <button type="submit" class="btn">Crear tienda &rarr;</button>
            </form>
            <a href="login.php" style="display: block; text-align: center; margin-top: 15px; color: var(--text-gray);">¿Ya tienes una tienda? Inicia sesión</a>
        </section>

    </div>

</body>

</html>
