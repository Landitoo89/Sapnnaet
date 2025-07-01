<?php
require('conexion_archivero.php');

// Validar y obtener ID del archivo
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID de archivo inválido");
}
$archivo_id = (int)$_GET['id'];

try {
    // Iniciar transacción
    $conexion->begin_transaction();

    // Eliminar relación de ubicación (prepared statement)
    $stmt1 = $conexion->prepare("DELETE FROM archivo_ubicacion WHERE archivo_id = ?");
    $stmt1->bind_param('i', $archivo_id);
    $stmt1->execute();

    // Eliminar archivo principal (prepared statement)
    $stmt2 = $conexion->prepare("DELETE FROM archivos WHERE id = ?");
    $stmt2->bind_param('i', $archivo_id);
    $stmt2->execute();

    // Confirmar cambios
    $conexion->commit();
    
    $mensaje = "Archivo eliminado exitosamente";
    $tipo_mensaje = "success";

} catch (Exception $e) {
    // Revertir cambios en caso de error
    $conexion->rollback();
    $mensaje = "Error al eliminar: " . $e->getMessage();
    $tipo_mensaje = "error";
}

// Cerrar conexión
$conexion->close();

// Mostrar mensaje y redirigir
echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Eliminando archivo...</title>
    <link rel='stylesheet' href='styles.css'>
    <script>
        setTimeout(function() {
            window.location.href = 'mostrar_archivos2.php';
        }, 2000);
    </script>
</head>
<body>
    <div class='$tipo_mensaje'>$mensaje</div>
</body>
</html>";
?>