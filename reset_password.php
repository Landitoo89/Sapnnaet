<?php
session_start();
require 'conexion_archivero.php';

$errores = [];
$mensaje = '';
$tipo_mensaje = '';

$valid_session_token = false;
$user_id_from_session = null;

// Verificar que hay un token de sesión en la URL y que coincide con el almacenado
if (isset($_GET['token']) && isset($_SESSION['reset_active']) &&
    $_GET['token'] === $_SESSION['reset_active']['one_time_token'] &&
    time() < $_SESSION['reset_active']['expires']) {
    
    $valid_session_token = true;
    $user_id_from_session = $_SESSION['reset_active']['user_id'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $new_password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        if (empty($new_password) || empty($confirm_password)) {
            $errores[] = 'Por favor, ingresa y confirma tu nueva contraseña.';
        } elseif ($new_password !== $confirm_password) {
            $errores[] = 'Las contraseñas no coinciden.';
        } elseif (strlen($new_password) < 8) {
            $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            // Actualizar contraseña
            $stmt_update = $conexion->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
            $stmt_update->bind_param("si", $hashed_password, $user_id_from_session);

            if ($stmt_update->execute()) {
                $mensaje = 'Tu contraseña ha sido restablecida con éxito. Ahora puedes iniciar sesión.';
                $tipo_mensaje = 'success';
                
                // Limpiar la sesión de restablecimiento después de un éxito
                unset($_SESSION['reset_active']);
                
            } else {
                $errores[] = 'Hubo un error al restablecer tu contraseña. Inténtalo de nuevo.';
            }
        }
    }
} else {
    $errores[] = 'Acceso no autorizado o sesión de restablecimiento expirada/inválida. Por favor, inicia el proceso de recuperación de contraseña de nuevo.';
    // Si la sesión no es válida, limpia cualquier intento
    unset($_SESSION['reset_active']);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña - Sistema de Archivo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Tu CSS existente aquí */
        :root {
            --primary-color: #764ba2;
            --secondary-color: #667eea;
            --accent-color: #ff6b6b;
        }
        
        body {
            background: linear-gradient(135deg, var(--secondary-color) 0%, var(--primary-color) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .auth-container {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            width: 100%;
            max-width: 420px;
            margin: 2rem auto;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .auth-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
        }

        .auth-header {
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 1.5rem;
            text-align: center;
        }

        .auth-form {
            padding: 2rem 2.5rem;
        }

        .form-control {
            border-radius: 12px;
            padding: 14px 20px;
            transition: all 0.3s ease;
            border: 1px solid #e0e0e0;
        }

        .form-control:focus {
            box-shadow: 0 0 0 3px rgba(118, 75, 162, 0.2);
            border-color: var(--primary-color);
        }

        .input-group-text {
            background-color: transparent;
            border-right: none;
        }

        .btn-submit {
            color: white !important;
            text-shadow: 0 1px 1px rgba(0, 0, 0, 0.2);
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            border: none;
            padding: 14px;
            border-radius: 12px;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            width: 100%;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(118, 75, 162, 0.4);
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #666;
            z-index: 5;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .fade-in {
            animation: fadeIn 0.6s ease-out forwards;
        }
        .shake {
            animation: shake 0.5s linear;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-5px); }
            40%, 80% { transform: translateX(5px); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="auth-container fade-in">
            <div class="auth-header">
                <h2><i class="fas fa-key me-2"></i>Nueva Contraseña</h2>
                <p class="mb-0">Establece tu nueva contraseña.</p>
            </div>
            
            <form class="auth-form" method="POST" id="resetPasswordForm">
                <?php if (!empty($errores)): ?>
                    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php foreach ($errores as $error): ?>
                            <span><?= $error ?></span>
                        <?php endforeach; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php elseif ($mensaje): ?>
                    <div class="alert alert-<?= $tipo_mensaje ?> alert-dismissible fade show mb-4" role="alert">
                        <i class="fas fa-info-circle me-2"></i>
                        <?= $mensaje ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if ($valid_session_token && empty($mensaje)): // Solo muestra el formulario si la sesión es válida y no hay un mensaje de éxito ?>
                    <div class="mb-4 position-relative">
                        <label for="password" class="form-label">Nueva Contraseña</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                            <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required minlength="8">
                            <i class="bi bi-eye-slash password-toggle" id="togglePassword"></i>
                        </div>
                        <div class="form-text">Mínimo 8 caracteres.</div>
                    </div>
                    
                    <div class="mb-4 position-relative">
                        <label for="confirm_password" class="form-label">Confirmar Contraseña</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="••••••••" required>
                            <i class="bi bi-eye-slash password-toggle" id="toggleConfirmPassword"></i>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-submit mb-3" id="setPasswordBtn">
                        <i class="fas fa-check-circle me-2"></i> Restablecer Contraseña
                    </button>
                <?php endif; ?>

                <div class="text-center mt-3">
                    <p><a href="index.php" class="text-decoration-none">Volver al inicio de sesión</a></p>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Mostrar/ocultar contraseña
        function setupPasswordToggle(toggleId, passwordId) {
            const toggle = document.querySelector(toggleId);
            const password = document.querySelector(passwordId);
            
            if (toggle && password) {
                toggle.addEventListener('click', function() {
                    const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                    password.setAttribute('type', type);
                    this.classList.toggle('bi-eye');
                    this.classList.toggle('bi-eye-slash');
                });
            }
        }

        setupPasswordToggle('#togglePassword', '#password');
        setupPasswordToggle('#toggleConfirmPassword', '#confirm_password');

        const resetPasswordForm = document.getElementById('resetPasswordForm');
        const setPasswordBtn = document.getElementById('setPasswordBtn');
        
        if (resetPasswordForm) {
            resetPasswordForm.addEventListener('submit', function(e) {
                if (!this.checkValidity()) {
                    e.preventDefault();
                    e.stopPropagation();
                    this.classList.add('was-validated');
                    resetPasswordForm.classList.add('shake');
                    setTimeout(() => resetPasswordForm.classList.remove('shake'), 500);
                } else {
                    setPasswordBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Restableciendo...';
                    setPasswordBtn.disabled = true;
                }
            });
        }

        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('input', function() {
                if (this.checkValidity()) {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                } else {
                    this.classList.remove('is-valid');
                }
            });
        });
    </script>
</body>
</html>