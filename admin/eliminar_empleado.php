<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/funciones.php';

if (!es_admin()) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

$id = intval($_GET['id'] ?? 0);

if ($id > 0 && $id != 1) { // No permitir eliminar admin
    $conn->query("DELETE FROM empleados WHERE id_usuario = $id");
    $conn->query("DELETE FROM usuarios WHERE id_usuario = $id");
}

header('Location: empleados.php');
exit;
