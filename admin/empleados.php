<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/funciones.php';

if (!es_admin()) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

$empleados = [];
require_once __DIR__ . '/admin_header.php';

$result = $conn->query("SELECT u.* FROM usuarios u WHERE u.rol = 'empleado' ORDER BY u.fecha_registro DESC");
if ($result) {
    $empleados = $result->fetch_all(MYSQLI_ASSOC);
}

admin_render_header('Gestión de Empleados', 'Empleados', 'fas fa-briefcase');
?>
    <style>
        .section {
            background: rgba(248, 250, 252, 0.95);
            border: 1px solid rgba(148, 163, 184, 0.35);
            border-radius: 16px;
            padding: 25px;
        }

        .section-title {
            color: #0f172a;
            margin-bottom: 20px;
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
            border-color: #6200ff;
            box-shadow: 0 0 10px rgba(98, 0, 255, 0.2);
        }

        .search-input::placeholder {
            color: #666666;
        }

        .table {
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            width: 100%;
        }

        .table thead {
            background: #e2e8f0;
        }

        .table th,
        .table td {
            color: #0f172a;
            padding: 14px 16px;
            border-bottom: 1px solid rgba(148, 163, 184, 0.35);
        }

        .table tbody tr {
            background: #ffffff;
            transition: background 0.2s ease;
        }

        .table tbody tr:hover {
            background: #e2e8f0;
        }

        .badge {
            font-size: 0.85rem;
            font-weight: 600;
        }

        .no-results {
            text-align: center;
            padding: 40px;
            color: #0f172a;
        }
    </style>
<div class="section">
    <div class="section-title">
        <i class="fas fa-briefcase"></i> Empleados
    </div>

    <div class="search-container">
        <input type="text" id="buscarEmpleados" class="search-input" placeholder="🔍 Buscar por nombre o email...">
    </div>

    <?php if (es_admin()): ?>
        <div class="mb-3 text-end">
            <a href="crear_empleado.php" class="btn btn-sm btn-primary">
                <i class="fas fa-user-plus"></i> Agregar Empleado
            </a>
        </div>
    <?php endif; ?>
    <div style="overflow-x: auto;">
        <table class="table" id="tablaEmpleados">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Estado</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody id="bodyEmpleados">
                <?php foreach ($empleados as $emp): ?>
                <tr>
                    <td class="empleado-nombre"><?php echo htmlspecialchars($emp['nombre']); ?></td>
                    <td class="empleado-email"><?php echo htmlspecialchars($emp['correo']); ?></td>
                    <td><span class="badge bg-success">Activo</span></td>
                    <td>
                        <a href="ver_usuario.php?id=<?php echo intval($emp['id_usuario']); ?>" class="btn btn-sm btn-info">
                            <i class="fas fa-eye"></i> Ver
                        </a>
                        <a href="editar_usuario.php?id=<?php echo intval($emp['id_usuario']); ?>" class="btn btn-sm btn-warning">
                            <i class="fas fa-edit"></i> Editar
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div id="sinResultados" class="no-results" style="display: none;">
            <i class="fas fa-search" style="font-size: 2rem; opacity: 0.5; margin-bottom: 10px; display: block;"></i>
            No se encontraron empleados con esa búsqueda
        </div>
    </div>
</div>

<script>
    document.getElementById('buscarEmpleados').addEventListener('keyup', function() {
        const filtro = this.value.toLowerCase();
        const filas = document.querySelectorAll('#bodyEmpleados tr');
        let contadorVisibles = 0;

        filas.forEach(fila => {
            const nombre = fila.querySelector('.empleado-nombre').textContent.toLowerCase();
            const email = fila.querySelector('.empleado-email').textContent.toLowerCase();

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
