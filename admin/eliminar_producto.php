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
    // Obtener todas las imágenes del producto
    $query = "SELECT ruta_imagen FROM imagenes_producto WHERE id_producto = $id";
    $result = $conn->query($query);
    
    if ($result) {
        // Eliminar archivos físicos
        while ($img = $result->fetch_assoc()) {
            $ruta_archivo = __DIR__ . '/../uploads/' . $img['ruta_imagen'];
            if (file_exists($ruta_archivo)) {
                unlink($ruta_archivo);
            }
        }
    }
    
    // Eliminar de base de datos
    $conn->query("DELETE FROM imagenes_producto WHERE id_producto = $id");
    $conn->query("DELETE FROM productos WHERE id_producto = $id");
}

header('Location: productos.php');
exit;

