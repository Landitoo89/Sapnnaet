<?php
session_start();
date_default_timezone_set('America/Caracas');

require 'conexion/conexion_db.php';

// Solo permitir acceso a admin
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'admin') {
    header('Location: index.php');
    exit;
}

// --- Variables seguras por defecto ---
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$event_type = isset($_GET['event_type']) ? trim($_GET['event_type']) : '';
$user_id = isset($_GET['user_id']) ? trim($_GET['user_id']) : '';

$pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$registros_por_pagina = 20;
$offset = ($pagina - 1) * $registros_por_pagina;

// --- Filtros para SQL ---
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

// --- Total de registros para paginación ---
$sql_count = "SELECT COUNT(*) as total FROM session_logs $where";
$stmt_count = $conexion->prepare($sql_count);
if (!empty($parametros)) {
    $stmt_count->bind_param($tipos, ...$parametros);
}
$stmt_count->execute();
$total_registros = $stmt_count->get_result()->fetch_assoc()['total'];
$total_paginas = ceil($total_registros / $registros_por_pagina);

// --- Logs paginados ---
$sql = "SELECT sl.*, CONCAT(u.nombre, ' ', u.apellido) as username 
        FROM session_logs sl
        LEFT JOIN usuarios u ON sl.user_id = u.id
        $where
        ORDER BY sl.created_at DESC
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

// --- Event types para filtro ---
$event_types = [];
$res_types = $conexion->query("SELECT DISTINCT event_type FROM session_logs ORDER BY event_type ASC");
while ($row = $res_types->fetch_assoc()) {
    $event_types[] = $row['event_type'];
}

// --- Usuarios para filtro ---
$usuarios = [];
$res_users = $conexion->query("SELECT id, nombre, apellido FROM usuarios ORDER BY nombre ASC, apellido ASC");
while ($row = $res_users->fetch_assoc()) {
    $usuarios[$row['id']] = $row['nombre'].' '.$row['apellido'];
}

require $_SERVER['DOCUMENT_ROOT']."/proyecto/inicio/sidebar.php";

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Logs de Sesión</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap 5.3 y FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <!-- Hacker-style Animated Background (Matrix) -->
    <style>
        html, body { height: 100%; }
        body {
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #d8f3ff;
            background: #10151e;
            position: relative;
            overflow-x: hidden;
        }
        #matrix-bg {
            position: fixed;
            left: 0; top: 0;
            width: 100vw; height: 100vh;
            z-index: 0;
            pointer-events: none;
            opacity: 0.18;
            background: transparent;
        }
        .logs-table-container {
            background: rgba(18,22,30,0.95);
            border-radius: 22px;
            box-shadow: 0 8px 44px 0 #3498db22, 0 0px 2px 0 #1effc455;
            padding: 2.5rem 2rem 2rem 2rem;
            margin-top: 2rem;
            max-width: 100%;
            z-index: 1;
            position: relative;
        }
        .logs-header {
            background: linear-gradient(90deg, #0e212e 0%, #3498db 100%);
            color: #fff;
            border-radius: 14px 14px 0 0;
            padding: 1.6rem 2rem 1.1rem 2rem;
            margin: -2.5rem -2rem 2.2rem -2rem;
            border-bottom: 2px solid #2c3e50;
            box-shadow: 0 1px 0 #3498db55;
        }
        .logs-header h2 {
            font-size: 2.1rem;
            font-weight: 700;
            letter-spacing: -1px;
            text-shadow: 0 7px 26px #0008, 0 1px 0 #3498db77;
        }
        .logs-header .btn {
            background: rgba(44,152,219,0.24);
            color: #fff;
            border: 1.5px solid #3498db77;
        }
        .logs-header .btn:hover { background: #3498db; color:#fff; }

        /* Enhanced Search Bar */
        .cyber-searchbar {
            background: #181f29;
            border-radius: 12px;
            box-shadow: 0 0 11px #17ffe1a2;
            border: 1.5px solid #3498db44;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: box-shadow 0.2s;
        }
        .cyber-searchbar:focus-within {
            box-shadow: 0 0 24px #00ffae, 0 0 2px #3498db;
            border-color: #00ffae;
        }
        .cyber-searchbar input {
            background: transparent;
            border: none;
            outline: none;
            color: #aeeeff;
            font-size: 1.05em;
            flex: 1;
        }
        .cyber-searchbar input::placeholder { color: #33ffe0be; }
        .cyber-searchbar .input-group-text {
            background: none;
            border: none;
            color: #30ffb5;
            font-size: 1.25em;
        }
        .filter-form label { color: #80ffc4; }
        .filter-form select, .filter-form .form-control {
            background: #121c26;
            color: #7ddeff;
            border: 1.5px solid #3498db44;
            font-size: 0.99em;
        }
        .filter-form .form-select:focus, .filter-form .form-control:focus {
            border-color: #00ffae;
            background: #181f29;
            color: #e0f3ff;
            outline: 0;
        }
        .filter-form .btn-primary {
            background: linear-gradient(90deg,#00ffae 50%, #3498db 100%);
            border: none;
            color: #181f29;
            font-weight: 700;
            letter-spacing: 1.5px;
        }
        .filter-form .btn-primary:hover {
            background: linear-gradient(90deg,#3498db 50%, #00ffae 100%);
            color:rgb(21, 255, 0);
        }
        .btn-outline-danger { border-radius: 50%; padding: 0.3em 0.7em;}

        .logs-table thead { background: linear-gradient(90deg, #0e212e, #3498db); color:#fff; border-top: 1px solid #3498db; }
        .logs-table th, .logs-table td { vertical-align: middle; font-size:1.01em; background: #151d26e6; color: #c4e1ff;}
        .logs-table th { color: #deebff; font-weight:600; text-shadow:0 1px 0 #0007;}
        .logs-table tbody tr { border-left: 5px solid transparent; transition: box-shadow 0.17s, border-left-color 0.17s; }
        .logs-table tbody tr:hover { background:rgb(0, 255, 72); box-shadow: 0 3px 18px #00ffae33; border-left: 5px solid #00ffae; }
        .log-details { max-width: 310px; white-space: pre-wrap; word-break: break-word; color: #d4e3fa;}
        .log-type {
            font-size:0.97em; font-family:monospace;
            padding: 2px 9px;
            border-radius: 6px;
            background: linear-gradient(90deg, #091d42 60%, #00ffae 100%);
            color: #00ffae;
            border: 1.5px solid #00ffae77;
            letter-spacing: 0.5px;
            text-shadow: 0 0 2px #00ffae, 0 0 6pxrgb(51, 255, 0);
        }
        .ip-useragent {
            font-size:0.91em;
            color:#60baff;
            font-family: 'Fira Mono', 'Consolas', monospace;
            background:rgb(4, 255, 0);
            border-radius: 7px;
            padding: 2px 7px 2px 6px;
        }
        .badge.bg-light.text-dark { background: #0b1422!important; color: #aeeeff!important; font-weight:600; letter-spacing:1px;}
        .fa-door-open { color: #00ffae; }
        .fa-arrow-left { color: #fff; }
        .fa-user-circle { color: #00ffae; }

        /* Cyber Pagination */
        .pagination .page-link {
            background: #0b1a25;
            border: 1.3px solid #00ffae44;
            color: #00ffae;
            font-weight:600;
            transition: all .18s;
            border-radius: 9px;
        }
        .pagination .page-link:hover, .pagination .page-item.active .page-link {
            background: linear-gradient(90deg,#00ffae 50%, #3498db 100%);
            color: #181f29;
            border-color: #00ffae;
            font-weight: bold;
            box-shadow: 0 0 7px #00ffae88;
        }
        .pagination .page-item.disabled .page-link {
            background: #181f29;
            color: #2c3e50;
        }
        .pagination { justify-content: center; margin-top:2rem; }

        /* Matrix Scrollbar */
        ::-webkit-scrollbar { width: 8px; background: #16223a;}
        ::-webkit-scrollbar-thumb { background: #00ffaecc; border-radius: 8px;}
        ::selection { background: #00ffae88; }

        @media(max-width: 950px) {
            .log-details { max-width: 90px; font-size:0.93em; }
            .logs-table-container { padding:1.1rem }
            .logs-header { padding:1.1rem; margin:-1.1rem -1.1rem 1.1rem -1.1rem; font-size:1.18em;}
        }
    </style>
</head>
<body>
<canvas id="matrix-bg"></canvas>
<div class="container py-4">
    <div class="logs-table-container mx-auto">
        <div class="logs-header d-flex flex-wrap flex-md-nowrap justify-content-between align-items-center mb-3 shadow">
            <h2 class="mb-0"><i class="fas fa-door-open me-2"></i>SESSION LOGS</h2>
        </div>
        <!-- Filtros de búsqueda -->
        <form class="row g-2 mb-4 filter-form align-items-end log-filters-bg px-3 py-3" method="get" autocomplete="off">
            <div class="col-md-4">
                <label class="form-label mb-1">Buscar detalles, IP, User Agent</label>
                <div class="cyber-searchbar px-2">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" name="busqueda" placeholder="Buscar..." value="<?= htmlspecialchars($busqueda ?? '') ?>">
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
            <div class="col-md-1 d-grid">
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter me-1"></i>Filtrar</button>
            </div>
            <div class="col-md-1 d-grid">
                <?php if ($busqueda || $event_type || $user_id): ?>
                    <a href="session_logs.php" class="btn btn-outline-danger" title="Limpiar filtros"><i class="fas fa-times"></i></a>
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
                            <i class="fas fa-user-circle me-1"></i>
                            <?= htmlspecialchars($log['username'] ?? ('ID:'.$log['user_id'])) ?>
                        </td>
                        <td>
                            <span class="log-type">
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
            <ul class="pagination justify-content-center">
                <?php if ($pagina > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $pagina-1])) ?>">&laquo;</a>
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
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $pagina+1])) ?>">&raquo;</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>
<!-- ANIMATED MATRIX BACKGROUND -->
<script>
const canvas = document.getElementById('matrix-bg');
const ctx = canvas.getContext('2d');
let w = window.innerWidth;
let h = window.innerHeight;
canvas.width = w;
canvas.height = h;
let cols = Math.floor(w / 20) + 1;
let ypos = Array(cols).fill(0);
function matrix() {
    ctx.fillStyle = 'rgba(16, 21, 30, 0.13)';
    ctx.fillRect(0, 0, w, h);
    ctx.font = '17px monospace';
    for (let i = 0; i < ypos.length; i++) {
        const txt = String.fromCharCode(0x30A0 + Math.random() * 96);
        ctx.fillStyle = '#00ffae';
        ctx.fillText(txt, i * 20, ypos[i] * 20 + 16);
        if (Math.random() > 0.985) ypos[i] = 0;
        else ypos[i]++;
    }
}
setInterval(matrix, 44);
window.addEventListener('resize', () => {
    w = window.innerWidth;
    h = window.innerHeight;
    canvas.width = w;
    canvas.height = h;
    cols = Math.floor(w / 20) + 1;
    ypos = Array(cols).fill(0);
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>