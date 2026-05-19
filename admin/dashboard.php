<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/funciones.php';

if (!es_admin()) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

$stats = obtener_estadisticas_admin($conn);

$ventas_recientes = [];
$result = $conn->query("SELECT v.*, u.nombre FROM ventas v
                       JOIN usuarios u ON v.id_usuario = u.id_usuario
                       ORDER BY v.fecha_venta DESC LIMIT 5");
if ($result) {
    $ventas_recientes = $result->fetch_all(MYSQLI_ASSOC);
}

$bajos_stock = [];
$result = $conn->query("SELECT id_producto, nombre, stock FROM productos WHERE stock <= stock_minimo LIMIT 5");
if ($result) {
    $bajos_stock = $result->fetch_all(MYSQLI_ASSOC);
}

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

require_once __DIR__ . '/admin_header.php';
admin_render_header('Panel Administrativo', 'Dashboard', 'fas fa-chart-line');
?>
    <style>
        :root {
            --primary: #6200ff;
            --accent: #00d4ff;
            --dark-bg: #0a0a0a;
            --card-bg: #1a1a2e;
            --text-light: #e0e0e0;
        }

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

        .table th {
            color: var(--accent);
            padding: 12px;
            text-align: left;
            font-weight: bold;
        }

        .ventas-container {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .venta-card {
            background: rgba(30, 30, 50, 0.6);
            border: 1px solid var(--primary);
            border-radius: 8px;
            padding: 15px;
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr;
            gap: 15px;
            align-items: center;
        }

        .venta-card:hover {
            background: rgba(30, 30, 50, 0.9);
            border-color: var(--accent);
        }

        .venta-item {
            display: flex;
            flex-direction: column;
        }

        .venta-label {
            font-size: 0.75rem;
            color: #888;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .venta-value {
            color: #ffffff;
            font-weight: bold;
            font-size: 0.95rem;
        }

        .venta-code {
            background: rgba(0, 212, 255, 0.1);
            color: #00d4ff;
            padding: 3px 6px;
            border-radius: 3px;
            font-family: monospace;
        }

        .stock-container {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .stock-card {
            background: rgba(30, 30, 50, 0.6);
            border: 1px solid #dc3545;
            border-radius: 8px;
            padding: 15px;
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 15px;
            align-items: center;
        }

        .stock-card:hover {
            background: rgba(30, 30, 50, 0.9);
        }

        .stock-item {
            display: flex;
            flex-direction: column;
        }

        .stock-label {
            font-size: 0.75rem;
            color: #888;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .stock-value {
            color: #ffffff;
            font-weight: bold;
        }

        .table td {
            padding: 12px;
            border-bottom: 1px solid #333;
            color: #ffffff !important;
            font-weight: 500;
        }

        .table tr:hover {
            background: rgba(0, 212, 255, 0.15);
        }

        .table code {
            background: rgba(0, 212, 255, 0.1);
            color: #00d4ff;
            padding: 3px 6px;
            border-radius: 3px;
            font-weight: bold;
        }

        .low-stock-table tbody td {
            color: #000000 !important;
            background: #ffffff !important;
        }

        .low-stock-table tbody tr:hover {
            background: #e2e8f0 !important;
        }

        .alert {
            background: rgba(26, 26, 46, 0.8);
            border: 1px solid var(--primary);
            color: var(--text-light);
            border-radius: 8px;
        }

        .alert.alert-info,
        .alert.alert-success,
        .alert.alert-danger {
            color: var(--text-light);
        }

        .alert i {
            color: var(--accent);
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
    <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <div class="stat-number"><?php echo $stats['total_usuarios']; ?></div>
                <div class="stat-label">Clientes</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-shopping-bag"></i></div>
                <div class="stat-number"><?php echo $stats['total_ventas']; ?></div>
                <div class="stat-label">Ventas</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
                <div class="stat-number">BOB <?php echo number_format($stats['ganancias_totales'], 0); ?></div>
                <div class="stat-label">Ganancias</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-box"></i></div>
                <div class="stat-number"><?php echo $stats['total_productos']; ?></div>
                <div class="stat-label">Productos</div>
            </div>
        </div>

            <!-- Recent Sales -->
            <div class="section">
                <div class="section-title">
                    <i class="fas fa-history"></i> Ventas Recientes
                </div>

                <?php if (!empty($ventas_recientes)): ?>
                <div class="ventas-container">
                    <?php foreach ($ventas_recientes as $venta): ?>
                    <div class="venta-card">
                        <div class="venta-item">
                            <div class="venta-label">Orden</div>
                            <div class="venta-value venta-code"><?php echo htmlspecialchars($venta['numero_venta']); ?></div>
                        </div>
                        <div class="venta-item">
                            <div class="venta-label">Cliente</div>
                            <div class="venta-value"><?php echo htmlspecialchars($venta['nombre']); ?></div>
                        </div>
                        <div class="venta-item">
                            <div class="venta-label">Total</div>
                            <div class="venta-value">BOB <?php echo number_format($venta['total'], 2, ',', '.'); ?></div>
                        </div>
                        <div class="venta-item">
                            <div class="venta-label">Estado</div>
                            <span class="badge bg-info"><?php echo htmlspecialchars($venta['estado_venta']); ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="alert">
                    <i class="fas fa-info-circle"></i> No hay ventas registradas aún.
                </div>
                <?php endif; ?>
            </div>

            <!-- Top / Least Sold Products -->
            <div class="section">
                <div class="section-title">
                    <i class="fas fa-chart-line"></i> Productos Más / Menos Vendidos
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                    <div style="background: rgba(26, 26, 46, 0.8); border: 2px solid var(--primary); border-radius: 10px; padding: 20px;">
                        <h4 style="color: var(--accent); margin-bottom: 15px;">Más Vendidos</h4>
                        <div style="overflow-x:auto;">
                            <table class="table low-stock-table">
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
                            <table class="table low-stock-table">
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

            <!-- Low Stock -->
            <div class="section">
                <div class="section-title">
                    <i class="fas fa-exclamation-triangle"></i> Productos con Bajo Stock
                </div>

                <?php if (!empty($bajos_stock)): ?>
                <div class="stock-container">
                    <?php foreach ($bajos_stock as $producto): ?>
                    <div class="stock-card">
                        <div class="stock-item">
                            <div class="stock-label">Producto</div>
                            <div class="stock-value"><?php echo htmlspecialchars($producto['nombre']); ?></div>
                        </div>
                        <div class="stock-item">
                            <div class="stock-label">Stock</div>
                            <span class="badge bg-danger"><?php echo $producto['stock']; ?> unidades</span>
                        </div>
                        <div class="stock-item">
                            <a href="editar_producto.php?id=<?php echo $producto['id_producto']; ?>" class="btn btn-sm btn-warning">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="alert">
                    <i class="fas fa-check-circle"></i> Todos los productos tienen stock suficiente.
                </div>
                <?php endif; ?>
            </div>
<?php admin_render_footer(); ?>
