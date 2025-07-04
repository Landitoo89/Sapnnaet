<?php
session_start();
require '../conexion.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$errores = [];
$datos_empleado = null;
$coincidencias = [];

$fecha_actual_iso = date('Y-m-d');

// --- Búsqueda de empleado ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buscar_empleado'])) {
    $busqueda_term = trim($_POST['busqueda']);
    if (!empty($busqueda_term)) {
        try {
            $sql = "
                SELECT p.id_pers, p.nombres, p.apellidos, p.cedula_identidad,
                       dl.fecha_ingreso, dl.estado,
                       tp.nombre AS tipo_personal_nombre
                FROM datos_personales p
                INNER JOIN (
                    SELECT id_pers, MAX(id_laboral) as max_id_laboral
                    FROM datos_laborales
                    GROUP BY id_pers
                ) AS latest_dl_id ON p.id_pers = latest_dl_id.id_pers
                INNER JOIN datos_laborales dl ON latest_dl_id.max_id_laboral = dl.id_laboral
                LEFT JOIN tipos_personal tp ON dl.id_tipo_personal = tp.id_tipo_personal
                WHERE p.cedula_identidad LIKE :q
                   OR p.nombres LIKE :q
                   OR p.apellidos LIKE :q
                   OR CONCAT(p.nombres, ' ', p.apellidos) LIKE :q
                ORDER BY p.nombres, p.apellidos
                LIMIT 10
            ";
            $stmt = $conn->prepare($sql);
            $stmt->execute([":q" => "%$busqueda_term%"]);
            $coincidencias = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($coincidencias)) {
                $errores[] = "No se encontraron coincidencias para la búsqueda: " . htmlspecialchars($busqueda_term);
            }

        } catch (PDOException $e) {
            $errores[] = "Error de base de datos al buscar empleado: " . $e->getMessage();
        }
    } else {
        $errores[] = "Por favor, ingrese una cédula, nombre o apellido para buscar.";
    }
}

// --- Selección de empleado ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['seleccionar_empleado'])) {
    $id_pers_seleccionado = intval($_POST['id_pers']);

    try {
        $stmt = $conn->prepare("
            SELECT p.id_pers, p.nombres, p.apellidos, p.cedula_identidad,
                   dl.fecha_ingreso, dl.estado,
                   tp.nombre AS tipo_personal_nombre
            FROM datos_personales p
            INNER JOIN datos_laborales dl ON p.id_pers = dl.id_pers
            LEFT JOIN tipos_personal tp ON dl.id_tipo_personal = tp.id_tipo_personal
            WHERE p.id_pers = ?
            ORDER BY dl.fecha_ingreso DESC
            LIMIT 1
        ");
        $stmt->execute([$id_pers_seleccionado]);
        $datos_empleado = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$datos_empleado) {
            $errores[] = "No se pudo cargar la información del empleado seleccionado.";
        } elseif ($datos_empleado['estado'] == 'reposo') {
            $errores[] = "El empleado ya se encuentra en período de reposo.";
        }
    } catch (PDOException $e) {
        $errores[] = "Error de base de datos al seleccionar empleado: " . $e->getMessage();
    }
}

// --- Registro de reposo ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar_reposo'])) {
    $id_pers = $_POST['id_pers'];
    $tipo_concesion = $_POST['tipo_concesion'] ?? '';
    $motivo_reposo = $_POST['motivo_reposo'] ?? '';
    $fecha_inicio = $_POST['fecha_inicio'];
    $fecha_fin = $_POST['fecha_fin'];
    $dias_otorgados = $_POST['dias_otorgados'];
    $observaciones = isset($_POST['observaciones']) ? trim($_POST['observaciones']) : null;
    $ruta_archivo_adjunto = null;

    // Estado actual
    $current_status_data = null;
    try {
        $stmt_check_status = $conn->prepare("SELECT estado FROM datos_laborales WHERE id_pers = ?");
        $stmt_check_status->execute([$id_pers]);
        $current_status_data = $stmt_check_status->fetch(PDO::FETCH_ASSOC);

        if (!$current_status_data) {
            $errores[] = "No se pudo verificar el estado del empleado. Intente de nuevo.";
        } elseif ($current_status_data['estado'] == 'reposo') {
            $errores[] = "El empleado ya se encuentra en período de reposo.";
        }
    } catch (PDOException $e) {
        $errores[] = "Error al verificar el estado: " . $e->getMessage();
    }

    if (empty($id_pers) || empty($tipo_concesion) || empty($motivo_reposo) || empty($fecha_inicio) || empty($fecha_fin) || empty($dias_otorgados)) {
        $errores[] = "Todos los campos obligatorios deben ser completados.";
    }
    if (new DateTime($fecha_inicio) > new DateTime($fecha_fin)) {
        $errores[] = "La fecha de inicio no puede ser posterior a la fecha de fin.";
    }

    // Subida de archivo adjunto (si potestativa)
    if ($tipo_concesion === 'potestativa' && isset($_FILES['archivo_adjunto']) && $_FILES['archivo_adjunto']['error'] === UPLOAD_ERR_OK) {
        $archivo = $_FILES['archivo_adjunto'];
        $nombre_archivo = basename($archivo['name']);
        $tipo_archivo = $archivo['type'];
        $tamano_archivo = $archivo['size'];
        $directorio_subidas = 'uploads/reposos/';
        if (!is_dir($directorio_subidas)) mkdir($directorio_subidas, 0777, true);
        $extension = pathinfo($nombre_archivo, PATHINFO_EXTENSION);
        $nombre_unico = uniqid('reposo_') . '.' . $extension;
        $ruta_destino = $directorio_subidas . $nombre_unico;
        $tipos_permitidos = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
        $tamano_maximo = 1000 * 1024;
        if (!in_array($tipo_archivo, $tipos_permitidos)) {
            $errores[] = "Tipo de archivo no permitido. Solo imágenes o PDF.";
        }
        if ($tamano_archivo > $tamano_maximo) {
            $errores[] = "El archivo adjunto no debe superar los 1000 KB.";
        }
        if (empty($errores)) {
            if (move_uploaded_file($archivo['tmp_name'], $ruta_destino)) {
                $ruta_archivo_adjunto = $ruta_destino;
            } else {
                $errores[] = "Error al subir el archivo adjunto.";
            }
        }
    }

    // Si no hay errores, registrar el reposo y cortar vacaciones si es necesario
    if (empty($errores)) {
        try {
            $conn->beginTransaction();

            $vacacion_interrumpida_id = null;

            // Si el empleado está de vacaciones, interrumpir y reprogramar
            if ($current_status_data['estado'] == 'vacaciones') {
                $stmt_vacacion_activa = $conn->prepare("
                    SELECT id_vacaciones, fecha_inicio, fecha_fin
                    FROM vacaciones
                    WHERE id_pers = ? AND estado = 'vacaciones'
                    ORDER BY fecha_inicio DESC
                    LIMIT 1
                ");
                $stmt_vacacion_activa->execute([$id_pers]);
                $vacacion_activa = $stmt_vacacion_activa->fetch(PDO::FETCH_ASSOC);

                if ($vacacion_activa) {
                    $vacacion_interrumpida_id = $vacacion_activa['id_vacaciones'];
                    $fecha_inicio_vac = new DateTime($vacacion_activa['fecha_inicio']);
                    $fecha_fin_vac = new DateTime($vacacion_activa['fecha_fin']);
                    $fecha_inicio_reposo = new DateTime($fecha_inicio);
                    $fecha_fin_reposo = new DateTime($fecha_fin);

                    // Días calendario antes del reposo
                    $fin_vac_usada = (clone $fecha_inicio_reposo)->modify('-1 day')->format('Y-m-d');
                    $dias_vacaciones_usados = obtenerDiasCalendarioEntreFechas(
                        $fecha_inicio_vac->format('Y-m-d'),
                        $fin_vac_usada
                    );

                    // Días calendario totales
                    $dias_vacaciones_totales = obtenerDiasCalendarioEntreFechas(
                        $fecha_inicio_vac->format('Y-m-d'),
                        $fecha_fin_vac->format('Y-m-d')
                    );

                    // Corta la vacación original y marca como interrumpida
                    $stmt_update_vacacion = $conn->prepare("
                        UPDATE vacaciones
                        SET fecha_fin = ?, estado = 'interrumpida'
                        WHERE id_vacaciones = ?
                    ");
                    $stmt_update_vacacion->execute([$fin_vac_usada, $vacacion_interrumpida_id]);

                    // Días restantes y crear vacación pendiente
                    $dias_vacaciones_restantes = $dias_vacaciones_totales - $dias_vacaciones_usados;
                    if ($dias_vacaciones_restantes > 0) {
                        $fecha_reinicio = (clone $fecha_fin_reposo)->modify('+1 day')->format('Y-m-d');
                        // Obtener tipo de personal
                        $stmt_tipo_personal = $conn->prepare("
                            SELECT tp.nombre FROM datos_laborales dl
                            LEFT JOIN tipos_personal tp ON dl.id_tipo_personal = tp.id_tipo_personal
                            WHERE dl.id_pers = ?
                            ORDER BY dl.fecha_ingreso DESC
                            LIMIT 1
                        ");
                        $stmt_tipo_personal->execute([$id_pers]);
                        $tipo_personal = $stmt_tipo_personal->fetchColumn();

                        $nueva_fecha_fin_vacacion_reanudada = calcularFechaFinCalendario(
                            $fecha_reinicio,
                            $dias_vacaciones_restantes
                        );

                        $stmt_insert_vacacion_pendiente = $conn->prepare("
                            INSERT INTO vacaciones (id_pers, fecha_inicio, fecha_fin, estado, vacacion_original_id)
                            VALUES (?, ?, ?, 'pendiente_reposo', ?)
                        ");
                        $stmt_insert_vacacion_pendiente->execute([
                            $id_pers,
                            $fecha_reinicio,
                            $nueva_fecha_fin_vacacion_reanudada,
                            $vacacion_interrumpida_id
                        ]);
                    }
                }
            }

            // Registrar el reposo
            $stmt = $conn->prepare("
                INSERT INTO reposos (id_pers, tipo_concesion, motivo_reposo, dias_otorgados, fecha_inicio, fecha_fin, estado, observaciones, ruta_archivo_adjunto, vacacion_interrumpida_id)
                VALUES (?, ?, ?, ?, ?, ?, 'activo', ?, ?, ?)
            ");
            $stmt->execute([
                $id_pers,
                $tipo_concesion,
                $motivo_reposo,
                $dias_otorgados,
                $fecha_inicio,
                $fecha_fin,
                $observaciones,
                $ruta_archivo_adjunto,
                $vacacion_interrumpida_id
            ]);

            $id_reposo_generado = $conn->lastInsertId();

            // Cambia estado laboral a reposo
            $stmt = $conn->prepare("UPDATE datos_laborales SET estado = 'reposo' WHERE id_pers = ?");
            $stmt->execute([$id_pers]);

            $conn->commit();

            $_SESSION['mensaje'] = [
                'titulo' => '¡Éxito!',
                'contenido' => 'Reposo registrado exitosamente. Generando reporte...',
                'tipo' => 'success'
            ];

            header('Location: reposo-registrado-exito.php?id_reposo=' . $id_reposo_generado);
            exit;

        } catch (PDOException $e) {
            $conn->rollBack();
            $errores[] = "Error al registrar el reposo: " . $e->getMessage();
        }
    }
}

// === FUNCIONES AUXILIARES (SOLO CALENDARIO, NO FERIADOS/NO HÁBILES) ===
function calcularFechaFinCalendario($fechaInicioStr, $dias) {
    $fecha = new DateTime($fechaInicioStr . 'T00:00:00');
    $fecha->modify('+' . ($dias - 1) . ' days');
    return $fecha->format('Y-m-d');
}
function obtenerDiasCalendarioEntreFechas($fechaInicioStr, $fechaFinStr) {
    $fechaInicio = new DateTime($fechaInicioStr . 'T00:00:00');
    $fechaFin = new DateTime($fechaFinStr . 'T00:00:00');
    if ($fechaInicio > $fechaFin) return 0;
    return $fechaFin->diff($fechaInicio)->days + 1;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Reposo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="../css/styles.css" rel="stylesheet">
    <style>
        .reposo-card { background: white; border-radius: 15px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); padding: 2rem;}
        input[readonly] { background-color: #f8f9fa; }
        .option-box { border: 1px solid #ced4da; border-radius: 8px; padding: 15px; margin-bottom: 10px; cursor: pointer; transition: all 0.2s ease-in-out;}
        .option-box:hover { background-color: #e9f5ff; border-color: #007bff; transform: translateY(-2px);}
        .option-box.selected { background-color: #e2f0ff; border-color: #007bff; box-shadow: 0 0 0 0.25rem rgba(0, 123, 255, 0.25);}
        .hidden { display: none;}
        .file-upload-label { display: block; margin-top: 10px; padding: 10px; border: 2px dashed #007bff; border-radius: 8px; text-align: center; cursor: pointer; color: #007bff; transition: all 0.2s ease-in-out;}
        .file-upload-label:hover { background-color: #e9f5ff; }
        .file-name { margin-top: 5px; font-size: 0.9em; color: #6c757d;}
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="reposo-card mx-auto" style="max-width: 900px;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="text-primary">
                    <i class="bi bi-person-lines-fill me-2"></i>
                    Registrar Reposo
                </h2>
                <a href="gestion-reposos.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Volver
                </a>
            </div>

            <?php if(!empty($errores)): ?>
                <div class="alert alert-danger mb-4">
                    <?php foreach ($errores as $error): ?>
                        <div><?= htmlspecialchars($error) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if(isset($_SESSION['mensaje'])): ?>
            <div class="alert alert-<?= $_SESSION['mensaje']['tipo'] ?> alert-dismissible fade show" role="alert">
                <div class="d-flex align-items-center">
                    <i class="bi <?= $_SESSION['mensaje']['tipo'] == 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?> me-2"></i>
                    <div>
                        <h5 class="mb-0"><?= $_SESSION['mensaje']['titulo'] ?></h5>
                        <p class="mb-0"><?= $_SESSION['mensaje']['contenido'] ?></p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['mensaje']); ?>
            <?php endif; ?>

            <!-- Formulario de búsqueda -->
            <form method="POST" class="mb-4">
                <div class="input-group">
                    <input type="text" name="busqueda" class="form-control form-control-lg"
                           placeholder="Buscar por cédula, nombre o apellido"
                           value="<?= isset($_POST['busqueda']) ? htmlspecialchars($_POST['busqueda']) : '' ?>" required>
                    <button type="submit" name="buscar_empleado" class="btn btn-primary btn-lg">
                        <i class="bi bi-search me-2"></i>Buscar
                    </button>
                </div>
            </form>

            <!-- Lista de coincidencias -->
            <?php if (!empty($coincidencias) && !$datos_empleado): ?>
                <div class="table-responsive mb-4">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Seleccionar</th>
                                <th>Cédula</th>
                                <th>Nombre</th>
                                <th>Apellido</th>
                                <th>Tipo de Personal</th>
                                <th>Estado Laboral</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($coincidencias as $emp): ?>
                            <tr>
                                <td>
                                    <form method="POST" style="margin:0;">
                                        <input type="hidden" name="id_pers" value="<?= $emp['id_pers'] ?>">
                                        <button type="submit" name="seleccionar_empleado" class="btn btn-link text-primary">
                                            <i class="bi bi-check-circle"></i> Seleccionar
                                        </button>
                                    </form>
                                </td>
                                <td><?= htmlspecialchars($emp['cedula_identidad']) ?></td>
                                <td><?= htmlspecialchars($emp['nombres']) ?></td>
                                <td><?= htmlspecialchars($emp['apellidos']) ?></td>
                                <td><?= htmlspecialchars($emp['tipo_personal_nombre'] ?? 'N/A') ?></td>
                                <td>
                                    <?php
                                        $estado_laboral = htmlspecialchars($emp['estado']);
                                        $badge_class = '';
                                        switch ($estado_laboral) {
                                            case 'activo': $badge_class = 'bg-success'; break;
                                            case 'vacaciones': $badge_class = 'bg-warning text-dark'; break;
                                            case 'reposo': $badge_class = 'bg-info text-dark'; break;
                                            case 'inactivo': $badge_class = 'bg-danger'; break;
                                            default: $badge_class = 'bg-secondary'; break;
                                        }
                                    ?>
                                    <span class="badge <?= $badge_class ?>"><?= ucfirst($estado_laboral) ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" id="reposoForm">
                <?php if($datos_empleado): ?>
                    <div class="bg-light p-4 rounded-3 mb-4">
                        <input type="hidden" name="id_pers" id="id_pers" value="<?= $datos_empleado['id_pers'] ?>">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Nombre Completo</label>
                                <input type="text" class="form-control"
                                       value="<?= htmlspecialchars($datos_empleado['nombres'] . ' ' . $datos_empleado['apellidos']) ?>"
                                       readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Cédula de Identidad</label>
                                <input type="text" class="form-control"
                                       value="<?= htmlspecialchars($datos_empleado['cedula_identidad']) ?>"
                                       readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Tipo de Personal</label>
                                <input type="text" class="form-control"
                                       value="<?= htmlspecialchars($datos_empleado['tipo_personal_nombre'] ?? 'N/A') ?>"
                                       readonly id="tipo_personal">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Estado Actual</label>
                                <input type="text" class="form-control"
                                       value="<?= htmlspecialchars(ucfirst($datos_empleado['estado'])) ?>"
                                       readonly>
                                <input type="hidden" name="estado_empleado_original" value="<?= htmlspecialchars($datos_empleado['estado']) ?>">
                            </div>
                        </div>
                    </div>

                    <?php if (empty($errores)): ?>
                        <div class="mb-4">
                            <h5 class="fw-bold mb-3">
                                <i class="bi bi-journal-check me-2"></i>
                                Seleccione el Tipo de Concesión
                            </h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="form-check option-box" id="obligatoria_option">
                                        <input class="form-check-input" type="radio" name="tipo_concesion" id="obligatoria" value="obligatoria" required>
                                        <label class="form-check-label" for="obligatoria">
                                            Concesiones Obligatorias
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check option-box" id="potestativa_option">
                                        <input class="form-check-input" type="radio" name="tipo_concesion" id="potestativa" value="potestativa" required>
                                        <label class="form-check-label" for="potestativa">
                                            Concesiones Potestativas
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="obligatorias_options" class="hidden mb-4">
                            <h5 class="fw-bold mb-3">
                                <i class="bi bi-list-check me-2"></i>
                                Opciones de Concesiones Obligatorias
                            </h5>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="motivo_reposo" id="fallecimiento" value="Fallecimiento de cónyuges, padres o hijos">
                                <label class="form-check-label" for="fallecimiento">1. Fallecimiento de cónyuges, padres o hijos</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="motivo_reposo" id="matrimonio" value="Matrimonio del trabajador">
                                <label class="form-check-label" for="matrimonio">2. Matrimonio del trabajador</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="motivo_reposo" id="paternidad" value="Paternidad">
                                <label class="form-check-label" for="paternidad">3. Paternidad</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="motivo_reposo" id="dirigencia_sindical" value="Cumplir actividades de dirigencia sindical">
                                <label class="form-check-label" for="dirigencia_sindical">4. Cumplir actividades de dirigencia sindical</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="motivo_reposo" id="comparecencia_autoridades" value="Comparecencia obligatoria ante las autoridades, legislativas, administrativas y judiciales">
                                <label class="form-check-label" for="comparecencia_autoridades">5. Comparecencia obligatoria ante las autoridades, legislativas, administrativas y judiciales</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="motivo_reposo" id="eventos_deportivos" value="Participación activa en eventos deportivos en representación al país">
                                <label class="form-check-label" for="eventos_deportivos">6. Participación activa en eventos deportivos en representación al país</label>
                            </div>
                        </div>

                        <div id="potestativas_options" class="hidden mb-4">
                            <h5 class="fw-bold mb-3">
                                <i class="bi bi-list-ul me-2"></i>
                                Opciones de Concesiones Potestativas (Ley de Carrera Administrativa Venezolana 65)
                            </h5>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="motivo_reposo" id="estudios" value="Para realizar estudios de postgrado o especialización">
                                <label class="form-check-label" for="estudios">1. Para realizar estudios de postgrado o especialización</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="motivo_reposo" id="investigacion" value="Para realizar investigaciones científicas o técnicas">
                                <label class="form-check-label" for="investigacion">2. Para realizar investigaciones científicas o técnicas</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="motivo_reposo" id="asuntos_personales" value="Asuntos personales que no afecten el servicio">
                                <label class="form-check-label" for="asuntos_personales">3. Asuntos personales que no afecten el servicio</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="motivo_reposo" id="representacion" value="Para representar al país en eventos deportivos o culturales">
                                <label class="form-check-label" for="representacion">4. Para representar al país en eventos deportivos o culturales</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="motivo_reposo" id="otros" value="Otros">
                                <label class="form-check-label" for="otros">5. Otros (especifique)</label>
                            </div>
                            <div id="otros_motivo_container" class="mt-3 hidden">
                                <label for="observaciones" class="form-label">Especifique el motivo:</label>
                                <textarea class="form-control" id="observaciones" name="observaciones" rows="3"></textarea>
                            </div>

                            <div id="file_upload_container" class="mt-3 hidden">
                                <label for="archivo_adjunto" class="file-upload-label">
                                    <i class="bi bi-cloud-arrow-up"></i>
                                    Subir archivo adjunto (opcional, máx. 1000KB, JPG, PNG, GIF, PDF)
                                </label>
                                <input type="file" class="form-control hidden" id="archivo_adjunto" name="archivo_adjunto" accept="image/jpeg,image/png,image/gif,application/pdf">
                                <div id="file_name_display" class="file-name"></div>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Fecha de Inicio</label>
                                <input type="date" name="fecha_inicio" id="fecha_inicio"
                                       class="form-control form-control-lg" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Fecha de Fin</label>
                                <input type="date" name="fecha_fin" id="fecha_fin"
                                       class="form-control form-control-lg" required readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Días de Reposo</label>
                                <input type="number" name="dias_otorgados" id="dias_otorgados"
                                       class="form-control form-control-lg" readonly required>
                            </div>
                        </div>

                        <button type="submit" name="registrar_reposo"
                                class="btn btn-primary btn-lg w-100 py-3">
                            <i class="bi bi-send-fill me-2"></i>Solicitar Reposo
                        </button>
                    <?php endif; ?>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const obligatoriaRadio = document.getElementById('obligatoria');
            const potestativaRadio = document.getElementById('potestativa');
            const obligatoriasOptionsDiv = document.getElementById('obligatorias_options');
            const potestativasOptionsDiv = document.getElementById('potestativas_options');
            const otrosMotivoContainer = document.getElementById('otros_motivo_container');
            const archivoAdjuntoInput = document.getElementById('archivo_adjunto');
            const fileUploadContainer = document.getElementById('file_upload_container');
            const fileNameDisplay = document.getElementById('file_name_display');
            const fileUploadLabel = document.querySelector('.file-upload-label');
            const fechaInicioInput = document.getElementById('fecha_inicio');
            const fechaFinInput = document.getElementById('fecha_fin');
            const diasOtorgadosInput = document.getElementById('dias_otorgados');
            const tipoPersonalInput = document.getElementById('tipo_personal');

            function calcularFechaFinCalendario(fechaInicioStr, dias) {
                let fecha = new Date(fechaInicioStr + 'T00:00:00');
                fecha.setDate(fecha.getDate() + (dias - 1));
                return fecha.toISOString().split('T')[0];
            }
            function obtenerDiasCalendarioEntreFechas(fechaInicioStr, fechaFinStr) {
                let fechaInicio = new Date(fechaInicioStr + 'T00:00:00');
                let fechaFin = new Date(fechaFinStr + 'T00:00:00');
                if (fechaInicio > fechaFin) return 0;
                return Math.floor((fechaFin - fechaInicio) / (1000 * 60 * 60 * 24)) + 1;
            }
            function updateOptionBoxes() {
                document.querySelectorAll('.option-box').forEach(box => {
                    const radio = box.querySelector('input[type="radio"]');
                    if (radio && radio.checked) {
                        box.classList.add('selected');
                    } else {
                        box.classList.remove('selected');
                    }
                });
            }
            if (obligatoriaRadio) obligatoriaRadio.addEventListener('change', () => {
                obligatoriasOptionsDiv.classList.remove('hidden');
                potestativasOptionsDiv.classList.add('hidden');
                fileUploadContainer.classList.add('hidden');
                otrosMotivoContainer.classList.add('hidden');
                archivoAdjuntoInput.value = '';
                fileNameDisplay.textContent = '';
                document.querySelectorAll('#potestativas_options input[type="radio"]').forEach(r => r.checked = false);
                fechaFinInput.readOnly = true;
                diasOtorgadosInput.readOnly = true;
                updateOptionBoxes();
            });
            if (potestativaRadio) potestativaRadio.addEventListener('change', () => {
                potestativasOptionsDiv.classList.remove('hidden');
                obligatoriasOptionsDiv.classList.add('hidden');
                fileUploadContainer.classList.remove('hidden');
                document.querySelectorAll('#obligatorias_options input[type="radio"]').forEach(r => r.checked = false);
                fechaFinInput.readOnly = false;
                diasOtorgadosInput.readOnly = false;
                updateOptionBoxes();
            });
            document.querySelectorAll('.option-box').forEach(box => {
                box.addEventListener('click', () => {
                    const radio = box.querySelector('input[type="radio"]');
                    if (radio) {
                        radio.checked = true;
                        radio.dispatchEvent(new Event('change'));
                    }
                });
            });
            document.querySelectorAll('input[name="motivo_reposo"]').forEach(radio => {
                radio.addEventListener('change', () => {
                    if (radio.id === 'otros') {
                        otrosMotivoContainer.classList.remove('hidden');
                        document.getElementById('observaciones').setAttribute('required', 'required');
                    } else {
                        otrosMotivoContainer.classList.add('hidden');
                        document.getElementById('observaciones').removeAttribute('required');
                        document.getElementById('observaciones').value = '';
                    }
                    updateOptionBoxes();
                    if (obligatoriaRadio && obligatoriaRadio.checked) {
                        fechaFinInput.readOnly = true;
                        diasOtorgadosInput.readOnly = true;
                        let dias = 0;
                        const tipoPersonal = tipoPersonalInput.value.toLowerCase();
                        switch(radio.id) {
                            case 'fallecimiento':
                                dias = tipoPersonal.includes('obrero') ? 10 : 6;
                                break;
                            case 'matrimonio':
                                dias = tipoPersonal.includes('obrero') ? 15 : 5;
                                break;
                            case 'paternidad':
                                dias = 14;
                                break;
                            default:
                                dias = 0;
                                break;
                        }
                        diasOtorgadosInput.value = dias;
                        if (fechaInicioInput.value && dias > 0) {
                            fechaFinInput.value = calcularFechaFinCalendario(fechaInicioInput.value, dias);
                        } else {
                            fechaFinInput.value = '';
                        }
                    } else {
                        fechaFinInput.readOnly = false;
                        diasOtorgadosInput.readOnly = false;
                    }
                });
            });
            if (fechaInicioInput) fechaInicioInput.addEventListener('change', () => {
                const tipoPersonal = tipoPersonalInput.value.toLowerCase();
                const selectedMotivo = document.querySelector('input[name="motivo_reposo"]:checked');
                const isManualOption = selectedMotivo && ['dirigencia_sindical', 'comparecencia_autoridades', 'eventos_deportivos'].includes(selectedMotivo.id);
                if (obligatoriaRadio && obligatoriaRadio.checked && fechaInicioInput.value && !isManualOption) {
                    const dias = parseInt(diasOtorgadosInput.value);
                    if (dias > 0) fechaFinInput.value = calcularFechaFinCalendario(fechaInicioInput.value, dias);
                    else fechaFinInput.value = '';
                } else if (potestativaRadio && potestativaRadio.checked && fechaInicioInput.value && fechaFinInput.value) {
                    diasOtorgadosInput.value = obtenerDiasCalendarioEntreFechas(fechaInicioInput.value, fechaFinInput.value);
                }
            });
            if (fechaFinInput) fechaFinInput.addEventListener('change', () => {
                const selectedMotivo = document.querySelector('input[name="motivo_reposo"]:checked');
                const isManualOption = selectedMotivo && ['dirigencia_sindical', 'comparecencia_autoridades', 'eventos_deportivos'].includes(selectedMotivo.id);
                if ((potestativaRadio && potestativaRadio.checked) || (obligatoriaRadio && obligatoriaRadio.checked && isManualOption)) {
                    if (fechaInicioInput.value && fechaFinInput.value) {
                        diasOtorgadosInput.value = obtenerDiasCalendarioEntreFechas(fechaInicioInput.value, fechaFinInput.value);
                    }
                }
            });
            if (diasOtorgadosInput) diasOtorgadosInput.addEventListener('change', () => {
                const tipoPersonal = tipoPersonalInput.value.toLowerCase();
                const dias = parseInt(diasOtorgadosInput.value);
                const fechaInicio = fechaInicioInput.value;
                const selectedMotivo = document.querySelector('input[name="motivo_reposo"]:checked');
                const isManualOption = selectedMotivo && ['dirigencia_sindical', 'comparecencia_autoridades', 'eventos_deportivos'].includes(selectedMotivo.id);
                if ((potestativaRadio && potestativaRadio.checked) || (obligatoriaRadio && obligatoriaRadio.checked && isManualOption)) {
                    if (fechaInicio && dias > 0) {
                        fechaFinInput.value = calcularFechaFinCalendario(fechaInicio, dias);
                    } else if (dias <= 0) {
                        fechaFinInput.value = '';
                    }
                }
            });
            if (fileUploadLabel && archivoAdjuntoInput) {
                fileUploadLabel.addEventListener('click', () => archivoAdjuntoInput.click());
                archivoAdjuntoInput.addEventListener('change', () => {
                    if (archivoAdjuntoInput.files.length > 0) {
                        fileNameDisplay.textContent = `Archivo seleccionado: ${archivoAdjuntoInput.files[0].name}`;
                    } else {
                        fileNameDisplay.textContent = '';
                    }
                });
            }
            <?php if ($datos_empleado): ?>
                updateOptionBoxes();
            <?php endif; ?>
        });
    </script>
</body>
</html>