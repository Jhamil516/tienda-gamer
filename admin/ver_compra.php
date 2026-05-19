<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/funciones.php';

if (!es_admin()) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

$id = intval($_GET['id'] ?? 0);
$compra = null;
$detalles = [];

require_once __DIR__ . '/admin_header.php';

// Procesar cambio de estado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nuevo_estado'])) {
    $nuevo_estado = $_POST['nuevo_estado'];
    $estados_validos = ['pendiente', 'confirmada', 'enviada', 'entregada', 'cancelada'];
    
    if (in_array($nuevo_estado, $estados_validos)) {
        $stmt = $conn->prepare("UPDATE ventas SET estado_venta = ? WHERE id_venta = ?");
        $stmt->bind_param("si", $nuevo_estado, $id);
        $stmt->execute();
        header('Location: ver_compra.php?id=' . $id . '&updated=1');
        exit;
    }
}

if ($id > 0) {
    $result = $conn->query("SELECT v.*, u.nombre FROM ventas v JOIN usuarios u ON v.id_usuario = u.id_usuario WHERE v.id_venta = $id");
    if ($result && $result->num_rows > 0) {
        $compra = $result->fetch_assoc();
        $det_result = $conn->query("SELECT dv.*, p.nombre as producto_nombre FROM detalle_venta dv JOIN productos p ON dv.id_producto = p.id_producto WHERE dv.id_venta = $id");
        if ($det_result) {
            $detalles = $det_result->fetch_all(MYSQLI_ASSOC);
        }
    }
}

if (!$compra) {
    header('Location: pedidos.php');
    exit;
}

admin_render_header('Detalles de Compra', 'Pedidos', 'fas fa-shopping-bag');
?>
<div class="section">
    <a href="pedidos.php" class="btn btn-outline-light mb-3">
        <i class="fas fa-arrow-left"></i> Volver
    </a>

    <?php if (isset($_GET['updated']) && $_GET['updated'] === '1'): ?>
        <div class="alert alert-success" role="alert" style="background: rgba(40, 167, 69, 0.12); border-color: #28a745; color: #e8f6e8;">
            <i class="fas fa-check-circle"></i> Estado actualizado correctamente.
        </div>
    <?php endif; ?>

    <div class="card-custom mb-4">
        <div class="section-title">
            <i class="fas fa-receipt"></i> Orden: <?php echo htmlspecialchars($compra['numero_venta']); ?>
        </div>
        <p><strong>Cliente:</strong> <?php echo htmlspecialchars($compra['nombre']); ?></p>
        <p><strong>Total:</strong> BOB <?php echo number_format($compra['total'], 2); ?></p>
        <p><strong>Estado Actual:</strong> <span class="badge bg-info"><?php echo htmlspecialchars($compra['estado_venta']); ?></span></p>
        <p><strong>Fecha:</strong> <?php echo formatear_fecha($compra['fecha_venta']); ?></p>
        
        <hr>
        
        <div style="background: rgba(0, 212, 255, 0.1); padding: 15px; border-radius: 8px; border-left: 3px solid #00d4ff;">
            <h6 style="color: #00d4ff; margin-bottom: 15px;"><i class="fas fa-sync-alt"></i> Cambiar Estado del Pedido</h6>
            <form method="POST" style="display: flex; gap: 10px; align-items: center;">
                <select name="nuevo_estado" class="form-select" style="max-width: 200px; background: rgba(71, 85, 105, 0.5); border: 1px solid rgba(0, 212, 255, 0.3); color: #e0e0e0;">
                    <option value="pendiente" <?php echo ($compra['estado_venta'] === 'pendiente') ? 'selected' : ''; ?>>Pendiente</option>
                    <option value="confirmada" <?php echo ($compra['estado_venta'] === 'confirmada') ? 'selected' : ''; ?>>Confirmada</option>
                    <option value="enviada" <?php echo ($compra['estado_venta'] === 'enviada') ? 'selected' : ''; ?>>Enviada</option>
                    <option value="entregada" <?php echo ($compra['estado_venta'] === 'entregada') ? 'selected' : ''; ?>>Entregada</option>
                    <option value="cancelada" <?php echo ($compra['estado_venta'] === 'cancelada') ? 'selected' : ''; ?>>Cancelada</option>
                </select>
                <button type="submit" class="btn btn-success" style="background: #28a745;">
                    <i class="fas fa-check"></i> Actualizar Estado
                </button>
            </form>
        </div>
    </div>

    <div class="card-custom">
        <div class="section-title">
            <i class="fas fa-box-open"></i> Productos
        </div>
        <div style="overflow-x: auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Cantidad</th>
                        <th>Precio</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($detalles as $det): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($det['producto_nombre']); ?></td>
                        <td><?php echo intval($det['cantidad']); ?></td>
                        <td>BOB <?php echo number_format($det['precio_unitario'], 2); ?></td>
                        <td>BOB <?php echo number_format($det['subtotal'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php admin_render_footer(); ?>
