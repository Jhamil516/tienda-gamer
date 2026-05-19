<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/funciones.php';

$error = '';
$success = '';
$correo = sanitizar_entrada($_GET['correo'] ?? $_POST['correo'] ?? '');
$motivo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $motivo = sanitizar_entrada($_POST['motivo'] ?? '');

    if (!$correo || !validar_email($correo)) {
        $error = 'Ingresa un correo válido para enviar la apelación.';
    } elseif (empty($motivo)) {
        $error = 'Describe brevemente por qué consideras que tu cuenta debe ser revisada.';
    } else {
        $correo_sql = sanitizar_sql($conn, $correo);
        $result = $conn->query("SELECT id_usuario, nombre, activo FROM usuarios WHERE correo = '$correo_sql' LIMIT 1");

        if (!$result || $result->num_rows === 0) {
            $error = 'No se encontró una cuenta con ese correo.';
        } else {
            $usuario = $result->fetch_assoc();
            if ($usuario['activo'] == 1) {
                $error = 'Tu cuenta no está desactivada. No es necesario apelar.';
            } else {
                $mensaje = "El usuario {$usuario['nombre']} ({$correo}) solicita una apelación de baneo. Motivo: $motivo";
                $admins = $conn->query("SELECT id_usuario, nombre, correo FROM usuarios WHERE rol = 'admin' AND activo = 1");
                $emails_enviados = [];
                $email_error = '';

                if (!$admins || $admins->num_rows === 0) {
                    $emails_enviados[] = EMAIL_TIENDA;
                }

                if ($admins && $admins->num_rows > 0) {
                    while ($admin = $admins->fetch_assoc()) {
                        registrar_notificacion($conn, $admin['id_usuario'], 'Solicitud de apelación de baneo', $mensaje, 'advertencia');
                        $emails_enviados[] = $admin['correo'];
                    }
                }

                $emails_enviados = array_unique(array_filter($emails_enviados));
                foreach ($emails_enviados as $email_admin) {
                    $mail_result = enviar_correo(
                        $email_admin,
                        'Administrador',
                        'Nueva apelación de baneo recibida',
                        "<p>Se ha recibido una apelación de baneo de <strong>{$usuario['nombre']}</strong> ({$correo}).</p><p><strong>Motivo:</strong> " . nl2br(htmlspecialchars($motivo)) . "</p>",
                        "Se ha recibido una apelación de baneo de {$usuario['nombre']} ({$correo}). Motivo: {$motivo}"
                    );

                    if ($mail_result !== true) {
                        $email_error = $mail_result;
                    }
                }

                $success = 'Tu apelación ha sido enviada. Un administrador revisará tu cuenta y se pondrá en contacto contigo si es necesario.';
                if ($email_error) {
                    $success .= ' No fue posible notificar por correo electrónico: ' . htmlspecialchars($email_error);
                }
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
    <title>Apelar Baneo - GAMER FRIKI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); min-height: 100vh; color: #fff; display: flex; align-items: center; justify-content: center; }
        .appeal-card { background: rgba(15,23,42,0.92); border: 1px solid rgba(96,165,250,0.28); border-radius: 18px; padding: 35px; width: 100%; max-width: 520px; }
        .appeal-card h1 { color: #60a5fa; font-size: 2rem; margin-bottom: 0.5rem; }
        .appeal-card p { color: #cbd5e1; }
        .form-control, .form-control:focus { background: rgba(71,85,105,0.25); border: 1px solid rgba(96,165,250,0.45); color: #fff; }
        .form-control::placeholder { color: rgba(226,232,240,0.7); }
        .btn-primary { background: #60a5fa; border-color: #60a5fa; }
        .alert { border-radius: 12px; }
        a { color: #93c5fd; }
    </style>
</head>
<body>
    <div class="appeal-card">
        <h1>Apelar baneo</h1>
        <p>Si tu cuenta ha sido desactivada, puedes enviar esta solicitud para que los administradores la revisen.</p>

        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error); ?></div>
        <?php elseif ($success): ?>
            <div class="alert alert-success" role="alert"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if (!$success): ?>
        <form method="POST">
            <input type="hidden" name="correo" value="<?php echo htmlspecialchars($correo); ?>">
            <div class="mb-3">
                <label for="correo" class="form-label">Correo electrónico</label>
                <input type="email" class="form-control" id="correo" name="correo" value="<?php echo htmlspecialchars($correo); ?>" required>
            </div>
            <div class="mb-3">
                <label for="motivo" class="form-label">Motivo de apelación</label>
                <textarea class="form-control" id="motivo" name="motivo" rows="5" placeholder="Explícanos brevemente por qué quieres que revisemos tu cuenta." required><?php echo htmlspecialchars($motivo); ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary w-100">Enviar apelación</button>
        </form>
        <?php endif; ?>

        <div class="mt-3 text-center">
            <a href="<?php echo BASE_URL; ?>auth/login.php">Volver al inicio de sesión</a>
        </div>
    </div>
</body>
</html>
