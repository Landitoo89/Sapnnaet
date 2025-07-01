<?php
session_start();
require 'conexion/conexion_db.php';

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
        default:
            echo json_encode([]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
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
        /* Animación de filtros */
        #filtrosDinamicos > div, #filtrosDinamicos > .row {
            animation: slideInFade 0.38s cubic-bezier(.6,1.6,.5,1) both;
        }
        @keyframes slideInFade {
            0% { opacity: 0; transform: translateY(16px);}
            100% { opacity: 1; transform: none;}
        }
        /* Recomendaciones */
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
        /* Responsive */
        @media (max-width: 600px) {
            .report-panel {padding: 1.3rem 0.7rem;}
            .report-title {font-size: 1.3rem;}
            .report-tips {font-size: 0.97rem;}
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9 col-md-11">
                <div class="report-panel shadow-sm">
                    <h2 class="report-title mb-4">
                        <i class="fas fa-chart-bar"></i>
                        Generador de Reportes
                    </h2>
                    <form id="reporteForm" action="generar_reporte.php" method="POST" target="_blank" autocomplete="off">   
                        <!-- Tipo de reporte -->
                        <div class="mb-3">
                            <label for="tipoReporte" class="form-label">Tipo de Reporte</label>
                            <select name="tipoReporte" id="tipoReporte" class="form-select" required>
                                <option value="" selected disabled>Seleccione tipo de reporte</option>
                                <option value="general">Listado General de Empleados</option>
                                <option value="por_departamento">Por Departamento</option>
                                <option value="por_coordinacion">Por Coordinación</option>
                                <option value="por_cargo">Por Cargo</option>
                                <option value="por_tipo_personal">Por Tipo de Personal</option>
                                <option value="por_estado">Por Estado Laboral</option>
                                <option value="vacaciones">Empleados en Vacaciones</option>
                                <option value="reposo">Empleados en Reposo</option>
                                <option value="carga_familiar">Empleados con Carga Familiar</option>
                                <option value="auditoria">Auditoría (Logs de acciones)</option>
                            </select>
                        </div>

                        <!-- Filtros dinámicos -->
                        <div id="filtrosDinamicos"></div>

                        <div class="divider"></div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary btn-lg" id="btnGenerarReporte">
                                <i class="fas fa-file-pdf me-2"></i>Generar PDF
                            </button>
                        </div>

                        <!-- Instrucciones y recomendaciones debajo del botón -->
                        <div class="report-tips mt-4">
                            <strong><i class="fas fa-info-circle"></i>Recomendaciones:</strong>
                            <ul>
                                <li>Seleccione primero el <b>tipo de reporte</b> para mostrar los filtros disponibles.</li>
                                <li>Los filtros adicionales (departamento, cargo, fechas, etc.) aparecerán según su selección.</li>
                                <li>El reporte se generará en formato PDF y se abrirá en una nueva pestaña.</li>
                                <li>Si desea un periodo específico, utilice los campos de fecha cuando estén disponibles.</li>
                                <li>Los reportes pueden demorar algunos segundos si hay muchos registros.</li>
                            </ul>
                        </div>
                    </form>
                </div>
                <!-- Aquí puedes mostrar una tabla previa si deseas previsualizar datos antes de exportar -->
            </div>
        </div>
    </div>

    <!-- Font Awesome & Bootstrap JS -->
    <script src="https://kit.fontawesome.com/8c6e3f1aeb.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Cargar opciones desde PHP vía AJAX
    async function cargarOpciones(filtro, selectId, valueKey='id', labelKey='nombre') {
        const res = await fetch('reportes.php?filtro=' + filtro);
        const data = await res.json();
        let html = `<option value="" selected disabled>Seleccione...</option>`;
        data.forEach(obj => {
            html += `<option value="${obj[valueKey]}">${obj[labelKey]}</option>`;
        });
        document.getElementById(selectId).innerHTML = html;
    }

    function renderFiltros(tipo) {
        const cont = document.getElementById('filtrosDinamicos');
        cont.innerHTML = '';

        if (tipo === 'por_departamento') {
            cont.innerHTML += `
                <div class="mb-3">
                    <label class="form-label">Departamento</label>
                    <select class="form-select" id="departamento" name="departamento"></select>
                </div>
            `;
            setTimeout(() => cargarOpciones('departamentos', 'departamento', 'id_departamento', 'nombre'), 100);
        }
        if (tipo === 'por_coordinacion') {
            cont.innerHTML += `
                <div class="mb-3">
                    <label class="form-label">Coordinación</label>
                    <select class="form-select" id="coordinacion" name="coordinacion"></select>
                </div>
            `;
            setTimeout(() => cargarOpciones('coordinaciones', 'coordinacion', 'id_coordinacion', 'nombre'), 100);
        }
        if (tipo === 'por_cargo') {
            cont.innerHTML += `
                <div class="mb-3">
                    <label class="form-label">Cargo</label>
                    <select class="form-select" id="cargo" name="cargo"></select>
                </div>
            `;
            setTimeout(() => cargarOpciones('cargos', 'cargo', 'id_cargo', 'nombre'), 100);
        }
        if (tipo === 'por_tipo_personal') {
            cont.innerHTML += `
                <div class="mb-3">
                    <label class="form-label">Tipo de Personal</label>
                    <select class="form-select" id="tipo_personal" name="tipo_personal"></select>
                </div>
            `;
            setTimeout(() => cargarOpciones('tipos_personal', 'tipo_personal', 'id_tipo_personal', 'nombre'), 100);
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
        // Filtros de fechas para vacaciones, reposo y auditoría
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
        renderFiltros(this.value);
    });

    document.getElementById('reporteForm').addEventListener('submit', function(e) {
        if (!document.getElementById('tipoReporte').value) {
            e.preventDefault();
            alert('Seleccione el tipo de reporte.');
        }
    });
    </script>
</body>
</html>