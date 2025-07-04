<?php
require('../../conexion_archivero.php');

//verificarAdministrador();

try {
    $piso_id = $_GET['id'];
    
    // Obtener edificio_id para redirección
    $stmt_edificio = $conexion->prepare("SELECT edificio_id FROM pisos WHERE id = ?");
    $stmt_edificio->bind_param('i', $piso_id);
    $stmt_edificio->execute();
    $result = $stmt_edificio->get_result();
    $piso = $result->fetch_assoc();
    
    if (!$piso) {
        throw new Exception("Piso no encontrado");
    }
    
    $edificio_id = $piso['edificio_id'];
    
    // Verificar si tiene oficinas
    $stmt_oficinas = $conexion->prepare("SELECT COUNT(*) FROM oficinas WHERE piso_id = ?");
    $stmt_oficinas->bind_param('i', $piso_id);
    $stmt_oficinas->execute();
    
    if ($stmt_oficinas->get_result()->fetch_row()[0] > 0) {
        throw new Exception("No se puede eliminar: el piso tiene oficinas asociadas");
    }
    
    // Eliminar piso
    $stmt_eliminar = $conexion->prepare("DELETE FROM pisos WHERE id = ?");
    $stmt_eliminar->bind_param('i', $piso_id);
    $stmt_eliminar->execute();
    
    $_SESSION['mensaje'] = [
        'tipo' => 'success',
        'texto' => 'Piso eliminado correctamente'
    ];
    
} catch (Exception $e) {
    $_SESSION['mensaje'] = [
        'tipo' => 'error',
        'texto' => $e->getMessage()
    ];
}

header('Location: ../pisos.php?edificio_id='.$edificio_id);
exit;