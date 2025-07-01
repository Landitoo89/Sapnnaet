<?php
session_start();
require 'conexion/conexion_gestion_primas.php';

// ==== FUNCIÓN PARA REGISTRAR LOGS ====
function registrarLog($conn, $user_id, $event_type, $details) {
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
    $stmt = $conn->prepare("INSERT INTO action_logs (user_id, event_type, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $event_type, $details, $ip_address, $user_agent]);
}
$current_user_id = $_SESSION['usuario']['id'] ?? null;

// Acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Crear o actualizar prima
    if (isset($_POST['guardar_prima'])) {
        $id_prima = isset($_POST['id_prima']) ? intval($_POST['id_prima']) : 0;
        $nombre = trim($_POST['nombre']);
        $descripcion = trim($_POST['descripcion']);
        $monto = floatval(str_replace(',', '.', $_POST['monto']));
        
        // Validaciones
        $errores = [];
        if (empty($nombre)) {
            $errores[] = "El nombre es obligatorio";
        }
        if ($monto <= 0) {
            $errores[] = "El monto debe ser mayor que cero";
        }
        
        if (empty($errores)) {
            if ($id_prima > 0) {
                // Actualizar
                $stmt = $conexion->prepare("UPDATE primas SET nombre = ?, descripcion = ?, monto = ? WHERE id_prima = ?");
                $stmt->execute([$nombre, $descripcion, $monto, $id_prima]);

                // ==== LOG DE EDICIÓN DE PRIMA ====
                if ($stmt->rowCount() > 0) {
                    $log_details = "Edición de prima ID $id_prima: $nombre (Monto: $monto, Descripción: $descripcion)";
                    registrarLog($conexion, $current_user_id, 'prima_edited', $log_details);
                }

            } else {
                // Crear nuevo
                $stmt = $conexion->prepare("INSERT INTO primas (nombre, descripcion, monto) VALUES (?, ?, ?)");
                $stmt->execute([$nombre, $descripcion, $monto]);

                // ==== LOG DE CREACIÓN DE PRIMA ====
                if ($stmt->rowCount() > 0) {
                    $log_details = "Registro de nueva prima: $nombre (Monto: $monto, Descripción: $descripcion)";
                    registrarLog($conexion, $current_user_id, 'prima_created', $log_details);
                }
            }
            
            if ($stmt->rowCount() > 0) {
                $_SESSION['mensaje'] = "Prima " . ($id_prima > 0 ? "actualizada" : "creada") . " correctamente";
            } else {
                $_SESSION['error'] = "Error al guardar: No se realizaron cambios";
            }
            
            header("Location: gestion_primas.php");
            exit;
        } else {
            $mensaje = "<div class='alert alert-danger'>Errores:<br>" . implode("<br>", $errores) . "</div>";
            // ==== LOG DE ERROR EN VALIDACIÓN ====
            $log_details = "Error al guardar prima (" . ($id_prima > 0 ? "editar" : "crear") . "): " . implode('; ', $errores);
            registrarLog($conexion, $current_user_id, 'prima_save_validation_error', $log_details);
        }
    }
    
    // Eliminar prima
    if (isset($_POST['eliminar_prima'])) {
        $id_prima = intval($_POST['id_prima']);
        
        // Obtener datos de la prima para el log
        $stmt_info = $conexion->prepare("SELECT nombre, monto FROM primas WHERE id_prima = ?");
        $stmt_info->execute([$id_prima]);
        $info = $stmt_info->fetch(PDO::FETCH_ASSOC);
        
        // Verificar si está asignada a empleados
        $stmt = $conexion->prepare("SELECT COUNT(*) AS total FROM empleado_primas WHERE id_prima = ?");
        $stmt->execute([$id_prima]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['total'] > 0) {
            $_SESSION['error'] = "No se puede eliminar, la prima está asignada a empleados";
            // ==== LOG DE INTENTO FALLIDO POR ASIGNACIÓN ====
            if ($info) {
                $log_details = "Intento fallido de eliminación de prima: {$info['nombre']} (ID: $id_prima, Monto: {$info['monto']}). Prima en uso por empleados.";
                registrarLog($conexion, $current_user_id, 'prima_delete_failed_in_use', $log_details);
            }
        } else {
            $stmt = $conexion->prepare("DELETE FROM primas WHERE id_prima = ?");
            $stmt->execute([$id_prima]);
            
            if ($stmt->rowCount() > 0) {
                $_SESSION['mensaje'] = "Prima eliminada correctamente";
                // ==== LOG DE ELIMINACIÓN EXITOSA ====
                if ($info) {
                    $log_details = "Eliminación de prima: {$info['nombre']} (ID: $id_prima, Monto: {$info['monto']})";
                    registrarLog($conexion, $current_user_id, 'prima_deleted', $log_details);
                }
            } else {
                $_SESSION['error'] = "Error al eliminar: Prima no encontrada";
                // ==== LOG DE ERROR EN ELIMINACIÓN ====
                if ($info) {
                    $log_details = "Error al eliminar prima: {$info['nombre']} (ID: $id_prima, Monto: {$info['monto']})";
                    registrarLog($conexion, $current_user_id, 'prima_delete_error', $log_details);
                }
            }
        }
        
        header("Location: gestion_primas.php");
        exit;
    }
}

// Obtener primas para mostrar
$stmt = $conexion->query("SELECT * FROM primas ORDER BY nombre");
$primas = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total_primas = count($primas);

// Si estamos editando, obtener datos de la prima
$prima_editar = null;
if (isset($_GET['editar'])) {
    $id_prima = intval($_GET['editar']);
    $stmt = $conexion->prepare("SELECT * FROM primas WHERE id_prima = ?");
    $stmt->execute([$id_prima]);
    $prima_editar = $stmt->fetch(PDO::FETCH_ASSOC);
}
require $_SERVER['DOCUMENT_ROOT']."/proyecto/inicio/sidebar.php";

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Primas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #1abc9c;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 20px 0;
            border-radius: 0 0 20px 20px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .card {
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border: none;
            transition: transform 0.3s;
            margin-bottom: 20px;
        }
        
        .card:hover {
            transform: translateY(-5px);
        }
        
        .card-header {
            background: linear-gradient(to right, var(--secondary-color), var(--accent-color));
            color: white;
            border-radius: 15px 15px 0 0 !important;
            font-weight: 600;
        }
        
        .btn-primary {
            background: var(--secondary-color);
            border: none;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            background: var(--primary-color);
            transform: scale(1.05);
        }
        
        .btn-danger {
            transition: all 0.3s;
        }
        
        .btn-danger:hover {
            transform: scale(1.05);
        }
        
        .action-btn {
            min-width: 80px;
        }
        
        .prima-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #eee;
            transition: background-color 0.2s;
        }
        
        .prima-item:hover {
            background-color: #f8f9fa;
        }
        
        .prima-info {
            flex: 1;
        }
        
        .prima-actions {
            display: flex;
            gap: 10px;
        }
        
        .monto-badge {
            background: var(--accent-color);
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 1.1em;
        }
        
        .form-container {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .section-title {
            color: var(--primary-color);
            border-bottom: 3px solid var(--accent-color);
            padding-bottom: 10px;
            margin-bottom: 20px;
            font-weight: 700;
        }
        
        .tabs-container {
            margin-bottom: 30px;
        }
        
        .nav-tabs .nav-link {
            color: var(--primary-color);
            font-weight: 500;
            border: none;
            border-bottom: 3px solid transparent;
            padding: 12px 25px;
            transition: all 0.3s;
        }
        
        .nav-tabs .nav-link.active {
            background: transparent;
            color: var(--secondary-color);
            border-bottom: 3px solid var(--accent-color);
            font-weight: 600;
        }
        
        .nav-tabs .nav-link:hover {
            border-bottom: 3px solid var(--accent-color);
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <h1><i class="fas fa-gift me-2"></i>Gestión de Primas</h1>
                <a href="gestion_laboral.php" class="btn btn-outline-light">
                    <i class="fas fa-arrow-left me-2"></i>Volver a Laboral
                </a>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if(isset($_SESSION['mensaje'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= $_SESSION['mensaje'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['mensaje']); ?>
        <?php endif; ?>
        
        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= $_SESSION['error'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <?php if(isset($mensaje)) echo $mensaje; ?>

        <div class="tabs-container">
            <ul class="nav nav-tabs" id="myTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="list-tab" data-bs-toggle="tab" data-bs-target="#list" type="button" role="tab">
                        <i class="fas fa-list me-2"></i>Listado de Primas
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="form-tab" data-bs-toggle="tab" data-bs-target="#form" type="button" role="tab">
                        <i class="fas fa-plus-circle me-2"></i><?= $prima_editar ? 'Editar Prima' : 'Nueva Prima' ?>
                    </button>
                </li>
            </ul>
        </div>

        <div class="tab-content">
            <!-- Tab 1: Listado de primas -->
            <div class="tab-pane fade show active" id="list" role="tabpanel">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-gift me-2"></i>Primas Disponibles
                        </div>
                        <div>
                            <span class="badge bg-light text-dark">
                                <?= $total_primas ?> prima<?= $total_primas != 1 ? 's' : '' ?>
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if($total_primas > 0): ?>
                            <div class="list-group">
                                <?php foreach($primas as $prima): ?>
                                    <div class="prima-item">
                                        <div class="prima-info">
                                            <h5 class="mb-1"><?= htmlspecialchars($prima['nombre']) ?></h5>
                                            <p class="mb-1 text-muted"><?= htmlspecialchars($prima['descripcion']) ?></p>
                                        </div>
                                        <div class="monto-badge">
                                            <?= number_format($prima['monto'], 2, ',', '.') ?>Bs
                                        </div>
                                        <div class="prima-actions">
                                            <a href="gestion_primas.php?editar=<?= $prima['id_prima'] ?>" class="btn btn-sm btn-warning action-btn">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="id_prima" value="<?= $prima['id_prima'] ?>">
                                                <button type="submit" name="eliminar_prima" class="btn btn-sm btn-danger action-btn"
                                                    onclick="return confirm('¿Eliminar permanentemente esta prima?')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-inbox fa-3x mb-3 text-muted"></i>
                                <h5>No hay primas registradas</h5>
                                <p class="text-muted">Comienza agregando una nueva prima</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Tab 2: Formulario de primas -->
            <div class="tab-pane fade" id="form" role="tabpanel">
                <div class="form-container">
                    <h3 class="section-title">
                        <i class="fas fa-<?= $prima_editar ? 'edit' : 'plus' ?> me-2"></i>
                        <?= $prima_editar ? 'Editar Prima' : 'Crear Nueva Prima' ?>
                    </h3>
                    
                    <form method="POST">
                        <?php if($prima_editar): ?>
                            <input type="hidden" name="id_prima" value="<?= $prima_editar['id_prima'] ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre de la Prima</label>
                            <input type="text" class="form-control form-control-lg" id="nombre" name="nombre" 
                                   placeholder="Ej: Prima de Transporte" required
                                   value="<?= $prima_editar['nombre'] ?? ($_POST['nombre'] ?? '') ?>">
                            <div class="form-text">Nombre descriptivo de la prima</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="3"
                                      placeholder="Ej: Ayuda para gastos de transporte"><?= $prima_editar['descripcion'] ?? ($_POST['descripcion'] ?? '') ?></textarea>
                            <div class="form-text">Breve descripción de la prima (opcional)</div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="monto" class="form-label">Monto (Bs)</label>
                            <input type="text" class="form-control form-control-lg" id="monto" name="monto" 
                                   placeholder="Ej: 50.00" required
                                   pattern="[0-9]+([\.,][0-9]{1,2})?"
                                   title="Formato: 999.99 (dos decimales)"
                                   value="<?= isset($prima_editar['monto']) ? number_format($prima_editar['monto'], 2, '.', '') : ($_POST['monto'] ?? '') ?>">
                            <div class="form-text">Monto monetario de la prima</div>
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2">
                            <a href="gestion_primas.php" class="btn btn-secondary">Cancelar</a>
                            <button type="submit" name="guardar_prima" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>
                                <?= $prima_editar ? 'Actualizar Prima' : 'Crear Prima' ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Activar la pestaña correcta según la acción
        document.addEventListener('DOMContentLoaded', function() {
            <?php if($prima_editar): ?>
                // Si estamos editando, activar la pestaña del formulario
                const formTab = new bootstrap.Tab(document.getElementById('form-tab'));
                formTab.show();
            <?php endif; ?>
            
            // Manejar el evento de envío del formulario
            const montoInput = document.getElementById('monto');
            if (montoInput) {
                montoInput.addEventListener('change', function() {
                    // Reemplazar coma por punto para formato decimal
                    this.value = this.value.replace(',', '.');
                    
                    // Validar formato numérico
                    if (!/^\d+(\.\d{1,2})?$/.test(this.value)) {
                        alert('Formato de monto inválido. Use números con hasta dos decimales (ej: 150.75)');
                        this.focus();
                    }
                });
            }
        });
    </script>
</body>
</html>