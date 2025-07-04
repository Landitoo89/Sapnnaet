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
    // Eliminar registro permanentemente
    if (isset($_POST['eliminar_permanentemente']) && isset($_POST['confirm_text']) && $_POST['confirm_text'] === 'ELIMINAR') {
        $id_pers = $_POST['id_pers_delete'];
        $cedula_identidad_log = 'Desconocida'; 

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
            $_SESSION['mensaje'] = [
                'titulo' => '¡Eliminación Exitosa!',
                'contenido' => 'Registro eliminado permanentemente.',
                'tipo' => 'success'
            ];

            log_action($conexion, 'Eliminación Personal Permanente', 'Se eliminó permanentemente el registro del personal con CI: ' . $cedula_identidad_log . ' (ID: ' . $id_pers . ')');
        } catch (mysqli_sql_exception $e) {
            $conexion->rollback();
            $_SESSION['error'] = [
                'titulo' => 'Error de Eliminación',
                'contenido' => 'Error al eliminar el registro: ' . $e->getMessage(),
                'tipo' => 'danger'
            ];
            log_action($conexion, 'Error de Eliminación', 'Fallo al intentar eliminar el registro del personal con CI: ' . $cedula_identidad_log . ' (ID: ' . $id_pers . '). Error: ' . $e->getMessage());
        }
    }
    // Poner inactivo
    else if (isset($_POST['inactivar_registro'])) {
        $id_pers = $_POST['id_pers_inactivate'];
        $causa_inactivo = trim($_POST['causa_inactivo']);
        $cedula_identidad_log = 'Desconocida';

        // Obtener cédula y ID laboral para el log y la actualización
        $stmt_get_info = $conexion->prepare("SELECT dp.cedula_identidad, dl.id_laboral FROM datos_personales dp LEFT JOIN datos_laborales dl ON dp.id_pers = dl.id_pers WHERE dp.id_pers = ?");
        $stmt_get_info->bind_param("i", $id_pers);
        $stmt_get_info->execute();
        $result_info = $stmt_get_info->get_result();
        $id_laboral = null;
        if ($result_info->num_rows > 0) {
            $row_info = $result_info->fetch_assoc();
            $cedula_identidad_log = $row_info['cedula_identidad'];
            $id_laboral = $row_info['id_laboral'];
        }
        $stmt_get_info->close();

        if (empty($causa_inactivo)) {
            $_SESSION['error'] = [
                'titulo' => 'Error de Inactivación',
                'contenido' => 'La causa para inactivar al empleado es obligatoria.',
                'tipo' => 'danger'
            ];
        } elseif ($id_laboral === null) {
            $_SESSION['error'] = [
                'titulo' => 'Error de Inactivación',
                'contenido' => 'No se encontró un registro laboral asociado para inactivar al empleado.',
                'tipo' => 'danger'
            ];
        } else {
            try {
                // Actualizar la tabla datos_laborales
                $stmt_inactivar = $conexion->prepare("UPDATE datos_laborales SET estado = 'inactivo', causa_inactivo = ? WHERE id_laboral = ?");
                $stmt_inactivar->bind_param("si", $causa_inactivo, $id_laboral);
                if ($stmt_inactivar->execute()) {
                    $_SESSION['mensaje'] = [
                        'titulo' => '¡Inactivación Exitosa!',
                        'contenido' => 'El empleado ha sido puesto en estado "inactivo" correctamente.',
                        'tipo' => 'success'
                    ];
                    log_action($conexion, 'Inactivación Personal', 'Se inactivó al personal con CI: ' . $cedula_identidad_log . ' (ID: ' . $id_pers . '). Causa: ' . $causa_inactivo);
                } else {
                    $_SESSION['error'] = [
                        'titulo' => 'Error de Inactivación',
                        'contenido' => 'Error al inactivar el registro: ' . $stmt_inactivar->error,
                        'tipo' => 'danger'
                    ];
                    log_action($conexion, 'Error de Inactivación', 'Fallo al intentar inactivar el registro del personal con CI: ' . $cedula_identidad_log . ' (ID: ' . $id_pers . '). Error: ' . $stmt_inactivar->error);
                }
                $stmt_inactivar->close();
            } catch (mysqli_sql_exception $e) {
                $_SESSION['error'] = [
                    'titulo' => 'Error de Inactivación',
                    'contenido' => 'Error inesperado al inactivar: ' . $e->getMessage(),
                    'tipo' => 'danger'
                ];
                log_action($conexion, 'Error de Inactivación', 'Excepción al inactivar el registro del personal con CI: ' . $cedula_identidad_log . ' (ID: ' . $id_pers . '). Excepción: ' . $e->getMessage());
            }
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
    $searchCondition = " WHERE (dp.nombres LIKE ? OR dp.apellidos LIKE ? OR dp.cedula_identidad LIKE ? OR dp.rif LIKE ? OR dp.pasaporte LIKE ?) ";
    $searchTermLike = "%$searchTerm%";
    $params = array_fill(0, 5, $searchTermLike);
}

// Configuración de paginación
$registros_por_pagina = 10;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

// Obtener total de registros
// Modificamos la consulta para contar registros de datos_personales, que es la base
$sql_total = "SELECT COUNT(*) AS total FROM datos_personales dp
              LEFT JOIN datos_laborales dl ON dp.id_pers = dl.id_pers
              LEFT JOIN estados e ON dp.id_estado = e.id_estado
              LEFT JOIN municipios m ON dp.id_municipio = m.id_municipio
              LEFT JOIN parroquias p ON dp.id_parroquia = p.id_parroquia
              $searchCondition";
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

// Modificar esta consulta para incluir los campos de datos_laborales
$sql = "SELECT dp.id_pers, dp.nombres, dp.apellidos, dp.cedula_identidad, dp.pasaporte, dp.rif, dp.genero,
               dp.fecha_nacimiento, dp.nacionalidad, dp.correo_electronico, dp.telefono_contacto,
               dp.telefono_contacto_secundario, dp.nombre_contacto_emergencia, dp.apellido_contacto_emergencia,
               dp.telefono_contacto_emergencia, dp.tiene_discapacidad, dp.detalle_discapacidad, dp.carnet_discapacidad_imagen,
               dp.tiene_licencia_conducir, dp.tipo_licencia, dp.licencia_vencimiento, dp.licencia_imagen,
               dp.numero_seguro_social, dp.direccion, dp.fecha_registro, dp.fecha_actualizacion,
               dl.estado AS estado_laboral, dl.causa_inactivo, -- Traemos estado y causa_inactivo de datos_laborales
               e.nombre AS nombre_estado, m.nombre AS nombre_municipio, p.nombre AS nombre_parroquia
        FROM datos_personales dp
        LEFT JOIN datos_laborales dl ON dp.id_pers = dl.id_pers -- Unimos con datos_laborales
        LEFT JOIN estados e ON dp.id_estado = e.id_estado
        LEFT JOIN municipios m ON dp.id_municipio = m.id_municipio
        LEFT JOIN parroquias p ON dp.id_parroquia = p.id_parroquia
        $searchCondition ORDER BY dp.fecha_registro DESC LIMIT ? OFFSET ?";
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
        .badge-estado {
            padding: 0.4em 0.8em;
            border-radius: 0.75rem;
            font-weight: bold;
            color: white;
            text-shadow: 0 1px 1px rgba(0,0,0,0.2);
        }
        .badge-estado.activo { background-color: #28a745; } /* Green */
        .badge-estado.inactivo { background-color: #dc3545; } /* Red */
        .badge-estado.vacaciones { background-color: #ffc107; color: #333; } /* Yellow */
        .badge-estado.reposo { background-color: #17a2b8; } /* Teal */

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
        .image-thumbnail {
            max-width: 100px;
            max-height: 100px;
            border-radius: 8px;
            object-fit: cover;
            margin-top: 5px;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>
<!-- BOTÓN MODO OSCURO/MÓDULO CLARO -->
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
                            <th>ID</th>
                            <th>Nombre Completo</th>
                            <th>Identificación</th>
                            <th>Contacto</th>
                            <th>Estado Laboral</th> <!-- Cambiado a Estado Laboral -->
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($registros as $registro): ?>
                        <tr data-bs-toggle="collapse" data-bs-target="#detalle-<?= htmlspecialchars($registro['id_pers']) ?>" aria-expanded="false" aria-controls="detalle-<?= htmlspecialchars($registro['id_pers']) ?>" role="button">
                            <td><?= htmlspecialchars($registro['id_pers']) ?></td>
                            <td>
                                <?= htmlspecialchars($registro['nombres'] . ' ' . $registro['apellidos']) ?><br>
                                <small class="text-muted"><?= htmlspecialchars($registro['genero']) ?></small>
                            </td>
                            <td>
                                <div class="badge badge-custom">
                                    C.I.: <?= htmlspecialchars($registro['cedula_identidad']) ?>
                                </div>
                                <?php if(isset($registro['rif']) && !empty($registro['rif'])): ?>
                                    <div class="mt-1"><small>RIF: <?= htmlspecialchars($registro['rif']) ?></small></div>
                                <?php endif; ?>
                                <?php if(isset($registro['pasaporte']) && $registro['pasaporte'] && $registro['pasaporte'] != 'NO POSEE'): ?>
                                    <div class="mt-1"><small>Pasaporte: <?= htmlspecialchars($registro['pasaporte']) ?></small></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <i class="fas fa-phone"></i> <?= htmlspecialchars($registro['telefono_contacto']) ?><br>
                                <?php if (!empty($registro['telefono_contacto_secundario'])): ?>
                                    <i class="fas fa-phone-alt"></i> <?= htmlspecialchars($registro['telefono_contacto_secundario']) ?><br>
                                <?php endif; ?>
                                <i class="fas fa-envelope"></i> <?= htmlspecialchars($registro['correo_electronico']) ?>
                            </td>
                            <td>
                                <!-- Mostramos el estado de datos_laborales -->
                                <span class="badge badge-estado <?= strtolower(htmlspecialchars($registro['estado_laboral'] ?? 'N/A')) ?>">
                                    <?= htmlspecialchars(ucfirst($registro['estado_laboral'] ?? 'N/A')) ?>
                                </span>
                                <?php if (($registro['estado_laboral'] ?? '') === 'inactivo' && !empty($registro['causa_inactivo'])): ?>
                                    <br><small class="text-muted" title="Causa de inactividad">Causa: <?= htmlspecialchars($registro['causa_inactivo']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    <a href="edit/editdatos_personal.php?id=<?= htmlspecialchars($registro['id_pers']) ?>" 
                                       class="btn btn-sm btn-warning"
                                       title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    
                                    <button type="button" 
                                            class="btn btn-sm btn-danger delete-action-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#deleteActionModal"
                                            data-id-pers="<?= htmlspecialchars($registro['id_pers']) ?>"
                                            title="Eliminar/Inactivar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr class="detail-row">
                            <td colspan="6" class="detail-cell">
                                <div class="collapse collapsible-content" id="detalle-<?= htmlspecialchars($registro['id_pers']) ?>">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <h6><i class="fas fa-map-marker-alt me-2"></i>Dirección</h6>
                                            <p><?= htmlspecialchars($registro['direccion']) ?></p>
                                            <p><small>Estado: <?= htmlspecialchars($registro['nombre_estado'] ?? 'N/A') ?>, Municipio: <?= htmlspecialchars($registro['nombre_municipio'] ?? 'N/A') ?>, Parroquia: <?= htmlspecialchars($registro['nombre_parroquia'] ?? 'N/A') ?></small></p>
                                            
                                            <h6><i class="fas fa-user-friends me-2"></i>Contacto de Emergencia</h6>
                                            <?php if (!empty($registro['nombre_contacto_emergencia']) || !empty($registro['apellido_contacto_emergencia']) || !empty($registro['telefono_contacto_emergencia'])): ?>
                                                <p>
                                                    Nombre: <?= htmlspecialchars($registro['nombre_contacto_emergencia'] ?? '') ?> <?= htmlspecialchars($registro['apellido_contacto_emergencia'] ?? '') ?><br>
                                                    Teléfono: <?= htmlspecialchars($registro['telefono_contacto_emergencia'] ?? '') ?>
                                                </p>
                                            <?php else: ?>
                                                <p>No registrado</p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-4">
                                            <h6><i class="fas fa-wheelchair me-2"></i>Discapacidad</h6>
                                            <p>
                                                <?= ($registro['tiene_discapacidad'] ?? '') === 'Sí'
                                                    ? 'Detalle: ' . htmlspecialchars($registro['detalle_discapacidad'] ?? 'No especificado')
                                                    : 'No posee discapacidad' ?>
                                            </p>
                                            <?php if (!empty($registro['carnet_discapacidad_imagen'])): ?>
                                                <p>Carnet: <a href="<?= htmlspecialchars($registro['carnet_discapacidad_imagen']) ?>" target="_blank">Ver imagen</a></p>
                                                <img src="<?= htmlspecialchars($registro['carnet_discapacidad_imagen']) ?>" alt="Carnet de Discapacidad" class="img-fluid image-thumbnail">
                                            <?php elseif (($registro['tiene_discapacidad'] ?? '') === 'Sí'): ?>
                                                <p class="text-muted">Imagen de carnet no disponible.</p>
                                            <?php endif; ?>

                                            <h6><i class="fas fa-car-side me-2"></i>Licencia de Conducir</h6>
                                            <p>
                                                <?= ($registro['tiene_licencia_conducir'] ?? '') === 'Sí' 
                                                    ? 'Tipo: ' . htmlspecialchars($registro['tipo_licencia'] ?? 'No especificado') . '<br>Vencimiento: ' . htmlspecialchars(date('d/m/Y', strtotime($registro['licencia_vencimiento'])))
                                                    : 'No posee licencia' ?>
                                            </p>
                                            <?php if (!empty($registro['licencia_imagen'])): ?>
                                                <p>Licencia: <a href="<?= htmlspecialchars($registro['licencia_imagen']) ?>" target="_blank">Ver imagen</a></p>
                                                <img src="<?= htmlspecialchars($registro['licencia_imagen']) ?>" alt="Licencia de Conducir" class="img-fluid image-thumbnail">
                                            <?php elseif (($registro['tiene_licencia_conducir'] ?? '') === 'Sí'): ?>
                                                <p class="text-muted">Imagen de licencia no disponible.</p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-4">
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

    <!-- Modal de Notificaciones -->
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

    <!-- Nuevo Modal para Acciones de Eliminación/Inactivación -->
    <div class="modal fade" id="deleteActionModal" tabindex="-1" aria-labelledby="deleteActionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteActionModalLabel"><i class="fas fa-exclamation-triangle me-2"></i>Seleccionar Acción</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-center lead">¿Qué acción deseas realizar para el empleado con ID: <strong id="modal-id-pers"></strong>?</p>
                    
                    <ul class="nav nav-pills nav-fill mb-3" id="pills-tab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="pills-delete-tab" data-bs-toggle="pill" data-bs-target="#pills-delete" type="button" role="tab" aria-controls="pills-delete" aria-selected="true">
                                <i class="fas fa-trash-alt me-2"></i>Eliminar Permanentemente
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="pills-inactivate-tab" data-bs-toggle="pill" data-bs-target="#pills-inactivate" type="button" role="tab" aria-controls="pills-inactivate" aria-selected="false">
                                <i class="fas fa-user-slash me-2"></i>Poner Inactivo
                            </button>
                        </li>
                    </ul>
                    <div class="tab-content" id="pills-tabContent">
                        <!-- Pestaña Eliminar Permanentemente -->
                        <div class="tab-pane fade show active" id="pills-delete" role="tabpanel" aria-labelledby="pills-delete-tab">
                            <form id="form-delete-permanent" method="POST">
                                <input type="hidden" name="id_pers_delete" id="id_pers_delete_form">
                                <input type="hidden" name="eliminar_permanentemente" value="1">
                                <div class="alert alert-danger" role="alert">
                                    <i class="fas fa-exclamation-circle me-2"></i>¡Advertencia! Esta acción es irreversible y eliminará todos los datos asociados al empleado.
                                </div>
                                <div class="mb-3">
                                    <label for="confirm_text" class="form-label">Para confirmar, escribe "ELIMINAR" en el siguiente campo:</label>
                                    <input type="text" class="form-control" id="confirm_text" name="confirm_text" placeholder="ELIMINAR">
                                </div>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-danger" id="btn-confirm-delete" disabled>
                                        <i class="fas fa-times-circle me-2"></i>Confirmar Eliminación Permanente
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Pestaña Poner Inactivo -->
                        <div class="tab-pane fade" id="pills-inactivate" role="tabpanel" aria-labelledby="pills-inactivate-tab">
                            <form id="form-inactivate" method="POST">
                                <input type="hidden" name="id_pers_inactivate" id="id_pers_inactivate_form">
                                <input type="hidden" name="inactivar_registro" value="1">
                                <div class="mb-3">
                                    <label for="causa_inactivo" class="form-label">Causa de la inactividad*:</label>
                                    <textarea class="form-control" id="causa_inactivo" name="causa_inactivo" rows="3" required></textarea>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-user-slash me-2"></i>Poner Empleado Inactivo
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
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

            // Lógica para el nuevo modal de eliminación/inactivación
            const deleteActionModal = document.getElementById('deleteActionModal');
            deleteActionModal.addEventListener('show.bs.modal', function (event) {
                // Botón que disparó el modal
                const button = event.relatedTarget; 
                // Extraer información de los atributos data-*
                const idPers = button.getAttribute('data-id-pers');
                
                // Actualizar el ID en el modal
                const modalIdPersSpan = deleteActionModal.querySelector('#modal-id-pers');
                modalIdPersSpan.textContent = idPers;

                // Actualizar los campos ocultos de los formularios dentro del modal
                document.getElementById('id_pers_delete_form').value = idPers;
                document.getElementById('id_pers_inactivate_form').value = idPers;

                // Resetear el campo de confirmación de texto y el botón de eliminar
                const confirmTextInput = document.getElementById('confirm_text');
                const btnConfirmDelete = document.getElementById('btn-confirm-delete');
                confirmTextInput.value = '';
                btnConfirmDelete.disabled = true;

                // Resetear la pestaña activa a "Eliminar Permanentemente"
                const pillsDeleteTab = document.getElementById('pills-delete-tab');
                const pillsInactivateTab = document.getElementById('pills-inactivate-tab');
                const pillsDeleteContent = document.getElementById('pills-delete');
                const pillsInactivateContent = document.getElementById('pills-inactivate');

                pillsDeleteTab.classList.add('active');
                pillsDeleteContent.classList.add('show', 'active');
                pillsInactivateTab.classList.remove('active');
                pillsInactivateContent.classList.remove('show', 'active');

                // Limpiar el textarea de causa de inactividad
                document.getElementById('causa_inactivo').value = '';
            });

            // Habilitar/deshabilitar el botón de eliminación permanente
            const confirmTextInput = document.getElementById('confirm_text');
            const btnConfirmDelete = document.getElementById('btn-confirm-delete');
            confirmTextInput.addEventListener('input', function() {
                btnConfirmDelete.disabled = (this.value.toUpperCase() !== 'ELIMINAR');
            });
        });
    </script>
</body>
</html>
