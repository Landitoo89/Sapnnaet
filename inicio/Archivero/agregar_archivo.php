<?php
require('conexion_archivero.php');

// Función para generar el código automático (actualizada)
function generarCodigo($cajon_id, $conexion) {
    $query = "SELECT codigo FROM cajones WHERE id = $cajon_id";
    $result = $conexion->query($query);
    $cajon = $result->fetch_assoc();
    $codigo_cajon = trim($cajon['codigo']);

    $query = "SELECT COUNT(*) AS total FROM archivo_ubicacion WHERE cajon_id = $cajon_id";
    $result = $conexion->query($query);
    $total = $result->fetch_assoc()['total'];

    $numero = str_pad($total + 1, 3, "0", STR_PAD_LEFT);
    return $codigo_cajon . "-" . $numero;
}

$mensaje = '';
$errores = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = mysqli_real_escape_string($conexion, $_POST['nombre']);
    $tipo = mysqli_real_escape_string($conexion, $_POST['tipo']);
    $descripcion = mysqli_real_escape_string($conexion, $_POST['descripcion']);
    $cajon_id = intval($_POST['cajon_id']);

    if (!$nombre) $errores[] = "El nombre del archivo es obligatorio.";
    if (!$tipo) $errores[] = "Debe seleccionar el tipo de archivo.";
    if (!$cajon_id) $errores[] = "Debe seleccionar la ubicación completa hasta el cajón.";

    if (empty($errores)) {
        $codigo = generarCodigo($cajon_id, $conexion);

        $stmt = $conexion->prepare("INSERT INTO archivos (codigo, nombre, tipo, descripcion, fecha_creacion) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssss", $codigo, $nombre, $tipo, $descripcion);

        if ($stmt->execute()) {
            $archivo_id = $conexion->insert_id;
            $stmt2 = $conexion->prepare("INSERT INTO archivo_ubicacion (archivo_id, cajon_id) VALUES (?, ?)");
            $stmt2->bind_param("ii", $archivo_id, $cajon_id);

            if ($stmt2->execute()) {
                $mensaje = "<div class='alert alert-success'><i class='bi bi-check-circle-fill me-2'></i>Archivo agregado con éxito. Código: <strong>$codigo</strong></div>";
            } else {
                $errores[] = "Error al registrar ubicación: " . $conexion->error;
            }
            $stmt2->close();
        } else {
            $errores[] = "Error al crear archivo: " . $conexion->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agregar Archivo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background: #f4f6fa;
        }
        .form-container-custom {
            background: white;
            padding: 2.5rem;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.08);
            max-width: 700px;
            margin: 2rem auto;
        }
        .form-section-header {
            background-color: #f0f2f5;
            padding: 1rem 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            border-left: 5px solid #3498db;
        }
        .form-section-header h2 {
            margin-bottom: 0;
            color: #2c3e50;
        }
        .btn-primary-custom {
            background: #3498db;
            border: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1.1rem;
            padding: 12px 32px;
            transition: background 0.2s;
        }
        .btn-primary-custom:hover {
            background: #217dbb;
        }
        .location-selectors select {
            margin-bottom: 10px;
        }
        .alert {
            margin-bottom: 1.5rem;
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
<div class="container">
    <div class="form-container-custom">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="text-primary mb-0">
                <i class="bi bi-folder-plus me-2"></i>Agregar Archivo
            </h1>
            <a href="mostrar_archivos.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-2"></i>Volver a la lista
            </a>
        </div>

        <?php if(!empty($errores)): ?>
            <div class="alert alert-danger" role="alert">
                <h5 class="alert-heading"><i class="bi bi-exclamation-triangle-fill me-2"></i>Errores:</h5>
                <ul class="mb-0">
                    <?php foreach ($errores as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?= $mensaje ?>

        <form action="agregar_archivo.php" method="POST" autocomplete="off">
            <div class="form-section-header">
                <h2><i class="bi bi-info-circle-fill me-2"></i>Datos del Archivo</h2>
            </div>
            <div class="mb-3">
                <label for="nombre" class="form-label">Nombre del archivo*:</label>
                <input type="text" id="nombre" name="nombre" class="form-control" required value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label for="tipo" class="form-label">Tipo de archivo*:</label>
                <select id="tipo" name="tipo" class="form-select" required>
                    <option value="">Seleccione...</option>
                    <option value="Carpeta" <?= (($_POST['tipo'] ?? '') == 'Carpeta') ? 'selected' : '' ?>>Carpeta</option>
                    <option value="Documento" <?= (($_POST['tipo'] ?? '') == 'Documento') ? 'selected' : '' ?>>Documento</option>
                    <option value="PDF" <?= (($_POST['tipo'] ?? '') == 'PDF') ? 'selected' : '' ?>>PDF</option>
                    <option value="Libro" <?= (($_POST['tipo'] ?? '') == 'Libro') ? 'selected' : '' ?>>Libro</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="descripcion" class="form-label">Descripción:</label>
                <textarea id="descripcion" name="descripcion" class="form-control"><?= htmlspecialchars($_POST['descripcion'] ?? '') ?></textarea>
            </div>
            <div class="form-section-header">
                <h2><i class="bi bi-geo-alt-fill me-2"></i>Ubicación</h2>
            </div>
            <div class="row mb-3 location-selectors">
                <div class="col-md-6 mb-3">
                    <label for="edificio" class="form-label">Edificio*:</label>
                    <select id="edificio" name="edificio" class="form-select" required>
                        <option value="">Seleccione Edificio</option>
                        <?php
                        $edificios = $conexion->query("SELECT id, nombre FROM edificios");
                        while ($e = $edificios->fetch_assoc()) {
                            $selected = (($_POST['edificio'] ?? '') == $e['id']) ? 'selected' : '';
                            echo "<option value='{$e['id']}' $selected>{$e['nombre']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="piso" class="form-label">Piso*:</label>
                    <select id="piso" name="piso" class="form-select" disabled required>
                        <option value="">Seleccione Piso</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="oficina" class="form-label">Oficina*:</label>
                    <select id="oficina" name="oficina" class="form-select" disabled required>
                        <option value="">Seleccione Oficina</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="estante" class="form-label">Estante*:</label>
                    <select id="estante" name="estante" class="form-select" disabled required>
                        <option value="">Seleccione Estante</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="cajon_id" class="form-label">Cajón*:</label>
                    <select id="cajon" name="cajon_id" class="form-select" disabled required>
                        <option value="">Seleccione Cajón</option>
                    </select>
                </div>
            </div>
            <div class="d-grid gap-2 mt-4">
                <button type="submit" class="btn btn-primary-custom">
                    <i class="bi bi-save me-2"></i>Agregar Archivo
                </button>
            </div>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    // Cargar pisos al seleccionar edificio
    $('#edificio').change(function() {
        var edificioId = $(this).val();
        if (edificioId) {
            loadOptions('piso', 'get_pisos.php?edificio_id=' + edificioId);
        }
    });

    // Cargar oficinas al seleccionar piso
    $('#piso').change(function() {
        var pisoId = $(this).val();
        if (pisoId) {
            loadOptions('oficina', 'get_oficinas.php?piso_id=' + pisoId);
        }
    });

    // Cargar estantes al seleccionar oficina
    $('#oficina').change(function() {
        var oficinaId = $(this).val();
        if (oficinaId) {
            loadOptions('estante', 'get_estantes.php?oficina_id=' + oficinaId);
        }
    });

    // Cargar cajones al seleccionar estante
    $('#estante').change(function() {
        var estanteId = $(this).val();
        if (estanteId) {
            loadOptions('cajon', 'get_cajones.php?estante_id=' + estanteId);
        }
    });

    // Función genérica para cargar opciones
    function loadOptions(target, url) {
        $('#' + target).prop('disabled', true).html('<option value="">Cargando...</option>');
        $.get(url)
            .done(function(data) {
                $('#' + target).html(data).prop('disabled', false);
            })
            .fail(function(jqXHR, textStatus) {
                console.error("Error cargando " + target + ":", textStatus);
                $('#' + target).html('<option value="">Error al cargar</option>');
            });
    }

    // Repoblar selects si hay POST (para experiencia de usuario)
    <?php if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['edificio'])): ?>
        loadOptions('piso', 'get_pisos.php?edificio_id=<?= intval($_POST['edificio']) ?>');
    <?php endif; ?>
    <?php if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['piso'])): ?>
        loadOptions('oficina', 'get_oficinas.php?piso_id=<?= intval($_POST['piso']) ?>');
    <?php endif; ?>
    <?php if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['oficina'])): ?>
        loadOptions('estante', 'get_estantes.php?oficina_id=<?= intval($_POST['oficina']) ?>');
    <?php endif; ?>
    <?php if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['estante'])): ?>
        loadOptions('cajon', 'get_cajones.php?estante_id=<?= intval($_POST['estante']) ?>');
    <?php endif; ?>
});
</script>
</body>
</html>
<?php
$conexion->close();
?>