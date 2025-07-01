<?php
session_start();
require '../conexion_archivero.php';

if (!isset($_SESSION['usuario'])) {
    header('Location: /proyecto/index.php');
    exit;
}

$user_id = $_SESSION['usuario']['id'];
$roles_permitidos = ['admin', 'supervisor', 'empleado'];
$avatares_predeterminados = [
    "/proyecto/inicio/img/avatar/hombre.png",
    "/proyecto/inicio/img/avatar/mujer.png",
    "/proyecto/inicio/img/avatar/hombre2.png",
    "/proyecto/inicio/img/avatar/mujer2.png",
    "/proyecto/inicio/img/avatar/hombre3.png",
    "/proyecto/inicio/img/avatar/mujer3.png",
    "/proyecto/inicio/img/avatar/hombre4.png",
    "/proyecto/inicio/img/avatar/mujer4.png",
];

$errores = [];
$exito = false;

// Cargar datos actuales
$stmt = $conexion->prepare("SELECT nombre, apellido, email, avatar, rol FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($nombre, $apellido, $email, $avatar_actual, $rol_actual);
$stmt->fetch();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $apellido = trim($_POST['apellido']);
    $avatar = $_POST['avatar'] ?? $avatar_actual;
    $email = trim($_POST['email']);

    // Validaciones
    if (empty($nombre)) $errores[] = "El nombre es requerido";
    if (empty($apellido)) $errores[] = "El apellido es requerido";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errores[] = "Email inválido";
    if (!in_array($avatar, $avatares_predeterminados)) $errores[] = "Avatar seleccionado inválido";

    // Si cambió el email, verificar que no exista en otro usuario
    if ($email !== $_SESSION['usuario']['correo']) {
        $stmt = $conexion->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
        $stmt->bind_param("si", $email, $user_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errores[] = "El email ya está en uso";
        }
        $stmt->close();
    }

    if (empty($errores)) {
        $stmt = $conexion->prepare("UPDATE usuarios SET nombre = ?, apellido = ?, email = ?, avatar = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $nombre, $apellido, $email, $avatar, $user_id);
        if ($stmt->execute()) {
            // Actualiza la sesión
            $_SESSION['usuario']['nombre'] = $nombre;
            $_SESSION['usuario']['apellido'] = $apellido;
            $_SESSION['usuario']['correo'] = $email;
            $_SESSION['usuario']['avatar'] = $avatar;
            $exito = true;
        } else {
            $errores[] = "Error al actualizar: " . $conexion->error;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi Perfil</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #3498db 0%, #2c3e50 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .profile-container {
            background: rgba(255,255,255,0.98);
            border-radius: 22px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.13);
            max-width: 420px;
            width: 100%;
            margin: 2.5rem auto;
            padding: 2.3rem 2.2rem 2.3rem 2.2rem;
        }
        .profile-header {
            text-align: center;
            margin-bottom: 1.3rem;
        }
        .avatar-profile {
            width: 70px; height: 70px; border-radius: 50%;
            object-fit: cover;
            margin-bottom: 0.7rem;
            border: 3px solid #007bff33;
        }
        .avatar-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            justify-content: center;
            margin-bottom: 1.2rem;
        }
        .avatar-option {
            border: 2.5px solid transparent;
            border-radius: 50%;
            padding: 2.5px;
            cursor: pointer;
            transition: border 0.2s, box-shadow 0.2s;
            position: relative;
        }
        .avatar-option.selected,
        .avatar-option:hover {
            border-color: #3498db;
            box-shadow: 0 0 10px #3498db55;
        }
        .avatar-option input[type="radio"] {
            display: none;
        }
        .avatar-option .checkmark {
            display: none;
            position: absolute;
            bottom: 1px;
            right: 2px;
            background: #3498db;
            color: #fff;
            border-radius: 50%;
            font-size: 1em;
            padding: 2px 5px;
        }
        .avatar-option.selected .checkmark {
            display: block;
        }
        .avatar-option img {
            width: 46px;
            height: 46px;
            object-fit: cover;
            border-radius: 50%;
            background: #e9ecef;
        }
        .disabled-input { background: #e9ecef !important; color: #8b8b8b !important;}
    </style>
</head>
<body>
    <div class="container">
        <div class="profile-container">
            <div class="profile-header">
                <img src="<?= htmlspecialchars($avatar_actual) ?>" alt="Avatar" class="avatar-profile mb-2">
                <h4 class="fw-bold mb-2"><?= htmlspecialchars($nombre . " " . $apellido) ?></h4>
                <span class="badge bg-secondary mb-2"><i class="fas fa-user-shield me-1"></i><?= ucfirst($rol_actual) ?></span><br>
                <small class="text-muted">Editar tu información personal</small>
            </div>
            <?php if ($exito): ?>
                <div class="alert alert-success">Perfil actualizado correctamente.</div>
            <?php endif; ?>
            <?php if (!empty($errores)): ?>
                <div class="alert alert-danger"><?php foreach($errores as $e) echo "<div>$e</div>"; ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label fw-bold">Avatar (elige uno):</label>
                    <div class="avatar-grid">
                        <?php foreach ($avatares_predeterminados as $avatar): ?>
                            <label class="avatar-option<?= ($avatar_actual == $avatar) || (isset($_POST['avatar']) && $_POST['avatar'] == $avatar) ? ' selected' : '' ?>">
                                <input type="radio" name="avatar" value="<?= htmlspecialchars($avatar) ?>" <?= ($avatar_actual == $avatar) || (isset($_POST['avatar']) && $_POST['avatar'] == $avatar) ? 'checked' : '' ?>>
                                <img src="<?= htmlspecialchars($avatar) ?>" alt="Avatar">
                                <span class="checkmark"><i class="fas fa-check"></i></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="nombre" class="form-label">Nombre</label>
                    <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($nombre) ?>" required>
                </div>
                <div class="mb-3">
                    <label for="apellido" class="form-label">Apellido</label>
                    <input type="text" name="apellido" class="form-control" value="<?= htmlspecialchars($apellido) ?>" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Correo electrónico</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($email) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Rol</label>
                    <input type="text" class="form-control disabled-input" value="<?= htmlspecialchars(ucfirst($rol_actual)) ?>" disabled readonly>
                </div>
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-save me-1"></i> Guardar cambios</button>
                <div class="text-center mt-3">
                    <a href="/proyecto/inicio/index.php" class="text-decoration-none"><i class="fas fa-arrow-left me-2"></i>Volver</a>
                </div>
            </form>
        </div>
    </div>
    <script>
        // Avatares tipo Netflix: selección visual
        document.querySelectorAll('.avatar-option input[type="radio"]').forEach(function(radio) {
            radio.addEventListener('change', function() {
                document.querySelectorAll('.avatar-option').forEach(function(opt) {
                    opt.classList.remove('selected');
                });
                this.closest('.avatar-option').classList.add('selected');
            });
        });
    </script>
</body>
</html>