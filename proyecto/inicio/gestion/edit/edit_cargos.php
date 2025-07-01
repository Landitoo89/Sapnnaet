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

$id_cargo = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Obtener el cargo actual
$stmt = $conexion->prepare("SELECT * FROM cargos WHERE id_cargo = ?");
$stmt->bind_param("i", $id_cargo);
$stmt->execute();
$resultado = $stmt->get_result();
$cargo = $resultado->fetch_assoc();

if (!$cargo) {
    $_SESSION['error'] = "Cargo no encontrado.";
    header('Location: ../gestion_cargos.php');
    exit;
}

// Procesar actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $nivel = trim($_POST['nivel']);
    $sueldo = (float)$_POST['sueldo'];

    // Validaciones básicas
    if (empty($nombre) || empty($nivel) || $sueldo <= 0) {
        $_SESSION['error'] = "Todos los campos son obligatorios y el sueldo debe ser positivo.";
    } else {
        $stmt = $conexion->prepare("UPDATE cargos SET nombre = ?, nivel = ?, sueldo = ? WHERE id_cargo = ?");
        $stmt->bind_param("ssdi", $nombre, $nivel, $sueldo, $id_cargo);
        if ($stmt->execute()) {
            $_SESSION['mensaje'] = "Cargo actualizado correctamente.";

            // ==== LOG DE EDICIÓN DE CARGO ====
            $log_details = "Edición de cargo ID $id_cargo: $nombre (Nivel: $nivel, Sueldo: $sueldo)";
            registrarLog(
                $conexion,
                $current_user_id,
                'cargo_edited',
                $log_details
            );

            header('Location: ../gestion_cargos.php');
            exit;
        } else {
            $_SESSION['error'] = "Error al actualizar el cargo.";

            // ==== LOG DE ERROR EN EDICIÓN ====
            $log_details = "Error al editar cargo ID $id_cargo: $nombre (Nivel: $nivel, Sueldo: $sueldo). Error: " . $conexion->error;
            registrarLog(
                $conexion,
                $current_user_id,
                'cargo_edit_error',
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
    <title>Editar Cargo</title>
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
            <h1 class="mb-0"><i class="fas fa-edit me-2"></i>Editar Cargo</h1>
            <a href="../gestion_cargos.php" class="btn btn-outline-secondary">
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
                    <label for="nombre" class="form-label">Nombre del Cargo</label>
                    <input type="text" class="form-control" id="nombre" name="nombre" 
                           value="<?= htmlspecialchars($cargo['nombre']) ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="nivel" class="form-label">Nivel</label>
                    <select class="form-select" id="nivel" name="nivel" required>
                        <option value="Junior" <?= $cargo['nivel'] === 'Junior' ? 'selected' : '' ?>>Junior</option>
                        <option value="Senior" <?= $cargo['nivel'] === 'Senior' ? 'selected' : '' ?>>Senior</option>
                        <option value="Gerencial" <?= $cargo['nivel'] === 'Gerencial' ? 'selected' : '' ?>>Gerencial</option>
                        <option value="Directivo" <?= $cargo['nivel'] === 'Directivo' ? 'selected' : '' ?>>Directivo</option>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label for="sueldo" class="form-label">Sueldo (Bs)</label>
                    <input type="number" step="0.01" min="0" class="form-control" id="sueldo" name="sueldo" 
                           value="<?= htmlspecialchars($cargo['sueldo']) ?>" required>
                </div>
                
                <div class="d-grid">
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