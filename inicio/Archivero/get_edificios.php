<?php
require('conexion_archivero.php');
$result = $conexion->query("SELECT id, nombre FROM edificios");
echo '<option value="">Seleccione Edificio</option>';
while ($row = $result->fetch_assoc()) {
    echo "<option value='{$row['id']}'>{$row['nombre']}</option>";
}
?>