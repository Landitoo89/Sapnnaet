<?php
require('conexion_archivero.php');
$edificio_id = intval($_GET['edificio_id']);
$result = $conexion->query("SELECT id, numero FROM pisos WHERE edificio_id = $edificio_id");
echo '<option value="">Seleccione Piso</option>';
while ($row = $result->fetch_assoc()) {
    echo "<option value='{$row['id']}'>{$row['numero']}</option>";
}
?>