<?php
// Configuración de conexión a la base de datos

if (!defined('DB_HOST')) {
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'tienda_gamer');
    define('DB_CHARSET', 'utf8mb4');
}

if (!isset($conn) || $conn->ping() === false) {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if ($conn->connect_error) {
        die("Error de conexión a la base de datos: " . $conn->connect_error);
    }

    $conn->set_charset(DB_CHARSET);
}

// Variables de configuración
define('BASE_URL', 'http://localhost/tienda-gamer/');
define('NOMBRE_TIENDA', '🎮 GAMER FRIKI 🎮');
define('EMAIL_TIENDA', 'info@gamerfiki.com');
define('TELEFONO_TIENDA', '+591 72470790');
define('RUTA_UPLOADS', $_SERVER['DOCUMENT_ROOT'] . '/tienda-gamer/uploads/');
define('URL_UPLOADS', BASE_URL . 'uploads/');
define('HASH_COST', 10);

// Configuración de correo para 2FA
if (!defined('MAIL_HOST')) {
    define('MAIL_HOST', 'smtp.gmail.com');
    define('MAIL_SMTP_AUTH', true);
    define('MAIL_USERNAME', 'jf7454669@gmail.com');
    define('MAIL_PASSWORD', 'uzkt nhcr qhjf gjlw');
    define('MAIL_ENCRYPTION', 'tls');
    define('MAIL_PORT', 587);
    define('MAIL_FROM', 'jf7454669@gmail.com');
    define('MAIL_FROM_NAME', NOMBRE_TIENDA);
}
?>
