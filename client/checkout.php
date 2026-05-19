<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../auth/proteger.php';
requerirCliente();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_usuario = $_SESSION['id_usuario'];
    $carrito = obtener_carrito($conn, $id_usuario);

    if (empty($carrito)) {
        header('Location: carrito.php');
        exit;
    }

    $telefono = sanitizar_sql($conn, $_POST['telefono'] ?? '');
    $direccion = sanitizar_sql($conn, $_POST['direccion'] ?? '');
    $metodo_pago = sanitizar_sql($conn, $_POST['metodo_pago'] ?? '');
    $codigo_cupon = trim($_POST['codigo_cupon'] ?? $_SESSION['codigo_cupon'] ?? '');

    if ($codigo_cupon !== '') {
        $promocion = obtener_promocion_por_codigo($conn, $codigo_cupon);
        if ($promocion) {
            $validacion = validar_promocion($conn, $promocion, $id_usuario);
            if (isset($validacion['error'])) {
                unset($_SESSION['codigo_cupon']);
                $codigo_cupon = '';
            } else {
                $_SESSION['codigo_cupon'] = strtoupper($codigo_cupon);
            }
        } else {
            unset($_SESSION['codigo_cupon']);
            $codigo_cupon = '';
        }
    }

    $subtotal = calcular_total_carrito($conn, $id_usuario);
    $descuento = $codigo_cupon ? calcular_descuento_cupon_carrito($conn, $id_usuario, $codigo_cupon) : 0;
    $total_con_descuento = max(0, $subtotal - $descuento);
    $impuesto = round($total_con_descuento * 0.19, 2);
    $total = $total_con_descuento + $impuesto;
    $numero_venta = generar_numero_venta();

    // Guardar impuesto/medio/direccion/teléfono ahora que existen las columnas en la tabla ventas
    $impuesto = round($impuesto, 2);
    $metodo_pago_db = sanitizar_sql($conn, $metodo_pago);
    $direccion_db = sanitizar_sql($conn, $direccion);
    $telefono_db = sanitizar_sql($conn, $telefono);

    $query = "INSERT INTO ventas (id_usuario, numero_venta, total, subtotal, impuesto, metodo_pago, direccion_envio, telefono, estado_venta)
              VALUES ($id_usuario, '$numero_venta', $total, $subtotal, $impuesto, '$metodo_pago_db', '$direccion_db', '$telefono_db', 'pendiente')";

    if ($conn->query($query)) {
        $id_venta = $conn->insert_id;

        foreach ($carrito as $item) {
            $id_producto = $item['id_producto'];
            $cantidad = $item['cantidad'];
            $precio = $item['precio_actual'];
            $subtotal_item = $precio * $cantidad;

            $query_detalle = "INSERT INTO detalle_venta (id_venta, id_producto, cantidad, precio_unitario, subtotal)
                             VALUES ($id_venta, $id_producto, $cantidad, $precio, $subtotal_item)";
            $conn->query($query_detalle);
            $conn->query("UPDATE productos SET stock = stock - $cantidad WHERE id_producto = $id_producto");
        }

        limpiar_carrito($conn, $id_usuario);
        header('Location: historial.php?compra_exitosa=1');
        exit;
    }
}

$carrito = obtener_carrito($conn, $_SESSION['id_usuario']);
$subtotal = calcular_total_carrito($conn, $_SESSION['id_usuario']);
$codigo_cupon = $_SESSION['codigo_cupon'] ?? '';
if ($codigo_cupon !== '') {
    $promocion = obtener_promocion_por_codigo($conn, $codigo_cupon);
    if (!$promocion) {
        unset($_SESSION['codigo_cupon']);
        $codigo_cupon = '';
    } else {
        $validacion = validar_promocion($conn, $promocion, $_SESSION['id_usuario']);
        if (isset($validacion['error'])) {
            unset($_SESSION['codigo_cupon']);
            $codigo_cupon = '';
        }
    }
}
$descuento = $codigo_cupon ? calcular_descuento_cupon_carrito($conn, $_SESSION['id_usuario'], $codigo_cupon) : 0;
$subtotal_con_descuento = max(0, $subtotal - $descuento);
$impuesto = round($subtotal_con_descuento * 0.19, 2);
$total = $subtotal_con_descuento + $impuesto;
?>

<?php include '../includes/header.php'; ?>

<style>
    .checkout-form {
        background: rgba(26, 26, 46, 0.9);
        border: 2px solid rgba(0, 212, 255, 0.2);
        border-radius: 18px;
        padding: 30px;
        margin: 30px 0;
    }
    .checkout-summary {
        background: rgba(26, 26, 46, 0.9);
        border: 2px solid rgba(0, 212, 255, 0.2);
        border-radius: 18px;
        padding: 25px;
        margin-top: 30px;
    }
    .btn-pay {
        background: linear-gradient(135deg, #6200ff, #00d4ff);
        border: none;
        color: #fff;
        padding: 15px;
        font-weight: bold;
        width: 100%;
        border-radius: 12px;
    }
    .btn-pay:hover {
        box-shadow: 0 0 20px rgba(0, 212, 255, 0.4);
    }
    .form-label {
        color: #e0e0e0 !important;
        font-weight: 500;
    }
    .form-control, .form-select {
        background: rgba(71, 85, 105, 0.5) !important;
        border: 1px solid rgba(0, 212, 255, 0.3) !important;
        color: #e0e0e0 !important;
    }
    .form-control:focus, .form-select:focus {
        background: rgba(71, 85, 105, 0.7) !important;
        border-color: #00d4ff !important;
        color: #fff !important;
        box-shadow: 0 0 10px rgba(0, 212, 255, 0.2) !important;
    }
    .form-control::placeholder {
        color: rgba(224, 224, 224, 0.6) !important;
    }
</style>

<div class="row mb-4 mt-4">
    <div class="col-md-8">
        <h2>Checkout</h2>
        <p class="text-muted">Completa tus datos de envío y confirma tu pedido.</p>
    </div>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="checkout-form">
            <h4 class="mb-3">Información de Envío</h4>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Teléfono</label>
                    <input type="tel" class="form-control" name="telefono" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Dirección de envío</label>
                    <textarea class="form-control" name="direccion" rows="3" required></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Método de pago</label>
                    <select class="form-select" name="metodo_pago" required>
                        <option value="">Seleccionar...</option>
                        <option value="transferencia">Transferencia</option>
                        <option value="efectivo">Efectivo contra entrega</option>
                        <option value="tarjeta">Tarjeta Crédito/Débito</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Código de cupón</label>
                    <input type="text" class="form-control" name="codigo_cupon" value="<?php echo htmlspecialchars($codigo_cupon); ?>" placeholder="Código de descuento">
                </div>
                <button type="submit" class="btn btn-pay">Confirmar Compra</button>
            </form>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="checkout-summary">
            <h4 class="mb-3">Resumen de tu pedido</h4>
            <?php if (empty($carrito)): ?>
                <p class="text-muted">Tu carrito está vacío. Añade productos antes de finalizar la compra.</p>
                <a href="catalogo.php" class="btn btn-outline-info">Ver Catálogo</a>
            <?php else: ?>
                <?php foreach ($carrito as $item): ?>
                    <div class="d-flex justify-content-between mb-2">
                        <span><?php echo htmlspecialchars($item['nombre']); ?> x<?php echo $item['cantidad']; ?></span>
                        <span>BOB <?php echo number_format($item['precio_actual'] * $item['cantidad'], 2, ',', '.'); ?></span>
                    </div>
                <?php endforeach; ?>
                <hr style="border-color: rgba(255,255,255,0.15);">
                <div class="d-flex justify-content-between mb-2"><span>Subtotal</span><strong>BOB <?php echo number_format($subtotal, 2, ',', '.'); ?></strong></div>
                <?php if (!empty($codigo_cupon) && $descuento > 0): ?>
                    <div class="d-flex justify-content-between mb-2"><span>Descuento (<?php echo htmlspecialchars($codigo_cupon); ?>)</span><strong>-BOB <?php echo number_format($descuento, 2, ',', '.'); ?></strong></div>
                    <div class="d-flex justify-content-between mb-2"><span>Subtotal con descuento</span><strong>BOB <?php echo number_format($subtotal_con_descuento, 2, ',', '.'); ?></strong></div>
                <?php endif; ?>
                <div class="d-flex justify-content-between mb-2"><span>IVA (19%)</span><strong>BOB <?php echo number_format($impuesto, 2, ',', '.'); ?></strong></div>
                <div class="d-flex justify-content-between"><span>Total</span><strong>BOB <?php echo number_format($total, 2, ',', '.'); ?></strong></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
