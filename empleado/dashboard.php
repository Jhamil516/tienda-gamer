<?php
require_once __DIR__ . '/header.php';

empleado_render_header('Dashboard', 'fas fa-chart-line');

$stats = [
    'pendientes' => 0,
    'por_entregar' => 0,
    'stock_bajo' => 0,
    'ventas_hoy' => 0,
    'productos_activos' => 0,
];

// Pedidos pendientes
$result = $conn->query("SELECT COUNT(*) AS total FROM ventas WHERE estado_venta = 'pendiente'");
if ($result) {
    $stats['pendientes'] = intval($result->fetch_assoc()['total']);
}

// Pedidos por entregar
$result = $conn->query("SELECT COUNT(*) AS total FROM ventas WHERE estado_venta IN ('confirmada', 'enviada')");
if ($result) {
    $stats['por_entregar'] = intval($result->fetch_assoc()['total']);
}

// Productos activos
$result = $conn->query("SELECT COUNT(*) AS total FROM productos WHERE estado = 'activo'");
if ($result) {
    $stats['productos_activos'] = intval($result->fetch_assoc()['total']);
}

// Productos con stock bajo
$result = $conn->query("SELECT COUNT(*) AS total FROM productos WHERE stock <= stock_minimo AND estado = 'activo'");
if ($result) {
    $stats['stock_bajo'] = intval($result->fetch_assoc()['total']);
}

// Ventas del día
$today = date('Y-m-d');
$result = $conn->query("SELECT SUM(total) AS total FROM ventas WHERE DATE(fecha_venta) = '$today'");
if ($result) {
    $stats['ventas_hoy'] = floatval($result->fetch_assoc()['total'] ?? 0);
}

$ventas_recientes = obtener_ventas_recientes($conn, 5);
$bajos_stock = obtener_productos_stock_bajo($conn, 5);

$productos_mas_vendidos = [];
$result = $conn->query("SELECT p.id_producto, p.nombre, p.marca, SUM(dv.cantidad) AS vendido FROM detalle_venta dv JOIN productos p ON dv.id_producto = p.id_producto GROUP BY p.id_producto ORDER BY vendido DESC LIMIT 3");
if ($result) {
    $productos_mas_vendidos = $result->fetch_all(MYSQLI_ASSOC);
}

$productos_menos_vendidos = [];
$result = $conn->query("SELECT p.id_producto, p.nombre, p.marca, COALESCE(SUM(dv.cantidad), 0) AS vendido FROM productos p LEFT JOIN detalle_venta dv ON p.id_producto = dv.id_producto GROUP BY p.id_producto ORDER BY vendido ASC, p.nombre ASC LIMIT 3");
if ($result) {
    $productos_menos_vendidos = $result->fetch_all(MYSQLI_ASSOC);
}
?>
<style>
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: rgba(26, 26, 46, 0.8);
        border: 2px solid var(--primary);
        border-radius: 10px;
        padding: 25px;
        text-align: center;
        transition: all 0.3s;
    }

    .stat-card:hover {
        border-color: var(--accent);
        box-shadow: 0 5px 15px rgba(0, 212, 255, 0.2);
    }

    .stat-icon {
        font-size: 2.5rem;
        color: var(--accent);
        margin-bottom: 10px;
    }

    .stat-number {
        font-size: 2rem;
        color: var(--accent);
        font-weight: bold;
        margin: 10px 0;
    }

    .stat-label {
        color: #aaa;
        font-size: 0.9rem;
    }

    .section {
        background: rgba(26, 26, 46, 0.8);
        border: 2px solid var(--primary);
        border-radius: 10px;
        padding: 25px;
        margin-bottom: 30px;
    }

    .section-title {
        color: var(--accent);
        font-size: 1.3rem;
        font-weight: bold;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .section-title i {
        font-size: 1.5rem;
    }

    .table {
        background: transparent;
        border-collapse: collapse;
        width: 100%;
    }

    .table thead {
        background: rgba(98, 0, 255, 0.2);
        border-bottom: 2px solid var(--primary);
    }

    .table th,
    .table td {
        color: #111111;
        padding: 12px;
        text-align: left;
    }

    .table tbody tr:hover {
        background: rgba(0, 212, 255, 0.12);
    }

    .badge-status {
        padding: 5px 10px;
        border-radius: 999px;
        font-size: 0.82rem;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .badge-info { background: rgba(59, 130, 246, 0.18); color: #93c5fd; }
    .badge-warning { background: rgba(234, 179, 8, 0.18); color: #facc15; }
    .badge-danger { background: rgba(248, 113, 113, 0.18); color: #fecaca; }

    .button-group {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .btn-action {
        border-radius: 10px;
        padding: 12px 18px;
        font-size: 0.95rem;
        font-weight: 600;
    }

    .btn-primary, .btn-info, .btn-warning, .btn-success {
        border: none;
    }

    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-hourglass-half"></i></div>
        <div class="stat-number"><?php echo $stats['pendientes']; ?></div>
        <div class="stat-label">Pedidos Pendientes</div>
    </div>

    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-truck"></i></div>
        <div class="stat-number"><?php echo $stats['por_entregar']; ?></div>
        <div class="stat-label">Por Entregar</div>
    </div>

    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-box"></i></div>
        <div class="stat-number"><?php echo $stats['productos_activos']; ?></div>
        <div class="stat-label">Productos Activos</div>
    </div>

    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
        <div class="stat-number"><?php echo $stats['stock_bajo']; ?></div>
        <div class="stat-label">Stock Bajo</div>
    </div>

    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
        <div class="stat-number">BOB <?php echo number_format($stats['ventas_hoy'], 0, ',', '.'); ?></div>
        <div class="stat-label">Ventas Hoy</div>
    </div>
</div>

<div class="section">
    <div class="section-title"><i class="fas fa-history"></i> Ventas Recientes</div>
    <?php if (!empty($ventas_recientes)): ?>
        <div style="display:grid; gap:15px;">
            <?php foreach ($ventas_recientes as $venta): ?>
                <div style="background: rgba(30, 30, 50, 0.7); border: 1px solid var(--primary); border-radius: 10px; padding: 18px; display:grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 15px; align-items:center;">
                    <div>
                        <div style="color:#888; font-size:0.8rem; text-transform:uppercase; margin-bottom:6px;">Orden</div>
                        <div style="font-weight:700; color:#fff;"><code><?php echo htmlspecialchars($venta['numero_venta']); ?></code></div>
                    </div>
                    <div>
                        <div style="color:#888; font-size:0.8rem; text-transform:uppercase; margin-bottom:6px;">Cliente</div>
                        <div style="font-weight:700; color:#fff;"><?php echo htmlspecialchars($venta['nombre']); ?></div>
                    </div>
                    <div>
                        <div style="color:#888; font-size:0.8rem; text-transform:uppercase; margin-bottom:6px;">Total</div>
                        <div style="font-weight:700; color:#fff;">BOB <?php echo number_format($venta['total'], 2, ',', '.'); ?></div>
                    </div>
                    <div>
                        <div style="color:#888; font-size:0.8rem; text-transform:uppercase; margin-bottom:6px;">Estado</div>
                        <span class="badge-status badge-info"><?php echo htmlspecialchars($venta['estado_venta']); ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="alert"><i class="fas fa-info-circle"></i> No hay ventas registradas aún.</div>
    <?php endif; ?>
</div>

<div class="section">
    <div class="section-title"><i class="fas fa-chart-line"></i> Productos Más / Menos Vendidos</div>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
        <div style="background: rgba(26, 26, 46, 0.8); border: 2px solid var(--primary); border-radius: 10px; padding: 20px;">
            <h4 style="color: var(--accent); margin-bottom: 15px;">Más Vendidos</h4>
            <div style="overflow-x:auto;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Marca</th>
                            <th>Cant.</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($productos_mas_vendidos)): ?>
                            <?php foreach ($productos_mas_vendidos as $prod): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($prod['nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($prod['marca']); ?></td>
                                    <td><?php echo intval($prod['vendido']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="3" style="text-align:center;">No hay ventas registradas.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div style="background: rgba(26, 26, 46, 0.8); border: 2px solid var(--primary); border-radius: 10px; padding: 20px;">
            <h4 style="color: var(--accent); margin-bottom: 15px;">Menos Vendidos</h4>
            <div style="overflow-x:auto;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Marca</th>
                            <th>Cant.</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($productos_menos_vendidos)): ?>
                            <?php foreach ($productos_menos_vendidos as $prod): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($prod['nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($prod['marca']); ?></td>
                                    <td><?php echo intval($prod['vendido']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="3" style="text-align:center;">No hay productos con ventas registradas.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="section">
    <div class="section-title"><i class="fas fa-exclamation-triangle"></i> Productos con Bajo Stock</div>
    <?php if (!empty($bajos_stock)): ?>
        <div style="display:grid; gap:15px;">
            <?php foreach ($bajos_stock as $producto): ?>
                <div style="background: rgba(30, 30, 50, 0.7); border: 1px solid #dc3545; border-radius: 10px; padding: 18px; display:grid; grid-template-columns: 1fr auto; gap: 15px; align-items:center;">
                    <div>
                        <div style="color:#888; font-size:0.8rem; text-transform:uppercase; margin-bottom:6px;">Producto</div>
                        <div style="font-weight:700; color:#fff;"><?php echo htmlspecialchars($producto['nombre']); ?></div>
                    </div>
                    <div style="text-align:right;">
                        <div style="color:#888; font-size:0.8rem; text-transform:uppercase; margin-bottom:6px;">Stock</div>
                        <span class="badge-status badge-danger"><?php echo intval($producto['stock']); ?> unidades</span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="alert"><i class="fas fa-check-circle"></i> Todos los productos tienen stock suficiente.</div>
    <?php endif; ?>
</div>

<div class="section">
    <div class="section-title"><i class="fas fa-bolt"></i> Acciones Rápidas</div>
    <div class="button-group">
        <a href="pedidos.php" class="btn btn-primary btn-action"><i class="fas fa-shopping-bag"></i> Ver Pedidos</a>
        <a href="stock.php" class="btn btn-info btn-action"><i class="fas fa-warehouse"></i> Gestionar Stock</a>
        <a href="ventas.php" class="btn btn-success btn-action"><i class="fas fa-chart-bar"></i> Ver Ventas</a>
    </div>
</div>

<?php empleado_render_footer(); ?>
