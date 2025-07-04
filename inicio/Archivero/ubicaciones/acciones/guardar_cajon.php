<?php
session_start(); // Asegurar que la sesión está iniciada
require('../../conexion_archivero.php');
require('../../funciones.php');

// Habilitar reporte de errores para debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

//verificarAdministrador();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Verificar conexión a la base de datos
        if ($conexion->connect_error) {
            throw new Exception("Error de conexión: " . $conexion->connect_error);
        }

        $estante_id = $_POST['estante_id'];
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $codigo = trim($_POST['codigo']);
        $descripcion = trim($_POST['descripcion']);

        // Validaciones esenciales
        if (empty($codigo)) {
            throw new Exception("El código es requerido");
        }

        // Validar formato del código
        if (!preg_match('/^[A-Z0-9-]{1,20}$/', $codigo)) {
            throw new Exception("Formato inválido. Use mayúsculas, números y guiones (ej: CAJ-001)");
        }

        // Verificar duplicados
        $query_duplicado = "SELECT id FROM cajones 
                          WHERE estante_id = ? 
                          AND codigo = ?
                          AND id != ?";
        $stmt_duplicado = $conexion->prepare($query_duplicado);
        $stmt_duplicado->bind_param('isi', $estante_id, $codigo, $id);
        
        if (!$stmt_duplicado->execute()) {
            throw new Exception("Error en verificación de duplicados: " . $stmt_duplicado->error);
        }
        
        if ($stmt_duplicado->get_result()->num_rows > 0) {
            throw new Exception("¡El código $codigo ya existe en este estante!");
        }

        // Operación de guardado
        if ($id > 0) {
            $query = "UPDATE cajones SET 
                      codigo = ?, 
                      descripcion = ?
                      WHERE id = ?";
            $stmt = $conexion->prepare($query);
            $stmt->bind_param('ssi', $codigo, $descripcion, $id);
        } else {
            $query = "INSERT INTO cajones 
                     (estante_id, codigo, descripcion) 
                     VALUES (?, ?, ?)";
            $stmt = $conexion->prepare($query);
            $stmt->bind_param('iss', $estante_id, $codigo, $descripcion);
        }

        if (!$stmt->execute()) {
            throw new Exception("Error al guardar: " . $stmt->error);
        }

        $_SESSION['mensaje'] = [
            'tipo' => 'success',
            'texto' => 'Cajón ' . ($id > 0 ? 'actualizado' : 'creado') . ' correctamente'
        ];

        header('Location: ../cajones.php?estante_id=' . $estante_id);
        exit();

    } catch (Exception $e) {
        $_SESSION['mensaje'] = [
            'tipo' => 'error',
            'texto' => 'Error: ' . $e->getMessage()
        ];
        
        $params = $id > 0 ? "id=$id" : "estante_id=$estante_id";
        header("Location: ../forms/cajon_form.php?$params");
        exit();
    }
} else {
    header("Location: ../cajones.php");
    exit();
}