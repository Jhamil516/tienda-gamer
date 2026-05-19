<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/funciones.php';

if (!es_admin()) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

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

require_once __DIR__ . '/admin_header.php';
admin_render_header('Gestión de Productos', 'Productos', 'fas fa-box');
?>
    <style>
        .section {
            background: rgba(26, 26, 46, 0.95);
            border: 1px solid rgba(98, 0, 255, 0.35);
            border-radius: 16px;
            padding: 25px;
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

        .filter-group select option {
            color: #000000;
            background: #ffffff;
        }

        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: var(--accent);
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 0 10px rgba(0, 212, 255, 0.2);
        }

        .filter-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .filter-buttons button,
        .filter-buttons a {
            padding: 10px 20px;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-filtrar {
            background: linear-gradient(135deg, #6200ff, #00d4ff);
            color: white;
        }

        .btn-filtrar:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(98, 0, 255, 0.4);
        }

        .btn-limpiar {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-light);
            border: 1px solid rgba(98, 0, 255, 0.3);
        }

        .btn-limpiar:hover {
            background: rgba(255, 255, 255, 0.15);
            border-color: var(--accent);
        }

        .product-img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 5px;
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
            cursor: pointer;
            user-select: none;
        }

        .table th:hover {
            background: #cdd8e3;
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

        .badge-stock-bajo {
            background: #ff9800;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .badge-stock-ok {
            background: #4caf50;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
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

        .pagination .disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .pagination .disabled:hover {
            background: rgba(98, 0, 255, 0.2);
            border-color: rgba(98, 0, 255, 0.3);
        }

        .results-info {
            color: var(--text-light);
            margin-bottom: 15px;
            font-size: 0.95rem;
        }

        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .header-section h3 {
            color: var(--accent);
            margin: 0;
        }

        .btn-nuevo {
            background: linear-gradient(135deg, #6200ff, #00d4ff);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-nuevo:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(98, 0, 255, 0.4);
        }
    </style>

    <div class="header-section">
        <h3><i class="fas fa-box"></i> Productos</h3>
        <a href="crear_producto.php" class="btn-nuevo">
            <i class="fas fa-plus"></i> Nuevo Producto
        </a>
    </div>

    <!-- Filtros -->
    <div class="filters-section">
        <form method="GET" id="filtrosForm">
            <div class="filter-row">
                <div class="filter-group">
                    <label for="busqueda"><i class="fas fa-search"></i> Buscar</label>
                    <input type="text" id="busqueda" name="busqueda" placeholder="Nombre o marca..." value="<?php echo htmlspecialchars($filtros['busqueda']); ?>">
                </div>

                <div class="filter-group">
                    <label for="categoria"><i class="fas fa-th-large"></i> Categoría</label>
                    <select id="categoria" name="categoria">
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
                    <input type="number" id="precio_min" name="precio_min" placeholder="0.00" step="0.01" value="<?php echo htmlspecialchars($filtros['precio_min']); ?>">
                </div>

                <div class="filter-group">
                    <label for="precio_max">Precio Máx.</label>
                    <input type="number" id="precio_max" name="precio_max" placeholder="5000.00" step="0.01" value="<?php echo htmlspecialchars($filtros['precio_max']); ?>">
                </div>
            </div>

            <div class="filter-row">
                <div class="filter-group">
                    <label for="stock_filtro"><i class="fas fa-boxes"></i> Stock</label>
                    <select id="stock_filtro" name="stock_filtro">
                        <option value="">Todo el stock</option>
                        <option value="bajo" <?php echo $filtros['stock_filtro'] === 'bajo' ? 'selected' : ''; ?>>Stock bajo</option>
                        <option value="agotado" <?php echo $filtros['stock_filtro'] === 'agotado' ? 'selected' : ''; ?>>Agotado</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="orden"><i class="fas fa-sort"></i> Ordenar por</label>
                    <select id="orden" name="orden">
                        <option value="id" <?php echo $filtros['orden'] === 'id' ? 'selected' : ''; ?>>ID (Reciente)</option>
                        <option value="nombre" <?php echo $filtros['orden'] === 'nombre' ? 'selected' : ''; ?>>Nombre A-Z</option>
                        <option value="precio_asc" <?php echo $filtros['orden'] === 'precio_asc' ? 'selected' : ''; ?>>Precio: Menor a Mayor</option>
                        <option value="precio_desc" <?php echo $filtros['orden'] === 'precio_desc' ? 'selected' : ''; ?>>Precio: Mayor a Menor</option>
                        <option value="stock_bajo" <?php echo $filtros['orden'] === 'stock_bajo' ? 'selected' : ''; ?>>Stock: Menor a Mayor</option>
                    </select>
                </div>
            </div>

            <div class="filter-buttons">
                <a href="productos.php" class="btn-limpiar">
                    <i class="fas fa-redo"></i> Limpiar Filtros
                </a>
            </div>
        </form>
    </div>

    <script>
        document.getElementById('filtrosForm').addEventListener('change', function() {
            this.submit();
        });

        document.getElementById('busqueda').addEventListener('keyup', function() {
            var form = document.getElementById('filtrosForm');
            clearTimeout(form.searchTimeout);
            form.searchTimeout = setTimeout(function() {
                form.submit();
            }, 500);
        });
    </script>

    <!-- Resultados -->
    <div class="results-info">
        Mostrando <strong><?php echo count($productos); ?></strong> de <strong><?php echo $total_productos; ?></strong> productos
        <?php if (!empty($filtros['busqueda'])): ?>
            - Búsqueda: "<strong><?php echo htmlspecialchars($filtros['busqueda']); ?></strong>"
        <?php endif; ?>
    </div>

    <div class="section">
        <?php if (!empty($productos)): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Imagen</th>
                        <th>Nombre</th>
                        <th>Categoría</th>
                        <th>Precio</th>
                        <th>Stock</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($productos as $prod): ?>
                    <tr>
                        <td>
                            <?php if (!empty($prod['imagen_principal'])): ?>
                                <img src="../uploads/<?php echo htmlspecialchars($prod['imagen_principal']); ?>" alt="Producto" class="product-img">
                            <?php else: ?>
                                <span class="badge bg-secondary">Sin imagen</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($prod['nombre']); ?></td>
                        <td><?php echo htmlspecialchars($prod['nombre_categoria']); ?></td>
                        <td>BOB <?php echo number_format($prod['precio_actual'], 2); ?></td>
                        <td>
                            <?php
                            $stock = intval($prod['stock']);
                            $stock_min = intval($prod['stock_minimo']);
                            if ($stock === 0) {
                                echo '<span style="color: #dc3545; font-weight: 600;">Agotado</span>';
                            } elseif ($stock <= $stock_min) {
                                echo '<span class="badge-stock-bajo">' . $stock . ' (BAJO)</span>';
                            } else {
                                echo '<span class="badge-stock-ok">' . $stock . '</span>';
                            }
                            ?>
                        </td>
                        <td>
                            <a href="ver_producto.php?id=<?php echo intval($prod['id_producto']); ?>" class="btn btn-sm btn-info">
                                <i class="fas fa-eye"></i> Ver
                            </a>
                            <a href="editar_producto.php?id=<?php echo intval($prod['id_producto']); ?>" class="btn btn-sm btn-warning">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                            <a href="eliminar_producto.php?id=<?php echo intval($prod['id_producto']); ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Seguro que deseas eliminar este producto?');">
                                <i class="fas fa-trash-alt"></i> Eliminar
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div style="text-align: center; padding: 40px; color: var(--text-light);">
                <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.5;"></i>
                <p>No se encontraron productos con los filtros aplicados.</p>
            </div>
        <?php endif; ?>
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

<?php admin_render_footer(); ?>
