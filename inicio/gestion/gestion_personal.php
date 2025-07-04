<?php
session_start();
require 'conexion/conexion_db.php';
//require '../../core/verificar_sesion.php';
require '../../core/logger.php'; // Asegúrate de que esta ruta sea correcta

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario'])) {
    // Registrar intento de acceso no autorizado
    $detalles_log = "Intento de acceso no autorizado al panel de gestión de datos personales";
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
    
    header("Location: ../../index.php");
    exit;
}

// Verificar permisos de admin
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

// Acciones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Eliminar registro
    if (isset($_POST['eliminar_registro'])) {
        $id_pers = $_POST['id_pers'];
        $cedula_identidad_log = 'Desconocida'; // Valor por defecto en caso de no encontrarla

        // --- INICIO: Obtener la cédula de identidad antes de eliminar ---
        $stmt_get_cedula = $conexion->prepare("SELECT cedula_identidad FROM datos_personales WHERE id_pers = ?");
        $stmt_get_cedula->bind_param("i", $id_pers);
        $stmt_get_cedula->execute();
        $result_cedula = $stmt_get_cedula->get_result();
        if ($result_cedula->num_rows > 0) {
            $row_cedula = $result_cedula->fetch_assoc();
            $cedula_identidad_log = $row_cedula['cedula_identidad'];
        }
        $stmt_get_cedula->close();
        // --- FIN: Obtener la cédula de identidad ---
        
        // Iniciar transacción
        $conexion->begin_transaction();
        try {
            // Eliminar registros relacionados en carga_familiar
            $stmt_carga_familiar = $conexion->prepare("DELETE FROM carga_familiar WHERE id_socioeconomico IN (SELECT id_socioeconomico FROM datos_socioeconomicos WHERE id_pers = ?)");
            $stmt_carga_familiar->bind_param("i", $id_pers);
            $stmt_carga_familiar->execute();

            // Eliminar registros relacionados en empleado_primas
            $stmt_empleado_primas = $conexion->prepare("DELETE FROM empleado_primas WHERE id_laboral IN (SELECT id_laboral FROM datos_laborales WHERE id_pers = ?)");
            $stmt_empleado_primas->bind_param("i", $id_pers);
            $stmt_empleado_primas->execute();

            // Eliminar registros relacionados en datos_socioeconomicos
            $stmt_socioeconomicos = $conexion->prepare("DELETE FROM datos_socioeconomicos WHERE id_pers = ?");
            $stmt_socioeconomicos->bind_param("i", $id_pers);
            $stmt_socioeconomicos->execute();

            // Eliminar registros relacionados en datos_laborales
            $stmt_laborales = $conexion->prepare("DELETE FROM datos_laborales WHERE id_pers = ?");
            $stmt_laborales->bind_param("i", $id_pers);
            $stmt_laborales->execute();
            
            // Eliminar registros relacionados en periodos_vacaciones
            $stmt_periodos_vacaciones = $conexion->prepare("DELETE FROM periodos_vacaciones WHERE id_pers = ?");
            $stmt_periodos_vacaciones->bind_param("i", $id_pers);
            $stmt_periodos_vacaciones->execute();

            // Eliminar registros relacionados en reposos
            $stmt_reposos = $conexion->prepare("DELETE FROM reposos WHERE id_pers = ?");
            $stmt_reposos->bind_param("i", $id_pers);
            $stmt_reposos->execute();
            
            // Finalmente, eliminar el registro principal de datos_personales
            $stmt_datos_personales = $conexion->prepare("DELETE FROM datos_personales WHERE id_pers = ?");
            $stmt_datos_personales->bind_param("i", $id_pers);
            $stmt_datos_personales->execute();

            // Si todo fue bien, confirmar la transacción
            $conexion->commit();
            $_SESSION['success_message'] = 'Registro eliminado correctamente.';

            log_action($conexion, 'Eliminación Personal', 'Se eliminó el registro del personal con CI: ' . $cedula_identidad_log . ' (ID: ' . $id_pers . ')');
        } catch (mysqli_sql_exception $e) {
            $conexion->rollback();
            $_SESSION['error_message'] = 'Error al eliminar el registro: ' . $e->getMessage();
            log_action($conexion, 'Error de Eliminación', 'Fallo al intentar eliminar el registro del personal con CI: ' . $cedula_identidad_log . ' (ID: ' . $id_pers . '). Error: ' . $e->getMessage());
        }
    }
        
    header("Location: gestion_personal.php");
    exit;
}

// Inicializar variables de búsqueda
$searchTerm = '';
$searchCondition = '';
$params = array();

if (isset($_GET['q']) && !empty(trim($_GET['q']))) {
    $searchTerm = trim($_GET['q']);
    $searchCondition = " WHERE (nombres LIKE ? OR apellidos LIKE ? OR cedula_identidad LIKE ? OR rif LIKE ? OR pasaporte LIKE ?) ";
    $searchTermLike = "%$searchTerm%";
    $params = array_fill(0, 5, $searchTermLike);
}

// Configuración de paginación
$registros_por_pagina = 10;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

// Obtener total de registros
$sql_total = "SELECT COUNT(*) AS total FROM datos_personales $searchCondition";
$stmt_total = $conexion->prepare($sql_total);

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

if ($pagina_actual > $total_paginas && $total_paginas > 0) {
    $pagina_actual = $total_paginas;
}

$offset = ($pagina_actual - 1) * $registros_por_pagina;

$sql = "SELECT * FROM datos_personales $searchCondition ORDER BY fecha_registro DESC LIMIT ? OFFSET ?";
$stmt = $conexion->prepare($sql);

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

// --- Lógica para Notificaciones de Datos Faltantes ---
$query_missing_socio = "
    SELECT dp.id_pers, dp.nombres, dp.apellidos, dp.cedula_identidad
    FROM datos_personales dp
    LEFT JOIN datos_socioeconomicos ds ON dp.id_pers = ds.id_pers
    WHERE ds.id_socioeconomico IS NULL
";
$result_missing_socio = $conexion->query($query_missing_socio);
$missing_socioeconomic_data = $result_missing_socio->fetch_all(MYSQLI_ASSOC);
$result_missing_socio->free();

$query_missing_laboral = "
    SELECT dp.id_pers, dp.nombres, dp.apellidos, dp.cedula_identidad
    FROM datos_personales dp
    LEFT JOIN datos_laborales dl ON dp.id_pers = dl.id_pers
    WHERE dl.id_laboral IS NULL
";
$result_missing_laboral = $conexion->query($query_missing_laboral);
$missing_laboral_data = $result_missing_laboral->fetch_all(MYSQLI_ASSOC);
$result_missing_laboral->free();

require $_SERVER['DOCUMENT_ROOT']."/proyecto/inicio/sidebar.php";
?>

<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Datos Personales</title>
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
        .badge-custom {
            background: var(--secondary-color);
            padding: 8px 12px;
            border-radius: 20px;
        }
        .no-results-card {
            background: var(--no-results-gradient);
            border-radius: 15px;
            box-shadow: 0 6px 15px rgba(0,0,0,0.1);
            border: none;
        }
        .no-results-icon {
            font-size: 4rem;
            color: #6c757d;
            margin-bottom: 20px;
        }
        .no-results-title {
            color: #495057;
            font-weight: 600;
        }
        .no-results-text {
            color: #6c757d;
            font-size: 1.1rem;
        }
        .no-results-suggest {
            color: #495057;
            font-weight: 500;
            margin-top: 15px;
        }
        [data-theme="dark"] .no-results-title, [data-theme="dark"] .no-results-suggest {
            color: #e2e7ef;
        }
        [data-theme="dark"] .no-results-text {
            color: #b5b8c0;
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
                <a class="nav-link active" href="gestion_personal.php">Datos Personales</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="gestion_socioeconomicos.php">Datos Socioeconómicos</a>
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
            <h1><i class="fas fa-user-friends me-2"></i>Datos Personales</h1>
            <div>
                <!-- Botón de Notificaciones -->
                <button type="button" class="btn btn-info position-relative me-2" data-bs-toggle="modal" data-bs-target="#notificationModal">
                    <i class="fas fa-bell me-2"></i>Notificaciones
                    <?php 
                        $total_missing = count($missing_socioeconomic_data) + count($missing_laboral_data);
                        if ($total_missing > 0): 
                    ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?= htmlspecialchars($total_missing) ?>
                            <span class="visually-hidden">notificaciones no leídas</span>
                        </span>
                    <?php endif; ?>
                </button>
                <a href="form/form_register.php" class="btn btn-primary">
                    <i class="fas fa-plus-circle me-2"></i>Nuevo Registro
                </a>
                <a href="reporte_personal.php<?= !empty($searchTerm) ? '?q='.urlencode($searchTerm) : '' ?>" 
                    class="btn btn-outline-secondary"
                    target="_blank">
                    <i class="fas fa-file-pdf me-2"></i>Exportar PDF
                </a>
            </div>
        </div>

        <?php 
        // Manejar mensajes de sesión (éxito o error)
        if (isset($_SESSION['mensaje'])) {
            $mensaje = $_SESSION['mensaje'];
            $display_contenido = '';
            $display_tipo = 'info';

            if (is_array($mensaje)) {
                $display_contenido = htmlspecialchars($mensaje['contenido'] ?? 'Mensaje sin contenido.');
                $display_tipo = htmlspecialchars($mensaje['tipo'] ?? 'info');
            } else {
                $display_contenido = htmlspecialchars((string) $mensaje);
            }
        ?>
            <div class="alert alert-<?= $display_tipo ?> alert-dismissible fade show" role="alert">
                <?php if (is_array($mensaje) && isset($mensaje['titulo'])): ?>
                    <h4 class="alert-heading"><?= htmlspecialchars($mensaje['titulo']) ?></h4>
                <?php endif; ?>
                <?= $display_contenido ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
        <?php 
            unset($_SESSION['mensaje']);
        }
        ?>
        
        <?php 
        if (isset($_SESSION['error'])) {
            $error = $_SESSION['error'];
            $display_contenido = '';
            $display_tipo = 'danger';

            if (is_array($error)) {
                $display_contenido = htmlspecialchars($error['contenido'] ?? 'Mensaje de error sin contenido.');
                $display_tipo = htmlspecialchars($error['tipo'] ?? 'danger');
            } else {
                $display_contenido = htmlspecialchars((string) $error);
            }
        ?>
            <div class="alert alert-<?= $display_tipo ?> alert-dismissible fade show" role="alert">
                <?php if (is_array($error) && isset($error['titulo'])): ?>
                    <h4 class="alert-heading"><?= htmlspecialchars($error['titulo']) ?></h4>
                <?php endif; ?>
                <?= $display_contenido ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
        <?php 
            unset($_SESSION['error']);
        }
        ?>

        <!-- BUSCADOR -->
        <div class="card mb-4 border-primary">
            <div class="card-body">
                <form method="GET" action="gestion_personal.php">
                    <div class="input-group">
                        <input type="text" 
                               class="form-control form-control-lg" 
                               name="q" 
                               placeholder="Buscar por nombre, apellido, cédula, RIF o pasaporte..."
                               value="<?= isset($_GET['q']) ? htmlspecialchars($_GET['q']) : '' ?>">
                        <button class="btn btn-primary" type="submit">
                            <i class="fas fa-search me-2"></i>Buscar
                        </button>
                        <?php if (!empty($searchTerm)): ?>
                            <a href="gestion_personal.php" class="btn btn-outline-danger ms-2">
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
                            <th></th>
                            <th>Nombre Completo</th>
                            <th>Identificación</th>
                            <th>Contacto</th>
                            <th>Detalles</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($registros as $registro): ?>
                        <tr data-bs-toggle="collapse" href="#detalle-<?= $registro['id_pers'] ?>" role="button">
                            <td><?= htmlspecialchars($registro['id_pers']) ?></td>
                            <td>
                                <?= htmlspecialchars($registro['nombres'] . ' ' . $registro['apellidos']) ?><br>
                                <small class="text-muted"><?= htmlspecialchars($registro['genero']) ?></small>
                            </td>
                            <td>
                                <div class="badge badge-custom">
                                    <?= htmlspecialchars($registro['cedula_identidad'] ?? $registro['rif']) ?>
                                </div>
                                <?php if(isset($registro['pasaporte']) && $registro['pasaporte'] && $registro['pasaporte'] != 'NO APLICA'): ?>
                                    <div class="mt-1"><small>Pasaporte: <?= htmlspecialchars($registro['pasaporte']) ?></small></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <i class="fas fa-phone"></i> <?= htmlspecialchars($registro['telefono_contacto']) ?><br>
                                <i class="fas fa-envelope"></i> <?= htmlspecialchars($registro['correo_electronico']) ?>
                            </td>
                            <td>
                                <i class="fas fa-birthday-cake"></i> <?= htmlspecialchars(date('d/m/Y', strtotime($registro['fecha_nacimiento']))) ?><br>
                                <i class="fas fa-flag"></i> <?= htmlspecialchars($registro['nacionalidad']) ?>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    <a href="edit/editdatos_personal.php?id=<?= htmlspecialchars($registro['id_pers']) ?>" 
                                       class="btn btn-sm btn-warning"
                                       title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="id_pers" value="<?= htmlspecialchars($registro['id_pers']) ?>">
                                        <button type="submit" 
                                                name="eliminar_registro"
                                                class="btn btn-sm btn-danger"
                                                title="Eliminar"
                                                onclick="return confirm('¿Borrar permanentemente este registro?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <tr class="detail-row">
                            <td colspan="6" class="detail-cell">
                                <div class="collapse collapsible-content" id="detalle-<?= htmlspecialchars($registro['id_pers']) ?>">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <h6><i class="fas fa-address-card me-2"></i>Dirección</h6>
                                            <p><?= htmlspecialchars($registro['direccion']) ?></p>
                                            
                                            <h6><i class="fas fa-id-card me-2"></i>Seguro Social</h6>
                                            <p><?= htmlspecialchars($registro['numero_seguro_social'] ?? 'No registrado') ?></p>

                                             <h6><i class="fas fa-id-card me-2"></i>RIF</h6>
                                            <p><?= htmlspecialchars($registro['rif'] ?? 'No registrado') ?></p>
                                        </div>
                                        <div class="col-md-4">
                                            <h6><i class="fas fa-exclamation-triangle me-2"></i>Emergencia</h6>
                                            <p>
                                                Contacto: <?= htmlspecialchars($registro['nombre_contacto_emergencia'] ?? '') ?> <?= htmlspecialchars($registro['apellido_contacto_emergencia'] ?? '') ?><br>
                                                Teléfono: <?= htmlspecialchars($registro['telefono_contacto_emergencia'] ?? '') ?>
                                            </p>
                                            
                                            <h6><i class="fas fa-car me-2"></i>Licencia</h6>
                                            <p>
                                                <?= ($registro['tiene_licencia_conducir'] ?? '') === 'Sí' 
                                                    ? 'Tipo: ' . htmlspecialchars($registro['detalle_licencia'] ?? '')
                                                    : 'No posee' ?>
                                            </p>
                                        </div>
                                        <div class="col-md-4">
                                            <h6><i class="fas fa-wheelchair me-2"></i>Discapacidad</h6>
                                            <p>
                                                <?= ($registro['tiene_discapacidad'] ?? '') === 'Sí'
                                                    ? 'Detalle: ' . htmlspecialchars($registro['detalle_discapacidad'] ?? '')
                                                    : 'No registrada' ?>
                                            </p>
                                            
                                            <h6><i class="fas fa-database me-2"></i>Metadata</h6>
                                            <p>
                                                Registro: <?= htmlspecialchars(date('d/m/Y H:i', strtotime($registro['fecha_registro']))) ?><br>
                                                Última actualización: <?= htmlspecialchars(date('d/m/Y H:i', strtotime($registro['fecha_actualizacion']))) ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <nav aria-label="Navegación de páginas" class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php if ($pagina_actual > 1): ?>
                        <li class="page-item">
                            <a class="page-link" 
                               href="gestion_personal.php?pagina=<?= htmlspecialchars($pagina_actual - 1) ?><?= !empty($searchTerm) ? '&q='.urlencode($searchTerm) : '' ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                    <?php 
                    $pagina_inicio = max(1, $pagina_actual - 2);
                    $pagina_fin = min($total_paginas, $pagina_actual + 2);
                    for ($i = $pagina_inicio; $i <= $pagina_fin; $i++): 
                    ?>
                        <li class="page-item <?= htmlspecialchars($i == $pagina_actual ? 'active' : '') ?>">
                            <a class="page-link" 
                               href="gestion_personal.php?pagina=<?= htmlspecialchars($i) ?><?= !empty($searchTerm) ? '&q='.urlencode($searchTerm) : '' ?>">
                                <?= htmlspecialchars($i) ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    <?php if ($pagina_actual < $total_paginas): ?>
                        <li class="page-item">
                            <a class="page-link" 
                               href="gestion_personal.php?pagina=<?= htmlspecialchars($pagina_actual + 1) ?><?= !empty($searchTerm) ? '&q='.urlencode($searchTerm) : '' ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php else: ?>
            <div class="card no-results-card py-5 text-center">
                <div class="card-body">
                    <i class="fas fa-search no-results-icon"></i>
                    <h3 class="no-results-title">No se encontraron registros</h3>
                    <p class="no-results-text">
                        <?php if (!empty($searchTerm)): ?>
                            No encontramos resultados para: <strong><?= htmlspecialchars($searchTerm) ?></strong>
                        <?php else: ?>
                            No hay datos personales registrados
                        <?php endif; ?>
                    </p>
                    <p class="no-results-suggest">
                        <?php if (!empty($searchTerm)): ?>
                            <i class="fas fa-lightbulb me-2"></i>Sugerencias:
                        <?php endif; ?>
                    </p>
                    <ul class="list-unstyled">
                        <?php if (!empty($searchTerm)): ?>
                            <li>• Verifica la ortografía de tu búsqueda</li>
                            <li>• Intenta con términos más generales</li>
                            <li>• Prueba buscar solo por nombre o apellido</li>
                        <?php else: ?>
                            <li>• Agrega un nuevo registro usando el botón "Nuevo Registro"</li>
                        <?php endif; ?>
                    </ul>
                    <?php if (!empty($searchTerm)): ?>
                        <a href="gestion_personal.php" class="btn btn-primary mt-3">
                            <i class="fas fa-redo me-2"></i>Ver todos los registros
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <div class="modal fade" id="notificationModal" tabindex="-1" aria-labelledby="notificationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="notificationModalLabel"><i class="fas fa-bell me-2"></i>Notificaciones de Datos Faltantes</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="missingSocioecoSection" class="mb-4">
                        <h6><i class="fas fa-users me-2 text-warning"></i>Datos Socioeconómicos Pendientes:</h6>
                        <ul class="list-group list-group-flush" id="missingSocioecoList"></ul>
                        <?php if (empty($missing_socioeconomic_data)): ?>
                            <div class="alert alert-success mt-2">No hay datos socioeconómicos pendientes.</div>
                        <?php endif; ?>
                    </div>
                    <div id="missingLaboralSection">
                        <h6><i class="fas fa-briefcase me-2 text-warning"></i>Datos Laborales Pendientes:</h6>
                        <ul class="list-group list-group-flush" id="missingLaboralList"></ul>
                        <?php if (empty($missing_laboral_data)): ?>
                            <div class="alert alert-success mt-2">No hay datos laborales pendientes.</div>
                        <?php endif; ?>
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
        // Tema claro / oscuro
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

            // Tooltips Bootstrap
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            })

            // Notificaciones de datos faltantes
            const missingSocioecoData = <?= json_encode($missing_socioeconomic_data) ?>;
            const missingLaboralData = <?= json_encode($missing_laboral_data) ?>;

            const missingSocioecoList = document.getElementById('missingSocioecoList');
            const missingLaboralList = document.getElementById('missingLaboralList');

            function populateMissingList(data, listElement, formPath) {
                listElement.innerHTML = '';
                if (data.length > 0) {
                    data.forEach(person => {
                        const listItem = document.createElement('li');
                        listItem.classList.add('list-group-item', 'd-flex', 'justify-content-between', 'align-items-center');
                        listItem.innerHTML = `
                            <span>${person.nombres} ${person.apellidos} (C.I.: ${person.cedula_identidad})</span>
                            <a href="${formPath}?id_pers=${person.id_pers}&nombres=${encodeURIComponent(person.nombres)}&apellidos=${encodeURIComponent(person.apellidos)}" class="btn btn-sm btn-primary">
                                <i class="fas fa-arrow-right me-1"></i> Completar
                            </a>
                        `;
                        listElement.appendChild(listItem);
                    });
                }
            }

            const notificationModal = document.getElementById('notificationModal');
            notificationModal.addEventListener('show.bs.modal', function () {
                populateMissingList(missingSocioecoData, missingSocioecoList, 'form/form_datossocioeco.php');
                populateMissingList(missingLaboralData, missingLaboralList, 'form/form_tregister.php');
            });
        });
    </script>
</body>
</html>