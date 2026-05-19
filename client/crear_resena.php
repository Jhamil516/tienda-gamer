<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/funciones.php';

header('Content-Type: application/json');

global $conn;

// Validar que el usuario está autenticado
if (!es_autenticado()) {
    echo json_encode(['error' => 'Debes iniciar sesión para dejar una reseña']);
    exit;
}

// Validar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

// Obtener datos del formulario
$id_producto = intval($_POST['id_producto'] ?? 0);
$titulo = $_POST['titulo'] ?? '';
$descripcion = $_POST['descripcion'] ?? '';
$valoracion = floatval($_POST['valoracion'] ?? 0);
$id_venta = intval($_POST['id_venta'] ?? 0);

// Validaciones
if ($id_producto <= 0) {
    echo json_encode(['error' => 'Producto inválido']);
    exit;
}

if (empty(trim($titulo)) || strlen($titulo) < 5) {
    echo json_encode(['error' => 'El título debe tener al menos 5 caracteres']);
    exit;
}

if (empty(trim($descripcion)) || strlen($descripcion) < 10) {
    echo json_encode(['error' => 'La descripción debe tener al menos 10 caracteres']);
    exit;
}

if ($valoracion < 1 || $valoracion > 5 || !is_numeric($valoracion)) {
    echo json_encode(['error' => 'La valoración debe estar entre 1 y 5 estrellas']);
    exit;
}

// Verificar que el producto existe
$producto = obtener_producto_por_id($conn, $id_producto);
if (!$producto) {
    echo json_encode(['error' => 'El producto no existe']);
    exit;
}

// Verificar que el usuario no ha reseñado ya este producto
if (usuario_ya_resenio_producto($conn, $_SESSION['id_usuario'], $id_producto)) {
    echo json_encode(['error' => 'Ya has dejado una reseña para este producto']);
    exit;
}

// Opcionalmente validar que el usuario compró el producto
// (puedes habilitar esto según tus requerimientos)

// Crear la reseña
$resultado = crear_resena($conn, $id_producto, $_SESSION['id_usuario'], $titulo, $descripcion, $valoracion, $id_venta > 0 ? $id_venta : null);

if (isset($resultado['error'])) {
    echo json_encode(['error' => $resultado['error']]);
    exit;
}

echo json_encode([
    'exito' => true,
    'mensaje' => 'Reseña publicada correctamente.'
]);
