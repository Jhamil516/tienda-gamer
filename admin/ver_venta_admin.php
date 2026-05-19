<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../auth/proteger.php';
requerirAdmin();

$id_venta = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id_venta === 0) {
    header('Location: historial_ventas.php');
    exit;
}

// Obtener información de la venta
$stmt = $conn->prepare("
    SELECT v.*, u.nombre, u.correo, u.id_usuario
    FROM ventas v
    JOIN usuarios u ON v.id_usuario = u.id_usuario
    WHERE v.id_venta = ?
");
$stmt->bind_param("i", $id_venta);
$stmt->execute();
$resultado = $stmt->get_result();
$venta = $resultado->fetch_assoc();
$stmt->close();

if (!$venta) {
    header('Location: historial_ventas.php');
    exit;
}

// Obtener detalles de la venta
$stmt = $conn->prepare("
    SELECT dv.*, p.nombre AS producto_nombre, p.imagen_principal
    FROM detalle_venta dv
    JOIN productos p ON dv.id_producto = p.id_producto
    WHERE dv.id_venta = ?
");
$stmt->bind_param("i", $id_venta);
$stmt->execute();
$resultado_detalles = $stmt->get_result();
$stmt->close();

// Obtener otras ventas del mismo cliente
$stmt_otras = $conn->prepare("
    SELECT id_venta, fecha_venta, total
    FROM ventas
    WHERE id_usuario = ? AND id_venta != ?
    ORDER BY fecha_venta DESC
    LIMIT 5
");
$stmt_otras->bind_param("ii", $venta['id_usuario'], $id_venta);
$stmt_otras->execute();
$resultado_otras = $stmt_otras->get_result();
$stmt_otras->close();
?>

<?php include '../includes/header.php'; ?>

<div class="container">
    <div class="row mb-4 mt-4">
        <div class="col-md-12">
            <a href="historial_ventas.php" class="btn btn-secondary">Volver</a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <h3 class="card-title">Detalle de Venta #<?php echo $venta['id_venta']; ?></h3>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Cliente:</strong></p>
                            <p><?php echo htmlspecialchars($venta['nombre']); ?></p>
                            <p class="mb-1"><strong>Correo:</strong></p>
                            <p><?php echo htmlspecialchars($venta['correo']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Fecha:</strong></p>
                            <p><?php echo date('d/m/Y H:i:s', strtotime($venta['fecha_venta'])); ?></p>
                            <p class="mb-1"><strong>Total:</strong></p>
                            <p class="h5 text-success">BOB <?php echo number_format($venta['total'], 2); ?></p>
                        </div>
                    </div>

                    <hr>

                    <h5 class="mb-3">Productos Vendidos</h5>

                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead class="table-light">
                                <tr>
                                    <th>Imagen</th>
                                    <th>Producto</th>
                                    <th>Cantidad</th>
                                    <th>Precio Unitario</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($detalle = $resultado_detalles->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <?php if (!empty($detalle['imagen_principal'])): ?>
                                            <img src="/tienda-gamer/uploads/<?php echo htmlspecialchars($detalle['imagen_principal']); ?>"
                                                 alt="<?php echo htmlspecialchars($detalle['producto_nombre']); ?>"
                                                 style="height: 60px; width: 60px; object-fit: cover;">
                                        <?php else: ?>
                                            <span class="text-muted">Sin imagen</span>
                                        <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($detalle['producto_nombre']); ?></strong>
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
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0">Información del Cliente</h6>
                </div>
                <div class="card-body">
                    <p class="mb-2"><strong><?php echo htmlspecialchars($venta['nombre']); ?></strong></p>
                    <p class="text-muted"><?php echo htmlspecialchars($venta['correo']); ?></p>

                    <hr>

                    <h6>Historial de Compras</h6>
                    <div class="list-group list-group-sm">
                        <?php if ($resultado_otras->num_rows > 0): ?>
                            <?php while ($otra = $resultado_otras->fetch_assoc()): ?>
                                <a href="?id=<?php echo $otra['id_venta']; ?>"
                                   class="list-group-item list-group-item-action">
                                    <div class="d-flex justify-content-between">
                                        <small>#<?php echo $otra['id_venta']; ?></small>
                                        <small class="text-success">BOB <?php echo number_format($otra['total'], 2); ?></small>
                                    </div>
                                    <small class="text-muted"><?php echo date('d/m/Y', strtotime($otra['fecha_venta'])); ?></small>
                                </a>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="text-muted small">No hay otras compras</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
