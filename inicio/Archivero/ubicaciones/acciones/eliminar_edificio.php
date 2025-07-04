// eliminar_edificio.php
<?php
require('../../conexion_archivero.php');

function eliminarJerarquiaCompleta($edificio_id) {
    global $conexion;
    
    // 1. Obtener todos los pisos del edificio
    $query_pisos = "SELECT id FROM pisos WHERE edificio_id = ?";
    $stmt_pisos = $conexion->prepare($query_pisos);
    $stmt_pisos->bind_param('i', $edificio_id);
    $stmt_pisos->execute();
    $result_pisos = $stmt_pisos->get_result();
    
    while ($piso = $result_pisos->fetch_assoc()) {
        // 2. Para cada piso, obtener sus oficinas
        $query_oficinas = "SELECT id FROM oficinas WHERE piso_id = ?";
        $stmt_oficinas = $conexion->prepare($query_oficinas);
        $stmt_oficinas->bind_param('i', $piso['id']);
        $stmt_oficinas->execute();
        $result_oficinas = $stmt_oficinas->get_result();
        
        while ($oficina = $result_oficinas->fetch_assoc()) {
            // 3. Para cada oficina, obtener sus estantes
            $query_estantes = "SELECT id FROM estantes WHERE oficina_id = ?";
            $stmt_estantes = $conexion->prepare($query_estantes);
            $stmt_estantes->bind_param('i', $oficina['id']);
            $stmt_estantes->execute();
            $result_estantes = $stmt_estantes->get_result();
            
            while ($estante = $result_estantes->fetch_assoc()) {
                // 4. Eliminar archivos asociados a los cajones de este estante
                $query_eliminar_archivos = "
                    DELETE a FROM archivos a
                    JOIN archivo_ubicacion au ON a.id = au.archivo_id
                    JOIN cajones c ON au.cajon_id = c.id
                    WHERE c.estante_id = ?
                ";
                $stmt_eliminar_archivos = $conexion->prepare($query_eliminar_archivos);
                $stmt_eliminar_archivos->bind_param('i', $estante['id']);
                $stmt_eliminar_archivos->execute();
                
                // 5. Eliminar relaciones archivo_ubicacion de estos cajones
                $query_eliminar_relaciones = "
                    DELETE au FROM archivo_ubicacion au
                    JOIN cajones c ON au.cajon_id = c.id
                    WHERE c.estante_id = ?
                ";
                $stmt_eliminar_relaciones = $conexion->prepare($query_eliminar_relaciones);
                $stmt_eliminar_relaciones->bind_param('i', $estante['id']);
                $stmt_eliminar_relaciones->execute();
                
                // 6. Eliminar cajones del estante
                $query_eliminar_cajones = "DELETE FROM cajones WHERE estante_id = ?";
                $stmt_eliminar_cajones = $conexion->prepare($query_eliminar_cajones);
                $stmt_eliminar_cajones->bind_param('i', $estante['id']);
                $stmt_eliminar_cajones->execute();
            }
            
            // 7. Eliminar estantes de la oficina
            $query_eliminar_estantes = "DELETE FROM estantes WHERE oficina_id = ?";
            $stmt_eliminar_estantes = $conexion->prepare($query_eliminar_estantes);
            $stmt_eliminar_estantes->bind_param('i', $oficina['id']);
            $stmt_eliminar_estantes->execute();
        }
        
        // 8. Eliminar oficinas del piso
        $query_eliminar_oficinas = "DELETE FROM oficinas WHERE piso_id = ?";
        $stmt_eliminar_oficinas = $conexion->prepare($query_eliminar_oficinas);
        $stmt_eliminar_oficinas->bind_param('i', $piso['id']);
        $stmt_eliminar_oficinas->execute();
    }
    
    // 9. Finalmente, eliminar los pisos del edificio
    $query_eliminar_pisos = "DELETE FROM pisos WHERE edificio_id = ?";
    $stmt_eliminar_pisos = $conexion->prepare($query_eliminar_pisos);
    $stmt_eliminar_pisos->bind_param('i', $edificio_id);
    $stmt_eliminar_pisos->execute();
    
    // 10. Eliminar el edificio
    $query_eliminar_edificio = "DELETE FROM edificios WHERE id = ?";
    $stmt_eliminar_edificio = $conexion->prepare($query_eliminar_edificio);
    $stmt_eliminar_edificio->bind_param('i', $edificio_id);
    $stmt_eliminar_edificio->execute();
    
    // Verificar que se eliminó el edificio
    if ($stmt_eliminar_edificio->affected_rows === 0) {
        throw new Exception("No se pudo eliminar el edificio. Puede que no exista.");
    }
}

// Lógica principal
try {
    $edificio_id = ($_GET['id']);
    eliminarJerarquiaCompleta($edificio_id);
    header('Location: ../index.php?success=1');
} catch (Exception $e) {
    header('Location: ../index.php?error=' . urlencode($e->getMessage()));
}
?>