<?php
session_start();
require '../config/db.php';
require '../auth/proteger.php';
require '../auth/notificaciones.php';
requerirAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    if (marcarNotificacionComoLeida($conn, $id)) {
        echo json_encode(['success' => true]);
    }
}
?>
