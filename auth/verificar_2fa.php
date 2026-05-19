<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/funciones.php';

if (!isset($_SESSION['temp_user_id'])) {
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit;
}

$error = '';
$exito = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo = sanitizar_entrada($_POST['codigo_2fa'] ?? '');

    if (verificar_codigo_2fa($conn, $_SESSION['temp_user_id'], $codigo)) {
        $usuario_id = $_SESSION['temp_user_id'];
        $resultado = $conn->query("SELECT id_usuario, nombre, correo, rol FROM usuarios WHERE id_usuario = $usuario_id");
        $usuario = $resultado->fetch_assoc();

        iniciar_sesion_usuario($usuario['id_usuario'], $usuario['nombre'], $usuario['correo'], $usuario['rol']);
        limpiar_2fa($conn, $usuario_id);

        // Limpiar variables temporales
        unset($_SESSION['temp_user_id']);
        unset($_SESSION['temp_user_role']);
        unset($_SESSION['codigo_2fa_generado']);

        if ($usuario['rol'] === 'admin') {
            header('Location: ' . BASE_URL . 'admin/dashboard.php');
        } elseif ($usuario['rol'] === 'empleado') {
            header('Location: ' . BASE_URL . 'empleado/dashboard.php');
        } else {
            header('Location: ' . BASE_URL . 'client/catalogo.php');
        }
        exit;
    } else {
        $error = 'Código 2FA inválido';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación 2FA - GAMER FRIKI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {--primary: #6200ff; --accent: #00d4ff; --dark-bg: #0a0a0a;}
        body {background: linear-gradient(135deg, var(--dark-bg) 0%, #1a0033 100%); min-height: 100vh; display: flex; align-items: center; color: #fff;}
        .container-2fa {background: rgba(10, 10, 10, 0.9); border: 3px solid var(--primary); border-radius: 15px; padding: 40px; max-width: 450px; margin: auto; box-shadow: 0 0 40px rgba(98, 0, 255, 0.4);}
        h1 {color: var(--accent); text-shadow: 0 0 20px rgba(0, 212, 255, 0.5); margin-bottom: 20px; text-align: center;}
        .form-control {background: rgba(71, 85, 105, 0.3); border: 2px solid #333; color: #fff; border-radius: 8px; padding: 12px; text-align: center; font-size: 1.5rem; letter-spacing: 2px;}
        .form-control:focus {border-color: var(--accent); box-shadow: 0 0 15px rgba(0, 212, 255, 0.3);}
        .btn-verify {background: linear-gradient(135deg, var(--primary), var(--accent)); border: none; color: #fff; padding: 12px; font-weight: bold; border-radius: 8px; width: 100%; margin-top: 20px;}
        .btn-verify:hover {box-shadow: 0 0 20px rgba(98, 0, 255, 0.5);}
        .alert {border-radius: 8px; margin-bottom: 20px;}
        .info-text {text-align: center; font-size: 0.9rem; color: #aaa; margin-top: 20px;}
    </style>
</head>
<body>
    <div class="container-2fa">
        <h1><i class="fas fa-lock"></i> Verificación 2FA</h1>
        <p style="text-align: center; color: #ccc;">Ingresa el código de 6 dígitos</p>

        <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="text" class="form-control" name="codigo_2fa" placeholder="000000" maxlength="6" inputmode="numeric" required autofocus>
            <button type="submit" class="btn btn-verify">VERIFICAR</button>
        </form>

        <div class="info-text">
            <p>Se ha enviado un código a tu correo registrado</p>
            <a href="login.php" style="color: var(--accent); text-decoration: none;"><i class="fas fa-arrow-left"></i> Volver al login</a>
        </div>
    </div>
</body>
</html>
