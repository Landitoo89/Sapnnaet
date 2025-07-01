<?php
require('../../conexion_archivero.php');
require('../../funciones.php');

//verificarAdministrador();

try {
    $estante_id = $_GET['id'];
    
    // Obtener oficina_id para redirección
    $stmt_oficina = $conexion->prepare("SELECT oficina_id FROM estantes WHERE id = ?");
    $stmt_oficina->bind_param('i', $estante_id);
    $stmt_oficina->execute();
    $result = $stmt_oficina->get_result();
    $estante = $result->fetch_assoc();
    
    if (!$estante) {
        throw new Exception("Estante no encontrado");
    }
    
    $oficina_id = $estante['oficina_id'];
    
    // Verificar dependencias (cajones asociados)
    $stmt_cajones = $conexion->prepare("SELECT COUNT(*) FROM cajones WHERE estante_id = ?");
    $stmt_cajones->bind_param('i', $estante_id);
    $stmt_cajones->execute();
    
    if ($stmt_cajones->get_result()->fetch_row()[0] > 0) {
        throw new Exception("No se puede eliminar: el estante tiene cajones asociados");
    }
    
    // Eliminar estante
    $stmt_eliminar = $conexion->prepare("DELETE FROM estantes WHERE id = ?");
    $stmt_eliminar->bind_param('i', $estante_id);
    $stmt_eliminar->execute();
    
    $_SESSION['mensaje'] = [
        'tipo' => 'success',
        'texto' => 'Estante eliminado correctamente'
    ];
    
} catch (Exception $e) {
    $_SESSION['mensaje'] = [
        'tipo' => 'error',
        'texto' => $e->getMessage()
    ];
}

header('Location: ../estantes.php?oficina_id='.$oficina_id);
exit;