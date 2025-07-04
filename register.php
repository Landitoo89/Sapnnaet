<?php
require 'conexion_archivero.php';
require 'auth_functions.php';

$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $apellido = trim($_POST['apellido']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validaciones
    if (empty($nombre)) $errores[] = "El nombre es requerido";
    if (empty($apellido)) $errores[] = "El apellido es requerido";
    if (!validarEmail($email)) $errores[] = "Email inválido";
    if (strlen($password) < 8) $errores[] = "La contraseña debe tener al menos 8 caracteres";
    if ($password !== $confirm_password) $errores[] = "Las contraseñas no coinciden";

    // Verificar email único
    $stmt = $conexion->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $errores[] = "El email ya está registrado";
    }

    if (empty($errores)) {
        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $conexion->prepare("INSERT INTO usuarios (nombre, apellido, email, password) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $nombre, $apellido, $email, $password_hash);
        
        if ($stmt->execute()) {
            header('Location: index.php?registro=exito');
            exit;
        } else {
            $errores[] = "Error al registrar el usuario";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Sistema de RRHH</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
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
            max-width: 450px;
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

        .btn-register {
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            border: none;
            padding: 14px;
            border-radius: 12px;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            width: 100%;
            color: white !important;
            text-shadow: 0 1px 1px rgba(0, 0, 0, 0.2);
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(118, 75, 162, 0.4);
            color: white;
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

        .auth-links a {
            color: #666;
            text-decoration: none;
            transition: color 0.3s ease;
            font-size: 0.9rem;
        }

        .auth-links a:hover {
            color: var(--primary-color);
        }

        .password-strength {
            height: 5px;
            background-color: #eee;
            border-radius: 5px;
            margin-top: 5px;
            overflow: hidden;
        }

        .strength-bar {
            height: 100%;
            width: 0%;
            transition: width 0.3s ease, background-color 0.3s ease;
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

        /* Responsive adjustments */
        @media (max-width: 576px) {
            .auth-container {
                margin: 1rem;
                border-radius: 15px;
            }
            
            .auth-form {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="auth-container fade-in">
            <div class="auth-header">
                <h2><i class="fas fa-user-tie me-2"></i> Registro de Personal</h2>
                <p class="mb-0">Crea tu cuenta para comenzar</p>
            </div>
            
            <form class="auth-form" method="POST" id="registerForm">
                <?php if (!empty($errores)): ?>
                    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php foreach ($errores as $error): ?>
                            <span><?= $error ?></span>
                        <?php endforeach; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="nombre" class="form-label">Nombre</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person-fill"></i></span>
                            <input type="text" name="nombre" id="nombre" class="form-control" placeholder="Ej. Oliver" required>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="apellido" class="form-label">Apellido</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person-badge-fill"></i></span>
                            <input type="text" name="apellido" id="apellido" class="form-control" placeholder="Ej. Félix" required>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="email" class="form-label">Correo electrónico</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope-fill"></i></span>
                        <input type="email" name="email" id="email" class="form-control" placeholder="tu@email.com" required>
                    </div>
                </div>
                
                <div class="mb-3 position-relative">
                    <label for="password" class="form-label">Contraseña</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                        <input type="password" name="password" id="password" class="form-control" placeholder="Mínimo 8 caracteres" required>
                        <i class="bi bi-eye-slash password-toggle" id="togglePassword"></i>
                    </div>
                    <div class="password-strength mt-2">
                        <div class="strength-bar" id="strengthBar"></div>
                    </div>
                    <small class="text-muted">La contraseña debe tener al menos 8 caracteres</small>
                </div>
                
                <div class="mb-4 position-relative">
                    <label for="confirm_password" class="form-label">Confirmar Contraseña</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Repite tu contraseña" required>
                        <i class="bi bi-eye-slash password-toggle" id="toggleConfirmPassword"></i>
                    </div>
                    <div id="passwordMatch" class="mt-1" style="font-size: 0.8rem;"></div>
                </div>
                
                <button type="submit" class="btn btn-register mb-3" id="registerBtn">
                    <i class="fas fa-user-plus me-2"></i> Registrarse
                </button>
                
                <div class="text-center mt-3">
                    <p>¿Ya tienes una cuenta? <a href="index.php" class="fw-bold">Inicia Sesión</a></p>
                </div>
            </form>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Mostrar/ocultar contraseña
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');
        const toggleConfirmPassword = document.querySelector('#toggleConfirmPassword');
        const confirmPassword = document.querySelector('#confirm_password');
        
        togglePassword.addEventListener('click', function() {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('bi-eye');
            this.classList.toggle('bi-eye-slash');
        });
        
        toggleConfirmPassword.addEventListener('click', function() {
            const type = confirmPassword.getAttribute('type') === 'password' ? 'text' : 'password';
            confirmPassword.setAttribute('type', type);
            this.classList.toggle('bi-eye');
            this.classList.toggle('bi-eye-slash');
        });

        // Validación de fortaleza de contraseña
        password.addEventListener('input', function() {
            const strengthBar = document.getElementById('strengthBar');
            const strength = calculatePasswordStrength(this.value);
            
            strengthBar.style.width = strength.percentage + '%';
            strengthBar.style.backgroundColor = strength.color;
        });

        // Validación de coincidencia de contraseñas
        confirmPassword.addEventListener('input', function() {
            const matchElement = document.getElementById('passwordMatch');
            if (this.value && password.value) {
                if (this.value === password.value) {
                    matchElement.innerHTML = '<i class="bi bi-check-circle-fill text-success"></i> Las contraseñas coinciden';
                } else {
                    matchElement.innerHTML = '<i class="bi bi-exclamation-circle-fill text-danger"></i> Las contraseñas no coinciden';
                }
            } else {
                matchElement.innerHTML = '';
            }
        });

        // Efecto al enviar formulario
        const registerForm = document.getElementById('registerForm');
        const registerBtn = document.getElementById('registerBtn');
        
        registerForm.addEventListener('submit', function(e) {
            if (!this.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
                this.classList.add('was-validated');
                registerForm.classList.add('shake');
                setTimeout(() => registerForm.classList.remove('shake'), 500);
            } else {
                registerBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Registrando...';
                registerBtn.disabled = true;
            }
        });

        // Función para calcular fortaleza de contraseña
        function calculatePasswordStrength(password) {
            let strength = 0;
            if (password.length >= 8) strength += 1;
            if (password.match(/[a-z]/)) strength += 1;
            if (password.match(/[A-Z]/)) strength += 1;
            if (password.match(/[0-9]/)) strength += 1;
            if (password.match(/[^a-zA-Z0-9]/)) strength += 1;
            
            let percentage = (strength / 5) * 100;
            let color = '#ff0000'; // Rojo
            
            if (strength >= 4) {
                color = '#4CAF50'; // Verde
            } else if (strength >= 2) {
                color = '#FFC107'; // Amarillo
            }
            
            return { percentage, color };
        }
    </script>
</body>
</html>