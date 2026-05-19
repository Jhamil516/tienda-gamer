<?php
session_start();
$_SESSION['BASE_URL'] = '/tienda-gamer';

require '../config/db.php';
require '../auth/proteger.php';
requerirCliente();

$id_celular = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';
$exito = '';

if ($id_celular === 0) {
    header('Location: catalogo.php');
    exit;
}

// Obtener celular
$stmt = $conn->prepare("SELECT * FROM celulares WHERE id_celular = ? AND stock > 0");
$stmt->bind_param("i", $id_celular);
$stmt->execute();
$resultado = $stmt->get_result();
$celular = $resultado->fetch_assoc();

if (!$celular) {
    header('Location: catalogo.php');
    exit;
}

// Obtener todas las imágenes del producto
$stmt_img = $conn->prepare("SELECT imagen FROM celular_imagenes WHERE id_celular = ? ORDER BY principal DESC, id_imagen ASC");
$stmt_img->bind_param("i", $id_celular);
$stmt_img->execute();
$resultado_img = $stmt_img->get_result();
$imagenes = $resultado_img->fetch_all(MYSQLI_ASSOC);

// Procesar agregar al carrito
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $cantidad = isset($_POST['cantidad']) ? max(1, (int)$_POST['cantidad']) : 1;

    // Validar cantidad
    if ($cantidad < 1 || $cantidad > $celular['stock']) {
        $error = "❌ Cantidad inválida. Máximo disponible: " . $celular['stock'];
    } else {
        // Inicializar carrito en sesión
        if (!isset($_SESSION['carrito'])) {
            $_SESSION['carrito'] = array();
        }

        // Obtener mejor imagen para el carrito
        $mejor_imagen = obtenerImagenProducto($conn, $id_celular, $celular['imagen']);

        // Verificar si el producto ya está en el carrito
        $encontrado = false;
        foreach ($_SESSION['carrito'] as &$item) {
            if ($item['id_celular'] == $id_celular) {
                $encontrado = true;
                $cantidad_total = $item['cantidad'] + $cantidad;
                if ($cantidad_total > $celular['stock']) {
                    $error = "❌ No hay stock suficiente. Ya tienes " . $item['cantidad'] . " en el carrito. Máximo total: " . $celular['stock'];
                } else {
                    $item['cantidad'] = $cantidad_total;
                    $exito = "✅ Cantidad actualizada en el carrito";
                }
                break;
            }
        }

        // Si no está en carrito, agregarlo
        if (!$encontrado && !$error) {
            $_SESSION['carrito'][] = array(
                'id_celular' => $celular['id_celular'],
                'marca' => $celular['marca'],
                'modelo' => $celular['modelo'],
                'precio' => $celular['precio'],
                'imagen' => $mejor_imagen,
                'cantidad' => $cantidad
            );
            $exito = "✅ Producto agregado al carrito exitosamente";
        }
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="container">
    <div class="row mb-4 mt-4">
        <div class="col-md-12">
            <a href="catalogo.php" class="btn btn-secondary">← Volver al Catálogo</a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle"></i>
            <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($exito): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle"></i>
            <?php echo $exito; ?>
            <a href="carrito.php" class="alert-link">Ver carrito</a>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-5">
            <div class="card">
                <!-- Galería de imágenes -->
                <div id="galeriaCarousel" class="carousel slide" data-bs-ride="carousel">
                    <div class="carousel-inner">
                        <?php if (count($imagenes) > 0): ?>
                            <?php foreach ($imagenes as $idx => $img): ?>
                                <div class="carousel-item <?php echo $idx == 0 ? 'active' : ''; ?>">
                                    <img src="/tienda-gamer/uploads/<?php echo htmlspecialchars($img['imagen']); ?>"
                                         class="d-block w-100" alt="Imagen <?php echo $idx + 1; ?>"
                                         style="height: 400px; object-fit: cover;">
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="carousel-item active">
                                <div class="d-flex align-items-center justify-content-center" style="height: 400px; background-color: #e9ecef;">
                                    <span class="text-muted">Sin imágenes</span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (count($imagenes) > 1): ?>
                        <button class="carousel-control-prev" type="button" data-bs-target="#galeriaCarousel" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon"></span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#galeriaCarousel" data-bs-slide="next">
                            <span class="carousel-control-next-icon"></span>
                        </button>
                    <?php endif; ?>
                </div>
                
                <?php if (count($imagenes) > 1): ?>
                    <div class="card-footer">
                        <small class="text-muted">Imagen 1 de <?php echo count($imagenes); ?></small>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-md-7">
            <div class="card">
                <div class="card-body">
                    <h2 class="card-title"><?php echo htmlspecialchars($celular['marca']); ?></h2>
                    <h4 class="card-subtitle text-muted"><?php echo htmlspecialchars($celular['modelo']); ?></h4>

                    <hr>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5>Precio</h5>
                            <p class="h3 text-success">BOB <?php echo number_format($celular['precio'], 2); ?></p>
                        </div>
                        <div class="col-md-6">
                            <h5>Stock Disponible</h5>
                            <p class="h4">
                                <span class="badge bg-success"><?php echo $celular['stock']; ?> unidades</span>
                            </p>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h5>Descripción</h5>
                        <p class="card-text">
                            <?php echo $celular['descripcion'] ? htmlspecialchars($celular['descripcion']) : '<span class="text-muted">Sin descripción disponible</span>'; ?>
                        </p>
                    </div>

                    <hr>

                    <!-- Formulario agregar al carrito -->
                    <form method="POST" class="mb-4">
                        <div class="mb-3">
                            <label for="cantidad" class="form-label">Cantidad</label>
                            <div class="input-group">
                                <button class="btn btn-outline-secondary" type="button" onclick="decrementarCantidad()">−</button>
                                <input type="number" class="form-control text-center" id="cantidad" name="cantidad"
                                       min="1" max="<?php echo $celular['stock']; ?>" value="1" required>
                                <button class="btn btn-outline-secondary" type="button" onclick="incrementarCantidad(<?php echo $celular['stock']; ?>)">+</button>
                            </div>
                            <small class="form-text text-muted">Máximo: <?php echo $celular['stock']; ?> unidades</small>
                        </div>

                        <button type="submit" class="btn btn-success btn-lg w-100">Agregar al Carrito</button>
                    </form>

                    <a href="catalogo.php" class="btn btn-outline-secondary w-100">Ver más productos</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function incrementarCantidad(max) {
    const input = document.getElementById('cantidad');
    const valor = parseInt(input.value) + 1;
    if (valor <= max) {
        input.value = valor;
    }
}

function decrementarCantidad() {
    const input = document.getElementById('cantidad');
    const valor = parseInt(input.value) - 1;
    if (valor >= 1) {
        input.value = valor;
    }
}
</script>

<?php include '../includes/footer.php'; ?>
