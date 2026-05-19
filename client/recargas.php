<?php
session_start();

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../auth/proteger.php';
requerirCliente();

$id_usuario = $_SESSION['id_usuario'];
$error = '';
$success = '';

// ── Obtener filtros ──────────────────────────────────────────────────────────
$tipo_filtro = trim($_GET['tipo'] ?? '');
$busqueda = trim($_GET['busqueda'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$items_por_pagina = 12;
$offset = ($page - 1) * $items_por_pagina;

// ── Obtener tipos disponibles ────────────────────────────────────────────────
$tipos_disponibles = [];
$result_tipos = $conn->query("SELECT DISTINCT tipo FROM recargas_digitales WHERE estado = 'activa' ORDER BY tipo ASC");
if ($result_tipos) {
    while ($row = $result_tipos->fetch_assoc()) {
        $tipos_disponibles[] = $row['tipo'];
    }
}

// ── Agregar recarga al carrito ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_recarga'])) {
    $id_recarga = intval($_POST['id_recarga']);
    $cantidad = max(1, intval($_POST['cantidad'] ?? 1));

    // Obtener información de la recarga
    $stmt = $conn->prepare("SELECT * FROM recargas_digitales WHERE id_recarga = ? AND estado = 'activa'");
    $stmt->bind_param('i', $id_recarga);
    $stmt->execute();
    $result = $stmt->get_result();
    $recarga = $result->fetch_assoc();

    if (!$recarga) {
        $error = 'Recarga no disponible';
    } elseif ($recarga['stock'] < $cantidad) {
        $error = 'Stock insuficiente';
    } else {
        // Insertar en carrito (usando id_producto = 10000 + id_recarga para evitar conflictos)
        $id_producto_carrito = 10000 + $id_recarga;
        
        $stmt_insert2 = $conn->prepare("SELECT id_carrito FROM carrito WHERE id_usuario = ? AND id_producto = ?");
        $stmt_insert2->bind_param('ii', $id_usuario, $id_producto_carrito);
        $stmt_insert2->execute();
        $result_check = $stmt_insert2->get_result();

        if ($result_check->num_rows > 0) {
            // Actualizar cantidad (con id_producto = 10000 + id_recarga)
            $item = $result_check->fetch_assoc();
            $id_producto_carrito = 10000 + $id_recarga;
            $stmt_update = $conn->prepare("UPDATE carrito SET cantidad = cantidad + ? WHERE id_usuario = ? AND id_producto = ?");
            $stmt_update->bind_param('iii', $cantidad, $id_usuario, $id_producto_carrito);
            if ($stmt_update->execute()) {
                $success = 'Recarga agregada al carrito';
            } else {
                $error = 'Error al agregar recarga: ' . $conn->error;
            }
        } else {
            // Insertar nuevo (almacenar con id_producto = 10000 + id_recarga para evitar conflictos)
            $id_producto_carrito = 10000 + $id_recarga;
            $stmt_insert3 = $conn->prepare("INSERT INTO carrito (id_usuario, id_producto, cantidad, precio_unitario) VALUES (?, ?, ?, ?)");
            $stmt_insert3->bind_param('iiid', $id_usuario, $id_producto_carrito, $cantidad, $recarga['precio']);
            if ($stmt_insert3->execute()) {
                $success = 'Recarga agregada al carrito';
            } else {
                $error = 'Error al agregar recarga: ' . $conn->error;
            }
        }
    }

    header("Location: recargas.php?tipo=" . urlencode($tipo_filtro) . 
           ($error ? "&error=" . urlencode($error) : "&success=" . urlencode($success)) .
           ($busqueda ? "&busqueda=" . urlencode($busqueda) : ""));
    exit;
}

// ── Construir consulta de recargas ───────────────────────────────────────────
$where = "WHERE estado = 'activa'";
if ($tipo_filtro) {
    $tipo_saneado = $conn->real_escape_string($tipo_filtro);
    $where .= " AND tipo = '$tipo_saneado'";
}
if ($busqueda) {
    $busqueda_saneada = $conn->real_escape_string($busqueda);
    $where .= " AND (nombre LIKE '%$busqueda_saneada%' OR descripcion LIKE '%$busqueda_saneada%')";
}

$sql_count = "SELECT COUNT(*) as total FROM recargas_digitales $where";
$result_count = $conn->query($sql_count);
$total_registros = $result_count->fetch_assoc()['total'];
$total_paginas = max(1, ceil($total_registros / $items_por_pagina));

$sql = "SELECT * FROM recargas_digitales $where ORDER BY nombre ASC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $items_por_pagina, $offset);
$stmt->execute();
$resultado = $stmt->get_result();

$queryParams = [];
if ($tipo_filtro) $queryParams['tipo'] = $tipo_filtro;
if ($busqueda) $queryParams['busqueda'] = $busqueda;
?>

<?php include '../includes/header.php'; ?>

<style>
    .recarga-card {
        background: rgba(26, 26, 46, 0.92);
        border: 1px solid rgba(0, 212, 255, 0.12);
        border-radius: 16px;
        overflow: hidden;
        transition: transform 0.25s ease, border-color 0.25s ease;
        padding: 20px;
        display: flex;
        flex-direction: column;
        height: 100%;
    }

    .recarga-card:hover {
        transform: translateY(-4px);
        border-color: rgba(0, 212, 255, 0.35);
    }

    .recarga-icon {
        font-size: 3rem;
        margin-bottom: 15px;
        text-align: center;
    }

    .recarga-name {
        font-weight: bold;
        font-size: 1.1rem;
        color: #00d4ff;
        margin-bottom: 8px;
    }

    .recarga-type {
        display: inline-block;
        background: rgba(0, 212, 255, 0.15);
        color: #00d4ff;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.85rem;
        margin-bottom: 12px;
    }

    .recarga-description {
        color: #a0a0a0;
        font-size: 0.9rem;
        margin-bottom: 12px;
        flex-grow: 1;
    }

    .recarga-price {
        font-size: 1.5rem;
        color: #00ff00;
        font-weight: bold;
        margin: 12px 0;
    }

    .recarga-stock {
        font-size: 0.9rem;
        color: #a0a0a0;
        margin-bottom: 12px;
    }

    .stock-badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.85rem;
    }

    .stock-available {
        background: rgba(0, 255, 0, 0.15);
        color: #00ff00;
    }

    .stock-limited {
        background: rgba(255, 165, 0, 0.15);
        color: #ffa500;
    }

    .stock-unavailable {
        background: rgba(255, 0, 0, 0.15);
        color: #ff6b6b;
    }

    .type-icon {
        margin-right: 5px;
    }

    .filters-section {
        background: rgba(26, 26, 46, 0.95);
        border: 1px solid rgba(98, 0, 255, 0.35);
        border-radius: 16px;
        padding: 20px;
        margin-bottom: 30px;
    }

    .section-title {
        color: #00d4ff;
        font-size: 1.8rem;
        font-weight: bold;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .section-subtitle {
        color: #a0a0a0;
        margin-bottom: 20px;
    }
</style>

<div class="row mb-4 mt-4">
    <div class="col-md-8">
        <h2 class="section-title"><i class="fas fa-coins"></i> Recargas Digitales</h2>
        <p class="section-subtitle">Compra créditos para tus plataformas favoritas</p>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger" role="alert">
        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success" role="alert">
        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
    </div>
<?php endif; ?>

<!-- Filtros -->
<div class="filters-section">
    <form method="GET" class="row g-3 align-items-end">
        <div class="col-md-4">
            <label class="form-label">Buscar</label>
            <input type="text" name="busqueda" class="form-control" placeholder="Nombre de la recarga"
                   value="<?php echo htmlspecialchars($busqueda); ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label">Tipo</label>
            <select name="tipo" class="form-select">
                <option value="">Todos los tipos</option>
                <?php foreach ($tipos_disponibles as $t): ?>
                    <option value="<?php echo htmlspecialchars($t); ?>" <?php echo $tipo_filtro === $t ? 'selected' : ''; ?>>
                        <?php 
                        $iconos = [
                            'steam' => '🎮 Steam',
                            'riot_points' => '⚔️ Riot Points',
                            'free_fire' => '🔥 Free Fire',
                            'playstation' => '🎮 PlayStation',
                            'xbox' => '🎮 Xbox',
                            'nintendo' => '🎮 Nintendo',
                            'otro' => '💎 Otros'
                        ];
                        echo $iconos[$t] ?? ucfirst(str_replace('_', ' ', $t));
                        ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-primary">Filtrar</button>
            <?php if ($tipo_filtro || $busqueda): ?>
                <a href="recargas.php" class="btn btn-secondary">Limpiar</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Grid de recargas -->
<?php if ($resultado->num_rows > 0): ?>
    <div class="row mb-4">
        <?php while ($recarga = $resultado->fetch_assoc()): ?>
            <div class="col-md-6 col-lg-3 mb-4">
                <div class="recarga-card">
                    <div class="recarga-icon">
                        <?php
                        $iconos = [
                            'steam' => '🎮',
                            'riot_points' => '⚔️',
                            'free_fire' => '🔥',
                            'playstation' => '🎮',
                            'xbox' => '🎮',
                            'nintendo' => '🎮',
                            'otro' => '💎'
                        ];
                        echo $iconos[$recarga['tipo']] ?? '💳';
                        ?>
                    </div>

                    <div class="recarga-name"><?php echo htmlspecialchars($recarga['nombre']); ?></div>

                    <span class="recarga-type">
                        <?php
                        $tipos_display = [
                            'steam' => 'Steam',
                            'riot_points' => 'Riot Points',
                            'free_fire' => 'Free Fire',
                            'playstation' => 'PlayStation',
                            'xbox' => 'Xbox',
                            'nintendo' => 'Nintendo',
                            'otro' => 'Otros'
                        ];
                        echo $tipos_display[$recarga['tipo']] ?? ucfirst(str_replace('_', ' ', $recarga['tipo']));
                        ?>
                    </span>

                    <?php if ($recarga['descripcion']): ?>
                        <div class="recarga-description">
                            <?php echo htmlspecialchars($recarga['descripcion']); ?>
                        </div>
                    <?php endif; ?>

                    <div class="recarga-price">
                        Bs. <?php echo number_format($recarga['precio'], 2); ?>
                    </div>

                    <div class="recarga-stock">
                        Stock disponible:
                        <?php if ($recarga['stock'] > 5): ?>
                            <span class="stock-badge stock-available">
                                <i class="fas fa-check-circle"></i> <?php echo $recarga['stock']; ?> disponibles
                            </span>
                        <?php elseif ($recarga['stock'] > 0): ?>
                            <span class="stock-badge stock-limited">
                                <i class="fas fa-exclamation-triangle"></i> Solo <?php echo $recarga['stock']; ?>
                            </span>
                        <?php else: ?>
                            <span class="stock-badge stock-unavailable">
                                <i class="fas fa-times-circle"></i> Agotado
                            </span>
                        <?php endif; ?>
                    </div>

                    <form method="POST" class="mt-3">
                        <input type="hidden" name="id_recarga" value="<?php echo $recarga['id_recarga']; ?>">
                        <div class="input-group mb-3">
                            <input type="number" name="cantidad" class="form-control" value="1" min="1" 
                                   max="<?php echo $recarga['stock']; ?>" <?php echo $recarga['stock'] <= 0 ? 'disabled' : ''; ?>>
                            <button class="btn btn-success" type="submit" <?php echo $recarga['stock'] <= 0 ? 'disabled' : ''; ?>>
                                <i class="fas fa-cart-plus"></i> Agregar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endwhile; ?>
    </div>

    <!-- Paginación -->
    <?php if ($total_paginas > 1): ?>
        <nav aria-label="Page navigation" class="mb-4">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="recargas.php?page=1<?php echo !empty($queryParams) ? '&' . http_build_query($queryParams) : ''; ?>">
                            Primera
                        </a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="recargas.php?page=<?php echo $page - 1; ?><?php echo !empty($queryParams) ? '&' . http_build_query($queryParams) : ''; ?>">
                            Anterior
                        </a>
                    </li>
                <?php endif; ?>

                <?php for ($i = max(1, $page - 2); $i <= min($total_paginas, $page + 2); $i++): ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="recargas.php?page=<?php echo $i; ?><?php echo !empty($queryParams) ? '&' . http_build_query($queryParams) : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                <?php endfor; ?>

                <?php if ($page < $total_paginas): ?>
                    <li class="page-item">
                        <a class="page-link" href="recargas.php?page=<?php echo $page + 1; ?><?php echo !empty($queryParams) ? '&' . http_build_query($queryParams) : ''; ?>">
                            Siguiente
                        </a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="recargas.php?page=<?php echo $total_paginas; ?><?php echo !empty($queryParams) ? '&' . http_build_query($queryParams) : ''; ?>">
                            Última
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>

<?php else: ?>
    <div class="alert alert-info text-center py-5" role="alert">
        <i class="fas fa-info-circle fa-2x mb-3"></i>
        <h4>No hay recargas disponibles</h4>
        <p>Intenta con otros filtros o vuelve más tarde</p>
        <a href="recargas.php" class="btn btn-primary mt-2">Ver todas las recargas</a>
    </div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
