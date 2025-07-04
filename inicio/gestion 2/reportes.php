<?php
require 'conexion/conexion_db.php';

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

// Para AJAX: Devuelve datos según el filtro solicitado
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
    <style>
        .report-panel {
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 4px 24px rgba(44,62,80,0.07);
            padding: 2rem 2.5rem;
            margin-top: 3rem;
        }
        .report-title {
            color: #2c3e50;
        }
        .form-label {
            font-weight: 500;
        }
        .form-select:disabled, .form-control:disabled {
            background: #ebeff2;
        }
        .divider {
            border-top: 2px solid #e5e8ea;
            margin: 2rem 0 1.5rem 0;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div class="report-panel">
                    <h2 class="report-title mb-4"><i class="fas fa-file-alt me-2"></i>Generador de Reportes</h2>
                    <form id="reporteForm" action="generar_reporte.php" method="POST" target="_blank">   
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
                        <div id="filtrosDinamicos">
                            <!-- Aquí se insertarán los filtros según el tipo de reporte -->
                        </div>

                        <div class="divider"></div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary btn-lg" id="btnGenerarReporte">
                                <i class="fas fa-file-pdf me-2"></i>Generar PDF
                            </button>
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