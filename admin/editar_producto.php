<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/funciones.php';

if (!es_admin()) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

$id = intval($_GET['id'] ?? 0);
$producto = obtener_producto_por_id($conn, $id);

if (!$producto) {
    header('Location: productos.php');
    exit;
}

$categorias = obtener_categorias($conn);
$imagenes = [];
$error = '';
$exito = '';

// Obtener imágenes del producto
$query_img = "SELECT id_imagen, ruta_imagen, es_principal FROM imagenes_producto WHERE id_producto = $id ORDER BY es_principal DESC, orden ASC";
$result_img = $conn->query($query_img);
if ($result_img) {
    $imagenes = $result_img->fetch_all(MYSQLI_ASSOC);
}

// Procesar cambio de principal
if (isset($_GET['principal_imagen'])) {
    $id_imagen_principal = intval($_GET['principal_imagen']);
    $query_select = "SELECT ruta_imagen FROM imagenes_producto WHERE id_imagen = $id_imagen_principal AND id_producto = $id";
    $result_select = $conn->query($query_select);
    if ($result_select && $result_select->num_rows > 0) {
        $img_data = $result_select->fetch_assoc();
        $conn->query("UPDATE imagenes_producto SET es_principal = 0 WHERE id_producto = $id");
        $conn->query("UPDATE imagenes_producto SET es_principal = 1 WHERE id_imagen = $id_imagen_principal");
        $imagen_principal = $conn->real_escape_string($img_data['ruta_imagen']);
        $conn->query("UPDATE productos SET imagen_principal = '$imagen_principal' WHERE id_producto = $id");
        header('Location: editar_producto.php?id=' . $id);
        exit;
    }
}

// Procesar eliminación de imagen
if (isset($_GET['eliminar_imagen'])) {
    $id_imagen = intval($_GET['eliminar_imagen']);
    
    $query_get_img = "SELECT ruta_imagen, es_principal FROM imagenes_producto WHERE id_imagen = $id_imagen AND id_producto = $id";
    $result_get = $conn->query($query_get_img);
    
    if ($result_get && $result_get->num_rows > 0) {
        $img_data = $result_get->fetch_assoc();
        $ruta_imagen = __DIR__ . '/../uploads/' . $img_data['ruta_imagen'];
        $es_principal_eliminada = $img_data['es_principal'];
        
        // Eliminar archivo físico
        if (file_exists($ruta_imagen)) {
            unlink($ruta_imagen);
        }
        
        // Eliminar de base de datos
        $conn->query("DELETE FROM imagenes_producto WHERE id_imagen = $id_imagen");

        if ($es_principal_eliminada) {
            $result_next = $conn->query("SELECT id_imagen, ruta_imagen FROM imagenes_producto WHERE id_producto = $id ORDER BY orden ASC LIMIT 1");
            if ($result_next && $result_next->num_rows > 0) {
                $next = $result_next->fetch_assoc();
                $conn->query("UPDATE imagenes_producto SET es_principal = 1 WHERE id_imagen = " . intval($next['id_imagen']));
                $conn->query("UPDATE productos SET imagen_principal = '" . $conn->real_escape_string($next['ruta_imagen']) . "' WHERE id_producto = $id");
            } else {
                $conn->query("UPDATE productos SET imagen_principal = '' WHERE id_producto = $id");
            }
        }
        
        $exito = 'Imagen eliminada correctamente';
        
        // Recargar imágenes
        $result_img = $conn->query($query_img);
        if ($result_img) {
            $imagenes = $result_img->fetch_all(MYSQLI_ASSOC);
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = sanitizar_sql($conn, $_POST['nombre'] ?? '');
    $id_categoria = intval($_POST['id_categoria'] ?? 0);
    $marca = sanitizar_sql($conn, $_POST['marca'] ?? '');
    $descripcion = sanitizar_sql($conn, $_POST['descripcion'] ?? '');
    $precio_original = floatval($_POST['precio_original'] ?? 0);
    $precio_actual = floatval($_POST['precio_actual'] ?? 0);
    $descuento = intval($_POST['descuento_porcentaje'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
    $stock_minimo = intval($_POST['stock_minimo'] ?? 5);
    $plataforma = sanitizar_sql($conn, $_POST['plataforma'] ?? '');
    $genero = sanitizar_sql($conn, $_POST['genero'] ?? '');
    $destacado = isset($_POST['destacado']) ? 1 : 0;
    $estado = sanitizar_sql($conn, $_POST['estado'] ?? 'activo');

    if (empty($nombre) || !$id_categoria || $precio_original <= 0 || $precio_actual <= 0) {
        $error = 'Completa todos los campos requeridos';
    } else {
        if ($precio_original > 0) {
            $descuento = max(0, min(100, round((1 - $precio_actual / $precio_original) * 100)));
        } else {
            $descuento = 0;
        }
        $imagenes_subidas = [];
        $principalIndexNueva = isset($_POST['principal_index']) ? intval($_POST['principal_index']) : null;
        
        // Procesar nuevas imágenes
        if (isset($_FILES['imagenes']) && count($_FILES['imagenes']['name']) > 0) {
            $cantidad_imagenes = count($_FILES['imagenes']['name']);
            $tipos_permitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            
            for ($i = 0; $i < $cantidad_imagenes; $i++) {
                if ($_FILES['imagenes']['size'][$i] > 0) {
                    $archivo_tipo = $_FILES['imagenes']['type'][$i];
                    $archivo_tamaño = $_FILES['imagenes']['size'][$i];
                    $archivo_tmp = $_FILES['imagenes']['tmp_name'][$i];
                    $archivo_nombre_original = $_FILES['imagenes']['name'][$i];
                    
                    if (!in_array($archivo_tipo, $tipos_permitidos)) {
                        $error = 'Solo se permiten imágenes (JPG, PNG, GIF, WEBP)';
                        break;
                    } elseif ($archivo_tamaño > 5 * 1024 * 1024) { // 5MB
                        $error = 'Cada imagen no puede ser mayor a 5MB';
                        break;
                    } else {
                        $extension = pathinfo($archivo_nombre_original, PATHINFO_EXTENSION);
                        $nombre_imagen = 'producto_' . $id . '_' . time() . '_' . $i . '.' . $extension;
                        $ruta_destino = __DIR__ . '/../uploads/' . $nombre_imagen;
                        
                        if (move_uploaded_file($archivo_tmp, $ruta_destino)) {
                            $imagenes_subidas[] = $nombre_imagen;
                        } else {
                            $error = 'Error al subir una de las imágenes';
                            break;
                        }
                    }
                }
            }
        }
        
        if (!$error) {
            $query = "UPDATE productos SET nombre = '$nombre', id_categoria = $id_categoria, marca = '$marca', 
                      descripcion = '$descripcion', precio_original = $precio_original, precio_actual = $precio_actual,
                      descuento_porcentaje = $descuento, stock = $stock, stock_minimo = $stock_minimo,
                      plataforma = '$plataforma', genero = '$genero', destacado = $destacado, estado = '$estado'
                      WHERE id_producto = $id";
            
            if ($conn->query($query)) {
                // Agregar nuevas imágenes a la BD
                $establecerNuevaPrincipal = is_int($principalIndexNueva) && $principalIndexNueva >= 0 && $principalIndexNueva < count($imagenes_subidas);
                if ($establecerNuevaPrincipal) {
                    $conn->query("UPDATE imagenes_producto SET es_principal = 0 WHERE id_producto = $id");
                }

                if (count($imagenes_subidas) > 0) {
                    foreach ($imagenes_subidas as $idx => $img) {
                        $es_principal = ($establecerNuevaPrincipal && $idx === $principalIndexNueva) ? 1 : 0;
                        $orden = count($imagenes) + $idx;
                        $conn->query("INSERT INTO imagenes_producto (id_producto, ruta_imagen, es_principal, orden) 
                                      VALUES ($id, '$img', $es_principal, $orden)");
                        if ($es_principal) {
                            $conn->query("UPDATE productos SET imagen_principal = '$img' WHERE id_producto = $id");
                        }
                    }
                }
                
                if (!$establecerNuevaPrincipal && count($imagenes_subidas) > 0 && empty($producto['imagen_principal'])) {
                    $conn->query("UPDATE imagenes_producto SET es_principal = 1 WHERE id_producto = $id AND orden = " . count($imagenes));
                    $conn->query("UPDATE productos SET imagen_principal = '" . $conn->real_escape_string($imagenes_subidas[0]) . "' WHERE id_producto = $id");
                }

                $exito = 'Producto actualizado correctamente' . (count($imagenes_subidas) > 0 ? ' con ' . count($imagenes_subidas) . ' imagen(es) nueva(s)' : '');
                $producto = obtener_producto_por_id($conn, $id);
                
                // Recargar imágenes
                $result_img = $conn->query($query_img);
                if ($result_img) {
                    $imagenes = $result_img->fetch_all(MYSQLI_ASSOC);
                }
            } else {
                $error = 'Error: ' . $conn->error;
            }
        }
    }
}

require_once __DIR__ . '/admin_header.php';
admin_render_header('Editar Producto', 'Productos', 'fas fa-box');
?>

<style>
    .section { background: rgba(26, 26, 46, 0.95); border: 1px solid rgba(98, 0, 255, 0.35); border-radius: 16px; padding: 25px; max-width: 900px; }
    .form-group { margin-bottom: 15px; }
    label { color: #00d4ff; font-weight: bold; margin-bottom: 5px; display: block; }
    input, textarea, select { background: rgba(255, 255, 255, 0.9) !important; color: #0f172a !important; border: 1px solid rgba(98, 0, 255, 0.3) !important; padding: 10px !important; border-radius: 5px !important; width: 100% !important; }
    .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
    .gallery-item { display: inline-block; position: relative; margin-right: 10px; margin-bottom: 10px; }
    .gallery-thumb { width: 100px; height: 100px; object-fit: cover; border-radius: 5px; border: 2px solid rgba(98, 0, 255, 0.3); }
    .gallery-thumb.principal { border: 2px solid #00d4ff; box-shadow: 0 0 10px rgba(0, 212, 255, 0.3); }
    .btn-delete-img { position: absolute; top: 5px; right: 5px; background: #ef4444; color: white; border: none; padding: 2px 8px; border-radius: 3px; font-size: 0.8rem; cursor: pointer; }
    .btn-delete-img:hover { background: #dc2626; }
    .btn-set-primary { position: absolute; bottom: 5px; left: 5px; background: #00d4ff; color: #0a0a0a; border: none; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 700; cursor: pointer; }
    .btn-set-primary:hover { background: #22d3ee; }
    .principal-badge { position: absolute; bottom: 5px; left: 5px; background: #00d4ff; color: #0a0a0a; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 700; }
    .file-input-wrapper { position: relative; cursor: pointer; border: 2px dashed rgba(98, 0, 255, 0.5); border-radius: 8px; padding: 20px; background: rgba(255,255,255,0.05); transition: background .2s, border-color .2s; }
    .file-input-wrapper:hover { background: rgba(255,255,255,0.08); }
    .file-input-label { display: flex; align-items: center; justify-content: center; gap: 10px; color: #e0e0e0; }
    .image-preview { display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 12px; margin-top: 15px; }
    .preview-item { position: relative; border-radius: 10px; overflow: hidden; border: 2px solid rgba(98, 0, 255, 0.4); }
    .preview-item img { width: 100%; height: 100px; object-fit: cover; display: block; }
    .preview-badge { position: absolute; top: 5px; right: 5px; background: #00d4ff; color: #0a0a0a; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 700; }
    .btn-delete-preview { position: absolute; bottom: 5px; right: 5px; background: #ef4444; color: white; border: none; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; cursor: pointer; }
    .btn-delete-preview:hover { background: #dc2626; }
    .btn-set-principal { position: absolute; bottom: 5px; left: 5px; background: rgba(0,212,255,0.95); color: #0a0a0a; border: none; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 700; cursor: pointer; }
    .btn-set-principal:hover { background: rgba(0,212,255,1); }
    .imagenes-section { background: rgba(98, 0, 255, 0.1); padding: 15px; border-radius: 8px; margin-bottom: 20px; }
</style>

<div class="section">
    <h3 style="color: #00d4ff; margin-bottom: 20px;"><i class="fas fa-edit"></i> Editar Producto</h3>
    
    <?php if ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($exito): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($exito); ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <!-- Información Básica -->
        <div style="margin-bottom: 30px;">
            <h4 style="color: #00d4ff; margin-bottom: 15px;"><i class="fas fa-info-circle"></i> Información Básica</h4>
            
            <div class="form-group">
                <label for="nombre">Nombre *</label>
                <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($producto['nombre']); ?>" required>
            </div>

            <div class="two-col">
                <div class="form-group">
                    <label for="id_categoria">Categoría *</label>
                    <select id="id_categoria" name="id_categoria" required>
                        <?php foreach ($categorias as $cat): ?>
                        <option value="<?php echo $cat['id_categoria']; ?>" <?php echo $cat['id_categoria'] == $producto['id_categoria'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['nombre_categoria']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="marca">Marca</label>
                    <input type="text" id="marca" name="marca" value="<?php echo htmlspecialchars($producto['marca'] ?? ''); ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="descripcion">Descripción</label>
                <textarea id="descripcion" name="descripcion" rows="3"><?php echo htmlspecialchars($producto['descripcion'] ?? ''); ?></textarea>
            </div>

            <div class="two-col">
                <div class="form-group">
                    <label for="plataforma">Plataforma</label>
                    <input type="text" id="plataforma" name="plataforma" value="<?php echo htmlspecialchars($producto['plataforma'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="genero">Género</label>
                    <input type="text" id="genero" name="genero" value="<?php echo htmlspecialchars($producto['genero'] ?? ''); ?>">
                </div>
            </div>

            <div class="two-col">
                <div class="form-group">
                    <label for="precio_original">Precio Original *</label>
                    <input type="number" id="precio_original" name="precio_original" step="0.01" value="<?php echo $producto['precio_original']; ?>" required>
                </div>

                <div class="form-group">
                    <label for="precio_actual">Precio Actual *</label>
                    <input type="number" id="precio_actual" name="precio_actual" step="0.01" value="<?php echo $producto['precio_actual']; ?>" required>
                </div>
            </div>

            <div class="two-col">
                <div class="form-group">
                    <label for="descuento_porcentaje">Descuento (%)</label>
                    <input type="number" id="descuento_porcentaje" name="descuento_porcentaje" min="0" max="100" value="<?php echo htmlspecialchars($producto['descuento_porcentaje'] ?? 0); ?>">
                </div>

                <div class="form-group">
                    <label for="stock">Stock</label>
                    <input type="number" id="stock" name="stock" value="<?php echo $producto['stock']; ?>">
                </div>
            </div>

            <div class="two-col">
                <div class="form-group">
                    <label for="stock_minimo">Stock Mínimo</label>
                    <input type="number" id="stock_minimo" name="stock_minimo" min="0" value="<?php echo $producto['stock_minimo']; ?>">
                </div>

                <div class="form-group">
                    <label for="estado">Estado</label>
                    <select id="estado" name="estado">
                        <option value="activo" <?php echo $producto['estado'] == 'activo' ? 'selected' : ''; ?>>Activo</option>
                        <option value="inactivo" <?php echo $producto['estado'] == 'inactivo' ? 'selected' : ''; ?>>Inactivo</option>
                        <option value="agotado" <?php echo $producto['estado'] == 'agotado' ? 'selected' : ''; ?>>Agotado</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" name="destacado" <?php echo $producto['destacado'] ? 'checked' : ''; ?>>
                    Producto Destacado
                </label>
            </div>
        </div>

        <!-- Gestión de Imágenes -->
        <div style="margin-bottom: 30px;">
            <h4 style="color: #00d4ff; margin-bottom: 15px;"><i class="fas fa-images"></i> Imágenes del Producto</h4>
            
            <!-- Imágenes Existentes -->
            <?php if (!empty($imagenes)): ?>
            <div class="imagenes-section">
                <h5 style="color: #e0e0e0; margin-bottom: 15px;">Imágenes Actuales (<?php echo count($imagenes); ?>)</h5>
                <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                    <?php foreach ($imagenes as $img): ?>
                    <div class="gallery-item">
                        <img src="../uploads/<?php echo htmlspecialchars($img['ruta_imagen']); ?>" 
                             class="gallery-thumb <?php echo $img['es_principal'] ? 'principal' : ''; ?>"
                             alt="Imagen del producto"
                             title="<?php echo $img['es_principal'] ? 'Imagen Principal' : 'Imagen'; ?>">
                        <?php if ($img['es_principal']): ?>
                            <div class="principal-badge">Principal</div>
                        <?php else: ?>
                            <a href="?id=<?php echo $id; ?>&principal_imagen=<?php echo $img['id_imagen']; ?>" 
                               class="btn-set-primary"
                               onclick="return confirm('¿Establecer esta imagen como principal?')">
                                <i class="fas fa-star"></i> Principal
                            </a>
                        <?php endif; ?>
                        <a href="?id=<?php echo $id; ?>&eliminar_imagen=<?php echo $img['id_imagen']; ?>" 
                           class="btn-delete-img"
                           onclick="return confirm('¿Eliminar esta imagen?')">
                            <i class="fas fa-trash"></i> Eliminar
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php else: ?>
            <div class="imagenes-section">
                <p style="color: #aaa;"><i class="fas fa-image"></i> Este producto no tiene imágenes aún</p>
            </div>
            <?php endif; ?>
            
            <!-- Agregar Nuevas Imágenes -->
            <div class="form-group">
                <label for="imagenesNuevas">Agregar Nuevas Imágenes</label>
                <div class="file-input-wrapper" id="dropZoneEdit">
                    <label class="file-input-label" for="imagenesNuevas">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <div>
                            <strong>Haz clic o arrastra múltiples imágenes aquí</strong>
                            <small>Selecciona varias imágenes a la vez</small>
                        </div>
                    </label>
                    <input type="file" id="imagenesNuevas" name="imagenes[]" multiple accept="image/*" style="display:none;">
                    <input type="hidden" id="principalIndexEdit" name="principal_index" value="0">
                </div>
                <small style="color: #aaa; display: block; margin-top: 8px;">Máximo 5 imágenes de 5MB cada una. Formatos: JPG, PNG, GIF, WEBP</small>
                <div id="editImagePreview" class="image-preview"></div>
            </div>
        </div>

        <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Guardar Cambios</button>
        <a href="productos.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancelar</a>
    </form>
</div>

<script>
    const editFileInput = document.getElementById('imagenesNuevas');
    const editPreview = document.getElementById('editImagePreview');
    const editPrincipalInput = document.getElementById('principalIndexEdit');
    const dropZoneEdit = document.getElementById('dropZoneEdit');
    let selectedEditFiles = [];

    editFileInput.addEventListener('change', function(e) {
        selectedEditFiles = Array.from(e.target.files);
        updateEditPreview();
    });

    function updateEditPreview() {
        editPreview.innerHTML = '';

        selectedEditFiles.forEach((file, index) => {
            const reader = new FileReader();

            reader.onload = function(e) {
                const div = document.createElement('div');
                div.className = 'preview-item';
                div.innerHTML = `
                    <img src="${e.target.result}" alt="Preview">
                    ${index === 0 ? '<span class="preview-badge">Principal</span>' : '<button type="button" class="btn-set-principal" onclick="setPrincipalEdit(' + index + ')">Hacer principal</button>'}
                    <button type="button" class="btn-delete-preview" onclick="deleteEditPreview(${index})">
                        <i class="fas fa-trash"></i> Eliminar
                    </button>
                `;
                editPreview.appendChild(div);
            };

            reader.readAsDataURL(file);
        });
    }

    function updateEditFileInput() {
        const dataTransfer = new DataTransfer();
        selectedEditFiles.forEach(file => dataTransfer.items.add(file));
        editFileInput.files = dataTransfer.files;
    }

    function setPrincipalEdit(index) {
        if (index === 0) return;
        const [file] = selectedEditFiles.splice(index, 1);
        selectedEditFiles.unshift(file);
        editPrincipalInput.value = 0;
        updateEditFileInput();
        updateEditPreview();
    }

    function deleteEditPreview(index) {
        selectedEditFiles.splice(index, 1);
        updateEditFileInput();
        updateEditPreview();
    }

    dropZoneEdit.addEventListener('click', () => editFileInput.click());
    dropZoneEdit.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZoneEdit.style.background = 'rgba(0, 212, 255, 0.15)';
    });
    dropZoneEdit.addEventListener('dragleave', () => {
        dropZoneEdit.style.background = '';
    });
    dropZoneEdit.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZoneEdit.style.background = '';
        selectedEditFiles = Array.from(e.dataTransfer.files);
        updateEditFileInput();
        updateEditPreview();
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

    if (precioOriginal && precioActual && descuentoPorcentaje) {
        precioOriginal.addEventListener('input', actualizarPrecioActual);
        descuentoPorcentaje.addEventListener('input', actualizarPrecioActual);
        precioActual.addEventListener('input', actualizarDescuento);
    }
</script>

<?php admin_render_footer(); ?>
