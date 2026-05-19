<?php
session_start();
$_SESSION['BASE_URL'] = '/tienda-gamer';

require '../config/db.php';
require '../auth/proteger.php';
require '../includes/funciones.php';
requerirCliente();

$id_usuario = $_SESSION['id_usuario'];
$id_venta = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_venta === 0) {
    header('Location: historial.php');
    exit;
}

// Verificar que la venta pertenece al usuario
$stmt = $conn->prepare("SELECT id_venta, fecha_venta, total FROM ventas WHERE id_venta = ? AND id_usuario = ?");
$stmt->bind_param("ii", $id_venta, $id_usuario);
$stmt->execute();
$resultado = $stmt->get_result();
$venta = $resultado->fetch_assoc();

if (!$venta) {
    header('Location: historial.php');
    exit;
}

// Obtener detalles de la venta
$stmt = $conn->prepare("
    SELECT dv.id_detalle, dv.cantidad, dv.precio_unitario, dv.subtotal,
           p.marca, p.nombre, p.imagen_principal
    FROM detalle_venta dv
    JOIN productos p ON dv.id_producto = p.id_producto
    WHERE dv.id_venta = ?
    ORDER BY dv.id_detalle ASC
");
$stmt->bind_param("i", $id_venta);
$stmt->execute();
$resultado_detalles = $stmt->get_result();
?>

<?php include '../includes/header.php'; ?>

<div class="container">
    <div class="row mb-4 mt-4">
        <div class="col-md-12">
            <a href="historial.php" class="btn btn-secondary">Volver</a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <h3 class="card-title">Detalle de Compra #<?php echo $venta['id_venta']; ?></h3>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Fecha:</strong></p>
                            <p><?php echo date('d/m/Y H:i:s', strtotime($venta['fecha_venta'])); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Total:</strong></p>
                            <p class="h5 text-success">BOB <?php echo number_format($venta['total'], 2); ?></p>
                        </div>
                    </div>

                    <hr>

                    <h5 class="mb-3">Productos y Recargas Comprados</h5>

                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead class="table-light">
                                <tr>
                                    <th>Imagen</th>
                                    <th>Producto/Recarga</th>
                                    <th>Cantidad</th>
                                    <th>Precio Unitario</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($detalle = $resultado_detalles->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <img src="/tienda-gamer/uploads/<?php echo htmlspecialchars($detalle['imagen_principal']); ?>"
                                                 alt="<?php echo htmlspecialchars($detalle['nombre']); ?>"
                                                 style="height: 60px; width: 60px; object-fit: cover;">
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($detalle['marca']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($detalle['nombre']); ?></small>
                                        </td>
                                        <td><?php echo $detalle['cantidad']; ?></td>
                                        <td>BOB <?php echo number_format($detalle['precio_unitario'], 2); ?></td>
                                        <td class="fw-bold">BOB <?php echo number_format($detalle['subtotal'], 2); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-md-8 text-end">
                            <h5>Total a Pagar:</h5>
                        </div>
                        <div class="col-md-4">
                            <h5 class="text-success">BOB <?php echo number_format($venta['total'], 2); ?></h5>
                        </div>
                    </div>

                    <hr>

                    <div class="d-flex gap-2">
                        <a href="historial.php" class="btn btn-secondary">Volver al Historial</a>
                        <a href="catalogo.php" class="btn btn-primary">Continuar Comprando</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
