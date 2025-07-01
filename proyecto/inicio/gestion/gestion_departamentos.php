<?php
session_start();
require 'conexion/conexion_db.php';
require $_SERVER['DOCUMENT_ROOT']."/proyecto/inicio/sidebar.php"; // Asegúrate de tener este archivo con la conexión a la BD

// Procesar acciones: agregar, editar, eliminar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        // Agregar nuevo departamento
        if ($action === 'add') {
            $nombre = trim($_POST['nombre']);
            if (!empty($nombre)) {
                $stmt = $conexion->prepare("INSERT INTO departamentos (nombre) VALUES (?)");
                $stmt->bind_param("s", $nombre);
                if ($stmt->execute()) {
                    $_SESSION['mensaje'] = "Departamento agregado correctamente";
                } else {
                    $_SESSION['error'] = "Error al agregar el departamento: " . $conexion->error;
                }
            }
        }
        
        // Editar departamento existente
        elseif ($action === 'edit') {
            $id = $_POST['id'];
            $nombre = trim($_POST['nombre']);
            if (!empty($nombre)) {
                $stmt = $conexion->prepare("UPDATE departamentos SET nombre = ? WHERE id_departamento = ?");
                $stmt->bind_param("si", $nombre, $id);
                if ($stmt->execute()) {
                    $_SESSION['mensaje'] = "Departamento actualizado correctamente";
                } else {
                    $_SESSION['error'] = "Error al actualizar el departamento: " . $conexion->error;
                }
            }
        }
        
        // Eliminar departamento
        elseif ($action === 'delete') {
    $id = $_POST['id'];
    
    // Iniciar transacción
    $conexion->begin_transaction();
    
    try {
        // 1. Eliminar todas las coordinaciones asociadas al departamento
        $stmt_coordinaciones = $conexion->prepare("DELETE FROM coordinaciones WHERE id_departamento = ?");
        $stmt_coordinaciones->bind_param("i", $id);
        $stmt_coordinaciones->execute();
        
        // 2. Eliminar el departamento
        $stmt_departamento = $conexion->prepare("DELETE FROM departamentos WHERE id_departamento = ?");
        $stmt_departamento->bind_param("i", $id);
        $stmt_departamento->execute();
        
        // Confirmar la transacción
        $conexion->commit();
        $_SESSION['mensaje'] = "Departamento y sus coordinaciones asociadas eliminados correctamente";
    } catch (Exception $e) {
        // Revertir la transacción en caso de error
        $conexion->rollback();
        $_SESSION['error'] = "Error al eliminar: " . $e->getMessage();
    }
}
        
        header("Location: gestion_departamentos.php");
        exit;
    }
}

// Obtener todos los departamentos
$sql = "SELECT * FROM departamentos";
$resultado = $conexion->query($sql);
$departamentos = $resultado->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Departamentos</title>
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
    </style>
</head>
<body>
    <div class="header-gradient">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-building me-3"></i>Gestión de Departamentos</h1>
                    <p class="lead">Administra los departamentos de tu organización</p>
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
                <a class="nav-link active" href="gestion_departamentos.php">Departamentos</a>
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
            <h2><i class="fas fa-list me-2"></i>Lista de Departamentos</h2>
            <div>
                <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addModal">
                    <i class="fas fa-plus-circle me-2"></i>Agregar Nuevo
                </button>
            </div>
        </div>

        <div class="data-table">
            <?php if (empty($departamentos)): ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i> No hay departamentos registrados.
                </div>
            <?php else: ?>
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($departamentos as $departamento): ?>
                            <tr>
                                <td><?= $departamento['id_departamento'] ?></td>
                                <td><?= htmlspecialchars($departamento['nombre']) ?></td>
                                <td class="action-buttons">
                                    <button class="btn btn-sm btn-warning edit-btn" 
                                            data-id="<?= $departamento['id_departamento'] ?>" 
                                            data-nombre="<?= htmlspecialchars($departamento['nombre']) ?>">
                                        <i class="fas fa-edit"></i> Editar
                                    </button>
                                    <button class="btn btn-sm btn-danger delete-btn" 
                                            data-id="<?= $departamento['id_departamento'] ?>" 
                                            data-nombre="<?= htmlspecialchars($departamento['nombre']) ?>">
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

    <!-- Modal para Agregar Departamento -->
    <div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="" method="post">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addModalLabel">Agregar Nuevo Departamento</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre del Departamento</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" 
                                   placeholder="Ej: Recursos Humanos" required>
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

    <!-- Modal para Editar Departamento -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="" method="post">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editModalLabel">Editar Departamento</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="mb-3">
                            <label for="edit_nombre" class="form-label">Nombre del Departamento</label>
                            <input type="text" class="form-control" id="edit_nombre" name="nombre" required>
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

    <!-- Modal para Eliminar Departamento -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="" method="post">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteModalLabel">Eliminar Departamento</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" id="delete_id">
                        <p>¿Estás seguro de que deseas eliminar el departamento "<span id="delete_nombre"></span>"?</p>
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
                
                document.getElementById('edit_id').value = id;
                document.getElementById('edit_nombre').value = nombre;
                
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