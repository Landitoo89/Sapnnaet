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

// Obtener coordinaciones para selects
$sql_coordinaciones = "SELECT * FROM coordinaciones ORDER BY nombre";
$resultado_coordinaciones = $conexion->query($sql_coordinaciones);
$coordinaciones = $resultado_coordinaciones->fetch_all(MYSQLI_ASSOC);

// --- ACCIONES POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Eliminar departamento
    if (isset($_POST['eliminar_departamento'])) {
        $id_departamento = $_POST['id_departamento'];
        $stmt_info = $conexion->prepare("SELECT nombre FROM departamentos WHERE id_departamento = ?");
        $stmt_info->bind_param("i", $id_departamento);
        $stmt_info->execute();
        $res_info = $stmt_info->get_result();
        $dep_info = $res_info->fetch_assoc();
        $stmt_info->close();

        $stmt_check = $conexion->prepare("SELECT COUNT(*) AS total FROM coordinaciones WHERE id_departamento = ?");
        $stmt_check->bind_param("i", $id_departamento);
        $stmt_check->execute();
        $result = $stmt_check->get_result();
        $row = $result->fetch_assoc();

        if ($row['total'] > 0) {
            $_SESSION['error'] = "No se puede eliminar el departamento porque tiene coordinaciones asociadas.";
            if ($dep_info) {
                $log_details = "Intento fallido de eliminación de departamento: {$dep_info['nombre']} (ID: $id_departamento). Departamento en uso por coordinaciones.";
                registrarLog($conexion, $current_user_id, 'departamento_delete_failed_in_use', $log_details);
            }
        } else {
            $stmt = $conexion->prepare("DELETE FROM departamentos WHERE id_departamento = ?");
            $stmt->bind_param("i", $id_departamento);

            if ($stmt->execute()) {
                $_SESSION['mensaje'] = "Departamento eliminado correctamente";
                if ($dep_info) {
                    $log_details = "Eliminación de departamento: {$dep_info['nombre']} (ID: $id_departamento).";
                    registrarLog($conexion, $current_user_id, 'departamento_deleted', $log_details);
                }
            } else {
                $_SESSION['error'] = "Error al eliminar el departamento";
                if ($dep_info) {
                    $log_details = "Error al eliminar departamento: {$dep_info['nombre']} (ID: $id_departamento).";
                    registrarLog($conexion, $current_user_id, 'departamento_delete_error', $log_details);
                }
            }
        }
        header("Location: gestion_departamentos.php");
        exit;
    }

    // AGREGAR
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $nombre = trim($_POST['nombre']);
        $id_coordinacion = intval($_POST['id_coordinacion']);
        if (!empty($nombre) && $id_coordinacion > 0) {
            $stmt = $conexion->prepare("INSERT INTO departamentos (nombre, id_coordinacion) VALUES (?, ?)");
            $stmt->bind_param("si", $nombre, $id_coordinacion);
            if ($stmt->execute()) {
                $_SESSION['mensaje'] = "Departamento agregado correctamente";
                registrarLog($conexion, $current_user_id, 'departamento_added', "Nuevo departamento: $nombre (Coordinación: $id_coordinacion)");
            } else {
                $_SESSION['error'] = "Error al agregar el departamento: " . $conexion->error;
            }
        } else {
            $_SESSION['error'] = "El nombre y la coordinación son obligatorios.";
        }
        header("Location: gestion_departamentos.php");
        exit;
    }

    // EDITAR
    if (isset($_POST['action']) && $_POST['action'] === 'edit') {
        $id = $_POST['id'];
        $nombre = trim($_POST['nombre']);
        $id_coordinacion = intval($_POST['id_coordinacion']);
        if (!empty($nombre) && $id_coordinacion > 0) {
            $stmt = $conexion->prepare("UPDATE departamentos SET nombre = ?, id_coordinacion = ? WHERE id_departamento = ?");
            $stmt->bind_param("sii", $nombre, $id_coordinacion, $id);
            if ($stmt->execute()) {
                $_SESSION['mensaje'] = "Departamento actualizado correctamente";
                registrarLog($conexion, $current_user_id, 'departamento_edited', "Departamento editado: $nombre (ID: $id, Coordinación: $id_coordinacion)");
            } else {
                $_SESSION['error'] = "Error al actualizar el departamento: " . $conexion->error;
            }
        } else {
            $_SESSION['error'] = "El nombre y la coordinación son obligatorios.";
        }
        header("Location: gestion_departamentos.php");
        exit;
    }
}

// --- Paginación y búsqueda ---
$registros_por_pagina = 10;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$condicion = '';
$parametros = [];
if ($busqueda !== '') {
    $condicion = " WHERE d.nombre LIKE ? ";
    $parametros = ["%$busqueda%"];
}
$sql_total = "SELECT COUNT(*) AS total FROM departamentos d $condicion";
$stmt_total = $conexion->prepare($sql_total);
if (!empty($parametros)) {
    $stmt_total->bind_param(str_repeat('s', count($parametros)), ...$parametros);
}
$stmt_total->execute();
$total_registros = $stmt_total->get_result()->fetch_assoc()['total'];
$total_paginas = ceil($total_registros / $registros_por_pagina);

$sql = "SELECT d.*, c.nombre AS nombre_coordinacion
        FROM departamentos d
        LEFT JOIN coordinaciones c ON d.id_coordinacion = c.id_coordinacion
        $condicion
        ORDER BY d.id_departamento DESC LIMIT ? OFFSET ?";
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
$departamentos = $resultado->fetch_all(MYSQLI_ASSOC);

require $_SERVER['DOCUMENT_ROOT']."/proyecto/inicio/sidebar.php";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Departamentos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary-color: #2c3e50; --secondary-color: #3498db; }
        .data-table { background: white; border-radius: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); overflow: hidden; margin-bottom: 2rem;}
        .table thead { background: linear-gradient(to right, var(--primary-color), var(--secondary-color)); color: white;}
        .table th { font-weight: 500; vertical-align: middle;}
        .action-btn { transition: all 0.3s ease; min-width: 100px;}
        .search-container { background-color: #f8f9fa; border-radius: 10px; padding: 1.5rem; margin-bottom: 1.5rem; box-shadow: 0 2px 10px rgba(0,0,0,0.05);}
        .stats-card { background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white; border-radius: 10px; padding: 1.5rem; margin-bottom: 1.5rem; box-shadow: 0 4px 15px rgba(0,0,0,0.1);}
        .stats-card i { font-size: 2.5rem; margin-bottom: 1rem;}
        .stats-card h3 { font-size: 1.8rem; margin-bottom: 0;}
        .pagination .page-item.active .page-link { background-color: var(--secondary-color); border-color: var(--secondary-color);}
        .pagination .page-link { color: var(--primary-color);}
        .btn-primary { background-color: var(--secondary-color); border-color: var(--secondary-color);}
        .btn-primary:hover { background-color: #258cd1; border-color: #258cd1;}
        .table-hover tbody tr:hover { background-color: rgba(52, 152, 219, 0.05);}
        .nav-tabs .nav-link { border: none; color: var(--primary-color); font-weight: 500;}
        .nav-tabs .nav-link.active { border-bottom: 3px solid var(--secondary-color); color: var(--secondary-color);}
        .badge-coordinacion { background-color: #6c757d; color: white; padding: 3px 8px; border-radius: 10px; font-size: 0.85rem;}
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0"><i class="fas fa-building me-2"></i>Departamentos</h1>
            <div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                    <i class="fas fa-plus-circle me-2"></i>Nuevo Departamento
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
                    <i class="fas fa-building"></i>
                    <h3><?= $total_registros ?></h3>
                    <p class="mb-0">Departamentos</p>
                </div>
            </div>
        </div>

        <ul class="nav nav-tabs mb-4">
            <li class="nav-item"><a class="nav-link" href="gestion_contrato.php">Contratos</a></li>
            <li class="nav-item"><a class="nav-link" href="gestion_cargos.php">Cargos</a></li>
            <li class="nav-item"><a class="nav-link" href="gestion_tpersonal.php">Tipos de Personal</a></li>
            <li class="nav-item"><a class="nav-link active" href="gestion_departamentos.php">Departamentos</a></li>
            <li class="nav-item"><a class="nav-link" href="gestion_coordinaciones.php">Coordinaciones</a></li>
            <li class="nav-item"><a class="nav-link" href="gestion_primas.php">Primas</a></li>
            <li class="nav-item"><a class="nav-link" href="gestion_personal.php">Volver a Personal</a></li>
        </ul>

        <div class="search-container">
            <form method="GET" class="row g-3">
                <div class="col-md-8">
                    <input type="text" name="busqueda" class="form-control form-control-lg"
                           placeholder="Buscar departamentos..." 
                           value="<?= htmlspecialchars($busqueda) ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-lg w-100">
                        <i class="fas fa-search me-2"></i>Buscar
                    </button>
                </div>
                <div class="col-md-2">
                    <a href="gestion_departamentos.php" class="btn btn-outline-secondary btn-lg w-100">
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
                        <th>Coordinación</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($departamentos) > 0): ?>
                        <?php foreach($departamentos as $departamento): ?>
                            <tr>
                                <td><?= $departamento['id_departamento'] ?></td>
                                <td><strong><?= htmlspecialchars($departamento['nombre']) ?></strong></td>
                                <td>
                                    <?php if ($departamento['nombre_coordinacion']): ?>
                                        <span class="badge-coordinacion"><?= htmlspecialchars($departamento['nombre_coordinacion']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">Sin coordinación asignada</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <!-- Editar -->
                                        <button class="btn btn-sm btn-warning edit-btn"
                                            data-id="<?= $departamento['id_departamento'] ?>"
                                            data-nombre="<?= htmlspecialchars($departamento['nombre']) ?>"
                                            data-id_coordinacion="<?= $departamento['id_coordinacion'] ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <!-- Eliminar -->
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="id_departamento" value="<?= $departamento['id_departamento'] ?>">
                                            <button type="submit"
                                                name="eliminar_departamento"
                                                class="btn btn-sm btn-danger"
                                                title="Eliminar"
                                                onclick="return confirm('¿Está seguro de eliminar este departamento?')">
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
                                <h5>No se encontraron departamentos</h5>
                                <p class="text-muted">Intenta con otros términos de búsqueda o crea un nuevo departamento.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_paginas > 1): ?>
        <nav aria-label="Navegación de páginas">
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                    <li class="page-item <?= $i == $pagina_actual ? 'active' : '' ?>">
                        <a class="page-link" href="gestion_departamentos.php?pagina=<?= $i ?>&busqueda=<?= urlencode($busqueda) ?>">
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
                        <h5 class="modal-title" id="addModalLabel">Agregar Nuevo Departamento</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre del Departamento</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Ej: Recursos Humanos" required>
                        </div>
                        <div class="mb-3">
                            <label for="id_coordinacion" class="form-label">Coordinación *</label>
                            <select class="form-select" id="id_coordinacion" name="id_coordinacion" required>
                                <option value="" selected disabled>Seleccione coordinación...</option>
                                <?php foreach($coordinaciones as $coor): ?>
                                    <option value="<?= $coor['id_coordinacion'] ?>">
                                        <?= htmlspecialchars($coor['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
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
                        <h5 class="modal-title" id="editModalLabel">Editar Departamento</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_nombre" class="form-label">Nombre del Departamento</label>
                            <input type="text" class="form-control" id="edit_nombre" name="nombre" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_id_coordinacion" class="form-label">Coordinación *</label>
                            <select class="form-select" id="edit_id_coordinacion" name="id_coordinacion" required>
                                <option value="" selected disabled>Seleccione coordinación...</option>
                                <?php foreach($coordinaciones as $coor): ?>
                                    <option value="<?= $coor['id_coordinacion'] ?>">
                                        <?= htmlspecialchars($coor['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
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
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('edit_id').value = this.getAttribute('data-id');
                document.getElementById('edit_nombre').value = this.getAttribute('data-nombre');
                document.getElementById('edit_id_coordinacion').value = this.getAttribute('data-id_coordinacion');
                new bootstrap.Modal(document.getElementById('editModal')).show();
            });
        });
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                bootstrap.Alert.getOrCreateInstance(alert).close();
            });
        }, 5000);
    </script>
</body>
</html>