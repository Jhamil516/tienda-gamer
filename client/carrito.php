<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../auth/proteger.php';
requerirCliente();

$id_usuario = $_SESSION['id_usuario'];
$error   = '';
$success = '';

// ── Agregar producto al carrito ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_producto'])) {
    $id_producto = intval($_POST['id_producto']);
    $cantidad    = max(1, intval($_POST['cantidad'] ?? 1));
    $codigo_cupon = trim($_POST['codigo_cupon'] ?? '');

    if ($codigo_cupon !== '') {
        $promocion = obtener_promocion_por_codigo($conn, $codigo_cupon);
        if ($promocion) {
            $validacion = validar_promocion($conn, $promocion, $id_usuario);
            if (isset($validacion['error'])) {
                $error = $validacion['error'];
                unset($_SESSION['codigo_cupon']);
            } else {
                $_SESSION['codigo_cupon'] = strtoupper(trim($codigo_cupon));
                $success = 'Cupón aplicado correctamente';
            }
        } else {
            $error = 'Cupón inválido o expirado';
            unset($_SESSION['codigo_cupon']);
        }
    }

    $resultado = agregar_al_carrito($conn, $id_usuario, $id_producto, $cantidad);

    if (isset($resultado['error'])) {
        $error = $resultado['error'];
    } elseif (!$success) {
        $success = 'Producto agregado al carrito';
    }

    header("Location: carrito.php" . ($error ? "?error=" . urlencode($error) : "?success=" . urlencode($success)));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['codigo_cupon']) && !isset($_POST['id_producto'])) {
    $codigo_cupon = trim($_POST['codigo_cupon']);
    if ($codigo_cupon === '') {
        unset($_SESSION['codigo_cupon']);
        $success = 'Cupón retirado';
    } else {
        $promocion = obtener_promocion_por_codigo($conn, $codigo_cupon);
        if ($promocion) {
            $validacion = validar_promocion($conn, $promocion, $id_usuario);
            if (isset($validacion['error'])) {
                $error = $validacion['error'];
                unset($_SESSION['codigo_cupon']);
            } else {
                $_SESSION['codigo_cupon'] = strtoupper($codigo_cupon);
                $success = 'Cupón aplicado correctamente';
            }
        } else {
            $error = 'Cupón inválido o expirado';
            unset($_SESSION['codigo_cupon']);
        }
    }

    header("Location: carrito.php" . ($error ? "?error=" . urlencode($error) : "?success=" . urlencode($success)));
    exit;
}

if (isset($_GET['quitar_cupon'])) {
    unset($_SESSION['codigo_cupon']);
    header('Location: carrito.php?success=' . urlencode('Cupón retirado'));
    exit;
}

// ── Eliminar producto ────────────────────────────────────────────────────────
if (isset($_GET['eliminar'])) {
    $id_producto = intval($_GET['eliminar']);
    eliminar_del_carrito($conn, $id_usuario, $id_producto);
    header("Location: carrito.php?success=" . urlencode('Producto eliminado del carrito'));
    exit;
}

// ── Actualizar cantidad ──────────────────────────────────────────────────────
if (isset($_GET['actualizar_cantidad'])) {
    $id_producto    = intval($_GET['actualizar_cantidad']);
    $nueva_cantidad = max(1, intval($_GET['cantidad'] ?? 1));

    $producto = obtener_producto_por_id($conn, $id_producto);

    if ($producto && $nueva_cantidad <= $producto['stock']) {
        $conn->query("UPDATE carrito SET cantidad = $nueva_cantidad WHERE id_usuario = $id_usuario AND id_producto = $id_producto");
        $success = 'Cantidad actualizada';
    } else {
        $error = 'Stock insuficiente';
    }
    header("Location: carrito.php" . ($error ? "?error=" . urlencode($error) : "?success=" . urlencode($success)));
    exit;
}

// ── Vaciar carrito ───────────────────────────────────────────────────────────
if (isset($_GET['vaciar'])) {
    limpiar_carrito($conn, $id_usuario);
    header("Location: carrito.php?success=" . urlencode('Carrito vaciado'));
    exit;
}

// ── Leer parámetros de URL ───────────────────────────────────────────────────
if (isset($_GET['error']))   $error   = $_GET['error'];
if (isset($_GET['success'])) $success = $_GET['success'];

// ── Datos del carrito ────────────────────────────────────────────────────────
$carrito  = obtener_carrito($conn, $id_usuario);
$subtotal = calcular_total_carrito($conn, $id_usuario);
$codigo_cupon = $_SESSION['codigo_cupon'] ?? '';
$descuento = $codigo_cupon ? calcular_descuento_cupon_carrito($conn, $id_usuario, $codigo_cupon) : 0;
$total    = max(0, $subtotal - $descuento);
$impuesto = round($total * 0.19, 2);
$total    = $total + $impuesto;
?>
<?php include '../includes/header.php'; ?>
<style>
    .cart-card { background: rgba(26, 26, 46, 0.9); border: 2px solid rgba(98, 0, 255, 0.4); border-radius: 15px; padding: 25px; margin-bottom: 20px; }
    .cart-item { background: rgba(15, 15, 30, 0.6); border: 1px solid rgba(98, 0, 255, 0.2); border-radius: 10px; padding: 15px; margin-bottom: 12px; display: flex; align-items: center; gap: 15px; }
    .cart-item img { width: 70px; height: 70px; object-fit: cover; border-radius: 8px; border: 1px solid rgba(98, 0, 255, 0.3); }
    .item-name { color: #e0e0e0; font-weight: bold; flex: 1; }
    .item-price { color: #00d4ff; font-weight: bold; font-size: 1.1rem; min-width: 90px; text-align: right; }
    .summary-card { background: rgba(26, 26, 46, 0.9); border: 2px solid #00d4ff; border-radius: 15px; padding: 25px; position: sticky; top: 80px; }
    .btn-checkout { background: linear-gradient(135deg, #6200ff, #9333ea); border: none; color: #fff; padding: 14px; font-weight: bold; font-size: 1rem; border-radius: 10px; width: 100%; transition: all 0.3s; }
    .btn-checkout:hover { box-shadow: 0 0 20px rgba(98, 0, 255, 0.5); transform: translateY(-2px); }
    .btn-danger-ghost { background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.5); color: #f87171; border-radius: 6px; padding: 5px 10px; font-size: 0.85rem; transition: all 0.2s; }
    .btn-danger-ghost:hover { background: rgba(239, 68, 68, 0.25); color: #fca5a5; }
    .empty-cart { text-align: center; padding: 60px 20px; }
    .alert-success-custom { background: rgba(16, 185, 129, 0.15); border: 1px solid #10b981; color: #6ee7b7; border-radius: 10px; padding: 12px 18px; margin-bottom: 15px; }
    .alert-danger-custom { background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; color: #fca5a5; border-radius: 10px; padding: 12px 18px; margin-bottom: 15px; }
</style>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><i class="fas fa-shopping-cart"></i> Mi Carrito</h2>
            <p class="text-muted">Administra tus productos, cantidades y continúa hacia el checkout.</p>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert-success-custom"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert-danger-custom"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if (empty($carrito)): ?>
        <div class="cart-card empty-cart">
            <i class="fas fa-shopping-cart" style="font-size: 4rem; color: rgba(98,0,255,0.4);"></i>
            <h4 class="mt-3">Tu carrito está vacío</h4>
            <p style="color: #aaa;">Agrega productos al carrito y revisa tu subtotal aquí.</p>
            <a href="catalogo.php" class="btn btn-primary mt-2">Ver Catálogo</a>
        </div>
    <?php else: ?>
        <div class="row">
            <div class="col-lg-8">
                <div class="cart-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 style="color: #00d4ff; margin: 0;"><i class="fas fa-list"></i> Productos (<?php echo count($carrito); ?>)</h5>
                        <a href="carrito.php?vaciar=1" class="btn-danger-ghost" onclick="return confirm('¿Vaciar todo el carrito?')">
                            <i class="fas fa-trash"></i> Vaciar
                        </a>
                    </div>

                    <?php foreach ($carrito as $item): ?>
                        <div class="cart-item">
                            <img src="<?php echo BASE_URL; ?>uploads/<?php echo htmlspecialchars($item['imagen_principal']); ?>" alt="<?php echo htmlspecialchars($item['nombre']); ?>" onerror="this.src='https://placehold.co/70x70/1a1a2e/00d4ff?text=IMG'">
                            <div class="item-name">
                                <?php echo htmlspecialchars($item['nombre']); ?>
                                <div style="font-size: 0.82rem; color: #aaa;">Precio unitario: BOB <?php echo number_format($item['precio_unitario'], 2, ',', '.'); ?></div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <a href="carrito.php?actualizar_cantidad=<?php echo $item['id_producto']; ?>&cantidad=<?php echo max(1, $item['cantidad'] - 1); ?>" class="btn btn-sm" style="background: rgba(98,0,255,0.3); color:#fff; border-radius:5px; padding:3px 9px;">−</a>
                                <span style="min-width: 30px; text-align:center; color:#fff;"><?php echo $item['cantidad']; ?></span>
                                <a href="carrito.php?actualizar_cantidad=<?php echo $item['id_producto']; ?>&cantidad=<?php echo $item['cantidad'] + 1; ?>" class="btn btn-sm" style="background: rgba(98,0,255,0.3); color:#fff; border-radius:5px; padding:3px 9px;">+</a>
                            </div>
                            <div class="item-price">BOB <?php echo number_format($item['precio_unitario'] * $item['cantidad'], 2, ',', '.'); ?></div>
                            <a href="carrito.php?eliminar=<?php echo $item['id_producto']; ?>" class="btn-danger-ghost" onclick="return confirm('¿Eliminar este producto?')">
                                <i class="fas fa-times"></i>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="summary-card">
                    <h5 style="color: #00d4ff; margin-bottom: 20px;"><i class="fas fa-receipt"></i> Resumen</h5>
                    <div class="summary-row"><span>Subtotal</span><span>BOB <?php echo number_format($subtotal, 2, ',', '.'); ?></span></div>
                    <?php if (!empty($codigo_cupon) && $descuento > 0): ?>
                        <div class="summary-row"><span>Descuento (<?php echo htmlspecialchars($codigo_cupon); ?>)</span><span>-BOB <?php echo number_format($descuento, 2, ',', '.'); ?></span></div>
                        <div class="summary-row"><span>Subtotal con descuento</span><span>BOB <?php echo number_format(max(0, $subtotal - $descuento), 2, ',', '.'); ?></span></div>
                    <?php endif; ?>
                    <div class="summary-row"><span>IVA (19%)</span><span>BOB <?php echo number_format($impuesto, 2, ',', '.'); ?></span></div>
                    <div class="summary-row"><span>Envío</span><span style="color:#10b981;">Gratis</span></div>
                    <div class="summary-total"><span>Total</span><span>BOB <?php echo number_format($total, 2, ',', '.'); ?></span></div>
                    <div class="mt-4">
                        <form method="POST" class="d-flex flex-column gap-2">
                            <input type="text" name="codigo_cupon" placeholder="Aplicar cupón" class="form-control" value="<?php echo htmlspecialchars($codigo_cupon); ?>">
                            <button type="submit" class="btn btn-outline-light">Aplicar cupón</button>
                        </form>
                        <?php if (!empty($codigo_cupon)): ?>
                            <a href="carrito.php?quitar_cupon=1" class="btn btn-link text-light mt-2 p-0">Quitar cupón</a>
                        <?php endif; ?>
                    </div>
                    <a href="checkout.php" class="btn btn-checkout mt-3"><i class="fas fa-credit-card"></i> Finalizar Compra</a>
                    <a href="catalogo.php" class="btn btn-outline-info mt-2 w-100">Seguir comprando</a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
