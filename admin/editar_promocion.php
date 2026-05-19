<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/funciones.php';

if (!es_admin()) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

$id = intval($_GET['id'] ?? 0);
$error = '';
$exito = '';
$promocion = null;

if ($id > 0) {
    $result = $conn->query("SELECT * FROM promociones WHERE id_promocion = $id LIMIT 1");
    if ($result && $result->num_rows === 1) {
        $promocion = $result->fetch_assoc();
    }
}

if (!$promocion) {
    header('Location: promociones.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = sanitizar_sql($conn, $_POST['nombre'] ?? '');
    $descripcion = sanitizar_sql($conn, $_POST['descripcion'] ?? '');
    $tipo = sanitizar_sql($conn, $_POST['tipo'] ?? 'porcentaje');
    $valor = floatval($_POST['valor'] ?? 0);
    $fecha_inicio = sanitizar_sql($conn, $_POST['fecha_inicio'] ?? '');
    $fecha_fin = sanitizar_sql($conn, $_POST['fecha_fin'] ?? '');
    $codigo_cupon = sanitizar_sql($conn, $_POST['codigo_cupon'] ?? '');
    $cantidad_minima = floatval($_POST['cantidad_minima'] ?? 0);
    $usos_limites = intval($_POST['usos_limites'] ?? 0);
    $activa = isset($_POST['activa']) ? 1 : 0;

    if (empty($nombre)) {
        $error = 'El nombre de la promoción es requerido';
    } elseif ($valor <= 0) {
        $error = 'El valor debe ser mayor a 0';
    } elseif (empty($fecha_inicio) || empty($fecha_fin)) {
        $error = 'Las fechas son requeridas';
    } else {
        $query = "UPDATE promociones SET
            nombre_promocion = '$nombre',
            descripcion = '$descripcion',
            tipo = '$tipo',
            valor = $valor,
            fecha_inicio = '$fecha_inicio',
            fecha_fin = '$fecha_fin',
            codigo_cupon = '$codigo_cupon',
            cantidad_minima_compra = $cantidad_minima,
            usos_limites = $usos_limites,
            activa = $activa,
            es_global = 1
            WHERE id_promocion = $id";

        if ($conn->query($query)) {
            $exito = 'Promoción actualizada correctamente';
            $promocion = array_merge($promocion, [
                'nombre_promocion' => $nombre,
                'descripcion' => $descripcion,
                'tipo' => $tipo,
                'valor' => $valor,
                'fecha_inicio' => $fecha_inicio,
                'fecha_fin' => $fecha_fin,
                'codigo_cupon' => $codigo_cupon,
                'cantidad_minima_compra' => $cantidad_minima,
                'usos_limites' => $usos_limites,
                'activa' => $activa,
            ]);
        } else {
            $error = 'Error al actualizar la promoción: ' . $conn->error;
        }
    }
}

require_once __DIR__ . '/admin_header.php';
admin_render_header('Editar Promoción', 'Promociones', 'fas fa-tag');
?>

<style>
    .section { background: rgba(26, 26, 46, 0.95); border: 1px solid rgba(98, 0, 255, 0.35); border-radius: 16px; padding: 25px; max-width: 720px; }
    .form-group { margin-bottom: 15px; }
    label { color: #00d4ff; font-weight: bold; margin-bottom: 5px; display: block; }
    input, textarea, select { background: rgba(255, 255, 255, 0.9) !important; color: #0f172a !important; border: 1px solid rgba(98, 0, 255, 0.3) !important; padding: 10px !important; border-radius: 5px !important; width: 100% !important; }
    input:focus, textarea:focus, select:focus { border-color: #00d4ff !important; outline: none !important; }
    .btn-group { display: flex; gap: 10px; flex-wrap: wrap; }
</style>

<div class="section">
    <h3 style="color: #00d4ff; margin-bottom: 20px;"><i class="fas fa-edit"></i> Editar Promoción</h3>

    <?php if ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($exito): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($exito); ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label for="nombre">Nombre de la Promoción *</label>
            <input type="text" id="nombre" name="nombre" required value="<?php echo htmlspecialchars($promocion['nombre_promocion']); ?>">
        </div>

        <div class="form-group">
            <label for="descripcion">Descripción</label>
            <textarea id="descripcion" name="descripcion" rows="3"><?php echo htmlspecialchars($promocion['descripcion']); ?></textarea>
        </div>

        <div class="form-group">
            <label for="tipo">Tipo *</label>
            <select id="tipo" name="tipo" required>
                <option value="porcentaje" <?php echo $promocion['tipo'] === 'porcentaje' ? 'selected' : ''; ?>>Porcentaje (%)</option>
                <option value="cantidad_fija" <?php echo $promocion['tipo'] === 'cantidad_fija' ? 'selected' : ''; ?>>Cantidad Fija (BOB)</option>
                <option value="combo" <?php echo $promocion['tipo'] === 'combo' ? 'selected' : ''; ?>>Combo</option>
                <option value="compra_llevar" <?php echo $promocion['tipo'] === 'compra_llevar' ? 'selected' : ''; ?>>Compra y Lleva</option>
            </select>
        </div>

        <div class="form-group">
            <label for="valor">Valor *</label>
            <input type="number" id="valor" name="valor" step="0.01" min="0" required value="<?php echo htmlspecialchars($promocion['valor']); ?>">
        </div>

        <div class="form-group">
            <label for="fecha_inicio">Fecha de Inicio *</label>
            <input type="date" id="fecha_inicio" name="fecha_inicio" required value="<?php echo htmlspecialchars($promocion['fecha_inicio']); ?>">
        </div>

        <div class="form-group">
            <label for="fecha_fin">Fecha de Fin *</label>
            <input type="date" id="fecha_fin" name="fecha_fin" required value="<?php echo htmlspecialchars($promocion['fecha_fin']); ?>">
        </div>

        <div class="form-group">
            <label for="codigo_cupon">Código Cupón</label>
            <input type="text" id="codigo_cupon" name="codigo_cupon" value="<?php echo htmlspecialchars($promocion['codigo_cupon']); ?>">
        </div>

        <div class="form-group">
            <label for="cantidad_minima">Compra Mínima (BOB)</label>
            <input type="number" id="cantidad_minima" name="cantidad_minima" step="0.01" min="0" value="<?php echo htmlspecialchars($promocion['cantidad_minima_compra']); ?>">
        </div>

        <div class="form-group">
            <label for="usos_limites">Límite de Usos</label>
            <input type="number" id="usos_limites" name="usos_limites" min="0" value="<?php echo htmlspecialchars($promocion['usos_limites']); ?>">
            <small class="form-text text-muted">0 = ilimitado</small>
        </div>

        <div class="form-group">
            <input type="hidden" name="activa" value="0">
            <div class="form-check">
                <input type="checkbox" id="activa" name="activa" class="form-check-input" value="1" <?php echo $promocion['activa'] ? 'checked' : ''; ?>>
                <label class="form-check-label" for="activa">Activa</label>
            </div>
            <small class="text-muted">Marca para activar la promoción; desmarca para dejarla inactiva.</small>
        </div>

        <div class="btn-group">
            <button type="submit" class="btn btn-success"><i class="fas fa-check"></i> Guardar cambios</button>
            <a href="promociones.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
        </div>
    </form>
</div>

<?php admin_render_footer(); ?>
