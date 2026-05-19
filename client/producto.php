<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/funciones.php';

$id_producto = intval($_GET['id'] ?? 0);
if ($id_producto <= 0) {
    header('Location: catalogo.php');
    exit;
}

$producto = obtener_producto_por_id($conn, $id_producto);
if (!$producto) {
    header('Location: catalogo.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_favorito'])) {
    if (!es_autenticado()) {
        header('Location: ../auth/login.php');
        exit;
    }

    if (es_favorito($conn, $_SESSION['id_usuario'], $id_producto)) {
        eliminar_favorito($conn, $_SESSION['id_usuario'], $id_producto);
    } else {
        agregar_favorito($conn, $_SESSION['id_usuario'], $id_producto);
    }

    header('Location: producto.php?id=' . $id_producto . '&favorito=1');
    exit;
}

$imagenes = obtener_imagenes_producto($conn, $id_producto);
$es_favorito = es_autenticado() ? es_favorito($conn, $_SESSION['id_usuario'], $id_producto) : false;
$promociones_aplicables = obtener_promociones_activas($conn, $id_producto, $producto['id_categoria']);
$precio_con_mejor_descuento = count($promociones_aplicables) ? obtener_mejor_precio_promocional($producto['precio_actual'], $promociones_aplicables) : null;

// Obtener reseñas
$resenas = obtener_resenas_producto($conn, $id_producto, true);
$usuario_ya_resenio = es_autenticado() ? usuario_ya_resenio_producto($conn, $_SESSION['id_usuario'], $id_producto) : false;
?>
<?php include '../includes/header.php'; ?>
<style>
    .product-detail {
        background: rgba(26, 26, 46, 0.92);
        border: 2px solid rgba(0, 212, 255, 0.18);
        border-radius: 18px;
        padding: 30px;
        margin: 30px 0;
    }
    .product-image-main {
        width: 100%;
        max-height: 420px;
        object-fit: cover;
        border-radius: 14px;
        border: 2px solid rgba(0, 212, 255, 0.2);
    }
    .product-gallery { display: flex; gap: 10px; margin-top: 18px; overflow-x: auto; }
    .gallery-thumb { width: 80px; height: 80px; cursor: pointer; border: 2px solid rgba(255,255,255,0.08); border-radius: 10px; transition: all 0.25s ease; object-fit: cover; }
    .gallery-thumb:hover { border-color: #00d4ff; }
    .product-title { font-size: 2rem; font-weight: 700; color: #00d4ff; margin-bottom: 18px; }
    .product-price { font-size: 2rem; color: #00d4ff; font-weight: 700; margin-bottom: 16px; }
    .price-original { color: #888; text-decoration: line-through; font-size: 1.1rem; margin-left: 12px; }
    .btn-add-cart { background: linear-gradient(135deg, #6200ff, #00d4ff); border: none; color: white; padding: 12px 24px; border-radius: 10px; }
    .btn-add-cart:hover { box-shadow: 0 0 18px rgba(0, 212, 255, 0.4); }
    .btn-favorite { border: 2px solid #00d4ff; color: #00d4ff; background: transparent; padding: 12px 22px; border-radius: 10px; }
    .btn-favorite:hover { background: rgba(0, 212, 255, 0.12); }
    .review-section { margin-top: 40px; padding-top: 30px; border-top: 2px solid rgba(0, 212, 255, 0.18); }
    .review-card { background: rgba(26, 26, 46, 0.5); border-left: 4px solid #00d4ff; padding: 18px; margin-bottom: 16px; border-radius: 8px; }
    .review-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 12px; }
    .review-author { font-weight: 600; color: #00d4ff; }
    .review-date { color: #888; font-size: 0.9rem; }
    .review-rating { color: #ffc107; font-size: 1.2rem; }
    .review-title { font-weight: 600; font-size: 1.1rem; margin-bottom: 8px; }
    .review-text { color: #ccc; line-height: 1.5; }
    .modal-content { background: rgba(26, 26, 46, 0.95); border: 2px solid rgba(0, 212, 255, 0.18); }
    .modal-header { border-bottom: 2px solid rgba(0, 212, 255, 0.18); }
    .form-control { background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(0, 212, 255, 0.18); color: #fff; }
    .form-control:focus { background: rgba(255, 255, 255, 0.08); border-color: #00d4ff; color: #fff; box-shadow: 0 0 10px rgba(0, 212, 255, 0.2); }
    .form-label { color: #00d4ff; }
    .star-rating { display: flex; gap: 8px; margin: 12px 0; }
    .star { font-size: 2rem; cursor: pointer; color: #444; transition: all 0.2s ease; }
    .star:hover, .star.active { color: #ffc107; }

</style>

<div class="row mb-4 mt-4">
    <div class="col-md-12">
        <h2><?php echo htmlspecialchars($producto['nombre']); ?></h2>
        <p class="text-muted">Detalles del producto, promociones y favoritos.</p>
    </div>
</div>

<?php if (isset($_GET['favorito'])): ?>
    <div class="alert alert-success">Preferencia de favorito actualizada.</div>
<?php endif; ?>

<div class="product-detail">
    <div class="row">
        <div class="col-md-5">
            <img id="mainImage" src="<?php echo BASE_URL . 'uploads/' . htmlspecialchars($producto['imagen_principal']); ?>" alt="<?php echo htmlspecialchars($producto['nombre']); ?>" class="product-image-main">
            <?php if (!empty($imagenes)): ?>
                <div class="product-gallery">
                    <?php foreach ($imagenes as $img): ?>
                        <img src="<?php echo BASE_URL . 'uploads/' . htmlspecialchars($img['ruta_imagen']); ?>" class="gallery-thumb" onclick="cambiarImagen('<?php echo htmlspecialchars($img['ruta_imagen']); ?>')" alt="Miniatura">
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="col-md-7">
            <div class="product-title"><?php echo htmlspecialchars($producto['nombre']); ?></div>
            <div class="mb-3">
                <span class="badge bg-primary"><?php echo htmlspecialchars($producto['nombre_categoria']); ?></span>
                <?php if (!empty($producto['marca'])): ?>
                    <span class="badge bg-secondary"><?php echo htmlspecialchars($producto['marca']); ?></span>
                <?php endif; ?>
                <?php if (!empty($producto['plataforma'])): ?>
                    <span class="badge bg-info text-dark"><?php echo htmlspecialchars($producto['plataforma']); ?></span>
                <?php endif; ?>
            </div>
            <div class="mb-4">
                <div class="product-price">
                    BOB <?php echo number_format($producto['precio_actual'], 2, ',', '.'); ?>
                    <?php if (!empty($producto['precio_original']) && $producto['precio_original'] > $producto['precio_actual']): ?>
                        <span class="price-original">BOB <?php echo number_format($producto['precio_original'], 2, ',', '.'); ?></span>
                    <?php endif; ?>
                </div>
                <?php if (!empty($promociones_aplicables) && $precio_con_mejor_descuento < $producto['precio_actual']): ?>
                    <div class="alert alert-info">Mejor precio con promoción: <strong>BOB <?php echo number_format($precio_con_mejor_descuento, 2, ',', '.'); ?></strong></div>
                <?php endif; ?>
            </div>
            <div class="mb-4">
                <p><strong>Stock:</strong> <?php echo $producto['stock'] > 0 ? $producto['stock'] : 'Agotado'; ?></p>
            </div>
            <?php if ($producto['stock'] > 0): ?>
                <form method="POST" action="carrito.php">
                    <input type="hidden" name="id_producto" value="<?php echo $producto['id_producto']; ?>">
                    <div class="mb-3">
                        <label class="form-label">Cantidad</label>
                        <input type="number" name="cantidad" value="1" min="1" max="<?php echo $producto['stock']; ?>" class="form-control" style="width: 120px;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Cupón (opcional)</label>
                        <input type="text" name="codigo_cupon" class="form-control" placeholder="Código de descuento">
                    </div>
                    <button type="submit" class="btn btn-add-cart"><i class="fas fa-shopping-cart"></i> Agregar al carrito</button>
                </form>
            <?php else: ?>
                <div class="alert alert-warning">Producto agotado. Revisa otros artículos en catálogo.</div>
            <?php endif; ?>
            <div class="mt-4">
                <?php if (es_autenticado()): ?>
                    <form method="POST" action="producto.php?id=<?php echo $producto['id_producto']; ?>">
                        <button type="submit" name="toggle_favorito" value="1" class="btn btn-favorite w-100">
                            <i class="fas fa-heart"></i> <?php echo $es_favorito ? 'Quitar de favoritos' : 'Agregar a favoritos'; ?>
                        </button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-light text-dark">Inicia sesión para guardar este producto en tus favoritos.</div>
                <?php endif; ?>
            </div>
            <?php if (!empty($promociones_aplicables)): ?>
                <div class="mt-4">
                    <h5>Promociones disponibles</h5>
                    <?php foreach ($promociones_aplicables as $promo): ?>
                        <div class="badge bg-warning text-dark d-block mb-2 p-2">
                            <strong><?php echo htmlspecialchars($promo['nombre_promocion']); ?></strong> -
                            <?php if ($promo['tipo'] === 'porcentaje'): ?>
                                <?php echo htmlspecialchars($promo['valor']); ?>%
                            <?php else: ?>
                                BOB <?php echo number_format($promo['valor'], 2, ',', '.'); ?>
                            <?php endif; ?>
                            <?php if (!empty($promo['codigo_cupon'])): ?>(Código: <?php echo htmlspecialchars($promo['codigo_cupon']); ?>)<?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <hr>
            <h5>Descripción</h5>
            <p><?php echo nl2br(htmlspecialchars($producto['descripcion'] ?? 'Sin descripción disponible.')); ?></p>
        </div>
    </div>
</div>

<script>
    function cambiarImagen(src) {
        document.getElementById('mainImage').src = '<?php echo BASE_URL; ?>uploads/' + src;
    }
</script>

<!-- SECCIÓN DE RESEÑAS -->
<div class="review-section">
    <div class="row mb-4">
        <div class="col-md-6">
            <h3>Reseñas de Clientes</h3>
            <?php if ($producto['cantidad_resenas'] > 0): ?>
                <div class="mb-3">
                    <strong><?php echo number_format($producto['valoracion'], 1); ?> ⭐</strong> 
                    <span class="text-muted">(<?php echo $producto['cantidad_resenas']; ?> reseña<?php echo $producto['cantidad_resenas'] !== 1 ? 's' : ''; ?>)</span>
                </div>
            <?php else: ?>
                <p class="text-muted">Sin reseñas aún. ¡Sé el primero en comentar!</p>
            <?php endif; ?>
        </div>
        <div class="col-md-6 text-end">
            <?php if (es_autenticado()): ?>
                <?php if (!$usuario_ya_resenio): ?>
                    <button type="button" class="btn btn-add-cart" data-bs-toggle="modal" data-bs-target="#modalResena">
                        <i class="fas fa-star"></i> Dejar una Reseña
                    </button>
                <?php else: ?>
                    <div class="alert alert-info mb-0">Ya has reseñado este producto</div>
                <?php endif; ?>
            <?php else: ?>
                <button type="button" class="btn btn-outline-light" onclick="window.location.href='../auth/login.php'">
                    <i class="fas fa-sign-in-alt"></i> Inicia sesión para comentar
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Listado de reseñas -->
    <div id="reviewsList">
        <?php if (count($resenas) > 0): ?>
            <?php foreach ($resenas as $review): ?>
                <div class="review-card">
                    <div class="review-header">
                        <div>
                            <div class="review-author"><?php echo htmlspecialchars($review['nombre']); ?></div>
                            <div class="review-date"><?php echo formatear_fecha($review['fecha_resena']); ?></div>
                        </div>
                        <div class="review-rating">
                            <?php echo str_repeat('★', intval($review['valoracion'])); ?><?php echo str_repeat('☆', 5 - intval($review['valoracion'])); ?>
                        </div>
                    </div>
                    <div class="review-title"><?php echo htmlspecialchars($review['titulo']); ?></div>
                    <div class="review-text"><?php echo nl2br(htmlspecialchars($review['descripcion'])); ?></div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="alert alert-info">No hay reseñas aprobadas aún. ¡Sé el primero!</div>
        <?php endif; ?>
    </div>
</div>

<!-- MODAL PARA CREAR RESEÑA -->
<div class="modal fade" id="modalResena" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Dejar una Reseña</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="alertResena"></div>
                <form id="formResena">
                    <input type="hidden" name="id_producto" value="<?php echo $id_producto; ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Valoración</label>
                        <div class="star-rating">
                            <span class="star" data-value="1">★</span>
                            <span class="star" data-value="2">★</span>
                            <span class="star" data-value="3">★</span>
                            <span class="star" data-value="4">★</span>
                            <span class="star" data-value="5">★</span>
                        </div>
                        <input type="hidden" name="valoracion" id="inputValoracion" value="0">
                    </div>

                    <div class="mb-3">
                        <label for="inputTitulo" class="form-label">Título de tu reseña</label>
                        <input type="text" class="form-control" id="inputTitulo" name="titulo" placeholder="Ej: Excelente producto" required>
                        <small class="text-muted">Mínimo 5 caracteres</small>
                    </div>

                    <div class="mb-3">
                        <label for="inputDescripcion" class="form-label">Tu comentario</label>
                        <textarea class="form-control" id="inputDescripcion" name="descripcion" rows="5" placeholder="Cuéntanos tu experiencia con este producto..." required></textarea>
                        <small class="text-muted">Mínimo 10 caracteres</small>
                    </div>

                    <button type="submit" class="btn btn-add-cart w-100">
                        <i class="fas fa-paper-plane"></i> Enviar Reseña
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Sistema de estrellas para valoración
    const stars = document.querySelectorAll('.star-rating .star');
    const inputValoracion = document.getElementById('inputValoracion');

    stars.forEach(star => {
        star.addEventListener('click', function() {
            const value = this.getAttribute('data-value');
            inputValoracion.value = value;
            
            stars.forEach(s => {
                if (s.getAttribute('data-value') <= value) {
                    s.classList.add('active');
                } else {
                    s.classList.remove('active');
                }
            });
        });

        star.addEventListener('mouseover', function() {
            const value = this.getAttribute('data-value');
            stars.forEach(s => {
                if (s.getAttribute('data-value') <= value) {
                    s.style.color = '#ffc107';
                } else {
                    s.style.color = '#444';
                }
            });
        });
    });

    document.querySelector('.star-rating').addEventListener('mouseleave', function() {
        const value = inputValoracion.value || 0;
        stars.forEach(s => {
            if (s.getAttribute('data-value') <= value) {
                s.style.color = '#ffc107';
            } else {
                s.style.color = '#444';
            }
        });
    });

    // Enviar formulario de reseña
    document.getElementById('formResena').addEventListener('submit', async function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const alertDiv = document.getElementById('alertResena');

        try {
            const response = await fetch('./crear_resena.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.error) {
                alertDiv.innerHTML = `<div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle"></i> ${data.error}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>`;
            } else if (data.exito) {
                alertDiv.innerHTML = `<div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle"></i> ${data.mensaje}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>`;
                document.getElementById('formResena').reset();
                inputValoracion.value = 0;
                stars.forEach(s => s.classList.remove('active'));
                
                // Cerrar modal después de 2 segundos
                setTimeout(() => {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('modalResena'));
                    modal.hide();
                }, 2000);
            }
        } catch (error) {
            alertDiv.innerHTML = `<div class="alert alert-danger" role="alert">
                <i class="bi bi-exclamation-triangle"></i> Error al enviar la reseña. Intenta de nuevo.
            </div>`;
        }
    });
    
    function cambiarImagen(src) {
        document.getElementById('mainImage').src = '<?php echo BASE_URL; ?>uploads/' + src;
    }
</script>

<?php include '../includes/footer.php'; ?>
