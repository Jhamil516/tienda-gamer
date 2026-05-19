<?php
require_once __DIR__ . '/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_estado'])) {
    $id_venta = intval($_POST['id_venta']);
    $nuevo_estado = $_POST['nuevo_estado'] ?? '';
    $estados_permitidos = ['pendiente', 'confirmada', 'enviada', 'entregada', 'cancelada'];

    if (in_array($nuevo_estado, $estados_permitidos)) {
        $stmt = $conn->prepare("UPDATE ventas SET estado_venta = ? WHERE id_venta = ?");
        $stmt->bind_param("si", $nuevo_estado, $id_venta);
        $stmt->execute();
    }

    header('Location: ventas.php');
    exit;
}

empleado_render_header('Ventas', 'fas fa-chart-bar');

// Filtros
$estado   = $_GET['estado']   ?? 'todos';
$buscar   = trim($_GET['buscar'] ?? '');
$fecha_desde = $_GET['fecha_desde'] ?? '';
$fecha_hasta = $_GET['fecha_hasta'] ?? '';
$page     = max(1, intval($_GET['page'] ?? 1));
$por_pag  = 10;
$offset   = ($page - 1) * $por_pag;

// WHERE dinámico
$where = "WHERE 1=1";
$params = [];
$types  = '';

if ($estado !== 'todos') {
    $where .= " AND v.estado_venta = ?";
    $params[] = $estado;
    $types   .= 's';
}
if ($buscar !== '') {
    $where .= " AND (u.nombre LIKE ? OR v.numero_venta LIKE ?)";
    $b = "%$buscar%";
    $params[] = $b;
    $params[] = $b;
    $types   .= 'ss';
}
if ($fecha_desde !== '') {
    $where .= " AND DATE(v.fecha_venta) >= ?";
    $params[] = $fecha_desde;
    $types   .= 's';
}
if ($fecha_hasta !== '') {
    $where .= " AND DATE(v.fecha_venta) <= ?";
    $params[] = $fecha_hasta;
    $types   .= 's';
}

// Total registros
$sql_count = "SELECT COUNT(*) as total FROM ventas v JOIN usuarios u ON v.id_usuario = u.id_usuario $where";
$stmt_c = $conn->prepare($sql_count);
if ($types) $stmt_c->bind_param($types, ...$params);
$stmt_c->execute();
$total_reg   = $stmt_c->get_result()->fetch_assoc()['total'];
$total_pags  = max(1, ceil($total_reg / $por_pag));

// Ventas paginadas
$tipos_pag = $types . 'ii';
$params_pag = array_merge($params, [$por_pag, $offset]);
$sql = "SELECT v.id_venta, v.numero_venta, u.nombre, u.correo,
               v.fecha_venta, v.total, v.estado_venta, v.subtotal
        FROM ventas v
        JOIN usuarios u ON v.id_usuario = u.id_usuario
        $where
        ORDER BY v.fecha_venta DESC
        LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param($tipos_pag, ...$params_pag);
$stmt->execute();
$ventas = $stmt->get_result();

// Stats rápidas
$stats_q = $conn->query("SELECT
    COUNT(*) as total,
    SUM(total) as ingresos,
    SUM(CASE WHEN estado_venta='pendiente'  THEN 1 ELSE 0 END) as pendientes,
    SUM(CASE WHEN estado_venta='entregada'  THEN 1 ELSE 0 END) as entregadas,
    SUM(CASE WHEN estado_venta='cancelada'  THEN 1 ELSE 0 END) as canceladas,
    SUM(CASE WHEN DATE(fecha_venta)=CURDATE() THEN total ELSE 0 END) as hoy
    FROM ventas v JOIN usuarios u ON v.id_usuario=u.id_usuario");
$stats = $stats_q->fetch_assoc();
?>

<style>
/* ── Paleta del proyecto ── */
:root {
    --primary:   #6200ff;
    --accent:    #00d4ff;
    --dark-bg:   #0a0a0a;
    --card-bg:   #1a1a2e;
    --text-light:#e0e0e0;
    --purple:    #9333ea;
    --green:     #10b981;
    --yellow:    #f59e0b;
    --red:       #ef4444;
    --blue:      #3b82f6;
}

/* ── Cards de stats ── */
.v-stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:16px; margin-bottom:28px; }
.v-stat  {
    background: rgba(26,26,46,0.9);
    border: 1px solid rgba(98,0,255,0.4);
    border-radius: 14px;
    padding: 20px 16px;
    text-align: center;
    transition: transform .25s, box-shadow .25s;
}
.v-stat:hover { transform:translateY(-3px); box-shadow:0 8px 20px rgba(98,0,255,0.25); }
.v-stat .v-icon { font-size:1.8rem; margin-bottom:8px; }
.v-stat .v-num  { font-size:1.7rem; font-weight:800; color:var(--accent); line-height:1; }
.v-stat .v-lbl  { font-size:.78rem; color:#888; margin-top:5px; }

/* ── Barra de filtros ── */
.filtros-bar {
    background: rgba(26,26,46,0.9);
    border: 1px solid rgba(98,0,255,0.3);
    border-radius: 12px;
    padding: 16px 20px;
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: center;
    margin-bottom: 20px;
}
.filtro-input {
    background: rgba(15,15,30,0.8);
    border: 1px solid rgba(98,0,255,0.4);
    color: #fff;
    border-radius: 8px;
    padding: 8px 14px;
    font-size:.9rem;
    outline: none;
    transition: border-color .2s;
}
.filtro-input:focus { border-color: var(--accent); box-shadow:0 0 8px rgba(0,212,255,0.2); }
.filtro-input option { background:#1a1a2e; color:#e0e0e0; }
.filtro-input[type="date"] {
    min-width: 140px;
}
.filtro-input[type="date"]::-webkit-calendar-picker-indicator {
    filter: invert(1) brightness(1.1);
    cursor: pointer;
}
.btn-filtrar {
    background: linear-gradient(135deg, var(--primary), var(--purple));
    border: none; color:#fff;
    padding: 8px 18px; border-radius:8px;
    font-size:.9rem; font-weight:600; cursor:pointer;
    transition: opacity .2s;
}
.btn-filtrar:hover { opacity:.85; }
.btn-limpiar {
    background: rgba(255,255,255,0.07);
    border: 1px solid rgba(255,255,255,0.15);
    color: #ccc;
    padding: 8px 16px; border-radius:8px;
    font-size:.9rem; text-decoration:none;
    transition: background .2s;
}
.btn-limpiar:hover { background: rgba(255,255,255,0.13); color:#fff; }

/* ── Tabla ── */
.v-table-wrap {
    background: rgba(26,26,46,0.9);
    border: 1px solid rgba(98,0,255,0.3);
    border-radius: 14px;
    overflow: hidden;
}
.v-table { width:100%; border-collapse:collapse; }
.v-table thead tr { background: rgba(98,0,255,0.2); border-bottom:2px solid var(--primary); }
.v-table th { padding:13px 14px; color:var(--accent); font-size:.85rem; font-weight:700; text-transform:uppercase; letter-spacing:.5px; }
.v-table td { padding:13px 14px; color:var(--text-light); font-size:.9rem; border-bottom:1px solid rgba(98,0,255,0.12); }
.v-table tbody tr:hover { background: rgba(98,0,255,0.08); }
.v-table tbody tr:last-child td { border-bottom:none; }

/* ── Badges de estado ── */
.badge-est {
    padding: 4px 12px; border-radius:20px;
    font-size:.75rem; font-weight:700;
    display:inline-block; white-space:nowrap;
}
.est-pendiente  { background:rgba(245,158,11,.18); color:#fbbf24; border:1px solid #fbbf24; }
.est-confirmada { background:rgba(59,130,246,.18);  color:#60a5fa; border:1px solid #60a5fa; }
.est-enviada    { background:rgba(147,51,234,.18);  color:#c084fc; border:1px solid #c084fc; }
.est-entregada  { background:rgba(16,185,129,.18);  color:#34d399; border:1px solid #34d399; }
.est-cancelada  { background:rgba(239,68,68,.18);   color:#f87171; border:1px solid #f87171; }

/* ── Botón ver ── */
.btn-ver {
    background: rgba(0,212,255,0.12);
    color: var(--accent);
    border: 1px solid var(--accent);
    border-radius:7px;
    padding:5px 13px;
    font-size:.82rem;
    text-decoration:none;
    transition: background .2s;
    white-space:nowrap;
}
.btn-ver:hover { background: rgba(0,212,255,0.25); color:var(--accent); }

/* ── Select cambio estado ── */
.sel-estado {
    background: rgba(15,15,30,0.9);
    border: 1px solid rgba(98,0,255,0.4);
    color: #e0e0e0;
    border-radius: 7px;
    padding: 4px 8px;
    font-size:.8rem;
    cursor:pointer;
}
.sel-estado:focus { border-color:var(--accent); outline:none; }

/* ── Paginación ── */
.pag { display:flex; justify-content:center; gap:6px; margin-top:20px; flex-wrap:wrap; }
.pag a, .pag span {
    background: rgba(26,26,46,0.9);
    border: 1px solid rgba(98,0,255,0.35);
    color: var(--accent);
    padding: 7px 14px; border-radius:8px;
    font-size:.88rem; text-decoration:none;
    transition: background .2s;
}
.pag a:hover     { background: rgba(98,0,255,0.3); }
.pag .active     { background: var(--primary); border-color:var(--primary); color:#fff; font-weight:700; }

/* ── Vacío ── */
.vacio { text-align:center; padding:60px 20px; color:#666; }
.vacio i { font-size:3.5rem; margin-bottom:14px; display:block; color:rgba(98,0,255,0.4); }
</style>

<!-- ── STATS ─────────────────────────────────────────────────────────────── -->
<div class="v-stats">
    <div class="v-stat">
        <div class="v-icon" style="color:var(--accent);">💰</div>
        <div class="v-num">BOB <?php echo number_format($stats['ingresos'] ?? 0, 0, ',', '.'); ?></div>
        <div class="v-lbl">Ingresos totales</div>
    </div>
    <div class="v-stat">
        <div class="v-icon" style="color:var(--green);">💵</div>
        <div class="v-num" style="color:var(--green);">BOB <?php echo number_format($stats['hoy'] ?? 0, 0, ',', '.'); ?></div>
        <div class="v-lbl">Ventas de hoy</div>
    </div>
    <div class="v-stat">
        <div class="v-icon" style="color:var(--yellow);">⏳</div>
        <div class="v-num" style="color:var(--yellow);"><?php echo $stats['pendientes']; ?></div>
        <div class="v-lbl">Pendientes</div>
    </div>
    <div class="v-stat">
        <div class="v-icon" style="color:var(--green);">✅</div>
        <div class="v-num" style="color:var(--green);"><?php echo $stats['entregadas']; ?></div>
        <div class="v-lbl">Entregadas</div>
    </div>
    <div class="v-stat">
        <div class="v-icon" style="color:var(--red);">❌</div>
        <div class="v-num" style="color:var(--red);"><?php echo $stats['canceladas']; ?></div>
        <div class="v-lbl">Canceladas</div>
    </div>
    <div class="v-stat">
        <div class="v-icon" style="color:var(--blue);">📦</div>
        <div class="v-num" style="color:var(--blue);"><?php echo $stats['total']; ?></div>
        <div class="v-lbl">Total órdenes</div>
    </div>
</div>

<!-- ── FILTROS ────────────────────────────────────────────────────────────── -->
<form method="GET" class="filtros-bar">
    <input type="text" name="buscar" class="filtro-input" placeholder="🔍 Buscar cliente o N° orden..." value="<?php echo htmlspecialchars($buscar); ?>" style="flex:1; min-width:200px;">
    <select name="estado" class="filtro-input">
        <option value="todos"       <?php echo $estado==='todos'       ? 'selected' : ''; ?>>Todos los estados</option>
        <option value="pendiente"   <?php echo $estado==='pendiente'   ? 'selected' : ''; ?>>Pendiente</option>
        <option value="confirmada"  <?php echo $estado==='confirmada'  ? 'selected' : ''; ?>>Confirmada</option>
        <option value="enviada"     <?php echo $estado==='enviada'     ? 'selected' : ''; ?>>Enviada</option>
        <option value="entregada"   <?php echo $estado==='entregada'   ? 'selected' : ''; ?>>Entregada</option>
        <option value="cancelada"   <?php echo $estado==='cancelada'   ? 'selected' : ''; ?>>Cancelada</option>
    </select>
    <input type="date" name="fecha_desde" class="filtro-input" value="<?php echo htmlspecialchars($fecha_desde); ?>" title="Desde">
    <input type="date" name="fecha_hasta" class="filtro-input" value="<?php echo htmlspecialchars($fecha_hasta); ?>" title="Hasta">
    <button type="submit" class="btn-filtrar"><i class="fas fa-filter"></i> Filtrar</button>
    <?php if ($buscar || $estado !== 'todos' || $fecha_desde || $fecha_hasta): ?>
    <a href="ventas.php" class="btn-limpiar"><i class="fas fa-times"></i> Limpiar</a>
    <?php endif; ?>
    <span style="color:#666; font-size:.82rem; margin-left:auto;"><?php echo $total_reg; ?> resultado<?php echo $total_reg!==1?'s':''; ?></span>
</form>

<!-- ── TABLA ─────────────────────────────────────────────────────────────── -->
<div class="v-table-wrap">
    <div style="overflow-x:auto;">
        <table class="v-table">
            <thead>
                <tr>
                    <th>N° Orden</th>
                    <th>Cliente</th>
                    <th>Correo</th>
                    <th>Fecha</th>
                    <th>Total</th>
                    <th>Estado</th>
                    <th style="text-align:center;">Cambiar estado</th>
                    <th style="text-align:center;">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($ventas->num_rows === 0): ?>
                <tr><td colspan="8" class="vacio"><i class="fas fa-box-open"></i>No hay ventas que coincidan con los filtros.</td></tr>
            <?php else: ?>
            <?php while ($v = $ventas->fetch_assoc()): ?>
                <tr>
                    <td style="font-family:monospace; color:var(--primary); font-weight:700;">
                        <?php echo htmlspecialchars($v['numero_venta'] ?? '#'.$v['id_venta']); ?>
                    </td>
                    <td><?php echo htmlspecialchars($v['nombre']); ?></td>
                    <td style="color:#888; font-size:.82rem;"><?php echo htmlspecialchars($v['correo']); ?></td>
                    <td style="color:#aaa; white-space:nowrap;"><?php echo date('d/m/Y H:i', strtotime($v['fecha_venta'])); ?></td>
                    <td style="color:var(--accent); font-weight:700;">BOB <?php echo number_format($v['total'], 2, ',', '.'); ?></td>
                    <td><span class="badge-est est-<?php echo $v['estado_venta']; ?>"><?php echo ucfirst($v['estado_venta']); ?></span></td>
                    <td style="text-align:center;">
                        <form method="POST" action="ventas.php" style="display:inline;">
                            <input type="hidden" name="id_venta" value="<?php echo $v['id_venta']; ?>">
                            <input type="hidden" name="actualizar_estado" value="1">
                            <select name="nuevo_estado" class="sel-estado" onchange="this.form.submit()">
                                <option value="">— cambiar —</option>
                                <?php if ($v['estado_venta'] === 'pendiente'): ?>
                                <option value="confirmada">Confirmar</option>
                                <option value="cancelada">Cancelar</option>
                                <?php elseif ($v['estado_venta'] === 'confirmada'): ?>
                                <option value="enviada">Marcar Enviada</option>
                                <option value="cancelada">Cancelar</option>
                                <?php elseif ($v['estado_venta'] === 'enviada'): ?>
                                <option value="entregada">Marcar Entregada</option>
                                <?php else: ?>
                                <option disabled>Sin cambios</option>
                                <?php endif; ?>
                            </select>
                        </form>
                    </td>
                    <td style="text-align:center;">
                        <a href="ver_compra.php?id=<?php echo $v['id_venta']; ?>" class="btn-ver">
                            <i class="fas fa-eye"></i> Ver
                        </a>
                    </td>
                </tr>
            <?php endwhile; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ── PAGINACIÓN ─────────────────────────────────────────────────────────── -->
<?php if ($total_pags > 1): ?>
<div class="pag">
    <?php for ($i = 1; $i <= $total_pags; $i++): ?>
        <?php $qs = http_build_query(['estado'=>$estado,'buscar'=>$buscar,'fecha_desde'=>$fecha_desde,'fecha_hasta'=>$fecha_hasta,'page'=>$i]); ?>
        <?php if ($i === $page): ?>
            <span class="active"><?php echo $i; ?></span>
        <?php else: ?>
            <a href="?<?php echo $qs; ?>"><?php echo $i; ?></a>
        <?php endif; ?>
    <?php endfor; ?>
</div>
<?php endif; ?>

<?php empleado_render_footer(); ?>