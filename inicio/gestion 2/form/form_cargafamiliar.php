<?php
session_start();
require_once __DIR__ . '/../conexion/conexion_db.php';
$conn = new mysqli($servidor, $usuario, $contraseña, $basedatos);

// === FUNCIÓN PARA LOGS CORREGIDA ===
function registrarLog($conn, $user_id, $event_type, $details) {
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
    $stmt = $conn->prepare("INSERT INTO action_logs (user_id, event_type, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $user_id, $event_type, $details, $ip_address, $user_agent);
    $stmt->execute();
    $stmt->close();
}

$current_user_id = $_SESSION['usuario']['id'] ?? null;

$mensajeExito = '';
$error = '';

$id_socio = $_GET['id_socioeco'] ?? 0;
$id_pers = $_GET['id_pers'] ?? 0;
$nombres = urldecode($_GET['nombres'] ?? '');
$apellidos = urldecode($_GET['apellidos'] ?? '');
$source = $_GET['source'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $id_socio && $id_pers) {
    registrarLog(
        $conn,
        $current_user_id,
        'view_carga_familiar_form',
        "Visualización del formulario de cargas familiares para ID Persona: $id_pers, Trabajador: $nombres $apellidos"
    );
}

if (!$id_socio || !$id_pers) {
    $_SESSION['mensaje'] = [
        'titulo' => 'Error',
        'contenido' => 'Datos de referencia incompletos. Vuelva a la página anterior.',
        'tipo' => 'danger'
    ];
    registrarLog(
        $conn,
        $current_user_id,
        'invalid_carga_familiar_access',
        "Acceso a formulario de cargas familiares sin IDs válidos (ID Persona: $id_pers, Trabajador: $nombres $apellidos)"
    );
    header("Location: form_datossocioeco.php?id_pers=" . urlencode($id_pers) . "&nombres=" . urlencode($nombres) . "&apellidos=" . urlencode($apellidos));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $familiares_post_data = $_POST['familiares'] ?? [];
    $has_valid_familiares_to_process = false;

    foreach ($familiares_post_data as $familiar) {
        if (!empty($familiar['nombres']) && !empty($familiar['apellidos']) && !empty($familiar['fecha_nacimiento_familiar'])) {
            $has_valid_familiares_to_process = true;
            break;
        }
    }

    if ($has_valid_familiares_to_process) {
        try {
            $pdo = new PDO("mysql:host=$servidor;dbname=$basedatos", $usuario, $contraseña);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
            $pdo->beginTransaction();

            $stmt_select_existing_files = $pdo->prepare("SELECT archivo_deficit FROM carga_familiar WHERE id_socioeconomico = :id_socio");
            $stmt_select_existing_files->bindParam(':id_socio', $id_socio, PDO::PARAM_INT);
            $stmt_select_existing_files->execute();
            $existing_files_in_db = $stmt_select_existing_files->fetchAll(PDO::FETCH_COLUMN);

            $stmt_delete = $pdo->prepare("DELETE FROM carga_familiar WHERE id_socioeconomico = :id_socio");
            $stmt_delete->bindParam(':id_socio', $id_socio, PDO::PARAM_INT);
            $stmt_delete->execute();

            foreach ($existing_files_in_db as $file_path) {
                if (!empty($file_path) && file_exists($file_path)) {
                    unlink($file_path);
                }
            }

            foreach ($familiares_post_data as $index => $familiar) {
                if (!empty($familiar['nombres']) && !empty($familiar['apellidos']) && !empty($familiar['fecha_nacimiento_familiar'])) {
                    $discapacidad = $familiar['discapacidad'] ?? 'No';
                    $detalle_discapacidad = ($discapacidad === 'Sí') ? (trim($familiar['detalle_discapacidad'] ?? '') !== '' ? $familiar['detalle_discapacidad'] : null) : null;
                    $cedula_familiar = !empty($familiar['cedula'] ?? '') ? $familiar['cedula'] : null;

                    $fecha_nacimiento_familiar = $familiar['fecha_nacimiento_familiar'];
                    $edad_familiar_calculated = NULL;
                    if (!empty($fecha_nacimiento_familiar)) {
                        try {
                            $dob = new DateTime($fecha_nacimiento_familiar);
                            $today = new DateTime();
                            $edad_familiar_calculated = $today->diff($dob)->y;
                        } catch (Exception $e) {}
                    }

                    $archivo = NULL;
                    if ($discapacidad === 'Sí' && isset($_FILES['familiares']['name'][$index]['archivo']) && $_FILES['familiares']['error'][$index]['archivo'] === UPLOAD_ERR_OK) {
                        $uploadDir = '../uploads/cargas/';
                        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                        $fileName = uniqid() . '_' . basename($_FILES['familiares']['name'][$index]['archivo']);
                        $targetFilePath = $uploadDir . $fileName;
                        if (move_uploaded_file($_FILES['familiares']['tmp_name'][$index]['archivo'], $targetFilePath)) {
                            $archivo = $targetFilePath;
                        } else {
                            throw new Exception("Error al subir el archivo para el familiar " . htmlspecialchars($familiar['nombres']) . ".");
                        }
                    }

                    $stmt = $pdo->prepare("INSERT INTO carga_familiar 
                        (id_socioeconomico, parentesco, nombres_familiar, apellidos_familiar, 
                        fecha_nacimiento_familiar, edad_familiar, cedula_familiar, genero_familiar, tiene_discapacidad, 
                        detalle_discapacidad, archivo_deficit)
                        VALUES (:id_socio, :parentesco, :nombres, :apellidos, :fecha_nacimiento, :edad, :cedula, 
                        :genero, :discapacidad, :detalle_discapacidad, :archivo)"
                    );

                    $stmt->execute([
                        ':id_socio' => $id_socio,
                        ':parentesco' => $familiar['parentesco'] ?? NULL,
                        ':nombres' => $familiar['nombres'],
                        ':apellidos' => $familiar['apellidos'],
                        ':fecha_nacimiento' => $fecha_nacimiento_familiar,
                        ':edad' => $edad_familiar_calculated,
                        ':cedula' => $cedula_familiar,
                        ':genero' => $familiar['genero'] ?? NULL,
                        ':discapacidad' => $discapacidad,
                        ':detalle_discapacidad' => $detalle_discapacidad,
                        ':archivo' => $archivo
                    ]);

                    // === LOG SOLO PARA ESTE FAMILIAR ===
                    $log_details = "Registro de carga familiar para ID Persona: $id_pers, Trabajador: $nombres $apellidos. Familiar: {$familiar['nombres']} {$familiar['apellidos']} (Parentesco: {$familiar['parentesco']}, Discapacidad: $discapacidad";
                    if ($discapacidad === 'Sí' && $detalle_discapacidad) $log_details .= ", Detalle: $detalle_discapacidad";
                    if ($cedula_familiar) $log_details .= ", Cédula: $cedula_familiar";
                    $log_details .= ")";
                    registrarLog(
                        $conn,
                        $current_user_id,
                        'carga_familiar_saved',
                        $log_details
                    );
                }
            }

            $pdo->commit();

            $_SESSION['mensaje'] = [
                'titulo' => '¡Éxito!',
                'contenido' => 'Cargas familiares registradas correctamente.',
                'tipo' => 'success'
            ];

            if ($source === 'new_employee_flow') {
                header("Location: form_tregister.php?id_pers=" . urlencode($id_pers) . "&nombres=" . urlencode($nombres) . "&apellidos=" . urlencode($apellidos));
            } else {
                header("Location: ../gestion_carga.php");
            }
            exit();

        } catch (Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
            $error = "Error al registrar cargas familiares: " . $e->getMessage();
            $_SESSION['mensaje'] = [
                'titulo' => 'Error',
                'contenido' => $error,
                'tipo' => 'danger'
            ];
            registrarLog(
                $conn,
                $current_user_id,
                'carga_familiar_save_error',
                "Error al registrar cargas familiares para ID Persona: $id_pers, Trabajador: $nombres $apellidos. Error: {$e->getMessage()}"
            );
            header("Location: " . $_SERVER['PHP_SELF'] . '?id_pers=' . urlencode($id_pers) . '&nombres=' . urlencode($nombres) . '&apellidos=' . urlencode($apellidos) . '&id_socioeco=' . urlencode($id_socio) . '&source=' . urlencode($source));
            exit();
        }
    } else {
        $_SESSION['mensaje'] = [
            'titulo' => 'Información',
            'contenido' => 'No se registraron cargas familiares. Se ha omitido este paso. Continúe con los datos laborales.',
            'tipo' => 'info'
        ];
        registrarLog(
            $conn,
            $current_user_id,
            'carga_familiar_skipped',
            "Se omitió el registro de cargas familiares para ID Persona: $id_pers, Trabajador: $nombres $apellidos"
        );
        if ($source === 'new_employee_flow') {
            header("Location: form_tregister.php?id_pers=" . urlencode($id_pers) . "&nombres=" . urlencode($nombres) . "&apellidos=" . urlencode($apellidos));
        } else {
            header("Location: ../../gestion_carga.php");
        }
        exit();
    }
}
// Siempre mostrar solo un formulario vacío para nuevas cargas familiares
$familiares_to_display = [
    [
        'parentesco' => '', 'nombres_familiar' => '', 'apellidos_familiar' => '',
        'fecha_nacimiento_familiar' => '', 'edad_familiar' => '', 'cedula_familiar' => '',
        'genero_familiar' => '', 'tiene_discapacidad' => 'No', 'detalle_discapacidad' => '',
        'archivo_deficit' => ''
    ]
];
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de Cargas Familiares</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/formularios_styles.css"> <!-- Asegúrate de que esta ruta sea correcta -->
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
        .familiar-group {
            border: 1px solid #e9ecef;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            background-color: #fcfcfc;
            position: relative; /* Para el botón de eliminar */
        }
        .remove-familiar-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 10;
        }
        .conditional-field {
            margin-top: 1rem;
        }
        .radio-group label {
            margin-right: 15px;
        }
        .checkbox-group label {
            margin-right: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-container-custom">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="text-primary mb-0">
                    <i class="bi bi-people-fill me-2"></i>Registro de Cargas Familiares
                </h1>
                <a href="../gestion_carga.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Volver a Gestión
                </a>
            </div>

            <?php if(isset($_SESSION['mensaje'])): ?>
                <div class="alert alert-<?= $_SESSION['mensaje']['tipo'] ?> alert-dismissible fade show" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="bi <?= ($_SESSION['mensaje']['tipo'] ?? 'success') == 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?> me-2"></i>
                        <div>
                            <h5 class="mb-0"><?= htmlspecialchars($_SESSION['mensaje']['titulo']) ?></h5>
                            <p class="mb-0"><?= htmlspecialchars($_SESSION['mensaje']['contenido']) ?></p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['mensaje']); ?>
            <?php endif; ?>
        
            <form method="POST" enctype="multipart/form-data" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?id_pers=' . urlencode($id_pers) . '&nombres=' . urlencode($nombres) . '&apellidos=' . urlencode($apellidos) . '&id_socioeco=' . urlencode($id_socio) . '&source=' . urlencode($source);?>">
                <input type="hidden" name="id_socioeconomico" value="<?= htmlspecialchars($id_socio) ?>">
                <input type="hidden" name="id_pers_hidden" value="<?= htmlspecialchars($id_pers) ?>">
                
                <div class="form-section-header">
                    <h2><i class="bi bi-person-fill me-2"></i>Información del Trabajador</h2>
                </div>
                <div class="mb-4">
                    <label class="form-label">Trabajador:</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($nombres . ' ' . $apellidos) ?>" readonly>
                </div>

                <div class="form-section-header mt-5 d-flex justify-content-between align-items-center">
                    <h2><i class="bi bi-people-fill me-2"></i>Cargas Familiares</h2>
                    <button type="button" class="btn btn-success btn-sm" id="addFamiliarBtn">
                        <i class="bi bi-plus-circle me-2"></i>Añadir Familiar
                    </button>
                </div>

                <div id="familiares-container">
                    <?php 
                    foreach ($familiares_to_display as $index => $familiar_data):
                        // Asegurarse de que los campos existan o sean nulos/vacíos
                        $parentesco_val = $familiar_data['parentesco'] ?? '';
                        $nombres_val = $familiar_data['nombres_familiar'] ?? $familiar_data['nombres'] ?? ''; // Ajuste para POST
                        $apellidos_val = $familiar_data['apellidos_familiar'] ?? $familiar_data['apellidos'] ?? ''; // Ajuste para POST
                        $fecha_nacimiento_val = $familiar_data['fecha_nacimiento_familiar'] ?? ''; 
                        $edad_val = $familiar_data['edad_familiar'] ?? $familiar_data['edad'] ?? ''; // Ajuste para POST
                        $cedula_val = $familiar_data['cedula_familiar'] ?? $familiar_data['cedula'] ?? ''; // Ajuste para POST
                        $genero_val = $familiar_data['genero_familiar'] ?? $familiar_data['genero'] ?? ''; // Ajuste para POST
                        $discapacidad_val = $familiar_data['tiene_discapacidad'] ?? $familiar_data['discapacidad'] ?? 'No'; // Ajuste para POST
                        $detalle_discapacidad_val = $familiar_data['detalle_discapacidad'] ?? '';
                        $archivo_deficit_val = $familiar_data['archivo_deficit'] ?? '';
                ?>
                <div class="familiar-group p-3 border rounded mb-3">
                    <button type="button" class="btn btn-danger btn-sm remove-familiar-btn">
                        <i class="bi bi-x-lg"></i>
                    </button>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Parentesco:</label>
                            <select name="familiares[<?= $index ?>][parentesco]" class="form-select">
                                <option value="">Seleccione...</option>
                                <option value="Cónyuge" <?= ($parentesco_val == 'Cónyuge') ? 'selected' : '' ?>>Cónyuge</option>
                                <option value="Hijo/a" <?= ($parentesco_val == 'Hijo/a') ? 'selected' : '' ?>>Hijo/a</option>
                                <option value="Padre" <?= ($parentesco_val == 'Padre') ? 'selected' : '' ?>>Padre</option>
                                <option value="Madre" <?= ($parentesco_val == 'Madre') ? 'selected' : '' ?>>Madre</option>
                                <option value="Hermano/a" <?= ($parentesco_val == 'Hermano/a') ? 'selected' : '' ?>>Hermano/a</option>
                                <option value="Otro" <?= ($parentesco_val == 'Otro') ? 'selected' : '' ?>>Otro</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Cédula:</label>
                            <input type="text" name="familiares[<?= $index ?>][cedula]" class="form-control" value="<?= htmlspecialchars($cedula_val) ?>">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nombres:</label>
                            <input type="text" name="familiares[<?= $index ?>][nombres]" class="form-control" value="<?= htmlspecialchars($nombres_val) ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Apellidos:</label>
                            <input type="text" name="familiares[<?= $index ?>][apellidos]" class="form-control" value="<?= htmlspecialchars($apellidos_val) ?>">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Fecha de Nacimiento:</label>
                            <!-- Se añadió esta línea para el campo de fecha de nacimiento -->
                            <input type="date" name="familiares[<?= $index ?>][fecha_nacimiento_familiar]" class="form-control familiar-fecha-nacimiento" value="<?= htmlspecialchars($fecha_nacimiento_val) ?>">
                            <input type="hidden" name="familiares[<?= $index ?>][edad]" class="familiar-edad-hidden" value="<?= htmlspecialchars($edad_val) ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Edad:</label>
                            <input type="text" class="form-control familiar-edad-display" value="<?= htmlspecialchars($edad_val ? $edad_val . ' años' : '') ?>" readonly>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Género:</label>
                        <select name="familiares[<?= $index ?>][genero]" class="form-select">
                            <option value="">Seleccione...</option>
                            <option value="Masculino" <?= ($genero_val == 'Masculino') ? 'selected' : '' ?>>Masculino</option>
                            <option value="Femenino" <?= ($genero_val == 'Femenino') ? 'selected' : '' ?>>Femenino</option>
                            <option value="Otro" <?= ($genero_val == 'Otro') ? 'selected' : '' ?>>Otro</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">¿Tiene discapacidad?</label>
                        <div class="form-check form-check-inline">
                            <input type="radio" name="familiares[<?= $index ?>][discapacidad]" value="Sí" 
                                   class="form-check-input familiar-discapacidad-radio" 
                                   id="discapacidad_si_<?= $index ?>" 
                                   onchange="toggleDocumento(this)" 
                                   <?= ($discapacidad_val == 'Sí') ? 'checked' : '' ?>>
                            <label class="form-check-label" for="discapacidad_si_<?= $index ?>">Sí</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input type="radio" name="familiares[<?= $index ?>][discapacidad]" value="No" 
                                   class="form-check-input familiar-discapacidad-radio" 
                                   id="discapacidad_no_<?= $index ?>" 
                                   onchange="toggleDocumento(this)" 
                                   <?= ($discapacidad_val == 'No') ? 'checked' : '' ?>>
                            <label class="form-check-label" for="discapacidad_no_<?= $index ?>">No</label>
                        </div>
                    </div>

                    <div class="mb-3 detalle-discapacidad" style="display: <?= ($discapacidad_val == 'Sí') ? 'block' : 'none' ?>;">
                        <label class="form-label">Detalle de la discapacidad:</label>
                        <input type="text" name="familiares[<?= $index ?>][detalle_discapacidad]" class="form-control" 
                               value="<?= htmlspecialchars($detalle_discapacidad_val) ?>"
                               <?= ($discapacidad_val == 'Sí') ? '' : 'disabled' ?>>
                    </div>

                    <div class="mb-3 archivo-deficit" style="display: <?= ($discapacidad_val == 'Sí') ? 'block' : 'none' ?>;">
                        <label class="form-label">Archivo de déficit:</label>
                        <input type="file" name="familiares[<?= $index ?>][archivo]" class="form-control" 
                               <?= ($discapacidad_val == 'Sí') ? '' : 'disabled' ?>>
                        <?php if (!empty($archivo_deficit_val)): ?>
                            <small class="text-muted">Archivo actual: <a href="<?= htmlspecialchars($archivo_deficit_val) ?>" target="_blank">Ver</a></small>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                </div>

                <div class="d-grid gap-2 mt-4">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-save me-2"></i>Guardar Cargas Familiares y Continuar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let contador = <?= count($familiares_to_display) ?>; // Inicia contador correctamente
        
        function calcularEdadFamiliar(dateInput, ageDisplayInput, ageHiddenInput) {
            const dobString = dateInput.value;
            if (!dobString) {
                ageDisplayInput.value = '';
                ageHiddenInput.value = '';
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
            ageHiddenInput.value = age; // Guardar la edad en el input oculto
        }

        window.toggleDocumento = function(radio) {
            const grupo = radio.closest('.familiar-group');
            if (!grupo) {
                console.error("Grupo familiar no encontrado para el radio:", radio);
                return;
            }
            const detalle = grupo.querySelector('.detalle-discapacidad');
            const archivo = grupo.querySelector('.archivo-deficit');
            const detalleInput = detalle.querySelector('input[type="text"]');
            const archivoInput = archivo.querySelector('input[type="file"]');

            if (radio.value === 'Sí') {
                detalle.style.display = 'block';
                archivo.style.display = 'block';
                if (detalleInput) {
                    detalleInput.removeAttribute('disabled');
                    detalleInput.setAttribute('required', 'required'); // Hacer requerido
                }
                if (archivoInput) {
                    archivoInput.removeAttribute('disabled');
                    // El archivo es requerido solo si no hay uno existente
                    const existingFileLink = archivo.querySelector('small a');
                    if (!existingFileLink) {
                        archivoInput.setAttribute('required', 'required'); 
                    }
                }
            } else {
                detalle.style.display = 'none';
                archivo.style.display = 'none';
                if (detalleInput) {
                    detalleInput.setAttribute('disabled', 'disabled');
                    detalleInput.removeAttribute('required'); // Quitar requerido
                    detalleInput.value = ''; // Limpiar el valor al ocultar
                }
                if (archivoInput) {
                    archivoInput.setAttribute('disabled', 'disabled');
                    archivoInput.removeAttribute('required'); // Quitar requerido
                    archivoInput.value = ''; // Limpiar la selección del archivo
                }
            }
        }

        // Función para añadir un nuevo grupo de familiar
        function agregarFamiliar() {
            const container = document.getElementById('familiares-container');
            const newGroup = document.createElement('div');
            newGroup.classList.add('familiar-group', 'p-3', 'border', 'rounded', 'mb-3');
            
            const currentFamiliarIndex = contador++;

            newGroup.innerHTML = `
                <button type="button" class="btn btn-danger btn-sm remove-familiar-btn">
                    <i class="bi bi-x-lg"></i>
                </button>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Parentesco:</label>
                        <select name="familiares[${currentFamiliarIndex}][parentesco]" class="form-select">
                            <option value="">Seleccione...</option>
                            <option value="Cónyuge">Cónyuge</option>
                            <option value="Hijo/a">Hijo/a</option>
                            <option value="Padre">Padre</option>
                            <option value="Madre">Madre</option>
                            <option value="Hermano/a">Hermano/a</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Cédula:</label>
                        <input type="text" name="familiares[${currentFamiliarIndex}][cedula]" class="form-control">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nombres:</label>
                        <input type="text" name="familiares[${currentFamiliarIndex}][nombres]" class="form-control">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Apellidos:</label>
                        <input type="text" name="familiares[${currentFamiliarIndex}][apellidos]" class="form-control">
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Fecha de Nacimiento:</label>
                        <!-- Se añadió esta línea para el campo de fecha de nacimiento -->
                        <input type="date" name="familiares[${currentFamiliarIndex}][fecha_nacimiento_familiar]" class="form-control familiar-fecha-nacimiento">
                        <input type="hidden" name="familiares[${currentFamiliarIndex}][edad]" class="familiar-edad-hidden">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Edad:</label>
                        <input type="text" class="form-control familiar-edad-display" readonly>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Género:</label>
                    <select name="familiares[${currentFamiliarIndex}][genero]" class="form-select">
                        <option value="">Seleccione...</option>
                        <option value="Masculino">Masculino</option>
                        <option value="Femenino">Femenino</option>
                        <option value="Otro">Otro</option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">¿Tiene discapacidad?</label>
                    <div class="form-check form-check-inline">
                        <input type="radio" name="familiares[${currentFamiliarIndex}][discapacidad]" value="Sí" 
                               class="form-check-input familiar-discapacidad-radio" 
                               id="discapacidad_si_${currentFamiliarIndex}" onchange="toggleDocumento(this)">
                        <label class="form-check-label" for="discapacidad_si_${currentFamiliarIndex}">Sí</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input type="radio" name="familiares[${currentFamiliarIndex}][discapacidad]" value="No" 
                               class="form-check-input familiar-discapacidad-radio" 
                               id="discapacidad_no_${currentFamiliarIndex}" onchange="toggleDocumento(this)" checked>
                        <label class="form-check-label" for="discapacidad_no_${currentFamiliarIndex}">No</label>
                    </div>
                </div>

                <div class="mb-3 detalle-discapacidad" style="display: none;">
                    <label class="form-label">Detalle de la discapacidad:</label>
                    <input type="text" name="familiares[${currentFamiliarIndex}][detalle_discapacidad]" class="form-control" disabled>
                </div>

                <div class="mb-3 archivo-deficit" style="display: none;">
                    <label class="form-label">Archivo de déficit:</label>
                    <input type="file" name="familiares[${currentFamiliarIndex}][archivo]" class="form-control" disabled>
                </div>
            `;
            
            container.appendChild(newGroup);
            
            // Asignar evento al nuevo botón de eliminar
            newGroup.querySelector('.remove-familiar-btn').addEventListener('click', function() {
                removeFamiliar(newGroup);
            });

            // Asignar evento al nuevo campo de fecha de nacimiento
            const newDateInput = newGroup.querySelector('.familiar-fecha-nacimiento');
            const newAgeDisplay = newGroup.querySelector('.familiar-edad-display');
            const newAgeHidden = newGroup.querySelector('.familiar-edad-hidden');
            newDateInput.addEventListener('change', () => calcularEdadFamiliar(newDateInput, newAgeDisplay, newAgeHidden));

            updateRemoveButtonsVisibility();
        }

        // Función para eliminar un grupo de familiar
        function removeFamiliar(groupElement) {
            groupElement.remove();
            updateRemoveButtonsVisibility();
        }

        function updateRemoveButtonsVisibility() {
            const allFamiliarGroups = document.querySelectorAll('.familiar-group');
            if (allFamiliarGroups.length === 1) {
                allFamiliarGroups[0].querySelector('.remove-familiar-btn').classList.add('d-none');
            } else {
                allFamiliarGroups.forEach(group => {
                    group.querySelector('.remove-familiar-btn').classList.remove('d-none');
                });
            }
        }


        document.addEventListener('DOMContentLoaded', function() {
            // Manejar click del botón "Añadir Familiar"
            document.getElementById('addFamiliarBtn').addEventListener('click', agregarFamiliar);

            // Manejar clicks en los botones de eliminar (para grupos ya existentes o los recién creados)
            document.getElementById('familiares-container').addEventListener('click', function(event) {
                const button = event.target.closest('.remove-familiar-btn');
                if (button) {
                    const group = button.closest('.familiar-group');
                    removeFamiliar(group);
                }
            });

            // Inicializar el estado de los campos de discapacidad y edad para los grupos ya existentes
            document.querySelectorAll('.familiar-group').forEach(group => {
                const radioSi = group.querySelector('.familiar-discapacidad-radio[value="Sí"]');
                if (radioSi && radioSi.checked) {
                    toggleDocumento(radioSi);
                } else {
                    const radioNo = group.querySelector('.familiar-discapacidad-radio[value="No"]');
                    if (radioNo) {
                         toggleDocumento(radioNo); // Asegura que se oculten si no está 'Sí'
                    }
                }
                // Calcular edad para grupos precargados (si hay datos POST)
                const dateInput = group.querySelector('.familiar-fecha-nacimiento');
                const ageDisplay = group.querySelector('.familiar-edad-display');
                const ageHidden = group.querySelector('.familiar-edad-hidden');
                if (dateInput && ageDisplay && ageHidden) {
                    if (dateInput.value) {
                        calcularEdadFamiliar(dateInput, ageDisplay, ageHidden);
                    }
                    dateInput.addEventListener('change', () => calcularEdadFamiliar(dateInput, ageDisplay, ageHidden));
                }
            });

            // Asegurarse de que el botón de eliminar del primer familiar esté oculto si es el único
            updateRemoveButtonsVisibility();
        });
    </script>
</body>
</html>