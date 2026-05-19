<?php
require_once __DIR__ . '/../config/db.php';

// ==================== VALIDATIONS ====================

function validar_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validar_contraseña($contraseña) {
    return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $contraseña);
}

function sanitizar_entrada($dato) {
    return htmlspecialchars(trim($dato), ENT_QUOTES, 'UTF-8');
}

function sanitizar_sql($conexion, $dato) {
    return $conexion->real_escape_string(trim($dato));
}

// ==================== AUTHENTICATION ====================

function registrar_usuario($conexion, $nombre, $correo, $contraseña, $habilitar_2fa = 0) {
    if (!validar_email($correo)) {
        return ['error' => 'Email inválido'];
    }

    if (!validar_contraseña($contraseña)) {
        return ['error' => 'Contraseña débil. Mínimo 8 caracteres, mayúscula, minúscula y número'];
    }

    $correo_sanitizado = sanitizar_sql($conexion, $correo);
    $result = $conexion->query("SELECT id_usuario FROM usuarios WHERE correo = '$correo_sanitizado'");

    if ($result && $result->num_rows > 0) {
        return ['error' => 'El correo ya está registrado'];
    }

    $contraseña_hash = password_hash($contraseña, PASSWORD_BCRYPT, ['cost' => HASH_COST]);
    $nombre_sanitizado = sanitizar_sql($conexion, $nombre);
    $habilitar_2fa = $habilitar_2fa ? 1 : 0;
    $query = "INSERT INTO usuarios (nombre, correo, contraseña, rol, estado_2fa) VALUES ('$nombre_sanitizado', '$correo_sanitizado', '$contraseña_hash', 'cliente', $habilitar_2fa)";

    if ($conexion->query($query)) {
        return ['exito' => true, 'id' => $conexion->insert_id];
    } else {
        return ['error' => 'Error al registrar: ' . $conexion->error];
    }
}

function verificar_login($conexion, $correo, $contraseña) {
    $correo_sanitizado = sanitizar_sql($conexion, $correo);
    $query = "SELECT id_usuario, nombre, correo, rol, contraseña FROM usuarios WHERE correo = '$correo_sanitizado' AND activo = 1";
    $result = $conexion->query($query);

    if ($result->num_rows === 0) {
        return ['error' => 'Correo o contraseña incorrectos'];
    }

    $usuario = $result->fetch_assoc();

    if (password_verify($contraseña, $usuario['contraseña'])) {
        return ['exito' => true, 'usuario' => $usuario];
    } else {
        return ['error' => 'Correo o contraseña incorrectos'];
    }
}

function es_2fa_habilitado($conexion, $id_usuario) {
    $id_usuario = intval($id_usuario);
    $query = "SELECT estado_2fa FROM usuarios WHERE id_usuario = $id_usuario LIMIT 1";
    $result = $conexion->query($query);
    return $result && $result->num_rows > 0 && $result->fetch_assoc()['estado_2fa'] == 1;
}

function generar_codigo_2fa() {
    return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

function enviar_codigo_2fa($correo, $nombre, $codigo) {
    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = MAIL_HOST;
        $mail->SMTPAuth = MAIL_SMTP_AUTH;
        $mail->Username = MAIL_USERNAME;
        $mail->Password = MAIL_PASSWORD;
        $mail->SMTPSecure = MAIL_ENCRYPTION;
        $mail->Port = MAIL_PORT;
        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($correo, $nombre);
        $mail->isHTML(true);
        $mail->Subject = 'Código de verificación 2FA';
        $mail->Body = "<p>Hola <strong>" . htmlspecialchars($nombre) . "</strong>,</p>" .
                      "<p>Tu código de verificación es: <strong>$codigo</strong></p>" .
                      "<p>Ingresa este código en el formulario para completar tu inicio de sesión.</p>";
        $mail->AltBody = "Hola $nombre, tu código de verificación es: $codigo";
        $mail->send();
        return true;
    } catch (Exception $e) {
        return 'Error SMTP: ' . $mail->ErrorInfo;
    }
}

function guardar_codigo_2fa($conexion, $id_usuario, $codigo) {
    $codigo_sanitizado = sanitizar_sql($conexion, $codigo);
    $query = "UPDATE usuarios SET codigo_2fa = '$codigo_sanitizado', estado_2fa = 1 WHERE id_usuario = $id_usuario";
    return $conexion->query($query);
}

function verificar_codigo_2fa($conexion, $id_usuario, $codigo) {
    $codigo_sanitizado = sanitizar_sql($conexion, $codigo);
    $query = "SELECT codigo_2fa FROM usuarios WHERE id_usuario = $id_usuario";
    $result = $conexion->query($query);

    if ($result->num_rows === 0) {
        return false;
    }

    $usuario = $result->fetch_assoc();
    return $usuario['codigo_2fa'] === $codigo_sanitizado;
}

function limpiar_2fa($conexion, $id_usuario) {
    $query = "UPDATE usuarios SET codigo_2fa = NULL WHERE id_usuario = $id_usuario";
    return $conexion->query($query);
}

// ==================== SESIONES ====================

function iniciar_sesion_usuario($id_usuario, $nombre, $correo, $rol) {
    $_SESSION['id_usuario'] = $id_usuario;
    $_SESSION['nombre'] = $nombre;
    $_SESSION['correo'] = $correo;
    $_SESSION['rol'] = $rol;
    $_SESSION['ultimo_acceso'] = time();
}

function cerrar_sesion() {
    session_destroy();
    unset($_SESSION);
}

function es_autenticado() {
    return isset($_SESSION['id_usuario']) && !empty($_SESSION['id_usuario']);
}

function es_admin() {
    return isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin';
}

function es_empleado() {
    return isset($_SESSION['rol']) && in_array($_SESSION['rol'], ['admin', 'empleado']);
}

// ==================== PRODUCTOS ====================

function obtener_productos($conexion, $filtros = []) {
    $query = "SELECT p.*, c.nombre_categoria FROM productos p JOIN categorias c ON p.id_categoria = c.id_categoria WHERE p.estado = 'activo'";

    if (isset($filtros['categoria']) && !empty($filtros['categoria'])) {
        $categoria = intval($filtros['categoria']);
        $query .= " AND p.id_categoria = $categoria";
    }

    if (isset($filtros['precio_min']) && isset($filtros['precio_max'])) {
        $min = floatval($filtros['precio_min']);
        $max = floatval($filtros['precio_max']);
        $query .= " AND p.precio_actual BETWEEN $min AND $max";
    }

    if (isset($filtros['busqueda']) && !empty($filtros['busqueda'])) {
        $busqueda = sanitizar_sql($conexion, $filtros['busqueda']);
        $query .= " AND (p.nombre LIKE '%$busqueda%' OR p.marca LIKE '%$busqueda%')";
    }

    if (isset($filtros['orden'])) {
        switch ($filtros['orden']) {
            case 'precio_asc':
                $query .= " ORDER BY p.precio_actual ASC";
                break;
            case 'precio_desc':
                $query .= " ORDER BY p.precio_actual DESC";
                break;
            case 'nuevo':
                $query .= " ORDER BY p.fecha_creacion DESC";
                break;
            default:
                $query .= " ORDER BY p.fecha_creacion DESC";
        }
    } else {
        $query .= " ORDER BY p.fecha_creacion DESC";
    }

    $result = $conexion->query($query);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function obtener_producto_por_id($conexion, $id_producto) {
    $id_producto = intval($id_producto);
    $query = "SELECT p.*, c.nombre_categoria FROM productos p JOIN categorias c ON p.id_categoria = c.id_categoria WHERE p.id_producto = $id_producto AND p.estado = 'activo'";
    $result = $conexion->query($query);
    return $result ? $result->fetch_assoc() : null;
}

function inicializar_promociones($conexion) {
    // La tabla promociones ya debe existir en la base de datos
    return true;
}

function obtener_promociones_activas($conexion, $id_producto = null, $id_categoria = null, $total_compra = null) {
    $hoy = date('Y-m-d');
    $sql = "SELECT * FROM promociones WHERE activa = 1 AND fecha_inicio <= '$hoy' AND fecha_fin >= '$hoy'";

    if ($id_producto !== null) {
        $id_producto = intval($id_producto);
        $sql .= " AND (es_global = 1 OR id_producto_aplicable = $id_producto)";
    }

    if ($id_categoria !== null) {
        $id_categoria = intval($id_categoria);
        $sql .= " AND (es_global = 1 OR id_categoria_aplicable = $id_categoria)";
    }

    if ($total_compra !== null) {
        $total_compra = floatval($total_compra);
        $sql .= " AND (cantidad_minima_compra IS NULL OR cantidad_minima_compra <= $total_compra)";
    }

    $sql .= " ORDER BY valor DESC";
    $result = $conexion->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function obtener_promocion_por_codigo($conexion, $codigo) {
    $codigo_sanitizado = sanitizar_sql($conexion, strtoupper(trim($codigo)));
    $hoy = date('Y-m-d');
    $sql = "SELECT * FROM promociones WHERE UPPER(codigo_cupon) = '$codigo_sanitizado' AND activa = 1 AND fecha_inicio <= '$hoy' AND fecha_fin >= '$hoy' LIMIT 1";
    $result = $conexion->query($sql);
    return $result ? $result->fetch_assoc() : null;
}

function obtener_promocion_por_id($conexion, $id_promocion) {
    $id_promocion = intval($id_promocion);
    $query = "SELECT * FROM promociones WHERE id_promocion = $id_promocion LIMIT 1";
    $result = $conexion->query($query);
    return $result ? $result->fetch_assoc() : null;
}

function validar_promocion($conexion, $promocion, $id_usuario = null) {
    if ($promocion['usos_limites'] !== null && $promocion['usos_actuales'] >= $promocion['usos_limites']) {
        return ['error' => 'Promoción agotada'];
    }
    return ['exito' => true];
}

function calcular_precio_con_promocion($precio, $promocion) {
    $precio = floatval($precio);
    $valor = floatval($promocion['valor'] ?? 0);

    if ($promocion['tipo'] === 'cantidad_fija') {
        return max(0, $precio - $valor);
    } elseif ($promocion['tipo'] === 'porcentaje') {
        return max(0, $precio * (1 - ($valor / 100)));
    }

    return $precio;
}

function obtener_mejor_precio_promocional($precio, $promociones) {
    $mejor = floatval($precio);
    foreach ($promociones as $promo) {
        $precio_con_promocion = calcular_precio_con_promocion($precio, $promo);
        if ($precio_con_promocion < $mejor) {
            $mejor = $precio_con_promocion;
        }
    }
    return $mejor;
}

function incrementar_usos_promocion($conexion, $id_promocion) {
    $id_promocion = intval($id_promocion);
    $sql = "UPDATE promociones SET usos_actuales = usos_actuales + 1 WHERE id_promocion = $id_promocion";
    return $conexion->query($sql);
}

function obtener_imagenes_producto($conexion, $id_producto) {
    $id_producto = intval($id_producto);
    $query = "SELECT * FROM imagenes_producto WHERE id_producto = $id_producto ORDER BY orden ASC";
    $result = $conexion->query($query);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

// ==================== CARRITO ====================

function agregar_al_carrito($conexion, $id_usuario, $id_producto, $cantidad = 1) {
    $id_usuario = intval($id_usuario);
    $id_producto = intval($id_producto);
    $cantidad = intval($cantidad);

    $producto = obtener_producto_por_id($conexion, $id_producto);
    if (!$producto || $producto['stock'] < $cantidad) {
        return ['error' => 'Stock insuficiente'];
    }

    $query = "SELECT id_carrito, cantidad FROM carrito WHERE id_usuario = $id_usuario AND id_producto = $id_producto";
    $result = $conexion->query($query);

    if ($result->num_rows > 0) {
        $item = $result->fetch_assoc();
        $nueva_cantidad = $item['cantidad'] + $cantidad;
        $query = "UPDATE carrito SET cantidad = $nueva_cantidad WHERE id_usuario = $id_usuario AND id_producto = $id_producto";
    } else {
        $query = "INSERT INTO carrito (id_usuario, id_producto, cantidad, precio_unitario) VALUES ($id_usuario, $id_producto, $cantidad, {$producto['precio_actual']})";
    }

    return $conexion->query($query) ? ['exito' => true] : ['error' => $conexion->error];
}

function obtener_carrito($conexion, $id_usuario) {
    $id_usuario = intval($id_usuario);

    $query = "SELECT 'producto' as tipo, c.id_carrito, c.id_producto, c.cantidad, c.precio_unitario,
                     p.nombre, p.imagen_principal, p.precio_actual, p.id_categoria
              FROM carrito c
              JOIN productos p ON c.id_producto = p.id_producto
              WHERE c.id_usuario = $id_usuario
              ORDER BY c.id_carrito DESC";

    $result = $conexion->query($query);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function eliminar_del_carrito($conexion, $id_usuario, $id_producto) {
    $id_usuario = intval($id_usuario);
    $id_producto = intval($id_producto);
    $query = "DELETE FROM carrito WHERE id_usuario = $id_usuario AND id_producto = $id_producto";
    return $conexion->query($query);
}

function limpiar_carrito($conexion, $id_usuario) {
    $id_usuario = intval($id_usuario);
    $query = "DELETE FROM carrito WHERE id_usuario = $id_usuario";
    return $conexion->query($query);
}

function calcular_total_carrito($conexion, $id_usuario) {
    $id_usuario = intval($id_usuario);

    $query = "SELECT SUM(c.cantidad * c.precio_unitario) as subtotal
              FROM carrito c
              JOIN productos p ON c.id_producto = p.id_producto
              WHERE c.id_usuario = $id_usuario";
    $result = $conexion->query($query);
    $subtotal = $result ? ($result->fetch_assoc()['subtotal'] ?? 0) : 0;

    return floatval($subtotal);
}

function calcular_descuento_cupon_carrito($conexion, $id_usuario, $codigo_cupon) {
    $codigo_cupon = trim($codigo_cupon);
    if ($codigo_cupon === '') {
        return 0;
    }

    $promocion = obtener_promocion_por_codigo($conexion, $codigo_cupon);
    if (!$promocion) {
        return 0;
    }

    $validacion = validar_promocion($conexion, $promocion, $id_usuario);
    if (isset($validacion['error'])) {
        return 0;
    }

    $carrito = obtener_carrito($conexion, $id_usuario);
    if (empty($carrito)) {
        return 0;
    }

    $subtotal = 0;
    $subtotal_aplicable = 0;

    foreach ($carrito as $item) {
        $line_total = $item['precio_actual'] * $item['cantidad'];
        $subtotal += $line_total;

        if ($promocion['es_global'] == 1) {
            $subtotal_aplicable += $line_total;
        } elseif (!empty($promocion['id_producto_aplicable']) && intval($item['id_producto']) === intval($promocion['id_producto_aplicable'])) {
            $subtotal_aplicable += $line_total;
        } elseif (!empty($promocion['id_categoria_aplicable']) && intval($item['id_categoria']) === intval($promocion['id_categoria_aplicable'])) {
            $subtotal_aplicable += $line_total;
        }
    }

    if ($promocion['cantidad_minima_compra'] !== null && $promocion['cantidad_minima_compra'] !== '' && $subtotal < floatval($promocion['cantidad_minima_compra'])) {
        return 0;
    }

    if ($subtotal_aplicable <= 0) {
        return 0;
    }

    if ($promocion['tipo'] === 'porcentaje') {
        return round($subtotal_aplicable * floatval($promocion['valor']) / 100, 2);
    }

    if ($promocion['tipo'] === 'cantidad_fija') {
        return min(round(floatval($promocion['valor']), 2), $subtotal_aplicable);
    }

    return 0;
}

function calcular_total_carrito_con_cupon($conexion, $id_usuario, $codigo_cupon) {
    $subtotal = calcular_total_carrito($conexion, $id_usuario);
    $descuento = calcular_descuento_cupon_carrito($conexion, $id_usuario, $codigo_cupon);
    return max(0, $subtotal - $descuento);
}

// ==================== FAVORITOS ====================

function agregar_favorito($conexion, $id_usuario, $id_producto) {
    $id_usuario = intval($id_usuario);
    $id_producto = intval($id_producto);
    $query = "INSERT IGNORE INTO favoritos (id_usuario, id_producto) VALUES ($id_usuario, $id_producto)";
    return $conexion->query($query);
}

function eliminar_favorito($conexion, $id_usuario, $id_producto) {
    $id_usuario = intval($id_usuario);
    $id_producto = intval($id_producto);
    $query = "DELETE FROM favoritos WHERE id_usuario = $id_usuario AND id_producto = $id_producto";
    return $conexion->query($query);
}

function obtener_favoritos($conexion, $id_usuario) {
    $id_usuario = intval($id_usuario);
    $query = "SELECT p.* FROM productos p JOIN favoritos f ON p.id_producto = f.id_producto WHERE f.id_usuario = $id_usuario AND p.estado = 'activo'";
    $result = $conexion->query($query);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function es_favorito($conexion, $id_usuario, $id_producto) {
    $id_usuario = intval($id_usuario);
    $id_producto = intval($id_producto);
    $query = "SELECT id_favorito FROM favoritos WHERE id_usuario = $id_usuario AND id_producto = $id_producto";
    $result = $conexion->query($query);
    return $result->num_rows > 0;
}

// ==================== UTILIDADES ====================

function formatear_precio($precio) {
    return number_format($precio, 2, ',', '.');
}

function generar_numero_venta() {
    return 'VTA-' . date('YmdHis') . '-' . rand(1000, 9999);
}

function obtener_categorias($conexion) {
    $query = "SELECT * FROM categorias WHERE activa = 1 ORDER BY orden ASC";
    $result = $conexion->query($query);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function registrar_notificacion($conexion, $id_usuario, $titulo, $mensaje, $tipo = 'info') {
    $id_usuario = intval($id_usuario);
    $titulo = sanitizar_sql($conexion, $titulo);
    $mensaje = sanitizar_sql($conexion, $mensaje);
    $query = "INSERT INTO notificaciones (id_usuario, titulo, mensaje, tipo) VALUES ($id_usuario, '$titulo', '$mensaje', '$tipo')";
    return $conexion->query($query);
}

// ==================== ESTADÍSTICAS ADMIN ====================

function obtener_estadisticas_admin($conexion) {
    $stats = [
        'total_ventas' => 0,
        'total_usuarios' => 0,
        'total_productos' => 0,
        'ganancias_totales' => 0,
        'productos_vendidos' => 0
    ];

    // Total de ventas
    $result = $conexion->query("SELECT COUNT(*) as total FROM ventas");
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['total_ventas'] = $row['total'];
    }

    // Total de usuarios (clientes)
    $result = $conexion->query("SELECT COUNT(*) as total FROM usuarios WHERE rol = 'cliente'");
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['total_usuarios'] = $row['total'];
    }

    // Total de productos
    $result = $conexion->query("SELECT COUNT(*) as total FROM productos WHERE estado = 'activo'");
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['total_productos'] = $row['total'];
    }

    // Ganancias totales
    $result = $conexion->query("SELECT SUM(total) as total FROM ventas WHERE estado_venta IN ('confirmada', 'enviada', 'entregada')");
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['ganancias_totales'] = floatval($row['total'] ?? 0);
    }

    // Total de productos vendidos
    $result = $conexion->query("SELECT SUM(cantidad) as total FROM detalle_venta");
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['productos_vendidos'] = intval($row['total'] ?? 0);
    }

    return $stats;
}

// ==================== VENTAS ====================

function obtener_ventas_recientes($conexion, $limite = 5) {
    $query = "SELECT v.id_venta, v.numero_venta, v.fecha_venta, v.total, v.estado_venta, u.nombre
              FROM ventas v
              JOIN usuarios u ON v.id_usuario = u.id_usuario
              ORDER BY v.fecha_venta DESC
              LIMIT $limite";
    $result = $conexion->query($query);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function obtener_productos_stock_bajo($conexion, $limite = 5) {
    $query = "SELECT id_producto, nombre, stock, stock_minimo
              FROM productos
              WHERE stock <= stock_minimo AND estado = 'activo'
              ORDER BY stock ASC
              LIMIT $limite";
    $result = $conexion->query($query);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function obtener_productos_mas_vendidos($conexion, $limite = 5) {
    $query = "SELECT p.id_producto, p.nombre, p.marca, SUM(dv.cantidad) as total_vendido
              FROM productos p
              JOIN detalle_venta dv ON p.id_producto = dv.id_producto
              GROUP BY p.id_producto
              ORDER BY total_vendido DESC
              LIMIT $limite";
    $result = $conexion->query($query);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function formatear_fecha($fecha) {
    return date('d/m/Y H:i', strtotime($fecha));
}

// ==================== RESEÑAS ====================

function crear_resena($conexion, $id_producto, $id_usuario, $titulo, $descripcion, $valoracion, $id_venta = null) {
    $id_producto = intval($id_producto);
    $id_usuario = intval($id_usuario);
    $titulo = sanitizar_sql($conexion, $titulo);
    $descripcion = sanitizar_sql($conexion, $descripcion);
    $valoracion = floatval($valoracion);
    $id_venta = $id_venta ? intval($id_venta) : 'NULL';

    // Validar que el producto exista
    $producto = obtener_producto_por_id($conexion, $id_producto);
    if (!$producto) {
        return ['error' => 'Producto no encontrado'];
    }

    // Validar rango de valoración
    if ($valoracion < 0 || $valoracion > 5) {
        return ['error' => 'La valoración debe estar entre 0 y 5'];
    }

    // Validar que el usuario exista
    $query = "SELECT id_usuario FROM usuarios WHERE id_usuario = $id_usuario LIMIT 1";
    $result = $conexion->query($query);
    if ($result->num_rows === 0) {
        return ['error' => 'Usuario no encontrado'];
    }

    // Crear la reseña con estado aprobada
    $query = "INSERT INTO resenas (id_producto, id_usuario, titulo, descripcion, valoracion, id_venta, estado, fecha_resena)
              VALUES ($id_producto, $id_usuario, '$titulo', '$descripcion', $valoracion, $id_venta, 'aprobada', NOW())";
    
    if ($conexion->query($query)) {
        $id_resena = $conexion->insert_id;
        return ['exito' => true, 'id_resena' => $id_resena];
    } else {
        return ['error' => $conexion->error];
    }
}

// ==================== RESEÑAS ====================

function obtener_resenas_producto($conexion, $id_producto, $solo_aprobadas = true) {
    $id_producto = intval($id_producto);
    
    $query = "SELECT r.*, u.nombre FROM resenas r 
              JOIN usuarios u ON r.id_usuario = u.id_usuario 
              WHERE r.id_producto = $id_producto";
    
    if ($solo_aprobadas) {
        $query .= " AND r.estado = 'aprobada'";
    }
    
    $query .= " ORDER BY r.fecha_resena DESC";
    
    $result = $conexion->query($query);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function obtener_resena_por_id($conexion, $id_resena) {
    $id_resena = intval($id_resena);
    $query = "SELECT r.*, u.nombre FROM resenas r 
              JOIN usuarios u ON r.id_usuario = u.id_usuario 
              WHERE r.id_resena = $id_resena LIMIT 1";
    $result = $conexion->query($query);
    return $result ? $result->fetch_assoc() : null;
}

function actualizar_estado_resena($conexion, $id_resena, $estado) {
    $id_resena = intval($id_resena);
    $estado = sanitizar_sql($conexion, strtolower($estado));
    
    // Validar que el estado sea válido (pendiente, aprobada, rechazada)
    if (!in_array($estado, ['pendiente', 'aprobada', 'rechazada'])) {
        return ['error' => 'Estado inválido'];
    }
    
    $query = "UPDATE resenas SET estado = '$estado' WHERE id_resena = $id_resena";
    
    if ($conexion->query($query)) {
        // Si se aprobó, recalcular valoración del producto
        if ($estado === 'aprobada') {
            $resena = obtener_resena_por_id($conexion, $id_resena);
            if ($resena) {
                actualizar_valoracion_producto($conexion, $resena['id_producto']);
            }
        }
        return ['exito' => true];
    } else {
        return ['error' => $conexion->error];
    }
}

function actualizar_valoracion_producto($conexion, $id_producto) {
    $id_producto = intval($id_producto);
    
    // Calcular promedio y cantidad de reseñas aprobadas
    $query = "SELECT AVG(valoracion) as promedio, COUNT(*) as cantidad 
              FROM resenas 
              WHERE id_producto = $id_producto AND estado = 'aprobada'";
    $result = $conexion->query($query);
    $datos = $result->fetch_assoc();
    
    $promedio = $datos['promedio'] ? round(floatval($datos['promedio']), 2) : 0;
    $cantidad = intval($datos['cantidad']);
    
    // Actualizar el producto
    $query = "UPDATE productos SET valoracion = $promedio, cantidad_resenas = $cantidad WHERE id_producto = $id_producto";
    return $conexion->query($query);
}

function eliminar_resena($conexion, $id_resena) {
    $id_resena = intval($id_resena);
    
    // Obtener datos de la reseña antes de eliminar
    $resena = obtener_resena_por_id($conexion, $id_resena);
    
    $query = "DELETE FROM resenas WHERE id_resena = $id_resena";
    
    if ($conexion->query($query)) {
        // Si era aprobada, recalcular valoración del producto
        if ($resena && $resena['estado'] === 'aprobada') {
            actualizar_valoracion_producto($conexion, $resena['id_producto']);
        }
        return ['exito' => true];
    } else {
        return ['error' => $conexion->error];
    }
}

function usuario_ya_resenio_producto($conexion, $id_usuario, $id_producto) {
    $id_usuario = intval($id_usuario);
    $id_producto = intval($id_producto);
    
    $query = "SELECT id_resena FROM resenas 
              WHERE id_usuario = $id_usuario AND id_producto = $id_producto";
    $result = $conexion->query($query);
    return $result && $result->num_rows > 0;
}

?>
