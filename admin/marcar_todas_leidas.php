<?php
session_start();
require '../config/db.php';
require '../auth/proteger.php';
require '../auth/notificaciones.php';
requerirAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (marcarTodasNotificacionesComoLeidas($conn)) {
        echo json_encode(['success' => true]);
    }
}
?>
