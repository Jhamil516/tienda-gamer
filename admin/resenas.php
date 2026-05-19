<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/funciones.php';

if (!es_admin()) {
    header('Location: ../auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_resena'], $_POST['accion'])) {
    if ($_POST['accion'] === 'eliminar') {
        $resultado = eliminar_resena($conn, $_POST['id_resena']);
        if (isset($resultado['exito'])) {
            $_SESSION['mensaje_exito'] = 'Reseña eliminada correctamente';
        } else {
            $_SESSION['mensaje_error'] = $resultado['error'] ?? 'Error al eliminar reseña';
        }
        header('Location: resenas.php');
        exit;
    }
}

$query = "SELECT r.*, u.nombre, u.correo, p.nombre as nombre_producto FROM resenas r
          JOIN usuarios u ON r.id_usuario = u.id_usuario
          JOIN productos p ON r.id_producto = p.id_producto
          WHERE r.estado = 'aprobada'
          ORDER BY r.fecha_resena DESC";

$result = $conn->query($query);
$resenas = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

$query_contar = "SELECT COUNT(*) as cantidad FROM resenas WHERE estado = 'aprobada'";
$result_contar = $conn->query($query_contar);
$cantidad_resenas = 0;
if ($result_contar) {
    $row = $result_contar->fetch_assoc();
    $cantidad_resenas = intval($row['cantidad']);
}

require_once __DIR__ . '/admin_header.php';
admin_render_header('Reseñas Publicadas', 'Gestionar Reseñas', 'fas fa-star');
?>

<style>
    :root {
        --primary: #6200ff;
        --accent: #00d4ff;
        --dark-bg: #0a0a0a;
        --card-bg: #1a1a2e;
        --text-light: #e0e0e0;
        --text-muted: #b8c2d1;
    }

    .stat-card {
        background: linear-gradient(135deg, var(--card-bg) 0%, rgba(98, 0, 255, 0.1) 100%);
        border: 2px solid var(--primary);
        border-radius: 10px;
        padding: 25px;
        text-align: center;
        transition: all 0.3s;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0, 212, 255, 0.2);
        border-color: var(--accent);
    }

    .stat-card i {
        font-size: 2.5rem;
        color: var(--accent);
        margin-bottom: 10px;
    }

    .stat-card h3 {
        color: var(--accent);
        font-weight: bold;
        margin: 15px 0;
    }

    .stat-card p {
        color: var(--text-light);
        font-size: 0.9rem;
    }

    .table {
        background: #ffffff;
        border: 2px solid var(--primary);
        border-radius: 10px;
        overflow: hidden;
    }

    .table thead {
        background: rgba(98, 0, 255, 0.3);
        border-bottom: 2px solid var(--primary);
    }

    .table thead th {
        color: var(--accent);
        font-weight: bold;
        border: none;
        padding: 15px;
    }

    .table tbody td {
        color: #000000;
        border: none;
        padding: 12px 15px;
        border-bottom: 1px solid rgba(98, 0, 255, 0.12);
    }

    .table tbody td small {
        color: var(--text-muted);
    }

    .table tbody tr:hover {
        background: rgba(0, 212, 255, 0.05);
    }

    .table tbody tr:last-child td {
        border-bottom: none;
    }

    .alert {
        background: linear-gradient(135deg, var(--card-bg) 0%, rgba(0, 212, 255, 0.1) 100%);
        border: 2px solid var(--accent);
        color: var(--text-light);
        border-radius: 10px;
    }

    .alert i {
        color: var(--accent);
    }

    .btn-info {
        background: var(--accent);
        border: none;
        color: #000;
        font-weight: bold;
    }

    .btn-info:hover {
        background: #00b8cc;
        color: #000;
    }

    .btn-danger {
        background: #dc3545;
        border: none;
        font-weight: bold;
    }

    .btn-danger:hover {
        background: #c82333;
    }

    .modal-content {
        background: var(--card-bg);
        border: 2px solid var(--primary);
    }

    .modal-header {
        background: rgba(98, 0, 255, 0.3);
        border-bottom: 2px solid var(--primary);
    }

    .modal-title {
        color: var(--accent);
        font-weight: bold;
    }

    .modal-body {
        color: var(--text-light);
    }

    .modal-footer {
        border-top: 2px solid var(--primary);
    }

    .badge {
        font-size: 0.85rem;
        padding: 5px 10px;
    }

    .section-title {
        color: var(--accent);
        font-weight: bold;
        margin-bottom: 20px;
        font-size: 1.3rem;
    }
</style>

<?php if (isset($_SESSION['mensaje_exito'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i> <?php echo $_SESSION['mensaje_exito']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" style="filter: brightness(0) invert(1);"></button>
    </div>
    <?php unset($_SESSION['mensaje_exito']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['mensaje_error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle"></i> <?php echo $_SESSION['mensaje_error']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" style="filter: brightness(0) invert(1);"></button>
    </div>
    <?php unset($_SESSION['mensaje_error']); ?>
<?php endif; ?>

<!-- Estadísticas -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <i class="fas fa-star"></i>
            <h3><?php echo $cantidad_resenas; ?></h3>
            <p>Reseñas Publicadas</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <i class="fas fa-user-check"></i>
            <h3><?php echo count(array_unique(array_column($resenas, 'id_usuario'))); ?></h3>
            <p>Clientes que Reseñaron</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <i class="fas fa-info-circle"></i>
            <h3><?php echo number_format(count($resenas) > 0 ? array_sum(array_column($resenas, 'valoracion')) / count($resenas) : 0, 1); ?></h3>
            <p>Valoración Promedio</p>
        </div>
    </div>
</div>

<!-- Tabla de reseñas -->
<div class="row mt-4">
    <div class="col-md-12">
        <h4 class="section-title"><i class="fas fa-list"></i> Reseñas Publicadas</h4>

        <?php if (count($resenas) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Cliente</th>
                            <th>Título</th>
                            <th>Valoración</th>
                            <th>Fecha</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($resenas as $resena): ?>
                            <tr>
                                <td><?php echo htmlspecialchars(substr($resena['nombre_producto'], 0, 30)); ?></td>
                                <td><?php echo htmlspecialchars($resena['nombre']); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($resena['titulo']); ?></strong>
                                    <br>
                                    <small style="color: #888;"><?php echo htmlspecialchars(substr($resena['descripcion'], 0, 50)); ?>...</small>
                                </td>
                                <td>
                                    <span class="badge" style="background: linear-gradient(135deg, #FFB700 0%, #FFA500 100%); color: #000;">
                                        <?php echo str_repeat('★', intval($resena['valoracion'])); ?><?php echo str_repeat('☆', 5 - intval($resena['valoracion'])); ?>
                                    </span>
                                </td>
                                <td><?php echo formatear_fecha($resena['fecha_resena']); ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#modal<?php echo $resena['id_resena']; ?>">
                                        <i class="fas fa-eye"></i> Ver
                                    </button>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('¿Estás seguro de que deseas eliminar esta reseña?');">
                                        <input type="hidden" name="id_resena" value="<?php echo $resena['id_resena']; ?>">
                                        <input type="hidden" name="accion" value="eliminar">
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i> Eliminar
                                        </button>
                                    </form>
                                </td>
                            </tr>

                            <!-- Modal -->
                            <div class="modal fade" id="modal<?php echo $resena['id_resena']; ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title"><i class="fas fa-star"></i> <?php echo htmlspecialchars($resena['titulo']); ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter: brightness(0) invert(1);"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p><strong>📦 Producto:</strong> <?php echo htmlspecialchars($resena['nombre_producto']); ?></p>
                                            <p><strong>👤 Cliente:</strong> <?php echo htmlspecialchars($resena['nombre']); ?> (<?php echo htmlspecialchars($resena['correo']); ?>)</p>
                                            <p><strong>⭐ Valoración:</strong>
                                                <span class="badge" style="background: linear-gradient(135deg, #FFB700 0%, #FFA500 100%); color: #000;">
                                                    <?php echo str_repeat('★', intval($resena['valoracion'])); ?><?php echo str_repeat('☆', 5 - intval($resena['valoracion'])); ?>
                                                </span>
                                            </p>
                                            <p><strong>📅 Fecha:</strong> <?php echo formatear_fecha($resena['fecha_resena']); ?></p>
                                            <hr style="border-color: var(--primary);">
                                            <p><strong>💬 Comentario:</strong></p>
                                            <p style="background: rgba(0, 212, 255, 0.1); padding: 15px; border-radius: 8px; border-left: 4px solid var(--accent);">
                                                <?php echo nl2br(htmlspecialchars($resena['descripcion'])); ?>
                                            </p>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('¿Estás seguro de que deseas eliminar esta reseña?');">
                                                <input type="hidden" name="id_resena" value="<?php echo $resena['id_resena']; ?>">
                                                <input type="hidden" name="accion" value="eliminar">
                                                <button type="submit" class="btn btn-danger">
                                                    <i class="fas fa-trash"></i> Eliminar reseña
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert">
                <i class="fas fa-info-circle"></i> <strong>No hay reseñas publicadas aún.</strong> Las reseñas aparecerán aquí cuando los clientes las dejen.
            </div>
        <?php endif; ?>
    </div>
</div>

<?php admin_render_footer(); ?>
