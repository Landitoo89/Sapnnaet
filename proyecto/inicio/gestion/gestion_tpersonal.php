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

// ----------- ACCIONES POST -------------
// AGREGAR
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $nombre = trim($_POST['nombre']);
    if (!empty($nombre)) {
        $stmt = $conexion->prepare("INSERT INTO tipos_personal (nombre) VALUES (?)");
        $stmt->bind_param("s", $nombre);
        if ($stmt->execute()) {
            $_SESSION['mensaje'] = "Tipo de personal agregado correctamente.";
            registrarLog($conexion, $current_user_id, 'tpersonal_added', "Nuevo tipo de personal: $nombre");
        } else {
            $_SESSION['error'] = "Error al agregar el tipo de personal.";
        }
    } else {
        $_SESSION['error'] = "El nombre no puede estar vacío.";
    }
    header("Location: gestion_tpersonal.php");
    exit;
}

// EDITAR
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = $_POST['id'];
    $nombre = trim($_POST['nombre']);
    if (!empty($nombre)) {
        $stmt = $conexion->prepare("UPDATE tipos_personal SET nombre = ? WHERE id_tipo_personal = ?");
        $stmt->bind_param("si", $nombre, $id);
        if ($stmt->execute()) {
            $_SESSION['mensaje'] = "Tipo de personal actualizado correctamente.";
            registrarLog($conexion, $current_user_id, 'tpersonal_edited', "Editado tipo de personal: $nombre (ID: $id)");
        } else {
            $_SESSION['error'] = "Error al editar el tipo de personal.";
        }
    } else {
        $_SESSION['error'] = "El nombre no puede estar vacío.";
    }
    header("Location: gestion_tpersonal.php");
    exit;
}

// ELIMINAR
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_tpersonal'])) {
    $id_tipo_personal = $_POST['id_tipo_personal'];
    $stmt_info = $conexion->prepare("SELECT nombre FROM tipos_personal WHERE id_tipo_personal = ?");
    $stmt_info->bind_param("i", $id_tipo_personal);
    $stmt_info->execute();
    $res_info = $stmt_info->get_result();
    $tp_info = $res_info->fetch_assoc();
    $stmt_info->close();

    // Verificar si está en uso
    $stmt_check = $conexion->prepare("SELECT COUNT(*) AS total FROM datos_laborales WHERE id_tipo_personal = ?");
    $stmt_check->bind_param("i", $id_tipo_personal);
    $stmt_check->execute();
    $result = $stmt_check->get_result();
    $row = $result->fetch_assoc();

    if ($row['total'] > 0) {
        $_SESSION['error'] = "No se puede eliminar el tipo de personal porque está asignado a empleados.";
        if ($tp_info) {
            $log_details = "Intento fallido de eliminación de tipo de personal: {$tp_info['nombre']} (ID: $id_tipo_personal). Tipo en uso por empleados.";
            registrarLog($conexion, $current_user_id, 'tpersonal_delete_failed_in_use', $log_details);
        }
    } else {
        $stmt = $conexion->prepare("DELETE FROM tipos_personal WHERE id_tipo_personal = ?");
        $stmt->bind_param("i", $id_tipo_personal);
        if ($stmt->execute()) {
            $_SESSION['mensaje'] = "Tipo de personal eliminado correctamente";
            if ($tp_info) {
                $log_details = "Eliminación de tipo de personal: {$tp_info['nombre']} (ID: $id_tipo_personal).";
                registrarLog($conexion, $current_user_id, 'tpersonal_deleted', $log_details);
            }
        } else {
            $_SESSION['error'] = "Error al eliminar el tipo de personal";
            if ($tp_info) {
                $log_details = "Error al eliminar tipo de personal: {$tp_info['nombre']} (ID: $id_tipo_personal).";
                registrarLog($conexion, $current_user_id, 'tpersonal_delete_error', $log_details);
            }
        }
    }
    header("Location: gestion_tpersonal.php");
    exit;
}

// ----------- CONSULTAS PARA TABLAS Y ESTADISTICAS -------------
// Empleados asociados por tipo de personal
$stmt_empleados = $conexion->prepare("
    SELECT tp.id_tipo_personal, COUNT(dl.id_laboral) AS total_empleados
    FROM tipos_personal tp
    LEFT JOIN datos_laborales dl ON tp.id_tipo_personal = dl.id_tipo_personal
    GROUP BY tp.id_tipo_personal
");
$stmt_empleados->execute();
$result_empleados = $stmt_empleados->get_result();
$empleados_por_tipo = [];
while ($row = $result_empleados->fetch_assoc()) {
    $empleados_por_tipo[$row['id_tipo_personal']] = $row['total_empleados'];
}

// Paginación y búsqueda
$registros_por_pagina = 10;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$condicion = '';
$parametros = [];
if ($busqueda !== '') {
    $condicion = " WHERE nombre LIKE ? ";
    $parametros = ["%$busqueda%"];
}
// Total de registros
$sql_total = "SELECT COUNT(*) AS total FROM tipos_personal $condicion";
$stmt_total = $conexion->prepare($sql_total);
if (!empty($parametros)) {
    $stmt_total->bind_param(str_repeat('s', count($parametros)), ...$parametros);
}
$stmt_total->execute();
$total_registros = $stmt_total->get_result()->fetch_assoc()['total'];
$total_paginas = ceil($total_registros / $registros_por_pagina);
// Registros paginados
$sql = "SELECT * FROM tipos_personal $condicion ORDER BY id_tipo_personal DESC LIMIT ? OFFSET ?";
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
$tipos_personal = $resultado->fetch_all(MYSQLI_ASSOC);
require $_SERVER['DOCUMENT_ROOT']."/proyecto/inicio/sidebar.php";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Tipos de Personal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
        }
        .data-table {
            background: white;
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
            vertical-align: middle;
        }
        .action-btn {
            transition: all 0.3s ease;
            min-width: 100px;
        }
        .search-container {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
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
        .table-hover tbody tr:hover {
            background-color: rgba(52, 152, 219, 0.05);
        }
        .employee-badge {
            background-color: #4e73df;
            color: white;
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
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
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0"><i class="fas fa-users-cog me-2"></i>Tipos de Personal</h1>
            <div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                    <i class="fas fa-plus-circle me-2"></i>Nuevo Tipo
                </button>
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
                    <i class="fas fa-users-cog"></i>
                    <h3><?= $total_registros ?></h3>
                    <p class="mb-0">Tipos de Personal</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center" style="background: linear-gradient(135deg, #28a745, #20c997);">
                    <i class="fas fa-user-tie"></i>
                    <h3><?= array_sum($empleados_por_tipo) ?></h3>
                    <p class="mb-0">Empleados Asociados</p>
                </div>
            </div>
        </div>

        <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link" href="gestion_contrato.php">Contratos</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="gestion_cargos.php">Cargos</a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="gestion_tpersonal.php">Tipos de Personal</a>
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
                        placeholder="Buscar tipos de personal..."
                        value="<?= htmlspecialchars($busqueda) ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-lg w-100">
                        <i class="fas fa-search me-2"></i>Buscar
                    </button>
                </div>
                <div class="col-md-2">
                    <a href="gestion_tpersonal.php" class="btn btn-outline-secondary btn-lg w-100">
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
                        <th>Nombre</th>
                        <th>Empleados Asociados</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($tipos_personal) > 0): ?>
                        <?php foreach($tipos_personal as $tipo): ?>
                            <tr>
                                <td><?= $tipo['id_tipo_personal'] ?></td>
                                <td><strong><?= htmlspecialchars($tipo['nombre']) ?></strong></td>
                                <td>
                                    <span class="employee-badge">
                                        <?= $empleados_por_tipo[$tipo['id_tipo_personal']] ?? 0 ?> empleados
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <!-- Editar -->
                                        <button class="btn btn-sm btn-warning edit-btn"
                                            data-id="<?= $tipo['id_tipo_personal'] ?>"
                                            data-nombre="<?= htmlspecialchars($tipo['nombre']) ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <!-- Eliminar -->
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="id_tipo_personal" value="<?= $tipo['id_tipo_personal'] ?>">
                                            <button type="submit"
                                                name="eliminar_tpersonal"
                                                class="btn btn-sm btn-danger"
                                                title="Eliminar"
                                                onclick="return confirm('¿Está seguro de eliminar este tipo de personal?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center py-4">
                                <i class="fas fa-info-circle fa-2x mb-3 text-secondary"></i>
                                <h5>No se encontraron tipos de personal</h5>
                                <p class="text-muted">Intenta con otros términos de búsqueda o crea un nuevo tipo de personal.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        <?php if ($total_paginas > 1): ?>
        <nav aria-label="Navegación de páginas">
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                    <li class="page-item <?= $i == $pagina_actual ? 'active' : '' ?>">
                        <a class="page-link" href="gestion_tpersonal.php?pagina=<?= $i ?>&busqueda=<?= urlencode($busqueda) ?>">
                            <?= $i ?>
                        </a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>

    <!-- Modal Agregar -->
    <div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="" method="post">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addModalLabel">Agregar Nuevo Tipo</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre del Tipo</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Ej: Empleado Temporal" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="" method="post">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editModalLabel">Editar Tipo de Personal</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_nombre" class="form-label">Nombre del Tipo</label>
                            <input type="text" class="form-control" id="edit_nombre" name="nombre" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Editar: cargar datos en modal
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('edit_id').value = this.getAttribute('data-id');
                document.getElementById('edit_nombre').value = this.getAttribute('data-nombre');
                new bootstrap.Modal(document.getElementById('editModal')).show();
            });
        });

        // Cerrar alertas después de 5 segundos
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                bootstrap.Alert.getOrCreateInstance(alert).close();
            });
        }, 5000);
    </script>
</body>
</html>