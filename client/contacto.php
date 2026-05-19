<?php
session_start();
require '../config/db.php';

$success = '';
$errors = [];
$nombre = '';
$correo = '';
$asunto = '';
$mensaje = '';
$telefono = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $asunto = trim($_POST['asunto'] ?? '');
    $mensaje = trim($_POST['mensaje'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');

    if ($nombre === '') {
        $errors[] = 'El nombre es obligatorio.';
    }
    if ($correo === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Debes ingresar un correo válido.';
    }
    if ($asunto === '') {
        $errors[] = 'El asunto es obligatorio.';
    }
    if ($mensaje === '') {
        $errors[] = 'El mensaje no puede estar vacío.';
    }
    if (strlen($mensaje) < 10) {
        $errors[] = 'El mensaje debe tener al menos 10 caracteres.';
    }

    if (empty($errors)) {
        $fecha = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'N/A';
        $usuario_id = $_SESSION['id_usuario'] ?? 'Anónimo';

        $registro = "[$fecha] ID Usuario: $usuario_id | Nombre: $nombre | Correo: $correo | Teléfono: $telefono | IP: $ip\nAsunto: $asunto\nMensaje: $mensaje\n---\n";
        $archivo = RUTA_UPLOADS . 'contact_messages.txt';

        if (file_put_contents($archivo, $registro, FILE_APPEND | LOCK_EX) !== false) {
            $success = '✅ Gracias por contactarnos. Tu mensaje ha sido enviado correctamente. Te responderemos pronto.';
            $nombre = $correo = $asunto = $mensaje = $telefono = '';
        } else {
            $errors[] = 'No se pudo guardar el mensaje. Intenta de nuevo más tarde.';
        }
    }
}
?>
<?php include '../includes/header.php'; ?>

<style>
    .contact-hero {
        background: linear-gradient(135deg, rgba(98, 0, 255, 0.15) 0%, rgba(0, 212, 255, 0.1) 100%);
        border: 2px solid rgba(0, 212, 255, 0.3);
        border-radius: 20px;
        padding: 50px 30px;
        margin-bottom: 40px;
        text-align: center;
        box-shadow: 0 0 30px rgba(98, 0, 255, 0.2);
    }

    .contact-hero h1 {
        color: #00d4ff;
        font-size: 2.8rem;
        font-weight: 900;
        margin-bottom: 15px;
        text-shadow: 0 0 20px rgba(0, 212, 255, 0.4);
    }

    .contact-info-card {
        background: rgba(26, 26, 46, 0.95);
        border: 2px solid rgba(0, 212, 255, 0.2);
        border-radius: 16px;
        padding: 30px;
        height: 100%;
        transition: all 0.3s ease;
    }

    .contact-info-card:hover {
        border-color: #00d4ff;
        box-shadow: 0 0 20px rgba(0, 212, 255, 0.2);
        transform: translateY(-5px);
    }

    .contact-info-card h4 {
        color: #00d4ff;
        margin-bottom: 20px;
        font-weight: 700;
    }

    .info-item {
        margin-bottom: 20px;
        padding-bottom: 20px;
        border-bottom: 1px solid rgba(0, 212, 255, 0.1);
    }

    .info-item:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }

    .info-icon {
        color: #00d4ff;
        font-size: 1.3rem;
        margin-right: 10px;
    }

    .form-card {
        background: rgba(18, 18, 42, 0.96);
        border: 2px solid rgba(0, 212, 255, 0.2);
        border-radius: 16px;
        padding: 40px;
    }

    .form-card h3 {
        color: #00d4ff;
        margin-bottom: 30px;
        font-weight: 700;
    }

    .form-control {
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(0, 212, 255, 0.2);
        color: #fff;
        padding: 12px 15px;
        transition: all 0.3s ease;
    }

    .form-control:focus {
        background: rgba(255, 255, 255, 0.12);
        border-color: #00d4ff;
        box-shadow: 0 0 0 0.3rem rgba(0, 212, 255, 0.1);
        color: #fff;
    }

    .form-label {
        color: #cbd5e1;
        font-weight: 600;
        margin-bottom: 8px;
    }

    .btn-submit {
        background: linear-gradient(135deg, #6200ff 0%, #8a2be2 100%);
        border: 2px solid #00d4ff;
        color: #fff;
        font-weight: 700;
        padding: 14px 40px;
        border-radius: 8px;
        transition: all 0.3s ease;
        width: 100%;
    }

    .btn-submit:hover {
        background: linear-gradient(135deg, #8a2be2 0%, #6200ff 100%);
        box-shadow: 0 0 20px rgba(0, 212, 255, 0.4);
        transform: translateY(-2px);
        color: #fff;
        text-decoration: none;
    }

    .alert {
        border-radius: 12px;
        border: 1px solid;
        animation: slideIn 0.3s ease;
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .alert-success {
        background: rgba(16, 185, 129, 0.12);
        border-color: rgba(16, 185, 129, 0.35);
        color: #a7f3d0;
    }

    .alert-danger {
        background: rgba(248, 113, 113, 0.12);
        border-color: rgba(248, 113, 113, 0.35);
        color: #fecaca;
    }

    .social-links {
        display: flex;
        gap: 15px;
        margin-top: 20px;
    }

    .social-links a {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 45px;
        height: 45px;
        border-radius: 50%;
        background: rgba(0, 212, 255, 0.1);
        border: 2px solid rgba(0, 212, 255, 0.3);
        color: #00d4ff;
        transition: all 0.3s ease;
    }

    .social-links a:hover {
        background: rgba(0, 212, 255, 0.2);
        border-color: #00d4ff;
        transform: translateY(-3px);
    }
</style>

<div class="contact-hero">
    <h1><i class="fas fa-envelope"></i> Ponte en Contacto</h1>
    <p style="color: #cbd5e1; font-size: 1.1rem; margin: 0;">¿Tienes preguntas, sugerencias o necesitas ayuda? Nos encantaría escucharte.</p>
</div>

<div class="row gy-4 mb-5">
    <!-- Información de Contacto -->
    <div class="col-lg-4">
        <div class="contact-info-card">
            <h4><i class="fas fa-info-circle"></i> Información</h4>

            <div class="info-item">
                <div style="display: flex; align-items: flex-start;">
                    <i class="fas fa-envelope info-icon"></i>
                    <div>
                        <div style="color: #fff; font-weight: 600; margin-bottom: 5px;">Correo</div>
                        <a href="mailto:<?php echo htmlspecialchars(EMAIL_TIENDA); ?>" style="color: #cbd5e1; text-decoration: none;">
                            <?php echo htmlspecialchars(EMAIL_TIENDA); ?>
                        </a>
                    </div>
                </div>
            </div>

            <div class="info-item">
                <div style="display: flex; align-items: flex-start;">
                    <i class="fas fa-phone info-icon"></i>
                    <div>
                        <div style="color: #fff; font-weight: 600; margin-bottom: 5px;">Teléfono</div>
                        <a href="tel:<?php echo htmlspecialchars(TELEFONO_TIENDA); ?>" style="color: #cbd5e1; text-decoration: none;">
                            <?php echo htmlspecialchars(TELEFONO_TIENDA); ?>
                        </a>
                    </div>
                </div>
            </div>

            <div class="info-item">
                <div style="display: flex; align-items: flex-start;">
                    <i class="fas fa-map-marker-alt info-icon"></i>
                    <div>
                        <div style="color: #fff; font-weight: 600; margin-bottom: 5px;">Ubicación</div>
                        <p style="color: #cbd5e1; margin: 0;">Cochabamba, Bolivia</p>
                    </div>
                </div>
            </div>

            <div class="info-item">
                <div style="display: flex; align-items: flex-start;">
                    <i class="fas fa-clock info-icon"></i>
                    <div>
                        <div style="color: #fff; font-weight: 600; margin-bottom: 5px;">Horario</div>
                        <p style="color: #cbd5e1; margin: 0;">Lunes a Viernes: 9:00 AM - 6:00 PM</p>
                        <p style="color: #cbd5e1; margin: 0;">Sábados: 10:00 AM - 4:00 PM</p>
                    </div>
                </div>
            </div>

            <a href="<?php echo BASE_URL; ?>client/historia.php" class="btn btn-outline-info w-100 mt-4">
                <i class="fas fa-history me-2"></i> Conoce nuestra Historia
            </a>
        </div>
    </div>

    <!-- Formulario de Contacto -->
    <div class="col-lg-8">
        <div class="form-card">
            <h3><i class="fas fa-paper-plane"></i> Envía tu Mensaje</h3>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><strong>Por favor, corrige los siguientes errores:</strong>
                    <ul style="margin: 10px 0 0 0; padding-left: 25px;">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" novalidate>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="nombre">
                            <i class="fas fa-user me-2"></i>Nombre Completo
                        </label>
                        <input type="text" class="form-control" id="nombre" name="nombre"
                               value="<?php echo htmlspecialchars($nombre); ?>"
                               placeholder="Tu nombre completo" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="correo">
                            <i class="fas fa-envelope me-2"></i>Correo Electrónico
                        </label>
                        <input type="email" class="form-control" id="correo" name="correo"
                               value="<?php echo htmlspecialchars($correo); ?>"
                               placeholder="tucorreo@ejemplo.com" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="telefono">
                            <i class="fas fa-phone me-2"></i>Teléfono (Opcional)
                        </label>
                        <input type="tel" class="form-control" id="telefono" name="telefono"
                               value="<?php echo htmlspecialchars($telefono); ?>"
                               placeholder="+57 300 000 0000">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="asunto">
                            <i class="fas fa-tag me-2"></i>Asunto
                        </label>
                        <select class="form-control" id="asunto" name="asunto" required>
                            <option value="">Selecciona un asunto...</option>
                            <option value="Consulta General" <?php echo ($asunto === 'Consulta General') ? 'selected' : ''; ?>>Consulta General</option>
                            <option value="Duda sobre Producto" <?php echo ($asunto === 'Duda sobre Producto') ? 'selected' : ''; ?>>Duda sobre Producto</option>
                            <option value="Problema con Compra" <?php echo ($asunto === 'Problema con Compra') ? 'selected' : ''; ?>>Problema con Compra</option>
                            <option value="Sugerencia" <?php echo ($asunto === 'Sugerencia') ? 'selected' : ''; ?>>Sugerencia</option>
                            <option value="Otro" <?php echo ($asunto === 'Otro') ? 'selected' : ''; ?>>Otro</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="mensaje">
                        <i class="fas fa-message me-2"></i>Mensaje
                    </label>
                    <textarea class="form-control" id="mensaje" name="mensaje" rows="8"
                              placeholder="Déjanos tu mensaje aquí... (mínimo 10 caracteres)" required><?php echo htmlspecialchars($mensaje); ?></textarea>
                    <small style="color: #cbd5e1;">Mínimo 10 caracteres</small>
                </div>

                <button type="submit" class="btn btn-submit">
                    <i class="fas fa-paper-plane me-2"></i>Enviar Mensaje
                </button>
            </form>

            <div style="margin-top: 30px; padding-top: 30px; border-top: 1px solid rgba(0, 212, 255, 0.1);">
                <p style="color: #cbd5e1; margin-bottom: 15px;">
                    <i class="fas fa-lightbulb me-2"></i><strong>Consejo:</strong> Nos esforzamos por responder todos los mensajes dentro de 24-48 horas.
                </p>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

