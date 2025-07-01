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

$id_contrato = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Obtener el tipo de contrato actual
$stmt = $conexion->prepare("SELECT * FROM tipos_contrato WHERE id_contrato = ?");
$stmt->bind_param("i", $id_contrato);
$stmt->execute();
$resultado = $stmt->get_result();
$contrato = $resultado->fetch_assoc();

if (!$contrato) {
    $_SESSION['error'] = "Tipo de contrato no encontrado.";
    header('Location: gestion_contrato.php');
    exit;
}

// Procesar actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);

    // Validaciones básicas
    if (empty($nombre)) {
        $_SESSION['error'] = "El nombre del tipo de contrato es obligatorio.";
    } else {
        $stmt = $conexion->prepare("UPDATE tipos_contrato SET nombre = ? WHERE id_contrato = ?");
        $stmt->bind_param("si", $nombre, $id_contrato);
        if ($stmt->execute()) {
            $_SESSION['mensaje'] = "Tipo de contrato actualizado correctamente.";

            // ==== LOG DE EDICIÓN DE CONTRATO ====
            $log_details = "Edición de tipo de contrato ID $id_contrato: $nombre.";
            registrarLog(
                $conexion,
                $current_user_id,
                'contrato_edited',
                $log_details
            );

            header('Location: ../gestion_contrato.php');
            exit;
        } else {
            $_SESSION['error'] = "Error al actualizar el tipo de contrato.";

            // ==== LOG DE ERROR EN EDICIÓN ====
            $log_details = "Error al editar tipo de contrato ID $id_contrato: $nombre. Error: " . $conexion->error;
            registrarLog(
                $conexion,
                $current_user_id,
                'contrato_edit_error',
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
    <title>Editar Tipo de Contrato</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
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
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .btn-primary:hover {
            background-color: #258cd1;
            border-color: #258cd1;
        }
        
        .form-label {
            font-weight: 500;
            color: var(--primary-color);
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0"><i class="fas fa-edit me-2"></i>Editar Tipo de Contrato</h1>
            <a href="../gestion_contrato.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Volver
            </a>
        </div>

        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= $_SESSION['error'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="form-container">
            <form method="POST">
                <div class="mb-3">
                    <label for="nombre" class="form-label">Nombre del Tipo de Contrato</label>
                    <input type="text" class="form-control" id="nombre" name="nombre" 
                           value="<?= htmlspecialchars($contrato['nombre']) ?>" required>
                </div>
                
                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-save me-2"></i>Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>