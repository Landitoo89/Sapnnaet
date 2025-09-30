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

// Obtener tipos de personal para el select
$tipos_personal = $conexion->query("SELECT id_tipo_personal, nombre FROM tipos_personal ORDER BY nombre");

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
    $grado = trim($_POST['grado']);
    $descripcion = trim($_POST['descripcion']);
    $id_tipo_personal = (int)$_POST['id_tipo_personal'];

    // Validaciones básicas
    if (empty($nombre) || empty($grado) || empty($descripcion) || $id_tipo_personal <= 0) {
        $_SESSION['error'] = "Todos los campos son obligatorios.";
    } else {
        $stmt = $conexion->prepare("UPDATE cargos SET nombre = ?, grado = ?, descripcion = ?, id_tipo_personal = ? WHERE id_cargo = ?");
        $stmt->bind_param("sssii", $nombre, $grado, $descripcion, $id_tipo_personal, $id_cargo);
        if ($stmt->execute()) {
            $_SESSION['mensaje'] = "Cargo actualizado correctamente.";

            // ==== LOG DE EDICIÓN DE CARGO ====
            $log_details = "Edición de cargo ID $id_cargo: $nombre (Grado: $grado, Tipo de Personal: $id_tipo_personal, Descripción: $descripcion)";
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
            $log_details = "Error al editar cargo ID $id_cargo: $nombre (Grado: $grado, Tipo de Personal: $id_tipo_personal, Descripción: $descripcion). Error: " . $conexion->error;
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
                    <label for="grado" class="form-label">Grado</label>
                    <input type="text" class="form-control" id="grado" name="grado" 
                           value="<?= htmlspecialchars($cargo['grado']) ?>" required>
                </div>
                <div class="mb-3">
                    <label for="id_tipo_personal" class="form-label">Tipo de Personal</label>
                    <select class="form-select" id="id_tipo_personal" name="id_tipo_personal" required>
                        <option value="" disabled <?= !$cargo['id_tipo_personal'] ? 'selected' : '' ?>>Seleccione tipo de personal</option>
                        <?php
                        $tipos_personal->data_seek(0);
                        while($tipo = $tipos_personal->fetch_assoc()): ?>
                            <option value="<?= $tipo['id_tipo_personal'] ?>" <?= $cargo['id_tipo_personal'] == $tipo['id_tipo_personal'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($tipo['nombre']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="mb-4">
                    <label for="descripcion" class="form-label">Descripción</label>
                    <textarea class="form-control" id="descripcion" name="descripcion" rows="4" required><?= htmlspecialchars($cargo['descripcion']) ?></textarea>
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