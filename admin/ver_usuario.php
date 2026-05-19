<?php
session_start();
$id = intval($_GET['id'] ?? 0);
$usuario = null;
$compras = [];

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/admin_header.php';

if ($id > 0) {
    $result = $conn->query("SELECT * FROM usuarios WHERE id_usuario = $id");
    if ($result && $result->num_rows > 0) {
        $usuario = $result->fetch_assoc();

        $compras_result = $conn->query("SELECT * FROM ventas WHERE id_usuario = $id ORDER BY fecha_venta DESC");
        if ($compras_result) {
            $compras = $compras_result->fetch_all(MYSQLI_ASSOC);
        }
    }
}

if (!$usuario) {
    header('Location: usuarios.php');
    exit;
}

admin_render_header('Ver Usuario', 'Gestión de Usuarios', 'fas fa-users');
?>
    <style>
        .card {
            background: rgba(26, 26, 46, 0.9);
            border: 2px solid #6200ff;
            border-radius: 10px;
        }

        .card-header {
            background: rgba(98, 0, 255, 0.2);
            border-bottom: 2px solid #6200ff;
            color: #00d4ff;
            font-weight: bold;
        }

        .card-body {
            padding: 25px;
        }

        .avatar-large {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: #6200ff;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
            font-weight: bold;
            margin-bottom: 20px;
            border: 3px solid #00d4ff;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #333;
            color: #ddd;
        }

        .info-label {
            color: #00d4ff;
            font-weight: bold;
        }

        .btn-group .btn {
            margin-right: 10px;
        }

        .table thead {
            background: rgba(98, 0, 255, 0.2);
        }

        .table th {
            color: #00d4ff;
        }

        .table td {
            color: #e0e0e0;
        }

        .alert-info {
            background: rgba(23, 162, 184, 0.15);
            border-color: #00d4ff;
            color: #e0e0e0;
        }
    </style>
    <div class="container">
        <a href="usuarios.php" class="btn btn-outline-light mb-3">
            <i class="fas fa-arrow-left"></i> Volver
        </a>

        <!-- Información del Usuario -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-user"></i> Información del Usuario
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 text-center">
                        <div class="avatar-large">
                            <?php echo strtoupper(substr($usuario['nombre'], 0, 1)); ?>
                        </div>
                    </div>
                    <div class="col-md-9">
                        <div class="info-item">
                            <span class="info-label">ID:</span>
                            <span>#<?php echo $usuario['id_usuario']; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Nombre:</span>
                            <span><?php echo htmlspecialchars($usuario['nombre']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Email:</span>
                            <span><?php echo htmlspecialchars($usuario['correo']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Rol:</span>
                            <span>
                                <span class="badge bg-primary">
                                    <?php echo htmlspecialchars($usuario['rol']); ?>
                                </span>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Estado:</span>
                            <span>
                                <?php if ($usuario['activo']): ?>
                                    <span class="badge bg-success">Activo</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Inactivo</span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Registrado:</span>
                            <span><?php echo formatear_fecha($usuario['fecha_registro']); ?></span>
                        </div>

                        <div class="btn-group">
                            <a href="editar_usuario.php?id=<?php echo $usuario['id_usuario']; ?>" class="btn btn-warning">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                            <button class="btn btn-danger" onclick="desactivarUsuario()">
                                <i class="fas fa-lock"></i> Desactivar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Historial de Compras -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-shopping-bag"></i> Historial de Compras (<?php echo count($compras); ?>)
            </div>
            <div class="card-body">
                <?php if (!empty($compras)): ?>
                <div style="overflow-x: auto;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Número de Orden</th>
                                <th>Total</th>
                                <th>Estado</th>
                                <th>Fecha</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($compras as $compra): ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($compra['numero_venta']); ?></code></td>
                                <td><strong>BOB <?php echo number_format($compra['total'], 2, ',', '.'); ?></strong></td>
                                <td>
                                    <span class="badge bg-info">
                                        <?php echo htmlspecialchars($compra['estado_venta']); ?>
                                    </span>
                                </td>
                                <td><?php echo formatear_fecha($compra['fecha_venta']); ?></td>
                                <td>
                                    <a href="ver_compra.php?id=<?php echo $compra['id_venta']; ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Este usuario no ha realizado compras aún.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function editarUsuario() {
            alert('Función de edición próximamente');
        }

        function desactivarUsuario() {
            if (confirm('¿Desactivar este usuario?')) {
                alert('Usuario desactivado');
            }
        }
    </script>
<?php admin_render_footer(); ?>
