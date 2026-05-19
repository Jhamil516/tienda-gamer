<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/funciones.php';

if (!es_admin()) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

$id = intval($_GET['id'] ?? 0);
$producto = null;
$imagenes = [];

if ($id > 0) {
    $producto = obtener_producto_por_id($conn, $id);
    if ($producto) {
        $imagenes = obtener_imagenes_producto($conn, $id);
    }
}

if (!$producto) {
    header('Location: productos.php');
    exit;
}

require_once __DIR__ . '/admin_header.php';
admin_render_header('Ver Producto', 'Productos', 'fas fa-eye');
?>
    <style>
        :root {
            --primary: #6200ff;
            --accent: #00d4ff;
            --dark-bg: #0a0a0a;
            --text-light: #e0e0e0;
        }
        body {
            background: linear-gradient(135deg, var(--dark-bg) 0%, #1a0033 100%);
            color: var(--text-light);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
        }
        .card {
            background: rgba(26, 26, 46, 0.8);
            border: 2px solid var(--primary);
            color: var(--text-light);
        }
        .card-body {
            padding: 25px;
        }
        .product-image {
            width: 100%;
            max-width: 320px;
            height: 320px;
            object-fit: cover;
            border-radius: 10px;
        }

        .product-gallery {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            flex-wrap: wrap;
        }

        .gallery-thumb {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: 8px;
            cursor: pointer;
            border: 2px solid transparent;
            transition: border-color 0.2s ease;
        }

        .gallery-thumb:hover,
        .gallery-thumb.active {
            border-color: var(--accent);
        }

        .btn {
            color: #fff;
        }
    </style>
    <div class="container">
            <div class="topbar">
                <h2><i class="fas fa-eye"></i> Ver Producto</h2>
                <a href="productos.php" class="btn btn-outline-light"><i class="fas fa-arrow-left"></i> Volver</a>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="row gy-4">
                        <div class="col-md-4">
                            <?php if (!empty($producto['imagen_principal'])): ?>
                                <img id="mainImage" src="../uploads/<?php echo htmlspecialchars($producto['imagen_principal']); ?>" alt="Producto" class="product-image">
                            <?php else: ?>
                                <div class="product-image bg-secondary d-flex align-items-center justify-content-center">
                                    <i class="fas fa-image fa-5x"></i>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($imagenes)): ?>
                            <div class="product-gallery">
                                <?php foreach ($imagenes as $img): ?>
                                <img src="../uploads/<?php echo htmlspecialchars($img['ruta_imagen']); ?>" class="gallery-thumb" data-src="../uploads/<?php echo htmlspecialchars($img['ruta_imagen']); ?>" alt="Miniatura">
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-8">
                            <h2 style="color: var(--accent);"><?php echo htmlspecialchars($producto['nombre']); ?></h2>
                            <p><strong>Marca:</strong> <?php echo htmlspecialchars($producto['marca'] ?? 'N/A'); ?></p>
                            <p><strong>Categoría:</strong> <?php echo htmlspecialchars($producto['nombre_categoria']); ?></p>
                            <p><strong>Descripción:</strong> <?php echo htmlspecialchars($producto['descripcion'] ?? 'N/A'); ?></p>
                            <hr>
                            <p style="font-size: 1.5rem;"><strong>Precio:</strong> <span style="color: var(--accent);">BOB <?php echo number_format($producto['precio_actual'], 2); ?></span></p>
                            <p><strong>Stock:</strong> <span class="badge bg-success"><?php echo intval($producto['stock']); ?> unidades</span></p>
                            <p><strong>Estado:</strong> <span class="badge bg-info"><?php echo htmlspecialchars($producto['estado']); ?></span></p>
                            <a href="editar_producto.php?id=<?php echo intval($producto['id_producto']); ?>" class="btn btn-warning mt-3">
                                <i class="fas fa-edit"></i> Editar Producto
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('.gallery-thumb').forEach((thumb) => {
            thumb.addEventListener('click', () => {
                const mainImage = document.getElementById('mainImage');
                mainImage.src = thumb.dataset.src;
                document.querySelectorAll('.gallery-thumb').forEach((t) => t.classList.remove('active'));
                thumb.classList.add('active');
            });
        });
    </script>
<?php admin_render_footer(); ?>