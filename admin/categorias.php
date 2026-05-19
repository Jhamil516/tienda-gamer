<?php
session_start();
require_once __DIR__ . '/admin_header.php';

$categorias = obtener_categorias($conn);

admin_render_header('Categorías', 'Categorías', 'fas fa-th-large');
?>

<style>
/* ── Paleta del proyecto (misma que admin_header) ── */
:root {
    --primary:   #6200ff;
    --accent:    #00d4ff;
    --dark-bg:   #0a0a0a;
    --card-bg:   #1a1a2e;
    --text-light:#e0e0e0;
    --green:     #10b981;
    --red:       #ef4444;
}

.cat-wrap {
    background: rgba(26,26,46,0.95);
    border: 1px solid rgba(98,0,255,0.35);
    border-radius: 16px;
    overflow: hidden;
}

.cat-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    border-bottom: 1px solid rgba(98,0,255,0.25);
}

.cat-toolbar h3 {
    color: var(--accent);
    margin: 0;
    font-size: 1.2rem;
    font-weight: 700;
}

.btn-nueva-cat {
    background: linear-gradient(135deg, var(--primary), #9333ea);
    border: none;
    color: #fff;
    padding: 9px 18px;
    border-radius: 9px;
    font-size: .88rem;
    font-weight: 600;
    text-decoration: none;
    transition: opacity .2s, transform .2s;
    display: inline-flex;
    align-items: center;
    gap: 7px;
}
.btn-nueva-cat:hover { opacity:.85; color:#fff; transform:translateY(-1px); }

.cat-table { width: 100%; border-collapse: collapse; }

.cat-table thead tr {
    background: rgba(98,0,255,0.18);
    border-bottom: 2px solid var(--primary);
}

.cat-table th {
    padding: 13px 16px;
    color: var(--accent);
    font-size: .82rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .5px;
    text-align: left;
}
.cat-table th:last-child { text-align: center; }

.cat-table td {
    padding: 13px 16px;
    color: var(--text-light);
    font-size: .9rem;
    border-bottom: 1px solid rgba(98,0,255,0.12);
    background: transparent;
}

.cat-table tbody tr:hover td { background: rgba(98,0,255,0.07); }
.cat-table tbody tr:last-child td { border-bottom: none; }

.icono-cell { font-size: 1.5rem; text-align: center; }

.badge-activa   { background:rgba(16,185,129,.18); color:var(--green); border:1px solid var(--green); padding:4px 12px; border-radius:20px; font-size:.78rem; font-weight:700; }
.badge-inactiva { background:rgba(239,68,68,.18);  color:var(--red);   border:1px solid var(--red);   padding:4px 12px; border-radius:20px; font-size:.78rem; font-weight:700; }

.btn-edit {
    background: rgba(98,0,255,0.2);
    color: var(--accent);
    border: 1px solid rgba(98,0,255,0.5);
    border-radius: 7px;
    padding: 5px 13px;
    font-size: .82rem;
    text-decoration: none;
    transition: background .2s;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}
.btn-edit:hover { background: rgba(98,0,255,0.4); color: var(--accent); }

.btn-del {
    background: rgba(239,68,68,.15);
    color: #f87171;
    border: 1px solid rgba(239,68,68,.5);
    border-radius: 7px;
    padding: 5px 13px;
    font-size: .82rem;
    text-decoration: none;
    transition: background .2s;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}
.btn-del:hover { background: rgba(239,68,68,.3); color: #fca5a5; }

.acciones-cell { text-align: center; display: flex; gap: 8px; justify-content: center; }

.vacio-cat {
    padding: 50px 20px;
    text-align: center;
    color: #666;
}
.vacio-cat i { font-size: 3rem; margin-bottom: 12px; display: block; color: rgba(98,0,255,0.35); }
</style>

<div class="cat-wrap">
    <div class="cat-toolbar">
        <h3><i class="fas fa-th-large"></i> Gestión de Categorías</h3>
        <a href="nueva_categoria.php" class="btn-nueva-cat">
            <i class="fas fa-plus"></i> Nueva Categoría
        </a>
    </div>

    <?php if (empty($categorias)): ?>
    <div class="vacio-cat">
        <i class="fas fa-folder-open"></i>
        <p>No hay categorías registradas aún.</p>
        <a href="nueva_categoria.php" class="btn-nueva-cat">Crear primera categoría</a>
    </div>

    <?php else: ?>
    <div style="overflow-x:auto;">
        <table class="cat-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Icono</th>
                    <th>Orden</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($categorias as $cat): ?>
                <tr>
                    <td style="color:#555; font-size:.82rem;"><?php echo $cat['id_categoria']; ?></td>
                    <td style="font-weight:600;"><?php echo htmlspecialchars($cat['nombre_categoria']); ?></td>
                    <td style="color:#aaa; font-size:.85rem; max-width:260px;">
                        <?php echo htmlspecialchars(mb_substr($cat['descripcion'] ?? '', 0, 60)); ?>
                        <?php if (mb_strlen($cat['descripcion'] ?? '') > 60) echo '…'; ?>
                    </td>
                    <td class="icono-cell"><?php echo htmlspecialchars($cat['icono'] ?? '🗂️'); ?></td>
                    <td style="color:#aaa;"><?php echo htmlspecialchars($cat['orden'] ?? '—'); ?></td>
                    <td>
                        <?php if (!empty($cat['activa'])): ?>
                            <span class="badge-activa"><i class="fas fa-check"></i> Activa</span>
                        <?php else: ?>
                            <span class="badge-inactiva"><i class="fas fa-times"></i> Inactiva</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="acciones-cell">
                            <a href="editar_categoria.php?id=<?php echo $cat['id_categoria']; ?>" class="btn-edit">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                            <a href="eliminar_categoria.php?id=<?php echo $cat['id_categoria']; ?>"
                                class="btn-del"
                                onclick="return confirm('¿Eliminar la categoría «<?php echo htmlspecialchars($cat['nombre_categoria']); ?>»? Esta acción no se puede deshacer.')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php admin_render_footer(); ?>