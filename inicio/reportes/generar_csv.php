<?php
require __DIR__ . '/conexion/conexion_db.php';

// --- Exportar toda la base de datos ---
if ($_POST['tipoReporte'] === 'toda_bd_csv') {
    if (ob_get_level()) ob_end_clean(); // Limpia buffer de salida
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="toda_bd.csv"');
    $output = fopen('php://output', 'w');
    // BOM UTF-8 para Excel
    fwrite($output, "\xEF\xBB\xBF");

    // Obtén todas las tablas
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $table) {
        // Nombre de la tabla como título
        fputcsv($output, ["------ Tabla: $table ------"], ';');
        // Encabezados
        $columns = $pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        $headers = array_map(function($col){ return $col['Field']; }, $columns);
        fputcsv($output, $headers, ';');

        // Datos
        $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $fila = [];
            foreach ($headers as $col) {
                $valor = $row[$col];
                $valor = mb_convert_encoding($valor, 'UTF-8', 'auto');
                if (preg_match('/^\d{4}-\d{2}-\d{2}/', $valor)) {
                    $valor = date('d/m/Y', strtotime($valor));
                }
                $fila[] = $valor;
            }
            fputcsv($output, $fila, ';');
        }
        // Línea vacía entre tablas
        fputcsv($output, [''], ';');
    }
    fclose($output);
    exit;
}

// --------- Reportes CSV normales ----------
$tipo = $_POST['tipoReporte'] ?? '';

// Construcción de consulta y encabezados
$sql = "";
$params = [];
$headers = [];
$campos = [];
switch ($tipo) {
    case 'general':
        $sql = "SELECT dp.nombres, dp.apellidos, dp.cedula_identidad, dp.correo_electronico, dp.telefono_contacto, dl.estado, d.nombre AS departamento, c.nombre AS cargo
                FROM datos_personales dp
                LEFT JOIN datos_laborales dl ON dp.id_pers = dl.id_pers
                LEFT JOIN departamentos d ON dl.id_departamento = d.id_departamento
                LEFT JOIN cargos c ON dl.id_cargo = c.id_cargo";
        $headers = ['Nombres', 'Apellidos', 'Cédula', 'Correo', 'Teléfono', 'Estado', 'Departamento', 'Cargo'];
        $campos = ['nombres', 'apellidos', 'cedula_identidad', 'correo_electronico', 'telefono_contacto', 'estado', 'departamento', 'cargo'];
        break;

    case 'por_estado_personal':
        $estado_id = $_POST['estado_personal'] ?? '';
        $sql = "SELECT nombres, apellidos, cedula_identidad, correo_electronico, telefono_contacto,
                    (SELECT nombre FROM estados WHERE id_estado = datos_personales.id_estado) AS estado,
                    direccion
                FROM datos_personales
                WHERE id_estado = ?";
        $params[] = $estado_id;
        $headers = ['Nombres', 'Apellidos', 'Cédula', 'Correo', 'Teléfono', 'Estado', 'Dirección'];
        $campos = ['nombres', 'apellidos', 'cedula_identidad', 'correo_electronico', 'telefono_contacto', 'estado', 'direccion'];
        break;

    case 'por_municipio_personal':
        $municipio_id = $_POST['municipio_personal'] ?? '';
        $sql = "SELECT nombres, apellidos, cedula_identidad, correo_electronico, telefono_contacto,
                    (SELECT nombre FROM estados WHERE id_estado = datos_personales.id_estado) AS estado,
                    (SELECT nombre FROM municipios WHERE id_municipio = datos_personales.id_municipio) AS municipio,
                    direccion
                FROM datos_personales
                WHERE id_municipio = ?";
        $params[] = $municipio_id;
        $headers = ['Nombres', 'Apellidos', 'Cédula', 'Correo', 'Teléfono', 'Estado', 'Municipio', 'Dirección'];
        $campos = ['nombres', 'apellidos', 'cedula_identidad', 'correo_electronico', 'telefono_contacto', 'estado', 'municipio', 'direccion'];
        break;

    case 'por_parroquia_personal':
        $parroquia_id = $_POST['parroquia_personal'] ?? '';
        $sql = "SELECT nombres, apellidos, cedula_identidad, correo_electronico, telefono_contacto,
                    (SELECT nombre FROM estados WHERE id_estado = datos_personales.id_estado) AS estado,
                    (SELECT nombre FROM municipios WHERE id_municipio = datos_personales.id_municipio) AS municipio,
                    (SELECT nombre FROM parroquias WHERE id_parroquia = datos_personales.id_parroquia) AS parroquia,
                    direccion
                FROM datos_personales
                WHERE id_parroquia = ?";
        $params[] = $parroquia_id;
        $headers = ['Nombres', 'Apellidos', 'Cédula', 'Correo', 'Teléfono', 'Estado', 'Municipio', 'Parroquia', 'Dirección'];
        $campos = ['nombres', 'apellidos', 'cedula_identidad', 'correo_electronico', 'telefono_contacto', 'estado', 'municipio', 'parroquia', 'direccion'];
        break;

    case 'por_departamento':
        $departamento = $_POST['departamento'] ?? '';
        $sql = "SELECT dp.nombres, dp.apellidos, dp.cedula_identidad, dp.correo_electronico, dp.telefono_contacto, dl.estado, d.nombre AS departamento, c.nombre AS cargo
                FROM datos_personales dp
                LEFT JOIN datos_laborales dl ON dp.id_pers = dl.id_pers
                LEFT JOIN departamentos d ON dl.id_departamento = d.id_departamento
                LEFT JOIN cargos c ON dl.id_cargo = c.id_cargo
                WHERE d.id_departamento = ?";
        $params[] = $departamento;
        $headers = ['Nombres', 'Apellidos', 'Cédula', 'Correo', 'Teléfono', 'Estado', 'Departamento', 'Cargo'];
        $campos = ['nombres', 'apellidos', 'cedula_identidad', 'correo_electronico', 'telefono_contacto', 'estado', 'departamento', 'cargo'];
        break;

    case 'por_coordinacion':
        $coordinacion = $_POST['coordinacion'] ?? '';
        $sql = "SELECT dp.nombres, dp.apellidos, dp.cedula_identidad, dp.correo_electronico, dp.telefono_contacto, dl.estado, co.nombre AS coordinacion, c.nombre AS cargo
                FROM datos_personales dp
                LEFT JOIN datos_laborales dl ON dp.id_pers = dl.id_pers
                LEFT JOIN coordinaciones co ON dl.id_coordinacion = co.id_coordinacion
                LEFT JOIN cargos c ON dl.id_cargo = c.id_cargo
                WHERE co.id_coordinacion = ?";
        $params[] = $coordinacion;
        $headers = ['Nombres', 'Apellidos', 'Cédula', 'Correo', 'Teléfono', 'Estado', 'Coordinación', 'Cargo'];
        $campos = ['nombres', 'apellidos', 'cedula_identidad', 'correo_electronico', 'telefono_contacto', 'estado', 'coordinacion', 'cargo'];
        break;

    case 'por_cargo':
        $cargo = $_POST['cargo'] ?? '';
        $sql = "SELECT dp.nombres, dp.apellidos, dp.cedula_identidad, dp.correo_electronico, dp.telefono_contacto, dl.estado, c.nombre AS cargo
                FROM datos_personales dp
                LEFT JOIN datos_laborales dl ON dp.id_pers = dl.id_pers
                LEFT JOIN cargos c ON dl.id_cargo = c.id_cargo
                WHERE c.id_cargo = ?";
        $params[] = $cargo;
        $headers = ['Nombres', 'Apellidos', 'Cédula', 'Correo', 'Teléfono', 'Estado', 'Cargo'];
        $campos = ['nombres', 'apellidos', 'cedula_identidad', 'correo_electronico', 'telefono_contacto', 'estado', 'cargo'];
        break;

    case 'por_tipo_personal':
        $tipo_personal = $_POST['tipo_personal'] ?? '';
        $sql = "SELECT dp.nombres, dp.apellidos, dp.cedula_identidad, dp.correo_electronico, dp.telefono_contacto, dl.estado, tp.nombre AS tipo_personal, c.nombre AS cargo
                FROM datos_personales dp
                LEFT JOIN datos_laborales dl ON dp.id_pers = dl.id_pers
                LEFT JOIN tipos_personal tp ON dl.id_tipo_personal = tp.id_tipo_personal
                LEFT JOIN cargos c ON dl.id_cargo = c.id_cargo
                WHERE tp.id_tipo_personal = ?";
        $params[] = $tipo_personal;
        $headers = ['Nombres', 'Apellidos', 'Cédula', 'Correo', 'Teléfono', 'Estado', 'Tipo Personal', 'Cargo'];
        $campos = ['nombres', 'apellidos', 'cedula_identidad', 'correo_electronico', 'telefono_contacto', 'estado', 'tipo_personal', 'cargo'];
        break;

    case 'por_estado':
        $estado = $_POST['estado'] ?? '';
        $sql = "SELECT dp.nombres, dp.apellidos, dp.cedula_identidad, dp.correo_electronico, dp.telefono_contacto, dl.estado, c.nombre AS cargo
                FROM datos_personales dp
                LEFT JOIN datos_laborales dl ON dp.id_pers = dl.id_pers
                LEFT JOIN cargos c ON dl.id_cargo = c.id_cargo
                WHERE dl.estado = ?";
        $params[] = $estado;
        $headers = ['Nombres', 'Apellidos', 'Cédula', 'Correo', 'Teléfono', 'Estado', 'Cargo'];
        $campos = ['nombres', 'apellidos', 'cedula_identidad', 'correo_electronico', 'telefono_contacto', 'estado', 'cargo'];
        break;

    case 'vacaciones':
        $fecha_inicio = $_POST['fecha_inicio'] ?? null;
        $fecha_fin = $_POST['fecha_fin'] ?? null;
        $sql = "SELECT dp.nombres, dp.apellidos, dp.cedula_identidad, pv.fecha_inicio_periodo, pv.fecha_fin_periodo, pv.dias_asignados, pv.dias_usados, pv.estado
                FROM datos_personales dp
                INNER JOIN periodos_vacaciones pv ON dp.id_pers = pv.id_pers
                WHERE pv.estado = 'activo'";
        if ($fecha_inicio && $fecha_fin) {
            $sql .= " AND pv.fecha_inicio_periodo >= ? AND pv.fecha_fin_periodo <= ?";
            $params[] = $fecha_inicio;
            $params[] = $fecha_fin;
        }
        $headers = ['Nombres', 'Apellidos', 'Cédula', 'Inicio', 'Fin', 'Asignados', 'Usados', 'Estado'];
        $campos = ['nombres', 'apellidos', 'cedula_identidad', 'fecha_inicio_periodo', 'fecha_fin_periodo', 'dias_asignados', 'dias_usados', 'estado'];
        break;

    case 'reposo':
        $fecha_inicio = $_POST['fecha_inicio'] ?? null;
        $fecha_fin = $_POST['fecha_fin'] ?? null;
        $sql = "SELECT dp.nombres, dp.apellidos, dp.cedula_identidad, r.motivo_reposo, r.dias_otorgados, r.fecha_inicio, r.fecha_fin, r.estado
                FROM datos_personales dp
                INNER JOIN reposos r ON dp.id_pers = r.id_pers
                WHERE r.estado = 'activo'";
        if ($fecha_inicio && $fecha_fin) {
            $sql .= " AND r.fecha_inicio >= ? AND r.fecha_fin <= ?";
            $params[] = $fecha_inicio;
            $params[] = $fecha_fin;
        }
        $headers = ['Nombres', 'Apellidos', 'Cédula', 'Motivo', 'Días', 'Inicio', 'Fin', 'Estado'];
        $campos = ['nombres', 'apellidos', 'cedula_identidad', 'motivo_reposo', 'dias_otorgados', 'fecha_inicio', 'fecha_fin', 'estado'];
        break;

    case 'carga_familiar':
        $sql = "SELECT dp.nombres, dp.apellidos, dp.cedula_identidad, cf.parentesco, cf.nombres_familiar, cf.apellidos_familiar, cf.cedula_familiar, cf.genero_familiar, cf.tiene_discapacidad
                FROM datos_personales dp
                INNER JOIN datos_socioeconomicos ds ON dp.id_pers = ds.id_pers
                INNER JOIN carga_familiar cf ON ds.id_socioeconomico = cf.id_socioeconomico";
        $headers = ['Empleado', 'Apellido', 'Cédula', 'Parentesco', 'Nombre Familiar', 'Apellido Familiar', 'Cédula Familiar', 'Género', 'Discapacidad'];
        $campos = ['nombres', 'apellidos', 'cedula_identidad', 'parentesco', 'nombres_familiar', 'apellidos_familiar', 'cedula_familiar', 'genero_familiar', 'tiene_discapacidad'];
        break;

    case 'auditoria':
        $fecha_inicio = $_POST['fecha_inicio'] ?? null;
        $fecha_fin = $_POST['fecha_fin'] ?? null;
        $sql = "SELECT a.id, u.nombre AS usuario, a.event_type, a.details, a.ip_address, a.user_agent, a.created_at
                FROM action_logs a
                LEFT JOIN usuarios u ON a.user_id = u.id
                WHERE 1=1";
        if ($fecha_inicio && $fecha_fin) {
            $sql .= " AND a.created_at >= ? AND a.created_at <= ?";
            $params[] = $fecha_inicio . " 00:00:00";
            $params[] = $fecha_fin . " 23:59:59";
        }
        $sql .= " ORDER BY a.created_at DESC";
        $headers = ['ID', 'Usuario', 'Evento', 'Detalles', 'IP', 'User-Agent', 'Fecha'];
        $campos = ['id', 'usuario', 'event_type', 'details', 'ip_address', 'user_agent', 'created_at'];
        break;

    default:
        die('Tipo de reporte no soportado para CSV.');
}

// --- Ejecutar la consulta ---
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- CSV EXPORTACIÓN MEJORADO ---
if (ob_get_level()) ob_end_clean(); // Limpia buffer de salida
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="reporte.csv"');
$output = fopen('php://output', 'w');
fwrite($output, "\xEF\xBB\xBF"); // BOM UTF-8

fputcsv($output, $headers, ';'); // punto y coma para Excel español

foreach ($registros as $row) {
    $fila = [];
    foreach ($campos as $k) {
        $col = $row[$k] ?? '';
        $col = mb_convert_encoding($col, 'UTF-8', 'auto');
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $col)) {
            $col = date('d/m/Y', strtotime($col));
        }
        $fila[] = $col;
    }
    fputcsv($output, $fila, ';');
}
fclose($output);
exit;
?>