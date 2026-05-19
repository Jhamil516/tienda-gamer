<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/funciones.php';

if (es_autenticado()) {
    header('Location: ' . BASE_URL);
    exit;
}

$error = '';
$exito = '';
$nombre = '';
$correo = '';
$habilitar_2fa = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = sanitizar_entrada($_POST['nombre'] ?? '');
    $correo = sanitizar_entrada($_POST['correo'] ?? '');
    $contraseña = $_POST['contraseña'] ?? '';
    $confirmar = $_POST['confirmar_contraseña'] ?? '';
    $habilitar_2fa = isset($_POST['habilitar_2fa']) ? 1 : 0;

    // Validaciones
    if (!$nombre || !$correo || !$contraseña || !$confirmar) {
        $error = 'Completa todos los campos';
    } elseif (strlen($nombre) < 3) {
        $error = 'El nombre debe tener al menos 3 caracteres';
    } elseif (!validar_email($correo)) {
        $error = 'Ingresa un correo válido';
    } elseif (!validar_contraseña($contraseña)) {
        $error = 'La contraseña debe tener: 8+ caracteres, mayúscula, minúscula, número';
    } elseif ($contraseña !== $confirmar) {
        $error = 'Las contraseñas no coinciden';
    } else {
        // Registrar usuario
        $resultado = registrar_usuario($conn, $nombre, $correo, $contraseña, $habilitar_2fa);
        if (isset($resultado['exito'])) {
            $exito = 'Registro exitoso. Ahora puedes iniciar sesión.';
            $nombre = '';
            $correo = '';
            $habilitar_2fa = 0;
            // Redirigir al login después de 2 segundos
            header('Refresh: 2; url=login.php');
        } else {
            $error = $resultado['error'] ?? 'Error al registrar';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Cuenta - Tienda Gamer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #6200ff;
            --accent: #00d4ff;
            --dark-bg: #0a0a0a;
            --card-bg: #1a1a2e;
            --text-light: #e0e0e0;
        }

        body {
            background: linear-gradient(135deg, var(--dark-bg) 0%, #1a0033 100%);
            color: var(--text-light);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .container-register {
            background: rgba(10, 10, 10, 0.9);
            border: 2px solid var(--primary);
            border-radius: 15px;
            padding: 40px;
            max-width: 450px;
            width: 100%;
            box-shadow: 0 0 40px rgba(98, 0, 255, 0.3);
        }

        .register-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .register-header h1 {
            font-size: 2.5rem;
            background: linear-gradient(135deg, var(--accent), #ff006e);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: bold;
            margin: 0;
            text-shadow: 0 0 20px rgba(0, 212, 255, 0.3);
        }

        .register-header p {
            color: #aaa;
            margin-top: 5px;
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            color: var(--text-light);
            font-weight: 500;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-label i {
            color: var(--accent);
            width: 18px;
        }

        .form-control {
            background: rgba(71, 85, 105, 0.3);
            border: 1px solid var(--primary);
            color: white;
            padding: 12px 15px;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .form-control::placeholder {
            color: #888;
        }

        .form-control:focus {
            background: rgba(71, 85, 105, 0.5);
            border-color: var(--accent);
            color: white;
            box-shadow: 0 0 10px rgba(0, 212, 255, 0.3);
            outline: none;
        }

        .strength-indicator {
            margin-top: 8px;
            height: 4px;
            background: #333;
            border-radius: 4px;
            overflow: hidden;
        }

        .strength-bar {
            height: 100%;
            width: 0%;
            background: #dc3545;
            transition: all 0.3s;
            border-radius: 4px;
        }

        .strength-text {
            font-size: 0.8rem;
            color: #aaa;
            margin-top: 4px;
        }

        .btn-register {
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border: none;
            color: white;
            padding: 12px 20px;
            font-weight: bold;
            border-radius: 8px;
            width: 100%;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-register:hover {
            box-shadow: 0 0 20px rgba(98, 0, 255, 0.6);
            transform: translateY(-2px);
            color: white;
        }

        .alert {
            border-radius: 8px;
            border: none;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 15px;
        }

        .alert-danger {
            background: rgba(220, 53, 69, 0.2);
            color: #ff6b6b;
        }

        .alert-success {
            background: rgba(40, 167, 69, 0.2);
            color: #51cf66;
        }

        .alert i {
            font-size: 1.2rem;
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #333;
        }

        .login-link p {
            color: #aaa;
            margin: 0;
            font-size: 0.95rem;
        }

        .login-link a {
            color: var(--accent);
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s;
        }

        .login-link a:hover {
            color: white;
            text-shadow: 0 0 10px rgba(0, 212, 255, 0.3);
        }

        .home-link {
            text-align: center;
            margin-top: 15px;
        }

        .home-link a {
            color: var(--accent);
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .home-link a:hover {
            color: white;
        }

        .requirements {
            background: rgba(98, 0, 255, 0.1);
            border: 1px solid var(--primary);
            border-radius: 8px;
            padding: 12px;
            margin-top: 15px;
            font-size: 0.85rem;
            color: #ccc;
        }

        .requirements li {
            margin-bottom: 5px;
        }

        .requirements i {
            width: 16px;
            color: var(--accent);
            margin-right: 5px;
        }

        .btn-toggle-eye {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--accent);
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .btn-toggle-eye:hover {
            color: #ff006e;
        }

        @media (max-width: 480px) {
            .container-register {
                padding: 30px 20px;
            }

            .register-header h1 {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="container-register">
        <div class="register-header">
            <h1><i class="fas fa-gamepad"></i> GAMER FRIKI</h1>
            <p>Crea tu cuenta para jugar</p>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <span><?php echo htmlspecialchars($error); ?></span>
        </div>
        <?php endif; ?>

        <?php if ($exito): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <span><?php echo htmlspecialchars($exito); ?></span>
        </div>
        <?php endif; ?>

        <form method="POST">
            <!-- Nombre -->
            <div class="form-group">
                <label class="form-label" for="nombre">
                    <i class="fas fa-user"></i> Nombre Completo
                </label>
                <input type="text" class="form-control" id="nombre" name="nombre"
                       value="<?php echo htmlspecialchars($nombre); ?>"
                       placeholder="Tu nombre completo"
                       required>
                <small style="color: #888;">Mínimo 3 caracteres</small>
            </div>

            <!-- Correo -->
            <div class="form-group">
                <label class="form-label" for="correo">
                    <i class="fas fa-envelope"></i> Correo Electrónico
                </label>
                <input type="email" class="form-control" id="correo" name="correo"
                       value="<?php echo htmlspecialchars($correo); ?>"
                       placeholder="tu@email.com"
                       required>
            </div>

            <!-- Contraseña -->
            <div class="form-group">
                <label class="form-label" for="contraseña">
                    <i class="fas fa-lock"></i> Contraseña
                </label>
                <div style="position: relative;">
                    <input type="password" class="form-control" id="contraseña" name="contraseña"
                           placeholder="••••••••"
                           onInput="validarFortaleza(this.value)"
                           style="padding-right: 40px;"
                           required>
                    <button type="button" class="btn-toggle-eye" onclick="toggleContraseña()">
                        <i class="fas fa-eye" id="toggleIcon1"></i>
                    </button>
                </div>
                <div class="strength-indicator">
                    <div class="strength-bar" id="strengthBar"></div>
                </div>
                <div class="strength-text" id="strengthText">Contraseña muy débil</div>

                <ul class="requirements">
                    <li><i class="fas fa-check"></i> Mínimo 8 caracteres</li>
                    <li><i class="fas fa-check"></i> Una letra mayúscula</li>
                    <li><i class="fas fa-check"></i> Una letra minúscula</li>
                    <li><i class="fas fa-check"></i> Un número</li>
                </ul>
            </div>

            <!-- Confirmar Contraseña -->
            <div class="form-group">
                <label class="form-label" for="confirmar_contraseña">
                    <i class="fas fa-lock"></i> Confirmar Contraseña
                </label>
                <div style="position: relative;">
                    <input type="password" class="form-control" id="confirmar_contraseña" name="confirmar_contraseña"
                           placeholder="••••••••"
                           style="padding-right: 40px;"
                           required>
                    <button type="button" class="btn-toggle-eye" onclick="toggleConfirmar()">
                        <i class="fas fa-eye" id="toggleIcon2"></i>
                    </button>
                </div>
            </div>

            <div class="form-group form-check mb-4">
                <input type="checkbox" class="form-check-input" id="habilitar_2fa" name="habilitar_2fa" value="1" <?php echo $habilitar_2fa ? 'checked' : ''; ?>>
                <label class="form-check-label" for="habilitar_2fa">Habilitar 2FA por correo</label>
            </div>

            <!-- Botón Registrar -->
            <button type="submit" class="btn-register">
                <i class="fas fa-user-plus"></i> Crear Cuenta
            </button>
        </form>

        <!-- Enlace a Login -->
        <div class="login-link">
            <p>¿Ya tienes cuenta? <a href="login.php">Inicia sesión aquí</a></p>
        </div>

        <!-- Enlace a Home -->
        <div class="home-link">
            <a href="../index.php">
                <i class="fas fa-arrow-left"></i> Volver al inicio
            </a>
        </div>
    </div>

    <script>
        function validarFortaleza(password) {
            const bar = document.getElementById('strengthBar');
            const text = document.getElementById('strengthText');

            let fortaleza = 0;
            let requisitos = [];

            if (password.length >= 8) fortaleza += 20;
            if (/[a-z]/.test(password)) fortaleza += 20;
            if (/[A-Z]/.test(password)) fortaleza += 20;
            if (/[0-9]/.test(password)) fortaleza += 20;
            if (/[^a-zA-Z0-9]/.test(password)) fortaleza += 20;

            bar.style.width = fortaleza + '%';

            if (fortaleza < 40) {
                bar.style.background = '#dc3545';
                text.textContent = 'Contraseña muy débil';
                text.style.color = '#ff6b6b';
            } else if (fortaleza < 60) {
                bar.style.background = '#ffc107';
                text.textContent = 'Contraseña débil';
                text.style.color = '#ffc107';
            } else if (fortaleza < 80) {
                bar.style.background = '#17a2b8';
                text.textContent = 'Contraseña moderada';
                text.style.color = '#00d4ff';
            } else {
                bar.style.background = '#28a745';
                text.textContent = 'Contraseña fuerte';
                text.style.color = '#51cf66';
            }
        }

        function toggleContraseña() {
            const input = document.getElementById('contraseña');
            const icon = document.getElementById('toggleIcon1');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        function toggleConfirmar() {
            const input = document.getElementById('confirmar_contraseña');
            const icon = document.getElementById('toggleIcon2');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
