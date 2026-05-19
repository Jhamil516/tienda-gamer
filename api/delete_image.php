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

// Si es la imagen principal, elegir otra como principal
if ($imagen['es_principal']) {
    $proxima = $conn->query("SELECT id_imagen FROM imagenes_producto WHERE id_producto = $id_producto AND id_imagen != $id_imagen LIMIT 1");
    if ($proxima && $proxima->num_rows > 0) {
        $proxima_img = $proxima->fetch_assoc();
        $conn->query("UPDATE imagenes_producto SET es_principal = 1 WHERE id_imagen = {$proxima_img['id_imagen']}");
        $nueva_principal = $conn->query("SELECT ruta_imagen FROM imagenes_producto WHERE id_imagen = {$proxima_img['id_imagen']}")->fetch_assoc();
        $conn->query("UPDATE productos SET imagen_principal = '{$nueva_principal['ruta_imagen']}' WHERE id_producto = $id_producto");
    }
}

// Eliminar archivo
$ruta_archivo = __DIR__ . '/../uploads/' . $imagen['ruta_imagen'];
if (file_exists($ruta_archivo)) {
    unlink($ruta_archivo);
}

// Eliminar registro
if ($conn->query("DELETE FROM imagenes_producto WHERE id_imagen = $id_imagen")) {
    echo json_encode(['exito' => true]);
} else {
    echo json_encode(['error' => 'Error al eliminar']);
}
?>
