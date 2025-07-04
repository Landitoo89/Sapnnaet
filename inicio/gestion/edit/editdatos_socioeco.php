<?php
session_start();
require_once __DIR__ . '/../conexion/conexion_db.php';

// Verificar sesión de usuario
if (!isset($_SESSION['usuario'])) {
    $detalles_log = "Intento de acceso no autorizado a edición de datos socioeconómicos";
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
    
    try {
        $conn_temp = new mysqli($servidor, $usuario, $contraseña, $basedatos);
        $stmt_log = $conn_temp->prepare("INSERT INTO action_logs (event_type, details, ip_address, user_agent) 
                                        VALUES (?, ?, ?, ?)");
        $event_type = 'unauthorized_access';
        $stmt_log->bind_param("ssss", $event_type, $detalles_log, $ip_address, $user_agent);
        $stmt_log->execute();
        $stmt_log->close();
        $conn_temp->close();
    } catch (Exception $e) {
        error_log('Error al registrar acceso no autorizado: ' . $e->getMessage());
    }
    header("Location: ../login.php");
    exit;
}

$current_user_id = $_SESSION['usuario']['id'];

// Función para registrar logs
function registrarLog($conn, $user_id, $event_type, $details) {
    $detalles_log = "Usuario: [$user_id]\n";
    $detalles_log .= "Acción: $event_type\n";
    $detalles_log .= "Detalles: $details";
    
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
    
    $stmt_log = $conn->prepare("INSERT INTO action_logs (user_id, event_type, details, ip_address, user_agent) 
                               VALUES (?, ?, ?, ?, ?)");
    $stmt_log->bind_param("issss", $user_id, $event_type, $detalles_log, $ip_address, $user_agent);
    
    if (!$stmt_log->execute()) {
        error_log("Error al registrar log: " . $stmt_log->error);
    }
    
    $stmt_log->close();
}

$id_socioeconomico = $_GET['id'] ?? 0;

// Obtener datos actuales
$stmt = $conexion->prepare("
    SELECT s.*, p.nombres, p.apellidos 
    FROM datos_socioeconomicos s
    INNER JOIN datos_personales p ON s.id_pers = p.id_pers
    WHERE s.id_socioeconomico = ?
");
$stmt->bind_param("i", $id_socioeconomico);
$stmt->execute();
$registro = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$registro) {
    $_SESSION['error'] = "Registro socioeconómico no encontrado.";
    registrarLog($conexion, $current_user_id, 'socioeconomic_edit_error', "Registro no encontrado: ID $id_socioeconomico");
    header("Location: ../gestion_socioeconomicos.php");
    exit();
}

// Registrar visualización del formulario de edición
registrarLog($conexion, $current_user_id, 'view_socioeconomic_edit', 
            "Visualización de edición para ID Socioeconómico: $id_socioeconomico");

// Procesar actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Guardar valores anteriores para comparación
        $valores_anteriores = [
            'estado_civil' => $registro['estado_civil'],
            'nivel_academico' => $registro['nivel_academico'],
            'mencion' => $registro['mencion'],
            'instituciones_academicas' => $registro['instituciones_academicas'],
            'tipo_vivienda' => $registro['tipo_vivienda'],
            'servicios_agua' => $registro['servicios_agua'],
            'servicios_electricidad' => $registro['servicios_electricidad'],
            'servicios_internet' => $registro['servicios_internet'],
            'servicios_gas' => $registro['servicios_gas'],
            'tecnologia_computadora' => $registro['tecnologia_computadora'],
            'tecnologia_smartphone' => $registro['tecnologia_smartphone'],
            'tecnologia_tablet' => $registro['tecnologia_tablet'],
            'carnet_patria' => $registro['carnet_patria'],
            'codigo_patria' => $registro['codigo_patria'],
            'serial_patria' => $registro['serial_patria'],
            'carnet_psuv' => $registro['carnet_psuv'],
            'codigo_psuv' => $registro['codigo_psuv'],
            'serial_psuv' => $registro['serial_psuv']
        ];

        // Inicializar variables para asegurar que siempre estén definidas
        $estado_civil = $_POST['estado_civil'] ?? '';
        $nivel_academico_post_raw = $_POST['nivel_academico'] ?? [];
        $menciones_ingresadas_raw = $_POST['mencion'] ?? [];
        $instituciones_academicas_raw = $_POST['instituciones_academicas'] ?? [];

        // Reorganizar arrays para que los índices coincidan
        $nivel_academico_post = [];
        $menciones_ingresadas = [];
        $instituciones_academicas_post = [];

        $academic_entry_keys = array_keys($nivel_academico_post_raw);
        foreach ($academic_entry_keys as $key) {
            if (isset($nivel_academico_post_raw[$key])) {
                $nivel_academico_post[] = $nivel_academico_post_raw[$key];
                $menciones_ingresadas[] = $menciones_ingresadas_raw[$key] ?? '';
                $instituciones_academicas_post[] = $instituciones_academicas_raw[$key] ?? '';
            }
        }

        $tipo_vivienda = $_POST['tipo_vivienda'] ?? '';

        // Validar campos requeridos
        $errores = [];
        if (empty($estado_civil)) $errores[] = "El campo Estado Civil es requerido.";
        if (empty($nivel_academico_post) || in_array('', $nivel_academico_post)) {
            $errores[] = "Debe seleccionar al menos un Nivel Académico y que no esté vacío.";
        }
        if (empty($tipo_vivienda)) $errores[] = "El campo Tipo de Vivienda es requerido.";

        // Validar si se posee carnet de la patria y campos asociados
        $carnet_patria_post = $_POST['carnet_patria'] ?? 'No';
        $codigo_patria = NULL;
        $serial_patria = NULL;
        if ($carnet_patria_post === 'Sí') {
            $codigo_patria = trim($_POST['codigo_patria'] ?? '');
            $serial_patria = trim($_POST['serial_patria'] ?? '');
            if (empty($codigo_patria)) $errores[] = "El código del Carnet de la Patria es obligatorio si posee el carnet.";
            if (empty($serial_patria)) $errores[] = "El serial del Carnet de la Patria es obligatorio si posee el carnet.";
        }

        // Validar si se posee carnet PSUV y campos asociados
        $carnet_psuv_post = $_POST['carnet_psuv'] ?? 'No';
        $codigo_psuv = NULL;
        $serial_psuv = NULL;
        if ($carnet_psuv_post === 'Sí') {
            $codigo_psuv = trim($_POST['codigo_psuv'] ?? '');
            $serial_psuv = trim($_POST['serial_psuv'] ?? '');
            if (empty($codigo_psuv)) $errores[] = "El código del Carnet PSUV es obligatorio si posee el carnet.";
            if (empty($serial_psuv)) $errores[] = "El serial del Carnet PSUV es obligatorio si posee el carnet.";
        }

        if (!empty($errores)) {
            $_SESSION['error'] = implode("<br>", $errores);
            // Registrar errores de validación
            registrarLog($conexion, $current_user_id, 'socioeconomic_validation_failed', 
                        "Errores de validación para ID Socioeconómico: $id_socioeconomico - " . implode('; ', $errores));
            
            // Vuelve a cargar los datos del POST para repoblar el formulario
            $registro = array_merge($registro, $_POST);
             // Reconvertir los campos de arrays para la interfaz
            $registro['nivel_academico_arr'] = $nivel_academico_post;
            $registro['mencion_arr'] = $menciones_ingresadas;
            $registro['instituciones_academicas_arr'] = $instituciones_academicas_post;
            $registro['carnet_patria'] = $carnet_patria_post;
            $registro['codigo_patria'] = $codigo_patria;
            $registro['serial_patria'] = $serial_patria;
            $registro['carnet_psuv'] = $carnet_psuv_post;
            $registro['codigo_psuv'] = $codigo_psuv;
            $registro['serial_psuv'] = $serial_psuv;

        } else {
            // Mapear valores de checkboxes
            $servicios = [
                'agua' => isset($_POST['servicios_agua']) ? 'Sí' : 'No',
                'electricidad' => isset($_POST['servicios_electricidad']) ? 'Sí' : 'No',
                'internet' => isset($_POST['servicios_internet']) ? 'Sí' : 'No',
                'gas' => isset($_POST['servicios_gas']) ? 'Sí' : 'No'
            ];

            $tecnologia = [
                'computadora' => isset($_POST['tecnologia_computadora']) ? 'Sí' : 'No',
                'smartphone' => isset($_POST['tecnologia_smartphone']) ? 'Sí' : 'No',
                'tablet' => isset($_POST['tecnologia_tablet']) ? 'Sí' : 'No'
            ];
            
            // Combinar niveles, menciones e instituciones en strings separados por "|"
            $nivel_academico_str = implode("|", $nivel_academico_post);
            $mencion_str = implode("|", $menciones_ingresadas);
            $instituciones_academicas_str = implode("|", $instituciones_academicas_post);

            $stmt_update = $conexion->prepare("
                UPDATE datos_socioeconomicos SET
                    estado_civil = ?,
                    nivel_academico = ?,
                    mencion = ?,
                    instituciones_academicas = ?,
                    tipo_vivienda = ?,
                    servicios_agua = ?,
                    servicios_electricidad = ?,
                    servicios_internet = ?,
                    servicios_gas = ?,
                    tecnologia_computadora = ?,
                    tecnologia_smartphone = ?,
                    tecnologia_tablet = ?,
                    carnet_patria = ?,
                    codigo_patria = ?,
                    serial_patria = ?,
                    carnet_psuv = ?,
                    codigo_psuv = ?,
                    serial_psuv = ?,
                    fecha_actualizacion = NOW()
                WHERE id_socioeconomico = ?
            ");

            $stmt_update->bind_param("ssssssssssssssssssi",
                $estado_civil,
                $nivel_academico_str,
                $mencion_str,
                $instituciones_academicas_str,
                $tipo_vivienda,
                $servicios['agua'],
                $servicios['electricidad'],
                $servicios['internet'],
                $servicios['gas'],
                $tecnologia['computadora'],
                $tecnologia['smartphone'],
                $tecnologia['tablet'],
                $carnet_patria_post,
                $codigo_patria,
                $serial_patria,
                $carnet_psuv_post,
                $codigo_psuv,
                $serial_psuv,
                $id_socioeconomico
            );

            if ($stmt_update->execute()) {
                // Detectar cambios importantes
                $cambios = [];
                $campos_importantes = [
                    'estado_civil', 'tipo_vivienda', 'carnet_patria', 'carnet_psuv'
                ];
                
                // Verificar cambios en campos importantes
                foreach ($campos_importantes as $campo) {
                    $valor_anterior = $valores_anteriores[$campo];
                    $valor_nuevo = $$campo;
                    
                    if ($valor_anterior !== $valor_nuevo) {
                        $cambios[] = "$campo: $valor_anterior → $valor_nuevo";
                    }
                }
                
                // Verificar cambios en servicios
                $servicios_cambiados = [];
                $servicios_anteriores = [
                    'agua' => $valores_anteriores['servicios_agua'],
                    'electricidad' => $valores_anteriores['servicios_electricidad'],
                    'internet' => $valores_anteriores['servicios_internet'],
                    'gas' => $valores_anteriores['servicios_gas']
                ];
                
                foreach ($servicios as $servicio => $valor) {
                    if ($servicios_anteriores[$servicio] !== $valor) {
                        $servicios_cambiados[] = "$servicio: {$servicios_anteriores[$servicio]} → $valor";
                    }
                }
                
                if (!empty($servicios_cambiados)) {
                    $cambios[] = "Servicios: " . implode(', ', $servicios_cambiados);
                }
                
                // Verificar cambios en tecnología
                $tecnologia_cambiada = [];
                $tecnologia_anterior = [
                    'computadora' => $valores_anteriores['tecnologia_computadora'],
                    'smartphone' => $valores_anteriores['tecnologia_smartphone'],
                    'tablet' => $valores_anteriores['tecnologia_tablet']
                ];
                
                foreach ($tecnologia as $dispositivo => $valor) {
                    if ($tecnologia_anterior[$dispositivo] !== $valor) {
                        $tecnologia_cambiada[] = "$dispositivo: {$tecnologia_anterior[$dispositivo]} → $valor";
                    }
                }
                
                if (!empty($tecnologia_cambiada)) {
                    $cambios[] = "Tecnología: " . implode(', ', $tecnologia_cambiada);
                }
                
                // Verificar cambios en carnets
                $carnets_cambiados = [];
                if ($valores_anteriores['codigo_patria'] !== $codigo_patria) {
                    $carnets_cambiados[] = "Código Patria";
                }
                if ($valores_anteriores['serial_patria'] !== $serial_patria) {
                    $carnets_cambiados[] = "Serial Patria";
                }
                if ($valores_anteriores['codigo_psuv'] !== $codigo_psuv) {
                    $carnets_cambiados[] = "Código PSUV";
                }
                if ($valores_anteriores['serial_psuv'] !== $serial_psuv) {
                    $carnets_cambiados[] = "Serial PSUV";
                }
                
                if (!empty($carnets_cambiados)) {
                    $cambios[] = "Detalles carnets: " . implode(', ', $carnets_cambiados);
                }
                
                // Verificar cambios en educación
                $educacion_cambiada = false;
                if ($valores_anteriores['nivel_academico'] !== $nivel_academico_str) {
                    $educacion_cambiada = true;
                }
                if ($valores_anteriores['mencion'] !== $mencion_str) {
                    $educacion_cambiada = true;
                }
                if ($valores_anteriores['instituciones_academicas'] !== $instituciones_academicas_str) {
                    $educacion_cambiada = true;
                }
                
                if ($educacion_cambiada) {
                    $cambios[] = "Educación actualizada";
                }
                
                // Registrar cambios
                $detalles_cambios = empty($cambios) ? "Sin cambios detectados" : implode('; ', $cambios);
                registrarLog($conexion, $current_user_id, 'socioeconomic_updated', 
                            "Actualización exitosa para ID Socioeconómico: $id_socioeconomico - $detalles_cambios");
                
                $_SESSION['mensaje'] = "¡Registro actualizado exitosamente!";
                header("Location: ../gestion_socioeconomicos.php");
                exit();
            } else {
                $_SESSION['error'] = "Error al actualizar datos socioeconómicos: " . $stmt_update->error;
                registrarLog($conexion, $current_user_id, 'socioeconomic_update_error', 
                            "Error al actualizar ID Socioeconómico: $id_socioeconomico - " . $stmt_update->error);
            }
            $stmt_update->close();
        }

    } catch (Exception $e) {
        $_SESSION['error'] = "Error inesperado: " . $e->getMessage();
        registrarLog($conexion, $current_user_id, 'socioeconomic_exception', 
                    "Excepción en edición ID Socioeconómico: $id_socioeconomico - " . $e->getMessage());
    }
}

// Si la página se carga por GET o después de un error POST, preparar los datos para mostrar
if (!isset($registro['nivel_academico_arr'])) {
    $registro['nivel_academico_arr'] = explode('|', $registro['nivel_academico'] ?? '');
    $registro['mencion_arr'] = explode('|', $registro['mencion'] ?? '');
    $registro['instituciones_academicas_arr'] = explode('|', $registro['instituciones_academicas'] ?? '');
}

// Asegurar que al menos haya un campo académico si no hay ninguno
if (empty($registro['nivel_academico_arr'][0]) && count($registro['nivel_academico_arr']) == 1) {
    $registro['nivel_academico_arr'][0] = '';
    $registro['mencion_arr'][0] = '';
    $registro['instituciones_academicas_arr'][0] = '';
}

// Asignar valores por defecto para evitar errores en el HTML si son NULL
$registro['servicios_agua'] = $registro['servicios_agua'] ?? 'No';
$registro['servicios_electricidad'] = $registro['servicios_electricidad'] ?? 'No';
$registro['servicios_internet'] = $registro['servicios_internet'] ?? 'No';
$registro['servicios_gas'] = $registro['servicios_gas'] ?? 'No';
$registro['tecnologia_computadora'] = $registro['tecnologia_computadora'] ?? 'No';
$registro['tecnologia_smartphone'] = $registro['tecnologia_smartphone'] ?? 'No';
$registro['tecnologia_tablet'] = $registro['tecnologia_tablet'] ?? 'No';
$registro['carnet_patria'] = $registro['carnet_patria'] ?? 'No';
$registro['codigo_patria'] = $registro['codigo_patria'] ?? '';
$registro['serial_patria'] = $registro['serial_patria'] ?? '';
$registro['carnet_psuv'] = $registro['carnet_psuv'] ?? 'No';
$registro['codigo_psuv'] = $registro['codigo_psuv'] ?? '';
$registro['serial_psuv'] = $registro['serial_psuv'] ?? '';

$conexion->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Datos Socioeconómicos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
        .checkbox-group label {
            margin-right: 20px;
        }
        .academic-entry {
            border: 1px solid #e9ecef;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            background-color: #fcfcfc;
        }
        .btn-add-remove {
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-container-custom">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="text-primary mb-0">
                    <i class="bi bi-wallet-fill me-2"></i>Editar Datos Socioeconómicos
                </h1>
                <a href="../gestion_socioeconomicos.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Volver a Gestión
                </a>
            </div>

            <?php if(isset($_SESSION['error'])): ?>
                <div class="alert alert-danger mb-4" role="alert">
                    <h4 class="alert-heading"><i class="bi bi-exclamation-triangle-fill me-2"></i>Errores de Validación:</h4>
                    <ul class="mb-0">
                        <li><?= $_SESSION['error'] ?></li>
                    </ul>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <?php if(isset($_SESSION['mensaje'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <div>
                            <h5 class="mb-0">¡Éxito!</h5>
                            <p class="mb-0"><?= htmlspecialchars($_SESSION['mensaje']) ?></p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['mensaje']); ?>
            <?php endif; ?>
        
            <form method="POST">
                <input type="hidden" name="id_socioeconomico" value="<?= htmlspecialchars($id_socioeconomico) ?>">
                
                <div class="form-section-header">
                    <h2><i class="bi bi-person-fill me-2"></i>Información del Trabajador</h2>
                </div>
                <div class="mb-3">
                    <label class="form-label">Trabajador:</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($registro['nombres'] . ' ' . $registro['apellidos']) ?>" readonly>
                </div>

                <div class="form-section-header mt-5">
                    <h2><i class="bi bi-person-heart me-2"></i>Datos Básicos</h2>
                </div>
                <div class="mb-3">
                    <label for="estado_civil" class="form-label">Estado Civil*:</label>
                    <select name="estado_civil" id="estado_civil" class="form-select" required>
                        <option value="">Seleccione...</option>
                        <option value="Soltero/a" <?= (($registro['estado_civil'] ?? '') == 'Soltero/a') ? 'selected' : '' ?>>Soltero/a</option>
                        <option value="Casado/a" <?= (($registro['estado_civil'] ?? '') == 'Casado/a') ? 'selected' : '' ?>>Casado/a</option>
                        <option value="Divorciado/a" <?= (($registro['estado_civil'] ?? '') == 'Divorciado/a') ? 'selected' : '' ?>>Divorciado/a</option>
                        <option value="Viudo/a" <?= (($registro['estado_civil'] ?? '') == 'Viudo/a') ? 'selected' : '' ?>>Viudo/a</option>
                        <option value="Unión Libre" <?= (($registro['estado_civil'] ?? '') == 'Unión Libre') ? 'selected' : '' ?>>Unión Libre</option>
                    </select>
                </div>
                
                <div class="form-section-header mt-5">
                    <h2><i class="bi bi-mortarboard-fill me-2"></i>Nivel Educativo</h2>
                </div>
                <div id="academic-fields-container">
                    <?php 
                    foreach ($registro['nivel_academico_arr'] as $index => $nivel_val):
                        $mencion_val = $registro['mencion_arr'][$index] ?? '';
                        $instituciones_val = $registro['instituciones_academicas_arr'][$index] ?? '';
                    ?>
                    <div class="academic-entry mb-3 p-3 border rounded">
                        <div class="mb-3">
                            <label for="nivel_academico_<?= $index ?>" class="form-label">Nivel Académico*:</label>
                            <select name="nivel_academico[<?= $index ?>]" id="nivel_academico_<?= $index ?>" class="form-select academic-level-select" required>
                                <option value="">Seleccione...</option>
                                <?php
                                $academic_levels = [
                                    "Primaria Incompleta", "Primaria Completa", "Secundaria Incompleta",
                                    "Secundaria Completa", "Técnico", "Universitario", "Postgrado",
                                    "Magister", "Ninguno"
                                ];
                                foreach ($academic_levels as $level):
                                    $selected = ($nivel_val == $level) ? 'selected' : '';
                                ?>
                                <option value="<?= $level ?>" <?= $selected ?>><?= $level ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="mencion_<?= $index ?>" class="form-label">Mención:</label>
                            <input type="text" name="mencion[<?= $index ?>]" id="mencion_<?= $index ?>" class="form-control" 
                                   value="<?= htmlspecialchars($mencion_val) ?>">
                        </div>

                        <div class="mb-3">
                            <label for="instituciones_academicas_<?= $index ?>" class="form-label">Institución(es) Académica(s):</label>
                            <input type="text" name="instituciones_academicas[<?= $index ?>]" id="instituciones_academicas_<?= $index ?>" class="form-control" 
                                   placeholder="Ej: UPTT MBI, ULA" value="<?= htmlspecialchars($instituciones_val) ?>">
                        </div>

                        <?php if ($index > 0 || count($registro['nivel_academico_arr']) > 1): // Permitir eliminar si no es el primer campo o si hay más de uno ?>
                            <button type="button" class="btn btn-danger btn-sm remove-academic-field">
                                <i class="bi bi-dash-circle me-2"></i>Eliminar Nivel
                            </button>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm btn-add-remove" id="addAcademicFieldBtn">
                    <i class="bi bi-plus-circle me-2"></i>Añadir otro Nivel Educativo
                </button>

                <div class="form-section-header mt-5">
                    <h2><i class="bi bi-house-fill me-2"></i>Vivienda</h2>
                </div>
                <div class="mb-3">
                    <label for="tipo_vivienda" class="form-label">Tipo de Vivienda*:</label>
                    <select name="tipo_vivienda" id="tipo_vivienda" class="form-select" required>
                        <option value="">Seleccione...</option>
                        <option value="Propia" <?= (($registro['tipo_vivienda'] ?? '') == 'Propia') ? 'selected' : '' ?>>Propia</option>
                        <option value="Alquilada" <?= (($registro['tipo_vivienda'] ?? '') == 'Alquilada') ? 'selected' : '' ?>>Alquilada</option>
                        <option value="Prestada" <?= (($registro['tipo_vivienda'] ?? '') == 'Prestada') ? 'selected' : '' ?>>Prestada</option>
                        <option value="Invadida" <?= (($registro['tipo_vivienda'] ?? '') == 'Invadida') ? 'selected' : '' ?>>Invadida</option>
                        <option value="Otro" <?= (($registro['tipo_vivienda'] ?? '') == 'Otro') ? 'selected' : '' ?>>Otro</option>
                    </select>
                </div>

                <div class="form-section-header mt-5">
                    <h2><i class="bi bi-lightning-charge-fill me-2"></i>Servicios Básicos</h2>
                </div>
                <div class="mb-3 checkbox-group">
                    <label class="form-label">Acceso a:</label>
                    <div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="servicios_agua" id="servicios_agua" value="Sí" <?= ($registro['servicios_agua'] == 'Sí') ? 'checked' : '' ?>>
                            <label class="form-check-label" for="servicios_agua">Agua Potable</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="servicios_electricidad" id="servicios_electricidad" value="Sí" <?= ($registro['servicios_electricidad'] == 'Sí') ? 'checked' : '' ?>>
                            <label class="form-check-label" for="servicios_electricidad">Electricidad</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="servicios_internet" id="servicios_internet" value="Sí" <?= ($registro['servicios_internet'] == 'Sí') ? 'checked' : '' ?>>
                            <label class="form-check-label" for="servicios_internet">Internet</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="servicios_gas" id="servicios_gas" value="Sí" <?= ($registro['servicios_gas'] == 'Sí') ? 'checked' : '' ?>>
                            <label class="form-check-label" for="servicios_gas">Gas</label>
                        </div>
                    </div>
                </div>

                <div class="form-section-header mt-5">
                    <h2><i class="bi bi-display-fill me-2"></i>Tecnología Disponible</h2>
                </div>
                <div class="mb-3 checkbox-group">
                    <label class="form-label">Dispositivos:</label>
                    <div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="tecnologia_computadora" id="tecnologia_computadora" value="Sí" <?= ($registro['tecnologia_computadora'] == 'Sí') ? 'checked' : '' ?>>
                            <label class="form-check-label" for="tecnologia_computadora">Computadora</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="tecnologia_smartphone" id="tecnologia_smartphone" value="Sí" <?= ($registro['tecnologia_smartphone'] == 'Sí') ? 'checked' : '' ?>>
                            <label class="form-check-label" for="tecnologia_smartphone">Smartphone</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="tecnologia_tablet" id="tecnologia_tablet" value="Sí" <?= ($registro['tecnologia_tablet'] == 'Sí') ? 'checked' : '' ?>>
                            <label class="form-check-label" for="tecnologia_tablet">Tablet</label>
                        </div>
                    </div>
                </div>

                <div class="form-section-header mt-5">
                    <h2><i class="bi bi-credit-card-2-front-fill me-2"></i>Documentos</h2>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">¿Posee Carnet de la Patria?*</label>
                    <div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="carnet_patria" id="carnet_patria_si" value="Sí" <?= ($registro['carnet_patria'] == 'Sí') ? 'checked' : '' ?> required>
                            <label class="form-check-label" for="carnet_patria_si">Sí</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="carnet_patria" id="carnet_patria_no" value="No" <?= ($registro['carnet_patria'] == 'No') ? 'checked' : '' ?>>
                            <label class="form-check-label" for="carnet_patria_no">No</label>
                        </div>
                    </div>
                    <div id="patria_fields" class="conditional-field d-none mt-3">
                        <div class="mb-3">
                            <label for="codigo_patria" class="form-label">Código del Carnet*:</label>
                            <input type="text" name="codigo_patria" id="codigo_patria" class="form-control" placeholder="Código" value="<?= htmlspecialchars($registro['codigo_patria'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label for="serial_patria" class="form-label">Serial del Carnet*:</label>
                            <input type="text" name="serial_patria" id="serial_patria" class="form-control" placeholder="Serial" value="<?= htmlspecialchars($registro['serial_patria'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">¿Posee Carnet PSUV?*</label>
                    <div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="carnet_psuv" id="carnet_psuv_si" value="Sí" <?= ($registro['carnet_psuv'] == 'Sí') ? 'checked' : '' ?> required>
                            <label class="form-check-label" for="carnet_psuv_si">Sí</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="carnet_psuv" id="carnet_psuv_no" value="No" <?= ($registro['carnet_psuv'] == 'No') ? 'checked' : '' ?>>
                            <label class="form-check-label" for="carnet_psuv_no">No</label>
                        </div>
                    </div>
                    <div id="psuv_fields" class="conditional-field d-none mt-3">
                        <div class="mb-3">
                            <label for="codigo_psuv" class="form-label">Código del Carnet*:</label>
                            <input type="text" name="codigo_psuv" id="codigo_psuv" class="form-control" placeholder="Código" value="<?= htmlspecialchars($registro['codigo_psuv'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label for="serial_psuv" class="form-label">Serial del Carnet*:</label>
                            <input type="text" name="serial_psuv" id="serial_psuv" class="form-control" placeholder="Serial" value="<?= htmlspecialchars($registro['serial_psuv'] ?? '') ?>">
                        </div>
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
        // academicFieldIndex se inicializa con el valor PHP para manejar múltiples niveles en caso de un POST con errores
        let academicFieldIndex = <?= count($registro['nivel_academico_arr']) ?>;
        
        document.addEventListener('DOMContentLoaded', function() {
            // Función para alternar la visibilidad y requisitos de campos condicionales
            function toggleFields(containerId, inputIds, show, makeInputsRequired = false) {
                const container = document.getElementById(containerId);
                if (!container) {
                    console.error(`[Generic Toggle ERROR] Container '${containerId}' not found.`);
                    return;
                }

                if (show) {
                    container.classList.remove('d-none');
                    inputIds.forEach(id => {
                        const input = document.getElementById(id);
                        if (input) {
                            input.removeAttribute('disabled');
                            if (makeInputsRequired) {
                                input.setAttribute('required', 'required');
                            }
                        } else {
                            console.error(`[Generic Toggle ERROR] Input '${id}' not found within '${containerId}'.`);
                        }
                    });
                } else {
                    container.classList.add('d-none');
                    inputIds.forEach(id => {
                        const input = document.getElementById(id);
                        if (input) {
                            input.setAttribute('disabled', 'disabled');
                            input.removeAttribute('required');
                            input.value = ''; // Limpiar el valor al ocultar
                        } else {
                            console.error(`[Generic Toggle ERROR] Input '${id}' not found within '${containerId}'.`);
                        }
                    });
                }
            }

            // Función para añadir un nuevo campo académico
            function addAcademicField() {
                const container = document.getElementById('academic-fields-container');
                const newEntry = document.createElement('div');
                newEntry.classList.add('academic-entry', 'mb-3', 'p-3', 'border', 'rounded');
                
                const currentAcademicIndex = academicFieldIndex++;

                newEntry.innerHTML = `
                    <div class="mb-3">
                        <label for="nivel_academico_${currentAcademicIndex}" class="form-label">Nivel Académico*:</label>
                        <select name="nivel_academico[${currentAcademicIndex}]" id="nivel_academico_${currentAcademicIndex}" class="form-select academic-level-select" required>
                            <option value="">Seleccione...</option>
                            <option value="Primaria Incompleta">Primaria Incompleta</option>
                            <option value="Primaria Completa">Primaria Completa</option>
                            <option value="Secundaria Incompleta">Secundaria Incompleta</option>
                            <option value="Secundaria Completa">Secundaria Completa</option>
                            <option value="Técnico">Técnico</option>
                            <option value="Universitario">Universitario</option>
                            <option value="Postgrado">Postgrado</option>
                            <option value="Magister">Magister</option>
                            <option value="Ninguno">Ninguno</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="mencion_${currentAcademicIndex}" class="form-label">Mención:</label>
                        <input type="text" name="mencion[${currentAcademicIndex}]" id="mencion_${currentAcademicIndex}" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label for="instituciones_academicas_${currentAcademicIndex}" class="form-label">Institución(es) Académica(s):</label>
                        <input type="text" name="instituciones_academicas[${currentAcademicIndex}]" id="instituciones_academicas_${currentAcademicIndex}" class="form-control" placeholder="Ej: UPTT MBI, ULA">
                    </div>

                    <button type="button" class="btn btn-danger btn-sm remove-academic-field">
                        <i class="bi bi-dash-circle me-2"></i>Eliminar Nivel
                    </button>
                `;
                
                container.appendChild(newEntry);
            }

            // Event listener para el botón "Añadir otro Nivel Educativo"
            document.getElementById('addAcademicFieldBtn').addEventListener('click', addAcademicField);

            // Manejar la eliminación de campos académicos existentes
            document.getElementById('academic-fields-container').addEventListener('click', function(event) {
                if (event.target.classList.contains('remove-academic-field') || event.target.closest('.remove-academic-field')) {
                    const button = event.target.closest('.remove-academic-field');
                    const entry = button.closest('.academic-entry');
                    if (document.querySelectorAll('.academic-entry').length > 1) { 
                        entry.remove();
                    } else {
                        // Aquí podrías mostrar un modal personalizado en lugar de alert
                        alert("Debe haber al menos un nivel educativo.");
                    }
                }
            });

            // --- Lógica para Carnets (Patria y PSUV) ---
            function setupCarnetLogic(tipo) {
                const siRadio = document.getElementById(`carnet_${tipo}_si`);
                const noRadio = document.getElementById(`carnet_${tipo}_no`);
                const containerId = `${tipo}_fields`;
                const codigoInputId = `codigo_${tipo}`;
                const serialInputId = `serial_${tipo}`;

                // Función para actualizar el estado del carnet
                function updateCarnetState() {
                    const show = siRadio && siRadio.checked; 
                    toggleFields(containerId, [codigoInputId, serialInputId], show, true); // Ambos son requeridos si se muestran
                }

                // Inicializar al cargar la página
                if (siRadio) { 
                    updateCarnetState(); 
                } else {
                    console.error(`[Carnet Setup ERROR] siRadio for ${tipo} not found.`);
                }

                // Añadir event listeners para los cambios de radio
                if (siRadio) siRadio.addEventListener('change', updateCarnetState);
                if (noRadio) noRadio.addEventListener('change', updateCarnetState);
            }

            // Configurar lógica para Carnet de la Patria
            setupCarnetLogic('patria');
            // Configurar lógica para Carnet PSUV
            setupCarnetLogic('psuv');
        });
    </script>
</body>
</html>
