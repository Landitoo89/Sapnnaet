<?php
session_start();
require 'conexion_archivero.php';

// ---- FUNCION DE LOGEO DIRECTA (copiada de logger.php) ----
function log_session_event($user_id, $event_type, $details = '') {
    global $conexion;

    if (!$conexion || $conexion->connect_error) {
        error_log("Error de conexión en logger: " . ($conexion->connect_error ?? 'Conexión no disponible'));
        return false;
    }

    $ip = $_SERVER['REMOTE_ADDR'];
    $user_agent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
    $details = substr($details, 0, 500);

    $stmt = $conexion->prepare("INSERT INTO session_logs
                               (user_id, event_type, ip_address, user_agent, details)
                               VALUES (?, ?, ?, ?, ?)");

    if (!$stmt) {
        error_log("Error preparando consulta: " . $conexion->error);
        return false;
    }

    $stmt->bind_param("issss", $user_id, $event_type, $ip, $user_agent, $details);

    if (!$stmt->execute()) {
        error_log("Error ejecutando log: " . $stmt->error);
        return false;
    }

    $stmt->close();
    return true;
}
// ---- FIN FUNCION DE LOGEO ----

function generarToken() {
    return bin2hex(random_bytes(32));
}

$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Validación básica de entradas
    if (empty($email) || empty($password)) {
        $errores[] = "Todos los campos son obligatorios";
    }

    if (empty($errores)) {
        $stmt = $conexion->prepare("SELECT id, password, rol, nombre, apellido, email, avatar FROM usuarios WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($usuario = $result->fetch_assoc()) {
            if (password_verify($password, $usuario['password'])) {
                session_regenerate_id(true);

                $_SESSION['usuario'] = [
                    'id' => $usuario['id'],
                    'nombre' => $usuario['nombre'],
                    'apellido' => $usuario['apellido'],
                    'correo' => $usuario['email'],
                    'rol' => $usuario['rol'],
                    'avatar' => !empty($usuario['avatar']) ? $usuario['avatar'] : '/proyecto/inicio/img/avatar-default.png'
                ];

                $_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];
                $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
                $_SESSION['ultimo_acceso'] = time();

                if (isset($_POST['remember'])) {
                    $token = generarToken();
                    $token_hash = hash('sha256', $token);
                    $expira = time() + 86400 * 30;

                    $stmt = $conexion->prepare("UPDATE usuarios SET remember_token = ?, token_expira = ? WHERE id = ?");
                    $stmt->bind_param("ssi", $token_hash, date('Y-m-d H:i:s', $expira), $usuario['id']);
                    $stmt->execute();

                    setcookie('remember_token', $token, [
                        'expires' => $expira,
                        'path' => '/',
                        'domain' => $_SERVER['HTTP_HOST'],
                        'secure' => true,
                        'httponly' => true,
                        'samesite' => 'Strict'
                    ]);
                }

                log_session_event($usuario['id'], 'login', 'Inicio de sesión exitoso');
                header('Location: inicio/index.php');
                exit;
            }
        }

        log_session_event(0, 'login_failed', 'Intento fallido para: ' . $email);
        $errores[] = "Credenciales incorrectas";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - SGITH</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #ff6b6b;
        }
        body {
            background: linear-gradient(120deg, var(--secondary-color) 0%, var(--primary-color) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            position: relative;
        }
        /* Fondo logo institucional gigante y translúcido */
        .login-bg-logo {
            position: fixed;
            top: 0; left: 0; width: 100vw; height: 100vh;
            z-index: 0;
            display: flex; align-items: center; justify-content: center;
            pointer-events: none;
        }
        .login-bg-logo img {
            opacity: 0.07;
            max-width: 520px;
            max-height: 60vh;
            filter: grayscale(100%) drop-shadow(0 10px 60px #0003);
            user-select: none;
        }
        .auth-container {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 22px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.16);
            overflow: hidden;
            width: 100%;
            max-width: 410px;
            margin: 2.5rem auto;
            z-index: 1;
            position: relative;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .auth-container:hover {
            transform: translateY(-6px) scale(1.01);
            box-shadow: 0 15px 45px rgba(44,62,80,0.22);
        }
        .auth-header {
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 1.6rem 1rem 1.1rem 1rem;
            text-align: center;
            border-bottom-left-radius: 40% 25px;
            border-bottom-right-radius: 40% 25px;
        }
        .auth-header img {
            display: block;
            margin: 0 auto 0.45rem auto;
            width: 7rem; /* Originalmente 3.3rem */
            filter: drop-shadow(0 2px 10px #2223);
        }
        .auth-header h2 {
            font-weight: 800;
            letter-spacing: .5px;
            font-size: 2.05rem;
        }
        .auth-header small {
            display: block;
            color: #e6e6e6;
            font-size: 1.05rem;
            margin-top: 0.4rem;
        }
        .auth-form {
            padding: 2rem 2.3rem 1.5rem 2.3rem;
        }
        .form-control {
            border-radius: 12px;
            padding: 14px 20px;
            transition: all 0.3s;
            border: 1px solid #e0e0e0;
            font-size: 1.1rem;
        }
        .form-control:focus {
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.11);
            border-color: var(--secondary-color);
        }
        .input-group-text {
            background-color: transparent;
            border-right: none;
        }
        .btn-login {
            color: white !important;
            text-shadow: 0 1px 1px rgba(0,0,0,0.18);
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            border: none;
            padding: 13px;
            border-radius: 12px;
            font-weight: 700;
            letter-spacing: 0.5px;
            transition: all 0.3s;
            width: 100%;
            font-size: 1.13rem;
        }
        .btn-login:hover {
            transform: translateY(-2px) scale(1.025);
            box-shadow: 0 5px 18px rgba(52, 152, 219, 0.22);
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
        .auth-links {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.4rem;
        }
        .auth-links a {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.97rem;
            transition: color 0.25s;
        }
        .auth-links a:hover {
            color: var(--secondary-color);
            text-decoration: underline;
        }
        .fade-in {
            animation: fadeIn 0.7s;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(18px);}
            to { opacity: 1; transform: none;}
        }
        .shake { animation: shake 0.5s linear; }
        @keyframes shake {
            0%, 100% { transform: translateX(0);}
            20%, 60% { transform: translateX(-5px);}
            40%, 80% { transform: translateX(5px);}
        }
        @media (max-width: 576px) {
            .auth-container { margin: 1rem; border-radius: 15px; }
            .auth-form { padding: 1.1rem 0.7rem 1rem 0.7rem; }
            .auth-header { padding: 1.25rem 0.7rem 0.85rem 0.7rem;}
        }
    </style>
</head>
<body>
    <div class="login-bg-logo">
        <img src="/proyecto/inicio/img/logo-sapnna.png" alt="Logo institucional">
    </div>
    <div class="container">
        <div class="auth-container fade-in">
            <div class="auth-header">
                <img src="/proyecto/inicio/img/logo-sapnna.png" alt="Logo pequeño">
                <h2>SGITH-WEB</h2>
                <small>Inicia sesión para continuar</small>
            </div>
            <form class="auth-form" method="POST" id="loginForm" autocomplete="on">
                <?php if (!empty($errores)): ?>
                    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php foreach ($errores as $error): ?>
                            <span><?= htmlspecialchars($error) ?></span>
                        <?php endforeach; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                <div class="mb-4">
                    <label for="email" class="form-label">Correo electrónico</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope-fill"></i></span>
                        <input type="email" name="email" id="email" class="form-control" placeholder="tu@email.com" required
                               value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                    </div>
                </div>
                <div class="mb-4 position-relative">
                    <label for="password" class="form-label">Contraseña</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                        <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
                        <i class="bi bi-eye-slash password-toggle" id="togglePassword"></i>
                    </div>
                </div>
                <div class="auth-links mb-4">
                    <div class="form-check m-0">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember"
                               <?= isset($_POST['remember']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="remember">Recordar sesión</label>
                    </div>
                    <a href="forgot_password.php">¿Olvidaste tu contraseña?</a>
                </div>
                <button type="submit" class="btn btn-login mb-2" id="loginBtn">
                    <i class="fas fa-sign-in-alt me-2"></i> Ingresar
                </button>
                <div class="text-center mt-3">
                    <p style="font-size:0.98rem;">¿No tienes una cuenta? <br>
                    Solicita tu usuario y contraseña a un miembro del equipo de soporte.
                        <!--<a href="register.php" class="text-decoration-none">Regístrate aquí</a>-->
                    </p>
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
        togglePassword.addEventListener('click', function() {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('bi-eye');
            this.classList.toggle('bi-eye-slash');
        });
        // Efecto al enviar formulario
        const loginForm = document.getElementById('loginForm');
        const loginBtn = document.getElementById('loginBtn');
        loginForm.addEventListener('submit', function(e) {
            if (!this.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
                this.classList.add('was-validated');
                loginForm.classList.add('shake');
                setTimeout(() => loginForm.classList.remove('shake'), 500);
            } else {
                loginBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Autenticando...';
                loginBtn.disabled = true;
            }
        });
        // Validación en tiempo real
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
