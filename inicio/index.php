<?php
require 'conexion.php';
ob_start();
session_start();
date_default_timezone_set('America/Caracas');

require $_SERVER['DOCUMENT_ROOT']."/proyecto/inicio/sidebar.php";
// Obtener el mes actual y el día de hoy
$mes_actual = date('m');
$dia_actual = date('d');
?>

<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tilin Dashboard Pro</title>
    <!-- Bootstrap 5 y Font Awesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <!-- Fuente Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --sidebar-expanded: 190px;
            --sidebar-collapsed: 75px;
            --transition: 0.3s cubic-bezier(.86,0,.07,1);

            /* Colores tema claro (por defecto) */
            --bg-main: #eef1f6;
            --bg-gradient: linear-gradient(120deg, #eef1f6 0%, #dbe6f6 100%);
            --bg-card: #fff;
            --bg-card-alt: #f5f7fa;
            --bg-card-2: #c3cfe2;
            --text-main: #232323;
            --text-secondary: #34395c;
            --stat-label: #666;
            --stat-value: #4538b5;
            --dashboard-card-hover: rgba(67, 56, 202, 0.20);
            --dashboard-card-shadow: 0 6px 34px 0 rgba(67, 56, 202, 0.14), 0 2px 8px 0 rgba(160, 160, 160, 0.09);
            --notification-panel-bg: rgba(255,255,255,0.98);
            --notification-title: #bc519c;
            --birthday-icon-bg: #fbe7f2;
            --birthday-icon-color: #bc519c;
            --birthday-label: #bc519c;
            --mini-stat-bg: #fff;
            --mini-stat-label: #666;
            --mini-stat-value: #4538b5;
            --dashboard-card-label: #34395c;
            --dashboard-card-text: #5d5d5d;
            --dashboard-card-bg-1: linear-gradient(120deg, #f5f7fa 0%, #c3cfe2 100%);
            --card-statistics-bg: rgba(255,255,255,0.80);
            --card-header-bg: #f4f0fa;
        }
        /* Dark mode overrides */
        [data-theme="dark"] {
            --bg-main: #15171c;
            --bg-gradient: linear-gradient(120deg, #23242a 0%, #191c22 100%);
            --bg-card: #23242a;
            --bg-card-alt: #191c22;
            --bg-card-2: #23242a;
            --text-main: #f4f6fb;
            --text-secondary: #e2e7ef;
            --stat-label: #b5b8c0;
            --stat-value: #8c7be7;
            --dashboard-card-hover: rgba(67,56,202,0.33);
            --dashboard-card-shadow: 0 8px 30px 0 rgba(35,34,58,0.22), 0 2px 12px 0 rgba(0,0,0,0.09);
            --notification-panel-bg: rgba(35,36,42,0.98);
            --notification-title: #ff94e8;
            --birthday-icon-bg: #3a2440;
            --birthday-icon-color: #ff94e8;
            --birthday-label: #ff94e8;
            --mini-stat-bg: #23242a;
            --mini-stat-label: #babfcc;
            --mini-stat-value: #8c7be7;
            --dashboard-card-label: #e2e7ef;
            --dashboard-card-text: #b5b8c0;
            --dashboard-card-bg-1: linear-gradient(120deg, #23242a 0%, #191c22 100%);
            --card-statistics-bg: rgba(35,36,42,0.80);
            --card-header-bg: #23242a;
        }
        html, body {
            font-family: 'Inter', system-ui, Arial, sans-serif;
            background: var(--bg-gradient) fixed;
            min-height: 100vh;
            color: var(--text-main);
        }
        #main-content {
            margin-left: var(--sidebar-collapsed);
            transition: margin-left var(--transition);
            min-height: 100vh;
            padding: 32px 18px 18px 18px;
        }
        .sidebar:not(.collapsed) ~ #main-content,
        body:not(.sidebar-collapsed) #main-content {
            margin-left: var(--sidebar-expanded);
        }
        /* Botón modo oscuro/claro */
        .theme-toggle-btn {
            position: fixed;
            top: 18px;
            right: 18px;
            z-index: 1101;
            background: var(--bg-card);
            border: 1px solid #b5b8c0;
            color: var(--text-main);
            border-radius: 50%;
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 10px #2222;
            transition: background 0.2s, color 0.2s;
        }
        .theme-toggle-btn:hover {
            background: var(--bg-card-alt);
            color: #bc519c;
        }
        /* Mini stats */
        .mini-stats {
            display: flex;
            gap: 0.8rem;
            margin: 1.7rem 0 1.2rem 0;
            flex-wrap: wrap;
        }
        .mini-stat-card {
            flex: 1 1 145px;
            background: var(--mini-stat-bg);
            border-radius: 0.9rem;
            box-shadow: 0 1px 10px #8881;
            padding: 0.9rem 1rem;
            display: flex;
            align-items: center;
            gap: 0.7em;
            min-width: 0;
        }
        .mini-stat-icon {
            width: 38px;
            height: 38px;
            border-radius: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        .mini-stat-info {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }
        .mini-stat-label {
            font-size: 0.97rem;
            color: var(--mini-stat-label);
            font-weight: 600;
            margin-bottom: 0.1em;
        }
        .mini-stat-value {
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--mini-stat-value);
        }
        .badge-soft {
            background: #e9ecef;
            color: #444;
            font-size: 0.98em;
            font-weight: 700;
            border-radius: 0.7rem;
            padding: 0.25em 0.7em;
        }
        .badge-soft-primary { background: #ece7fa; color: #4538b5;}
        .badge-soft-danger { background: #fbe7f2; color: #bc519c; }
        [data-theme="dark"] .badge-soft {
            background: #23242a;
            color: #ff94e8;
        }
        /* Mejoras de tarjetas */
        .dashboard-cards-row {
            display: flex;
            flex-wrap: wrap;
            gap: 2rem;
            justify-content: flex-start;
            margin-bottom: 3rem;
        }
        .dashboard-card {
            flex: 1 1 260px;
            min-width: 240px;
            max-width: 340px;
            background: var(--dashboard-card-bg-1);
            border: none;
            border-radius: 1.7rem;
            box-shadow: var(--dashboard-card-shadow);
            padding: 1.9rem 1.7rem 1.6rem 1.7rem;
            position: relative;
            transition: transform .22s cubic-bezier(.22,1,.36,1), box-shadow .22s cubic-bezier(.22,1,.36,1);
            cursor: pointer;
            overflow: hidden;
            display: flex;
            align-items: center;
        }
        .dashboard-card:hover, .dashboard-card:focus-visible {
            transform: translateY(-8px) scale(1.03);
            box-shadow: 0 12px 36px 0 var(--dashboard-card-hover), 0 6px 20px 0 rgba(80, 80, 80, 0.11);
            z-index: 2;
            text-decoration: none;
        }
        .dashboard-card .icon-foreground {
            min-width: 62px;
            min-height: 62px;
            width: 62px;
            height: 62px;
            border-radius: 21px;
            background: rgba(120,90,220,0.12);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.7rem;
            margin-right: 1.3rem;
            box-shadow: 0 1px 7px 0 rgba(120,90,220,0.07);
            animation: pulseIcon 2.7s infinite cubic-bezier(0.7,0,0.5,1);
        }
        /* Colores */
        .dashboard-card .icon-foreground.total    { color: #4538b5; background: rgba(69,56,181,0.11);}
        .dashboard-card .icon-foreground.activos  { color: #199a66; background: rgba(25,154,102,0.12);}
        .dashboard-card .icon-foreground.vac      { color: #f0a900; background: rgba(240,169,0,0.12);}
        .dashboard-card .icon-foreground.rep      { color: #4485c6; background: rgba(68,133,198,0.13);}
        .dashboard-card .icon-foreground.cumple   { color: #bc519c; background: rgba(188,81,156,0.13);}
        @keyframes pulseIcon {
            0%, 100% { transform: scale(1);}
            50% { transform: scale(1.14);}
        }
        .dashboard-card .card-title {
            font-size: 2.2rem;
            font-weight: 900;
            letter-spacing: 0.7px;
            margin-bottom: .12rem;
            line-height: 1.09;
            color: var(--stat-value);
        }
        .dashboard-card .card-label {
            font-size: 1.08rem;
            font-weight: 700;
            color: var(--dashboard-card-label);
            margin-bottom: .37rem;
            letter-spacing: .6px;
        }
        .dashboard-card .card-text {
            font-size: 1.08rem;
            font-weight: 500;
            color: var(--dashboard-card-text);
            margin-bottom: 0;
        }
        .dashboard-card:link, .dashboard-card:visited, .dashboard-card:hover, .dashboard-card:active {
            text-decoration: none;
            color: inherit;
        }
        /* Notificaciones cumpleaños */
        .notification-panel {
            background: var(--notification-panel-bg);
            border-radius: 1.25rem;
            box-shadow: 0 2px 14px #8882;
            padding: 1.2rem 1.5rem 1.2rem 1.5rem;
            margin-bottom: 2rem;
            margin-top: 0.7rem;
        }
        .notification-title {
            font-size: 1.14rem;
            font-weight: 700;
            color: var(--notification-title);
            margin-bottom: 0.7rem;
            letter-spacing: 0.04em;
            display: flex;
            align-items: center;
            gap: 0.5em;
        }
        .birthday-list {
            list-style: none;
            margin: 0;
            padding: 0;
        }
        .birthday-list li {
            display: flex;
            align-items: center;
            gap: 0.7em;
            margin-bottom: 0.45em;
            font-size: 1.06rem;
            color: var(--text-main);
        }
        .birthday-icon {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: var(--birthday-icon-bg);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.55rem;
            color: var(--birthday-icon-color);
            border: 2px solid #bc519c66;
        }
        .birthday-label {
            font-weight: 600;
            color: var(--birthday-label);
            margin-left: 0.08em;
        }
        @media (max-width: 1200px) {
            .dashboard-cards-row { gap: 1.2rem;}
        }
        @media (max-width: 900px) {
            .dashboard-cards-row { gap: 1rem;}
            .dashboard-card { min-width: 180px; }
        }
        @media (max-width: 600px) {
            .dashboard-cards-row { gap: .7rem; }
            .dashboard-card { min-width: 120px; padding: 1.1rem 0.65rem 1rem 0.65rem;}
            #main-content { padding: 11px 2px 20px 2px;}
            .dashboard-card .icon-foreground { min-width: 48px; min-height: 48px; width: 48px; height: 48px; font-size: 1.45rem; }
            .mini-stats { gap: 0.4rem; }
            .mini-stat-card { min-width: 90px; padding: 0.7rem 0.6rem;}
        }
        .card.statistics-card {
            border-radius: 1.5rem;
            background: var(--card-statistics-bg);
            box-shadow: 0 2px 16px rgba(120,90,180,0.10);
            margin-top: 2rem;
            margin-bottom: 2rem;
        }
        .card-header {
            border-radius: 1.5rem 1.5rem 0 0 !important;
            background: var(--card-header-bg) !important;
            font-weight: 700;
            letter-spacing: 1px;
            font-size: 1.12rem;
            color: var(--text-main);
        }
    </style>
</head>
<body>
<!-- BOTÓN MODO OSCURO/MODO CLARO -->
<button class="theme-toggle-btn" id="themeToggleBtn" title="Cambiar modo" aria-label="Cambiar modo claro/oscuro">
    <i id="themeToggleIcon" class="fas fa-moon"></i>
</button>

<?php
// Obtener el total de empleados
$total_empleados = $conn->query("SELECT COUNT(*) FROM datos_personales")->fetchColumn();
// Obtener el total de empleados activos (usando la columna 'estado')
$total_activos = $conn->query("SELECT COUNT(*) FROM datos_laborales WHERE estado = 'activo'")->fetchColumn();
// Obtener el total de vacaciones vigentes (contando las vacaciones registradas)
$total_vacaciones = $conn->query("SELECT COUNT(*) FROM vacaciones WHERE fecha_fin > CURDATE()")->fetchColumn();
// Obtener el total de reposos vigentes (contando empleados en estado 'reposo' en datos_laborales)
$total_reposos = $conn->query("SELECT COUNT(*) FROM datos_laborales WHERE estado = 'reposo'")->fetchColumn();
// Obtener el total de cumpleañeros del mes actual
$stmt_cumpleanos = $conn->prepare("SELECT COUNT(*) FROM datos_personales WHERE MONTH(fecha_nacimiento) = ?");
$stmt_cumpleanos->execute([$mes_actual]);
$total_cumpleanos_mes = $stmt_cumpleanos->fetchColumn();

// Mini stats: porcentaje femenino/masculino
$total_masculino = $conn->query("SELECT COUNT(*) FROM datos_personales WHERE genero = 'Masculino'")->fetchColumn();
$total_femenino = $conn->query("SELECT COUNT(*) FROM datos_personales WHERE genero = 'Femenino'")->fetchColumn();
$total_otro = $conn->query("SELECT COUNT(*) FROM datos_personales WHERE genero NOT IN ('Masculino','Femenino')")->fetchColumn();
$porc_m = $total_empleados > 0 ? round($total_masculino * 100 / $total_empleados) : 0;
$porc_f = $total_empleados > 0 ? round($total_femenino * 100 / $total_empleados) : 0;

// Mini stats: antigüedad promedio
$antiguedad_prom = 0;
$row_ant = $conn->query("SELECT AVG(TIMESTAMPDIFF(YEAR, fecha_ingreso, CURDATE())) as prom FROM datos_laborales WHERE fecha_ingreso IS NOT NULL")->fetch();
if ($row_ant && !is_null($row_ant['prom'])) $antiguedad_prom = round($row_ant['prom'],1);

// Mini stats: nuevos ingresos este año
$nuevos_ano = $conn->query("SELECT COUNT(*) FROM datos_laborales WHERE YEAR(fecha_ingreso) = YEAR(CURDATE())")->fetchColumn();

// Cumpleañeros de HOY
$stmt_cumple_hoy = $conn->prepare("
    SELECT p.nombres, p.apellidos, p.fecha_nacimiento
    FROM datos_personales p
    WHERE DAY(p.fecha_nacimiento) = ? AND MONTH(p.fecha_nacimiento) = ?
");
$stmt_cumple_hoy->execute([$dia_actual, $mes_actual]);
$cumpleaneros_hoy = $stmt_cumple_hoy->fetchAll(PDO::FETCH_ASSOC);
?>

<div id="main-content">
    <div class="d-flex flex-wrap flex-md-nowrap align-items-center pt-4 pb-3 mb-4 border-bottom">
        <h1 class="h1 display-4 mb-0 fw-bold"><i class="fas fa-chart-line me-2 text-primary"></i> Dashboard de Trabajadores</h1>
    </div>

    <!-- Notificaciones: Cumpleañeros de HOY -->
    <div class="notification-panel mb-4">
        <div class="notification-title">
            <i class="fas fa-birthday-cake"></i> Cumpleaños de hoy
        </div>
        <?php if (count($cumpleaneros_hoy)): ?>
            <ul class="birthday-list mb-0">
                <?php foreach($cumpleaneros_hoy as $emp): ?>
                <li>
                    <span class="birthday-icon"><i class="fas fa-gift"></i></span>
                    <span class="birthday-label"><?= htmlspecialchars($emp['nombres'] . ' ' . $emp['apellidos']) ?></span>
                    <span class="small text-muted ms-2"><i class="fas fa-gift"></i> Hoy está de cumpleaños</span>
                </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <div class="text-muted ps-1"><i class="far fa-smile-beam me-1"></i> No hay cumpleaños hoy</div>
        <?php endif; ?>
    </div>

    <!-- Mini stats bar -->
    <div class="mini-stats">
        <div class="mini-stat-card">
            <div class="mini-stat-icon" style="background:#e3f0fc;color:#2d91e5"><i class="fas fa-mars"></i></div>
            <div class="mini-stat-info">
                <span class="mini-stat-label">Hombres</span>
                <span class="mini-stat-value"><?= $total_masculino ?> <span class="badge badge-soft badge-soft-primary"><?= $porc_m ?>%</span></span>
            </div>
        </div>
        <div class="mini-stat-card">
            <div class="mini-stat-icon" style="background:#fce3ef;color:#e84393"><i class="fas fa-venus"></i></div>
            <div class="mini-stat-info">
                <span class="mini-stat-label">Mujeres</span>
                <span class="mini-stat-value"><?= $total_femenino ?> <span class="badge badge-soft badge-soft-danger"><?= $porc_f ?>%</span></span>
            </div>
        </div>
        <div class="mini-stat-card">
            <div class="mini-stat-icon" style="background:#ececec;color:#888"><i class="fas fa-genderless"></i></div>
            <div class="mini-stat-info">
                <span class="mini-stat-label">Otros</span>
                <span class="mini-stat-value"><?= $total_otro ?></span>
            </div>
        </div>
        <div class="mini-stat-card">
            <div class="mini-stat-icon" style="background:#e5e8fa;color:#3f51b5"><i class="fas fa-calendar-check"></i></div>
            <div class="mini-stat-info">
                <span class="mini-stat-label">Antigüedad Prom.</span>
                <span class="mini-stat-value"><?= $antiguedad_prom ?> años</span>
            </div>
        </div>
        <div class="mini-stat-card">
            <div class="mini-stat-icon" style="background:#d0fff6;color:#00b894"><i class="fas fa-user-plus"></i></div>
            <div class="mini-stat-info">
                <span class="mini-stat-label">Nuevos este año</span>
                <span class="mini-stat-value"><?= $nuevos_ano ?></span>
            </div>
        </div>
    </div>

    <div class="dashboard-cards-row">
        <a href="gestion/gestion_personal.php" class="dashboard-card total position-relative" tabindex="0" aria-label="Total empleados">
            <div class="icon-foreground total"><i class="fas fa-users"></i></div>
            <div>
                <div class="card-label">Total Empleados</div>
                <div class="card-title total"><?= $total_empleados ?></div>
                <div class="card-text">Registrados en el sistema</div>
            </div>
        </a>
        <a href="gestion/gestion_laboral.php" class="dashboard-card activos position-relative text-decoration-none" tabindex="0" aria-label="Empleados activos">
            <div class="icon-foreground activos"><i class="fas fa-user-check"></i></div>
            <div>
                <div class="card-label">Activos</div>
                <div class="card-title activos"><?= $total_activos ?></div>
                <div class="card-text">En actividad</div>
            </div>
        </a>
        <a href="vacaciones/gestion-vacaciones.php" class="dashboard-card vac position-relative text-decoration-none" tabindex="0" aria-label="Vacaciones">
            <div class="icon-foreground vac"><i class="fas fa-umbrella-beach"></i></div>
            <div>
                <div class="card-label">Vacaciones</div>
                <div class="card-title vac"><?= $total_vacaciones ?></div>
                <div class="card-text">Fuera de oficina</div>
            </div>
        </a>
        <a href="reposos/gestion-reposos.php" class="dashboard-card rep position-relative text-decoration-none" tabindex="0" aria-label="Reposos">
            <div class="icon-foreground rep"><i class="fas fa-bed"></i></div>
            <div>
                <div class="card-label">Reposos</div>
                <div class="card-title rep"><?= $total_reposos ?></div>
                <div class="card-text">En período de reposo</div>
            </div>
        </a>
        <a href="cumple/calendario.php" class="dashboard-card cumple position-relative text-decoration-none" tabindex="0" aria-label="Cumpleaños">
            <div class="icon-foreground cumple"><i class="fas fa-birthday-cake"></i></div>
            <div>
                <div class="card-label">Cumpleaños</div>
                <div class="card-title cumple"><?= $total_cumpleanos_mes ?></div>
                <div class="card-text">En este mes</div>
            </div>
        </a>
    </div>
    <div class="row">
        <div class="col-12">
            <div class="card statistics-card shadow-lg">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-chart-bar me-2 text-warning"></i> 
                        <span id="chartTitle">Estadísticas Mensuales de Personal</span>
                    </div>
                    <div class="btn-group" role="group" aria-label="Basic radio toggle button group">
                        <input type="radio" class="btn-check" name="chart_view" id="view_monthly" autocomplete="off">
                        <label class="btn btn-outline-primary" for="view_monthly">Mensual</label>
                        <input type="radio" class="btn-check" name="chart_view" id="view_yearly" autocomplete="off" checked>
                        <label class="btn btn-outline-primary" for="view_yearly">Anual</label>
                    </div>
                </div>
                <div class="card-body p-4">
                    <canvas id="workersChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Adjust main-content margin when sidebar changes
    function adjustMainContentMargin() {
        var sidebar = document.getElementById('sidebar');
        var mainContent = document.getElementById('main-content');
        if (sidebar && mainContent) {
            if (sidebar.classList.contains('collapsed')) {
                mainContent.style.marginLeft = 'var(--sidebar-collapsed)';
            } else {
                mainContent.style.marginLeft = 'var(--sidebar-expanded)';
            }
        }
    }
    window.addEventListener('DOMContentLoaded', function() {
        adjustMainContentMargin();
        var sidebar = document.getElementById('sidebar');
        if (sidebar) {
            var observer = new MutationObserver(adjustMainContentMargin);
            observer.observe(sidebar, { attributes: true, attributeFilter: ['class'] });
        }

        // Theme: mantener preferencia y actualizar icono
        var themeToggleBtn = document.getElementById('themeToggleBtn');
        var themeToggleIcon = document.getElementById('themeToggleIcon');
        var htmlTag = document.documentElement;

        // Al cargar, lee la preferencia
        let theme = localStorage.getItem('theme') || 'light';
        htmlTag.setAttribute('data-theme', theme);
        themeToggleIcon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';

        themeToggleBtn.addEventListener('click', function() {
            let current = htmlTag.getAttribute('data-theme');
            let next = current === 'dark' ? 'light' : 'dark';
            htmlTag.setAttribute('data-theme', next);
            localStorage.setItem('theme', next);
            themeToggleIcon.className = next === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        });
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="js/scripts.js"></script>
</body>
</html>
<?php ob_end_flush(); ?>