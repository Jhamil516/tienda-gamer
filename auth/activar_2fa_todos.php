<?php
require_once __DIR__ . '/../config/db.php';

$sql = "UPDATE usuarios SET estado_2fa = 1";
if ($conn->query($sql)) {
    echo '2FA activado para usuarios existentes. Filas afectadas: ' . $conn->affected_rows;
} else {
    echo 'Error al activar 2FA: ' . $conn->error;
}
?>