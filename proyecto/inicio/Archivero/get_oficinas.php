<?php
require('conexion_archivero.php');
$piso_id = intval($_GET['piso_id']);
$result = $conexion->query("SELECT id, codigo, nombre FROM oficinas WHERE piso_id = $piso_id");
echo '<option value="">Seleccione Oficina</option>';
while ($row = $result->fetch_assoc()) {
    $label = $row['codigo'] . " - " . $row['nombre'];
    echo "<option value='{$row['id']}'>$label</option>";
}
?>