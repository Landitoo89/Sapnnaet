<?php
session_start();
require_once __DIR__ . '/../conexion/conexion_db.php';

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

// Verificar sesión de usuario
if (!isset($_SESSION['usuario'])) {
    $detalles_log = "Intento de acceso no autorizado a formulario de datos laborales";
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
$conn = new mysqli($servidor, $usuario, $contraseña, $basedatos);
if ($conn->connect_error) die("Error de conexión: " . $conn->connect_error);

$id_pers = isset($_GET['id_pers']) ? intval($_GET['id_pers']) : 0;
$nombres = isset($_GET['nombres']) ? urldecode($_GET['nombres']) : '';
$apellidos = isset($_GET['apellidos']) ? urldecode($_GET['apellidos']) : '';

// Registrar visualización del formulario
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $id_pers) {
    registrarLog($conn, $current_user_id, 'view_laboral_form',
                "Visualización de formulario para ID Persona: $id_pers");
}

if ($id_pers <= 0) {
    $_SESSION['mensaje'] = [
        'titulo' => 'Error',
        'contenido' => 'ID de trabajador no válido. Vuelva a la página anterior.',
        'tipo' => 'danger'
    ];
    header("Location: gestion_personal.php");
    exit();
}

// Obtener tipos de personal, departamentos, cargos, tipos de contrato, coordinaciones y primas
$tipos_personal = $conn->query("SELECT id_tipo_personal, nombre FROM tipos_personal ORDER BY nombre");
$departamentos = $conn->query("SELECT id_departamento, nombre FROM departamentos ORDER BY nombre");
$cargos = $conn->query("SELECT id_cargo, nombre FROM cargos ORDER BY nombre");
$tipos_contrato = $conn->query("SELECT id_contrato, nombre FROM tipos_contrato ORDER BY nombre");
$coordinaciones = $conn->query("SELECT id_coordinacion, nombre FROM coordinaciones ORDER BY nombre");
$primas = $conn->query("SELECT id_prima, nombre, monto FROM primas ORDER BY nombre");

// Función de validación
function existeRegistro($conn, $tabla, $campo, $valor) {
    $stmt = $conn->prepare("SELECT $campo FROM $tabla WHERE $campo = ?");
    $stmt->bind_param("s", $valor);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fecha_ingreso = $_POST['fecha_ingreso'];
    $id_tipo_personal = $_POST['id_tipo_personal'];
    $estado = $_POST['estado'];
    $id_departamento = $_POST['id_departamento'];
    $id_cargo = $_POST['id_cargo'];
    $id_contrato = $_POST['id_contrato'];
    $ficha = trim($_POST['ficha']);
    $id_coordinacion = $_POST['id_coordinacion'];

    $ha_trabajado_anteriormente = $_POST['ha_trabajado_anteriormente'] ?? 'No';
    $nombre_empresa_anterior = ($ha_trabajado_anteriormente === 'Sí') ? trim($_POST['nombre_empresa_anterior'] ?? '') : NULL;
    $ano_ingreso_anterior = ($ha_trabajado_anteriormente === 'Sí') ? ($_POST['ano_ingreso_anterior'] ?? NULL) : NULL;
    $ano_culminacion_anterior = ($ha_trabajado_anteriormente === 'Sí') ? ($_POST['ano_culminacion_anterior'] ?? NULL) : NULL;

    $correo_institucional = filter_var($_POST['correo_institucional'] ?? '', FILTER_SANITIZE_EMAIL);
    $descripcion_funciones = trim($_POST['descripcion_funciones'] ?? '');

    // Validaciones
    $errores = [];
    if (empty($fecha_ingreso) || empty($id_tipo_personal) || empty($estado) || empty($id_departamento) ||
        empty($id_cargo) || empty($id_contrato) || empty($ficha) || empty($id_coordinacion) ||
        empty($correo_institucional) || empty($descripcion_funciones)) {
        $errores[] = 'Todos los campos marcados son obligatorios.';
    } elseif (!filter_var($correo_institucional, FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'El correo institucional no es válido.';
    } elseif ($ha_trabajado_anteriormente === 'Sí' && (empty($nombre_empresa_anterior) || empty($ano_ingreso_anterior) || empty($ano_culminacion_anterior))) {
        $errores[] = 'Si ha trabajado anteriormente, debe completar todos los campos de experiencia previa.';
    } elseif ($ha_trabajado_anteriormente === 'Sí' && ($ano_ingreso_anterior > $ano_culminacion_anterior)) {
        $errores[] = 'La fecha de ingreso anterior no puede ser posterior a la fecha de culminación anterior.';
    } elseif (existeRegistro($conn, 'datos_laborales', 'ficha', $ficha)) {
        $errores[] = 'La ficha laboral ya existe. Por favor, introduzca una ficha única.';
    }

    if (!empty($errores)) {
        $_SESSION['mensaje'] = [
            'titulo' => 'Error',
            'contenido' => implode('<br>', $errores),
            'tipo' => 'danger'
        ];

        // Registrar errores de validación
        registrarLog($conn, $current_user_id, 'laboral_validation_failed',
                    "Errores de validación para ID Persona: $id_pers - " . implode('; ', $errores));
    } else {
        try {
            $pdo = new PDO("mysql:host=$servidor;dbname=$basedatos", $usuario, $contraseña);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt_insert = $pdo->prepare("INSERT INTO datos_laborales (
                id_pers, fecha_ingreso, id_tipo_personal, estado,
                id_departamento, id_cargo, id_contrato,
                ficha, id_coordinacion,
                ha_trabajado_anteriormente, nombre_empresa_anterior,
                ano_ingreso_anterior, ano_culminacion_anterior, -- Nombres de columnas actualizados
                correo_institucional, descripcion_funciones
            ) VALUES (
                :id_pers, :fecha_ingreso, :id_tipo_personal, :estado,
                :id_departamento, :id_cargo, :id_contrato,
                :ficha, :id_coordinacion,
                :ha_trabajado_anteriormente, :nombre_empresa_anterior,
                :ano_ingreso_anterior, :ano_culminacion_anterior, -- Nombres de parámetros actualizados
                :correo_institucional, :descripcion_funciones
            )");

            $stmt_insert->execute([
                ':id_pers' => $id_pers,
                ':fecha_ingreso' => $fecha_ingreso,
                ':id_tipo_personal' => $id_tipo_personal,
                ':estado' => $estado,
                ':id_departamento' => $id_departamento,
                ':id_cargo' => $id_cargo,
                ':id_contrato' => $id_contrato,
                ':ficha' => $ficha,
                ':id_coordinacion' => $id_coordinacion,
                ':ha_trabajado_anteriormente' => $ha_trabajado_anteriormente,
                ':nombre_empresa_anterior' => $nombre_empresa_anterior,
                ':ano_ingreso_anterior' => $ano_ingreso_anterior, // Variable PHP correcta
                ':ano_culminacion_anterior' => $ano_culminacion_anterior, // Variable PHP correcta
                ':correo_institucional' => $correo_institucional,
                ':descripcion_funciones' => $descripcion_funciones
            ]);

            // Obtener ID del nuevo registro
            $id_laboral = $pdo->lastInsertId();

            // Registrar creación exitosa
            registrarLog($conn, $current_user_id, 'laboral_created',
                        "Datos laborales creados para ID Persona: $id_pers - ID Laboral: $id_laboral");

            $_SESSION['mensaje'] = [
                'titulo' => '¡Éxito!',
                'contenido' => 'Datos laborales registrados correctamente. Registro completado.',
                'tipo' => 'success'
            ];
            header("Location: ../gestion_personal.php");
            exit();

        } catch (PDOException $e) {
            $error_msg = 'Hubo un error al registrar los datos laborales: ' . $e->getMessage();
            $_SESSION['mensaje'] = [
                'titulo' => 'Error de Base de Datos',
                'contenido' => $error_msg,
                'tipo' => 'danger'
            ];

            // Registrar error de base de datos
            registrarLog($conn, $current_user_id, 'laboral_insert_error',
                        "Error al registrar datos para ID Persona: $id_pers - $error_msg");
        } catch (Exception $e) {
            $error_msg = $e->getMessage();
            $_SESSION['mensaje'] = [
                'titulo' => 'Error',
                'contenido' => $error_msg,
                'tipo' => 'danger'
            ];

            // Registrar excepción general
            registrarLog($conn, $current_user_id, 'laboral_exception',
                        "Excepción para ID Persona: $id_pers - $error_msg");
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Datos Laborales</title>
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
        .conditional-fields {
            padding: 1rem;
            border: 1px dashed #cccccc;
            border-radius: 8px;
            margin-top: 1rem;
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-container-custom">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="text-primary mb-0">
                    <i class="bi bi-person-workspace me-2"></i>Registro de Datos Laborales
                </h1>
                <a href="../gestion_personal.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Volver a Gestión
                </a>
            </div>

            <?php if(isset($_SESSION['mensaje'])): ?>
                <div class="alert alert-<?= $_SESSION['mensaje']['tipo'] ?> alert-dismissible fade show" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="bi <?= $_SESSION['mensaje']['tipo'] == 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?> me-2"></i>
                        <div>
                            <h5 class="mb-0"><?= htmlspecialchars($_SESSION['mensaje']['titulo']) ?></h5>
                            <p class="mb-0"><?= htmlspecialchars($_SESSION['mensaje']['contenido']) ?></p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['mensaje']); ?>
            <?php endif; ?>

            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?id_pers=' . urlencode($id_pers) . '&nombres=' . urlencode($nombres) . '&apellidos=' . urlencode($apellidos); ?>">
                <input type="hidden" name="id_pers" value="<?= htmlspecialchars($id_pers) ?>">

                <div class="form-section-header">
                    <h2><i class="bi bi-person-fill me-2"></i>Información del Trabajador</h2>
                </div>
                <div class="mb-4">
                    <label class="form-label">Trabajador:</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars(explode(' ', $nombres)[0] . ' ' . explode(' ', $apellidos)[0]) ?>" readonly>
                </div>

                <div class="form-section-header mt-5">
                    <h2><i class="bi bi-briefcase-fill me-2"></i>Datos Laborales</h2>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="fecha_ingreso" class="form-label">Fecha de Ingreso*:</label>
                        <input type="date" class="form-control" id="fecha_ingreso" name="fecha_ingreso" value="<?= htmlspecialchars($_POST['fecha_ingreso'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="estado" class="form-label">Estado Laboral*:</label>
                        <select class="form-select" id="estado" name="estado" required>
                            <option value="">Seleccione...</option>
                            <option value="activo" <?= (isset($_POST['estado']) && $_POST['estado'] == 'activo') ? 'selected' : '' ?>>Activo</option>
                            <option value="inactivo" <?= (isset($_POST['estado']) && $_POST['estado'] == 'inactivo') ? 'selected' : '' ?>>Inactivo</option>
                            <option value="reposo" <?= (isset($_POST['estado']) && $_POST['estado'] == 'reposo') ? 'selected' : '' ?>>Reposo</option>
                            <option value="vacaciones" <?= (isset($_POST['estado']) && $_POST['estado'] == 'vacaciones') ? 'selected' : '' ?>>Vacaciones</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="id_tipo_personal" class="form-label">Tipo de Personal*:</label>
                        <select class="form-select" id="id_tipo_personal" name="id_tipo_personal" required>
                            <option value="">Seleccione...</option>
                            <?php while($tipo = $tipos_personal->fetch_assoc()): ?>
                                <option value="<?= $tipo['id_tipo_personal'] ?>" <?= (isset($_POST['id_tipo_personal']) && $_POST['id_tipo_personal'] == $tipo['id_tipo_personal']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($tipo['nombre']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="id_departamento" class="form-label">Departamento*:</label>
                        <select class="form-select" id="id_departamento" name="id_departamento" required>
                            <option value="">Seleccione...</option>
                            <?php $departamentos->data_seek(0); ?>
                            <?php while($depto = $departamentos->fetch_assoc()): ?>
                                <option value="<?= $depto['id_departamento'] ?>" <?= (isset($_POST['id_departamento']) && $_POST['id_departamento'] == $depto['id_departamento']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($depto['nombre']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="id_cargo" class="form-label">Cargo*:</label>
                        <select class="form-select" id="id_cargo" name="id_cargo" required>
                            <option value="">Seleccione...</option>
                            <?php $cargos->data_seek(0); ?>
                            <?php while($cargo = $cargos->fetch_assoc()): ?>
                                <option value="<?= $cargo['id_cargo'] ?>" <?= (isset($_POST['id_cargo']) && $_POST['id_cargo'] == $cargo['id_cargo']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cargo['nombre']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="id_contrato" class="form-label">Tipo de Contrato*:</label>
                        <select class="form-select" id="id_contrato" name="id_contrato" required>
                            <option value="">Seleccione...</option>
                            <?php $tipos_contrato->data_seek(0); ?>
                            <?php while($contrato = $tipos_contrato->fetch_assoc()): ?>
                                <option value="<?= $contrato['id_contrato'] ?>" <?= (isset($_POST['id_contrato']) && $_POST['id_contrato'] == $contrato['id_contrato']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($contrato['nombre']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="id_coordinacion" class="form-label">Coordinación*:</label>
                        <select class="form-select" id="id_coordinacion" name="id_coordinacion" required>
                            <option value="">Seleccione...</option>
                            <?php $coordinaciones->data_seek(0); ?>
                            <?php while($coord = $coordinaciones->fetch_assoc()): ?>
                                <option value="<?= $coord['id_coordinacion'] ?>" <?= (isset($_POST['id_coordinacion']) && $_POST['id_coordinacion'] == $coord['id_coordinacion']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($coord['nombre']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="ficha" class="form-label">Número de Ficha*:</label>
                        <input type="text" class="form-control" id="ficha" name="ficha"
                               value="<?= htmlspecialchars($_POST['ficha'] ?? '') ?>"
                               pattern="[A-Z0-9-]+"
                               title="Solo mayúsculas, números y guiones"
                               required>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="correo_institucional" class="form-label">Correo Institucional*:</label>
                    <input type="email" class="form-control" id="correo_institucional" name="correo_institucional"
                           value="<?= htmlspecialchars($_POST['correo_institucional'] ?? '') ?>" required>
                </div>

                <div class="mb-3">
                    <label for="descripcion_funciones" class="form-label">Descripción de Funciones*:</label>
                    <textarea class="form-control" id="descripcion_funciones" name="descripcion_funciones" rows="4" required><?= htmlspecialchars($_POST['descripcion_funciones'] ?? '') ?></textarea>
                </div>

                <div class="form-section-header mt-5">
                    <h2><i class="bi bi-journal-check me-2"></i>Primas Asignadas</h2>
                </div>
                <div class="mb-3 p-3 border rounded bg-light">
                    <?php
                    $primas->data_seek(0);
                    if ($primas->num_rows > 0): ?>
                        <div class="row">
                            <?php while($prima = $primas->fetch_assoc()): ?>
                                <div class="col-md-6 col-lg-4 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox"
                                               name="primas[]"
                                               value="<?= $prima['id_prima'] ?>"
                                               id="prima_<?= $prima['id_prima'] ?>"
                                               <?= (isset($_POST['primas']) && in_array($prima['id_prima'], $_POST['primas'])) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="prima_<?= $prima['id_prima'] ?>">
                                            <?= htmlspecialchars($prima['nombre']) ?>
                                            (<?= number_format($prima['monto'], 2, ',', '.') ?> Bs)
                                        </label>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No hay primas disponibles para asignar.</p>
                    <?php endif; ?>
                </div>

                <div class="form-section-header mt-5">
                    <h2><i class="bi bi-building-fill-check me-2"></i>Experiencia Laboral Previa</h2>
                </div>
                <div class="mb-3">
                    <label class="form-label">¿Ha trabajado en otra empresa anteriormente?*</label>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="ha_trabajado_anteriormente" id="trabajo_anterior_si" value="Sí" onchange="toggleExperienciaPrevia(true)"
                               <?= (isset($_POST['ha_trabajado_anteriormente']) && $_POST['ha_trabajado_anteriormente'] == 'Sí') ? 'checked' : '' ?> required>
                        <label class="form-check-label" for="trabajo_anterior_si">Sí</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="ha_trabajado_anteriormente" id="trabajo_anterior_no" value="No" onchange="toggleExperienciaPrevia(false)"
                               <?= (isset($_POST['ha_trabajado_anteriormente']) && $_POST['ha_trabajado_anteriormente'] == 'No') ? 'checked' : '' ?> required>
                        <label class="form-check-label" for="trabajo_anterior_no">No</label>
                    </div>
                </div>

                <div id="campos_experiencia_previa" class="conditional-fields" style="display: <?= (isset($_POST['ha_trabajado_anteriormente']) && $_POST['ha_trabajado_anteriormente'] == 'Sí') ? 'block' : 'none' ?>;">
                    <div class="mb-3">
                        <label for="nombre_empresa_anterior" class="form-label">Nombre de la Institución/Empresa:</label>
                        <input type="text" class="form-control" id="nombre_empresa_anterior" name="nombre_empresa_anterior"
                               value="<?= htmlspecialchars($_POST['nombre_empresa_anterior'] ?? '') ?>"
                               <?= (isset($_POST['ha_trabajado_anteriormente']) && $_POST['ha_trabajado_anteriormente'] == 'No') ? 'disabled' : '' ?>>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="ano_ingreso_anterior" class="form-label">Fecha de Ingreso:</label>
                            <input type="date" class="form-control" id="ano_ingreso_anterior" name="ano_ingreso_anterior"
                                   value="<?= htmlspecialchars($_POST['ano_ingreso_anterior'] ?? '') ?>"
                                   max="<?= date('Y-m-d') ?>"
                                   <?= (isset($_POST['ha_trabajado_anteriormente']) && $_POST['ha_trabajado_anteriormente'] == 'No') ? 'disabled' : '' ?>>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="ano_culminacion_anterior" class="form-label">Fecha de Culminación:</label>
                            <input type="date" class="form-control" id="ano_culminacion_anterior" name="ano_culminacion_anterior"
                                   value="<?= htmlspecialchars($_POST['ano_culminacion_anterior'] ?? '') ?>"
                                   max="<?= date('Y-m-d') ?>"
                                   <?= (isset($_POST['ha_trabajado_anteriormente']) && $_POST['ha_trabajado_anteriormente'] == 'No') ? 'disabled' : '' ?>>
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-2 mt-4">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-save me-2"></i>Guardar Datos Laborales
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleExperienciaPrevia(mostrar) {
            const camposExperiencia = document.getElementById('campos_experiencia_previa');
            const nombreEmpresa = document.getElementById('nombre_empresa_anterior');
            const fechaIngreso = document.getElementById('ano_ingreso_anterior');
            const fechaCulminacion = document.getElementById('ano_culminacion_anterior');

            if (mostrar) {
                camposExperiencia.style.display = 'block';
                nombreEmpresa.removeAttribute('disabled');
                fechaIngreso.removeAttribute('disabled');
                fechaCulminacion.removeAttribute('disabled');
                nombreEmpresa.setAttribute('required', 'required');
                fechaIngreso.setAttribute('required', 'required');
                fechaCulminacion.setAttribute('required', 'required');
            } else {
                camposExperiencia.style.display = 'none';
                nombreEmpresa.setAttribute('disabled', 'disabled');
                fechaIngreso.setAttribute('disabled', 'disabled');
                fechaCulminacion.setAttribute('disabled', 'disabled');
                nombreEmpresa.value = '';
                fechaIngreso.value = '';
                fechaCulminacion.value = '';
                nombreEmpresa.removeAttribute('required');
                fechaIngreso.removeAttribute('required');
                fechaCulminacion.removeAttribute('required');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const trabajoAnteriorSi = document.getElementById('trabajo_anterior_si');
            if (trabajoAnteriorSi.checked) {
                toggleExperienciaPrevia(true);
            } else {
                toggleExperienciaPrevia(false);
            }
        });
    </script>
</body>
</html>
