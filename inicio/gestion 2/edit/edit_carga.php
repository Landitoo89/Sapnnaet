<?php
session_start();
require_once __DIR__ . '/../conexion/conexion_db.php';
$conn = new mysqli($servidor, $usuario, $contraseña, $basedatos);

// ==== FUNCIÓN PARA REGISTRAR LOGS ====
function registrarLog($conn, $user_id, $event_type, $details) {
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
    $stmt = $conn->prepare("INSERT INTO action_logs (user_id, event_type, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $user_id, $event_type, $details, $ip_address, $user_agent);
    $stmt->execute();
    $stmt->close();
}

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

$current_user_id = $_SESSION['usuario']['id'] ?? null;

$id_carga = $_GET['id_carga'] ?? 0;
$registro = [];
$errores = [];

if ($id_carga <= 0) {
    $_SESSION['error'] = "ID de carga familiar no válido.";
    header("Location: ../gestion_carga.php");
    exit();
}

// Obtener datos del familiar para editar, incluyendo fecha_nacimiento_familiar
$stmt = $conn->prepare("
    SELECT cf.id_carga, cf.id_socioeconomico, cf.parentesco, cf.nombres_familiar, cf.apellidos_familiar, 
           cf.fecha_nacimiento_familiar, cf.cedula_familiar, cf.genero_familiar, cf.tiene_discapacidad, 
           cf.detalle_discapacidad, cf.archivo_deficit, 
           dp.nombres AS nombres_trabajador, dp.apellidos AS apellidos_trabajador, dp.id_pers
    FROM carga_familiar cf
    JOIN datos_socioeconomicos ds ON cf.id_socioeconomico = ds.id_socioeconomico
    JOIN datos_personales dp ON ds.id_pers = dp.id_pers
    WHERE cf.id_carga = ?
");
$stmt->bind_param("i", $id_carga);
$stmt->execute();
$result = $stmt->get_result();
$registro = $result->fetch_assoc();
$stmt->close();

if (!$registro) {
    $_SESSION['error'] = "Carga familiar no encontrada.";
    header("Location: ../gestion_carga.php");
    exit();
}

// ========== LOG DE VISUALIZACIÓN DE FORMULARIO ==========
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $id_carga > 0) {
    registrarLog(
        $conn,
        $current_user_id,
        'view_edit_carga_familiar_form',
        "Visualización de edición de carga familiar para ID Persona: {$registro['id_pers']}, Trabajador: {$registro['nombres_trabajador']} {$registro['apellidos_trabajador']}, Familiar: {$registro['nombres_familiar']} {$registro['apellidos_familiar']} (Parentesco: {$registro['parentesco']})"
    );
}

// Procesar formulario POST para actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $parentesco = $_POST['parentesco'] ?? NULL;
    $nombres_familiar = trim($_POST['nombres_familiar'] ?? '');
    $apellidos_familiar = trim($_POST['apellidos_familiar'] ?? '');
    $fecha_nacimiento_familiar = $_POST['fecha_nacimiento_familiar'] ?? NULL;
    $edad_familiar_calculated = NULL;
    if (!empty($fecha_nacimiento_familiar)) {
        try {
            $dob = new DateTime($fecha_nacimiento_familiar);
            $today = new DateTime();
            $edad_familiar_calculated = $today->diff($dob)->y;
        } catch (Exception $e) {
            $errores[] = "Formato de fecha de nacimiento inválido.";
        }
    }

    $cedula_familiar = trim($_POST['cedula_familiar'] ?? '');
    $genero_familiar = $_POST['genero_familiar'] ?? NULL;
    $tiene_discapacidad = $_POST['discapacidad'] ?? 'No';
    $detalle_discapacidad = ($tiene_discapacidad === 'Sí') ? (trim($_POST['detalle_discapacidad'] ?? '')) : NULL;

    // Validaciones
    if (empty($nombres_familiar)) $errores[] = "Los nombres del familiar son obligatorios.";
    if (empty($apellidos_familiar)) $errores[] = "Los apellidos del familiar son obligatorios.";
    if (empty($fecha_nacimiento_familiar)) $errores[] = "La fecha de nacimiento del familiar es obligatoria.";
    if (empty($parentesco)) $errores[] = "El parentesco es obligatorio.";

    $archivo_deficit = $registro['archivo_deficit']; // Mantener el archivo actual por defecto

    // Manejo de la subida de un nuevo archivo
    if (isset($_FILES['archivo_deficit']) && $_FILES['archivo_deficit']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/cargas/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileName = uniqid() . '_' . basename($_FILES['archivo_deficit']['name']);
        $targetFilePath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['archivo_deficit']['tmp_name'], $targetFilePath)) {
            // Si se sube un nuevo archivo, eliminar el anterior si existe
            if (!empty($registro['archivo_deficit']) && file_exists($registro['archivo_deficit'])) {
                unlink($registro['archivo_deficit']);
            }
            $archivo_deficit = $targetFilePath;
        } else {
            $errores[] = "Error al subir el nuevo archivo de déficit.";
        }
    } elseif ($tiene_discapacidad === 'Sí' && empty($archivo_deficit) && (isset($_FILES['archivo_deficit']) && $_FILES['archivo_deficit']['error'] === UPLOAD_ERR_NO_FILE)) {
         $errores[] = "Si el familiar tiene discapacidad, el archivo de déficit es obligatorio.";
    }

    if (empty($errores)) {
        try {
            $stmt_update = $conn->prepare("UPDATE carga_familiar SET 
                parentesco = ?, 
                nombres_familiar = ?, 
                apellidos_familiar = ?, 
                fecha_nacimiento_familiar = ?, 
                cedula_familiar = ?, 
                genero_familiar = ?, 
                tiene_discapacidad = ?, 
                detalle_discapacidad = ?, 
                archivo_deficit = ?
                WHERE id_carga = ?");

            $stmt_update->bind_param("sssssssssi",
                $parentesco,
                $nombres_familiar,
                $apellidos_familiar,
                $fecha_nacimiento_familiar,
                $cedula_familiar,
                $genero_familiar,
                $tiene_discapacidad,
                $detalle_discapacidad,
                $archivo_deficit,
                $id_carga
            );

            if ($stmt_update->execute()) {
                // ==== LOG DE EDICIÓN EXITOSA ====
                $log_details = "Edición de carga familiar para ID Persona: {$registro['id_pers']}, Trabajador: {$registro['nombres_trabajador']} {$registro['apellidos_trabajador']}. Familiar actualizado: $nombres_familiar $apellidos_familiar (Parentesco: $parentesco, Discapacidad: $tiene_discapacidad";
                if ($tiene_discapacidad === 'Sí' && $detalle_discapacidad) $log_details .= ", Detalle: $detalle_discapacidad";
                if ($cedula_familiar) $log_details .= ", Cédula: $cedula_familiar";
                $log_details .= ")";
                registrarLog(
                    $conn,
                    $current_user_id,
                    'carga_familiar_edited',
                    $log_details
                );

                $_SESSION['mensaje'] = "Carga familiar actualizada correctamente.";
                header("Location: ../gestion_carga.php");
                exit();
            } else {
                $errores[] = "Error al actualizar la carga familiar: " . $stmt_update->error;
            }
            $stmt_update->close();

        } catch (Exception $e) {
            $errores[] = "Error inesperado al actualizar: " . $e->getMessage();
            // ==== LOG DE ERROR ====
            $log_details = "Error al editar carga familiar para ID Persona: {$registro['id_pers']}, Trabajador: {$registro['nombres_trabajador']} {$registro['apellidos_trabajador']}. Error: {$e->getMessage()}";
            registrarLog(
                $conn,
                $current_user_id,
                'carga_familiar_edit_error',
                $log_details
            );
        }
    } else {
        // ==== LOG DE VALIDACIÓN FALLIDA ====
        $detalles_errores = implode('; ', $errores);
        $log_details = "Validación fallida al editar carga familiar para ID Persona: {$registro['id_pers']}, Trabajador: {$registro['nombres_trabajador']} {$registro['apellidos_trabajador']}. Errores: $detalles_errores";
        registrarLog(
            $conn,
            $current_user_id,
            'carga_familiar_edit_validation_failed',
            $log_details
        );
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Carga Familiar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/formularios_styles.css">
    <style>
        .form-container-custom {
            background: white;
            padding: 2.5rem;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            max-width: 900px;
            margin: 2rem auto;
        }
        .form-section-header {
            background-color: #f0f2f5;
            padding: 1rem 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            border-left: 5px solid var(--secondary-color);
        }
        .form-section-header h2 {
            margin-bottom: 0;
            color: var(--primary-color);
        }
        .conditional-field {
            margin-top: 1rem;
        }
        .radio-group label {
            margin-right: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-container-custom">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="text-primary mb-0">
                    <i class="bi bi-pencil-square me-2"></i>Editar Carga Familiar
                </h1>
                <a href="../gestion_carga.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Volver a Gestión de Cargas
                </a>
            </div>

            <?php if(!empty($errores)): ?>
                <div class="alert alert-danger mb-4" role="alert">
                    <h4 class="alert-heading"><i class="bi bi-exclamation-triangle-fill me-2"></i>Errores de Validación:</h4>
                    <ul class="mb-0">
                        <?php foreach ($errores as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if(isset($_SESSION['mensaje'])): ?>
                <div class="alert alert-<?= $_SESSION['mensaje']['tipo'] ?? 'success' ?> alert-dismissible fade show" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="bi <?= ($_SESSION['mensaje']['tipo'] ?? 'success') == 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?> me-2"></i>
                        <div>
                            <h5 class="mb-0"><?= htmlspecialchars($_SESSION['mensaje']['titulo'] ?? 'Mensaje') ?></h5>
                            <p class="mb-0"><?= htmlspecialchars($_SESSION['mensaje']['contenido'] ?? $_SESSION['mensaje']) ?></p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['mensaje']); ?>
            <?php endif; ?>
        
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id_carga" value="<?= htmlspecialchars($registro['id_carga']) ?>">
                
                <div class="form-section-header">
                    <h2><i class="bi bi-person-fill me-2"></i>Información del Trabajador Principal</h2>
                </div>
                <div class="mb-4">
                    <label class="form-label">Trabajador:</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($registro['nombres_trabajador'] . ' ' . $registro['apellidos_trabajador']) ?>" readonly>
                </div>

                <div class="form-section-header mt-5">
                    <h2><i class="bi bi-people-fill me-2"></i>Datos del Familiar</h2>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="parentesco">Parentesco*:</label>
                        <select name="parentesco" id="parentesco" class="form-select" required>
                            <option value="">Seleccione...</option>
                            <option value="Cónyuge" <?= ($registro['parentesco'] == 'Cónyuge') ? 'selected' : '' ?>>Cónyuge</option>
                            <option value="Hijo/a" <?= ($registro['parentesco'] == 'Hijo/a') ? 'selected' : '' ?>>Hijo/a</option>
                            <option value="Padre" <?= ($registro['parentesco'] == 'Padre') ? 'selected' : '' ?>>Padre</option>
                            <option value="Madre" <?= ($registro['parentesco'] == 'Madre') ? 'selected' : '' ?>>Madre</option>
                            <option value="Hermano/a" <?= ($registro['parentesco'] == 'Hermano/a') ? 'selected' : '' ?>>Hermano/a</option>
                            <option value="Otro" <?= ($registro['parentesco'] == 'Otro') ? 'selected' : '' ?>>Otro</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="cedula_familiar">Cédula:</label>
                        <input type="text" name="cedula_familiar" id="cedula_familiar" class="form-control" value="<?= htmlspecialchars($registro['cedula_familiar'] ?? '') ?>">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="nombres_familiar">Nombres*:</label>
                        <input type="text" name="nombres_familiar" id="nombres_familiar" class="form-control" value="<?= htmlspecialchars($registro['nombres_familiar'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="apellidos_familiar">Apellidos*:</label>
                        <input type="text" name="apellidos_familiar" id="apellidos_familiar" class="form-control" value="<?= htmlspecialchars($registro['apellidos_familiar'] ?? '') ?>" required>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="fecha_nacimiento_familiar">Fecha de Nacimiento*:</label>
                        <input type="date" name="fecha_nacimiento_familiar" id="fecha_nacimiento_familiar" class="form-control" value="<?= htmlspecialchars($registro['fecha_nacimiento_familiar'] ?? '') ?>" required>
                        <!-- El campo oculto de edad_familiar_hidden ya no es necesario para guardar en DB -->
                        <!-- <input type="hidden" name="edad_familiar" id="edad_familiar_hidden"> -->
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Edad:</label>
                        <!-- Mostrar la edad calculada por JS -->
                        <input type="text" id="edad_familiar_display" class="form-control" readonly>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label" for="genero_familiar">Género:</label>
                    <select name="genero_familiar" id="genero_familiar" class="form-select">
                        <option value="">Seleccione...</option>
                        <option value="Masculino" <?= ($registro['genero_familiar'] == 'Masculino') ? 'selected' : '' ?>>Masculino</option>
                        <option value="Femenino" <?= ($registro['genero_familiar'] == 'Femenino') ? 'selected' : '' ?>>Femenino</option>
                        <option value="Otro" <?= ($registro['genero_familiar'] == 'Otro') ? 'selected' : '' ?>>Otro</option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">¿Tiene discapacidad?*</label>
                    <div class="form-check form-check-inline">
                        <input type="radio" name="discapacidad" value="Sí" 
                               class="form-check-input" id="discapacidad_si" 
                               onchange="toggleDiscapacidadFields(true)" <?= ($registro['tiene_discapacidad'] == 'Sí') ? 'checked' : '' ?> required>
                        <label class="form-check-label" for="discapacidad_si">Sí</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input type="radio" name="discapacidad" value="No" 
                               class="form-check-input" id="discapacidad_no" 
                               onchange="toggleDiscapacidadFields(false)" <?= ($registro['tiene_discapacidad'] == 'No') ? 'checked' : '' ?>>
                        <label class="form-check-label" for="discapacidad_no">No</label>
                    </div>
                </div>

                <div id="detalle_discapacidad_container" class="conditional-field" style="display: <?= ($registro['tiene_discapacidad'] == 'Sí') ? 'block' : 'none' ?>;">
                    <div class="mb-3">
                        <label class="form-label" for="detalle_discapacidad">Detalle de la discapacidad*:</label>
                        <input type="text" name="detalle_discapacidad" id="detalle_discapacidad" class="form-control" 
                               value="<?= htmlspecialchars($registro['detalle_discapacidad'] ?? '') ?>"
                               <?= ($registro['tiene_discapacidad'] == 'Sí') ? '' : 'disabled' ?>>
                    </div>
                </div>

                <div id="archivo_deficit_container" class="conditional-field" style="display: <?= ($registro['tiene_discapacidad'] == 'Sí') ? 'block' : 'none' ?>;">
                    <div class="mb-3">
                        <label class="form-label" for="archivo_deficit">Archivo de déficit (opcional para actualizar):</label>
                        <input type="file" name="archivo_deficit" id="archivo_deficit" class="form-control" 
                               <?= ($registro['tiene_discapacidad'] == 'Sí') ? '' : 'disabled' ?>>
                        <?php if (!empty($registro['archivo_deficit'])): ?>
                            <small class="text-muted mt-1 d-block">Archivo actual: 
                                <a href="<?= htmlspecialchars($registro['archivo_deficit']) ?>" target="_blank">
                                    <?= htmlspecialchars(basename($registro['archivo_deficit'])) ?>
                                </a>
                            </small>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="d-grid gap-2 mt-4">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-save me-2"></i>Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function calcularEdad() {
            const dateInput = document.getElementById('fecha_nacimiento_familiar');
            const ageDisplayInput = document.getElementById('edad_familiar_display');
            // Ya no hay edad_familiar_hidden para guardar, solo para mostrar

            const dobString = dateInput.value;
            if (!dobString) {
                ageDisplayInput.value = '';
                // ageHiddenInput.value = ''; // Ya no existe
                return;
            }

            // Añadir 'T00:00:00' para evitar problemas de zona horaria con new Date()
            const dob = new Date(dobString + 'T00:00:00');
            const today = new Date();
            let age = today.getFullYear() - dob.getFullYear();
            const m = today.getMonth() - dob.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) {
                age--;
            }
            ageDisplayInput.value = age + ' años';
            // ageHiddenInput.value = age; // Ya no existe
        }

        function toggleDiscapacidadFields(show) {
            const detalleContainer = document.getElementById('detalle_discapacidad_container');
            const archivoContainer = document.getElementById('archivo_deficit_container');
            const detalleInput = document.getElementById('detalle_discapacidad');
            const archivoInput = document.getElementById('archivo_deficit');

            if (show) {
                detalleContainer.style.display = 'block';
                archivoContainer.style.display = 'block';
                detalleInput.removeAttribute('disabled');
                archivoInput.removeAttribute('disabled');
                detalleInput.setAttribute('required', 'required'); // Hacer requerido si hay discapacidad
            } else {
                detalleContainer.style.display = 'none';
                archivoContainer.style.display = 'none';
                detalleInput.setAttribute('disabled', 'disabled');
                archivoInput.setAttribute('disabled', 'disabled');
                detalleInput.removeAttribute('required');
                detalleInput.value = ''; // Limpiar valor
                archivoInput.value = ''; // Limpiar el archivo seleccionado
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Calcular edad al cargar la página si ya hay una fecha de nacimiento
            calcularEdad();
            document.getElementById('fecha_nacimiento_familiar').addEventListener('change', calcularEdad);

            // Inicializar el estado de los campos de discapacidad al cargar la página
            const discapacidadSiChecked = document.getElementById('discapacidad_si').checked;
            toggleDiscapacidadFields(discapacidadSiChecked);
        });
    </script>
</body>
</html>
