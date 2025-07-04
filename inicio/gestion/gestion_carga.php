<?php
session_start();
require 'conexion/conexion_db.php';

// ==== FUNCIÓN PARA REGISTRAR LOGS ====
function registrarLog($conn, $user_id, $event_type, $details) {
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
    $stmt = $conn->prepare("INSERT INTO action_logs (user_id, event_type, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $user_id, $event_type, $details, $ip_address, $user_agent);
    $stmt->execute();
    $stmt->close();
}
$current_user_id = $_SESSION['usuario']['id'] ?? null;

// Acciones POST para eliminar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_carga'])) {
    $id_carga = $_POST['id_carga'];

    // Obtener datos del familiar y trabajador para el log ANTES de eliminar
    $stmt = $conexion->prepare("
        SELECT cf.nombres_familiar, cf.apellidos_familiar, cf.parentesco, cf.cedula_familiar, cf.tiene_discapacidad,
               ds.id_pers, dp.nombres AS nombres_trabajador, dp.apellidos AS apellidos_trabajador, cf.archivo_deficit
        FROM carga_familiar cf
        JOIN datos_socioeconomicos ds ON cf.id_socioeconomico = ds.id_socioeconomico
        JOIN datos_personales dp ON ds.id_pers = dp.id_pers
        WHERE cf.id_carga = ?
    ");
    $stmt->bind_param("i", $id_carga);
    $stmt->execute();
    $result = $stmt->get_result();
    $familiar = $result->fetch_assoc();
    $stmt->close();

    // Eliminar archivo físico si existe
    if ($familiar && !empty($familiar['archivo_deficit']) && file_exists($familiar['archivo_deficit'])) {
        unlink($familiar['archivo_deficit']);
    }

    // Eliminar de la base de datos
    $stmt = $conexion->prepare("DELETE FROM carga_familiar WHERE id_carga = ?");
    $stmt->bind_param("i", $id_carga);

    if ($stmt->execute()) {
        $_SESSION['mensaje'] = [
            'titulo' => '¡Éxito!',
            'contenido' => 'Carga familiar eliminada correctamente',
            'tipo' => 'success'
        ];

        // ==== LOG DE ELIMINACIÓN EXITOSA ====
        if ($familiar) {
            $log_details = "Eliminación de carga familiar para ID Persona: {$familiar['id_pers']}, Trabajador: {$familiar['nombres_trabajador']} {$familiar['apellidos_trabajador']}. Familiar eliminado: {$familiar['nombres_familiar']} {$familiar['apellidos_familiar']} (Parentesco: {$familiar['parentesco']}, Discapacidad: {$familiar['tiene_discapacidad']}";
            if ($familiar['tiene_discapacidad'] === 'Sí' && !empty($familiar['cedula_familiar'])) $log_details .= ", Cédula: {$familiar['cedula_familiar']}";
            $log_details .= ")";
            registrarLog(
                $conexion,
                $current_user_id,
                'carga_familiar_deleted',
                $log_details
            );
        }
    } else {
        $_SESSION['mensaje'] = [
            'titulo' => 'Error',
            'contenido' => 'Error al eliminar la carga familiar',
            'tipo' => 'danger'
        ];
        // ==== LOG DE ERROR EN ELIMINACIÓN ====
        $log_details = "Error al eliminar carga familiar ID {$id_carga}.";
        registrarLog(
            $conexion,
            $current_user_id,
            'carga_familiar_delete_error',
            $log_details
        );
    }

    header("Location: gestion_carga.php");
    exit;
}

// Parámetros de búsqueda y paginación
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;

$registrosPorPagina = 10;

function calcularEdadDesdeFecha($fecha_nacimiento) {
    if (empty($fecha_nacimiento) || $fecha_nacimiento == '0000-00-00') {
        return 'N/A';
    }
    try {
        $dob = new DateTime($fecha_nacimiento);
        $today = new DateTime();
        $age = $today->diff($dob)->y;
        return $age . ' años';
    } catch (Exception $e) {
        return 'Inválida';
    }
}

$sqlBase = "
    SELECT 
        cf.id_carga,
        cf.parentesco,
        cf.nombres_familiar,
        cf.apellidos_familiar,
        cf.fecha_nacimiento_familiar,
        cf.cedula_familiar,
        cf.genero_familiar,
        cf.tiene_discapacidad,
        cf.detalle_discapacidad,
        cf.archivo_deficit,
        dp.nombres AS nombres_trabajador,
        dp.apellidos AS apellidos_trabajador,
        dp.cedula_identidad AS cedula_trabajador
    FROM carga_familiar cf
    JOIN datos_socioeconomicos ds ON cf.id_socioeconomico = ds.id_socioeconomico
    JOIN datos_personales dp ON ds.id_pers = dp.id_pers
";

$condiciones = '';
$parametros = [];
$tipos = '';

if (!empty($busqueda)) {
    $condiciones = " WHERE CONCAT(dp.nombres, ' ', dp.apellidos) LIKE ? 
                     OR dp.cedula_identidad LIKE ? 
                     OR CONCAT(cf.nombres_familiar, ' ', cf.apellidos_familiar) LIKE ? 
                     OR cf.cedula_familiar LIKE ? ";
    $parametros[] = '%' . $busqueda . '%';
    $parametros[] = '%' . $busqueda . '%';
    $parametros[] = '%' . $busqueda . '%';
    $parametros[] = '%' . $busqueda . '%';
    $tipos = 'ssss';
}

$sqlCount = "SELECT COUNT(*) AS total FROM carga_familiar cf
             JOIN datos_socioeconomicos ds ON cf.id_socioeconomico = ds.id_socioeconomico
             JOIN datos_personales dp ON ds.id_pers = dp.id_pers" . $condiciones;

$stmtCount = $conexion->prepare($sqlCount);

if (!empty($parametros)) {
    $stmtCount->bind_param($tipos, ...$parametros);
}
$stmtCount->execute();
$resultadoCount = $stmtCount->get_result();
$totalRegistros = $resultadoCount->fetch_assoc()['total'];
$totalPaginas = ceil($totalRegistros / $registrosPorPagina);

if ($pagina > $totalPaginas && $totalPaginas > 0) {
    $pagina = $totalPaginas;
} else if ($totalPaginas == 0) {
    $pagina = 1;
}

$sql = $sqlBase . $condiciones . " ORDER BY cf.id_carga DESC LIMIT ? OFFSET ?";
$offset = ($pagina - 1) * $registrosPorPagina;

$parametros[] = $registrosPorPagina;
$parametros[] = $offset;
$tipos .= 'ii';

$stmt = $conexion->prepare($sql);

if (!empty($parametros)) {
    $stmt->bind_param($tipos, ...array_values($parametros));
}
$stmt->execute();
$resultado = $stmt->get_result();
$cargas = $resultado->fetch_all(MYSQLI_ASSOC);

$stmt->close();
$conexion->close();
require $_SERVER['DOCUMENT_ROOT']."/proyecto/inicio/sidebar.php";

?>

<!-- ... El resto del HTML permanece igual ... -->

<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Carga Familiar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --danger-color: #dc3545;
            --table-bg: #fff;
            --table-row-detail: #f8f9fa;
            --no-results-gradient: linear-gradient(135deg, #f8f9fa, #e9ecef);
        }
        [data-theme="dark"] {
            --primary-color: #8c7be7;
            --secondary-color: #ff94e8;
            --danger-color: #d75454;
            --table-bg: #23242a;
            --table-row-detail: #181b20;
            --no-results-gradient: linear-gradient(135deg, #23242a, #181b20);
        }
        body {
            background: #f7f9fc;
            color: #232323;
        }
        [data-theme="dark"] body {
            background: #15171c;
            color: #e2e7ef;
        }
        .theme-toggle-btn {
            position: fixed;
            top: 18px;
            right: 18px;
            z-index: 1101;
            background: var(--table-bg);
            border: 1px solid #b5b8c0;
            color: #232323;
            border-radius: 50%;
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 10px #2222;
            transition: background 0.2s, color 0.2s;
        }
        .theme-toggle-btn:hover {
            background: var(--table-row-detail);
            color: var(--secondary-color);
        }
        .data-table {
            background: var(--table-bg);
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .table thead {
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            color: white;
        }
        .table td,
        .table th {
            color: inherit;
            border-color: #333;
        }
        .action-btn {
            transition: all 0.3s ease;
            min-width: 100px;
        }
        .nav-tabs .nav-link {
            border: none;
            color: var(--primary-color);
            font-weight: 500;
        }
        .nav-tabs .nav-link.active {
            border-bottom: 3px solid var(--secondary-color);
            color: var(--secondary-color);
        }
        .pagination-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
        }
        .info-pagina {
            font-weight: 500;
            color: var(--primary-color);
        }
        .detail-row {
            background-color: var(--table-row-detail);
        }
        .detail-cell {
            padding: 0 !important;
        }
        .collapsible-content {
            padding: 20px;
            border-top: 2px solid #dee2e6;
        }
        .badge {
            font-weight: 500;
            padding: 0.5em 0.75em;
        }
        [data-theme="dark"] .badge.bg-success {
            background: #22e67b !important;
            color: #23242a !important;
        }
        [data-theme="dark"] .badge.bg-danger {
            background: #d75454 !important;
            color: #fff !important;
        }
        [data-theme="dark"] .badge.bg-primary {
            background: #8c7be7 !important;
            color: #fff !important;
        }
        [data-theme="dark"] .badge.bg-info {
            background: #50b6f9 !important;
            color: #23242a !important;
        }
        [data-theme="dark"] .badge.bg-secondary {
            background: #b5b8c0 !important;
            color: #23242a !important;
        }
        [data-theme="dark"] .alert {
            background: #23242a;
            color: #ff94e8;
            border-color: #ff94e8;
        }
        .list-group-item {
            background-color: transparent;
            border: none;
            padding: 0.5rem 0;
        }
        .search-card {
            border: 2px solid var(--secondary-color);
            border-radius: 15px;
            overflow: hidden;
        }
        [data-theme="dark"] .search-card,
        [data-theme="dark"] .card {
            background: #23242a !important;
        }
        [data-theme="dark"] .form-control,
        [data-theme="dark"] input,
        [data-theme="dark"] textarea {
            background: #191a1f !important;
            color: #e2e7ef !important;
            border-color: #393b3f !important;
        }
        [data-theme="dark"] .form-control::placeholder,
        [data-theme="dark"] input::placeholder {
            color: #b5b8c0 !important;
            opacity: 1;
        }
        [data-theme="dark"] .input-group-text {
            background: #23242a !important;
            color: #e2e7ef !important;
            border-color: #393b3f !important;
        }
        [data-theme="dark"] .nav-tabs .nav-link {
            background: none !important;
            color: #bab8fa !important;
        }
        [data-theme="dark"] .nav-tabs .nav-link.active {
            background: none !important;
            color: #ff94e8 !important;
            border-bottom: 3px solid #ff94e8 !important;
        }
        [data-theme="dark"] body {
            background: #15171c;
            color: #e2e7ef;
        }
        [data-theme="dark"] .data-table,
        [data-theme="dark"] .card,
        [data-theme="dark"] .table {
            background: #23242a !important;
            color: #e2e7ef !important;
        }
        [data-theme="dark"] .table td,
        [data-theme="dark"] .table th {
            color: #e2e7ef !important;
            border-color: #333 !important;
        }
        [data-theme="dark"] .form-control,
        [data-theme="dark"] input,
        [data-theme="dark"] textarea {
            background: #191a1f !important;
            color: #e2e7ef !important;
            border-color: #393b3f !important;
        }
        [data-theme="dark"] .form-control::placeholder,
        [data-theme="dark"] input::placeholder {
            color: #b5b8c0 !important;
            opacity: 1;
        }
        [data-theme="dark"] .input-group-text {
            background: #23242a !important;
            color: #e2e7ef !important;
            border-color: #393b3f !important;
        }
        [data-theme="dark"] .badge-custom,
        [data-theme="dark"] .badge-estado {
            color: #23242a !important;
            background: #ff94e8 !important;
        }
        [data-theme="dark"] .badge-estado.activo { background: #4ee87a !important; color: #23242a !important; }
        [data-theme="dark"] .badge-estado.vacaciones { background: #ffe066 !important; color: #23242a !important; }
        [data-theme="dark"] .badge-estado.inactivo { background: #d75454 !important; color: #fff !important; }
        [data-theme="dark"] .badge-estado.reposo { background: #4ec5e8 !important; color: #23242a !important; }
        [data-theme="dark"] .nav-tabs .nav-link {
            background: none !important;
            color: #bab8fa !important;
        }
        [data-theme="dark"] .nav-tabs .nav-link.active {
            background: none !important;
            color: #ff94e8 !important;
            border-bottom: 3px solid #ff94e8 !important;
        }
        [data-theme="dark"] .btn,
        [data-theme="dark"] .btn-primary,
        [data-theme="dark"] .btn-outline-danger,
        [data-theme="dark"] .btn-warning,
        [data-theme="dark"] .btn-danger {
            border-color: #393b3f !important;
        }
        [data-theme="dark"] .btn-primary {
            background: #8c7be7 !important;
            color: #fff !important;
        }
        [data-theme="dark"] .btn-outline-danger {
            color: #ff9494 !important;
            background: transparent !important;
            border-color: #ff9494 !important;
        }
        [data-theme="dark"] .btn-warning {
            background: #ffe066 !important;
            color: #23242a !important;
            border-color: #ffe066 !important;
        }
        [data-theme="dark"] .btn-danger {
            background: #d75454 !important;
            color: #fff !important;
            border-color: #d75454 !important;
        }
        [data-theme="dark"] .no-results-card {
            background: linear-gradient(135deg, #23242a, #181b20) !important;
            color: #e2e7ef !important;
        }
        [data-theme="dark"] .alert {
            background: #23242a;
            color: #ff94e8;
            border-color: #ff94e8;
        }
        [data-theme="light"] .table th,
        [data-theme="light"] .table td,
        [data-theme="light"] .table tr {
            border-color: #dee2e6 !important;
        }
        [data-theme="dark"] .table th,
        [data-theme="dark"] .table td,
        [data-theme="dark"] .table tr {
            border-color: #333 !important;
        }
    </style>
</head>
<body>
<!-- BOTÓN MODO OSCURO/MODO CLARO -->
<button class="theme-toggle-btn" id="themeToggleBtn" title="Cambiar modo" aria-label="Cambiar modo claro/oscuro">
    <i id="themeToggleIcon" class="fas fa-moon"></i>
</button>
    <div class="container py-5">
        <!-- Pestañas de navegación -->
         <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link" href="gestion_personal.php">Datos Personales</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="gestion_socioeconomicos.php">Datos Socioeconómicos</a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="gestion_carga.php">Carga Familiar</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="gestion_laboral.php">Datos Laborales</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="gestion_contrato.php">Otros Paneles</a>
            </li>
        </ul>
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-users me-2"></i>Gestión de Carga Familiar</h1>
            <a href="form/buscar_empleado_carga.php" class="btn btn-primary">
                <i class="fas fa-plus-circle me-2"></i>Nueva Carga Familiar
            </a>
        </div>

        <?php if(isset($_SESSION['mensaje'])): ?>
            <div class="alert alert-<?= htmlspecialchars($_SESSION['mensaje']['tipo'] ?? 'success') ?> alert-dismissible fade show">
                <div class="d-flex align-items-center">
                    <i class="bi <?= ($_SESSION['mensaje']['tipo'] ?? 'success') == 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?> me-2"></i>
                    <div>
                        <h5 class="mb-0"><?= htmlspecialchars($_SESSION['mensaje']['titulo'] ?? 'Mensaje') ?></h5>
                        <p class="mb-0"><?= htmlspecialchars($_SESSION['mensaje']['contenido'] ?? '') ?></p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['mensaje']); ?>
        <?php endif; ?>
        
        <?php if(isset($_SESSION['error'])): // Este bloque ahora puede ser eliminado si todos los errores se manejan con $_SESSION['mensaje'] ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= htmlspecialchars($_SESSION['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Formulario de búsqueda -->
        <div class="card search-card mb-4">
            <div class="card-body p-0">
                <form method="GET" action="gestion_carga.php">
                    <div class="input-group">
                        <input type="text" 
                               class="form-control form-control-lg border-0 py-3" 
                               name="busqueda" 
                               value="<?= htmlspecialchars($busqueda) ?>" 
                               placeholder="Buscar por nombre, cédula de empleado o familiar...">
                        <button class="btn btn-primary search-btn px-4" type="submit">
                            <i class="fas fa-search me-2"></i>Buscar
                        </button>
                        <?php if (!empty($busqueda)): ?>
                            <a href="gestion_carga.php" class="btn btn-outline-danger clear-btn mx-2">
                                <i class="fas fa-times me-2"></i>Limpiar
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <?php if (empty($cargas) && !empty($busqueda)): ?>
            <div class="alert alert-warning">
                No se encontraron cargas familiares para: "<?= htmlspecialchars($busqueda) ?>"
            </div>
        <?php elseif (empty($cargas)): ?>
            <div class="alert alert-info">
                No hay cargas familiares registradas.
            </div>
        <?php else: ?>
            <div class="data-table">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th></th>
                            <th>Familiar</th>
                            <th>Parentesco</th>
                            <th>Edad</th> <!-- Ahora esta edad se calcula dinámicamente -->
                            <th>Trabajador Principal</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($cargas as $carga): ?>
                        <tr data-bs-toggle="collapse" href="#detalle-carga-<?= htmlspecialchars($carga['id_carga']) ?>" role="button">
                            <td><?= htmlspecialchars($carga['id_carga']) ?></td>
                            <td>
                                <?= htmlspecialchars($carga['nombres_familiar'] . ' ' . $carga['apellidos_familiar']) ?><br>
                                <small class="text-muted">Cédula: <?= htmlspecialchars($carga['cedula_familiar'] ?? 'N/A') ?></small>
                            </td>
                            <td><?= htmlspecialchars($carga['parentesco']) ?></td>
                            <!-- Aquí se usa la función PHP para calcular la edad dinámicamente -->
                            <td><?= calcularEdadDesdeFecha($carga['fecha_nacimiento_familiar']) ?></td>
                            <td>
                                <?= htmlspecialchars($carga['nombres_trabajador'] . ' ' . $carga['apellidos_trabajador']) ?><br>
                                <small class="text-muted">C.I.: <?= htmlspecialchars($carga['cedula_trabajador']) ?></small>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    <a href="edit/edit_carga.php?id_carga=<?= htmlspecialchars($carga['id_carga']) ?>" 
                                       class="btn btn-sm btn-warning"
                                       title="Editar Carga Familiar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="id_carga" value="<?= htmlspecialchars($carga['id_carga']) ?>">
                                        <button type="submit" name="eliminar_carga" 
                                                class="btn btn-sm btn-danger"
                                                title="Eliminar Carga Familiar"
                                                onclick="return confirm('¿Eliminar permanentemente este registro de carga familiar? Esto no afectará los datos del empleado principal.')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        
                        <!-- Fila expandible con detalles adicionales -->
                        <tr class="detail-row">
                            <td colspan="6" class="detail-cell">
                                <div class="collapse collapsible-content" id="detalle-carga-<?= htmlspecialchars($carga['id_carga']) ?>">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6><i class="fas fa-venus-mars me-2"></i>Género y Discapacidad</h6>
                                            <p><strong>Género:</strong> <?= htmlspecialchars($carga['genero_familiar'] ?? 'N/A') ?></p>
                                            <p><strong>Discapacidad:</strong> 
                                                <?= htmlspecialchars($carga['tiene_discapacidad'] === 'Sí' ? 'Sí' : 'No') ?>
                                            </p>
                                            <?php if ($carga['tiene_discapacidad'] === 'Sí' && !empty($carga['detalle_discapacidad'])): ?>
                                                <p class="ms-3"><strong>Detalle:</strong> <?= htmlspecialchars($carga['detalle_discapacidad']) ?></p>
                                            <?php endif; ?>
                                            <?php if ($carga['tiene_discapacidad'] === 'Sí' && !empty($carga['archivo_deficit'])): ?>
                                                <p class="ms-3"><strong>Archivo:</strong> 
                                                    <a href="<?= htmlspecialchars($carga['archivo_deficit']) ?>" target="_blank">Ver archivo</a>
                                                </p>
                                            <?php elseif ($carga['tiene_discapacidad'] === 'Sí'): ?>
                                                <p class="ms-3 text-muted">No hay archivo de déficit subido.</p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-6">
                                            <h6><i class="fas fa-info-circle me-2"></i>Información Adicional</h6>
                                            <p class="text-muted small">ID Carga: <?= htmlspecialchars($carga['id_carga']) ?></p>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Paginación -->
            <div class="pagination-container">
                <div class="info-pagina">
                    Mostrando <?= count($cargas) ?> de <?= $totalRegistros ?> registros
                </div>
                <nav>
                    <ul class="pagination">
                        <?php if ($pagina > 1): ?>
                            <li class="page-item">
                                <a class="page-link" 
                                   href="?busqueda=<?= urlencode($busqueda) ?>&pagina=<?= $pagina-1 ?>">
                                    &laquo; Anterior
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php 
                        $inicio = max(1, $pagina - 2);
                        $fin = min($totalPaginas, $pagina + 2);
                        
                        if ($inicio > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?busqueda=<?= urlencode($busqueda) ?>&pagina=1">1</a>
                            </li>
                            <?php if ($inicio > 2): ?>
                                <li class="page-item disabled"><span class="page-link">...</span></li>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php for ($i = $inicio; $i <= $fin; $i++): ?>
                            <li class="page-item <?= ($i == $pagina) ? 'active' : '' ?>">
                                <a class="page-link" 
                                   href="?busqueda=<?= urlencode($busqueda) ?>&pagina=<?= $i ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($fin < $totalPaginas): ?>
                            <?php if ($fin < $totalPaginas - 1): ?>
                                <li class="page-item disabled"><span class="page-link">...</span></li>
                            <?php endif; ?>
                            <li class="page-item">
                                <a class="page-link" 
                                   href="?busqueda=<?= urlencode($busqueda) ?>&pagina=<?= $totalPaginas ?>">
                                    <?= $totalPaginas ?>
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php if ($pagina < $totalPaginas): ?>
                            <li class="page-item">
                                <a class="page-link" 
                                   href="?busqueda=<?= urlencode($busqueda) ?>&pagina=<?= $pagina+1 ?>">
                                    Siguiente &raquo;
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var themeToggleBtn = document.getElementById('themeToggleBtn');
            var themeToggleIcon = document.getElementById('themeToggleIcon');
            var htmlTag = document.documentElement;

            let theme = localStorage.getItem('theme') || 'light';
            htmlTag.setAttribute('data-theme', theme);
            themeToggleIcon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';

            themeToggleBtn.addEventListener('click', function() {
                let current = htmlTag.getAttribute('data-theme');
                let next = current === 'dark' ? 'light' : 'dark';
                htmlTag.setAttribute('data-theme', next);
                localStorage.setItem('theme', next);
                themeToggleIcon.className = next === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
            });
        });
    </script>
</body>
</html>