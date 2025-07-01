<?php
require('../../conexion_archivero.php');
require('../../funciones.php');

//verificarAdministrador();

try {
    $cajon_id = $_GET['id'];
    
    // Obtener estante_id para redirección
    $stmt_estante = $conexion->prepare("SELECT estante_id FROM cajones WHERE id = ?");
    $stmt_estante->bind_param('i', $cajon_id);
    $stmt_estante->execute();
    $result = $stmt_estante->get_result();
    $cajon = $result->fetch_assoc();
    
    if (!$cajon) {
        throw new Exception("Cajón no encontrado");
    }
    
    $estante_id = $cajon['estante_id'];
    
    // Verificar dependencias (archivos asociados)
    $stmt_archivos = $conexion->prepare("SELECT COUNT(*) FROM archivo_ubicacion WHERE cajon_id = ?");
    $stmt_archivos->bind_param('i', $cajon_id);
    $stmt_archivos->execute();
    
    if ($stmt_archivos->get_result()->fetch_row()[0] > 0) {
        throw new Exception("No se puede eliminar: el cajón contiene archivos");
    }
    
    // Eliminar cajón
    $stmt_eliminar = $conexion->prepare("DELETE FROM cajones WHERE id = ?");
    $stmt_eliminar->bind_param('i', $cajon_id);
    $stmt_eliminar->execute();
    
    $_SESSION['mensaje'] = [
        'tipo' => 'success',
        'texto' => 'Cajón eliminado correctamente'
    ];
    
} catch (Exception $e) {
    $_SESSION['mensaje'] = [
        'tipo' => 'error',
        'texto' => $e->getMessage()
    ];
}

header('Location: ../cajones.php?estante_id='.$estante_id);
exit;