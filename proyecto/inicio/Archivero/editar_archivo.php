<?php
require('conexion_archivero.php');

// Validar y obtener ID del archivo
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID de archivo inválido");
}
$archivo_id = (int)$_GET['id'];

// Obtener datos actuales del archivo y su ubicación
$query = "SELECT 
            a.*, 
            au.cajon_id,
            e.id AS edificio_id,
            p.id AS piso_id,
            o.id AS oficina_id,
            es.id AS estante_id
          FROM archivos a
          LEFT JOIN archivo_ubicacion au ON a.id = au.archivo_id
          LEFT JOIN cajones c ON au.cajon_id = c.id
          LEFT JOIN estantes es ON c.estante_id = es.id
          LEFT JOIN oficinas o ON es.oficina_id = o.id
          LEFT JOIN pisos p ON o.piso_id = p.id
          LEFT JOIN edificios e ON p.edificio_id = e.id
          WHERE a.id = ?";

$stmt = $conexion->prepare($query);
$stmt->bind_param('i', $archivo_id);
$stmt->execute();
$result = $stmt->get_result();
$archivo = $result->fetch_assoc();

// Procesar formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Validar y sanitizar datos
        $nombre = mysqli_real_escape_string($conexion, $_POST['nombre']);
        $tipo = mysqli_real_escape_string($conexion, $_POST['tipo']);
        $descripcion = mysqli_real_escape_string($conexion, $_POST['descripcion']);
        $nuevo_cajon_id = (int)$_POST['cajon_id'];

        // Iniciar transacción
        $conexion->begin_transaction();

        // Actualizar datos del archivo
        $update_archivo = $conexion->prepare("
            UPDATE archivos 
            SET nombre = ?, tipo = ?, descripcion = ?
            WHERE id = ?
        ");
        $update_archivo->bind_param('sssi', $nombre, $tipo, $descripcion, $archivo_id);
        $update_archivo->execute();

        // Actualizar ubicación si cambió
        if ($nuevo_cajon_id !== $archivo['cajon_id']) {
            $update_ubicacion = $conexion->prepare("
                UPDATE archivo_ubicacion 
                SET cajon_id = ? 
                WHERE archivo_id = ?
            ");
            $update_ubicacion->bind_param('ii', $nuevo_cajon_id, $archivo_id);
            $update_ubicacion->execute();
        }

        $conexion->commit();
        echo "<div class='success'>Archivo actualizado correctamente</div>";
        header("Refresh: 2; url=mostrar_archivos2.php");
        
    } catch (Exception $e) {
        $conexion->rollback();
        echo "<div class='error'>Error al actualizar: " . $e->getMessage() . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Archivo</title>
    <link rel="stylesheet" href="styles-acciones.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    // Pasar datos de ubicación actual a JavaScript
    const currentLocation = {
        edificio_id: <?= $archivo['edificio_id'] ?? 0 ?>,
        piso_id: <?= $archivo['piso_id'] ?? 0 ?>,
        oficina_id: <?= $archivo['oficina_id'] ?? 0 ?>,
        estante_id: <?= $archivo['estante_id'] ?? 0 ?>,
        cajon_id: <?= $archivo['cajon_id'] ?? 0 ?>
    };
    </script>
    <script src="ubicacion.js"></script>
</head>
<body>
    <h1>Editar Archivo</h1>
    <form action="editar_archivo.php?id=<?= $archivo_id ?>" method="POST">
        <!-- Campos existentes -->
        <div class="form-group">
            <label for="codigo">Código:</label>
            <input type="text" id="codigo" value="<?= htmlspecialchars($archivo['codigo']) ?>" readonly>
        </div>

        <div class="form-group">
            <label for="nombre">Nombre:</label>
            <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($archivo['nombre']) ?>" required>
        </div>

        <div class="form-group">
            <label for="tipo">Tipo:</label>
            <select id="tipo" name="tipo" required>
                <option value="Carpeta" <?= $archivo['tipo'] == 'Carpeta' ? 'selected' : '' ?>>Carpeta</option>
                <option value="Documento" <?= $archivo['tipo'] == 'Documento' ? 'selected' : '' ?>>Documento</option>
                <option value="PDF" <?= $archivo['tipo'] == 'PDF' ? 'selected' : '' ?>>PDF</option>
                <option value="Digital" <?= $archivo['tipo'] == 'Digital' ? 'selected' : '' ?>>Digital</option>
            </select>
        </div>

        <div class="form-group">
            <label for="descripcion">Descripción:</label>
            <textarea id="descripcion" name="descripcion"><?= htmlspecialchars($archivo['descripcion']) ?></textarea>
        </div>

        <!-- Selectores de ubicación -->
        <div class="form-group">
            <label>Ubicación:</label>
            <div class="location-selectors">
                <select id="edificio" name="edificio" required>
                    <option value="">Cargando edificios...</option>
                </select>
                
                <select id="piso" name="piso" disabled required>
                    <option value="">Seleccione edificio primero</option>
                </select>
                
                <select id="oficina" name="oficina" disabled required>
                    <option value="">Seleccione piso primero</option>
                </select>
                
                <select id="estante" name="estante" disabled required>
                    <option value="">Seleccione oficina primero</option>
                </select>
                
                <select id="cajon" name="cajon_id" disabled required>
                    <option value="">Seleccione estante primero</option>
                </select>
            </div>
        </div>

        <button type="submit">Guardar Cambios</button>
    </form>

    <a href="mostrar_archivos.php">← Volver al listado</a>
</body>
</html>

<?php
$conexion->close();
?>