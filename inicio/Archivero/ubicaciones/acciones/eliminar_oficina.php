<?php
require('../../conexion_archivero.php');
require('../../funciones.php');

//verificarAdministrador();

try {
    $oficina_id = $_GET['id'];
    
    // Obtener piso_id para redirección
    $stmt_piso = $conexion->prepare("SELECT piso_id FROM oficinas WHERE id = ?");
    $stmt_piso->bind_param('i', $oficina_id);
    $stmt_piso->execute();
    $result = $stmt_piso->get_result();
    $oficina = $result->fetch_assoc();
    
    if (!$oficina) {
        throw new Exception("Oficina no encontrada");
    }
    
    $piso_id = $oficina['piso_id'];
    
    // Verificar dependencias
    $stmt_estantes = $conexion->prepare("SELECT COUNT(*) FROM estantes WHERE oficina_id = ?");
    $stmt_estantes->bind_param('i', $oficina_id);
    $stmt_estantes->execute();
    
    if ($stmt_estantes->get_result()->fetch_row()[0] > 0) {
        throw new Exception("No se puede eliminar: la oficina tiene estantes asociados");
    }
    
    // Eliminar oficina
    $stmt_eliminar = $conexion->prepare("DELETE FROM oficinas WHERE id = ?");
    $stmt_eliminar->bind_param('i', $oficina_id);
    $stmt_eliminar->execute();
    
    $_SESSION['mensaje'] = [
        'tipo' => 'success',
        'texto' => 'Oficina eliminada correctamente'
    ];
    
} catch (Exception $e) {
    $_SESSION['mensaje'] = [
        'tipo' => 'error',
        'texto' => $e->getMessage()
    ];
}

header('Location: ../oficinas.php?piso_id='.$piso_id);
exit;