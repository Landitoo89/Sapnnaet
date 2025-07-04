<?php
session_start();
require '../conexion.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$errores = [];
$datos_empleado = null;
$periodos = [];
$todos_los_periodos = [];
$coincidencias = [];
$dias_habiles_mensaje = "";

// Obtener la fecha actual del sistema para límites
$fecha_actual_iso = date('Y-m-d'); // Formato YYYY-MM-DD para input date

// --- Lista de feriados nacionales (puedes completarla o externalizarla a una tabla) ---
$feriados_nacionales = [
    '2025-01-01', '2025-02-24', '2025-02-25', '2025-03-19',
    '2025-04-17', '2025-04-18', '2025-05-01', '2025-06-24',
    '2025-07-05', '2025-07-24', '2025-10-12', '2025-12-24',
    '2025-12-25', '2025-12-31'
];

// Función para obtener el primer día hábil igual o posterior a la fecha dada
function primerDiaHabil($fecha_str, $feriados) {
    $fecha = new DateTime($fecha_str);
    while ($fecha->format('N') >= 6 || in_array($fecha->format('Y-m-d'), $feriados)) {
        $fecha->modify('+1 day');
    }
    return $fecha->format('Y-m-d');
}

// Función para sumar días hábiles a una fecha (incluye el primer día hábil)
function sumarDiasHabilesPhp($fecha_inicio, $dias_habiles, $feriados) {
    $fecha = new DateTime(primerDiaHabil($fecha_inicio, $feriados));
    $sumados = 0;
    while ($sumados < $dias_habiles) {
        $yyyy_mm_dd = $fecha->format('Y-m-d');
        if ($fecha->format('N') <= 5 && !in_array($yyyy_mm_dd, $feriados)) {
            $sumados++;
        }
        if ($sumados < $dias_habiles) {
            $fecha->modify('+1 day');
        }
    }
    return $fecha->format('Y-m-d');
}

// Función para contar días hábiles entre dos fechas (incluye inicio y fin)
function contarDiasHabiles($fecha_inicio, $fecha_fin, $feriados = []) {
    $inicio = new DateTime($fecha_inicio);
    $fin = new DateTime($fecha_fin);
    $dias_habiles = 0;
    $intervalo = new DateInterval('P1D');
    for ($fecha = clone $inicio; $fecha <= $fin; $fecha->add($intervalo)) {
        $es_finde = $fecha->format('N') >= 6;
        $es_feriado = in_array($fecha->format('Y-m-d'), $feriados);
        if (!$es_finde && !$es_feriado) {
            $dias_habiles++;
        }
    }
    return $dias_habiles;
}

// Buscador por cédula/nombre/apellido
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buscar_empleado'])) {
    $busqueda = trim($_POST['busqueda']);
    if ($busqueda !== '') {
        $sql = "
            SELECT p.id_pers, p.nombres, p.apellidos, p.cedula_identidad,
                   dl.fecha_ingreso, dl.estado,
                   dl.ha_trabajado_anteriormente, dl.ano_ingreso_anterior, dl.ano_culminacion_anterior,
                   dl.nombre_empresa_anterior,
                   d.nombre AS departamento, c.nombre AS cargo
            FROM datos_personales p
            INNER JOIN (
                SELECT id_pers, MAX(id_laboral) as max_id_laboral
                FROM datos_laborales
                GROUP BY id_pers
            ) AS latest_dl_id ON p.id_pers = latest_dl_id.id_pers
            INNER JOIN datos_laborales dl ON latest_dl_id.max_id_laboral = dl.id_laboral
            LEFT JOIN departamentos d ON dl.id_departamento = d.id_departamento
            LEFT JOIN cargos c ON dl.id_cargo = c.id_cargo
            WHERE p.cedula_identidad LIKE :q
               OR p.nombres LIKE :q
               OR p.apellidos LIKE :q
               OR CONCAT(p.nombres, ' ', p.apellidos) LIKE :q
            ORDER BY p.nombres, p.apellidos
            LIMIT 10
        ";
        $stmt = $conn->prepare($sql);
        $stmt->execute([":q"=>"%$busqueda%"]);
        $coincidencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!$coincidencias) {
            $errores[] = "No se encontraron coincidencias.";
        }
    } else {
        $errores[] = "Debe ingresar un dato de búsqueda.";
    }
}

// Selección del empleado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['seleccionar_empleado'])) {
    $id_pers = intval($_POST['id_pers']);
    $stmt = $conn->prepare("
        SELECT p.id_pers, p.nombres, p.apellidos, p.cedula_identidad,
               dl.fecha_ingreso, dl.estado,
               dl.ha_trabajado_anteriormente, dl.ano_ingreso_anterior, dl.ano_culminacion_anterior,
               dl.nombre_empresa_anterior,
               d.nombre AS departamento, c.nombre AS cargo
        FROM datos_personales p
        INNER JOIN datos_laborales dl ON p.id_pers = dl.id_pers
        LEFT JOIN departamentos d ON dl.id_departamento = d.id_departamento
        LEFT JOIN cargos c ON dl.id_cargo = c.id_cargo
        WHERE p.id_pers = ?
        ORDER BY dl.fecha_ingreso DESC
        LIMIT 1
    ");
    $stmt->execute([$id_pers]);
    $datos_empleado = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($datos_empleado && $datos_empleado['estado'] == 'vacaciones') {
        $errores[] = "El empleado ya está en período de vacaciones";
    } elseif ($datos_empleado && $datos_empleado['estado'] == 'reposo') {
        $errores[] = "El empleado se encuentra actualmente en período de reposo y no puede tomar vacaciones.";
    }

    // Llamada al procedimiento solo si hay datos correctos y sin errores
    if ($datos_empleado && empty($errores)) {
        $ano_ingreso_anterior_param = (isset($datos_empleado['ha_trabajado_anteriormente']) && (strtolower(trim($datos_empleado['ha_trabajado_anteriormente'])) === 'sí' || strtolower(trim($datos_empleado['ha_trabajado_anteriormente'])) === 'si') && !empty($datos_empleado['ano_ingreso_anterior'])) ? $datos_empleado['ano_ingreso_anterior'] : null;
        $ano_culminacion_anterior_param = (isset($datos_empleado['ha_trabajado_anteriormente']) && (strtolower(trim($datos_empleado['ha_trabajado_anteriormente'])) === 'sí' || strtolower(trim($datos_empleado['ha_trabajado_anteriormente'])) === 'si') && !empty($datos_empleado['ano_culminacion_anterior'])) ? $datos_empleado['ano_culminacion_anterior'] : null;
        $nombre_empresa_anterior_param = (isset($datos_empleado['ha_trabajado_anteriormente']) && (strtolower(trim($datos_empleado['ha_trabajado_anteriormente'])) === 'sí' || strtolower(trim($datos_empleado['ha_trabajado_anteriormente'])) === 'si') && !empty($datos_empleado['nombre_empresa_anterior'])) ? $datos_empleado['nombre_empresa_anterior'] : null;

        $params = [
            $datos_empleado['id_pers'],
            $datos_empleado['fecha_ingreso'],
            $ano_ingreso_anterior_param,
            $ano_culminacion_anterior_param,
            $nombre_empresa_anterior_param
        ];

        try {
            $stmt_proc = $conn->prepare("CALL generar_periodos_vacaciones(?, ?, ?, ?, ?)");
            $stmt_proc->execute($params);
            do { $stmt_proc->fetch(); } while ($stmt_proc->nextRowset());
        } catch (PDOException $e) {
            $errores[] = "Error al ejecutar el procedimiento `generar_periodos_vacaciones`: " . $e->getMessage();
            error_log("Error en generar_periodos_vacaciones: " . $e->getMessage() . " - SQLSTATE: " . $e->getCode());
        }

        // Obtener periodos activos y todos los periodos
        $stmt_periodos = $conn->prepare("
            SELECT id_periodo, fecha_inicio_periodo, fecha_fin_periodo,
                   dias_asignados, dias_usados, estado, institucion,
                   CONCAT(YEAR(fecha_inicio_periodo), '-', YEAR(fecha_fin_periodo)) AS nombre_periodo
            FROM periodos_vacaciones
            WHERE id_pers = ?
            ORDER BY fecha_inicio_periodo ASC
        ");
        $stmt_periodos->execute([$datos_empleado['id_pers']]);
        $todos_los_periodos = $stmt_periodos->fetchAll(PDO::FETCH_ASSOC);

        $periodos = array_filter($todos_los_periodos, function($p) {
            return $p['estado'] === 'activo' && ($p['dias_asignados'] - $p['dias_usados']) > 0;
        });
    }
}

// Procesar registro de vacaciones
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar_vacacion'])) {
    $id_pers = $_POST['id_pers'];
    $fecha_inicio_str = $_POST['fecha_inicio'];
    $periodos_seleccionados_ids = $_POST['periodos'] ?? [];

    // --- Validaciones en el backend ---
    if (empty($periodos_seleccionados_ids)) {
        $errores[] = "Debe seleccionar al menos un período de vacaciones.";
    }

    // Validar que la fecha de inicio no sea futura
    $fecha_inicio_dt = new DateTime($fecha_inicio_str);
    $fecha_actual_dt = new DateTime(date('Y-m-d')); // Fecha actual para comparación

    if ($fecha_inicio_dt > $fecha_actual_dt) {
        $errores[] = "La fecha de salida no puede ser una fecha futura a la fecha actual (" . date('d/m/Y') . ").";
    }

    $dias_disponibles_seleccionados = 0;
    if (!empty($periodos_seleccionados_ids)) {
        $placeholders = implode(',', array_fill(0, count($periodos_seleccionados_ids), '?'));
        $stmt_dias = $conn->prepare("SELECT SUM(dias_asignados - dias_usados) AS total_dias FROM periodos_vacaciones WHERE id_periodo IN ($placeholders)");
        $stmt_dias->execute($periodos_seleccionados_ids);
        $dias_disponibles_seleccionados = $stmt_dias->fetch(PDO::FETCH_ASSOC)['total_dias'];
    }

    // AJUSTE: Calcular el primer día hábil para la fecha de inicio
    $fecha_inicio_habil = primerDiaHabil($fecha_inicio_str, $feriados_nacionales);
    // Calcular la fecha de fin real sumando los días hábiles
    $fecha_fin_str = sumarDiasHabilesPhp($fecha_inicio_str, $dias_disponibles_seleccionados, $feriados_nacionales);

    // Nuevo cálculo: días hábiles solicitados
    $dias_solicitados = contarDiasHabiles($fecha_inicio_habil, $fecha_fin_str, $feriados_nacionales);

    // Mostrar mensaje de días hábiles para informar al usuario (si hay selección)
    $dias_habiles_mensaje = "";
    if (!empty($periodos_seleccionados_ids) && $fecha_inicio_str && $fecha_fin_str) {
        $dias_habiles_mensaje = "El rango seleccionado tiene $dias_solicitados días hábiles. Los días disponibles de los períodos seleccionados son $dias_disponibles_seleccionados.";
    }

    // Validación: Deben coincidir los días hábiles y los disponibles
    if ($dias_solicitados !== $dias_disponibles_seleccionados && empty($errores)) {
        $errores[] = "Advertencia: Los días hábiles solicitados ($dias_solicitados) no coinciden con los días disponibles de los períodos seleccionados ($dias_disponibles_seleccionados).";
    }

    if (count($periodos_seleccionados_ids) > 1 && empty($errores)) {
        $fechas_periodos = [];
        $placeholders = implode(',', array_fill(0, count($periodos_seleccionados_ids), '?'));
        $stmt_fechas = $conn->prepare("
            SELECT id_periodo, fecha_inicio_periodo, fecha_fin_periodo
            FROM periodos_vacaciones
            WHERE id_periodo IN ($placeholders)
            ORDER BY fecha_inicio_periodo ASC
        ");
        $stmt_fechas->execute($periodos_seleccionados_ids);
        $fechas_periodos_raw = $stmt_fechas->fetchAll(PDO::FETCH_ASSOC);

        usort($fechas_periodos_raw, function($a, $b) {
            return strtotime($a['fecha_inicio_periodo']) - strtotime($b['fecha_inicio_periodo']);
        });

        for ($i = 0; $i < count($fechas_periodos_raw) - 1; $i++) {
            $fin_actual = new DateTime($fechas_periodos_raw[$i]['fecha_fin_periodo']);
            $inicio_siguiente = new DateTime($fechas_periodos_raw[$i+1]['fecha_inicio_periodo']);
            $fin_siguiente_dia = (clone $fin_actual)->modify('+1 day');

            if ($fin_siguiente_dia->format('Y-m-d') !== $inicio_siguiente->format('Y-m-d')) {
                $errores[] = "Los períodos seleccionados deben ser consecutivos y sin espacios intermedios.";
                break;
            }
        }
    }

    if (empty($errores) || (count($errores) === 1 && strpos($errores[0], 'Advertencia:') === 0)) {
        // Permitir continuar si solo hay advertencia
        try {
            $conn->beginTransaction();

            $stmt = $conn->prepare("
                INSERT INTO vacaciones (id_pers, fecha_inicio, fecha_fin)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$id_pers, $fecha_inicio_habil, $fecha_fin_str]);
            $id_vacacion_generada = $conn->lastInsertId();

            foreach ($periodos_seleccionados_ids as $id_periodo) {
                $stmt = $conn->prepare("
                    UPDATE periodos_vacaciones
                    SET dias_usados = dias_asignados,
                        estado = 'usado'
                    WHERE id_periodo = ?
                ");
                $stmt->execute([$id_periodo]);
            }

            $stmt = $conn->prepare("
                UPDATE datos_laborales
                SET estado = 'vacaciones'
                WHERE id_pers = ?
            ");
            $stmt->execute([$id_pers]);

            $conn->commit();

            $_SESSION['mensaje'] = [
                'titulo' => '¡Éxito!',
                'contenido' => 'Vacación registrada exitosamente. Generando reporte...',
                'tipo' => 'success'
            ];

            $periodos_ids_str_for_url = implode(',', $periodos_seleccionados_ids);
            header('Location: vacacion-registrada-exito.php?id_vacacion=' . $id_vacacion_generada . '&periodos_ids=' . urlencode($periodos_ids_str_for_url));
            exit;

        } catch (PDOException $e) {
            $conn->rollBack();
            $errores[] = "Error al registrar: " . $e->getMessage();
        }
    }
}
require $_SERVER['DOCUMENT_ROOT']."/proyecto/inicio/sidebar.php";

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Vacaciones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="../css/styles.css" rel="stylesheet">
    <style>
        .vacation-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            padding: 2rem;
        }
        .periodo-disponible-card {
            border: 2px solid #4e73df;
            border-radius: 12px;
            background: linear-gradient(90deg, #f7faff 70%, #eaf1fc 100%);
            margin-bottom: 1rem;
            transition: box-shadow 0.2s;
            box-shadow: 0 0 0 rgba(0,0,0,0);
        }
        .periodo-disponible-card.selected {
            box-shadow: 0 4px 16px rgba(78,115,223,0.2);
            border-color: #224abe;
            background: linear-gradient(90deg, #e8eefd 70%, #cddcfa 100%);
        }
        .periodo-titulo {
            font-size: 1.2rem;
            font-weight: 600;
            color: #224abe;
        }
        .periodo-label {
            font-weight: 500;
            color: #6c757d;
        }
        .periodo-table thead {
            background: #224abe;
            color: #fff;
        }
        .periodo-table tbody tr:not(.table-secondary):hover {
            background: #f7faff;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="vacation-card mx-auto" style="max-width: 900px;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="text-primary">
                    <i class="bi bi-calendar2-range me-2"></i>
                    Registrar Vacaciones
                </h2>
                <a href="gestion-vacaciones.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Volver
                </a>
            </div>

            <?php if(!empty($errores)): ?>
                <div class="alert <?= (count($errores) === 1 && strpos($errores[0], 'Advertencia:') === 0) ? 'alert-warning' : 'alert-danger' ?> mb-4">
                    <?php foreach ($errores as $error): ?>
                        <div><?= htmlspecialchars($error) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if($dias_habiles_mensaje): ?>
                <div class="alert alert-info mb-4">
                    <?= htmlspecialchars($dias_habiles_mensaje) ?>
                </div>
            <?php endif; ?>

            <!-- Buscador -->
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
            <?php if (!empty($coincidencias)): ?>
                <div class="table-responsive mb-4">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Seleccionar</th>
                                <th>Cédula</th>
                                <th>Nombre</th>
                                <th>Apellido</th>
                                <th>Departamento</th>
                                <th>Cargo</th>
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
                                <td><?= htmlspecialchars($emp['departamento']) ?></td>
                                <td><?= htmlspecialchars($emp['cargo']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <form method="POST">
            <?php if($datos_empleado): ?>
                <div class="bg-light p-4 rounded-3 mb-4">
                    <input type="hidden" name="id_pers" value="<?= $datos_empleado['id_pers'] ?>">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label>Nombre Completo</label>
                            <input type="text" class="form-control"
                                   value="<?= htmlspecialchars($datos_empleado['nombres'] . ' ' . $datos_empleado['apellidos']) ?>"
                                   readonly>
                        </div>
                        <div class="col-md-6">
                            <label>Cédula de Identidad</label>
                            <input type="text" class="form-control"
                                   value="<?= htmlspecialchars($datos_empleado['cedula_identidad']) ?>"
                                   readonly>
                        </div>
                        <div class="col-md-6">
                            <label>Fecha de Ingreso</label>
                            <input type="text" class="form-control"
                                   value="<?= date('d/m/Y', strtotime($datos_empleado['fecha_ingreso'])) ?>"
                                   readonly>
                        </div>
                        <div class="col-md-6">
                            <label>Departamento</label>
                            <input type="text" class="form-control"
                                   value="<?= htmlspecialchars($datos_empleado['departamento']) ?>"
                                   readonly>
                        </div>
                        <div class="col-md-6">
                            <label>Cargo</label>
                            <input type="text" class="form-control"
                                   value="<?= htmlspecialchars($datos_empleado['cargo']) ?>"
                                   readonly>
                        </div>
                        <div class="col-md-6">
                            <label>Estado Actual</label>
                            <input type="text" class="form-control"
                                   value="<?= htmlspecialchars(ucfirst($datos_empleado['estado'])) ?>"
                                   readonly>
                        </div>
                    </div>
                </div>

                <?php if(!empty($periodos)): ?>
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="fw-bold mb-0">
                                <i class="bi bi-calendar2-event me-2"></i>
                                Períodos Disponibles
                            </h5>
                            <button type="button" class="btn btn-outline-dark btn-sm" data-bs-toggle="modal" data-bs-target="#todosPeriodosModal">
                                <i class="bi bi-list-ul"></i> Ver todos los períodos
                            </button>
                        </div>
                        <div class="row">
                            <?php foreach ($periodos as $periodo): ?>
                                <div class="col-12">
                                    <label class="periodo-disponible-card d-flex align-items-center p-3 mb-2">
                                        <input class="form-check-input me-3 periodo-check"
                                            type="checkbox"
                                            name="periodos[]"
                                            value="<?= $periodo['id_periodo'] ?>"
                                            data-dias="<?= htmlspecialchars($periodo['dias_asignados'] - $periodo['dias_usados']) ?>">
                                        <div class="w-100">
                                            <div class="d-flex justify-content-between">
                                                <span class="periodo-titulo">
                                                    <?= htmlspecialchars($periodo['nombre_periodo']) ?>
                                                    <span class="badge bg-primary ms-1"><?= htmlspecialchars($periodo['institucion']) ?></span>
                                                </span>
                                                <span class="badge bg-success fs-6">
                                                    <?= ($periodo['dias_asignados'] - $periodo['dias_usados']) ?> días disponibles
                                                </span>
                                            </div>
                                            <div class="mt-2 row">
                                                <div class="col-md-3 periodo-label">
                                                    <i class="bi bi-calendar-date me-1"></i>
                                                    <?= date('d/m/Y', strtotime($periodo['fecha_inicio_periodo'])) ?>
                                                </div>
                                                <div class="col-md-3 periodo-label">
                                                    <i class="bi bi-calendar2-check me-1"></i>
                                                    <?= date('d/m/Y', strtotime($periodo['fecha_fin_periodo'])) ?>
                                                </div>
                                                <div class="col-md-3 periodo-label">
                                                    <i class="bi bi-award me-1"></i>
                                                    Días asignados: <strong><?= htmlspecialchars($periodo['dias_asignados']) ?></strong>
                                                </div>
                                                <div class="col-md-3 periodo-label">
                                                    <i class="bi bi-person-check-fill me-1"></i>
                                                    Estado: <strong><?= ucfirst($periodo['estado']) ?></strong>
                                                </div>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="fw-bold">Fecha de Salida</label>
                            <input type="date" name="fecha_inicio"
                                   class="form-control form-control-lg"
                                   value="<?= $fecha_actual_iso ?>"
                                   max="<?= $fecha_actual_iso ?>"
                                   required>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-bold">Fecha de Reintegro (automático)</label>
                            <input type="date" name="fecha_fin"
                                   id="fecha_fin"
                                   class="form-control form-control-lg"
                                   readonly required>
                        </div>
                    </div>

                    <div id="alertaDiasHabiles" class="alert alert-info mb-3" style="display:none;"></div>

                    <button type="submit" name="registrar_vacacion"
                            class="btn btn-primary btn-lg w-100 py-3">
                        <i class="bi bi-save2-fill me-2"></i>Registrar Vacación
                    </button>
                <?php else: ?>
                    <div class="alert alert-warning">No hay períodos disponibles para este empleado</div>
                <?php endif; ?>
            <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Modal todos los periodos (igual que antes) -->
    <div class="modal fade" id="todosPeriodosModal" tabindex="-1" aria-labelledby="todosPeriodosLabel" aria-hidden="true">
      <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header bg-primary text-white">
            <h5 class="modal-title" id="todosPeriodosLabel">
                <i class="bi bi-list-ul"></i> Todos los períodos del empleado
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body">
            <div class="table-responsive">
                <table class="table table-striped table-bordered periodo-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Institución</th>
                            <th>Periodo</th>
                            <th>Inicio</th>
                            <th>Fin</th>
                            <th>Días asignados</th>
                            <th>Días usados</th>
                            <th>Disponibles</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($todos_los_periodos as $idx => $p): ?>
                        <tr class="<?= $p['estado'] == 'activo' ? '' : 'table-secondary' ?>">
                            <td><?= $idx + 1 ?></td>
                            <td><?= htmlspecialchars($p['institucion']) ?></td>
                            <td><?= htmlspecialchars($p['nombre_periodo']) ?></td>
                            <td><?= date('d/m/Y', strtotime($p['fecha_inicio_periodo'])) ?></td>
                            <td><?= date('d/m/Y', strtotime($p['fecha_fin_periodo'])) ?></td>
                            <td><?= $p['dias_asignados'] ?></td>
                            <td><?= $p['dias_usados'] ?></td>
                            <td><?= $p['dias_asignados'] - $p['dias_usados'] ?></td>
                            <td>
                                <span class="badge <?= $p['estado']=='activo' ? 'bg-success' : 'bg-secondary' ?>">
                                    <?= ucfirst($p['estado']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
             </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
          </div>
        </div>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Lista de feriados nacionales en Venezuela (ejemplo, puedes ampliar)
        const feriados = [
            '2025-01-01', '2025-02-24', '2025-02-25', '2025-03-19',
            '2025-04-17', '2025-04-18', '2025-05-01', '2025-06-24',
            '2025-07-05', '2025-07-24', '2025-10-12', '2025-12-24',
            '2025-12-25', '2025-12-31'
        ];

        function esFeriado(fecha) {
            return feriados.includes(fecha);
        }

        // JS: Mueve la fecha al primer día hábil igual o posterior a la fecha dada
        function primerDiaHabil(fechaStr) {
            let fecha = new Date(fechaStr);
            let yyyy_mm_dd = fecha.toISOString().split('T')[0];
            while (fecha.getDay() === 0 || fecha.getDay() === 6 || esFeriado(yyyy_mm_dd)) {
                fecha.setDate(fecha.getDate() + 1);
                yyyy_mm_dd = fecha.toISOString().split('T')[0];
            }
            return fecha;
        }

        // JS: Suma días hábiles a una fecha (incluye el primer día hábil)
        function sumarDiasHabiles(fechaInicioStr, diasHabiles) {
            let fecha = primerDiaHabil(fechaInicioStr);
            let sumados = 0;
            while (sumados < diasHabiles) {
                const yyyy_mm_dd = fecha.toISOString().split('T')[0];
                if (fecha.getDay() !== 0 && fecha.getDay() !== 6 && !esFeriado(yyyy_mm_dd)) {
                    sumados++;
                }
                if (sumados < diasHabiles) {
                    fecha.setDate(fecha.getDate() + 1);
                }
            }
            return fecha;
        }

        // Calcula días hábiles entre dos fechas (incluye ambos extremos)
        function contarDiasHabilesFrontend(fechaInicioStr, fechaFinStr) {
            let inicio = primerDiaHabil(fechaInicioStr);
            let fin = new Date(fechaFinStr);
            let diasHabiles = 0;
            while (inicio <= fin) {
                const yyyy_mm_dd = inicio.toISOString().split('T')[0];
                if (inicio.getDay() !== 0 && inicio.getDay() !== 6 && !esFeriado(yyyy_mm_dd)) {
                    diasHabiles++;
                }
                inicio.setDate(inicio.getDate() + 1);
            }
            return diasHabiles;
        }

        const fechaInicioInput = document.querySelector('input[name="fecha_inicio"]');
        const fechaFinInput = document.getElementById('fecha_fin');
        const alertaDiasHabiles = document.getElementById('alertaDiasHabiles');

        document.querySelectorAll('.periodo-check').forEach(cb => {
            cb.addEventListener('change', function() {
                const card = this.closest('.periodo-disponible-card');
                if (this.checked) {
                    card.classList.add('selected');
                } else {
                    card.classList.remove('selected');
                }
                calcularFechas();
            });
        });

        fechaInicioInput.addEventListener('change', calcularFechas);

        function calcularFechas() {
            const checkboxes = Array.from(document.querySelectorAll('.periodo-check:checked'));
            let totalDias = 0;

            checkboxes.forEach(cb => {
                totalDias += parseInt(cb.dataset.dias);
            });

            if (totalDias > 0 && fechaInicioInput.value) {
                let fechaFin = sumarDiasHabiles(fechaInicioInput.value, totalDias);
                const fechaFinStr = fechaFin.toISOString().split('T')[0];
                fechaFinInput.value = fechaFinStr;

                // Mostrar alerta de días hábiles realmente calculados
                const primerHabil = primerDiaHabil(fechaInicioInput.value);
                const fechaInicioMostrar = primerHabil.toISOString().split('T')[0];
                const diasHabilesCalculados = contarDiasHabilesFrontend(fechaInicioMostrar, fechaFinStr);

                if(alertaDiasHabiles) {
                    alertaDiasHabiles.textContent = `El rango seleccionado tiene ${diasHabilesCalculados} días hábiles. Los días disponibles de los períodos seleccionados son ${totalDias}.`;
                    alertaDiasHabiles.style.display = 'block';
                    if (diasHabilesCalculados !== totalDias) {
                        alertaDiasHabiles.className = 'alert alert-warning mb-3';
                    } else {
                        alertaDiasHabiles.className = 'alert alert-info mb-3';
                    }
                }
            } else {
                fechaFinInput.value = '';
                if(alertaDiasHabiles) alertaDiasHabiles.style.display = 'none';
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            if (fechaInicioInput.value) {
                calcularFechas();
            }
        });
    </script>
</body>
</html>