<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/funciones.php';

if (!es_admin()) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

$id_categoria = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id_categoria <= 0) {
    header('Location: categorias.php');
    exit;
}

$error = '';
$exito = '';

$stmt = $conn->prepare('SELECT * FROM categorias WHERE id_categoria = ? LIMIT 1');
$stmt->bind_param('i', $id_categoria);
$stmt->execute();
$result = $stmt->get_result();
$categoria = $result->fetch_assoc();

if (!$categoria) {
    header('Location: categorias.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = sanitizar_sql($conn, $_POST['nombre'] ?? '');
    $descripcion = sanitizar_sql($conn, $_POST['descripcion'] ?? '');
    $icono = sanitizar_sql($conn, $_POST['icono'] ?? '');
    $orden = intval($_POST['orden'] ?? 0);
    $activa = isset($_POST['activa']) ? 1 : 0;

    if (empty($nombre)) {
        $error = 'El nombre es requerido';
    } else {
        $stmt_update = $conn->prepare('UPDATE categorias SET nombre_categoria = ?, descripcion = ?, icono = ?, orden = ?, activa = ? WHERE id_categoria = ?');
        $stmt_update->bind_param('sssiii', $nombre, $descripcion, $icono, $orden, $activa, $id_categoria);

        if ($stmt_update->execute()) {
            $exito = 'Categoría actualizada correctamente';
            $categoria['nombre_categoria'] = $nombre;
            $categoria['descripcion'] = $descripcion;
            $categoria['icono'] = $icono;
            $categoria['orden'] = $orden;
            $categoria['activa'] = $activa;
        } else {
            $error = 'Error al guardar la categoría: ' . $conn->error;
        }
    }
}

require_once __DIR__ . '/admin_header.php';
admin_render_header('Editar Categoría', 'Categorías', 'fas fa-edit');
?>

<style>
    .section { background: rgba(26, 26, 46, 0.95); border: 1px solid rgba(98, 0, 255, 0.35); border-radius: 16px; padding: 25px; max-width: 650px; }
    .form-group { margin-bottom: 15px; }
    label { color: #00d4ff; font-weight: bold; margin-bottom: 5px; display: block; }
    input, textarea, select { background: rgba(255, 255, 255, 0.95) !important; color: #0f172a !important; border: 1px solid rgba(98, 0, 255, 0.3) !important; padding: 10px !important; border-radius: 5px !important; width: 100% !important; }
</style>

<div class="section">
    <h3 style="color: #00d4ff; margin-bottom: 20px;"><i class="fas fa-edit"></i> Editar Categoría</h3>

    <?php if ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($exito): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($exito); ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label for="nombre">Nombre *</label>
            <input type="text" id="nombre" name="nombre" required value="<?php echo htmlspecialchars($categoria['nombre_categoria']); ?>">
        </div>

        <div class="form-group">
            <label for="descripcion">Descripción</label>
            <textarea id="descripcion" name="descripcion" rows="3"><?php echo htmlspecialchars($categoria['descripcion']); ?></textarea>
        </div>

        <div class="form-group">
            <label for="icono">Icono (emoji)</label>
            <input type="text" id="icono" name="icono" value="<?php echo htmlspecialchars($categoria['icono']); ?>">
        </div>

        <div class="form-group">
            <label for="orden">Orden</label>
            <input type="number" id="orden" name="orden" value="<?php echo htmlspecialchars($categoria['orden']); ?>" min="0">
        </div>

        <div class="form-group">
            <label>
                <input type="checkbox" name="activa" <?php echo !empty($categoria['activa']) ? 'checked' : ''; ?>> Activa
            </label>
        </div>

        <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Guardar cambios</button>
        <a href="categorias.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancelar</a>
    </form>
</div>

<?php admin_render_footer(); ?>
