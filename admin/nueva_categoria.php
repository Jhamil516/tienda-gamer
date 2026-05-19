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
    $descripcion = sanitizar_sql($conn, $_POST['descripcion'] ?? '');
    $icono = sanitizar_sql($conn, $_POST['icono'] ?? '');
    $orden = intval($_POST['orden'] ?? 0);

    if (empty($nombre)) {
        $error = 'El nombre es requerido';
    } else {
        $query = "INSERT INTO categorias (nombre_categoria, descripcion, icono, orden, activa) 
                  VALUES ('$nombre', '$descripcion', '$icono', $orden, 1)";
        
        if ($conn->query($query)) {
            $exito = 'Categoría creada correctamente';
            header('Refresh: 2; url=categorias.php');
        } else {
            $error = 'Error: ' . $conn->error;
        }
    }
}

require_once __DIR__ . '/admin_header.php';
admin_render_header('Nueva Categoría', 'Categorías', 'fas fa-list');
?>

<style>
    .section { background: rgba(26, 26, 46, 0.95); border: 1px solid rgba(98, 0, 255, 0.35); border-radius: 16px; padding: 25px; max-width: 600px; }
    .form-group { margin-bottom: 15px; }
    label { color: #00d4ff; font-weight: bold; margin-bottom: 5px; display: block; }
    input, textarea, select { background: rgba(255, 255, 255, 0.9) !important; color: #0f172a !important; border: 1px solid rgba(98, 0, 255, 0.3) !important; padding: 10px !important; border-radius: 5px !important; width: 100% !important; }
</style>

<div class="section">
    <h3 style="color: #00d4ff; margin-bottom: 20px;"><i class="fas fa-plus"></i> Nueva Categoría</h3>
    
    <?php if ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($exito): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($exito); ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label for="nombre">Nombre *</label>
            <input type="text" id="nombre" name="nombre" required placeholder="Ej: Videojuegos">
        </div>

        <div class="form-group">
            <label for="descripcion">Descripción</label>
            <textarea id="descripcion" name="descripcion" rows="3" placeholder="Describe la categoría"></textarea>
        </div>

        <div class="form-group">
            <label for="icono">Icono (emoji)</label>
            <input type="text" id="icono" name="icono" placeholder="🎮">
        </div>

        <div class="form-group">
            <label for="orden">Orden</label>
            <input type="number" id="orden" name="orden" value="0" min="0">
        </div>

        <button type="submit" class="btn btn-success"><i class="fas fa-check"></i> Crear</button>
        <a href="categorias.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancelar</a>
    </form>
</div>

<?php admin_render_footer(); ?>
