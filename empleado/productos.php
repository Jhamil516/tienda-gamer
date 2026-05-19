<?php
require_once __DIR__ . '/header.php';

// Paginación
$items_por_pagina = 10;
$pagina_actual = max(1, intval($_GET['page'] ?? 1));
$offset = ($pagina_actual - 1) * $items_por_pagina;

// Obtener filtros
$filtros = [];
$filtros['categoria'] = $_GET['categoria'] ?? '';
$filtros['precio_min'] = $_GET['precio_min'] ?? '';
$filtros['precio_max'] = $_GET['precio_max'] ?? '';
$filtros['stock_filtro'] = $_GET['stock_filtro'] ?? '';
$filtros['busqueda'] = $_GET['busqueda'] ?? '';
$filtros['orden'] = $_GET['orden'] ?? 'id';

// Construir query con filtros
$query = "SELECT p.*, c.nombre_categoria FROM productos p JOIN categorias c ON p.id_categoria = c.id_categoria WHERE 1=1";

if (!empty($filtros['categoria'])) {
    $categoria = intval($filtros['categoria']);
    $query .= " AND p.id_categoria = $categoria";
}

if (!empty($filtros['precio_min'])) {
    $min = floatval($filtros['precio_min']);
    $query .= " AND p.precio_actual >= $min";
}

if (!empty($filtros['precio_max'])) {
    $max = floatval($filtros['precio_max']);
    $query .= " AND p.precio_actual <= $max";
}

if ($filtros['stock_filtro'] === 'bajo') {
    $query .= " AND p.stock <= p.stock_minimo";
} elseif ($filtros['stock_filtro'] === 'agotado') {
    $query .= " AND p.stock = 0";
}

if (!empty($filtros['busqueda'])) {
    $busqueda = $conn->real_escape_string($filtros['busqueda']);
    $query .= " AND (p.nombre LIKE '%$busqueda%' OR p.marca LIKE '%$busqueda%')";
}

// Ordenamiento
switch ($filtros['orden']) {
    case 'precio_asc':
        $query .= " ORDER BY p.precio_actual ASC";
        break;
    case 'precio_desc':
        $query .= " ORDER BY p.precio_actual DESC";
        break;
    case 'nombre':
        $query .= " ORDER BY p.nombre ASC";
        break;
    case 'stock_bajo':
        $query .= " ORDER BY p.stock ASC";
        break;
    default:
        $query .= " ORDER BY p.id_producto DESC";
}

// Obtener total de productos
$result_total = $conn->query($query);
$total_productos = $result_total->num_rows;
$total_paginas = ceil($total_productos / $items_por_pagina);

// Obtener productos paginados
$query .= " LIMIT $offset, $items_por_pagina";
$productos = $conn->query($query)->fetch_all(MYSQLI_ASSOC);

// Obtener categorías
$categorias = obtener_categorias($conn);

$mensaje = '';

if (isset($_GET['creado'])) {
    $mensaje = 'Producto creado correctamente.';
} elseif (isset($_GET['editado'])) {
    $mensaje = 'Producto actualizado correctamente.';
} elseif (isset($_GET['eliminado'])) {
    $mensaje = 'Producto eliminado correctamente.';
}

empleado_render_header('Gestión de Productos', 'fas fa-box');
?>
<style>
    .section {
        background: rgba(26, 26, 46, 0.95);
        border: 1px solid rgba(98, 0, 255, 0.35);
        border-radius: 16px;
        padding: 24px;
    }

    .products-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 15px;
        margin-bottom: 25px;
        flex-wrap: wrap;
    }

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

    .product-action-btns a,
    .product-action-btns form {
        display: inline-block;
        margin-right: 6px;
    }

    .product-action-btns button,
    .product-action-btns a {
        border-radius: 10px;
        padding: 8px 12px;
    }

    .product-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 860px;
    }

    .product-table th,
    .product-table td {
        padding: 14px 14px;
        text-align: left;
        border-bottom: 1px solid rgba(255,255,255,0.08);
        vertical-align: middle;
    }

    .product-table th {
        color: #9ca3af;
        font-weight: 600;
        font-size: 0.95rem;
    }

    .product-table td {
        color: #f8fafc;
        font-size: 0.95rem;
    }

    .badge {
        padding: 5px 11px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 0.82rem;
        font-weight: 700;
    }

    .badge-activo { background: rgba(16, 185, 129, 0.16); color: #a7f3d0; }
    .badge-agotado { background: rgba(239, 68, 68, 0.16); color: #fecaca; }
    .badge-inactivo { background: rgba(148, 163, 184, 0.16); color: #cbd5e1; }
    .alert { border-radius: 12px; }
    .btn-primary, .btn-secondary, .btn-info, .btn-warning, .btn-danger {
        border-radius: 10px;
    }
    .btn-sm { padding: 6px 10px; font-size: 0.85rem; }

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
</style>

<div class="section">
    <div class="products-header">
        <div>
            <h2 style="color:#00d4ff; margin-bottom:6px;">Gestión de Productos</h2>
            <p style="color:#cbd5e1; margin:0;">Administra los productos desde el panel de empleado.</p>
        </div>
        <a href="crear_producto.php" class="btn btn-primary"><i class="fas fa-plus"></i> Nuevo producto</a>
    </div>

    <?php if (!empty($mensaje)): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($mensaje); ?></div>
    <?php endif; ?>

    <?php if (isset($_SESSION['mensaje'])): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_SESSION['mensaje']); unset($_SESSION['mensaje']); ?></div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <!-- Filtros -->
    <div class="filters-section">
        <div class="filter-row">
            <div class="filter-group">
                <label for="busqueda"><i class="fas fa-search"></i> Buscar</label>
                <input type="text" id="busqueda" placeholder="Nombre o marca..." value="<?php echo htmlspecialchars($filtros['busqueda']); ?>">
            </div>

            <div class="filter-group">
                <label for="categoria"><i class="fas fa-th-large"></i> Categoría</label>
                <select id="categoria">
                    <option value="">Todas las categorías</option>
                    <?php foreach ($categorias as $cat): ?>
                    <option value="<?php echo $cat['id_categoria']; ?>" <?php echo $filtros['categoria'] == $cat['id_categoria'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat['nombre_categoria']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label for="precio_min"><i class="fas fa-dollar-sign"></i> Precio Mín.</label>
                <input type="number" id="precio_min" placeholder="0.00" step="0.01" value="<?php echo htmlspecialchars($filtros['precio_min']); ?>">
            </div>

            <div class="filter-group">
                <label for="precio_max">Precio Máx.</label>
                <input type="number" id="precio_max" placeholder="5000.00" step="0.01" value="<?php echo htmlspecialchars($filtros['precio_max']); ?>">
            </div>
        </div>

        <div class="filter-row">
            <div class="filter-group">
                <label for="stock_filtro"><i class="fas fa-boxes"></i> Stock</label>
                <select id="stock_filtro">
                    <option value="">Todo el stock</option>
                    <option value="bajo" <?php echo $filtros['stock_filtro'] === 'bajo' ? 'selected' : ''; ?>>Stock bajo</option>
                    <option value="agotado" <?php echo $filtros['stock_filtro'] === 'agotado' ? 'selected' : ''; ?>>Agotado</option>
                </select>
            </div>

            <div class="filter-group">
                <label for="orden"><i class="fas fa-sort"></i> Ordenar por</label>
                <select id="orden">
                    <option value="id" <?php echo $filtros['orden'] === 'id' ? 'selected' : ''; ?>>ID (Reciente)</option>
                    <option value="nombre" <?php echo $filtros['orden'] === 'nombre' ? 'selected' : ''; ?>>Nombre A-Z</option>
                    <option value="precio_asc" <?php echo $filtros['orden'] === 'precio_asc' ? 'selected' : ''; ?>>Precio: Menor a Mayor</option>
                    <option value="precio_desc" <?php echo $filtros['orden'] === 'precio_desc' ? 'selected' : ''; ?>>Precio: Mayor a Menor</option>
                    <option value="stock_bajo" <?php echo $filtros['orden'] === 'stock_bajo' ? 'selected' : ''; ?>>Stock: Menor a Mayor</option>
                </select>
            </div>
        </div>

        <a href="productos.php" class="btn-limpiar">
            <i class="fas fa-redo"></i> Limpiar Filtros
        </a>
    </div>

    <!-- Resultados -->
    <div class="results-info">
        Mostrando <strong><?php echo count($productos); ?></strong> de <strong><?php echo $total_productos; ?></strong> productos
    </div>

    <div style="overflow-x:auto;">
        <table class="product-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Categoría</th>
                    <th>Precio</th>
                    <th>Stock</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($productos)): ?>
                    <tr>
                        <td colspan="7" style="text-align:center; padding: 30px 0; color:#94a3b8;">No se encontraron productos.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($productos as $producto): ?>
                        <tr>
                            <td>#<?php echo $producto['id_producto']; ?></td>
                            <td><?php echo htmlspecialchars($producto['nombre']); ?></td>
                            <td><?php echo htmlspecialchars($producto['nombre_categoria']); ?></td>
                            <td>BOB <?php echo number_format($producto['precio_actual'], 2); ?></td>
                            <td><?php echo intval($producto['stock']); ?></td>
                            <td>
                                <?php if ($producto['estado'] === 'activo'): ?>
                                    <span class="badge badge-activo">Activo</span>
                                <?php elseif ($producto['estado'] === 'agotado'): ?>
                                    <span class="badge badge-agotado">Agotado</span>
                                <?php else: ?>
                                    <span class="badge badge-inactivo">Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td class="product-action-btns">
                                <a href="ver_producto.php?id=<?php echo $producto['id_producto']; ?>" class="btn btn-sm btn-info"><i class="fas fa-eye"></i></a>
                                <a href="editar_producto.php?id=<?php echo $producto['id_producto']; ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                                <form method="POST" action="eliminar_producto.php" style="display:inline;">
                                    <input type="hidden" name="id_producto" value="<?php echo $producto['id_producto']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('¿Estás seguro de que deseas eliminar este producto?');"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    <?php if ($total_paginas > 1): ?>
    <div class="pagination">
        <?php if ($pagina_actual > 1): ?>
            <a href="?page=1<?php echo !empty($filtros['busqueda']) ? '&busqueda=' . urlencode($filtros['busqueda']) : ''; ?><?php echo !empty($filtros['categoria']) ? '&categoria=' . $filtros['categoria'] : ''; ?><?php echo !empty($filtros['precio_min']) ? '&precio_min=' . $filtros['precio_min'] : ''; ?><?php echo !empty($filtros['precio_max']) ? '&precio_max=' . $filtros['precio_max'] : ''; ?><?php echo !empty($filtros['stock_filtro']) ? '&stock_filtro=' . $filtros['stock_filtro'] : ''; ?><?php echo !empty($filtros['orden']) ? '&orden=' . $filtros['orden'] : ''; ?>">
                <i class="fas fa-chevron-left"></i> Primera
            </a>
            <a href="?page=<?php echo $pagina_actual - 1; ?><?php echo !empty($filtros['busqueda']) ? '&busqueda=' . urlencode($filtros['busqueda']) : ''; ?><?php echo !empty($filtros['categoria']) ? '&categoria=' . $filtros['categoria'] : ''; ?><?php echo !empty($filtros['precio_min']) ? '&precio_min=' . $filtros['precio_min'] : ''; ?><?php echo !empty($filtros['precio_max']) ? '&precio_max=' . $filtros['precio_max'] : ''; ?><?php echo !empty($filtros['stock_filtro']) ? '&stock_filtro=' . $filtros['stock_filtro'] : ''; ?><?php echo !empty($filtros['orden']) ? '&orden=' . $filtros['orden'] : ''; ?>">
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
                <a href="?page=<?php echo $i; ?><?php echo !empty($filtros['busqueda']) ? '&busqueda=' . urlencode($filtros['busqueda']) : ''; ?><?php echo !empty($filtros['categoria']) ? '&categoria=' . $filtros['categoria'] : ''; ?><?php echo !empty($filtros['precio_min']) ? '&precio_min=' . $filtros['precio_min'] : ''; ?><?php echo !empty($filtros['precio_max']) ? '&precio_max=' . $filtros['precio_max'] : ''; ?><?php echo !empty($filtros['stock_filtro']) ? '&stock_filtro=' . $filtros['stock_filtro'] : ''; ?><?php echo !empty($filtros['orden']) ? '&orden=' . $filtros['orden'] : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if ($pagina_actual < $total_paginas): ?>
            <a href="?page=<?php echo $pagina_actual + 1; ?><?php echo !empty($filtros['busqueda']) ? '&busqueda=' . urlencode($filtros['busqueda']) : ''; ?><?php echo !empty($filtros['categoria']) ? '&categoria=' . $filtros['categoria'] : ''; ?><?php echo !empty($filtros['precio_min']) ? '&precio_min=' . $filtros['precio_min'] : ''; ?><?php echo !empty($filtros['precio_max']) ? '&precio_max=' . $filtros['precio_max'] : ''; ?><?php echo !empty($filtros['stock_filtro']) ? '&stock_filtro=' . $filtros['stock_filtro'] : ''; ?><?php echo !empty($filtros['orden']) ? '&orden=' . $filtros['orden'] : ''; ?>">
                Siguiente <i class="fas fa-chevron-right"></i>
            </a>
            <a href="?page=<?php echo $total_paginas; ?><?php echo !empty($filtros['busqueda']) ? '&busqueda=' . urlencode($filtros['busqueda']) : ''; ?><?php echo !empty($filtros['categoria']) ? '&categoria=' . $filtros['categoria'] : ''; ?><?php echo !empty($filtros['precio_min']) ? '&precio_min=' . $filtros['precio_min'] : ''; ?><?php echo !empty($filtros['precio_max']) ? '&precio_max=' . $filtros['precio_max'] : ''; ?><?php echo !empty($filtros['stock_filtro']) ? '&stock_filtro=' . $filtros['stock_filtro'] : ''; ?><?php echo !empty($filtros['orden']) ? '&orden=' . $filtros['orden'] : ''; ?>">
                Última <i class="fas fa-chevron-right"></i>
            </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<script>
    // Auto-aplicar filtros sin recargar
    const filtrosForm = {
        busqueda: document.getElementById('busqueda'),
        categoria: document.getElementById('categoria'),
        precio_min: document.getElementById('precio_min'),
        precio_max: document.getElementById('precio_max'),
        stock_filtro: document.getElementById('stock_filtro'),
        orden: document.getElementById('orden')
    };

    function aplicarFiltros() {
        const params = new URLSearchParams();
        if (filtrosForm.busqueda.value) params.append('busqueda', filtrosForm.busqueda.value);
        if (filtrosForm.categoria.value) params.append('categoria', filtrosForm.categoria.value);
        if (filtrosForm.precio_min.value) params.append('precio_min', filtrosForm.precio_min.value);
        if (filtrosForm.precio_max.value) params.append('precio_max', filtrosForm.precio_max.value);
        if (filtrosForm.stock_filtro.value) params.append('stock_filtro', filtrosForm.stock_filtro.value);
        if (filtrosForm.orden.value) params.append('orden', filtrosForm.orden.value);

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
