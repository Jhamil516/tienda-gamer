<?php
require_once __DIR__ . '/header.php';

// Paginación
$items_por_pagina = 10;
$pagina_actual = max(1, intval($_GET['page'] ?? 1));
$offset = ($pagina_actual - 1) * $items_por_pagina;

// Obtener filtros
$filtros = [];
$filtros['busqueda'] = $_GET['busqueda'] ?? '';
$filtros['estado'] = $_GET['estado'] ?? '';
$filtros['fecha_desde'] = $_GET['fecha_desde'] ?? '';
$filtros['fecha_hasta'] = $_GET['fecha_hasta'] ?? '';

// Construir query con filtros
$query = "SELECT v.*, u.nombre FROM ventas v JOIN usuarios u ON v.id_usuario = u.id_usuario WHERE 1=1";

if (!empty($filtros['busqueda'])) {
    $busqueda = $conn->real_escape_string($filtros['busqueda']);
    $query .= " AND (v.numero_venta LIKE '%$busqueda%' OR u.nombre LIKE '%$busqueda%')";
}

if (!empty($filtros['estado'])) {
    $estado = $conn->real_escape_string($filtros['estado']);
    $query .= " AND v.estado_venta = '$estado'";
}

if (!empty($filtros['fecha_desde'])) {
    $fecha_desde = $conn->real_escape_string($filtros['fecha_desde']);
    $query .= " AND DATE(v.fecha_venta) >= '$fecha_desde'";
}

if (!empty($filtros['fecha_hasta'])) {
    $fecha_hasta = $conn->real_escape_string($filtros['fecha_hasta']);
    $query .= " AND DATE(v.fecha_venta) <= '$fecha_hasta'";
}

// Obtener total de pedidos
$result_total = $conn->query($query);
$total_pedidos = $result_total->num_rows;
$total_paginas = ceil($total_pedidos / $items_por_pagina);

// Obtener pedidos paginados
$query .= " ORDER BY v.fecha_venta DESC LIMIT $offset, $items_por_pagina";
$pedidos = $conn->query($query)->fetch_all(MYSQLI_ASSOC);

empleado_render_header('Gestión de Pedidos', 'fas fa-shopping-bag');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_estado'])) {
    $id_venta = intval($_POST['id_venta']);
    $nuevo_estado = sanitizar_sql($conn, $_POST['nuevo_estado']);

    $estados_permitidos = ['pendiente', 'confirmada', 'enviada', 'entregada'];
    if (in_array($nuevo_estado, $estados_permitidos)) {
        $conn->query("UPDATE ventas SET estado_venta = '$nuevo_estado' WHERE id_venta = $id_venta");
        header('Location: pedidos.php');
        exit;
    }
}
?>

<style>
    .filters-section {
        background: rgba(26, 26, 46, 0.95);
        border: 1px solid rgba(98, 0, 255, 0.35);
        border-radius: 16px;
        padding: 20px;
        margin-bottom: 25px;
    }

    .filter-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 15px;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
    }

    .filter-group label {
        color: var(--accent);
        font-weight: 600;
        margin-bottom: 8px;
        font-size: 0.95rem;
    }

    .filter-group input,
    .filter-group select {
        padding: 10px 12px;
        background: rgba(255, 255, 255, 0.9);
        border: 1px solid rgba(98, 0, 255, 0.5);
        border-radius: 8px;
        color: #000000;
        font-size: 0.9rem;
        transition: all 0.3s;
    }

    .filter-group input:focus,
    .filter-group select:focus {
        outline: none;
        border-color: var(--accent);
        background: rgba(255, 255, 255, 0.95);
        box-shadow: 0 0 10px rgba(0, 212, 255, 0.2);
    }

    .filter-group select option {
        color: #000000;
        background: #ffffff;
    }

    .btn-limpiar {
        background: rgba(255, 255, 255, 0.1);
        color: var(--text-light);
        border: 1px solid rgba(98, 0, 255, 0.3);
        padding: 10px 20px;
        border-radius: 8px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-weight: 600;
        transition: all 0.3s;
    }

    .btn-limpiar:hover {
        background: rgba(255, 255, 255, 0.15);
        border-color: var(--accent);
        color: var(--accent);
    }

    .section {
        background: rgba(26, 26, 46, 0.95);
        border: 1px solid rgba(98, 0, 255, 0.35);
        border-radius: 16px;
        padding: 25px;
    }

    .results-info {
        color: var(--text-light);
        margin-bottom: 15px;
        font-size: 0.95rem;
    }

    .pagination {
        display: flex;
        justify-content: center;
        gap: 5px;
        margin-top: 25px;
        flex-wrap: wrap;
    }

    .pagination a,
    .pagination span {
        padding: 8px 12px;
        border-radius: 6px;
        text-decoration: none;
        background: rgba(98, 0, 255, 0.2);
        color: var(--accent);
        border: 1px solid rgba(98, 0, 255, 0.3);
        transition: all 0.3s;
    }

    .pagination a:hover {
        background: rgba(98, 0, 255, 0.4);
        border-color: var(--accent);
    }

    .pagination .active {
        background: linear-gradient(135deg, #6200ff, #00d4ff);
        border-color: var(--accent);
        color: white;
    }

    .table {
        background: #f8fafc;
        border-radius: 12px;
        overflow: hidden;
    }

    .table thead {
        background: #e2e8f0;
    }

    .table th {
        color: #0f172a;
        padding: 14px 16px;
        font-weight: 700;
        border-bottom: 1px solid rgba(148, 163, 184, 0.4);
    }

    .table tbody tr {
        background: #ffffff;
    }

    .table td {
        color: #0f172a;
        padding: 14px 16px;
        border-bottom: 1px solid rgba(148, 163, 184, 0.35);
    }

    .table tbody tr:hover {
        background: #e2e8f0;
    }
</style>

<!-- Filtros -->
<div class="filters-section">
    <div class="filter-row">
        <div class="filter-group">
            <label for="busqueda"><i class="fas fa-search"></i> Buscar</label>
            <input type="text" id="busqueda" placeholder="Número de orden o cliente..." value="<?php echo htmlspecialchars($filtros['busqueda']); ?>">
        </div>

        <div class="filter-group">
            <label for="estado"><i class="fas fa-filter"></i> Estado</label>
            <select id="estado">
                <option value="">Todos los estados</option>
                <option value="pendiente" <?php echo $filtros['estado'] === 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                <option value="confirmada" <?php echo $filtros['estado'] === 'confirmada' ? 'selected' : ''; ?>>Confirmada</option>
                <option value="enviada" <?php echo $filtros['estado'] === 'enviada' ? 'selected' : ''; ?>>Enviada</option>
                <option value="entregada" <?php echo $filtros['estado'] === 'entregada' ? 'selected' : ''; ?>>Entregada</option>
            </select>
        </div>

        <div class="filter-group">
            <label for="fecha_desde"><i class="fas fa-calendar"></i> Desde</label>
            <input type="date" id="fecha_desde" value="<?php echo htmlspecialchars($filtros['fecha_desde']); ?>">
        </div>

        <div class="filter-group">
            <label for="fecha_hasta">Hasta</label>
            <input type="date" id="fecha_hasta" value="<?php echo htmlspecialchars($filtros['fecha_hasta']); ?>">
        </div>
    </div>

    <a href="pedidos.php" class="btn-limpiar">
        <i class="fas fa-redo"></i> Limpiar Filtros
    </a>
</div>

<!-- Resultados -->
<div class="results-info">
    Mostrando <strong><?php echo count($pedidos); ?></strong> de <strong><?php echo $total_pedidos; ?></strong> pedidos
</div>

<div class="section">
    <div style="overflow-x: auto;">
        <table class="table" id="tablaPedidos">
            <thead>
                <tr>
                    <th>Número</th>
                    <th>Cliente</th>
                    <th>Total</th>
                    <th>Estado</th>
                    <th>Fecha</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody id="bodyPedidos">
                <?php if (!empty($pedidos)): ?>
                    <?php foreach ($pedidos as $p): ?>
                    <tr>
                        <td><code><?php echo htmlspecialchars($p['numero_venta']); ?></code></td>
                        <td><?php echo htmlspecialchars($p['nombre']); ?></td>
                        <td><strong>BOB <?php echo number_format($p['total'], 2); ?></strong></td>
                        <td>
                            <span class="badge bg-info"><?php echo htmlspecialchars($p['estado_venta']); ?></span>
                        </td>
                        <td><?php echo formatear_fecha($p['fecha_venta']); ?></td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="id_venta" value="<?php echo $p['id_venta']; ?>">
                                <select name="nuevo_estado" class="form-select form-select-sm" style="width: 150px; display: inline; background: white; color: #0f172a;">
                                    <option value="">Cambiar a...</option>
                                    <?php if ($p['estado_venta'] === 'pendiente'): ?>
                                        <option value="confirmada">Confirmada</option>
                                    <?php elseif ($p['estado_venta'] === 'confirmada'): ?>
                                        <option value="enviada">Enviada</option>
                                    <?php elseif ($p['estado_venta'] === 'enviada'): ?>
                                        <option value="entregada">Entregada</option>
                                    <?php endif; ?>
                                </select>
                                <button type="submit" name="actualizar_estado" class="btn btn-sm btn-success">Actualizar</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 30px; color: #999;">
                            <i class="fas fa-search" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
                            No se encontraron pedidos con los filtros aplicados
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Paginación -->
<?php if ($total_paginas > 1): ?>
<div class="pagination">
    <?php if ($pagina_actual > 1): ?>
        <a href="?page=1<?php echo !empty($filtros['busqueda']) ? '&busqueda=' . urlencode($filtros['busqueda']) : ''; ?><?php echo !empty($filtros['estado']) ? '&estado=' . $filtros['estado'] : ''; ?><?php echo !empty($filtros['fecha_desde']) ? '&fecha_desde=' . $filtros['fecha_desde'] : ''; ?><?php echo !empty($filtros['fecha_hasta']) ? '&fecha_hasta=' . $filtros['fecha_hasta'] : ''; ?>">
            <i class="fas fa-chevron-left"></i> Primera
        </a>
        <a href="?page=<?php echo $pagina_actual - 1; ?><?php echo !empty($filtros['busqueda']) ? '&busqueda=' . urlencode($filtros['busqueda']) : ''; ?><?php echo !empty($filtros['estado']) ? '&estado=' . $filtros['estado'] : ''; ?><?php echo !empty($filtros['fecha_desde']) ? '&fecha_desde=' . $filtros['fecha_desde'] : ''; ?><?php echo !empty($filtros['fecha_hasta']) ? '&fecha_hasta=' . $filtros['fecha_hasta'] : ''; ?>">
            <i class="fas fa-chevron-left"></i> Anterior
        </a>
    <?php endif; ?>

    <?php
    $inicio = max(1, $pagina_actual - 2);
    $fin = min($total_paginas, $pagina_actual + 2);

    for ($i = $inicio; $i <= $fin; $i++):
    ?>
        <?php if ($i === $pagina_actual): ?>
            <span class="active"><?php echo $i; ?></span>
        <?php else: ?>
            <a href="?page=<?php echo $i; ?><?php echo !empty($filtros['busqueda']) ? '&busqueda=' . urlencode($filtros['busqueda']) : ''; ?><?php echo !empty($filtros['estado']) ? '&estado=' . $filtros['estado'] : ''; ?><?php echo !empty($filtros['fecha_desde']) ? '&fecha_desde=' . $filtros['fecha_desde'] : ''; ?><?php echo !empty($filtros['fecha_hasta']) ? '&fecha_hasta=' . $filtros['fecha_hasta'] : ''; ?>">
                <?php echo $i; ?>
            </a>
        <?php endif; ?>
    <?php endfor; ?>

    <?php if ($pagina_actual < $total_paginas): ?>
        <a href="?page=<?php echo $pagina_actual + 1; ?><?php echo !empty($filtros['busqueda']) ? '&busqueda=' . urlencode($filtros['busqueda']) : ''; ?><?php echo !empty($filtros['estado']) ? '&estado=' . $filtros['estado'] : ''; ?><?php echo !empty($filtros['fecha_desde']) ? '&fecha_desde=' . $filtros['fecha_desde'] : ''; ?><?php echo !empty($filtros['fecha_hasta']) ? '&fecha_hasta=' . $filtros['fecha_hasta'] : ''; ?>">
            Siguiente <i class="fas fa-chevron-right"></i>
        </a>
        <a href="?page=<?php echo $total_paginas; ?><?php echo !empty($filtros['busqueda']) ? '&busqueda=' . urlencode($filtros['busqueda']) : ''; ?><?php echo !empty($filtros['estado']) ? '&estado=' . $filtros['estado'] : ''; ?><?php echo !empty($filtros['fecha_desde']) ? '&fecha_desde=' . $filtros['fecha_desde'] : ''; ?><?php echo !empty($filtros['fecha_hasta']) ? '&fecha_hasta=' . $filtros['fecha_hasta'] : ''; ?>">
            Última <i class="fas fa-chevron-right"></i>
        </a>
    <?php endif; ?>
</div>
<?php endif; ?>

<script>
    // Auto-aplicar filtros sin recargar
    const filtrosForm = {
        busqueda: document.getElementById('busqueda'),
        estado: document.getElementById('estado'),
        fecha_desde: document.getElementById('fecha_desde'),
        fecha_hasta: document.getElementById('fecha_hasta')
    };

    function aplicarFiltros() {
        const params = new URLSearchParams();
        if (filtrosForm.busqueda.value) params.append('busqueda', filtrosForm.busqueda.value);
        if (filtrosForm.estado.value) params.append('estado', filtrosForm.estado.value);
        if (filtrosForm.fecha_desde.value) params.append('fecha_desde', filtrosForm.fecha_desde.value);
        if (filtrosForm.fecha_hasta.value) params.append('fecha_hasta', filtrosForm.fecha_hasta.value);

        const url = params.toString() ? '?' + params.toString() : '?';
        window.location.href = url;
    }

    filtrosForm.busqueda.addEventListener('keyup', function() {
        clearTimeout(this.searchTimeout);
        this.searchTimeout = setTimeout(aplicarFiltros, 500);
    });

    Object.keys(filtrosForm).forEach(key => {
        if (key !== 'busqueda') {
            filtrosForm[key].addEventListener('change', aplicarFiltros);
        }
    });
</script>

<?php empleado_render_footer(); ?>
