<?php
session_start();
require 'conexion_archivero.php';
//require $_SERVER['DOCUMENT_ROOT']."/proyecto/inicio/sidebar.php";

//Verificar permisos de admin
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'admin') {
    header('Location: index.php');
    exit;
}

// Acciones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Cambiar rol
    if (isset($_POST['cambiar_rol'])) {
        $usuario_id = $_POST['usuario_id'];
        $nuevo_rol = $_POST['nuevo_rol'];
        if ($usuario_id != $_SESSION['usuario']['id']) {
            $stmt = $conexion->prepare("UPDATE usuarios SET rol = ? WHERE id = ?");
            $stmt->bind_param("si", $nuevo_rol, $usuario_id);
            if ($stmt->execute()) {
                $_SESSION['mensaje'] = "Rol actualizado correctamente";
            } else {
                $_SESSION['error'] = "Error al actualizar el rol";
            }
        } else {
            $_SESSION['error'] = "No puedes cambiar tu propio rol.";
        }
    }
    // Eliminar usuario
    if (isset($_POST['eliminar_usuario'])) {
        $usuario_id = $_POST['usuario_id'];
        if ($usuario_id != $_SESSION['usuario']['id']) {
            $stmt = $conexion->prepare("DELETE FROM usuarios WHERE id = ?");
            $stmt->bind_param("i", $usuario_id);
            if ($stmt->execute()) {
                $_SESSION['mensaje'] = "Usuario eliminado correctamente";
            } else {
                $_SESSION['error'] = "Error al eliminar el usuario";
            }
        } else {
            $_SESSION['error'] = "No puedes eliminarte a ti mismo.";
        }
    }

}

// Obtener usuarios (excepto el admin actual)
$stmt = $conexion->prepare("SELECT id, nombre, apellido, email, rol, creado_en, avatar FROM usuarios WHERE id != ?");
$stmt->bind_param("i", $_SESSION['usuario']['id']);
$stmt->execute();
$resultado = $stmt->get_result();
$usuarios = $resultado->fetch_all(MYSQLI_ASSOC);

// Mapeo para badges de roles
$badge_colors = [
    'admin' => 'badge-admin',
    'supervisor' => 'badge-supervisor',
    'empleado' => 'badge-empleado'
];
require $_SERVER['DOCUMENT_ROOT']."/proyecto/inicio/sidebar.php";

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --danger-color: #dc3545;
            --warning-color: #fd7e14;
            --success-color: #20c997;
        }
        .user-table {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .table thead {
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            color: white;
        }
        .role-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            border: none;
            outline: none;
        }
        .badge-empleado { background: #e9ecef; color: #495057; }
        .badge-admin { background: var(--secondary-color); color: #fff; }
        .badge-supervisor { background: var(--warning-color); color: #fff; }
        .avatar-user {
            width: 38px; height: 38px; border-radius: 50%;
            object-fit: cover; border: 2px solid #e9ecef; margin-right: 10px;
            vertical-align: middle; background: #f8f9fa;
        }
        .action-btn {
            transition: all 0.3s ease;
            min-width: 100px;
        }
        .btn-danger { background: var(--danger-color); border-color: var(--danger-color);}
        .btn-warning { background: var(--warning-color); border-color: var(--warning-color); color: #fff;}
        .btn-warning:hover { background: #fdae42; border-color: #fdae42; }
        .search-container { max-width: 400px; margin: 0 auto 2rem; }
        .filter-buttons .btn { margin: 0 5px; }
        .pagination { justify-content: center; margin-top: 2rem; }
        .table td, .table th { vertical-align: middle !important; }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-users-cog me-2"></i>Gestión de Usuarios</h1>
            <div>
                <a href="register-admin.php" class="btn btn-primary me-2">
                    <i class="fas fa-user-plus me-2"></i>Nuevo Usuario
                </a>
                <a href="../index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Panel
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

        <div class="search-container mb-4">
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-search"></i></span>
                <input type="text" id="searchInput" class="form-control" placeholder="Buscar usuarios...">
            </div>
        </div>
        <div class="filter-buttons text-center mb-4">
            <button class="btn btn-outline-primary filter-btn active" data-filter="all">Todos</button>
            <button class="btn btn-outline-primary filter-btn" data-filter="empleado">Empleados</button>
            <button class="btn btn-outline-primary filter-btn" data-filter="supervisor">Supervisores</button>
            <button class="btn btn-outline-primary filter-btn" data-filter="admin">Admins</button>
        </div>
        <div class="user-table">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Perfil</th>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Rol</th>
                        <th>Registro</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="userTableBody">
                    <?php foreach($usuarios as $usuario): ?>
                    <tr data-role="<?= $usuario['rol'] ?>">
                        <td>
                            <img src="<?= htmlspecialchars($usuario['avatar'] ?? '/proyecto/inicio/img/avatar-default.png') ?>" 
                                class="avatar-user" alt="avatar" referrerpolicy="no-referrer">
                        </td>
                        <td><?= htmlspecialchars($usuario['nombre']." ".$usuario['apellido']) ?></td>
                        <td><?= htmlspecialchars($usuario['email']) ?></td>
                        <td>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="usuario_id" value="<?= $usuario['id'] ?>">
                                <input type="hidden" name="nuevo_rol" value="<?= 
                                    $usuario['rol'] === 'admin' ? 'empleado' : (
                                        $usuario['rol'] === 'empleado' ? 'supervisor' :
                                        ($usuario['rol'] === 'supervisor' ? 'admin' : 'empleado')
                                    ) ?>">
                                <button type="submit" name="cambiar_rol" 
                                    class="role-badge <?= $badge_colors[$usuario['rol']] ?? 'badge-empleado' ?>"
                                    title="Clic para cambiar el rol">
                                    <?= ucfirst($usuario['rol']) ?>
                                </button>
                            </form>
                        </td>
                        <td><?= date('d/m/Y', strtotime($usuario['creado_en'])) ?></td>
                        <td>
                            <div class="d-flex gap-2">
                                <form method="POST" class="me-2 d-inline">
                                    <input type="hidden" name="usuario_id" value="<?= $usuario['id'] ?>">
                                    <button type="submit" name="eliminar_usuario" class="btn btn-sm btn-danger action-btn" 
                                            onclick="return confirm('¿Eliminar permanentemente este usuario?')">
                                        <i class="fas fa-trash-alt me-2"></i>Eliminar
                                    </button>
                                </form>
                                <a href="editar-usuario.php?id=<?= $usuario['id'] ?>" class="btn btn-sm btn-warning action-btn">
                                    <i class="fas fa-edit me-2"></i>Editar
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <!-- Paginación (solo mockup visual, implementar lógica real si deseas) -->
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination">
                <li class="page-item disabled"><a class="page-link" href="#">Anterior</a></li>
                <li class="page-item active"><a class="page-link" href="#">1</a></li>
                <li class="page-item"><a class="page-link" href="#">2</a></li>
                <li class="page-item"><a class="page-link" href="#">3</a></li>
                <li class="page-item"><a class="page-link" href="#">Siguiente</a></li>
            </ul>
        </nav>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Búsqueda en tiempo real
        document.getElementById('searchInput').addEventListener('input', function() {
            const filter = this.value.toLowerCase();
            const rows = document.querySelectorAll('#userTableBody tr');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });

        // Filtrado por rol
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                const filter = this.dataset.filter;
                const rows = document.querySelectorAll('#userTableBody tr');
                rows.forEach(row => {
                    if (filter === 'all') {
                        row.style.display = '';
                    } else {
                        row.style.display = row.dataset.role === filter ? '' : 'none';
                    }
                });
            });
        });

        // Confirmación eliminar
        document.querySelectorAll('form[method="POST"] [name="eliminar_usuario"]').forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (!confirm('¿Estás seguro de eliminar este usuario? Esta acción no puede deshacerse.')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>