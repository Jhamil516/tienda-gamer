<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/funciones.php';

if (!es_admin()) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

$id = intval($_GET['id'] ?? 0);

if ($id > 0) {
    $conn->query("DELETE FROM promociones WHERE id_promocion = $id");
}

header('Location: promociones.php');
exit;
