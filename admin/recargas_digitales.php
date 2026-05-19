<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/funciones.php';

if (!es_admin()) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

$recargas = [];
$result = $conn->query("SELECT * FROM recargas_digitales ORDER BY tipo ASC, nombre ASC");
if ($result) {
    $recargas = $result->fetch_all(MYSQLI_ASSOC);
}

require_once __DIR__ . '/admin_header.php';
admin_render_header('Recargas Digitales', 'Recargas', 'fas fa-coins');
?>
    <div class="section">
        <div class="section-title">
            <i class="fas fa-wallet"></i> Catálogo de Recargas Digitales
        </div>
        <p style="color: #cbd5e1; margin-bottom: 20px;">Revisa el inventario de recargas digitales como monedas de juegos, tarjetas de crédito virtuales y otros productos digitales.</p>
        <?php if (!empty($recargas)): ?>
            <div style="overflow-x:auto;">
                <table class="table low-stock-table">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Tipo</th>
                            <th>Precio</th>
                            <th>Stock</th>
                            <th>Estado</th>
                            <th>Creación</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recargas as $recarga): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($recarga['nombre']); ?></td>
                            <td><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $recarga['tipo']))); ?></td>
                            <td>BOB <?php echo number_format($recarga['precio'], 2, ',', '.'); ?></td>
                            <td><?php echo intval($recarga['stock']); ?></td>
                            <td>
                                <?php if ($recarga['estado'] === 'activa'): ?>
                                    <span class="badge bg-info">Activa</span>
                                <?php elseif ($recarga['estado'] === 'agotada'): ?>
                                    <span class="badge bg-danger">Agotada</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inactiva</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($recarga['fecha_creacion'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> No hay recargas digitales registradas.
            </div>
        <?php endif; ?>
    </div>
<?php admin_render_footer(); ?>