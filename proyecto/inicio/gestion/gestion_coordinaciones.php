<?php
session_start();
require 'conexion/conexion_db.php';
require $_SERVER['DOCUMENT_ROOT']."/proyecto/inicio/sidebar.php";// Asegúrate de tener este archivo con la conexión a la BD

// Procesar acciones: agregar, editar, eliminar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        // Agregar nueva coordinación
        if ($action === 'add') {
            $nombre = trim($_POST['nombre']);
            $id_departamento = $_POST['id_departamento'];
            
            if (!empty($nombre) && !empty($id_departamento)) {
                $stmt = $conexion->prepare("INSERT INTO coordinaciones (nombre, id_departamento) VALUES (?, ?)");
                $stmt->bind_param("si", $nombre, $id_departamento);
                if ($stmt->execute()) {
                    $_SESSION['mensaje'] = "Coordinación agregada correctamente";
                } else {
                    $_SESSION['error'] = "Error al agregar la coordinación: " . $conexion->error;
                }
            }
        }
        
        // Editar coordinación existente
        elseif ($action === 'edit') {
            $id = $_POST['id'];
            $nombre = trim($_POST['nombre']);
            $id_departamento = $_POST['id_departamento'];
            
            if (!empty($nombre) && !empty($id_departamento)) {
                $stmt = $conexion->prepare("UPDATE coordinaciones SET nombre = ?, id_departamento = ? WHERE id_coordinacion = ?");
                $stmt->bind_param("sii", $nombre, $id_departamento, $id);
                if ($stmt->execute()) {
                    $_SESSION['mensaje'] = "Coordinación actualizada correctamente";
                } else {
                    $_SESSION['error'] = "Error al actualizar la coordinación: " . $conexion->error;
                }
            }
        }
        
        // Eliminar coordinación
        elseif ($action === 'delete') {
            $id = $_POST['id'];
            $stmt = $conexion->prepare("DELETE FROM coordinaciones WHERE id_coordinacion = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $_SESSION['mensaje'] = "Coordinación eliminada correctamente";
            } else {
                $_SESSION['error'] = "Error al eliminar la coordinación: " . $conexion->error;
            }
        }
        
        header("Location: gestion_coordinaciones.php");
        exit;
    }
}

// Obtener todas las coordinaciones con sus departamentos
$sql = "SELECT c.*, d.nombre AS nombre_departamento 
        FROM coordinaciones c
        LEFT JOIN departamentos d ON c.id_departamento = d.id_departamento";
$resultado = $conexion->query($sql);
$coordinaciones = $resultado->fetch_all(MYSQLI_ASSOC);

// Obtener todos los departamentos para los dropdowns
$sql_departamentos = "SELECT * FROM departamentos";
$resultado_departamentos = $conexion->query($sql_departamentos);
$departamentos = $resultado_departamentos->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Coordinaciones</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Mismo estilo que en gestion_laboral.php */
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --danger-color: #dc3545;
        }
        
        .data-table {
            background: white;
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
        
        .header-gradient {
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 25px 0;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .btn-primary-custom {
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            border: none;
            transition: all 0.3s ease;
        }
        
        .btn-primary-custom:hover {
            background: linear-gradient(to right, #1a252f, #2980b9);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #b71c1c, #d32f2f);
            border: none;
        }
        
        .btn-danger:hover {
            background: linear-gradient(135deg, #8a1a1a, #a82424);
        }
        
        .btn-warning {
            background: linear-gradient(135deg, #e65100, #ef6c00);
            border: none;
        }
        
        .btn-warning:hover {
            background: linear-gradient(135deg, #b33c00, #c45c00);
        }
        
        .alert-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
            max-width: 400px;
        }
        
        .pagination .page-item.active .page-link {
            background: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .action-buttons .btn {
            margin-right: 5px;
            padding: 5px 10px;
            border-radius: 5px;
        }
        
        .badge-departamento {
            background-color: #6c757d;
            color: white;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <div class="header-gradient">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-sitemap me-3"></i>Gestión de Coordinaciones</h1>
                    <p class="lead">Administra las coordinaciones de tu organización</p>
                </div>
                <div class="col-md-4 text-end">
                    <span class="badge bg-light text-dark fs-6 p-2">Sistema de RRHH</span>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Pestañas de navegación -->
       <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link" href="gestion_contrato.php">Contratos</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="gestion_cargos.php">Cargos</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="gestion_tpersonal.php">Tipos de Personal</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="gestion_departamentos.php">Departamentos</a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="gestion_coordinaciones.php">Coordinaciones</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="gestion_primas.php">Primas</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="gestion_personal.php">Volver a Personal</a>
            </li>
        </ul>
        <!-- Mensajes de alerta -->
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
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-list me-2"></i>Lista de Coordinaciones</h2>
            <div>
                <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addModal">
                    <i class="fas fa-plus-circle me-2"></i>Agregar Nuevo
                </button>
            </div>
        </div>

        <div class="data-table">
            <?php if (empty($coordinaciones)): ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i> No hay coordinaciones registradas.
                </div>
            <?php else: ?>
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Departamento</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($coordinaciones as $coordinacion): ?>
                            <tr>
                                <td><?= $coordinacion['id_coordinacion'] ?></td>
                                <td><?= htmlspecialchars($coordinacion['nombre']) ?></td>
                                <td>
                                    <?php if ($coordinacion['nombre_departamento']): ?>
                                        <span class="badge-departamento">
                                            <?= htmlspecialchars($coordinacion['nombre_departamento']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">Sin departamento asignado</span>
                                    <?php endif; ?>
                                </td>
                                <td class="action-buttons">
                                    <button class="btn btn-sm btn-warning edit-btn" 
                                            data-id="<?= $coordinacion['id_coordinacion'] ?>" 
                                            data-nombre="<?= htmlspecialchars($coordinacion['nombre']) ?>"
                                            data-id_departamento="<?= $coordinacion['id_departamento'] ?>">
                                        <i class="fas fa-edit"></i> Editar
                                    </button>
                                    <button class="btn btn-sm btn-danger delete-btn" 
                                            data-id="<?= $coordinacion['id_coordinacion'] ?>" 
                                            data-nombre="<?= htmlspecialchars($coordinacion['nombre']) ?>">
                                        <i class="fas fa-trash-alt"></i> Eliminar
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal para Agregar Coordinación -->
    <div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="" method="post">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addModalLabel">Agregar Nueva Coordinación</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre de la Coordinación</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" 
                                   placeholder="Ej: Coordinación de Informática" required>
                        </div>
                        <div class="mb-3">
                            <label for="id_departamento" class="form-label">Departamento</label>
                            <select class="form-select" id="id_departamento" name="id_departamento" required>
                                <option value="" selected disabled>Seleccionar departamento...</option>
                                <?php foreach($departamentos as $departamento): ?>
                                    <option value="<?= $departamento['id_departamento'] ?>">
                                        <?= htmlspecialchars($departamento['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary-custom">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para Editar Coordinación -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="" method="post">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editModalLabel">Editar Coordinación</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="mb-3">
                            <label for="edit_nombre" class="form-label">Nombre de la Coordinación</label>
                            <input type="text" class="form-control" id="edit_nombre" name="nombre" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_id_departamento" class="form-label">Departamento</label>
                            <select class="form-select" id="edit_id_departamento" name="id_departamento" required>
                                <option value="" disabled>Seleccionar departamento...</option>
                                <?php foreach($departamentos as $departamento): ?>
                                    <option value="<?= $departamento['id_departamento'] ?>">
                                        <?= htmlspecialchars($departamento['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary-custom">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para Eliminar Coordinación -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="" method="post">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteModalLabel">Eliminar Coordinación</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" id="delete_id">
                        <p>¿Estás seguro de que deseas eliminar la coordinación "<span id="delete_nombre"></span>"?</p>
                        <p class="text-danger"><i class="fas fa-exclamation-triangle me-2"></i>Esta acción no se puede deshacer.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Eliminar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Funcionalidad para los botones de editar
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const nombre = this.getAttribute('data-nombre');
                const id_departamento = this.getAttribute('data-id_departamento');
                
                document.getElementById('edit_id').value = id;
                document.getElementById('edit_nombre').value = nombre;
                document.getElementById('edit_id_departamento').value = id_departamento;
                
                const editModal = new bootstrap.Modal(document.getElementById('editModal'));
                editModal.show();
            });
        });

        // Funcionalidad para los botones de eliminar
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const nombre = this.getAttribute('data-nombre');
                
                document.getElementById('delete_id').value = id;
                document.getElementById('delete_nombre').textContent = nombre;
                
                const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
                deleteModal.show();
            });
        });
    </script>
</body>
</html>