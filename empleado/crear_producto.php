<?php
require_once __DIR__ . '/header.php';

$categorias = obtener_categorias($conn);
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_categoria    = intval($_POST['id_categoria'] ?? 0);
    $nombre          = sanitizar_sql($conn, $_POST['nombre'] ?? '');
    $marca           = sanitizar_sql($conn, $_POST['marca'] ?? '');
    $descripcion     = sanitizar_sql($conn, $_POST['descripcion'] ?? '');
    $precio_original = floatval($_POST['precio_original'] ?? 0);
    $precio_actual   = floatval($_POST['precio_actual'] ?? 0);
    $stock           = intval($_POST['stock'] ?? 0);
    $stock_minimo    = intval($_POST['stock_minimo'] ?? 0);
    $plataforma      = sanitizar_sql($conn, $_POST['plataforma'] ?? '');
    $genero          = sanitizar_sql($conn, $_POST['genero'] ?? '');
    $estado          = 'activo';
    $destacado       = isset($_POST['destacado']) ? 1 : 0;

    if ($id_categoria <= 0 || empty($nombre) || $precio_original <= 0 || $precio_actual <= 0 || $stock < 0 || $stock_minimo < 0) {
        $error = 'Por favor completa todos los campos obligatorios con valores válidos.';
    } else {
        $stmt = $conn->prepare("INSERT INTO productos (id_categoria, nombre, marca, descripcion, precio_original, precio_actual, descuento_porcentaje, stock, stock_minimo, plataforma, genero, estado, destacado) VALUES (?, ?, ?, ?, ?, ?, 0, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('isssddiisssi', $id_categoria, $nombre, $marca, $descripcion, $precio_original, $precio_actual, $stock, $stock_minimo, $plataforma, $genero, $estado, $destacado);

        if ($stmt->execute()) {
            $id_producto = $conn->insert_id;
            $imagen_principal = '';
            $imagen_index = 0;

            if (isset($_FILES['imagenes']) && count($_FILES['imagenes']['name']) > 0) {
                $tipos_permitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

                for ($i = 0; $i < count($_FILES['imagenes']['name']); $i++) {
                    if (!empty($_FILES['imagenes']['name'][$i]) && $_FILES['imagenes']['error'][$i] === UPLOAD_ERR_OK) {
                        $tipo = $_FILES['imagenes']['type'][$i];
                        $tamaño = $_FILES['imagenes']['size'][$i];
                        $tmp = $_FILES['imagenes']['tmp_name'][$i];
                        $nombre_archivo = basename($_FILES['imagenes']['name'][$i]);

                        if (!in_array($tipo, $tipos_permitidos)) {
                            continue;
                        }

                        if ($tamaño > 5 * 1024 * 1024) {
                            continue;
                        }

                        $ext = pathinfo($nombre_archivo, PATHINFO_EXTENSION);
                        $nombre_imagen = 'producto_' . $id_producto . '_' . time() . '_' . $i . '.' . $ext;
                        $destino = __DIR__ . '/../uploads/' . $nombre_imagen;

                        if (move_uploaded_file($tmp, $destino)) {
                            $es_principal = $imagen_index === 0 ? 1 : 0;
                            $conn->query("INSERT INTO imagenes_producto (id_producto, ruta_imagen, es_principal, orden) VALUES ($id_producto, '$nombre_imagen', $es_principal, $imagen_index)");

                            if ($es_principal) {
                                $imagen_principal = $nombre_imagen;
                            }

                            $imagen_index++;
                        }
                    }
                }
            }

            if (!empty($imagen_principal)) {
                $imagen_principal_esc = $conn->real_escape_string($imagen_principal);
                $conn->query("UPDATE productos SET imagen_principal = '$imagen_principal_esc' WHERE id_producto = $id_producto");
            }

            $success = 'Producto creado correctamente.';
            header('Location: productos.php?creado=1');
            exit;
        } else {
            $error = 'Error al crear el producto: ' . $conn->error;
        }
    }
}

empleado_render_header('Crear Producto', 'fas fa-box');
?>
<style>
    .section { background: rgba(26,26,46,0.95); border: 1px solid rgba(98,0,255,0.35); border-radius: 16px; padding: 25px; }
    .form-label { color: #e0e0e0; margin-bottom: 8px; display: block; font-weight: 600; }
    .form-control, .form-select { background: rgba(15,15,30,0.8); color: #fff; border: 1px solid rgba(98,0,255,0.5); border-radius: 8px; padding: 10px; }
    .form-control:focus, .form-select:focus { border-color: #00d4ff; box-shadow: 0 0 8px rgba(0,212,255,0.2); outline: none; }
    .btn-primary, .btn-success, .btn-secondary { border-radius: 10px; }
    .file-drop { border: 2px dashed rgba(0,212,255,0.5); padding: 25px; border-radius: 14px; text-align: center; cursor: pointer; color: #cbd5e1; }
    .file-drop:hover { background: rgba(255,255,255,0.04); }
    .file-drop input { display:none; }
    .alert { border-radius: 10px; }
</style>

<div class="section">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h3 style="color: #00d4ff;"><i class="fas fa-plus-circle"></i> Crear Producto</h3>
        <a href="productos.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" style="display:grid; gap:18px;">
        <div class="row gx-3">
            <div class="col-md-6">
                <label class="form-label">Nombre *</label>
                <input type="text" name="nombre" class="form-control" required value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Categoría *</label>
                <select name="id_categoria" class="form-select" required>
                    <option value="">Seleccionar categoría</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?php echo $cat['id_categoria']; ?>" <?php echo isset($_POST['id_categoria']) && intval($_POST['id_categoria']) === $cat['id_categoria'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['nombre_categoria']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="row gx-3">
            <div class="col-md-6">
                <label class="form-label">Marca</label>
                <input type="text" name="marca" class="form-control" value="<?php echo htmlspecialchars($_POST['marca'] ?? ''); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Plataforma</label>
                <input type="text" name="plataforma" class="form-control" value="<?php echo htmlspecialchars($_POST['plataforma'] ?? ''); ?>" placeholder="Ej: PC, PS5, Xbox">
            </div>
        </div>

        <div class="row gx-3">
            <div class="col-md-4">
                <label class="form-label">Precio Original *</label>
                <input type="number" step="0.01" min="0" name="precio_original" class="form-control" required value="<?php echo htmlspecialchars($_POST['precio_original'] ?? ''); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Precio Actual *</label>
                <input type="number" step="0.01" min="0" name="precio_actual" class="form-control" required value="<?php echo htmlspecialchars($_POST['precio_actual'] ?? ''); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Stock *</label>
                <input type="number" min="0" name="stock" class="form-control" required value="<?php echo htmlspecialchars($_POST['stock'] ?? '0'); ?>">
            </div>
        </div>

        <div class="row gx-3">
            <div class="col-md-6">
                <label class="form-label">Stock Mínimo *</label>
                <input type="number" min="0" name="stock_minimo" class="form-control" required value="<?php echo htmlspecialchars($_POST['stock_minimo'] ?? '5'); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Género</label>
                <input type="text" name="genero" class="form-control" value="<?php echo htmlspecialchars($_POST['genero'] ?? ''); ?>">
            </div>
        </div>

        <div>
            <label class="form-label">Descripción</label>
            <textarea name="descripcion" class="form-control" rows="4"><?php echo htmlspecialchars($_POST['descripcion'] ?? ''); ?></textarea>
        </div>

        <div>
            <label class="form-label">Imágenes del producto</label>
            <label class="file-drop" for="imagenes">Haz clic o arrastra las imágenes aquí</label>
            <input type="file" id="imagenes" name="imagenes[]" accept="image/*" multiple>
            <small style="color:#aaa;">Formatos JPG, PNG, GIF, WEBP. Máximo 5MB por imagen.</small>
        </div>

        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="destacado" id="destacado" <?php echo isset($_POST['destacado']) ? 'checked' : ''; ?>>
            <label class="form-check-label" for="destacado">Producto destacado</label>
        </div>

        <div style="display:flex; gap:12px; flex-wrap:wrap; margin-top:20px;">
            <button type="submit" class="btn btn-success"><i class="fas fa-check"></i> Crear Producto</button>
            <a href="productos.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Cancelar</a>
        </div>
    </form>
</div>

<?php empleado_render_footer(); ?>
