<?php
// Inicia la sesión para manejar mensajes y datos temporales
session_start();
// Conexión a MySQL
require_once __DIR__ . '/../conexion/conexion_db.php'; // Ajusta la ruta a tu archivo de conexión

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario'])) {
    // Registrar intento de acceso no autorizado
    $detalles_log = "Intento de acceso no autorizado al formulario de datos personales";
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

    header("Location: ../../../login.php");
    exit;
}

$current_user_id = $_SESSION['usuario']['id'];
//$current_user_name = $_SESSION['usuario']['nombres'] . ' ' . $_SESSION['usuario']['apellidos'];

// Función para registrar logs
function registrarLog($conn, $user_id, $event_type, $details) {
    // Asegúrate de que $conn es un objeto mysqli válido
    if ($conn instanceof mysqli) {
        $detalles_log = "Usuario: [$user_id]\n";
        $detalles_log .= "Acción: $event_type\n";
        $detalles_log .= "Detalles: $details";

        $ip_address = $_SERVER['REMOTE_ADDR'];
        $user_agent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);

        $stmt_log = $conn->prepare("INSERT INTO action_logs (user_id, event_type, details, ip_address, user_agent)
                                   VALUES (?, ?, ?, ?, ?)");
        // 'issss' -> i para int, s para string
        $stmt_log->bind_param("issss", $user_id, $event_type, $detalles_log, $ip_address, $user_agent);

        if (!$stmt_log->execute()) {
            error_log("Error al registrar log: " . $stmt_log->error);
        }

        $stmt_log->close();
    } else {
        error_log("Error: La conexión a la base de datos no es válida para registrar el log.");
    }
}

$conn = new mysqli($servidor, $usuario, $contraseña, $basedatos);

// Verificar conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Obtener lista de estados para el select
$estados = [];
$sql_estados = "SELECT id_estado, nombre FROM estados ORDER BY nombre ASC";
$result_estados = $conn->query($sql_estados);
if ($result_estados) {
    while ($row = $result_estados->fetch_assoc()) {
        $estados[] = $row;
    }
}

// Registrar visualización del formulario
if ($_SERVER["REQUEST_METHOD"] == "GET") {
    registrarLog($conn, $current_user_id,
                'view_personal_data_form',
                'Visualización del formulario de registro de datos personales');
}

// Inicializa el array de errores
$errores = [];
$mensaje_exito = '';

// Valores por defecto para repoblar el formulario en caso de error
$nombres = $_POST['nombres'] ?? '';
$apellidos = $_POST['apellidos'] ?? '';
$nacionalidad = $_POST['nacionalidad'] ?? '';
$fecha_nacimiento = $_POST['fecha_nacimiento'] ?? '';
$genero = $_POST['genero'] ?? '';
$correo_electronico = $_POST['email'] ?? '';
$telefono_contacto = $_POST['telefono'] ?? '';
$posee_telefono_secundario_post = $_POST['posee_telefono_secundario'] ?? 'No';
$telefono_contacto_secundario = $_POST['telefono_secundario'] ?? '';
$nombre_emergencia = $_POST['nombre_emergencia'] ?? '';
$apellido_emergencia = $_POST['apellido_emergencia'] ?? '';
$telefono_emergencia = $_POST['telefono_emergencia'] ?? '';
$direccion = $_POST['direccion'] ?? '';
$id_estado = $_POST['id_estado'] ?? '';
$id_municipio = $_POST['id_municipio'] ?? '';
$id_parroquia = $_POST['id_parroquia'] ?? '';
$numero_seguro_social = $_POST['seguro_social'] ?? '';
$tiene_discapacidad = $_POST['discapacidad'] ?? 'No';
$detalle_discapacidad = $_POST['detalle_discapacidad'] ?? '';
$carnet_discapacidad_imagen_path = ''; // Inicializar la ruta de la imagen de discapacidad
$tipo_licencia = $_POST['tipo_licencia'] ?? ''; // Nuevo campo
$licencia_vencimiento = $_POST['licencia_vencimiento'] ?? ''; // Nuevo campo
$tiene_licencia_conducir = $_POST['licencia'] ?? 'No';
$licencia_imagen_path = ''; // Inicializar la ruta de la imagen de licencia
$posee_pasaporte = $_POST['posee_pasaporte'] ?? 'No';
$pasaporte_num = $_POST['pasaporte'] ?? '';

// Para repoblar los números de cédula y RIF en el HTML
$cedula_numero_html = htmlspecialchars($_POST['cedula_numero'] ?? '');
// Para RIF, se repuebla el prefijo y el número
$rif_prefijo_html = $_POST['rif_prefijo'] ?? 'V-'; // Default for RIF
$rif_numero_html = htmlspecialchars($_POST['rif_numero'] ?? '');


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Limpiar y obtener datos del formulario
    $nombres = trim($_POST['nombres']);
    $apellidos = trim($_POST['apellidos']);

    // --- CAMBIO CLAVE AQUÍ: Obtener SOLO los números para cédula y RIF para guardar en DB ---
    $cedula_db = filter_input(INPUT_POST, 'cedula_numero', FILTER_SANITIZE_NUMBER_INT);
    $rif_prefijo_post = strtoupper(trim($_POST['rif_prefijo']));
    $rif_db = filter_input(INPUT_POST, 'rif_numero', FILTER_SANITIZE_NUMBER_INT);

    $genero = $_POST['genero'];
    $fecha_nacimiento = $_POST['fecha_nacimiento'];
    $nacionalidad = $_POST['nacionalidad'];
    $correo_electronico = trim($_POST['email']);
    $telefono_contacto = trim($_POST['telefono']);

    $posee_telefono_secundario_post = $_POST['posee_telefono_secundario'] ?? 'No';
    $telefono_contacto_secundario = ($posee_telefono_secundario_post === 'Sí') ? (trim($_POST['telefono_secundario'] ?? '')) : NULL;

    $nombre_emergencia = trim($_POST['nombre_emergencia']);
    $apellido_emergencia = trim($_POST['apellido_emergencia']);
    $telefono_emergencia = trim($_POST['telefono_emergencia']);

    // Nueva dirección exacta y geográfica
    $direccion = trim($_POST['direccion']);
    $id_estado = $_POST['id_estado'] ?? '';
    $id_municipio = $_POST['id_municipio'] ?? '';
    $id_parroquia = $_POST['id_parroquia'] ?? '';

    $numero_seguro_social = trim($_POST['seguro_social']);

    $tiene_discapacidad = $_POST['discapacidad'] ?? 'No';
    $detalle_discapacidad = ($tiene_discapacidad == 'Sí') ? (trim($_POST['detalle_discapacidad'] ?? '')) : 'No aplica';

    $tiene_licencia_conducir = $_POST['licencia'] ?? 'No';
    $tipo_licencia = ($tiene_licencia_conducir == 'Sí') ? ($_POST['tipo_licencia'] ?? '') : NULL;
    $licencia_vencimiento = ($tiene_licencia_conducir == 'Sí') ? ($_POST['licencia_vencimiento'] ?? '') : NULL;

    $posee_pasaporte = $_POST['posee_pasaporte'] ?? 'No';
    $pasaporte_db = ($posee_pasaporte === 'Sí') ? (strtoupper(trim($_POST['pasaporte'] ?? ''))) : 'NO POSEE';

    // Manejo de subida de archivos
    // Rutas de subida de archivos (ajustadas para estar en el mismo directorio que form_register.php)
    $upload_dir_discapacidad = __DIR__ . '/discapacidad/';
    $upload_dir_licencia = __DIR__ . '/licencia/';

    // Asegurarse de que los directorios existan
    if (!is_dir($upload_dir_discapacidad)) {
        mkdir($upload_dir_discapacidad, 0755, true); // Crea recursivamente con permisos 0755
    }
    if (!is_dir($upload_dir_licencia)) {
        mkdir($upload_dir_licencia, 0755, true); // Crea recursivamente con permisos 0755
    }

    // Archivo de carnet de discapacidad
    if ($tiene_discapacidad == 'Sí' && isset($_FILES['carnet_discapacidad_imagen']) && $_FILES['carnet_discapacidad_imagen']['error'] == UPLOAD_ERR_OK) {
        $file_tmp_name = $_FILES['carnet_discapacidad_imagen']['tmp_name'];
        $file_name = uniqid() . '_' . basename($_FILES['carnet_discapacidad_imagen']['name']);
        $carnet_discapacidad_imagen_full_path = $upload_dir_discapacidad . $file_name;
        if (!move_uploaded_file($file_tmp_name, $carnet_discapacidad_imagen_full_path)) {
            $errores[] = "Error al subir la imagen del carnet de discapacidad. Verifique permisos de la carpeta: " . $upload_dir_discapacidad;
            $carnet_discapacidad_imagen_path = NULL; // Reset path on error
        } else {
            // Guarda la ruta relativa al directorio del formulario para la DB
            $carnet_discapacidad_imagen_path = 'form/discapacidad/' . $file_name;
        }
    } elseif ($tiene_discapacidad == 'Sí') {
        $errores[] = "Debe subir la imagen del carnet de discapacidad si seleccionó 'Sí'.";
    } else {
        $carnet_discapacidad_imagen_path = NULL;
    }

    // Archivo de licencia de conducir
    if ($tiene_licencia_conducir == 'Sí' && isset($_FILES['licencia_imagen']) && $_FILES['licencia_imagen']['error'] == UPLOAD_ERR_OK) {
        $file_tmp_name = $_FILES['licencia_imagen']['tmp_name'];
        $file_name = uniqid() . '_' . basename($_FILES['licencia_imagen']['name']);
        $licencia_imagen_full_path = $upload_dir_licencia . $file_name;
        if (!move_uploaded_file($file_tmp_name, $licencia_imagen_full_path)) {
            $errores[] = "Error al subir la imagen de la licencia de conducir. Verifique permisos de la carpeta: " . $upload_dir_licencia;
            $licencia_imagen_path = NULL; // Reset path on error
        } else {
            // Guarda la ruta relativa al directorio del formulario para la DB
            $licencia_imagen_path = 'form/licencia/' . $file_name;
        }
    } elseif ($tiene_licencia_conducir == 'Sí') {
        $errores[] = "Debe subir la imagen de la licencia de conducir si seleccionó 'Sí'.";
    } else {
        $licencia_imagen_path = NULL;
    }


    // Validaciones (Ajustadas para validar solo la parte numérica)
    if (empty($nombres)) $errores[] = "Los nombres son obligatorios.";
    if (empty($apellidos)) $errores[] = "Los apellidos son obligatorios.";

    if (empty($cedula_db) || !ctype_digit($cedula_db) || strlen($cedula_db) < 6 || strlen($cedula_db) > 9) {
        $errores[] = "La cédula de identidad es obligatoria y debe contener entre 6 y 9 dígitos numéricos.";
    }
    if (empty($rif_db) || !ctype_digit($rif_db) || strlen($rif_db) < 6 || strlen($rif_db) > 10) {
        $errores[] = "El RIF es obligatorio y debe contener entre 6 y 10 dígitos numéricos.";
    }
    if (empty($rif_prefijo_post)) {
        $errores[] = "El prefijo del RIF es obligatorio.";
    }

    if (empty($genero)) $errores[] = "El género es obligatorio.";
    if (empty($fecha_nacimiento)) $errores[] = "La fecha de nacimiento es obligatoria.";
    if (empty($nacionalidad)) $errores[] = "La nacionalidad es obligatoria.";
    if (empty($correo_electronico) || !filter_var($correo_electronico, FILTER_VALIDATE_EMAIL)) $errores[] = "El correo electrónico es obligatorio y debe ser válido.";
    if (empty($telefono_contacto) || !preg_match('/^\d{11}$/', $telefono_contacto)) $errores[] = "El teléfono principal es obligatorio y debe contener 11 dígitos numéricos.";
    if ($posee_telefono_secundario_post === 'Sí' && !empty($telefono_contacto_secundario) && !preg_match('/^\d{11}$/', $telefono_contacto_secundario)) {
         $errores[] = "El teléfono secundario debe contener 11 dígitos numéricos.";
    } else if ($posee_telefono_secundario_post === 'Sí' && empty($telefono_contacto_secundario)) {
        $errores[] = "Debe ingresar el número de teléfono secundario si seleccionó 'Sí'.";
    }

    // Contacto de emergencia: Ya no son obligatorios, solo se valida el formato del teléfono si se proporciona.
    if (!empty($telefono_emergencia) && !preg_match('/^\d{11}$/', $telefono_emergencia)) {
        $errores[] = "El teléfono de contacto de emergencia debe contener 11 dígitos numéricos si se proporciona.";
    }


    // Nueva validación de geografía/dirección
    if (empty($id_estado)) $errores[] = "Debe seleccionar un estado.";
    if (empty($id_municipio)) $errores[] = "Debe seleccionar un municipio.";
    if (empty($id_parroquia)) $errores[] = "Debe seleccionar una parroquia.";
    if (empty($direccion)) $errores[] = "La dirección exacta es obligatoria.";

    if (empty($numero_seguro_social)) $errores[] = "El número de seguro social es obligatorio.";

    // Validación de discapacidad
    if ($tiene_discapacidad == 'Sí' && empty($detalle_discapacidad)) {
        $errores[] = "Debe especificar el detalle de la discapacidad si seleccionó 'Sí'.";
    }

    // Validación de licencia de conducir
    if ($tiene_licencia_conducir == 'Sí') {
        if (empty($tipo_licencia)) {
            $errores[] = "Debe seleccionar el tipo de licencia si seleccionó 'Sí'.";
        }
        if (empty($licencia_vencimiento)) {
            $errores[] = "Debe ingresar la fecha de vencimiento de la licencia si seleccionó 'Sí'.";
        }
    }


    // Validación de fecha de nacimiento (mayor a 18 años)
    $fecha_nacimiento_dt = new DateTime($fecha_nacimiento);
    $hoy = new DateTime();
    $edad = $hoy->diff($fecha_nacimiento_dt)->y;
    if ($edad < 18) {
        $errores[] = "El empleado debe ser mayor de 18 años para el registro.";
    }

    // Si no hay errores, procede a insertar
    if (empty($errores)) {
        try {
            // Verificar si la cédula (solo el número) ya existe
            $stmt_check_cedula = $conn->prepare("SELECT id_pers FROM datos_personales WHERE cedula_identidad = ?");
            $stmt_check_cedula->bind_param("s", $cedula_db);
            $stmt_check_cedula->execute();
            $stmt_check_cedula->store_result();
            if ($stmt_check_cedula->num_rows > 0) {
                $errores[] = "La cédula de identidad ya se encuentra registrada.";
            }
            $stmt_check_cedula->close();

            // Verificar si el RIF (solo el número) ya existe
            $stmt_check_rif = $conn->prepare("SELECT id_pers FROM datos_personales WHERE rif = ?");
            $stmt_check_rif->bind_param("s", $rif_db);
            $stmt_check_rif->execute();
            $stmt_check_rif->store_result();
            if ($stmt_check_rif->num_rows > 0) {
                $errores[] = "El RIF ya se encuentra registrado.";
            }
            $stmt_check_rif->close();

            // Solo si no hay nuevos errores después de las verificaciones de existencia
            if (empty($errores)) {
                $stmt = $conn->prepare("INSERT INTO datos_personales (
                    nombres, apellidos, cedula_identidad, pasaporte, rif, genero,
                    fecha_nacimiento, nacionalidad, correo_electronico, telefono_contacto,
                    telefono_contacto_secundario,
                    nombre_contacto_emergencia, apellido_contacto_emergencia,
                    telefono_contacto_emergencia, tiene_discapacidad, detalle_discapacidad, carnet_discapacidad_imagen,
                    tiene_licencia_conducir, tipo_licencia, licencia_vencimiento, licencia_imagen, numero_seguro_social,
                    direccion, id_estado, id_municipio, id_parroquia
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                $stmt->bind_param("ssssssssssssssssssssssssss",
                    $nombres,
                    $apellidos,
                    $cedula_db, // Se guarda solo el número
                    $pasaporte_db,
                    $rif_db,      // Se guarda solo el número
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
                    $carnet_discapacidad_imagen_path,
                    $tiene_licencia_conducir,
                    $tipo_licencia,
                    $licencia_vencimiento,
                    $licencia_imagen_path,
                    $numero_seguro_social,
                    $direccion,
                    $id_estado,
                    $id_municipio,
                    $id_parroquia
                );

                if ($stmt->execute()) {
                    $id_pers = $conn->insert_id;

                    registrarLog($conn, $current_user_id,
                                'personal_data_created',
                                "Nuevo registro creado con ID: $id_pers\n" .
                                "Nombre: $nombres $apellidos\n" .
                                "Cédula: $cedula_db\n" .
                                "RIF: $rif_db");

                    $_SESSION['mensaje'] = [
                        'titulo' => '¡Registro Exitoso!',
                        'contenido' => 'Datos personales guardados. Continuar con los datos socioeconómicos.',
                        'tipo' => 'success'
                    ];
                    header("Location: form_datossocioeco.php?id_pers=" . $id_pers . "&nombres=" . urlencode($nombres) . "&apellidos=" . urlencode($apellidos));
                    exit();
                } else {
                    $errores[] = "Error al registrar datos personales: " . $stmt->error;
                    registrarLog($conn, $current_user_id,
                                'personal_data_insert_error',
                                "Error al insertar datos: " . $stmt->error);
                }
                $stmt->close();
            }

        } catch (Exception $e) {
            $errores[] = "Error inesperado: " . $e->getMessage();
            registrarLog($conn, $current_user_id,
                        'personal_data_exception',
                        "Excepción: " . $e->getMessage());
        }
    } else {
        $detalles_errores = implode('; ', $errores);
        registrarLog($conn, $current_user_id,
                    'personal_data_validation_failed',
                    "Errores de validación: $detalles_errores");
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de Personal</title>
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
    </style>
</head>
<body>
    <div class="container">
        <div class="form-container-custom">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="text-primary mb-0">
                    <i class="bi bi-person-plus me-2"></i>Registro de Datos Personales
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

            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" enctype="multipart/form-data">
                <div class="form-section-header">
                    <h2><i class="bi bi-info-circle-fill me-2"></i>Información Básica</h2>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6 mb-3">
                        <label for="nombres" class="form-label">Nombres*:</label>
                        <input type="text" name="nombres" id="nombres" class="form-control" value="<?= htmlspecialchars($nombres) ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="apellidos" class="form-label">Apellidos*:</label>
                        <input type="text" name="apellidos" id="apellidos" class="form-control" value="<?= htmlspecialchars($apellidos) ?>" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6 mb-3">
                        <label for="nacionalidad" class="form-label">Nacionalidad*:</label>
                        <select name="nacionalidad" id="nacionalidad" class="form-select" required>
                            <option value="">Seleccione...</option>
                            <option value="Venezolano" <?= ($nacionalidad == 'Venezolano') ? 'selected' : '' ?>>Venezolano</option>
                            <option value="Extranjero" <?= ($nacionalidad == 'Extranjero') ? 'selected' : '' ?>>Extranjero</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento*:</label>
                        <input type="date" name="fecha_nacimiento" id="fecha_nacimiento" class="form-control" value="<?= htmlspecialchars($fecha_nacimiento) ?>" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6 mb-3">
                        <label for="cedula_numero" class="form-label">Cédula de Identidad*:</label>
                        <input type="text" name="cedula_numero" id="cedula_numero" class="form-control" placeholder="Ej: 12345678" value="<?= $cedula_numero_html ?>" required oninput="this.value = this.value.replace(/[^0-9]/g, '');" title="Ingrese solo los números de la cédula">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="rif_numero" class="form-label">RIF*:</label>
                        <div class="input-group input-group-custom">
                            <select class="form-select" id="rif_prefijo" name="rif_prefijo">
                                <option value="" disabled selected>Prefijo</option> <option value="V-" <?= ($rif_prefijo_html == 'V-') ? 'selected' : '' ?>>V-</option>
                                <option value="J-" <?= ($rif_prefijo_html == 'J-') ? 'selected' : '' ?>>J-</option>
                                <option value="G-" <?= ($rif_prefijo_html == 'G-') ? 'selected' : '' ?>>G-</option>
                                <option value="E-" <?= ($rif_prefijo_html == 'E-') ? 'selected' : '' ?>>E-</option>
                            </select>
                            <input type="text" name="rif_numero" id="rif_numero" class="form-control" placeholder="Ej: 123456789" value="<?= $rif_numero_html ?>" required oninput="this.value = this.value.replace(/[^0-9]/g, '');" title="Ingrese solo los números del RIF">
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12 mb-3">
                        <label class="form-label">¿Posee pasaporte?</label>
                        <div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="posee_pasaporte" id="passport_si" value="Sí" <?= ($posee_pasaporte == 'Sí') ? 'checked' : '' ?>>
                                <label class="form-check-label" for="passport_si">Sí</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="posee_pasaporte" id="passport_no" value="No" <?= ($posee_pasaporte == 'No') ? 'checked' : '' ?>>
                                <label class="form-check-label" for="passport_no">No</label>
                            </div>
                        </div>
                    </div>
                    <div id="pasaporte_container" class="col-md-12 mb-3 conditional-field d-none">
                        <label for="pasaporte_input" class="form-label">Número de Pasaporte:</label>
                        <input type="text" name="pasaporte" id="pasaporte_input" class="form-control" value="<?= htmlspecialchars($pasaporte_num) ?>" oninput="this.value = this.value.toUpperCase()">
                    </div>
                </div>

                <!-- NUEVA SECCIÓN DE DIRECCIÓN -->
                <div class="mb-3">
                    <label for="id_estado" class="form-label">Estado*:</label>
                    <select name="id_estado" id="id_estado" class="form-select" required>
                        <option value="">Seleccione el estado...</option>
                        <?php foreach ($estados as $estado): ?>
                            <option value="<?= $estado['id_estado'] ?>" <?= ($id_estado == $estado['id_estado']) ? 'selected' : '' ?>><?= htmlspecialchars($estado['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="id_municipio" class="form-label">Municipio*:</label>
                    <select name="id_municipio" id="id_municipio" class="form-select" required>
                        <option value="">Seleccione el municipio...</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="id_parroquia" class="form-label">Parroquia*:</label>
                    <select name="id_parroquia" id="id_parroquia" class="form-select" required>
                        <option value="">Seleccione la parroquia...</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="direccion" class="form-label">Dirección exacta*:</label>
                    <input type="text" name="direccion" id="direccion" class="form-control" value="<?= htmlspecialchars($direccion) ?>" required>
                </div>
                <!-- FIN DE NUEVA SECCIÓN DE DIRECCIÓN -->

                <div class="form-section-header mt-5">
                    <h2><i class="bi bi-file-person-fill me-2"></i>Datos Personales Adicionales</h2>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6 mb-3">
                        <label for="genero" class="form-label">Género*:</label>
                        <select name="genero" id="genero" class="form-select" required>
                            <option value="">Seleccione...</option>
                            <option value="Masculino" <?= ($genero == 'Masculino') ? 'selected' : '' ?>>Masculino</option>
                            <option value="Femenino" <?= ($genero == 'Femenino') ? 'selected' : '' ?>>Femenino</option>
                            <option value="No binario" <?= ($genero == 'No binario') ? 'selected' : '' ?>>No binario</option>
                            <option value="Prefiero no decir" <?= ($genero == 'Prefiero no decir') ? 'selected' : '' ?>>Prefiero no decir</option>
                            <option value="Otro" <?= ($genero == 'Otro') ? 'selected' : '' ?>>Otro</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="seguro_social" class="form-label">Número de Seguro Social*:</label>
                        <input type="text" name="seguro_social" id="seguro_social" class="form-control" value="<?= htmlspecialchars($numero_seguro_social) ?>" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">¿Posee discapacidad?*</label>
                    <div class="radio-group">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="discapacidad" id="discapacidad_si" value="Sí"
                                   onchange="toggleCampo('detalle-discapacidad', 'detalle_discapacidad', this.value === 'Sí', true); toggleCampo('carnet-discapacidad-imagen-container', 'carnet_discapacidad_imagen', this.value === 'Sí', true)" <?= ($tiene_discapacidad == 'Sí') ? 'checked' : '' ?> required>
                            <label class="form-check-label" for="discapacidad_si">Sí</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="discapacidad" id="discapacidad_no" value="No"
                                   onchange="toggleCampo('detalle-discapacidad', 'detalle_discapacidad', this.value === 'Sí', true); toggleCampo('carnet-discapacidad-imagen-container', 'carnet_discapacidad_imagen', this.value === 'Sí', true)" <?= ($tiene_discapacidad == 'No') ? 'checked' : '' ?>>
                            <label class="form-check-label" for="discapacidad_no">No</label>
                        </div>
                    </div>
                </div>

                <div id="detalle-discapacidad" class="conditional-field d-none">
                    <div class="mb-3">
                        <label for="detalle_discapacidad" class="form-label">Detalle de la discapacidad*:</label>
                        <input type="text" name="detalle_discapacidad" id="detalle_discapacidad" class="form-control" value="<?= htmlspecialchars($detalle_discapacidad) ?>">
                    </div>
                </div>

                <div id="carnet-discapacidad-imagen-container" class="conditional-field d-none">
                    <div class="mb-3">
                        <label for="carnet_discapacidad_imagen" class="form-label">Foto del carnet de discapacidad*:</label>
                        <input type="file" name="carnet_discapacidad_imagen" id="carnet_discapacidad_imagen" class="form-control" accept="image/*">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">¿Tiene licencia de conducir?*</label>
                    <div class="radio-group">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="licencia" id="licencia_si" value="Sí"
                                   onchange="toggleCampo('licencia-details-container', ['tipo_licencia', 'licencia_vencimiento', 'licencia_imagen'], this.value === 'Sí', true)" <?= ($tiene_licencia_conducir == 'Sí') ? 'checked' : '' ?> required>
                            <label class="form-check-label" for="licencia_si">Sí</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="licencia" id="licencia_no" value="No"
                                   onchange="toggleCampo('licencia-details-container', ['tipo_licencia', 'licencia_vencimiento', 'licencia_imagen'], this.value === 'Sí', true)" <?= ($tiene_licencia_conducir == 'No') ? 'checked' : '' ?>>
                            <label class="form-check-label" for="licencia_no">No</label>
                        </div>
                    </div>
                </div>

                <div id="licencia-details-container" class="conditional-field d-none">
                    <div class="mb-3">
                        <label for="tipo_licencia" class="form-label">Tipo de Licencia*:</label>
                        <select name="tipo_licencia" id="tipo_licencia" class="form-select">
                            <option value="">Seleccione el tipo...</option>
                            <option value="Primera" <?= ($tipo_licencia == 'Primera') ? 'selected' : '' ?>>Primera</option>
                            <option value="Segunda" <?= ($tipo_licencia == 'Segunda') ? 'selected' : '' ?>>Segunda</option>
                            <option value="Tercera" <?= ($tipo_licencia == 'Tercera') ? 'selected' : '' ?>>Tercera</option>
                            <option value="Cuarta" <?= ($tipo_licencia == 'Cuarta') ? 'selected' : '' ?>>Cuarta</option>
                            <option value="Quinta" <?= ($tipo_licencia == 'Quinta') ? 'selected' : '' ?>>Quinta</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="licencia_vencimiento" class="form-label">Fecha de Vencimiento de Licencia*:</label>
                        <input type="date" name="licencia_vencimiento" id="licencia_vencimiento" class="form-control" value="<?= htmlspecialchars($licencia_vencimiento) ?>">
                    </div>
                    <div class="mb-3">
                        <label for="licencia_imagen" class="form-label">Foto de la Licencia de Conducir*:</label>
                        <input type="file" name="licencia_imagen" id="licencia_imagen" class="form-control" accept="image/*">
                    </div>
                </div>

                <div class="form-section-header mt-5">
                    <h2><i class="bi bi-phone-fill me-2"></i>Información de Contacto</h2>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Correo Electrónico*:</label>
                    <input type="email" name="email" id="email" class="form-control" value="<?= htmlspecialchars($correo_electronico) ?>" required>
                </div>

                <div class="mb-3">
                    <label for="telefono_principal" class="form-label">Teléfono Principal*:</label>
                    <input type="tel" name="telefono" id="telefono_principal" class="form-control" placeholder="Ej: 04121234567" pattern="[0-9]{11}" value="<?= htmlspecialchars($telefono_contacto) ?>" required>

                    <div class="mt-3">
                        <label class="form-label">¿Desea agregar un teléfono secundario?</label>
                        <div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="posee_telefono_secundario" id="telefono_secundario_si" value="Sí" <?= ($posee_telefono_secundario_post == 'Sí') ? 'checked' : '' ?>>
                                <label class="form-check-label" for="telefono_secundario_si">Sí</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="posee_telefono_secundario" id="telefono_secundario_no" value="No" <?= ($posee_telefono_secundario_post == 'No') ? 'checked' : '' ?>>
                                <label class="form-check-label" for="telefono_secundario_no">No</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="telefono_secundario_container" class="conditional-field d-none">
                    <div class="mb-3">
                        <label for="telefono_secundario" class="form-label">Teléfono Secundario:</label>
                        <input type="tel" name="telefono_secundario" id="telefono_secundario" class="form-control" placeholder="Ej: 04147654321" pattern="[0-9]{11}" value="<?= htmlspecialchars($telefono_contacto_secundario) ?>">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6 mb-3">
                        <label for="nombre_emergencia" class="form-label">Nombre de Contacto de Emergencia:</label>
                        <input type="text" name="nombre_emergencia" id="nombre_emergencia" class="form-control" value="<?= htmlspecialchars($nombre_emergencia) ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="apellido_emergencia" class="form-label">Apellido de Contacto de Emergencia:</label>
                        <input type="text" name="apellido_emergencia" id="apellido_emergencia" class="form-control" value="<?= htmlspecialchars($apellido_emergencia) ?>">
                    </div>
                </div>
                <div class="mb-3">
                    <label for="telefono_emergencia" class="form-label">Teléfono de Contacto de Emergencia:</label>
                    <input type="tel" name="telefono_emergencia" id="telefono_emergencia" class="form-control" placeholder="Ej: 04169876543" pattern="[0-9]{11}" value="<?= htmlspecialchars($telefono_emergencia) ?>">
                </div>

                <div class="d-grid gap-2 mt-4">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-save me-2"></i>Registrar Datos
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Función para alternar la visibilidad y requisitos de campos condicionales
            function toggleCampo(containerId, inputIds, show, makeInputRequired = false) {
                const container = document.getElementById(containerId);
                if (!container) return;

                const idsArray = Array.isArray(inputIds) ? inputIds : [inputIds];
                const inputs = idsArray.map(id => document.getElementById(id)).filter(Boolean);

                if (show) {
                    container.classList.remove('d-none');
                    inputs.forEach(input => {
                        input.removeAttribute('disabled');
                        if (makeInputRequired) {
                            input.setAttribute('required', 'required');
                        }
                    });
                } else {
                    container.classList.add('d-none');
                    inputs.forEach(input => {
                        input.setAttribute('disabled', 'disabled');
                        input.removeAttribute('required');
                        if (input.type !== 'file') { // No borrar el valor de los inputs de tipo file
                            input.value = '';
                        }
                    });
                }
            }

            // --- INICIALIZACIÓN DE CAMPOS CONDICIONALES AL CARGAR LA PÁGINA ---
            const discapacidadSiRadio = document.getElementById('discapacidad_si');
            if (discapacidadSiRadio) {
                const initialDiscapacidad = document.querySelector('input[name="discapacidad"]:checked')?.value === 'Sí';
                toggleCampo('detalle-discapacidad', 'detalle_discapacidad', initialDiscapacidad, true);
                toggleCampo('carnet-discapacidad-imagen-container', 'carnet_discapacidad_imagen', initialDiscapacidad, true);

                document.querySelectorAll('input[name="discapacidad"]').forEach(radio => {
                    radio.addEventListener('change', function() {
                        const isDiscapacidad = this.value === 'Sí';
                        toggleCampo('detalle-discapacidad', 'detalle_discapacidad', isDiscapacidad, true);
                        toggleCampo('carnet-discapacidad-imagen-container', 'carnet_discapacidad_imagen', isDiscapacidad, true);
                    });
                });
            }

            const licenciaSiRadio = document.getElementById('licencia_si');
            if (licenciaSiRadio) {
                const initialLicencia = document.querySelector('input[name="licencia"]:checked')?.value === 'Sí';
                toggleCampo('licencia-details-container', ['tipo_licencia', 'licencia_vencimiento', 'licencia_imagen'], initialLicencia, true);

                document.querySelectorAll('input[name="licencia"]').forEach(radio => {
                    radio.addEventListener('change', function() {
                        const isLicencia = this.value === 'Sí';
                        toggleCampo('licencia-details-container', ['tipo_licencia', 'licencia_vencimiento', 'licencia_imagen'], isLicencia, true);
                    });
                });
            }

            const passportSiRadio = document.getElementById('passport_si');
            if (passportSiRadio) {
                const initialPasaporte = document.querySelector('input[name="posee_pasaporte"]:checked')?.value === 'Sí';
                toggleCampo('pasaporte_container', 'pasaporte_input', initialPasaporte, true);
                document.querySelectorAll('input[name="posee_pasaporte"]').forEach(radio => {
                    radio.addEventListener('change', function() {
                        toggleCampo('pasaporte_container', 'pasaporte_input', this.value === 'Sí', true);
                    });
                });
            }
            const telefonoSecundarioSiRadio = document.getElementById('telefono_secundario_si');
            if (telefonoSecundarioSiRadio) {
                const initialTelSecundario = document.querySelector('input[name="posee_telefono_secundario"]:checked')?.value === 'Sí';
                toggleCampo('telefono_secundario_container', 'telefono_secundario', initialTelSecundario, false);
                document.querySelectorAll('input[name="posee_telefono_secundario"]').forEach(radio => {
                    radio.addEventListener('change', function() {
                        toggleCampo('telefono_secundario_container', 'telefono_secundario', this.value === 'Sí', false);
                    });
                });
            }
            const fechaNacimientoInput = document.getElementById('fecha_nacimiento');
            if (fechaNacimientoInput) {
                fechaNacimientoInput.addEventListener('change', function() {
                    const dob = new Date(this.value);
                    const today = new Date();
                    let age = today.getFullYear() - dob.getFullYear();
                    const m = today.getMonth() - dob.getMonth();
                    if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) {
                        age--;
                    }
                    const existingAlert = this.parentNode.querySelector('.alert-warning');
                    if (existingAlert) {
                        existingAlert.remove();
                    }
                    if (age < 18) {
                        const alertDiv = document.createElement('div');
                        alertDiv.classList.add('alert', 'alert-warning', 'mt-3');
                        alertDiv.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-2"></i>La fecha de nacimiento debe ser mayor a 18 años a partir del día de registro.';
                        this.parentNode.insertBefore(alertDiv, this.nextSibling);
                        this.value = '';
                        setTimeout(() => alertDiv.remove(), 5000);
                    }
                });
            }

            // --- DIRECCIÓN: Selects dependientes ---
            const estadoSelect = document.getElementById('id_estado');
            const municipioSelect = document.getElementById('id_municipio');
            const parroquiaSelect = document.getElementById('id_parroquia');

            // Variables para mantener selección anterior (en caso de error y recarga)
            let selectedMunicipio = "<?= htmlspecialchars($id_municipio) ?>";
            let selectedParroquia = "<?= htmlspecialchars($id_parroquia) ?>";

            function cargarMunicipios(id_estado, selected = "") {
                municipioSelect.innerHTML = '<option value="">Cargando...</option>';
                parroquiaSelect.innerHTML = '<option value="">Seleccione la parroquia...</option>';
                if (id_estado) {
                    fetch('ajax_municipios.php?id_estado=' + id_estado)
                        .then(response => response.json())
                        .then(data => {
                            municipioSelect.innerHTML = '<option value="">Seleccione el municipio...</option>';
                            data.forEach(function(municipio) {
                                let sel = (municipio.id_municipio == selected) ? "selected" : "";
                                municipioSelect.innerHTML += `<option value="${municipio.id_municipio}" ${sel}>${municipio.nombre}</option>`;
                            });
                            if (selected) {
                                cargarParroquias(selected, selectedParroquia);
                            }
                        });
                } else {
                    municipioSelect.innerHTML = '<option value="">Seleccione el municipio...</option>';
                }
            }

            function cargarParroquias(id_municipio, selected = "") {
                parroquiaSelect.innerHTML = '<option value="">Cargando...</option>';
                if (id_municipio) {
                    fetch('ajax_parroquias.php?id_municipio=' + id_municipio)
                        .then(response => response.json())
                        .then(data => {
                            parroquiaSelect.innerHTML = '<option value="">Seleccione la parroquia...</option>';
                            data.forEach(function(parroquia) {
                                let sel = (parroquia.id_parroquia == selected) ? "selected" : "";
                                parroquiaSelect.innerHTML += `<option value="${parroquia.id_parroquia}" ${sel}>${parroquia.nombre}</option>`;
                            });
                        });
                } else {
                    parroquiaSelect.innerHTML = '<option value="">Seleccione la parroquia...</option>';
                }
            }

            estadoSelect.addEventListener('change', function() {
                cargarMunicipios(this.value);
            });

            municipioSelect.addEventListener('change', function() {
                cargarParroquias(this.value);
            });

            // Si hay selección previa (POST con error), recarga municipios y parroquias
            if (estadoSelect.value) {
                cargarMunicipios(estadoSelect.value, selectedMunicipio);
            }
            if (municipioSelect.value) {
                cargarParroquias(municipioSelect.value, selectedParroquia);
            }
        });
    </script>
</body>
</html>
