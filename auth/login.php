<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/funciones.php';

if (es_autenticado()) {
    if ($_SESSION['rol'] === 'admin') {
        header('Location: ' . BASE_URL . 'admin/dashboard.php');
    } elseif ($_SESSION['rol'] === 'empleado') {
        header('Location: ' . BASE_URL . 'empleado/dashboard.php');
    } else {
        header('Location: ' . BASE_URL . 'client/catalogo.php');
    }
    exit;
}

$error = '';
$appeal_url = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo    = sanitizar_entrada($_POST['correo'] ?? '');
    $contraseña = $_POST['contraseña'] ?? '';

    $resultado = verificar_login($conn, $correo, $contraseña);

    if (isset($resultado['error'])) {
        if ($resultado['error'] === 'baneado') {
            $error = $resultado['mensaje'] ?? 'Tu cuenta ha sido desactivada.';
            $appeal_url = BASE_URL . 'auth/apelar_baneo.php?correo=' . urlencode($correo);
        } else {
            $error = $resultado['error'];
        }
    } else {
        $usuario = $resultado['usuario'];

        // Generar y guardar código 2FA para iniciar sesión por correo
        $codigo = generar_codigo_2fa();
        guardar_codigo_2fa($conn, $usuario['id_usuario'], $codigo);

        $envio_resultado = enviar_codigo_2fa($usuario['correo'], $usuario['nombre'], $codigo);
        if ($envio_resultado !== true) {
            $error = $envio_resultado;
        } else {
            $_SESSION['temp_user_id'] = $usuario['id_usuario'];
            $_SESSION['temp_user_role'] = $usuario['rol'];
            header('Location: ' . BASE_URL . 'auth/verificar_2fa.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - GAMER FRIKI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {--primary: #7c3aed; --secondary: #06b6d4; --dark: #0f172a; --accent: #ec4899;}
        body {background: linear-gradient(135deg, var(--dark) 0%, #1e293b 100%); min-height: 100vh; display: flex; align-items: center; color: #fff;}
        .login-container {background: rgba(15,23,42,0.8); border: 2px solid var(--primary); border-radius: 15px; padding: 40px; max-width: 450px; margin: auto; box-shadow: 0 0 30px rgba(124,58,237,0.3);}
        .login-header {text-align: center; margin-bottom: 30px;}
        .login-header h1 {font-size: 2.5rem; background: linear-gradient(135deg, var(--secondary), var(--accent)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; font-weight: bold;}
        .form-control {background: rgba(71,85,105,0.3); border: 1px solid var(--primary); color: #fff; border-radius: 8px; padding: 12px 15px; margin-bottom: 15px;}
        .form-control:focus {background: rgba(71,85,105,0.5); border-color: var(--secondary); color: #fff; box-shadow: 0 0 10px rgba(6,182,212,0.3);}
        .btn-login {background: linear-gradient(135deg, var(--primary), var(--accent)); border: none; color: #fff; padding: 12px; font-weight: bold; border-radius: 8px; width: 100%;}
        .alert {border-radius: 8px; border: none; background: rgba(239,68,68,0.2); color: #fca5a5;}
        a {color: var(--secondary);} a:hover {color: var(--accent);}
        .btn-toggle-password {position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--secondary); cursor: pointer; font-size: 1.1rem;}
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="login-header">
                <h1><i class="fas fa-gamepad"></i> GAMER FRIKI</h1>
                <p>Sube de nivel</p>
            </div>
            <?php if ($error): ?>
            <div class="alert"><?php echo htmlspecialchars($error);
                if (!empty($appeal_url)): ?>
                    <br><a href="<?php echo htmlspecialchars($appeal_url); ?>" class="text-white fw-bold">Apelar mi baneo</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <form method="POST">
                <input type="email" class="form-control" name="correo" placeholder="Correo electrónico" required autocomplete="email">
                <div style="position: relative; margin-bottom: 15px;">
                    <input type="password" class="form-control" id="pwd" name="contraseña" placeholder="Contraseña" required style="padding-right: 40px; margin-bottom: 0;">
                    <button type="button" class="btn-toggle-password" onclick="togglePwd()">
                        <i class="fas fa-eye" id="toggleIcon"></i>
                    </button>
                </div>
                <button type="submit" class="btn btn-login">INGRESAR</button>
            </form>
            <div style="text-align: center; margin-top: 20px;">
                <p>¿No tienes cuenta? <a href="registro.php">Regístrate</a></p>
                <p><a href="<?php echo BASE_URL; ?>">Volver al inicio</a></p>
            </div>
        </div>
    </div>
    <script>
        function togglePwd() {
            const input = document.getElementById('pwd');
            const icon = document.getElementById('toggleIcon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
