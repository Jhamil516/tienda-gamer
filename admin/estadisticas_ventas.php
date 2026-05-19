<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/funciones.php';

if (!es_admin()) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

$periodo = sanitizar_sql($conn, $_GET['periodo'] ?? 'mes');

$stats = [
    'total_ventas' => 0,
    'total_monto' => 0,
    'promedio_venta' => 0,
    'producto_top' => [],
    'estado_ventas' => []
];

// Total de ventas
if ($periodo === 'hoy') {
    $result = $conn->query("SELECT COUNT(*) as total, SUM(total) as monto FROM ventas WHERE DATE(fecha_venta) = CURDATE()");
} elseif ($periodo === 'semana') {
    $result = $conn->query("SELECT COUNT(*) as total, SUM(total) as monto FROM ventas WHERE WEEK(fecha_venta) = WEEK(CURDATE())");
} else {
    $result = $conn->query("SELECT COUNT(*) as total, SUM(total) as monto FROM ventas WHERE MONTH(fecha_venta) = MONTH(CURDATE())");
}

if ($result) {
    $row = $result->fetch_assoc();
    $stats['total_ventas'] = $row['total'];
    $stats['total_monto'] = $row['monto'] ?? 0;
    $stats['promedio_venta'] = $stats['total_ventas'] > 0 ? $stats['total_monto'] / $stats['total_ventas'] : 0;
}

// Producto más vendido
$result = $conn->query("SELECT p.nombre, SUM(dv.cantidad) as vendido FROM detalle_venta dv 
                        JOIN productos p ON dv.id_producto = p.id_producto 
                        GROUP BY p.id_producto ORDER BY vendido DESC LIMIT 5");
if ($result) {
    $stats['producto_top'] = $result->fetch_all(MYSQLI_ASSOC);
}

// Estados de ventas
$result = $conn->query("SELECT estado_venta, COUNT(*) as total FROM ventas GROUP BY estado_venta");
if ($result) {
    $stats['estado_ventas'] = $result->fetch_all(MYSQLI_ASSOC);
}

require_once __DIR__ . '/admin_header.php';
admin_render_header('Estadísticas de Ventas', 'Ventas', 'fas fa-chart-bar');
?>

<style>
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0; }
    .stat { background: rgba(26, 26, 46, 0.95); border: 1px solid rgba(98, 0, 255, 0.35); border-radius: 10px; padding: 20px; text-align: center; }
    .stat .number { font-size: 1.8rem; color: #00d4ff; font-weight: bold; }
    .stat .label { color: #aaa; margin-top: 10px; }
    .section { background: rgba(26, 26, 46, 0.95); border: 1px solid rgba(98, 0, 255, 0.35); border-radius: 16px; padding: 25px; margin: 20px 0; }
    .table { background: #f8fafc; border-radius: 12px; overflow: hidden; }
    .table thead { background: #e2e8f0; }
    .table th { color: #0f172a; padding: 14px 16px; font-weight: 700; }
    .table td { color: #0f172a; padding: 14px 16px; border-bottom: 1px solid #e2e8f0; }
</style>

<div>
    <div style="margin-bottom: 20px;">
        <a href="?periodo=hoy" class="btn btn-sm btn-info">Hoy</a>
        <a href="?periodo=semana" class="btn btn-sm btn-info">Esta Semana</a>
        <a href="?periodo=mes" class="btn btn-sm btn-info">Este Mes</a>
    </div>

    <div class="stats-grid">
        <div class="stat">
            <div class="number"><?php echo $stats['total_ventas']; ?></div>
            <div class="label">Total Ventas</div>
        </div>
        <div class="stat">
            <div class="number">BOB <?php echo number_format($stats['total_monto'], 0); ?></div>
            <div class="label">Monto Total</div>
        </div>
        <div class="stat">
            <div class="number">BOB <?php echo number_format($stats['promedio_venta'], 0); ?></div>
            <div class="label">Promedio</div>
        </div>
    </div>

    <div class="section">
        <h3 style="color: #00d4ff; margin-bottom: 20px;"><i class="fas fa-star"></i> Productos Más Vendidos</h3>
        <div style="overflow-x: auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Cantidad</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stats['producto_top'] as $prod): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($prod['nombre']); ?></td>
                        <td><?php echo $prod['vendido']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="section">
        <h3 style="color: #00d4ff; margin-bottom: 20px;"><i class="fas fa-tasks"></i> Estados de Ventas</h3>
        <div style="overflow-x: auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Estado</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stats['estado_ventas'] as $est): ?>
                    <tr>
                        <td><span class="badge bg-info"><?php echo htmlspecialchars($est['estado_venta']); ?></span></td>
                        <td><?php echo $est['total']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php admin_render_footer(); ?>
