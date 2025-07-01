<?php
session_start();
require '../conexion.php';
require $_SERVER['DOCUMENT_ROOT']."/proyecto/inicio/sidebar.php";

$errores = [];
$datos_empleado = null;
$periodos_empleado = [];
$coincidencias = [];

// Buscador avanzado por cédula, nombre o apellido
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buscar_empleado'])) {
    $busqueda = trim($_POST['busqueda']);
    if ($busqueda !== '') {
        $sql = "
            SELECT p.id_pers, p.nombres, p.apellidos, p.cedula_identidad,
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

// Selección del empleado desde coincidencias
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['seleccionar_empleado'])) {
    $_POST['cedula'] = $_POST['cedula_identidad'];
    $_POST['buscar_cedula'] = true;
}

// Procesar búsqueda de empleado (por cédula)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buscar_cedula'])) {
    $cedula = trim($_POST['cedula']);
    if (!is_numeric($cedula)) {
        $errores[] = "La cédula debe contener solo números.";
    } else {
        try {
            $stmt = $conn->prepare("
                SELECT p.id_pers, p.nombres, p.apellidos, p.cedula_identidad AS cedula, 
                       dl.fecha_ingreso, dl.estado,
                       d.nombre AS departamento, c.nombre AS cargo
                FROM datos_personales p
                INNER JOIN datos_laborales dl ON p.id_pers = dl.id_pers
                LEFT JOIN departamentos d ON dl.id_departamento = d.id_departamento
                LEFT JOIN cargos c ON dl.id_cargo = c.id_cargo
                WHERE p.cedula_identidad = ?
            ");
            $stmt->execute([$cedula]);
            $datos_empleado = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$datos_empleado) {
                $errores[] = "No se encontró empleado con esta cédula.";
            } else {
                $stmt_periodos = $conn->prepare("
                    SELECT id_periodo, fecha_inicio_periodo, fecha_fin_periodo, 
                           dias_asignados, dias_usados, estado, institucion
                    FROM periodos_vacaciones 
                    WHERE id_pers = ?
                    ORDER BY fecha_inicio_periodo DESC
                ");
                $stmt_periodos->execute([$datos_empleado['id_pers']]);
                $periodos_empleado = $stmt_periodos->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {
            $errores[] = "Error de base de datos al buscar empleado: " . $e->getMessage();
        }
    }
}

// Procesar edición de período (sin cambios)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_periodo'])) {
    $id_periodo = $_POST['id_periodo_edit'];
    $nuevo_estado = $_POST['estado_periodo_edit'];
    $dias_asignados_edit = $_POST['dias_asignados_edit'];
    $dias_usados_edit_input = $_POST['dias_usados_edit'];
    $id_pers_edit = $_POST['id_pers_edit']; 
    $cedula_busqueda_hidden = $_POST['cedula_busqueda_hidden'];

    $dias_usados_to_save = 0;
    if ($nuevo_estado === 'usado') {
        $dias_usados_to_save = $dias_asignados_edit;
    } else {
        $dias_usados_to_save = 0;
    }

    try {
        $conn->beginTransaction();
        $stmt_update_periodo = $conn->prepare("
            UPDATE periodos_vacaciones 
            SET estado = ?, dias_asignados = ?, dias_usados = ? 
            WHERE id_periodo = ?
        ");
        $stmt_update_periodo->execute([$nuevo_estado, $dias_asignados_edit, $dias_usados_to_save, $id_periodo]);
        $conn->commit();

        $_SESSION['mensaje'] = [
            'titulo' => '¡Éxito!',
            'contenido' => 'Período de vacaciones actualizado exitosamente.',
            'tipo' => 'success'
        ];
        header('Location: gestion-periodos.php?cedula=' . urlencode($cedula_busqueda_hidden));
        exit;
    } catch (PDOException $e) {
        $conn->rollBack();
        $errores[] = "Error al actualizar el período: " . $e->getMessage();
    }
}

// Si la página se carga con una cédula en el GET (después de una edición), procesarla
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['cedula'])) {
    $cedula = trim($_GET['cedula']);
    if (!is_numeric($cedula)) {
        $errores[] = "La cédula debe contener solo números.";
    } else {
        try {
            $stmt = $conn->prepare("
                SELECT p.id_pers, p.nombres, p.apellidos, p.cedula_identidad AS cedula, 
                       dl.fecha_ingreso, dl.estado,
                       d.nombre AS departamento, c.nombre AS cargo
                FROM datos_personales p
                INNER JOIN datos_laborales dl ON p.id_pers = dl.id_pers
                LEFT JOIN departamentos d ON dl.id_departamento = d.id_departamento
                LEFT JOIN cargos c ON dl.id_cargo = c.id_cargo
                WHERE p.cedula_identidad = ?
            ");
            $stmt->execute([$cedula]);
            $datos_empleado = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$datos_empleado) {
                $errores[] = "No se encontró empleado con esta cédula.";
            } else {
                $stmt_periodos = $conn->prepare("
                    SELECT id_periodo, fecha_inicio_periodo, fecha_fin_periodo, 
                           dias_asignados, dias_usados, estado, institucion
                    FROM periodos_vacaciones 
                    WHERE id_pers = ?
                    ORDER BY fecha_inicio_periodo DESC
                ");
                $stmt_periodos->execute([$datos_empleado['id_pers']]);
                $periodos_empleado = $stmt_periodos->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {
            $errores[] = "Error de base de datos al buscar empleado: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Períodos de Vacaciones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="../css/styles.css" rel="stylesheet">
    <style>
        .period-header {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            border-radius: 15px;
        }
        .period-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            padding: 2rem;
        }
        input[readonly] {
            background-color: #f8f9fa;
        }
        .status-badge {
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 500;
            transition: transform 0.2s;
        }
        .badge-activo { 
            background: #d4edda; 
            color: #155724;
            border: 2px solid #155724;
        }
        .badge-inactivo { 
            background: #f8d7da; 
            color: #721c24;
            border: 2px solid #721c24;
        }
        .badge-usado {
            background: #fff3cd;
            color: #856404;
            border: 2px solid #856404;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="period-header p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="mb-0">
                    <i class="bi bi-calendar-range me-3"></i>
                    Gestión de Períodos de Vacaciones
                </h1>
                <a href="gestion-vacaciones.php" class="btn btn-light btn-lg">
                    <i class="bi bi-arrow-left me-2"></i>Volver
                </a>
            </div>
        </div>

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

        <?php if(!empty($errores)): ?>
            <div class="alert alert-danger mb-4">
                <?php foreach ($errores as $error): ?>
                    <div><?= htmlspecialchars($error) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="period-card mx-auto" style="max-width: 900px;">
            <!-- Buscador avanzado -->
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

            <!-- Coincidencias -->
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
                                        <input type="hidden" name="cedula_identidad" value="<?= $emp['cedula_identidad'] ?>">
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

            <?php if($datos_empleado): ?>
                <div class="bg-light p-4 rounded-3 mb-4">
                    <h5 class="fw-bold mb-3">Datos del Empleado</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nombre Completo</label>
                            <input type="text" class="form-control" 
                                   value="<?= htmlspecialchars($datos_empleado['nombres'] . ' ' . $datos_empleado['apellidos']) ?>" 
                                   readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Cédula de Identidad</label>
                            <input type="text" class="form-control" 
                                   value="<?= htmlspecialchars((string)($datos_empleado['cedula'] ?? '')) ?>" 
                                   readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Cargo</label>
                            <input type="text" class="form-control" 
                                   value="<?= htmlspecialchars($datos_empleado['cargo'] ?? 'N/A') ?>" 
                                   readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Estado Laboral</label>
                            <input type="text" class="form-control" 
                                   value="<?= htmlspecialchars(ucfirst($datos_empleado['estado'])) ?>" 
                                   readonly>
                        </div>
                    </div>
                </div>

                <?php if(!empty($periodos_empleado)): ?>
                    <h5 class="fw-bold mb-3">Períodos de Vacaciones del Empleado</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>Período</th>
                                    <th>Inicio</th>
                                    <th>Fin</th>
                                    <th>Días Asignados</th>
                                    <th>Días Usados</th>
                                    <th>Estado</th>
                                    <th>Institución</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($periodos_empleado as $periodo): ?>
                                <tr>
                                    <td><?= htmlspecialchars(date('Y', strtotime($periodo['fecha_inicio_periodo'])) . '-' . date('Y', strtotime($periodo['fecha_fin_periodo']))) ?></td>
                                    <td><?= date('d/m/Y', strtotime($periodo['fecha_inicio_periodo'])) ?></td>
                                    <td><?= date('d/m/Y', strtotime($periodo['fecha_fin_periodo'])) ?></td>
                                    <td><?= htmlspecialchars($periodo['dias_asignados']) ?></td>
                                    <td><?= htmlspecialchars($periodo['dias_usados']) ?></td>
                                    <td>
                                        <span class="status-badge badge-<?= htmlspecialchars(strtolower($periodo['estado'])) ?>">
                                            <?= ucfirst($periodo['estado']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($periodo['institucion']) ?></td>
                                    <td>
                                        <!-- Aquí puedes agregar botón de editar si lo necesitas -->
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info text-center py-4">
                        <i class="bi bi-info-circle-fill display-4 mb-3"></i>
                        <h4 class="text-muted">No se encontraron períodos de vacaciones para este empleado.</h4>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="modal fade" id="editPeriodModal" tabindex="-1" aria-labelledby="editPeriodModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="editPeriodModalLabel">
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Efectos hover para badges
        document.querySelectorAll('.status-badge').forEach(element => {
            element.addEventListener('mouseenter', function() {
                this.style.transform = 'scale(1.05)';
            });
            element.addEventListener('mouseleave', function() {
                this.style.transform = 'scale(1)';
            });
        });
    </script>
</body>
</html>