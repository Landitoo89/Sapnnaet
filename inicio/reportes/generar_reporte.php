<?php
require __DIR__ . '/conexion/conexion_db.php';
require_once('fpdf/fpdf.php');

// Ruta del membrete institucional (ajústala si es necesario)
$membrete_path = __DIR__ . '\\logogob.jpg';
$tipo = $_POST['tipoReporte'] ?? '';

// --- Construir la consulta y encabezados según el tipo de reporte ---
$sql = "";
$params = [];
$headers = [];
$widths = [];
$title = "";
$campos = [];
switch ($tipo) {
    case 'general':
        $title = "Listado General de Empleados";
        $sql = "SELECT dp.nombres, dp.apellidos, dp.cedula_identidad, dp.correo_electronico, dp.telefono_contacto, dl.estado, d.nombre AS departamento, c.nombre AS cargo
                FROM datos_personales dp
                LEFT JOIN datos_laborales dl ON dp.id_pers = dl.id_pers
                LEFT JOIN departamentos d ON dl.id_departamento = d.id_departamento
                LEFT JOIN cargos c ON dl.id_cargo = c.id_cargo";
        $headers = ['Nombres', 'Apellidos', 'Cédula', 'Correo', 'Teléfono', 'Estado', 'Departamento', 'Cargo'];
        $widths = [28, 28, 18, 38, 22, 17, 32, 32]; // total 215mm aprox
        $campos = ['nombres', 'apellidos', 'cedula_identidad', 'correo_electronico', 'telefono_contacto', 'estado', 'departamento', 'cargo'];
        break;
    case 'por_estado_personal':
        $title = "Reporte por Estado";
        $estado_id = $_POST['estado_personal'] ?? '';
        $sql = "SELECT nombres, apellidos, cedula_identidad, correo_electronico, telefono_contacto,
                    (SELECT nombre FROM estados WHERE id_estado = datos_personales.id_estado) AS estado,
                    direccion
                FROM datos_personales
                WHERE id_estado = ?";
        $params[] = $estado_id;
        $headers = ['Nombres', 'Apellidos', 'Cédula', 'Correo', 'Teléfono', 'Estado', 'Dirección'];
        $widths = [28, 28, 18, 38, 22, 22, 50]; // total 206mm
        $campos = ['nombres', 'apellidos', 'cedula_identidad', 'correo_electronico', 'telefono_contacto', 'estado', 'direccion'];
        break;
    case 'por_municipio_personal':
        $title = "Reporte por Municipio";
        $municipio_id = $_POST['municipio_personal'] ?? '';
        $sql = "SELECT nombres, apellidos, cedula_identidad, correo_electronico, telefono_contacto,
                    (SELECT nombre FROM estados WHERE id_estado = datos_personales.id_estado) AS estado,
                    (SELECT nombre FROM municipios WHERE id_municipio = datos_personales.id_municipio) AS municipio,
                    direccion
                FROM datos_personales
                WHERE id_municipio = ?";
        $params[] = $municipio_id;
        $headers = ['Nombres', 'Apellidos', 'Cédula', 'Correo', 'Teléfono', 'Estado', 'Municipio', 'Dirección'];
        $widths = [24, 25, 18, 34, 20, 17, 30, 42]; // total 210mm
        $campos = ['nombres', 'apellidos', 'cedula_identidad', 'correo_electronico', 'telefono_contacto', 'estado', 'municipio', 'direccion'];
        break;
    case 'por_parroquia_personal':
        $title = "Reporte por Parroquia";
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
        $widths = [21, 21, 17, 31, 18, 15, 23, 23, 41]; // total 210mm
        $campos = ['nombres', 'apellidos', 'cedula_identidad', 'correo_electronico', 'telefono_contacto', 'estado', 'municipio', 'parroquia', 'direccion'];
        break;
    case 'por_departamento':
        $title = "Empleados por Departamento";
        $departamento = $_POST['departamento'] ?? '';
        $sql = "SELECT dp.nombres, dp.apellidos, dp.cedula_identidad, dp.correo_electronico, dp.telefono_contacto, dl.estado, d.nombre AS departamento, c.nombre AS cargo
                FROM datos_personales dp
                LEFT JOIN datos_laborales dl ON dp.id_pers = dl.id_pers
                LEFT JOIN departamentos d ON dl.id_departamento = d.id_departamento
                LEFT JOIN cargos c ON dl.id_cargo = c.id_cargo
                WHERE d.id_departamento = ?";
        $params[] = $departamento;
        $headers = ['Nombres', 'Apellidos', 'Cédula', 'Correo', 'Teléfono', 'Estado', 'Departamento', 'Cargo'];
        $widths = [28, 28, 18, 38, 22, 17, 32, 32];
        $campos = ['nombres', 'apellidos', 'cedula_identidad', 'correo_electronico', 'telefono_contacto', 'estado', 'departamento', 'cargo'];
        break;
    case 'por_coordinacion':
        $title = "Empleados por Coordinación";
        $coordinacion = $_POST['coordinacion'] ?? '';
        $sql = "SELECT dp.nombres, dp.apellidos, dp.cedula_identidad, dp.correo_electronico, dp.telefono_contacto, dl.estado, co.nombre AS coordinacion, c.nombre AS cargo
                FROM datos_personales dp
                LEFT JOIN datos_laborales dl ON dp.id_pers = dl.id_pers
                LEFT JOIN coordinaciones co ON dl.id_coordinacion = co.id_coordinacion
                LEFT JOIN cargos c ON dl.id_cargo = c.id_cargo
                WHERE co.id_coordinacion = ?";
        $params[] = $coordinacion;
        $headers = ['Nombres', 'Apellidos', 'Cédula', 'Correo', 'Teléfono', 'Estado', 'Coordinación', 'Cargo'];
        $widths = [28, 28, 18, 38, 22, 17, 32, 32];
        $campos = ['nombres', 'apellidos', 'cedula_identidad', 'correo_electronico', 'telefono_contacto', 'estado', 'coordinacion', 'cargo'];
        break;
    case 'por_cargo':
        $title = "Empleados por Cargo";
        $cargo = $_POST['cargo'] ?? '';
        $sql = "SELECT dp.nombres, dp.apellidos, dp.cedula_identidad, dp.correo_electronico, dp.telefono_contacto, dl.estado, c.nombre AS cargo
                FROM datos_personales dp
                LEFT JOIN datos_laborales dl ON dp.id_pers = dl.id_pers
                LEFT JOIN cargos c ON dl.id_cargo = c.id_cargo
                WHERE c.id_cargo = ?";
        $params[] = $cargo;
        $headers = ['Nombres', 'Apellidos', 'Cédula', 'Correo', 'Teléfono', 'Estado', 'Cargo'];
        $widths = [28, 28, 18, 38, 22, 17, 32];
        $campos = ['nombres', 'apellidos', 'cedula_identidad', 'correo_electronico', 'telefono_contacto', 'estado', 'cargo'];
        break;
    case 'por_tipo_personal':
        $title = "Empleados por Tipo de Personal";
        $tipo_personal = $_POST['tipo_personal'] ?? '';
        $sql = "SELECT dp.nombres, dp.apellidos, dp.cedula_identidad, dp.correo_electronico, dp.telefono_contacto, dl.estado, tp.nombre AS tipo_personal, c.nombre AS cargo
                FROM datos_personales dp
                LEFT JOIN datos_laborales dl ON dp.id_pers = dl.id_pers
                LEFT JOIN tipos_personal tp ON dl.id_tipo_personal = tp.id_tipo_personal
                LEFT JOIN cargos c ON dl.id_cargo = c.id_cargo
                WHERE tp.id_tipo_personal = ?";
        $params[] = $tipo_personal;
        $headers = ['Nombres', 'Apellidos', 'Cédula', 'Correo', 'Teléfono', 'Estado', 'Tipo Personal', 'Cargo'];
        $widths = [28, 28, 18, 38, 22, 17, 32, 32];
        $campos = ['nombres', 'apellidos', 'cedula_identidad', 'correo_electronico', 'telefono_contacto', 'estado', 'tipo_personal', 'cargo'];
        break;
    case 'por_estado':
        $title = "Empleados por Estado Laboral";
        $estado = $_POST['estado'] ?? '';
        $sql = "SELECT dp.nombres, dp.apellidos, dp.cedula_identidad, dp.correo_electronico, dp.telefono_contacto, dl.estado, c.nombre AS cargo
                FROM datos_personales dp
                LEFT JOIN datos_laborales dl ON dp.id_pers = dl.id_pers
                LEFT JOIN cargos c ON dl.id_cargo = c.id_cargo
                WHERE dl.estado = ?";
        $params[] = $estado;
        $headers = ['Nombres', 'Apellidos', 'Cédula', 'Correo', 'Teléfono', 'Estado', 'Cargo'];
        $widths = [28, 28, 18, 38, 22, 17, 32];
        $campos = ['nombres', 'apellidos', 'cedula_identidad', 'correo_electronico', 'telefono_contacto', 'estado', 'cargo'];
        break;
    case 'vacaciones':
        $title = "Empleados en Vacaciones";
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
        $widths = [27, 27, 17, 29, 29, 17, 17, 28]; // total 191mm
        $campos = ['nombres', 'apellidos', 'cedula_identidad', 'fecha_inicio_periodo', 'fecha_fin_periodo', 'dias_asignados', 'dias_usados', 'estado'];
        break;
    case 'reposo':
        $title = "Empleados en Reposo";
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
        $widths = [24, 24, 18, 32, 14, 21, 21, 25]; // total 179mm
        $campos = ['nombres', 'apellidos', 'cedula_identidad', 'motivo_reposo', 'dias_otorgados', 'fecha_inicio', 'fecha_fin', 'estado'];
        break;
    case 'carga_familiar':
        $title = "Empleados con Carga Familiar";
        $sql = "SELECT dp.nombres, dp.apellidos, dp.cedula_identidad, cf.parentesco, cf.nombres_familiar, cf.apellidos_familiar, cf.cedula_familiar, cf.genero_familiar, cf.tiene_discapacidad
                FROM datos_personales dp
                INNER JOIN datos_socioeconomicos ds ON dp.id_pers = ds.id_pers
                INNER JOIN carga_familiar cf ON ds.id_socioeconomico = cf.id_socioeconomico";
        $headers = ['Empleado', 'Apellido', 'Cédula', 'Parentesco', 'Nombre Familiar', 'Apellido Familiar', 'Cédula Familiar', 'Género', 'Discapacidad'];
        $widths = [22, 22, 17, 17, 22, 22, 17, 15, 15]; // total 169mm
        $campos = ['nombres', 'apellidos', 'cedula_identidad', 'parentesco', 'nombres_familiar', 'apellidos_familiar', 'cedula_familiar', 'genero_familiar', 'tiene_discapacidad'];
        break;
    case 'auditoria':
        $title = "Auditoría (Logs de Acciones)";
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
        $widths = [10, 22, 22, 50, 18, 40, 25]; // total 187mm
        $campos = ['id', 'usuario', 'event_type', 'details', 'ip_address', 'user_agent', 'created_at'];
        break;
    default:
        die('Tipo de reporte no soportado.');
}

// --- Ejecutar la consulta ---
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

session_start();
if (isset($_SESSION['usuario']['id'])) {
    $user_id = $_SESSION['usuario']['id'];
    $event_type = 'generacion_reporte_pdf';
    $details = 'Generación de reporte: ' . ($title ?? $tipo ?? '');
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
    $stmt_log = $conexion->prepare("INSERT INTO action_logs (user_id, event_type, details, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt_log->bind_param("issss", $user_id, $event_type, $details, $ip_address, $user_agent);
    $stmt_log->execute();
    $stmt_log->close();
}

// --- Clase FPDF extendida para encabezado y filas alineadas ---
class PDF_MC_Table extends FPDF
{
    var $widths;
    var $aligns;
    var $headers;
    var $headerWidths;
    var $headerStartX;
    var $title;
    var $membrete_path;

    function SetTableFormat($headers, $widths, $startX, $title, $membrete_path) {
        $this->headers = $headers;
        $this->headerWidths = $widths;
        $this->headerStartX = $startX;
        $this->title = $title;
        $this->membrete_path = $membrete_path;
    }

    function SetWidths($w) { $this->widths = $w; }
    function SetAligns($a) { $this->aligns = $a; }

    function Header()
    {
        // Membrete institucional
        if ($this->membrete_path && file_exists($this->membrete_path)) {
            $this->Image($this->membrete_path, 10, 2, 280, 27);
            $this->SetY(28);
        } else {
            $this->SetY(15);
        }
        // Título centrado
        $titulo = $this->title ?? '';
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 10, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', (string)$titulo), 0, 1, 'C');
        $this->Ln(2);

        // Encabezado de la tabla
        $this->SetFillColor(44, 62, 80);
        $this->SetTextColor(255,255,255);
        $this->SetFont('Arial','B',10);
        $this->SetDrawColor(160,160,160);
        $this->SetX($this->headerStartX ?? 10);
        foreach (($this->headers ?? []) as $i => $header) {
            $w = $this->headerWidths[$i] ?? 20;
            $text = $header ?? '';
            $this->Cell($w, 10, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', (string)$text), 1, 0, 'C', true);
        }
        $this->Ln();
        $this->SetTextColor(44,62,80);
        $this->SetFont('Arial','',9);
    }

    function Row($data, $startX)
    {
        $nb = 0;
        for($i=0;$i<count($data);$i++)
            $nb = max($nb, $this->NbLines($this->widths[$i], $data[$i]));
        $h = 8 * $nb;
        $this->CheckPageBreak($h);
        $this->SetX($startX);
        for($i=0;$i<count($data);$i++)
        {
            $w = $this->widths[$i];
            $a = isset($this->aligns[$i]) ? $this->aligns[$i] : 'L';
            $x = $this->GetX();
            $y = $this->GetY();
            $this->Rect($x, $y, $w, $h);
            $txt = $data[$i] ?? '';
            $txt = (string)$txt;
            $txt = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $txt);
            $this->MultiCell($w, 8, $txt, 0, $a);
            $this->SetXY($x+$w, $y);
        }
        $this->Ln($h);
    }

    function CheckPageBreak($h)
    {
        if($this->GetY() + $h > $this->PageBreakTrigger)
            $this->AddPage($this->CurOrientation);
    }

    function NbLines($w, $txt)
    {
        $txt = (string)$txt;
        $cw = &$this->CurrentFont['cw'];
        if($w == 0) $w = $this->w - $this->rMargin - $this->x;
        $wmax = ($w - 2*$this->cMargin) * 1000 / $this->FontSize;
        $s = str_replace("\r", '', $txt);
        $nb = strlen($s);
        if($nb > 0 && $s[$nb-1] == "\n") $nb--;
        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $nl = 1;
        while($i < $nb)
        {
            $c = $s[$i];
            if($c == "\n") { $i++; $sep = -1; $j = $i; $l = 0; $nl++; continue; }
            if($c == ' ') $sep = $i;
            $l += $cw[$c] ?? 0;
            if($l > $wmax) {
                if($sep == -1) { if($i == $j) $i++; }
                else $i = $sep+1;
                $sep = -1; $j = $i; $l = 0; $nl++;
            } else $i++;
        }
        return $nl;
    }
}

$pdf = new PDF_MC_Table();

// Márgenes laterales y cálculo de startX
$margen_izquierdo = 10;
$margen_derecho = 10;
$pageWidth = $pdf->GetPageWidth();
$totalWidth = array_sum($widths);
$areaImprimible = $pageWidth - $margen_izquierdo - $margen_derecho;
if ($totalWidth >= $areaImprimible) $startX = $margen_izquierdo;
else $startX = $margen_izquierdo + ($areaImprimible - $totalWidth)/2;

$pdf->SetTableFormat($headers, $widths, $startX, $title, $membrete_path);
$pdf->AddPage('L', 'A4');
$pdf->SetWidths($widths);
$pdf->SetAligns(array_fill(0, count($widths), 'L'));
// Filas de datos
foreach ($registros as $row) {
    $fila = [];
    foreach ($campos as $i => $k) {
        $col = $row[$k] ?? '';
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $col)) {
            $col = date('d/m/Y', strtotime($col));
        }
        $fila[] = $col;
    }
    $pdf->Row($fila, $startX);
}

$pdf->Output('I', 'reporte.pdf');
exit;
?>