<?php
session_start();
require_once __DIR__ . '/admin_header.php';

admin_render_header('Historial de Ventas', 'Historial', 'fas fa-history');

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$fecha_inicio = isset($_GET['fecha_inicio']) ? trim($_GET['fecha_inicio']) : '';
$fecha_fin = isset($_GET['fecha_fin']) ? trim($_GET['fecha_fin']) : '';
$estado = isset($_GET['estado']) ? trim($_GET['estado']) : '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$items_por_pagina = 10;
$offset = ($page - 1) * $items_por_pagina;

$conditions = [];
$params = [];
$types = '';

if ($search !== '') {
    $conditions[] = "(u.nombre LIKE ? OR u.correo LIKE ? OR v.numero_venta LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sss';
}

if ($fecha_inicio !== '') {
    $conditions[] = "DATE(v.fecha_venta) >= ?";
    $params[] = $fecha_inicio;
    $types .= 's';
}

if ($fecha_fin !== '') {
    $conditions[] = "DATE(v.fecha_venta) <= ?";
    $params[] = $fecha_fin;
    $types .= 's';
}

$estado_permitido = ['pendiente', 'confirmada', 'enviada', 'entregada', 'cancelada'];
if ($estado !== '' && in_array($estado, $estado_permitido, true)) {
    $conditions[] = "v.estado_venta = ?";
    $params[] = $estado;
    $types .= 's';
}

$where = '';
if (!empty($conditions)) {
    $where = 'WHERE ' . implode(' AND ', $conditions);
}

// Contar total de ventas
$sql_count = "SELECT COUNT(*) as total FROM ventas v JOIN usuarios u ON v.id_usuario = u.id_usuario $where";
$stmt_count = $conn->prepare($sql_count);
if (!empty($params)) {
    $stmt_count->bind_param($types, ...$params);
}
$stmt_count->execute();
$resultado_count = $stmt_count->get_result();
$total_registros = $resultado_count->fetch_assoc()['total'];
$resultado_count->free();
$stmt_count->close();
$total_paginas = max(1, ceil($total_registros / $items_por_pagina));

// Obtener ventas
$sql = "SELECT v.id_venta, v.numero_venta, u.nombre, u.correo, v.fecha_venta, v.total, v.estado_venta FROM ventas v
        JOIN usuarios u ON v.id_usuario = u.id_usuario $where ORDER BY v.fecha_venta DESC LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);
$bind_params = $params;
$bind_types = $types . 'ii';
$bind_params[] = $items_por_pagina;
$bind_params[] = $offset;
$stmt->bind_param($bind_types, ...$bind_params);
$stmt->execute();
$resultado = $stmt->get_result();

// Totales
$sql_total = "SELECT SUM(v.total) as total_ventas, COUNT(DISTINCT v.id_usuario) as total_clientes FROM ventas v JOIN usuarios u ON v.id_usuario = u.id_usuario $where";
$stmt_total = $conn->prepare($sql_total);
if (!empty($params)) {
    $stmt_total->bind_param($types, ...$params);
}
$stmt_total->execute();
$total_data = $stmt_total->get_result()->fetch_assoc();
$stmt_total->close();
$total_vtas = $total_data;
$total_clientes = $total_data;
?>

<style>
    .historial-card {background: rgba(26,26,46,0.95); border: 1px solid rgba(98,0,255,0.35); border-radius: 16px; padding: 24px; margin-bottom: 20px;}
    .stat-mini {background: rgba(98,0,255,0.15); border: 1px solid rgba(98,0,255,0.4); border-radius: 10px; padding: 15px; text-align: center; margin-bottom: 20px;}
    .stat-mini h3 {color: #00d4ff; margin: 0; font-size: 1.8rem; font-weight: bold;}
    .stat-mini p {color: #aaa; margin: 0; font-size: 0.85rem;}
    .table {color: #e0e0e0;}
    .table th {color: #00d4ff; border-bottom: 1px solid rgba(98,0,255,0.3); font-weight: 600;}
    .table td {border-bottom: 1px solid rgba(98,0,255,0.1); vertical-align: middle;}
    .badge-estado {padding: 5px 12px; border-radius: 20px; font-size: 0.78rem; font-weight: bold;}
    .badge-pendiente {background: rgba(234,179,8,0.2); color: #fbbf24; border: 1px solid #fbbf24;}
    .badge-confirmada {background: rgba(59,130,246,0.2); color: #60a5fa; border: 1px solid #60a5fa;}
    .badge-enviada {background: rgba(168,85,247,0.2); color: #c084fc; border: 1px solid #c084fc;}
    .badge-entregada {background: rgba(16,185,129,0.2); color: #34d399; border: 1px solid #34d399;}
    .badge-cancelada {background: rgba(239,68,68,0.2); color: #f87171; border: 1px solid #f87171;}
    .search-box {background: rgba(71,85,105,0.3); border: 1px solid rgba(98,0,255,0.4); color: #fff; border-radius: 8px; padding: 10px 15px; width: 280px;}
    .search-box:focus {outline: none; border-color: #00d4ff; box-shadow: 0 0 8px rgba(0,212,255,0.3);}
    .page-link {background: rgba(26,26,46,0.9); color: #00d4ff; border-color: rgba(98,0,255,0.4);}
    .page-item.active .page-link {background: #6200ff; border-color: #6200ff; color: #fff;}
</style>

<div class="historial-card">
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="stat-mini">
                <h3>BOB <?php echo number_format($total_vtas['total_ventas'] ?? 0, 2, ',', '.'); ?></h3>
                <p>Total en ventas</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-mini">
                <h3><?php echo $total_registros; ?></h3>
                <p>Órdenes registradas</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-mini">
                <h3><?php echo $total_clientes['total_clientes']; ?></h3>
                <p>Clientes únicos</p>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-3">
        <h5 style="color:#00d4ff; margin:0;"><i class="fas fa-history"></i> Historial de Ventas</h5>
        <form method="GET" style="display:flex; flex-wrap:wrap; gap:8px; align-items:center;">
            <input type="text" name="search" class="search-box" placeholder="Buscar cliente, correo u orden..." value="<?php echo htmlspecialchars($search); ?>">
            <input type="date" name="fecha_inicio" class="search-box" style="width:auto;" value="<?php echo htmlspecialchars($fecha_inicio); ?>">
            <input type="date" name="fecha_fin" class="search-box" style="width:auto;" value="<?php echo htmlspecialchars($fecha_fin); ?>">
            <select name="estado" class="search-box" style="width:auto; min-width:170px;">
                <option value="">Todos los estados</option>
                <option value="pendiente" <?php echo $estado === 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                <option value="confirmada" <?php echo $estado === 'confirmada' ? 'selected' : ''; ?>>Confirmada</option>
                <option value="enviada" <?php echo $estado === 'enviada' ? 'selected' : ''; ?>>Enviada</option>
                <option value="entregada" <?php echo $estado === 'entregada' ? 'selected' : ''; ?>>Entregada</option>
                <option value="cancelada" <?php echo $estado === 'cancelada' ? 'selected' : ''; ?>>Cancelada</option>
            </select>
            <button type="submit" class="btn btn-sm" style="background:#6200ff; color:#fff; border-radius:8px; padding:8px 16px;">Filtrar</button>
            <?php if ($search || $fecha_inicio || $fecha_fin || $estado): ?>
            <a href="historial_ventas.php" class="btn btn-sm" style="background:rgba(255,255,255,0.1); color:#ccc; border-radius:8px; padding:8px 16px;">Limpiar</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-borderless">
            <thead>
                <tr>
                    <th># Orden</th>
                    <th>Cliente</th>
                    <th>Correo</th>
                    <th>Fecha</th>
                    <th>Total</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($venta = $resultado->fetch_assoc()): ?>
                <tr>
                    <td><span style="color:#6200ff; font-family:monospace;"><?php echo htmlspecialchars($venta['numero_venta'] ?? '#'.$venta['id_venta']); ?></span></td>
                    <td><?php echo htmlspecialchars($venta['nombre']); ?></td>
                    <td style="color:#aaa; font-size:0.9rem;"><?php echo htmlspecialchars($venta['correo']); ?></td>
                    <td style="color:#aaa;"><?php echo date('d/m/Y H:i', strtotime($venta['fecha_venta'])); ?></td>
                    <td style="color:#00d4ff; font-weight:bold;">BOB <?php echo number_format($venta['total'], 2, ',', '.'); ?></td>
                    <td><span class="badge-estado badge-<?php echo $venta['estado_venta']; ?>"><?php echo ucfirst($venta['estado_venta']); ?></span></td>
                    <td>
                        <a href="ver_venta_admin.php?id=<?php echo $venta['id_venta']; ?>" class="btn btn-sm" style="background:rgba(0,212,255,0.15); color:#00d4ff; border:1px solid #00d4ff; border-radius:6px; padding:4px 12px; font-size:0.8rem;">
                            <i class="fas fa-eye"></i> Ver
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if ($total_registros === 0): ?>
                <tr><td colspan="7" style="text-align:center; color:#aaa; padding:30px;">No hay ventas registradas<?php echo $search ? " para \"$search\"" : ''; ?>.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($total_paginas > 1): ?>
    <nav>
        <ul class="pagination justify-content-center mt-3">
            <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&fecha_inicio=<?php echo urlencode($fecha_inicio); ?>&fecha_fin=<?php echo urlencode($fecha_fin); ?>&estado=<?php echo urlencode($estado); ?>"><?php echo $i; ?></a>
            </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/admin_footer.php'; admin_render_footer(); ?>
