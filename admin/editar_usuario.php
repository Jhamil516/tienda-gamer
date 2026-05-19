<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/funciones.php';

if (!es_admin()) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

require_once __DIR__ . '/admin_header.php';

$id = intval($_GET['id'] ?? $_POST['id'] ?? 0);
$usuario = null;
$mensaje_error = '';
$mensaje_exito = '';

if ($id > 0) {
    $result = $conn->query("SELECT * FROM usuarios WHERE id_usuario = $id");
    if ($result && $result->num_rows > 0) {
        $usuario = $result->fetch_assoc();
    }
}

if (!$usuario) {
    header('Location: usuarios.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = sanitizar_entrada($_POST['nombre'] ?? '');
    $correo = sanitizar_entrada($_POST['correo'] ?? '');
    $rol = in_array($_POST['rol'] ?? '', ['admin', 'empleado', 'cliente']) ? $_POST['rol'] : $usuario['rol'];
    $activo = isset($_POST['activo']) ? 1 : 0;
    $confirmar_contraseña_admin = trim($_POST['confirmar_contraseña_admin'] ?? '');
    $rol_anterior = $usuario['rol'];
    $requiere_validacion_password = $rol_anterior === 'admin' || $rol !== $rol_anterior;

    if (!$nombre || !$correo) {
        $mensaje_error = 'Completa el nombre y el correo.';
    } elseif (!validar_email($correo)) {
        $mensaje_error = 'Ingresa un correo válido.';
    } elseif ($requiere_validacion_password && !$confirmar_contraseña_admin) {
        $mensaje_error = 'Debes ingresar la contraseña del administrador para cambiar el rol o editar un usuario administrador.';
    } else {
        if ($requiere_validacion_password) {
            $admin_actual_id = intval($_SESSION['id_usuario']);
            $admin_actual = $conn->query("SELECT contraseña FROM usuarios WHERE id_usuario = $admin_actual_id")->fetch_assoc();

            if (!$admin_actual || !password_verify($confirmar_contraseña_admin, $admin_actual['contraseña'])) {
                $mensaje_error = 'Contraseña inválida. No se puede aplicar los cambios.';
            }
        }

        if (!$mensaje_error) {
            $correo_sanitizado = sanitizar_sql($conn, $correo);
            $validar_email_existente = $conn->query("SELECT id_usuario FROM usuarios WHERE correo = '$correo_sanitizado' AND id_usuario != $id");

            if ($validar_email_existente && $validar_email_existente->num_rows > 0) {
                $mensaje_error = 'El correo ya está registrado en otro usuario.';
            } else {
                $nombre_sanitizado = sanitizar_sql($conn, $nombre);
                $query = "UPDATE usuarios SET nombre = '$nombre_sanitizado', correo = '$correo_sanitizado', rol = '$rol', activo = $activo WHERE id_usuario = $id";

                if ($conn->query($query)) {
                    $mensaje_exito = 'Usuario actualizado correctamente.';
                    $usuario['nombre'] = $nombre;
                    $usuario['correo'] = $correo;
                    $usuario['rol'] = $rol;
                    $usuario['activo'] = $activo;
                } else {
                    $mensaje_error = 'Error al actualizar el usuario. Intenta nuevamente.';
                }
            }
        }
    }
}

admin_render_header('Editar Usuario', 'Gestión de Usuarios', 'fas fa-user-edit');
?>
    <style>
        .form-container {
            max-width: 760px;
            margin: 0 auto;
        }

        .form-card {
            background: rgba(248, 250, 252, 0.95);
            border: 1px solid rgba(148, 163, 184, 0.35);
            border-radius: 16px;
            padding: 30px;
        }

        .form-label {
            font-weight: 600;
            color: #0f172a;
        }

        .form-control,
        .form-select {
            background: #ffffff;
            border: 1px solid rgba(148, 163, 184, 0.8);
            color: #0f172a;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #6200ff;
            box-shadow: 0 0 0 0.2rem rgba(98, 0, 255, 0.25);
        }

        .btn-primary {
            background: #00d4ff;
            border-color: #00d4ff;
            color: #0a0a0a;
        }
    </style>

<div class="section form-container">
    <div class="form-card">
        <div class="section-title">
            <i class="fas fa-user-edit"></i> Editar Usuario
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
            <input type="hidden" name="id" value="<?php echo intval($usuario['id_usuario']); ?>">

            <div class="mb-3">
                <label class="form-label" for="nombre">Nombre</label>
                <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo htmlspecialchars($usuario['nombre']); ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label" for="correo">Correo</label>
                <input type="email" class="form-control" id="correo" name="correo" value="<?php echo htmlspecialchars($usuario['correo']); ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label" for="rol">Rol</label>
                <select id="rol" name="rol" class="form-select">
                    <option value="admin" <?php echo $usuario['rol'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                    <option value="empleado" <?php echo $usuario['rol'] === 'empleado' ? 'selected' : ''; ?>>Empleado</option>
                    <option value="cliente" <?php echo $usuario['rol'] === 'cliente' ? 'selected' : ''; ?>>Cliente</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label" for="confirmar_contraseña_admin">Contraseña actual del administrador</label>
                <input type="password" class="form-control" id="confirmar_contraseña_admin" name="confirmar_contraseña_admin" placeholder="Introduce tu contraseña para confirmar el cambio de rol o editar un admin">
                <div class="form-text">Solo es necesaria para cambiar el rol o editar un usuario administrador.</div>
            </div>

            <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" value="1" id="activo" name="activo" <?php echo $usuario['activo'] ? 'checked' : ''; ?>>
                <label class="form-check-label" for="activo">
                    Usuario activo
                </label>
            </div>

            <div class="d-flex justify-content-between align-items-center">
                <a href="usuarios.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Guardar cambios
                </button>
            </div>
        </form>
    </div>
</div>
<?php admin_render_footer(); ?>
