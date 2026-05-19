<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../auth/proteger.php';
requerirCliente();

$id_usuario = $_SESSION['id_usuario'];
$favoritos = obtener_favoritos($conn, $id_usuario);
?>
<?php include '../includes/header.php'; ?>

<div class="row mb-4 mt-4">
    <div class="col-md-8">
        <h2>Favoritos</h2>
        <p class="text-muted">Tus productos guardados para comprar más tarde.</p>
    </div>
    <div class="col-md-4 text-end">
        <a href="catalogo.php" class="btn btn-primary">Volver al Catálogo</a>
    </div>
</div>

<div class="row mb-4">
    <?php if (!empty($favoritos)): ?>
        <?php foreach ($favoritos as $row): ?>
            <?php $imagen = $row['imagen_principal'] ?: ''; ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card product-card h-100 position-relative">
                    <?php if ($imagen): ?>
                        <img src="<?php echo BASE_URL . 'uploads/' . htmlspecialchars($imagen); ?>" class="product-image" alt="<?php echo htmlspecialchars($row['nombre']); ?>">
                    <?php else: ?>
                        <div class="product-image d-flex align-items-center justify-content-center">
                            <i class="fas fa-image fa-2x" style="color: #7b7b7b;"></i>
                        </div>
                    <?php endif; ?>
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title" style="color: #00d4ff; font-weight: bold;"><?php echo htmlspecialchars($row['marca']); ?></h5>
                        <p class="card-text mb-2" style="color: #e0e0e0;"><?php echo htmlspecialchars($row['nombre']); ?></p>
                        <p class="product-price mb-2">BOB <?php echo number_format($row['precio_actual'], 2); ?></p>
                        <p class="text-success mb-3"><i class="fas fa-box-seam"></i> Stock: <?php echo $row['stock']; ?></p>
                        <div class="mt-auto d-grid">
                            <a href="producto.php?id=<?php echo $row['id_producto']; ?>" class="btn btn-info btn-sm"><i class="fas fa-eye"></i> Ver detalle</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-12">
            <div class="alert alert-info text-center" role="alert">
                <h5>No tienes favoritos aún</h5>
                <p>Guarda productos para revisarlos más tarde.</p>
                <a href="catalogo.php" class="btn btn-primary">Ver Catálogo</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
