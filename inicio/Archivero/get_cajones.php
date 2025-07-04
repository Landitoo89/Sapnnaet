<?php
require('conexion_archivero.php');
header('Content-Type: text/html; charset=utf-8');

// Debug: Mostrar parámetro recibido
error_log("GET estante_id: " . print_r($_GET['estante_id'], true));

// Validar parámetro
$estante_id = isset($_GET['estante_id']) ? intval($_GET['estante_id']) : 0;

if ($estante_id <= 0) {
    die('<option value="">Seleccione un estante válido</option>');
}

try {
    $stmt = $conexion->prepare("
        SELECT id, codigo, descripcion 
        FROM cajones 
        WHERE estante_id = ?
        ORDER BY codigo
    ");
    $stmt->bind_param('i', $estante_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $html = '<option value="">Seleccione cajón</option>';
    while ($row = $result->fetch_assoc()) {
        $texto = htmlspecialchars($row['codigo']);
        if (!empty($row['descripcion'])) {
            $texto .= ' - ' . htmlspecialchars($row['descripcion']);
        }
        $html .= "<option value='{$row['id']}'>$texto</option>";
    }

    echo $html;

} catch (Exception $e) {
    error_log("Error en get_cajones.php: " . $e->getMessage());
    echo '<option value="">Error al cargar cajones</option>';
} finally {
    $stmt->close();
    $conexion->close();
}
?>