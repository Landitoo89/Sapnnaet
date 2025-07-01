<?php
require('../../conexion_archivero.php');
require('../../funciones.php');

//verificarAdministrador();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $piso_id = $_POST['piso_id'];
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $codigo = trim($_POST['codigo']);
        $nombre = trim($_POST['nombre']);

        // Validaciones
        if (empty($codigo) || empty($nombre)) {
            throw new Exception("Todos los campos son requeridos");
        }

        // Verificar duplicados
        $query_duplicado = "SELECT id FROM oficinas 
                          WHERE piso_id = ? AND (codigo = ? OR nombre = ?) AND id != ?";
        $stmt_duplicado = $conexion->prepare($query_duplicado);
        $stmt_duplicado->bind_param('issi', $piso_id, $codigo, $nombre, $id);
        $stmt_duplicado->execute();
        
        if ($stmt_duplicado->get_result()->num_rows > 0) {
            throw new Exception("Ya existe una oficina con ese código o nombre en este piso");
        }

        // Guardar datos
        if ($id > 0) {
            $query = "UPDATE oficinas SET 
                      codigo = ?, nombre = ?
                      WHERE id = ?";
            $stmt = $conexion->prepare($query);
            $stmt->bind_param('ssi', $codigo, $nombre, $id);
        } else {
            $query = "INSERT INTO oficinas 
                     (piso_id, codigo, nombre) 
                     VALUES (?, ?, ?)";
            $stmt = $conexion->prepare($query);
            $stmt->bind_param('iss', $piso_id, $codigo, $nombre);
        }

        $stmt->execute();

        $_SESSION['mensaje'] = [
            'tipo' => 'success',
            'texto' => 'Oficina ' . ($id > 0 ? 'actualizada' : 'creada') . ' correctamente'
        ];

        header('Location: ../oficinas.php?piso_id='.$piso_id);
        exit;

    } catch (Exception $e) {
        $_SESSION['mensaje'] = [
            'tipo' => 'error',
            'texto' => 'Error: ' . $e->getMessage()
        ];
        
        header('Location: ' . ($id > 0 ? "../forms/oficina_form.php?id=$id" : "../forms/oficina_form.php?piso_id=$piso_id"));
        exit;
    }
}