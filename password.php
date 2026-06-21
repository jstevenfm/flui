<?php
require_once 'auth.php';
checkRole(['admin', 'cajero', 'cliente']);
require 'conexion.php';

$mensaje = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $password_actual = $_POST['password_actual'];
    $password_nueva = $_POST['password_nueva'];

    if (!empty($password_actual) && !empty($password_nueva)) {
       
        $stmt = $pdo->prepare("SELECT password FROM usuarios WHERE id = ?");
        $stmt->execute([$_SESSION['usuario_id']]);
        $user = $stmt->fetch();


        if ($user && password_verify($password_actual, $user['password'])) {
            $nueva_encriptada = password_hash($password_nueva, PASSWORD_BCRYPT);
            
   
            $update_stmt = $pdo->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
            $update_stmt->execute([$nueva_encriptada, $_SESSION['usuario_id']]);
            
            $mensaje = "<div class='alert alert-success'>Contraseña actualizada correctamente.</div>";
        } else {
            $mensaje = "<div class='alert alert-danger'>La contraseña actual es incorrecta.</div>";
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                <div class="input-group">
                    <label>Contraseña Actual</label>
                    <input name="password_actual" id="password_actual" type="password" placeholder="Tu contraseña actual" required>
                </div>

                <div class="input-group">
                    <label>Nueva Contraseña</label>
                    <input name="password_nueva" id="password_nueva" type="password" placeholder="Mínimo 6-8 caracteres recomendado" required>
                </div>

                <button type="submit" class="btn">Actualizar contraseña &rarr;</button>
            </form>
            
            <a class="singup" href="logout.php" style="color: #ff7070;">Cerrar Sesión de Flui</a>
        </section>

    </div>

</body>

</html>