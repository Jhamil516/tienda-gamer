<?php
session_start();
$_SESSION['BASE_URL'] = '/tienda-gamer';

require '../config/db.php';
require '../auth/proteger.php';
require '../auth/notificaciones.php';
requerirAdmin();

$notificaciones = obtenerNotificacionesAdmin($conn, false);

if (empty($notificaciones)): ?>
    <div class="text-center py-4">
        <i class="bi bi-inbox" style="font-size: 3rem; color: #bdc3c7;"></i>
        <p class="text-muted mt-3">No hay notificaciones</p>
    </div>
<?php else: ?>
    <div class="notification-list">
        <?php foreach ($notificaciones as $notif): ?>
            <div class="notification-item <?php echo !$notif['leida'] ? 'unread' : ''; ?>">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="d-flex gap-3 flex-grow-1">
                        <i class="bi <?php echo obtenerIconoNotificacion($notif['tipo']); ?>" style="font-size: 1.3rem; color: #3498db;"></i>
                        <div>
                            <h6 class="mb-1">
                                <?php echo htmlspecialchars($notif['marca'] . ' ' . $notif['modelo']); ?>
                            </h6>
                            <p class="mb-1 small"><?php echo htmlspecialchars($notif['mensaje']); ?></p>
                            <small class="text-muted">
                                <i class="bi bi-clock"></i>
                                <?php echo date('d/m/Y H:i', strtotime($notif['fecha'])); ?>
                            </small>
                        </div>
                    </div>
                    <?php if (!$notif['leida']): ?>
                        <button class="btn btn-sm btn-link" onclick="marcarComoLeida(<?php echo $notif['id']; ?>)">
                            <i class="bi bi-check-circle"></i>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="mt-3 text-center">
        <button class="btn btn-sm btn-outline-secondary" onclick="marcarTodasComoLeidas()">
            <i class="bi bi-check-all"></i> Marcar todas como leídas
        </button>
    </div>
<?php endif; ?>

<script>
function marcarComoLeida(id) {
    fetch('<?php echo $_SESSION['BASE_URL']; ?>/admin/marcar_notificacion_leida.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'id=' + id
    }).then(() => {
        document.querySelector('#notificacionesModal').click();
        location.reload();
    });
}

function marcarTodasComoLeidas() {
    fetch('<?php echo $_SESSION['BASE_URL']; ?>/admin/marcar_todas_leidas.php', {
        method: 'POST'
    }).then(() => {
        location.reload();
    });
}
</script>
