<?php
require_once 'auth.php';
checkRole('cliente');
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flui POS - Cliente</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="login.css">
</head>

<body>

    <div class="main-container">
        <section class="card">
            <div class="card-header">
                <div class="icon-circle">
                    <img src="https://img.icons8.com/ios-filled/50/70ffbd/cash-register.png" alt="Icono POS" width="30">
                </div>
                <h1>Flui</h1>
                <p class="subtitle">Menú del Cliente</p>
                <p class="subtitle">Hola, <strong><?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?></strong></p>
            </div>

            <p style="color: var(--text-gray); margin-bottom: 20px;">Próximamente: catálogo de productos y carrito de compras.</p>

            <a class="singup" href="logout.php" style="color: #ff7070;">Cerrar Sesión</a>
        </section>
    </div>

</body>

</html>