<?php
session_start();
require 'conexion/conexion_db.php';

// Solo permitir acceso a admin
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'admin') {
    header('Location: index.php');
    exit;
}

// Parámetros de búsqueda y filtrado
$pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$registros_por_pagina = 20;
$offset = ($pagina - 1) * $registros_por_pagina;

$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$event_type = isset($_GET['event_type']) ? trim($_GET['event_type']) : '';
$user_id = isset($_GET['user_id']) ? trim($_GET['user_id']) : '';

// Construcción de consulta dinámica
$condiciones = [];
$parametros = [];
$tipos = '';

if ($busqueda !== '') {
    $condiciones[] = "(details LIKE ? OR ip_address LIKE ? OR user_agent LIKE ?)";
    $parametros[] = "%$busqueda%";
    $parametros[] = "%$busqueda%";
    $parametros[] = "%$busqueda%";
    $tipos .= 'sss';
}
if ($event_type !== '') {
    $condiciones[] = "event_type = ?";
    $parametros[] = $event_type;
    $tipos .= 's';
}
if ($user_id !== '') {
    $condiciones[] = "user_id = ?";
    $parametros[] = $user_id;
    $tipos .= 'i';
}

$where = '';
if (count($condiciones) > 0) {
    $where = 'WHERE ' . implode(' AND ', $condiciones);
}

// Obtener total de registros para paginación
$sql_count = "SELECT COUNT(*) as total FROM action_logs $where";
$stmt_count = $conexion->prepare($sql_count);
if (!empty($parametros)) {
    $stmt_count->bind_param($tipos, ...$parametros);
}
$stmt_count->execute();
$total_registros = $stmt_count->get_result()->fetch_assoc()['total'];
$total_paginas = ceil($total_registros / $registros_por_pagina);

// Obtener logs paginados (ajustar nombre de columna de created_at según tu tabla)
$sql = "SELECT al.*, CONCAT(u.nombre, ' ', u.apellido) as username 
        FROM action_logs al
        LEFT JOIN usuarios u ON al.user_id = u.id
        $where
        ORDER BY al.created_at DESC
        LIMIT ? OFFSET ?";
$parametros2 = $parametros;
$tipos2 = $tipos . 'ii';
$parametros2[] = $registros_por_pagina;
$parametros2[] = $offset;
$stmt = $conexion->prepare($sql);
if (!empty($parametros2)) {
    $stmt->bind_param($tipos2, ...$parametros2);
}
$stmt->execute();
$resultado = $stmt->get_result();
$logs = $resultado->fetch_all(MYSQLI_ASSOC);

// Obtener event types únicos para filtro
$event_types = [];
$res_types = $conexion->query("SELECT DISTINCT event_type FROM action_logs ORDER BY event_type ASC");
while ($row = $res_types->fetch_assoc()) {
    $event_types[] = $row['event_type'];
}

// Obtener usuarios para filtro
$usuarios = [];
$res_users = $conexion->query("SELECT id, nombre, apellido FROM usuarios ORDER BY nombre ASC, apellido ASC");
while ($row = $res_users->fetch_assoc()) {
    $usuarios[$row['id']] = $row['nombre'].' '.$row['apellido'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Logs del Sistema</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap 5.3 y FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body { background: #f4f7fa; }
        .logs-table-container {
            background: white;
            border-radius: 18px;
            box-shadow: 0 6px 28px rgba(44,62,80,0.09);
            padding: 2.5rem 2rem 2rem 2rem;
        }
        .logs-header {
            background: linear-gradient(90deg, #2c3e50 0%, #3498db 100%);
            color: #fff;
            border-radius: 12px 12px 0 0;
            padding: 1.5rem 2rem;
            margin: -2.5rem -2rem 2rem -2rem;
        }
        .logs-header h2 {
            font-size: 2.1rem;
            font-weight: 700;
            letter-spacing: -1px;
        }
        .filter-form .form-control, .filter-form .form-select { font-size:0.97em; }
        .logs-table thead { background: linear-gradient(90deg, #2c3e50, #3498db); color:#fff; }
        .logs-table th, .logs-table td { vertical-align: middle; font-size:0.97em; }
        .logs-table tbody tr:hover { background: #eaf1fa; }
        .log-details { max-width: 370px; white-space: pre-wrap; word-break: break-word; }
        .log-type { font-size:0.95em; font-family:monospace; background:#eef6fa; color:#3498db; }
        .ip-useragent { font-size:0.89em; color:#787a91; }
        .pagination { justify-content: center; margin-top:2rem; }
        .search-icon { color:#3498db; }
        .log-filters-bg { background: #f1f6fa; border-radius: 10px; }
        @media(max-width: 900px) {
            .log-details { max-width: 120px; font-size:0.91em; }
            .logs-table-container { padding:1rem }
            .logs-header { padding:1rem; margin:-1rem -1rem 1rem -1rem; }
        }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="logs-table-container mx-auto">
        <div class="logs-header d-flex flex-wrap flex-md-nowrap justify-content-between align-items-center mb-3">
            <h2 class="mb-0"><i class="fas fa-clipboard-list me-2"></i>Logs del Sistema</h2>
            <a href="gestion_personal.php" class="btn btn-outline-light btn-sm">
                <i class="fas fa-arrow-left me-1"></i>Panel
            </a>
        </div>
        <!-- Filtros de búsqueda -->
        <form class="row g-2 mb-4 filter-form align-items-end log-filters-bg px-3 py-3" method="get">
            <div class="col-md-3">
                <label class="form-label mb-1">Buscar detalles, IP, User Agent</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-search search-icon"></i></span>
                    <input type="text" class="form-control border-start-0" name="busqueda" placeholder="Buscar..." value="<?= htmlspecialchars($busqueda) ?>">
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label mb-1">Tipo de evento</label>
                <select name="event_type" class="form-select">
                    <option value="">Todos</option>
                    <?php foreach ($event_types as $et): ?>
                        <option value="<?= htmlspecialchars($et) ?>" <?= $event_type == $et ? 'selected' : '' ?>><?= htmlspecialchars($et) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label mb-1">Usuario</label>
                <select name="user_id" class="form-select">
                    <option value="">Todos</option>
                    <?php foreach ($usuarios as $uid=>$uname): ?>
                        <option value="<?= $uid ?>" <?= $user_id == $uid ? 'selected' : '' ?>><?= htmlspecialchars($uname) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter me-1"></i>Filtrar</button>
            </div>
            <div class="col-md-1 d-grid">
                <?php if ($busqueda || $event_type || $user_id): ?>
                    <a href="logs_admin.php" class="btn btn-outline-danger"><i class="fas fa-times"></i></a>
                <?php endif; ?>
            </div>
        </form>

        <?php if ($total_registros == 0): ?>
            <div class="alert alert-warning text-center mt-4">No se encontraron logs para los filtros seleccionados.</div>
        <?php else: ?>
        <div class="table-responsive mb-3">
            <table class="table table-hover table-bordered logs-table align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Fecha/Hora</th>
                        <th>Usuario</th>
                        <th>Evento</th>
                        <th>Detalles</th>
                        <th>IP</th>
                        <th>User Agent</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($logs as $i => $log): ?>
                    <tr>
                        <td><?= ($offset+$i+1) ?></td>
                        <td>
                            <span class="badge bg-light text-dark">
                                <?= date('Y-m-d H:i:s', strtotime($log['created_at'])) ?>
                            </span>
                        </td>
                        <td>
                            <i class="fas fa-user-circle me-1 text-secondary"></i>
                            <?= htmlspecialchars($log['username'] ?? ('ID:'.$log['user_id'])) ?>
                        </td>
                        <td>
                            <span class="log-type px-2 py-1 rounded">
                                <?= htmlspecialchars($log['event_type']) ?>
                            </span>
                        </td>
                        <td class="log-details"><?= htmlspecialchars($log['details']) ?></td>
                        <td class="ip-useragent"><?= htmlspecialchars($log['ip_address']) ?></td>
                        <td class="ip-useragent"><?= htmlspecialchars($log['user_agent']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <!-- Paginación -->
        <nav>
            <ul class="pagination">
                <?php if ($pagina > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $pagina-1])) ?>">&laquo; Anterior</a>
                    </li>
                <?php endif; ?>
                <?php
                    $inicio = max(1, $pagina - 2);
                    $fin = min($total_paginas, $pagina + 2);
                    if ($inicio > 1): ?>
                    <li class="page-item"><a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => 1])) ?>">1</a></li>
                    <?php if ($inicio > 2): ?>
                    <li class="page-item disabled"><span class="page-link">...</span></li>
                    <?php endif; endif; ?>
                <?php for ($p = $inicio; $p <= $fin; $p++): ?>
                    <li class="page-item <?= $p == $pagina ? 'active' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $p])) ?>"><?= $p ?></a>
                    </li>
                <?php endfor; ?>
                <?php if ($fin < $total_paginas): ?>
                    <?php if ($fin < $total_paginas - 1): ?>
                    <li class="page-item disabled"><span class="page-link">...</span></li>
                    <?php endif; ?>
                    <li class="page-item"><a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $total_paginas])) ?>"><?= $total_paginas ?></a></li>
                <?php endif; ?>
                <?php if ($pagina < $total_paginas): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $pagina+1])) ?>">Siguiente &raquo;</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>