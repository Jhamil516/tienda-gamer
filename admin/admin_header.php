<?php
// No llamar a session_start() aquí - ya se llama en los archivos que incluyen este
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/admin_footer.php';

if (!es_admin()) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

function admin_render_header($titulo, $seccion, $icono) {
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($titulo); ?> - Tienda Gamer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #6200ff;
            --accent: #00d4ff;
            --dark-bg: #0a0a0a;
            --card-bg: #1a1a2e;
            --text-light: #e0e0e0;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background: linear-gradient(135deg, var(--dark-bg) 0%, #1a0033 100%);
            color: var(--text-light);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .wrapper { display: flex; min-height: 100vh; }

        .sidebar {
            width: 250px;
            background: rgba(26, 26, 46, 0.95);
            border-right: 3px solid var(--primary);
            padding: 20px 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            left: 0;
            top: 0;
            z-index: 1000;
        }

        .sidebar-brand {
            padding: 20px;
            text-align: center;
            border-bottom: 2px solid var(--primary);
            margin-bottom: 20px;
        }

        .sidebar-brand h4 {
            color: var(--accent);
            font-weight: bold;
            margin: 0;
            text-shadow: 0 0 10px rgba(0, 212, 255, 0.3);
            font-size: 1.3rem;
        }

        .sidebar-brand small {
            color: var(--text-light);
            display: block;
            margin-top: 5px;
            font-size: 0.8rem;
        }

        .sidebar-menu { list-style: none; padding: 0; margin: 0; }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: var(--text-light);
            text-decoration: none;
            transition: all 0.3s;
            border-left: 3px solid transparent;
            font-size: 0.95rem;
        }

        .sidebar-menu a:hover { background: rgba(98, 0, 255, 0.2); border-left-color: var(--accent); color: var(--accent); }

        .sidebar-menu i { width: 20px; margin-right: 10px; text-align: center; }

        .main-content { 
            margin-left: 250px; 
            flex: 1; 
            padding: 30px; 
            display: flex;
            flex-direction: column;
        }

        .admin-footer {
            margin-top: auto;
            padding: 20px 30px;
            text-align: center;
            background: rgba(26, 26, 46, 0.95);
            border-top: 3px solid var(--primary);
            border-radius: 8px;
            color: var(--text-light);
            font-size: 0.9rem;
        }

        .topbar {
            background: rgba(10, 10, 10, 0.95);
            border-bottom: 3px solid var(--primary);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: -30px -30px 30px -30px;
            margin-bottom: 30px;
            border-radius: 5px;
        }

        .topbar h2 { color: var(--accent); margin: 0; font-weight: bold; font-size: 1.8rem; }

        .user-info { display: flex; align-items: center; gap: 15px; }

        .user-info span { color: var(--text-light); font-weight: 500; }

        .user-info a {
            background: #dc3545;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            transition: all 0.3s;
            font-weight: 500;
        }

        .user-info a:hover { background: #c82333; }

        .container { margin-left: 0; padding: 30px; max-width: 100%; }

        @media (max-width: 768px) {
            .sidebar { width: 100%; position: relative; height: auto; }
            .main-content { margin-left: 0; }
            .topbar { flex-direction: column; gap: 15px; }
            .container { margin-left: 0; }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="sidebar">
            <div class="sidebar-brand">
                <h4><i class="fas fa-gamepad"></i> GAMER FRIKI</h4>
                <small>Panel Admin</small>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <li><a href="productos.php"><i class="fas fa-box"></i> Productos</a></li>
                <li><a href="categorias.php"><i class="fas fa-th-large"></i> Categorías</a></li>
                <li><a href="usuarios.php"><i class="fas fa-users"></i> Usuarios</a></li>
                <li><a href="resenas.php"><i class="fas fa-star"></i> Reseñas</a></li>
                <li><a href="pedidos.php"><i class="fas fa-shopping-bag"></i> Pedidos</a></li>
                <li><a href="historial_ventas.php"><i class="fas fa-history"></i> Historial Ventas</a></li>
                <li><a href="empleados.php"><i class="fas fa-briefcase"></i> Empleados</a></li>
                <li><a href="promociones.php"><i class="fas fa-tag"></i> Promociones</a></li>
            </ul>
        </div>
        <div class="main-content">
            <div class="topbar">
                <h2><i class="<?php echo htmlspecialchars($icono); ?>"></i> <?php echo htmlspecialchars($titulo); ?></h2>
                <div class="user-info">
                    <span><?php echo htmlspecialchars($_SESSION['nombre'] ?? 'Admin'); ?></span>
                    <a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Salir</a>
                </div>
            </div>
    <?php
}
?>
