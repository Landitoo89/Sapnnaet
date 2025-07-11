<?php
session_start();
require_once __DIR__ . '/../conexion/conexion_db.php';

// Función para registrar logs
function registrarLog($conn, $user_id, $event_type, $details) {
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
    
    $stmt_log = $conn->prepare("INSERT INTO action_logs (user_id, event_type, details, ip_address, user_agent) 
                               VALUES (?, ?, ?, ?, ?)");
    $stmt_log->bind_param("issss", $user_id, $event_type, $details, $ip_address, $user_agent);
    
    if (!$stmt_log->execute()) {
        error_log("Error al registrar log: " . $stmt_log->error);
    }
    
    $stmt_log->close();
}

// Verificar sesión de usuario
if (!isset($_SESSION['usuario'])) {
    $detalles_log = "Intento de acceso no autorizado a edición de datos personales";
    try {
        $conn_temp = new mysqli($servidor, $usuario, $contraseña, $basedatos);
        registrarLog($conn_temp, 0, 'unauthorized_access', $detalles_log);
        $conn_temp->close();
    } catch (Exception $e) {
        error_log('Error al registrar acceso no autorizado: ' . $e->getMessage());
    }
    header("Location: ../../../login.php");
    exit;
}

$current_user_id = $_SESSION['usuario']['id'];
$id_pers = $_GET['id'] ?? null;
$registro = [];

$conn = new mysqli($servidor, $usuario, $contraseña, $basedatos);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Registrar visualización del formulario
if ($id_pers && $_SERVER['REQUEST_METHOD'] === 'GET') {
    registrarLog($conn, $current_user_id, 'view_personal_edit_form', "Visualización de formulario para ID: $id_pers");
}

// Inicializa el array de errores
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Actualizar registro
    $id_pers = $_POST['id_pers'];
    
    // Guardar valores antiguos para comparación
    $stmt_old = $conn->prepare("SELECT * FROM datos_personales WHERE id_pers = ?");
    $stmt_old->bind_param("i", $id_pers);
    $stmt_old->execute();
    $resultado_old = $stmt_old->get_result();
    $valores_antiguos = $resultado_old->fetch_assoc();
    $stmt_old->close();
    
    // Limpiar y obtener datos del formulario
    $nombres = trim($_POST['nombres']);
    $apellidos = trim($_POST['apellidos']);
    
    // Concatenar prefijo y número para cédula y RIF
    $cedula_full = strtoupper(trim($_POST['cedula_prefijo'] . $_POST['cedula_numero']));
    $rif_full = strtoupper(trim($_POST['rif_prefijo'] . $_POST['rif_numero']));
    
    $genero = $_POST['genero'];
    $fecha_nacimiento = $_POST['fecha_nacimiento'];
    $nacionalidad = $_POST['nacionalidad'];
    $correo_electronico = trim($_POST['email']);
    $telefono_contacto = trim($_POST['telefono']);
    
    // Asignar NULL si no se desea teléfono secundario, o el valor si sí
    $posee_telefono_secundario_post = $_POST['posee_telefono_secundario'] ?? 'No';
    $telefono_contacto_secundario = ($posee_telefono_secundario_post === 'Sí') ? (trim($_POST['telefono_secundario'] ?? '')) : NULL;

    $nombre_emergencia = trim($_POST['nombre_emergencia']);
    $apellido_emergencia = trim($_POST['apellido_emergencia']);
    $telefono_emergencia = trim($_POST['telefono_emergencia']);
    $direccion = trim($_POST['direccion']);
    $numero_seguro_social = trim($_POST['numero_seguro_social']);

    // Procesar campos condicionales de discapacidad y licencia
    $tiene_discapacidad = $_POST['discapacidad'] ?? 'No';
    $detalle_discapacidad = ($tiene_discapacidad == 'Sí') ? (trim($_POST['detalle_discapacidad'] ?? '')) : 'No aplica';

    $tiene_licencia_conducir = $_POST['licencia'] ?? 'No';
    $detalle_licencia = ($tiene_licencia_conducir == 'Sí') ? (trim($_POST['detalle_licencia'] ?? '')) : 'No aplica';

    // Procesar pasaporte condicional
    $posee_pasaporte = $_POST['posee_pasaporte'] ?? 'No';
    $pasaporte = ($posee_pasaporte === 'Sí') ? (strtoupper(trim($_POST['pasaporte'] ?? ''))) : 'NO POSEE';

    // Validaciones
    if (empty($nombres)) $errores[] = "Los nombres son obligatorios.";
    if (empty($apellidos)) $errores[] = "Los apellidos son obligatorios.";
    if (empty($cedula_full) || !preg_match('/^[VE]-\d+$/', $cedula_full)) $errores[] = "La cédula de identidad es obligatoria y debe tener el formato correcto (V-XXXXXXXX o E-XXXXXXXX).";
    if (empty($rif_full) || !preg_match('/^[VJGE]-\d+$/', $rif_full)) $errores[] = "El RIF es obligatorio y debe tener el formato correcto (V/J/G/E-XXXXXXXXX).";
    if (empty($genero)) $errores[] = "El género es obligatorio.";
    if (empty($fecha_nacimiento)) $errores[] = "La fecha de nacimiento es obligatoria.";
    if (empty($nacionalidad)) $errores[] = "La nacionalidad es obligatoria.";
    if (empty($correo_electronico) || !filter_var($correo_electronico, FILTER_VALIDATE_EMAIL)) $errores[] = "El correo electrónico es obligatorio y debe ser válido.";
    if (empty($telefono_contacto) || !preg_match('/^\d{11}$/', $telefono_contacto)) $errores[] = "El teléfono principal es obligatorio y debe contener 11 dígitos numéricos.";
    // Solo validar teléfono secundario si se ha elegido agregar uno y no está vacío
    if ($posee_telefono_secundario_post === 'Sí' && !empty($telefono_contacto_secundario) && !preg_match('/^\d{11}$/', $telefono_contacto_secundario)) {
         $errores[] = "El teléfono secundario debe contener 11 dígitos numéricos.";
    } else if ($posee_telefono_secundario_post === 'Sí' && empty($telefono_contacto_secundario)) {
        $errores[] = "Debe ingresar el número de teléfono secundario si seleccionó 'Sí'.";
    }

    if (empty($nombre_emergencia)) $errores[] = "El nombre de contacto de emergencia es obligatorio.";
    if (empty($apellido_emergencia)) $errores[] = "El apellido de contacto de emergencia es obligatorio.";
    if (empty($telefono_emergencia) || !preg_match('/^\d{11}$/', $telefono_emergencia)) $errores[] = "El teléfono de contacto de emergencia es obligatorio y debe contener 11 dígitos numéricos.";
    if (empty($direccion)) $errores[] = "La dirección es obligatoria.";
    if (empty($numero_seguro_social)) $errores[] = "El número de seguro social es obligatorio.";

    // Validación de fecha de nacimiento (mayor a 18 años)
    $fecha_nacimiento_dt = new DateTime($fecha_nacimiento);
    $hoy = new DateTime();
    $edad = $hoy->diff($fecha_nacimiento_dt)->y;
    if ($edad < 18) {
        $errores[] = "El empleado debe ser mayor de 18 años para el registro.";
    }

    // Si no hay errores, procede a actualizar
    if (empty($errores)) {
        try {
            $stmt = $conn->prepare("UPDATE datos_personales SET 
                nombres = ?,
                apellidos = ?,
                cedula_identidad = ?,
                pasaporte = ?,
                rif = ?,
                genero = ?,
                fecha_nacimiento = ?,
                nacionalidad = ?,
                correo_electronico = ?,
                telefono_contacto = ?,
                telefono_contacto_secundario = ?, 
                nombre_contacto_emergencia = ?,
                apellido_contacto_emergencia = ?,
                telefono_contacto_emergencia = ?,
                tiene_discapacidad = ?,
                detalle_discapacidad = ?,
                tiene_licencia_conducir = ?,
                detalle_licencia = ?,
                numero_seguro_social = ?,
                direccion = ?
                WHERE id_pers = ?");
                
            $stmt->bind_param("ssssssssssssssssssssi", 
                $nombres,
                $apellidos,
                $cedula_full,
                $pasaporte,
                $rif_full,
                $genero,
                $fecha_nacimiento,
                $nacionalidad,
                $correo_electronico,
                $telefono_contacto,
                $telefono_contacto_secundario, 
                $nombre_emergencia,
                $apellido_emergencia,
                $telefono_emergencia,
                $tiene_discapacidad,
                $detalle_discapacidad,
                $tiene_licencia_conducir,
                $detalle_licencia,
                $numero_seguro_social,
                $direccion,
                $id_pers);
                
            if ($stmt->execute()) {
                // Registrar cambios exitosos
                $detalles_cambios = [];
                $campos = [
                    'nombres', 'apellidos', 'cedula_identidad', 'pasaporte', 'rif', 'genero',
                    'fecha_nacimiento', 'nacionalidad', 'correo_electronico', 'telefono_contacto',
                    'telefono_contacto_secundario', 'nombre_contacto_emergencia', 'apellido_contacto_emergencia',
                    'telefono_contacto_emergencia', 'tiene_discapacidad', 'detalle_discapacidad',
                    'tiene_licencia_conducir', 'detalle_licencia', 'numero_seguro_social', 'direccion'
                ];
                
                foreach ($campos as $campo) {
                    $valor_antiguo = $valores_antiguos[$campo] ?? 'N/A';
                    $valor_nuevo = $$campo ?? 'N/A';
                    
                    if ($valor_antiguo != $valor_nuevo) {
                        $detalles_cambios[] = "$campo: '$valor_antiguo' → '$valor_nuevo'";
                    }
                }
                
                $detalles_log = "Usuario ID: $current_user_id actualizó datos personales ID: $id_pers\n";
                $detalles_log .= "Cambios realizados:\n" . implode("\n", $detalles_cambios);
                
                registrarLog($conn, $current_user_id, 'personal_data_updated', $detalles_log);
                
                $_SESSION['mensaje'] = [
                    'titulo' => '¡Actualización Exitosa!',
                    'contenido' => 'Los datos personales han sido actualizados correctamente.',
                    'tipo' => 'success'
                ];
                header("Location: ../gestion_personal.php");
                exit;
            } else {
                $errores[] = "Error al actualizar datos personales: " . $stmt->error;
                
                // Registrar error de base de datos
                registrarLog($conn, $current_user_id, 'personal_update_error', "Error al actualizar: " . $stmt->error);
            }
            
            $stmt->close();

        } catch (Exception $e) {
            $errores[] = "Error inesperado: " . $e->getMessage();
            
            // Registrar excepción
            registrarLog($conn, $current_user_id, 'personal_update_exception', "Excepción: " . $e->getMessage());
        }
    } else {
        // Registrar errores de validación
        $detalles_errores = implode('; ', $errores);
        registrarLog($conn, $current_user_id, 'personal_validation_failed', "Errores: $detalles_errores");
    }
}

// Obtener datos actuales del registro si hay un ID
if ($id_pers) {
    $stmt = $conn->prepare("SELECT * FROM datos_personales WHERE id_pers = ?");
    $stmt->bind_param("i", $id_pers);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $registro = $resultado->fetch_assoc();
    $stmt->close();

    // Si no se encuentra el registro, redirigir
    if (!$registro) {
        // Registrar registro no encontrado
        registrarLog($conn, $current_user_id, 'personal_record_not_found', "Intento de acceso a registro inexistente ID: $id_pers");
        
        $_SESSION['error'] = [
            'titulo' => 'Error',
            'contenido' => 'Registro no encontrado.',
            'tipo' => 'danger'
        ];
        header('Location: ../gestion_personal.php');
        exit;
    }
} else {
    // Registrar acceso sin ID
    registrarLog($conn, $current_user_id, 'invalid_personal_access', "Acceso sin ID de registro");
    
    $_SESSION['error'] = [
        'titulo' => 'Error',
        'contenido' => 'ID de registro no proporcionado.',
        'tipo' => 'danger'
    ];
    header('Location: ../gestion_personal.php');
    exit;
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Datos Personales</title>
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
        .input-group-custom .form-control {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
        }
        .input-group-custom .form-select {
            flex: 0 0 auto;
            width: auto;
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }
        .radio-group label {
            margin-right: 15px;
        }
        /* Clase para campos condicionales, inicialmente oculta */
        .conditional-field {
            display: none; 
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-container-custom">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="text-primary mb-0">
                    <i class="bi bi-person-plus me-2"></i>Editar Datos Personales
                </h1>
                <a href="../gestion_personal.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Volver a Gestión
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
                <div class="alert alert-<?= $_SESSION['mensaje']['tipo'] ?> alert-dismissible fade show" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="bi <?= $_SESSION['mensaje']['tipo'] == 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?> me-2"></i>
                        <div>
                            <h5 class="mb-0"><?= htmlspecialchars($_SESSION['mensaje']['titulo']) ?></h5>
                            <p class="mb-0"><?= htmlspecialchars($_SESSION['mensaje']['contenido']) ?></p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['mensaje']); ?>
            <?php endif; ?>
        
            <form method="POST">
                <input type="hidden" name="id_pers" value="<?= htmlspecialchars($registro['id_pers']) ?>">
                
                <!-- Sección Información Básica -->
                <div class="form-section-header">
                    <h2><i class="bi bi-info-circle-fill me-2"></i>Información Básica</h2>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6 mb-3">
                        <label for="nombres" class="form-label">Nombres*:</label>
                        <input type="text" name="nombres" id="nombres" class="form-control" value="<?= htmlspecialchars($registro['nombres'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="apellidos" class="form-label">Apellidos*:</label>
                        <input type="text" name="apellidos" id="apellidos" class="form-control" value="<?= htmlspecialchars($registro['apellidos'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6 mb-3">
                        <label for="nacionalidad" class="form-label">Nacionalidad*:</label>
                        <select name="nacionalidad" id="nacionalidad" class="form-select" required>
                            <option value="">Seleccione...</option>
                            <option value="Venezolano" <?= (($registro['nacionalidad'] ?? '') == 'Venezolano') ? 'selected' : '' ?>>Venezolano</option>
                            <option value="Extranjero" <?= (($registro['nacionalidad'] ?? '') == 'Extranjero') ? 'selected' : '' ?>>Extranjero</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento*:</label>
                        <input type="date" name="fecha_nacimiento" id="fecha_nacimiento" class="form-control" value="<?= htmlspecialchars($registro['fecha_nacimiento'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6 mb-3">
                        <label for="edad_display" class="form-label">Edad:</label>
                        <input type="text" id="edad_display" class="form-control" readonly>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="genero" class="form-label">Género*:</label>
                        <select name="genero" id="genero" class="form-select" required>
                            <option value="">Seleccione...</option>
                            <option value="Masculino" <?= (($registro['genero'] ?? '') == 'Masculino') ? 'selected' : '' ?>>Masculino</option>
                            <option value="Femenino" <?= (($registro['genero'] ?? '') == 'Femenino') ? 'selected' : '' ?>>Femenino</option>
                            <option value="No binario" <?= (($registro['genero'] ?? '') == 'No binario') ? 'selected' : '' ?>>No binario</option>
                            <option value="Prefiero no decir" <?= (($registro['genero'] ?? '') == 'Prefiero no decir') ? 'selected' : '' ?>>Prefiero no decir</option>
                            <option value="Otro" <?= (($registro['genero'] ?? '') == 'Otro') ? 'selected' : '' ?>>Otro</option>
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6 mb-3">
                        <label for="cedula_numero" class="form-label">Cédula de Identidad*:</label>
                        <div class="input-group input-group-custom">
                            <select class="form-select" id="cedula_prefijo" name="cedula_prefijo">
                                <option value="V-" <?= (str_starts_with($registro['cedula_identidad'] ?? '', 'V-')) ? 'selected' : '' ?>>V-</option>
                                <option value="E-" <?= (str_starts_with($registro['cedula_identidad'] ?? '', 'E-')) ? 'selected' : '' ?>>E-</option>
                            </select>
                            <input type="text" name="cedula_numero" id="cedula_numero" class="form-control" placeholder="Ej: 12345678" value="<?= htmlspecialchars(substr($registro['cedula_identidad'] ?? '', 2)) ?>" required oninput="this.value = this.value.replace(/[^0-9]/g, '');">
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="rif_numero" class="form-label">RIF*:</label>
                        <div class="input-group input-group-custom">
                            <select class="form-select" id="rif_prefijo" name="rif_prefijo">
                                <option value="V-" <?= (str_starts_with($registro['rif'] ?? '', 'V-')) ? 'selected' : '' ?>>V-</option>
                                <option value="J-" <?= (str_starts_with($registro['rif'] ?? '', 'J-')) ? 'selected' : '' ?>>J-</option>
                                <option value="G-" <?= (str_starts_with($registro['rif'] ?? '', 'G-')) ? 'selected' : '' ?>>G-</option>
                                <option value="E-" <?= (str_starts_with($registro['rif'] ?? '', 'E-')) ? 'selected' : '' ?>>E-</option>
                            </select>
                            <input type="text" name="rif_numero" id="rif_numero" class="form-control" placeholder="Ej: 123456789" value="<?= htmlspecialchars(substr($registro['rif'] ?? '', 2)) ?>" required oninput="this.value = this.value.replace(/[^0-9]/g, '');">
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">¿Posee pasaporte?</label>
                        <div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="posee_pasaporte" id="passport_si" value="Sí" <?= (($registro['pasaporte'] ?? 'NO POSEE') !== 'NO POSEE') ? 'checked' : '' ?>>
                                <label class="form-check-label" for="passport_si">Sí</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="posee_pasaporte" id="passport_no" value="No" <?= (($registro['pasaporte'] ?? 'NO POSEE') === 'NO POSEE') ? 'checked' : '' ?>>
                                <label class="form-check-label" for="passport_no">No</label>
                            </div>
                        </div>
                    </div>
                    <div id="pasaporte_container" class="col-md-6 mb-3 conditional-field" style="display: <?= (($registro['pasaporte'] ?? 'NO POSEE') !== 'NO POSEE') ? 'block' : 'none' ?>;">
                        <label for="pasaporte_input" class="form-label">Número de Pasaporte:</label>
                        <input type="text" name="pasaporte" id="pasaporte_input" class="form-control" value="<?= htmlspecialchars(($registro['pasaporte'] ?? 'NO POSEE') === 'NO POSEE' ? '' : $registro['pasaporte']) ?>" oninput="this.value = this.value.toUpperCase()" <?= (($registro['pasaporte'] ?? 'NO POSEE') === 'NO POSEE') ? 'disabled' : '' ?>>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="direccion" class="form-label">Dirección*:</label>
                    <input type="text" name="direccion" id="direccion" class="form-control" value="<?= htmlspecialchars($registro['direccion'] ?? '') ?>" required>
                </div>

                <!-- Sección Datos Personales Adicionales -->
                <div class="form-section-header mt-5">
                    <h2><i class="bi bi-file-person-fill me-2"></i>Datos Adicionales y de Salud</h2>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6 mb-3">
                        <label for="numero_seguro_social" class="form-label">Número de Seguro Social*:</label>
                        <input type="text" name="numero_seguro_social" id="numero_seguro_social" class="form-control" value="<?= htmlspecialchars($registro['numero_seguro_social'] ?? '') ?>" required>
                    </div>
                     <!-- Campo Discapacidad -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">¿Posee discapacidad?*</label>
                        <div class="radio-group">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="discapacidad" id="discapacidad_si" value="Sí" 
                                    onchange="toggleCampo('detalle-discapacidad', 'detalle_discapacidad', this.value === 'Sí', true)" <?= (($registro['tiene_discapacidad'] ?? 'No') == 'Sí') ? 'checked' : '' ?> required>
                                <label class="form-check-label" for="discapacidad_si">Sí</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="discapacidad" id="discapacidad_no" value="No" 
                                    onchange="toggleCampo('detalle-discapacidad', 'detalle_discapacidad', this.value === 'Sí', true)" <?= (($registro['tiene_discapacidad'] ?? 'No') == 'No') ? 'checked' : '' ?>>
                                <label class="form-check-label" for="discapacidad_no">No</label>
                            </div>
                        </div>
                    </div>
                </div>
               

                <div id="detalle-discapacidad" class="conditional-field" style="display: <?= (($registro['tiene_discapacidad'] ?? 'No') == 'Sí') ? 'block' : 'none' ?>;">
                    <div class="mb-3">
                        <label for="detalle_discapacidad" class="form-label">Detalle de la discapacidad*:</label>
                        <input type="text" name="detalle_discapacidad" id="detalle_discapacidad" class="form-control" 
                               value="<?= htmlspecialchars(($registro['detalle_discapacidad'] ?? 'No aplica') === 'No aplica' ? '' : $registro['detalle_discapacidad']) ?>"
                               <?= (($registro['tiene_discapacidad'] ?? 'No') == 'Sí') ? '' : 'disabled' ?>>
                    </div>
                </div>

                <!-- Campo Licencia -->
                <div class="mb-3">
                    <label class="form-label">¿Tiene licencia de conducir?*</label>
                    <div class="radio-group">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="licencia" id="licencia_si" value="Sí" 
                                   onchange="toggleCampo('detalle-licencia', 'detalle_licencia', this.value === 'Sí', true)" <?= (($registro['tiene_licencia_conducir'] ?? 'No') == 'Sí') ? 'checked' : '' ?> required>
                            <label class="form-check-label" for="licencia_si">Sí</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="licencia" id="licencia_no" value="No" 
                                   onchange="toggleCampo('detalle-licencia', 'detalle_licencia', this.value === 'Sí', true)" <?= (($registro['tiene_licencia_conducir'] ?? 'No') == 'No') ? 'checked' : '' ?>>
                            <label class="form-check-label" for="licencia_no">No</label>
                        </div>
                    </div>
                </div>

                <div id="detalle-licencia" class="conditional-field" style="display: <?= (($registro['tiene_licencia_conducir'] ?? 'No') == 'Sí') ? 'block' : 'none' ?>;">
                    <div class="mb-3">
                        <label for="detalle_licencia" class="form-label">Detalle de la licencia*:</label>
                        <input type="text" name="detalle_licencia" id="detalle_licencia" class="form-control" 
                               value="<?= htmlspecialchars(($registro['detalle_licencia'] ?? 'No aplica') === 'No aplica' ? '' : $registro['detalle_licencia']) ?>"
                               <?= (($registro['tiene_licencia_conducir'] ?? 'No') == 'Sí') ? '' : 'disabled' ?>>
                    </div>
                </div>

                <!-- Sección de Contacto -->
                <div class="form-section-header mt-5">
                    <h2><i class="bi bi-phone-fill me-2"></i>Información de Contacto</h2>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Correo Electrónico*:</label>
                    <input type="email" name="email" id="email" class="form-control" value="<?= htmlspecialchars($registro['correo_electronico'] ?? '') ?>" required>
                </div>

                <div class="mb-3">
                    <label for="telefono_principal" class="form-label">Teléfono Principal*:</label>
                    <input type="tel" name="telefono" id="telefono_principal" class="form-control" placeholder="Ej: 04121234567" pattern="[0-9]{11}" value="<?= htmlspecialchars($registro['telefono_contacto'] ?? '') ?>" required>
                    
                    <div class="mt-3">
                        <label class="form-label">¿Desea agregar un teléfono secundario?</label>
                        <div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="posee_telefono_secundario" id="telefono_secundario_si" value="Sí" <?= (($registro['telefono_contacto_secundario'] ?? '') !== '') ? 'checked' : '' ?>>
                                <label class="form-check-label" for="telefono_secundario_si">Sí</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="posee_telefono_secundario" id="telefono_secundario_no" value="No" <?= (($registro['telefono_contacto_secundario'] ?? '') === '') ? 'checked' : '' ?>>
                                <label class="form-check-label" for="telefono_secundario_no">No</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="telefono_secundario_container" class="conditional-field" style="display: <?= (($registro['telefono_contacto_secundario'] ?? '') !== '') ? 'block' : 'none' ?>;">
                    <div class="mb-3">
                        <label for="telefono_secundario" class="form-label">Teléfono Secundario:</label>
                        <input type="tel" name="telefono_secundario" id="telefono_secundario" class="form-control" placeholder="Ej: 04147654321" pattern="[0-9]{11}" 
                               value="<?= htmlspecialchars($registro['telefono_contacto_secundario'] ?? '') ?>"
                               <?= (($registro['telefono_contacto_secundario'] ?? '') === '') ? 'disabled' : '' ?>>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6 mb-3">
                        <label for="nombre_emergencia" class="form-label">Nombre de Contacto de Emergencia*:</label>
                        <input type="text" name="nombre_emergencia" id="nombre_emergencia" class="form-control" value="<?= htmlspecialchars($registro['nombre_contacto_emergencia'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="apellido_emergencia" class="form-label">Apellido de Contacto de Emergencia*:</label>
                        <input type="text" name="apellido_emergencia" id="apellido_emergencia" class="form-control" value="<?= htmlspecialchars($registro['apellido_contacto_emergencia'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="telefono_emergencia" class="form-label">Teléfono de Contacto de Emergencia*:</label>
                    <input type="tel" name="telefono_emergencia" id="telefono_emergencia" class="form-control" placeholder="Ej: 04169876543" pattern="[0-9]{11}" value="<?= htmlspecialchars($registro['telefono_contacto_emergencia'] ?? '') ?>" required>
                </div>

                <!-- Botón de envío -->
                <div class="d-grid gap-2 mt-4">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-save me-2"></i>Guardar Cambios
                    </button>
                    <a href="../gestion_personal.php" class="btn btn-secondary btn-lg">
                        <i class="bi bi-x-circle me-2"></i>Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Función para alternar la visibilidad y requisitos de campos condicionales
            function toggleCampo(containerId, inputId, show, makeInputRequired = false) {
                const container = document.getElementById(containerId);
                const input = document.getElementById(inputId); 
                if (!container || !input) return;

                if (show) {
                    container.style.display = 'block'; 
                    input.removeAttribute('disabled');     
                    if (makeInputRequired) {
                        input.setAttribute('required', 'required'); 
                    }
                } else {
                    container.style.display = 'none'; 
                    input.setAttribute('disabled', 'disabled'); 
                    input.removeAttribute('required'); 
                    input.value = ''; 
                }
            }

            // --- INICIALIZACIÓN DE CAMPOS CONDICIONALES AL CARGAR LA PÁGINA ---

            // Pasaporte
            const passportSiRadio = document.getElementById('passport_si');
            if (passportSiRadio) {
                document.querySelectorAll('input[name="posee_pasaporte"]').forEach(radio => {
                    radio.addEventListener('change', function() {
                        toggleCampo('pasaporte_container', 'pasaporte_input', this.value === 'Sí', true);
                    });
                });
                // Establecer estado inicial basado en los datos existentes
                toggleCampo('pasaporte_container', 'pasaporte_input', passportSiRadio.checked, true);
            }

            // Discapacidad
            const discapacidadSiRadio = document.getElementById('discapacidad_si');
            if (discapacidadSiRadio) {
                document.querySelectorAll('input[name="discapacidad"]').forEach(radio => {
                    radio.addEventListener('change', function() {
                        toggleCampo('detalle-discapacidad', 'detalle_discapacidad', this.value === 'Sí', true);
                    });
                });
                // Establecer estado inicial basado en los datos existentes
                toggleCampo('detalle-discapacidad', 'detalle_discapacidad', discapacidadSiRadio.checked, true);
            }

            // Licencia
            const licenciaSiRadio = document.getElementById('licencia_si');
            if (licenciaSiRadio) {
                document.querySelectorAll('input[name="licencia"]').forEach(radio => {
                    radio.addEventListener('change', function() {
                        toggleCampo('detalle-licencia', 'detalle_licencia', this.value === 'Sí', true);
                    });
                });
                // Establecer estado inicial basado en los datos existentes
                toggleCampo('detalle-licencia', 'detalle_licencia', licenciaSiRadio.checked, true);
            }
            
            // Teléfono Secundario
            const telefonoSecundarioSiRadio = document.getElementById('telefono_secundario_si');
            if (telefonoSecundarioSiRadio) {
                document.querySelectorAll('input[name="posee_telefono_secundario"]').forEach(radio => {
                    radio.addEventListener('change', function() {
                        toggleCampo('telefono_secundario_container', 'telefono_secundario', this.value === 'Sí', false); 
                    });
                });
                // Establecer estado inicial basado en los datos existentes
                toggleCampo('telefono_secundario_container', 'telefono_secundario', telefonoSecundarioSiRadio.checked, false);
            }
            
            // Cálculo y Validación de Edad
            const fechaNacimientoInput = document.getElementById('fecha_nacimiento');
            const edadDisplayInput = document.getElementById('edad_display');

            function calcularEdad() {
                const dobString = fechaNacimientoInput.value;
                if (!dobString) {
                    edadDisplayInput.value = '';
                    return;
                }

                const dob = new Date(dobString);
                const today = new Date();
                let age = today.getFullYear() - dob.getFullYear();
                const m = today.getMonth() - dob.getMonth();
                if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) {
                    age--;
                }

                // Eliminar cualquier alerta existente
                const existingAlert = fechaNacimientoInput.parentNode.querySelector('.alert-warning');
                if (existingAlert) {
                    existingAlert.remove();
                }

                if (age < 18) {
                    const alertDiv = document.createElement('div');
                    alertDiv.classList.add('alert', 'alert-warning', 'mt-3');
                    alertDiv.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-2"></i>La fecha de nacimiento debe ser mayor a 18 años a partir del día de registro.';
                    fechaNacimientoInput.parentNode.insertBefore(alertDiv, fechaNacimientoInput.nextSibling); 
                    fechaNacimientoInput.value = ''; // Limpiar el input de fecha
                    edadDisplayInput.value = ''; // Limpiar la edad
                    
                    // Eliminar la alerta después de unos segundos
                    setTimeout(() => alertDiv.remove(), 5000);
                } else {
                    edadDisplayInput.value = age + ' años';
                }
            }

            if (fechaNacimientoInput) {
                fechaNacimientoInput.addEventListener('change', calcularEdad);
                // Calcular la edad al cargar la página si ya hay una fecha de nacimiento
                if (fechaNacimientoInput.value) {
                    calcularEdad();
                }
            }

            // Función para rellenar los prefijos de CI/RIF
            function splitPrefixedValue(fullValue, prefixSelectId, numberInputId) {
                const prefixSelect = document.getElementById(prefixSelectId);
                const numberInput = document.getElementById(numberInputId);
                if (!fullValue || !prefixSelect || !numberInput) return;

                const parts = fullValue.split('-');
                if (parts.length > 1) {
                    const prefix = parts[0] + '-';
                    const number = parts.slice(1).join('-');
                    prefixSelect.value = prefix;
                    numberInput.value = number;
                } else {
                    // Si no hay prefijo (ej. un número puro), solo asignarlo al campo de número
                    numberInput.value = fullValue;
                }
            }

            // Aplicar la lógica al cargar la página para CI y RIF
            splitPrefixedValue("<?= htmlspecialchars($registro['cedula_identidad'] ?? '') ?>", 'cedula_prefijo', 'cedula_numero');
            splitPrefixedValue("<?= htmlspecialchars($registro['rif'] ?? '') ?>", 'rif_prefijo', 'rif_numero');
        });
    </script>
</body>
</html>
