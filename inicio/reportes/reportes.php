<?php
session_start();
require __DIR__ . '/conexion/conexion_db.php';

// ===== VERIFICACIÓN DE SESIÓN Y ROL SOLO ADMIN =====
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

// ==== REGISTRO DE LOG DE ACCESO A GENERADOR DE REPORTES SOLO AL ENTRAR (NO AJAX, NO GENERAR) ====
function registrarLog($con, $user_id, $event_type, $details) {
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
    $stmt = $con->prepare("INSERT INTO action_logs (user_id, event_type, details, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("issss", $user_id, $event_type, $details, $ip_address, $user_agent);
    $stmt->execute();
    $stmt->close();
}
$current_user_id = $_SESSION['usuario']['id'] ?? null;

if (!isset($_GET['filtro']) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    registrarLog($conexion, $current_user_id, 'acceso_generador_reportes', 'Acceso a generador de reportes de talento humano');
}

// Utiliza PDO para todas las consultas de selección (más seguro y flexible).
function getDepartamentos($pdo) {
    $stmt = $pdo->query("SELECT id_departamento, nombre FROM departamentos ORDER BY nombre");
    return $stmt->fetchAll();
}
function getCoordinaciones($pdo) {
    $stmt = $pdo->query("SELECT id_coordinacion, nombre FROM coordinaciones ORDER BY nombre");
    return $stmt->fetchAll();
}
function getCargos($pdo) {
    $stmt = $pdo->query("SELECT id_cargo, nombre FROM cargos ORDER BY nombre");
    return $stmt->fetchAll();
}
function getTiposPersonal($pdo) {
    $stmt = $pdo->query("SELECT id_tipo_personal, nombre FROM tipos_personal ORDER BY nombre");
    return $stmt->fetchAll();
}
function getEstados($pdo) {
    $stmt = $pdo->query("SELECT id_estado, nombre FROM estados ORDER BY nombre");
    return $stmt->fetchAll();
}
function getMunicipios($pdo, $estado_id = null) {
    if ($estado_id) {
        $stmt = $pdo->prepare("SELECT id_municipio, nombre FROM municipios WHERE id_estado = ? ORDER BY nombre");
        $stmt->execute([$estado_id]);
        return $stmt->fetchAll();
    } else {
        $stmt = $pdo->query("SELECT id_municipio, nombre FROM municipios ORDER BY nombre");
        return $stmt->fetchAll();
    }
}
function getParroquias($pdo, $municipio_id = null) {
    if ($municipio_id) {
        $stmt = $pdo->prepare("SELECT id_parroquia, nombre FROM parroquias WHERE id_municipio = ? ORDER BY nombre");
        $stmt->execute([$municipio_id]);
        return $stmt->fetchAll();
    } else {
        $stmt = $pdo->query("SELECT id_parroquia, nombre FROM parroquias ORDER BY nombre");
        return $stmt->fetchAll();
    }
}

if (isset($_GET['filtro'])) {
    header('Content-Type: application/json; charset=utf-8');
    switch ($_GET['filtro']) {
        case 'departamentos':
            echo json_encode(getDepartamentos($pdo));
            break;
        case 'coordinaciones':
            echo json_encode(getCoordinaciones($pdo));
            break;
        case 'cargos':
            echo json_encode(getCargos($pdo));
            break;
        case 'tipos_personal':
            echo json_encode(getTiposPersonal($pdo));
            break;
        case 'estados':
            echo json_encode(getEstados($pdo));
            break;
        case 'municipios':
            $estado_id = $_GET['estado_id'] ?? null;
            echo json_encode(getMunicipios($pdo, $estado_id));
            break;
        case 'parroquias':
            $municipio_id = $_GET['municipio_id'] ?? null;
            echo json_encode(getParroquias($pdo, $municipio_id));
            break;
        default:
            echo json_encode([]);
    }
    exit;
}
require $_SERVER['DOCUMENT_ROOT']."/proyecto/inicio/sidebar.php";
?>
<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
    <meta charset="UTF-8">
    <title>Generador de Reportes de Talento Humano</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@500;700;900&display=swap" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(120deg,#eaf0fc 0%,#f9f9ff 100%) fixed;
            font-family: 'Inter', Arial, sans-serif;
        }
        .report-panel {
            background: rgba(255,255,255,0.93);
            border-radius: 18px;
            box-shadow: 0 4px 32px rgba(44,62,80,0.11), 0 1.5px 6px rgba(100,100,160,0.09);
            padding: 2.5rem 2.5rem 2rem 2.5rem;
            margin-top: 3rem;
            transition: box-shadow 0.22s, transform 0.22s;
            position: relative;
            overflow: hidden;
        }
        .report-panel:hover {
            box-shadow: 0 10px 40px rgba(44,62,80,0.16), 0 3px 16px rgba(100,100,160,0.13);
            transform: translateY(-3px) scale(1.01);
        }
        .report-title {
            color: #4238b5;
            font-weight: 900;
            letter-spacing: 0.5px;
            font-size: 2.2rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.7rem;
        }
        .report-title i {
            color: #4f6cf7;
            font-size: 1.5em;
            filter: drop-shadow(0 1px 3px #4f6cf736);
        }
        label.form-label {
            font-weight: 700;
            color: #3d3d3d;
            letter-spacing: 0.2px;
        }
        .form-select, .form-control {
            border-radius: 0.85rem;
            font-size: 1.03rem;
            border: 1.7px solid #e0e7ef;
            box-shadow: none;
            transition: border 0.22s;
            background: #fff;
            color: #232323;
        }
        .form-select:focus, .form-control:focus {
            border: 1.7px solid #5f8eff;
            box-shadow: 0 0 0 0.12rem #7f8fff33;
        }
        .form-select:disabled, .form-control:disabled {
            background: #ebeff2;
            opacity: 0.95;
        }
        .divider {
            border-top: 2px solid #e5e8ea;
            margin: 2.1rem 0 1.5rem 0;
        }
        #btnGenerarReporte {
            background: linear-gradient(90deg,#4238b5 0%,#4f6cf7 100%);
            border: none;
            border-radius: 1.1rem;
            padding: 0.7em 2em;
            font-size: 1.08rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 16px 0 rgba(80,100,200,0.12);
            transition: background 0.19s, box-shadow 0.19s, transform 0.19s;
            color: #fff;
        }
        #btnGenerarReporte:hover, #btnGenerarReporte:focus {
            background: linear-gradient(90deg,#4f6cf7 0%,#4238b5 100%);
            box-shadow: 0 8px 32px 0 rgba(80,100,200,0.19);
            transform: scale(1.018);
            color: #fff;
        }
        .report-tips {
            background: #f4f7fe;
            border-left: 5px solid #4f6cf7;
            border-radius: 0 0 13px 13px;
            box-shadow: 0 1px 6px rgba(80,100,200,0.10);
            padding: 1.2em 1.5em 1.2em 1.3em;
            margin-top: 1.8em;
            font-size: 1.04rem;
            color: #314a7b;
            animation: fadeInTips 0.6s;
        }
        .report-tips strong {
            color: #4238b5;
        }
        .report-tips ul {
            margin-bottom: 0;
            padding-left: 1.25em;
        }
        .report-tips i {
            color: #4f6cf7;
            margin-right: 0.4em;
        }
        @keyframes fadeInTips {
            0% { opacity: 0; transform: translateY(8px);}
            100% { opacity: 1; transform: none;}
        }
        @media (max-width: 600px) {
            .report-panel {padding: 1.3rem 0.7rem;}
            .report-title {font-size: 1.3rem;}
            .report-tips {font-size: 0.97rem;}
        }
        /* MODO OSCURO ... igual que antes ... */
        [data-theme="dark"] body {
            background: linear-gradient(120deg,#181924 0%,#23242a 100%) fixed;
            color: #e2e7ef;
        }
        [data-theme="dark"] .report-panel {
            background: rgba(35,36,42,0.98);
            box-shadow: 0 4px 28px rgba(80,100,200,0.17), 0 1.5px 7px rgba(60,60,120,0.19);
        }
        [data-theme="dark"] .report-title,
        [data-theme="dark"] .report-title i {
            color: #ff94e8 !important;
            filter: none;
        }
        [data-theme="dark"] label.form-label {
            color: #bab8fa;
        }
        [data-theme="dark"] .form-select,
        [data-theme="dark"] .form-control {
            background: #181b20 !important;
            color: #e2e7ef !important;
            border-color: #393b3f !important;
        }
        [data-theme="dark"] .form-select:focus, [data-theme="dark"] .form-control:focus {
            border-color: #8c7be7 !important;
            box-shadow: 0 0 0 0.12rem #5f8eff33;
        }
        [data-theme="dark"] .form-select:disabled, [data-theme="dark"] .form-control:disabled {
            background: #23242a;
            color: #bab8fa;
        }
        [data-theme="dark"] #btnGenerarReporte,
        [data-theme="dark"] .btn-primary {
            background: linear-gradient(90deg,#8c7be7 0%,#ff94e8 100%) !important;
            color: #fff !important;
            border: none;
        }
        [data-theme="dark"] #btnGenerarReporte:hover, [data-theme="dark"] #btnGenerarReporte:focus {
            background: linear-gradient(90deg,#ff94e8 0%,#8c7be7 100%) !important;
            color: #fff !important;
        }
        [data-theme="dark"] .divider {
            border-top: 2px solid #23242a;
        }
        [data-theme="dark"] .report-tips {
            background: #23242a;
            color: #bab8fa;
            border-left: 5px solid #8c7be7;
        }
        [data-theme="dark"] .report-tips strong,
        [data-theme="dark"] .report-tips i {
            color: #ff94e8;
        }
        [data-theme="light"] .form-select,
        [data-theme="light"] .form-control {
            border-color: #e0e7ef !important;
        }
        [data-theme="dark"] .form-select,
        [data-theme="dark"] .form-control {
            border-color: #393b3f !important;
        }
    </style>
</head>
<body>
<!-- BOTÓN MODO OSCURO/MODO CLARO -->
<button style="position:fixed;top:18px;right:18px;z-index:1101;background:#fff;border:1px solid #b5b8c0;color:#232323;border-radius:50%;width:44px;height:44px;display:flex;align-items:center;justify-content:center;cursor:pointer;box-shadow:0 2px 10px #2222;transition:background 0.2s,color 0.2s;" id="themeToggleBtn" title="Cambiar modo" aria-label="Cambiar modo claro/oscuro">
    <i id="themeToggleIcon" class="fas fa-moon"></i>
</button>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9 col-md-11">
                <div class="report-panel shadow-sm">
                    <h2 class="report-title mb-4">
                        <i class="fas fa-chart-bar"></i>
                        Generador de Reportes
                    </h2>
                    <!-- Formulario único PDF/CSV -->
                    <form id="reporteForm" action="generar_reporte.php" method="POST" target="_blank" autocomplete="off">
                        <div class="mb-3">
                            <label for="tipoReporte" class="form-label">Tipo de Reporte</label>
                            <select name="tipoReporte" id="tipoReporte" class="form-select" required>
                                <option value="" selected disabled>Seleccione tipo de reporte</option>
                                <option value="general">Listado General de Empleados</option>
                                <option value="por_estado_personal">Por Estado</option>
                                <option value="por_municipio_personal">Por Municipio</option>
                                <option value="por_parroquia_personal">Por Parroquia</option>
                                <!-- el resto igual que antes -->
                                <option value="por_departamento">Por Departamento</option>
                                <option value="por_coordinacion">Por Coordinación</option>
                                <option value="por_cargo">Por Cargo</option>
                                <option value="por_tipo_personal">Por Tipo de Personal</option>
                                <option value="por_estado">Por Estado Laboral</option>
                                <option value="vacaciones">Empleados en Vacaciones</option>
                                <option value="reposo">Empleados en Reposo</option>
                                <option value="carga_familiar">Empleados con Carga Familiar</option>
                                <option value="auditoria">Auditoría (Logs de acciones)</option>
                                <option value="toda_bd_csv">Exportar toda la base de datos (CSV)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="tipoExportacion" class="form-label">Formato de Exportación</label>
                            <select name="tipoExportacion" id="tipoExportacion" class="form-select" required>
                                <option value="pdf" selected>PDF</option>
                                <option value="csv">CSV</option>
                            </select>
                        </div>
                        <div id="filtrosDinamicos"></div>
                        <div class="divider"></div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary btn-lg" id="btnGenerarReporte">
                                <i class="fas fa-file-export me-2"></i>Generar Reporte
                            </button>
                        </div>
                    </form>
                    <div class="report-tips mt-4">
                        <strong><i class="fas fa-info-circle"></i>Recomendaciones:</strong>
                        <ul>
                            <li>Seleccione primero el <b>tipo de reporte</b> para mostrar los filtros disponibles.</li>
                            <li>Los filtros adicionales (departamento, cargo, fechas, etc.) aparecerán según su selección.</li>
                            <li>El reporte se generará en el formato elegido y se abrirá en una nueva pestaña.</li>
                            <li>Si desea un periodo específico, utilice los campos de fecha cuando estén disponibles.</li>
                            <li>Los reportes pueden demorar algunos segundos si hay muchos registros.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Font Awesome & Bootstrap JS -->
    <script src="https://kit.fontawesome.com/8c6e3f1aeb.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Modo oscuro/claro
    document.addEventListener('DOMContentLoaded', function () {
        var themeToggleBtn = document.getElementById('themeToggleBtn');
        var themeToggleIcon = document.getElementById('themeToggleIcon');
        var htmlTag = document.documentElement;
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

    // ----- Filtros dinámicos -----
    async function cargarOpciones(filtro, selectElem, valueKey='id', labelKey='nombre', extraParams = {}) {
        let url = 'reportes.php?filtro=' + filtro;
        if (extraParams.estado_id) url += '&estado_id=' + extraParams.estado_id;
        if (extraParams.municipio_id) url += '&municipio_id=' + extraParams.municipio_id;
        const res = await fetch(url);
        const data = await res.json();
        let html = `<option value="" selected disabled>Seleccione...</option>`;
        data.forEach(obj => {
            html += `<option value="${obj[valueKey]}">${obj[labelKey]}</option>`;
        });
        selectElem.innerHTML = html;
    }

    function renderFiltros(tipo) {
        const cont = document.getElementById('filtrosDinamicos');
        cont.innerHTML = '';

        if (tipo === 'por_estado_personal') {
            cont.innerHTML += `
                <div class="mb-3">
                    <label class="form-label">Estado</label>
                    <select class="form-select" id="estado_personal" name="estado_personal"></select>
                </div>
            `;
            setTimeout(() => cargarOpciones('estados', document.getElementById('estado_personal'), 'id_estado', 'nombre'), 100);
        }
        if (tipo === 'por_municipio_personal') {
            cont.innerHTML += `
                <div class="mb-3">
                    <label class="form-label">Estado</label>
                    <select class="form-select" id="estado_personal_mun" name="estado_personal_mun"></select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Municipio</label>
                    <select class="form-select" id="municipio_personal" name="municipio_personal"></select>
                </div>
            `;
            setTimeout(() => cargarOpciones('estados', document.getElementById('estado_personal_mun'), 'id_estado', 'nombre'), 100);
            document.getElementById('estado_personal_mun').addEventListener('change', function() {
                cargarOpciones('municipios', document.getElementById('municipio_personal'), 'id_municipio', 'nombre', {estado_id: this.value});
            });
        }
        if (tipo === 'por_parroquia_personal') {
            cont.innerHTML += `
                <div class="mb-3">
                    <label class="form-label">Estado</label>
                    <select class="form-select" id="estado_personal_parr" name="estado_personal_parr"></select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Municipio</label>
                    <select class="form-select" id="municipio_personal_parr" name="municipio_personal_parr"></select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Parroquia</label>
                    <select class="form-select" id="parroquia_personal" name="parroquia_personal"></select>
                </div>
            `;
            setTimeout(() => cargarOpciones('estados', document.getElementById('estado_personal_parr'), 'id_estado', 'nombre'), 100);
            document.getElementById('estado_personal_parr').addEventListener('change', function() {
                cargarOpciones('municipios', document.getElementById('municipio_personal_parr'), 'id_municipio', 'nombre', {estado_id: this.value});
            });
            document.getElementById('municipio_personal_parr').addEventListener('change', function() {
                cargarOpciones('parroquias', document.getElementById('parroquia_personal'), 'id_parroquia', 'nombre', {municipio_id: this.value});
            });
        }
        // ... el resto igual que antes ...
        if (tipo === 'por_departamento') {
            cont.innerHTML += `
                <div class="mb-3">
                    <label class="form-label">Departamento</label>
                    <select class="form-select" id="departamento" name="departamento"></select>
                </div>
            `;
            setTimeout(() => cargarOpciones('departamentos', document.getElementById('departamento'), 'id_departamento', 'nombre'), 100);
        }
        if (tipo === 'por_coordinacion') {
            cont.innerHTML += `
                <div class="mb-3">
                    <label class="form-label">Coordinación</label>
                    <select class="form-select" id="coordinacion" name="coordinacion"></select>
                </div>
            `;
            setTimeout(() => cargarOpciones('coordinaciones', document.getElementById('coordinacion'), 'id_coordinacion', 'nombre'), 100);
        }
        if (tipo === 'por_cargo') {
            cont.innerHTML += `
                <div class="mb-3">
                    <label class="form-label">Cargo</label>
                    <select class="form-select" id="cargo" name="cargo"></select>
                </div>
            `;
            setTimeout(() => cargarOpciones('cargos', document.getElementById('cargo'), 'id_cargo', 'nombre'), 100);
        }
        if (tipo === 'por_tipo_personal') {
            cont.innerHTML += `
                <div class="mb-3">
                    <label class="form-label">Tipo de Personal</label>
                    <select class="form-select" id="tipo_personal" name="tipo_personal"></select>
                </div>
            `;
            setTimeout(() => cargarOpciones('tipos_personal', document.getElementById('tipo_personal'), 'id_tipo_personal', 'nombre'), 100);
        }
        if (tipo === 'por_estado') {
            cont.innerHTML += `
                <div class="mb-3">
                    <label class="form-label">Estado Laboral</label>
                    <select class="form-select" id="estado" name="estado">
                        <option value="" selected disabled>Seleccione...</option>
                        <option value="activo">Activo</option>
                        <option value="vacaciones">Vacaciones</option>
                        <option value="inactivo">Inactivo</option>
                        <option value="reposo">Reposo</option>
                    </select>
                </div>
            `;
        }
        if (tipo === 'vacaciones' || tipo === 'reposo' || tipo === 'auditoria') {
            cont.innerHTML += `
                <div class="mb-3 row">
                    <div class="col-md-6">
                        <label class="form-label">Fecha Inicio</label>
                        <input type="date" class="form-control" name="fecha_inicio">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Fecha Fin</label>
                        <input type="date" class="form-control" name="fecha_fin">
                    </div>
                </div>
            `;
        }
    }

    document.getElementById('tipoReporte').addEventListener('change', function() {
        var formato = document.getElementById('tipoExportacion');
        var filtros = document.getElementById('filtrosDinamicos');
        if (this.value === 'toda_bd_csv') {
            formato.value = 'csv';
            formato.setAttribute('disabled', 'disabled');
            filtros.innerHTML = '';
        } else {
            formato.removeAttribute('disabled');
            renderFiltros(this.value);
        }
    });

    document.getElementById('reporteForm').addEventListener('submit', function(e) {
        var tipoReporte = document.getElementById('tipoReporte').value;
        var tipoExportacion = document.getElementById('tipoExportacion').value;
        if (!tipoReporte) {
            e.preventDefault();
            alert('Seleccione el tipo de reporte.');
            return;
        }
        if (tipoReporte === 'toda_bd_csv') {
            this.action = 'generar_csv.php';
        } else if (tipoExportacion === 'csv') {
            this.action = 'generar_csv.php';
        } else {
            this.action = 'generar_reporte.php';
        }
    });
    </script>
</body>
</html>