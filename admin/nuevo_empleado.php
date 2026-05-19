<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/funciones.php';

if (!es_admin()) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

$error = '';
$exito = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = sanitizar_sql($conn, $_POST['nombre'] ?? '');
    $correo = sanitizar_sql($conn, $_POST['correo'] ?? '');
    $contraseña = $_POST['contraseña'] ?? '';
    $puesto = sanitizar_sql($conn, $_POST['puesto'] ?? '');
    $departamento = sanitizar_sql($conn, $_POST['departamento'] ?? '');
    $fecha_contratacion = sanitizar_sql($conn, $_POST['fecha_contratacion'] ?? '');

    if (empty($nombre) || empty($correo)) {
        $error = 'Nombre y correo son requeridos';
    } elseif (strlen($contraseña) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres';
    } else {
        $contraseña_hash = password_hash($contraseña, PASSWORD_BCRYPT, ['cost' => 10]);
        
        // Crear usuario
        $query = "INSERT INTO usuarios (nombre, correo, contraseña, rol, activo) 
                  VALUES ('$nombre', '$correo', '$contraseña_hash', 'empleado', 1)";
        
        if ($conn->query($query)) {
            $id_usuario = $conn->insert_id;
            
            // Crear registro de empleado
            $query_emp = "INSERT INTO empleados (id_usuario, nombre_completo, puesto, departamento, fecha_contratacion, activo)
                          VALUES ($id_usuario, '$nombre', '$puesto', '$departamento', '$fecha_contratacion', 1)";
            $conn->query($query_emp);
            
            $exito = 'Empleado creado correctamente';
            header('Refresh: 2; url=empleados_crud.php');
        } else {
            $error = 'Error al crear el empleado: ' . $conn->error;
        }
    }
}

require_once __DIR__ . '/admin_header.php';
admin_render_header('Nuevo Empleado', 'Empleados', 'fas fa-briefcase');
?>

<style>
    .section { background: rgba(26, 26, 46, 0.95); border: 1px solid rgba(98, 0, 255, 0.35); border-radius: 16px; padding: 25px; max-width: 600px; }
    .form-group { margin-bottom: 15px; }
    label { color: #00d4ff; font-weight: bold; margin-bottom: 5px; display: block; }
    input, textarea, select { background: rgba(255, 255, 255, 0.9) !important; color: #0f172a !important; border: 1px solid rgba(98, 0, 255, 0.3) !important; padding: 10px !important; border-radius: 5px !important; width: 100% !important; }
    input:focus, textarea:focus, select:focus { border-color: #00d4ff !important; outline: none !important; }
</style>

<div class="section">
    <h3 style="color: #00d4ff; margin-bottom: 20px;"><i class="fas fa-plus"></i> Registrar Nuevo Empleado</h3>
    
    <?php if ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($exito): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($exito); ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label for="nombre">Nombre Completo *</label>
            <input type="text" id="nombre" name="nombre" required placeholder="Juan Pérez">
        </div>

        <div class="form-group">
            <label for="correo">Correo *</label>
            <input type="email" id="correo" name="correo" required placeholder="juan@email.com">
        </div>

        <div class="form-group">
            <label for="contraseña">Contraseña *</label>
            <input type="password" id="contraseña" name="contraseña" required placeholder="Mínimo 6 caracteres">
        </div>

        <div class="form-group">
            <label for="puesto">Puesto</label>
            <select id="puesto" name="puesto">
                <option value="">Seleccionar puesto</option>
                <option value="Vendedor">Vendedor</option>
                <option value="Gerente">Gerente</option>
                <option value="Soporte">Soporte</option>
                <option value="Logística">Logística</option>
            </select>
        </div>

        <div class="form-group">
            <label for="departamento">Departamento</label>
            <input type="text" id="departamento" name="departamento" placeholder="Ventas">
        </div>

        <div class="form-group">
            <label for="fecha_contratacion">Fecha de Contratación</label>
            <input type="date" id="fecha_contratacion" name="fecha_contratacion">
        </div>

        <button type="submit" class="btn btn-success"><i class="fas fa-check"></i> Crear Empleado</button>
        <a href="empleados_crud.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancelar</a>
    </form>
</div>

<?php admin_render_footer(); ?>
