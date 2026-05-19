<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/funciones.php';

if (!es_admin()) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

$error = '';
$exito = '';

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

    if (empty($nombre)) {
        $error = 'El nombre de la promoción es requerido';
    } elseif ($valor <= 0) {
        $error = 'El valor debe ser mayor a 0';
    } elseif (empty($fecha_inicio) || empty($fecha_fin)) {
        $error = 'Las fechas son requeridas';
    } else {
        $query = "INSERT INTO promociones (nombre_promocion, descripcion, tipo, valor, fecha_inicio, fecha_fin, codigo_cupon, cantidad_minima_compra, usos_limites, activa, es_global)
                  VALUES ('$nombre', '$descripcion', '$tipo', $valor, '$fecha_inicio', '$fecha_fin', '$codigo_cupon', $cantidad_minima, $usos_limites, 1, 1)";
        
        if ($conn->query($query)) {
            $exito = 'Promoción creada exitosamente';
            header('Refresh: 2; url=promociones.php');
        } else {
            $error = 'Error al crear la promoción: ' . $conn->error;
        }
    }
}

require_once __DIR__ . '/admin_header.php';
admin_render_header('Nueva Promoción', 'Promociones', 'fas fa-tag');
?>

<style>
    .section { background: rgba(26, 26, 46, 0.95); border: 1px solid rgba(98, 0, 255, 0.35); border-radius: 16px; padding: 25px; max-width: 600px; }
    .form-group { margin-bottom: 15px; }
    label { color: #00d4ff; font-weight: bold; margin-bottom: 5px; display: block; }
    input, textarea, select { background: rgba(255, 255, 255, 0.9) !important; color: #0f172a !important; border: 1px solid rgba(98, 0, 255, 0.3) !important; padding: 10px !important; border-radius: 5px !important; width: 100% !important; }
    input:focus, textarea:focus, select:focus { border-color: #00d4ff !important; outline: none !important; }
</style>

<div class="section">
    <h3 style="color: #00d4ff; margin-bottom: 20px;"><i class="fas fa-plus"></i> Crear Nueva Promoción</h3>
    
    <?php if ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($exito): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($exito); ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label for="nombre">Nombre de la Promoción *</label>
            <input type="text" id="nombre" name="nombre" required placeholder="Ej: Black Friday">
        </div>

        <div class="form-group">
            <label for="descripcion">Descripción</label>
            <textarea id="descripcion" name="descripcion" rows="3" placeholder="Detalles de la promoción"></textarea>
        </div>

        <div class="form-group">
            <label for="tipo">Tipo *</label>
            <select id="tipo" name="tipo" required>
                <option value="porcentaje">Porcentaje (%)</option>
                <option value="cantidad_fija">Cantidad Fija ($)</option>
                <option value="combo">Combo</option>
                <option value="compra_llevar">Compra y Lleva</option>
            </select>
        </div>

        <div class="form-group">
            <label for="valor">Valor *</label>
            <input type="number" id="valor" name="valor" step="0.01" min="0" required placeholder="10">
        </div>

        <div class="form-group">
            <label for="fecha_inicio">Fecha de Inicio *</label>
            <input type="date" id="fecha_inicio" name="fecha_inicio" required>
        </div>

        <div class="form-group">
            <label for="fecha_fin">Fecha de Fin *</label>
            <input type="date" id="fecha_fin" name="fecha_fin" required>
        </div>

        <div class="form-group">
            <label for="codigo_cupon">Código Cupón</label>
            <input type="text" id="codigo_cupon" name="codigo_cupon" placeholder="VERANO2024">
        </div>

        <div class="form-group">
            <label for="cantidad_minima">Compra Mínima ($)</label>
            <input type="number" id="cantidad_minima" name="cantidad_minima" step="0.01" min="0" placeholder="0">
        </div>

        <div class="form-group">
            <label for="usos_limites">Límite de Usos (0 = ilimitado)</label>
            <input type="number" id="usos_limites" name="usos_limites" min="0" placeholder="100">
        </div>

        <button type="submit" class="btn btn-success"><i class="fas fa-check"></i> Crear Promoción</button>
        <a href="promociones.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancelar</a>
    </form>
</div>

<?php admin_render_footer(); ?>
