<?php
session_start();
require 'conexion_archivero.php';

$errores = [];
$mensaje = '';
$tipo_mensaje = '';

if (isset($_SESSION['reset_message'])) {
    $mensaje = $_SESSION['reset_message']['text'];
    $tipo_mensaje = $_SESSION['reset_message']['type'];
    unset($_SESSION['reset_message']); // Clear the message after displaying
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $codigo = trim($_POST['codigo']);

    if (empty($email) || empty($codigo)) {
        $errores[] = 'Por favor, ingresa tu correo electrónico y el código de recuperación.';
    } else {
        // Buscar el usuario por email y verificar el código y su expiración
        $stmt = $conexion->prepare("SELECT id FROM usuarios WHERE email = ? AND token_reset = ? AND token_expira > NOW()");
        $stmt->bind_param("ss", $email, $codigo);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($usuario = $result->fetch_assoc()) {
            // Código válido, ahora redirigir a reset_password.php
            // Pasar el email (o el ID de usuario) y el código de alguna manera segura,
            // por ejemplo, usando una sesión o recreando un token temporal para el siguiente paso.
            // Para simplificar, pasaremos el email por sesión y el código lo "quemamos" al validar.
            
            // Lo más seguro es generar un nuevo token de sesión temporal aquí para el paso final
            $one_time_token = bin2hex(random_bytes(16)); // Token de uso único
            $_SESSION['reset_active'] = [
                'user_id' => $usuario['id'],
                'email' => $email,
                'one_time_token' => $one_time_token,
                'expires' => time() + (5 * 60) // Token de sesión válido por 5 minutos
            ];

            // Invalidar el código de un solo uso en la DB para prevenir reuso
            $stmt_clear_token = $conexion->prepare("UPDATE usuarios SET token_reset = NULL, token_expira = NULL WHERE id = ?");
            $stmt_clear_token->bind_param("i", $usuario['id']);
            $stmt_clear_token->execute();

            header('Location: reset_password.php?token=' . $one_time_token); // Redirigir con el nuevo token de sesión
            exit;

        } else {
            $errores[] = 'Correo electrónico o código de recuperación incorrecto, o el código ha expirado. Por favor, verifica e inténtalo de nuevo.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificar Código - Sistema de Archivo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* El mismo CSS que usas en index.php y forgot_password.php */
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
                <h2><i class="fas fa-mobile-alt me-2"></i>Verificar Código</h2>
                <p class="mb-0">Introduce el código de 6 dígitos que te enviamos.</p>
            </div>
            
            <form class="auth-form" method="POST" id="verifyCodeForm">
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
                
                <div class="mb-4">
                    <label for="email" class="form-label">Correo electrónico</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope-fill"></i></span>
                        <input type="email" name="email" id="email" class="form-control" placeholder="tu@email.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>
                </div>

                <div class="mb-4">
                    <label for="codigo" class="form-label">Código de Verificación</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-hashtag"></i></span>
                        <input type="text" name="codigo" id="codigo" class="form-control" placeholder="Ej: 123456" required pattern="\d{6}" title="Debe ser un código de 6 dígitos">
                    </div>
                </div>
                
                <button type="submit" class="btn btn-submit mb-3" id="verifyBtn">
                    <i class="fas fa-check-circle me-2"></i> Verificar Código
                </button>

                <div class="text-center mt-3">
                    <p><a href="index.php" class="text-decoration-none">Volver al inicio de sesión</a></p>
                    <p><a href="forgot_password.php" class="text-decoration-none">Solicitar nuevo código</a></p>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const verifyCodeForm = document.getElementById('verifyCodeForm');
        const verifyBtn = document.getElementById('verifyBtn');

        verifyCodeForm.addEventListener('submit', function(e) {
            if (!this.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
                this.classList.add('was-validated');
                verifyCodeForm.classList.add('shake');
                setTimeout(() => verifyCodeForm.classList.remove('shake'), 500);
            } else {
                verifyBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Verificando...';
                verifyBtn.disabled = true;
            }
        });

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