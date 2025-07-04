<?php
session_start();
require_once __DIR__ . '/../conexion/conexion_db.php';

// ==== FUNCIÓN PARA REGISTRAR LOGS ====
function registrarLog($conn, $user_id, $event_type, $details) {
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
    $stmt = $conn->prepare("INSERT INTO action_logs (user_id, event_type, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $user_id, $event_type, $details, $ip_address, $user_agent);
    $stmt->execute();
    $stmt->close();
}
$current_user_id = $_SESSION['usuario']['id'] ?? null;

// Verificar permisos de admin
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Procesar el formulario de creación
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);

    // Validaciones básicas
    if (empty($nombre)) {
        $_SESSION['error'] = "El nombre del tipo de contrato es obligatorio.";
    } else {
        // Verificar si el tipo de contrato ya existe
        $stmt_check = $conexion->prepare("SELECT COUNT(*) AS total FROM tipos_contrato WHERE nombre = ?");
        $stmt_check->bind_param("s", $nombre);
        $stmt_check->execute();
        $result = $stmt_check->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['total'] > 0) {
            $_SESSION['error'] = "Este tipo de contrato ya existe en el sistema.";

            // ==== LOG DE ERROR: CONTRATO REPETIDO ====
            $log_details = "Intento fallido de creación de tipo de contrato repetido: $nombre.";
            registrarLog(
                $conexion,
                $current_user_id,
                'contrato_create_duplicate',
                $log_details
            );
        } else {
            $stmt = $conexion->prepare("INSERT INTO tipos_contrato (nombre) VALUES (?)");
            $stmt->bind_param("s", $nombre);
            if ($stmt->execute()) {
                $_SESSION['mensaje'] = "Tipo de contrato creado correctamente.";

                // ==== LOG DE CREACIÓN EXITOSA ====
                $log_details = "Registro de nuevo tipo de contrato: $nombre.";
                registrarLog(
                    $conexion,
                    $current_user_id,
                    'contrato_created',
                    $log_details
                );

                header('Location: ../gestion_contrato.php');
                exit;
            } else {
                $_SESSION['error'] = "Error al crear el tipo de contrato. " . $conexion->error;

                // ==== LOG DE ERROR EN CREACIÓN ====
                $log_details = "Error al crear tipo de contrato: $nombre. Error: " . $conexion->error;
                registrarLog(
                    $conexion,
                    $current_user_id,
                    'contrato_create_error',
                    $log_details
                );
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Añadir Nuevo Tipo de Contrato</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --success-color: #28a745;
            --warning-color: #ffc107;
        }
        
        .form-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .form-header {
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 1rem;
            margin-bottom: 2rem;
        }
        
        .btn-primary {
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            border: none;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background: linear-gradient(to right, var(--primary-color), #258cd1);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .form-label {
            font-weight: 500;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .form-control, .form-select {
            border-radius: 8px;
            padding: 0.75rem 1rem;
            border: 1px solid #dee2e6;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 0.25rem rgba(52, 152, 219, 0.25);
        }
        
        .info-card {
            background: linear-gradient(to right, #f8f9fa, #e9ecef);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--secondary-color);
        }
        
        .card-icon {
            font-size: 3rem;
            color: var(--secondary-color);
            margin-bottom: 1rem;
        }
        
        .contract-types {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 1rem;
        }
        
        .contract-type-badge {
            background-color: #e9ecef;
            border-radius: 20px;
            padding: 5px 15px;
            font-size: 0.9rem;
            color: #495057;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0"><i class="fas fa-file-contract me-2"></i>Añadir Nuevo Tipo de Contrato</h1>
            <a href="../gestion_contrato.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Volver al Listado
            </a>
        </div>

        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= $_SESSION['error'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-8">
                <div class="form-container">
                    <div class="form-header">
                        <h3><i class="fas fa-edit me-2"></i>Información del Tipo de Contrato</h3>
                        <p class="text-muted mb-0">Complete el campo requerido para crear un nuevo tipo de contrato</p>
                    </div>
                    
                    <form method="POST">
                        <div class="mb-4">
                            <label for="nombre" class="form-label">Nombre del Tipo de Contrato *</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" 
                                   placeholder="Ej: Indefinido, Temporal, Por Obra..." required>
                            <div class="form-text">El nombre debe ser claro y descriptivo.</div>
                        </div>
                        
                        <div class="d-grid mt-5">
                            <button type="submit" class="btn btn-primary btn-lg py-3">
                                <i class="fas fa-save me-2"></i>Crear Nuevo Tipo de Contrato
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="info-card">
                    <div class="card-icon text-center">
                        <i class="fas fa-lightbulb"></i>
                    </div>
                    <h4 class="text-center mb-3">Recomendaciones</h4>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex align-items-center">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <span>Los nombres deben ser únicos y descriptivos</span>
                        </li>
                        <li class="list-group-item d-flex align-items-center">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <span>Utilice nombres estandarizados</span>
                        </li>
                        <li class="list-group-item d-flex align-items-center">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <span>Considere las regulaciones laborales locales</span>
                        </li>
                    </ul>
                </div>
                
                <div class="info-card" style="border-left-color: var(--success-color);">
                    <div class="card-icon text-center">
                        <i class="fas fa-list"></i>
                    </div>
                    <h4 class="text-center mb-3">Tipos Comunes</h4>
                    <div class="contract-types">
                        <span class="contract-type-badge">Indefinido</span>
                        <span class="contract-type-badge">Temporal</span>
                        <span class="contract-type-badge">Por Obra</span>
                        <span class="contract-type-badge">Practicante</span>
                        <span class="contract-type-badge">Consultor</span>
                        <span class="contract-type-badge">Tiempo Parcial</span>
                        <span class="contract-type-badge">Tiempo Completo</span>
                        <span class="contract-type-badge">Freelance</span>
                    </div>
                </div>
                
                <div class="info-card" style="border-left-color: var(--warning-color);">
                    <div class="card-icon text-center">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h4 class="text-center mb-3">Consideraciones Legales</h4>
                    <div class="alert alert-warning">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Importante:</strong> Cada tipo de contrato puede tener implicaciones legales diferentes.
                    </div>
                    <div class="alert alert-warning">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Recomendado:</strong> Consulte con su departamento legal antes de crear nuevos tipos.
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sugerir tipos comunes al hacer clic en el campo
        document.getElementById('nombre').addEventListener('click', function() {
            const suggestions = ['Indefinido', 'Temporal', 'Por Obra', 'Practicante', 'Consultor'];
            if (!this.value) {
                this.placeholder = suggestions.join(', ');
                setTimeout(() => {
                    this.placeholder = 'Ej: Indefinido, Temporal, Por Obra...';
                }, 3000);
            }
        });
        
        // Validación antes de enviar
        document.querySelector('form').addEventListener('submit', function(e) {
            const nombre = document.getElementById('nombre').value.trim();
            
            if (nombre.length < 3) {
                alert('El nombre del tipo de contrato debe tener al menos 3 caracteres');
                e.preventDefault();
                return false;
            }
            
            return true;
        });
    </script>
</body>
</html>