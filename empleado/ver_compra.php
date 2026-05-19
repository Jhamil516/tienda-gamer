<?php
require_once __DIR__ . '/header.php';

$id = intval($_GET['id'] ?? 0);
$compra = null;
$detalles = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nuevo_estado'], $_POST['id_venta'])) {
    $id_venta = intval($_POST['id_venta']);
    $nuevo_estado = $_POST['nuevo_estado'];
    $estados_validos = ['pendiente', 'confirmada', 'enviada', 'entregada', 'cancelada'];

    if (in_array($nuevo_estado, $estados_validos)) {
        $stmt = $conn->prepare("UPDATE ventas SET estado_venta = ? WHERE id_venta = ?");
        $stmt->bind_param('si', $nuevo_estado, $id_venta);
        $stmt->execute();
    }

    header('Location: ver_compra.php?id=' . $id_venta . '&updated=1');
    exit;
}

if ($id > 0) {
    $stmt = $conn->prepare("SELECT v.*, u.nombre FROM ventas v JOIN usuarios u ON v.id_usuario = u.id_usuario WHERE v.id_venta = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $compra = $result ? $result->fetch_assoc() : null;

    if ($compra) {
        $stmt_det = $conn->prepare("SELECT dv.*, p.nombre AS producto_nombre FROM detalle_venta dv JOIN productos p ON dv.id_producto = p.id_producto WHERE dv.id_venta = ?");
        $stmt_det->bind_param('i', $id);
        $stmt_det->execute();
        $detalles = $stmt_det->get_result();
    }
}

if (!$compra) {
    header('Location: ventas.php');
    exit;
}

empleado_render_header('Detalles de Compra', 'fas fa-shopping-bag');
?>
<style>
    .detalle-card {
        background: rgba(26, 26, 46, 0.95);
        border: 1px solid rgba(98, 0, 255, 0.35);
        border-radius: 16px;
        padding: 24px;
        margin-bottom: 24px;
    }
    .detalle-card h3 {
        color: var(--accent);
        margin-bottom: 18px;
    }
    .detalle-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }
    .detalle-table th,
    .detalle-table td {
        border: 1px solid rgba(98, 0, 255, 0.18);
        padding: 12px 14px;
        color: #e0e0e0;
    }
    .detalle-table thead th {
        background: rgba(98, 0, 255, 0.2);
        color: var(--accent);
        text-align: left;
        font-weight: 700;
    }
    .badge-status {
        padding: 6px 12px;
        border-radius: 999px;
        font-weight: 700;
    }
    .badge-pendiente { background: rgba(245, 158, 11, 0.18); color: #fbbf24; border: 1px solid #f59e0b; }
    .badge-confirmada { background: rgba(59, 130, 246, 0.18); color: #60a5fa; border: 1px solid #60a5fa; }
    .badge-enviada { background: rgba(147, 51, 234, 0.18); color: #c084fc; border: 1px solid #c084fc; }
    .badge-entregada { background: rgba(16, 185, 129, 0.18); color: #34d399; border: 1px solid #10b981; }
    .badge-cancelada { background: rgba(239, 68, 68, 0.18); color: #f87171; border: 1px solid #ef4444; }
    .btn-back { margin-bottom: 18px; }
</style>

<div class="detalle-card">
    <a href="ventas.php" class="btn btn-outline-light btn-back"><i class="fas fa-arrow-left"></i> Volver a Ventas</a>

    <?php if (isset($_GET['updated']) && $_GET['updated'] === '1'): ?>
        <div class="alert alert-success" style="background: rgba(16, 185, 129, 0.18); border-color: #10b981; color: #d1fae5;">
            <i class="fas fa-check-circle"></i> Estado actualizado correctamente.
        </div>
    <?php endif; ?>

    <h3>Orden: <?php echo htmlspecialchars($compra['numero_venta']); ?></h3>
    <p><strong>Cliente:</strong> <?php echo htmlspecialchars($compra['nombre']); ?></p>
    <p><strong>Correo:</strong> <?php echo htmlspecialchars($compra['correo'] ?? 'N/A'); ?></p>
    <p><strong>Fecha:</strong> <?php echo formatear_fecha($compra['fecha_venta']); ?></p>
    <p><strong>Total:</strong> BOB <?php echo number_format($compra['total'], 2); ?></p>
    <p><strong>Estado:</strong> <span class="badge-status badge-<?php echo htmlspecialchars($compra['estado_venta']); ?>"><?php echo ucfirst(htmlspecialchars($compra['estado_venta'])); ?></span></p>

    <div style="background: rgba(0, 212, 255, 0.08); padding: 18px; border-radius: 12px; margin-top: 20px;">
        <h5 style="color: var(--accent);">Actualizar estado del pedido</h5>
        <form method="POST" style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center; margin-top: 12px;">
            <input type="hidden" name="id_venta" value="<?php echo intval($compra['id_venta']); ?>">
            <select name="nuevo_estado" class="form-select" style="max-width: 240px; background: rgba(15, 15, 30, 0.9); color: #fff; border: 1px solid rgba(98, 0, 255, 0.4);">
                <option value="pendiente" <?php echo $compra['estado_venta'] === 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                <option value="confirmada" <?php echo $compra['estado_venta'] === 'confirmada' ? 'selected' : ''; ?>>Confirmada</option>
                <option value="enviada" <?php echo $compra['estado_venta'] === 'enviada' ? 'selected' : ''; ?>>Enviada</option>
                <option value="entregada" <?php echo $compra['estado_venta'] === 'entregada' ? 'selected' : ''; ?>>Entregada</option>
                <option value="cancelada" <?php echo $compra['estado_venta'] === 'cancelada' ? 'selected' : ''; ?>>Cancelada</option>
            </select>
            <button type="submit" class="btn btn-success">Actualizar Estado</button>
        </form>
    </div>
</div>

<div class="detalle-card">
    <h3>Productos en la orden</h3>
    <div style="overflow-x:auto;">
        <table class="detalle-table">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Cantidad</th>
                    <th>Precio Unitario</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($det = $detalles->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($det['producto_nombre']); ?></td>
                    <td><?php echo intval($det['cantidad']); ?></td>
                    <td>BOB <?php echo number_format($det['precio_unitario'], 2); ?></td>
                    <td>BOB <?php echo number_format($det['subtotal'], 2); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php empleado_render_footer(); ?>
