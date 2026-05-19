<?php
require_once __DIR__ . '/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_stock'])) {
    $id_producto = intval($_POST['id_producto']);
    $nueva_cantidad = intval($_POST['nueva_cantidad']);

    if ($nueva_cantidad >= 0) {
        $conn->query("UPDATE productos SET stock = $nueva_cantidad WHERE id_producto = $id_producto");
        $_SESSION['mensaje'] = 'Stock actualizado correctamente';
        header('Location: stock.php');
        exit;
    }
}

empleado_render_header('Gestión de Stock', 'fas fa-warehouse');

$productos = [];
$result = $conn->query("SELECT * FROM productos WHERE estado = 'activo' ORDER BY nombre");
if ($result) {
    $productos = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<?php if (isset($_SESSION['mensaje'])): ?>
<div class="alert alert-success" style="margin-bottom: 20px;">
    <i class="fas fa-check-circle"></i> <?php echo $_SESSION['mensaje']; unset($_SESSION['mensaje']); ?>
</div>
<?php endif; ?>

<div class="section">
    <h3 style="color: var(--accent); margin-bottom: 20px;"><i class="fas fa-boxes"></i> Productos con Stock Bajo</h3>
    
    <div style="overflow-x: auto;">
        <table class="table">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Stock Actual</th>
                    <th>Stock Mínimo</th>
                    <th>Nuevo Stock</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($productos as $p): 
                    if ($p['stock'] <= $p['stock_minimo']):
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($p['nombre']); ?></td>
                    <td>
                        <span class="badge bg-danger"><?php echo $p['stock']; ?> u.</span>
                    </td>
                    <td><?php echo $p['stock_minimo']; ?></td>
                    <td>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="id_producto" value="<?php echo $p['id_producto']; ?>">
                            <input type="number" name="nueva_cantidad" value="<?php echo $p['stock']; ?>" class="form-control form-control-sm" style="width: 100px; display: inline; background: white; color: #0f172a;">
                            <button type="submit" name="actualizar_stock" class="btn btn-sm btn-success">Actualizar</button>
                        </form>
                    </td>
                    <td>
                        <a href="#" class="btn btn-sm btn-info"><i class="fas fa-history"></i> Historial</a>
                    </td>
                </tr>
                <?php 
                    endif;
                endforeach; 
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php empleado_render_footer(); ?>
