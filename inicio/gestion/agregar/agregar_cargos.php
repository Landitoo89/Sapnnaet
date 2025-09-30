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

// Obtener tipos de personal para el select
$tipos_personal = $conexion->query("SELECT id_tipo_personal, nombre FROM tipos_personal ORDER BY nombre");

// Procesar el formulario de creación
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $grado = trim($_POST['grado']);
    $descripcion = trim($_POST['descripcion']);
    $id_tipo_personal = (int)$_POST['id_tipo_personal'];

    // Validaciones básicas
    if (empty($nombre) || empty($grado) || empty($descripcion) || $id_tipo_personal <= 0) {
        $_SESSION['error'] = "Todos los campos son obligatorios.";
    } else {
        $stmt = $conexion->prepare("INSERT INTO cargos (nombre, grado, descripcion, id_tipo_personal) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $nombre, $grado, $descripcion, $id_tipo_personal);
        if ($stmt->execute()) {
            $_SESSION['mensaje'] = "Cargo creado correctamente.";

            // ==== LOG DE AGREGADO DE CARGO ====
            $log_details = "Registro de nuevo cargo: $nombre (Grado: $grado, Tipo de Personal: $id_tipo_personal, Descripción: $descripcion)";
            registrarLog(
                $conexion,
                $current_user_id,
                'cargo_created',
                $log_details
            );

            header('Location: ../gestion_cargos.php');
            exit;
        } else {
            $_SESSION['error'] = "Error al crear el cargo. " . $conexion->error;

            // ==== LOG DE ERROR DE CREACIÓN ====
            $log_details = "Error al crear cargo: $nombre (Grado: $grado, Tipo de Personal: $id_tipo_personal, Descripción: $descripcion). Error: " . $conexion->error;
            registrarLog(
                $conexion,
                $current_user_id,
                'cargo_create_error',
                $log_details
            );
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Nuevo Cargo</title>
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
        .input-group-text {
            background-color: #f8f9fa;
            border-radius: 8px 0 0 8px;
            border: 1px solid #dee2e6;
        }
        .card-icon {
            font-size: 3rem;
            color: var(--secondary-color);
            margin-bottom: 1rem;
        }
        .info-card {
            background: linear-gradient(to right, #f8f9fa, #e9ecef);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--secondary-color);
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Crear Nuevo Cargo</h1>
            <a href="../gestion_cargos.php" class="btn btn-outline-secondary">
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
                        <h3><i class="fas fa-edit me-2"></i>Información del Cargo</h3>
                        <p class="text-muted mb-0">Complete todos los campos requeridos para crear un nuevo cargo</p>
                    </div>
                    
                    <form method="POST">
                        <div class="mb-4">
                            <label for="nombre" class="form-label">Nombre del Cargo *</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" 
                                   placeholder="Ej: Vigilante" required>
                            <div class="form-text">El nombre debe ser descriptivo y claro.</div>
                        </div>
                        <div class="mb-4">
                            <label for="grado" class="form-label">Grado *</label>
                             <input type="text" class="form-control" id="grado" name="grado" 
                                   placeholder="Ej: Junior" required>
                        </div>
                        <div class="mb-4">
                            <label for="id_tipo_personal" class="form-label">Tipo de Personal *</label>
                            <select class="form-select" id="id_tipo_personal" name="id_tipo_personal" required>
                                <option value="" selected disabled>Seleccione tipo de personal</option>
                                <?php while($tipo = $tipos_personal->fetch_assoc()): ?>
                                    <option value="<?= $tipo['id_tipo_personal'] ?>"><?= htmlspecialchars($tipo['nombre']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label for="descripcion" class="form-label">Descripción del Cargo *</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="4" required placeholder="Describa funciones, competencias, etc."></textarea>
                        </div>
                        <div class="d-grid mt-5">
                            <button type="submit" class="btn btn-primary btn-lg py-3">
                                <i class="fas fa-save me-2"></i>Crear Nuevo Cargo
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
                            <span>Asigne el grado correcto según responsabilidades</span>
                        </li>
                        <li class="list-group-item d-flex align-items-center">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <span>Considere el tipo de personal adecuado</span>
                        </li>
                        <li class="list-group-item d-flex align-items-center">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <span>Revise cargos existentes antes de crear</span>
                        </li>
                    </ul>
                </div>
                <div class="info-card" style="border-left-color: var(--success-color);">
                    <div class="card-icon text-center">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h4 class="text-center mb-3">Buenas Prácticas</h4>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Nomenclatura:</strong> Use mayúsculas para la primera letra de cada palabra
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Grados:</strong> Mantenga rangos coherentes por grado
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Descripciones:</strong> Desarrolle descripciones claras y completas
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>