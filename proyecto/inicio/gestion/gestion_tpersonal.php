<?php
session_start();
//if (!isset($_SESSION['usuario'])) {
//    header("Location: login.php");
//    exit();
//}

require 'conexion/conexion_db.php';
require $_SERVER['DOCUMENT_ROOT']."/proyecto/inicio/sidebar.php";

// Procesar acciones: agregar, editar, eliminar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        // Agregar nuevo tipo
        if ($action === 'add') {
            $nombre = trim($_POST['nombre']);
            if (!empty($nombre)) {
                $sql = "INSERT INTO tipos_personal (nombre) VALUES (?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$nombre]);
            }
        }
        
        // Editar tipo existente
        elseif ($action === 'edit') {
            $id = $_POST['id'];
            $nombre = trim($_POST['nombre']);
            if (!empty($nombre)) {
                $sql = "UPDATE tipos_personal SET nombre = ? WHERE id_tipo_personal = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$nombre, $id]);
            }
        }
        
        // Eliminar tipo
        elseif ($action === 'delete') {
            $id = $_POST['id'];
            $sql = "DELETE FROM tipos_personal WHERE id_tipo_personal = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id]);
        }
    }
}

// Obtener todos los tipos de personal
$sql = "SELECT * FROM tipos_personal";
$stmt = $pdo->query($sql);
$tipos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Tipos de Personal</title>
    <!-- Mismo Bootstrap que en gestion_laboral.php -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Mismo Font Awesome -->
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
        
        .badge-custom {
            background: var(--secondary-color);
            padding: 8px 12px;
            border-radius: 20px;
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
    <!-- Mismo encabezado que en gestion_laboral.php -->
    <div class="header-gradient">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-users-cog me-3"></i>Gestión de Tipos de Personal</h1>
                    <p class="lead">Administra los diferentes tipos de personal de tu organización</p>
                </div>
                <div class="col-md-4 text-end">
                    <span class="badge bg-light text-dark fs-6 p-2">Sistema de RRHH</span>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Mismas pestañas de navegación -->
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

        <!-- Mensajes de alerta (mismo estilo) -->
        <?php if (isset($_GET['success'])): ?>
            <div class="alert-container">
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i> 
                    <?php 
                        $success = $_GET['success'];
                        if ($success == 'add') echo "Tipo de personal agregado con éxito!";
                        elseif ($success == 'edit') echo "Tipo de personal actualizado con éxito!";
                        elseif ($success == 'delete') echo "Tipo de personal eliminado con éxito!";
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-list me-2"></i>Lista de Tipos</h2>
            <div>
                <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addModal">
                    <i class="fas fa-plus-circle me-2"></i>Agregar Nuevo
                </button>
            </div>
        </div>

        <div class="data-table">
            <?php if (empty($tipos)): ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i> No hay tipos de personal registrados.
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
                        <?php foreach ($tipos as $tipo): ?>
                            <tr>
                                <td><?= $tipo['id_tipo_personal'] ?></td>
                                <td><?= htmlspecialchars($tipo['nombre']) ?></td>
                                <td class="action-buttons">
                                    <button class="btn btn-sm btn-warning edit-btn" 
                                            data-id="<?= $tipo['id_tipo_personal'] ?>" 
                                            data-nombre="<?= htmlspecialchars($tipo['nombre']) ?>">
                                        <i class="fas fa-edit"></i> Editar
                                    </button>
                                    <button class="btn btn-sm btn-danger delete-btn" 
                                            data-id="<?= $tipo['id_tipo_personal'] ?>" 
                                            data-nombre="<?= htmlspecialchars($tipo['nombre']) ?>">
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

    <!-- Modales (mismo estilo) -->
    <!-- Modal para Agregar Tipo -->
    <div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="" method="post">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addModalLabel">Agregar Nuevo Tipo</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre del Tipo</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" 
                                   placeholder="Ej: Empleado Temporal" required>
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

    <!-- Modal para Editar Tipo -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="" method="post">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editModalLabel">Editar Tipo de Personal</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="mb-3">
                            <label for="edit_nombre" class="form-label">Nombre del Tipo</label>
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

    <!-- Modal para Eliminar Tipo -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="" method="post">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteModalLabel">Eliminar Tipo</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" id="delete_id">
                        <p>¿Estás seguro de que deseas eliminar el tipo "<span id="delete_nombre"></span>"?</p>
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

    <!-- Mismo Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Misma funcionalidad JS
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

        // Cerrar automáticamente las alertas después de 5 segundos
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                bootstrap.Alert.getOrCreateInstance(alert).close();
            });
        }, 5000);
    </script>
</body>
</html>