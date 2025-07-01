<?php
// Inicia la sesión si aún no está iniciada
session_start();

// Habilitar la visualización de errores solo para depuración.
// Asegúrate de DESHABILITAR esto en un entorno de producción por seguridad.
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Incluye el archivo de conexión a la base de datos
// Es CRÍTICO que la ruta a 'conexion.php' sea correcta.
// Si 'eliminar-reposo.php' está en 'tu_proyecto/modulos/reposos/',
// y 'conexion.php' está en 'tu_proyecto/', entonces '../conexion.php' es correcto.
// Si no, ajusta la ruta. Por ejemplo, si 'conexion.php' está en la misma carpeta, usa 'conexion.php'.
if (!file_exists('../conexion.php')) {
    // Si el archivo de conexión no se encuentra, registra un error y notifica al usuario.
    // Esto es útil para depurar problemas de rutas.
    error_log("Error: conexion.php no encontrado en la ruta esperada para eliminar-reposo.php.");
    $_SESSION['mensaje'] = [
        'titulo' => 'Error Crítico',
        'contenido' => 'El archivo de conexión a la base de datos no se encontró. Contacte al administrador.',
        'tipo' => 'danger'
    ];
    // Intenta redirigir a gestion-reposos.php.
    header('Location: gestion-reposos.php');
    exit;
}
require '../conexion.php';


// Validación mejorada de parámetros GET
// Asegura que el ID de reposo esté presente y sea un número válido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['mensaje'] = [
        'titulo' => 'Error',
        'contenido' => 'ID de reposo inválido.',
        'tipo' => 'danger'
    ];
    // Redirige a la página de gestión de reposos si el ID es inválido
    header('Location: gestion-reposos.php');
    exit; // Termina la ejecución del script
}

// Obtener la página actual para mantener la paginación después de la eliminación
$pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;

try {
    // Inicia una transacción para asegurar que todas las operaciones se completen o ninguna
    $conn->beginTransaction();

    // 1. Obtener el id_pers (ID del empleado) asociado al reposo que se va a eliminar
    // También obtenemos la ruta del archivo adjunto para poder eliminarlo del servidor.
    $stmt = $conn->prepare("SELECT id_pers, ruta_archivo_adjunto FROM reposos WHERE id_reposo = ?");
    $stmt->execute([$_GET['id']]);
    $reposo_data = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verifica si se encontró el registro de reposo
    if (!$reposo_data) {
        throw new Exception("Registro de reposo no encontrado.");
    }

    $id_pers = $reposo_data['id_pers'];
    $ruta_archivo_adjunto = $reposo_data['ruta_archivo_adjunto'];

    // 2. Reactivar al empleado en la tabla datos_laborales
    // Se establece 'activo' = 1 (empleado activo) y 'estado' = 'activo' (estado laboral normal).
    $stmt = $conn->prepare("UPDATE datos_laborales SET activo = 1, estado = 'activo' WHERE id_pers = ?");
    $stmt->execute([$id_pers]);

    // 3. Eliminar el archivo adjunto del servidor si existe
    // Se verifica si la ruta no está vacía y si el archivo realmente existe antes de intentar eliminarlo.
    // La ruta 'uploads/reposos/' se asume relativa al archivo PHP que la está ejecutando.
    // Si la carpeta 'uploads' está en la raíz de tu proyecto, y este archivo está en una subcarpeta,
    // es posible que necesites ajustar la ruta (ej. '../../uploads/reposos/').
    if ($ruta_archivo_adjunto && file_exists($ruta_archivo_adjunto)) {
        unlink($ruta_archivo_adjunto); // Elimina el archivo físico del servidor
    }

    // 4. Eliminar el registro de reposo de la tabla `reposos`
    $stmt = $conn->prepare("DELETE FROM reposos WHERE id_reposo = ?");
    $stmt->execute([$_GET['id']]);

    // Confirma la transacción si todas las operaciones fueron exitosas
    $conn->commit();

    // Establece un mensaje de éxito en la sesión para mostrar en la página de gestión
    $_SESSION['mensaje'] = [
        'titulo' => '¡Éxito!',
        'contenido' => 'Reposo eliminado y empleado reactivado.',
        'tipo' => 'success'
    ];

} catch (Exception $e) {
    // En caso de cualquier error durante la transacción, revierte todos los cambios
    $conn->rollBack();
    // Establece un mensaje de error en la sesión
    $_SESSION['mensaje'] = [
        'titulo' => 'Error',
        'contenido' => 'Error al eliminar el reposo: ' . $e->getMessage(),
        'tipo' => 'danger'
    ];
}

// Redirección a la página de gestión de reposos, manteniendo la paginación actual
// Asegúrate de que 'gestion-reposos.php' esté en la misma carpeta que este archivo,
// o ajusta la ruta si está en una ubicación diferente (ej. '../gestion-reposos.php').
header("Location: gestion-reposos.php?pagina=$pagina");
exit; // Termina la ejecución del script
?>
```
Una vez que resuelvas el problema del 404 (asegurándote de que el archivo PHP sea accesible en la URL correcta), el código debería funcionar como se espera para eliminar el registro y redirig