<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config/db.php';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🎮 GAMER FRIKI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #0a0a0a 0%, #1a0033 100%);
            color: #e0e0e0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .navbar {
            background: rgba(10, 10, 10, 0.95);
            border-bottom: 2px solid #00d4ff;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.35);
        }

        .navbar-brand {
            color: #00d4ff !important;
            font-weight: bold;
        }

        .nav-link {
            color: #e0e0e0 !important;
        }

        .nav-link:hover,
        .navbar-brand:hover {
            color: #8ad4ff !important;
        }

        .form-control {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.16);
            color: #fff;
        }

        .form-control:focus {
            border-color: #00d4ff;
            box-shadow: 0 0 0 0.2rem rgba(0, 212, 255, 0.18);
            background: rgba(255, 255, 255, 0.14);
        }

        .btn-outline-info {
            color: #00d4ff;
            border-color: #00d4ff;
        }

        .btn-outline-info:hover {
            background: rgba(0, 212, 255, 0.12);
            color: #fff;
        }

        main {
            padding: 30px 0;
        }

        .product-card {
            background: rgba(26, 26, 46, 0.92);
            border: 1px solid rgba(0, 212, 255, 0.12);
            border-radius: 16px;
            overflow: hidden;
            transition: transform 0.25s ease, border-color 0.25s ease;
        }

        .product-card:hover {
            transform: translateY(-4px);
            border-color: rgba(0, 212, 255, 0.35);
        }

        .product-image {
            width: 100%;
            height: 250px;
            object-fit: cover;
            background: #111;
        }

        .product-price {
            font-size: 1.35rem;
            color: #00d4ff;
            font-weight: 700;
        }

        .float-right {
            float: right;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?php echo BASE_URL; ?>"><?php echo NOMBRE_TIENDA; ?></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>client/catalogo.php">Catálogo</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>client/historia.php">Historia</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>client/contacto.php">Contacto</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>client/favoritos.php">Favoritos</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>client/carrito.php">Carrito</a></li>
                <?php if (isset($_SESSION['id_usuario']) && !empty($_SESSION['id_usuario'])): ?>
                    <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>client/historial.php"><i class="fas fa-receipt me-1"></i> Mis Compras</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>auth/login.php">Iniciar Sesión</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>auth/registro.php">Registrarse</a></li>
                <?php endif; ?>
            </ul>
            <form class="d-flex" method="GET" action="<?php echo BASE_URL; ?>client/catalogo.php">
                <input class="form-control form-control-sm me-2" type="search" name="search" placeholder="Buscar nombre, marca, categoría"
                       aria-label="Buscar" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                <button class="btn btn-outline-info btn-sm" type="submit"><i class="fas fa-search"></i></button>
            </form>
            <?php if (!empty($_SESSION['id_usuario'])): ?>
                <?php
                    $user_name = htmlspecialchars($_SESSION['nombre'] ?? 'Usuario');
                    $user_role = htmlspecialchars($_SESSION['rol'] ?? 'cliente');
                    $role_icon = 'fa-user';
                    if ($user_role === 'admin') $role_icon = 'fa-user-shield';
                    if ($user_role === 'empleado') $role_icon = 'fa-user-tie';
                ?>
                <div class="ms-3 d-flex align-items-center">
                    <div class="dropdown">
                        <a class="btn btn-sm btn-outline-info dropdown-toggle d-flex align-items-center" href="#" role="button" id="userMenu" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas <?php echo $role_icon; ?> me-2"></i>
                            <span class="me-2"><?php echo $user_name; ?></span>
                            <span class="badge bg-light text-dark text-uppercase small"><?php echo $user_role; ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>client/historial.php"><i class="fas fa-receipt me-2"></i> Mis Compras</a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>client/favoritos.php"><i class="fas fa-heart me-2"></i> Favoritos</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?php echo BASE_URL; ?>auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Cerrar sesión</a></li>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</nav>
<main class="container">
    <?php if (!empty($_SESSION['nombre'])): ?>
        <div class="alert alert-secondary rounded-3 mb-4 py-2">
            <div class="d-flex justify-content-between align-items-center">
                <div>Bienvenido, <strong><?php echo htmlspecialchars($_SESSION['nombre']); ?></strong></div>
                <?php if (!empty($_SESSION['rol'])): ?>
                    <span class="badge bg-info text-dark text-uppercase"><?php echo htmlspecialchars($_SESSION['rol']); ?></span>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

