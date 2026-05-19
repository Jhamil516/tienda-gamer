<?php
session_start();
$_SESSION['BASE_URL'] = '/tienda-gamer';

require '../config/db.php';
require '../auth/proteger.php';
requerirAdmin();

$id_celular = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$mensaje_stock = '';
$error_stock = '';

// Procesar aumento de stock
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['aumentar_stock'])) {
    $cantidad_aumento = isset($_POST['cantidad_aumento']) ? max(1, (int)$_POST['cantidad_aumento']) : 0;
    
    if ($cantidad_aumento < 1) {
        $error_stock = 'Debes ingresar una cantidad mayor a 0';
    } else {
        $stmt_update = $conn->prepare("UPDATE celulares SET stock = stock + ? WHERE id_celular = ?");
        $stmt_update->bind_param("ii", $cantidad_aumento, $id_celular);
        
        if ($stmt_update->execute()) {
            $mensaje_stock = "✅ Stock aumentado en " . $cantidad_aumento . " unidades exitosamente";
            // Recargar datos del celular
            $stmt = $conn->prepare("SELECT * FROM celulares WHERE id_celular = ?");
            $stmt->bind_param("i", $id_celular);
            $stmt->execute();
            $resultado = $stmt->get_result();
            $celular = $resultado->fetch_assoc();
        } else {
            $error_stock = 'Error al actualizar el stock';
        }
    }
}

if ($id_celular === 0) {
    header('Location: dashboard.php');
    exit;
}

$stmt = $conn->prepare("SELECT * FROM celulares WHERE id_celular = ?");
$stmt->bind_param("i", $id_celular);
$stmt->execute();
$resultado = $stmt->get_result();
$celular = $resultado->fetch_assoc();

if (!$celular) {
    header('Location: dashboard.php');
    exit;
}

// Obtener todas las imágenes del producto
$stmt_img = $conn->prepare("SELECT id_imagen, imagen FROM celular_imagenes WHERE id_celular = ? ORDER BY principal DESC, id_imagen ASC");
$stmt_img->bind_param("i", $id_celular);
$stmt_img->execute();
$resultado_img = $stmt_img->get_result();
$imagenes = $resultado_img->fetch_all(MYSQLI_ASSOC);
?>

<?php include '../includes/header.php'; ?>

<div class="container">
    <div class="row mb-4 mt-4">
        <div class="col-md-12">
            <a href="dashboard.php" class="btn btn-secondary">← Volver</a>
        </div>
    </div>

    <?php if ($mensaje_stock): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle"></i>
            <?php echo $mensaje_stock; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error_stock): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle"></i>
            <?php echo $error_stock; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-5">
            <div class="card">
                <!-- Galería de imágenes -->
                <?php if (count($imagenes) > 0): ?>
                    <div id="galeriaCarousel" class="carousel slide" data-bs-ride="carousel">
                        <div class="carousel-inner">
                            <?php foreach ($imagenes as $idx => $img): ?>
                                <div class="carousel-item <?php echo $idx == 0 ? 'active' : ''; ?>">
                                    <img src="/tienda-gamer/uploads/<?php echo htmlspecialchars($img['imagen']); ?>"
                                         class="d-block w-100" alt="Imagen <?php echo $idx + 1; ?>"
                                         style="height: 400px; object-fit: cover;">
                                </div>
                            <?php endforeach; ?>
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
                    <div class="card-footer">
                        <small class="text-muted"><?php echo count($imagenes); ?> imagen(es)</small>
                    </div>
                <?php else: ?>
                    <div class="card-img-top" style="height: 400px; background-color: #e9ecef; display: flex; align-items: center; justify-content: center;">
                        <span class="text-muted">Sin imágenes</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-md-7">
            <div class="card">
                <div class="card-body">
                    <h2 class="card-title"><?php echo htmlspecialchars($celular['marca']); ?> <?php echo htmlspecialchars($celular['modelo']); ?></h2>

                    <p class="card-text text-muted">ID: #<?php echo $celular['id_celular']; ?></p>

                    <hr>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5>Precio</h5>
                            <p class="h4 text-success">BOB <?php echo number_format($celular['precio'], 2); ?></p>
                        </div>
                        <div class="col-md-6">
                            <h5>Stock</h5>
                            <p class="h4">
                                <span class="badge <?php echo $celular['stock'] > 0 ? 'bg-success' : 'bg-danger'; ?>">
                                    <?php echo $celular['stock']; ?> unidades
                                </span>
                            </p>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h5>Descripción</h5>
                        <p class="card-text">
                            <?php echo $celular['descripcion'] ? htmlspecialchars($celular['descripcion']) : '<span class="text-muted">Sin descripción</span>'; ?>
                        </p>
                    </div>

                    <div class="mb-4">
                        <h5>Fecha de Creación</h5>
                        <p class="card-text text-muted">
                            <?php echo date('d/m/Y H:i', strtotime($celular['fecha_creacion'])); ?>
                        </p>
                    </div>

                    <hr>

                    <div class="mb-4 p-3 bg-light rounded">
                        <h5><i class="bi bi-box2-heart"></i> Aumentar Stock</h5>
                        <form method="POST" class="row g-3 align-items-end">
                            <div class="col-md-6">
                                <label for="cantidad_aumento" class="form-label">Cantidad a Agregar</label>
                                <input type="number" class="form-control" id="cantidad_aumento" name="cantidad_aumento"
                                       min="1" max="1000" value="1" required>
                            </div>
                            <div class="col-md-6">
                                <button type="submit" name="aumentar_stock" class="btn btn-info w-100">
                                    <i class="bi bi-plus-circle"></i> Aumentar Stock
                                </button>
                            </div>
                        </form>
                    </div>

                    <hr>

                    <div class="d-flex gap-2">
                        <a href="editar_celular.php?id=<?php echo $celular['id_celular']; ?>"
                           class="btn btn-warning">
                            <i class="bi bi-pencil"></i> Editar
                        </a>
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                            <i class="bi bi-trash"></i> Eliminar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmación de eliminación -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="bi bi-exclamation-triangle"></i> Eliminar Producto
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de que quieres eliminar este producto?</p>
                <p class="text-muted small">Se eliminarán el producto y todas sus imágenes. Esta acción no se puede deshacer.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x"></i> Cancelar
                </button>
                <a href="dashboard.php?delete=<?php echo $celular['id_celular']; ?>"
                   class="btn btn-danger">
                    <i class="bi bi-trash"></i> Eliminar
                </a>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
