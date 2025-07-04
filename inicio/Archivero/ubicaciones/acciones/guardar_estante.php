<?php
require('../../conexion_archivero.php');
require('../../funciones.php');

//verificarAdministrador();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $oficina_id = $_POST['oficina_id'];
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $codigo = trim($_POST['codigo']);
        $descripcion = trim($_POST['descripcion']);

        // Validaciones
        if (empty($codigo)) {
            throw new Exception("El código es requerido");
        }

        // Verificar duplicados
        $query_duplicado = "SELECT id FROM estantes 
                          WHERE oficina_id = ? AND codigo = ? AND id != ?";
        $stmt_duplicado = $conexion->prepare($query_duplicado);
        $stmt_duplicado->bind_param('isi', $oficina_id, $codigo, $id);
        $stmt_duplicado->execute();
        
        if ($stmt_duplicado->get_result()->num_rows > 0) {
            throw new Exception("Ya existe un estante con este código en la oficina");
        }

        // Guardar datos
        if ($id > 0) {
            $query = "UPDATE estantes SET 
                      codigo = ?, descripcion = ?
                      WHERE id = ?";
            $stmt = $conexion->prepare($query);
            $stmt->bind_param('ssi', $codigo, $descripcion, $id);
        } else {
            $query = "INSERT INTO estantes 
                     (oficina_id, codigo, descripcion) 
                     VALUES (?, ?, ?)";
            $stmt = $conexion->prepare($query);
            $stmt->bind_param('iss', $oficina_id, $codigo, $descripcion);
        }

        $stmt->execute();

        $_SESSION['mensaje'] = [
            'tipo' => 'success',
            'texto' => 'Estante ' . ($id > 0 ? 'actualizado' : 'creado') . ' correctamente'
        ];

        header('Location: ../estantes.php?oficina_id='.$oficina_id);
        exit;

    } catch (Exception $e) {
        $_SESSION['mensaje'] = [
            'tipo' => 'error',
            'texto' => 'Error: ' . $e->getMessage()
        ];
        
        header('Location: ' . ($id > 0 ? "../forms/estante_form.php?id=$id" : "../forms/estante_form.php?oficina_id=$oficina_id"));
        exit;
    }
}