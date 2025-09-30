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

// Verificar permisos de admin
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$stmt_empleados = $conexion->prepare("
    SELECT c.id_cargo, COUNT(dl.id_laboral) AS total_empleados
    FROM cargos c
    LEFT JOIN datos_laborales dl ON c.id_cargo = dl.id_cargo
    GROUP BY c.id_cargo
");
$stmt_empleados->execute();
$result_empleados = $stmt_empleados->get_result();

$empleados_por_cargo = [];
while ($row = $result_empleados->fetch_assoc()) {
    $empleados_por_cargo[$row['id_cargo']] = $row['total_empleados'];
}

// Obtener total de empleados en la organización
$stmt_total_empleados = $conexion->prepare("SELECT COUNT(*) AS total FROM datos_laborales");
$stmt_total_empleados->execute();
$total_empleados = $stmt_total_empleados->get_result()->fetch_assoc()['total'];

// Acciones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Eliminar cargo
    if (isset($_POST['eliminar_cargo'])) {
        $id_cargo = $_POST['id_cargo'];

        // Obtén el nombre y nivel del cargo para el log
        $stmt_info = $conexion->prepare("SELECT nombre, nivel FROM cargos WHERE id_cargo = ?");
        $stmt_info->bind_param("i", $id_cargo);
        $stmt_info->execute();
        $res_info = $stmt_info->get_result();
        $cargo_info = $res_info->fetch_assoc();
        $stmt_info->close();

        // Verificar si el cargo está en uso
        $stmt_check = $conexion->prepare("SELECT COUNT(*) AS total FROM datos_laborales WHERE id_cargo = ?");
        $stmt_check->bind_param("i", $id_cargo);
        $stmt_check->execute();
        $result = $stmt_check->get_result();
        $row = $result->fetch_assoc();

        if ($row['total'] > 0) {
            $_SESSION['error'] = "No se puede eliminar el cargo porque está asignado a empleados.";

            // LOG DE INTENTO FALLIDO POR RESTRICCIÓN
            if ($cargo_info) {
                $log_details = "Intento fallido de eliminación de cargo: {$cargo_info['nombre']} (Nivel: {$cargo_info['nivel']}, ID: $id_cargo). Cargo en uso por empleados.";
                registrarLog(
                    $conexion,
                    $current_user_id,
                    'cargo_delete_failed_in_use',
                    $log_details
                );
            }
        } else {
            $stmt = $conexion->prepare("DELETE FROM cargos WHERE id_cargo = ?");
            $stmt->bind_param("i", $id_cargo);

            if ($stmt->execute()) {
                $_SESSION['mensaje'] = "Cargo eliminado correctamente";

                // LOG DE ELIMINACIÓN EXITOSA
                if ($cargo_info) {
                    $log_details = "Eliminación de cargo: {$cargo_info['nombre']} (Nivel: {$cargo_info['nivel']}, ID: $id_cargo).";
                    registrarLog(
                        $conexion,
                        $current_user_id,
                        'cargo_deleted',
                        $log_details
                    );
                }
            } else {
                $_SESSION['error'] = "Error al eliminar el cargo";

                // LOG DE ERROR EN ELIMINACIÓN
                if ($cargo_info) {
                    $log_details = "Error al eliminar cargo: {$cargo_info['nombre']} (Nivel: {$cargo_info['nivel']}, ID: $id_cargo).";
                    registrarLog(
                        $conexion,
                        $current_user_id,
                        'cargo_delete_error',
                        $log_details
                    );
                }
            }
        }

        header("Location: gestion_cargos.php");
        exit;
    }
}

// Configuración de paginación
$registros_por_pagina = 10;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

// Manejar búsqueda
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$condicion = '';
$parametros = [];

if ($busqueda !== '') {
    // Buscar también por tipo de personal y descripción
    $condicion = " WHERE c.nombre LIKE ? OR c.nivel LIKE ? OR c.descripcion LIKE ? OR tp.nombre LIKE ? ";
    $parametros = ["%$busqueda%", "%$busqueda%", "%$busqueda%", "%$busqueda%"];
}

// Obtener total de registros
$sql_total = "SELECT COUNT(*) AS total FROM cargos c LEFT JOIN tipos_personal tp ON c.id_tipo_personal = tp.id_tipo_personal $condicion";
$stmt_total = $conexion->prepare($sql_total);

if (!empty($parametros)) {
    $stmt_total->bind_param(str_repeat('s', count($parametros)), ...$parametros);
}

$stmt_total->execute();
$total_registros = $stmt_total->get_result()->fetch_assoc()['total'];
$total_paginas = ceil($total_registros / $registros_por_pagina);

// Obtener registros paginados
$sql = "SELECT c.*, tp.nombre AS tipo_personal_nombre FROM cargos c
        LEFT JOIN tipos_personal tp ON c.id_tipo_personal = tp.id_tipo_personal
        $condicion
        ORDER BY c.id_cargo DESC LIMIT ? OFFSET ?";
$stmt = $conexion->prepare($sql);

if (!empty($parametros)) {
    $parametros[] = $registros_por_pagina;
    $parametros[] = $offset;
    $stmt->bind_param(str_repeat('s', count($parametros) - 2) . 'ii', ...$parametros);
} else {
    $stmt->bind_param("ii", $registros_por_pagina, $offset);
}

$stmt->execute();
$resultado = $stmt->get_result();
$cargos = $resultado->fetch_all(MYSQLI_ASSOC);
require $_SERVER['DOCUMENT_ROOT']."/proyecto/inicio/sidebar.php";

?>

<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Cargos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --table-bg: #fff;
            --table-row-detail: #f8f9fa;
        }
        [data-theme="dark"] {
            --primary-color: #8c7be7;
            --secondary-color: #ff94e8;
            --success-color: #22e67b;
            --danger-color: #d75454;
            --warning-color: #ffe066;
            --table-bg: #23242a;
            --table-row-detail: #181b20;
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
            margin-bottom: 2rem;
        }
        .table thead {
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            color: white;
        }
        .table th {
            font-weight: 500;
        }
        .table td,
        .table th {
            color: inherit;
        }
        [data-theme="dark"] .table th,
        [data-theme="dark"] .table td,
        [data-theme="dark"] .table tr {
            border-color: #333 !important;
        }
        [data-theme="light"] .table th,
        [data-theme="light"] .table td,
        [data-theme="light"] .table tr {
            border-color: #dee2e6 !important;
        }
        .action-btn {
            transition: all 0.3s ease;
            min-width: 100px;
        }
        .badge-custom {
            padding: 8px 12px;
            border-radius: 20px;
            font-weight: normal;
        }
        .badge-junior {
            background-color: #6f42c1;
        }
        .badge-senior {
            background-color: #d63384;
        }
        .badge-gerencial {
            background-color: #fd7e14;
        }
        .badge-directivo {
            background-color: #20c997;
        }
        [data-theme="dark"] .badge-junior {
            background-color: #a899fa !important;
            color: #23242a !important;
        }
        [data-theme="dark"] .badge-senior {
            background-color: #ff94e8 !important;
            color: #23242a !important;
        }
        [data-theme="dark"] .badge-gerencial {
            background-color: #ffe066 !important;
            color: #23242a !important;
        }
        [data-theme="dark"] .badge-directivo {
            background-color: #22e67b !important;
            color: #23242a !important;
        }
        .search-container {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        [data-theme="dark"] .search-container {
            background-color: #23242a !important;
        }
        .stats-card {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .stats-card i {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        .stats-card h3 {
            font-size: 1.8rem;
            margin-bottom: 0;
        }
        .pagination .page-item.active .page-link {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
            color: #fff;
        }
        .pagination .page-link {
            color: var(--primary-color);
        }
        .btn-primary {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        .btn-primary:hover {
            background-color: #258cd1;
            border-color: #258cd1;
        }
        [data-theme="dark"] .btn-primary {
            background-color: #8c7be7 !important;
            border-color: #8c7be7 !important;
            color: #fff !important;
        }
        .btn-outline-secondary {
            border-color: var(--secondary-color);
            color: var(--secondary-color);
        }
        [data-theme="dark"] .btn-outline-secondary {
            border-color: #bab8fa !important;
            color: #bab8fa !important;
            background: none !important;
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
        [data-theme="dark"] .nav-tabs .nav-link {
            background: none !important;
            color: #bab8fa !important;
        }
        [data-theme="dark"] .nav-tabs .nav-link.active {
            background: none !important;
            color: #ff94e8 !important;
            border-bottom: 3px solid #ff94e8 !important;
        }
        [data-theme="dark"] .alert {
            background: #23242a;
            color: #ff94e8;
            border-color: #ff94e8;
        }
        [data-theme="dark"] .form-control {
            background: #181b20 !important;
            color: #e2e7ef !important;
            border-color: #393b3f !important;
        }
        [data-theme="dark"] .form-control::placeholder {
            color: #b5b8c0 !important;
            opacity: 1;
        }
        [data-theme="dark"] .table td strong,
        [data-theme="dark"] .table td .fw-bold,
        [data-theme="dark"] .table th {
            color: #e2e7ef !important;
        }
        [data-theme="dark"] .table td,
        [data-theme="dark"] .table th {
            color: #e2e7ef !important;
        }
    </style>
</head>
<body>
<!-- BOTÓN MODO OSCURO/MODO CLARO -->
<button class="theme-toggle-btn" id="themeToggleBtn" title="Cambiar modo" aria-label="Cambiar modo claro/oscuro">
    <i id="themeToggleIcon" class="fas fa-moon"></i>
</button>
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0"><i class="fas fa-briefcase me-2"></i>Gestión de Cargos</h1>
            <div>
                <a href="agregar/agregar_cargos.php" class="btn btn-primary">
                    <i class="fas fa-plus-circle me-2"></i>Nuevo Cargo
                </a>
            </div>
        </div>

        <?php if(isset($_SESSION['mensaje'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= $_SESSION['mensaje'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['mensaje']); ?>
        <?php endif; ?>
        
        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= $_SESSION['error'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <i class="fas fa-briefcase"></i>
                    <h3><?= $total_registros ?></h3>
                    <p class="mb-0">Cargos Totales</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center" style="background: linear-gradient(135deg, #28a745, #20c997);">
                    <i class="fas fa-user-tie"></i>
                    <h3><?= number_format($total_empleados) ?></h3>
                    <p class="mb-0">Empleados Asociados</p>
                </div>
            </div>

             <div class="container py-5">
        <!-- Pestañas de navegación -->
        <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link" href="gestion_contrato.php">Contratos</a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="gestion_cargos.php">Cargos</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="gestion_tpersonal.php">Tipos de Personal</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="gestion_departamentos.php">Departamentos</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="gestion_coordinaciones.php">Coordinaciones</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="gestion_primas.php">Primas</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="gestion_personal.php">Volver a Personal</a>
            </li>
        </ul>

        <div class="search-container">
            <form method="GET" class="row g-3">
                <div class="col-md-8">
                    <input type="text" name="busqueda" class="form-control form-control-lg" 
                           placeholder="Buscar cargos por nombre, grado, tipo de personal o descripción..." 
                           value="<?= htmlspecialchars($busqueda) ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-lg w-100">
                        <i class="fas fa-search me-2"></i>Buscar
                    </button>
                </div>
                <div class="col-md-2">
                    <a href="gestion_cargos.php" class="btn btn-outline-secondary btn-lg w-100">
                        <i class="fas fa-sync-alt me-2"></i>Limpiar
                    </a>
                </div>
            </form>
        </div>

        <div class="data-table">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre del Cargo</th>
                        <th>Grado</th>
                        <th>Tipo de Personal</th>
                        <th>Descripción</th>
                        <th>Empleados Asociados</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($cargos) > 0): ?>
                        <?php foreach($cargos as $cargo): ?>
                            <?php 
                            $badge_class = '';
                            switch($cargo['grado']) {
                                case 'Junior': $badge_class = 'badge-junior'; break;
                                case 'Senior': $badge_class = 'badge-senior'; break;
                                case 'Gerencial': $badge_class = 'badge-gerencial'; break;
                                case 'Directivo': $badge_class = 'badge-directivo'; break;
                                default: $badge_class = 'bg-secondary';
                            }
                            ?>
                            <tr>
                                <td><?= $cargo['id_cargo'] ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($cargo['nombre']) ?></strong>
                                </td>
                                <td>
                                    <span class="badge badge-custom <?= $badge_class ?>">
                                        <?= htmlspecialchars($cargo['grado']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="fw-bold"><?= htmlspecialchars($cargo['tipo_personal_nombre'] ?? '') ?></span>
                                </td>
                                <td>
                                    <span><?= htmlspecialchars($cargo['descripcion']) ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-primary rounded-pill py-2 px-3">
                                       <?= $empleados_por_cargo[$cargo['id_cargo']] ?? 0 ?> empleados
                                   </span>
                                </td>
                                
                                <td>
                                    <div class="d-flex gap-2">
                                        <!-- Botón Editar -->
                                        <a href="edit/edit_cargos.php?id=<?= $cargo['id_cargo'] ?>" 
                                           class="btn btn-sm btn-warning"
                                           title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        
                                        <!-- Botón Eliminar -->
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="id_cargo" value="<?= $cargo['id_cargo'] ?>">
                                            <button type="submit" 
                                                    name="eliminar_cargo"
                                                    class="btn btn-sm btn-danger"
                                                    title="Eliminar"
                                                    onclick="return confirm('¿Está seguro de eliminar este cargo?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <i class="fas fa-info-circle fa-2x mb-3 text-secondary"></i>
                                <h5>No se encontraron cargos</h5>
                                <p class="text-muted">Intenta con otros términos de búsqueda o crea un nuevo cargo.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        <nav aria-label="Navegación de páginas">
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                    <li class="page-item <?= $i == $pagina_actual ? 'active' : '' ?>">
                        <a class="page-link" href="gestion_cargos.php?pagina=<?= $i ?>&busqueda=<?= urlencode($busqueda) ?>">
                            <?= $i ?>
                        </a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
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