<?php
// Configuración de la base de datos local (Ajusta si usas credenciales distintas)
$host    = 'localhost';
$db      = 'sistema_usuarios'; // Nombre de la base de datos según tu manual
$user    = 'root';             // Usuario por defecto en entornos locales como XAMPP
$pass    = '';                 // Contraseña por defecto (vacía en XAMPP, 'root' en MAMP)
$charset = 'utf8mb4';          // Soporte completo para caracteres especiales y emojis

// Definición del Data Source Name (DSN)
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

// Opciones críticas de configuración de PDO
$options = [
    // Activar el modo de excepciones para capturar errores limpiamente en el try-catch
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    // Configurar el modo de obtención por defecto a Array Asociativo
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    // DESACTIVAR emulación de sentencias preparadas (Mitigación clave contra Inyección SQL)
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // Instanciar el objeto PDO que usarán los archivos login.php, singup.php y password.php
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // En desarrollo muestra el error; en producción se recomienda registrarlo en un log privado
    die("Error crítico de conexión a la base de datos: " . $e->getMessage());
}
?>