<?php
session_start();
require_once __DIR__ . '/../conexion/conexion_db.php';

// Función para registrar logs (ajustada al estilo de form_datospersonales)
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

// Verificar sesión de usuario
if (!isset($_SESSION['usuario'])) {
    $detalles_log = "Intento de acceso no autorizado a formulario socioeconómico";
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
$id_pers = $_GET['id_pers'] ?? 0;
$nombres = urldecode($_GET['nombres'] ?? '');
$apellidos = urldecode($_GET['apellidos'] ?? '');

$conn = new mysqli($servidor, $usuario, $contraseña, $basedatos);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Registrar visualización del formulario
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $id_pers) {
    registrarLog($conn, $current_user_id, 'view_socioeconomic_form', 
                "Visualización de formulario para ID Persona: $id_pers");
}

$errores = [];
$mensajeExito = '';

// Si no se proporcionan id_pers, nombres o apellidos, redirigir
if (empty($_POST) && (!$id_pers || !$nombres || !$apellidos)) {
    $_SESSION['mensaje'] = [
        'titulo' => 'Error de Acceso',
        'contenido' => 'Datos de trabajador no proporcionados. Vuelva a la página de registro de personal.',
        'tipo' => 'danger'
    ];
    
    registrarLog($conn, $current_user_id, 'invalid_socioeconomic_access', 
                "Acceso sin datos de trabajador (ID Persona: $id_pers)");
    
    header("Location: ../gestion_personal.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Es importante reobtener id_pers, nombres, y apellidos del POST si fueron enviados o mantener los de GET
    $id_pers = $_POST['id_pers'] ?? $id_pers;
    $nombres = $_POST['nombres_persona'] ?? $nombres;
    $apellidos = $_POST['apellidos_persona'] ?? $apellidos;

    // Inicializar variables para asegurar que siempre estén definidas
    $estado_civil = $_POST['estado_civil'] ?? '';
    $nivel_academico_post_raw = $_POST['nivel_academico'] ?? [];
    $menciones_ingresadas_raw = $_POST['mencion'] ?? [];
    $instituciones_academicas_raw = $_POST['instituciones_academicas'] ?? [];

    // Reorganizar arrays para que los índices coincidan
    $nivel_academico_post = [];
    $menciones_ingresadas = [];
    $instituciones_academicas_post = [];

    if (is_array($nivel_academico_post_raw)) {
        foreach ($nivel_academico_post_raw as $key => $value) {
            if (!empty($value)) {
                $nivel_academico_post[] = $value;
                $menciones_ingresadas[] = $menciones_ingresadas_raw[$key] ?? '';
                $instituciones_academicas_post[] = $instituciones_academicas_raw[$key] ?? '';
            }
        }
    }

    $tipo_vivienda = $_POST['tipo_vivienda'] ?? '';

    // Validar campos requeridos
    if (empty($estado_civil)) $errores[] = "El estado civil es requerido.";
    if (empty($tipo_vivienda)) $errores[] = "El tipo de vivienda es requerido.";

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

    // Validar el nivel académico dinámico
    if (empty($nivel_academico_post)) {
        $errores[] = "Debe añadir al menos un nivel educativo.";
    } else {
        foreach ($nivel_academico_post as $nivel) {
            if (empty($nivel)) {
                $errores[] = "Ningún nivel académico puede estar vacío.";
                break;
            }
        }
    }

    if (empty($errores)) {
        try {
            $pdo = new PDO("mysql:host=$servidor;dbname=$basedatos", $usuario, $contraseña);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 

            // Convertir arrays a strings separados por '|'
            $nivel_academico_str = implode("|", $nivel_academico_post);
            $mencion_str = implode("|", $menciones_ingresadas);
            $instituciones_academicas_str = implode("|", $instituciones_academicas_post);
            
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

            // Verificar si ya existe un registro socioeconómico para este id_pers
            $stmt_check = $pdo->prepare("SELECT id_socioeconomico FROM datos_socioeconomicos WHERE id_pers = :id_pers");
            $stmt_check->bindParam(':id_pers', $id_pers, PDO::PARAM_INT);
            $stmt_check->execute();
            $existing_socioeconomico = $stmt_check->fetch(PDO::FETCH_ASSOC);

            if ($existing_socioeconomico) {
                // Actualizar registro existente
                $stmt = $pdo->prepare("
                    UPDATE datos_socioeconomicos SET
                        estado_civil = :estado_civil,
                        nivel_academico = :nivel_academico,
                        mencion = :mencion,
                        instituciones_academica = :instituciones_academica,
                        tipo_vivienda = :tipo_vivienda,
                        servicios_agua = :servicios_agua,
                        servicios_electricidad = :servicios_electricidad,
                        servicios_internet = :servicios_internet,
                        servicios_gas = :servicios_gas,
                        tecnologia_computadora = :tecnologia_computadora,
                        tecnologia_smartphone = :tecnologia_smartphone,
                        tecnologia_tablet = :tecnologia_tablet,
                        carnet_patria = :carnet_patria,
                        codigo_patria = :codigo_patria,
                        serial_patria = :serial_patria,
                        carnet_psuv = :carnet_psuv,
                        codigo_psuv = :codigo_psuv,
                        serial_psuv = :serial_psuv
                    WHERE id_socioeconomico = :id_socioeconomico
                ");

                $stmt->bindParam(':estado_civil', $estado_civil);
                $stmt->bindParam(':nivel_academico', $nivel_academico_str);
                $stmt->bindParam(':mencion', $mencion_str);
                $stmt->bindParam(':instituciones_academica', $instituciones_academicas_str);
                $stmt->bindParam(':tipo_vivienda', $tipo_vivienda);
                $stmt->bindParam(':servicios_agua', $servicios['agua']);
                $stmt->bindParam(':servicios_electricidad', $servicios['electricidad']);
                $stmt->bindParam(':servicios_internet', $servicios['internet']);
                $stmt->bindParam(':servicios_gas', $servicios['gas']);
                $stmt->bindParam(':tecnologia_computadora', $tecnologia['computadora']);
                $stmt->bindParam(':tecnologia_smartphone', $tecnologia['smartphone']);
                $stmt->bindParam(':tecnologia_tablet', $tecnologia['tablet']);
                $stmt->bindParam(':carnet_patria', $carnet_patria_post);
                $stmt->bindParam(':codigo_patria', $codigo_patria);
                $stmt->bindParam(':serial_patria', $serial_patria);
                $stmt->bindParam(':carnet_psuv', $carnet_psuv_post);
                $stmt->bindParam(':codigo_psuv', $codigo_psuv);
                $stmt->bindParam(':serial_psuv', $serial_psuv);
                $stmt->bindParam(':id_socioeconomico', $existing_socioeconomico['id_socioeconomico'], PDO::PARAM_INT);
                
                $stmt->execute();
                $id_socioeconomico_inserted = $existing_socioeconomico['id_socioeconomico'];
                $mensajeExito = "Datos socioeconómicos actualizados correctamente.";

            } else {
                // Insertar nuevo registro
                $stmt = $pdo->prepare("
                    INSERT INTO datos_socioeconomicos (
                        id_pers, estado_civil, nivel_academico, mencion, instituciones_academicas, tipo_vivienda,
                        servicios_agua, servicios_electricidad, servicios_internet, servicios_gas,
                        tecnologia_computadora, tecnologia_smartphone, tecnologia_tablet,
                        carnet_patria, codigo_patria, serial_patria, carnet_psuv, codigo_psuv, serial_psuv
                    ) VALUES (
                        :id_pers, :estado_civil, :nivel_academico, :mencion, :instituciones_academicas, :tipo_vivienda,
                        :servicios_agua, :servicios_electricidad, :servicios_internet, :servicios_gas,
                        :tecnologia_computadora, :tecnologia_smartphone, :tecnologia_tablet,
                        :carnet_patria, :codigo_patria, :serial_patria, :carnet_psuv, :codigo_psuv, :serial_psuv
                    )
                ");

                $stmt->bindParam(':id_pers', $id_pers, PDO::PARAM_INT);
                $stmt->bindParam(':estado_civil', $estado_civil);
                $stmt->bindParam(':nivel_academico', $nivel_academico_str);
                $stmt->bindParam(':mencion', $mencion_str);
                $stmt->bindParam(':instituciones_academicas', $instituciones_academicas_str);
                $stmt->bindParam(':tipo_vivienda', $tipo_vivienda);
                $stmt->bindParam(':servicios_agua', $servicios['agua']);
                $stmt->bindParam(':servicios_electricidad', $servicios['electricidad']);
                $stmt->bindParam(':servicios_internet', $servicios['internet']);
                $stmt->bindParam(':servicios_gas', $servicios['gas']);
                $stmt->bindParam(':tecnologia_computadora', $tecnologia['computadora']);
                $stmt->bindParam(':tecnologia_smartphone', $tecnologia['smartphone']);
                $stmt->bindParam(':tecnologia_tablet', $tecnologia['tablet']);
                $stmt->bindParam(':carnet_patria', $carnet_patria_post);
                $stmt->bindParam(':codigo_patria', $codigo_patria);
                $stmt->bindParam(':serial_patria', $serial_patria);
                $stmt->bindParam(':carnet_psuv', $carnet_psuv_post);
                $stmt->bindParam(':codigo_psuv', $codigo_psuv);
                $stmt->bindParam(':serial_psuv', $serial_psuv);

                $stmt->execute();
                $id_socioeconomico_inserted = $pdo->lastInsertId();
                $mensajeExito = "Datos socioeconómicos registrados correctamente.";
            }

            // REGISTRO DE ÉXITO - AGREGADO
            $tipo_operacion = $existing_socioeconomico ? 'actualización' : 'creación';
            registrarLog($conn, $current_user_id, 'socioeconomic_success', 
                        "Operación exitosa ($tipo_operacion) para ID Persona: $id_pers");
            
            $_SESSION['mensaje'] = [
                'titulo' => '¡Éxito!',
                'contenido' => $mensajeExito,
                'tipo' => 'success'
            ];
            
            // Redirigir al formulario de carga familiar
            header("Location: form_cargafamiliar.php?id_socioeco=" . urlencode($id_socioeconomico_inserted) . "&id_pers=" . urlencode($id_pers) . "&nombres=" . urlencode($nombres) . "&apellidos=" . urlencode($apellidos) . "&source=new_employee_flow");
            exit;

        } catch (PDOException $e) {
            $errores[] = "Error al guardar los datos socioeconómicos: " . $e->getMessage();
            
            // Registrar error de base de datos
            registrarLog($conn, $current_user_id, 'socioeconomic_save_error', 
                        "Error al guardar datos para ID Persona: $id_pers - " . $e->getMessage());
            
            error_log("Error PDO en form_datossocioeco.php: " . $e->getMessage());
        } catch (Exception $e) {
            $errores[] = "Error: " . $e->getMessage();
            
            // Registrar excepción
            registrarLog($conn, $current_user_id, 'socioeconomic_exception', 
                        "Excepción para ID Persona: $id_pers - " . $e->getMessage());
            
            error_log("Error General en form_datossocioeco.php: " . $e->getMessage());
        }
    } 
    // Si hay errores de validación
    if (!empty($errores)) {
        // Registrar errores de validación
        $detalles_errores = implode('; ', $errores);
        registrarLog($conn, $current_user_id, 'socioeconomic_validation_failed', 
                    "Errores de validación para ID Persona: $id_pers - $detalles_errores");
        
        $_SESSION['mensaje'] = [
            'titulo' => 'Error de Validación',
            'contenido' => implode("<br>", $errores),
            'tipo' => 'danger'
        ];
    }
} else {
    // Si es una solicitud GET para cargar el formulario, buscar datos existentes
    $stmt = $conn->prepare("
        SELECT s.*, p.nombres, p.apellidos 
        FROM datos_socioeconomicos s
        INNER JOIN datos_personales p ON s.id_pers = p.id_pers
        WHERE s.id_pers = ?
    ");
    $stmt->bind_param("i", $id_pers);
    $stmt->execute();
    $registro = $stmt->get_result()->fetch_assoc();

    if ($registro) {
        // Decodificar los strings de los campos si existen
        $post_niveles = explode("|", $registro['nivel_academico'] ?? '');
        $post_menciones = explode("|", $registro['mencion'] ?? '');
        $post_instituciones = explode("|", $registro['institucion_academica'] ?? '');
        
        // Reconstruir $_POST para pre-llenar el formulario
        $_POST['estado_civil'] = $registro['estado_civil'];
        $_POST['nivel_academico'] = $post_niveles;
        $_POST['mencion'] = $post_menciones;
        $_POST['instituciones_academicas'] = $post_instituciones;
        $_POST['tipo_vivienda'] = $registro['tipo_vivienda'];
        
        $_POST['servicios_agua'] = $registro['servicios_agua'];
        $_POST['servicios_electricidad'] = $registro['servicios_electricidad'];
        $_POST['servicios_internet'] = $registro['servicios_internet'];
        $_POST['servicios_gas'] = $registro['servicios_gas'];
        
        $_POST['tecnologia_computadora'] = $registro['tecnologia_computadora'];
        $_POST['tecnologia_smartphone'] = $registro['tecnologia_smartphone'];
        $_POST['tecnologia_tablet'] = $registro['tecnologia_tablet'];

        $_POST['carnet_patria'] = $registro['carnet_patria'];
        $_POST['codigo_patria'] = $registro['codigo_patria'];
        $_POST['serial_patria'] = $registro['serial_patria'];
        $_POST['carnet_psuv'] = $registro['carnet_psuv'];
        $_POST['codigo_psuv'] = $registro['codigo_psuv'];
        $_POST['serial_psuv'] = $registro['serial_psuv'];

        // Actualizar nombres y apellidos para la visualización del título
        $nombres = $registro['nombres'];
        $apellidos = $registro['apellidos'];
    } else {
        // Si no hay registro, inicializar con valores vacíos
        $post_niveles = [''];
        $post_menciones = [''];
        $post_instituciones = [''];
    }
}
$conn->close();

// Recuperar errores y datos del formulario de la sesión si existen después de una redirección POST
if (isset($_SESSION['errores_form_socioeconomico'])) {
    $errores = $_SESSION['errores_form_socioeconomico'];
    unset($_SESSION['errores_form_socioeconomico']);
}
$post_data = $_SESSION['form_data_socioeconomico'] ?? $_POST; // Usar $_POST si no hay data de sesión
unset($_SESSION['form_data_socioeconomico']);

// Asegurarse de que $post_niveles, $post_menciones, $post_instituciones siempre sean arrays válidos para el bucle
if (!isset($post_niveles)) {
    $post_niveles = $post_data['nivel_academico'] ?? [''];
}
if (!isset($post_menciones)) {
    $post_menciones = $post_data['mencion'] ?? [''];
}
if (!isset($post_instituciones)) {
    $post_instituciones = $post_data['instituciones_academicas'] ?? [''];
}


?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro Socioeconómico</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/formularios_styles.css"> <style>
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
                    <i class="bi bi-wallet-fill me-2"></i>Registro de Datos Socioeconómicos
                </h1>
                <a href="../gestion/gestion_personal.php" class="btn btn-secondary">
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
        
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?id_pers=' . htmlspecialchars($id_pers) . '&nombres=' . urlencode($nombres) . '&apellidos=' . urlencode($apellidos);?>">
                <input type="hidden" name="id_pers" value="<?= htmlspecialchars($id_pers) ?>">
                <!-- Campos ocultos para mantener nombres y apellidos en el POST -->
                <input type="hidden" name="nombres_persona" value="<?= htmlspecialchars($nombres) ?>">
                <input type="hidden" name="apellidos_persona" value="<?= htmlspecialchars($apellidos) ?>">
                
                <div class="form-section-header">
                    <h2><i class="bi bi-person-fill me-2"></i>Información del Trabajador</h2>
                </div>
                <div class="mb-3">
                    <label class="form-label">Trabajador:</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($nombres . ' ' . $apellidos) ?>" readonly>
                </div>

                <div class="form-section-header mt-5">
                    <h2><i class="bi bi-person-heart me-2"></i>Datos Básicos</h2>
                </div>
                <div class="mb-3">
                    <label for="estado_civil" class="form-label">Estado Civil*:</label>
                    <select name="estado_civil" id="estado_civil" class="form-select" required>
                        <option value="">Seleccione...</option>
                        <option value="Soltero/a" <?= (($post_data['estado_civil'] ?? '') == 'Soltero/a') ? 'selected' : '' ?>>Soltero/a</option>
                        <option value="Casado/a" <?= (($post_data['estado_civil'] ?? '') == 'Casado/a') ? 'selected' : '' ?>>Casado/a</option>
                        <option value="Divorciado/a" <?= (($post_data['estado_civil'] ?? '') == 'Divorciado/a') ? 'selected' : '' ?>>Divorciado/a</option>
                        <option value="Viudo/a" <?= (($post_data['estado_civil'] ?? '') == 'Viudo/a') ? 'selected' : '' ?>>Viudo/a</option>
                        <option value="Unión Libre" <?= (($post_data['estado_civil'] ?? '') == 'Unión Libre') ? 'selected' : '' ?>>Unión Libre</option>
                    </select>
                </div>

                <div class="form-section-header mt-5">
                    <h2><i class="bi bi-mortarboard-fill me-2"></i>Nivel Educativo</h2>
                </div>
                <div id="academic-fields-container">
                    <?php 
                    foreach ($post_niveles as $index => $nivel_val):
                        $mencion_val = $post_menciones[$index] ?? '';
                        $instituciones_val = $post_instituciones[$index] ?? ''; 
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

                        <?php if ($index > 0): // Permitir eliminar si no es el primer campo ?>
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
                        <option value="Propia" <?= (($post_data['tipo_vivienda'] ?? '') == 'Propia') ? 'selected' : '' ?>>Propia</option>
                        <option value="Alquilada" <?= (($post_data['tipo_vivienda'] ?? '') == 'Alquilada') ? 'selected' : '' ?>>Alquilada</option>
                        <option value="Prestada" <?= (($post_data['tipo_vivienda'] ?? '') == 'Prestada') ? 'selected' : '' ?>>Prestada</option>
                        <option value="Invadida" <?= (($post_data['tipo_vivienda'] ?? '') == 'Invadida') ? 'selected' : '' ?>>Invadida</option>
                        <option value="Otro" <?= (($post_data['tipo_vivienda'] ?? '') == 'Otro') ? 'selected' : '' ?>>Otro</option>
                    </select>
                </div>

                <div class="form-section-header mt-5">
                    <h2><i class="bi bi-lightning-charge-fill me-2"></i>Servicios Básicos</h2>
                </div>
                <div class="mb-3 checkbox-group">
                    <label class="form-label">Acceso a:</label>
                    <div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="servicios_agua" id="servicios_agua" value="Sí" <?= (isset($post_data['servicios_agua']) && $post_data['servicios_agua'] == 'Sí') ? 'checked' : '' ?>>
                            <label class="form-check-label" for="servicios_agua">Agua Potable</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="servicios_electricidad" id="servicios_electricidad" value="Sí" <?= (isset($post_data['servicios_electricidad']) && $post_data['servicios_electricidad'] == 'Sí') ? 'checked' : '' ?>>
                            <label class="form-check-label" for="servicios_electricidad">Electricidad</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="servicios_internet" id="servicios_internet" value="Sí" <?= (isset($post_data['servicios_internet']) && $post_data['servicios_internet'] == 'Sí') ? 'checked' : '' ?>>
                            <label class="form-check-label" for="servicios_internet">Internet</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="servicios_gas" id="servicios_gas" value="Sí" <?= (isset($post_data['servicios_gas']) && $post_data['servicios_gas'] == 'Sí') ? 'checked' : '' ?>>
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
                            <input class="form-check-input" type="checkbox" name="tecnologia_computadora" id="tecnologia_computadora" value="Sí" <?= (isset($post_data['tecnologia_computadora']) && $post_data['tecnologia_computadora'] == 'Sí') ? 'checked' : '' ?>>
                            <label class="form-check-label" for="tecnologia_computadora">Computadora</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="tecnologia_smartphone" id="tecnologia_smartphone" value="Sí" <?= (isset($post_data['tecnologia_smartphone']) && $post_data['tecnologia_smartphone'] == 'Sí') ? 'checked' : '' ?>>
                            <label class="form-check-label" for="tecnologia_smartphone">Smartphone</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="tecnologia_tablet" id="tecnologia_tablet" value="Sí" <?= (isset($post_data['tecnologia_tablet']) && $post_data['tecnologia_tablet'] == 'Sí') ? 'checked' : '' ?>>
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
                            <input class="form-check-input" type="radio" name="carnet_patria" id="carnet_patria_si" value="Sí" <?= (($post_data['carnet_patria'] ?? 'No') == 'Sí') ? 'checked' : '' ?> required>
                            <label class="form-check-label" for="carnet_patria_si">Sí</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="carnet_patria" id="carnet_patria_no" value="No" <?= (($post_data['carnet_patria'] ?? 'No') == 'No') ? 'checked' : '' ?>>
                            <label class="form-check-label" for="carnet_patria_no">No</label>
                        </div>
                    </div>
                    <div id="patria_fields" class="conditional-field d-none mt-3">
                        <div class="mb-3">
                            <label for="codigo_patria" class="form-label">Código del Carnet*:</label>
                            <input type="text" name="codigo_patria" id="codigo_patria" class="form-control" placeholder="Código" value="<?= htmlspecialchars($post_data['codigo_patria'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label for="serial_patria" class="form-label">Serial del Carnet*:</label>
                            <input type="text" name="serial_patria" id="serial_patria" class="form-control" placeholder="Serial" value="<?= htmlspecialchars($post_data['serial_patria'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">¿Posee Carnet PSUV?*</label>
                    <div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="carnet_psuv" id="carnet_psuv_si" value="Sí" <?= (($post_data['carnet_psuv'] ?? 'No') == 'Sí') ? 'checked' : '' ?> required>
                            <label class="form-check-label" for="carnet_psuv_si">Sí</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="carnet_psuv" id="carnet_psuv_no" value="No" <?= (($post_data['carnet_psuv'] ?? 'No') == 'No') ? 'checked' : '' ?>>
                            <label class="form-check-label" for="carnet_psuv_no">No</label>
                        </div>
                    </div>
                    <div id="psuv_fields" class="conditional-field d-none mt-3">
                        <div class="mb-3">
                            <label for="codigo_psuv" class="form-label">Código del Carnet*:</label>
                            <input type="text" name="codigo_psuv" id="codigo_psuv" class="form-control" placeholder="Código" value="<?= htmlspecialchars($post_data['codigo_psuv'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label for="serial_psuv" class="form-label">Serial del Carnet*:</label>
                            <input type="text" name="serial_psuv" id="serial_psuv" class="form-control" placeholder="Serial" value="<?= htmlspecialchars($post_data['serial_psuv'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-2 mt-4">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-save me-2"></i>Guardar y Continuar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // academicFieldIndex se inicializa con el valor PHP para manejar múltiples niveles en caso de un POST con errores
        let academicFieldIndex = <?= count($post_niveles) ?>;
        

        document.addEventListener('DOMContentLoaded', function() {
            // Función para alternar la visibilidad y requisitos de campos condicionales
            // containerId: ID del div contenedor del campo (ej. 'patria_fields')
            // inputIds: Array de IDs de los inputs dentro del contenedor que deben ser afectados (ej. ['codigo_patria', 'serial_patria'])
            // show: booleano, true para mostrar, false para ocultar
            // makeInputsRequired: booleano, true para hacer los inputs listados requeridos cuando se muestran
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
                
                // Asegurar que el ID del input sea único
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
                console.log(`[Add Field] New academic entry added with index: ${currentAcademicIndex}`);
            }

            // Inicializar al cargar la página: Si no hay campos académicos renderizados (e.g., primera carga limpia)
            // se añade un campo para empezar.
            const initialAcademicFields = document.querySelectorAll('.academic-entry');
            if (initialAcademicFields.length === 0) {
                addAcademicField();
                // Ocultar el botón de eliminar para el primer campo si es el único
                const firstRemoveButton = document.querySelector('.academic-entry .remove-academic-field');
                if (firstRemoveButton) {
                    firstRemoveButton.style.display = 'none';
                }
            }


            // Event listener para el botón "Añadir otro Nivel Educativo"
            document.getElementById('addAcademicFieldBtn').addEventListener('click', function() {
                addAcademicField();
                // Asegurarse de que todos los botones de eliminar sean visibles si hay más de un campo
                document.querySelectorAll('.academic-entry .remove-academic-field').forEach(btn => {
                    btn.style.display = 'inline-block';
                });
            });

            // Manejar la eliminación de campos académicos existentes y de los nuevos
            document.getElementById('academic-fields-container').addEventListener('click', function(event) {
                if (event.target.classList.contains('remove-academic-field') || event.target.closest('.remove-academic-field')) {
                    const button = event.target.closest('.remove-academic-field');
                    const entry = button.closest('.academic-entry');
                    
                    const allAcademicEntries = document.querySelectorAll('.academic-entry');
                    if (allAcademicEntries.length > 1) { 
                        entry.remove();
                        // Si después de eliminar solo queda un campo, ocultar su botón de eliminar
                        if (document.querySelectorAll('.academic-entry').length === 1) {
                            document.querySelector('.academic-entry .remove-academic-field').style.display = 'none';
                        }
                    } else {
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

