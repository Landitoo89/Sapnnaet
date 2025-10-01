<?php
session_start();
require_once __DIR__ . '/../conexion/conexion_db.php';

// Verificar sesión de usuario
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../../login.php");
    exit;
}

$current_user_id = $_SESSION['usuario']['id'];
$id_pers = $_GET['id'] ?? null;
$registro = [];
$errores = [];

$conn = new mysqli($servidor, $usuario, $contraseña, $basedatos);
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

// Obtener datos actuales del registro si hay un ID (para precargar el formulario)
if ($id_pers) {
    $stmt = $conn->prepare("SELECT * FROM datos_personales WHERE id_pers = ?");
    $stmt->bind_param("i", $id_pers);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $registro = $resultado->fetch_assoc();
    $stmt->close();

    // Si no se encuentra el registro, redirigir
    if (!$registro) {
        $_SESSION['mensaje'] = [
            'titulo' => 'Error',
            'contenido' => 'Registro no encontrado.',
            'tipo' => 'danger'
        ];
        header('Location: ../gestion_personal.php');
        exit;
    }
} else {
    $_SESSION['mensaje'] = [
        'titulo' => 'Error',
        'contenido' => 'ID de registro no proporcionado.',
        'tipo' => 'danger'
    ];
    header('Location: ../gestion_personal.php');
    exit;
}

// Valores iniciales para los campos del formulario (usados para repoblar en caso de error)
$nombres = $_POST['nombres'] ?? ($registro['nombres'] ?? '');
$apellidos = $_POST['apellidos'] ?? ($registro['apellidos'] ?? '');
$nacionalidad = $_POST['nacionalidad'] ?? ($registro['nacionalidad'] ?? '');
$fecha_nacimiento = $_POST['fecha_nacimiento'] ?? ($registro['fecha_nacimiento'] ?? '');
$genero = $_POST['genero'] ?? ($registro['genero'] ?? '');
$correo_electronico = $_POST['email'] ?? ($registro['correo_electronico'] ?? '');
$telefono_contacto = $_POST['telefono'] ?? ($registro['telefono_contacto'] ?? '');
$posee_telefono_secundario_post = $_POST['posee_telefono_secundario'] ?? ((!empty($registro['telefono_contacto_secundario'])) ? 'Sí' : 'No');
$telefono_contacto_secundario = $_POST['telefono_secundario'] ?? ($registro['telefono_contacto_secundario'] ?? '');
$nombre_emergencia = $_POST['nombre_emergencia'] ?? ($registro['nombre_contacto_emergencia'] ?? '');
$apellido_emergencia = $_POST['apellido_emergencia'] ?? ($registro['apellido_contacto_emergencia'] ?? '');
$telefono_emergencia = $_POST['telefono_emergencia'] ?? ($registro['telefono_contacto_emergencia'] ?? '');
$direccion = $_POST['direccion'] ?? ($registro['direccion'] ?? '');
$id_estado = $_POST['id_estado'] ?? ($registro['id_estado'] ?? '');
$id_municipio = $_POST['id_municipio'] ?? ($registro['id_municipio'] ?? '');
$id_parroquia = $_POST['id_parroquia'] ?? ($registro['id_parroquia'] ?? '');
$numero_seguro_social = $_POST['seguro_social'] ?? ($registro['numero_seguro_social'] ?? '');
$tiene_discapacidad = $_POST['discapacidad'] ?? ($registro['tiene_discapacidad'] ?? 'No');
$detalle_discapacidad = $_POST['detalle_discapacidad'] ?? ($registro['detalle_discapacidad'] ?? '');
$carnet_discapacidad_imagen_path = $registro['carnet_discapacidad_imagen'] ?? '';
$tipo_licencia = $_POST['tipo_licencia'] ?? ($registro['tipo_licencia'] ?? '');
$licencia_vencimiento = $_POST['licencia_vencimiento'] ?? ($registro['licencia_vencimiento'] ?? '');
$tiene_licencia_conducir = $_POST['licencia'] ?? ($registro['tiene_licencia_conducir'] ?? 'No');
$licencia_imagen_path = $registro['licencia_imagen'] ?? '';
$posee_pasaporte = $_POST['posee_pasaporte'] ?? ((!empty($registro['pasaporte']) && $registro['pasaporte'] !== 'NO POSEE') ? 'Sí' : 'No');
$pasaporte_num = $_POST['pasaporte'] ?? (($registro['pasaporte'] ?? 'NO POSEE') === 'NO POSEE' ? '' : $registro['pasaporte']);

// Para repoblar los números de cédula y RIF en el HTML
$cedula_numero_html = $_POST['cedula_numero'] ?? (is_numeric($registro['cedula_identidad'] ?? '') ? $registro['cedula_identidad'] : '');
$rif_prefijo_html = $_POST['rif_prefijo'] ?? ((isset($registro['rif']) && strlen($registro['rif']) > 2) ? substr($registro['rif'], 0, 2) : 'V-');
$rif_numero_html = $_POST['rif_numero'] ?? ((isset($registro['rif']) && strlen($registro['rif']) > 2) ? substr($registro['rif'], 2) : '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_pers = $_POST['id_pers'];
    $nombres = trim($_POST['nombres']);
    $apellidos = trim($_POST['apellidos']);
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
    $direccion = trim($_POST['direccion']);
    $id_estado = $_POST['id_estado'] ?? '';
    $id_municipio = $_POST['id_municipio'] ?? '';
    $id_parroquia = $_POST['id_parroquia'] ?? '';
    $numero_seguro_social = trim($_POST['seguro_social']);
    $tiene_discapacidad = $_POST['discapacidad'] ?? 'No';
    $detalle_discapacidad = ($tiene_discapacidad == 'Sí') ? (trim($_POST['detalle_discapacidad'] ?? '')) : 'No aplica';
    $tipo_licencia = ($tiene_licencia_conducir == 'Sí') ? ($_POST['tipo_licencia'] ?? '') : NULL;
    $licencia_vencimiento = ($tiene_licencia_conducir == 'Sí') ? ($_POST['licencia_vencimiento'] ?? '') : NULL;
    $tiene_licencia_conducir = $_POST['licencia'] ?? 'No';
    $posee_pasaporte = $_POST['posee_pasaporte'] ?? 'No';
    $pasaporte_db = ($posee_pasaporte === 'Sí') ? (strtoupper(trim($_POST['pasaporte'] ?? ''))) : 'NO POSEE';

    $upload_dir_discapacidad = __DIR__ . '/discapacidad/';
    $upload_dir_licencia = __DIR__ . '/licencia/';
    if (!is_dir($upload_dir_discapacidad)) { mkdir($upload_dir_discapacidad, 0755, true); }
    if (!is_dir($upload_dir_licencia)) { mkdir($upload_dir_licencia, 0755, true); }

    $carnet_discapacidad_imagen_path_for_db = $registro['carnet_discapacidad_imagen'];
    if ($tiene_discapacidad == 'Sí' && isset($_FILES['carnet_discapacidad_imagen']) && $_FILES['carnet_discapacidad_imagen']['error'] == UPLOAD_ERR_OK) {
        $file_tmp_name = $_FILES['carnet_discapacidad_imagen']['tmp_name'];
        $file_name = uniqid() . '_' . basename($_FILES['carnet_discapacidad_imagen']['name']);
        $carnet_discapacidad_imagen_full_path = $upload_dir_discapacidad . $file_name;
        if (move_uploaded_file($file_tmp_name, $carnet_discapacidad_imagen_full_path)) {
            $carnet_discapacidad_imagen_path_for_db = 'form/discapacidad/' . $file_name;
        }
    } elseif ($tiene_discapacidad == 'No') {
        $carnet_discapacidad_imagen_path_for_db = NULL;
    }

    $licencia_imagen_path_for_db = $registro['licencia_imagen'];
    if ($tiene_licencia_conducir == 'Sí' && isset($_FILES['licencia_imagen']) && $_FILES['licencia_imagen']['error'] == UPLOAD_ERR_OK) {
        $file_tmp_name = $_FILES['licencia_imagen']['tmp_name'];
        $file_name = uniqid() . '_' . basename($_FILES['licencia_imagen']['name']);
        $licencia_imagen_full_path = $upload_dir_licencia . $file_name;
        if (move_uploaded_file($file_tmp_name, $licencia_imagen_full_path)) {
            $licencia_imagen_path_for_db = 'form/licencia/' . $file_name;
        }
    } elseif ($tiene_licencia_conducir == 'No') {
        $licencia_imagen_path_for_db = NULL;
    }

    // Validaciones
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
    if (!empty($telefono_emergencia) && !preg_match('/^\d{11}$/', $telefono_emergencia)) {
        $errores[] = "El teléfono de contacto de emergencia debe contener 11 dígitos numéricos si se proporciona.";
    }
    if (empty($id_estado)) $errores[] = "Debe seleccionar un estado.";
    if (empty($id_municipio)) $errores[] = "Debe seleccionar un municipio.";
    if (empty($id_parroquia)) $errores[] = "Debe seleccionar una parroquia.";
    if (empty($direccion)) $errores[] = "La dirección exacta es obligatoria.";
    if (empty($numero_seguro_social)) $errores[] = "El número de seguro social es obligatorio.";
    if ($tiene_discapacidad == 'Sí' && empty($detalle_discapacidad)) {
        $errores[] = "Debe especificar el detalle de la discapacidad si seleccionó 'Sí'.";
    }
    if ($tiene_licencia_conducir == 'Sí') {
        if (empty($tipo_licencia)) $errores[] = "Debe seleccionar el tipo de licencia si seleccionó 'Sí'.";
        if (empty($licencia_vencimiento)) $errores[] = "Debe ingresar la fecha de vencimiento de la licencia si seleccionó 'Sí'.";
    }
    $fecha_nacimiento_dt = new DateTime($fecha_nacimiento);
    $hoy = new DateTime();
    $edad = $hoy->diff($fecha_nacimiento_dt)->y;
    if ($edad < 18) $errores[] = "El empleado debe ser mayor de 18 años para el registro.";

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
                carnet_discapacidad_imagen = ?,
                tiene_licencia_conducir = ?,
                tipo_licencia = ?,
                licencia_vencimiento = ?,
                licencia_imagen = ?,
                numero_seguro_social = ?,
                direccion = ?,
                id_estado = ?,
                id_municipio = ?,
                id_parroquia = ?
                WHERE id_pers = ?");
            $stmt->bind_param("ssssssssssssssssssssssssssi",
                $nombres,
                $apellidos,
                $cedula_db,
                $pasaporte_db,
                $rif_db,
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
                $carnet_discapacidad_imagen_path_for_db,
                $tiene_licencia_conducir,
                $tipo_licencia,
                $licencia_vencimiento,
                $licencia_imagen_path_for_db,
                $numero_seguro_social,
                $direccion,
                $id_estado,
                $id_municipio,
                $id_parroquia,
                $id_pers
            );
            if ($stmt->execute()) {
                $_SESSION['mensaje'] = [
                    'titulo' => '¡Actualización Exitosa!',
                    'contenido' => 'Los datos personales han sido actualizados correctamente.',
                    'tipo' => 'success'
                ];
                header("Location: ../gestion_personal.php");
                exit;
            } else {
                $errores[] = "Error al actualizar datos personales: " . $stmt->error;
            }
            $stmt->close();
        } catch (Exception $e) {
            $errores[] = "Error inesperado: " . $e->getMessage();
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Datos Personales</title>
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

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id_pers" value="<?= htmlspecialchars($registro['id_pers']) ?>">

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
                    <input type="text" name="cedula_numero" id="cedula_numero" class="form-control" placeholder="Ej: 12345678" value="<?= htmlspecialchars($cedula_numero_html) ?>" required oninput="this.value = this.value.replace(/[^0-9]/g, '');" title="Ingrese solo los números de la cédula">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="rif_numero" class="form-label">RIF*:</label>
                    <div class="input-group input-group-custom">
                        <select class="form-select" id="rif_prefijo" name="rif_prefijo">
                            <option value="" disabled>Prefijo</option>
                            <option value="V-" <?= ($rif_prefijo_html == 'V-') ? 'selected' : '' ?>>V-</option>
                            <option value="J-" <?= ($rif_prefijo_html == 'J-') ? 'selected' : '' ?>>J-</option>
                            <option value="G-" <?= ($rif_prefijo_html == 'G-') ? 'selected' : '' ?>>G-</option>
                            <option value="E-" <?= ($rif_prefijo_html == 'E-') ? 'selected' : '' ?>>E-</option>
                        </select>
                        <input type="text" name="rif_numero" id="rif_numero" class="form-control" placeholder="Ej: 123456789" value="<?= htmlspecialchars($rif_numero_html) ?>" required oninput="this.value = this.value.replace(/[^0-9]/g, '');" title="Ingrese solo los números del RIF">
                    </div>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-12 mb-3">
                    <label for="posee_pasaporte" class="form-label">¿Posee pasaporte?*</label>
                    <select name="posee_pasaporte" id="posee_pasaporte" class="form-select" required>
                        <option value="No" <?= ($posee_pasaporte == 'No') ? 'selected' : '' ?>>No</option>
                        <option value="Sí" <?= ($posee_pasaporte == 'Sí') ? 'selected' : '' ?>>Sí</option>
                    </select>
                </div>
                <div class="col-md-12 mb-3" id="pasaporte_container" style="<?= ($posee_pasaporte == 'Sí') ? '' : 'display:none;' ?>">
                    <label for="pasaporte_input" class="form-label">Número de Pasaporte:</label>
                    <input type="text" name="pasaporte" id="pasaporte_input" class="form-control" value="<?= htmlspecialchars($pasaporte_num) ?>" oninput="this.value = this.value.toUpperCase()">
                </div>
            </div>

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

            <div class="row mb-3">
                <div class="col-md-6 mb-3">
                    <label for="discapacidad" class="form-label">¿Posee discapacidad?*</label>
                    <select name="discapacidad" id="discapacidad" class="form-select" required>
                        <option value="No" <?= ($tiene_discapacidad == 'No') ? 'selected' : '' ?>>No</option>
                        <option value="Sí" <?= ($tiene_discapacidad == 'Sí') ? 'selected' : '' ?>>Sí</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3" id="detalle_discapacidad_container" style="<?= ($tiene_discapacidad == 'Sí') ? '' : 'display:none;' ?>">
                    <label for="detalle_discapacidad" class="form-label">Detalle de la discapacidad*:</label>
                    <input type="text" name="detalle_discapacidad" id="detalle_discapacidad" class="form-control" value="<?= htmlspecialchars($detalle_discapacidad) ?>">
                </div>
            </div>
            <div class="mb-3" id="carnet_discapacidad_imagen_container" style="<?= ($tiene_discapacidad == 'Sí') ? '' : 'display:none;' ?>">
                <label for="carnet_discapacidad_imagen" class="form-label">Foto del carnet de discapacidad*:</label>
                <?php if (!empty($carnet_discapacidad_imagen_path)): ?>
                    <p class="text-muted">Archivo actual: <a href="<?= htmlspecialchars($carnet_discapacidad_imagen_path) ?>" target="_blank">Ver imagen</a></p>
                <?php endif; ?>
                <input type="file" name="carnet_discapacidad_imagen" id="carnet_discapacidad_imagen" class="form-control" accept="image/*">
            </div>

            <div class="row mb-3">
                <div class="col-md-6 mb-3">
                    <label for="licencia" class="form-label">¿Tiene licencia de conducir?*</label>
                    <select name="licencia" id="licencia" class="form-select" required>
                        <option value="No" <?= ($tiene_licencia_conducir == 'No') ? 'selected' : '' ?>>No</option>
                        <option value="Sí" <?= ($tiene_licencia_conducir == 'Sí') ? 'selected' : '' ?>>Sí</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3" id="licencia_details_container" style="<?= ($tiene_licencia_conducir == 'Sí') ? '' : 'display:none;' ?>">
                    <label for="tipo_licencia" class="form-label">Tipo de Licencia*:</label>
                    <select name="tipo_licencia" id="tipo_licencia" class="form-select">
                        <option value="">Seleccione el tipo...</option>
                        <option value="Primera" <?= ($tipo_licencia == 'Primera') ? 'selected' : '' ?>>Primera</option>
                        <option value="Segunda" <?= ($tipo_licencia == 'Segunda') ? 'selected' : '' ?>>Segunda</option>
                        <option value="Tercera" <?= ($tipo_licencia == 'Tercera') ? 'selected' : '' ?>>Tercera</option>
                        <option value="Cuarta" <?= ($tipo_licencia == 'Cuarta') ? 'selected' : '' ?>>Cuarta</option>
                        <option value="Quinta" <?= ($tipo_licencia == 'Quinta') ? 'selected' : '' ?>>Quinta</option>
                    </select>
                    <label for="licencia_vencimiento" class="form-label mt-2">Fecha de Vencimiento*:</label>
                    <input type="date" name="licencia_vencimiento" id="licencia_vencimiento" class="form-control" value="<?= htmlspecialchars($licencia_vencimiento) ?>">
                    <label for="licencia_imagen" class="form-label mt-2">Foto Licencia:</label>
                    <?php if (!empty($licencia_imagen_path)): ?>
                        <p class="text-muted">Archivo actual: <a href="<?= htmlspecialchars($licencia_imagen_path) ?>" target="_blank">Ver imagen</a></p>
                    <?php endif; ?>
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
                    <select name="posee_telefono_secundario" id="posee_telefono_secundario" class="form-select" required>
                        <option value="No" <?= ($posee_telefono_secundario_post == 'No') ? 'selected' : '' ?>>No</option>
                        <option value="Sí" <?= ($posee_telefono_secundario_post == 'Sí') ? 'selected' : '' ?>>Sí</option>
                    </select>
                </div>
            </div>
            <div class="mb-3" id="telefono_secundario_container" style="<?= ($posee_telefono_secundario_post == 'Sí') ? '' : 'display:none;' ?>">
                <label for="telefono_secundario" class="form-label">Teléfono Secundario:</label>
                <input type="tel" name="telefono_secundario" id="telefono_secundario" class="form-control" placeholder="Ej: 04147654321" pattern="[0-9]{11}" value="<?= htmlspecialchars($telefono_contacto_secundario) ?>">
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
    // Mostrar/Ocultar campos condicionales (pasaporte)
    document.getElementById('posee_pasaporte').addEventListener('change', function() {
        document.getElementById('pasaporte_container').style.display = (this.value === 'Sí') ? '' : 'none';
    });
    document.getElementById('discapacidad').addEventListener('change', function() {
        var show = (this.value === 'Sí');
        document.getElementById('detalle_discapacidad_container').style.display = show ? '' : 'none';
        document.getElementById('carnet_discapacidad_imagen_container').style.display = show ? '' : 'none';
    });
    document.getElementById('licencia').addEventListener('change', function() {
        document.getElementById('licencia_details_container').style.display = (this.value === 'Sí') ? '' : 'none';
    });
    document.getElementById('posee_telefono_secundario').addEventListener('change', function() {
        document.getElementById('telefono_secundario_container').style.display = (this.value === 'Sí') ? '' : 'none';
    });

    // Municipios y parroquias dinámicos
    const estadoSelect = document.getElementById('id_estado');
    const municipioSelect = document.getElementById('id_municipio');
    const parroquiaSelect = document.getElementById('id_parroquia');
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
    // Inicialización
    if (estadoSelect.value) {
        cargarMunicipios(estadoSelect.value, selectedMunicipio);
    }
});
</script>
</body>
</html>