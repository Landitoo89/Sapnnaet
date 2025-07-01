<?php
session_start();
require 'conexion/conexion_db.php';

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

// Verificar permisos de admin (descomentar cuando tengas el sistema de permisos)
// if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'admin') {
//     header('Location: login.php');
//     exit;
// }

// Acciones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Eliminar registro
    if (isset($_POST['eliminar_registro'])) {
        $id_socioeconomico = $_POST['id_socioeconomico'];
        
        // Obtener datos del registro antes de eliminar para el log
        $stmt_select = $conexion->prepare("
            SELECT s.*, p.nombres, p.apellidos 
            FROM datos_socioeconomicos s
            INNER JOIN datos_personales p ON s.id_pers = p.id_pers
            WHERE s.id_socioeconomico = ?
        ");
        $stmt_select->bind_param("i", $id_socioeconomico);
        $stmt_select->execute();
        $registro_eliminado = $stmt_select->get_result()->fetch_assoc();
        $stmt_select->close();
        
        $stmt = $conexion->prepare("DELETE FROM datos_socioeconomicos WHERE id_socioeconomico = ?");
        $stmt->bind_param("i", $id_socioeconomico);
        
        if ($stmt->execute()) {
            // Registrar eliminación exitosa
            $detalles = "ID Socioeconómico: $id_socioeconomico, ";
            $detalles .= "Trabajador: {$registro_eliminado['nombres']} {$registro_eliminado['apellidos']}, ";
            $detalles .= "Estado Civil: {$registro_eliminado['estado_civil']}, ";
            $detalles .= "Tipo Vivienda: {$registro_eliminado['tipo_vivienda']}";
            
            registrarLog($conexion, $_SESSION['usuario']['id'], 'socioeconomic_deleted', $detalles);
            
            $_SESSION['mensaje'] = "Registro socioeconómico eliminado correctamente";
        } else {
            // Registrar error en eliminación
            registrarLog($conexion, $_SESSION['usuario']['id'], 'socioeconomic_delete_error', 
                        "Error al eliminar ID Socioeconómico: $id_socioeconomico - " . $stmt->error);
            
            $_SESSION['error'] = "Error al eliminar el registro";
        }
        
        header("Location: gestion_socioeconomicos.php");
        exit;
    }
}

// Inicializar variables de búsqueda
$searchTerm = '';
$searchCondition = '';
$params = [];

// Verificar si se envió un término de búsqueda
if (isset($_GET['q']) && !empty(trim($_GET['q']))) {
    $searchTerm = trim($_GET['q']);
    $searchCondition = " WHERE (p.nombres LIKE ? OR p.apellidos LIKE ? OR s.estado_civil LIKE ? OR s.nivel_academico LIKE ? OR s.tipo_vivienda LIKE ?) ";
    $searchTermLike = "%$searchTerm%";
    $params = array_fill(0, 5, $searchTermLike); // Se corrigió el conteo de parámetros
}

// Configuración de paginación
$registros_por_pagina = 10;
$pagina_actual = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

// Obtener total de registros (con búsqueda)
$sql_total = "
    SELECT COUNT(*) AS total 
    FROM datos_socioeconomicos s
    INNER JOIN datos_personales p ON s.id_pers = p.id_pers
    $searchCondition
";
$stmt_total = $conexion->prepare($sql_total);

// Evitar error "Commands out of sync"
if ($conexion->more_results()) {
    $conexion->next_result();
}

if (!empty($params)) {
    $types = str_repeat('s', count($params));
    $stmt_total->bind_param($types, ...$params);
}

$stmt_total->execute();
$result_total = $stmt_total->get_result();
$total_registros = $result_total->fetch_assoc()['total'];
$total_paginas = ceil($total_registros / $registros_por_pagina);

// Liberar resultados y cerrar statement
$result_total->free();
$stmt_total->close();

// Obtener registros paginados (con búsqueda)
$sql = "
    SELECT s.*, p.nombres, p.apellidos 
    FROM datos_socioeconomicos s
    INNER JOIN datos_personales p ON s.id_pers = p.id_pers
    $searchCondition
    ORDER BY s.id_socioeconomico DESC
    LIMIT ? OFFSET ?
";
$stmt = $conexion->prepare($sql);

// Evitar error "Commands out of sync"
if ($conexion->more_results()) {
    $conexion->next_result();
}

if (!empty($params)) {
    $types = str_repeat('s', count($params)) . 'ii';
    $params_pag = array_merge($params, [$registros_por_pagina, $offset]);
    $stmt->bind_param($types, ...$params_pag);
} else {
    $stmt->bind_param("ii", $registros_por_pagina, $offset);
}

$stmt->execute();
$resultado = $stmt->get_result();
$registros = $resultado->fetch_all(MYSQLI_ASSOC);
require $_SERVER['DOCUMENT_ROOT']."/proyecto/inicio/sidebar.php";

?>

<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Datos Socioeconómicos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
        .list-group-item {
            background-color: transparent;
            border: none;
            padding: 0.5rem 0;
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
        [data-theme="dark"] .alert {
            background: #23242a;
            color: #ff94e8;
            border-color: #ff94e8;
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
                <a class="nav-link active" href="gestion_socioeconomicos.php">Datos Socioeconómicos</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="gestion_carga.php">Carga Familiar</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="gestion_laboral.php">Datos Laborales</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="gestion_contrato.php">Otros Paneles</a>
            </li>
        </ul>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-chart-pie me-2"></i>Datos Socioeconómicos</h1>
            <div>
                <a href="form/form_datossocioeco.php" class="btn btn-primary">
                    <i class="fas fa-plus-circle me-2"></i>Nuevo Registro Socioeconómico
                </a>
            </div>
        </div>

        <?php if(isset($_SESSION['mensaje'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= htmlspecialchars($_SESSION['mensaje']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['mensaje']); ?>
        <?php endif; ?>
        
        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= htmlspecialchars($_SESSION['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <!-- BUSCADOR -->
        <div class="card search-card mb-4">
            <div class="card-body p-0">
                <form method="GET" action="gestion_socioeconomicos.php">
                    <div class="input-group">
                        <input type="text" 
                               class="form-control form-control-lg border-0 py-3" 
                               name="q" 
                               placeholder="Buscar por nombre, apellido, estado civil, nivel académico o tipo de vivienda..."
                               value="<?= htmlspecialchars($searchTerm) ?>">
                        <button class="btn btn-primary search-btn px-4" type="submit">
                            <i class="fas fa-search me-2"></i>Buscar
                        </button>
                        <?php if (!empty($searchTerm)): ?>
                            <a href="gestion_socioeconomicos.php" class="btn btn-outline-danger clear-btn mx-2">
                                <i class="fas fa-times me-2"></i>Limpiar
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
        <!-- FIN BUSCADOR -->

        <?php if (!empty($registros)): ?>
            <div class="data-table">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Trabajador</th>
                            <th>Estado Civil</th>
                            <th>Vivienda</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($registros as $registro): ?>
                        <tr data-bs-toggle="collapse" href="#detalle-<?= htmlspecialchars($registro['id_socioeconomico']) ?>" role="button">
                            <td><?= htmlspecialchars($registro['id_socioeconomico']) ?></td>
                            <td>
                                <?= htmlspecialchars("{$registro['nombres']} {$registro['apellidos']}") ?><br>
                                <small class="text-muted"><?= htmlspecialchars($registro['nivel_academico'] ?? 'N/A') ?></small>
                            </td>
                            <td><?= htmlspecialchars($registro['estado_civil']) ?></td>
                            <td><?= htmlspecialchars($registro['tipo_vivienda']) ?></td>
                            <td>
                                <div class="d-flex gap-2">
                                    <a href="edit/editdatos_socioeco.php?id=<?= htmlspecialchars($registro['id_socioeconomico']) ?>" 
                                       class="btn btn-sm btn-warning action-btn">
                                        <i class="fas fa-edit me-2"></i>Editar
                                    </a>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="id_socioeconomico" value="<?= htmlspecialchars($registro['id_socioeconomico']) ?>">
                                        <button type="submit" name="eliminar_registro" 
                                                class="btn btn-sm btn-danger action-btn"
                                                onclick="return confirm('¿Eliminar permanentemente este registro?')">
                                            <i class="fas fa-trash-alt me-2"></i>Eliminar
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        
                        <!-- Fila expandible con detalles adicionales -->
                        <tr class="detail-row">
                            <td colspan="6" class="detail-cell">
                                <div class="collapse collapsible-content" id="detalle-<?= htmlspecialchars($registro['id_socioeconomico']) ?>">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6><i class="fas fa-bolt me-2"></i>Servicios Básicos</h6>
                                            <ul class="list-group list-group-flush">
                                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                                    Agua Potable
                                                    <span class="badge bg-<?= ($registro['servicios_agua'] ?? 'No') == 'Sí' ? 'success' : 'danger' ?>">
                                                        <?= htmlspecialchars($registro['servicios_agua'] ?? 'No') ?>
                                                    </span>
                                                </li>
                                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                                    Electricidad
                                                    <span class="badge bg-<?= ($registro['servicios_electricidad'] ?? 'No') == 'Sí' ? 'success' : 'danger' ?>">
                                                        <?= htmlspecialchars($registro['servicios_electricidad'] ?? 'No') ?>
                                                    </span>
                                                </li>
                                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                                    Internet
                                                    <span class="badge bg-<?= ($registro['servicios_internet'] ?? 'No') == 'Sí' ? 'success' : 'danger' ?>">
                                                        <?= htmlspecialchars($registro['servicios_internet'] ?? 'No') ?>
                                                    </span>
                                                </li>
                                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                                    Gas
                                                    <span class="badge bg-<?= ($registro['servicios_gas'] ?? 'No') == 'Sí' ? 'success' : 'danger' ?>">
                                                        <?= htmlspecialchars($registro['servicios_gas'] ?? 'No') ?>
                                                    </span>
                                                </li>
                                            </ul>
                                            
                                            <h6 class="mt-3"><i class="fas fa-id-card me-2"></i>Carnets</h6>
                                            <ul class="list-group list-group-flush">
                                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                                    Carnet de la Patria
                                                    <span class="badge bg-<?= ($registro['carnet_patria'] ?? 'No') == 'Sí' ? 'success' : 'secondary' ?>">
                                                        <?= htmlspecialchars($registro['carnet_patria'] ?? 'No') ?>
                                                        <?php if(($registro['carnet_patria'] ?? 'No') == 'Sí'): ?>
                                                            <small class="ms-2">(Cód: <?= htmlspecialchars($registro['codigo_patria'] ?? 'N/A') ?>, Ser: <?= htmlspecialchars($registro['serial_patria'] ?? 'N/A') ?>)</small>
                                                        <?php endif; ?>
                                                    </span>
                                                </li>
                                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                                    Carnet PSUV
                                                    <span class="badge bg-<?= ($registro['carnet_psuv'] ?? 'No') == 'Sí' ? 'success' : 'secondary' ?>">
                                                        <?= htmlspecialchars($registro['carnet_psuv'] ?? 'No') ?>
                                                        <?php if(($registro['carnet_psuv'] ?? 'No') == 'Sí'): ?>
                                                            <small class="ms-2">(Cód: <?= htmlspecialchars($registro['codigo_psuv'] ?? 'N/A') ?>, Ser: <?= htmlspecialchars($registro['serial_psuv'] ?? 'N/A') ?>)</small>
                                                        <?php endif; ?>
                                                    </span>
                                                </li>
                                            </ul>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <h6><i class="fas fa-laptop me-2"></i>Tecnología Disponible</h6>
                                            <div class="d-flex flex-wrap gap-2">
                                                <span class="badge bg-<?= ($registro['tecnologia_computadora'] ?? 'No') == 'Sí' ? 'primary' : 'secondary' ?> p-2">
                                                    <i class="fas fa-desktop me-1"></i> Computadora
                                                </span>
                                                <span class="badge bg-<?= ($registro['tecnologia_smartphone'] ?? 'No') == 'Sí' ? 'primary' : 'secondary' ?> p-2">
                                                    <i class="fas fa-mobile-alt me-1"></i> Smartphone
                                                </span>
                                                <span class="badge bg-<?= ($registro['tecnologia_tablet'] ?? 'No') == 'Sí' ? 'primary' : 'secondary' ?> p-2">
                                                    <i class="fas fa-tablet-alt me-1"></i> Tablet
                                                </span>
                                            </div>
                                            
                                            <h6 class="mt-3"><i class="fas fa-graduation-cap me-2"></i>Información Educativa</h6>
                                            <ul class="list-group list-group-flush">
                                                <?php 
                                                $niveles = explode('|', $registro['nivel_academico'] ?? '');
                                                $menciones = explode('|', $registro['mencion'] ?? '');
                                                $instituciones = explode('|', $registro['instituciones_academicas'] ?? '');

                                                for ($i = 0; $i < count($niveles); $i++): 
                                                    if (!empty($niveles[$i])):
                                                ?>
                                                <li class="list-group-item">
                                                    <strong>Nivel:</strong> <?= htmlspecialchars($niveles[$i]) ?><br>
                                                    <?php if (!empty($menciones[$i])): ?>
                                                        <strong>Mención:</strong> <?= htmlspecialchars($menciones[$i]) ?><br>
                                                    <?php endif; ?>
                                                    <?php if (!empty($instituciones[$i])): ?>
                                                        <strong>Institución:</strong> <?= htmlspecialchars($instituciones[$i]) ?>
                                                    <?php endif; ?>
                                                </li>
                                                <?php 
                                                    endif;
                                                endfor; 
                                                if (empty($niveles[0])):
                                                ?>
                                                    <li class="list-group-item">No hay información académica registrada.</li>
                                                <?php endif; ?>
                                            </ul>
                                            
                                            <h6><i class="fas fa-info-circle me-2"></i>Información General</h6>
                                            <div class="d-flex gap-2">
                                                <span class="badge bg-info">
                                                    <i class="fas fa-home me-1"></i> <?= htmlspecialchars($registro['tipo_vivienda']) ?>
                                                </span>
                                            </div>
                                            <p class="mt-2 text-muted">Última actualización: <?= !empty($registro['fecha_actualizacion']) ? date('d/m/Y H:i', strtotime($registro['fecha_actualizacion'])) : 'N/A' ?></p>
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
            <nav aria-label="Navegación de páginas" class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php if ($pagina_actual > 1): ?>
                        <li class="page-item">
                            <a class="page-link" 
                               href="gestion_socioeconomicos.php?pagina=<?= $pagina_actual - 1 ?><?= !empty($searchTerm) ? '&q='.urlencode($searchTerm) : '' ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <?php 
                    $pagina_inicio = max(1, $pagina_actual - 2);
                    $pagina_fin = min($total_paginas, $pagina_actual + 2);
                    
                    for ($i = $pagina_inicio; $i <= $pagina_fin; $i++): 
                    ?>
                        <li class="page-item <?= $i == $pagina_actual ? 'active' : '' ?>">
                            <a class="page-link" 
                               href="gestion_socioeconomicos.php?pagina=<?= $i ?><?= !empty($searchTerm) ? '&q='.urlencode($searchTerm) : '' ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($pagina_actual < $total_paginas): ?>
                        <li class="page-item">
                            <a class="page-link" 
                               href="gestion_socioeconomicos.php?pagina=<?= $pagina_actual + 1 ?><?= !empty($searchTerm) ? '&q='.urlencode($searchTerm) : '' ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php else: ?>
            <div class="alert alert-info text-center py-4">
                <i class="fas fa-info-circle fa-2x mb-3"></i>
                <h3>No se encontraron registros</h3>
                <p class="mb-0"><?= !empty($searchTerm) ? 'Intenta con otro término de búsqueda' : 'No hay datos socioeconómicos registrados' ?></p>
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