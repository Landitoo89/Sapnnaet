<?php
session_start();
date_default_timezone_set('America/Caracas');
require_once __DIR__ . '/conexion/conexion_db.php';

// Solo permitir acceso a admin
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$tabla_filtro = isset($_GET['tabla']) ? trim($_GET['tabla']) : '';
$usuario_filtro = isset($_GET['usuario']) ? trim($_GET['usuario']) : '';
$pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$registros_por_pagina = 20;
$offset = ($pagina - 1) * $registros_por_pagina;

// Filtros para SQL
$condiciones = [];
$parametros = [];
$tipos = '';

if ($busqueda !== '') {
    $condiciones[] = "(tabla LIKE ? OR archivo LIKE ? OR usuario LIKE ?)";
    $parametros[] = "%$busqueda%";
    $parametros[] = "%$busqueda%";
    $parametros[] = "%$busqueda%";
    $tipos .= 'sss';
}
if ($tabla_filtro !== '') {
    $condiciones[] = "tabla = ?";
    $parametros[] = $tabla_filtro;
    $tipos .= 's';
}
if ($usuario_filtro !== '') {
    $condiciones[] = "usuario = ?";
    $parametros[] = $usuario_filtro;
    $tipos .= 's';
}

$where = '';
if (count($condiciones) > 0) {
    $where = 'WHERE ' . implode(' AND ', $condiciones);
}

// Total de registros para paginación
$sql_count = "SELECT COUNT(*) as total FROM respaldos_tablas $where";
$stmt_count = $conexion->prepare($sql_count);
if (!empty($parametros)) {
    $stmt_count->bind_param($tipos, ...$parametros);
}
$stmt_count->execute();
$total_registros = $stmt_count->get_result()->fetch_assoc()['total'];
$total_paginas = ceil($total_registros / $registros_por_pagina);

// Respaldos paginados
$sql = "SELECT * FROM respaldos_tablas
        $where
        ORDER BY fecha DESC
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
$respaldos = $resultado->fetch_all(MYSQLI_ASSOC);

// Tablas únicas para filtro
$tablas_filtro = [];
$res_tablas = $conexion->query("SELECT DISTINCT tabla FROM respaldos_tablas ORDER BY tabla ASC");
while ($row = $res_tablas->fetch_assoc()) {
    $tablas_filtro[] = $row['tabla'];
}

// Usuarios únicos para filtro
$usuarios_filtro = [];
$res_users = $conexion->query("SELECT DISTINCT usuario FROM respaldos_tablas ORDER BY usuario ASC");
while ($row = $res_users->fetch_assoc()) {
    $usuarios_filtro[] = $row['usuario'];
}

// Restaurar si POST
$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restaurar_id'])) {
    $id = (int) $_POST['restaurar_id'];
    $stmt_res = $conexion->prepare("SELECT * FROM respaldos_tablas WHERE id=? LIMIT 1");
    $stmt_res->bind_param("i", $id);
    $stmt_res->execute();
    $res = $stmt_res->get_result();
    if ($row = $res->fetch_assoc()) {
        $archivo_rel = $row['archivo'];
        $archivo = __DIR__ . '/../' . $archivo_rel;
        if (file_exists($archivo)) {
            $tabla = $row['tabla'];
            $is_full = ($tabla === 'completa');
            $mysql_bin = 'C:\\wamp64\\bin\\mysql\\mysql9.1.0\\bin\\mysql.exe';
            $comando = "\"{$mysql_bin}\" --user={$usuario} --password={$contraseña} --host={$servidor} {$basedatos} < " . escapeshellarg($archivo);
            $output = shell_exec($comando . " 2>&1");
            $mensaje = "<div class='alert alert-success mt-3'>Restauración de <b>"
                . htmlspecialchars($tabla)
                . "</b> completada.<br><small>Salida:</small><br><pre style='max-height:150px;overflow:auto;background:#111;color:#0fa;'>"
                . htmlspecialchars($output)
                . "</pre></div>";
        } else {
            $mensaje = "<div class='alert alert-danger mt-3'>Archivo no encontrado: <code>" . htmlspecialchars($archivo_rel) . "</code></div>";
        }
    } else {
        $mensaje = "<div class='alert alert-danger mt-3'>Respaldo no encontrado.</div>";
    }
}
require $_SERVER['DOCUMENT_ROOT']."/proyecto/inicio/sidebar.php";

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Restaurar Respaldos BD</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap y FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body {
            background: #10151e;
            color: #d8f3ff;
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            position: relative;
            overflow-x: hidden;
        }
        #matrix-bg {
            position: fixed;
            left: 0; top: 0; width: 100vw; height: 100vh; z-index: 0;
            pointer-events: none; opacity: 0.15; background: transparent;
        }
        .backup-container {
            background: rgba(18,22,30,0.96);
            border-radius: 22px;
            box-shadow: 0 8px 44px 0 #3498db22, 0 0px 2px 0 #1effc455;
            padding: 2.5rem 2rem 2rem 2rem;
            margin-top: 2rem;
            max-width: 950px;
            z-index: 1; position: relative;
        }
        .backup-header {
            background: linear-gradient(90deg, #0e212e 0%, #3498db 100%);
            color: #fff;
            border-radius: 14px 14px 0 0;
            padding: 1.6rem 2rem 1.1rem 2rem;
            margin: -2.5rem -2rem 2.2rem -2rem;
            border-bottom: 2px solid #2c3e50;
            box-shadow: 0 1px 0 #3498db55;
            text-align:center;
        }
        .backup-header h2 {
            font-size: 2.1rem;
            font-weight: 700;
            letter-spacing: -1px;
            text-shadow: 0 7px 26px #0008, 0 1px 0 #3498db77;
        }
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
            color: #0e212e;
        }
        .btn-outline-danger { border-radius: 50%; padding: 0.3em 0.7em;}
        .respaldos-table thead { background: linear-gradient(90deg, #0e212e, #3498db); color:#fff; border-top: 1px solid #3498db; }
        .respaldos-table th, .respaldos-table td { vertical-align: middle; font-size:1.01em; background: #151d26e6; color: #c4e1ff;}
        .respaldos-table th { color: #deebff; font-weight:600; text-shadow:0 1px 0 #0007;}
        .respaldos-table tbody tr { border-left: 5px solid transparent; transition: box-shadow 0.17s, border-left-color 0.17s; }
        .respaldos-table tbody tr:hover { background: #11203a; box-shadow: 0 3px 18px #00ffae33; border-left: 5px solid #00ffae; }
        .log-type {
            font-size:0.97em; font-family:monospace;
            padding: 2px 9px;
            border-radius: 6px;
            background: linear-gradient(90deg, #091d42 60%, #00ffae 100%);
            color: #00ffae;
            border: 1.5px solid #00ffae77;
            letter-spacing: 0.5px;
            text-shadow: 0 0 2px #00ffae, 0 0 6px #10151e;
        }
        .badge.bg-light.text-dark { background: #0b1422!important; color: #aeeeff!important; font-weight:600; letter-spacing:1px;}
        .fa-database { color: #00ffae; }
        .fa-arrow-left { color: #fff; }
        .fa-user-circle { color: #00ffae; }
        .fa-file-arrow-up { color: #0fa; }
        .fa-download { color: #00ffae; }
        .fa-trash { color: #ff5252; }
        .btn-cyber {
            background: linear-gradient(90deg,#00ffae 50%, #3498db 100%);
            color: #10151e; font-weight: bold; font-size: 1.1em;
            border: none; border-radius: 9px; padding: 0.7em 2em;
            margin: 0.5em 0.4em 0.2em 0;
            box-shadow: 0 1px 8px #00ffae33;
            transition: background 0.18s, color 0.18s;
        }
        .btn-cyber:hover { background: linear-gradient(90deg,#3498db 50%, #00ffae 100%); color: #fff; }
        .btn-cyber-alt { background: #181f29; color: #00ffae; border: 1.5px solid #00ffae77; }
        .btn-cyber-alt:hover { background: #00ffae; color: #181f29; }
        ::-webkit-scrollbar { width: 8px; background: #16223a;}
        ::-webkit-scrollbar-thumb { background: #00ffaecc; border-radius: 8px;}
        ::selection { background: #00ffae88; }
        @media(max-width: 1050px) {
            .backup-container { padding:1.1rem; max-width:98vw;}
            .backup-header { padding:1.1rem; margin:-1.1rem -1.1rem 1.1rem -1.1rem; font-size:1.18em;}
        }
    </style>
</head>
<body>
<canvas id="matrix-bg"></canvas>
<div class="container py-4">
    <div class="backup-container mx-auto">
        <div class="backup-header shadow mb-3">
            <h2><i class="fas fa-database"></i> Restaurar Respaldos de Base de Datos</h2>
        </div>
        <form class="row g-2 mb-4 filter-form align-items-end log-filters-bg px-3 py-3" method="get" autocomplete="off">
            <div class="col-md-4">
                <label class="form-label mb-1">Buscar tabla, archivo o usuario</label>
                <div class="cyber-searchbar px-2">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" name="busqueda" placeholder="Buscar..." value="<?= htmlspecialchars($busqueda ?? '') ?>">
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label mb-1">Tabla</label>
                <select name="tabla" class="form-select">
                    <option value="">Todas</option>
                    <?php foreach ($tablas_filtro as $tf): ?>
                        <option value="<?= htmlspecialchars($tf) ?>" <?= $tabla_filtro == $tf ? 'selected' : '' ?>><?= htmlspecialchars($tf) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label mb-1">Usuario</label>
                <select name="usuario" class="form-select">
                    <option value="">Todos</option>
                    <?php foreach ($usuarios_filtro as $uf): ?>
                        <option value="<?= htmlspecialchars($uf) ?>" <?= $usuario_filtro == $uf ? 'selected' : '' ?>><?= htmlspecialchars($uf) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-1 d-grid">
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter me-1"></i>Filtrar</button>
            </div>
            <div class="col-md-1 d-grid">
                <?php if ($busqueda || $tabla_filtro || $usuario_filtro): ?>
                    <a href="restaurar_bd.php" class="btn btn-outline-danger" title="Limpiar filtros"><i class="fas fa-times"></i></a>
                <?php endif; ?>
            </div>
        </form>

        <?= $mensaje ?>

        <?php if ($total_registros == 0): ?>
            <div class="alert alert-warning text-center mt-4">No se encontraron respaldos para los filtros seleccionados.</div>
        <?php else: ?>
        <div class="table-responsive mb-3">
            <table class="table table-hover table-bordered respaldos-table align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Fecha/Hora</th>
                        <th>Tabla</th>
                        <th>Archivo</th>
                        <th>Usuario</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($respaldos as $i => $res): ?>
                    <tr>
                        <td><?= ($offset+$i+1) ?></td>
                        <td>
                            <span class="badge bg-light text-dark">
                                <?= date('Y-m-d H:i:s', strtotime($res['fecha'])) ?>
                            </span>
                        </td>
                        <td>
                            <span class="log-type"><?= htmlspecialchars($res['tabla'] === 'completa' ? 'Completa' : $res['tabla']) ?></span>
                        </td>
                        <td>
                            <a href="<?= htmlspecialchars('../'.$res['archivo']) ?>" download class="btn btn-sm btn-outline-info" title="Descargar">
                                <i class="fas fa-download"></i> <?= htmlspecialchars(basename($res['archivo'])) ?>
                            </a>
                        </td>
                        <td>
                            <i class="fas fa-user-circle me-1"></i>
                            <?= htmlspecialchars($res['usuario']) ?>
                        </td>
                        <td>
                            <form method="post" onsubmit="return confirm('¿Seguro que deseas restaurar este respaldo? ¡Esto puede sobrescribir datos importantes!');" style="display:inline;">
                                <input type="hidden" name="restaurar_id" value="<?= $res['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-warning">
                                    <i class="fas fa-file-arrow-up"></i> Restaurar
                                </button>
                            </form>
                        </td>
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
<script>
// Matrix background
const canvas = document.getElementById('matrix-bg');
const ctx = canvas.getContext('2d');
let w = window.innerWidth, h = window.innerHeight;
canvas.width = w; canvas.height = h;
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
    w = window.innerWidth; h = window.innerHeight;
    canvas.width = w; canvas.height = h;
    cols = Math.floor(w / 20) + 1; ypos = Array(cols).fill(0);
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>