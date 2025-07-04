<?php
session_start();
// No database connection needed directly here, will be handled by send_password_reset.php

$mensaje = '';
$tipo_mensaje = ''; // 'success' or 'danger'

if (isset($_SESSION['reset_message'])) {
    $mensaje = $_SESSION['reset_message']['text'];
    $tipo_mensaje = $_SESSION['reset_message']['type'];
    unset($_SESSION['reset_message']); // Clear the message after displaying
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - Sistema de Archivo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
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
                <h2><i class="fas fa-lock me-2"></i>Recuperar Contraseña</h2>
                <p class="mb-0">Ingresa tu correo electrónico para restablecer tu contraseña.</p>
            </div>
            
            <form class="auth-form" method="POST" action="send_password_reset.php" id="forgotPasswordForm">
                <?php if ($mensaje): ?>
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
                        <input type="email" name="email" id="email" class="form-control" placeholder="tu@email.com" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-submit mb-3" id="resetBtn">
                    <i class="fas fa-paper-plane me-2"></i> Enviar enlace de restablecimiento
                </button>

                <div class="text-center mt-3">
                    <p><a href="index.php" class="text-decoration-none">Volver al inicio de sesión</a></p>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const forgotPasswordForm = document.getElementById('forgotPasswordForm');
        const resetBtn = document.getElementById('resetBtn');

        forgotPasswordForm.addEventListener('submit', function(e) {
            if (!this.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
                this.classList.add('was-validated');
                forgotPasswordForm.classList.add('shake');
                setTimeout(() => forgotPasswordForm.classList.remove('shake'), 500);
            } else {
                resetBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Enviando...';
                resetBtn.disabled = true;
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