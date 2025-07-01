<?php
require('../../conexion_archivero.php');

//verificarAdministrador();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $edificio_id = $_POST['edificio_id'];
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $numero = trim($_POST['numero']);
        $descripcion = trim($_POST['descripcion']);

        // Validaciones
        if (empty($numero)) {
            throw new Exception("El número de piso es requerido");
        }

        // Verificar duplicados (mismo número en el mismo edificio)
        $query_duplicado = "SELECT id FROM pisos 
                           WHERE edificio_id = ? AND numero = ? AND id != ?";
        $stmt_duplicado = $conexion->prepare($query_duplicado);
        $stmt_duplicado->bind_param('isi', $edificio_id, $numero, $id);
        $stmt_duplicado->execute();
        
        if ($stmt_duplicado->get_result()->num_rows > 0) {
            throw new Exception("Ya existe un piso con este número en el edificio");
        }

        // Guardar datos
        if ($id > 0) {
            // Actualización
            $query = "UPDATE pisos SET 
                      numero = ?, descripcion = ?
                      WHERE id = ?";
            $stmt = $conexion->prepare($query);
            $stmt->bind_param('ssi', $numero, $descripcion, $id);
        } else {
            // Inserción
            $query = "INSERT INTO pisos 
                     (edificio_id, numero, descripcion) 
                     VALUES (?, ?, ?)";
            $stmt = $conexion->prepare($query);
            $stmt->bind_param('iss', $edificio_id, $numero, $descripcion);
        }

        $stmt->execute();

        $_SESSION['mensaje'] = [
            'tipo' => 'success',
            'texto' => 'Piso ' . ($id > 0 ? 'actualizado' : 'creado') . ' correctamente'
        ];

        header('Location: ../pisos.php?edificio_id='.$edificio_id);
        exit;

    } catch (Exception $e) {
        $_SESSION['mensaje'] = [
            'tipo' => 'error',
            'texto' => 'Error: ' . $e->getMessage()
        ];
        
        $url_retorno = $id > 0 
            ? "../forms/piso_form.php?id=$id" 
            : "../forms/piso_form.php?edificio_id=$edificio_id";
            
        header('Location: ' . $url_retorno);
        exit;
    }
}