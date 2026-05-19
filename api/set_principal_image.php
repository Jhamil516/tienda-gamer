<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/funciones.php';

header('Content-Type: application/json');

if (!es_autenticado()) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$id_imagen = intval($_POST['id_imagen'] ?? 0);

if ($id_imagen <= 0) {
    echo json_encode(['error' => 'ID de imagen inválido']);
    exit;
}

// Obtener imagen
$query = "SELECT * FROM imagenes_producto WHERE id_imagen = $id_imagen";
$result = $conn->query($query);
if (!$result || $result->num_rows === 0) {
    echo json_encode(['error' => 'Imagen no encontrada']);
    exit;
}

$imagen = $result->fetch_assoc();
$id_producto = $imagen['id_producto'];

// Verificar que el usuario sea admin o empleado
if ($_SESSION['rol'] !== 'admin' && $_SESSION['rol'] !== 'empleado') {
    http_response_code(403);
    echo json_encode(['error' => 'No tienes permiso']);
    exit;
}

// Quitar principal de todas
$conn->query("UPDATE imagenes_producto SET es_principal = 0 WHERE id_producto = $id_producto");

// Establecer esta como principal
$conn->query("UPDATE imagenes_producto SET es_principal = 1 WHERE id_imagen = $id_imagen");

// Actualizar imagen principal del producto
$conn->query("UPDATE productos SET imagen_principal = '{$imagen['ruta_imagen']}' WHERE id_producto = $id_producto");

echo json_encode(['exito' => true]);
?>
