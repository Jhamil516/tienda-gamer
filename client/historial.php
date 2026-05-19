<?php
session_start();
$_SESSION['BASE_URL'] = '/tienda-gamer';

require '../config/db.php';
require '../auth/proteger.php';
requerirCliente();

$id_usuario = $_SESSION['id_usuario'];

// Obtener ventas del cliente con paginación
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$items_por_pagina = 10;
$offset = ($page - 1) * $items_por_pagina;

// Contar total de ventas
$stmt_count = $conn->prepare("SELECT COUNT(*) as total FROM ventas WHERE id_usuario = ?");
$stmt_count->bind_param("i", $id_usuario);
$stmt_count->execute();
$resultado_count = $stmt_count->get_result();
$total_registros = $resultado_count->fetch_assoc()['total'];
$total_paginas = ceil($total_registros / $items_por_pagina);

// Obtener ventas
$stmt = $conn->prepare("SELECT v.id_venta, v.fecha_venta, v.total
                        FROM ventas v
                        WHERE v.id_usuario = ?
                        ORDER BY v.fecha_venta DESC
                        LIMIT ? OFFSET ?");
$stmt->bind_param("iii", $id_usuario, $items_por_pagina, $offset);
$stmt->execute();
$resultado = $stmt->get_result();
?>

<?php include '../includes/header.php'; ?>

<div class="container">
    <div class="row mb-4 mt-4">
        <div class="col-md-8">
            <h2>Mis Compras</h2>
        </div>
        <div class="col-md-4 text-end">
            <a href="catalogo.php" class="btn btn-primary">Seguir Comprando</a>
        </div>
    </div>

    <?php if (isset($_GET['compra_exitosa'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            Compra realizada exitosamente! Tu pedido ha sido registrado.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($resultado->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>ID Venta</th>
                        <th>Fecha</th>
                        <th>Total</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $resultado->fetch_assoc()): ?>
                        <tr>
                            <td>
                                #<?php echo $row['id_venta']; ?>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($row['fecha_venta'])); ?></td>
                            <td class="fw-bold text-success">BOB <?php echo number_format($row['total'], 2); ?></td>
                            <td>
                                <a href="detalle_venta.php?id=<?php echo $row['id_venta']; ?>"
                                   class="btn btn-sm btn-info">Ver Detalle</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        <?php if ($total_paginas > 1): ?>
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo ($page === 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=1">Inicio</a>
                    </li>

                    <li class="page-item <?php echo ($page === 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo max(1, $page - 1); ?>">Anterior</a>
                    </li>

                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_paginas, $page + 2);

                    for ($i = $start_page; $i <= $end_page; $i++):
                    ?>
                        <li class="page-item <?php echo ($i === $page) ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <li class="page-item <?php echo ($page === $total_paginas) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo min($total_paginas, $page + 1); ?>">Siguiente</a>
                    </li>

                    <li class="page-item <?php echo ($page === $total_paginas) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $total_paginas; ?>">Final</a>
                    </li>
                </ul>
            </nav>

            <p class="text-center text-muted">
                Página <?php echo $page; ?> de <?php echo $total_paginas; ?> | Total de compras: <?php echo $total_registros; ?>
            </p>
        <?php endif; ?>

    <?php else: ?>
        <div class="alert alert-info text-center" role="alert">
            <h5>No tienes compras aun</h5>
            <p>Comienza a comprar celulares en nuestro catálogo.</p>
            <a href="catalogo.php" class="btn btn-primary">Ver Catálogo</a>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
