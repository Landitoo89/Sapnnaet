<?php
require 'conexion/conexion_db.php';
require_once('fpdf/fpdf.php');

// Ruta del membrete institucional (ajústala si es necesario)
$membrete_path = __DIR__ . 'img/logo-sapna.png';

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
        $widths = [35, 35, 25, 50, 30, 20, 35, 35];
        $campos = ['nombres', 'apellidos', 'cedula_identidad', 'correo_electronico', 'telefono_contacto', 'estado', 'departamento', 'cargo'];
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
        $widths = [35, 35, 25, 50, 30, 20, 35, 35];
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
        $widths = [35, 35, 25, 50, 30, 20, 40, 35];
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
        $widths = [35, 35, 25, 50, 30, 20, 35];
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
        $widths = [35, 35, 25, 50, 30, 20, 40, 35];
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
        $widths = [35, 35, 25, 50, 30, 20, 35];
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
        $widths = [35, 35, 25, 25, 25, 20, 20, 23];
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
        $widths = [35, 35, 25, 40, 12, 25, 25, 20];
        $campos = ['nombres', 'apellidos', 'cedula_identidad', 'motivo_reposo', 'dias_otorgados', 'fecha_inicio', 'fecha_fin', 'estado'];
        break;
    case 'carga_familiar':
        $title = "Empleados con Carga Familiar";
        $sql = "SELECT dp.nombres, dp.apellidos, dp.cedula_identidad, cf.parentesco, cf.nombres_familiar, cf.apellidos_familiar, cf.cedula_familiar, cf.genero_familiar, cf.tiene_discapacidad
                FROM datos_personales dp
                INNER JOIN datos_socioeconomicos ds ON dp.id_pers = ds.id_pers
                INNER JOIN carga_familiar cf ON ds.id_socioeconomico = cf.id_socioeconomico";
        $headers = ['Empleado', 'Apellido', 'Cédula', 'Parentesco', 'Nombre Familiar', 'Apellido Familiar', 'Cédula Familiar', 'Género', 'Discapacidad'];
        $widths = [30, 30, 25, 20, 30, 30, 25, 18, 18];
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
        $widths = [10, 25, 25, 65, 22, 40, 30];
        $campos = ['id', 'usuario', 'event_type', 'details', 'ip_address', 'user_agent', 'created_at'];
        break;
    default:
        die('Tipo de reporte no soportado.');
}

// --- Ejecutar la consulta ---
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
            $this->Image($this->membrete_path, 10, 8, 50, 20, 'PNG');
            $this->SetY(28);
        } else {
            $this->SetY(15);
        }
        // Título centrado
        $titulo = $this->title ?? '';
        $this->SetFont('Arial', 'B', 14); // Cambia aquí el tamaño del TÍTULO
        $this->Cell(0, 10, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', (string)$titulo), 0, 1, 'C');
        $this->Ln(2);

        // Encabezado de la tabla
        $this->SetFillColor(44, 62, 80);
        $this->SetTextColor(255,255,255);
        $this->SetFont('Arial','B',10);   // Cambia aquí el tamaño de letra del ENCABEZADO
        $this->SetDrawColor(160,160,160);
        $this->SetX($this->headerStartX ?? 10);
        foreach (($this->headers ?? []) as $i => $header) {
            $w = $this->headerWidths[$i] ?? 20;
            $text = $header ?? '';
            $this->Cell($w, 10, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', (string)$text), 1, 0, 'C', true);
        }
        $this->Ln();
        $this->SetTextColor(44,62,80);
        $this->SetFont('Arial','',9);    // Cambia aquí el tamaño de letra de los DATOS
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

// Márgenes manuales (ajusta si quieres más o menos margen)
$margen_izquierdo = 0;
$margen_derecho = 505;
$pageWidth = $pdf->GetPageWidth();
$totalWidth = array_sum($widths);
$areaImprimible = $pageWidth - $margen_izquierdo - $margen_derecho;
$startX = $margen_izquierdo + ($areaImprimible - $totalWidth) / 2;

// Pasa el startX calculado
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