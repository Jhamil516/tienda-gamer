<?php
session_start();
require_once __DIR__ . '/admin_header.php';

if (!es_admin()) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

$mensaje_error = '';
$mensaje_exito = '';
$nombre = '';
$correo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = sanitizar_entrada($_POST['nombre'] ?? '');
    $correo = sanitizar_entrada($_POST['correo'] ?? '');
    $contraseña = $_POST['contraseña'] ?? '';
    $confirmar = $_POST['confirmar_contraseña'] ?? '';

    if (!$nombre || !$correo || !$contraseña || !$confirmar) {
        $mensaje_error = 'Completa todos los campos';
    } elseif (!validar_email($correo)) {
        $mensaje_error = 'Ingresa un correo válido';
    } elseif (!validar_contraseña($contraseña)) {
        $mensaje_error = 'La contraseña debe tener al menos 8 caracteres, una mayúscula, una minúscula y un número';
    } elseif ($contraseña !== $confirmar) {
        $mensaje_error = 'Las contraseñas no coinciden';
    } else {
        $correo_sanitizado = sanitizar_sql($conn, $correo);
        $result = $conn->query("SELECT id_usuario FROM usuarios WHERE correo = '$correo_sanitizado'");

        if ($result && $result->num_rows > 0) {
            $mensaje_error = 'El correo ya está registrado';
        } else {
            $nombre_sanitizado = sanitizar_sql($conn, $nombre);
            $contraseña_hash = password_hash($contraseña, PASSWORD_BCRYPT, ['cost' => HASH_COST]);
            $query = "INSERT INTO usuarios (nombre, correo, contraseña, rol) VALUES ('$nombre_sanitizado', '$correo_sanitizado', '$contraseña_hash', 'empleado')";

            if ($conn->query($query)) {
                $mensaje_exito = 'Empleado creado correctamente.';
                $nombre = '';
                $correo = '';
                header('Refresh: 2; url=empleados.php');
            } else {
                $mensaje_error = 'Error al crear el empleado. Intenta nuevamente.';
            }
        }
    }
}

admin_render_header('Crear Empleado', 'Agregar Empleado', 'fas fa-user-plus');
?>
    <style>
        .form-container {
            max-width: 700px;
            margin: 0 auto;
        }

        .form-card {
            background: rgba(26, 26, 46, 0.9);
            border: 1px solid rgba(98, 0, 255, 0.35);
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.18);
        }

        .form-card .section-title {
            margin-bottom: 24px;
        }

        .form-label {
            color: #cbd5e1;
            font-weight: 600;
        }

        .form-control {
            background: rgba(15, 23, 42, 0.9);
            border: 1px solid rgba(148, 163, 184, 0.35);
            color: #f8fafc;
        }

        .form-control:focus {
            border-color: #00d4ff;
            box-shadow: 0 0 0 0.2rem rgba(0, 212, 255, 0.25);
        }

        .btn-primary {
            background: #00d4ff;
            border-color: #00d4ff;
            color: #0b0c10;
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.12);
            color: #f8fafc;
        }
    </style>
<div class="section form-container">
    <div class="form-card">
        <div class="section-title">
            <i class="fas fa-user-plus"></i> Crear nuevo empleado
        </div>
        <?php if ($mensaje_error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($mensaje_error); ?>
            </div>
        <?php endif; ?>
        <?php if ($mensaje_exito): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($mensaje_exito); ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label" for="nombre">Nombre completo</label>
                <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo htmlspecialchars($nombre); ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label" for="correo">Correo electrónico</label>
                <input type="email" class="form-control" id="correo" name="correo" value="<?php echo htmlspecialchars($correo); ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label" for="contraseña">Contraseña</label>
                <input type="password" class="form-control" id="contraseña" name="contraseña" required>
            </div>

            <div class="mb-3">
                <label class="form-label" for="confirmar_contraseña">Confirmar contraseña</label>
                <input type="password" class="form-control" id="confirmar_contraseña" name="confirmar_contraseña" required>
            </div>

            <div class="d-flex justify-content-between align-items-center">
                <a href="empleados.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Guardar Empleado
                </button>
            </div>
        </form>
    </div>
</div>
<?php admin_render_footer(); ?>
