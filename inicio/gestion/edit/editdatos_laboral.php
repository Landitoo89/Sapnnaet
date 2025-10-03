<?php
require_once __DIR__ . '/../conexion/conexion_db.php';
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['usuario'])) {
    header("Location: ../login.php");
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['mensaje'] = [
        'titulo' => 'Error',
        'contenido' => 'ID de registro laboral no válido. Vuelva a la página anterior.',
        'tipo' => 'danger'
    ];
    header("Location: ../gestion_laboral.php");
    exit;
}

$id_laboral = intval($_GET['id']);
$current_user_id = $_SESSION['usuario']['id'];

try {
    $pdo = new PDO("mysql:host=$servidor;dbname=$basedatos", $usuario, $contraseña);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("SELECT * FROM datos_laborales WHERE id_laboral = ?");
    $stmt->execute([$id_laboral]);
    $registro = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$registro) {
        $_SESSION['mensaje'] = [
            'titulo' => 'Error',
            'contenido' => 'Registro laboral no encontrado.',
            'tipo' => 'danger'
        ];
        header("Location: ../gestion_laboral.php");
        exit;
    }

    $stmt_pers = $pdo->prepare("SELECT nombres, apellidos, cedula_identidad FROM datos_personales WHERE id_pers = ?");
    $stmt_pers->execute([$registro['id_pers']]);
    $trabajador = $stmt_pers->fetch(PDO::FETCH_ASSOC);

    // SOLO obtener primas personalizadas (empleado_primas_personalizadas)
    $stmt_primas_manual = $pdo->prepare("SELECT id, nombre_prima, monto FROM empleado_primas_personalizadas WHERE id_laboral = ?");
    $stmt_primas_manual->execute([$id_laboral]);
    $primas_manual = $stmt_primas_manual->fetchAll(PDO::FETCH_ASSOC);

    // Listas para selectores
    $tipos_personal = $pdo->query("SELECT id_tipo_personal, nombre FROM tipos_personal ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
    $departamentos = $pdo->query("SELECT id_departamento, nombre FROM departamentos ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
    $cargos = $pdo->query("SELECT id_cargo, nombre FROM cargos ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
    $tipos_contrato = $pdo->query("SELECT id_contrato, nombre FROM tipos_contrato ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
    $coordinaciones_all = $pdo->query("SELECT id_coordinacion, nombre, id_departamento FROM coordinaciones ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $_SESSION['mensaje'] = [
        'titulo' => 'Error de Base de Datos',
        'contenido' => 'No se pudieron cargar los datos: ' . $e->getMessage(),
        'tipo' => 'danger'
    ];
    header("Location: ../gestion_laboral.php");
    exit;
}

// Procesar actualización
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['actualizar'])) {
    $fecha_ingreso = $_POST['fecha_ingreso'];
    $id_tipo_personal = $_POST['id_tipo_personal'];
    $estado = $_POST['estado'];
    $id_departamento = $_POST['id_departamento'];
    $id_cargo = $_POST['id_cargo'];
    $id_contrato = $_POST['id_contrato'];
    $ficha = trim($_POST['ficha']);
    $id_coordinacion = $_POST['id_coordinacion'];
    $sueldo = isset($_POST['sueldo']) ? floatval($_POST['sueldo']) : null;

    $ha_trabajado_anteriormente = $_POST['ha_trabajado_anteriormente'] ?? 'No';
    $nombre_empresa_anterior = ($ha_trabajado_anteriormente === 'Sí') ? trim($_POST['nombre_empresa_anterior'] ?? '') : NULL;
    $fecha_ingreso_anterior = ($ha_trabajado_anteriormente === 'Sí') ? ($_POST['fecha_ingreso_anterior'] ?? NULL) : NULL;
    $fecha_culminacion_anterior = ($ha_trabajado_anteriormente === 'Sí') ? ($_POST['fecha_culminacion_anterior'] ?? NULL) : NULL;

    $correo_institucional = filter_var($_POST['correo_institucional'] ?? '', FILTER_SANITIZE_EMAIL);
    $descripcion_funciones = trim($_POST['descripcion_funciones'] ?? '');
    $primas_manual_input = $_POST['primas_manual'] ?? [];

    $errores = [];
    if (empty($fecha_ingreso) || empty($id_tipo_personal) || empty($estado) || empty($id_departamento) || empty($id_cargo) ||
        empty($id_contrato) || empty($ficha) || empty($id_coordinacion) || empty($correo_institucional) || empty($descripcion_funciones) ||
        $sueldo === null || $sueldo <= 0) {
        $errores[] = 'Todos los campos obligatorios y el sueldo deben estar completos y ser válidos.';
    } elseif (!filter_var($correo_institucional, FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'El correo institucional no es válido.';
    } elseif ($ha_trabajado_anteriormente === 'Sí' && (empty($nombre_empresa_anterior) || empty($fecha_ingreso_anterior) || empty($fecha_culminacion_anterior))) {
        $errores[] = 'Complete todos los campos de experiencia laboral previa.';
    } elseif ($ha_trabajado_anteriormente === 'Sí' && ($fecha_ingreso_anterior > $fecha_culminacion_anterior)) {
        $errores[] = 'La fecha de ingreso anterior no puede ser posterior a la fecha de culminación anterior.';
    }

    try {
        $stmt_ficha = $pdo->prepare("SELECT id_laboral FROM datos_laborales WHERE ficha = ? AND id_laboral != ?");
        $stmt_ficha->execute([$ficha, $id_laboral]);
        if ($stmt_ficha->rowCount() > 0) {
            $errores[] = 'La ficha laboral ya existe. Por favor, introduzca una ficha única.';
        }
    } catch (PDOException $e) {
        $errores[] = 'Error al verificar la ficha: ' . $e->getMessage();
    }

    if (!empty($primas_manual_input)) {
        foreach ($primas_manual_input as $prima) {
            if (empty($prima['nombre']) || !isset($prima['monto']) || floatval($prima['monto']) <= 0) {
                $errores[] = 'Todas las primas manuales deben tener nombre y monto mayor a cero.';
                break;
            }
        }
    }

    if (empty($errores)) {
        $pdo->beginTransaction();
        try {
            // Actualizar registro laboral, incluyendo sueldo
            $stmt_update = $pdo->prepare("UPDATE datos_laborales SET
                fecha_ingreso = :fecha_ingreso,
                id_tipo_personal = :id_tipo_personal,
                estado = :estado,
                id_departamento = :id_departamento,
                id_cargo = :id_cargo,
                id_contrato = :id_contrato,
                ficha = :ficha,
                id_coordinacion = :id_coordinacion,
                ha_trabajado_anteriormente = :ha_trabajado_anteriormente,
                nombre_empresa_anterior = :nombre_empresa_anterior,
                ano_ingreso_anterior = :ano_ingreso_anterior,
                ano_culminacion_anterior = :ano_culminacion_anterior,
                correo_institucional = :correo_institucional,
                descripcion_funciones = :descripcion_funciones,
                sueldo = :sueldo,
                fecha_actualizacion = NOW()
                WHERE id_laboral = :id_laboral");

            $stmt_update->execute([
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
                ':ano_ingreso_anterior' => $fecha_ingreso_anterior,
                ':ano_culminacion_anterior' => $fecha_culminacion_anterior,
                ':correo_institucional' => $correo_institucional,
                ':descripcion_funciones' => $descripcion_funciones,
                ':sueldo' => $sueldo,
                ':id_laboral' => $id_laboral
            ]);
            // SOLO actualizar primas personalizadas
            $pdo->prepare("DELETE FROM empleado_primas_personalizadas WHERE id_laboral = ?")->execute([$id_laboral]);
            if (!empty($primas_manual_input)) {
                $stmt_insert_manual = $pdo->prepare("INSERT INTO empleado_primas_personalizadas (id_laboral, nombre_prima, monto) VALUES (?, ?, ?)");
                foreach ($primas_manual_input as $prima) {
                    $stmt_insert_manual->execute([
                        $id_laboral,
                        trim($prima['nombre']),
                        floatval($prima['monto'])
                    ]);
                }
            }

            $detalles_log = "Usuario ID: $current_user_id editó registro laboral ID: $id_laboral\n";
            $detalles_log .= "Trabajador: " . $trabajador['nombres'] . ' ' . $trabajador['apellidos'] . "\n";
            $detalles_log .= "Cambios realizados.";

            $ip_address = $_SERVER['REMOTE_ADDR'];
            $user_agent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
            $stmt_log = $pdo->prepare("INSERT INTO action_logs (user_id, event_type, details, ip_address, user_agent) 
                VALUES (:user_id, :event_type, :details, :ip_address, :user_agent)");
            $stmt_log->execute([
                ':user_id' => $current_user_id,
                ':event_type' => 'edit_laboral_data',
                ':details' => $detalles_log,
                ':ip_address' => $ip_address,
                ':user_agent' => $user_agent
            ]);

            $pdo->commit();
            $_SESSION['mensaje'] = [
                'titulo' => '¡Éxito!',
                'contenido' => 'Datos laborales actualizados correctamente.',
                'tipo' => 'success'
            ];
            header("Location: ../gestion_laboral.php");
            exit();

        } catch (PDOException $e) {
            $pdo->rollback();
            $_SESSION['mensaje'] = [
                'titulo' => 'Error de Base de Datos',
                'contenido' => 'Hubo un error al actualizar los datos laborales: ' . $e->getMessage(),
                'tipo' => 'danger'
            ];
            error_log('Error al actualizar datos laborales: ' . $e->getMessage());
        }
    } else {
        $_SESSION['mensaje'] = [
            'titulo' => 'Error de Validación',
            'contenido' => implode('<br>', $errores),
            'tipo' => 'danger'
        ];
    }
    header("Location: editdatos_laboral.php?id=" . $id_laboral);
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Datos Laborales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary-color: #2c3e50; --secondary-color: #3498db; }
        .form-container-custom { background: white; padding: 2.5rem; border-radius: 15px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); max-width: 900px; margin: 2rem auto; }
        .form-section-header { background-color: #f0f2f5; padding: 1rem 1.5rem; border-radius: 10px; margin-bottom: 2rem; border-left: 5px solid var(--secondary-color); }
        .form-section-header h2 { margin-bottom: 0; color: var(--primary-color);}
        .conditional-fields { padding: 1rem; border: 1px dashed #cccccc; border-radius: 8px; margin-top: 1rem; background-color: #f9f9f9;}
    </style>
</head>
<body>
    <div class="container">
        <div class="form-container-custom">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="text-primary mb-0">
                    <i class="bi bi-person-workspace me-2"></i>Editar Datos Laborales
                </h1>
                <a href="../gestion_laboral.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Volver a Gestión
                </a>
            </div>
            <?php if(isset($_SESSION['mensaje'])): ?>
                <div class="alert alert-<?= $_SESSION['mensaje']['tipo'] ?> alert-dismissible fade show" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="bi <?= $_SESSION['mensaje']['tipo'] == 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?> me-2"></i>
                        <div>
                            <h5 class="mb-0"><?= htmlspecialchars($_SESSION['mensaje']['titulo']) ?></h5>
                            <p class="mb-0"><?= $_SESSION['mensaje']['contenido'] ?></p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['mensaje']); ?>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="id_laboral" value="<?= htmlspecialchars($id_laboral) ?>">

                <div class="form-section-header">
                    <h2><i class="bi bi-person-fill me-2"></i>Información del Trabajador</h2>
                </div>
                <div class="mb-4">
                    <label class="form-label">Trabajador:</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($trabajador['nombres'] . ' ' . $trabajador['apellidos']) ?>" readonly>
                    <small class="form-text text-muted">Cédula: <?= htmlspecialchars($trabajador['cedula_identidad']) ?></small>
                </div>

                <div class="form-section-header mt-5">
                    <h2><i class="bi bi-briefcase-fill me-2"></i>Datos Laborales</h2>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="fecha_ingreso" class="form-label">Fecha de Ingreso*:</label>
                        <input type="date" class="form-control" id="fecha_ingreso" name="fecha_ingreso" value="<?= htmlspecialchars($registro['fecha_ingreso'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="estado" class="form-label">Estado Laboral*:</label>
                        <select class="form-select" id="estado" name="estado" required>
                            <option value="">Seleccione...</option>
                            <option value="activo" <?= ($registro['estado'] == 'activo') ? 'selected' : '' ?>>Activo</option>
                            <option value="inactivo" <?= ($registro['estado'] == 'inactivo') ? 'selected' : '' ?>>Inactivo</option>
                            <option value="reposo" <?= ($registro['estado'] == 'reposo') ? 'selected' : '' ?>>Reposo</option>
                            <option value="vacaciones" <?= ($registro['estado'] == 'vacaciones') ? 'selected' : '' ?>>Vacaciones</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="id_tipo_personal" class="form-label">Tipo de Personal*:</label>
                        <select class="form-select" id="id_tipo_personal" name="id_tipo_personal" required>
                            <option value="">Seleccione...</option>
                            <?php foreach($tipos_personal as $tipo): ?>
                                <option value="<?= htmlspecialchars($tipo['id_tipo_personal']) ?>" <?= ($registro['id_tipo_personal'] == $tipo['id_tipo_personal']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($tipo['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="id_departamento" class="form-label">Departamento*:</label>
                        <select class="form-select" id="id_departamento" name="id_departamento" required>
                            <option value="">Seleccione...</option>
                            <?php foreach($departamentos as $depto): ?>
                                <option value="<?= htmlspecialchars($depto['id_departamento']) ?>" <?= ($registro['id_departamento'] == $depto['id_departamento']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($depto['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="id_cargo" class="form-label">Cargo*:</label>
                        <select class="form-select" id="id_cargo" name="id_cargo" required>
                            <option value="">Seleccione...</option>
                            <?php foreach($cargos as $cargo): ?>
                                <option value="<?= htmlspecialchars($cargo['id_cargo']) ?>" <?= ($registro['id_cargo'] == $cargo['id_cargo']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cargo['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="id_contrato" class="form-label">Tipo de Contrato*:</label>
                        <select class="form-select" id="id_contrato" name="id_contrato" required>
                            <option value="">Seleccione...</option>
                            <?php foreach($tipos_contrato as $contrato): ?>
                                <option value="<?= htmlspecialchars($contrato['id_contrato']) ?>" <?= ($registro['id_contrato'] == $contrato['id_contrato']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($contrato['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="id_coordinacion" class="form-label">Coordinación*:</label>
                        <select class="form-select" id="id_coordinacion" name="id_coordinacion" required>
                            <option value="">Seleccione...</option>
                            <?php foreach($coordinaciones_all as $coord): ?>
                                <option value="<?= htmlspecialchars($coord['id_coordinacion']) ?>" 
                                    data-departamento-id="<?= htmlspecialchars($coord['id_departamento']) ?>"
                                    <?= ($registro['id_coordinacion'] == $coord['id_coordinacion']) ? 'selected' : '' ?>
                                    style="display: none;">
                                    <?= htmlspecialchars($coord['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="ficha" class="form-label">Número de Ficha*:</label>
                        <input type="text" class="form-control" id="ficha" name="ficha"
                               value="<?= htmlspecialchars($registro['ficha'] ?? '') ?>"
                               pattern="[A-Z0-9-]+"
                               title="Solo mayúsculas, números y guiones"
                               required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="sueldo" class="form-label">Sueldo Mensual (Bs)*:</label>
                        <input type="number" class="form-control" id="sueldo" name="sueldo"
                               value="<?= htmlspecialchars($registro['sueldo'] ?? '') ?>"
                               min="0.01" step="0.01" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="correo_institucional" class="form-label">Correo Institucional*:</label>
                    <input type="email" class="form-control" id="correo_institucional" name="correo_institucional"
                           value="<?= htmlspecialchars($registro['correo_institucional'] ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label for="descripcion_funciones" class="form-label">Descripción de Funciones*:</label>
                    <textarea class="form-control" id="descripcion_funciones" name="descripcion_funciones" rows="4" required><?= htmlspecialchars($registro['descripcion_funciones'] ?? '') ?></textarea>
                </div>

                <!-- SOLO Primas personalizadas -->
                <div class="form-section-header mt-5">
                    <h2><i class="bi bi-journal-check me-2"></i>Primas Personalizadas Asignadas</h2>
                </div>
                <div class="mb-3 p-3 border rounded bg-light">
                    <label class="form-label">Primas Personalizadas:</label>
                    <div id="primas-manual-container">
                        <?php
                        if (!empty($primas_manual)) {
                            foreach ($primas_manual as $i => $prima) {
                        ?>
                        <div class="row prima-row mb-2 align-items-center">
                            <div class="col-md-5">
                                <input type="text" name="primas_manual[<?= $i ?>][nombre]" class="form-control" placeholder="Nombre de la Prima" value="<?= htmlspecialchars($prima['nombre_prima']) ?>" required>
                            </div>
                            <div class="col-md-5">
                                <input type="number" name="primas_manual[<?= $i ?>][monto]" class="form-control" placeholder="Monto (Bs)" value="<?= htmlspecialchars($prima['monto']) ?>" min="0.01" step="0.01" required>
                            </div>
                            <div class="col-md-2 text-center">
                                <button type="button" class="btn btn-danger btn-sm" onclick="eliminarPrimaManual(this)">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                        <?php
                            }
                        } else {
                        ?>
                        <div class="row prima-row mb-2 align-items-center">
                            <div class="col-md-5">
                                <input type="text" name="primas_manual[0][nombre]" class="form-control" placeholder="Nombre de la Prima" required>
                            </div>
                            <div class="col-md-5">
                                <input type="number" name="primas_manual[0][monto]" class="form-control" placeholder="Monto (Bs)" min="0.01" step="0.01" required>
                            </div>
                            <div class="col-md-2 text-center">
                                <button type="button" class="btn btn-danger btn-sm" onclick="eliminarPrimaManual(this)">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                        <?php } ?>
                    </div>
                    <button type="button" class="btn btn-outline-success mt-2" onclick="agregarPrimaManual()">
                        <i class="bi bi-plus-circle"></i> Agregar otra prima personalizada
                    </button>
                </div>

                <div class="form-section-header mt-5">
                    <h2><i class="bi bi-building-fill-check me-2"></i>Experiencia Laboral Previa</h2>
                </div>
                <div class="mb-3">
                    <label class="form-label">¿Ha trabajado en otra empresa anteriormente?*</label>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="ha_trabajado_anteriormente" id="trabajo_anterior_si" value="Sí" onchange="toggleExperienciaPrevia(true)"
                               <?= ($registro['ha_trabajado_anteriormente'] == 'Sí') ? 'checked' : '' ?> required>
                        <label class="form-check-label" for="trabajo_anterior_si">Sí</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="ha_trabajado_anteriormente" id="trabajo_anterior_no" value="No" onchange="toggleExperienciaPrevia(false)"
                               <?= ($registro['ha_trabajado_anteriormente'] == 'No') ? 'checked' : '' ?> required>
                        <label class="form-check-label" for="trabajo_anterior_no">No</label>
                    </div>
                </div>
                <div id="campos_experiencia_previa" class="conditional-fields" style="display: <?= ($registro['ha_trabajado_anteriormente'] == 'Sí') ? 'block' : 'none' ?>;">
                    <div class="mb-3">
                        <label for="nombre_empresa_anterior" class="form-label">Nombre de la Institución/Empresa:</label>
                        <input type="text" class="form-control" id="nombre_empresa_anterior" name="nombre_empresa_anterior"
                               value="<?= htmlspecialchars($registro['nombre_empresa_anterior'] ?? '') ?>"
                               <?= ($registro['ha_trabajado_anteriormente'] == 'No') ? 'disabled' : '' ?>>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="fecha_ingreso_anterior" class="form-label">Fecha de Ingreso:</label>
                            <input type="date" class="form-control" id="fecha_ingreso_anterior" name="fecha_ingreso_anterior"
                                   value="<?= htmlspecialchars($registro['ano_ingreso_anterior'] ?? '') ?>"
                                   max="<?= date('Y-m-d') ?>"
                                   <?= ($registro['ha_trabajado_anteriormente'] == 'No') ? 'disabled' : '' ?>>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="fecha_culminacion_anterior" class="form-label">Fecha de Culminación:</label>
                            <input type="date" class="form-control" id="fecha_culminacion_anterior" name="fecha_culminacion_anterior"
                                   value="<?= htmlspecialchars($registro['ano_culminacion_anterior'] ?? '') ?>"
                                   max="<?= date('Y-m-d') ?>"
                                   <?= ($registro['ha_trabajado_anteriormente'] == 'No') ? 'disabled' : '' ?>>
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-2 mt-4">
                    <button type="submit" name="actualizar" class="btn btn-primary btn-lg">
                        <i class="bi bi-save me-2"></i>Actualizar Datos Laborales
                    </button>
                </div>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function agregarPrimaManual() {
            var primasContainer = document.getElementById('primas-manual-container');
            var index = primasContainer.querySelectorAll('.prima-row').length;
            var row = document.createElement('div');
            row.className = 'row prima-row mb-2 align-items-center';
            row.innerHTML = `
                <div class="col-md-5">
                    <input type="text" name="primas_manual[${index}][nombre]" class="form-control" placeholder="Nombre de la Prima" required>
                </div>
                <div class="col-md-5">
                    <input type="number" name="primas_manual[${index}][monto]" class="form-control" placeholder="Monto (Bs)" min="0.01" step="0.01" required>
                </div>
                <div class="col-md-2 text-center">
                    <button type="button" class="btn btn-danger btn-sm" onclick="eliminarPrimaManual(this)">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            `;
            primasContainer.appendChild(row);
        }

        function eliminarPrimaManual(btn) {
            var row = btn.closest('.prima-row');
            var primasContainer = document.getElementById('primas-manual-container');
            // Solo permitir eliminar si hay más de una prima
            if (primasContainer.querySelectorAll('.prima-row').length > 1) {
                row.parentNode.removeChild(row);
            } else {
                // Opcional: Si quieres alertar al usuario
                alert('Debe haber al menos una prima personalizada.');
            }
        }

        // Experiencia previa
        function toggleExperienciaPrevia(mostrar) {
            const camposExperiencia = document.getElementById('campos_experiencia_previa');
            const nombreEmpresa = document.getElementById('nombre_empresa_anterior');
            const fechaIngreso = document.getElementById('fecha_ingreso_anterior');
            const fechaCulminacion = document.getElementById('fecha_culminacion_anterior');
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
        // Coordinaciones
        const coordinacionesData = <?= json_encode($coordinaciones_all) ?>;
        function filterCoordinaciones() {
            const departamentoSelect = document.getElementById('id_departamento');
            const coordinacionSelect = document.getElementById('id_coordinacion');
            const selectedDepartamentoId = departamentoSelect.value;
            Array.from(coordinacionSelect.options).forEach(option => {
                option.style.display = 'none';
                option.selected = false;
            });
            let firstOptionDisplayed = false;
            Array.from(coordinacionSelect.options).forEach(option => {
                if (option.value === "" || option.dataset.departamentoId === selectedDepartamentoId) {
                    option.style.display = 'block';
                    if (!firstOptionDisplayed && option.value !== "") {
                        option.selected = true;
                        firstOptionDisplayed = true;
                    }
                }
            });
            const currentCoordInRegistro = "<?= htmlspecialchars($registro['id_coordinacion']) ?>";
            const currentSelectedCoordOption = coordinacionSelect.querySelector(`option[value="${currentCoordInRegistro}"]`);
            if (currentSelectedCoordOption && currentSelectedCoordOption.dataset.departamentoId === selectedDepartamentoId) {
                coordinacionSelect.value = currentCoordInRegistro;
            } else if (!firstOptionDisplayed && selectedDepartamentoId !== "") {
                const firstAvailableOption = coordinacionSelect.querySelector('option[style="display: block;"]:not([value=""])');
                if (firstAvailableOption) {
                    firstAvailableOption.selected = true;
                } else {
                    coordinacionSelect.value = "";
                }
            } else if (selectedDepartamentoId === "") {
                coordinacionSelect.value = "";
            }
        }
        document.getElementById('id_departamento').addEventListener('change', filterCoordinaciones);
        document.addEventListener('DOMContentLoaded', function() {
            filterCoordinaciones();
            const trabajoAnteriorSi = document.getElementById('trabajo_anterior_si');
            toggleExperienciaPrevia(trabajoAnteriorSi.checked);
            const initialSelectedCoord = "<?= htmlspecialchars($registro['id_coordinacion']) ?>";
            const coordinacionSelect = document.getElementById('id_coordinacion');
            if (initialSelectedCoord) {
                coordinacionSelect.value = initialSelectedCoord;
                const selectedOption = coordinacionSelect.querySelector(`option[value="${initialSelectedCoord}"]`);
                if (selectedOption) {
                    selectedOption.style.display = 'block';
                }
            }
        });
    </script>
</body>
</html>