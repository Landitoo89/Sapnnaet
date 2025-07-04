<?php
session_start();

// Incluir archivos necesarios en orden correcto
require '../conexion_archivero.php';
require '../core/verificar_sesion.php';
require '../core/logger.php';

// Solo administradores pueden ver logs
if ($_SESSION['usuario']['rol'] !== 'admin') {
    header('HTTP/1.0 403 Forbidden');
    exit('Acceso denegado');
}

// Obtener parámetros
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;

// Obtener logs
$logs = get_session_history($user_id, $limit);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Sesiones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; padding: 20px; }
        .table-container { background: white; border-radius: 10px; box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075); padding: 20px; }
        .table thead th { background-color: #764ba2; color: white; }
        .filters { background: white; border-radius: 10px; padding: 20px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">Historial de Sesiones</h1>
        
        <!-- Filtros -->
        <div class="filters">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="user_id" class="form-label">ID de Usuario</label>
                    <input type="number" name="user_id" id="user_id" class="form-control" 
                           value="<?= htmlspecialchars($_GET['user_id'] ?? '') ?>" placeholder="Filtrar por ID">
                </div>
                <div class="col-md-4">
                    <label for="limit" class="form-label">Límite de registros</label>
                    <select name="limit" id="limit" class="form-select">
                        <option value="50" <?= ($limit == 50) ? 'selected' : '' ?>>50 registros</option>
                        <option value="100" <?= ($limit == 100) ? 'selected' : '' ?>>100 registros</option>
                        <option value="200" <?= ($limit == 200) ? 'selected' : '' ?>>200 registros</option>
                        <option value="500" <?= ($limit == 500) ? 'selected' : '' ?>>500 registros</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">Filtrar</button>
                    <a href="session_logs.php" class="btn btn-secondary">Limpiar</a>
                </div>
            </form>
        </div>
        
        <!-- Tabla de logs -->
        <div class="table-container">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Usuario</th>
                            <th>Evento</th>
                            <th>IP</th>
                            <th>Dispositivo</th>
                            <th>Fecha/Hora</th>
                            <th>Detalles</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="7" class="text-center">No se encontraron registros</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?= htmlspecialchars($log['id']) ?></td>
                                    <td><?= htmlspecialchars($log['user_id']) ?></td>
                                    <td><?= htmlspecialchars($log['event_type']) ?></td>
                                    <td><?= htmlspecialchars($log['ip_address']) ?></td>
                                    <td><?= htmlspecialchars(substr($log['user_agent'], 0, 30)) ?>...</td>
                                    <td><?= htmlspecialchars($log['created_at']) ?></td>
                                    <td><?= htmlspecialchars(substr($log['details'], 0, 50)) ?>...</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>