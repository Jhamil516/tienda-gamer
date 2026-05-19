<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/funciones.php';

if (!es_admin()) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

$pedidos = [];
$result = $conn->query("SELECT v.*, u.nombre FROM ventas v JOIN usuarios u ON v.id_usuario = u.id_usuario ORDER BY v.fecha_venta DESC");
if ($result) {
    $pedidos = $result->fetch_all(MYSQLI_ASSOC);
}

require_once __DIR__ . '/admin_header.php';
admin_render_header('Gestión de Pedidos', 'Pedidos', 'fas fa-shopping-bag');
?>
    <style>
        .section {
            background: rgba(26, 26, 46, 0.8);
            border: 2px solid var(--primary);
            border-radius: 10px;
            padding: 25px;
        }

        .filters-section {
            background: rgba(26, 26, 46, 0.95);
            border: 1px solid rgba(98, 0, 255, 0.35);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 25px;
        }

        .filter-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            color: var(--accent);
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 0.95rem;
        }

        .filter-group input,
        .filter-group select {
            padding: 10px 12px;
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(98, 0, 255, 0.5);
            border-radius: 8px;
            color: #000000;
            font-size: 0.9rem;
            transition: all 0.3s;
        }

        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: var(--accent);
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 0 10px rgba(0, 212, 255, 0.2);
        }

        .filter-group select option {
            color: #000000;
            background: #ffffff;
        }

        .btn-limpiar {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-light);
            border: 1px solid rgba(98, 0, 255, 0.3);
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-limpiar:hover {
            background: rgba(255, 255, 255, 0.15);
            border-color: var(--accent);
            color: var(--accent);
        }

        .table {
            background: rgba(10, 10, 10, 0.95) !important;
            color: #f7f7f7 !important;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid rgba(0, 212, 255, 0.15);
        }
        .table thead {
            background: rgba(0, 212, 255, 0.12) !important;
        }
        .table th,
        .table td {
            background: rgba(10, 10, 10, 0.9) !important;
        }
        .table th {
            color: #00d4ff !important;
            border-bottom: 2px solid rgba(0, 212, 255, 0.2) !important;
            font-weight: 700;
        }
        .table td {
            border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
            color: #f7f7f7 !important;
            font-weight: 600;
        }
        .table tbody tr {
            background: transparent !important;
        }
        .table tbody tr:hover {
            background: rgba(0, 212, 255, 0.12) !important;
        }
        .btn-info {
            background-color: #00d4ff !important;
            border-color: #00b8e6 !important;
            color: #0a0a0a !important;
        }

        .btn {
            color: #fff;
        }

        .badge-estado {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: capitalize;
        }

        .badge-pendiente {
            background: #ff9800;
            color: white;
        }

        .badge-confirmada {
            background: #2196f3;
            color: white;
        }

        .badge-enviada {
            background: #9c27b0;
            color: white;
        }

        .badge-entregada {
            background: #4caf50;
            color: white;
        }

        .badge-cancelada {
            background: #dc3545;
            color: white;
        }

        .no-results {
            text-align: center;
            padding: 40px;
            color: #f7f7f7;
        }

        .results-info {
            color: var(--text-light);
            margin-bottom: 15px;
            font-size: 0.95rem;
        }
    </style>

    <!-- Filtros -->
    <div class="filters-section">
        <div class="filter-row">
            <div class="filter-group">
                <label for="buscarPedidos"><i class="fas fa-search"></i> Buscar</label>
                <input type="text" id="buscarPedidos" placeholder="Número de venta o cliente...">
            </div>

            <div class="filter-group">
                <label for="filtroEstado"><i class="fas fa-filter"></i> Estado</label>
                <select id="filtroEstado">
                    <option value="">Todos los estados</option>
                    <option value="pendiente">Pendiente</option>
                    <option value="confirmada">Confirmada</option>
                    <option value="enviada">Enviada</option>
                    <option value="entregada">Entregada</option>
                    <option value="cancelada">Cancelada</option>
                </select>
            </div>

            <div class="filter-group">
                <label for="fechaDesde"><i class="fas fa-calendar"></i> Desde</label>
                <input type="date" id="fechaDesde">
            </div>

            <div class="filter-group">
                <label for="fechaHasta">Hasta</label>
                <input type="date" id="fechaHasta">
            </div>
        </div>

        <a href="pedidos.php" class="btn-limpiar">
            <i class="fas fa-redo"></i> Limpiar Filtros
        </a>
    </div>

    <!-- Resultados -->
    <div class="results-info">
        Mostrando <strong id="contadorPedidos">0</strong> pedidos
    </div>

    <div class="section">
        <?php if (!empty($pedidos)): ?>
            <div style="overflow-x: auto;">
                <table class="table table-borderless" id="tablaPedidos">
                    <thead>
                        <tr>
                            <th>Número</th>
                            <th>Cliente</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody id="bodyPedidos">
                        <?php foreach ($pedidos as $p): ?>
                        <tr data-numero="<?php echo htmlspecialchars($p['numero_venta']); ?>" data-cliente="<?php echo htmlspecialchars($p['nombre']); ?>" data-estado="<?php echo htmlspecialchars($p['estado_venta']); ?>" data-fecha="<?php echo date('Y-m-d', strtotime($p['fecha_venta'])); ?>">
                            <td class="pedido-numero"><?php echo htmlspecialchars($p['numero_venta']); ?></td>
                            <td class="pedido-cliente"><?php echo htmlspecialchars($p['nombre']); ?></td>
                            <td>BOB <?php echo number_format($p['total'], 2); ?></td>
                            <td>
                                <span class="badge-estado badge-<?php echo htmlspecialchars($p['estado_venta']); ?>">
                                    <?php echo htmlspecialchars($p['estado_venta']); ?>
                                </span>
                            </td>
                            <td><?php echo formatear_fecha($p['fecha_venta']); ?></td>
                            <td>
                                <a href="ver_compra.php?id=<?php echo intval($p['id_venta']); ?>" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i> Ver
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div id="sinResultados" class="no-results" style="display: none;">
                    <i class="fas fa-search" style="font-size: 2rem; opacity: 0.5; margin-bottom: 10px; display: block;"></i>
                    No se encontraron pedidos con los filtros aplicados
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> No hay pedidos registrados.
            </div>
        <?php endif; ?>
    </div>

    <script>
        function aplicarFiltros() {
            const busqueda = document.getElementById('buscarPedidos').value.toLowerCase();
            const estado = document.getElementById('filtroEstado').value.toLowerCase();
            const fechaDesde = document.getElementById('fechaDesde').value;
            const fechaHasta = document.getElementById('fechaHasta').value;

            const filas = document.querySelectorAll('#bodyPedidos tr');
            let contadorVisibles = 0;

            filas.forEach(fila => {
                const numero = fila.dataset.numero.toLowerCase();
                const cliente = fila.dataset.cliente.toLowerCase();
                const estadoFila = fila.dataset.estado.toLowerCase();
                const fechaFila = fila.dataset.fecha;

                let mostrar = true;

                // Filtro de búsqueda
                if (busqueda && !numero.includes(busqueda) && !cliente.includes(busqueda)) {
                    mostrar = false;
                }

                // Filtro de estado
                if (estado && estadoFila !== estado) {
                    mostrar = false;
                }

                // Filtro de fecha desde
                if (fechaDesde && fechaFila < fechaDesde) {
                    mostrar = false;
                }

                // Filtro de fecha hasta
                if (fechaHasta && fechaFila > fechaHasta) {
                    mostrar = false;
                }

                fila.style.display = mostrar ? '' : 'none';
                if (mostrar) contadorVisibles++;
            });

            document.getElementById('contadorPedidos').textContent = contadorVisibles;
            document.getElementById('sinResultados').style.display = contadorVisibles === 0 ? 'block' : 'none';
        }

        // Event listeners para filtros en tiempo real
        document.getElementById('buscarPedidos').addEventListener('keyup', aplicarFiltros);
        document.getElementById('filtroEstado').addEventListener('change', aplicarFiltros);
        document.getElementById('fechaDesde').addEventListener('change', aplicarFiltros);
        document.getElementById('fechaHasta').addEventListener('change', aplicarFiltros);

        // Inicializar contador
        document.getElementById('contadorPedidos').textContent = document.querySelectorAll('#bodyPedidos tr').length;
    </script>

<?php admin_render_footer(); ?>
