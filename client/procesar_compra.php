<?php
session_start();

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/funciones.php';

if (!es_autenticado()) {
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit;
}

$id_usuario = $_SESSION['id_usuario'];

// Verificar que haya productos en el carrito (DB)
$carrito = obtener_carrito($conn, $id_usuario);

if (empty($carrito)) {
    header('Location: ' . BASE_URL . 'client/carrito.php');
    exit;
}

$error = '';

$conn->begin_transaction();

try {
    // Verificar stock disponible ANTES de procesar
    foreach ($carrito as $item) {
        $id_producto = intval($item['id_producto']);

        $stmt = $conn->prepare("SELECT id_producto, nombre, stock FROM productos WHERE id_producto = ? AND estado = 'activo'");
        $stmt->bind_param("i", $id_producto);
        $stmt->execute();
        $producto_actual = $stmt->get_result()->fetch_assoc();

        if (!$producto_actual) {
            throw new Exception("El producto '" . htmlspecialchars($item['nombre']) . "' ya no está disponible.");
        }

        if ($item['cantidad'] > $producto_actual['stock']) {
            throw new Exception("Stock insuficiente para '" . htmlspecialchars($producto_actual['nombre']) . "'. Disponible: " . $producto_actual['stock'] . ", solicitado: " . $item['cantidad']);
        }
    }

    // Calcular totales
    $subtotal = calcular_total_carrito($conn, $id_usuario);
    $impuesto = round($subtotal * 0.19, 2);
    $total = $subtotal + $impuesto;
    $numero_venta = generar_numero_venta();

    // Crear venta
    $stmt = $conn->prepare("INSERT INTO ventas (id_usuario, numero_venta, total, subtotal, impuesto, estado_venta) VALUES (?, ?, ?, ?, ?, 'pendiente')");
    $stmt->bind_param("isddd", $id_usuario, $numero_venta, $total, $subtotal, $impuesto);

    if (!$stmt->execute()) {
        throw new Exception("Error al registrar la venta. Intenta de nuevo.");
    }

    $id_venta = $conn->insert_id;

    // Insertar detalles y reducir stock
    foreach ($carrito as $item) {
        $id_producto = intval($item['id_producto']);
        $cantidad    = intval($item['cantidad']);
        $precio      = floatval($item['precio_actual']);
        $subtotal_item = round($precio * $cantidad, 2);

        $stmt = $conn->prepare("INSERT INTO detalle_venta (id_venta, id_producto, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiidd", $id_venta, $id_producto, $cantidad, $precio, $subtotal_item);
        if (!$stmt->execute()) {
            throw new Exception("Error al guardar el detalle de la venta.");
        }

        // Reducir stock de producto
        $stmt = $conn->prepare("UPDATE productos SET stock = stock - ? WHERE id_producto = ?");
        $stmt->bind_param("ii", $cantidad, $id_producto);
        if (!$stmt->execute()) {
            throw new Exception("Error al actualizar el stock.");
        }

        // Notificación de stock bajo
        $stmt_check = $conn->prepare("SELECT stock, stock_minimo, nombre FROM productos WHERE id_producto = ?");
        $stmt_check->bind_param("i", $id_producto);
        $stmt_check->execute();
        $prod_check = $stmt_check->get_result()->fetch_assoc();

        if ($prod_check && $prod_check['stock'] <= $prod_check['stock_minimo']) {
            $admin = $conn->query("SELECT id_usuario FROM usuarios WHERE rol = 'admin' LIMIT 1")->fetch_assoc();
            if ($admin) {
                $msg = "Stock bajo: '{$prod_check['nombre']}' — quedan {$prod_check['stock']} unidades";
                registrar_notificacion($conn, $admin['id_usuario'], 'Stock bajo', $msg, 'advertencia');
            }
        }
    }

    // Si hay un cupón aplicado en sesión, incrementar los usos de la promoción
    $codigo_cupon_sesion = $_SESSION['codigo_cupon'] ?? '';
    if (!empty($codigo_cupon_sesion)) {
        $promocion_usada = obtener_promocion_por_codigo($conn, $codigo_cupon_sesion);
        if ($promocion_usada) {
            incrementar_usos_promocion($conn, $promocion_usada['id_promocion']);
        }
    }

    // Notificación de compra al cliente
    registrar_notificacion($conn, $id_usuario, 'Compra exitosa', "Tu orden $numero_venta fue registrada correctamente.", 'exito');

    $conn->commit();

    // Limpiar carrito en DB
    limpiar_carrito($conn, $id_usuario);

    $_SESSION['venta_exito'] = "¡Compra exitosa! N° de orden: $numero_venta";
    header('Location: ' . BASE_URL . 'client/detalle_venta.php?id=' . $id_venta);
    exit;

} catch (Exception $e) {
    $conn->rollback();
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - GAMER FRIKI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {--primary: #6200ff; --accent: #00d4ff; --dark-bg: #0a0a0a;}
        body {background: linear-gradient(135deg, var(--dark-bg) 0%, #1a0033 100%); color: #e0e0e0; min-height: 100vh; display: flex; align-items: center; justify-content: center;}
        .error-box {background: rgba(26,26,46,0.9); border: 2px solid var(--primary); border-radius: 15px; padding: 40px; max-width: 600px; width: 100%;}
    </style>
</head>
<body>
    <div class="error-box text-center">
        <h2 class="text-danger mb-3"><i class="fas fa-exclamation-circle"></i> Error al procesar la compra</h2>
        <p><?php echo htmlspecialchars($error); ?></p>
        <a href="<?php echo BASE_URL; ?>client/carrito.php" class="btn mt-3" style="background: var(--primary); color: #fff;">
            <i class="fas fa-arrow-left"></i> Volver al Carrito
        </a>
    </div>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
