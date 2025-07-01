<?php
session_start();
require '../conexion.php';

// Habilitar la visualización de errores solo para depuración.
// Asegúrate de DESHABILITAR esto en un entorno de producción por seguridad.
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Validación mejorada de parámetros GET
// Asegura que el ID de vacación esté presente y sea un número válido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['mensaje'] = [
        'titulo' => 'Error',
        'contenido' => 'ID de vacación inválido.',
        'tipo' => 'danger'
    ];
    // Redirige a la página de gestión de vacaciones si el ID es inválido
    header('Location: gestion-vacaciones.php');
    exit; // Termina la ejecución del script
}

// Obtener la página actual para mantener la paginación después de la eliminación
$pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;

try {
    // Inicia una transacción para asegurar que todas las operaciones se completen o ninguna
    $conn->beginTransaction();

    // 1. Obtener id_pers asociado a la vacación que se va a eliminar
    $stmt = $conn->prepare("SELECT id_pers FROM vacaciones WHERE id_vacaciones = ?");
    $stmt->execute([$_GET['id']]);
    $vacacion_data = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verifica si se encontró el registro de vacación
    if (!$vacacion_data) {
        throw new Exception("Registro de vacaciones no encontrado.");
    }

    $id_pers = $vacacion_data['id_pers'];

    error_log("--- Eliminando Vacación ID: " . $_GET['id'] . " para Empleado ID: " . $id_pers . " ---");

    // 2. Reactivar al empleado en la tabla datos_laborales
    // Se establece 'estado' = 'activo' (estado laboral normal).
    error_log("Attempting to update datos_laborales for id_pers: " . $id_pers . " to estado = 'activo'.");
    $stmt = $conn->prepare("UPDATE datos_laborales SET estado = 'activo' WHERE id_pers = ?");
    $stmt->execute([$id_pers]);
    error_log("datos_laborales update executed. Rows affected: " . $stmt->rowCount());

    // 3. Eliminar TODOS los periodos de vacaciones asociados a este empleado.
    // Esto es para que se puedan regenerar limpiamente la próxima vez que se consulte al empleado.
    error_log("Deleting all vacation periods for employee ID: " . $id_pers);
    $stmt_delete_periods = $conn->prepare("DELETE FROM periodos_vacaciones WHERE id_pers = ?");
    $stmt_delete_periods->execute([$id_pers]);
    error_log("Deleted " . $stmt_delete_periods->rowCount() . " periods for employee ID: " . $id_pers);

    // 4. Eliminar el registro de vacación de la tabla `vacaciones`
    error_log("Deleting vacation record with ID: " . $_GET['id']);
    $stmt = $conn->prepare("DELETE FROM vacaciones WHERE id_vacaciones = ?");
    $stmt->execute([$_GET['id']]);
    error_log("Vacation record deleted. Rows affected: " . $stmt->rowCount());

    // Confirma la transacción si todas las operaciones fueron exitosas
    $conn->commit();
    error_log("Transaction committed successfully.");

    // Establece un mensaje de éxito en la sesión para mostrar en la página de gestión
    $_SESSION['mensaje'] = [
        'titulo' => '¡Éxito!',
        'contenido' => 'Registro de vacación eliminado y empleado reactivado. Los períodos se regenerarán al consultar el empleado.',
        'tipo' => 'success'
    ];

} catch (Exception $e) {
    // En caso de cualquier error durante la transacción, revierte todos los cambios
    $conn->rollBack();
    error_log("Transaction rolled back. Error: " . $e->getMessage());
    // Establece un mensaje de error en la sesión
    $_SESSION['mensaje'] = [
        'titulo' => 'Error',
        'contenido' => 'Error al eliminar la vacación: ' . $e->getMessage(),
        'tipo' => 'danger'
    ];
}

// Redirección a la página de gestión de vacaciones, manteniendo la paginación actual
header("Location: gestion-vacaciones.php?pagina=$pagina");
exit; // Termina la ejecución del script
?>
