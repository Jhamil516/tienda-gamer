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

// Obtener categorías
$categorias = obtener_categorias($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = sanitizar_sql($conn, $_POST['nombre'] ?? '');
    $marca = sanitizar_sql($conn, $_POST['marca'] ?? '');
    $descripcion = sanitizar_sql($conn, $_POST['descripcion'] ?? '');
    $id_categoria = intval($_POST['id_categoria'] ?? 0);
    $precio_original = floatval($_POST['precio_original'] ?? 0);
    $precio_actual = floatval($_POST['precio_actual'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
    $stock_minimo = intval($_POST['stock_minimo'] ?? 5);
    $descuento = intval($_POST['descuento_porcentaje'] ?? 0);
    $plataforma = sanitizar_sql($conn, $_POST['plataforma'] ?? '');
    $genero = sanitizar_sql($conn, $_POST['genero'] ?? '');

    // Validaciones
    if (!$nombre || !$id_categoria || $precio_original <= 0) {
        $error = 'Completa todos los campos requeridos';
    } else {
        // Crear producto
        $query = "INSERT INTO productos (id_categoria, nombre, marca, descripcion, precio_original,
                  precio_actual, descuento_porcentaje, stock, stock_minimo, plataforma, genero)
                  VALUES ($id_categoria, '$nombre', '$marca', '$descripcion', $precio_original,
                  $precio_actual, $descuento, $stock, $stock_minimo, '$plataforma', '$genero')";

        if ($conn->query($query)) {
            $id_producto = $conn->insert_id;

            // Procesar imágenes
            $principalIndex = isset($_POST['principal_index']) ? intval($_POST['principal_index']) : 0;
            if (isset($_FILES['imagenes'])) {
                $uploaded = 0;
                foreach ($_FILES['imagenes']['tmp_name'] as $key => $tmp_name) {
                    if (!empty($tmp_name) && $_FILES['imagenes']['error'][$key] === UPLOAD_ERR_OK) {
                        $file_name = 'producto_' . $id_producto . '_' . time() . '_' . $key . '.jpg';
                        $file_path = RUTA_UPLOADS . $file_name;

                        // Validar que sea imagen
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        $mime = finfo_file($finfo, $tmp_name);
                        finfo_close($finfo);

                        if (in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) {
                            if (move_uploaded_file($tmp_name, $file_path)) {
                                $es_principal = ($uploaded === $principalIndex) ? 1 : 0;
                                $query_img = "INSERT INTO imagenes_producto (id_producto, ruta_imagen, es_principal, orden) VALUES ($id_producto, '$file_name', $es_principal, $uploaded)";
                                $conn->query($query_img);

                                if ($es_principal) {
                                    $conn->query("UPDATE productos SET imagen_principal = '$file_name' WHERE id_producto = $id_producto");
                                }

                                $uploaded++;
                            }
                        }
                    }
                }
            }

            $exito = 'Producto creado exitosamente con ' . $uploaded . ' imagen(es)';
            header('Refresh: 2; url=productos.php');
        } else {
            $error = 'Error al crear el producto: ' . $conn->error;
        }
    }
}

require_once __DIR__ . '/admin_header.php';
admin_render_header('Crear Producto', 'Productos', 'fas fa-plus');
?>
    <style>
        :root {
            --primary: #6200ff;
            --accent: #00d4ff;
            --dark-bg: #0a0a0a;
            --card-bg: #1a1a2e;
            --text-light: #e0e0e0;
        }

        body {
            background: linear-gradient(135deg, var(--dark-bg) 0%, #1a0033 100%);
            color: var(--text-light);
        }

        .navbar {
            background: rgba(10, 10, 10, 0.95);
            border-bottom: 3px solid var(--primary);
            padding: 15px 30px;
        }

        .navbar-brand {
            color: var(--accent) !important;
            font-weight: bold;
        }

        .container {
            margin-top: 30px;
            max-width: 900px;
        }

        .page-title {
            color: var(--accent);
            font-weight: bold;
            margin-bottom: 30px;
            font-size: 1.8rem;
        }

        .form-section {
            background: rgba(26, 26, 46, 0.8);
            border: 2px solid var(--primary);
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
        }

        .section-title {
            color: var(--accent);
            font-weight: bold;
            margin-bottom: 20px;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            color: var(--text-light);
            font-weight: 500;
            margin-bottom: 8px;
        }

        .form-control, .form-select {
            background: rgba(71, 85, 105, 0.3);
            border: 1px solid var(--primary);
            color: white;
            padding: 12px 15px;
            border-radius: 6px;
            transition: all 0.3s;
        }

        .form-control:focus, .form-select:focus {
            background: rgba(71, 85, 105, 0.5);
            border-color: var(--accent);
            color: white;
            box-shadow: 0 0 10px rgba(0, 212, 255, 0.3);
            outline: none;
        }

        .form-control::placeholder {
            color: #aaa;
        }

        .form-select option {
            background: var(--card-bg);
            color: var(--text-light);
        }

        .file-input-wrapper {
            position: relative;
            cursor: pointer;
        }

        .file-input-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            background: rgba(98, 0, 255, 0.2);
            border: 2px dashed var(--accent);
            border-radius: 8px;
            padding: 40px;
            transition: all 0.3s;
            cursor: pointer;
        }

        .file-input-label:hover {
            background: rgba(98, 0, 255, 0.3);
            border-color: var(--accent);
        }

        .file-input-label i {
            font-size: 2rem;
            color: var(--accent);
        }

        input[type="file"] {
            display: none;
        }

        .image-preview {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .preview-item {
            position: relative;
            border-radius: 8px;
            overflow: hidden;
            border: 2px solid var(--primary);
        }

        .preview-item img {
            width: 100%;
            height: 100px;
            object-fit: cover;
            display: block;
        }

        .preview-badge {
            position: absolute;
            top: 5px;
            right: 5px;
            background: var(--accent);
            color: var(--dark-bg);
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: bold;
        }

        .btn-delete-preview {
            position: absolute;
            bottom: 5px;
            right: 5px;
            background: #ef4444;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.75rem;
            transition: background 0.2s;
            z-index: 10;
        }

        .btn-delete-preview:hover {
            background: #dc2626;
        }

        .btn-set-principal {
            position: absolute;
            bottom: 5px;
            left: 5px;
            background: rgba(0, 212, 255, 0.95);
            color: #0a0a0a;
            border: none;
            padding: 5px 8px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.75rem;
            font-weight: 700;
            transition: background 0.2s;
        }

        .btn-set-principal:hover {
            background: rgba(0, 212, 255, 1);
        }

        .btn-submit {
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: bold;
            transition: all 0.3s;
            width: 100%;
        }

        .btn-submit:hover {
            box-shadow: 0 0 20px rgba(98, 0, 255, 0.5);
            color: white;
        }

        .btn-back {
            background: #495057;
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            margin-bottom: 20px;
        }

        .btn-back:hover {
            background: #404449;
            color: white;
        }

        .alert {
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .row-2col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        @media (max-width: 768px) {
            .row-2col {
                grid-template-columns: 1fr;
            }
        }
    </style>
    <div class="container">
        <a href="productos.php" class="btn-back">
            <i class="fas fa-arrow-left"></i> Volver a Productos
        </a>

        <h1 class="page-title"><i class="fas fa-plus-circle"></i> Crear Nuevo Producto</h1>

        <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <?php if ($exito): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($exito); ?>
        </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <!-- Información Básica -->
            <div class="form-section">
                <div class="section-title">
                    <i class="fas fa-info-circle"></i> Información Básica
                </div>

                <div class="row-2col">
                    <div class="form-group">
                        <label class="form-label">Nombre del Producto *</label>
                        <input type="text" class="form-control" name="nombre" placeholder="Ej: Elden Ring" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Marca</label>
                        <input type="text" class="form-control" name="marca" placeholder="Ej: FromSoftware">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Categoría *</label>
                        <select class="form-select" name="id_categoria" required>
                            <option value="">Seleccionar...</option>
                            <?php foreach ($categorias as $cat): ?>
                            <option value="<?php echo $cat['id_categoria']; ?>">
                                <?php echo htmlspecialchars($cat['nombre_categoria']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Plataforma</label>
                        <input type="text" class="form-control" name="plataforma" placeholder="Ej: PS5, PC, Xbox">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Descripción</label>
                    <textarea class="form-control" name="descripcion" rows="4" placeholder="Descripción detallada del producto..."></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Género</label>
                    <input type="text" class="form-control" name="genero" placeholder="Ej: RPG, Acción, Aventura">
                </div>
            </div>

            <!-- Precios y Stock -->
            <div class="form-section">
                <div class="section-title">
                    <i class="fas fa-dollar-sign"></i> Precios y Stock
                </div>

                <div class="row-2col">
                    <div class="form-group">
                        <label class="form-label">Precio Original *</label>
                        <input type="number" class="form-control" name="precio_original" step="0.01" placeholder="59.99" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Precio Actual *</label>
                        <input type="number" class="form-control" name="precio_actual" step="0.01" placeholder="29.99" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Descuento (%)</label>
                        <input type="number" class="form-control" name="descuento_porcentaje" min="0" max="100" value="0">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Stock *</label>
                        <input type="number" class="form-control" name="stock" min="0" value="0" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Stock Mínimo</label>
                        <input type="number" class="form-control" name="stock_minimo" min="1" value="5">
                    </div>
                </div>
            </div>

            <!-- Imágenes -->
            <div class="form-section">
                <div class="section-title">
                    <i class="fas fa-images"></i> Imágenes del Producto
                </div>

                <p style="color: #aaa; margin-bottom: 20px;">
                    <i class="fas fa-info-circle"></i> Carga múltiples imágenes. La primera será la imagen principal.
                </p>

                <div class="file-input-wrapper">
                    <label class="file-input-label" for="imagenes">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <div>
                            <strong>Haz clic o arrastra múltiples imágenes aquí</strong>
                            <small>(Selecciona varias imágenes a la vez)</small>
                        </div>
                    </label>
                    <input type="file" id="imagenes" name="imagenes[]" multiple accept="image/*">
                    <input type="hidden" id="principalIndex" name="principal_index" value="0">
                </div>

                <div id="imagePreview" class="image-preview"></div>
            </div>

            <!-- Botones -->
            <div class="form-section">
                <button type="submit" class="btn-submit">
                    <i class="fas fa-save"></i> Crear Producto
                </button>
            </div>
        </form>
    </div>

    <script>
        const fileInput = document.getElementById('imagenes');
        const preview = document.getElementById('imagePreview');
        let selectedFiles = [];

        fileInput.addEventListener('change', function(e) {
            selectedFiles = Array.from(e.target.files);
            updatePreview();
        });

        function updatePreview() {
            preview.innerHTML = '';

            selectedFiles.forEach((file, index) => {
                const reader = new FileReader();

                reader.onload = function(e) {
                    const div = document.createElement('div');
                    div.className = 'preview-item';
                    div.innerHTML = `
                        <img src="${e.target.result}" alt="Preview">
                        ${index === 0 ? '<span class="preview-badge">Principal</span>' : '<button type="button" class="btn-set-principal" onclick="setPrincipal(' + index + ')">Hacer principal</button>'}
                        <button type="button" class="btn-delete-preview" onclick="deletePreview(${index})">
                            <i class="fas fa-trash"></i> Eliminar
                        </button>
                    `;
                    preview.appendChild(div);
                };

                reader.readAsDataURL(file);
            });
        }

        function updateFileInput() {
            const dataTransfer = new DataTransfer();
            selectedFiles.forEach(file => dataTransfer.items.add(file));
            fileInput.files = dataTransfer.files;
        }

        function setPrincipal(index) {
            if (index === 0) return;
            const [file] = selectedFiles.splice(index, 1);
            selectedFiles.unshift(file);
            document.getElementById('principalIndex').value = 0;
            updateFileInput();
            updatePreview();
        }

        function deletePreview(index) {
            selectedFiles.splice(index, 1);
            
            updateFileInput();
            updatePreview();
        }

        // Drag and drop
        const label = document.querySelector('.file-input-label');
        label.addEventListener('dragover', (e) => {
            e.preventDefault();
            label.style.background = 'rgba(0, 212, 255, 0.2)';
        });

        label.addEventListener('dragleave', () => {
            label.style.background = 'rgba(98, 0, 255, 0.2)';
        });

        label.addEventListener('drop', (e) => {
            e.preventDefault();
            label.style.background = 'rgba(98, 0, 255, 0.2)';
            selectedFiles = Array.from(e.dataTransfer.files);
            
            // Actualizar el input file con un DataTransfer
            const dataTransfer = new DataTransfer();
            selectedFiles.forEach(file => {
                dataTransfer.items.add(file);
            });
            fileInput.files = dataTransfer.files;
            
            updatePreview();
        });

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

        precioOriginal.addEventListener('input', actualizarPrecioActual);
        descuentoPorcentaje.addEventListener('input', actualizarPrecioActual);
        precioActual.addEventListener('input', actualizarDescuento);
    </script>
<?php admin_render_footer(); ?>
