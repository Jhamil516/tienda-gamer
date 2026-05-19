<?php
require_once __DIR__ . '/header.php';

$id = intval($_GET['id'] ?? 0);
$producto = obtener_producto_por_id_admin($conn, $id);
$categorias = obtener_categorias($conn);
$error = '';

if (!$producto) {
    header('Location: productos.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_categoria    = intval($_POST['id_categoria'] ?? 0);
    $nombre          = sanitizar_sql($conn, $_POST['nombre'] ?? '');
    $marca           = sanitizar_sql($conn, $_POST['marca'] ?? '');
    $descripcion     = sanitizar_sql($conn, $_POST['descripcion'] ?? '');
    $precio_original = floatval($_POST['precio_original'] ?? 0);
    $precio_actual   = floatval($_POST['precio_actual'] ?? 0);
    $descuento       = intval($_POST['descuento_porcentaje'] ?? 0);
    $stock           = intval($_POST['stock'] ?? 0);
    $stock_minimo    = intval($_POST['stock_minimo'] ?? 0);
    $plataforma      = sanitizar_sql($conn, $_POST['plataforma'] ?? '');
    $genero          = sanitizar_sql($conn, $_POST['genero'] ?? '');
    $estado          = sanitizar_sql($conn, $_POST['estado'] ?? 'activo');
    $destacado       = isset($_POST['destacado']) ? 1 : 0;

    if ($id_categoria <= 0 || empty($nombre) || $precio_original <= 0 || $precio_actual <= 0 || $stock < 0 || $stock_minimo < 0) {
        $error = 'Por favor completa todos los campos obligatorios con valores válidos.';
    } else {
        if ($precio_original > 0) {
            $descuento = max(0, min(100, round((1 - $precio_actual / $precio_original) * 100)));
        } else {
            $descuento = 0;
        }

        $stmt = $conn->prepare("UPDATE productos SET id_categoria = ?, nombre = ?, marca = ?, descripcion = ?, precio_original = ?, precio_actual = ?, stock = ?, stock_minimo = ?, plataforma = ?, genero = ?, estado = ?, destacado = ?, descuento_porcentaje = ? WHERE id_producto = ?");
        $stmt->bind_param('isssddiisssiii', $id_categoria, $nombre, $marca, $descripcion, $precio_original, $precio_actual, $stock, $stock_minimo, $plataforma, $genero, $estado, $destacado, $descuento, $id);

        if ($stmt->execute()) {
            $tienePrincipal = false;
            $result = $conn->query("SELECT COUNT(*) AS total FROM imagenes_producto WHERE id_producto = $id AND es_principal = 1");
            if ($result) {
                $tienePrincipal = intval($result->fetch_assoc()['total']) > 0;
            }

            if (isset($_FILES['imagenes']) && count($_FILES['imagenes']['name']) > 0) {
                $tipos_permitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $orden = 0;
                $resultOrden = $conn->query("SELECT COALESCE(MAX(orden), -1) + 1 AS next_orden FROM imagenes_producto WHERE id_producto = $id");
                if ($resultOrden) {
                    $orden = intval($resultOrden->fetch_assoc()['next_orden']);
                }

                for ($i = 0; $i < count($_FILES['imagenes']['name']); $i++) {
                    if (!empty($_FILES['imagenes']['name'][$i]) && $_FILES['imagenes']['error'][$i] === UPLOAD_ERR_OK) {
                        $tipo = $_FILES['imagenes']['type'][$i];
                        $tamaño = $_FILES['imagenes']['size'][$i];
                        $tmp = $_FILES['imagenes']['tmp_name'][$i];
                        $nombre_archivo = basename($_FILES['imagenes']['name'][$i]);

                        if (!in_array($tipo, $tipos_permitidos) || $tamaño > 5 * 1024 * 1024) {
                            continue;
                        }

                        $ext = pathinfo($nombre_archivo, PATHINFO_EXTENSION);
                        $nombre_imagen = 'producto_' . $id . '_' . time() . '_' . $i . '.' . $ext;
                        $destino = __DIR__ . '/../uploads/' . $nombre_imagen;

                        if (move_uploaded_file($tmp, $destino)) {
                            $es_principal = $tienePrincipal ? 0 : 1;
                            $conn->query("INSERT INTO imagenes_producto (id_producto, ruta_imagen, es_principal, orden) VALUES ($id, '$nombre_imagen', $es_principal, $orden)");

                            if (!$tienePrincipal) {
                                $conn->query("UPDATE productos SET imagen_principal = '$nombre_imagen' WHERE id_producto = $id");
                                $tienePrincipal = true;
                            }

                            $orden++;
                        }
                    }
                }
            }

            header('Location: productos.php?editado=1');
            exit;
        }

        $error = 'Error al actualizar el producto: ' . $conn->error;
    }
}

$imagenes = obtener_imagenes_producto($conn, $id);

empleado_render_header('Editar Producto', 'fas fa-edit');
?>
<style>
    .section { background: rgba(26,26,46,0.95); border: 1px solid rgba(98,0,255,0.35); border-radius: 16px; padding: 25px; }
    .form-label { color: #e0e0e0; margin-bottom: 8px; display: block; font-weight: 600; }
    .form-control, .form-select { background: rgba(15,15,30,0.8); color: #fff; border: 1px solid rgba(98,0,255,0.5); border-radius: 8px; padding: 10px; }
    .form-control:focus, .form-select:focus { border-color: #00d4ff; box-shadow: 0 0 8px rgba(0,212,255,0.2); outline: none; }
    .btn-primary, .btn-success, .btn-secondary { border-radius: 10px; }

    .image-container {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
        gap: 15px;
        margin-top: 15px;
    }

    .image-item {
        position: relative;
        border: 2px solid rgba(98, 0, 255, 0.3);
        border-radius: 10px;
        overflow: hidden;
        transition: all 0.3s;
    }

    .image-item:hover {
        border-color: #00d4ff;
        box-shadow: 0 0 15px rgba(0, 212, 255, 0.2);
    }

    .image-item img {
        width: 100%;
        height: 120px;
        object-fit: cover;
        display: block;
    }

    .image-controls {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(10, 10, 10, 0.9);
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        gap: 8px;
        opacity: 0;
        transition: opacity 0.3s;
    }

    .image-item:hover .image-controls {
        opacity: 1;
    }

    .image-btn {
        padding: 6px 10px;
        border: none;
        border-radius: 6px;
        font-size: 0.8rem;
        cursor: pointer;
        transition: all 0.3s;
        font-weight: 600;
    }

    .image-btn-primary {
        background: #6200ff;
        color: white;
    }

    .image-btn-primary:hover {
        background: #00d4ff;
        color: #0a0a0a;
    }

    .image-btn-danger {
        background: #dc3545;
        color: white;
    }

    .image-btn-danger:hover {
        background: #ff6b6b;
    }

    .image-badge {
        position: absolute;
        top: 5px;
        right: 5px;
        background: #6200ff;
        color: white;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 700;
    }

    .upload-area {
        border: 2px dashed rgba(98, 0, 255, 0.5);
        border-radius: 10px;
        padding: 20px;
        text-align: center;
        transition: all 0.3s;
        cursor: pointer;
        background: rgba(98, 0, 255, 0.05);
    }

    .upload-area:hover {
        border-color: #00d4ff;
        background: rgba(0, 212, 255, 0.1);
    }

    .upload-area.dragover {
        border-color: #00d4ff;
        background: rgba(0, 212, 255, 0.15);
    }

    .upload-label {
        cursor: pointer;
    }

    .pending-images {
        background: rgba(98, 0, 255, 0.1);
        border: 1px solid rgba(98, 0, 255, 0.3);
        padding: 10px;
        border-radius: 8px;
        font-size: 0.9rem;
        margin-top: 10px;
        color: #a7f3d0;
    }
</style>

<div class="section">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:20px;">
        <h3 style="color: #00d4ff;"><i class="fas fa-edit"></i> Editar Producto</h3>
        <a href="productos.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" style="display:grid; gap:18px;">
        <div class="row gx-3">
            <div class="col-md-6">
                <label class="form-label">Nombre *</label>
                <input type="text" name="nombre" class="form-control" required value="<?php echo htmlspecialchars($_POST['nombre'] ?? $producto['nombre']); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Categoría *</label>
                <select name="id_categoria" class="form-select" required>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?php echo $cat['id_categoria']; ?>" <?php echo intval($_POST['id_categoria'] ?? $producto['id_categoria']) === $cat['id_categoria'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['nombre_categoria']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="row gx-3">
            <div class="col-md-6">
                <label class="form-label">Marca</label>
                <input type="text" name="marca" class="form-control" value="<?php echo htmlspecialchars($_POST['marca'] ?? $producto['marca']); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Plataforma</label>
                <input type="text" name="plataforma" class="form-control" value="<?php echo htmlspecialchars($_POST['plataforma'] ?? $producto['plataforma']); ?>">
            </div>
        </div>

        <div class="row gx-3">
            <div class="col-md-4">
                <label class="form-label">Precio Original *</label>
                <input type="number" step="0.01" min="0" name="precio_original" class="form-control" required value="<?php echo htmlspecialchars($_POST['precio_original'] ?? $producto['precio_original']); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Precio Actual *</label>
                <input type="number" step="0.01" min="0" name="precio_actual" class="form-control" required value="<?php echo htmlspecialchars($_POST['precio_actual'] ?? $producto['precio_actual']); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Descuento (%)</label>
                <input type="number" step="1" min="0" max="100" name="descuento_porcentaje" class="form-control" value="<?php echo htmlspecialchars($_POST['descuento_porcentaje'] ?? $producto['descuento_porcentaje']); ?>">
            </div>
        </div>
        <div class="row gx-3">
            <div class="col-md-6">
                <label class="form-label">Stock *</label>
                <input type="number" min="0" name="stock" class="form-control" required value="<?php echo htmlspecialchars($_POST['stock'] ?? $producto['stock']); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Stock Mínimo *</label>
                <input type="number" min="0" name="stock_minimo" class="form-control" required value="<?php echo htmlspecialchars($_POST['stock_minimo'] ?? $producto['stock_minimo']); ?>">
            </div>
        </div>

        <div class="row gx-3">
            <div class="col-md-12">
                <label class="form-label">Género</label>
                <input type="text" name="genero" class="form-control" value="<?php echo htmlspecialchars($_POST['genero'] ?? $producto['genero']); ?>">
            </div>
        </div>

        <div>
            <label class="form-label">Descripción</label>
            <textarea name="descripcion" class="form-control" rows="4"><?php echo htmlspecialchars($_POST['descripcion'] ?? $producto['descripcion']); ?></textarea>
        </div>

        <div class="row gx-3">
            <div class="col-md-6">
                <label class="form-label">Estado</label>
                <select name="estado" class="form-select">
                    <option value="activo" <?php echo ($_POST['estado'] ?? $producto['estado']) === 'activo' ? 'selected' : ''; ?>>Activo</option>
                    <option value="inactivo" <?php echo ($_POST['estado'] ?? $producto['estado']) === 'inactivo' ? 'selected' : ''; ?>>Inactivo</option>
                    <option value="agotado" <?php echo ($_POST['estado'] ?? $producto['estado']) === 'agotado' ? 'selected' : ''; ?>>Agotado</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-check" style="margin-top:32px;">
                    <input class="form-check-input" type="checkbox" name="destacado" <?php echo isset($_POST['destacado']) || $producto['destacado'] ? 'checked' : ''; ?>>
                    <span class="form-check-label">Destacado</span>
                </label>
            </div>
        </div>

        <div>
            <label class="form-label">Gestionar Imágenes</label>

            <div class="upload-area" id="uploadArea">
                <label class="upload-label">
                    <i class="fas fa-cloud-upload-alt" style="font-size: 2rem; color: #00d4ff; margin-bottom: 10px; display: block;"></i>
                    <strong>Arrastra imágenes aquí o haz clic para seleccionar</strong>
                    <p style="margin: 8px 0 0 0; color: #aaa; font-size: 0.9rem;">Puedes subir múltiples imágenes (JPG, PNG, GIF, WebP)</p>
                    <input type="file" id="fileInput" name="imagenes[]" style="display: none;" accept="image/*" multiple>
                </label>
            </div>

            <!-- Imágenes pendientes de subir -->
            <div id="pendingImagesContainer" style="display: none;">
                <label class="form-label" style="margin-top: 20px;">📋 Imágenes pendientes de guardar</label>
                <div class="image-container" id="pendingImages"></div>
                <div class="pending-images">
                    <i class="fas fa-info-circle"></i> Estas imágenes se guardarán cuando hagas clic en "Guardar cambios"
                </div>
            </div>

            <!-- Imágenes actuales -->
            <?php if (!empty($imagenes)): ?>
                <label class="form-label" style="margin-top: 20px;">✅ Imágenes guardadas del producto</label>
                <div class="image-container" id="currentImages">
                    <?php foreach ($imagenes as $img): ?>
                        <div class="image-item" data-img-id="<?php echo $img['id_imagen']; ?>" data-es-principal="<?php echo $img['es_principal']; ?>">
                            <?php if ($img['es_principal']): ?>
                                <div class="image-badge">PRINCIPAL</div>
                            <?php endif; ?>
                            <img src="../uploads/<?php echo htmlspecialchars($img['ruta_imagen']); ?>" alt="Imagen producto">
                            <div class="image-controls">
                                <?php if (!$img['es_principal']): ?>
                                    <button type="button" class="image-btn image-btn-primary btn-set-principal" data-img-id="<?php echo $img['id_imagen']; ?>">
                                        <i class="fas fa-star"></i> Hacer principal
                                    </button>
                                <?php endif; ?>
                                <button type="button" class="image-btn image-btn-danger btn-delete-current" data-img-id="<?php echo $img['id_imagen']; ?>">
                                    <i class="fas fa-trash"></i> Eliminar
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div style="display:flex; gap:12px; flex-wrap:wrap; margin-top:20px;">
            <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Guardar cambios</button>
            <a href="productos.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
        </div>
    </form>
</div>

<script>
    const precioOriginal = document.querySelector('[name="precio_original"]');
    const precioActual = document.querySelector('[name="precio_actual"]');
    const descuentoPorcentaje = document.querySelector('[name="descuento_porcentaje"]');
    let precioUpdating = false;

    function actualizarPrecioActual() {
        if (precioUpdating) return;
        const original = parseFloat(precioOriginal.value) || 0;
        const descuento = parseFloat(descuentoPorcentaje.value) || 0;
        if (original > 0) {
            precioUpdating = true;
            precioActual.value = (original * (1 - descuento / 100)).toFixed(2);
            precioUpdating = false;
        }
    }

    function actualizarDescuento() {
        if (precioUpdating) return;
        const original = parseFloat(precioOriginal.value) || 0;
        const actual = parseFloat(precioActual.value) || 0;
        if (original > 0) {
            precioUpdating = true;
            const descuento = Math.round((1 - actual / original) * 100);
            descuentoPorcentaje.value = descuento >= 0 ? descuento : 0;
            precioUpdating = false;
        }
    }

    if (precioOriginal && precioActual && descuentoPorcentaje) {
        precioOriginal.addEventListener('input', actualizarPrecioActual);
        descuentoPorcentaje.addEventListener('input', actualizarPrecioActual);
        precioActual.addEventListener('input', actualizarDescuento);
    }

    // Gestión de imágenes
    const uploadArea = document.getElementById('uploadArea');
    const fileInput = document.getElementById('fileInput');
    const pendingImagesContainer = document.getElementById('pendingImagesContainer');
    const pendingImages = document.getElementById('pendingImages');
    const imagenForm = document.querySelector('[name="imagenes[]"]');
    let pendingImagesList = [];

    uploadArea.addEventListener('click', () => fileInput.click());

    uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadArea.classList.add('dragover');
    });

    uploadArea.addEventListener('dragleave', () => {
        uploadArea.classList.remove('dragover');
    });

    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.classList.remove('dragover');
        const files = e.dataTransfer.files;
        handleFiles(files);
    });

    fileInput.addEventListener('change', (e) => {
        handleFiles(e.target.files);
    });

    function handleFiles(files) {
        for (let file of files) {
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    addPendingImage(file, e.target.result);
                };
                reader.readAsDataURL(file);
            }
        }
    }

    function addPendingImage(file, preview) {
        const id = 'pending_' + Date.now() + Math.random();
        pendingImagesList.push({ id, file });

        const item = document.createElement('div');
        item.className = 'image-item';
        item.dataset.pendingId = id;
        item.innerHTML = `
            <img src="${preview}" alt="Preview">
            <div class="image-controls">
                <button type="button" class="image-btn image-btn-danger btn-delete-pending" data-pending-id="${id}">
                    <i class="fas fa-trash"></i> Eliminar
                </button>
            </div>
        `;

        pendingImages.appendChild(item);
        pendingImagesContainer.style.display = 'block';

        item.querySelector('.btn-delete-pending').addEventListener('click', (e) => {
            e.preventDefault();
            deletePendingImage(id);
        });
    }

    function deletePendingImage(id) {
        pendingImagesList = pendingImagesList.filter(img => img.id !== id);
        document.querySelector(`[data-pending-id="${id}"]`).remove();

        if (pendingImagesList.length === 0) {
            pendingImagesContainer.style.display = 'none';
        }
    }

    // Actualizar formulario antes de enviar
    document.querySelector('form').addEventListener('submit', (e) => {
        const dataTransfer = new DataTransfer();
        pendingImagesList.forEach(item => {
            dataTransfer.items.add(item.file);
        });
        fileInput.files = dataTransfer.files;
    });

    // Eliminar imagen actual
    document.querySelectorAll('.btn-delete-current').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const imgId = btn.dataset.imgId;
            const item = document.querySelector(`[data-img-id="${imgId}"]`);

            if (confirm('¿Estás seguro de que deseas eliminar esta imagen?')) {
                fetch('../api/delete_image.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'id_imagen=' + imgId
                })
                .then(r => r.json())
                .then(data => {
                    if (data.exito) {
                        item.remove();
                    } else {
                        alert('Error al eliminar la imagen');
                    }
                });
            }
        });
    });

    // Cambiar imagen principal
    document.querySelectorAll('.btn-set-principal').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const imgId = btn.dataset.imgId;

            fetch('../api/set_principal_image.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id_imagen=' + imgId
            })
            .then(r => r.json())
            .then(data => {
                if (data.exito) {
                    document.querySelectorAll('[data-es-principal="1"]').forEach(item => {
                        item.dataset.esPrincipal = "0";
                        item.querySelector('.image-badge')?.remove();
                    });

                    const item = document.querySelector(`[data-img-id="${imgId}"]`);
                    item.dataset.esPrincipal = "1";
                    const badge = document.createElement('div');
                    badge.className = 'image-badge';
                    badge.textContent = 'PRINCIPAL';
                    item.appendChild(badge);
                    btn.remove();
                } else {
                    alert('Error al cambiar la imagen principal');
                }
            });
        });
    });
</script>

<?php empleado_render_footer(); ?>
