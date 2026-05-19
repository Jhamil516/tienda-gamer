<?php
session_start();
require_once 'config/db.php';

// Si el usuario está autenticado, redirigir según su rol
if (isset($_SESSION['id_usuario'])) {
    if ($_SESSION['rol'] === 'admin') {
        header('Location: admin/dashboard.php');
        exit;
    } elseif ($_SESSION['rol'] === 'empleado') {
        header('Location: empleado/dashboard.php');
        exit;
    } else {
        header('Location: client/catalogo.php');
        exit;
    }
}

$productos_destacados = [];
$result = $conn->query("SELECT * FROM productos WHERE destacado = 1 AND estado = 'activo' LIMIT 8");
if ($result) {
    $productos_destacados = $result->fetch_all(MYSQLI_ASSOC);
}

$categorias = [];
$result = $conn->query("SELECT * FROM categorias WHERE activa = 1 ORDER BY orden ASC");
if ($result) {
    $categorias = $result->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tienda Gamer - Compra Videojuegos y Hardware</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <style>
        :root {
            --primary: #6200ff;
            --primary-dark: #4100cc;
            --accent: #00d4ff;
            --danger: #ff006e;
            --dark-bg: #0a0a0a;
            --card-bg: #1a1a2e;
            --text-light: #e0e0e0;
        }

        body {
            background: linear-gradient(135deg, var(--dark-bg) 0%, #1a0033 100%);
            color: var(--text-light);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Navbar */
        .navbar {
            background: rgba(10, 10, 10, 0.95);
            border-bottom: 3px solid var(--primary);
            backdrop-filter: blur(10px);
        }

        .navbar-brand {
            font-size: 24px;
            font-weight: bold;
            color: var(--accent) !important;
            text-shadow: 0 0 20px rgba(0, 212, 255, 0.5);
        }

        .nav-link {
            color: var(--text-light) !important;
            margin: 0 10px;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            color: var(--accent) !important;
            text-shadow: 0 0 10px rgba(0, 212, 255, 0.3);
        }

        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, rgba(98, 0, 255, 0.2) 0%, rgba(0, 212, 255, 0.1) 100%);
            border: 2px solid var(--primary);
            border-radius: 15px;
            padding: 80px 40px;
            text-align: center;
            margin: 40px 0;
            box-shadow: 0 0 30px rgba(98, 0, 255, 0.3);
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        .hero-section h1 {
            font-size: 3.5rem;
            font-weight: 900;
            margin-bottom: 20px;
            color: var(--accent);
            text-shadow: 0 0 30px rgba(0, 212, 255, 0.6);
        }

        .hero-section p {
            font-size: 1.3rem;
            margin-bottom: 30px;
            color: var(--text-light);
        }

        /* Buttons */
        .btn-primary {
            background: var(--primary);
            border: 2px solid var(--accent);
            color: #fff;
            font-weight: bold;
            padding: 12px 30px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            box-shadow: 0 0 20px rgba(98, 0, 255, 0.6);
            border-color: var(--accent);
        }

        .btn-outline-light {
            border: 2px solid var(--accent);
            color: var(--accent);
        }

        .btn-outline-light:hover {
            background: var(--primary);
            border-color: var(--accent);
            box-shadow: 0 0 20px rgba(0, 212, 255, 0.4);
        }

        /* Categorias */
        .categoria-card {
            background: var(--card-bg);
            border: 2px solid var(--primary);
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            height: 100%;
        }

        .categoria-card:hover {
            border-color: var(--accent);
            transform: translateY(-10px);
            box-shadow: 0 10px 30px rgba(0, 212, 255, 0.3);
        }

        .categoria-card h5 {
            color: var(--accent);
            margin-top: 15px;
        }

        /* Productos */
        .product-card {
            background: var(--card-bg);
            border: 2px solid #333;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .product-card:hover {
            border-color: var(--accent);
            transform: translateY(-8px);
            box-shadow: 0 15px 40px rgba(0, 212, 255, 0.2);
        }

        .product-image {
            position: relative;
            overflow: hidden;
            height: 200px;
            background: #000;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .product-card:hover .product-image img {
            transform: scale(1.1);
        }

        .product-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: var(--danger);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .product-info {
            padding: 15px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .product-name {
            color: var(--text-light);
            font-weight: bold;
            margin-bottom: 8px;
            flex: 1;
        }

        .product-price {
            color: var(--accent);
            font-size: 1.3rem;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .product-original {
            color: #888;
            text-decoration: line-through;
            font-size: 0.9rem;
        }

        .product-actions {
            display: flex;
            gap: 10px;
            margin-top: auto;
        }

        .btn-small {
            flex: 1;
            padding: 8px 12px;
            font-size: 0.9rem;
            border-radius: 6px;
        }

        /* Section Titles */
        .section-title {
            font-size: 2.5rem;
            font-weight: 900;
            color: var(--accent);
            text-shadow: 0 0 20px rgba(0, 212, 255, 0.4);
            margin: 50px 0 30px;
            text-align: center;
            border-bottom: 3px solid var(--primary);
            padding-bottom: 15px;
        }

        /* Footer */
        footer {
            background: rgba(10, 10, 10, 0.95);
            border-top: 3px solid var(--primary);
            padding: 40px 0;
            margin-top: 60px;
        }

        .footer-section h5 {
            color: var(--accent);
            margin-bottom: 20px;
        }

        .footer-section a {
            color: var(--text-light);
            text-decoration: none;
            transition: color 0.3s;
        }

        .footer-section a:hover {
            color: var(--accent);
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php"><i class="fas fa-gamepad"></i> GAMER FRIKI</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#categorias">Categorías</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="client/catalogo.php">Catálogo</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="client/historia.php">Historia</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="client/contacto.php">Contacto</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="auth/login.php"><i class="fas fa-sign-in-alt"></i> Iniciar Sesión</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="auth/registro.php"><i class="fas fa-user-plus"></i> Registrarse</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="container">
        <div class="hero-section">
            <h1><i class="fas fa-gamepad"></i> Bienvenido a Gamer Friki <i class="fas fa-gamepad"></i></h1>
            <p>La tienda online más épica para gamers</p>
            <div class="row justify-content-center">
                <div class="col-md-3 mb-2">
                    <a href="client/catalogo.php" class="btn btn-primary w-100">
                        <i class="fas fa-store"></i> Ver Productos
                    </a>
                </div>
                <div class="col-md-3 mb-2">
                    <a href="auth/login.php" class="btn btn-outline-light w-100">
                        <i class="fas fa-sign-in-alt"></i> Ingresar
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Categorías -->
    <div class="container">
        <div class="section-title" id="categorias">
            <i class="fas fa-th-large"></i> Categorías
        </div>
        <div class="row g-4 mb-5">
            <?php foreach ($categorias as $cat): ?>
            <div class="col-md-4 col-lg-2-5">
                <div class="categoria-card">
                    <div style="font-size: 3rem;"><?php echo htmlspecialchars($cat['icono']); ?></div>
                    <h5><?php echo htmlspecialchars($cat['nombre_categoria']); ?></h5>
                    <p style="font-size: 0.9rem; color: #999;"><?php echo substr(htmlspecialchars($cat['descripcion'] ?? ''), 0, 50); ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Productos Destacados -->
    <div class="container">
        <div class="section-title">
            <i class="fas fa-star"></i> Productos Destacados
        </div>
        <div class="row g-4 mb-5">
            <?php foreach ($productos_destacados as $prod): ?>
            <div class="col-md-6 col-lg-3">
                <div class="product-card">
                    <div class="product-image">
                        <img src="uploads/<?php echo htmlspecialchars($prod['imagen_principal'] ?? 'placeholder.jpg'); ?>" alt="<?php echo htmlspecialchars($prod['nombre']); ?>">
                        <?php if ($prod['descuento_porcentaje'] > 0): ?>
                        <span class="product-badge">-<?php echo $prod['descuento_porcentaje']; ?>%</span>
                        <?php endif; ?>
                    </div>
                    <div class="product-info">
                        <div class="product-name"><?php echo htmlspecialchars($prod['nombre']); ?></div>
                        <div>
                            <div class="product-price">BOB <?php echo number_format($prod['precio_actual'], 2, ',', '.'); ?></div>
                            <?php if ($prod['precio_original'] > $prod['precio_actual']): ?>
                            <div class="product-original">BOB <?php echo number_format($prod['precio_original'], 2, ',', '.'); ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="product-actions">
                            <a href="client/producto.php?id=<?php echo $prod['id_producto']; ?>" class="btn btn-primary btn-small">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="client/carrito.php?agregar=<?php echo $prod['id_producto']; ?>" class="btn btn-outline-light btn-small">
                                <i class="fas fa-shopping-cart"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="row">
                <div class="col-md-4 footer-section">
                    <h5><i class="fas fa-gamepad"></i> Sobre Nosotros</h5>
                    <p>Somos la tienda online especializada en videojuegos, hardware gamer y artículos frikis más grande del país.</p>
                    <a href="client/historia.php" class="text-decoration-none">Conoce nuestra historia →</a>
                </div>
                <div class="col-md-4 footer-section">
                    <h5><i class="fas fa-phone"></i> Contacto</h5>
                    <p><?php echo htmlspecialchars($_ENV['TELEFONO_TIENDA'] ?? TELEFONO_TIENDA); ?></p>
                    <p><?php echo htmlspecialchars($_ENV['EMAIL_TIENDA'] ?? EMAIL_TIENDA); ?></p>
                    <a href="client/contacto.php" class="text-decoration-none">Enviar mensaje →</a>
                </div>
                <div class="col-md-4 footer-section">
                    <h5><i class="fas fa-link"></i> Enlaces Rápidos</h5>
                    <ul style="list-style: none; padding: 0;">
                        <li><a href="client/catalogo.php">Catálogo</a></li>
                        <li><a href="client/historia.php">Nuestra Historia</a></li>
                        <li><a href="client/contacto.php">Contacto</a></li>
                        <li><a href="auth/login.php">Mi Cuenta</a></li>
                        <li><a href="client/carrito.php">Carrito</a></li>
                    </ul>
                </div>
            </div>
            <hr style="border-color: #333; margin: 30px 0;">
            <div style="text-align: center; color: #888;">
                <p>&copy; 2025 Gamer Friki. Todos los derechos reservados. | Diseño épico para gamers</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
