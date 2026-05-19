<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/funciones.php';

if (!es_admin()) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

$id = intval($_GET['id'] ?? 0);
$empleado = null;

if ($id > 0) {
    $result = $conn->query("SELECT u.*, e.puesto, e.departamento, e.salario FROM usuarios u 
                            LEFT JOIN empleados e ON u.id_usuario = e.id_usuario 
                            WHERE u.id_usuario = $id AND u.rol = 'empleado'");
    if ($result && $result->num_rows > 0) {
        $empleado = $result->fetch_assoc();
    }
}

if (!$empleado) {
    header('Location: empleados.php');
    exit;
}

$error = '';
$exito = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = sanitizar_sql($conn, $_POST['nombre'] ?? '');
    $puesto = sanitizar_sql($conn, $_POST['puesto'] ?? '');
    $departamento = sanitizar_sql($conn, $_POST['departamento'] ?? '');
    $activo = isset($_POST['activo']) ? 1 : 0;

    if (empty($nombre)) {
        $error = 'El nombre es requerido';
    } else {
        $conn->query("UPDATE usuarios SET nombre = '$nombre', activo = $activo WHERE id_usuario = $id");
        $conn->query("UPDATE empleados SET nombre_completo = '$nombre', puesto = '$puesto', departamento = '$departamento' WHERE id_usuario = $id");
        $exito = 'Empleado actualizado correctamente';
    }
}

require_once __DIR__ . '/admin_header.php';
admin_render_header('Editar Empleado', 'Empleados', 'fas fa-briefcase');
?>

<style>
    .section { background: rgba(26, 26, 46, 0.95); border: 1px solid rgba(98, 0, 255, 0.35); border-radius: 16px; padding: 25px; max-width: 600px; }
    .form-group { margin-bottom: 15px; }
    label { color: #00d4ff; font-weight: bold; margin-bottom: 5px; display: block; }
    input, textarea, select { background: rgba(255, 255, 255, 0.9) !important; color: #0f172a !important; border: 1px solid rgba(98, 0, 255, 0.3) !important; padding: 10px !important; border-radius: 5px !important; width: 100% !important; }
</style>

<div class="section">
    <h3 style="color: #00d4ff; margin-bottom: 20px;"><i class="fas fa-edit"></i> Editar Empleado</h3>
    
    <?php if ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($exito): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($exito); ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label for="nombre">Nombre</label>
            <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($empleado['nombre']); ?>" required>
        </div>

        <div class="form-group">
            <label for="correo">Email (No editable)</label>
            <input type="email" value="<?php echo htmlspecialchars($empleado['correo']); ?>" disabled>
        </div>

        <div class="form-group">
            <label for="puesto">Puesto</label>
            <input type="text" id="puesto" name="puesto" value="<?php echo htmlspecialchars($empleado['puesto'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label for="departamento">Departamento</label>
            <input type="text" id="departamento" name="departamento" value="<?php echo htmlspecialchars($empleado['departamento'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label>
                <input type="checkbox" name="activo" <?php echo $empleado['activo'] ? 'checked' : ''; ?>>
                Activo
            </label>
        </div>

        <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Guardar</button>
        <a href="empleados.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancelar</a>
    </form>
</div>

<?php admin_render_footer(); ?>
