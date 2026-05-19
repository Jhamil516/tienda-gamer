<?php
session_start();

require '../config/db.php';
require '../includes/funciones.php';
require '../auth/proteger.php';
requerirCliente();

$search       = trim($_GET['search'] ?? '');
$categoria    = isset($_GET['categoria']) ? intval($_GET['categoria']) : 0;
$plataforma   = trim($_GET['plataforma'] ?? '');
$precio_min   = isset($_GET['precio_min']) && $_GET['precio_min'] !== '' ? floatval($_GET['precio_min']) : null;
$precio_max   = isset($_GET['precio_max']) && $_GET['precio_max'] !== '' ? floatval($_GET['precio_max']) : null;
$page         = max(1, intval($_GET['page'] ?? 1));
$items_por_pagina = 10;
$offset       = ($page - 1) * $items_por_pagina;

$categorias = obtener_categorias($conn);
$plataformas = [];
$result_plataformas = $conn->query("SELECT DISTINCT plataforma FROM productos WHERE plataforma <> '' ORDER BY plataforma ASC");
if ($result_plataformas) {
    while ($row = $result_plataformas->fetch_assoc()) {
        $plataformas[] = $row['plataforma'];
    }
}

$where = " WHERE p.estado = 'activo' AND p.stock > 0";
if ($search) {
    $valor_busqueda = $conn->real_escape_string($search);
    $where .= " AND (p.nombre LIKE '%$valor_busqueda%' OR p.marca LIKE '%$valor_busqueda%' OR c.nombre_categoria LIKE '%$valor_busqueda%')";
}
if ($categoria > 0) {
    $where .= " AND p.id_categoria = $categoria";
}
if ($plataforma) {
    $plataforma_saneada = $conn->real_escape_string($plataforma);
    $where .= " AND p.plataforma LIKE '%$plataforma_saneada%'";
}
if ($precio_min !== null) {
    $precio_min = floatval($precio_min);
    $where .= " AND p.precio_actual >= $precio_min";
}
if ($precio_max !== null) {
    $precio_max = floatval($precio_max);
    $where .= " AND p.precio_actual <= $precio_max";
}

$sql_count = "SELECT COUNT(*) as total FROM productos p JOIN categorias c ON p.id_categoria = c.id_categoria" . $where;
$stmt_count = $conn->prepare($sql_count);
$stmt_count->execute();
$resultado_count = $stmt_count->get_result();
$total_registros = $resultado_count->fetch_assoc()['total'];
$total_paginas = max(1, ceil($total_registros / $items_por_pagina));

$sql = "SELECT p.id_producto, p.nombre, p.marca, p.precio_actual, p.stock, p.imagen_principal, p.descuento_porcentaje, c.nombre_categoria,
        (SELECT ruta_imagen FROM imagenes_producto WHERE id_producto = p.id_producto AND es_principal = 1 LIMIT 1) AS imagen_alt
        FROM productos p
        JOIN categorias c ON p.id_categoria = c.id_categoria"
        . $where
        . " ORDER BY p.fecha_creacion DESC LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $items_por_pagina, $offset);
$stmt->execute();
$resultado = $stmt->get_result();

$queryParams = [];
if ($search) $queryParams['search'] = $search;
if ($categoria) $queryParams['categoria'] = $categoria;
if ($plataforma) $queryParams['plataforma'] = $plataforma;
if ($precio_min !== null) $queryParams['precio_min'] = $precio_min;
if ($precio_max !== null) $queryParams['precio_max'] = $precio_max;
?>

<?php include '../includes/header.php'; ?>

<div class="row mb-4 mt-4">
    <div class="col-md-8">
        <h2>Catálogo de Productos</h2>
        <p class="text-muted">Explora por nombre, marca, categoría o plataforma. Filtra por precio y encuentra tu compra gamer.</p>
    </div>
</div>

<div class="row mb-4">
    <div class="col-12">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label" style="color: #e0e0e0; font-weight: 600;">Buscar</label>
                <input type="text" name="search" class="form-control" placeholder="Nombre, marca o categoría"
                       value="<?php echo htmlspecialchars($search); ?>" style="color: #000; background: #f8f9fa;">
            </div>
            <div class="col-md-2">
                <label class="form-label" style="color: #e0e0e0; font-weight: 600;">Categoría</label>
                <select name="categoria" class="form-select" style="color: #000; background: #f8f9fa;">
                    <option value="">Todas</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?php echo $cat['id_categoria']; ?>" <?php echo $categoria === (int)$cat['id_categoria'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['nombre_categoria']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" style="color: #e0e0e0; font-weight: 600;">Plataforma</label>
                <select name="plataforma" class="form-select" style="color: #000; background: #f8f9fa;">
                    <option value="">Todas</option>
                    <?php foreach ($plataformas as $plat): ?>
                        <option value="<?php echo htmlspecialchars($plat); ?>" <?php echo $plataforma === $plat ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($plat); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" style="color: #e0e0e0; font-weight: 600;">Precio min.</label>
                <input type="number" step="0.01" min="0" name="precio_min" class="form-control"
                       value="<?php echo $precio_min !== null ? htmlspecialchars($precio_min) : ''; ?>" style="color: #000; background: #f8f9fa;">
            </div>
            <div class="col-md-2">
                <label class="form-label" style="color: #e0e0e0; font-weight: 600;">Precio max.</label>
                <input type="number" step="0.01" min="0" name="precio_max" class="form-control"
                       value="<?php echo $precio_max !== null ? htmlspecialchars($precio_max) : ''; ?>" style="color: #000; background: #f8f9fa;">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary">Filtrar</button>
                <?php if ($search || $categoria || $plataforma || $precio_min !== null || $precio_max !== null): ?>
                    <a href="catalogo.php" class="btn btn-secondary">Limpiar</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="row mb-4">
    <?php if ($resultado->num_rows > 0): ?>
        <?php while ($row = $resultado->fetch_assoc()): ?>
            <?php $imagen_mostrar = $row['imagen_principal'] ?: $row['imagen_alt']; ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card product-card h-100 position-relative">
                    <?php if ($imagen_mostrar): ?>
                        <img src="<?php echo BASE_URL . 'uploads/' . htmlspecialchars($imagen_mostrar); ?>"
                                 class="product-image" alt="<?php echo htmlspecialchars($row['nombre']); ?>" loading="lazy">
                        <?php if (!empty($row['descuento_porcentaje']) && $row['descuento_porcentaje'] > 0): ?>
                            <div style="position: absolute; left: 12px; top: 12px;">
                                <span class="badge bg-danger">-<?php echo intval($row['descuento_porcentaje']); ?>%</span>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="product-image d-flex align-items-center justify-content-center">
                            <i class="fas fa-image fa-2x" style="color: #7b7b7b;"></i>
                        </div>
                    <?php endif; ?>
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title" style="color: #00d4ff; font-weight: bold;"><?php echo htmlspecialchars($row['marca']); ?></h5>
                        <p class="card-text mb-2" style="color: #e0e0e0;"><?php echo htmlspecialchars($row['nombre']); ?></p>
                        <p class="mb-2"><span class="badge bg-info text-dark"><?php echo htmlspecialchars($row['nombre_categoria']); ?></span></p>
                        <?php if (!empty($row['plataforma'])): ?>
                            <p class="mb-2"><span class="badge bg-secondary"><?php echo htmlspecialchars($row['plataforma']); ?></span></p>
                        <?php endif; ?>
                        <p class="product-price mb-2">BOB <?php echo number_format($row['precio_actual'], 2); ?>
                            <?php if (!empty($row['descuento_porcentaje']) && $row['descuento_porcentaje'] > 0 && !empty($row['precio_original']) && $row['precio_original'] > $row['precio_actual']): ?>
                                <small class="text-muted ms-2" style="text-decoration: line-through;">BOB <?php echo number_format($row['precio_original'], 2); ?></small>
                            <?php endif; ?>
                        </p>
                        <p class="text-success mb-3"><i class="fas fa-box-seam"></i> Stock: <?php echo $row['stock']; ?></p>
                        <div class="mt-auto d-grid">
                            <a href="producto.php?id=<?php echo $row['id_producto']; ?>" class="btn btn-info btn-sm">
                                <i class="fas fa-eye"></i> Ver detalle
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="col-12">
            <div class="alert alert-info text-center" role="alert">
                <h5>No se encontraron productos</h5>
                <p>El catálogo no tiene resultados con esos filtros. Prueba con otra categoría o rango de precio.</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php if ($total_paginas > 1): ?>
    <nav aria-label="Paginación de catálogo">
        <ul class="pagination justify-content-center">
            <?php
            $baseQuery = $queryParams;
            $baseQuery['page'] = 1;
            ?>
            <li class="page-item <?php echo ($page === 1) ? 'disabled' : ''; ?>">
                <a class="page-link" href="?<?php echo http_build_query($baseQuery); ?>">Inicio</a>
            </li>
            <?php $baseQuery['page'] = max(1, $page - 1); ?>
            <li class="page-item <?php echo ($page === 1) ? 'disabled' : ''; ?>">
                <a class="page-link" href="?<?php echo http_build_query($baseQuery); ?>">Anterior</a>
            </li>
            <?php
            $start_page = max(1, $page - 2);
            $end_page = min($total_paginas, $page + 2);
            for ($i = $start_page; $i <= $end_page; $i++):
                $baseQuery['page'] = $i;
            ?>
                <li class="page-item <?php echo ($i === $page) ? 'active' : ''; ?>">
                    <a class="page-link" href="?<?php echo http_build_query($baseQuery); ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
            <?php $baseQuery['page'] = min($total_paginas, $page + 1); ?>
            <li class="page-item <?php echo ($page === $total_paginas) ? 'disabled' : ''; ?>">
                <a class="page-link" href="?<?php echo http_build_query($baseQuery); ?>">Siguiente</a>
            </li>
            <?php $baseQuery['page'] = $total_paginas; ?>
            <li class="page-item <?php echo ($page === $total_paginas) ? 'disabled' : ''; ?>">
                <a class="page-link" href="?<?php echo http_build_query($baseQuery); ?>">Final</a>
            </li>
        </ul>
    </nav>
    <p class="text-center text-muted">Página <?php echo $page; ?> de <?php echo $total_paginas; ?> | Total de productos: <?php echo $total_registros; ?></p>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
