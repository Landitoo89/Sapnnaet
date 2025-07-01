<?php
require('conexion_archivero.php');
header('Content-Type: text/html; charset=utf-8');

// Validar parámetro
$oficina_id = isset($_GET['oficina_id']) ? intval($_GET['oficina_id']) : 0;

if ($oficina_id <= 0) {
    die('<option value="">Seleccione una oficina válida</option>');
}

try {
    $stmt = $conexion->prepare("
        SELECT id, codigo, descripcion 
        FROM estantes 
        WHERE oficina_id = ?
        ORDER BY codigo
    ");
    $stmt->bind_param('i', $oficina_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $html = '<option value="">Seleccione estante</option>';
    while ($row = $result->fetch_assoc()) {
        $texto = htmlspecialchars($row['codigo']);
        if (!empty($row['descripcion'])) {
            $texto .= ' - ' . htmlspecialchars($row['descripcion']);
        }
        $html .= "<option value='{$row['id']}'>$texto</option>";
    }

    echo $html;

} catch (Exception $e) {
    error_log("Error en get_estantes.php: " . $e->getMessage());
    echo '<option value="">Error al cargar estantes</option>';
} finally {
    $stmt->close();
    $conexion->close();
}
?>