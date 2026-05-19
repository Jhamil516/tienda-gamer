<?php
require_once __DIR__ . '/header.php';

$id = intval($_GET['id'] ?? 0);
$producto = obtener_producto_por_id($conn, $id);
$imagenes = $producto ? obtener_imagenes_producto($conn, $id) : [];

if (!$producto) {
    header('Location: productos.php');
    exit;
}

empleado_render_header('Ver Producto', 'fas fa-eye');
?>
<style>
    .section { background: rgba(26,26,46,0.95); border: 1px solid rgba(98,0,255,0.35); border-radius: 16px; padding: 25px; }
    .product-image { width: 100%; max-width: 360px; object-fit: cover; border-radius: 14px; }
    .badge { padding: 6px 10px; border-radius: 10px; font-weight: 700; }
    .badge-estado { background: rgba(0,212,255,0.15); color: #00d4ff; }
    .gallery { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 16px; }
    .gallery img { width: 90px; height: 90px; object-fit: cover; border-radius: 10px; border: 2px solid rgba(98,0,255,0.2); }
</style>

<div class="section">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap: 12px; margin-bottom: 20px;">
        <div>
            <h3 style="color:#00d4ff; margin-bottom: 8px;"><i class="fas fa-box-open"></i> <?php echo htmlspecialchars($producto['nombre']); ?></h3>
            <span class="badge badge-estado"><?php echo htmlspecialchars(ucfirst($producto['estado'])); ?></span>
        </div>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <a href="productos.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
            <a href="editar_producto.php?id=<?php echo $producto['id_producto']; ?>" class="btn btn-warning"><i class="fas fa-edit"></i> Editar</a>
        </div>
    </div>

    <div style="display:grid; grid-template-columns: minmax(0, 360px) 1fr; gap: 24px; align-items: start;">
        <div>
            <?php if (!empty($producto['imagen_principal'])): ?>
                <img src="../uploads/<?php echo htmlspecialchars($producto['imagen_principal']); ?>" alt="Imagen del producto" class="product-image">
            <?php else: ?>
                <div class="product-image" style="background: rgba(255,255,255,0.08); display:flex; align-items:center; justify-content:center; color:#888; font-size:1.2rem;">Sin imagen</div>
            <?php endif; ?>

            <?php if (!empty($imagenes)): ?>
                <div class="gallery">
                    <?php foreach ($imagenes as $img): ?>
                        <img src="../uploads/<?php echo htmlspecialchars($img['ruta_imagen']); ?>" alt="Imagen secundaria">
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div>
            <p><strong>Categoría:</strong> <?php echo htmlspecialchars($producto['nombre_categoria']); ?></p>
            <p><strong>Marca:</strong> <?php echo htmlspecialchars($producto['marca'] ?? 'N/A'); ?></p>
            <p><strong>Plataforma:</strong> <?php echo htmlspecialchars($producto['plataforma'] ?? 'N/A'); ?></p>
            <p><strong>Género:</strong> <?php echo htmlspecialchars($producto['genero'] ?? 'N/A'); ?></p>
            <p><strong>Precio Actual:</strong> <span style="color:#00d4ff; font-weight:700;">BOB <?php echo number_format($producto['precio_actual'], 2); ?></span></p>
            <p><strong>Precio Original:</strong> BOB <?php echo number_format($producto['precio_original'], 2); ?></p>
            <p><strong>Stock:</strong> <?php echo intval($producto['stock']); ?> unidades</p>
            <p><strong>Stock mínimo:</strong> <?php echo intval($producto['stock_minimo']); ?></p>
            <p><strong>Descripción:</strong><br><?php echo nl2br(htmlspecialchars($producto['descripcion'] ?? 'Sin descripción')); ?></p>
        </div>
    </div>
</div>

<?php empleado_render_footer(); ?>
