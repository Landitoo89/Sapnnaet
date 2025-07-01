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

// Acciones POST (eliminar)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['eliminar_registro'])) {
        $id_laboral = $_POST['id_laboral'];
        
        try {
            $stmt_select = $conexion->prepare("
                SELECT dl.*, dp.nombres, dp.apellidos, dp.cedula_identidad
                FROM datos_laborales dl
                JOIN datos_personales dp ON dl.id_pers = dp.id_pers
                WHERE dl.id_laboral = ?
            ");
            $stmt_select->bind_param("i", $id_laboral);
            $stmt_select->execute();
            $registro_eliminado = $stmt_select->get_result()->fetch_assoc();
            $stmt_select->close();
            
            $pdo = new PDO("mysql:host=$servidor;dbname=$basedatos", $usuario, $contraseña);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt_delete_primas = $pdo->prepare("DELETE FROM empleado_primas WHERE id_laboral = ?");
            $stmt_delete_primas->execute([$id_laboral]);

            $stmt_delete = $pdo->prepare("DELETE FROM datos_laborales WHERE id_laboral = ?");
            $stmt_delete->execute([$id_laboral]);
            
            $detalles = "ID Laboral: $id_laboral, ";
            $detalles .= "Trabajador: {$registro_eliminado['nombres']} {$registro_eliminado['apellidos']}, ";
            $detalles .= "Cédula: {$registro_eliminado['cedula_identidad']}, ";
            $detalles .= "Cargo: {$registro_eliminado['id_cargo']}, ";
            $detalles .= "Departamento: {$registro_eliminado['id_departamento']}";
            
            registrarLog($conexion, $_SESSION['usuario']['id'], 'laboral_deleted', $detalles);
            
            $_SESSION['mensaje'] = [
                'titulo' => '¡Éxito!',
                'contenido' => 'Registro laboral eliminado correctamente.',
                'tipo' => 'success'
            ];
        } catch (PDOException $e) {
            registrarLog($conexion, $_SESSION['usuario']['id'], 'laboral_delete_error', 
                        "Error al eliminar ID Laboral: $id_laboral - " . $e->getMessage());
            
            $_SESSION['mensaje'] = [
                'titulo' => 'Error',
                'contenido' => 'Error al eliminar el registro laboral: ' . $e->getMessage(),
                'tipo' => 'danger'
            ];
        }
        
        header("Location: gestion_laboral.php");
        exit;
    }
}

$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$busqueda_like = "%$busqueda%";

$registros_por_pagina = 10;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_actual < 1) $pagina_actual = 1;

$condicion_busqueda = '';
$parametros = array();
$tipos = '';

if (!empty($busqueda)) {
    $condicion_busqueda = " WHERE (dp.nombres LIKE ? OR dp.apellidos LIKE ? OR dp.cedula_identidad LIKE ?) ";
    $parametros = array_fill(0, 3, $busqueda_like);
    $tipos = str_repeat('s', count($parametros));
}

$sql_count = "SELECT COUNT(*) AS total 
              FROM datos_laborales dl
              LEFT JOIN datos_personales dp ON dl.id_pers = dp.id_pers
              $condicion_busqueda";

$stmt_count = $conexion->prepare($sql_count);
if (!empty($parametros)) {
    $stmt_count->bind_param($tipos, ...$parametros);
}
$stmt_count->execute();
$resultado_count = $stmt_count->get_result();
$total_registros = $resultado_count->fetch_assoc()['total'];
$total_paginas = ceil($total_registros / $registros_por_pagina);

if ($pagina_actual > $total_paginas && $total_paginas > 0) {
    $pagina_actual = $total_paginas;
}

$offset = ($pagina_actual - 1) * $registros_por_pagina;

$sql = "SELECT dl.*, 
                 dp.nombres, dp.apellidos, dp.cedula_identidad,
                 d.nombre AS nombre_departamento,
                 c.nombre AS nombre_cargo,
                 tc.nombre AS tipo_contrato,
                 co.nombre AS nombre_coordinacion,
                 tp.nombre AS nombre_tipo_personal
          FROM datos_laborales dl
          LEFT JOIN datos_personales dp ON dl.id_pers = dp.id_pers
          LEFT JOIN departamentos d ON dl.id_departamento = d.id_departamento
          LEFT JOIN cargos c ON dl.id_cargo = c.id_cargo
          LEFT JOIN tipos_contrato tc ON dl.id_contrato = tc.id_contrato
          LEFT JOIN coordinaciones co ON dl.id_coordinacion = co.id_coordinacion
          LEFT JOIN tipos_personal tp ON dl.id_tipo_personal = tp.id_tipo_personal
          $condicion_busqueda
          ORDER BY dl.fecha_registro DESC
          LIMIT ? OFFSET ?";

$stmt = $conexion->prepare($sql);

if (!empty($parametros)) {
    $tipos .= 'ii';
    $parametros[] = $registros_por_pagina;
    $parametros[] = $offset;
    $stmt->bind_param($tipos, ...$parametros);
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
    <title>Gestión de Datos Laborales</title>
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
        .badge-custom {
            background: var(--secondary-color);
            padding: 8px 12px;
            border-radius: 20px;
        }
        .badge-estado {
            padding: 8px 12px;
            border-radius: 20px;
            color: white;
        }
        .activo { background-color: #28a745; }
        .vacaciones { background-color: #ffc107; color: #000; }
        .inactivo { background-color: #dc3545; }
        .reposo { background-color: #17a2b8; }
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
        .funciones-text {
            max-height: 150px;
            overflow-y: auto;
            white-space: pre-wrap;
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
       <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link" href="gestion_personal.php">Datos Personales</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="gestion_socioeconomicos.php">Datos Socioeconómicos</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="gestion_carga.php">Carga Familiar</a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="gestion_laboral.php">Datos Laborales</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="gestion_contrato.php">Otros Paneles</a>
            </li>
        </ul>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-briefcase me-2"></i>Datos Laborales</h1>
            <div>
                <a href="form/form_register.php" class="btn btn-primary">
                    <i class="fas fa-plus-circle me-2"></i>Nuevo Registro
                </a>
            </div>
        </div>

        <?php 
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

        <div class="card mb-4 border-primary">
            <div class="card-body">
                <form method="GET" action="gestion_laboral.php">
                    <div class="input-group">
                        <input type="text" 
                               class="form-control form-control-lg" 
                               name="busqueda" 
                               placeholder="Buscar por nombre, apellido, cédula o ficha..." 
                               value="<?= htmlspecialchars($busqueda) ?>">
                        <button class="btn btn-primary" type="submit">
                            <i class="fas fa-search me-2"></i>Buscar
                        </button>
                        <?php if (!empty($busqueda)): ?>
                            <a href="gestion_laboral.php" class="btn btn-outline-danger ms-2">
                                <i class="fas fa-times me-2"></i>Limpiar
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <?php if (empty($registros)): ?>
            <div class="card no-results-card py-5 text-center">
                <div class="card-body">
                    <i class="fas fa-search no-results-icon"></i>
                    <h3 class="no-results-title">No se encontraron registros</h3>
                    <p class="no-results-text">
                        <?php if (!empty($busqueda)): ?>
                            No encontramos resultados para: <strong><?= htmlspecialchars($busqueda) ?></strong>
                        <?php else: ?>
                            No hay datos laborales registrados
                        <?php endif; ?>
                    </p>
                    <p class="no-results-suggest">
                        <?php if (!empty($busqueda)): ?>
                            <i class="fas fa-lightbulb me-2"></i>Sugerencias:
                        <?php endif; ?>
                    </p>
                    <ul class="list-unstyled">
                        <?php if (!empty($busqueda)): ?>
                            <li>• Verifica la ortografía de tu búsqueda</li>
                            <li>• Intenta con términos más generales</li>
                            <li>• Prueba buscar solo por nombre o apellido</li>
                        <?php else: ?>
                            <li>• Agrega un nuevo registro usando el botón "Nuevo Registro"</li>
                        <?php endif; ?>
                    </ul>
                    <?php if (!empty($busqueda)): ?>
                        <a href="gestion_laboral.php" class="btn btn-primary mt-3">
                            <i class="fas fa-redo me-2"></i>Ver todos los registros
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="data-table">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th></th>
                            <th>Trabajador</th>
                            <th>Departamento</th>
                            <th>Cargo</th>
                            <th>Contrato</th>
                            <th>Tipo Personal</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($registros as $registro): ?>
                        <tr data-bs-toggle="collapse" href="#detalle-<?= htmlspecialchars($registro['id_laboral']) ?>" role="button">
                            <td><?= htmlspecialchars($registro['id_laboral']) ?></td>
                            <td>
                                <?= htmlspecialchars($registro['nombres'] . ' ' . $registro['apellidos']) ?><br>
                                <small class="text-muted">C.I.: <?= htmlspecialchars($registro['cedula_identidad']) ?></small><br>
                                <small><i class="fas fa-envelope"></i> <?= htmlspecialchars($registro['correo_institucional']) ?></small>
                            </td>
                            <td><?= htmlspecialchars($registro['nombre_departamento'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($registro['nombre_cargo'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($registro['tipo_contrato'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($registro['nombre_tipo_personal'] ?? 'N/A') ?></td>
                            <td>
                                <?php 
                                    $claseEstado = '';
                                    switch($registro['estado']) {
                                        case 'activo': $claseEstado = 'activo'; break;
                                        case 'vacaciones': $claseEstado = 'vacaciones'; break;
                                        case 'inactivo': $claseEstado = 'inactivo'; break;
                                        case 'reposo': $claseEstado = 'reposo'; break;
                                        default: $claseEstado = 'secondary'; break;
                                    }
                                ?>
                                <span class="badge badge-estado <?= $claseEstado ?>">
                                    <?= htmlspecialchars(ucfirst($registro['estado'])) ?>
                                </span>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    <a href="edit/editdatos_laboral.php?id=<?= htmlspecialchars($registro['id_laboral']) ?>" 
                                       class="btn btn-sm btn-warning"
                                       title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="id_laboral" value="<?= htmlspecialchars($registro['id_laboral']) ?>">
                                        <button type="submit" 
                                                name="eliminar_registro"
                                                class="btn btn-sm btn-danger"
                                                title="Eliminar"
                                                onclick="return confirm('¿Borrar permanentemente este registro laboral?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <tr class="detail-row">
                            <td colspan="9" class="detail-cell">
                                <div class="collapse collapsible-content" id="detalle-<?= htmlspecialchars($registro['id_laboral']) ?>">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6><i class="fas fa-calendar-alt me-2"></i>Fechas Clave</h6>
                                            <p>
                                                Fecha de Ingreso: <?= htmlspecialchars(date('d/m/Y', strtotime($registro['fecha_ingreso']))) ?><br>
                                                Fecha de Registro: <?= htmlspecialchars(date('d/m/Y H:i', strtotime($registro['fecha_registro']))) ?><br>
                                                Última Actualización: <?= htmlspecialchars(date('d/m/Y H:i', strtotime($registro['fecha_actualizacion']))) ?>
                                            </p>
                                            <h6><i class="fas fa-building me-2"></i>Coordinación</h6>
                                            <p><?= htmlspecialchars($registro['nombre_coordinacion'] ?? 'N/A') ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <h6><i class="fas fa-tasks me-2"></i>Descripción de Funciones</h6>
                                            <div class="funciones-text">
                                                <?= $registro['descripcion_funciones'] ? nl2br(htmlspecialchars($registro['descripcion_funciones'])) : 'No se ha registrado descripción de funciones' ?>
                                            </div>
                                        </div>
                                        <div class="col-md-12 mt-3">
                                            <h6><i class="fas fa-history me-2"></i>Experiencia Laboral Previa</h6>
                                            <?php if ($registro['ha_trabajado_anteriormente'] == 'Sí'): ?>
                                                <p>Ha trabajado anteriormente: Sí</p>
                                                <p>
                                                    Empresa: <?= htmlspecialchars($registro['nombre_empresa_anterior'] ?? 'N/A') ?><br>
                                                    Ingreso: <?= htmlspecialchars(date('d/m/Y', strtotime($registro['ano_ingreso_anterior'] ?? ''))) ?><br>
                                                    Culminación: <?= htmlspecialchars(date('d/m/Y', strtotime($registro['ano_culminacion_anterior'] ?? ''))) ?>
                                                </p>
                                            <?php else: ?>
                                                <p>No ha trabajado en otra empresa anteriormente.</p>
                                            <?php endif; ?>
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
                               href="gestion_laboral.php?pagina=<?= htmlspecialchars($pagina_actual - 1) ?><?= !empty($busqueda) ? '&busqueda='.urlencode($busqueda) : '' ?>">
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
                               href="gestion_laboral.php?pagina=<?= htmlspecialchars($i) ?><?= !empty($busqueda) ? '&busqueda='.urlencode($busqueda) : '' ?>">
                                <?= htmlspecialchars($i) ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    <?php if ($pagina_actual < $total_paginas): ?>
                        <li class="page-item">
                            <a class="page-link" 
                               href="gestion_laboral.php?pagina=<?= htmlspecialchars($pagina_actual + 1) ?><?= !empty($busqueda) ? '&busqueda='.urlencode($busqueda) : '' ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
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