<?php
require_once __DIR__ . '/header.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id_producto'])) {
    header('Location: productos.php');
    exit;
}

$id_producto = intval($_POST['id_producto']);
$stmt = $conn->prepare("UPDATE productos SET estado = 'inactivo' WHERE id_producto = ?");
$stmt->bind_param('i', $id_producto);
$stmt->execute();

header('Location: productos.php?eliminado=1');
exit;
