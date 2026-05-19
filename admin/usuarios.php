<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/funciones.php';

if (!es_admin()) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

$usuarios = [];
$mensaje_exito = '';
$mensaje_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_usuario'])) {
    $id_eliminar = intval($_POST['eliminar_usuario']);
    $usuario_eliminar = $conn->query("SELECT rol FROM usuarios WHERE id_usuario = $id_eliminar")->fetch_assoc();

    if ($usuario_eliminar && $usuario_eliminar['rol'] !== 'admin') {
        if ($conn->query("DELETE FROM usuarios WHERE id_usuario = $id_eliminar")) {
            $mensaje_exito = 'Usuario eliminado correctamente.';
        } else {
            $mensaje_error = 'No se pudo eliminar el usuario. Intenta nuevamente.';
        }
    } else {
        $mensaje_error = 'No se puede eliminar un usuario administrador.';
    }
}

$result = $conn->query("SELECT * FROM usuarios ORDER BY fecha_registro DESC");
if ($result) {
    $usuarios = $result->fetch_all(MYSQLI_ASSOC);
}

require_once __DIR__ . '/admin_header.php';
admin_render_header('Gestión de Usuarios', 'Usuarios', 'fas fa-users');
?>
    <style>
        .section {
            background: rgba(13, 17, 31, 0.95);
            border: 1px solid rgba(98, 0, 255, 0.35);
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.18);
        }

        .section-title {
            color: var(--accent);
            font-size: 1.45rem;
            font-weight: bold;
            margin-bottom: 24px;
            letter-spacing: 0.02em;
        }

        .search-container {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
        }

        .search-input {
            flex: 1;
            max-width: 400px;
            padding: 10px 15px;
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(98, 0, 255, 0.5);
            border-radius: 8px;
            color: #000000;
            font-size: 0.95rem;
            transition: all 0.3s;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 10px rgba(0, 212, 255, 0.2);
        }

        .search-input::placeholder {
            color: #666666;
        }

        .table-container {
            background: #f8fafc;
            border-radius: 16px;
            padding: 18px;
            border: 1px solid rgba(148, 163, 184, 0.25);
        }

        .table {
            background: transparent;
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .table thead {
            background: #e2e8f0;
            border-bottom: 1px solid rgba(148, 163, 184, 0.4);
        }

        .table th {
            color: #0f172a;
            padding: 14px 16px;
            text-align: left;
            font-weight: 700;
        }

        .table tbody tr {
            background: #ffffff;
            transition: background 0.2s ease;
        }

        .table tbody tr:hover {
            background: #e2e8f0;
        }

        .table td {
            padding: 14px 16px;
            border-bottom: 1px solid rgba(148, 163, 184, 0.35);
            color: #0f172a;
        }

        .user-cell {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #0f172a;
        }

        .avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: linear-gradient(135deg, #7c3aed, #06b6d4);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            text-transform: uppercase;
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.2);
        }

        .badge {
            padding: 6px 10px;
            border-radius: 6px;
            font-size: 0.82rem;
            font-weight: 700;
        }

        .btn-sm {
            padding: 7px 12px;
            font-size: 0.82rem;
            margin-right: 6px;
        }

        .no-results {
            text-align: center;
            padding: 40px;
            color: #0f172a;
        }
    </style>
    <div class="section">
        <div class="section-title">
            <i class="fas fa-list"></i> Usuarios Registrados
        </div>

        <div class="search-container">
            <input type="text" id="buscarUsuarios" class="search-input" placeholder="🔍 Buscar por nombre o email...">
        </div>

        <?php if (!empty($usuarios)): ?>
        <?php if ($mensaje_error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($mensaje_error); ?>
            </div>
        <?php endif; ?>
        <?php if ($mensaje_exito): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($mensaje_exito); ?>
            </div>
        <?php endif; ?>
        <div class="table-container">
            <div style="overflow-x: auto;">
                <table class="table" id="tablaUsuarios">
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Email</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Registro</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="bodyUsuarios">
                    <?php foreach ($usuarios as $usuario): ?>
                    <tr>
                        <td>
                            <div class="user-cell">
                                <div class="avatar">
                                    <?php echo strtoupper(substr($usuario['nombre'], 0, 1)); ?>
                                </div>
                                <span class="usuario-nombre"><?php echo htmlspecialchars($usuario['nombre']); ?></span>
                            </div>
                        </td>
                        <td class="usuario-email"><?php echo htmlspecialchars($usuario['correo']); ?></td>
                        <td>
                            <span class="badge bg-primary">
                                <?php echo htmlspecialchars($usuario['rol']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($usuario['activo']): ?>
                                <span class="badge bg-success">Activo</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Inactivo</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo formatear_fecha($usuario['fecha_registro']); ?></td>
                        <td style="white-space: nowrap;">
                            <a href="ver_usuario.php?id=<?php echo $usuario['id_usuario']; ?>" class="btn btn-sm btn-info mb-1">
                                <i class="fas fa-eye"></i> Ver
                            </a>
                            <?php if (in_array($usuario['rol'], ['admin', 'empleado'])): ?>
                                <a href="editar_usuario.php?id=<?php echo $usuario['id_usuario']; ?>" class="btn btn-sm btn-warning mb-1">
                                    <i class="fas fa-edit"></i> Editar
                                </a>
                            <?php else: ?>
                                <button class="btn btn-sm btn-warning mb-1" disabled title="No se puede editar usuarios cliente">
                                    <i class="fas fa-edit"></i> Editar
                                </button>
                            <?php endif; ?>

                            <?php if ($usuario['rol'] !== 'admin'): ?>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar este usuario?');">
                                    <button type="submit" name="eliminar_usuario" value="<?php echo $usuario['id_usuario']; ?>" class="btn btn-sm btn-danger">
                                        <i class="fas fa-trash-alt"></i> Eliminar
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div id="sinResultados" class="no-results" style="display: none;">
                <i class="fas fa-search" style="font-size: 2rem; opacity: 0.5; margin-bottom: 10px; display: block;"></i>
                No se encontraron usuarios con esa búsqueda
            </div>
            </div>
        </div>
        <?php else: ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> No hay usuarios registrados.
        </div>
        <?php endif; ?>
    </div>

    <script>
        document.getElementById('buscarUsuarios').addEventListener('keyup', function() {
            const filtro = this.value.toLowerCase();
            const filas = document.querySelectorAll('#bodyUsuarios tr');
            let contadorVisibles = 0;

            filas.forEach(fila => {
                const nombre = fila.querySelector('.usuario-nombre').textContent.toLowerCase();
                const email = fila.querySelector('.usuario-email').textContent.toLowerCase();

                if (nombre.includes(filtro) || email.includes(filtro)) {
                    fila.style.display = '';
                    contadorVisibles++;
                } else {
                    fila.style.display = 'none';
                }
            });

            const sinResultados = document.getElementById('sinResultados');
            sinResultados.style.display = contadorVisibles === 0 ? 'block' : 'none';
        });
    </script>
<?php admin_render_footer(); ?>
