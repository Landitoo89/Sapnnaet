<?php
session_start();
require ('../conexion.php');
require $_SERVER['DOCUMENT_ROOT']."/proyecto/inicio/sidebar.php";

$errores = [];
$datos_empleado = null;
$primas_empleado = [];
$coincidencias = [];

// Buscador avanzado por cédula, nombre o apellido
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buscar_empleado'])) {
    $busqueda = trim($_POST['busqueda']);
    if ($busqueda !== '') {
        $sql = "
            SELECT p.id_pers, p.nombres, p.apellidos, p.cedula_identidad,
                   dl.id_laboral, dl.fecha_ingreso, c.nombre AS cargo_nombre
            FROM datos_personales p
            INNER JOIN (
                SELECT id_pers, MAX(id_laboral) as max_id_laboral
                FROM datos_laborales
                GROUP BY id_pers
            ) AS latest_dl_id ON p.id_pers = latest_dl_id.id_pers
            INNER JOIN datos_laborales dl ON latest_dl_id.max_id_laboral = dl.id_laboral
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

// Selección del empleado desde coincidencias (por id_pers)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['seleccionar_empleado'])) {
    $id_pers = intval($_POST['id_pers']);
    $stmt = $conn->prepare("
        SELECT p.id_pers, p.nombres, p.apellidos, p.cedula_identidad,
               dl.id_laboral, dl.fecha_ingreso, c.nombre AS cargo_nombre
        FROM datos_personales p
        INNER JOIN datos_laborales dl ON p.id_pers = dl.id_pers
        LEFT JOIN cargos c ON dl.id_cargo = c.id_cargo
        WHERE p.id_pers = ?
        ORDER BY dl.fecha_ingreso DESC
        LIMIT 1
    ");
    $stmt->execute([$id_pers]);
    $datos_empleado = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($datos_empleado) {
        $stmt_primas = $conn->prepare("
            SELECT pr.nombre, pr.monto 
            FROM empleado_primas ep
            INNER JOIN primas pr ON ep.id_prima = pr.id_prima
            WHERE ep.id_laboral = ?
        ");
        $stmt_primas->execute([$datos_empleado['id_laboral']]);
        $primas_empleado = $stmt_primas->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $errores[] = "No se encontró empleado con este ID.";
    }
}

// Procesar búsqueda de empleado (por cédula, solo si se usa el buscador tradicional)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buscar_cedula'])) {
    $cedula = trim($_POST['cedula']);
    if (!is_numeric($cedula)) {
        $errores[] = "La cédula debe contener solo números.";
    } else {
        try {
            $stmt = $conn->prepare("
                SELECT 
                    p.id_pers, 
                    p.nombres, 
                    p.apellidos, 
                    p.cedula_identidad,
                    dl.id_laboral,
                    dl.fecha_ingreso, 
                    c.nombre AS cargo_nombre,
                FROM datos_personales p
                INNER JOIN datos_laborales dl ON p.id_pers = dl.id_pers
                LEFT JOIN cargos c ON dl.id_cargo = c.id_cargo
                WHERE p.cedula_identidad = ?
            ");
            $stmt->execute([$cedula]);
            $datos_empleado = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$datos_empleado) {
                $errores[] = "No se encontró empleado con esta cédula.";
            } else {
                $stmt_primas = $conn->prepare("
                    SELECT pr.nombre, pr.monto 
                    FROM empleado_primas ep
                    INNER JOIN primas pr ON ep.id_prima = pr.id_prima
                    WHERE ep.id_laboral = ?
                ");
                $stmt_primas->execute([$datos_empleado['id_laboral']]);
                $primas_empleado = $stmt_primas->fetchAll(PDO::FETCH_ASSOC);
            }

        } catch (PDOException $e) {
            $errores[] = "Error de base de datos al buscar empleado o sus primas: " . $e->getMessage();
        }
    }
}

// Lógica para el mensaje de éxito después de generar la constancia
if (isset($_SESSION['mensaje_constancia'])) {
    $mensaje_constancia = $_SESSION['mensaje_constancia'];
    unset($_SESSION['mensaje_constancia']);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generar Constancia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="css/styles.css" rel="stylesheet">
    <style>
        .constancia-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            padding: 2rem;
        }
        input[readonly] {
            background-color: #f8f9fa;
        }
        .btn-reset {
            background-color: #6c757d;
            color: white;
            border: none;
        }
        .btn-reset:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="constancia-card mx-auto" style="max-width: 900px;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="text-primary">
                    <i class="bi bi-file-earmark-text me-2"></i>
                    Generar Constancia de Trabajo
                </h2>
            </div>

            <?php if(!empty($errores)): ?>
                <div class="alert alert-danger mb-4">
                    <?php foreach ($errores as $error): ?>
                        <div><?= htmlspecialchars($error) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if(isset($mensaje_constancia)): ?>
                <div class="alert alert-<?= $mensaje_constancia['tipo'] ?> alert-dismissible fade show" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="bi <?= $mensaje_constancia['tipo'] == 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?> me-2"></i>
                        <div>
                            <h5 class="mb-0"><?= htmlspecialchars($mensaje_constancia['titulo']) ?></h5>
                            <p class="mb-0"><?= htmlspecialchars($mensaje_constancia['contenido']) ?></p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Buscador avanzado -->
            <form method="POST" class="mb-4">
                <div class="input-group">
                    <input type="text"
                           name="busqueda"
                           class="form-control form-control-lg"
                           placeholder="Buscar por cédula, nombre o apellido"
                           value="<?= isset($_POST['busqueda']) ? htmlspecialchars($_POST['busqueda']) : '' ?>"
                           required>
                    <button type="submit"
                            name="buscar_empleado"
                            class="btn btn-primary btn-lg">
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
                                <td><?= htmlspecialchars($emp['cargo_nombre']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

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
                            <label class="form-label fw-bold">Cargo</label>
                            <input type="text" class="form-control"
                                   value="<?= htmlspecialchars($datos_empleado['cargo_nombre'] ?? 'N/A') ?>"
                                   readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Fecha de Ingreso</label>
                            <input type="text" class="form-control"
                                   value="<?= htmlspecialchars(date('d/m/Y', strtotime($datos_empleado['fecha_ingreso']))) ?>"
                                   readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Sueldo Mensual</label>
                            <input type="text" class="form-control"
                                   value="<?= htmlspecialchars(number_format($datos_empleado['sueldo'], 2, ',', '.') . ' Bs.') ?>"
                                   readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Primas Adicionales</label>
                            <?php if (!empty($primas_empleado)): ?>
                                <ul class="list-group mt-2">
                                    <?php foreach ($primas_empleado as $prima): ?>
                                        <li class="list-group-item">
                                            <?= htmlspecialchars(number_format($prima['monto'], 2, ',', '.') . ' Bs. de PRIMA DE ' . $prima['nombre']) ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p class="form-control-plaintext">Ninguna</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-reset btn-lg" id="resetButton">
                        <i class="bi bi-arrow-counterclockwise me-2"></i>Resetear
                    </button>
                    <a href="generar-constancia-pdf.php?id_pers=<?= $datos_empleado['id_pers'] ?>" target="_blank" class="btn btn-primary btn-lg">
                        <i class="bi bi-file-earmark-pdf me-2"></i>Generar Constancia
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const resetButton = document.getElementById('resetButton');
            if (resetButton) {
                resetButton.addEventListener('click', () => {
                    window.location.href = window.location.pathname;
                });
            }
        });
    </script>
</body>
</html>