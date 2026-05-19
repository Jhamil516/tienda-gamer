<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/funciones.php';

if (!es_admin()) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

$promociones = [];
$result = $conn->query("SELECT * FROM promociones ORDER BY fecha_creacion DESC");
if ($result) {
    $promociones = $result->fetch_all(MYSQLI_ASSOC);
}

require_once __DIR__ . '/admin_header.php';
admin_render_header('Gestión de Promociones', 'Promociones', 'fas fa-tag');
?>

<style>
    .section {
        background: rgba(26, 26, 46, 0.95);
        border: 1px solid rgba(98, 0, 255, 0.35);
        border-radius: 16px;
        padding: 25px;
    }

    .section-title {
        color: #00d4ff;
        font-size: 1.3rem;
        font-weight: bold;
        margin-bottom: 20px;
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

    .btn-group { display: flex; gap: 5px; }
</style>

<div class="section">
    <div class="section-title">
        <i class="fas fa-list"></i> Promociones Activas
    </div>

    <div style="margin-bottom: 20px;">
        <a href="nueva_promocion.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nueva Promoción
        </a>
    </div>

    <?php if (!empty($promociones)): ?>
    <div style="overflow-x: auto;">
        <table class="table">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Tipo</th>
                    <th>Valor</th>
                    <th>Inicio</th>
                    <th>Fin</th>
                    <th>Estado</th>
                    <th>Usos</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($promociones as $promo): ?>
                <tr>
                    <td><?php echo htmlspecialchars($promo['nombre_promocion']); ?></td>
                    <td><span class="badge bg-info"><?php echo htmlspecialchars($promo['tipo']); ?></span></td>
                    <td>
                        <?php if ($promo['tipo'] === 'porcentaje'): ?>
                            <?php echo $promo['valor']; ?>%
                        <?php else: ?>
                            BOB <?php echo number_format($promo['valor'], 2); ?>
                        <?php endif; ?>
                    </td>
                    <td><?php echo formatear_fecha($promo['fecha_inicio']); ?></td>
                    <td><?php echo formatear_fecha($promo['fecha_fin']); ?></td>
                    <td>
                        <?php if ($promo['activa']): ?>
                            <span class="badge bg-success">Activa</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Inactiva</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $promo['usos_actuales']; ?> / <?php echo $promo['usos_limites'] ?? '∞'; ?></td>
                    <td>
                        <div class="btn-group">
                            <a href="editar_promocion.php?id=<?php echo intval($promo['id_promocion']); ?>" class="btn btn-sm btn-warning">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="eliminar_promocion.php?id=<?php echo intval($promo['id_promocion']); ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar esta promoción?');">
                                <i class="fas fa-trash-alt"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> No hay promociones creadas. <a href="nueva_promocion.php">Crear una nueva</a>
    </div>
    <?php endif; ?>
</div>

<?php admin_render_footer(); ?>
